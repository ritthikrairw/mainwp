<?php

class MainWPBackWPupDestinationGlacier extends MainWPBackWPupJob {
	public $is_pro_extension = true;

	public function save_form( $settings ) {
		$settings['glacieraccesskey']  = ( isset( $_POST['glacieraccesskey'] ) ? $_POST['glacieraccesskey'] : '' );
		$settings['glaciersecretkey']  = ( isset( $_POST['glaciersecretkey'] ) ? $_POST['glaciersecretkey'] : '' );
		$settings['glacierregion']     = ( isset( $_POST['glacierregion'] ) ? $_POST['glacierregion'] : '' );
		$settings['glaciervault']      = ( isset( $_POST['glaciervault'] ) ? $_POST['glaciervault'] : '' );
		$settings['glaciermaxbackups'] = ( isset( $_POST['glaciermaxbackups'] ) ? (int) $_POST['glaciermaxbackups'] : 0 );

		return $settings;
	}

	public function render_form( $information ) {
		$default = $information['default'];
		?>
		<div ng-show="is_selected_2('<?php echo $this->tab_name; ?>')">
			<form action="<?php echo esc_attr( $this->current_page ); ?>" method="post">
				<input type="hidden" name="our_id" value="<?php echo esc_attr( $this->our_id ); ?>">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>">
				<input type="hidden" name="website_id" value="<?php echo esc_attr( $this->website_id ); ?>">
				<input type="hidden" name="job_tab" value="<?php echo esc_attr( $this->original_tab_name ); ?>">
				<?php wp_nonce_field( MainWPBackWPupExtension::$nonce_token . 'update_jobs' ); ?>
				<div class="postbox">
					<h3 class="mainwp_box_title"><span><i
								class="fa fa-hdd-o"></i> <?php _e( 'To: Amazon Glacier', $this->plugin_translate ); ?></span>
					</h3>

					<div class="inside">
						<h3 class="title"><?php _e( 'Amazon Glacier', $this->plugin_translate ); ?></h3>

						<p></p>
						<table class="form-table">
							<tr>
								<th scope="row"><label
										for="glacierregion"><?php _e( 'Select a region:', $this->plugin_translate ); ?></label>
								</th>
								<td>
									<select name="glacierregion" id="glacierregion"
											title="<?php _e( 'Amazon Glacier Region', $this->plugin_translate ); ?>">
										<option
											value="us-east-1" <?php selected( 'us-east-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'US Standard', $this->plugin_translate ); ?></option>
										<option
											value="us-west-1" <?php selected( 'us-west-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'US West (Northern California)', $this->plugin_translate ); ?></option>
										<option
											value="us-west-2" <?php selected( 'us-west-2', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'US West (Oregon)', $this->plugin_translate ); ?></option>
										<option
											value="eu-west-1" <?php selected( 'eu-west-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'EU (Ireland)', $this->plugin_translate ); ?></option>
										<option
											value="eu-central-1" <?php selected( 'eu-central-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'EU (Germany)', $this->plugin_translate ); ?></option>
										<option
											value="ap-northeast-1" <?php selected( 'ap-northeast-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'Asia Pacific (Tokyo)', $this->plugin_translate ); ?></option>
										<option
											value="ap-southeast-1" <?php selected( 'ap-southeast-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'Asia Pacific (Singapore)', $this->plugin_translate ); ?></option>
										<option
											value="ap-southeast-2" <?php selected( 'ap-southeast-2', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'Asia Pacific (Sydney)', $this->plugin_translate ); ?></option>
										<option
											value="sa-east-1" <?php selected( 'sa-east-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'South America (Sao Paulo)', $this->plugin_translate ); ?></option>
										<option
											value="cn-north-1" <?php selected( 'cn-north-1', MainWPBackWPUpView::get_value( $default, 'glacierregion', false ), true ); ?>><?php _e( 'China (Beijing)', $this->plugin_translate ); ?></option>
									</select>
								</td>
							</tr>
						</table>

						<h3 class="title"><?php _e( 'Amazon Access Keys', $this->plugin_translate ); ?></h3>

						<p></p>
						<table class="form-table">
							<tr>
								<th scope="row"><label
										for="glacieraccesskey"><?php _e( 'Access Key', $this->plugin_translate ); ?></label>
								</th>
								<td>
									<input id="glacieraccesskey" name="glacieraccesskey" type="text"
										   value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'glacieraccesskey', '' ) ); ?>"
										   class="regular-text" autocomplete="off"/>
								</td>
							</tr>
							<tr>
								<th scope="row"><label
										for="glaciersecretkey"><?php _e( 'Secret Key', $this->plugin_translate ); ?></label>
								</th>
								<td>
									<input id="glaciersecretkey" name="glaciersecretkey" type="password"
										   value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'glaciersecretkey', '' ) ); ?>"
										   class="regular-text" autocomplete="off"/>
								</td>
							</tr>
						</table>

						<h3 class="title"><?php _e( 'Vault', $this->plugin_translate ); ?></h3>

						<p></p>
						<table class="form-table">
							<tr>
								<th scope="row"><label
										for="vaultselected"><?php _e( 'Vault selection', $this->plugin_translate ); ?></label>
								</th>
								<td>
									<span ng-bind-html="scope_glacier_bucket_message"></span>
									<select ng-if="scope_glacier_buckets" name="glaciervault" id="glaciervault"
											ng-init="scope_glacier_bucket='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'glaciervault', '' ) ); ?>'">
										<option ng-selected="scope_glacier_bucket==bucket" value="{{ bucket }}"
												ng-repeat="bucket in scope_glacier_buckets">{{ bucket }}
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><label
										for="newvault"><?php _e( 'Create a new vault', $this->plugin_translate ); ?></label>
								</th>
								<td>
									<input id="newvault" name="newvault" type="text" value="" class="small-text"
										   autocomplete="off"/>
								</td>
							</tr>
						</table>

						<h3 class="title"><?php _e( 'Glacier Backup settings', $this->plugin_translate ); ?></h3>

						<p></p>
						<table class="form-table">
							<tr>
								<th scope="row"><?php _e( 'File deletion', $this->plugin_translate ); ?></th>
								<td>
									<label for="glaciermaxbackups"><input id="glaciermaxbackups"
																		  name="glaciermaxbackups" type="text" size="3"
																		  value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'glaciermaxbackups', '' ) ); ?>"
																		  class="small-text help-tip"
																		  title="<?php esc_attr_e( 'Oldest files will be deleted first. 0 = no deletion', $this->plugin_translate ); ?>"/>&nbsp;
										<?php _e( 'Number of files to keep in folder. (Archives deleted before 3 months after they have been stored may cause extra costs when deleted.)', $this->plugin_translate ); ?>
									</label>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button-primary"
						   value="<?php _e( 'Save Changes', $this->plugin_translate ); ?>"/>
				</p>
			</form>
		</div>
		<?php
	}
}
