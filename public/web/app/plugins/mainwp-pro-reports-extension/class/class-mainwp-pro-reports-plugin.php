<?php

class MainWP_Pro_Reports_Plugin {

	private $option_handle  = 'mainwp_creport_branding_option';
	private $option         = array();
	private static $order   = '';
	private static $orderby = '';
	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Pro_Reports_Plugin();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );

		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_pro_reports_active_plugin', array( $this, 'ajax_active_plugin' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_upgrade_plugin', array( $this, 'ajax_upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_pro_reports_showhide_plugin', array( $this, 'ajax_showhide_wsal' ) );
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

	public function site_synced( $website, $information = array() ) {
		$website_id = $website->id;
		if ( is_array( $information ) && isset( $information['syncClientReportData'] ) && is_array( $information['syncClientReportData'] ) ) {
			$data = $information['syncClientReportData'];
			if ( isset( $data['firsttime_activated'] ) ) {
				$creportSettings = $this->get_option( 'settings' );

				if ( ! is_array( $creportSettings ) ) {
					$creportSettings = array();
				}

				$creportSettings[ $website_id ]['first_time'] = $data['firsttime_activated'];

				$this->set_option( 'settings', $creportSettings );
			}
		}
	}

	public static function gen_dashboard_tab( $websites ) {
		?>

		<table id="mainwp-pro-reports-sites-table" class="ui single line table" style="width:100%">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Version', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Hidden', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Last Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Log Start', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( is_array( $websites ) && count( $websites ) > 0 ) {
					self::gen_dashboard_table_rows( $websites );
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Version', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Hidden', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Last Report', 'mainwp-pro-reports-extension' ); ?></th>
					<th><?php _e( 'Log Start', 'mainwp-pro-reports-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-pro-reports-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>

		<script type="text/javascript">
		jQuery( '#mainwp-pro-reports-sites-table' ).DataTable( {
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
			"language": { "emptyTable": "No websites were found with the Child Reports plugin installed." },
			"drawCallback": function( settings ) {
				jQuery('#mainwp-pro-reports-sites-table .ui.checkbox').checkbox();
				jQuery( '#mainwp-pro-reports-sites-table .ui.dropdown').dropdown();
			},
		} );
		</script>
		<?php
	}

	public static function gen_dashboard_table_rows( $websites ) {
		$location    = 'options-general.php?page=mainwp-reports-page';
		$plugin_slug = 'mainwp-child-reports/mainwp-child-reports.php';
		$plugin_name = 'MainWP Child Reports';

		foreach ( $websites as $website ) {
			$website_id = $website['id'];

			$class_active = ( isset( $website['plugin_activated'] ) && ! empty( $website['plugin_activated'] ) ) ? '' : 'negative';
			$class_update = ( isset( $website['reports_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			$version = '';
			if ( isset( $website['reports_upgrade'] ) ) {
				if ( isset( $website['reports_upgrade']['new_version'] ) ) {
					$version = $website['reports_upgrade']['new_version'];
				}
			}
			// echo var_dump( $website );
			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['plugin_version'] ) ) ? $website['plugin_version'] : 'N/A'; ?>">
		<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>" data-tooltip="<?php esc_attr_e( 'Click to jump to the site Overview page', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted=""><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" data-tooltip="<?php esc_attr_e( 'Jump to the site WP Admin', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted=""><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank" data-tooltip="<?php esc_attr_e( 'Jump to the site front page', 'mainwp-pro-reports-extension' ); ?>" data-position="right center" data-inverted=""><?php echo $website['url']; ?></a></td>
				<td><span class="updating"></span> <?php echo ( isset( $website['reports_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['plugin_version'] ) ) ? $website['plugin_version'] : 'N/A'; ?></td>
				<td><span class="visibility"></span> <span class="wp-reports-visibility"><?php echo ( 1 == $website['hide_stream'] ) ? __( 'Yes', 'mainwp-pro-reports-extension' ) : __( 'No', 'mainwp-pro-reports-extension' ); ?></span></td>
				<td>
					<?php if ( $website['last_report'] ) : ?>
						<?php echo MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $website['last_report'] ) ); ?>
			<?php endif; ?>
				</td>
				<td>
					<?php if ( isset( $website['first_time'] ) && ! empty( $website['first_time'] ) ) : ?>
						<?php echo MainWP_Pro_Reports_Utility::format_timestamp( MainWP_Pro_Reports_Utility::get_timestamp( $website['first_time'] ) ); ?>
			<?php endif; ?>
				</td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999" data-tooltip="<?php esc_attr_e( 'See more options', 'mainwp-pro-reports-extension' ); ?>" data-position="left center" data-inverted="">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-pro-reports-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-pro-reports-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Open Child Reports', 'mainwp-pro-reports-extension' ); ?></a>
							<?php if ( 1 == $website['hide_stream'] ) : ?>
							<a class="item mainwp-pro-reports-showhide-plugin" href="#" showhide="show"><?php _e( 'Unhide Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php else : ?>
							<a class="item mainwp-pro-reports-showhide-plugin" href="#" showhide="hide"><?php _e( 'Hide Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['plugin_activated'] ) && empty( $website['plugin_activated'] ) ) : ?>
							<a class="item mainwp-pro-reports-activate-plugin" href="#"><?php _e( 'Activate Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['reports_upgrade'] ) ) : ?>
							<a class="item mainwp-pro-reports-update-plugin" href="#"><?php _e( 'Update Plugin', 'mainwp-pro-reports-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
		</td>
	  </tr>
			<?php
		}
	}

	public function get_websites_stream( $websites, $filter_groups = '', $lastReportsSites = array() ) {
		$websites_stream = array();

		$streamHide = $this->get_option( 'hide_stream_plugin' );

		if ( ! is_array( $streamHide ) ) {
				$streamHide = array();
		}

		$creportSettings = $this->get_option( 'settings' );

		if ( ! is_array( $creportSettings ) ) {
			$creportSettings = array();
		}

		if ( ! empty( $filter_groups ) ) {
			$filter_groups = explode( '-', $filter_groups );
		}

		if ( ! is_array( $filter_groups ) ) {
			$filter_groups = array();
		}

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $filter_groups ) ) {
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							$creportSiteSettings = array();

							if ( isset( $creportSettings[ $website->id ] ) ) {
								$creportSiteSettings = $creportSettings[ $website->id ];

								if ( ! is_array( $creportSiteSettings ) ) {
									$creportSiteSettings = array();
								}
							}

							foreach ( $plugins as $plugin ) {
								if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {

									$site = MainWP_Pro_Reports_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['plugin_activated'] = 1;
									} else {
										$site['plugin_activated'] = 0;
									}
									// get upgrade info
									$site['plugin_version'] = $plugin['version'];
									$plugin_upgrades        = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'] ) ) {
											$upgrade = $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'];
											if ( isset( $upgrade['update'] ) ) {
															  $site['reports_upgrade'] = $upgrade['update'];
											}
										}
									}

									$site['hide_stream'] = 0;
									if ( isset( $streamHide[ $website->id ] ) && $streamHide[ $website->id ] ) {
										$site['hide_stream'] = 1;
									}

									if ( isset( $creportSiteSettings['first_time'] ) ) {
										$site['first_time'] = $creportSiteSettings['first_time'];
									}
									$site['last_report'] = isset( $lastReportsSites[ $website->id ] ) ? $lastReportsSites[ $website->id ] : 0;
									$websites_stream[]   = $site;
									break;
								}
							}
						}
					}
				}
			} else {
				global $mainWPProReportsExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), array(), $filter_groups );
				$sites          = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' && in_array( $website->id, $sites ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							$creportSiteSettings = array();

							if ( isset( $creportSettings[ $website->id ] ) ) {
								$creportSiteSettings = $creportSettings[ $website->id ];
							}

							if ( ! is_array( $creportSiteSettings ) ) {
								$creportSiteSettings = array();
							}

							foreach ( $plugins as $plugin ) {
								if ( 'mainwp-child-reports/mainwp-child-reports.php' == $plugin['slug'] ) {

									$site = MainWP_Pro_Reports_Utility::map_site( $website, array( 'id', 'name', 'url' ) );

									if ( $plugin['active'] ) {
										$site['plugin_activated'] = 1;
									} else {
										$site['plugin_activated'] = 0;
									}

									$site['plugin_version'] = $plugin['version'];

									// get upgrade info
									$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
									if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
										if ( isset( $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'] ) ) {
											$upgrade = $plugin_upgrades['mainwp-child-reports/mainwp-child-reports.php'];
											if ( isset( $upgrade['update'] ) ) {
															  $site['reports_upgrade'] = $upgrade['update'];
											}
										}
									}

									$site['hide_stream'] = 0;
									if ( isset( $streamHide[ $website->id ] ) && $streamHide[ $website->id ] ) {
										$site['hide_stream'] = 1;
									}
									if ( isset( $creportSiteSettings['first_time'] ) ) {
										$site['first_time'] = $creportSiteSettings['first_time'];
									}
									$site['last_report'] = isset( $lastReportsSites[ $website->id ] ) ? $lastReportsSites[ $website->id ] : 0;
									$websites_stream[]   = $site;
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
			foreach ( $websites_stream as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_stream = $search_sites;
		}
		unset( $search_sites );

		return $websites_stream;
	}

	public static function gen_actions_bar( $websites ) {
		global $mainWPProReportsExtensionActivator;

		$groups = apply_filters( 'mainwp_getgroups', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), null );

		$filter_groups = array();
		if ( isset( $_GET['group'] ) && ! empty( $_GET['group'] ) ) {
			$filter_groups = explode( '-', $_GET['group'] );
		}

		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid mini form">
				<div class="ui two column row">
					<div class="column">
						<select id="mainwp-pro-reports-actions" class="ui dropdown">
							<option selected="selected" value=""><?php _e( 'Bulk Actions', 'mainwp-reports-extension' ); ?></option>
							<option value="activate-selected"><?php _e( 'Activate' ); ?></option>
							<option value="update-selected"><?php _e( 'Update' ); ?></option>
							<option value="hide-selected"><?php _e( 'Hide' ); ?></option>
							<option value="show-selected"><?php _e( 'Unhide' ); ?></option>
				  </select>
						<input type="button" value="<?php _e( 'Apply' ); ?>" class="ui mini button" id="mainwp-pro-reports-actions-button">
			</div>
					<div class="right aligned column">

			<form method="post" action="admin.php?page=Extensions-Mainwp-Pro-Reports-Extension&tab=dashboard">
					<?php echo __( 'Filter sites: ' ); ?>
					<select name="mainwp-pro-reports-groups-selection" id="mainwp-pro-reports-groups-selection" multiple="" class="ui dropdown">
						<option value=""><?php _e( 'All groups', 'mainwp-reports-extension' ); ?></option>
						<?php
						if ( is_array( $groups ) && count( $groups ) > 0 ) {
							foreach ( $groups as $group ) {

								$_select = '';
								if ( in_array( $group['id'], $filter_groups ) ) {
									$_select = 'selected ';
								}

								echo '<option value="' . $group['id'] . '" ' . $_select . '>' . $group['name'] . '</option>';
							}
						}
						?>
					</select>
				<input class="ui mini button" type="button" name="mainwp-pro-reports-sites-filter-button" id="mainwp-pro-reports-sites-filter-button" value="<?php _e( 'Filter Sites', 'mainwp-reports-extension' ); ?>">
			</form>
			</div>
		</div>
			</div>
		</div>
		<?php
		return;
	}

	public function ajax_active_plugin() {
		MainWP_Pro_Reports::verify_nonce();
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function ajax_upgrade_plugin() {
		MainWP_Pro_Reports::verify_nonce();
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function ajax_showhide_wsal() {
		MainWP_Pro_Reports::verify_nonce();
		$siteid   = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainWPProReportsExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'set_showhide',
				'showhide'   => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPProReportsExtensionActivator->get_child_file(), $mainWPProReportsExtensionActivator->get_child_key(), $siteid, 'client_report', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {
				$hide_stream = $this->get_option( 'hide_stream_plugin' );
				if ( ! is_array( $hide_stream ) ) {
					$hide_stream = array();
				}
				$hide_stream[ $siteid ] = ( 'hide' === $showhide ) ? 1 : 0;
				$this->set_option( 'hide_stream_plugin', $hide_stream );
			}

			die( json_encode( $information ) );
		}
		die();
	}

}
