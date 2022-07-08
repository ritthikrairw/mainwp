<?php
class MainWP_Extract_Urls_Utility {
	public static function get_timestamp( $timestamp ) {
		$gmtOffset = get_option( 'gmt_offset' );
		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}

	public static function format_timestamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}
}
