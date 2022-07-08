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
class MainWP_AUM_Site24x7_Controller extends MainWP_AUM_Controller {

	/**
	 * Sevice name.
	 *
	 * @var $service_name.
	 */
	public $service_name = 'site24x7';

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_Main_Controller.
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 */
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
	 * Method get_monitor_types()
	 *
	 * Gets monitor type.
	 *
	 * @param string $type Available monitor types.
	 *
	 * @return string Monitor type.
	 */
	public static function get_monitor_types( $type ) {
		$types = array(
			'URL'      => 'Website',
			'HOMEPAGE' => 'Web Page Speed (Browser)',
			'RESTAPI'  => 'REST API',
			'PING'     => 'Ping',
			'DNS'      => 'DNS Server',
		);

		return isset( $types[ $type ] ) ? $types[ $type ] : '';
	}

	/**
	 * Method get_monitor_status()
	 *
	 * Gets monitor status.
	 *
	 * @param string $status Available monitor statuses.
	 *
	 * @return string Monitor status.
	 */
	public static function get_monitor_status( $status = false ) {

		$statuses = array(
			0  => __( 'Down', 'advanced-uptime-monitor-extension' ),
			1  => __( 'Up', 'advanced-uptime-monitor-extension' ),
			2  => __( 'Trouble', 'advanced-uptime-monitor-extension' ),
			3  => __( 'Critical', 'advanced-uptime-monitor-extension' ),
			5  => __( 'Suspended', 'advanced-uptime-monitor-extension' ),
			7  => __( 'Maintenance', 'advanced-uptime-monitor-extension' ),
			9  => __( 'Discovery', 'advanced-uptime-monitor-extension' ),
			10 => __( 'Configuration Error', 'advanced-uptime-monitor-extension' ),
		);

		if ( false === $status ) {
			return $statuses;
		}

		return isset( $statuses[ $status ] ) ? $statuses[ $status ] : '';
	}

