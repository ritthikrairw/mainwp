<?php

final class MainWP_ITSEC_Multisite_Tweaks_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'multisite-tweaks';
		$this->title = __( 'Multisite Tweaks', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Advanced settings that improve security by changing default WordPress Multisite behavior.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		
		parent::__construct();
	}
	
	
	protected function render_settings( $form ) {
		
?>
	<p><?php _e( 'Note: These settings are listed as advanced because they block common forms of attacks but they can also block legitimate plugins and themes that rely on the same techniques. When activating the settings below, we recommend enabling them one by one to test that everything on your site is still working as expected.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
	<p><?php _e( 'Remember, some of these settings might conflict with other plugins or themes, so test your site after enabling each setting.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="itsec-multisite-tweaks-theme_updates"><?php _e( 'Theme Update Notifications', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'theme_updates' ); ?>
				<label for="itsec-multisite-tweaks-theme_updates"><?php _e( 'Hide Theme Update Notifications', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
				<p class="description"><?php _e( 'Hides theme update notifications from users who cannot update themes. Please note that this only makes a difference in multi-site installations.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-multisite-tweaks-plugin_updates"><?php _e( 'Plugin Update Notifications', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'plugin_updates' ); ?>
				<label for="itsec-multisite-tweaks-plugin_updates"><?php _e( 'Hide Plugin Update Notifications', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
				<p class="description"><?php _e( 'Hides plugin update notifications from users who cannot update plugins. Please note that this only makes a difference in multi-site installations.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-multisite-tweaks-core_updates"><?php _e( 'Core Update Notifications', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
			<td>
				<?php $form->add_checkbox( 'core_updates' ); ?>
				<label for="itsec-multisite-tweaks-core_updates"><?php _e( 'Hide Core Update Notifications', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
				<p class="description"><?php _e( 'Hides core update notifications from users who cannot update core. Please note that this only makes a difference in multi-site installations.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</td>
		</tr>
	</table>
<?php
		
	}
}

new MainWP_ITSEC_Multisite_Tweaks_Settings_Page();
