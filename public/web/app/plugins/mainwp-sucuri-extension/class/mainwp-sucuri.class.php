<?php
/**
 * MainWP Sucuri
 *
 * This is the main MainWP Sucuri Class file.
 */

/**
 * Class MainWP_Sucuri
 */
class MainWP_Sucuri {

	/**
	 * Public static variable to hold the single instance of MainWP_Sucuri.
	 *
	 * @var mixed Default null
	 */
	public static $instance = null;

	/** @var string MainWP Sucuri Nonce Token. */
	public static $nonce_token = 'mainwp-sucuri-extension-';

	/**
	 * Create a public static instance of MainWP_Sucuri.
	 *
	 * @return MainWP_Sucuri|mixed|null
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Sucuri();
		}
		return self::$instance;
	}

	/**
	 * MainWP_Sucuri constructor.
	 */
	public function __construct() {
		add_action( 'mainwp_cron_jobs_list', array( $this, 'cron_job_info' ) );
	}

	/**
	 * MainWP Sucuri initiator.
	 */
	public function init() {
		add_action( 'wp_ajax_mainwp_sucuri_security_scan', array( $this, 'ajax_sucuri_scan' ), 10, 2 );
		add_action( 'wp_ajax_mainwp_sucuri_scan_site_action', array( $this, 'ajax_sucuri_scan_site' ), 10, 2 );
		add_action( 'wp_ajax_mainwp_sucuri_delete_report', array( $this, 'delete_report' ) );
		add_action( 'wp_ajax_mainwp_sucuri_show_report', array( $this, 'show_report' ) );
		add_action( 'wp_ajax_mainwp_sucuri_change_remind', array( $this, 'ajax_change_remind' ) );
		add_action( 'wp_ajax_mainwp_sucuri_sslverify_certificate', array( $this, 'ajax_save_ssl_verify' ) );
		add_action( 'mainwp_sucuri_extension_cronsecurityscan_notification', array( 'MainWP_Sucuri', 'cronsecurityscan_notification' ) );
		add_filter( 'mainwp_sucuri_scan_data', array( $this, 'sucuri_scan_data' ) );
		add_action( 'mainwp_sucuri_scan_finished', array( &$this, 'sucuri_scan_done' ), 10, 4 ); // to fix action for wp cli.
		add_filter( 'mainwp_header_actions_right', array( $this, 'screen_options' ), 10, 2 );
		add_action( 'mainwp_sucuriscan_sites', 'MainWP_Sucuri::render' );

		$useWPCron = ( get_option( 'mainwp_wp_cron' ) === false ) || ( get_option( 'mainwp_wp_cron' ) == 1 );

		$local_time = self::get_timestamp();
		if ( ( $sched = wp_next_scheduled( 'mainwp_sucuri_extension_cronsecurityscan_notification' ) ) == false ) {
			if ( $useWPCron ) {
				wp_schedule_event( $local_time, 'daily', 'mainwp_sucuri_extension_cronsecurityscan_notification' );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_sucuri_extension_cronsecurityscan_notification' );
			}
		}

	}

	/**
	 * Method screen_options()
	 *
	 * Create Screen Options button.
	 *
	 * @param mixed $input Screen options button HTML.
	 *
	 * @return mixed Screen sptions button.
	 */
	public function screen_options( $input ) {
		if ( isset( $_GET['page'] ) && 'Extensions-Mainwp-Sucuri-Extension' == $_GET['page'] ) {
			if ( ! isset( $_GET['tab'] ) || 'dashboard' == $_GET['tab'] ) {
						$input .= '<a class="ui button basic icon" onclick="mainwp_sucuri_sites_screen_options(); return false;" data-inverted="" data-position="bottom right" href="#" target="_blank" data-tooltip="' . esc_html__( 'Screen Options', 'mainwp' ) . '">
					<i class="cog icon"></i>
				</a>';
			}
		}
		return $input;
	}

