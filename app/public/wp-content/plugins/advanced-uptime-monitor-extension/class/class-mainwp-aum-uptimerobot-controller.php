<?php
/**
 * MainWP Uptime Robot Controller
 *
 * Controls the Uptime Robot actions.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_UptimeRobot_Controller
 *
 * Uptiem Robot controller.
 */
class MainWP_AUM_UptimeRobot_Controller extends MainWP_AUM_Controller {

	/**
	 * Sevice name.
	 *
	 * @var $service_name.
	 */
	public $service_name = 'uptimerobot';

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_UptimeRobot_Controller.
	 */
	private static $instance = null;

	/**
	 * Monitor ports.
	 *
	 * @var $monitor_ports.
	 */
	public $monitor_ports = array(
		'1' => '80',
		'2' => '443',
		'3' => '21',
		'4' => '25',
		'5' => '110',
		'6' => '143',
	);

	/**
	 * Method instance()
	 *
	 * Get instance.
	 *
	 * @return object Instance.
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
	 * Method admin_init()
	 *
	 * Admin init.
	 */
	public function admin_init() {
		add_action( 'wp_ajax_mainwp_advanced_uptime_display_dashboard', array( $this, 'ajax_display_dashboard' ) );
		add_action( 'wp_ajax_mainwp_advanced_uptime_update_url', array( $this, 'ajax_update_url' ) );
	}


	/**
	 * Method ajax_meta_box()
	 *
	 * Ajax handle.
	 */
	public function ajax_meta_box() {
		$this->ajax_check_permissions( 'meta_box' );

		if ( isset( $_POST['site_id'] ) && $_POST['site_id'] ) {
			$site_id = intval( $_POST['site_id'] );
		} else {
			$site_id = null;
		}

		$this->set_view_monitors( $site_id );
		$this->render_view( 'uptimerobot_meta_box' );
		die();
	}

	/**
	 * Method ajax_monitor_urls()
	 *
	 * Ajax handle.
	 */
	public function ajax_monitor_urls() {
		$this->ajax_check_permissions( 'monitor_urls' );
		$this->set_view_monitors();
		$this->render_view( 'uptimerobot_monitor_urls' );
		die();
	}

	/**
	 * Method: set_view_monitors()
	 *
	 * Sets Monitor view.
	 *
	 * @param int $site_id Child site ID.
	 */
	public function set_view_monitors( $site_id = null ) {

		global $mainwpAUMExtensionActivator;

		$conds = array();
		if ( $site_id ) {
			$website = apply_filters( 'mainwp_getsites', $mainwpAUMExtensionActivator->get_child_file(), $mainwpAUMExtensionActivator->get_child_key(), $site_id );
			if ( $website && is_array( $website ) ) {
				$website = current( $website );
			}
			if ( empty( $website ) || ! isset( $website['url'] ) ) {
				$this->flash( 'red', __( 'Site data could not be loaded. Please try again.', 'advanced-uptime-monitor-extension' ) );
				return false;
			}
			// $conds['url_address']      = $website['url']; // pass string value only.
			$conds['url_address_like'] = $website['url']; // to get LIKE url.
		}

		$aum_api_key = MainWP_AUM_UptimeRobot_API::instance()->get_api_key();

		if ( empty( $aum_api_key ) ) {
			$this->flash( 'yellow', __( 'Please enter your Uptime Robot API Key and try again.', 'advanced-uptime-monitor-extension' ) );
			return false;
		}

		$off = MainWP_AUM_UptimeRobot_API::instance()->get_ur_gmt_offset_time();
		if ( false !== $off ) {
			$gmt_offset = $off['offset_time'];
		} else {
			$gmt_offset = 0;
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
				'conds'    => $conds,
				'per_page' => $per_page,
				'page'     => $get_page,
			)
		);

