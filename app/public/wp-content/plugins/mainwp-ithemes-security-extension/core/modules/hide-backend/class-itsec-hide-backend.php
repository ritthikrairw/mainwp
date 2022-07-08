<?php

class MainWP_ITSEC_Hide_Backend {

	private	$settings;		

	function run() {

		$this->settings = MainWP_ITSEC_Modules::get_settings( 'hide-backend' );

		if ( ! $this->settings['enabled'] ) {
			return;
		}

		add_filter( 'mainwp_itsec_notifications', array( $this, 'register_notification' ) );
        add_filter( 'mainwp_itsec_hide-backend_notification_strings', array( $this, 'notification_strings' ) );

	}
    
    public function register_notification( $notifications ) {

		if ( MainWP_ITSEC_Modules::get_setting( 'hide-backend', 'enabled' ) ) {
			$notifications['hide-backend'] = array(
				'subject_editable' => true,
				'message_editable' => true,
				'schedule'         => MainWP_ITSEC_Notification_Center::S_NONE,
				'recipient'        => MainWP_ITSEC_Notification_Center::R_USER_LIST,
				'tags'             => array( 'login_url', 'site_title', 'site_url' ),
				'module'           => 'hide-backend',
			);
		}

		return $notifications;
	}
    
    
    public function notification_strings() {
		return array(
			'label'       => esc_html__( 'Hide Backend – New Login URL', 'l10n-mainwp-ithemes-security-extension' ),
			'description' => sprintf( esc_html__( '%1$sHide Backend%2$s will notify the chosen recipients whenever the login URL is changed.', 'l10n-mainwp-ithemes-security-extension' ), '<a href="#" data-module-link="hide-backend">', '</a>' ),
			'subject'     => esc_html__( 'WordPress Login Address Changed', 'l10n-mainwp-ithemes-security-extension' ),
			'message'     => esc_html__( 'The login address for {{ $site_title }} has changed. The new login address is {{ $login_url }}. You will be unable to use the old login address.', 'l10n-mainwp-ithemes-security-extension' ),
			'tags'        => array(
				'login_url'  => esc_html__( 'The new login link.', 'l10n-mainwp-ithemes-security-extension' ),
				'site_title' => esc_html__( 'The WordPress Site Title. Can be changed under Settings -> General -> Site Title', 'l10n-mainwp-ithemes-security-extension' ),
				'site_url'   => esc_html__( 'The URL to your website.', 'l10n-mainwp-ithemes-security-extension' ),
			),
		);
	}

    
}
