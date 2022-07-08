<?php

class MainWPBackWPupJob {
	public $current_page      = '';
	public $plugin_translate  = 'mainwp-backwpup-extension';
	public $website_id        = 0;
	public $job_id            = 0;
	public $our_id            = 0;
	public $type              = '';
	public $tab_name          = '';
	public $original_tab_name = '';
	public $is_pro_extension  = false;


	public function __construct( $tab_name, $type, $website_id, $job_id, $our_id ) {
		$this->tab_name          = str_replace( array( 'jobtype-', 'dest-' ), '', $tab_name );
		$this->original_tab_name = $tab_name;
		$this->type              = $type;
		$this->website_id        = $website_id;
		$this->job_id            = $job_id;
		$this->our_id            = $our_id;

		if ( strcasecmp( $_REQUEST['page'], 'Extensions-Mainwp-Backwpup-Extension' ) === 0 ) {
			$this->current_page = admin_url( 'admin.php?page=Extensions-Mainwp-Backwpup-Extension&id=' . $this->website_id . '&our_job_id=' . $this->our_id );
		} else {
			$this->current_page = admin_url( 'admin.php?page=ManageSitesBackwpup&id=' . $this->website_id . '&our_job_id=' . $this->our_id );
		}
	}

	public function check_child_response( $response, $error_message ) {
		if ( ! isset( $response['success'] ) || $response['success'] != 1 ) {
			if ( isset( $response['error'] ) ) {
				wp_die( 'Child error in job: ' . $response['error'] );
			} else {
				print_r( $response );
				wp_die( 'Child error in job: ' . $error_message );
			}
		}
	}

	/**
	 * From BackWPup_Job::sanitize_file_name
	 * Sanitizes a filename, replacing whitespace with underscores.
	 *
	 * @param $filename
	 *
	 * @return mixed
	 */
	public static function sanitize_file_name( $filename ) {

		$filename = trim( $filename );

		$special_chars = array(
			'?',
			'[',
			']',
			'/',
			'\\',
			'=',
			'<',
			'>',
			':',
			';',
			',',
			"'",
			'"',
			'&',
			'$',
			'#',
			'*',
			'(',
			')',
			'|',
			'~',
			'`',
			'!',
			'{',
			'}',
			chr( 0 ),
		);

		$filename = str_replace( $special_chars, '', $filename );

		$filename = str_replace( array( ' ', '%20', '+' ), '_', $filename );
		$filename = str_replace( array( "\n", "\t", "\r" ), '-', $filename );
		$filename = trim( $filename, '.-_' );

		return $filename;
	}
}
