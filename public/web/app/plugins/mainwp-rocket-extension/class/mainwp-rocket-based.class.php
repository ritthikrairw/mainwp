<?php
/*
 *
 * Credits
 *
 * Plugin-Name: WP Rocket
 * Plugin URI: https://wp-rocket.me
 * Description: The best WordPress performance plugin.
 * Version: 3.3.3.1
 * Code Name: Dagobah
 * Author: WP Media
 * Author URI: https://wp-media.me
 * Licence: GPLv2 or later
 *
*/

class MainWP_Rocket_Based {

	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Rocket_Based();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_post_mainwp_rocket_export', array( $this, 'do_options_export' ) );
		add_action( 'admin_post_mainwp_rocket_optimize_database', array( $this, 'do_optimize_database' ) );
	}

	function register_setting() {
		register_setting( 'mainwp_wp_rocket', MAINWP_WP_ROCKET_SLUG, array( $this, 'settings_callback' ) );
	}

	/**
	 * Used to clean and sanitize the settings fields
	 *
	 * @since 1.0
	 */
	function settings_callback( $inputs ) {

		if ( isset( $_FILES['import'] ) && isset( $_POST['import'] ) ) {
			$this->handle_settings_import();
			return; // all proccess in handle function
		}

		/*
		 * Option : Minification CSS & JS
		 */
		$inputs['minify_css'] = ! empty( $inputs['minify_css'] ) ? 1 : 0;
		$inputs['minify_js']  = ! empty( $inputs['minify_js'] ) ? 1 : 0;

		/*
		 * Option : Purge delay
		 */
		$inputs['purge_cron_interval'] = isset( $inputs['purge_cron_interval'] ) ? (int) $inputs['purge_cron_interval'] : mainwp_get_rocket_option( 'purge_cron_interval' );
		$inputs['purge_cron_unit']     = isset( $inputs['purge_cron_unit'] ) ? $inputs['purge_cron_unit'] : mainwp_get_rocket_option( 'purge_cron_unit' );
		/*
		 * Option : Prefetch DNS requests
		 */
		if ( ! empty( $inputs['dns_prefetch'] ) ) {
			if ( ! is_array( $inputs['dns_prefetch'] ) ) {
				$inputs['dns_prefetch'] = explode( "\n", $inputs['dns_prefetch'] );
			}
			$inputs['dns_prefetch'] = array_map( 'trim', $inputs['dns_prefetch'] );
			$inputs['dns_prefetch'] = array_map( 'esc_url', $inputs['dns_prefetch'] );
			$inputs['dns_prefetch'] = (array) array_filter( $inputs['dns_prefetch'] );
			$inputs['dns_prefetch'] = array_unique( $inputs['dns_prefetch'] );
		} else {
			$inputs['dns_prefetch'] = array();
		}

		/*
		 * Option : Empty the cache of the following pages when updating an article
		 */
		if ( ! empty( $inputs['cache_purge_pages'] ) ) {
			if ( ! is_array( $inputs['cache_purge_pages'] ) ) {
				$inputs['cache_purge_pages'] = explode( "\n", $inputs['cache_purge_pages'] );
			}
			$inputs['cache_purge_pages'] = array_map( 'trim', $inputs['cache_purge_pages'] );
			$inputs['cache_purge_pages'] = array_map( 'esc_url', $inputs['cache_purge_pages'] );
			$inputs['cache_purge_pages'] = array_map( 'mainwp_rocket_clean_exclude_file', $inputs['cache_purge_pages'] );
			$inputs['cache_purge_pages'] = (array) array_filter( $inputs['cache_purge_pages'] );
			$inputs['cache_purge_pages'] = array_unique( $inputs['cache_purge_pages'] );
		} else {
			$inputs['cache_purge_pages'] = array();
		}

		/*
		 * Option : Never cache the following pages
		 */
		if ( ! empty( $inputs['cache_reject_uri'] ) ) {
			if ( ! is_array( $inputs['cache_reject_uri'] ) ) {
				$inputs['cache_reject_uri'] = explode( "\n", $inputs['cache_reject_uri'] );
			}
			$inputs['cache_reject_uri'] = array_map( 'trim', $inputs['cache_reject_uri'] );
			$inputs['cache_reject_uri'] = array_map( 'esc_url', $inputs['cache_reject_uri'] );
			$inputs['cache_reject_uri'] = array_map( 'mainwp_rocket_clean_exclude_file', $inputs['cache_reject_uri'] );
			$inputs['cache_reject_uri'] = (array) array_filter( $inputs['cache_reject_uri'] );
			$inputs['cache_reject_uri'] = array_unique( $inputs['cache_reject_uri'] );
		} else {
			$inputs['cache_reject_uri'] = array();
		}

		/*
		 * Option : Don't cache pages that use the following cookies
		 */
		if ( ! empty( $inputs['cache_reject_cookies'] ) ) {
			if ( ! is_array( $inputs['cache_reject_cookies'] ) ) {
				$inputs['cache_reject_cookies'] = explode( "\n", $inputs['cache_reject_cookies'] );
			}
			$inputs['cache_reject_cookies'] = array_map( 'trim', $inputs['cache_reject_cookies'] );
			$inputs['cache_reject_cookies'] = array_map( 'sanitize_key', $inputs['cache_reject_cookies'] );
			$inputs['cache_reject_cookies'] = (array) array_filter( $inputs['cache_reject_cookies'] );
			$inputs['cache_reject_cookies'] = array_unique( $inputs['cache_reject_cookies'] );
		} else {
			$inputs['cache_reject_cookies'] = array();
		}

		/*
		 * Option : Cache pages that use the following query strings (GET parameters)
		 */
		if ( ! empty( $inputs['cache_query_strings'] ) ) {
			if ( ! is_array( $inputs['cache_query_strings'] ) ) {
				$inputs['cache_query_strings'] = explode( "\n", $inputs['cache_query_strings'] );
			}
			$inputs['cache_query_strings'] = array_map( 'trim', $inputs['cache_query_strings'] );
			$inputs['cache_query_strings'] = array_map( 'sanitize_key', $inputs['cache_query_strings'] );
			$inputs['cache_query_strings'] = (array) array_filter( $inputs['cache_query_strings'] );
			$inputs['cache_query_strings'] = array_unique( $inputs['cache_query_strings'] );
		} else {
			$inputs['cache_query_strings'] = array();
		}

		/*
		 * Option : Never send cache pages for these user agents
		 */
		if ( ! empty( $inputs['cache_reject_ua'] ) ) {
			if ( ! is_array( $inputs['cache_reject_ua'] ) ) {
				$inputs['cache_reject_ua'] = explode( "\n", $inputs['cache_reject_ua'] );
			}
			$inputs['cache_reject_ua'] = array_map( 'trim', $inputs['cache_reject_ua'] );
			$inputs['cache_reject_ua'] = array_map( 'esc_textarea', $inputs['cache_reject_ua'] );
			$inputs['cache_reject_ua'] = (array) array_filter( $inputs['cache_reject_ua'] );
			$inputs['cache_reject_ua'] = array_unique( $inputs['cache_reject_ua'] );
		} else {
			$inputs['cache_reject_ua'] = array();
		}

		/*
		 * Option : CSS files to exclude of the minification
		 */
		$inputs['exclude_css'] = $this->sanitize_excluded_files( $inputs, 'exclude_css' );

		/*
		 * Option : JS files to exclude of the minification
		 */
		$inputs['exclude_js'] = $this->sanitize_excluded_files( $inputs, 'exclude_js' );

		// Option: inline JS patterns to exclude from combine JS.
		if ( ! empty( $inputs['exclude_inline_js'] ) ) {
			if ( ! is_array( $inputs['exclude_inline_js'] ) ) {
				$inputs['exclude_inline_js'] = explode( "\n", $inputs['exclude_inline_js'] );
			}

			$inputs['exclude_inline_js'] = array_map( 'sanitize_text_field', $inputs['exclude_inline_js'] );

			$inputs['exclude_inline_js'] = array_filter( $inputs['exclude_inline_js'] );
			$inputs['exclude_inline_js'] = array_unique( $inputs['exclude_inline_js'] );
		} else {
			$inputs['exclude_inline_js'] = array();
		}

		// Option: Critical CSS
		$inputs['critical_css'] = ! empty( $inputs['critical_css'] ) ? wp_filter_nohtml_kses( $inputs['critical_css'] ) : '';

		$inputs['defer_all_js'] = ! empty( $inputs['defer_all_js'] ) ? 1 : 0;

		/*
		 * Option : JS files with deferred loading
		 */
		$inputs['exclude_defer_js'] = $this->sanitize_excluded_files( $inputs, 'exclude_defer_js' );

		$inputs['delay_js'] = ! empty( $inputs['delay_js'] ) ? 1 : 0;

		 /*
		 * Option : JS files with deferred loading
		 */
		$inputs['delay_js_scripts'] = $this->sanitize_excluded_files( $inputs, 'delay_js_scripts' );

		 /*
		 * Option : JS files with deferred loading
		 */
		$inputs['exclude_lazyload'] = $this->sanitize_excluded_files( $inputs, 'exclude_lazyload' );


		 /*
		 * Performs the database optimization when settings are saved with the "save and optimize" submit button"
		 */
		$optimize = false;
		if ( ! empty( $_POST ) && isset( $_POST['mainwp_wp_rocket_settings']['submit_optimize'] ) ) {
			$optimize = true;
		}

		/**
		 * Database options
		 */
		$inputs['database_revisions']          = ! empty( $inputs['database_revisions'] ) ? 1 : 0;
		$inputs['database_auto_drafts']        = ! empty( $inputs['database_auto_drafts'] ) ? 1 : 0;
		$inputs['database_trashed_posts']      = ! empty( $inputs['database_trashed_posts'] ) ? 1 : 0;
		$inputs['database_spam_comments']      = ! empty( $inputs['database_spam_comments'] ) ? 1 : 0;
		$inputs['database_trashed_comments']   = ! empty( $inputs['database_trashed_comments'] ) ? 1 : 0;
		$inputs['database_expired_transients'] = ! empty( $inputs['database_expired_transients'] ) ? 1 : 0;
		$inputs['database_all_transients']     = ! empty( $inputs['database_all_transients'] ) ? 1 : 0;
		$inputs['database_optimize_tables']    = ! empty( $inputs['database_optimize_tables'] ) ? 1 : 0;
		$inputs['schedule_automatic_cleanup']  = ! empty( $inputs['schedule_automatic_cleanup'] ) ? 1 : 0;

		if ( $inputs['schedule_automatic_cleanup'] != 1 && ( 'daily' != $inputs['automatic_cleanup_frequency'] || 'weekly' != $inputs['automatic_cleanup_frequency'] || 'monthly' != $inputs['automatic_cleanup_frequency'] ) ) {
			unset( $inputs['automatic_cleanup_frequency'] );
		}

		/**
		 * Options: Activate bot preload
		 */
		$inputs['manual_preload']    = ! empty( $inputs['manual_preload'] ) ? 1 : 0;
		$inputs['automatic_preload'] = ! empty( $inputs['automatic_preload'] ) ? 1 : 0;

		/*
		 * Option: activate sitemap preload
		 */
		$inputs['sitemap_preload'] = ! empty( $inputs['sitemap_preload'] ) ? 1 : 0;

		/*
		 * Option : XML sitemaps URLs
		 */
		if ( ! empty( $inputs['sitemaps'] ) ) {
			if ( ! is_array( $inputs['sitemaps'] ) ) {
				$inputs['sitemaps'] = explode( "\n", $inputs['sitemaps'] );
			}
			$inputs['sitemaps'] = array_map( 'trim', $inputs['sitemaps'] );
			// $inputs['sitemaps'] = array_map( 'rocket_sanitize_xml', $inputs['sitemaps'] );
			$inputs['sitemaps'] = (array) array_filter( $inputs['sitemaps'] );
			$inputs['sitemaps'] = array_unique( $inputs['sitemaps'] );
		} else {
			$inputs['sitemaps'] = array();
		}

		/*
		 * Option : CDN
		 */
		$inputs['cdn_cnames'] = isset( $inputs['cdn_cnames'] ) ? array_unique( array_filter( $inputs['cdn_cnames'] ) ) : array();

		if ( ! $inputs['cdn_cnames'] ) {
			$inputs['cdn_zone'] = array();
		} else {
			for ( $i = 0; $i <= max( array_keys( $inputs['cdn_cnames'] ) ); $i++ ) {
				if ( ! isset( $inputs['cdn_cnames'][ $i ] ) ) {
					unset( $inputs['cdn_zone'][ $i ] );
				} else {
					$inputs['cdn_zone'][ $i ] = isset( $inputs['cdn_zone'][ $i ] ) ? $inputs['cdn_zone'][ $i ] : 'all';
				}
			}

			$inputs['cdn_cnames'] = array_values( $inputs['cdn_cnames'] );
			ksort( $inputs['cdn_zone'] );
			$inputs['cdn_zone'] = array_values( $inputs['cdn_zone'] );
		}

		$inputs['cdn_reject_files'] = $this->sanitize_excluded_files( $inputs, 'cdn_reject_files' );

		/*
		 * Option: Support
		 */
		$fake_options = array(
			'support_summary',
			'support_description',
			'support_documentation_validation',
		);

		foreach ( $fake_options as $option ) {
			if ( isset( $inputs[ $option ] ) ) {
				unset( $inputs[ $option ] );
			}
		}
		$message = 1;

		$site_id = 0;
		if ( isset( $_POST['mainwp_rocket_current_site_id'] ) && ! empty( $_POST['mainwp_rocket_current_site_id'] ) ) {
			$site_id = $_POST['mainwp_rocket_current_site_id'];
		}

		if ( MainWP_Rocket::is_manage_sites_page() ) {
			if ( $site_id ) {
				$update = array(
					'site_id'  => $site_id,
					'settings' => base64_encode( serialize( $inputs ) ),
				);
				MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
			}
		} else {
			update_option( MAINWP_ROCKET_GENERAL_SETTINGS, $inputs );
		}

			wp_redirect(
				add_query_arg(
					array(
						'_perform_action' => 'mainwp_rocket_save_opts_child_sites',
						'_wpnonce'        => wp_create_nonce( 'mainwp_rocket_save_opts_child_sites' ),
						'optimize_db'     => $optimize,
						'message'         => $message,
					),
					remove_query_arg( 's', wp_get_referer() )
				)
			);

			die();
	}

	public function sanitize_excluded_files( $data, $field ) {
		$return = array();
		if ( ! empty( $data[ $field ] ) ) {
			if ( ! is_array( $data[ $field ] ) ) {
				$data[ $field ] = explode( "\n", $data[ $field ] );
			}			
			$return = array_map( 'trim', $data[ $field ] );
			$return = array_map( 'mainwp_rocket_clean_exclude_file', $return );
			$return = (array) array_filter( $return );
			$return = array_unique( $return );
		}
		return $return;
	}

	function do_options_export() {

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'mainwp_rocket_export' ) ) {
			return;
		}

		$options = null;
		if ( isset( $_GET['id'] ) ) {
			$options = MainWP_Rocket_DB::get_instance()->get_wprocket_settings_by( 'site_id', $_GET['id'] );
		} else {
			$options = get_option( MAINWP_ROCKET_GENERAL_SETTINGS );
		}

		if ( empty( $options ) ) {
			return;
		}
		// old export
		// $filename = sprintf( 'wp-rocket-settings-%s-%s.txt', date( 'Y-m-d' ), uniqid() );
		// $gz = 'gz' . strrev( 'etalfed' );
		// $options = $gz//;
		// ( serialize( $options ), 1 );
		// nocache_headers();
		// @header( 'Content-Type: text/plain' );
		// @header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		// @header( 'Content-Transfer-Encoding: binary' );
		// @header( 'Content-Length: ' . strlen( $options ) );
		// @header( 'Connection: close' );
		// echo $options;
		// exit();

		$filename = sprintf( 'wp-rocket-settings-%s-%s.json', date( 'Y-m-d' ), uniqid() );
		$gz       = 'gz' . strrev( 'etalfed' );
		$options  = wp_json_encode( $options ); // do not use get_rocket_option() here.
		nocache_headers();
		@header( 'Content-Type: application/json' );
		@header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		@header( 'Content-Transfer-Encoding: binary' );
		@header( 'Content-Length: ' . strlen( $options ) );
		@header( 'Connection: close' );
		echo $options;
		exit();

	}

	function do_optimize_database() {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'mainwp_rocket_nonce_optimize_database' ) ) {
			wp_nonce_ays( '' );
		}
		wp_redirect(
			add_query_arg(
				array(
					'_perform_action' => 'mainwp_rocket_optimize_database',
					'_wpnonce'        => wp_create_nonce( 'mainwp_rocket_optimize_database' ),
				),
				remove_query_arg( 's', wp_get_referer() )
			)
		);
		die();
	}

	function handle_settings_import() {

		if ( ! isset( $_FILES['import'] ) || 0 === $_FILES['import']['size'] ) {
			mainwp_rocket_settings_import_redirect( __( 'Settings import failed: no file uploaded.', 'rocket' ), 'error' );
		}

		if ( ! preg_match( '/wp-rocket-settings-20\d{2}-\d{2}-\d{2}-[a-f0-9]{13}\.(?:txt|json)/', $_FILES['import']['name'] ) ) {
			mainwp_rocket_settings_import_redirect( __( 'Settings import failed: incorrect filename.', 'rocket' ), 'error' );
		}

			add_filter( 'mime_types', 'mainwp_rocket_allow_json_mime_type' );
			add_filter( 'wp_check_filetype_and_ext', 'mainwp_rocket_check_json_filetype', 10, 4 );

			$file_data = wp_check_filetype_and_ext( $_FILES['import']['tmp_name'], $_FILES['import']['name'] );

		if ( 'text/plain' !== $file_data['type'] && 'application/json' !== $file_data['type'] ) {
			mainwp_rocket_settings_import_redirect( __( 'Settings import failed: incorrect filetype.', 'rocket' ), 'error' );
		}

			$_post_action    = $_POST['action'];
			$_POST['action'] = 'wp_handle_sideload';
			$file            = wp_handle_sideload( $_FILES['import'] );

		if ( isset( $file['error'] ) ) {
			mainwp_rocket_settings_import_redirect( __( 'Settings import failed: ', 'rocket' ) . $file['error'], 'error' );
		}

			$_POST['action'] = $_post_action;
			$settings        = mainwp_rocket_direct_filesystem()->get_contents( $file['file'] );

			remove_filter( 'mime_types', 'mainwp_rocket_allow_json_mime_type' );
			remove_filter( 'wp_check_filetype_and_ext', 'mainwp_rocket_check_json_filetype', 10 );

		if ( 'text/plain' === $file_data['type'] ) {
			$gz       = 'gz' . strrev( 'etalfni' );
			$settings = $gz( $settings );
			$settings = maybe_unserialize( $settings );
		} elseif ( 'application/json' === $file_data['type'] ) {
			$settings = json_decode( $settings, true );

			if ( null === $settings ) {
				mainwp_rocket_settings_import_redirect( __( 'Settings import failed: unexpected file content.', 'rocket' ), 'error' );
			}
		}

			mainwp_rocket_put_content( $file['file'], '' );
			mainwp_rocket_direct_filesystem()->delete( $file['file'] );

		if ( is_array( $settings ) ) {
			if ( MainWP_Rocket::is_manage_sites_page() ) {
				if ( $site_id ) {
					$update = array(
						'site_id'  => $site_id,
						'settings' => base64_encode( serialize( $settings ) ),
					);
					MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
				}
			} else {
				update_option( MAINWP_ROCKET_GENERAL_SETTINGS, $settings );
			}

			$message = 2;
			wp_redirect(
				add_query_arg(
					array(
						'message'         => $message,
					),
					remove_query_arg( 's', wp_get_referer() )
				)
			);
			die();
		}
	}
}