	/**
	 * Method get_site24x7_monitor_state()
	 *
	 * Gets monitor state.
	 *
	 * @param object $monitor Available monitor statuses.
	 *
	 * @return string $state_item Monitor state.
	 */
	public static function get_site24x7_monitor_state( $monitor ) {
		$state      = $monitor->state;
		$state_item = '';
		switch ( $state ) {
			case 0:
				$state_item = '<span class="ui green mini basic fluid center aligned label">' . __( 'Active', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 5:
				$state_item = '<span class="ui black mini basic fluid center aligned label">' . __( 'Suspended', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
		}
		return $state_item;
	}

	/**
	 * Method render_monitor_status()
	 *
	 * Renders monitor status.
	 *
	 * @param int $status Monitor status.
	 *
	 * @return string $state_item Monitor status label.
	 */
	public static function render_monitor_status( $status ) {
		$state_item = '';
		switch ( $status ) {
			case 0:
				$state_item = '<span class="ui red fluid center aligned label">' . __( 'Down', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 1:
				$state_item = '<span class="ui green fluid center aligned label">' . __( 'Up', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 2:
				$state_item = '<span class="ui yellow fluid center aligned label">' . __( 'Trouble', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 3:
				$state_item = '<span class="ui orange fluid center aligned label">' . __( 'Critical', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 5:
				$state_item = '<span class="ui black fluid center aligned label">' . __( 'Suspended', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 7:
				$state_item = '<span class="ui blue fluid center aligned label">' . __( 'Maintanence', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 9:
				$state_item = '<span class="ui purple fluid center aligned label">' . __( 'Discovery', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
			case 10:
				$state_item = '<span class="ui grey fluid center aligned label">' . __( 'Configuration Error', 'advanced-uptime-monitor-extension' ) . '</span>';
				break;
		}
		return $state_item;
	}

	/**
	 * Method ajax_url_edit_monitor()
	 *
	 * Edits moniotr settings.
	 */
	public function ajax_url_edit_monitor() {

		$this->ajax_check_permissions( 'monitors_page' );

		$this->set( 'url_saved', false );

		// load/reload monitor settings.
		$this->get_api_monitor_settings();

		$loca_profiles = MainWP_AUM_Site24x7_API::instance()->get_option( 'location_profiles' );
		if ( ! is_array( $loca_profiles ) ) {
			$loca_profiles = array();
		}
		$this->set( 'location_profiles', $loca_profiles );

		$thres_profiles = MainWP_AUM_Site24x7_API::instance()->get_option( 'threshold_profiles' );
		$items          = array();
		if ( is_array( $thres_profiles ) ) {
			foreach ( $thres_profiles as $item ) {
				$items[ $item['profile_id'] ] = $item['profile_name'];
			}
		}
		$this->set( 'threshold_profiles', $items );

		$noti_profiles = MainWP_AUM_Site24x7_API::instance()->get_option( 'notification_profiles' );
		$items         = array();
		if ( is_array( $noti_profiles ) ) {
			foreach ( $noti_profiles as $item ) {
				$items[ $item['profile_id'] ] = $item['profile_name'];
			}
		}

		$this->set( 'notification_profiles', $items );

		$user_groups = MainWP_AUM_Site24x7_API::instance()->get_option( 'user_groups' );
		$items       = array();
		if ( is_array( $user_groups ) ) {
			foreach ( $user_groups as $item ) {
				$items[ $item['user_group_id'] ] = $item['display_name'];
			}
		}
		$this->set( 'user_groups', $items );

		$monitors_groups = MainWP_AUM_Site24x7_API::instance()->get_option( 'monitors_groups' );
		if ( ! is_array( $monitors_groups ) ) {
			$monitors_groups = array();
		}
		$this->set( 'monitors_groups', $monitors_groups );

		if ( isset( $this->params['url_id'] ) && ! empty( $this->params['url_id'] ) ) {
			$editing_url_id = $this->params['url_id'];
		} elseif ( isset( $this->params['data'][ $this->service_name ]['url_id'] ) && ! empty( $this->params['data'][ $this->service_name ]['url_id'] ) ) {
			$editing_url_id = $this->params['data'][ $this->service_name ]['url_id'];
		}

		$dependency_mos = array();
		$urls           = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'default' => true,
				'fields'  => array(
					'dependent_monitors',
				),
			)
		);

		if ( ! empty( $urls ) ) {
			foreach ( $urls as $url ) {
				if ( $editing_url_id == $url->url_id ) {
					continue;
				}
				$dependency_mos[ $url->monitor_id ] = $url->url_address;
			}
		}
		$this->set( 'dependent_site247_monitors', $dependency_mos );

		if ( ! empty( $this->params ) && ! empty( $this->params['data'][ $this->service_name ] ) ) {

			$data = $this->params['data'][ $this->service_name ];

			$update = array(
				'url_name'    => $data['url_name'],
				'url_address' => $data['url_address'],
			);

			$saved  = false;
			$url_id = 0;
			if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
				$update['service'] = $this->service_name;
				$url_id            = MainWP_AUM_DB::instance()->insert_monitor_url( $update );
				$saved             = $url_id ? true : false;
			} else {
				$url_id  = $data['url_id'];
				$url_bak = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $url_id, $this->service_name );
				$saved   = MainWP_AUM_DB::instance()->update_monitor_url( $update, $url_id );
			}

			if ( $saved ) {

				$opts_value = array(
					'monitor_type'     => 1, // http type.
					'monitor_interval' => $data['check_frequency'],
					'timeout'          => $data['timeout'],
				);
				MainWP_AUM_DB::instance()->save_monitor_url_options( $url_id, $opts_value );

				try {
					$user_groupids           = ! empty( $data['user_group_ids'] ) ? explode( ',', $data['user_group_ids'] ) : array();
					$monitor_group_ids       = ! empty( $data['monitor_group_ids'] ) ? explode( ',', $data['monitor_group_ids'] ) : array();
					$dependency_resource_ids = ! empty( $data['dependent_monitors'] ) ? explode( ',', $data['dependent_monitors'] ) : array();

					$post_data = array(
						'display_name'            => trim( $data['url_name'] ),
						'website'                 => trim( $data['url_address'] ),
						'type'                    => 'URL',
						'check_frequency'         => ! empty( $data['check_frequency'] ) ? intval( $data['check_frequency'] ) : 5, // minutes.
						'timeout'                 => ! empty( $data['timeout'] ) ? intval( $data['timeout'] ) : 15, // minutes.
						'location_profile_id'     => ! empty( $data['location_profile_id'] ) ? $data['location_profile_id'] : '',
						'notification_profile_id' => ! empty( $data['notification_profile_id'] ) ? $data['notification_profile_id'] : '',
						'threshold_profile_id'    => ! empty( $data['threshold_profile_id'] ) ? $data['threshold_profile_id'] : '',
						'user_group_ids'          => $user_groupids,
						'http_method'             => ! empty( $data['http_method'] ) ? $data['http_method'] : 'G',
						'auth_user'               => ! empty( $data['http_username'] ) ? $data['http_username'] : '',
						'auth_pass'               => ! empty( $data['http_password'] ) ? $data['http_password'] : '',
						'monitor_groups'          => $monitor_group_ids,
						'dependency_resource_ids' => $dependency_resource_ids,
					);

					try {
						if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
							$result = MainWP_AUM_Site24x7_API::instance()->api_edit_monitor( $post_data );
						} else {
							$monitor_url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $data['url_id'], $this->service_name );
							$result      = MainWP_AUM_Site24x7_API::instance()->api_edit_monitor( $post_data, $monitor_url->monitor_id );
						}
						$result = json_decode( $result, true );
					} catch ( \Exception $ex ) {
						throw $ex;
					}

					if ( is_array( $result ) && isset( $result['data'] ) ) {
						$new_monitor = false;
						if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
							$this->flash( 'green', __( 'Monitor created successfully!', 'advanced-uptime-monitor-extension' ) );
							$new_monitor = true;
							do_action( 'mainwp_aum_monitor_created', $data );
						} else {
							$this->flash( 'green', __( 'Monitor updated successfully!', 'advanced-uptime-monitor-extension' ) );
						}
						$update_data = array(
							'monitor_id' => $result['data']['monitor_id'],
						);
						MainWP_AUM_DB::instance()->update_monitor_url( $update_data, $url_id );

						if ( isset( $data['dependent_monitors'] ) ) {
							$update = array(
								'dependent_monitors' => $data['dependent_monitors'],
							);
							MainWP_AUM_DB::instance()->save_monitor_url_options( $url_id, $update );
						}

						$this->set( 'url_saved', true );
					} else {
						if ( is_array( $result ) && isset( $result['error_code'] ) ) {
							$this->flash( 'red', $result['message'] );
						} else {
							$this->flash( 'red', __( 'Undefined error on the Site24x7 side. Please try again later.', 'advanced-uptime-monitor-extension' ) );
						}
						if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
							MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $url_id );
						} else {
							$update = array(
								'url_name'    => $url_bak->url_name,
								'url_address' => $url_bak->url_address,
							);
							MainWP_AUM_DB::instance()->update_monitor_url( $update, $url_id );
						}
					}
				} catch ( \Exception $ex ) {
					switch ( $ex->getCode() ) {
						case 1:
							echo esc_html( $ex->getMessage() );
							break;
						case 2:
							$this->flash( 'yellow', __( 'You should specify API key', 'advanced-uptime-monitor-extension' ) );
							break;
						case 3:
							$this->flash( 'red', __( 'Undefined error. Please, try again.', 'advanced-uptime-monitor-extension' ) );
							break;
						default:
							echo esc_html( $ex->getCode() . ': ' . $ex->getMessage() );
					}
				}
			} else {
				$this->flash( 'red', __( 'Undefined error occurred while saving the form. Please, try again.', 'advanced-uptime-monitor-extension' ) );
			}
		} else {

			$monitor_url                                 = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'], $this->service_name );
			$monitor_url                                 = json_decode( wp_json_encode( $monitor_url ), true );
			$this->params['data'][ $this->service_name ] = $monitor_url;
		}

		$this->render_view( 'site24x7_monitor' );
		die();
	}