		$monitor_urls = array();
		$url_ids      = array();
		$stats        = array();
		if ( $total > 0 ) {
			foreach ( $urls as $url ) {
				if ( empty( $url->monitor_id ) ) {
					continue;
				}

				if ( in_array( $url->monitor_id, $url_ids ) ) {
					continue;
				}

				$url_ids[ $url->monitor_id ] = $url->url_id;

				$monitor_urls[ $url->url_id ] = $url;
				if ( count( $url_ids ) >= MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
					break;
				}
			}

			$mo_ids = array_keys( $url_ids );
			// statistics.
			$result = MainWP_AUM_UptimeRobot_Settings_Handle::instance()->get_uptime_monitors( $aum_api_key, $mo_ids, 1, $alert  = 0, $all_ratio = 1, '1-7-15-30-45-60' ); // to set view uptime monitors.

			if ( false === $result ) {
				$this->flash( 'red', __( 'Statitics data could not be found. Please, try again later.', 'advanced-uptime-monitor-extension' ) );
			} else {
				$time_limit            = 3600 * 24;
				$current_unix_gmt_time = time();
				$unix_gmt_time_start   = $current_unix_gmt_time - $time_limit;
				if ( is_array( $result->monitors ) && count( $result->monitors ) > 0 ) {
					foreach ( $result->monitors as $monitor ) {
						if ( ! isset( $url_ids[ $monitor->id ] ) ) {
							continue;
						}

						$url_id = $url_ids[ $monitor->id ];

						// getting last event recorded to database.
						$last_existing_event      = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_last_events( $url_id );
						$unix_gmt_time_last_event = 0;
						if ( ! empty( $last_existing_event ) ) {
							$unix_gmt_time_last_event = strtotime( $last_existing_event->event_datetime_gmt );
						}

						$monitor_urls[ $url_id ]->alltimeuptimeratio = $monitor->all_time_uptime_ratio;

						$update = array(
							'status'             => $monitor->status,
							'alltimeuptimeratio' => $monitor->all_time_uptime_ratio,
							'customuptimeratio'  => $monitor->custom_uptime_ratio,
						);
						MainWP_AUM_DB::instance()->save_monitor_url_options( $url_id, $update );

						// searching for the last event before happend befor last 24 hours.
						if ( is_array( $monitor->logs ) ) {
							foreach ( $monitor->logs as $index => $log ) {
								$unix_gmt_time_log = $log->datetime;
								// storing event to database.
								if ( $unix_gmt_time_log > $unix_gmt_time_last_event ) {
									$data = array(
										'url_id'   => $url_id,
										'type'     => $log->type,
										'event_datetime_gmt' => gmdate( 'Y-m-d H:i:s', $unix_gmt_time_log ),
										'duration' => $log->duration,
										'code'     => $log->reason->code,
										'detail'   => $log->reason->detail,
									);
									MainWP_AUM_DB_Events_UptimeRobot::instance()->insert_event( $data );
								} elseif ( $unix_gmt_time_last_event == $unix_gmt_time_log ) {
									MainWP_AUM_DB_Events_UptimeRobot::instance()->update_event( $last_existing_event->event_id, array( 'duration' => $log->duration ) );
								}
							}
						}

						$start_log = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_last_events( $url_id, $unix_gmt_time_start );

						if ( ! isset( $stats[ $url_id ] ) ) {
							$stats[ $url_id ]    = array();
							$stats[ $url_id ][0] = new \stdClass();
						}

						if ( ! is_object( $stats[ $url_id ][0] ) ) {
							$stats[ $url_id ][0] = new \stdClass();
						}

						// add start event.
						if ( ! empty( $start_log ) ) {
							$stats[ $url_id ][0]->type = $start_log->type;
						} else {
							$stats[ $url_id ][0]->type = 0; // data not available.
						}
						$stats[ $url_id ][0]->event_datetime_gmt = date( 'Y-m-d H:i:s', $unix_gmt_time_start );
						$stats[ $url_id ][0]->status_bar_length  = 0;
						$stats[ $url_id ][0]->monitor_id         = $monitor->id;
					}
				}

				if ( 0 == count( $url_ids ) ) {
					$url_ids = array( 0 ); // to fix bug.
				}

				$include_url_ids = array_values( $url_ids );

				// getting stats from db, for simplified sorting.
				$events_from_db = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_events( 'event_datetime_gmt >= "' . date( 'Y-m-d H:i:s', $unix_gmt_time_start ) . '" AND url_id IN (' . implode( ',', $include_url_ids ) . ')' );

				foreach ( $events_from_db as $event ) {
					if ( empty( $event ) || empty( $event->url_id ) || empty( $stats[ $event->url_id ] ) ) {
						continue;
					}
					$count      = count( $stats[ $event->url_id ] );
					$time_value = strtotime( $event->event_datetime_gmt ) - strtotime( $stats[ $event->url_id ][ $count - 1 ]->event_datetime_gmt );

					$stats[ $event->url_id ][ $count ] = (object) array(
						'type'               => $event->type,
						'event_datetime_gmt' => $event->event_datetime_gmt,
						'status_bar_length'  => $time_value,
						'url_id'             => $event->url_id,
						'monitor_id'         => $stats[ $event->url_id ][ $count - 1 ]->monitor_id,
					);
				}
			}
		}
		// to add more last event to fix.
		foreach ( $stats as $url_id => $mo_stats ) {
			$count_event                      = count( $mo_stats );
			$stats[ $url_id ][ $count_event ] = new \stdClass();
			$stats[ $url_id ][ $count_event ]->status_bar_length  = $count_event > 0 ? ( $current_unix_gmt_time - strtotime( $stats[ $url_id ][ $count_event - 1 ]->event_datetime_gmt ) ) : 0;
			$stats[ $url_id ][ $count_event ]->event_datetime_gmt = date( 'Y-m-d H:i:s', $current_unix_gmt_time );
			$stats[ $url_id ][ $count_event ]->type               = $count_event > 0 ? ( $stats[ $url_id ][ $count_event - 1 ]->type ) : 0;
			$stats[ $url_id ][ $count_event ]->monitor_id         = $count_event > 0 ? ( $stats[ $url_id ][ $count_event - 1 ]->monitor_id ) : 0;
		}

