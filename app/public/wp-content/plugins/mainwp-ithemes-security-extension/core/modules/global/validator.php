<?php

class MainWP_ITSEC_Global_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'global';
	}

	public function get_valid_log_types() {
		return array(
			'database' => __( 'Database Only', 'l10n-mainwp-ithemes-security-extension' ),
			'file'     => __( 'File Only', 'l10n-mainwp-ithemes-security-extension' ),
			'both'     => __( 'Both', 'l10n-mainwp-ithemes-security-extension' ),
		);
	}

	public function get_proxy_types() {
		MainWP_ITSEC_Lib::load( 'ip-detector' );

		return MainWP_ITSEC_Lib_IP_Detector::get_proxy_types();
	}

	
	public function get_proxy_header_options() {
		MainWP_ITSEC_Lib::load( 'ip-detector' );

		$possible_headers = MainWP_ITSEC_Lib_IP_Detector::get_proxy_headers();
		
		$ucwords = version_compare( phpversion(), '5.5.16', '>=' ) || ( version_compare( phpversion(), '5.4.32', '>=' ) && version_compare( phpversion(), '5.5.0', '<' ) );
		$options = array();

		foreach ( $possible_headers as $header ) {
			$label = $header;

			if ( 0 === strpos( $header, 'HTTP_' ) ) {
				$label = substr( $label, 5 );
			}

			$label = str_replace( '_', '-', $label );
			$label = strtolower( $label );
			$label = $ucwords ? ucwords( $label, '-' ) : implode( '-', array_map( 'ucfirst', explode( '-', $label ) ) );
			$label = str_replace('Ip', 'IP', $label );

			$options[ $header ] = $label;
		}

		return $options;
	}

	protected function sanitize_settings() {
		if ( is_dir( WP_PLUGIN_DIR . '/iwp-client' ) ) {
			$this->sanitize_setting( 'bool', 'infinitewp_compatibility', __( 'Add InfiniteWP Compatibility', 'l10n-mainwp-ithemes-security-extension' ) );
		} else {
			$this->settings['infinitewp_compatibility'] = $this->previous_settings['infinitewp_compatibility'];
		}

		if ( 'nginx' === MainWP_ITSEC_Lib::get_server() ) {
			$this->sanitize_setting( 'writable-file', 'nginx_file', __( 'NGINX Conf File', 'l10n-mainwp-ithemes-security-extension' ), false );
		} else {
			$this->settings['nginx_file'] = $this->previous_settings['nginx_file'];
		}

		$this->set_previous_if_empty( array( 'did_upgrade', 'log_info', 'show_new_dashboard_notice' ) );
		//$this->set_default_if_empty( array( 'log_location', 'nginx_file' ) );

		if (isset($this->settings['use_individual_log_location'])) {
			$this->vars_to_skip_validate_matching_fields[] = 'use_individual_log_location';
			$this->vars_to_skip_validate_matching_fields[] = 'log_location';
			$this->sanitize_setting( 'bool', 'use_individual_log_location', __( 'Path to Log Files', 'l10n-mainwp-ithemes-security-extension' ) );
		}

		$this->sanitize_setting( 'bool', 'write_files', __( 'Write to Files', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'bool', 'digest_email', __( 'Send Digest Email', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'blacklist', __( 'Blacklist Repeat Offender', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'bool', 'email_notifications', __( 'Email Lockout Notifications', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'allow_tracking', __( 'Allow Data Tracking', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'bool', 'lock_file', __( 'Disable File Locking', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'bool', 'proxy_override', __( 'Override Proxy Detection', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( array_keys( $this->get_proxy_types() ), 'proxy', __( 'Proxy Detection', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'string', 'proxy_header', __( 'Manual Proxy Header', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'hide_admin_bar', __( 'Hide Security Menu in Admin Bar', 'l10n-mainwp-ithemes-security-extension' ) );
		
		$this->sanitize_setting( 'string', 'lockout_message', __( 'Host Lockout Message', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'string', 'user_lockout_message', __( 'User Lockout Message', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'string', 'community_lockout_message', __( 'Community Lockout Message', 'l10n-mainwp-ithemes-security-extension' ) );

		if (isset($this->settings['log_location'])) {
			$this->sanitize_setting( 'writable-directory', 'log_location', __( 'Path to Log Files', 'l10n-mainwp-ithemes-security-extension' ) );
		}

		$this->sanitize_setting( 'positive-int', 'blacklist_count', __( 'Blacklist Threshold', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'blacklist_period', __( 'Blacklist Lockout Period', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'lockout_period', __( 'Lockout Period', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'positive-int', 'log_rotation', __( 'Days to Keep Database Logs', 'l10n-mainwp-ithemes-security-extension' ) );
        $this->sanitize_setting( 'positive-int', 'file_log_rotation', __( 'Days to Keep File Logs', 'l10n-mainwp-ithemes-security-extension' ) );
   	
		$log_types = array_keys( $this->get_valid_log_types() );
		$this->sanitize_setting( $log_types, 'log_type', __( 'Log Type', 'l10n-mainwp-ithemes-security-extension' ) );

		$this->sanitize_setting( 'newline-separated-ips', 'lockout_white_list', __( 'Lockout White List', 'l10n-mainwp-ithemes-security-extension' ) );

		//$this->sanitize_setting( 'newline-separated-emails', 'notification_email', __( 'Notification Email', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'newline-separated-emails', 'backup_email', __( 'Backup Delivery Email', 'l10n-mainwp-ithemes-security-extension' ) );


		$allowed_tags = array(
			'a'      => array(
				'href'  => array(),
				'title' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'h1'     => array(),
			'h2'     => array(),
			'h3'     => array(),
			'h4'     => array(),
			'h5'     => array(),
			'h6'     => array(),
			'div'    => array(
				'style' => array(),
			),
		);

		$this->settings['lockout_message'] = trim( wp_kses( $this->settings['lockout_message'], $allowed_tags ) );
		$this->settings['user_lockout_message'] = trim( wp_kses( $this->settings['user_lockout_message'], $allowed_tags ) );
		$this->settings['community_lockout_message'] = trim( wp_kses( $this->settings['community_lockout_message'], $allowed_tags ) );
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Global_Validator() );
