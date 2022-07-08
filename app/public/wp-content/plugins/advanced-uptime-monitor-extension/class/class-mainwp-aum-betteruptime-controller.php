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
 * Class MainWP_AUM_BetterUptime_Controller
 */
class MainWP_AUM_BetterUptime_Controller extends MainWP_AUM_Controller {

	/**
	 * Sevice name.
	 *
	 * @var $service_name.
	 */
	public $service_name = 'betteruptime';

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_BetterUptime_Controller.
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
	 * Method get_betteruptime_monitor_state()
	 *
	 * Gets monitor state.
	 *
	 * @param object $monitor Available monitor statuses.
	 *
	 * @return string $state_item Monitor state.
	 */
	public static function get_betteruptime_monitor_state( $url ) {
		$monitor_status = '';
		if ( 'up' == $url->status ) {
			$monitor_status = '<span class="ui green fluid center aligned label">' . __( 'Up', 'advanced-uptime-monitor-extension' ) . '</span>';
		} elseif ( 'down' == $url->status ) {
			$monitor_status = '<span class="ui red fluid center aligned label">' . __( 'Down', 'advanced-uptime-monitor-extension' ) . '</span>';
		} elseif ( 'paused' == $url->status ) {
			$monitor_status = '<span class="ui yellow fluid center aligned label">' . __( 'Paused', 'advanced-uptime-monitor-extension' ) . '</span>';
		} elseif ( 'pending' == $url->status ) {
			$monitor_status = '<span class="ui gray fluid center aligned label">' . __( 'Pending', 'advanced-uptime-monitor-extension' ) . '</span>';
		}
		return $monitor_status;
	}

	/**
	 * Method get_betteruptime_event_state()
	 *
	 * Gets event state.
	 *
	 * @param object $event Available event statuses.
	 *
	 * @return string $state_item event state.
	 */
	public static function get_betteruptime_event_state( $event ) {
		return ! empty( $event->resolved_at ) ? '<span class="ui green fluid center aligned label">' . __( 'Resolved', 'advanced-uptime-monitor-extension' ) . '</span>' : '<span class="ui red fluid center aligned label">' . __( 'Ongoing', 'advanced-uptime-monitor-extension' ) . '</span>';
	}

