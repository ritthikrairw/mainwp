<?php
// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	echo 'Howdy! I am not of much use without MotherShip Dashboard.';
	exit;
}


add_action( 'init', 'mainwp_wptc_init' );

// add_action('setup_theme', 'init_wptc_cron');

require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'wptc-constants.php';

require_once ABSPATH . 'wp-admin/includes/file.php';

require_once MAINWP_WPTC_CLASSES_DIR . 'FileList.php';
require_once MAINWP_WPTC_CLASSES_DIR . 'Config.php';
require_once MAINWP_WPTC_CLASSES_DIR . 'BackupController.php';
require_once MAINWP_WPTC_CLASSES_DIR . 'Factory.php';
require_once MAINWP_WPTC_CLASSES_DIR . 'ActivityLog.php';


function mainwp_include_primary_files_wptc() {

	include_once MAINWP_WPTC_PLUGIN_DIR . 'source/Base/Factory.php';

	include_once MAINWP_WPTC_PLUGIN_DIR . 'source/Base/init.php';
	include_once MAINWP_WPTC_PLUGIN_DIR . 'source/Base/Hooks.php';
	include_once MAINWP_WPTC_PLUGIN_DIR . 'source/Base/HooksHandler.php';
	include_once MAINWP_WPTC_PLUGIN_DIR . 'source/Base/Config.php';

	include_once MAINWP_WPTC_PLUGIN_DIR . 'source/Base/CurlWrapper.php';

	include_once MAINWP_WPTC_CLASSES_DIR . 'WptcBackup/Hooks.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'WptcBackup/init.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'WptcBackup/HooksHandler.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'WptcBackup/Config.php';

	include_once MAINWP_WPTC_CLASSES_DIR . 'Common/init.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'Common/Hooks.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'Common/HooksHandler.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'Common/Config.php';

	// include_once MAINWP_WPTC_CLASSES_DIR.'Analytics/init.php';
	// include_once MAINWP_WPTC_CLASSES_DIR.'Analytics/Hooks.php';
	// include_once MAINWP_WPTC_CLASSES_DIR.'Analytics/HooksHandler.php';
	// include_once MAINWP_WPTC_CLASSES_DIR.'Analytics/Config.php';
	// include_once MAINWP_WPTC_CLASSES_DIR.'Analytics/BackupAnalytics.php';

	include_once MAINWP_WPTC_CLASSES_DIR . 'Settings/Settings.php';

	include_once MAINWP_WPTC_CLASSES_DIR . 'InitialSetup/init.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'InitialSetup/Hooks.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'InitialSetup/HooksHandler.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'InitialSetup/Config.php';
	include_once MAINWP_WPTC_CLASSES_DIR . 'InitialSetup/InitialSetup.php';

	MainWP_WPTC_Base_Factory::get( 'MainWP_Wptc_Base' )->init();
}


function mainwp_wptc_init() {
	do_action( 'mainwp_just_initialized_wptc_h', '' );
	mainwp_wptc_init_actions();
	mainwp_include_primary_files_wptc();
}

function mainwp_wptc_style() {
	// Register stylesheet
	wp_register_style( 'wptc-style', MAINWP_WP_TIME_CAPSULE_URL . '/source/wptime-capsule.css' );
	wp_enqueue_style( 'wptc-style', false, array(), MainWP_WPTC_VERSION );
	wp_enqueue_style( 'dashicons', false, array(), MainWP_WPTC_VERSION );
}

$config = MainWP_WPTC_Factory::get( 'config' );
define( 'MAINWP_WPTC_DEFAULT_REPO', $config->get_option( 'default_repo' ) );

