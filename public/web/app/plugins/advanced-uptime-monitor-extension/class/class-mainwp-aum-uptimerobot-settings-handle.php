<?php
/**
 * MainWP Uptime Robot Settings
 *
 * Handles Uptime Robot settings.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

use stdClass;

/**
 * Class MainWP_AUM_UptimeRobot_Settings_Handle
 *
 * Uptime Robot settings handle.
 */
class MainWP_AUM_UptimeRobot_Settings_Handle {

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_UptimeRobot_Settings_Handle.
	 */
	private static $instance = null;

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
		self::$instance = $this;
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

		$options['api_timezone'] = '';

		$api_key = isset( $data['api_key'] ) ? esc_html( trim( $data['api_key'] ) ) : '';

		$reload = false;

		// All is OK now.
		if ( ! empty( $api_key ) ) {
			MainWP_AUM_UptimeRobot_API::instance()->update_option_field( 'api_key', $api_key ); // set to update.
			$list_contact                                      = MainWP_AUM_UptimeRobot_Controller::instance()->get_notification_contacts( $api_key );
			$options['uptime_default_notification_contact_id'] = isset( $data['select_default_noti_contact'] ) ? wp_unslash( $data['select_default_noti_contact'] ) : 0;
			$options['list_notification_contact']              = $list_contact;

			$off = MainWP_AUM_UptimeRobot_API::instance()->get_ur_gmt_offset_time();
			if ( is_array( $off ) ) {
				$options['api_timezone'] = $off;
			}

			$reload = true;

		} else {
			$options['list_notification_contact'] = '';
			$options['api_key']                   = '';
		}

		MainWP_AUM_UptimeRobot_API::instance()->update_options( $options );