	/**
	 * Method get_monitor_interval_options()
	 *
	 * Get monitor interval.
	 *
	 * @param int $interval Monitor interval.
	 *
	 * @return string $state_item Monitor interval label.
	 */
	public static function get_monitor_interval_options( $interval = false ) {

		$opts = array(
			'30'   => '30 seconds',
			'45'   => '45 seconds',
			'60'   => '1 minute',
			'120'  => '2 minutes',
			'180'  => '3 minutes',
			'300'  => '5 minutes',
			'600'  => '10 minutes',
			'900'  => '15 minutes',
			'1800' => '30 minutes',
		);

		if ( false === $interval ) {
			return $opts;
		} elseif ( isset( $opts[ $interval ] ) ) {
			return $opts[ $interval ];
		}
		return '';
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

		if ( isset( $this->params['url_id'] ) && ! empty( $this->params['url_id'] ) ) {
			$editing_url_id = $this->params['url_id'];
		} elseif ( isset( $this->params['data'][ $this->service_name ]['url_id'] ) && ! empty( $this->params['data'][ $this->service_name ]['url_id'] ) ) {
			$editing_url_id = $this->params['data'][ $this->service_name ]['url_id'];
		}

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

				$request_headers = ! empty( $data['request_headers'] ) ? $data['request_headers'] : '';
				$db_headers      = array();
				$api_headers     = array();
				if ( is_array( $request_headers ) ) {
					foreach ( $request_headers as $items_headers ) {
						if ( ! empty( $items_headers['name'] ) && ! empty( $items_headers['value'] ) ) {
							$db_headers[]  = $items_headers;
							$api_headers[] = $items_headers;
						} elseif ( ! empty( $items_headers['id'] ) ) {
							$api_headers[] = array(
								'id'       => $items_headers['id'],
								'_destroy' => true,
							);
						}
					}
				}

				$is_monitor_response = false;
				if ( in_array( $data['monitor_type'], array( 'ping', 'tcp', 'udp', 'smtp', 'pop', 'imap', 'dns' ) ) ) {
					$is_monitor_response = true;
				}

				$regions = ! empty( $data['regions'] ) ? $data['regions'] : array();

				$opts_value = array(
					'monitor_type'        => ! empty( $data['monitor_type'] ) ? $data['monitor_type'] : 'status', // http type.
					'required_keyword'    => ! empty( $data['required_keyword'] ) ? $data['required_keyword'] : '', // http type.
					'port'                => ! empty( $data['port'] ) ? $data['port'] : '',
					'monitor_interval'    => ! empty( $data['monitor_interval'] ) ? $data['monitor_interval'] : 180,
					'domain_expiration'   => ! empty( $data['domain_expiration'] ) ? $data['domain_expiration'] : '',
					'verify_ssl'          => ! empty( $data['verify_ssl'] ) ? $data['verify_ssl'] : 'true',
					'ssl_expiration'      => ! empty( $data['ssl_expiration'] ) ? $data['ssl_expiration'] : '',
					'request_timeout'     => ! empty( $data['request_timeout'] ) ? $data['request_timeout'] : 30,
					'request_body'        => ! empty( $data['request_body'] ) ? $data['request_body'] : '',
					'request_headers'     => json_encode( $db_headers ),
					'follow_redirects'    => ! empty( $data['follow_redirects'] ) ? 1 : 0,
					'recovery_period'     => ! empty( $data['recovery_period'] ) ? $data['recovery_period'] : 180,
					'confirmation_period' => ! empty( $data['confirmation_period'] ) ? $data['confirmation_period'] : 0,
					'http_username'       => ! empty( $data['http_username'] ) ? $data['http_username'] : '',
					'http_password'       => ! empty( $data['http_password'] ) ? $data['http_password'] : '',
					'http_method'         => ! empty( $data['http_method'] ) ? $data['http_method'] : 'get',
					'team_wait'           => isset( $data['team_wait'] ) ? $data['team_wait'] : '',
					'email'               => isset( $data['email'] ) ? $data['email'] : 1,
					'maintenance_from'    => ! empty( $data['maintenance_from'] ) ? $data['maintenance_from'] : '',
					'maintenance_to'      => ! empty( $data['maintenance_to'] ) ? $data['maintenance_to'] : '',
					'regions'             => json_encode( $regions ),
				);

				if ( $is_monitor_response ) {
					$opts_value['tcp_timeout'] = ! empty( $data['tcp_timeout'] ) ? $data['tcp_timeout'] : 5000;
				}

				MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $url_id, $opts_value );