$repo_labels_arr = array(
	'g_drive' => 'Google Drive',
	's3'      => 'Amazon S3',
	'dropbox' => 'Dropbox',
);
if ( defined( 'MAINWP_WPTC_DEFAULT_REPO' ) ) {
	$this_repo = MAINWP_WPTC_DEFAULT_REPO;
}
if ( ! empty( $this_repo ) && ! empty( $repo_labels_arr[ $this_repo ] ) ) {
	$supposed_repo_label = $repo_labels_arr[ $this_repo ];
} else {
	$supposed_repo_label = 'Cloud';
}

define( 'MAINWP_WTC_DEFAULT_REPO_LABEL', $supposed_repo_label );


function mainwp_wordpress_time_capsule_login() {
	include MAINWP_WPTC_PLUGIN_DIR . 'source/Views/wptc-login.php';
}

function mainwp_wordpress_time_capsule_change_store() {
	include MAINWP_WPTC_PLUGIN_DIR . 'source/Views/wptc-store.php';
}

/**
 * A wrapper function that includes the WP Time Capsule options page
 *
 * @return void
 */
function mainwp_wordpress_time_capsule_activity() {
	if ( isset( $_GET['id'] ) && $_GET['id'] > 0 ) {
		$current_site_id = $_GET['id'];
		$uri             = 'admin.php?page=ManageSitesWPTimeCapsule&tab=activity_log&id=' . $current_site_id;
	} else {
		return;
	}
	include MAINWP_WPTC_PLUGIN_DIR . 'source/Views/wptc-activity.php';
}

function mainwp_wordpress_time_capsule_admin_menu_contents() {
	$uri = rtrim( MAINWP_WP_TIME_CAPSULE_URL, '/' );
	include MAINWP_WPTC_PLUGIN_DIR . 'source/Views/wptc-options.php';
}

function mainwp_wordpress_time_capsule_monitor() {

}

/**
 * A wrapper function for the progress AJAX request
 *
 * @return void
 */
