<?php
class MainWPGAUtility {

	static function explode_assoc( $inner_glue, $outer_glue, $params ) {
		$tempArr = explode( $outer_glue, $params );
		foreach ( $tempArr as $val ) {
			$pos            = strpos( $val, $inner_glue );
			$key            = substr( $val, 0, $pos );
			$array2[ $key ] = substr( $val, $pos + 1, strlen( $val ) );
		}
		return $array2;
	}

	static function ctype_digit( $str ) {
		return ( is_string( $str ) || is_int( $str ) || is_float( $str ) ) && preg_match( '/^\d+\z/', $str );
	}

	public static function isAdmin() {
		global $current_user;

		if ( $current_user->ID == 0 ) {
			return false;
		}

		if ( $current_user->wp_user_level == 10 || ( isset( $current_user->user_level ) && $current_user->user_level == 10 ) || current_user_can( 'level_10' ) ) {
			return true;
		}

		return false;
	}

	static function getDifferenceInPerc( $old, $new ) {

		if ( $old == 0 ) {
			$old = 1;
		}

		$val = round( ( ( $new / $old ) - 1 ) * 100, 2 );
		return $val;
	}

	static function sec2hms( $sec, $padHours = false ) {
		// start with a blank string
		$hms = '';

		// do the hours first: there are 3600 seconds in an hour, so if we divide
		// the total number of seconds by 3600 and throw away the remainder, we're
		// left with the number of hours in those seconds
		$hours = intval( intval( $sec ) / 3600 );

		// add hours to $hms (with a leading 0 if asked for)
		$hms .= ( $padHours ) ? str_pad( $hours, 2, '0', STR_PAD_LEFT ) . ':' : $hours . ':';

		// dividing the total seconds by 60 will give us the number of minutes
		// in total, but we're interested in *minutes past the hour* and to get
		// this, we have to divide by 60 again and then use the remainder
		$minutes = intval( ( $sec / 60 ) % 60 );

		// add minutes to $hms (with a leading 0 if needed)
		$hms .= str_pad( $minutes, 2, '0', STR_PAD_LEFT ) . ':';

		// seconds past the minute are found by dividing the total number of seconds
		// by 60 and using the remainder
		$seconds = intval( $sec % 60 );

		// add seconds to $hms (with a leading 0 if needed)
		$hms .= str_pad( $seconds, 2, '0', STR_PAD_LEFT );

		// done!
		return $hms;
	}

	public static function get_current_wpid() {
		global $current_user;
		return $current_user->current_site_id;
	}

	public static function array_sort( &$array, $key, $sort_flag = SORT_STRING ) {
		$sorter = array();
		$ret    = array();

		reset( $array );

		foreach ( $array as $ii => $val ) {
			$sorter[ $ii ] = $val[ $key ];
		}

		asort( $sorter, $sort_flag );

		foreach ( $sorter as $ii => $val ) {
			$ret[ $ii ] = $array[ $ii ];
		}

		$array = $ret;
	}

}
