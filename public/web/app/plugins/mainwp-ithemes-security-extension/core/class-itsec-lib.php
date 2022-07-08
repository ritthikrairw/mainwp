<?php

/**
 * Miscelaneous plugin-wide functions
 *
 * @package iThemes-Security
 * @since   4.0
 */
final class MainWP_ITSEC_Lib {

	/**
	 * Loads core functionality across both admin and frontend.
	 */
	function __construct() {

		return;

	}

	/**
	 * Converts CIDR to ip range.
	 *
	 * Modified from function at http://stackoverflow.com/questions/4931721/getting-list-ips-from-cidr-notation-in-php
	 * as it was far more elegant than my own solution
	 *
	 * @param string $cidr cidr notation to convert
	 *
	 * @return array        range of ips returned
	 */
	public static function cidr_to_range( $cidr ) {

		$range = array();

		if ( strpos( $cidr, '/' ) ) {

			$cidr = explode( '/', $cidr );

			$range[] = long2ip( ( ip2long( $cidr[0] ) ) & ( ( - 1 << ( 32 - (int) $cidr[1] ) ) ) );
			$range[] = long2ip( ( ip2long( $cidr[0] ) ) + pow( 2, ( 32 - (int) $cidr[1] ) ) - 1 );

		} else { //if not a range just return the original ip

			$range[] = $cidr;

		}

		return $range;

	}
	
	
	/**
	 * Load a library class definition.
	 *
	 * @param string $name
	 */
	public static function load( $name ) {
		require_once( dirname( __FILE__ ) . "/lib/class-itsec-lib-{$name}.php" );
	}
	
	public static function get_whitelisted_ips() {
		return apply_filters( 'mainwp_itsec_white_ips', array() );
	}
	
	
	public static function show_error_message( $message ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		if ( ! is_string( $message ) ) {
			return;
		}

		echo "<div class=\"error\"><p><strong>$message</strong></p></div>\n";
	}
	
	public static function is_ip_whitelisted( $ip, $whitelisted_ips = null, $current = false ) {
		global $mainwp_itsec_lockout;

		$ip = sanitize_text_field( $ip );

		if ( MainWP_ITSEC_Lib::get_ip() === $ip && $mainwp_itsec_lockout->is_visitor_temp_whitelisted() ) {
			return true;
		}

		if ( ! class_exists( 'MainWP_ITSEC_Lib_IP_Tools' ) ) {
			require_once( MainWP_ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-ip-tools.php' );
		}

		if ( is_null( $whitelisted_ips ) ) {
			$whitelisted_ips = self::get_whitelisted_ips();
		}

		if ( $current ) {
			$whitelisted_ips[] = MainWP_ITSEC_Lib::get_ip(); //add current user ip to whitelist
		}

		foreach ( $whitelisted_ips as $whitelisted_ip ) {
			if ( MainWP_ITSEC_Lib_IP_Tools::intersect( $ip, MainWP_ITSEC_Lib_IP_Tools::ip_wild_to_ip_cidr( $whitelisted_ip ) ) ) {
				return true;
			}
		}

		return false;

	}
	
	/**
	 * Gets location of wp-config.php
	 *
	 * @since 4.0
	 *
	 * Finds and returns path to wp-config.php
	 *
	 * @return string path to wp-config.php
	 *
	 * */
	public static function get_config() {

		if ( file_exists( trailingslashit( ABSPATH ) . 'wp-config.php' ) ) {

			return trailingslashit( ABSPATH ) . 'wp-config.php';

		} else {

			return trailingslashit( dirname( ABSPATH ) ) . 'wp-config.php';

		}

	}

	/**
	 * Gets current url
	 *
	 * @since 4.3
	 *
	 * Finds and returns current url
	 *
	 * @return string current url
	 *
	 * */
	public static function get_current_url() {

		$page_url = 'http';

		if ( isset( $_SERVER["HTTPS"] ) ) {

			if ( $_SERVER["HTTPS"] == "on" ) {
				$page_url .= "s";
			}

		}

		$page_url .= "://";

		if ( $_SERVER["SERVER_PORT"] != "80" ) {

			$page_url .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];

		} else {

			$page_url .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

		}

		return esc_url( $page_url );
	}

