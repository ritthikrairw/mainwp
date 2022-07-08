<?php

class MainWP_Wptc_ExcludeOption extends MainWP_Wptc_Exclude {
	protected $config;
	protected $logger;

	private $db;


	private $bulk_limit;

    public function __construct() {
		$this->db = MainWP_WPTC_Factory::db();
		$this->bulk_limit = 500;

		$this->config = MainWP_WPTC_Base_Factory::get('Wptc_Exclude_Config');
	}

    public function get_tables($exc_wp_tables = false) {

        MainWP_TimeCapsule::ajax_check_data();

        $websiteId = intval($_REQUEST['timecapsuleSiteID']);

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
            'mwp_action' => 'get_tables',
            'exc_wp_tables' => $exc_wp_tables
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

		if ( is_array( $information ) && isset( $information['result'] ) ) {
			die( json_encode( $information['result'] ) );
		}
        die( json_encode( array( 'error' => 'Undefined error.', 'extra' => $information) ) );
	}

}