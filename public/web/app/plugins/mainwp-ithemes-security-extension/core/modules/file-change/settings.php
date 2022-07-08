<?php

final class MainWP_ITSEC_File_Change_Settings extends MainWP_ITSEC_Settings {
	public function get_id() {
		return 'file-change';
	}

	public function get_defaults() {
		return array(
			//'split'        => false,
			//'method'       => 'exclude',
			'file_list'    => array(),
			'types'           => array(
				'.log', '.mo', '.po',
				// Images
				'.bmp', '.gif', '.ico', '.jpe', '.jpeg', '.jpg', '.png', '.psd', '.raw', '.svg', '.tif', '.tiff',

				// Audio
				'.aif', '.flac', '.m4a', '.mp3', '.oga', '.ogg', '.ogg', '.ra', '.wav', '.wma',

				// Video
				'.asf', '.avi', '.mkv', '.mov', '.mp4', '.mpe', '.mpeg', '.mpg', '.ogv', '.qt', '.rm', '.vob', '.webm', '.wm', '.wmv',
			),
			//'email'        => true,
			//'notify_admin' => true,
			//'last_run'     => 0,
			//'last_chunk'   => false,
			'show_warning' => false,
		);
	}
}

MainWP_ITSEC_Modules::register_settings( new MainWP_ITSEC_File_Change_Settings() );