	/**
	 * Return primary domain from given url
	 *
	 * Returns primary domain name (without subdomains) of given URL
	 *
	 * @since 4.0
	 *
	 * @param string $address  address to filter
	 * @param bool   $apache   [true] does this require an apache style wildcard
	 * @param bool   $wildcard false if a wildcard shouldn't be included at all
	 *
	 * @return string domain name
	 *
	 * */
	public static function get_domain( $address, $apache = true, $wildcard = true ) {

		preg_match( "/^(http:\/\/)?([^\/]+)/i", $address, $matches );

		$host = $matches[2];

		preg_match( "/[^\.\/]+\.[^\.\/]+$/", $host, $matches );

		if ( $wildcard === true ) {

			if ( $apache === true ) {

				$wc = '(.*)';

			} else {

				$wc = '*.';

			}

		} else {

			$wc = '';

		}

		if ( ! is_array( $matches ) ) {
			return false;
		}

		// multisite domain mapping compatibility. when hide login is enabled,
		// rewrite rules redirect valid POST requests from MAPPED_DOMAIN/wp-login.php?SECRET_KEY
		// because they aren't coming from the "top-level" domain. blog_id 1, the parent site,
		// is a completely different, unrelated domain in this configuration.
		if ( is_multisite() && function_exists( 'domain_mapping_warning' ) ) {

			if ( $apache == true ) {
				return $wc;
			} else {
				return '*';
			}

		} elseif ( isset( $matches[0] ) ) {

			return $wc . $matches[0];

		} else {

			return false;

		}

	}

	/**
	 * Get the absolute filesystem path to the root of the WordPress installation
	 *
	 * @since 4.3
	 *
	 * @uses  get_option
	 * @return string Full filesystem path to the root of the WordPress installation
	 */
	public static function get_home_path() {

		$home    = set_url_scheme( get_option( 'home' ), 'http' );
		$siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );

		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {

			$wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
			$pos                 = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );

			if ( $pos === false ) {

				$home_path = dirname( $_SERVER['SCRIPT_FILENAME'] );

			} else {

				$home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );

			}

		} else {

			$home_path = ABSPATH;

		}

		return trailingslashit( str_replace( '\\', '/', $home_path ) );

	}

	/**
	 * Returns the root of the WordPress install
	 *
	 * @since 4.0.6
	 *
	 * @return string the root folder
	 */
	public static function get_home_root() {

		//homeroot from wp_rewrite
		$home_root = parse_url( site_url() );

		if ( isset( $home_root['path'] ) ) {

			$home_root = trailingslashit( $home_root['path'] );

		} else {

			$home_root = '/';

		}

		return $home_root;

	}

	/**
	 * Gets location of .htaccess
	 *
	 * Finds and returns path to .htaccess or nginx.conf if appropriate
	 *
	 * @return string path to .htaccess
	 *
	 * */
	public static function get_htaccess() {

		global $mainwp_itsec_globals;

		if ( MainWP_ITSEC_Lib::get_server() === 'nginx' ) {

			return $mainwp_itsec_globals['settings']['nginx_file'];

		} else {

			return MainWP_ITSEC_Lib::get_home_path() . '.htaccess';

		}

	}

	/**
	 * Returns the actual IP address of the user.
	 *
	 * Determines the user's IP address by returning the fowarded IP address if present or
	 * the direct IP address if not.
	 *
	 * @since 4.0
	 *
	 * @return  String The IP address of the user
	 *
	 */
	public static function get_ip() {

		global $mainwp_itsec_globals;
	
		$headers = array(
			'REMOTE_ADDR',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CLIENT_IP',
		);

		$headers = apply_filters( 'itsec_filter_remote_addr_headers', $headers );

		$headers = (array) $headers;

		if ( ! in_array( 'REMOTE_ADDR', $headers ) ) {
			$headers[] = 'REMOTE_ADDR';
		}

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[$header] ) ) {
				continue;
			}

			$ip = filter_var( $_SERVER[$header], FILTER_VALIDATE_IP );

			if ( ! empty( $ip ) ) {
				break;
			}
		}

		return esc_sql( (string) $ip );

	}

	/**
	 * Returns the URL of the current module
	 *
	 * @since 4.0
	 *
	 * @param string $file the module file from which to derive the path
	 *
	 * @return string the path of the current module
	 */
	public static function get_module_path( $file ) {

		$path = str_replace( MainWP_ITSEC_Core::get_plugin_dir(), '', dirname( $file ) );
		$path = ltrim( str_replace( '\\', '/', $path ), '/' );

		$url_base = trailingslashit( plugin_dir_url( MainWP_ITSEC_Core::get_plugin_file() ) );

		return trailingslashit( $url_base . $path );

		

	}

	/**
	 * Returns a psuedo-random string of requested length.
	 *
	 * @param int  $length how long the string should be (max 62)
	 * @param bool $base32 true if use only base32 characters to generate
	 *
	 * @return string
	 */
	public static function get_random( $length, $base32 = false ) {

		if ( $base32 === true ) {

			$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

		} else {

			$string = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		}

		return substr( str_shuffle( $string ), mt_rand( 0, strlen( $string ) - $length ), $length );

	}

	/**
	 * Returns the server type of the plugin user.
	 *
	 * @return string|bool server type the user is using of false if undetectable.
	 */
	public static function get_server() {

		//Allows to override server authentication for testing or other reasons.
		if ( defined( 'MainWP_ITSEC_SERVER_OVERRIDE' ) ) {
			return MainWP_ITSEC_SERVER_OVERRIDE;
		}

		$server_raw = strtolower( filter_var( $_SERVER['SERVER_SOFTWARE'], FILTER_SANITIZE_STRING ) );

		//figure out what server they're using
		if ( strpos( $server_raw, 'apache' ) !== false ) {

			return 'apache';

		} elseif ( strpos( $server_raw, 'nginx' ) !== false ) {

			return 'nginx';

		} elseif ( strpos( $server_raw, 'litespeed' ) !== false ) {

			return 'litespeed';

		} else { //unsupported server

			return false;

		}

	}

	/**
	 * Determine whether the server supports SSL (shared cert not supported
	 *
	 * @return bool true if ssl is supported or false
	 */
	public static function get_ssl() {

		$url = str_replace( 'http://', 'https://', get_bloginfo( 'url' ) );

		if ( function_exists( 'wp_http_supports' ) && wp_http_supports( array( 'ssl' ), $url ) ) {
			return true;
		} elseif ( function_exists( 'curl_init' ) ) {

			$timeout    = 5; //timeout for the request
			$site_title = trim( get_bloginfo() );

			$request = curl_init();

			curl_setopt( $request, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $request, CURLOPT_VERBOSE, false );
			curl_setopt( $request, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $request, CURLOPT_HEADER, true );
			curl_setopt( $request, CURLOPT_URL, $url );
			curl_setopt( $request, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $request, CURLOPT_CONNECTTIMEOUT, $timeout );

			$data = curl_exec( $request );

			$header_size = curl_getinfo( $request, CURLINFO_HEADER_SIZE );
			$http_code   = intval( curl_getinfo( $request, CURLINFO_HTTP_CODE ) );
			$body        = substr( $data, $header_size );

			preg_match( '/<title>(.+)<\/title>/', $body, $matches );

			if ( $http_code == 200 && isset( $matches[1] ) && strpos( $matches[1], $site_title ) !== false ) {
				return true;
			} else {
				return false;
			}

		}

		return false;

	}

	/**
	 * Converts IP with a netmask wildcards to one with * instead
	 *
	 * @param string $ip ip to convert
	 *
	 * @return string     the converted ip
	 */
	public static function ip_mask_to_range( $ip ) {

		if ( strpos( $ip, '/' ) ) {

			$parts  = explode( '/', trim( $ip ) );
			$octets = array_reverse( explode( '.', trim( $parts[0] ) ) );

			if ( isset( $parts[1] ) && intval( $parts[1] ) > 0 ) {

				$wildcards = ( 32 - $parts[1] ) / 8;

				for ( $count = 0; $count < $wildcards; $count ++ ) {

					$octets[ $count ] = '[0-9]+';

				}

				return implode( '.', array_reverse( $octets ) );

			} else {

				return $ip;

			}

		}

		return $ip;

	}

	/**
	 * Converts IP with * wildcards to one with a netmask instead
	 *
	 * @param string $ip ip to convert
	 *
	 * @return string     the converted ip
	 */
	public static function ip_wild_to_mask( $ip ) {

		$host_parts = array_reverse( explode( '.', trim( $ip ) ) );

		if ( strpos( $ip, '*' ) ) {

			$mask           = 32; //used to calculate netmask with wildcards
			$converted_host = str_replace( '*', '0', $ip );

			//convert hosts with wildcards to host with netmask and create rule lines
			foreach ( $host_parts as $part ) {

				if ( $part === '*' ) {
					$mask = $mask - 8;
				}

			}

			$converted_host = trim( $converted_host );

			//Apply a mask if we had to convert
			if ( $mask > 0 ) {
				$converted_host .= '/' . $mask;
			}

			return $converted_host;

		}

		return $ip;

	}

	/**
	 * Determine whether we're on the login page or not
	 *
	 * @return bool true if is login page else false
	 */
	public static function is_login_page() {

		return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );

	}

	/**
	 * Forces the given page to a WordPress 404 error
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public static function set_404() {

		global $wp_query;

		status_header( 404 );

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}

		$wp_query->set_404();
		$page_404 = get_404_template();

		if ( strlen( $page_404 ) > 1 ) {
			include_once( $page_404 );
		} else {
			include_once( get_query_template( 'index' ) );
		}

		die();

	}

	/**
	 * Increases minimum memory limit.
	 *
	 * This function, adopted from builder, attempts to increase the minimum
	 * memory limit before heavy functions.
	 *
	 * @since 4.0
	 *
	 * @param int $new_memory_limit what the new memory limit should be
	 *
	 * @return void
	 */
	public static function set_minimum_memory_limit( $new_memory_limit ) {

		$memory_limit = @ini_get( 'memory_limit' );

		if ( $memory_limit > - 1 ) {

			$unit = strtolower( substr( $memory_limit, - 1 ) );

			$new_unit = strtolower( substr( $new_memory_limit, - 1 ) );

			if ( 'm' == $unit ) {
				$memory_limit *= 1048576;
			} else if ( 'g' == $unit ) {
				$memory_limit *= 1073741824;
			} else if ( 'k' == $unit ) {
				$memory_limit *= 1024;
			}

			if ( 'm' == $new_unit ) {
				$new_memory_limit *= 1048576;
			} else if ( 'g' == $new_unit ) {
				$new_memory_limit *= 1073741824;
			} else if ( 'k' == $new_unit ) {
				$new_memory_limit *= 1024;
			}

			if ( (int) $memory_limit < (int) $new_memory_limit ) {
				@ini_set( 'memory_limit', $new_memory_limit );
			}

		}

	}

	/**
	 * Checks if user exists
	 *
	 * Checks to see if WordPress user with given id exists
	 *
	 * @param int $user_id user id of user to check
	 *
	 * @return bool true if user exists otherwise false
	 *
	 * */
	public static function user_id_exists( $user_id ) {

		global $wpdb;

		//return false if username is null
		if ( $user_id == '' ) {
			return false;
		}

		//queary the user table to see if the user is there
		$saved_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM `" . $wpdb->users . "` WHERE ID='%s';", sanitize_text_field( $user_id ) ) );

		if ( $saved_id == $user_id ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Validates a list of ip addresses
	 *
	 * @param string $ip string of hosts to check
	 *
	 * @return array array of good hosts or false
	 */
	public static function validates_ip_address( $ip ) {

		//validate list
		$ip             = trim( filter_var( $ip, FILTER_SANITIZE_STRING ) );
		$ip_parts       = explode( '.', $ip );
		$error_handler  = null;
		$is_ip          = 0;
		$part_count     = 1;
		$good_ip        = true;
		$found_wildcard = false;

		foreach ( $ip_parts as $part ) {

			if ( $good_ip == true ) {

				if ( ( is_numeric( $part ) && $part <= 255 && $part >= 0 ) || $part === '*' || ( $part_count === 3 && strpos( $part,
				                                                                                                              '/' ) !== false )
				) {
					$is_ip ++;
				}

				switch ( $part_count ) {

					case 1: //1st octet

						if ( $part === '*' || strpos( $part, '/' ) !== false ) {

							return false;

						}

						break;

					case 2: //2nd octet

						if ( $part === '*' ) {

							$found_wildcard = true;

						} elseif ( strpos( $part, '/' ) !== false ) {

							return false;

						}

						break;

					case 3: //3rd octet

						if ( $part !== '*' ) {

							if ( $found_wildcard === true ) {

								return false;

							}

						} elseif ( strpos( $part, '/' ) !== false ) {

							return false;

						} else {

							$found_wildcard = true;

						}

						break;

					default: //4th octet and netmask

						if ( $part !== '*' ) {

							if ( $found_wildcard == true ) {

								return false;

							} elseif ( strpos( $part, '/' ) !== false ) {

								$netmask = intval( substr( $part, ( strpos( $part, '/' ) + 1 ) ) );

								if ( ! is_numeric( $netmask ) && 1 > $netmask && 31 < $netmask ) {

									return false;

								}

							}

						}

						break;

				}

				$part_count ++;

			}

		}

		if ( ( strpos( $ip, '/' ) !== false && ip2long( trim( substr( $ip, 0, strpos( $ip,
		                                                                              '/' ) ) ) ) === false ) || ( strpos( $ip,
		                                                                                                                   '/' ) === false && ip2long( trim( str_replace( '*',
		                                                                                                                                                                  '0',
		                                                                                                                                                                  $ip ) ) ) === false )
		) { //invalid ip

			return false;

		}

		return true; //ip is valid

	}

	/**
	 * Validates a file path
	 *
	 * Adapted from http://stackoverflow.com/questions/4049856/replace-phps-realpath/4050444#4050444 as a replacement for PHP's realpath
	 *
	 * @param string $path The original path, can be relative etc.
	 *
	 * @return bool true if the path is valid and writeable else false
	 */
	public static function validate_path( $path ) {

		// whether $path is unix or not
		$unipath = strlen( $path ) == 0 || substr( $path, 0, 1 ) != '/';

		// attempts to detect if path is relative in which case, add cwd
		if ( strpos( $path, ':' ) === false && $unipath ) {
			$path = getcwd() . DIRECTORY_SEPARATOR . $path;
		}

		// resolve path parts (single dot, double dot and double delimiters)
		$path      = str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, $path );
		$parts     = array_filter( explode( DIRECTORY_SEPARATOR, $path ), 'strlen' );
		$absolutes = array();

		foreach ( $parts as $part ) {

			if ( '.' == $part ) {
				continue;
			}

			if ( '..' == $part ) {

				array_pop( $absolutes );

			} else {

				$absolutes[] = $part;

			}

		}

		$path = implode( DIRECTORY_SEPARATOR, $absolutes );

		// resolve any symlinks
		if ( function_exists( 'linkinfo' ) ) { //linkinfo not available on Windows with PHP < 5.3.0

			if ( file_exists( $path ) && linkinfo( $path ) > 0 ) {
				$path = @readlink( $path );
			}

		} else {

			if ( file_exists( $path ) && linkinfo( $path ) > 0 ) {
				$path = @readlink( $path );
			}

		}

		// put initial separator that could have been lost
		$path = ! $unipath ? '/' . $path : $path;

		$test = @touch( $path . '/test.txt' );
		@unlink( $path . '/test.txt' );

		return $test;

	}

	
	/**
	 * Merges two arrays recursively such that only arrays are deeply merged.
	 *
	 * @param array $array1
	 * @param array $array2
	 *
	 * @return array
	 */
	public static function array_merge_recursive_distinct( array $array1, array $array2 ): array {
		$merged = $array1;

		foreach ( $array2 as $key => $value ) {
			if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
				$merged[ $key ] = self::array_merge_recursive_distinct( $merged[ $key ], $value );
			} else {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Validates a URL
	 *
	 * @since 4.3
	 *
	 * @param string $url the url to validate
	 *
	 * @return bool true if valid url else false
	 */
	public static function validate_url( $url ) {

		$pattern = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";

		return (bool) preg_match( $pattern, $url );

	}

	
	/**
	 * Resolve JSON Schema refs.
	 *
	 * @param array $schema
	 *
	 * @return array
	 */
	public static function resolve_schema_refs( array $schema ): array {
		if ( isset( $schema['definitions'] ) ) {
			array_walk( $schema, [ static::class, 'resolve_ref' ], $schema['definitions'] );
		}

		return $schema;
	}

	/**
	 * Resolves $ref entries at any point in the config.
	 *
	 * Currently, only a simplified form of JSON Pointers are supported where `/` is the only
	 * allowed control character.
	 *
	 * Additionally, the `$ref` keyword must start with `#/definitions`.
	 *
	 * @param mixed  $value       The incoming value.
	 * @param string $key         The array key.
	 * @param array  $definitions The shared definitions.
	 */
	private static function resolve_ref( &$value, $key, $definitions ) {
		if ( ! is_array( $value ) ) {
			return;
		}

		if ( isset( $value['$ref'] ) ) {
			$ref   = str_replace( '#/definitions/', '', $value['$ref'] );
			$value = MainWP_ITSEC_Lib::array_get( $definitions, $ref, null, '/' );

			return;
		}

		array_walk( $value, [ static::class, 'resolve_ref' ], $definitions );
	}

	
	/**
	 * Get a dot nested value from an array.
	 *
	 * @param array  $array
	 * @param string $key
	 * @param mixed  $default
	 * @param string $delimeter
	 *
	 * @return mixed
	 */
	public static function array_get( $array, $key, $default = null, $delimeter = '.' ) {
		if ( ! is_array( $array ) ) {
			return $default;
		}

		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		if ( strpos( $key, $delimeter ) === false ) {
			return isset( $array[ $key ] ) ? $array[ $key ] : $default;
		}

		foreach ( explode( $delimeter, $key ) as $segment ) {
			if ( is_array( $array ) && isset( $array[ $segment ] ) ) {
				$array = $array[ $segment ];
			} else {
				return $default;
			}
		}

		return $array;
	}


}
