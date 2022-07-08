<?php

class MainWPBackWPupDestinationSugar extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		if ( isset( $_POST['sugardir'] ) ) {
			$settings['sugardir'] = trim( stripslashes( $_POST['sugardir'] ) );
		}

		$settings['sugarrefreshtoken']   = ( isset( $_POST['sugarrefreshtoken'] ) ? $_POST['sugarrefreshtoken'] : '' );
		$settings['sugaremail']          = ( isset( $_POST['sugaremail'] ) ? $_POST['sugaremail'] : '' );
		$settings['sugarpass']           = ( isset( $_POST['sugarpass'] ) ? $_POST['sugarpass'] : '' );
		$settings['sugarroot']           = ( isset( $_POST['sugarroot'] ) ? $_POST['sugarroot'] : '' );
		$settings['sugarfolderselected'] = ( isset( $_POST['sugarfolderselected'] ) ? $_POST['sugarfolderselected'] : '' );
		$settings['sugarmaxbackups']     = ( isset( $_POST['sugarmaxbackups'] ) ? (int) $_POST['sugarmaxbackups'] : 0 );
		$settings['sugarsyncnodelete']   = ( ( isset( $_POST['sugarsyncnodelete'] ) && $_POST['sugarsyncnodelete'] == 1 ) ? true : false );
		$settings['authbutton']          = '';

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

				<?php if ( strlen( MainWPBackWPUpView::get_value( MainWPBackWPUpView::$information['settings'], 'sugarsynckey', '' ) ) > 2 && strlen( MainWPBackWPUpView::get_value( MainWPBackWPUpView::$information['settings'], 'sugarsyncsecret', '' ) ) > 2 && strlen( MainWPBackWPUpView::get_value( MainWPBackWPUpView::$information['settings'], 'sugarsyncappid', '' ) ) > 2 ) : ?>
				<div class="ui info message">You are using custom API Key</div>
				<?php endif; ?>
				<h3 class="ui dividing header"><?php _e( 'Sugarsync Login', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Email Address', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="sugaremail" name="sugaremail" type="text"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'sugaremail', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Password', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="sugarpass" name="sugarpass" type="password"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'sugarpass', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'SugarSync Root', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Sync folder selection', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<span ng-bind-html="scope_sugar_folder_message"></span>
						<select ng-model="scope_sugar_folder" class="ui dropdown" ng-if="scope_sugar_folders"
										name="sugarfolderselected" id="s3bucket"
										ng-init="scope_sugar_folder='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'sugarfolderselected', '' ) ); ?>'">
							<option ng-selected="scope_sugar_folder==folder" value="{{ folder }}"
											ng-repeat="folder in scope_sugar_folders">{{ folder }}
							</option>
						</select>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Backup Settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Sugar token', 'sugarrefreshtoken' ); ?></label>
				  <div class="ten wide column">
						<input id="sugarrefreshtoken" name="sugarrefreshtoken" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'sugarrefreshtoken', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Folder in root', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idsugardir" name="sugardir" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'sugardir', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File Deletion', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<?php if ( $information['information']['backuptype'] == 'archive' ) : ?>

							<input id="idsugarmaxbackups" name="sugarmaxbackups"
							 type="text" size="3"
							 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'sugarmaxbackups', '' ) ); ?>"/>

							<?php
							else :
								?>

							<input class="checkbox" value="1"
							 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'sugarmaxbackups', '' ), 1 ); ?>
							 name="sugarmaxbackups"
							 id="idsugarmaxbackups"/> <label><?php _e( 'Do not delete files while syncing to destination!', $this->plugin_translate ); ?></label>

								<?php
							endif;
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
