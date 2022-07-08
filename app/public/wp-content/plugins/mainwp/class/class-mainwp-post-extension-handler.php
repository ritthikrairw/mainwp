<?php
/**
 * This class extends the MainWP Post Base Handler class
 * to add support for MainWP Extensions.
 *
 * @package     MainWP/Dashboard
 */

namespace MainWP\Dashboard;

/**
 * Class MainWP_Post_Extension_Handler
 *
 * @package MainWP\Dashboard
 *
 * @uses \MainWP\Dashboard\MainWP_Post_Base_Handler
 */
class MainWP_Post_Extension_Handler extends MainWP_Post_Base_Handler {

	/**
	 * Public static varibale to hold the instance.
	 *
	 * @var null Default value.
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 *
	 * Create public static instance.
	 *
	 * @return self $instance.
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init extensions actions
	 *
	 * @uses \MainWP\Dashboard\MainWP_Extensions::get_class_name()
	 */
	public function init() {

		$this->add_action( 'mainwp_extension_add_menu', array( &$this, 'add_extension_menu' ) );
		$this->add_action( 'mainwp_extension_remove_menu', array( &$this, 'remove_extension_menu_from_mainwp_menu' ) );

		$this->add_action( 'mainwp_extension_api_activate', array( &$this, 'activate_api_extension' ) );
		$this->add_action( 'mainwp_extension_deactivate', array( &$this, 'deactivate_extension' ) );
		$this->add_action( 'mainwp_extension_testextensionapilogin', array( &$this, 'test_extensions_api_login' ) );

		if ( mainwp_current_user_have_right( 'dashboard', 'bulk_install_and_activate_extensions' ) ) {
			$this->add_action( 'mainwp_extension_grabapikey', array( &$this, 'grab_extension_api_key' ) );
			$this->add_action( 'mainwp_extension_saveextensionapilogin', array( &$this, 'save_extensions_api_login' ) );
			$this->add_action( 'mainwp_extension_getpurchased', array( MainWP_Extensions::get_class_name(), 'get_purchased_exts' ) );
			$this->add_action( 'mainwp_extension_downloadandinstall', array( &$this, 'download_and_install' ) );
			$this->add_action( 'mainwp_extension_bulk_activate', array( &$this, 'bulk_activate' ) );
			$this->add_action( 'mainwp_extension_apisslverifycertificate', array( &$this, 'save_api_ssl_verify' ) );
		}

		// Page: ManageSites.
		$this->add_action( 'mainwp_ext_applypluginsettings', array( &$this, 'mainwp_ext_applypluginsettings' ) );
	}

	/**
	 * Apply plugin settings.
	 *
	 * @return mixed success|error.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Manage_Sites_Handler::apply_plugin_settings()
	 */
	public function mainwp_ext_applypluginsettings() {
		$this->check_security( 'mainwp_ext_applypluginsettings' );
		MainWP_Manage_Sites_Handler::apply_plugin_settings();
	}

	/**
	 * Ajax add extension menu.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Extensions_Handler::add_extension_menu()
	 */
	public function add_extension_menu() {
		$this->check_security( 'mainwp_extension_add_menu' );
		$slug = isset( $_POST['slug'] ) ? wp_unslash( $_POST['slug'] ) : '';
		MainWP_Extensions_Handler::add_extension_menu( $slug );
		die( wp_json_encode( array( 'result' => 'SUCCESS' ) ) );
	}

	/**
	 * Activate MainWP Extension.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Api_Manager::license_key_activation()
	 * @uses \MainWP\Dashboard\MainWP_Deprecated_Hooks::maybe_handle_deprecated_hook()
	 */
	public function activate_api_extension() {
		$this->check_security( 'mainwp_extension_api_activate' );
		MainWP_Deprecated_Hooks::maybe_handle_deprecated_hook();
		$api_slug = isset( $_POST['slug'] ) ? dirname( $_POST['slug'] ) : '';
		$api_key  = isset( $_POST['key'] ) ? sanitize_text_field( wp_unslash( $_POST['key'] ) ) : '';
		$result   = MainWP_Api_Manager::instance()->license_key_activation( $api_slug, $api_key );
		wp_send_json( $result );
	}

