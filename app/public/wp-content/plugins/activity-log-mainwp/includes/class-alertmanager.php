<?php
/**
 * Class: Alert Manager
 *
 * Alert manager class file of the extension.
 *
 * @package mwp-al-ext
 * @since 1.0.0
 */

namespace WSAL\MainWPExtension;

use \WSAL\MainWPExtension as MWPAL_Extension;
use \WSAL\MainWPExtension\Alert as Alert;
use \WSAL\MainWPExtension\Loggers\AbstractLogger as AbstractLogger;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Alert Manager Class
 *
 * Alert manager class of the extension.
 */
final class AlertManager {

	/**
	 * Extension Alerts.
	 *
	 * @var array
	 */
	protected $alerts = array();

	/**
	 * Extension Loggers.
	 *
	 * @var array
	 */
	protected $loggers = array();

	/**
	 * Triggered Alerts
	 *
	 * Contains an array of alerts that have been triggered for this request.
	 *
	 * @var array
	 */
	protected $triggered_types = array();

	/**
	 * Local static cache for the event objects.
	 *
	 * @var array
	 * @since 2.0.0
	 */
	private static $objects;

	/**
	 * Local static cache for the event types.
	 *
	 * @var array
	 * @since 2.0.0
	 */
	private static $event_types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		foreach ( glob( MWPAL_BASE_DIR . 'includes/loggers/*.php' ) as $file ) {
			$this->add_from_file( $file );
		}
	}

	/**
	 * Add new logger from file inside autoloader path.
	 *
	 * @param string $file Path to file.
	 */
	public function add_from_file( $file ) {
		$this->add_from_class( $this->get_class_name( $file ) );
	}

	/**
	 * Add new logger given class name.
	 *
	 * @param string $class Class name.
	 */
	public function add_from_class( $class ) {
		$this->add_instance( new $class() );
	}

	/**
	 * Add newly created logger to list.
	 *
	 * @param AbstractLogger $logger - The new logger.
	 */
	public function add_instance( AbstractLogger $logger ) {
		$this->loggers[] = $logger;
	}

	/**
	 * Get class name for logger classes.
	 *
	 * @param string $file – File name.
	 * @return string
	 */
	private function get_class_name( $file ) {
		if ( empty( $file ) ) {
			return false;
		}

		// Replace file path, `class-`, and `.php` in the file string.
		$file = str_replace( array( MWPAL_BASE_DIR . 'includes/loggers/', 'class-', '.php' ), '', $file );
		$file = str_replace( 'logger', 'Logger', $file );
		$file = ucfirst( $file );
		$file = '\WSAL\MainWPExtension\Loggers\\' . $file;
		return $file;
	}

	/**
	 * Register a whole group of items.
	 *
	 * @param array $groups - An array with group name as the index and an array of group items as the value.
	 * Item values is an array of [type, code, description, message] respectively.
	 */
	public function RegisterGroup( $groups ) {
		foreach ( $groups as $name => $group ) {
			foreach ( $group as $subname => $subgroup ) {
				foreach ( $subgroup as $item ) {
					/*
					 * For legacy events keys 4 & 5 are not set. Make them empty
					 * strings so that `list` doesn't throw warnings/fail.
					 */
					$item[4] = ( isset( $item[4] ) ) ? $item[4] : '';
					$item[5] = ( isset( $item[5] ) ) ? $item[5] : '';
					list( $type, $code, $desc, $mesg, $object, $event_type ) = $item;
					$this->Register( array( $type, $code, $name, $subname, $desc, $mesg, $object, $event_type ) );
				}
			}
		}
	}

	/**
	 * Register an alert type.
	 *
	 * @param array $info - Array of [type, code, category, description, message] respectively.
	 * @throws \Exception - Error if alert is already registered.
	 */
	public function Register( $info ) {
		if ( 1 === func_num_args() ) {
			// Handle single item.
			list($type, $code, $catg, $subcatg, $desc, $mesg, $object, $event_type ) = $info;
			if ( isset( $this->alerts[ $type ] ) ) {
				add_action( 'admin_notices', array( $this, 'duplicate_event_notice' ) );
				/* Translators: Event ID */
				throw new \Exception( sprintf( esc_html__( 'Event %s already registered with Activity Log MainWP Extension.', 'mwp-al-ext' ), $type ) );
			}
			$this->alerts[ $type ] = new Alert( $type, $code, $catg, $subcatg, $desc, $mesg, $object, $event_type );
		} else {
			// Handle multiple items.
			foreach ( func_get_args() as $arg ) {
				$this->Register( $arg );
			}
		}
	}

	/**
	 * Duplicate Event Notice
	 *
	 * WP admin notice for duplicate event.
	 */
	public function duplicate_event_notice() {
		$class   = 'notice notice-error';
		$message = __( 'You have custom events that are using the same ID or IDs which are already registered in the plugin, so they have been disabled.', 'mwp-al-ext' );
		printf(
			/* Translators: 1.CSS classes, 2. Notice, 3. Contact us link */
			'<div class="%1$s"><p>%2$s %3$s ' . esc_html__( '%4$s to help you solve this issue.', 'mwp-al-ext' ) . '</p></div>',
			esc_attr( $class ),
			'<span style="color:#dc3232; font-weight:bold;">' . esc_html__( 'ERROR:', 'mwp-al-ext' ) . '</span>',
			esc_html( $message ),
			'<a href="https://wpactivitylog.com/contact/" target="_blank">' . esc_html__( 'Contact us', 'mwp-al-ext' ) . '</a>'
		);
	}

	/**
	 * Method: Returns an array of loaded loggers.
	 *
	 * @return AbstractLogger[]
	 */
	public function get_loggers() {
		return $this->loggers;
	}

	/**
	 * Return alert given alert type.
	 *
	 * @param integer $type    - Alert type.
	 * @param mixed   $default - Returned if alert is not found.
	 * @return \WSAL\MainWPExtension\Alert
	 */
	public function GetAlert( $type, $default = null ) {
		foreach ( $this->alerts as $alert ) {
			if ( $alert->type == $type ) {
				return $alert;
			}
		}
		return $default;
	}

	/**
	 * Returns all supported alerts.
	 *
	 * @return \WSAL\MainWPExtension\Alert[]
	 */
	public function GetAlerts() {
		return $this->alerts;
	}

	/**
	 * Method: Returns array of alerts by category.
	 *
	 * @param string $category - Alerts category.
	 * @return \WSAL\MainWPExtension\Alert[]
	 */
	public function get_alerts_by_category( $category ) {
		// Categorized alerts array.
		$alerts = array();
		foreach ( $this->alerts as $alert ) {
			if ( $category === $alert->catg ) {
				$alerts[ $alert->type ] = $alert;
			}
		}
		return $alerts;
	}

	/**
	 * Method: Returns array of alerts by sub-category.
	 *
	 * @param string $sub_category - Alerts sub-category.
	 * @return \WSAL\MainWPExtension\Alert[]
	 */
	public function get_alerts_by_sub_category( $sub_category ) {
		// Sub-categorized alerts array.
		$alerts = array();
		foreach ( $this->alerts as $alert ) {
			if ( $sub_category === $alert->subcatg ) {
				$alerts[ $alert->type ] = $alert;
			}
		}
		return $alerts;
	}

	/**
	 * Returns all supported alerts.
	 *
	 * @param bool $sorted – Sort the alerts array or not.
	 * @return array
	 */
	public function get_categorized_alerts( $sorted = true ) {
		$result = array();
		foreach ( $this->alerts as $alert ) {
			if ( ! isset( $result[ $alert->catg ] ) ) {
				$result[ $alert->catg ] = array();
			}
			if ( ! isset( $result[ $alert->catg ][ $alert->subcatg ] ) ) {
				$result[ $alert->catg ][ $alert->subcatg ] = array();
			}
			$result[ $alert->catg ][ $alert->subcatg ][] = $alert;
		}

		if ( $sorted ) {
			ksort( $result );
		}
		return $result;
	}

	/**
	 * Log events in the database.
	 *
	 * @param array   $events  – Activity Log Events.
	 * @param integer $site_id – Site ID according to MainWP.
	 * @return void
	 */
	public function log_events( $events, $site_id ) {
		if ( empty( $events ) ) {
			return;
		}

		if ( is_array( $events ) ) {
			foreach ( $events as $event_id => $event ) {
				// Get loggers.
				$loggers = $this->get_loggers();

				// Get meta data of event.
				$meta_data = $event['meta_data'];
				$user_data = isset( $meta_data['UserData'] ) ? $meta_data['UserData'] : false;

				// Username.
				if ( isset( $user_data['username'] ) ) {
					$meta_data['Username'] = $user_data['username'];
				}

				// First name.
				if ( isset( $user_data['first_name'] ) ) {
					$meta_data['FirstName'] = $user_data['first_name'];
				}

				// Last name.
				if ( isset( $user_data['last_name'] ) ) {
					$meta_data['LastName'] = $user_data['last_name'];
				}

				// Log the events in DB.
				foreach ( $loggers as $logger ) {
					$logger->log( $event['alert_id'], $meta_data, $event['created_on'], $site_id );
				}
			}
		}
	}

	/**
	 * Trigger an event.
	 *
	 * @param integer $type - Event type.
	 * @param array   $data - Event data.
	 */
	public function trigger( $type, $data = array() ) {
		// Get username.
		if ( ! isset( $data['Username'] ) || empty( $data['Username'] ) ) {
			$data['Username'] = wp_get_current_user()->user_login;
		}

		// Trigger event.
		$this->commit_event( $type, $data, null );
	}

	/**
	 * Method: Commit an alert now.
	 *
	 * @param int   $type  - Alert type.
	 * @param array $data  - Data of the alert.
	 * @param array $cond  - Condition for the alert.
	 * @param bool  $retry - Retry.
	 * @internal
	 *
	 * @throws Exception - Error if alert is not registered.
	 */
	protected function commit_event( $type, $data, $cond, $retry = true ) {
		if ( ! $cond || call_user_func( $cond, $this ) ) {
			if ( $this->is_enabled( $type ) ) {
				if ( isset( $this->alerts[ $type ] ) ) {
					// Ok, convert alert to a log entry.
					$this->triggered_types[] = $type;
					$this->log( $type, $data );
				} elseif ( $retry ) {
					// This is the last attempt at loading alerts from default file.
					MWPAL_Extension\mwpal_extension()->load_events();
					return $this->commit_event( $type, $data, $cond, false );
				} else {
					// In general this shouldn't happen, but it could, so we handle it here.
					/* translators: Event ID */
					throw new Exception( sprintf( esc_html__( 'Event with code %d has not be registered.', 'mwp-al-ext' ), $type ) );
				}
			}
		}
	}

	/**
	 * Returns whether alert of type $type is enabled or not.
	 *
	 * @since 1.0.4
	 *
	 * @param integer $type - Alert type.
	 * @return boolean - True if enabled, false otherwise.
	 */
	public function is_enabled( $type ) {
		$disabled_events = MWPAL_Extension\mwpal_extension()->settings->get_disabled_events();
		return ! in_array( $type, $disabled_events, true );
	}

	/**
	 * Log Alert
	 *
	 * Converts an Alert into a Log entry (by invoking loggers).
	 * You should not call this method directly.
	 *
	 * @param integer $type - Alert type.
	 * @param array   $data - Misc alert data.
	 */
	protected function log( $type, $data = array() ) {
		// Client IP.
		if ( ! isset( $data['ClientIP'] ) ) {
			$client_ip = MWPAL_Extension\mwpal_extension()->settings->get_main_client_ip();
			if ( ! empty( $client_ip ) ) {
				$data['ClientIP'] = $client_ip;
			}
		}

		// User agent.
		if ( ! isset( $data['UserAgent'] ) ) {
			if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$data['UserAgent'] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			}
		}

		// Username.
		if ( ! isset( $data['Username'] ) && ! isset( $data['CurrentUserID'] ) ) {
			if ( function_exists( 'get_current_user_id' ) ) {
				$data['CurrentUserID'] = get_current_user_id();
			}
		}

		// Current user roles.
		if ( ! isset( $data['CurrentUserRoles'] ) && function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			$current_user_roles = MWPAL_Extension\mwpal_extension()->settings->get_current_user_roles();
			if ( ! empty( $current_user_roles ) ) {
				$data['CurrentUserRoles'] = $current_user_roles;
			}
		}

		// Get event severity.
		$alert_obj  = $this->GetAlert( $type );
		$alert_code = $alert_obj ? $alert_obj->code : 0;
		$severity   = MWPAL_Extension\mwpal_extension()->constants->GetConstantBy( 'value', $alert_code );

		/**
		 * Events Severity.
		 *
		 * Add event severity to the metadata of the event.
		 * The lower the number, the higher is the severity.
		 *
		 * Based on monolog log levels:
		 *
		 * Formerly based on Syslog severity levels (https://en.wikipedia.org/wiki/Syslog#Severity_level).
		 *
		 * @see https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md#log-levels
		 * @since 2.0.0
		 */
		if ( is_object( $severity ) && property_exists( $severity, 'name' ) ) {
			if ( 'E_CRITICAL' === $severity->name ) {
				// CRITICAL (500): Critical conditions.
				$data['Severity'] = 500;
			} elseif ( 'E_WARNING' === $severity->name ) {
				// WARNING (300): Exceptional occurrences that are not errors.
				$data['Severity'] = 300;
			} elseif ( 'E_NOTICE' === $severity->name ) {
				// DEBUG (100): Detailed debug information.
				$data['Severity'] = 100;
			} elseif ( property_exists( $severity, 'value' ) ) {
				$data['Severity'] = $severity->value;
			}
		}

		/*
		 * In cases where we were not able to figure out a severity already
		 * use a default of 200: info.
		 *
		 * @since 2.0.0
		 */
		if ( ! isset( $data['Severity'] ) ) {
			// Assuming this is a misclassified item and using info code.
			// INFO (200): Interesting events.
			$data['Severity'] = 200;
		}

		// Alert `object type`.
		if ( ! isset( $data['Object'] ) && isset( $this->alerts[ $type ]->object ) ) {
			$data['Object'] = $this->alerts[ $type ]->object;
		}

		// Alert `event type`.
		if ( ! isset( $data['EventType'] ) && isset( $this->alerts[ $type ]->event_type ) ) {
			$data['EventType'] = $this->alerts[ $type ]->event_type;
		}

		// Log event.
		foreach ( $this->loggers as $logger ) {
			$logger->log( $type, $data, null, 0 );
		}
	}

	/**
	 * Delete Events from Child Sites.
	 *
	 * @since 1.0.1
	 *
	 * @param integer $site_id - Child site ID.
	 */
	public function delete_site_events( $site_id = 0 ) {
		if ( $site_id ) {
			// Delete events by site id.
			$delete_query = new \WSAL\MainWPExtension\Models\OccurrenceQuery();
			$delete_query->addCondition( 'site_id = %s ', $site_id );
			$delete_query->getAdapter()->Delete( $delete_query );
		}
	}

	/**
	 * Retrieve Events Manually.
	 *
	 * @since 1.1
	 */
	public function retrieve_events_manually() {
		// Get MainWP sites.
		$mwp_sites = MWPAL_Extension\mwpal_extension()->settings->get_wsal_child_sites();

		if ( ! empty( $mwp_sites ) ) {
			$trigger_retrieving = true; // Event 7711.
			$trigger_ready      = true; // Event 7712.
			$server_ip          = MWPAL_Extension\mwpal_extension()->settings->get_server_ip(); // Get server IP.

			foreach ( $mwp_sites as $site_id => $site ) {
				// Delete events by site id.
				$this->delete_site_events( $site_id );

				// Fetch events by site id.
				$sites_data[ $site_id ] = $this->fetch_site_events( $site_id, $trigger_retrieving );

				// Set $trigger_retrieving to false to avoid logging 7711 multiple times.
				$trigger_retrieving = false;

				if ( $trigger_ready && ( isset( $sites_data[ $site_id ]['events'] ) || isset( $sites_data[ $site_id ]['incompatible__skipped'] ) ) ) {
					// Extension is ready after retrieving.
					$this->trigger(
						7712,
						array(
							'mainwp_dash' => true,
							'Username'    => 'System',
							'ClientIP'    => ! empty( $server_ip ) ? $server_ip : false,
						)
					);
					$trigger_ready = false;
				}
			}
			// Set child site events.
			$this->set_site_events( $sites_data );
		}
	}

	/**
	 * Fetch Events from Child Sites.
	 *
	 * @since 1.0.1
	 *
	 * @param integer $site_id            - Child site id.
	 * @param bool    $trigger_retrieving - True if trigger retrieve events alert.
	 * @param array   $post_data          - MainWP post data.
	 * @return array
	 */
	public function fetch_site_events( $site_id = 0, $trigger_retrieving = true, $post_data = array() ) {
		$sites_data = array();

		if ( $site_id ) {
			$plugin = MWPAL_Extension\mwpal_extension();

			// Get server IP.
			$server_ip = $plugin->settings->get_server_ip();

			if ( $trigger_retrieving ) {
				// Extension has started retrieving.
				$this->trigger(
					7711,
					array(
						'mainwp_dash' => true,
						'Username'    => 'System',
						'ClientIP'    => ! empty( $server_ip ) ? $server_ip : false,
					)
				);
			}

			// Post data for child sites.
			if ( empty( $post_data ) ) {
				$post_data = array(
					'events_count' => $plugin->settings->get_child_site_events(),
				);
			}

			// Call to child sites to fetch WSAL events.
			$sites_data = $plugin->make_api_call( $site_id, 'get_events', $post_data );

			// Check if we recognize all the received events.
			if ( ! empty( $sites_data ) && array_key_exists( 'events', $sites_data ) ) {
				$codes = array_unique( wp_list_pluck( $sites_data['events'], 'alert_id' ) );

				$event_definitions_missing = false;
				if ( ! empty( $codes ) ) {
					foreach ( $codes as $code ) {
						$alert = $this->GetAlert( $code );
						if ( is_null( $alert ) ) {
							$event_definitions_missing = true;
							break;
						}
					}
				}

				if ( $event_definitions_missing ) {
					// Some event definitions are missing. Try to update them from the child site.
					$plugin->update_dynamic_event_definitions( $site_id );
				}
			}
		}

		return $sites_data;
	}

	/**
	 * Save Events from Child Sites.
	 *
	 * @since 1.0.1
	 *
	 * @param array $sites_data - Sites data.
	 */
	public function set_site_events( $sites_data = array() ) {
		if ( ! empty( $sites_data ) && is_array( $sites_data ) ) {
			// Get MainWP child sites.
			$mwp_sites = MWPAL_Extension\mwpal_extension()->settings->get_mwp_child_sites();

			// Get server IP.
			$server_ip = MWPAL_Extension\mwpal_extension()->settings->get_server_ip();

			foreach ( $sites_data as $site_id => $site_data ) {
				// If $site_data doesn't have the keys we expected then it failed to retrieve logs.
				if ( ! empty( $site_data ) && ! ( isset( $site_data['events'] ) && isset( $site_data['users'] ) ) ) {
					// Search for the site data.
					$key = array_search( $site_id, array_column( $mwp_sites, 'id' ), false );

					if ( false !== $key && isset( $mwp_sites[ $key ] ) ) {
						// Extension is unable to retrieve events.
						$this->trigger(
							7710,
							array(
								'friendly_name' => $mwp_sites[ $key ]['name'],
								'site_url'      => $mwp_sites[ $key ]['url'],
								'site_id'       => $mwp_sites[ $key ]['id'],
								'mainwp_dash'   => true,
								'Username'      => 'System',
								'ClientIP'      => ! empty( $server_ip ) ? $server_ip : false,
							)
						);
					}
				} elseif ( empty( $site_data ) || ! isset( $site_data['events'] ) ) {
					continue;
				}

				if ( isset( $site_data['events'] ) ) {
					$this->log_events( $site_data['events'], $site_id );
				}

				if ( isset( $site_data['users'] ) ) {
					save_child_site_users( $site_id, $site_data['users'] );
				}
			}
		}
	}

	/**
	 * Get event objects.
	 *
	 * @return array
	 */
	public function get_event_objects_data() {
		if ( isset( self::$objects ) ) {
			return self::$objects;
		}

		$objects = array(
			'activity-logs'  => __( 'Activity Logs', 'mwp-al-ext' ),
			'child-site'     => __( 'Child Site', 'mwp-al-ext' ),
			'extension'      => __( 'Extension', 'mwp-al-ext' ),
			'mainwp'         => __( 'MainWP', 'mwp-al-ext' ),
			'uptime-monitor' => __( 'Uptime Monitor', 'mwp-al-ext' ),
		);

		$result = apply_filters(
			'wsal_event_objects',
			$objects
		);

		asort( $result );
		self::$objects = $result;

		return $result;
	}

	/**
	 * Returns the text to display for object.
	 *
	 * @param string $object - Object.
	 * @return string
	 */
	public function get_display_object_text( $object ) {
		$data = $this->get_event_objects_data();
		if ( array_key_exists( $object, $data ) ) {
			return $data[ $object ];
		}

		return '';
	}

	/**
	 * Get event type data.
	 *
	 * @return array
	 */
	public function get_event_type_data() {
		if ( isset( self::$event_types ) ) {
			return self::$event_types;
		}

		$event_types = array(
			'activated'   => __( 'Activated', 'mwp-al-ext' ),
			'added'       => __( 'Added', 'mwp-al-ext' ),
			'created'     => __( 'Created', 'mwp-al-ext' ),
			'deactivated' => __( 'Deactivated', 'mwp-al-ext' ),
			'deleted'     => __( 'Deleted', 'mwp-al-ext' ),
			'failed'      => __( 'Failed', 'mwp-al-ext' ),
			'finished'    => __( 'Finished', 'mwp-al-ext' ),
			'installed'   => __( 'Installed', 'mwp-al-ext' ),
			'modified'    => __( 'Modified', 'mwp-al-ext' ),
			'removed'     => __( 'Removed', 'mwp-al-ext' ),
			'started'     => __( 'Started', 'mwp-al-ext' ),
			'stopped'     => __( 'Stopped', 'mwp-al-ext' ),
			'synced'      => __( 'Synced', 'mwp-al-ext' ),
			'uninstalled' => __( 'Uninstalled', 'mwp-al-ext' ),
			'updated'     => __( 'Updated', 'mwp-al-ext' ),
		);

		$result = apply_filters(
			'wsal_event_type_data',
			$event_types
		);

		asort( $result );
		self::$event_types = $result;

		return $result;
	}

	/**
	 * Returns the text to display for event type.
	 *
	 * @param string $event_type - Event type.
	 * @return string
	 */
	public function get_display_event_type_text( $event_type ) {
		$data = $this->get_event_type_data();
		if ( array_key_exists( $event_type, $data ) ) {
			return $data[ $event_type ];
		}

		return '';
	}
}
