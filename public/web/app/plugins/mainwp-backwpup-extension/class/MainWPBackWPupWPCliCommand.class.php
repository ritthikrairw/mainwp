<?php

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage all child sites added to the MainWP Dashboard
 */
class MainWPBackWPupWPCliCommand extends WP_CLI_Command {

	public static function init() {
		add_action( 'plugins_loaded', array( 'MainWPBackWPupWPCliCommand', 'init_wpcli_commands' ), 99999 );
	}

	public static function init_wpcli_commands() {
		WP_CLI::add_command( 'mainwp-backwpup', 'MainWPBackWPupWPCliCommand' );
	}

	/**
	 * Run a backup job
	 *
	 * ## OPTIONS
	 *
	 * [<jobid>]
	 * : The id of the job.
	 *
	 * [--site-id]
	 *  : Site ID - required parameter.
	 *
	 * @todo: run a backup job
	 * @synopsis  [<jobid>] [--site-id=<siteid>]
	 */
	public function backup( $args, $assoc_args ) {
		$job_id     = 0;
		$website_id = 0;

		if ( count( $args ) > 0 ) {
			$job_id = $args[0];
		}

		if ( empty( $job_id ) ) {
			WP_CLI::error( 'Backup Job ID should not be empty.' );
			return;
		}

		function cli_backup( $jobid, $website_id, &$bulk = false ) {

			global $mainWPBackWPupExtensionActivator;
			$website = apply_filters( 'mainwp_getsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id );

			if ( $website && is_array( $website ) ) {
				$website = current( $website );
			}

			if ( empty( $website ) ) {
				WP_CLI::error( 'Not found the site. ' . $website_id );
				return;
			}

			WP_CLI::line( ' -> ' . $website['name'] . ' (' . $website['url'] . ')' );

			$post_data                       = array( 'action' => 'backwpup_backup_now' );
			$post_data['settings']['job_id'] = $jobid;

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

			$error = MainWPBackWPupExtension::get_instance()->check_child_response( $information, 'Cannot update settings in child website', false );

			if ( ! empty( $error ) ) {
				WP_CLI::error( $error );
				return;
			}

			WP_CLI::success( 'Requesting a backup' );

			if ( isset( $information['response'] ) ) {
				if ( is_array( $information['response'] ) ) {
					foreach ( $information['response'] as $msg ) {
						WP_CLI::line( $msg );
					}
				} else {
					WP_CLI::line( $information['response'] );
				}
			}
		}

		if ( isset( $assoc_args['site-id'] ) ) {
			$website_id = $assoc_args['site-id'];
			if ( empty( $website_id ) ) {
				WP_CLI::error( 'Site ID should not be empty, use --site-id.' );
				return;
			}
			return cli_backup( $job_id, $website_id );
		} else {
			$job = MainWPBackWPupDB::Instance()->get_job_by_id( $job_id );

			if ( ! isset( $job['id'] ) ) {
				 WP_CLI::error( 'Job does not exist' );
				 return;
			}

			$global_jobs = MainWPBackWPupDB::Instance()->get_global_job_by_global_id( $job_id );

			$jobs_ids_array = array();
			foreach ( $global_jobs as $global_job ) {
				$jobs_ids_array[ $global_job['website_id'] ] = $global_job['job_id'];
			}

			$return = MainWPBackWPupExtension::get_instance()->get_list_of_overrides();

			$jobs = array();
			foreach ( $return['ids'] as $temp_website_id ) {
				if ( ! isset( $jobs_ids_array[ $temp_website_id ] ) ) {
					WP_CLI::error( 'Cannot retrive global job id for website_id ' . intval( $temp_website_id ) . '. Probably you disable `Override General Settings` and don\' synchronize datas. Please save global job and try again.' );
				}
				$jobs[] = array(
					'jobid'  => $jobs_ids_array[ $temp_website_id ],
					'siteid' => $temp_website_id,
				);
			}

			$total_process = count( $jobs );
			if ( $total_process == 0 ) {
				WP_CLI::warning( 'No websites to backup.' );
			} else {
				$j = 0;
				while ( $j < $total_process ) {
					$val = $jobs[ $j ];
					cli_backup( $val['jobid'], $val['siteid'] );
					$j++;
				}
			}
		}

	}

}
