<?php

class MainWPBulkSettingsManagerView {

	public static $plugin_translate = "bulk_settings_manager_extension";
	public static $render_selectbox_counter = 1;
	public static $id = 0;

	/**
	 * Display messages from controller
	 **/
	public static function display_messages() {
		if ( ! empty( MainWPBulkSettingsManager::$messages ) ) {
			?>
			<div class="ui green message">
				<?php
				foreach ( MainWPBulkSettingsManager::$messages as $message ) {
					echo '<p>' . esc_html( $message ) . '</p>';
				}
				?>
			</div>
			<?php
		}

		if ( ! empty( MainWPBulkSettingsManager::$error_messages ) ) {
			?>
			<div class="ui red message">
				<?php
				foreach ( MainWPBulkSettingsManager::$error_messages as $message ) {
					echo '<p>' . esc_html( $message ) . '</p>';
				}
				?>
			</div>
			<?php
		}
	}

	/**
	 * @param $name
	 *
	 * @return string
	 *
	 * Some fields are tricky to use
	 * For example http://codex.wordpress.org/Changing_The_Site_URL
	 */
	public static function check_if_blacklisted_name( $name ) {
		$name = strtolower( trim( $name ) );
		if ( in_array( $name, array( 'siteurl', 'home' ) ) ) {
			return '<span class="ui red label">' . __( 'Be carefull when using this name', self::$plugin_translate ) . '</span>';
		}
	}

