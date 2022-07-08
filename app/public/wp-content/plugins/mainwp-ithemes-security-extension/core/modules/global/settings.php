<?php

final class MainWP_ITSEC_Global_Settings_New extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'global';
	}

	public function get_defaults() {
		global $mainwp_itsec_globals;

		$email = get_option( 'admin_email' );

		return array(
			// 'notification_email'        => array( $email ),
			// 'backup_email'              => array( $email ),
			'lockout_message'           => __( 'error', 'l10n-mainwp-ithemes-security-extension' ),
			'user_lockout_message'      => __( 'You have been locked out due to too many invalid login attempts.', 'l10n-mainwp-ithemes-security-extension' ),
			'community_lockout_message' => __( 'Your IP address has been flagged as a threat by the iThemes Security network.', 'l10n-mainwp-ithemes-security-extension' ),
			'blacklist'                 => true,
			'blacklist_count'           => 3,
			'blacklist_period'          => 7,
			// 'email_notifications'       => true,
			'lockout_period'            => 15,
			'lockout_white_list'        => array(),
			'log_rotation'              => 60,
			'file_log_rotation'         => 180,
			'log_type'                  => 'database',
			'log_location'              => MainWP_ITSEC_Core::get_storage_dir( 'logs' ),
			'log_info'                  => '',
			'allow_tracking'            => false,
			'write_files'               => true,
			'nginx_file'                => ABSPATH . 'nginx.conf',
			'infinitewp_compatibility'  => false,
			'did_upgrade'               => false,
			// 'lock_file'                 => false,
			// 'digest_email'              => false,
			// 'proxy_override'            => false,
			'proxy'                     => 'automatic',
			'proxy_header'              => 'HTTP_X_FORWARDED_FOR',
			'hide_admin_bar'            => false,
			'show_new_dashboard_notice' => false,
			'enable_grade_report'       => false,
			'automatic_temp_auth'       => false,
		);
	}

	protected function handle_settings_changes( $old_settings ) {
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_Global_Settings_New() );
