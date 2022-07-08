<?php
/**
 * MainWP Site24x7 Settings
 *
 * Handles Site24x7 settings.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class class MainWP_AUM_Site24x7_Settings_Handle
 *
 * Site24x7 settings handle
 */
class MainWP_AUM_Site24x7_Settings_Handle {

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_Site24x7_Settings_Handle.
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

		$endpoint = isset( $data['endpoint'] ) ? sanitize_text_field( $data['endpoint'] ) : '';
		$options  = array(
			'endpoint'      => isset( $data['endpoint'] ) ? sanitize_text_field( $data['endpoint'] ) : '',
			'client_id'     => isset( $data['client_id'] ) ? sanitize_text_field( $data['client_id'] ) : '',
			'client_secret' => isset( $data['client_secret'] ) ? sanitize_text_field( $data['client_secret'] ) : '',
		);
		MainWP_AUM_Site24x7_API::instance()->update_options( $options );

		$expires_time = MainWP_AUM_Site24x7_API::instance()->get_scope_auth_option( 'expires_time', 'admin' );

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
	public function get_api_monitors() {

		$result = MainWP_AUM_Site24x7_API::instance()->get_all_monitors();  // to reload urls.

		if ( ! empty( $result ) ) {
			$result = json_decode( $result, true );
		}

		if ( is_array( $result ) && isset( $result['data'] ) ) {
			$result       = $result['data'];
			$monitor_urls = array();
			foreach ( $result as $mo ) {
				if ( 'URL' == $mo['type'] ) {
					$monitor_urls[] = $mo;
				}
			}
			return $monitor_urls;
		} else {
			update_option( 'mainwp_aum_uptime_robot_message', __( 'Site24x7 monitor undefined error', 'advanced-uptime-monitor-extension' ) );
		}
		return false;
	}

	/**
	 * Method: reload_monitor_urls()
	 *
	 * Reloads monitors.
	 */
	public function reload_monitor_urls() {
		$count    = 0;
		$monitors = $this->get_api_monitors(); // saving options.

		if ( is_array( $monitors ) && ( count( $monitors ) > 0 ) ) {
			$current_urls        = MainWP_AUM_DB::instance()->get_monitor_urls( 'site24x7' );
			$current_monitor_ids = array();
			if ( is_array( $current_urls ) && ( count( $current_urls ) > 0 ) ) {
				foreach ( $current_urls as $mo ) {
					if ( ! empty( $mo->url_address ) ) {
						$current_monitor_ids[] = $mo->monitor_id;
					}
				}
			}
			$results_uptime_monitor_ids = array();
			$this->save_api_monitors( $monitors ); // saving site24x7 monitors.
			foreach ( $monitors as $mo ) {
				$results_uptime_monitor_ids[] = $mo['monitor_id'];
			}
			$diff_ids = array_diff( $current_monitor_ids, $results_uptime_monitor_ids );
			if ( is_array( $diff_ids ) && count( $diff_ids ) > 0 ) {
				foreach ( $diff_ids as $mo_id ) {
					// delete missing items.
					MainWP_AUM_DB::instance()->delete_db_monitor( 'monitor_id', $mo_id, 'site24x7' );
				}
			}
		}
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

			if ( ! isset( $monitor_url['website'] ) ) {
				continue;
			}

			$current = MainWP_AUM_DB::instance()->get_monitor_by( 'url_address', $monitor_url['website'], 'site24x7', OBJECT, false );

			$data = array(
				'url_name'    => $monitor_url['display_name'],
				'url_address' => $monitor_url['website'],
				'monitor_id'  => $monitor_url['monitor_id'],
				'service'     => 'site24x7',
			);

			if ( ! empty( $current ) ) {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->update_monitor_url( $data, $current->url_id );
			} else {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->insert_monitor_url( $data );
			}

			if ( ! empty( $just_insertupdate_id ) ) {
					$user_groupids    = ! empty( $monitor_url['user_group_ids'] ) ? implode( ',', $monitor_url['user_group_ids'] ) : '';
					$monitor_groupids = ! empty( $monitor_url['monitor_groups'] ) ? implode( ',', $monitor_url['monitor_groups'] ) : '';

					$url_options = array(
						'monitor_interval'        => $monitor_url['check_frequency'],
						'monitor_type'            => $monitor_url['type'],
						'state'                   => $monitor_url['state'],
						'http_username'           => $monitor_url['auth_user'],
						'http_password'           => $monitor_url['auth_pass'],
						'timeout'                 => $monitor_url['timeout'],
						'location_profile_id'     => $monitor_url['location_profile_id'],
						'notification_profile_id' => $monitor_url['notification_profile_id'],
						'threshold_profile_id'    => $monitor_url['threshold_profile_id'],
						'user_group_ids'          => $user_groupids,
						'http_method'             => $monitor_url['http_method'],
						'monitor_group_ids'       => $monitor_groupids,
					);

					MainWP_AUM_DB::instance()->save_monitor_url_options( $just_insertupdate_id, $url_options );
					$saved_ids[] = $just_insertupdate_id;
			}
		}
		return $saved_ids;
	}

	/**
	 * Method: handle_oauth_redirect()
	 *
	 * Handles oAuth redirection.
	 *
	 * @return bool true|false
	 */
	public function handle_oauth_redirect() {
		if ( isset( $_GET['page'] ) && 'Extensions-Advanced-Uptime-Monitor-Extension' == $_GET['page'] ) {

			if ( isset( $_GET['location'] ) && isset( $_GET['code'] ) ) {
				$opts = array(
					'code'     => sanitize_text_field( $_GET['code'] ),
					'location' => sanitize_text_field( $_GET['location'] ),
				);

				$auth_scope = get_option( 'mainwp_aum_go_auth_scope' );
				delete_option( 'mainwp_aum_go_auth_scope' );

				MainWP_AUM_Site24x7_API::instance()->update_options( $opts );
				// get access and refresh token right now.
				$result = MainWP_AUM_Site24x7_API::instance()->get_access_and_refresh_token();

				if ( ! empty( $result ) ) {
					$data = json_decode( $result, true );

					if ( is_array( $data ) && isset( $data['access_token'] ) ) {
						if ( 'admin' == $auth_scope || 'report' == $auth_scope || 'operations' == $auth_scope ) {
							$opts    = array(
								'api_domain'    => isset( $data['api_domain'] ) ? $data['api_domain'] : '',
								'token_type'    => $data['token_type'],
								'expires_time'  => time() + $data['expires_in'],
								'access_token'  => $data['access_token'],
								'refresh_token' => $data['refresh_token'],
							);
							$current = MainWP_AUM_Site24x7_API::instance()->get_option( 'auth_settings', array() );
							if ( ! is_array( $current ) ) {
								$current = array();
							}
							$current[ $auth_scope ] = $opts;
							$auth_settings          = array(
								'auth_settings' => $current,
							);
							MainWP_AUM_Site24x7_API::instance()->update_options( $auth_settings );
							return true;
						}
					}
				}
			}
		}
		return false;
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
		$result = MainWP_AUM_DB::instance()->get_monitor_urls( 'site24x7', $opts );

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

		$count = 0;
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
