<?php

class MainWPCustomPostType {
	public static $plugin_translate = 'mainwp-custom-post-types';
	public static $nonce_token      = 'mainwp_custom_post_type_nonce_';
	private static $instance        = null;
	public $plugin_handle           = 'mainwp_custom_post_type';
	protected $child_version        = '0.1';

	public static $default_post_types = array(
		'post',
		'page',
		'attachment',
		'revision',
		'nav_menu_item',
		'bulkpost',
		'bulkpage',
		'shop_webhook',
		'product_variation',
		'shop_order',
		'shop_order_refund',
	);

	public function __construct() {
		global $pagenow;

		add_action( 'in_admin_header', array( $this, 'in_admin_header' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		add_action( 'mainwp_custom_post_types_default', array( $this, 'post_types_default' ) );

		if ( is_admin() ) {
			// Add our scripts only on plugin extension page
			if ( $pagenow == 'admin.php' && isset( $_REQUEST['page'] ) && strcasecmp( $_REQUEST['page'], 'Extensions-Mainwp-Custom-Post-Types' ) === 0 ) {
				add_action( 'init', array( $this, 'init' ) );
			} else {
				// It need to be admin_init because of add_meta_box function
				add_action( 'admin_init', array( $this, 'init_other' ) );
			}

			add_action( 'save_post', array( $this, 'redirect_after_save_post' ) );
			add_action( 'woocommerce_process_product_meta', array( $this, 'redirect_after_save_product' ), 99 );
			add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'redirect_after_save_coupon' ), 99 );

			// We cannot use admin_print_scripts because jQuery is not already defined there
			// Also all post types needs to be registered using register_post_type
			add_action( 'wp_before_admin_bar_render', array( $this, 'admin_add_mainwp_icon' ) );

			// Ajax requests
			add_action( 'wp_ajax_mainwp_custom_post_type_list', array( $this, 'ajax_list' ) );
			add_action( 'wp_ajax_mainwp_custom_post_type_send_to_child', array( $this, 'ajax_send_to_child' ) );
			add_action( 'wp_ajax_mainwp_custom_post_type_send_to_child_step_2', array( $this, 'ajax_send_to_child_step_2' ) );
		}
	}

	/**
	 * Add support for boilerplate token insertion
	 * Support TinyMCE ver 4
	 * See http://stackoverflow.com/questions/7408559/wait-for-tinymce-to-load
	 **/
	public function tiny_mce_before_init( $initArray ) {
		$initArray['setup'] = <<<JS
		function(ed) {
    ed.on("init",
      function(args) {
        ed.on("focus", function() {
          lastOne = "editor";
        } );
      }
    );
}
JS;

		return $initArray;
	}

	public function post_types_default() {
		return self::$default_post_types;
	}

	/**
	 * Redirect user to synchronization page
	 * if he select at least one website/group
	 **/
	public function redirect_after_save_post( $post_id ) {
		$post_id   = (int) $post_id;
		$post_type = get_post_type( $post_id );
		// return to process in other hooks
		if ( in_array( $post_type, array( 'product', 'product_variation', 'shop_coupon' ) ) ) {
			return;
		}
		$this->process_save_post( $post_id );
	}

	public function redirect_after_save_product( $post_id ) {
		$post_id   = (int) $post_id;
		$post_type = get_post_type( $post_id );
		// only process products
		if ( ! in_array( $post_type, array( 'product', 'product_variation' ) ) ) {
			return;
		}

		$this->process_save_post( $post_id );
	}

	public function redirect_after_save_coupon( $post_id ) {
		$post_id   = (int) $post_id;
		$post_type = get_post_type( $post_id );
		// only process shop_coupon
		if ( ! in_array( $post_type, array( 'shop_coupon' ) ) ) {
			return; // process in other hook
		}
		$this->process_save_post( $post_id );
	}


