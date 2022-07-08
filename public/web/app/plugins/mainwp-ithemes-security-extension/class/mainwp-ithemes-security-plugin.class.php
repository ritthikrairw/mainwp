<?php

class MainWP_IThemes_Security_Plugin {

	private $option_handle  = 'mainwp_ithemes_plugin_option';
	private $option         = array();
	private static $order   = '';
	private static $orderby = '';
	// Singleton
	private static $instance = null;
	static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new MainWP_IThemes_Security_Plugin();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_ithemes_active_plugin', array( $this, 'active_plugin' ) );
		add_action( 'wp_ajax_mainwp_ithemes_upgrade_plugin', array( $this, 'upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_ithemes_showhide_plugin', array( $this, 'ajax_showhide_plugin' ) );
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

	public static function gen_plugin_dashboard_tab( $websites ) {
		global $mainwp_itsec_globals;
		?>

		<table id="mainwp-ithemes-security-sites-table" class="ui single line table">

			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Version', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Status', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Banned Users', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Lockouts', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Hidden', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Settings', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
				</tr>
			</thead>
			<tbody id="the-mwp-ithemes-list" class="list:sites">
				<?php
				if ( is_array( $websites ) && count( $websites ) > 0 ) {
					self::get_plugin_dashboard_table_row( $websites );
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Version', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Status', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Banned Users', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Lockouts', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Hidden', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th><?php _e( 'Settings', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
		jQuery( '#mainwp-ithemes-security-sites-table' ).DataTable( {
			"stateSave": true,
			"stateDuration": 0, // forever
			"colReorder" : {
				fixedColumnsLeft: 1,
				fixedColumnsRight: 1
			},
			"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
			"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
			"order": [ [ 1, "asc" ] ],
			"language": { "emptyTable": "No websites were found with the iThemes Security plugin installed." },
			"drawCallback": function( settings ) {
				//jQuery( '#mainwp-ithemes-security-sites-table .ui.checkbox' ).checkbox();
				//jQuery( '#mainwp-ithemes-security-sites-table .ui.dropdown' ).dropdown();
			},
		} );
		</script>
		<?php
	}

	public static function get_plugin_dashboard_table_row( $websites ) {
		$location = 'admin.php?page=itsec';

		foreach ( $websites as $website ) {
			$website_id  = $website['id'];
			$plugin_name = ! empty( $website['is_pro'] ) ? 'iThemes Security Pro' : 'iThemes Security';

			$class_active  = ( isset( $website['ithemes_active'] ) && ! empty( $website['ithemes_active'] ) ) ? '' : 'negative';
			$class_update  = ( isset( $website['ithemes_upgrade'] ) ) ? 'warning' : '';
			$class_update  = ( 'negative' == $class_active ) ? 'negative' : $class_update;
			$site_status   = isset( $website['site_status'] ) ? $website['site_status'] : array();
			$lockout_count = is_array( $site_status ) && isset( $site_status['lockout_count'] ) ? $site_status['lockout_count'] : null;
			$scan_info     = is_array( $site_status ) && isset( $site_status['scan_info'] ) ? $site_status['scan_info'] : null;
			$count_bans    = is_array( $site_status ) && isset( $site_status['count_bans'] ) ? $site_status['count_bans'] : null;

			$version     = '';
			$plugin_slug = $website['plugin_slug'];
			if ( isset( $website['ithemes_upgrade'] ) ) {
				if ( isset( $website['ithemes_upgrade']['new_version'] ) ) {
					$version = $website['ithemes_upgrade']['new_version'];
				}
				if ( isset( $website['ithemes_upgrade']['plugin'] ) ) {
					$plugin_slug = $website['ithemes_upgrade']['plugin'];
				}
			}

			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['ithemes_plugin_version'] ) ) ? $website['ithemes_plugin_version'] : 'N/A'; ?>">
		<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td><span class="updating"></span> <?php echo ( isset( $website['ithemes_upgrade'] ) ) ? '<span data-tooltip="' . __( 'Update available', 'l10n-mainwp-ithemes-security-extension' ) . '" data-inverted="inverted"><i class="exclamation circle icon"></i></span>' : ''; ?> <?php echo ( isset( $website['ithemes_plugin_version'] ) ) ? $website['ithemes_plugin_version'] : 'N/A'; ?> <?php echo ( isset( $website['is_pro'] ) && $website['is_pro'] == 1 ) ? '(Pro)' : '(Free)'; ?></td>
				<td><?php echo self::render_status( $scan_info ); ?></td>
				<td><?php echo ( null !== $count_bans ? intval( $count_bans ) : '' ); ?></td>
				<td><?php echo ( null !== $lockout_count ? intval( $lockout_count ) : '' ); ?></td>
				<td class="wp-ithemes-visibility"><span class="visibility"></span> <?php echo ( 1 == $website['hide_ithemes'] ) ? __( 'Yes', 'l10n-mainwp-ithemes-security-extension' ) : __( 'No', 'l10n-mainwp-ithemes-security-extension' ); ?></td>
				<td><?php echo ( $website['individual_in_use'] ? __( 'Individual', 'l10n-mainwp-ithemes-security-extension' ) : __( 'General', 'l10n-mainwp-ithemes-security-extension' ) ); ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Open iThemes Security', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
							<?php if ( 1 == $website['hide_ithemes'] ) : ?>
							<a class="item mwp_ithemes_showhide_plugin" href="#" showhide="show"><?php _e( 'Unhide Plugin', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
							<?php else : ?>
							<a class="item mwp_ithemes_showhide_plugin" href="#" showhide="hide"><?php _e( 'Hide Plugin', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['ithemes_active'] ) && empty( $website['ithemes_active'] ) ) : ?>
							<a class="item mwp_ithemes_active_plugin" href="#"><?php _e( 'Activate Plugin', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['ithemes_upgrade'] ) ) : ?>
							<a class="item mwp_ithemes_upgrade_plugin" href="#"><?php _e( 'Update Plugin', 'l10n-mainwp-ithemes-security-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
		</td>
	  </tr>
			<?php
		}
	}