function mainwp_tc_backup_progress_wptc() {
	 MainWP_TimeCapsule::ajax_check_data();

	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data = array(
		'mwp_action' => 'progress_wptc',
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

	$err = __( 'Undefined error.' );

	if ( is_array( $information ) && isset( $information['result'] ) ) {
		die( json_encode( $information['result'] ) );
	} elseif ( isset( $information['error'] ) ) {
		$err = $information['error'];
	}

	die(
		json_encode(
			array(
				'error' => $err,
				'extra' => $information,
			)
		)
	);

	// include MAINWP_WPTC_PLUGIN_DIR.'source/Views/wptc-progress.php';
}

/**
 * A wrapper function for the progress AJAX request
 *
 * @return void
 */
function mainwp_get_this_day_backups_callback_wptc() {
	// note that we are getting the ajax function data via $_POST.
	$backupIds = $_POST['data'];
	echo mainwp_get_this_backups_html( $backupIds );
}


function mainwp_get_this_backups_html( $this_backup_ids, $specific_dir = null, $type = null, $treeRecursiveCount = 0 ) {
	MainWP_TimeCapsule::ajax_check_data();

	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data = array(
		'mwp_action'         => 'get_this_backups_html',
		'this_backup_ids'    => $this_backup_ids,
		'specific_dir'       => $specific_dir,
		'type'               => $type,
		'treeRecursiveCount' => $treeRecursiveCount,
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

	if ( is_array( $information ) && isset( $information['result'] ) ) {
		return $information['result'];
	}
	return 'Undefined error';
}


function mainwp_get_sibling_files_callback_wptc() {

	MainWP_TimeCapsule::ajax_check_data();
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data   = array(
		'mwp_action' => 'get_sibling_files',
		'data'       => $_POST['data'],
	);
	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );
	die( $information );

}

function mainwp_start_fresh_backup_tc_callback_wptc( $type = '', $args = null ) {

	MainWP_TimeCapsule::ajax_check_data();
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;
	$data = $_REQUEST;
	unset( $data['action'] );
	unset( $data['timecapsuleSiteID'] );
	$post_data = array(
		'mwp_action' => 'start_fresh_backup',
		'data'       => $data,
		'type'       => $_REQUEST['type'],
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );
	die( json_encode( $information ) );
}

function mainwp_stop_fresh_backup_tc_callback_wptc( $deactivated_plugin = null ) {

	MainWP_TimeCapsule::ajax_check_data();

	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data = array(
		'mwp_action' => 'stop_fresh_backup',
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

	if ( is_array( $information ) && isset( $information['result'] ) ) {
		die( 'ok' );
	}

	die(
		json_encode(
			array(
				'error' => 'Undefined error.',
				'extra' => $information,
			)
		)
	);

}


function mainwp_start_restore_tc_callback_wptc() {

	MainWP_TimeCapsule::ajax_check_data();
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data   = array(
		'mwp_action' => 'start_restore_tc_wptc',
		'data'       => $_POST['data'],
	);
	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );
	die( $information );
}

function mainwp_wptc_send_issue_report_callback() {

	MainWP_TimeCapsule::ajax_check_data();
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data   = array(
		'mwp_action' => 'send_issue_report',
		'data'       => $_REQUEST['data'],
	);
	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

	die( $information );
}


function mainwp_wptc_init_flags() {
	try {

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'before_backup' ) ) {
			MainWP_WPTC_Factory::get( 'config' )->set_option( 'before_backup', 'yes_no' );
		}

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'anonymous_datasent' ) ) {
			MainWP_WPTC_Factory::get( 'config' )->set_option( 'anonymous_datasent', 'no' );
		}

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'auto_backup_interval' ) ) {
			MainWP_WPTC_Factory::get( 'config' )->set_option( 'auto_backup_interval', 'every_ten' );
		}

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'auto_backup_switch' ) ) {
			MainWP_WPTC_Factory::get( 'config' )->set_option( 'auto_backup_switch', 'off' );
		}

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'schedule_backup' ) ) {
			MainWP_WPTC_Factory::get( 'config' )->set_option( 'schedule_backup', 'off' );
		}

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'wptc_timezone' ) ) {
			if ( get_option( 'timezone_string' ) != '' ) {
				MainWP_WPTC_Factory::get( 'config' )->set_option( 'wptc_timezone', get_option( 'timezone_string' ) );
			} else {
				MainWP_WPTC_Factory::get( 'config' )->set_option( 'wptc_timezone', 'UTC' );
			}
		}

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'schedule_day' ) ) {
			MainWP_WPTC_Factory::get( 'config' )->set_option( 'schedule_day', 'sunday' );
		}

		if ( ! MainWP_WPTC_Factory::get( 'config' )->get_option( 'wptc_service_request' ) ) {
			MainWP_WPTC_Factory::get( 'config' )->set_option( 'wptc_service_request', 'no' );
		}
	} catch ( Exception $e ) {
		error_log( $e->getMessage() );
	}
}

