<?php

final class MainWP_ITSEC_Backup_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'backup';
	}
	
	public function get_defaults() {
		return array(		
			'method'    => 1,
			'location'  => MainWP_ITSEC_Core::get_storage_dir( 'backups' ),
			'retain'    => 0,
			'zip'       => true,
			'exclude'   => array(
				'itsec_log',
				'itsec_temp',
				'itsec_lockouts',
			),
			'enabled'   => false,
			'interval'  => 3,
			'last_run'  => 0,
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_Backup_Settings() );
