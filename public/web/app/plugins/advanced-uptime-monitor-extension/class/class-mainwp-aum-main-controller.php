<?php
/**
 * MainWP AUM Database Controller
 *
 * This file handles all interactions with the DB.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Main_Controller
 */
class MainWP_AUM_Main_Controller extends MainWP_AUM_Controller {

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_Main_Controller.
	 */
	private static $instance = null;


	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method __construct()
	 *
	 * Contructor.
	 */
	public function __construct() {
	}

	/**
	 * Method admin_init()
	 *
	 * Admin init.
	 */
	public function admin_init() {
		$this->handle_main_post_saving();
		add_action( 'wp_ajax_mainwp_advanced_uptime_delete_url', array( $this, 'ajax_delete_url' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_edit_monitor', array( $this, 'ajax_url_edit_monitor' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_monitor_urls', array( $this, 'ajax_monitor_urls' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_go_auth', array( $this, 'ajax_go_auth' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_add_site_monitor', array( $this, 'ajax_add_monitor_for_site' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_statistics_table', array( $this, 'ajax_statistics_table' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_meta_box', array( $this, 'ajax_meta_box' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_auto_add_sites', array( $this, 'ajax_add_sites_monitor' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_url_start', array( $this, 'ajax_url_start' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_url_pause', array( $this, 'ajax_url_pause' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_info_location', array( $this, 'ajax_info_location' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_reload_monitors', array( $this, 'ajax_reload_monitors' ) );
	}

	/**
	 * Handles post saving.
	 */
	public function handle_main_post_saving() {

		$saved = false;

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Advanced-Uptime-Monitor-Extension' ) {
			if ( isset( $_GET['gen_access'] ) ) {
				$gen = MainWP_AUM_Site24x7_API::instance()->gen_access_tokens();
				if ( $gen ) {
					$saved = 3;
				}
			}
			if ( $saved ) {
				wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings&message=' . $saved ) );
				exit();
			}
		}

		if ( isset( $_POST['aum_submit'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'mainwp_aum_nonce_option' ) ) {

			$enable_service = isset( $_POST['mainwp-select-service'] ) ? trim( $_POST['mainwp-select-service'] ) : 'uptimerobot';

			update_option( 'mainwp_aum_enabled_service', $enable_service );

			$data = isset( $_POST['aum_services_settings'] ) && isset( $_POST['aum_services_settings'][ $enable_service ] ) ? $_POST['aum_services_settings'][ $enable_service ] : array();

			$reload_monitors = '';
			if ( ! empty( $data ) ) {
				if ( 'uptimerobot' == $enable_service ) {
					$reload = MainWP_AUM_UptimeRobot_Settings_Handle::instance()->handle_post_saving( $data );
					if ( $reload ) {
						$reload_monitors = '&reload_uptime_monitors=yes';
					}
					$saved = true;
				} elseif ( 'site24x7' == $enable_service ) {
					$saved = MainWP_AUM_Site24x7_Settings_Handle::get_instance()->handle_post_saving( $data );
				} elseif ( 'nodeping' == $enable_service ) {
					$saved = MainWP_AUM_NodePing_Settings_Handle::get_instance()->handle_post_saving( $data );
				} elseif ( 'betteruptime' == $enable_service ) {
					$saved = MainWP_AUM_BetterUptime_Settings_Handle::get_instance()->handle_post_saving( $data );
				}
			}

			if ( $saved ) {
				wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings&message=1' . $reload_monitors ) );
				exit();
			}
		}

		$saved = MainWP_AUM_Site24x7_Settings_Handle::get_instance()->handle_oauth_redirect();
		if ( $saved ) {
			wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings&message=2' ) );
			exit();
		}

		if ( ! $saved ) {
			$selected_service = get_option( 'mainwp_aum_enabled_service', 'uptimerobot' );
			if ( 'site24x7' == $selected_service ) {
				MainWP_AUM_Site24x7_API::instance()->auto_gen_expired_access_tokens();
			}
		}
	}

	/**
	 * Monitors URLs via AJAX request.
	 *
	 * @return void
	 */
	public function ajax_monitor_urls() {
		if ( isset( $_REQUEST['service'] ) ) {
			if ( 'site24x7' == $_REQUEST['service'] ) {
				MainWP_AUM_Site24x7_Controller::instance()->ajax_monitor_urls();
				return;
			} elseif ( 'nodeping' == $_REQUEST['service'] ) {
				MainWP_AUM_Nodeping_Controller::instance()->ajax_monitor_urls();
				return;
			} elseif ( 'betteruptime' == $_REQUEST['service'] ) {
				MainWP_AUM_BetterUptime_Controller::instance()->ajax_monitor_urls();
				return;
			}
		}
		MainWP_AUM_UptimeRobot_Controller::instance()->ajax_monitor_urls();
	}

	/**
	 * Deletes monitor via AJAX request.
	 */
	public function ajax_delete_url() {
		$this->ajax_check_permissions( 'monitors_page' );
		if ( isset( $this->params['service'] ) ) {
			if ( 'uptimerobot' == $this->params['service'] ) {
				$url      = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'] );
				$response = MainWP_AUM_UptimeRobot_API::instance()->delete_monitor( $url->monitor_id );
				$response = json_decode( $response );
				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $this->params['url_id'] );
				MainWP_AUM_DB_Events_UptimeRobot::instance()->delete_events( $url->url_id );
				do_action( 'mainwp_aum_monitor_deleted', $url );
				if ( $response && $response->stat == 'ok' ) {
					die( 'success' );
				}
			} elseif ( 'site24x7' == $this->params['service'] ) {
				$url      = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'], 'site24x7' );
				$response = MainWP_AUM_Site24x7_API::instance()->delete_monitor( $url->monitor_id );
				$response = json_decode( $response, true );
				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $this->params['url_id'] );
				MainWP_AUM_DB_Events_UptimeRobot::instance()->delete_events( $url->url_id );

				if ( is_array( $response ) && 'success' == $response['message'] ) {
					do_action( 'mainwp_aum_monitor_deleted', $url );
					die( 'success' );
				} elseif ( is_array( $response ) && isset( $response['error_code'] ) ) {
					wp_die( 'Error: ' . $response['message'] . '(' . $response['error_code'] . ')' );
				}
			} elseif ( 'nodeping' == $this->params['service'] ) {
				$url      = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'], 'nodeping' );
				$response = MainWP_AUM_Nodeping_API::instance()->delete_monitor( $url->monitor_id );
				$response = json_decode( $response, true );

				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $this->params['url_id'] );
				MainWP_AUM_DB_Events_UptimeRobot::instance()->delete_events( $url->url_id );
				do_action( 'mainwp_aum_monitor_deleted', $url );

				if ( is_array( $response ) && '1' == $response['ok'] ) {
					die( 'success' );
				} elseif ( isset( $response['error'] ) ) {
					wp_die( $response['error'] );
				}
			} elseif ( 'betteruptime' == $this->params['service'] ) {
				$url      = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'], 'betteruptime' );
				$response = MainWP_AUM_BetterUptime_API::instance()->delete_monitor( $url->monitor_id );
				$response = json_decode( $response, true );

				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $this->params['url_id'] );
				MainWP_AUM_DB_Events_BetterUptime::instance()->delete_events( $url->url_id );
				do_action( 'mainwp_aum_monitor_deleted', $url );

				if ( is_array( $response ) && '1' == $response['ok'] ) {
					die( 'success' );
				} elseif ( isset( $response['error'] ) ) {
					wp_die( $response['error'] );
				}
			}
		}
		die( 'invalid request!' );
	}

	/**
	 * Edit monitors via AJAX request.
	 *
	 * @return void
	 */
	public function ajax_url_edit_monitor() {
		if ( isset( $_REQUEST['service'] ) ) {
			if ( 'site24x7' == $_REQUEST['service'] ) {
				MainWP_AUM_Site24x7_Controller::instance()->ajax_url_edit_monitor();
				return;
			} elseif ( 'nodeping' == $_REQUEST['service'] ) {
				MainWP_AUM_Nodeping_Controller::instance()->ajax_url_edit_monitor();
				return;
			} elseif ( 'betteruptime' == $_REQUEST['service'] ) {
				MainWP_AUM_BetterUptime_Controller::instance()->ajax_url_edit_monitor();
				return;
			}
		}
		MainWP_AUM_UptimeRobot_Controller::instance()->ajax_url_edit_monitor();
	}

	/**
	 * Renders stats table via AJAX request.
	 *
	 * @return void
	 */
	public function ajax_statistics_table() {
		if ( isset( $_REQUEST['service'] ) ) {
			if ( 'site24x7' == $_REQUEST['service'] ) {
				MainWP_AUM_Site24x7_Controller::instance()->ajax_statistics_table();
				return;
			} elseif ( 'nodeping' == $_REQUEST['service'] ) {
				MainWP_AUM_Nodeping_Controller::instance()->ajax_statistics_table();
				return;
			} elseif ( 'betteruptime' == $_REQUEST['service'] ) {
				MainWP_AUM_BetterUptime_Controller::instance()->ajax_statistics_table();
				return;
			}
		}
		MainWP_AUM_UptimeRobot_Controller::instance()->ajax_statistics_table();
	}


	/**
	 * Add moniotor for a site via AJAX request.
	 */
	public function ajax_add_sites_monitor() {

		$this->ajax_check_permissions( 'monitors_page' );

		$service = isset( $_REQUEST['service'] ) ? $_REQUEST['service'] : '';

		$result = false;
		if ( ! empty( $service ) ) {
			$result = MainWP_AUM_DB::instance()->get_monitor_urls( $service );
		}

		$current_sites_addresses = array();
		$count                   = $result && is_array( $result ) ? count( $result ) : 0;
		for ( $i = 0; $i < $count; $i++ ) {
			$value                         = str_replace( array( 'https://www.', 'http://www.', 'https://', 'http://', 'www.', '/' ), array( '', '', '', '', '', '' ), $result[ $i ]->url_address );
			$current_sites_addresses[ $i ] = $value;
		}

		$other_site_urls = array();

		global $mainwpAUMExtensionActivator;
		$pWebsites = apply_filters( 'mainwp_getsites', $mainwpAUMExtensionActivator->get_child_file(), $mainwpAUMExtensionActivator->get_child_key(), null );
		if ( count( $current_sites_addresses ) > 0 ) {
			foreach ( $pWebsites as $website ) {
				$url   = rtrim( $website['url'], '/' );
				$value = str_replace( array( 'https://www.', 'http://www.', 'https://', 'http://', 'www.', '/' ), array( '', '', '', '', '', '' ), $url );
				if ( ! in_array( $value, $current_sites_addresses ) ) {
					$other_site_urls[ $website['id'] ] = $url;
				}
			}
		} else {
			foreach ( $pWebsites as $website ) {
				$url                               = rtrim( $website['url'], '/' );
				$other_site_urls[ $website['id'] ] = $url;
			}
		}

		if ( empty( $other_site_urls ) ) {
			echo __( 'Monitors for all sites have already been created.', 'advanced-uptime-monitor-extension' );
			die();
		} else {
			echo '<div class="ui middle aligned divided selection list">';
			foreach ( $other_site_urls as $website_id => $url ) {
				?>
				<div class="item siteItemProcess" action="" site-id="<?php echo $website_id; ?>" status="queue">
					<div class="right floated content">
						<div class="sync-site-status" niceurl="<?php echo $url; ?>" siteid="<?php echo $website_id; ?>"><i class="clock outline icon"></i></div>
					</div>
					<div class="content"><?php echo $url; ?></div>
				</div>
				<?php
			}
			echo '</div>';
		}
		?>
		<script type="text/javascript">
			 var aum_bulkFinishedThreads = 0;
			 var aum_bulkTotalThreads = 0;
			 var aum_bulkCurrentThreads = 0;
			 var aum_bulkMaxThreads = 3;

			mainwp_aum_auto_add_monitors_start_next = function() {
				while ((objProcess = jQuery( '.siteItemProcess[status=queue]:first' )) && (objProcess.length > 0) && (aum_bulkCurrentThreads < aum_bulkMaxThreads)) {
						objProcess.attr( 'status', 'processed' );
						mainwp_aum_auto_add_monitors_start_specific( objProcess );
				}
			}

			mainwp_aum_auto_add_monitors_start_specific = function(objProcess) {

				var statusEl = objProcess.find( '.sync-site-status' );
				aum_bulkCurrentThreads++;
				var data = {
					action: 'mainwp_advanced_uptime_add_site_monitor',
					site_id: objProcess.attr( 'site-id' ),
					service: jQuery('#mainwp-aum-form-field-service').val(),
					wp_nonce: jQuery('input[name=wp_js_nonce]').val(),
				};

				statusEl.html( '<i class="sync alternate loading icon"></i>' );
				jQuery.post( ajaxurl, data, function ( response ) {
						if ( response) {
							if (response.error) {
								statusEl.html( '<i class="exclamation red icon"></i>');
							} else if (response.result == 'success') {
								statusEl.html( '<i class="check green icon"></i>' );
							} else {
								statusEl.html( '<i class="exclamation red icon"></i>' );
							}
						} else {
							statusEl.html( '<i class="exclamation red icon"></i>' );
						}
						aum_bulkCurrentThreads--;
						aum_bulkFinishedThreads++;
						mainwp_aum_auto_add_monitors_start_next();
				}, 'json' );
			}

			jQuery( document ).ready(function ($) {
				aum_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
				if (aum_bulkTotalThreads > 0) {
					mainwp_aum_auto_add_monitors_start_next();
				}
			});

		</script>
		<?php
		do_action( 'mainwp_aum_auto_add_sites' );
		die();
	}

	/**
	 * Start monitor via AJAX request.
	 */
	public function ajax_url_start() {

		$this->ajax_check_permissions( 'url_sp' );

		$service = isset( $_REQUEST['service'] ) ? $_REQUEST['service'] : '';
		$url     = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'] );

		if ( 'site24x7' == $service ) {
			$result = MainWP_AUM_Site24x7_API::instance()->activate_monitor( $url->monitor_id );
			$result = json_decode( $result, true );

			if ( isset( $result['data'] ) ) {
				$opts_value = array(
					'state' => 0, // activated.
				);
				MainWP_AUM_DB::instance()->save_monitor_url_options( $url->url_id, $opts_value );
				do_action( 'mainwp_aum_monitor_started', $url );
				die( 'success' );
			} else {
				die( esc_html( $result['message'] ) );
			}
		} elseif ( 'uptimerobot' == $service ) {
			$post_data = array(
				'status' => 1,
			);
			$result    = MainWP_AUM_UptimeRobot_API::instance()->api_edit_monitor( $url->monitor_id, $post_data );
			$result    = json_decode( $result );
			if ( $result->stat == 'ok' ) {
				do_action( 'mainwp_aum_monitor_started', $url );
				die( 'success' );
			} else {
				die( esc_html( $result->message ) );
			}
		} elseif ( 'nodeping' == $service ) {
			$update = array(
				'enabled' => 'active',
			);
			$result = MainWP_AUM_Nodeping_API::instance()->api_edit_monitor( $update, $url->monitor_id );
			$result = json_decode( $result, true );
			if ( isset( $result['_id'] ) ) {
				do_action( 'mainwp_aum_monitor_started', $url );
				$opts_value = array(
					'enable' => 'active',
				);
				MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $url->url_id, $opts_value );
				die( 'success' );
			} else {
				die( esc_html( $result['message'] ) );
			}
		} elseif ( 'betteruptime' == $service ) {
			$update = array(
				'paused' => false,
			);
			$result = MainWP_AUM_Betteruptime_API::instance()->api_edit_monitor( $update, $url->monitor_id );
			$result = json_decode( $result, true );
			if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['id'] ) ) {
				do_action( 'mainwp_aum_monitor_started', $url );

				$opts_value = array(
					'status' => 'up',
				);
				MainWP_AUM_DB::instance()->save_monitor_url_options( $url->url_id, $opts_value );

				die( 'success' );
			} else {
				die( esc_html( $result['message'] ) );
			}
		}
		die( 'Invalid request!' );
	}

