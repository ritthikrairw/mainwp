<?php

final class MainWP_ITSEC_Hide_Backend_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'hide-backend';
	}

	protected function sanitize_settings() {
		$this->sanitize_setting( 'bool', 'enabled', __( 'Hide Backend', 'l10n-mainwp-ithemes-security-extension' ) );
		
		if ( ! $this->settings['enabled'] ) {
			// Ignore all non-enabled settings changes when enabled is not checked.
			foreach ( $this->previous_settings as $name => $val ) {
				if ( 'enabled' !== $name ) {
					$this->settings[$name] = $val;
				}
			}

			return;
		}

		if ( ! isset( $this->settings['register'] ) ) {
			$this->settings['register'] = $this->previous_settings['register'];
		}

		$this->sanitize_setting( 'non-empty-title', 'slug', __( 'Login Slug', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'non-empty-title', 'register', __( 'Register Slug', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'theme_compat', __( 'Enable Redirection', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'non-empty-title', 'theme_compat_slug', __( 'Redirection Slug', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'title', 'post_logout_slug', __( 'Custom Login Action', 'l10n-mainwp-ithemes-security-extension' ) );
	}

	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}

		$forbidden_slugs = array( 'admin', 'login', 'wp-login.php', 'dashboard', 'wp-admin' );

		if ( in_array( $this->settings['slug'], $forbidden_slugs ) ) {
			$this->add_error( __( 'The Login Slug cannot be "%1$s" as WordPress uses that slug.', 'l10n-mainwp-ithemes-security-extension' ) );
			$this->set_can_save( false );
			return;
		}

		if ( $this->settings['enabled'] && $this->settings['slug'] !== $this->previous_settings['slug'] ) {
			$url = get_site_url() . '/' . $this->settings['slug'];
			MainWP_ITSEC_Response::add_message( sprintf( __( 'The Hide Backend feature is now active. Your new login URL is <strong><code>%1$s</code></strong>. Please note this may be different than what you sent as the URL was sanitized to meet various requirements. A reminder has also been sent to the notification email addresses set in iThemes Security\'s Global settings.', 'l10n-mainwp-ithemes-security-extension' ), esc_url( $url ) ) );
		} else if ( $this->settings['enabled'] && ! $this->previous_settings['enabled'] ) {
			$url = get_site_url() . '/' . $this->settings['slug'];
			MainWP_ITSEC_Response::add_message( sprintf( __( 'The Hide Backend feature is now active. Your new login URL is <strong><code>%1$s</code></strong>. A reminder has also been sent to the notification email addresses set in iThemes Security\'s Global settings.', 'l10n-mainwp-ithemes-security-extension' ), esc_url( $url ) ) );
		} else if ( ! $this->settings['enabled'] && $this->previous_settings['enabled'] ) {
			$url = get_site_url() . '/wp-login.php';
			MainWP_ITSEC_Response::add_message( sprintf( __( 'The Hide Backend feature is now disabled. Your new login URL is <strong><code>%1$s</code></strong>. A reminder has also been sent to the notification email addresses set in iThemes Security\'s Global settings.', 'l10n-mainwp-ithemes-security-extension' ), esc_url( $url ) ) );
		}

		if ( isset( $url ) ) {			
			MainWP_ITSEC_Response::prevent_modal_close();
		}

		MainWP_ITSEC_Response::reload_module( $this->get_id() );
	}

	
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_Hide_Backend_Validator() );
