<?php

/**
 * Class MainWP_Domain_Monitor_Utility
 */
namespace MainWP\Extensions\Domain_Monitor;

class MainWP_Domain_Monitor_Utility {

	public static $instance = null;

	protected $option_handle = 'mainwp_domain_monitor_options';
	protected $option;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Domain_Monitor_Utility
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
		add_action( 'mainwp_domain_monitor_update_option', array( $this, 'set_option' ), 10, 2 );
	}

	/**
	 * Get Option
	 *
	 * Gets option in Databse.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Option value.
	 *
	 * @return mixed Retruns option value.
	 */
	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	/**
	 * Set Option
	 *
	 * Sets option in Databse.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Option value.
	 *
	 * @return mixed Update option.
	 */
	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	/**
	 * Get timestamp.
	 *
	 * @param string $timestamp Holds Timestamp.
	 *
	 * @return float|int Return GMT offset.
	 */
	public static function get_timestamp( $timestamp = false ) {
		if ( false === $timestamp ) {
			$timestamp = time();
		}

		$timestamp = intval( $timestamp ); // to fix.

		$gmtOffset = get_option( 'gmt_offset' );

		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}

	/**
	 * Format timestamp.
	 *
	 * @param string $timestamp Holds Timestamp.
	 * @param bool   $gmt Whether to set as General mountain time. Default: FALSE.
	 *
	 * @return string Return Timestamp.
	 */
	public static function format_timestamp( $timestamp, $gmt = false ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp, $gmt );
	}

	/**
	 * Format datestamp.
	 *
	 * @param string $timestamp Holds Timestamp.
	 * @param bool   $gmt Whether to set as General mountain time. Default: FALSE.
	 *
	 * @return string Return Timestamp.
	 */
	public static function format_datestamp( $timestamp, $gmt = false ) {
		return date_i18n( get_option( 'date_format' ), $timestamp, $gmt );
	}

	/**
	 * Format date.
	 *
	 * @param string $timestamp Holds Timestamp.
	 * @param bool   $gmt Whether to set as General mountain time. Default: FALSE.
	 *
	 * @return string Return Timestamp.
	 */
	public static function format_date( $timestamp, $gmt = false ) {
		return date_i18n( get_option( 'date_format' ), $timestamp, $gmt );
	}


	/**
	 * Method map_fields()
	 *
	 * Map Site.
	 *
	 * @param mixed $website Website to map.
	 * @param mixed $keys Keys to map.
	 * @param bool  $object_output Output format array|object.
	 *
	 * @return object $outputSite Mapped site.
	 */
	public static function map_fields( &$website, $keys, $object_output = false ) {
		$outputSite = array();
		if ( ! empty( $website ) ) {
			if ( is_object( $website ) ) {
				foreach ( $keys as $key ) {
					if ( property_exists( $website, $key ) ) {
						$outputSite[ $key ] = $website->$key;
					}
				}
			} elseif ( is_array( $website ) ) {
				foreach ( $keys as $key ) {
					$outputSite[ $key ] = $website[ $key ];
				}
			}
		}

		if ( $object_output ) {
			return (object) $outputSite;
		} else {
			return $outputSite;
		}
	}

	/**
	 * Set Cron
	 *
	 * Sets the cron job.
	 *
	 * @param bool True on success.
	 */
	public static function set_cron_working() {
		if ( ! defined( 'MAINWP_DOMAIN_MONITOR_CRON_WORKING' ) ) {
			define( 'MAINWP_DOMAIN_MONITOR_CRON_WORKING', true );
		}
		return true;
	}

	/**
	 * Cron Check
	 *
	 * Checks if WP Cron is working.
	 *
	 * @param bool                                        $set_cron Cron status.
	 *
	 * @param bool True or false depending on cron status.
	 */
	public static function is_cron_working( $set_cron = false ) {
		if ( defined( 'MAINWP_DOMAIN_MONITOR_CRON_WORKING' ) && MAINWP_DOMAIN_MONITOR_CRON_WORKING ) {
			return true;
		}
		return false;
	}

	/**
	 * Convert time to readable format.
	 *
	 * Converts time stamp to more readable format to display time difference.
	 *
	 * @param mixed $time Timestamp.
	 *
	 * @return string Time difference.
	 */
	public function human_timing( $time ) {
		if ( empty( $time ) ) {
			return 'N/A';
		}
		$time = current_time( 'timestamp' ) - $time;

		$tokens = array(
			31536000 => __( 'year', 'mainwp-domain-monitor-extension' ),
			2592000  => __( 'month', 'mainwp-domain-monitor-extension' ),
			604800   => __( 'week', 'mainwp-domain-monitor-extension' ),
			86400    => __( 'day', 'mainwp-domain-monitor-extension' ),
			3600     => __( 'hour', 'mainwp-domain-monitor-extension' ),
			60       => __( 'minute', 'mainwp-domain-monitor-extension' ),
			1        => __( 'second', 'mainwp-domain-monitor-extension' ),
		);

		foreach ( $tokens as $unit => $text ) {
			if ( $time < $unit ) {
				continue;
			}
			$number_of_units = floor( $time / $unit );

			return $number_of_units . ' ' . $text . ( ( $number_of_units > 1 ) ? __( 's ago', 'mainwp-domain-monitor-extension' ) : ' ' . __( 'ago', 'mainwp-domain-monitor-extension' ) );
		}
	}

	/**
	 * Method get_nice_url()
	 *
	 * Grab url.
	 *
	 * @param string $pUrl Website URL.
	 * @param bool   $showHttp Show HTTP.
	 *
	 * @return string $url.
	 */
	public static function get_nice_url( $pUrl, $showHttp = false ) {
		$url = $pUrl;

		if ( self::starts_with( $url, 'http://' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 7 );
			}
		} elseif ( self::starts_with( $pUrl, 'https://' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 8 );
			}
		} else {
			if ( $showHttp ) {
				$url = 'http://' . $url;
			}
		}

		if ( self::ends_with( $url, '/' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 0, strlen( $url ) - 1 );
			}
		} else {
			$url = $url . '/';
		}

		return $url;
	}

	/**
	 * Method get_domain()
	 *
	 * Converts subdomain to domain.
	 *
	 * @param string domain Subdomain to convert.
	 *
	 * @return string Domain name.
	 */
	public static function get_domain( $subdomain, $second = false ) {

		$domain = strtolower( trim( $subdomain ) );

		if ( false !== strpos( $domain, '/' ) ) {
			if ( false === strpos( $domain, 'http' ) ) {
				$domain = 'http://' . $domain; // to parse url and fix subfolers.
			}
			$domain = wp_parse_url( $domain, PHP_URL_HOST );
		}

		$count = substr_count( $domain, '.' );

		if ( 2 === $count ) {
			if ( strlen( explode( '.', $domain )[1] ) > 3 ) {
				  $domain = explode( '.', $domain, 2 )[1];
			}
		} elseif ( 2 < $count && ! $second ) {
			$domain = self::get_domain( explode( '.', $domain, 2 )[1], true );
		}
		return $domain;
	}

	public static function get_domain_status( $domain_status ) {
		$epp_status_codes = array(
			'addPeriod'                => array(
				'status'  => 'Add Period',
				'meaning' => 'This grace period is provided after the initial registration of a domain name. If the registrar deletes the domain name during this period, the registry may provide credit to the registrar for the cost of the registration.',
				'action'  => 'This is an informative status set for the first several days of your domain\'s registration. There is no issue with your domain name.',
			),
			'autoRenewPeriod'          => array(
				'status'  => 'Auto Renew Period',
				'meaning' => 'This grace period is provided after a domain name registration period expires and is extended (renewed) automatically by the registry. If the registrar deletes the domain name during this period, the registry provides a credit to the registrar for the cost of the renewal.',
				'action'  => 'This is an informative status set for a limited time after your domain\'s auto- renewal by the registry. If you do not want to keep it (i.e., pay the renewal fee) anymore, you should contact your registrar immediately to discuss what options are available.',
			),
			'inactive'                 => array(
				'status'  => 'Inactive',
				'meaning' => 'This status code indicates that delegation information (name servers) has not been associated with your domain. Your domain is not activated in the DNS and will not resolve.',
				'action'  => 'If your domain has remained in this status for several days, you may want to contact your registrar to request information about the delay in processing. If the TLD requires documentation to be provided for registration, you may need to provide the required documentation.',
			),
			'ok'                       => array(
				'status'  => 'OK',
				'meaning' => 'This is the standard status for a domain, meaning it has no pending operations or prohibitions.',
				'action'  => 'Asking your registrar to enact status restrictions, like clientTransferProhibited, clientDeleteProhibited, and clientUpdateProhibited, can help to prevent unauthorized transfers, deletions, or updates to your domain.',
			),
			'pendingCreate'            => array(
				'status'  => 'Pending Create',
				'meaning' => 'This status code indicates that a request to create your domain has been received and is being processed.',
				'action'  => 'If the TLD is on a special registration period (e.g. sunrise), this may indicate that the domain name will be allocated at the end of such period. If the TLD is not on a special registration period and you are NOT the listed Registrant, you should contact your registrar immediately to resolve the issue.',
			),
			'pendingDelete'            => array(
				'status'  => 'Pending Delete',
				'meaning' => 'This status code may be mixed with redemptionPeriod or pendingRestore. In such case, depending on the status (i.e. redemptionPeriod or pendingRestore) set in the domain name, the corresponding description presented above applies. If this status is not combined with the redemptionPeriod or pendingRestore status, the pendingDelete status code indicates that your domain has been in redemptionPeriod status for 30 days and you have not restored it within that 30-day period. Your domain will remain in this status for several days, after which time your domain will be purged and dropped from the registry database.',
				'action'  => 'If you want to keep your domain name, you must immediately contact your registrar to discuss what options are available.',
			),
			'pendingRenew'             => array(
				'status'  => 'Pending Renew',
				'meaning' => 'This status code indicates that a request to renew your domain has been received and is being processed.',
				'action'  => 'If you did not request to renew your domain and do not want to keep it (i.e., pay the renewal fee) anymore, you should contact your registrar immediately to discuss what options are available.',
			),
			'pendingRestore'           => array(
				'status'  => 'Pending Restore',
				'meaning' => 'This status code indicates that your registrar has asked the registry to restore your domain that was in redemptionPeriod status. Your registry will hold the domain in this status while waiting for your registrar to provide required restoration documentation. If your registrar fails to provide documentation to the registry operator within a set time period to confirm the restoration request, the domain will revert to redemptionPeriod status.',
				'action'  => 'Watch your domain\'s status codes within this frequently defined seven day period to ensure that your registrar has submitted the correct restoration documentation within the time window. If this period ended and your domain has reverted back to a redemptionPeriod status, contact your registrar to resolve whatever issues that may have halted the delivery of your domain\'s required restoration documentation.',
			),
			'pendingTransfer'          => array(
				'status'  => 'Pending Transfer',
				'meaning' => 'This status code indicates that a request to transfer your domain to a new registrar has been received and is being processed.',
				'action'  => 'If you did not request to transfer your domain, you should contact your registrar immediately to request that they deny the transfer request on your behalf.',
			),
			'pendingUpdate'            => array(
				'status'  => 'Pending Update',
				'meaning' => 'This status code indicates that a request to update your domain has been received and is being processed.',
				'action'  => 'If you did not request to update your domain, you should contact your registrar immediately to resolve the issue.',
			),
			'redemptionPeriod'         => array(
				'status'  => 'Redemption Period',
				'meaning' => 'This status code indicates that your registrar has asked the registry to delete your domain. Your domain will be held in this status for 30 days. After five calendar days following the end of the redemptionPeriod, your domain is purged from the registry database and becomes available for registration.',
				'action'  => 'If you want to keep your domain, you must immediately contact your registrar to resolve whatever issues resulted in your registrar requesting that your domain be deleted, which resulted in the redemptionPeriod status for your domain. Once any outstanding issues are resolved and the appropriate fee has been paid, your registrar should restore the domain on your behalf.',
			),
			'renewPeriod'              => array(
				'status'  => 'Renew Period',
				'meaning' => 'This grace period is provided after a domain name registration period is explicitly extended (renewed) by the registrar. If the registrar deletes the domain name during this period, the registry provides a credit to the registrar for the cost of the renewal.',
				'action'  => 'This is an informative status set for a limited period or your domain\'s renewal by your registrar. If you did not request to renew your domain and do not want to keep it (i.e., pay the renewal fee) anymore, you should contact your registrar immediately to discuss what options are available.',
			),
			'serverDeleteProhibited'   => array(
				'status'  => 'Server Delete Prohibited',
				'meaning' => 'This status code prevents your domain from being deleted. It is an uncommon status that is usually enacted during legal disputes, at your request, or when a redemptionPeriod status is in place.',
				'action'  => 'This status may indicate an issue with your domain that needs resolution. If so, you should contact your registrar to request more information and to resolve the issue. If your domain does not have any issues, and you simply want to delete it, you must first contact your registrar and request that they work with the Registry Operator to remove this status code. Alternatively, some Registry Operators offer a Registry Lock Service that allows registrants, through their registrars to set this status as an extra protection against unauthorized deletions. Removing this status can take longer than it does for clientDeleteProhibited because your registrar has to forward your request to your domain\'s registry and wait for them to lift the restriction.',
			),
			'serverHold'               => array(
				'status'  => 'Server Hold',
				'meaning' => 'This status code is set by your domain\'s Registry Operator. Your domain is not activated in the DNS.',
				'action'  => 'If you provided delegation information (name servers), this status may indicate an issue with your domain that needs resolution. If so, you should contact your registrar to request more information. If your domain does not have any issues, but you need it to resolve in the DNS, you must first contact your registrar in order to provide the necessary delegation information.',
			),
			'serverRenewProhibited'    => array(
				'status'  => 'Server Renew Prohibited',
				'meaning' => 'This status code indicates your domain\'s Registry Operator will not allow your registrar to renew your domain. It is an uncommon status that is usually enacted during legal disputes or when your domain is subject to deletion.',
				'action'  => 'Often, this status indicates an issue with your domain that needs to be addressed promptly. You should contact your registrar to request more information and resolve the issue. If your domain does not have any issues, and you simply want to renew it, you must first contact your registrar and request that they work with the Registry Operator to remove this status code. This process can take longer than it does for clientRenewProhibited because your registrar has to forward your request to your domain\'s registry and wait for them to lift the restriction.',
			),
			'serverTransferProhibited' => array(
				'status'  => 'Server Transfer Prohibited',
				'meaning' => 'This status code prevents your domain from being transferred from your current registrar to another. It is an uncommon status that is usually enacted during legal or other disputes, at your request, or when a redemptionPeriod status is in place.',
				'action'  => 'This status may indicate an issue with your domain that needs to be addressed promptly. You should contact your registrar to request more information and resolve the issue. If your domain does not have any issues, and you simply want to transfer it to another registrar, you must first contact your registrar and request that they work with the Registry Operator to remove this status code. Alternatively, some Registry Operators offer a Registry Lock Service that allows registrants, through their registrars to set this status as an extra protection against unauthorized transfers. Removing this status can take longer than it does for clientTransferProhibited because your registrar has to forward your request to your domain\'s registry and wait for them to lift the restriction.',
			),
			'serverUpdateProhibited'   => array(
				'status'  => 'Server Update Prohibited',
				'meaning' => 'This status code locks your domain preventing it from being updated. It is an uncommon status that is usually enacted during legal disputes, at your request, or when a redemptionPeriod status is in place.',
				'action'  => 'This status may indicate an issue with your domain that needs resolution. If so, you should contact your registrar for more information or to resolve the issue. If your domain does not have any issues, and you simply want to update it, you must first contact your registrar and request that they work with the Registry Operator to remove this status code.',
			),
			'transferPeriod'           => array(
				'status'  => 'Transfer Period',
				'meaning' => 'This grace period is provided after the successful transfer of a domain name from one registrar to another. If the new registrar deletes the domain name during this period, the registry provides a credit to the registrar for the cost of the transfer.',
				'action'  => 'This is an informative status set for a limited period or your domain\'s transfer to a new registrar. If you did not request to transfer your domain, you should contact your original registrar.',
			),
			'clientDeleteProhibited'   => array(
				'status'  => 'Client Delete Prohibited',
				'meaning' => 'This status code tells your domain\'s registry to reject requests to delete the domain.',
				'action'  => 'This status indicates that it is not possible to delete the domain name registration, which can prevent unauthorized deletions resulting from hijacking and/or fraud. If you do want to delete your domain, you must first contact your registrar and request that they remove this status code.',
			),
			'clientHold'               => array(
				'status'  => 'Client Hold',
				'meaning' => 'This status code tells your domain\'s registry to not activate your domain in the DNS and as a consequence, it will not resolve. It is an uncommon status that is usually enacted during legal disputes, non-payment, or when your domain is subject to deletion.',
				'action'  => 'Often, this status indicates an issue with your domain that needs resolution. If so, you should contact your registrar to resolve the issue. If your domain does not have any issues, but you need it to resolve, you must first contact your registrar and request that they remove this status code.',
			),
			'clientRenewProhibited'    => array(
				'status'  => 'Client Renew Prohibited',
				'meaning' => 'This status code tells your domain\'s registry to reject requests to renew your domain. It is an uncommon status that is usually enacted during legal disputes or when your domain is subject to deletion.',
				'action'  => 'Often, this status indicates an issue with your domain that needs resolution. If so, you should contact your registrar to resolve the issue. If your domain does not have any issues, and you simply want to renew it, you must first contact your registrar and request that they remove this status code.',
			),
			'clientTransferProhibited' => array(
				'status'  => 'Client Transfer Prohibited',
				'meaning' => 'This status code tells your domain\'s registry to reject requests to transfer the domain from your current registrar to another.',
				'action'  => 'This status indicates that it is not possible to transfer the domain name registration, which will help prevent unauthorized transfers resulting from hijacking and/or fraud. If you do want to transfer your domain, you must first contact your registrar and request that they remove this status code.',
			),
			'clientUpdateProhibited'   => array(
				'status'  => 'Client Update Prohibited',
				'meaning' => 'This status code tells your domain\'s registry to reject requests to update the domain.',
				'action'  => 'This domain name status indicates that it is not possible to update the domain, which can help prevent unauthorized updates resulting from fraud. If you do want to update your domain, you must first contact your registrar and request that they remove this status code.',
			),
		);

		$empty = array(
			'status'  => '',
			'meaning' => '',
			'action'  => '',
		);

		return isset( $epp_status_codes[ $domain_status ] ) ? $epp_status_codes[ $domain_status ] : $empty;
	}

	/**
	 * Method starts_with()
	 *
	 * Start of Stack Trace.
	 *
	 * @param mixed $haystack The full stack.
	 * @param mixed $needle The function that is throwing the error.
	 *
	 * @return mixed Needle in the Haystack.
	 */
	public static function starts_with( $haystack, $needle ) {
		return ! strncmp( $haystack, $needle, strlen( $needle ) );
	}

	/**
	 * Method ends_with()
	 *
	 * End of Stack Trace.
	 *
	 * @param mixed $haystack Haystack parameter.
	 * @param mixed $needle Needle parameter.
	 *
	 * @return boolean
	 */
	public static function ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( 0 === $length ) {
			return true;
		}

		return ( substr( $haystack, - $length ) === $needle );
	}

	/**
	 * Debugging log
	 *
	 * Sets logging for debugging purpose
	 *
	 * @param string $message Log message.
	 * @param string $logtype Log type.
	 */
	public static function log_debug( $message, $logtype = 'action' ) {
		$cron = '';
		if ( self::is_cron_working() ) {
			$cron = 'CRON :: ';
		}
		if ( 'action' == $logtype ) {
			do_action( 'mainwp_log_action', 'Domain Monitor:: ' . $cron . $message, MAINWP_DOMAIN_MONITOR_LOG_PRIORITY_NUMBER );
		}
	}

	 /**
	  * Show Info Messages
	  *
	  * Check whenther or not to show the MainWP Message.
	  *
	  * @param string $notice_id Notice ID.
	  *
	  * @return bool False if hidden, true to show.
	  */
	public static function show_mainwp_message( $notice_id ) {
		$status = get_user_option( 'mainwp_notice_saved_status' );
		if ( ! is_array( $status ) ) {
			$status = array();
		}
		if ( isset( $status[ $notice_id ] ) ) {
			return false;
		}
		return true;
	}

}