				try {
					$post_data = array(
						'pronounceable_name'  => trim( $data['url_name'] ),
						'url'                 => trim( $data['url_address'] ),
						'monitor_type'        => $opts_value['monitor_type'],
						'required_keyword'    => $opts_value['required_keyword'],
						'port'                => $opts_value['port'],
						'check_frequency'     => $opts_value['monitor_interval'],
						'domain_expiration'   => $opts_value['domain_expiration'],
						'verify_ssl'          => $opts_value['verify_ssl'],
						'ssl_expiration'      => $opts_value['ssl_expiration'],
						'request_timeout'     => $opts_value['request_timeout'],
						'request_body'        => $opts_value['request_body'],
						'request_headers'     => $api_headers,
						'follow_redirects'    => $opts_value['follow_redirects'],
						'recovery_period'     => $opts_value['recovery_period'],
						'confirmation_period' => $opts_value['confirmation_period'],
						'http_method'         => $opts_value['http_method'],
						'auth_username'       => $opts_value['http_username'],
						'auth_password'       => $opts_value['http_password'],
						'team_wait'           => $opts_value['team_wait'],
						'email'               => $opts_value['email'],
						'maintenance_from'    => $opts_value['maintenance_from'],
						'maintenance_to'      => $opts_value['maintenance_to'],
						'regions'             => $regions,
					);

					if ( $is_monitor_response ) {
						$post_data['tcp_timeout'] = $opts_value['tcp_timeout'];
					}

					try {
						if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
							$result = MainWP_AUM_BetterUptime_API::instance()->api_edit_monitor( $post_data );
						} else {
							$monitor_url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $data['url_id'], $this->service_name );
							$result      = MainWP_AUM_BetterUptime_API::instance()->api_edit_monitor( $post_data, $monitor_url->monitor_id );
						}
						$result = json_decode( $result, true );
					} catch ( \Exception $ex ) {
						// ok.
					}

					// to fix display.
					$this->params['data'][ $this->service_name ]['request_headers'] = json_encode( $db_headers );
					$this->params['data'][ $this->service_name ]['regions']         = json_encode( $regions );

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
							'monitor_id' => $result['data']['id'],
						);
						MainWP_AUM_DB::instance()->update_monitor_url( $update_data, $url_id );
						$this->set( 'url_saved', true );
					} else {
						if ( is_array( $result ) && isset( $result['errors'] ) ) {
							$this->flash( 'red', $result['errors'] );
						} else {
							$this->flash( 'red', __( 'Undefined error on the Better Uptime API side. Please try again later.', 'advanced-uptime-monitor-extension' ) );
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
			$monitor_url                                 = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'], $this->service_name, ARRAY_A );
			$this->params['data'][ $this->service_name ] = $monitor_url;
		}

		$this->render_view( 'betteruptime_monitor' );
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
		$this->render_view( 'betteruptime_monitor_urls' );
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

		$aum_code = MainWP_AUM_BetterUptime_API::instance()->init_settings();

		if ( empty( $aum_code['api_token'] ) ) {
			$this->flash( 'yellow', __( 'Please enter your Better Uptime API Token.', 'advanced-uptime-monitor-extension' ) );
			return false;
		}

		global $mainwpAUMExtensionActivator;

		$filter_searching = '';

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
		} else {
			$filter_searching = isset( $_REQUEST['searching'] ) && ! empty( $_REQUEST['searching'] ) ? sanitize_text_field( $_REQUEST['searching'] ) : '';
		}

		$get_page = isset( $_REQUEST['get_page'] ) && $_REQUEST['get_page'] > 0 ? $_REQUEST['get_page'] : 1;

		$total = 0;
		$urls  = array();

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
				'searching'       => ( empty(  $site_id ) && ! empty(  $filter_searching ) ) ? $filter_searching : '',
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
	 * Method check_unavailable_url_monitors()
	 *
	 * Checks for unavaialble monitors.
	 *
	 * @return bool True|False.
	 */
	public function check_unavailable_url_monitors() {
		$last_reload = get_option( 'mainwp_aum_betteruptime_last_reload_monitors', 0 );

		$urls             = MainWP_AUM_DB::instance()->get_monitor_urls( 'betteruptime' );
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

	/**
	 * Method check_unavailable_url_monitors()
	 *
	 * Checks for unavaialble monitors.
	 *
	 * @return bool True|False.
	 */
	public function get_monitors_availability( $get_page = 0, $per_page = 5 ) {

		$avail_lastchecked = get_option( 'mainwp_aum_betteruptime_monitors_avail_lastchecked' );

		if ( date( 'Y-m-d' ) == $avail_lastchecked ) {
			return;
		}

		$monitors = MainWP_AUM_DB::instance()->get_monitor_urls(
			'betteruptime',
			array(
				'page'     => $get_page,
				'per_page' => $per_page,
			)
		);

		$count = is_array( $monitors ) ? count( $monitors ) : 0;

		if ( $count ) {
			$params['start_date'] = date( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
			$params['end_date']   = date( 'Y-m-d' );

			foreach ( $monitors as $monitor_address ) {
				if ( ! $monitor_address->monitor_id ) {
					continue;
				}
				$result = MainWP_AUM_BetterUptime_API::instance()->get_uptime( $monitor_address->monitor_id, $params );
				$result = json_decode( $result, true );
				if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['attributes'] ) ) {
					$opts_value = array(
						'availability' => $result['data']['attributes']['availability'],
					);
					MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $monitor_address->url_id, $opts_value );
				}
			}
			$get_page = $get_page + 1;
			if ( $get_page * $per_page < 500 ) { // to limit.
				$this->get_monitors_availability( $get_page );
			}
		} else {
			update_option( 'mainwp_aum_betteruptime_monitors_availability_today', date( 'Y-m-d' ) );
		}
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

		$stats = MainWP_AUM_DB_Events_BetterUptime::instance()->get_cond_events(
			array(
				'conds'    => $stats_contditions,
				'order'    => 'started_at DESC',
				'page'     => isset( $this->params['stats_page'] ) && (int) $this->params['stats_page'] > 0 ? $this->params['stats_page'] : 1,
				'per_page' => 10,
			)
		);

		$cnt = MainWP_AUM_DB_Events_BetterUptime::instance()->get_cond_events(
			array(
				'conds'      => $stats_contditions,
				'count_only' => true,
			)
		);

		$this->set( 'stats_cnt', $cnt );
		$this->set( 'stats_page', isset( $this->params['stats_page'] ) ? $this->params['stats_page'] : 1 );
		$this->set( 'url', $url );
		$this->set( 'stats', $stats );
		$this->render_view( 'betteruptime_statistics_table' );
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
		$this->render_view( 'betteruptime_meta_box' );
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
				'with_properties' => false,
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

		$order_by = 'started_at DESC';
		$limit    = 100;
		$stats    = MainWP_AUM_DB_Events_BetterUptime::instance()->get_events( '', $limit, $order_by );
		$this->set( 'urls_info', $urls_info );
		$this->set( 'stats', $stats );
		$this->render_view( 'betteruptime_recent_events' );
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

		// getting last event recorded to database.
		$last_event = MainWP_AUM_DB_Events_BetterUptime::instance()->get_last_events();

		$fromdate = '';
		if ( ! empty( $last_event ) ) {
			$fromdate = date( 'yy-m-d', $last_event->started_at );
		}

		$result = MainWP_AUM_BetterUptime_API::instance()->get_last_incidents( false, $fromdate );
		$result = json_decode( $result, true );

		if ( is_array( $result ) && isset( $result['data'] ) ) {
			$monitors_status = $result['data'];
			foreach ( $monitors_status as $mo_status ) {
				$mo_id = isset( $mo_status['relationships']['monitor']['data']['id'] ) ? $mo_status['relationships']['monitor']['data']['id'] : 0;
				if ( empty( $mo_id ) || ! isset( $url_ids[ $mo_id ] ) ) {
					continue;
				}
				$url_id = $url_ids[ $mo_id ];

				$response_opts = ! empty( $mo_status['attributes']['response_options'] ) ? json_decode( $mo_status['attributes']['response_options'], true ) : array();
				if ( ! is_array( $response_opts ) ) {
					$response_opts = array();
				}

				$data = array(
					'url_id'          => $url_id,
					'cause'           => $mo_status['attributes']['cause'],
					'started_at'      => strtotime( $mo_status['attributes']['started_at'] ),
					'resolved_at'     => strtotime( $mo_status['attributes']['resolved_at'] ),
					'acknowledged_at' => strtotime( $mo_status['attributes']['acknowledged_at'] ),
					'incident_id'     => $mo_status['id'],
					'incident_name'   => $mo_status['attributes']['name'],
				);
				MainWP_AUM_DB_Events_BetterUptime::instance()->insert_event( $data );
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
			'pronounceable_name'  => $site->url,
			'url'                 => $site->url,
			'monitor_type'        => 'status',
			'check_frequency'     => 180, // seconds.
			'request_timeout'     => 30, // seconds.
			'recovery_period'     => 180,
			'confirmation_period' => 0,
			'http_method'         => 'get',
			'auth_username'       => $site->http_user,
			'auth_password'       => $site->http_pass,
			'team_wait'           => '',
			'email'               => 1,
		);

		try {
			$result = MainWP_AUM_BetterUptime_API::instance()->api_edit_monitor( $post_data ); // add new monitor.
			$result = json_decode( $result, true );
		} catch ( \Exception $ex ) {
			// ok.
		}

		if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['id'] ) ) {
			$this->flash( 'green', __( 'Monitor created successfully!', 'advanced-uptime-robot-extension' ) );
			$new_monitor = true;
			do_action( 'mainwp_aum_monitor_created', $data );
			$uptime_monitor_id = $result['data']['id'];

			if ( ! empty( $uptime_monitor_id ) ) {
				$data = array(
					'monitor_id' => $uptime_monitor_id,
					'dashboard'  => 1, // new monitor, set display on dashboard as default.
				);
				MainWP_AUM_DB::instance()->update_monitor_url( $data, $just_inserted_id );
				$user_groupids = implode( ',', $post_data['user_group_ids'] );
				$opts_value    = array(
					'monitor_type'        => 'status', // http type.
					'monitor_interval'    => 180,
					'request_timeout'     => 30,
					'http_username'       => $site->http_user,
					'http_password'       => $site->http_pass,
					'http_method'         => 'get',
					'recovery_period'     => 180,
					'confirmation_period' => 0,
					'team_wait'           => '',
					'email'               => 1,
				);
				MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $just_inserted_id, $opts_value );
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
				$err = __( 'Undefined error on the Better Uptime API side. Please try again later.', 'advanced-uptime-monitor-extension' );
				$this->flash( 'red', $err );
			}
			MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $just_inserted_id );
			$information['error'] = $err;
		}
		return $information;
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
			0  => 365, // Last 365 days.
			7  => 7, // Last 7 Days.
			15 => 15, // Last 15 Days.
			30 => 30, // Last 30 Days.
			45 => 45, // Last 45 Days.
			60 => 60, // Last 60 Days.
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
			$params = array();

			$params['start_date'] = date( 'Y-m-d', time() - $val * DAY_IN_SECONDS );
			$params['end_date']   = date( 'Y-m-d' );

			$result = MainWP_AUM_BetterUptime_API::instance()->get_uptime( $monitor_address->monitor_id, $params );
			$result = json_decode( $result, true );
			if ( is_array( $result ) && isset( $result['data'] ) && isset( $result['data']['attributes'] ) ) {
				$uptimes[ $d ] = $result['data']['attributes']['availability']; // or number_of_incidents.
			}
		}

		$opts_value = array(
			'availability' => $uptimes[30],
		);

		MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $monitor_address->url_id, $opts_value );

		$return['aum.alltimeuptimeratio'] = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[0] );
		$return['aum.uptime7']            = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[7] );
		$return['aum.uptime15']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[15] );
		$return['aum.uptime30']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[30] );
		$return['aum.uptime45']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[45] );
		$return['aum.uptime60']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[60] );

		$order_by = 'started_at DESC';
		$stats    = MainWP_AUM_DB_Events_BetterUptime::instance()->get_events( 'started_at >= STR_TO_DATE("' . gmdate( 'Y-m-d H:i:s', $start_date ) . '", "%Y-%m-%d %H:%i:%s") AND started_at <= STR_TO_DATE("' . date( 'Y-m-d H:i:s', $end_date ) . '", "%Y-%m-%d %H:%i:%s") AND url_id = ' . $monitor_address->url_id, false, $order_by );

		ob_start();
		?>
			<table class="ui single line table">
				<thead>
					<tr>
					<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'URL', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Interval', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo esc_html( $monitor_address->url_name ); ?></td>
						<td><a href="<?php echo $monitor_address->url_address; ?>" target="_blank"><?php echo $monitor_address->url_address; ?></a></td>
						<td><?php echo esc_html( $monitor_address->monitor_interval ); ?></td>
						<td><?php echo self::get_betteruptime_monitor_state( $monitor_address ); ?></td>
					</tr>
					</tbody>
				</table>
				<?php if ( ! empty( $stats ) ) { ?>					
					<table class="ui single line table">
					<thead>
						<tr>
							<th><?php _e( 'Cause', 'advanced-uptime-monitor-extension' ); ?></th>
							<th><?php _e( 'Started at', 'advanced-uptime-monitor-extension' ); ?></th>
							<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $stats as $event ) {
						?>
						<tr>
						<td><?php echo esc_html( $event->cause ); ?></td>
						<td><?php echo MainWP_AUM_Main::format_timestamp( $event->started_at ); ?></td>
						<td><?php echo self::get_betteruptime_event_state( $event ); ?></td></tr>
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