	/**
	 * Deactivate MainWP Extension.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Api_Manager::license_key_deactivation()
	 * @uses \MainWP\Dashboard\MainWP_Deprecated_Hooks::maybe_handle_deprecated_hook()
	 */
	public function deactivate_extension() {
		$this->check_security( 'mainwp_extension_deactivate' );
		MainWP_Deprecated_Hooks::maybe_handle_deprecated_hook();
		$api_slug = isset( $_POST['slug'] ) ? dirname( wp_unslash( $_POST['slug'] ) ) : '';
		$api_key  = isset( $_POST['api_key'] ) ? wp_unslash( $_POST['api_key'] ) : '';
		$result   = MainWP_Api_Manager::instance()->license_key_deactivation( $api_slug, $api_key );
		wp_send_json( $result );
	}

	/**
	 * Grab MainWP Extension API Key.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Api_Manager::grab_license_key()
	 */
	public function grab_extension_api_key() {
		$this->check_security( 'mainwp_extension_grabapikey' );
		$api_slug       = isset( $_POST['slug'] ) ? dirname( wp_unslash( $_POST['slug'] ) ) : '';
		$master_api_key = isset( $_POST['master_api_key'] ) ? wp_unslash( $_POST['master_api_key'] ) : '';
		$result         = MainWP_Api_Manager::instance()->grab_license_key( $api_slug, $master_api_key );
		wp_send_json( $result );
	}

	/**
	 * Save MainWP Extensions API Login details for future logins.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Api_Manager::verify_mainwp_api()
	 * @uses \MainWP\Dashboard\MainWP_Api_Manager_Password_Management::encrypt_string()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function save_extensions_api_login() { // phpcs:ignore -- Current complexity is the only way to achieve desired results, pull request solutions appreciated.
		$this->check_security( 'mainwp_extension_saveextensionapilogin' );
		$api_login_history = isset( $_SESSION['api_login_history'] ) ? $_SESSION['api_login_history'] : array();

		$new_api_login_history = array();
		$requests              = 0;

		foreach ( $api_login_history as $api_login ) {
			if ( $api_login['time'] > ( time() - 1 * 60 ) ) {
				$new_api_login_history[] = $api_login;
				$requests++;
			}
		}

		if ( 4 < $requests ) {
			$_SESSION['api_login_history'] = $new_api_login_history;
			die( wp_json_encode( array( 'error' => __( 'Too many requests', 'mainwp' ) ) ) );
		} else {
			$new_api_login_history[]       = array( 'time' => time() );
			$_SESSION['api_login_history'] = $new_api_login_history;
		}

		$api_key = isset( $_POST['api_key'] ) ? trim( $_POST['api_key'] ) : false;

		if ( '' === $api_key && false !== $api_key ) {
			MainWP_Utility::update_option( 'mainwp_extensions_master_api_key', '' );
		}

		if ( empty( $api_key ) ) {
			die( wp_json_encode( array( 'saved' => 1 ) ) );
		}

		$result = array();
		try {
			$test = MainWP_Api_Manager::instance()->verify_mainwp_api( $api_key );
		} catch ( \Exception $e ) {
			$return['error'] = $e->getMessage();
			die( wp_json_encode( $return ) );
		}

		if ( is_array( $test ) && isset( $test['retry_action'] ) ) {
			wp_send_json( $test );
		}

		$result     = json_decode( $test, true );
		$save_login = ( isset( $_POST['saveLogin'] ) && ( 1 == $_POST['saveLogin'] ) ) ? true : false;
		$return     = array();
		if ( is_array( $result ) ) {
			if ( isset( $result['success'] ) && $result['success'] ) {
				if ( $save_login ) {
					if ( empty( $api_key ) && isset( $result['master_api_key'] ) ) {
						$api_key = $result['master_api_key'];
					}
					$enscrypt_api_key = MainWP_Api_Manager_Password_Management::encrypt_string( $api_key );
					MainWP_Utility::update_option( 'mainwp_extensions_master_api_key', $enscrypt_api_key );
					MainWP_Utility::update_option( 'mainwp_extensions_api_save_login', true );
					$plan_info = isset( $result['plan_info'] ) ? wp_json_encode( $result['plan_info'] ) : '';
					MainWP_Utility::update_option( 'mainwp_extensions_plan_info', $plan_info );
				}
				$return['result'] = 'SUCCESS';
			} elseif ( isset( $result['error'] ) ) {
				$return['error'] = $result['error'];
			}
		}

		if ( ! $save_login ) {
			MainWP_Utility::update_option( 'mainwp_extensions_api_save_login', '' );
			MainWP_Utility::update_option( 'mainwp_extensions_plan_info', '' );
		}

		die( wp_json_encode( $return ) );
	}

	/**
	 * Save whenther or not to verify MainWP API SSL certificate.
	 *
	 * @return void
	 *
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function save_api_ssl_verify() {
		$this->check_security( 'mainwp_extension_apisslverifycertificate' );
		MainWP_Utility::update_option( 'mainwp_api_sslVerifyCertificate', isset( $_POST['api_sslverify'] ) ? intval( $_POST['api_sslverify'] ) : 0 );
		die( wp_json_encode( array( 'saved' => 1 ) ) );
	}

	/**
	 * Test Extension page MainWP.com login details.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Api_Manager::verify_mainwp_api()
	 * @uses \MainWP\Dashboard\MainWP_Api_Manager_Password_Management::decrypt_string()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function test_extensions_api_login() {
		$this->check_security( 'mainwp_extension_testextensionapilogin' );
		$enscrypt_api_key = get_option( 'mainwp_extensions_master_api_key', false );
		$api_key          = false;
		if ( false !== $enscrypt_api_key ) {
			$api_key = ! empty( $enscrypt_api_key ) ? MainWP_Api_Manager_Password_Management::decrypt_string( $enscrypt_api_key ) : '';
		}

		$result = array();
		try {
			$test = MainWP_Api_Manager::instance()->verify_mainwp_api( $api_key );
		} catch ( \Exception $e ) {
			$return['error'] = $e->getMessage();
			die( wp_json_encode( $return ) );
		}

		if ( is_array( $test ) && isset( $test['retry_action'] ) ) {
			wp_send_json( $test );
		}

		$result = json_decode( $test, true );
		$return = array();
		if ( is_array( $result ) ) {
			if ( isset( $result['success'] ) && $result['success'] ) {
				$return['result'] = 'SUCCESS';
			} elseif ( isset( $result['error'] ) ) {
				$return['error'] = $result['error'];
			}
		} else {
			$apisslverify = get_option( 'mainwp_api_sslVerifyCertificate' );
			if ( 1 == $apisslverify ) {
				MainWP_Utility::update_option( 'mainwp_api_sslVerifyCertificate', 0 );
				$return['retry_action'] = 1;
			}
		}
		wp_send_json( $return );
	}

	/**
	 * Download & Install MainWP Extension.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_Extensions_Handler::install_plugin()
	 */
	public function download_and_install() {
		$this->check_security( 'mainwp_extension_downloadandinstall' );
		// phpcs:ignore -- custom setting to install plugin.
		ini_set( 'zlib.output_compression', 'Off' );
		$download_link = isset( $_POST['download_link'] ) ? wp_unslash( $_POST['download_link'] ) : '';

		$return = MainWP_Extensions_Handler::install_plugin( $download_link );

		die( '<mainwp>' . wp_json_encode( $return ) . '</mainwp>' );
	}

