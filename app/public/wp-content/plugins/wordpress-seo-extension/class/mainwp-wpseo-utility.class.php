<?php
class MainWP_WPSeo_Utility {


	public static function format_timestamp( $timestamp ) {

		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	public static function map_site( &$website, $keys ) {

		$outputSite = array();
		foreach ( $keys as $key ) {
			$outputSite[ $key ] = $website->$key;
		}
		return $outputSite;
	}
}
