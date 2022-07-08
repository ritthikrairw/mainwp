<?php
/**
 * =======================================
 * MainWP_Lighthouse_Core
 * =======================================
 *
 * @copyright Matt Keys <https://profiles.wordpress.org/mattkeys>
 */
namespace MainWP\Extensions\Lighthouse;

class MainWP_Lighthouse_Core {

	/**
	 * Static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	static $instance           = null;
	static $last_scan_finished = false;
	static $exceeded_runtime   = false;
	// static $skipped_all        = true;

	/**
	 * Constructor
	 *
	 * Runs each time the class is called.
	 */
	public function __construct() {

	}

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Lighthouse_Core
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initiate Hooks
	 *
	 * Initiates hooks for the Lighthouse extension.
	 */
	public function init() {
		add_action( 'init', array( $this, 'trigger_lighthouse' ), 9999 );
		add_action( 'mainwp_lighthouse_action_cron_start', array( $this, 'mainwp_lighthouse_cron_start' ), 10, 2 );
		add_action( 'mainwp_lighthouse_action_cron_continue', array( $this, 'mainwp_lighthouse_cron_continue' ), 10, 2 );
		add_action( 'mainwp_lighthouse_cron_alert', array( MainWP_Lighthouse_Admin::get_class_name(), 'cron_lighthouse_alert' ), 10, 2 );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_filter( 'mainwp_lighthouse_check_status', array( $this, 'hook_mainwp_lighthouse_check_status' ), 10, 1 );
		add_action( 'mainwp_lighthouse_run', array( $this, 'hook_mainwp_lighthouse_run' ), 10, 3 );

		$this->init_jobs();
	}


	/**
	 * Init cron jobs.
	 */
	public function init_jobs( $old_interval = false ) {

		$use_schedule = MainWP_Lighthouse_Utility::get_instance()->get_option( 'use_schedule' );

		$recheck_interval = 0;
		if ( $old_interval ) {
			$recheck_interval = MainWP_Lighthouse_Utility::get_instance()->get_option( 'recheck_interval' );
		}

		$local_timestamp = MainWP_Lighthouse_Utility::get_timestamp();
		$today_end   = strtotime( date("Y-m-d 23:59:59", $local_timestamp ) ) ; // phpcs:ignore -- to localtime.

		if ( $use_schedule && ! wp_next_scheduled( 'mainwp_lighthouse_action_cron_start' ) ) {
			wp_schedule_event( $today_end, 'mainwp_lighthouse_start_interval', 'mainwp_lighthouse_action_cron_start' );
		} elseif ( $use_schedule && $old_interval && $recheck_interval !== $old_interval ) {
			wp_clear_scheduled_hook( 'mainwp_lighthouse_action_cron_start' );
			wp_schedule_event( $today_end, 'mainwp_lighthouse_start_interval', 'mainwp_lighthouse_action_cron_start' );
		}

		if ( $use_schedule && ! wp_next_scheduled( 'mainwp_lighthouse_action_cron_continue' ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'mainwp_lighthouse_continue_interval', 'mainwp_lighthouse_action_cron_continue' );
		} elseif ( $use_schedule && $old_interval && $recheck_interval !== $old_interval ) {
			wp_clear_scheduled_hook( 'mainwp_lighthouse_action_cron_continue' );
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'mainwp_lighthouse_continue_interval', 'mainwp_lighthouse_action_cron_continue' );
		}

		if ( $use_schedule && ! wp_next_scheduled( 'mainwp_lighthouse_cron_alert' ) ) {
			wp_schedule_event( $today_end + 6 * HOUR_IN_SECONDS, 'mainwp_lighthouse_start_interval', 'mainwp_lighthouse_cron_alert' );
		} elseif ( $use_schedule && $old_interval && $recheck_interval !== $old_interval ) {
			wp_clear_scheduled_hook( 'mainwp_lighthouse_cron_alert' );
			wp_schedule_event( $today_end + 6 * HOUR_IN_SECONDS, 'mainwp_lighthouse_start_interval', 'mainwp_lighthouse_cron_alert' );
		}

