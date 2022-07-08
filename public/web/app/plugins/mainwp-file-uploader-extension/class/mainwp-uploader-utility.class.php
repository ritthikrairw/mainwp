<?php
class MainWP_Uploader_Utility {

	static function ctype_digit( $str ) {
		return ( is_string( $str ) || is_int( $str ) || is_float( $str ) ) && preg_match( '/^\d+\z/', $str );
	}

	static function get_short_file_name( $name ) {
		$info       = pathinfo( $name );
		$short_name = $name;
		if ( strlen( $info['filename'] ) > 30 ) {
			$short_name  = substr( $info['filename'], 0, 19 ) . '...' . substr( $info['filename'], -9 );
			$short_name .= '.' . $info['extension'];
		}

		 // fix display file name.
		if ( 0 === strpos( $short_name, 'fix_underscore' ) ) {
			$short_name = str_replace( 'fix_underscore', '', $short_name );
		}

		if ( '.phpfile.txt' === substr( $short_name, - 12 ) ) {
			$short_name = substr( $short_name, 0, - 12 ) . '.php';
		}

		return $short_name;
	}

	static function to_bytes( $str ) {
		$val = trim( $str );
		$val = intval( $val );

		$last = strtolower( $str[ strlen( $str ) - 1 ] );
		switch ( $last ) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}
}
