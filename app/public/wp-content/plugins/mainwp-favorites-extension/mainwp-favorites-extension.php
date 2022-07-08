<?php
/*
Plugin Name: MainWP Favorites Extension
Plugin URI: https://mainwp.com
Description: MainWP Favorites is an extension for the MainWP plugin that allows you to store your favorite plugins and themes, and install them directly to child sites from the dashboard repository.
Version: 4.0.10
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/docs/category/mainwp-extensions/favorites/
*/

if ( ! defined( 'MAINWP_FAVORITES_PLUGIN_FILE' ) ) {
	define( 'MAINWP_FAVORITES_PLUGIN_FILE', __FILE__ );
}

require_once ABSPATH . '/wp-admin/includes/file.php';

class Favorites_Extension {
	public static $instance = null;
	protected $plugin_url;
	private $plugin_slug;
	public $settings         = null;
	public $favorites_folder = null;

	public function __construct() {

		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_action( 'init', array( &$this, 'parse_init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'wp_ajax_favorites_addplugintheme', array( &$this, 'favorites_addplugintheme' ) );
		add_action( 'mainwp_install_plugin_card_bottom', array( &$this, 'plugininstall_action_links' ), 10, 2 );
		add_action( 'mainwp_install_theme_favorites_enabled', '__return_true' ); // deprecated
		add_filter( 'mainwp_uploadbulk_uploader_options', array( &$this, 'uploadbulk_uploader_options' ), 10, 2 );
		add_action( 'wp_ajax_favorites_group_add', array( &$this, 'favorites_group_add' ) );
		add_action( 'wp_ajax_favorites_group_delete', array( &$this, 'favorites_group_delete' ) );
		add_action( 'wp_ajax_favorites_group_rename', array( &$this, 'favorites_group_rename' ) );
		add_action( 'wp_ajax_favorites_group_getfavorites', array( &$this, 'favorites_group_getfavorites' ) );
		add_action( 'wp_ajax_favorites_group_updategroup', array( &$this, 'favorites_group_updategroup' ) );
		add_action( 'wp_ajax_favorite_notes_save', array( &$this, 'favorite_notes_save' ) );
		add_action( 'wp_ajax_group_notes_save', array( &$this, 'group_notes_save' ) );
		add_action( 'wp_ajax_favorite_prepareinstallplugintheme', array( &$this, 'favorite_prepareinstallplugintheme' ) );
		add_action( 'wp_ajax_favorite_performinstallplugintheme', array( &$this, 'favorite_performinstallplugintheme' ) );
		add_action( 'wp_ajax_favorite_prepareinstallgroupplugintheme', array( &$this, 'favorite_prepareinstallgroupplugintheme' ) );
		add_action( 'wp_ajax_favorite_removefavorite', array( &$this, 'favorite_removefavorite' ) );
		add_action( 'wp_ajax_favorite_removegroup', array( &$this, 'favorite_removegroup' ) );
		add_action( 'wp_ajax_favorites_uploadbulkaddtofavorites', array( &$this, 'favorites_uploadbulkaddtofavorites' ) );
		Favorites_Extension_DB::get_instance()->install();
	}

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new Favorites_Extension();
		}

		return self::$instance;
	}

	public function parse_init() {

		if ( isset( $_GET['favor_mwpdl'] ) && isset( $_GET['sig'] ) ) {

			$this->init_settings();

			if ( empty( $this->settings['custom_folder'] ) ) {
				return;
			}

			$file = $this->get_favorites_folder() . rawurldecode( $_REQUEST['favor_mwpdl'] );
			if ( stristr( rawurldecode( $_REQUEST['favor_mwpdl'] ), '..' ) ) {
				return;
			}

			if ( file_exists( $file ) && md5( filesize( $file ) ) == $_GET['sig'] ) {
				Manage_Favorites::get_instance()->uploadFile( $file );
				exit();
			}
		}
	}

	public function init_settings() {
		if ( null === $this->settings ) {
			$this->settings = get_option( 'manwp_favorites_settings', array() );
			if ( ! is_array( $this->settings ) ) {
				$this->settings = array();
			}

			$this->favorites_folder = ( isset( $this->settings['custom_folder'] ) && ! empty( $this->settings['custom_folder'] ) ) ? $this->settings['custom_folder'] . 'favorites/' : '';
			if ( empty( $this->favorites_folder ) ) {
				$this->favorites_folder = apply_filters( 'mainwp_getspecificdir', 'favorites' );
			}

			if ( substr( $this->favorites_folder, - 1 ) != '/' ) {
				$this->favorites_folder .= '/';
			}
		}
		return $this->settings;
	}