function mainwp_send_wtc_issue_report_wptc() {
	MainWP_TimeCapsule::ajax_check_data();
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
	global $mainwpWPTimeCapsuleExtensionActivator;
	$data        = $_REQUEST['data'];
	$post_data   = array(
		'mwp_action' => 'send_issue_report',
		'data'       => $data,
	);
	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );
	if ( is_array( $information ) && isset( $information['result'] ) ) {
		echo $information['result'];
	} elseif ( isset( $information['error'] ) ) {
		echo $information['error'];
	} else {
		echo 'Undefined error';
	}
	die();
}
// Clear the WPTC log's completely
function mainwp_clear_wptc_logs() {
	MainWP_TimeCapsule::ajax_check_data();
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data = array(
		'mwp_action' => 'clear_logs',
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

	if ( is_array( $information ) && isset( $information['result'] ) ) {
		die( $information['result'] );
	} elseif ( isset( $information['error'] ) ) {
		 die( $information['error'] );
	} else {
		 die( 'Undefined error.' );
	}
}

function mainwp_register_the_js_events_wptc( $hook ) {

	if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'Extensions-Mainwp-Timecapsule-Extension' || ( $_GET['page'] == 'ManageSitesWPTimeCapsule' ) ) ) {
		wp_enqueue_style( 'mainwp-tc-ui', MAINWP_WP_TIME_CAPSULE_URL . '/source/tc-ui.css', array(), MainWP_WPTC_VERSION );
		wp_enqueue_style( 'mainwp-wptc-fancytree-css', MAINWP_WP_TIME_CAPSULE_URL . '/source/treeView/skin/ui.fancytree.css', array(), MainWP_WPTC_VERSION );
		wp_enqueue_script( 'mainwp-wptc-jquery-ui-custom-js', MAINWP_WP_TIME_CAPSULE_URL . '/source/treeView/jquery-ui.custom.js', array(), MainWP_WPTC_VERSION );
		wp_enqueue_script( 'mainwp-wptc-fancytree-js', MAINWP_WP_TIME_CAPSULE_URL . '/source/treeView/jquery.fancytree.js', array( 'mainwp-wptc-jquery-ui-custom-js' ), MainWP_WPTC_VERSION );

		wp_enqueue_style( 'mainwp-wptc-sweetalert-css', MAINWP_WP_TIME_CAPSULE_URL . '/source/lib/sweetalert.css', array(), MainWP_WPTC_VERSION );
		wp_enqueue_script( 'mainwp-wptc-sweetalert-js', MAINWP_WP_TIME_CAPSULE_URL . '/source/lib/sweetalert.min.js', array(), MainWP_WPTC_VERSION );

		wp_enqueue_style( 'opentip', MAINWP_WP_TIME_CAPSULE_URL . '/source/css/opentip.css', array(), MainWP_WPTC_VERSION );
		wp_enqueue_script( 'jquery', false, array(), MainWP_WPTC_VERSION );
		wp_enqueue_script( 'mainwp-time-capsule-update-actions', MAINWP_WP_TIME_CAPSULE_URL . '/source/time-capsule-update-actions.js', array(), MainWP_WPTC_VERSION );
		wp_enqueue_script( 'opentip-jquery', MAINWP_WP_TIME_CAPSULE_URL . '/source/js/opentip-jquery.js', array(), MainWP_WPTC_VERSION );
	}

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'ManageSitesWPTimeCapsule' && isset( $_GET['tab'] ) && ( $_GET['tab'] == 'staging' || $_GET['tab'] == 'backup' ) ) {
		wp_enqueue_script( 'mainwp-wptc-staging-init', MAINWP_WP_TIME_CAPSULE_URL . '/source/Pro/Staging/init.js', array(), MainWP_WPTC_VERSION );
	}
}

add_action( 'admin_enqueue_scripts', 'mainwp_register_the_js_events_wptc' );

