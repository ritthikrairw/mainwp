<?php

class MainWP_Wordfence {

	public function __construct() {

	}

	public function admin_init() {
		foreach (
			array(
				'activityLogUpdate',
				'loadFirstActivityLog',
				'loadIssues',
				'deleteIssue',
				'bulkOperation',
				'deleteFile',
				'restoreFile',
				'updateIssueStatus',
				'updateAllIssues',
				'saveConfig',
				'saveDebuggingConfig',
				'saveDebuggingSettingsToSite',
				'ticker',
				'reverseLookup',
				'updateLiveTraffic',
				'blockIP',
				'unblockIP',
				'loadStaticPanel',
				'downgradeLicense',
				'exportSettings',
				'importSettings',
				'saveCacheConfig',
				'checkFalconHtaccess',
				'downloadHtaccess',
				'openChildSite',
				'saveCacheOptions',
				'clearPageCache',
				'getCacheStats',
				'getDiagnostics',
				'updateWAFRules',
				'updateWAFRules_New',
				'addCacheExclusion',
				'loadCacheExclusions',
				'removeCacheExclusion',
				'bulkSaveCacheConfig',
				'bulkSaveCacheOptions',
				'bulkAddCacheExclusion',
				'bulkRemoveCacheExclusion',
				'loadLiveTraffic',
				'whitelistWAFParamKey',
				'hideFileHtaccess',
				'fixFPD',
				'disableDirectoryListing',
				'deleteDatabaseOption',
				'deleteAdminUser',
				'revokeAdminUser',
				// 'loadBlockRanges',
				// 'clearAllBlocked',
				'permanentlyBlockAllIPs',
				'saveCountryBlocking',
				'unlockOutIP',
				'blockIPUARange',
				'permBlockIP',
				'unblockRange',
				'loadingSites',
				'saveWAFConfig',
				'whitelistBulkDelete',
				'whitelistBulkEnable',
				'whitelistBulkDisable',
				'updateConfig',
				'checkHtaccess',
				'getBlocks',
				'deleteBlocks',
				'makePermanentBlocks',
				'saveOptions',
				'load_wafData',
				'saveDisclosureState',
				'scan',
				'killScan',
				'recentTraffic',
				'whois',
			) as $func
		) {
			add_action( 'wp_ajax_mainwp_wfc_' . $func, 'MainWP_Wordfence::ajax_receiver' );
		}
		MainWP_Wordfence_Config::load_settings();
		$updateInt = MainWP_Wordfence_Config::get( 'actUpdateInterval', 2 );
		if ( ! preg_match( '/^\d+$/', $updateInt ) ) {
			$updateInt = 2;
		}
		$updateInt *= 1000;
		wp_localize_script(
			'mainwp-wordfence-extension-admin-log',
			'mainwp_WordfenceAdminVars',
			array(
				'ajaxURL'           => admin_url( 'admin-ajax.php' ),
				'firstNonce'        => wp_create_nonce( 'wp-ajax' ),
				'nonce'             => wp_create_nonce( 'wfc-nonce' ),
				'siteBaseURL'       => MainWP_Wordfence_Utility::get_site_base_url(),
				'debugOn'           => 0,
				'actUpdateInterval' => $updateInt,
				'modalTemplate'     => MainWP_wfView::create(
					'common/modal-prompt',
					array(
						'title'         => '${title}',
						'message'       => '${message}',
						'primaryButton' => array(
							'id'    => 'wf-generic-modal-close',
							'label' => __( 'Close', 'wordfence' ),
							'link'  => '#',
						),
					)
				)->render(),
				'modalHTMLTemplate' => MainWP_wfView::create(
					'common/modal-prompt',
					array(
						'title'         => '${title}',
						'message'       => '{{html message}}',
						'primaryButton' => array(
							'id'    => 'wf-generic-modal-close',
							'label' => __( 'Close', 'wordfence' ),
							'link'  => '#',
						),
					)
				)->render(),
			)
		);

		add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'sitestable_getcolumns' ), 10 );
		add_filter( 'mainwp_sitestable_sortable_columns', array( $this, 'sortable_columns' ), 10 );
		add_filter( 'mainwp_sitestable_item', array( $this, 'sitestable_item' ), 10 );
		add_action( 'mainwp_site_synced', array( &$this, 'site_synced' ), 10, 1 );
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site' ), 10, 1 );
		// to support display content in mainwp security scan
		add_action( 'mainwp_wordfence_sites', array( $this, 'render_general_tabs' ) );
	}

	public static function on_load_general_page() {
		// MainWP_Wordfence_Extension::activity_enqueue_style();
	}

	public static function open_site() {
		$id = $_GET['websiteid'];
		global $mainWPWordfenceExtensionActivator;
		$websites = apply_filters( 'mainwp_getdbsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), array( $id ) );
		$website  = null;
		if ( $websites && is_array( $websites ) ) {
			$website = current( $websites );
		}

		$open_location = '';
		if ( isset( $_GET['open_location'] ) ) {
			$open_location = $_GET['open_location'];
		}
		?>
		<div id="mainwp_background-box">
		<?php
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'dashboard', 'access_wpadmin_on_child_sites' ) ) {
			mainwp_do_not_have_permissions( 'WP-Admin on child sites' );
		} else {
			?>
			<?php _e( 'Will redirect to your website immediately.', 'mainwp-wordfence-extension' ); ?>
				<form method="POST"
					  action="<?php echo MainWP_Wordfence_Utility::get_data_authed( $website, $open_location ); ?>"
					  id="mfc_redirectForm">
				</form>
			<?php } ?>
		</div>
		<?php
	}

	public function sitestable_getcolumns( $columns ) {
		$columns['wfc_status'] = __( 'Wordfence', 'mainwp-wordfence-extension' );

		return $columns;
	}

	public function sortable_columns( $columns ) {
		$columns['wfc_status'] = array( 'wfc_status', false );
		return $columns;
	}

	public function sitestable_item( $item ) {
		$site_id = $item['id'];

		$settings = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $site_id );

		$status = 0;

		if ( $settings ) {
			$status = $settings->status;
		}

		if ( empty( $status ) ) {
			$icon_status        = '<i class="shield alternate grey icon"></i>';
			$item['wfc_status'] = '<span style="text-align: center;">' . $icon_status . '</span>';
		} else {
			if ( 1 == $status ) {
				$icon_status = '<i class="shield alternate green icon"></i>';
			} else {
				$icon_status = '<i class="shield alternate red icon"></i>';
			}
			$item['wfc_status'] = '<span style="text-align: center;"><a href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=result&site_id=' . $site_id . '">' . $icon_status . '</a></span>';
		}

		return $item;
	}

	public function site_synced( $website ) {
		if ( $website && $website->plugins != '' ) {
			$plugins = json_decode( $website->plugins, 1 );
			$status  = 0;
			if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
				foreach ( $plugins as $plugin ) {
					if ( 'wordfence/wordfence.php' == $plugin['slug'] ) {
						if ( $plugin['active'] ) {
							$status = 1;
						}
						break;
					}
				}
			}

			$update = array( 'site_id' => $website->id );

			if ( 1 == $status ) {
				global $mainWPWordfenceExtensionActivator;
				$post_data    = array( 'mwp_action' => 'loadIssues' );
				$information  = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $website->id, 'wordfence', $post_data );
				$count_issues = 0;
				if ( is_array( $information ) ) {
					if ( isset( $information['lastScanCompleted'] ) && 'ok' == $information['lastScanCompleted'] ) {
						if ( isset( $information['issueCount'] ) ) {
							$count_issues = $information['issueCount'];
						}
						if ( $count_issues > 0 ) {
							$status = 2;
						}
					}

					if ( isset( $information['apiKey'] ) ) {
						$update['apiKey'] = $information['apiKey'];
						$update['isPaid'] = isset( $information['isPaid'] ) ? $information['isPaid'] : 0;
					}
					if ( isset( $information['lastscan_timestamp'] ) && ! empty( $information['lastscan_timestamp'] ) ) {
						$update['lastscan'] = $information['lastscan_timestamp'];
					}
					// if (isset( $information['isNginx'] )) {
					// $update['isNginx'] = $information['isNginx'];
					// }

					$update['todayAttBlocked'] = isset( $information['todayAttBlocked'] ) ? intval( $information['todayAttBlocked'] ) : 0;
					$update['weekAttBlocked']  = isset( $information['weekAttBlocked'] ) ? intval( $information['weekAttBlocked'] ) : 0;
					$update['monthAttBlocked'] = isset( $information['monthAttBlocked'] ) ? intval( $information['monthAttBlocked'] ) : 0;

					if ( isset( $information['wafData'] ) ) {
						MainWP_Wordfence_DB::get_instance()->update_extra_settings_fields_values_by( $website->id, array( 'wafData' => $information['wafData'] ) );
					}
				}
			}
			$update['status'] = $status;
			MainWP_Wordfence_DB::get_instance()->update_setting( $update );
		}
	}

	public function delete_site( $website ) {
		if ( $website ) {
			MainWP_Wordfence_DB::get_instance()->delete_wordfence( (array) $website->id );
		}
	}

	public static function render_metabox() {
		$website_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;
		if ( empty( $website_id ) ) {
			return;
		}
		$mainwpWfData    = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $website_id );
		$status          = $lastscan = 0;
		$localBlockToday = $localBlockWeek = $localBlockMonth = '';
		if ( $mainwpWfData ) {
			$status          = $mainwpWfData->status;
			$lastscan        = $mainwpWfData->lastscan;
			$localBlockToday = $mainwpWfData->todayAttBlocked;
			$localBlockWeek  = $mainwpWfData->weekAttBlocked;
			$localBlockMonth = $mainwpWfData->monthAttBlocked;
		} else {
			global $mainWPWordfenceExtensionActivator;
			$option     = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);
			$sites_ids  = array( $website_id );
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $sites_ids, array(), $option );
			if ( $dbwebsites ) {
				$website = current( $dbwebsites );
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( 'wordfence/wordfence.php' == $plugin['slug'] ) {
								$status           = ! empty( $plugin['active'] ) ? 1 : 0;
								$update           = array( 'site_id' => $website_id );
								$update['status'] = $status;
								MainWP_Wordfence_DB::get_instance()->update_setting( $update );
								break;
							}
						}
					}
				}
			}
		}

		?>

		<h3 class="ui header handle-drag">
			Wordfence
			<div class="sub header"><?php echo __( 'The most recent Wordfence information', 'mainwp-wordfence-extension' ); ?></div>
		</h3>
		<?php

		if ( 0 == $status ) {
			?>
			<h2 class="ui icon header">
				<i class="info circle icon"></i>
				<div class="content">
					<?php echo __( 'Wordfence Not Detected', 'mainwp-wordfence-extension' ); ?>
					<div class="sub header"><?php echo __( 'First install and activate the Wordfence plugin on the child site.', 'mainwp-wordfence-extension' ); ?></div>
					<div class="ui hidden divider"></div>
				</div>
			</h2>
			<?php
			return;
		} else {
			if ( 1 == $status ) {
				$icon_status = '<i class="shield alternate green icon"></i>';
				$status_txt  = __( 'No issues Detected', 'mainwp-wordfence-extension' );
			} else {
				$icon_status = '<i class="shield alternate red icon"></i>';
				$status_txt  = __( 'Issues Detected', 'mainwp-wordfence-extension' );
			}

			$scan_lnk      = '<a href="#" class="wfc_metabox_scan_now_lnk" site-id="' . $website_id . '">' . __( 'Scan', 'mainwp-wordfence-extension' ) . '</a> | ';
			$result_lnk    = '' . __( 'Results', 'mainwp-wordfence-extension' ) . '</a> | ';
			$traffic_lnk   = '<a href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=traffic&site_id=' . $website_id . '">' . __( 'Live Traffic', 'mainwp-wordfence-extension' ) . '</a> | ';
			$dashboard_lnk = '<a href="admin.php?page=Extensions-Mainwp-Wordfence-Extension">' . __( 'Wordfence Dashboard', 'mainwp-wordfence-extension' ) . '</a>';

			?>
			<h2 class="ui icon header">
				<a href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=result&site_id=<?php echo $website_id; ?>"><?php echo $icon_status; ?></a>
				<div class="content">
					<?php echo $status_txt; ?>
					<div class="sub header"><?php echo __( 'Scanned on', 'mainwp-wordfence-extension' ) . ' ' . ( $lastscan ? MainWP_Wordfence_Utility::format_timestamp( $lastscan ) : '' ); ?></div>
					<div class="ui hidden divider"></div>
				</div>
			</h2>
			<h3 class="ui dividing header"><?php _e( 'Firewall Summary - Attacks Blocked', 'mainwp-wordfence-extension' ); ?></h3>
			<table class="ui single line table">
				<thead>
					<tr>
						<th class="center aligned"><?php echo __( 'Today', 'mainwp-wordfence-extension' ); ?></th>
						<th class="center aligned"><?php echo __( 'Last Week', 'mainwp-wordfence-extension' ); ?></th>
						<th class="center aligned"><?php echo __( 'Last Month', 'mainwp-wordfence-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="center aligned"><?php echo $localBlockToday; ?></td>
						<td class="center aligned"><?php echo $localBlockWeek; ?></td>
						<td class="center aligned"><?php echo $localBlockMonth; ?></td>
					</tr>
				</tbody>
			</table>
			<div class="ui hidden divider"></div>
			<div class="ui center aligned segment">
				<a href="admin.php?page=Extensions-Mainwp-Wordfence-Extension" class="ui big green button"><?php echo __( 'Wordfence Dashboard', 'mainwp-wordfence-extension' ); ?></a>
			</div>
			<?php
		}
	}

	public static function render() {

		$website = null;
		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			global $mainWPWordfenceExtensionActivator;
			$option     = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), array( $_GET['id'] ), array(), $option );

			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}
		$is_individual = false;
		if ( self::is_managesites_subpage() ) {
			$is_individual = true;
			$error         = '';
			if ( empty( $website ) ) {
				$error = __( 'Invalid site ID.', 'mainwp-wordfence-extension' );
			} else {
				$activated = false;
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'wordfence/wordfence.php' == $plugin['slug'] ) ) {
								if ( $plugin['active'] ) {
									$activated = true; }
								break;
							}
						}
					}
				}
				if ( ! $activated ) {
					$error = __( 'Wordfence plugin not detected on the site.', 'mainwp-wordfence-extension' );
				}
			}

			if ( ! empty( $error ) ) {
				do_action( 'mainwp_pageheader_sites', 'Wordfence' );
				echo '<div class="ui red message">' . $error . '</div>';
				do_action( 'mainwp_pagefooter_sites', 'Wordfence' );
				return;
			}
		}

		if ( $is_individual ) {
			do_action( 'mainwp_pageheader_sites', 'Wordfence' );
			self::render_individual_tabs( $website );
			do_action( 'mainwp_pagefooter_sites', 'Wordfence' );
		} else {
			self::render_general_tabs( $website );
		}
	}

	public static function is_managesites_subpage( $tabs = array() ) {
		if ( isset( $_GET['page'] ) && ( 'ManageSitesWordfence' == $_GET['page'] || 'managesites' == $_GET['page'] ) ) {
			return true;
		}
		return false;
	}

	public static function securityCheck( $json = true ) {
		$nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : null;
		if ( ! wp_verify_nonce( $nonce, 'wfc-nonce' ) ) {
			$err = 'Invalid security.';
			if ( $json ) {
				die( json_encode( array( 'error' => $err ) ) );
			} else {
				die( $err );
			}
		}
	}

	public static function render_individual_tabs( $website = false ) {
		$current_site_id = $website ? $website->id : 0;

		if ( empty( $current_site_id ) ) {
			return;
		}

		$current_tab = 'settings';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'traffic' ) {
				$current_tab = 'traffic';
			} elseif ( $_GET['tab'] == 'blocking' ) {
				$current_tab = 'blocking';
			} elseif ( $_GET['tab'] == 'diagnostics' ) {
				$current_tab = 'diagnostics';
			} elseif ( $_GET['tab'] == 'firewall' ) {
				$current_tab = 'firewall';
			} elseif ( $_GET['tab'] == 'scan' ) {
				$current_tab = 'scan';
			}
		}

		$base_page                 = 'admin.php?page=ManageSitesWordfence&id=' . $current_site_id;
		$lnk_tab_settings          = $base_page . '&tab=settings';
		$lnk_tab_firewall_settings = $base_page . '&tab=firewall';
		$lnk_tab_traffic_settings  = $base_page . '&tab=traffic';
		$lnk_tab_blocking_settings = $base_page . '&tab=blocking';
		$lnk_tab_scan_settings     = $base_page . '&tab=scan';
		$lnk_tab_diagnostic        = $base_page . '&tab=diagnostics';

		?>
		<div class="clear">
			<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-wordfence-menu">
				<a data-tab="setting" href="<?php echo $lnk_tab_settings; ?>" class="item <?php echo ( $current_tab == 'settings' ) ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Wordfence Options', 'mainwp-wordfence-extension' ); ?></a>
				<a data-tab="traffic" href="<?php echo $lnk_tab_traffic_settings; ?>" class="item <?php echo ( $current_tab == 'traffic' ) ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Live traffic', 'mainwp-wordfence-extension' ); ?></a>
				<a data-tab="blocking" href="<?php echo $lnk_tab_blocking_settings; ?>" class="item <?php echo ( $current_tab == 'blocking' ) ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Blocking', 'mainwp-wordfence-extension' ); ?></a>
				<a data-tab="firewall" href="<?php echo $lnk_tab_firewall_settings; ?>" class="item <?php echo ( $current_tab == 'firewall' ) ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Firewall', 'mainwp-wordfence-extension' ); ?></a>
				<a data-tab="scan" href="<?php echo $lnk_tab_scan_settings; ?>" class="item <?php echo ( $current_tab == 'scan' ) ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Scan', 'mainwp-wordfence-extension' ); ?></a>
				<a data-tab="diagnostic" href="<?php echo $lnk_tab_diagnostic; ?>" class="item <?php echo ( $current_tab == 'diagnostics' ) ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Diagnostics', 'mainwp-wordfence-extension' ); ?></a>
			</div>
		</div>
		<?php
		$_error = get_option( 'mainwp_wfc_save_individual_setting_error', false );
		if ( $_error ) {
			delete_option( 'mainwp_wfc_save_individual_setting_error' );
			?>
	  <div class="ui red message"><?php echo esc_html( $_error ); ?></div>
			<?php
		}
		?>
	<div id="mwp_wfc_ajax_message">
		<div class="status ui yellow message" style="display:none;"></div>
		<span class="loading" style="display:none;">
				<div class="ui active inverted dimmer">
				<div class="ui text loader">Saving...</div>
			  </div>
			</span>
		<div style="display: none" class="detailed"></div>
	</div>

	<div class="ui red message" id="mwp_wfc_ajax_error_message" style="display:none">
		<div class="error-message"></div>			
		<i class="ui close icon"></i>
	</div>

	<span id="mwp_wfc_current_site_id" site-id="<?php echo intval( $current_site_id ); ?>"></span>
		<form method="post" class="mwp_wfConfigForm" action="">
		  <div class="mainwp_wfc_tabs_content">
		  <?php

			$individual_saving_section = '';
			if ( $current_tab == 'settings' ) {
				MainWP_Wordfence_Setting::gen_settings_tab( true );
				$individual_saving_section = MainWP_Wordfence_Config::OPTIONS_TYPE_GLOBAL;
			} elseif ( $current_tab == 'traffic' ) {
				MainWP_Wordfence_Setting::gen_live_traffic_settings_tab( true );
				$individual_saving_section = MainWP_Wordfence_Config::OPTIONS_TYPE_LIVE_TRAFFIC;
			} elseif ( $current_tab == 'blocking' ) {
				MainWP_Wordfence_Setting::gen_blocking_settings_tab( true );
				$individual_saving_section = MainWP_Wordfence_Config::OPTIONS_TYPE_BLOCKING;
			} elseif ( $current_tab == 'firewall' ) {
				MainWP_Wordfence_Setting::gen_firewall_settings_tab( true );
				$individual_saving_section = MainWP_Wordfence_Config::OPTIONS_TYPE_FIREWALL;
			} elseif ( $current_tab == 'scan' ) {
				MainWP_Wordfence_Setting::gen_scan_settings_tab( true );
				$individual_saving_section = MainWP_Wordfence_Config::OPTIONS_TYPE_SCANNER;
			} elseif ( $current_tab == 'diagnostics' ) {
				MainWP_Wordfence_Setting::gen_diagnostics_tab( true );
				$individual_saving_section = MainWP_Wordfence_Config::OPTIONS_TYPE_DIAGNOSTICS;
			}
			?>

		<input type="hidden" name="_post_saving_section" id="_post_saving_section" value="<?php echo $individual_saving_section; ?>"  />
		<input type="hidden" id="wfc_individual_settings_site_id" name="wfc_individual_settings_site_id" value="<?php echo $current_site_id; ?>"  />
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'mwp-wfc-nonce' ); ?>"  />

		<?php if ( $current_tab !== 'traffic' ) { ?>
		<div class="ui divider"></div>
		  <input type="submit" value="<?php _e( 'Save Settings', 'mainwp-wordfence-extension' ); ?>" class="ui big green right floating button" id="submit" name="submit">
			<?php if ( $current_tab == 'settings' ) { ?>
			  <input type="button" value="<?php _e( 'Save General Settings to Child site', 'mainwp-wordfence-extension' ); ?>" class="ui big green basic button" id="wfc_btn_savegeneral">
		  <?php } ?>
		  <span id="wfc_save_settings_status"></span>
		</p>
		<?php } ?>
		  </div>
	  </form>
	<script type="text/javascript">
		<?php
		if ( $current_site_id ) {
			if ( get_option( 'mainwp_wfc_do_save_individual_setting' ) == 'yes' ) {
				update_option( 'mainwp_wfc_do_save_individual_setting', '' );
				?>
			jQuery(document).ready(function ($) {
			mainwp_wfc_save_site_settings(<?php echo $current_site_id; ?>);
			});
				<?php
			}
		}
		?>
	</script>
		<?php
	}

	public static function get_bulk_wfc_sites( $paged = 1, $actived = true, $selected_group = 0 ) {
		global $mainWPWordfenceExtensionActivator;
		$others    = array(
			'per_page' => MAINWP_BULK_NUMBER_SITES,
			'paged'    => $paged,
		);
		$websites  = apply_filters( 'mainwp_getsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), null, false, $others );
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}
		$option               = array(
			'plugin_upgrades' => true,
			'plugins'         => true,
		);
		$dbwebsites           = apply_filters( 'mainwp_getdbsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$dbwebsites_wordfence = MainWP_Wordfence_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites, $selected_group, false );
		$wfc_active_sites     = array();

		foreach ( $dbwebsites_wordfence as $wfc_website ) {
			if ( $actived ) {
				if ( isset( $wfc_website['wordfence_active'] ) && ! empty( $wfc_website['wordfence_active'] ) ) {
					$wfc_active_sites[ $wfc_website['id'] ] = $wfc_website['name'];
				}
			} else {
				$wfc_active_sites[ $wfc_website['id'] ] = $wfc_website;
			}
		}

		$last = ( empty( $websites ) || ( count( $websites ) < MAINWP_BULK_NUMBER_SITES ) ) ? true : false;

		unset( $websites );

		return array(
			'result' => $wfc_active_sites,
			'last'   => $last,
		);
	}

	public static function render_general_tabs( $website = null ) {
		if ( isset( $_GET['action'] ) && 'open_site' == $_GET['action'] ) {
			self::open_site();
			return;
		}

		if ( ! empty( $website ) ) {
			$settings = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $website->id );
			if ( empty( $settings->status ) ) {
				return;
			}
		}

		$style_tab_dashboard = $style_tab_scan = $style_tab_settings = $style_performance = $style_diagnostics = $style_tab_adv_blocking = $style_tab_traffic = ' style="display: none" ';

		$base_page                    = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension';
		$lnk_tab_live_traffic_network = $base_page . '&tab=network_traffic';
		$lnk_tab_firewall_network     = $base_page . '&tab=network_firewall';
		$lnk_tab_scan_network         = $base_page . '&tab=network_scan';
		$lnk_tab_blocking_network     = $base_page . '&tab=network_blocking';
		$lnk_tab_settings             = $base_page . '&tab=network_setting';
		$lnk_tab_diagnostic           = $base_page . '&tab=diagnostics';

		$current_site_id        = 0;
		$current_site_name      = '';
		$current_action         = $current_tab = $do_action  = '';
		$display_scan_in_widget = false;

		if ( ! empty( $website ) ) {
			$display_scan_in_widget = true;
			$style_tab_scan         = '';
			$current_site_id        = $website->id;
			$current_site_name      = $website->name;
		}

		$bulkDiagnosticsAction = false;

		$do_saving_section = '';
		if ( ! $display_scan_in_widget ) {
			if ( isset( $_GET['action'] ) ) {
				$current_action  = $_GET['action'];
				$current_site_id = isset( $_GET['site_id'] ) && ! empty( $_GET['site_id'] ) ? $_GET['site_id'] : 0;
				if ( 'result' == $current_action ) {
					$style_tab_scan = '';
				} elseif ( 'blocking' == $current_action ) {
					$style_tab_adv_blocking = '';
				} elseif ( 'traffic' == $current_action ) {
					$style_tab_traffic = '';
				} elseif ( isset( $_GET['bulk_diagnostic_action'] ) && ! empty( $_GET['bulk_diagnostic_action'] ) ) {
					self::securityCheck();
					$do_action             = $_GET['action'];
					$style_diagnostics     = '';
					$bulkDiagnosticsAction = true;
				} elseif ( 'bulk_import' == $_GET['action'] ) {
					self::securityCheck();
					$do_action          = 'bulk_import';
					$style_tab_settings = '';
				} elseif ( $current_action == 'diagnostics' ) {
					$style_diagnostics = '';
				}
			} elseif ( isset( $_GET['save'] ) ) {
				if ( 'settings' == $_GET['save'] ) {
					$do_action = 'save_settings';

					if ( isset( $_GET['wfSavingSection'] ) ) {
						$do_saving_section = $_GET['wfSavingSection'];
					}

					$style_tab_settings = '';
				} if ( 'firewall' == $_GET['save'] ) {
					$do_action   = 'save_firewall';
					$current_tab = 'network_firewall';
				}
			} else {
				if ( isset( $_GET['tab'] ) ) {
					if ( $_GET['tab'] == 'network_setting' ) {
						$current_tab        = 'network_setting';
						$style_tab_settings = '';
					} elseif ( $_GET['tab'] == 'network_traffic' ) {
						$current_tab = 'network_traffic';
					} elseif ( $_GET['tab'] == 'network_firewall' ) {
						$current_tab = 'network_firewall';
					} elseif ( $_GET['tab'] == 'network_scan' ) {
						$current_tab = 'network_scan';
					} elseif ( $_GET['tab'] == 'network_blocking' ) {
						$current_tab = 'network_blocking';
					} elseif ( $_GET['tab'] == 'performance' ) {
						$style_performance = '';
					} elseif ( $_GET['tab'] == 'diagnostics' ) {
						$current_tab       = 'diagnostics';
						$style_diagnostics = '';
					} else {
						$style_tab_dashboard = '';
					}
				} else {
					$style_tab_dashboard = '';
				}
			}
		}

		global $mainWPWordfenceExtensionActivator;

		$paged = isset( $_POST['paged'] ) ? $_POST['paged'] : 0;

		$selected_group = 0;

		if ( isset( $_POST['mainwp_wfc_plugin_groups_select'] ) ) {
			$selected_group = intval( $_POST['mainwp_wfc_plugin_groups_select'] );
		}

		$data                 = self::get_bulk_wfc_sites( $paged, false, $selected_group );
		$dbwebsites_wordfence = $data['result'];
		$last                 = $data['last'];

		if ( $current_site_id ) {
			$_website = apply_filters( 'mainwp_getsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $current_site_id, null );
			if ( is_array( $_website ) ) {
				$_website          = current( $_website );
				$current_site_name = $_website['name'];
			}
		}

		unset( $dbwebsites );

		if ( ! $display_scan_in_widget ) {
			?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-wordfence-menu">
			<a data-tab="dashboard" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension" class="item <?php echo( empty( $style_tab_dashboard ) ? 'active' : '' ); ?>"><i class="tasks icon"></i> <?php _e( 'Wordfence Dashboard', 'mainwp-wordfence-extension' ); ?></a>
			<a data-tab="setting" href="<?php echo $lnk_tab_settings; ?>" class="item  <?php echo( empty( $style_tab_settings ) ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Options', 'mainwp-wordfence-extension' ); ?></a>
			<a data-tab="network_traffic" href="<?php echo $lnk_tab_live_traffic_network; ?>" class="item  <?php echo( 'network_traffic' == $current_tab ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Live Traffic', 'mainwp-wordfence-extension' ); ?></a>
			<a data-tab="network_blocking" href="<?php echo $lnk_tab_blocking_network; ?>" class="item  <?php echo( 'network_blocking' == $current_tab ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Blocking', 'mainwp-wordfence-extension' ); ?></a>
			<a data-tab="scan" href="#" <?php echo $style_tab_scan; ?> class="item  <?php echo( 'result' == $current_action ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Scan Results', 'mainwp-wordfence-extension' ); ?></a>
			<a data-tab="blocking" href="#" <?php echo $style_tab_adv_blocking; ?> class="item  <?php echo( 'blocking' == $current_action ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Blocking', 'mainwp-wordfence-extension' ); // individual blocking ?></a>
			<a data-tab="traffic" href="#" <?php echo $style_tab_traffic; ?> class="item  <?php echo( 'traffic' == $current_action ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Live Traffic', 'mainwp-wordfence-extension' ); // individual traffic ?></a>
			<a data-tab="network_firewall" href="<?php echo $lnk_tab_firewall_network; ?>" class="item  <?php echo( 'network_firewall' == $current_tab ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Firewall', 'mainwp-wordfence-extension' ); ?></a>
			<a data-tab="network_scan" href="<?php echo $lnk_tab_scan_network; ?>" class="item  <?php echo( 'network_scan' == $current_tab ? 'active' : '' ); ?>"><i class="cog icon"></i><?php _e( 'Scan', 'mainwp-wordfence-extension' ); ?></a>
			<a data-tab="diagnostic" href="<?php echo $lnk_tab_diagnostic; ?>" class="item <?php echo( empty( $style_diagnostics ) ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Diagnostics', 'mainwp-wordfence-extension' ); ?></a>
		</div>

		<?php } ?>

	<span id="mwp_wfc_current_site_id" site-id="<?php echo intval( $current_site_id ); ?>"></span>
		<?php if ( ! $display_scan_in_widget ) : ?>
	  <div class="mainwp_wfc_tabs_content" data-tab="dashboard" <?php echo $style_tab_dashboard; ?>>
				<div class="mainwp-actions-bar">
					<div class="ui grid">
						<div class="ui two column row">
							<div class="middle aligned column ui mini form">
								<select class="ui dropdown" id="mwp_wfc_plugin_action">
									<option value="-1"><?php _e( 'Bulk Actions', 'mainwp-wordfence-extension' ); ?></option>
									<option value="activate-selected"><?php _e( 'Activate', 'mainwp-wordfence-extension' ); ?></option>
									<option value="update-selected"><?php _e( 'Update', 'mainwp-wordfence-extension' ); ?></option>
									<option value="hide-selected"><?php _e( 'Hide', 'mainwp-wordfence-extension' ); ?></option>
									<option value="show-selected"><?php _e( 'Unhide', 'mainwp-wordfence-extension' ); ?></option>
								</select>
								<input type="button" value="<?php _e( 'Apply' ); ?>" class="ui basic mini button action" id="wfc_plugin_doaction_btn" name="wfc_plugin_doaction_btn">
								<?php do_action( 'mainwp_wordfence_actions_bar_left' ); ?>
							</div>
							<div class="right aligned middle aligned column">
								<span id="mainwp_wfc_remind_change_status"></span>
								<a href="#" id="mainwp-wfc-kill-scan" class="ui mini button"><?php _e( 'Stop Scan Process', 'mainwp-wordfence-extension' ); ?></a>
								<a href="#" id="mainwp-wfc-run-scan" class="ui mini green button wfc-run-scan" title="<?php _e( 'Start a Wordfence Scan', 'mainwp-wordfence-extension' ); ?>"><?php _e( 'Scan all Child Sites', 'mainwp-wordfence-extension' ); ?></a>
								<?php do_action( 'mainwp_wordfence_actions_bar_right' ); ?>
							</div>
					</div>
					</div>
				</div>
				<div class="ui segment">				
					<?php MainWP_Wordfence_Plugin::gen_plugin_dashboard_tab( $dbwebsites_wordfence, $paged, $last ); ?>
					<div class="ui active inverted dimmer" id="mainwp-wfc-sites-table-loader">
						<div class="ui large text loader"><?php esc_html_e( 'Loading sites...', 'mainwp' ); ?></div>
					</div>
				</div>

				<script type="text/javascript">
					jQuery(document).ready(function ($) {
						var lastpaged = jQuery( '#mainwp-wordfence-sites-table-body' ).attr('last-paged') == '1' ? true : false;
						if ( ! lastpaged ){
							mainwp_wfc_load_more_dashboard_sites([<?php echo intval( $selected_group ); ?>]);
						} else {
							mainwp_wfc_dashboard_init_table();
						}
					} );
				</script>
	  </div>
	<?php endif; ?>

		<?php
		$wfc_active_sites = array();
		foreach ( $dbwebsites_wordfence as $wfc_website ) {
			if ( isset( $wfc_website['wordfence_active'] ) && ! empty( $wfc_website['wordfence_active'] ) ) {
				$wfc_active_sites[ $wfc_website['id'] ] = $wfc_website['name'];
			}
		}

		foreach ( $wfc_active_sites as $wp_id => $site_name ) {
			$w         = new MainWP_Wordfence_Config_Site( $wp_id ); // new: to load data
			$cacheType = $w->get_cacheType();
			?>
	  <span class="wfc_NetworkTrafficItemProcess" site-id="<?php echo $wp_id; ?>" site-name="<?php echo htmlspecialchars( stripslashes( $site_name ) ); ?>" status="queue" cacheType="<?php echo $cacheType; ?>">
	  </span>
			<?php
		}
		$general_save_section = '';
		?>
	
	<div class="ui red message" id="mwp_wfc_ajax_error_message" style="display:none">
		<div class="error-message"></div>			
		<i class="ui close icon"></i>
	</div>
	
	<div id="mainwp_wfc_tabs_wrapper">
		<div class="mainwp_wfc_tabs_content" <?php echo ( $current_tab == 'network_traffic' ) ? '' : 'style="display: none"'; ?>>
			 <?php
				if ( $current_tab == 'network_traffic' ) {
					MainWP_Wordfence_Setting::gen_live_traffic_settings_tab();
					$general_save_section = MainWP_Wordfence_Config::OPTIONS_TYPE_LIVE_TRAFFIC;
				}
				?>
		</div>

		<div class="mainwp_wfc_tabs_content mwp_wfc_settings_firewall_form_content" id="mwp_wfc_network_firewall_box" <?php echo ( 'network_firewall' == $current_tab ) ? '' : 'style="display: none"'; ?>>
			<?php
			if ( ! empty( $do_action ) && ( 'save_firewall' == $do_action ) ) {
				MainWP_Wordfence_Setting::gen_listing_sites( $do_action );
			} elseif ( 'network_firewall' == $current_tab ) {
				MainWP_Wordfence_Setting::gen_firewall_settings_tab();
				$general_save_section = MainWP_Wordfence_Config::OPTIONS_TYPE_FIREWALL;
			}
			?>
		</div>

		<div class="mainwp_wfc_tabs_content mwp_wfc_settings_scan_form_content" id="mwp_wfc_network_scan_box" <?php echo ( 'network_scan' == $current_tab ) ? '' : 'style="display: none"'; ?>>
			<?php
			if ( 'network_scan' == $current_tab ) {
				MainWP_Wordfence_Setting::gen_scan_settings_tab();
				$general_save_section = MainWP_Wordfence_Config::OPTIONS_TYPE_SCANNER;
			}
			?>
		</div>

		<div class="mainwp_wfc_tabs_content" id="mwp_wfc_network_blocking_box" <?php echo ( 'network_blocking' == $current_tab ) ? '' : 'style="display: none"'; ?>>
			<?php
			if ( 'network_blocking' == $current_tab ) {
				MainWP_Wordfence_Setting::gen_blocking_settings_tab();
				$general_save_section = MainWP_Wordfence_Config::OPTIONS_TYPE_BLOCKING;
			}
			?>
		</div>

		<div class="mainwp_wfc_tabs_content" data-tab="scan" <?php echo $style_tab_scan; ?>>
			<?php
			if ( $current_site_id && ( 'result' == $current_action || $display_scan_in_widget ) ) {
				MainWP_Wordfence_Log::gen_result_tab( $current_site_id, $current_site_name );
			}
			?>
		</div>

		<form method="post" class="mwp_wfConfigForm" action="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_setting">
			<div class="mainwp_wfc_tabs_content"  data-tab="setting" <?php echo $style_tab_settings; ?>>
				<?php
				if ( ! empty( $do_action ) && ( 'save_settings' == $do_action || 'bulk_import' == $do_action ) ) {
					MainWP_Wordfence_Setting::gen_listing_sites( $do_action, '', $do_saving_section );
				} elseif ( $current_tab == 'network_setting' ) {
					MainWP_Wordfence_Setting::gen_settings_tab(); // general settings
					$general_save_section = MainWP_Wordfence_Config::OPTIONS_TYPE_GLOBAL;
				}
				?>
			</div>
		</form>

		<div class="mainwp_wfc_tabs_content" data-tab="traffic" <?php echo $style_tab_traffic; ?>>
			<?php
			if ( $current_action == 'traffic' && $current_site_id ) {
				MainWP_Wordfence_Setting::gen_live_traffic_settings_tab();
			}
			?>
		</div>

		<div class="mainwp_wfc_tabs_content" data-tab="blocking" <?php echo $style_tab_adv_blocking; ?>>
			<?php
			if ( $current_action == 'blocking' && $current_site_id ) {
				MainWP_Wordfence_Setting::gen_blocking_settings_tab();
			}
			?>
		</div>

		<form method="post" id="wfDebuggingConfigForm" action="admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=diagnostic">
			<div class="mainwp_wfc_tabs_content" data-tab="diagnostic" <?php echo $style_diagnostics; ?>>
				<div class="ui segment">
					<?php
					if ( ! empty( $do_action ) && $bulkDiagnosticsAction ) {
						MainWP_Wordfence_Setting::gen_listing_sites( $do_action, 'diagnostics' );
					} elseif ( $current_tab == 'diagnostics' ) {
						MainWP_Wordfence_Setting::gen_diagnostics_tab();
						$general_save_section = MainWP_Wordfence_Config::OPTIONS_TYPE_DIAGNOSTICS;
					}
					?>
				</div>
			</div>
		</form>
		<input type="hidden" name="_post_saving_section" id="_post_saving_section" value="<?php echo $general_save_section; ?>"  />
	</div>

		<div class="ui modal" id="mainwp-wordfence-sync-modal2">
			<div class="header"></div>
			<div class="scrolling content">
				
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-wordfence-extension' ); ?></div>
			</div>
		</div>
		<?php
	}

	public static function showMainWPMessage( $type, $notice_id ) {
		if ( $type == 'tour' ) {
			$status = get_user_option( 'mainwp_tours_status' );
		} else {
			$status = get_user_option( 'mainwp_notice_saved_status' );
		}

		if ( ! is_array( $status ) ) {
				$status = array();
		}
		if ( isset( $status[ $notice_id ] ) ) {
			return false;
		}
		return true;
	}

	public static function ajax_receiver() {
		if ( ! MainWP_Wordfence_Utility::is_admin() ) {
			die( json_encode( array( 'error' => 'You appear to have logged out or you are not an admin. Please sign-out and sign-in again.' ) ) );
		}
		$func  = ( isset( $_POST['action'] ) && $_POST['action'] ) ? $_POST['action'] : $_GET['action'];
		$nonce = ( isset( $_POST['nonce'] ) && $_POST['nonce'] ) ? $_POST['nonce'] : $_GET['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'wp-ajax' ) ) {
			die( json_encode( array( 'error' => 'Your browser sent an invalid security token to MainWP Wordfence Extension. Please try reloading this page or signing out and in again.' ) ) );
		}
		// func is e.g. wordfence_ticker so need to munge it
		$func      = str_replace( 'mainwp_wfc_', '', $func );
		$returnArr = call_user_func( 'MainWP_Wordfence::ajax_' . $func . '_callback' );
		if ( false === $returnArr ) {
			$returnArr = array( 'error' => 'MainWP Wordfence Extension encountered an internal error executing that request.' );
		}

		if ( ! is_array( $returnArr ) ) {
			error_log( "Function $func did not return an array and did not generate an error." );
			$returnArr = array();
		}
		if ( isset( $returnArr['nonce'] ) ) {
			error_log( "MainWP Wordfence Extension ajax function return an array with 'nonce' already set. This could be a bug." );
		}
		$returnArr['nonce'] = wp_create_nonce( 'wp-ajax' );
		die( json_encode( $returnArr ) );
	}

	public static function check_valid() {
		$siteid = isset( $_POST['site_id'] ) ? $_POST['site_id'] : false;
		if ( empty( $siteid ) ) {
			self::json_error( __( 'Invalid data', MainWP_Wordfence_Extension::$plugin_translate ) );
		}
	}

	public static function ajax_loadFirstActivityLog_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'get_log' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		$events      = array();
		if ( isset( $information['events'] ) ) {
			$events = $information['events'];
			unset( $information['events'] );
		}

		$output     = $information;
		$newestItem = 0;
		ob_start();
		if ( sizeof( $events ) > 0 ) {
			$debugOn    = isset( $information['debugOn'] ) ? $information['debugOn'] : false;
			$timeOffset = isset( $information['timeOffset'] ) ? $information['timeOffset'] : 3600 * get_option( 'gmt_offset' );
			foreach ( $events as $e ) {
				if ( strpos( $e['msg'], 'SUM_' ) !== 0 ) {
					if ( $debugOn || $e['level'] < 4 ) {
						$typeClass = '';
						if ( $debugOn ) {
							$typeClass = ' wf' . $e['type'];
						}
						echo '<div class="wfActivityLine' . $typeClass . '">[' . date( 'M d H:i:s', $e['ctime'] + $timeOffset ) . ']&nbsp;' . $e['msg'] . '</div>';
					}
				}
				$newestItem = $e['ctime'];
			}
		} else {
			_e( 'A live stream of what Wordfence is busy with right now will appear in this box.', 'mainwp-wordfence-extension' );
			$output['not_found_events'] = true;
		}
		$output['result']    = ob_get_clean();
		$output['lastctime'] = $newestItem;
		die( json_encode( $output ) );
	}

	public static function ajax_activityLogUpdate_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action'    => 'update_log',
			'lastctime'     => $_POST['lastctime'],
			'lastissuetime' => $_POST['lastissuetime'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_loadIssues_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'loadIssues' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		if ( is_array( $information ) ) {
			$perform = false;
			$update  = array( 'site_id' => $siteid );
			if ( isset( $information['lastScanCompleted'] ) && 'ok' == $information['lastScanCompleted'] ) {
				$count_issues = 0;
				if ( isset( $information['issueCount'] ) ) {
					$count_issues = $information['issueCount'];
				}
				$status = 1;
				if ( $count_issues > 0 ) {
					$status = 2;
				}
				$update['status'] = $status;
				$perform          = true;
			}
			if ( isset( $information['lastscan_timestamp'] ) && ! empty( $information['lastscan_timestamp'] ) ) {
				$perform            = true;
				$update['lastscan'] = $information['lastscan_timestamp'];
			}
			if ( $perform ) {
				MainWP_Wordfence_DB::get_instance()->update_setting( $update );
			}
		}
		die( json_encode( $information ) );
	}

	public static function ajax_load_wafData_callback() {

		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'load_wafData' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		if ( is_array( $information ) ) {
			$perform = false;
			$update  = array( 'site_id' => $siteid );
			if ( isset( $information['wafData'] ) && ! empty( $information['lastScanCompleted'] ) ) {
				$update['wafData'] = $status;
				$perform           = true;
			}
			if ( $perform ) {
				MainWP_Wordfence_DB::get_instance()->update_setting( $update );
			}
		}

		die( json_encode( $information ) );
	}

	public static function ajax_saveDisclosureState_callback() {
		if ( isset( $_POST['name'] ) && isset( $_POST['state'] ) ) {
			$name  = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $_POST['name'] );
			$state = MainWP_wfUtils::truthyToBoolean( $_POST['state'] );
			if ( ! empty( $name ) ) {
				$disclosureStates          = MainWP_Wordfence_Config_Site::get_ser( 'disclosureStates', array() );
				$disclosureStates[ $name ] = $state;
				MainWP_Wordfence_Config::set_ser( 'disclosureStates', $disclosureStates );
				return array( 'ok' => 1 );
			}
		}

		return array(
			'err'      => 1,
			'errorMsg' => 'Required parameters not sent.',
		);
	}

	public static function ajax_killScan_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'killScan' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_scan_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'requestScan' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_deleteIssue_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'delete_issues',
			'id'         => $_POST['id'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_bulkOperation_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action' => 'bulkOperation',
			'op'         => $_POST['op'],
			// 'ids'        => $_POST['ids'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_deleteFile_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'delete_file',
			'issueID'    => $_POST['issueID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_hideFileHtaccess_callback() {
			self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'hide_file_htaccess',
			'issueID'    => $_POST['issueID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_fixFPD_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'fix_fpd',
			'issueID'    => $_POST['issueID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_disableDirectoryListing_callback() {
				self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'disable_directory_listing',
			'issueID'    => $_POST['issueID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_deleteDatabaseOption_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'delete_database_option',
			'issueID'    => $_POST['issueID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_misconfiguredHowGetIPsChoice_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'misconfigured_howget_ips_choice',
			'issueID'    => $_POST['issueID'],
			'choice'     => $_POST['choice'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_deleteAdminUser_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'delete_admin_user',
			'issueID'    => $_POST['issueID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_revokeAdminUser_callback() {
			self::check_valid();
			$siteid = $_POST['site_id'];
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'revoke_admin_user',
				'issueID'    => $_POST['issueID'],
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			die( json_encode( $information ) );
	}

	public static function ajax_loadBlockRanges_callback() {
			self::check_valid();
			$siteid = $_POST['site_id'];
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'load_block_ranges',
				'issueID'    => $_POST['issueID'],
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			die( json_encode( $information ) );
	}

	public static function ajax_clearAllBlocked_callback() {
			self::check_valid();
			$siteid = $_POST['site_id'];
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'clear_all_blocked',
				'op'         => $_POST['op'],
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			die( json_encode( $information ) );
	}

	public static function ajax_saveCountryBlocking_callback() {
			self::check_valid();
			$settings = array(
				'blockAction'       => $_POST['blockAction'],
				'codes'             => $_POST['codes'],
				'redirURL'          => $_POST['redirURL'],
				'loggedInBlocked'   => $_POST['loggedInBlocked'],
				'loginFormBlocked'  => $_POST['loginFormBlocked'],
				'restOfSiteBlocked' => $_POST['restOfSiteBlocked'],
				'bypassRedirURL'    => $_POST['bypassRedirURL'],
				'bypassRedirDest'   => $_POST['bypassRedirDest'],
				'bypassViewURL'     => $_POST['bypassViewURL'],
			);

			$update = array();
			if ( $is_individual || ( isset( $_POST['isFirstSaving'] ) && ! empty( $_POST['isFirstSaving'] ) ) ) {
					$update['cbl_action']            = $settings['blockAction'];
					$update['cbl_countries']         = $settings['codes'];
					$update['cbl_redirURL']          = $settings['redirURL'];
					$update['cbl_loggedInBlocked']   = $settings['loggedInBlocked'];
					$update['cbl_loginFormBlocked']  = $settings['loginFormBlocked'];
					$update['cbl_restOfSiteBlocked'] = $settings['restOfSiteBlocked'];
					$update['cbl_bypassRedirURL']    = $settings['bypassRedirURL'];
					$update['cbl_bypassRedirDest']   = $settings['bypassRedirDest'];
					$update['cbl_bypassViewURL']     = $settings['bypassViewURL'];
			}

			$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
			$siteid        = isset( $_POST['site_id'] ) ? $_POST['site_id'] : 0;

			if ( $is_individual ) {
					MainWP_Wordfence_DB::get_instance()->update_extra_settings_fields_values_by( $siteid, $update );
			} elseif ( isset( $_POST['isFirstSaving'] ) && ! empty( $_POST['isFirstSaving'] ) ) {
					self::network_saveCountryBlocking( $update );
			}

			if ( ! empty( $siteid ) ) {
				self::check_override_setting( $siteid, $is_individual );
			}

			global $mainWPWordfenceExtensionActivator;

			$post_data   = array(
				'mwp_action' => 'save_country_blocking',
				'settings'   => $settings,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			die( json_encode( $information ) );
	}


	public static function network_saveCountryBlocking( $opts ) {
		foreach ( $opts as $key => $val ) {
				MainWP_Wordfence_Config::set( $key, $val );
		}
			return array( 'ok' => 1 );
	}


	public static function ajax_permanentlyBlockAllIPs_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'permanently_block_all_ips',
			'type'       => $_POST['type'],
			'reason'     => $_POST['reason'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_unlockOutIP_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'unlockout_ip',
			'IP'         => $_POST['IP'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_blockIPUARange_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;

		// {"type":"custom-pattern","duration":0,"reason":"asdfasdfa","ipRange":"192.1.1.4-192.1.1.6","hostname":"","userAgent":"","referrer":""}
		$payload = array(
			'type'      => 'custom-pattern',
			'duration'  => 0,
			'reason'    => $_POST['reason'],
			'ipRange'   => $_POST['ipRange'],
			'hostname'  => $_POST['hostname'],
			'userAgent' => $_POST['uaRange'],
			'referrer'  => $_POST['referer'],

		);

		$post_data = array(
			'mwp_action' => 'createBlock',
			'payload'    => json_encode( $payload ),
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_permBlockIP_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'perm_block_ip',
			'IP'         => $_POST['IP'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_unblockRange_callback() {
			self::check_valid();
			$siteid = $_POST['site_id'];
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'unblock_range',
				'id'         => $_POST['id'],
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			die( json_encode( $information ) );
	}

	public static function ajax_loadingSites_callback() {
		if ( isset( $_POST['what'] ) ) {
				$what = $_POST['what'];
		}

		$paged                = isset( $_POST['paged'] ) ? $_POST['paged'] : 0;
		$data                 = self::get_bulk_wfc_sites( $paged, false );
		$dbwebsites_wordfence = $data['result'];
		$last                 = $data['last'];

		ob_start();

		if ( ! is_array( $dbwebsites_wordfence ) || count( $dbwebsites_wordfence ) <= 0 ) {
				echo '<div class="message ui yellow">' . __( 'No websites were found with the iThemes Security plugin installed.', 'mainwp-wordfence-extension' ) . '</div>';
		} else {
			?>
				<div class="ui relaxed divided list">				
			<?php
			foreach ( $dbwebsites_wordfence as $website ) {
				echo '<div class="item">';
					echo '<div><strong>' . stripslashes( $website['name'] ) . '</strong>: ';
					echo '<span class="siteItemProcess" site-id="' . $website['id'] . '" status="queue"><span class="status">Queue ...</span> <i class="fa fa-spinner fa-pulse" style="display:none"></i></span>';
					echo '</div>';
				echo '</div>';
			}
			?>
				</div>
			<?php
		}
		$html = ob_get_clean();
		die( json_encode( array( 'html' => $html ) ) );
	}

	public static function ajax_saveWAFConfig_callback() {
		$general = false;
		if ( isset( $_POST['general'] ) && $_POST['general'] ) {
			$general = true;
		}

		if ( ! $general ) {
			self::check_valid();
		}

		$post_data = array();

		$check_values = array(
			'wafStatus',
			'learningModeGracePeriodEnabled',
			'learningModeGracePeriod',
			'whitelistedPath',
			'whitelistedParam',
			'whitelistedEnabled',
			'oldWhitelistedPath',
			'oldWhitelistedParam',
			'newWhitelistedPath',
			'newWhitelistedParam',
			'deletedWhitelistedPath',
			'deletedWhitelistedParam',
			'whitelistedEnabled',
			'ruleEnabled',
			'ruleID',
		);

		$check_values = array( 'wafStatus', 'learningModeGracePeriodEnabled', 'learningModeGracePeriod' );
		foreach ( $check_values as $value ) {
			if ( isset( $_POST[ $value ] ) ) {
				$post_data[ $value ] = $_POST[ $value ];
			}
		}

		if ( $general ) {

			$settings = array(
				'wafStatus'                      => $post_data['wafStatus'],
				'learningModeGracePeriodEnabled' => intval( $post_data['learningModeGracePeriodEnabled'] ),
			);

			if ( $post_data['wafStatus'] == 'learning-mode' && ! empty( $post_data['learningModeGracePeriodEnabled'] ) ) {
				$gracePeriodEnd = strtotime( isset( $post_data['learningModeGracePeriod'] ) ? $post_data['learningModeGracePeriod'] : '' );
				if ( $gracePeriodEnd < time() ) {
					die( json_encode( array( 'error' => 'The grace period end time must be in the future.' ) ) );
					return;
				}
				$settings['learningModeGracePeriod'] = $post_data['learningModeGracePeriod']; // string of time
			}

			$current                     = get_option( 'mainwp_wfc_general_extra_settings', array() );
			$current['general_firewall'] = $settings;
			update_option( 'mainwp_wfc_general_extra_settings', $current );
			die( json_encode( array( 'ok' => 1 ) ) );
		}

		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data['mwp_action']      = 'save_waf_config';
		$post_data['wafConfigAction'] = $_POST['wafConfigAction'];

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['data'] ) ) {
			$update = array(
				'wafData'                        => $information['data'],
				'wafStatus'                      => $post_data['wafStatus'],
				'learningModeGracePeriodEnabled' => $post_data['learningModeGracePeriodEnabled'],
			);
			if ( isset( $information['learningModeGracePeriod'] ) ) {
				$update['learningModeGracePeriod'] = $information['learningModeGracePeriod'];
			}
			MainWP_Wordfence_DB::get_instance()->update_extra_settings_fields_values_by( $siteid, $update );

		}
		die( json_encode( $information ) );
	}


	public static function ajax_whitelistBulkDelete_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'whitelist_bulk_delete',
			'items'      => stripslashes( $_POST['items'] ), // to fix bug
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['data'] ) ) {
			MainWP_Wordfence_DB::get_instance()->update_extra_settings_fields_values_by( $siteid, array( 'wafData' => $information['data'] ) );
		}
		die( json_encode( $information ) );
	}

	public static function ajax_whitelistBulkEnable_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'whitelist_bulk_enable',
			'items'      => stripslashes( $_POST['items'] ), // to fix bug
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['data'] ) ) {
			MainWP_Wordfence_DB::get_instance()->update_extra_settings_fields_values_by( $siteid, array( 'wafData' => $information['data'] ) );
		}
		die( json_encode( $information ) );
	}

	public static function ajax_whitelistBulkDisable_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'whitelist_bulk_disable',
			'items'      => stripslashes( $_POST['items'] ), // to fix bug
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['data'] ) ) {
			MainWP_Wordfence_DB::get_instance()->update_extra_settings_fields_values_by( $siteid, array( 'wafData' => $information['data'] ) );
		}
		die( json_encode( $information ) );
	}


	public static function ajax_updateConfig_callback() {
			self::check_valid();
			$siteid = $_POST['site_id'];
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array(
				'mwp_action' => 'update_config',
				'key'        => $_POST['key'],
				'val'        => $_POST['val'],
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
			if ( is_array( $information ) && isset( $information['ok'] ) ) {
				// update settings value
				MainWP_Wordfence_DB::get_instance()->update_setting_fields_values_by( 'site_id', $siteid, array( $_POST['key'] => $_POST['val'] ) );
			}
			die( json_encode( $information ) );
	}

	public static function ajax_checkHtaccess_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'checkHtaccess',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_restoreFile_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'restore_file',
			'issueID'    => $_POST['issueID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_updateIssueStatus_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'updateIssueStatus',
			'id'         => $_POST['id'],
			'status'     => $_POST['status'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_updateAllIssues_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'update_all_issues',
			'op'         => $_POST['op'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_ticker_callback() {
		self::check_valid();
		$siteid          = $_POST['site_id'];
		$mode            = isset( $_POST['mode'] ) ? $_POST['mode'] : '';
		$cacheType       = $_POST['cacheType'];
				$alsoGet = $_POST['alsoGet'];

		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action'  => 'ticker',
			'alsoGet'     => $alsoGet,
			'otherParams' => $_POST['otherParams'],
		);
				self::map_params( $post_data );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['cacheType'] ) ) {
			$site_cacheType = $information['cacheType'];
			if ( 'activity' == $mode ) {
				if ( $cacheType != $site_cacheType ) {
					$information['reload'] = 'reload';
					MainWP_Wordfence_DB::get_instance()->update_setting(
						array(
							'site_id'   => $siteid,
							'cacheType' => $site_cacheType,
						)
					);
				}
			}
			$information['site_id'] = $siteid;
			if ( isset( $_POST['forceUpdate'] ) ) {
				$information['forceUpdate'] = true;
			} else {
				$information['forceUpdate'] = false;
			}
		}
		die( json_encode( $information ) );
	}

	public static function ajax_getBlocks_callback() {
		self::check_valid();

		$offset = 0;
		if ( isset( $_POST['offset'] ) ) {
			$offset = (int) $_POST['offset'];
		}

		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action' => 'getBlocks',
			'offset'     => $offset,
		);
		self::map_params( $post_data );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		die( json_encode( $information ) );

	}

	public static function ajax_deleteBlocks_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action' => 'deleteBlocks',
			'blocks'     => $_POST['blocks'],
		);
		self::map_params( $post_data );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		die( json_encode( $information ) );

	}

	public static function ajax_makePermanentBlocks_callback() {

		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action' => 'deleteBlocks',
			'updates'    => $_POST['updates'],
		);
		self::map_params( $post_data );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		die( json_encode( $information ) );

	}

		// ticker
	public static function ajax_loadLiveTraffic_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action' => 'load_live_traffic',
			'site_id'    => $siteid,
		);
		self::map_params( $post_data );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) ) {
			$information['site_id'] = $siteid;
		}
		die( json_encode( $information ) );
	}

	public static function ajax_whitelistWAFParamKey_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action' => 'white_list_waf',
			'site_id'    => $siteid,
		);

		if ( isset( $_POST['path'] ) ) {
			$post_data['path'] = $_POST['path'];
		}
		if ( isset( $_POST['paramKey'] ) ) {
			$post_data['paramKey'] = $_POST['paramKey'];
		}
		if ( isset( $_POST['failedRules'] ) ) {
			$post_data['failedRules'] = $_POST['failedRules'];
		}

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) ) {
			$information['site_id'] = $siteid;
		}
		die( json_encode( $information ) );
	}


	static function map_params( &$post_data ) {
		 // assign parameters
		if ( array_key_exists( 'groupby', $_REQUEST ) ) {
				$post_data['groupby'] = $_REQUEST['groupby'];
		}
		if ( isset( $_REQUEST['limit'] ) ) {
			 $post_data['limit'] = $_REQUEST['limit'];
		}
		if ( isset( $_REQUEST['offset'] ) ) {
			 $post_data['offset'] = $_REQUEST['offset'];
		}
		$post_data['since']     = isset( $_REQUEST['since'] ) ? $_REQUEST['since'] : '';
		$post_data['startDate'] = isset( $_REQUEST['startDate'] ) ? $_REQUEST['startDate'] : '';
		$post_data['endDate']   = isset( $_REQUEST['endDate'] ) ? $_REQUEST['endDate'] : '';

		if ( isset( $_REQUEST['param'] ) ) {
			 $post_data['param'] = $_REQUEST['param'];
		}
		if ( isset( $_REQUEST['operator'] ) ) {
			 $post_data['operator'] = $_REQUEST['operator'];
		}
		if ( isset( $_REQUEST['value'] ) ) {
			 $post_data['value'] = $_REQUEST['value'];
		}
	}

	public static function ajax_reverseLookup_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'reverse_lookup',
			'ips'        => $_POST['ips'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}


	public static function ajax_recentTraffic_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		$ip     = trim( $_POST['ip'] );
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'recentTraffic',
			'ip'         => $ip,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_saveOptions_callback() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-ajax' ) ) {
			wp_send_json( array( 'error' => esc_html( __( 'Invalid request' ) ) ) );
		}
		self::check_valid();
		$siteid        = $_POST['site_id'];
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;

		self::check_override_setting( $siteid, $is_individual );

		global $mainWPWordfenceExtensionActivator;

		$changes = json_decode( stripslashes( $_POST['changes'] ), true );

		$post_data   = array(
			'mwp_action' => 'saveOptions',
			'changes'    => json_encode( $changes ),
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_saveConfig_callback() {
		return self::handlePostSettings();
	}

	public static function handlePostSettings( $site_id = null ) {

		$opts = MainWP_Wordfence_Config::parseOptions();

		// debugging
		// error_log(print_r($opts, true));

		if ( empty( $opts ) || ! is_array( $opts ) ) {
			return array( 'errorMsg' => 'Empty settings' );
		}

		if ( $site_id ) {
			$old_settings = MainWP_Wordfence_DB::get_instance()->get_setting_fields_by( 'site_id', $site_id );
		} else {
			$old_settings = MainWP_Wordfence_Config::load_settings();
		}

		if ( ! is_array( $old_settings ) ) {
			$old_settings = array();
		}

		// do not need to do this, because those settings was filtered
		// foreach (MainWP_Wordfence_Config::$diagnosticParams as $param) {
		// check if saving diagnostic options then set the values
		// $opts[$param] = isset($old_settings[$param]) && $old_settings[$param] ? '1' : '0';
		// }

		// if saving then validate data
		if ( isset( $opts['alertEmails'] ) ) {
			$emails = array();
			foreach ( explode( ',', preg_replace( '/[\r\n\s\t]+/', '', $opts['alertEmails'] ) ) as $email ) {
				if ( strlen( $email ) > 0 ) {
					$emails[] = $email;
				}
			}
			if ( sizeof( $emails ) > 0 ) {
				$badEmails = array();
				foreach ( $emails as $email ) {
					if ( ! preg_match( '/^[^@]+@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,11})$/i', $email ) ) {
						$badEmails[] = $email;
					}
				}
				if ( sizeof( $badEmails ) > 0 ) {
					return array( 'errorMsg' => 'The following emails are invalid: ' . htmlentities( implode( ', ', $badEmails ) ) );
				}
				$opts['alertEmails'] = implode( ',', $emails );
			} else {
				$opts['alertEmails'] = '';
			}
		}

		// if saving then validate data
		if ( isset( $opts['learningModeGracePeriod'] ) ) {
			if ( $opts['wafStatus'] == 'learning-mode' && ! empty( $opts['learningModeGracePeriodEnabled'] ) ) {
				$gracePeriodEnd = strtotime( isset( $opts['learningModeGracePeriod'] ) ? $opts['learningModeGracePeriod'] : '' );
				if ( $gracePeriodEnd < time() ) {
					return array( 'errorMsg' => 'The grace period end time must be in the future.' );
				}
				$opts['learningModeGracePeriod'] = $gracePeriodEnd;
			}
		}

		// if saving then validate data
		if ( isset( $opts['scan_exclude'] ) ) {
			// $opts['scan_exclude'] = preg_replace( '/[\r\n\s\t]+/', '', $opts['scan_exclude'] );
			$opts['scan_exclude'] = MainWP_Wordfence_Utility::cleanupOneEntryPerLine( $opts['scan_exclude'] );
		}

		// if saving then validate data
		if ( isset( $opts['scan_include_extra'] ) ) {
			foreach ( explode( "\n", $opts['scan_include_extra'] ) as $regex ) {
				if ( @preg_match( "/$regex/", '' ) === false ) {
					return array( 'errorMsg' => '"' . esc_html( $regex ) . '" is not a valid regular expression' );
				}
			}
		}

		// if saving then validate data
		if ( isset( $opts['whitelisted'] ) ) {
			$whiteIPs = array();
			foreach ( explode( ',', preg_replace( '/[\r\n\s\t]+/', '', $opts['whitelisted'] ) ) as $whiteIP ) {
				if ( strlen( $whiteIP ) > 0 ) {
					$whiteIPs[] = $whiteIP;
				}
			}
			if ( sizeof( $whiteIPs ) > 0 ) {
				$badWhiteIPs = array();
				foreach ( $whiteIPs as $whiteIP ) {
					if ( ! preg_match( '/^[\[\]\-\d]+\.[\[\]\-\d]+\.[\[\]\-\d]+\.[\[\]\-\d]+$/', $whiteIP ) ) {
						$badWhiteIPs[] = $whiteIP;
					}
				}
				if ( sizeof( $badWhiteIPs ) > 0 ) {
					return array( 'errorMsg' => 'Please make sure you separate your IP addresses with commas. The following whitelisted IP addresses are invalid: ' . htmlentities( implode( ', ', $badWhiteIPs ) ) );
				}
				$opts['whitelisted'] = implode( ',', $whiteIPs );
			} else {
				$opts['whitelisted'] = '';
			}
		}

		// if saving then validate data
		if ( isset( $opts['loginSec_userBlacklist'] ) ) {
			$opts['loginSec_userBlacklist'] = MainWP_Wordfence_Utility::cleanupOneEntryPerLine( $opts['loginSec_userBlacklist'] );
		}

		// if saving then validate data
		if ( isset( $opts['liveTraf_ignoreIPs'] ) ) {
			$validIPs   = array();
			$invalidIPs = array();
			foreach ( explode( ',', preg_replace( '/[\r\n\s\t]+/', '', $opts['liveTraf_ignoreIPs'] ) ) as $val ) {
				if ( strlen( $val ) > 0 ) {
					if ( MainWP_Wordfence_Utility::is_valid_ip( $val ) ) {
						$validIPs[] = $val;
					} else {
						$invalidIPs[] = $val;
					}
				}
			}
			if ( sizeof( $invalidIPs ) > 0 ) {
				return array( 'errorMsg' => 'The following IPs you selected to ignore in live traffic reports are not valid: ' . wp_kses( implode( ', ', $invalidIPs ), array() ) );
			}
			if ( sizeof( $validIPs ) > 0 ) {
				$opts['liveTraf_ignoreIPs'] = implode( ',', $validIPs );
			}
		}

		// if saving then validate data
		if ( isset( $opts['liveTraf_ignoreUA'] ) ) {
			if ( preg_match( '/[a-zA-Z0-9\d]+/', $opts['liveTraf_ignoreUA'] ) ) {
				$opts['liveTraf_ignoreUA'] = trim( $opts['liveTraf_ignoreUA'] );
			} else {
				$opts['liveTraf_ignoreUA'] = '';
			}
		}

		// if saving then validate data
		if ( isset( $opts['liveTraf_maxRows'] ) ) {
			if ( ! is_numeric( $opts['liveTraf_maxRows'] ) ) {
				return array(
					'errorMsg' => 'Please enter a number for the amount of Live Traffic data to store.',
				);
			}
		}

		// if saving then validate data
		if ( isset( $opts['liveTraf_maxAge'] ) ) {
			if ( ! is_numeric( $opts['liveTraf_maxAge'] ) ) {
				return array(
					'errorMsg' => 'Please enter a number for the maximum days to keep Live Traffic data (minimum: 1).',
				);
			}
		}

		// if saving then validate data
		if ( isset( $opts['email_summary_enabled'] ) ) {
			if ( ! empty( $opts['email_summary_enabled'] ) ) {
				$opts['email_summary_enabled']              = 1;
				$opts['email_summary_interval']             = $opts['email_summary_interval'];
				$opts['email_summary_excluded_directories'] = MainWP_Wordfence_Utility::cleanupOneEntryPerLine( $opts['email_summary_excluded_directories'] );
			} else {
				$opts['email_summary_enabled'] = 0;
			}
		}

		if ( $site_id ) {
			if ( $opts ) {
				foreach ( $opts as $key => $val ) {
					$old_settings[ $key ] = $val;
				}
				$_update = array(
					'site_id'  => $site_id,
					'settings' => serialize( $old_settings ),
				);
				MainWP_Wordfence_DB::get_instance()->update_setting( $_update );
			}
		} else {
			foreach ( $opts as $key => $val ) {
				MainWP_Wordfence_Config::set( $key, $val );
			}
		}

		if ( isset( $_POST['apiKey'] ) && is_array( $_POST['apiKey'] ) && count( $_POST['apiKey'] ) > 0 ) {
			$_error = '';
			foreach ( $_POST['apiKey'] as $wid => $_apiKey ) {
				$_apiKey = trim( $_apiKey );
				if ( $_apiKey && ( ! preg_match( '/^[a-fA-F0-9]+$/', $_apiKey ) ) ) { // User entered something but it's garbage.
					$_error .= $_apiKey . '<br>';
				} else {
					MainWP_Wordfence_DB::get_instance()->update_setting(
						array(
							'site_id' => $wid,
							'apiKey'  => $_apiKey,
						)
					);
				}
			}
			if ( ! empty( $_error ) ) {
				$_error = 'You entered an API key but it is not in a valid format. It must consist only of characters A to F and 0 to 9:' . '<br>' . $_error;
				return array( 'errorMsg' => $_error );
			}
		}
		return array(
			'ok'         => 1,
			'reload'     => '',
			'paidKeyMsg' => false,
		);
	}

	public static function ajax_saveDebuggingConfig_callback() {
		$opts = array();
		foreach ( MainWP_Wordfence_Config::$diagnosticParams as $param ) {
			$opts[ $param ] = array_key_exists( $param, $_POST ) ? '1' : '0';
		}

		$site_id    = isset( $_POST['site_id'] ) ? $_POST['site_id'] : 0;
		$individual = isset( $_POST['individual'] ) ? $_POST['individual'] : 0;

		if ( $site_id ) {
			self::check_override_setting( $site_id, $individual );
		}

		if ( $individual ) {
			if ( $site_id ) {
				MainWP_Wordfence_DB::get_instance()->update_setting_fields_by( 'site_id', $site_id, $opts );
				self::check_override_setting( $site_id, true );
				global $mainWPWordfenceExtensionActivator;
				$post_data   = array(
					'mwp_action' => 'save_debugging_config',
					'settings'   => $opts,
				);
				$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $site_id, 'wordfence', $post_data );
				die( json_encode( $information ) );
			} else {
				die( json_encode( array( 'error' => 'Error: empty site id.' ) ) );
			}
		} else {
			foreach ( $opts as $key => $val ) {
				MainWP_Wordfence_Config::set( $key, $val );
			}
		}
		return array( 'ok' => 1 );
	}

	public static function ajax_saveDebuggingSettingsToSite_callback() {
		self::check_valid();
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$siteid        = $_POST['site_id'];
		self::check_override_setting( $siteid, $is_individual );

		$opts = array();

		if ( $is_individual ) {
				// process individual in ajax_saveDebuggingConfig_callback
				self::json_error( 'Invalid' );
		} else {
			foreach ( MainWP_Wordfence_Config::$diagnosticParams as $param ) {
					$opts[ $param ] = MainWP_Wordfence_Config::get( $param, 0 );
			}
		}
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'save_debugging_config',
			'settings'   => $opts,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}


	public static function ajax_blockIP_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;

		// payload: {"type":"ip-address","duration":0,"reason":"sfsd","ip":"192.1.1.1"}
		$payload = array(
			'type'     => 'ip-address',
			'duration' => 0,
			'reason'   => $_POST['reason'],
			'ip'       => $_POST['IP'],
		);

		$post_data   = array(
			'mwp_action' => 'createBlock',
			'payload'    => json_encode( $payload ),
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		$network = isset( $_POST['network'] ) && ! empty( $_POST['network'] ) ? true : false;
		if ( $network ) {
			if ( is_array( $information ) && isset( $information['error'] ) ) {
				$information['_error'] = $information['error'];
				unset( $information['error'] );
			}
			if ( is_array( $information ) && isset( $information['errorMsg'] ) ) {
				$information['_errorMsg'] = $information['errorMsg'];
				unset( $information['errorMsg'] );
			}
		}

		die( json_encode( $information ) );
	}

	public static function ajax_whois_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;

		$val         = trim( $_POST['val'] );
		$val         = preg_replace( '/[^a-zA-Z0-9\.\-:]+/', '', $val );
		$post_data   = array(
			'mwp_action' => 'whois',
			'val'        => $val,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}


	public static function ajax_unblockIP_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'unblock_ip',
			'IP'         => $_POST['IP'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		$network = isset( $_POST['network'] ) && ! empty( $_POST['network'] ) ? true : false;
		if ( $network ) {
			if ( is_array( $information ) && isset( $information['error'] ) ) {
				$information['_error'] = $information['error'];
				unset( $information['error'] );
			}
			if ( is_array( $information ) && isset( $information['errorMsg'] ) ) {
				$information['_errorMsg'] = $information['errorMsg'];
				unset( $information['errorMsg'] );
			}
		}

		die( json_encode( $information ) );
	}

	public static function ajax_loadStaticPanel_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'load_static_panel',
			'mode'       => $_POST['mode'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_downgradeLicense_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'downgrade_license' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		$update  = array( 'site_id' => $siteid );
		$perform = false;

		if ( is_array( $information ) && isset( $information['isPaid'] ) ) {
			$perform          = true;
			$update['isPaid'] = $information['isPaid'];
			$update['apiKey'] = $information['apiKey'];
		}
		if ( $perform ) {
			MainWP_Wordfence_DB::get_instance()->update_setting( $update );
		}

		die( json_encode( $information ) );
	}


	public static function ajax_importSettings_callback() {
		self::check_valid();
		$siteid      = $_POST['site_id'];
		$w           = new MainWP_Wordfence_Config_Site( $siteid ); // new: to load data
		$override    = $w->is_override();
		$bulkImport  = isset( $_POST['bulk_import'] ) && $_POST['bulk_import'] ? true : false;
		$save_import = isset( $_POST['save_import_settings'] ) && $_POST['save_import_settings'] ? true : false;

		if ( $bulkImport && $override ) {
			// if bulk import and overrided then dont update.
			die( json_encode( array( 'result' => 'OVERRIDED' ) ) );
		} elseif ( ! $bulkImport && ! $override ) {
			die( json_encode( array( 'errorImport' => 'Import Failed: Override General Settings need to be set to Yes.' ) ) );
		}

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'import_settings',
			'token'      => $_POST['token'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['settings'] ) && is_array( $information['settings'] ) ) {
			$settings = $information['settings'];
			$wfc_data = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $siteid );
			$option   = array();
			if ( $wfc_data && $wfc_data->settings ) {
				$option = unserialize( $wfc_data->settings );
			}
			if ( ! is_array( $option ) ) {
				$option = array();
			}
			$keys = MainWP_Wordfence_Config::getExportableOptionsKeys();
			foreach ( $keys as $key ) {
				if ( isset( $settings[ $key ] ) ) {
					$option[ $key ] = $settings[ $key ];
				}
			}
			$update = array( 'site_id' => $siteid );

			if ( isset( $settings['apiKey'] ) ) {
				$update['apiKey'] = $settings['apiKey'];
			}

			if ( isset( $settings['isPaid'] ) ) {
				$update['isPaid'] = $settings['isPaid'];
			}

			$update['settings'] = serialize( $option );
			MainWP_Wordfence_DB::get_instance()->update_setting( $update );

			if ( $save_import && $bulkImport ) {
				// save general import settings
				foreach ( $keys as $key ) {
					if ( isset( $settings[ $key ] ) ) {
						MainWP_Wordfence_Config::set( $key, $settings[ $key ] );
					}
				}
			}

			unset( $information['settings'] );
		}
		die( json_encode( $information ) );
	}

	public static function ajax_exportSettings_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];

		$w        = new MainWP_Wordfence_Config_Site( $siteid ); // new: to load data
		$override = $w->is_override();
		if ( ! $override ) {
			die( json_encode( array( 'errorExport' => 'Export Failed: Override General Settings need to be set to Yes.' ) ) );
		}

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'export_settings' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function check_override_setting( $siteid, $pIndividual = false ) {
		$w        = new MainWP_Wordfence_Config_Site( $siteid ); // new: to load data
		$override = $w->is_override();
		if ( $pIndividual && ! $override ) {
			die( json_encode( array( 'error' => 'Not Updated - Override General Settings need to be set to Yes.' ) ) );
		} elseif ( ! $pIndividual && $override ) {
			die( json_encode( array( 'error' => 'Not Updated - Individual site settings are in use' ) ) );
		}
		return true;
	}

	public static function ajax_saveCacheConfig_callback() {
		self::check_valid();
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$siteid        = isset( $_POST['site_id'] ) ? $_POST['site_id'] : 0;
		if ( ! empty( $siteid ) ) {
			self::check_override_setting( $siteid, $is_individual );
		}

		if ( $is_individual ) {
			$cacheType = $_POST['cacheType'];
		} else {
			$cacheType = MainWP_Wordfence_Config::get( 'cacheType' );
		}

		$noEditHtaccess = isset( $_POST['noEditHtaccess'] ) ? $_POST['noEditHtaccess'] : 0;

		global $mainWPWordfenceExtensionActivator;
		$post_data = array(
			'mwp_action'     => 'save_cache_config',
			'cacheType'      => $cacheType,
			'noEditHtaccess' => $noEditHtaccess,
		);
		if ( ! $is_individual ) {
			$post_data['needToCheckFalconHtaccess'] = 1;
		}
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['ok'] ) ) {
			$update = array(
				'site_id'   => $siteid,
				'cacheType' => $cacheType,
			);
			MainWP_Wordfence_DB::get_instance()->update_setting( $update );
		}
		die( json_encode( $information ) );
	}

	public static function ajax_bulkSaveCacheConfig_callback() {
		MainWP_Wordfence_Config::set( 'cacheType', (string) $_POST['cacheType'] );
		die( json_encode( array( 'ok' => 1 ) ) );
	}

	public static function ajax_checkFalconHtaccess_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'check_falcon_htaccess' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_downloadHtaccess_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'download_htaccess' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_openChildSite_callback() {
		self::check_valid();
		$website_id = $_POST['site_id'];

		global $mainWPWordfenceExtensionActivator;

		$websites = apply_filters( 'mainwp_getdbsites', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), array( $website_id ), '' );

		$website = null;
		if ( $websites && is_array( $websites ) ) {
			$website = current( $websites );
		}

		if ( is_null( $website ) ) {
			self::json_error( __( 'Cannot get child data', MainWP_Wordfence_Extension::$plugin_translate ) );
		}

		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'dashboard', 'access_wpadmin_on_child_sites' ) ) {
			$err = mainwp_do_not_have_permissions( 'WP-Admin on child sites' );
			self::json_error( $err );
		}

		$open_location = ( isset( $_POST['open_location'] ) ? trim( $_POST['open_location'] ) : '' );
		$open_location = substr( $open_location, strpos( $open_location, '/wp-admin' ) );

		if ( strlen( $open_location ) == 0 ) {
			self::json_error( __( 'Missing open location', MainWP_Wordfence_Extension::$plugin_translate ) );
		}
		$open_location = base64_encode( $open_location );
		die(
			json_encode(
				array(
					'ok'  => 1,
					'url' => MainWP_Wordfence_Utility::get_data_authed(
						$website,
						$open_location
					),
				)
			)
		);
	}

	public static function ajax_saveCacheOptions_callback() {
		self::check_valid();
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$siteid        = isset( $_POST['site_id'] ) ? $_POST['site_id'] : 0;
		if ( ! empty( $siteid ) ) {
			self::check_override_setting( $siteid, $is_individual );
		}

		if ( $is_individual ) {
			$cacheType         = $_POST['cacheType'];
			$allowHTTPSCaching = $_POST['allowHTTPSCaching'] == '1' ? 1 : 0;
			// $addCacheComment = $_POST['addCacheComment'] == '1' ? 1 : 0;
			$clearCacheSched = $_POST['clearCacheSched'] == '1' ? 1 : 0;

		} else {
			$allowHTTPSCaching = MainWP_Wordfence_Config::get( 'allowHTTPSCaching' );
			// $addCacheComment = MainWP_Wordfence_Config::get('addCacheComment');
			$clearCacheSched = MainWP_Wordfence_Config::get( 'clearCacheSched' );
		}

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action'        => 'save_cache_options',
			'allowHTTPSCaching' => $allowHTTPSCaching,
			// 'addCacheComment' => $addCacheComment,
			'clearCacheSched'   => $clearCacheSched,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && ( isset( $information['ok'] ) || isset( $information['updateErr'] ) ) ) {
			$w = new MainWP_Wordfence_Config_Site( $siteid );
			$w->set( 'allowHTTPSCaching', $allowHTTPSCaching );
			// $w->set('addCacheComment', $addCacheComment);
			$w->set( 'clearCacheSched', $clearCacheSched );
		}
		die( json_encode( $information ) );

	}

	public static function ajax_bulkSaveCacheOptions_callback() {
		MainWP_Wordfence_Config::set( 'allowHTTPSCaching', $_POST['allowHTTPSCaching'] == '1' ? 1 : 0 );
		// MainWP_Wordfence_Config::set('addCacheComment', $_POST['addCacheComment'] == '1' ? 1 : 0);
		MainWP_Wordfence_Config::set( 'clearCacheSched', $_POST['clearCacheSched'] == '1' ? 1 : 0 );
		die( json_encode( array( 'ok' => 1 ) ) );
	}

	public static function ajax_clearPageCache_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;

		$post_data = array( 'mwp_action' => 'clear_page_cache' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		die( json_encode( $information ) );
	}

	public static function ajax_getCacheStats_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;

		$post_data = array( 'mwp_action' => 'get_cache_stats' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		die( json_encode( $information ) );
	}

	public static function ajax_getDiagnostics_callback() {
		self::check_valid();
		$siteid = $_POST['site_id'];
		global $mainWPWordfenceExtensionActivator;

		$post_data = array( 'mwp_action' => 'get_diagnostics' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

		die( json_encode( $information ) );
	}

	public static function ajax_updateWAFRules_callback() {
		self::check_valid();
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$siteid        = $_POST['site_id'];
				self::check_override_setting( $siteid, $is_individual );
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'update_waf_rules' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}

	public static function ajax_updateWAFRules_New_callback() {
		self::check_valid();
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$siteid        = $_POST['site_id'];
		self::check_override_setting( $siteid, $is_individual );
		global $mainWPWordfenceExtensionActivator;
		$post_data   = array( 'mwp_action' => 'update_waf_rules_new' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		die( json_encode( $information ) );
	}


	public static function ajax_addCacheExclusion_callback() {
		self::check_valid();
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$siteid        = $_POST['site_id'];
		self::check_override_setting( $siteid, $is_individual );

		$patternType = $pattern = '';
		if ( $is_individual ) {
			$patternType = $_POST['patternType'];
			$pattern     = $_POST['pattern'];
			$id          = microtime( true );
		} else {
			$ex = MainWP_Wordfence_Config::get( 'cacheExclusions', false );
			if ( $ex ) {
				$ex = unserialize( $ex );
			} else {
				$ex = array();
			}

			$id = isset( $_POST['id'] ) ? $_POST['id'] : 0;
			for ( $i = 0; $i < sizeof( $ex ); $i++ ) {
				if ( (string) $ex[ $i ]['id'] == (string) $id ) {
					$patternType = $ex[ $i ]['pt'];
					$pattern     = $ex[ $i ]['p'];
					break;
				}
			}

			if ( empty( $id ) || empty( $patternType ) || empty( $pattern ) ) {
				self::json_error( __( 'Not found cache excution data', MainWP_Wordfence_Extension::$plugin_translate ) );
			}
		}

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action'  => 'add_cache_exclusion',
			'patternType' => $patternType,
			'pattern'     => $pattern,
			'id'          => $id,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['ex'] ) ) {
			$w = new MainWP_Wordfence_Config_Site( $siteid );
			$w->set( 'cacheExclusions', serialize( $information['ex'] ) );
			unset( $information['ex'] );
		}
		die( json_encode( $information ) );
	}

	public static function ajax_bulkAddCacheExclusion_callback() {
		$ex = MainWP_Wordfence_Config::get( 'cacheExclusions', false );
		if ( $ex ) {
			$ex = unserialize( $ex );
		} else {
			$ex = array();
		}
		$id   = microtime( true );
		$ex[] = array(
			'pt' => $_POST['patternType'],
			'p'  => $_POST['pattern'],
			'id' => $id,
		);
		MainWP_Wordfence_Config::set( 'cacheExclusions', serialize( $ex ) );
		die(
			json_encode(
				array(
					'ok' => 1,
					'id' => $id,
				)
			)
		);
	}

	public static function ajax_loadCacheExclusions_callback() {
		$siteid = $_POST['site_id'];
		if ( ! empty( $siteid ) ) {
			global $mainWPWordfenceExtensionActivator;
			$post_data   = array( 'mwp_action' => 'load_cache_exclusions' );
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );

			if ( is_array( $information ) && isset( $information['ok'] ) && isset( $information['ex'] ) ) {
				$w = new MainWP_Wordfence_Config_Site( $siteid );
				$w->set( 'cacheExclusions', serialize( $information['ex'] ) );
			}
			die( json_encode( $information ) );
		} else {
			$ex = MainWP_Wordfence_Config::get( 'cacheExclusions', false );
			if ( ! $ex ) {
				die( json_encode( array( 'ex' => false ) ) );
			}

			$ex = unserialize( $ex );
			die( json_encode( array( 'ex' => $ex ) ) );
		}
		die();
	}

	public static function ajax_removeCacheExclusion_callback() {
		self::check_valid();
		$is_individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$siteid        = $_POST['site_id'];

		if ( ! empty( $siteid ) ) {
			self::check_override_setting( $siteid, $is_individual );
		}

		global $mainWPWordfenceExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'remove_cache_exclusion',
			'id'         => $_POST['id'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWordfenceExtensionActivator->get_child_file(), $mainWPWordfenceExtensionActivator->get_child_key(), $siteid, 'wordfence', $post_data );
		if ( is_array( $information ) && isset( $information['ex'] ) ) {
			$w = new MainWP_Wordfence_Config_Site( $siteid );
			$w->set( 'cacheExclusions', serialize( $information['ex'] ) );
			unset( $information['ex'] );
		}

		die( json_encode( $information ) );
	}

	public static function ajax_bulkRemoveCacheExclusion_callback() {
		$id = $_POST['id'];
		$ex = MainWP_Wordfence_Config::get( 'cacheExclusions', false );
		if ( ! $ex ) {
			die( json_encode( array( 'ok' => 1 ) ) );
		}
		$ex = unserialize( $ex );
		for ( $i = 0; $i < sizeof( $ex ); $i++ ) {
			if ( (string) $ex[ $i ]['id'] == (string) $id ) {
				array_splice( $ex, $i, 1 );
				// Dont break in case of dups
			}
		}
		MainWP_Wordfence_Config::set( 'cacheExclusions', serialize( $ex ) );
		die( json_encode( array( 'ok' => 1 ) ) );
	}


	/**
	 * Used for sending error messages through json
	 * We use wp_send_json because it sets header to Content-Type: application/json
	 **/
	public static function json_error( $error ) {
		wp_send_json( array( 'error' => esc_html( $error ) ) );
	}

	/**
	 * Used for sending OK messages through json
	 * We use wp_send_json because it sets header to Content-Type: application/json
	 **/
	public static function json_ok( $message = null, $data = null ) {
		if ( is_null( $data ) ) {
			if ( is_null( $message ) ) {
				wp_send_json( array( 'success' => 1 ) );
			} else {
				wp_send_json( array( 'success' => esc_html( $message ) ) );
			}
		} else {
			if ( is_null( $message ) ) {
				wp_send_json(
					array(
						'success' => 1,
						'data'    => $data,
					)
				);
			} else {
				wp_send_json(
					array(
						'success' => esc_html( $message ),
						'data'    => $data,
					)
				);
			}
		}

		self::json_error( __( 'Invalid data', MainWP_Wordfence_Extension::$plugin_translate ) );
	}


}
