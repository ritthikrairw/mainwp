<?php

class MainWP_Staging {
	protected static $_instance = null;
	protected $settings         = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_staging_site_override_settings', array( $this, 'ajax_override_settings' ) );
		add_action( 'wp_ajax_mainwp_staging_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_mainwp_staging_overview', array( $this, 'ajax_get_overview' ) );
		add_action( 'wp_ajax_mainwp_staging_scanning', array( $this, 'ajax_get_scanning' ) );
		add_action( 'wp_ajax_mainwp_staging_check_disk_space', array( $this, 'ajax_check_disk_space' ) );
		add_action( 'wp_ajax_mainwp_staging_check_clone', array( $this, 'ajax_check_clone' ) );

		add_action( 'wp_ajax_mainwp_staging_cloning', array( $this, 'ajax_cloning' ) );
		add_action( 'wp_ajax_mainwp_staging_clone_database', array( $this, 'ajax_clone_database' ) );
		add_action( 'wp_ajax_mainwp_staging_clone_prepare_directories', array( $this, 'ajax_clone_prepare_directories' ) );
		add_action( 'wp_ajax_mainwp_staging_clone_files', array( $this, 'ajax_clone_files' ) );
		add_action( 'wp_ajax_mainwp_staging_clone_replace_data', array( $this, 'ajax_clone_replace_data' ) );
		add_action( 'wp_ajax_mainwp_staging_clone_finish', array( $this, 'ajax_clone_finish' ) );
		add_action( 'wp_ajax_mainwp_staging_cancel_clone', array( $this, 'ajax_cancel_clone' ) );
		add_action( 'wp_ajax_mainwp_staging_update', array( $this, 'ajax_staging_update' ) );
		add_action( 'wp_ajax_mainwp_staging_cancel_update', array( $this, 'ajax_cancel_update' ) );
		add_action( 'wp_ajax_mainwp_staging_confirm_delete_clone', array( $this, 'ajax_confirm_delete_clone' ) );
		add_action( 'wp_ajax_mainwp_staging_delete_clone', array( $this, 'ajax_delete_clone' ) );
		add_action( 'wp_ajax_mainwp_staging_add_clone_website', array( $this, 'ajax_staging_addclonewebsite' ) );
		add_action( 'wp_ajax_mainwp_staging_delete_clone_website', array( $this, 'ajax_staging_deletewebsite' ) );
	}

	public static function verify_nonce() {
		if ( ! isset( $_REQUEST['_stagingNonce'] ) || ! wp_verify_nonce( $_REQUEST['_stagingNonce'], '_wpnonce_staging' ) ) {
			die( json_encode( array( 'error' => __( 'Invalid request', 'mainwp-staging-extension' ) ) ) );
		}

		$site_id = isset( $_POST['stagingSiteID'] ) ? intval( $_POST['stagingSiteID'] ) : 0;
		if ( empty( $site_id ) ) {
			die( json_encode( array( 'error' => __( 'Empty site id', 'mainwp-staging-extension' ) ) ) );
		}
	}

	public static function on_load_settings_page() {

	}

	public static function on_load_individual_settings_page() {

	}

	public function ajax_save_settings() {
		self::verify_nonce();
		$individual = isset( $_POST['individual'] ) && $_POST['individual'] ? true : false;

		$site_id = $_POST['stagingSiteID'];

		$individual_data = MainWP_Staging_DB::instance()->get_setting_by( 'site_id', $site_id );
		$override        = $individual_data->override;

		if ( $individual ) {
			if ( ! $override ) {
				die( json_encode( array( 'error' => __( 'Update failed! Override General Settings need to be set to Yes.', 'mainwp-staging-extension' ) ) ) );
			}
			$data = $individual_data;
		} else {
			if ( $override ) {
				die( json_encode( array( 'error' => __( 'Update failed! Individual site settings are in use.', 'mainwp-staging-extension' ) ) ) );
			}
			$data = MainWP_Staging_DB::instance()->get_setting_by( 'site_id', 0 ); // general settings
		}

		$settings = unserialize( $data->settings );
		if ( empty( $settings ) || ! is_array( $settings ) ) {
			die( json_encode( array( 'error' => 'Empty settings' ) ) );
		}

		$filters = array(
			'queryLimit',
			'fileLimit',
			'batchSize',
			'cpuLoad',
			'delayRequests',
			'disableAdminLogin',
			'querySRLimit',
			'maxFileSize',
			// 'wpSubDirectory', //removed
			'debugMode',
			'unInstallOnDelete',
			'checkDirectorySize',
			'optimizer',
			// 'loginSlug' // removed
		);
		$save_fields = array();
		foreach ( $filters as $field ) {
			if ( isset( $settings[ $field ] ) ) {
				$save_fields[ $field ] = $settings[ $field ];
			}
		}
		global $mainwp_StagingExtensionActivator;
		$post_data = array(
			'mwp_action' => 'save_settings',
			'settings'   => $save_fields,
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_staging_addclonewebsite() {
		self::verify_nonce();

		$site_id   = $_POST['stagingSiteID'];
		$cloneID   = isset( $_POST['clone'] ) ? $_POST['clone'] : '';
		$clone_url = isset( $_POST['clone_url'] ) ? $_POST['clone_url'] : '';

		if ( empty( $cloneID ) || empty( $clone_url ) ) {
			die( json_encode( array( 'error' => __( 'Empty clone data', 'mainwp-staging-extension' ) ) ) );
		}

		global $mainwp_StagingExtensionActivator;

		$information = apply_filters( 'mainwp_clonesite', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, $cloneID, $clone_url, $force_update = true );
		if ( is_array( $information ) && isset( $information['siteid'] ) && ! empty( $information['siteid'] ) ) {
			$update = array(
				'site_id'         => $site_id,
				'clone_id'        => $cloneID,
				'clone_url'       => $clone_url,
				'staging_site_id' => $information['siteid'],
			);
			MainWP_Staging_DB::instance()->update_staging_site( $update );
		}
		die( json_encode( $information ) );
	}

	public function ajax_staging_deletewebsite() {
		self::verify_nonce();

		$site_id = $_POST['stagingSiteID'];
		$cloneID = isset( $_POST['clone'] ) ? $_POST['clone'] : '';

		if ( empty( $cloneID ) ) {
			die( json_encode( array( 'error' => __( 'Empty clone id', 'mainwp-staging-extension' ) ) ) );
		}

		$staging_site = MainWP_Staging_DB::instance()->get_staging_site( $site_id, $cloneID );
		$information  = array();
		global $mainwp_StagingExtensionActivator;
		if ( $staging_site && property_exists( $staging_site, 'clone_url' ) ) {
			// $information = apply_filters( 'mainwp_deleteclonesite', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, $staging_site->clone_url );
			// will release when dashboard 3.4.9 released - did it
			$information = apply_filters( 'mainwp_delete_clonesite', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $staging_site->clone_url );
			MainWP_Staging_DB::instance()->delete_staging_site( $site_id, $cloneID );
		}
		die( json_encode( $information ) );
	}

	public function ajax_get_overview() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];
		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'get_overview',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );

		if ( is_array( $information ) && isset( $information['availableClones'] ) ) {
			$available_clones = $information['availableClones'];
			if ( ! is_array( $available_clones ) ) {
				$available_clones = array();
			}
			self::instance()->sync_staging_site_data( $site_id, $available_clones );
		} else {
			die( json_encode( $information ) );
		}
		ob_start();

			// Existing Staging Sites
		?>

			<table class="ui single line table mainwp-staging-extension-clones-table" id="mainwp-staging-extension-clones-table">
				<thead>
					<tr>
						<th><?php _e( 'Site', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'URL', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Slug', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'DB Prefix', 'mainwp-staging-extension' ); ?></th>
						<th class="no-sort collapsing"><?php _e( 'Actions', 'mainwp-staging-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $available_clones ) ) : ?>
						<?php
						foreach ( $available_clones as $name => $data ) :
							$urlLogin = $data['url'] . '/wp-login.php';
							$prefix   = isset( $data['prefix'] ) ? $data['prefix'] : '';
							?>
							<tr id="<?php echo esc_html( $data['directoryName'] ); ?>" directory-name="<?php echo esc_html( $data['directoryName'] ); ?>" class="mwp-wpstg-clone">
								<td><strong><a href="<?php echo esc_html( $urlLogin ); ?>" target="_blank"><?php echo esc_html( $data['directoryName'] ); ?></a></strong></td>
								<td><a href="<?php echo esc_html( $data['url'] ); ?>" target="_blank"><?php echo esc_html( $data['url'] ); ?></a></td>
								<td><?php echo '/' . esc_html( $data['directoryName'] ) . '/'; ?></td>
								<td><?php echo esc_html( $prefix ); ?></td>
								<td>
									<a href="<?php echo esc_html( $urlLogin ); ?>" class="ui mini basic green button wpstg-open-clone mwp-wpstg-clone-action" target="_blank"><?php _e( 'Open', 'mainwp-staging-extension' ); ?></a>
							<a href="#" class="ui mini green button wpstg-execute-clone mwp-wpstg-clone-action" data-clone="<?php echo esc_html( $name ); ?>"><?php _e( 'Update', 'mainwp-staging-extension' ); ?></a>
							<a href="#" class="ui mini button wpstg-remove-clone mwp-wpstg-clone-action" data-clone="<?php echo esc_html( $name ); ?>"><?php _e( 'Delete', 'mainwp-staging-extension' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th><?php _e( 'Site', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'URL', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'Slug', 'mainwp-staging-extension' ); ?></th>
						<th><?php _e( 'DB Prefix', 'mainwp-staging-extension' ); ?></th>
						<th class="no-sort collapsing"><?php _e( 'Actions', 'mainwp-staging-extension' ); ?></th>
					</tr>
				</tfoot>
			</table>
			<script type="text/javascript">
			jQuery( '#mainwp-staging-extension-clones-table' ).DataTable( {
				"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
				"order": [ [ 1, "asc" ] ],
				"language": { "emptyTable": "No staging sites found." }
			} );
			</script>
			<i class="notched loading circle icon" style="display:none;"></i> <div class="status" style="display:none;"></div>
			<div id="wpstg-removing-clone"></div>
		<?php
		// End Existing Clones

		$html = ob_get_clean();

		die( json_encode( array( 'result' => $html ) ) );
	}

	public function sync_staging_site_data( $site_id, $available_clones ) {
		if ( empty( $site_id ) ) {
			return false;
		}

			$stagings_sites    = MainWP_Staging_DB::instance()->get_stagings_of_site( $site_id );
			$current_clone_ids = array();
		if ( is_array( $stagings_sites ) ) {
			foreach ( $stagings_sites as $val ) {
				  $current_clone_ids[ $val->clone_id ] = $val->clone_url;
			}
		}

			$clone_ids = array();
		if ( is_array( $available_clones ) ) {
			foreach ( $available_clones as $name => $data ) {
				$clone_ids[ $data['directoryName'] ] = $data['url'];
			}
		}

			global $mainwp_StagingExtensionActivator;

		foreach ( $clone_ids as $clone_id => $clone_url ) {
			if ( ! isset( $current_clone_ids[ $clone_id ] ) ) {
				// add website into dashboard database
				$result = apply_filters( 'mainwp_clonesite', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, $clone_id, $clone_url ); // no need to force update clone site
				if ( is_array( $result ) && isset( $result['siteid'] ) && ! empty( $result['siteid'] ) ) {
					$update = array(
						'site_id'         => $site_id,
						'clone_id'        => $clone_id,
						'clone_url'       => $clone_url,
						'staging_site_id' => $result['siteid'],
					);
					MainWP_Staging_DB::instance()->update_staging_site( $update );
				}
			}
		}

		foreach ( $current_clone_ids as $current_clone_id => $current_clone_url ) {
			if ( ! isset( $clone_ids[ $current_clone_id ] ) ) {
				// remove website on dashboard database
				apply_filters( 'mainwp_deleteclonesite', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, $current_clone_url );
				// will use new hook when dashboard 3.4.9 released
				// apply_filters( 'mainwp_delete_clonesite', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $current_clone_url );
				MainWP_Staging_DB::instance()->delete_staging_site( $site_id, $current_clone_id );
			}
		}
	}

	public function ajax_get_scanning() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data = array(
			'mwp_action' => 'get_scan',
		);
		if ( isset( $_POST['clone'] ) ) {
			$post_data['clone'] = $_POST['clone'];
		}
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );

		if ( is_array( $information ) && isset( $information['options'] ) ) {
			$options          = unserialize( $information['options'] );
			$directoryListing = $information['directoryListing'];
			$prefix           = $information['prefix'];
		} else {
			  die( json_encode( $information ) );
		}

		global $mainwp_StagingExtensionActivator;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), array( $site_id ), array() );
		if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
			$website = current( $dbwebsites );
		}

		$allowed = array(
			'div'   => array(
				'id'    => array(),
				'class' => array(),
				'style' => array(),
			),
			'input' => array(
				'type'    => array(),
				'class'   => array(),
				'name'    => array(),
				'name'    => array(),
				'checked' => array(),
				'value'   => array(),
			),
			'a'     => array(
				'href'  => array(),
				'class' => array(),
			),
			'span'  => array(
				'class' => array(),
			),
		);

		ob_start();
		?>
		<div class="ui yellow message" id="mwp-wpstg-clone-id-error" style="display:none;">
		<?php echo __( 'Probably not enough free disk space to create a staging site. You can continue but its likely that the copying process will fail.', 'mainwp-staging-extension' ); ?>
	</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Staging site name', 'mainwp-staging-extension' ); ?></label>
			<div class="ten wide column">
				<input type="text" id="wpstg-new-clone-id"  value="<?php echo esc_html( $options->current ); ?>"
																			  <?php
																				if ( null !== $options->current ) {
																					echo " disabled='disabled'";}
																				?>
					>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Staging site URL', 'mainwp-staging-extension' ); ?></label>
			<div class="ten wide column"><?php echo ( $website ? $website->url : '' ); ?><span id="wpstg_site_url"><?php echo esc_html( $options->current ); ?></span>
			</div>
		</div>

   <div class="ui secondary segment">
			<h3 class="ui divided header" ><?php echo __( 'DB Tables', 'mainwp-staging-extension' ); ?></h3>
			<div class="ui info message">
				<?php echo __( 'Uncheck the tables you do not want to copy. (If the copy process was previously interrupted, successfully copied tables are greyed out and copy process will skip these ones)', 'mainwp-staging-extension' ); ?>
			</div>
		 <div class="mwp-wpstg-tab-section" id="wpstg-scanning-db">
		   <ul class="mwp_wpstg_checkboxes">
		   <?php
			foreach ( $options->tables as $table ) :
				$attributes  = in_array( $table->name, $options->excludedTables ) ? '' : 'checked';
				$attributes .= in_array( $table->name, $options->clonedTables ) ? ' disabled' : '';
				?>
		  <li>
						<div class="wpstg-db-table ui checkbox">

				 <input class="wpstg-db-table-checkboxes" type="checkbox" name="<?php echo esc_html( $table->name ); ?>" <?php echo esc_html( $attributes ); ?>>
								 <label for="<?php echo esc_html( $table->name ); ?>">
				 <?php echo esc_html( $table->name ); ?> <span class="mwp-wpstg-size-info"><?php echo $this->formatSize( $table->size ); ?></span>
				 </label>

			</div>
					</li>
				 <?php endforeach ?>
				 </ul>

				 <div>
				   <a href="#" class="wpstg-button-unselect ui button"><?php _e( 'Un-check all', 'mainwp-staging-extension' ); ?></a>
					 <a href="#" class="wpstg-button-select ui button" tblprefix="<?php echo esc_html( $prefix ); ?>"><?php echo esc_html( $prefix ); ?></a>
				 </div>
			 </div>
			<h3 class="ui dividing header"><?php echo __( 'Files', 'mainwp-staging-extension' ); ?></h3>
			<div class="ui info message">
				<?php echo __( 'Uncheck the folders you do not want to copy. Click on them for expanding!', 'mainwp-staging-extension' ); ?>
			</div>
	   <div class="mwp-wpstg-tab-section" id="wpstg-scanning-files">
		  <?php echo wp_kses( $directoryListing, $allowed ); ?>

			  <h3 class="ui dividing header"><?php echo __( 'Extra directories to copy', 'mainwp-staging-extension' ); ?></h3>
		 <textarea id="wpstg_extraDirectories" name="wpstg_extraDirectories" style="width:100%; height:120px;"></textarea>
			  <br/><em><?php echo __( 'Enter one folder path per line. Folders must start with absolute path: ' . esc_html( $options->root ), 'mainwp-staging-extension' ); ?></em>
				 <p>
						 <?php
							if ( isset( $options->clone ) ) {
								echo __( 'All files are copied into: ', 'mainwp-staging-extension' ) . esc_html( $options->root . $options->clone );
							}
							?>
				 </p>
			 </div>
		 </div>
		<br>

		 <?php

			if ( null !== $options->current ) {
				$label  = __( 'Update Clone', 'mainwp-staging-extension' );
				$action = 'staging_update';
			} else {
				 $label  = __( 'Create Staging Site', 'mainwp-staging-extension' );
				 $action = 'staging_cloning';
			}

			?>

		<div class="ui divider"></div>

		 <?php if ( null !== $options->current ) { ?>
<button type="button" class="wpstg-prev-step-link wpstg-link-btn ui big button"><?php _e( 'Back', 'mainwp-staging-extension' ); ?></button>
<?php } ?>

		<button type="button" id="wpstg-start-cloning" class="wpstg-next-step-link wpstg-link-btn ui big green right floated button" data-action="<?php echo $action; ?>">
		<?php echo esc_html( $label ); ?>
	</button>

	<a href="#" id="mwp-wpstg-check-space" class="ui big green basic button"><?php _e( 'Check Disk Space', 'mainwp-staging-extension' ); ?></a>
		<span id="mwp-wpstg-working-overview">
	  <i class="fa fa-spinner fa-pulse fa-3x" style="display:none;"></i> <span class="status" style="display:none;"></span>
		</span>
		 <?php
			$html = ob_get_clean();
			die( json_encode( array( 'result' => $html ) ) );
	}

	public function formatSize( $bytes, $precision = 2 ) {
		if ( (float) $bytes < 1 ) {
			return '';
		}
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes = (float) $bytes;
		$base  = log( $bytes ) / log( 1000 ); // 1024 would be for MiB KiB etc
		$pow   = pow( 1000, $base - floor( $base ) ); // Same rule for 1000

		return round( $pow, $precision ) . ' ' . $units[ (int) floor( $base ) ];
	}

	public function ajax_check_disk_space() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'check_disk_space',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_check_clone() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'check_clone',
			'cloneID'    => $_POST['cloneID'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_cloning() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data = array(
			'mwp_action'          => 'start_clone',
			'cloneID'             => $_POST['cloneID'],
			'cloneName'           => $_POST['cloneName'],
			'includedTables'      => $_POST['includedTables'],
			'excludedTables'      => isset( $_POST['excludedTables'] ) ? $_POST['excludedTables'] : '',
			'includedDirectories' => isset( $_POST['includedDirectories'] ) ? $_POST['includedDirectories'] : '',
			'excludedDirectories' => isset( $_POST['excludedDirectories'] ) ? $_POST['excludedDirectories'] : '',
			'extraDirectories'    => isset( $_POST['extraDirectories'] ) ? $_POST['extraDirectories'] : '',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( $information );
	}

	public function ajax_clone_database() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];
		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'clone_database',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_clone_prepare_directories() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'prepare_directories',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_clone_files() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'copy_files',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_clone_replace_data() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'replace_data',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_clone_finish() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'clone_finish',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( json_encode( $information ) );
	}

	public function ajax_confirm_delete_clone() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'delete_confirmation',
			'clone'      => $_POST['clone'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );

		if ( is_array( $information ) && isset( $information['clone'] ) ) {
			$clone            = $information['clone'];
			$delete_getTables = $information['deleteTables'];
		} else {
			die( json_encode( $information ) );
		}

		ob_start();

		?>
	<div class="ui yellow message">
	  <strong><?php _e( 'Attention: Check carefully if these database tables and files are safe to delete and do not belong to your live site!', 'mainwp-staging-extension' ); ?></strong><br/>
		<?php _e( 'Clone name:', 'mainwp-staging-extension' ); ?> <?php echo esc_html( $clone['directoryName'] ); ?><br/>
		<?php _e( 'Usually the preselected data can be deleted without any risk. But in case something goes wrong you better check it first.', 'mainwp-staging-extension' ); ?>
	</div>
	<div class="ui secondary segment">
			<h3 class="ui divided header" ><?php echo __( 'DB Tables', 'mainwp-staging-extension' ); ?></h3>

			<!-- Database -->
	  <div class="mwp-wpstg-tab-section" id="wpstg-scanning-db">
				<div class="ui info message">
					<?php echo __( 'Uncheck the tables you do not want to copy. (If the copy process was previously interrupted, successfully copied tables are greyed out and copy process will skip these ones)', 'mainwp-staging-extension' ); ?>
				</div>
				<?php foreach ( $delete_getTables as $table ) : ?>
					<div class="wpstg-db-table  ui checkbox">
						  <input class="wpstg-db-table-checkboxes" type="checkbox" name="<?php echo esc_html( $table['name'] ); ?>" checked>
						  <label>
													  <?php echo esc_html( $table['name'] ); ?>
														<span class="mwp-wpstg-size-info">
								<?php echo esc_html( $table['size'] ); ?>
							</span>
					  </label>
					</div>
				<?php endforeach ?>
		<div>
		  <a href="#" class="wpstg-button-unselect ui button"><?php _e( 'Un-check all', 'mainwp-staging-extension' ); ?></a>
				</div>
			</div>
			<!-- /Database -->

			<h3 class="ui dividing header"><?php echo __( 'Files', 'mainwp-staging-extension' ); ?></h3>

			<!-- Files -->
			<div class="mwp-wpstg-tab-section" id="wpstg-scanning-files">
					<div class="ui info message">
						<?php echo __( 'Uncheck the folders you do not want to copy. Click on them for expanding!', 'mainwp-staging-extension' ); ?>
					</div>
					<div class="wpstg-dir ui checkbox">
					<input id="deleteDirectory" type="checkbox" class="wpstg-check-dir" name="deleteDirectory" value="1" checked data-deletepath="<?php echo urlencode( $clone['path'] ); ?>">
					<label>
					  <?php echo esc_html( $clone['path'] ); ?>
					  <span class="mwp-wpstg-size-info"></span>
					</label>
				</div>
			</div>
			<!-- /Files -->
		</div>
				<div class="ui hidden divider"></div>
		<div>
		<a href="#" class="wpstg-link-btn ui button" id="wpstg-cancel-removing"><?php _e( 'Cancel', 'mainwp-staging-extension' ); ?></a>
			<a href="#" class="wpstg-link-btn ui button green" id="wpstg-remove-clone" data-clone="<?php echo esc_html( $clone['name'] ); ?>"><?php echo __( 'Remove', 'mainwp-staging-extension' ); ?></a>
		</div>
		<?php
		$html = ob_get_clean();
		die( $html );
	}

	public function ajax_delete_clone() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action'     => 'delete_clone',
			'clone'          => $_POST['clone'],
			'excludedTables' => isset( $_POST['excludedTables'] ) ? $_POST['excludedTables'] : '',
			'deleteDir'      => $_POST['deleteDir'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data, $raw = true );  // $raw get raw response from the child site
		die( $information );
	}

	public function ajax_cancel_clone() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];
		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'cancel_clone',
			'clone'      => $_POST['clone'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data, $raw = true ); // $raw get raw response from the child site
		die( $information );
	}

	public function ajax_staging_update() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;

		global $mainwp_StagingExtensionActivator;
		$post_data = array(
			'mwp_action'          => 'staging_update',
			'cloneID'             => $_POST['cloneID'],
			'includedTables'      => $_POST['includedTables'],
			'excludedTables'      => $_POST['excludedTables'],
			'includedDirectories' => $_POST['includedDirectories'],
			'excludedDirectories' => $_POST['excludedDirectories'],
			'extraDirectories'    => $_POST['extraDirectories'],
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data );
		die( $information );
	}

	public function ajax_cancel_update() {
		self::verify_nonce();
		$site_id = $_POST['stagingSiteID'];

		global $mainwp_StagingExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'cancel_update',
			'clone'      => $_POST['clone'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $site_id, 'wp_staging', $post_data, $raw = true ); // $raw get raw response from the child site
		die( $information );
	}


	function ajax_override_settings() {
		$websiteId = $_POST['stagingSiteID'];
		if ( empty( $websiteId ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) );
		}
		$update = array(
			'site_id'  => $websiteId,
			'override' => $_POST['override'],
		);

		MainWP_Staging_DB::instance()->update_setting( $update );
		die( json_encode( array( 'ok' => 1 ) ) );
	}

	public static function ajax_load_sites( $what = '', $echo = false ) {
		$all_sites = MainWP_Staging_Plugin::get_instance()->get_websites_installed_the_plugin();
		$error     = '';

		if ( count( $all_sites ) == 0 ) {
			$error = __( 'No websites were found with the WP Staging (Pro) plugin installed.', 'mainwp-staging-extension' );
		}

		if ( $what == '' ) {
			$what = isset( $_POST['what'] ) ? $_POST['what'] : '';
		}

		$html = '';
		if ( empty( $error ) ) {
			ob_start();
			?>
			<div class="ui modal" id="mainwp-staging-sync-settings-modal">
				<div class="header"><?php echo __( 'WP Staging Synchronization', 'mainwp-staging-extension' ); ?></div>
				<div class="scrolling content">
					<div class="ui relaxed divided list">
						<?php foreach ( $all_sites as $website ) : ?>
						<div class="item"><?php echo $website['name']; ?>
							<span class="siteItemProcess right floated" action="" site-id="<?php echo $website['id']; ?>" status="queue"><span class="status"><i class="clock outline icon"></i></span> <i style="display: none;" class="notched circle loading icon"></i></span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="actions">
					<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-staging-extension' ); ?></div>
				</div>
			</div>

			<?php if ( $what != '' ) : ?>
	  <script type="text/javascript">
				jQuery( '#mainwp-staging-sync-settings-modal' ).modal( 'show' );
		jQuery( document ).ready(function ($) {

		  staging_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
		  mainwp_staging_save_settings_start_next('<?php echo esc_js( $what ); ?>');
		} );
	  </script>
	  <?php endif; ?>
			<?php
			$html = ob_get_clean();

		}

		if ( $echo ) {
			if ( ! empty( $error ) ) {
				echo '<div class="ui red message" >' . $error . '</div>';
				return;
			}
			echo $html;
		} else {
			if ( ! empty( $error ) ) {
				die( json_encode( array( 'error' => $error ) ) );
			}
			die( json_encode( array( 'result' => $html ) ) );
		}
	}

	public static function render() {
		$website = null;
		if ( self::is_manage_site() ) {
			if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
				global $mainwp_StagingExtensionActivator;
				$option     = array(
					'plugin_upgrades' => true,
					'plugins'         => true,
				);
				$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), array( $_GET['id'] ), array(), $option );

				if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
					$website = current( $dbwebsites );
				}
			}

			$error = '';
			if ( empty( $website ) || empty( $website->id ) ) {
				$error = __( 'Error! Site not found.', 'mainwp-staging-extension' );
			} else {
				$activated = false;
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'wp-staging/wp-staging.php' == strtolower( $plugin['slug'] ) ) || ( 'wp-staging-pro/wp-staging-pro.php' == strtolower( $plugin['slug'] ) ) ) {
								if ( $plugin['active'] ) {
									$activated = true;
								}
								break;
							}
						}
					}
				}
				if ( ! $activated ) {
					$error = __( 'WP Staging (Pro) plugin is not installed or activated on the site.', 'mainwp-staging-extension' );
				}
			}

			if ( ! empty( $error ) ) {
				do_action( 'mainwp_pageheader_sites', 'Staging' );
				echo '<div class="ui form segment"><div class="ui red message">' . $error . '</div></div>';
				do_action( 'mainwp_pagefooter_sites', 'Staging' );
				return;
			}
		}
		self::render_tabs();
	}

	public static function render_tabs() {
		$is_manager_site = self::is_manage_site();
		$site_id         = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

		$show_dashboard_tab = $show_settings_tab = $show_stagings_tab = $show_new_tab = false;
		$staging_list_link  = $dashboard_link = $new_staging_link = '';

		if ( $is_manager_site ) {
			if ( isset( $_GET['tab'] ) ) {
				if ( $_GET['tab'] == 'settings' ) {
					$show_settings_tab = true;
				} elseif ( $_GET['tab'] == 'new' ) {
					$show_new_tab = true;
				} else {
					$show_stagings_tab = true;
				}
			} else {
				$show_stagings_tab = true;
			}

			$staging_list_link = '<a href="admin.php?page=ManageSitesStaging&id=' . $site_id . '" class="item ' . ( $show_stagings_tab ? 'active' : '' ) . '"><i class="WordPress simple icon"></i>' . __( 'Staging sites', 'mainwp-staging-extension' ) . '</a>';
			$new_staging_link  = '<a href="admin.php?page=ManageSitesStaging&tab=new&id=' . $site_id . '" class="item ' . ( $show_new_tab ? 'active' : '' ) . '"><i class="plus icon"></i>' . __( 'Create New', 'mainwp-staging-extension' ) . '</a>';
			$setings_link      = '<a href="admin.php?page=ManageSitesStaging&tab=settings&id=' . $site_id . '" class="item ' . ( $show_settings_tab ? 'active' : '' ) . '"><i class="cog icon"></i>' . __( 'WP Staging Settings', 'mainwp-staging-extension' ) . '</a>';

		} else {
			if ( isset( $_GET['tab'] ) ) {
				if ( $_GET['tab'] == 'settings' ) {
					$show_settings_tab = true;
				} else {
					$show_dashboard_tab = true;
				}
			} else {
				$show_dashboard_tab = true;
			}
			$dashboard_link = '<a href="admin.php?page=Extensions-Mainwp-Staging-Extension" class="item ' . ( $show_dashboard_tab ? 'active' : '' ) . '"><i class="tasks icon"></i>' . __( 'WP Staging Dashboard', 'mainwp-staging-extension' ) . '</a>';
			$setings_link   = '<a href="admin.php?page=Extensions-Mainwp-Staging-Extension&tab=settings" class="item ' . ( $show_settings_tab ? 'active' : '' ) . '"><i class="cog icon"></i>' . __( 'Settings', 'mainwp-staging-extension' ) . '</a>';
		}

		if ( $is_manager_site ) {
			do_action( 'mainwp_pageheader_sites', 'Staging' );
		}
		?>
	<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-wp-staging-menu">
		<?php echo $dashboard_link . $staging_list_link . $new_staging_link . $setings_link; ?>
	</div>

		<?php
		$perform_settings_update = $perform_what = false;
		$updated                 = self::instance()->handlePosting();

		if ( ! $is_manager_site ) {
			global $mainwp_StagingExtensionActivator;
			$websites  = apply_filters( 'mainwp_getsites', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), null );
			$sites_ids = array();
			if ( is_array( $websites ) ) {
				foreach ( $websites as $site ) {
					$sites_ids[] = $site['id'];
				}
				unset( $websites );
			}
			$option = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);

			$dbwebsites     = apply_filters( 'mainwp_getdbsites', $mainwp_StagingExtensionActivator->get_child_file(), $mainwp_StagingExtensionActivator->get_child_key(), $sites_ids, array(), $option );
			$selected_group = 0;

			if ( isset( $_POST['mainwp_staging_plugin_groups_select'] ) ) {
				$selected_group = intval( $_POST['mainwp_staging_plugin_groups_select'] );
			}

			$pluginDataSites = array();
			if ( count( $sites_ids ) > 0 ) {
				$pluginDataSites = MainWP_Staging_DB::instance()->get_staging_data( $sites_ids );
			}

			$dbwebsites_data = MainWP_Staging_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites, $selected_group, $pluginDataSites );

			unset( $dbwebsites );
			unset( $pluginDataSites );

			if ( $show_dashboard_tab ) {
				?>
				<div class="mainwp-actions-bar">
					<div class="ui grid">
						<div class="ui two column row">
							<div class="middle aligned column ui mini form">
								<select class="ui dropdown" id="mwp_staging_plugin_action">
									<option value="-1"><?php _e( 'Bulk Actions', 'mainwp-staging-extension' ); ?></option>
									<option value="activate-selected"><?php _e( 'Activate', 'mainwp-staging-extension' ); ?></option>
									<option value="update-selected"><?php _e( 'Update', 'mainwp-staging-extension' ); ?></option>
									<option value="hide-selected"><?php _e( 'Hide', 'mainwp-staging-extension' ); ?></option>
									<option value="show-selected"><?php _e( 'Unhide', 'mainwp-staging-extension' ); ?></option>
					</select>
								<input type="button" value="<?php _e( 'Apply' ); ?>" class="ui basic mini button action" id="staging_plugin_doaction_btn" name="staging_plugin_doaction_btn">
								<?php do_action( 'mainwp_staging_actions_bar_left' ); ?>
					</div>
							<div class="right aligned middle aligned column">
								<?php do_action( 'mainwp_staging_actions_bar_right' ); ?>
					</div>
				</div>
					</div>
				</div>
				<?php
				MainWP_Staging_Plugin::gen_dashboard_tab( $dbwebsites_data );
			}
		}

		if ( $perform_settings_update && ! $is_manager_site ) {
			self::ajax_load_sites( $perform_what, true );
		} else {
			if ( $is_manager_site ) {
					echo '<div class="ui form segment">';
				if ( $show_settings_tab ) {
						self::render_site_settings_box();
					  self::render_settings_tab();
				} elseif ( $show_stagings_tab ) {
					self::render_stagings_tab( 'stagings' );
				} elseif ( $show_new_tab ) {
					self::render_stagings_tab( 'new' );
				}
				if ( $updated ) {
					?>
				<div class="ui green message"><?php _e( 'Settings saved successfully.', 'mainwp-staging-extension' ); ?></div>

					<?php
					if ( $is_manager_site ) {
						update_option( 'mainwp_staging_perform_individual_setting', 'yes' );
					} else {
						$perform_settings_update = true;
						$perform_what            = 'save_settings';
					}
				}
					echo '</div>';
			} else {
				if ( $show_settings_tab ) {
						echo '<div class="ui form segment">';
					if ( $updated ) {
						?>
					<div class="ui green message"><?php _e( 'Settings saved successfully.', 'mainwp-staging-extension' ); ?></div>
						<?php
						$perform_settings_update = true;
						$perform_what            = 'save_settings';
					}
						self::ajax_load_sites( $perform_what, true );
					self::render_settings_tab();
						echo '</div>';
				}
			}
		}

		if ( $is_manager_site ) {
			do_action( 'mainwp_pagefooter_sites', 'Staging' );
		}
	}

	public static function render_site_settings_box() {
		$site_id         = isset( $_GET['id'] ) ? $_GET['id'] : 0;
		$individual_data = MainWP_Staging_DB::instance()->get_setting_by( 'site_id', $site_id );
		$override        = $individual_data ? $individual_data->override : 0;
		?>
	<script type="text/javascript">
		<?php
		if ( $site_id ) {
			if ( get_option( 'mainwp_staging_perform_individual_setting' ) == 'yes' ) {
				delete_option( 'mainwp_staging_perform_individual_setting' );
				?>
		  jQuery(document).ready(function ($) {
			mainwp_staging_save_individual_settings( <?php echo $site_id; ?> );
		  } );
				<?php
			}
		}
		?>
	</script>

	<input type="hidden" name="mainwp_staging_site_id" value="<?php echo $site_id; ?>" />

		<h3 class="ui dividing header"><?php echo __( 'Staging Site Settings', 'mainwp-staging-extension' ); ?></h3>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Override General Settings', 'mainwp-staging-extension' ); ?></label>
		  <div class="two wide column ui toggle checkbox">
				<input type="checkbox" id="mainwp_staging_override_general_settings" name="mainwp_staging_override_general_settings" <?php echo( $override ? 'checked="checked"' : '' ); ?> value="1"/>
		  </div>
			<div class="eight wide column">
				<div id="mwp_staging_setting_ajax_message">
					<span class="staging_change_override_working"></span>
					<div class="ui message info status" style="display:none;"></div>
					<span class="loading" style="display:none;"><i class="nothched circle loading icon"></i> <?php _e( 'Saving ...', 'mainwp-staging-extension' ); ?></span>
					<div style="display: none" class="detailed"></div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function getCPULoadSetting( $value ) {
		switch ( $value ) {
			case 'high':
				$cpuLoad = 0;
				break;
			case 'medium':
				$cpuLoad = 1000;
				break;
			case 'low':
				$cpuLoad = 0;
				break;
			case 'default':
			default:
				$cpuLoad = 0;
				break;
		}

		return $cpuLoad;
	}

	private function handlePosting() {
		if ( isset( $_POST['_wpnonce_mainwp_wpstg_settings'] ) && wp_verify_nonce( $_POST['_wpnonce_mainwp_wpstg_settings'], '_wpnonce_mainwp_wpstg_settings' ) ) {
			$site_id  = intval( $_POST['mainwp_staging_site_id'] ); // site_id = 0 is general settings
			$settings = $this->sanitizeData( $_POST['mainwp_wp_stg_settings'] );
			$update   = array(
				'site_id'  => $site_id,
				'settings' => serialize( $settings ),
			);
			MainWP_Staging_DB::instance()->update_setting( $update );
			return true;
		}
		return false;
	}

	private function sanitizeData( $data = array() ) {
		$sanitized = array();
		foreach ( $data as $key => $value ) {
			$sanitized[ $key ] = ( is_array( $value ) ) ? $this->sanitizeData( $value ) : htmlspecialchars( $value );
		}
		return $sanitized;
	}

	public static function render_settings_tab() {
		$site_id   = isset( $_GET['id'] ) ? $_GET['id'] : 0;
		$site_data = MainWP_Staging_DB::instance()->get_setting_by( 'site_id', $site_id );
		$settings  = array();
		if ( $site_data ) {
			$settings = unserialize( $site_data->settings );
		}

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		?>

	 <form method="post" action="">
		<input type="hidden" name="mainwp_staging_site_id" value="<?php echo $site_id; ?>">
				<h3 class="ui dividing header"><?php echo __( 'WP Staging Settings', 'mainwp-staging-extension' ); ?></h3>
				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'DB copy query limit', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column">
						<input id="mainwp_wp_stg_settings[queryLimit]" name="mainwp_wp_stg_settings[queryLimit]" value="<?php echo isset( $settings['queryLimit'] ) ? esc_attr( $settings['queryLimit'] ) : 10000; ?>" type="text">
						<div class="ui mini message">
							<?php _e( 'Number of DB rows, that will be copied within one ajax request. The higher the value the faster the database copy process. To find out the highest possible values try a high value like 1.000 or more. If you get timeout issues, lower it until you get no more errors during copying process.', 'mainwp-staging-extension' ); ?>
						</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'DB search &amp; replace limit', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column">
					  <input id="mainwp_wp_stg_settings[querySRLimit]" name="mainwp_wp_stg_settings[querySRLimit]" type="text" step="1" max="999999" min="0" value="<?php echo isset( $settings['queryLimit'] ) ? esc_attr( $settings['querySRLimit'] ) : 5000; ?>">
					<div class="ui mini message">
						<?php _e( 'Number of DB rows, that are processed within one ajax query. The higher the value the faster the database search &amp; replace process. This is a high memory consumptive process. If you get timeouts lower this value!.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<?php $fileLimit = isset( $settings['fileLimit'] ) ? intval( $settings['fileLimit'] ) : 1; ?>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'File copy limit', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column">
						<select id="mainwp_wp_stg_settings[fileLimit]" name="mainwp_wp_stg_settings[fileLimit]" class="ui dropdown">
							<option value="1" <?php echo $fileLimit == 1 ? 'selected="selected"' : ''; ?>>1</option>
							<option value="10" <?php echo $fileLimit == 10 ? 'selected="selected"' : ''; ?>>10</option>
							<option value="50" <?php echo $fileLimit == 50 ? 'selected="selected"' : ''; ?>>50</option>
							<option value="250" <?php echo $fileLimit == 250 ? 'selected="selected"' : ''; ?>>250</option>
							<option value="500" <?php echo $fileLimit == 500 ? 'selected="selected"' : ''; ?>>500</option>
							<option value="1000" <?php echo $fileLimit == 1000 ? 'selected="selected"' : ''; ?>>1000</option>
						</select>
					<div class="ui mini message">
							<?php _e( 'Number of files to copy that will be copied within one ajax request. The higher the value the faster the file copy process. To find out the highest possible values try a high value like 500 or more. If you get timeout issues, lower it until you get no more errors during copying process.', 'mainwp-staging-extension' ); ?><br />
							<?php _e( 'Important:', 'mainwp-staging-extension' ); ?></strong> <?php _e( 'If CPU Load Priority is Low set a file copy limit value of 50 or higher! Otherwise file copying process takes a lot of time.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'Maximum file size (MB)', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column">
					  <input id="mainwp_wp_stg_settings[maxFileSize]" name="mainwp_wp_stg_settings[maxFileSize]" class="medium-text" step="1" max="999999" min="0" value="<?php echo isset( $settings['maxFileSize'] ) ? $settings['maxFileSize'] : 8; ?>" type="number">
					<div class="ui mini message">
						<?php _e( 'Maximum size of the files which are allowed to copy. All files larger than this value will be skipped. Note: Increase this option only if you have a good reason. Files larger than a few megabytes are in 99% of all cases log and backup files which are not needed on a staging site.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'File copy batch size', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column">
					  <input id="mainwp_wp_stg_settings[batchSize]" name="mainwp_wp_stg_settings[batchSize]" class="medium-text" step="1" max="999999" min="0" value="<?php echo isset( $settings['batchSize'] ) ? $settings['batchSize'] : 2; ?>" type="number">
					<div class="ui mini message">
					<?php _e( 'Buffer size for the file copy process in megabyte. The higher the value the faster large files will be copied. To find out the highest possible values try a high one and lower it until you get no errors during file copy process. Usually this value correlates directly with the memory consumption of php so make sure that it does not exceed any php.ini max_memory limits.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<?php
				$cpuLoad = isset( $settings['cpuLoad'] ) ? $settings['cpuLoad'] : 'low';
				if ( ! in_array( $cpuLoad, array( 'high', 'medium', 'low' ) ) ) {
					  $cpuLoad = 'low';
				}
				?>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'CPU load priority', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column">
						<select id="mainwp_wp_stg_settings[cpuLoad]" name="mainwp_wp_stg_settings[cpuLoad]" class="ui dropdown">
							<option value="high" <?php echo $cpuLoad == 'high' ? 'selected="selected"' : ''; ?>><?php _e( 'High (fast)', 'mainwp-staging-extension' ); ?></option>
							<option value="medium" <?php echo $cpuLoad == 'medium' ? 'selected="selected"' : ''; ?>><?php _e( 'Medium (average)', 'mainwp-staging-extension' ); ?></option>
							<option value="low" <?php echo $cpuLoad == 'low' ? 'selected="selected"' : ''; ?>><?php _e( 'Low (slow)', 'mainwp-staging-extension' ); ?></option>
						</select>
					<div class="ui mini message">
							<?php _e( 'Using high will result in fast as possible processing but the cpu load increases and it is also possible that staging process gets interrupted because of too many ajax requests (e.g. authorization error). Using a lower value results in lower cpu load on your server but also slower staging site creation.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'Delay Between Requests', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column">
					  <input id="mainwp_wp_stg_settings[delayRequests]" name="mainwp_wp_stg_settings[delayRequests]" class="medium-text" step="1" max="999999" min="0" value="<?php echo isset( $settings['delayRequests'] ) ? $settings['delayRequests'] : 0; ?>" type="number">
					<div class="ui mini message">
					<?php _e( 'If your server uses rate limits it blocks requests and WP Staging can be interrupted. You can resolve that by adding one or more seconds of delay between the processing requests.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'Disable admin authorization', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input name="mainwp_wp_stg_settings[disableAdminLogin]" id="mainwp_wp_stg_settings[disableAdminLogin]_1" value="1" <?php echo isset( $settings['disableAdminLogin'] ) && $settings['disableAdminLogin'] ? 'checked="checked"' : ''; ?> type="checkbox">
					<div class="ui mini message">
					<?php _e( 'If you want to remove the requirement to login to the staging site you can deactivate it here. If you disable authentication everyone can see your staging sites including search engines and this can lead to "duplicate content" in search engines.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'Debug mode', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input name="mainwp_wp_stg_settings[debugMode]" id="mainwp_wp_stg_settings[debugMode]_1" value="1" <?php echo isset( $settings['debugMode'] ) && $settings['debugMode'] ? 'checked="checked"' : ''; ?> type="checkbox">
					<div class="ui mini message">
						<?php _e( 'This will enable an extended debug mode which creates additional entries in <strong>wp-content/uploads/wp-staging/logs/logfile.log</strong>. Please enable this when we ask you to do so.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'Optimizer', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input name="mainwp_wp_stg_settings[optimizer]" id="mainwp_wp_stg_settings[optimizer]_1" value="1" <?php echo isset( $settings['optimizer'] ) && $settings['optimizer'] ? 'checked="checked"' : ''; ?> type="checkbox">
					<div class="ui mini message">
						<?php _e( 'The Optimizer is a mu plugin which disables all other plugins during WP Staging processing. Usually this makes the cloning process more reliable. If you experience issues, disable the Optimizer.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'Remove data on uninstall?', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input name="mainwp_wp_stg_settings[unInstallOnDelete]" id="mainwp_wp_stg_settings[unInstallOnDelete]_1" value="1" <?php echo isset( $settings['unInstallOnDelete'] ) && $settings['unInstallOnDelete'] ? 'checked="checked"' : ''; ?> type="checkbox">
					<div class="ui mini message">
						<?php _e( 'Check this box if you like WP Staging to completely remove all of its data when the plugin is deleted. This will not remove staging sites files or database tables.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

				<div class="ui grid field">
				  <label class="six wide column middle aligned"><?php _e( 'Check directory size', 'mainwp-staging-extension' ); ?></label>
				  <div class="ten wide column ui toggle checkbox">
						<input name="mainwp_wp_stg_settings[checkDirectorySize]" id="mainwp_wp_stg_settings[checkDirectorySize]_1" value="1" <?php echo isset( $settings['checkDirectorySize'] ) && $settings['checkDirectorySize'] ? 'checked="checked"' : ''; ?> type="checkbox">
					<div class="ui mini message">
						<?php _e( 'Check this box if you like WP Staging to check sizes of each directory on scanning process.', 'mainwp-staging-extension' ); ?><br/>
							<?php _e( 'Warning this might cause timeout problems in big directory / file structures.', 'mainwp-staging-extension' ); ?>
					</div>
				  </div>
				</div>

			<div class="ui divider"></div>
			<input type="submit" class="ui green big right floating button" name="submit_staging" value="<?php _e( 'Save Settings', 'mainwp-staging-extension' ); ?>"/>
		<input type="hidden" name="_wpnonce_mainwp_wpstg_settings" value="<?php echo wp_create_nonce( '_wpnonce_mainwp_wpstg_settings' ); ?>"/>
	</form>
		<?php
	}

	public static function render_stagings_tab( $tab ) {
		$site_id         = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$individual_data = MainWP_Staging_DB::instance()->get_setting_by( 'site_id', $site_id );
		$settings        = array();
		if ( $individual_data ) {
			$settings = unserialize( $individual_data->settings );
		}
		$cpu_load = is_array( $settings ) && isset( $settings['cpuLoad'] ) ? $settings['cpuLoad'] : 'low';
		$cpu_load = self::getCPULoadSetting( $cpu_load );
		?>
		<div id="mwp-wpstg-working-clone">
	  <div class="ui yellow messsage" style="display:none;"></div>
			<div class="mwp-loading" style="display:none;"><div class="ui active inverted dimmer"><p></p><div class="ui text loader">Loading...</div></div></div>
		</div>
	  <div id="mwp-wpstg-workflow" class="" site-id="<?php echo $site_id; ?>"></div>
	  <script type="text/javascript">
		var mainwp_staging_$workFlow = null, mainwp_staging_$working = null;
		jQuery(document).ready(function ($) {
		  staging_elementsCache.site_id = <?php echo $site_id; ?>;
		  staging_elementsCache.cpuLoad = <?php echo esc_js( $cpu_load ); ?>;
		  mainwp_staging_$workFlow = jQuery('#mwp-wpstg-workflow');
		  mainwp_staging_$working = jQuery('#mwp-wpstg-working-clone');
					MainWP_WPStaging.init();
		  <?php if ( $tab == 'new' ) { ?>
						 mainwp_staging_scanning();
		  <?php } else { ?>
						mainwp_staging_loadOverview();
		  <?php } ?>
			})
		</script>
		<?php
	}

	public function get_site_settings( $site_id ) {
		if ( empty( $site_id ) ) {
			return false;
		}

		$settings  = array();
		$override  = false;
		$site_data = MainWP_Staging_DB::instance()->get_setting_by( 'site_id', $site_id );
		if ( $site_data ) {
			$settings = unserialize( $site_data->settings );
			if ( is_array( $settings ) && $settings['override'] ) {
				$override = true;
			}
		}

		if ( empty( $settings ) || ! $override ) {
			$site_data = MainWP_Staging_DB::instance()->get_setting_by( 'site_id', 0 ); // general settings
			if ( $site_data ) {
				$settings = unserialize( $site_data->settings );
			}
		}

		return $settings;
	}

	public static function map_site( &$website, $keys ) {
		$outputSite = array();
		foreach ( $keys as $key ) {
			$outputSite[ $key ] = $website->$key;
		}
		return $outputSite;
	}

	public static function is_manage_site() {
		if ( isset( $_POST['isIndividual'] ) && ! empty( $_POST['isIndividual'] ) && isset( $_POST['stagingSiteID'] ) && ! empty( $_POST['stagingSiteID'] ) ) {
			return true;
		} elseif ( isset( $_GET['page'] ) && ( 'ManageSitesStaging' == $_GET['page'] ) ) {
			return true;
		}
		return false;
	}

	public static function formatTimestamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}


} // End of class
