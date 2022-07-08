<?php

class MainWP_TimeCapsule_Plugin {

	private $option_handle  = 'mainwp_time_capsule_plugin_option';
	private $option         = array();
	private static $order   = '';
	private static $orderby = '';
	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_TimeCapsule_Plugin();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_time_capsule_active_plugin', array( $this, 'active_plugin' ) );
		add_action( 'wp_ajax_mainwp_time_capsule_upgrade_plugin', array( $this, 'upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_time_capsule_showhide_plugin', array( $this, 'ajax_showhide_plugin' ) );
	}


	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}


	public static function gen_tab_dashboard( $websites ) {

		?>
	<table id="mainwp-time-capsule-sites-table" class="ui single line table" style="width:100%">
	<thead>
		<tr>
			<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
				<th><?php _e( 'Site', 'mainwp-timecapsule-extension' ); ?></th>
				<th class="no-sort collapsing"><i class="sign in icon"></i></th>
				<th><?php _e( 'URL', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Version', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Backups', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Last Backup', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Hidden', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Settings', 'mainwp-timecapsule-extension' ); ?></th>
				<th class="no-sort collapsing"><?php _e( '', 'mainwp-timecapsule-extension' ); ?></th>
		</tr>
	</thead>
		<tbody id="the-time-capsule-list">
			<?php
			if ( is_array( $websites ) && count( $websites ) > 0 ) {
				self::get_dashboard_table_row( $websites );
			}
			?>
	</tbody>
	<tfoot>
			<tr>
				<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
				<th><?php _e( 'Site', 'mainwp-timecapsule-extension' ); ?></th>
				<th class="no-sort collapsing"><i class="sign in icon"></i></th>
				<th><?php _e( 'URL', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Version', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Backups', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Last Backup', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Hidden', 'mainwp-timecapsule-extension' ); ?></th>
				<th><?php _e( 'Settings', 'mainwp-timecapsule-extension' ); ?></th>
				<th class="no-sort collapsing"><?php _e( '', 'mainwp-timecapsule-extension' ); ?></th>
		</tr>
	</tfoot>
  </table>
	<script type="text/javascript">	
		jQuery( document ).ready( function () {
			jQuery( '#mainwp-time-capsule-sites-table' ).DataTable( {
				"stateSave": true,
				"stateDuration": 0, // forever
				"scrollX": true,
				"colReorder" : {
					fixedColumnsLeft: 1,
					fixedColumnsRight: 1
				},
				"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
				"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
				"order": [ [ 1, "asc" ] ],
				"language": { "emptyTable": "No websites were found with the WP Time Capsule plugin installed." },
				"drawCallback": function( settings ) {
					jQuery( '#mainwp-time-capsule-sites-table .ui.checkbox' ).checkbox();
					jQuery( '#mainwp-time-capsule-sites-table .ui.dropdown' ).dropdown();
					if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
						mainwp_datatable_fix_menu_overflow();
					}
				},
			} );
			if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {								
				mainwp_datatable_fix_menu_overflow();
			}
		});
	</script>
		<?php
	}

	public static function get_dashboard_table_row( $websites ) {
		$plugin_name = 'WP Time Capsule';
		$location    = 'admin.php?page=wp-time-capsule-monitor';

		$change_store_location = '/wp-admin/admin.php?page=wp-time-capsule&show_connect_pane=set';
		$change_store_location = base64_encode( $change_store_location );

		foreach ( $websites as $website ) {
			$website_id = intval( $website['id'] );

			$class_active = ( isset( $website['plugin_active'] ) && ! empty( $website['plugin_active'] ) ) ? '' : 'negative';
			$class_update = ( isset( $website['the_plugin_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			$version     = '';
			$plugin_slug = ( isset( $website['plugin_slug'] ) ) ? $website['plugin_slug'] : '';

			if ( isset( $website['the_plugin_upgrade'] ) ) {
				if ( isset( $website['the_plugin_upgrade']['new_version'] ) ) {
					$version = $website['the_plugin_upgrade']['new_version'];
				}
				if ( isset( $website['the_plugin_upgrade']['plugin'] ) ) {
					$plugin_slug = $website['the_plugin_upgrade']['plugin'];
				}
			}

			$last_backup = empty( $website['lastbackup_time'] ) ? 'N/A' : MainWP_TimeCapsule_Utility::format_timestamp( MainWP_TimeCapsule_Utility::get_timestamp( $website['lastbackup_time'] ) );

			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['the_plugin_version'] ) ) ? $website['the_plugin_version'] : 'N/A'; ?>">
		<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a> <span class="status"></span></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td><?php echo ( isset( $website['the_plugin_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['the_plugin_version'] ) ) ? $website['the_plugin_version'] : 'N/A'; ?></td>
				<td><?php echo $website['backups_count']; ?></td>
				<td><?php echo $last_backup; ?></td>
				<td class="wp-timecapsule-visibility"><?php echo ( 1 == $website['hide_backupwp'] ) ? __( 'Yes', 'mainwp-timecapsule-extension' ) : __( 'No', 'mainwp-timecapsule-extension' ); ?></td>
				<td><?php echo ( $website['isOverride'] ? __( 'Individual', 'mainwp-timecapsule-extension' ) : __( 'General', 'mainwp-timecapsule-extension' ) ); ?></td>
				<td>
					<div class="ui right pointing dropdown icon mini basic green button" style="z-index:999" data-tooltip="<?php esc_attr_e( 'See more options', 'mainwp-rocket-extension' ); ?>" data-position="left center" data-inverted="">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Dashboard', 'mainwp-timecapsule-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-timecapsule-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Open WP Time Capsule', 'mainwp-timecapsule-extension' ); ?></a>
							<?php if ( 1 == $website['hide_backupwp'] ) : ?>
							<a class="item mwp_time_capsule_showhide_plugin" href="#" showhide="show"><?php _e( 'Unhide Plugin', 'mainwp-timecapsule-extension' ); ?></a>
							<?php else : ?>
							<a class="item mwp_time_capsule_showhide_plugin" href="#" showhide="hide"><?php _e( 'Hide Plugin', 'mainwp-timecapsule-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['plugin_active'] ) && empty( $website['plugin_active'] ) ) : ?>
							<a class="item mwp_time_capsule_active_plugin" href="#"><?php _e( 'Activate Plugin', 'mainwp-timecapsule-extension' ); ?></a>
							<?php else : ?>
							<a class="item" href="admin.php?page=ManageSitesWPTimeCapsule&tab=backup&id=<?php echo $website_id; ?>"><?php _e( 'Backup Now', 'mainwp-timecapsule-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&openUrl=yes&location=<?php echo $change_store_location; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Change Storage', 'mainwp-timecapsule-extension' ); ?></a>
							<a class="item" href="admin.php?page=ManageSitesWPTimeCapsule&tab=staging&id=<?php echo $website_id; ?>"><?php _e( 'Staging', 'mainwp-timecapsule-extension' ); ?></a>
			  <a class="item" href="admin.php?page=ManageSitesWPTimeCapsule&tab=activity_log&id=<?php echo $website_id; ?>"><?php _e( 'Logs', 'mainwp-timecapsule-extension' ); ?></a>
			  <a class="item" href="admin.php?page=ManageSitesWPTimeCapsule&tab=info&tab=info&id=<?php echo $website_id; ?>"><?php _e( 'Info', 'mainwp-timecapsule-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['the_plugin_upgrade'] ) ) : ?>
							<a class="item mwp_rocket_upgrade_plugin" href="#"><?php _e( 'Update Plugin', 'mainwp-timecapsule-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
		  </td>
		</tr>
				<?php
		}
	}

	public function get_time_capsule_websites() {
		global $mainwpWPTimeCapsuleExtensionActivator;

		$others   = array(
			'plugins_slug' => 'wp-time-capsule/wp-time-capsule.php',
		);
		$websites = apply_filters( 'mainwp_getsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), null, false, $others );

		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $site ) {
				$sites_ids[] = $site['id'];
			}
			unset( $websites );
		}
		$option     = array(
			'plugin_upgrades' => true,
			'plugins'         => true,
		);
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$dataSites  = array();
		if ( count( $sites_ids ) > 0 ) {
			$dataSites = MainWP_TimeCapsule_DB::get_instance()->get_timecapsule_of_sites( $sites_ids );
		}
		return self::get_instance()->get_websites_with_the_plugin_data( $dbwebsites, $dataSites );
	}

	public function get_websites_with_the_plugin_data( $websites, $dataSites = array() ) {
		$return = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'wp-time-capsule/wp-time-capsule.php' == $plugin['slug'] ) ) {
								$site = MainWP_TimeCapsule_Utility::map_site( $website, array( 'id' ) );
								if ( ! $plugin['active'] ) {
									continue;
								}
								$data_site = array( 'id' => $website->id );
								$data      = isset( $dataSites[ $site['id'] ] ) ? $dataSites[ $site['id'] ] : array();
								if ( ! is_array( $data ) ) {
										$data = array();
								}
								$data_site['lastbackup_time'] = isset( $data['lastbackup_time'] ) ? $data['lastbackup_time'] : 0;
								$data_site['backups_count']   = isset( $data['backups_count'] ) ? $data['backups_count'] : 0;
								$return[ $website->id ]       = $data_site;
								break;
							}
						}
					}
				}
			}
		}
		return $return;
	}

	public function get_websites_with_the_plugin( $websites, $selected_group = null, $plugin_data_sites = array() ) {

		$websites_data = array();

		$pluginHide = $this->get_option( 'hide_the_plugin' );

		if ( ! is_array( $pluginHide ) ) {
			$pluginHide = array(); }
		$_text = __( 'There are no scheduled backups at the moment.', 'mainwp-timecapsule-extension' );
		if ( is_array( $websites ) && count( $websites ) ) {
			if ( $selected_group === null ) {
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( ( 'wp-time-capsule/wp-time-capsule.php' == $plugin['slug'] ) ) {
									$site = MainWP_TimeCapsule_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['plugin_active'] = 1;
									} else {
										$site['plugin_active'] = 0;
									}
									// get upgrade info
									$site['the_plugin_version'] = $plugin['version'];
									$site['plugin_slug']        = $plugin['slug'];
									$plugin_upgrades            = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['wp-time-capsule/wp-time-capsule.php'] ) ) {
											$upgrade = $plugin_upgrades['wp-time-capsule/wp-time-capsule.php'];
											if ( isset( $upgrade['update'] ) ) {
												$site['the_plugin_upgrade'] = $upgrade['update'];
											}
										}
									}

									$site['hide_backupwp'] = 0;
									if ( isset( $pluginHide[ $website->id ] ) && $pluginHide[ $website->id ] ) {
										$site['hide_backupwp'] = 1;
									}

									$plugintDS = isset( $plugin_data_sites[ $site['id'] ] ) ? $plugin_data_sites[ $site['id'] ] : array();
									if ( ! is_array( $plugintDS ) ) {
										$plugintDS = array(); }

									// $site['isPremium'] = isset($plugintDS['is_premium']) ? $plugintDS['is_premium'] : 0;
									$site['isOverride']      = isset( $plugintDS['override'] ) ? $plugintDS['override'] : 0;
									$site['lastbackup_time'] = isset( $plugintDS['lastbackup_time'] ) ? $plugintDS['lastbackup_time'] : 0;
									$site['backups_count']   = isset( $plugintDS['backups_count'] ) ? $plugintDS['backups_count'] : 0;
									$websites_data[]         = $site;
									break;
								}
							}
						}
					}
				}
			} else {
				global $mainwpWPTimeCapsuleExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$sites          = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' && in_array( $website->id, $sites ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( ( 'wp-time-capsule/wp-time-capsule.php' == $plugin['slug'] ) ) {
									$site = MainWP_TimeCapsule_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['plugin_active'] = 1; } else {
										$site['plugin_active'] = 0; }
										$site['the_plugin_version'] = $plugin['version'];
										// get upgrade info
										$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
										if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
											if ( isset( $plugin_upgrades['wp-time-capsule/wp-time-capsule.php'] ) ) {
												$upgrade = $plugin_upgrades['wp-time-capsule/wp-time-capsule.php'];
												if ( isset( $upgrade['update'] ) ) {
													$site['the_plugin_upgrade'] = $upgrade['update'];
												}
											}
										}
										$site['hide_backupwp'] = 0;
										if ( isset( $pluginHide[ $website->id ] ) && $pluginHide[ $website->id ] ) {
											$site['hide_backupwp'] = 1;
										}

										$plugintDS = isset( $plugin_data_sites[ $site['id'] ] ) ? $plugin_data_sites[ $site['id'] ] : array();
										if ( ! is_array( $plugintDS ) ) {
											$plugintDS = array(); }

										// $site['isPremium'] = $plugintDS['is_premium'];
										$site['isOverride']      = $plugintDS['override'];
										$site['lastbackup_time'] = isset( $plugintDS['lastbackup_time'] ) ? $plugintDS['lastbackup_time'] : 0;
										$site['backups_count']   = isset( $plugintDS['backups_count'] ) ? $plugintDS['backups_count'] : 0;
										$websites_data[]         = $site;
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
			foreach ( $websites_data as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_data = $search_sites;
		}
		unset( $search_sites );

		return $websites_data;
	}

	public static function gen_select_sites() {
		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid">
				<div class="ui two column row">
					<div class="column">
						<select class="ui dropdown" id="mwp_time_capsule_plugin_action">
							<option value="-1"><?php _e( 'Bulk Actions', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="activate-selected"><?php _e( 'Active', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="update-selected"><?php _e( 'Update', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="hide-selected"><?php _e( 'Hide', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="show-selected"><?php _e( 'Show', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="check-pages"><?php _e( 'Check Pages', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="force-recheck-pages"><?php _e( 'Force Recheck All Pages', 'mainwp-pagespeed-extension' ); ?></option>
			</select>
						<input type="button" name="mwp_time_capsule_doaction_btn" id="mwp_time_capsule_doaction_btn" class="ui basic button" value="<?php _e( 'Apply', 'mainwp-rocket-extension' ); ?>"/>
						<?php do_action( 'mainwp_timecapsule_actions_bar_right' ); ?>
		</div>
					<div class="right aligned column">
						<?php do_action( 'mainwp_timecapsule_actions_bar_right' ); ?>
		</div>
		</div>
			</div>
		</div>
		<?php
		return;
	}

	public function active_plugin() {
		$_POST['websiteId'] = $_POST['timecapsuleSiteID'];
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function upgrade_plugin() {
		$_POST['websiteId'] = $_POST['timecapsuleSiteID'];
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function ajax_showhide_plugin() {
		$siteid   = isset( $_POST['timecapsuleSiteID'] ) ? $_POST['timecapsuleSiteID'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainwpWPTimeCapsuleExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'set_showhide',
				'showhide'   => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $siteid, 'time_capsule', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {
				$hide_backupwp = $this->get_option( 'hide_the_plugin' );
				if ( ! is_array( $hide_backupwp ) ) {
					$hide_backupwp = array(); }
				$hide_backupwp[ $siteid ] = ( 'hide' === $showhide ) ? 1 : 0;
				$this->set_option( 'hide_the_plugin', $hide_backupwp );
			}
			die( json_encode( $information ) );
		}
		die();
	}
}