	public static function render_status( $status_info, $fluid = true ) {
		if ( ! is_array( $status_info ) || ! isset( $status_info ) ) {
			return '';
		}

		if ( 'clean' == $status_info['status'] ) {
			$cls = 'ui green center aligned label ';
		} else {
			$cls = 'ui yellow center aligned label ';
		}

		if ( $fluid ) {
			$cls .= 'fluid';
		}
		return '<span class="' . $cls . '">' . esc_html( $status_info['description'] ) . '</span>';
	}

	public function get_websites_with_the_plugin( $websites, $activated_only = false ) {
		$websites_itheme = array();

		$ithemeHide = $this->get_option( 'hide_the_plugin' );

		if ( ! is_array( $ithemeHide ) ) {
			$ithemeHide = array();
		}

		$itheme_data = MainWP_IThemes_Security_DB::get_instance()->get_settings_field_array();

		if ( is_array( $websites ) && count( $websites ) ) {
			foreach ( $websites as $website ) {
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'better-wp-security/better-wp-security.php' == $plugin['slug'] ) || ( 'ithemes-security-pro/ithemes-security-pro.php' == $plugin['slug'] ) ) {
									$site           = MainWP_IThemes_Security_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									$site['is_pro'] = ( 'ithemes-security-pro/ithemes-security-pro.php' == $plugin['slug'] ) ? 1 : 0;
								if ( $activated_only && ! $plugin['active'] ) {
									continue;
								}
								if ( $plugin['active'] ) {
									$site['ithemes_active'] = 1;
								} else {
									$site['ithemes_active'] = 0;
								}
									// get upgrade info
									$site['ithemes_plugin_version'] = $plugin['version'];
									$site['plugin_slug']            = $plugin['slug'];
									$plugin_upgrades                = json_decode( $website->plugin_upgrades, 1 );
								if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
									if ( isset( $plugin_upgrades['better-wp-security/better-wp-security.php'] ) ) {
										$upgrade = $plugin_upgrades['better-wp-security/better-wp-security.php'];
										if ( isset( $upgrade['update'] ) ) {
											$site['ithemes_upgrade'] = $upgrade['update'];
										}
									} elseif ( isset( $plugin_upgrades['ithemes-security-pro/ithemes-security-pro.php'] ) ) {
										$upgrade = $plugin_upgrades['ithemes-security-pro/ithemes-security-pro.php'];
										if ( isset( $upgrade['update'] ) ) {
											$site['ithemes_upgrade'] = $upgrade['update'];
										}
									}
								}

								$site['hide_ithemes'] = 0;
								if ( isset( $ithemeHide[ $website->id ] ) && $ithemeHide[ $website->id ] ) {
									$site['hide_ithemes'] = 1;
								}

								$site['individual_in_use'] = isset( $itheme_data[ $website->id ] ) ? $itheme_data[ $website->id ]['override'] : 0;

								if ( isset( $itheme_data[ $website->id ] ) ) {
									$site['site_status'] = $itheme_data[ $website->id ]['site_status'];
								}

								$websites_itheme[] = $site;
								break;
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
			foreach ( $websites_itheme as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_itheme = $search_sites;
		}
		unset( $search_sites );

		return $websites_itheme;
	}

