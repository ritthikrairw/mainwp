<?php

class MainWPBackWPupJobTable extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		$settings['dbcheckwponly'] = ( isset( $_POST['dbcheckwponly'] ) ? $_POST['dbcheckwponly'] : '' );
		$settings['dbcheckrepair'] = ( isset( $_POST['dbcheckrepair'] ) ? $_POST['dbcheckrepair'] : '' );

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
				<h3 class="ui dividing header"><?php _e( 'Settings for database check', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'WordPress tables only', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox" value="1" id="iddbcheckwponly"
									 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'dbcheckwponly', '' ), true ); ?>
									 name="dbcheckwponly"/> <?php _e( 'Check WordPress database tables only', $this->plugin_translate ); ?>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Repair', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input class="checkbox" value="1" id="iddbcheckrepair"
									 type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 'dbcheckrepair', '' ), true ); ?>
									 name="dbcheckrepair"/> <?php _e( 'Try to repair defect table', $this->plugin_translate ); ?>
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
