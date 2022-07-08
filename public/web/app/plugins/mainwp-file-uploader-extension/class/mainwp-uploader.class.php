<?php
class MainWP_Uploader {
	public static $instance    = null;
	private $allowedExtensions = array();

	static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new MainWP_Uploader();
		}
		return self::$instance;
	}

	public function init() {

		add_action( 'wp_ajax_mainwp_uploader_load_sites', array( $this, 'uploader_load_sites' ) );
		add_action( 'wp_ajax_mainwp_uploader_delete_file', array( $this, 'ajax_delete_file' ) );
		add_action( 'wp_ajax_mainwp_uploader_uploadbulk_file', array( $this, 'uploader_uploadbulk_file' ) );
		add_action( 'wp_ajax_mainwp_uploader_delete_temp_file', array( $this, 'uploader_delete_temp_file' ) );

		$this->allowedExtensions = array(
			'zip',
			'rar',
			'txt',
			'php',
			'xml',
			'bmp',
			'php',
			'html',
			'css',
			'ico',
			'jpg',
			'jpeg',
			'png',
			'gif',
			'pdf',
			'doc',
			'docx',
			'ppt',
			'pptx',
			'pps',
			'ppsx',
			'odt',
			'xls',
			'xlsx',
			'js',
			'mp3',
			'm4a',
			'ogg',
			'wav',
			'mp4',
			'm4v',
			'mov',
			'wmv',
			'avi',
			'mpg',
			'ogv',
			'3gp',
			'3g2',
			'po',
			'mo',
			'dat',
		);
	}

	public function get_allowed_types() {
		return apply_filters( 'mainwp_file_uploader_allowed_file_types', $this->allowedExtensions );
	}

	public function admin_init() {

		if ( isset( $_REQUEST['uploader_do'] ) ) {
			if ( 'UploaderInstallBulk-uploadfile' == $_REQUEST['uploader_do'] ) {

				$postSize   = MainWP_Uploader_Utility::to_bytes( ini_get( 'post_max_size' ) );
				$uploadSize = MainWP_Uploader_Utility::to_bytes( ini_get( 'upload_max_filesize' ) );
				$sizeLimit  = $postSize;

				if ( $postSize > $uploadSize ) {
					$sizeLimit = $uploadSize;
				}

				$allowed_types = $this->get_allowed_types();
				$uploader      = apply_filters( 'mainwp_qq2fileuploader', $allowed_types, $sizeLimit );

				$path = apply_filters( 'mainwp_getspecificdir', 'uploader/' );

				$result = $uploader->handleUpload( $path, true );
				// to pass data through iframe you will need to encode all html tags
				die( htmlspecialchars( json_encode( $result ), ENT_NOQUOTES ) );
			}
		}
	}

	public function sanitize_upload_files_name( $path, $files ) {
		if ( ! is_array( $files ) ) {
			return $files;

		}
		$new_files = array();

		foreach ( $files as $file ) {

			if ( $file == '.htaccess' ) {
				$san_name = $file;
			} else {
				$san_name = $file;
				if ( 0 === strpos( $file, '_' ) ) {
					$san_name = 'fix_underscore' . $file;
				}
				$san_name = sanitize_file_name( $san_name );
			}

			if ( substr( $san_name, -3 ) == 'php' ) {
				$san_name = substr( $san_name, 0, - 3 ) . 'phpfile.txt';
			}

			if ( $file != $san_name && @rename( $path . $file, $path . $san_name ) ) {
				$new_files[] = $san_name;
			} else {
				$new_files[] = $file;
			}
		}
		return $new_files;
	}

	public function uploader_load_sites() {
		global $mainWPUploaderExtensionActivator;
		$files           = isset( $_POST['files'] ) ? $_POST['files'] : array();
		$selected_sites  = isset( $_POST['sites'] ) ? $_POST['sites'] : array();
		$selected_groups = isset( $_POST['groups'] ) ? $_POST['groups'] : array();
		$path            = trim( $_POST['path'] );

		if ( substr( $path, -1 ) != '/' ) {
			$path .= '/';
		}

		if ( count( $files ) == 0 ) {
			die( 'NOFILE' );
		}

		$uploader_root = apply_filters( 'mainwp_getspecificdir', 'uploader/' );
		$files         = $this->sanitize_upload_files_name( $uploader_root, $files );

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPUploaderExtensionActivator->get_child_file(), $mainWPUploaderExtensionActivator->get_child_key(), $selected_sites, $selected_groups );

		if ( ! is_array( $dbwebsites ) || count( $dbwebsites ) == 0 ) {
			die( 'NOSITE' );
		}

		?>

		<div class="ui modal" id="mainwp-file-uploader-process-modal">
			<div class="header"><?php esc_html_e( 'File Upload', 'mainwp-file-uploader-extension' ); ?></div>
			<div class="ui green progress mainwp-modal-progress">
				<div class="bar"><div class="progress"></div></div>
				<div class="label"></div>
			</div>
			<div class="content">
				<div id="mainwp-modal-message-zone" style="display:none"></div>
			</div>
			<div class="scrolling content">
				<div class="ui selection divided list">
				<?php foreach ( $dbwebsites as $website ) : ?>
					<div class="item mainwpUploaderSiteItem" siteid="<?php echo $website->id; ?>">
						<a href="<?php echo 'admin.php?page=managesites&dashboard='. $website->id; ?>"><?php echo stripslashes( $website->name ); ?></a>
						<div class="ui selection divided list">
							<?php foreach ( $files as $file ) : ?>
								<div class="item mainwpUploaderFileItem" filename="<?php echo $file; ?>" status="queue">
									<?php echo MainWP_Uploader_Utility::get_short_file_name( $file ); ?>
									<span class="status right floated"><span data-tooltip="<?php esc_attr_e( 'Pending. Please wait.', 'mainwp-file-uploader-extension' ); ?>" data-inverted="" data-position="left center"><i class="clock outline icon"></i></span></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
				</div>
			</div>
			<div class="actions">
				<div class="ui two columns grid">
					<div class="left aligned column">

					</div>
					<div class="right aligned column">
						<a href="admin.php?page=Extensions-Mainwp-File-Uploader-Extension" class="ui button"><?php esc_html_e( 'Close', 'mainwp-file-uploader-extension' ); ?></a>
					</div>
				</div>

			</div>
		</div>
		<script type="text/javascript">
		jQuery( '#mainwp-file-uploader-process-modal' ).modal( 'show' );
		</script>

		<input type="hidden" id="mainwp_uploader_upload_file_path" value="<?php echo $path; ?>"/>
		<input type="hidden" id="mainwp_uploader_tmp_files_name" value="<?php echo implode( ',', $files ); ?>"/>
		<?php
		die();
	}

	public function ajax_delete_file() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'uploader-nonce' ) ) {
			exit( 'Invalid request!' );
		}

		$file_name = $_POST['filename'];

		if ( empty( $file_name ) ) {
			die( json_encode( array( 'error' => 'Invalid data.' ) ) );
		}

		$local_file = apply_filters( 'mainwp_getspecificdir', 'uploader' ) . $file_name;

		$success = false;

		if ( file_exists( $local_file ) ) {
			$success = @unlink( $local_file );
		}

		if ( $success ) {
			die( json_encode( array( 'ok' => 1 ) ) );
		}

		die( 'Failed' );
	}

	public function uploader_uploadbulk_file() {
		$site_id   = $_POST['siteId'];
		$file_name = $_POST['filename'];

		if ( empty( $site_id ) || empty( $file_name ) ) {
			die( json_encode( 'FAIL' ) );
		}

		$local_file = apply_filters( 'mainwp_getspecificdir', 'uploader' ) . $file_name;

		if ( ! file_exists( $local_file ) ) {
			die( json_encode( 'NOTEXIST' ) );
		}

		global $mainWPUploaderExtensionActivator;

		$file_url  = apply_filters( 'mainwp_getdownloadurl', 'uploader', $file_name );
		$post_data = array(
			'url'      => base64_encode( $file_url ),
			'filename' => $file_name,
			'path'     => trim( $_POST['path'] ),
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUploaderExtensionActivator->get_child_file(), $mainWPUploaderExtensionActivator->get_child_key(), $site_id, 'uploader_action', $post_data );

		die( json_encode( $information ) );
	}

	public function uploader_delete_temp_file() {
		$tmp_files     = $_POST['tmp_files'];
		$tmp_files     = explode( ',', $tmp_files );
		$uploader_root = apply_filters( 'mainwp_getspecificdir', 'uploader/' );

		if ( is_array( $tmp_files ) ) {
			foreach ( $tmp_files as $file ) {
				$file_path = $uploader_root . $file;
				if ( file_exists( $file_path ) ) {
					@unlink( $file_path ); }
			}
		}

		die( 'SUCCESS' );
	}

	public function scan_upload_folder() {
		$uploader_root = apply_filters( 'mainwp_getspecificdir', 'uploader/' );
		$dir           = @opendir( $uploader_root );
		$scan_files    = array();

		if ( $dir ) {
			while ( ( $file = readdir( $dir ) ) !== false ) {
				if ( substr( $file, 0, 1 ) == '.' ) {
					continue;

				}

				$ext           = pathinfo( $file, PATHINFO_EXTENSION );
				$allowed_types = $this->get_allowed_types();
				if ( ! in_array( $ext, $allowed_types ) ) {
					continue;
				}

				$scan_files[] = $file;
			}

			closedir( $dir );
		}

		return $scan_files;
	}

	public static function render() {
		$new_files = self::get_instance()->scan_upload_folder();
		?>
		<div class="ui segment" id="mainwp-file-uploader">
			<div class="mainwp-main-content">
				<div class="ui segment">
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<div  class="ui form" id="mainwp-uploader-settings-inside">
						<?php if ( self::show_mainwp_message( 'mainwp_file_uploader' ) ) : ?>
						<div class="ui message info">
							<i class="close icon mainwp-notice-dismiss" notice-id="mainwp_file_uploader"></i>
							<p><?php esc_html_e( 'This Extension uploads files directly to your server so treat it the same way you would when uploading a file via FTP.', 'mainwp-file-uploader-extension' ); ?></p>
							<div class="ui bulleted list">
								<div class="item"><?php esc_html_e( 'The Extension will overwrite files of the same name in the same folder.', 'mainwp-file-uploader-extension' ); ?></div>
								<div class="item"><?php esc_html_e( 'If you upload a corrupt file or a file with an error it could break your site.', 'mainwp-file-uploader-extension' ); ?><strong><?php _e( ' MainWP is not responsible for files that you upload to your sites.', 'mainwp-file-uploader-extension' ); ?></strong></div>
								<div class="item"><?php echo sprintf( __( 'For additinal help with the File Uploader extension, please review the extension %shelp documentation%s.', 'mainwp-file-uploader-extension' ), '<a href="https://kb.mainwp.com/docs/file-uploader-extension/" target="_blank">', '</a>' ); ?></div>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( self::show_mainwp_message( 'mainwp_file_uploader_filetype' ) ) : ?>
						<div class="ui message info">
							<i class="close icon mainwp-notice-dismiss" notice-id="mainwp_file_uploader_filetype"></i>
							<?php echo sprintf( __( 'By default, the File Uploader Extension does not support all file types. By using the %sMainWP Custom Dashboard Extension%s, it is possible to add support for any file type. Please review the %shelp document%s for detailed information.', 'mainwp-file-uploader-extension' ), '<a href="https://mainwp.com/extension/mainwp-custom-dashboard-extension/" target="_blank">', '</a>', '<a href="https://kb.mainwp.com/docs/add-support-for-a-custom-file-type-in-file-uploader/" target="_blank">', '</a>' ); ?>
						</div>
						<?php endif; ?>
						<div class="ui grid field">
							<label class="six wide column top aligned"><?php esc_html_e( 'Upload file to insert (replace)', 'mainwp-file-uploader-extension' ); ?></label>
						  <div class="ten wide column file-uploader-drop-zone">
								<div id="mainwp-uploader-file-uploader"></div>
							</div>
						</div>
						<?php if ( count( $new_files ) > 0 ) : ?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( '', 'mainwp-file-uploader-extension' ); ?></label>
						  <div class="ten wide column">
									<div class="ui secondary segment">
										<div class="ui divided list qq-upload-list">
											<?php foreach ( $new_files as $file ) : ?>
												<?php $short_name = MainWP_Uploader_Utility::get_short_file_name( $file ); ?>
												<div class="item qq-upload-success uploader-setting-upload-new-files">
													<div class="ui grid">
														<div class="ui three column row">
															<div class="left aligned middle aligned column qq-upload-file" filename="<?php echo $file; ?>"><strong><i class="file icon"></i> <?php echo $short_name; ?></strong></div>
															<div class="left aligned middle aligned column"><span class="status"></span></div>
															<div class="right aligned middle aligned column"><a href="#" class="ui red mini basic button mainwp-file-uploader-delete-file"><?php _e( 'Remove File', 'mainwp-file-uploader-extension' ); ?></a></div>
														</div>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
							</div>
						</div>
						<?php endif; ?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Enter path to insert (replace)', 'mainwp-file-uploader-extension' ); ?></label>
						  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set where you want to insert the file.', 'mainwp-file-uploader-extension' ); ?>" data-position="top left" data-inverted="">
								<div class="ui left action input">
									<select name="mainwp_uploader_select_path" id="mainwp_uploader_select_path" class="ui dropdown">
										<option value="/">/</option>
										<option value="wp-admin">wp-admin</option>
										<option value="wp-content" selected="selected">wp-content</option>
										<option value="wp-includes">wp-includes</option>
									</select>
									<input type="text" id="mainwp_uploader_path_option" name="mainwp_uploader_path_option" placeholder="<?php esc_attr_e( '/directory/subdirectory/../..', 'mainwp-file-uploader-extension' ); ?>" value="" />
								</div>
							</div>
						</div>
					</div>
					<script type="text/javascript">
						mainwpUploaderCreateUploaderFile();
					</script>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<?php echo self::render_sidebar_options(); ?>
				<div class="mainwp-select-sites">
					<div class="ui header"><?php esc_html_e( 'Select Sites', 'mainwp-file-uploader-extension' ); ?></div>
					<?php do_action( 'mainwp_select_sites_box' ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input type="button" id="mainwp_uploader_btn_upload" name="submit" value="<?php esc_attr_e( 'Upload File', 'mainwp-file-uploader-extension' ); ?>" class="ui big green fluid button" >
					<input type="hidden" id="uploader_nonce" value="<?php echo wp_create_nonce( 'uploader-nonce' ); ?>"/>
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>
		<?php
	}

	/**
	 * Method render_sidebar_options()
	 *
	 * Render sidebar Options.
	 *
	 * @param bool $with_form Default: True. With form tags.
	 *
	 * @return void  Render sidebar Options html.
	 */
	public static function render_sidebar_options( $with_form = true ) {
		$sidebarPosition = get_user_option( 'mainwp_sidebarPosition' );
		if ( false === $sidebarPosition ) {
			$sidebarPosition = 1;
		}
		?>
		<div class="mainwp-sidebar-options ui fluid accordion mainwp-sidebar-accordion">
			<div class="title active"><i class="cog icon"></i> <?php esc_html_e( 'Sidebar Options', 'mainwp' ); ?></div>
			<div class="content active">
				<div class="ui mini form">
					<?php if ( $with_form ) { ?>
					<form method="post">
					<?php } ?>
					<div class="field">
						<label><?php esc_html_e( 'Sidebar position', 'mainwp' ); ?></label>
						<select name="mainwp_sidebar_position" id="mainwp_sidebar_position" class="ui dropdown" onchange="mainwp_sidebar_position_onchange(this)">
							<option value="1" <?php echo ( 1 == $sidebarPosition ? 'selected' : '' ); ?>><?php esc_html_e( 'Right', 'mainwp' ); ?></option>
							<option value="0" <?php echo ( 0 == $sidebarPosition ? 'selected' : '' ); ?>><?php esc_html_e( 'Left', 'mainwp' ); ?></option>
						</select>
					</div>
					<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'onchange_sidebarposition' ); ?>" />
					<?php if ( $with_form ) { ?>
					</form>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="ui fitted divider"></div>
		<?php
	}

	/**
	 * Method show_mainwp_message()
	 *
	 * Check whenther or not to show the MainWP Message.
	 * @param mixed $notice_id Notice ID.
	 *
	 * @return bool true|false.
	 */
	public static function show_mainwp_message( $notice_id ) {
		$status = get_user_option( 'mainwp_notice_saved_status' );
		if ( ! is_array( $status ) ) {
			$status = array();
		}
		if ( isset( $status[ $notice_id ] ) ) {
			return false;
		}
		return true;
	}
}
