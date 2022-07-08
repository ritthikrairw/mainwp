<?php

/**
 * File Change Detection Execution and Processing
 *
 * Handles all file change detection execution once the feature has been
 * enabled by the user.
 *
 * @since   4.0.0
 *
 * @package iThemes_Security
 */
class MainWP_ITSEC_File_Change {

	/**
	 * Setup the module's functionality
	 *
	 * Loads the file change detection module's unpriviledged functionality including
	 * performing the scans themselves
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	function run() {

		global $mainwp_itsec_globals;

		add_filter( 'mainwp_itsec_logger_displays', array( $this, 'itsec_logger_displays' ) ); // adds logs metaboxes
		add_filter( 'mainwp_itsec_logger_modules', array( $this, 'itsec_logger_modules' ) );
		add_filter( 'mainwp_itsec_sync_modules', array( $this, 'itsec_sync_modules' ) ); // register sync modules
		add_filter( 'mainwp_itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'mainwp_itsec_file-change_notification_strings', array( $this, 'notification_strings' ) );
	}

	/**
	 * Register file change detection for logger
	 *
	 * Registers the file change detection module with the core logger functionality.
	 *
	 * @since 4.0.0
	 *
	 * @param  array $logger_modules array of logger modules
	 *
	 * @return array array of logger modules
	 */
	public function itsec_logger_modules( $logger_modules ) {

		$logger_modules['file_change'] = array(
			'type'     => 'file_change',
			'function' => __( 'File Changes Detected', 'l10n-mainwp-ithemes-security-extension' ),
		);

		return $logger_modules;

	}

	/**
	 * Array of displays for the logs screen
	 *
	 * Registers the custom log page with the core plugin to allow for access from the log page's
	 * dropdown menu.
	 *
	 * @since 4.0.0
	 *
	 * @param array $displays metabox array
	 *
	 * @return array metabox array
	 */
	public function itsec_logger_displays( $displays ) {

		$displays[] = array(
			'module'   => 'file_change',
			'title'    => __( 'File Change History', 'l10n-mainwp-ithemes-security-extension' ),
			'callback' => array( $this, 'logs_metabox_content' ),
		);

		return $displays;

	}

	/**
	 * Render the file change log metabox
	 *
	 * Displays a metabox on the logs page, when filtered, showing all file change items.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function logs_metabox_content() {

		global $mainwp_itsec_globals;

		if ( ! class_exists( 'MainWP_ITSEC_File_Change_Log' ) ) {
			require_once dirname( __FILE__ ) . '/class-itsec-file-change-log.php';
		}

		$settings = MainWP_ITSEC_Modules::get_settings( 'file-change' );

		// If we're splitting the file check run it every 6 hours. Else daily.
		if ( isset( $settings['split'] ) && true === $settings['split'] ) {

			$interval = 12342;

		} else {

			$interval = 86400;

		}

		$next_run_raw = $settings['last_run'] + $interval;

		if ( date( 'j', $next_run_raw ) == date( 'j', $mainwp_itsec_globals['current_time'] ) ) {
			$next_run_day = __( 'Today', 'l10n-mainwp-ithemes-security-extension' );
		} else {
			$next_run_day = __( 'Tomorrow', 'l10n-mainwp-ithemes-security-extension' );
		}

		$next_run = $next_run_day . ' at ' . date( 'g:i a', $next_run_raw );

		echo '<p>' . __( 'Next automatic scan at: ', 'l10n-mainwp-ithemes-security-extension' ) . '<strong>' . $next_run . '*</strong></p>';
		echo '<p><em>*' . __( 'Automatic file change scanning is triggered by a user visiting your page and may not happen exactly at the time listed.', 'l10n-mainwp-ithemes-security-extension' ) . '</em>';

		$log_display = new MainWP_ITSEC_File_Change_Log();

		$log_display->prepare_items();
		$log_display->display();

	}

	/**
	 * Register file change detection for Sync
	 *
	 * Reigsters iThemes Sync verbs for the file change detection module.
	 *
	 * @since 4.0.0
	 *
	 * @param  array $sync_modules array of sync modules
	 *
	 * @return array array of sync modules
	 */
	public function itsec_sync_modules( $sync_modules ) {

		$sync_modules['file-change'] = array(
			'verbs' => array(
				'itsec-perform-file-scan' => 'Ithemes_Sync_Verb_ITSEC_Perform_File_Scan',
			),
			'path'  => dirname( __FILE__ ),
		);

		return $sync_modules;

	}

	public function register_notification( $notifications ) {
		$notifications['file-change'] = array(
			'recipient'        => MainWP_ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE,
			'schedule'         => MainWP_ITSEC_Notification_Center::S_NONE,
			'subject_editable' => true,
			'optional'         => true,
			'module'           => 'file-change',
		);

		return $notifications;
	}

	public function notification_strings() {
		return array(
			'label'       => esc_html__( 'File Change', 'l10n-mainwp-ithemes-security-extension' ),
			'description' => '',
			'subject'     => esc_html__( 'File Change Warning', 'l10n-mainwp-ithemes-security-extension' ),
			'order'       => 4,
		);
	}

}
