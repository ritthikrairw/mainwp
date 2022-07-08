<?php

/**
 * Handles lockouts for modules and core
 *
 * @package iThemes-Security
 * @since   4.0
 */
class MainWP_ITSEC_Lockout {

	private
		$core,
		$lockout_modules;

	function __construct( $core ) {

		$this->core            = $core;
		$this->lockout_modules = array(); // array to hold information on modules using this feature

		// Register all plugin modules
		add_action( 'plugins_loaded', array( $this, 'register_modules' ) );

		// Register Logger
		add_filter( 'mainwp_itsec_logger_modules', array( $this, 'register_logger' ) );

		// Register Sync
		add_filter( 'mainwp_getmetaboxes', array( $this, 'getMetabox' ) );
		add_filter( 'mainwp_itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'mainwp_itsec_lockout_notification_strings', array( $this, 'notification_strings' ) );

	}


	public function get_temp_whitelist() {
		$whitelist = get_site_option( 'itsec_temp_whitelist_ip', false );

		if ( ! is_array( $whitelist ) ) {
			$whitelist = array();
		} elseif ( isset( $whitelist['ip'] ) ) {
			// Update old format
			$whitelist = array(
				$whitelist['ip'] => $whitelist['exp'] - MainWP_ITSEC_Core::get_time_offset(),
			);
		} else {
			return $whitelist;
		}

		update_site_option( 'itsec_temp_whitelist_ip', $whitelist );

		return $whitelist;
	}

	public function is_visitor_temp_whitelisted() {
		global $mainwp_itsec_globals;

		$whitelist = $this->get_temp_whitelist();
		$ip        = MainWP_ITSEC_Lib::get_ip();

		if ( isset( $whitelist[ $ip ] ) && $whitelist[ $ip ] > $mainwp_itsec_globals['current_time'] ) {
			return true;
		}

		return false;
	}


	public function getMetabox( $metaboxes ) {
		if ( ! isset( $_GET['page'] ) || ( $_GET['page'] != 'managesites' ) ) {
			return $metaboxes;
		}

			global $mainWPIThemesSecurityExtensionActivator;
		if ( ! is_array( $metaboxes ) ) {
			$metaboxes = array();
		}
			$metaboxes[] = array(
				'plugin'        => $mainWPIThemesSecurityExtensionActivator->get_child_file(),
				'key'           => $mainWPIThemesSecurityExtensionActivator->get_child_key(),
				'metabox_title' => 'Active Lockouts',
				'callback'      => array( &$this, 'lockout_metabox' ),
			);
			return $metaboxes;
	}

		/**
		 * Active lockouts table and form for dashboard.
		 *
		 * @Since 4.0
		 *
		 * @return void
		 */
	public function lockout_metabox() {

		global $mainwp_itsec_globals;

		$site_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;

		if ( empty( $site_id ) ) {
			return;
		}

		$site_variables = MainWP_IThemes_Security_DB::get_instance()->get_status_fields_by( 'site_id', $site_id );

		$scan_info  = is_array( $site_variables ) && isset( $site_variables['scan_info'] ) ? $site_variables['scan_info'] : '';
		$count_bans = is_array( $site_variables ) && isset( $site_variables['count_bans'] ) ? $site_variables['count_bans'] : 0;

		?>
	<span id="mainwp_itheme_managesites_site_id" site-id="<?php echo intval( $site_id ); ?>"></span>

		<?php wp_nonce_field( 'itsec_release_lockout', 'wp_nonce' ); ?>

		<input type="hidden" name="itsec_release_lockout" value="true"/>
		<?php
		// get locked out hosts and users from database
		$host_locks     = isset( $site_variables['lockouts_host'] ) ? $site_variables['lockouts_host'] : array(); // $this->get_lockouts( 'host', true );
		$user_locks     = isset( $site_variables['lockouts_user'] ) ? $site_variables['lockouts_user'] : array(); // $this->get_lockouts( 'user', true );
		$username_locks = isset( $site_variables['lockouts_username'] ) ? $site_variables['lockouts_username'] : array(); // $this->get_lockouts( 'username', true );
		?>

		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
				<?php _e( 'iThemes Security Lockouts', 'l10n-mainwp-ithemes-security-extension' ); ?>
				<div class="sub header"><?php _e( 'See the site active lockouts', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned"></div>
		</div>

	<table class="form-table">

	
	<tr valign="top">
		  <th scope="row" class="settinglabel"><?php _e( 'Status', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
		  <td class="settingfield">
			<?php echo MainWP_IThemes_Security_Plugin::render_status( $scan_info, false ); ?>
		  </td>
		</tr>

		<tr valign="top">
		  <th scope="row" class="settinglabel"><?php _e( 'Banned users', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
		  <td class="settingfield">
		  <?php echo intval( $count_bans ); ?>
		  </td>
		</tr>		

	  <tr valign="top">
		<th scope="row" class="settinglabel"><?php _e( 'Locked out hosts', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
		<td class="settingfield">
		  <?php if ( sizeof( $host_locks ) > 0 ) { ?>
		  <ul>
				<?php foreach ( $host_locks as $host ) { ?>
			  <li style="list-style: none;">
							<input type="checkbox" name="lo_<?php echo $host['lockout_id']; ?>" id="lo_<?php echo $host['lockout_id']; ?>" value="<?php echo $host['lockout_id']; ?>"/>
			  <label for="lo_<?php echo $host['lockout_id']; ?>"><strong><?php echo filter_var( $host['lockout_host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ); ?></strong> - <?php _e( 'Expires in', 'l10n-mainwp-ithemes-security-extension' ); ?><em> <?php echo human_time_diff( $mainwp_itsec_globals['current_time_gmt'], strtotime( $host['lockout_expire_gmt'] ) ); ?></em></label>
			  </li>
			<?php } ?>
		  </ul>
		  <?php } else { // no host is locked out ?>
			  <?php _e( 'Currently no hosts are locked out of this website.', 'l10n-mainwp-ithemes-security-extension' ); ?>
			<?php } ?>
		</td>
	  </tr>

	  <tr valign="top">
		<th scope="row" class="settinglabel"><?php _e( 'Locked out users', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
		  <td class="settingfield">
			<?php if ( sizeof( $user_locks ) > 0 ) { ?>
			<ul>
				<?php foreach ( $user_locks as $user ) { ?>
					<?php $userdata = get_userdata( $user['lockout_user'] ); ?>
				<li style="list-style: none;">
									<input type="checkbox" name="lo_<?php echo $user['lockout_id']; ?>" id="lo_<?php echo $user['lockout_id']; ?>" value="<?php echo $user['lockout_id']; ?>"/>
				  <label for="lo_<?php echo $user['lockout_id']; ?>"><strong><?php echo $userdata->user_login; ?></strong> - <?php _e( 'Expires in', 'l10n-mainwp-ithemes-security-extension' ); ?><em> <?php echo human_time_diff( $mainwp_itsec_globals['current_time_gmt'], strtotime( $user['lockout_expire_gmt'] ) ); ?></em></label>
				</li>
			  <?php } ?>
			  </ul>
			<?php } else { // no user is locked out ?>
				<?php _e( 'Currently no users are locked out of this website.', 'l10n-mainwp-ithemes-security-extension' ); ?>
			<?php } ?>
		  </td>
				</tr>



		<tr valign="top">
		  <th scope="row" class="settinglabel"><?php _e( 'Locked out usernames (not real users)', 'l10n-mainwp-ithemes-security-extension' ); ?></th>
		  <td class="settingfield">
			<?php if ( sizeof( $username_locks ) > 0 ) { ?>
			<ul>
				<?php foreach ( $username_locks as $user ) { ?>
			  <li style="list-style: none;">
								<input type="checkbox" name="lo_<?php echo $user['lockout_id']; ?>" id="lo_<?php echo $user['lockout_id']; ?>" value="<?php echo $user['lockout_id']; ?>"/>
				<label for="lo_<?php echo $user['lockout_id']; ?>"><strong><?php echo sanitize_text_field( $user['lockout_username'] ); ?></strong> - <?php _e( 'Expires in', 'l10n-mainwp-ithemes-security-extension' ); ?> <em> <?php echo human_time_diff( $mainwp_itsec_globals['current_time_gmt'], strtotime( $user['lockout_expire_gmt'] ) ); ?></em></label>
			  </li>
			  <?php } ?>
			  </ul>
			<?php } else { // no user is locked out ?>
				<?php _e( 'Currently no usernames are locked out of this website.', 'l10n-mainwp-ithemes-security-extension' ); ?>
			<?php } ?>
		  </td>
		</tr>

			</table>
	  <p class="submitleft">
		  <span class="mwp_itheme_lockouts_status hidden"></span><br />
		<input type="button" id="mwp_itheme_lockouts_release_btn" class="ui green button" value="<?php _e( 'Release Lockout', 'l10n-mainwp-ithemes-security-extension' ); ?>"/>&nbsp;<span><i class="notched circle loading icon" style="display:none"></i></span>
	  </p>
		<?php
	}


	/**
	 * Checks if the host or user is locked out and executes lockout
	 *
	 * @since 4.0
	 *
	 * @param mixed $user     WordPress user object or false
	 * @param mixed $username the username to check
	 *
	 * @return void
	 */
	public function check_lockout( $user = false, $username = false ) {

	}

	/**
	 * Executes lockout and logging for modules
	 *
	 * @since 4.0
	 *
	 * @param string $module string name of the calling module
	 * @param string $user   username of user
	 *
	 * @return void
	 */
	public function do_lockout( $module, $user = null ) {

	}

	/**
	 * Executes lockout (locks user out)
	 *
	 * @param boolean $user if we're locking out a user or not
	 *
	 * @return void
	 */
	protected function execute_lock( $user = false, $network = false ) {

	}

	/**
	 * Provides a description of lockout configuration for use in module settings.
	 *
	 * @since 4.0
	 *
	 * @return string the description of settings.
	 */
	public function get_lockout_description() {

		$global_settings_url = add_query_arg( array( 'module' => 'global' ), MainWP_ITSEC_Core::get_settings_page_url() ) . '#itsec-global-blacklist';
		// If the user is currently viewing "all" then let them keep viewing all
		if ( ! empty( $_GET['module_type'] ) && 'all' === $_GET['module_type'] ) {
			$global_settings_url = add_query_arg( array( 'module_type', 'all' ), $global_settings_url );
		}

		$description  = '<h4>' . __( 'About Lockouts', 'l10n-mainwp-ithemes-security-extension' ) . '</h4>';
		$description .= '<p>';
		$description .= sprintf( __( 'Your lockout settings can be configured in <a href="%s">Global Settings</a>.', 'l10n-mainwp-ithemes-security-extension' ), esc_url( $global_settings_url ) );
		$description .= '<br />';
		$description .= __( 'Your current settings are configured as follows:', 'l10n-mainwp-ithemes-security-extension' );
		$description .= '<ul><li>';
		$description .= sprintf( __( '<strong>Permanently ban:</strong> %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Modules::get_setting( 'global', 'blacklist' ) === true ? __( 'yes', 'l10n-mainwp-ithemes-security-extension' ) : __( 'no', 'l10n-mainwp-ithemes-security-extension' ) );
		$description .= '</li><li>';
		$description .= sprintf( __( '<strong>Number of lockouts before permanent ban:</strong> %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Modules::get_setting( 'global', 'blacklist_count' ) );
		$description .= '</li><li>';
		$description .= sprintf( __( '<strong>How long lockouts will be remembered for ban:</strong> %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Modules::get_setting( 'global', 'blacklist_period' ) );
		$description .= '</li><li>';
		$description .= sprintf( __( '<strong>Host lockout message:</strong> %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Modules::get_setting( 'global', 'lockout_message' ) );
		$description .= '</li><li>';
		$description .= sprintf( __( '<strong>User lockout message:</strong> %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Modules::get_setting( 'global', 'user_lockout_message' ) );
		$description .= '</li><li>';
		$description .= sprintf( __( '<strong>Is this computer white-listed:</strong> %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Lib::is_ip_whitelisted( MainWP_ITSEC_Lib::get_ip() ) === true ? __( 'yes', 'l10n-mainwp-ithemes-security-extension' ) : __( 'no', 'l10n-mainwp-ithemes-security-extension' ) );
		$description .= '</li></ul>';

		return $description;

	}

	/**
	 * Shows all lockouts currently in the database.
	 *
	 * @since 4.0
	 *
	 * @param string $type    'all', 'host', or 'user'
	 * @param bool   $current true for all lockouts, false for current lockouts
	 *
	 * @return array all lockouts in the system
	 */
	public function get_lockouts( $type = 'all', $current = false ) {

	}

	/**
	 * Process ajax request to set temp whitelist
	 *
	 * @since 4.3
	 *
	 * @return void
	 */
	public function itsec_temp_whitelist_ajax() {

	}

	/**
	 * Process ajax request to release temp whitelist
	 *
	 * @since 4.6
	 *
	 * @return void
	 */
	public function itsec_temp_whitelist_release_ajax() {

	}

	/**
	 * Locks out given user or host
	 *
	 * @since 4.0
	 *
	 * @param  string $type     The type of lockout (for user reference)
	 * @param  string $reason   Reason for lockout, for notifications
	 * @param  string $host     Host to lock out
	 * @param  int    $user     user id to lockout
	 * @param string $username username to lockout
	 *
	 * @return void
	 */
	private function lockout( $type, $reason, $host = null, $user = null, $username = null ) {

	}


	/**
	 * Register 404 and file change detection for logger
	 *
	 * @param  array $logger_modules array of logger modules
	 *
	 * @return array                   array of logger modules
	 */
	public function register_logger( $logger_modules ) {

		$logger_modules['lockout'] = array(
			'type'     => 'lockout',
			'function' => __( 'Host or User Lockout', 'l10n-mainwp-ithemes-security-extension' ),
		);

		return $logger_modules;

	}

	/**
	 * Register Lockouts for Sync
	 *
	 * @param  array $sync_modules array of logger modules
	 *
	 * @return array                   array of logger modules
	 */
	public function register_sync( $sync_modules ) {

		$sync_modules['lockout'] = array(
			'verbs'      => array(
				'itsec-get-lockouts'       => 'MainWP_Ithemes_Sync_Verb_ITSEC_Get_Lockouts',
				'itsec-release-lockout'    => 'MainWP_Ithemes_Sync_Verb_ITSEC_Release_Lockout',
				'itsec-get-temp-whitelist' => 'MainWP_Ithemes_Sync_Verb_ITSEC_Get_Temp_Whitelist',
				'itsec-set-temp-whitelist' => 'MainWP_Ithemes_Sync_Verb_ITSEC_Set_Temp_Whitelist',
			),
			'everything' => array(
				'itsec-get-lockouts',
				'itsec-get-temp-whitelist',
			),
			'path'       => dirname( __FILE__ ),
		);

		return $sync_modules;

	}

	public function register_notification( $notifications ) {
		$notifications['lockout'] = array(
			'subject_editable' => true,
			'recipient'        => MainWP_ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE,
			'schedule'         => MainWP_ITSEC_Notification_Center::S_NONE,
			'optional'         => true,
		);

		return $notifications;
	}

	public function notification_strings() {
		return array(
			'label'       => esc_html__( 'Site Lockouts', 'l10n-mainwp-ithemes-security-extension' ),
			'description' => '',
			'subject'     => esc_html__( 'Site Lockout Notification', 'l10n-mainwp-ithemes-security-extension' ),
			'order'       => 2,
		);
	}

	/**
	 * Register modules that will use the lockout service
	 *
	 * @return void
	 */
	public function register_modules() {

		$this->lockout_modules = apply_filters( 'mainwp_itsec_lockout_modules', $this->lockout_modules );

	}

	/**
	 * Sets an error message when a user has been forcibly logged out due to lockout
	 *
	 * @return string
	 */
	public function set_lockout_error() {

	}

}