	/**
	 * MainWP Extension Bulck Activation.
	 *
	 * @return void
	 */
	public function bulk_activate() {
		$this->check_security( 'mainwp_extension_bulk_activate' );
		$plugins = isset( $_POST['plugins'] ) ? wp_unslash( $_POST['plugins'] ) : false;
		if ( is_array( $plugins ) && 0 < count( $plugins ) ) {
			if ( current_user_can( 'activate_plugins' ) ) {
				activate_plugins( $plugins );
				die( 'SUCCESS' );
			}
		}
		die( 'FAILED' );
	}

	/**
	 * Remove Extensions menu from MainWP Menu.
	 *
	 * @return void
	 *
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function remove_extension_menu_from_mainwp_menu() {
		$this->check_security( 'mainwp_extension_remove_menu' );
		$snMenuExtensions = get_option( 'mainwp_extmenu' );
		if ( ! is_array( $snMenuExtensions ) ) {
			$snMenuExtensions = array();
		}

		$key = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

		if ( ! empty( $key ) && isset( $snMenuExtensions[ $key ] ) ) {
			unset( $snMenuExtensions[ $key ] );
			MainWP_Utility::update_option( 'mainwp_extmenu', $snMenuExtensions );
			do_action( 'mainwp_removed_extension_menu', $key );
			die( wp_json_encode( array( 'result' => 'SUCCESS' ) ) );
		}

		die( - 1 );
	}


}
