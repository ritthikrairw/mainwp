<?php

/**
 * Plugin activation, upgrade, deactivation and uninstall
 *
 * @package iThemes-Security
 * @since   4.0
 */
class MainWP_ITSEC_Setup {

	private $defaults;

	/**
	 * Establish setup object
	 *
	 * Establishes set object and calls appropriate execution function
	 *
	 * @param bool $case [optional] Appropriate execution module to call
	 * */
	function __construct( $case = false, $upgrading = false ) {

		global $mainwp_itsec_globals;

		$this->defaults = array(
			// 'notification_email'        => array( get_option( 'admin_email' ) ),
			// 'backup_email'              => array( get_option( 'admin_email' ) ),
			'lockout_message'                => __( 'error', 'l10n-mainwp-ithemes-security-extension' ),
			'user_lockout_message'           => __( 'You have been locked out due to too many invalid login attempts.', 'l10n-mainwp-ithemes-security-extension' ),
			'community_lockout_message'      => __( 'Your IP address has been flagged as a threat by the iThemes Security network.', 'l10n-mainwp-ithemes-security-extension' ),
			'blacklist'                      => true,
			'blacklist_count'                => 3,
			'blacklist_period'               => 7,
			// 'email_notifications'       => true,
							'lockout_period' => 15,
			'lockout_white_list'             => array(),
			'log_rotation'                   => 60,
			'file_log_rotation'              => 180,
			'log_type'                       => 'database',
			'log_location'                   => MainWP_ITSEC_Core::get_storage_dir( 'logs' ),
			'allow_tracking'                 => false,
			'write_files'                    => false,
			'nginx_file'                     => ABSPATH . 'nginx.conf',
			'infinitewp_compatibility'       => false,
			'did_upgrade'                    => false,
			// 'lock_file'                 => false,
			// 'digest_email'              => false,
				// 'proxy_override'            => false,
				'proxy'                      => 'automatic',
			'proxy_header'                   => 'HTTP_X_FORWARDED_FOR',
			'hide_admin_bar'                 => false,
			'enable_grade_report'            => false,
			'automatic_temp_auth'            => false,
		);

		if ( ! $case ) {
			die( 'error' );
		}

		switch ( $case ) {

			case 'activate': // active plugin
				$this->activate_execute();
				break;

			case 'upgrade': // upgrade plugin
				$this->upgrade_execute( $upgrading );
				break;

			case 'deactivate': // deactivate plugin
				$this->deactivate_execute();
				break;

			case 'uninstall': // uninstall plugin
				$this->uninstall_execute();
				break;

		}

	}

	/**
	 * Execute setup script for each module installed
	 *
	 * @return void
	 */
	function do_modules() {
		$itsec_modules = MainWP_ITSEC_Modules::get_instance();
		$itsec_modules->run_activation();
	}

	/**
	 * Public function to activate
	 * */
	static function on_activate() {
		global $mainwp_itsec_setup_action;
		$mainwp_itsec_setup_action = 'activate';
		new MainWP_ITSEC_Setup( 'activate' );
		global $itsec_setup_action;
	}

	/**
	 * Public function to deactivate
	 * */
	static function on_deactivate() {

		global $mainwp_itsec_setup_action;

		if ( defined( 'MainWP_ITSEC_DEVELOPMENT' ) && MainWP_ITSEC_DEVELOPMENT == true ) { // set MainWP_ITSEC_DEVELOPMENT to true to reset settings on deactivation for development

			$mainwp_itsec_setup_action = 'uninstall';

		} else {

			$mainwp_itsec_setup_action = 'deactivate';

		}

		new MainWP_ITSEC_Setup( $mainwp_itsec_setup_action );
	}

	/**
	 * Public function to uninstall
	 * */
	static function on_uninstall() {

		global $mainwp_itsec_setup_action;

		$mainwp_itsec_setup_action = 'uninstall';

		new MainWP_ITSEC_Setup( 'uninstall' );

	}

	/**
	 * Execute activation.
	 *
	 * @since 4.0
	 *
	 * @param boolean $upgrade true if the plugin is updating
	 *
	 * @return void
	 */
	private function activate_execute() {

		global $mainwp_itsec_globals;

		if ( ( $site_data = get_site_option( 'mainwp_itsec_data' ) ) === false ) {
			add_site_option( 'mainwp_itsec_data', array(), false );
		}

		// load utility functions
		if ( ! class_exists( 'MainWP_ITSEC_Lib' ) ) {
			require_once trailingslashit( $mainwp_itsec_globals['plugin_dir'] ) . 'core/class-itsec-lib.php';
		}

		$this->do_modules();

	}


	/**
	 * Deactivate execution
	 *
	 * @since 4.0
	 *
	 * @return void
	 * */
	private function deactivate_execute() {

		$this->do_modules();
		$mainwp_itsec_files = MainWP_ITSEC_Core::get_itsec_files();
		$mainwp_itsec_files->do_deactivate();

		flush_rewrite_rules();

	}

	private function upgrade_execute( $upgrade = false ) {

		global $mainwp_itsec_old_version, $mainwp_itsec_globals, $mainwp_itsec_setup_action;

		$mainwp_itsec_setup_action = 'upgrade';
		$mainwp_itsec_old_version  = $upgrade;

		$this->do_modules();

		$itsec_modules = MainWP_ITSEC_Modules::get_instance();
		$itsec_modules->run_upgrade( $mainwp_itsec_old_version, MainWP_ITSEC_Core::get_plugin_build() );

	}

	/**
	 * Uninstall execution
	 *
	 * @since 4.0
	 *
	 * @return void
	 * */
	private function uninstall_execute() {

		$this->deactivate_execute();
		$mainwp_itsec_files = MainWP_ITSEC_Core::get_itsec_files();
		$mainwp_itsec_files->do_deactivate();

	}

	/**
	 * Deletes all iThemes Security files.
	 *
	 * @access private
	 *
	 * @since  4.0
	 *
	 * @param string $path path of plugin files
	 *
	 * @return void
	 */
	private function recursive_delete( $path ) {

		foreach ( scandir( $path ) as $item ) {

			if ( $item != '.' && $item != '..' ) {

				if ( is_dir( $path . '/' . $item ) ) {
					$this->recursive_delete( $path . '/' . $item );
				}
			}

			@unlink( $path . '/' . $item );
		}

		@rmdir( $path );

	}

}
