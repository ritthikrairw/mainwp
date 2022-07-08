<?php

/**
 * MainWP Domain Monitor CLI
 *
 * This file handles CLI interactions.
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Domain_Monitor;

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

/**
 * Class MainWP_Domain_Monitor_WP_CLI_Command
 */
class MainWP_Domain_Monitor_WP_CLI_Command extends \WP_CLI_Command {

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
		\WP_CLI::add_command( 'mainwp-domain-monitor', self::class );
	}

	/**
	 * Run Domain Monitor check
	 *
	 * ## OPTIONS
	 *
	 * [<websiteid>]
	 * : The id of the child site to check.
	 *
	 * [--all]
	 * : If set, all child sites report will be show.
	 *
	 * ## EXAMPLES
	 *
	 *     wp mainwp-domain-monitor check 2
	 *     wp mainwp-domain-monitor check --all
	 *
	 * ## Synopsis [<websiteid>] [--all]
	 * ## EXAMPLES
	 *
	 * @param array $args       Scan arguments.
	 * @param array $assoc_args Associated arguments.
	 *
	 * @return void
	 */
	public function check( $args, $assoc_args ) {

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
			$websites = MainWP_Domain_Monitor_Admin::get_websites();
			if ( empty( $websites ) ) {
				\WP_CLI::error( __( 'Sites not found.', 'mainwp-domain-monitor-extension' ) );
				return;
			}
			$update = false;
			$id     = '';
			foreach ( $websites as $website ) {
				$site_id     = $website['id'];
				$site_url    = $website['url'];
				$domain      = MainWP_Domain_Monitor_Utility::get_domain( MainWP_Domain_Monitor_Utility::get_nice_url( $site_url ) );
				$domain_site = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $site_id );
				$id          = isset( $domain_site->id ) ? $domain_site->id : 0;
				if ( 0 < $id ) {
					$update = true;
				}
				$message = MainWP_Domain_Monitor_Core::lookup_domain( $domain, $id, $site_id, $site_url, $update );
				if ( ! empty( $message ) ) {
					\WP_CLI::line( $message );
				} else {
					$message = __( 'An undefined error occured.', 'mainwp-domain-monitor-extension' );
					\WP_CLI::error( $message );
				}
			}
			return;
		} else {
			$dbwebsites = MainWP_Domain_Monitor_Admin::get_db_websites( $sites );
			if ( empty( $dbwebsites ) ) {
				\WP_CLI::error( __( 'Sites not found.', 'mainwp-domain-monitor-extension' ) );
				return;
			}
			$update = false;
			$id     = '';
			foreach ( $dbwebsites as $website ) {
				$site_id     = $website->id;
				$site_url    = $website->url;
				$domain      = MainWP_Domain_Monitor_Utility::get_domain( MainWP_Domain_Monitor_Utility::get_nice_url( $site_url ) );
				$domain_site = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $site_id );
				$id          = isset( $domain_site->id ) ? $domain_site->id : 0;
				if ( 0 < $id ) {
					$update = true;
				}
				$message = MainWP_Domain_Monitor_Core::lookup_domain( $domain, $id, $site_id, $site_url, $update );
				if ( ! empty( $message ) ) {
					\WP_CLI::line( $message );
				} else {
					$message = __( 'An undefined error occured.', 'mainwp-domain-monitor-extension' );
					\WP_CLI::error( $message );
				}
			}
			return;
		}
	}

	/**
	 *
	 * View Domain Monitor data for Child Sites
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
	 *     wp mainwp-domain-monitor domain-profile 2,5
	 *     wp mainwp-domain-monitor domain-profile --all
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
			$websites = MainWP_Domain_Monitor_Admin::get_websites();
		} else {
			$dbwebsites = MainWP_Domain_Monitor_Admin::get_db_websites( $sites );
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
			\WP_CLI::error( __( 'Sites not found.', 'mainwp-domain-monitor-extension' ) );
			return;
		}
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				MainWP_Domain_Monitor_Admin::handle_wp_cli_sites_domain_profiles( $website );
			}
		}
	}
}