		if ( ! $use_schedule ) {
			if ( wp_next_scheduled( 'mainwp_lighthouse_action_cron_continue' ) ) {
				wp_clear_scheduled_hook( 'mainwp_lighthouse_action_cron_start' );
				wp_clear_scheduled_hook( 'mainwp_lighthouse_action_cron_continue' );
				wp_clear_scheduled_hook( 'mainwp_lighthouse_cron_alert' );
			}
		}
	}


	/**
	 * Cron Schedules
	 *
	 * Defines cron schedules.
	 *
	 * @param array $schedules Cron schedules.
	 *
	 * @return array $schedules Cron schedules.
	 */
	public function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['mainwp_lighthouse_start_interval'] ) ) {

			$recheck_interval = self::get_recheck_intervals();

			$interval         = 86400; // run daily to start interval.
			$interval_display = isset( $recheck_interval[ $interval ] ) ? $recheck_interval[ $interval ] : __( 'Once a Day', 'mainwp-lighthouse-extension' );

			$schedules['mainwp_lighthouse_start_interval'] = array(
				'interval' => $interval,
				'display'  => $interval_display,
			);
		}

		if ( ! isset( $schedules['mainwp_lighthouse_continue_interval'] ) ) {
			$schedules['mainwp_lighthouse_continue_interval'] = array(
				'interval' => 5 * MINUTE_IN_SECONDS, // continue every 5 minutes.
				'display'  => __( 'Once Every 5 Minutes', 'mainwp-lighthouse-extension' ),
			);
		}

		return $schedules;
	}

	/**
	 * Method get_recheck_intervals().
	 *
	 * Get recheck intervals.
	 */
	public static function get_recheck_intervals() {
		return array(
			86400   => __( 'Once a Day', 'mainwp-lighthouse-extension' ),
			604800  => __( 'Once a Week', 'mainwp-lighthouse-extension' ),
			1296000 => __( 'Twice a Month', 'mainwp-lighthouse-extension' ),
			2592000 => __( 'Once Monthly', 'mainwp-lighthouse-extension' ),
		);
	}

	/**
	 * Trigger Lighthouse
	 *
	 * Triggers the Lighthouse API call.
	 */
	public function trigger_lighthouse() {

		if ( ! isset( $_GET['lighthouse_check_now'] ) ) {
			return;
		}

		if ( ! get_option( 'mainwp_lighthouse_check_now' ) ) {
			return;
		}

		delete_option( 'mainwp_lighthouse_check_now' );

		$timeout_respawn = isset( $_GET['timeout'] ) ? true : false;
		$urls_provided   = isset( $_GET['urls_provided'] ) ? true : false;
		if ( isset( $_GET['cron_worker'] ) && ! empty( $_GET['cron_worker'] ) ) {
			MainWP_Lighthouse_Utility::set_cron_working();
		}

		if ( $urls_provided ) {
			$urls_to_recheck = get_option( 'mainwp_lighthouse_recheck_urls', array() ); // re-check one.
		} else {
			$urls_to_recheck = array();
		}
		$this->mainwp_lighthouse_trigger( $urls_to_recheck, $timeout_respawn );
	}

	/**
	 * Check User Abort
	 *
	 * Checks if user aborted audit.
	 *
	 * @return bool True if yes, false if not.
	 */
	private function check_user_abort() {
		global $wpdb;

		$abort_scan = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name = 'mainwp_lighthouse_abort_scan'" );

		if ( $abort_scan ) {
			delete_option( 'mainwp_lighthouse_abort_scan' );
			return true;
		}

		return false;
	}

	/**
	 * Start Lighthouse Cron
	 *
	 * Starts the Lighthouse cron jobs.
	 */
	public function mainwp_lighthouse_cron_start() {
		MainWP_Lighthouse_Utility::set_cron_working();
		MainWP_Lighthouse_Utility::log_debug( 'START init urls check' );
		update_option( 'mainwp_lighthouse_start_cron_check', true ); // to start new cron check.
	}


	/**
	 * Method mainwp_lighthouse_trigger().
	 *
	 * Cron continue lighthouse.
	 *
	 * @param array $urls_to_recheck To support re-check url.
	 * @param bool  $timeout_respawn Timeout.
	 * @param bool  $busy Busy.
	 *
	 * @return string $location Target URL.
	 */
	public function mainwp_lighthouse_trigger( $urls_to_recheck = false, $timeout_respawn = false ) {
		$busy = false;
		// check if busy.
		$mutex_lock = $this->get_lock();
		if ( ! $mutex_lock ) {
			$busy = true;
		} else {
			MainWP_Lighthouse_Utility::log_debug( 'trigger urls check' );
			$url_groups = $this->worker_get_urls( $urls_to_recheck, $timeout_respawn );
			$empty      = empty( $url_groups ) || empty( $url_groups['urls'] );
			MainWP_Lighthouse_Utility::log_debug( 'trigger total urls to check :: ' . ( $empty ? 'empty' : count( $url_groups['urls'] ) ) );
			if ( ! $empty ) {
				$this->worker_start( $url_groups );
			}
		}
		return $busy;
	}

	/**
	 * Method mainwp_lighthouse_cron_continue().
	 *
	 * Cron continue lighthouse.
	 *
	 * @return bool $busy Busy status.
	 */
	public function mainwp_lighthouse_cron_continue() {
		MainWP_Lighthouse_Utility::set_cron_working();
		$busy = false;
		// check if busy.
		$mutex_lock = $this->get_lock();
		if ( ! $mutex_lock ) {
			$busy = true;
		} else {
			$url_groups = $this->worker_get_urls();
			$empty      = empty( $url_groups ) || empty( $url_groups['urls'] );
			MainWP_Lighthouse_Utility::log_debug( 'CONTINUE total urls to check :: ' . ( $empty ? 'empty' : count( $url_groups['urls'] ) ) );
			if ( ! $empty ) {
				$this->worker_start( $url_groups );
			}
		}
		return $busy;
	}

	/**
	 * Get URLs
	 *
	 * Gets the URLs to audit.
	 *
	 * @param array $urls_to_recheck URLs to check.
	 * @param bool  $timeout_respawn Timeout respawn.
	 *
	 * @return array $url_groups URLs to check.
	 */
	public function worker_get_urls( $urls_to_recheck = false, $timeout_respawn = false ) {
		if ( empty( MainWP_Lighthouse_Utility::get_instance()->get_option( 'google_developer_key' ) ) ) {
			return false;
		}
		// Get our URLs and go to work!
		$url_groups   = array();
		$cron_working = MainWP_Lighthouse_Utility::is_cron_working();

		// for cron check.
		if ( $cron_working ) {
			$new_start = get_option( 'mainwp_lighthouse_start_cron_check', false );
			if ( $new_start ) {
				$excl_blacklist = true;
				$url_groups     = $this->get_urls_to_check( $excl_blacklist );
				update_option( 'mainwp_lighthouse_start_cron_check', false );
				update_option( 'mainwp_lighthouse_start_cron_lastcheck', time() );
				MainWP_Lighthouse_Utility::log_debug( 'start get urls to check' );
			} else {
				// if continue cron checking or/and timeout_respawn.
				$missed_url_groups = get_option( 'mainwp_lighthouse_cron_missed_url_groups' ); // get missed urls.
				MainWP_Lighthouse_Utility::log_debug( 'get missed urls to check :: ' . ( empty( $missed_url_groups ) || empty( $missed_url_groups['urls'] ) ? 'empty' : count( $missed_url_groups['urls'] ) ) );
				if ( ! empty( $missed_url_groups ) && ! empty( $missed_url_groups['urls'] ) ) {
					$url_groups = $missed_url_groups;
				}
				update_option( 'mainwp_lighthouse_continue_cron_lastcheck', time() );
			}
			return $url_groups;
		}

		if ( $timeout_respawn && $missed_url_groups = get_option( 'mainwp_lighthouse_missed_url_groups' ) ) {
			MainWP_Lighthouse_Utility::log_debug( 'check missed urls timeout' );
			$url_groups = $missed_url_groups;
		} elseif ( empty( $urls_to_recheck ) ) {
			MainWP_Lighthouse_Utility::log_debug( 'start get urls to check' );
			$excl_blacklist = $cron_working ? true : false;
			$url_groups     = $this->get_urls_to_check( $excl_blacklist );
		} elseif ( ! empty( $urls_to_recheck ) ) { // re-check url.
			MainWP_Lighthouse_Utility::log_debug( 'recheck urls' );
			$url_groups = $urls_to_recheck;
		}
		return $url_groups;
	}


	public function worker_start( $url_groups ) {

		// Add a shutdown function to check if the last scan finished successfully, and relaunch the scan if it did not.
		register_shutdown_function( array( self::class, 'shutdown_checker' ) );
		$max_runtime = MainWP_Lighthouse_Utility::get_instance()->get_option( 'max_run_time' );

		if ( $max_runtime ) {
			$start_runtime = time();
		}

		$recheck_interval = MainWP_Lighthouse_Utility::get_instance()->get_option( 'recheck_interval' );

		$run_settings = array(
			'google_developer_key' => MainWP_Lighthouse_Pagespeed_API::get_instance()->get_developer_key(),
			'recheck_interval'     => $recheck_interval,
			'response_language'    => MainWP_Lighthouse_Utility::get_instance()->get_option( 'response_language', 'en_US' ),
		);

		// Don't stop the script when the connection is closed.
		ignore_user_abort( true );

		MainWP_Lighthouse_Utility::log_debug( 'WORKER CHECK urls' );

		// Set last run finished to false, we will change this to true if this process finishes before max execution time.
		MainWP_Lighthouse_Utility::get_instance()->set_option( 'last_run_finished', false );

		// Clear Pagespeed Disabled and API Restriction warnings.
		MainWP_Lighthouse_Utility::get_instance()->set_option( 'pagespeed_disabled', false );
		MainWP_Lighthouse_Utility::get_instance()->set_option( 'api_restriction', false );

		$current_page     = isset( $url_groups['completed_pages'] ) ? $url_groups['completed_pages'] : 0;
		$url_groups_clone = $url_groups;

		$user_abort = false;

		// general strategy.
		$strategy = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );

		foreach ( $url_groups as $group_type => $group ) {
			if ( 'total_url_count' == $group_type || 'completed_pages' == $group_type ) {
				continue;
			}
			// $group_type : 'urls' - only.

			foreach ( $group as $item_key => $item ) {
				$item_id = isset( $item['id'] ) ? $item['id'] : 0;
				$site_id = isset( $item['site_id'] ) ? $item['site_id'] : 0;

				// to check individual settings.
				if ( $item_id ) {
					$existing_lihouse = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'id', $item_id );
				} elseif ( $site_id ) {
					$existing_lihouse = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $site_id );
				}

				if ( $existing_lihouse ) {
					if ( isset( $existing_lihouse->override ) && $existing_lihouse->override ) {
						// use individual settings.
						$strategy                          = $existing_lihouse->strategy;
						$run_settings['strategy']          = $strategy;
						$run_settings['response_language'] = ! empty( $existing_lihouse->settings['response_language'] ) ? $existing_lihouse->settings['response_language'] : 'en_US';
						if ( ! empty( $existing_lihouse->settings['google_developer_key'] ) ) {
							$run_settings['google_developer_key'] = $existing_lihouse->settings['google_developer_key'];
						}
					}
				}
				// end.

				update_option( 'mainwp_ext_lig_progress', $current_page . ' / ' . $url_groups['total_url_count'] );
				$current_page++;

				if ( 'both' == $strategy ) {
					foreach ( array( 'desktop', 'mobile' ) as $new_strategy ) {
						$run_settings['strategy'] = $new_strategy;
						$result                   = $this->get_lighthouse_result( $new_strategy, $item, $run_settings );

						if ( ! $result ) { // stop continue checking if error.
							break 3;
						}

						if ( $max_runtime && time() - $start_runtime > $max_runtime ) {
							self::$exceeded_runtime = true;
							break 3;
						}

						if ( $this->check_user_abort() ) {
							$user_abort = true;
							break 3;
						}
					}
				} else {
					$run_settings['strategy'] = $strategy;
					$result                   = $this->get_lighthouse_result( $strategy, $item, $run_settings );

					if ( ! $result ) { // stop continue checking if error.
						break 2;
					}

					if ( $max_runtime && time() - $start_runtime > $max_runtime ) {
						self::$exceeded_runtime = true;
						break 2;
					}

					if ( $this->check_user_abort() ) {
						$user_abort = true;
						break 2;
					}
				}

				$url_groups_clone['completed_pages'] = $current_page;
				unset( $url_groups_clone[ $group_type ][ $item_key ] );
			}
			unset( $url_groups_clone[ $group_type ] );
		}

		$cron_working = MainWP_Lighthouse_Utility::is_cron_working();

		if ( ! empty( $url_groups_clone ) && ! $user_abort ) {
			if ( $cron_working ) {
				update_option( 'mainwp_lighthouse_cron_missed_url_groups', $url_groups_clone ); // saved urls to check, when timeout.
			} else {
				update_option( 'mainwp_lighthouse_missed_url_groups', $url_groups_clone ); // saved urls to check, when timeout.
			}
		} else {
			if ( $cron_working ) {
				delete_option( 'mainwp_lighthouse_cron_missed_url_groups' );
			} else {
				delete_option( 'mainwp_lighthouse_missed_url_groups' );
			}
		}

		// update the 'last_run_finished' value in the options so we know for next time
		MainWP_Lighthouse_Utility::get_instance()->set_option( 'last_run_finished', true );
		self::$last_scan_finished = ( self::$exceeded_runtime ) ? false : true;

		// Clear out our status option or show abort message
		if ( ! $user_abort ) {
			delete_option( 'mainwp_ext_lig_progress' );
		} else {
			update_option( 'mainwp_ext_lig_progress', 'abort' );
		}

		// Release our lock on the DB
		$this->release_lock();

		// If this is the first time we have run through the whole way, update the DB
		MainWP_Lighthouse_Utility::get_instance()->set_option( 'first_run_complete', true );
	}


	public function hook_mainwp_lighthouse_run( $timeout = false, $urls_provided = false, $cron_working = false ) {
		add_option( 'mainwp_lighthouse_check_now', true );

		$query_args = array(
			'lighthouse_check_now' => true,
			'cb'                   => time(),
		);

		if ( $timeout ) {
			$query_args['timeout'] = true;
		}
		if ( $urls_provided ) {
			$query_args['urls_provided'] = true;
		}

		if ( $cron_working ) {
			$query_args['cron_worker'] = true;
		}

		$cron_url = add_query_arg( $query_args, home_url() );

		wp_remote_post(
			$cron_url,
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => apply_filters(
					'https_local_ssl_verify',
					true
				),
			)
		);
	}

	public function get_urls_to_check( $excl_blacklist = false ) {
		$total_count   = 0;
		$urls_to_check = array();

		$existing_urls_array = MainWP_Lighthouse_DB::get_instance()->get_existing_urls( $excl_blacklist );
		$existing_site_ids   = array();
		$existing_urls       = array();

		$x = 0;
		if ( ! empty( $existing_urls_array ) ) {
			foreach ( $existing_urls_array as $item_url ) {
				if ( empty( $item_url['URL'] ) ) {
					continue;
				}
				$site_id                                = isset( $item_url['site_id'] ) ? intval( $item_url['site_id'] ) : 0;
				$urls_to_check['urls'][ $x ]['url']     = $item_url['URL'];
				$urls_to_check['urls'][ $x ]['id']      = $item_url['id'];
				$urls_to_check['urls'][ $x ]['site_id'] = $site_id;
				if ( ! empty( $site_id ) ) {
					$existing_site_ids[ $site_id ] = $item_url['id'];
				}
				$existing_urls[ $item_url['URL'] ] = $item_url['id'];
				$total_count++;
				$x++;
			}
		}

		$websites = MainWP_Lighthouse_Admin::get_websites();

		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$site_id = $website['id'];
				if ( empty( $existing_site_ids[ $site_id ] ) && isset( $existing_urls[ $website['url'] ] ) ) {
					$update = array(
						'site_id' => $site_id,
						'id'      => $existing_urls[ $website['url'] ],
					);
					MainWP_Lighthouse_DB::get_instance()->update_lighthouse( $update ); // update site_id value.
				}
				if ( ! isset( $existing_urls[ $website['url'] ] ) ) {
					$urls_to_check['urls'][ $x ]['url']     = $website['url'];
					$urls_to_check['urls'][ $x ]['site_id'] = $site_id;
					$existing_site_ids[ $site_id ]          = 0; // to exclude.
					$existing_urls[ $website['url'] ]       = 0; // to exclude.
					$total_count++;
					$x++;
				}
			}
		}

		$urls_to_check['total_url_count'] = $total_count;
		return $urls_to_check;
	}


	public function get_lock() {
		global $wpdb;

		$lock = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', 'gpi_lock_' . MainWP_Lighthouse_Utility::get_instance()->get_option( 'mutex_id' ), 0 ) );

		return $lock == 1;
	}

	public function release_lock() {
		global $wpdb;

		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'gpi_lock_' . MainWP_Lighthouse_Utility::get_instance()->get_option( 'mutex_id' ) ) );
	}

	public function hook_mainwp_lighthouse_check_status( $busy = false ) {
		$mutex_lock = $this->get_lock();
		$this->release_lock();
		MainWP_Lighthouse_Utility::log_debug( 'check proccess status :: ' . ( ! $mutex_lock ? 'busy' : 'not busy' ) );
		if ( ! $mutex_lock ) {
			$busy = true;
		}
		return $busy;
	}

	static function shutdown_checker() {
		$cron_working = MainWP_Lighthouse_Utility::is_cron_working();
		$cron_log     = $cron_working ? ' :: cron worker' : '';
		// If scan took longer than Maximum Script Run Time or Maximum Execution Time, start new scan.
		if ( ! self::$last_scan_finished && self::$exceeded_runtime ) {
			MainWP_Lighthouse_Utility::log_debug( 'shutdown checker :: timeout' . $cron_log );
			do_action( 'mainwp_lighthouse_run', true, false, $cron_working ); // timeout.
		} elseif ( ! self::$last_scan_finished ) {
			MainWP_Lighthouse_Utility::log_debug( 'shutdown checker :: urls provided' . $cron_log );
			do_action( 'mainwp_lighthouse_run', true, false, $cron_working ); // If scan failed due to Maximum Execution Time, avoid trying again with force_recheck as it could cause infinite loop.
		}
	}

	private function get_lighthouse_result( $strategy, $item, $run_settings, $continue = true ) {
		global $wpdb;

		// Use max_execution_time set in settings.
		@set_time_limit( MainWP_Lighthouse_Utility::get_instance()->get_option( 'max_execution_time' ) );

		$object_url = $item['url'];

		$item_id = isset( $item['id'] ) ? $item['id'] : 0;
		$site_id = isset( $item['site_id'] ) ? $item['site_id'] : 0;

		$recheck_interval = $run_settings['recheck_interval'];

		if ( $item_id ) {
			$existing_url_info = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_info( 'id', $item_id, $strategy );
		} elseif ( $site_id ) {
			$existing_url_info = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_info( 'site_id', $site_id, $strategy );
			if ( $existing_url_info ) {
				$item_id = $existing_url_info->id;
			}
		}

		if ( $existing_url_info && $existing_url_info->override ) {
			if ( empty( $existing_url_info->use_schedule ) ) {
				return $continue;
			}
			$settings         = json_decode( $existing_url_info->settings, true );
			$settings         = is_array( $settings ) ? $settings : array();
			$recheck_interval = isset( $settings['recheck_interval'] ) ? $settings['recheck_interval'] : $recheck_interval;
		}

		$cron_working = MainWP_Lighthouse_Utility::is_cron_working();
		$property     = $strategy . '_last_modified';
		$update       = $existing_url_info && ( isset( $existing_url_info->$property ) ) ? true : false;
		$time         = current_time( 'timestamp' );

		if ( $cron_working && $update && ! $existing_url_info->force_recheck ) {
			$last_modified = $existing_url_info->$property;

			if ( ! empty( $last_modified ) && $time - $last_modified < $recheck_interval ) {
				return $continue;
			}
		}

		MainWP_Lighthouse_Utility::log_debug( 'running check :: ' . $strategy . ' :: ' . $object_url );
		// self::$skipped_all = false;

		$result = MainWP_Lighthouse_Pagespeed_API::get_instance()->run_lighthouse(
			$object_url,
			$run_settings,
			array(
				'category' => array( 'PERFORMANCE', 'ACCESSIBILITY', 'BEST_PRACTICES', 'SEO' ),
			)
		);
		if ( ! empty( $result ) ) {
			if ( isset( $result['responseCode'] ) && $result['responseCode'] >= 200 && $result['responseCode'] < 300 ) {
				MainWP_Lighthouse_Utility::log_debug( 'check url :: success :: ' . $strategy . ' :: ' . $object_url );
				$result['last_modified'] = $time;
				$result['site_id']       = $site_id;
				$this->save_values( $result, $item_id, $object_url, $update, $strategy );
			} else {
				MainWP_Lighthouse_Utility::log_debug( 'check url :: failed :: ' . $strategy . ' :: ' . $object_url );
				$exception_type = $this->exception_handler( $result, $strategy, $update, $object_url, $item_id );
				if ( 'fatal' == $exception_type ) {
					$continue = false;
				}
			}
		}

		// Some web servers seem to have a difficult time responding to the constant requests from the Google API, sleeping inbetween each URL helps
		sleep( MainWP_Lighthouse_Utility::get_instance()->get_option( 'sleep_time' ) );

		return $continue;
	}

	/**
	 * Save Values
	 *
	 * Saves the Lighthosue report values.
	 *
	 * @param array  $result     Lighthouse audit report.
	 * @param int    $id         Site ID.
	 * @param string $object_url Site URL.
	 * @param mixed  $update     Update.
	 * @param string $strategy   Audit strategy.
	 */
	public function save_values( $result, $id, $object_url, $update, $strategy ) {
		// compatible name.
		global $wpdb;
		$gpi_page_stats_values = array();
		$score                 = $result['data']->lighthouseResult->categories->performance->score;

		$accessibility  = $result['data']->lighthouseResult->categories->accessibility->score;
		$best_practices = $result['data']->lighthouseResult->categories->{'best-practices'}->score;
		$seo            = $result['data']->lighthouseResult->categories->seo->score;

		// Store identifying information.
		$gpi_page_stats_values['URL']                                = $object_url;
		$gpi_page_stats_values[ $strategy . '_last_modified' ]       = $result['last_modified'];
		$gpi_page_stats_values['response_code']                      = $result['responseCode'];
		$gpi_page_stats_values[ $strategy . '_lab_data' ]            = MainWP_Lighthouse_Pagespeed_API::get_instance()->get_lab_data( $result['data'] );
		$gpi_page_stats_values[ $strategy . '_score' ]               = $score * 100;
		$gpi_page_stats_values[ "{$strategy}_accessibility_score" ]  = $accessibility * 100;
		$gpi_page_stats_values[ "{$strategy}_best_practices_score" ] = $best_practices * 100;
		$gpi_page_stats_values[ "{$strategy}_seo_score" ]            = $seo * 100;
		$gpi_page_stats_values[ $strategy . '_others_data' ]         = MainWP_Lighthouse_Pagespeed_API::get_instance()->get_others_data( $result['data'] );
		$gpi_page_stats_values['site_id']                            = $result['site_id'];
		$gpi_page_stats_values['blacklist']                          = 0;
		$gpi_page_stats_values['force_recheck']                      = 0;

		if ( $id ) {
			$gpi_page_stats_values['id'] = $id;
		}

		MainWP_Lighthouse_DB::get_instance()->update_lighthouse( $gpi_page_stats_values );
	}

	/**
	 * Exception Handler
	 *
	 * Handles exceptions.
	 *
	 * @param array  $result     Lighthouse audit report.
	 * @param string $strategy   Audit strategy.
	 * @param mixed  $update     Update.
	 * @param string $object_url Site URL.
	 * @param int    $item_id    Site ID.
	 * @param string $error_type Error type.
	 *
	 * @return string Error type.
	 */
	public function exception_handler( $result, $strategy, $update, $object_url, $item_id, $error_type = 'non_fatal' ) {
		$errors = isset( $result['data']->error->errors ) ? $result['data']->error->errors : false;

		if ( isset( $errors[0]->reason ) && $errors[0]->reason == 'keyInvalid' ) {
			MainWP_Lighthouse_Utility::get_instance()->set_option( 'bad_api_key', true );
			$error_type = 'fatal';
		} elseif ( isset( $errors[0]->reason ) && $errors[0]->reason == 'accessNotConfigured' ) {
			MainWP_Lighthouse_Utility::get_instance()->set_option( 'pagespeed_disabled', true );
			$error_type = 'fatal';
		} elseif ( isset( $errors[0]->reason ) && $errors[0]->reason == 'ipRefererBlocked' ) {
			MainWP_Lighthouse_Utility::get_instance()->set_option( 'api_restriction', true );
			$error_type = 'fatal';
		} elseif ( isset( $errors[0]->reason ) && $errors[0]->reason == 'backendError' ) {
			MainWP_Lighthouse_DB::get_instance()->save_bad_request( $item_id, false );
			MainWP_Lighthouse_Utility::get_instance()->set_option( 'backend_error', true );
		} elseif ( isset( $errors[0]->reason ) && $errors[0]->reason == 'mainResourceRequestFailed' ) {
			MainWP_Lighthouse_DB::get_instance()->save_bad_request( $item_id );
		} elseif ( $result['responseCode'] == '500' ) {
			MainWP_Lighthouse_DB::get_instance()->save_bad_request( $item_id );
		}
		return $error_type;
	}

}
