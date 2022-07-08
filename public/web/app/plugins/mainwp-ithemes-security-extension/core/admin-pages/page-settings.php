<?php

final class MainWP_ITSEC_Settings_Page {
	private $version = 1.5;
	private static $instance = null;
	private $self_url     = '';
	private $modules      = array();
	private $widgets      = array();
	private $translations = array();

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self(); 
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'mainwp-itsec-settings-page-register-module', array( $this, 'register_module' ) );
		add_action( 'mainwp-itsec-settings-page-register-widget', array( $this, 'register_widget' ) );

		add_action( 'mainwp-itsec-page-ajax', array( $this, 'handle_ajax_request' ) );
		add_action( 'admin_print_scripts', array( $this, 'add_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'add_styles' ) );

		add_filter( 'admin_body_class', array( $this, 'add_settings_classes' ) );

		$this->set_translation_strings();

		if ( ! empty( $_GET['enable'] ) && ! empty( $_GET['mainwp-itsec-enable-nonce'] ) && wp_verify_nonce( $_GET['mainwp-itsec-enable-nonce'], 'itsec-enable-' . $_GET['enable'] ) ) {
			MainWP_ITSEC_Modules::activate( $_GET['enable'] );
		}

		require_once dirname( __FILE__ ) . '/module-settings.php';

		require_once MainWP_ITSEC_Core::get_core_dir() . '/lib/form.php';

		do_action( 'mainwp-itsec-settings-page-init' );
		do_action( 'mainwp-itsec-settings-page-register-modules' );
		do_action( 'mainwp-itsec-settings-page-register-widgets' );

		if ( ! empty( $_POST ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$this->handle_save_post();
		}
	}

	public function add_settings_classes( $classes ) {
		

		if ( MainWP_ITSEC_Modules::get_setting( 'global', 'write_files' ) ) {
			$classes .= ' itsec-write-files-enabled';
		} else {
			$classes .= ' itsec-write-files-disabled';
		}

		$classes = trim( $classes );

		return $classes;
	}

	public function add_scripts() {
		foreach ( $this->modules as $id => $module ) {
			$module->enqueue_scripts_and_styles();
		}

		foreach ( $this->widgets as $id => $widget ) {
			$widget->enqueue_scripts_and_styles();
		}

		$vars = array(
			'ajax_action'         => 'mainwp_itsec_settings_page',
			'ajax_nonce'          => wp_create_nonce( 'mainwp-itsec-settings-nonce' ),
			'translations'        => $this->translations,
			'settings_page_url'   => MainWP_ITSEC_Core::get_settings_page_url( false ),
			'show_security_check' => MainWP_ITSEC_Modules::get_setting( 'global', 'show_security_check' ),
		);

		if ( $vars['show_security_check'] ) {
			MainWP_ITSEC_Modules::set_setting( 'global', 'show_security_check', false );

			if ( ! empty( $_GET['module'] ) && 'security-check' === $_GET['module'] ) {
				$vars['show_security_check'] = false;
			}
		}

		if ( MainWP_IThemes_Security::is_manage_site() ) {
			$vars['ithemeSiteID']   = MainWP_IThemes_Security::get_manage_site_id();
			$vars['individualSite'] = 1;
		}

		wp_enqueue_script( 'mainwp-itsec-settings-page-script', plugins_url( 'js/script.js', __FILE__ ), array(), $this->version, true );
		wp_localize_script( 'mainwp-itsec-settings-page-script', 'mainwp_itsec_page', $vars );
	}

	public function add_styles() {
		wp_enqueue_style( 'mainwp-itsec-settings-page-style', plugins_url( 'css/style.css', __FILE__ ), array(), $this->version );
	}

	private function set_translation_strings() {
		$this->translations = array(
			'save_settings'     => __( 'Save Settings', 'l10n-mainwp-ithemes-security-extension' ),
			'close_settings'    => __( 'Close', 'l10n-mainwp-ithemes-security-extension' ),
			'show_settings'     => __( 'Configure Settings', 'l10n-mainwp-ithemes-security-extension' ),
			'hide_settings'     => __( 'Hide Settings', 'l10n-mainwp-ithemes-security-extension' ),
			'show_description'  => __( 'Learn More', 'l10n-mainwp-ithemes-security-extension' ),
			'hide_description'  => __( 'Hide Details', 'l10n-mainwp-ithemes-security-extension' ),
			'show_information'  => __( 'Show Details', 'l10n-mainwp-ithemes-security-extension' ),
			'activate'          => __( 'Enable', 'l10n-mainwp-ithemes-security-extension' ),
			'deactivate'        => __( 'Disable', 'l10n-mainwp-ithemes-security-extension' ),
			'error'             => __( 'Error', 'l10n-mainwp-ithemes-security-extension' ),

			/* translators: 1: module name */
			'successful_save'   => __( 'Settings saved successfully for %1$s.', 'l10n-mainwp-ithemes-security-extension' ),

			'ajax_invalid'      => new WP_Error( 'itsec-settings-page-invalid-ajax-response', __( 'An "invalid format" error prevented the request from completing as expected. The format of data returned could not be recognized. This could be due to a plugin/theme conflict or a server configuration issue.', 'l10n-mainwp-ithemes-security-extension' ) ),

			'ajax_forbidden'    => new WP_Error( 'itsec-settings-page-forbidden-ajax-response: %1$s "%2$s"', __( 'A "request forbidden" error prevented the request from completing as expected. The server returned a 403 status code, indicating that the server configuration is prohibiting this request. This could be due to a plugin/theme conflict or a server configuration issue. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings or server configuration that could account for this AJAX request being blocked.', 'l10n-mainwp-ithemes-security-extension' ) ),

			'ajax_not_found'    => new WP_Error( 'itsec-settings-page-not-found-ajax-response: %1$s "%2$s"', __( 'A "not found" error prevented the request from completing as expected. The server returned a 404 status code, indicating that the server was unable to find the requested admin-ajax.php file. This could be due to a plugin/theme conflict, a server configuration issue, or an incomplete WordPress installation. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings, alter server configurations, or reinstall WordPress.', 'l10n-mainwp-ithemes-security-extension' ) ),

			'ajax_server_error' => new WP_Error( 'itsec-settings-page-server-error-ajax-response: %1$s "%2$s"', __( 'A "internal server" error prevented the request from completing as expected. The server returned a 500 status code, indicating that the server was unable to complete the request due to a fatal PHP error or a server problem. This could be due to a plugin/theme conflict, a server configuration issue, a temporary hosting issue, or invalid custom PHP modifications. Please check your server\'s error logs for details about the source of the error and contact your hosting company for assistance if required.', 'l10n-mainwp-ithemes-security-extension' ) ),

			'ajax_unknown'      => new WP_Error( 'itsec-settings-page-ajax-error-unknown: %1$s "%2$s"', __( 'An unknown error prevented the request from completing as expected. This could be due to a plugin/theme conflict or a server configuration issue.', 'l10n-mainwp-ithemes-security-extension' ) ),

			'ajax_timeout'      => new WP_Error( 'itsec-settings-page-ajax-error-timeout: %1$s "%2$s"', __( 'A timeout error prevented the request from completing as expected. The site took too long to respond. This could be due to a plugin/theme conflict or a server configuration issue.', 'l10n-mainwp-ithemes-security-extension' ) ),

			'ajax_parsererror'  => new WP_Error( 'itsec-settings-page-ajax-error-parsererror: %1$s "%2$s"', __( 'A parser error prevented the request from completing as expected. The site sent a response that jQuery could not process. This could be due to a plugin/theme conflict or a server configuration issue.', 'l10n-mainwp-ithemes-security-extension' ) ),
		);

		foreach ( $this->translations as $key => $message ) {
			if ( is_wp_error( $message ) ) {
				$messages                   = MainWP_ITSEC_Response::get_error_strings( $message );
				$this->translations[ $key ] = $messages[0];
			}
		}
	}

	public function handle_ajax_request() {
		global $mainwp_itsec_globals;

		if ( WP_DEBUG ) {
			ini_set( 'display_errors', 1 );
		}

		$method = ( isset( $_POST['method'] ) && is_string( $_POST['method'] ) ) ? $_POST['method'] : '';
		$module = ( isset( $_POST['module'] ) && is_string( $_POST['module'] ) ) ? $_POST['module'] : '';

		if ( false === check_ajax_referer( 'mainwp-itsec-settings-nonce', 'nonce', false ) ) {
			MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-page-failed-nonce', __( 'A nonce security check failed, preventing the request from completing as expected. Please try reloading the page and trying again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
		} elseif ( ! MainWP_ITSEC_Core::current_user_can_manage() ) {
			MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-page-insufficient-privileges', __( 'A permissions security check failed, preventing the request from completing as expected. The currently logged in user does not have sufficient permissions to make this request. Please try reloading the page and trying again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
		} elseif ( empty( $method ) ) {
			MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-page-missing-method', __( 'The server did not receive a valid request. The required "method" argument is missing. Please try again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
		} elseif ( 'save' === $method ) {
			$this->handle_save_post();
		} elseif ( empty( $module ) ) {
			MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-page-missing-module', __( 'The server did not receive a valid request. The required "module" argument is missing. Please try again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
		} elseif ( 'activate' === $method ) {
			MainWP_ITSEC_Response::set_response( MainWP_ITSEC_Modules::activate( $module ) );
		} elseif ( 'deactivate' === $method ) {
			MainWP_ITSEC_Response::set_response( MainWP_ITSEC_Modules::deactivate( $module ) );
		} elseif ( 'is_active' === $method ) {
			MainWP_ITSEC_Response::set_response( MainWP_ITSEC_Modules::is_active( $module ) );
		} elseif ( 'get_refreshed_module_settings' === $method ) {
			MainWP_ITSEC_Response::set_response( $this->get_module_settings( $module ) );
		} elseif ( 'get_refreshed_widget_settings' === $method ) {
			MainWP_ITSEC_Response::set_response( $this->get_widget_settings( $module ) );
		} elseif ( 'handle_module_request' === $method ) {
			if ( $module == 'file-change' && isset( $_POST['data'] ) && $_POST['data']['method'] == 'one-time-scan' ) {
				$mainwp_result = MainWP_IThemes_Security::get_instance()->do_scan_file_change();
			} elseif ( $module == 'security-check' && isset( $_POST['data'] ) && $_POST['data']['method'] == 'secure-site' ) {
				$mainwp_result = MainWP_IThemes_Security::get_instance()->do_security_site();
			} elseif ( $module == 'security-check' && isset( $_POST['data'] ) && $_POST['data']['method'] == 'activate-network-brute-force' ) {
				$mainwp_result = MainWP_IThemes_Security::get_instance()->do_activate_network_brute_force();
			} elseif ( $module == 'network-brute-force' && isset( $_POST['data'] ) && $_POST['data']['method'] == 'reset-api-key' ) {
				$mainwp_result = MainWP_IThemes_Security::get_instance()->do_reset_api_key();
			} elseif ( isset( $this->modules[ $module ] ) ) {
				if ( isset( $_POST['data'] ) ) {
					$returned_value = $this->modules[ $module ]->handle_ajax_request( $_POST['data'] );

					if ( ! is_null( $returned_value ) ) {
						MainWP_ITSEC_Response::set_response( $returned_value );
					}
				} else {
					MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-page-module-request-missing-data', __( 'The server did not receive a valid request. The required "data" argument for the module is missing. Please try again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
				}
			} else {
				MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-page-module-request-invalid-module', __( "The server did not receive a valid request. The supplied module, \"$module\", does not exist. Please try again.", 'l10n-mainwp-ithemes-security-extension' ) ) );
			}
		} elseif ( 'mainwp_update_module_status' == $method ) {
			$mainwp_result = MainWP_IThemes_Security::get_instance()->update_module_status( $module );
		} elseif ( 'mainwp_itheme_load_sites' == $method ) {
			$mainwp_result = MainWP_IThemes_Security::get_instance()->do_load_sites( true );
		} elseif ( 'mainwp_itheme_save_settings' == $method || 'mainwp_itheme_load_files_permissions' == $method ) {
			$mainwp_result = MainWP_IThemes_Security::get_instance()->do_save_settings( $module );
		} elseif ( 'mainwp_itheme_change_database_prefix' == $method ) {
			$mainwp_result = MainWP_IThemes_Security::get_instance()->do_change_database_prefix();
		} elseif ( 'mainwp_itheme_scan_file_change' == $method ) {
			$mainwp_result = MainWP_IThemes_Security::get_instance()->do_scan_file_change( $with_html = false );
		} elseif ( 'mainwp_itheme_secure_site' == $method ) {
			$mainwp_result = MainWP_IThemes_Security::get_instance()->do_security_site();
		} elseif ( 'mainwp_itheme_reset_api_key' == $method ) {
			$mainwp_result = MainWP_IThemes_Security::get_instance()->do_reset_api_key();
		} else {
			MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-page-unknown-method', __( 'The server did not receive a valid request. An unknown "method" argument was supplied. Please try again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
		}

		if ( isset( $mainwp_result ) && $mainwp_result !== null ) {
			MainWP_ITSEC_Response::set_mainwp_response( $mainwp_result );
		}

		if ( 'activate' == $method ||
			 'deactivate' == $method ||
			 'mainwp_itheme_change_admin_user' == $method ||
			 'mainwp_itheme_save_settings' == $method ) {
			MainWP_ITSEC_Response::prevent_modal_close();
		}

		MainWP_ITSEC_Response::send_json();
	}

	public function register_module( $module ) {
		if ( ! is_object( $module ) || ! is_a( $module, 'MainWP_ITSEC_Module_Settings_Page' ) ) {
			trigger_error( 'An invalid module was registered.', E_USER_ERROR );
			return;
		}

		if ( isset( $this->modules[ $module->id ] ) ) {
			//trigger_error( "A module with the id of {$module->id} is already registered. Module id's must be unique." );
			return;
		}

		$this->modules[ $module->id ] = $module;
	}

	public function register_widget( $widget ) {

		if ( isset( $this->modules[ $widget->id ] ) ) {
			trigger_error( "A widget with the id of {$widget->id} is registered. Widget id's must be unique from any other module or widget." );
			return;
		}

		if ( isset( $this->widgets[ $widget->id ] ) ) {
			//trigger_error( "A widget with the id of {$widget->id} is already registered. Widget id's must be unique from any other module or widget." );
			return;
		}

		$this->widgets[ $widget->id ] = $widget;
	}


	private function handle_save_post() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			if ( ! isset( $_POST['mainwp-itsec-settings-page'] ) ) {
				return;
			}
			// Only process the nonce when the request is not an AJAX request as the AJAX handler has its own nonce check.
			MainWP_ITSEC_Form::check_nonce( 'mainwp-itsec-settings-page' );
		}

		$post_data         = MainWP_ITSEC_Form::get_post_data();
		$saved             = true;
		$js_function_calls = array();

		
		if ( ! empty( $_POST['module'] ) ) {
			if ( isset( $this->modules[ $_POST['module'] ] ) ) {
				$modules = array( $_POST['module'] => $this->modules[ $_POST['module'] ] );
			} else {
				MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-save-unrecognized-module', sprintf( __( 'The supplied module (%s) is not recognized. The module settings could not be saved.', 'l10n-mainwp-ithemes-security-extension' ), $_POST['module'] ) ) );
				$modules = array();
			}
		} else {
			$modules = $this->modules;
		}

		foreach ( $modules as $id => $module ) {
			if ( isset( $post_data[ $id ] ) ) {				
				$results = $module->handle_form_post( $post_data[ $id ] );
			}
		}

		if ( MainWP_ITSEC_Response::is_success() ) {
			if ( MainWP_ITSEC_Response::get_show_default_success_message() ) {
				MainWP_ITSEC_Response::add_message( __( 'The settings saved successfully.', 'l10n-mainwp-ithemes-security-extension' ) );
			}
		} else {
			if ( MainWP_ITSEC_Response::get_show_default_error_message() ) {
				$error_count = MainWP_ITSEC_Response::get_error_count();

				if ( $error_count > 0 ) {
					MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-data-not-saved', _n( 'The settings could not be saved. Please correct the error above and try again.', 'The settings could not be saved. Please correct the errors above and try again.', $error_count, 'l10n-mainwp-ithemes-security-extension' ) ) );
				} else {
					MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-settings-data-not-saved-missing-error', __( 'The settings could not be saved. Due to an unknown error. Please try refreshing the page and trying again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
				}
			}
		}
	}

	private function get_module_settings( $id, $form = false, $echo = false ) {
		if ( ! isset( $this->modules[ $id ] ) ) {
			$error = new WP_Error( 'itsec-settings-page-get-module-settings-invalid-id', sprintf( __( 'The requested module (%s) does not exist. Settings for it cannot be rendered.', 'l10n-mainwp-ithemes-security-extension' ), $id ) );

			if ( $echo ) {
				MainWP_ITSEC_Lib::show_error_message( $error );
			} else {
				return $error;
			}
		}

		if ( false === $form ) {
			$form = new MainWP_ITSEC_Form();
		}

		$module = $this->modules[ $id ];

		$form->add_input_group( $id );
		$form->set_defaults( $module->get_settings() );

		if ( ! $echo ) {
			ob_start();
		}

		$module->render( $form );

		$form->remove_all_input_groups();

		if ( ! $echo ) {
			return ob_get_clean();
		}
	}

	private function get_widget_settings( $id, $form = false, $echo = false ) {
		if ( ! isset( $this->widgets[ $id ] ) ) {
			$error = new WP_Error( 'mainwp-itsec-settings-page-get-widget-settings-invalid-id', sprintf( __( 'The requested widget (%s) does not exist. Settings for it cannot be rendered.', 'l10n-mainwp-ithemes-security-extension' ), $id ) );

			if ( $echo ) {
				MainWP_ITSEC_Lib::show_error_message( $error );
			} else {
				return $error;
			}
		}

		if ( false === $form ) {
			$form = new MainWP_ITSEC_Form();
		}

		$widget = $this->widgets[ $id ];

		$form->add_input_group( $id );
		$form->set_defaults( $widget->get_defaults() );

		if ( ! $echo ) {
			ob_start();
		}

		$widget->render( $form );

		$form->remove_all_input_groups();

		if ( ! $echo ) {
			return ob_get_clean();
		}
	}

	public function show_settings_page() {

		$selected_dashboard = false;
		if ( isset($_GET['tab']) && 'dashboard' == $_GET['tab']  ) {
			$selected_dashboard = true;
		}

		$current_tab = '';
		if ( MainWP_IThemes_Security::is_manage_site() ) {
			$current_tab = 'global';
		}

		$form = new MainWP_ITSEC_Form();

		$module_filters = array(
			'all'         => array(
				_x( 'All', 'List all modules', 'l10n-mainwp-ithemes-security-extension' ),
				0,
			),
			'recommended' => array(
				_x( 'Recommended', 'List recommended modules', 'l10n-mainwp-ithemes-security-extension' ),
				0,
			),
			'advanced'    => array(
				_x( 'Advanced', 'List advanced modules', 'l10n-mainwp-ithemes-security-extension' ),
				0,
			),
		);

		$current_type    = isset( $_REQUEST['module_type'] ) ? $_REQUEST['module_type'] : 'recommended';
		$visible_modules = array();

		foreach ( $this->modules as $id => $module ) {
			$module_filters['all'][1]++;

			if ( 'all' === $current_type ) {
				$visible_modules[] = $id;
			}

			if ( isset( $module_filters[ $module->type ] ) ) {
				$module_filters[ $module->type ][1]++;

				if ( $module->type === $current_type ) {
					$visible_modules[] = $id;
				}
			}

			$module->enabled       = MainWP_ITSEC_Modules::is_active( $id );
			$module->always_active = MainWP_ITSEC_Modules::is_always_active( $id );
		}
	?>

	<div class="mainwp_info-box-blue" id="mwp_itheme_message_zone" style="display: none"></div>
	<div class="mainwp_info-box-red" id="mwp_itheme_error_zone" style="display: none"></div>
	<div id="itsec-settings-messages-container">
	<?php
		foreach ( MainWP_ITSEC_Response::get_errors() as $error ) {
			MainWP_ITSEC_Lib::show_error_message( $error );
		}

		foreach ( MainWP_ITSEC_Response::get_messages() as $message ) {
			MainWP_ITSEC_Lib::show_status_message( $message );
		}
	?>
	</div>	
	<div class="ui form">
	    <?php $form->start_form( 'itsec-module-settings-form' ); ?>
		<?php $form->add_nonce( 'mainwp-itsec-settings-page' ); ?>
			    <?php foreach ( $this->modules as $id => $module ) : ?>
				<?php
				if ( ! in_array( $id, $visible_modules ) ) {
					// continue;
				}

				$classes = array(
					'itsec-module-type-' . $module->type,
					'itsec-module-type-' . ( $module->enabled ? 'enabled' : 'disabled' ),
				);

				if ( $module->upsell ) {
					$classes[] = 'itsec-module-pro-upsell';
				}

				if ( $module->pro ) {
					$classes[] = 'itsec-module-type-pro';
				}
				
				?>
         		 <div class="ui segment alt tab itsec-module-wrapper <?php echo $module->enabled ? 'itsec-module-type-enabled' : 'itsec-module-type-disabled'; ?> <?php echo ( ( 'global' == $current_tab && 'global' == $id ) ? 'active' : '' ); ?>" data-tab="<?php echo $id; ?>">					
						<div class="ui hidden divider"></div>
						<h3 class="ui dividing header"><?php echo esc_html( $module->title ); ?></h3>
						<div class="ui yellow message mwp_itheme_module_working_status" style="display:none"></div>
						<?php if ( $module->pro ) : ?>
							<div class="itsec-pro-label"><?php _e( 'Pro', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
						<?php endif; ?>													
							<?php $this->get_module_settings( $id, $form, true ); ?>
					<?php if ( ! $module->upsell ) : ?>
						<div class="itsec-modal-content-footer">
							<?php if ( $module->enabled || $module->always_active || $module->information_only ) : ?>
								<?php if ( ! $module->always_active && ! $module->information_only ) : ?>
									<button class="button ui basic green align-right itsec-toggle-activation"><?php echo $this->translations['deactivate']; ?></button>
								<?php 
							endif; ?>
							<?php else : ?>
								<button class="button ui green align-right itsec-toggle-activation"><?php echo $this->translations['activate']; ?></button>
								<?php 
							endif; ?>
							<?php if ( $module->can_save ) : ?>
								<button class="button ui green <?php echo $module->custom_save ? 'itsec-module-settings-custome-save' : 'itsec-module-settings-save'; ?>"><?php echo $this->translations['save_settings']; ?></button>
							<?php endif; ?>	
							<div class="ui hidden divider"></div>	
						</div>
					<?php endif; ?>					  
				  </div>
				  <?php endforeach; ?>			
		<?php $form->end_form(); ?>
		</div>
		<div class="ui divider"></div>
		<button class="ui big green right floating button" id="itsec-module-settings-save-all"><?php _e( 'Save To All Sites', 'l10n-mainwp-ithemes-security-extension' ); ?></button>
		<?php if ( MainWP_IThemes_Security::is_manage_site() ) : ?>
			<input type="button" value="<?php _e( 'Save General Settings To Site', 'l10n-mainwp-ithemes-security-extension' ); ?>" class="ui big green basic button" id="mwp_itheme_btn_savegeneral">&nbsp;
			<div id="itsec_save_all_status" style="display: inline"></div>
		<?php endif; ?>
	
		<script type="text/javascript">			
			jQuery( '#mainwp-ithemes-security-menu a.item' ).tab({'onVisible':function(){
				jQuery( '#mainwp-ithemes-security-dashboard-tab').hide();
				jQuery( '#mainwp-ithemes-side-content').show();
				jQuery( '#mainwp-ithemes-security-settings-tabs' ).show();
			}});
			{function s() {
				
				<?php
					if ( $selected_dashboard ){
					?>
					localStorage.setItem("mainwp-ithemes-hash", "");		
					<?php
					} else {
						$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
						if ( 'backup' == $current_tab ) {
							?>
 							localStorage.setItem("mainwp-ithemes-hash", "backup"); 
							<?php
						}						
					}
					?>
				    var site_id = jQuery('#mainwp_itheme_managesites_site_id').attr('site-id');
					var e = this;
					if (this.$links = document.querySelectorAll("#mainwp-ithemes-security-menu a"), this.$menuItem = null, this.pageId = null, window.onhashchange = function() {
							e.detectID()
						}, window.location.hash) this.detectID();						
					else {
						var default_hash = site_id ? 'global' : 'dashboard-tab';
						var i = localStorage.getItem("mainwp-ithemes-hash");
						i ? (window.location.hash = i, this.detectID()) : ( localStorage.setItem("mainwp-ithemes-hash", default_hash), window.location.hash = "#" + default_hash )
					}
					for (var s = 0; s < this.$links.length; s++) this.$links[s].onclick = function() {
						var t = this.href.split("#")[1];
						if (null != t) window.location.hash = t
					};

					if (this.$menuItem) {					    
						jQuery( '#mainwp-ithemes-security-settings-tabs' ).show();
						this.$menuItem.click(); // semantic will show/hide tabs						
					}
					// clicking on other links with clear saved status
					var r = document.querySelectorAll("#mainwp-page-navigation-wrapper a, #mainwp-main-menu a, #mainwp-sync-sites, #mainwp-ithemes-item-dashboard-tab");
					for (s = 0; s < r.length; s++) r[s].onclick = function() {
						//localStorage.setItem("mainwp-ithemes-hash", "");						
					}					
			   }
			   document.addEventListener("DOMContentLoaded", function() {
				   var t = document.querySelector("#mainwp-ithemes-security-menu");
				   t && new s()
			   }),
			   s.prototype.detectID = function() {
				   this.pageId = window.location.hash.split("#")[1], localStorage.setItem("mainwp-ithemes-hash", this.pageId), this.$menuItem = document.querySelector('a[data-tab="' + this.pageId  +'"]')
			   }
		   };
		</script>
		<?php
	}
}

new MainWP_ITSEC_Settings_Page();
