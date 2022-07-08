<?php

class MainWP_ITSEC_Security_Check_Pro_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'security-check-pro';
	}
	
	protected function sanitize_settings() {
	}
	
	protected function validate_settings() {
		
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Security_Check_Pro_Validator() );