		// will reload monitors.
		return $reload;
	}

	/**
	 * Method: get_uptime_monitors()
	 *
	 * Gets uptime monitors.
	 *
	 * @param string $api_key        API Key.
	 * @param array  $monitor_ids    Monitor IDs.
	 * @param int    $logs           Enable or Disable logs.
	 * @param int    $alertContacts  Alert contacts.
	 * @param int    $allUptimeRatio Uptime ratio.
	 * @param string $customRatio    Custom ratio.
	 *
	 * @return array $results Monitors.
	 */
	public function get_uptime_monitors( $api_key, $monitor_ids = array(), $logs = 0, $alertContacts = 0, $allUptimeRatio = 0, $customRatio = '' ) {

		$valid  = false;
		$result = MainWP_AUM_UptimeRobot_API::instance()->get_monitors( $monitor_ids, $logs, $alertContacts, $allUptimeRatio, $customRatio );

		// place this one first
		while ( false !== strpos( $result, ',,' ) ) {
			$result = str_replace( array( ',,' ), ',', $result ); // fix json.
		}
		$result = str_replace( ',]', ']', $result ); // fix json.
		$result = str_replace( '[,', '[', $result ); // fix json.
		$result = json_decode( $result );

		update_option( 'mainwp_aum_uptime_robot_message', '' );
		if ( empty( $result ) ) {
			update_option( 'mainwp_aum_uptime_robot_message', __( 'Uptime Robot undefined error', 'advanced-uptime-monitor-extension' ) );
		} elseif ( 'fail' == $result->stat ) {
			update_option( 'mainwp_aum_uptime_robot_message', $result->message );
		} else {
			$valid = true;
		}

		if ( isset( $result->id ) && ( 212 == $result->id ) ) {
			update_option( 'mainwp_aum_uptime_robot_message', '' );
			return array();
		}

		if ( $valid ) {
			if ( ! isset( $result->id ) || ( 212 != $result->id ) ) {
				update_option( 'mainwp_aum_uptime_robot_message', '' );
			}
		} else {
			return false;
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
			$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		} else {
			$offset = get_option( 'mainwp_aum_uptime_reload_monitors_offset', 0 );
		}

		$results = self::instance()->get_api_monitors( $offset );

		$valid = false;
		$error = '';
		$total = 0;

		if ( is_array( $results ) && count( $results ) > 0 ) {
			$result = current( $results ); // check first one only.
			if ( is_object( $result ) && property_exists( $result, 'stat' ) ) {
				if ( $result->stat == 'fail' ) {
					$error = $result->message;
				} else {
					$valid = true;
				}
			}
		}

		$next_offset = $offset;
		if ( $valid ) {
			if ( 0 == $offset ) {
				update_option('mainwp_aum_uptime_last_reload_monitors', time());
			}

			if ( is_array( $results ) && count( $results ) > 0 ) {
				foreach ( $results as $result ) {
					if ( is_object( $result ) ) {
						self::instance()->save_api_monitors( $result->monitors );
					}
				}
			}

			$count = count( $result->monitors );
			if ( is_object( $result ) && property_exists( $result, 'pagination' ) && $result->pagination->total > $offset + $count ) {
				$next_offset = $offset + $count;
			} else {
				MainWP_AUM_UptimeRobot_Controller::instance()->check_unavailable_url_monitors();
				$next_offset = 0;
			}
			$total = $result->pagination->total;
		}

		update_option( 'mainwp_aum_uptime_reload_monitors_offset', $next_offset );
		return array(
			'total'  => $total,
			'offset' => $next_offset,
		);
	}


	/**
	 * Method: get_api_monitors()
	 *
	 * Gets Monitors.
	 *
	 * @return array $results Monitors data.
	 */
	public function get_api_monitors( $offset = 0 ) {

		$valid   = false;
		$results = MainWP_AUM_UptimeRobot_API::instance()->get_all_monitors( $offset ); // to reload urls.

		update_option( 'mainwp_aum_uptime_robot_message', '' );

		if ( empty( $results ) ) {
			update_option( 'mainwp_aum_uptime_robot_message', __( 'Uptime Robot undefined error.', 'advanced-uptime-monitor-extension' ) );
		}

		if ( is_array( $results ) && count( $results ) > 0 ) {
			$result = current( $results ); // check first one only.
			if ( is_object( $result ) && property_exists( $result, 'stat' ) ) {
				if ( 'fail' == $result->stat ) {
					update_option( 'mainwp_aum_uptime_robot_message', $result->message );
				} else {
					$valid = true;
				}
			}

			if ( isset( $result->id ) && ( 212 == $result->id ) ) { // api have no monitors.
				update_option( 'mainwp_aum_uptime_robot_message', '' );
				return array();
			}
		}
		if ( $valid ) {
			if ( ! isset( $result->id ) || ( 212 != $result->id ) ) {
				update_option( 'mainwp_aum_uptime_robot_message', '' );
			}
		} else {
			return false;
		}
		return $results;
	}

	/**
	 * Method get_uptime_data()
	 *
	 * Gets uptime data.
	 *
	 * @param string $selected_service Selected service.
	 *
	 * @return array $uptime_data Uptime data.
	 */
	public function get_uptime_data( $selected_service ) {

		$opts = array(
			'default' => true,
		);
		if ( 'uptimerobot' == $selected_service ) {
			$opts['fields'] = array(
				'alltimeuptimeratio',
			);
		}

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls( $selected_service, $opts );
		if ( ! $urls ) {
			return array();
		}

		$uptime_data = array();

		foreach ( $urls as $url ) {
			$url_address = wp_parse_url( $url->url_address, PHP_URL_HOST );
			$url_address = str_replace( array( 'www.', '/' ), array( '', '' ), $url_address );

			$stdObj = new \stdClass();
			if ( 'uptimerobot' == $selected_service ) {
				$stdObj->status             = $url->status;
				$stdObj->alltimeuptimeratio = $url->alltimeuptimeratio;
			} elseif ( 'site24x7' == $selected_service ) {
				$stdObj->state = $url->state;
			} elseif ( 'nodeping' == $selected_service ) {
				$stdObj->enable = $url->enable;
				$stdObj->state  = $url->state;
			} elseif ( 'betteruptime' == $selected_service ) {
				$stdObj->status = $url->status;
			}
			$uptime_data[ $url_address ] = $stdObj;
		}
		return $uptime_data;
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

		if ( ! is_array( $urls_monitor ) || empty( $urls_monitor ) ) {
			return false;
		}
		$saved_ids = array();

		foreach ( $urls_monitor as $monitor_url ) {
			if ( ! property_exists( $monitor_url, 'url' ) ) {
				continue;
			}
			$current = MainWP_AUM_DB::instance()->get_monitor_by( 'url_address', $monitor_url->url, 'uptimerobot', OBJECT, false );

			$data = array(
				'url_name'    => $monitor_url->friendly_name,
				'url_address' => $monitor_url->url,
				'monitor_id'  => $monitor_url->id,
				'service'     => 'uptimerobot',
				'lastupdate'  => time(),
			);

			if ( ! empty( $current ) ) {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->update_monitor_url( $data, $current->url_id );
			} else {
				$just_insertupdate_id = MainWP_AUM_DB::instance()->insert_monitor_url( $data );
			}

			if ( ! empty( $just_insertupdate_id ) ) {
				$url_options = array(
					'monitor_interval' => intval( $monitor_url->interval / 60 ),
					'monitor_type'     => $monitor_url->type,
					'monitor_subtype'  => $monitor_url->sub_type,
					'status'           => $monitor_url->status,
					'keyword_type'     => $monitor_url->keyword_type,
					'keyword_value'    => $monitor_url->keyword_value,
					'http_username'    => $monitor_url->http_username,
					'http_password'    => $monitor_url->http_password,
				);

				if ( property_exists( $monitor_url, 'all_time_uptime_ratio' ) ) {
					$url_options['alltimeuptimeratio'] = $monitor_url->all_time_uptime_ratio;
				}
				MainWP_AUM_DB::instance()->save_monitor_url_options( $just_insertupdate_id, $url_options );
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
		$return = array();
		$opts   = array(
			'fields'  => array(
				'customuptimeratio',
			),
			'default' => true,
		);

		$result = MainWP_AUM_DB::instance()->get_monitor_urls( 'uptimerobot', $opts );

		$overal_ratios = array();
		$overal_status = array(
			'paused'      => 0,
			'not_checked' => 0,
			'up'          => 0,
			'seems_down'  => 0,
			'down'        => 0,
		);
		$count         = 0;
		if ( $result ) {
			foreach ( $result as $val ) {
				$ratios = explode( '-', $val->customuptimeratio );
				if ( is_array( $ratios ) && count( $ratios ) == 6 ) {
					$count++;
					for ( $i = 0; $i < 6; $i++ ) {
						$overal_ratios[ $i ] += $ratios[ $i ];
					}
				}
				// monitor status: 0 - paused, 1 - not checked yet, 2 - up, 8 - seems down, 9 - down.
				if ( 0 == $val->status ) {
					$overal_status['paused']++;
				} elseif ( 1 == $val->status ) {
					$overal_status['not_checked']++;
				} elseif ( 2 == $val->status ) {
					$overal_status['up']++;
				} elseif ( 8 == $val->status ) {
					$overal_status['seems_down']++;
				} elseif ( 9 == $val->status ) {
					$overal_status['down']++;
				}
			}
		}
		if ( $count ) {
			for ( $i = 0; $i < 6; $i++ ) {
				$overal_ratios[ $i ] = number_format( $overal_ratios[ $i ] / $count, 2 );
			}
			$return['overal_ratios'] = $overal_ratios;
		}
		$return['overal_status'] = $overal_status;
		return $return;
	}

}
