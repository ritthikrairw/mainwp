<?php

/**
 * iThemes IPCheck API Wrapper.
 *
 * Provides static calls to the iThemes IPCheck API
 *
 * @package iThemes_Security
 *
 * @since   4.5
 *
 */
class MainWP_ITSEC_IPCheck {

	private $endpoint = 'http://ipcheck-api.ithemes.com/?action=';
	private $settings;

	function run() {

		$this->settings = get_site_option( 'itsec_ipcheck' );

		//Execute API Brute force protection
		if ( isset( $this->settings['api_ban'] ) && $this->settings['api_ban'] === true ) {
			add_filter( 'mainwp_itsec_logger_modules', array( $this, 'itsec_logger_modules' ) );
		}

	}



	/**
	 * Set transient for caching IPs
	 *
	 * @since 4.5
	 *
	 * @param string $ip     IP Address
	 * @param bool   $status if the IP is blocked or not
	 * @param int    $time   length, in seconds, to cache
	 *
	 * @return void
	 */
	private function cache_ip( $ip, $status, $time ) {

		//@todo one size fits all is too long. Need to adjust time
		set_site_transient( 'itsec_ip_cache_' . esc_sql( $ip ), $status, $time );

	}

	/**
	 * IP to check for blacklist
	 *
	 * @since 4.5
	 *
	 * @param string|null $ip ip to report
	 *
	 * @return bool true if successfully reported else false
	 */
	public function check_ip( $ip = null ) {

		global $mainwp_itsec_globals, $mainwp_itsec_logger;

		//get current IP if needed
		if ( $ip === null ) {

			$ip = MainWP_ITSEC_Lib::get_ip();

		} else {

			$ip = trim( sanitize_text_field( $ip ) );

		}

		if ( MainWP_ITSEC_Lib::is_ip_whitelisted( $ip ) ) {
			return false;
		}

		//See if we've checked this IP in the last hour
		$cache_check = get_site_transient( 'itsec_ip_cache_' . esc_sql( $ip ) );

		if ( is_array( $cache_check ) && isset( $cache_check['status'] ) ) {
			return $cache_check['status'];
		}

		$action = 'check-ip';

		if ( MainWP_ITSEC_Lib_IP_Tools::validate( $ip ) ) { //verify IP address is valid

			if ( ! isset( $this->settings['api_key'] ) || ! isset( $this->settings['api_secret'] ) ) {
				return false; //invalid key or secret
			}

			$args = json_encode(
				array(
					'apikey'    => $this->settings['api_key'], //the api key
					'behavior'  => 'brute-force-login', //type of behanvior we're reporting
					'ip'        => $ip, //the ip to report
					'site'      => home_url( '', 'http' ), //the current site URL
					'timestamp' => $mainwp_itsec_globals['current_time_gmt'], //current time (GMT)
				)
			);

			//Build the request parameters
			$request = array(
				'body' => array(
					'request'   => $args,
					'signature' => $this->hmac_sha1( $this->settings['api_secret'], $action . $args ),
				),
			);

			$response = wp_remote_post( $this->endpoint . $action, $request );

			//Make sure the request was valid and has a valid body
			if ( ! is_wp_error( $response ) && isset( $response['body'] ) ) {

				$response = json_decode( $response['body'], true );

				if ( is_array( $response ) && isset( $response['success'] ) && $response['success'] == true ) {

					$cache = isset( $response['cache_ttl'] ) ? absint( $response['cache_ttl'] ) : 3600;

					if ( isset( $response['block'] ) && $response['block'] == true ) {

						$expiration     = date( 'Y-m-d H:i:s', $mainwp_itsec_globals['current_time'] + $cache );
						$expiration_gmt = date( 'Y-m-d H:i:s', $mainwp_itsec_globals['current_time_gmt'] + $cache );

						$mainwp_itsec_logger->log_event( __( 'lockout', 'l10n-mainwp-ithemes-security-extension' ), 10, array(
							'expires' => $expiration, 'expires_gmt' => $expiration_gmt, 'type' => 'host'
						), $ip );

						$this->cache_ip( $ip, array( 'status' => true ), $cache );

						return true; //API reports IP is blocked

					} else {

						$this->cache_ip( $ip, array( 'status' => false ), $cache );

						return false; //API reports IP is not blocked or no report (default to no block)

					}

				}

			}

		}

		return false;

	}

