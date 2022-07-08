<?php

/**
 * Class MainWP_ITSEC_Notification_Center
 */
final class MainWP_ITSEC_Notification_Center {

	const R_USER = 'user'; // Goes to an end user. Two Factor or Magic Links.
	const R_ADMIN = 'admin'; // Emails currently listed in Global Settings -> Notification Email
	const R_USER_LIST = 'user-list'; // Can select users who should receive the email. For example Malware Scheduling.
	const R_EMAIL_LIST = 'email-list'; // List of email addresses.
	const R_PER_USE = 'per-use'; // Email address is selected before performing the action. For example Import/Export.
	const R_USER_LIST_ADMIN_UPGRADE = 'user-list-admin-upgrade'; // Can select users/roles, but was previously the admin email list. Contains upgrade functionality

	const S_NONE = 'none';
	const S_DAILY = 'daily';
	const S_WEEKLY = 'weekly';
	const S_MONTHLY = 'monthly';
	const S_CONFIGURABLE = 'configurable';

	/** @var bool */
	private $use_cron;

	/**
	 * Array of notification configs, keyed by notification slug.
	 *
	 * Lazily computed, see ::get_notifications().
	 *
	 * @var array
	 */
	private $notifications;

	/**
	 * Array of notification strings, keyed by notification slug.
	 * Separated from regular configuration due to gettext perforamnce.
	 *
	 * Lazily computed, see ::get_notification_strings().
	 *
	 * @var array
	 */
	private $strings = array();

	/**
	 * The current notification being sent by ::send().
	 *
	 * Used for providing additional information when capturing mail errors.
	 *
	 * This could be replaced with closure scope if migrated to PHP 5.3.
	 *
	 * @var string
	 */
	private $_sending_notification = '';

	/**
	 * MainWP_ITSEC_Notification_Center constructor.
	 */
	public function __construct() {
		$this->use_cron = defined( 'ITSEC_NOTIFY_USE_CRON' ) && ITSEC_NOTIFY_USE_CRON;
	}

	/**
	 * Get registered notifications.
	 *
	 * This value is cached.
	 *
	 * @return array
	 */
	public function get_notifications() {

		if ( null === $this->notifications ) {
			/**
			 * Filter the registered notifications.
			 *
			 * Do not conditionally register the filter, instead perform any conditional registration in the callback,
			 * so the cache can be properly cleared on settings changes.
			 *
			 * @param array                     $notifications
			 * @param MainWP_ITSEC_Notification_Center $this
			 */
			$notifications = apply_filters( 'mainwp_itsec_notifications', array(), $this );

			foreach ( $notifications as $slug => $notification ) {
				$notification                 = $this->notification_defaults( $notification );
				$notification['slug']         = $slug;
				$this->notifications[ $slug ] = $notification;
			}
		}

		return $this->notifications;
	}

	/**
	 * Clear the notifications cache.
	 *
	 * This shouldn't be necessary in the vast majority of cases.
	 */
	public function clear_notifications_cache() {
		$this->notifications = null;
	}

	/**
	 * Get enabled notifications.
	 *
	 * @return array
	 */
	public function get_enabled_notifications() {
		$notifications = $this->get_notifications();
		$enabled       = array();

		foreach ( $notifications as $slug => $notification ) {
			if ( $this->is_notification_enabled( $slug ) ) {
				$enabled[ $slug ] = $notification;
			}
		}

		return $enabled;
	}

	/**
	 * Check if a notification is enabled.
	 *
	 * @param string $notification
	 *
	 * @return bool
	 */
	public function is_notification_enabled( $notification ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return false;
		}

		if ( empty( $config['optional'] ) ) {
			return true;
		}

		$settings = $this->get_notification_settings( $notification );

