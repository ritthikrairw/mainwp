<?php

class MainWPBackWPupJobXml extends MainWPBackWPupJob {

	public function save_form( $settings ) {

		$settings['wpexportcontent'] = ( isset( $_POST['wpexportcontent'] ) ? $_POST['wpexportcontent'] : '' );
		$settings['wpexportfile']    = ( isset( $_POST['wpexportfile'] ) ? $this->sanitize_file_name( $_POST['wpexportfile'] ) : '' );

		if ( isset( $_POST['wpexportfilecompression'] ) && ( $_POST['wpexportfilecompression'] == '' || $_POST['wpexportfilecompression'] == '.gz' || $_POST['wpexportfilecompression'] == '.bz2' ) ) {
			$settings['wpexportfilecompression'] = $_POST['wpexportfilecompression'];
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

				<h3 class="ui dividing header"><?php _e( 'XML export', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Items to export', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui list">
						<div class="item ui radio checkbox"><input type="radio" name="wpexportcontent" id="idwpexportcontent-all" value="all" <?php checked( MainWPBackWPUpView::get_value( $default, 'wpexportcontent', '' ), 'all' ); ?> /> <label for="idwpexportcontent-all"><?php _e( 'All content', $this->plugin_translate ); ?></label></div>
						<div class="item ui radio checkbox"><input type="radio" name="wpexportcontent" id="idwpexportcontent-post" value="post" <?php checked( MainWPBackWPUpView::get_value( $default, 'wpexportcontent', '' ), 'post' ); ?> /> <label for="idwpexportcontent-post"><?php _e( 'Posts', $this->plugin_translate ); ?></label></div>
						<div class="item ui radio checkbox"><input type="radio" name="wpexportcontent" id="idwpexportcontent-page" value="page" <?php checked( MainWPBackWPUpView::get_value( $default, 'wpexportcontent', '' ), 'page' ); ?> /> <label for="idwpexportcontent-page"><?php _e( 'Pages', $this->plugin_translate ); ?></label></div>
						<?php
						foreach (
							get_post_types(
								array(
									'_builtin'   => false,
									'can_export' => true,
								),
								'objects'
							) as $post_type
						) {
							?>
							<div class="ui radio checkbox"><input type="radio" name="wpexportcontent" id="idwpexportcontent-<?php echo esc_attr( $post_type->name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( MainWPBackWPUpView::get_value( $default, 'wpexportcontent', '' ), esc_attr( $post_type->name ) ); ?> /> <label for="idwpexportcontent-<?php echo esc_attr( $post_type->name ); ?>"><?php echo esc_html( $post_type->label ); ?></label></div>
						<?php } ?>

					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'XML Export file name', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<div class="ui right labeled input">

						<input name="wpexportfile" type="text" id="idwpexportfile"
									 value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'wpexportfile', '' ) ); ?>"
									 class="medium-text code"/>
									<div class="ui label">
										.xml
									</div></div>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File compression', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui list">
						<?php
						echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '', MainWPBackWPUpView::get_value( $default, 'wpexportfilecompression', '' ), false ) . ' name="wpexportfilecompression" id="idwpexportfilecompression" value="" /> <label for="idwpexportfilecompression">' . __( 'none', $this->plugin_translate ) . '</label></div>';

						echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '.gz', MainWPBackWPUpView::get_value( $default, 'wpexportfilecompression', '' ), false ) . ' name="wpexportfilecompression" id="idwpexportfilecompression-gz" value=".gz" /> <label for="idwpexportfilecompression-gz">' . __( 'GZip', $this->plugin_translate ) . '</label></div>';

						echo '<div class="item ui radio checkbox"><input class="radio" type="radio"' . checked( '.bz2', MainWPBackWPUpView::get_value( $default, 'wpexportfilecompression', '' ), false ) . ' name="wpexportfilecompression" id="idwpexportfilecompression-bz2" value=".bz2" /> <label for="idwpexportfilecompression-bz2">' . __( 'BZip2', $this->plugin_translate ) . '</label></div>';
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
