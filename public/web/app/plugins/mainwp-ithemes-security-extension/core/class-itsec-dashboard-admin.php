<?php

/**
 * Brand plugins with iThemes sidebar items in the admin
 *
 * @version 1.0
 */
class MainWP_ITSEC_Dashboard_Admin {

	function __construct() {

		if ( is_admin() ) {

			$this->initialize();

		}

	}

	/**
	 * Enqueue CSS for iThemes Security dashboard
	 *
	 * @return void
	 */
	public function enqueue_admin_css() {

	}

	/**
	 * Initializes all admin functionality.
	 *
	 * @since 4.0
	 *
	 * @param MainWP_ITSEC_Core $core The $itsec_core instance
	 *
	 * @return void
	 */
	private function initialize() {            

	}

	public function getMetabox($metaboxes) {          
		
	}

	/**
	 * Display security status
	 *
	 * @return void
	 */
	public function metabox_normal_status() {                
		

	}
        
	/**
	 * Display the system information metabox
	 *
	 * @return void
	 */
	public function metabox_normal_system_info() {

		require_once( 'content/system.php' );

	}

	/**
	 * Displays required status array
	 *
	 * @since 4.0
	 *
	 * @param array  $status_array array of statuses
	 * @param string $button_text  string for button
	 * @param string $button_class string for button
	 *
	 * @return void
	 */
	private function status_loop( $status_array, $button_text, $button_class ) {
                $site_id = isset($_GET['dashboard']) ? $_GET['dashboard'] : 0;               
		foreach ( $status_array as $status ) {
                        $url = "&id=" . $site_id . "&tab=";
			if ( isset( $status['advanced'] ) && $status['advanced'] === true ) {
				//$page = 'advanced';
                                $url .= "advanced";
			} elseif ( isset( $status['pro'] ) && $status['pro'] === true ) {
				$page = 'pro';
			} else {
				//$page = 'settings';
                                $url .= "settings";
			}

			if ( strpos( $status['link'], 'http:' ) === false && strpos( $status['link'], '?page=' ) === false ) {

				//$setting_link = '?page=toplevel_page_itsec_' . $page . $status['link'];
                                $setting_link = '?page=ManageSitesiThemes' . $url . $status['link'];

			} else {

				$setting_link = $status['link'];

			}

			printf( '<li><p>%s</p><div class="itsec_status_action"><a class="button-%s" href="%s">%s</a></div></li>', $status['text'], $button_class, $setting_link, $button_text );

		}

	}

}