	/**
	 * Calculates the HMAC of a string using SHA1.
	 *
	 * there is a native PHP hmac function, but we use this one for
	 * the widest compatibility with older PHP versions
	 *
	 * @param   string $key  the shared secret key used to generate the mac
	 * @param   string $data data to be signed
	 *
	 *
	 * @return  string    base64 encoded hmac
	 */
	private function hmac_sha1( $key, $data ) {

		if ( strlen( $key ) > 64 ) {
			$key = pack( 'H*', sha1( $key ) );
		}

		$key = str_pad( $key, 64, chr( 0x00 ) );

		$ipad = str_repeat( chr( 0x36 ), 64 );

		$opad = str_repeat( chr( 0x5c ), 64 );

		$hmac = pack( 'H*', sha1( ( $key ^ $opad ) . pack( 'H*', sha1( ( $key ^ $ipad ) . $data ) ) ) );

		return base64_encode( $hmac );

	}

	/**
	 * Register IPCheck for logger
	 *
	 * @since 4.5
	 *
	 * @param  array $logger_modules array of logger modules
	 *
	 * @return array                   array of logger modules
	 */
	public function itsec_logger_modules( $logger_modules ) {

		$logger_modules['ipcheck'] = array(
			'type'     => 'ipcheck',
			'function' => __( 'IP Flagged as bad by iThemes IPCheck', 'l10n-mainwp-ithemes-security-extension' ),
		);

		return $logger_modules;

	}

	/**
	 * Send offending IP to IPCheck API
	 *
	 * @since 4.5
	 *
	 * @param string|null $ip   ip to report
	 * @param int         $type type of behavior to report
	 *
	 * @return int -1 on failure, 0 if report successful and IP not blocked, 1 if IP successful and IP blocked
	 */
	public function report_ip( $ip = null, $type = 1 ) {

		global $mainwp_itsec_globals, $mainwp_itsec_logger;

		$action = 'report-ip';

		/**
		 * Switch types or return false if no valid type
		 *
		 * Valid types:
		 * 1 = invalid/failed login
		 *
		 */
		switch ( $type ) {

			case 1:
				$behavior = 'brute-force-login';
				break;
			default:
				return -1;

		}

		//get current IP if needed
		if ( $ip === null ) {

			$ip = MainWP_ITSEC_Lib::get_ip();

		} else {

			$ip = trim( sanitize_text_field( $ip ) );

		}

		if ( MainWP_ITSEC_Lib::is_ip_whitelisted( $ip ) ) {
			return 0;
		}

		if ( MainWP_ITSEC_Lib_IP_Tools::validate( $ip ) ) { //verify IP address is valid

			if ( ! isset( $this->settings['api_key'] ) || ! isset( $this->settings['api_secret'] ) ) {
				return -1; //invalid key or secret
			}

			$args = json_encode(
				array(
					'apikey'    => $this->settings['api_key'], //the api key
					'behavior'  => $behavior, //type of behanvior we're reporting
					'ip'        => $ip, //the ip to report
					'site'      => home_url( '', 'http' ), //the current site URL
					'timestamp' => $mainwp_itsec_globals['current_time_gmt'], //current time (GMT)
				)
			);

			//Build the request parameters
			$request = array(
				'body' => array(
					'request'   => $args,
					'signature' => $this->hmac_SHA1( $this->settings['api_secret'], $action . $args ),
				),
			);

			$response = wp_remote_post( $this->endpoint . $action, $request );

			//Make sure the request was valid and has a valid body
			if ( ! is_wp_error( $response ) && isset( $response['body'] ) ) {

				$response = json_decode( $response['body'], true );

				if ( is_array( $response ) && isset( $response['success'] ) && $response['success'] == true ) {

					if ( isset( $response['block'] ) && $response['block'] == true ) {

						$cache = isset( $response['cache_ttl'] ) ? absint( $response['cache_ttl'] ) : 3600;

						$expiration     = date( 'Y-m-d H:i:s', $mainwp_itsec_globals['current_time'] + $cache );
						$expiration_gmt = date( 'Y-m-d H:i:s', $mainwp_itsec_globals['current_time_gmt'] + $cache );

						$mainwp_itsec_logger->log_event( __( 'lockout', 'l10n-mainwp-ithemes-security-extension' ), 10, array(
							'expires' => $expiration, 'expires_gmt' => $expiration_gmt, 'type' => 'host'
						), $ip );

						$this->cache_ip( $ip, array( 'status' => true ), $cache );

						return 1; //ip report success. Just return true for now

					} else {

						return 0;

					}

				}

			}

		}

		return -1;

	}

	
}
