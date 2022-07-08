<?php

final class MainWP_ITSEC_Backup_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 3;


	public function __construct() {
		$this->id          = 'backup';
		$this->title       = __( 'Database Backups', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Create backups of your site\'s database. The backups can be created manually and on a schedule.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type        = 'recommended';

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {
		global $mainwp_itsec_globals, $mainwp_itheme_site_data_values;
		wp_enqueue_script( 'jquery-multi-select', plugins_url( 'js/jquery.multi-select.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		$vars = array(
			'default_backup_location' => is_array( $mainwp_itheme_site_data_values ) && isset( $mainwp_itheme_site_data_values['default_location'] ) ? $mainwp_itheme_site_data_values['default_location'] : '',
			'available_tables_label'  => __( 'Tables for Backup', 'l10n-mainwp-ithemes-security-extension' ),
			'excluded_tables_label'   => __( 'Excluded Tables', 'l10n-mainwp-ithemes-security-extension' ),
			'creating_backup_text'    => __( 'Creating Backup...', 'l10n-mainwp-ithemes-security-extension' ),
			'available'               => __( 'Tables for Backup', 'l10n-mainwp-ithemes-security-extension' ),
			'excluded'                => __( 'Excluded Tables', 'l10n-mainwp-ithemes-security-extension' ),
			'success'                 => __( 'Backup Completed.', 'l10n-mainwp-ithemes-security-extension' ),
			'fail'                    => __( 'Something went wrong with your backup. It looks like another process might already be trying to backup your database. Please try again in a few minutes. If the problem persists please contact support.', 'l10n-mainwp-ithemes-security-extension' ),
			'mainwp_itheme_url'       => $mainwp_itsec_globals['mainwp_itheme_url'],
		);

		wp_enqueue_script( 'mainwp-itsec-backup-settings-page-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery', 'jquery-multi-select' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-backup-settings-page-script', 'mainwp_itsec_backup_local', $vars );

		wp_enqueue_style( 'mainwp-itsec-backup-settings-page-style', plugins_url( 'css/settings-page.css', __FILE__ ), array(), $this->script_version );
	}

	public function handle_ajax_request( $data ) {
		global $itsec_backup;

		if ( ! isset( $itsec_backup ) ) {
			require_once 'class-itsec-backup.php';
			$itsec_backup = new MainWP_ITSEC_Backup();
			$itsec_backup->run();
		}

		// $result = $itsec_backup->do_backup( true );
		$message = '';

		if ( is_wp_error( $result ) ) {
			$errors = MainWP_ITSEC_Response::get_error_strings( $result );

			foreach ( $errors as $error ) {
				$message .= '<div class="error inline"><p><strong>' . $error . '</strong></p></div>';
			}
		} elseif ( is_string( $result ) ) {
			$message = '<div class="updated fade inline"><p><strong>' . $result . '</strong></p></div>';
		} else {
			$message = '<div class="error inline"><p><strong>' . sprintf( __( 'The backup request returned an unexpected response. It returned a response of type <code>%1$s</code>.', 'l10n-mainwp-ithemes-security-extension' ), gettype( $result ) ) . '</strong></p></div>';
		}

		MainWP_ITSEC_Response::set_response( $message );
	}

	protected function render_settings( $form ) {
		$settings = $form->get_options();
		$methods  = array(
			0 => __( 'Save Locally and Email', 'l10n-mainwp-ithemes-security-extension' ),
			1 => __( 'Email Only', 'l10n-mainwp-ithemes-security-extension' ),
			2 => __( 'Save Locally Only', 'l10n-mainwp-ithemes-security-extension' ),
		);

		$excludes      = array();
		$is_individual = MainWP_IThemes_Security::is_manage_site();
		if ( $is_individual ) {
			global $mainwp_itheme_site_data_values;
			$excludes = isset( $mainwp_itheme_site_data_values['excludable_tables'] ) && is_array( $mainwp_itheme_site_data_values['excludable_tables'] ) ? $mainwp_itheme_site_data_values['excludable_tables'] : array();
		}
		?>
	<div class="hide-if-no-js">
		<p><?php _e( 'Press the button below to create a database backup using the saved settings.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		<p class="submit">
			<input type="button" id="mwp_itheme_backups_db_btn" class="button ui green" value="<?php echo __( 'Create a Database Backup', 'l10n-mainwp-ithemes-security-extension' ); ?>" />			
		</p>
		<div id="itsec_backup_status"></div>
	</div>

	<div class="ui dividing header"><?php _e( 'Scheduling', 'mainwp-wordfence-extension' ); ?></div>		
	<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-backup-enabled"><?php _e( 'Schedule Database Backups', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
			<?php $form->add_checkbox( 'enabled', array( 'class' => 'itsec-settings-toggle' ) ); ?>
			</div>
		</div>

		<div class="ui grid field itsec-backup-enabled-content">
			<label class="six wide column middle aligned" for="itsec-backup-interval"><?php _e( 'Backup Interval', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_text( 'interval' ); ?>
				<p class="description"><?php _e( 'The number of days between database backups.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>
		<div class="ui dividing header"><?php _e( 'Configuration', 'mainwp-wordfence-extension' ); ?></div>

		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-backup-method"><?php _e( 'Backup Method', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_select( 'method', $methods ); ?>
				<p class="description"><?php _e( 'Select what we should do with your backup file. You can have it emailed to you, saved locally or both.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>

		<div class="ui grid field itsec-backup-method-file-content">
			<label class="six wide column middle aligned" for="itsec-backup-location"><?php _e( 'Backup Location', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php
				if ( $is_individual ) {
					?>
									
					<?php $form->add_text( 'location', array( 'class' => 'large-text' ) ); ?>
					<label for="itsec-backup-location"><?php _e( 'The path on your machine where backup files should be stored.', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
					<p class="description"><?php _e( 'This path must be writable by your website. For added security, it is recommended you do not include it in your website root folder.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
					<div class="hide-if-no-js">
						<?php
						$form->add_button(
							'reset_backup_location',
							array(
								'value' => __( 'Restore Default Location', 'l10n-mainwp-ithemes-security-extension' ),
								'class' => 'button ui basic green',
							)
						);
						?>
					</div>
					<?php
				} else {
					$form->add_checkbox( 'use_individual_location' );
					?>
					<label for="itsec-backup-use_individual_location"><?php _e( 'Use value from individual site settings', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
					<p class="description"><?php _e( 'Use "Backup Location" value from individual site settings. Uncheck will do not update the value.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>				
					<?php
				}
				?>
						
				</div>
		</div>

		<div class="ui grid field itsec-backup-method-file-content">
			<label class="six wide column middle aligned" for="itsec-backup-retain"><?php _e( 'Backups to Retain', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_text( 'retain' ); ?>
				<p class="description"><?php _e( 'Limit the number of backups stored locally (on this server). Any older backups beyond this number will be removed. Setting to "0" will retain all backups.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-backup-zip"><?php _e( 'Compress Backup Files', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_checkbox( 'zip' ); ?>
				<p class="description"><?php _e( 'You may need to turn this off if you are having problems with backups.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			</div>
		</div>

		<div class="ui dividing header"><?php _e( 'Backup Tables', 'mainwp-wordfence-extension' ); ?></div>

		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-backup-exclude"><?php _e( 'Exclude Tables', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			<div class="ten wide column ui">
			<?php
			if ( $is_individual ) {
				?>
					<label for="itsec-backup-exclude"><?php _e( 'Tables with data that does not need to be backed up', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
					<div id="backup_multi_select_wrap">
					<?php $form->add_multi_select( 'exclude', $excludes ); ?>
					</div>
					<p class="description"><?php _e( 'Some plugins can create log files in your database. While these logs might be handy for some functions, they can also take up a lot of space and, in some cases, even make backing up your database almost impossible. Select log tables above to exclude their data from the backup. Note: The table itself will be backed up, but not the data in the table.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
					<p>
						<input type="button" id="mwp_itheme_backups_reload_exclude_tables_btn" class="button ui green" value="<?php echo __( 'Reload excludable tables', 'l10n-mainwp-ithemes-security-extension' ); ?>" />&nbsp;
						<span id="itsec_reload_exclude_status"></span>
					</p>
					
					<?php
			} else {
				$form->add_checkbox( 'use_individual_exclude' );
				?>
					<label for="itsec-backup-use_individual_exclude"><?php _e( 'Use value from individual site settings', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
					<p class="description"><?php _e( 'Use "Exclude Tables" value from individual site settings. Uncheck will do not update the value.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>									
					<?php
			}
			?>
					
			</div>
		</div>
		<?php

	}
}

new MainWP_ITSEC_Backup_Settings_Page();
