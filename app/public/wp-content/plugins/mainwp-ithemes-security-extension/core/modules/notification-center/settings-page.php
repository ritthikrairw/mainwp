<?php

class MainWP_ITSEC_Notification_Center_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {

	private $version = 1;

	/** @var MainWP_ITSEC_Notification_Center_Validator */
	private $validator;

	/** @var array */
	private $last_sent = array();

	public function __construct() {
		$this->id          = 'notification-center';
		$this->title       = __( 'Notification Center', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Manage and configure email notifications sent by iThemes Security related to various settings modules.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type        = 'recommended';
		$this->can_save    = true;

		$this->validator = MainWP_ITSEC_Modules::get_validator( 'notification-center' );

		if ( MainWP_ITSEC_Core::get_notification_center()->get_mail_errors() ) {
			$this->status = 'warning';
		}

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {
		wp_enqueue_style( 'mainwp-itsec-notification-center-admin', plugins_url( 'css/settings-page.css', __FILE__ ), array(), $this->version );
		wp_enqueue_script( 'mainwp-itsec-notification-center-admin', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), $this->version );
	}

	public function handle_ajax_request( $data ) {

		if ( empty( $data['method'] ) ) {
			return;
		}

		switch ( $data['method'] ) {
			case 'dismiss-mail-error':
				if ( ! empty( $data['mail_error'] ) ) {
					MainWP_ITSEC_Core::get_notification_center()->dismiss_mail_error( $data['mail_error'] );

					if ( ! MainWP_ITSEC_Core::get_notification_center()->get_mail_errors() ) {
						ITSEC_Response::set_response( array( 'status' => 'all-cleared' ) );
					}
				}

				break;
		}
	}

	/**
	 * @param ITSEC_Form $form
	 */
	protected function render_settings( $form ) {

		$this->last_sent = MainWP_ITSEC_Modules::get_setting( 'notification-center', 'last_sent' );

		$this->render_mail_errors();
		?>

		<table class="form-table itsec-settings-section">
			<tbody>
			<?php // do not add recipients here. ?>
			</tbody>
		</table>


		<?php

		$notifications = MainWP_ITSEC_Core::get_notification_center()->get_notifications();

		usort( $notifications, array( $this, 'sort_notifications' ) );

		$form->add_input_group( 'notifications' );
		foreach ( $notifications as $notification ) {
			$this->render_notification_setting( $form, $notification['slug'], $notification );
		}
		$form->remove_input_group();
	}

	protected function render_mail_errors() {
		$errors = MainWP_ITSEC_Core::get_notification_center()->get_mail_errors();

		if ( ! $errors ) {
			return;
		}

		?>
		<div class="itsec-notification-center-mail-errors-container">
			<?php
			foreach ( $errors as $id => $error ) :
				$strings = MainWP_ITSEC_Core::get_notification_center()->get_notification_strings( $error['notification'] );
				$error   = $error['error'];

				if ( is_wp_error( $error ) ) {
					$message = $error->get_error_message();
				} elseif ( is_array( $error ) && isset( $error['message'] ) && is_string( $error['message'] ) ) {
					$message = $error['message'];
				} else {
					$message = __( 'Unknown error encountered while sending.', 'l10n-mainwp-ithemes-security-extension' );
				}
				?>
				<div class="notice notice-alt notice-error below-h2 itsec-is-dismissible itsec-notification-center-mail-error" data-id="<?php echo esc_attr( $id ); ?>">
					<p><?php printf( esc_html__( 'Error while sending %1$s notification at %2$s: %3$s', 'l10n-mainwp-ithemes-security-extension' ), '<b>' . $strings['label'] . '</b>', '<b>' . MainWP_ITSEC_Lib::date_format_i18n_and_local_timezone( $error['time'] ) . '</b>', $message ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * @param ITSEC_Form $form
	 * @param string     $slug
	 * @param array      $config
	 */
	protected function render_notification_setting( $form, $slug, $config ) {
		$is_individual = MainWP_IThemes_Security::is_manage_site();

		$strings = MainWP_ITSEC_Core::get_notification_center()->get_notification_strings( $slug );

		$form->add_input_group( $slug );
		?>

		<div class="itsec-notification-center-notification-settings" id="itsec-notification-center-notification-settings--<?php echo esc_attr( $slug ); ?>">
			<h4><?php echo $strings['label']; ?></h4>
			<?php if ( ! empty( $strings['description'] ) ) : ?>
				<p class="description"><?php echo $strings['description']; ?></p>
			<?php endif; ?>

			<table class="form-table itsec-settings-section" id="itsec-notification-center-notification-<?php echo esc_attr( $slug ); ?>">

				<?php if ( ! empty( $config['optional'] ) ) : ?>

					<div class="ui grid field itsec-notification-center-enable-notification">
						<label class="six wide column middle aligned" for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-enabled" ><?php esc_html_e( 'Enabled', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
						<div class="ten wide column ui">
						<?php $form->add_checkbox( 'enabled', array( 'data-slug' => $slug ) ); ?>
						</div>
					</div>

				<?php endif; ?>

				<?php
				if ( ! empty( $config['subject_editable'] ) ) :
					$form->get_option( 'subject' ) ? '' : $form->set_option( 'subject', $strings['subject'] );
					?>
										
					<div class="ui grid field">
						<label class="six wide column middle aligned" for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-subject" ><?php esc_html_e( 'Subject', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
						<div class="ten wide column ui">
						<?php $form->add_text( 'subject' ); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php
				if ( ! empty( $config['message_editable'] ) ) :
					$form->get_option( 'message' ) ? '' : $form->set_option( 'message', $strings['message'] );
					?>

					<div class="ui grid field">
						<label class="six wide column middle aligned" for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-message" ><?php esc_html_e( 'Message', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
						<div class="ten wide column ui">
								<?php $form->add_textarea( 'message' ); ?>
									<p class="description">
										<?php echo wp_sprintf( esc_html__( 'You can use HTML in your message. Allowed HTML includes: %l.', 'l10n-mainwp-ithemes-security-extension' ), array_keys( $this->validator->get_allowed_html() ) ); ?>

										<?php if ( ! empty( $config['tags'] ) ) : ?>
												<?php printf( esc_html__( 'This notification supports email tags. Tags are formatted as follows %s.', 'l10n-mainwp-ithemes-security-extension' ), '<code>{{ $tag_name }}</code>' ); ?>
										<?php endif; ?>
									</p>

									<?php if ( ! empty( $config['tags'] ) ) : ?>
										<dl class="itsec-notification-center-tags">
											<?php foreach ( $strings['tags'] as $tag => $description ) : ?>
												<dt><?php echo esc_html( $tag ); ?></dt>
												<dd><?php echo $description; // Already escaped. ?></dd>
											<?php endforeach; ?>
										</dl>
									<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( is_array( $config['schedule'] ) ) : ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned" for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-schedule" ><?php esc_html_e( 'Schedule', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
						<div class="ten wide column ui">
								<?php $form->add_select( 'schedule', $this->validator->get_schedule_options( $config['schedule'] ) ); ?>
								<p class="description">
									<?php if ( empty( $this->last_sent[ $slug ] ) ) : ?>
										<?php esc_html_e( 'Not yet sent.', 'l10n-mainwp-ithemes-security-extension' ); ?>
									<?php else : ?>
										<?php printf( esc_html__( 'Last sent on %s', 'l10n-mainwp-ithemes-security-extension' ), MainWP_ITSEC_Lib::date_format_i18n_and_local_timezone( $this->last_sent[ $slug ] ) ); ?>
									<?php endif; ?>
								</p>
						</div>
					</div>
				<?php endif; ?>
			<?php if ( $is_individual ) { ?>
				<?php
				switch ( $config['recipient'] ) :
					case MainWP_ITSEC_Notification_Center::R_USER:
						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Recipient', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
							<div class="ten wide column ui">
								<em><?php esc_html_e( 'Site Users', 'l10n-mainwp-ithemes-security-extension' ); ?></em>
							</div>
						</div>
					<?php break; ?>

					<?php
					case MainWP_ITSEC_Notification_Center::R_ADMIN:
						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Recipient', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
							<div class="ten wide column ui">
								<em><?php esc_html_e( 'Admin Emails', 'l10n-mainwp-ithemes-security-extension' ); ?></em>
							</div>
						</div>
					<?php break; ?>

					<?php
					case MainWP_ITSEC_Notification_Center::R_PER_USE:
						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Recipient', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
							<div class="ten wide column ui">
								<em><?php esc_html_e( 'Specified when sending', 'l10n-mainwp-ithemes-security-extension' ); ?></em>
							</div>
						</div>
					<?php break; ?>

					<?php
					case MainWP_ITSEC_Notification_Center::R_EMAIL_LIST:
						?>
						<div class="ui grid field">
							<label class="six wide column middle aligned" for="itsec-notification-center-notifications-<?php echo esc_attr( $slug ); ?>-email_list"><?php esc_html_e( 'Recipient', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
							<div class="ten wide column ui">
							<?php $form->add_textarea( 'email_list', array( 'class' => 'textarea-small' ) ); ?>
								<p class="description"><?php _e( 'The email address(es) this notification will be sent to. One address per line.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
							</div>
						</div>
						<?php break; ?>

					<?php
					case MainWP_ITSEC_Notification_Center::R_USER_LIST:
					case MainWP_ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE:
						?>
							<?php $this->render_user_list( $slug, $form, $config['recipient'] ); ?>
					<?php break; ?>

				<?php endswitch; ?>     
			<?php } ?>
			</table>
		</div>
		<?php
		$form->remove_input_group();
	}

	/**
	 * Render the User List form.
	 *
	 * @param string     $slug Notification slug.
	 * @param ITSEC_Form $form
	 * @param string     $type
	 */
	protected function render_user_list( $slug, $form, $type ) {

		$users         = $roles = array();
		$is_individual = MainWP_IThemes_Security::is_manage_site();

		if ( $is_individual ) {
			global $mainwp_itheme_site_data_values;
			$users_and_roles = isset( $mainwp_itheme_site_data_values['users_and_roles'] ) && is_array( $mainwp_itheme_site_data_values['users_and_roles'] ) ? $mainwp_itheme_site_data_values['users_and_roles'] : array();
			$users           = isset( $users_and_roles['users'] ) ? $users_and_roles['users'] : array();
			$roles           = isset( $users_and_roles['roles'] ) ? $users_and_roles['roles'] : array();
		}

		natcasesort( $users );

		?>

		<div class="ui grid field itsec-email-contacts-setting">
			<label class="six wide column middle aligned"><?php esc_html_e( 'Recipient', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
			<div class="ten wide column ui">
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Recipients for this email.', 'l10n-mainwp-ithemes-security-extension' ); ?></legend>
					<p><?php esc_html_e( 'Select which users should be emailed.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>

					<ul>
						<?php foreach ( $roles as $role => $name ) : ?>
							<li>
								<label>
									<?php $form->add_multi_checkbox( 'user_list', $role ); ?>
									<?php echo esc_html( sprintf( _x( 'All %s users', 'role', 'l10n-mainwp-ithemes-security-extension' ), $name ) ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>

					<ul>
						<?php foreach ( $users as $id => $name ) : ?>
							<li>
								<label>
									<?php $form->add_multi_checkbox( 'user_list', $id ); ?>
									<?php echo esc_html( $name ); ?>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>

					<?php if ( MainWP_ITSEC_Notification_Center::R_USER_LIST_ADMIN_UPGRADE === $type && $form->get_option( 'previous_emails' ) ) : ?>

						<div class="itsec-notification-center--deprecated-recipients">
							<span><?php esc_html_e( 'Deprecated Recipients', 'l10n-mainwp-ithemes-security-extension' ); ?></span>
							<p class="description">
								<?php esc_html_e( 'The following email recipients are deprecated. Please create new users for these email addresses or remove them.', 'l10n-mainwp-ithemes-security-extension' ); ?>
							</p>
							<ul>
								<?php foreach ( $form->get_option( 'previous_emails' ) as $email ) : ?>
									<li>
										<label>
											<?php $form->add_multi_checkbox( 'previous_emails', $email ); ?>
											<?php echo esc_html( $email ); ?>
										</label>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</fieldset>
			</div>
		</div>
		<?php
	}

	private function sort_notifications( $a, $b ) {

		$a_s = MainWP_ITSEC_Core::get_notification_center()->get_notification_strings( $a['slug'] );
		$b_s = MainWP_ITSEC_Core::get_notification_center()->get_notification_strings( $b['slug'] );

		if ( $a_s['order'] == $b_s['order'] ) {
			return 0;
		}
		
		return ( $a_s['order'] > $b_s['order'] ) ? true : false;
	}
}

new MainWP_ITSEC_Notification_Center_Settings_Page();
