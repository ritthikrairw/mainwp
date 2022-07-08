<?php

class MainWP_ITSEC_Strong_Passwords {

	function run() {
		add_action( 'mainwp_itsec_register_password_requirements', array( $this, 'register_requirements' ) );
	}

    /**
	 * Register the Strong Passwords requirement.
	 */
	public function register_requirements() {
		MainWP_ITSEC_Lib_Password_Requirements::register( 'strength', array(
			'evaluate_if_not_enabled' => true,
			'defaults'                => array( 'role' => 'administrator' ),
			'settings_config'         => array( $this, 'get_settings_config' ),
		) );
	}

    public function get_settings_config() {
		return array(
			'label'       => esc_html__( 'Strong Passwords', 'l10n-mainwp-ithemes-security-extension' ),
			'description' => esc_html__( 'Force users to use strong passwords as rated by the WordPress password meter.', 'l10n-mainwp-ithemes-security-extension' ),
			'render'      => array( $this, 'render_settings' ),
			'sanitize'    => array( $this, 'sanitize_settings' ),
		);
	}

    public function render_settings( $form ) {
		?>
		<tr>
			<th scope="row">
				<label for="itsec-password-requirements-requirement_settings-strength-group">
					<?php esc_html_e( 'User Group', 'l10n-mainwp-ithemes-security-extension' ); ?>
				</label>
			</th>
			<td>
				<?php $form->add_user_groups( 'group', 'password-requirements', 'requirement_settings.strength.group' ); ?>
				<br/>
				<label for="itsec-password-requirements-requirement_settings-strength-group"><?php _e( 'Force users in the selected groups to use strong passwords.', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			</td>
		</tr>
		<?php
	}

    /**
	 * Get a list of the sanitizer rules to apply.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		return array(
			array( 'string', 'role', esc_html__( 'Minimum Role for Strong Passwords', 'l10n-mainwp-ithemes-security-extension' ) ),
			array( 'canonical-roles', 'role', esc_html__( 'Minimum Role for Strong Passwords', 'l10n-mainwp-ithemes-security-extension' ) ),
		);
	}

	/**
	 * Enqueue script to check password strength
	 *
	 * @return void
	 */
	public function add_scripts() {

		global $mainwp_itsec_globals;

		$module_path = MainWP_ITSEC_Lib::get_module_path( __FILE__ );

		wp_enqueue_script( 'mainwp-itsec_strong_passwords', $module_path . 'js/script.js', array( 'jquery' ), MainWP_ITSEC_Core::get_plugin_build() );

	}


}
