<?php

class MainWPBackWPupDestinationEmail extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		$settings['emailaddress']      = ( isset( $_POST['emailaddress'] ) ? sanitize_email( $_POST['emailaddress'] ) : '' );
		$settings['emailefilesize']    = ( isset( $_POST['emailefilesize'] ) ? (int) $_POST['emailefilesize'] : 0 );
		$settings['emailsndemail']     = ( isset( $_POST['emailsndemail'] ) ? sanitize_email( $_POST['emailsndemail'] ) : '' );
		$settings['emailmethod']       = ( ( isset( $_POST['emailmethod'] ) && ( $_POST['emailmethod'] == '' || $_POST['emailmethod'] == 'mail' || $_POST['emailmethod'] == 'sendmail' || $_POST['emailmethod'] == 'smtp' ) ) ? $_POST['emailmethod'] : '' );
		$settings['emailsendmail']     = ( isset( $_POST['emailsendmail'] ) ? $_POST['emailsendmail'] : '' );
		$settings['emailsndemailname'] = ( isset( $_POST['emailsndemailname'] ) ? $_POST['emailsndemailname'] : '' );
		$settings['emailhost']         = ( isset( $_POST['emailhost'] ) ? $_POST['emailhost'] : '' );
		$settings['emailhostport']     = ( isset( $_POST['emailhostport'] ) ? intval( $_POST['emailhostport'] ) : 0 );
		$settings['emailsecure']       = ( isset( $_POST['emailsecure'] ) && ( $_POST['emailsecure'] == 'ssl' || $_POST['emailsecure'] == 'tls' ) ? $_POST['emailsecure'] : '' );
		$settings['emailuser']         = ( isset( $_POST['emailuser'] ) ? $_POST['emailuser'] : '' );
		$settings['emailpass']         = ( isset( $_POST['emailpass'] ) ? $_POST['emailpass'] : '' );

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
				<h3 class="ui dividing header"><?php _e( 'Email address', $this->plugin_translate ); ?></h3>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Email address', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input name="emailaddress" ng-model="destination_email_emailaddress" type="text"
									 title="<?php esc_attr_e( 'Email address to which Backups are sent.', $this->plugin_translate ); ?>"
									 ng-init="destination_email_emailaddress='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailaddress', '' ) ); ?>'"/>
					</div>
				</div>
				<?php if ( $this->our_id > 0 ) : ?>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Send test email', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<button id="sendemailtest" class="ui green mini button" type="button"
										ng-click="destination_email_test_email()"><?php _e( 'Send test email', $this->plugin_translate ); ?></button>
					</div>
				</div>
				<?php endif; ?>

				<h3 class="ui dividing header"><?php _e( 'Send email settings', $this->plugin_translate ); ?></h3>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Maximum file size', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input name="emailefilesize" ng-model="destination_email_emailefilesize" type="text"
											 ng-init="destination_email_emailefilesize='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailefilesize', '' ) ); ?>'"
											 class="small-text help-tip"
											 /><?php _e( 'MB', $this->plugin_translate ); ?>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Sender email address', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input name="emailsndemail" ng-model="destination_email_emailsndemail" type="text" id="emailsndemail" ng-init="destination_email_emailsndemail='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailsndemail', '' ) ); ?>'" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Sender name', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input name="emailsndemailname" ng-model="destination_email_emailsndemailname" type="text" id="emailsndemailname" ng-init="destination_email_emailsndemailname='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailsndemailname', '' ) ); ?>'" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Sending method', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<select ng-model="destination_email_emailmethod"
										ng-init="destination_email_emailmethod='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailmethod', '' ) ); ?>'"
										id="emailmethod" name="emailmethod" class="ui dropdown" >
							<?php
							echo '<option value="">' . __( 'Use site settings', $this->plugin_translate ) . '</option>';
							echo '<option value="mail">' . __( 'PHP: mail()', $this->plugin_translate ) . '</option>';
							echo '<option value="sendmail">' . __( 'Sendmail', $this->plugin_translate ) . '</option>';
							echo '<option value="smtp">' . __( 'SMTP', $this->plugin_translate ) . '</option>';
							?>
						</select>
					</div>
				</div>

				<div class="ui grid field" ng-show="destination_email_emailmethod=='sendmail'">
					<label class="six wide column middle aligned"><?php _e( 'Sendmail path', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input name="emailsendmail" ng-model="destination_email_emailsendmail" type="text"
									 ng-init="destination_email_emailsendmail='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailsendmail', '' ) ); ?>'"
									 class="regular-text code"/>
					</div>
				</div>

				<div class="ui grid field emailsmtp" ng-show="destination_email_emailmethod=='smtp'">
					<label class="six wide column middle aligned"><?php _e( 'SMTP host name', $this->plugin_translate ); ?></label>
				  <div class="eight wide column">
						<input name="emailhost" ng-model="destination_email_emailhost" type="text"
									 ng-init="destination_email_emailhost='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailhost', '' ) ); ?>'"
									 class="regular-text code"/>
					</div>
					<div class="one wide column middle aligned">
						Port
					</div>

					<div class="one wide column">
						<input
								name="emailhostport" ng-model="destination_email_emailhostport" type="text"
								ng-init="destination_email_emailhostport='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailhostport', '' ) ); ?>'" />
					</div>
				</div>

				<div class="ui grid field" ng-show="destination_email_emailmethod=='smtp'">
					<label class="six wide column middle aligned"><?php _e( 'SMTP secure connection', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<select name="emailsecure" ng-model="destination_email_emailsecure" class="ui dropdown"
										ng-value="<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailsecure', '' ) ); ?>">
							<option value=""><?php _e( 'none', $this->plugin_translate ); ?></option>
							<option value="ssl"><?php _e( 'SSL', $this->plugin_translate ); ?></option>
							<option value="tls"><?php _e( 'TLS', $this->plugin_translate ); ?></option>
						</select>
					</div>
				</div>

				<div class="ui grid field" ng-show="destination_email_emailmethod=='smtp'">
					<label class="six wide column middle aligned"><?php _e( 'SMTP username', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input name="emailuser" ng-model="destination_email_emailuser" type="text"
									 ng-init="destination_email_emailuser='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailuser', '' ) ); ?>'"
									 class="regular-text" autocomplete="off"/>
					</div>
				</div>

				<div class="ui grid field" ng-show="destination_email_emailmethod=='smtp'">
					<label class="six wide column middle aligned"><?php _e( 'SMTP password', $this->plugin_translate ); ?></label>
				  <div class="ten wide column">
						<input name="emailpass" ng-model="destination_email_emailpass" type="password"
									 ng-init="destination_email_emailpass='<?php echo esc_attr( MainWPBackWPUpView::get_value( $default, 'emailpass', '' ) ); ?>'"
									 class="regular-text" autocomplete="off"/>
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
