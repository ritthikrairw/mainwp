<?php
/**
 * =======================================
 * MainWP Lighthouse Admin
 * =======================================
 *
 * @copyright Matt Keys <https://profiles.wordpress.org/mattkeys>
 */

namespace MainWP\Extensions\Lighthouse;

class MainWP_Lighthouse_Hooks {

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
	 * @return MainWP_Lighthouse_Hooks
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
	 * Initiates hooks for the Lighthouse extension.
	 */
	public function init() {
		add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
		add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'manage_sites_column' ), 10 );
		add_filter( 'mainwp_sitestable_item', array( $this, 'manage_sites_item' ), 10 );
		add_filter( 'mainwp_monitoring_sitestable_getcolumns', array( $this, 'manage_sites_column' ), 10 );
		add_filter( 'mainwp_monitoring_sitestable_item', array( $this, 'manage_sites_item' ), 10 );
		add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_added_new_site', array( &$this, 'update_lighthouse_data' ), 10, 2 );
		add_action( 'mainwp_delete_site', array( &$this, 'hook_delete_site' ), 10, 1 );
		add_filter( 'mainwp_lighthouse_get_data', array( &$this, 'lighthouse_get_data' ), 10, 5 );
		add_filter( 'mainwp_notification_types', array( &$this, 'hook_notification_types' ), 10, 2 );
		add_filter( 'mainwp_default_emails_fields', array( &$this, 'hook_default_emails_fields' ), 10, 5 );
		add_filter( 'mainwp_notification_type_desc', array( &$this, 'hook_notification_type_desc' ), 10, 2 );
		add_filter( 'mainwp_get_notification_template_name_by_type', array( &$this, 'hook_get_template_name_by_type' ), 10, 2 );
		add_filter( 'mainwp_default_template_locate', array( &$this, 'hook_default_notification_template_locate' ), 10, 5 );
		add_filter( 'minwp_notification_template_copy_message', array( &$this, 'hook_notification_template_copy_message' ), 10, 4 );
		add_filter( 'mainwp_default_template_source_dir', array( &$this, 'hook_default_template_source_dir' ), 10, 2 );
	}

	/**
	 * Update Lighthose Data
	 *
	 * Update the Lighthouse data for a new child site.
	 *
	 * @param int    $site_id Child site ID.
	 * @param object $website Child site.
	 */
	public function update_lighthouse_data( $site_id = null, $website = null ) {
		if ( empty( $site_id ) ) {
			return;
		}

		$update = array(
			'site_id' => $site_id,
		);

		if ( $website ) {
			$update['URL'] = $website->url;
		}

		MainWP_Lighthouse_DB::get_instance()->update_lighthouse( $update );
	}

	/**
	 * Delete Lighthose Data
	 *
	 * Deletes the Lighthouse data for a child site.
	 *
	 * @param object $website Child site.
	 */
	public function hook_delete_site( $website ) {
		if ( $website ) {
			MainWP_Lighthouse_DB::get_instance()->delete_lighthouse( 'site_id', $website->id );
		}
	}

	/**
	 * Site Lighthose page
	 *
	 * Creates the Lighthouse page for each child site.
	 *
	 * @param array $subPage Subpage data.
	 *
	 * @return array $subPage Subpage data.
	 */
	public function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title'       => __( 'Lighthouse', 'mainwp-lighthouse-extension' ),
			'slug'        => 'Lighthouse',
			'sitetab'     => true,
			'menu_hidden' => true,
			'callback'    => array( MainWP_Lighthouse_Admin::class, 'render_tabs' ),
		);
		return $subPage;
	}


	/**
	 * Get Lighthouse audit data.
	 *
	 * @param string $input    input value.
	 * @param int    $site_id   Child Site ID.
	 *
	 * @return array Report data.
	 */
	public function lighthouse_get_data( $input, $site_id ) {

		if ( empty( $site_id ) ) {
			return $input;
		}

		$lighthouse_data = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $site_id );

		if ( empty( $lighthouse_data ) ) {
			return $input;
		}

		$desktop_performance          = 'N/A';
		$desktop_accessibility_score  = 'N/A';
		$desktop_best_practices_score = 'N/A';
		$desktop_seo_score            = 'N/A';
		$desktop_lab_data             = false;

		$mobile_performance          = 'N/A';
		$mobile_accessibility_score  = 'N/A';
		$mobile_best_practices_score = 'N/A';
		$mobile_seo_score            = 'N/A';
		$mobile_lab_data             = false;

		if ( ! empty( $lighthouse_data->desktop_score ) ) {
			$desktop_performance = $lighthouse_data->desktop_score;
		}
		if ( ! empty( $lighthouse_data->desktop_accessibility_score ) ) {
			$desktop_accessibility_score = $lighthouse_data->desktop_accessibility_score;
		}
		if ( ! empty( $lighthouse_data->desktop_best_practices_score ) ) {
			$desktop_best_practices_score = $lighthouse_data->desktop_best_practices_score;
		}
		if ( ! empty( $lighthouse_data->desktop_seo_score ) ) {
			$desktop_seo_score = $lighthouse_data->desktop_seo_score;
		}

		if ( ! empty( $lighthouse_data->desktop_lab_data ) ) {
			$desktop_lab_data = json_decode( $lighthouse_data->desktop_lab_data, true );
		}

		if ( ! empty( $lighthouse_data->mobile_score ) ) {
			$mobile_performance = $lighthouse_data->mobile_score;
		}
		if ( ! empty( $lighthouse_data->mobile_accessibility_score ) ) {
			$mobile_accessibility_score = $lighthouse_data->mobile_accessibility_score;
		}
		if ( ! empty( $lighthouse_data->mobile_best_practices_score ) ) {
			$mobile_best_practices_score = $lighthouse_data->mobile_best_practices_score;
		}
		if ( ! empty( $lighthouse_data->mobile_seo_score ) ) {
			$mobile_seo_score = $lighthouse_data->mobile_seo_score;
		}

		if ( ! empty( $lighthouse_data->mobile_lab_data ) ) {
			$mobile_lab_data = json_decode( $lighthouse_data->mobile_lab_data, true );
		}

		$strategy = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
		if ( $lighthouse_data->override ) {
			$strategy = $lighthouse_data->strategy;
		}

		if ( is_object( $lighthouse_data ) ) {
			$input['lighthouse.performance.desktop']   = $desktop_performance;
			$input['lighthouse.accessibility.desktop'] = $desktop_accessibility_score;
			$input['lighthouse.bestpractices.desktop'] = $desktop_best_practices_score;
			$input['lighthouse.seo.desktop']           = $desktop_seo_score;
			$input['lighthouse.lastcheck.desktop']     = MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse_data->desktop_last_modified ) );

			$desktop_audits = '';
			if ( $desktop_lab_data ) {
				foreach ( $desktop_lab_data as $item ) {
					$desktop_audits .= $item['title'] . ': ' . $item['displayValue'] . '<br/>';
				}
			}
			$input['lighthouse.audits.desktop'] = $desktop_audits;

			$input['lighthouse.performance.mobile']   = $mobile_performance;
			$input['lighthouse.accessibility.mobile'] = $mobile_accessibility_score;
			$input['lighthouse.bestpractices.mobile'] = $mobile_best_practices_score;
			$input['lighthouse.seo.mobile']           = $mobile_seo_score;
			$input['lighthouse.lastcheck.mobile']     = MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse_data->mobile_last_modified ) );

			$mobile_audits = '';
			if ( $mobile_lab_data ) {
				foreach ( $mobile_lab_data as $item ) {
					$mobile_audits .= $item['title'] . ': ' . $item['displayValue'] . '<br/>';
				}
			}
			$input['lighthouse.audits.mobile'] = $mobile_audits;
		}
		return $input;
	}

	/**
	 * Hook notification types
	 *
	 * Hook notification types.
	 *
	 * @param array  $types notification types.
	 * @param string $type notification type input.
	 *
	 * @return array $types notification types.
	 */
	public function hook_notification_types( $types, $type = '' ) {
		if ( ! is_array( $types ) ) {
			$types = array();
		}
		$types['lighthouse_noti_email'] = __( 'Lighthouse Notification Email', 'mainwp-lighthouse-extension' );
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
		$fields['lighthouse_noti_email'] = array(
			'disable'    => $disable,
			'recipients' => $recipients,
			'subject'    => $general ? 'MainWP Lighthouse Notification' : 'MainWP Lighthouse Notification for [site.name]',
			'heading'    => $general ? 'MainWP Lighthouse Notification' : 'MainWP Lighthouse Notification for [site.name]',
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
		if ( 'lighthouse_noti_email' == $type ) {
			$desc = __( 'Lighthouse Notification Email', 'mainwp-lighthouse-extension' );
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
		if ( 'lighthouse_noti_email' == $type ) {
			$template_name = 'emails/mainwp-lighthouse-noti-email.php';
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
		if ( 'lighthouse_noti_email' == $type ) {
			$default_file = MAINWP_LIGHTHOUSE_PLUGIN_DIR . '/templates/emails/mainwp-lighthouse-noti-email.php';
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
		if ( 'lighthouse_noti_email' == $type ) {
			$message = $overrided ? esc_html__( 'This template has been overridden and can be found in:', 'mainwp-lighthouse-extension' ) . ' <code>wp-content/uploads/mainwp/templates/' . $templ . '</code>' : esc_html__( 'To override and edit this email template copy:', 'mainwp-lighthouse-extension' ) . ' <code>mainwp-lighthouse-extension/templates/' . $templ . '</code> ' . esc_html__( 'to the folder:', 'mainwp-lighthouse-extension' ) . ' <code>wp-content/uploads/mainwp/templates/' . $templ . '</code>';
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
		if ( 'emails/mainwp-lighthouse-noti-email.php' == $template_base_name ) {
			$template_path = MAINWP_LIGHTHOUSE_PLUGIN_DIR . '/templates/';
		}
		return $template_path;
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
		$columns['lighthouse_desktop_score'] = '<i class="desktop icon"></i> ' . __( 'Lighthouse', 'mainwp-lighthouse-extension' );
		$columns['lighthouse_mobile_score']  = '<i class="mobile alternate icon"></i> ' . __( 'Lighthouse', 'mainwp-lighthouse-extension' );
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
		$lighthouse = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $item['id'] );

		if ( ! empty( $lighthouse ) ) {
			$strategy       = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
			$strategy_child = $strategy;
			if ( $lighthouse->override == 1 ) {
				$strategy_child = $lighthouse->strategy;
			}

			if ( 'both' == $strategy || 'desktop' == $strategy ) {
				if ( 'mobile' == $strategy_child ) {
					$item['lighthouse_desktop_score'] = '<div class="ui grey label" data-tooltip="Disabled in individual site settings." data-inverted="" data-position="left center">N/A</div>';
				} else {
					$item['lighthouse_desktop_score'] = '<a href="admin.php?page=ManageSitesLighthouse&id=' . $item['id'] . '" class="ui ' . MainWP_Lighthouse_Utility::score_color_code( $lighthouse->desktop_score ) . ' label" data-tooltip="Lighthouse desktop performance score. Last audit: ' . MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse->desktop_last_modified ) ) . '" data-inverted="" data-position="left center">' . $lighthouse->desktop_score . '</a>';
				}
			}

			if ( 'both' == $strategy || 'mobile' == $strategy ) {
				if ( 'desktop' == $strategy_child ) {
					$item['lighthouse_mobile_score'] = '<div class="ui grey label" data-tooltip="Disabled in individual site settings." data-inverted="" data-position="left center">N/A</div>';
				} else {
					$item['lighthouse_mobile_score'] = '<a href="admin.php?page=ManageSitesLighthouse&id=' . $item['id'] . '" class="ui ' . MainWP_Lighthouse_Utility::score_color_code( $lighthouse->mobile_score ) . ' label" data-tooltip="Lighthouse desktop performance score. Last audit: ' . MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse->mobile_last_modified ) ) . '" data-inverted="" data-inverted="" data-position="left center">' . $lighthouse->mobile_score . '</a>';
				}
			}

			if ( ! isset( $item['lighthouse_desktop_score'] ) ) {
				$item['lighthouse_desktop_score'] = '<div class="ui grey label" data-tooltip="Lighthose score not found. Run audit again." data-inverted="" data-position="left center">N/A</div>';
			}

			if ( ! isset( $item['lighthouse_mobile_score'] ) ) {
				$item['lighthouse_mobile_score'] = '<div class="ui grey label" data-tooltip="Lighthose score not found. Run audit again." data-inverted="" data-position="left center">N/A</div>';
			}
		} else {
			$item['lighthouse_desktop_score'] = '<div class="ui grey label" data-tooltip="Empty Lighthouse data. Run audits first." data-inverted="" data-position="left center">N/A</div>';
			$item['lighthouse_mobile_score']  = '<div class="ui grey label" data-tooltip="Empty Lighthouse data. Run audits first." data-inverted="" data-position="left center">N/A</div>';
		}
		return $item;
	}

	/**
	 * Sync Others Data
	 *
	 * Syncs the Lighthose info to child sites.
	 *
	 * @param array $data Data to sync.
	 * @param array $pWebsite Child site.
	 *
	 * @return array $data Data to sync.
	 */
	public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		  $data['syncLighthouseData'] = 1;
		  return $data;
	}
}
