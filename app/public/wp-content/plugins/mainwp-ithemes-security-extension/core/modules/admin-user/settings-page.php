<?php

final class MainWP_ITSEC_Admin_User_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 1;
	
	
	public function __construct() {
		$this->id = 'admin-user';
		$this->title = __( 'Admin User', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'An advanced tool that removes users with a username of "admin" or a user ID of "1".', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'advanced';
		$this->custom_save	= false;	
		parent::__construct();
	}
	
	public function enqueue_scripts_and_styles() {			
		wp_enqueue_script( 'mainwp-itsec-admin-user-settings-page-script', plugins_url( 'js/admin-admin-user.js', __FILE__ ) , array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-admin-user-settings-page-script', 'mainwp_itsec_admin_user_local', array(
			'success' => __('Successful', 'mainwp'),
			'fail' => __( 'The new admin username you entered is invalid or WordPress could not change the user id or username. Please check the name and try again.', 'l10n-mainwp-ithemes-security-extension' ),			
			'child_admin_msg' => __('Changing this user will break communication between your Dashboard and Child site. To fix this issue, please read this <a target="_blank" href="http://docs.mainwp.com/known-plugin-conflicts/">Help Document</a>.', 'mainwp')
		) );
	}
	
	
	protected function render_settings( $form ) {
		global $mainwp_itsec_globals;		
?>
	<div class="ui yellow message"><?php printf( __( 'The changes made by this tool could cause compatibility issues with some plugins, themes, or customizations. Ensure that you <a href="%s">create a database backup</a> before using this tool.', 'l10n-mainwp-ithemes-security-extension' ), esc_url( MainWP_ITSEC_Core::get_backup_creation_page_url() ) ); ?></div>
	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-admin-user-new_username"><?php _e( 'New Admin Username', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
		<?php $form->add_text( 'new_username', array( 'class' => 'code' ) ); ?>
			<p class="description"><?php _e( 'Enter a new username to replace "admin." Please note that if you are logged in as admin you will have to log in again.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned" for="itsec-admin-user-change_id"><?php _e( 'Change User ID 1', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
		<?php $form->add_checkbox( 'change_id', array( 'label' => __( 'Change the ID of the user with ID 1.', 'l10n-mainwp-ithemes-security-extension' ) ) ); ?>
		</div>
	</div>
<?php
	}
}

new MainWP_ITSEC_Admin_User_Settings_Page();

