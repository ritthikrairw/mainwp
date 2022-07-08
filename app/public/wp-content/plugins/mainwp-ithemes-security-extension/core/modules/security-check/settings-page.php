<?php

final class MainWP_ITSEC_Security_Check_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 1;


	public function __construct() {
		$this->id = 'security-check';
		$this->title = __( 'Security Check', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Ensure that your site is using the recommended features and settings.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->information_only = true;
		$this->can_save = false;

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {
		$vars = array(
			'securing_site'                  => __( 'Securing Site...', 'l10n-mainwp-ithemes-security-extension' ),
			'rerun_secure_site'              => __( 'Run Secure Site Again', 'l10n-mainwp-ithemes-security-extension' ),
			'activating_network_brute_force' => __( 'Activating Network Brute Force...', 'l10n-mainwp-ithemes-security-extension' ),
		);

		wp_enqueue_script( 'mainwp-itsec-security-check-settings-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-security-check-settings-script', 'itsec_security_check_settings', $vars );
	}

	public function handle_ajax_request( $data ) {
	}

	protected function render_settings( $form ) {
		$available_modules = MainWP_ITSEC_Modules::get_available_modules();

		$modules_to_activate = array(
			'ban-users'           => __( 'Ban Users', 'l10n-mainwp-ithemes-security-extension' ),
			'backup'              => __( 'Database Backups', 'l10n-mainwp-ithemes-security-extension' ),
			'brute-force'         => __( 'Local Brute Force', 'l10n-mainwp-ithemes-security-extension' ),
			'malware-scheduling'  => __( 'Malware Scan Scheduling', 'l10n-mainwp-ithemes-security-extension' ),
			'network-brute-force' => __( 'Network Brute Force', 'l10n-mainwp-ithemes-security-extension' ),
			'strong-passwords'    => __( 'Strong Passwords', 'l10n-mainwp-ithemes-security-extension' ),
			'two-factor'          => __( 'Two-Factor Authentication', 'l10n-mainwp-ithemes-security-extension' ),
			//'user-logging'        => __( 'User Logging', 'l10n-mainwp-ithemes-security-extension' ),
			'wordpress-tweaks'    => __( 'WordPress Tweaks', 'l10n-mainwp-ithemes-security-extension' ),
		);

		foreach ( $modules_to_activate as $module => $val ) {
			if ( ! in_array( $module, $available_modules ) ) {
				unset( $modules_to_activate[$module] );
			}
		}

?>
	<div id="itsec_security_check_status"></div>
	<div id="itsec-security-check-details-container">
		<p><?php _e( 'Some features and settings are recommended for every site to run. This tool will ensure that your site is using these recommendations.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		<p><?php _e( 'When the button below is clicked the following modules will be enabled and configured:', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		<ul class="itsec-security-check-list">
			<?php foreach ( $modules_to_activate as $name ) : ?>
				<li><p><?php echo $name; ?></p></li>
			<?php endforeach; ?>
		</ul>
	</div>

	<p><?php $form->add_button( 'secure_site', array( 'value' => 'Secure Site', 'class' => 'button ui green' ) ); ?></p>
<?php

	}
}
new MainWP_ITSEC_Security_Check_Settings_Page();
