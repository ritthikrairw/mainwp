<?php
/*
 * Plugin Name: MainWP Maintenance Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP Maintenance Extension is MainWP Dashboard extension that clears unwanted entries from your child sites. You can delete post revisions, delete auto draft pots, delete trash posts, delete spam, pending and trash comments, delete unused tags and categories and optimize database tables on selected child sites.
 * Version: 4.1.1
 * Author: MainWP
 * Author URI: https://mainwp.com
 * Documentation URI: https://kb.mainwp.com/docs/category/mainwp-extensions/maintenance/
 */

if ( ! defined( 'MAINWP_MAINTENANCE_PLUGIN_FILE' ) ) {
	define( 'MAINWP_MAINTENANCE_PLUGIN_FILE', __FILE__ );
}

class Maintenance_Extension {
	public static $instance = null;
	public $plugin_handle   = 'maintenance-extension';
	protected $plugin_url;
	private $plugin_slug;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new Maintenance_Extension();
		}
		return self::$instance;
	}

	public function __construct() {

		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'wp_ajax_maintenance_run_site', array( $this, 'maintenance_run_site' ) );
		add_action( 'wp_ajax_maintenance_selected_sites', array( $this, 'maintenance_selected_sites' ) );
		add_action( 'wp_ajax_maintenance_removetask', array( &$this, 'maintenance_removetask' ) );
		add_action( 'wp_ajax_maintenance_addtask', array( &$this, 'maintenance_addtask' ) );
		add_action( 'wp_ajax_maintenance_updatetask', array( &$this, 'maintenance_updatetask' ) );
		add_action( 'wp_ajax_maintenace_reload_select_sites', array( &$this, 'maintenace_reload_select_sites_action' ) );
		add_action( 'wp_ajax_maintenance_maintenancetask_get_sites_to_run', array( &$this, 'maintenance_maintenancetask_get_sites_to_run' ) );
		add_action( 'wp_ajax_maintenance_save_settings', array( &$this, 'maintenance_save_settings' ) );
		add_action( 'wp_ajax_maintenance_save_settings_load_sites', array( &$this, 'maintenance_save_settings_load_sites' ) );
		add_action( 'wp_ajax_mainwp_maintenance_performsavesettings', array( &$this, 'mainwp_maintenance_performsavesettings' ) );
		add_action( 'mainwp_cron_jobs_list', array( $this, 'cron_job_info' ) );

		Maintenance_Extension_DB::get_instance()->install();

		$useWPCron = ( false === get_option( 'mainwp_wp_cron' ) ) || ( 1 == get_option( 'mainwp_wp_cron' ) );

		add_action( 'mainwp_maintenance_cron_scheduled_start', array( 'Maintenance_Extension', 'cron_get_schuduled_to_start' ) );
		add_action( 'mainwp_maintenance_cron_scheduled_continue', array( 'Maintenance_Extension', 'cron_get_scheduled_to_continue' ) );

		if ( ( $sched = wp_next_scheduled( 'mainwp_maintenance_cron_scheduled_start' ) ) == false ) {
			if ( $useWPCron ) {
				wp_schedule_event( time(), 'hourly', 'mainwp_maintenance_cron_scheduled_start' );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_maintenance_cron_scheduled_start' );
			}
		}

		if ( ( $sched = wp_next_scheduled( 'mainwp_maintenance_cron_scheduled_continue' ) ) == false ) {
			if ( $useWPCron ) {
				wp_schedule_event( time(), 'minutely', 'mainwp_maintenance_cron_scheduled_continue' );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_maintenance_cron_scheduled_continue' );
			}
		}
	}

	public function init() {

	}

	public function cron_job_info() {

		$startNextRun    = Manage_Maintenance::format_TimeStamp( Manage_Maintenance::get_TimeStamp( wp_next_scheduled( 'mainwp_maintenance_cron_scheduled_start' ) ) );
		$continueNextRun = Manage_Maintenance::format_TimeStamp( Manage_Maintenance::get_TimeStamp( wp_next_scheduled( 'mainwp_maintenance_cron_scheduled_continue' ) ) );

		?>
		<tr>
			<td><?php echo __( 'Start maintenance tasks', 'mainwp-maintenance-extension' ); ?></td>
			<td><?php echo 'mainwp_maintenance_cron_scheduled_start'; ?></td>
			<td><?php echo __( 'Once hourly', 'mainwp-maintenance-extension' ); ?></td>
			<td><?php // echo $startLastRun; ?></td>
			<td><?php echo $startNextRun; ?></td>
		</tr>
		<tr>
			<td><?php echo __( 'Continue maintenance tasks', 'mainwp-maintenance-extension' ); ?></td>
			<td><?php echo 'mainwp_maintenance_cron_scheduled_continue'; ?></td>
			<td><?php echo __( 'Once every minute', 'mainwp-maintenance-extension' ); ?></td>
			<td><?php // echo $continueLastRun; ?></td>
			<td><?php echo $continueNextRun; ?></td>
		</tr>
		<?php
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}

	public function admin_init() {
		if ( isset( $_REQUEST['page'] ) && 'Extensions-Mainwp-Maintenance-Extension' == $_REQUEST['page'] ) {
			wp_enqueue_script( $this->plugin_handle . '-admin-js', $this->plugin_url . 'js/admin.js', array(), '2.0' );
			wp_enqueue_style( $this->plugin_handle . '-admin-css', $this->plugin_url . 'css/admin.css' );
			if ( isset( $_GET['a'] ) && ! empty( $_GET['a'] ) ) {
				update_option( '_mainwp_maintenance_notice_number', $_GET['a'] );
				wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Maintenance-Extension' ) );
				exit;
			}
		}
	}

	public function maintenance_run_site() {
		Manage_Maintenance::maintenance_run_site();
	}

	function maintenance_selected_sites() {
		Manage_Maintenance::render_progress_content();
	}

	public static function cron_get_schuduled_to_start() {
		$allScheduledToStart = array();
		$allScheduled        = Maintenance_Extension_DB::get_instance()->get_scheduled_to_start();

		foreach ( $allScheduled as $sched ) {
			if ( time() < $sched->schedule_nextsend ) {
				continue;
			}

			// IMPORTANCE CHECK: to prevent INCORRECT schedule_nextsend or auto send to quick (at least after about 30 mins)
			if ( ( $sched->schedule_nextsend < $sched->schedule_lastsend ) || ( time() - $sched->schedule_lastsend < 30 * 60 ) ) {
				continue;
			}
			$allScheduledToStart[] = $sched;
		}

		unset( $allScheduled );

		foreach ( $allScheduledToStart as $sched ) {
			Maintenance_Extension_DB::get_instance()->update_schedule_start( $sched->id );
			$cal_recurring = self::calc_scheduled_date( $sched->schedule, $sched->recurring_day, $sched->recurring_hour, $sched->perform );

			if ( empty( $cal_recurring ) ) {
				continue;
			}

			$values = array(
				'schedule_nextsend' => $cal_recurring['nextsend'],
			);

			Maintenance_Extension_DB::get_instance()->update_schedule_with_values( $sched->id, $values );
		}
	}

	public static function cron_get_scheduled_to_continue() {

		@ignore_user_abort( true );
		@set_time_limit( 0 );
		$mem = '512M';
		@ini_set( 'memory_limit', $mem );
		@ini_set( 'max_execution_time', 0 );

		// Fetch all tasks where complete < last & last checkup is more then 1 minute ago! & last is more then 1 minute ago!
		$schedules = Maintenance_Extension_DB::get_instance()->get_scheduled_to_continue();

		if ( empty( $schedules ) ) {
			return;
		}

		foreach ( $schedules as $sched ) {
			$sched = Maintenance_Extension_DB::get_instance()->get_maintenance_task_by_id( $sched->id );
			self::execute_task( $sched );
			break;
		}
	}

	public static function calc_scheduled_date( $schedule, $recurring_day, $recurring_hour, $perform ) {

		if ( $perform == 1 && $recurring_day != '' && ( $schedule == 'weekly' || $schedule == 'monthly' || $schedule == 'yearly' ) ) {
			$gmtOffset   = get_option( 'gmt_offset' );
			$date_offset = $gmtOffset * HOUR_IN_SECONDS;

			$the_time  = time() + $date_offset;  // to fix gmt offset issue
			$date_send = 0;

			if ( 'weekly' == $schedule ) {
				// for strtotime()
				$day_of_week = array(
					1 => 'monday',
					2 => 'tuesday',
					3 => 'wednesday',
					4 => 'thursday',
					5 => 'friday',
					6 => 'saturday',
					7 => 'sunday',
				);

				$date_send = strtotime( 'next ' . $day_of_week[ $recurring_day ] ) + $recurring_hour * 60 * 60;  // day of next week

				if ( $date_send < $the_time ) { // to fix
					$date_send += 7 * 24 * 3600;
				}
			} elseif ( 'monthly' == $schedule ) {
						$cal_month   = date( 'm', $the_time );
						$cal_year    = date( 'Y', $the_time );
						$current_day = date( 'j', $the_time );

				if ( $current_day > $recurring_day ) {
					$cal_month += 1;
				}

				if ( $cal_month > 12 ) {
					$cal_month = $cal_month - 12;
					$cal_year += 1;
				}

				$max_d = self::cal_days_in_month( $cal_month, $cal_year );

				if ( $recurring_day > $max_d ) {
					$recurring_day = $max_d;
				}

				$date_send = mktime( $recurring_hour, 0, 1, $cal_month, $recurring_day, $cal_year );
			} elseif ( 'yearly' == $schedule ) {
				$cal_year      = date( 'Y' );
				list( $m, $d ) = explode( '-', $recurring_day );
				$cal_date      = $d;
				$max_d         = self::cal_days_in_month( $m, $cal_year );

				if ( $d > $max_d ) {
					$cal_date = $max_d;
				}

				$temp_date = mktime( $recurring_hour, 0, 1, $m, $cal_date, $cal_year );

				if ( $temp_date < time() ) {
					  $cal_year += 1;
				}

				$cal_date = $d;
				$max_d    = self::cal_days_in_month( $m, $cal_year );

				if ( $d > $max_d ) {
					$cal_date = $max_d;
				}

				$date_send = mktime( $recurring_hour, 0, 1, $m, $cal_date, $cal_year );
			}

			return array(
				'nextsend' => $date_send - $date_offset, // minus gmt offset so it will send in local time
			);

		} elseif ( $recurring_day == '' && $perform ) {
			$schedule_nextsend = time();

			if ( $schedule == 'daily' ) {
				$schedule_nextsend += 60 * 60 * 24 / $perform;
			} elseif ( $schedule == 'weekly' ) {
				$schedule_nextsend += 60 * 60 * 24 * 7 / $perform;
			} elseif ( $schedule == 'monthly' ) {
				$schedule_nextsend += 60 * 60 * 24 * 30 / $perform;
			} elseif ( $schedule == 'yearly' ) {
				$schedule_nextsend += 60 * 60 * 24 * 365 / $perform;
			} else {
				return false;
			}

			return array(
				'nextsend' => $schedule_nextsend, // minus gmt offset so it will send in local time
			);
		}

		return false;
	}

	public static function cal_days_in_month( $month, $year ) {
		if ( function_exists( 'cal_days_in_month' ) ) {
			$max_d = cal_days_in_month( CAL_GREGORIAN, $month, $year );
		} else {
			$max_d = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );
		}
		return $max_d;
	}

	public static function execute_task( $sched ) {
		if ( empty( $sched ) ) {
			return;
		}

		$nrOfSites = 3;

		$completed_sites = $sched->completed_sites;

		if ( $completed_sites != '' ) {
			$completed_sites = json_decode( $completed_sites, true );
		}

		if ( ! is_array( $completed_sites ) ) {
			$completed_sites = array();
		}

		$sites = $groups = array();

		if ( $sched->groups == '' ) {
			if ( $sched->sites != '' ) {
				$sites = explode( ',', $sched->sites );
			}
		} else {
			$groups = explode( ',', $sched->groups );
		}

		if ( ! is_array( $sites ) ) {
			$sites = array();
		}

		if ( ! is_array( $groups ) ) {
			$groups = array();
		}

		global $mainWPMaintenance_Extension_Activator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), $sites, $groups );

		$currentCount = 0;

		foreach ( $dbwebsites as $dbsite ) {
			$siteid = $dbsite->id;

			if ( isset( $completed_sites[ $siteid ] ) && ( $completed_sites[ $siteid ] == true ) ) {
				continue;
			}

			$completed_sites[ $siteid ] = true;
			Maintenance_Extension_DB::get_instance()->update_completed_sites( $sched->id, $completed_sites );

			try {
				Manage_Maintenance::maintenance_site( $siteid, $sched->options, $sched->revisions );
			} catch ( Exception $e ) {
			}

			$currentCount ++;

			if ( $nrOfSites <= $currentCount ) {
				break;
			}
		}

		// update completed sites
		if ( count( $completed_sites ) >= count( $dbwebsites ) ) {
			Maintenance_Extension_DB::get_instance()->update_schedule_completed( $sched->id );
		}

		return true;
	}

	public function maintenance_removetask() {
		Manage_Maintenance::remove_maintenance();
	}

	public function maintenance_addtask() {
		Manage_Maintenance::save_maintenance();
		die();
	}

	public function maintenance_updatetask() {
		Manage_Maintenance::save_maintenance();
		die();
	}

	public function maintenance_save_settings() {
		Manage_Maintenance::save_settings();
		die();
	}

	public function maintenance_save_settings_load_sites() {
		Manage_Maintenance::load_sites_to_save_settings();
		die();
	}

	public function mainwp_maintenance_performsavesettings() {
		Manage_Maintenance::perform_save_settings();
		die();
	}

	public function maintenace_reload_select_sites_action() {

		$what = $_POST['what'];

		if ( 'task' == $what ) {
			if ( isset( $_POST['taskid'] ) && ! empty( $_POST['taskid'] ) ) {
				$task = Maintenance_Extension_DB::get_instance()->get_maintenance_task_by_id( $_POST['taskid'] );
				if ( null != $task ) {
					$selected_websites = ( '' != $task->sites ) ? explode( ',', $task->sites ) : array();
					$selected_groups   = ( '' != $task->groups ) ? explode( ',', $task->groups ) : array();
					do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_websites, $selected_groups );
					die();
				}
			}
			die( 'NOTASK' );
		} else {
			$settings                   = get_option( 'mainwp_maintenance_settings' );
			$selected_websites_settings = isset( $settings['sites'] ) && is_array( $settings['sites'] ) ? $settings['sites'] : array();
			$selected_groups_settings   = isset( $settings['groups'] ) && is_array( $settings['groups'] ) ? $settings['groups'] : array();
			do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_websites_settings, $selected_groups_settings );
			die();
		}

	}

	public function maintenance_maintenancetask_get_sites_to_run() {
		$taskID = $_POST['task_id'];
		Manage_Maintenance::render_progress_content( $taskID );
	}
}


function maintenance_extension_autoload( $class_name ) {
	$allowedLoadingTypes = array( 'class', 'page' );
	$class_name          = str_replace( '_', '-', strtolower( $class_name ) );
	foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}
}

class Maintenance_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle       = 'mainwp-maintenance-extension';
	protected $product_id          = 'MainWP Maintenance Extension';
	protected $software_version    = '4.1.1';

	public function __construct() {
		$this->childFile = __FILE__;

		$this->includes();

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}

			add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

	function includes() {
		require_once 'class/maintenance-extension-db.class.php';
		require_once 'class/manage-maintenance.class.php';
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'maintenance_settings' ),
			'apiManager' => true,
		);
		return $pArray;
	}

	function maintenance_settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		Manage_Maintenance::get_instance()->render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];

		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-maintenance-extension' ) ) {
			return;
		}

		Maintenance_Extension::get_instance();
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Maintenance Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function get_child_key() {
		return $this->childKey;
	}

	public function get_child_file() {
		return $this->childFile;
	}

	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle, $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPMaintenance_Extension_Activator;

$mainWPMaintenance_Extension_Activator = new Maintenance_Extension_Activator();
