<?php
/**
 * MainWP Uptime Robot API
 *
 * Handles the Uptime Robot API.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Uptime_Robot
 *
 * Handles the Uptime Robot API.
 */
class MainWP_AUM_UptimeRobot_API extends MainWP_AUM_Settings_Base {

	/**
	 * ID of the class extending the settings API. Used in option names.
	 *
	 * @var string
	 */
	public static $setting_id = 'uptimerobot';


	private $api_uptime_uri = 'https://api.uptimerobot.com/v2';
	public $api_key         = null;
	private $format         = 'json';
	private $json_encap     = 'jsonUptimeRobotApi()';

	/**
	 * Private static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Create public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_AUM_Uptime_Robot
	 */
	public static function instance() {
		if ( null == self::$instance ) {
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
	 * Returns the name of the option in the WP DB.
	 *
	 * @return string
	 */
	protected static function get_settings_id() {
		return self::$setting_id;
	}


	/**
	 * Gets API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->get_option( 'api_key' );
	}

	/**
	 * Returns the result of the API calls.
	 *
	 * @param mixed $url required
	 */
	private function fetch_url( $url, $post_fields = '' ) {

		if ( empty( $url ) ) {
			throw new \Exception( 'Value not specified: url', 1 );
		}
		$url = trim( $url );
		$ch  = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_ENCODING, '' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 240 );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields );
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'cache-control: no-cache',
				'content-type: application/x-www-form-urlencoded',
			)
		);
		$ssl_verifyhost = apply_filters( 'mainwp_aum_verify_certificate', true );
		if ( ! $ssl_verifyhost ) {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		}

		$file_contents = curl_exec( $ch );
		if ( 'resource' === gettype( $ch ) ) {
			curl_close( $ch );
		}
		switch ( $this->format ) {
			case 'xml':
				return $file_contents;
			default:
				if ( strpos( $file_contents, 'UptimeRobotApi' ) == false ) {
					return $file_contents;
				} else {
					return substr( $file_contents, strlen( $this->json_encap ) - 1, strlen( $file_contents ) - strlen( $this->json_encap ) );
				}
		}
		return false;
	}

	/**
	 * This is a Swiss-Army knife type of a method for getting any information on monitors.
	 *
	 * @param array $monitors        optional (if not used, will return all monitors in an account.
	 *                               Else, it is possible to define any number of monitors with their IDs like: monitors=15830-32696-83920).
	 * @param bool  $logs             optional (defines if the logs of each monitor will be returned. Should be set to 1 for getting the logs. Default is 0).
	 * @param bool  $alertContacts    optional (defines if the notified alert contacts of each notification will be returned.
	 *                                Should be set to 1 for getting them. Default is 0. Requires logs to be set to 1).
	 */
	public function get_monitors( $monitors = array(), $logs = 0, $alertContacts = 0, $allRatio = null, $customRatio = null ) {

		if ( empty( $this->get_api_key() ) ) {
			throw new \Exception( 'Property not set: api_key', 2 );
		}
		$url         = "{$this->api_uptime_uri}/getMonitors";
		$post_fields = 'api_key=' . $this->get_api_key();
		if ( ! empty( $monitors ) ) {
			$post_fields .= '&monitors=' . implode( '-', $monitors );
		}
		if ( ! empty( $allRatio ) ) {
			$post_fields .= '&all_time_uptime_ratio=1';
		}

		if ( ! empty( $customRatio ) ) {
			$post_fields .= '&custom_uptime_ratios=' . $customRatio;
		}

		$post_fields .= "&logs=$logs&alert_contacts=$alertContacts&format={$this->format}";
		return $this->fetch_url( $url, $post_fields );
	}

	public function get_all_monitors( $offset = 0 ) {

		if ( empty( $this->get_api_key() ) ) {
			throw new \Exception( 'Property not set: api_key', 2 );
		}

		$url          = "{$this->api_uptime_uri}/getMonitors";
		$post_fields  = 'api_key=' . $this->get_api_key() . '&offset=' . $offset;
		$post_fields .= "&logs=0&alert_contacts=0&format={$this->format}";

		$result = $this->fetch_url( $url, $post_fields );
		$result = str_replace( ',]', ']', $result ); // fix json.
		$result = str_replace( '[,', '[', $result ); // fix json.
		$result = str_replace( ',,', ',', $result ); // fix json.

		$result  = json_decode( $result );
		$limit   = MAINWP_MONITOR_API_LIMIT_PER_PAGE;
		$results = array();
		// if ( is_object( $result ) && $result->pagination->total > $limit ) {
		// $total = $result->pagination->total;
		// while ( $result ) {
		// $results[] = $result;
		// if ( $offset + $limit >= $total ) {
		// break;
		// }
		// $offset      += $limit;
		// $url          = "{$this->api_uptime_uri}/getMonitors";
		// $post_fields  = 'api_key=' . $this->get_api_key() . '&offset=' . $offset;
		// $post_fields .= "&logs=0&alert_contacts=0&format={$this->format}";
		// $result       = $this->fetch_url( $url, $post_fields );
		// $result       = json_decode( $result );
		// }
		// } else {
			 $results[] = $result;
		// }
		return $results;
	}

	// Get Alert Contacts.
	public function get_contacts() {
		if ( empty( $this->get_api_key() ) ) {
			throw new \Exception( 'Property not set: api_key', 2 );
		}
		$url          = "{$this->api_uptime_uri}/getAlertContacts";
		$post_fields  = 'api_key=' . $this->get_api_key();
		$post_fields .= "&format={$this->format}";
		return $this->fetch_url( $url, $post_fields );
	}

	public function get_ur_gmt_offset_time() {
		$url          = $this->api_uptime_uri . '/getMonitors';
		$post_fields  = 'api_key=' . $this->get_api_key();
		$post_fields .= '&format=json&timezone=1';
		$results      = $this->fetch_url( $url, $post_fields );
		$content      = json_decode( $results );
		if ( $content && $content->stat = 'ok' ) {
			return $this->get_gmt_offset_time( $content->timezone );
		} else {
			return false;
		}
	}

	function get_gmt_offset_time( $time_zone ) {
		if ( '-720' == $time_zone ) {
			return array(
				'offset_time' => -12,
				'text'        => 'Yankee Timezone',
			);
		}
		if ( '-660' == $time_zone ) {
			return array(
				'offset_time' => -11,
				'text'        => 'Midway Island, Samoa',
			);
		}
		if ( '-600' == $time_zone ) {
			return array(
				'offset_time' => -10,
				'text'        => 'Hawaii',
			);
		}
		if ( '-480' == $time_zone ) {
			return array(
				'offset_time' => -8,
				'text'        => 'Alaska',
			);
		}
		if ( '-420' == $time_zone ) {
			return array(
				'offset_time' => -7,
				'text'        => 'Pacific Time (US &amp; Canada)',
			);
		}
		if ( '-360' == $time_zone ) {
			return array(
				'offset_time' => -6,
				'text'        => 'Mountain Time (US &amp; Canada)',
			);
		}
		if ( '-300' == $time_zone ) {
			return array(
				'offset_time' => -5,
				'text'        => 'Central Time (US & Canada), Mexico City',
			);
		}
		if ( '-240' == $time_zone ) {
			return array(
				'offset_time' => -4,
				'text'        => 'Eastern Time (US & Canada), Bogota, Lima, Atlantic Time (Canada), La Paz',
			);
		}
		if ( '-270' == $time_zone ) {
			return array(
				'offset_time' => -4.5,
				'text'        => 'Caracas-Venezuela',
			);
		}
		if ( '-150' == $time_zone ) {
			return array(
				'offset_time' => -2.5,
				'text'        => 'Newfoundland',
			);
		}
		if ( '-180' == $time_zone ) {
			return array(
				'offset_time' => -3,
				'text'        => 'Brazil, Buenos Aires, Georgetown',
			);
		}
		if ( '-120' == $time_zone ) {
			return array(
				'offset_time' => -2,
				'text'        => 'Mid-Atlantic',
			);
		}
		if ( '-60' == $time_zone ) {
			return array(
				'offset_time' => -1,
				'text'        => 'Azores, Cape Verde Islands',
			);
		}
		if ( '+60' == $time_zone ) {
			return array(
				'offset_time' => 1,
				'text'        => 'Western Europe Time, London, Lisbon, Casablanca',
			);
		}
		if ( '+120' == $time_zone ) {
			return array(
				'offset_time' => 2,
				'text'        => 'Brussels, Copenhagen, Madrid, Paris',
			);
		}
		if ( '+180' == $time_zone ) {
			return array(
				'offset_time' => 3,
				'text'        => 'Istanbul, Kaliningrad, Athens ,Baghdad, Riyadh',
			);
		}
		if ( '+270' == $time_zone ) {
			return array(
				'offset_time' => 4.5,
				'text'        => 'Tehran , Kabul',
			);
		}
		if ( '+300' == $time_zone ) {
			return array(
				'offset_time' => 5,
				'text'        => 'Ekaterinburg, Islamabad, Karachi, Tashkent',
			);
		}
		if ( '+330' == $time_zone ) {
			return array(
				'offset_time' => 5.5,
				'text'        => 'Bombay, Calcutta, Madras, New Delhi',
			);
		}
		if ( '+345' == $time_zone ) {
			return array(
				'offset_time' => 5.75,
				'text'        => 'Kathmandu',
			);
		}
		if ( '+360' == $time_zone ) {
			return array(
				'offset_time' => 6,
				'text'        => 'Almaty, Dhaka, Colombo',
			);
		}
		if ( '+420' == $time_zone ) {
			return array(
				'offset_time' => 7,
				'text'        => 'Bangkok, Hanoi, Jakarta',
			);
		}
		if ( '+480' == $time_zone ) {
			return array(
				'offset_time' => 8,
				'text'        => 'Beijing, Perth, Singapore, Hong Kong',
			);
		}
		if ( '+540' == $time_zone ) {
			return array(
				'offset_time' => 9,
				'text'        => 'Tokyo, Seoul, Osaka, Sapporo, Yakutsk',
			);
		}
		if ( '+630' == $time_zone ) {
			return array(
				'offset_time' => 10.5,
				'text'        => 'Adelaide, Darwin',
			);
		}
		if ( '+660' == $time_zone ) {
			return array(
				'offset_time' => 11,
				'text'        => 'Nouméa, Solomon Islands',
			);
		}
		if ( '+600' == $time_zone ) {
			return array(
				'offset_time' => 10,
				'text'        => 'Magadan, Solomon Islands, New Caledonia, Eastern Australia, Guam, Vladivostok',
			);
		}
		if ( '+720' == $time_zone ) {
			return array(
				'offset_time' => 12,
				'text'        => 'Auckland, Wellington, Fiji, Kamchatka, Eniwetok, Kwajalein',
			);
		}
		if ( '+780' == $time_zone ) {
			return array(
				'offset_time' => 13,
				'text'        => 'New Zealand Daylight Time, Tonga',
			);
		}
		if ( '+765' == $time_zone ) {
			return array(
				'offset_time' => 12.75,
				'text'        => 'Chatham Islands',
			);
		}
		if ( '-270' == $time_zone ) {
			return array(
				'offset_time' => -4.5,
				'text'        => 'Caracas-Venezuela',
			);
		}
		if ( '+120' == $time_zone ) {
			return array(
				'offset_time' => 2,
				'text'        => 'South African Standard Time',
			);
		}
		if ( '+180' == $time_zone ) {
			return array(
				'offset_time' => 3,
				'text'        => 'Moscow, St. Petersburg',
			);
		}
		if ( '+240' == $time_zone ) {
			return array(
				'offset_time' => 4,
				'text'        => 'Samara, Abu Dhabi, Muscat, Baku, Tbilisi',
			);
		}
		if ( '+0' == $time_zone ) {
			return array(
				'offset_time' => 0,
				'text'        => 'GMT/UTC',
			);
		}
		if ( '+660' == $time_zone ) {
			return array(
				'offset_time' => 11,
				'text'        => 'Sydney, Melbourne',
			);
		}
	}

	/**
	 * New monitors of any type can be created using this method
	 *
	 * @param array $params
	 *
	 * $params can have the following keys:
	 *    name           - required
	 *    uri            - required
	 *    type           - required
	 *    subtype        - optional (required for port monitoring)
	 *    port           - optional (required for port monitoring)
	 *    keyword_type   - optional (required for keyword monitoring)
	 *    keyword_value  - optional (required for keyword monitoring)
	 */
	public function new_monitor( $params = array() ) {

		if ( empty( $params['name'] ) || empty( $params['uri'] ) || empty( $params['type'] ) ) {
			throw new \Exception( 'Required key "name", "uri" or "type" not specified', 3 );
		} else {
			extract( $params );
		}
		if ( empty( $this->get_api_key() ) ) {
			throw new \Exception( 'Property not set: api_key', 2 );
		}
		$url = "{$this->api_uptime_uri}/newMonitor";

		if ( ! isset( $params['monitorAlertContacts'] ) ) {
			$post_fields = 'api_key=' . $this->get_api_key() . '&friendly_name=' . urlencode( $name ) . "&url=$uri&type=$type";
		} else {
			$post_fields = 'api_key=' . $this->get_api_key() . '&friendly_name=' . urlencode( $name ) . "&url=$uri&type=$type&alert_contacts=$monitorAlertContacts";
		}

		if ( isset( $subtype ) ) {
			$post_fields .= "&sub_type=$subtype"; }
		if ( isset( $port ) ) {
			$post_fields .= "&port=$port"; }
		if ( isset( $keyword_type ) ) {
			$post_fields .= "&keyword_type=$keyword_type"; }
		if ( isset( $keyword_value ) ) {
			$post_fields .= '&keyword_value=' . urlencode( $keyword_value ); }
		if ( isset( $monitor_interval ) ) {
			$post_fields .= '&interval=' . urlencode( $monitor_interval * 60 ); }
		if ( isset( $http_username ) ) {
			$post_fields .= '&http_username=' . urlencode( $http_username ); }
		if ( isset( $http_password ) ) {
			$post_fields .= '&http_password=' . urlencode( $http_password ); }
		$post_fields .= "&format={$this->format}";

		return $this->fetch_url( $url, $post_fields );
	}

	/**
	 * monitors can be edited using this method.
	 *
	 * Important: The type of a monitor can not be edited (like changing a HTTP monitor into a Port monitor).
	 * For such cases, deleting the monitor and re-creating a new one is adviced.
	 *
	 * @param string $monitorId required
	 * @param array  $params required
	 *
	 *  $params can have the following keys:
	 *     name           - required
	 *     uri            - required
	 *     type           - required
	 *     subtype        - optional (required for port monitoring)
	 *     port           - optional (required for port monitoring)
	 *     keyword_type   - optional (required for keyword monitoring)
	 *     keyword_value  - optional (required for keyword monitoring)
	 */
	public function api_edit_monitor( $monitorId, $params = array() ) {

		if ( empty( $params ) ) {
			throw new \Exception( 'Value not specified: params', 1 );
		} else {
			extract( $params );
		}
		if ( empty( $this->get_api_key() ) ) {
			throw new \Exception( 'Property not set: api_key', 2 );
		}

		$url         = "{$this->api_uptime_uri}/editMonitor";
		$post_fields = 'api_key=' . $this->get_api_key() . "&id=$monitorId";

		if ( isset( $name ) ) {
			$post_fields .= '&friendly_name=' . urlencode( $name ); }
		if ( isset( $status ) ) {
			$post_fields .= "&status=$status"; }
		if ( isset( $uri ) ) {
			$post_fields .= "&url=$uri"; }
		if ( isset( $subtype ) ) {
			$post_fields .= "&sub_type=$subtype"; }
		if ( isset( $port ) ) {
			$post_fields .= "&port=$port"; }
		if ( isset( $keyword_type ) ) {
			$post_fields .= "&keyword_type=$keyword_type"; }
		if ( isset( $keyword_value ) ) {
			$post_fields .= '&keyword_value=' . urlencode( $keyword_value ); }
		if ( isset( $monitorAlertContacts ) ) {
			$post_fields .= '&alert_contacts=' . $monitorAlertContacts; }
		if ( isset( $monitor_interval ) ) {
			$post_fields .= '&interval=' . ( $monitor_interval * 60 ); }
		if ( isset( $http_username ) ) {
			$post_fields .= '&http_username=' . urlencode( $http_username ); }
		if ( isset( $http_password ) ) {
			$post_fields .= '&http_password=' . urlencode( $http_password ); }
		$post_fields .= "&format={$this->format}";

		return $this->fetch_url( $url, $post_fields );
	}

	/**
	 * Deletes monitor.
	 *
	 * @param string $monitorId Monitor ID.
	 */
	public function delete_monitor( $monitorId ) {

		if ( empty( $monitorId ) ) {
			throw new \Exception( 'Value not specified: monitorId', 1 );
		}
		if ( empty( $this->get_api_key() ) ) {
			throw new \Exception( 'Property not set: api_key', 2 );
		}

		$url         = "{$this->api_uptime_uri}/deleteMonitor";
		$post_fields = 'api_key=' . $this->get_api_key() . "&id=$monitorId&format={$this->format}";

		return $this->fetch_url( $url, $post_fields );
	}
}
