<?php


final class MainWP_ITSEC_Admin_Page_Loader {

	private $page_id;

	public function __construct() {
		add_action( 'wp_ajax_mainwp_itsec_settings_page', array( $this, 'handle_ajax_request' ) );
		add_action( 'wp_ajax_mainwp_itsec_logs_page', array( $this, 'handle_ajax_request' ) );
		add_action( 'wp_ajax_mainwp-itsec-set-user-setting', array( $this, 'handle_user_setting' ) );
		// Filters for validating user settings
		add_filter( 'mainwp-itsec-user-setting-valid-itsec-settings-view', array( $this, 'validate_view' ), null, 2 );
	}

	public static function load() {
		$file = '';
		$page = '';
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( isset( $_REQUEST['action'] ) && preg_match( '/^mainwp_itsec_(.+)_page$/', $_REQUEST['action'], $match ) ) {
				$page = $match[1];
			}
		} elseif ( isset( $_GET['tab'] ) ) {
			$page = $_GET['tab'];
		} else {
			if ( MainWP_IThemes_Security::is_manage_site() ) {
				$page = 'settings';
			}
		}

		$file = dirname( __FILE__ ) . '/page-settings.php';
		require_once $file;
	}

	public function handle_ajax_request() {
		self::load();
		do_action( 'mainwp-itsec-page-ajax' );
	}

	public function handle_user_setting() {
		$whitelist_settings = array(
			'mainwp-itsec-settings-view',
		);

		if ( in_array( $_REQUEST['setting'], $whitelist_settings ) ) {
			$_REQUEST['setting'] = sanitize_title_with_dashes( $_REQUEST['setting'] );

			// Verify nonce is valid and for this setting, and allow a filter to
			if ( wp_verify_nonce( $_REQUEST['mainwp-itsec-user-setting-nonce'], 'set-user-setting-' . $_REQUEST['setting'] ) &&
				apply_filters( 'mainwp-itsec-user-setting-valid-' . $_REQUEST['setting'], true, $_REQUEST['value'] ) ) {

				if ( false !== update_user_meta( get_current_user_id(), $_REQUEST['setting'], $_REQUEST['value'] ) ) {
					wp_send_json_success();
				}
			}
		}
		wp_send_json_error();
	}

	public function validate_view( $valid, $view ) {
		return in_array( $view, array( 'grid', 'list' ) );
	}
}

new MainWP_ITSEC_Admin_Page_Loader();
