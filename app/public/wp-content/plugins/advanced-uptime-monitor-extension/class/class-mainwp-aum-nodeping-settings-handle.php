<?php
/**
 * MainWP AUM NodePing Settings Handle.
 *
 * Handles NodePing Settigns.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_NodePing_Settings_Handle
 *
 * Handles NodePing Settigns.
 */
class MainWP_AUM_NodePing_Settings_Handle {
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
	 * @return object $instance Class instance.
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
	 * Handles post saving process.
	 *
	 * @param array $data Post data.
	 *
	 * @return bool True on success.
	 */
	public function handle_post_saving( $data ) {

		$options = array(
			'api_token' => isset( $data['api_token'] ) ? sanitize_text_field( $data['api_token'] ) : '',
		);
		MainWP_AUM_NodePing_API::instance()->update_options( $options );

		$this->reload_monitor_urls();

		return true;
	}

	/**
	 * Method: reload_monitor_urls()
	 *
	 * Reloads monitors URLs.
	 */
	public function reload_monitor_urls() {

		$count    = 0;
		$monitors = $this->get_api_monitors();

		if ( is_array( $monitors ) && ( count( $monitors ) > 0 ) ) {
			$current_urls        = MainWP_AUM_DB::instance()->get_monitor_urls( 'nodeping' );
			$current_monitor_ids = array();
			if ( is_array( $current_urls ) && ( count( $current_urls ) > 0 ) ) {
				foreach ( $current_urls as $mo ) {
					if ( ! empty( $mo->url_address ) ) {
						$current_monitor_ids[] = $mo->monitor_id;
					}
				}
			}
			$results_uptime_monitor_ids = array();
			$this->save_api_monitors( $monitors );
			foreach ( $monitors as $mo ) {
				$results_uptime_monitor_ids[] = $mo['_id'];
			}
			$diff_ids = array_diff( $current_monitor_ids, $results_uptime_monitor_ids );
			if ( is_array( $diff_ids ) && count( $diff_ids ) > 0 ) {
				foreach ( $diff_ids as $mo_id ) {
					MainWP_AUM_DB::instance()->delete_db_monitor( 'monitor_id', $mo_id, 'nodeping' );
				}
			}
		}
	}

	/**
	 * Method: save_api_monitors()
	 *
	 * Saves API monitors.
	 *
	 * @param array $urls_monitor Monitors.
	 *
	 * @return array Saved monitors IDs.
	 */
	public function save_api_monitors( $urls_monitor ) {
		$saved_ids = array();
		foreach ( $urls_monitor as $customer_id => $monitor_url ) {

			$url = isset( $monitor_url['parameters']['target'] ) ? $monitor_url['parameters']['target'] : '';

			if ( empty( $url ) ) {
				continue;
			}

			$current = MainWP_AUM_DB::instance()->get_monitor_by( 'url_address', $url, 'nodeping', OBJECT, false );

			$data = array(
				'url_name'    => isset( $monitor_url['label'] ) ? $monitor_url['label'] : $url,
				'url_address' => $url,
				'monitor_id'  => $monitor_url['_id'],
				'service'     => 'nodeping',
			);

			if ( ! empty( $current ) ) {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->update_monitor_url( $data, $current->url_id );
			} else {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->insert_monitor_url( $data );
			}

			if ( ! empty( $just_insertupdate_id ) ) {

				$notify_contacts = array();
				$notifications   = isset( $monitor_url['notifications'] ) ? $monitor_url['notifications'] : array();

				if ( is_array( $notifications ) ) {
					foreach ( $notifications as $noti_items ) {
						foreach ( $noti_items as $contact => $val ) {
							$notify_contacts[] = array(
								'contact'  => $contact,
								'delay'    => $val['delay'],
								'schedule' => $val['schedule'],
							);
						}
					}
				}

				$url_options = array(
					'monitor_interval' => $monitor_url['interval'],
					'monitor_type'     => $monitor_url['type'],
					'state'            => $monitor_url['state'],
					'enable'           => $monitor_url['enable'],
					'customer_id'      => $customer_id,
					'notify_contacts'  => ! empty( $notify_contacts ) ? wp_json_encode( $notify_contacts ) : '',
					'http_method'      => isset( $monitor_url['method'] ) ? $monitor_url['method'] : '',
					'sensitivity'      => isset( $monitor_url['parameters']['sens'] ) ? $monitor_url['parameters']['sens'] : 2,
					'timeout'          => isset( $monitor_url['parameters']['timeout'] ) ? $monitor_url['parameters']['timeout'] : 5,
					'dependency'       => isset( $monitor_url['dep'] ) ? $monitor_url['dep'] : '',
					'region'           => isset( $monitor_url['runlocations'] ) && is_array( $monitor_url['runlocations'] ) ? $monitor_url['runlocations'][0] : '',
					'location'         => isset( $monitor_url['homeloc'] ) ? $monitor_url['homeloc'] : 'none',
				);

				MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $just_insertupdate_id, $url_options );
				$saved_ids[] = $just_insertupdate_id;
			}
		}
		return $saved_ids;
	}

	/**
	 * Method: get_api_monitors()
	 *
	 * Gets API monitors.
	 *
	 * @return array Monitors.
	 */
	public function get_api_monitors() {

		$timeout = 3 * 60 * 60;
		set_time_limit( $timeout );
		ini_set( 'max_execution_time', $timeout );

		$result = MainWP_AUM_Nodeping_API::instance()->get_all_monitors();  // to reload urls.

		if ( ! empty( $result ) ) {
			$result = json_decode( $result, true );
		}

		if ( is_array( $result ) ) {
			return $result;
		} else {
			update_option( 'mainwp_aum_uptime_robot_message', __( 'NodePing monitor undefined error', 'advanced-uptime-monitor-extension' ) );
		}
		return false;
	}

	/**
	 * Method: get_overal_monitors_status()
	 *
	 * Gets monitor overal status.
	 *
	 * @return string Status.
	 */
	public function get_overal_monitors_status() {

		$opts = array(
			'default' => true,
		);

		// get monitors status.
		$result = MainWP_AUM_DB::instance()->get_monitor_urls( 'nodeping', $opts );

		$overal_status = array(
			'inactive' => 0,
			'pass'     => 0,
			'fail'     => 0,
		);

		$count = 0;
		if ( $result ) {
			foreach ( $result as $val ) {
				// monitor status: 0 - paused, 1 - not checked yet, 2 - up, 8 - seems down, 9 - down.
				if ( 'inactive' == $val->enable ) {
					$overal_status['inactive']++;
				} elseif ( $val->state == 1 ) {
					$overal_status['pass']++;
				} elseif ( $val->state == 0 ) {
					$overal_status['fail']++;
				}
			}
		}

		return array( 'overal_status' => $overal_status );
	}

}
