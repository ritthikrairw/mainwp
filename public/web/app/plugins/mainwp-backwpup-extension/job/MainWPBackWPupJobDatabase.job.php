<?php

class MainWPBackWPupJobDatabase extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		global $mainWPBackWPupExtensionActivator;

		if ( isset( $_POST['dbdumpfilecompression'] ) && ( $_POST['dbdumpfilecompression'] == '' || $_POST['dbdumpfilecompression'] == '.gz' ) ) {
			$settings['dbdumpfilecompression'] = $_POST['dbdumpfilecompression'];
		}

		$settings['dbdumpfile'] = ( isset( $_POST['dbdumpfile'] ) ? $this->sanitize_file_name( $_POST['dbdumpfile'] ) : '' );
		$settings['tabledb']    = ( isset( $_POST['tabledb'] ) ? $_POST['tabledb'] : array() );

		$settings['dbdumpwpdbsettings'] = ( ( isset( $_POST['dbdumpwpdbsettings'] ) && $_POST['dbdumpwpdbsettings'] == 1 ) ? true : false );
		$settings['dbdumptype']         = ( ( ! isset( $_POST['dbdumptype'] ) ) ? 'sql' : trim( $_POST['dbdumptype'] ) );
		$settings['dbdumpdbhost']       = ( isset( $_POST['dbdumpdbhost'] ) ? $_POST['dbdumpdbhost'] : '' );
		$settings['dbdumpdbuser']       = ( isset( $_POST['dbdumpdbuser'] ) ? $_POST['dbdumpdbuser'] : '' );
		$settings['dbdumpdbpassword']   = ( isset( $_POST['dbdumpdbpassword'] ) ? $_POST['dbdumpdbpassword'] : '' );
		$settings['dbdumpdbname']       = ( ( ! isset( $_POST['dbdumpdbname'] ) ) ? '' : trim( $_POST['dbdumpdbname'] ) );
		$settings['dbdumpdbcharset']    = ( isset( $_POST['dbdumpdbcharset'] ) ? $_POST['dbdumpdbcharset'] : '' );
		$settings['dbdumpmysqlfolder']  = ( isset( $_POST['dbdumpmysqlfolder'] ) ? str_replace( '\\', '/', $_POST['dbdumpmysqlfolder'] ) : '' );

		if ( isset( $_POST['dbdumpspecialsetalltables'] ) ) {
			$settings['dbdumpspecialsetalltables'] = 1;
		} else {
			$settings['dbdumpspecialsetalltables'] = 0;
		}

		return $settings;
	}

	public function render_form( $information ) {
		global $wpdb;
		$default = $information['default'];
		MainWPBackWPUpView::add_script( 'backwpup_dbdumpexclude', wp_json_encode( MainWPBackWPUpView::get_value( $default, 'tabledb', array() ) ) );
		?>

		<div ng-show="is_selected_2('<?php echo $this->tab_name; ?>')">
			<div class="ui form segment">
			<form action="<?php echo esc_attr( $this->current_page ); ?>" method="post">
				<input type="hidden" name="our_id" value="<?php echo esc_attr( $this->our_id ); ?>">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>">
				<input type="hidden" name="website_id" value="<?php echo esc_attr( $this->website_id ); ?>">
				<input type="hidden" name="job_tab" value="<?php echo esc_attr( $this->original_tab_name ); ?>">
				<?php wp_nonce_field( MainWPBackWPupExtension::$nonce_token . 'update_jobs' ); ?>

				<h3 class="ui dividing header"><?php _e( 'Settings for database backup', $this->plugin_translate ); ?></h3>

				<?php if ( MainWPBackWPUpView::$information['display_pro_settings'] && $this->website_id > 0 ) : ?>
					<div ng-show="get_child_tables_loading">
						<div class="ui active inverted dimmer">
						<div class="ui text loader">Loading</div>
					  </div>
					</div>
					<div ng-show="scope_child_message" class="ui red message">{{ scope_child_message }}</div>
				<?php endif; ?>

				<?php if ( MainWPBackWPUpView::$information['display_pro_settings'] && $this->website_id > 0 ) : ?>
					<div class="ui grid field">
						<label class="six wide column"><?php _e( 'Database connection', $this->plugin_translate ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input ng-model="mainwp_job_database_settings"
								 class="checkbox"
								 type="checkbox"
								 ng-init="mainwp_job_database_settings='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpwpdbsettings', '' ) ); ?>'"
								 ng-checked="'<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpwpdbsettings', '' ) ); ?>'=='1'"
								 id="dbdumpwpdbsettings"
								 name="dbdumpwpdbsettings"
								 value="1"/> <label for="dbdumpwpdbsettings"><?php _e( 'Use WordPress database connection.', $this->plugin_translate ); ?></label>
						</div>
					</div>

					<div class="ui grid field" ng-show="mainwp_job_database_settings!='1'">
						<label class="six wide column"></label>
					  <div class="ten wide column">
							<table id="dbconnection" >
								<tr>
									<td>
										<label
											for="dbdumpdbhost"><?php _e( 'Host:', $this->plugin_translate ); ?>
											<br/>
											<input class="text" type="text" id="dbdumpdbhost"
														 name="dbdumpdbhost" autocomplete="off"
														 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpdbhost', '' ) ); ?>"/></label><br/>
										<label
											for="dbdumpdbuser"><?php _e( 'User:', $this->plugin_translate ); ?>
											<br/>
											<input class="text" type="text" id="dbdumpdbuser"
														 name="dbdumpdbuser" autocomplete="off"
														 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpdbuser', '' ) ); ?>"/></label><br/>
										<label
											for="dbdumpdbpassword"><?php _e( 'Password:', $this->plugin_translate ); ?>
											<br/>
											<input class="text" type="password" id="dbdumpdbpassword"
														 name="dbdumpdbpassword" autocomplete="off"
														 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpdbpassword', '' ) ); ?>"/></label>
									</td>
									<td>
										<label
											for="dbdumpdbcharset"><?php _e( 'Charset:', $this->plugin_translate ); ?></label><br/>
										<select id="dbdumpdbcharset" name="dbdumpdbcharset">
											<?php
											$colations = $wpdb->get_results( 'SHOW CHARACTER SET', ARRAY_A );
											foreach ( $colations as $colation ) {
												echo '<option value="' . esc_attr( $colation['Charset'] ) . '" ' . selected( MainWPBackWPUpView::get_value( $default, 'dbdumpdbcharset', '' ), $colation['Charset'] ) . ' title="' . esc_attr( $colation['Description'] ) . '">' . esc_html( $colation['Charset'] ) . '</option>';
											}
											?>
										</select>
										<br/>
										<?php _e( 'Database:', $this->plugin_translate ); ?><br/>
										<input id="dbdumpdbname" type="hidden" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpdbname', '' ) ); ?>">
										<select
											ng-init="job_database_dbdumpdbname='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpdbname', '' ) ); ?>'"
											ng-model="job_database_dbdumpdbname"
											ng-change="get_child_tables()" id="dbdumpdbname"
											name="dbdumpdbname">
											<option ng-selected="job_database_dbdumpdbname==d"
															value="{{ d }}" ng-repeat="d in scope_child_databases">
												{{ d }}
											</option>
										</select>
									</td>
								</tr>
							</table>
						</div>
					</div>
					<?php endif; ?>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Tables to backup', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<?php if ( $this->website_id > 0 ) : ?>
								<span ng-hide="scope_child_tables">
									Loading ...
								</span>
								<div ng-repeat="d in scope_child_tables">
									<label><input class="checkbox" type="checkbox" name="tabledb[]"
																value="{{ d }}"
																ng-checked="scope_backwpup_dbdumpexclude.indexOf(d) == -1">{{ d
										}}</label>
								</div>
							<?php else : ?>
								<input type="hidden" name="dbdumpwpdbsettings" value="1"/>
								<input type="hidden" name="dbdumpspecialsetalltables" value="1"/>
								ALL
							<?php endif; ?>
						</div>
					</div>

					<?php if ( MainWPBackWPUpView::$information['display_pro_settings'] ) : ?>

						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Database Backup type', $this->plugin_translate ); ?></label>
						  <div class="ten wide column ui list">
								<?php
								echo '<div class="item ui radio checkbox"><input ng-model="mainwp_job_database_backup_type" ng-init="mainwp_job_database_backup_type=\'' . esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumptype', '' ) ) . '\'" class="radio" type="radio" name="dbdumptype" id="iddbdumptype-sql" value="sql" /> <label for="iddbdumptype-sql">' . __( 'SQL File (with mysqli)', $this->plugin_translate ) . '</label></div>';
								echo '<div class="item ui radio checkbox"><input ng-model="mainwp_job_database_backup_type" class="radio" type="radio" name="dbdumptype" id="iddbdumptype-syssql" value="syssql" /> <label for="iddbdumptype-syssql">' . __( 'SQL File (with mysqldump)', $this->plugin_translate ) . '</label></div>';
								echo '<div class="item ui radio checkbox"><input ng-model="mainwp_job_database_backup_type" class="radio" type="radio"name="dbdumptype" id="iddbdumptype-xml" value="xml" /> <label for="iddbdumptype-xml">' . __( 'XML File (phpMyAdmin schema)', $this->plugin_translate ) . '</label></div>';
								?>
							</div>
						</div>

						<div class="ui grid field" id="trdbdumpmysqlfolder" ng-show="mainwp_job_database_backup_type=='syssql'">
							<label class="six wide column middle aligned"><?php _e( 'Path to <em>mysqldump</em> file', $this->plugin_translate ); ?></label>
						  <div class="ten wide column">
								<input name="dbdumpmysqlfolder" id="dbdumpmysqlfolder" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpmysqlfolder', '' ) ); ?>" />
							</div>
						</div>
					<?php endif; ?>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Backup file name', $this->plugin_translate ); ?></label>
						<div class="ten wide column">
							<div class="ui right labeled input">

							<input id="iddbdumpfile" name="dbdumpfile" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'dbdumpfile', '' ) ); ?>" /><div class="ui label">.sql</div></div>
						</div>
					</div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php _e( 'Backup file compression', $this->plugin_translate ); ?></label>
						<div class="ten wide column ui list">
							<?php
							echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '', MainWPBackWPUpView::get_value( $default, 'dbdumpfilecompression', '' ), false ) . ' name="dbdumpfilecompression"  id="iddbdumpfilecompression" value="" /> <label for="iddbdumpfilecompression">' . __( 'none', $this->plugin_translate ) . '</label></div>';
							echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '.gz', MainWPBackWPUpView::get_value( $default, 'dbdumpfilecompression', '' ), false ) . ' name="dbdumpfilecompression" id="iddbdumpfilecompression-gz" value=".gz" /> <label for="iddbdumpfilecompression-gz">' . __( 'GZip', $this->plugin_translate ) . '</label></div>';
							?>
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