	public function get_settings( $name = false ) {
		$this->init_settings();
		if ( empty( $name ) ) {
			return $this->settings;
		}
		return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : '';
	}

	public function get_favorites_folder() {
		$this->init_settings();
		return $this->favorites_folder;
	}

	public function create_folders() {
		$dir         = $this->get_favorites_folder();
		$dir_plugins = $dir . 'plugins';
		$dir_themes  = $dir . 'themes';

		if ( ! file_exists( $dir ) ) {
			@mkdir( $dir, 0777, true );
		}

		if ( ! file_exists( $dir . '/index.php' ) ) {
			@touch( $dir . '/index.php' );
		}

		if ( ! file_exists( $dir_plugins ) ) {
			@mkdir( $dir_plugins, 0777, true );
		}

		if ( ! file_exists( $dir_plugins . '/index.php' ) ) {
			@touch( $dir_plugins . '/index.php' );
		}

		if ( ! file_exists( $dir_themes ) ) {
			@mkdir( $dir_themes, 0777, true );
		}

		if ( ! file_exists( $dir_themes . '/index.php' ) ) {
			@touch( $dir_themes . '/index.php' );
		}
	}

	public static function delete_dir( $dirPath ) {
		if ( ! is_dir( $dirPath ) ) {
			return;
		}

		if ( substr( $dirPath, strlen( $dirPath ) - 1, 1 ) != '/' ) {
			$dirPath .= '/';
		}

		$files = scandir( $dirPath );
		foreach ( $files as $file ) {
			if ( ! empty( $file ) ) {
				if ( is_dir( $dirPath . $file ) ) {
					if ( '.' != $file && '..' != $file ) {
						self::delete_dir( $dirPath . $file );
					}
				} else {
					@unlink( $dirPath . $file );
				}
			}
		}
		rmdir( $dirPath );
	}

	public static function validate_favories( $type ) {
		$result    = self::scan_favorites_folder( $type );
		$added_fav = '';
		$messages  = array();
		if ( isset( $result['added_fav'] ) && is_array( $result['added_fav'] ) && count( $result['added_fav'] ) > 0 ) {
			$added_fav  = implode( '<br />', $result['added_fav'] );
			$messages[] = $added_fav;
		}
		$removed_fav = '';
		if ( isset( $result['removed_fav'] ) && is_array( $result['removed_fav'] ) && count( $result['removed_fav'] ) > 0 ) {
			$removed_fav = implode( '<br />', $result['removed_fav'] );
			$messages[]  = $removed_fav;
		}
		if ( count( $messages ) > 0 ) {
			?>
			<div class="ui green message"><i class="close icon"></i><?php echo implode( '<br />', $messages ); ?></div>
			<?php
		}
	}

	public static function scan_favorites_folder( $type ) {

		$results   = Favorites_Extension_DB::get_instance()->query( Favorites_Extension_DB::get_instance()->get_sql_favorites_for_current_user( $type ) );
		$favorites = array();
		while ( $results && ( $favorite = @Favorites_Extension_DB::fetch_object( $results ) ) ) {
			$favorites[] = $favorite;
		}

		$current_fav_files = array();
		foreach ( $favorites as $favorite ) {
			if ( ! empty( $favorite->file ) ) {
				$current_fav_files[]                 = $favorite->file;
				$current_fav_name[ $favorite->file ] = $favorite->name . ' ' . $favorite->version;
			}
		}

		if ( 'plugin' == $type ) {
			$path = 'plugins/';
		} else {
			$path = 'themes/';
		}

		$favorite_root = self::get_instance()->get_favorites_folder() . $path; // apply_filters( 'mainwp_getspecificdir', $path );

		$dir            = @opendir( $favorite_root );
		$scan_fav_files = $new_fav_files = array();
		if ( $dir ) {
			while ( false !== ( $file = readdir( $dir ) ) ) {
				if ( '.' == substr( $file, 0, 1 ) ) {
					continue;
				}
				if ( '.zip' !== substr( $file, - 4 ) ) {
					continue;
				}
				$scan_fav_files[] = $file;
				if ( ! in_array( $file, $current_fav_files ) ) {
					$new_fav_files[] = $file;
				}
			}
			closedir( $dir );
		}
		$missing_fav_files = array();
		foreach ( $current_fav_files as $file ) {
			if ( ! in_array( $file, $scan_fav_files ) ) {
				$missing_fav_files[] = $file;
			}
		}
		$return = array();
		// remove missing favorites
		foreach ( $missing_fav_files as $file ) {
			Favorites_Extension_DB::get_instance()->remove_favorite_by( 'file', $file, null, $type );
			$return['removed_fav'][] = "Removed favorite $type: " . $current_fav_name[ $file ];
		}
		$output = array();
		foreach ( $new_fav_files as $file ) {
			self::get_favorites_data_zip( $file, $type, $output );
		}
		$return['added_fav'] = isset( $output['added_fav'] ) ? $output['added_fav'] : array();

		return $return;
	}

