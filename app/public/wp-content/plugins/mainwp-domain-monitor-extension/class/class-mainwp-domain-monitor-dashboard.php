<?php

namespace MainWP\Extensions\Domain_Monitor;

class MainWP_Domain_Monitor_Dashboard {

	// Singleton
	private static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Domain_Monitor_Dashboard
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_domain_monitor_check_site_domain', array( $this, 'ajax_domain_lookup' ) );
	}

	/**
	 * Render Actions Bar
	 *
	 * Renders the actions bar on the Dashboard tab.
	 */
	public static function render_actions_bar() {
		$page  = 'admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension';
		$nonce = wp_create_nonce( 'domain_monitor_nonce' );
		?>
		<div class="mainwp-actions-bar">
			<div class="ui two columns grid">
				<div class="column ui mini form">
					<select class="ui dropdown" id="mainwp-domain-monitor-bulk-actions-menu">
						<option value="-1"><?php _e( 'Bulk actions', 'mainwp-domain-monitor-extension' ); ?></option>
						<option value="check-sites"><?php _e( 'Check Domain', 'mainwp-domain-monitor-extension' ); ?></option>
						<option value="open-wpadmin"><?php _e( 'Go to WP Admin', 'mainwp-domain-monitor-extension' ); ?></option>
						<option value="open-frontpage"><?php _e( 'Go to Site', 'mainwp-domain-monitor-extension' ); ?></option>
					</select>
					<input type="button" name="mainwp-domain-monitor-bulk-actions-button" id="mainwp-domain-monitor-bulk-actions-button" class="ui basic mini button" value="<?php _e( 'Apply', 'mainwp-domain-monitor-extension' ); ?>"/>
				</div>
				<div class="right aligned middle aligned column">
					<a href="admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension&tab=dashboard&action=domain_lookup&domain_monitor_nonce=<?php echo wp_create_nonce( 'domain_monitor_nonce' ); ?>" id="mainwp-domain-lookup-start-button" class="ui mini green button" data-tooltip="<?php echo __( 'Click to start the domain lookup process for all sites.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="bottom right"><?php _e( 'Check All Sites Domains', 'mainwp-domain-monitor-extension' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Dashbaord tab
	 *
	 * Renders the dashbaord tab content - Domain Monitor table
	 *
	 * @param array $websites Child sites.
	 */
	public static function gen_dashboard_tab() {
		$websites = MainWP_Domain_Monitor_Admin::get_websites();
		if ( isset( $_GET['action'] ) && 'domain_lookup' == $_GET['action'] && isset( $_GET['domain_monitor_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['domain_monitor_nonce'] ), 'domain_monitor_nonce' ) ) {
			return self::mainwp_domain_monitor_modal();
		}
		?>
		<?php if ( MainWP_Domain_Monitor_Utility::show_mainwp_message( 'mainwp-domain-monitor-dashboard-info-message' ) ) : ?>
			<div class="ui info message">
				<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-domain-monitor-dashboard-info-message"></i>
				<?php echo sprintf( __( 'Get domain information for your child sites. For more information, review %1$shelp documentation%2$s.', 'mainwp-domain-monitor-extension' ), '<a href="https://kb.mainwp.com/docs/mainwp-domain-monitor-extension/" target="_blank">', '</a>' ); ?>
			</div>
		<?php endif; ?>
		<table class="ui single line table" id="mainwp-domain-monitor-sites-table" style="width:100%">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th id="site" class="collapsing"><?php _e( 'Site', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="sign-in" class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th id="url" class="collapsing"><?php _e( 'URL', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="domain-name"><?php _e( 'Domain Name', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="expires"><?php _e( 'Expires', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="creation-date"><?php _e( 'Creation Date', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="updated-date"><?php _e( 'Updated Date', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="expiry-date"><?php _e( 'Expiry Date', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="registrar"><?php _e( 'Registrar', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="domain-status"><?php _e( 'Domain Status', 'mainwp-domain-monitor-extension' ); ?></th>
					<th id="actions" class="no-sort collapsing right aligned"></th>
				</tr>
			</thead>
			<tbody>
				<?php self::render_dashboard_table_row( $websites ); ?>
			</tbody>
			<tfoot>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-domain-monitor-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php _e( 'Domain Name', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php _e( 'Expires', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php _e( 'Creation Date', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php _e( 'Updated Date', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php _e( 'Expiry Date', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php _e( 'Registrar', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php _e( 'Domain Status', 'mainwp-domain-monitor-extension' ); ?></th>
					<th class="no-sort collapsing"></th>
				</tr>
			</tfoot>
		</table>
		<?php self::render_domain_status_modal(); ?>
		<?php self::render_screen_options(); ?>
		<script type="text/javascript">
		jQuery( document ).ready( function () {
			$domain_monitor_sites_table = jQuery( '#mainwp-domain-monitor-sites-table' ).DataTable( {
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
				"language": { "emptyTable": "No websites found." },
				"drawCallback": function( settings ) {
					jQuery('#mainwp-domain-monitor-sites-table .ui.checkbox').checkbox();
					jQuery( '#mainwp-domain-monitor-sites-table .ui.dropdown').dropdown();
					mainwp_datatable_fix_menu_overflow();
				},
			} );

			_init_domain_monitor_sites_screen = function() {
				jQuery( '#mainwp-domain-monitor-sites-screen-options-modal input[type=checkbox][id^="mainwp_show_column_"]' ).each( function() {
					var check_id = jQuery( this ).attr( 'id' );
					col_id = check_id.replace( "mainwp_show_column_", "" );
					try {
						$domain_monitor_sites_table.column( '#' + col_id ).visible( jQuery( this ).is( ':checked' ) );
						if ( check_id.indexOf( "mainwp_show_column_desktop" ) >= 0 ) {
							col_id = check_id.replace( "mainwp_show_column_desktop", "" );
							$domain_monitor_sites_table.column( '#mobile' + col_id ).visible( jQuery( this ).is( ':checked' ) ); // to set mobile columns.
						}
					} catch( err ) {
						// to fix js error.
					}
				} );
			};
			_init_domain_monitor_sites_screen();

			mainwp_domain_monitor_sites_screen_options = function () {
				jQuery( '#mainwp-domain-monitor-sites-screen-options-modal' ).modal( {
					allowMultiple: true,
					onHide: function () {
					}
				} ).modal( 'show' );

				jQuery( '#domain-monitor-sites-screen-options-form' ).submit( function() {
					if ( jQuery('input[name=reset_domainmonitorsites_columns_order]').attr( 'value' ) == 1 ) {
						$domain_monitor_sites_table.colReorder.reset();
					}
					jQuery( '#mainwp-domain-monitor-sites-screen-options-modal' ).modal( 'hide' );
				} );
				return false;
			};

		} );
		</script>

		<?php
	}

	/**
	 * Render Domain Monitor Table Row
	 *
	 * Gets the Domain Monitor dashbaord table row.
	 *
	 * @param array $websites Child sites.
	 */
	public static function render_dashboard_table_row( $websites ) {
		foreach ( $websites as $website ) {
			$website_id  = $website['id'];
			$domain_data = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $website_id );
			if ( empty( $domain_data ) ) {
				continue;
			}

			$domain_statuses = array();

			if ( ! empty( $domain_data->domain_status_1 ) ) {
				$domain_statuses[] = $domain_data->domain_status_1;
			}

			if ( ! empty( $domain_data->domain_status_2 ) ) {
				$domain_statuses[] .= $domain_data->domain_status_2;
			}

			if ( ! empty( $domain_data->domain_status_3 ) ) {
				$domain_statuses[] .= $domain_data->domain_status_3;
			}

			if ( ! empty( $domain_data->domain_status_4 ) ) {
				$domain_statuses[] .= $domain_data->domain_status_4;
			}

			if ( ! empty( $domain_data->domain_status_5 ) ) {
				$domain_statuses[] .= $domain_data->domain_status_5;
			}

			if ( ! empty( $domain_data->domain_status_6 ) ) {
				$domain_statuses[] .= $domain_data->domain_status_6;
			}

			if ( ! empty( $domain_data->expiry_date ) ) {
				$expires = round( ( $domain_data->expiry_date - time() ) / ( 60 * 60 * 24 ) );
			} else {
				$expires = 0;
			}

			$color_code = 'green';
			if ( 30 > $expires && $expires >= 1 ) {
				$color_code = 'yellow';
			} elseif ( 1 > $expires ) {
				$color_code = 'red';
			}
			?>
			<tr class="" website-id="<?php echo $website_id; ?>">
				<td class="check-column"><span class="ui checkbox" data-tooltip="<?php esc_attr_e( 'Click to select the site.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="right center"><input type="checkbox" name="checked[]"></span></td>
				<td><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the site overview.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="right center" class="mainwp-site-name-link"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" class="open_newwindow_wpadmin" data-tooltip="<?php esc_attr_e( 'Jump to the WP Admin.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="right center"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank" class="open_site_url" data-tooltip="<?php esc_attr_e( 'Go to the website.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $website['url']; ?></a></td>
				<td><?php echo $domain_data->domain_name; ?></td>
				<td sort-value="<?php echo $expires; ?>"><span class="ui <?php echo $color_code; ?> fluid mini center aligned label"><?php echo $expires . __( ' days', 'mainwp-domain-monitor-extension' ); ?></span></td>
				<td><?php echo MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->creation_date ) ); ?></td>
				<td><?php echo MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->updated_date ) ); ?></td>
				<td><?php echo MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->expiry_date ) ); ?></td>
				<td><a href="<?php echo $domain_data->registrar_url; ?>" target="_blank" data-tooltip="<?php esc_attr_e( 'Visit registrar website.', 'mainwp-domain-monitor-extension' ); ?>" data-position="left center" data-inverted=""><?php echo $domain_data->registrar; ?></a></td>
				<td>
				<?php foreach ( $domain_statuses as $status ) : ?>
					<?php $domain_status = MainWP_Domain_Monitor_Utility::get_domain_status( $status ); ?>
					<span class="mainwp-domain-monitor-status-details">
						<a href="#" class="mainwp-domain-monitor-status-show-modal" data-tooltip="<?php esc_attr_e( 'See status details.', 'mainwp-domain-monitor-extension' ); ?>" data-position="left center" data-inverted=""><?php echo $domain_status['status']; ?></a><?php echo 1 < count( $domain_statuses ) ? ', ' : ''; ?>
						<input type="hidden" class="mainwp-domain-monitor-status-meaning" value="<?php echo $domain_status['meaning']; ?>">
						<input type="hidden" class="mainwp-domain-monitor-status-action" value="<?php echo $domain_status['action']; ?>">
					</span>
				<?php endforeach; ?>
				</td>
				<td class="right aligned">
					<div class="ui left pointing dropdown icon mini basic green button">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item domain-monitor-action-recheck" href="javascript:void(0)"><?php _e( 'Check Domain', 'mainwp-domain-monitor-extension' ); ?></a>
							<a class="item" href="admin.php?page=ManageSitesDomainMonitor&id=<?php echo $website_id; ?>"><?php _e( 'Domain Info', 'mainwp-domain-monitor-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-domain-monitor-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-domain-monitor-extension' ); ?></a>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Get columns.
	 *
	 * @return array Array of column names.
	 */
	public static function get_columns() {
		return array(
			'site'          => __( 'Site', 'mainwp-domain-monitor-extension' ),
			'sign-in'       => '<i class="sign in icon"></i>',
			'url'           => __( 'URL', 'mainwp-domain-monitor-extension' ),
			'domain-name'   => __( 'Domain Name', 'mainwp-domain-monitor-extension' ),
			'expires'       => __( 'Expires', 'mainwp-domain-monitor-extension' ),
			'creation-date' => __( 'Creation Date', 'mainwp-domain-monitor-extension' ),
			'updated-date'  => __( 'Updated Date', 'mainwp-domain-monitor-extension' ),
			'expiry-date'   => __( 'Expiry Date', 'mainwp-domain-monitor-extension' ),
			'registrar'     => __( 'Registrar', 'mainwp-domain-monitor-extension' ),
			'domain-status' => __( 'Domain Status', 'mainwp-domain-monitor-extension' ),
			'actions'       => __( 'Action', 'mainwp-domain-monitor-extension' ),
		);
	}

	/**
	 * Render screen options.
	 *
	 * @return array Array of default column names.
	 */
	public static function render_screen_options() {

		$columns = self::get_columns();

		$show_cols = get_user_option( 'mainwp_settings_show_domain_monitor_sites_columns' );

		if ( ! is_array( $show_cols ) ) {
			$show_cols = array();
		}

		?>
		<div class="ui modal" id="mainwp-domain-monitor-sites-screen-options-modal">
			<div class="header"><?php esc_html_e( 'Screen Options', 'mainwp-domain-monitor-extension' ); ?></div>
			<div class="scrolling content ui form">
				<form method="POST" action="" id="domain-monitor-sites-screen-options-form" name="domain_monitor_sites_screen_options_form">
					<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
					<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'DomainMonitorSitesScrOptions' ); ?>" />
						<div class="ui grid field">
							<label class="six wide column"><?php esc_html_e( 'Show columns', 'mainwp-domain-monitor-extension' ); ?></label>
							<div class="ten wide column">
								<ul class="mainwp_hide_wpmenu_checkboxes">
									<?php
									foreach ( $columns as $name => $title ) {
										?>
										<li>
											<div class="ui checkbox">
												<input type="checkbox"
												<?php
												$show_col = ! isset( $show_cols[ $name ] ) || ( 1 == $show_cols[ $name ] );
												if ( $show_col ) {
													echo 'checked="checked"';
												}
												?>
												id="mainwp_show_column_<?php echo esc_attr( $name ); ?>" name="mainwp_show_column_<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $name ); ?>">
												<label for="mainwp_show_column_<?php echo esc_attr( $name ); ?>" ><?php echo $title; ?></label>
												<input type="hidden" value="<?php echo esc_attr( $name ); ?>" name="show_columns_name[]" />
											</div>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
					</div>
				</div>
			<div class="actions">
					<div class="ui two columns grid">
						<div class="left aligned column">
							<span data-tooltip="<?php esc_attr_e( 'Returns this page to the state it was in when installed. The feature also restores any column you have moved through the drag and drop feature on the page.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="top center"><input type="button" class="ui button" name="reset" id="reset-domainmonitorsites-settings" value="<?php esc_attr_e( 'Reset Page', 'mainwp-domain-monitor-extension' ); ?>" /></span>
						</div>
						<div class="ui right aligned column">
					<input type="submit" class="ui green button" name="btnSubmit" id="submit-domainmonitorsites-settings" value="<?php esc_attr_e( 'Save Settings', 'mainwp-domain-monitor-extension' ); ?>" />
					<div class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp-domain-monitor-extension' ); ?></div>
				</div>
					</div>
				</div>
				<input type="hidden" name="reset_domainmonitorsites_columns_order" value="0">
			</form>
		</div>
		<div class="ui small modal" id="mainwp-domain-monitor-sites-site-preview-screen-options-modal">
			<div class="header"><?php esc_html_e( 'Screen Options', 'mainwp-domain-monitor-extension' ); ?></div>
			<div class="scrolling content ui form">
				<span><?php esc_html_e( 'Would you like to turn on home screen previews? This function queries WordPress.com servers to capture a screenshot of your site the same way comments shows you preview of URLs.', 'mainwp-domain-monitor-extension' ); ?>
			</div>
			<div class="actions">
				<div class="ui ok button"><?php esc_html_e( 'Yes', 'mainwp-domain-monitor-extension' ); ?></div>
				<div class="ui cancel button"><?php esc_html_e( 'No', 'mainwp-domain-monitor-extension' ); ?></div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function () {
				jQuery( '#reset-domain-monitor-settings' ).on( 'click', function () {
					mainwp_confirm( __( 'Are you sure.' ), function() {
						jQuery( '.mainwp_hide_wpmenu_checkboxes input[id^="mainwp_show_column_"]' ).prop( 'checked', false );
						//default columns
						var cols = ['site','url','actions'];
						jQuery.each( cols, function ( index, value ) {
							jQuery( '.mainwp_hide_wpmenu_checkboxes input[id="mainwp_show_column_' + value + '"]' ).prop( 'checked', true );
						} );
						jQuery( 'input[name=reset_domainmonitorsites_columns_order]' ).attr( 'value', 1 );
						jQuery( '#submit-domainmonitorsites-settings' ).click();
					}, false, false, true );
					return false;
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Domain Status Modal
	 *
	 * Renders the domain status info modal.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function render_domain_status_modal() {
		?>
		<div class="ui modal" id="mainwp-domain-status-info-modal">
			<div class="header"><?php _e( 'Domain Status Info', 'mainwp-domain-monitor-extension' ); ?></div>
			<div class="scrolling content"></div>
			<div class="actions">
				<div class="ui cancel reload button"><?php _e( 'Close', 'mainwp-domain-monitor-extension' ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Domain Monitor Modal
	 *
	 * Renders the progress modal.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function mainwp_domain_monitor_modal() {
		$websites  = MainWP_Domain_Monitor_Admin::get_websites();
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}

		$dbwebsites = MainWP_Domain_Monitor_Admin::get_db_sites( $sites_ids );

		$sites_to_process = array();

		if ( $dbwebsites ) {
			foreach ( $dbwebsites as $website ) {
				$sites_to_process[] = MainWP_Domain_Monitor_Utility::map_fields( $website, array( 'id', 'name' ) );
			}
		}
		?>
		<?php if ( count( $sites_to_process ) > 0 ) : ?>
		<div class="ui modal" id="mainwp-domain-monitor-sync-modal">
			<div class="header"><?php _e( 'Domain Lookup', 'mainwp-domain-monitor-extension' ); ?></div>
			<div class="ui green progress mainwp-modal-progress">
				<div class="bar"><div class="progress"></div></div>
				<div class="label"></div>
			</div>
			<div class="scrolling content">
				<div class="ui message" id="mainwp-domain-monitor-modal-progress-feedback" style="display:none"></div>
				<div class="ui relaxed divided list">
					<?php foreach ( $sites_to_process as $website ) : ?>
						<div class="item" siteid="<?php echo $website['id']; ?>" status="queue">
							<a href="admin.php?page=managesites&dashboard=<?php echo $website['id']; ?>" data-tooltip="<?php esc_attr_e( 'Go to the site Overview page.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a>
							<span class="right floated status"><span data-tooltip="<?php esc_attr_e( 'Pending.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="left center"><i class="clock outline icon"></i></span></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel reload button"><?php _e( 'Close', 'mainwp-domain-monitor-extension' ); ?></div>
			</div>
		</div>
		<script>
		  jQuery( document ).ready( function($) {
			  jQuery( '#mainwp-domain-monitor-sync-modal' ).modal( 'show' );
			  mainwp_domain_monitor_start_next();
		  } );
		</script>
			<?php return true; ?>
		<?php else : ?>
		<div class="ui yellow message"><?php _e( 'Sites not found.', 'mainwp-domain-monitor-extension' ); ?></div>
		<?php endif; ?>
		<?php
		return false;
	}

	/**
	 * Domain Lookup AJAX
	 *
	 * Perform the domain lookup via AJAX request.
	 */
	public function ajax_domain_lookup() {
		MainWP_Domain_Monitor_Admin::check_security();

		$site_id = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;
		$id      = isset( $_POST['item_id'] ) ? $_POST['item_id'] : 0;
		$update  = false;
		if ( 0 < $id ) {
			$update = true;
		}

		if ( $site_id ) {
			$dbwebsites = MainWP_Domain_Monitor_Admin::get_db_sites( array( $site_id ) );
			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website  = current( $dbwebsites );
				$domain   = MainWP_Domain_Monitor_Utility::get_domain(  MainWP_Domain_Monitor_Utility::get_nice_url( $website->url ) );
				$site_url = $website->url;
			}
		}

		if ( $site_id ) {
			MainWP_Domain_Monitor_Core::lookup_domain( $domain, $id, $site_id, $site_url, $update );

			die( wp_json_encode( array( 'status' => 'success' ) ) );
		}
		die( wp_json_encode( array( 'error' => __( 'Invalid site ID. Please reload and try again.', 'mainwp-domain-monitor-extension' ) ) ) );
	}
}
