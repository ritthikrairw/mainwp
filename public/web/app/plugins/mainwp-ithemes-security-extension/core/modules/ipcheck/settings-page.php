<?php

final class MainWP_ITSEC_Network_Brute_Force_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	protected $script_version = 1;
	
	
	public function __construct() {
		$this->id = 'network-brute-force';
		$this->title = __( 'Network Brute Force', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Join a network of sites that reports and protects against bad actors on the internet.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		
		parent::__construct();
	}
	
	public function enqueue_scripts_and_styles() {
		$settings = MainWP_ITSEC_Modules::get_settings( $this->id );
		
		$vars = array(
			'resetting_button_text' => __( 'Resetting...', 'l10n-mainwp-ithemes-security-extension' ),
		);
		
		wp_enqueue_script( 'mainwp-itsec-network-brute-force-settings-page-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-network-brute-force-settings-page-script', 'itsec_network_brute_force', $vars );
	}
	
	public function handle_ajax_request( $data ) {
		if ( 'reset-api-key' === $data['method'] ) {
			$defaults = MainWP_ITSEC_Modules::get_defaults( $this->id );
			$results = MainWP_ITSEC_Modules::set_settings( $this->id, $defaults );
			
			MainWP_ITSEC_Response::set_response( $results['saved'] );
			MainWP_ITSEC_Response::add_errors( $results['errors'] );
			MainWP_ITSEC_Response::add_messages( $results['messages'] );
			
			if ( $results['saved'] ) {
				MainWP_ITSEC_Response::reload_module( $this->id );
			} else if ( empty( $results['errors'] ) ) {
				MainWP_ITSEC_Response::add_error( new WP_Error( 'itsec-network-brute-force-settings-page-handle-ajax-request-bad-response', __( 'An unknown error prevented the API key from being reset properly. An unrecognized response was received. Please wait a few minutes and try again.', 'l10n-mainwp-ithemes-security-extension' ) ) );
			}
		}
	}
		
	protected function render_settings( $form ) {
		$settings = $form->get_options();
		$is_individual = MainWP_IThemes_Security::is_manage_site();
?>
	<?php	
		if ( empty( $settings['api_key'] ) || empty( $settings['api_secret'] ) ) : 
		
		?>
		<br />
		<div class="ui green message"><?php _e( 'To get started with iThemes Network Brute Force, please supply your email address and save the settings. This will provide this site with an API key and starts the site protection.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
					
		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-network-brute-force-email"><?php _e( 'Email Address', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_text( 'email', array( 'value' => get_option( 'admin_email' ) ) ); ?>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-network-brute-force-updates_optin"><?php _e( 'Receive Email Updates', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_checkbox( 'updates_optin' ); ?>
			</div>
		</div>

	<?php else : ?>		
		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-network-brute-force-enable_ban"><?php _e( 'Ban Reported IPs', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_checkbox( 'enable_ban' ); ?>
				<p class="description"><?php _e( 'Automatically ban IPs reported as a problem by the network.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>		
		<div class="ui dividing header"><?php _e( 'API Configuration', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-network-brute-force-api_key"><?php _e( 'API Key', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
			<?php if ($is_individual) $form->add_text( 'api_key', array( 'class' => 'code', 'readonly' => 'readonly' ) ); ?>
					<?php $form->add_button( 'reset_api_key', array( 'value' => __( 'Reset API Key', 'l10n-mainwp-ithemes-security-extension' ), 'class' => 'button ui green' ) ); ?>
					<div id="itsec-network-brute-force-reset-status"></div>
			</div>
		</div>
	<?php endif; 		
	}
}

new MainWP_ITSEC_Network_Brute_Force_Settings_Page();
