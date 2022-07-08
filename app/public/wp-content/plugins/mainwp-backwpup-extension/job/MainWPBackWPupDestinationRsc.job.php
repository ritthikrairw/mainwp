<?php

class MainWPBackWPupDestinationRsc extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		$settings['rscusername']  = ( isset( $_POST['rscusername'] ) ? $_POST['rscusername'] : '' );
		$settings['rscapikey']    = ( isset( $_POST['rscapikey'] ) ? $_POST['rscapikey'] : '' );
		$settings['rsccontainer'] = ( isset( $_POST['rsccontainer'] ) ? $_POST['rsccontainer'] : '' );
		$settings['rscregion']    = ( ! empty( $_POST['rscregion'] ) ? $_POST['rscregion'] : 'DFW' );

		if ( isset( $_POST['rscdir'] ) ) {
			$settings['rscdir'] = trim( stripslashes( $_POST['rscdir'] ) );
		}

		$settings['rscmaxbackups']   = ( isset( $_POST['rscmaxbackups'] ) ? (int) $_POST['rscmaxbackups'] : 0 );
		$settings['rscsyncnodelete'] = ( ( isset( $_POST['rscsyncnodelete'] ) && $_POST['rscsyncnodelete'] == 1 ) ? true : false );

		$settings['newrsccontainer'] = ( isset( $_POST['newrsccontainer'] ) ? $_POST['newrsccontainer'] : '' );

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
				<h3 class="ui dividing header"><?php _e( 'Rack Space Cloud Keys', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Username', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="rscusername" name="rscusername" type="text"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'rscusername', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'API Key', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="rscapikey" name="rscapikey" type="password"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'rscapikey', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Select region', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Rackspace Cloud Files Region', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<select name="rscregion" id="rscregion" class="ui dropdown">
							<option
								value="DFW" <?php selected( 'DFW', MainWPBackWPUpView::get_value( $default, 'rscregion', false ), true ) ?>><?php _e( 'Dallas (DFW)', $this->plugin_translate ); ?></option>
							<option
								value="ORD" <?php selected( 'ORD', MainWPBackWPUpView::get_value( $default, 'rscregion', false ), true ) ?>><?php _e( 'Chicago (ORD)', $this->plugin_translate ); ?></option>
							<option
								value="SYD" <?php selected( 'SYD', MainWPBackWPUpView::get_value( $default, 'rscregion', false ), true ) ?>><?php _e( 'Sydney (SYD)', $this->plugin_translate ); ?></option>
							<option
								value="LON" <?php selected( 'LON', MainWPBackWPUpView::get_value( $default, 'rscregion', false ), true ) ?>><?php _e( 'London (LON)', $this->plugin_translate ); ?></option>
							<option
								value="IAD" <?php selected( 'IAD', MainWPBackWPUpView::get_value( $default, 'rscregion', false ), true ) ?>><?php _e( 'Northern Virginia (IAD)', $this->plugin_translate ); ?></option>
							<option
								value="HKG" <?php selected( 'HKG', MainWPBackWPUpView::get_value( $default, 'rscregion', false ), true ) ?>><?php _e( 'Hong Kong (HKG)', $this->plugin_translate ); ?></option>
						</select>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Container selection', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<span class="ui dropdown" ng-bind-html="scope_rsc_bucket_message"></span>
						<select ng-if="scope_rsc_buckets" name="rsccontainer" id="rsccontainer"
										ng-init="scope_rsc_bucket='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'rsccontainer', '' ) ); ?>'">
							<option ng-selected="scope_rsc_bucket==bucket" value="{{ bucket }}"
											ng-repeat="bucket in scope_rsc_buckets">{{ bucket }}
							</option>
						</select>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Create a new container', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idnewrsccontainer" name="newrsccontainer" type="text" value=""  class="text"/>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Backup Settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Folder in bucket', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idrscdir" name="rscdir" type="text"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'rscdir', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File deletion', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<?php if ($information['information']['backuptype'] == 'archive'): ?>

							<input id="idrscmaxbackups" name="rscmaxbackups"
							 type="text" size="3"
							 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'rscmaxbackups', '' ) ); ?>"/>

							<?php
							else:
							?>

							<input class="checkbox" value="1"
							 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'rscmaxbackups', '' ), 1 ); ?>
							 name="rscmaxbackups"
							 id="idrscmaxbackups"/> <label><?php _e( 'Do not delete files while syncing to destination!', $this->plugin_translate ); ?></label>

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
