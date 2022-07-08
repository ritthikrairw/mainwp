<?php

class MainWP_Clean_And_Lock {

	public static $instance = null;
	public $plugin_handle   = 'mainwp_cal_nonce';
	public $option_handle   = 'mainwp_cal_options';
	public $option          = array();

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new MainWP_Clean_And_Lock();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
		// add_action( 'admin_menu', array( $this, 'remove_menus' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function init() {

		if ( defined( 'WP_CLI' ) || defined( 'DOING_CRON' ) || defined( 'DOING_AJAX' ) ) {
			return;
		}

		$redirect_url = $this->get_option( 'redirect_url', '' );
		if ( ! empty( $redirect_url ) ) {
			if ( ! is_admin() && ( strpos( $_SERVER['REQUEST_URI'], $redirect_url ) === false ) && ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) === false ) && ( strpos( $_SERVER['REQUEST_URI'], 'favicon.ico' ) === false ) && ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) === false ) && ( strpos( $_SERVER['REQUEST_URI'], 'cron' ) === false ) && ! isset( $_POST['action'] ) ) {
				$exclude_slugs = self::get_instance()->get_option( 'exclude_slugs', '' );
				if ( ! empty( $exclude_slugs ) ) {
					$exclude_slugs = explode( "\n", $exclude_slugs );
					if ( is_array( $exclude_slugs ) && count( $exclude_slugs ) > 0 ) {
						if ( $_SERVER['REQUEST_URI'] == '/' ) {
							if ( in_array( '/', $exclude_slugs ) ) {
								return;
							}
						} else {
							foreach ( $exclude_slugs as $excl ) {
								if ( empty( $excl ) || $excl == '/' ) {
									continue;
								}
								if ( strpos( $_SERVER['REQUEST_URI'], $excl ) !== false ) {
									return;
								}
							}
						}
					}
				}
				header( 'Location: ' . $redirect_url, true, 301 );
				exit();
			}
		}
	}
	public function admin_init() {
		$this->handle_post_settings();
	}

	function mod_rewrite_rules( $pRules ) {
		$home_root = parse_url( home_url() );
		if ( isset( $home_root['path'] ) ) {
			$home_root = trailingslashit( $home_root['path'] );
		} else {
			$home_root = '/';
		}

		$rules  = "<IfModule mod_rewrite.c>\n";
		$rules .= "RewriteEngine On\n";
		$rules .= "RewriteBase $home_root\n";

		foreach ( $pRules as $value ) {
			if ( ! empty( $value['query'] ) ) {
				$rules .= $value['rule'] . ' ' . $value['match'] . ' ' . $value['query'] . "\n";
			}
		}

		$rules .= "</IfModule>\n";

		return $rules;
	}

	public static function clear_rewrite_htaccess() {
		include_once ABSPATH . '/wp-admin/includes/misc.php';
		$home_path     = ABSPATH;
		$htaccess_file = $home_path . '.htaccess';
		if ( function_exists( 'save_mod_rewrite_rules' ) ) {
			$rules = explode( "\n", '' );
			insert_with_markers( $htaccess_file, 'MainWP Secure and Clean.', $rules );
		}
	}

	public function update_rewrite_htaccess( $force_clear = false ) {

		$allow_ips_address = $this->get_option( 'allow_ips_address', '' );

		$rulesRewrite = array();
		if ( ! empty( $allow_ips_address ) || ! empty( $trusted_refers ) ) {
			include_once ABSPATH . '/wp-admin/includes/misc.php';

			$allow_ips_address = ! empty( $allow_ips_address ) ? explode( "\n", trim( $allow_ips_address ) ) : array();

			if ( count( $allow_ips_address ) > 0 ) {
				$rulesRewrite[] = array(
					'rule'  => 'RewriteCond',
					'match' => '%{REQUEST_URI}',
					'query' => '^(.*)?wp-login\.php(.*)$ [OR]',
				);
				$rulesRewrite[] = array(
					'rule'  => 'RewriteCond',
					'match' => '%{REQUEST_URI}',
					'query' => '^(.*)?wp-admin$',
				);
				foreach ( $allow_ips_address as $ip ) {
					if ( ! empty( $ip ) ) {
						$ip             = str_replace( '.', '\.', trim( $ip ) );
						$rulesRewrite[] = array(
							'rule'  => 'RewriteCond',
							'match' => '%{REMOTE_ADDR}',
							'query' => '!^' . $ip . '$',
						);
					}
				}
				$rulesRewrite[] = array(
					'rule'  => 'RewriteRule',
					'match' => '^(.*)$',
					'query' => '- [R=403,L]',
				);
			}
		}

		if ( ! empty( $rulesRewrite ) ) {
			// Create rewrite ruler
			$rules         = $this->mod_rewrite_rules( $rulesRewrite );
			$home_path     = ABSPATH;
			$htaccess_file = $home_path . '.htaccess';
			if ( function_exists( 'save_mod_rewrite_rules' ) ) {
				$rules_arr = explode( "\n", $rules );
				insert_with_markers( $htaccess_file, 'MainWP Secure and Clean.', $rules_arr ); // dont remove "." it will generate a strange bug
			}
		} else {
			self::clear_rewrite_htaccess();
		}

		return true;
	}

	function get_system_name() {
		$name     = php_uname();
		$sys_name = 'other';
		if ( stripos( $name, 'windows' ) === 0 ) {
			$sys_name = 'windows';
		} elseif ( stripos( $name, 'linux' ) === 0 ) {
			$sys_name = 'linux';
		}
		return $sys_name;
	}

	public function update_authen_htaccess( $force_clear = false ) {
		include_once ABSPATH . '/wp-admin/includes/misc.php';
		$home_path = ABSPATH;

		if ( function_exists( 'save_mod_rewrite_rules' ) ) {
			$wpadmin_user = $this->get_option( 'wpadmin_user', '' );
			$wpadmin_pass = $this->get_option( 'wpadmin_passwd', '' );

			$wpadmin_pass = stripslashes( $wpadmin_pass );

			$htaccess_file1 = $home_path . 'wp-admin/.htaccess';
			$htpasswd_file1 = $home_path . 'wp-admin/.htpasswd';

			$session_name1 = 'MainWP Secure and Clean - Apache Password Protect wp-admin';

			$sys = $this->get_system_name();

			if ( ! empty( $wpadmin_user ) && ! empty( $wpadmin_pass ) ) {
				$rules_str  = '<Limit GET POST>' . PHP_EOL;
				$rules_str .= 'AuthUserFile "' . $htpasswd_file1 . '"' . PHP_EOL;
				$rules_str .= 'AuthName "Please, enter your WP-Admin Username and Password"' . PHP_EOL;
				$rules_str .= 'AuthType Basic' . PHP_EOL;
				$rules_str .= 'require valid-user' . PHP_EOL;
				$rules_str .= '</Limit>' . PHP_EOL;
				$rules_str .= '<Files admin-ajax.php>' . PHP_EOL;
				$rules_str .= 'Order allow,deny' . PHP_EOL;
				$rules_str .= 'Allow from all' . PHP_EOL;
				$rules_str .= 'Satisfy any' . PHP_EOL;
				$rules_str .= '</Files>' . PHP_EOL;

				$rules = explode( PHP_EOL, $rules_str );

				insert_with_markers( $htaccess_file1, $session_name1, $rules );

				$authen_pass = $wpadmin_pass;

				if ( 'linux' === $sys ) {
					$authen_pass = crypt( $wpadmin_pass, base64_encode( $wpadmin_pass ) );

				}
				$passwd_str = $wpadmin_user . ':' . $authen_pass;
				$fopen      = fopen( $htpasswd_file1, 'w+' );

				fwrite( $fopen, $passwd_str );
				fclose( $fopen );
			} else {
				$rules = explode( PHP_EOL, '' );
				insert_with_markers( $htaccess_file1, $session_name1, $rules );
			}

			$wplogin_user = $this->get_option( 'wplogin_user', '' );
			$wplogin_pass = $this->get_option( 'wplogin_passwd', '' );

			$htaccess_file2 = $home_path . '.htaccess';
			$htpasswd_file2 = $home_path . '.htpasswd';

			$session_name2 = 'MainWP Secure and Clean - Apache Password Protect wp-login.php';

			if ( ! empty( $wplogin_user ) && ! empty( $wplogin_pass ) ) {
				$rules_str  = '<Files wp-login.php>' . PHP_EOL;
				$rules_str .= 'AuthUserFile "' . $htpasswd_file2 . '"' . PHP_EOL;
				$rules_str .= 'AuthName "Please, enter your wp-login.php Username and Password"' . PHP_EOL;
				$rules_str .= 'AuthType Basic' . PHP_EOL;
				$rules_str .= 'require valid-user' . PHP_EOL;
				$rules_str .= '</Files>' . PHP_EOL;

				$rules = explode( PHP_EOL, $rules_str );
				insert_with_markers( $htaccess_file2, $session_name2, $rules );
				$authen_pass = stripslashes( $wplogin_pass );
				if ( 'linux' === $sys ) {
					$authen_pass = crypt( $wplogin_pass, base64_encode( $wplogin_pass ) );
				}

				$passwd_str = $wplogin_user . ':' . $authen_pass;
				$fopen      = fopen( $htpasswd_file2, 'w+' );

				fwrite( $fopen, $passwd_str );
				fclose( $fopen );
			} else {
				$rules = explode( PHP_EOL, '' );
				insert_with_markers( $htaccess_file2, $session_name2, $rules );
			}
		}
		return true;
	}

	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}

		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	public function handle_post_settings() {

		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'cal_save_settings' ) ) {
			if ( isset( $_POST['cal-save-settings-btn'] ) ) {
				$this->set_option( 'redirect_url', trim( $_POST['mwp-cal-setting-redirect-url'] ) );

				$exclude_slugs = array();

				$setting_exclude_slug = trim( $_POST['mwp-cal-setting-exclude-slug'] );
				$setting_exclude_slug = ! empty( $setting_exclude_slug ) ? explode( "\n", $setting_exclude_slug ) : array();

				foreach ( $setting_exclude_slug as $slug ) {
					$slug = trim( $slug );
					if ( ! empty( $slug ) && ! in_array( $slug, $exclude_slugs ) ) {
						$exclude_slugs[] = $slug;
					}
				}

				$save_exclude_slugs = count( $exclude_slugs ) > 0 ? implode( "\n", $exclude_slugs ) : '';

				$this->set_option( 'exclude_slugs', $save_exclude_slugs );

				$ips_address = array();

				$allow_ips_address = trim( $_POST['cal_allow_login_from_ip_address'] );

				$allow_ips_address = ! empty( $allow_ips_address ) ? explode( "\n", $allow_ips_address ) : array();

				foreach ( $allow_ips_address as $ip ) {
					$ip = trim( $ip );
					if ( ! empty( $ip ) && ! in_array( $ip, $ips_address ) ) {
						$ips_address[] = $ip;
					}
				}

				$txt_ips_address = count( $ips_address ) > 0 ? implode( "\n", $ips_address ) : '';
				$this->set_option( 'allow_ips_address', $txt_ips_address );

				$this->set_option( 'wpadmin_user', sanitize_text_field( $_POST['cal_wpadmin_lock_user_name'] ) );
				$password_hashed1 = trim( $_POST['cal_wpadmin_lock_passwd'] );
				$this->set_option( 'wpadmin_passwd', $password_hashed1 );

				$this->set_option( 'wplogin_user', sanitize_text_field( $_POST['cal_wplogin_lock_user_name'] ) );
				$password_hashed2 = trim( $_POST['cal_wplogin_lock_passwd'] );
				$this->set_option( 'wplogin_passwd', $password_hashed2 );

			} elseif ( isset( $_POST['cal-unlock-btn'] ) ) {
				$this->set_option( 'allow_ips_address', '' );
				$this->set_option( 'wpadmin_user', '' );
				$this->set_option( 'wpadmin_passwd', '' );
				$this->set_option( 'wplogin_user', '' );
				$this->set_option( 'wplogin_passwd', '' );
			}

			$this->option = get_option( $this->option_handle ); // reload options
			$this->update_rewrite_htaccess();
			$this->update_authen_htaccess();
			wp_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Clean-And-Lock-Extension&message=1' ) );
			die();
		}
		return false;
	}

	public static function render() {

		$message = '';

		if ( isset( $_GET['message'] ) && 1 == $_GET['message'] ) {
			$message = __( 'Settings saved.' );
		}

		$redirect_url   = self::get_instance()->get_option( 'redirect_url', '' );
		$exclude_slugs  = self::get_instance()->get_option( 'exclude_slugs', '' );
		$allow_ip_login = self::get_instance()->get_option( 'allow_ips_address', '' );
		$wpadmin_user   = self::get_instance()->get_option( 'wpadmin_user', '' );
		$wpadmin_pass   = self::get_instance()->get_option( 'wpadmin_passwd', '' );
		$wplogin_user   = self::get_instance()->get_option( 'wplogin_user', '' );
		$wplogin_pass   = self::get_instance()->get_option( 'wplogin_passwd', '' );

		?>
		<div class="ui alt segment" id="mainwp-clean-and-lock-extension">
			<div class="mainwp-main-content">
				<?php if ( ! empty( $message ) ) : ?>
					<div class="ui message green"><i class="ui icon close"></i><?php echo $message; ?></div>
				<?php endif; ?>
				<div class="ui hidden divider"></div>
				<div class="ui form">
					<form method="post" action="admin.php?page=Extensions-Mainwp-Clean-And-Lock-Extension">
						<h3 class="ui dividing header"><?php esc_html_e( '301 Redirect', 'mainwp-clean-and-lock-extension' ); ?></h3>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( '301 Redirect URL', 'mainwp-clean-and-lock-extension' ); ?></label>
						  <div class="ten wide column">
								<span ><input type="text" id="mwp-cal-setting-redirect-url" name="mwp-cal-setting-redirect-url" value="<?php echo htmlspecialchars( stripslashes( $redirect_url ) ); ?>" placeholder="http://"></span>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Exclude slug from redirection', 'mainwp-clean-and-lock-extension' ); ?></label>
						  <div class="ten wide column">
								<textarea id="mwp-cal-setting-exclude-slug" name="mwp-cal-setting-exclude-slug" cols="30" rows="5" placeholder="<?php esc_attr_e( 'One URL/slug per line.', 'mainwp-clean-and-lock-extension' ); ?>"><?php echo esc_textarea( $exclude_slugs ); ?></textarea>
							</div>
						</div>
						<h3 class="ui dividing header"><?php esc_html_e( 'Dashboard Lock Down', 'mainwp-clean-and-lock-extension' ); ?></h3>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Allow login from', 'mainwp-clean-and-lock-extension' ); ?></label>
						  <div class="ten wide column">
								<span data-tooltip="<?php esc_attr_e( 'Enter IP Address you want to allow. Enter 1 Address per row.', 'mainwp-clean-and-lock-extension' ); ?>" data-inverted=""><textarea id="cal_allow_login_from_ip_address" name="cal_allow_login_from_ip_address" cols="30" rows="5" placeholder="<?php esc_attr_e( 'One IP per line.', 'mainwp-clean-and-lock-extension' ); ?>"><?php echo esc_textarea( $allow_ip_login ); ?></textarea></span>
								<div class="ui bottom attached info message"><?php echo __( 'We detect your current IP to be: ', 'mainwp-clean-and-lock-extension' ) . $_SERVER['REMOTE_ADDR']; ?></div>
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'WP Admin Lock', 'mainwp-clean-and-lock-extension' ); ?></label>
						  <div class="five wide column">
								<input type="text" name="cal_wpadmin_lock_user_name" id="cal_wpadmin_lock_user_name" value="<?php echo htmlspecialchars( stripslashes( $wpadmin_user ) ); ?>" placeholder="<?php _e( 'Username', 'mainwp-clean-and-lock-extension' ); ?>" >
							</div>
							<div class="five wide column">
								<input type="text" placeholder="<?php _e( 'Password', 'mainwp-clean-and-lock-extension' ); ?>" name="cal_wpadmin_lock_passwd" id="cal_wpadmin_lock_passwd" value="<?php echo htmlspecialchars( stripslashes( $wpadmin_pass ) ); ?>" placeholder="<?php _e( 'Password', 'mainwp-clean-and-lock-extension' ); ?>" maxlength="18" autocomplete="off" >
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Login Lock', 'mainwp-clean-and-lock-extension' ); ?></label>
						  <div class="five wide column">
								<input type="text" name="cal_wplogin_lock_user_name" id="cal_wplogin_lock_user_name" value="<?php echo htmlspecialchars( stripslashes( $wplogin_user ) ); ?>" placeholder="<?php _e( 'Username', 'mainwp-clean-and-lock-extension' ); ?>" >
							</div>
							<div class="five wide column">
								<input type="text" name="cal_wplogin_lock_passwd" id="cal_wplogin_lock_passwd" value="<?php echo htmlspecialchars( stripslashes( $wplogin_pass ) ); ?>" placeholder="<?php _e( 'Password', 'mainwp-clean-and-lock-extension' ); ?>"   maxlength="18" autocomplete="off" >
							</div>
						</div>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'WP Admin Lock', 'mainwp-clean-and-lock-extension' ); ?></label>
						  <div class="ten wide column">
								<span data-tooltip="<?php esc_attr_e( 'Click the button to remove Allowed IP Addresses, WP Admin and Login page locks', 'mainwp-clean-and-lock-extension' ); ?>" data-inverted=""><input type="submit" class="ui green basic button" id="cal-unlock-btn" name="cal-unlock-btn" value="<?php _e( 'Remove Locks', 'mainwp-clean-and-lock-extension' ); ?>"></span>
							</div>
						</div>
					</div>
					<div class="ui divider"></div>
					<input type="submit" name="cal-save-settings-btn" id="cal-save-settings-btn" class="ui green big right floated button" value="<?php _e( 'Save Settings', 'mainwp-clean-and-lock-extension' ); ?>">
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'cal_save_settings' ); ?>" />
				</form>
			</div>
			<div class="mainwp-side-content">
				<h3 class="ui header"><?php esc_html_e( '301 Redirection', 'mainwp-clean-and-lock-extension' ); ?></h3>
				<p><?php esc_html_e( 'Make your dashboard front page inaccessible to everyone but you. Easily set a redirect URL and all hits on Non-WP-Admin pages will be redirected to it. This will make your MainWP Dashboard site virtually invisible.', 'mainwp-clean-and-lock-extension' ); ?></p>
				<p><?php esc_html_e( 'To exclude certain slugs, it’s enough to add just page slug.', 'mainwp-clean-and-lock-extension' ); ?></p>
				<h3 class="ui header"><?php esc_html_e( 'Dashboard Lock Down', 'mainwp-clean-and-lock-extension' ); ?></h3>
				<p><?php esc_html_e( 'The Extension allows you to limit access to WP Admin pages and to your wp-login.php page to specific IP addresses without having to manually edit your .htaccess file.', 'mainwp-clean-and-lock-extension' ); ?></p>
				<p><?php esc_html_e( 'Some hosts don’t allow HTTP basic authentication and this can create an infinite redirection loop. This can make accessing your MainWP Dashboard site hard. In case you experience this issue, please remove WP Admin and WP Login Locks. If this doesn’t help, it is highly recommended to contact your host support.', 'mainwp-clean-and-lock-extension' ); ?></p>
				<a class="ui basic fluid big green button"  href="https://mainwp.com/help/docs/clean-and-lock-extension/" target="_blank"><?php _e( 'Help Documentation', 'mainwp-clean-and-lock-extension' ); ?></a>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>
		<?php
	}
}