function mainwp_wptc_init_actions() {
	if ( is_admin() ) {

		add_action( 'admin_enqueue_scripts', 'mainwp_wptc_style' );

		// WordPress filters and actions
		add_action( 'wp_ajax_mainwp_progress_wptc', 'mainwp_tc_backup_progress_wptc' );
		add_action( 'wp_ajax_mainwp_get_this_day_backups_wptc', 'mainwp_get_this_day_backups_callback_wptc' );
		add_action( 'wp_ajax_mainwp_get_sibling_files_wptc', 'mainwp_get_sibling_files_callback_wptc' );

		add_action( 'wp_ajax_mainwp_start_fresh_backup_tc_wptc', 'mainwp_start_fresh_backup_tc_callback_wptc' );
		add_action( 'wp_ajax_mainwp_stop_fresh_backup_tc_wptc', 'mainwp_stop_fresh_backup_tc_callback_wptc' );
		add_action( 'wp_ajax_mainwp_start_restore_tc_wptc', 'mainwp_start_restore_tc_callback_wptc' );
		add_action( 'wp_ajax_mainwp_send_wtc_issue_report_wptc', 'mainwp_send_wtc_issue_report_wptc' );
		add_action( 'wp_ajax_mainwp_wptc_send_issue_report', 'mainwp_wptc_send_issue_report_callback' );
		add_action( 'wp_ajax_mainwp_clear_wptc_logs', 'mainwp_clear_wptc_logs' );
		add_action( 'wp_ajax_mainwp_lazy_load_activity_log_wptc', 'mainwp_lazy_load_activity_log_wptc' );
		add_action( 'wp_ajax_mainwp_test_connection_wptc_cron', 'mainwp_test_connection_wptc_cron' );
		add_action( 'wp_ajax_mainwp_login_wptc', 'ajax_mainwp_login_wptc' );
		add_action( 'wp_ajax_mainwp_wptc_override_general_settings', 'ajax_mainwp_wptc_override_general_settings' );
		add_action( 'wp_ajax_mainwp_get_s3_authorize_url_wptc', 'mainwp_get_s3_authorize_url_wptc' );

		add_action( 'wp_ajax_mainwp_save_manual_backup_name_wptc', 'mainwp_save_manual_backup_name_wptc' );

	}
}


function mainwp_get_s3_authorize_url_wptc() {
	if ( empty( $_POST['credsData'] ) ) {
		echo json_encode( array( 'error' => 'Didnt get credentials.' ) );
		die();
	}

	$as3_access_key    = $_POST['credsData']['as3_access_key'];
	$as3_secure_key    = $_POST['credsData']['as3_secure_key'];
	$as3_bucket_region = $_POST['credsData']['as3_bucket_region'];
	$as3_bucket_name   = $_POST['credsData']['as3_bucket_name'];

	if ( empty( $as3_access_key ) || empty( $as3_secure_key ) || empty( $as3_bucket_name ) ) {
		echo json_encode( array( 'error' => 'Didnt get credentials.' ) );
		die();
	}

	// $config = MainWP_WPTC_Factory::get('config');
	// $config->set_option('as3_access_key', $as3_access_key);
	// $config->set_option('as3_secure_key', $as3_secure_key);
	// $config->set_option('as3_bucket_region', $as3_bucket_region);
	// $config->set_option('as3_bucket_name', $as3_bucket_name);
	// $config->set_option('default_repo', 's3');
	// $result['authorize_url'] = network_admin_url() . 'admin.php?page=wp-time-capsule&cloud_auth_action=s3&as3_access_key=' . $as3_access_key . '&as3_secure_key=' . $as3_secure_key . '&as3_bucket_region=' . $as3_bucket_region . '&as3_bucket_name=' . $as3_bucket_name . '';
	// MainWP_WPTC_Factory::get('S3Facade');
	// echo json_encode($result);
	die();
}

//
// function plugin_update_notice_wptc() {
// $config = MainWP_WPTC_Factory::get('config');
// $config->set_option('user_came_from_existing_ver', 0);
// }


// function show_processing_files_view_wptc() {
// $config = MainWP_WPTC_Factory::get('config');
// $config->set_option('show_processing_files_view_wptc', false);
// }