		// re-calculating status bar values.
		foreach ( $stats as $url_id => $mo_stats ) {
			$total_length = 0;
			foreach ( $mo_stats as $sta_id => $sta_val ) {
				$total_length += $sta_val->status_bar_length;
			}

			$max_length = 0;
			$max_sta_id = 0;

			foreach ( $mo_stats as $sta_id => $sta_val ) {
				if ( $total_length > 0 ) {
					$bar_length = number_format( $sta_val->status_bar_length * 100 / $total_length, 2 );

					$stats[ $url_id ][ $sta_id ]->status_bar_length = $bar_length;
					if ( $bar_length > $max_length ) {
						$max_length = $bar_length;
						$max_sta_id = $sta_id;
					}
				} else {
					$stats[ $url_id ][ $sta_id ]->status_bar_length = 0;
				}
			}
			// to fix layout.
			if ( $max_sta_id ) {
				$stats[ $url_id ][ $max_sta_id ]->status_bar_length -= 1;
			}
		}

		// statistics end.
		$this->set( 'stats', $stats );
		$this->set( 'get_page', $get_page );
		$this->set( 'total', $total );
		$this->set( 'urls', $monitor_urls );
		$this->set( 'log_gmt_offset', $gmt_offset );
		return true;
	}


	/**
	 * Method: show()
	 *
	 * Sets object.
	 */
	public function show() {
		$this->set_object();
	}

	/**
	 * Method: add()
	 *
	 * Adds monitor.
	 */
	public function add() {
		$this->set_object();
		if ( ! empty( $this->params ) && ! empty( $this->params['data']['UptimeMonitor'] ) ) {
			$data = $this->params['data']['UptimeMonitor'];
			if ( MainWP_AUM_DB::instance()->insert_monitor_url( $data ) ) {
				$this->flash( 'green', __( 'Monitor has been created successfully.', 'advanced-uptime-monitor-extension' ) );
			}
		}
	}

	/**
	 * Method: ajax_url_edit_monitor()
	 *
	 * Edits monitors via AJAX request.
	 */
	public function ajax_url_edit_monitor() {
		$this->ajax_check_permissions( 'monitors_page' );
		$this->set( 'url_saved', false );

		if ( ! empty( $this->params ) && ! empty( $this->params['data'][ $this->service_name ] ) ) {
			// to fix bug display friend name after create new monitor.
			if ( ! isset( $this->params['checkbox_show_select'] ) ) {
				$this->params['data'][ $this->service_name ]['url_name'] = $this->params['url_name_textbox'];
			}

			$aum_api_key      = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'api_key' );
			$just_inserted_id = 0;
			$url_saved        = false;
			if ( ! empty( $aum_api_key ) ) {
				if ( ! isset( $this->params['data'][ $this->service_name ]['url_id'] ) || empty( $this->params['data'][ $this->service_name ]['url_id'] ) ) {
					$data                   = $this->params['data'][ $this->service_name ];
					$update_data            = array(
						'url_name'    => $data['url_name'],
						'url_address' => $data['url_address'],
						'service'     => $this->service_name,
					);
					$just_insertedupdate_id = MainWP_AUM_DB::instance()->insert_monitor_url( $update_data );
					$url_saved              = $just_insertedupdate_id ? true : false;
				} else {
					$just_insertedupdate_id = $this->params['data'][ $this->service_name ]['url_id'];
					$url_bak                = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $just_insertedupdate_id );
					$data                   = $this->params['data'][ $this->service_name ];

					$update_data = array(
						'url_name'    => $data['url_name'],
						'url_address' => $data['url_address'],
					);
					$url_saved   = MainWP_AUM_DB::instance()->update_monitor_url( $update_data, $just_insertedupdate_id );
				}
			}

			if ( $url_saved ) {
				try {
					$post_data = array(
						'name'             => trim( $this->params['data'][ $this->service_name ]['url_name'] ),
						'uri'              => trim( $this->params['data'][ $this->service_name ]['url_address'] ),
						'monitor_interval' => ! empty( $this->params['data'][ $this->service_name ]['monitor_interval'] ) ? intval( $this->params['data'][ $this->service_name ]['monitor_interval'] ) : 5, // minutes
						'http_username'    => ! empty( $this->params['data'][ $this->service_name ]['http_username'] ) ? $this->params['data'][ $this->service_name ]['http_username'] : '',
						'http_password'    => ! empty( $this->params['data'][ $this->service_name ]['http_password'] ) ? $this->params['data'][ $this->service_name ]['http_password'] : '',
					);

					if ( isset( $this->params['data'][ $this->service_name ]['monitor_type'] ) ) {
						$post_data['type'] = $this->params['data'][ $this->service_name ]['monitor_type'];
					}

					if ( ! empty( $this->params['monitor_contacts_notification'] ) ) {
						$post_data['monitorAlertContacts'] = $this->params['monitor_contacts_notification'];
					}
					if ( isset( $post_data['type'] ) && 2 == $post_data['type'] && isset( $this->params['data'][ $this->service_name ]['monitor_keywordtype'] ) ) {
						$post_data['keyword_type']  = $this->params['data'][ $this->service_name ]['monitor_keywordtype'];
						$post_data['keyword_value'] = $this->params['data'][ $this->service_name ]['monitor_keywordvalue'];
					}

					if ( isset( $post_data['type'] ) && 4 == $post_data['type'] && isset( $this->params['data'][ $this->service_name ]['monitor_subtype'] ) ) {
						$post_data['subtype'] = $this->params['data'][ $this->service_name ]['monitor_subtype'];
						$post_data['port']    = $this->monitor_ports[ $this->params['data'][ $this->service_name ]['monitor_subtype'] ];
					}

					try {
						if ( ! isset( $this->params['data'][ $this->service_name ]['url_id'] ) || empty( $this->params['data'][ $this->service_name ]['url_id'] ) ) {
							$result = MainWP_AUM_UptimeRobot_API::instance()->new_monitor( $post_data );
						} else {
							$url    = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['data'][ $this->service_name ]['url_id'] );
							$result = MainWP_AUM_UptimeRobot_API::instance()->api_edit_monitor( $url->monitor_id, $post_data );
						}
						$result = json_decode( $result );
					} catch ( \Exception $ex ) {
						throw $ex;
					}

					if ( 'ok' == $result->stat ) {
						$new_monitor = false;
						if ( ! isset( $this->params['data'][ $this->service_name ]['url_id'] ) || empty( $this->params['data'][ $this->service_name ]['url_id'] ) ) {
							$this->flash( 'green', __( 'Monitor created successfully!', 'advanced-uptime-monitor-extension' ) );
							$new_monitor = true;
							do_action( 'mainwp_aum_monitor_created', $this->params['data'][ $this->service_name ] );
						} else {
							$this->flash( 'green', __( 'Monitor updated successfully!', 'advanced-uptime-monitor-extension' ) );
						}

						$uptime_monitor_id = $result->monitor->id;

						$post_data = $this->params['data'][ $this->service_name ];

						$data = array(
							'url_name' => $post_data['url_name'],
						);

						if ( $new_monitor ) {
							$data['dashboard']  = 1; // new monitor, set display on dashboard as default.
							$data['monitor_id'] = $uptime_monitor_id;
						}

						MainWP_AUM_DB::instance()->update_monitor_url( $data, $just_insertedupdate_id );

						$url_options = array(
							'monitor_interval' => $post_data['monitor_interval'],
							'http_username'    => $post_data['http_username'],
							'http_password'    => $post_data['http_password'],
						);
						MainWP_AUM_DB::instance()->save_monitor_url_options( $just_insertedupdate_id, $url_options );

						$this->set( 'url_saved', true );

					} else {
						if ( $result ) {
							$this->flash( 'red', $result->error->message );
						} else {
							$this->flash( 'red', __( 'Undefined error on the Uptime Robot side. Please, try again later.', 'advanced-uptime-monitor-extension' ) );
						}
						if ( ! isset( $this->params['data'][ $this->service_name ]['url_id'] ) || empty( $this->params['data'][ $this->service_name ]['url_id'] ) ) {
							MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $just_inserted_id );
						} else {
							$url_bak = (array) $url_bak;
							foreach ( $url_bak as $field => $value ) {
								if ( false === strpos( $field, 'url_' ) ) {
									unset( $url_bak[ $field ] );
								}
							}

							$url_id = $url_bak['url_id'];
							$data   = $url_bak;
							MainWP_AUM_DB::instance()->update_monitor_url( $data, $url_id );
						}
					}
				} catch ( \Exception $ex ) {
					switch ( $ex->getCode() ) {
						case 1:
							echo esc_html( $ex->getMessage() );
							break;
						case 2:
							$this->flash( 'yellow', __( 'Please, specify the API key.', 'advanced-uptime-monitor-extension' ) );
							break;
						case 3:
							$this->flash( 'red', __( 'Undefined error occurred, please try again.', 'advanced-uptime-monitor-extension' ) );
							break;
						default:
							echo esc_html( $ex->getCode() . ': ' . $ex->getMessage() );
					}
				}
			} else {
				$this->flash( 'red', __( 'Undefined error occurred while saving the form, please, try again.', 'advanced-uptime-monitor-extension' ) );
			}
		}
		$this->render_view( 'uptimerobot_monitor' );
		die();
	}

	/**
	 * Method: ajax_display_dashboard()
	 *
	 * Displays dashboard via AJAX.
	 */
	public function ajax_display_dashboard() {
		$this->ajax_check_permissions( 'display_dashboard' );
		$result = MainWP_AUM_DB::instance()->update_monitor_url( array( 'dashboard' => 1 ), $this->params['url_id'] );

		if ( $result > 0 ) {
			die( 'success' );
		} else {
			die( 'success' );
		}
	}

	/**
	 * Method: get_alert_contact_url()
	 *
	 * Gets alert contacts.
	 *
	 * @param string $api_key        ALI key.
	 * @param int    $monitor_url_id Monitor ID.
	 *
	 * @return array $list_contact_url Alert contacts.
	 */
	public function get_alert_contact_url( $api_key, $monitor_url_id ) {
		$monitors = array( (string) $monitor_url_id );
		try {
			$result = MainWP_AUM_UptimeRobot_API::instance()->get_monitors( $monitors, 0, 1 ); // get alert.
			// place this one first.
			while ( false !== strpos( $result, ',,' ) ) {
				$result = str_replace( array( ',,' ), ',', $result ); // fix json.
			}
			$result = str_replace( ',]', ']', $result ); // fix json.
			$result = str_replace( '[,', '[', $result ); // fix json.

			$result = json_decode( $result );
			if ( 'fail' == $result->stat ) {
				return array();
			}
		} catch ( \Exception $ex ) {
			$this->flash( 'red', $ex->getMessage() );
		}
		$list_contact_url = array();

		if ( is_array( $result->monitors ) && count( $result->monitors ) > 0 ) {
			$number_contacts = count( $result->monitors[0]->alert_contacts );
			for ( $i = 0; $i < $number_contacts; $i++ ) {
				$list_contact_url[ $i ] = $result->monitors[0]->alert_contacts[ $i ]->id;
			}
		}
		return $list_contact_url;
	}

	/**
	 * Method: ajax_update_url()
	 *
	 * Updates URL.
	 */
	public function ajax_update_url() {
		$this->ajax_check_permissions( 'monitors_page' );

		$aum_api_key = MainWP_AUM_UptimeRobot_API::instance()->get_api_key();

		$monitor_url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'] );

		$monitor_url                                 = (array) $monitor_url;
		$this->params['data'][ $this->service_name ] = $monitor_url;

		$list_contact_url = $this->get_alert_contact_url( $aum_api_key, $monitor_url['monitor_id'] );
		$this->params['data'][ $this->service_name ]['url_not_email'] = $list_contact_url;
		$this->render_view( 'uptimerobot_monitor' );
		die();
	}

	/**
	 * Method: ajax_statistics_table()
	 *
	 * Loads statistics table.
	 */
	public function ajax_statistics_table() {
		$this->ajax_check_permissions( 'monitors_page' );

		$url_id = $this->params['url_id'];
		$url    = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $url_id );

		if ( ! $url ) {
			exit;
		}

		$stats_contditions = array( 'url_id' => $url_id );

		$stats = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_cond_events(
			array(
				'conds'      => $stats_contditions,
				'order'      => 'event_datetime_gmt DESC',
				'page'       => isset( $this->params['stats_page'] ) && (int) $this->params['stats_page'] > 0 ? $this->params['stats_page'] : 1,
				'per_page'   => 10,
			)
		);

		$event_statuses = MainWP_AUM_Settings_Page::get_event_statuses();

		$last_existing_event      = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_last_events( $url_id );
		$unix_gmt_time_last_event = 0;
		if ( ! empty( $last_existing_event ) ) {
			$unix_gmt_time_last_event = strtotime( $last_existing_event->event_datetime_gmt );
		}

		$cnt = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_cond_events(
			array(
				'conds'      => $stats_contditions,
				'count_only' => true,
			)
		);
		$this->set( 'stats_cnt', $cnt );
		$this->set( 'stats_page', isset( $this->params['stats_page'] ) ? $this->params['stats_page'] : 1 );
		$this->set( 'url', $url );
		$this->set( 'event_statuses', $event_statuses );
		$this->set( 'unixtime_last_event', $unix_gmt_time_last_event );
		$this->set( 'stats', $stats );
		$this->render_view( 'uptimerobot_statistics_table' );
		die();
	}


	/**
	 * Method: get_notification_contacts()
	 *
	 * Gets notification contacts.
	 *
	 * @param string $api_key API Key.
	 *
	 * @return array $list_contact Notification contacts.
	 */
	public function get_notification_contacts( $api_key ) {
		try {
			$result = MainWP_AUM_UptimeRobot_API::instance()->get_contacts();

			$result = json_decode( $result );
			if ( is_object( $result ) ) {
				if ( property_exists( $result, 'stat' ) && 'fail' == $result->stat ) {
					return array();
				}

				if ( ! property_exists( $result, 'alert_contacts' ) ) {
					return array();
				}
			} else {
				return array();
			}
		} catch ( \Exception $ex ) {
			$this->flash( 'red', $ex->getMessage() );
			return array();
		}

		$contact_types = array(
			2  => 'E-mail',
			8  => 'Pro SMS',
			3  => 'Twitter',
			5  => 'Web-Hook',
			1  => 'Email-to-SMS',
			4  => 'Boxcar 2 (Push for iOS)',
			6  => 'Pushbullet (Push for Android, iOS &amp; Browsers)',
			9  => 'Pushover (Push for Android, iOS, Browsers &amp; Desktop)',
			10 => 'HipChat',
			11 => 'Slack',
		);

		$number_contacts = count( $result->alert_contacts );
		$list_contact    = array();
		for ( $i = 0; $i < $number_contacts; $i++ ) {
			$value = '';
			$type  = $result->alert_contacts[ $i ]->type;
			if ( isset( $contact_types[ $type ] ) ) {
				$value = $result->alert_contacts[ $i ]->friendly_name . ' (' . $contact_types[ $type ] . ')';
			} else {
				$value = $result->alert_contacts[ $i ]->value;
			}
			$list_contact[ $result->alert_contacts[ $i ]->id ] = $value;
		}
		return $list_contact;
	}

	/**
	 * Method: get_monitor_types()
	 *
	 * Gets monitor types.
	 *
	 * @param array $type Monitor types.
	 *
	 * @return string Monitor type.
	 */
	public static function get_monitor_types( $type = false ) {

		$monitor_types = array(
			'1' => 'HTTP(s)',
			'2' => 'Keyword',
			'3' => 'Ping',
			'4' => 'TCP Ports',
		);

		if ( false === $type ) {
			return $monitor_types;
		}

		return isset( $monitor_types[ $type ] ) ? $monitor_types[ $type ] : '';
	}

	/**
	 * Method: render_recent_events()
	 *
	 * Renders recent events.
	 */
	public function render_recent_events() {

		$api_timezone = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'api_timezone', false );

		$offset_time = 0;
		if ( is_array( $api_timezone ) ) {
			$offset_time = $api_timezone ['offset_time'];
			$offset_time = $offset_time * 60 * 60;
		}

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls();

		$urls_info = array();
		$urls_ids  = array();
		if ( $urls ) {
			foreach ( $urls as $url ) {
				if ( ! isset( $urls_info[ $url->url_id ] ) ) {
					$urls_info[ $url->url_id ] = array(
						'url_address' => $url->url_address,
						'url_name'    => $url->url_name,
					);
					$urls_ids[]                = $url->url_id;
				}
			}
		}
		unset( $urls );

		$event_statuses = MainWP_AUM_Settings_Page::get_event_statuses();

		$order_by = 'event_datetime_gmt DESC';
		$limit    = 100;
		$stats    = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_events( '', $limit, $order_by, $urls_ids );

		unset( $urls_ids );

		$this->set( 'offset_time', $offset_time );
		$this->set( 'urls_info', $urls_info );
		$this->set( 'stats', $stats );
		$this->set( 'event_statuses', $event_statuses );
		$this->render_view( 'uptimerobot_recent_events' );
	}

	/**
	 * Method: ajax_add_monitor_for_site()
	 *
	 * Creates monitor for child site.
	 *
	 * @param object $site             Site object.
	 * @param array  $data             Data.
	 * @param array  $just_inserted_id Just inserted IDs.
	 */
	public function ajax_add_monitor_for_site( $site, $data, $just_inserted_id ) {
		$information = array();
		try {
			$post_data = array(
				'name'                 => $site->url,
				'uri'                  => $site->url,
				'type'                 => 1,
				'monitor_interval'     => 5,
				'http_username'        => $site->http_user,
				'http_password'        => $site->http_pass,
				'monitorAlertContacts' => MainWP_AUM_UptimeRobot_API::instance()->get_option( 'uptime_default_notification_contact_id' ),
			);
			try {
				$result = MainWP_AUM_UptimeRobot_API::instance()->new_monitor( $post_data );
				$result = json_decode( $result );
			} catch ( \Exception $ex ) {
				throw $ex;
			}
			if ( isset( $result ) && 'ok' == $result->stat ) {
				$information['result'] = 'success';
				$uptime_monitor_id     = $result->monitor->id;
				if ( $uptime_monitor_id ) {
					$data = array(
						'monitor_id' => $uptime_monitor_id,
						'dashboard'  => 1,  // new monitor, set display on dashboard as default.
					);
					MainWP_AUM_DB::instance()->insert_monitor_url( $data );

					$opts_value = array(
						'monitor_type'     => 1, // http type.
						'monitor_interval' => 5,
						'http_username'    => $site->http_user,
						'http_password'    => $site->http_pass,
					);
					MainWP_AUM_DB::instance()->save_monitor_url_options( $just_inserted_id, $opts_value );

				} else {
					MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $just_inserted_id );
					$information['error'] = 'Creating monitor failed!';
				}
			} else {
				if ( $result ) {
					$information['error'] = $result->error->message;
				} else {
					$information['error'] = 'Undefined error on the Uptime Robot side. Please try again later.';
				}
				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $just_inserted_id );
			}
		} catch ( \Exception $ex ) {
			switch ( $ex->getCode() ) {
				case 1:
					$information['error'] = esc_html( $ex->getMessage() );
					break;
				case 2:
					$information['error'] = 'You should specify API key';
					break;
				case 3:
					$information['error'] = 'Error';
					break;
				default:
					$information['error'] = esc_html( $ex->getCode() . ': ' . $ex->getMessage() );
			}
		}
		return $information;
	}

	/**
	 * Method get_uptime_monitor_state()
	 *
	 * Renders uptime status.
	 *
	 * @param string $monitor Monitor status.
	 *
	 * @return string $sta Uptime status.
	 */
	public static function get_uptime_monitor_state( $monitor ) {
		$status  = $monitor->status;
		$ratio   = $monitor->alltimeuptimeratio;
		$tooltip = 'data-tooltip="' . __( 'Uptime ratio:' ) . ' ' . number_format( $ratio, 2 ) . '%"';

		switch ( $status ) {
			case 0:
				$sta = '<span class="ui black fluid center aligned label" data-inverted="" data-position="left center" ' . $tooltip . '><span >Paused</span>';
				break;
			case 1:
				$sta = '<span class="ui grey fluid center aligned label" data-inverted="" data-position="left center" ' . $tooltip . '><span >Pending</span>';
				break;
			case 2:
				$sta = '<span class="ui green fluid center aligned  label" data-inverted="" data-position="left center" ' . $tooltip . '><span >Up</span>';
				break;
			case 8:
				$sta = '<span class="ui yellow fluid center aligned  label" data-inverted="" data-position="left center" ' . $tooltip . '><span >Seems Down</span>';
				break;
			case 9:
				$sta = '<span class="ui red fluid center aligned  label" data-inverted="" data-position="left center" ' . $tooltip . '><span >Down</span>';
				break;
		}
		return $sta;
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

		$url_upmo_ids = array( $monitor_address->monitor_id );
		$result       = MainWP_AUM_UptimeRobot_API::instance()->get_monitors( $url_upmo_ids, 0, 0, 1, '7-15-30-45-60' );

		// Place this one first.
		while ( strpos( $result, ',,' ) !== false ) {
			$result = str_replace( array( ',,' ), ',', $result ); // fix json.
		}
		$result = str_replace( ',]', ']', $result ); // fix json.
		$result = str_replace( '[,', '[', $result ); // fix json.

		$result = json_decode( $result );

		if ( empty( $result ) || $result->stat == 'fail' ) {
			return false;
		}
		$return = array();

		if ( isset( $result->monitors ) && is_array( $result->monitors ) && count( $result->monitors ) > 0 ) {
			$monitor                                 = $result->monitors[0];
			$return['aum.alltimeuptimeratio']        = $monitor->all_time_uptime_ratio;
			list( $up7, $up15, $up30, $up45, $up60 ) = explode( '-', $monitor->custom_uptime_ratio );

			$return['aum.uptime7']  = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $up7 );
			$return['aum.uptime15'] = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $up15 );
			$return['aum.uptime30'] = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $up30 );
			$return['aum.uptime45'] = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $up45 );
			$return['aum.uptime60'] = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $up60 );
		}

		$event_statuses = MainWP_AUM_Settings_Page::get_event_statuses();
		$order_by       = 'event_datetime_gmt DESC';
		$stats          = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_events( 'event_datetime_gmt >= STR_TO_DATE("' . gmdate( 'Y-m-d H:i:s', $start_date ) . '", "%Y-%m-%d %H:%i:%s") AND event_datetime_gmt <= STR_TO_DATE("' . date( 'Y-m-d H:i:s', $end_date ) . '", "%Y-%m-%d %H:%i:%s") AND url_id = ' . $monitor_address->url_id, false, $order_by );

		$last_existing_event = MainWP_AUM_DB_Events_UptimeRobot::instance()->get_last_events( $monitor_address->url_id );
		$unixtime_last_event = 0;
		if ( ! empty( $last_existing_event ) ) {
			$unixtime_last_event = strtotime( $last_existing_event->event_datetime_gmt );
		}

		if ( $stats ) {

			$api_timezone = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'api_timezone', false );
			$offset_time  = 0;
			if ( is_array( $api_timezone ) ) {
				$offset_time = $option['api_timezone']['offset_time'];
				$offset_time = $offset_time * 60 * 60;
			}

			ob_start();
			?>
			<table>
				<tbody>
					<tr>
					   <th style="text-align: left;"><?php _e( 'Monitor Name:', 'advanced-uptime-monitor-extension' ); ?></th>
					   <td><?php echo $monitor_address->url_name; ?></td>
				   </tr>
				   <tr>
					   <th style="text-align: left;"><?php _e( 'Monitor URL:', 'advanced-uptime-monitor-extension' ); ?></th>
					   <td><?php echo $monitor_address->url_address; ?></td>
				   </tr>
				   <tr>
					   <th style="text-align: left;"><?php _e( 'Monitor Type:', 'advanced-uptime-monitor-extension' ); ?></th>
					   <td><?php echo self::get_monitor_types( $monitor_address->monitor_type ); ?></td>
				   </tr>
				   <tr>
						<th style="text-align: left;"><?php _e( 'Current Status:', 'advanced-uptime-monitor-extension' ); ?></th>
						<td>
						<?php
						$last_status = $stats[0];
						if ( is_object( $last_status ) && property_exists( $last_status, 'monitor_type' ) ) {
							if ( $last_status->monitor_type == '-1' ) {
								$type = $last_status->type;
								echo ucfirst( $event_statuses[ $type ] );
							} else {
								$type = $last_status->monitor_type;
								switch ( $type ) {
									case '0':
										echo __( 'Paused', 'advanced-uptime-monitor-extension' );
										break;
									case '1':
										echo __( 'Started', 'advanced-uptime-monitor-extension' );
										break;
								}
							}
						}
						?>
						</td>
				   </tr>
				</tbody>
			</table>
			<table style="border-spacing: 0;">
					<thead>
						<tr>
							<th width="100px" style="text-align: left;">
								<?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?>
							</th>
							<th width="220px" style="text-align: left;">
								<?php _e( 'Details', 'advanced-uptime-monitor-extension' ); ?>
							</th>
							<th   width="160px" style="text-align: left;" >
								<?php _e( 'Date / Time', 'advanced-uptime-monitor-extension' ); ?>
							</th>
							<th style="text-align: left;">
								<?php _e( 'Duration', 'advanced-uptime-monitor-extension' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $stats as $event ) {
							$status = '';
							$type   = $event->type;
							$status = ucfirst( $event_statuses[ $type ] );
							?>
							<tr>
								<td>
									<?php
									switch ( $status ) {
										case 'Started':
											echo __( 'Started', 'advanced-uptime-monitor-extension' );
											break;
										case 'Up':
											echo __( 'Up', 'advanced-uptime-monitor-extension' );
											break;
										case 'Paused':
											echo __( 'Paused', 'advanced-uptime-monitor-extension' );
											break;
										case 'Down':
											echo __( 'Down', 'advanced-uptime-monitor-extension' );
											break;
									}
									?>
								</td>
								<td>
									<?php
									switch ( $status ) {
										case 'Started':
											echo __( 'The monitor started manually', 'advanced-uptime-monitor-extension' );
											break;
										case 'Up':
											echo __( 'Successful response received.', 'advanced-uptime-monitor-extension' );
											break;
										case 'Paused':
											echo __( 'The monitor is paused manually', 'advanced-uptime-monitor-extension' );
											break;
										case 'Down':
											if ( $monitor_address->monitor_type == '2' ) {
												echo __( 'The keyword exists.', 'advanced-uptime-monitor-extension' );
											} else {
												echo __( 'No response from the website.', 'advanced-uptime-monitor-extension' );
											}
											break;
									}
									?>
								</td>
								<td>
								<?php
									$datetime = strtotime( $event->event_datetime_gmt ) + $offset_time;
									$datetime = MainWP_AUM_Main::format_timestamp( $datetime );
									echo $datetime;
								?>
								</td>
								<td>
									<?php
									$duration                = $event->duration;
									$unixtime_event_datetime = strtotime( $event->event_datetime_gmt );
									if ( $unixtime_event_datetime >= $unixtime_last_event ) {
										$duration = time() - strtotime( $event->event_datetime_gmt );
									}
									$hrs  = floor( $duration / 3600 );
									$mins = floor( ( $duration - $hrs * 3600 ) / 60 );
									echo $hrs . ' hrs, ' . $mins . ' mins';
									?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			<?php
			$stats_html          = ob_get_clean();
			$stats_html          = preg_replace( "/\r|\n/", '', $stats_html );
			$return['aum.stats'] = $stats_html;
		}

		return $return;
	}

	/**
	 * Method get_nodeping_monitor_state()
	 *
	 * Renders uptime status.
	 *
	 * @param string $monitor Monitor status.
	 *
	 * @return string $sta Uptime status.
	 */
	public static function get_nodeping_monitor_state( $monitor ) {
		$monitor_status = '';
		if ( 'inactive' == $monitor->enable ) {
			$monitor_status = '<span class="ui grey fluid center aligned label">' . __( 'DISABLED', 'advanced-uptime-monitor-extension' ) . '</span>';
		} else {
			if ( 1 == $monitor->state ) {
				$monitor_status = '<span class="ui green fluid center aligned label">' . __( 'PASS', 'advanced-uptime-monitor-extension' ) . '</span>';
			} else {
				$monitor_status = '<span class="ui red fluid center aligned label">' . __( 'FAIL', 'advanced-uptime-monitor-extension' ) . '</span>';
			}
		}
		return $monitor_status;
	}

	/**
	 * Method: check_unavailable_url_monitors()
	 *
	 * Checks for missing monitors.
	 *
	 * @return array Missing monitors.
	 */
	public function check_unavailable_url_monitors() {

		$last_reload = get_option( 'mainwp_aum_uptime_last_reload_monitors', 0 );

		$urls             = MainWP_AUM_DB::instance()->get_monitor_urls();
		$unavailable_urls = array();
		foreach ( $urls as $url ) {
			if ( ! empty( $url->lastupdate ) && $url->lastupdate < $last_reload ) {
				$unavailable_urls[] = $url->url_name;
				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $url->url_id );
				MainWP_AUM_DB_Events_UptimeRobot::instance()->delete_events( $url->url_id );
			}
		}

		if ( ! empty( $unavailable_urls ) ) {
			$this->flash( 'yellow', __( 'Following monitors could not be found in your Uptime Robot dashboard so they have been deleted:<br/>', 'advanced-uptime-monitor-extension' ) . implode( ',<br/>', $unavailable_urls ) );
			return true;
		}
		return false;
	}
}
