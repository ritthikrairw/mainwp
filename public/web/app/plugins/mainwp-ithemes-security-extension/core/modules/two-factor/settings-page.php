<?php

final class MainWP_ITSEC_Two_Factor_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 1;
	
	
	public function __construct() {
		$this->id = 'two-factor';
		$this->title = __( 'Two Factor', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Two-Factor Authentication greatly increases the security of your WordPress user account by requiring additional information beyond your username and password in order to log in.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->custom_save	= false;	
		parent::__construct();
	}
	
	public function enqueue_scripts_and_styles() {			
		wp_enqueue_script( 'mainwp-itsec-two-factor-settings-page-script', plugins_url( 'js/admin-two-factor.js', __FILE__ ) , array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-two-factor-settings-page-script', 'itsec_two_factor_settings', array() );
	}
	
	
	
	protected function render_settings( $form ) {
		global $mainwp_itsec_globals;
		
		
		$validator = MainWP_ITSEC_Modules::get_validator( $this->id );
		$methods = $validator->get_valid_methods();
		$custom_methods = array(
			"Two_Factor_Totp",
			"Two_Factor_Email",
			"Two_Factor_Backup_Codes"
		);
?>

	<div class="ui dividing header"><?php _e( 'Methods', 'mainwp-wordfence-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-two-factor-available_methods"><?php _e( 'Authentication Methods Available to Users', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
		<?php $form->add_select( 'available_methods', array( 'value' => $methods ) ); ?>
			<p class="description"><?php _e( 'iThemes Security supports multiple two-factor methods: mobile app, email, and backup codes. Selecting “All Methods” is highly recommended so that users can use the method that works the best for them.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui grid field itsec-two-factor-available_methods-content">
		<label class="six wide column middle aligned" for="itsec-two-factor-custom_available_methods"><?php _e( 'Select Available Methods', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'custom_available_methods[]', array( 'value' => 'Two_Factor_Totp', 'id' => 'itsec-two-factor-custom_available_methods_1' ) ); ?>
			<label for="itsec-two-factor-custom_available_methods_1"><?php _e( 'Mobile App', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			<br/>
			<?php $form->add_checkbox( 'custom_available_methods[]', array( 'value' => 'Two_Factor_Email', 'id' => 'itsec-two-factor-custom_available_methods_2' ) ); ?>
			<label for="itsec-two-factor-custom_available_methods_2"><?php _e( 'Email', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			<br/>
			<?php $form->add_checkbox( 'custom_available_methods[]', array( 'value' => 'Two_Factor_Backup_Codes', 'id' => 'itsec-two-factor-custom_available_methods_3' ) ); ?>
			<label for="itsec-two-factor-custom_available_methods_3"><?php _e( 'Backup Authentication Codes', 'l10n-mainwp-ithemes-security-extension' ); ?></label>					
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'Setup Flow', 'mainwp-wordfence-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-two-factor-disable_first_login"><?php _e( 'Disable on First Login', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'disable_first_login' ); ?>
			<p class="description"><?php _e( 'This simplifies the sign up flow for users that require two-factor to be enabled for their account.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-global-on_board_welcome"><?php _e( 'On-board Welcome Text', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_textarea( 'on_board_welcome' ); ?>
			<p class="description"><?php _e( 'Customize the text shown to users at the beginning of the Two-Factor On-Board flow.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
<?php
	}
}

new MainWP_ITSEC_Two_Factor_Settings_Page();