	public function process_save_post( $post_id ) {
		$post_id   = (int) $post_id;
		$post_type = get_post_type( $post_id );

		if ( ! $post_type || in_array( $post_type, self::$default_post_types ) ) {
			return;
		}

		// verify this came from the our screen and with proper authorization.
		if ( ! isset( $_POST['select_sites_nonce'] ) || ! wp_verify_nonce( $_POST['select_sites_nonce'], 'select_sites_' . $post_id ) ) {
			return;
		}

		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$selected_groups = array();
		$selected_sites  = array();

		if ( isset( $_POST['select_by'] ) ) {
			switch ( $_POST['select_by'] ) {
				case 'group':
					if ( isset( $_POST['selected_groups'] ) && is_array( $_POST['selected_groups'] ) ) {
						foreach ( $_POST['selected_groups'] as $selected ) {
							$selected_groups[] = (int) $selected;
						}
					}
					break;

				case 'site':
					if ( isset( $_POST['selected_sites'] ) && is_array( $_POST['selected_sites'] ) ) {
						foreach ( $_POST['selected_sites'] as $selected ) {
							$selected_sites[] = (int) $selected;
						}
					}
					break;
			}
		}

		if ( isset( $_POST['post_category'] ) && is_array( $_POST['post_category'] ) ) {
			update_post_meta( $post_id, '_categories', $_POST['post_category'] );
		}

		if ( ! empty( $selected_groups ) || ! empty( $selected_sites ) ) {
			$post_only_existing = ( isset( $_POST['post_only_existing'] ) && $_POST['post_only_existing'] == '1' ? 1 : 0 );
			?>
			<form id="mainwp_custom_post_type_redirect_after_save_post" method="post" action="<?php echo admin_url( 'admin.php?page=Extensions-Mainwp-Custom-Post-Types' ); ?>">
				<input type="hidden" name="selected_groups" value="<?php echo esc_attr( wp_json_encode( $selected_groups ) ); ?>">
				<input type="hidden" name="selected_sites" value="<?php echo esc_attr( wp_json_encode( $selected_sites ) ); ?>">
				<?php if ( isset( $_POST['post_category'] ) && is_array( $_POST['post_category'] ) ) : ?>
					<input type="hidden" name="selected_categories" value="<?php echo esc_attr( wp_json_encode( $_POST['post_category'] ) ); ?>">
				<?php endif; ?>
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
				<input type="hidden" name="redirect_after_save_post" value="1">
				<input type="hidden" name="post_only_existing" value="<?php echo esc_attr( $post_only_existing ); ?>">
				<?php
				wp_nonce_field( 'mainwp_custom_post_type_redirect_after_save_post_' . $post_id );
				?>
			</form>
			<script>document.getElementById( 'mainwp_custom_post_type_redirect_after_save_post' ).submit();</script>
			<?php
			die();
		}
	}


