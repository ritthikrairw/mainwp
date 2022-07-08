<?php
/**
 * MainWP Virusdie Extension
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Virusdie;

/**
 * Class MainWP_Virusdie
 *
 * MainWP Virusdie Extension.
 */
class MainWP_Virusdie {

	// phpcs:disable Generic.Metrics.CyclomaticComplexity -- complexity.

	/**
	 * Public static variable to hold the single instance of MainWP_Virusdie.
	 *
	 * @var mixed Default null
	 */
	public static $instance = null;

	/**
	 * Creates a public static instance of MainWP_Virusdie.
	 *
	 * @return MainWP_Virusdie|mixed|null
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Virusdie();
		}
		return self::$instance;
	}

	/**
	 * MainWP_Virusdie constructor.
	 */
	public function __construct() {
	}

	/**
	 * Initiates the MainWP Virusdie extension.
	 */
	public function init() {
		add_action( 'wp_ajax_mainwp_virusdie_security_scan', array( $this, 'ajax_virusdie_scan' ), 10, 2 );
		add_action( 'wp_ajax_mainwp_virusdie_show_report', array( $this, 'ajax_show_report' ) );
		add_action( 'wp_ajax_mainwp_virusdie_add_sites', array( $this, 'ajax_add_sites' ) );
		add_action( 'wp_ajax_mainwp_virusdie_upload_sync_file', array( $this, 'ajax_upload_sync_file' ) );
		add_action( 'wp_ajax_mainwp_virusdie_delete_temp_file', array( $this, 'ajax_delete_temp_file' ) );
		add_action( 'wp_ajax_mainwp_virusdie_remove_site', array( $this, 'ajax_remove_site' ) );
		add_action( 'wp_ajax_mainwp_virusdie_setoption_site', array( $this, 'ajax_set_option_site' ) );
		add_filter( 'mainwp_virusdie_get_data', array( &$this, 'virusdie_get_data' ), 10, 6 );
		add_action( 'mainwp_help_sidebar_content', array( &$this, 'mainwp_help_content' ) ); // Hook the Help Sidebar content.
	}


	/**
	 * Get Virusdie data.
	 *
	 * @param array  $input        Information.
	 * @param int    $website_id   Date from.
	 * @param string $start_date   Start date.
	 * @param string $end_date     End date.
	 * @param array  $sections     Sections.
	 * @param array  $other_tokens Other tokens.
	 *
	 * @return array Return Virusdie scan data.
	 */
	public function virusdie_get_data( $input, $website_id, $start_date, $end_date, $sections, $other_tokens ) {
		return MainWP_Virusdie_Reports_Data::get_instance()->virusdie_get_data( $website_id, $start_date, $end_date, $sections, $other_tokens );
	}

	/**
	 * Handles the post saving process.
	 *
	 * @return void
	 */
	public function handle_main_post_saving() {
		if ( isset( $_POST['mainwp_virusdie_settings_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['mainwp_virusdie_settings_nonce'] ), 'virusdie_nonce' ) ) {
			$message = 0;
			if ( isset( $_POST['virusdie_signup_btn'] ) ) {
				$email    = isset( $_POST['mainwp_virusdie_sigup_email'] ) ? sanitize_text_field( wp_unslash( $_POST['mainwp_virusdie_sigup_email'] ) ) : '';
				$response = MainWP_Virusdie_API::instance()->signup( $email );
				if ( is_array( $response ) && isset( $response['result'] ) && isset( $response['result']['otp'] ) ) {
					$message = 1;
					MainWP_Virusdie_API::instance()->update_option_field( 'api_email', $email );
				} else {
					$message = 2;
				}
				wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=settings&signin&message=' . $message ) );
				exit();
			} elseif ( isset( $_POST['virusdie_signin_btn'] ) ) {
				$api_email = isset( $_POST['mainwp_virusdie_api_email'] ) ? sanitize_text_field( wp_unslash( $_POST['mainwp_virusdie_api_email'] ) ) : '';
				MainWP_Virusdie_API::instance()->update_option_field( 'api_email', $api_email );
				$onetime_passwd = sanitize_text_field( wp_unslash( $_POST['mainwp_virusdie_onetime_passwd'] ) );
				$response = MainWP_Virusdie_API::instance()->signin( $api_email, $onetime_passwd );
				if ( is_array( $response ) && isset( $response['result'] ) && ( $response['result']['privatekey'] ) && isset( $response['result']['privatekey']['key'] ) ) {
					MainWP_Virusdie_API::instance()->update_option_field( 'client_api_key', $response['result']['privatekey']['key'] );
					MainWP_Virusdie_API::instance()->update_option_field( 'client_hmac_key', $response['result']['privatekey']['hmac'] );
					$message = 3;
				} else {
					$message = 4;
					if ( isset( $response['error'] ) ) {
						update_option( 'mainwp_virusdie_api_error', $response['error'] );
					}
				}
			} elseif ( isset( $_POST['virusdie_save_btn'] ) ) {
				$api_email = isset( $_POST['mainwp_virusdie_api_email'] ) ? sanitize_text_field( wp_unslash( $_POST['mainwp_virusdie_api_email'] ) ) : '';
				if ( empty( $api_email ) ) {
					$response = MainWP_Virusdie_API::instance()->signout(); // forced signout.
					MainWP_Virusdie_API::instance()->update_option_field( 'api_email', '' );
					MainWP_Virusdie_API::instance()->update_option_field( 'client_api_key', '' );
					MainWP_Virusdie_API::instance()->update_option_field( 'client_hmac_key', '' );
					$message = 7;
				} elseif ( $this->update_virusdie_sites_data() ) {
					$response = MainWP_Virusdie_API::instance()->api_userinfo();
					if ( is_array( $response ) && isset( $response['error'] ) && empty( $response['error'] ) && isset( $response['result'] ) ) {
						if ( $response['result']['postpaid'] && $response['result']['subscription'] ) {
							MainWP_Virusdie_API::instance()->update_option_field( 'is_premium', 1 );
						} else {
							MainWP_Virusdie_API::instance()->update_option_field( 'is_premium', 0 );
						}
					}
					$message = 5;
				} else {
					$message = 6;
				}
			} elseif ( isset( $_POST['mainwp_virusdie_form_action'] ) && 'signout' == $_POST['mainwp_virusdie_form_action'] ) {
				$response = MainWP_Virusdie_API::instance()->signout();
				MainWP_Virusdie_API::instance()->update_option_field( 'api_email', '' );
				MainWP_Virusdie_API::instance()->update_option_field( 'client_api_key', '' );
				MainWP_Virusdie_API::instance()->update_option_field( 'client_hmac_key', '' );
				if ( is_array( $response ) && isset( $response['error'] ) && empty( $response['error'] ) && isset( $response['result'] ) ) {
					$message = 7;
				} else {
					$message = 8;
				}
			}

			wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=settings&message=' . $message ) );
			exit();
		}

		if ( isset( $_GET['virusdie_update_nonce'] ) && wp_verify_nonce( wp_unslash( $_GET['virusdie_update_nonce'] ), 'virusdie_nonce' ) ) {
			if ( $this->update_virusdie_sites_data() ) {
				$message = 9;
				wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=dashboard&message=' . $message ) );
				exit();
			}
		}
	}

	/**
	 * Updates the site data by getting the data from Virusdie API.
	 *
	 * @return bool True on success, False on failure.
	 */
	public function update_virusdie_sites_data() {

		$response = MainWP_Virusdie_API::instance()->sites_list();

		if ( is_array( $response ) && isset( $response['result'] ) ) {

			$virusdie_site_ids = array();

			foreach ( $response['result'] as $item ) {
				$update = array(
					'virusdie_item_id' => $item['id'],
					'domain'           => $item['domain'],
				);

				$new         = null;
				$scan_status = '';

				if ( isset( $item['scanreport'] ) && ! empty( $item['scanreport'] ) ) {
					$last_report = MainWP_Virusdie_DB::get_instance()->get_lastscan( $item['id'] );
					$last_scan   = 0;
					if ( $last_report ) {
						$last_scan = $last_report->scandate;
					}
					if ( empty( $last_scan ) || $last_scan < $item['scanreport']['date'] ) {
						$update['last_scandate'] = $item['scanreport']['date'];
						$new                     = array(
							'virusdie_item_id' => $item['id'],
							'cure'             => $item['scanreport']['cure'],
							'detectedfiles'    => $item['scanreport']['detectedfiles'],
							'incurablefiles'   => $item['scanreport']['incurablefiles'],
							'malicious'        => $item['scanreport']['malicious'],
							'checkeddirs'      => $item['scanreport']['checkeddirs'],
							'checkedfiles'     => $item['scanreport']['checkedfiles'],
							'suspicious'       => $item['scanreport']['suspicious'],
							'treatedfiles'     => $item['scanreport']['treatedfiles'],
							'deletedfiles'     => $item['scanreport']['deletedfiles'],
							'checkedbytes'     => $item['scanreport']['checkedbytes'],
							'scandate'         => $item['scanreport']['date'],
						);
						if ( isset( $item['scanhistory'] ) && ! empty( $item['scanhistory'] ) ) {
							$historydate = gmdate( 'Ymd', $item['scanreport']['date'] );
							if ( isset( $item['scanhistory'][ $historydate ] ) ) {
								$new['status'] = $item['scanhistory'][ $historydate ];
								$scan_status   = $this->get_status_message( $new['status'] );
							}
						}
						MainWP_Virusdie_DB::get_instance()->save_report( $new );
					}
				}

				$result = MainWP_Virusdie_DB::get_instance()->update_virusdie( $update );
				if ( $result && $result->site_id ) {
					$virusdie_site_ids[ $result->site_id ] = $result->virusdie_item_id;
				}

				if ( ! empty( $new ) ) {
					$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'virusdie_item_id', $new['virusdie_item_id'] );
					if ( $virusdie && ! empty( $virusdie->site_id ) ) {
						do_action( 'mainwp_virusdie_scan_result', $virusdie->site_id, $scan_status, $new, $new['scandate'] );
					}
				}
			}

			// update virusdie item id.
			$virusdies = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'all' );
			$sites_ids = array();
			if ( $virusdies ) {
				foreach ( $virusdies as $item ) {
					if ( $item->virusdie_item_id && ( ! isset( $virusdie_site_ids[ $item->site_id ] ) || 0 == $virusdie_site_ids[ $item->site_id ] ) ) {
						$update = array(
							'site_id'          => $item->site_id,
							'virusdie_item_id' => 0,
						);
						MainWP_Virusdie_DB::get_instance()->update_virusdie( $update );
					}
				}
			}

			return true;
		}
		return false;
	}

