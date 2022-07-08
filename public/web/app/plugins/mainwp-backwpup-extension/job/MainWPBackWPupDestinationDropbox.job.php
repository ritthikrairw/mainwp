<?php

class MainWPBackWPupDestinationDropbox extends MainWPBackWPupJob {

	public function save_form( $settings ) {
	
		$settings['dropboxsyncnodelete'] = ( ( isset( $_POST['dropboxsyncnodelete'] ) && $_POST['dropboxsyncnodelete'] == 1 ) ? true : false );
		$settings['dropboxmaxbackups']   = ( isset( $_POST['dropboxmaxbackups'] ) ? (int) $_POST['dropboxmaxbackups'] : 0 );

		if ( isset( $_POST['dropboxdir'] ) ) {
			$_POST['dropboxdir'] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( stripslashes( $_POST['dropboxdir'] ) ) ) ) );
			if ( substr( $_POST['dropboxdir'], 0, 1 ) == '/' ) {
				$_POST['dropboxdir'] = substr( $_POST['dropboxdir'], 1 );
			}

			if ( $_POST['dropboxdir'] == '/' ) {
				$_POST['dropboxdir'] = '';
			}

			$settings['dropboxdir'] = $_POST['dropboxdir'];
		}

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

				<div class="ui info message"><?php echo 'To complete the setup, all Cloud Storage Apps need to be authenticated directly on child sites'; ?></div>

				<h3 class="ui dividing header"><?php _e( 'Backup Settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Destination Folder', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="iddropboxdir" name="dropboxdir" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dropboxdir', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File Deletion', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<?php if ( $information['information']['backuptype'] == 'archive' ) : ?>
							<input id="iddropboxmaxbackups"
								name="dropboxmaxbackups"
								type="text" size="3"
								value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dropboxmaxbackups', '' ) ); ?>" />
						<?php else : ?>
							<input class="checkbox" value="1"
										 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'dropboxsyncnodelete', '' ), true ); ?>
										 name="dropboxsyncnodelete"
										 id="iddropboxsyncnodelete"/> <?php _e( 'Do not delete files while syncing to destination!', $this->plugin_translate ); ?>
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
