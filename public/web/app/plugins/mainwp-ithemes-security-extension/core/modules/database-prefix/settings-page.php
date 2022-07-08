<?php

final class MainWP_ITSEC_Database_Prefix_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 1;
		
	public function __construct() {
		$this->id = 'database-prefix';
		$this->title = __( 'Change Database Table Prefix', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Change the database table prefix that WordPress uses.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'advanced';
		
		parent::__construct();
	}
			
	protected function render_description( $form ) {		
?>
	<p><?php _e( 'By default, WordPress assigns the prefix <code>wp_</code> to all tables in the database where your content, users, and objects exist. For potential attackers, this means it is easier to write scripts that can target WordPress databases as all the important table names for 95% of sites are already known. Changing the <code>wp_</code> prefix makes it more difficult for tools that are trying to take advantage of vulnerabilities in other places to affect the database of your site. <strong>Before using this tool, we strongly recommend creating a backup of your database.</strong>', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
	<p><?php _e( 'Note: The use of this tool requires quite a bit of system memory which may be more than some hosts can handle. If you back your database up you can\'t do any permanent damage but without a proper backup you risk breaking your site and having to perform a rather difficult fix.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
	<div class="ui yellow message"><?php printf( __( '<a href="%1$s">Backup your database</a> before using this tool.', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Core::get_backup_creation_page_url() ); ?></div>
<?php		
	}
	
	protected function render_settings( $form ) {
		global $wpdb;
		
		$yes_or_no = array(
			'yes' => __( 'Yes', 'l10n-mainwp-ithemes-security-extension' ),
			'no'  => __( 'No', 'l10n-mainwp-ithemes-security-extension' ),
		);
		
		$form->set_option( 'change_prefix', 'no' );
		
?>
	<div class="itsec-write-files-disabled">
		<div class="ui yellow message"><?php _e( 'The "Write to Files" setting is disabled in Global Settings. In order to use this feature, you must enable the "Write to Files" setting.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	</div>
	
	<div class="ui grid field itsec-write-files-enabled">
		<label class="six wide column middle aligned" for="itsec-database-prefix-change_prefix"><?php _e( 'Change Prefix', 'mainwp-wordfence-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_select( 'change_prefix', $yes_or_no ); ?>
				<p class="description"><?php _e( 'Select "Yes" and save the settings to change the database table prefix.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>	
<?php
	}
	
	public function handle_form_post( $data ) {
		require_once( dirname( __FILE__ ) . '/utility.php' );
		
		if ( isset( $data['change_prefix'] ) && 'yes' === $data['change_prefix'] ) {
			$result = MainWP_ITSEC_Database_Prefix_Utility::change_database_prefix();
			
			MainWP_ITSEC_Response::add_errors( $result['errors'] );
			MainWP_ITSEC_Response::reload_module( $this->id );
			
			if ( false === $result['new_prefix'] ) {
				MainWP_ITSEC_Response::set_success( false );
			} else {
				/* translators: 1: New database table prefix */
				MainWP_ITSEC_Response::add_message( sprintf( __( 'The database table prefix was successfully changed to <code>%1$s</code>.', 'l10n-mainwp-ithemes-security-extension' ), $result['new_prefix'] ) );
			}
		}
	}
}

new MainWP_ITSEC_Database_Prefix_Settings_Page();
