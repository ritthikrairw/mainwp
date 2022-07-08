<?php

final class MainWP_ITSEC_WordPress_Salts_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'wordpress-salts';
		$this->title = __( 'WordPress Salts', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Update the secret keys WordPress uses to increase the security of your site.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		
		parent::__construct();
	}
	
	protected function render_settings( $form ) {
		
?>
	<div class="itsec-write-files-enabled">
		<p><strong>Note that changing the salts will log you out of your WordPress site.</strong></p>
		<table class="form-table itsec-settings-section">
			<tr>
				<th scope="row"><label for="itsec-wordpress-salts-regenerate"><?php _e( 'Change WordPress Salts', 'l10n-mainwp-ithemes-security-extension' ); ?></label></th>
				<td>
					<?php $form->add_checkbox( 'regenerate' ); ?>
					<br />
					<p class="description"><?php _e( 'Check this box and then save settings to change your WordPress Salts.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
				</td>
			</tr>
		</table>
	</div>
	<div class="itsec-write-files-disabled">
		<div class="itsec-warning-message"><?php _e( 'The "Write to Files" setting is disabled in Global Settings. In order to use this feature, you must enable the "Write to Files" setting.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	</div>
<?php
		
	}
}

new MainWP_ITSEC_WordPress_Salts_Settings_Page();