	/**
	 * @param string $description
	 * @param string $name
	 * @param string $value
	 * @param string $type
	 *
	 * Render <input type="text">
	 */
	public static function render_text_field( $description = "", $name = "", $value = "", $type = "" ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action"><a class="widget-action hide-if-no-js" href="#"></a></div>
				<div class="widget-title">
					<h4><?php _e( 'Text Field', self::$plugin_translate ); ?> <span class="in-widget-title"></span></h4>
				</div>
			</div>
			<div class="widget-inside">
				<div class="widget-content ui form">
					<input type="hidden" name="field_type" value="text_field">
					<div class="field">
						<label><?php _e( 'Description', self::$plugin_translate ); ?></label>
						<input class="bulk_settings_manager_description" name="text_field_description" type="text" value="<?php echo esc_attr( $description ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Name', self::$plugin_translate ); ?></label>
						<input class="" name="text_field_name" type="text" value="<?php echo esc_attr( $name ); ?>"/>
						<?php echo self::check_if_blacklisted_name( $name ); ?>
					</div>
					<div class="field">
						<label><?php _e( 'Value', self::$plugin_translate ); ?></label>
						<input class="" name="text_field_value" type="text" value="<?php echo esc_attr( $value ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Type', self::$plugin_translate ); ?></label>
						<select name="text_field_type" class="ui dropdown">
							<option value="post">$_POST</option>
							<option value="get" <?php selected( $type, 'get' ); ?>>$_GET</option>
						</select>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="widget-control-actions">
					<a class="widget-control-remove ui mini button" href="#remove"><?php _e( 'Delete', self::$plugin_translate ); ?></a>
					<a class="widget-control-close ui mini button" href="#close"><?php _e( 'Close', self::$plugin_translate ); ?></a>
				</div>
				<div class="ui clearing hidden divider"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $description
	 * @param string $name
	 * @param string $value
	 * @param string $type
	 *
	 * Render <textarea>
	 */
	public static function render_textarea_field( $description = "", $name = "", $value = "", $type = "" ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action"><a class="widget-action hide-if-no-js" href="#"></a></div>
				<div class="widget-title">
					<h4><?php _e( 'Textarea Field', self::$plugin_translate ); ?> <span class="in-widget-title"></span></h4>
				</div>
			</div>
			<div class="widget-inside">
				<div class="widget-content ui form">
					<input type="hidden" name="field_type" value="textarea_field">
					<div class="field">
						<label><?php _e( 'Description', self::$plugin_translate ); ?></label>
						<input class="bulk_settings_manager_description" name="textarea_field_description" type="text" value="<?php echo esc_attr( $description ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Name', self::$plugin_translate ); ?></label>
						<input class="" name="textarea_field_name" type="text" value="<?php echo esc_attr( $name ); ?>"/>
						<?php echo self::check_if_blacklisted_name( $name ); ?>
					</div>
					<div class="field">
						<label><?php _e( 'Value', self::$plugin_translate ); ?></label>
						<textarea class="widefat" name="textarea_field_value"><?php echo esc_textarea( $value ); ?></textarea>
					</div>
					<div class="field">
						<label><?php _e( 'Type', self::$plugin_translate ); ?></label>
						<select name="textarea_field_type" class="ui dropdown">
							<option value="post">$_POST</option>
							<option value="get" <?php selected( $type, 'get' ); ?>>$_GET</option>
						</select>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="widget-control-actions">
					<a class="widget-control-remove ui mini button" href="#remove"><?php _e( 'Delete', self::$plugin_translate ); ?></a>
					<a class="widget-control-close ui mini button" href="#close"><?php _e( 'Close', self::$plugin_translate ); ?></a>
				</div>
				<div class="ui clearing hidden divider"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $name
	 * @param string $value
	 * @param string $type
	 *
	 * Render <input type="submit">
	 */
	public static function render_submit_field( $name = "", $value = "", $type = "" ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action"><a class="widget-action hide-if-no-js" href="#"></a></div>
				<div class="widget-title">
					<h4><?php _e( 'Submit Field', self::$plugin_translate ); ?> <span class="in-widget-title"></span></h4>
				</div>
			</div>
			<div class="widget-inside">
				<div class="widget-content ui form">
					<input type="hidden" name="field_type" value="submit_field">
					<div class="field">
						<label><?php _e( 'Name', self::$plugin_translate ); ?></label>
						<input class="" name="submit_field_name" type="text" value="<?php echo esc_attr( $name ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Value', self::$plugin_translate ); ?></label>
						<input name="submit_field_value" type="text" value="<?php echo esc_attr( $value ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Type', self::$plugin_translate ); ?></label>
						<select name="textarea_field_type" class="ui dropdown">
							<option value="post">$_POST</option>
							<option value="get" <?php selected( $type, 'get' ); ?>>$_GET</option>
						</select>
					</div>
				</div>
				<div class="widget-control-actions">
					<div class="ui divider"></div>
					<div class="widget-control-actions">
						<a class="widget-control-remove ui mini button" href="#remove"><?php _e( 'Delete', self::$plugin_translate ); ?></a>
						<a class="widget-control-close ui mini button" href="#close"><?php _e( 'Close', self::$plugin_translate ); ?></a>
					</div>
					<div class="ui clearing hidden divider"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $name
	 * @param string $url
	 *
	 * All keys need to have name and url
	 */
	public static function render_settings_field( $name = "", $url = "" ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action"><a class="widget-action hide-if-no-js" href="#"></a></div>
				<div class="widget-title">
					<h4><?php _e( 'Key Settings', self::$plugin_translate ); ?><span class="in-widget-title"></span></h4>
				</div>
			</div>
			<div class="widget-inside widget_settings">
				<div class="widget-content ui form">
					<input type="hidden" name="field_type" value="settings_field">
					<div class="field">
						<label><?php _e( 'Settings name', self::$plugin_translate ); ?></label>
						<input name="settings_field_name" type="text" value="<?php echo esc_attr( $name ); ?>" />
					</div>
					<div class="field">
						<label><?php _e( 'Settings URL', self::$plugin_translate ); ?></label>
						<input name="settings_field_url" type="text" value="<?php echo esc_attr( $url ); ?>" />
					</div>
					<div class="field">
						<label><?php _e( 'New keyring', self::$plugin_translate ); ?></label>
						<input name="settings_field_keyring" type="text" />
					</div>
					<div class="field">
						<label><?php _e( 'Or select existing Key Ring', self::$plugin_translate ); ?></label>
						<select name="settings_field_keyring_select" multiple style="width: 100%;">
							<?php
							foreach ( MainWPBulkSettingsManagerDB::Instance()->get_key_rings_by_entry_id( ( $name == "" && $url == "" ) ? 0 : self::$id ) as $keyring ) {
								echo '<option value="' . esc_attr__( $keyring['id'] ) . '" ' . selected( 1, $keyring['checked'] ) . '>' . esc_html( $keyring['name'] ) . '</option>';
							}
							?>
						</select>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="widget-control-actions">
					<a class="widget-control-close ui mini button" href="#close"><?php _e( 'Close', self::$plugin_translate ); ?></a>
				</div>
				<div class="ui clearing hidden divider"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $name
	 * @param string $arg
	 *
	 * Render all nonce fields ( so generated using wp_nonce_field)
	 */
	public static function render_nonce_field( $name = "", $arg = "" ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action"><a class="widget-action hide-if-no-js" href="#"></a></div>
				<div class="widget-title">
					<h4><?php _e( 'Nonce', self::$plugin_translate ); ?><span class="in-widget-title"></span></h4>
				</div>
			</div>
			<div class="widget-inside">
				<div class="widget-content ui form">
					<input type="hidden" name="field_type" value="nonce_field">
					<div class="field">
						<label><?php _e( 'Nonce name', self::$plugin_translate ); ?></label>
						<input name="nonce_field_name" type="text" value="<?php echo esc_attr( $name ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Optional query arg', self::$plugin_translate ); ?></label>
						<input name="nonce_field_arg" type="text" value="<?php echo esc_attr( $arg ); ?>"/>
					</div>
				</div>
				<div class="widget-control-actions">
					<div class="ui divider"></div>
					<div class="widget-control-actions">
						<a class="widget-control-remove ui mini button" href="#remove"><?php _e( 'Delete', self::$plugin_translate ); ?></a>
						<a class="widget-control-close ui mini button" href="#close"><?php _e( 'Close', self::$plugin_translate ); ?></a>
					</div>
					<div class="ui clearing hidden divider"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $ok
	 * @param string $fail
	 *
	 * Sometimes we want to check if some text exist in response
	 */
	public static function render_search_field( $ok = "", $fail = "" ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action"><a class="widget-action hide-if-no-js" href="#"></a></div>
				<div class="widget-title">
					<h4><?php _e( 'Search Text', self::$plugin_translate ); ?><span class="in-widget-title"></span></h4>
				</div>
			</div>
			<div class="widget-inside">
				<div class="widget-content ui form">
					<input type="hidden" name="field_type" value="search_field">
					<div class="field">
						<label><?php _e( 'OK text', self::$plugin_translate ); ?></label>
						<input name="search_field_ok" type="text" value="<?php echo esc_attr( $ok ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Fail text', self::$plugin_translate ); ?></label>
						<input name="search_field_fail" type="text" value="<?php echo esc_attr( $fail ); ?>"/>
					</div>
				</div>
				<div class="widget-control-actions">
					<div class="ui divider"></div>
					<div class="widget-control-actions">
						<a class="widget-control-remove ui mini button" href="#remove"><?php _e( 'Delete', self::$plugin_translate ); ?></a>
						<a class="widget-control-close ui mini button" href="#close"><?php _e( 'Close', self::$plugin_translate ); ?></a>
					</div>
					<div class="ui clearing hidden divider"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * @param string $description
	 * @param string $name
	 * @param string $type
	 * @param string $type_send
	 * @param array $fields
	 *
	 * Render <select> field
	 */
	public static function render_selectbox_field( $description = "", $name = "", $type = "", $type_send = "", $fields = array() ) {
		?>
		<div class='widget'>
			<div class="widget-top">
				<div class="widget-title-action"><a class="widget-action hide-if-no-js" href="#"></a></div>
				<div class="widget-title">
					<?php if ( $type == 'checkbox' ) : ?>
						<h4><?php _e( 'Check Box', self::$plugin_translate ); ?> <span class="in-widget-title"></span></h4>
					<?php else : ?>
						<h4><?php _e( 'Select Box / Radio Box', self::$plugin_translate ); ?> <span class="in-widget-title"></span></h4>
					<?php endif; ?>
				</div>
			</div>
			<div class="widget-inside">
				<div class="widget-content ui form">
					<input type="hidden" name="field_type" value="selectbox_field">
					<div class="field">
						<label><?php _e( 'Description', self::$plugin_translate ); ?></label>
						<input class="bulk_settings_manager_description" name="selectbox_field_description" type="text" value="<?php echo esc_attr( $description ); ?>"/>
					</div>
					<div class="field">
						<label><?php _e( 'Name', self::$plugin_translate ); ?></label>
						<input class="" name="selectbox_field_name" type="text" value="<?php echo esc_attr( $name ); ?>"/>
					</div>
					<input type="hidden" name="selectbox_field_type" value="<?php echo( $type == 'checkbox' ? 'checkbox' : 'radio' ); ?>">
					<div class="field">
						<label><?php _e( 'Type', self::$plugin_translate ); ?></label>
						<select name="selectbox_field_type_send" class="ui dropdown">
							<option value="post">$_POST</option>
							<option value="get" <?php selected( $type_send, 'get' ); ?>>$_GET</option>
						</select>
					</div>

					<table class="<?php echo( $type == 'checkbox' ? 'selectbox_field_table' : 'radio_field_table' ); ?> ui table">
						<thead>
						<tr>
							<td></td>
							<td></td>
							<td><?php _e( 'Label', self::$plugin_translate ); ?></td>
							<td><?php _e( 'Value', self::$plugin_translate ); ?></td>
							<td></td>
						</tr>
						</thead>
						<tbody>
						<?php
						if ( empty( $fields ) ) {
							?>
							<tr>
								<td><i class="ui angle double right icon"></i></td>
								<td>
									<input type="<?php echo( $type == 'checkbox' ? 'checkbox' : 'radio' ); ?>" name="fake_radio_name_replacement" class="<?php echo( $type == 'checkbox' ? 'selectbox_field_checkbox_click' : 'selectbox_field_radio_click' ); ?>">
									<input type="hidden" name="selectbox_field_checkbox" value="0">
								</td>
								<td><input type="text" name="selectbox_field_label"></td>
								<td><input type="text" name="selectbox_field_value"></td>
								<td><a class="<?php echo( $type == 'checkbox' ? 'selectbox_field_add_click' : 'radio_field_add_click' ); ?>"><i class="ui plus circle icon"></i></a> <a class="selectbox_field_remove_click"><i class="minus circle icon"></i></a></td>
							</tr>
							<?php
						}

						foreach ( $fields as $field ):
							?>
							<tr>
								<td><i class="ui angle double right icon"></i></td>
								<td>
									<input type="<?php echo( $type == 'checkbox' ? 'checkbox' : 'radio' ); ?>" <?php checked( $field['selectbox_field_checkbox'], 1 ); ?> name="fake_radio_name_<?php echo (int) self::$render_selectbox_counter; ?>" class="<?php echo( $type == 'checkbox' ? 'selectbox_field_checkbox_click' : 'selectbox_field_radio_click' ); ?>">
									<input type="hidden" name="selectbox_field_checkbox" value="<?php echo( $field['selectbox_field_checkbox'] == 1 ? 1 : 0 ); ?>">
								</td>
								<td><input type="text" name="selectbox_field_label" value="<?php echo esc_attr( $field['selectbox_field_label'] ); ?>"></td>
								<td><input type="text" name="selectbox_field_value" value="<?php echo esc_attr( $field['selectbox_field_value'] ); ?>"></td>
								<td>
									<a href="" rel="<?php echo (int) self::$render_selectbox_counter; ?>" class="<?php echo( $type == 'checkbox' ? 'selectbox_field_add_click' : 'radio_field_add_click' ); ?>"><i class="ui plus circle icon"></i></a> <a href="" class="selectbox_field_remove_click"><i class="minus circle icon"></i></a></td>
							</tr>
							<?php
						endforeach;
						++ self::$render_selectbox_counter;
						?>
						</tbody>

					</table>
				</div>
				<div class="widget-control-actions">
					<div class="ui divider"></div>
					<div class="widget-control-actions">
						<a class="widget-control-remove ui mini button" href="#remove"><?php _e( 'Delete', self::$plugin_translate ); ?></a>
						<a class="widget-control-close ui mini button" href="#close"><?php _e( 'Close', self::$plugin_translate ); ?></a>
					</div>
					<div class="ui clearing hidden divider"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render wp-admin/admin.php?page=Extensions-Mainwp-Bulk-Settings-Manager
	 */
	public static function render_view() {
		self::display_messages();
		add_thickbox();

		self::$id = ( isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0 );
		if ( self::$id > 0 ) {
			$entry = MainWPBulkSettingsManagerDB::Instance()->get_entry_by_id( self::$id );
		}

		?>


		<noscript><?php _e( 'Please enable JavaScript.', self::$plugin_translate ); ?></noscript>
		<div id="ngBulkSettingsManagerId" ng-app="ngBulkSettingsManagerApp" style="display: none; background: #fff;">
			<div ng-controller="ngBulkSettingsManagerController">
				<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-bulk-settings-manager-menu">
					<a class="<?php echo ( self::$id > 0 ) ? '' : 'active'; ?> item" data-tab="bsm-keyrings-tab"><i class="icons"><i class="big circle outline icon"></i><i class="key icon"></i></i> <?php _e( 'Key Rings', self::$plugin_translate ); ?></a>
					<a class="item" data-tab="bsm-single-keys-tab"><i class="key icon"></i> <?php _e( 'Single Keys', self::$plugin_translate ); ?></a>
					<a class="item" data-tab="bsm-new-tab"><i class="add icon"></i> <?php _e( 'Create New Key', self::$plugin_translate ); ?></a>
					<?php if ( self::$id > 0 ) : ?>
					<a class="active item" data-tab="bsm-edit-tab"><i class="cog icon"></i> <?php _e( 'Key Settings', self::$plugin_translate ); ?></a>
					<?php endif; ?>
					<a class="item" data-tab="bsm-history-tab"><i class="history icon"></i> <?php _e( 'History', self::$plugin_translate ); ?></a>
					<a class="item" data-tab="bsm-settings-tab"><i class="cog icon"></i> <?php _e( 'Settings', self::$plugin_translate ); ?></a>
					<a class="item" data-tab="bsm-import-tab"><i class="download icon"></i> <?php _e( 'Import Keys', self::$plugin_translate ); ?></a>
				</div>

				<div class="ui <?php echo ( self::$id > 0 ) ? '' : 'active'; ?> tab" data-tab="bsm-keyrings-tab">
					<div class="mainwp-main-content ui segment">
						<div class="ui red message bsm" style="display: none;"></div>
						<div class="ui green message bsm" style="display: none;"></div>
						<div id="bulk_settings_manager_preview" style="display: none;"></div>
						<div tasty-table bind-resource-callback="get_keyring" bind-init="init_get_keyring" bind-theme="keyring_theme" bind-reload="reload_keyring_callback">

						<div class="ui stackable grid">
							<div class="row">
								<div class="eight wide column"></div>
								<div class="right aligned eight wide column">
									<div id="" class="ui form">
										<label><?php _e( 'Search:', self::$plugin_translate ); ?>
											<span class="ui input">
												<input type="search" id="mainwp-bulk-settings-keyrings-search" class="" placeholder="" aria-controls="">
											</span>
										</label>
									</div>
								</div>
							</div>
						</div>

						<table  class="mainwp-bulk-settings-manager-keyrings-table ui stackable single line table">
								<thead>
									<tr>
										<th class="collapsing"></th>
										<th><?php _e( 'Keyring', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Keys', self::$plugin_translate ); ?></th>
										<th class="collapsing"><?php _e( '', self::$plugin_translate ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr ng-if="rows.length==0">
										<td colspan="4">
											<div class="ui segment">
											  <div class="ui active inverted dimmer">
											    <div class="ui text loader"><?php _e( 'Loading...', self::$plugin_translate ); ?></div>
											  </div>
											  <p></p>
											</div>
										</td>
									</tr>
									<tr ng-repeat-start="d in rows">
										<td ng-show="d.id">
											<input class="bulk_settings_manager_keyring_checkbox" type="checkbox" ng-model="scope_checkbox_keyring[d.id]" ng-click="checkbox_keyring_toggle(d.id)" value="{{ d.id }}">
										</td>
										<td>
											<span ng-show="d.id">
												<a ng-hide="scope_display_edit_keyring[d.id]" class="keyrings_search_field" ng-click="toggle_keyring(d.id)">{{ d.name }}</a>
											</span>
											<span ng-hide="d.id">{{ d.name }}</span>
											<span class="ui mini form"><input ng-show="scope_display_edit_keyring[d.id]" type="text" ng-model="scope_edit_keyring[d.id]"></span>
										</td>
										<td ng-show="d.id">
											<a ng-hide="scope_toggled_keyring[d.id]" ng-click="toggle_keyring(d.id)"><?php _e( 'Show Keys', self::$plugin_translate ); ?></a>
											<a ng-show="scope_toggled_keyring[d.id]" ng-click="toggle_keyring(d.id)"><?php _e( 'Hide Keys', self::$plugin_translate ); ?>
											</a>
										</td>
										<td ng-show="d.id">
											<a class="ui mini green button" ng-show="d.id && !scope_display_edit_keyring[d.id]" ng-click="enable_editing_keyring(d.id, d.name)"><?php _e( 'Edit', self::$plugin_translate ); ?></a>
											<a class="ui mini breen basic button" ng-show="d.id && scope_display_edit_keyring[d.id]" ng-click="enable_editing_keyring(d.id)"><?php _e( 'Save', self::$plugin_translate ); ?></a>
											<a class="ui mini button" ng-click="show_notes(d.id, d.name, 'keyring')"><?php _e( 'Notes', 'mainwp' ); ?></a>
											<a class="ui mini button" ng-show="d.id" ng-click="delete_settings(d.id, 'keyring')"><?php _e( 'Delete', self::$plugin_translate ); ?></a>
										</td>
									</tr>

									<tr ng-repeat-end>
									  <td colspan="6" ng-show="scope_toggled_keyring[d.id]">
											<div ng-hide="scope_toggled_keyring_datas[d.id]" class="ui segment">
											  <div class="ui active inverted dimmer">
											    <div class="ui text loader"><?php _e( 'Loading...', self::$plugin_translate ); ?></div>
											  </div>
											  <p></p>
											</div>
									    <table class="mainwp-bulk-settings-manager-toggled-keyrings-table ui stackable single line table" keyring-id="{{ d.id }}" ng-show="scope_toggled_keyring_datas[d.id]">
												<thead>
													<tr>
														<th class="collapsing"></th>
														<th><?php _e( 'Key', self::$plugin_translate ); ?></th>
														<th><?php _e( 'URL', self::$plugin_translate ); ?></th>
														<th class="collapsing"><?php _e( '', self::$plugin_translate ); ?></th>
													</tr>
												</thead>
									      <tbody>
													<tr ng-repeat="dd in scope_toggled_keyring_datas[d.id]">
										        <td><input ng-show="dd.id" ng-checked="scope_checkbox_keyring[d.id]" type="checkbox" class="bulk_settings_manager_checkbox_keyring_subkey" value="{{ dd.id }}"></td>
										        <td>{{ dd.name }}</td>
										        <td>{{ dd.url }}</td>
										        <td>
															<a class="ui mini green button" ng-show="dd.id" href="<?php echo admin_url( 'admin.php?page=Extensions-Mainwp-Bulk-Settings-Manager&id=' ); ?>{{ dd.id }}"><?php _e( 'Edit', self::$plugin_translate ); ?></a>
										        	<a class="ui mini button" ng-show="dd.id>0" ng-click="remove_from_keyring(d.id, dd.id)"><?php _e( 'Remove from the Key Ring', self::$plugin_translate ); ?></a>
														</td>
										      </tr>
												</tbody>
									    </table>
										<!-- <div tasty-pagination template-url="custom_table_template_ring.html"></div> -->
									  </td>
									</tr>
								</tbody>
								<tfoot>
									<tr>
										<th></th>
										<th><?php _e( 'Keyring', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Keys', self::$plugin_translate ); ?></th>
										<th><?php _e( '', self::$plugin_translate ); ?></th>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>
					<div class="mainwp-side-content mainwp-no-padding">
						<div class="mainwp-select-sites mainwp_select_sites_keyring">
							<div class="ui header"><?php _e( 'Select Sites', self::$plugin_translate ); ?></div>
							<?php do_action( 'mainwp_select_sites_box', __( "Select Sites", 'mainwp' ), 'checkbox', true, true, '', "", array(), array() ); ?>
						</div>
						<div class="ui divider"></div>
						<div class="mainwp-search-submit">
							<input type="button" class="ui big green fluid button" ng-click="send_to_child('keyring')" value="<?php _e( 'Save Key Ring', self::$plugin_translate ); ?>">
							<div class="ui fitted hidden divider"></div>
							<div class="ui fitted hidden divider"></div>
							<input type="button" class="ui big fluid button" ng-click="delete_settings_bulk('keyring')" value="<?php _e( 'Delete Selected Key Rings', self::$plugin_translate ); ?>">
						</div>
					</div>
					<div class="ui clearing hidden divider"></div>
				</div>

				<div class="ui tab" data-tab="bsm-single-keys-tab">
					<div class="mainwp-main-content ui segment">
						<div class="ui red message bsm" style="display: none;"></div>
						<div class="ui green message bsm" style="display: none;"></div>
						<div id="bulk_settings_manager_preview" style="display: none;"></div>
						<div tasty-table bind-resource-callback="get_key" bind-init="init_get_key" bind-theme="key_theme" bind-reload="reload_key_callback">
							<table id="mainwp-bulk-settings-manager-keys-table" class="ui stackable single line table">
								<thead>
									<tr>
										<th class="no-sort collapsing"></th>
										<th class="no-sort"><?php _e( 'Key', self::$plugin_translate ); ?></th>
										<th class="no-sort"><?php _e( 'URL', self::$plugin_translate ); ?></th>
										<th class="no-sort"><?php _e( 'Sutmission Time', self::$plugin_translate ); ?></th>
										<th class="no-sort"><?php _e( 'Last Edit', self::$plugin_translate ); ?></th>
										<th class="no-sort collapsing"><?php _e( '', self::$plugin_translate ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr ng-repeat="d in rows">
										<td ng-show="d.id"><input type="checkbox" class="bulk_settings_manager_checkbox" value="{{ d.id }}"></td>
										<td>{{ d.name }}</td>
										<td>{{ d.url }}</td>
										<td ng-show="d.id">{{ d.created_time }}</td>
										<td ng-show="d.id">{{ d.edited_time }}</td>
										<td ng-show="d.id" class="right aligned">
											<a class="ui mini green button" ng-show="d.id" ng-click="enable_editing(d.id)" href="<?php echo admin_url( 'admin.php?page=Extensions-Mainwp-Bulk-Settings-Manager&id=' ); ?>{{ d.id }}"><?php _e( 'Edit', self::$plugin_translate ); ?></a>
											<a class="ui mini basic green button" ng-show="d.id" ng-click="export_settings([d.id])"><?php _e( 'Export', self::$plugin_translate ); ?></a>
											<a class="ui mini button" ng-click="show_notes(d.id, d.name, 'entry')"><?php _e( 'Notes', 'mainwp' ); ?></a>
											<a class="ui mini button" ng-show="d.id" ng-click="delete_settings(d.id, 'key')" ><?php _e( 'Delete', self::$plugin_translate ); ?></a>
										</td>
									</tr>
								</tbody>
								<tfoot>
									<tr>
										<th class="collapsing"></th>
										<th><?php _e( 'Key', self::$plugin_translate ); ?></th>
										<th><?php _e( 'URL', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Sutmission Time', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Last Edit', self::$plugin_translate ); ?></th>
										<th class="collapsing"><?php _e( '', self::$plugin_translate ); ?></th>
									</tr>
								</tfoot>
							</table>
							<!-- <div tasty-pagination template-url="custom_table_template_key.html"></div> -->

						</div>
					</div>
					<div class="mainwp-side-content mainwp-no-padding">
						<div class="mainwp-select-sites mainwp_select_sites_key">
							<div class="ui header"><?php _e( 'Select Sites', self::$plugin_translate ); ?></div>
							<?php do_action( 'mainwp_select_sites_box', __( "Select Sites", 'mainwp' ), 'checkbox', true, true, "", "", array(), array() ); ?>
						</div>
						<div class="ui divider"></div>
						<div class="mainwp-search-submit">
							<input type="button" class="ui big green fluid button" ng-click="send_to_child('key')" value="<?php _e( 'Save Key', self::$plugin_translate ); ?>">
							<div class="ui fitted hidden divider"></div>
							<div class="ui fitted hidden divider"></div>
							<input type="button" class="ui big green basic fluid button" ng-click="export_bulk()" value="<?php _e( 'Export Selected Keys', self::$plugin_translate ); ?>">
							<div class="ui fitted hidden divider"></div>
							<div class="ui fitted hidden divider"></div>
							<input type="button" class="ui big fluid button" ng-click="delete_settings_bulk('key')" value="<?php _e( 'Delete Selected Keys', self::$plugin_translate ); ?>">
						</div>
					</div>
				</div>

				<?php if ( self::$id > 0 ): ?>
					<div class="ui active tab" id="mainwp-bulk-settings-manager-edit-key-tab">
						<div class="ui divider hidden"></div>
						<div class="ui red message bsm" style="display:none;"></div>
						<div class="ui green message bsm" style="display:none;"></div>
						<div id="bulk_settings_manager_preview" style="display: none;"></div>
						<?php if ( isset( $_GET['just_imported'] ) ) : ?>
							<div class="ui segment">
								<div class="ui green message">
									<i class="close icon"></i>
									<div class="header">
										<?php _e( 'Your Key has been made.', self::$plugin_translate ); ?>
									</div>
									<?php _e( 'Please review the key fields below to verify everything imported correctly.', self::$plugin_translate ); ?>
								</div>
							</div>
							<?php endif; ?>
							<?php if ( empty( $entry ) ) : ?>
							<div class="ui red message"><?php _e( 'This entry does not exist.', self::$plugin_translate ); ?></div>
							<?php else :
							$args = json_decode( $entry->settings, true );
							if ( ! isset( $args['all_args'] ) ) {
								echo __( 'Missing all_args', self::$plugin_translate );
								return;
							}
							$args = $args['all_args'];
							?>
							<input type="hidden" id="mainwp_bulk_settings_manager_edit_id" value="<?php echo esc_attr( self::$id ); ?>">
							<div class="mainwp-widget-liquid-right widget-liquid-right">
								<div class="ui secondary segment">
									<div class="available-widgets">
										<h2 class="ui header">
											<?php _e( 'Available Fields', self::$plugin_translate ); ?>
											<div class="sub header"><?php _e( 'Use available fields to create your key. You can select a field by dragging it to the Key Fields section.', self::$plugin_translate ); ?></div>
										</h2>
										<div class="widget-holder">
											<div class="widget-list">
												<?php
												self::render_nonce_field();
												self::render_text_field();
												self::render_textarea_field();
												self::render_submit_field();
												self::render_selectbox_field( "", "", "checkbox", "" );
												self::render_search_field();
												self::render_selectbox_field( "", "", "radio", "" );
												?>
											</div>
										</div>
									</div>
								</div>
							</div>
							<form method="post" action="" id="mainwp_bulk_settings_manager_edit_form">
								<div class="widget-liquid-left mainwp-widget-liquid-left">
									<div class="ui segment">
									<div class="single-sidebar">
										<div class="sidebars-column-1">
											<div class="">
												<h2 class="ui header">
													<?php _e( 'Key Fields', self::$plugin_translate ); ?>
													<div class="sub header"><?php _e( 'Insert key fields by dragging them from the Available Fields section.', self::$plugin_translate ); ?></div>
												</h2>
												<?php
												if ( isset( $args[0] ) && isset( $args[0]['field_type'] ) && $args[0]['field_type'] == 'settings_field' ) {
													self::render_settings_field( $args[0]['settings_field_name'], $args[0]['settings_field_url'] );
												} else {
													echo 'Data Missmatch';

													return;
												}
												?>

												<div class="widgets-sortables" style="min-height: 100px" id="left_widgets_list_<?php echo self::$id; ?>">
													<?php
													for ( $i = 1; $i < count( $args ); ++ $i ) {
														switch ( $args[ $i ]['field_type'] ) {
															case 'text_field':
																self::render_text_field( $args[ $i ]['text_field_description'], $args[ $i ]['text_field_name'], $args[ $i ]['text_field_value'], $args[ $i ]['text_field_type'] );
																break;

															case 'textarea_field':
																self::render_textarea_field( $args[ $i ]['textarea_field_description'], $args[ $i ]['textarea_field_name'], $args[ $i ]['textarea_field_value'], $args[ $i ]['textarea_field_type'] );
																break;

															case 'nonce_field':
																self::render_nonce_field( $args[ $i ]['nonce_field_name'], $args[ $i ]['nonce_field_arg'] );
																break;

															case 'submit_field':
																self::render_submit_field( $args[ $i ]['submit_field_name'], $args[ $i ]['submit_field_value'], $args[ $i ]['submit_field_type'] );
																break;

															case 'search_field':
																self::render_search_field( $args[ $i ]['search_field_ok'], $args[ $i ]['search_field_fail'] );
																break;

															case 'selectbox_field':
																self::render_selectbox_field( $args[ $i ]['selectbox_field_description'], $args[ $i ]['selectbox_field_name'], $args[ $i ]['selectbox_field_type'], $args[ $i ]['selectbox_field_type_send'], $args[ $i ]['fields'] );
																break;

															default:
																echo 'Invalid field type';
														}
													}
													?>
												</div>
												<div class="ui divider"></div>
												<input type="button" class="ui green big button" name="sending" value="<?php _e( 'Save Key', self::$plugin_translate ); ?>" id="mainwp_bulk_settings_manager_edit_button">
	                      <input type="button" class="ui big button" value="<?php _e( 'Cancel', self::$plugin_translate ); ?>" ng-click="cancel_editing(<?php echo self::$id; ?>)">
	                      <input type="button" class="ui big button" value="<?php _e( 'Remove All Fields', self::$plugin_translate ); ?>" ng-click="remove_all_fields(<?php echo self::$id; ?>)">
	                      <input type="button" class="ui big button" value="<?php _e( 'Reset All Fields', self::$plugin_translate ); ?>" ng-click="reset_all_fields(<?php echo self::$id; ?>)">
											</div>
										</div>
									</div>
									</div>
								</div>
							</form>

							<div class="widgets-chooser">
								<ul class="widgets-chooser-sidebars"></ul>
								<div class="widgets-chooser-actions">
								</div>
							</div>

						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="ui tab" data-tab="bsm-new-tab">
					<div class="ui segment">
						<div class="ui red message bsm" style="display: none;"></div>
						<div class="ui green message bsm" style="display: none;"></div>
						<div id="bulk_settings_manager_preview" style="display: none;"></div>
						<input type="hidden" id="mainwp_bulk_settings_manager_add_new_id" value="0">
						<div class="ui info message"><?php _e( 'Making Keys by yourself can be tricky and may lead to unwanted issues. It is recommended to let the Key Maker plugin auto-create Keys for you. <a href="https://mainwp.com/help/docs/bulk-settings-manager-extension/create-a-single-key/" target="_blank">Learn more</a>.', self::$plugin_translate ); ?></div>
						<div class="widget-liquid-right mainwp-widget-liquid-right">
							<div class="ui secondary segment">
								<div class="available-widgets">
									<h2 class="ui header">
										<?php _e( 'Available Fields', self::$plugin_translate ); ?>
										<div class="sub header"><?php _e( 'Use available fields to create your key. You can select a field by dragging it to the Key Fields section.', self::$plugin_translate ); ?></div>
									</h2>
									<div class="widget-holder">
										<div class="widget-list">
											<?php
											self::render_nonce_field();
											self::render_text_field();
											self::render_textarea_field();
											self::render_submit_field();
											self::render_search_field();
											self::render_selectbox_field( "", "", "checkbox", "" );
											self::render_selectbox_field( "", "", "radio", "" );
											?>
										</div>
									</div>
								</div>
							</div>
						</div>
						<form method="post" action="" id="mainwp_bulk_settings_manager_add_new_form">
							<div class="widget-liquid-left mainwp-widget-liquid-left">
								<div class="ui segment">
								<div class="single-sidebar">
									<div class="sidebars-column-1">

										<div class="">
											<h2 class="ui header">
												<?php _e( 'Key Fields', self::$plugin_translate ); ?>
												<div class="sub header"><?php _e( 'Insert key fields by dragging them from the Available Fields section.', self::$plugin_translate ); ?></div>
											</h2>
											<?php self::render_settings_field(); ?>
											<div class="widgets-sortables" style="min-height: 100px" id="left_widgets_list_0"></div>
											<div class="ui divider"></div>
											<input type="button" class="ui big green button" name="sending" value="Save Key" id="mainwp_bulk_settings_manager_add_new_button">
	                    <input type="button" class="ui big button" value="Remove All Fields" ng-click="remove_all_fields(0)">
	                    <input type="button" class="ui big green basic button" value="Reset All Fields" ng-click="reset_all_fields(0)">

										</div>
									</div>
								</div>
							</div>
							</div>
						</form>
					</div>
					<div class="widgets-chooser">
						<ul class="widgets-chooser-sidebars"></ul>
						<div class="widgets-chooser-actions"></div>
					</div>
				</div>

				<div class="ui tab" data-tab="bsm-history-tab">
					<div class="ui segment">
						<div class="ui red message bsm" style="display: none;"></div>
						<div class="ui green message bsm" style="display: none;"></div>
						<div id="bulk_settings_manager_preview" style="display: none;"></div>
						<div tasty-table bind-resource-callback="get_history" bind-init="init_get_history" bind-theme="history_theme" bind-reload="reload_history_callback">
							<table class="ui stackable table" id="mainwp-bulk-settings-manager-history-table">
								<thead>
									<tr>
										<th><?php _e( 'Entry', self::$plugin_translate ); ?></th>
										<th><?php _e( 'URL', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Submission Time', self::$plugin_translate ); ?></th>
										<th class="collapsing right aligned no-sort"><?php _e( '', self::$plugin_translate ); ?></th>
									</tr>
								</thead>
								<tbody>
									<tr ng-repeat="d in rows">
										<td>{{ d.name }}</td>
										<td>{{ d.url }}</td>
										<td ng-show="d.id">{{ d.created_time }}</td>
										<td ng-show="d.id" class="right aligned">
											<div class="ui right pointing dropdown icon mini basic green button">
												<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
												<div class="menu">
													<a href="/?TB_inline&width=1200&height=auto&inlineId=bulk_settings_manager_preview" class="item thickbox" ng-click="preview(d.id, 1, d.secret)"><?php _e( 'Review Changes', self::$plugin_translate ); ?></a>
													<a href="/?TB_inline&width=1200&height=auto&inlineId=bulk_settings_manager_preview" class="item thickbox" ng-click="preview(d.id, 0, d.secret)"><?php _e( 'Review  Parameters', self::$plugin_translate ); ?></a>
												</div>
											</div>
										</td>
									</tr>
								</tbody>
								<tfoot>
									<tr>
										<th><?php _e( 'Entry', self::$plugin_translate ); ?></th>
										<th><?php _e( 'URL', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Submission Time', self::$plugin_translate ); ?></th>
										<th class="collapsing"><?php _e( '', self::$plugin_translate ); ?></th>
									</tr>
								</tfoot>
							</table>
							<!-- <div tasty-pagination template-url="custom_table_template_history.html"></div> -->
						</div>
					</div>
				</div>

				<div class="ui tab" data-tab="bsm-settings-tab">
					<div class="ui segment">
						<div class="ui red message bsm" style="display: none;"></div>
						<div class="ui green message bsm" style="display: none;"></div>
						<div id="bulk_settings_manager_preview" style="display: none;"></div>
						<div class="ui form">
							<h3 class="ui dividing header"><?php _e( 'Settings', self::$plugin_translate ); ?></h3>
							<div class="ui grid field">
								<label class="six wide column middle aligned"><?php _e( 'Delay', self::$plugin_translate ); ?></label>
							  <div class="five wide column">
									<input type="text" id="mainwp_bulk_settings_manager_interval" value="<?php echo esc_attr( get_option( 'mainwp_bulk_settings_manager_interval', 5 ) ); ?>" data-inverted="" data-position="top right" data-tooltip="<?php _e( 'Allows you to set time delay between two submissions. For example, if you set delay to 5 seconds, and submit a key to 3 child sites, after the Key has been submitted to the first site, the extension will wait for 5 seconds before it proceeds to the next child site. This option helps you to reduce server load.', self::$plugin_translate ); ?>">
								</div>
								<div class="five wide column">
									 <input type="submit" class="ui green button" value="<?php _e( 'Change Delay', self::$plugin_translate ); ?>" ng-click="change_settings('interval')">
								</div>
							</div>
							<div class="ui grid field">
								<label class="six wide column middle aligned"><?php _e( 'Clear history', self::$plugin_translate ); ?></label>
							  <div class="ten wide column">
									<input type="submit" class="ui green button" value="<?php _e( 'Clear History', self::$plugin_translate ); ?>" ng-click="change_settings('history')">
								</div>
							</div>
							<?php if ( defined( "MAINWP_BOILERPLATE_PLUGIN_FILE" ) ) : ?>
								<div class="ui grid field">
									<label class="six wide column middle aligned"><?php _e( 'Use Boilerplate', self::$plugin_translate ); ?></label>
								  <div class="ten wide column ui toggle checkbox not-auto-init">
										<input ng-click="change_settings('boilerplate')" type="checkbox" name="mainwp_options_wp_cron" id="mainwp_bulk_settings_manager_boilerplate_checkbox" <?php echo checked( get_option( 'mainwp_bulk_settings_manager_use_boilerplate', 0 ), 1 ); ?> >
										<label for="mainwp_bulk_settings_manager_boilerplate_checkbox"></label>
									</div>
								</div>
							<?php else: ?>
								<div class="ui grid field">
									<label class="six wide column middle aligned"><?php _e( 'Use Boilerplate', self::$plugin_translate ); ?></label>
								  <div class="ten wide column">
										<?php _e( 'Bulk Settings Manager Extension integrates with the MainWP Boilerplate Extension. If the extension is installed and activated, you will be able to use boilerplate tokens in key fields.', self::$plugin_translate ); ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div  class="ui tab" data-tab="bsm-import-tab">
					<div class="ui segment">
						<div class="ui red message bsm" style="display: none;"></div>
						<div class="ui green message bsm" style="display: none;"></div>
						<div id="bulk_settings_manager_preview" style="display: none;"></div>
						<form method="post" class="ui form" action="<?php echo admin_url( 'admin.php?page=Extensions-Mainwp-Bulk-Settings-Manager' ); ?>" enctype="multipart/form-data">
							<h3 class="ui dividing header"><?php _e( 'Import from MainWP Key Maker', self::$plugin_translate ); ?></h3>
							<div class="ui grid field">
								<label class="six wide column middle aligned"><?php _e( 'Key name', self::$plugin_translate ); ?></label>
							  <div class="ten wide column">
									<input type="text" name="import_name">
								</div>
							</div>
							<div class="ui grid field">
								<label class="six wide column middle aligned"><?php _e( 'Key code', self::$plugin_translate ); ?></label>
							  <div class="ten wide column">
									<textarea name="import" rows="10" data-tooltip="<?php _e( 'Paste the code created by the MainWP Key Maker plugin to have your key auto-built', self::$plugin_translate ); ?>" data-inverted=""></textarea>
									<?php wp_nonce_field( MainWPBulkSettingsManager::$nonce_token . 'import' ); ?>
									<input type="hidden" name="import_text" value="1">
								</div>
							</div>
							<div class="ui divider"></div>
							<input type="submit" class="ui big green right floated button" value="<?php _e( 'Make Key', self::$plugin_translate ); ?>">
						</form>
					</div>
					<div class="ui hidden divider"></div>
					<div class="ui hidden divider"></div>
					<div class="ui segment">
						<form method="post" class="ui form" action="<?php echo admin_url( 'admin.php?page=Extensions-Mainwp-Bulk-Settings-Manager' ); ?>" enctype="multipart/form-data">
							<h3 class="ui dividing header"><?php _e( 'Import Key From a File', self::$plugin_translate ); ?></h3>
							<div class="ui grid field">
								<label class="six wide column middle aligned"><?php _e( 'Upload file', self::$plugin_translate ); ?></label>
							  <div class="ten wide column">
									<input type="file" name="import">
									<?php wp_nonce_field( MainWPBulkSettingsManager::$nonce_token . 'import' ); ?>
									<input type="hidden" name="import_file" value="1">
								</div>
							</div>
							<div class="ui divider"></div>
							<input type="submit" class="ui big green right floated button" value="<?php _e( 'Import Key', self::$plugin_translate ); ?>">
						</form>
					</div>
				</div>

				<div id="mainwp-bsm-notes" class="ui modal">
					<div id="mainwp_notes_title" class="header">{{ notes_title }}</div>
					<div class="scrolling content">
						<div class="ui form">
							<textarea ng-model="notes_content"></textarea>
						</div>
						<div>{{ notes_status }}</div>
					</div>
					<div class="actions">
						<form>
							<input type="button" class="ui green button" ng-click="save_notes()" value="<?php _e( 'Save Note', self::$plugin_translate ); ?>"/>
							<div class="ui cancel button"><?php _e( 'Close', self::$plugin_translate ); ?></div>
						</form>
					</div>
				</div>

				<div id="mainwp-bsm-syncing-modal" class="ui large modal">
					<div class="header"><?php _e( 'Data Synchronization', self::$plugin_translate ); ?> (<?php _e( 'delay:', self::$plugin_translate ); ?> {{scope_syncing_delay }})</div>
					<div class="scrolling content">
						<div class="ui relaxed list" id="syncing_message"></div>
					</div>
					<div class="actions">
						<div class="ui cancel button"><?php _e( 'Close', self::$plugin_translate ); ?></div>
					</div>
				</div>

				<?php
				self::render_pager( 'ring', __( 'Key Rings per page', self::$plugin_translate ) );
				self::render_pager( 'key', __( 'Items per page', self::$plugin_translate ) );
				self::render_pager( 'history', __( 'Records per page', self::$plugin_translate ) );
				?>

				<script type="text/javascript">
					jQuery( document ).ready( function () {
						jQuery('#mainwp-bulk-settings-manager-menu .item').tab();
						setTimeout( function(){
							jQuery('#mainwp-bulk-settings-manager-history-table .ui.dropdown').dropdown();
						}, 3000);
					});
				</script>

			</div>
		</div>
		<?php
	}


	/**
	 * Custom pager for ng-table
	 **/
	public static function render_pager( $id, $name ) {
		?>
		<script type="text/ng-template" id="custom_table_template_<?php echo esc_attr( $id ); ?>.html">
			<div class="pager" id="pager">
				<a ng-click="page.get(1)" href=""><img
						src="<?php echo plugins_url( 'images/first.png', dirname( __FILE__ ) ); ?>"
						class="first"></a>
				<a ng-click="page.get(pagination.page-1)" href=""><img
						src="<?php echo plugins_url( 'images/prev.png', dirname( __FILE__ ) ); ?>"
						class="prev"></a>

				<input value="Page {{pagination.page}} of {{pagination.pages}}, {{pagination.size}} rows" type="text"
				       class="pagedisplay">

				<a ng-click="page.get(pagination.page+1)" href=""><img
						src="<?php echo plugins_url( 'images/next.png', dirname( __FILE__ ) ); ?>"
						class="next"></a>
				<a ng-click="page.get(pagination.pages)" href=""><img
						src="<?php echo plugins_url( 'images/last.png', dirname( __FILE__ ) ); ?>"
						class="last"></a>

				<span>&nbsp;&nbsp;<?php _e( 'Show:', self::$plugin_translate ); ?> </span>
				<select class="pagesize" ng-init="bulkSettingsManagerPageSelect=10"
				        ng-model="bulkSettingsManagerPageSelect"
				        ng-change="page.setCount(bulkSettingsManagerPageSelect)">
					<option value="10">10</option>
					<option value="25">25</option>
					<option value="50">50</option>
					<option value="100">100</option>
					<option value="1000000000">All</option>
				</select>
				<span><?php echo esc_html( $name ); ?></span>
			</div>
			<div class="clear"></div>
		</script>
		<?php
	}


}