	public static function gen_actions_row() {

		global $mainWPIThemesSecurityExtensionActivator;

		$groups = apply_filters( 'mainwp_getgroups', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), null );

		$filter_groups = array();
		if ( isset( $_GET['group'] ) && ! empty( $_GET['group'] ) ) {
			$filter_groups = explode( '-', $_GET['group'] );
		}

		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid mini form">
				<div class="ui two column row">
					<div class="column">
						<select class="ui dropdown" id="mwp_ithemes_plugin_action">
							<option value="-1"><?php _e( 'Bulk Actions', 'l10n-mainwp-ithemes-security-extension' ); ?></option>
							<option value="activate-selected"><?php _e( 'Activate', 'l10n-mainwp-ithemes-security-extension' ); ?></option>
							<option value="update-selected"><?php _e( 'Update', 'l10n-mainwp-ithemes-security-extension' ); ?></option>
							<option value="hide-selected"><?php _e( 'Hide', 'l10n-mainwp-ithemes-security-extension' ); ?></option>
							<option value="show-selected"><?php _e( 'Unhide', 'l10n-mainwp-ithemes-security-extension' ); ?></option>
			</select>
						<input type="button" value="<?php _e( 'Apply' ); ?>" class="ui mini button action" id="ithemes_plugin_doaction_btn" name="">
						<?php do_action( 'ithemes_ithemes_actions_bar_right' ); ?>
			</div>
					<div class="right aligned column">
						<form method="post" action="admin.php?page=Extensions-Mainwp-Ithemes-Security-Extension">
								<select name="mainwp-ithemes-security-groups-selection" id="mainwp-ithemes-security-groups-selection" multiple="" class="ui dropdown">
									<option value=""><?php _e( 'All groups', 'l10n-mainwp-ithemes-security-extension' ); ?></option>
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
							<input class="ui mini button" type="button" name="mainwp-ithemes-security-sites-filter-button" id="mainwp-ithemes-security-sites-filter-button" value="<?php _e( 'Filter Sites', 'l10n-mainwp-ithemes-security-extension' ); ?>">
							</form>
			</div>
		</div>
			</div>
		</div>
		<?php
	}

	public function active_plugin() {
		$_POST['websiteId'] = $_POST['ithemeSiteID'];
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function upgrade_plugin() {
		$_POST['websiteId'] = $_POST['ithemeSiteID'];
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function ajax_showhide_plugin() {
		$siteid   = isset( $_POST['ithemeSiteID'] ) ? $_POST['ithemeSiteID'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainWPIThemesSecurityExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'set_showhide',
				'showhide'   => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPIThemesSecurityExtensionActivator->get_child_file(), $mainWPIThemesSecurityExtensionActivator->get_child_key(), $siteid, 'ithemes', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'success' === $information['result'] ) {
				$hide_itheme = $this->get_option( 'hide_the_plugin' );
				if ( ! is_array( $hide_itheme ) ) {
					$hide_itheme = array();
				}
				$hide_itheme[ $siteid ] = ( 'hide' === $showhide ) ? 1 : 0;
				$this->set_option( 'hide_the_plugin', $hide_itheme );
			}
			die( json_encode( $information ) );
		}
		die();
	}
}
