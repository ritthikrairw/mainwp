<?php

final class MainWP_ITSEC_Ban_Users_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'ban-users';
	}
	
	public function get_defaults() {
		return array(
			'default'          => false,
			'enable_ban_lists' => false,
			'agent_list'       => array(),
			'server_config_limit' => 100
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_Ban_Users_Settings() );
