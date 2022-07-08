<?php

final class MainWP_ITSEC_Security_Check_Scanner {
	private static $available_modules;
	private static $calls_to_action = array();
	private static $actions_taken = array();
	private static $confirmations = array();


	public static function run() {
		self::$available_modules = MainWP_ITSEC_Modules::get_available_modules();

		self::enforce_activation( 'ban-users', __( 'Ban Users', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_setting( 'ban-users', 'enable_ban_lists', true, __( 'Enabled the Enable Ban Lists setting in Banned Users.', 'l10n-mainwp-ithemes-security-extension' ) );

		self::enforce_activation( 'backup', __( 'Database Backups', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_activation( 'brute-force', __( 'Local Brute Force', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_activation( 'malware-scheduling', __( 'Malware Scan Scheduling', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_setting( 'malware-scheduling', 'email_notifications', true, __( 'Enabled the Email Notifications setting in Malware Scan Scheduling.', 'l10n-mainwp-ithemes-security-extension' ) );

		self::add_network_brute_force_signup();

		self::enforce_activation( 'strong-passwords', __( 'Strong Password Enforcement', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_activation( 'two-factor', __( 'Two-Factor Authentication', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enable_all_two_factor_providers();

		//self::enforce_activation( 'user-logging', __( 'User Logging', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_activation( 'wordpress-tweaks', __( 'WordPress Tweaks', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_setting( 'wordpress-tweaks', 'file_editor', true, __( 'Disabled the File Editor in WordPress Tweaks.', 'l10n-mainwp-ithemes-security-extension' ) );
		self::enforce_setting( 'wordpress-tweaks', 'allow_xmlrpc_multiauth', false, __( 'Changed the Multiple Authentication Attempts per XML-RPC Request setting in WordPress Tweaks to "Block".', 'l10n-mainwp-ithemes-security-extension' ) );

		self::enforce_setting( 'global', 'write_files', true, __( 'Enabled the Write to Files setting in Global Settings.', 'l10n-mainwp-ithemes-security-extension' ) );


		ob_start();

		echo implode( "\n", self::$calls_to_action );
		echo implode( "\n", self::$actions_taken );
		echo implode( "\n", self::$confirmations );

		MainWP_ITSEC_Response::set_response( ob_get_clean() );
	}

	private static function add_network_brute_force_signup() {
		if ( ! in_array( 'network-brute-force', self::$available_modules ) ) {
			return;
		}


		$settings = MainWP_ITSEC_Modules::get_settings( 'network-brute-force' );

		if ( ! empty( $settings['api_key'] ) && ! empty( $settings['api_secret'] ) ) {
			self::enforce_activation( 'network-brute-force', __( 'Network Brute Force', 'l10n-mainwp-ithemes-security-extension' ) );
			return;
		}


		require_once( MainWP_ITSEC_Core::get_core_dir() . '/lib/form.php' );
		$form = new MainWP_ITSEC_Form();
		$form->add_input_group( 'security-check' );

		ob_start();

		self::open_container( 'incomplete', 'itsec-security-check-network-brute-force-container' );

		echo '<p>' . __( 'With Network Brute Force, your site is protected against attackers found by other sites running iThemes Security. If your site identifies a new attacker, it automatically notifies the network so that other sites are protected as well. To join this site to the network and enable the protection, click the button below.', 'l10n-mainwp-ithemes-security-extension' ) . '</p>';

		ob_start();
		$form->add_text( 'email', array( 'value' => get_option( 'admin_email' ) ) );
		$email_input = ob_get_clean();
		/* translators: 1: email text input */
		echo '<p><label for="itsec-security-check-email">' . sprintf( __( 'Email Address: %1$s', 'l10n-mainwp-ithemes-security-extension' ), $email_input ) . '</p>';

		ob_start();
		$form->add_select( 'updates_optin', array( 'true' => __( 'Yes', 'l10n-mainwp-ithemes-security-extension' ), 'false' => __( 'No', 'l10n-mainwp-ithemes-security-extension' ) ) );
		$optin_input = ob_get_clean();
		/* translators: 1: opt-in input */
		echo '<p><label for="itsec-security-check-updates_optin">' . sprintf( __( 'Receive Email Updates: %1$s', 'l10n-mainwp-ithemes-security-extension' ), $optin_input ) . '</p>';

		ob_start();
		$form->add_button( 'enable_network_brute_force', array( 'class' => 'button ui green', 'value' => __( 'Activate Network Brute Force', 'l10n-mainwp-ithemes-security-extension' ) ) );
		echo '<p>' . ob_get_clean() . '</p>';

		echo '<div id="itsec-security-check-network-brute-force-errors"></div>';

		echo '</div>';

		self::$calls_to_action[] = ob_get_clean();
	}

	private static function enable_all_two_factor_providers() {
		if ( ! in_array( 'two-factor', self::$available_modules ) ) {
			return;
		}


		$two_factor_providers = MainWP_ITSEC_Modules::get_setting( 'two-factor', 'enabled-providers' );
		$added_provider = false;

		ob_start();

		if ( ! in_array( 'Two_Factor_Totp', $two_factor_providers ) ) {
			$two_factor_providers[] = 'Two_Factor_Totp';
			$added_provider = true;

			self::open_container();
			echo '<p>' . __( 'Enabled the Time-Based One-Time Password (TOTP) provider for Two-Factor Authentication.', 'l10n-mainwp-ithemes-security-extension' ) . '</p>';
			echo '</div>';
		}

		if ( ! in_array( 'Two_Factor_Email', $two_factor_providers ) ) {
			$two_factor_providers[] = 'Two_Factor_Email';
			$added_provider = true;

			self::open_container();
			echo '<p>' . __( 'Enabled the Email provider for Two-Factor Authentication.', 'l10n-mainwp-ithemes-security-extension' ) . '</p>';
			echo '</div>';
		}

		if ( ! in_array( 'Two_Factor_Backup_Codes', $two_factor_providers ) ) {
			$two_factor_providers[] = 'Two_Factor_Backup_Codes';
			$added_provider = true;

			self::open_container();
			echo '<p>' . __( 'Enabled the Backup Verification Codes provider for Two-Factor Authentication.', 'l10n-mainwp-ithemes-security-extension' ) . '</p>';
			echo '</div>';
		}


		if ( $added_provider ) {
			self::$actions_taken[] = ob_get_clean();

			MainWP_ITSEC_Modules::set_setting( 'two-factor', 'enabled-providers', $two_factor_providers );

			MainWP_ITSEC_Response::reload_module( 'two-factor' );
		}
	}

	private static function enforce_setting( $module, $setting_name, $setting_value, $description ) {
		if ( ! in_array( $module, self::$available_modules ) ) {
			return;
		}

		if ( MainWP_ITSEC_Modules::get_setting( $module, $setting_name ) !== $setting_value ) {
			MainWP_ITSEC_Modules::set_setting( $module, $setting_name, $setting_value );

			ob_start();

			self::open_container();
			echo "<p>$description</p>";
			echo '</div>';

			self::$actions_taken[] = ob_get_clean();

			MainWP_ITSEC_Response::reload_module( $module );
		}
	}

	private static function enforce_activation( $module, $name ) {
		if ( ! in_array( $module, self::$available_modules ) ) {
			return;
		}

		if ( MainWP_ITSEC_Modules::is_active( $module ) ) {
			/* Translators: 1: feature name */
			$text = __( '%1$s is enabled as recommended.', 'l10n-mainwp-ithemes-security-extension' );
			$took_action = false;
		} else {
			MainWP_ITSEC_Modules::activate( $module );
			MainWP_ITSEC_Response::add_js_function_call( 'setModuleToActive', $module );

			/* Translators: 1: feature name */
			$text = __( 'Enabled %1$s.', 'l10n-mainwp-ithemes-security-extension' );
			$took_action = true;
		}

		ob_start();

		self::open_container();
		echo '<p>' . sprintf( $text, $name ) . '</p>';
		echo '</div>';

		if ( $took_action ) {
			self::$actions_taken[] = ob_get_clean();
		} else {
			self::$confirmations[] = ob_get_clean();
		}
	}

	public static function activate_network_brute_force() {
		$settings = MainWP_ITSEC_Modules::get_settings( 'network-brute-force' );

		$settings['email'] = $_POST['data']['email'];
		$settings['updates_optin'] = $_POST['data']['updates_optin'];
		$settings['api_nag'] = false;

		$results = MainWP_ITSEC_Modules::set_settings( 'network-brute-force', $settings );

		if ( is_wp_error( $results ) ) {
			MainWP_ITSEC_Response::add_error( $results );
		} else if ( $results['saved'] ) {
			MainWP_ITSEC_Modules::activate( 'network-brute-force' );
			MainWP_ITSEC_Response::add_js_function_call( 'setModuleToActive', 'network-brute-force' );
			MainWP_ITSEC_Response::set_response( '<p>' . __( 'Your site is now using Network Brute Force.', 'l10n-mainwp-ithemes-security-extension' ) . '</p>' );
		}
	}

	private static function open_container( $status = 'complete', $id = '' ) {
		echo '<div class="itsec-security-check-container itsec-security-check-container-' . $status . '"';

		if ( ! empty( $id ) ) {
			echo ' id="' . $id . '"';
		}

		echo '>';
	}
}
