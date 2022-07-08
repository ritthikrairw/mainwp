<?php

class MainWP_ITSEC_WordPress_Tweaks_Validator extends MainWP_ITSEC_Validator {
	public function get_id() {
		return 'wordpress-tweaks';
	}

	protected function sanitize_settings() {
		$previous_settings = MainWP_ITSEC_Modules::get_settings( $this->get_id() );

		//$this->sanitize_setting( 'bool', 'wlwmanifest_header', __( 'Windows Live Writer Header', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'bool', 'edituri_header', __( 'EditURI Header', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'bool', 'comment_spam', __( 'Comment Spam', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'file_editor', __( 'File Editor', 'l10n-mainwp-ithemes-security-extension' ) );
		// $this->sanitize_setting( 'positive-int', 'disable_xmlrpc', __( 'XML-RPC', 'l10n-mainwp-ithemes-security-extension' ) );
		// $this->sanitize_setting( array( 0, 1, 2 ), 'disable_xmlrpc', __( 'XML-RPC', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( array( 'disable', 'disable_pingbacks', 'enable' ), 'disable_xmlrpc', __( 'XML-RPC', 'l10n-mainwp-ithemes-security-extension' ) );
		
		$this->sanitize_setting( array( 'default-access', 'restrict-access' ), 'rest_api', __( 'REST API', 'l10n-mainwp-ithemes-security-extension' ) );
		
		$this->sanitize_setting( 'bool', 'allow_xmlrpc_multiauth', __( 'Multiple Authentication Attempts per XML-RPC Request', 'l10n-mainwp-ithemes-security-extension' ) );
        $this->sanitize_setting( array( 'default-access', 'restrict-access' ), 'rest_api', __( 'REST API', 'l10n-mainwp-ithemes-security-extension' ) );
		//$this->sanitize_setting( 'bool', 'login_errors', __( 'Login Error Messages', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'force_unique_nicename', __( 'Force Unique Nickname', 'l10n-mainwp-ithemes-security-extension' ) );
		$this->sanitize_setting( 'bool', 'disable_unused_author_pages', __( 'Disable Extra User Archives', 'l10n-mainwp-ithemes-security-extension' ) );
       // $this->sanitize_setting( 'bool', 'block_tabnapping', __( 'Protect Against Tabnapping', 'l10n-mainwp-ithemes-security-extension' ) );
        $this->sanitize_setting( array( 'both', 'email', 'username' ), 'valid_user_login_type', __( 'Login with Email Address or Username', 'l10n-mainwp-ithemes-security-extension' ) );
        //$this->sanitize_setting( 'bool', 'patch_thumb_file_traversal', __( 'Mitigate Attachment File Traversal Attack', 'l10n-mainwp-ithemes-security-extension' ) );
	}

	protected function validate_settings() {
		if ( ! $this->can_save() ) {
			return;
		}
	}
}

MainWP_ITSEC_Modules::register_validator( new MainWP_ITSEC_WordPress_Tweaks_Validator() );
