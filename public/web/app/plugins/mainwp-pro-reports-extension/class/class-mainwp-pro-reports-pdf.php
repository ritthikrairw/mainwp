<?php

/*
 * DOMPDF requires following configuration on your server
 * PHP > 5.3
 * DOM extension
 * GD extension
 * MB String extension
 * php-font-lib
 * php-svg-lib
*/


use Dompdf\Dompdf;
use Dompdf\Options;

class MainWP_Pro_Reports_Pdf {

	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_filter( 'mainwp_pro_reports_email_attachments', array( $this, 'reports_email_attachments' ), 99, 4 );
	}

	/**
	 * Attach PDF to mainwp pro report email
	 */
	public function reports_email_attachments( $attachments, $html, $report, $site_id = 0 ) {
		if ( empty( $html ) ) {
			return $attachments;
		}

		$report_id = $report->id;

		$tmp_path = $this->get_tmp_path( 'attachments' );

		try {
			$filename = $this->get_filename( $report, $site_id );
			$pdf_path = $tmp_path . $filename;

			$lock_file = true;

			// if file exists, reuse it if it's not older than 60 seconds
			$max_time = apply_filters( 'mainwp_pro_reports_reuse_attachment_time', 60 );

			if ( file_exists( $pdf_path ) && $max_time > 0 ) {
				// get last modification date
				if ( $filemtime = filemtime( $pdf_path ) ) {
					$time_difference = time() - $filemtime;
					if ( $time_difference < $max_time ) {
						// check file
						if ( $lock_file && $this->check_file_lock( $pdf_path ) === false ) {
							$attachments[] = $pdf_path;
						} else {
							error_log( "Attachment file locked (reusing: {$pdf_path})" );
						}
					}
				}
			}

			$pdf_settings = $this->get_pdf_settings();
			// get pdf data
			$pdf_data = $this->get_pdf( $html, $pdf_settings );

			if ( $lock_file ) {
				file_put_contents( $pdf_path, $pdf_data, LOCK_EX );
			} else {
				file_put_contents( $pdf_path, $pdf_data );
			}

			if ( $lock_file && $this->check_file_lock( $pdf_path ) === true ) {
				error_log( "Attachment file locked ({$pdf_path})" );
			}
			$attachments[] = $pdf_path;

		} catch ( \Exception $e ) {
			error_log( $e->getMessage() );
		} catch ( \Error $e ) {
			error_log( $e->getMessage() );
		}
		return $attachments;
	}

	public function get_pdf_settings() {
		return $pdf_settings = array(
			'paper_size'        => apply_filters( 'mainwp_pro_reports_pdf_paper_format', 'A4' ),
			'paper_orientation' => apply_filters( 'mainwp_pro_reports_pdf_paper_orientation', 'portrait' ),
			'font_subsetting'   => apply_filters( 'mainwp_pro_reports_pdf_font_subsetting', false ),
		);
	}

	/**
	 * Return tmp path
	 */
	public function get_tmp_path( $type = '' ) {

		$tmp_base = $this->get_tmp_base();
		// don't continue
		if ( $tmp_base === false ) {
			return false;
		}
		// check if tmp folder exists
		if ( ! @is_dir( $tmp_base ) ) {
			$this->init_tmp_folders( $tmp_base );
		}
		if ( empty( $type ) ) {
			return $tmp_base;
		}
		switch ( $type ) {
			case 'dompdf':
				$tmp_path = $tmp_base . 'dompdf';
				break;
			case 'font_cache':
			case 'fonts':
				$tmp_path = $tmp_base . 'fonts';
				break;
			case 'attachments':
				$tmp_path = $tmp_base . 'attachments/';
				break;
			default:
				$tmp_path = $tmp_base . $type;
				break;
		}
		// double check for existence, in case tmp_base was installed, but subfolder not created
		if ( ! @is_dir( $tmp_path ) ) {
			@mkdir( $tmp_path );
		}
		return $tmp_path;
	}


	/**
	 * return the base tmp folder (usually uploads)
	 */
	public function get_tmp_base() {
		// wp_upload_dir() is used to set the base temp folder
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			$tmp_base = false;
		} else {
			$upload_base = trailingslashit( $upload_dir['basedir'] );
			$tmp_base    = $upload_base . 'mainwp_pro_reports/';
		}
		// May be overridden by the mainwp_pro_reports_pdf_tmp_path filter
		$tmp_base = apply_filters( 'mainwp_pro_reports_pdf_tmp_path', $tmp_base );
		if ( $tmp_base !== false ) {
			$tmp_base = trailingslashit( $tmp_base );
		}
		return $tmp_base;
	}


	/**
	 * Install/create tmp folders
	 */
	public function init_tmp_folders( $tmp_base ) {
		// create plugin base temp folder
		mkdir( $tmp_base );

		if ( ! is_dir( $tmp_base ) ) {
			error_log( "Unable to create temp folder {$tmp_base}" );
		}

		// create subfolders & protect
		$subfolders = array( 'attachments', 'fonts', 'dompdf' );
		foreach ( $subfolders as $subfolder ) {
			$path = $tmp_base . $subfolder . '/';
			if ( ! is_dir( $path ) ) {
				mkdir( $path );
			}

			// copy font files
			if ( $subfolder == 'fonts' ) {
				$this->copy_fonts( $path, false );
			}

			// create .htaccess file and empty index.php to protect in case an open webfolder is used!
			file_put_contents( $path . '.htaccess', 'deny from all' );
			touch( $path . 'index.php' );
		}

	}

	/**
	 * Copy DOMPDF fonts to WordPress tmp folder
	 */
	public function copy_fonts( $path, $merge_with_local = true ) {
		$path = trailingslashit( $path );

		$dompdf_font_dir = MAINWP_PRO_REPORTS_PLUGIN_DIR . 'libs/dompdf/lib/fonts/';

		// get local font dir from filtered options
		$dompdf_options = apply_filters(
			'mainwp_pro_reports_dompdf_options',
			array(
				'defaultFont'             => 'lato',
				'tempDir'                 => $this->get_tmp_path( 'dompdf' ),
				'logOutputFile'           => $this->get_tmp_path( 'dompdf' ) . '/log.htm',
				'fontDir'                 => $this->get_tmp_path( 'fonts' ),
				'fontCache'               => $this->get_tmp_path( 'fonts' ),
				'isRemoteEnabled'         => true,
				'isFontSubsettingEnabled' => true,
				'isHtml5ParserEnabled'    => true,
			)
		);
		$fontDir        = $dompdf_options['fontDir'];

		// merge font family cache with local/custom if present
		$font_cache_files = array(
			'cache'      => 'dompdf_font_family_cache.php',
			'cache_dist' => 'dompdf_font_family_cache.dist.php',
		);
		foreach ( $font_cache_files as $font_cache_name => $font_cache_filename ) {
			$plugin_fonts = @require $dompdf_font_dir . $font_cache_filename;
			if ( $merge_with_local && is_readable( $path . $font_cache_filename ) ) {
				$local_fonts = @require $path . $font_cache_filename;
				if ( is_array( $local_fonts ) && is_array( $plugin_fonts ) ) {
					// merge local & plugin fonts, plugin fonts overwrite (update) local fonts
					// while custom local fonts are retained
					$local_fonts = array_merge( $local_fonts, $plugin_fonts );
					// create readable array with $fontDir in place of the actual folder for portability
					$fonts_export = var_export( $local_fonts, true );
					$fonts_export = str_replace( '\'' . $fontDir, '$fontDir . \'', $fonts_export );
					$cacheData    = sprintf( '<?php return %s;%s?>', $fonts_export, PHP_EOL );
					// write file with merged cache data
					file_put_contents( $path . $font_cache_filename, $cacheData );
				} else { // empty local file
					copy( $dompdf_font_dir . $font_cache_filename, $path . $font_cache_filename );
				}
			} else {
				// we couldn't read the local font cache file so we're simply copying over plugin cache file
				copy( $dompdf_font_dir . $font_cache_filename, $path . $font_cache_filename );
			}
		}

		// first try the easy way with glob!
		if ( function_exists( 'glob' ) ) {
			$files = glob( $dompdf_font_dir . '*.*' );
			foreach ( $files as $file ) {
				$filename = basename( $file );
				if ( ! is_dir( $file ) && is_readable( $file ) && ! in_array( $filename, $font_cache_files ) ) {
					$dest = $path . $filename;
					copy( $file, $dest );
				}
			}
		} else {
			// fallback method using font cache file (glob is disabled on some servers with disable_functions)
			$extensions   = array( '.ttf', '.ufm', '.ufm.php', '.afm', '.afm.php' );
			$fontDir      = untrailingslashit( $dompdf_font_dir );
			$plugin_fonts = @require $dompdf_font_dir . $font_cache_files['cache'];

			foreach ( $plugin_fonts as $font_family => $filenames ) {
				foreach ( $filenames as $filename ) {
					foreach ( $extensions as $extension ) {
						$file = $filename . $extension;
						if ( file_exists( $file ) ) {
							$dest = $path . basename( $file );
							copy( $file, $dest );
						}
					}
				}
			}
		}
	}


	public function get_filename( $report, $site_id = false ) {
		$name   = 'pro-report';
		$suffix = date( 'Y-m-d' ); // 2020-11-11

		$report_id = $report->id;

		if ( $report_id ) {
			$suffix .= '-' . $report_id;
		}

		if ( $site_id ) {
			$suffix .= '-' . $site_id;
		}

		$filename = $name . '-' . $suffix . '.pdf';
		$filename = apply_filters( 'mainwp_pro_reports_pdf_filename', $filename, $report, $site_id );

		if ( substr( $filename, -4 ) !== '.pdf' ) {
			$filename .= '.pdf';
		}

		// sanitize filename.
		return sanitize_file_name( $filename );
	}

	public function admin_init() {
		if ( isset( $_GET['action'] ) && ( 'saveaspdf' == $_GET['action'] ) && isset( $_GET['_nonce_savepdf'] ) && wp_verify_nonce( $_GET['_nonce_savepdf'], '_nonce_savepdf' ) && isset( $_GET['id'] ) && ! empty( $_GET['id'] ) && isset( $_GET['time'] ) ) {
			$html      = '';
			$report_id = $site_id = 0;
			if ( isset( $_GET['id'] ) && $_GET['id'] && isset( $_GET['siteid'] ) && $_GET['siteid'] && isset( $_GET['time'] ) && ! empty( $_GET['time'] ) ) {
				$time      = sanitize_key( $_GET['time'] );
				$report_id = intval( $_GET['id'] );
				$site_id   = intval( $_GET['siteid'] );
				$content   = get_transient( 'mainwp_report_pdf_' . $time . '_' . $site_id . '_' . $report_id );
				if ( ! empty( $content ) ) {
					$html = json_decode( $content );
					delete_transient( 'mainwp_report_pdf_' . $time . '_' . $site_id . '_' . $report_id );
				} else {
					echo 'Error: empty report content, please try again.';
				}
			}
			$this->output_pdf( $html, $report_id, $site_id );
		}
	}

	public function output_pdf( $html, $report_id, $site_id = 0, $output_mode = 'download' ) {
		$pdf_settings = $this->get_pdf_settings();
		$pdf          = $this->get_pdf( $html, $pdf_settings );
		$report       = MainWP_Pro_Reports_DB::get_instance()->get_report_by( 'id', $report_id );
		$this->output_pdf_headers( $this->get_filename( $report, $site_id ), $output_mode, $pdf );
		echo $pdf;
		die();
	}

	public function get_pdf( $html, $settings = array() ) {

		if ( empty( $html ) ) {
			return;
		}

		global $_dompdf_show_warnings;
		$_dompdf_show_warnings = true;

		$default_settings = array(
			'paper_size'        => 'A4',
			'paper_orientation' => 'portrait',
			'font_subsetting'   => true,
		);

		$pdf_settings = $settings + $default_settings;

		require_once MAINWP_PRO_REPORTS_PLUGIN_DIR . 'libs/dompdf/autoload.inc.php';

		// set options
		$options = new Options(
			apply_filters(
				'mainwp_pro_reports_dompdf_options',
				array(
					'defaultFont'             => 'lato',
					'tempDir'                 => $this->get_tmp_path( 'dompdf' ),
					'logOutputFile'           => $this->get_tmp_path( 'dompdf' ) . '/log.htm',
					'fontDir'                 => $this->get_tmp_path( 'fonts' ),
					'fontCache'               => $this->get_tmp_path( 'fonts' ),
					'isRemoteEnabled'         => true,
					'isFontSubsettingEnabled' => $pdf_settings['font_subsetting'],
					'isHtml5ParserEnabled'    => extension_loaded( 'iconv' ) ? true : false,
				)
			)
		);

		libxml_use_internal_errors( true );
		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( $pdf_settings['paper_size'], $pdf_settings['paper_orientation'] );
		$dompdf->render();
		$return = $dompdf->output();
		libxml_use_internal_errors( false );
		return $return;
	}

	function output_pdf_headers( $filename, $mode = 'inline', $pdf = null ) {
		switch ( $mode ) {
			case 'download':
				header( 'Content-Description: File Transfer' );
				header( 'Content-Type: application/pdf' );
				header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Connection: Keep-Alive' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
				header( 'Pragma: public' );
				break;
			case 'inline':
			default:
				header( 'Content-type: application/pdf' );
				header( 'Content-Disposition: inline; filename="' . $filename . '"' );
				break;
		}
	}

	public function check_file_lock( $path ) {
		$fp = fopen( $path, 'r+' );
		if ( $locked = $this->file_is_locked( $fp ) ) {
			// delay (ms) to double check
			$delay = intval( apply_filters( 'mainwp_pro_reports_attachment_locked_file_delay', 250 ) );
			if ( $delay > 0 ) {
				usleep( $delay * 1000 );
				$locked = $this->file_is_locked( $fp );
			}
		}
		fclose( $fp );

		return $locked;
	}


	public function file_is_locked( $fp ) {
		if ( ! flock( $fp, LOCK_EX | LOCK_NB, $locked ) ) {
			if ( $locked ) {
				return true; // file is locked
			} else {
				return true; // can't lock for whatever reason (could be locked in Windows + PHP5.3)
			}
		} else {
			flock( $fp, LOCK_UN ); // release lock
			return false; // not locked
		}
	}

}
