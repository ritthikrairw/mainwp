<?php

final class MainWP_ITSEC_Hide_Backend_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	public function __construct() {
		$this->id = 'hide-backend';
		$this->title = __( 'Hide Backend', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Hide the login page by changing its name and preventing access to wp-login.php and wp-admin.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'advanced';
		
		parent::__construct();
	}
		
	protected function render_settings( $form ) {
		global $mainwp_itheme_site_data_values;
		$permalink_structure = $users_can_register = true;
		$is_multisite = false;		
		$settings = $form->get_options();
		
		if ( empty( $permalink_structure ) && ! $is_multisite ) {
			echo '<div class="ui yello message">';
			echo __( 'You must change <strong>WordPress permalinks</strong> to a setting other than "Plain" in order to use this feature.', 'l10n-mainwp-ithemes-security-extension' );
			echo "</div>\n";
			
			return;
		}
		
?>
	<div class="itsec-write-files-disabled">
		<div class="ui green message"><?php _e( 'The "Write to Files" setting is disabled in Global Settings. In order to use this feature, you must enable the "Write to Files" setting.', 'l10n-mainwp-ithemes-security-extension' ); ?></div>
	</div>
	<br/>

	<div class="itsec-write-files-enabled">

		<div class="ui grid field">
			<label class="six wide column middle aligned" for="itsec-hide-backend-enabled"><?php _e( 'Enable the hide backend feature', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column ui">
				<?php $form->add_checkbox( 'enabled', array( 'class' => 'itsec-settings-toggle' ) ); ?>
			</div>
		</div>

		<div class="itsec-hide-backend-enabled-content">
			<div class="ui grid field">
				<label class="six wide column middle aligned" for="itsec-hide-backend-slug"><?php _e( 'Login Slug', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ten wide column ui">
					<?php $form->add_text( 'slug', array( 'class' => 'text code' ) ); ?>
					<br />
					<label for="itsec-hide-backend-slug"><?php printf( __( 'Login URL: %s', 'l10n-mainwp-ithemes-security-extension' ), 'http://www.yourchildsite.com/<span style="color: #4AA02C">' . sanitize_title( $settings['slug'] ) . '</span>' ); ?></label>
					<p class="description"><?php _e( 'The login url slug cannot be "login," "admin," "dashboard," or "wp-login.php" as these are use by default in WordPress.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
					<p class="description"><em><?php _e( 'Note: The output is limited to alphanumeric characters, underscore (_) and dash (-). Special characters such as "." and "/" are not allowed and will be converted in the same manner as a post title. Please review your selection before logging out.', 'l10n-mainwp-ithemes-security-extension' ); ?></em></p>
				</div>
			</div>		
			<?php if ( $users_can_register ) : ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned" for="itsec-hide-backend-register"><?php _e( 'Register Slug', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ten wide column ui">
					<?php $form->add_text( 'register', array( 'class' => 'text code' ) ); ?>
					<br />
					<label for="itsec-hide-backend-register"><?php printf( __( 'Registration URL: %s', 'l10n-mainwp-ithemes-security-extension' ), 'http://www.yourchildsite.com/<span style="color: #4AA02C">' . sanitize_title( $settings['register'] ) . '</span>' ); ?></label>
				</div>
			</div>
			<?php endif; ?>

			<div class="ui grid field">
				<label class="six wide column middle aligned" for="itsec-hide-backend-theme_compat"><?php _e( 'Enable Redirection', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ten wide column ui">
					<?php $form->add_checkbox( 'theme_compat', array( 'class' => 'itsec-settings-toggle' ) ); ?>
					<label for="itsec-hide-backend-theme_compat"><?php _e( 'Enable Redirection', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
					<p class="description"><?php _e( 'Redirect users to a custom location on your site, instead of throwing a 403 (forbidden) error.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
				</div>
			</div>

			<div class="ui grid itsec-hide-backend-theme_compat-content">
				<label class="six wide column middle aligned" for="itsec-hide-backend-theme_compat_slug"><?php _e( 'Redirection Slug', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ten wide column ui">
					<?php $form->add_text( 'theme_compat_slug', array( 'class' => 'text code' ) ); ?>
					<br />
					<label for="itsec-hide-backend-theme_compat_slug"><?php printf( __( 'Redirect Location: %s', 'l10n-mainwp-ithemes-security-extension' ), 'http://www.yourchildsite.com/<span style="color: #4AA02C">' . sanitize_title( $settings['theme_compat_slug'] ) . '</span>' ); ?></label>
					<p class="description"><?php _e( 'The slug to redirect users to when they attempt to access wp-admin while not logged in.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
				</div>
			</div>

			<div class="ui grid">
				<label class="six wide column middle aligned" for="itsec-hide-backend-post_logout_slug"><?php _e( 'Advanced', 'mainwp-wordfence-extension' ); ?></label>
				<div class="ten wide column ui">
					<label for="itsec-hide-backend-post_logout_slug"><?php _e( 'Custom Login Action', 'l10n-mainwp-ithemes-security-extension' ); ?></label><br/>
					<?php $form->add_text( 'post_logout_slug', array( 'class' => 'text code' ) ); ?>
					<br />
					<p class="description"><?php _e( 'WordPress uses the "action" variable to handle many login and logout functions. By default this plugin can handle the normal ones but some plugins and themes may utilize a custom action (such as logging out of a private post). If you need a custom action please enter it here.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
				</div>
			</div>
		</div>
	</div>
<?php		
	}
}

new MainWP_ITSEC_Hide_Backend_Settings_Page();
