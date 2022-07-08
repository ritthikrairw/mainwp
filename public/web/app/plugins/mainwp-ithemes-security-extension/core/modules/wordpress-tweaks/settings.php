<?php

final class MainWP_ITSEC_Wordpress_Tweaks_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'wordpress-tweaks';
	}

	public function get_defaults() {
		return array(
			///'wlwmanifest_header'          => false,
			//'edituri_header'              => false,
			//'comment_spam'                => false,
			'file_editor'                 => false,
			'disable_xmlrpc'              => 'enable',
			'allow_xmlrpc_multiauth'      => true,
            'rest_api'                    => 'default-access',
			//'login_errors'                => false,
			'force_unique_nicename'       => false,
			'disable_unused_author_pages' => false,
            //'block_tabnapping'            => false,
            'valid_user_login_type'       => 'both',
            //'patch_thumb_file_traversal'  => true,
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_WordPress_Tweaks_Settings() );
