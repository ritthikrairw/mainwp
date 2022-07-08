<?php
/**
 * MainWP Advaned Uptime Monitor Settings
 *
 * Renders the extension pages.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Settings_Page
 *
 * Renders the extension pages.
 */
class MainWP_AUM_Settings_Page {

	// phpcs:disable WordPress.DB.RestrictedFunctions, WordPress.DB.PreparedSQL.NotPrepared -- unprepared SQL ok, accessing the database directly to custom database functions.

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_Settings_Page.
	 */
	private static $instance = null;

	/**
	 * Method __construct()
	 *
	 * Contructor.
	 */
	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Create public static instance.
	 *
	 * @static
	 * @return object Class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method: render_tabs()
	 *
	 * Renders tabs.
	 *
	 * @return void
	 */
	public function render_tabs() {

		$message          = isset( $_GET['message'] ) ? intval( $_GET['message'] ) : false;
		$selected_service = get_option( 'mainwp_aum_enabled_service', 'uptimerobot' );
		$api_existed      = self::check_api_existed( $selected_service );

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( 'monitors' == $_GET['tab'] ) {
				$current_tab = 'monitors';
			} elseif ( 'settings' == $_GET['tab'] ) {
				$current_tab = 'settings';
			} elseif ( 'recent-events' == $_GET['tab'] ) {
				$current_tab = 'recent-events';
			}
		} else {
			if ( ! $api_existed ) {
				$current_tab = 'settings';
			} else {
				$current_tab = 'monitors';
			}
		}

		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-advanced-uptime-monitor-menu">
			<a href="admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=monitors" class="item <?php echo ( 'monitors' == $current_tab ? 'active' : '' ); ?>"><i class="eye icon"></i> <?php _e( 'Monitors', 'mainwp-spinner' ); ?></a>
			<a href="admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=recent-events" class="item <?php echo ( 'recent-events' == $current_tab ? 'active' : '' ); ?>"><i class="history icon"></i> <?php _e( 'Recent Events', 'mainwp-spinner' ); ?></a>
			<a href="admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings" class="item <?php echo ( 'settings' == $current_tab ? 'active' : '' ); ?>" data-tab="mainwp-advanced-uptime-monitor-settings"><i class="cog icon"></i> <?php _e( 'Settings', 'mainwp-spinner' ); ?></a>
	  </div>

		<!-- Monitors -->
		<?php if ( 'monitors' == $current_tab || '' == $current_tab ) : ?>
		<div id="mainwp-advanced-uptime-monitor-monitors-tab">
			<?php
			if ( false == $api_existed ) {
				self::render_api_not_existed( $selected_service );
			} else {
				self::render_monitors_page();
			}
			?>
		</div>
	<?php endif; ?>

		<!-- Recent Events -->
		<?php if ( 'recent-events' == $current_tab ) : ?>
			<div class="ui segment" id="mainwp-advanced-uptime-monitor-recent-events">
				<?php
				if ( false == $api_existed ) {
					self::render_api_not_existed( $selected_service );
				} else {
					if ( 'uptimerobot' === $selected_service ) {
						MainWP_AUM_UptimeRobot_Controller::instance()->render_recent_events();
					} elseif ( 'site24x7' === $selected_service ) {
						MainWP_AUM_Site24x7_Controller::instance()->render_recent_events();
					} elseif ( 'nodeping' === $selected_service ) {
						MainWP_AUM_Nodeping_Controller::instance()->render_recent_events();
					} elseif ( 'betteruptime' === $selected_service ) {
						MainWP_AUM_BetterUptime_Controller::instance()->render_recent_events();
					}
				}
				?>
			</div>
	<?php endif; ?>

		<!-- Settings -->
		<?php if ( $current_tab == 'settings' ) : ?>
			<div class="ui segment" id="mainwp-advanced-uptime-monitor-settings">
					<?php
					$error = false;
					if ( ! empty( $error ) ) {
						?>
						<div class="ui red message"><i class="close icon"></i><?php echo esc_html( $error ); ?></div>
						<?php
					}
					$this->render_messages( $message );
					$this->render_uptime_settings();
					$dev_console_url = $this->get_dev_console_link();
					?>
					</div>
	<?php endif; ?>

