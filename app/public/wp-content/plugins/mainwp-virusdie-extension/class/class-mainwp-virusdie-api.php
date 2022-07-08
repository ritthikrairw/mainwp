<?php
/**
 * MainWP Virusdie API
 *
 * This file handles API interactions.
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Virusdie;

/**
 * Class MainWP_Virusdie_API
 */
class MainWP_Virusdie_API extends MainWP_Virusdie_Settings_Base {

	// phpcs:disable WordPress.DB.RestrictedFunctions, WordPress.WP.AlternativeFunctions -- Using cURL functions.

	/**
	 * Public static variable holding the settings ID.
	 *
	 * @static
	 *
	 * @var string
	 */
	public static $setting_id = 'virusdie';

	/**
	 * Private variable holding the Virusdie API URI.
	 *
	 * @var string
	 */
	private $api_url = 'https://virusdie.com/api/';

	/**
	 * Private static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed
	 */
	private static $instance = null;

	/**
	 * Create public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_DB
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Public constructor function
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
	 * Gets Auth key.
	 *
	 * @return string Auth Key.
	 */
	public function get_auth_key() {
		return base64_decode( 'QWRFN25jVTBNcEUxMU04SjZBNkVXd0RqcjMxYzFqS0Z4NFkySGFmVDdKM3p3eFM1N1pocFJ1cWN5dFk1S004SA==' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- safe for hardcode.
	}

	/**
	 * Gets the Client API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->get_option( 'client_api_key' );
	}


	/**
	 * Gets the hmac key.
	 *
	 * @return string
	 */
	public function get_hmac_key() {
		return $this->get_option( 'client_hmac_key' );
	}

	/**
	 * Gets the hmac value.
	 *
	 * @param string $method_path Method path.
	 *
	 * @return string
	 */
	public function get_hmac_value( $method_path ) {
		$hmac_key = $this->get_hmac_key();
		return hash_hmac_md5( $method_path, $hmac_key );
	}

	/**
	 * Gets the Client mac key.
	 *
	 * @param string $query_string Query string.
	 *
	 * @return string
	 */
	public function get_api_mac( $query_string ) {
		return hash_hmac_md5( $query_string, $this->get_option( 'client_mac_key' ) );
	}

	/**
	 * Handles the Virusdie signup process via API request.
	 *
	 * @param string $email Email address.
	 *
	 * @return string
	 */
	public function signup( $email ) {
		if ( empty( $email ) ) {
			return false;
		}
		return $this->fetch_remote_url( 'signup/' . $email . '/', array(), 'GET', true );
	}

	/**
	 * Handles the Virusdie signin process via API request.
	 *
	 * @param string $email Email address.
	 * @param string $opt   Options.
	 *
	 * @return string
	 */
	public function signin( $email, $opt ) {
		if ( empty( $email ) || empty( $opt ) ) {
			return false;
		}
		return $this->fetch_remote_url( 'signin/' . $email . '/' . $opt . '/', array(), 'GET', true );
	}

	/**
	 * Handles the Virusdie signout process via API request.
	 *
	 * @return string
	 */
	public function signout() {
		return $this->fetch_remote_url( 'signout/', array(), 'GET' );
	}

	/**
	 * Handles get the Virusdie user info.
	 *
	 * @return string
	 */
	public function api_userinfo() {
		return $this->fetch_remote_url( 'api_userinfo/', array(), 'GET' );
	}

	/**
	 * Initiates the scan process via Virusdie API request.
	 *
	 * @param string $siteurl Site URL.
	 *
	 * @return string
	 */
	public function scan_site( $siteurl ) {
		if ( empty( $siteurl ) ) {
			return false;
		}
		return $this->fetch_remote_url( 'site_scan/' . $siteurl, array(), 'GET' );
	}

	/**
	 * Adds child sites to a Virusdie account via API request.
	 *
	 * @param array $sites Child sites to add.
	 *
	 * @return string
	 */
	public function sites_add( $sites ) {
		$post_data = $sites;
		return $this->fetch_remote_url( 'sites_add', $post_data, 'POST' );
	}

	/**
	 * Set site option.
	 *
	 * @param string $domain Site domain.
	 * @param string $option Option name.
	 * @param string $value Option value.
	 *
	 * @return string
	 */
	public function site_setoption( $domain, $option, $value ) {
		$post_data = array();
		return $this->fetch_remote_url( 'site_setoption/' . $domain . '/' . $option . '/' . $value . '/', $post_data );
	}

	/**
	 * Deletes sites from the Virusdie account via API request.
	 *
	 * @param string $domain Site domain.
	 *
	 * @return string
	 */
	public function site_delete( $domain ) {
		return $this->fetch_remote_url( 'site_delete/' . $domain, array() );
	}

	/**
	 * Gets the unique sync file from the Virusdie user account via API request.
	 *
	 * @return string
	 */
	public function get_syncfile() {
		$post_data = array();
		return $this->fetch_remote_url( 'syncfile_get', $post_data );
	}

	/**
	 * Lists sites from the Virusdie account via API request.
	 *
	 * @return string
	 */
	public function sites_list() {
		$post_data = array(
			'withscanreport'  => true,
			'withscanhistory' => true,
		);
		return $this->fetch_remote_url( 'sites_list/', $post_data, 'GET' );
	}

	/**
	 * Returns the authorization request url
	 *
	 * @param string $api_act API path.
	 * @param array  $params   Request parameters.
	 *
	 * @return string API URL.
	 */
	public function get_request_url( $api_act, $params = array() ) {
		$url        = $this->api_url . $api_act;
		$str_params = '';
		if ( ! empty( $params ) ) {
			$str_params = $this->build_params_string( $params );
		}
		return $url . $str_params;
	}

	/**
	 * Builds parameters string.
	 *
	 * @param array $params Parameters.
	 *
	 * @return string Parameters string.
	 */
	public function build_params_string( $params ) {
		$str_params = '?';
		foreach ( $params as $name => $val ) {
			$str_params .= $name . '=' . $val . '&';
		}
		return rtrim( $str_params, '&' );
	}

	/**
	 * Returns the result of the API requests.
	 *
	 * @param string $api_request API request.
	 * @param array  $post_data   Post data.
	 * @param string $method      Post method.
	 * @param bool   $auth        Whether to authenticate or not.
	 */
	private function fetch_remote_url( $api_request, $post_data = array(), $method = 'POST', $auth = false ) {

		if ( 'GET' == $method ) {
			$url       = $this->get_request_url( $api_request, $post_data );
			$post_data = array();
		} else {
			$url = $this->api_url . $api_request;
		}

		$header = array();

		$cookie = array();

		if ( $auth ) {
			$cookie[] = 'apikey=' . $this->get_auth_key();
		} else {
			$cookie[] = 'apikey=' . $this->get_api_key();
		}
		$cookie = implode( '; ', $cookie );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_ENCODING, '' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 240 );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
		curl_setopt( $ch, CURLOPT_COOKIE, $cookie );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, wp_json_encode( $post_data, true ) );

