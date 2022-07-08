<?php
class MainWP_Team_Control_Utility {
	static function gen_role_id( $name ) {
		$code = sanitize_text_field( $name );
		$code = strtolower( $code );
		$code = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $code );
		if ( strpos( $code, 'mainwp_' ) !== 0 ) {
			$code = 'mainwp_' . $code; }
		return $code;
	}
}
