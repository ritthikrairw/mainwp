<?php

final class MainWP_ITSEC_SSL_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 1;


	public function __construct() {
		$this->id          = 'ssl';
		$this->title       = __( 'SSL', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Configure use of SSL to ensure that communications between browsers and the server are secure.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type        = 'recommended';

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {
		$vars = array(
			'translations' => array(
				'ssl_warning' => __( 'Are you sure you want to enable SSL? If your server does not support SSL you will be locked out of your WordPress Dashboard.', 'l10n-mainwp-ithemes-security-extension' ),
			),
		);

		wp_enqueue_script( 'mainwp-itsec-ssl-admin-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-ssl-admin-script', 'itsec_ssl', $vars );
	}


	protected function render_settings( $form ) {
		global $mainwp_itheme_site_data_values;
		if ( ! is_array( $mainwp_itheme_site_data_values ) ) {
			$mainwp_itheme_site_data_values = array();
		}
		$ssl_is_enabled = false;

		if ( isset( $mainwp_itheme_site_data_values['require_ssl'] ) ) {
			if ( 'enabled' === $mainwp_itheme_site_data_values['require_ssl'] || ( 'advanced' === $mainwp_itheme_site_data_values['require_ssl'] && $mainwp_itheme_site_data_values['admin'] ) ) {
				$ssl_is_enabled = true;
			}
		}

		$ssl_support_probability = isset( $mainwp_itheme_site_data_values['has_ssl'] ) ? $mainwp_itheme_site_data_values['has_ssl'] : 0;

		$frontend_modes = array(
			0 => __( 'Off', 'l10n-mainwp-ithemes-security-extension' ),
			1 => __( 'Per Content', 'l10n-mainwp-ithemes-security-extension' ),
			2 => __( 'Whole Site', 'l10n-mainwp-ithemes-security-extension' ),
		);

		if ( isset( $mainwp_itheme_site_data_values['require_ssl'] ) && 'advanced' === $mainwp_itheme_site_data_values['require_ssl'] ) {
			$hide_advanced_setting = '';
		} else {
			$hide_advanced_setting = ' style="display:none;"';
		}

		$require_ssl_options = array(
			'disabled' => esc_html__( 'Disabled', 'l10n-mainwp-ithemes-security-extension' ),
			'enabled'  => esc_html__( 'Enabled', 'l10n-mainwp-ithemes-security-extension' ),
			'advanced' => esc_html__( 'Advanced', 'l10n-mainwp-ithemes-security-extension' ),
		);

		if ( 100 === $ssl_support_probability ) {
			$require_ssl_options['enabled'] = esc_html( 'Enabled (recommended)', 'l10n-mainwp-ithemes-security-extension' );
		}

		?>
	
		<?php if ( ! $ssl_is_enabled ) : ?>
		<div class="ui yellow message"><?php esc_html_e( 'Note: After enabling this feature, you will be logged out and you will have to log back in. This is to prevent possible cookie conflicts that could make it more difficult to get in otherwise.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<?php endif; ?>
		
	<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-ssl-require_ssl"><?php _e( 'Redirect All HTTP Page Requests to HTTPS', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_select( 'require_ssl', $require_ssl_options ); ?>
				<ul>
					<li><?php echo wp_kses( __( '<strong>Disabled</strong> - Use the site\'s default handling of page requests.', 'l10n-mainwp-ithemes-security-extension' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Enabled</strong> - Redirect all http page requests to https.', 'l10n-mainwp-ithemes-security-extension' ), array( 'strong' => array() ) ); ?></li>
					<li><?php echo wp_kses( __( '<strong>Advanced</strong> - Choose different settings for front-end and dashboard page requests.', 'l10n-mainwp-ithemes-security-extension' ), array( 'strong' => array() ) ); ?></li>
				</ul>
			</div>
		</div>

		<div class="ui grid field itsec-ssl-advanced-setting" <?php echo $hide_advanced_setting; ?>>
			<label class="six wide column middle aligned" for="itsec-ssl-frontend"><?php _e( 'Front End SSL Mode', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
			<?php $form->add_select( 'frontend', $frontend_modes ); ?>
				<p class="description"><?php _e( 'Enables secure SSL connection for the front-end (public parts of your site). Turning this off will disable front-end SSL control, turning this on "Per Content" will place a checkbox on the edit page for all posts and pages (near the publish settings) allowing you to turn on SSL for selected pages or posts. Selecting "Whole Site" will force the whole site to use SSL.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>

		<div class="ui grid field itsec-ssl-advanced-setting" <?php echo $hide_advanced_setting; ?>>
			<label class="six wide column middle aligned" for="itsec-ssl-admin"><?php _e( 'Force SSL for Dashboard', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_checkbox( 'admin' ); ?>
				<p class="description"><?php _e( 'Forces all dashboard access to be served only over an SSL connection.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>
		<?php
	}
}

new MainWP_ITSEC_SSL_Settings_Page();
