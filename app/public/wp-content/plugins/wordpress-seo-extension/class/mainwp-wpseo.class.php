<?php
class MainWP_WPSeo {
	static $orderby = 'name';
	static $order   = 'desc';

	public static $instance = null;

	public function __construct() {

	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		self::handle_save_template();
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_wpseo_delete_template', array( $this, 'ajax_delete_template' ) );
		add_action( 'wp_ajax_mainwp_wpseo_set_template_loading', array( $this, 'ajax_loading_sites' ) );
		add_action( 'wp_ajax_mainwp_wpseo_set_template', array( $this, 'ajax_set_template' ) );
		add_action( 'wp_ajax_mainwp_seo_active_plugin', array( $this, 'active_plugin' ) );
		add_action( 'wp_ajax_mainwp_seo_upgrade_plugin', array( $this, 'upgrade_plugin' ) );

		add_action( 'mainwp_edit_posts_before_submit_button', array( &$this, 'wpseo_init_scripts' ) );
		add_action( 'mainwp_save_bulkpost', array( &$this, 'save_meta_bulkpost' ), 10, 1 );
		add_action( 'mainwp_save_bulkpage', array( &$this, 'save_meta_bulkpost' ), 10, 1 );

	}

	public function save_meta_bulkpost( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id; }

		// if ( isset( $_POST['mainwp_wpseo_metabox_save_values'] ) && ( ! empty( $_POST['mainwp_wpseo_metabox_save_values'] ) ) ) {
		// return;
		// }

		$seo_metabox = false;

		if ( isset( $GLOBALS['wpseo_metabox'] ) ) {
			$seo_metabox = $GLOBALS['wpseo_metabox'];
		} elseif ( class_exists( 'WPSEO_Metabox' ) ) {
			 $seo_metabox = new WPSEO_Metabox();
		}