		return ! empty( $settings['enabled'] );
	}

	/**
	 * Parse notification defaults.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	private function notification_defaults( $args ) {
		$args = wp_parse_args( $args, array(
			'recipient'        => self::R_ADMIN,
			'schedule'         => self::S_NONE,
			'subject_editable' => false,
			'message_editable' => false,
			'optional'         => false,
			'tags'             => array(),
			'module'           => '',
		) );

		$schedules = self::get_schedule_order();
		$schedule  = array(
			'min'     => $schedules[0],
			'max'     => $schedules[ count( $schedules ) - 1 ],
			'default' => self::S_DAILY,
		);

		if ( $args['schedule'] === self::S_CONFIGURABLE ) {
			$args['schedule'] = $schedule;
		} elseif ( is_array( $args['schedule'] ) ) {
			$args['schedule'] = wp_parse_args( $args['schedule'], $schedule );
		}

		return $args;
	}

	/**
	 * Get the notification config.
	 *
	 * @param string $slug
	 *
	 * @return array|null
	 */
	public function get_notification( $slug ) {
		$notifications = $this->get_notifications();

		return isset( $notifications[ $slug ] ) ? $notifications[ $slug ] : null;
	}

	/**
	 * Get strings for a notification.
	 *
	 * @param string $slug
	 *
	 * @return array
	 */
	public function get_notification_strings( $slug ) {

		if ( ! isset( $this->strings[ $slug ] ) ) {
			$this->strings[ $slug ] = apply_filters( "mainwp_itsec_{$slug}_notification_strings", array() );
		}

		return $this->strings[ $slug ];
	}

	/**
	 * Get the configured subject for a notification.
	 *
	 * @param string $notification
	 *
	 * @return string
	 */
	public function get_subject( $notification ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return '';
		}

		$settings = $this->get_notification_settings( $notification );

		if ( ! empty( $config['subject_editable'] ) && ! empty( $settings['subject'] ) ) {
			return $settings['subject'];
		}

		$strings = $this->get_notification_strings( $notification );

		return isset( $strings['subject'] ) ? $strings['subject'] : '';
	}

	/**
	 * Get the configured main message for a notification.
	 *
	 * @param string $notification
	 * @param string $format Either 'raw' or 'display'. If 'display', the message will have wpautop. Defaults to 'display'.
	 *
	 * @return string
	 */
	public function get_message( $notification, $format = 'display' ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return '';
		}

		$settings = $this->get_notification_settings( $notification );

		if ( ! empty( $config['message_editable'] ) && ! empty( $settings['message'] ) ) {
			return 'display' === $format ? wpautop( $settings['message'] ) : $settings['message'];
		}

		$strings = $this->get_notification_strings( $notification );

		if ( isset( $strings['message'] ) ) {
			return 'display' === $format ? wpautop( $strings['message'] ) : $strings['message'];
		}

		return '';
	}

	/**
	 * Get the selected schedule for a notification.
	 *
	 * @param string $notification
	 *
	 * @return string
	 */
	public function get_schedule( $notification ) {

		$config = $this->get_notification( $notification );

		if ( ! $config ) {
			return self::S_NONE;
		}

		if ( self::S_CONFIGURABLE !== $config['schedule'] && ! is_array( $config['schedule'] ) ) {
			return $config['schedule'];
		}

		$settings = $this->get_notification_settings( $notification );

		if ( ! empty( $settings['schedule'] ) ) {
			return $settings['schedule'];
		}

		return $config['schedule']['min'];
	}

	/**
	 * Enqueue some data a scheduled notification should have access to when sending.
	 *
	 * @param string $notification
	 * @param mixed  $data
	 * @param bool   $enforce_unique Whether to enforce all the data for that notification is unique. Only set to false if you are sure data is already unique.
	 */
	public function enqueue_data( $notification, $data, $enforce_unique = true ) {
		$all_data = MainWP_ITSEC_Modules::get_setting( 'notification-center', 'data' );

		$notification_data   = isset( $all_data[ $notification ] ) ? $all_data[ $notification ] : array();
		$notification_data[] = $data;

		if ( $enforce_unique ) {
			$notification_data = array_unique( $notification_data );
		}

		$all_data[ $notification ] = $notification_data;

		MainWP_ITSEC_Modules::set_setting( 'notification-center', 'data', $all_data );
	}

	/**
	 * Get the data for a notification.
	 *
	 * @param string $notification
	 *
	 * @return array
	 */
	public function get_data( $notification ) {

		$all_data = MainWP_ITSEC_Modules::get_setting( 'notification-center', 'data' );

		return isset( $all_data[ $notification ] ) ? $all_data[ $notification ] : array();
	}

	/**
	 * Initialize a Mail instance.
	 *
	 * @return ITSEC_Mail
	 */
	public function mail() {
//		require_once( MainWP_ITSEC_Core::get_core_dir() . 'lib/class-itsec-mail.php' );
//
//		return new ITSEC_Mail();
	}

	/**
	 * Send an email.
	 *
	 * This will set the subject and recipients configured for the notification if they have not been set.
	 *
	 * Additionally, will log any errors encountered while sending.
	 *
	 * @param string     $notification
	 * @param ITSEC_Mail $mail
	 *
	 * @return bool
	 */
	public function send( $notification, $mail ) {

	}

	/**
	 * Dismiss an error encountered while sending a notification with wp_mail().
	 *
	 * @param string $error_id
	 */
	public function dismiss_mail_error( $error_id ) {
		$errors = MainWP_ITSEC_Modules::get_setting( 'notification-center', 'mail_errors', array() );
		unset( $errors[ $error_id ] );
		MainWP_ITSEC_Modules::set_setting( 'notification-center', 'mail_errors', $errors );
	}

	/**
	 * Get the loggged mail errors keyed by id.
	 *
	 * @return array
	 */
	public function get_mail_errors() {
		return MainWP_ITSEC_Modules::get_setting( 'notification-center', 'mail_errors', array() );
	}

	/**
	 * Initialize the module.
	 */
	public function run() {

	}

	/**
	 * Capture whenever an error occurs in wp_mail() while sending a notification so it can be displayed later in the Notification Center.
	 *
	 * @param WP_Error $error
	 */
	public function capture_mail_fail( $error ) {

		$errors = MainWP_ITSEC_Modules::get_setting( 'notification-center', 'mail_errors', array() );

		$errors[ uniqid() ] = array(
			'error'        => array( 'message' => $error->get_error_message(), 'code' => $error->get_error_code() ),
			'time'         => MainWP_ITSEC_Core::get_current_time_gmt(),
			'notification' => $this->_sending_notification,
		);

		MainWP_ITSEC_Modules::set_setting( 'notification-center', 'mail_errors', $errors );

		if ( MainWP_ITSEC_Core::is_interactive() ) {
			ITSEC_Response::reload_module( 'notification-center' );
		}
	}

	/**
	 * Update the notification settings when the admin user id changes.
	 *
	 * @since 4.1.0
	 *
	 * @param int $new_user_id
	 */
	public function update_notification_user_id_on_admin_change( $new_user_id ) {

		$settings      = MainWP_ITSEC_Modules::get_settings_obj( 'notification-center' );
		$notifications = $settings->get( 'notifications' );

		if ( empty( $notifications ) ) {
			return;
		}

		$changed = false;

		foreach ( $notifications as $slug => $notification ) {

			if ( empty( $notification['user_list'] ) ) {
				continue;
			}

			$user_list = $notification['user_list'];

			foreach ( $user_list as $i => $contact ) {
				if ( is_numeric( $contact ) && 1 === (int) $contact ) {
					$notifications[ $slug ]['user_list'][ $i ] = $new_user_id;

					$changed = true;
					break;
				}
			}
		}

		if ( $changed ) {
			$settings->set( 'notifications', $notifications );
		}
	}



	/**
	 * Get the settings for a notification.
	 *
	 * @param string $notification
	 *
	 * @return array|null
	 */
	private function get_notification_settings( $notification ) {
		$settings = MainWP_ITSEC_Modules::get_setting( 'notification-center', 'notifications' );

		return isset( $settings[ $notification ] ) ? $settings[ $notification ] : null;
	}

	/**
	 * Get the cached value that all notification should be resent at.
	 *
	 * @return int[]
	 */
	private function get_all_resend_at() {

		$resend_at = MainWP_ITSEC_Modules::get_setting( 'notification-center', 'resend_at' );

		if ( ! is_array( $resend_at ) || empty( $resend_at ) ) {
			$resend_at = array();
		}

		return array_merge( array_fill_keys( array_keys( $this->get_notifications() ), 0 ), $resend_at );
	}


	/**
	 * Get the uncached options storage.
	 *
	 * @return array
	 */
	private function get_uncached_options() {
		/** @var $wpdb \wpdb */
		global $wpdb;

		$option  = 'itsec-storage';
		$storage = array();

		if ( is_multisite() ) {
			$network_id = get_current_site()->id;
			$row        = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = %s AND site_id = %d", $option, $network_id ) );

			if ( is_object( $row ) ) {
				$storage = maybe_unserialize( $row->meta_value );
			}
		} else {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

			if ( is_object( $row ) ) {
				$storage = maybe_unserialize( $row->option_value );
			}
		}

		if ( ! isset( $storage['notification-center'] ) ) {
			return array();
		}

		return $storage['notification-center'];
	}

	/**
	 * Get labels for the different schedule options.
	 *
	 * @return array
	 */
	public static function get_schedule_labels() {
		return array(
			self::S_DAILY   => esc_html__( 'Daily', 'l10n-mainwp-ithemes-security-extension' ),
			self::S_WEEKLY  => esc_html__( 'Weekly', 'l10n-mainwp-ithemes-security-extension' ),
			self::S_MONTHLY => esc_html__( 'Monthly', 'l10n-mainwp-ithemes-security-extension' ),
		);
	}

	/**
	 * Get the order of schedules from smallest to largest.
	 *
	 * @return array
	 */
	public static function get_schedule_order() {
		return array( self::S_DAILY, self::S_WEEKLY, self::S_MONTHLY );
	}

	/**
	 * Flip a 2-dimensional array.
	 *
	 * @param array $array
	 *
	 * @return array
	 */
	private static function flip_2d_array( $array ) {
		$out = array();

		foreach ( $array as $row => $columns ) {
			foreach ( $columns as $new_row => $new_column ) {
				$out[ $new_row ][ $row ] = $new_column;
			}
		}

		return $out;
	}
}