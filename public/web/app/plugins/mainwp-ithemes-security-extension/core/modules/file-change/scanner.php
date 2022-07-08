<?php

final class MainWP_ITSEC_File_Change_Scanner {

	/**
	 * Files and directories to be excluded from the scan
	 *
	 * @since  4.0.0
	 * @access private
	 * @var array
	 */
	private $excludes;

	/**
	 * Flag to indicate if a file change scan is in process
	 *
	 * @since  4.0.0
	 * @access private
	 * @var bool
	 */
	private $running;

	/**
	 * The module's saved options
	 *
	 * @since  4.0.0
	 * @access private
	 * @var array
	 */
	private $settings;

	private static $instance = false;


	private function __construct() {

		global $mainwp_itsec_globals;

		$this->settings = MainWP_ITSEC_Modules::get_settings( 'file-change' );
		$this->running  = false;
		$this->excludes = array(
			'file_change.lock',
			MainWP_ITSEC_Modules::get_setting( 'backup', 'location' ),
			MainWP_ITSEC_Modules::get_setting( 'global', 'log_location' ),
			'.lock',
		);

	}

	
	/**
	 * Set HTML content type for email
	 *
	 * This filter allows for the content type of the file change notification emails to be set to
	 * HTML in order to send the tables and related data included in file change reporting.
	 *
	 * @since 4.0.0
	 *
	 * @return string html content type
	 */
	public function set_html_content_type() {

		return 'text/html';

	}

}