	/**
	 * Render Cron Job Info html.
	 */
	public function cron_job_info() {
		$lastEvent   = $nextEvent = '';
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$lastEvent   = wp_next_scheduled( 'mainwp_sucuri_extension_cronsecurityscan_notification' ) - 60 * 60 * 24;
		$nextEvent   = wp_next_scheduled( 'mainwp_sucuri_extension_cronsecurityscan_notification' )
		?>
		<tr>
			<td><?php echo __( 'Send Sucuri scan notifications', 'mainwp-sucuri-extension' ); ?></td>
			<td><?php echo 'mainwp_sucuri_extension_cronsecurityscan_notification'; ?></td>
			<td><?php echo __( 'Once daily', 'mainwp-sucuri-extension' ); ?></td>
			<td><?php echo date( $date_format, $lastEvent ) . ' ' . date( $time_format, $lastEvent ); ?></td>
			<td><?php echo date( $date_format, $nextEvent ) . ' ' . date( $time_format, $nextEvent ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Cron Security scan notification.
	 *
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 * @uses MainWP_Sucuri_DB::get_instance()::get_sucuri_by()
	 * @uses MainWP_Sucuri_DB::get_instance()::update_sucuri()
	 * @uses MainWP_Sucuri::check_remind()
	 * @uses MainWP_Sucuri::send_remind_email()
	 */
	static function cronsecurityscan_notification() {

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$websites = apply_filters( 'mainwp_getsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), null );
		if ( is_array( $websites ) && count( $websites ) > 0 ) {
			foreach ( $websites as $site ) {
				if ( $sucuri = MainWP_Sucuri_DB::get_instance()->get_sucuri_by( 'site_id', $site['id'] ) ) {
					if ( self::check_remind( $sucuri ) ) {
						self::send_remind_email( $site, $sucuri ); }
				} else {
					$sucuri = array(
						'site_id' => $site['id'],
						'remind'  => 'never',
					); // insert
					MainWP_Sucuri_DB::get_instance()->update_sucuri( $sucuri );
				}
			}
		}
	}

	/**
	 * Sucuri Scan reminder.
	 *
	 * @param array $sucuri Sucuri cron system security notifications array.
	 * @return bool TRUE|FALSE Whether or not Child Site needs to be scaned.
	 */
	static function check_remind( $sucuri ) {
		$lasttime    = $sucuri->lastscan;
		$remind      = $sucuri->remind;
		$last_remind = $sucuri->lastremind;
		$send_email  = false;
		$local_time  = self::get_timestamp();
		switch ( $remind ) {
			case 'never':
				return false;
				break;
			case 'day':
				if ( $local_time > $lasttime + DAY_IN_SECONDS ) {
					if ( 0 == $last_remind || $local_time > $last_remind + DAY_IN_SECONDS ) {
						return true;
					}
				}
				break;
			case 'week':
				if ( $local_time > $lasttime + WEEK_IN_SECONDS ) {
					if ( 0 == $last_remind || $local_time > $last_remind + WEEK_IN_SECONDS ) {
						return true;
					}
				}
				break;
			case 'month':
				if ( $local_time > strtotime( '+1 month', $lasttime ) ) {
					if ( 0 == $last_remind || $local_time > strtotime( '+1 month', $last_remind ) ) {
						return true;
					}
				}
				break;
		}
		return false;
	}

	/**
	 * Send scan reminder email.
	 *
	 * @param array $site Child site array.
	 * @param array $sucuri Sucuri cron system security notifications array.
	 * @return bool Return TRUE when email has been sent or FALSE on failure.
	 *
	 * @uses MainWP_Sucuri_DB::get_instance::update_sucuri()
	 */
	static function send_remind_email( $site, $sucuri ) {

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$lastscan = $sucuri->lastscan;
		$remind   = $sucuri->remind;
		$email    = apply_filters( 'mainwp_getnotificationemail', false );

		$local_time = self::get_timestamp();

		if ( ! empty( $site ) && ! empty( $email ) ) {
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			$last_time   = 'N/A';
			$day_number  = 0;
			if ( ! empty( $lastscan ) ) {
				$last_time  = date( $date_format, $lastscan ) . ' ' . date( $time_format, $lastscan );
				$day_number = ceil( ( $local_time - $lastscan ) / ( 24 * 60 * 60 ) );
				$day_number = $day_number . ( ( $day_number > 1 ) ? ' days' : ' day' );
			} else {
				$lastremind = $sucuri->lastremind;
				if ( $lastremind > 0 ) {
					$day_number = ceil( ( $local_time - $lastremind ) / ( 24 * 60 * 60 ) );
					$day_number = $day_number . ( ( $day_number > 1 ) ? ' days' : ' day' );
				}
			}

			$mail  = '<p>MainWP Security Scan Notification</p>';
			$mail .= '<p>Your site: <a href="' . $site['url'] . '">' . $site['url'] . '</a> has not been Scanned over ' . ( 0 !== $day_number ? $day_number : '1 ' . $remind ) . '</p>';
			$mail .= '<p>Last time of Scan: ' . $last_time . '</p>';
			$mail .= '<p>Please perform a security scan from your MainWP Dashboard.</p>';
			if ( wp_mail( $email, 'MainWP - Security Scan Notification', $mail, array( 'From: "' . get_option( 'admin_email' ) . '" <' . get_option( 'admin_email' ) . '>', 'content-type: text/html' ) ) ) {
				$sucuri = array(
					'id'         => $sucuri->id,
					'lastremind' => $local_time,
				);
				MainWP_Sucuri_DB::get_instance()->update_sucuri( $sucuri );
				return true;
			}
		}
		return false;
	}

	/**
	 * Render MainWP Sucuri settings page.
	 *
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 * @uses MainWP_Sucuri::gen_select_sites()
	 * @uses MainWP_Sucuri_DB::get_instance::get_sucuri_by()
	 * @uses MainWP_Sucuri_DB::get_instance::get_report_by()
	 * @uses MainWP_Sucuri::$nonce_token
	 */
	public static function renderSettings() {
		$selected_group = -1;
		$groups         = array();
		if ( isset( $_GET['group_id'] ) ) {
			$selected_group = intval( $_GET['group_id'] );
			$groups         = array( $selected_group );
		}

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		if ( $groups ) {
			$websites = apply_filters( 'mainwp_getdbsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), array(), $groups );
		} else {
			$websites = apply_filters( 'mainwp_getsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key() );
		}

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		?>

		<div class="mainwp-actions-bar"><?php self::gen_select_sites( $selected_group ); ?></div>
		<div class="ui segment" id="mainwp-sucuri">
			<table class="ui unstackable table" id="mainwp-sucuri-sites" style="width: 100%">
				<thead>
					<tr>
						<th id="site"><?php esc_html_e( 'Site', 'mainwp-sucuri-extension' ); ?></th>
						<th id="sign-in" class="collapsing no-sort"><?php esc_html_e( '', 'mainwp-sucuri-extension' ); ?></th>
						<th id="url"><?php esc_html_e( 'URL', 'mainwp-sucuri-extension' ); ?></th>
						<th id="last-scan"><?php esc_html_e( 'Last Scan', 'mainwp-sucuri-extension' ); ?></th>
						<th id="status"><?php esc_html_e( 'Status', 'mainwp-sucuri-extension' ); ?></th>
						<th id="webtrust"><?php esc_html_e( 'Webtrust', 'mainwp-sucuri-extension' ); ?></th>
						<th id="reports-settings" class="collapsing no-sort"><?php esc_html_e( '', 'mainwp-sucuri-extension' ); ?></th>
						<th id="scan-now" class="collapsing no-sort"><?php esc_html_e( '', 'mainwp-sucuri-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( $websites ) :
						foreach ( $websites as $website ) {
							$website = (object) $website;
							$sucuri  = MainWP_Sucuri_DB::get_instance()->get_sucuri_by( 'site_id', $website->id );
							?>
						<tr class="mainwp-site mainwp-site-<?php esc_attr_e( $website->id ); ?>">
							<td class="mainwp-site-cell"><a href="<?php echo 'admin.php?page=managesites&dashboard=' . $website->id; ?>" data-tooltip="<?php esc_attr_e( 'Open the child site overview', 'mainwp' ); ?>" data-inverted=""><?php echo stripslashes( $website->name ); ?></a></td>
							<td>
							<?php if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'dashboard', 'access_wpadmin_on_child_sites' ) ) : ?>
									<i class="sign in icon"></i>
							<?php else : ?>
									<a href="<?php echo 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website->id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" data-tooltip="<?php esc_attr_e( 'Jump to the child site WP Admin', 'mainwp' ); ?>" data-inverted="" class="open_newwindow_wpadmin" target="_blank"><i class="sign in icon"></i></a>
							<?php endif; ?>
							</td>
							<td class="mainwp-url-cell"><a href="<?php echo $website->url; ?>" target="_blank"><?php echo $website->url; ?></td>
							<td>
								<?php
								if ( $sucuri && $sucuri->lastscan != 0 ) {
									echo date( $date_format, $sucuri->lastscan ) . ' ' . date( $time_format, $sucuri->lastscan );
								} else {
									echo __( 'Not scanned yet.', 'mainwp-sucuri-extension' );
								}
								?>
							</td>
							<?php
							$saved_reports = MainWP_Sucuri_DB::get_instance()->get_report_by( 'site_id', $website->id );
							$last_report   = end( $saved_reports );
							?>
							<td><?php echo ( isset( $last_report->MALWARE->WARN ) ? '<span class="ui red basic mini label">' . __( 'Issues Detected', 'mainwp-sucuri-extension' ) . '</span>' : '<span class="ui green basic mini label">' . __( 'No Malware Found', 'mainwp-sucuri-extension' ) . '</span>' ); ?></td>
							<td><?php echo ( isset( $last_report->BLACKLIST->WARN ) ? '<span class="ui red basic mini label">' . __( 'Issues Detected', 'mainwp-sucuri-extension' ) . '</span>' : '<span class="ui green basic mini label">' . __( 'Site is not Blacklisted', 'mainwp-sucuri-extension' ) . '</span>' ); ?></td>
							<td><a href="<?php echo 'admin.php?page=managesites&scanid=' . $website->id; ?>" class="ui mini button"><?php echo __( 'Reports & Settings', 'mainwp-sucuri-extension' ); ?></a></td>
							<td><a href="#" class="ui button mini green mainwp-sucuri-scan-site" nonce="<?php echo wp_create_nonce( self::$nonce_token . 'sucuri_scan' ); ?>" site_id="<?php echo $website->id; ?>"><?php esc_html_e( 'Scan Now', 'mainwp-sucuri-extension' ); ?></a></td>
						</tr>
						<?php } ?>
					<?php endif; ?>
				</tbody>
			</table>
		<?php
		$table_features = array(
			'searching'     => 'true',
			'paging'        => 'true',
			'info'          => 'true',
			'stateSave'     => 'true',
			'stateDuration' => '0',
			'scrollX'       => 'true',
			'colReorder'    => 'true',
			'lengthMenu'    => '[ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ]',
		);

		/**
		 * Filter: mainwp_sucuri_table_features
		 *
		 * Filters the Sucuri table features.
		 *
		 * @param array $table_features Table features array.
		 *
		 * @since 4.0.8
		 */
		$table_features = apply_filters( 'mainwp_sucuri_table_features', $table_features );
		?>
			<script type="text/javascript">
			var responsive = true;
			if( jQuery( window ).width() > 1140 ) {
				responsive = false;
			}
			jQuery( document ).ready( function () {
				$sucuri_sites_table = jQuery( '#mainwp-sucuri-sites' ).DataTable( {
					"responsive": responsive,
					<?php
					foreach ( $table_features as $feature => $value ) {
							echo "'" . $feature . "' : " . $value . ',';
					};
					?>
				} );

				_init_sucuri_sites_screen = function() {
					jQuery( '#mainwp-sucuri-sites-screen-options-modal input[type=checkbox][id^="mainwp_show_column_"]' ).each( function() {
						var check_id = jQuery( this ).attr( 'id' );
						col_id = check_id.replace( "mainwp_show_column_", "" );
						try {
							$sucuri_sites_table.column( '#' + col_id ).visible( jQuery(this).is( ':checked' ) );
						} catch(err) {
							// to fix js error.
						}
					} );
				};
				_init_sucuri_sites_screen();

				mainwp_sucuri_sites_screen_options = function () {
					jQuery( '#mainwp-sucuri-sites-screen-options-modal' ).modal( {
						allowMultiple: true,
						onHide: function () {
						}
					} ).modal( 'show' );

					jQuery( '#sucuri-sites-screen-options-form' ).submit( function() {
						if ( jQuery('input[name=reset_sucurisites_columns_order]').attr('value') == 1 ) {
							$sucuri_sites_table.colReorder.reset();
						}
						jQuery( '#mainwp-sites-sites-screen-options-modal' ).modal( 'hide' );
					} );
					return false;
				};
			} );
			</script>
			<div class="ui large modal" id="mainwp-sucuri-scan-modal">
				<div class="header"><?php esc_html_e( 'Sucuri Scan', 'mainwp-sucuri-extension' ); ?></div>
			  <div class="scrolling content">
					<div class="ui inverted dimmer">
					<div class="ui text loader"><?php esc_html_e( 'Scanning...', 'mainwp-sucuri-extension' ); ?></div>
				  </div>
				</div>
				<div class="actions">
				<div class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp-sucuri-extension' ); ?></div>
			  </div>
			</div>
			<?php self::render_screen_options(); ?>
		</div>
		  <?php
	}

	/**
	 * Get columns.
	 *
	 * @return array Array of column names.
	 */
	public static function get_columns() {
		return array(
			'site'             => __( 'Site', 'mainwp-sucuri-extension' ),
			'sign-in'          => '<i class="sign in icon"></i>',
			'url'              => __( 'URL', 'mainwp-sucuri-extension' ),
			'last-scan'        => __( 'Last Scan', 'mainwp-sucuri-extension' ),
			'status'           => __( 'Status', 'mainwp-sucuri-extension' ),
			'webtrust'         => __( 'Webtrust', 'mainwp-sucuri-extension' ),
			'reports-settings' => __( 'Reports & Settings', 'mainwp-sucuri-extension' ),
			'scan-now'         => __( 'Scan Now', 'mainwp-sucuri-extension' ),
		);
	}

	/**
	 * Render screen options.
	 *
	 * @return array Array of default column names.
	 */
	public static function render_screen_options() {

		$columns = self::get_columns();

		$show_cols = get_user_option( 'mainwp_settings_show_sucuri_sites_columns' );

		if ( ! is_array( $show_cols ) ) {
			$show_cols = array();
		}

		?>
		<div class="ui modal" id="mainwp-sucuri-sites-screen-options-modal">
			<div class="header"><?php esc_html_e( 'Screen Options', 'mainwp' ); ?></div>
			<div class="scrolling content ui form">
				<form method="POST" action="" id="sucuri-sites-screen-options-form" name="sucuri_sites_screen_options_form">
					<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
					<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'SucuriSitesScrOptions' ); ?>" />
						<div class="ui grid field">
							<label class="six wide column"><?php esc_html_e( 'Show columns', 'mainwp' ); ?></label>
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
							<span data-tooltip="<?php esc_attr_e( 'Returns this page to the state it was in when installed. The feature also restores any column you have moved through the drag and drop feature on the page.', 'mainwp' ); ?>" data-inverted="" data-position="top center"><input type="button" class="ui button" name="reset" id="reset-sucurisites-settings" value="<?php esc_attr_e( 'Reset Page', 'mainwp' ); ?>" /></span>
						</div>
						<div class="ui right aligned column">
					<input type="submit" class="ui green button" name="btnSubmit" id="submit-sucurisites-settings" value="<?php esc_attr_e( 'Save Settings', 'mainwp' ); ?>" />
					<div class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp' ); ?></div>
				</div>
					</div>
				</div>
				<input type="hidden" name="reset_sucurisites_columns_order" value="0">
			</form>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function () {
				jQuery('#reset-sucurisites-settings').on( 'click', function () {
					mainwp_confirm(__( 'Are you sure.' ), function(){
						jQuery('.mainwp_hide_wpmenu_checkboxes input[id^="mainwp_show_column_"]').prop( 'checked', false );
						//default columns
						var cols = ['site','url','sign-in','last-scan','status','webtrust','reports-settings', 'scan-now'];
						jQuery.each( cols, function ( index, value ) {
							jQuery('.mainwp_hide_wpmenu_checkboxes input[id="mainwp_show_column_' + value + '"]').prop( 'checked', true );
						} );
						jQuery('input[name=reset_sucurisites_columns_order]').attr('value',1);
						jQuery('#submit-sucurisites-settings').click();
					}, false, false, true );
					return false;
				} );
			} );
		</script>
		<?php
	}

	/**
	 * Method handle_sites_screen_settings()
	 *
	 * Handle sites screen settings
	 */
	public function handle_sites_screen_settings() {
		if ( isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'SucuriSitesScrOptions' ) ) {
			$show_cols = array();
			foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST ) ) as $key => $val ) {
				if ( false !== strpos( $key, 'mainwp_show_column_' ) ) {
					$col               = str_replace( 'mainwp_show_column_', '', $key );
					$show_cols[ $col ] = 1;
				}
			}
			if ( isset( $_POST['show_columns_name'] ) ) {
				foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST['show_columns_name'] ) ) as $col ) {
					if ( ! isset( $show_cols[ $col ] ) ) {
						$show_cols[ $col ] = 0; // uncheck, hide columns.
					}
				}
			}
			$user = wp_get_current_user();
			if ( $user ) {
				update_user_option( $user->ID, 'mainwp_settings_show_sucuri_sites_columns', $show_cols, true );
			}
		}
	}

	/**
	 * Get Child Site results.
	 *
	 * @param array $websites       Child sites array.
	 * @param int   $selected_group Selected Child Site group.
	 * @return array $sites_results  Child Site results array.
	 *
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 */
	public static function get_websites_result( $websites, $selected_group = 0 ) {
		$sites_results = array();

		$groups = array();
		if ( ! empty( $selected_group ) ) {
			$groups = array( $selected_group );
		}

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), array(), $groups );

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				$sites_results = $websites;
			} else {

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$site_ids       = array();

				foreach ( $group_websites as $site ) {
					$site_ids[] = $site->id;
				}

				foreach ( $websites as $site ) {
					if ( $site && in_array( $site['id'], $site_ids ) ) {
						$sites_results[] = $site;
					}
				}
			}
		}
		return $sites_results;
	}

	/**
	 * Generate Select Sites dropdown.
	 *
	 * @param int $selected_group Selected Child Sites group.
	 * @return string Return Select Child Sites group dropdown.
	 *
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 */
	public static function gen_select_sites( $selected_group ) {

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$groups = apply_filters( 'mainwp_getgroups', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), null );

		?>
		<div class="ui stackable grid">
			<div class="ui eight wide column">

			</div>
			<div class="ui eight wide right aligned column mini form">
			<form method="post" action="admin.php?page=Extensions-Mainwp-Sucuri-Extension">
					<?php _e( 'Filter sites: ', 'mainwp-sucuri-extension' ); ?>
			  <div name="mainwp_sucuri_groups_select" id="mainwp_sucuri_groups_select" class="ui selection dropdown not-auto-init">
				<div class="text"><?php _e( 'Select group', 'mainwp-sucuri-extension' ); ?></div>
						<input type="hidden" name="mainwp_sucuri_groups_select" value="<?php echo intval( $selected_group ); ?>">
						<i class="dropdown icon"></i>
						<div class="menu">
							<div class="item" data-value="-1" ><?php esc_html_e( 'All groups', 'mainwp' ); ?></div>
							<?php
							if ( is_array( $groups ) && count( $groups ) > 0 ) {
								foreach ( $groups as $group ) {
									$_select = '';
									if ( $selected_group == $group['id'] ) {
										$_select = 'selected ';
									}
									echo '<div class="item" data-value="' . $group['id'] . '" ' . $_select . '>' . $group['name'] . '</div>';
								}
							}
							?>
						</div>
			  </div>
			</form>
			</div>
		</div>
		<?php
		return;
	}

	/**
	 * Render Securi Scan list.
	 *
	 * @param array $website Child Site array.
	 *
	 * @uses MainWP_Sucuri_DB::get_instance::get_sucuri_by()
	 * @uses MainWP_Sucuri_DB::get_instance::get_report_by()
	 */
	public static function render( $website = null ) {

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		if ( ! $website ) {
			return;
		}

		$sucuri = MainWP_Sucuri_DB::get_instance()->get_sucuri_by( 'site_id', $website->id );
		if ( is_object( $sucuri ) ) {
			$remind    = $sucuri->remind;
			$sucuri_id = $sucuri->id;
		} else {
			$remind    = 'never';
			$sucuri_id = 0;
		}

		$apisslverify = get_option( 'mainwp_security_sslVerifyCertificate' );

		if ( defined( 'OPENSSL_VERSION_NUMBER' ) && ( OPENSSL_VERSION_NUMBER <= 0x009080bf ) && ( false === $apisslverify ) ) {
			$apisslverify = 0;
			update_option( 'mainwp_security_sslVerifyCertificate', $apisslverify );
		}

		$_selected_1 = ( ( false === $apisslverify ) || ( 1 == $apisslverify ) ) ? 'selected' : '';
		$_selected_0 = empty( $_selected_1 ) ? 'selected' : '';
		?>

		<h3 class="ui dividing header"><?php esc_html_e( 'Sucuri malware and security scanner', 'mainwp-sucuri-extension' ); ?></h3>

		<input type="hidden" name="mainwp_sucuri_site_id" value="<?php echo $website->id; ?>"/>
		<input type="hidden" name="mainwp_sucuri_id" value="<?php echo $sucuri_id; ?>"/>
		<input type="hidden" name="mainwp_sucuri_scan_nonce" value="<?php echo wp_create_nonce( self::$nonce_token . 'sucuri_scan' ); ?>"/>
		<input type="hidden" name="mainwp_sucuri_delete_report_nonce" value="<?php echo wp_create_nonce( self::$nonce_token . 'delete_report' ); ?>"/>
		<input type="hidden" name="mainwp_sucuri_show_report_nonce" value="<?php echo wp_create_nonce( self::$nonce_token . 'show_report' ); ?>"/>
		<input type="hidden" name="mainwp_sucuri_change_remind_nonce" value="<?php echo wp_create_nonce( self::$nonce_token . 'change_remind' ); ?>"/>

		<div class="ui message" style="display:none" id="mainwp-sucuri-message-zone"></div>

		<div class="ui secondary green padded segment">
			<div class="ui stackable two column grid">
				<div class="column">

					<div class="ui stackable grid">
						<div class="ten wide column"><div class="ui fluid input"><input type="text" disabled="disabled" value="<?php echo $website->url; ?>"></div></div>
						<div class="four wide column">
							<select name="mainwp_security_sslVerifyCertificate" id="mainwp_sucuri_verify_certificate" class="ui dropdown">
								<option value="0" <?php echo $_selected_0; ?> ><?php _e( 'Don\'t Verify SSL Certificate', 'mainwp-sucuri-extension' ); ?></option>
								<option value="1" <?php echo $_selected_1; ?> ><?php _e( 'Verify SSL Certificate', 'mainwp-sucuri-extension' ); ?></option>
						  </select>
						</div>
						<div class="two wide column"><a class="ui green button" id="mainwp-sucuri-run-scan"><?php esc_html_e( 'Scan Website', 'mainwp-sucuri-extension' ); ?></a></div>
					</div>
				</div>

				<div class="right aligned column">
					<?php esc_html_e( 'Remind me if i don\'t scan my child site for', 'mainwp-sucuri-extension' ); ?>
					<select name="mainwp_sucuri_remind_scan" id="mainwp_sucuri_remind_scan" class="ui dropdown">
						<option value="never" <?php echo ( empty( $remind ) || 'never' === $remind ) ? 'selected' : ''; ?>><?php esc_html_e( 'Never', 'mainwp-sucuri-extension' ); ?></option>
						<option value="day" <?php echo ( 'day' === $remind ) ? 'selected' : ''; ?>><?php esc_html_e( '1 Day', 'mainwp-sucuri-extension' ); ?></option>
						<option value="week" <?php echo ( 'week' === $remind ) ? 'selected' : ''; ?>><?php esc_html_e( '1 Week', 'mainwp-sucuri-extension' ); ?></option>
						<option value="month" <?php echo ( 'month' === $remind ) ? 'selected' : ''; ?>><?php esc_html_e( '1 Month', 'mainwp-sucuri-extension' ); ?></option>
					</select>
				</div>
			</div>
		</div>

		<div id="mainwp-sucuri-scan-modal" class="ui large modal">
			<div class="header"><?php esc_html_e( 'Sucuri Report', 'mainwp-sucuri-extension' ); ?></div>
			<div class="scrolling content" id="mainwp-sucuri-security-scan-result">
				<div class="ui inverted dimmer">
					<div class="ui text loader"><?php esc_html_e( 'Scanning...', 'mainwp-sucuri-extension' ); ?></div>
				</div>
			</div>
			<div class="actions">
				<a href="#" class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp-sucuri-extension' ); ?></a>
			</div>
		</div>

		<?php $saved_reports = MainWP_Sucuri_DB::get_instance()->get_report_by( 'site_id', $website->id ); ?>

		<h3 class="ui dividing header"><?php esc_html_e( 'Saved Sucuri Reports', 'mainwp-sucuri-extension' ); ?></h3>
		<div class="ui segment">
			<div class="ui selection divided list" id="mainwp-sucuri-scan-list">
			<?php
			if ( is_array( $saved_reports ) && count( $saved_reports ) > 0 ) {
				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );
				foreach ( $saved_reports as $report ) {
					?>
					<div class="item">
						<div class="ui stackable grid">
							<div class="two column row">
								<div class="middle aligned column">
								<?php echo date( $date_format, $report->timescan ) . ' - ' . date( $time_format, $report->timescan ); ?>
								</div>
								<div class="right aligned middle aligned column">
									<a href="#" class="ui green mini button mainwp-sucuri-saved-report-show" report-id="<?php echo $report->id; ?>"><?php _e( 'Show' ); ?></a>
									<a href="#" class="ui mini button mainwp-sucuri-saved-report-delete" report-id="<?php echo $report->id; ?>"><?php _e( 'Delete' ); ?></a>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
			} else {
				echo '<div class="item">' . __( 'No saved reports. Click Scan Website button to scan the website.', 'mainwp-sucuri-extension' ) . '</div>';
			}
			?>
			</div>
		</div>

		<?php
	}

	/**
	 * Get link.
	 *
	 * @param string $str url to trim.
	 * @return string $str Return link html.
	 */
	public static function get_link( $str ) {
		$str = trim( $str );
		if ( preg_match( '/^https?\:\/\/.*$/i', $str ) ) {
			return '<a href="' . $str . '" target="_blank">' . $str . '</a>';
		} else {
			return $str;
		}
	}

	/**
	 * Ajax update scan reminder.
	 *
	 * @return string Return SUCCESS|FAIL.
	 *
	 * @uses MainWP_Sucuri::ajax_check_permissions()
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 * @uses MainWP_Sucuri_DB::get_instance::update_sucuri_by_site_id()
	 */
	function ajax_change_remind() {

		$this->ajax_check_permissions( 'change_remind' );

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$website_id = $_POST['siteId'];

		$website = apply_filters( 'mainwp_getsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), $website_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			die( 'FAIL' );
		}

		$sucuri = array(
			'remind' => $_POST['remind'],
		);

		// if ( MainWP_Sucuri_DB::get_instance()->update_sucuri( $sucuri ) ) {
		// if ( MainWP_Sucuri_DB::get_instance()->update_sucuri_by_site_url( $website['url'], $sucuri ) ) { // insert or update
		// die( 'SUCCESS' );
		// }

		if ( MainWP_Sucuri_DB::get_instance()->update_sucuri_by_site_id( $website['id'], $sucuri ) ) { // insert or update
			die( 'SUCCESS' );
		}
			die( 'FAIL' );
	}

	/**
	 * Ajax save verify SSL setting.
	 */
	public static function ajax_save_ssl_verify() {
		update_option( 'mainwp_security_sslVerifyCertificate', intval( $_POST['security_sslverify'] ) );
		die( json_encode( array( 'saved' => 1 ) ) );
	}

	/**
	 * Delete Sucuri scan report.
	 *
	 * @return string SUCCESS|FAIL.
	 *
	 * @uses MainWP_Sucuri::ajax_check_permissions()
	 * @uses MainWP_Sucuri_DB::get_instance::remove_report_by()
	 */
	function delete_report() {

		$this->ajax_check_permissions( 'delete_report' );

		$report_id = intval( $_POST['reportId'] );
		if ( empty( $report_id ) ) {
			die( 'FAIL' );
		}
		if ( MainWP_Sucuri_DB::get_instance()->remove_report_by( 'id', $report_id ) ) {
			die( 'SUCCESS' );
		}
		die( 'FAIL' );
	}

	/**
	 * Show Sucuri scan report.
	 *
	 * @uses MainWP_Sucuri::ajax_check_permissions()
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 * @uses MainWP_Sucuri_DB::get_instance::get_report_by()
	 * @uses MainWP_Sucuri::display_report()
	 */
	function show_report() {

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$this->ajax_check_permissions( 'show_report' );

		$report_id  = intval( $_POST['reportId'] );
		$website_id = intval( $_POST['siteId'] );

		if ( empty( $report_id ) || empty( $website_id ) ) {
			die( 'FAIL' ); }

		$website = apply_filters( 'mainwp_getsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), $website_id );

		if ( $website && is_array( $website ) ) {
			$mainWPSucuriExtensionActivator->get_child_file();
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			die( 'FAIL' );
		}

		if ( $report = MainWP_Sucuri_DB::get_instance()->get_report_by( 'id', $report_id ) ) {
			// to compatible with old data
			if ( is_serialized( $report->data ) ) {
				$data = unserialize( $report->data );
			} else {
				$data = json_decode( $report->data, true );
			}
			echo $this->display_report( $website, $data );
			die( '' );
		}
		die( 'FAIL' );
	}

	/**
	 * Perform Ajax Security scan.
	 *
	 * @uses MainWP_Sucuri::ajax_check_permissions()
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 * @uses MainWP_Sucuri_DB::get_instance::update_sucuri_by_site_id()
	 */
	public function ajax_sucuri_scan() {

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$this->ajax_check_permissions( 'sucuri_scan' );

		$website_id = $_POST['siteId'];

		$website = apply_filters( 'mainwp_getsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), $website_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			die( json_encode( array() ) );
		}

		$time_scan = self::get_timestamp();
		$sucuri    = array(
			'lastscan' => $time_scan,
		);

		MainWP_Sucuri_DB::get_instance()->update_sucuri_by_site_id( $website['id'], $sucuri ); // insert or update

		$apisslverify = get_option( 'mainwp_security_sslVerifyCertificate', true );
		$scan_url     = 'https://sitecheck.sucuri.net/scanner/?serialized&clear&mainwp&scan=' . $website['url'];
		$results      = wp_remote_get(
			$scan_url,
			array(
				'timeout'   => 180,
				'sslverify' => $apisslverify,
			)
		);
		$scan_result  = $scan_status = '';
		$list_html    = '';
		if ( is_wp_error( $results ) ) {
			if ( 1 == $apisslverify ) {
				update_option( 'mainwp_security_sslVerifyCertificate', 0 );
				die( json_encode( array( 'result' => 'retry_action' ) ) );
			}

			ob_start();
			$scan_status = 'failed';
			$scan_result = __( 'Error retrieving the scan report', 'mainwp-sucuri-extension' );
			echo '<div class="ui red message">' . $scan_result . '</div>';

		} elseif ( preg_match( '/^ERROR:/', $results['body'] ) ) {

			ob_start();
			$scan_status = 'failed';
			$scan_result = $results['body'];
			echo '<div class="ui red message">' . $scan_result . '</div>';
		} else {
			$report = array(
				'data'     => $results['body'],
				'site_id'  => $website['id'],
				'timescan' => $time_scan,
			);

			ob_start();
			$saved_report = MainWP_Sucuri_DB::get_instance()->save_report( $report );
			$data         = json_decode( $results['body'], true );
			if ( ! is_array( $data ) ) {
				$scan_status = 'failed';
				$code        = '';
				if ( is_array( $results ) && isset( $results['response'] ) ) {
					$code = ': code ' . $results['response']['code'];
				}
				echo '<div class="ui red message">' . __( 'Error', 'mainwp-sucuri-extension' ) . $code . '</div>';
			} else {
				$scan_result = $data;
				$scan_status = 'success';
				$this->display_report( $website, $data );

				$date_format = get_option( 'date_format' );
				$time_format = get_option( 'time_format' );

				$list_html = '<div class="item">
						<div class="ui grid">
							<div class="two column row">
								<div class="middle aligned column">' .
									date( $date_format, $time_scan ) . ' - ' . date( $time_format, $time_scan ) .
								'</div>
								<div class="right aligned middle aligned column">
									<a href="#" class="ui green mini button mainwp-sucuri-saved-report-show" report-id="' . $saved_report->id . '">' . __( 'Show' ) . '</a>
									<a href="#" class="ui mini button mainwp-sucuri-saved-report-delete" report-id="' . $saved_report->id . '">' . __( 'Delete' ) . '</a>
								</div>
							</div>
						</div>
					</div>';
			}
		}

		$html = ob_get_clean();

		// do_action( 'mainwp_sucuri_scan_done', $website_id, $scan_status, $scan_result, $time_scan );
		do_action( 'mainwp_sucuri_scan_finished', $website_id, $scan_status, $scan_result, $time_scan );

		die(
			json_encode(
				array(
					'result' => $html,
					'item'   => $list_html,
				)
			)
		);
	}

	/**
	 * Perform Ajax Sucuri Site Scan.
	 *
	 * @uses MainWP_Sucuri::ajax_check_permissions()
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 * @uses MainWP_Sucuri_DB::get_instance::update_sucuri_by_site_id()
	 * @uses MainWP_Sucuri_DB::get_instance::save_report()
	 */
	public function ajax_sucuri_scan_site() {

		/** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		$this->ajax_check_permissions( 'sucuri_scan' );

		$website_id = $_POST['siteId'];

		$website = apply_filters( 'mainwp_getsites', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), $website_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			die();
		}

		$time_scan = self::get_timestamp();

		$sucuri = array(
			'lastscan' => $time_scan,
		);

		// MainWP_Sucuri_DB::get_instance()->update_sucuri_by_site_url( $website['url'], $sucuri ); // insert or update
		MainWP_Sucuri_DB::get_instance()->update_sucuri_by_site_id( $website['id'], $sucuri ); // insert or update

		$apisslverify = get_option( 'mainwp_security_sslVerifyCertificate', true );
		$scan_url     = 'https://sitecheck.sucuri.net/scanner/?serialized&clear&mainwp&scan=' . $website['url'];
		$results      = wp_remote_get(
			$scan_url,
			array(
				'timeout'   => 180,
				'sslverify' => $apisslverify,
			)
		);

		$scan_result = $scan_status = '';

		if ( is_wp_error( $results ) ) {
			if ( 1 == $apisslverify ) {
				update_option( 'mainwp_security_sslVerifyCertificate', 0 );
				die( 'retry_action' );
			} else {
				$scan_status = 'failed';
				$scan_result = __( 'Error retrieving the scan report', 'mainwp-sucuri-extension' );
				echo '<div class="ui red message">' . $scan_result . '</div>';
			}
		} elseif ( preg_match( '/^ERROR:/', $results['body'] ) ) {
			$scan_status = 'failed';
			$scan_result = $results['body'];
			echo '<div class="ui red message">' . $scan_result . '</div>';
		} else {
			$new = array(
				'data'     => $results['body'],
				'site_id'  => $website['id'],
				'timescan' => $time_scan,
			);
			MainWP_Sucuri_DB::get_instance()->save_report( $new );
			$data = json_decode( $results['body'], true );

			if ( ! is_array( $data ) ) {
				$scan_status = 'failed';
				$code        = '';
				if ( is_array( $results ) && isset( $results['response'] ) ) {
					$code = ': code ' . $results['response']['code'];
				}
				echo '<div class="ui red message">' . __( 'Error ', 'mainwp-sucuri-extension' ) . $code . '</div>';
			} else {
				$scan_result = $data;
				$scan_status = 'success';
				$this->display_report( $website, $data );
			}
		}
		// do_action( 'mainwp_sucuri_scan_done', $website_id, $scan_status, $scan_result, $time_scan );
		do_action( 'mainwp_sucuri_scan_finished', $website_id, $scan_status, $scan_result, $time_scan );
		die();
	}


	/**
	 * Check if Sucuri scan in done.
	 *
	 * @param int    $website_id Child Site ID.
	 * @param string $scan_status Current scan status.
	 * @param array  $data Blacklist & Malware data.
	 * @param bool   $time_scan Check if time to scan. TRUE|FALSE.
	 *
	 * @uses $mainWPSucuriExtensionActivator::get_child_file()
	 * @uses $mainWPSucuriExtensionActivator::get_child_key()
	 */
	function sucuri_scan_done( $website_id, $scan_status, $data, $time_scan = false ) {
		$scan_result = array();

		if ( is_array( $data ) ) {
			$blacklisted    = isset( $data['BLACKLIST']['WARN'] ) ? true : false;
			$malware_exists = isset( $data['MALWARE']['WARN'] ) ? true : false;

			$status = array();
			if ( $blacklisted ) {
				$status[] = __( 'Site Blacklisted', 'mainwp-reports-extension' ); }
			if ( $malware_exists ) {
				$status[] = __( 'Site With Warnings', 'mainwp-reports-extension' ); }

			$scan_result['status']   = count( $status ) > 0 ? implode( ', ', $status ) : __( 'Verified Clear', 'mainwp-reports-extension' );
			$scan_result['webtrust'] = $blacklisted ? __( 'Site Blacklisted', 'mainwp-reports-extension' ) : __( 'Trusted', 'mainwp-reports-extension' );
		}

		$scan_data = array(
			'blacklisted'    => $blacklisted,
			'malware_exists' => $malware_exists,
		);

		// save results to child site stream
		$post_data = array(
			'mwp_action'  => 'save_sucuri_stream',
			'result'      => base64_encode( serialize( $scan_result ) ),
			'scan_status' => $scan_status,
			'scan_data'   => base64_encode( serialize( $scan_data ) ),
			'scan_time'   => $time_scan,
		);

		 /** @global object $mainWPSucuriExtensionActivator MainWP Sucuri Object. */
		global $mainWPSucuriExtensionActivator;

		apply_filters( 'mainwp_fetchurlauthed', $mainWPSucuriExtensionActivator->get_child_file(), $mainWPSucuriExtensionActivator->get_child_key(), $website_id, 'client_report', $post_data );
	}

	/**
	 * Get Sucuri scan data.
	 *
	 * @param strign $timescan Scan time.
	 * @return array Return Sucuri scan data.
	 *
	 * @uses MainWP_Sucuri_DB::get_instance::get_report_by()
	 */
	function sucuri_scan_data( $timescan ) {
		return MainWP_Sucuri_DB::get_instance()->get_report_by( 'timescan', $timescan );
	}

	/**
	 * Ajax check permissions.
	 *
	 * @param $action Action to check permissions for.
	 * @param bool                                   $json TRUE or json encoded error message on failure.
	 */
	public function ajax_check_permissions( $action, $json = false ) {
		if ( has_filter( 'mainwp_currentusercan' ) ) {
			if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-sucuri-extension' ) ) {
				$output = mainwp_do_not_have_permissions( 'MainWP Sucuri Extension ' . $action, ! $json );
				if ( $json ) {
					echo json_encode( array( 'error' => $output ) );
				}
				die();
			}
		}

		if ( ! isset( $_REQUEST['wp_nonce'] ) || ! wp_verify_nonce( $_REQUEST['wp_nonce'], self::$nonce_token . $action ) ) {
			echo $json ? json_encode( array( 'error' => 'Error: Wrong or expired request' ) ) : 'Error: Wrong or expired request';
			die();
		}
	}

	/**
	 * Display Sucuri Scan Report.
	 *
	 * @param array $website Child Site array.
	 * @param array $data Malware & Blacklist data array.
	 */
	function display_report( $website, $data ) {

		$blacklisted    = isset( $data['BLACKLIST']['WARN'] ) ? true : false;
		$malware_exists = isset( $data['MALWARE']['WARN'] ) ? true : false;
		$system_error   = isset( $data['SYSTEM']['ERROR'] ) ? true : false;

		$status = array();

		if ( $blacklisted ) {
			$status[] = 'Site Blacklisted'; }
		if ( $malware_exists ) {
			$status[] = 'Site With Warnings'; }
		?>

		<div class="ui stackable grid">
			<div class="eight wide column">
				<h3 class="ui header">
				  <i class="<?php echo count( $status ) > 0 ? 'circle times red' : 'circle check green'; ?> big icon"></i>
				  <div class="content">
					<?php echo count( $status ) > 0 ? __( 'Problems Detected', 'mainwp-sucuri-extension' ) : __( 'No Malware Found', 'mainwp-sucuri-extension' ); ?>
					<div class="sub header"><?php echo count( $status ) > 0 ? __( 'Immediate action is required', 'mainwp-sucuri-extension' ) : __( 'Our scanner didn\'t detect any malware', 'mainwp-sucuri-extension' ); ?></div>
				  </div>
				</h3>
			</div>
			<div class="eight wide column">
				<h3 class="ui header">
				  <i class="<?php echo $blacklisted ? 'circle times red' : 'circle check green'; ?> big icon"></i>
				  <div class="content">
					<?php echo $blacklisted ? __( 'Site is Blacklisted', 'mainwp-sucuri-extension' ) : __( 'Site is not Blacklisted', 'mainwp-sucuri-extension' ); ?>
					<div class="sub header">9 Blacklists checked</div>
				  </div>
				</h3>
			</div>
		</div>
		<div class="ui hidden divider"></div>
		<?php
		$scan_site = isset( $data['SCAN']['SITE'] ) ? htmlspecialchars( $data['SCAN']['SITE'][0] ) : '';
		$domain    = isset( $data['SCAN']['DOMAIN'] ) ? htmlspecialchars( $data['SCAN']['DOMAIN'][0] ) : '';
		$ip        = isset( $data['SCAN']['IP'] ) ? htmlspecialchars( $data['SCAN']['IP'][0] ) : '';
		$sys_noti  = isset( $data['SYSTEM']['NOTICE'] ) ? $data['SYSTEM']['NOTICE'] : '';
		?>
		<div class="ui secondary segment">
			<div class="ui stackable grid">
				<div class="two column row">
					<div class="middle aligned column">
						<h2 class="ui header">
						  <i class="WordPress icon"></i>
						  <div class="content">
							<?php esc_html_e( 'Scan Info', 'mainwp-sucuri-extension' ); ?>
							<div class="sub header"><?php echo $domain; ?></div>
						  </div>
						</h2>
					</div>
					<div class="column">
						<div class="ui list">
							<div class="item"><?php esc_html_e( 'IP Address: ', 'mainwp-sucuri-extension' ); ?><?php echo $ip; ?></div>
							<div class="item"><?php esc_html_e( 'Host: ', 'mainwp-sucuri-extension' ); ?><?php echo $scan_site; ?></div>
							<div class="item">
								<?php
								if ( $sys_noti ) {
									if ( ! is_array( $sys_noti ) ) {
										echo htmlspecialchars( $sys_noti );
									} else {
										foreach ( $sys_noti as $noti ) {
											echo htmlspecialchars( $noti ) . '<br />';
										}
									}
								}
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="ui stackable grid">
			<div class="eight wide column">
				<h3 class="ui header"><?php esc_html_e( 'Website Malware & Security', 'mainwp-sucuri-extension' ); ?></h3>
				<div class="ui list">
				<?php if ( ! $malware_exists && ! $system_error ) { ?>
					<div class="item">Blacklisted: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Malware: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Malicious javascript: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Malicious iframes: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Drive-By Downloads: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Anomaly detection: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">IE-only attacks: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Suspicious redirections: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Blackhat SEO Spam: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
					<div class="item">Spam: <span class="ui green mini label right floated"><?php esc_html_e( 'NO', 'mainwp-sucuri-extension' ); ?></span></div>
				<?php } elseif ( $malware_exists ) { ?>
					<?php foreach ( $data['MALWARE']['WARN'] as $malware ) { ?>
						<div class="item">
							<?php
							if ( ! is_array( $malware ) ) {
								echo htmlspecialchars( $malware );
							} else {
								$mwdetails = explode( "\n", htmlspecialchars( $malware[1] ) );
								$mwdetails = explode( 'Details:', substr( $mwdetails[0], 1 ) );
								echo htmlspecialchars( $malware[0] ) . "\n<br />";
								echo $mwdetails[0] . ' - <a href="' . trim( $mwdetails[1] ) . '">' . __( 'Details' ) . '</a>.';
							}
							?>
							<span class="ui green mini label right floated"><?php esc_html_e( 'Malware Found', 'mainwp-sucuri-extension' ); ?></span>
						</div>
					<?php } ?>
				<?php } elseif ( $system_error ) { ?>
					<?php foreach ( $data['SYSTEM']['ERROR'] as $error ) { ?>
						<?php
						if ( ! is_array( $error ) ) {
							echo htmlspecialchars( $error );
						} else {
							echo htmlspecialchars( $error[0] ) . "<br />\n";
						}
						?>
						<span class="ui green mini label right floated"><?php esc_html_e( 'System Error', 'mainwp-sucuri-extension' ); ?></span>
					<?php } ?>
				<?php } ?>
				</div>
			</div>
			<div class="eight wide column">
				<h3 class="ui header"><?php esc_html_e( 'Website Blacklist Status', 'mainwp-sucuri-extension' ); ?></h3>
				<div class="ui relaxed list">
					<?php
					foreach ( array(
						'INFO' => 'CLEAN',
						'WARN' => 'WARNING',
					) as $type => $group_title ) {
						if ( isset( $data['BLACKLIST'][ $type ] ) ) {
							foreach ( $data['BLACKLIST'][ $type ] as $blres ) {
								$report_site = htmlspecialchars( $blres[0] );
								$report_url  = htmlspecialchars( $blres[1] );
								$info        = "{$report_site} - <a href='{$report_url}' target='_blank'>" . __( 'Reference' ) . '</a>';
								if ( $type == 'INFO' ) {
									$icon = '<i class="circle check green icon"></i>';
								} else {
									$icon = '<i class="circle times red icon"></i>';
								}
								echo '<div class="item">' . $icon . '<div class="content">' . $info . '</div></div>';
							}
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Method get_timestamp()
	 *
	 * Get time stamp in gmt_offset.
	 *
	 * @param mixed $timestamp Time stamp to convert.
	 *
	 * @return string Time stamp in general mountain time offset.
	 */
	public static function get_timestamp( $timestamp = false ) {
		if ( false === $timestamp ) {
			$timestamp = time();
		}
		$gmtOffset = get_option( 'gmt_offset' );

		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}
}