	/**
	 * Renders the extension pages.
	 */
	public function render_pages() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'dashboard';
		$this->gen_sub_menus( $current_tab );
		?>
		<input type="hidden" name="mainwp_virusdie_nonce" value="<?php echo wp_create_nonce( 'virusdie_nonce' ); ?>"/>
		<?php

		if ( empty( get_transient( 'mainwp_virusdie_transient_updated_data' ) ) ) {
			$api_key  = MainWP_Virusdie_API::instance()->get_api_key();
			$hmac_key = MainWP_Virusdie_API::instance()->get_hmac_key();
			if ( ! empty( $api_key ) && ! empty( $hmac_key ) ) {
				$this->update_virusdie_sites_data();
			}
			set_transient( 'mainwp_virusdie_transient_updated_data', 1, DAY_IN_SECONDS );
		}

		if ( 'dashboard' == $current_tab ) {
			$this->render_dashboard_pages();
		} else {
			$this->render_settings_pages();
		}
	}


	/**
	 * Generate sub menus.
	 *
	 * @param string $current_tab Current tab slug.
	 *
	 * @return void
	 */
	public function gen_sub_menus( $current_tab = '' ) {
		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-client-reports-menu">
			<a href="admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=dashboard" class="item <?php echo ( 'dashboard' == $current_tab ) ? 'active' : ''; ?>"><i class="tasks icon"></i> <?php _e( 'Virusdie Dashboard', 'mainwp-virusdie-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=settings" class="item <?php echo ( 'settings' == $current_tab ) ? 'active' : ''; ?>"><i class="file alternate outline icon"></i> <?php _e( 'Settings', 'mainwp-virusdie-extension' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Generate messages.
	 *
	 * @param string $current_tab Current tab slug.
	 *
	 * @return void
	 */
	public function gen_messages( $current_tab = 'settings' ) {
		if ( isset( $_GET['message'] ) ) :
			?>
			<?php $feedback = $this->get_post_message( $_GET['message'], $current_tab ); ?>
			<?php if ( ! empty( $feedback ) ) : ?>
				<div class="ui <?php echo esc_attr( $feedback['type'] ); ?> message"><?php echo esc_html( $feedback['text'] ); ?></div>
			<?php endif; ?>
			<?php
			$error = get_option( 'mainwp_virusdie_api_error' );
			if ( ! empty( $error ) ) {
				?>
				<div class="ui red message"><?php echo esc_html( $error ); ?></div>
				<?php
				delete_option( 'mainwp_virusdie_api_error' );
			}
		endif;
	}


	/**
	 * Renders MainWP Virusdie settings page.
	 *
	 * @return void
	 */
	public function render_dashboard_pages() {

		$selected_group = -1;
		$groups         = array();
		if ( isset( $_GET['group_id'] ) ) {
			$selected_group = intval( $_GET['group_id'] );
			$groups         = array( $selected_group );
		}

		$is_premium = MainWP_Virusdie_API::instance()->get_option( 'is_premium', 0 );
		/**
		 * Extension object
		 *
		 * @global object
		 */
		global $mainWPVirusdieExtensionActivator;

		if ( $groups ) {
			$websites = apply_filters( 'mainwp_getdbsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), array(), $groups );
		} else {
			$websites = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key() );
		}

		?>
		<div class="mainwp-actions-bar"><?php self::gen_select_sites( $selected_group ); ?></div>
		<div class="ui segment" id="mainwp-virusdie">
			<?php $this->gen_messages(); ?>
			<table class="ui compact single line table" id="mainwp-virusdie-sites" style="width: 100%">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Site', 'mainwp-virusdie-extension' ); ?></th>
						<th class="collapsing no-sort"><?php esc_html_e( '', 'mainwp-virusdie-extension' ); ?></th>
						<th><?php esc_html_e( 'URL', 'mainwp-virusdie-extension' ); ?></th>
						<th class="collapsing"><?php esc_html_e( 'Checked', 'mainwp-virusdie-extension' ); ?></th>
						<th class="collapsing"><?php esc_html_e( 'Malicious', 'mainwp-virusdie-extension' ); ?></th>
						<th class="collapsing"><?php esc_html_e( 'Suspicious', 'mainwp-virusdie-extension' ); ?></th>
						<th><?php esc_html_e( 'Scanned at', 'mainwp-virusdie-extension' ); ?></th>
						<?php if ( $is_premium ) : ?>
						<th><?php esc_html_e( 'Firewall ', 'mainwp-virusdie-extension' ); ?></th>
						<th><?php esc_html_e( 'AutoCleanup', 'mainwp-virusdie-extension' ); ?></th>
						<?php endif; ?>
						<th class="collapsing no-sort"><?php esc_html_e( '', 'mainwp-virusdie-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( $websites ) :
						foreach ( $websites as $website ) {
							$website     = (object) $website;
							$virusdie    = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'site_id', $website->id );
							$virusdie_id = $virusdie ? $virusdie->virusdie_item_id : 0;

							$enabled_autocleanup = false;
							$enabled_firewall    = false;

							if ( $virusdie ) {
								$enabled_autocleanup = ( 1 == $virusdie->firewall ) ? true : false;
								$enabled_firewall    = ( 'enable' == $virusdie->autocleanup ) ? true : false;
							}

							?>
							<tr class="mainwp-site mainwp-site-<?php esc_attr_e( $website->id ); ?>" site-id="<?php echo $website->id; ?>" virusdie-item-id="<?php echo intval( $virusdie_id ); ?>">
								<td><a href="<?php echo 'admin.php?page=managesites&dashboard=' . $website->id; ?>" data-tooltip="<?php esc_attr_e( 'Open the child site overview', 'mainwp' ); ?>" data-inverted=""><?php echo stripslashes( $website->name ); ?></a></td>
								<td>
								<?php if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'dashboard', 'access_wpadmin_on_child_sites' ) ) : ?>
										<i class="sign in icon"></i>
								<?php else : ?>
										<a href="<?php echo 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website->id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" data-tooltip="<?php esc_attr_e( 'Jump to the child site WP Admin', 'mainwp' ); ?>" data-inverted="" class="open_newwindow_wpadmin" target="_blank"><i class="sign in icon"></i></a>
								<?php endif; ?>
								</td>
								<td><a href="<?php echo $website->url; ?>" target="_blank"><?php echo $website->url; ?></td>
								<?php
								$last_report = false;
								if ( $virusdie_id ) {
									$last_report = MainWP_Virusdie_DB::get_instance()->get_lastscan( $virusdie_id );
								}

								$checkedfiles = 0;
								$malicious    = 0;
								$suspicious   = 0;
								$scandate     = 0;

								if ( ! empty( $last_report ) ) {
									$checkedfiles = $last_report->checkedfiles;
									$malicious    = $last_report->malicious;
									$suspicious   = $last_report->suspicious;
									$scandate     = $last_report->scandate;
								}
								?>
								<?php if ( $virusdie_id ) : ?>
								<td><?php echo intval( $checkedfiles ); ?></td>
								<td class="center aligned"><?php echo $malicious ? '<span class="ui red fluid basic label">' . intval( $malicious ) . '</span>' : '<span class="ui virusdie fluid basic label">0</span>'; ?></td>
								<td class="center aligned"><?php echo $suspicious ? '<span class="ui red fluid basic label">' . intval( $suspicious ) . '</span>' : '<span class="ui virusdie fluid basic label">0</span>'; ?></td>
								<?php else : ?>
									<td></td>
									<td></td>
									<td></td>
									<?php if ( $is_premium ) : ?>
										<td></td>
										<td></td>
									<?php endif; ?>
								<?php endif; ?>
								<td>
								<?php
								if ( ! empty( $scandate ) ) {
									echo MainWP_Virusdie::format_timestamp( MainWP_Virusdie::get_timestamp( $scandate ) );
								} else {
									echo __( 'Not scanned yet.', 'mainwp-virusdie-extension' );
								}
								?>
								</td>
								<?php if ( $virusdie_id && $is_premium ) : ?>
									<td><?php echo $enabled_firewall ? __( 'Enabled', 'mainwp-virusdie-extension' ) : __( 'Disabled', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo $enabled_autocleanup ? __( 'Enabled', 'mainwp-virusdie-extension' ) : __( 'Disabled', 'mainwp-virusdie-extension' ); ?></td>
								<?php endif; ?>
								<td>
									<div class="ui left pointing dropdown icon mini basic virusdie button" data-tooltip="<?php esc_attr_e( 'See more options', 'mainwp-virusdie-extension' ); ?>" data-position="left center" data-inverted="">
										<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
										<div class="menu">
										<?php if ( $virusdie_id ) : ?>
											<?php if ( $is_premium && ( 0 < $malicious || 0 < $suspicious ) ) : ?>
											<a href="#" class="item mainwp-virusdie-clean-up"><?php esc_html_e( 'CLEAN UP', 'mainwp-virusdie-extension' ); ?></a>
											<?php endif; ?>
											<a href="#" class="item mainwp-virusdie-row-scan-site"><?php esc_html_e( 'Scan Site', 'mainwp-virusdie-extension' ); ?></a>
											<a href="<?php echo 'admin.php?page=ManageSitesVirusdie&id=' . $website->id; ?>" class="item"><?php echo __( 'Site Reports', 'mainwp-virusdie-extension' ); ?></a>
											<?php
											if ( $is_premium ) {
												?>
												<a href="#" class="item mainwp-virusdie-row-set-site-option" opt-name="autocleanup" opt-new-value="<?php echo $enabled_firewall ? '0' : '1'; ?>"><?php echo $enabled_firewall ? esc_html__( 'Disable AutoCleanup', 'mainwp-virusdie-extension' ) : esc_html__( 'Enable AutoCleanup', 'mainwp-virusdie-extension' ); ?></a>
												<a href="#" class="item mainwp-virusdie-row-set-site-option" opt-name="firewall" opt-new-value="<?php echo $enabled_autocleanup ? 'disable' : 'enable'; ?>"><?php echo $enabled_firewall ? esc_html__( 'Disable Firewall', 'mainwp-virusdie-extension' ) : esc_html__( 'Enable Firewall', 'mainwp-virusdie-extension' ); ?></a>
												<?php
											}
											?>
											<a href="#" data-tooltip="<?php esc_html_e( 'Remove from Virusdie account', 'mainwp-virusdie-extension' ); ?>" class="item mainwp-virusdie-row-remove-site"><?php echo __( 'Remove Site', 'mainwp-virusdie-extension' ); ?></a>
											<?php else : ?>
											<a href="#" class="item mainwp-virusdie-row-add-site"><?php esc_html_e( 'Add To Virusdie', 'mainwp-virusdie-extension' ); ?></a>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						<?php } ?>
					<?php endif; ?>
				</tbody>
			</table>
			<div id="mainwp-virusdie-sign-up-modal" class="ui small modal">
					<div class="header"><?php esc_html_e( 'Sign up for Virusdie', 'mainwp-virusdie-extension' ); ?></div>
					<div class="content">
					<div class="ui grid">
						<div class="ui eight wide column">
							<img class="ui centered image" src="<?php echo MAINWP_VIRUSDIE_URL . '/image/img-beat-the-shark.svg'; ?>" alt="<?php esc_attr_e( 'Sign up for Virusdie', 'mainwp-virusdie-extension' ); ?>">
						</div>
						<div class="ui eight wide aligned column">
							<?php
							esc_html_e( 'Sign up for Virusdie to be able to automatically eliminate malicious code. You\'ll be able to detect and eliminate viruses on your sites before they can cause serious damage. You\'ll be able to set up scans at regular intervals, every day, once a week or once a month; and you\'ll be able to select the exact time of your scans. A file editor with malicious code hight-lighting and a vulnerability patching manager are also available only with a Premium license', 'mainwp-virusdie-extension' );
							?>
							<br/><br/>
							<div class="actions">
								<a href="https://myaccount.virusdie.com/#signup" class="ui teal button" target="_blank"><?php esc_html_e( 'Sign Up Now', 'mainwp-virusdie-extension' ); ?></a>
								<a href="https://virusdie.com/about/" class="ui basic teal button" target="_blank"><?php esc_html_e( 'Learn more', 'mainwp-virusdie-extension' ); ?></a>
							</div>
						</div>
					</div>
				</div>
			<?php

			$table_features = array(
				'searching'     => 'true',
				'paging'        => 'true',
				'info'          => 'true',
				'stateSave'     => 'true',
				'stateDuration' => '0',
				'scrollX'       => 'true',
				'colReorder'    => 'true',
				'lengthMenu'    => '[ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ]',
			);

			/**
			 * Filter: mainwp_virusdie_table_features
			 *
			 * Filters the Virusdie table features.
			 *
			 * @param array $table_features Table features array.
			 *
			 * @since 4.0.0
			 */
			$table_features = apply_filters( 'mainwp_virusdie_table_features', $table_features );
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function () {
					jQuery( '#mainwp-virusdie-sites' ).DataTable( {
						<?php
						foreach ( $table_features as $feature => $value ) {
								echo "'" . $feature . "' : " . $value . ',';
						};
						?>
						"columnDefs": [ {
							"targets": 'no-sort',
							"orderable": false
						} ],
						"drawCallback": function( settings ) {
							jQuery( '#mainwp-virusdie-sites .ui.dropdown').dropdown();
							mainwp_datatable_fix_menu_overflow();
						},
					} );
					mainwp_datatable_fix_menu_overflow();
				} );
			</script>

			<div class="ui mini modal" id="mainwp-virusdie-scan-modal">
				<div class="header"><?php echo esc_html__( 'Virusdie', 'mainwp-virusdie-extension' ); ?></div>
				<div class="scrolling content">
					<div class="ui inverted dimmer">
						<div class="ui text loader">
							<?php esc_html_e( 'Scanning...', 'mainwp-virusdie-extension' ); ?>
						</div>
					</div>
				</div>
				<div class="actions">
					<div class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp-virusdie-extension' ); ?></div>
				</div>
			</div>

			<div class="ui modal" id="mainwp-virusdie-add-sites-modal">
				<div class="header"><?php echo esc_html__( 'Adding Sites to Virusdie', 'mainwp-virusdie-extension' ); ?></div>
				<div class="scrolling content">
					<div class="ui inverted dimmer">
						<div class="ui text loader">
							<?php esc_html_e( 'Running...', 'mainwp-virusdie-extension' ); ?>
						</div>
					</div>
				</div>
				<div class="actions">
					<div class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp-virusdie-extension' ); ?></div>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Renders MainWP Virusdie settings page.
	 *
	 * @return void
	 */
	public function render_settings_pages() {
		$email     = MainWP_Virusdie_API::instance()->get_option( 'api_email', '' );
		$api_key   = MainWP_Virusdie_API::instance()->get_api_key();
		$hmac_key  = MainWP_Virusdie_API::instance()->get_hmac_key();
		$signed_in = ! empty( $api_key ) && ! empty( $hmac_key ) ? true : false;

		$sign_in = isset( $_GET['signin'] ) ? true : false;
		$sign_up = isset( $_GET['signup'] ) ? true : false;

		$created_account = ! empty( $api_key ) && ! empty( $hmac_key ) ? true : false;

		$title_btn = $sign_in ? __( 'Signin', 'mainwp-virusdie-extension' ) : ( $sign_up ? __( 'Signup', 'mainwp-virusdie-extension' ) : __( 'Save', 'mainwp-virusdie-extension' ) );

		?>
		<div id="mainwp-virusdie-settings" class="ui segment">
			<?php $this->gen_messages(); ?>
			<form class="ui form" id="mainwp_virusdie_form_settings" method="post" action="admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=settings">
			<input type="hidden" name="mainwp_virusdie_settings_nonce" value="<?php echo wp_create_nonce( 'virusdie_nonce' ); ?>" />
				<?php if ( ! $created_account ) : ?>
				<div class="ui yellow message">
					<?php echo __( 'Due to limitations in Virusdie\'s API, existing accounts created without using the MainWP Virusdie Extension do not have the required permissions to manage connected sites.', 'mainwp-virusdie-extension' ); ?>
				</div>
				<div class="ui yellow message">
					<?php echo __( 'Use this extension only if you plan to create a new Virusdie account through the Sign-Up form below.', 'mainwp-virusdie-extension' ); ?>
				</div>				
				<h3 class="ui header"><?php echo __( 'Create a free Virusdie account', 'mainwp-virusdie-extension' ); ?></h3>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Email address', 'mainwp-virusdie-extension' ); ?></label>
					<div class="ten wide column">
						<input type="text" name="mainwp_virusdie_sigup_email" autocomplete="off" id="mainwp_virusdie_sigup_email" value="" />
						<small><em><?php _e( 'Enter your email to create a free Virusdie account.', 'mainwp-virusdie-extension' ); ?></em></small>
					</div>
				</div>
				<div class="ui divider"></div>
				<button type="submit" name="virusdie_signup_btn" class="ui virusdie big basic right floated button"><?php _e( 'Sign Up', 'mainwp-virusdie-extension' ); ?></button>
				<div class="ui hidden clearing divider"></div>
				<?php else : ?>
					<div class="ui green message">
						<?php echo __( 'Virusdie API Connected successfully.', 'mainwp-virusdie-extension' ); ?>
					</div>	
				<?php endif; ?>
				<h3 class="ui header"><?php echo __( 'Sign in to your Virusdie account to get API Credentials', 'mainwp-virusdie-extension' ); ?></h3>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Email address', 'mainwp-virusdie-extension' ); ?></label>
					<div class="ten wide column">
						<input type="text" name="mainwp_virusdie_api_email" id="mainwp_virusdie_api_email" value="<?php echo esc_attr( $email ); ?>" />
					</div>
				</div>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'One-time password', 'mainwp-virusdie-extension' ); ?></label>
					<div class="ten wide column">
						<input type="password" name="mainwp_virusdie_onetime_passwd" id="mainwp_virusdie_onetime_passwd" autocomplete="off" value="" <?php echo $signed_in ? 'disabled placeholder="&#11044;&#11044;&#11044;&#11044;&#11044;&#11044;&#11044;&#11044;&#11044;&#11044;&#11044;&#11044;"' : ''; ?> /><br/>
						<small><em><?php _e( 'Check your email to find an email from Virusdie that contains the one-time password.', 'mainwp-virusdie-extension' ); ?></em></small>
					</br/>
					</div>
				</div>
				<div class="ui divider"></div>
				<input type="hidden" name="mainwp_virusdie_form_action" id="mainwp_virusdie_form_action" value="">
				<?php if ( ! $signed_in ) : ?>
				<button type="submit" name="virusdie_signin_btn" class="ui virusdie big basic right floated button"><?php _e( 'Sign In', 'mainwp-virusdie-extension' ); ?></button>
				<?php else : ?>
				<input type="submit" name="virusdie_save_btn" class="ui big virusdie button" value="<?php echo esc_html__( 'Reload Virusdie Data', 'mainwp-virusdie-extension' ); ?>">
				<button type="button" name="virusdie_signout_btn" id="virusdie_signout_btn" class="ui virusdie big basic right floated button"><?php _e( 'Sign Out', 'mainwp-virusdie-extension' ); ?></button>
				<?php endif; ?>
			</from>
		</div>
		<?php
	}

	/**
	 * Handles feedback message after settigns submission.
	 *
	 * @param int    $message Message code.
	 * @param string $tab Current tab.
	 *
	 * @return array $feedback Feedback message and type.
	 */
	private function get_post_message( $message, $tab = 'settings' ) {
		$feedback = array();
		if ( 'settings' == $tab ) {
			switch ( $message ) {
				case 1:
					$feedback = array(
						'text' => __( 'Account created successfully. Check your email please to get the one time password.', 'mainwp-virusdie-extension' ),
						'type' => 'green',
					);
					break;
				case 2:
					$feedback = array(
						'text' => __( 'Account creation failed. Please try again or contact support.', 'mainwp-virusdie-extension' ),
						'type' => 'red',
					);
					break;
				case 3:
					$feedback = array(
						'text' => __( 'You are signed in successfully. API credenetials will be loaded automatically.', 'mainwp-virusdie-extension' ),
						'type' => 'green',
					);
					break;
				case 4:
					$feedback = array(
						'text' => __( 'Sign-in failed. Please try again or contact support.', 'mainwp-virusdie-extension' ),
						'type' => 'red',
					);
					break;
				case 5:
					$feedback = array(
						'text' => __( 'API connected successfully.', 'mainwp-virusdie-extension' ),
						'type' => 'green',
					);
					break;
				case 6:
					$feedback = array(
						'text' => __( 'API could not be connected. Please try again or contact support.', 'mainwp-virusdie-extension' ),
						'type' => 'red',
					);
					break;
				case 7:
					$feedback = array(
						'text' => __( 'You are signed out successfully.', 'mainwp-virusdie-extension' ),
						'type' => 'green',
					);
					break;
				case 8:
					$feedback = array(
						'text' => __( 'Sign-out failed. Please try again or contact support.', 'mainwp-virusdie-extension' ),
						'type' => 'red',
					);
					break;
				case 9:
					$feedback = array(
						'text' => __( 'Data updated.', 'mainwp-virusdie-extension' ),
						'type' => 'green',
					);
					break;
			}
		} else {
			switch ( $message ) {
				case 9:
					$feedback = array(
						'text' => __( 'Data updated.', 'mainwp-virusdie-extension' ),
						'type' => 'green',
					);
					break;
			}
		}
		return $feedback;
	}

	/**
	 * Handles site status.
	 *
	 * @param string $status   Site status.
	 * @param bool   $withicon Wheather to use icon or not.
	 *
	 * @return string @msg Status string.
	 */
	public function get_status_message( $status, $withicon = false ) {
			$msg = '';
		switch ( $status ) {
			case 'clean':
				$msg = ( $withicon ? '<i class="ui outline circle virusdie icon"></i> ' : '' ) . esc_html__( 'No threats were found.', 'mainwp-virusdie-extension' );
				break;
			case 'not-synced':
				$msg = ( $withicon ? '<i class="ui outline circle grey icon"></i> ' : '' ) . esc_html__( 'Sync error.', 'mainwp-virusdie-extension' );
				break;
			case 'infected':
				$msg = ( $withicon ? '<i class="ui outline circle red icon"></i> ' : '' ) . esc_html__( 'Threats were found.', 'mainwp-virusdie-extension' );
				break;
		}
		return $msg;
	}

	/**
	 * Get Child Site results.
	 *
	 * @param array $websites       Child sites array.
	 * @param int   $selected_group Selected Child Site group.
	 *
	 * @return array $sites_results  Child Site results array.
	 */
	public static function get_websites_result( $websites, $selected_group = 0 ) {
		$sites_results = array();

		$groups = array();
		if ( ! empty( $selected_group ) ) {
			$groups = array( $selected_group );
		}

		/**
		 * Extension object
		 *
		 * @global object
		 */
		global $mainWPVirusdieExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), array(), $groups );

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				$sites_results = $websites;
			} else {

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$site_ids       = array();

				foreach ( $group_websites as $site ) {
					$site_ids[] = $site->id;
				}

				foreach ( $websites as $site ) {
					if ( $site && in_array( $site['id'], $site_ids ) ) {
						$sites_results[] = $site;
					}
				}
			}
		}
		return $sites_results;
	}

	/**
	 * Generates Select Sites dropdown.
	 *
	 * @param int $selected_group Selected Child Sites group.
	 *
	 * @return void
	 */
	public static function gen_select_sites( $selected_group ) {

		global $mainWPVirusdieExtensionActivator;

		$api_key   = MainWP_Virusdie_API::instance()->get_api_key();
		$hmac_key  = MainWP_Virusdie_API::instance()->get_hmac_key();
		$signed_in = ! empty( $api_key ) && ! empty( $hmac_key ) ? true : false;

		$groups = apply_filters( 'mainwp_getgroups', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), null );

		?>
		<div class="ui grid">
			<div class="ui eight wide column">
				<button id="virusdie-add-all-sites" class="ui virusdie mini button"><?php _e( 'Add All Child Sites to Virusdie', 'mainwp-virusdie-extension' ); ?></button>
				<?php if ( $signed_in ) { ?>
				<button id="virusdie-reload-data" class="ui virusdie mini basic button"><?php _e( 'Reload Virusdie Data', 'mainwp-virusdie-extension' ); ?></button>
				<?php } ?>
			</div>
			<div class="ui eight wide right aligned column mini form">
				<form method="post" action="admin.php?page=Extensions-Mainwp-Virusdie-Extension">
				<?php _e( 'Filter sites: ', 'mainwp-virusdie-extension' ); ?>
					<div name="mainwp_virusdie_groups_select" id="mainwp_virusdie_groups_select" class="ui selection dropdown not-auto-init">
						<div class="text"><?php _e( 'Select group', 'mainwp-virusdie-extension' ); ?></div>
						<input type="hidden" name="mainwp_virusdie_groups_select" value="<?php echo intval( $selected_group ); ?>">
						<i class="dropdown icon"></i>
						<div class="menu">
							<div class="item" data-value="-1" ><?php esc_html_e( 'All groups', 'mainwp' ); ?></div>
						<?php
						if ( is_array( $groups ) && count( $groups ) > 0 ) {
							foreach ( $groups as $group ) {
								$_select = '';
								if ( $selected_group == $group['id'] ) {
									$_select = 'selected ';
								}
								echo '<div class="item" data-value="' . $group['id'] . '" ' . $_select . '>' . $group['name'] . '</div>';
							}
						}
						?>
						</div>
					</div>
				</form>
			</div>
		</div>

		<script type="text/javascript">
			// Auto create monitors.
			jQuery( '#virusdie-add-all-sites' ).click( function ( event ) {
				if ( jQuery( this ).prop( 'disabled' ) ) {
					return false;
				}
				if ( !confirm( 'In order to connect your websites to your Virusdie account, extension needs to place your unique sync file to the root directory of child sites. Are you sure you want to proceed?' ) )
					return false;
				jQuery( this ).attr( 'disabled', 'disabled' );
				mainwp_virusdie_action_popup( 'add_sites' );
				return false;
			} );

			jQuery( '#virusdie-reload-data' ).click( function ( event ) {
				var wp_nonce = jQuery( 'input[name="mainwp_virusdie_nonce"]' ).val();
				location.href = 'admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=dashboard&virusdie_update_nonce=' + wp_nonce;
			} );
		</script>
		<?php
	}

	/**
	 * Renders Virusdie reports list.
	 *
	 * @param array $website Child Site array.
	 *
	 * @return void
	 */
	public function render_report( $website ) {

		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'site_id', $website['id'] );

		if ( empty( $virusdie ) ) {
			?>
			<div class="ui segment">
			<div class="ui yellow message">
			<?php echo __( 'Site not connected to your Virusdie account.', 'mainwp-virusdie-extension' ); ?>
				</div>
			</div>
			<?php
			return;
		}

		$virusdie_id = $virusdie->virusdie_item_id;

		$last_report = false;
		$scandate    = 0;

		if ( $virusdie_id ) {
			$last_report = MainWP_Virusdie_DB::get_instance()->get_lastscan( $virusdie_id );
			if ( ! empty( $last_report ) ) {
				$scandate = $last_report->scandate;
			}
		}

		if ( ! empty( $scandate ) ) {
			$scanned_at  = MainWP_Virusdie::format_timestamp( MainWP_Virusdie::get_timestamp( $scandate ) );
		} else {
			$scanned_at = __( 'Not scanned yet.', 'mainwp-virusdie-extension' );
		}
		$last_status            = $last_report ? $this->get_status_message( $last_report->status ) : '';
		$last_status_color_code = '';

		if ( 'clean' == $last_report->status ) {
			$last_status_color_code = 'virusdie';
		} elseif ( 'infected' == $last_report->status ) {
			$last_status_color_code = 'red';
		} else {
			$last_status_color_code = 'grey';
		}

		$params        = array(
			'by_value' => $virusdie->virusdie_item_id,
		);
		$saved_reports = MainWP_Virusdie_DB::get_instance()->get_report_by( 'virusdie_item_id', $params );
		?>

		<div class="ui segment">
			<div class="ui hidden divider"></div>
			<div class="ui grid">
				<div class="one wide column">
					<i class="ui huge <?php echo $last_status_color_code; ?> outline circle icon"></i>
				</div>
				<div class="twelve wide middle aligned column">
					<h2 class="ui header">
					<?php echo $website['name'] . ' ' . esc_html__( 'Status (last scan):', 'mainwp-virusdie-extension' ) . ' ' . esc_html( $last_status ); ?>
						<div class="sub header"><?php echo esc_html__( 'Scanned at:', 'mainwp-virusdie-extension' ) . ' ' . $scanned_at; ?></div>
					</h2>
				</div>
				<div class="three wide right aligned middle aligned column">
					<a href="#" class="ui virusdie large button <?php echo 'infected' == $last_report->status ? '' : 'disabled'; ?>"><?php echo esc_html__( 'CLEAN UP', 'mainwp-virusdie-extension' ); ?></a>
				</div>
			</div>
			<input type="hidden" name="mainwp_virusdie_site_id" value="<?php echo $website['id']; ?>"/>
			<input type="hidden" name="mainwp_virusdie_item_id" value="<?php echo intval( $virusdie->virusdie_item_id ); ?>"/>
			<div class="ui hidden divider"></div>
			<div class="ui divider"></div>
			<div class="ui hidden divider"></div>
			<h3 class="ui header"><?php esc_html_e( 'Reports', 'mainwp-virusdie-extension' ); ?></h3>
			<div class="ui relaxed divided list" id="mainwp-virusdie-scan-list">
			<?php
			if ( is_array( $saved_reports ) && count( $saved_reports ) > 0 ) {
				foreach ( $saved_reports as $report ) {
					?>
						<div class="item" virusdie-report-id="<?php echo esc_html( $report->id ); ?>">
							<div class="ui grid">
								<div class="three column row">
									<div class="middle aligned column"><?php echo $this->get_status_message( $report->status, true ); ?></div>
									<div class="middle aligned column"><?php echo esc_html__( 'Scanned at:', 'mainwp-virusdie-extension' ) . ' ' . MainWP_Virusdie::format_timestamp( MainWP_Virusdie::get_timestamp( $report->scandate ) ); ?></div>
									<div class="right aligned middle aligned column"><a href="#" class="mainwp-virusdie-saved-report-show ui virusdie mini basic button"><?php _e( 'Details' ); ?></a></div>
								</div>
							</div>
						</div>
						<?php
				}
			} else {
				echo '<div class="item">' . __( 'No saved reports.', 'mainwp-virusdie-extension' ) . '</div>';
			}
			?>
			</div>

			<div id="mainwp-virusdie-report-modal" class="ui modal">
				<div class="header"><?php echo __( 'Virusdie Scan Report', 'mainwp-virusdie-extension' ); ?> - <span id="scan-time"></span></div>
				<div class="scrolling content" id="mainwp-virusdie-security-scan-result">
					<div class="ui inverted dimmer">
						<div class="ui text loader">
						<?php esc_html_e( 'Loading data...', 'mainwp-virusdie-extension' ); ?>
						</div>
					</div>
				</div>
				<div class="actions">
					<a href="#" class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp-virusdie-extension' ); ?></a>
				</div>
			</div>

			<input type="hidden" name="mainwp_virusdie_site_nonce" value="<?php echo wp_create_nonce( 'virusdie_nonce' ); ?>" />
		</div>
		<?php
	}


	/**
	 * Adds sites.
	 *
	 * @return void
	 */
	public function ajax_add_sites() {

		$this->ajax_check_permissions( 'virusdie_nonce' );

		global $mainWPVirusdieExtensionActivator;

		$new_sites   = array();
		$new_uploads = array();
		$site_id     = isset( $_POST['site_id'] ) ? intval( $_POST['site_id'] ) : 0;

		if ( ! empty( $site_id ) ) {
			$websites = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), $site_id );
			if ( $websites ) {
				$website                       = current( $websites );
				$new_sites[]                   = array(
					'name' => $website['url'],
				);
				$new_uploads[ $website['id'] ] = $website['url'];

				// to fix site_id for existed domain.				
				$domain = str_replace( array( 'https://www.', 'http://www.', 'https://', 'http://', 'www.', '/' ), array( '', '', '', '', '', '' ), $website['url'] );
				MainWP_Virusdie_DB::get_instance()->update_virusdie_site_id( $domain, $website['id'] );
			} else {
				die( __( 'Site not found.', 'mainwp-virusdie-extension' ) );
			}
		} else {
			// add multi sites.
			$virusdies = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'all' );
			$sites_ids = array();
			if ( $virusdies ) {
				foreach ( $virusdies as $item ) {
					$sites_ids[ $item->site_id ] = $item->virusdie_item_id;
				}
			}

			$websites = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), null );

			if ( $websites ) {
				foreach ( $websites as $website ) {
					if ( ! isset( $sites_ids[ $website['id'] ] ) || 0 == $sites_ids[ $website['id'] ] ) {
						$new_sites[]                   = array(
							'name' => $website['url'],
						);
						$new_uploads[ $website['id'] ] = $website['url'];
					}
				}
			}
		}

		if ( empty( $new_sites ) ) {
			die( __( 'All sites have already been added.', 'mainwp-virusdie-extension' ) );
		}

		// start adding sites.
		$information = MainWP_Virusdie_API::instance()->sites_add( $new_sites );

		$message = is_array( $information ) && ! empty( $information['error'] ) ? '<div class="ui red message">' . esc_html( $information['error'] ) . '</div>' : '<div class="ui green message">' . __( 'Sites added successfully. Please wait until your unique sync file gets uploaded to your child sites.', 'mainwp-virusdie-extension' ) . '</div>';

		$upload_syncfiles = '';
		$result           = MainWP_Virusdie_API::instance()->get_syncfile();
		if ( ! is_array( $result ) || empty( $result['filename'] ) ) {
			$message .= '<div class="ui red message">' . __( 'Unique sync file could not be uploaded. Please upload it manually.', 'mainwp-virusdie-extension' ) . '</div>';
		} else {

			$file_content    = $result['content'];
			$file_name       = $result['filename'];
			$hasWPFileSystem = apply_filters( 'mainwp_getwpfilesystem', false );

			if ( $this->check_file_name( $file_name ) ) {
				/**
				 * WordPress files system object.
				 *
				 * @global object
				 */
				global $wp_filesystem;

				if ( $hasWPFileSystem && ! empty( $wp_filesystem ) ) {
					$uploadsDir = $this->get_uploads_folder();
					$file_tmp   = $uploadsDir . '/' . $file_name;
					$wp_filesystem->put_contents( $file_tmp, $file_content );
				}

				$message          .= '<h3>' . __( 'Uploading synchronization files', 'mainwp-virusdie-extension' ) . '</h3>';
				$upload_syncfiles .= '<div class="ui middle aligned divided selection list">';
				$upload_syncfiles .= '<input type="hidden" id="sync-file-tmp" file-name="' . esc_attr( $file_name ) . '"/>';
				foreach ( $new_uploads as $website_id => $url ) {
					$upload_syncfiles .= '<div class="item siteItemProcess" action="" site-id="' . $website_id . '" status="queue">';
					$upload_syncfiles .= '<div class="right floated content">';
					$upload_syncfiles .= '<div class="upload-file-status" niceurl="' . $url . '" siteid="' . $website_id . '"><i class="clock outline icon"></i></div>';
					$upload_syncfiles .= '</div>';
					$upload_syncfiles .= '<div class="content">' . $url . '</div>';
					$upload_syncfiles .= '</div>';
				}
				$upload_syncfiles .= '</div>';
				$upload_syncfiles .= '<div id="mainwp-message-zone" class="ui message" style="display:none"></div>';
			}
		}
		die( $message . $upload_syncfiles );
	}



	/**
	 * Uploads the unique sync file.
	 *
	 * @return void
	 */
	public function ajax_upload_sync_file() {
		$this->ajax_check_permissions( 'virusdie_nonce' );

		$site_id   = $_POST['siteId'];
		$file_name = $_POST['filename'];

		if ( empty( $site_id ) || empty( $file_name ) || ! $this->check_file_name( $file_name ) ) {
			die( wp_json_encode( 'FAIL' ) );
		}

		$uploadsDir = $this->get_uploads_folder();

		$local_file = $uploadsDir . '/' . $file_name;

		if ( ! file_exists( $local_file ) ) {
			die( wp_json_encode( 'NOTEXIST' ) );
		}

		global $mainWPVirusdieExtensionActivator;

		$file_url = apply_filters( 'mainwp_getdownloadurl', null, $file_name );

		$post_data = array(
			'url'      => base64_encode( $file_url ),  // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- compatibility.
			'filename' => $file_name,
			'path'     => '/', // root WP folder.
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), $site_id, 'uploader_action', $post_data );

		if ( is_array( $information ) && isset( $information['error'] ) ) {
			$information['error'] = wp_strip_all_tags( $information['error'] );
		}

		die( wp_json_encode( $information ) );
	}

	/**
	 * Delete the temporary sync file.
	 *
	 * @return void
	 */
	public function ajax_delete_temp_file() {
		$this->ajax_check_permissions( 'virusdie_nonce' );
		$tmp_file = $_POST['tmp_file'];
		if ( $this->check_file_name( $tmp_file ) ) {
			$uploadsDir = $this->get_uploads_folder();
			$file_path  = $uploadsDir . '/' . $tmp_file;

			if ( file_exists( $file_path ) ) {
				unlink( $file_path );
			}
			die( 'SUCCESS' );
		} else {
			die( 'FAIL' );
		}
	}

	/**
	 * Gets the uploads folder.
	 *
	 * @return string folder
	 */
	public function get_uploads_folder() {
		return apply_filters( 'mainwp_getspecificdir', '' );
	}

	/**
	 * Checks the file type..
	 *
	 * @param string $filename Contains file name.
	 *
	 * @return true|false valid name or not.
	 */
	public static function check_file_name( $filename ) {
		if ( validate_file( $filename ) ) {
			return false;
		}

		$allowed_files = array( 'php' );
		$file_ext      = explode( '.', $filename );
		$file_ext      = end( $file_ext );
		$file_ext      = strtolower( $file_ext );
		if ( ! in_array( $file_ext, $allowed_files ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Shows Virusdie scan report.
	 *
	 * @return void
	 */
	public function ajax_show_report() {

		$this->ajax_check_permissions( 'virusdie_nonce' );

		$report_id = intval( $_POST['report_id'] );

		if ( empty( $report_id ) ) {
			die();
		}
		$params = array(
			'by_value' => $report_id,
		);
		$report = MainWP_Virusdie_DB::get_instance()->get_report_by( 'id', $params );
		if ( $report ) {
			$scanned_at  = MainWP_Virusdie::format_timestamp( MainWP_Virusdie::get_timestamp($report->scandate ) );
			ob_start();
			$this->display_report( $report );
			$result = ob_get_clean();
			wp_send_json(
				array(
					'result'    => $result,
					'scan_date' => $scanned_at,
				)
			);
		}
		die();
	}

	/**
	 * Display Virusdie Scan Report.
	 *
	 * @param array $report Malware & Blacklist data array.
	 *
	 * @return void
	 */
	public function display_report( $report ) {

		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'virusdie_item_id', $report->virusdie_item_id );

		$scan_site = $virusdie->domain;
		$domain    = $virusdie->domain;

		?>
		<div class="ui segment">
			<div class="ui grid">
				<div class="two column row">
					<div class="center aligned middle aligned column">
						<img src="<?php echo MAINWP_VIRUSDIE_URL . '/image/img-relax.svg'; ?>" alt="<?php esc_attr_e( 'Sign up for Virusdie', 'mainwp-virusdie-extension' ); ?>">
					</div>
					<div class="center aligned middle aligned column">
						<table class="ui definition table">
							<tbody>
								<tr>
									<td><?php echo esc_html__( 'Checked bytes', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo ( $report->checkedbytes > 1024 * 1024 * 1024 ) ? ( ( round( $report->checkedbytes / ( 1024 * 1024 * 1024 ), 2 ) ) . ' GB' ) : ( ( round( $report->checkedbytes / ( 1024 * 1024 ), 2 ) ) . ' MB' ); ?></td>
								</tr>
								<tr>
									<td><?php echo esc_html__( 'Detected files', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo intval( $report->detectedfiles ); ?></td>
								</tr>
								<tr>
									<td><?php echo esc_html__( 'Incurable files', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo intval( $report->incurablefiles ); ?></td>
								</tr>
								<tr>
									<td><?php echo esc_html__( 'Vulnerabilities', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo intval( $report->malicious ); ?></td>
								</tr>
								<tr>
									<td><?php echo esc_html__( 'Checked dirs', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo intval( $report->checkeddirs ); ?></td>
								</tr>
								<tr>
									<td><?php echo esc_html__( 'Checked files', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo intval( $report->checkedfiles ); ?></td>
								</tr>
								<tr>
									<td><?php echo esc_html__( 'Treated files', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo intval( $report->treatedfiles ); ?></td>
								</tr>
								<tr>
									<td><?php echo esc_html__( 'Deleted files', 'mainwp-virusdie-extension' ); ?></td>
									<td><?php echo intval( $report->deletedfiles ); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Performs Ajax Virusdie Site Scan.
	 *
	 * @return void
	 */
	public function ajax_virusdie_scan() {

		$this->ajax_check_permissions( 'virusdie_nonce' );

		$virusdie_id = $_POST['virusdie_id'];

		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'virusdie_item_id', $virusdie_id );

		if ( empty( $virusdie ) ) {
			die( __( 'Site not found in Virusdie dashboard.', 'mainwp-virusdie-extension' ) );
		}

		$site_page = isset( $_POST['site_page'] ) && $_POST['site_page'] ? true : false;

		$results = MainWP_Virusdie_API::instance()->scan_site( $virusdie->domain );

		$scan_result = '';
		$scan_status = '';

		ob_start();

		if ( is_array( $results ) && empty( $results['error'] ) ) {
			$scan_status = 'success';
			$scan_result = __( 'Scan process initiated.', 'mainwp-virusdie-extension' );
			echo '<div class="ui green message">' . esc_html( $scan_result ) . '</div>';
		} elseif ( ! empty( $results['error'] ) ) {
			$scan_status = 'failed';
			$scan_result = $results['error'];
		}
		if ( 'failed' == $scan_status ) {
			echo '<div class="ui red message">' . esc_html( $scan_result ) . '</div>';
		}
		$html = ob_get_clean();

		if ( $virusdie->site_id ) {
			do_action( 'mainwp_virusdie_scan_finished', $virusdie->site_id, $scan_status, $scan_result );
		}

		if ( $site_page ) {
			die(
				wp_json_encode(
					array(
						'result' => $html,
					)
				)
			);
		} else {
			die( $html );
		}
	}

	/**
	 * Performs Ajax Virusdie Site Remove.
	 *
	 * @return void
	 */
	public function ajax_remove_site() {

		$this->ajax_check_permissions( 'virusdie_nonce' );

		$virusdie_id = $_POST['virusdie_id'];

		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'virusdie_item_id', $virusdie_id );

		if ( empty( $virusdie ) ) {
			die( __( 'Site not found in Virusdie dashboard.', 'mainwp-virusdie-extension' ) );
		}

		$results = MainWP_Virusdie_API::instance()->site_delete( $virusdie->domain );

		$update = array(
			'site_id'          => $virusdie->site_id,
			'virusdie_item_id' => 0,
		);

		MainWP_Virusdie_DB::get_instance()->update_virusdie( $update );

		$scan_result = '';
		$scan_status = '';

		ob_start();

		if ( is_array( $results ) && empty( $results['error'] ) ) {
			$scan_status = 'success';
			$scan_result = __( 'Site removed successfully.', 'mainwp-virusdie-extension' );
			echo '<div class="ui green message">' . esc_html( $scan_result ) . '</div>';
		} elseif ( ! empty( $results['error'] ) ) {
			$scan_status = 'failed';
			$scan_result = $results['error'];
		}
		if ( 'failed' == $scan_status ) {
			echo '<div class="ui red message">' . esc_html( $scan_result ) . '</div>';
		}
		$html = ob_get_clean();

		if ( $virusdie->site_id ) {
			do_action( 'mainwp_virusdie_remove_finished', $virusdie->site_id, $scan_status, $scan_result );
		}
		die( $html );
	}

	/**
	 * Performs Ajax Virusdie Site Remove.
	 *
	 * @return void
	 */
	public function ajax_set_option_site() {

		$this->ajax_check_permissions( 'virusdie_nonce' );

		$virusdie_id = $_POST['virusdie_id'];

		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'virusdie_item_id', $virusdie_id );

		if ( empty( $virusdie ) ) {
			die( __( 'Site not found in Virusdie dashboard.', 'mainwp-virusdie-extension' ) );
		}

		$opt_name  = $_POST['opt_name'];
		$opt_value = $_POST['opt_value'];

		if ( 'autocleanup' != $opt_name && 'firewall' != $opt_name ) {
			die( __( 'Invalid option.', 'mainwp-virusdie-extension' ) );
		}

		$results = MainWP_Virusdie_API::instance()->site_setoption( $virusdie->domain, $opt_name, $opt_value );

		$scan_result = '';
		$status      = '';

		ob_start();

		if ( is_array( $results ) && empty( $results['error'] ) ) {

			$update = array(
				$opt_name          => $opt_value,
				'virusdie_item_id' => $virusdie_id,
			);

			MainWP_Virusdie_DB::get_instance()->update_virusdie( $update );

			$status      = 'success';
			$scan_result = __( 'Successful.', 'mainwp-virusdie-extension' );
			echo '<div class="ui green message">' . esc_html( $scan_result ) . '</div>';
		} elseif ( ! empty( $results['error'] ) ) {
			$status      = 'failed';
			$scan_result = $results['error'];
		}
		if ( 'failed' == $status ) {
			echo '<div class="ui red message">' . esc_html( $scan_result ) . '</div>';
		}
		$html = ob_get_clean();

		if ( $virusdie->site_id ) {
			do_action( 'mainwp_virusdie_remove_finished', $virusdie->site_id, $status, $scan_result );
		}
		die( $html );
	}

	/**
	 * Method get_timestamp_from_hh_mm()
	 *
	 * Get Time Stamp from $hh_mm.
	 *
	 * @param mixed $hh_mm Global time stamp variable.
	 * @param int   $time Time of day.
	 *
	 * @return time Y-m-d 00:00:59.
	 */
	public static function get_timestamp( $timestamp ) {
		$gmtOffset = get_option( 'gmt_offset' );

		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}

	/**
	 * Method format_timestamp()
	 *
	 * Format the given timestamp.
	 *
	 * @param mixed $timestamp Timestamp to format.
	 *
	 * @return string Formatted timestamp.
	 */
	public static function format_timestamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Ajax check permissions.
	 *
	 * @param string $action Action to check permissions for.
	 * @param bool   $json   TRUE or json encoded error message on failure.
	 */
	public function ajax_check_permissions( $action, $json = false ) {
		if ( has_filter( 'mainwp_currentusercan' ) ) {
			if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-virusdie-extension' ) ) {
				$output = mainwp_do_not_have_permissions( 'MainWP Virusdie Extension ' . $action, ! $json );
				if ( $json ) {
					echo wp_json_encode( array( 'error' => $output ) );
				}
				die();
			}
		}

		if ( ! isset( $_REQUEST['wp_nonce'] ) || ! wp_verify_nonce( $_REQUEST['wp_nonce'], $action ) ) {
			echo $json ? wp_json_encode( array( 'error' => 'Error: Wrong or expired request' ) ) : 'Error: Wrong or expired request';
			die();
		}
	}

	/**
	 * Method mainwp_help_content()
	 *
	 * Hook the extension help content to the Help Sidebar element
	 *
	 * @return void Help section html.
	 */
	public static function mainwp_help_content() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Virusdie-Extension' == $_GET['page'] ) ) {
			?>
			<p><?php esc_html_e( 'If you need help with the Virusdie extension, please review following help documents', 'mainwp' ); ?></p>
			<div class="ui relaxed bulleted list">
				<div class="item"><a href="https://kb.mainwp.com/docs/mainwp-virusdie-extension/" target="_blank">MainWP Virusdie Extension</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/scan-child-sites-with-virusdie/" target="_blank">Scan Child Sites With Virusdie</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/add-sites-to-virusdie-dashboard/" target="_blank">Add Sites To Virusdie Dashboard</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/set-up-the-mainwp-virusdie-extension/" target="_blank">Set up the MainWP Virusdie Extension</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/virusdie-extension-api-compatibility/" target="_blank">Virusdie Extension API Compatibility</a></div>
			</div>
			<?php
		}
	}

}
