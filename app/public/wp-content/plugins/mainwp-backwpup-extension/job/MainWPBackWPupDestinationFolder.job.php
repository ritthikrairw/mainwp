<?php

class MainWPBackWPupDestinationFolder extends MainWPBackWPupJob {

	public function save_form( $settings ) {

		$settings['backupdir']          = ( isset( $_POST['backupdir'] ) ? $_POST['backupdir'] : '' );
		$settings['maxbackups']         = ( isset( $_POST['maxbackups'] ) ? (int) $_POST['maxbackups'] : 0 );
		$settings['backupsyncnodelete'] = ( ( isset( $_POST['backupsyncnodelete'] ) && $_POST['backupsyncnodelete'] == 1 ) ? true : false );

		return $settings;
	}

	public function render_form( $information ) {
		$default = $information['default'];
		?>
		<div ng-show="is_selected_2('<?php echo $this->tab_name; ?>')">
			<div class="ui form segment">
			<form action="<?php echo esc_attr( $this->current_page ); ?>" method="post">
				<input type="hidden" name="our_id" value="<?php echo esc_attr( $this->our_id ); ?>">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>">
				<input type="hidden" name="website_id" value="<?php echo esc_attr( $this->website_id ); ?>">
				<input type="hidden" name="job_tab" value="<?php echo esc_attr( $this->original_tab_name ); ?>">
				<?php wp_nonce_field( MainWPBackWPupExtension::$nonce_token . 'update_jobs' ); ?>
				<h3 class="ui dividing header"><?php _e( 'Settings for database check', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Folder to store backups in', $this->plugin_translate ); ?></label>
					<div class="ten wide column">
						<input name="backupdir" id="idbackupdir" type="text"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'backupdir', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File deletion', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<?php if ( $information['information']['backuptype'] == 'archive' ) : ?>
							<input name="maxbackups" id="idmaxbackups" type="text"
										 size="3"
										 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'maxbackups', '' ) ); ?>"
										 class="small-text help-tip"
										 title="<?php esc_attr_e( 'Oldest files will be deleted first. 0 = no deletion', $this->plugin_translate ); ?>"/>&nbsp;
								<?php _e( 'Number of files to keep in folder.', $this->plugin_translate ); ?>
						<?php else : ?>
							<input class="checkbox" value="1"
								 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'backupsyncnodelete', false ), true ); ?>
								 name="backupsyncnodelete"
								 id="idbackupsyncnodelete"/> <?php _e( 'Do not delete files while syncing to destination!', $this->plugin_translate ); ?>
						<?php endif; ?>
					</div>
				</div>


				<div class="ui divider"></div>
				<input type="submit" name="submit" id="submit" class="ui big green right floated button" value="<?php _e( 'Save Changes', $this->plugin_translate ); ?>"/>
				<div class="ui hidden clearing divider"></div>
			</form>
			</div>
		</div>
		<?php
	}
}