function mainwp_test_connection_wptc_cron() {

	MainWP_TimeCapsule::ajax_check_data();
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data = array(
		'mwp_action' => 'wptc_cron_status',
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

	if ( is_array( $information ) && isset( $information['result'] ) ) {
		die( json_encode( $information['result'] ) );
	}
	die(
		json_encode(
			array(
				'error' => 'Undefined error.',
				'extra' => $information,
			)
		)
	);
}

function ajax_mainwp_login_wptc() {

	$general   = ( isset( $_POST['type'] ) && $_POST['type'] == 'general' ) ? true : false;
	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	if ( ! $general ) {
		MainWP_TimeCapsule::ajax_check_data( true );
	}

	// general login
	if ( $general ) {
		if ( isset( $_POST['step'] ) && $_POST['step'] == 'general_login' ) {
			MainWP_TimeCapsule::ajax_check_data();
			MainWP_TimeCapsule::ajax_check_override( $websiteId, false );
			$temp_info = MainWP_TimeCapsule_DB::get_instance()->get_settings( 0 );
			if ( is_array( $temp_info ) && isset( $temp_info['encoded_acc_email'] ) && ! empty( $temp_info['encoded_acc_email'] ) ) {
				$acc_email = base64_decode( $temp_info['encoded_acc_email'] );
				$acc_pwd   = base64_decode( $temp_info['encoded_acc_pwd'] );
			}
		} else {
			$encoded_acc_email = base64_encode( $_POST['acc_email'] );
			$encoded_acc_pwd   = base64_encode( trim( wp_unslash( $_POST['acc_pwd'] ) ) );
			$update            = array(
				'encoded_acc_email' => $encoded_acc_email,
				'encoded_acc_pwd'   => $encoded_acc_pwd,
			);
			MainWP_TimeCapsule_DB::get_instance()->update_settings_fields( (int) 0, $update );
			die( json_encode( array( 'result' => 'next_step' ) ) );
		}
	} else {
		$acc_email = $_POST['acc_email'];
		$acc_pwd   = $_POST['acc_pwd'];
		$update    = array(
			'encoded_acc_email' => base64_encode( $acc_email ),
			'encoded_acc_pwd'   => base64_encode( trim( wp_unslash( $acc_pwd ) ) ),
		);
		MainWP_TimeCapsule_DB::get_instance()->update_settings_fields( (int) $websiteId, $update );
	}

	if ( empty( $acc_email ) || empty( $acc_pwd ) ) {
		die( json_encode( array( 'error' => 'Error: email or password cannot be empty.' ) ) );
	}

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data = array(
		'mwp_action' => 'wptc_login',
		'acc_email'  => $acc_email,
		'acc_pwd'    => $acc_pwd,
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

	// logged in
	if ( is_array( $information ) && isset( $information['sync_data'] ) ) {
		$data = $information['sync_data'];
		if ( is_array( $data ) && isset( $data['main_account_email'] ) ) {
			$lastbackup_time = $data['lastbackup_time'];
			$backups_count   = isset( $data['backups_count'] ) ? $data['backups_count'] : 0;
			unset( $data['lastbackup_time'] );
			unset( $data['backups_count'] );
			if ( $websiteId ) {
				MainWP_TimeCapsule_DB::get_instance()->update_settings_fields( (int) $websiteId, $data );
				$update_data = array(
					'lastbackup_time' => $lastbackup_time,
					'backups_count'   => intval( $backups_count ),
					'site_id'         => $websiteId,
				);
				MainWP_TimeCapsule_DB::get_instance()->update_data( $update_data );
			}
		}
	}

	die( json_encode( $information ) );
}

function ajax_mainwp_wptc_override_general_settings() {

	MainWP_TimeCapsule::ajax_check_data();

	$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

	global $mainwpWPTimeCapsuleExtensionActivator;

	$update_data = array(
		'override' => $_POST['override'] ? 1 : 0,
		'site_id'  => $websiteId,
	);

	MainWP_TimeCapsule_DB::get_instance()->update_data( $update_data );

	die( json_encode( array( 'result' => 'ok' ) ) );
}

function mainwp_save_manual_backup_name_wptc() {
	MainWP_TimeCapsule::ajax_check_data();

	$websiteId   = intval( $_REQUEST['timecapsuleSiteID'] );
	$backup_name = $_POST['name'];

	global $mainwpWPTimeCapsuleExtensionActivator;

	$post_data = array(
		'mwp_action'  => 'save_manual_backup_name',
		'backup_name' => $backup_name,
	);

	$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );

	die( $information );
}
