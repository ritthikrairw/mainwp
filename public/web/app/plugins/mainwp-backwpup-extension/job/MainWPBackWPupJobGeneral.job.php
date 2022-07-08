<?php

class MainWPBackWPupJobGeneral extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		global $mainWPBackWPupExtensionActivator;

		$settings['type'] = ( isset( $_POST['type'] ) ? $_POST['type'] : array() );

		if ( ! empty( $settings['type'] ) ) {
			foreach ( $settings['type'] as $t ) {
				if ( ! isset( MainWPBackWPupExtension::$jobs_and_destinations[ 'jobtype-' . $t ] ) ) {
					wp_die( __( 'Missing type', $this->plugin_translate ) . ' ' . esc_html( $t ) );
				}
			}
		}

		$settings['destinations'] = ( isset( $_POST['destinations'] ) ? $_POST['destinations'] : array() );

		if ( ! empty( $settings['destinations'] ) ) {
			foreach ( $settings['destinations'] as $t ) {
				if ( ! isset( MainWPBackWPupExtension::$jobs_and_destinations[ 'dest-' . $t ] ) ) {
					wp_die( __( 'Missing destination', $this->plugin_translate ) . ' ' . esc_html( $t ) );
				}
			}
		}

		$name = ( ! empty( $_POST['name'] ) ? esc_html( trim( $_POST['name'] ) ) : sprintf( __( 'Job with ID %d', $this->plugin_translate ), uniqid() ) );

		$settings['name'] = $name;

		$settings['mailaddresslog'] = ( isset( $_POST['mailaddresslog'] ) ? sanitize_email( $_POST['mailaddresslog'] ) : '' );

		$settings['mailaddresssenderlog'] = ( ! empty( $_POST['mailaddresssenderlog'] ) ? trim( $_POST['mailaddresssenderlog'] ) : '' );

		$settings['mailerroronly'] = ( isset( $_POST['mailerroronly'] ) ? $_POST['mailerroronly'] : '' );

		$settings['backuptype'] = ( isset( $_POST['backuptype'] ) && $_POST['backuptype'] == 'sync' ? 'sync' : 'archive' );

		$settings['archiveformat'] = ( isset( $_POST['archiveformat'] ) ? esc_html( $_POST['archiveformat'] ) : '' );

		$settings['archivename'] = ( isset( $_POST['archivename'] ) ? $this->sanitize_file_name( $_POST['archivename'] ) : '' );

		return $settings;
	}

	public function render_form( $information ) {
		$default = $information['default'];
		if ( $this->our_id > 0 ) :
			?>
			<div ng-show="is_selected_2('<?php echo $this->tab_name; ?>')">
			<?php
		endif;
		?>
		<form action="<?php echo esc_attr( $this->current_page ); ?>" method="post">
			<div class="ui segment">
				<input type="hidden" name="our_id" value="<?php echo esc_attr( $this->our_id ); ?>">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>">
				<input type="hidden" name="website_id" value="<?php echo esc_attr( $this->website_id ); ?>">
				<input type="hidden" name="job_tab" value="<?php echo esc_attr( $this->original_tab_name ); ?>">

				<?php wp_nonce_field( MainWPBackWPupExtension::$nonce_token . 'update_jobs' ); ?>



				<div class='ui form'>
				<div id="<?php echo ( $this->website_id == 0 ) ? 'bwpup-editjob-left' : ''; ?>">

				<div class="ui segment" id="bwpup-jobname-box">
					<h3 class="ui dividing header"><?php _e( 'Job Name', $this->plugin_translate ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column"><?php _e( 'Please name this job', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<input name="name" type="text" id="name" 
										 data-empty="<?php _e( 'New Job', $this->plugin_translate ); ?>"
										 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'name', '' ) ); ?>"
										 class="regular-text"/>
										 <div class="ui message">
											 <?php _e( 'Allowed tags: %sitename%, %url%, %date%, %time%', $this->plugin_translate ); ?>
										 </div>
						</div>
					</div>
				</div>

				<div class="ui segment">
					<h3 class="ui dividing header"><?php _e( 'Job Tasks', $this->plugin_translate ); ?></h3>

					<div class="ui grid field">
						<label class="six wide column"><?php _e( 'This job is a …', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<div class="ui list">
								<?php
								foreach ( MainWPBackWPupExtension::$jobs_and_destinations as $key => $val ) {
									if ( $val['type'] != 'job' ) {
										continue;
									}
									echo '<div class="item ui checkbox">';
									$temp_value = str_replace( array( 'jobtype-', 'dest-' ), '', $key );

									if ( $this->our_id == 0 ) {
										echo '<input class="jobtype-select checkbox filetype" type="checkbox" name="type[]" value="' . esc_attr( $temp_value ) . '"/><label>' . esc_html( $val['name'] ) . '</label>';
									} else {

										echo '<input ng-model="current_job.' . $temp_value . '" ng-true-value="\'' . $temp_value . '\'" class="jobtype-select checkbox filetype" type="checkbox" name="type[]" value="' . $temp_value . '"';
										if ( array_search( $temp_value, MainWPBackWPUpView::get_value( $default, 'type', array() ) ) !== false ) {
											echo ' ng-checked="1" ng-init="current_job.' . esc_attr( $temp_value ) . '=\'' . esc_attr( $temp_value ) . '\'" ';
										}
										echo '/><label>' . $val['name'] . '</label>';
									}
									echo '</div>';
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="ui segment">
					<h3 class="ui dividing header"><?php _e( 'Backup File Creation', $this->plugin_translate ); ?></h3>

					<?php
					if ( MainWPBackWPUpView::$information['display_pro_settings'] ) :
						?>
					<div class="ui grid field nosync" ng-init="backuptype<?php echo (int) $this->our_id; ?>='<?php echo MainWPBackWPUpView::get_value( $default, 'backuptype', 'archive' ); ?>'">
						<label class="six wide column"><?php _e( 'Backup type', $this->plugin_translate ); ?></label>
						<div class="ten wide column ui list">
									<div class="item ui radio checkbox">  
										<input class="radio" ng-model="backuptype<?php echo (int) $this->our_id; ?>"
												type="radio"
												name="backuptype"
												value="sync"/> 
												<label for="idbackuptype-sync"><?php _e( 'Synchronize file by file to destination', $this->plugin_translate ); ?></label><br/>
										</div>								
									<div class="item ui radio checkbox"> 
										<input class="radio" ng-model="backuptype<?php echo (int) $this->our_id; ?>"
										type="radio"
										name="backuptype"
										value="archive"/><label for="idbackuptype-archive"> <?php _e( 'Create a backup archive', $this->plugin_translate ); ?></label>
									</div>		
						</div>
					</div>
						<?php
					else :
						?>
						<input ng-init="backuptype<?php echo (int) $this->our_id; ?>='archive'" ng-model="backuptype<?php echo (int) $this->our_id; ?>" type="hidden" name="backuptype">
						<?php
					endif;
					?>

					<div class="ui grid field nosync" ng-show="backuptype<?php echo (int) $this->our_id; ?>=='archive'">
						<label class="six wide column"><?php _e( 'Archive name', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<input name="archivename" type="text" id="archivename"
										 value="<?php esc_attr_e( MainWPBackWPUpView::get_value( $default, 'archivename', '' ) ); ?>"
										 class="regular-text code help-tip"/>

										 <div class="ui message">
										   <?php _e( 'Allowed tags: %sitename%, %url%, %date%, %time%', $this->plugin_translate ); ?>
											 <?php
												echo '<p>';
												echo '<strong>' . esc_attr__( 'Replacement patterns:', $this->plugin_translate ) . '</strong><br />';
												echo esc_attr__( '%d = Two digit day of the month, with leading zeros', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%j = Day of the month, without leading zeros', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%m = Day of the month, with leading zeros', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%n = Representation of the month (without leading zeros)', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%Y = Four digit representation for the year', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%y = Two digit representation of the year', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%a = Lowercase ante meridiem (am) and post meridiem (pm)', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%A = Uppercase ante meridiem (AM) and post meridiem (PM)', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%B = Swatch Internet Time', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%g = Hour in 12-hour format, without leading zeros', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%G = Hour in 24-hour format, without leading zeros', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%h = Hour in 12-hour format, with leading zeros', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%H = Hour in 24-hour format, with leading zeros', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%i = Two digit representation of the minute', $this->plugin_translate ) . '<br />';
												echo esc_attr__( '%s = Two digit representation of the second', $this->plugin_translate ) . '<br />';
												echo '</p>';
												?>
										 </div>
						</div>
					</div>

					<div class="ui grid field nosync" ng-show="backuptype<?php echo (int) $this->our_id; ?>=='archive'">
						<label class="six wide column"><?php _e( 'Archive format', $this->plugin_translate ); ?></label>
					  <div class="ten wide column ui list">
							<?php
							echo '<div class="item ui radio checkbox"><input class="radio help-tip" title="' . __( 'PHP Zip functions will be used if available (needs less memory). Otherwise the PCLZip class will be used.', $this->plugin_translate ) . '" type="radio"' . checked( '.zip', MainWPBackWPUpView::get_value( $default, 'archiveformat', '' ), false ) . ' name="archiveformat" id="idarchiveformat-zip" value=".zip" /> <label for="idarchiveformat-zip">' . __( 'Zip', $this->plugin_translate ) . '</label></div>';

							echo '<div class="item ui radio checkbox"><input class="radio help-tip" title="' . __( 'A tarballed, not compressed archive (fast and less memory)', $this->plugin_translate ) . '" type="radio"' . checked( '.tar', MainWPBackWPUpView::get_value( $default, 'archiveformat', '' ), false ) . ' name="archiveformat" id="idarchiveformat-tar" value=".tar" /> <label for="idarchiveformat-tar">' . __( 'Tar', $this->plugin_translate ) . '</label></div>';

							echo '<div class="item ui radio checkbox"><input class="radio help-tip" title="' . __( 'A tarballed, GZipped archive (fast and less memory)', $this->plugin_translate ) . '" type="radio"' . checked( '.tar.gz', MainWPBackWPUpView::get_value( $default, 'archiveformat', '' ), false ) . ' name="archiveformat" id="idarchiveformat-targz" value=".tar.gz" /> <label for="idarchiveformat-targz">' . __( 'Tar GZip', $this->plugin_translate ) . '</label></div>';

							?>
						</div>
					</div>

				</div>

				<div class="ui segment">
					<h3 class="ui dividing header"><?php _e( 'Job Destination', $this->plugin_translate ); ?></h3>

					<div class="ui grid field nosync">
						<label class="six wide column"><?php _e( 'Where should your backup file be stored?', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<div class="ui list">
							<?php
							foreach ( MainWPBackWPupExtension::$jobs_and_destinations as $key => $val ) {
								if ( $val['type'] != 'destination' ) {
									continue;
								}

								if ( isset( $val['is_pro'] ) && $val['is_pro'] && ! MainWPBackWPUpView::$information['display_pro_settings'] ) {
									continue;
								}

								$temp_value = str_replace(
									array(
										'jobtype-',
										'dest-',
									),
									'',
									$key
								);

								if ( $this->our_id == 0 ) {
									echo '<div class="item ui checkbox"><input class="checkbox" type="checkbox" name="destinations[]" value="' . esc_attr( $temp_value ) . '"/><label>' . esc_html( $val['name'] ) . '</label></div>';
								} else {
									echo '<div class="item ui checkbox"><input ng-model="current_job.' . esc_attr( $temp_value ) . '" ng-true-value="\'' . esc_attr( $temp_value ) . '\'" class="checkbox" type="checkbox" name="destinations[]" value="' . esc_attr( $temp_value ) . '"';
									if ( array_search( $temp_value, MainWPBackWPUpView::get_value( $default, 'destinations', array() ) ) !== false ) {
										echo ' ng-checked="1" ng-init="current_job.' . esc_attr( $temp_value ) . '=\'' . esc_attr( $temp_value ) . '\'" ';
									}
									echo '/><label>' . esc_html( $val['name'] ) . '</label></div>';
								}
							}
							?>
							</div>
						</div>
					</div>
				</div>

				<div class="ui segment">
					<h3 class="ui dividing header"><?php _e( 'Log files', $this->plugin_translate ); ?></h3>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Send log to email address', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<input name="mailaddresslog" type="text" id="mailaddresslog"
										 value="<?php esc_attr_e( MainWPBackWPUpView::get_value( $default, 'mailaddresslog', '' ) ); ?>"
										 class="regular-text help-tip"
										 title="<?php esc_attr_e( 'Leave empty to not have log sent.', $this->plugin_translate ); ?>"/>
						</div>
					</div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Email FROM field', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<input name="mailaddresssenderlog" type="text" id="mailaddresssenderlog"
										 value="<?php esc_attr_e( MainWPBackWPUpView::get_value( $default, 'mailaddresssenderlog', '' ) ); ?>"
										 class="regular-text help-tip"
										 title="<?php esc_attr_e( 'Email "From" field (Name &lt;&#160;you@your-email-address.tld&#160;&gt;)', $this->plugin_translate ); ?>"/>
						</div>
					</div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Errors only', $this->plugin_translate ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input class="checkbox" value="1" id="idmailerroronly"
										 type="checkbox" <?php checked( 1, MainWPBackWPUpView::get_value( $default, 'mailerroronly', 0 ) ); ?>
										 name="mailerroronly"/> <?php _e( 'Send email with log only when errors occur during job execution.', $this->plugin_translate ); ?>
						</div>
					</div>

				</div>

				</div>
				<div class="ui divider"></div>
				<input type="submit" name="submit" id="submit" class="ui big green right floated button" value="<?php _e( 'Save Changes', $this->plugin_translate ); ?>"/>
				<div class="ui hidden clearing divider"></div>
			</div>


			</div>
		</form>
		<?php
		if ( $this->our_id > 0 ) :
			?>
			</div>
			<?php
		endif;
	}
}
