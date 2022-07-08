<?php
class MainWP_IThemes_Security {

	public static $instance = null;

	private $translations = array();

	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self(); }
		return self::$instance;
	}

	public function __construct() {
		$this->set_translation_strings();
	}

	public function init() {

	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_itheme_load_sites', array( $this, 'do_load_sites' ) );
		add_action( 'wp_ajax_mainwp_itheme_whitelist', array( $this, 'ajax_whitelist' ) );
		add_action( 'wp_ajax_mainwp_itheme_backups_database', array( $this, 'ajax_backups_database' ) );
		add_action( 'wp_ajax_mainwp_itheme_save_all_settings', array( $this, 'ajax_save_all_settings' ) );
		add_action( 'wp_ajax_mainwp_itheme_reload_exclude_tables', array( $this, 'ajax_reload_exclude_tables' ) );
		add_action( 'wp_ajax_mainwp_itheme_change_content_dir_name', array( $this, 'ajax_change_content_dir_name' ) );
		add_action( 'wp_ajax_mainwp_itheme_site_override_settings', array( $this, 'ajax_override_settings' ) );
		add_action( 'wp_ajax_mainwp_itheme_malware_scan', array( $this, 'ajax_malware_scan' ) );
		add_action( 'wp_ajax_mainwp_itheme_clear_all_logs', array( $this, 'ajax_clear_all_logs' ) );
		add_action( 'wp_ajax_mainwp_itheme_release_lockouts', array( $this, 'ajax_itheme_release_lockouts' ) );
		add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( $this, 'synced_site' ), 10, 2 );
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site_data' ), 10, 1 );
	}

	private function set_translation_strings() {
		$this->translations = array(
			'individual_settings_in_use' => __( 'Not Updated - Individual site settings are in use.', 'l10n-mainwp-ithemes-security-extension' ),
			'need_to_override_settings'  => __( 'Update Failed: Override General Settings need to be set to Yes.', 'l10n-mainwp-ithemes-security-extension' ),
		);
	}

	public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array(); }
		$data['ithemeExtActivated'] = 'yes';
		return $data;
	}

	public function synced_site( $pWebsite, $information = array() ) {
		if ( is_array( $information ) && isset( $information['syncIThemeData'] ) ) {
			$data = $information['syncIThemeData'];

			if ( is_array( $data ) ) {
				$websiteId = $pWebsite->id;

				$update_status = array();
				if ( isset( $data['users_and_roles'] ) ) {
					$update_status['users_and_roles'] = $data['users_and_roles'];
				}
				if ( isset( $data['lockout_count'] ) ) {
					$update_status['lockout_count'] = $data['lockout_count'];
				}

				if ( isset( $data['scan_info'] ) ) {
					$update_status['scan_info'] = $data['scan_info'];
				}

				if ( isset( $data['count_bans'] ) ) {
					$update_status['count_bans'] = $data['count_bans'];
				}

				if ( isset( $data['lockouts_host'] ) ) {
					$update_status['lockouts_host'] = $data['lockouts_host'];
				}

				if ( isset( $data['lockouts_user'] ) ) {
					$update_status['lockouts_user'] = $data['lockouts_user'];
				}

				if ( isset( $data['lockouts_username'] ) ) {
					$update_status['lockouts_username'] = $data['lockouts_username'];
				}

				if ( ! empty( $update_status ) ) {
					MainWP_IThemes_Security_DB::get_instance()->update_site_status_fields_by( 'site_id', $websiteId, $update_status );
				}
			}
			unset( $information['syncIThemeData'] );
		}
	}


	public function delete_site_data( $website ) {
		if ( $website ) {
			MainWP_IThemes_Security_DB::get_instance()->delete_setting( 'site_id', $website->id );
		}
	}

	public static function render() {

		$website = null;

		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			global $mainWPIThemesSecurityExtensionActivator;
			$option     = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), array( $_GET['id'] ), array(), $option );

			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}

		if ( self::is_manage_site() ) {
			$error = '';
			if ( empty( $website ) ) {
				$error = __( 'Invalid site ID.', 'l10n-mainwp-ithemes-security-extension' );
			} else {
				$activated = false;
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'better-wp-security/better-wp-security.php' == $plugin['slug'] ) || ( 'ithemes-security-pro/ithemes-security-pro.php' == $plugin['slug'] ) ) {
								if ( $plugin['active'] ) {
									$activated = true; }
								break;
							}
						}
					}
				}
				if ( ! $activated ) {
					$error = __( 'iThemes Security plugin is not installed or activated on the site.', 'l10n-mainwp-ithemes-security-extension' );
				}
			}

			if ( ! empty( $error ) ) {
				do_action( 'mainwp_pageheader_sites', 'iThemes' );
				echo '<div class="ui red message">' . $error . '</div>';
				do_action( 'mainwp_pagefooter_sites', 'iThemes' );
				return;
			}
		}

		self::render_tabs( $website );
	}

	public static function render_tabs( $website = null ) {

		global $mainWPIThemesSecurityExtensionActivator, $mainwp_itsec_globals;

		$current_tab = 'dashboard';

		if ( self::is_manage_site() ) {
			$current_tab = 'global';
		}

		if ( ! self::is_manage_site() ) {

			$sites_ids       = array();
			$selected_groups = array();
			if ( isset( $_GET['group'] ) ) {
				$selected_groups = explode( '-', $_GET['group'] );
			}

			if ( empty( $selected_groups ) ) {
				// get sites with the itheme plugin installed only
				$others    = array(
					'plugins_slug' => 'better-wp-security/better-wp-security.php,ithemes-security-pro/ithemes-security-pro.php',
				);
				$websites  = apply_filters( 'mainwp_getsites', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), null, false, $others ); // to fix overload all sites data.
				$sites_ids = array();
				if ( is_array( $websites ) ) {
					foreach ( $websites as $site ) {
						$sites_ids[] = $site['id'];
					}
					unset( $websites );
				}
			}

			$option = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);

			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $sites_ids, $selected_groups, $option );

			$dbwebsites_itheme = MainWP_IThemes_Security_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites );
			unset( $dbwebsites );
		}

		if ( self::is_manage_site() ) {
			do_action( 'mainwp_pageheader_sites', 'iThemes' );
		}

		$module_items = array(
			'global'              => __( 'Global Settings', 'l10n-mainwp-ithemes-security-extension' ),
			// 'away-mode'             => __( 'Away Mode', 'l10n-mainwp-ithemes-security-extension' ),
			'file-change'         => __( 'File Change', 'l10n-mainwp-ithemes-security-extension' ),
			// '404-detection'         => __( '404 Detection', 'l10n-mainwp-ithemes-security-extension' ),
			'ssl'                 => __( 'SSL', 'l10n-mainwp-ithemes-security-extension' ),
			// 'password-requirements' => __( 'Password Requirements', 'l10n-mainwp-ithemes-security-extension' ),
			// 'multisite-tweaks'      => __( 'Multisite Tweaks', 'l10n-mainwp-ithemes-security-extension' ),
			'notification-center' => __( 'Notification Center', 'l10n-mainwp-ithemes-security-extension' ),
			'admin-user'          => __( 'Admin User', 'l10n-mainwp-ithemes-security-extension' ),
			// 'content-directory'     => __( 'Change Content Directory', 'l10n-mainwp-ithemes-security-extension' ),
			'database-prefix'     => __( 'Change Database Table Prefix', 'l10n-mainwp-ithemes-security-extension' ), // not saved values
			'ban-users'           => __( 'Ban Users', 'l10n-mainwp-ithemes-security-extension' ),
			'backup'              => __( 'Database Backups', 'l10n-mainwp-ithemes-security-extension' ),
			'brute-force'         => __( 'Local Brute Force', 'l10n-mainwp-ithemes-security-extension' ),
			'network-brute-force' => __( 'Network Brute Force', 'l10n-mainwp-ithemes-security-extension' ),
			// 'strong-passwords'      => __( 'Strong Passwords', 'l10n-mainwp-ithemes-security-extension' ),
			'two-factor'          => __( 'Two-Factor', 'l10n-mainwp-ithemes-security-extension' ),
			// 'user-logging'          => __( 'User Logging', 'l10n-mainwp-ithemes-security-extension' ),
			'system-tweaks'       => __( 'System Tweaks', 'l10n-mainwp-ithemes-security-extension' ),
			'wordpress-tweaks'    => __( 'WordPress Tweaks', 'l10n-mainwp-ithemes-security-extension' ),
			'hide-backend'        => __( 'Hide Backend', 'l10n-mainwp-ithemes-security-extension' ),
			'security-check-pro'  => __( 'Security Check Pro', 'l10n-mainwp-ithemes-security-extension' ),
		);
		?>
	<span id="mainwp_itheme_managesites_site_id" site-id="<?php echo ! empty( $website ) ? $website->id : ''; ?>"></span>
		<div class="ui labeled icon inverted mini menu mainwp-sub-submenu" id="mainwp-ithemes-security-menu">
			<?php if ( ! self::is_manage_site() ) : ?>
				<a class="item active" href="admin.php?page=Extensions-Mainwp-Ithemes-Security-Extension&tab=dashboard" id="mainwp-ithemes-item-dashboard-tab"><i class="tasks icon"></i> <?php _e( 'iThemes Security Dashboard', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
			<?php endif; ?>
			<?php
			foreach ( $module_items as $mod => $title ) {
				?>
				<a href="#<?php echo $mod; ?>" class="item <?php echo ( ( 'global' == $current_tab && 'global' == $mod ) ? 'active' : '' ); ?> " data-tab="<?php echo $mod; ?>"><i class="cog icon"></i> <?php echo $title; ?></a>
				<?php
			}
			?>
		</div>

		<div id="mainwp-ithemes-security-tabs">
			<div class="ui alt segment">
				<?php if ( ! self::is_manage_site() ) : ?>
					<div id="mainwp-ithemes-security-dashboard-tab" data-tab="dashboard-tab" class="ui segment alt tab <?php echo ( 'dashboard' == $current_tab ? 'active' : '' ); ?>">
						<?php MainWP_IThemes_Security_Plugin::gen_actions_row(); ?>
							<div class="ui red message" id="mwpithemeerror_box" style="display:none"></div>
							<?php MainWP_IThemes_Security_Plugin::gen_plugin_dashboard_tab( $dbwebsites_itheme ); ?>
					</div>
				<?php endif; ?>
				<div>
					<div class="ui red message" id="mwpithemeerror_box" style="display:none"></div>
					<?php
					$self_url = MainWP_ITSEC_Core::get_settings_page_url();
					if ( self::is_manage_site() ) {
						self::site_settings_box();
					}
					?>

					<div id="mainwp-ithemes-security-settings-tabs"
					<?php
					if ( ! self::is_manage_site() ) {
						?>
						   style="display:none"
					<?php } ?>
					>
					<?php
					// Settings tabs.
					MainWP_ITSEC_Settings_Page::get_instance()->show_settings_page( $self_url );
					?>
					</div>
				</div>

				<div class="ui hidden clearing divider"></div>
			</div>
		</div>
		<?php

		if ( self::is_manage_site() ) {
			do_action( 'mainwp_pagefooter_sites', 'iThemes' );
		}

	}

	public static function site_settings_box() {
		global $mainwp_itsec_globals;
		$site_id  = isset( $_GET['id'] ) ? $_GET['id'] : 0;
		$override = 0;
		if ( $site_id ) {
			$site_itheme = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $site_id );
			if ( $site_itheme ) {
				$override = $site_itheme->override;
			}
		}

		?>
		<div class="ui form">
			<div class="ui hidden divider"></div>
			<div class="ui dividing header">
				<?php _e( 'iThemes Security Site Settings', 'l10n-mainwp-ithemes-security-extension' ); ?>
				<input type="hidden" name="mainwp_itheme_settings_site_id" value="<?php echo $site_id; ?>">
			</div>
			<div id="mwp_itheme_site_save_settings_status" class="ui message" style="display:none"></div>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Override General Settings', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			  <div class="six wide column ui toggle checkbox">
					<input type="checkbox" id="mainwp_itheme_override_general_settings" name="mainwp_itheme_override_general_settings"  <?php echo ( 0 == $override ? '' : 'checked="checked"' ); ?> value="1"/>
					<label for="mainwp_itheme_override_general_settings"></label>
				</div>
			</div>
		</div>
		<?php
	}

	public static function is_itheme_page( $tabs = array() ) {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Ithemes-Security-Extension' == $_GET['page'] || 'ManageSitesiThemes' == $_GET['page'] ) ) {
			if ( 'ManageSitesiThemes' == $_GET['page'] ) {
				if ( ! isset( $_GET['tab'] ) || empty( $_GET['tab'] ) ) {
					$_GET['tab'] = 'settings';
				}
			}
			if ( empty( $tabs ) ) {
				return true;
			} elseif ( is_array( $tabs ) && isset( $_GET['tab'] ) && in_array( $_GET['tab'], $tabs ) ) {
				return true;
			} elseif ( isset( $_GET['tab'] ) && $_GET['tab'] == $tabs ) {
				return true;
			}
		}
		return false;
	}

	public static function is_manage_site() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX !== false && isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ) {
			return true;
		} elseif ( isset( $_GET['page'] ) && ( 'ManageSitesiThemes' == $_GET['page'] ) ) {
			return true;
		}
		return false;
	}

	public static function get_manage_site_id() {
		$site_id = false;
		if ( self::is_manage_site() ) {
			if ( isset( $_POST['ithemeSiteID'] ) && ! empty( $_POST['ithemeSiteID'] ) ) {
				$site_id = $_POST['ithemeSiteID'];
			} elseif ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
				$site_id = $_GET['id'];
			} elseif ( isset( $_POST['mainwp_itheme_site_id'] ) && ! empty( $_POST['mainwp_itheme_site_id'] ) ) {
				$site_id = $_POST['mainwp_itheme_site_id'];
			}
		}
		return $site_id;
	}

	public static function get_itheme_slug() {
		if ( isset( $_GET['page'] ) && ( 'ManageSitesiThemes' == $_GET['page'] || 'Extensions-Mainwp-Ithemes-Security-Extension' == $_GET['page'] ) ) {
			return $_GET['page'];
		}
		return '';
	}

	public static function get_itheme_url() {
		$slug = '';
		if ( isset( $_GET['page'] ) ) {
			if ( 'ManageSitesiThemes' == $_GET['page'] ) {
				$slug = $_GET['page'] . ( isset( $_GET['id'] ) ? '&id=' . $_GET['id'] : '' );
			} elseif ( 'Extensions-Mainwp-Ithemes-Security-Extension' == $_GET['page'] ) {
				$slug = $_GET['page'];
			}
		}
		return $slug;
	}

	public static function get_itheme_settings( $site_id = false ) {
		// general settings
		if ( empty( $site_id ) ) {
			$itheme_settings = get_site_option( 'mainwp_itheme_generalSettings' );
		} else {
			$itheme_settings = MainWP_IThemes_Security_DB::get_instance()->get_setting_fields_by( 'site_id', $site_id );
		}
		if ( ! is_array( $itheme_settings ) ) {
			$itheme_settings = array();
		}
		return $itheme_settings;
	}

	public static function update_general_settings( $settings ) {
		$curgen_settings = self::get_itheme_settings();
		if ( ! is_array( $curgen_settings ) ) {
			$curgen_settings = array(); }

		foreach ( $settings as $key => $value ) {
			$curgen_settings[ $key ] = $value;
		}
		return update_site_option( 'mainwp_itheme_generalSettings', $curgen_settings );
	}

	public static function update_itheme_settings( $settings, $site_id = false ) {
		if ( $site_id ) {
			return MainWP_IThemes_Security_DB::get_instance()->update_setting_fields_by( 'site_id', $site_id, $settings );
		} else {
			return self::update_general_settings( $settings );
		}
		return false;
	}

	public static function update_build_number( $build, $site_id = false ) {
		if ( $site_id ) {
			return MainWP_IThemes_Security_DB::get_instance()->update_setting_fields_by( 'site_id', $site_id, array( 'build_number' => $build ) );
		} else {
			return self::update_general_settings( array( 'build_number' => $build ) );
		}
		return false;
	}

	public static function unset_setting_field( $fields, $site_id = false ) {
		$itheme_settings = self::get_itheme_settings( $site_id );
		$changed         = false;
		foreach ( $fields as $field ) {
			if ( isset( $itheme_settings[ $field ] ) ) {
				unset( $itheme_settings[ $field ] );
				$changed = true;
			}
		}
		if ( $changed ) {
			self::update_itheme_settings( $itheme_settings, $site_id );
		}

	}

	public function do_load_sites( $return = false ) {

		if ( isset( $_POST['what'] ) ) {
			$what = $_POST['what'];
		} elseif ( isset( $_POST['data']['what'] ) ) {
			$what = $_POST['data']['what'];
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$others = array(
			'plugins_slug' => 'better-wp-security/better-wp-security.php,ithemes-security-pro/ithemes-security-pro.php',
		);

		$websites = apply_filters( 'mainwp_getsites', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), null, false, $others ); // to fix overload all sites data.

		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
			unset( $websites );
		}

		$option = array(
			'plugin_upgrades' => true,
			'plugins'         => true,
		);

		$dbwebsites        = apply_filters( 'mainwp_getdbsites', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$dbwebsites_itheme = MainWP_IThemes_Security_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites, true ); // actived only.
		unset( $dbwebsites );

		ob_start();

		?>
		<div id="mainwp-ithemes-security-sync-modal" class="ui modal">
			<div class="header"><?php echo __( 'iThemes Security Synchronization', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
			<div class="scrolling content">
				<?php if ( 'reset-api-key' == $what ) : ?>
					<?php $defaults = MainWP_ITSEC_Modules::get_defaults( 'network-brute-force' ); ?>
					<?php MainWP_ITSEC_Modules::set_settings( 'network-brute-force', $defaults ); ?>
				<?php endif; ?>
				<?php if ( ! is_array( $dbwebsites_itheme ) || count( $dbwebsites_itheme ) <= 0 ) : ?>
				<div class="ui yellow message">
					<?php echo __( 'No websites found with the iThemes Security plugin installed.', 'l10n-mainwp-ithemes-security-extension' ); ?>
				</div>
				<?php else : ?>
				<div class="ui relaxed divided list">
					<?php foreach ( $dbwebsites_itheme as $website ) : ?>
						<div class="item siteItemProcess" site-id="<?php echo $website['id']; ?>" status="queue"><?php echo stripslashes( $website['name'] ); ?><span class="status right floated"><i class="outline clock icon"></i></span></div>
					<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
			</div>
		</div>
		<?php

		$html = ob_get_clean();

		if ( $return ) {
			return $html;
		} else {
			die( $html );
		}
	}

	function ajax_whitelist() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( ! $individual_update ) {
			if ( $itheme_site ) {
				$this->is_override_settings( $itheme_site->override );
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$ip        = MainWP_ITSEC_Lib::get_ip();
		$post_data = array(
			'mwp_action' => 'whitelist',
			'ip'         => $ip,
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		if ( is_array( $information ) && ! empty( $information['ip'] ) ) {
			$response = array(
				'ip'  => $information['ip'],
				'exp' => $information['exp'],
			);
			if ( $individual_update ) {
				$result = self::update_itheme_settings( array( 'itsec_temp_whitelist_ip' => $response ), $siteid );
			} else {
				self::update_itheme_settings( array( 'itsec_temp_whitelist_ip' => $response ) );
				if ( $itheme_site->override != 1 ) {
					self::update_itheme_settings( array( 'itsec_temp_whitelist_ip' => $response ), $siteid ); }
			}
		}

		die( json_encode( $information ) );
	}

	function ajax_backups_database() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mainwp-itsec-settings-nonce' ) ) {
			die( 'invalid request' );
		}
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;

		if ( $individual_update ) {
			if ( $itheme_site ) {
				if ( $itheme_site->override ) {
					$settings = unserialize( base64_decode( $itheme_site->settings ) );
				} else {
					die( json_encode( array( 'error' => $this->translations['need_to_override_settings'] ) ) );
				}
			}
		} else {
			if ( $itheme_site ) {
				if ( 1 == $itheme_site->override ) {
					die( json_encode( array( 'error' => $this->translations['individual_settings_in_use'] ) ) );
				}
			}
			$settings = self::get_itheme_settings();
		}

		global $mainWPIThemesSecurityExtensionActivator;
		$post_data   = array( 'mwp_action' => 'backup_db' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );
		die( json_encode( $information ) );
	}


	function ajax_save_all_settings() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mainwp-itsec-settings-nonce' ) ) {
			die( 'Invalid request' );
		}
		$siteid = $_POST['ithemeSiteID'];

		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) );
		}

		$save_general = ( isset( $_POST['saveGeneralSettings'] ) && $_POST['saveGeneralSettings'] ) ? true : false;
		$information  = $this->save_settings_to_site( $siteid, $save_general, '', true );

		$result = array();
		if ( is_array( $information ) ) {
			if ( isset( $information['result'] ) ) {
				$result = array( 'result' => 'success' );
			} elseif ( $information['error'] ) {
				$result = array( 'error' => $information['error'] );
			}
		}
		die( json_encode( $result ) );
	}

	function ajax_reload_exclude_tables() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mainwp-itsec-settings-nonce' ) ) {
			die( 'invalid request' );
		}

		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		global $mainWPIThemesSecurityExtensionActivator;
		$post_data   = array( 'mwp_action' => 'reload_backup_exclude' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		if ( is_array( $information ) && isset( $information['exclude'] ) ) {
			$exlude = $information['exclude'];
			MainWP_IThemes_Security_DB::get_instance()->update_setting_module_fields_by( 'site_id', $siteid, 'backup', array( 'exclude' => $exlude ) );
			MainWP_IThemes_Security_DB::get_instance()->update_site_status_fields_by( 'site_id', $siteid, array( 'excludable_tables' => $information['excludable_tables'] ) );
			$html = '<select id="itsec-backup-exclude" name="backup[exclude][]" multiple="multiple" style="position: absolute; left: -9999px;">';
			if ( is_array( $information['excludable_tables'] ) ) {
				if ( ! is_array( $exlude ) ) {
					$exlude = array();
				}
				foreach ( $information['excludable_tables'] as $short_name => $name ) {
					$selected = in_array( $short_name, $exlude ) ? 'selected="selected"' : '';
					$html    .= '<option value="' . $short_name . '" ' . $selected . '>' . $name . '</option>';
				}
			}
			$html               .= '</select>';
			$information['html'] = $html;
			unset( $information['exclude'] );
		}

		die( json_encode( $information ) );
	}

	function do_specical_modules_update( $module, $module_settings = array() ) {

		$siteid    = $_POST['ithemeSiteID'];
		$post_data = array();
		if ( $module == 'admin-user' ) {
			$post_data['mwp_action'] = 'admin_user';
			$post_data['settings']   = $module_settings;
		} elseif ( $module == 'wordpress-salts' ) {
			$post_data['mwp_action'] = 'wordpress_salts';
		} elseif ( $module == 'file-permissions' ) {
			$post_data['mwp_action'] = 'file_permissions';
		}

		global $mainWPIThemesSecurityExtensionActivator;
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		return $information;
	}

	public function update_module_status( $module ) {

		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			return array( 'error' => 'Empty site id.' );
		}

		$data              = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;

		if ( $individual_update ) {
			if ( $data ) {
				if ( ! $data->override ) {
					return array( 'error' => $this->translations['need_to_override_settings'] );
				}
			}
			$settings = unserialize( base64_decode( $data->settings ) );
		} else {
			if ( $data ) {
				if ( $data->override ) {
					return array( 'error' => $this->translations['individual_settings_in_use'] );
				}
			}
			$settings = self::get_itheme_settings();
		}

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$field          = 'itsec_active_modules';
		$active_modules = isset( $settings[ $field ] ) ? $settings[ $field ] : array();

		global $mainWPIThemesSecurityExtensionActivator;
		$post_data   = array(
			'mwp_action'     => 'module_status',
			'active_modules' => $active_modules,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		return $information;
	}

	function ajax_change_content_dir_name() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( ! $individual_update ) {
			if ( $itheme_site ) {
				$this->is_override_settings( $itheme_site->override );
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;
		$post_data = array( 'mwp_action' => 'content_dir' );

		if ( isset( $_POST['content_dir_name'] ) && ! empty( $_POST['content_dir_name'] ) ) {
			$post_data['name'] = sanitize_file_name( $_POST['content_dir_name'] ); }

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );
		die( json_encode( $information ) );
	}

	function do_change_database_prefix() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			return array( 'error' => 'Empty site id.' );
		}

		$data              = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;

		if ( $individual_update ) {
			if ( $data ) {
				if ( ! $data->override ) {
					return array( 'error' => $this->translations['need_to_override_settings'] );
				}
			}
		} else {
			if ( $data ) {
				if ( $data->override ) {
					return array( 'error' => $this->translations['individual_settings_in_use'] );
				}
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;
		$post_data = array(
			'mwp_action'    => 'database_prefix',
			'change_prefix' => 'yes',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		return $information;
	}

	function do_save_settings( $module = '' ) {
		$siteid = $_POST['ithemeSiteID'];

		if ( empty( $siteid ) ) {
			return ( array( 'error' => 'Empty site id.' ) );
		}

		return $this->save_settings_to_site( $siteid, false, $module );
	}

	function mainwp_apply_plugin_settings( $siteid ) {
		$information = $this->save_settings_to_site( $siteid, true );
		$result      = array();
		if ( is_array( $information ) ) {
			if ( isset( $information['result'] ) && ( 'success' == $information['result'] || 'noupdate' == $information['result'] ) ) {
				$result = array( 'result' => 'success' );
			} elseif ( $information['error'] ) {
				$result = array( 'error' => $information['error'] );
			} elseif ( $information['message'] ) {
				$result = array( 'message' => $information['message'] );
			} else {
				$result = array( 'result' => 'failed' );
			}
		} else {
			$result = array( 'result' => 'failed' );
		}
		die( json_encode( $result ) );
	}


	public function save_settings_to_site( $siteid, $forced_global_setting = false, $module = '', $save_active_modules = false ) {

		global $mainwp_itsec_globals;
		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		$general           = false;

		$settings = array();
		if ( ! $forced_global_setting && $individual_update ) {
			if ( $itheme_site ) {
				if ( $itheme_site->override ) {
					$settings = unserialize( base64_decode( $itheme_site->settings ) );
				} else {
					return array( 'error' => $this->translations['need_to_override_settings'] );
				}
			}
		} else {
			if ( ! $forced_global_setting && $itheme_site ) {
				if ( 1 == $itheme_site->override ) {
					return array( 'error' => $this->translations['individual_settings_in_use'] );
				}
			}
			$settings = self::get_itheme_settings();
			$general  = true;
		}

		if ( $general ) {
			$individual_settings = array();
			if ( $itheme_site ) {
				$individual_settings = unserialize( base64_decode( $itheme_site->settings ) );
			}
			if ( isset( $settings['global']['use_individual_log_location'] ) && ! empty( $settings['global']['use_individual_log_location'] ) ) {
				if ( is_array( $individual_settings ) && isset( $individual_settings['global']['log_location'] ) && ! empty( $individual_settings['global']['log_location'] ) ) {
					$settings['global']['log_location'] = $individual_settings['global']['log_location'];
				}
			}
			if ( isset( $settings['backup']['use_individual_location'] ) && ! empty( $settings['backup']['use_individual_location'] ) ) {
				if ( is_array( $individual_settings ) && isset( $individual_settings['backup']['location'] ) && ! empty( $individual_settings['backup']['location'] ) ) {
					$settings['backup']['location'] = $individual_settings['backup']['location'];
				}
			}
			if ( isset( $settings['backup']['use_individual_exclude'] ) && ! empty( $settings['backup']['use_individual_exclude'] ) ) {
				if ( is_array( $individual_settings ) && isset( $individual_settings['backup']['exclude'] ) ) {
					$settings['backup']['location'] = $individual_settings['backup']['exclude'];
				}
			}
			if ( isset( $settings['global']['use_individual_log_location'] ) ) {
				unset( $settings['global']['use_individual_log_location'] );
			}
			if ( isset( $settings['global']['use_individual_location'] ) ) {
				unset( $settings['backup']['use_individual_location'] );
			}
			if ( isset( $settings['global']['use_individual_exclude'] ) ) {
				unset( $settings['backup']['use_individual_exclude'] );
			}
			// if (isset($settings['notification-center'])) unset($settings['notification-center']);
		}

		$update_settings = array();
		if ( ! empty( $module ) ) {
			if ( in_array( $module, array( 'admin-user', 'wordpress-salts', 'file-permissions' ) ) ) {
				$module_settings = array();
				if ( isset( $settings[ $module ] ) ) {
					$module_settings = $settings[ $module ];
				}
				return $this->do_specical_modules_update( $module, $module_settings );
			} elseif ( in_array( $module, $mainwp_itsec_globals['itheme_module_settings'] ) ) {
				$module_settings = isset( $settings[ $module ] ) ? $settings[ $module ] : array();
				$settings        = array( $module => $module_settings );
			}
		}

		$update_settings = $settings;

		if ( ! is_array( $update_settings ) || empty( $update_settings ) ) {
			return array( 'error' => $general ? 'Error: Empty General Settings.' : 'Error: Empty Individual Settings.' );
		}

		if ( ! $save_active_modules && ! $forced_global_setting ) {
			if ( isset( $update_settings['itsec_active_modules'] ) ) {
				unset( $update_settings['itsec_active_modules'] );
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$post_data = array(
			'mwp_action'    => 'save_settings', // mainwp_itheme_save_settings.
			'settings'      => base64_encode( serialize( $update_settings ) ),
			'is_individual' => $general ? 0 : 1,
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );
		if ( is_array( $information ) ) {
			if ( isset( $information['require_permalinks'] ) && ! empty( $information['require_permalinks'] ) ) {
				MainWP_IThemes_Security_DB::get_instance()->update_setting_module_fields_by( 'site_id', $siteid, 'hide-backend', array( 'enabled' => 0 ) );
			}

			if ( isset( $information['nbf_settings'] ) ) {
				MainWP_IThemes_Security_DB::get_instance()->update_setting_module_fields_by( 'site_id', $siteid, 'network-brute-force', $information['nbf_settings'] );
				if ( $individual_update ) {
					MainWP_ITSEC_Response::reload_module( 'network-brute-force' );
				}
			}

			if ( isset( $information['site_status'] ) ) {
				$siteStatus = $information['site_status'];
				if ( is_array( $siteStatus ) ) {
					MainWP_IThemes_Security_DB::get_instance()->update_site_status_fields_by( 'site_id', $siteid, $siteStatus );
				}
			}
			unset( $information['site_status'] );
		}
		return $information;
	}

	function ajax_override_settings() {
		$websiteId = $_POST['ithemeSiteID'];
		if ( empty( $websiteId ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		global $mainWPIThemesSecurityExtensionActivator;

		$website = apply_filters( 'mainwp_getsites', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $websiteId );
		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}
		if ( ! $website ) {
			return; }

		$update = array(
			'site_id'  => $website['id'],
			'override' => $_POST['override'],
		);

		MainWP_IThemes_Security_DB::get_instance()->update_setting( $update );
		die( json_encode( array( 'result' => 'success' ) ) );
	}

	public function ajax_malware_scan() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;

		if ( $individual_update ) {
			if ( $itheme_site ) {
				if ( ! $itheme_site->override ) {
					die( json_encode( array( 'error' => $this->translations['need_to_override_settings'] ) ) );
				}
			}
		} else {
			if ( $itheme_site ) {
				if ( 1 == $itheme_site->override ) {
					die( json_encode( array( 'error' => $this->translations['individual_settings_in_use'] ) ) );
				}
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;
		$post_data   = array( 'mwp_action' => 'malware_scan' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		die( json_encode( $information ) );
	}

	public function ajax_malware_get_scan_results() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		global $mainWPIThemesSecurityExtensionActivator;

		$post_data   = array( 'mwp_action' => 'malware_get_scan_results' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		die( json_encode( $information ) );
	}

	public function ajax_clear_all_logs() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( ! $individual_update ) {
			if ( $itheme_site ) {
				$this->is_override_settings( $itheme_site->override );
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$post_data   = array( 'mwp_action' => 'clear_all_logs' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		die( json_encode( $information ) );
	}

	public function do_scan_file_change( $with_html = true ) {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			return array( 'error' => 'Empty site id.' );
		}

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( $individual_update ) {
			if ( $itheme_site ) {
				if ( ! $itheme_site->override ) {
					return array( 'error' => $this->translations['need_to_override_settings'] );
				}
			}
		} else {
			if ( $itheme_site ) {
				if ( 1 == $itheme_site->override ) {
					return array( 'error' => $this->translations['individual_settings_in_use'] );
				}
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$post_data   = array( 'mwp_action' => 'file_change' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		$result = array();

		if ( is_array( $information ) ) {
			if ( isset( $information['scan_result'] ) ) {
				$result  = $information['scan_result'];
				$message = '';

				$location = 'admin.php?page=itsec-logs';
				$log_url  = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $siteid . '&location=' . base64_encode( $location ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' );

				if ( false == $result ) {
					$message = __( 'No changes were detected.', 'l10n-mainwp-ithemes-security-extension' );
					if ( $with_html ) {
						$message = '<div class="updated fade inline"><p><strong>' . $message . '</strong></p></div>';
					}
				} elseif ( true == $result ) {
					$message = sprintf( __( 'Changes were detected. Please check the <a href="%s" target="_blank">logs page</a> for details.', 'l10n-mainwp-ithemes-security-extension' ), $log_url );
					if ( $with_html ) {
						$message = '<div class="error inline"><p><strong>' . $message . '</strong></p></div>';
					}
				} elseif ( -1 == $result ) {
					$message = sprintf( __( 'A scan is already in progress. Please check the <a href="%s" target="_blank">logs page</a> at a later time for the results of the scan.', 'l10n-mainwp-ithemes-security-extension' ), $log_url );
					if ( $with_html ) {
						$message = '<div class="error inline"><p><strong>' . $message . '</strong></p></div>';
					}
				}
				$result['message'] = $message;
			} elseif ( isset( $information['scan_error'] ) ) {
				$result['message'] = esc_html( $information['scan_error'] );
			}
		}
		return $result;
	}

	public function do_security_site() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			return array( 'error' => 'Empty site id.' );
		}

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( $individual_update ) {
			if ( $itheme_site ) {
				if ( ! $itheme_site->override ) {
					return array( 'error' => $this->translations['need_to_override_settings'] );
				}
			}
		} else {
			if ( $itheme_site ) {
				if ( 1 == $itheme_site->override ) {
					return array( 'error' => $this->translations['individual_settings_in_use'] );
				}
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$post_data   = array( 'mwp_action' => 'security_site' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );
		return $information;
	}

	public function do_activate_network_brute_force() {

		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			return array( 'error' => 'Empty site id.' );
		}

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( $individual_update ) {
			if ( $itheme_site ) {
				if ( ! $itheme_site->override ) {
					return array( 'error' => $this->translations['need_to_override_settings'] );
				}
			}
		} else {
			if ( $itheme_site ) {
				if ( 1 == $itheme_site->override ) {
					return array( 'error' => $this->translations['individual_settings_in_use'] );
				}
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$post_data   = array(
			'mwp_action' => 'activate_network_brute_force',
			'data'       => base64_encode( serialize( $_POST['data'] ) ),
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		if ( is_array( $information ) && isset( $information['result'] ) && $information['result'] == 'success' ) {
			if ( isset( $information['nbf_settings'] ) ) {
				MainWP_IThemes_Security_DB::get_instance()->update_setting_module_fields_by( 'site_id', $siteid, 'network-brute-force', $information['nbf_settings'] );
				MainWP_ITSEC_Modules::activate( 'network-brute-force' );
				if ( $individual_update ) {
					MainWP_ITSEC_Response::add_js_function_call( 'setModuleToActive', 'network-brute-force' );
				}
				unset( $information['nbf_settings'] );
			}
			if ( $individual_update ) {
				MainWP_ITSEC_Response::reload_module( 'network-brute-force' );
			}
		}

		return $information;
	}


	public function do_reset_api_key() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			return array( 'error' => 'Empty site id.' );
		}

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( $individual_update ) {
			if ( $itheme_site ) {
				if ( ! $itheme_site->override ) {
					return array( 'error' => $this->translations['need_to_override_settings'] );
				}
			}
		} else {
			if ( $itheme_site ) {
				if ( 1 == $itheme_site->override ) {
					return array( 'error' => $this->translations['individual_settings_in_use'] );
				}
			}
		}
		global $mainWPIThemesSecurityExtensionActivator;
		$post_data   = array( 'mwp_action' => 'reset_api_key' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		if ( is_array( $information ) && isset( $information['result'] ) && $information['result'] == 'success' ) {
			if ( isset( $information['nbf_settings'] ) ) {
				MainWP_IThemes_Security_DB::get_instance()->update_setting_module_fields_by( 'site_id', $siteid, 'network-brute-force', $information['nbf_settings'] );
			}
			if ( $individual_update ) {
				MainWP_ITSEC_Response::reload_module( 'network-brute-force' );
			}
		}

		return $information;
	}

	public function ajax_itheme_release_lockouts() {
		$siteid = $_POST['ithemeSiteID'];
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$itheme_site       = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individualSite'] ) && ! empty( $_POST['individualSite'] ) ? true : false;
		if ( ! $individual_update ) {
			if ( $itheme_site ) {
				$this->is_override_settings( $itheme_site->override );
			}
		}

		global $mainWPIThemesSecurityExtensionActivator;

		$post_data   = array(
			'mwp_action'  => 'release_lockout',
			'lockout_ids' => $_POST['lockout_ids'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

		if ( is_array( $information ) && isset( $information['result'] ) && 'success' == $information['result'] ) {
			if ( isset( $information['site_status'] ) ) {
				$siteStatus = $information['site_status'];
				if ( is_array( $siteStatus ) ) {
					$update = array(
						'site_id'     => $siteid,
						'site_status' => base64_encode( serialize( $siteStatus ) ),
					);
					MainWP_IThemes_Security_DB::get_instance()->update_setting( $update );
				}
				unset( $information['site_status'] );
			}
		}

		die( json_encode( $information ) );
	}

	private function is_override_settings( $override, $return = false ) {
		$massage = __( 'Not Updated - Individual site settings are in use.', 'l10n-mainwp-ithemes-security-extension' );
		if ( 1 == $override ) {
			die( json_encode( array( 'message' => $massage ) ) );
		}
	}
}
