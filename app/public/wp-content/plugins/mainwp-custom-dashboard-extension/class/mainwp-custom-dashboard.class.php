<?php

class MainWP_Custom_Dashboard {

	public static $instance       = null;
	public static $custom_snippet = null;

	static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance       = new self();
			self::$custom_snippet = array(
				'php' => get_option( 'mainwp_custom_dashboard_php_snippet', '' ),
				'css' => get_option( 'mainwp_custom_dashboard_css_snippet', '' ),
				'js'  => get_option( 'mainwp_custom_dashboard_js_snippet', '' ),
			);
		}
		return self::$instance;
	}

	public function __construct() {

		$donotexec = false;

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Mainwp-Custom-Dashboard-Extension' && isset( $_GET['donotexec'] ) && $_GET['donotexec'] == 'yes' ) {
			$donotexec = true;
		}

		if ( ! $donotexec ) {
			add_action( 'init', array( &$this, 'init' ), 999 );
			// add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts_custom_css' ), 999 );
			// add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts_custom_js' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts_custom_css' ), 999 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts_custom_js' ) );
		}

		add_action( 'wp_ajax_mainwp_custom_dashboard_save_snippet', array( $this, 'ajax_save_snippet' ) );
		add_action( 'mainwp_help_sidebar_content', array( $this, 'get_help_content' ) );
	}

	public function init() {
		if ( ! empty( self::$custom_snippet ) ) {
			if ( isset( self::$custom_snippet['php'] ) && ! empty( self::$custom_snippet['php'] ) ) {

				// do not execute the php snippet when saving
				if ( defined( 'DOING_AJAX' ) && isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'mainwp-custom-dashboard-nonce' ) ) {
					return;
				}

				// show custom error message for admin side only
				if ( is_admin() ) {
					if ( class_exists( 'WP_Fatal_Error_Handler' ) && ( ! defined( 'WP_SANDBOX_SCRAPING' ) || ! WP_SANDBOX_SCRAPING ) ) {
						add_filter( 'wp_php_error_message', array( $this, 'wp_php_error_message' ), 10, 2 );
					} else {
						register_shutdown_function( array( $this, 'wp_php_error_message' ) );
					}
				}

				try {
					$code = wp_unslash( self::$custom_snippet['php'] );
					eval( $code );
				} catch ( Exception $e ) {

				}
			}
		}
	}

	public function wp_php_error_message( $message = '', $error = false ) {
		// handle fatal errors and compile errors
		$err = error_get_last();
		if ( isset( $err['type'] ) && isset( $err['message'] ) && ( E_PARSE === $err['type'] || E_COMPILE_ERROR === $err['type'] ) ) {
			// error may from the snippet
			if ( strpos( $err['file'], 'eval(' ) !== false ) {
				$go_fix = '<a href="admin.php?page=Extensions-Mainwp-Custom-Dashboard-Extension&tab=php&donotexec=yes" style="background: #7fb100; padding: 12px 25px; color: #ffff; text-decoration: none; border-radius: 3px;">Go to Fix the Snippet</a>';
				die( 'Fatal error : ' . $err['message'] . ' Line: ' . $err['line'] . ' File: ' . $err['file'] . '<br/><br/><hr/><br/>' . $go_fix );
			}
		}
		return $message;
	}

	public function enqueue_scripts_custom_css() {
		if ( ! empty( self::$custom_snippet ) ) {
			if ( isset( self::$custom_snippet['css'] ) && ! empty( self::$custom_snippet['css'] ) ) {
				if (self::is_mainwp_pages()){
					// do not delete this line
					wp_enqueue_style( 'mainwp-custom-dashboard-inline', MAINWP_CUSTOM_DASHBOARD_PLUGIN_URL . 'css/mainwp-custom-dashboard.css', array(), '1.0' );
					$custom_css = wp_unslash( self::$custom_snippet['css'] );
					wp_add_inline_style( 'mainwp-custom-dashboard-inline', $custom_css );
				}
			}
		}
	}

	/**
	 * Method is_mainwp_pages()
	 *
	 * Get the current page and check it for "mainwp_".
	 *
	 * @return boolean ture|false.
	 */
	public static function is_mainwp_pages() {
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->base, 'mainwp_' ) !== false && strpos( $screen->base, 'mainwp_child_tab' ) === false ) {
			return true;
		}

		return false;
	}

	public function enqueue_scripts_custom_js() {
		if ( ! empty( self::$custom_snippet ) ) {
			if ( isset( self::$custom_snippet['js'] ) && ! empty( self::$custom_snippet['js'] ) ) {
				// do not delete this line
				wp_enqueue_script( 'mainwp-custom-dashboard-inline', MAINWP_CUSTOM_DASHBOARD_PLUGIN_URL . 'js/mainwp-custom-dashboard.js', array(), '1.0' );
				$js = wp_unslash( self::$custom_snippet['js'] );
				wp_add_inline_script( 'mainwp-custom-dashboard-inline', $js );
			}
		}
	}

	function ajax_save_snippet() {

		if ( isset( $_POST['code'] ) && isset( $_POST['type'] ) && isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'mainwp-custom-dashboard-nonce' ) ) {

			$type = $_POST['type'];
			$name = '';
			if ( $type == 'php' ) {
				$name = 'mainwp_custom_dashboard_php_snippet';
			} elseif ( $type == 'css' ) {
				$name = 'mainwp_custom_dashboard_css_snippet';
			} elseif ( $type == 'js' ) {
				$name = 'mainwp_custom_dashboard_js_snippet';
			}
			if ( ! empty( $name ) ) {
				update_option( $name, $_POST['code'] );

				if ( is_array( self::$custom_snippet ) && isset( self::$custom_snippet['php'] ) ) {
					self::$custom_snippet['php'] = $_POST['code'];
				}

				die( json_encode( array( 'status' => 'SUCCESS' ) ) );
			}
		}
		die( json_encode( array( 'status' => 'FAILED' ) ) );
	}

	function render_settings() {

		$type = 'css';

		$current_tab = '';
		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'css' ) {
				$current_tab = 'css';
			} elseif ( $_GET['tab'] == 'php' ) {
				$current_tab = 'php';
				$type        = 'php';
			} elseif ( $_GET['tab'] == 'js' ) {
				$current_tab = 'js';
				$type        = 'js';
			}
		} else {
			$current_tab = 'css';
		}

		?>
	<div id="mainwp-custom-dashboard-extension">
	  <div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-custom-dashboard-menu">
			<a href="admin.php?page=Extensions-Mainwp-Custom-Dashboard-Extension&tab=css" class="item <?php echo ( $current_tab == 'css' ? 'active' : '' ); ?>"><i class="css3 icon"></i> <?php _e( 'CSS', 'mainwp-team-control' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Custom-Dashboard-Extension&tab=php" class="item <?php echo ( $current_tab == 'php' ? 'active' : '' ); ?>"><i class="php icon"></i> <?php _e( 'PHP', 'mainwp-team-control' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Custom-Dashboard-Extension&tab=js" class="item <?php echo ( $current_tab == 'js' ? 'active' : '' ); ?>"><i class="js icon"></i> <?php _e( 'JS', 'mainwp-team-control' ); ?></a>
	  </div>
	<div class="ui segment" style="margin-bottom:0">
		  <div class="ui yellow message">
		<?php echo __( 'MainWP is not responsible for the code that you run on your MainWP Dashboard. &nbsp; Use this tool with extreme care and at your own risk.', 'mainwp-custom-dashboard-extension' ); ?>
	  </div>
		  <div class="ui green message" id="mainwp-cust-dash-message-zone" style="display:none"></div>
		  <div class="ui red message" id="mainwp-cust-dash-error-zone" style="display:none"></div>
	</div>

		<?php if ( $current_tab == 'css' || $current_tab == '' ) : ?>
			<div class="ui segment" id="mainwp-css-customisations-tab">
			  <?php self::render_editor( $type ); ?>
			</div>
	  <?php endif; ?>

		<?php if ( $current_tab == 'php' ) : ?>
			<div class="ui segment" id="mainwp-php-customisations-tab">
			  <?php self::render_editor( $type ); ?>			       
			</div>
	  <?php endif; ?>

		<?php if ( $current_tab == 'js' ) : ?>
			<div class="ui segment" id="mainwp-js-customisations-tab">
			  <?php self::render_editor( $type ); ?>			       
			</div>  		
	  <?php endif; ?>						
	</div>
		<?php
	}

	function render_editor( $type ) {
		if ( $type == 'php' ) {
			$name        = 'mainwp_custom_dashboard_php_snippet';
			$title       = __( 'Custom PHP Code Snippets', 'mainwp-custom-dashboard-extension' );
			$description = __( 'Add custom PHP code snippets to extend functionality of your MainWP Dashboard. <a href="https://github.com/mainwp/mainwp-custom-dashboard-extension-examples/tree/master/php/" target="_blank">See some useful examples</a>.', 'mainwp-custom-dashboard-extension' );
		} elseif ( $type == 'css' ) {
			$name        = 'mainwp_custom_dashboard_css_snippet';
			$title       = __( 'Custom CSS Code Snippets', 'mainwp-custom-dashboard-extension' );
			$description = __( 'Add custom CSS rules here to adjust your MainWP Dashboard style to your needs. <a href="https://github.com/mainwp/mainwp-custom-dashboard-extension-examples/tree/master/css/" target="_blank">See some useful examples</a>', 'mainwp-custom-dashboard-extension' );
		} elseif ( $type == 'js' ) {
			$name        = 'mainwp_custom_dashboard_js_snippet';
			$title       = __( 'Custom JS Code Snippets', 'mainwp-custom-dashboard-extension' );
			$description = __( 'Add custom Jasvascrip code snippets here to customize your MainWP Dashboard. <a href="https://github.com/mainwp/mainwp-custom-dashboard-extension-examples/tree/master/js/" target="_blank">See some useful examples</a>', 'mainwp-custom-dashboard-extension' );
		}

		$code = get_option( $name, '' );
		?>
	<h2 class="ui header">
		<?php echo $title; ?>
	  <div class="sub header"><?php echo $description; ?></div>
	</h2>
		<form method="POST" id="mainwp_cust_dashboard_form">
			<textarea id="mainwp-custom-dashboard-code-editor" name="mainwp-custom-dashboard-code-editor" rows="50" spellcheck="false"><?php echo ! empty( $code ) ? esc_textarea( stripslashes( $code ) ) : ''; ?></textarea>
			<input type="hidden" name="mainwp_custom_dashboard_snippet_type" value="<?php echo $type; ?>">
			<input type="hidden" id="cust_dashboard_nonce" name="cust_dashboard_nonce" value="<?php echo wp_create_nonce( 'mainwp-custom-dashboard-nonce' ); ?>" />
			<div class="ui divider"></div>
			<input type="submit" class="ui big green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-custom-dashboard-extension' ); ?>" />
		</form>
	  </div>
		<?php
	}

	function get_help_content() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Mainwp-Custom-Dashboard-Extension' ) {
			echo __( 'The MainWP Custom Dashboard Extension is designed to contain your custom snippets for your MainWP Dashboard. By adding custom snippets to this plugin, you can easily extend your MainWP Dashboard functionality or change the way it looks.', 'mainwp-cutom-dashboard-extension' );
			?>
	  <p><?php echo __( 'Also, here you can review our Github repository that contains some examples:', 'mainwp-custom-dashboard-extension' ); ?></p>
	  <div class="ui list">
		<div class="item"><a href="https://github.com/mainwp/mainwp-custom-dashboard-extension-examples/tree/master/css/" target="_blank"><?php echo __( 'CSS Examples', 'mainwp-custom-dashboard-extension' ); ?></a></div>
		<div class="item"><a href="https://github.com/mainwp/mainwp-custom-dashboard-extension-examples/tree/master/php/" target="_blank"><?php echo __( 'PHP Examples', 'mainwp-custom-dashboard-extension' ); ?></a></div>
		<div class="item"><a href="https://github.com/mainwp/mainwp-custom-dashboard-extension-examples/tree/master/js/" target="_blank"><?php echo __( 'JS Examples', 'mainwp-custom-dashboard-extension' ); ?></a></div>
	  </div>
			<?php
		}
	}

}
