<?php

class MainWPBackWPupDestinationAzure extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		$settings['msazureaccname']   = ( isset( $_POST['msazureaccname'] ) ? $_POST['msazureaccname'] : '' );
		$settings['msazurekey']       = ( isset( $_POST['msazurekey'] ) ? $_POST['msazurekey'] : '' );
		$settings['msazurecontainer'] = ( isset( $_POST['msazurecontainer'] ) ? $_POST['msazurecontainer'] : '' );

		if ( isset( $_POST['msazuredir'] ) ) {
			$_POST['msazuredir'] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( stripslashes( $_POST['msazuredir'] ) ) ) ) );
			if ( substr( $_POST['msazuredir'], 0, 1 ) == '/' ) {
				$_POST['msazuredir'] = substr( $_POST['msazuredir'], 1 );
			}
			if ( $_POST['msazuredir'] == '/' ) {
				$_POST['msazuredir'] = '';
			}

			$settings['msazuredir'] = $_POST['msazuredir'];
		}

		$settings['msazuremaxbackups']   = ( isset( $_POST['msazuremaxbackups'] ) ? (int) $_POST['msazuremaxbackups'] : 0 );
		$settings['msazuresyncnodelete'] = ( ( isset( $_POST['msazuresyncnodelete'] ) && $_POST['msazuresyncnodelete'] == 1 ) ? true : false );

		$settings['newmsazurecontainer'] = ( isset( $_POST['newmsazurecontainer'] ) ? $_POST['newmsazurecontainer'] : '' );

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
				<h3 class="ui dividing header"><?php _e( 'MS Azure access keys', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Account name', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="msazureaccname" name="msazureaccname" type="text"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'msazureaccname', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Access key', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="msazurekey" name="msazurekey" type="password"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'msazurekey', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Blob Container', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Container selection', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<span ng-bind-html="scope_azure_bucket_message"></span>
						<select class="ui dropdown" ng-if="scope_azure_buckets" name="msazurecontainer" id="msazurecontainer"
										ng-init="scope_azure_bucket='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'msazurecontainer', '' ) ); ?>'">
							<option ng-selected="scope_azure_bucket==bucket" value="{{ bucket }}"
											value="{{ bucket }}" ng-repeat="bucket in scope_azure_buckets">{{ bucket
								}}
							</option>
						</select>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Backup Settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Folder in container', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="idmsazuredir" name="msazuredir" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'msazuredir', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File deletion', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<?php if ( $information['information']['backuptype'] == 'archive' ) : ?>

							<input id="idmsazuremaxbackups" name="msazuremaxbackups"
									 type="text" size="3"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'msazuremaxbackups', '' ) ); ?>" title="<?php esc_attr_e( 'Oldest files will be deleted first. 0 = no deletion', $this->plugin_translate ); ?>"/>

							<?php
							else :
								?>

							<input class="checkbox" value="1"
								 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'msazuresyncnodelete', '' ), 1 ); ?>
								 name="msazuresyncnodelete"
								 id="idmsazuresyncnodelete"/> <label><?php _e( 'Do not delete files while syncing to destination!', $this->plugin_translate ); ?></label>

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
