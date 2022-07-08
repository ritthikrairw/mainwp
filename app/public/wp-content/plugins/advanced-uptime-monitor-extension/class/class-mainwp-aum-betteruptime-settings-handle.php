<?php
/**
 * MainWP BetterUptime Settings
 *
 * Handles BetterUptime settings.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class class MainWP_AUM_BetterUptime_Settings_Handle
 *
 * BetterUptime settings handle
 */
class MainWP_AUM_BetterUptime_Settings_Handle {

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_BetterUptime_Settings_Handle.
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 *
	 * Get instance.
	 *
	 * @return object Instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Method: handle_post_saving()
	 *
	 * Handles post saving.
	 *
	 * @param array $data Post data.
	 *
	 * @return bool true|false.
	 */
	public function handle_post_saving( $data ) {

		$options = array(
			'api_token' => isset( $data['api_token'] ) ? sanitize_text_field( $data['api_token'] ) : '',
		);
		MainWP_AUM_BetterUptime_API::instance()->update_options( $options );

		$this->reload_monitor_urls();

		return true;
	}

	/**
	 * Method: get_api_monitors()
	 *
	 * Gets Monitors.
	 *
	 * @return array $results Monitors data.
	 */
	public function get_api_monitors( $offset_page = 0 ) {

		$result = MainWP_AUM_BetterUptime_API::instance()->get_all_monitors( $offset_page );  // to reload urls.

		if ( ! empty( $result ) ) {
			$result = json_decode( $result, true );
		}
		return $result;
	}

	/**
	 * Method: reload_monitor_urls()
	 *
	 * Reloads monitors.
	 */
	public function reload_monitor_urls( $ajax = false ) {

		if ( $ajax ) {
			$offset_page = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 1;
		} else {
			$offset_page = get_option( 'mainwp_aum_betteruptime_reload_monitors_offset', 1 );
		}

		if ( 0 == $offset_page ) {
			$offset_page = 1;
		}

		$result = $this->get_api_monitors( $offset_page );

		$valid = false;

		$next_offset = $offset_page;
		$monitors    = array();

		if ( is_array( $result ) && isset( $result['data'] ) ) {
			$data = $result['data'];
			foreach ( $data as $mo ) {
				if ( 'monitor' == $mo['type'] ) {
					$monitors[] = $mo;
				}
			}
			$valid = true;
		} else {
			update_option( 'mainwp_aum_uptime_robot_message', __( 'Better Uptime monitor undefined error', 'advanced-uptime-monitor-extension' ) );
		}

		if ( is_array( $monitors ) && ( count( $monitors ) > 0 ) ) {
			$this->save_api_monitors( $monitors ); // saving BetterUptime monitors.
		}

		$last_page = 0;
		if ( is_array( $result ) && isset( $result['pagination'] ) && isset( $result['pagination']['last'] ) ) {
			$pagi_last = $result['pagination']['last'];

			if ( preg_match( '/.+page=([0-9])+/ix', $result['pagination']['last'], $matches ) ) {
				$last_page = intval( $matches[1] );
			}

			if ( $next_offset < $last_page ) {
				$next_offset += 1; // go next page offset.
			} else {
				$next_offset = 1; // go first page offset.
			}
		}

		update_option( 'mainwp_aum_betteruptime_reload_monitors_offset', $next_offset );

		if ( $valid ) {
			if ( 1 == $offset_page ) {
				update_option('mainwp_aum_betteruptime_last_reload_monitors', time());
			}
			if ( 1 == $next_offset ) { // go first page reload, so check unavailable urls.
				MainWP_AUM_BetterUptime_Controller::instance()->check_unavailable_url_monitors();
				MainWP_AUM_BetterUptime_Controller::instance()->get_monitors_availability();
			} elseif ( $next_offset <= $last_page && $last_page > 0 ) {
				$this->reload_monitor_urls(); // reload next monitors page.
			}
		}

		$total = MainWP_AUM_DB::instance()->get_monitor_urls(
			'betteruptime',
			array(
				'conds'      => array(),
				'count_only' => true,
			)
		);

		return array(
			'total'  => $total,
			'offset' => $next_offset,
		);
	}