		if ( $seo_metabox && method_exists( $seo_metabox, 'save_postdata' ) ) {
			$seo_metabox->save_postdata( $post_id );
		}
	}


	public static function render() {
		self::render_tabs();
	}

	public function wpseo_init_scripts() {
		?>
	<script type="text/javascript">
		mainwp_wpseo_metabox_init = function () {
			if ( jQuery( '.wpseo-metabox-content' ).length == 0)
			 return;
			console.log('mainwp_wpseo_metabox_init');
			setTimeout(function () // to fix loading later issue
			{
				var mainwp_wpseo_meta_input_onPress = function( event ) {
					if (event.target.id == 'snippet-editor-field-slug') {
						var input = jQuery('#snippet-editor-field-slug');
						var val = input.val();
						if (input.data("lastval") != val) {
							input.data("lastval", val);
						   jQuery("#add-slug-div input[name=add_slug]").val(val);
						}
					}
				};
				console.log('init slug onchange');
				// catch onchange of snippet slug field of yoast seo plugin
				document.addEventListener("change", mainwp_wpseo_meta_input_onPress );
				document.addEventListener("keyup", mainwp_wpseo_meta_input_onPress );
				document.addEventListener("paste", mainwp_wpseo_meta_input_onPress );

			}, 1000);
		}
		jQuery( document ).ready(function ($) {
			mainwp_wpseo_metabox_init();
		});
	</script>

		<?php
	}

	public function ajax_delete_template() {
		if ( $temp_id = $_POST['tempId'] ) {
			if ( MainWP_WPSeo_DB::get_instance()->delete_template( $temp_id ) ) {
				die( 'success' );
			}
		}

		die( 'failed' );
	}

	public function ajax_loading_sites() {
		$result = array();
		$sites  = $groups = array();

		if ( is_array( $_POST['sites'] ) && count( $_POST['sites'] ) > 0 ) {
			$sites = $_POST['sites'];
		}

		if ( is_array( $_POST['groups'] ) && count( $_POST['groups'] ) > 0 ) {
			$groups = $_POST['groups'];
		}

		global $wordpressSeoExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $wordpressSeoExtensionActivator->get_child_file(), $wordpressSeoExtensionActivator->get_child_key(), $sites, $groups );

		if ( ! is_array( $dbwebsites ) || count( $dbwebsites ) <= 0 ) {
			$result['error'] = __( 'No websites found.', 'wordpress-seo-extension' );
			die( json_encode( $result ) );
		}

		if ( $temp_id = $_POST['tempId'] ) {
			$template = MainWP_WPSeo_DB::get_instance()->get_template_by( 'id', $temp_id );
			if ( is_object( $template ) ) {
				$str  = '';
				$str .= '<div class="ui modal" id="mainwp-wordpress-seo-sync-modal">';
				$str .= '<div class="header">' . __( 'Saving Settings', 'wordpress-seo-extension' ) . '</div>';
				$str .= '<div class="scrolling content">';
				$str .= '<input type="hidden" id="mainwp_yst_set_temp_id" value="' . $temp_id . '"/>';
				$str .= '<div class="ui divided relaxed list">';
				foreach ( $dbwebsites as $website ) {
					$str .= '<div class="item">' . $website->name;
					$str .= '<span class="mainwpSetSiteItem right floated" siteid="' . $website->id . '" status="queue"><span class="status"><i class="clock outline icon"></i></span></span>';
					$str .= '</div>';
				}
				$str             .= '</div>';
				$str             .= '</div>';
				$str             .= '<div class="actions"><div class="ui cancel button">' . __( 'Close', 'wordpress-seo-extension' ) . '</div></div>';
				$str             .= '</div>';
				$result['result'] = $str;
			}
		}
		die( json_encode( $result ) );
	}

	public function ajax_set_template() {
		$result = array();
		global $wordpressSeoExtensionActivator;
		$siteid   = $_POST['siteId'];
		$tempId   = intval( $_POST['tempId'] );
		$template = MainWP_WPSeo_DB::get_instance()->get_template_by( 'id', $tempId );
		if ( empty( $template ) ) {
			die( json_encode( array( 'error' => 'Error: Not found template' ) ) );
		}

		if ( empty( $template->settings ) ) {
			die( json_encode( array( 'error' => 'Error: Empty settings' ) ) );
		}

		$post_data   = array(
			'action'   => 'import_settings',
			'settings' => base64_encode( stripslashes( $template->settings ) ),
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $wordpressSeoExtensionActivator->get_child_file(), $wordpressSeoExtensionActivator->get_child_key(), $siteid, 'wordpress_seo', $post_data );

		if ( is_array( $information ) && isset( $information['success'] ) ) {
			$website = apply_filters( 'mainwp_getsites', $wordpressSeoExtensionActivator->get_child_file(), $wordpressSeoExtensionActivator->get_child_key(), $siteid );
			if ( $website && is_array( $website ) ) {
				$website = current( $website );
			}
			if ( is_array( $website ) ) {
				MainWP_WPSeo_DB::get_instance()->update_site_template( $website['id'], $tempId, time() );
			}
		}

		die( json_encode( $information ) );
	}

	public static function handle_save_template() {

		$title = '';
		if ( isset( $_POST['mainwp_wpseo_in_template_title'] ) ) {
			$title       = trim( $_POST['mainwp_wpseo_in_template_title'] );
			$description = trim( $_POST['mainwp_wpseo_in_template_description'] );
		}

		if ( isset( $_POST['mainwp_wpseo_settings_import'] ) && ! empty( $title ) ) {
			$template = array(
				'title'       => $title,
				'description' => $description,
				'settings'    => $_POST['mainwp_wpseo_settings_import'],
			);
			if ( MainWP_WPSeo_DB::get_instance()->update_template( $template ) ) {
				wp_redirect( get_site_url() . '/wp-admin/admin.php?page=Extensions-Wordpress-Seo-Extension&action=setting&message=1' );
				die();
			}
			wp_redirect( get_site_url() . '/wp-admin/admin.php?page=Extensions-Wordpress-Seo-Extension&action=setting&message=-1' );
			die();
		}

		return false;
	}

	public static function render_tabs() {
		global $current_user;
		$str_message = '';

		if ( isset( $_GET['action'] ) && 'setting' == $_GET['action'] ) {
			if ( isset( $_GET['message'] ) ) {
				switch ( $_GET['message'] ) {
					case 1:
						$str_message = __( 'Template uploaded successfully!', 'wordpress-seo-extension' );
						break;
					case -1:
						$str_message = __( 'Upload failed. Please, try again.', 'wordpress-seo-extension' );
						break;
				}
			}
		}

		global $wordpressSeoExtensionActivator;

		$websites = apply_filters( 'mainwp_getsites', $wordpressSeoExtensionActivator->get_child_file(), $wordpressSeoExtensionActivator->get_child_key(), null );
		$sites    = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites[] = $website['id'];
			}
		}
		$option = array(
			'plugin_upgrades' => true,
			'plugins'         => true,
		);

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $wordpressSeoExtensionActivator->get_child_file(), $wordpressSeoExtensionActivator->get_child_key(), $sites, array(), $option );

		$selected_temp = $selected_group = 0;

		if ( isset( $_POST['mainwp_wpseo_select_template'] ) ) {
			$selected_temp = intval( $_POST['mainwp_wpseo_select_template'] );
		}

		if ( isset( $_POST['mainwp_wpseo_groups_select'] ) ) {
			$selected_group = intval( $_POST['mainwp_wpseo_groups_select'] );
		}
		$templates = MainWP_WPSeo_DB::get_instance()->get_template_by( 'all' );
		// print_r($templates);
		$dbwebsites_seo = self::get_websites_seo( $dbwebsites, $selected_temp, $selected_group );
		// print_r($dbwebsites_seo);
		unset( $dbwebsites );

		$current_tab = 'seo-dashboard';
		if ( ( isset( $_GET['tab'] ) && ( 'seo-settings' == $_GET['tab'] ) ) ) {
			$current_tab = 'seo-settings';
		} else {
			$current_tab = 'seo-dashboard';
		}

		?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-wordpress-seo-menu">
			<a href="admin.php?page=Extensions-Wordpress-Seo-Extension&tab=seo-dashboard" class="item <?php echo $current_tab == 'seo-dashboard' ? 'active' : ''; ?> "><i class="tasks icon"></i> <?php _e( 'Yoast SEO Dashboard', 'wordpress-seo-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Wordpress-Seo-Extension&tab=seo-settings" class="item <?php echo $current_tab == 'seo-settings' ? 'active' : ''; ?>"><i class="cog icon"></i> <?php _e( 'Settings Templates', 'wordpress-seo-extension' ); ?></a>
		</div>

		<!-- Dashboard -->
		<div id="mainwp-wordpress-seo-dashboard-tab" class="ui tab <?php echo $current_tab == 'seo-dashboard' ? 'active' : ''; ?>">
			<div class="ui segment">
				<div  class="ui red message" id="wpseo-site-error-box" style="display: none;"></div>
				<div  class="ui yellow message" <?php echo ( empty( $str_message ) ? ' style="display: none" ' : '' ); ?>><i class="close icon"></i> <?php echo $str_message; ?></div>
				<div class="ui red message" id="yst_seo_error_box" style="display: none;"></div>
				<?php self::gen_wpseo_dashboard_tab( $dbwebsites_seo ); ?>
			</div>
		</div>
		<!-- Settings -->
		<div id="mainwp-wordpress-seo-settings-tab" class="ui tab <?php echo $current_tab == 'seo-settings' ? 'active' : ''; ?>">
			<?php self::gen_wpseo_active_tab( $templates ); ?>
		</div>
		<?php
	}

	public static function gen_wpseo_dashboard_tab( $websites ) {
		?>
	<table id="mainwp-wordpress-seo-sites-table" class="ui single line table">
			<thead>
			<tr>
				<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'wordpress-seo-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'wordpress-seo-extension' ); ?></th>
					<th><?php _e( 'Version', 'wordpress-seo-extension' ); ?></th>
					<th><?php _e( 'Last Template', 'wordpress-seo-extension' ); ?></th>
					<th><?php _e( 'Last Updated', 'wordpress-seo-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'wordpress-seo-extension' ); ?></th>
			</tr>
		</thead>
			<tbody>
				<?php if ( is_array( $websites ) && count( $websites ) > 0 ) : ?>
					<?php self::get_dashboard_table_row( $websites ); ?>
				<?php endif; ?>
	  </tbody>
			<tfoot>
			<tr>
				<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'wordpress-seo-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'wordpress-seo-extension' ); ?></th>
					<th><?php _e( 'Version', 'wordpress-seo-extension' ); ?></th>
					<th><?php _e( 'Last Template', 'wordpress-seo-extension' ); ?></th>
					<th><?php _e( 'Last Updated', 'wordpress-seo-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'wordpress-seo-extension' ); ?></th>
			</tr>
		</tfoot>
	</table>
		<script type="text/javascript">
		jQuery( '#mainwp-wordpress-seo-sites-table' ).DataTable( {
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"order": [ [ 1, "asc" ] ],
			"language": { "emptyTable": "No websites were found with the Yoast SEO plugin installed." },
			"drawCallback": function( settings ) {
				jQuery('#mainwp-wordpress-seo-sites-table .ui.checkbox').checkbox();
				jQuery( '#mainwp-wordpress-seo-sites-table .ui.dropdown').dropdown();
			},
		} );
		</script>
		<?php
	}

	public static function get_dashboard_table_row( $websites ) {
		$plugin_name = 'Yoast SEO';
		$location    = 'admin.php?page=wpseo_dashboard';
		foreach ( $websites as $website ) {
			$website_id = $website['id'];

			$template_title = empty( $website['template_title'] ) ? '&nbsp;' : $website['template_title'];

			$class_active = ( isset( $website['seo_active'] ) && ! empty( $website['seo_active'] ) ) ? '' : 'negative';
			$class_update = ( isset( $website['seo_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			$version     = '';
			$plugin_slug = ( isset( $website['plugin_slug'] ) ) ? $website['plugin_slug'] : '';

			if ( isset( $website['seo_upgrade'] ) ) {
				if ( isset( $website['seo_upgrade']['new_version'] ) ) {
					$version = $website['seo_upgrade']['new_version'];
				}
				if ( isset( $website['seo_upgrade']['plugin'] ) ) {
					$plugin_slug = $website['seo_upgrade']['plugin'];
				}
			}

			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['wpseo_plugin_version'] ) ) ? $website['wpseo_plugin_version'] : 'N/A'; ?>">
		<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td><span class="updating"></span> <?php echo ( isset( $website['seo_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['wpseo_plugin_version'] ) ) ? $website['wpseo_plugin_version'] : 'N/A'; ?></td>
				<td><?php echo esc_html( $template_title ); ?></td>
				<td><?php echo ( isset( $website['setting_update_time'] ) && ! empty( $website['setting_update_time'] ) ) ? MainWP_WPSeo_Utility::format_timestamp( $website['setting_update_time'] ) : 'N/A'; ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'wordpress-seo-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'wordpress-seo-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Open Yoast SEO', 'wordpress-seo-extension' ); ?></a>
							<?php if ( isset( $website['seo_active'] ) && empty( $website['seo_active'] ) ) : ?>
							<a class="item mwp_seo_active_plugin" href="#"><?php _e( 'Activate Plugin', 'wordpress-seo-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['seo_upgrade'] ) ) : ?>
							<a class="item mwp_seo_upgrade_plugin" href="#"><?php _e( 'Update Plugin', 'wordpress-seo-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
		</td>
	  </tr>
			<?php
		}
	}

	public function upgrade_plugin() {
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function active_plugin() {
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public static function get_websites_seo( $websites, $selected_temp = 0, $selected_group = 0 ) {
		$sites_ids = array();
		if ( $selected_temp ) {
			$sites_temp = MainWP_WPSeo_DB::get_instance()->get_site_template_by( 'temp_id', $selected_temp );
			if ( is_array( $sites_temp ) ) {
				foreach ( $sites_temp as $value ) {
					$sites_ids[] = $value->site_id;
				}
			}
		}
		$websites_seo = array();
		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $websites as $website ) {
					if ( $website && '' != $website->plugins && ( 0 == $selected_temp || ( 0 != $selected_temp && in_array( $website->id, $sites_ids ) ) ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && 0 != count( $plugins ) ) {
							foreach ( $plugins as $plugin ) {
								if ( 'wordpress-seo/wp-seo.php' == $plugin['slug'] || strpos( $plugin['slug'], '/wp-seo.php' ) !== false ) {
									$site = MainWP_WPSeo_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['seo_active'] = 1;
									} else {
										$site['seo_active'] = 0;
									}
									// get upgrade info
									$site['wpseo_plugin_version'] = $plugin['version'];
									$plugin_upgrades              = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['wordpress-seo/wp-seo.php'] ) ) {
											$upgrade = $plugin_upgrades['wordpress-seo/wp-seo.php'];
											if ( isset( $upgrade['update'] ) ) {
												$site['seo_upgrade'] = $upgrade['update'];
											}
										}
									}
									$websites_seo[] = $site;
									break;
								}
							}
						}
					}
				}
			} else {
				global $wordpressSeoExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $wordpressSeoExtensionActivator->get_child_file(), $wordpressSeoExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$sites          = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && '' != $website->plugins && in_array( $website->id, $sites ) && ( 0 == $selected_temp || ( 0 != $selected_temp && in_array( $website->id, $sites_ids ) ) ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( 'wordpress-seo/wp-seo.php' == $plugin['slug'] || false !== strpos( $plugin['slug'], '/wp-seo.php' ) ) {
									$site = MainWP_WPSeo_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									// get upgrade info
									$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['wordpress-seo/wp-seo.php'] ) ) {
											$upgrade = $plugin_upgrades['wordpress-seo/wp-seo.php'];
											if ( isset( $upgrade['update'] ) ) {
												$site['seo_upgrade'] = $upgrade['update'];
											}
										}
									}
									$websites_seo[] = $site;
									break;
								}
							}
						}
					}
				}
			}
		}

		// if search action
		$search_sites = array();
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$find = trim( $_GET['s'] );
			foreach ( $websites_seo as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_seo = $search_sites;
		}
		unset( $search_sites );

		$return = array();
		foreach ( $websites_seo as $website ) {
			$site_template  = MainWP_WPSeo_DB::get_instance()->get_site_template_by( 'site_id', $website['id'] );
			$template_title = '';
			$date           = 0;
			if ( is_object( $site_template ) && isset( $site_template->template ) ) {
				$template = MainWP_WPSeo_DB::get_instance()->get_template_by( 'id', $site_template->template );
				if ( is_object( $template ) ) {
					$template_title = $template->title;
				}
				$date = $site_template->date;
			}
			$website['template_title']      = $template_title;
			$website['setting_update_time'] = $date;
			$return[]                       = $website;
		}
		return $return;
	}



	public static function gen_wpseo_active_tab( $templates ) {
		$selected_websites = array();
		$selected_groups   = array();

		if ( ! is_array( $templates ) ) {
			$templates = array(); }
		?>

		<div class="ui alt segment" id="mainwp-wordpress-seo-settings-tab">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
				<div  class="ui yellow message" <?php echo ( empty( $str_message ) ? ' style="display: none" ' : '' ); ?>><i class="close icon"></i> <?php echo $str_message; ?></div>
				<div  class="ui red message" id="wpseo-site-error-box" style="display:none"></div>
				<div class="ui red message" id="yst_seo_error_box" style="display:none"></div>

				<table class="ui stackable table" id="mainwp-wordpress-seo-templates-table">
					<thead>
						<tr>
							<th><?php _e( 'Template', 'wordpress-seo-extension' ); ?></th>
							<th><?php _e( 'Description', 'wordpress-seo-extension' ); ?></th>
							<th class="no-sort"></th>
						</tr>
					</thead>
					<tbody>
						<?php self::render_active_table( $templates ); ?>
					</tbody>
					<tfoot>
						<tr>
							<th><a class="ui green button" href="#" id="mainwp-wordpress-seo-import-button"><?php echo __( 'Import Settings' ); ?></a></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>
				</table>
				<div class="ui modal" id="mainwp-wordpress-seo-import-modal">
					<div class="header"><?php echo __( 'Import Template', 'wordpress-seo-extension' ); ?></div>

					<div class="scrolling content">
						<div class="ui info message"><?php _e( 'We recommend setting your Yoast SEO templates using the available variables before you import them.', 'wordpress-seo-extension' ); ?></div>
						<div class="ui red message" id="mainwp_wpseo_import_error_box" style="display: none;"></div>
						<form method="post" enctype="multipart/form-data">
						<div class="ui form">
							<div class="field">
								<label><?php _e( 'Template title', 'wordpress-seo-extension' ); ?></label>
								<input type="text" name="mainwp_wpseo_in_template_title" id="mainwp_wpseo_in_template_title" value="" />
							</div>
							<div class="field">
								<label><?php _e( 'Template description', 'wordpress-seo-extension' ); ?></label>
								<input type="text" name="mainwp_wpseo_in_template_description" id="mainwp_wpseo_in_template_description" value="" />
							</div>
							<div class="field">
								<label><?php _e( 'Import Settings', 'wordpress-seo-extension' ); ?></label>
								<textarea id="mainwp_wpseo_settings_import" name="mainwp_wpseo_settings_import"></textarea>
							</div>
						</div>
					</div>
					<div class="actions">
						<input type="submit" name="mainwp_wpseo_btn_import" id="mainwp_wpseo_btn_import" class="ui green button" value="<?php _e( 'Import Settings', 'wordpress-seo-extension' ); ?>" />
						<div class="ui cancel button"><?php echo __( 'Close', 'wordpress-seo-extension' ); ?></div>
					</form>
					</div>
				</div>
				<script type="text/javascript">
				jQuery( '#mainwp-wordpress-seo-templates-table' ).DataTable( {
					"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
					"order": [ [ 0, "asc" ] ],
					"language": { "emptyTable": "No saved templates." },
				} );

				jQuery( '#mainwp-wordpress-seo-import-button' ).on( 'click', function () {
					jQuery( '#mainwp-wordpress-seo-import-modal' ).modal( 'show' );
				} );
				</script>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<div class="mainwp-select-sites">
					<div class="ui header"><?php echo __( 'Select Sites', 'wordpress-seo-extension' ); ?></div>
					<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_websites, $selected_groups ); ?>
				</div>
			</div>
			<div class="ui hidden clearing divider"></div>
		</div>
		<?php
	}

	public static function render_active_table( $templates ) {
		if ( count( $templates ) > 0 ) {
			foreach ( $templates as $temp ) {
				?>
	  <tr tempid="<?php echo $temp->id; ?>">
		<td><?php echo esc_html( stripslashes( $temp->title ) ); ?></td>
		<td><?php echo esc_html( stripslashes( $temp->description ) ); ?></td>
		<td class="right aligned">
		  <span class="loading"><span class="status"></span><i class="notched circle loading icon" style="display: none;"></i></span>
		  <a href="#" class="mainwp-yst-set ui mini green button"><?php _e( 'Apply Template', 'wordpress-seo-extension' ); ?></a>
					<a href="#" class="mainwp-yst-delete ui mini button"><?php _e( 'Delete', 'wordpress-seo-extension' ); ?></a>
		</td>
	  </tr>
				<?php
			}
		}
	}

}