	public static function get_favorites_data_zip( $file, $type, &$output ) {
		if ( 'plugin' == $type ) {
			$path = 'plugins/';
		} else {
			$path = 'themes/';
		}

		$favorite_path = self::get_instance()->get_favorites_folder() . $path; // apply_filters( 'mainwp_getspecificdir', $path );
		$new_fname     = sanitize_file_name( $file );
		if ( $new_fname != $file ) {
			if ( @rename( $favorite_path . $file, $favorite_path . $new_fname ) ) {
				$file = $new_fname;
			} else {
				return false;
			}
		}

		$destination_path = $favorite_path . 'tmp/';

		$_prefix = null;
		// create new empty folder to unzip
		while ( file_exists( $destination_path ) ) {
			$_prefix         .= rand( 10, 99 );
			$destination_path = $favorite_path . 'tmp_' . $_prefix . '/';
		}

		$hasWPFileSystem = apply_filters( 'mainwp_getwpfilesystem', 10 );
		global $wp_filesystem;
		$result = false;
		if ( $hasWPFileSystem && function_exists( 'unzip_file' ) ) {
			if ( ! $wp_filesystem->mkdir( $destination_path ) ) {
				return false;
			}
			$result = unzip_file( $favorite_path . $file, $destination_path );
		} else {
			return false;
		}

		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_codes();
			if ( is_array( $error ) ) {
				$error = implode( ', ', $error );
			}

			return false;
		} elseif ( false === $result ) {
			return false;
		}

		$files     = scandir( $destination_path );
		$theFolder = '';
		foreach ( $files as $value ) {
			if ( '.' === $value || '..' === $value ) {
				continue;
			}
			if ( is_dir( $destination_path . $value ) ) {
				$theFolder = $value;
				break; // get only the first folder
			}
		}

		$data = array();
		if ( ! empty( $theFolder ) ) {
			if ( 'plugin' == $type ) {
				$data = self::get_plugin_data( $destination_path . $theFolder . '/' );
			} else {
				// echo $theFolder . "======" . $destination_path;
				$data = self::get_theme_data( $theFolder, $destination_path );
			}
		}
		// try get data at root folder
		if ( empty( $data ) ) {
			if ( 'plugin' == $type ) {
				$data = self::get_plugin_data( $destination_path );
			} else {
				$data = self::get_theme_data( '/', $destination_path );
			}
		}

		if ( false !== $data && '' != $data['Name'] ) {
			// uploaded favorites so slug is empty
			global $current_user;
			$url     = isset( $data['url'] ) ? $data['url'] : '';
			$old_fav = Favorites_Extension_DB::get_instance()->get_favorite_by( 'name', $data['Name'], $current_user->ID, $type );
			$result  = Favorites_Extension_DB::get_instance()->add_favorite( $current_user->ID, $type, '', $data['Name'], $data['Author'], $data['Version'], $file, $url );
			if ( 'NEWER_EXISTED' !== $result ) {
				$output['added_fav'][] = "Added new favorite $type: " . $data['Name'] . ' ' . $data['Version'] . ' by ' . $data['Author'];

				if ( $old_fav && isset( $old_fav->file ) && $old_fav->file != $file ) {
					$old_file = $favorite_path . $old_fav->file;
					if ( file_exists( $old_file ) ) {
						@unlink( $old_file );
					}
				}
			} else {
				if ( file_exists( $favorite_path . $file ) ) {  // delete old version zip file
					@unlink( $favorite_path . $file );
				}
			}
		}

		if ( $hasWPFileSystem ) {
			if ( $wp_filesystem->is_dir( $destination_path ) ) {
				$wp_filesystem->delete( $destination_path, true );
			}
		}

