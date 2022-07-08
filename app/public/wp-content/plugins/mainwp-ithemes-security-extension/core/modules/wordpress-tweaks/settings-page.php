<?php

final class MainWP_ITSEC_WordPress_Tweaks_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'wordpress-tweaks';
		$this->title = __( 'WordPress Tweaks', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Disables the WordPress file editor for plugins and themes. Once activated you will need to manually edit files using FTP or other tools.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';

		parent::__construct();
	}

	protected function render_settings( $form ) {
		$settings = $form->get_options();

		$xmlrpc_options = array(
			'disable' => __( 'Disable XML-RPC', 'l10n-mainwp-ithemes-security-extension' ),
			'disable_pingbacks' => __( 'Disable Pingbacks', 'l10n-mainwp-ithemes-security-extension' ),
			'enable' => __( 'Enable XML-RPC', 'l10n-mainwp-ithemes-security-extension' ),
		);

        $rest_api_options = array(
			'restrict-access' => esc_html__( 'Restricted Access', 'l10n-mainwp-ithemes-security-extension' ),
			'default-access'  => esc_html__( 'Default Access', 'l10n-mainwp-ithemes-security-extension' ),
		);

        $valid_user_login_types = array(
			'both'     => esc_html__( 'Email Address and Username', 'l10n-mainwp-ithemes-security-extension' ),
			'email'    => esc_html__( 'Email Address Only', 'l10n-mainwp-ithemes-security-extension' ),
			'username' => esc_html__( 'Username Only', 'l10n-mainwp-ithemes-security-extension' ),
		);


?>
	<div class="ui green message"><?php _e( 'Note: These settings are listed as advanced because they block common forms of attacks but they can also block legitimate plugins and themes that rely on the same techniques. When activating the settings below, we recommend enabling them one by one to test that everything on your site is still working as expected.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui green message"><?php _e( 'Remember, some of these settings might conflict with other plugins or themes, so test your site after enabling each setting.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-wordpress-tweaks-file_editor"><?php _e( 'Disable File Editor', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'file_editor' ); ?>
			<p class="description"><?php _e( 'Disables the file editor for plugins and themes requiring users to have access to the file system to modify files. Once activated you will need to manually edit theme and other files using a tool other than WordPress.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'API Access', 'mainwp-wordfence-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-wordpress-tweaks-disable_xmlrpc"><?php _e( 'XML-RPC', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">			
			<?php $form->add_select( 'disable_xmlrpc', $xmlrpc_options ); ?>
			<ul>
				<li><?php _e( '<strong>Disable XML-RPC</strong> - XML-RPC is disabled on the site. This setting is highly recommended if Jetpack, the WordPress mobile app, pingbacks, and other services that use XML-RPC are not used.', 'l10n-mainwp-ithemes-security-extension' ); ?></li>
				<li><?php _e( '<strong>Disable Pingbacks</strong> - Only disable pingbacks. Other XML-RPC features will work as normal. Select this setting if you require features such as Jetpack or the WordPress Mobile app.', 'l10n-mainwp-ithemes-security-extension' ); ?></li>
				<li><?php _e( '<strong>Enable XML-RPC</strong> - XML-RPC is fully enabled and will function as normal. Use this setting only if the site must have unrestricted use of XML-RPC.', 'l10n-mainwp-ithemes-security-extension' ); ?></li>
			</ul>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-wordpress-tweaks-allow_xmlrpc_multiauth"><?php _e( 'Allow Multiple Authentication Attempts per XML-RPC Request', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">			
			<?php $form->add_checkbox( 'allow_xmlrpc_multiauth' ); ?>
			<p><?php _e( 'WordPress\' XML-RPC feature allows hundreds of username and password guesses per request. Use the recommended "Block" setting below to prevent attackers from exploiting this feature.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-wordpress-tweaks-rest_api"><?php _e( 'REST API', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">			
			<?php $form->add_select( 'rest_api', $rest_api_options ); ?>
			<p class="description"><?php esc_html_e( 'The WordPress REST API is part of WordPress and provides developers with new ways to manage WordPress. By default, it could give public access to information that you believe is private on your site.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'Users', 'mainwp-wordfence-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-wordpress-tweaks-valid_user_login_type"><?php _e( 'Login with Email Address or Username', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_select( 'valid_user_login_type', $valid_user_login_types ); ?>
			<p class="description"><?php esc_html_e( 'By default, WordPress allows users to log in using either an email address or username. This setting allows you to restrict logins to only accept email addresses or usernames.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-wordpress-tweaks-force_unique_nicename"><?php _e( 'Force Unique Nickname', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'force_unique_nicename' ); ?>
			<p class="description"><?php _e( 'This forces users to choose a unique nickname when updating their profile or creating a new account which prevents bots and attackers from easily harvesting user\'s login usernames from the code on author pages. Note this does not automatically update existing users as it will affect author feed urls if used.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-wordpress-tweaks-disable_unused_author_pages"><?php _e( 'Disable Extra User Archives', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'disable_unused_author_pages' ); ?>
			<p class="description"><?php _e( 'This makes it harder for bots to determine usernames by disabling post archives for users that don\'t post to your site.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
<?php
	}
}

new MainWP_ITSEC_WordPress_Tweaks_Settings_Page();
