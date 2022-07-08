<?php

final class MainWP_ITSEC_File_Change_Settings_Page extends MainWP_ITSEC_Module_Settings_Page {
	private $script_version = 1;


	public function __construct() {
		$this->id = 'file-change';
		$this->title = __( 'File Change', 'l10n-mainwp-ithemes-security-extension' );
		$this->description = __( 'Monitor the site for unexpected file changes.', 'l10n-mainwp-ithemes-security-extension' );
		$this->type = 'recommended';

		parent::__construct();
	}

	public function enqueue_scripts_and_styles() {
		$settings = MainWP_ITSEC_Modules::get_settings( $this->id );

		$logs_page_url = MainWP_ITSEC_Core::get_logs_page_url( 'file_change' );

		$vars = array(
			'button_text'          => isset( $settings['split'] ) && true === $settings['split'] ? __( 'Scan Next File Chunk', 'l10n-mainwp-ithemes-security-extension' ) : __( 'Scan Files Now', 'l10n-mainwp-ithemes-security-extension' ),
			'scanning_button_text' => __( 'Scanning...', 'l10n-mainwp-ithemes-security-extension' ),
			'no_changes'           => __( 'No changes were detected.', 'l10n-mainwp-ithemes-security-extension' ),
			'found_changes'        => sprintf( __( 'Changes were detected. Please check the <a href="%s" target="_blank">logs page</a> for details.', 'l10n-mainwp-ithemes-security-extension' ), esc_url( $logs_page_url ) ),
			'unknown_error'        => __( 'An unknown error occured. Please try again later', 'l10n-mainwp-ithemes-security-extension' ),
			'already_running'      => sprintf( __( 'A scan is already in progress. Please check the <a href="%s" target="_blank">logs page</a> at a later time for the results of the scan.', 'l10n-mainwp-ithemes-security-extension' ), esc_url( $logs_page_url ) ),
			'ABSPATH'              => MainWP_ITSEC_Lib::get_home_path(),
			'nonce'                => wp_create_nonce( 'itsec_do_file_check' ),
		);

		wp_enqueue_script( 'mainwp-itsec-file-change-settings-script', plugins_url( 'js/settings-page.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-file-change-settings-script', 'itsec_file_change_settings', $vars );


		$vars = array(
			'nonce' => wp_create_nonce( 'itsec_jquery_filetree' ),
		);

		wp_enqueue_script( 'mainwp-itsec-file-change-admin-filetree-script', plugins_url( 'js/filetree/jqueryFileTree.js', __FILE__ ), array( 'jquery' ), $this->script_version, true );
		wp_localize_script( 'mainwp-itsec-file-change-admin-filetree-script', 'itsec_jquery_filetree', $vars );


		wp_enqueue_style( 'mainwp-itsec-file-change-admin-filetree-style', plugins_url( 'js/filetree/jqueryFileTree.css', __FILE__ ), array(), $this->script_version );
		wp_enqueue_style( 'mainwp-itsec-file-change-admin-style', plugins_url( 'css/settings.css', __FILE__ ), array(), $this->script_version );
	}

	public function handle_ajax_request( $data ) {
		if ( 'get-filetree-data' === $data['method'] ) {
			MainWP_ITSEC_Response::set_response( $this->get_filetree_data( $data ) );
		}
	}

	protected function render_settings( $form ) {
		$methods = array(
			'exclude' => __( 'Exclude Selected', 'l10n-mainwp-ithemes-security-extension' ),
			'include' => __( 'Include Selected', 'l10n-mainwp-ithemes-security-extension' ),
		);


		$file_list = $form->get_option( 'file_list' );

		if ( is_array( $file_list ) ) {
			$file_list = implode( "\n", $file_list );
		} else {
			$file_list = '';
		}

		$form->set_option( 'file_list', $file_list );

		$split = $form->get_option( 'split' );
		$one_time_button_label = ( true === $split ) ? __( 'Scan Next File Chunk', 'l10n-mainwp-ithemes-security-extension' ) : __( 'Scan Files Now', 'l10n-mainwp-ithemes-security-extension' )

?>
	<div class="hide-if-no-js">
		<p><?php _e( "Press the button below to scan your site's files for changes. Note that if changes are found this will take you to the logs page for details.", 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		<p><?php $form->add_button( 'one_time_check', array( 'value' => $one_time_button_label, 'class' => 'button ui green' ) ); ?></p>
		<div id="itsec_file_change_status"></div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned"><?php _e( 'Excluded Files', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<p class="description"><?php _e( 'Enter a list of file paths to exclude from each File Change scan.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
			<div class="file_list">
				<div class="file_chooser"><div class="jquery_file_tree"></div></div>
				<div class="list_field"><?php $form->add_textarea( 'file_list', array( 'wrap' => 'off' ) ); ?></div>
			</div>
		</div>
	</div>

	<div class="ui grid field">
		<label class="six wide column middle aligned"><?php _e( 'Ignore File Types', 'l10n-mainwp-ithemes-security-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php $form->add_textarea( 'types', array( 'wrap' => 'off', 'cols' => 20, 'rows' => 10 ) ); ?>
			<p class="description"><?php _e( 'File types listed here will not be checked for changes. While it is possible to change files such as images it is quite rare and nearly all known WordPress attacks exploit php, js and other text files.', 'l10n-mainwp-ithemes-security-extension' ); ?></p>
		</div>
	</div>
<?php

	}

	/**
	 * Gets file list for tree.
	 *
	 * Processes the ajax request for retreiving the list of files and folders that can later either
	 * excluded or included.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function get_filetree_data( $data ) {

		global $mainwp_itsec_globals;

		$directory = sanitize_text_field( $data['dir'] );
		$directory = urldecode( $directory );
		$directory = realpath( $directory );

		$base_directory = realpath( MainWP_ITSEC_Lib::get_home_path() );

		// Ensure that requests cannot traverse arbitrary directories.
		if ( 0 !== strpos( $directory, $base_directory ) ) {
			$directory = $base_directory;
		}

		$directory .= '/';

		ob_start();

		if ( file_exists( $directory ) ) {

			$files = scandir( $directory );

			natcasesort( $files );

			if ( 2 < count( $files ) ) { /* The 2 accounts for . and .. */

				echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

				//two loops keep directories sorted before files

				// All files and directories (alphabetical sorting)
				foreach ( $files as $file ) {

					if ( '.' != $file && '..' != $file && file_exists( $directory . $file ) && is_dir( $directory . $file ) ) {

						echo '<li class="directory collapsed"><a href="#" rel="' . htmlentities( $directory . $file ) . '/">' . htmlentities( $file ) . '<div class="itsec_treeselect_control"><img src="' . plugins_url( 'images/redminus.png', __FILE__ ) . '" style="vertical-align: -3px;" title="Add to exclusions..." class="itsec_filetree_exclude"></div></a></li>';

					} elseif ( '.' != $file && '..' != $file && file_exists( $directory . $file ) && ! is_dir( $directory . $file ) ) {

						$ext = preg_replace( '/^.*\./', '', $file );
						echo '<li class="file ext_' . $ext . '"><a href="#" rel="' . htmlentities( $directory . $file ) . '">' . htmlentities( $file ) . '<div class="itsec_treeselect_control"><img src="' . plugins_url( 'images/redminus.png', __FILE__ ) . '" style="vertical-align: -3px;" title="Add to exclusions..." class="itsec_filetree_exclude"></div></a></li>';

					}

				}

				echo "</ul>";

			}

		}

		return ob_get_clean();

	}

}

new MainWP_ITSEC_File_Change_Settings_Page();
