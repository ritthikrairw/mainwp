<?php
/*
  Plugin Name: MainWP Time Capsule Extension
  Plugin URI: https://mainwp.com
  Description: With the MainWP Time Capsule Extension, you can control the WP Time Capsule Plugin on all your child sites directly from your MainWP Dashboard.
  Version: 4.0.3
  Author: MainWP
  Author URI: https://mainwp.com
  Documentation URI: http://mainwp.com/help/category/mainwp-extensions/time-capsule
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

if ( ! defined( 'MAINWP_WPTC_CLASSES_DIR' ) ) {
	define( 'MAINWP_WPTC_CLASSES_DIR', str_replace( '/', DIRECTORY_SEPARATOR, plugin_dir_path( __FILE__ ) . 'source/Classes/' ) );
	define( 'MAINWP_WPTC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'MAINWP_WP_TIME_CAPSULE_URL' ) ) {
	define( 'MAINWP_WP_TIME_CAPSULE_URL', plugins_url( '', __FILE__ ) );
}

class MainWP_TimeCapsule_Extension {

	public $plugin_slug;
	public static $isPremium      = null;
	protected $staging_sites_info = null;
	public $the_plugin_sites      = null;

	public function __construct() {
		$this->plugin_slug = plugin_basename( __FILE__ );
		add_action( 'plugins_loaded', array( $this, 'load_translations' ) );

		self::files_include();
		require_once 'source/wptime-capsule.php';

		global $mainwp_timecapsule_current_site_id;
		if ( $mainwp_timecapsule_current_site_id === null ) {
			$mainwp_timecapsule_current_site_id = MainWP_TimeCapsule::get_current_manage_site_id();
		}

		MainWP_TimeCapsule_DB::get_instance()->install();
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ), 20 );
		add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );

		add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'sitestable_getcolumns' ), 10 );
		add_filter( 'mainwp_sitestable_item', array( $this, 'sitestable_item' ), 10 );

		add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( $this, 'synced_site' ), 10, 2 );
		add_filter( 'mainwp_widgetupdates_actions_top', array( &$this, 'hook_widgetupdates_actions_top' ), 10, 1 );

		$add_managesites_column = false;
		$primary_backup         = get_option( 'mainwp_primaryBackup', null );

		if ( 'wptimecapsule' == $primary_backup ) {
			add_filter( 'mainwp_managesites_getbackuplink', array( $this, 'managesites_backup_link' ), 10, 2 );
			add_filter( 'mainwp_getcustompage_backups', array( $this, 'add_page_backups' ), 10, 1 );
			add_filter( 'mainwp_getprimarybackup_activated', array( $this, 'primary_backups_activated' ), 10, 1 );
			$add_managesites_column = true;
		} elseif ( empty( $primary_backup ) ) {
			$add_managesites_column = true;
		}

		if ( $add_managesites_column ) {
			add_filter( 'mainwp_managesites_column_url', array( &$this, 'managesites_column_url' ), 10, 2 );
		}

		add_filter( 'mainwp_getprimarybackup_methods', array( $this, 'primary_backups_method' ), 10, 1 );

	}

	public function load_translations() {
		load_plugin_textdomain( 'mainwp-timecapsule-extension', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	public static function update_option( $option_name, $option_value,
									   $autoload = 'no' ) {
		$success = add_option( $option_name, $option_value, '', $autoload );

		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}

		return $success;
	}

	public static function files_include() {
		include_once 'class/class-mainwp-timecapsule-db.php';
		include_once 'class/class-mainwp-timecapsule-plugin.php';
		include_once 'class/class-mainwp-timecapsule-utility.php';
		include_once 'class/class-mainwp-timecapsule.php';
	}

	public function primary_backups_activated( $input ) {
		return 'wptimecapsule';
	}

	public function primary_backups_method( $methods ) {
		$methods[] = array(
			'value' => 'wptimecapsule',
			'title' => 'MainWP WP Time Capsule Extension',
		);
		return $methods;
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

	public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$data['syncWPTimeCapsule'] = 1;
		return $data;
	}

	public function synced_site( $pWebsite, $information = array() ) {
		if ( is_array( $information ) && isset( $information['syncWPTimeCapsule'] ) ) {
			$data = $information['syncWPTimeCapsule'];

			if ( is_array( $data ) ) {
				if ( isset( $data['main_account_email'] ) ) {
					$lastbackup_time = $data['lastbackup_time'];
					$backups_count   = isset( $data['backups_count'] ) ? $data['backups_count'] : 0;
					unset( $data['lastbackup_time'] );
					$websiteId = $pWebsite->id;
					if ( $websiteId ) {
						// $data: 'main_account_email', 'signed_in_repos', 'plan_name', 'plan_interval', 'is_user_logged_in'
						MainWP_TimeCapsule_DB::get_instance()->update_settings_fields( (int) $websiteId, $data );
						$update_data = array(
							'lastbackup_time' => intval( $lastbackup_time ),
							'backups_count'   => intval( $backups_count ),
							'site_id'         => $websiteId,
						);
						MainWP_TimeCapsule_DB::get_instance()->update_data( $update_data );
					}
				}

				if ( isset( $data['availableClones'] ) ) {
					$clones = $data['availableClones'];
					if ( is_array( $clones ) && isset( $clones['destination_url'] ) ) {
						$clone_url = $clones['destination_url'];
						$cloneID   = $clones['staging_folder'];
						MainWP_TimeCapsule::get_instance()->sync_staging_site_data( $websiteId, $clone_url, $cloneID );
					}
				}
			}

			unset( $information['syncWPTimeCapsule'] );
		}
	}

	public function sitestable_getcolumns( $columns ) {
		$columns['timecapsule_staging'] = __( 'WPTC Staging', 'mainwp-timecapsule-extension' );
		return $columns;
	}

	public function sitestable_item( $item ) {
		// do not add links for staging sites
		if ( $item['is_staging'] ) {
			return $item;
		}
		$site_id = $item['id'];

		$created = false;
		if ( $this->staging_sites_info && isset( $this->staging_sites_info[ $site_id ] ) && $this->staging_sites_info[ $site_id ] > 0 ) {
			$created = true;
		}

		$item['timecapsule_staging'] = '<a href="admin.php?page=ManageSitesWPTimeCapsule&tab=staging&id=' . $site_id . '" class="ui mini green basic button">' . ( $created ? __( 'Manage', 'mainwp-timecapsule-extension' ) : __( 'Create', 'mainwp-timecapsule-extension' ) ) . '</a>';

		return $item;
	}

	function hook_widgetupdates_actions_top( $input ) {
		// added the selection
		if ( $input != '' ) {
			return $input;
		}

		$view_stagings = get_user_option( 'mainwp_staging_options_updates_view' ) == 'staging' ? true : false;
		$input        .= '<div class="mainwp-cols-s mainwp-left mainwp-t-align-left">
                <form method="post" action="">
                    <label for="mainwp_staging_select_options_siteview">' . __( 'View updates in: ', 'mainwp' ) . '</label>
                    <select class="mainwp-select2-mini" id="mainwp_staging_select_options_siteview" name="select_mainwp_staging_options_siteview">
                        <option value="live" ' . ( $view_stagings ? '' : 'selected' ) . '>' . esc_html__( 'Live Sites', 'mainwp' ) . '</option>
                        <option value="staging" ' . ( $view_stagings ? 'selected' : '' ) . '>' . esc_html__( 'Staging Sites', 'mainwp' ) . '</option>
                    </select>
                </form>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    jQuery("#mainwp_staging_select_options_siteview").change(function() {
                        jQuery(this).closest("form").submit();
                    });
                })
        	</script>';

		return $input;
	}

	function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title'            => __( 'WP Time Capsule', 'mainwp-timecapsule-extension' ),
			'slug'             => 'WPTimeCapsule',
			'sitetab'          => true,
			'menu_hidden'      => true,
			'callback'         => array( 'MainWP_TimeCapsule', 'render' ),
			'on_load_callback' => '',
		);
		return $subPage;
	}

	public function managesites_column_url( $actions, $websiteid ) {
		$actions['wptimecapsule'] = sprintf( '<a href="admin.php?page=ManageSitesWPTimeCapsule&id=%1$s">WP Time Capsule</a>', $websiteid );
		return $actions;
	}

	public function managesites_backup_link( $input, $site_id ) {

		if ( $site_id ) {
			$lastbackup = 0;
			if ( is_array( $this->the_plugin_sites ) && isset( $this->the_plugin_sites[ $site_id ] ) ) {
				$d = $this->the_plugin_sites[ $site_id ];
				if ( is_array( $d ) ) {
					$lastbackup = ( isset( $d['lastbackup_time'] ) ) ? $d['lastbackup_time'] : 0;

				}
			}
			$output = '';
			if ( ! empty( $lastbackup ) ) {
				$output = MainWP_TimeCapsule_Utility::format_timestamp( MainWP_TimeCapsule_Utility::get_timestamp( $lastbackup ) ) . '<br />';
			} else {
				$output = '<span class="mainwp-red">Never</span><br/>';
			}
			$link = '';
			if ( mainwp_current_user_can( 'dashboard', 'execute_backups' ) ) {
				$link .= sprintf( '<a href="admin.php?page=ManageSitesWPTimeCapsule&tab=backup&id=%d">' . __( 'Backup Now', 'mainwp-timecapsule-extension' ) . '</a>', $site_id );
			}
			$output .= $link;

			return $output;
		} else {
			return $input;
		}
	}

	public function add_page_backups( $input = null ) {
		return array(
			'title'            => __( 'Existing Backups', 'mainwp-timecapsule-extension' ),
			'slug'             => 'MainWP_TimeCapsule',
			'managesites_slug' => 'WPTimeCapsule',
			'callback'         => array( $this, 'render_redicting' ),
		);
	}

	public function render_redicting() {
		?>
		<div id="mainwp_background-box">
			<div style="font-size: 30px; text-align: center; margin-top: 5em;"><?php _e( 'You will be redirected to the page immediately.', 'mainwp-timecapsule-extension' ); ?></div>
			<script type="text/javascript">
				window.location = "admin.php?page=Extensions-Mainwp-Timecapsule-Extension";
			</script>
		</div>
		<?php
	}

	public function admin_init() {

		// to fix conflict with Post S M T P plugin.
		$primary_backup = get_option( 'mainwp_primaryBackup', null );
		if ( ( 'wptimecapsule' == $primary_backup ) && ( ( isset( $_GET['page'] ) && 'managesites' == $_GET['page'] ) || ( defined( 'DOING_AJAX' ) && isset( $_POST['action'] ) && $_POST['action'] == 'mainwp_manage_sites_display_rows' ) ) ) {
			// load data to reduce db query
			if ( $this->the_plugin_sites === null ) {
				$this->the_plugin_sites = MainWP_TimeCapsule_Plugin::get_instance()->get_time_capsule_websites();
			}
		}

		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'Extensions-Mainwp-Timecapsule-Extension' || $_GET['page'] == 'ManageSitesWPTimeCapsule' ) ) {
			wp_enqueue_style( 'mainwp-timecapsule-extension', MAINWP_WP_TIME_CAPSULE_URL . '/assests/css/mainwp-wptc.css' );
			wp_enqueue_script( 'mainwp-timecapsule-extension', MAINWP_WP_TIME_CAPSULE_URL . '/assests/js/mainwp-wptc.js', array(), MainWP_WPTC_VERSION );
			wp_localize_script(
				'mainwp-timecapsule-extension',
				'mainwp_timecapsule_loc',
				array(
					'nonce' => wp_create_nonce( 'timecapsule_nonce' ),
				)
			);
		}

		// create staging group if needed
		if ( false === get_option( 'mainwp_stagingsites_group_id', false ) ) {
			global $mainwpWPTimeCapsuleExtensionActivator;
			$staging_group_id = apply_filters( 'mainwp_addgroup', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $group_name = 'Staging Sites' );
			if ( $staging_group_id ) {
				self::update_option( 'mainwp_stagingsites_group_id', intval( $staging_group_id ), 'yes' );
			}
		}

		if ( isset( $_POST['select_mainwp_staging_options_siteview'] ) ) {
			global $current_user;
			update_user_option( $current_user->ID, 'mainwp_staging_options_updates_view', $_POST['select_mainwp_staging_options_siteview'] );
		}

		if ( ( isset( $_POST['action'] ) && 'mainwp_manage_sites_display_rows' == $_POST['action'] ) ||
			( isset( $_GET['page'] ) && 'managesites' == $_GET['page'] && ! isset( $_GET['id'] ) && ! isset( $_GET['do'] ) && ! isset( $_GET['dashboard'] ) ) ) {
			if ( $this->staging_sites_info === null ) {
				$this->staging_sites_info = MainWP_TimeCapsule_DB::get_instance()->get_count_stagings_of_sites();
			}
		}

		MainWP_TimeCapsule::get_instance()->admin_init();
		MainWP_TimeCapsule_Plugin::get_instance()->admin_init();
	}
}

class MainWP_TimeCapsule_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'mainwp-timecapsule-extension';
	protected $product_id       = 'MainWP Time Capsule Extension';
	protected $software_version = '4.0.3';

	public function __construct() {

		$this->childFile = __FILE__;

		// loaded, see MainWP_TimeCapsule_Extension::files_include()
		// spl_autoload_register( array( $this, 'autoload' ) );
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

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		MainWP_TimeCapsule::render();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-timecapsule-extension' ) ) {
			return;
		}
		new MainWP_TimeCapsule_Extension();
	}

	public function get_child_key() {
		return $this->childKey;
	}

	public function get_child_file() {
		return $this->childFile;
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP WP Time Capsule Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
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

global $mainwpWPTimeCapsuleExtensionActivator;
$mainwpWPTimeCapsuleExtensionActivator = new MainWP_TimeCapsule_Extension_Activator();
