<?php

final class MainWP_ITSEC_Response {
	private static $instance = false;

	private $response;
	private $errors;
	private $messages;
	private $success;
	private $js_function_calls;
	private $show_default_success_message;
	private $show_default_error_message;
	private $redirect;
	private $close_modal;
	private $mainwp_response;


	private function __construct() {
		$this->reset_to_defaults();

		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	public static function set_success( $success ) {
		$self = self::get_instance();

		$old_success   = $self->success;
		$self->success = (bool) $success;

		return $old_success;
	}

	public static function is_success() {
		$self = self::get_instance();

		return $self->success;
	}

	public static function set_response( $response ) {
		$self = self::get_instance();

		$old_response   = $self->response;
		$self->response = $response;

		return $old_response;
	}

	public static function set_mainwp_response( $response ) {
		$self = self::get_instance();

		$old_mainwp_response   = $self->mainwp_response;
		$self->mainwp_response = $response;

		return $old_mainwp_response;
	}

	public static function get_response() {
		$self = self::get_instance();

		return $self->response;
	}

	public static function add_errors( $errors ) {
		foreach ( $errors as $error ) {
			self::add_error( $error );
		}
	}

	public static function add_error( $error ) {
		$self = self::get_instance();

		$self->errors[] = $error;
	}

	public static function get_errors() {
		$self = self::get_instance();

		return $self->errors;
	}

	public static function get_error_count() {
		$self = self::get_instance();

		return count( $self->errors );
	}

	public static function add_messages( $messages ) {
		foreach ( $messages as $message ) {
			self::add_message( $message );
		}
	}

	public static function add_message( $message ) {
		$self = self::get_instance();

		$self->messages[] = $message;
	}

	public static function get_messages() {
		$self = self::get_instance();

		return $self->messages;
	}

	public static function add_js_function_call( $js_function, $args = null ) {
		$self = self::get_instance();

		if ( is_null( $args ) ) {
			$self->js_function_calls[] = array( $js_function );
		} else {
			$self->js_function_calls[] = array( $js_function, $args );
		}
	}

	public static function get_js_function_calls() {
		$self = self::get_instance();

		return $self->js_function_calls;
	}

	public static function set_show_default_success_message( $show_default_success_message ) {
		$self = self::get_instance();

		$old_show_default_success_message   = $self->show_default_success_message;
		$self->show_default_success_message = $show_default_success_message;

		return $old_show_default_success_message;
	}

	public static function get_show_default_success_message() {
		$self = self::get_instance();

		return $self->show_default_success_message;
	}

	public static function set_show_default_error_message( $show_default_error_message ) {
		$self = self::get_instance();

		$old_show_default_error_message   = $self->show_default_error_message;
		$self->show_default_error_message = $show_default_error_message;

		return $old_show_default_error_message;
	}

	public static function get_show_default_error_message() {
		$self = self::get_instance();

		return $self->show_default_error_message;
	}

	public static function prevent_modal_close() {
		$self = self::get_instance();

		$self->close_modal = false;
	}

	public static function reload_module( $module ) {
		$self = self::get_instance();

		$self->add_js_function_call( 'reloadModule', $module );
	}


	public static function redirect( $redirect ) {
		$self = self::get_instance();

		$self->redirect = $redirect;
	}


	public static function send_json() {
		$self = self::get_instance();

		if ( is_wp_error( $self->response ) ) {
			$self->add_error( $self->response );
			$self->set_response( null );
		}

		$data = array(
			'source'          => 'MainWP_ITSEC_Response',
			'success'         => $self->success,
			'response'        => $self->response,
			'errors'          => self::get_error_strings( $self->errors ),
			'messages'        => $self->messages,
			'functionCalls'   => $self->js_function_calls,
			'redirect'        => $self->redirect,
			'closeModal'      => $self->close_modal,
			'mainwp_response' => $self->mainwp_response,
		);

		wp_send_json( $data );
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public function reset_to_defaults() {
		$this->response                     = null;
		$this->errors                       = array();
		$this->messages                     = array();
		$this->success                      = true;
		$this->js_function_calls            = array();
		$this->show_default_success_message = true;
		$this->show_default_error_message   = true;
		$this->redirect                     = false;
		$this->close_modal                  = true;
		$this->mainwp_response              = array();
		$this->method                       = null;
	}

	public static function get_error_strings( $error ) {
		if ( is_string( $error ) ) {
			return array( $error );
		} elseif ( is_a( $error, 'WP_Error' ) ) {
			/* translators: 1: error message, 2: error code */
			$format = __( '%1$s <span class="itsec-error-code">(%2$s)</span>', 'l10n-mainwp-ithemes-security-extension' );
			$errors = array();

			foreach ( $error->get_error_codes() as $code ) {
				$message  = implode( ' ', (array) $error->get_error_messages( $code ) );
				$errors[] = sprintf( $format, $message, $code ) . ' ';
			}

			return $errors;
		} elseif ( is_array( $error ) ) {
			$errors = array();

			foreach ( $error as $error_item ) {
				$new_errors = self::get_error_strings( $error_item );
				$errors     = array_merge( $errors, $new_errors );
			}

			return $errors;
		}

		/* translators: 1: variable type */
		return array( sprintf( __( 'Unknown error type received: %1$s.', 'l10n-mainwp-ithemes-security-extension' ), gettype( $error ) ) );
	}

	public function shutdown() {

	}
}
