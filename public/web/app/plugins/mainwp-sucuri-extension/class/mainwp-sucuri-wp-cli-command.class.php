<?php
/** MainWP WP-CLI commands. */

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

/**
 * Class MainWP_Sucuri_WP_CLI_Command
 */
class MainWP_Sucuri_WP_CLI_Command extends WP_CLI_Command {

	/**
	 * Check if plugins are loaded and fire off init_wpcli_commands()
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( 'MainWP_Sucuri_WP_CLI_Command', 'init_wpcli_commands' ), 99999 );
	}

	/**
	 * Initiate MainWP WP-CLI commands.
	 */
	public static function init_wpcli_commands() {
		WP_CLI::add_command( 'mainwp-sucuri', 'MainWP_Sucuri_WP_CLI_Command' );
	}

	/**
	 * Run a sucuri scan
	 *
	 * ## OPTIONS
	 *
	 * [<siteid>]
	 * : The id of the site.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mainwp-sucuri scan 2
	 *
	 * @todo: run a sucuri scan
	 * @synopsis  [<siteid>]
	 *
	 * @param array      $args Scan arguments.
	 * @param $assoc_args
	 *
	 * @uses WP_CLI::error()
	 * @uses WP_CLI::line()
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 * @uses MainWP_Sucuri_DB::get_instance::update_sucuri_by_site_id()
	 * @uses MainWP_Sucuri_DB::get_instance::save_report()
	 */
	public function scan( $args, $assoc_args ) {

		$website_id = 0;
		if ( count( $args ) > 0 ) {
			$website_id = $args[0];
		}

		if ( empty( $website_id ) ) {
			WP_CLI::error( 'Site ID should not be empty.' );
			return;
		}

		/** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$website = apply_filters( 'mainwp_getsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), $website_id );
		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			WP_CLI::error( 'Site not found.' );
		}

		$time_scan = MainWP_Sucuri::get_timestamp();

		$sucuri = array(
			'lastscan'   => $time_scan,
		);

		MainWP_Sucuri_DB::get_instance()->update_sucuri_by_site_id( $website['id'], $sucuri );
	
		WP_CLI::line( ' -> ' . $website['name'] . ' (' . $website['url'] . ')' );

		$apisslverify = get_option( 'mainwp_security_sslVerifyCertificate', true );
		$scan_url     = 'https://sitecheck.sucuri.net/scanner/?serialized&clear&mainwp&scan=' . $website['url'];
		$results      = wp_remote_get(
			$scan_url,
			array(
				'timeout'   => 180,
				'sslverify' => $apisslverify,
			)
		);
		$scan_result  = $scan_status = '';

		if ( is_wp_error( $results ) ) {
			$scan_status = 'failed';
			$scan_result = __( 'Error retrieving the scan report', 'mainwp-sucuri-extension' );
			WP_CLI::error( $scan_result );
		} elseif ( preg_match( '/^ERROR:/', $results['body'] ) ) {
			$scan_status = 'failed';
			$scan_result = $results['body'];
			WP_CLI::error( $scan_result );
		} else {
			$new = array(
				'data'     => $results['body'],
				'site_id'  => $website['id'],
				'timescan' => $time_scan,
			);

			MainWP_Sucuri_DB::get_instance()->save_report( $new );
			// $data = unserialize( $results['body'] );
			$data = json_decode( $results['body'], true );

			if ( ! is_array( $data ) ) {
				$scan_status = 'failed';
				$code        = '';
				if ( is_array( $results ) && isset( $results['response'] ) ) {
					$code = ': code ' . $results['response']['code'];
				}
				WP_CLI::error( $code );
			} else {
				$scan_result = $data;
				$scan_status = 'success';

				$blacklisted    = isset( $data['BLACKLIST']['WARN'] ) ? true : false;
				$malware_exists = isset( $data['MALWARE']['WARN'] ) ? true : false;
				$system_error   = isset( $data['SYSTEM']['ERROR'] ) ? true : false;

				$status = array();
				if ( $blacklisted ) {
					$status[] = 'Site Blacklisted'; }

				if ( $malware_exists ) {
					$status[] = 'Site With Warnings'; }

				$status_msg      = count( $status ) > 0 ? implode( ', ', $status ) : __( 'Verified Clear', 'mainwp-sucuri-extension' );
				$blacklisted_msg = $blacklisted ? __( 'Site Blacklisted', 'mainwp-sucuri-extension' ) : __( 'Trusted', 'mainwp-sucuri-extension' );
				WP_CLI::line( 'Status: ' . $status_msg );
				WP_CLI::line( 'Webtrust: ' . $blacklisted_msg );
			}
		}
		// do_action( 'mainwp_sucuri_scan_done', $website_id, $scan_status, $scan_result, $time_scan );
		do_action( 'mainwp_sucuri_scan_finished', $website_id, $scan_status, $scan_result, $time_scan );
	}

}