		if ( 'syncfile_get' == $api_request ) {
			curl_setopt( $ch, CURLOPT_HEADER, 1 );
		}

		$response = curl_exec( $ch );

		if ( 'syncfile_get' == $api_request ) {
			$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
			$header      = substr( $response, 0, $header_size );
			$header      = trim( $header );
			$headers     = $this->get_headers( $header );
			$filename    = isset( $headers['X-Filename'] ) ? $headers['X-Filename'] : '';
			$response    = substr( $response, $header_size );

			$response = array(
				'filename' => $filename,
				'content'  => $response,
			);

		} else {
			$response = json_decode( $response, true );
		}
		
		if ( 'resource' === gettype( $ch ) ) {
			curl_close( $ch );
		}

		if ( is_array( $response ) && isset( $response['error'] ) && ! empty( $response['error'] ) ) {
			return array( 'error' => $response['message'] );
		}

		return $response;
	}

	/**
	 * Gets an associative headers array.
	 *
	 * @param string $text The response.
	 *
	 * @return array $headers An array of the headers
	 */
	public function get_headers( $text ) {
		$headers = array();
		foreach ( explode( "\r\n", $text ) as $i => $line ) {
			if ( 0 === $i || 1 == $i ) {
				$headers['http_code'] = $line;
			} else {
				list($key, $value) = explode( ': ', $line );
				if ( '' != $key && '' != $value ) {
					$headers[ $key ] = $value;
				}
			}
		}

		return $headers;
	}


}
