<?php
class MainWP_Comment_Utility {

	static function ctype_digit( $str ) {
		return ( is_string( $str ) || is_int( $str ) || is_float( $str ) ) && preg_match( '/^\d+\z/', $str );
	}

	public static function get_timestamp( $timestamp ) {
		$gmtOffset = get_option( 'gmt_offset' );
		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}

	public static function format_timestamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	public static function sortmulti( $array, $index, $order, $natsort = false, $case_sensitive = false ) {
		$sorted = array();
		if ( is_array( $array ) && count( $array ) > 0 ) {
			foreach ( array_keys( $array ) as $key ) {
				$temp[ $key ] = $array[ $key ][ $index ]; }
			if ( ! $natsort ) {
				if ( 'asc' == $order ) {
					asort( $temp ); } else {
					arsort( $temp ); }
			} else {
				if ( true === $case_sensitive ) {
					natsort( $temp ); } else {
					natcasesort( $temp ); }
					if ( 'asc' != $order ) {
						$temp = array_reverse( $temp, true ); }
			}
			foreach ( array_keys( $temp ) as $key ) {
				if ( is_numeric( $key ) ) {
					$sorted[] = $array[ $key ];
				} else {
					$sorted[ $key ] = $array[ $key ];
				}
			}
			return $sorted;
		}
		return $sorted;
	}

	public static function get_sub_array_having( $array, $index, $value ) {
		$output = array();
		if ( is_array( $array ) && count( $array ) > 0 ) {
			foreach ( $array as $arrvalue ) {
				if ( $arrvalue[ $index ] == $value ) {
					$output[] = $arrvalue; }
			}
		}
		return $output;
	}

	public static function starts_with( $haystack, $needle ) {
		return ! strncmp( $haystack, $needle, strlen( $needle ) );
	}

	public static function ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( 0 == $length ) {
			return true;
		}
		return ( substr( $haystack, -$length ) === $needle );
	}

	public static function get_nice_url( $pUrl, $showHttp = false ) {

		$url = $pUrl;

		if ( self::starts_with( $url, 'http://' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 7 ); }
		} elseif ( self::starts_with( $pUrl, 'https://' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 8 ); }
		} else {
			if ( $showHttp ) {
				$url = 'http://' . $url; }
		}

		if ( self::ends_with( $url, '/' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 0, strlen( $url ) - 1 ); }
		} else {
			$url = $url . '/';
		}
		return $url;
	}

	/**
	 * Method init_session()
	 *
	 * Start session.
	 */
	public static function init_session() {
		if ( PHP_SESSION_NONE === session_status() ) {
			session_start();
		}
	}
}
