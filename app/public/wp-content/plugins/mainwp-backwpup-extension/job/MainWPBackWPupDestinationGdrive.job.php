<?php

class MainWPBackWPupDestinationGdrive extends MainWPBackWPupJob {
	public $is_pro_extension = true;

	public function save_form( $settings ) {
		$settings['gdrivesyncnodelete'] = ( ( isset( $_POST['gdrivesyncnodelete'] ) && $_POST['gdrivesyncnodelete'] == 1 ) ? true : false );
		$settings['gdriveusetrash']     = ( ( isset( $_POST['gdriveusetrash'] ) && $_POST['gdriveusetrash'] == 1 ) ? true : false );
		$settings['gdrivemaxbackups']   = ( isset( $_POST['gdrivemaxbackups'] ) ? (int) $_POST['gdrivemaxbackups'] : 0 );
		$settings['gdriverefreshtoken'] = ( isset( $_POST['gdriverefreshtoken'] ) ? $_POST['gdriverefreshtoken'] : '' );

		if ( isset( $_POST['gdrivedir'] ) ) {
			$settings['gdrivedir'] = trim( stripslashes( $_POST['gdrivedir'] ) );
		} else {
			$settings['gdrivedir'] = '';
		}

		return $settings;
	}

	public function render_form( $information ) {

		update_option( 'mainwp_gdr_job_' . get_current_user_id(), $this->our_id );
		update_option( 'mainwp_gdr_website_' . get_current_user_id(), $this->website_id );

		$default = $information['default'];

		?>
		<div ng-show="is_selected_2('<?php echo $this->tab_name; ?>')">
			<form action="<?php echo esc_attr( $this->current_page ); ?>" method="post">
				<input type="hidden" name="our_id" value="<?php echo esc_attr( $this->our_id ); ?>">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>">
				<input type="hidden" name="website_id" value="<?php echo esc_attr( $this->website_id ); ?>">
				<input type="hidden" name="job_tab" value="<?php echo esc_attr( $this->original_tab_name ); ?>">
				<?php wp_nonce_field( MainWPBackWPupExtension::$nonce_token . 'update_jobs' ); ?>
				<div class="postbox">
					<?php

					if ( $this->website_id == 0 ) {
						?>
						<br/><span class="mainwp_info-box">Remember to set valid list of Authorized redirect URIs inside https://console.developers.google.com.</span>
						<?php
					}
					?>
					<h3 class="title"><?php _e( 'Login', $this->plugin_translate ); ?></h3>

					<p></p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php _e( 'Authenticate', $this->plugin_translate ); ?></th>
							<td>
								<?php
								if ( strlen( MainWPBackWPUpView::get_value( MainWPBackWPUpView::$information['settings'], 'googleclientid', '' ) ) < 2 || strlen( MainWPBackWPUpView::get_value( MainWPBackWPUpView::$information['settings'], 'googleclientsecret', '' ) ) < 2 ) :
									?>
									First, set api keys inside settings tab.
									<?php
								else :
									if ( empty( $default['gdriverefreshtoken'] ) ) :
										?>
										<span
											style="color:red;"><?php _e( 'Not authenticated!', $this->plugin_translate ); ?></span>
										<br/>
										<?php
									else :
										?>
										<span
											style="color:green;"><?php _e( 'Authenticated!', $this->plugin_translate ); ?></span>
										<br/>
										<?php
									endif;
									?>
									<a class="button secondary"
									   href="<?php echo admin_url( 'admin-ajax.php' ); ?>?action=mainwp_backwpup_dest_gdrive"><?php _e( 'Reauthenticate', $this->plugin_translate ); ?></a>
									Set http://your-website/wp-admin/admin-ajax.php?action=mainwp_backwpup_dest_gdrive inside Redirect URIs
									<?php
								endif;
								?>
							</td>
						</tr>
					</table>


					<h3 class="title"><?php _e( 'Backup settings', $this->plugin_translate ); ?></h3>

					<p></p>
					<table class="form-table">
						<tr>
							<th scope="row"><label
									for="gdriverefreshtoken"><?php _e( 'Google Drive Refresh Token', $this->plugin_translate ); ?></label>
							</th>
							<td>
								<input name="gdriverefreshtoken" type="text"
									   value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'gdriverefreshtoken', '' ) ); ?>"
									   class="regular-text"/>
							</td>
						</tr>

						<tr>
							<th scope="row"><label
									for="idgdrivedir"><?php _e( 'Folder in Google Drive', $this->plugin_translate ); ?></label>
							</th>
							<td>
								<input id="idgdrivedir" name="gdrivedir" type="text"
									   value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'gdrivedir', '' ) ); ?>"
									   class="regular-text"/>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php _e( 'File Deletion', $this->plugin_translate ); ?></th>
							<?php
							if ( $information['information']['backuptype'] == 'archive' ) :
								?>
								<td>
									<label for="idgdrivemaxbackups"><input id="idgdrivemaxbackups" name="gdrivemaxbackups"
																		   type="text" size="3"
																		   value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'gdrivemaxbackups', '' ) ); ?>"
																		   class="small-text help-tip"
																		   title="<?php esc_attr_e( 'Oldest files will be deleted first. 0 = no deletion', $this->plugin_translate ); ?>"/>&nbsp;
									<?php _e( 'Number of files to keep in folder.', $this->plugin_translate ); ?>
									</label>
								</td>
								<?php
							else :
								?>
								<td>
									<label for="idgdrivesyncnodelete"><input class="checkbox" value="1"
																			 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'gdrivesyncnodelete', false ), true ); ?>
																			 name="gdrivesyncnodelete"
																			 id="idgdrivesyncnodelete"/> <?php _e( 'Do not delete files while syncing to destination!', $this->plugin_translate ); ?>
									</label>
								</td>
								<?php
							endif;
							?>
						</tr>
						<tr>
							<td></td>
							<td>
								<label for="idgdriveusetrash">
									<input class="checkbox help-tip" value="1"
										   type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'gdriveusetrash', false ), true ); ?>
										   name="gdriveusetrash"
										   id="idggdriveusetrash"/> <?php _e( 'Consider using trash to delete files. If trash is not enabled, files will be deleted permanently.', $this->plugin_translate ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button-primary"
						   value="<?php _e( 'Save Changes', $this->plugin_translate ); ?>"/>
				</p>
			</form>
		</div>
		<?php
	}
}
