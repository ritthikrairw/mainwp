<?php

class MainWP_Wordfence_Setting {
	// Singleton
	private static $instance = null;
	private $configs         = null;

	private $individual_configs_loaded = false;
	private $general_configs_loaded    = false;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Wordfence_Setting();
		}

		return self::$instance;
	}

	public function load_configs( $site_id = false, $websites = array() ) {
		if ( $site_id ) {
			if ( ! $this->individual_configs_loaded ) {
				$this->configs                   = new MainWP_Wordfence_Config_Site( $site_id );
				$this->individual_configs_loaded = true;
			}
		} else {
			if ( ! $this->general_configs_loaded ) {
				$this->configs                = new MainWP_Wordfence_Config();
				$this->general_configs_loaded = true;
			}
		}
		return $this->configs;
	}

	public function __construct() {

	}

	public static function init() {
		if ( isset( $_POST['submit'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'mwp-wfc-nonce' ) && isset( $_POST['wfc_individual_settings_site_id'] ) && ! empty( $_POST['wfc_individual_settings_site_id'] ) ) {
			$return = MainWP_Wordfence::handlePostSettings( $_POST['wfc_individual_settings_site_id'] );
			if ( is_array( $return ) ) {
				if ( isset( $return['ok'] ) ) {
					update_option( 'mainwp_wfc_do_save_individual_setting', 'yes' );
				} elseif ( isset( $return['errorMsg'] ) ) {
					update_option( 'mainwp_wfc_save_individual_setting_error', $return['errorMsg'] );
				}
			} else {
				update_option( 'mainwp_wfc_save_individual_setting_error', 'Undefined error.' );
			}
		}
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_wfc_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_mainwp_wfc_save_firewall_settings', array( $this, 'ajax_save_firewall_settings' ) );
		add_action( 'wp_ajax_mainwp_wfc_save_settings_reload', array( $this, 'ajax_save_settings_reload' ) );
		add_action( 'wp_ajax_mainwp_wfc_change_override_general_settings', array( $this, 'ajax_change_override_general_settings' ) );
		add_action( 'wp_ajax_mainwp_wfc_change_general_settings_use_premium', array( $this, 'ajax_change_general_settings_use_premium' ) );
		add_action( 'wp_ajax_mainwp_wfc_save_general_settings_to_child', array( $this, 'ajax_save_general_settings_to_child' ) );
		add_action( 'wp_ajax_mainwp_wfc_load_more_keys', array( $this, 'ajax_loading_more_keys' ) );
		add_action( 'wp_ajax_mainwp_wfc_diagnostic_load_more_sites', array( $this, 'ajax_diagnostic_load_more_sites' ) );
	}

	function ajax_save_settings() {
		$siteid = $_POST['siteId'];
		if ( empty( $siteid ) ) {
			die( json_encode( 'FAIL' ) );
		}
		$selected_section = isset( $_POST['_ajax_saving_section'] ) ? $_POST['_ajax_saving_section'] : '';
		$information      = $this->perform_save_settings( $siteid, false, $selected_section );
		die( json_encode( $information ) );
	}

	function ajax_save_firewall_settings() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wfc-nonce' ) ) {
			wp_send_json( array( 'error' => esc_html( __( 'Invalid request' ) ) ) );
		}

		$siteid = $_POST['siteId'];
		if ( empty( $siteid ) ) {
			wp_send_json( array( 'error' => esc_html( __( 'Empty Site ID' ) ) ) );
		}

		$w        = new MainWP_Wordfence_Config_Site( $siteid ); // new: to load data
		$override = $w->is_override();
		if ( $override ) {
			die( json_encode( array( 'error' => 'Not Updated - Individual site settings are in use' ) ) );
			return;
		}

		$settings     = array();
		$ext_settings = get_option( 'mainwp_wfc_general_extra_settings', array() );
		if ( is_array( $ext_settings ) && isset( $ext_settings['general_firewall'] ) ) {
			$settings = $ext_settings['general_firewall'];
		}

		$check_values = array( 'wafStatus', 'learningModeGracePeriodEnabled', 'learningModeGracePeriod' );

		$post_data = array();
		foreach ( $check_values as $value ) {
			if ( isset( $settings[ $value ] ) ) {
				$post_data[ $value ] = $settings[ $value ];
			}
		}

		global $mainWPWordfenceExtensionActivator;

		$post_data['mwp_action']      = 'save_waf_config';
		$post_data['wafConfigAction'] = 'config';

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['data'] ) ) {
			$update = array(
				'wafData'                        => $information['data'],
				'wafStatus'                      => $post_data['wafStatus'],
				'learningModeGracePeriodEnabled' => $post_data['learningModeGracePeriodEnabled'],
			);
			if ( isset( $information['learningModeGracePeriod'] ) ) {
				$update['learningModeGracePeriod'] = $information['learningModeGracePeriod'];
			}
			MainWP_Wordfence_DB::get_instance()->update_extra_settings_fields_values_by( $siteid, $update );

		}

		die( json_encode( $information ) );
	}

	function mainwp_apply_plugin_settings( $siteid ) {
		// forced apply settings to child so save ALL settings
		$all_section = MainWP_Wordfence_Config::OPTIONS_TYPE_ALL;
		$information = $this->perform_save_settings( $siteid, true, $all_section );
		$result      = array();
		if ( is_array( $information ) ) {
			if ( isset( $information['ok'] ) ) {
				$result = array( 'result' => 'success' );
			} elseif ( $information['error'] ) {
				$result = array( 'error' => $information['error'] );
			} else {
				$result = array( 'result' => 'failed' );
			}
		} else {
			$result = array( 'result' => 'failed' );
		}
		die( json_encode( $result ) );
	}

	function simple_crypt( $key, $data, $action = 'encrypt' ) {
		$res = '';
		if ( $action == 'encrypt' ) {
			$string = base64_encode( serialize( $data ) );
		} else {
			$string = $data;
		}
		for ( $i = 0; $i < strlen( $string ); $i++ ) {
			$c = ord( substr( $string, $i ) );
			if ( $action == 'encrypt' ) {
				$c   += ord( substr( $key, ( ( $i + 1 ) % strlen( $key ) ) ) );
				$res .= chr( $c & 0xFF );
			} else {
				$c   -= ord( substr( $key, ( ( $i + 1 ) % strlen( $key ) ) ) );
				$res .= chr( abs( $c ) & 0xFF );
			}
		}

		if ( $action !== 'encrypt' ) {
			$res = unserialize( base64_decode( $res ) );
		}
		return $res;
	}

	public function perform_save_settings( $siteid, $forced_global_setting = false, $pSection = '' ) {

		$saving_opts = MainWP_Wordfence_Config::getSectionSettings( $pSection );

		if ( empty( $saving_opts ) ) {
			return array( 'error' => 'Invalid fields number' );
		}
		$w         = new MainWP_Wordfence_Config_Site( $siteid ); // new: to load data
		$cacheType = $w->get_cacheType();
		$apiKey    = MainWP_Wordfence_Config_Site::$apiKey;

		if ( ! $forced_global_setting && $override = $w->is_override() ) {
			$options = MainWP_Wordfence_Config_Site::load_settings();
		} else {
			// if forced save general settings
			// or saving general settings to child
			// or saving to child site from general settings
			// then get general settings to save
			$options = MainWP_Wordfence_Config::load_settings();
		}

		if ( ! $forced_global_setting ) {
			$individual = isset( $_POST['individual'] ) && $_POST['individual'] ? true : false;
			if ( $individual && ! $override ) {
				return array( 'error' => 'Update Failed: Override General Settings need to be set to Yes.' );
			} elseif ( ! $individual && $override ) {
				return array( 'result' => 'OVERRIDED' );
			}
		}

		global $mainWPWordfenceExtensionActivator;

		$post_data = array(
			'mwp_action'    => 'save_settings_new', // new version of saving settings
			'savingSection' => $pSection,
			'apiKey'        => $apiKey,
		);

		foreach ( $options as $key => $val ) {
			if ( ! in_array( $key, $saving_opts ) ) {
				unset( $options[ $key ] );
			}
		}

		if ( isset( $options['apiKey'] ) ) {
			unset( $options['apiKey'] );
		}

		$post_data['settings']  = $this->simple_crypt( 'thisisakey', $options, 'encrypt' ); // to fix pass through sec rules of Dreamhost
		$post_data['encrypted'] = 1;

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		// error_log( print_r($information, true ) );
		if ( is_array( $information ) ) {
			$update  = array( 'site_id' => $siteid );
			$perform = false;
			if ( isset( $information['isPaid'] ) ) {
				$perform          = true;
				$update['isPaid'] = $information['isPaid'];
				$update['apiKey'] = $information['apiKey'];
			}
			if ( isset( $information['cacheType'] ) && $cacheType != $information['cacheType'] ) {
				$perform             = true;
				$update['cacheType'] = $information['cacheType'];
			}
			if ( $perform ) {
				MainWP_Wordfence_DB::get_instance()->update_setting( $update );
			}
		}
		return $information;
	}

	public function ajax_save_settings_reload() {
		$siteid = $_POST['siteId'];
		if ( empty( $siteid ) ) {
			die( 'Error reload.' );
		}

		new MainWP_Wordfence_Config_Site( $siteid );
		$is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid' );
		$api_Key = MainWP_Wordfence_Config_Site::get( 'apiKey' );
		?>
		<tr>
			<th>Wordfence API Key:</th>
			<td><input type="text" class="apiKey" name="apiKey[<?php echo $siteid; ?>]"
					   value="<?php echo esc_attr( $api_Key ); ?>" size="80"/>&nbsp;
			<?php if ( $is_Paid ) { ?>
					License Status: Premium Key. <span style="font-weight: bold; color: #0A0;">Premium scanning enabled!</span>
				<?php } else { ?>
				License Status: <span style="color: #F00; font-weight: bold;">Free Key</span>.
					<?php } ?>
			</td>
		</tr>
		<tr>
			<th>&nbsp;</th>
			<td>
			<?php if ( $is_Paid ) { ?>
					<table border="0">
						<tr>
							<td><a href="https://www.wordfence.com/manage-wordfence-api-keys/" target="_blank"><input
										type="button" value="Renew your premium license"/></a></td>
							<td>&nbsp;</td>
							<td><input type="button" value="Downgrade to a free license"
									   onclick="MWP_WFAD.downgradeLicense(<?php echo $siteid; ?>);"/></td>
						</tr>
					</table>
				<?php } ?>
			</td>
		</tr>
		<?php
		die();
	}

	public function ajax_change_override_general_settings() {
		$siteid = isset( $_POST['siteId'] ) ? $_POST['siteId'] : false;
		if ( empty( $siteid ) ) {
			wp_send_json( array( 'error' => esc_html( __( 'Invalid data.' ) ) ) );
		}

		$override = isset( $_POST['override'] ) && $_POST['override'] ? 1 : 0;
		MainWP_Wordfence_DB::get_instance()->update_setting(
			array(
				'site_id'  => $siteid,
				'override' => $override,

			)
		);

		die( json_encode( array( 'ok' => 1 ) ) );
	}

	public function ajax_change_general_settings_use_premium() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wfc-nonce' ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}
		$value = isset( $_POST['value'] ) && $_POST['value'] ? 1 : 0;
		update_option( 'mainwp_wordfence_use_premium_general_settings', $value );

		die( json_encode( array( 'ok' => 1 ) ) );
	}

	public function ajax_loading_more_keys() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wfc-nonce' ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}

		$paged = isset( $_POST['paged'] ) ? $_POST['paged'] : 0;

		$data     = MainWP_Wordfence::get_bulk_wfc_sites( $paged );
		$websites = $data['result'];
		$last     = $data['last'];

		$api_settings = MainWP_Wordfence_Config::get_api_settings();

		$result = '';

		if ( is_array( $websites ) && count( $websites ) > 0 ) :
			ob_start();
			foreach ( $websites as $site_id => $site_name ) {
				$is_Paid = isset( $api_settings[ $site_id ] ) ? $api_settings[ $site_id ]->isPaid : 0;
				$api_Key = isset( $api_settings[ $site_id ] ) ? $api_settings[ $site_id ]->apiKey : '';
				?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php echo stripslashes( $site_name ); ?></label>
				<div class="ten wide column">
					<div class="ui labeled input">
						<div class="ui label"><?php echo $is_Paid ? 'Premium Key' : 'Free Key'; ?></div>
						<input type="text" class="apiKey" name="apiKey[<?php echo $site_id; ?>]" value="<?php echo esc_attr( $api_Key ); ?>"/>
					</div>
				</div>
			</div>
				<?php
			}
			$result = ob_get_clean();
		endif;

		die(
			wp_json_encode(
				array(
					'result' => $result,
					'last'   => $last,
				)
			)
		);
	}

	public function ajax_diagnostic_load_more_sites() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wfc-nonce' ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}

		$paged = isset( $_POST['paged'] ) ? $_POST['paged'] : 0;

		$data     = MainWP_Wordfence::get_bulk_wfc_sites( $paged );
		$websites = $data['result'];
		$last     = $data['last'];

		$result = '';

		if ( is_array( $websites ) && count( $websites ) > 0 ) :
			ob_start();
			foreach ( $websites as $site_id => $site_name ) {
				echo '<option value="' . $site_id . '">' . stripslashes( $site_name ) . '</option>';
			}
			$result = ob_get_clean();
		endif;

		die(
			wp_json_encode(
				array(
					'result' => $result,
					'last'   => $last,
				)
			)
		);
	}

	public function ajax_save_general_settings_to_child() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wfc-nonce' ) ) {
				wp_send_json( array( 'error' => esc_html( __( 'Invalid request' ) ) ) );
		}

			 $siteid = isset( $_POST['siteId'] ) ? $_POST['siteId'] : false;
		if ( empty( $siteid ) ) {
			wp_send_json( array( 'error' => esc_html( __( 'Invalid data' ) ) ) );
		}

			 // to saving general settings from individual page, so save ALL settings
			 $all_section = MainWP_Wordfence_Config::OPTIONS_TYPE_ALL;
			 $information = $this->perform_save_settings( $siteid, true, $all_section );
			 die( json_encode( $information ) );
	}

	public static function gen_listing_sites( $do_action, $tab = '', $pSection = '' ) {

		global $mainWPWordfenceExtensionActivator;
		
		$others = array(
			'plugins_slug' => 'wordfence/wordfence.php',
		);

		$websites  = apply_filters( 'mainwp_getsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), null, false, $others );
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}

		$option     = array(
			'plugin_upgrades' => true,
			'plugins'         => true,
		);
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $sites_ids, array(), $option );

		$all_the_plugin_sites = array();
		foreach ( $dbwebsites as $website ) {
			if ( $website && $website->plugins != '' ) {
				$plugins = json_decode( $website->plugins, 1 );
				if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
					foreach ( $plugins as $plugin ) {
						if ( 'wordfence/wordfence.php' == $plugin['slug'] ) {
							if ( $plugin['active'] ) {
								$all_the_plugin_sites[] = MainWP_Wordfence_Utility::map_site(
									$website,
									array(
										'id',
										'name',
									)
								);
								break;
							}
						}
					}
				}
			}
		}
		if ( count( $all_the_plugin_sites ) > 0 ) {
			?>
			<div class="ui modal" id="mainwp-wordfence-sync-modal">
				<div class="header">
					<?php
					if ( $do_action == 'save_settings' ) {
						echo __( 'Wordfence Settings Synchronization', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'bulk_import' ) {
						echo __( 'Wordfence Settings Import', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'save_firewall' ) {
						echo __( 'Wordfence Firewall Settings Synchronization', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'save_caching_type' ) {
						echo __( 'Caching Type Synchronization', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'save_cache_options' ) {
						echo __( 'Wordfence Cache Settings Synchronization', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'clear_page_cache' ) {
						echo __( 'Clear Wordfence Cache', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'get_cache_stats' ) {
						echo __( 'Wordfence Cache Stats Synchronization', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'add_cache_exclusion' ) {
						echo __( 'Wordfence Cache Exclusion Synchronization', 'mainwp-wordfence-extension' );
						?>
						<span id="mainwp_wfc_bulk_cache_exclusion_id" value="<?php echo esc_attr( $_GET['id'] ); ?>"></span>
						<?php
					} elseif ( $do_action == 'remove_cache_exclusion' ) {
						echo __( 'Wordfence Cache Exclusion Synchronization', 'mainwp-wordfence-extension' );
						?>
						<span id="mainwp_wfc_bulk_cache_exclusion_id" value="<?php echo esc_attr( $_GET['id'] ); ?>"></span>
						<?php
					} elseif ( $do_action == 'waf_update_rules' ) {
						echo __( 'Wordfence Firewall Rules Synchronization', 'mainwp-wordfence-extension' );
					} elseif ( $do_action == 'save_debugging_options' ) {
						echo __( 'Wordfence Debugging Settings Synchronization', 'mainwp-wordfence-extension' );
					} else {
						$do_action = '';
					}
					?>
				</div>
				<div class="scrolling content">
					<div class="ui relaxed divided list">
						<?php foreach ( $all_the_plugin_sites as $website ) : ?>
							<div class="item">
								<?php echo stripslashes( $website['name'] ); ?>
								<span class="itemToProcess right floated" siteid="<?php echo $website['id']; ?>" status="queue"><span class="loading" style="display: none"><i class="notched circle loading icon"></i></span> <span class="status"><i class="clock outline icon"></i></span>
								<div style="display: none" class="detailed"></div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="actions">
					<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-wordfence-extension' ); ?></div>
					<input type="hidden" id="_post_popup_saving_section" value="<?php echo esc_html( $pSection ); ?>"  />
				</div>
			</div>
			<script type="text/javascript">
			jQuery(document).ready(function ($) {
				jQuery( '#mainwp-wordfence-sync-modal' ).modal({ onHide: function (){
							var _section = jQuery('#_post_popup_saving_section').val();
							var _tab = 'network_setting';
							switch(_section) {
								case 'firewall':
									_tab = 'network_firewall';
									break;
								case 'blocking':
									_tab = 'network_blocking';
									break;
								case 'scanner':
									_tab = 'network_scan';
									break;
								case 'livetraffic':
									_tab = 'network_traffic';
									break;
								case 'diagnostics':
									_tab = 'diagnostics';
									break;
							}
							window.location.replace('admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=' + _tab );
						}
				}).modal( 'show' );
				<?php if ( $do_action == 'save_settings' ) { ?>
				mainwp_wfc_save_setting_start_next();
				<?php } elseif ( $do_action == 'bulk_import' ) { ?>
				wfc_save_general_import_settings = true;
				mainwp_wfc_bulk_import_start_next('<?php echo esc_html( $_GET['token'] ); ?>');
				<?php } elseif ( $do_action == 'save_firewall' ) { ?>
				mainwp_wfc_save_firewall_start_next();
					<?php
				} elseif ( $tab == 'performance' ) {
					// seem not used any more
					?>
					mainwp_wfc_bulk_performance_setup_start_next('<?php echo $do_action; ?>');
				<?php } elseif ( $tab == 'diagnostics' ) { ?>
					mainwp_wfc_bulk_diagnostics_start_next('<?php echo $do_action; ?>');
				<?php } ?>
			} );
			</script>
			<?php
			return true;
		} else {
			echo '<div class="ui yellow message">' . __( 'Wordfence not detected on the child sites.', 'mainwp-wordfence-extension' ) . '</div>';
			?>
				<script>
				jQuery(document).ready(function ($) {
				  setTimeout(function () {
					location.href = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_setting';
				  }, 3000);
				} );
				</script>
			<?php
		}
	}

	public static function gen_settings_tab( $is_individual = false ) {
		?>
	 <div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_settings"></div>
		<?php
		if ( $is_individual ) {
			?>
			<div class="ui form segment">
			<?php
			self::gen_settings_scan_schedule( true );
			self::gen_settings_individual_licenses();
			self::gen_settings_view_customization( true );
			self::gen_settings_basic( true );
			self::gen_settings_alerts( true );
			self::gen_settings_email( true );
			self::gen_dashboard_notification_options( true );
			self::gen_settings_import_settings( true );
			?>
			</div>
			<?php
		} else {
			?>
			<div class="ui form segment">
			<?php
			self::gen_settings_licenses();
			self::gen_settings_scan_schedule( false );
			self::gen_settings_view_customization( false );
			self::gen_settings_basic( false );
			self::gen_settings_alerts( false );
			self::gen_settings_email( false );
			self::gen_dashboard_notification_options( false );
			self::gen_settings_import_settings( false );
			self::gen_save_general_button();
			?>
			</div>
			<?php
		}

	}

	public static function gen_save_general_button() {
		?>
		<div class="ui divider"></div>
		<input type="button" onclick="MWP_WFAD.saveConfig();" class="ui right floated green big button" value="<?php _e( 'Save Settings', 'mainwp-wordfence-extension' ); ?>">
		<?php
	}

	public static function gen_firewall_settings_tab( $is_individual = false ) {
		?>
		<div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_settings"></div>
	  <div class="mwp_wfc_firewall_settings_form_content">
				<div class="ui form segment">
		<?php
		if ( $is_individual ) {
					MainWP_Wordfence_Firewall::gen_individual_firewall_basic();
					MainWP_Wordfence_Firewall::gen_advanced_firewall_options( true );
					MainWP_Wordfence_Firewall::gen_settings_rate_limiting_rules( true );
					MainWP_Wordfence_Firewall::gen_settings_login_security( true );
					MainWP_Wordfence_Firewall::gen_whitelisted_url();
		} else {
			if ( $_GET['tab'] == 'network_firewall' ) {
				MainWP_Wordfence_Firewall::gen_general_firewall_basic();
				MainWP_Wordfence_Firewall::gen_advanced_firewall_options( false );
				MainWP_Wordfence_Firewall::gen_settings_rate_limiting_rules( false );
				MainWP_Wordfence_Firewall::gen_settings_login_security( false );
			}
		}

		// individual page have own Saving button
		if ( ! $is_individual ) {
			self::gen_save_general_button();
		}
		?>
				</div>
	  </div>
		<?php
	}

	public static function gen_live_traffic_settings_tab( $is_individual = false ) {

		$current_tab = $current_action = '';

		if ( isset( $_GET['action'] ) ) {
			$current_action = $_GET['action'];
		}

		if ( isset( $_GET['tab'] ) ) {
			$current_tab = $_GET['tab'];
		}

		?>
		<div class="ui form segment">
		<div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_settings"></div>
		<?php
		if ( $is_individual ) {
			MainWP_Wordfence_Live_Traffic::gen_live_traffic_options( true );
			MainWP_Wordfence_Live_Traffic::gen_live_traffic_tab( true );
		} else {
			if ( $current_tab == 'network_traffic' || $current_action == 'traffic' ) {
				if ( $current_tab == 'network_traffic' ) {
					MainWP_Wordfence_Live_Traffic::gen_live_traffic_options( false );
				}
				MainWP_Wordfence_Live_Traffic::gen_live_traffic_tab( false );
			}
		}
		?>
		</div>
		<?php
	}

	public static function gen_blocking_settings_tab( $is_individual = false ) {
		echo '<div class="ui form segment">';
		MainWP_Wordfence_Blocking::gen_blocking_general_settings_tab();
		MainWP_Wordfence_Blocking::gen_blocking_custom_rules_tab();
		MainWP_Wordfence_Blocking::gen_blocking_rules_ip_address_tab();

		// individual page have own Saving button
		if ( ! $is_individual ) {
			if ( ! isset( $_GET['action'] ) ) {
				self::gen_save_general_button();
			}
		}
		echo '</div>';
	}

	public static function gen_scan_settings_tab( $is_individual = false ) {
		?>
		<div class="ui form segment">
			<div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_settings"></div>
			<div class="mwp_wfc_scan_settings_form_content">
			<?php
			MainWP_Wordfence_Scan::gen_scans_scheduling();
			MainWP_Wordfence_Scan::gen_scans_basic_settings();
			MainWP_Wordfence_Scan::gen_scans_general_settings();
			MainWP_Wordfence_Scan::gen_scans_performace_settings();
			MainWP_Wordfence_Scan::gen_scans_advanced_settings();
			// individual page have own Saving button
			if ( ! $is_individual ) {
				self::gen_save_general_button();
			}
			?>
			</div>
		</div>
		<?php
	}

	public static function gen_settings_licenses() {

		$w = self::get_instance()->load_configs();

		if ( empty( $w ) ) {
			return;
		}

		$data     = MainWP_Wordfence::get_bulk_wfc_sites();
		$websites = $data['result'];

		$is_Paids = $w->get_isPaids();
		$api_Keys = $w->get_apiKeys();

		$is_premium = get_option( 'mainwp_wordfence_use_premium_general_settings' );
		?>
		<div class="ui dividing header"><?php echo __( 'Wordfence License', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Use premium version', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ui two wide column toggle checkbox">
				<input type="checkbox" id="mwp_wordfence_general_use_premium" name="mwp_wordfence_general_use_premium"  <?php echo ( $is_premium ? 'checked="checked"' : '' ); ?> value="yes"/>
			</div>
			<div class="eight wide column">
				<span id="wfc_change_use_premium_working"></span>
			</div>
		</div>
		<?php if ( is_array( $websites ) && count( $websites ) > 0 ) : ?>		
		<div class="ui accordion">
			<div class="ui title" style="padding-left:10px"> <?php echo _e( 'Show/hide Licenses', 'mainwp-wordfence-extension' ); ?></div>	
		<div class="ui content">
			<?php
			foreach ( $websites as $site_id => $site_name ) {
				$is_Paid = isset( $is_Paids[ $site_id ] ) ? $is_Paids[ $site_id ] : 0;
				$api_Key = isset( $api_Keys[ $site_id ] ) ? $api_Keys[ $site_id ] : '';
				?>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo stripslashes( $site_name ); ?></label>
					<div class="ten wide column">
						<div class="ui labeled input">
							<div class="ui label"><?php echo $is_Paid ? 'Premium Key' : 'Free Key'; ?></div>
						  <input type="text" class="apiKey" name="apiKey[<?php echo $site_id; ?>]" value="<?php echo esc_attr( $api_Key ); ?>"/>
						</div>
					</div>
				</div>
			<?php } ?>
				<div class="ui grid field wfc-load-more-api-wrapper">
					<label class="six wide column middle aligned"></label>
					<div class="ten wide column">
						<a href="#" id="wfc-load-more-api" load-paged="1"><?php echo _e( 'Load more', 'mainwp-wordfence-extension' ); ?></a><div class="load-more-status" style="display:inline"></div>
					</div>
				</div>
		</div>
		</div>
		<script type="application/javascript">
		   jQuery( document ).ready( function () {
				jQuery( '.ui.accordion' ).accordion( {
					exclusive: false,
					duration: 200,
				} );			
			} );
		</script>
		
		<?php else : ?>
			<div class="ui yellow message">
				<?php _e( 'No websites were found with the Wordfence plugin installed.', 'mainwp-wordfence-extension' ); ?>
			</div>
		<?php endif; ?>
		<?php
	}

	public static function gen_settings_individual_licenses() {
		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		$is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid', 0 );
		$api_Key = MainWP_Wordfence_Config_Site::get( 'apiKey', '' );
		?>
		<div class="ui dividing header"><?php echo __( 'Licenses', 'mainwp-wordfence-extension' ); ?></div>

		<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php echo __( 'Wordfence API key', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<div class="ui labeled input">
		  <div class="ui label"><?php echo $is_Paid ? 'Premium Key' : 'Free Key'; ?></div>
					<input type="text" class="apiKey" name="apiKey[<?php echo $current_site_id; ?>]" value="<?php echo esc_attr( $api_Key ); ?>" />
		</div>
	  </div>
	</div>
		<?php
	}

	public static function gen_settings_scan_schedule( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = self::get_instance()->load_configs( $current_site_id );
		if ( empty( $w ) ) {
			return;
		}

		$override = 0;
		if ( $current_site_id ) {
			$override = $w->is_override();
		}
		?>
		<div class="ui dividing header"><?php echo __( 'Automatic Scans', 'mainwp-wordfence-extension' ); ?></div>
		<?php if ( $current_site_id ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Override general settings', 'mainwp-wordfence-extension' ); ?></label>
			<div class="two wide column ui toggle checkbox">
				<input type="checkbox" id="mainwp_wfc_override_global_setting" name="mainwp_wfc_override_global_setting" <?php echo( 0 == $override ? '' : 'checked="checked"' ); ?> value="1"/>
			</div>
			<div class="eight wide column">
				<span class="wfc_change_override_working"></span>
			</div>
		</div>
		<?php endif; ?>
		<?php
		$scheduleScan = $w->get( 'scheduleScan', 'disabled' );
		?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Scan schedule', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column">
				<select id="scheduleScan" name="scheduleScan" class="ui dropdown">
					<option value="disabled"<?php selected( $scheduleScan, 'disabled' ); ?>>N/A</option>
					<option value="twicedaily"<?php selected( $scheduleScan, 'twicedaily' ); ?>>Twice a day</option>
					<option value="daily"<?php selected( $scheduleScan, 'daily' ); ?>>Once a day</option>
					<option value="weekly"<?php selected( $scheduleScan, 'weekly' ); ?>>Once a week</option>
					<option value="monthly"<?php selected( $scheduleScan, 'monthly' ); ?>>Once a month</option>
				</select>
			</div>
		</div>
		<?php
	}

	public static function gen_settings_view_customization( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = self::get_instance()->load_configs( $current_site_id );

		if ( empty( $w ) ) {
			return;
		}
		?>

		<div class="ui dividing header"><?php echo __( 'View Customization', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Display "All Options" menu item', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="displayTopLevelOptions" class="wfConfigElem" name="displayTopLevelOptions" value="1" <?php $w->cb( 'displayTopLevelOptions' ); ?> />
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Display "Blocking" menu item', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="displayTopLevelBlocking" class="wfConfigElem" name="displayTopLevelBlocking" value="1" <?php $w->cb( 'displayTopLevelBlocking' ); ?> />
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Display "Live Traffic" menu item', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="displayTopLevelLiveTraffic" class="wfConfigElem" name="displayTopLevelLiveTraffic" value="1" <?php $w->cb( 'displayTopLevelLiveTraffic' ); ?> />
			</div>
		</div>

		<?php
	}

	public static function gen_settings_basic( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = self::get_instance()->load_configs( $current_site_id );

		if ( empty( $w ) ) {
			return;
		}

		if ( $current_site_id ) {
			$is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid', 0 );
		} else {
			$is_Paid = $is_premium = get_option( 'mainwp_wordfence_use_premium_general_settings' );
		}
		?>
		<div class="ui dividing header"><?php echo __( 'General Wordfence Options', 'mainwp-wordfence-extension' ); ?></div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Update Wordfence automatically when a new version is released', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="autoUpdate" class="wfConfigElem" name="autoUpdate" value="1" <?php $w->cb( 'autoUpdate' ); ?> />
			</div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Where to email alerts', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" id="alertEmails" class="mwp-wf-form-control" name="alertEmails" value="<?php $w->f( 'alertEmails' ); ?>"/>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'How does Wordfence get IPs', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="howGetIPs" name="howGetIPs" class="mwp-wf-form-control ui dropdown">
					<option value="">Let Wordfence use the most secure method to get visitor IP addresses. Prevents spoofing and works with most sites. <strong>(Recommended)</strong></option>
					<option value="REMOTE_ADDR"<?php $w->sel( 'howGetIPs', 'REMOTE_ADDR' ); ?>>Use PHP's built in REMOTE_ADDR and don't use anything else. Very secure if this is compatible with your site.</option>
					<option value="HTTP_X_FORWARDED_FOR"<?php $w->sel( 'howGetIPs', 'HTTP_X_FORWARDED_FOR' ); ?>>Use the X-Forwarded-For HTTP header. Only use if you have a front-end proxy or spoofing may result.</option>
					<option value="HTTP_X_REAL_IP"<?php $w->sel( 'howGetIPs', 'HTTP_X_REAL_IP' ); ?>>Use the X-Real-IP HTTP header. Only use if you have a front-end proxy or spoofing may result.</option>
					<option value="HTTP_CF_CONNECTING_IP"<?php $w->sel( 'howGetIPs', 'HTTP_CF_CONNECTING_IP' ); ?>> Use the Cloudflare "CF-Connecting-IP" HTTP header to get a visitor IP. Only use if you're using Cloudflare. </option>
				</select>
				<span class="wf-help-block"><a href="#" class="mwp-do-show" data-selector="#howGetIPs_trusted_proxies">+ Edit trusted proxies</a></span>
		  </div>
		</div>

		<div class="ui grid field hidden" id="howGetIPs_trusted_proxies">
		  <label class="six wide column middle aligned"><?php _e( 'Trusted proxies', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<textarea class="mwp-wf-form-control" rows="4" name="howGetIPs_trusted_proxies" id="howGetIPs_trusted_proxies_field"><?php echo $w->getHTML( 'howGetIPs_trusted_proxies' ); ?></textarea>
				<?php if ( false && $current_site_id ) { ?>
						<script type="application/javascript">
								(function($) {
										var updateIPPreview = function() {
												MWP_WFAD.updateIPPreview({howGetIPs: $('#howGetIPs').val(), 'howGetIPs_trusted_proxies': $('#howGetIPs_trusted_proxies_field').val()}, function(ret) {
														if (ret && ret.ok) {
																$('#howGetIPs-preview-all').html(ret.ipAll);
																$('#howGetIPs-preview-single').html(ret.ip);
														}
														else {
																//TODO: implementing testing whether or not this setting will lock them out and show the error saying that they'd lock themselves out
														}
												});
										};

										$('#howGetIPs').on('change', function() {
												updateIPPreview();
										});

										var coalescingUpdateTimer;
										$('#howGetIPs_trusted_proxies_field').on('keyup', function() {
												clearTimeout(coalescingUpdateTimer);
												coalescingUpdateTimer = setTimeout(updateIPPreview, 1000);
										});
								})(jQuery);
						</script>
				<?php } ?>
			</div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Hide WordPress version', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="other_hideWPVersion" class="wfConfigElem" name="other_hideWPVersion" value="1" <?php $w->cb( 'other_hideWPVersion' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Disable code execution for uploads directory', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="disableCodeExecutionUploads" class="wfConfigElem" name="disableCodeExecutionUploads" value="1" <?php $w->cb( 'disableCodeExecutionUploads' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Pause live updates when window loses focus', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="liveActivityPauseEnabled" class="wfConfigElem" name="liveActivityPauseEnabled" value="1" <?php $w->cb( 'liveActivityPauseEnabled' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Update interval in seconds (2 is default)', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" id="actUpdateInterval" name="actUpdateInterval" value="<?php $w->f( 'actUpdateInterval' ); ?>"/>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Bypass the LiteSpeed "noabort" check', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="other_bypassLitespeedNoabort" class="wfConfigElem" name="other_bypassLitespeedNoabort" value="1" <?php $w->cb( 'other_bypassLitespeedNoabort' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Delete Wordfence tables and data on deactivation?', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="deleteTablesOnDeact" class="wfConfigElem" name="deleteTablesOnDeact" value="1" <?php $w->cb( 'deleteTablesOnDeact' ); ?> />
		  </div>
		</div>
		<?php
	}

	public static function gen_settings_alerts( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = self::get_instance()->load_configs( $current_site_id );
		if ( empty( $w ) ) {
			return;
		}
		?>
	<div class="ui dividing header"><?php echo __( 'Email Alert Preferences', 'mainwp-wordfence-extension' ); ?></div>
		<?php
		$emails = $w->get_AlertEmails();
		if ( sizeof( $emails ) < 1 ) {
			echo "<div class='ui yellow message'>You have not configured an email to receive alerts yet. Set this up under \"Basic Options\" above.</div>\n";
		}
		?>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Email me when Wordfence is automatically updated', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_update" class="wfConfigElem" name="alertOn_update" value="1" <?php $w->cb( 'alertOn_update' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Email me if Wordfence is deactivated', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_wordfenceDeactivated" class="wfConfigElem" name="alertOn_wordfenceDeactivated" value="1" <?php $w->cb( 'alertOn_wordfenceDeactivated' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Email me if the Wordfence Web Application Firewall is turned off', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_wafDeactivated" class="wfConfigElem" name="alertOn_wafDeactivated" value="1" <?php $w->cb( 'alertOn_wafDeactivated' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert me with scan results of this severity level or greater', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			  
			  <div class="ui toggle checkbox">
				<input type="checkbox" id="alertOn_scanIssues" class="wfConfigElem" name="alertOn_scanIssues" value="1" <?php $w->cb( 'alertOn_scanIssues' ); ?>/>
			</div>

			<select id="alertOn_severityLevel" class="wfConfigElem ui dropdown" name="alertOn_severityLevel">
				<option value="100"<?php $w->sel( 'alertOn_severityLevel', 100 ); ?>>Critical</option>
				<option value="75"<?php $w->sel( 'alertOn_severityLevel', 75 ); ?>>High</option>
				<option value="50"<?php $w->sel( 'alertOn_severityLevel', 50 ); ?>>Medium</option>
				<option value="25"<?php $w->sel( 'alertOn_severityLevel', 25 ); ?>>Low</option>
			</select>
		</div>
	</div>
	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert when an IP address is blocked', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_block" class="wfConfigElem" name="alertOn_block" value="1" <?php $w->cb( 'alertOn_block' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert when someone is locked out from login', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_loginLockout" class="wfConfigElem" name="alertOn_loginLockout" value="1" <?php $w->cb( 'alertOn_loginLockout' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert when someone is blocked from logging in for using a password found in a breach', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_breachLogin" class="wfConfigElem" name="alertOn_breachLogin" value="1" <?php $w->cb( 'alertOn_breachLogin' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert when the "lost password" form is used for a valid user', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_lostPasswdForm" class="wfConfigElem" name="alertOn_lostPasswdForm" value="1" <?php $w->cb( 'alertOn_lostPasswdForm' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert me when someone with administrator access signs in', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_adminLogin" class="wfConfigElem" name="alertOn_adminLogin" value="1" <?php $w->cb( 'alertOn_adminLogin' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Only alert me when that administrator signs in from a new device or location', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_firstAdminLoginOnly" class="wfConfigElem" name="alertOn_firstAdminLoginOnly" value="1" <?php $w->cb( 'alertOn_firstAdminLoginOnly' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert me when a non-admin user signs in', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_nonAdminLogin" class="wfConfigElem" name="alertOn_nonAdminLogin" value="1" <?php $w->cb( 'alertOn_nonAdminLogin' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Only alert me when that user signs in from a new device or location', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="alertOn_firstNonAdminLoginOnly" class="wfConfigElem" name="alertOn_firstNonAdminLoginOnly" value="1" <?php $w->cb( 'alertOn_firstNonAdminLoginOnly' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Alert me when there\'s a large increase in attacks detected on my site', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="wafAlertOnAttacks" class="wfConfigElem" name="wafAlertOnAttacks" value="1" <?php $w->cb( 'wafAlertOnAttacks' ); ?>/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Maximum email alerts to send per hour', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
			<input type="text" id="alert_maxHourly" name="alert_maxHourly" value="<?php $w->f( 'alert_maxHourly' ); ?>"/>
	  </div>
	</div>
		<?php
	}

	public static function gen_settings_email( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = self::get_instance()->load_configs( $current_site_id );

		if ( empty( $w ) ) {
			return;
		}
		?>

		<div class="ui dividing header"><?php echo __( 'Activity Report', 'mainwp-wordfence-extension' ); ?></div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Enable email summary', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="email_summary_enabled" name="email_summary_enabled" value="1" <?php $w->cb( 'email_summary_enabled' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Email summary frequency', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="email_summary_interval" class="wfConfigElem ui dropdown" name="email_summary_interval">
					<option value="daily"<?php $w->sel( 'email_summary_interval', 'daily' ); ?>>Once a day </option>
					<option value="weekly"<?php $w->sel( 'email_summary_interval', 'weekly' ); ?>>Once a week</option>
					<option value="monthly"<?php $w->sel( 'email_summary_interval', 'monthly' ); ?>>Once a month </option>
				</select>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'List of directories to exclude from recently modified file list', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<textarea id="email_summary_excluded_directories" name="email_summary_excluded_directories" class="mwp-wf-form-control" rows="4"><?php echo esc_html( MainWP_Wordfence_Utility::cleanupOneEntryPerLine( $w->get( 'email_summary_excluded_directories', '' ) ) ); ?></textarea>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Enable activity report widget on the WordPress dashboard', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="email_summary_dashboard_widget_enabled" name="email_summary_dashboard_widget_enabled" value="1" <?php $w->cb( 'email_summary_dashboard_widget_enabled' ); ?> />
		  </div>
		</div>
		<?php
	}

	public static function gen_dashboard_notification_options( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = self::get_instance()->load_configs( $current_site_id );

		if ( empty( $w ) ) {
			return;
		}

		if ( $current_site_id ) {
			$is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid', 0 );
		} else {
			$is_Paid = $is_premium = get_option( 'mainwp_wordfence_use_premium_general_settings' );
		}
		?>
		<div class="ui dividing header"><?php echo __( 'Wordfence Dashboard Notification Options', 'mainwp-wordfence-extension' ); ?></div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Updates needed (Plugin, Theme, or Core)', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="notification_updatesNeeded" name="notification_updatesNeeded" value="1" <?php $w->cb( 'notification_updatesNeeded' ); ?>>
		  </div>
		</div>

		<?php if ( $is_Paid ) : ?>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Security alerts', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="notification_securityAlerts"
				<?php
				if ( $is_Paid ) {
					echo ' name="notification_securityAlerts"'; }
				?>
				 value="1" 
			<?php
			if ( $is_Paid ) {
					$w->cb( 'notification_securityAlerts' );
			} else {
				echo ' checked disabled';
			}
			?>
			>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Promotions', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="notification_promotions"
				<?php
				if ( $is_Paid ) {
					echo ' name="notification_promotions"'; }
				?>
				 value="1" 
			<?php
			if ( $is_Paid ) {
					$w->cb( 'notification_promotions' );
			} else {
				echo ' checked disabled'; }
			?>
>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Blog highlights', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="notification_blogHighlights"
				<?php
				if ( $is_Paid ) {
					echo ' name="notification_blogHighlights"'; }
				?>
				 value="1" 
			<?php
			if ( $is_Paid ) {
					$w->cb( 'notification_blogHighlights' );
			} else {
				echo ' checked disabled'; }
			?>
>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Product updates', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
			<input type="checkbox" id="notification_productUpdates" name="notification_productUpdates" value="1" 
			<?php
				$w->cb( 'notification_productUpdates' );
			?>
			 >
		  </div>
		</div>

		<?php endif; ?>
		
		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Scan status', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="notification_scanStatus" name="notification_scanStatus" value="1" 
				<?php
					$w->cb( 'notification_scanStatus' );
				?>
				 >
			</div>
		</div>
		<?php
	}

	public static function gen_comment_spam_filter_settings( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = self::get_instance()->load_configs( $current_site_id );
		if ( empty( $w ) ) {
			return;
		}

		if ( $current_site_id ) {
			$is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid', 0 );
		} else {
			$is_Paid = $is_premium = get_option( 'mainwp_wordfence_use_premium_general_settings' );
		}
		?>

		<div class="ui dividing header"><?php echo __( 'Comment Spam Filter Options', 'mainwp-wordfence-extension' ); ?></div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Hold anonymous comments using member emails for moderation', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="other_noAnonMemberComments" class="wfConfigElem" name="other_noAnonMemberComments" value="1" <?php $w->cb( 'other_noAnonMemberComments' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Filter comments for malware and phishing URL\'s', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="other_scanComments" class="wfConfigElem" name="other_scanComments" value="1" <?php $w->cb( 'other_scanComments' ); ?> />
		  </div>
		</div>

		<?php if ( $is_Paid ) : ?>
		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Advanced comment spam filter', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="advancedCommentScanning" class="wfConfigElem" name="advancedCommentScanning" value="1" 
				<?php
				$w->cbp( 'advancedCommentScanning', $is_Paid ); if ( ! $is_Paid ) {
					?>
					onclick="jQuery('#advancedCommentScanning').attr('checked', false); return false;" <?php } ?> />
		  </div>
		</div>
		<?php endif; ?>
		<?php
	}

	public static function gen_settings_import_settings( $individual = false ) {
		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		?>

		<?php if ( $current_site_id ) : ?>
			<div class="ui grid field">
			  <label class="six wide column middle aligned"><?php _e( 'Export this site\'s Wordfence settings for import on another site', 'mainwp-wordfence-extension' ); ?></label>
			  <div class="ten wide column">
					<input type="button" class="ui button" id="exportSettingsBut" value="Export Wordfence Settings" onclick="MWP_WFAD.exportSettings(this, <?php echo $current_site_id; ?>); return false;"/>
			  </div>
			</div>
		<?php endif; ?>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Import Wordfence settings from another site using a token', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="four wide column">
				<input type="text" size="20" value="" id="importToken"/>

		  </div>
			<div class="six wide column">
				<?php if ( $current_site_id ) { ?>
					<input type="button" class="ui button" name="importSettingsButton" value="Import Settings" onclick="MWP_WFAD.importSettings(this, jQuery('#importToken').val(), <?php echo $current_site_id; ?>); return false;"/>
				<?php } else { ?>
					<input type="button" class="ui button" name="importSettingsButton" value="Bulk Import Settings" onclick="MWP_WFAD.onBulkImportSettings(jQuery('#importToken').val()); return false;"/>
				<?php } ?>
		  </div>
		</div>
		<?php
	}

	public static function gen_diagnostics_tab( $pIndividual = false ) {

		?>
			<style type="text/css">
						table.wf-table {
								width: 100%;
								max-width: 100%;
								border-collapse: collapse;
						}
						table.wf-table th,
						table.wf-table td {
								padding: 6px 4px;
								border: 1px solid #ccc;
						}
						table.wf-table thead th,
						table.wf-table thead td,
						table.wf-table tfoot th,
						table.wf-table tfoot td,
						table.wf-table tbody.thead th,
						table.wf-table tbody.thead td {
								background-color: #222;
								color: #fff;
								font-weight: bold;
								border-color: #474747;
								text-align: left;
						}
						table.wf-table tbody tr.even td,
						table.wf-table tbody tr:nth-child(2n) td {
								background-color: #eee;
						}
						table.wf-table tbody tr td,
						table.wf-table tbody tr.odd td {
								background-color: #fff;
						}
						table.wf-table tbody tr:hover > td {
								background-color: #fffbd8;
						}
						table.wf-table tbody.empty-row tr td {
								border-width: 0;
								padding: 8px 0;
								background-color: transparent;
						}


						.wf-table td.error {
								color: #d0514c;
								font-weight: bold;
						}
						.wf-table td.success:before,
						.wf-table td.error:before {
								font-size: 16px;
								display: inline-block;
								margin: 0px 8px 0px 0px;
						}
						.wf-table td.error:before {
								content: "\2718";
						}
						.wf-table td.success {
								color: #008c10;
								font-weight: bold;
								max-width: 20%;
						}
						.wf-table td.success:before {
								content: "\2713";
						}
						.wf-table td.inactive {
								font-weight: bold;
								color: #666666;
						}

			</style>
		<?php

		if ( $pIndividual ) {
						self::gen_diagnostics_result();
						self::gen_diagnostics_other_tests();
						self::gen_diagnostics_debugging_options();
		} else {
						self::gen_diagnostics_result();
						self::gen_diagnostics_debugging_options();
		}

		// individual page have own Saving button
		if ( ! $pIndividual ) {
			self::gen_save_general_button();
		}

	}

	public static function gen_diagnostics_result() {
		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		?>
		<div class="ui dividing header"><?php echo __( 'Diagnostics', 'mainwp-wordfence-extension' ); ?></div>
		<?php
		if ( ! $current_site_id ) {
			$data     = MainWP_Wordfence::get_bulk_wfc_sites();
			$websites = $data['result'];
			$last     = $data['last'];

			if ( ! is_array( $websites ) || count( $websites ) == 0 ) {
				?>
				<div class="ui yellow message">
				<?php
				echo __( 'No websites were found with the Wordfence plugin installed', 'mainwp-wordfence-extension' );
				?>
				</div>
				<?php
			} else {
				?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Select child site', 'mainwp-wordfence-extension' ); ?></label>
				  <div class="ui six wide column mainwp_wfc_select_diagnostic_wrapper" load-paged="1" last-paged="<?php echo $last ? 1 : 0; ?>">
						<select name="" id="mainwp_wfc_diagnostic_info" class="ui dropdown not-auto-init">
							<option value="-1"><?php _e( 'Select Child Site', 'mainwp' ); ?></option>
						  <?php
							foreach ( $websites as $_id => $_site ) {
								echo '<option value="' . $_id . '">' . stripslashes( $_site ) . '</option>';
							}
							?>
						</select>
						<div class="load-more-status" style="display:inline"></div>
				</div>
			</div>

			<script type="application/javascript">
				jQuery( document ).ready( function () {
					var lastpaged = jQuery( '.mainwp_wfc_select_diagnostic_wrapper' ).attr('last-paged') == '1' ? true : false;
					if ( ! lastpaged ){
						mainwp_wfc_diagnostic_load_more_sites();
					} else {
						jQuery( '.mainwp_wfc_select_diagnostic_wrapper .ui.dropdown' ).dropdown();
					}
				} );
			</script>

				<?php	} ?>
		  <?php } else { ?>
				<script>
				  jQuery(document).ready(function ($) {
					  MWP_WFAD.getDiagnostics(<?php echo $current_site_id; ?>);
				  });
				</script>
		<?php } ?>
	  <div id="mainwp_wfc_diagnostics_child_loading" style="display: none">
				<div class="ui active inverted dimmer">
				<div class="ui text loader">Loading...</div>
			  </div>
			</div>
			<div class="ui hidden divider"></div>
	  <div id="mainwp_diagnostics_child_resp"></div>
			<div class="ui hidden divider"></div>
		<?php
	}

	public static function gen_diagnostics_other_tests() {
		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		if ( empty( $current_site_id ) ) {
			return;
		}
		$open_url  = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site';
		$conf_href = $open_url . '&websiteid=' . $current_site_id . '&open_location=' . base64_encode( '?_wfsf=sysinfo&nonce=child_temp_nonce' );
		$test_href = $open_url . '&websiteid=' . $current_site_id . '&open_location=' . base64_encode( '?_wfsf=testmem&nonce=child_temp_nonce' );
		?>
		<div class="ui dividing header"><?php echo __( 'Other Tests', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'System configuration', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ui six wide column">
				<a id="mwp_wfc_system_conf_lnk" class="ui green button" href="<?php echo $open_url . $conf_href; ?>" target="_blank"><?php _e( 'Click to See System Configuration', 'mainwp-wordfence-extension' ); ?></a>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Host available memory', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ui six wide column">
				<a id="mwp_wfc_test_mem_lnk" class="ui green button" href="<?php echo $open_url . $test_href; ?>" target="_blank"><?php _e( 'Click to See host available memory', 'mainwp-wordfence-extension' ); ?></a>
			</div>
		</div>
		<?php
	}

	public static function gen_diagnostics_debugging_options() {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$w               = self::get_instance()->load_configs( $current_site_id );
		if ( empty( $w ) ) {
			return;
		}

		?>
		<div class="ui dividing header"><?php echo __( 'Debugging Options', 'mainwp-wordfence-extension' ); ?></div>
	<form method="post" id="wfDebuggingConfigForm" action="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=diagnostics">
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Enable debugging mode (increases database load)', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ui six wide column ui toggle checkbox">
					<input type="checkbox" id="debugOn" class="wfConfigElem" name="debugOn" value="1" <?php $w->cb( 'debugOn' ); ?> />
				</div>
			</div>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Start all scans remotely (Try this if your scans aren\'t starting and your site is publicly accessible)', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ui six wide column ui toggle checkbox">
					<input type="checkbox" id="startScansRemotely" class="wfConfigElem" name="startScansRemotely" value="1" <?php $w->cb( 'startScansRemotely' ); ?> />
				</div>
			</div>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Enable SSL Verification (Disable this if you are consistently unable to connect to the Wordfence servers.)', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ui six wide column ui toggle checkbox">
					<input type="checkbox" id="ssl_verify" class="wfConfigElem" name="ssl_verify" value="1" <?php $w->cb( 'ssl_verify' ); ?> />
				</div>
			</div>

			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Disable reading of php://input', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ui six wide column ui toggle checkbox">
					<input type="checkbox" id="avoid_php_input" class="wfConfigElem" name="avoid_php_input" value="1" <?php $w->cb( 'avoid_php_input' ); ?> />
				</div>
			</div>

			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Enable beta threat defense feed', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ui six wide column ui toggle checkbox">
					<input type="checkbox" id="betaThreatDefenseFeed" class="wfConfigElem" name="betaThreatDefenseFeed" value="1" <?php $w->cb( 'betaThreatDefenseFeed' ); ?> />
				</div>
			</div>

			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Enable Wordfence translations', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ui six wide column ui toggle checkbox">
					<input type="checkbox" id="wordfenceI18n" class="wfConfigElem" name="wordfenceI18n" value="1" <?php $w->cb( 'wordfenceI18n' ); ?> />
				</div>
			</div>
	</form>
		<?php
	}
}
