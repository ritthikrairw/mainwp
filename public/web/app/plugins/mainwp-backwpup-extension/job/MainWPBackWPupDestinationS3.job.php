<?php

class MainWPBackWPupDestinationS3 extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		$settings['s3accesskey']    = ( isset( $_POST['s3accesskey'] ) ? $_POST['s3accesskey'] : '' );
		$settings['s3secretkey']    = ( isset( $_POST['s3secretkey'] ) ? $_POST['s3secretkey'] : '' );
		$settings['s3base_url']     = ( isset( $_POST['s3base_url'] ) ? esc_url_raw( $_POST['s3base_url'] ) : '' );
		$settings['s3region']       = ( isset( $_POST['s3region'] ) ? $_POST['s3region'] : '' );
		$settings['s3storageclass'] = ( isset( $_POST['s3storageclass'] ) ? $_POST['s3storageclass'] : '' );
		$settings['s3ssencrypt']    = ( ( isset( $_POST['s3ssencrypt'] ) && $_POST['s3ssencrypt'] == 'AES256' ) ? 'AES256' : '' );
		$settings['s3bucket']       = ( isset( $_POST['s3bucket'] ) ? $_POST['s3bucket'] : '' );

		if ( isset( $_POST['s3dir'] ) ) {
			$settings['s3dir'] = trim( stripslashes( $_POST['s3dir'] ) );
		}

		$settings['s3maxbackups']   = ( isset( $_POST['s3maxbackups'] ) ? (int) $_POST['s3maxbackups'] : 0 );
		$settings['s3syncnodelete'] = ( ( isset( $_POST['s3syncnodelete'] ) ) ? $_POST['s3syncnodelete'] : '' );
		$settings['s3multipart']    = ( ( isset( $_POST['s3multipart'] ) && $_POST['s3multipart'] == 1 ) ? true : false );

		$settings['s3newbucket'] = ( ( isset( $_POST['s3newbucket'] ) ) ? $_POST['s3newbucket'] : '' );

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
				
				<h3 class="ui dividing header"><?php _e( 'S3 Service', $this->plugin_translate ) ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Select a S3 service', $this->plugin_translate ) ?></label>
				  <div class="ten wide column">
						<select name="s3region" id="s3region" class="ui dropdown">
							<option value="us-east-1" <?php selected( 'us-east-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: US Standard', $this->plugin_translate ); ?></option>
							<option value="us-west-1" <?php selected( 'us-west-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: US West (Northern California)', $this->plugin_translate ); ?></option>
							<option value="us-west-2" <?php selected( 'us-west-2', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: US West (Oregon)', $this->plugin_translate ); ?></option>
							<option value="eu-west-1" <?php selected( 'eu-west-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: EU (Ireland)', $this->plugin_translate ); ?></option>
							<option value="eu-central-1" <?php selected( 'eu-central-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: EU (Germany)', $this->plugin_translate ); ?></option>
							<option value="ap-northeast-1" <?php selected( 'ap-northeast-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: Asia Pacific (Tokyo)', $this->plugin_translate ); ?></option>
							<option value="ap-southeast-1" <?php selected( 'ap-southeast-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: Asia Pacific (Singapore)', $this->plugin_translate ); ?></option>
							<option value="ap-southeast-2" <?php selected( 'ap-southeast-2', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: Asia Pacific (Sydney)', $this->plugin_translate ); ?></option>
							<option value="sa-east-1" <?php selected( 'sa-east-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: South America (Sao Paulo)', $this->plugin_translate ); ?></option>
							<option value="cn-north-1" <?php selected( 'cn-north-1', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Amazon S3: China (Beijing)', $this->plugin_translate ); ?></option>
							<option value="google-storage" <?php selected( 'google-storage', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Google Storage (Interoperable Access)', $this->plugin_translate ); ?></option>
							<option value="dreamhost" <?php selected( 'dreamhost', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'Dream Host Cloud Storage', $this->plugin_translate ); ?></option>
							<option value="greenqloud" <?php selected( 'greenqloud', MainWPBackWPUpView::get_value( $default, 's3region', false ), true ) ?>><?php _e( 'GreenQloud Storage Qloud', $this->plugin_translate ); ?></option>
						</select>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Or a S3 Server URL', $this->plugin_translate ) ?></label>
				  <div class="ten wide column">
						<input id="s3base_url" name="s3base_url" type="text" title="<?php esc_attr_e( 'Leave it empty to use a destination from S3 service list.', $this->plugin_translate ); ?>" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3base_url', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Region', $this->plugin_translate ) ?></label>
				  <div class="ten wide column">
						<input id="s3base_region" name="s3base_region" title="<?php esc_attr_e( 'Specify S3 region like "us-west-1"', $this->plugin_translate ); ?>" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3base_region', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Multipart', $this->plugin_translate ); ?></label>
					<div class="ten wide column ui toggle checkbox">
						<input class="checkbox help-tip" value="1" title="<?php esc_attr_e( 'Destination supports multipart.', $this->plugin_translate ); ?>" type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 's3base_multipart', false ), true ); ?> name="s3base_multipart" id="s3base_multipart"/> <label><?php _e( 'Destination supports multipart', $this->plugin_translate ); ?></label>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Pathstyle-Only Bucket', $this->plugin_translate ); ?></label>
					<div class="ten wide column ui toggle checkbox">
						<input class="checkbox help-tip" value="1" title="<?php esc_attr_e( 'Example: http://s3.example.com/bucket-name', $this->plugin_translate ); ?>" type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 's3base_pathstylebucket', false ), true ); ?> name="s3base_pathstylebucket" id="s3base_pathstylebucket"/> <label><?php _e( 'Destination provides only Pathstyle buckets', $this->plugin_translate ); ?></label>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Version', $this->plugin_translate ) ?></label>
				  <div class="ten wide column">
						<input id="s3base_version" name="s3base_version" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3base_version', 'latest' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Signature', $this->plugin_translate ) ?></label>
				  <div class="ten wide column">
						<input id="s3base_signature" name="s3base_signature" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3base_signature', 'v4' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'S3 Access Keys', $this->plugin_translate ) ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Access Key', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="s3accesskey" name="s3accesskey" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3accesskey', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Secret Key', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input id="s3secretkey" name="s3secretkey" type="password" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3secretkey', '' ) ); ?>" autocomplete="off"/>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'S3 Bucket', $this->plugin_translate ) ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Bucket selection', $this->plugin_translate ); ?></label>
					<div class="ten wide column">
						<select class="ui dropdown" ng-if="scope_s3_buckets" name="s3bucket" id="s3bucket" ng-init="scope_s3_bucket='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3bucket', '' ) ); ?>'">
							<option ng-selected="scope_s3_bucket==bucket" value="{{ bucket }}" ng-repeat="bucket in scope_s3_buckets">{{ bucket }}</option>
						</select>
						<span ng-bind-html="scope_s3_bucket_message"></span>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Create a new bucket', $this->plugin_translate ); ?></label>
					<div class="ten wide column">
						<input id="s3newbucket" name="s3newbucket" type="text" value="" autocomplete="off"/>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'S3 Bucket Settings', $this->plugin_translate ) ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Folder in bucket', $this->plugin_translate ); ?></label>
					<div class="ten wide column">
						<input id="ids3dir" name="s3dir" type="text" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3dir', '' ) ); ?>" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'File deletion', $this->plugin_translate ); ?></label>
					<div class="ten wide column">
						<?php if ($information['information']['backuptype'] == 'archive'): ?>
							<input id="ids3maxbackups" name="s3maxbackups" type="text" size="3" value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 's3maxbackups', '' ) ); ?>" class="small-text help-tip" title="<?php esc_attr_e( 'Oldest files will be deleted first. 0 = no deletion', $this->plugin_translate ); ?>"/>
						<?php else: ?>
							<input class="checkbox" value="1" type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 's3syncnodelete', '' ), 1 ); ?> name="s3syncnodelete" id="ids3syncnodelete"/> <label><?php _e( 'Do not delete files while syncing to destination!', $this->plugin_translate ); ?></label>
						<?php endif; ?>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Multipart Upload', $this->plugin_translate ); ?></label>
					<div class="ten wide column ui toggle checkbox">
						<input class="checkbox help-tip" value="1" title="<?php esc_attr_e( 'Multipart splits file into multiple chunks while uploading. This is necessary for displaying the upload process and to transfer bigger files. Works without a problem on Amazon. Other services might have issues.', $this->plugin_translate ); ?>" type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 's3multipart', false ), true ); ?> name="s3multipart" id="ids3multipart"/> <label><?php _e( 'Use multipart upload for uploading a file', $this->plugin_translate ); ?></label>
					</div>
				</div>

				<h3 class="ui dividing header"><?php _e( 'Amazon specific settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Amazon: Storage Class', $this->plugin_translate ); ?></label>
					<div class="ten wide column">
						<select name="s3storageclass" id="ids3storageclass" class="ui dropdown">
							<option value="" <?php selected( 'us-east-1', MainWPBackWPUpView::get_value( $default, 's3storageclass', false ), true ) ?>><?php _e( 'none', $this->plugin_translate ); ?></option>
							<option value="STANDARD_IA" <?php selected( 'STANDARD_IA', MainWPBackWPUpView::get_value( $default, 's3storageclass' ), TRUE ) ?>><?php esc_html_e( 'Standard-Infrequent Access', $this->plugin_translate ); ?></option>
							<option value="REDUCED_REDUNDANCY" <?php selected( 'REDUCED_REDUNDANCY', MainWPBackWPUpView::get_value( $default, 's3storageclass', false ), true ) ?>><?php _e( 'Reduced Redundancy', $this->plugin_translate ); ?></option>
						</select>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Server side encryption', $this->plugin_translate ); ?></label>
					<div class="ten wide column ui toggle checkbox">
						<input class="checkbox" value="AES256" type="checkbox" <?php checked( MainWPBackWPUpView::get_value( $default, 's3ssencrypt', false ), 'AES256' ); ?> name="s3ssencrypt" id="ids3ssencrypt"/> <label><?php _e( 'Save files encrypted (AES256) on server.', $this->plugin_translate ); ?></label>
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
