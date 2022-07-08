<?php

class MainWP_ITSEC_Brute_Force {

	private
		$settings,
		$username;

	function run() {

		$this->settings = MainWP_ITSEC_Modules::get_settings( 'brute-force' );
		
		$this->username = null;

		add_filter( 'mainwp_itsec_logger_displays', array( $this, 'itsec_logger_displays' ) ); //adds logs metaboxes
		add_filter( 'mainwp_itsec_lockout_modules', array( $this, 'itsec_lockout_modules' ) );
		add_filter( 'mainwp_itsec_logger_modules', array( $this, 'itsec_logger_modules' ) );

	}

	/**
	 * Sends to lockout class when login form isn't completely filled out and process xml_rpc username
	 *
	 * @since 4.0
	 *
	 * @param object $user     user or wordpress error
	 * @param string $username username attempted
	 * @param string $password password attempted
	 *
	 * @return user object or WordPress error
	 */
	public function authenticate( $user, $username = '', $password = '' ) {


	}

	/**
	 * Register Brute Force for lockout
	 *
	 * @since 4.0
	 *
	 * @param  array $lockout_modules array of lockout modules
	 *
	 * @return array                   array of lockout modules
	 */
	public function itsec_lockout_modules( $lockout_modules ) {

		$lockout_modules['brute_force'] = array(
			'type'   => 'brute_force',
			'reason' => __( 'too many bad login attempts', 'l10n-mainwp-ithemes-security-extension' ),
			'host'   => $this->settings['max_attempts_host'],
			'user'   => $this->settings['max_attempts_user'],
			'period' => $this->settings['check_period'],
		);

		$lockout_modules['brute_force_admin_user'] = array(
			'type'   => 'brute_force',
			'reason' => __( 'user tried to login as "admin."', 'l10n-mainwp-ithemes-security-extension' ),
			'host'   => 1,
			'user'   => 1,
			'period' => $this->settings['check_period']
		);

		return $lockout_modules;

	}

	/**
	 * Register Brute Force for logger
	 *
	 * @since 4.0
	 *
	 * @param  array $logger_modules array of logger modules
	 *
	 * @return array                   array of logger modules
	 */
	public function itsec_logger_modules( $logger_modules ) {

		$logger_modules['brute_force'] = array(
			'type'     => 'brute_force',
			'function' => __( 'Invalid Login Attempt', 'l10n-mainwp-ithemes-security-extension' ),
		);

		return $logger_modules;

	}

	/**
	 * Make sure user isn't already locked out even on successful form submission
	 *
	 * @since 4.0
	 *
	 * @param string $username the username attempted
	 * @param        object    wp_user the user
	 *
	 * @return void
	 */
	
	/**
	 * Array of metaboxes for the logs screen
	 *
	 * @since 4.0
	 *
	 * @param object $displays metabox array
	 *
	 * @return array metabox array
	 */
	public function itsec_logger_displays( $displays ) {

		$displays[] = array(
			'module'   => 'brute_force',
			'title'    => __( 'Invalid Login Attempts', 'l10n-mainwp-ithemes-security-extension' ),
			'callback' => array( $this, 'logs_metabox_content' ),
		);

		return $displays;

	}

	/**
	 * Render the settings metabox
	 *
	 * @since 4.0
	 *
	 * @return void
	 */
	public function logs_metabox_content() {

		if ( ! class_exists( 'MainWP_ITSEC_Brute_Force_Log' ) ) {
			require_once( dirname( __FILE__ ) . '/class-itsec-brute-force-log.php' );
		}

		$log_display = new MainWP_ITSEC_Brute_Force_Log();
		$log_display->prepare_items();
		$log_display->display();

	}

}
