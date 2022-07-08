<?php

class MainWP_Staging_Plugin {

	private $option_handle = 'mainwp_staging_plugin_option';
	private $option = array();
	private static $order = '';
	private static $orderby = '';
	//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Staging_Plugin::$instance ) {
			MainWP_Staging_Plugin::$instance = new MainWP_Staging_Plugin();
		}
		return MainWP_Staging_Plugin::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
	}

	public function admin_init() {
		//add_action( 'wp_ajax_mainwp_staging_upgrade_noti_dismiss', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'wp_ajax_mainwp_staging_active_plugin', array( $this, 'ajax_active_plugin' ) );
		add_action( 'wp_ajax_mainwp_staging_upgrade_plugin', array( $this, 'ajax_upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_staging_showhide_plugin', array( $this, 'ajax_showhide_plugin' ) );
	}

	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ]; }
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	public static function gen_dashboard_tab( $websites ) {
		?>
		<div class="ui segment">
			<table id="mainwp-wp-staging-sites-table" class="ui single line table">
				<thead>
					<tr>
						<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
						<th><?php _e( 'Site', 'mainwp-staging-extension' ); ?></th>
						<th class="no-sort collapsing"><i class="sign in icon"></i></th>
						<th><?php _e( 'URL', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Version', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Hidden', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Settings', 'mainwp-staging-extension' ); ?></th>
						<th class="no-sort collapsing"><?php _e( '', 'mainwp-staging-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( is_array( $websites ) && count( $websites ) > 0 ) {
						self::get_dashboard_table_row( $websites );
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
						<th><?php _e( 'Site', 'mainwp-staging-extension' ); ?></th>
						<th class="no-sort collapsing"><i class="sign in icon"></i></th>
						<th><?php _e( 'URL', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Version', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Hidden', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Settings', 'mainwp-staging-extension' ); ?></th>
						<th class="no-sort collapsing"><?php _e( '', 'mainwp-staging-extension' ); ?></th>
					</tr>
				</tfoot>
			</table>
		</div>
		<script type="text/javascript">
		jQuery( '#mainwp-wp-staging-sites-table' ).DataTable( {
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"order": [ [ 1, "asc" ] ],
			"language": { "emptyTable": "No websites were found with the WP Staging plugin installed." },
			"drawCallback": function( settings ) {
				jQuery('#mainwp-wp-staging-sites-table .ui.checkbox').checkbox();
				jQuery( '#mainwp-wp-staging-sites-table .ui.dropdown').dropdown();
			},
		} );
		</script>
		<?php
	}

	public static function get_dashboard_table_row( $websites ) {
		$location = 'admin.php?page=wpstg_clone';
		$plugin_name = 'WP Staging';
    $staging_sites_info = MainWP_Staging_DB::instance()->get_count_stagings_of_sites();


		foreach ( $websites as $website ) {
			$website_id = $website['id'];
			$plugin_slug = $website['plugin_slug'];

			$class_active = ( isset( $website['staging_active'] ) && ! empty( $website['staging_active'] ) ) ? '' : 'negative';
			$class_update = ( isset( $website['staging_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active) ? 'negative' : $class_update;

			$version = '';
			if ( isset( $website['staging_upgrade'] ) ) {
				if ( isset( $website['staging_upgrade']['new_version'] ) ) {
					$version = $website['staging_upgrade']['new_version'];
				}
			}

			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['staging_plugin_version'] ) ) ? $website['staging_plugin_version'] : 'N/A'; ?>">
        <td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td><span class="updating"></span> <?php echo ( isset( $website['staging_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['staging_plugin_version'] ) ) ? $website['staging_plugin_version'] : 'N/A'; ?></td>
				<td class="wp-staging-visibility"><span class="visibility"></span> <?php echo ( 1 == $website['hide_staging'] ) ? __( 'Yes', 'mainwp-staging-extension' ) : __( 'No', 'mainwp-staging-extension' ); ?></td>
				<td><?php echo ( $website['isOverride'] ? __( 'Individual', 'mainwp-staging-extension' ) : __( 'General', 'mainwp-staging-extension' ) ); ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-staging-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-staging-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Open WP Staging', 'mainwp-staging-extension' ); ?></a>
							<?php if ( 1 == $website['hide_staging'] ) : ?>
							<a class="item mwp_staging_showhide_plugin" href="#" showhide="show"><?php _e( 'Unhide Plugin', 'mainwp-staging-extension' ); ?></a>
							<?php else : ?>
							<a class="item mwp_staging_showhide_plugin" href="#" showhide="hide"><?php _e( 'Hide Plugin', 'mainwp-staging-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['staging_active'] ) && empty( $website['staging_active'] ) ) : ?>
							<a class="item mwp_staging_active_plugin" href="#"><?php _e( 'Activate Plugin', 'mainwp-staging-extension' ); ?></a>
							<?php else : ?>
								<?php if ( $staging_sites_info && isset( $staging_sites_info[$website_id] ) && $staging_sites_info[$website_id] > 0 ) : ?>
									<a class="item" href="admin.php?page=ManageSitesStaging&tab=stagings&id=<?php echo $website_id; ?>"><?php echo __('Manage Staging Sites', 'mainwp-staging-extension'); ?></a>
								<?php else : ?>
									<a class="item" href="admin.php?page=ManageSitesStaging&tab=new&id=<?php echo $website_id; ?>"><?php echo __('Create Staging Site', 'mainwp-staging-extension'); ?></a>
								<?php endif; ?>
							<?php endif; ?>
							<?php if ( isset( $website['staging_upgrade'] ) ) : ?>
							<a class="item mwp_staging_upgrade_plugin" href="#"><?php _e( 'Update Plugin', 'mainwp-staging-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
        </td>
      </tr>
			<?php
		}
	}

	public function get_websites_installed_the_plugin() {
		global $mainwp_StagingExtensionActivator;
		$websites = apply_filters( 'mainwp_getsites', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), null );
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $site ) {
				$sites_ids[] = $site['id'];
			}
			unset( $websites );
		}
		$option = array(
            'plugin_upgrades' => true,
			'plugins' => true,
		);
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$pluginSites = array();
		if ( count( $sites_ids ) > 0 ) {
			$pluginSites = MainWP_Staging_DB::instance()->get_staging_data( $sites_ids );
		}
		return MainWP_Staging_Plugin::get_instance()->get_websites_with_the_plugin_data( $dbwebsites, $pluginSites );
	}

	public function get_websites_with_the_plugin_data( $websites, $plugin_data_sites = array() ) {
		$sites_data = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ('wp-staging/wp-staging.php' == strtolower($plugin['slug'])) || ( 'wp-staging-pro/wp-staging-pro.php' == strtolower($plugin['slug']) ) ) {
								if ( ! $plugin['active'] ) {
									continue;
                                }
								$data = array( 'id' => $website->id, 'name' => $website->name );
								$plugintDS = isset( $plugin_data_sites[ $website->id ] ) ? $plugin_data_sites[ $website->id ] : array();
								if ( ! is_array( $plugintDS ) ) {
									$plugintDS = array();
                                }
								$data['isOverride'] = isset( $plugintDS['override'] ) ? $plugintDS['override'] : 0;
								$sites_data[ $website->id ] = $data;
								break;
							}
						}
					}
				}
			}
		}

	return $sites_data;
	}

	public function get_websites_with_the_plugin( $websites, $selected_group = 0, $plugin_data_sites = array() ) {
		$websites_plugin = array();

		$pluginHide = $this->get_option( 'hide_the_plugin' );

		if ( ! is_array( $pluginHide ) ) {
			$pluginHide = array();
		}
		$_text = __( 'Nothing currently scheduled', 'mainwp-staging-extension' );
		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( ('wp-staging/wp-staging.php' == strtolower($plugin['slug'])) || ( 'wp-staging-pro/wp-staging-pro.php' == strtolower($plugin['slug']) )) {
									$site = self::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['staging_active'] = 1;
                                    } else {
										$site['staging_active'] = 0;
                                    }
                                    // get upgrade info
                                    $site['staging_plugin_version'] = $plugin['version'];
                                    $site['plugin_slug'] = $plugin['slug'];
                                    $plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
                                    if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
                                        $upgrade = array();
                                        if ( isset( $plugin_upgrades['wp-staging/wp-staging.php'] ) ) {
                                            $upgrade = $plugin_upgrades['wp-staging/wp-staging.php'];
                                        } else if ( isset( $plugin_upgrades['wp-staging-pro/wp-staging-pro.php'] ) ) {
                                            $upgrade = $plugin_upgrades['wp-staging-pro/wp-staging-pro.php'];
                                        }
                                        if ( isset( $upgrade['update'] ) ) {
                                            $site['staging_upgrade'] = $upgrade['update'];
                                        }
                                    }
                                    $site['hide_staging'] = 0;
                                    if ( isset( $pluginHide[ $website->id ] ) && $pluginHide[ $website->id ] ) {
                                        $site['hide_staging'] = 1;
                                    }
                                    $plugintDS = isset( $plugin_data_sites[ $site['id'] ] ) ? $plugin_data_sites[ $site['id'] ] : array();
                                    if ( ! is_array( $plugintDS ) ) {
                                        $plugintDS = array();
                                    }

                                    $site['isOverride'] = isset( $plugintDS['override'] ) ? $plugintDS['override'] : 0;
                                    $websites_plugin[] = $site;
                                    break;
								}
							}
						}
					}
				}
			} else {
				global $mainwp_StagingExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$sites = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' && in_array( $website->id, $sites ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( ('wp-staging/wp-staging.php' == strtolower($plugin['slug'])) || ( 'wp-staging-pro/wp-staging-pro.php' == strtolower($plugin['slug']) ) ) {
									$site = self::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['staging_active'] = 1; } else {
										$site['staging_active'] = 0; }
										$site['staging_plugin_version'] = $plugin['version'];
										// get upgrade info
										$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
										if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
                                            $upgrade = array();
											if ( isset( $plugin_upgrades['wp-staging/wp-staging.php'] ) ) {
												$upgrade = $plugin_upgrades['wp-staging/wp-staging.php'];
											} else if ( isset( $plugin_upgrades['wp-staging-pro/wp-staging-pro.php'] ) ) {
                                                $upgrade = $plugin_upgrades['wp-staging-pro/wp-staging-pro.php'];
                                            }
                                            if ( isset( $upgrade['update'] ) ) {
                                                    $site['staging_upgrade'] = $upgrade['update'];
                                            }
										}
										$site['hide_staging'] = 0;
										if ( isset( $pluginHide[ $website->id ] ) && $pluginHide[ $website->id ] ) {
											$site['hide_staging'] = 1;
										}

										$plugintDS = isset( $plugin_data_sites[ $site['id'] ] ) ? $plugin_data_sites[ $site['id'] ] : array();
										if ( ! is_array( $plugintDS ) ) {
											$plugintDS = array();
                                        }
										$site['isOverride'] = $plugintDS['override'];
										$websites_plugin[] = $site;
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
			foreach ( $websites_plugin as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_plugin = $search_sites;
		}
		unset( $search_sites );

		return $websites_plugin;
	}

	public function ajax_active_plugin() {
		$_POST['websiteId'] = $_POST['stagingSiteID'];
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function ajax_upgrade_plugin() {
		$_POST['websiteId'] = $_POST['stagingSiteID'];
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function ajax_showhide_plugin() {
		$siteid = isset( $_POST['stagingSiteID'] ) ? $_POST['stagingSiteID'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainwp_StagingExtensionActivator;
			$post_data = array(
                'mwp_action' => 'set_showhide',
				'showhide' => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $siteid, 'wp_staging', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {
				$hide_staging = $this->get_option( 'hide_the_plugin' );
				if ( ! is_array( $hide_staging ) ) {
					$hide_staging = array(); }
				$hide_staging[ $siteid ] = ('hide' === $showhide) ? 1 : 0;
				$this->set_option( 'hide_the_plugin', $hide_staging );
			}
			die( json_encode( $information ) );
		}
		die();
	}

    public static function map_site( &$website, $keys ) {
		$outputSite = array();
		foreach ( $keys as $key ) {
			$outputSite[ $key ] = $website->$key;
		}
		return $outputSite;
	}

}
