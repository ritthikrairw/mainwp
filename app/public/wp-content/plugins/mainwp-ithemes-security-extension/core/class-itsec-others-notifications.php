<?php

class MainWP_ITSEC_Others_Notifications {

	private static $instance = false;

	private function __construct() {
		add_filter( 'mainwp_itsec_notifications', array( $this, 'register_notifications' ) );
		add_filter( 'mainwp_itsec_two-factor-email_notification_strings', array( $this, 'two_factor_email_method_strings' ) );
		add_filter( 'mainwp_itsec_two-factor-confirm-email_notification_strings', array( $this, 'two_factor_confirm_email_method_strings' ) );

	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	/**
	 * Register the Two Factor Email method notification.
	 *
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notifications( $notifications ) {

		$notifications['two-factor-email'] = array(
			'slug'             => 'two-factor-email',
			'schedule'         => MainWP_ITSEC_Notification_Center::S_NONE,
			'recipient'        => MainWP_ITSEC_Notification_Center::R_USER,
			'subject_editable' => true,
			'message_editable' => true,
			'tags'             => array( 'username', 'display_name', 'site_title' ),
			'module'           => 'two-factor',
		);

		$notifications['two-factor-confirm-email'] = array(
			'slug'             => 'two-factor-confirm-email',
			'schedule'         => MainWP_ITSEC_Notification_Center::S_NONE,
			'recipient'        => MainWP_ITSEC_Notification_Center::R_USER,
			'subject_editable' => true,
			'message_editable' => true,
			'tags'             => array( 'username', 'display_name', 'site_title' ),
			'module'           => 'two-factor',
			'optional'         => true,
		);

		return $notifications;
	}

	/**
	 * Provide translated strings for the Two Factor Email method notification.
	 *
	 * @return array
	 */
	public function two_factor_email_method_strings() {
		/* translators: Do not translate the curly brackets or their contents, those are placeholders. */
		$message = __(
			'Hi {{ $display_name }},

Click the button to continue or manually enter the authentication code below to finish logging in.',
			'better-wp-security'
		);

		return array(
			'label'       => __( 'Two-Factor Email', 'better-wp-security' ),
			'description' => '',
			'subject'     => __( 'Login Authentication Code', 'better-wp-security' ),
			'message'     => $message,
			'tags'        => array(
				'username'     => __( 'The recipient’s WordPress username.', 'better-wp-security' ),
				'display_name' => __( 'The recipient’s WordPress display name.', 'better-wp-security' ),
				'site_title'   => __( 'The WordPress Site Title. Can be changed under Settings → General → Site Title', 'better-wp-security' ),
			),
			'order'       => 5,
		);
	}

	/**
	 * Provide translated strings for the Two Factor Confirm Email method notification.
	 *
	 * @return array
	 */
	public function two_factor_confirm_email_method_strings() {
		/* translators: Do not translate the curly brackets or their contents, those are placeholders. */
		$message = __(
			'Hi {{ $display_name }},

Click the button to continue or manually enter the authentication code below to finish setting up Two-Factor.',
			'better-wp-security'
		);

		$desc = ' ' . __( 'Disabling this email will disable the Two-Factor Email Confirmation flow.', 'better-wp-security' );

		return array(
			'label'       => __( 'Two-Factor Email Confirmation', 'better-wp-security' ),
			'description' => $desc,
			'subject'     => __( 'Login Authentication Code', 'better-wp-security' ),
			'message'     => $message,
			'tags'        => array(
				'username'     => __( 'The recipient’s WordPress username.', 'better-wp-security' ),
				'display_name' => __( 'The recipient’s WordPress display name.', 'better-wp-security' ),
				'site_title'   => __( 'The WordPress Site Title. Can be changed under Settings → General → Site Title', 'better-wp-security' ),
			),
			'order'       => 6,
		);
	}

}
