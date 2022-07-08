<?php

class MainWPBackWPupJobFile extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		if ( ! isset( $_POST['fileexclude'] ) ) {
			$_POST['fileexclude'] = "";
		}

		$fileexclude = explode( ',', stripslashes( str_replace( array(
			"\r\n",
			"\r"
		), ',', $_POST['fileexclude'] ) ) );

		foreach ( $fileexclude as $key => $value ) {
			$fileexclude[ $key ] = str_replace( '//', '/', str_replace( '\\', '/', trim( $value ) ) );
			if ( empty( $fileexclude[ $key ] ) ) {
				unset( $fileexclude[ $key ] );
			}
		}
		sort( $fileexclude );
		$settings['fileexclude'] = implode( ',', $fileexclude );

		if ( ! isset( $_POST['dirinclude'] ) ) {
			$_POST['dirinclude'] = "";
		}

		$dirinclude = explode( ',', stripslashes( str_replace( array(
			"\r\n",
			"\r"
		), ',', $_POST['dirinclude'] ) ) );

		$dirinclude_new = $dirinclude;

		foreach ( $dirinclude as $key => $value ) {
			$dirinclude_new[ $key ] = stripslashes( trim( $value ) );
			if ( $dirinclude_new[ $key ] == '/' || empty( $dirinclude_new[ $key ] ) ) {
				unset( $dirinclude_new[ $key ] );
			}
		}
		sort( $dirinclude_new );
		$settings['dirinclude'] = implode( ',', $dirinclude_new );

		$settings['backupexcludethumbs'] = ( ( isset( $_POST['backupexcludethumbs'] ) && $_POST['backupexcludethumbs'] == 1 ) ? true : false );
		$settings['backupspecialfiles']  = ( ( isset( $_POST['backupspecialfiles'] ) && $_POST['backupspecialfiles'] == 1 ) ? true : false );
		$settings['backuproot']          = ( ( isset( $_POST['backuproot'] ) && $_POST['backuproot'] == 1 ) ? true : false );

		if ( ! isset( $_POST['backuprootexcludedirs'] ) || ! is_array( $_POST['backuprootexcludedirs'] ) ) {
			$_POST['backuprootexcludedirs'] = array();
		}
		sort( $_POST['backuprootexcludedirs'] );
		$settings['backuprootexcludedirs'] = $_POST['backuprootexcludedirs'];

		$settings['backupcontent'] = ( ( isset( $_POST['backupcontent'] ) && $_POST['backupcontent'] == 1 ) ? true : false );

		if ( ! isset( $_POST['backupcontentexcludedirs'] ) || ! is_array( $_POST['backupcontentexcludedirs'] ) ) {
			$_POST['backupcontentexcludedirs'] = array();
		}
		sort( $_POST['backupcontentexcludedirs'] );
		$settings['backupcontentexcludedirs'] = $_POST['backupcontentexcludedirs'];

		$settings['backupplugins'] = ( ( isset( $_POST['backupplugins'] ) && $_POST['backupplugins'] == 1 ) ? true : false );

		if ( ! isset( $_POST['backuppluginsexcludedirs'] ) || ! is_array( $_POST['backuppluginsexcludedirs'] ) ) {
			$_POST['backuppluginsexcludedirs'] = array();
		}
		sort( $_POST['backuppluginsexcludedirs'] );
		$settings['backuppluginsexcludedirs'] = $_POST['backuppluginsexcludedirs'];

		$settings['backupthemes'] = ( ( isset( $_POST['backupthemes'] ) && $_POST['backupthemes'] == 1 ) ? true : false );

		if ( ! isset( $_POST['backupthemesexcludedirs'] ) || ! is_array( $_POST['backupthemesexcludedirs'] ) ) {
			$_POST['backupthemesexcludedirs'] = array();
		}
		sort( $_POST['backupthemesexcludedirs'] );
		$settings['backupthemesexcludedirs'] = $_POST['backupthemesexcludedirs'];

		$settings['backupuploads'] = ( ( isset( $_POST['backupuploads'] ) && $_POST['backupuploads'] == 1 ) ? true : false );

		if ( ! isset( $_POST['backupuploadsexcludedirs'] ) || ! is_array( $_POST['backupuploadsexcludedirs'] ) ) {
			$_POST['backupuploadsexcludedirs'] = array();
		}
		sort( $_POST['backupuploadsexcludedirs'] );
		$settings['backupuploadsexcludedirs'] = $_POST['backupuploadsexcludedirs'];

		return $settings;
	}


	public function render_form( $information ) {
		$default = $information['default'];

		$folders = array(
			'abs'     => array(
				'name'           => 'Backup root folder',
				'form_root_name' => 'backuproot',
				'form_checkbox'  => 'backuprootexcludedirs[]',
				'checked'        => MainWPBackWPUpView::get_value( $default, 'backuproot', 1 ),
				'exclude'        => MainWPBackWPUpView::get_value( $default, 'backuprootexcludedirs', array() )
			),
			'content' => array( 'name'           => 'Backup content folder',
			                    'form_root_name' => 'backupcontent',
			                    'form_checkbox'  => 'backupcontentexcludedirs[]',
			                    'checked'        => MainWPBackWPUpView::get_value( $default, 'backupcontent', 1 ),
			                    'exclude'        => MainWPBackWPUpView::get_value( $default, 'backupcontentexcludedirs', array() )
			),
			'plugin'  => array( 'name'           => 'Backup plugins',
			                    'form_root_name' => 'backupplugins',
			                    'form_checkbox'  => 'backuppluginsexcludedirs[]',
			                    'checked'        => MainWPBackWPUpView::get_value( $default, 'backupplugins', 1 ),
			                    'exclude'        => MainWPBackWPUpView::get_value( $default, 'backuppluginsexcludedirs', array() )
			),
			'theme'   => array( 'name'           => 'Backup themes',
			                    'form_root_name' => 'backupthemes',
			                    'form_checkbox'  => 'backupthemesexcludedirs[]',
			                    'checked'        => MainWPBackWPUpView::get_value( $default, 'backupthemes', 1 ),
			                    'exclude'        => MainWPBackWPUpView::get_value( $default, 'backupthemesexcludedirs', array() )
			),
			'upload'  => array( 'name'           => 'Backup uploads folder',
			                    'form_root_name' => 'backupuploads',
			                    'form_checkbox'  => 'backupuploadsexcludedirs[]',
			                    'checked'        => MainWPBackWPUpView::get_value( $default, 'backupuploads', 1 ),
			                    'exclude'        => MainWPBackWPUpView::get_value( $default, 'backupuploadsexcludedirs', array() )
			)
		);
		MainWPBackWPUpView::add_script( 'mainwpbackwpup_job_file_folders', wp_json_encode( $folders ) );
		?>

		<div ng-show="is_selected_2('<?php echo $this->tab_name; ?>')">
			<div class="ui form segment">
			<form action="<?php echo esc_attr( $this->current_page ); ?>" method="post">
				<input type="hidden" name="our_id" value="<?php echo esc_attr( $this->our_id ); ?>">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>">
				<input type="hidden" name="website_id" value="<?php echo esc_attr( $this->website_id ); ?>">
				<input type="hidden" name="job_tab" value="<?php echo esc_attr( $this->original_tab_name ); ?>">
				<?php wp_nonce_field( MainWPBackWPupExtension::$nonce_token . 'update_jobs' ); ?>

				<h3 class="ui dividing header"><?php _e( 'Files to Backup', $this->plugin_translate ) ?></h3>
				<?php if ( $this->website_id == 0 ): ?>
				<div class="ui grid field">
					<label class="six wide column middle aligned">Backup root folder</label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox"
									 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'backuproot', true ), true, true );?>
									 name="backuproot" id="idbackuproot" value="1"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned">Backup content folder</label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox"
									 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'backupcontent', true ), true, true );?>
									 name="backupcontent" id="idbackupcontent" value="1"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned">Backup plugins</label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox"
									 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'backupplugins', true ), true, true );?>
									 name="backupplugins" id="idbackupplugins" value="1"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned">Backup themes</label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox"
									 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'backupthemes', true ), true, true );?>
									 name="backupthemes" id="idbackupthemes" value="1"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned">Backup uploads folder</label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox"
									 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'backupuploads', true ), true, true );?>
									 name="backupuploads" id="idbackupuploads" value="1"/>
					</div>
				</div>

				<?php else: ?>

				<div><span ng-hide="scope_job_files" class="mainwp_info-box">Loading ...</span></div>
				<table ng-show="scope_job_files" class="form-table">
					<tr ng-repeat="(job_key, level_1) in scope_job_files">
						<th scope="row"><label>{{ scope_job_file_folders[job_key].name }}</label></th>
						<td>
							<input class="checkbox" type="checkbox"
										 name="{{ scope_job_file_folders[job_key].form_root_name }}" value="1"
										 ng-checked="scope_job_file_folders[job_key].checked==1"><code>{{ level_1.name
								}}</code> {{ level_1.size }}
							<fieldset style="padding-left:15px; margin:2px;">
								<legend><strong><?php _e( 'Exclude:', $this->plugin_translate ); ?></strong>
								</legend>
								<span ng-repeat="level_2 in level_1.folders">
									<nobr>
										<label>
											<input class="checkbox" type="checkbox"
														 name="{{ scope_job_file_folders[job_key].form_checkbox }}"
														 value="{{ level_2.name }}"
														 ng-checked="scope_job_file_folders[job_key].exclude.indexOf(level_2.name) > -1">{{
											level_2.name }} {{ level_2.size }}
										</label>
										<br/>
									</nobr>
								</span>
							</fieldset>
						</td>
					</tr>
				</table>

				<?php endif; ?>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Extra folders to backup', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<textarea name="dirinclude" id="dirinclude" class="text code help-tip" rows="7" cols="50"><?php echo esc_textarea( MainWPBackWPUpView::get_value( $default, 'dirinclude', '' ) ); ?></textarea>

					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Files to Exclude', $this->plugin_translate ) ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Thumbnails in uploads', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox"
							type="checkbox"<?php checked( MainWPBackWPUpView::get_value( $default, 'backupexcludethumbs', false ), true, true ); ?>
							name="backupexcludethumbs"
							id="idbackupexcludethumbs"
							value="1"/> <?php _e( 'Don\'t backup thumbnails from the site\'s uploads folder.', $this->plugin_translate ); ?>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Exclude files/folders from backup', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<textarea name="fileexclude" id="idfileexclude" class="text code help-tip" rows="7"
											cols="50"
											><?php echo esc_textarea( MainWPBackWPUpView::get_value( $default, 'fileexclude', '.DS_Store,.git,.svn,.tmp,/node_modules/,desktop.ini' ) ); ?></textarea>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Special option', $this->plugin_translate ) ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Include special files', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox" id="idbackupspecialfiles"
																										 type="checkbox"<?php checked( MainWPBackWPUpView::get_value( $default, 'backupspecialfiles', false ), true, true ); ?>
																										 name="backupspecialfiles"
																										 value="1"/>
					</div>
				</div>
				
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Use one folder above as WP install folder', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox" id="backupabsfolderup"
							type="checkbox"<?php checked( MainWPBackWPUpView::get_value( $default, 'backupabsfolderup', false ), true, true ); ?>
							name="backupabsfolderup"
							value="1"/>
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
