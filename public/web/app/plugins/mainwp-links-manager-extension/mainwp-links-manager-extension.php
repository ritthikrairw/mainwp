<?php
/*
  Plugin Name: MainWP Links Manager Extension
  Plugin URI: https://mainwp.com
  Description: MainWP Links Manager is an Extension that allows you to create, manage and track links in your posts and pages for all your sites right from your MainWP Dashboard.
  Version: 2.1
  Author: MainWP
  Author URI: https://mainwp.com
  Documentation URI: https://mainwp.com/help/category/mainwp-extensions/links-manager/
 */

if ( ! defined( 'MAINWP_LINKS_MANAGER_PLUGIN_FILE' ) ) {
	define( 'MAINWP_LINKS_MANAGER_PLUGIN_FILE', __FILE__ );
}

class Links_Manager_Extension {
	public static $instance = null;
	public $plugin_handle = 'mainwp-links-manager-extension';
	public $settings_page_slug = 'Extensions-Mainwp-Links-Manager-Extension';
	public $control_panel;
	protected $plugin_dir;
	protected $plugin_url;
	private $plugin_slug;
	protected $plugin_admin = '';
	protected $version = 1.3;
	protected $db_version = 1006;
	protected $option;
	protected $keyword_links_specific_posts;
	protected $option_handle = 'mainwp_links_manager_extension';
	protected $modules = array();
	protected $table = array();
	protected $link_temp;
	protected $link_count_temp;
	protected $link_count_each_temp;

	protected $child_sites = array();
	protected $number_childsite = 0;
	protected $number_childsite_synced = 0;
	protected $childKey;


	static function get_instance() {
		if ( null == Links_Manager_Extension::$instance ) {
			Links_Manager_Extension::$instance = new Links_Manager_Extension();
		}
		return Links_Manager_Extension::$instance;
	}

