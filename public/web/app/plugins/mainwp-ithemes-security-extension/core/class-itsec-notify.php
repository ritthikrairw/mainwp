<?php

/**
 * Handles sending notifications to users
 *
 * @package iThemes-Security
 * @since   4.5
 */
class MainWP_ITSEC_Notify {

	private $queue;

	function __construct() {

		global $mainwp_itsec_globals;

		$this->queue = get_site_option( 'itsec_message_queue' );
		add_filter( 'mainwp_itsec_notifications', array( $this, 'register_notification' ) );
		add_filter( 'mainwp_itsec_digest_notification_strings', array( $this, 'notification_strings' ) );

	}

	/**
	 * Processes and sends daily digest message
	 *
	 * @since 4.5
	 *
	 * @return void
	 */
	public function init() {

	}

	/**
	 * Enqueue or send notification accordingly
	 *
	 * @since 4.5
	 *
	 * @param int        $type 1 for lockout or 2 for custom message
	 * @param null|array $body Custom message information to send
	 *
	 * @return bool whether the message was successfully enqueue or sent
	 */
	public function notify( $body = null ) {

		global $mainwp_itsec_globals;

		$allowed_tags = array(
			'a'      => array(
				'href' => array(),
			),
			'em'     => array(),
			'p'      => array(),
			'strong' => array(),
			'table'  => array(
				'border' => array(),
				'style'  => array(),
			),
			'tr'     => array(),
			'td'     => array(
				'colspan' => array(),
			),
			'th'     => array(),
			'br'     => array(),
			'h4'     => array(),
		);

		return true;

	}

	/**
	 * Sends email to recipient
	 *
	 * @since 4.5
	 *
	 * @param string       $subject     Email subject
	 * @param string       $message     Message contents
	 * @param string|array $headers     Optional. Additional headers.
	 * @param string|array $attachments Optional. Files to attach.
	 *
	 * @return bool Whether the email contents were sent successfully.
	 */
	private function send_mail( $subject, $message, $headers = '', $attachments = array() ) {

	}

	/**
	 * Set HTML content type for email
	 *
	 * @since 4.5
	 *
	 * @return string html content type
	 */
	public function wp_mail_content_type() {

		return 'text/html';

	}

	public function register_notification( $notifications ) {
		$notifications['digest'] = array(
			'slug'             => 'digest',
			'recipient'        => MainWP_ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE,
			'schedule'         => array(
				'min' => MainWP_ITSEC_Notification_Center::S_DAILY,
				'max' => MainWP_ITSEC_Notification_Center::S_WEEKLY,
			),
			'subject_editable' => true,
			'optional'         => true,
		);

		return $notifications;
	}

	public function notification_strings() {
		return array(
			'label'       => esc_html__( 'Security Digest', 'l10n-mainwp-ithemes-security-extension' ),
			'description' => '',
			'subject'     => esc_html__( 'Daily Security Digest', 'l10n-mainwp-ithemes-security-extension' ), // Default schedule is Daily
			'order'       => 1,
		);
	}

}