	/**
	 * Method get_api_monitor_settings()
	 *
	 * Gets Monitor API settings.
	 *
	 * @return bool True|False.
	 */
	public function get_api_monitor_settings() {

		$opts = array();

		$loca_profiles = MainWP_AUM_Site24x7_API::instance()->get_location_profiles();
		$loca_profiles = json_decode( $loca_profiles, true );
		if ( is_array( $loca_profiles ) && isset( $loca_profiles['data'] ) ) {
			$loc_profiles = array();
			foreach ( $loca_profiles['data'] as $item ) {
				$loc_profiles[ $item['profile_id'] ] = $item['profile_name'];
			}
			$opts['location_profiles'] = $loc_profiles;
		}

		$thres_profiles = MainWP_AUM_Site24x7_API::instance()->get_threshold_profiles();
		$thres_profiles = json_decode( $thres_profiles, true );
		if ( is_array( $thres_profiles ) && isset( $thres_profiles['data'] ) ) {
			$url_types = array();
			foreach ( $thres_profiles['data'] as $item ) {
				if ( 'URL' == $item['type'] ) {
					$url_types[] = $item;
				}
			}
			$opts['threshold_profiles'] = $url_types;
		}

		$noti_profiles = MainWP_AUM_Site24x7_API::instance()->get_notification_profiles();
		$noti_profiles = json_decode( $noti_profiles, true );
		if ( is_array( $noti_profiles ) && isset( $noti_profiles['data'] ) ) {
			$opts['notification_profiles'] = $noti_profiles['data'];
		}

		$user_groups = MainWP_AUM_Site24x7_API::instance()->get_user_groups();
		$user_groups = json_decode( $user_groups, true );
		if ( is_array( $user_groups ) && isset( $user_groups['data'] ) ) {
			$opts['user_groups'] = $user_groups['data'];
		}

		$monitors_groups = MainWP_AUM_Site24x7_API::instance()->get_monitors_groups();
		$monitors_groups = json_decode( $monitors_groups, true );
		if ( is_array( $monitors_groups ) && isset( $monitors_groups['data'] ) && is_array( $monitors_groups['data'] ) ) {
			$mo_groups = array();
			foreach ( $monitors_groups['data'] as $group ) {
				$mo_groups[ $group['group_id'] ] = $group['display_name'];
			}
			$opts['monitors_groups'] = $mo_groups;
		}

		if ( ! empty( $opts ) ) {
			MainWP_AUM_Site24x7_API::instance()->update_options( $opts );
			return true;
		}

		return false;
	}