	/**
	 * Method save_api_monitors()
	 *
	 * Saves moniotors.
	 *
	 * @param array $urls_monitor Monitors list.
	 *
	 * @return array $saved_ids Saved IDs.
	 */
	public function save_api_monitors( $urls_monitor ) {

		$saved_ids = array();
		foreach ( $urls_monitor as $monitor_url ) {

			if ( ! isset( $monitor_url['attributes'] ) ) {
				continue;
			}

			$current = MainWP_AUM_DB::instance()->get_monitor_by( 'url_address', $monitor_url['attributes']['url'], 'betteruptime', OBJECT, false );
			$data    = array(
				'url_name'    => $monitor_url['attributes']['pronounceable_name'],
				'url_address' => $monitor_url['attributes']['url'],
				'monitor_id'  => $monitor_url['id'],
				'service'     => 'betteruptime',
				'lastupdate'  => time(),
			);

			if ( ! empty( $current ) ) {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->update_monitor_url( $data, $current->url_id );
			} else {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->insert_monitor_url( $data );
			}

			if ( ! empty( $just_insertupdate_id ) ) {
					$url_options = array(
						'monitor_type'        => $monitor_url['attributes']['monitor_type'],
						'required_keyword'    => $monitor_url['attributes']['required_keyword'],
						'monitor_interval'    => $monitor_url['attributes']['check_frequency'],
						'port'                => $monitor_url['attributes']['port'],
						'request_timeout'     => $monitor_url['attributes']['request_timeout'],
						'http_method'         => $monitor_url['attributes']['http_method'],
						'recovery_period'     => $monitor_url['attributes']['recovery_period'],
						'domain_expiration'   => $monitor_url['attributes']['domain_expiration'],
						'verify_ssl'          => $monitor_url['attributes']['verify_ssl'],
						'ssl_expiration'      => $monitor_url['attributes']['ssl_expiration'],
						'confirmation_period' => $monitor_url['attributes']['confirmation_period'],
						'request_body'        => $monitor_url['attributes']['request_body'],
						'request_headers'     => ! empty( $monitor_url['attributes']['request_headers'] ) ? json_encode( $monitor_url['attributes']['request_headers'] ) : '',
						'regions'             => ! empty( $monitor_url['attributes']['regions'] ) ? json_encode( $monitor_url['attributes']['regions'] ) : '',
						'follow_redirects'    => $monitor_url['attributes']['follow_redirects'],
						'call'                => $monitor_url['attributes']['call'],
						'sms'                 => $monitor_url['attributes']['sms'],
						'email'               => $monitor_url['attributes']['email'],
						'push'                => $monitor_url['attributes']['push'],
						'team_wait'           => $monitor_url['attributes']['team_wait'],
						'last_checked_at'     => $monitor_url['attributes']['last_checked_at'],
					);
					MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $just_insertupdate_id, $url_options );

					// save status.
					$opts_value = array(
						'status' => $monitor_url['attributes']['status'],
					);
					MainWP_AUM_DB::instance()->save_monitor_url_options( $just_insertupdate_id, $opts_value );

					$saved_ids[] = $just_insertupdate_id;
			}
		}
		return $saved_ids;
	}

	/**
	 * Method get_overal_monitors_status()
	 *
	 * Gets overal monitor status.
	 *
	 * @return array $return Monitor status.
	 */
	public function get_overal_monitors_status() {

		$opts = array(
			'default' => true,
		);

		// get monitors status.
		$result = MainWP_AUM_DB::instance()->get_monitor_urls( 'betteruptime', $opts );

		$overal_status = array(
			'down'                => 0,
			'up'                  => 0,
			'trouble'             => 0,
			'critical'            => 0,
			'suspended'           => 0,
			'maintenance'         => 0,
			'discovery'           => 0,
			'configuration_error' => 0,
		);

		if ( $result ) {
			foreach ( $result as $val ) {
				if ( 0 == $val->status ) {
					$overal_status['down']++;
				} elseif ( 1 == $val->status ) {
					$overal_status['up']++;
				} elseif ( 2 == $val->status ) {
					$overal_status['trouble']++;
				} elseif ( 3 == $val->status ) {
					$overal_status['critical']++;
				} elseif ( 5 == $val->status ) {
					$overal_status['suspended']++;
				} elseif ( 7 == $val->status ) {
					$overal_status['maintenance']++;
				} elseif ( 9 == $val->status ) {
					$overal_status['discovery']++;
				} elseif ( 10 == $val->status ) {
					$overal_status['configuration_error']++;
				}
			}
		}
		return array( 'overal_status' => $overal_status );
	}


}
