<?php
/**
 * iThemes Security utility function library.
 *
 * Contains the MainWP_ITSEC_Lib_Utility class.
 *
 * @package iThemes_Security
 */

if ( ! class_exists( 'MainWP_ITSEC_Lib_Utility' ) ) {
	/**
	 * iThemes Security Utility Library class.
	 *
	 * Various utility functions.
	 *
	 * @package iThemes_Security
	 * @since 1.15.0
	 */
	class MainWP_ITSEC_Lib_Utility {
		/**
		 * Determines if a function is callable.
		 *
		 * @since 1.15.0
		 *
		 * @param string $function Name of function.
		 * @return bool Boolean true if the function is callable, false otherwise.
		 */
		public static function is_callable_function( $function ) {
			if ( ! is_callable( $function ) ) {
				return false;
			}
			
			if ( ! isset( $GLOBALS['itsec_lib_cached_values'] ) ) {
				$GLOBALS['itsec_lib_cached_values'] = array();
			}
			
			if ( ! isset( $GLOBALS['itsec_lib_cached_values']['ini_get:disable_functions'] ) ) {
				$GLOBALS['itsec_lib_cached_values']['ini_get:disable_functions'] = preg_split( '/\s*,\s*/', (string) ini_get( 'disable_functions' ) );
			}
			
			if ( in_array( $function, $GLOBALS['itsec_lib_cached_values']['ini_get:disable_functions'] ) ) {
				return false;
			}
			
			if ( ! isset( $GLOBALS['itsec_lib_cached_values']['ini_get:suhosin.executor.func.blacklist'] ) ) {
				$GLOBALS['itsec_lib_cached_values']['ini_get:suhosin.executor.func.blacklist'] = preg_split( '/\s*,\s*/', (string) ini_get( 'suhosin.executor.func.blacklist' ) );
			}
			
			if ( in_array( $function, $GLOBALS['itsec_lib_cached_values']['ini_get:suhosin.executor.func.blacklist'] ) ) {
				return false;
			}
			
			return true;
		}
		
		/**
		 * Returns the type of web server.
		 *
		 * This code makes a best effort attempt of identifying the active web server. If the MainWP_ITSEC_SERVER_OVERRIDE define
		 * is defined, this value is returned.
		 *
		 * @since 1.15.0
		 *
		 * @return string Returns apache, nginx, litespeed, or iis. Defaults to apache when the server cannot be identified.
		 */
		public static function get_web_server() {
			// @codeCoverageIgnoreStart
			if ( defined( 'MainWP_ITSEC_SERVER_OVERRIDE' ) ) {
				return MainWP_ITSEC_SERVER_OVERRIDE;
			}
			// @codeCoverageIgnoreEnd
			
			
			if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
				$server_software = strtolower( $_SERVER['SERVER_SOFTWARE'] );
			} else {
				$server_software = '';
			}
			
			if ( false !== strpos( $server_software, 'apache' ) ) {
				$server = 'apache';
			} else if ( false !== strpos( $server_software, 'nginx' ) ) {
				$server = 'nginx';
			} else if ( false !== strpos( $server_software, 'litespeed' ) ) {
				$server = 'litespeed';
			} else if ( false !== strpos( $server_software, 'thttpd' ) ) {
				$server = 'thttpd';
			} else if ( false !== strpos( $server_software, 'microsoft-iis' ) ) {
				$server = 'iis';
			} else {
				$server = 'apache';
			}
			
			return apply_filters( 'itsec_filter_web_server', $server );
		}
		
		/**
		 * Updates the supplied content to use the same line endings.
		 *
		 * @since 1.15.0
		 *
		 * @param string $content     The content to update.
		 * @param string $line_ending Optional. The line ending to use. Defaults to "\n".
		 * @return string The content with normalized line endings.
		 */
		public static function normalize_line_endings( $content, $line_ending = "\n" ) {
			return preg_replace( '/(?<!\r)\n|\r(?!\n)|(?<!\r)\r\n|\r\r\n/', $line_ending, $content );
		}
		
		/**
		 * Returns the directory path to the uploads directory relative to the site root.
		 *
		 * @since 1.16.1
		 *
		 * @return string|bool The upload directory relative path or false if the path could not be determined.
		 */
		public static function get_relative_upload_url_path() {
			$upload_dir_details = wp_upload_dir();
			$upload_baseurl = parse_url( $upload_dir_details['baseurl'], PHP_URL_PATH );
			$home_url = parse_url( home_url(), PHP_URL_PATH );
			
			$upload_path = preg_replace( '/^' . preg_quote( $home_url, '/' ) . '/', '', $upload_baseurl, 1, $count );
			
			if ( 1 === $count ) {
				return trim( $upload_path, '/' );
			}
			
			return false;
		}
		
		/**
		 * Remove comments from a string containing PHP code.
		 *
		 * @since 1.15.0
		 *
		 * @param string $contents String containing the code to strip of comments.
		 * @return string|WP_Error Returns a string containing the stripped source or a WP_Error object on an error.
		 */
		public static function strip_php_comments( $contents ) {
			if ( ! self::is_callable_function( 'token_get_all' ) ) {
				return new WP_Error( 'itsec-lib-utility-strip-php-comments-token-get-all-is-disabled', __( 'Unable to strip comments from the source code as the token_get_all() function is disabled. This is a system configuration issue.', 'l10n-mainwp-ithemes-security-extension' ) );
			}
			
			
			$tokens = token_get_all( $contents );
			
			if ( ! is_array( $tokens ) ) {
				return new WP_Error( 'itsec-lib-utility-strip-php-comments-token-get-all-invalid-response', sprintf( __( 'Unable to strip comments from the source code as the token_get_all() function returned an unrecognized value (type: %s)', 'l10n-mainwp-ithemes-security-extension' ), gettype( $tokens ) ) );
			}
			
			
			if ( ! defined( 'T_ML_COMMENT' ) ) {
				define( 'T_ML_COMMENT', T_COMMENT );
			}
			if ( ! defined( 'T_DOC_COMMENT' ) ) {
				define( 'T_DOC_COMMENT', T_ML_COMMENT );
			}
			
			$contents = '';
			
			foreach ( $tokens as $token ) {
				if ( is_string( $token ) ) {
					$contents .= $token;
				} else {
					list( $id, $text ) = $token;
					
					switch ($id) {
						case T_COMMENT:
						case T_ML_COMMENT:
						case T_DOC_COMMENT:
							break;
						default:
							$contents .= $text;
							break;
					}
				}
			}
			
			return $contents;
		}
	}
}
