<?php

class MainWPBackWPupDestinationFtp extends MainWPBackWPupJob {
	public function save_form( $settings ) {
		$settings['ftphost'] = ( isset( $_POST['ftphost'] ) ? str_replace( array(
			'http://',
			'ftp://'
		), '', $_POST['ftphost'] ) : '' );

		$settings['ftphostport'] = ( ! empty( $_POST['ftphostport'] ) ? (int) $_POST['ftphostport'] : 21 );
		$settings['ftptimeout']  = ( ! empty( $_POST['ftptimeout'] ) ? (int) $_POST['ftptimeout'] : 90 );
		$settings['ftpuser']     = ( isset( $_POST['ftpuser'] ) ? $_POST['ftpuser'] : '' );
		$settings['ftppass']     = ( isset( $_POST['ftppass'] ) ? $_POST['ftppass'] : '' );

		if ( ! empty( $_POST['ftpdir'] ) ) {
			$settings['ftpdir'] = trim( stripslashes( $_POST['ftpdir'] ) );
		}

		$settings['ftpmaxbackups'] = ( isset( $_POST['ftpmaxbackups'] ) ? (int) $_POST['ftpmaxbackups'] : 0 );

		$settings['ftpssl'] = ( ( isset( $_POST['ftpssl'] ) && $_POST['ftpssl'] == 1 ) ? true : false );

		$settings['ftppasv'] = ( ( isset( $_POST['ftppasv'] ) && $_POST['ftppasv'] == 1 ) ? true : false );

		$settings['ftpsyncnodelete'] = ( ( isset( $_POST['ftpsyncnodelete'] ) && $_POST['ftpsyncnodelete'] == 1 ) ? true : false );

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
				<h3 class="ui dividing header"><?php _e( 'FTP server and login', $this->plugin_translate ) ?></h3>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'FTP server', $this->plugin_translate ); ?></label>
				  <div class="eight wide column">
						<input id="idftphost" name="ftphost" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'ftphost', '' ) ); ?>" autocomplete="off"/>
					</div>
					<div class="one wide column middle aligned">
						Port
					</div>
					<div class="one wide column">
						<input name="ftphostport" id="idftphostport" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'ftphostport', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Username', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idftpuser" name="ftpuser" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'ftpuser', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Password', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idftppass" name="ftppass" type="password" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'ftppass', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<h3 class="ui diving header"><?php _e( 'Backup settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Folder to store files in', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idftpdir" name="ftpdir" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'ftpdir', '' ) ); ?>" />
						<div class="ui mini message">
							<?php _e( 'Allowed tags:  %sitename%, %url%, %date%, %time%', $this->plugin_translate ); ?>
						</div>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File Deletion', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idftpmaxbackups" name="ftpmaxbackups" type="text" size="3" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'ftpmaxbackups', '' ) ); ?>" />
						<div class="ui mini message">
							<?php _e( 'Maximum number of files to keep in folder.', $this->plugin_translate ); ?>
						</div>
					</div>
				</div>

				<h3 class="ui diving header"><?php _e( 'FTP specific settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Timeout for FTP connection', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idftptimeout" name="ftptimeout" type="text" size="3" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'ftptimeout', '' ) ); ?>" /> <?php _e( 'seconds', $this->plugin_translate ); ?>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'SSL-FTP connection', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox" value="1"
																				 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'ftpssl', '' ), true ); ?>
																				 id="idftpssl"
																				 name="ftpssl"/> <label><?php _e( 'Use explicit SSL-FTP connection.', $this->plugin_translate ); ?></label>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'FTP Passive Mode', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox" value="1"
																					type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'ftppasv', '' ), true ); ?>
																					name="ftppasv"
																					id="idftppasv"/> <label><?php _e( 'Use FTP Passive Mode.', $this->plugin_translate ); ?></label>
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
