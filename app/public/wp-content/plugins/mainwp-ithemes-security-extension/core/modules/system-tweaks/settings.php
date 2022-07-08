<?php

final class MainWP_ITSEC_System_Tweaks_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'system-tweaks';
	}
	
	public function get_defaults() {
		return array(
			'protect_files'            => false,
			'directory_browsing'       => false,
			//'request_methods'          => false,
			//'suspicious_query_strings' => false,
			//'non_english_characters'   => false,
			//'long_url_strings'         => false,
			//'write_permissions'        => false,
			'uploads_php'              => false,
            'themes_php'               => false,
			'plugins_php'              => false,
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_System_Tweaks_Settings() );
