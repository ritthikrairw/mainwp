<?php
class MainWP_CS_Utility {
	public static function rand_string( $length, $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789' ) {

		$str = '';
		$count = strlen( $charset );
		if ( $length > 10 ) {
			$length = 10;
		}
		while ( $length-- ) {
			$str .= $charset[ mt_rand( 0, $count - 1 ) ];
		}
		return date( 'YmdHis' ) . $str;
	}
}
