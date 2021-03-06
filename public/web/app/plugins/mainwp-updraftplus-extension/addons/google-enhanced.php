<?php

/*
  UpdraftPlus Addon: google-enhanced:Google Drive, enhanced
  Description: Adds enhanced capabilities for Google Drive users
  Version: 1.0
  Shop: /shop/google-drive-enhanced/
  Latest Change: 1.9.1
 */

$mainwp_updraft_plus_addon_google_enhanced = new MainWP_Updraft_Plus_Addon_Google_Enhanced;

class MainWP_Updraft_Plus_Addon_Google_Enhanced {

	public function __construct() {
			add_filter( 'mainwp_updraftplus_options_googledrive_others', array( $this, 'options_googledrive_others' ), 10, 2 );
			add_filter( 'mainwp_updraftplus_googledrive_parent_id', array( $this, 'googledrive_parent_id' ), 10, 4 );
			add_filter( 'mainwp_updraftplus_options_googledrive_foldername', array( $this, 'options_googledrive_foldername' ), 10, 2 );
	}

	public function options_googledrive_foldername( $opt, $orig ) {
			return $orig;
	}

	public function googledrive_parent_id( $parent_id, $opts, $service, $module ) {

		if ( isset( $opts['folder'] ) ) {
				$folder = $opts['folder'];
		} else {
			if ( isset( $opts['parentid'] ) ) {
				if ( empty( $opts['parentid'] ) ) {
						$folder = '';
				} else {
					if ( is_array( $opts['parentid'] ) ) {
							$folder = '#' . $opts['parentid']['id'];
					} else {
							$folder = '#' . $opts['parentid'];
					}
				}
			} else {
					$folder = 'UpdraftPlus';
			}
		}

		if ( '#' === substr( $folder, 0, 1 ) ) {
				return substr( $folder, 1 );
		} else {
				return $module->id_from_path( $folder );
		}
	}

	public function options_googledrive_others( $folder_opts, $opts ) {

		if ( isset( $opts['folder'] ) ) {
				$folder = $opts['folder'];
		} else {
			if ( isset( $opts['parentid'] ) ) {
				if ( is_array( $opts['parentid'] ) ) {
					if ( isset( $opts['parentid']['name'] ) ) {
							$folder = $opts['parentid']['name'];
					} else {
							$folder = empty( $opts['parentid']['id'] ) ? '' : '#' . $opts['parentid']['id'];
					}
				} else {
						$folder = empty( $opts['parentid'] ) ? '' : '#' . $opts['parentid'];
				}
			} else {
					$folder = 'UpdraftPlus';
			}
		}

			$folder_opts = '<div class="ui grid field mwp_updraftplusmethod googledrive">
			<label class="six wide column middle aligned">
				<h4 class="ui header">' . __( 'Google Drive', 'mainwp-updraftplus-extension' ) . ' ' . __( 'Folder', 'mainwp-updraftplus-extension' ) . ':</h4>
			</label>
            <div class="ui ten wide column">
                <input title="' . esc_attr( sprintf( __( 'Enter the path of the %s folder you wish to use here.', 'mainwp-updraftplus-extension' ), 'Google Drive' ) . ' ' . __( 'If the folder does not already exist, then it will be created.' ) . ' ' . sprintf( __( 'e.g. %s', 'mainwp-updraftplus-extension' ), 'MyBackups/WorkWebsite.' ) . ' ' . sprintf( __( 'If you leave it blank, then the backup will be placed in the root of your %s', 'mainwp-updraftplus-extension' ), 'Google Drive' ) ) . '" type="text" style="width:442px" name="mwp_updraft_googledrive[folder]" value="' . esc_attr( $folder ) . '">
                <br>
                <em>' . htmlspecialchars( sprintf( __( 'In %s, path names are case sensitive.', 'mainwp-updraftplus-extension' ), 'Google Drive' ) ) . ' ' . __('Supported tokens', 'mainwp-updraftplus-extension'). ' %sitename%, %siteurl%</em>
            </div>
            </div>';

			return $folder_opts;
	}
}
