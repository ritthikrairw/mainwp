<?php

class MainWP_Wordfence_Utility {
	public static function format_timestamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	static function ctype_digit( $str ) {
		return ( is_string( $str ) || is_int( $str ) || is_float( $str ) ) && preg_match( '/^\d+\z/', $str );
	}

	public static function map_site( &$website, $keys ) {
		$outputSite = array();
		foreach ( $keys as $key ) {
			$outputSite[ $key ] = $website->$key;
		}

		return $outputSite;
	}


	public static function is_admin( $user = false ) {
		if ( $user ) {
			if ( is_multisite() ) {
				if ( user_can( $user, 'manage_network' ) ) {
					return true;
				}
			} else {
				if ( user_can( $user, 'manage_options' ) ) {
					return true;
				}
			}
		} else {
			if ( is_multisite() ) {
				if ( current_user_can( 'manage_network' ) ) {
					return true;
				}
			} else {
				if ( current_user_can( 'manage_options' ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function get_site_base_url() {
		return rtrim( site_url(), '/' ) . '/';
	}

	public static function big_rando_hex() {
		return dechex( rand( 0, 2147483647 ) ) . dechex( rand( 0, 2147483647 ) ) . dechex( rand( 0, 2147483647 ) );
	}

	public static function get_data_authed( $website, $open_location = "" ) {
		$paramValue = "index.php";
		$params     = array();
		if ( $website && $paramValue != '' ) {
			$nonce = rand( 0, 9999 );
			if ( ( $website->nossl == 0 ) && function_exists( 'openssl_verify' ) ) {
				$nossl = 0;
				openssl_sign( $paramValue . $nonce, $signature, base64_decode( $website->privkey ) );
			} else {
				$nossl     = 1;
				$signature = md5( $paramValue . $nonce . $website->nosslkey );
			}
			$signature = base64_encode( $signature );

			$params = array(
				'login_required'  => 1,
				'user'            => $website->adminname,
				'mainwpsignature' => rawurlencode( $signature ),
				'nonce'           => $nonce,
				'nossl'           => $nossl,
				'open_location'   => $open_location,
				'where'           => $paramValue
			);
		}

		$url = ( isset( $website->siteurl ) && $website->siteurl != '' ? $website->siteurl : $website->url );
		$url .= ( substr( $url, - 1 ) != '/' ? '/' : '' );
		$url .= '?';

		foreach ( $params as $key => $value ) {
			$url .= $key . '=' . $value . '&';
		}

		return rtrim( $url, '&' );
	}
	
	public static function is_valid_ip( $IP ) {
		return filter_var( $IP, FILTER_VALIDATE_IP ) !== false;
	}
	public static function cleanupOneEntryPerLine($string) {
		$string = str_replace(",", "\n", $string); // fix old format
		return implode("\n", array_unique(array_filter(array_map('trim', explode("\n", $string)))));
	}            
}
