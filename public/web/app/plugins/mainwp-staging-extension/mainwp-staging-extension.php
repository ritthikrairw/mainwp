<?php
/*
Plugin Name: MainWP Staging Extension
Plugin URI: https://mainwp.com
Description: MainWP Staging Extension allows you to create and manage Staging sites directly from your MainWP Dashboard.
Version: 4.0.3
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/category/mainwp-extensions/staging/
*/

// Based on the version 2.1.1

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' ); }

if ( ! defined( 'MAINWP_STAGING_PLUGIN_FILE' ) ) {
	define( 'MAINWP_STAGING_PLUGIN_FILE', __FILE__ );
}

define( 'MAINWP_STAGING_PLUGIN_DIR', dirname( __FILE__ ) );
define( 'MAINWP_STAGING_PLUGIN_URL', plugins_url( '', __FILE__ ) );

class MainWP_Staging_Extension
{
	public static $instance = null;
    protected $staging_sites_info = null;

	static function instance() {
		if ( null == self::$instance ) {
                    self::$instance = new self();
                }
		return self::$instance;
	}

	public function __construct() {
    add_action( 'admin_init', array( &$this, 'admin_init' ) );
    add_action( 'init', array( &$this, 'localization' ) );
    add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'sitestable_getcolumns' ), 10 );
    add_filter( 'mainwp_sitestable_item', array( $this, 'sitestable_item' ), 10 );

    add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
    add_action( 'mainwp_delete_site', array( &$this, 'on_delete_site' ), 10, 1 );
    add_filter( 'mainwp_widgetupdates_actions_top', array( &$this, 'hook_widgetupdates_actions_top' ), 10, 1 );

    add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( $this, 'synced_site' ), 10, 2 );

    MainWP_Staging::instance();

    MainWP_Staging_DB::instance()->install();
	}

    public function admin_init() {
      wp_enqueue_style( 'mainwp-staging-extension', MAINWP_STAGING_PLUGIN_URL . '/css/mainwp-staging.css', array(), '1.3' );
      if ( isset( $_REQUEST['page'] ) && ( 'Extensions-Mainwp-Staging-Extension' == $_REQUEST['page'] || 'ManageSitesStaging' == $_REQUEST['page'] )) {
        wp_enqueue_script( 'mainwp-staging-extension', MAINWP_STAGING_PLUGIN_URL . '/js/mainwp-staging.js', array(), '1.4' );
      }

      wp_localize_script(
        'mainwp-staging-extension', 'mainwp_staging_loc', array(
        'nonce' => wp_create_nonce( '_wpnonce_staging' )
      ) );

      // create staging group if needed
      if (false === get_option('mainwp_stagingsites_group_id', false)) {
        global $mainwp_StagingExtensionActivator;
        $staging_group_id = apply_filters( 'mainwp_addgroup', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $group = 'Staging Sites' );
        if ($staging_group_id) {
          self::update_option('mainwp_stagingsites_group_id', intval($staging_group_id), 'yes');
        }
      }

      if ( isset( $_POST['select_mainwp_staging_options_siteview'] ) ) {
        global $current_user;
        update_user_option($current_user->ID, 'mainwp_staging_options_updates_view', $_POST['select_mainwp_staging_options_siteview']);
      }

      if (isset( $_GET['page'] ) && 'managesites' == $_GET['page'] && !isset( $_GET['id'] ) && !isset( $_GET['do']) && !isset( $_GET['dashboard'])) {
          if ($this->staging_sites_info === null) {
              $this->staging_sites_info = MainWP_Staging_DB::instance()->get_count_stagings_of_sites();
          }
      }
    }

    public static function update_option( $option_name, $option_value, $autoload = 'no' ) {
		$success = add_option( $option_name, $option_value, '', $autoload );

		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}

		return $success;
	}

    public function localization() {
        load_plugin_textdomain( 'mainwp-staging-extension', false,  dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    function managesites_subpage( $subPage ) {
        $subPage[] = array(
            'title'            => __( 'WP Staging','mainwp-staging-extension' ),
            'slug'             => 'Staging',
            'sitetab'          => true,
            'menu_hidden'      => true,
            'callback'         => array( 'MainWP_Staging', 'render' ),
            'on_load_callback' => array('MainWP_Staging', 'on_load_individual_settings_page')
        );
        return $subPage;
    }

    public function sitestable_getcolumns( $columns ) {
    $columns['wp_staging'] = __( 'Staging', 'mainwp-staging-extension' );
        return $columns;
	}

    public function sitestable_item( $item ) {
         // do not add links for staging sites
        if ( $item['is_staging'] )
          return $item;

        $site_id = $item['id'];
        if ($this->staging_sites_info && isset($this->staging_sites_info[$site_id]) && $this->staging_sites_info[$site_id] > 0) {
            $item['wp_staging'] = '<a class="ui mini green basic button" href="admin.php?page=ManageSitesStaging&tab=stagings&id=' . $site_id . '">' . __( 'Manage', 'mainwp-staging-extension') . '</a>';
        } else {
            $item['wp_staging'] = '<a class="ui mini green button" href="admin.php?page=ManageSitesStaging&tab=new&id=' . $site_id . '">' . __( 'Create', 'mainwp-staging-extension') . '</a>';
        }

        return $item;
	}

    function hook_widgetupdates_actions_top( $input ) {
        // added the selection
        if ($input != '')
            return $input;

        $view_stagings = get_user_option('mainwp_staging_options_updates_view') == 'staging' ? true : false;
        $input .= '<form method="post" action="">
                      <select class="ui dropdown" id="mainwp_staging_select_options_siteview" name="select_mainwp_staging_options_siteview">
                        <option value="live" ' . ( $view_stagings ? '' : 'selected' ) . '>' .  esc_html__( 'Production sites', 'mainwp')  . '</option>
                        <option value="staging" ' . ( $view_stagings ? 'selected' : '' ) . '>' .  esc_html__( 'Staging sites', 'mainwp' ) . '</option>
                      </select>
                    </form>
                <script type="text/javascript">
                    jQuery(document).ready(function ($) {
                        jQuery("#mainwp_staging_select_options_siteview").change(function() {
                            jQuery(this).closest("form").submit();
                        });
                    })
            	</script>';

        return $input;
    }

    public function sync_others_data( $data, $pWebsite = null ) {
        // not sync staging with staging site
        if ($pWebsite->is_staging)
            return;
		if ( ! is_array( $data ) ) {
			$data = array(); }
		$data['syncWPStaging'] = 1;
		return $data;
	}

	public function synced_site( $pWebsite, $information = array() ) {
        // do not sync staging site
        if ($pWebsite->is_staging)
            return;

		if ( is_array( $information ) && isset( $information['syncWPStaging'] ) ) {
			$data = $information['syncWPStaging'];

			if ( is_array( $data ) && isset($data['availableClones']) ) {
                $available_clones = $data['availableClones'];
                unset($data['availableClones']);

                if (!is_array($available_clones))
                    $available_clones = array();

                $site_id = $pWebsite->id;
                MainWP_Staging::instance()->sync_staging_site_data( $site_id, $available_clones);
			}
			unset( $information['syncWPStaging'] );
		}
	}


    public function on_delete_site( $website ) {
      if ( $website ) {
        if ($website->is_staging == 0) {
        	MainWP_Staging_DB::instance()->delete_settings_by( 'site_id', $website->id );
          MainWP_Staging_DB::instance()->delete_staging_site( $website->id );
        } else { // the site is staging site
          MainWP_Staging_DB::instance()->delete_staging_site( false, false, $website->id );
        }
      }
	}

}


class MainWP_Staging_Extension_Activator
{
	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-staging-extension';
	protected $product_id = 'MainWP Staging Extension';
	protected $software_version = '4.0.3';

	public function __construct() {

		$this->childFile = __FILE__;

    spl_autoload_register( array( $this, 'autoload' ) );
    register_activation_hook( __FILE__, array($this, 'activate') );
    register_deactivation_hook( __FILE__, array($this, 'deactivate') );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}


  function autoload( $class_name ) {
    $autoload_types = array( 'class' );

    foreach ( $autoload_types as $type ) {
      $autoload_dir  = \trailingslashit( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . $type );

      $autoload_path = sprintf( '%s%s-%s.php', $autoload_dir, $type, strtolower( str_replace( '_', '-', $class_name ) ) );

      if ( file_exists( $autoload_path ) ) {
        require_once( $autoload_path );
      }
    }
  }

	function admin_init() {
    MainWP_Staging::instance()->admin_init();
    MainWP_Staging_Plugin::get_instance()->admin_init();
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
      'plugin'           => __FILE__,
      'api'              => $this->plugin_handle,
      'mainwp'           => true,
      'callback'         => array( &$this, 'settings' ),
      'apiManager'       => true,
      'on_load_callback' => array('MainWP_Staging', 'on_load_settings_page')
    );
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		if ( $this->childEnabled ) {
            MainWP_Staging::instance()->render();
		}
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' )&& ! mainwp_current_user_can( 'extension', 'mainwp-staging-extension' ) ) {
            return;
		}
		new MainWP_Staging_Extension();
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
                    echo '<div class="error"><p>MainWP Staging Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
            }
	}

	public function activate() {
		$options = array(
            'product_id' => $this->product_id,
			'software_version' => $this->software_version,
		);
        do_action( 'mainwp_activate_extention', $this->plugin_handle , $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainwp_StagingExtensionActivator;
$mainwp_StagingExtensionActivator = new MainWP_Staging_Extension_Activator();
