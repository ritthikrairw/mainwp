<?php

final class MainWP_ITSEC_System_Tweaks_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'system-tweaks';
		$this->title = __( 'System Tweaks', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Make changes to the server configuration for this site.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		
		parent::__construct();
	}
	
	
	protected function render_settings( $form ) {
		
?>
	<div class="ui green message"><?php _e( 'Note: These settings are listed as advanced because they block common forms of attacks but they can also block legitimate plugins and themes that rely on the same techniques. When activating the settings below, we recommend enabling them one by one to test that everything on your site is still working as expected.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	<div class="ui green message"><?php _e( 'Remember, some of these settings might conflict with other plugins or themes, so test your site after enabling each setting.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	
	<div class="ui dividing header"><?php _e( 'File Access', 'mainwp-wordfence-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-system-tweaks-protect_files"><?php _e( 'Protect System Files', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'protect_files' ); ?>
			<p class="description"><?php _e( 'Prevent public access to readme.html, readme.txt, wp-config.php, install.php, wp-includes, and .htaccess. These files can give away important information on your site and serve no purpose to the public once WordPress has been successfully installed.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-system-tweaks-directory_browsing"><?php _e( 'Disable Directory Browsing', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'directory_browsing' ); ?>
			<p class="description"><?php _e( 'Prevents users from seeing a list of files in a directory when no index file is present.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
	<div class="ui dividing header"><?php _e( 'PHP Execution', 'mainwp-wordfence-extension' ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-system-tweaks-uploads_php"><?php _e( 'Disable PHP in Uploads', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'uploads_php' ); ?>
			<p class="description"><?php _e( 'Disable PHP execution in the uploads directory. This will prevent uploading of malicious scripts to uploads.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-system-tweaks-plugins_php"><?php _e( 'Disable PHP in Plugins', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'plugins_php' ); ?>
			<p class="description"><?php esc_html_e( 'Disable PHP execution in the plugins directory. This blocks requests to PHP files inside plugin directories that can be exploited directly.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-system-tweaks-themes_php"><?php _e( 'Disable PHP in Themes', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_checkbox( 'themes_php' ); ?>
			<p class="description"><?php esc_html_e( 'Disable PHP execution in the themes directory. This blocks requests to PHP files inside theme directories that can be exploited directly.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
<?php
	}
}

new MainWP_ITSEC_System_Tweaks_Settings_Page();
