<?php

class MainWP_Pro_Reports_Template {

	private $template_base        = '';
	private $template_custom_base = '';

	private $template_email_base        = '';
	private $template_email_custom_base = '';

	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->template_base        = MAINWP_PRO_REPORTS_PLUGIN_DIR . 'templates/reports/';
		$this->template_custom_base = $this->get_mainwp_sub_dir( 'report-templates' );

		$this->template_email_base        = MAINWP_PRO_REPORTS_PLUGIN_DIR . 'templates/emails/';
		$this->template_email_custom_base = $this->get_mainwp_sub_dir( 'report-email-templates' );
	}


	private function get_mainwp_dir( $folder ) {
			return apply_filters( 'mainwp_getmainwpdir', false, $folder, false );
	}

	public function get_mainwp_sub_dir( $dir ) {
		$dirs = $this->get_mainwp_dir( $dir );
		return $dirs[0];
	}


	public function get_template_file_path( $templ, $website = false ) {
		if ( empty( $templ ) ) {
			return '';
		}
		if ( stripos( $templ, '/custom-template/' ) !== false ) {
			$filename = str_replace( '/custom-template/', '', $templ );
			$path     = $this->template_custom_base . $filename;
		} else {
			$path = $this->template_base . $templ;
		}

		return apply_filters( 'mainwp_pro_reports_template_file_path', $path, $templ, $website );
	}


	public function get_template_email_file_path( $templ ) {
		if ( empty( $templ ) ) {
			return '';
		}
		if ( stripos( $templ, '/custom-template-email/' ) !== false ) {
			$filename = str_replace( '/custom-template-email/', '', $templ );
			$path     = $this->template_email_custom_base . $filename;
		} else {
			$path = $this->template_email_base . $templ;
		}
		return $path;
	}


	public function get_template_email_files() {

		$templates = array();
		// get core templates
		$scan_dir   = $this->template_email_base;
		$handle     = @opendir( $scan_dir );
		$scan_files = array();
		if ( $handle ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( '.' == substr( $file, 0, 1 ) || 'index.php' == $file ) {
					continue;
				}
				if ( '.php' !== substr( $file, - 4 ) ) {
					continue;
				}

				$templates[ $file ] = $file;
			}
			closedir( $handle );
		}

		// get custom templates
		$scan_dir = $this->template_email_custom_base;

		$handle = @opendir( $scan_dir );

		$scan_files = array();

		if ( $handle ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( '.' == substr( $file, 0, 1 ) || 'index.php' == $file ) {
					continue;
				}
				if ( '.php' !== substr( $file, - 4 ) ) {
					continue;
				}
				$index               = '/custom-template-email/' . $file;
				$templates[ $index ] = $file;
			}
			closedir( $handle );
		}
		return $templates;
	}

	/**
	 *
	 * Get template content for email.
	 */
	public function get_template_email_file_content( $report, $email_message ) {

			$templ = $report->template_email;

		if ( $templ == '' ) {
			return $email_message;
		}

			$path = $this->get_template_email_file_path( $templ );

		if ( $path == '' || ! file_exists( $path ) ) {
			return $email_message;
		}

			ob_start();
			require_once $path;
			$content = ob_get_clean();
			return $content;
	}

	public function get_template_file_content( $report, $website, $enable_woocom = false ) {

		$templ = $report->template;

		if ( $templ == '' ) {
			return '';
		}

		$path = $this->get_template_file_path( $templ, $website );

		if ( $path == '' || ! file_exists( $path ) ) {
			do_action( 'mainwp_log_action', 'Pro Reports :: ERROR :: CRON :: Invalid template file :: ' . $templ . ' :: ' .  $path, MAINWP_PRO_REPORTS_LOG_PRIORITY_NUMBER );
			return '';
		}

		ob_start();
		require_once $path;
		$content = ob_get_clean();
		return apply_filters( 'mainwp_pro_reports_template_file_content', $content, $report, $website );
	}


	public function get_template_files() {

		$templates = array();
		// get core templates
		$scan_dir   = $this->template_base;
		$handle     = @opendir( $scan_dir );
		$scan_files = array();
		if ( $handle ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( '.' == substr( $file, 0, 1 ) || 'index.php' == $file ) {
					continue;
				}
				if ( '.php' !== substr( $file, - 4 ) ) {
					continue;
				}

				$templates[ $file ] = $file;
			}
			closedir( $handle );
		}

		// get custom templates
		$scan_dir = $this->template_custom_base;

		$handle = @opendir( $scan_dir );

		$scan_files = array();

		if ( $handle ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( '.' == substr( $file, 0, 1 ) || 'index.php' == $file ) {
					continue;
				}
				if ( '.php' !== substr( $file, - 4 ) ) {
					continue;
				}
				$index               = '/custom-template/' . $file;
				$templates[ $index ] = $file;
			}
			closedir( $handle );
		}

		return apply_filters( 'mainwp_pro_reports_template_files', $templates );
	}

}
