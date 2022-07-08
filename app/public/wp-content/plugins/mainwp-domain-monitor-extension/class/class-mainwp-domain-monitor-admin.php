<?php
namespace MainWP\Extensions\Domain_Monitor;

class MainWP_Domain_Monitor_Admin {

	public $version           = '1.0';
	public static $sort_field = '';

	/**
	 * Static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Domain_Monitor_Admin
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Runs each time the class is called.
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'init', array( &$this, 'localization' ) );
	}

	/**
	 * Initiate Hooks
	 *
	 * Initiates hooks for the Domain Monitor extension.
	 */
	public function init() {
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'mainwp_help_sidebar_content', array( $this, 'mainwp_help_content' ) );
		add_filter( 'mainwp_header_actions_right', array( $this, 'screen_options' ), 10, 2 );
		add_action( 'mainwp_cron_jobs_list', array( $this, 'cron_job_info' ) );

		MainWP_Domain_Monitor_Core::get_instance()->init();
		MainWP_Domain_Monitor_DB::get_instance()->init();
		MainWP_Domain_Monitor_Hooks::get_instance()->init();

		$this->handle_sites_screen_settings();

		$this->init_cron_alert();
	}

	/**
	 * Localization
	 *
	 * Sets the localization domain.
	 */
	public function localization() {
		load_plugin_textdomain( 'mainwp-domain-monitor-extension', false, MAINWP_DOMAIN_MONITOR_PLUGIN_DIR . '/languages/' );
	}

	/**
	 * Sites Page Check
	 *
	 * Checks if the current page is individual site Domain Monitor page.
	 *
	 * @return bool True if correct, false if not.
	 */
	public static function is_managesites_page() {
		if ( isset( $_GET['page'] ) && ( 'ManageSitesDomainMonitor' == $_GET['page'] ) ) {
			return true;
		}
		  return false;
	}


	/**
	 * Plugin Row Meta
	 *
	 * Displays the meta in the plugin row on the WP > Plugins > Installed Plugins page.
	 *
	 * @param array  $plugin_meta Plugin meta data.
	 * @param string $plugin_file Plugin file.
	 *
	 * @return array  $plugin_meta Plugin meta data.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( MAINWP_DOMAIN_MONITOR_PLUGIN_SLUG != $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
	}

	/**
	 * Admin Init
	 *
	 * Initiates admin hooks.
	 */
	public function admin_init() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Domain-Monitor-Extension' == $_GET['page'] || 'managesites' == $_GET['page'] || 'ManageSitesDomainMonitor' == $_GET['page'] ) ) {
			wp_enqueue_style( 'mainwp-domain-monitor-extension', MAINWP_DOMAIN_MONITOR_PLUGIN_URL . 'css/mainwp-domain-monitor.css', array(), $this->version );
			wp_enqueue_script( 'mainwp-domain-monitor-extension', MAINWP_DOMAIN_MONITOR_PLUGIN_URL . 'js/mainwp-domain-monitor.js', array( 'jquery', 'heartbeat' ), $this->version );
			wp_localize_script(
				'mainwp-domain-monitor-extension',
				'mainwpDomainMonitor',
				array(
					'nonce'     => wp_create_nonce( 'mwp_domain_monitor_nonce' )
				)
			);
		}
		MainWP_Domain_Monitor_Dashboard::get_instance()->admin_init();
	}

	/**
	 * Render tabs
	 *
	 * Renders the extension page tabs.
	 */
	public static function render_extension_page() {

		$current_site_id = null;
		$website         = null;

		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			$current_site_id = $_GET['id'];
			$dbwebsites      = self::get_db_sites( array( $current_site_id ) );
			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}

		if ( $current_site_id ) {
			$error = '';
			if ( empty( $website ) || empty( $website->id ) ) {
				$error = __( 'Undefined site id. Please, try again.', 'mainwp-domain-monitor-extension' );
			}

			do_action( 'mainwp_pageheader_sites', 'DomainMonitor' );

			if ( ! empty( $error ) ) {
			  echo '<div class="ui segment">';
			  echo '<div class="ui yellow message">' . $error . '</div>';
			  echo '</div>';
			} else {
				self::render_extension_page_site( $website );
			}

			do_action( 'mainwp_pagefooter_sites', 'DomainMonitor' );
		} else {
			self::render_extension_page_general();
		}
	}

	/**
	 * Render extension page
	 *
	 * Renders the main extension page.
	 */
	public static function render_extension_page_general() {
		$curent_tab = 'dashboard';
		if ( isset( $_GET['tab'] ) ) {
			if ( 'settings' == $_GET['tab'] ) {
				  $curent_tab = 'settings';
			}
		} elseif ( isset( $_POST['mwp_domain_monitor_setting_submit'] ) ) {
			$curent_tab = 'settings';
		}
		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-domain-monitor-menu">
			<a href="admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension&tab=dashboard" class="item <?php echo ( $curent_tab == 'dashboard' ? 'active' : '' ); ?>"><i class="tasks icon"></i> <?php _e( 'Dashboard', 'mainwp-domain-monitor-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension&tab=settings" class="item <?php echo ( $curent_tab == 'settings' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Settings', 'mainwp-domain-monitor-extension' ); ?></a>
		</div>
		<?php if ( $curent_tab == 'dashboard' || $curent_tab == '' ) : ?>
			<?php MainWP_Domain_Monitor_Dashboard::render_actions_bar(); ?>
			<div class="ui segment">
				<?php MainWP_Domain_Monitor_Dashboard::gen_dashboard_tab(); ?>
			</div>
		<?php endif; ?>
		<?php if ( $curent_tab == 'settings' ) : ?>
			<div class="ui segment">
				<?php self::handle_general_settings_post(); ?>
				<?php self::render_settings(); ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render extension site page
	 *
	 * Renders the extension page for individual site.
	 */
	public static function render_extension_page_site( $website ) {
		$current_site_id = intval( $_GET['id'] );
		$curent_tab      = 'domain-info';
		if ( isset( $_GET['tab'] ) && 'settings' == $_GET['tab'] ) {
			$curent_tab = 'settings';
		}
		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-domain-monitor-menu">
			<a href="admin.php?page=ManageSitesDomainMonitor&id=<?php echo intval( $current_site_id ); ?>&tab=domain-info" class="item <?php echo 'domain-info' == $curent_tab ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Domain Info', 'mainwp-domain-monitor-extension' ); ?></a>
			<a href="admin.php?page=ManageSitesDomainMonitor&id=<?php echo intval( $current_site_id ); ?>&tab=settings" class="item <?php echo 'settings' == $curent_tab ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Settings', 'mainwp-domain-monitor-extension' ); ?></a>
		</div>
		<?php if ( $curent_tab == 'domain-info' || $curent_tab == '' ) : ?>
			<div class="ui segment">
				<?php self::render_domain_profile( $current_site_id ); ?>
			</div>
		<?php endif; ?>
		<?php if ( $curent_tab == 'settings' ) : ?>
			<div class="ui segment">
				<?php self::handle_individual_settings_post( $current_site_id ); ?>
				<?php self::render_settings( true ); ?>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render settings
	 *
	 * Renders the extension settings page.
	 *
	 * @param int $individual Individual or General settings.
	 */
	public static function render_settings( $individual = false ) {

		$current_site_id = 0;
		if ( self::is_managesites_page() ) {
			$current_site_id = $_GET['id'];
		}

		// Test Emails
		if ( isset( $_GET['email'] ) && 'send' == $_GET['email'] ) {
			self::cron_domain_monitor_alert();
		}

		$use_schedule           = 0;
		$frequency              = 0;
		$notification_threshold = "";

		if ( $current_site_id ) {
			$site_settings = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $current_site_id );
		} else {
			$use_schedule           = MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'use_schedule' );
			$frequency              = MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'frequency' );
			$notification_threshold = MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'notification_threshold' );
		}

		$overwrite = 0;

		if ( $current_site_id && $site_settings ) {
			$settings = isset( $site_settings->settings ) ? $site_settings->settings : '';
			if ( is_array( $settings ) ) {
				$use_schedule           = isset( $settings['use_schedule'] ) ? $settings['use_schedule'] : '';
				$frequency              = isset( $settings['frequency'] ) ? $settings['frequency'] : '';
				$notification_threshold = isset( $settings['notification_threshold'] ) ? $settings['notification_threshold'] : '';
			}
			$overwrite = $site_settings->overwrite;
		}

		$frequency              = empty( $frequency ) ? 86400 : $frequency;
		$notification_threshold = empty( $notification_threshold ) ? 7 : $notification_threshold;

		$auto_check_frequency = array(
			86400   => __( 'Once a Day', 'mainwp-domain-monitor-extension' ),
			604800  => __( 'Once a Week', 'mainwp-domain-monitor-extension' ),
			1296000 => __( 'Twice a Month', 'mainwp-domain-monitor-extension' ),
			2592000 => __( 'Once Monthly', 'mainwp-domain-monitor-extension' ),
		);
		?>
		<?php if ( MainWP_Domain_Monitor_Utility::show_mainwp_message( 'mainwp-domain-monitor-settings-info-message' ) ) : ?>
			<div class="ui info message">
				<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-domain-monitor-settings-info-message"></i>
				<?php echo sprintf( __( 'Manage the Domain Monitor settings. For detailed information, review %1$shelp documentation%2$s.', 'mainwp-domain-monitor-extension' ), '<a href="https://kb.mainwp.com/docs/mainwp-domain-monitor-extension/" target="_blank">', '</a>' ); ?>
			</div>
		<?php endif; ?>
		<form id="mainwp-domain-monitor-settings-form" method="post" action="<?php echo ( $individual ? 'admin.php?page=ManageSitesDomainMonitor&id=' . $current_site_id . '&tab=settings' : 'admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension&tab=settings' ); ?>" class="ui form">
			<?php if ( $individual ) : ?>
			<h3 class="header"><?php echo __( 'Individual Site Domain Monitor Settings', 'mainwp-domain-monitor-extension' ); ?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Overwrite general settings', 'mainwp-domain-monitor-extension' ); ?></label>
				<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable to overwrite the general settings for this child site.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="top left">
					<input type="checkbox" name="mainwp-domain-monitor-overwrite-general-settings" id="mainwp-domain-monitor-overwrite-general-settings" value="1" <?php echo ( 0 == $overwrite ? '' : 'checked="checked"' ); ?>><label></label>
				</div>
			</div>
			<?php endif; ?>
			<?php if ( $individual ) : ?>
			<div class="mainwp-domain-monitor-overwrite-general-settings-toggle-area" <?php echo 0 == $overwrite ? 'style="display:none"' : ''; ?>>
			<?php endif; ?>
			<h3 class="header"><?php echo __( 'Domain Monitor Settings', 'mainwp-domain-monitor-extension' ); ?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Notifications threshold', 'mainwp-domain-monitor-extension' ); ?></label>
				<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set the domain expiration threshold for the email notification.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="top left">
					<input type="number" id="mainwp-domain-monitor-notification-threshold" name="mainwp-domain-monitor-notification-threshold" value="<?php echo $notification_threshold; ?>" />
				</div>
			</div>
			<h3 class="header"><?php echo __( 'Automated Checks', 'mainwp-domain-monitor-extension' ); ?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Automatically check domains', 'mainwp-domain-monitor-extension' ); ?></label>
			  <div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable if you want the extension to run domain checks automatically.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="top left">
					<input type="checkbox" name="mainwp-domain-monitor-automatic-checks" id="mainwp-domain-monitor-automatic-checks" value="1" <?php checked( $use_schedule ); ?>><label></label>
				</div>
			</div>
			<div class="ui grid field" id="mainwp-domain-monitor-automatic-checks-toggle-area" <?php echo $use_schedule == 1 ? '' : 'style="display:none"'; ?>>
				<label class="six wide column middle aligned"><?php _e( 'Automated domain checks frequency', 'mainwp-domain-monitor-extension' ); ?></label>
			  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Select automated domain lookup frequency.', 'mainwp-domain-monitor-extension' ); ?>" data-inverted="" data-position="top left">
					<select id="mainwp-domain-monitor-automatic-checks-frequency" name="mainwp-domain-monitor-automatic-checks-frequency" class="ui dropdown">
						<?php foreach ( $auto_check_frequency as $key => $value ) : ?>
							<?php
							$_select = '';
							if ( $key == $frequency ) {
								$_select = ' selected ';
							}
							echo '<option value="' . $key . '" ' . $_select . '>' . $value . '</option>';
							?>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<?php if ( $individual ) : ?>
			</div>
			<?php endif; ?>
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'mwp_domain_monitor_nonce' ); ?>">
			<input type="hidden" name="mwp_domain_monitor_setting_submit" value="1">
			<div class="ui divider"></div>
			<input type="submit" name="submit" id="submit" class="ui big green button" value="<?php _e( 'Save Settings', 'mainwp-domain-monitor-extension' ); ?>">
		</form>
		<?php
	}

	public static function render_domain_profile( $website ) {
		$domain_data = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $website );
		?>
		<?php if ( MainWP_Domain_Monitor_Utility::show_mainwp_message( 'mainwp-domain-monitor-report-info-message' ) ) : ?>
			<div class="ui info message">
				<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-domain-monitor-report-info-message"></i>
				<div><?php echo __( 'Review the Domain info for the child site.', 'mainwp-domain-monitor-extension' ); ?></div>
				<div><?php echo __( 'All report data is generated by a WHOIS server.', 'mainwp-domain-monitor-extension' ); ?></div>
			</div>
		<?php endif; ?>
		<table class="ui table">
			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Domain Profile', 'mainwp-domain-monitor-extension' ); ?> (Last check: <?php echo MainWP_Domain_Monitor_Utility::format_timestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->last_check ) ); ?>)</th>
				</tr>
			</thead>
			<tbody>
				<tr><td><strong><?php _e( 'Domain Name', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->domain_name; ?></td></tr>
				<tr><td><strong><?php _e( 'Registrar', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->registrar; ?></td></tr>
				<tr><td><strong><?php _e( 'Registrar URL', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><a href="<?php echo $domain_data->registrar_url; ?>" target="_blank" data-tooltip="<?php esc_attr_e( 'Visit registrar website.', 'mainwp-domain-monitor-extension' ); ?>" data-position="left center" data-inverted=""><?php echo $domain_data->registrar_url; ?></a></td></tr>
				<tr><td><strong><?php _e( 'Registry Domain ID', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->registry_domain_id; ?></td></tr>
				<tr><td><strong><?php _e( 'Registrar WHOIS Server', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->registrar_whois_server; ?></td></tr>
				<tr><td><strong><?php _e( 'Registrar Abuse Contact Email', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->registrar_abuse_contact_email; ?></td></tr>
				<tr><td><strong><?php _e( 'Registrar Abuse Contact Phone', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->registrar_abuse_contact_phone; ?></td></tr>
				<tr><td><strong><?php _e( 'Registrar IANA ID', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->registrar_iana_id; ?></td></tr>
				<tr><td><strong><?php _e( 'DNSSEC', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo $domain_data->dnssec; ?></td></tr>
				<tr><td><strong><?php _e( 'Domain Creation Date', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->creation_date ) ); ?></td></tr>
				<tr><td><strong><?php _e( 'Domain Update Date', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->updated_date ) ); ?></td></tr>
				<tr><td><strong><?php _e( 'Domain Expiration Date', 'mainwp-domain-monitor-extension' ); ?></strong></td><td><?php echo MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->expiry_date ) ); ?></td></tr>
				<tr>
					<td><strong><?php _e( 'Domain Status', 'mainwp-domain-monitor-extension' ); ?></strong></td>
					<td>
						<?php if ( isset( $domain_data->domain_status_1 ) && ! empty( $domain_data->domain_status_1 ) ) : ?>
						<div><?php echo $domain_data->domain_status_1; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->domain_status_2 ) && ! empty( $domain_data->domain_status_2 ) ) : ?>
						<div><?php echo $domain_data->domain_status_2; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->domain_status_3 ) && ! empty( $domain_data->domain_status_3 ) ) : ?>
						<div><?php echo $domain_data->domain_status_3; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->domain_status_4 ) && ! empty( $domain_data->domain_status_4 ) ) : ?>
						<div><?php echo $domain_data->domain_status_4; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->domain_status_5 ) && ! empty( $domain_data->domain_status_5 ) ) : ?>
						<div><?php echo $domain_data->domain_status_5; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->domain_status_6 ) && ! empty( $domain_data->domain_status_6 ) ) : ?>
						<div><?php echo $domain_data->domain_status_6; ?></div>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td>
						<strong><?php _e( 'Name Server(s)', 'mainwp-domain-monitor-extension' ); ?></strong>
					</td>
					<td>
						<?php if ( isset( $domain_data->name_server_1 ) && ! empty( $domain_data->name_server_1 ) ) : ?>
						<div><?php echo $domain_data->name_server_1; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->name_server_2 ) && ! empty( $domain_data->name_server_2 ) ) : ?>
						<div><?php echo $domain_data->name_server_2; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->name_server_3 ) && ! empty( $domain_data->name_server_3 ) ) : ?>
						<div><?php echo $domain_data->name_server_3; ?></div>
						<?php endif; ?>
						<?php if ( isset( $domain_data->name_server_4 ) && ! empty( $domain_data->name_server_4 ) ) : ?>
						<div><?php echo $domain_data->name_server_4; ?></div>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render metabox
	 *
	 * Initiates the metabox.
	 */
	public static function render_metabox() {
		if ( ! isset( $_GET['page'] ) || 'managesites' == $_GET['page'] ) {
			self::site_overview_metabox();
		} else {
			self::overview_metabox();
		}
	}

	/**
	 * Global Metabox
	 *
	 * Renders the Overview page widget content.
	 */
	public static function overview_metabox() {
		$domains = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_domains( true );
		$unique_domains = array_unique( $domains, SORT_REGULAR );
		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
					<?php echo __( 'Domain Monitor', 'mainwp-domain-monitor-extension' ); ?>
					<div class="sub header"><?php echo __( 'Domain Monitor extension.', 'mainwp-domain-monitor-extension' ); ?></div>
				</h3>
			</div>
		</div>
		<div class="ui hidden divider"></div>
		<table class="ui table" id="mainwp-domain-monitor-domains-table">
			<thead>
				<tr>
					<th class="collapsing no-sort"><?php echo __( '', 'mainwp-domain-monitor-extension' ); ?></th>
					<th><?php echo __( 'Domain', 'mainwp-domain-monitor-extension' ); ?></th>
					<th class="collapsing"><?php echo __( 'Expiration Date', 'mainwp-domain-monitor-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $unique_domains as $domain ) : 
					if ( ! is_object( $domain ) ) {
						continue;
					}
					?>
					<?php
					$expires = $domain->expiry_date ? round( ( $domain->expiry_date - time() ) / ( 60 * 60 * 24 ) ) : 0;

					$icon_code  = '<i class="check large circle icon green"></i>';
					if ( 30 > $expires && $expires >= 1 ) {
						$icon_code  = '<i class="warning large circle icon yellow"></i>';
					} else if  ( 1 > $expires ) {
						$icon_code  = '<i class="times large circle icon red"></i>';
					}
					?>
					<tr>
						<td><span data-tooltip="Expires in <?php echo $expires; ?> day(s)." data-inverted="" data-position="right center"><?php echo $icon_code; ?></span></td>
						<td><?php echo $domain->domain_name; ?></td>
						<td><?php echo MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain->expiry_date ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<div class="ui hidden divider"></div>
		<div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
		<div class="ui two columns grid">
			<div class="left aligned column">
				<a href="admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension" class="ui basic green button"><?php esc_html_e( 'Domain Monitor Dashboard', 'mainwp-domain-monitor-extension' ); ?></a>
			</div>
		</div>
		<script type="text/javascript">
		jQuery( document ).ready( function () {
			jQuery( '#mainwp-domain-monitor-domains-table' ).DataTable( {
				"stateSave": true,
				"stateDuration": 0,
				"scrollX": false,
				"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
				"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
				"order": [ [ 1, "asc" ] ],
				"language": { "emptyTable": "No domains found." },
			} );
		} );
		</script>
		<?php
	}

	/**
	 * Individual Metabox
	 *
	 * Renders the individual site Overview page widget content.
	 */
	public static function site_overview_metabox() {
		$site_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;

		if ( empty( $site_id ) ) {
			return;
		}

		$domain_data = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $site_id );
		$expires = round( ( $domain_data->expiry_date - time() ) / ( 60 * 60 * 24 ) );

		$icon_code  = '<i class="check huge circle icon green"></i>';
		if ( 30 > $expires && $expires >= 1 ) {
			$icon_code  = '<i class="warning huge circle icon yellow"></i>';
		} else if  ( 1 > $expires ) {
			$icon_code  = '<i class="times huge circle icon red"></i>';
		}
		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
					<?php echo __( 'Domain Monitor', 'mainwp-domain-monitor-extension' ); ?>
					<div class="sub header"><?php echo __( 'Domain Monitor extension.', 'mainwp-domain-monitor-extension' ); ?></div>
				</h3>
			</div>
		</div>
		<div class="ui hidden divider"></div>
		<div class="ui relaxed middle aligned list">
		  <div class=" item">
		    <?php echo $icon_code; ?>
		    <div class="content">
		      <h3 class="header"><?php echo $domain_data->domain_name; ?></h3>
		      <div class="description"><?php echo __( 'Expires in: ', 'mainwp-domain-monitor-extension' ) . $expires . ' days (' . MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->expiry_date ) ) . ')'; ?></div>
		    </div>
		  </div>
		</div>
		<div class="ui hidden divider"></div>
		<div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
		<div class="ui two columns grid">
			<div class="left aligned column">
				<a href="admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension" class="ui basic green button"><?php esc_html_e( 'Domain Monitor Dashboard', 'mainwp-domain-monitor-extension' ); ?></a>
			</div>
			<div class="right aligned column">
				<a href="admin.php?page=ManageSitesDomainMonitor&id=<?php echo $site_id; ?>" class="ui green button"><?php esc_html_e( 'Detailed Report', 'mainwp-domain-monitor-extension' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Individual Settigns Post
	 *
	 * Handles the Individual site save settings post request.
	 *
	 * @param int $websiteId Child site ID.
	 */
	public static function handle_individual_settings_post( $websiteId ) {
		if ( isset( $_POST['submit'] ) && $websiteId ) {
			self::handle_settings_post( $websiteId );
		}
	}

	/**
	 * General Settigns Post
	 *
	 * Handles the general save settings post request.
	 *
	 * @return mixed $save_output Save output.
	 */
	public static function handle_general_settings_post() {
		$save_output = self::handle_settings_post();
		return $save_output;
	}

	/**
	 * Check Security
	 *
	 * Verifies nonce for security reasons.
	 */
	public static function check_security() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'mwp_domain_monitor_nonce' ) ) {
			die( __( 'Nonce could not be verified. Please reload and try again.', 'mainwp-domain-monitor-extension' ) );
		}
	}

	/**
	 * Settigns Post
	 *
	 * Handles the save settings post request.
	 *
	 * @param int $website_id Child site ID.
	 *
	 * @return mixed Save output.
	 */
	public static function handle_settings_post( $website_id = null ) {
		if ( isset( $_POST['mwp_domain_monitor_setting_submit'] ) ) {
			self::check_security();
		}

		if ( isset( $_POST['mwp_domain_monitor_setting_submit'] ) || $website_id ) {
			$output = array();

			$use_schedule = '';
			if ( isset( $_POST['mainwp-domain-monitor-automatic-checks'] ) ) {
				$use_schedule = intval( $_POST['mainwp-domain-monitor-automatic-checks'] );
			}

			$frequency = '';
			if ( isset( $_POST['mainwp-domain-monitor-automatic-checks-frequency'] ) ) {
				$frequency = intval( $_POST['mainwp-domain-monitor-automatic-checks-frequency'] );
			}

			$notification_threshold = '';
			if ( isset( $_POST['mainwp-domain-monitor-notification-threshold'] ) ) {
				$notification_threshold = intval( $_POST['mainwp-domain-monitor-notification-threshold'] );
			}

			if ( isset( $_POST['mwp_domain_monitor_setting_submit'] ) && ! $website_id ) {
				$old_frequency = MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'frequency' );

				MainWP_Domain_Monitor_Utility::get_instance()->set_option( 'use_schedule', $use_schedule );
				MainWP_Domain_Monitor_Utility::get_instance()->set_option( 'frequency', $frequency );
				MainWP_Domain_Monitor_Utility::get_instance()->set_option( 'notification_threshold', $notification_threshold );

				if ( $use_schedule && ! wp_next_scheduled( 'mainwp_domain_monitor_action_cron_start' ) ) {
					wp_schedule_event( time() + $frequency, 'mainwp_domain_monitor_start_interval', 'mainwp_domain_monitor_action_cron_start' );
				} elseif ( $use_schedule && $frequency !== $old_frequency ) {
					wp_clear_scheduled_hook( 'mainwp_domain_monitor_action_cron_start' );
					wp_schedule_event( time() + $frequency, 'mainwp_domain_monitor_start_interval', 'mainwp_domain_monitor_action_cron_start' );
				}

				if ( ! $use_schedule ) {
					wp_clear_scheduled_hook( 'mainwp_domain_monitor_action_cron_start' );
				}

				if ( empty( $notification_threshold ) ) {
					wp_clear_scheduled_hook( 'mainwp_domain_monitor_cron_alert' );
				}
			} elseif ( $website_id ) {
				$overwrite = isset( $_POST['mainwp-domain-monitor-overwrite-general-settings'] ) ? 1 : 0;

				$settings = array(
					'use_schedule'           => $use_schedule,
					'frequency'              => $frequency,
					'notification_threshold' => $notification_threshold,
				);

				$update                 = array();
				$update['settings']     = wp_json_encode( $settings );
				$update['overwrite']    = $overwrite;
				$update['site_id']      = $website_id;

				$out = MainWP_Domain_Monitor_DB::get_instance()->update_domain_monitor( $update );
			}
			return $output;
		}
	  return false;
	}

	/**
	 * Get Websites
	 *
	 * Gets speific child sites or groups through the 'mainwp_getsites' filter.
	 *
	 * @param array $site_ids  Child sites IDs.
	 * @param array $group_ids Groups IDs.
	 *
	 * @return array Child sites array.
	 */
	public static function get_db_sites( $site_ids, $group_ids = array() ) {
		if ( ! is_array( $site_ids ) ) {
			$site_ids = array();
		}

		if ( ! is_array( $group_ids ) ) {
			$group_ids = array();
		}

		if ( ! empty( $site_ids ) || ! empty( $group_ids ) ) {
			global $mainWPDomainMonitorExtensionActivator;
			return apply_filters( 'mainwp_getdbsites', $mainWPDomainMonitorExtensionActivator->get_child_file(), $mainWPDomainMonitorExtensionActivator->get_child_key(), $site_ids, $group_ids );
		}
		return false;
	}

	/**
	 * Get Websites
	 *
	 * Gets all child sites through the 'mainwp_getsites' filter.
	 *
	 * @param int $site_id  Child site ID.
	 * @param int $group_id Group ID.
	 *
	 * @return array Child sites array.
	 */
	public static function get_websites( $site_id = null, $group_id = null ) {
		global $mainWPDomainMonitorExtensionActivator;
		return apply_filters( 'mainwp_getsites', $mainWPDomainMonitorExtensionActivator->get_child_file(), $mainWPDomainMonitorExtensionActivator->get_child_key(), $site_id, $group_id );
	}

	/**
	 * Get DB Websites
	 *
	 * Gets all child sites through the 'mainwp_getdbsites' filter.
	 *
	 * @param array $site_ids  Child sites IDs.
	 * @param array $group_ids Groups IDs.
	 *
	 * @return array Child sites array.
	 */
	public static function get_db_websites( $site_ids = null, $group_ids = false ) {
		global $mainWPDomainMonitorExtensionActivator;
		return apply_filters( 'mainwp_getdbsites', $mainWPDomainMonitorExtensionActivator->get_child_file(), $mainWPDomainMonitorExtensionActivator->get_child_key(), $site_ids, $group_ids );
	}

	/**
	 * Widgets screen options.
	 *
	 * @param array $input Input.
	 *
	 * @return array $input Input.
	 */
	public function widgets_screen_options( $input ) {
		$input['advanced-domain-monitor-widget'] = __( 'Domain Monitor', 'mainwp-domain-monitor-extension' );
		return $input;
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
		if ( isset( $_GET['page'] ) && 'Extensions-Mainwp-Domain-Monitor-Extension' == $_GET['page'] ) {
			if ( ! isset( $_GET['tab'] ) || 'dashboard' == $_GET['tab'] ) {
				$input .= '<a class="ui button basic icon" onclick="mainwp_domain_monitor_sites_screen_options(); return false;" data-inverted="" data-position="bottom right" href="#" target="_blank" data-tooltip="' . esc_html__( 'Screen Options', 'mainwp' ) . '"><i class="cog icon"></i></a>';
			}
		}
		return $input;
	}

	/**
	 * Method handle_sites_screen_settings()
	 *
	 * Handle sites screen settings
	 */
	public function handle_sites_screen_settings() {
		if ( isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'DomainMonitorSitesScrOptions' ) ) {
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
				update_user_option( $user->ID, 'mainwp_settings_show_domain_monitor_sites_columns', $show_cols, true );
			}
		}
	}

	/**
	 * Initiate Cron Notification
	 *
	 * Initiates cron notifications.
	 */
	public static function init_cron_alert() {
		$notification_threshold = MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'notification_threshold' );
		if ( $notification_threshold ) {
			add_action( 'mainwp_domain_monitor_cron_alert', array( self::class, 'cron_domain_monitor_alert' ) );
			$useWPCron = ( false === get_option( 'mainwp_wp_cron' ) ) || ( 1 == get_option( 'mainwp_wp_cron' ) );
			if ( false == ( $schedule = wp_next_scheduled( 'mainwp_domain_monitor_cron_alert' ) ) ) {
				if ( $useWPCron ) {
					wp_schedule_event( time(), 'daily', 'mainwp_domain_monitor_cron_alert' );
				}
			} else {
				if ( ! $useWPCron ) {
					wp_clear_scheduled_hook( $schedule, 'mainwp_domain_monitor_cron_alert' );
				}
			}
		}
	}

	/**
	 * Domain Monitor Alert
	 *
	 * Sends emaail alerts when Domain is about to expire.
	 */
	public static function cron_domain_monitor_alert() {
		$email_settings = apply_filters( 'mainwp_notification_get_settings', array(), 'domain_monitor_notification_email' );

		if ( empty( $email_settings ) || ! empty( $email_settings['disable'] ) ) {
			return;
		}

		self::domain_monitor_trigger_email_notification( $email_settings );
	}

	/**
	 * Send Email Notification
	 *
	 * @param array $email_settings email settings.
	 *
	 *  Sends email alerts when domain is about to expire.
	 */
	public static function domain_monitor_trigger_email_notification( $email_settings ) {
		$notification_threshold = MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'notification_threshold' );
		$domains                = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'all' );

		if ( is_array( $domains ) ) {
			foreach ( $domains as $domain ) {
				$site_notification_threshold = $notification_threshold;

				if ( $domain->overwrite ) {
					$settings                    = json_decode( $domain->settings, true );
					$settings                    = is_array( $settings ) ? $settings : array();
					$site_notification_threshold = isset( $settings['notification_threshold'] ) ? $settings['notification_threshold'] : 0;
				}

				if ( ! empty( $site_notification_threshold ) ) {
					$local_time = MainWP_Domain_Monitor_Utility::get_timestamp();
					$last_alert = $domain->last_alert;
					$expires    = round( ( $domain->expiry_date - time() ) / ( 60 * 60 * 24 ) );
					if ( $expires < $site_notification_threshold ) {
						if ( $last_alert + 24 * 3600 < $local_time ) {
							$sent = self::send_alert_mail( $domain, $email_settings );
							if ( $sent && ! empty( $domain->site_id ) ) {
								$update = array(
									'site_id'    => $domain->site_id,
									'last_alert' => $local_time,
								);
								MainWP_Domain_Monitor_DB::get_instance()->update_domain_monitor( $update );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Send Email Alert
	 *
	 * Sends email notifications when domain is abou to expire.
	 *
	 * @param array  $domain         Domain data for a site.
	 * @param string $email_settings Email settings.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public static function send_alert_mail( $domain, $email_settings ) {

		if ( empty( $domain->site_id ) ) {
			return false;
		}

		$site_id = $domain->site_id;

		$website = self::get_websites( $site_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( ! $website ) {
			return;
		}

		$email = '';

		if ( ! empty( $email_settings['recipients'] ) ) {
			$email .= ',' . $email_settings['recipients']; // send to recipients, individual email settings or general email settings.
		}

		$email = trim( $email, ',' );

		if ( empty( $email ) ) {
			return false;
		}

		$args = array(
			'site_url'      => $website['url'],
			'site_name'     => $website['name'],
			'site_id'       => $site_id,
			'domain'        => $domain,
			'last_check'    => $domain->last_check,
			'heading'       => $email_settings['heading'],
		);

		$formated_content = apply_filters( 'mainwp_notification_get_template_content', '', 'emails/mainwp-domain-monitor-notification-email.php', $args );
		$content_type     = "Content-Type: text/html; charset=\"utf-8\"\r\n";
		$subject          = $email_settings['subject'];
		$sent             = apply_filters( 'mainwp_send_wp_mail', null, $email, $subject, $formated_content, $content_type );

		return $sent;
	}

	/**
	 * Cron Job Info
	 *
	 * Hooks the Domain Monitor cron job info to the Cron Schedules table.
	 */
	public function cron_job_info() {
		$next_run = MainWP_Domain_Monitor_Utility::format_timestamp( MainWP_Domain_Monitor_Utility::get_timestamp( wp_next_scheduled( 'mainwp_domain_monitor_action_cron_start' ) ) );
		$last_run = MainWP_Domain_Monitor_Utility::format_timestamp( MainWP_Domain_Monitor_Utility::get_timestamp( get_option( 'mainwp_domain_monitor_action_cron_start' ) ) );
		?>
		<tr>
			<td><?php echo __( 'Start domain lookup', 'mainwp-domain-monitor-extension' ); ?></td>
			<td><?php echo 'mainwp_domain_monitor_action_cron_start'; ?></td>
			<td><?php echo __( 'Once every 5 minutes', 'mainwp-domain-monitor-extension' ); ?></td>
			<td><?php echo $last_run; ?></td>
			<td><?php echo $next_run; ?></td>
		</tr>
		<?php
	}

	/**
	 * REST API Domain Profiles
	 *
	 * Handles the REST API sites domain profiles.
	 *
	 * @param array $websites Child sites.
	 *
	 * @return array $data Reports data.
	 */
	public static function handle_rest_api_sites_domain_profiles( $websites ) {
		$data = array();
		if ( ! empty( $websites ) ) {
			foreach ( $websites as $website ) {
				$website_id = $website['id'];

				$result = array();

				$domain_data = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $website_id );

				$result['id']   = $website['id'];
				$result['name'] = $website['name'];
				$result['url']  = $website['url'];

				if ( ! empty( $domain_data ) ) {
					$result['domain_name']                   = isset( $domain_data->domain_name ) ? $domain_data->domain_name : '';
					$result['registrar']                     = isset( $domain_data->registrar ) ? $domain_data->registrar : '';
					$result['registrar_url']                 = isset( $domain_data->registrar_url ) ? $domain_data->registrar_url : '';
					$result['registrar_iana_id']             = isset( $domain_data->registrar_iana_id ) ? $domain_data->registrar_iana_id : '';
					$result['registrar_abuse_contact_email'] = isset( $domain_data->registrar_abuse_contact_email ) ? $domain_data->registrar_abuse_contact_email : '';
					$result['registrar_abuse_contact_phone'] = isset( $domain_data->registrar_abuse_contact_phone ) ? $domain_data->registrar_abuse_contact_phone : '';
					$result['registrar_whois_server']        = isset( $domain_data->registrar_whois_server ) ? $domain_data->registrar_whois_server : '';
					$result['registry_domain_id']            = isset( $domain_data->registry_domain_id ) ? $domain_data->registry_domain_id : '';
					$result['updated_date']                  = isset( $domain_data->updated_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->updated_date ) ) : '';
					$result['creation_date']                 = isset( $domain_data->creation_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->creation_date ) ) : '';
					$result['expiry_date']                   = isset( $domain_data->expiry_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->expiry_date ) ) : '';

					$data['resutls'][ $website['id'] ] = $result;
				}
			}
		} else {
			$data['error'] = __( 'Domain info not found.', 'mainwp-domain-monitor-extension' );
		}
		return $data;
	}

	/**
	 * WP CLI domain profiles
	 *
	 * Handles the WP CLI domain profiles.
	 *
	 * @param array $website Child site.
	 */
	public static function handle_wp_cli_sites_domain_profiles( $website ) {

		$website_id = $website['id'];

		$domain_data = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $website_id );

		\WP_CLI::line( ' -> ' . $website['name'] . ' (' . $website['url'] . ')' );

		if ( ! empty( $domain_data ) ) {

			$domain_name                   = isset( $domain_data->domain_name ) ? $domain_data->domain_name : '';
			$registrar                     = isset( $domain_data->registrar ) ? $domain_data->registrar : '';
			$registrar_url                 = isset( $domain_data->registrar_url ) ? $domain_data->registrar_url : '';
			$registrar_iana_id             = isset( $domain_data->registrar_iana_id ) ? $domain_data->registrar_iana_id : '';
			$registrar_abuse_contact_email = isset( $domain_data->registrar_abuse_contact_email ) ? $domain_data->registrar_abuse_contact_email : '';
			$registrar_abuse_contact_phone = isset( $domain_data->registrar_abuse_contact_phone ) ? $domain_data->registrar_abuse_contact_phone : '';
			$registrar_whois_server        = isset( $domain_data->registrar_whois_server ) ? $domain_data->registrar_whois_server : '';
			$registry_domain_id            = isset( $domain_data->registry_domain_id ) ? $domain_data->registry_domain_id : '';
			$updated_date                  = isset( $domain_data->updated_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->updated_date ) ) : '';
			$creation_date                 = isset( $domain_data->creation_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->creation_date ) ) : '';
			$expiry_date                   = isset( $domain_data->expiry_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->expiry_date ) ) : '';

			\WP_CLI::line( __( 'Domain Name: ', 'mainwp-domain-monitor-extension' ) . $domain_name );
			\WP_CLI::line( __( 'Registrar: ', 'mainwp-domain-monitor-extension' ) . $registrar );
			\WP_CLI::line( __( 'Registrar URL: ', 'mainwp-domain-monitor-extension' ) . $registrar_url );
			\WP_CLI::line( __( 'Registrar IANA ID: ', 'mainwp-domain-monitor-extension' ) . $registrar_iana_id );
			\WP_CLI::line( __( 'Registrar Abuse Contact Email: ', 'mainwp-domain-monitor-extension' ) . $registrar_abuse_contact_email );
			\WP_CLI::line( __( 'Registrar Abuse Contact Phone: ', 'mainwp-domain-monitor-extension' ) . $registrar_abuse_contact_phone );
			\WP_CLI::line( __( 'Regisrar WHOIS Server: ', 'mainwp-domain-monitor-extension' ) . $registrar_whois_server );
			\WP_CLI::line( __( 'Registry Domain ID: ', 'mainwp-domain-monitor-extension' ) . $registry_domain_id );
			\WP_CLI::line( __( 'Domain Updated Date: ', 'mainwp-domain-monitor-extension' ) . $updated_date );
			\WP_CLI::line( __( 'Domain Creation Date: ', 'mainwp-domain-monitor-extension' ) . $creation_date );
			\WP_CLI::line( __( 'Domain Expiry Date: ', 'mainwp-domain-monitor-extension' ) . $expiry_date );

		} else {
			\WP_CLI::line( __( 'Domain data not found.', 'mainwp-domain-monitor-extension' ) );
		}
	}

	/**
	 * Hooks the section help content to the Help Sidebar element.
	 */
	public static function mainwp_help_content() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Domain-Monitor-Extension' === $_GET['page'] ) ) {
			?>
			<p><?php esc_html_e( 'If you need help with the Domain Monitor extension, please review following help documents', 'mainwp' ); ?></p>
			<div class="ui relaxed bulleted list">
				<div class="item"><a href="https://kb.mainwp.com/docs/mainwp-domain-monitor-extension/" target="_blank">How to use the Extension</a></div>
				<?php
				/**
				 * Action: mainwp_domain_monitor_help_item
				 *
				 * Fires at the bottom of the help articles list in the Help sidebar on the Themes page.
				 *
				 * Suggested HTML markup:
				 *
				 * <div class="item"><a href="Your custom URL">Your custom text</a></div>
				 *
				 * @since 4.1
				 */
				do_action( 'mainwp_domain_monitor_help_item' );
				?>
			</div>
			<?php
		}
	}
}