	/**
	 * Method ajax_monitor_urls()
	 *
	 * Ajax handle.
	 */
	public function ajax_monitor_urls() {
		$this->ajax_check_permissions( 'monitor_urls' );
		$this->set_view_monitors();
		$this->render_view( 'site24x7_monitor_urls' );
		die();
	}

	/**
	 * Method set_view_monitors()
	 *
	 * Sets Monitor view.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return bool True|False.
	 */
	public function set_view_monitors( $site_id = null ) {

		if ( empty( $site_id ) ) {
			$found_unav_urls = $this->check_unavailable_url_monitors();
			if ( false === $found_unav_urls ) {
				MainWP_AUM_Site24x7_Settings_Handle::get_instance()->reload_monitor_urls();
			}
		}

		global $mainwpAUMExtensionActivator;

		$conds = array();
		if ( $site_id ) {
			$website = apply_filters( 'mainwp_getsites', $mainwpAUMExtensionActivator->get_child_file(), $mainwpAUMExtensionActivator->get_child_key(), $site_id );
			if ( $website && is_array( $website ) ) {
				$website = current( $website );
			}
			if ( empty( $website ) || ! isset( $website['url'] ) ) {
				$this->flash( 'red', __( 'Site data could not be loaded. Please, try again.', 'advanced-uptime-monitor-extension' ) );
				return false;
			}
			$conds['url_address'] = $website['url'];
		}

		$aum_code = MainWP_AUM_Site24x7_API::instance()->init_settings();

		if ( empty( $aum_code['client_id'] ) || empty( $aum_code['client_secret'] ) || empty( $aum_code['code'] ) ) {
			$this->flash( 'yellow', __( 'Please enter your Site24x7 Client ID and Client Secret and Authenticate first.', 'advanced-uptime-monitor-extension' ) );
			return false;
		}

		$get_page = isset( $_REQUEST['get_page'] ) && $_REQUEST['get_page'] > 0 ? $_REQUEST['get_page'] : 1;

		$total = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'conds'      => $conds,
				'count_only' => true,
			)
		);

		if ( 0 == $total && $site_id ) {
			// try to get monitor with url like site url.
			$conds = array( 'url_address_like' => $website['url'] );
			$total = MainWP_AUM_DB::instance()->get_monitor_urls(
				$this->service_name,
				array(
					'conds'      => $conds,
					'count_only' => true,
				)
			);
		}

		// to use datatable js.
		if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
			$per_page = get_option( 'mainwp_aum_setting_monitors_per_page', 10 );
		} else {
			$per_page = MAINWP_MONITOR_API_LIMIT_PER_PAGE;
		}

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'conds'           => $conds,
				'per_page'        => $per_page,
				'page'            => $get_page,
				'with_properties' => true,
			)
		);

		$monitor_urls = array();
		$url_ids      = array();

		if ( $total > 0 ) {
			foreach ( $urls as $url ) {
				$url_ids[ $url->monitor_id ]      = $url->url_id;
				$monitor_urls[ $url->monitor_id ] = $url;
				if ( count( $url_ids ) >= MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
					break;
				}
			}

			$err            = '';
			$loaded_success = false;
			try {
				$loaded_success = $this->update_monitors_status( $url_ids );
			} catch ( \Exception $ex ) {
				$err = $ex->getMessage();
			}

			if ( ! $loaded_success ) {
				$this->flash( 'red', __( 'Statitics data could not be found or empty. Please, try again later.', 'advanced-uptime-monitor-extension' ) );
			}

			if ( ! empty( $err ) ) {
				$this->flash( 'red', $err );
			}
		}

		$this->set( 'get_page', $get_page );
		$this->set( 'total', $total );
		$this->set( 'urls', $monitor_urls );
		return true;
	}

	/**
	 * Method get_report_today()
	 *
	 * Gets today report.
	 *
	 * @param array $monitor_urls Monitor URLs.
	 */
	public function get_report_today( &$monitor_urls ) {
		$today     = gmdate( 'Y-m-d' );
		$max_count = 3;
		$get_count = 0;
		foreach ( $monitor_urls as $mo_id => $url ) {
			$reports_today  = property_exists( $url, 'reports_today' ) ? json_decode( $url->reports_today, true ) : array();
			$reports_values = is_array( $reports_today ) && isset( $reports_today[ $today ] ) ? $reports_today[ $today ] : array();

			if ( true || ( empty( $reports_values ) && $get_count < $max_count ) ) { // max get per time.
				$result = MainWP_AUM_Site24x7_API::instance()->get_log_reports( $url->monitor_id, $today );
				$result = json_decode( $result, true );

				if ( is_array( $result['data'] ) && isset( $result['data'] ) && isset( $result['data']['report'] ) ) {
					if ( isset( $result['data']['monitor_id'] ) ) {
						if ( isset( $result['data']['report'][0] ) ) {
							$reports_values = $result['data']['report'][0];
						} else {
							$reports_values = $result['data']['report'];
						}
						$get_count++;
						$opts_value = array(
							'reports_today' => wp_json_encode( array( $today => $reports_values ) ),
						);
						MainWP_AUM_DB::instance()->save_monitor_url_options( $monitor_url->url_id, $opts_value );
					}
				}
			}
			$monitor_urls[ $mo_id ]->reports_today = $reports_values;
		}
	}

	/**
	 * Method check_unavailable_url_monitors()
	 *
	 * Checks for unavaialble monitors.
	 *
	 * @return bool True|False.
	 */
	public function check_unavailable_url_monitors() {

		$url_monitors_ids = array();

		$monitors = MainWP_AUM_Site24x7_Settings_Handle::get_instance()->get_api_monitors(); // to check unavailable urls.

		if ( false === $monitors ) {
			$this->flash( 'red', __( 'Unable to load Site24x7 monitor data. Please, try again later.', 'advanced-uptime-monitor-extension' ) );
			return -1;
		}

		if ( is_array( $monitors ) ) {
			foreach ( $monitors as $url_monitor ) {
				$url_monitors_ids[] = $url_monitor['monitor_id'];
			}
		}

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls( $this->service_name );

		$unavailable_urls = array();
		foreach ( $urls as $url ) {
			if ( ! in_array( $url->monitor_id, $url_monitors_ids ) ) {
				$unavailable_urls[] = $url->url_name;
				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $url->url_id );
				MainWP_AUM_DB_Events_Site24x7::instance()->delete_events( $url->url_id );
			}
		}

		if ( ! empty( $unavailable_urls ) ) {
			$this->flash( 'yellow', __( 'Following monitors could not be found in your Uptime Robot dashboard so they have been deleted:<br/>', 'advanced-uptime-monitor-extension' ) . implode( ',<br/>', $unavailable_urls ) );
			return true;
		}
		return false;
	}

	/**
	 * Method ajax_statistics_table()
	 *
	 * Gets stats table.
	 */
	public function ajax_statistics_table() {
		$this->ajax_check_permissions( 'monitors_page' );

		$url_id = $this->params['url_id'];

		$url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $url_id );
		if ( ! $url ) {
			exit;
		}

		$stats_contditions = array( 'url_id' => $url_id );

		$stats = MainWP_AUM_DB_Events_Site24x7::instance()->get_cond_events(
			array(
				'conds'      => $stats_contditions,
				'order'      => 'last_polled_datetime_gmt DESC',
				'page'       => isset( $this->params['stats_page'] ) && (int) $this->params['stats_page'] > 0 ? $this->params['stats_page'] : 1,
				'per_page'   => 10,
			)
		);

		$last_existing_event      = MainWP_AUM_DB_Events_Site24x7::instance()->get_last_events( $url_id );
		$unix_gmt_time_last_event = 0;
		if ( ! empty( $last_existing_event ) ) {
			$unix_gmt_time_last_event = strtotime( $last_existing_event->last_polled_datetime_gmt );
		}

		$cnt = MainWP_AUM_DB_Events_Site24x7::instance()->get_cond_events(
			array(
				'conds'      => $stats_contditions,
				'count_only' => true,
			)
		);

		$monitor_statuses = self::get_monitor_status();
		$this->set( 'stats_cnt', $cnt );
		$this->set( 'stats_page', isset( $this->params['stats_page'] ) ? $this->params['stats_page'] : 1 );
		$this->set( 'url', $url );
		$this->set( 'monitor_statuses', $monitor_statuses );
		$this->set( 'unixtime_last_event', $unix_gmt_time_last_event );
		$this->set( 'stats', $stats );
		$this->render_view( 'site24x7_statistics_table' );
		die();
	}

	/**
	 * Method ajax_meta_box()
	 *
	 * Loads metabox fontent via AJAX.
	 */
	public function ajax_meta_box() {
		$this->ajax_check_permissions( 'meta_box' );

		if ( isset( $_POST['site_id'] ) && $_POST['site_id'] ) {
			$site_id = intval( $_POST['site_id'] );
		} else {
			$site_id = null;
		}

		$this->set_view_monitors( $site_id );
		$this->render_view( 'site24x7_meta_box' );
		die();
	}

	/**
	 * Method render_recent_events()
	 *
	 * Renders recent events.
	 */
	public function render_recent_events() {

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'with_properties' => true,
			)
		);

		$urls_info = array();
		$url_ids   = array();

		if ( ! empty( $urls ) ) {
			foreach ( $urls as $url ) {
				if ( ! isset( $urls_info[ $url->url_id ] ) ) {
					$urls_info[ $url->url_id ] = array(
						'url_address' => $url->url_address,
						'url_name'    => $url->url_name,
					);
				}
				$url_ids[ $url->monitor_id ] = $url->url_id;
			}
		}

		$err            = '';
		$loaded_success = false;
		try {
			$loaded_success = $this->update_monitors_status( $url_ids );
		} catch ( \Exception $ex ) {
			$err = $ex->getMessage();
		}

		if ( ! $loaded_success ) {
			$this->flash( 'red', __( 'Statitics data could not be found or empty. Please, try again later.', 'advanced-uptime-robot-extension' ) );
		}

		if ( ! empty( $err ) ) {
			$this->flash( 'red', $err );
		}

		$order_by = 'last_polled_datetime_gmt DESC';
		$limit    = 100;
		$stats    = MainWP_AUM_DB_Events_Site24x7::instance()->get_events( '', $limit, $order_by );
		$this->set( 'urls_info', $urls_info );
		$this->set( 'stats', $stats );
		$this->render_view( 'site24x7_recent_events' );
	}

	/**
	 * Method update_monitors_status()
	 *
	 * Updates monitor status.
	 *
	 * @param array           $url_ids List of URL IDs.
	 *
	 * @param bool True|False.
	 */
	public function update_monitors_status( $url_ids ) {

		$result = MainWP_AUM_Site24x7_API::instance()->get_monitors_status();

		$result = json_decode( $result, true );

		if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['monitors'] ) ) {
			$monitors_status = $result['data']['monitors'];
			foreach ( $monitors_status as $mo_status ) {
				if ( ! isset( $url_ids[ $mo_status['monitor_id'] ] ) ) {
					continue;
				}
				$url_id            = $url_ids[ $mo_status['monitor_id'] ];
				$unix_gmt_time_log = strtotime( $mo_status['last_polled_time'] );
				$data              = array(
					'url_id'                   => $url_id,
					'status'                   => $mo_status['status'],
					'response_time'            => isset( $mo_status['attribute_value'] ) ? $mo_status['attribute_value'] : 0,
					'last_polled_datetime_gmt' => gmdate( 'Y-m-d H:i:s', $unix_gmt_time_log ),
					'duration'                 => isset( $mo_status['duration'] ) ? $mo_status['duration'] : 0,
					'reason'                   => isset( $mo_status['down_reason'] ) ? $mo_status['down_reason'] : '',
				);
				MainWP_AUM_DB_Events_Site24x7::instance()->insert_event( $data );
				$update = array(
					'response_time'            => $data['response_time'],
					'last_polled_datetime_gmt' => $data['last_polled_datetime_gmt'],
					'status'                   => $data['status'],
				);
				MainWP_AUM_DB::instance()->save_monitor_url_options( $url_id, $update );
			}
			return true;
		} elseif ( isset( $result['message'] ) ) {
			throw new \Exception( $result['message'] );
		}
		return false;
	}

	/**
	 * Method ajax_add_monitor_for_site()
	 *
	 * Creates monior for a site via AJAX request.
	 *
	 * @param object $site             Site object.
	 * @param array  $data             Data array.
	 * @param int    $just_inserted_id Site ID.
	 *
	 * @return array $information Information array.
	 */
	public function ajax_add_monitor_for_site( $site, $data, $just_inserted_id ) {
		$information = array();

		$post_data = array(
			'display_name'            => $site->url,
			'website'                 => $site->url,
			'type'                    => 'URL',
			'check_frequency'         => 5, // minutes.
			'timeout'                 => 15, // minutes.
			'location_profile_id'     => $this->get_default_api_monitor_value( 'location_profiles' ),
			'notification_profile_id' => $this->get_default_api_monitor_value( 'notification_profiles' ),
			'threshold_profile_id'    => $this->get_default_api_monitor_value( 'threshold_profiles' ),
			'user_group_ids'          => $this->get_default_api_monitor_value( 'user_groups' ),
			'http_method'             => 'G',
			'auth_user'               => $site->http_user,
			'auth_pass'               => $site->http_pass,
		);

		try {
			$result = MainWP_AUM_Site24x7_API::instance()->api_edit_monitor( $post_data ); // add new monitor.
			$result = json_decode( $result, true );
		} catch ( \Exception $ex ) {
			throw $ex;
		}

		if ( is_array( $result ) && isset( $result['data'] ) ) {
			$this->flash( 'green', __( 'Monitor created successfully!', 'advanced-uptime-robot-extension' ) );
			$new_monitor = true;
			do_action( 'mainwp_aum_monitor_created', $data );
			$uptime_monitor_id = $result['data']['monitor_id'];

			if ( ! empty( $uptime_monitor_id ) ) {
				$data = array(
					'monitor_id' => $uptime_monitor_id,
					'dashboard'  => 1, // new monitor, set display on dashboard as default.
				);
				MainWP_AUM_DB::instance()->update_monitor_url( $data, $just_inserted_id );
				$user_groupids = implode( ',', $post_data['user_group_ids'] );
				$opts_value    = array(
					'monitor_type'            => 'URL', // http type.
					'monitor_interval'        => 5,
					'timeout'                 => 15,
					'http_username'           => $site->http_user,
					'http_password'           => $site->http_pass,
					'location_profile_id'     => $post_data['location_profile_id'],
					'notification_profile_id' => $post_data['notification_profile_id'],
					'threshold_profile_id'    => $post_data['threshold_profile_id'],
					'user_group_ids'          => $user_groupids,
					'http_method'             => $post_data['http_method'],
				);
				MainWP_AUM_DB::instance()->save_monitor_url_options( $just_inserted_id, $opts_value );
				$this->set( 'url_saved', true );
				$information['result'] = 'success';
			} else {
				$information['error'] = __( 'Creating monitor failed!', 'advanced-uptime-monitor-extension' );
				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $just_inserted_id );
			}
		} else {
			if ( is_array( $result ) && isset( $result['error_code'] ) ) {
				$err = 'Error: ' . $result['message'];
				$this->flash( 'red', $err );
			} else {
				$err = __( 'Undefined error on the Site24x7 side. Please try again later.', 'advanced-uptime-monitor-extension' );
				$this->flash( 'red', $err );
			}
			MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $just_inserted_id );
			$information['error'] = $err;
		}
		return $information;
	}

	/**
	 * Method get_default_api_monitor_value()
	 *
	 * Gets default API monitor values.
	 *
	 * @param string $field Firld type.
	 *
	 * @return string Default value.
	 */
	public function get_default_api_monitor_value( $field ) {
		if ( 'user_groups' == $field ) {
			$user_groups = MainWP_AUM_Site24x7_API::instance()->get_option( 'user_groups' );
			if ( is_array( $user_groups ) ) {
				foreach ( $user_groups as $val ) {
					if ( $val['is_master_group'] ) {
						return array( $val['user_group_id'] );
					}
				}
			}
		} elseif ( 'location_profiles' == $field ) {
			$loca_profiles = MainWP_AUM_Site24x7_API::instance()->get_option( 'location_profiles' );
			if ( is_array( $loca_profiles ) ) {
				foreach ( $loca_profiles as $loc_id => $val ) {
					if ( stripos( $val, 'World' ) !== false ) { // default: World wide Locations.
						return $loc_id;
					}
				}
			}
		} elseif ( 'threshold_profiles' == $field ) {
			$thres_profiles = MainWP_AUM_Site24x7_API::instance()->get_option( 'threshold_profiles' );
			if ( is_array( $thres_profiles ) ) {
				foreach ( $thres_profiles as $val ) {
					if ( stripos( $val['profile_name'], 'Default' ) !== false ) {
						return $val['profile_id'];
					}
				}
			}
		} elseif ( 'notification_profiles' == $field ) {
			$noti_profiles = MainWP_AUM_Site24x7_API::instance()->get_option( 'notification_profiles' );
			if ( is_array( $noti_profiles ) ) {
				foreach ( $noti_profiles as $val ) {
					if ( stripos( $val['profile_name'], 'Default' ) !== false ) {
						return $val['profile_id'];
					}
				}
			}
		}
		return '';
	}

	/**
	 * Method aum_get_data()
	 *
	 * Get uptime status.
	 *
	 * @param object $monitor_address Monitor object.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return mixed array|false $return AUM data.
	 */
	public function aum_get_data( $monitor_address, $start_date = null, $end_date = null ) {

		$period_constants = array(
			0  => 0, // Custom Period, last 365 days.
			7  => 2, // Last 7 Days.
			15 => 50, // Custom Period.
			30 => 5, // Last 30 Days.
			45 => 0, // Custom Period.
			60 => 0, // Custom Period.
		);

		$uptimes = array(
			0  => 0,
			7  => 0,
			15 => 0,
			30 => 0,
			45 => 0,
			60 => 0,
		);

		foreach ( $period_constants as $d => $val ) {
			$params = array( 'period' => $val );

			if ( 15 == $d || 45 == $d || 60 == $d ) {
				$params['start_date'] = date( 'Y-m-d' );
				$params['end_date']   = date( 'Y-m-d', time() - $d * DAY_IN_SECONDS );
			} elseif ( 0 == $d ) {
				$params['start_date'] = date( 'Y-m-d' );
				$params['end_date']   = date( 'Y-m-d', time() - 365 * DAY_IN_SECONDS ); // last 365 days.
			}

			$result = MainWP_AUM_Site24x7_API::instance()->get_uptime( $monitor_address->monitor_id, $params );
			$result = json_decode( $result, true );
			if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['summary_details'] ) ) {
				$uptimes[ $d ] = $result['data']['summary_details']['availability_percentage'];
			}
		}

		$return['aum.alltimeuptimeratio'] = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[0] );
		$return['aum.uptime7']            = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[7] );
		$return['aum.uptime15']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[15] );
		$return['aum.uptime30']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[30] );
		$return['aum.uptime45']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[45] );
		$return['aum.uptime60']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[60] );

		$order_by = 'last_polled_datetime_gmt DESC';
		$stats    = MainWP_AUM_DB_Events_Site24x7::instance()->get_events( 'last_polled_datetime_gmt >= STR_TO_DATE("' . gmdate( 'Y-m-d H:i:s', $start_date ) . '", "%Y-%m-%d %H:%i:%s") AND last_polled_datetime_gmt <= STR_TO_DATE("' . date( 'Y-m-d H:i:s', $end_date ) . '", "%Y-%m-%d %H:%i:%s") AND url_id = ' . $monitor_address->url_id, false, $order_by );

		ob_start();
		?>
			<table class="ui single line table">
				<thead>
					<tr>
					<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'URL', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'State', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
					<td><?php echo esc_html( $monitor_address->url_name ); ?></td>
					<td><a href="<?php echo $monitor_address->url_address; ?>" target="_blank"><?php echo $monitor_address->url_address; ?></a></td>
					<td><?php echo self::get_monitor_types( $monitor_address->monitor_type ); ?></td>
					<td><?php echo self::get_site24x7_monitor_state( $monitor_address ); ?></td>
				<td><?php echo self::render_monitor_status( $monitor_address->status ); ?></td>
					</tr>
					</tbody>
				</table>
				<?php if ( ! empty( $stats ) ) { ?>
					<table class="ui single line table">
					<thead>
						<tr>
						<th class="collapsing"><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
						<th><?php _e( 'Performance', 'advanced-uptime-monitor-extension' ); ?></th>
						<th><?php _e( 'Last Polled', 'advanced-uptime-monitor-extension' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $stats as $event ) {
							?>
							<tr>
							<td><?php echo self::render_monitor_status( $event->status ); ?></td>
							<td><?php echo intval( $event->response_time ) . ' ms'; ?></td>
							<td>
							<?php
									$datetime = strtotime( $event->last_polled_datetime_gmt );
									echo human_time_diff( $datetime ) . ' ' . __( 'ago', 'advanced-uptime-monitor-extension' );
							?>
							</td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
					<?php
				}
				$stats_html          = ob_get_clean();
				$stats_html          = preg_replace( "/\r|\n/", '', $stats_html );
				$return['aum.stats'] = $stats_html;

				return $return;
	}


}
