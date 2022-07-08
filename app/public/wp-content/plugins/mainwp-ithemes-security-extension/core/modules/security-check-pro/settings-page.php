<?php

final class MainWP_ITSEC_Security_Check_Pro_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {

	public function __construct() {
		$this->id            = 'security-check-pro';
		$this->title         = __( 'Security Check Pro', 'l10n-mainwp-ithemes-security-extension' );
		$this->description   = __( 'Detects the correct way to identify user IP addresses based on your server configuration by making an API request to iThemes.com servers. No user information is sent to iThemes. [Read our Privacy Policy](https://ithemes.com/privacy-policy/).', 'l10n-mainwp-ithemes-security-extension' );
		$this->type          = 'advanced';
		$this->can_save      = false;
		$this->always_active = true;

		parent::__construct();
	}

	protected function render_settings( $form ) {
		?>		
		<?php
	}
}

new MainWP_ITSEC_Security_Check_Pro_Settings_Page();
