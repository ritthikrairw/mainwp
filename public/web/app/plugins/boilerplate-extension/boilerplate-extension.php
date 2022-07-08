<?php
/*
 * Plugin Name: Boilerplate Extension
 * Plugin URI: https://mainwp.com
 * Description: MainWP Boilerplate extension allows you to create, edit and share repetitive pages across your child sites.
 * Version: 4.1
 * Author: MainWP
 * Author URI: https://mainwp.com/
 * Documentation URI: https://kb.mainwp.com/docs/category/mainwp-extensions/boilerplate/
 */

if ( ! defined( 'MAINWP_BOILERPLATE_PLUGIN_FILE' ) ) {
	define( 'MAINWP_BOILERPLATE_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MAINWP_BOILERPLATE_URL' ) ) {
	define( 'MAINWP_BOILERPLATE_URL', plugins_url( '', __FILE__ ) );
}

class BoilerplateExtension {
	public static $instance = null;
	public $ver             = '2.0';

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		Boilerplate_DB::get_instance()->install();

		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'mainwp_main_menu_submenu', array( $this, 'init_menu_submenu' ), 10, 1 );

		add_filter( 'mainwp_boilerplate_get_tokens', array( &$this, 'hook_get_tokens' ), 10, 2 );

		add_filter( 'mainwp_pre_posting_posts', array( 'BoilerplateExtension', 'boilerplate_pre_posting' ), 8, 2 );
		add_action( 'mainwp_extension_sites_edit_tablerow', array( 'BoilerplateExtension', 'render_edit_site_tokens' ), 10, 1 );

		if ( isset( $_GET['boilerplate'] ) ) {
			add_filter( 'mce_buttons', array( &$this, 'boilerplate_register_buttons' ) );
			require_once 'includes/functions.php';
			add_action( 'admin_print_footer_scripts', 'mainwp_boilerplate_admin_print_footer_scripts' );
		}

		add_action( 'mainwp_help_sidebar_content', array( &$this, 'mainwp_help_content' ) );
	}

	public function admin_init() {

		if ( isset( $_GET['page'] ) && ( 'Extensions-Boilerplate-Extension' == $_GET['page'] || 'managesites' == $_GET['page'] ) ) {
			wp_enqueue_script( 'mainwp-boilerplate-extension', MAINWP_BOILERPLATE_URL . '/js/boilerplate-ext.js', array( 'jquery' ), $this->ver );
		}

		wp_enqueue_style( 'mainwp-boilerplate-extension', MAINWP_BOILERPLATE_URL . '/css/boilerplate-extension.css' );


		Boilerplate_Post::get_instance()->admin_init();

		add_action( 'mainwp_update_site', array( &$this, 'mainwp_update_site' ), 8, 1 );
		add_action( 'mainwp_delete_site', array( &$this, 'mainwp_delete_site' ), 8, 1 );
		add_action( 'mainwp_bulkpost_edit', array( &$this, 'bulkpost_edit_boilerplate' ), 10, 2 );
		add_filter( 'mainwp_page_navigation', array( $this, 'init_page_navigation' ), 10, 2 );
	}


	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		$plugin_slug = plugin_basename( __FILE__ );

		if ( $plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">' . __( 'Check for updates now', 'boilerplate-extension' ) . '</a>';

		return $plugin_meta;
	}

	public function init_menu_submenu( $subs ) {
		if ( is_array( $subs ) && isset( $subs['PostBulkManage'] ) ) {
			$subs['PostBulkManage'][] = array( __( 'Add New Boilerplate' ), 'admin.php?page=PostBulkAdd&boilerplate=1', ( $right = false ) );
		}
		if ( is_array( $subs ) && isset( $subs['PageBulkManage'] ) ) {
			$subs['PageBulkManage'][] = array( __( 'Add New Boilerplate' ), 'admin.php?page=PageBulkAdd&boilerplate=1', ( $right = false ) );
		}
		return $subs;
	}

	public function init_page_navigation( $sub_headers, $caller_class = false ) {
		$caller_class = str_replace( 'MainWP\\Dashboard\\', '', $caller_class ); // remove namespace string.
		$add_header = false;
		if ( $caller_class == 'MainWP_Page' ) {
			$add_header = true;
			$add_item   = array(
				'href'  => 'admin.php?page=PageBulkAdd&boilerplate=1',
				'title' => __( 'Add New Boilerplate', 'boilerplate-extension' ),
			);

			$edit_item = false;

			if ( isset( $_GET['page'] ) && $_GET['page'] == 'PageBulkEdit' && isset( $_GET['boilerplate'] ) && $_GET['boilerplate'] ) {
				$edit_item = array(
					'href'  => 'admin.php?page=PageBulkEdit&boilerplate=1&post_id=' . $_GET['post_id'],
					'title' => __( 'Edit Boilerplate', 'boilerplate-extension' ),
				);
			}
		} elseif ( $caller_class == 'MainWP_Post' ) {
				$add_header = true;

				$add_item = array(
					'href'  => 'admin.php?page=PostBulkAdd&boilerplate=1',
					'title' => __( 'Add New Boilerplate', 'boilerplate-extension' ),
				);

				$edit_item = false;
				if ( isset( $_GET['page'] ) && $_GET['page'] == 'PostBulkEdit' && isset( $_GET['boilerplate'] ) && $_GET['boilerplate'] ) {
					$edit_item = array(
						'href'  => 'admin.php?page=PostBulkEdit&boilerplate=1&post_id=' . intval( $_GET['post_id'] ),
						'title' => __( 'Edit Boilerplate', 'boilerplate-extension' ),
					);
				}
		}

		if ( $add_header ) {
			$active = false;
			if ( isset( $_GET['boilerplate'] ) && $_GET['boilerplate'] ) {
				$active = true;
			}

			if ( $active ) {
				for ( $i = 0; $i < count( $sub_headers ); $i++ ) {
					if ( isset( $sub_headers[ $i ] ) && isset( $sub_headers[ $i ]['active'] ) ) {
						unset( $sub_headers[ $i ]['active'] );
					}
				}

				if ( $edit_item ) {
					$edit_item['active'] = true;
				} else {
					$add_item['active'] = true;
				}
			}

			$sub_headers[] = $add_item;
			if ( empty( $edit_item ) ) {
				$sub_headers[] = $edit_item;
			}
		}

		return $sub_headers;
	}


	public function extension_enabled( $input = false ) {
		return true;
	}

	public static function boilerplate_pre_posting( $params, $website ) {

		$new_post = unserialize( base64_decode( $params['new_post'] ) );

		if ( isset( $new_post['mainwp_post_id'] ) && $post_id = $new_post['mainwp_post_id'] ) {
			if ( 'yes' == get_post_meta( $post_id, '_mainwp_boilerplate', true ) ) {
				$tokens        = Boilerplate_DB::get_instance()->get_tokens();
				$site_tokens   = Boilerplate_DB::get_instance()->get_indexed_site_tokens( $website );
				$search_tokens = $replace_values = array();

				foreach ( $tokens as $token ) {
					$search_tokens[]  = '[' . $token->token_name . ']';
					$replace_values[] = isset( $site_tokens[ $token->id ] ) ? $site_tokens[ $token->id ]->token_value : '';
				}

				$new_post['post_content'] = str_replace( $search_tokens, $replace_values, $new_post['post_content'] );
				$new_post['post_title']   = str_replace( $search_tokens, $replace_values, $new_post['post_title'] );

				$sites_posts = get_post_meta( $post_id, '_mainwp_boilerplate_sites_posts', true );
				if ( is_array( $sites_posts ) && isset( $sites_posts['previous_posts'] ) ) {
					$previous_posts = $sites_posts['previous_posts'];
					if ( is_array( $previous_posts ) ) {
						if ( isset( $previous_posts[ $website->id ] ) && isset( $previous_posts[ $website->id ]['post_id'] ) && $previous_posts[ $website->id ]['post_id'] ) {
							$new_post['ID'] = $previous_posts[ $website->id ]['post_id'];
						}
					}
				}

				$params['new_post'] = base64_encode( serialize( $new_post ) );
			}
		}
		return $params;
	}


	public static function hook_get_tokens( $false, $website ) {

		$tokens      = Boilerplate_DB::get_instance()->get_tokens();
		$site_tokens = Boilerplate_DB::get_instance()->get_indexed_site_tokens( $website );

		$return_tokens = array();
		foreach ( $tokens as $token ) {
			$return_tokens[ '[' . $token->token_name . ']' ] = isset( $site_tokens[ $token->id ] ) ? $site_tokens[ $token->id ]->token_value : '';
		}

		return $return_tokens;
	}

	public static function render_edit_site_tokens( $website ) {

		if ( empty( $website ) ) {
			return;
		}

		$tokens = Boilerplate_DB::get_instance()->get_tokens();

		$site_tokens = array();

		if ( $website ) {
			$site_tokens = Boilerplate_DB::get_instance()->get_indexed_site_tokens( $website );
		}

		if ( is_array( $tokens ) && count( $tokens ) > 0 ) {
			?>
			<h3 class="ui dividing header"><?php echo __( 'Boilerplate Tokens', 'boilerplate-extension' ); ?> </h3>
			<?php if ( self::show_mainwp_message( 'mainwp_boilerplate_site_notice' ) ) : ?>
			<div class="ui message info">
				<i class="close icon mainwp-notice-dismiss" notice-id="mainwp_boilerplate_site_notice"></i>
				<?php echo sprintf( __( 'Enter token values for the child site. Tokens used in Boilerplate posts and pages will be replaced with the values that you set here. Please review the %shelp document%s for detailed information.', 'boilerplate-extension' ), '<a href="https://kb.mainwp.com/docs/boilerplate-tokens/" target="_blank">', '</a>' ); ?>
			</div>
			<?php endif; ?>
			<div class="ui form">
			<?php
			foreach ( $tokens as $token ) {
				if ( ! $token ) {
					continue;
				}
				$token_value = '';
				if ( isset( $site_tokens[ $token->id ] ) && $site_tokens[ $token->id ] ) {
					$token_value = htmlspecialchars( stripslashes( $site_tokens[ $token->id ]->token_value ) );
				}

				$input_name = 'bpl_value_' . str_replace( array( '.', ' ', '-' ), '_', $token->token_name );
				?>
				<div class="ui grid field">
					<label class="six wide column middle aligned">[<?php echo stripslashes( $token->token_name ); ?>]</label>
					<div class="ui six wide column" data-tooltip="<?php echo __( 'Enter the', 'boilerplate-extension' ) . ' ' . stripslashes( $token->token_name ) . ' ' . __( 'token value for this child site.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="top left">
						<div class="ui left labeled input">
							<input type="text" value="<?php echo $token_value; ?>" class="regular-text" name="<?php echo $input_name; ?>"/>
						</div>
					</div>
				</div>
				<?php
				}
				?>
				<div class="ui grid field">
					<label class="six wide column middle aligned"></label>
					<div class="ui six wide column" data-tooltip="<?php echo __( 'Click the button to create new custom tokens for the Boilerplate extension.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="top left">
						<div class="ui left labeled input">
							<a href="admin.php?page=Extensions-Boilerplate-Extension&tab=tokens" class="ui basic green button"><?php esc_html_e( 'Create New Token', 'boilerplate-extension' ); ?></a>
						</div>
					</div>
				</div>
				</div>
			<?php
		}
	}

	/**
	 * Method show_mainwp_message()
	 *
	 * Check whenther or not to show the MainWP Message.
	 * @param mixed $notice_id Notice ID.
	 *
	 * @return bool true|false.
	 */
	public static function show_mainwp_message( $notice_id ) {
		$status = get_user_option( 'mainwp_notice_saved_status' );
		if ( ! is_array( $status ) ) {
			$status = array();
		}
		if ( isset( $status[ $notice_id ] ) ) {
			return false;
		}
		return true;
	}

	public function format_post_status( $status ) {

		if ( 'publish' == $status ) {
			return 'Published';
		} elseif ( 'Future' == $status ) {
			return 'Scheduled';
		}
		return ucfirst( $status );
	}

	public function mainwp_update_site( $websiteId ) {

		global $mainWPBoilerplateExtensionActivator;

		if ( isset( $_POST['submit'] ) ) {
			if ( ! $websiteId ) {
				return;
			}

			// to fix compatible
			$website    = apply_filters( 'mainwp_getsites', $mainWPBoilerplateExtensionActivator->get_child_file(), $mainWPBoilerplateExtensionActivator->get_child_key(), $websiteId );
			$websiteUrl = '';

			if ( $website && is_array( $website ) ) {
				$website    = current( $website );
				$websiteUrl = $website['url'];
			}

			$tokens = Boilerplate_DB::get_instance()->get_tokens();

			foreach ( $tokens as $token ) {
				$input_name = 'bpl_value_' . str_replace( array( '.', ' ', '-' ), '_', $token->token_name );
				if ( isset( $_POST[ $input_name ] ) ) {
					Boilerplate_DB::get_instance()->try_to_fix_compatible( $websiteId, $token->id, $websiteUrl );
					$token_value = trim( $_POST[ $input_name ] );
					Boilerplate_DB::get_instance()->update_token_site( $websiteId, $token->id, $token_value );
				}
			}
		}

	}

	public function mainwp_delete_site( $website ) {
		if ( $website ) {
			Boilerplate_DB::get_instance()->delete_tokens_of_site_by( 'site_id', $website->id );
		}
	}

	public function render_settings() {

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'tokens' ) {
				$current_tab = 'tokens';
			} elseif ( $_GET['tab'] == 'boilerplate-posts' ) {
				$current_tab = 'boilerplate-posts';
			} elseif ( $_GET['tab'] == 'boilerplate-pages' ) {
				$current_tab = 'boilerplate-pages';
			}
		}

		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-boilerplate-menu">
			<a href="admin.php?page=Extensions-Boilerplate-Extension&tab=boilerplate-posts" class="<?php echo ( $current_tab == 'boilerplate-posts' || '' == $current_tab ? 'active' : '' ); ?> item"><i class="file outline icon"></i> <?php _e( 'Boilerplate Posts', 'boilerplate-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Boilerplate-Extension&tab=boilerplate-pages" class="<?php echo ( $current_tab == 'boilerplate-pages' ? 'active' : '' ); ?> item"><i class="file icon"></i> <?php _e( 'Boilerplate Pages', 'boilerplate-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Boilerplate-Extension&tab=tokens" class="<?php echo ( $current_tab == 'tokens' ? 'active' : '' ); ?> item"><i class="code icon"></i> <?php _e( 'Custom Tokens', 'boilerplate-extension' ); ?></a>
	  </div>
	  <div class="ui segment">
			<div class="ui message" id="mainwp-message-zone" style="display:none"></div>
			<?php if ( isset( $_REQUEST['message'] ) ) : ?>
			  <div class="ui green message">
				<?php
				switch ( $_REQUEST['message'] ) {
					case '1':
						_e( 'Settings saved successfully!.', 'boilerplate-extension' );
						break;
					case '2':
						_e( 'Import completed successfully!', 'boilerplate-extension' );
						break;
					case '3':
						_e( 'Data cleared successfully!', 'boilerplate-extension' );
						break;
					case '-1':
						_e( 'An error occured while trying to save. Please try again.', 'boilerplate-extension' );
						break;
					case '-2':
						_e( 'Invalid data. Please try again.', 'boilerplate-extension' );
				}
				?>
			  </div>
			<?php endif; ?>

			<?php if ( 'tokens' == $current_tab ) : ?>
			<div>
				<?php if ( self::show_mainwp_message( 'mainwp_boilerplate_tokens_info_notice' ) ) : ?>
				<div class="ui message info">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp_boilerplate_tokens_info_notice"></i>
					<div><?php echo sprintf( __( 'Manage existing Boilerplate tokens and create custom ones. Please review the %shelp document%s for detailed information.', 'boilerplate-extension' ), '<a href="https://kb.mainwp.com/docs/default-boilerplate-tokens/" target="_blank">', '</a>' ); ?></div>
				</div>
				<?php endif; ?>
			  <div id="mainwp-boilerplate-tokens">
				<?php $tokens = Boilerplate_DB::get_instance()->get_tokens(); ?>
				<table class="ui stackable single line compact table" id="mainwp-boilerplate-tokens-table">
				  <thead>
						<tr>
						  <th><?php echo __( 'Token Name', 'boilerplate-extension' ); ?></th>
						  <th><?php echo __( 'Token Description', 'boilerplate-extension' ); ?></th>
						  <th class="collapsing no-sort"></th>
						</tr>
				  </thead>
				  <tbody>
					<?php foreach ( (array) $tokens as $token ) : ?>
						<?php
						if ( ! $token ) {
							continue;}
						?>
					  <tr class="mainwp-token" token_id="<?php echo $token->id; ?>">
							<td class="token-name">[<?php echo stripslashes( $token->token_name ); ?>]</td>
							<td class="token-description"><?php echo stripslashes( $token->token_description ); ?></td>
							<td>
							  <div class="ui left pointing dropdown icon mini basic green button">
								<i class="ellipsis horizontal icon"></i>
								<div class="menu">
									<a class="item" id="mainwp-boilerplate-edit-token" href="#"><?php _e( 'Edit', 'boilerplate-extension' ); ?></a>
									<a class="item" id="mainwp-boilerplate-delete-token" href="#"><?php _e( 'Delete', 'boilerplate-extension' ); ?></a>
								  </div>
								</div>
							</td>
					  </tr>
					<?php endforeach; ?>
				  </tbody>
				  <tfoot>
					<tr class="full width">
					  <th colspan="3">
							<a href="#" class="ui green mini button" id="mainwp-boilerplate-new-token-button"><?php echo __( 'Create New Token', 'boilerplate-extension' ); ?></a>
					  </th>
					</tr>
				  </tfoot>
				</table>

				<div class="ui modal" id="mainwp-boilerplate-new-token-modal">
				  <div class="header"><?php echo __( 'Boilerplate Token', 'boilerplate-extension' ); ?></div>
				  <div class="content ui mini form">
						<div class="ui yellow message" style="display:none"></div>
						<div class="field">
						  <label><?php _e( 'Token Name', 'boilerplate-extension' ); ?></label>
						  <input type="text" value="" id="token-name" name="token-name" placeholder="<?php esc_attr_e( 'Enter token name (without of square brackets)', 'boilerplate-extension' ); ?>">
						</div>
						<div class="field">
						  <label><?php _e( 'Token Description', 'boilerplate-extension' ); ?></label>
						  <input type="text" value="" id="token-description" name="token-description" placeholder="<?php esc_attr_e( 'Enter token description', 'boilerplate-extension' ); ?>">
						</div>
				  </div>
				  <div class="actions">
						<div class="ui two columns grid">
							<div class="left aligned column">
								<input type="button" class="ui green button" id="mainwp-boilerplate-create-new-token" value="<?php esc_attr_e( 'Save Token', 'boilerplate-extension' ); ?>">
							</div>
							<div class="column">
								<div class="ui cancel button"><?php echo __( 'Close', 'boilerplate-extension' ); ?></div>
							</div>
						</div>
				  </div>
				</div>

				<div class="ui modal" id="mainwp-boilerplate-update-token-modal">
				  <div class="header"><?php echo __( 'Boilerplate Token', 'boilerplate-extension' ); ?></div>
				  <div class="content ui mini form">
						<div class="ui yellow message" style="display:none"></div>
						<div class="field">
						  <label><?php _e( 'Token Name', 'boilerplate-extension' ); ?></label>
						  <input type="text" value="" id="token-name" name="token-name" placeholder="<?php esc_attr_e( 'Enter token name (without of square brackets)', 'boilerplate-extension' ); ?>">
						</div>
						<div class="field">
						  <label><?php _e( 'Token Description', 'boilerplate-extension' ); ?></label>
						  <input type="text" value="" id="token-description" name="token-description" placeholder="<?php esc_attr_e( 'Enter token description', 'boilerplate-extension' ); ?>">
						</div>
						<input type="hidden" value="" id="token-id" name="token-id">
				  </div>
				  <div class="actions">
						<div class="ui two columns grid">
							<div class="left aligned column">
								<input type="button"  class="ui green button" id="mainwp-save-boilerplate-token" value="<?php esc_attr_e( 'Save Token', 'boilerplate-extension' ); ?>">
							</div>
							<div class="column">
								<div class="ui cancel button"><?php echo __( 'Close', 'boilerplate-extension' ); ?></div>
							</div>
						</div>
				  </div>
				</div>
			  </div>
			</div>
			<?php endif; ?>

			<?php if ( 'boilerplate-posts' == $current_tab || '' == $current_tab )  : ?>
			<div>
				<?php if ( self::show_mainwp_message( 'mainwp_boilerplate_boilerplate_posts_info_notice' ) ) : ?>
				<div class="ui message info">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp_boilerplate_boilerplate_posts_info_notice"></i>
					<div><?php echo __( 'Boilerplate gives you the ability to create quickly, edit, and remove repetitive pages or posts across your network of child sites. Using the available placeholders (tokens), you can customize these pages for each site.', 'boilerplate-extension' ); ?></div>
					<p><?php echo __( 'This is the perfect solution for commonly repeated pages such as your “Privacy Policy,” “About Us,” “Terms of Use,” “Support Policy,” or any other page with standard text that you need to distribute across your network.', 'boilerplate-extension' ); ?></p>
					<p><?php echo sprintf( __( 'Manage existing Boilerplate Posts. Please review the %shelp document%s for detailed information.', 'boilerplate-extension' ), '<a href="https://kb.mainwp.com/docs/boilerplate-posts/" target="_blank">', '</a>' ); ?></p>
				</div>
				<?php endif; ?>
			  <div id="mainwp-boilerplate-posts">
				<?php
				$args = array(
					'post_type'      => 'bulkpost',
					'posts_per_page' => -1,
					'post_status'    => array( 'publish', 'pending', 'future', 'private', 'draft' ),
					'post_parent'    => null,
					'meta_key'       => '_mainwp_boilerplate',
					'meta_value'     => 'yes',
				);
			  $boilerplate_posts = get_posts( $args );
				?>
				<table id="mainwp-boilerplate-posts-table" class="ui stackable table">
				  <thead>
					<tr>
					  <th><?php _e( 'Post Title', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Status', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Slug', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Author', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Date', 'boilerplate-extension' ); ?></th>
					  <th class="collapsing no-sort"></th>
					</tr>
				  </thead>
				  <tbody>
					<?php $this->render_boilerplates_table_content( $boilerplate_posts, 'bulkpost' ); ?>
				  </tbody>
				  <tfoot>
					<tr class="full width">
					  <th colspan="6">
						<a href="admin.php?page=PostBulkAdd&boilerplate=1" class="ui green mini button"><?php echo __( 'Create Boilerplate Post', 'boilerplate-extension' ); ?></a>
					  </th>
					</tr>
				  </tfoot>
				</table>
			  </div>
			</div>
		<?php endif; ?>

		<?php if ( 'boilerplate-pages' == $current_tab ) : ?>
			<div>
				<?php if ( self::show_mainwp_message( 'mainwp_boilerplate_boilerplate_pages_info_notice' ) ) : ?>
				<div class="ui message info">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp_boilerplate_boilerplate_pages_info_notice"></i>
					<div><?php echo __( 'Boilerplate gives you the ability to create quickly, edit, and remove repetitive pages or posts across your network of child sites. Using the available placeholders (tokens), you can customize these pages for each site.', 'boilerplate-extension' ); ?></div>
					<p><?php echo __( 'This is the perfect solution for commonly repeated pages such as your “Privacy Policy,” “About Us,” “Terms of Use,” “Support Policy,” or any other page with standard text that you need to distribute across your network.', 'boilerplate-extension' ); ?></p>
					<p><?php echo sprintf( __( 'Manage existing Boilerplate Pages. Please review the %shelp document%s for detailed information.', 'boilerplate-extension' ), '<a href="https://kb.mainwp.com/docs/boilerplate-pages/" target="_blank">', '</a>' ); ?></p>
				</div>
				<?php endif; ?>
			  <div id="mainwp-boilerplate-pages">
				<?php
				$args              = array(
					'post_type'      => 'bulkpage',
					'posts_per_page' => -1,
					'post_status'    => array( 'publish', 'pending', 'future', 'private', 'draft' ),
					'post_parent'    => null,
					'meta_key'       => '_mainwp_boilerplate',
					'meta_value'     => 'yes',
				);
				$boilerplate_pages = get_posts( $args );
				?>
				<table id="mainwp-boilerplate-pages-table" class="ui stackable table">
				  <thead>
					<tr>
					  <th><?php _e( 'Page Title', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Status', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Slug', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Author', 'boilerplate-extension' ); ?></th>
					  <th><?php _e( 'Date', 'boilerplate-extension' ); ?></th>
					  <th class="collapsing no-sort"></th>
					</tr>
				  </thead>
					<tbody>
					<?php $this->render_boilerplates_table_content( $boilerplate_pages, 'bulkpage' ); ?>
				  </tbody>
				  <tfoot>
					<tr class="full width">
					  <th colspan="6">
						  <a href="admin.php?page=PageBulkAdd&boilerplate=1" class="ui green mini button"><?php echo __( 'Create Boilerplate Page', 'boilerplate-extension' ); ?></a>
					  </th>
					</tr>
				  </tfoot>
				</table>
			  </div>
			</div>
			<?php endif; ?>
	  </div>
	  <script type="text/javascript">

		jQuery( document ).ready( function( $ ) {
		  jQuery( '#mainwp-boilerplate-new-token-button' ).on( 'click', function () {
			jQuery( '#mainwp-boilerplate-new-token-modal' ).modal( 'show' );
		  } );
		  jQuery( 'table' ).DataTable( {
			"colReorder" : true,
			"stateSave":  true,
			"pagingType": "full_numbers",
			"order": [],
			"language": { "emptyTable": "No boilerplates created yet." },
			"columnDefs": [ {
			  "targets": 'no-sort',
			  "orderable": false
			} ]
		  } );
		} );
		</script>
		<?php
	}

	public function bulkpost_edit_boilerplate( $post, $post_type ) {
		if ( empty( $post ) ) {
			return;
		}

		if ( ! isset( $_GET['boilerplate'] ) || 1 != $_GET['boilerplate'] ) {
			if ( 'yes' != get_post_meta( $post->ID, '_mainwp_boilerplate', true ) ) {
				return;
			}
		}

		?>
		<div class="ui fitted divider"></div>
		<div class="ui fluid accordion mainwp-sidebar-accordion" style="padding:10px">
			<div class="title"><i class="dropdown icon"></i> <?php echo __( 'Boilerplate Tokens', 'boilerplate-extension' ); ?></div>
			<div class="content ui segment">
				<table class="ui very basic table">
					<tbody>
					  <?php
						$tokens = Boilerplate_DB::get_instance()->get_tokens();
						foreach ( (array) $tokens as $token ) :
							if ( ! $token ) {
								continue;
							}
							?>
						<tr>
							<td class="token-name"><a class="bpl_post_add_token" data-tooltip="<?php esc_attr_e( 'Click to insert this tokent to the post editor in the place of the cursor.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="top left">[<?php echo stripslashes( $token->token_name ); ?>]</a></td>
							<td class="token-description"><?php echo stripslashes( $token->token_description ); ?></td>
					  </tr>
						<?php endforeach; ?>
						<tr>
							<td><a class="ui green mini basic button" href="admin.php?page=Extensions-Boilerplate-Extension&tab=tokens" data-tooltip="<?php esc_attr_e( 'Click to go to the extension settings to create a new custom token.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="right center"><?php echo __( 'Create New Token', 'boilerplate-extension' ); ?></a></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div class="ui fitted divider"></div>

	  <input type="hidden" name="mainwp_boilerplate_nonce" value="<?php echo wp_create_nonce( 'boilerplate_' . $post->ID ); ?>">
	  <script type="text/javascript">
			jQuery( document ).ready( function($) {
				window.currFocus = document;
				$( window ).on( 'focusin', function () {
					window.prevFocus = window.currFocus;
					window.currFocus = document.activeElement;
				} );

				function bpl_getPos(obj) {
					var pos = 0;
					// IE Support
					if ( document.selection ) {
						obj.focus ();
						var range = document.selection.createRange ();
						range.moveStart ( 'character', -obj.value.length );
						pos = range.text.length;
					}
					// Firefox support
					else if ( obj.selectionStart || obj.selectionStart == '0' )
					pos = obj.selectionStart;
						return ( pos );
				}

				$( 'a.bpl_post_add_token' ).on( 'click', function( e ) {
					var replace_text = jQuery( this ).html();
					if ( window.prevFocus === document.getElementById( 'title' ) ) {
						var titleObj = $( 'input[name="post_title"]' );
						var str = titleObj.val();
						var pos = bpl_getPos( titleObj[0] );
						str = str.substring( 0,pos ) + replace_text + str.substring( pos, str.length )
						titleObj.val( str );
					} else {
						tinyMCE.execCommand( 'mceInsertContent', false, replace_text );
					}
				} );
			} );
		</script>
		<?php
	}

	public function render_boilerplates_table_content( $boilerplates, $type ) {

		$page_slug = 'PostBulkEdit';

		if ( 'bulkpage' == $type ) {
			$page_slug = 'PageBulkEdit';
		}

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		foreach ( $boilerplates as $boilerplate ) :
			$author_info = get_userdata( $boilerplate->post_author );
			$post_time   = strtotime( $boilerplate->post_date );
			?>
			<tr id="post-<?php echo $boilerplate->ID; ?>">
				<td>
					<input type="hidden" value="<?php echo get_edit_post_link( $boilerplate->ID, true ); ?>" name="id" class="postId">
					<a href="?page=<?php echo $page_slug; ?>&post_id=<?php echo $boilerplate->ID; ?>&boilerplate=1" data-tooltip="<?php echo __( 'Click to edit the boilerplate.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $boilerplate->post_title; ?></a>
				</td>
				<td><span data-tooltip="<?php echo __( 'Boilerplate status.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $this->format_post_status( $boilerplate->post_status ); ?></span></td>
				<td><span data-tooltip="<?php echo __( 'Boilerplate slug.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $boilerplate->post_name; ?></span></td>
				<td><span data-tooltip="<?php echo __( 'Boilerplate author.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $author_info->user_login; ?></span></td>
				<td><span data-tooltip="<?php echo __( 'Published on: ', 'boilerplate-extension' ); ?>" data-inverted="" data-position="left center"><?php echo date( $date_format, $post_time ) . ' ' . date( $time_format, $post_time ); ?></span></td>
				<td class="right aligned">
					<div class="ui left pointing dropdown icon mini basic green button" data-tooltip="<?php echo __( 'See more options.', 'boilerplate-extension' ); ?>" data-inverted="" data-position="left center">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a href="?page=<?php echo $page_slug; ?>&post_id=<?php echo $boilerplate->ID; ?>&boilerplate=1" class="item"><?php _e( 'Edit', 'boilerplate-extension' ); ?></a>
							<a href="#" boilerplate-id=<?php echo $boilerplate->ID; ?> class="item mainwp-boilerplate-delete-post-action"><?php _e( 'Delete', 'boilerplate-extension' ); ?></a>
						</div>
					</div>
				</td>
			</tr>
		<?php
		endforeach;
	}

	public function boilerplate_register_buttons( $buttons ) {
	   array_push( $buttons, 'inserttoken' );
	   return $buttons;
	}

	/**
	 * Hooks the section help content to the Help Sidebar element.
	 */
	public static function mainwp_help_content() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Boilerplate-Extension' === $_GET['page'] ) ) {
			?>
			<p><?php esc_html_e( 'If you need help with managing boilerplates, please review following help documents', 'mainwp' ); ?></p>
			<div class="ui relaxed bulleted list">
				<div class="item"><a href="https://kb.mainwp.com/docs/boilerplate-tokens/" target="_blank">Boilerplate Tokens</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/default-boilerplate-tokens/" target="_blank">Default Tokens</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/custom-boilerplate-tokens/" target="_blank">Custom Tokens</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/boilerplate-posts/" target="_blank">Boilerplate Posts</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/create-boilerplate-post/" target="_blank">Create Boilerplate Post</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/edit-existing-boilerplate-post/" target="_blank">Edit Existing Boilerplate Post</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/add-existing-boilerplate-post-to-a-new-site/" target="_blank">Add Existing Boilerplate Post to a New Site</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/remove-a-boilerplate-post-from-a-child-site/" target="_blank">Remove a Boilerplate Post from a Child Site</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/delete-a-boilerplate-post/" target="_blank">Delete a Boilerplate Post</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/boilerplate-pages/" target="_blank">Boilerplate Pages</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/create-boilerplate-page/" target="_blank">Create Boilerplate Page</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/edit-existing-boilerplate-page/" target="_blank">Edit Existing Boilerplate Page</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/add-existing-boilerplate-page-to-a-new-site/" target="_blank">Add Existing Boilerplate Page to a New Site</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/remove-a-boilerplate-page-from-a-child-site/" target="_blank">Remove a Boilerplate Page from a Child Site</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/delete-a-boilerplate-page/" target="_blank">Delete a Boilerplate Page</a></div>
				<?php
				/**
				 * Action: mainwp_themes_help_item
				 *
				 * Fires at the bottom of the help articles list in the Help sidebar on the Themes page.
				 *
				 * Suggested HTML markup:
				 *
				 * <div class="item"><a href="Your custom URL">Your custom text</a></div>
				 *
				 * @since 4.1
				 */
				do_action( 'mainwp_comments_help_item' );
				?>
			</div>
			<?php
		}
	}

}

