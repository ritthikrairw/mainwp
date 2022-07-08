<?php

final class MainWP_ITSEC_Ban_Users_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 1;
	
	
	public function __construct() {
		$this->id = 'ban-users';
		$this->title = __( 'Ban Users', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Block specific IP addresses and user agents from accessing the site.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		
		parent::__construct();
	}
		
	protected function render_settings( $form ) {
		
?>
		
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-ban-users-default"><?php _e( 'Default Ban List', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
		<?php $form->add_checkbox( 'default' ); ?>
			<p class="description"><?php _e( 'As a getting-started point you can include the HackRepair.com ban list developed by Jim Walker.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'Custom Bans', 'mainwp-wordfence-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-ban-users-enable_ban_lists"><?php _e( 'Enable Ban Lists', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'enable_ban_lists', array( 'class' => 'itsec-settings-toggle' ) ); ?>		
		</div>
	</div>
	<div class="ui grid field  itsec-ban-users-enable_ban_lists-content">
		<label class="six wide column middle aligned" for="itsec-ban-users-agent_list"><?php _e( 'Limit Banned IPs in Server Configuration Files', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_text( 'server_config_limit' ); ?>
			<p class="description"><?php _e( 'Limiting the number of IPs blocked by the Server Configuration Files (.htaccess and nginx.conf) will help reduce the risk of a server timeout when updating the configuration file. If the number of IPs in the banned list exceeds the Server Configuration File limit, the additional IPs will be blocked using PHP. Blocking IPs at the server level is more efficient than blocking IPs at the application level using PHP.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field itsec-ban-users-enable_ban_lists-content">
		<label class="six wide column middle aligned" for="itsec-ban-users-agent_list"><?php _e( 'Ban User Agents', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_textarea( 'agent_list', array( 'wrap' => 'off' ) ); ?>
			<p class="description"><?php _e( 'Enter a list of user agents that will not be allowed access to your site. Add one user agent per-line.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
<?php
		
	}
}

new MainWP_ITSEC_Ban_Users_Settings_Page();