		<input type="hidden" id="mainwp_aum_reload_monitors_nonce" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_reload_monitors' ); ?>"/>
							
		<div class="ui modal" id="mainwp-create-edit-monitor-modal">
			<div class="header"></div>
			<div class="scrolling content"></div>
			<div class="actions">
				<div class="ui cancel button"><?php _e( 'Close', 'advanced-uptime-monitor-extension' ); ?></div>
			</div>
		</div>
		<input type="hidden" name="wp_js_nonce" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_monitors_page' ); ?>">
		<?php
	}

	/**
	 * Method: render_message()
	 *
	 * Renders the success or failure message after form submsission.
	 *
	 * @param  string $message Message text.
	 *
	 * @return void
	 */
	public function render_messages( $message ) {
		if ( $message ) {
			if ( isset( $_POST['submit_btn'] ) ) {
				?>
				<div class="ui green message"><i class="close icon"></i><?php echo __( 'Settings saved successfully.', 'advanced-uptime-monitor-extension' ); ?></div>
				<?php
			} elseif ( 2 == $message ) {
				?>
				<div class="ui green message"><i class="close icon"></i><?php echo __( 'Authorized.', 'advanced-uptime-monitor-extension' ); ?></div>
				<?php
			} elseif ( 3 == $message ) {
				?>
				<div class="ui green message"><i class="close icon"></i><?php echo __( 'Access generated successfully.', 'advanced-uptime-monitor-extension' ); ?></div>
				<?php
			} elseif ( 4 == $message ) {
				?>
				<div class="ui green message"><i class="close icon"></i><?php echo __( 'Advanced Monitor Settings reload successfully.', 'advanced-uptime-monitor-extension' ); ?></div>
				<?php
			}
		}
	}

	/**
	 * Method: render_uptime_settings()
	 *
	 * Renders the extension settings page.
	 *
	 * @return void
	 */
	public function render_uptime_settings() {
		$api_key_aum                     = MainWP_AUM_UptimeRobot_API::instance()->get_api_key();
		$list_noti_contact               = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'list_notification_contact', array() );
		$default_notification_contact_id = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'uptime_default_notification_contact_id', 0 );
		$api_timezone                    = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'api_timezone', false );
		$selected_service                = get_option( 'mainwp_aum_enabled_service', 'uptimerobot' );

		$supported_services = array(
			'uptimerobot'  => __( 'Uptime Robot', 'advanced-uptime-monitor-extension' ),
			'site24x7'     => __( 'Site24x7', 'advanced-uptime-monitor-extension' ),
			'nodeping'     => __( 'NodePing', 'advanced-uptime-monitor-extension' ),
			'betteruptime' => __( 'Better Uptime', 'advanced-uptime-monitor-extension' ),
		);

		$site247_location      = MainWP_AUM_Site24x7_API::instance()->get_option( 'endpoint', 'us' );
		$site247_client_id     = MainWP_AUM_Site24x7_API::instance()->get_option( 'client_id' );
		$site247_client_secret = MainWP_AUM_Site24x7_API::instance()->get_option( 'client_secret' );
		$site247_code          = MainWP_AUM_Site24x7_API::instance()->get_option( 'code' );

		$site247_endpoints = array(
			'us' => 'US (zoho.com)',
			'eu' => 'EU (zoho.eu)',
			'cn' => 'CN (zoho.com.cn)',
			'in' => 'IN (zoho.in)',
			'au' => 'AU (zoho.com.au)',
		);

		$api_token_nodeping = MainWP_AUM_NodePing_API::instance()->get_api_token();

		$api_key_betteruptime = MainWP_AUM_BetterUptime_API::instance()->get_api_token();

		if ( 'site24x7' == $selected_service && ! empty( $site247_client_id ) && ! empty( $site247_code ) ) {
			// load/reload monitor settings.
			MainWP_AUM_Site24x7_Controller::instance()->get_api_monitor_settings();
		}

		$auth_url            = MainWP_AUM_Site24x7_API::instance()->authorization_request_url( 'admin' );
		$auth_report_url     = MainWP_AUM_Site24x7_API::instance()->authorization_request_url( 'reports' );
		$auth_operations_url = MainWP_AUM_Site24x7_API::instance()->authorization_request_url( 'operations' );

		$dev_console_url = $this->get_dev_console_link();

		if ( 'site24x7' == $selected_service ) {
			$expired = MainWP_AUM_Site24x7_API::instance()->check_access_expires();
			if ( $expired ) {
				echo '<div class="ui yellow message">' . sprintf( __( 'Access token expired. Please %1$sGenerate access%2$s again.' ), '<a href="' . admin_url( 'admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings&gen_access' ) . '">', '</a>' ) . '</div>';
			}
		}
		?>

		<form method="POST" action="admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings" class="ui form">
			<div class="ui secondary segment">
				<h3 class="ui dividing header"><?php _e( 'Advanced Uptime Monitor Settings', 'advanced-uptime-monitor-extension' ); ?></h3>
			<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Select monitoring service', 'advanced-uptime-monitor-extension' ); ?></label>
				<div class="ten wide column">						
						<select name="mainwp-select-service" id="mainwp-select-service" class="ui dropdown">
						<?php
						foreach ( $supported_services as $key => $val ) {
								echo '<option class="' . ( $selected_service == $key ? 'active' : '' ) . '" value="' . esc_attr( $key ) . '" data-tab="' . esc_attr( $key ) . '"' . ( $selected_service == $key ? 'selected="selected"' : '' ) . '>' . esc_html( $val ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>	
			</div>

			<div class="ui hidden divider"></div>

			<div id="mainwp-aum-settings-tabs">
				<div class="ui tab <?php echo 'uptimerobot' == $selected_service ? 'active' : ''; ?>" data-tab="uptimerobot">
					<h3 class="ui dividing header"><?php _e( 'Uptime Robot', 'advanced-uptime-monitor-extension' ); ?></h3>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Uptime Robot API Key', 'advanced-uptime-monitor-extension' ); ?></label>
							<div class="ten wide column">
								<input type="text" name="aum_services_settings[uptimerobot][api_key]" id="aum_services_settings[uptimerobot][api_key]" value="<?php echo esc_attr( $api_key_aum ); ?>" />
								<br />
								<em><?php _e( 'Enter your API key provided by the Uptime Robot. Not sure how to get it?', 'advanced-uptime-monitor-extension' ); ?> <a href="https://mainwp.com/help/docs/create-uptime-robot-api-key" target="_blank"><?php _e( 'Click here to find out!', 'advanced-uptime-monitor-extension' ); ?></a></em>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Default alert contact', 'advanced-uptime-monitor-extension' ); ?></label>
							<div class="ten wide column">
								<?php
								if ( is_array( $list_noti_contact ) && count( $list_noti_contact ) > 0 ) {
									?>
									<select name="aum_services_settings[uptimerobot][select_default_noti_contact]" class="ui dropdown">
										<?php
										foreach ( $list_noti_contact as $key => $val ) {
											if ( $default_notification_contact_id == $key ) {
												echo '<option value="' . esc_attr( $key ) . '" selected="selected">' . esc_html( $val ) . '</option>';
											} else {
												echo '<option value="' . esc_attr( $key ) . '" >' . esc_html( $val ) . '</option>';
											}
										}
										?>
									</select>
									<?php
								} else {
									echo __( 'No items found! Make sure to submit your Uptime Robot API key first.', 'advanced-uptime-monitor-extension' );
								}
								?>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Timezone', 'advanced-uptime-monitor-extension' ); ?></label>
							<div class="ten wide column">
								<?php
								if ( ! empty( $api_timezone ) ) {
									$this->display_timezone( $api_timezone );
								}
								?>
							</div>
						</div>
				</div>				
				<div class="ui tab <?php echo 'site24x7' == $selected_service ? 'active' : ''; ?>" data-tab="site24x7">
					<h3 class="ui dividing header"><?php _e( 'Site 24x7', 'advanced-uptime-monitor-extension' ); ?></h3>
					
					<div class="ui grid field">						
						<label class="six wide column middle aligned"><?php _e( 'Authentication Data centers', 'advanced-uptime-monitor-extension' ); ?></label>
						<div class="ten wide column">
							<select name="aum_services_settings[site24x7][endpoint]" class="ui dropdown">
								<?php
								foreach ( $site247_endpoints as $key => $val ) {
									if ( $site247_location == $key ) {
										echo '<option value="' . esc_attr( $key ) . '" selected="selected">' . esc_html( $val ) . '</option>';
									} else {
										echo '<option value="' . esc_attr( $key ) . '" >' . esc_html( $val ) . '</option>';
									}
								}
								?>
							</select>	
							<button type="button" go-link="<?php echo $auth_url; ?>" auth-scope="admin" class="ui green basic button mainwp_aum_auth_links <?php echo empty( $site247_client_id ) || empty( $site247_client_secret ) ? 'disabled' : ''; ?>"><?php echo empty( $site247_code ) ? esc_html__( 'Authorization Administrator', 'advanced-uptime-monitor-extension' ) : esc_html__( 'Re-authorization Administrator', 'advanced-uptime-monitor-extension' ); ?></button>				
							<button type="button" go-link="<?php echo $auth_report_url; ?>" auth-scope="report" class="ui green basic button mainwp_aum_auth_links <?php echo empty( $site247_client_id ) || empty( $site247_client_secret ) ? 'disabled' : ''; ?>"><?php echo empty( $site247_code ) ? esc_html__( 'Authorization Reports', 'advanced-uptime-monitor-extension' ) : esc_html__( 'Re-authorization Reports', 'advanced-uptime-monitor-extension' ); ?></button>				
							<button type="button" go-link="<?php echo $auth_operations_url; ?>" auth-scope="operations" class="ui green basic button mainwp_aum_auth_links <?php echo empty( $site247_client_id ) || empty( $site247_client_secret ) ? 'disabled' : ''; ?>"><?php echo empty( $site247_code ) ? esc_html__( 'Authorization Operations', 'advanced-uptime-monitor-extension' ) : esc_html__( 'Re-authorization Operations', 'advanced-uptime-monitor-extension' ); ?></button><br>				
							<input type="hidden" name="nonce_auth" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_go_auth' ); ?>" />
						</div>
					</div>					
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Client ID', 'advanced-uptime-monitor-extension' ); ?></label>
						<div class="ten wide column">
							<input type="text" name="aum_services_settings[site24x7][client_id]" id="aum_services_settings[site24x7][client_id]" value="<?php echo esc_attr( $site247_client_id ); ?>" />
							<br />
							<em><?php _e( 'Enter your Client ID & Client Secret provided by the Site24x7. Not sure how to get it?', 'advanced-uptime-monitor-extension' ); ?> <a href="<?php echo esc_url_raw( $dev_console_url ); ?>" target="_blank"><?php _e( 'Click here to find out!', 'advanced-uptime-monitor-extension' ); ?></a></em>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Client Secret', 'advanced-uptime-monitor-extension' ); ?></label>
						<div class="ten wide column">
							<input type="text" name="aum_services_settings[site24x7][client_secret]" id="aum_services_settings[site24x7][client_secret]" value="<?php echo esc_attr( $site247_client_secret ); ?>" />
							<?php
							$lasttime = MainWP_AUM_Site24x7_API::instance()->get_option( 'lasttime_generate_access', 0 );
							if ( ! empty( $lasttime ) ) {
								echo '<br><div class="ui green message">' . sprintf( __( 'Access token generated: %1$s ago. %2$sRe-generate%3$s access tokens again.', 'advanced-uptime-monitor-extension' ), human_time_diff( $lasttime ), '<a href="' . admin_url( 'admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings&gen_access' ) . '">', '</a>' ) . '</div>';
							}
							?>
													
						</div>
					</div>									
				</div>
				<div class="ui tab <?php echo 'nodeping' == $selected_service ? 'active' : ''; ?>" data-tab="nodeping">
					<h3 class="ui dividing header"><?php _e( 'NodePing', 'advanced-uptime-monitor-extension' ); ?></h3>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'NodePing API Token', 'advanced-uptime-monitor-extension' ); ?></label>
							<div class="ten wide column">
								<input type="text" name="aum_services_settings[nodeping][api_token]" id="aum_services_settings[nodeping][api_token]" value="<?php echo esc_attr( $api_token_nodeping ); ?>" />
								<br />
								<em><?php _e( 'Enter your API Token provided by the NodePing. Not sure how to get it?', 'advanced-uptime-monitor-extension' ); ?> <a href="https://mainwp.com/help/docs/create-uptime-robot-api-key" target="_blank"><?php _e( 'Click here to find out!', 'advanced-uptime-monitor-extension' ); ?></a></em>
							</div>
						</div>						
				</div>
				<div class="ui tab <?php echo 'betteruptime' == $selected_service ? 'active' : ''; ?>" data-tab="betteruptime">
					<h3 class="ui dividing header"><?php _e( 'Better Uptime', 'advanced-uptime-monitor-extension' ); ?></h3>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Better Uptime API Key', 'advanced-uptime-monitor-extension' ); ?></label>
							<div class="ten wide column">
								<input type="text" name="aum_services_settings[betteruptime][api_token]" id="aum_services_settings[betteruptime][api_token]" value="<?php echo esc_attr( $api_key_betteruptime ); ?>" />
								<br />
								<em><?php _e( 'Enter your API key provided by the Better Uptime. Not sure how to get it?', 'advanced-uptime-monitor-extension' ); ?> <a href="https://docs.betteruptime.com/api/getting-started#obtaining-an-api-token" target="_blank"><?php _e( 'Click here to find out!', 'advanced-uptime-monitor-extension' ); ?></a></em>
							</div>
						</div>
				</div>	
			</div>
			<div class="ui divider"></div>
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_option' ); ?>" />
			<input type="hidden" name="aum_submit" value="1"/>
			<input type="submit" name="submit_btn" class="ui big green right floated button" value="<?php esc_attr_e( 'Save Settings', 'advanced-uptime-monitor-extension' ); ?>" />
		</form>
		
		<script type="text/javascript">
			jQuery( document ).ready( function ($) {
				jQuery( document ).on( 'change', '#mainwp-select-service', function() {
					var tab = jQuery( '#mainwp-select-service' ).dropdown( 'get value' );
					jQuery( '#mainwp-aum-settings-tabs .tab' ).tab( 'change tab', tab );
				} )
				<?php
				if ( isset( $_GET['reload_uptime_monitors'] ) && 'yes' == $_GET['reload_uptime_monitors'] ) {
					?>
					mainwp_aum_reload_uptime_monitors(0, '<?php echo esc_html( $selected_service ); ?>' );
					<?php
				}
				?>
								
			} );			
			</script>
		<?php
	}

	/**
	 * Method: get_dev_console_link()
	 *
	 * Returns dev console links.
	 *
	 * @return string Dev console links.
	 */
	public function get_dev_console_link() {
		$endpoint = MainWP_AUM_Site24x7_API::instance()->get_option( 'endpoint', 'us' );
		$dev_urls = array(
			'us' => 'https://api-console.zoho.com/',
			'eu' => 'https://api-console.zoho.eu/',
			'cn' => 'https://api-console.zoho.com.cn/',
			'in' => 'https://api-console.zoho.in/',
			'au' => 'https://api-console.zoho.com.au/',
		);
		return isset( $dev_urls[ $endpoint ] ) ? $dev_urls[ $endpoint ] : '';
	}

	/**
	 * Method: check_api_existed()
	 *
	 * Checks if any of provided services has been enabled and API connected.
	 *
	 * @param string $selected_service Selected monitoring service.
	 *
	 * @return bool $api_existed True is API connected, false if not.
	 */
	public static function check_api_existed( $selected_service ) {
		$api_existed = true;
		if ( 'uptimerobot' == $selected_service ) {
			$api_key_aum = MainWP_AUM_UptimeRobot_API::instance()->get_api_key();
			if ( empty( $api_key_aum ) ) {
				$api_existed = false;
			}
		} elseif ( 'site24x7' == $selected_service ) {
			$access_token = MainWP_AUM_Site24x7_API::instance()->get_access_token( 'admin' );
			if ( empty( $access_token ) ) {
				$api_existed = false;
			}
		} elseif ( 'nodeping' == $selected_service ) {
			$api_token_nodeping = MainWP_AUM_NodePing_API::instance()->get_api_token();
			if ( empty( $api_token_nodeping ) ) {
				$api_existed = false;
			}
		}
		return $api_existed;
	}

	/**
	 * Method: render_api_not_existed()
	 *
	 * Renders content if none of monitoring services has been connected.
	 *
	 * @param string $selected_service Selected monitoring service.
	 *
	 * @return void
	 */
	public static function render_api_not_existed( $selected_service ) {
		?>
		<div class="ui padded segment">
			<div class="ui hidden divider"></div>
			<h2 class="ui icon header">
			<i class="info circle icon"></i>
			<div class="content">
				<?php
				if ( 'uptimerobot' == $selected_service ) {
					echo __( 'No Uptime Robot Account Connected', 'advanced-uptime-monitor-extension' );
					?>
				<div class="sub header"><?php _e( 'To add new monitors or load existing ones, please enter your Uptime Robot API key in the Uptime Robot Settings.', 'advanced-uptime-monitor-extension' ); ?></div>
					<?php
				} elseif ( 'site24x7' == $selected_service ) {
					echo __( 'No Site24x7 Account Connected', 'advanced-uptime-monitor-extension' );
					?>
				<div class="sub header"><?php _e( 'To add new monitors or load existing ones, please enter your Site24x7 API key in the Site24x7 Settings.', 'advanced-uptime-monitor-extension' ); ?></div>
					<?php
				} elseif ( 'nodeping' == $selected_service ) {
					echo __( 'No NodePing Account Connected', 'advanced-uptime-monitor-extension' );
					?>
				<div class="sub header"><?php _e( 'To add new monitors or load existing ones, please enter your NodePing API token in the NodePing Settings.', 'advanced-uptime-monitor-extension' ); ?></div>
					<?php
				} elseif ( 'betteruptime' == $selected_service ) {
					echo __( 'No BetterUptime Account Connected', 'advanced-uptime-monitor-extension' );
					?>
				<div class="sub header"><?php _e( 'To add new monitors or load existing ones, please enter your BetterUptime API token in the BetterUptime Settings.', 'advanced-uptime-monitor-extension' ); ?></div>
				<?php } ?>
				<div class="ui hidden divider"></div>
					<a class="ui big green button" href="admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension&tab=settings"><?php echo __( 'Advanced Uptime Monitor Settings', 'advanced-uptime-monitor-extension' ); ?></a>
			</div>
			</h2>
			<div class="ui hidden divider"></div>
		</div>
		<?php
	}

	/**
	 * Method: render_monitor_page()
	 *
	 * Renders the Monitors page.
	 *
	 * @return void
	 */
	public static function render_monitors_page() {
		$selected_service = get_option( 'mainwp_aum_enabled_service', 'uptimerobot' );
		echo '<input name="mainwp-aum-form-field-service" type="hidden" id="mainwp-aum-form-field-service" value="' . esc_html( $selected_service ) . '">';
		?>
			<div class="mainwp-actions-bar">
				<div class="ui grid">
					<div class="ui two column row">
					<div class="middle aligned column">
							<input type="hidden" id="mainwp_aum_extension_display_dashboard_nonce" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_display_dashboard' ); ?>"/>
							<div id="aum_monitor_action" class="actions ui mini form">
								<select name="monitor_action" class="ui mini dropdown">
									<option value="-1" selected="selected"><?php _e( 'Bulk Actions', 'advanced-uptime-monitor-extension' ); ?></option>
									<option value="start" ><?php _e( 'Start', 'advanced-uptime-monitor-extension' ); ?></option>
									<option value="pause" ><?php _e( 'Pause', 'advanced-uptime-monitor-extension' ); ?></option>
									<option value="delete"><?php _e( 'Delete', 'advanced-uptime-monitor-extension' ); ?></option>
								</select>
								<input type="button" href="javascript:void(0)" onclick="mainwp_aum_js_apply_check(this, event)" name="doaction" id="doaction" class="ui mini button" value="<?php _e( 'Apply', 'advanced-uptime-monitor-extension' ); ?>">
							</div>
						</div>
					<div class="right aligned middle aligned column">
						<a onclick="mainwp_aum_on_add_monitor()" class="ui green mini button" ><?php _e( 'Create New Monitor', 'advanced-uptime-monitor-extension' ); ?></a>
						<button id="aum-auto-create-monitors" disabled="disabled" class="ui basic green mini button"><?php _e( 'Create Monitors for All Sites', 'advanced-uptime-monitor-extension' ); ?></button>
				</div>
			</div>			
			</div>
			</div>
		<div class="ui segment" >
			<div class="ui active inverted dimmer" id="aum-loader" style="min-height:50vh">
				<div class="ui large text loader"><?php _e( 'Loading monitors...', 'advanced-uptime-monitor-extension' ); ?></div>
			  </div>
				<div id="mainwp-advanced-uptime-monitor-monitors"></div>
			</div>
		<?php $get_page = isset( $_GET['get_page'] ) && $_GET['get_page'] > 0 ? $_GET['get_page'] : 1; ?>
		<script type="text/javascript">
		// Load monitors
		jQuery( document ).ready( function () {

			mainwp_aum_get_monitors = function( pPage ) {
				jQuery('#aum-loader').show();
				jQuery.ajax( {
					url: ajaxurl,
					type: "POST",
					data: {
						action: 'mainwp_advanced_uptime_monitor_urls',
						get_page: pPage,
						service: '<?php echo esc_js( $selected_service ); ?>',
						wp_nonce: '<?php echo wp_create_nonce( 'mainwp_aum_nonce_monitor_urls' ); ?>'
					},
					error: function () {
						jQuery( '#aum-loader' ).hide();
						jQuery( '#mainwp-advanced-uptime-monitor-monitors' ).html( '<div class="ui yellow message">' + __( 'Request timed out. Please, try again later.' ) + '</div>' ).show();
					},
					success: function (response) {
						jQuery( '#aum-loader' ).hide();
						jQuery( '#mainwp-advanced-uptime-monitor-monitors' ).html( response ).show();
						jQuery( '#aum-auto-create-monitors' ).removeAttr( 'disabled' );

						jQuery( '#mainwp_aum_monitor_select_page' ).dropdown();
						jQuery( '#mainwp_aum_monitor_select_page' ).dropdown( 'setting', 'onChange', function( val ){
							mainwp_aum_get_monitors( val );
						} );

						if ( typeof mainwp_table_check_columns_init === 'function' )
							mainwp_table_check_columns_init(); // to fix select all checkboxs for ajax table.
					},
					timeout: 20000
				});
			};

			mainwp_aum_get_monitors( <?php echo intval( $get_page ); ?> );

		} );

		// Auto create monitors.
		jQuery( '#aum-auto-create-monitors' ).click( function ( event ) {
			if ( jQuery( this ).prop( 'disabled' ) ) {
			return false;
			}

			if ( !confirm( 'Are you sure?' ) )
				return false;

			jQuery( this ).attr( 'disabled', 'disabled' );
			
			mainwp_aum_uptimerobot_popup( 'auto_add_sites' );
		} );
		</script>
		<?php
	}

	/**
	 * Method: get_overal_monitors_status().
	 *
	 * Gets the overal monitors status.
	 *
	 * @param string $service Selected monitoring service.
	 *
	 * @return string $result Overal monitoring status.
	 */
	public static function get_overal_monitors_status( $service ) {
		$result = array();
		if ( 'uptimerobot' == $service ) {
			$result = MainWP_AUM_UptimeRobot_Settings_Handle::instance()->get_overal_monitors_status();
		} elseif ( 'site24x7' == $service ) {
			$result = MainWP_AUM_Site24x7_Settings_Handle::get_instance()->get_overal_monitors_status();
		} elseif ( 'nodeping' == $service ) {
			$result = MainWP_AUM_NodePing_Settings_Handle::get_instance()->get_overal_monitors_status();
		}
		return $result;
	}

	/**
	 * Method: get_event_statuses().
	 *
	 * Gets event status.
	 *
	 * @return array $even_stauses Available event statuses.
	 */
	public static function get_event_statuses() {
		// 1 - down, 2 - up, 99 - paused, 98 - started.
		return $event_statuses = array(
			1  => 'down',
			2  => 'up',
			99 => 'paused',
			98 => 'started',
		);
	}

	/**
	 * Method: display_timezone()
	 *
	 * Displays selected timezone.
	 *
	 * @param string $timezone Timezone.
	 *
	 * @return void
	 */
	public function display_timezone( $timezone ) {
		if ( ! is_array( $timezone ) ) {
			return;
		}
		$offset_time = $timezone['offset_time'];
		$out         = $timezone['text'] . ' (GMT';

		if ( $offset_time >= 0 ) {
			$out .= '+';
		} else {
			$out         .= '-';
			$offset_time *= -1;
		}

		$hour   = floor( $offset_time );
		$min    = ( $offset_time - $hour ) * 60;
		$format = '%1$02d:%2$02d:00)';
		$out   .= sprintf( $format, $hour, $min );
		echo $out;
	}

	/**
	 * Method: get_uptime_state()
	 *
	 * Renders uptime status.
	 *
	 * @param string $selected_service Selected service.
	 * @param string $monitor Monitor status.
	 *
	 * @return string $sta Uptime status.
	 */
	public static function get_uptime_state( $selected_service, $monitor ) {
		$aum_status = '';
		if ( 'uptimerobot' == $selected_service ) {
			$aum_status = MainWP_AUM_UptimeRobot_Controller::get_uptime_monitor_state( $monitor );
		} elseif ( 'site24x7' == $selected_service ) {
			$aum_status = MainWP_AUM_Site24x7_Controller::get_site24x7_monitor_state( $monitor );
		} elseif ( 'nodeping' == $selected_service ) {
			$aum_status = MainWP_AUM_Nodeping_Controller::get_nodeping_monitor_state( $monitor );
		} elseif ( 'betteruptime' == $selected_service ) {
			$aum_status = MainWP_AUM_BetterUptime_Controller::get_betteruptime_monitor_state( $monitor );
		}
		return $aum_status;
	}


	/**
	 * Method: render_ratio_value()
	 *
	 * Renders uptime ratio value.
	 *
	 * @param string $status Monitor status.
	 * @param string $ratio  Uptime ratio.
	 *
	 * @return string $ratio Ratio value.
	 */
	public function render_ratio_value( $status, $ratio ) {
		$ratio = number_format( $ratio, 2 );
		return $ratio . '%';
	}

	public function aum_get_data( $websiteid = null, $start_date = null, $end_date = null ) {
		global $mainwpAUMExtensionActivator;
		$website  = apply_filters( 'mainwp_getsites', $mainwpAUMExtensionActivator->get_child_file(), $mainwpAUMExtensionActivator->get_child_key(), $websiteid );
		$url_site = '';
		if ( $website && is_array( $website ) ) {
			$website  = current( $website );
			$url_site = $website['url'];
		}

		if ( empty( $url_site ) ) {
			return false;
		}

		$enabled_service = get_option( 'mainwp_aum_enabled_service', 'uptimerobot' );
		$monitor_address = MainWP_AUM_DB::instance()->get_monitor_by( 'url_address', $url_site, $enabled_service );

		if ( ! $monitor_address ) {
			// try to get by url_address_like.
			$monitor_address = MainWP_AUM_DB::instance()->get_monitor_by( 'url_address_like', $url_site, $enabled_service );
		}

		if ( ! $monitor_address ) {
			return false;
		}

		$result = false;

		if ( 'uptimerobot' == $enabled_service ) {
			$result = MainWP_AUM_UptimeRobot_Controller::instance()->aum_get_data( $monitor_address, $start_date, $end_date );
		} elseif ( 'site24x7' == $enabled_service ) {
			$result = MainWP_AUM_Site24x7_Controller::instance()->aum_get_data( $monitor_address, $start_date, $end_date );
		} elseif ( 'nodeping' == $enabled_service ) {
			$result = MainWP_AUM_Nodeping_Controller::instance()->aum_get_data( $monitor_address, $start_date, $end_date );
		} elseif ( 'betteruptime' == $enabled_service ) {
			$result = MainWP_AUM_BetterUptime_Controller::instance()->aum_get_data( $monitor_address, $start_date, $end_date );
		}
		return $result;
	}

	/**
	 * Method get_ratio_value()
	 *
	 * Gets uptime ratio value.
	 *
	 * @param string $value Value.
	 *
	 * @return string $value Value.
	 */
	public function get_ratio_value( $value ) {
		if ( empty( $value ) ) {
			return 'N/A';
		}
		$value = (float) $value;
		$value = sprintf( '%.2f', $value );
		$exp   = explode( '.', $value );

		if ( is_array( $exp ) && count( $exp ) == 2 ) {
			if ( 0 == intval( $exp[1] ) ) {
				return $exp[0];
			}
		}

		return $value;
	}

}