class BoilerplateExtensionActivator {
	protected $mainwpMainActivated = false;
	protected $childEnabled        = false;
	protected $childKey            = false;
	protected $childFile;
	protected $plugin_handle    = 'boilerplate-extension';
	protected $product_id       = 'Boilerplate Extension';
	protected $software_version = '4.1';

	public function __construct() {

		$this->childFile = __FILE__;

		$this->includes();
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );

		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this' ) );
		}
		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

	public function includes() {
		require_once 'class/boilerplate-db.class.php';
		require_once 'class/boilerplate-post.class.php';
	}


	public function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'     => __FILE__,
			'api'        => $this->plugin_handle,
			'mainwp'     => true,
			'callback'   => array( &$this, 'settings' ),
			'apiManager' => true,
		);

		return $pArray;
	}

	public function settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		BoilerplateExtension::get_instance()->render_settings();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}


	public function activate_this() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
		$this->childEnabled        = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
		$this->childKey            = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'boilerplate-extension' ) ) {
			return;
		}
		new BoilerplateExtension();
	}

	public function get_enable_status() {
		return $this->childEnabled;
	}

	public function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>Boilerplate Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
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

global $mainWPBoilerplateExtensionActivator;
$mainWPBoilerplateExtensionActivator = new BoilerplateExtensionActivator();
