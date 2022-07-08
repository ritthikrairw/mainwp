<?php

/**
 * Backup execution.
 *
 * Handles database backups at scheduled interval.
 *
 * @since   4.0.0
 *
 * @package iThemes_Security
 */
class MainWP_ITSEC_Backup {

	/**
	 * The module's saved options
	 *
	 * @since  4.0.0
	 * @access private
	 * @var array
	 */
	private $settings;

	/**
	 * Setup the module's functionality.
	 *
	 * Loads the backup detection module's unpriviledged functionality including
	 * performing the scans themselves.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	function run() {

		global $mainwp_itsec_globals;

		$this->settings = MainWP_ITSEC_Modules::get_settings( 'backup' );

		add_filter( 'mainwp_itsec_logger_modules', array( $this, 'register_logger' ) );
		add_filter( 'mainwp_itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'mainwp_itsec_backup_notification_strings', array( $this, 'notification_strings' ) );

	}

	/**
	 * Public function to get lock and call backup.
	 *
	 * Attempts to get a lock to prevent concurrant backups and calls the backup function itself.
	 *
	 * @since 4.0.0
	 *
	 * @param  boolean $one_time whether this is a one time backup
	 *
	 * @return mixed false on error or nothing
	 */
	public function do_backup( $one_time = false ) {

	}

	private function execute_backup( $one_time = false ) {

	}

	/**
	 * Register backups for logger.
	 *
	 * Adds the backup module to MainWP_ITSEC_Logger.
	 *
	 * @since 4.0.0
	 *
	 * @param  array $logger_modules array of logger modules
	 *
	 * @return array                   array of logger modules
	 */
	public function register_logger( $logger_modules ) {

		$logger_modules['backup'] = array(
			'type'     => 'backup',
			'function' => __( 'Database Backup Executed', 'l10n-mainwp-ithemes-security-extension' ),
		);

		return $logger_modules;

	}

	public function register_notification( $notifications ) {

		$method = MainWP_ITSEC_Modules::get_setting( 'backup', 'method' );

		if ( 0 === $method || 1 === $method ) {
			$notifications['backup'] = array(
				'subject_editable' => true,
				'recipient'        => MainWP_ITSEC_Notification_Center::R_EMAIL_LIST,
				'schedule'         => MainWP_ITSEC_Notification_Center::S_NONE,
				'module'           => 'backup',
			);
		}

		return $notifications;
	}

	public function notification_strings() {
		return array(
			'label'       => esc_html__( 'Database Backup', 'l10n-mainwp-ithemes-security-extension' ),
			'description' => '',
			'subject'     => esc_html__( 'Database Backup', 'l10n-mainwp-ithemes-security-extension' ),
			'order'       => 3,
		);
	}

	/**
	 * Set HTML content type for email.
	 *
	 * Sets the content type on outgoing emails to HTML.
	 *
	 * @since 4.0.0
	 *
	 * @return string html content type
	 */
	public function set_html_content_type() {

		return 'text/html';

	}

}