		return true;
	}

	public static function get_plugin_data( $folderPath ) {

		$srcFiles = glob( $folderPath . '*.php' );
		// print_r($srcFiles);
		foreach ( $srcFiles as $srcFile ) {
			$thePlugin = get_plugin_data( $srcFile );
			if ( ! empty( $thePlugin ) && '' != $thePlugin['Name'] ) {
				// 'Name'        => 'Plugin Name',
				// 'PluginURI'   => 'Plugin URI',
				// 'Version'     => 'Version',
				// 'Description' => 'Description',
				// 'Author'      => 'Author',
				// 'AuthorURI'   => 'Author URI',
				// 'TextDomain'  => 'Text Domain',
				$thePlugin['url'] = $thePlugin['PluginURI'];

				return $thePlugin;
			}
		}

		return false;
	}

	public static function get_theme_data( $folder, $path ) {

		$theTheme = wp_get_theme( $folder, $path );
		// print_r($theTheme);

		if ( $theTheme->exists() ) {
			$data['Name']    = $theTheme->get( 'Name' );
			$data['Author']  = $theTheme->get( 'Author' );
			$data['Version'] = $theTheme->get( 'Version' );

			return $data;
		}

		return false;
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
	}

	public function admin_init() {

		if ( isset( $_REQUEST['page'] ) ) {
			if ( 'Extensions-Mainwp-Favorites-Extension' == $_REQUEST['page'] || 'ThemesFavorite' == $_REQUEST['page'] || 'PluginsFavorite' == $_REQUEST['page'] || 'PluginsInstall' == $_REQUEST['page'] || 'ThemesInstall' == $_REQUEST['page'] ) {
				wp_enqueue_script( 'favorites-extension-admin', $this->plugin_url . 'js/admin.js', array(), '4.1' );
				wp_enqueue_script( 'favorites-ext', $this->plugin_url . 'js/mainwp-favorites.js', false );
				wp_enqueue_style( 'favorites-extension-admin', $this->plugin_url . 'css/mainwp-favorites.css' );
			}
		}

		if ( isset( $_REQUEST['favorites_do'] ) ) {
			if ( 'FavoritesInstallBulk-uploadfile' == $_REQUEST['favorites_do'] ) {
				// list of valid extensions, ex. array("jpeg", "xml", "bmp")
				$allowedExtensions = array( 'zip' ); // Only zip allowed
				// max file size in bytes
				$sizeLimit = 20 * 1024 * 1024; // 20MB = max allowed

				$uploader = apply_filters( 'mainwp_qq2fileuploader', $allowedExtensions, $sizeLimit );

				if ( 'plugin' == $_REQUEST['type'] ) {
					$path_type = 'plugins/';
				} elseif ( 'theme' == $_REQUEST['type'] ) {
					$path_type = 'themes/';
				} else {
					$path_type = 'others/';
				}

				$path = $this->get_favorites_folder() . $path_type; // apply_filters( 'mainwp_getspecificdir', 'favorites/' . $path_type );

				$result = $uploader->handleUpload( $path, true );
				// to pass data through iframe you will need to encode all html tags
				die( htmlspecialchars( json_encode( $result ), ENT_NOQUOTES ) );
			}
		}


		if ( isset( $_POST['save_settings'] ) && isset( $_POST['favor_nonce'] ) && ! empty( $_POST['favor_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['favor_nonce'], 'favor-nonce' ) ) {
				exit( __( 'Invalid request.', 'mainwp-favorites-extension' ) );
				return;
			}

			$this->init_settings();

			$old_folder = isset( $this->settings['custom_folder'] ) ? $this->settings['custom_folder'] : '';
			$folder     = trim( $_POST['custom_folder'] );

			if ( isset( $_POST['custom_folder'] ) && ! empty( $folder ) ) {
				$folder = stripslashes( $folder );
				if ( substr( $folder, - 1 ) != '/' ) {
					$folder .= '/';
				}
				$this->settings['custom_folder'] = $folder;
				$this->favorites_folder          = $folder . 'favorites/';

				if ( $old_folder != $folder ) {
					$this->create_folders();
				}
			} else {
				$this->settings['custom_folder'] = '';
			}
			update_option( 'manwp_favorites_settings', $this->settings );
			wp_redirect( add_query_arg( 'updated', 'true' ) );
			exit();
		}

	}

	// Hook the Add To Favorites button
	public function plugininstall_action_links( $plugin ) {
		if ( is_object( $plugin ) ) {
			$plugin = (array) $plugin;
		}

		if ( is_array( $plugin ) && isset( $plugin['name'] ) ) {
			?>
			<div class="extra content">
				<span><?php echo _e( 'Add to Favorites', 'mainwp-favorites-extension' ); ?></span>
				<a class="ui huge star rating right floated" data-max-rating="1" id="add-favorite-plugin-<?php echo esc_html( $plugin['slug'] ); ?>"></a>
			</div>
			<?php
		}
	}


	public function uploadbulk_uploader_options( $option = '', $type = 'plugin' ) {
		$type    = esc_attr( $type );
		$option .= ' onComplete: function(id, fileName, result){ favorites_uploadbulk_oncomplete(id, fileName, result, \'' . $type . '\')},';

		return $option;
	}

	public function favorites_addplugintheme() {
		Manage_Favorites::add_favorite();
	}

	function favorites_group_add() {
		Manage_Favorites::add_group();
	}

	function favorites_group_delete() {
		Manage_Favorites::delete_group();
	}

	function favorites_group_rename() {
		Manage_Favorites::rename_group();
	}

	function favorites_group_getfavorites() {
		die( Manage_Favorites::get_favorites() );
	}

	function favorites_group_updategroup() {
		Manage_Favorites::update_group();
	}

	function favorite_notes_save() {
		Manage_Favorites::save_favorite_note();
	}

	function group_notes_save() {
		Manage_Favorites::save_group_note();
	}

	function favorite_prepareinstallplugintheme() {
		do_action( 'mainwp_prepareinstallplugintheme' );
	}

	function favorite_performinstallplugintheme() {
		do_action( 'mainwp_performinstallplugintheme' );
	}

	function favorite_prepareinstallgroupplugintheme() {
		$this->prepare_install_group();
	}

	public function prepare_install_group() {

		$groupid = $_POST['groupid'];
		if ( empty( $groupid ) ) {
			die( __( 'Unexpected error occurred. Please, try again.', 'mainwp-favorites-extension' ) );
		}

		$favorites = Favorites_Extension_DB::get_instance()->get_favorites_by_group_id( $groupid );

		$output = array();

		foreach ( $favorites as $favorite ) {
			if ( ! empty( $favorite->file ) ) {
				if ( $favorite->type == 'P' ) {
					$path = 'plugins';
				} else {
					$path = 'themes';
				}
				$favorite->download_url = $this->getFavoritesDownloadUrl( $path, $favorite->file ); // apply_filters( 'mainwp_getdownloadurl', $path, $favorite->file );
			} else {
				$favorite->download_url = '';
			}

			$output['favorites'][ $favorite->id ] = self::map_favorite(
				$favorite,
				array(
					'id',
					'name',
					'download_url',
					'slug',
				)
			);
		}

		die( json_encode( $output ) );
	}

	public function getFavoritesDownloadUrl( $what, $filename ) {
		$download_url = '';
		if ( empty( $this->settings['custom_folder'] ) ) {
			$download_url = apply_filters( 'mainwp_getdownloadurl', 'favorites/' . $what, $filename );
		} else {
			$fullFile     = $this->get_favorites_folder() . $what . '/' . $filename;
			$download_url = admin_url( '?sig=' . md5( filesize( $fullFile ) ) . '&favor_mwpdl=' . rawurlencode( $what . '/' . $filename ) );
		}
		return $download_url;
	}

	public static function map_favorite( &$favorite, $keys ) {
		$output = array();

		foreach ( $keys as $key ) {
			$output[ $key ] = $favorite->$key;
		}

		return (object) $output;
	}

	function favorite_prepareinstallgroup() {
		do_action( 'mainwp_prepareinstallplugintheme' );
	}

	public function favorite_removefavorite() {
		self::remove_favorite();
	}

	public static function remove_favorite() {
		if ( isset( $_POST['id'] ) && self::ctype_digit( $_POST['id'] ) ) {
			$favorite = Favorites_Extension_DB::get_instance()->get_favorite_by( 'id', $_POST['id'] );
			if ( self::can_edit_favorite( $favorite ) ) {
				// Remove from DB
				if ( Favorites_Extension_DB::get_instance()->remove_favorite_by( 'id', $favorite->id ) ) {
					if ( 'P' == $_POST['type'] ) {
						$path = 'plugins/';
					} elseif ( 'T' == $_POST['type'] ) {
						$path = 'themes/';
					}
					$path = self::get_instance()->get_favorites_folder() . $path; // apply_filters( 'mainwp_getspecificdir', 'favorites/' . $path );
					$file = $path . $_POST['file'];
					if ( file_exists( $file ) ) {
						@unlink( $file );
					}
					die( 'SUCCESS' );
				} else {
					die( 'ERROR' );
				}
			}
			die( 'DENIED' );
		}
		die( 'NOFAVORITE' );
	}

	// fix bug do not delete hidden file

	static function ctype_digit( $str ) {
		return ( is_string( $str ) || is_int( $str ) || is_float( $str ) ) && preg_match( '/^\d+\z/', $str );
	}

	public static function can_edit_favorite( &$favorite ) {
		if ( null == $favorite ) {
			return false;
		}

		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );
		// Everyone may change this favorite
		if ( ! $is_multi_user ) {
			return true;
		}

		global $current_user;

		return ( $favorite->userid == $current_user->ID );
	}

	public function favorite_removegroup() {
		self::remove_group();
	}

	public static function remove_group() {
		if ( isset( $_POST['id'] ) && self::ctype_digit( $_POST['id'] ) ) {
			$group = Favorites_Extension_DB::get_instance()->get_group_by( 'id', $_POST['id'] );
			if ( self::can_edit_group( $group ) ) {
				// Remove from DB
				if ( Favorites_Extension_DB::get_instance()->remove_group( $group->id ) ) {
					die( 'SUCCESS' );
				} else {
					die( 'ERROR' );
				}
			}
			die( 'DENIED' );
		}
		die( 'NOFAVORITE' );
	}

	public static function can_edit_group( &$group ) {
		if ( null == $group ) {
			return false;
		}
		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );
		// Everyone may change this favorite group
		if ( ! $is_multi_user ) {
			return true;
		}

		global $current_user;

		return ( $group->userid == $current_user->ID );
	}

	public function favorites_uploadbulkaddtofavorites() {

		$this->secure_request();
		$type = $_POST['type'];
		$file = $_POST['file'];
		$copy = $_POST['copy'];

		if ( empty( $type ) || empty( $file ) ) {
			die( __( 'Unexpected error occured while trying to add the favorite.', 'mainwp-favorites-extension' ) );
		}

		if ( 'plugin' == $type ) {
			$path = 'plugins/';
		} else {
			$path = 'themes/';
		}

		$favorite_path = $this->get_favorites_folder() . $path; // apply_filters( 'mainwp_getspecificdir', $path );

		if ( 'yes' == $copy ) {
			$file_path = apply_filters( 'mainwp_getspecificdir', 'bulk' );
			if ( ! @copy( $file_path . $file, $favorite_path . $file ) ) {
				die( __( 'Unexpected error occured while trying to copy the file.', 'mainwp-favorites-extension' ) );
			}
		}

		$new_fname = sanitize_file_name( $file );

		if ( $new_fname != $file ) {
			if ( @rename( $favorite_path . $file, $favorite_path . $new_fname ) ) {
				$file = $new_fname;
			} else {
				die( __( 'Unexpected error occured while trying to rename te file.', 'mainwp-favorites-extension' ) );
			}
		}

		$destination_path = $favorite_path . 'tmp/';

		$_prefix = null;
		// create new empty folder to unzip
		while ( file_exists( $destination_path ) ) {
			$_prefix          = rand( 10, 99 );
			$destination_path = $favorite_path . 'tmp_' . $_prefix . '/';
		}

		$hasWPFileSystem = apply_filters( 'mainwp_getwpfilesystem', 10 );
		global $wp_filesystem;

		if ( $hasWPFileSystem && ! empty( $wp_filesystem ) ) {
			if ( ! $wp_filesystem->mkdir( $destination_path ) ) {
				die( __( 'Unexpected error occured while trying to unzip the file.', 'mainwp-favorites-extension' ) );
			}
		}

		$result = unzip_file( $favorite_path . $file, $destination_path );

		if ( is_wp_error( $result ) ) {
			$error = $result->get_error_codes();
			if ( is_array( $error ) ) {
				$error = implode( ', ', $error );
			}
			die( $error );
		}

		$files     = scandir( $destination_path );
		$theFolder = '';
		foreach ( $files as $value ) {
			if ( '.' === $value || '..' === $value ) {
				continue;
			}
			if ( is_dir( $destination_path . $value ) ) {
				$theFolder = $value;
				break; // get only the first folder
			}
		}

		$data = array();
		if ( ! empty( $theFolder ) ) {
			if ( 'plugin' == $type ) {
				$data = self::get_plugin_data( $destination_path . $theFolder . '/' );
			} else {
				$data = self::get_theme_data( $theFolder, $destination_path );
			}
		}
		// try get data at root folder
		if ( empty( $data ) ) {
			if ( 'plugin' == $type ) {
				$data = self::get_plugin_data( $destination_path );
			} else {
				$data = self::get_theme_data( '/', $destination_path );
			}
		}

		if ( empty( $data ) ) {
			die( 'ERROR Data error.' );
		}

		$return = 'FAIL';
		if ( false !== $data && '' != $data['Name'] ) {
			// uploaded favorites so slug is empty
			global $current_user;
			$url    = isset( $data['url'] ) ? $data['url'] : '';
			$result = Favorites_Extension_DB::get_instance()->add_favorite( $current_user->ID, $type, '', $data['Name'], $data['Author'], $data['Version'], $file, $url );
			if ( 'NEWER_EXISTED' == $result ) {
				$return = 'NEWER_EXISTED';
			} else {
				$return = 'SUCCESS';
			}
		}

		if ( $wp_filesystem->is_dir( $destination_path ) ) {
			$wp_filesystem->delete( $destination_path, true );
		}

		die( $return );
	}

	function secure_request() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mainwp-common-nonce' ) ) {
			die( 'Invalid request!' );
		}
	}
}

