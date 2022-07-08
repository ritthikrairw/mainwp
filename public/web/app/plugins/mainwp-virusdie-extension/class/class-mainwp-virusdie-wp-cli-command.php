<?php
/**
 * MainWP Virusdie API
 *
 * This file handles API interactions.
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Virusdie;

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

/**
 * Class MainWP_Virusdie_WP_CLI_Command
 */
class MainWP_Virusdie_WP_CLI_Command extends \WP_CLI_Command {

	/**
	 * Checks if plugins are loaded and fire off init_wpcli_commands()
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( self::class, 'init_wpcli_commands' ), 99999 );
	}

	/**
	 * Initiates MainWP WP-CLI commands.
	 */
	public static function init_wpcli_commands() {
		\WP_CLI::add_command( 'mainwp-virusdie', self::class );
	}

	/**
	 * Run a virusdie scan
	 *
	 * ## OPTIONS
	 *
	 * [<siteid>] : The id of the site.
	 *
	 * ## EXAMPLES
	 *
	 * wp mainwp-virusdie scan <siteid>
	 *
	 * @param array $args       Scan arguments.
	 * @param array $assoc_args Associated arguments.
	 *
	 * @return void
	 */
	public function scan( $args, $assoc_args ) {

		$website_id = 0;

		if ( count( $args ) > 0 ) {
			$website_id = $args[0];
		}

		if ( empty( $website_id ) ) {
			\WP_CLI::error( __( 'Site ID should not be empty.', 'mainwp-virusdie-extension' ) );
			return;
		}

		/**
		 * Extension object
		 *
		 * @global object
		 */
		global $mainWPVirusdieExtensionActivator;

		$website = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), $website_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			\WP_CLI::error( __( 'Site not found.', 'mainwp-virusdie-extension' ) );
		}

		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'site_id', $website_id );

		if ( empty( $virusdie ) ) {
			\WP_CLI::error( __( 'Site not found in the Virusdie account.', 'mainwp-virusdie-extension' ) );
		}

		\WP_CLI::line( ' -> ' . $website['name'] . ' (' . $website['url'] . ')' );

		$results = MainWP_Virusdie_API::instance()->scan_site( $virusdie->domain );

		$scan_result = '';
		$scan_status = '';

		if ( is_array( $results ) && empty( $results['error'] ) ) {
			$scan_status = 'success';
			$scan_result = __( 'Scan started.', 'mainwp-virusdie-extension' );
		} elseif ( ! empty( $results['error'] ) ) {
			$scan_status = 'failed';
			$scan_result = $results['error'];
		}

		if ( 'failed' == $scan_status ) {
			$scan_result = __( 'Error retrieving the scan report', 'mainwp-virusdie-extension' ) . ' ' . $scan_result;
			\WP_CLI::error( $scan_result );
		} elseif ( 'success' == $scan_status ) {
			\WP_CLI::line( $scan_result );
		}
		do_action( 'mainwp_virusdie_scan_finished', $website_id, $scan_status, $scan_result );
	}

	/**
	 * View virusdie last scan
	 *
	 * ## OPTIONS
	 *
	 * [<siteid>] : The id of the site.
	 *
	 * ## EXAMPLES
	 *
	 * wp mainwp-virusdie lastscan <siteid>
	 *
	 * @param array $args       Scan arguments.
	 * @param array $assoc_args Associated arguments.
	 *
	 * @return void
	 */
	public function lastscan( $args, $assoc_args ) {

		$website_id = 0;

		if ( count( $args ) > 0 ) {
			$website_id = $args[0];
		}

		if ( empty( $website_id ) ) {
			\WP_CLI::error( __( 'Site ID should not be empty.', 'mainwp-virusdie-extension' ) );
			return;
		}

		/**
		 * Extension object
		 *
		 * @global object
		 */
		global $mainWPVirusdieExtensionActivator;

		$website = apply_filters( 'mainwp_getsites', $mainWPVirusdieExtensionActivator->get_child_file(), $mainWPVirusdieExtensionActivator->get_child_key(), $website_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			\WP_CLI::error( __( 'Site not found.', 'mainwp-virusdie-extension' ) );
			return;
		}

		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'site_id', $website_id );

		if ( empty( $virusdie ) ) {
			\WP_CLI::error( __( 'Site not found in the Virusdie account.', 'mainwp-virusdie-extension' ) );
			return;
		}

		\WP_CLI::line( ' -> ' . $website['name'] . ' (' . $website['url'] . ')' );

		$last_report = MainWP_Virusdie_DB::get_instance()->get_lastscan( $virusdie->virusdie_item_id );

		if ( ! empty( $last_report ) ) {
			\WP_CLI::line( __( 'Infected files: ', 'mainwp-virusdie-extension' ) . $last_report->detectedfiles );
			\WP_CLI::line( __( 'Incurable files: ', 'mainwp-virusdie-extension' ) . $last_report->incurablefiles );
			\WP_CLI::line( __( 'Vulnerabilities: ', 'mainwp-virusdie-extension' ) . $last_report->malicious );
			\WP_CLI::line( __( 'Checked dirs: ', 'mainwp-virusdie-extension' ) . $last_report->checkeddirs );
			\WP_CLI::line( __( 'Checked files: ', 'mainwp-virusdie-extension' ) . $last_report->checkedfiles );
			\WP_CLI::line( __( 'Suspicious files: ', 'mainwp-virusdie-extension' ) . $last_report->suspicious );
			\WP_CLI::line( __( 'Treated files: ', 'mainwp-virusdie-extension' ) . $last_report->treatedfiles );
			\WP_CLI::line( __( 'Deleted files: ', 'mainwp-virusdie-extension' ) . $last_report->deletedfiles );
		} else {
			\WP_CLI::line( __( 'Scan report not found.', 'mainwp-virusdie-extension' ) );
		}
	}
}
