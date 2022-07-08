<?php

/**
 * MainWP Lighthouse API
 *
 * This file handles API interactions.
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Lighthouse;

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

/**
 * Class MainWP_Lighthouse_WP_CLI_Command
 */
class MainWP_Lighthouse_WP_CLI_Command extends \WP_CLI_Command {

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
		\WP_CLI::add_command( 'mainwp-lighthouse', self::class );
	}

	/**
	 * Run lighthouse audit
	 *
	 * ## OPTIONS
	 *
	 * [<websiteid>]
	 * : The id (or ids, comma separated) of the child sites.
	 *
	 * [--all]
	 * : If set, all child sites report will be show.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mainwp-lighthouse audit 2,5
	 *     wp mainwp-lighthouse audit --all
	 *
	 * ## Synopsis [<websiteid>] [--all]
	 * ## EXAMPLES
	 *
	 * @param array $args       Scan arguments.
	 * @param array $assoc_args Associated arguments.
	 *
	 * @return void
	 */
	public function audit( $args, $assoc_args ) {

		$sites = array();
		if ( count( $args ) > 0 ) {
			$args_exploded = explode( ',', $args[0] );
			foreach ( $args_exploded as $arg ) {
				if ( ! is_numeric( trim( $arg ) ) ) {
					\WP_CLI::error( 'Child site ids should be numeric.' );
				}

				$sites[] = trim( $arg );
			}
		}

		if ( ( count( $sites ) == 0 ) && ( ! isset( $assoc_args['all'] ) ) ) {
			\WP_CLI::error( 'Please specify one or more child sites, or use --all.' );
			return;
		}

		$all = false;
		if ( isset( $assoc_args['all'] ) ) {
			$all = true;
		}

		if ( $all ) {
			$result  = MainWP_Lighthouse_DB::get_instance()->start_schedule_recheck_all_pages();
			$message = $result['message'];
			\WP_CLI::success( $message );
			return;
		} else {
			$dbwebsites = MainWP_Lighthouse_Admin::get_db_websites( $sites );
			$websites   = array();
			if ( is_array( $dbwebsites ) ) {
				foreach ( $dbwebsites as $site ) {
					$website    = array(
						'name' => $site->name,
						'url'  => $site->url,
						'id'   => $site->id,
					);
					$websites[] = $website;
				}
			}
		}

		if ( empty( $websites ) ) {
			\WP_CLI::error( __( 'Sites not found.', 'mainwp-lighthouse-extension' ) );
			return;
		}

		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				\WP_CLI::line( '  -> ' . $website['name'] . ' (' . $website['url'] . ')' );
				$message = MainWP_Lighthouse_Admin::get_instance()->recheck_site( $website );
				if ( ! empty( $message ) ) {
					\WP_CLI::success( $message );
				} else {
					$message = __( 'An undefined error occured.', 'mainwp-lighthouse-extension' );
					\WP_CLI::warning( $message );
				}
				do_action( 'mainwp_lighthouse_recheck_finished', $website['id'], $message );
			}
		}
	}

	/**
	 *
	 * View lighthouse reports of Child Sites
	 *
	 * ## OPTIONS
	 *
	 * [<websiteid>]
	 * : The id (or ids, comma separated) of the child sites.
	 *
	 * [--all]
	 * : If set, all child sites report will be show.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mainwp-lighthouse results 2,5
	 *     wp mainwp-lighthouse results --all
	 *
	 * ## Synopsis [<websiteid>] [--all]
	 *
	 * @param array $args       Scan arguments.
	 * @param array $assoc_args Associated arguments.
	 *
	 * @return void
	 */
	public function results( $args, $assoc_args ) {

		$sites = array();
		if ( count( $args ) > 0 ) {
			$args_exploded = explode( ',', $args[0] );
			foreach ( $args_exploded as $arg ) {
				if ( ! is_numeric( trim( $arg ) ) ) {
					\WP_CLI::error( 'Child site ids should be numeric.' );
				}

				$sites[] = trim( $arg );
			}
		}

		if ( ( count( $sites ) == 0 ) && ( ! isset( $assoc_args['all'] ) ) ) {
			\WP_CLI::error( 'Please specify one or more child sites, or use --all.' );
			return;
		}

		$all = false;
		if ( isset( $assoc_args['all'] ) ) {
			$all = true;
		}

		if ( $all ) {
			$websites = MainWP_Lighthouse_Admin::get_websites();
		} else {
			$dbwebsites = MainWP_Lighthouse_Admin::get_db_websites( $sites );
			$websites   = array();
			if ( is_array( $dbwebsites ) ) {
				foreach ( $dbwebsites as $site ) {
					$website    = array(
						'name' => $site->name,
						'url'  => $site->url,
						'id'   => $site->id,
					);
					$websites[] = $website;
				}
			}
		}

		if ( empty( $websites ) ) {
			\WP_CLI::error( __( 'Sites not found.', 'mainwp-lighthouse-extension' ) );
			return;
		}
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				MainWP_Lighthouse_Admin::handle_wp_cli_sites_reports( $website );
			}
		}
	}
}
