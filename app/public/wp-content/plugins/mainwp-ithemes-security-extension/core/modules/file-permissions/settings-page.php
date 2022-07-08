<?php

final class MainWP_ITSEC_File_Permissions_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'file-permissions';
		$this->title = __( 'File Permissions', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Lists file and directory permissions of key areas of the site.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';
		$this->information_only = true;
		$this->can_save = false;

		parent::__construct();
	}

	protected function render_settings( $form ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			echo '<p>' . __( 'Click the button to load the current file permissions.', 'l10n-mainwp-ithemes-security-extension' ) . '</p>';
			echo '<p>' . $form->add_button( 'load_file_permissions', array( 'value' => __( 'Load File Permissions Details', 'l10n-mainwp-ithemes-security-extension' ), 'class' => 'button ui green itsec-reload-module' ) ) . '</p>';

			return;
		}
		$error = '';
		
//		$result = MainWP_IThemes_Security::get_instance()->do_specical_modules_update('file-permissions');
//		if (is_array($result)) {
//			if (isset($result['html'])) {		
//				echo $result['html'];
//				return;
//			} else if (isset($result['error'])) {
//				$error = $result['error'];
//			} else {
//				$error = __('Undefined error', 'l10n-mainwp-ithemes-security-extension');
//			}
//		} else {
//			$error = __('Undefined error', 'l10n-mainwp-ithemes-security-extension');
//		}
		
		if (false && !empty($error)) {
			?>		
			<p><?php $form->add_button( 'reload_file_permissions', array( 'value' => __( 'Reload File Permissions Details', 'l10n-mainwp-ithemes-security-extension' ), 'class' => 'button ui green itsec-reload-module' ) ); ?></p>
			<div class="mainwp_info-box-red"><?php echo $error; ?></div>
			<?php
		}

	}
}
new MainWP_ITSEC_File_Permissions_Settings_Page();
