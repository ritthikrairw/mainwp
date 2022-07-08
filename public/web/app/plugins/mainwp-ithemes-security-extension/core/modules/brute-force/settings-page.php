<?php

final class MainWP_ITSEC_Brute_Force_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'brute-force';
		$this->title = __( 'Local Brute Force', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Protect your site against attackers that try to randomly guess login details to your site.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		
		parent::__construct();
	}
		
	protected function render_settings( $form ) {		
?>

<div id="brute_force-settings">
	<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-brute-force-auto_ban_admin"><?php _e( 'Automatically ban "admin" user', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_checkbox( 'auto_ban_admin' ); ?>
				<p class="description"><?php _e( 'Immediately ban a host that attempts to login using the "admin" username.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>
		<div class="ui dividing header"><?php _e( 'Login Attempts', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-brute-force-max_attempts_host"><?php _e( 'Max Login Attempts Per Host', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_text( 'max_attempts_host', array( 'label' => __( 'Attempts', 'l10n-mainwp-ithemes-security-extension' ) ) ); ?>
				<p class="description"><?php _e( 'The number of login attempts a user has before their host or computer is locked out of the system. Set to 0 to record bad login attempts without locking out the host.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-brute-force-max_attempts_user"><?php _e( 'Max Login Attempts Per User', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_text( 'max_attempts_user', array( 'label' => __( 'Attempts', 'l10n-mainwp-ithemes-security-extension' ) ) ); ?>
				<p class="description"><?php _e( 'The number of login attempts a user has before their username is locked out of the system. Note that this is different from hosts in case an attacker is using multiple computers. In addition, if they are using your login name you could be locked out yourself. Set to 0 to log bad login attempts per user without ever locking the user out (this is not recommended).', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-brute-force-check_period"><?php _e( 'Minutes to Remember Bad Login (check period)', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_text( 'check_period', array( 'label' => __( 'Minutes', 'l10n-mainwp-ithemes-security-extension' )  ) ); ?>
				<p class="description"><?php _e( 'The number of minutes in which bad logins should be remembered.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>
</div>
<?php
	}
}

new MainWP_ITSEC_Brute_Force_Settings_Page();
