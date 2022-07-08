<?php

class MainWPBackWPupJobPlugin extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		$settings['pluginlistfile'] = ( isset( $_POST['pluginlistfile'] ) ? $this->sanitize_file_name( $_POST['pluginlistfile'] ) : '' );
		if ( isset( $_POST['pluginlistfilecompression'] ) && ( $_POST['pluginlistfilecompression'] == '' || $_POST['pluginlistfilecompression'] == '.gz' || $_POST['pluginlistfilecompression'] == '.bz2' ) ) {
			$settings['pluginlistfilecompression'] = $_POST['pluginlistfilecompression'];
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

				<h3 class="ui dividing header"><?php _e( 'Plugins', $this->plugin_translate ); ?></h3>


				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Plugin list file name', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<div class="ui right labeled input">

							<input name="pluginlistfile" type="text" id="idpluginlistfile"
										 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'pluginlistfile', '' ) ); ?>"
										 />
									<div class="ui label">
										.txt
									</div></div>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File compression', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui list">
						<?php
						echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '', MainWPBackWPUpView::get_value( $default, 'pluginlistfilecompression', '' ), false ) . ' name="pluginlistfilecompression" id="pluginlistfilecompression" value="" /> <label for="pluginlistfilecompression">' . __( 'none', $this->plugin_translate ) . '</label></div>';

						echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '.gz', MainWPBackWPUpView::get_value( $default, 'pluginlistfilecompression', '' ), false ) . ' name="pluginlistfilecompression" id="pluginlistfilecompression-gz" value=".gz" /> <label for="pluginlistfilecompression-gz">' . __( 'GZip', $this->plugin_translate ) . '</label></div>';

						echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '.bz2', MainWPBackWPUpView::get_value( $default, 'pluginlistfilecompression', '' ), false ) . ' name="pluginlistfilecompression" id="pluginlistfilecompression-bz2" value=".bz2" /> <label for="pluginlistfilecompression-bz2">' . __( 'BZip2', $this->plugin_translate ) . '</label></div>';
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