	public function __construct() {
		global $wpdb;
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );
		Keyword_Links_DB::get_instance()->install();
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'after_plugin_row', array( &$this, 'after_plugin_row' ), 10, 3 );
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'wp_ajax_keywordLinksSendClick', array( $this, 'retrieve_click_callback' ) );
		add_action( 'wp_ajax_nopriv_keywordLinksSendClick', array( $this, 'retrieve_click_callback' ) );
		add_action( 'wp_ajax_keyword_links_do_clear_statistic_data', array( $this, 'do_clear_statistic_data' ) );
		add_filter( 'mainwp-sync-extensions-options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );		
		add_action( 'mainwp_applypluginsettings_mainwp-links-manager-extension', array( $this, 'mainwp_apply_plugin_settings' ) );
		
		$this->option = get_option( $this->option_handle );
		$this->keyword_links_specific_posts = get_option( 'mainwp_keyword_links_specific_posts' );
		if ( empty( $this->keyword_links_specific_posts ) ) {
			$this->keyword_links_specific_posts = array(); }

		$childEnabled = apply_filters( 'mainwp-extension-enabled-check', __FILE__ );
		$this->childKey = $childEnabled['key'];
	}

	public static function get_class_name() {

		return __CLASS__;
	}

	public static function get_file_name() {

		return __FILE__;
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin_slug != $plugin_file ) { return $plugin_meta; }
		
		$slug = basename($plugin_file, ".php");
		$api_data = get_option( $slug. '_APIManAdder');		
		if (!is_array($api_data) || !isset($api_data['activated_key']) || $api_data['activated_key'] != 'Activated' || !isset($api_data['api_key']) || empty($api_data['api_key']) ) {
			return $plugin_meta;
		}
		
		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}

	function mainwp_sync_extensions_options($values = array()) {
		$values['mainwp-links-manager-extension'] = array(
				'plugin_slug' => null				
		);
		return $values;
	}	
	
	public function after_plugin_row( $plugin_file, $plugin_data, $status ) {	
		if ( $this->plugin_slug != $plugin_file ) {
			return ;
		}	
		$slug = basename($plugin_file, ".php");
		$api_data = get_option( $slug. '_APIManAdder');
		
		if (!is_array($api_data) || !isset($api_data['activated_key']) || $api_data['activated_key'] != 'Activated'){
			if (!isset($api_data['api_key']) || empty($api_data['api_key'])) {
				?>
				<style type="text/css">
					tr#<?php echo $slug;?> td, tr#<?php echo $slug;?> th{
						box-shadow: none;
					}
				</style>
				<tr class="plugin-update-tr active"><td colspan="3" class="plugin-update colspanchange"><div class="update-message api-deactivate">
				<?php echo (sprintf(__("API not activated check your %sMainWP account%s for updates. For automatic update notification please activate the API.", "mainwp"), '<a href="https://mainwp.com/my-account" target="_blank">', '</a>')); ?>
				</div></td></tr>
				<?php
			}
		}		
	}	
	
	public function init() {
		global $mainwp_control_panel;
		if ( is_object( $mainwp_control_panel ) ) {
			$this->control_panel = $mainwp_control_panel; }

		//$this->redirect_cloak();
	}
	public function admin_init() {
		wp_enqueue_style( $this->plugin_handle . '-admin-css', $this->plugin_url . 'css/admin.css' );
		if ( isset( $_REQUEST['page'] ) && 'Extensions-Mainwp-Links-Manager-Extension' == $_REQUEST['page'] ) {
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( $this->plugin_handle . '-admin-js', $this->plugin_url . 'js/admin.js' );
			wp_enqueue_style( 'thickbox' );
		}
		wp_enqueue_script( 'qtip-1.0.0-rc3-admin-js', $this->plugin_url . 'js/jquery.qtip-1.0.0-rc3.js' );

		add_action( 'publish_bulkpost', array( &$this, 'publish_bulkpost_event' ), 1, 1 );
		add_action( 'publish_bulkpage', array( &$this, 'publish_bulkpost_event' ), 1, 1 );
		add_action( 'save_post', array( &$this, 'publish_bulkpost_event' ), 9 );

		// Hook
		add_action( 'wp_ajax_mainwp_kl_new_link', array( &$this, 'link_form' ) );
		add_action( 'wp_ajax_mainwp_kl_edit_link', array( &$this, 'link_form' ) );
		add_action( 'wp_ajax_keyword_links_save_link', array( &$this, 'keyword_links_save_link' ) );
		add_action( 'wp_ajax_keyword_links_clear_link_child_site', array( &$this, 'keyword_links_clear_link_child_site' ) );
		add_action( 'wp_ajax_keyword_links_save_link_child_site', array( &$this, 'keyword_links_save_link_child_site' ) );
		add_action( 'wp_ajax_keyword_links_delete_link_popup', array( &$this, 'keyword_links_delete_link_popup' ) );
		add_action( 'wp_ajax_keyword_links_delete_link', array( &$this, 'keyword_links_delete_link' ) );
		add_action( 'wp_ajax_keyword_links_delete_group', array( &$this, 'keyword_links_delete_group' ) );
		add_action( 'wp_ajax_keyword_links_load_links', array( &$this, 'keyword_links_load_links' ) );
		add_action( 'wp_ajax_keyword_links_save_configuration', array( &$this, 'keyword_links_save_configuration' ) );
		add_action( 'wp_ajax_keyword_links_save_configuration_child_site', array( &$this, 'keyword_links_save_configuration_child_site' ) );
		add_action( 'wp_ajax_keyword_links_delete_link_child_site', array( &$this, 'keyword_links_delete_link_child_site' ) );
		add_action( 'wp_ajax_keyword_links_import_refresh_data_sites', array( &$this, 'keyword_links_import_refresh_data_sites' ) );
		add_action( 'wp_ajax_keyword_links_preimport_config', array( &$this, 'keyword_links_preimport_config' ) );
		add_action( 'wp_ajax_keyword_links_perform_import_config', array( &$this, 'keyword_links_perform_import_config' ) );
		add_action( 'wp_ajax_keyword_links_preimport_links', array( &$this, 'keyword_links_preimport_links' ) );
		add_action( 'wp_ajax_keyword_links_perform_import_links', array( &$this, 'keyword_links_perform_import_links' ) );
		add_action( 'wp_ajax_keyword_links_save_do_not_links', array( &$this, 'keyword_links_save_do_not_links' ) );
		add_action( 'wp_ajax_keyword_links_save_stats_options', array( &$this, 'keyword_links_save_stats_options' ) );
		add_action( 'wp_ajax_keyword_links_remove_keywords', array( &$this, 'keyword_links_remove_keywords' ) );
		add_action( 'wp_ajax_keyword_links_perform_remove_keywords', array( &$this, 'keyword_links_perform_remove_keywords' ) );

		add_action( 'wp_ajax_keyword_links_clear_donotlink_on_sites', array( &$this, 'keyword_links_clear_donotlink_on_sites' ) );
		add_action( 'wp_ajax_keyword_links_donotlink_pre_apply_to_sites', array( &$this, 'keyword_links_donotlink_pre_apply_to_sites' ) );
		add_action( 'wp_ajax_keyword_links_donotlink_perform_apply_to_sites', array( &$this, 'keyword_links_donotlink_perform_apply_to_sites' ) );
		add_action( 'wp_ajax_keyword_links_stats_perform_apply_to_sites', array( &$this, 'keyword_links_stats_perform_apply_to_sites' ) );
		add_action( 'wp_ajax_mainwp_kl_new_group', array( &$this, 'group_form' ) );
		add_action( 'wp_ajax_mainwp_kl_edit_group', array( &$this, 'group_form' ) );
		add_action( 'wp_ajax_mainwp_kl_save_group', array( &$this, 'save_group' ) );
		//add_action('mainwp-site-synced', array(&$this, 'delete_kl_after_sync'), 99, 1);
		add_action( 'wp_ajax_mainwp_kl_load_group', array( &$this, 'load_group' ) );
		add_action( 'wp_ajax_mainwp_kl_view_statistic', array( &$this, 'view_statistic' ) );
		add_action( 'wp_ajax_mainwp_kl_download_export', array( &$this, 'download_export' ) );
		add_action( 'mainwp-bulkposting-done', array( &$this, 'mainwp_bulkposting_done' ), 10, 3 );

		//for graph
		add_action( 'wp_ajax_mainwp_kl_graph', array( &$this, 'draw_graph' ) );
		//for load a part of statistic
		add_action( 'wp_ajax_mainwp_kl_load_statistic', array( &$this, 'load_statistic' ) );
		$get_post_types = get_post_types(
			array(
			'show_ui' => true,
				), 'objects'
		);
		$post_types = array();
		$allow_post_type = (array) $this->get_option( 'enable_post_type', array( 'bulkpost', 'bulkpage' ) );

		foreach ( $get_post_types as $type => $post_type ) {
			if ( in_array( $type, $allow_post_type ) ) {
				add_meta_box( $this->plugin_handle . '-metabox', __( 'Links Manager Extension Options' ), array( &$this, 'metabox' ), $post_type->name, 'normal', 'high' );
			}
		}
		// MCE
		add_filter( 'mce_external_plugins', array( &$this, 'mce_plugin' ) );
		add_filter( 'mce_buttons', array( &$this, 'mce_button' ) );

		if ( isset( $_POST['kwlImport'] ) && wp_verify_nonce( $_POST['kwlImport'], 'kwlDoImport' ) ) {
			$this->do_import();
		}

	}

	public function admin_menu() {
	}

	function table_name( $suffix ) {
		return Keyword_Links_DB::get_instance()->table_name( $suffix );
	}

	public function settings_page() {
		global $wpdb;
		include $this->plugin_dir . '/includes/option-page.php';
	}

	public function retrieve_click_callback() {

		$data = ( isset( $_POST['data'] ) ) ? unserialize( base64_decode( $_POST['data'] ) ) : null;
		if (
				! isset( $_POST['signature'] ) ||
				! isset( $_POST['timestamp'] ) ||
				! $data ||
				! $this->check_signature( $_SERVER['HTTP_REFERER'], $_POST['signature'], $_POST['timestamp'], $data )
		) {
				die( -1 ); }
		if ( ! is_array( $data ) ) {
				die( -1 ); }
		$website = apply_filters( 'mainwp_getwebsitesbyurl', $_SERVER['HTTP_REFERER'] );
		if ( ! $website && ! preg_match( '/\/$/', $_SERVER['HTTP_REFERER'] ) ) {
				$website = apply_filters( 'mainwp_getwebsitesbyurl', $_SERVER['HTTP_REFERER'].'/' ); }

		if ( ! $website ) {
			die( -1 ); }

		//        if (!Keyword_Links_DB::get_instance()->get_links_by('id', $click['link_id']))
		//            die(-1);

		//$websiteid = $website[0]->id;
		$adds = array();
		foreach ( $data as $click ) {
			$adds[] = Keyword_Links_DB::get_instance()->add_statistic( $click['link_id'], $click['timestamp'], $click['ip'], $click['referer'] );
		}
		echo count( $adds ); // return to clear click data on child site
		exit;
	}

	public function check_signature( $url, $signature, $timestamp, $data ) {

		if ( substr( $url, -1, 1 ) != '/' ) {
			$url .= '/'; }
			$website = apply_filters( 'mainwp_getwebsitesbyurl', $url );
		if ( ! $website && ! preg_match( '/\/$/', $url ) ) {
				$website = apply_filters( 'mainwp_getwebsitesbyurl', $url.'/' ); }
		if ( ! $website ) {
				return false; }
			$createSign = $this->create_signature( $website[0]->pubkey, $timestamp, $data );
			return ( $signature == $createSign );
	}

	public function create_signature( $key, $timestamp, $data ) {

			$datamd5 = md5( $timestamp.base64_encode( serialize( $data ) ) );
			$signature = md5( $key.$datamd5 );
			return $signature;
	}

	// save to option, on slug level to able move others hosting
	public function save_do_not_link_options( $post_id = null ) {
		$disable = $this->get_option( 'disable_add_links_automatically', array() );
		$disable_linking = $this->get_option( 'disable_linking_automatically', array() );
		// update link options of post
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $_POST['mainwp_kl_disable'] && ! in_array( $post->post_name, (array) $disable[ $post->post_type ] ) ) {
				$disable[ $post->post_type ][] = $post->post_name;
			} else { 				$disable[ $post->post_type ] = array_diff( (array) $disable[ $post->post_type ], array( $post->post_name ) ); // delete $post->post_name
			}			if ( $_POST['mainwp_kl_disable_post_link'] && ! in_array( $post->post_name, (array) $disable_linking[ $post->post_type ] ) ) {
				$disable_linking[ $post->post_type ][] = $post->post_name;
			} else { 				$disable_linking[ $post->post_type ] = array_diff( (array) $disable_linking[ $post->post_type ], array( $post->post_name ) ); // delete $post->post_name
			}			// save
			$this->set_option( 'disable_add_links_automatically', $disable );
			$this->set_option( 'disable_linking_automatically', $disable_linking );
			return;
		}
		// update from do_not_link page
		$get_post_types = get_post_types(
			array(
			'show_ui' => true,
				), 'objects'
		);
		foreach ( (array) $get_post_types as $post_type ) {
			$type = $post_type->name;
			$disable[ $type ] = array_unique( array_merge( (array) $disable[ $type ], (array) $_REQUEST['disable_add_links'][ $type ] ) );
			$enable = array_diff( (array) $_REQUEST['diff_slugs'][ $type ], (array) $_REQUEST['disable_add_links'][ $type ] );
			$disable[ $type ] = array_diff( (array) $disable[ $type ], $enable );
			$disable_linking[ $type ] = array_unique( array_merge( (array) $disable_linking[ $type ], (array) $_REQUEST['disable_linking'][ $type ] ) );
			$enable = array_diff( (array) $_REQUEST['diff_slugs'][ $type ], (array) $_REQUEST['disable_linking'][ $type ] );
			$disable_linking[ $type ] = array_diff( (array) $disable_linking[ $type ], $enable );
		}
		$this->set_option( 'disable_add_links_automatically', $disable );
		$this->set_option( 'disable_linking_automatically', $disable_linking );
		wp_redirect( 'admin.php?page=' . $this->settings_page_slug . '&message=4' );
	}

	function mainwp_bulkposting_done( $post, $website, $output ) {

		if ( is_object( $post ) && $post->ID ) {
			$spec_link = get_post_meta( $post->ID, '_mainwp_kwl_specific_link', true );
			if ( $spec_link && is_array( $spec_link ) ) {
				$spec_link = current( $spec_link );
				if ( is_object( $spec_link ) && $spec_link->id && $output->added_id[ $website->id ] ) {
					$this->set_links_posts( $spec_link->id, $website->id, $output->added_id[ $website->id ] );
				}
			}
		}
	}

	public function get_links_posts( $link_id, $wpid = null, $default = null ) {
		if ( empty( $wpid ) ) {
			if ( isset( $this->keyword_links_specific_posts[ $link_id ] ) ) {
				return $this->keyword_links_specific_posts[ $link_id ]; }
		} else {
			if ( isset( $this->keyword_links_specific_posts[ $link_id ][ $wpid ] ) ) {
				return $this->keyword_links_specific_posts[ $link_id ][ $wpid ]; }
		}
		return $default;
	}

	public function set_links_posts( $link_id, $wpid = null, $post_id = null ) {
		if ( empty( $wpid ) && isset( $this->keyword_links_specific_posts[ $link_id ] ) ) {
			unset( $this->keyword_links_specific_posts[ $link_id ][ $wpid ] ); } else if ( empty( $post_id ) && isset( $this->keyword_links_specific_posts[ $link_id ][ $wpid ] ) ) {
			unset( $this->keyword_links_specific_posts[ $link_id ][ $wpid ] ); } else {
				$this->keyword_links_specific_posts[ $link_id ][ $wpid ] = $post_id; }
			return update_option( 'mainwp_keyword_links_specific_posts', $this->keyword_links_specific_posts );
	}

	public function get_option( $key, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ]; }
		return $default;
	}
	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}
	public function unset_option( $key, $value ) {
		unset( $this->option[ $key ] );
		return update_option( $this->option_handle, $this->option );
	}
	public function show_warning() {
		return false;
	}
	public function metabox() {
		global $wpdb, $post;
		include( $this->plugin_dir . '/includes/metabox.php' );
	}
	public function save_post() {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id; }
	}
	public function publish_bulkpost_event( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id; }

		if ( ! isset( $_POST['kwl_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['kwl_metabox_nonce'], $this->plugin_handle . '-metabox' ) ) {
			//            if ( ! get_post_meta($post_id, '__kl_mainwp_keyword', true) )
			//                    update_post_meta($post_id, '_mainwp_kl_post_keyword', '');
			return;
		}
		$disable = intval( $_POST['mainwp_kl_disable'] );
		$disable_post_link = intval( $_POST['mainwp_kl_disable_post_link'] );
		update_post_meta( $post_id, '_mainwp_kl_disable', $disable );
		update_post_meta( $post_id, '_mainwp_kl_disable_post_link', $disable_post_link );
		$this->save_do_not_link_options( $post_id );
		$this->save_specific_link();

		//        $custom = get_post_custom($post_id);
		//        print_r($custom);
		//        exit();
	}

	// save link from post page (add/edit post)
	public function save_specific_link() {
		global $wpdb, $post;
		$ret = array(
			'success' => false,
			'field_error' => array(),
			'error' => '',
		);
		if ( ! wp_verify_nonce( $_POST['kwl_metabox_nonce'], $this->plugin_handle . '-metabox' ) ) {
			// cann''t verify save action from post page (add/edit)
			return;
		}
		$this->save_not_allowed_keywords_on_post();
		// If we have link id, it means we do edit
		if ( isset( $_POST['_mainwp_kwl_meta_specific_link_id'] ) ) {
			$specific_id = intval( $_POST['_mainwp_kwl_meta_specific_link_id'] );
			if ( $specific_id ) {
				$link = $wpdb->get_row( sprintf( "SELECT * FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_link' ), $specific_id ) ); }
		}
		// new link
		// Validate link name
		if ( isset( $_POST['mainwp_kl_link_name'] ) && ! empty( $_POST['mainwp_kl_link_name'] ) ) {
			$link_name = sanitize_text_field( $_POST['mainwp_kl_link_name'] );
			if ( empty( $link_name ) ) {
				$ret['field_error']['link_name'] = __( 'This field has unallowed character(s)' ); }
			if ( ! isset( $link ) && $link_name && $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s` WHERE `name`='%s'", $this->table_name( 'keyword_links_link' ), $link_name ) ) > 0 ) {
				$ret['field_error']['link_name'] = __( 'Link name exists' ); }
		} else {
			$ret['field_error']['link_name'] = __( 'This field is required' );
		}
		// Validate link destination url
		if ( isset( $_POST['mainwp_kl_link_destination_url'] ) && ! empty( $_POST['mainwp_kl_link_destination_url'] ) ) {
			$link_destination_url = esc_url_raw( $_POST['mainwp_kl_link_destination_url'] );
			if ( empty( $link_destination_url ) ) {
				$ret['field_error']['link_destination_url'] = __( 'Please fill with a valid URL' ); }
		} else {
			$ret['field_error']['link_destination_url'] = __( 'This field is required' );
		}
		// Validate link cloak path
		if ( isset( $_POST['link_cloak_path'] ) && ! empty( $_POST['link_cloak_path'] ) ) {
			$link_cloak_path = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $_POST['link_cloak_path'] );
			if ( empty( $link_cloak_path ) ) {
				$ret['field_error']['link_cloak_path'] = __( 'This field has unallowed character(s)' ); }
			if ( ! isset( $link ) && $link_cloak_path && $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s` WHERE `cloak_path`='%s'", $this->table_name( 'keyword_links_link' ), $link_cloak_path ) ) > 0 ) {
				$ret['field_error']['link_cloak_path'] = __( 'The cloak URL exists' ); }
		} else {
			$link_cloak_path = '';
		}
		// Validate link keywords
		if ( isset( $_POST['mainwp_kl_post_keyword'] ) && ! empty( $_POST['mainwp_kl_post_keyword'] ) ) {
			$link_keyword = esc_html( $_POST['mainwp_kl_post_keyword'] );
			if ( empty( $link_keyword ) ) {
				$ret['field_error']['link_keyword'] = __( 'This field has unallowed character(s)' ); }
		} else {
			$ret['field_error']['link_keyword'] = __( 'This field is required' );
		}
		// Validate link no follow
		if ( isset( $_POST['mainwp_kl_link_nofollow'] ) ) {
			if ( '-1' == $_POST['mainwp_kl_link_nofollow'] ) {
				$link_rel = '-1'; } else {
				$link_rel = ('1' == $_POST['mainwp_kl_link_nofollow']) ? 'nofollow' : ''; }
		} else {
			$link_rel = '-1';
		}
		// Validate link new tab
		if ( isset( $_POST['mainwp_kl_link_newtab'] ) ) {
			if ( '-1' == $_POST['mainwp_kl_link_newtab'] ) {
				$link_target = '-1'; } else {
				$link_target = ('1' == $_POST['mainwp_kl_link_newtab']) ? '_blank' : ''; }
		} else {
			$link_target = '-1';
		}
		// Validate link class
		if ( isset( $_POST['mainwp_kl_link_class'] ) && ! empty( $_POST['mainwp_kl_link_class'] ) ) {
			$link_class = sanitize_text_field( esc_html( $_POST['mainwp_kl_link_class'] ) );
			if ( empty( $link_class ) ) {
				$ret['field_error']['link_class'] = __( 'This field has unallowed character(s)' ); }
		} else {
			$link_class = '';
		}

		$exact_match = $case_sensitive = 0;
		if ( isset( $_POST['mainwp_kl_exact_match'] ) ) {
			$exact_match = intval( $_POST['mainwp_kl_exact_match'] );
		}
		if ( isset( $_POST['mainwp_kl_case_sensitive'] ) ) {
			$case_sensitive = intval( $_POST['mainwp_kl_case_sensitive'] );
		}
		// Check for error
		if ( empty( $ret['error'] ) && count( $ret['field_error'] ) == 0 ) {
			$fields = array(
				'name' => $link_name,
				'destination_url' => $link_destination_url,
				'cloak_path' => $link_cloak_path,
				'keyword' => $link_keyword,
				'link_rel' => $link_rel,
				'link_target' => $link_target,
				'link_class' => $link_class,
				'exact_match' => $exact_match,
				'case_sensitive' => $case_sensitive,
				// do not need to save site and groups for specific links,
				// but site and post data will be saved to $keyword_links_specific_posts variable
				'sites' => '',
				'groups' => '',
				'type' => 2,
			);
			if ( ! isset( $link ) ) {
				if ( $wpdb->insert( $this->table_name( 'keyword_links_link' ), $fields ) ) {
					$specific_id = $wpdb->insert_id;
					// no need to change group of link here
					if ( ! empty( $specific_id ) ) {
						update_post_meta( $post->ID, '_mainwp_kwl_meta_specific_link_id', $specific_id ); }
					$ret['success'] = true;
				}
			} else {
				$wpdb->update( $this->table_name( 'keyword_links_link' ), $fields, array( 'id' => $link->id ) );
				// no need to change group relationship here
				$ret['success'] = true;
			}
			if ( ! empty( $specific_id ) ) {
				$specific_link = Keyword_Links_DB::get_instance()->get_links_by( 'id', $specific_id );
				if ( ! empty( $specific_link ) ) {
					update_post_meta( $post->ID, '_mainwp_kwl_specific_link', array( $specific_id => $specific_link ) );
				}
			}
		}
	}
	public function save_not_allowed_keywords_on_post() {
		global $wpdb, $post;
		if ( ! isset( $_POST['kwl_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['kwl_metabox_nonce'], $this->plugin_handle . '-metabox' ) ) {
			// cann''t verify save action from post page (add/edit)
			return;
		}
		$not_allowed[] = array();
		if ( isset( $_POST['check_allowed_keywords'] ) && is_array( $_POST['check_allowed_keywords'] ) && count( $_POST['check_allowed_keywords'] ) > 0 ) {
			for ( $i = 0; $i < count( $_POST['check_allowed_keywords'] ); $i++ ) {
				$val_i = $_POST['check_allowed_keywords'][ $i ];
				$not_allowed[] = array( 'keyword' => $_POST['allowed_keywords'][ $val_i ], 'link' => $_POST['allowed_links'][ $val_i ] );
			}
		}
		if ( isset( $_POST['check_not_allowed_keywords'] ) && is_array( $_POST['check_not_allowed_keywords'] ) && count( $_POST['check_not_allowed_keywords'] ) > 0 ) {
			for ( $i = 0; $i < count( $_POST['check_not_allowed_keywords'] ); $i++ ) {
				$val_i = $_POST['check_not_allowed_keywords'][ $i ];
				$not_allowed[] = array( 'keyword' => $_POST['not_allowed_keywords'][ $val_i ], 'link' => $_POST['not_allowed_links'][ $val_i ] );
			}
		}
		update_post_meta( $post->ID, 'mainwp_kl_not_allowed_keywords_on_this_post', $not_allowed );
	}
	public function download_export() {
		global $wpdb;

		$data = ( isset( $_GET['data'] ) ) ? $_GET['data'] : '';
		if ( ! $data ) {
			return; }
		$datas = explode( ',', $data );
		$filename = 'mainwp_links_manager_export_' . date( 'Y_m_d_H_i_s' ) . '.txt';
		include( $this->plugin_dir . '/includes/download-export.php' );
		exit;
	}
	public function do_import() {
		global $wpdb;

		if ( isset( $_FILES['import_file'] ) and '' != $_FILES['import_file']['tmp_name'] ) {
			$data_file = file_get_contents( $_FILES['import_file']['tmp_name'] );
			if ( strpos( $data_file, '[easy_reading_format]' ) !== false ) {
				if ( strpos( $data_file, '[link][begin]' ) !== false ) {
					$pos1 = strpos( $data_file, '[keyword][begin]' ) + strlen( '[keyword][begin]' );
					$pos2 = strpos( $data_file, '[keyword][end]' );
					$keyword = trim( substr( $data_file, $pos1, $pos2 - $pos1 ) );
					$pos1 = strpos( $data_file, '[link group][begin]' ) + strlen( '[link group][begin]' );
					$pos2 = strpos( $data_file, '[link group][end]' );
					$group = trim( substr( $data_file, $pos1, $pos2 - $pos1 ) );
					$pos1 = strpos( $data_file, '[group_relation][begin]' ) + strlen( '[group_relation][begin]' );
					$pos2 = strpos( $data_file, '[group_relation][end]' );
					$group_relation = trim( substr( $data_file, $pos1, $pos2 - $pos1 ) );
					$keyword_lines = explode( "\n", $keyword );
					//print_r($keyword_lines);
					// skip header line
					for ( $i = 1; $i < count( $keyword_lines ); $i++ ) {
						$line = trim( $keyword_lines[ $i ] );
						if ( $line ) {
							$csv = str_getcsv( $line, ',', '"' );
							$datas['link']['keyword'][ $i ]['id'] = $csv[0];
							$datas['link']['keyword'][ $i ]['name'] = $csv[1];
							$datas['link']['keyword'][ $i ]['destination_url'] = $csv[2];
							$datas['link']['keyword'][ $i ]['cloak_path'] = $csv[3];
							$datas['link']['keyword'][ $i ]['keyword'] = $csv[4];
							$datas['link']['keyword'][ $i ]['link_target'] = $csv[5];
							$datas['link']['keyword'][ $i ]['link_rel'] = $csv[6];
							$datas['link']['keyword'][ $i ]['link_class'] = $csv[7];
							$datas['link']['keyword'][ $i ]['sites'] = $csv[8];
							$datas['link']['keyword'][ $i ]['groups'] = $csv[9];
							$datas['link']['keyword'][ $i ]['type'] = $csv[10];
						}
					}
					$group_lines = explode( "\n", $group );
					//print_r($group_lines);
					// skip header line
					for ( $i = 1; $i < count( $group_lines ); $i++ ) {
						$line = trim( $group_lines[ $i ] );
						if ( $line ) {
							$csv = str_getcsv( $line, ',', '"' );
							$datas['link']['group'][ $i ]['id'] = $csv[0];
							$datas['link']['group'][ $i ]['name'] = $csv[1];
						}
					}
					$group_relation_lines = explode( "\n", $group_relation );
					//print_r($group_relation_lines);
					// skip header line
					for ( $i = 1; $i < count( $group_relation_lines ); $i++ ) {
						$line = trim( $group_relation_lines[ $i ] );
						if ( $line ) {
							$csv = str_getcsv( $line, ',', '"' );
							$datas['link']['group_relation'][ $i ]['id'] = $csv[0];
							$datas['link']['group_relation'][ $i ]['group_id'] = $csv[1];
							$datas['link']['group_relation'][ $i ]['link_id'] = $csv[2];
						}
					}
				};
				if ( strpos( $data_file, '[configuration][begin]' ) !== false ) {
					$pos1 = strpos( $data_file, '[configuration][begin]' ) + strlen( '[configuration][begin]' );
					$pos2 = strpos( $data_file, '[configuration][end]' );
					$config = trim( substr( $data_file, $pos1, $pos2 - $pos1 ) );
					$datas['config'] = json_decode( $config, true );
				};
				if ( strpos( $data_file, '[links_posts][begin]' ) !== false ) {
					$pos1 = strpos( $data_file, '[links_posts][begin]' ) + strlen( '[links_posts][begin]' );
					$pos2 = strpos( $data_file, '[links_posts][end]' );
					$links_posts = trim( substr( $data_file, $pos1, $pos2 - $pos1 ) );
					$datas['links_posts'] = json_decode( $links_posts, true );
				};
				if ( strpos( $data_file, '[statistic][begin]' ) !== false ) {
					$pos1 = strpos( $data_file, '[statistic][begin]' ) + strlen( '[statistic][begin]' );
					$pos2 = strpos( $data_file, '[statistic][end]' );
					$statistic = trim( substr( $data_file, $pos1, $pos2 - $pos1 ) );
					$datas['statistic'] = json_decode( $statistic, true );
				};
			} else {
				$datas = json_decode( $data_file, true );
			}
			$count_import = 0;
			$redirect = '';
			if ( is_array( $datas ) && count( $datas ) > 0 ) {
				foreach ( $datas as $data => $data_value ) {
					switch ( $data ) {
						case 'config':
							$this->option = $data_value;
							$this->option['saved_time'] = current_time( 'timestamp' );
							if ( update_option( $this->option_handle, $this->option ) ) {
									$count_import++; }
							break;
						case 'links_posts':
							$this->keyword_links_specific_posts = $data_value;
							if ( update_option( 'mainwp_keyword_links_specific_posts', $this->keyword_links_specific_posts ) ) {
									$count_import++; }
							break;
						case 'link':
							$table = $this->table_name( 'keyword_links_link' );
							foreach ( (array) $data_value['keyword'] as $keyword ) {

									$item = Keyword_Links_DB::get_instance()->get_data_row( $table, 'id', $keyword['id'] );
								if ( empty( $item ) ) {
									if ( $wpdb->insert( $table, $keyword ) ) {
										$count_import++; }
								}
							}

							$table = $this->table_name( 'keyword_links_group' );
							foreach ( (array) $data_value['group'] as $group ) {
								$item = Keyword_Links_DB::get_instance()->get_data_row( $table, 'id', $group['id'] );
								if ( empty( $item ) ) {
									if ( $wpdb->insert( $table, $group ) ) {
										$count_import++; }
								}
							}
							$table = $this->table_name( 'keyword_links_link_group' );
							foreach ( (array) $data_value['group_relation'] as $relation ) {
								$item = Keyword_Links_DB::get_instance()->get_data_row( $table, 'id', $relation['id'] );
								if ( empty( $item ) ) {
									if ( $wpdb->insert( $table, $relation ) ) {
										$count_import++; }
								}
							}
							break;
						case 'statistic':
							//$keywords = array();
							$table = $this->table_name( 'keyword_links_statistic' );
							foreach ( (array) $data_value as $statistic ) {
								$stat['id'] = $statistic['id'];
								$stat['link_id'] = $statistic['link_id'];
								$stat['date'] = $statistic['date'];
								$stat['type'] = $statistic['type'];
								$stat['ip'] = $statistic['ip'];
								$stat['referer'] = $statistic['referer'];
								$item = Keyword_Links_DB::get_instance()->get_data_row( $table, 'id', $statistic['id'] );
								if ( empty( $item ) ) {
									if ( $wpdb->insert( $table, $stat ) ) {
										$count_import++; }
								}
							}
							break;
					}
				}
				if ( $count_import > 0 ) {
					$redirect = '&message=2&kwlImportChild=' . wp_create_nonce( 'kwlImportChild' ); }
			}
		}

		if ( empty( $redirect ) ) {
			$redirect = '&message=-2'; }

		wp_redirect( 'admin.php?page=' . $this->settings_page_slug . $redirect );
		exit;
	}

	public function do_clear_statistic_data() {
		global $wpdb;
		if ( ! wp_verify_nonce( $_POST['nonce'], 'kwlDoClear' ) ) {
			  exit; }
		//clear statistic
		if ( $wpdb->query( sprintf( 'TRUNCATE table `%s`', $this->table_name( 'keyword_links_statistic' ) ) ) ) {
			echo 'SUCCESS'; } else {
			echo 'FAIL'; }
			exit;
	}
	public function mce_plugin( $plugins ) {
		global $post;
		if ( $post->post_type == 'bulkpost' || $post->post_type == 'bulkpage' ) {
			$plugins['mainwpkeywordlink'] = $this->plugin_url . 'js/mce/editor_plugin.php';
		}
		return $plugins;
	}
	public function mce_button( $buttons ) {
		/* $get_post_types = get_post_types(
          array(
          'show_ui' => true
          ),
          'objects'
          );
          foreach ( $get_post_types as $type => $post_type )
          {
          if($type == 'bulkpost' || $type == 'bulkpage')
          {
          /* $buttons[] = 'mainwpkeywordlink_select';
          }
          } */
		$buttons[] = 'mainwpkeywordlink_select';
		return $buttons;
	}
	public function load_mce_select() {
		global $wpdb;
		$links = $wpdb->get_results( sprintf( 'SELECT * FROM `%s`', $this->table_name( 'keyword_links_link' ) ) );
		foreach ( (array) $links as $link ) :
			if ( ! $link ) {
				continue; }
			$group = $wpdb->get_row( sprintf( "SELECT g.* FROM `%s` g JOIN `%s` gr ON g.id=gr.group_id WHERE gr.link_id='%d'", $this->table_name( 'keyword_links_group' ), $this->table_name( 'keyword_links_link_group' ), $link->id ) );
			if ( $link->link_target != '-1' ) {
				$target = $link->link_target; } else {
				$target = $this->get_option( 'default_link_newtab' ) ? '_blank' : ''; }
				if ( $link->link_rel != '-1' ) {
					$rel = $link->link_rel; } else {
					$rel = $this->get_option( 'default_link_nofollow' ) ? 'nofollow' : ''; }
					if ( $link->link_class != '' ) {
						$class = $link->link_class; } else {
						$class = $this->get_option( 'default_link_class' ); }

						$redirection_folder = $this->get_option( 'redirection_folder', '' );
						//            if (empty($redirection_folder))
						//                $redirection_folder = "goto";
						if ( '' != $redirection_folder ) {
							$redirection_folder = '/'.$redirection_folder; }

						$url = $link->cloak_path ? get_option( 'home' ) . $redirection_folder. '/' . $link->cloak_path : $link->destination_url;
			?>
            select.add('<?php echo $link->name ?>', {
            'href': '<?php echo $url; ?>',
            'target': '<?php echo $target ?>',
            'rel': '<?php echo $rel ?>',
            'class': '<?php echo $class ?>',
            'name' : 'kwl'
            });
            <?php
		endforeach;
	}

	public function keyword_links_load_links( $exit = true ) {
		global $wpdb;
		include( $this->plugin_dir . '/includes/link-list.php' );
		exit;
	}


	public function load_group( $exit = true ) {
		global $wpdb;
		include( $this->plugin_dir . '/includes/group-list.php' );
		exit;
	}

	public function link_form() {
		global $wpdb;
		include( $this->plugin_dir . '/includes/link-form.php' );
		exit;
	}

	public function draw_graph() {
		global $wpdb;
		include( $this->plugin_dir . '/includes/draw-graph.php' );
		exit;
	}

	public function group_form() {
		global $wpdb;
		include( $this->plugin_dir . '/includes/group-form.php' );
		exit;
	}

	public function keyword_links_delete_link_popup() {
		global $wpdb;
		include( $this->plugin_dir . '/includes/link-delete-form.php' );
		exit;
	}

	public function keyword_links_delete_link() {
		global $wpdb;
		$ret = array( 'success' => false );

		$link_id = intval( $_REQUEST['link_id'] );
		if ( intval( $link_id ) > 0 ) {
			$this->set_links_posts( $link_id ); // delete link post if existed
			if ( $wpdb->query( sprintf( "DELETE FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_link' ), $link_id ) ) ) {
				$wpdb->query( sprintf( "DELETE FROM `%s` WHERE `link_id`='%d'", $this->table_name( 'keyword_links_link_group' ), $link_id ) );
				$wpdb->query( sprintf( "DELETE FROM `%s` WHERE `link_id`='%d'", $this->table_name( 'keyword_links_statistic' ), $link_id ) );
				$ret['success'] = true;
			}
		}
		echo json_encode( $ret );
		exit;
	}


	public function keyword_links_delete_link_child_site() {

		$result['success'] = false;
		if ( empty( $_POST['link_id'] ) ) {
			$result['message'] = __( 'ERROR: Empty link id.' );
			die( json_encode( $result ) );
		}

		$link = Keyword_Links_DB::get_instance()->get_links_by( 'id', $_POST['link_id'] );
		if ( empty( $link ) ) {
			$result['message'] = __( 'ERROR: Link empty.' );
			die( json_encode( $result ) );
		}

		$sites = ! empty( $link->sites ) ? explode( ';', $link->sites ) : null;
		$groups = ! empty( $link->groups ) ? explode( ';', $link->groups ) : null;
		$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, $sites, $groups );

		// get sites for specific link
		if ( $link->type == 2 || $link->type == 3 ) {
			$site_posts = $this->get_links_posts( $link->id );
			if ( is_array( $site_posts ) && count( $site_posts ) > 0 ) {
				foreach ( $site_posts as $siteid => $post_id ) {
					$dbwebsites_post = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, array( $siteid ), null );
					if ( $dbwebsites_post ) {
						$dbwebsites[ $siteid ] = $dbwebsites_post[ $siteid ]; }
				}
			}
		}

		//print_r($dbwebsites);
		$output = new stdClass();
		$output->ok = array();
		$output->errors = array();
		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'link_id' => intval( $_POST['link_id'] ),
				'delete_permanent' => $_POST['delete_permanent'],
			);
			$post_data['action'] = 'delete_link';
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsites, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'delete_link_handler' ), $output );
			$message = '';
			$success = true;
			foreach ( $dbwebsites as $website ) {
				if ( $output->status[ $website->id ] != 'success' ) {
					$success = false; }
				$message .= stripslashes( $website->name ) . ': ' . $output->infor[ $website->id ] . '<br />';
			}
			$result['success'] = $success;
			$result['message'] = $message;
		} else {
			$result['success'] = true;
			$result['message'] = __( 'No sites to delete link.' );
		}
		 die( json_encode( $result ) );
	}

	public function keyword_links_preimport_config() {

		$websites = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, null );
		$output = array();
		if ( count( $websites ) > 0 ) {
			$output['sites'] = $websites;
		} else {
			$output['error'] = __( 'These are not child sites.' ); }

		die( json_encode( $output ) );
	}

	function keyword_links_import_refresh_data_sites() {

		$websites = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, null );
		$dbwebsites = array();
		if ( $websites ) {
			$sites = array();
			foreach ( $websites as $website ) {
				if ( $website ) {
					$sites[] = $website['id']; }
			}
			$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, $sites, null );
		}

		//print_r($dbwebsites);
		$message = '';
		$result = array();
		$output = new stdClass();
		$output->ok = array();
		$output->errors = array();
		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array( 'clear_all' => true );
			$post_data['action'] = 'refresh_data';
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsites, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'refresh_data_handler' ), $output );
			foreach ( $dbwebsites as $website ) {
				$message .= $website->name . ': ' . $output->infor[ $website->id ] . '<br />';
			}
			$result['message'] = $message;
		} else {
			$result['success'] = true;
			$result['message'] = __( 'No child sites to import.' );
		}
		 die( json_encode( $result ) );
	}

	public function keyword_links_perform_import_config() {

		$siteid = $_POST['siteId'];
		if ( empty( $siteid ) ) {
			die( '' ); }
		Keyword_Links_Utility::end_session();

		$output = new stdClass();
		$output->ok = array();
		$output->status = array();
		$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, array( $siteid ), null );
		if ( $dbwebsites ) {
			$post_data = array(
			  'replace_max' => $this->get_option( 'replace_max' ),
			  'replace_max_keyword' => $this->get_option( 'replace_max_keyword' ),
			  'default_link_nofollow' => $this->get_option( 'default_link_nofollow' ),
			  'default_link_newtab' => $this->get_option( 'default_link_newtab' ),
			  'replace_keyword_in_h_tag' => $this->get_option( 'replace_keyword_in_h_tag' ),
			  'default_link_class' => $this->get_option( 'default_link_class' ),
			  'post_match_title' => $this->get_option( 'post_match_title' ),
			  'post_same_category' => $this->get_option( 'post_same_category' ),
			  'redirection_folder' => $this->get_option( 'redirection_folder' ),
			  'enable_post_type' => $this->get_option( 'enable_post_type' ),
			  'enable_post_type_link' => $this->get_option( 'enable_post_type_link' ),
			);

			$enable_post_type = $this->get_option( 'enable_post_type' );

			$enable_post_type_sites = array();
			if ( is_array( $enable_post_type ) ) {
				foreach ( $enable_post_type as $post_type ) {
					if ( 'bulkpost' == $post_type ) {
						$enable_post_type_sites[] = 'post';
					} else if ( 'bulkpage' == $post_type ) {
						$enable_post_type_sites[] = 'page';
					} else {
						$enable_post_type_sites[] = $post_type; }
				}
			}
			$post_data['enable_post_type'] = $enable_post_type_sites;

			$enable_post_type_link = $this->get_option( 'enable_post_type_link' );
			$enable_post_type_link_sites = array();
			if ( is_array( $enable_post_type_link ) ) {
				foreach ( $enable_post_type_link as $post_type ) {
					if ( 'bulkpost' == $post_type ) {
						$enable_post_type_link_sites[] = 'post';
					} else if ( 'bulkpage' == $post_type ) {
						$enable_post_type_link_sites[] = 'page';
					} else {
						$enable_post_type_link_sites[] = $post_type; }
				}
			}
			$post_data['enable_post_type_link'] = $enable_post_type_link_sites;

			$post_data['action'] = 'update_config';
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsites, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'import_config_handler' ), $output );
		} else {
			$output->error = __( 'Error: Site data.' ); }
		die( json_encode( $output ) );
	}

	public function keyword_links_preimport_links() {

		$links = Keyword_Links_DB::get_instance()->get_links_by( 'all' );
		$output = array();
		if ( is_array( $links ) && count( $links ) > 0 ) {
			foreach ( $links as $link ) {
				$link_sites = array();
				$sites = $link->sites;
				$groups = $link->groups;
				if ( '' != $sites ) {
					$sites = explode( ';', $sites );
					foreach ( $sites as $id ) {
						$id = $id;
						$website = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, $id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
							if ( $website ) {
								$link_sites[ $id ]['name'] = $website['name'];
							}
						}
					}
				}
				if ( '' != $groups ) {
					$groups = explode( ';', $groups );
					foreach ( $groups as $id ) {
						$websites = apply_filters( 'mainwp-getgroups', __FILE__, $this->childKey, $id );
						if ( $websites ) {
							foreach ( $websites as $website ) {
								$link_sites[ $website->id ]['name'] = $website['name'];
							}
						}
					}
				}

				// get sites for specific link
				if ( $link->type == 2 || $link->type == 3 ) {
					$site_posts = $this->get_links_posts( $link->id );
					if ( is_array( $site_posts ) && count( $site_posts ) > 0 ) {
						foreach ( $site_posts as $siteid => $post_id ) {
							$website = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, $siteid );
							if ( $website && is_array( $website ) ) {
								$website = current( $website );
								if ( $website ) {
									$link_sites[ $website['id'] ]['name'] = $website['name']; }
							}
						}
					}
				}

				$output['links'][ $link->id ]['name'] = $link->name;
				$output['links'][ $link->id ]['sites'] = $link_sites;
			}
		}

		die( json_encode( $output ) );
	}

	public function keyword_links_perform_import_links() {

		$siteid = $_POST['siteId'];
		$linkid = $_POST['linkId'];
		if ( empty( $siteid ) || empty( $linkid ) ) {
			die( '' ); }
		Keyword_Links_Utility::end_session();

		$output = new stdClass();
		$output->ok = array();
		$output->status = array();
		$dbwebsites = array();

		$link = Keyword_Links_DB::get_instance()->get_links_by( 'id', $linkid );
		$dbwebsite = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, array( $siteid ), null );
		if ( $dbwebsite && $link ) {
			$post_data = array(
					'id' => $link->id,
					'name' => $link->name,
					'destination_url' => $link->destination_url,
					'cloak_path' => $link->cloak_path,
					'keyword' => $link->keyword,
					'link_target' => $link->link_target,
					'link_rel' => $link->link_rel,
					'link_class' => $link->link_class,
					'type' => $link->type,
				);

			if ( $link->type == 2 || $link->type == 3 ) {
				$post_id = $this->get_links_posts( $link->id, $siteid, 0 );
				if ( $post_id ) {
					$post_data['post_id'] = $post_id; }
			}
			$post_data['action'] = 'add_link';
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsite, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'import_link_handler' ), $output );
		} else if ( empty( $dbwebsite ) ) {
			$output->error = __( 'Error: Site data.' ); } else if ( empty( $link ) ) {
			$output->error = __( 'Error: Link data.' ); }

			die( json_encode( $output ) );
	}

	public function keyword_links_save_do_not_links() {
		$donotlinks = $_POST['donotlinks'];
		$donotlinks = explode( "\n", $donotlinks );

		$links = array();
		if ( is_array( $donotlinks ) ) {
			foreach ( $donotlinks as $link ) {
				$link = trim( $link );
				if ( ! empty( $link ) ) {
					$links[] = $link; }
			}
		} else {
			$links[] = trim( $donotlinks );
		}

		if ( is_array( $links ) ) {
			$txt_donotlinks = implode( "\n", $links );
		} else {
			$txt_donotlinks = $links; }

		$return = new stdClass();
		$return->success = false;
		if ( $this->set_option( 'keyword_links_do_not_links', $txt_donotlinks ) ) {
			$return->success = true;

			$site_blocks = $fullurl_blocks = $partial_blocks = array();
			foreach ( $links as $url_path ) {
				if ( substr( $url_path, -1 ) == '/' ) {
					$url_path = substr( $url_path, 0, strlen( $url_path ) - 1 ); }

				if ( preg_match( '/^(https?:\/\/)[^\/]*$/is', $url_path ) ) {
					if ( $website = apply_filters( 'mainwp_getwebsitesbyurl', $url_path ) ) {
						$site_blocks[ $url_path ] = array( 'siteid' => $website[0]->id );
					}
				} else if ( preg_match( '/^(https?:\/\/)([^\/]*)\/(.+)$/is', $url_path, $matches ) ) {
					$site_url = $matches[1] . $matches[2];
					$path = $matches[3];
					if ( $website = apply_filters( 'mainwp_getwebsitesbyurl', $site_url ) ) {
						$fullurl_blocks[ $url_path ] = array( 'siteid' => $website[0]->id, 'path' => $path );
					}
				} else {
					$url_path = trim( $url_path, '/' );
					$partial_blocks[] = $url_path;
				}
			}

			$this->set_option( 'keyword_links_site_blocks', $site_blocks );
			$this->set_option( 'keyword_links_full_url_blocks', $fullurl_blocks );
			$this->set_option( 'keyword_links_partial_blocks', $partial_blocks );
		}

		die( json_encode( $return ) );
	}

	public function keyword_links_remove_keywords() {

		global $keywordLinksExtensionActivator;
		$removeKeywords = $_POST['removeKeywords'];
		$removeAll = $_POST['removeAll'];

		$selected_sites = isset( $_POST['sites'] ) ? $_POST['sites'] : array();
		$selected_groups = isset( $_POST['groups'] ) ? $_POST['groups'] : array();

		$dbwebsites = apply_filters( 'mainwp-getdbsites', $keywordLinksExtensionActivator->get_child_file(), $keywordLinksExtensionActivator->get_child_key(), $selected_sites, $selected_groups );

		if ( ! is_array( $dbwebsites ) || count( $dbwebsites ) == 0 ) {
			die( 'NOSITE' );
		}

		$url_loader = plugins_url( 'img/loader.gif', __FILE__ );
		foreach ( $dbwebsites as $website ) {
			echo '<div><strong>' . stripslashes( $website->name ) .'</strong>: ';
			echo '<span class="kwlProcessSiteItem" siteid="' . $website->id . '" status="queue"><span class="status">' . __( 'Queued' ) . '</span><span class="progress hidden"><img src="' . $url_loader .  '"> ' . __( 'running ...' ) . '</span></span>';
			echo '</div><br />';
		}
		?>        
        <input type="hidden" name="mainwp_kwl_removeKeywords" id="mainwp_kwl_removeKeywords" value="<?php echo $removeKeywords; ?>">    
        <input type="hidden" name="mainwp_kwl_removeAll" id="mainwp_kwl_removeAll" value="<?php echo $removeAll; ?>">
        <div id="mainwp_kwl_remove_keywords_ajax_message_zone" class="mainwp_info-box-yellow hidden"></div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                kwl_currentSitesThreads = 0;
                kwl_finishedSitesThreads = 0;
                kwl_totalSitesThreads = jQuery('.kwlProcessSiteItem[status="queue"]').length;
                keyword_links_remove_keywords_start_next();
            })
        </script>
        <?php
		die();
	}

	public function keyword_links_perform_remove_keywords() {
		global $keywordLinksExtensionActivator;
		$site_id = $_POST['siteId'];
		$removeKeywords = $_POST['removeKeywords'];
		$removeSettings = $_POST['removeSettings'];

		if ( empty( $site_id ) ) {
			die( json_encode( array( 'error' => __( 'Site ID empty' ) ) ) ); }

		$post_data = array(
		'action' => 'remove_keywords',
							'keywords' => base64_encode( serialize( $removeKeywords ) ),
							'removeSettings' => $removeSettings,
							);

		$information = apply_filters( 'mainwp_fetchurlauthed', $keywordLinksExtensionActivator->get_child_file(), $keywordLinksExtensionActivator->get_child_key(), $site_id, 'keyword_links_action', $post_data );

		die( json_encode( $information ) );
	}

	function keyword_links_save_stats_options() {
			$enable_stats = intval( $_POST['enablestats'] );
		if ( ! $this->set_option( 'mainwp_links_manager_enable_stats', $enable_stats ) ) {
				die( json_encode( 'FAIL' ) ); }
	}



	public function handle_stats_option() {
	?>
		<h3><?php echo ($this->get_option( 'mainwp_links_manager_enable_stats' )) ? __( 'Turning on statistic on sites' ) : __( 'Turning off statistic on sites' ) ?></h3>
		<?php
		$websites = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, null );

		if ( is_array( $websites ) && count( $websites ) > 0 ) {
			foreach ( $websites as $website ) {
				if ( $website ) {
					?>
		<span class="applyStatsToSites" siteid="<?php echo $website['id']; ?>" status="queue"><strong><?php echo stripslashes( $website['name'] ); ?></strong>: <span class="queue"><?php _e( 'Queued' ); ?></span>
                    <span class="progress" style="display:none"></span><span class="status"></span></span><br />
					<?php
				}
			}
			?>                  
            <div id="kwl-stats-apply-to-sites-ajax-message-zone"></div>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    kwl_TotalDNLThreads = jQuery('.applyStatsToSites[status="queue"]').length;
                    kwl_FinishedDNLThreads = 0;       
                    if (kwl_TotalDNLThreads > 0)    
                        keyword_links_stats_options_apply_to_sites_start_next();
                })
            </script>
			<?php

		}
	}

	public function keyword_links_clear_donotlink_on_sites() {

		if ( ! wp_verify_nonce( $_POST['kwlPreSaveToSites'], 'kwlPreSaveToSites' ) ) {
			exit; }

		$websites = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, null );
		$dbwebsites = array();
		if ( $websites ) {
			$sites = array();
			foreach ( $websites as $website ) {
				if ( $website ) {
					$sites[] = $website['id']; }
			}
			$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, $sites, null );
		}

		$output = new stdClass();
		$output->ok = array();
		$output->status = array();
		$post_data = array();
		$post_data['action'] = 'donotlink_clear';

		do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsites, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'do_not_link_clear_handler' ), $output );
		$message = '';
		foreach ( $dbwebsites as $website ) {
			$message .= stripslashes( $website->name ) . ': ' . $output->status[ $website->id ] . '<br />';
		}
		$result = array();
		$result['message'] = $message;

		die( json_encode( $result ) );

	}

	public function keyword_links_donotlink_pre_apply_to_sites() {
		if ( ! wp_verify_nonce( $_POST['kwlPreSaveToSites'], 'kwlPreSaveToSites' ) ) {
			exit; }

		$site_blocks = $this->get_option( 'keyword_links_site_blocks' );
		$fullurl_blocks = $this->get_option( 'keyword_links_full_url_blocks' );
		$partial_blocks = $this->get_option( 'keyword_links_partial_blocks' );
		$site_blocks = is_array( $site_blocks ) ? $site_blocks : array();
		$fullurl_blocks = is_array( $fullurl_blocks ) ? $fullurl_blocks : array();
		$partial_blocks = is_array( $partial_blocks ) ? $partial_blocks : array();

		$websites = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, null );
		$dbwebsites = array();
		if ( $websites ) {
			$sites = array();
			foreach ( $websites as $website ) {
				if ( $website ) {
					$sites[] = $website['id']; }
			}
			$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, $sites, null );
		}

		$output = array();
		foreach ( $site_blocks as $url_path => $value ) {
			$siteid = $value['siteid'];
			if ( $dbwebsites[ $siteid ] ) {
				$output['site_blocks'][ $url_path ] = array( 'siteid' => $siteid, 'name' => stripslashes( $dbwebsites[ $siteid ]->name ) ); }
		}

		foreach ( $fullurl_blocks as $url_path => $value ) {
			$siteid = $value['siteid'];
			$path = $value['path'];
			if ( $dbwebsites[ $siteid ] ) {
				$output['fullurl_blocks'][ $url_path ][] = array( 'siteid' => $siteid , 'path' => $path, 'name' => stripslashes( $dbwebsites[ $siteid ]->name ) );
			}
		}
		foreach ( $partial_blocks as $path ) {
			foreach ( $dbwebsites as $siteid => $website ) {
				$output['partial_blocks'][ $path ][] = array( 'siteid' => $siteid , 'name' => stripslashes( $dbwebsites[ $siteid ]->name ) );
			}
		}

		die( json_encode( $output ) );
	}

	public function keyword_links_donotlink_perform_apply_to_sites() {
		$siteid = $_POST['siteId'];
		$type = $_POST['type'];
		$path = isset( $_POST['path'] ) ? $_POST['path'] : '';

		if ( empty( $siteid ) || empty( $type ) ) {
			die( '' ); }
		Keyword_Links_Utility::end_session();

		$output = new stdClass();
		$output->ok = array();
		$output->status = array();

		$dbwebsite = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, array( $siteid ), null );
		if ( $dbwebsite ) {
			$post_data = array();
			if ( 'site_blocks' == $type ) {
				$post_data['action'] = 'donotlink_site_blocks';
			} else if ( 'fullurl_blocks' == $type || 'partial_blocks' == $type ) {
				$post_data['path'] = $path;
				$post_data['action'] = 'donotlink_path_blocks';
			}
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsite, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'do_not_link_site_block_handler' ), $output );
		} else {
			$output->error = __( 'Error: Site data.' ); }
		die( json_encode( $output ) );
	}

	public function keyword_links_stats_perform_apply_to_sites() {
			$siteid = $_POST['siteId'];
			$enableSats = intval( $this->get_option( 'mainwp_links_manager_enable_stats' ) );

		if ( empty( $siteid ) ) {
			die( json_encode( 'FAIL' ) ); }
			$post_data = array( 'action' => 'enable_stats','enablestats' => $enableSats );
			$information = apply_filters( 'mainwp_fetchurlauthed', __FILE__, $this->childKey, $siteid, 'keyword_links_action', $post_data );
			die( json_encode( $information ) );
	}


	public function keyword_links_delete_group() {
		global $wpdb;
		$ret = array( 'success' => false );
		$group_id = intval( $_REQUEST['group_id'] );
		if ( intval( $group_id ) > 0 ) {
			if ( $wpdb->query( sprintf( "DELETE FROM `%s` WHERE `id`='%d' LIMIT 1", $this->table_name( 'keyword_links_group' ), $group_id ) ) ) {
				$wpdb->query( sprintf( "DELETE FROM `%s` WHERE `group_id`='%d'", $this->table_name( 'keyword_links_link_group' ), $group_id ) );
				$ret['success'] = true;
			}
		}
		echo json_encode( $ret );
		exit;
	}

	public function keyword_links_save_configuration() {

		 // All is OK now
		$this->option['saved_time'] = current_time( 'timestamp' );
		$this->option['replace_max'] = intval( $_POST['replace_max'] );
		$this->option['replace_max_keyword'] = intval( $_POST['replace_max_keyword'] );
		$this->option['default_link_nofollow'] = intval( $_POST['default_link_nofollow'] );
		$this->option['default_link_newtab'] = intval( $_POST['default_link_newtab'] );
		$this->option['replace_keyword_in_h_tag'] = intval( $_POST['replace_keyword_in_h_tag'] );
		$this->option['default_link_class'] = sanitize_text_field( $_POST['default_link_class'] );
		$this->option['enable_post_type'] = isset( $_POST['enable_post_type'] ) ? (array) $_POST['enable_post_type'] : array();
		//        $this->option['12_24_clock'] = intval($_POST['12_24_clock']);
		$this->option['redirection_folder'] = sanitize_text_field( $_POST['redirection_folder'] );
		$this->option['post_match_title'] = intval( $_POST['post_match_title'] );
		//        $this->option['post_same_category'] = intval($_POST['post_same_category']);
		$this->option['enable_post_type_link'] = isset( $_POST['enable_post_type_link'] ) ? (array) $_POST['enable_post_type_link'] : array();

		if ( update_option( $this->option_handle, $this->option ) ) {
			die( 'SUCCESS' );
		} else {
			die( 'ERROR' ); }
	}

	public function keyword_links_save_link() {
		global $wpdb;
		$ret = array(
			'success' => false,
			'field_error' => array(),
			'error' => '',
		);

		// If we have link id, it means we do edit
		if ( isset( $_POST['link_id'] ) ) {
			$link_id = intval( $_POST['link_id'] );
			if ( $link_id ) {
				$link = $wpdb->get_row( sprintf( "SELECT * FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_link' ), $link_id ) ); }
		}
		// Validate link name
		if ( isset( $_POST['link_name'] ) && ! empty( $_POST['link_name'] ) ) {
			$link_name = sanitize_text_field( $_POST['link_name'] );
			if ( empty( $link_name ) ) {
				$ret['field_error']['link_name'] = __( 'This field has unallowed character(s)' ); }
			if ( ! isset( $link ) && $link_name && $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s` WHERE `name`='%s'", $this->table_name( 'keyword_links_link' ), $link_name ) ) > 0 ) {
				$ret['field_error']['link_name'] = __( 'Link name exists' ); }
		} else {
			$ret['field_error']['link_name'] = __( 'This field is required' );
		}
		// Validate link destination url
		if ( isset( $_POST['link_destination_url'] ) && ! empty( $_POST['link_destination_url'] ) ) {
			$link_destination_url = esc_url_raw( $_POST['link_destination_url'] );
			if ( empty( $link_destination_url ) ) {
				$ret['field_error']['link_destination_url'] = __( 'Please fill with a valid URL' ); }
		} else {
			$ret['field_error']['link_destination_url'] = __( 'This field is required' );
		}
		// Validate link cloak path
		if ( isset( $_POST['link_cloak_path'] ) && ! empty( $_POST['link_cloak_path'] ) ) {
			$link_cloak_path = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $_POST['link_cloak_path'] );
			if ( empty( $link_cloak_path ) ) {
				$ret['field_error']['link_cloak_path'] = __( 'This field has unallowed character(s)' ); }
			if ( ! isset( $link ) && $link_cloak_path && $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s` WHERE `cloak_path`='%s'", $this->table_name( 'keyword_links_link' ), $link_cloak_path ) ) > 0 ) {
				$ret['field_error']['link_cloak_path'] = __( 'The cloak URL exists' ); }
		} else {
			$link_cloak_path = '';
		}
		// Validate link keywords
		if ( isset( $_POST['link_keyword'] ) && ! empty( $_POST['link_keyword'] ) ) {
			$link_keyword = esc_html( $_POST['link_keyword'] );
			if ( empty( $link_keyword ) ) {
				$ret['field_error']['link_keyword'] = __( 'This field has unallowed character(s)' ); }
		} else {
			$ret['field_error']['link_keyword'] = __( 'This field is required' );
		}
		// Validate link group
		if ( isset( $_POST['link_group'] ) ) {
			$link_group = intval( $_POST['link_group'] );
		} else {
			$link_group = 0;
		}
		// Validate link no follow
		if ( isset( $_POST['link_nofollow'] ) ) {
			if ( '-1' == $_POST['link_nofollow'] ) {
				$link_rel = '-1'; } else {
				$link_rel = ('1' == $_POST['link_nofollow']) ? 'nofollow' : ''; }
		} else {
			$link_rel = '-1';
		}
		// Validate link new tab
		if ( isset( $_POST['link_newtab'] ) ) {
			if ( '-1' == $_POST['link_newtab'] ) {
				$link_target = '-1'; } else {
				$link_target = ('1' == $_POST['link_newtab']) ? '_blank' : ''; }
		} else {
			$link_target = '-1';
		}
		// Validate link class
		if ( isset( $_POST['link_class'] ) && ! empty( $_POST['link_class'] ) ) {
			$link_class = sanitize_text_field( esc_html( $_POST['link_class'] ) );
			if ( empty( $link_class ) ) {
				$ret['field_error']['link_class'] = __( 'This field has unallowed character(s)' ); }
		} else {
			$link_class = '';
		}

		$selected_sites = $selected_groups = '';

		if ( isset( $_POST['selected_sites'] ) && 'site' == $_POST['selected_by'] && is_array( $_POST['selected_sites'] ) && count( $_POST['selected_sites'] ) > 0 ) {
			foreach ( $_POST['selected_sites'] as $id ) {
				$selected_sites .= $id.';'; }
			$selected_sites = rtrim( $selected_sites, ';' );
		} else if ( isset( $_POST['selected_groups'] ) && is_array( $_POST['selected_groups'] ) && count( $_POST['selected_groups'] ) > 0 ) {
			foreach ( $_POST['selected_groups'] as $id ) {
				$selected_groups .= $id.';'; }
			$selected_groups = rtrim( $selected_groups, ';' );
		}
		$link_type = intval( $_POST['link_type'] );
		if ( 2 == $link_type && ( ! empty( $selected_groups ) || ! empty( $selected_sites )) ) {
			$link_type = 3; // individual link but apply for all filterd posts of sites
		} else if ( 3 == $link_type && empty( $selected_groups ) && empty( $selected_sites ) ) {
			$link_type = 2; // individual link only
		}

		$exact_match = $case_sensitive = 0;
		if ( isset( $_POST['link_exact_match'] ) ) {
			$exact_match = intval( $_POST['link_exact_match'] );
		}
		if ( isset( $_POST['link_case_sensitive'] ) ) {
			$case_sensitive = intval( $_POST['link_case_sensitive'] );
		}

		// Check for error
		if ( empty( $ret['error'] ) && 0 == count( $ret['field_error'] ) ) {
			$fields = array(
				'name' => $link_name,
				'destination_url' => $link_destination_url,
				'cloak_path' => $link_cloak_path,
				'keyword' => $link_keyword,
				'link_rel' => $link_rel,
				'link_target' => $link_target,
				'link_class' => $link_class,
				'sites' => $selected_sites,
				'groups' => $selected_groups,
				'type' => $link_type,
				'exact_match' => $exact_match,
				'case_sensitive' => $case_sensitive,
			);

			if ( ! isset( $link ) ) {
				if ( $wpdb->insert( $this->table_name( 'keyword_links_link' ), $fields ) ) {
					$insert_id = $wpdb->insert_id;
					if ( $link_group > 0 ) {
						$wpdb->insert($this->table_name( 'keyword_links_link_group' ), array(
							'group_id' => $link_group,
							'link_id' => $insert_id,
						)); }
					$ret['link_id'] = $insert_id;
					$ret['success'] = true;
				}
			} else {
				$wpdb->update( $this->table_name( 'keyword_links_link' ), $fields, array( 'id' => $link->id ) );
				$wpdb->query( sprintf( "DELETE FROM `%s` WHERE `link_id`='%d'", $this->table_name( 'keyword_links_link_group' ), $link->id ) );
				if ( $link_group > 0 ) {
					$wpdb->insert($this->table_name( 'keyword_links_link_group' ), array(
						'group_id' => $link_group,
						'link_id' => $link->id,
					));
				}
				$ret['success'] = true;
			}
		}
		echo json_encode( $ret );
		exit;
	}

	function keyword_links_clear_link_child_site() {

		$current_sites = $_POST['current_sites'];
		$current_groups = $_POST['current_groups'];
		if ( empty( $current_sites ) && empty( $current_groups ) ) {
			return; }
		$sites = explode( ';', $current_sites );
		$groups = explode( ';', $current_groups );
		$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, $sites, $groups );

		//print_r($dbwebsites);
		$output = new stdClass();
		$output->ok = array();
		$output->errors = array();
		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'link_id' => intval( $_POST['link_id'] ),
			);
			$post_data['action'] = 'clear_link';
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsites, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'clear_link_handler' ), $output );
			foreach ( $dbwebsites as $website ) {
				echo stripslashes( $website->name ) . ': ' . $output->infor[ $website->id ] . '<br />';
			}
		}
		die( '' );
	}

	function keyword_links_save_link_child_site() {

		$link_id = $_POST['link_id'];
		if ( empty( $link_id ) ) {
			die( 'ERROR: Wrong link id.' ); }
		$link = Keyword_Links_DB::get_instance()->get_links_by( 'id', $link_id );

		if ( empty( $link ) ) {
			die( 'ERROR: Link not found.' );
		}
		//print_r($link);

		$sites = ! empty( $link->sites ) ? explode( ';', $link->sites ) : null;
		$groups = ! empty( $link->groups ) ? explode( ';', $link->groups ) : null;
		$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, $sites, $groups );

		 // get sites for specific link
		if ( $link->type == 2 || $link->type == 3 ) {
			$site_posts = $this->get_links_posts( $link->id );
			if ( is_array( $site_posts ) && count( $site_posts ) > 0 ) {
				foreach ( $site_posts as $siteid => $post_id ) {
					$dbwebsite_post = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, array( $siteid ), null );
					if ( $dbwebsite_post ) {
						$dbwebsites[ $siteid ] = $dbwebsite_post[ $siteid ];
					}
				}
			}
		}

		//print_r($dbwebsites);
		$output = new stdClass();
		$output->ok = array();
		$output->errors = array();
		$output->existed_keywords = array();
		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'id' => $link->id,
				'name' => $link->name,
				'destination_url' => $link->destination_url,
				'cloak_path' => $link->cloak_path,
				'keyword' => $link->keyword,
				'link_target' => $link->link_target,
				'link_rel' => $link->link_rel,
				'link_class' => $link->link_class,
				'type' => $link->type,
				'exact_match' => $link->exact_match,
				'case_sensitive' => $link->case_sensitive,
			);
			$post_data['action'] = 'add_link';
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsites, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'add_link_handler' ), $output );
			$return = array();
			$mss = '';
			$err = '';
			foreach ( $dbwebsites as $website ) {
				$mss .= stripslashes( $website->name ) . ': ' . $output->infor[ $website->id ] . '<br />';
				if ( isset( $output->existed_keywords[ $website->id ] ) && is_array( $output->existed_keywords[ $website->id ] ) ) {
					$kws = implode( ', ', $output->existed_keywords[ $website->id ] );
					$err .= $kws . ' is already in use on ' . stripslashes( $website->name ) . ' site. Please try another keyword or delete the existing one.' . '<br />';
					$return['norefresh'] = 1;
				}
			}
			$return['error_keywords'] = $err;
			$return['message'] = $mss;
			die( json_encode( $return ) );
		} else {
			die( json_encode( array( 'message' => __( 'No child site to save.' ) ) ) ); }

	}

	public function keyword_links_save_configuration_child_site() {

		if ( empty( $this->option ) ) {
			die( __( 'ERROR: Configuration empty.' ) );
		}		
		$this->perform_save_settings();		
		die();
	}

	
	function mainwp_apply_plugin_settings($siteid) {				
		$information = $this->perform_save_settings($siteid);					
		$result = array();
		if (is_array($information)) {
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$result = array('result' => 'success');
			} else if ($information['error']) {
				$result = array('error' => $information['error']);				
			} else {
				$result = array('result' => 'failed');
			}			
		} else {
			$result = array('result' => 'failed');
		}			
		die( json_encode( $result ) );		
	}
	
	
	public function perform_save_settings($siteid = null) {	
		$output = new stdClass();
		
		if ($siteid === null) {
			$websites = apply_filters( 'mainwp-getsites', __FILE__, $this->childKey, null );
			$dbwebsites = array();
			if ( $websites ) {
				$sites = array();
				foreach ( $websites as $website ) {
					if ( $website ) {
						$sites[] = $website['id']; }
				}
				$dbwebsites = apply_filters( 'mainwp-getdbsites', __FILE__, $this->childKey, $sites, null );
			}
			if ( empty($dbwebsites )) {
				die( __( 'No child site to save.' ) ); 	
			}			
			$output->ok = array();
			$output->message = array();
			$output->errors = array();
		}
			
		$post_data = array(
		  'replace_max' => $this->get_option( 'replace_max' ),
		  'replace_max_keyword' => $this->get_option( 'replace_max_keyword' ),
		  'default_link_nofollow' => $this->get_option( 'default_link_nofollow' ),
		  'default_link_newtab' => $this->get_option( 'default_link_newtab' ),
		  'replace_keyword_in_h_tag' => $this->get_option( 'replace_keyword_in_h_tag' ),
		  'default_link_class' => $this->get_option( 'default_link_class' ),
		  'post_match_title' => $this->get_option( 'post_match_title' ),
		  'post_same_category' => $this->get_option( 'post_same_category' ),
		  'redirection_folder' => $this->get_option( 'redirection_folder' ),
		);

		$enable_post_type = $this->get_option( 'enable_post_type' );

		$enable_post_type_sites = array();
		if ( is_array( $enable_post_type ) ) {
			foreach ( $enable_post_type as $post_type ) {
				if ( 'bulkpost' == $post_type ) {
					$enable_post_type_sites[] = 'post';
				} else if ( 'bulkpage' == $post_type ) {
					$enable_post_type_sites[] = 'page';
				} else {
					$enable_post_type_sites[] = $post_type; }
			}
		}
		$post_data['enable_post_type'] = $enable_post_type_sites;

		$enable_post_type_link = $this->get_option( 'enable_post_type_link' );
		$enable_post_type_link_sites = array();
		if ( is_array( $enable_post_type_link ) ) {
			foreach ( $enable_post_type_link as $post_type ) {
				if ( 'bulkpost' == $post_type ) {
					$enable_post_type_link_sites[] = 'post';
				} else if ( 'bulkpage' == $post_type ) {
					$enable_post_type_link_sites[] = 'page';
				} else {
					$enable_post_type_link_sites[] = $post_type; }
			}
		}
		$post_data['enable_post_type_link'] = $enable_post_type_link_sites;

		$post_data['action'] = 'update_config';
		
		if ($siteid === null) {
			do_action( 'mainwp_fetchurlsauthed', __FILE__, $this->childKey, $dbwebsites, 'keyword_links_action', $post_data, array( 'Keyword_Links_Handler', 'update_config_handler' ), $output );
			foreach ( $dbwebsites as $website ) {
				echo stripslashes( $website->name ) . ': ' . $output->infor[ $website->id ] . '<br />';
				if ( isset( $output->message[ $website->id ] ) ) {
					echo stripslashes( $website->name ) . ': ' . $output->message[ $website->id ] . '<br />'; }
			}
			die();		
		} else {
			return apply_filters( 'mainwp_fetchurlauthed', __FILE__, $this->childKey, $siteid, 'keyword_links_action', $post_data );
		}
	}	
	
	public function save_group() {
		global $wpdb;
		$ret = array(
			'success' => false,
			'field_error' => array(),
			'error' => '',
		);
		// If we have group id, it means we do edit
		if ( isset( $_POST['group_id'] ) ) {
			$group_id = intval( $_POST['group_id'] );
			if ( $group_id ) {
				$group = $wpdb->get_row( sprintf( "SELECT * FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_group' ), $group_id ) ); }
		}
		// Validate group name
		if ( isset( $_POST['group_name'] ) && ! empty( $_POST['group_name'] ) ) {
			$group_name = sanitize_text_field( $_POST['group_name'] );
			if ( empty( $group_name ) ) {
				$ret['field_error']['group_name'] = __( 'This field has unallowed character(s)' ); }
			if ( ! isset( $group ) && $group_name && $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s` WHERE `name`='%s'", $this->table_name( 'keyword_links_group' ), $group_name ) ) > 0 ) {
				$ret['field_error']['group_name'] = __( 'Group name exists' ); }
		} else {
			$ret['field_error']['group_name'] = __( 'This field is required' );
		}
		// Check for error
		if ( empty( $ret['error'] ) && count( $ret['field_error'] ) == 0 ) {
			$fields = array(
				'name' => $group_name,
			);
			if ( ! isset( $group ) ) {
				if ( $wpdb->insert( $this->table_name( 'keyword_links_group' ), $fields ) ) {
					$insert_id = $wpdb->insert_id;
					$ret['success'] = true;
				}
			} else {
				$wpdb->update( $this->table_name( 'keyword_links_group' ), $fields, array( 'id' => $group->id ) );
				$ret['success'] = true;
			}
		}
		echo json_encode( $ret );
		exit;
	}
	public function view_statistic( $exit = true ) {
		global $wpdb;
		include( $this->plugin_dir . '/includes/view-statistic.php' );
		if ( isset( $_REQUEST['action'] ) ) {
			exit; }
	}
	function load_statistic() {
		global $wpdb;
		include( $this->plugin_dir . '/includes/load-statistic.php' );
		exit;
	}

	public function explode_multi( $str ) {
		$delimiters = array( ',', ';', '|' );
		$str = str_replace( $delimiters, ',', $str );
		return explode( ',', $str );
	}

	// this function using for metabox
	public function get_post_keywords( $post_type, $cats = null ) {
		global $wpdb;
		$join = '';
		$where = '';
		if ( is_array( $cats ) && count( $cats ) > 0 ) {
			$join = "JOIN $wpdb->term_relationships tr ON tr.object_id = p.ID";
			$where = " AND (tr.term_taxonomy_id = '" . implode( "' OR tr.term_taxonomy_id = '", $cats ) . "')";
		}
		//$results = $wpdb->get_results(sprintf("SELECT * FROM $wpdb->posts as p LEFT JOIN $wpdb->postmeta as pm ON p.ID=pm.post_id $join WHERE p.post_status='publish' AND p.post_type='%s' AND pm.meta_key='_mainwp_kl_post_keyword' $where", $post_type));
		$results = $wpdb->get_results( sprintf( "SELECT * FROM $wpdb->posts as p $join WHERE p.post_status='publish' AND p.post_type='%s' $where", $post_type ) );
		$links = array();
		if ( ! is_array( $results ) ) {
			return array(); }
		$disable_linking = $this->get_option( 'disable_linking_automatically', array() );
		foreach ( $results as $result ) {
			if ( in_array( $result->post_name, (array) $disable_linking[ $result->post_type ] ) ) {
				continue; }
			$link = new stdClass;
			$link->id = $result->ID;
			$link->name = $result->post_title;
			if ( $result->post_type == 'page' ) {
				$link->destination_url = get_permalink( $result->ID ); } else {
				$link->destination_url = $result->guid; }
				//$link->destination_url = get_post_meta($result->ID, '_mainwp_kl_post_link', $link->destination_url);
				$meta_value = '';
				if ( isset( $result->meta_value ) ) {
					$meta_value = $result->meta_value; }
				$link->cloak_path = '';
				$link->keyword = ( $this->get_option( 'post_match_title' ) == 1 ? $result->post_title . ',' : '' ) . $meta_value;
				$link->link_target = '';
				$link->link_rel = '';
				$link->link_class = '';
				$link->type = 'post_type';
				$links[] = $link;
		}
		return $links;
	}
	// this function using for metabox
	public function get_all_links_enable_for_this_post( $post_id = null ) {
		global $post, $wpdb;
		if ( null !== $post_id ) {
			$post = get_post( $post_id ); }
		$links = array();
		$disable_add_links = $this->get_option( 'disable_add_links_automatically' );
		if ( ! is_array( $disable_add_links ) ) { $disable_add_links = array(); }
		$disable_array = isset( $disable_add_links[ $post->post_type ] ) ? $disable_add_links[ $post->post_type ] : array();
		// if disabled add links automatically in this post, avoid
		if ( in_array( $post->post_name, $disable_array ) ) {
			return $links;
		}
		// get allow post typies, if it isn't belong that => avoid
		$allow_post_type = (array) $this->get_option( 'enable_post_type', array( 'bulkpost', 'bulkpage' ) );
		if ( ! in_array( $post->post_type, $allow_post_type ) ) {
			return $links; }
		// Check if this post was disabled with this function, come back
		$disable = get_post_meta( $post->ID, '_mainwp_kl_disable', true );
		if ( 1 == $disable ) {
			return $links; }
		// count replace max and max keyword allowed.
		$replace_max = intval( $this->get_option( 'replace_max' ) );
		$replace_max_keyword = intval( $this->get_option( 'replace_max_keyword' ) );
		if ( 0 === $replace_max || 0 === $replace_max_keyword ) {
			return $links; }
		// Post types enabled to create links
		$post_types = (array) $this->get_option( 'enable_post_type_link', array( 'bulkpost', 'bulkpage' ) );
		foreach ( $post_types as $post_type ) {
			if ( 'bulkpost' == $post->post_type && $post_type == $post->post_type && $this->get_option( 'post_same_category' ) == 1 ) {
				$categories = get_the_terms( $post->ID, 'category' );
				$cats = array();
				if ( is_array( $categories ) ) {
					foreach ( $categories as $category ) {
						$cats[] = $category->term_id; }
				}
				$links_post_type = (array) $this->get_post_keywords( $post_type, $cats );
			} else {
				$links_post_type = (array) $this->get_post_keywords( $post_type );
			}
			if ( count( $links_post_type ) > 0 ) {
				$links = array_merge( $links, $links_post_type ); }
		}

		// custom links is links in "keyword" table which we was created
		$links_custom = (array) $wpdb->get_results( sprintf( 'SELECT * FROM `%s`', $this->table_name( 'keyword_links_link' ) ) );
		if ( count( $links_custom ) > 0 ) {
			$links = array_merge( $links, $links_custom ); }
		return $links;
	}

	// this function using for metabox
	public function get_all_keywords_with_link_to_on_this_post() {
		$ret = array();
		$available_liks = $this->get_all_links_enable_for_this_post();
		if ( empty( $available_liks ) ) {
			return $ret; }
		$i = 0;
		foreach ( $available_liks as $link ) {
			if ( ! $link ) {
				continue; }
			$keywords = $this->explode_multi( $link->keyword );
			usort( $keywords, create_function( '$a,$b', 'return strlen($a)<strlen($b);' ) );
			foreach ( $keywords as $keyword ) {
				$keyword = trim( $keyword );
				if ( empty( $keyword ) ) {
					continue; }
				$ret[ $i ]['keyword'] = $keyword;
				$ret[ $i ]['link'] = $link->destination_url; // only get destination_url
				$i++;
			}
		}
		return $ret;
	}
	public function get_taxonomy_keywords( $taxonomy ) {
		$terms = get_terms( $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array(); }
		$links = array();
		foreach ( $terms as $term ) {
			$link = new stdClass;
			$link->id = $term->term_id;
			$link->name = $term->name;
			$link->destination_url = get_term_link( $term, $taxonomy );
			$link->cloak_path = '';
			$link->keyword = $term->name;
			$link->link_target = '';
			$link->link_rel = '';
			$link->link_class = '';
			$link->type = 'taxonomy';
			$links[] = $link;
		}
		return $links;
	}

	protected function create_option_field( $name, $label, $type, $default = null, $fields = null, $description = null, $before = null, $after = null, $other = array() ) {
		echo '<div class="option-list">';
		echo '<label for="' . $name . '">' . $label . '</label>';
		echo '<div class="option-field">';
		if ( ! is_null( $before ) ) {
			echo $before; }
		$op_val = $this->get_option( $name );
		switch ( $type ) {
			case 'text_help':
				echo '<input type="' . $type . '" class="texta" name="' . $name . '" id="' . $name . '" value="' . ( ! is_null( $default ) && ! $this->get_option( $name ) ? $default : $this->get_option( $name )) . '" /><span class="' . $name . '_help"><img src="' . $this->plugin_url . '/img/help.png"/></span>';
				break;
			case 'text':
			case 'password':
				if ( isset( $this->option[ $name ] ) ) {
					echo '<input type="' . $type . '" class="text" name="' . $name . '" id="' . $name . '" value="' . $this->get_option( $name ) . '" />' ; } else {
					echo '<input type="' . $type . '" class="text" name="' . $name . '" id="' . $name . '" value="' . ( ! is_null( $default ) && ! $this->get_option( $name ) ? $default : $this->get_option( $name )) . '" />'; }
				break;
			case 'file':
				echo '<input type="' . $type . '" name="' . $name . '" id="' . $name . '" />';
				break;
			case 'textarea':
				echo '<textarea class="text" rows="5" cols="50" name="' . $name . '" id="' . $name . '">' . ( ! is_null( $default ) && ! $this->get_option( $name ) ? $default : $this->get_option( $name )) . '</textarea>';
				break;
			case 'select':
				echo '<select name="' . $name . '" id="' . $name . '">';
				foreach ( (array) $fields as $val => $field ) {
					echo '<option value="' . $val . '" ' . ( ( $this->get_option( $name ) != '' && $this->get_option( $name ) == $val ) || ( ! is_null( $default ) && $this->get_option( $name ) === '' && $default == $val ) ? 'selected="selected"' : '' ) . '>' . $field . '</option>';
				}
				echo '</select>';
				break;
			case 'checkbox':
				foreach ( (array) $fields as $val => $field ) {
					echo '<label>';
					echo '<input type="checkbox" name="' . $name . '[]" value="' . $val . '" ' . ((( ! empty( $op_val ) && in_array( $val, (array) $op_val )) || (is_array( $default ) && '' === $op_val && in_array( $val, $default ) ) || ('force_check_all' == $default)) ? 'checked="checked"' : '' ) . ' /> ';
					echo $field;
					echo '</label><br />';
				}
				break;
			case 'radio':
				foreach ( (array) $fields as $val => $field ) {
					echo '<label>';
					echo '<input type="radio" name="' . $name . '" value="' . $val . '" ' . ( $val == $this->get_option( $name ) || ( ! is_null( $default ) && $this->get_option( $name ) === '' && $val == $default ) ? 'checked="checked"' : '' ) . ' /> ';
					echo $field;
					echo '</label><br />';
				}
				break;
		}
		if ( ! is_null( $after ) ) {
			echo $after; }
		if ( ! is_null( $description ) ) {
			echo '<br /><small>' . $description . '</small>';
		}
		echo '</div>';
		echo '</div>';
	}
	// modify  create_extra_option function a bit
	public function create_extra_option_field( $params ) {
		$name = $params['name'];
		$label = $params['label'];
		$type = $params['type'];
		$default = $params['default'];
		$fields = $params['fields'];
		$description = $params['description'];
		$before = $params['before'];
		$after = $params['after'];
		$field_class = $params['field_class'];
		if ( ! empty( $field_class ) ) {
			$str_cl = "class = \"$field_class\""; }
		echo '<div class="option-list">';
		echo '<label for="' . $name . '">' . $label . '</label>';
		echo '<div class="option-field">';
		if ( empty( $fields ) ) {
			echo '(There are not items in this field)';
		} else {
			if ( ! is_null( $before ) ) {
				echo $before; }
			$op_val = $this->get_option( $name );
			switch ( $type ) {
				case 'checkbox':
					foreach ( (array) $fields as $field ) {
						$val = $field['value'];
						$label = $field['label'];
						$title = $field['title'];
						$html_asg = $field['html_assigned'];
						if ( ! empty( $title ) ) {
							$str_title = "title = \"$title\""; }
						echo "<div $str_cl>";
						echo '<input type="checkbox" name="' . $name . '[]" ' . $str_title . ' value="' . $val . '" ' . ((( ! empty( $op_val ) && in_array( $val, (array) $op_val )) || (is_array( $default ) && '' === $op_val && in_array( $val, $default ) ) || ('force_check_all' == $default)) ? 'checked="checked"' : '' ) . ' /> ';
						echo $label;
						echo '</div> ' . $html_asg;
					}
					break;
			}
			if ( ! is_null( $after ) ) {
				echo $after; }
			if ( ! is_null( $description ) ) {
				echo '<div class="clearfix"></div>';
				echo '<small>' . $description . '</small>';
			}
		}
		echo '</div>';
		echo '</div>';
	}
}



function keyword_links_autoload( $class_name ) {
	$class_name = str_replace( '_', '-', strtolower( $class_name ) );
	//General system classes
	$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'class' . DIRECTORY_SEPARATOR . $class_name . '.class.php';
	if ( file_exists( $class_file ) ) {
		require_once( $class_file );
	}

	//Classes per page
	$page_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'page' . DIRECTORY_SEPARATOR . $class_name . '.page.php';
	if ( file_exists( $page_file ) ) {
		require_once( $page_file );
	}
}

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( 'keyword_links_autoload' );
} else {
	function __autoload( $class_name ) {
		keyword_links_autoload( $class_name );
	}
}

register_activation_hook( __FILE__, 'keyword_links_activate' );
register_deactivation_hook( __FILE__, 'keyword_links_deactivate' );

function keyword_links_activate() {
	update_option( 'mainwp_links_manager_extension_activated', 'yes' );
	$extensionActivator = new KeywordLinksExtensionActivator();
	$extensionActivator->activate();	
}

function keyword_links_deactivate() {
	$extensionActivator = new KeywordLinksExtensionActivator();
	$extensionActivator->deactivate();
}

class KeywordLinksExtensionActivator {
	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-links-manager-extension';
	protected $product_id = 'MainWP Links Manager Extension';
	protected $software_version = '2.1';

	public function __construct() {
		$this->childFile = __FILE__;
		add_filter( 'mainwp-getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp-activated-check', false );
		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this();
		} else {
			add_action( 'mainwp-activated', array( &$this, 'activate_this' ) );
		}
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

	function admin_init() {
		if ( get_option( 'mainwp_links_manager_extension_activated' ) == 'yes' ) {
			delete_option( 'mainwp_links_manager_extension_activated' );
			wp_redirect( admin_url( 'admin.php?page=Extensions' ) );
			return;
		}
	}

	function settings() {
		do_action( 'mainwp-pageheader-extensions', __FILE__ );
		if ( $this->childEnabled ) {
			?>
            <?php self::render_qsg(); ?>
            <div style="display: none;" class="mainwp_info-box-yellow" id="kwl-setting-ajax-zone"></div>
                <?php
				Links_Manager_Extension::get_instance()->settings_page();
		} else {
			?><div class="mainwp_info-box-yellow"><strong>The Extension has to be enabled to change the settings.</strong></div><?php
		}
		do_action( 'mainwp-pagefooter-extensions', __FILE__ );
	}

	function get_this_extension( $pArray ) {
		$pArray[] = array( 'plugin' => __FILE__, 'api' => $this->plugin_handle , 'mainwp' => true, 'callback' => array( &$this, 'settings' ), 'apiManager' => true );
		return $pArray;
	}

	function activate_this() {
		$this->mainwpMainActivated = apply_filters( 'mainwp-activated-check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp-extension-enabled-check', __FILE__ );		
		$this->childKey = $this->childEnabled['key'];

		if ( function_exists( 'mainwp_current_user_can' )&& ! mainwp_current_user_can( 'extension', 'mainwp-links-manager-extension' ) ) {
			return; }
		$keyword_links = Links_Manager_Extension::get_instance();
	}

	function get_enable_status() {
		return $this->childEnabled;
	}
	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>Links Manager Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function get_child_key() {

		return $this->childKey;
	}

	public function get_child_file() {

		return $this->childFile;
	}

	public function update_option( $option_name, $option_value ) {

		$success = add_option( $option_name, $option_value, '', 'no' );

		if ( ! $success ) {
			$success = update_option( $option_name, $option_value );
		}

		 return $success;
	}
	public function activate() {
		$options = array(
		'product_id' => $this->product_id,
							'activated_key' => 'Deactivated',
							'instance_id' => apply_filters( 'mainwp-extensions-apigeneratepassword', 12, false ),
							'software_version' => $this->software_version,
						);
		$this->update_option( $this->plugin_handle . '_APIManAdder', $options );
	}
	public function deactivate() {
		$this->update_option( $this->plugin_handle . '_APIManAdder', '' );
	}

	public static function render_qsg() {
		$plugin_data = get_plugin_data( MAINWP_LINKS_MANAGER_PLUGIN_FILE, false );
		$description = $plugin_data['Description'];
		$extraHeaders = array( 'DocumentationURI' => 'Documentation URI' );
		$file_data = get_file_data( MAINWP_LINKS_MANAGER_PLUGIN_FILE, $extraHeaders );
		$documentation_url  = $file_data['DocumentationURI'];
		?>
         <div  class="mainwp_ext_info_box" id="lm-pth-notice-box">
            <div class="mainwp-ext-description"><?php echo $description; ?></div><br/>
            <b><?php echo __( 'Need Help?' ); ?></b> <?php echo __( 'Review the Extension' ); ?> <a href="<?php echo $documentation_url; ?>" target="_blank"><i class="fa fa-book"></i> <?php echo __( 'Documentation' ); ?></a>. 
                    <a href="#" id="mainwp-lm-quick-start-guide"><i class="fa fa-info-circle"></i> <?php _e( 'Show Quick Start Guide','mainwp' ); ?></a></div>
                    <div class="mainwp_ext_info_box" id="mainwp-lm-tips" style="color: #333!important; text-shadow: none!important;">
                      <span><a href="#" class="mainwp-show-tut" number="1"><i class="fa fa-book"></i> <?php _e( 'MainWP Links Manager Extension Overview','mainwp' ) ?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" class="mainwp-show-tut"  number="2"><i class="fa fa-book"></i> <?php _e( 'Create a New Link','mainwp' ) ?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" class="mainwp-show-tut"  number="3"><i class="fa fa-book"></i> <?php _e( 'Delete Links','mainwp' ) ?></a></span><span><a href="#" id="mainwp-lm-tips-dismiss" style="float: right;"><i class="fa fa-times-circle"></i> <?php _e( 'Dismiss','mainwp' ); ?></a></span>
                      <div class="clear"></div>
                      <div id="mainwp-lm-tuts">
                        <div class="mainwp-lm-tut" number="1">
                            <h3>MainWP Links Manager Extension Overview</h3>
                            <p>MainWP Links Manager extension has been built for both beginning marketers and those proficient in Search Engine Optimization with numerous customizable features. It allows you to preset a list of keyword (links) which will be automatically applied to your posts and pages. Also, you can use it for inter-linking your posts.</p>
                            <p>The extension settings page enables you to Create and Manage Links, Set the Extension Configuration, Manage Links Groups, Set Do Not links, Track click Statistics and Import/Export Settings.</p>
                            <h3>Links</h3>
                            <p>In the Links page provides you overview of your links. Here, you can create  a new links and edit or delete the existing ones.</p>
                            <h3>Configuration</h3>
                            <p>The Configuration page enables you to set default values for the extension. A few of these options can be individually set for each link. If you don't specify these options when creating a link, these, default settings will be applied.</p>
                            <h3>Groups</h3>
                            <p>Here you can create new groups, edit or delete the existing groups.</p>
                            <h3>Do Not Link</h3>
                            <p>This tab provides you an option to set specific urls ( sites/pages/posts/categories/..) you don't want to use links in. To use it properly, add one url or a part of the url per line. For Example:</p>
                            <ul>
                                <li><strong>Full URL:</strong> http://mysite.com/this-is-my-full-url - Only blocks that URL</li>
                                <li><strong>Partial URL:</strong> /about/ - Blocks every .../about/... page in your network</li>
                                <li><strong>Site URL:</strong> http://mysite.com/ - Completely blocks that site</li>
                            </ul>
                            <h3>Statistics</h3>
                            <p>Here you can track statistics for your links. It will count Row and Unique clicks for all your links.</p>
                            <h3>Import/Export</h3>
                            <p>Use this page to export your settings and if needed to import old ones.</p>
                            <h3>Links Manager Extension Options Metabox</h3>
                            <p>This widget is very important part of the extension. It can be found in the MainWP > Posts/Pages > Add New page.</p>
                            <p>If you don't have a previously created link, you can quickly create one here. Just enter a Link Name, Destination URL, optionally Cloak URL and wanted keywords. Also if you don't want to use some of already created keywords, just check the right checkboxes in the bottom part of the widget and the extension will ignore them.</p>
                        </div>
                        <div class="mainwp-lm-tut"  number="2">
                            <h3>Create a New Link</h3>
                            <p>On the Links tab and click the Add New Link button.</p>
                            <p>After clicking the button, popup window will appear with a few options you need to set.</p>
                            <ol>
                                <li>Enter a Link Name;</li>
                                <li>Enter a Destination URL;</li>
                                <li>Set a Cloak URL (optional);</li>
                                <li>Enter a keyword(s);</li>
                                <li>Select a link Group (optional);</li>
                                <li>Select a link Attribute (Follow/No Follow) - if you leave "Use default" the setting from the Configuration page will be applied;</li>
                                <li>Select whether you like link to be opened in the same or new window  - if you leave "Use default" the setting from the Configuration page will be applied;</li>
                                <li>Enter a link CSS class name (optional);</li>
                                <li>Select child sites where you want to use the link. Automatic linking will be applied only on sites selected here.</li>
                                <li>Click the Add Link button.</li>
                            </ol>
                        </div>
                        <div class="mainwp-lm-tut"  number="3">
                            <h3>Delete Links</h3>
                            <p>To delete a link in your MainWP Links Manager Extension, in the Links tab, locate the link you want to delete and click the Delete link.</p>
                            <img src="http://docs.mainwp.com/wp-content/uploads/2014/02/kl-delete-1024x111.png" style="max-width: 100%"; >
                            <p>Popup window will appear and as you if you want to keep your previously used links in your child site. If you select NO, links will be removed from your sites, is you select YES links will stay.</p>
                            <img src="http://docs.mainwp.com/wp-content/uploads/2014/02/kl-keep-links.png" style="max-width: 100%"; >
                            <p>Click the Delete Link button.</p>
                        </div>
                      </div>
                    </div>
        <?php
	}
}

global $keywordLinksExtensionActivator;
$keywordLinksExtensionActivator = new KeywordLinksExtensionActivator();
