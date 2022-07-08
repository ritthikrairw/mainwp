<?php

final class MainWP_ITSEC_Global_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $version = 1;


	public function __construct() {
		$this->id          = 'global';
		$this->title       = __( 'Global Settings', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Configure basic settings that control how iThemes Security functions.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type        = 'recommended';

		parent::__construct();

		add_filter( 'admin_body_class', array( $this, 'filter_body_classes' ) );
	}

	public function filter_body_classes( $classes ) {

		if ( MainWP_ITSEC_Modules::get_setting( 'global', 'write_files' ) ) {
			$classes .= ' itsec-write-files-enabled';
		} else {
			$classes .= ' itsec-write-files-disabled';
		}

		$classes = trim( $classes );

		return $classes;
	}

	public function enqueue_scripts_and_styles() {
		global $mainwp_itheme_site_data_values;
		$vars = array(
			'ip'           => MainWP_ITSEC_Lib::get_ip(),
			'log_location' => is_array( $mainwp_itheme_site_data_values ) && isset( $mainwp_itheme_site_data_values['default_log_location'] ) ? $mainwp_itheme_site_data_values['default_log_location'] : '',
		);

		wp_enqueue_script( 'mainwp-itsec-global-settings-page-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), $this->version, true );
		wp_localize_script( 'mainwp-itsec-global-settings-page-script', 'itsec_global_settings_page', $vars );
	}

	public function handle_form_post( $data ) {
		$retval = MainWP_ITSEC_Modules::set_settings( $this->id, $data );

		if ( $retval['saved'] ) {

			if ( $retval['old_settings']['write_files'] !== $retval['new_settings']['write_files'] ) {
				MainWP_ITSEC_Response::add_js_function_call( 'mainwp_itsec_change_write_files', array( (bool) $retval['new_settings']['write_files'] ) );
			}
		}
	}

	protected function render_settings( $form ) {
		$validator = MainWP_ITSEC_Modules::get_validator( $this->id );

		$log_types = $validator->get_valid_log_types();

		$proxy = array( 'value' => $validator->get_proxy_types() );

		$proxy_header_opt = $validator->get_proxy_header_options();

		$is_individual = MainWP_IThemes_Security::is_manage_site();

		?>
	
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-write_files"><?php _e( 'Write to Files', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'write_files' ); ?>
			<p class="description"><?php _e( 'Allow iThemes Security to write to wp-config.php and .htaccess automatically. If disabled, you will need to place configuration options in those files manually.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'Lockouts', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-lockout_period"><?php _e( 'Minutes to Lockout', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'lockout_period' ); ?>
			<p class="description"><?php _e( 'The length of time a host or user will be locked out from this site after hitting the limit of bad logins. The default setting of 15 minutes is recommended as increasing it could prevent attackers from being banned.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-blacklist_period"><?php _e( 'Days to Remember Lockouts', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'blacklist_period' ); ?>
			<p class="description"><?php _e( 'How many days should iThemes Security remember a lockout. This does not affect the logs generated when creating a lockout.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-blacklist"><?php _e( 'Ban Repeat Offender', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'blacklist' ); ?>
			<p class="description"><?php _e( 'Should iThemes Security permanently add a locked out IP address to the “Ban Users” list after reaching the configured “Ban Threshold”.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-blacklist_count"><?php _e( 'Ban Threshold', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'blacklist_count' ); ?>
			<p class="description"><?php _e( 'The number of lockouts per IP before the host is banned permanently from this site.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'Lockout Messages', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-lockout_message"><?php _e( 'Host Lockout Message', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'lockout_message' ); ?>
			<p class="description"><?php _e( 'The message to display when a computer (host) has been locked out.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-user_lockout_message"><?php _e( 'User Lockout Message', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'user_lockout_message' ); ?>
			<p class="description"><?php _e( 'The message to display to a user when their account has been locked out.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-community_lockout_message"><?php _e( 'Community Lockout Message', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'community_lockout_message' ); ?>
			<p class="description"><?php _e( 'The message to display to a user when their IP has been flagged as bad by the iThemes network.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'Authorized Hosts', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-automatic_temp_auth"><?php _e( 'Automatically Temporarily Authorize Hosts', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'automatic_temp_auth' ); ?>
			<p class="description"><?php _e( 'Should iThemes Security permanently add a locked out IP address to the “Ban Users” list after reaching the configured “Ban Threshold”.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-lockout_white_list"><?php _e( 'Authorized Hosts', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_textarea( 'lockout_white_list' ); ?>
			<?php
			$server_ip = '';
			if ( isset( $_SERVER['LOCAL_ADDR'] ) ) {
				$server_ip = $_SERVER['LOCAL_ADDR'];
			} elseif ( isset( $_SERVER['SERVER_ADDR'] ) ) {
				$server_ip = $_SERVER['SERVER_ADDR'];
			}
			?>
			<p class="description"><?php _e( 'Enter a list of hosts that should not be locked out by iThemes Security.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			<p>
			<?php
			$form->add_button(
				'add-to-whitelist',
				array(
					'value' => __( 'Add my current IP to the White List', 'l10n-mainwp-ithemes-security-extension' ),
					'class' => 'button ui green',
				)
			);
			?>
				&nbsp;&nbsp;<a href="<?php echo PHP_EOL . $server_ip; ?>" class="itsec_add_dashboard_ip_to_whitelist button ui green"><?php echo __( 'Add Dashboard IP to Whitelist', 'l10n-mainwp-ithemes-security-extension' ); ?></a></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'Logging', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-log_type"><?php _e( 'How should event logs be kept', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_select( 'log_type', $log_types ); ?>
			<p class="description"><?php _e( 'How should event logs be kept iThemes Security can log events in multiple ways, each with advantages and disadvantages. Database Only puts all events in the database with your posts and other WordPress data. This makes it easy to retrieve and process but can be slower if the database table gets very large. File Only is very fast but the plugin does not process the logs itself as that would take far more resources. For most users or smaller sites Database Only should be fine. If you have a very large site or a log processing software then File Only might be a better option.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-log_rotation"><?php _e( 'Days to Keep Database Logs', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'log_rotation' ); ?>
			<p class="description"><?php _e( 'The number of days database logs should be kept.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-file_log_rotation"><?php _e( 'Days to Keep File Logs', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'file_log_rotation' ); ?>
			<p class="description"><?php _e( 'The number of days file logs should be kept. File logs will additionally be rotated once the file hits 10MB. Set to 0 to only use log rotation.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field" id="itsec-global-log_location_container">
		<label class="six wide column middle aligned" for="itsec-global-log_location"><?php _e( 'Path to Log Files', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
		<?php
		if ( $is_individual ) {
			?>
				<?php $form->add_text( 'log_location', array( 'class' => 'large-text code' ) ); ?>
				<p><label for="itsec-global-log_location"><?php _e( 'The path on your server where log files should be stored.', 'l10n-mainwp-ithemes-security-extension' ); ?></label></p>
				<p class="description"><?php _e( 'This path must be writable by your website. For added security, it is recommended you do not include it in your website root folder.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
				<p>
				<?php
				$form->add_button(
					'reset-log-location',
					array(
						'value' => __( 'Restore Default Log File Path', 'l10n-mainwp-ithemes-security-extension' ),
						'class' => 'button ui basic green',
					)
				);
				?>
					</p>
				<?php
		} else {
			$form->add_checkbox( 'use_individual_log_location', array( 'label' => __( 'Use value from individual site settings', 'l10n-mainwp-ithemes-security-extension' ) ) );
			?>
								
				<p class="description"><?php _e( 'Use "Path to Log Files" value from individual site settings. Uncheck will do not update the value.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
				<?php
		}
		?>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-allow_tracking"><?php _e( 'Allow Data Tracking', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'allow_tracking', array( 'label' => __( 'Allow iThemes to track plugin usage via anonymous data.', 'l10n-mainwp-ithemes-security-extension' ) ) ); ?>
		</div>
	</div>

		<?php if ( 'nginx' === MainWP_ITSEC_Lib::get_server() ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-global-nginx_file"><?php _e( 'NGINX Conf File', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_text( 'nginx_file', array( 'class' => 'large-text code' ) ); ?>
				<p><label for="itsec-global-nginx_file"><?php _e( 'The path on your server where the nginx config file is located.', 'l10n-mainwp-ithemes-security-extension' ); ?></label></p>
				<p class="description"><?php _e( 'This path must be writable by your website. For added security, it is recommended you do not include it in your website root folder.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>
	<?php endif; ?>
	<div class="ui dividing header"><?php _e( 'IP Detection', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-proxy"><?php _e( 'Proxy Detection', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_select( 'proxy', $proxy ); ?>
			<p class="itsec-global-detected-ip"><?php printf( __( 'Detected IP: %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Lib::get_ip() ); ?></p>
			<p class="description"><?php esc_html_e( 'Determine how iThemes Security determines your visitor‘s IP addresses. Choose the Security Check Scan to increase iThemes Security’s ability to identify your server IP and IPs attacking your website accurately.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui grid field itsec-global-proxy_header-container">
		<label class="six wide column middle aligned" for="itsec-global-proxy_header"><?php _e( 'Proxy Header', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_select( 'proxy_header', $proxy_header_opt ); ?>
			<p class="description">
			<?php esc_html_e( 'Select the header your Proxy Server uses to forward the client IP address. If you don’t know the header, you can contact your hosting provider or select the header that has your IP Address.', 'l10n-mainwp-ithemes-security-extension' ); ?>
			</p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'UI Tweaks', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-hide_admin_bar"><?php _e( 'Hide Security Menu in Admin Bar', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'hide_admin_bar' ); ?>
		</div>
	</div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-enable_grade_report"><?php _e( 'Enable Grade Report', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
		<?php $form->add_checkbox( 'enable_grade_report' ); ?>
		</div>
	</div>
		<?php
	}
}

new MainWP_ITSEC_Global_Settings_Page();
