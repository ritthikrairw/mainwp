<?php
class MainWP_Page_Speed_Dashboard {

	private $option_handle = 'mainwp_pagespeed_dashboard_option';
	private $option = array();
	private static $order = '';
	private static $orderby = '';

	//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Page_Speed_Dashboard::$instance ) {
			MainWP_Page_Speed_Dashboard::$instance = new MainWP_Page_Speed_Dashboard();
		}
		return MainWP_Page_Speed_Dashboard::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_pagespeed_upgrade_noti_dismiss', array( $this, 'ajax_dismiss_notice' ) );
		add_action( 'wp_ajax_mainwp_pagespeed_invalid_noti_dismiss', array( $this, 'ajax_dismiss_invalid_notice' ) );
		add_action( 'wp_ajax_mainwp_pagespeed_active_plugin', array( $this, 'ajax_active_plugin' ) );
		add_action( 'wp_ajax_mainwp_pagespeed_upgrade_plugin', array( $this, 'ajax_upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_pagespeed_showhide_pagespeed', array( $this, 'ajax_showhide_pagespeed' ) );
		add_action( 'wp_ajax_mainwp_pagespeed_perform_check_pages', array( $this, 'ajax_perform_check_pages' ) );
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

	public static function gen_dashboard_tab( $websites ) {
		$_orderby = 'name';
		$_order = 'desc';

		self::$order = $_order;
		self::$orderby = $_orderby;

		$strategy = Mainwp_Page_Speed::get_instance()->get_option( 'strategy' );

		?>
		<table class="ui single line table" id="mainwp-page-speed-sites-table" style="width:100%;">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-pagespeed-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-pagespeed-extension' ); ?></th>
					<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
					<th><?php _e( 'Desktop', 'mainwp-pagespeed-extension' ); ?></th>
					<?php endif; ?>
					<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
					<th><?php _e( 'Mobile', 'mainwp-pagespeed-extension' ); ?></th>
					<?php endif; ?>
					<th><?php _e( 'Version', 'mainwp-pagespeed-extension' ); ?></th>
					<th class="center aligned collapsing"><?php _e( 'Hidden', 'mainwp-pagespeed-extension' ); ?></th>
					<th><?php _e( 'Settings', 'mainwp-pagespeed-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-pagespeed-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( is_array( $websites ) && count( $websites ) > 0 ) : ?>
					<?php self::get_dashboard_table_row( $websites ); ?>
				<?php else : ?>
					<tr>
						<td colspan="8"><?php _e( 'No websites were found with the Google Pagespeed Insights plugin installed.', 'mainwp-pagespeed-extension' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<th class="check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site','mainwp-pagespeed-extension' ); ?></th>
					<th><i class="sign in icon"></i></th>
					<th><?php _e( 'URL','mainwp-pagespeed-extension' ); ?></th>
					<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
					<th><?php _e( 'Desktop','mainwp-pagespeed-extension' ); ?></th>
					<?php endif; ?>
					<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
					<th><?php _e( 'Mobile','mainwp-pagespeed-extension' ); ?></th>
					<?php endif; ?>
					<th><?php _e( 'Version','mainwp-pagespeed-extension' ); ?></th>
					<th><?php _e( 'Hidden','mainwp-pagespeed-extension' ); ?></th>
					<th><?php _e( 'Settings','mainwp-pagespeed-extension' ); ?></th>
					<th><?php _e( '', 'mainwp-pagespeed-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
		jQuery( '#mainwp-page-speed-sites-table' ).DataTable( {
			"stateSave": true,
			"stateDuration": 0,
			"scrollX": true,
			"colReorder" : {
				fixedColumnsLeft: 1,
				fixedColumnsRight: 1
			},
			"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
			"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
			"order": [ [ 1, "asc" ] ],
			"language": { "emptyTable": "No websites were found with the Google Pagespeed Insights plugin installed." },
			"drawCallback": function( settings ) {
				jQuery('#mainwp-page-speed-sites-table .ui.checkbox').checkbox();
				jQuery( '#mainwp-page-speed-sites-table .ui.dropdown').dropdown();
			},
		} );
		</script>

		<?php
	}

	public static function get_dashboard_table_row( $websites ) {

		$strategy = $strategy_child = Mainwp_Page_Speed::get_instance()->get_option( 'strategy' );
		$plugin_slug = 'google-pagespeed-insights/google-pagespeed-insights.php';

		foreach ( $websites as $website ) {
			$strategy_child = $strategy;

			if ( 1 == $website['override'] ) {
				$strategy_child = $website['strategy'];
			}

			$website_id = $website['id'];
			$location = 'admin.php?page=google-pagespeed-insights&render=report-list';

			$class_active = ( isset( $website['pagespeed_active'] ) && ! empty( $website['pagespeed_active'] ) ) ? 'pagespeed-active' : 'negative';
			$class_update = ( isset( $website['pagespeed_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			$version = "";

			if ( isset( $website['pagespeed_upgrade'] ) ) {
				if ( isset( $website['pagespeed_upgrade']['new_version'] ) ) {
					$version = $website['pagespeed_upgrade']['new_version'];
				}
				if ( isset( $website['pagespeed_upgrade']['plugin'] ) ) {
					$plugin_slug = $website['pagespeed_upgrade']['plugin'];
				}
			}

			if ( isset( $website['pagespeed_active'] ) && $website['pagespeed_active'] ) {
				if ( $website['bad_api_key'] ) {
					$invalid_location = 'tools.php?page=google-pagespeed-insights&render=options';
					$invalid_link = __( 'Google API Key Incorrect or Not Set.', 'mainwp-pagespeed-extension' ) . ' <a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $invalid_location ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Please, Enter a valid Google API Key', 'mainwp-pagespeed-extension' ) . '</a>';
				}
				if ( empty( $website['desktop_total_pages'] ) && empty( $website['mobile_total_pages'] ) ) {
					$not_found_mess = __( 'No Pagespeed Reports Found. Google Pagespeed may still be checking your pages.', 'mainwp-pagespeed-extension' ) . '<br>';
				}
			}
			?>

			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo $version; ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a> <span class="status-el"></span></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
					<td>
						<?php if ( 'mobile' == $strategy_child ) : ?>
							<div class="ui progress" style="margin-bottom: 0;">
							  <div class="bar">
									<div class="progress"><?php _e( 'Disabled', 'mainwp-pagespeed-extension'); ?></div>
								</div>
							</div>
						<?php else : ?>
							<?php $_scd = intval( $website['score_desktop'] ); ?>
							<div class="ui <?php echo self::get_score_color( $_scd ); ?> progress" data-value="<?php echo $_scd; ?>" data-total="100" style="margin-bottom: 0;">
							  <div class="bar">
									<div class="progress"><?php echo $_scd; ?></div>
								</div>
							</div>
						<?php endif; ?>
					</td>
				<?php endif; ?>
				<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
					<td>
						<?php if ( 'desktop' == $strategy_child ) : ?>
							<div class="ui progress" style="margin-bottom: 0;">
							  <div class="bar">
									<div class="progress"><?php _e( 'Disabled', 'mainwp-pagespeed-extension'); ?></div>
								</div>
							</div>
						<?php else : ?>
							<?php $_scm = intval( $website['score_mobile'] ); ?>
							<div class="ui <?php echo self::get_score_color( $_scm ); ?> progress" data-value="<?php echo $_scm; ?>" data-total="100" style="margin-bottom: 0;">
							  <div class="bar">
									<div class="progress"><?php echo $_scm; ?></div>
								</div>
							</div>
						<?php endif; ?>
					</td>
				<?php endif; ?>
				<td><span class="updating"></span><?php echo ( isset( $website['pagespeed_upgrade'] ) ) ? '<span data-tooltip="' . __( 'Update available', 'mainwp-pagespeed-extension' ) . '" data-inverted="inverted"><i class="exclamation circle icon"></i></span>' : ''; ?> <?php echo ( isset( $website['pagespeed_plugin_version'] ) ) ? $website['pagespeed_plugin_version'] : 'N/A'; ?></td>
				<td class="pagespeed-visibility"><?php echo ( 1 == $website['hide_pagespeed'] ) ? __( 'Yes', 'mainwp-pagespeed-extension' ) : __( 'No', 'mainwp-pagespeed-extension' ); ?></td>
				<td><?php echo ( $website['override'] ? __( 'Individual', 'mainwp-pagespeed-extension' ) : __( 'General', 'mainwp-pagespeed-extension' ) ); ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999" data-tooltip="<?php esc_attr_e( 'See more options', 'mainwp-page-speed-extension' ); ?>" data-position="left center" data-inverted="">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-pagespeed-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-pagespeed-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" target="_blank"><?php _e( 'Open Page Speed', 'mainwp-pagespeed-extension' ); ?></a>
							<?php if ( 1 == $website['hide_pagespeed'] ) : ?>
							<a class="item pagespeed_showhide_plugin" href="#" showhide="show"><?php _e( 'Unhide Plugin', 'mainwp-pagespeed-extension' ); ?></a>
							<?php else : ?>
							<a class="item pagespeed_showhide_plugin" href="#" showhide="hide"><?php _e( 'Hide Plugin', 'mainwp-pagespeed-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['pagespeed_active'] ) && empty( $website['pagespeed_active'] ) ) : ?>
							<a class="item pagespeed_active_plugin" href="#"><?php _e( 'Activate Plugin', 'mainwp-pagespeed-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['pagespeed_upgrade'] ) ) : ?>
							<a class="item pagespeed_upgrade_plugin" href="#"><?php _e( 'Update Plugin', 'mainwp-pagespeed-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	public static function get_score_color( $score ) {
		if ( $score <= 50 ) {
			$color = 'red';
		} else if ( $score > 51 && $score <= 90 ) {
			$color = 'yellow';
		} else if ( $score > 90 ) {
			$color = 'green';
		}
		return $color;
	}

	public function get_websites_pagespeed( $websites, $selected_group = 0, $pagespeed_data ) {
		$websites_plugin = array();

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' ) {
						$plugins = json_decode( $website->plugins, 1 );
						$site = $this->get_pagespeed_site_data( $plugins, $website, $pagespeed_data );
						if ( false !== $site ) {
							$websites_plugin[] = $site;
            }
					}
				}
			} else {
				global $mainWPPageSpeedExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$sites = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' && in_array( $website->id, $sites ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						$site = $this->get_pagespeed_site_data( $plugins, $website, $pagespeed_data );
						if ( false !== $site ) {
							$websites_plugin[] = $site;
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
				if ( false !== stripos( $website['name'], $find ) || false !== stripos( $website['url'], $find ) ) {
					$search_sites[] = $website;
				}
			}
			$websites_plugin = $search_sites;
		}
		unset( $search_sites );

		return $websites_plugin;
	}

	public function get_pagespeed_site_data( $plugins, $website, $pagespeed_data ) {
		if ( is_array( $plugins ) && 0 != count( $plugins ) ) {
			foreach ( $plugins as $plugin ) {
				if ( 'google-pagespeed-insights/google-pagespeed-insights.php' == $plugin['slug'] || strpos( $plugin['slug'], '/google-pagespeed-insights.php' ) !== false ) {
          $site = MainWP_Page_Speed_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
          $site['pagespeed_active'] = ( isset($plugin['active']) && $plugin['active'] ) ? 1 : 0;
          $site_data = isset( $pagespeed_data[ $site['id'] ] ) ? $pagespeed_data[ $site['id'] ] : array();
          $site['bad_api_key'] = isset( $site_data['bad_api_key'] ) ? $site_data['bad_api_key'] : false;
          $site['desktop_total_pages'] = isset( $site_data['desktop_total_pages'] ) ? $site_data['desktop_total_pages'] : 0;
          $site['mobile_total_pages'] = isset( $site_data['mobile_total_pages'] ) ? $site_data['mobile_total_pages'] : 0;
          $site['desktop_last_checked'] = isset( $site_data['desktop_last_checked'] ) ? $site_data['desktop_last_checked'] : 0;
          $site['mobile_last_checked'] = isset( $site_data['mobile_last_checked'] ) ? $site_data['mobile_last_checked'] : 0;
          $site['score_desktop'] = isset( $site_data['score_desktop'] ) ? $site_data['score_desktop'] : 0;
          $site['score_mobile'] = isset( $site_data['score_mobile'] ) ? $site_data['score_mobile'] : 0;
          $site['strategy'] = isset( $site_data['strategy'] ) ? $site_data['strategy'] : 'desktop';
          $site['override'] = isset( $site_data['override'] ) ? $site_data['override'] : 0;
          $site['pagespeed_plugin_version'] = $plugin['version'];
          $plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );

          if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
            if ( isset( $plugin_upgrades['google-pagespeed-insights/google-pagespeed-insights.php'] ) ) {
              $upgrade = $plugin_upgrades['google-pagespeed-insights/google-pagespeed-insights.php'];
              if ( isset( $upgrade['update'] ) ) {
              	$site['pagespeed_upgrade'] = $upgrade['update'];
              }
            }
          }

          $site['hide_pagespeed'] = ( isset( $site_data['hide_plugin'] ) && $site_data['hide_plugin'] ) ? 1 : 0;

          return $site;

          break;
				}
			}
		}
		return false;
	}

	public static function render_actions_bar() {
		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid mini form">
				<div class="ui two column row">
					<div class="column">
						<select class="ui dropdown" id="mwp_pagespeed_bulk_action">
							<option value="-1"><?php _e( 'Bulk Actions', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="activate-selected"><?php _e( 'Activate', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="update-selected"><?php _e( 'Update', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="hide-selected"><?php _e( 'Hide', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="show-selected"><?php _e( 'Unhide', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="check-pages"><?php _e( 'Recheck', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="force-recheck-pages"><?php _e( 'Force Recheck', 'mainwp-pagespeed-extension' ); ?></option>
						</select>
						<input type="button" name="mwp_pagespeed_action_btn" id="mwp_pagespeed_action_btn" class="ui basic mini button" value="<?php _e( 'Apply', 'mainwp-pagespeed-extension' ); ?>"/>
						<?php do_action( 'mainwp_pagespeed_actions_bar_right' ); ?>
					</div>
					<div class="right aligned column">
						<?php do_action( 'mainwp_pagespeed_actions_bar_right' ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return;
	}

	public function ajax_dismiss_notice() {
		$website_id = intval( $_POST['siteId'] );
		if ( $website_id ) {
			if ( session_id() == '' ) {
				session_start();
			}
			$dismiss = $_SESSION['mainwp_pagespeed_dismiss_upgrade_plugin_notis'];
			if ( is_array( $dismiss ) && count( $dismiss ) > 0 ) {
				$dismiss[ $website_id ] = 1;
			} else {
				$dismiss = array();
				$dismiss[ $website_id ] = 1;
			}
			$_SESSION['mainwp_pagespeed_dismiss_upgrade_plugin_notis'] = $dismiss;
			die( 'updated' );
		}
		die( 'nochange' );
	}

	public function ajax_dismiss_invalid_notice() {
		$website_id = intval( $_POST['siteId'] );
		if ( $website_id ) {
			if ( session_id() == '' ) {
				session_start(); }
			$dismiss = isset( $_SESSION['mainwp_pagespeed_dismiss_invalid_api_notis'] ) ? $_SESSION['mainwp_pagespeed_dismiss_invalid_api_notis'] : array();
			if ( is_array( $dismiss ) && count( $dismiss ) > 0 ) {
				$dismiss[ $website_id ] = 1;
			} else {
				$dismiss = array();
				$dismiss[ $website_id ] = 1;
			}
			$_SESSION['mainwp_pagespeed_dismiss_invalid_api_notis'] = $dismiss;
			die( 'updated' );
		}
		die( 'nochange' );
	}

	public function ajax_active_plugin() {
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function ajax_upgrade_plugin() {
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function ajax_showhide_pagespeed() {
		$siteid = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainWPPageSpeedExtensionActivator;
			$post_data = array(
				'mwp_action' => 'set_showhide',
				'showhide' 	 => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $siteid, 'page_speed', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {
				$website = apply_filters( 'mainwp_getsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $siteid );
				if ( $website && is_array( $website ) ) {
					$website = current( $website );
				}

				if ( ! empty( $website ) ) {
					MainWP_Page_Speed_DB::get_instance()->update_page_speed( array(
	            		'site_id' => $siteid,
						'hide_plugin' => ('hide' === $showhide) ? 1 : 0,
					) );
				}
			}

			die( json_encode( $information ) );
		}
		die();
	}

	public function ajax_perform_check_pages() {
		Mainwp_Page_Speed::check_security();
		$siteid = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;
		$force = isset( $_POST['forceRecheck'] ) ? $_POST['forceRecheck'] : false;
		if ( ! empty( $siteid ) ) {
			global $mainWPPageSpeedExtensionActivator;
			$post_data = array(
				'mwp_action' => 'check_pages',
				'force_recheck' => $force,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $siteid, 'page_speed', $post_data );
			die( json_encode( $information ) );
		}
		die();
	}
}
