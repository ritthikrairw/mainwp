<?php

class MainWP_Wordfence_Plugin {

	private static $order   = '';
	private static $orderby = '';

	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Wordfence_Plugin();
		}

		return self::$instance;
	}

	public function __construct() {

	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_wfc_upgrade_noti_dismiss', array( $this, 'dismiss_notice' ) );
		add_action( 'wp_ajax_mainwp_wfc_active_plugin', array( $this, 'active_plugin' ) );
		add_action( 'wp_ajax_mainwp_wfc_upgrade_plugin', array( $this, 'upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_wfc_showhide_plugin', array( $this, 'showhide_plugin' ) );
		add_action( 'wp_ajax_mainwp_wfc_scan_now', array( $this, 'ajax_scan_now' ) );
		add_action( 'wp_ajax_mainwp_wfc_kill_scan_now', array( $this, 'ajax_kill_scan_now' ) );
		add_action( 'wp_ajax_mainwp_wfc_load_more_dashboard_sites', array( $this, 'ajax_load_more_dashboard_sites' ) );
	}

	public static function gen_plugin_dashboard_tab( $websites, $paged, $last ) {
		?>
		<table id="mainwp-wordfence-sites-table" class="ui single line table" style="width:100%;">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-wordfence-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-wordfence-extension' ); ?></th>
					<th class="collapsing"><?php _e( 'Status', 'mainwp-wordfence-extension' ); ?></th>
					<th><?php _e( 'Last Scan', 'mainwp-wordfence-extension' ); ?></th>
					<th><?php _e( 'Version', 'mainwp-wordfence-extension' ); ?></th>
					<th><?php _e( 'Hidden', 'mainwp-wordfence-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-wordfence-extension' ); ?></th>
				</tr>
			</thead>
			<tbody id="mainwp-wordfence-sites-table-body" last-paged="<?php echo $last ? '1' : '0'; ?>" load-paged="<?php echo intval( $paged ); ?>">
				<?php
				if ( is_array( $websites ) && count( $websites ) > 0 ) {
					self::get_plugin_dashboard_table_row( $websites );
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-wordfence-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-wordfence-extension' ); ?></th>
					<th><?php _e( 'Status', 'mainwp-wordfence-extension' ); ?></th>
					<th><?php _e( 'Last Scan', 'mainwp-wordfence-extension' ); ?></th>
					<th><?php _e( 'Version', 'mainwp-wordfence-extension' ); ?></th>
					<th><?php _e( 'Hidden', 'mainwp-wordfence-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-wordfence-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>
	  <?php
	}

	public static function get_plugin_dashboard_table_row( $websites ) {
		$location    = 'admin.php?page=Wordfence';
		$plugin_slug = 'wordfence/wordfence.php';
		$plugin_name = 'Wordfence Security – Firewall & Malware Scan';

		foreach ( $websites as $website ) {
			$website_id = $website['id'];
			$lastscan   = isset( $website['lastscan'] ) ? $website['lastscan'] : 0;
			$status     = isset( $website['status'] ) ? $website['status'] : 0;

			$class_active = ( isset( $website['wordfence_active'] ) && ! empty( $website['wordfence_active'] ) ) ? 'plugin-active' : 'negative';
			$class_update = ( isset( $website['wordfence_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			if ( empty( $status ) ) {
				$icon_status = '<i class="shield alternate grey icon"></i>';
			} else {
				if ( 1 == $status ) {
					$icon_status = '<i class="shield alternate green icon"></i>';
				} else {
					$icon_status = '<i class="shield alternate red icon"></i>';
				}
			}

			$version = '';
			if ( isset( $website['wordfence_upgrade'] ) ) {
				if ( isset( $website['wordfence_upgrade']['new_version'] ) ) {
					$version = $website['wordfence_upgrade']['new_version'];
				}
			}

			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['wordfence_plugin_version'] ) ) ? $website['wordfence_plugin_version'] : 'N/A'; ?>">
		<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td class="center aligned"><span class="wfc-scan-working"><i class="notched circle loading icon" style="display:none"></i> <span class="status"></span> </span><a href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=result&site_id=<?php echo $website_id; ?>"><?php echo $icon_status; ?></a></td>
				<td><?php echo ! empty( $lastscan ) ? MainWP_Wordfence_Utility::format_timestamp( $lastscan ) : ''; ?></td>
				<td><span class="updating"></span> <?php echo ( isset( $website['wordfence_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['wordfence_plugin_version'] ) ) ? $website['wordfence_plugin_version'] : 'N/A'; ?></td>
				<td class="wp-wordfence-visibility"><?php echo ( 1 == $website['hide_wordfence'] ) ? __( 'Yes', 'mainwp-wordfence-extension' ) : __( 'No', 'mainwp-wordfence-extension' ); ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-wordfence-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-wordfence-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Open Child Reports', 'mainwp-client-reports-extension' ); ?></a>
							<?php if ( 1 == $website['hide_wordfence'] ) : ?>
							<a class="item mwp_wfc_showhide_plugin" href="javascript:void(0)" showhide="show"><?php _e( 'Unhide Plugin', 'mainwp-wordfence-extension' ); ?></a>
							<?php else : ?>
							<a class="item mwp_wfc_showhide_plugin" href="javascript:void(0)" showhide="hide"><?php _e( 'Hide Plugin', 'mainwp-wordfence-extension' ); ?></a>
							<?php endif; ?>

							<?php if ( isset( $website['wordfence_active'] ) && empty( $website['wordfence_active'] ) ) : ?>
							<a class="item mwp_wfc_active_plugin" href="#"><?php _e( 'Activate Plugin', 'mainwp-wordfence-extension' ); ?></a>
							<?php else : ?>
							<a class="item mwp_wfc_scan_now_lnk" href="#"><?php echo __( 'Scan Site', 'mainwp-wordfence-extension' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=traffic&site_id=<?php echo $website_id; ?>"><?php echo __( 'Live Traffic', 'mainwp-wordfence-extension' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=blocking&site_id=<?php echo $website_id; ?>"><?php echo __( 'Blocking', 'mainwp-wordfence-extension' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=result&site_id=<?php echo $website_id; ?>"><?php echo __( 'Scan Results', 'mainwp-wordfence-extension' ); ?></a>
							<?php endif; ?>

							<?php if ( isset( $website['wordfence_upgrade'] ) ) : ?>
							<a class="item mwp_wfc_upgrade_plugin" href="#"><?php _e( 'Update Plugin', 'mainwp-wordfence-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
		</td>
	  </tr>
			<?php
		}
	}

	
	public function ajax_load_more_dashboard_sites() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : null;
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wfc-nonce' ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}
		$paged = isset( $_POST['paged'] ) ? $_POST['paged'] : 0;
		$selected_group = 0;
		if ( isset( $_POST['mainwp_wfc_plugin_groups_select'] ) ) {
			$selected_group = intval( $_POST['mainwp_wfc_plugin_groups_select'] );
		}
		$data     = MainWP_Wordfence::get_bulk_wfc_sites( $paged, false, $selected_group );
		$websites = $data['result'];
		$last     = $data['last'];

		$result = '';

		if ( is_array( $websites ) && count( $websites ) > 0 ) :
			ob_start();
			if ( is_array( $websites ) && count( $websites ) > 0 ) {
				self::get_plugin_dashboard_table_row( $websites );
			}
			$result = ob_get_clean();
		endif;

		die(
			wp_json_encode(
				array(
					'result' => $result,
					'last'   => $last,
				)
			)
		);
	}


	public function get_websites_with_the_plugin( $websites, $selected_group = 0, $active_only = false ) {
		$websites_wordfence = array();

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' ) {
						$settings = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $website->id );
						$plugins  = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( 'wordfence/wordfence.php' == $plugin['slug'] ) {
									if ( $active_only && ! $plugin['active'] ) {
										continue;
									}
									$site = MainWP_Wordfence_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['wordfence_active'] = 1;
									} else {
										$site['wordfence_active'] = 0;
									}
									// get upgrade info
									$site['wordfence_plugin_version'] = $plugin['version'];
									$plugin_upgrades                  = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['wordfence/wordfence.php'] ) ) {
											$upgrade = $plugin_upgrades['wordfence/wordfence.php'];
											if ( isset( $upgrade['update'] ) ) {
												$site['wordfence_upgrade'] = $upgrade['update'];
											}
										}
									}

									$site['lastscan']       = $settings->lastscan;
									$site['status']         = $settings->status;
									$site['hide_wordfence'] = $settings->isHidden;
									$websites_wordfence[]   = $site;
									break;
								}
							}
						}
					}
				}
			} else {
				global $mainWPWordfenceExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$sites          = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' && in_array( $website->id, $sites ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( 'wordfence/wordfence.php' == $plugin['slug'] ) {
									if ( $active_only && ! $plugin['active'] ) {
										continue;
									}
									$site = MainWP_Wordfence_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['wordfence_active'] = 1;
									} else {
										$site['wordfence_active'] = 0;
									}
									$site['wordfence_plugin_version'] = $plugin['version'];

									// get upgrade info
									$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['wordfence/wordfence.php'] ) ) {
											$upgrade = $plugin_upgrades['wordfence/wordfence.php'];
											if ( isset( $upgrade['update'] ) ) {
												$site['wordfence_upgrade'] = $upgrade['update'];
											}
										}
									}
									$site['hide_wordfence'] = $settings->isHidden;
									$site['lastscan']       = $settings->lastscan;
									$site['status']         = $settings->status;
									$websites_wordfence[]   = $site;
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
			foreach ( $websites_wordfence as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_wordfence = $search_sites;
		}
		unset( $search_sites );

		return $websites_wordfence;
	}

	public function active_plugin() {
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function upgrade_plugin() {
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function showhide_plugin() {
		$siteid   = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'set_showhide',
				'showhide'   => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {

				$update = array(
					'site_id'  => $siteid,
					'isHidden' => ( 'hide' === $showhide ? 1 : 0 ),
				);
				MainWP_Wordfence_DB::get_instance()->update_setting( $update );

			}
			die( json_encode( $information ) );
		}
		die();
	}

	function ajax_scan_now() {
		$siteid = $_POST['siteId'];
		if ( ! empty( $siteid ) ) {
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array( 'mwp_action' => 'start_scan' );
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			if ( ( isset( $information['result'] ) && 'SUCCESS' == $information['result'] ) ||
				 ( isset( $information['error'] ) && 'SCAN_RUNNING' == $information['error'] )
			) {
				$update = array(
					'site_id'  => $siteid,
					'lastscan' => time(),
				);
				MainWP_Wordfence_DB::get_instance()->update_setting( $update );
			}
			die( json_encode( $information ) );
		}
		die();
	}

	function ajax_kill_scan_now() {
		$siteid = $_POST['siteId'];
		if ( ! empty( $siteid ) ) {
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array( 'mwp_action' => 'kill_scan' );
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			die( json_encode( $information ) );
		}
		die();
	}


}
