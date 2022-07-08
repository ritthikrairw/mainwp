<?php
/**
 * All pro modules are here as upsell modules
 */


final class MainWP_ITSEC_Malware_Scheduling_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'malware-scheduling';
		$this->title = __( 'Malware Scan Scheduling', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Protect your site with automated malware scans. When this feature is enabled, the site will be automatically scanned each day. If a problem is found, an email is sent to select users.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->pro = true;
		$this->upsell = true;
		$this->upsell_url = 'http://ithemes.com/security/wordpress-malware-scan/?utm_source=wordpressadmin&utm_medium=widget&utm_campaign=itsecfreecta';

		parent::__construct();
	}
}
new MainWP_ITSEC_Malware_Scheduling_Settings_Page();


final class MainWP_ITSEC_Privilege_Escalation_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'privilege';
		$this->title = __( 'Privilege Escalation', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Allow administrators to temporarily grant extra access to a user of the site for a specified period of time.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->pro = true;
		$this->upsell = true;
		$this->upsell_url = 'https://ithemes.com/security/wordpress-privilege-escalation/?utm_source=wordpressadmin&utm_medium=widget&utm_campaign=itsecfreecta';

		parent::__construct();
	}
}
new MainWP_ITSEC_Privilege_Escalation_Settings_Page();


final class MainWP_ITSEC_Password_Expiration_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'password-expiration';
		$this->title = __( 'Password Expiration', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Strengthen the passwords on the site with automated password expiration.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->pro = true;
		$this->upsell = true;
		$this->upsell_url = 'https://ithemes.com/security/wordpress-password-security/?utm_source=wordpressadmin&utm_medium=widget&utm_campaign=itsecfreecta';

		parent::__construct();
	}
}
new MainWP_ITSEC_Password_Expiration_Settings_Page();


final class MainWP_ITSEC_Recaptcha_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'recaptcha';
		$this->title = __( 'reCAPTCHA', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Protect your site from bots by verifying that the person submitting comments or logging in is indeed human.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->pro = true;
		$this->upsell = true;
		$this->upsell_url = 'https://ithemes.com/security/wordpress-recaptcha/?utm_source=wordpressadmin&utm_medium=widget&utm_campaign=itsecfreecta';

		parent::__construct();
	}
}
new MainWP_ITSEC_Recaptcha_Settings_Page();


final class MainWP_ITSEC_Two_Factor_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'two-factor';
		$this->title = __( 'Two-Factor Authentication', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Two-Factor Authentication greatly increases the security of your WordPress user account by requiring additional information beyond your username and password in order to log in.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->pro = true;
		$this->upsell = true;
		$this->upsell_url = 'https://ithemes.com/security/wordpress-two-factor-authentication/?utm_source=wordpressadmin&utm_medium=widget&utm_campaign=itsecfreecta';

		parent::__construct();
	}
}
new MainWP_ITSEC_Two_Factor_Settings_Page();


final class MainWP_ITSEC_User_Logging_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'user-logging';
		$this->title = __( 'User Logging', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Log user actions such as login, saving content and others.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->pro = true;
		$this->upsell = true;
		$this->upsell_url = 'https://ithemes.com/security/wordpress-user-log/?utm_source=wordpressadmin&utm_medium=widget&utm_campaign=itsecfreecta';

		parent::__construct();
	}
}
new MainWP_ITSEC_User_Logging_Settings_Page();


final class MainWP_ITSEC_Import_Export_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $version = 1;


	public function __construct() {
		$this->id = 'import-export';
		$this->title = __( 'Settings Import and Export', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Export your settings as a backup or to import on other sites for quicker setup.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->pro = true;
		$this->upsell = true;
		$this->upsell_url = 'https://ithemes.com/security/import-export-settings/?utm_source=wordpressadmin&utm_medium=widget&utm_campaign=itsecfreecta';

		parent::__construct();
	}
}
new MainWP_ITSEC_Import_Export_Settings_Page();
