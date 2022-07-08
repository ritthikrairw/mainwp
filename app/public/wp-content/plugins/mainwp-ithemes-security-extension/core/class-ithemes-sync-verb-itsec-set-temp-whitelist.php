<?php

class MainWP_Ithemes_Sync_Verb_ITSEC_Set_Temp_Whitelist extends MainWP_Ithemes_Sync_Verb {

	public static $name        = 'itsec-set-temp-whitelist';
	public static $description = 'Set temporarily whitelisted IP.';

	public $default_arguments = array(
		'direction' => 'add', // whether to "add" or "remove" whitelist
		'ip'        => '', // IP to add or remove
	);

	public function run( $arguments ) {

		global $mainwp_itsec_globals;

		return false;

	}

}
