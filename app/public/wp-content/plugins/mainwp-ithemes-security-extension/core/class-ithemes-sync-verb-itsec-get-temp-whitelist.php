<?php

class MainWP_Ithemes_Sync_Verb_ITSEC_Get_Temp_Whitelist extends MainWP_Ithemes_Sync_Verb {
	public static $name        = 'itsec-get-temp-whitelist';
	public static $description = 'Retrieve and report temporarily whitelisted IP.';

	public $default_arguments = array();


	public function run( $arguments ) {
		global $mainwp_itsec_lockout;

		$response = array(
			'version'        => 2,
			'temp_whitelist' => $mainwp_itsec_lockout->get_temp_whitelist(),
		);

		return $response;
	}

}