	/**
	 * Pause monotor via AJAX response.
	 */
	public function ajax_url_pause() {

		$this->ajax_check_permissions( 'url_sp' );

		$service = isset( $_REQUEST['service'] ) ? $_REQUEST['service'] : '';

		$url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'] );

		try {
			if ( 'site24x7' == $service ) {
				$result = MainWP_AUM_Site24x7_API::instance()->suspend_monitor( $url->monitor_id );
				$result = json_decode( $result, true );
				if ( isset( $result['data'] ) ) {
					do_action( 'mainwp_aum_monitor_paused', $url );
					$opts_value = array(
						'state' => 5, // suspended.
					);
					MainWP_AUM_DB::instance()->save_monitor_url_options( $url->url_id, $opts_value );
					die( 'success' );
				} else {
					die( esc_html( $result['message'] ) );
				}
			} elseif ( 'uptimerobot' == $service ) {
				$post_data = array(
					'status' => 0,
				);
				$result    = MainWP_AUM_UptimeRobot_API::instance()->api_edit_monitor( $url->monitor_id, $post_data );

				$result = json_decode( $result );

				if ( $result->stat == 'ok' ) {
					do_action( 'mainwp_aum_monitor_paused', $url );
					die( 'success' );
				} else {
					die( esc_html( $result->message ) );
				}
			} elseif ( 'nodeping' == $service ) {
				$update = array(
					'enabled' => 'inactive',
				);
				$result = MainWP_AUM_Nodeping_API::instance()->api_edit_monitor( $update, $url->monitor_id );
				$result = json_decode( $result, true );
				if ( isset( $result['_id'] ) ) {
					do_action( 'mainwp_aum_monitor_paused', $url );
					$opts_value = array(
						'enable' => 'inactive',
					);
					MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $url->url_id, $opts_value );
					die( 'success' );
				} else {
					die( esc_html( $result['message'] ) );
				}
			} elseif ( 'betteruptime' == $service ) {
				$update = array(
					'paused' => true,
				);
				$result = MainWP_AUM_Betteruptime_API::instance()->api_edit_monitor( $update, $url->monitor_id );
				$result = json_decode( $result, true );
				if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['id'] ) ) {
					do_action( 'mainwp_aum_monitor_paused', $url );

					$opts_value = array(
						'status' => 'paused',
					);
					MainWP_AUM_DB::instance()->save_monitor_url_options( $url->url_id, $opts_value );

					die( 'success' );
				} else {
					die( esc_html( $result['message'] ) );
				}
			}
		} catch ( \Exception $ex ) {
			$err = $ex->getMessage();
			die( esc_html( $err ) );
		}
		die( 'Invalid request!' );
	}


	/**
	 * Method ajax_meta_box()
	 *
	 * Ajax handle.
	 */
	public function ajax_meta_box() {
		$this->ajax_check_permissions( 'meta_box' );
		if ( isset( $_REQUEST['service'] ) ) {
			if ( 'site24x7' == $_REQUEST['service'] ) {
				MainWP_AUM_Site24x7_Controller::instance()->ajax_meta_box();
				return;
			} elseif ( 'nodeping' == $_REQUEST['service'] ) {
				MainWP_AUM_Nodeping_Controller::instance()->ajax_meta_box();
				return;
			} elseif ( 'betteruptime' == $_REQUEST['service'] ) {
				MainWP_AUM_BetterUptime_Controller::instance()->ajax_meta_box();
				return;
			}
		}
		MainWP_AUM_UptimeRobot_Controller::instance()->ajax_meta_box();
	}

	/**
	 * Add monitor for a site via AJAX request.
	 */
	public function ajax_add_monitor_for_site() {
		$this->ajax_check_permissions( 'monitors_page' );

		$site_id = $_POST['site_id'];

		if ( empty( $site_id ) ) {
			die( wp_json_encode( array( 'error' => 'Empty site id' ) ) );
		}

		global $mainwpAUMExtensionActivator;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpAUMExtensionActivator->get_child_file(), $mainwpAUMExtensionActivator->get_child_key(), array( $site_id ), array() );

		$site = false;
		if ( is_array( $dbwebsites ) ) {
			$site = current( $dbwebsites );
		}

		if ( empty( $site ) ) {
			die( wp_json_encode( array( 'error' => 'Site not found' ) ) );
		}

		$service = isset( $_POST['service'] ) ? $_POST['service'] : '';

		if ( empty( $service ) ) {
			die( wp_json_encode( array( 'error' => 'Error: empty service' ) ) );
		}

		$data = array(
			'url_name'    => $site->url,
			'url_address' => $site->url,
			'service'     => $service,
		);

		$just_inserted_id = MainWP_AUM_DB::instance()->insert_monitor_url( $data );

		$information = array();

		if ( $just_inserted_id ) {
			try {
				if ( 'site24x7' == $service ) {
					$information = MainWP_AUM_Site24x7_Controller::instance()->ajax_add_monitor_for_site( $site, $data, $just_inserted_id );
				} elseif ( 'uptimerobot' == $service ) {
					$information = MainWP_AUM_UptimeRobot_Controller::instance()->ajax_add_monitor_for_site( $site, $data, $just_inserted_id );
				} elseif ( 'nodeping' == $service ) {
					$information = MainWP_AUM_Nodeping_Controller::instance()->ajax_add_monitor_for_site( $site, $data, $just_inserted_id );
				} elseif ( 'betteruptime' == $service ) {
					$information = MainWP_AUM_BetterUptime_Controller::instance()->ajax_add_monitor_for_site( $site, $data, $just_inserted_id );
				}
			} catch ( \Exception $ex ) {
				$information['error'] = 'Saving monitor failed!';
			}
		} else {
			$information['error'] = 'Saving monitor failed!';
		}
		die( wp_json_encode( $information ) );
	}

	/**
	 * Ajax go auth.
	 *
	 * @return void
	 */
	public function ajax_go_auth() {
		$this->ajax_check_permissions( 'go_auth', true );
		update_option( 'mainwp_aum_go_auth_scope', sanitize_text_field( wp_unslash( $_POST['scope'] ) ) );
		wp_send_json( array( 'result' => 'ok' ) );
		return;
	}

	/**
	 * Monitors URLs via AJAX request.
	 *
	 * @return void
	 */
	public function ajax_reload_monitors() {
		$this->ajax_check_permissions( 'reload_monitors', true );
		$return = array();
		if ( isset( $_POST['service'] ) ) {
			if ( 'uptimerobot' == $_POST['service'] ) {
				$return = MainWP_AUM_UptimeRobot_Settings_Handle::instance()->reload_monitor_urls( true );
			} elseif ( 'betteruptime' == $_POST['service'] ) {
				$return = MainWP_AUM_BetterUptime_Settings_Handle::get_instance()->reload_monitor_urls( true );
			}
		}
		die( json_encode( $return ) );
	}


	/**
	 * Ajax info locations.
	 */
	public function ajax_info_location() {
		$this->ajax_check_permissions( 'monitors_page' );
		$region = isset( $_POST['region'] ) ? $_POST['region'] : '';
		$locs   = $this->get_info_locations( $region );
		$opts   = $this->get_opts_info_locations( $region );
		wp_die( $opts );
	}

	/**
	 * Gets options info lcoations.
	 *
	 * @param string $region Region.
	 *
	 * @return string Options.
	 */
	public function get_opts_info_locations( $region ) {
		$locs = $this->get_info_locations( $region );
		$opts = '';
		foreach ( $locs as $code => $loc ) {
			$opts .= '<option value="">' . $loc . '</option>';
		}
		return $opts;
	}

	/**
	 * Get info regions.
	 *
	 * @param string $regi Region.
	 *
	 * @return string Region.
	 */
	public function get_info_regions( $regi = null ) {

		$regions = array(
			'---' => __( 'Use Account Default', 'advanced-uptime-monitor-extension' ),
			'nam' => __( 'North America', 'advanced-uptime-monitor-extension' ),
			'eur' => __( 'Europe', 'advanced-uptime-monitor-extension' ),
			'eao' => __( 'East Asia/Oceania', 'advanced-uptime-monitor-extension' ),
			'lam' => __( 'Latin America', 'advanced-uptime-monitor-extension' ),
			'wlw' => __( 'World Wide', 'advanced-uptime-monitor-extension' ),
		);

		if ( null === $regi ) {
			return $regions;
		}

		return isset( $regions[ $regi ] ) ? $regions[ $regi ] : array();
	}


	/**
	 * Gets locations list.
	 *
	 * @param string $regi Region.
	 * @param string $loc  Location
	 *
	 * @return string Location.
	 */
	public function get_info_locations( $regi = '---', $loc = null ) {

		$locations = array(
			'---' => array(
				'none' => __( 'Default (recommended)', 'advanced-uptime-monitor-extension' ),
				'roam' => __( 'Roam', 'advanced-uptime-monitor-extension' ),
				'nl'   => __( 'Amsterdam, Netherlands', 'advanced-uptime-monitor-extension' ),
				'ga'   => __( 'Atlanta, Georgia', 'advanced-uptime-monitor-extension' ),
				'ro'   => __( 'Bucharest, Romania', 'advanced-uptime-monitor-extension' ),
				'il'   => __( 'Chicago, Illinois', 'advanced-uptime-monitor-extension' ),
				'oh'   => __( 'Columbus, Ohio', 'advanced-uptime-monitor-extension' ),
				'uk'   => __( 'Coventry, England', 'advanced-uptime-monitor-extension' ),
				'tx'   => __( 'Dallas, Texas', 'advanced-uptime-monitor-extension' ),
				'co'   => __( 'Denver, Colorado', 'advanced-uptime-monitor-extension' ),
				'de'   => __( 'Frankfurt, Germany', 'advanced-uptime-monitor-extension' ),
				'hk'   => __( 'Hong Kong', 'advanced-uptime-monitor-extension' ),
				'ld'   => __( 'London, England', 'advanced-uptime-monitor-extension' ),
				'ca'   => __( 'Los Angeles, California', 'advanced-uptime-monitor-extension' ),
				'am'   => __( 'Melbourne, Australia', 'advanced-uptime-monitor-extension' ),
				'fl'   => __( 'Miami, Florida', 'advanced-uptime-monitor-extension' ),
				'ny'   => __( 'New York City, New York', 'advanced-uptime-monitor-extension' ),
				'nj'   => __( 'Newark, New Jersey', 'advanced-uptime-monitor-extension' ),
				'ut'   => __( 'Ogden, Utah', 'advanced-uptime-monitor-extension' ),
				'fr'   => __( 'Paris, France', 'advanced-uptime-monitor-extension' ),
				'pe'   => __( 'Perth, Australia', 'advanced-uptime-monitor-extension' ),
				'py'   => __( 'Philadelphia, Pennsylvania', 'advanced-uptime-monitor-extension' ),
				'wa'   => __( 'Seattle, Washington', 'advanced-uptime-monitor-extension' ),
				'sg'   => __( 'Singapore', 'advanced-uptime-monitor-extension' ),
				'au'   => __( 'Sydney, Australia', 'advanced-uptime-monitor-extension' ),
				'jp'   => __( 'Tokyo, Japan', 'advanced-uptime-monitor-extension' ),
				'ot'   => __( 'Toronto, Ontario', 'advanced-uptime-monitor-extension' ),
				'pl'   => __( 'Warsaw, Poland', 'advanced-uptime-monitor-extension' ),
			),
			'nam' => array(
				'none' => __( 'Default (recommended)', 'advanced-uptime-monitor-extension' ),
				'roam' => __( 'Roam', 'advanced-uptime-monitor-extension' ),
				'ga'   => __( 'Atlanta, Georgia', 'advanced-uptime-monitor-extension' ),
				'il'   => __( 'Chicago, Illinois', 'advanced-uptime-monitor-extension' ),
				'oh'   => __( 'Columbus, Ohio', 'advanced-uptime-monitor-extension' ),
				'tx'   => __( 'Dallas, Texas', 'advanced-uptime-monitor-extension' ),
				'co'   => __( 'Denver, Colorado', 'advanced-uptime-monitor-extension' ),
				'ca'   => __( 'Los Angeles, California', 'advanced-uptime-monitor-extension' ),
				'fl'   => __( 'Miami, Florida', 'advanced-uptime-monitor-extension' ),
				'ny'   => __( 'New York City, New York', 'advanced-uptime-monitor-extension' ),
				'nj'   => __( 'Newark, New Jersey', 'advanced-uptime-monitor-extension' ),
				'ut'   => __( 'Ogden, Utah', 'advanced-uptime-monitor-extension' ),
				'py'   => __( 'Philadelphia, Pennsylvania', 'advanced-uptime-monitor-extension' ),
				'wa'   => __( 'Seattle, Washington', 'advanced-uptime-monitor-extension' ),
				'ot'   => __( 'Toronto, Ontario', 'advanced-uptime-monitor-extension' ),
			),
			'eur' => array(
				'none' => __( 'Default (recommended)', 'advanced-uptime-monitor-extension' ),
				'roam' => __( 'Roam', 'advanced-uptime-monitor-extension' ),
				'nl'   => __( 'Amsterdam, Netherlands', 'advanced-uptime-monitor-extension' ),
				'ro'   => __( 'Bucharest, Romania', 'advanced-uptime-monitor-extension' ),
				'uk'   => __( 'Coventry, England', 'advanced-uptime-monitor-extension' ),
				'de'   => __( 'Frankfurt, Germany', 'advanced-uptime-monitor-extension' ),
				'ld'   => __( 'London, England', 'advanced-uptime-monitor-extension' ),
				'fr'   => __( 'Paris, France', 'advanced-uptime-monitor-extension' ),
				'pl'   => __( 'Warsaw, Poland', 'advanced-uptime-monitor-extension' ),
			),
			'eao' => array(
				'none' => __( 'Default (recommended)', 'advanced-uptime-monitor-extension' ),
				'roam' => __( 'Roam', 'advanced-uptime-monitor-extension' ),
				'hk'   => __( 'Hong Kong', 'advanced-uptime-monitor-extension' ),
				'am'   => __( 'Melbourne, Australia', 'advanced-uptime-monitor-extension' ),
				'pe'   => __( 'Perth, Australia', 'advanced-uptime-monitor-extension' ),
				'sg'   => __( 'Singapore', 'advanced-uptime-monitor-extension' ),
				'au'   => __( 'Sydney, Australia', 'advanced-uptime-monitor-extension' ),
				'jp'   => __( 'Tokyo, Japan', 'advanced-uptime-monitor-extension' ),
			),
			'lam' => array(
				'none' => __( 'Default (recommended)', 'advanced-uptime-monitor-extension' ),
				'roam' => __( 'Roam', 'advanced-uptime-monitor-extension' ),
				'ar'   => __( 'Federal, Argentina', 'advanced-uptime-monitor-extension' ),
				'fl'   => __( 'Miami, Florida', 'advanced-uptime-monitor-extension' ),
				'br'   => __( 'Sao Paulo, Brazil', 'advanced-uptime-monitor-extension' ),
			),
			'wlw' => array(
				'none' => __( 'Default (recommended)', 'advanced-uptime-monitor-extension' ),
				'roam' => __( 'Roam', 'advanced-uptime-monitor-extension' ),
				'nl'   => __( 'Amsterdam, Netherlands', 'advanced-uptime-monitor-extension' ),
				'ga'   => __( 'Atlanta, Georgia', 'advanced-uptime-monitor-extension' ),
				'ro'   => __( 'Bucharest, Romania', 'advanced-uptime-monitor-extension' ),
				'il'   => __( 'Chicago, Illinois', 'advanced-uptime-monitor-extension' ),
				'oh'   => __( 'Columbus, Ohio', 'advanced-uptime-monitor-extension' ),
				'uk'   => __( 'Coventry, England', 'advanced-uptime-monitor-extension' ),
				'tx'   => __( 'Dallas, Texas', 'advanced-uptime-monitor-extension' ),
				'co'   => __( 'Denver, Colorado', 'advanced-uptime-monitor-extension' ),
				'de'   => __( 'Frankfurt, Germany', 'advanced-uptime-monitor-extension' ),
				'hk'   => __( 'Hong Kong', 'advanced-uptime-monitor-extension' ),
				'ld'   => __( 'London, England', 'advanced-uptime-monitor-extension' ),
				'ca'   => __( 'Los Angeles, California', 'advanced-uptime-monitor-extension' ),
				'am'   => __( 'Melbourne, Australia', 'advanced-uptime-monitor-extension' ),
				'fl'   => __( 'Miami, Florida', 'advanced-uptime-monitor-extension' ),
				'ny'   => __( 'New York City, New York', 'advanced-uptime-monitor-extension' ),
				'nj'   => __( 'Newark, New Jersey', 'advanced-uptime-monitor-extension' ),
				'ut'   => __( 'Ogden, Utah', 'advanced-uptime-monitor-extension' ),
				'fr'   => __( 'Paris, France', 'advanced-uptime-monitor-extension' ),
				'pe'   => __( 'Perth, Australia', 'advanced-uptime-monitor-extension' ),
				'py'   => __( 'Philadelphia, Pennsylvania', 'advanced-uptime-monitor-extension' ),
				'wa'   => __( 'Seattle, Washington', 'advanced-uptime-monitor-extension' ),
				'sg'   => __( 'Singapore', 'advanced-uptime-monitor-extension' ),
				'au'   => __( 'Sydney, Australia', 'advanced-uptime-monitor-extension' ),
				'jp'   => __( 'Tokyo, Japan', 'advanced-uptime-monitor-extension' ),
				'ot'   => __( 'Toronto, Ontario', 'advanced-uptime-monitor-extension' ),
				'pl'   => __( 'Warsaw, Poland', 'advanced-uptime-monitor-extension' ),
			),
		);

		if ( null === $loc || empty( $loc ) ) {
			return isset( $locations[ $regi ] ) ? $locations[ $regi ] : $locations['---'];
		}
		return isset( $locations[ $regi ][ $loc ] ) ? $locations[ $regi ][ $loc ] : 'none';
	}



	function human_time_diff( $from, $to = 0 ) {
		if ( empty( $to ) ) {
			$to = time();
		}

		$diff = (int) abs( $to - $from );

		if ( $diff < MINUTE_IN_SECONDS ) {
			$secs = $diff;
			if ( $secs <= 1 ) {
				$secs = 1;
			}
			/* translators: Time difference between two dates, in seconds. %s: Number of seconds. */
			$since = sprintf( _n( '%s second', '%s seconds', $secs ), $secs );
		} elseif ( $diff < HOUR_IN_SECONDS && $diff >= MINUTE_IN_SECONDS ) {
			$mins = round( $diff / MINUTE_IN_SECONDS );
			if ( $mins <= 1 ) {
				$mins = 1;
			}
			/* translators: Time difference between two dates, in minutes (min=minute). %s: Number of minutes. */
			$since = sprintf( _n( '%s min', '%s mins', $mins ), $mins );
		} elseif ( $diff < DAY_IN_SECONDS && $diff >= HOUR_IN_SECONDS ) {
			$hours = round( $diff / HOUR_IN_SECONDS );
			if ( $hours <= 1 ) {
				$hours = 1;
			}
			/* translators: Time difference between two dates, in hours. %s: Number of hours. */
			$since = sprintf( _n( '%s hour', '%s hours', $hours ), $hours );
		} elseif ( $diff < WEEK_IN_SECONDS && $diff >= DAY_IN_SECONDS ) {
			$days = floor( $diff / DAY_IN_SECONDS );
			if ( $days <= 1 ) {
				$days = 1;
			}

			/* translators: Time difference between two dates, in days. %s: Number of days. */
			$since_days = sprintf( _n( '%s day', '%s days', $days ), $days );

			$diff_hours  = $diff - $days * DAY_IN_SECONDS;
			$hours       = round( $diff_hours / HOUR_IN_SECONDS );
			$since_hours = sprintf( _n( '%s hour', '%s hours', $hours ), $hours );

			$since = sprintf( '%s and %s', $since_days, $since_hours );

		} elseif ( $diff < MONTH_IN_SECONDS && $diff >= WEEK_IN_SECONDS ) {
			$weeks = round( $diff / WEEK_IN_SECONDS );
			if ( $weeks <= 1 ) {
				$weeks = 1;
			}
			/* translators: Time difference between two dates, in weeks. %s: Number of weeks. */
			$since = sprintf( _n( '%s week', '%s weeks', $weeks ), $weeks );
		} elseif ( $diff < YEAR_IN_SECONDS && $diff >= MONTH_IN_SECONDS ) {
			$months = round( $diff / MONTH_IN_SECONDS );
			if ( $months <= 1 ) {
				$months = 1;
			}
			/* translators: Time difference between two dates, in months. %s: Number of months. */
			$since = sprintf( _n( '%s month', '%s months', $months ), $months );
		} elseif ( $diff >= YEAR_IN_SECONDS ) {
			$years = round( $diff / YEAR_IN_SECONDS );
			if ( $years <= 1 ) {
				$years = 1;
			}
			/* translators: Time difference between two dates, in years. %s: Number of years. */
			$since = sprintf( _n( '%s year', '%s years', $years ), $years );
		}
		return $since;
	}

	/**
	 * Method: map_options_monitor_fields()
	 *
	 * Map options monitor fields.
	 *
	 * @param string $service service type.
	 * @param array  $data Fields data.
	 */
	public function map_options_monitor_fields( $service, &$data ) {
		if ( 'nodeping' == $service || 'betteruptime' == $service ) {
			$tmp_array = array();
			foreach ( $data as $item ) {
				$this->map_options_fields( $service, $item );
				$tmp_array[] = $item;
			}
			$data = $tmp_array;
		}
	}

	/**
	 * Method: map_options_fields()
	 *
	 * Map options monitor fields.
	 *
	 * @param string       $service service type.
	 * @param mixed object $monitor_item Monitor object.
	 */
	public function map_options_fields( $service, &$monitor_item ) {

		$map_fields = array();
		if ( 'nodeping' == $service ) {
			$map_fields = array(
				'monitor_interval',
				'monitor_type',
				'http_username',
				'http_password',
				'state',
				'enable',
				'notify_contacts',
				'description',
				'dependency',
				'region',
				'location',
				'sensitivity',
			);
		} elseif ( 'betteruptime' == $service ) {
			$map_fields = array(
				'monitor_interval',
				'monitor_type',
				'http_username',
				'http_password',
				// 'status', //save to separated option field.
				'request_timeout',
				'check_frequency',
				'required_keyword',
				'port',
				'recovery_period',
				'confirmation_period',
				'call_mo',
				'sms',
				'email',
				'push',
				'maintenance_from',
				'maintenance_to',
				'domain_expiration',
				'verify_ssl',
				'ssl_expiration',
				'request_body',
				'request_headers',
				'follow_redirects',
				'last_checked_at',
				'http_method',
				'availability',
			);
		}

		if ( ! empty( $map_fields ) ) {
			if ( is_object( $monitor_item ) && property_exists( $monitor_item, 'field_options_data' ) ) {
				$data = json_decode( $monitor_item->field_options_data, true );
				if ( is_array( $data ) ) {
					foreach ( $map_fields as $field ) {
						if ( isset( $data[ $field ] ) ) {
							$monitor_item->$field = $data[ $field ];
						}
					}
				}
			} elseif ( is_array( $monitor_item ) && isset( $monitor_item['field_options_data'] ) ) {
				$data = json_decode( $monitor_item['field_options_data'], true );
				if ( is_array( $data ) ) {
					foreach ( $map_fields as $field ) {
						if ( isset( $data[ $field ] ) ) {
							$monitor_item[ $field ] = $data[ $field ];
						}
					}
				}
			}
		}
	}
}