class Favorites_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childKey            = false;
	protected $plugin_handle       = 'mainwp-favorites-extension';
	protected $product_id          = 'MainWP Favorites Extension';
	protected $software_version    = '4.0.10';

	public function __construct() {

		spl_autoload_register( array( $this, 'autoload' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
		}

		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

	function autoload( $class_name ) {
		$class_name = str_replace( '_', '-', strtolower( $class_name ) );
		$class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'class' . DIRECTORY_SEPARATOR . $class_name . '.class.php';
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		}
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );

		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-favorites-extension' ) ) {
			return;
		}

		$favorites = Favorites_Extension::get_instance();
		add_filter( 'mainwp_getsubpages_themes', array( &$this, 'get_sub_pages_themes' ) );
		add_filter( 'mainwp_getsubpages_plugins', array( &$this, 'get_sub_pages_plugins' ) );

	}

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin'           => __FILE__,
			'api'              => $this->plugin_handle,
			'mainwp'           => true,
			'callback'         => array( &$this, 'favorites_settings' ),
			'apiManager'       => true,
			'on_load_callback' => '',
		);

		return $pArray;
	}

	function favorites_settings() {
		do_action( 'mainwp_pageheader_extensions', __FILE__ );
		Manage_Favorites::get_instance()->render_manage();
		do_action( 'mainwp_pagefooter_extensions', __FILE__ );
	}

	function get_sub_pages_themes( $pArray ) {
		$pArray[] = array(
			'slug'     => 'Favorite',
			'title'    => __( 'Favorite Themes', 'mainwp-favorites-extension' ),
			'callback' => array( &$this, 'sub_page_favorite_themes' ),
		);

		return $pArray;
	}

	function get_sub_pages_plugins( $pArray ) {
		$pArray[] = array(
			'slug'     => 'Favorite',
			'title'    => __( 'Favorite Plugins', 'mainwp-favorites-extension' ),
			'callback' => array( &$this, 'sub_page_favorite_plugins' ),
		);

		return $pArray;
	}

	function sub_page_favorite_themes() {
		do_action( 'mainwp-pageheader-themes', 'Favorite' );
		Favorites_Themes::get_instance()->render_page();
		do_action( 'mainwp-pagefooter-themes', 'Favorite' );
	}

	function sub_page_favorite_plugins() {
		do_action( 'mainwp-pageheader-plugins', 'Favorite' );
		Favorites_Plugins::get_instance()->render_page();
		do_action( 'mainwp-pagefooter-plugins', 'Favorite' );
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Favorites Extension ' . __( 'requires <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="https://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function activate() {
		$options = array(
			'product_id'       => $this->product_id,
			'software_version' => $this->software_version,
		);
		do_action( 'mainwp_activate_extention', $this->plugin_handle, $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

new Favorites_Extension_Activator();
