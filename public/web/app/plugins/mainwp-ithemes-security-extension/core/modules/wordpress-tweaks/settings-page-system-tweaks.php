<?php

final class MainWP_ITSEC_System_Tweaks_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'system-tweaks';
		$this->title = __( 'System Tweaks', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Advanced settings that improve security by changing the server config for this site.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		
		parent::__construct();
	}
	
	
	protected function render_settings( $form ) {
		
?>
	<p><?php _e( 'Note: These settings are listed as advanced because they block common forms of attacks but they can also block legitimate plugins and themes that rely on the same techniques. When activating the settings below, we recommend enabling them one by one to test that everything on your site is still working as expected.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
	<p><?php _e( 'Remember, some of these settings might conflict with other plugins or themes, so test your site after enabling each setting.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="itsec-system-tweaks-protect_files"><?php _e( 'System Files', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'protect_files' ); ?>
				<label for="itsec-system-tweaks-protect_files"><?php _e( 'Protect System Files', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
				<p class="description"><?php _e( 'Prevent public access to readme.html, readme.txt, wp-config.php, install.php, wp-includes, and .htaccess. These files can give away important information on your site and serve no purpose to the public once WordPress has been successfully installed.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-system-tweaks-directory_browsing"><?php _e( 'Directory Browsing', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'directory_browsing' ); ?>
				<label for="itsec-system-tweaks-directory_browsing"><?php _e( 'Disable Directory Browsing', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
				<p class="description"><?php _e( 'Prevents users from seeing a list of files in a directory when no index file is present.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</td>
		</tr>
		
		<tr>
			<th scope="row"><label for="itsec-system-tweaks-write_permissions"><?php _e( 'File Writing Permissions', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'write_permissions' ); ?>
				<label for="itsec-system-tweaks-write_permissions"><?php _e( 'Remove File Writing Permissions', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
				<p class="description"><?php _e( 'Prevents scripts and users from being able to write to the wp-config.php file and .htaccess file. Note that in the case of this and many plugins this can be overcome however it still does make the files more secure. Turning this on will set the UNIX file permissions to 0444 on these files and turning it off will set the permissions to 0664.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</td>
		</tr>

		
		<tr>
			<th scope="row"><label for="itsec-system-tweaks-uploads_php"><?php _e( 'Uploads', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'uploads_php' ); ?>
				<label for="itsec-system-tweaks-uploads_php"><?php _e( 'Disable PHP in Uploads', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
				<p class="description"><?php _e( 'Disable PHP execution in the uploads directory. This will prevent uploading of malicious scripts to uploads.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</td>
		</tr>
	</table>
<?php
		
	}
}

new MainWP_ITSEC_System_Tweaks_Settings_Page();