	/**
	 * Add and remove metaboxes on post.php and post-new.php
	 **/
	public function init_other() {
		global $pagenow;

		if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
			// global $post_id is not defined here
			$post_type = false;
			$post_id   = ( isset( $_REQUEST['post'] ) ? (int) $_REQUEST['post'] : 0 );
			if ( $post_id > 0 ) {
				$post_type = get_post_type( $post_id );
			}
			if ( ! $post_type ) {
				$post_type = ( isset( $_REQUEST['post_type'] ) ? (string) trim( $_REQUEST['post_type'] ) : '' );
			}

			if ( strlen( $post_type ) > 0 && ! in_array( $post_type, self::$default_post_types ) ) {
				$metaboxes = apply_filters( 'mainwp_get_metaboxes_post', false );
				add_meta_box(
					'select-sites-div',
					__( 'Select sites', 'mainwp' ),
					array(
						$metaboxes,
						'select_sites',
					),
					$post_type,
					'side',
					'default'
				);

				// MainWp Select Categories Metabox
				// WooCommerce has own categories support
				if ( $post_type != 'product' ) {
					add_meta_box(
						'add-categories-div',
						__( 'Categories', 'mainwp' ),
						array(
							$metaboxes,
							'add_categories',
						),
						$post_type,
						'side',
						'default'
					);
					remove_meta_box( 'categorydiv', $post_type, 'normal' );
				}

				// Support for boilerplate
				if ( defined( 'MAINWP_BOILERPLATE_PLUGIN_FILE' ) ) {
					add_meta_box(
						'mainwp-boilerplate-metabox',
						__( 'Boilerplate Tokens', 'mainwp' ),
						array(
							BoilerplateExtension::get_instance(),
							'bulkpost_edit_boilerplate',
						),
						$post_type,
						'side',
						'default'
					);
				}
			}
		}
	}

	/*
	 * Singleton
	 **/
	public static function Instance() {
		if ( self::$instance == null ) {
			self::$instance = new MainWPCustomPostType();
		}

		return self::$instance;
	}

	/**
	 * Display MainWp icon on custom post types
	 **/
	public function admin_add_mainwp_icon() {
		// Its displayed on every page
		?>
		<script type='text/javascript'>
			jQuery(document).ready(function () {
				var mainwp_custom_post_types_list = <?php echo wp_json_encode( array_keys( get_post_types( array( '_builtin' => false ) ) ) ); ?>;
				jQuery.each(mainwp_custom_post_types_list, function (i, val) {
					// Here we add icon to top-level menu
					jQuery('#adminmenu a[href="edit.php?post_type=' + val + '"] .wp-menu-name').html(function (index, old_html) {
						return old_html + ' <img src="/wp-content/plugins/mainwp/assets/images/icon.png" height="16" width="16" style="position: relative; top: 3px;">';
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * @return bool
	 *
	 * Check if user can access this plugin
	 */
	public function check_permissions() {
		if ( has_filter( 'mainwp_currentusercan' ) ) {
			if ( ! apply_filters( 'mainwp_currentusercan', true, 'extension', 'mainwp-custom-post-types' ) ) {

				return false;
			}
		} else {
			if ( ! current_user_can( 'manage_options' ) ) {

				return false;
			}
		}

		return true;
	}

	/**
	 * Get websites and post info for synchronization
	 **/
	public function ajax_send_to_child() {
		$this->ajax_check_permissions( 'send_to_child' );

		$sites  = ( isset( $_POST['sites'] ) ? array_map( 'intval', (array) $_POST['sites'] ) : array() );
		$groups = ( isset( $_POST['groups'] ) ? array_map( 'intval', (array) $_POST['groups'] ) : array() );
		$id     = ( isset( $_POST['id'] ) ? array_map( 'intval', (array) $_POST['id'] ) : array() );

		// By default get_posts doesn't return for example 'shop_coupon'
		$entries = MainWPCustomPostTypeDB::Instance()->get_posts_id_and_title_by_ids( $id );

		if ( count( $entries ) != count( $id ) ) {
			$this->json_error( __( 'This entry does not exist', self::$plugin_translate ) );
		}

		if ( empty( $sites ) && empty( $groups ) ) {
			$this->json_error( __( "You doesn't select site or group", self::$plugin_translate ) );
		}

		global $mainwpCustomPostTypeExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpCustomPostTypeExtensionActivator->getChildFile(), $mainwpCustomPostTypeExtensionActivator->getChildKey(), $sites, $groups );

		if ( count( $dbwebsites ) == 0 ) {
			$this->json_error( __( "Sites you are select doesn't exist", self::$plugin_translate ) );
		}

		$return_ids           = array();
		$return_urls          = array();
		$return_entries       = array();
		$return_entries_names = array();

		foreach ( $dbwebsites as $website ) {
			$return_ids[]  = $website->id;
			$return_urls[] = $website->url;
		}

		foreach ( $entries as $entry ) {
			// It mightbe used to leak private post data
			if ( $entry->ID <= 0 || ! current_user_can( 'edit_post', $entry->ID ) ) {
				$this->json_error( __( 'You dont have priveleges to access this post', self::$plugin_translate ) );
			}

			$return_entries[]       = $entry->ID;
			$return_entries_names[] = $entry->post_title;
		}

		$this->json_ok(
			null,
			array(
				'ids'           => $return_ids,
				'urls'          => $return_urls,
				'entries'       => $return_entries,
				'entries_names' => $return_entries_names,
				'interval'      => 0,
			)
		);
	}

	/*
	 * Send synchronization datas to child
	 **/
	public function ajax_send_to_child_step_2() {
		$this->ajax_check_permissions( 'send_to_child_step_2' );

		global $mainwpCustomPostTypeExtensionActivator, $wpdb;

		$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );

		// $website = MainWP_DB::Instance()->getWebsiteById( $website_id );

		$website    = null;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpCustomPostTypeExtensionActivator->getChildFile(), $mainwpCustomPostTypeExtensionActivator->getChildKey(), array( $website_id ), array() );
		if ( $dbwebsites && is_array( $dbwebsites ) ) {
			$website = current( $dbwebsites );
		}

		if ( empty( $website ) ) {
			$this->json_error( __( 'Website does not exist', self::$plugin_translate ) );
		}

		$post_id = ( isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0 );

		if ( $post_id <= 0 || ! current_user_can( 'edit_post', $post_id ) ) {
			$this->json_error( __( 'You dont have priveleges to access this post', self::$plugin_translate ) );
		}

		$wp_post = MainWPCustomPostTypeDB::Instance()->get_post_by_id( $post_id );

		if ( empty( $wp_post ) ) {
			$this->json_error( __( 'Cannot find post', self::$plugin_translate ) );
		}

		$wp_postmeta = MainWPCustomPostTypeDB::Instance()->get_post_meta_by_post_id( $post_id );
		$wp_terms    = MainWPCustomPostTypeDB::Instance()->get_post_terms_by_post_id( $post_id );

		$return = array();

		$return['post']     = $this->boilerplate_support( $wp_post, $website );
		$return['postmeta'] = $this->boilerplate_support( $wp_postmeta, $website, 'postmeta' );
		$return['terms']    = $wp_terms;

		// We need this for uploading images
		$return['extras'] = array( 'upload_dir' => wp_upload_dir() );

		$post_featured_image = get_post_thumbnail_id( $post_id );
		if ( $post_featured_image !== false ) {
			$post_featured_image_src = wp_get_attachment_image_src( $post_featured_image, 'full' );
			if ( $post_featured_image_src !== false ) {
				$return['extras']['featured_image'] = $post_featured_image_src[0];
			}
		}

		// Woocommerce support
		$return['extras']['woocommerce'] = $this->_woocommerce( $post_id );

		// MainWP Categories
		$selected_categories  = ( isset( $_POST['selected_categories'] ) ? (array) $_POST['selected_categories'] : array() );
		$return['categories'] = $selected_categories;

		$return['post_only_existing'] = ( isset( $_POST['post_only_existing'] ) && $_POST['post_only_existing'] == '1' ? 1 : 0 );

		$product_variation = get_children( 'post_parent=' . $post_id . '&post_type=product_variation' );
		if ( ! empty( $product_variation ) && is_array( $product_variation ) ) {
			foreach ( $product_variation as $var_id => $var_data ) {
				$return['product_variation'][ $var_id ]['post']     = $var_data;
				$return['product_variation'][ $var_id ]['postmeta'] = MainWPCustomPostTypeDB::Instance()->get_post_meta_by_post_id( $var_id );
				$return['product_variation'][ $var_id ]['extras']   = array( 'upload_dir' => wp_upload_dir() );
				$post_variation_featured_image                      = get_post_thumbnail_id( $var_id );
				if ( $post_variation_featured_image !== false ) {
					$post_variation_featured_image_src = wp_get_attachment_image_src( $post_variation_featured_image, 'full' );
					if ( $post_variation_featured_image_src !== false ) {
						$return['product_variation'][ $var_id ]['extras']['featured_image'] = $post_variation_featured_image_src[0];
					}
				}
			}
		}

		$return_json = wp_json_encode( $return );

		$post_data = array(
			'action' => 'custom_post_type_import',
			'data'   => $return_json,
		);

		// Maybe we are editing this post ?
		$connection = MainWPCustomPostTypeDB::Instance()->get_connection_by_dash_and_website_id( $post_id, $website_id );
		if ( isset( $connection['child_post_id'] ) ) {
			$post_data['post_id'] = $connection['child_post_id'];
		}

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpCustomPostTypeExtensionActivator->getChildFile(), $mainwpCustomPostTypeExtensionActivator->getChildKey(), $website_id, 'custom_post_type', $post_data );

		if ( isset( $information['delete_connection'] ) ) {
			// User probably delete this post on child
			if ( isset( $connection['child_post_id'] ) ) {
				if ( MainWPCustomPostTypeDB::Instance()->delete_connection_by_child_post_id_and_website_id( $connection['child_post_id'], $website_id ) === false ) {
					$this->json_error( __( 'Cannot delete connection', self::$plugin_translate ) );
				}
			}
		}

		$this->check_child_response( $information, __( 'Cannot send datas to child', self::$plugin_translate ) );

		$child_post_id = $information['post_id'];

		if ( isset( $connection['child_post_id'] ) ) {
			if ( $child_post_id != $post_data['post_id'] ) {
				$this->json_error( __( 'Error on editing, it seems that we are edit wrong id', self::$plugin_translate ) );
			}
		} else {
			if ( MainWPCustomPostTypeDB::Instance()->insert_connection( $post_id, $child_post_id, $website_id ) === false ) {
				$this->json_error( __( 'Cannot insert connection', self::$plugin_translate ) );
			}
		}

		$this->json_ok(
			null,
			array(
				'test' => 'ok',
			)
		);
	}

	/**
	 * Support for WooCommerce images
	 **/
	private function _woocommerce( $post_id ) {
		$woocommerce = array();

		$product_image_gallery = get_post_meta( $post_id, '_product_image_gallery', true );
		$attachments           = array_filter( explode( ',', $product_image_gallery ) );
		if ( ! empty( $attachments ) ) {
			$woocommerce['product_images'] = array();
			foreach ( $attachments as $attachment_id ) {
				$product_image = wp_get_attachment_image_src( $attachment_id, 'full' );
				if ( $product_image !== false ) {
					$woocommerce['product_images'][] = $product_image[0];
				}
			}
		}

		return $woocommerce;
	}

	/**
	 * Display all entries displayed as ajax grid
	 */
	public function ajax_list() {
		global $wp_post_types;

		$this->ajax_check_permissions( 'list' );

		$type = ( isset( $_POST['type'] ) ? (string) $_POST['type'] : '' );

		switch ( $type ) {
			case 'entry':
				$entries = array();

				foreach ( get_post_types( array( '_builtin' => false ) ) as $custom_post_type_name ) {
					if ( in_array( $custom_post_type_name, self::$default_post_types ) ) {
						continue;
					}

					if ( isset( $wp_post_types[ $custom_post_type_name ]->label ) ) {
						$entries[] = array(
							'name'  => esc_html( $wp_post_types[ $custom_post_type_name ]->label ),
							'desc'  => esc_html( $wp_post_types[ $custom_post_type_name ]->description ),
							'value' => $custom_post_type_name,
						);
					} else {
						$entries[] = array(
							'name'  => esc_html( $custom_post_type_name ),
							'value' => $custom_post_type_name,
						);
					}
				}
				break;

			default:
				$this->json_error( __( 'Wrong type for ajax_list', self::$plugin_translate ) );
		}

		$this->json_ok(
			null,
			array(
				'entries' => $entries,
			)
		);
	}

	/**
	 * Checking permission
	 * If Team Control is installed - check extension priveleges
	 * Other case - check manage_options
	 * Additionally verify nonce token
	 */
	protected function ajax_check_permissions( $action, $get = false ) {
		if ( has_filter( 'mainwp_currentusercan' ) ) {
			if ( ! apply_filters( 'mainwp_currentusercan', true, 'extension', 'mainwp-custom-post-types' ) ) {
				$this->json_error( mainwp_do_not_have_permissions( 'MainWP Custom Post Type Extension ' . esc_html( $action ), false ) );
			}
		} else {
			if ( ! current_user_can( 'manage_options' ) ) {
				$this->json_error( mainwp_do_not_have_permissions( 'MainWP Custom Post Type Extension ' . esc_html( $action ), false ) );
			}
		}

		if ( $get ) {
			if ( ! isset( $_GET['wp_nonce'] ) || ! wp_verify_nonce( $_GET['wp_nonce'], self::$nonce_token . $action ) ) {
				$this->json_error( __( 'Error: Wrong or expired request. Please reload page', self::$plugin_translate ) );
			}
		} else {
			if ( ! isset( $_POST['wp_nonce'] ) || ! wp_verify_nonce( $_POST['wp_nonce'], self::$nonce_token . $action ) ) {
				$this->json_error( __( 'Error: Wrong or expired request. Please reload page', self::$plugin_translate ) );
			}
		}
	}

	/**
	 * @param $error
	 *
	 * Send error message through json
	 */
	public function json_error( $error ) {
		die( wp_send_json( array( 'error' => $error ) ) );
	}

	/**
	 * @param null      $message
	 * @param null      $data
	 * @param bool|true $die
	 *
	 * Send json ok message
	 */
	public function json_ok( $message = null, $data = null, $die = true ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		if ( is_null( $data ) ) {
			if ( is_null( $message ) ) {
				echo json_encode( array( 'success' => 1 ) );
			} else {
				echo json_encode( array( 'success' => $message ) );
			}
		} else {
			if ( is_null( $message ) ) {
				echo json_encode(
					array(
						'success' => 1,
						'data'    => $data,
					)
				);
			} else {
				echo json_encode(
					array(
						'success' => $message,
						'data'    => $data,
					)
				);
			}
		}
		if ( $die ) {
			die();
		}
	}

	/**
	 * @param $response
	 * @param $error_message
	 *
	 * Check if we receive error message from child
	 */
	protected function check_child_response( $response, $error_message ) {
		if ( ! isset( $response['success'] ) || $response['success'] != 1 ) {
			if ( isset( $response['error'] ) ) {
				$this->json_error( $response['error'] );
			} else {
				$this->json_error( $error_message );
			}
		}
	}

	public function init() {
		// Check permissions
		if ( ! $this->check_permissions() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		load_plugin_textdomain( self::$plugin_translate, false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * remove unwanted metaboxes
	 **/
	public function in_admin_header() {
		global $post_type;
		$post_type_object = get_post_type_object( $post_type );
		if ( isset( $post_type_object->_builtin ) && ! $post_type_object->_builtin && ! in_array( $post_type, self::$default_post_types ) ) {
			remove_meta_box( 'authordiv', $post_type, 'normal' );
			remove_meta_box( 'formatdiv', $post_type, 'side' );
		}
	}

	/**
	 * Print message in custom post types about our extension
	 **/
	public function admin_notices() {
		global $post_type;
		$post_type_object = get_post_type_object( $post_type );
		if ( isset( $post_type_object->_builtin ) && ! $post_type_object->_builtin && ! in_array( $post_type, self::$default_post_types ) ) {
			echo '<div class="updated"><p><a href="' . admin_url( 'admin.php?page=Extensions-Mainwp-Custom-Post-Types' ) . '" style="text-decoration: none;">' . __( 'Go to MainWP Custom Post Type Extension', self::$plugin_translate ) . '</a></p></div>';
		}
	}

	function admin_enqueue_scripts() {
		wp_register_script( $this->plugin_handle . 'angular-core', plugins_url( '../js/angular.min.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( $this->plugin_handle . 'ng-tasty', plugins_url( '../js/ng-tasty-tpls.min.js', __FILE__ ), array( $this->plugin_handle . 'angular-core' ) );
		wp_register_script( $this->plugin_handle . 'ng-sanitize', plugins_url( '../js/angular-sanitize.min.js', __FILE__ ), array( $this->plugin_handle . 'angular-core' ) );
		wp_register_script(
			$this->plugin_handle . 'app',
			plugins_url( '../js/app.js', __FILE__ ),
			array(
				$this->plugin_handle . 'ng-tasty',
				$this->plugin_handle . 'ng-sanitize',
			)
		);

		wp_localize_script( $this->plugin_handle . 'app', $this->plugin_handle . '_translations', $this->get_js_translations() );
		wp_localize_script( $this->plugin_handle . 'app', $this->plugin_handle . '_security_nonce', $this->get_nonce() );

		wp_enqueue_script( $this->plugin_handle . 'app' );

	}

	/**
	 * @return array
	 *
	 * Print translations for JS
	 */
	public function get_js_translations() {
		$translations = array();
		$this->add_translation( $translations, 'Child Sites have been updated', __( 'Child Sites have been updated', self::$plugin_translate ) );

		return $translations;
	}

	/**
	 * @param $array
	 * @param $key
	 * @param $val
	 *
	 * Add translations for JS
	 */
	private function add_translation( &$array, $key, $val ) {
		if ( ! is_array( $array ) ) {
			$array = array();
		}

		$text = str_replace( ' ', '_', $key );
		$text = preg_replace( '/[^A-Za-z0-9_]/', '', $text );

		$array[ $text ] = $val;
	}

	/**
	 * @return array
	 *
	 * Generate nonces
	 */
	public function get_nonce() {
		$nonce_ids = array(
			'save',
			'list',
			'send_to_child',
			'send_to_child_step_2',
		);

		$generated_nonce = array();

		foreach ( $nonce_ids as $id ) {
			$generated_nonce[ $id ] = wp_create_nonce( self::$nonce_token . $id );
		}

		return $generated_nonce;
	}

	/**
	 * Render wp-admin/admin.php?page=Extensions-Mainwp-Custom-Post-Types
	 */
	public function settings() {
		// Check permissions
		if ( ! $this->check_permissions() ) {
			return;
		}

		MainWPCustomPostTypeView::render_view();
	}

	/**
	 *
	 * If boilerplate plugin is installed, we support [tags]
	 *
	 * @param $content
	 * @param $website
	 * @param $what
	 *
	 * @return mixed
	 */
	public function boilerplate_support( $content, $website, $what = '' ) {
		$replace_tokens = apply_filters( 'mainwp_boilerplate_get_tokens', false, $website );
		if ( $replace_tokens ) {
			$tokens = array_keys( $replace_tokens );
			$values = array_values( $replace_tokens );
			if ( ! empty( $tokens ) ) {
				$output = array();
				if ( 'postmeta' == $what ) {
					foreach ( $content as $key => $meta ) {
						$meta['meta_value'] = str_replace( $tokens, $values, $meta['meta_value'] );
						$output[ $key ]     = $meta;
					}
				} else {
					foreach ( $content as $key => $val ) {
						$output[ $key ] = str_replace( $tokens, $values, $val );
					}
				}
				return $output;
			}
		}
		return $content;
	}
}
