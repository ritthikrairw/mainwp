<?php
/**
 * MainWP AUM Controller
 *
 * This file handles all interactions with the DB.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Controller
 */
class MainWP_AUM_Controller {

	public $params = null;

	public $view_vars = array();

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_Controller.
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 *
	 * Get instance.
	 *
	 * @return object $instance Class instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method __construct()
	 *
	 * Contructor.
	 */
	public function __construct() {
	}

	/**
	 * Method dispatch()
	 *
	 * Validates and sets parameters values before render view.
	 *
	 * @param array $params Params array.
	 */
	public function dispatch( $params = array() ) {
		$request_params = $_REQUEST;
		$request_params = self::escape_params( $request_params );
		$params         = array_merge( $request_params, $params );
		$this->params   = $params;
	}

	/**
	 * Method set()
	 *
	 * Sets value for variable.
	 *
	 * @param string $variable_name_or_array Variable name.
	 * @param array  $data Data array.
	 */
	public function set( $variable_name_or_array, $data = null ) {
		if ( is_string( $variable_name_or_array ) ) {
			$this->set_view_var( $variable_name_or_array, $data );
		} elseif ( is_array( $variable_name_or_array ) ) {
			foreach ( $variable_name_or_array as $key => $value ) {
				$this->set_view_var( $key, $value );
			}
		}
	}

	/**
	 * Method set_view_var()
	 *
	 * Set variable values for view.
	 *
	 * @param string $key Key.
	 * @param string $key Value.
	 */
	private function set_view_var( $key, $value ) {
		if ( 'object' == $key ) {
			$this->object = $value;
		}
		$this->view_vars[ $key ] = $value;
	}

	/**
	 * Method render_view()
	 *
	 * Renders view.
	 *
	 * @param string $path Path.
	 * @param array  $options Options array.
	 */
	public function render_view( $path, $options = array() ) {
		$this->view_vars = array_merge( $this->view_vars, $options );
		$this->include_view( $path, $this->view_vars );
	}

	/**
	 * Method include_view().
	 *
	 * Sets include view file.
	 *
	 * @param string $path Path.
	 * @param array  $view_vars View variables.
	 */
	protected function include_view( $path, $view_vars = array() ) {
		if ( is_array( $view_vars ) && isset( $view_vars['this'] ) ) {
			$obj = $view_vars['this'];
			if ( is_object( $obj ) ) {
				foreach ( $obj as $key => $value ) {
					$this->$key = $value;
				}
			}
			unset( $view_vars['this'] );
		}

		extract( $view_vars );

		$filepath = MAINWP_MONITOR_PLUGIN_PATH . 'view/' . $path . '.php';

		if ( file_exists( $filepath ) ) {
			require $filepath;
		}
	}

	/**
	 * Method escape_params()
	 *
	 * @param array $params Parameters.
	 *
	 * @return array $params Parameters.
	 */
	private static function escape_params( $params ) {
		if ( is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				if ( is_string( $value ) ) {
					$params[ $key ] = wp_unslash( $value );
				} elseif ( is_array( $value ) ) {
					$params[ $key ] = self::escape_params( $value );
				}
			}
		}
		return $params;
	}

	/**
	 * Method set_flash()
	 *
	 * Sets notification message.
	 *
	 * @param string $type    Notification type.
	 * @param string $message Notification content.
	 */
	protected function set_flash( $type, $message ) {
		$this->init_flash();
		$_SESSION['mvc_flash'][ $type ] = $message;
	}

	/**
	 * Method unset_flash()
	 *
	 * Unsets notification message.
	 *
	 * @param string $type    Notification type.
	 */
	protected function unset_flash( $type ) {
		$this->init_flash();
		unset( $_SESSION['mvc_flash'][ $type ] );
	}

	/**
	 * Method get_flash()
	 *
	 * Gets notification message.
	 *
	 * @param string $type    Notification type.
	 *
	 * @return string $message Notification content.
	 */
	protected function get_flash( $type ) {
		$this->init_flash();
		$message = empty( $_SESSION['mvc_flash'][ $type ] ) ? null : $_SESSION['mvc_flash'][ $type ];
		return $message;
	}

	/**
	 * Method get_all_flashes()
	 *
	 * Gets all notification message.
	 *
	 * @return string Notifications.
	 */
	protected function get_all_flashes() {
		$this->init_flash();
		return $_SESSION['mvc_flash'];
	}

	/**
	 * Method flash()
	 *
	 * Show notification message.
	 *
	 * @param string $type    Notification type.
	 * @param string $message Notification content.
	 *
	 * @return string Message.
	 */
	public function flash( $type, $message = null ) {
		if ( 1 == func_num_args() ) {
			$message = $this->get_flash( $type );
			$this->unset_flash( $type );
			return $message;
		}
		$this->set_flash( $type, $message );
	}

	/**
	 * Method display_flash()
	 *
	 * Displays notification message.
	 *
	 * @param string $type    Notification type.
	 * @param string $message Notification content.
	 *
	 * @return string Message.
	 */
	public function display_flash() {
		$flashes = $this->get_all_flashes();
		$html    = '';
		if ( ! empty( $flashes ) ) {
			foreach ( $flashes as $type => $message ) {
				$classes   = array();
				$classes[] = $type;
				if ( is_admin() ) {
					if ( 'notice' == $type ) {
						$classes[] = 'yellow';
					}
				}

				$id = 'message';

				if ( in_array( 'error', $classes ) ) {
					$id = 'error';
				}

				$str_classes = implode( ' ', $classes );

				$html .= '<div id="' . $id . '" class="ui ' . implode( ' ', $classes ) . ' message">' . $message . '</div>';

				$this->unset_flash( $type );
			}
		}
		echo $html;
	}

	/**
	 * Method init_flash()
	 *
	 * Initiates flash process.
	 */
	private function init_flash() {
		if ( ! isset( $_SESSION['mvc_flash'] ) ) {
			$_SESSION['mvc_flash'] = array();
		}
	}

	/**
	 * Method ajax_check_permissions()
	 *
	 * Checks permissions via AJAX.
	 *
	 * @param  string $nonce Nonce.
	 * @param  bool   $json  True or False.
	 */
	protected function ajax_check_permissions( $nonce, $json = false ) {
		if ( has_filter( 'mainwp_currentusercan' ) ) {
			if ( ! mainwp_current_user_can( 'extension', 'advanced-uptime-monitor-extension' ) ) {
				$output = mainwp_do_not_have_permissions( 'Advanced Uptime Monitor Extension ', ! $json );
				if ( $json ) {
					die( wp_json_encode( array( 'error' => $output ) ) );
				} else {
					die( $output );
				}
			}
		} else {
			if ( ! current_user_can( 'manage_options' ) ) {
				$output = mainwp_do_not_have_permissions( 'Advanced Uptime Monitor Extension ', ! $json );
				if ( $json ) {
					die( wp_json_encode( array( 'error' => $output ) ) );
				} else {
					die( $output );
				}
			}
		}

		if ( ! isset( $_REQUEST['wp_nonce'] ) || ! wp_verify_nonce( $_REQUEST['wp_nonce'], 'mainwp_aum_nonce_' . $nonce ) ) {
			echo $json ? wp_json_encode( array( 'error' => 'Error: Wrong or expired request' ) ) : 'Error: Wrong or expired request';
			die();
		}

		$this->dispatch();
	}

}
