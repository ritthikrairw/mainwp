<?php

namespace MainWP\Extensions\Domain_Monitor;

class MainWP_Domain_Monitor_Hooks {

	/**
	 * Public static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	public static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Domain_Monitor_Hooks
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Runs each time the class is called.
	 */
	public function __construct() {

	}

	/**
	 * Initiate Hooks
	 *
	 * Initiates hooks for the Domain Monitor extension.
	 */
	public function init() {
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site' ), 10, 1 );
		add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
		add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'manage_sites_column' ), 10 );
		add_filter( 'mainwp_sitestable_item', array( $this, 'manage_sites_item' ), 10 );
		add_filter( 'mainwp_monitoring_sitestable_getcolumns', array( $this, 'manage_sites_column' ), 10 );
		add_filter( 'mainwp_monitoring_sitestable_item', array( $this, 'manage_sites_item' ), 10 );
		add_filter( 'mainwp_notification_types', array( &$this, 'hook_notification_types' ), 10, 2 );
		add_filter( 'mainwp_default_emails_fields', array( &$this, 'hook_default_emails_fields' ), 10, 5 );
		add_filter( 'mainwp_notification_type_desc', array( &$this, 'hook_notification_type_desc' ), 10, 2 );
		add_filter( 'mainwp_get_notification_template_name_by_type', array( &$this, 'hook_get_template_name_by_type' ), 10, 2 );
		add_filter( 'mainwp_default_template_locate', array( &$this, 'hook_default_notification_template_locate' ), 10, 5 );
		add_filter( 'minwp_notification_template_copy_message', array( &$this, 'hook_notification_template_copy_message' ), 10, 4 );
		add_filter( 'mainwp_default_template_source_dir', array( &$this, 'hook_default_template_source_dir' ), 10, 2 );
		add_filter( 'mainwp_domain_monitor_get_data', array( &$this, 'get_domain_monitor_reports_data' ), 10, 5 );
	}

	/**
	 * Delete Domain Monitor Data
	 *
	 * Deletes the Domain Monitor data for a child site.
	 *
	 * @param object $website Child site.
	 */
	public function delete_site( $website ) {
		if ( $website ) {
			MainWP_Domain_Monitor_DB::get_instance()->delete_domain_monitor( 'site_id', $website->id );
		}
	}


	/**
	 * Site Domain Monitor page
	 *
	 * Creates the Domain Monitor page for each child site.
	 *
	 * @param array $subPage Subpage data.
	 *
	 * @return array $subPage Subpage data.
	 */
	public function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title'       => __( 'Domain Monitor', 'mainwp-domain-monitor-extension' ),
			'slug'        => 'DomainMonitor',
			'sitetab'     => true,
			'menu_hidden' => true,
			'callback'    => array( MainWP_Domain_Monitor_Admin::class, 'render_extension_page' ),
		);
		return $subPage;
	}

	/**
	 * Manage Sites Column
	 *
	 * Adds the custom column in the Manage Sites and Monitoring tables.
	 *
	 * @param array $columns Table comlumns.
	 *
	 * @return array $columns Table comlumns.
	 */
	public function manage_sites_column( $columns ) {
		$columns['domain-expires'] = __( 'Domain Expires', 'mainwp-domain-monitor-extension' );
		return $columns;
	}

	/**
	 * Manage Sites Item
	 *
	 * Adds the custom column data in the Manage Sites and Monitoring tables.
	 *
	 * @param array $item Site comlumn data.
	 *
	 * @return array $item Site comlumn data.
	 */
	public function manage_sites_item( $item ) {
		$website_id = $item['id'];

		$domain_data = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $website_id );

		if ( isset( $domain_data ) && ! empty( $domain_data ) ) {
			$expires = round( ( intval( $domain_data->expiry_date ) - time() ) / ( 60 * 60 * 24 ) );

			$color_code = 'green';
			if ( 30 > $expires && $expires >= 7 ) {
				$color_code = 'yellow';
			} else if  ( 7 > $expires ) {
				$color_code = 'red';
			}

			$item['domain-expires'] = '<span class="ui ' . $color_code . ' fluid mini center aligned label">' . $expires  . __( ' days', 'mainwp-domain-monitor-extension' ) . '</span>';
		} else {
			$item['domain-expires'] = 'N/A';
		}

		return $item;
	}

	/**
	 * Hook notification types
	 *
	 * Hook notification types.
	 *
	 * @param array  $types notification types.
	 * @param string $type  notification type input.
	 *
	 * @return array $types notification types.
	 */
	public function hook_notification_types( $types, $type = '' ) {
		if ( ! is_array( $types ) ) {
			$types = array();
		}
		$types['domain_monitor_notification_email'] = __( 'Domain Monitor Notification Email', 'mainwp-domain-monitor-extension' );
		return $types;
	}

	/**
	 * Hook default emails fields
	 *
	 * Hook default emails fields.
	 *
	 * @param array  $fields emails fields.
	 * @param array  $recipients recipients.
	 * @param string $type notification type input.
	 * @param string $field emails fields.
	 * @param bool   $general general or individual.
	 *
	 * @return array $fields emails fields.
	 */
	public function hook_default_emails_fields( $fields, $recipients, $type, $field, $general ) {
		if ( ! is_array( $fields ) ) {
			$fields = array();
		}
		$disable                         = $general ? 0 : 1;
		$fields['domain_monitor_notification_email'] = array(
			'disable'    => $disable,
			'recipients' => $recipients,
			'subject'    => $general ? 'MainWP Domain Monitor Notification' : 'MainWP Domain Monitor Notification for [site.name]',
			'heading'    => $general ? 'MainWP Domain Monitor Notification' : 'MainWP Domain Monitor Notification for [site.name]',
		);
		return $fields;
	}

	/**
	 * Hook notification type description
	 *
	 * Hook notification type description.
	 *
	 * @param string $desc notification settings description.
	 * @param string $type notification type input.
	 *
	 * @return array $desc notification description.
	 */
	public function hook_notification_type_desc( $desc, $type ) {
		if ( 'domain_monitor_notification_email' == $type ) {
			$desc = __( 'Domain Monitor Notification Email', 'mainwp-domain-monitor-extension' );
		}
		return $desc;
	}

	/**
	 * Hook get template name by type
	 *
	 * Hook get template name by type.
	 *
	 * @param string $template_name template name.
	 * @param string $type notification type input.
	 *
	 * @return array $template_name template name.
	 */
	public function hook_get_template_name_by_type( $template_name, $type ) {
		if ( 'domain_monitor_notification_email' == $type ) {
			$template_name = 'emails/mainwp-domain-monitor-notification-email.php';
		}
		return $template_name;
	}

	/**
	 * Hook default notification template locate
	 *
	 * Hook default notification template locate.
	 *
	 * @param string $default_file default template file.
	 * @param string $template template.
	 * @param string $default_dir default directory.
	 * @param string $type notification type input.
	 * @param int    $siteid site id.
	 *
	 * @return array $template_name template name.
	 */
	public function hook_default_notification_template_locate( $default_file, $template, $default_dir, $type, $siteid ) {
		if ( 'domain_monitor_notification_email' == $type ) {
			$default_file = MAINWP_DOMAIN_MONITOR_PLUGIN_DIR . '/templates/emails/mainwp-domain-monitor-notification-email.php';
		}
		return $default_file;
	}

	/**
	 * Hook default notification template locate
	 *
	 * Hook default notification template locate.
	 *
	 * @param string $message copy message.
	 * @param string $templ template.
	 * @param string $type notification type input.
	 * @param bool   $overrided overrided template or not.
	 *
	 * @return array $template_name template name.
	 */
	public function hook_notification_template_copy_message( $message, $templ, $type, $overrided ) {
		if ( 'domain_monitor_notification_email' == $type ) {
			$message = $overrided ? esc_html__( 'This template has been overridden and can be found in:', 'mainwp-domain-monitor-extension' ) . ' <code>wp-content/uploads/mainwp/templates/' . $templ . '</code>' : esc_html__( 'To override and edit this email template copy:', 'mainwp-domain-monitor-extension' ) . ' <code>mainwp-domain-monitor-extension/templates/' . $templ . '</code> ' . esc_html__( 'to the folder:', 'mainwp-domain-monitor-extension' ) . ' <code>wp-content/uploads/mainwp/templates/' . $templ . '</code>';
		}
		return $message;
	}

	/**
	 * Hook default notification template source directory.
	 *
	 * Hook default notification template source directory.
	 *
	 * @param string $template_path source directory.
	 * @param string $template_base_name notification type input.
	 *
	 * @return string $template_path source directory.
	 */
	public function hook_default_template_source_dir( $template_path, $template_base_name = '' ) {
		if ( 'emails/mainwp-domain-monitor-notification-email.php' == $template_base_name ) {
			$template_path = MAINWP_DOMAIN_MONITOR_PLUGIN_DIR . '/templates/';
		}
		return $template_path;
	}

	/**
	 * Get Domain Data
	 *
	 * @param string $input     Input value.
	 * @param int    $site_id   Child Site ID.
	 *
	 * @return array Report data.
	 */
	public function get_domain_monitor_reports_data( $input, $site_id ) {

		if ( empty( $site_id ) ) {
			return $input;
		}

		$domain_data = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $site_id );

		if ( empty( $domain_data ) ) {
			return $input;
		}

		if ( is_object( $domain_data ) ) {

			$domain_name   = isset( $domain_data->domain_name ) ? $domain_data->domain_name : '';
			$registrar     = isset( $domain_data->registrar ) ? $domain_data->registrar : '';
			$updated_date  = isset( $domain_data->updated_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->updated_date ) ) : '';
			$creation_date = isset( $domain_data->creation_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->creation_date ) ) : '';
			$expiry_date   = isset( $domain_data->expiry_date ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->expiry_date ) ) : '';
			$last_check    = isset( $domain_data->last_check ) ? MainWP_Domain_Monitor_Utility::format_datestamp( MainWP_Domain_Monitor_Utility::get_timestamp( $domain_data->last_check ) ) : '';

			$expires = round( ( $domain_data->expiry_date - time() ) / ( 60 * 60 * 24 ) );

			$status = __( 'Valid', 'mainwp-domain-monitor-extension' );

			if ( 0 > $expires ) {
				$status = __( 'Expired', 'mainwp-domain-monitor-extension' );
			}

			$input['domain.monitor.domain.name']   = $domain_name;
			$input['domain.monitor.registrar']     = $registrar;
			$input['domain.monitor.updated.date']  = $updated_date;
			$input['domain.monitor.creation.date'] = $creation_date;
			$input['domain.monitor.expiry.date']   = $expiry_date;
			$input['domain.monitor.expires']       = $expires;
			$input['domain.monitor.status']        = $status;
			$input['domain.monitor.last.check']    = $last_check;

		}
		return $input;
	}

}
