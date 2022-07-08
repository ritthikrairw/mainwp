<?php

/**
 * Class MainWP_Lighthouse_Utility
 */
namespace MainWP\Extensions\Lighthouse;

class MainWP_Lighthouse_Utility {

	public static $instance = null;

	protected $option_handle = 'mainwp_lighthouse_options';
	protected $option;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Lighthouse_Utility
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
		add_action( 'mainwp_lighthouse_update_option', array( $this, 'set_option' ), 10, 2 );
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
		if ( empty( $timestamp ) ) {
			$timestamp = time();
		}
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
		if ( ! defined( 'MAINWP_LIGHTHOUSE_CRON_WORKING' ) ) {
			define( 'MAINWP_LIGHTHOUSE_CRON_WORKING', true );
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
		// checking if cron working.
		if ( defined( 'MAINWP_LIGHTHOUSE_CRON_WORKING' ) && MAINWP_LIGHTHOUSE_CRON_WORKING ) {
			return true;
		}
		return false;
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
			do_action( 'mainwp_log_action', 'Lighthouse:: ' . $cron . $message, MAINWP_LIGHTHOUSE_LOG_PRIORITY_NUMBER );
		}
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
			31536000 => __( 'year', 'mainwp-lighthouse-extension' ),
			2592000  => __( 'month', 'mainwp-lighthouse-extension' ),
			604800   => __( 'week', 'mainwp-lighthouse-extension' ),
			86400    => __( 'day', 'mainwp-lighthouse-extension' ),
			3600     => __( 'hour', 'mainwp-lighthouse-extension' ),
			60       => __( 'minute', 'mainwp-lighthouse-extension' ),
			1        => __( 'second', 'mainwp-lighthouse-extension' ),
		);

		foreach ( $tokens as $unit => $text ) {
			if ( $time < $unit ) {
				continue;
			}
			$number_of_units = floor( $time / $unit );

			return $number_of_units . ' ' . $text . ( ( $number_of_units > 1 ) ? __( 's ago', 'mainwp-lighthouse-extension' ) : ' ' . __( 'ago', 'mainwp-lighthouse-extension' ) );
		}
	}

	/**
	 * Get Score Color Code
	 *
	 * Returns CSS class to use correct color for the element.
	 *
	 * @param int $score Audit score.
	 *
	 * @return string CSS class.
	 */
	public static function score_color_code( $score ) {
		$color = '';
		if ( $score <= 49 ) {
			$color = 'red';
		} elseif ( $score >= 50 && $score <= 89 ) {
			$color = 'yellow';
		} elseif ( $score >= 90 && $score <= 100 ) {
			$color = 'green';
		} else {
			$color = '';
		}
		return $color;
	}

	/**
	 * Get Score Color Code
	 *
	 * Returns CSS class to use correct color and icon for the element.
	 *
	 * @param string $audit Audit ID.
	 * @param string $value Audit value.
	 *
	 * @return string CSS classes.
	 */
	public static function audit_color_code( $audit, $value ) {
		$classes = '';

		if ( 'first-contentful-paint' == $audit ) {
			if ( $value <= 1.7 ) {
				$classes = 'green circle';
			} elseif ( $value >= 1.8 && $value <= 3 ) {
				$classes = 'yellow square';
			} elseif ( $value > 3 ) {
				$classes = 'red play';
			} else {
				$classes = '';
			}
		}

		if ( 'speed-index' == $audit ) {
			if ( $value <= 3.4 ) {
				$classes = 'green circle';
			} elseif ( $value >= 3.5 && $value <= 5.8 ) {
				$classes = 'yellow square';
			} elseif ( $value > 5.9 ) {
				$classes = 'red play';
			} else {
				$classes = '';
			}
		}

		if ( 'largest-contentful-paint' == $audit ) {
			if ( $value <= 2.5 ) {
				$classes = 'green circle';
			} elseif ( $value >= 2.51 && $value <= 4 ) {
				$classes = 'yellow square';
			} elseif ( $value > 4 ) {
				$classes = 'red play';
			} else {
				$classes = '';
			}
		}

		if ( 'interactive' == $audit ) {
			if ( $value <= 3.8 ) {
				$classes = 'green circle';
			} elseif ( $value >= 3.9 && $value <= 7.3 ) {
				$classes = 'yellow square';
			} elseif ( $value > 7.3 ) {
				$classes = 'red play';
			} else {
				$classes = '';
			}
		}

		if ( 'total-blocking-time' == $audit ) {
			if ( $value <= 300 ) {
				$classes = 'green circle';
			} else {
				$classes = 'red play';
			}
		}

		if ( 'cumulative-layout-shift' == $audit ) {
			if ( $value <= 0.1 ) {
				$classes = 'green circle';
			} elseif ( $value > 0.1 && $value <= 2.5 ) {
				$classes = 'yellow square';
			} elseif ( $value > 2.5 ) {
				$classes = 'red play';
			} else {
				$classes = '';
			}
		}

		if ( 'first-meaningful-paint' == $audit ) {
			if ( $value <= 2 ) {
				$classes = 'green circle';
			} elseif ( $value >= 2.1 && $value <= 4 ) {
				$classes = 'yellow square';
			} elseif ( $value > 4 ) {
				$classes = 'red play';
			} else {
				$classes = '';
			}
		}

		return $classes;
	}

	/**
	 * Audits Status
	 *
	 * Returns audit satus.
	 *
	 * @param array $audit Audit data.
	 *
	 * @return string Audit status.
	 */
	public static function get_audit_status( $audit ) {
		$audit_status = '';
		if ( 'manual' == $audit['scoreDisplayMode'] ) {
			$audit_status = '<span class="audit-status manual" style="float:right" data-tooltip="Item to manually check." data-inverted="" data-position="left center"><i class="black circle icon"></i></span>';
		} elseif ( 'notApplicable' == $audit['scoreDisplayMode'] ) {
			$audit_status = '<span class="audit-status not-applicable" style="float:right" data-tooltip="Not applicable audit." data-inverted="" data-position="left center"><i class="grey circle icon"></i></span>';
		} elseif ( 'informative' == $audit['scoreDisplayMode'] ) {
			$audit_status = '<span class="audit-status diagnostics" style="float:right" data-tooltip="Diagnostics" data-inverted="" data-position="left center"><i class="grey circle outline icon"></i></span>';
		} elseif ( 'error' == $audit['scoreDisplayMode'] ) {
			$audit_status = '<span class="audit-status failed" style="float:right" data-tooltip="ERROR" data-inverted="" data-position="left center"><i class="red circle icon"></i></span>';
		} elseif ( 'numeric' == $audit['scoreDisplayMode'] ) {
			if ( 0.9 <= $audit['score'] ) {
				$audit_status = '<span class="audit-status passed" style="float:right" data-tooltip="Passed audit." data-inverted="" data-position="left center"><i class="green circle icon"></i></span>';
			} else {
				$audit_status = '<span class="audit-status failed" style="float:right" data-tooltip="Failed audit." data-inverted="" data-position="left center"><i class="red circle icon"></i></span>';
			}
		} elseif ( 'binary' == $audit['scoreDisplayMode'] ) {
			if ( 0.9 <= $audit['score'] ) {
				$audit_status = '<span class="audit-status passed" style="float:right" data-tooltip="Passed audit." data-inverted="" data-position="left center"><i class="green circle icon"></i></span>';
			} else {
				$audit_status = '<span class="audit-status failed" style="float:right" data-tooltip="Failed audit." data-inverted="" data-position="left center"><i class="red circle icon"></i></span>';
			}
		} else {
			$audit_status = '<span class="audit-status unknown" style="float:right" data-tooltip="Unknown" data-inverted="" data-position="left center"><i class="grey circle icon"></i></span>';
		}

		return $audit_status;
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
