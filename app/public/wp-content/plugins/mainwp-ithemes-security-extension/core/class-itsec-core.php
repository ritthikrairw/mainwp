<?php
/**
 * iThemes Security Core.
 *
 * Core class for iThemes Security sets up globals and other items and dispatches modules.
 *
 * @package iThemes_Security
 *
 * @since   4.0
 *
 * @global array  $mainwp_itsec_globals Global variables for use throughout iThemes Security.
 * @global object $mainwp_itsec_files   iThemes Security file writer.
 * @global object $mainwp_itsec_logger  iThemes Security logging class.
 * @global object $mainwp_itsec_lockout Class for handling lockouts.
 */
if ( ! class_exists( 'MainWP_ITSEC_Core' ) ) {

	final class MainWP_ITSEC_Core {

		private
			$one_click,
			$notifications;

		public $available_pages;

		private static $instance = null;

		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		function __construct() {

		}

		public function init( $plugin_file, $plugin_name ) {
			global $mainwp_itsec_globals, $mainwp_itsec_logger, $mainwp_itsec_lockout, $mainwp_itheme_site_data_values;

			$this->plugin_build       = 4041; // used to trigger updates
			$this->plugin_file        = $plugin_file;
			$this->plugin_dir         = dirname( $plugin_file ) . '/';
			$this->current_time       = current_time( 'timestamp' );
			$this->current_time_gmt   = time();
			$this->notices_loaded     = false;
			$this->doing_data_upgrade = false;

			$this->interactive = false; // Used to distinguish between a user modifying settings and the API modifying
										// settings (such as from Sync requests).
			$mainwp_itsec_globals = array(
				'plugin_name'       => sanitize_text_field( $plugin_name ),
				'plugin_dir'        => $this->plugin_dir,
				'current_time'      => $this->current_time,
				'current_time_gmt'  => $this->current_time_gmt,
				'mainwp_itheme_url' => MainWP_IThemes_Security::get_itheme_url(),
			);

			$mainwp_itsec_globals['itheme_module_settings'] = array(
				'global',
				//'away-mode',
				'backup',
				'hide-backend',
				'ipcheck',
				'ban-users',
				'brute-force',
				'file-change',
				//'404-detection',
				'network-brute-force',
				'ssl',
				// 'strong-passwords',
				//'password-requirements',
				'system-tweaks',
				'wordpress-tweaks',
				//'multisite-tweaks',
				'notification-center',
				// 'admin-user', // not saved values
				// 'salts',
				// 'content-directory',
				// 'database-prefix' // not saved values				
			);

			$current_site_id = MainWP_IThemes_Security::get_manage_site_id();
			$itheme_settings = MainWP_IThemes_Security::get_itheme_settings( $current_site_id );
			if ( $current_site_id ) {
				$result = MainWP_IThemes_Security_DB::get_instance()->get_site_setting_by( 'site_id', $current_site_id );
				if ( $result ) {
					$mainwp_itsec_globals['build_number'] = isset( $itheme_settings['build_number'] ) ? $itheme_settings['build_number'] : 0;
				}

				if ( null === $mainwp_itheme_site_data_values ) {
					if ( $result ) {
						$mainwp_itheme_site_data_values = unserialize( base64_decode( $result->site_status ) );
					} else {
						$mainwp_itheme_site_data_values = array();
					}
				}
			} else {
				$mainwp_itsec_globals['build_number'] = isset( $itheme_settings['build_number'] ) ? $itheme_settings['build_number'] : 0;
			}

			if ( ! is_array( $itheme_settings ) ) {
				$itheme_settings = array();
			}
			$mainwp_itsec_globals['current_site_id'] = $current_site_id;

			require_once $this->plugin_dir . 'core/class-itsec-modules.php';
			require_once $this->plugin_dir . 'core/lib/Module_Config.php';
			require_once $this->plugin_dir . 'core/class-itsec-lib.php';

			add_action( 'mainwp-itsec-register-modules', array( $this, 'register_modules' ) );
			MainWP_ITSEC_Modules::init_modules();

			require_once $this->plugin_dir . 'core/lib/class-itsec-lib-password-requirements.php';
			require_once $this->plugin_dir . 'core/class-itsec-logger.php';
			require_once $this->plugin_dir . 'core/class-itsec-lockout.php';
			require_once $this->plugin_dir . 'core/class-itsec-files.php';
			require_once $this->plugin_dir . 'core/class-itsec-notify.php';
			require_once $this->plugin_dir . 'core/class-itsec-response.php';
			require_once $this->plugin_dir . 'core/class-itsec-others-notifications.php';

			$this->itsec_files    = MainWP_ITSEC_Files::get_instance();
			$this->itsec_notify   = new MainWP_ITSEC_Notify();
			$mainwp_itsec_logger  = new MainWP_ITSEC_Logger();
			$mainwp_itsec_lockout = new MainWP_ITSEC_Lockout( $this );

			MainWP_ITSEC_Others_Notifications::get_instance();

			// Determine if we need to run upgrade scripts
			$plugin_data = get_site_option( 'mainwp_itsec_data' );

			if ( false === $plugin_data ) {
				$plugin_data = $this->save_plugin_data();
			}

			if ( isset( $mainwp_itsec_globals['build_number'] ) && $mainwp_itsec_globals['build_number'] !== $this->plugin_build ) {
				// We need to upgrade the data. Delay init of the rest of the plugin until the upgrade is complete.
				$this->doing_data_upgrade = true;
				MainWP_IThemes_Security::update_build_number( $this->plugin_build, $current_site_id );
				$mainwp_itsec_globals['current_itheme_settings'] = $itheme_settings;
				// Run the actions early so that the rest of the code can still use the plugins_loaded hook.
				add_action( 'plugins_loaded', array( $this, 'continue_init' ), -90 );
			} else {
				if ( $current_site_id && $this->plugin_build == 4041 ) {
					if ( isset( $itheme_settings['itsec_tweaks'] ) ) {
						MainWP_IThemes_Security::unset_setting_field( array( 'itsec_tweaks' ), $current_site_id );
					}
				}

				$this->continue_init();
			}
		}

		public function continue_init() {
			MainWP_ITSEC_Modules::run_active_modules();

			if ( is_admin() ) {
				require_once $this->plugin_dir . 'core/admin-pages/init.php';

				require_once $this->plugin_dir . 'core/class-itsec-dashboard-admin.php';
				new MainWP_ITSEC_Dashboard_Admin( $this );

			}

			register_activation_hook( $this->plugin_file, array( 'MainWP_ITSEC_Core', 'on_activate' ) );
			register_deactivation_hook( $this->plugin_file, array( 'MainWP_ITSEC_Core', 'on_deactivate' ) );
			register_uninstall_hook( $this->plugin_file, array( 'MainWP_ITSEC_Core', 'on_uninstall' ) );

		}


		/**
		 * Prints out all settings sections added to a particular settings page.
		 *
		 * adapted from core function for better styling within meta_box.
		 *
		 * @since 4.0
		 *
		 * @param string  $page       The slug name of the page whos settings sections you want to output
		 * @param string  $section    the section to show
		 * @param boolean $show_title Whether or not the title of the section should display: default true.
		 *
		 * @return void
		 */
		public function do_settings_section( $page, $section, $show_title = true ) {

			global $wp_settings_sections, $wp_settings_fields;

			if ( ! isset( $wp_settings_sections ) || ! isset( $wp_settings_sections[ $page ] ) || ! isset( $wp_settings_sections[ $page ][ $section ] ) ) {
				return;
			}

			$section = $wp_settings_sections[ $page ][ $section ];

			if ( $section['title'] && $show_title === true ) {
				echo "<h4>{$section['title']}</h4>\n";
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				return;
			}

			echo '<table class="form-table" id="' . $section['id'] . '">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>';

		}

		/**
		 * Calls upgrade script for older versions (pre 4.x).
		 *
		 * @since 4.0
		 *
		 * @return void
		 */
		public function do_upgrade() {

		}

		/**
		 * Call activation script
		 *
		 * @since 4.5
		 *
		 * @return void
		 */
		public static function on_activate() {

			global $mainwp_itsec_globals;

			// require plugin setup information
			if ( ! class_exists( 'MainWP_ITSEC_Setup' ) ) {
				require_once trailingslashit( $mainwp_itsec_globals['plugin_dir'] ) . 'core/class-itsec-setup.php';
			}

			MainWP_ITSEC_Setup::on_activate();

		}

		/**
		 * Call deactivation script
		 *
		 * @since 4.5
		 *
		 * @return void
		 */
		public static function on_deactivate() {

			global $mainwp_itsec_globals;

			// require plugin setup information
			if ( ! class_exists( 'MainWP_ITSEC_Setup' ) ) {
				require_once trailingslashit( $mainwp_itsec_globals['plugin_dir'] ) . 'core/class-itsec-setup.php';
			}

			MainWP_ITSEC_Setup::on_deactivate();

		}

		/**
		 * Call uninstall script
		 *
		 * @since 4.5
		 *
		 * @return void
		 */
		public static function on_uninstall() {

			global $mainwp_itsec_globals;

			// require plugin setup information
			if ( ! class_exists( 'MainWP_ITSEC_Setup' ) ) {
				require_once trailingslashit( $mainwp_itsec_globals['plugin_dir'] ) . 'core/class-itsec-setup.php';
			}

			MainWP_ITSEC_Setup::on_uninstall();

		}


		/**
		 * Register modules that will use the lockout service
		 *
		 * @return void
		 */
		public function register_modules() {

			$path = dirname( __FILE__ );
			// include_once "$path/modules/security-check/init.php";
			// include_once "$path/modules/global/init.php";
			MainWP_ITSEC_Modules::register_module( 'security-check', "$path/modules/security-check", 'always-active' );
			MainWP_ITSEC_Modules::register_module( 'global', "$path/modules/global", 'always-active' );
			
			MainWP_ITSEC_Modules::register_module( 'notification-center', "$path/modules/notification-center", 'always-active' );
			
			// include_once "$path/modules/404-detection/init.php";
			// include_once "$path/modules/away-mode/init.php";
			// include_once "$path/modules/ban-users/init.php";
			// include_once "$path/modules/brute-force/init.php";
			// include_once "$path/modules/backup/init.php";
			// include_once "$path/modules/file-change/init.php";

			//MainWP_ITSEC_Modules::register_module( '404-detection', "$path/modules/404-detection" );
			//MainWP_ITSEC_Modules::register_module( 'away-mode', "$path/modules/away-mode" );
			MainWP_ITSEC_Modules::register_module( 'ban-users', "$path/modules/ban-users", 'default-active' );
			MainWP_ITSEC_Modules::register_module( 'brute-force', "$path/modules/brute-force", 'default-active' );
			MainWP_ITSEC_Modules::register_module( 'backup', "$path/modules/backup", 'default-active' );
			MainWP_ITSEC_Modules::register_module( 'file-change', "$path/modules/file-change" );
			
			if ( MainWP_IThemes_Security::is_manage_site() ) {
				// include_once "$path/modules/file-permissions/init.php";
				MainWP_ITSEC_Modules::register_module( 'file-permissions', "$path/modules/file-permissions", 'always-active' );
			
			}
			// include_once "$path/modules/hide-backend/init.php";
			// include_once "$path/modules/ipcheck/init.php";
			// include_once "$path/modules/ssl/init.php";

			MainWP_ITSEC_Modules::register_module( 'hide-backend', "$path/modules/hide-backend", 'always-active' );
			MainWP_ITSEC_Modules::register_module( 'network-brute-force', "$path/modules/ipcheck", 'default-active' );
			MainWP_ITSEC_Modules::register_module( 'ssl', "$path/modules/ssl" );			
			MainWP_ITSEC_Modules::register_module( 'password-requirements', "$path/modules/password-requirements", 'always-active' );
			
			// include_once "$path/modules/strong-passwords/init.php";
			// include_once "$path/modules/system-tweaks/init.php";
			// include_once "$path/modules/wordpress-tweaks/init.php";
			// include_once "$path/modules/multisite-tweaks/init.php";
			// include_once "$path/modules/admin-user/init.php";
			// include_once "$path/modules/salts/init.php";
			// include_once "$path/modules/content-directory/init.php";
			// include_once "$path/modules/database-prefix/init.php";
			
			MainWP_ITSEC_Modules::register_module( 'strong-passwords', "$path/modules/strong-passwords", 'always-active' );
			MainWP_ITSEC_Modules::register_module( 'system-tweaks', "$path/modules/system-tweaks" );
			MainWP_ITSEC_Modules::register_module( 'wordpress-tweaks', "$path/modules/wordpress-tweaks", 'default-active' );

			//MainWP_ITSEC_Modules::register_module( 'multisite-tweaks', "$path/modules/multisite-tweaks" );
			MainWP_ITSEC_Modules::register_module( 'admin-user', "$path/modules/admin-user", 'always-active' );
			MainWP_ITSEC_Modules::register_module( 'wordpress-salts', "$path/modules/salts", 'always-active' );
			//MainWP_ITSEC_Modules::register_module( 'content-directory', "$path/modules/content-directory", 'always-active' );
			MainWP_ITSEC_Modules::register_module( 'database-prefix', "$path/modules/database-prefix", 'always-active' );
			MainWP_ITSEC_Modules::register_module( 'two-factor', "$path/modules/two-factor", 'always-active' );

			if ( MainWP_IThemes_Security::is_manage_site() ) {
				// include_once "$path/modules/file-writing/init.php";
				MainWP_ITSEC_Modules::register_module( 'file-writing', "$path/modules/file-writing", 'always-active' );
			}
			//MainWP_ITSEC_Modules::register_module( 'pro-module-upsells', "$path/modules/pro", 'always-active' );
			MainWP_ITSEC_Modules::register_module( 'security-check-pro', "$path/modules/security-check-pro" );
		}

		/**
		 * Saves general plugin data to determine global items.
		 *
		 * Sets up general plugin data such as build, and others.
		 *
		 * @since 4.0
		 *
		 * @return array plugin data
		 */
		public function save_plugin_data() {

			global $mainwp_itsec_globals;

			$save_data = false; // flag to avoid saving data if we don't have to

			$plugin_data = get_site_option( 'mainwp_itsec_data' );

			// Update the build number if we need to
			if ( isset( $mainwp_itsec_globals['plugin_build'] ) && ( ! isset( $plugin_data['build'] ) || ( isset( $plugin_data['build'] ) && $plugin_data['build'] != $mainwp_itsec_globals['plugin_build'] ) ) ) {
				$plugin_data['build'] = $mainwp_itsec_globals['plugin_build'];
				$save_data            = true;
			}

			// update the activated time if we need to in order to tell when the plugin was installed
			if ( ! isset( $plugin_data['activation_timestamp'] ) ) {
				$plugin_data['activation_timestamp'] = $mainwp_itsec_globals['current_time_gmt'];
				$save_data                           = true;
			}

			// update the activated time if we need to in order to tell when the plugin was installed
			if ( ! isset( $plugin_data['already_supported'] ) ) {
				$plugin_data['already_supported'] = false;
				$save_data                        = true;
			}

			// update the activated time if we need to in order to tell when the plugin was installed
			if ( ! isset( $plugin_data['setup_completed'] ) ) {
				$plugin_data['setup_completed'] = false;
				$save_data                      = true;
			}

			// update the options table if we have to
			if ( $save_data === true ) {
				update_site_option( 'mainwp_itsec_data', $plugin_data );
			}

			return $plugin_data;

		}

		/**
		 * Handles the building of admin menus and calls required functions to render admin pages.
		 *
		 * @since 4.0
		 *
		 * @return void
		 */
		public function setup_primary_admin() {

			global $mainwp_itsec_globals;

		}

		/**
		 * Setup and call admin messages.
		 *
		 * Sets up messages and registers actions for WordPress admin messages.
		 *
		 * @since 4.0
		 *
		 * @param object $messages WordPress error object or string of message to display
		 *
		 * @return void
		 */
		public function show_network_admin_notice( $errors ) {

		}

		/**
		 * Sorts pages from lowest priority to highest.
		 *
		 * @since 4.0
		 *
		 * @param array $a page
		 * @param array $b page
		 *
		 * @return int 1 if a is a lower priority, -1 if b is a lower priority, 0 if equal
		 */
		public function sort_pages( $a, $b ) {

			if ( $a['priority'] == $b['priority'] ) {
				return 0;
			}

			return ( $a['priority'] > $b['priority'] ? 1 : - 1 );

		}

		/**
		 * Sorts tooltips from highest priority to lowest.
		 *
		 * @since 4.0
		 *
		 * @param array $a tooltip
		 * @param array $b tooltip
		 *
		 * @return int 1 if a is a lower priority, -1 if b is a lower priority, 0 if equal
		 */
		public function sort_tooltips( $a, $b ) {

			if ( $a['priority'] == $b['priority'] ) {
				return 0;
			}

			return ( $a['priority'] < $b['priority'] ? 1 : - 1 );

		}

		/**
		 * Performs actions for tooltip function.
		 *
		 * @since 4.0
		 *
		 * return void
		 */
		public function tooltip_ajax() {

			foreach ( $this->one_click as $setting => $option_pair ) {

				$saved_setting = get_site_option( $setting );

				foreach ( $option_pair as $option ) {
					$saved_setting[ $option['option'] ] = $option['value'];
				}

				update_site_option( $setting, $saved_setting );

			}

			echo 'true';

		}

		public static function get_required_cap() {
			return apply_filters( 'mainwp_itsec_cap_required', is_multisite() ? 'manage_network_options' : 'manage_options' );
		}

		public static function current_user_can_manage() {
			return current_user_can( self::get_required_cap() );
		}

		public static function get_itsec_files() {
			$self = self::get_instance();
			return $self->itsec_files;
		}

		public static function get_itsec_notify() {
			$self = self::get_instance();
			return $self->itsec_notify;
		}

		/**
		 * Set the notification center instance.
		 *
		 * @param ITSEC_Notification_Center $center
		 */
		public static function set_notification_center( MainWP_ITSEC_Notification_Center $center ) {
			self::get_instance()->notifications = $center;
		}

		/**
		 * Get the notification center instance.
		 *
		 * @return ITSEC_Notification_Center
		 */
		public static function get_notification_center() {
			return self::get_instance()->notifications;
		}

		public static function get_itsec_sync() {
			$self = self::get_instance();

			if ( ! isset( $self->itsec_sync ) ) {
				require_once dirname( __FILE__ ) . '/class-itsec-sync.php';
				$self->itsec_sync = new ITSEC_Sync();
			}

			return $self->itsec_sync;
		}


		public static function get_plugin_file() {
			$self = self::get_instance();
			return $self->plugin_file;
		}

		public static function set_plugin_file( $plugin_file ) {
			$self              = self::get_instance();
			$self->plugin_file = $plugin_file;
			$self->plugin_dir  = dirname( $plugin_file ) . '/';
		}

		public static function get_plugin_build() {
			$self = self::get_instance();
			return $self->plugin_build;
		}

		public static function get_plugin_dir() {
			$self = self::get_instance();
			return $self->plugin_dir;
		}

		public static function get_core_dir() {
			return self::get_plugin_dir() . 'core/';
		}

		public static function is_pro() {
			return is_dir( self::get_plugin_dir() . 'pro' );
		}


		public static function get_current_time() {
			$self = self::get_instance();
			return $self->current_time;
		}

		public static function get_current_time_gmt() {
			$self = self::get_instance();
			return $self->current_time_gmt;
		}

		public static function get_time_offset() {
			$self = self::get_instance();
			return $self->current_time - $self->current_time_gmt;
		}

		public static function get_settings_page_url( $full_url = true ) {
			$url = 'admin.php?page=Extensions-Mainwp-Ithemes-Security-Extension';
			if ( MainWP_IThemes_Security::is_manage_site() ) {
				$site_id = MainWP_IThemes_Security::get_manage_site_id();
				$url     = 'admin.php?page=ManageSitesiThemes&id=' . $site_id;
			}
			if ( $full_url ) {
				$url = admin_url( $url );
			}
			return $url;
		}

		public static function get_logs_page_url( $filter = false ) {
			$url = network_admin_url( 'admin.php?page=itsec-logs' );

			if ( ! empty( $filter ) ) {
				$url = add_query_arg( array( 'filter' => $filter ), $url );
			}

			return $url;
		}

		public static function get_backup_creation_page_url() {
			global $mainwp_itsec_globals;
			$url = 'admin.php?page=' . $mainwp_itsec_globals['mainwp_itheme_url'] . '&tab=backup';
			return $url;
		}

		public static function get_settings_module_route( $module ) {
			$path   = '/settings/configure/';
			$config = MainWP_ITSEC_Modules::get_config( $module );

			if ( $config ) {
				$settings = MainWP_ITSEC_Modules::get_settings_obj( $module );

				if ( ! $settings || ! $settings->has_interactive_settings() ) {
					return "/settings/modules/{$config->get_type()}#{$config->get_id()}";
				}

				if ( $config->get_type() !== 'recommended' ) {
					$path .= $config->get_type() . '/';
				}

				$path .= $config->get_id();
			}

			return $path;
		}

		public static function set_interactive( $interactive ) {
			$self              = self::get_instance();
			$self->interactive = (bool) $interactive;
		}

		public static function is_interactive() {
			$self = self::get_instance();
			return $self->interactive;
		}

		public static function is_iwp_call() {
			$self = self::get_instance();

			if ( isset( $self->is_iwp_call ) ) {
				return $self->is_iwp_call;
			}

			$self->is_iwp_call = false;

			if ( false && ! ITSEC_Modules::get_setting( 'global', 'infinitewp_compatibility' ) ) {
				return false;
			}

			$HTTP_RAW_POST_DATA = @file_get_contents( 'php://input' );

			if ( ! empty( $HTTP_RAW_POST_DATA ) ) {
				$data = base64_decode( $HTTP_RAW_POST_DATA );

				if ( false !== strpos( $data, 's:10:"iwp_action";' ) ) {
					$self->is_iwp_call = true;
				}
			}

			return $self->is_iwp_call;
		}

		public static function get_wp_upload_dir() {
			$self = self::get_instance();

			if ( isset( $self->wp_upload_dir ) ) {
				return $self->wp_upload_dir;
			}

			$wp_upload_dir = get_site_transient( 'itsec_wp_upload_dir' );

			if ( ! is_array( $wp_upload_dir ) || ! isset( $wp_upload_dir['basedir'] ) || ! is_dir( $wp_upload_dir['basedir'] ) ) {
				if ( is_multisite() ) {
					switch_to_blog( 1 );
					$wp_upload_dir = wp_upload_dir();
					restore_current_blog();
				} else {
					$wp_upload_dir = wp_upload_dir();
				}

				set_site_transient( 'itsec_wp_upload_dir', $wp_upload_dir, DAY_IN_SECONDS );
			}

			$self->wp_upload_dir = $wp_upload_dir;

			return $self->wp_upload_dir;
		}

		public static function update_wp_upload_dir( $old_dir, $new_dir ) {
			$self = self::get_instance();

			// Prime caches.
			self::get_wp_upload_dir();

			$self->wp_upload_dir = str_replace( $old_dir, $new_dir, $self->wp_upload_dir );

			// Ensure that the transient will be regenerated on the next page load.
			delete_site_transient( 'itsec_wp_upload_dir' );
		}

		public static function get_storage_dir( $dir = '' ) {
			$self = self::get_instance();

			require_once self::get_core_dir() . '/lib/class-itsec-lib-directory.php';

			if ( ! isset( $self->storage_dir ) ) {
				$wp_upload_dir = self::get_wp_upload_dir();

				$self->storage_dir = $wp_upload_dir['basedir'] . '/ithemes-security/';
			}

			$dir = $self->storage_dir . $dir;
			$dir = rtrim( $dir, '/' );

			MainWP_ITSEC_Lib_Directory::create( $dir );

			return $dir;
		}

		public static function doing_data_upgrade() {
			$self = self::get_instance();

			return $self->doing_data_upgrade;
		}


	}

}
