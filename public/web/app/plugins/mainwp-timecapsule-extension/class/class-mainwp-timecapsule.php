<?php

class MainWP_TimeCapsule {

	public static $instance = null;

	public $general_settings_filter = array(
		'backup'       => array(
			'backup_slot',
			'schedule_time_str',
			'wptc_timezone',
			'revision_limit',
			'user_excluded_extenstions',
			'user_excluded_files_more_than_size_settings',
			'backup_db_query_limit',
			'database_encrypt_settings', // "database_encryption_settings"
		),
		'backup_auto'  => array(
			'backup_before_update_setting',
			'wptc_auto_update_settings',
		),
		'vulns_update' => array(
			'vulns_settings',
		),
		'staging_opts' => array(
			'user_excluded_extenstions_staging',
			'internal_staging_db_rows_copy_limit',
			'internal_staging_file_copy_limit',
			'internal_staging_deep_link_limit',
			'internal_staging_enable_admin_login',
			'staging_is_reset_permalink',
				// 'staging_login_custom_link' // do not saving this field for general settings
		),

	);

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'wp_ajax_mainwp_wptc_load_sites', array( &$this, 'ajax_load_sites' ) );
		add_action( 'wp_ajax_mainwp_wptc_get_root_files', array( &$this, 'ajax_get_root_files' ) );
		add_action( 'wp_ajax_mainwp_wptc_get_files_by_key', array( &$this, 'ajax_get_files_by_key' ) );
		add_action( 'wp_ajax_mainwp_exclude_file_list_wptc', array( &$this, 'ajax_exclude_file_list' ) );
		add_action( 'wp_ajax_mainwp_include_file_list_wptc', array( &$this, 'ajax_include_file_list' ) );
		add_action( 'wp_ajax_mainwp_get_installed_plugins_wptc', array( &$this, 'ajax_get_installed_plugins' ) );
		add_action( 'wp_ajax_mainwp_get_installed_themes_wptc', array( &$this, 'ajax_get_installed_themes' ) );
		add_action( 'wp_ajax_mainwp_is_staging_need_request_wptc', array( &$this, 'ajax_is_staging_need_request' ) );
		add_action( 'wp_ajax_mainwp_get_staging_details_wptc', array( &$this, 'ajax_get_staging_details_wptc' ) );
		add_action( 'wp_ajax_mainwp_start_fresh_staging_wptc', array( &$this, 'ajax_start_fresh_staging_wptc' ) );
		add_action( 'wp_ajax_mainwp_get_staging_url_wptc', array( &$this, 'ajax_get_staging_url_wptc' ) );
		add_action( 'wp_ajax_mainwp_stop_staging_wptc', array( &$this, 'ajax_stop_staging_wptc' ) );
		add_action( 'wp_ajax_mainwp_continue_staging_wptc', array( &$this, 'ajax_continue_staging_wptc' ) );
		add_action( 'wp_ajax_mainwp_copy_staging_wptc', array( &$this, 'ajax_copy_staging_wptc' ) );
		add_action( 'wp_ajax_mainwp_delete_staging_wptc', array( &$this, 'ajax_delete_staging_wptc' ) );
		add_action( 'wp_ajax_mainwp_get_staging_current_status_key_wptc', array( &$this, 'ajax_get_staging_current_status_key_wptc' ) );
		add_action( 'wp_ajax_mainwp_wptc_sync_purchase', array( &$this, 'ajax_wptc_sync_purchase' ) );
		add_action( 'wp_ajax_mainwp_init_restore_to_staging_wptc', array( &$this, 'ajax_init_restore' ) );

		add_action( 'wp_ajax_mainwp_exclude_table_list_wptc', array( &$this, 'ajax_exclude_table_list' ) );
		add_action( 'wp_ajax_mainwp_include_table_list_wptc', array( &$this, 'ajax_include_table_list' ) );
		add_action( 'wp_ajax_mainwp_include_table_structure_only_wptc', array( &$this, 'ajax_include_table_structure_only' ) );
		add_action( 'wp_ajax_mainwp_wptc_get_tables', array( &$this, 'ajax_get_tables' ) );
		add_action( 'wp_ajax_mainwp_analyze_inc_exc_lists_wptc', array( &$this, 'ajax_analyze_inc_exc' ) );
		add_action( 'wp_ajax_mainwp_get_installed_plugins_vulns_wptc', array( &$this, 'ajax_get_enabled_plugins' ) );
		add_action( 'wp_ajax_mainwp_get_installed_themes_vulns_wptc', array( &$this, 'ajax_get_enabled_themes' ) );
		add_action( 'wp_ajax_mainwp_wptc_get_system_info', array( &$this, 'ajax_get_system_info' ) );
		add_action( 'wp_ajax_mainwp_save_settings_wptc', array( &$this, 'ajax_mainwp_save_settings_wptc' ) );
		add_action( 'wp_ajax_mainwp_save_settings_wptc_general', array( &$this, 'ajax_mainwp_save_settings_wptc_general' ) );

	}

	public function admin_init() {
			add_action( 'mainwp_delete_site', array( &$this, 'delete_site_data' ), 10, 1 );
	}

	public static function ajax_check_override( $websiteId, $isIndividual = true ) {
		$result   = MainWP_TimeCapsule_DB::get_instance()->get_data_by( 'site_id', $websiteId );
		$override = ( $result && $result->override ) ? true : false;
		if ( $isIndividual && ! $override ) {
			die( json_encode( array( 'error' => 'Not Updated - Override General Settings need to be set to Yes.' ) ) );
		} elseif ( ! $isIndividual && $override ) {
			die( json_encode( array( 'error' => 'Not Updated - Individual site settings are in use' ) ) );
		}
	}

	public static function ajax_check_data( $check_override = false ) {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'timecapsule_nonce' ) ) {
			die( json_encode( array( 'error' => 'Security Error.' ) ) );
		}

		if ( empty( $_REQUEST['timecapsuleSiteID'] ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) );
		}

		if ( $check_override ) {
			self::ajax_check_override( $_REQUEST['timecapsuleSiteID'] );
		}
	}

	public function delete_site_data( $website ) {
		if ( $website ) {
			MainWP_TimeCapsule_DB::get_instance()->delete_data( 'site_id', $website->id );
		}
	}

	public static function ajax_load_sites() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'timecapsule_nonce' ) ) {
			die( json_encode( array( 'error' => 'Security Error.' ) ) );
		}
		global $mainwpWPTimeCapsuleExtensionActivator;

		$what    = $_POST['what'];
		$tabName = isset( $_POST['tabName'] ) ? $_POST['tabName'] : '';

		$websites  = apply_filters( 'mainwp_getsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), null );
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
			unset( $websites );
		}
		$option             = array(
			'plugin_upgrades' => true,
			'plugins'         => true,
		);
		$dbwebsites         = apply_filters( 'mainwp_getdbsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$dbwebsites_plugins = MainWP_TimeCapsule_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites );

		unset( $dbwebsites );

		$error = '';

		if ( count( $dbwebsites_plugins ) == 0 ) {
			$error = __( 'No websites were found with the WP Time Capsule plugin installed.', 'mainwp-timecapsule-extension' );
		}

		$html = '';
		if ( empty( $error ) ) {
				$title = '';
			if ( $what == 'save_settings' ) {
				$title = __( 'Saving settings to child sites ...', 'mainwp-timecapsule-extension' );
			} elseif ( $what == 'start_backup' ) {
				$title = __( 'Starting backup on child sites ...', 'mainwp-timecapsule-extension' );
			} elseif ( $what == 'test_communication' ) {
				$title = __( 'Starting test Plugin - Server communication status on child sites ...', 'mainwp-timecapsule-extension' );
			} elseif ( $what == 'logging' ) {
				$title = __( 'Starting Login to your WP Time Capsule account on child sites ...', 'mainwp-timecapsule-extension' );
			} elseif ( $what == 'sync_purchase' ) {
				$title = __( 'Starting sync purchase on child sites ...', 'mainwp-timecapsule-extension' );
			}
				ob_start();
			?>
				<div class="ui hidden divider"></div>
				<h3 class="ui dividing header"><?php echo ! empty( $title ) ? $title : '&nbsp;'; ?></h3>
				<input type="hidden" id="mainwp_wptc_saving_tab_settings" name="mainwp_wptc_saving_tab_settings" value="<?php echo $tabName; ?>">
				<?php
				foreach ( $dbwebsites_plugins as $website ) {
					echo '<div><strong>' . $website['name'] . '</strong>: ';
					echo '<span class="siteItemProcess" action="" site-id="' . $website['id'] . '" status="queue"><span class="status"></span></span>';
					echo '</div><br />';
				}

				$html = ob_get_clean();
		}

		if ( ! empty( $error ) ) {
			$error = '<div class="ui red message">' . $error . '</div>';
			die( $error );
		}
		die( $html );
	}


	function ajax_mainwp_save_settings_wptc() {

		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'timecapsule_nonce' ) ) {
			die( json_encode( array( 'error' => 'Security Error.' ) ) );
		}

		$general_saving = ( isset( $_POST['type'] ) && $_POST['type'] == 'general' ) ? true : false;

		if ( ! $general_saving && empty( $_REQUEST['timecapsuleSiteID'] ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) );
		}

		$data    = $_POST['data'];
		$tabName = $_POST['tabName'];

		$update = $this->get_post_fields_to_save( $data, $tabName, $general_saving );

		if ( empty( $update ) ) {
			die( json_encode( array( 'error' => 'Invalid data. Please check and try again.' ) ) );
		}

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		 // $websiteId = 0 for general settings
		MainWP_TimeCapsule_DB::get_instance()->update_settings_fields( (int) $websiteId, $update );

		// save general settings and go to update on child sites
		if ( $general_saving ) {
			die( json_encode( array( 'result' => 'next_step' ) ) );
			return;
		}

		self::ajax_check_override( $_REQUEST['timecapsuleSiteID'], ! $general_saving );

		$information = $this->perform_save_settings( $websiteId, $update, $tabName );

		if ( is_array( $information ) ) {
			if ( isset( $information['result'] ) ) {
				die( json_encode( array( 'result' => 'ok' ) ) );
			} elseif ( isset( $information['error'] ) ) {
				die( json_encode( array( 'error' => $information['error'] ) ) );
			}
		}
		die(
			json_encode(
				array(
					'error' => 'Undefined error.',
					'extra' => $information,
				)
			)
		);
	}

	function ajax_mainwp_save_settings_wptc_general() {
		self::ajax_check_data();
		$general_saving = ( isset( $_POST['type'] ) && $_POST['type'] == 'general' ) ? true : false;
		$websiteId      = intval( $_POST['timecapsuleSiteID'] );
		$tabName        = $_POST['tabName'];

		if ( ! $general_saving ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		} else {
			self::ajax_check_override( $websiteId, false );
		}

		if ( ! isset( $this->general_settings_filter[ $tabName ] ) ) {
			die( json_encode( array( 'error' => 'Error: empty data.' ) ) );
		}

		// 0 for general settings
		$settings = MainWP_TimeCapsule_DB::get_instance()->get_settings( 0 );

		if ( empty( $settings ) ) {
			die( json_encode( array( 'error' => 'Empty general settings.' ) ) );
		}

		$saving_fields = $this->general_settings_filter[ $tabName ];

		$update = array();
		foreach ( $saving_fields as $name ) {
			$update[ $name ] = $settings[ $name ];
		}

		$information = $this->perform_save_settings( $websiteId, $update, $tabName, true ); // saving general settings

		if ( is_array( $information ) ) {
			if ( isset( $information['result'] ) ) {
				die( json_encode( array( 'result' => 'ok' ) ) );
			} elseif ( isset( $information['error'] ) ) {
				die( json_encode( array( 'error' => $information['error'] ) ) );
			}
		}
		die(
			json_encode(
				array(
					'error' => 'Undefined error.',
					'extra' => $information,
				)
			)
		);
	}


	function perform_save_settings( $websiteId, $data, $tab, $is_general = false ) {

		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'save_settings',
			'data'       => base64_encode( serialize( $data ) ),
			'is_general' => $is_general,
			'tabname'    => $tab,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

		if ( is_array( $information ) && isset( $information['sync_data'] ) ) {
			$data = $information['sync_data'];
			if ( is_array( $data ) && isset( $data['main_account_email'] ) ) {
				$lastbackup_time = $data['lastbackup_time'];
				$backups_count   = isset( $data['backups_count'] ) ? $data['backups_count'] : 0;
				unset( $data['lastbackup_time'] );
				unset( $data['backups_count'] );
				if ( $websiteId ) {
					MainWP_TimeCapsule_DB::get_instance()->update_settings_fields( (int) $websiteId, $data );
					$update_data = array(
						'lastbackup_time' => $lastbackup_time,
						'backups_count'   => $backups_count,
						'site_id'         => $websiteId,
					);
					MainWP_TimeCapsule_DB::get_instance()->update_data( $update_data );
				}
			}
		}

		return $information;
	}

	function get_post_fields_to_save( $data, $tab, $general_saving ) {
		$settings = array();
		if ( $tab == 'backup' ) { // save_backup_settings_wptc()
			if ( ! empty( $data['user_excluded_extenstions'] ) ) {
				$settings['user_excluded_extenstions'] = strtolower( $data['user_excluded_extenstions'] );
			} else {
				$settings['user_excluded_extenstions'] = false;
			}

			$backup_slot = ( isset( $data['backup_slot'] ) ) ? $data['backup_slot'] : 'daily';

			if ( ! empty( $backup_slot ) ) {
				$settings['backup_slot'] = $backup_slot;
			}

			$backup_db_query_limit = ( isset( $data['backup_db_query_limit'] ) ) ? $data['backup_db_query_limit'] : 300;

			if ( ! empty( $backup_db_query_limit ) ) {
				$settings['backup_db_query_limit'] = $backup_db_query_limit;
			}

			$updateSettings = array(
				'status' => $data['user_excluded_files_more_than_size_settings']['status'],
				'size'   => $this->convert_mb_to_bytes( $data['user_excluded_files_more_than_size_settings']['size'] ),
			);
			$settings['user_excluded_files_more_than_size_settings'] = serialize( $updateSettings );

			if ( ! empty( $data['database_encryption_settings'] ) && ! empty( $data['database_encryption_settings']['key'] ) ) {
				$data['database_encryption_settings']['key'] = base64_encode( $data['database_encryption_settings']['key'] );
			}
			// database_encrypt_settings field name to save child
			$settings['database_encrypt_settings'] = serialize( $data['database_encryption_settings'] );

			if ( ! empty( $data['scheduled_time'] ) && ! empty( $data['timezone'] ) ) {
				$settings['wptc_timezone']     = $data['timezone'];
				$settings['schedule_time_str'] = $data['scheduled_time'];
			}

			if ( ! empty( $data['revision_limit'] ) ) {
				$settings['revision_limit'] = $data['revision_limit'];
			}
		} elseif ( $tab == 'backup_auto' ) {
			if ( ! empty( $data['backup_before_update_setting'] ) && ( $data['backup_before_update_setting'] == 'true' || $data['backup_before_update_setting'] == 'always' ) ) {
				$settings['backup_before_update_setting'] = 'always';
			} else {
				$settings['backup_before_update_setting'] = 'everytime';
			}

			$backup_settings                              = array();
			$backup_settings['update_settings']['status'] = empty( $data['auto_update_wptc_setting'] ) ? 'no' : $data['auto_update_wptc_setting'];
			$backup_settings['update_settings']['schedule']['enabled']     = empty( $data['schedule_enabled'] ) ? 0 : 1;
			$backup_settings['update_settings']['schedule']['time']        = empty( $data['schedule_time'] ) ? '' : $data['schedule_time'];
			$backup_settings['update_settings']['core']['major']['status'] = empty( $data['auto_updater_core_major'] ) ? 0 : 1;
			$backup_settings['update_settings']['core']['minor']['status'] = empty( $data['auto_updater_core_minor'] ) ? 0 : 1;
			$backup_settings['update_settings']['themes']['status']        = empty( $data['auto_updater_themes'] ) ? 0 : 1;
			$backup_settings['update_settings']['plugins']['status']       = empty( $data['auto_updater_plugins'] ) ? 0 : 1;

			if ( ! $general_saving ) {
				if ( ! empty( $data['auto_updater_plugins_included'] ) ) {
					$plugin_include_array                                      = explode( ',', $data['auto_updater_plugins_included'] );
					$backup_settings['update_settings']['plugins']['included'] = serialize( $plugin_include_array );
				}

				if ( ! empty( $data['auto_updater_themes_included'] ) ) {
					$themes_include_array                                     = explode( ',', $data['auto_updater_themes_included'] );
					$backup_settings['update_settings']['themes']['included'] = serialize( $themes_include_array );
				}
			}

			$settings['wptc_auto_update_settings'] = serialize( $backup_settings );

		} elseif ( $tab == 'vulns_update' ) {
			$vulns_settings                      = array();
			$vulns_settings['status']            = empty( $data['vulns_wptc_setting'] ) ? 'no' : $data['vulns_wptc_setting'];
			$vulns_settings['core']['status']    = empty( $data['wptc_vulns_core'] ) ? 0 : 1;
			$vulns_settings['themes']['status']  = empty( $data['wptc_vulns_themes'] ) ? 0 : 1;
			$vulns_settings['plugins']['status'] = empty( $data['wptc_vulns_plugins'] ) ? 0 : 1;
			if ( ! $general_saving ) {
				$vulns_plugins_included                              = ! empty( $data['vulns_plugins_included'] ) ? $data['vulns_plugins_included'] : array();
				$vulns_settings['plugins']['vulns_plugins_included'] = $vulns_plugins_included; // will re-calculate
				$vulns_themes_included                               = ! empty( $data['vulns_themes_included'] ) ? $data['vulns_themes_included'] : array();
				$vulns_settings['themes']['vulns_themes_included']   = $vulns_themes_included; // will re-calculate
			}
			$settings['vulns_settings'] = serialize( $vulns_settings );
		} elseif ( $tab == 'staging_opts' ) {
			$settings['internal_staging_db_rows_copy_limit'] = ! empty( $data['db_rows_clone_limit_wptc'] ) ? $data['db_rows_clone_limit_wptc'] : false;
			$settings['internal_staging_file_copy_limit']    = ! empty( $data['files_clone_limit_wptc'] ) ? $data['files_clone_limit_wptc'] : false;
			$settings['internal_staging_deep_link_limit']    = ! empty( $data['deep_link_replace_limit_wptc'] ) ? $data['deep_link_replace_limit_wptc'] : false;
			$settings['internal_staging_enable_admin_login'] = ! empty( $data['enable_admin_login_wptc'] ) ? $data['enable_admin_login_wptc'] : false;

			if ( ! empty( $data['reset_permalink_wptc'] ) ) {
				$settings['staging_is_reset_permalink'] = true;
			} else {
				$settings['staging_is_reset_permalink'] = false;
			}

			if ( ! $general_saving ) {
				if ( ! empty( $data['login_custom_link_wptc'] ) ) {
					$settings['staging_login_custom_link'] = $data['login_custom_link_wptc'];
				} else {
					$settings['staging_login_custom_link'] = false;
				}
			}
		}

		return $settings;
	}


	// table related functions
	public function ajax_exclude_table_list() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data   = array(
			'mwp_action' => 'exclude_table_list',
			'data'       => $_POST['data'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		die( $information );
	}

	public function ajax_get_root_files() {

		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_root_files',
			'category'   => $_POST['category'],
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );

		die( $information );
	}

	public function ajax_get_files_by_key() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_files_by_key',
			'key'        => $_REQUEST['key'],
			'category'   => $_REQUEST['category'],
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		die( $information );
	}

	public function ajax_exclude_file_list() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'exclude_file_list',
			'data'       => $_POST['data'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		die( $information );
	}

	public function ajax_get_installed_plugins() {

		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_installed_plugins',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

		if ( is_array( $information ) && isset( $information['results'] ) ) {
			die( json_encode( $information['results'] ) );
		}

		die(
			json_encode(
				array(
					'error' => 'Undefined error.',
					'extra' => $information,
				)
			)
		);
	}

	public function ajax_get_installed_themes() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_installed_themes',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

		if ( is_array( $information ) && isset( $information['results'] ) ) {
			die( json_encode( $information['results'] ) );
		}
		die(
			json_encode(
				array(
					'error' => 'Undefined error.',
					'extra' => $information,
				)
			)
		);
	}

	public function ajax_is_staging_need_request() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'is_staging_need_request',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

		die( $information );
	}

	public function ajax_get_staging_details_wptc() {
		self::ajax_check_data();

		$websiteId    = intval( $_REQUEST['timecapsuleSiteID'] );
		$force_update = isset( $_POST['forced_update'] ) && $_POST['forced_update'] ? true : false;

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_staging_details_wptc',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

		$data = @json_decode( $information, true );
		if ( is_array( $data ) && isset( $data['destination_url'] ) ) {
			$clone_url = $data['destination_url'];
			$cloneID   = $data['staging_folder'];
			$this->sync_staging_site_data( $websiteId, $clone_url, $cloneID, $force_update );
		}
		die( $information );
	}

	public function ajax_start_fresh_staging_wptc() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		$path      = $_POST['path'];
		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'start_fresh_staging_wptc',
			'path'       => $path,
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

		die( $information );
	}

	public function ajax_get_staging_url_wptc() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'get_staging_url_wptc',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

		die( $information );
	}

	public function ajax_stop_staging_wptc() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'stop_staging_wptc',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );
		$this->delete_clone_site( $websiteId );
		die( $information );
	}

	public function ajax_copy_staging_wptc() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'copy_staging_wptc',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

		die( $information );
	}

	public function ajax_delete_staging_wptc() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'delete_staging_wptc',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

		$data = @json_decode( $information, true );
		if ( is_array( $data ) && isset( $data['status'] ) && $data['status'] == 'success' ) {
			$this->delete_clone_site( $websiteId );
		}
		die( $information );
	}

	public function delete_clone_site( $websiteId ) {
		if ( empty( $websiteId ) ) {
			return false;
		}

		global $mainwpWPTimeCapsuleExtensionActivator;
		$current = MainWP_TimeCapsule_DB::get_instance()->get_staging_site( $websiteId );
		if ( $current && ! empty( $current->clone_url ) ) {
			return apply_filters( 'mainwp_deleteclonesite', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, $current->clone_url );
			// will use new hook when new version of dashboard released
			// apply_filters( 'mainwp_delete_clonesite', $$mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $$mainwpWPTimeCapsuleExtensionActivator->get_child_key(), '', $current->staging_site_id );
		}
		return false;
	}

	public function ajax_get_staging_current_status_key_wptc() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'get_staging_current_status_key',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );
		die( $information );
	}

	public function ajax_wptc_sync_purchase() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'wptc_sync_purchase',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );
		die( $information );
	}

	public function ajax_init_restore() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'init_restore',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );
		die( $information );
	}

	public function sync_staging_site_data( $site_id, $clone_url, $cloneID, $force_update = false ) {
		global $mainwpWPTimeCapsuleExtensionActivator;
		$information = false;
		if ( ! empty( $site_id ) && ! empty( $clone_url ) ) {
			$information = apply_filters( 'mainwp_clonesite', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $site_id, $cloneID, $clone_url, $force_update );
			if ( is_array( $information ) && isset( $information['siteid'] ) && ! empty( $information['siteid'] ) ) {
				$update = array(
					'site_id'         => $site_id,
					'clone_id'        => $cloneID,
					'clone_url'       => $clone_url,
					'staging_site_id' => $information['siteid'],
				);
				MainWP_TimeCapsule_DB::get_instance()->update_staging_site( $update );
			}
		}
		return $information;
	}

	public function ajax_continue_staging_wptc() {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'continue_staging_wptc',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, $raw_response = true );

		die( $information );
	}

	public function ajax_include_file_list() {

		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'include_file_list',
			'data'       => $_POST['data'],
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		die( $information );

	}

	public function ajax_include_table_list( $data ) {
		self::ajax_check_data();
		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );
		global $mainwpWPTimeCapsuleExtensionActivator;
		$post_data   = array(
			'mwp_action' => 'include_table_list',
			'data'       => $_POST['data'],
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		die( $information );
	}



	public function ajax_include_table_structure_only( $data ) {

		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'include_table_structure_only',
			'data'       => $_POST['data'],
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		die( $information );

	}

	public function ajax_get_tables() {

		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_tables',
			'category'   => $_REQUEST['category'],
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		die( $information );
	}

	public function ajax_analyze_inc_exc() {

		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'analyze_inc_exc',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
		if ( is_array( $information ) ) {
			die( json_encode( $information ) );
		} else {
			die( $information );
		}
	}

	public function ajax_get_enabled_plugins() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_enabled_plugins',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

		if ( is_array( $information ) && isset( $information['results'] ) ) {
			die( json_encode( $information['results'] ) );
		}
		die(
			json_encode(
				array(
					'error' => 'Undefined error.',
					'extra' => $information,
				)
			)
		);
	}

	public function ajax_get_enabled_themes() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_enabled_themes',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

		if ( is_array( $information ) && isset( $information['results'] ) ) {
			die( json_encode( $information['results'] ) );
		}
		die(
			json_encode(
				array(
					'error' => 'Undefined error.',
					'extra' => $information,
				)
			)
		);
	}

	public function ajax_get_system_info() {
		self::ajax_check_data();

		$websiteId = intval( $_REQUEST['timecapsuleSiteID'] );

		global $mainwpWPTimeCapsuleExtensionActivator;

		$post_data = array(
			'mwp_action' => 'get_system_info',
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );
		die( json_encode( $information ) );
	}


	// public function exclude_file_list($data ){
	//
	// if (empty($data['file'])) {
	// return false;
	// }
	//
	// MainWP_TimeCapsule::ajax_check_data();
	//
	// $websiteId = intval($_REQUEST['timecapsuleSiteID']);
	//
	// global $mainwpWPTimeCapsuleExtensionActivator;
	//
	// $post_data = array(
	// 'mwp_action' => 'exclude_file_list',
	// 'data' => array(
	// 'category' => $data['category'],
	// 'file' => $data['file'],
	// 'isdir' => $data['isdir']
	// )
	// );
	//
	// $information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data, true );
	// die( $information );
	//
	// $success = false;
	// if ( is_array( $information ) && isset( $information['status'] ) && $information['status'] == 'success' ) {
	// $success = true;
	// }
	//
	// if($do_not_die){
	// return $success;
	// }
	//
	// if ( $success ) {
	// die( json_encode( array('status' => 'success') ) );
	// }
	// die( json_encode( array( 'error' => 'Undefined error.', 'extra' => $information) ) );
	// }

	public static function render() {

		$website = null;
		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			global $mainwpWPTimeCapsuleExtensionActivator;
			$option     = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), array( $_GET['id'] ), array(), $option );

			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}

		if ( self::is_managesites_page() ) {
			$error = '';
			if ( empty( $website ) || empty( $website->id ) ) {
				$error = __( 'Invalid website ID. Please, try again.', 'mainwp-timecapsule-extension' );
			} else {
				$activated = false;
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'wp-time-capsule/wp-time-capsule.php' == $plugin['slug'] ) ) {
								if ( $plugin['active'] ) {
									$activated = true; }
								break;
							}
						}
					}
				}
				if ( ! $activated ) {
					$error = __( 'WP Time Capsule plugin is not installed or activated on the site.', 'mainwp-timecapsule-extension' );
				}
			}

			if ( ! empty( $error ) ) {
				do_action( 'mainwp_pageheader_sites', 'WPTimeCapsule' );
				echo '<div class="ui red message">' . $error . '</div>';
				do_action( 'mainwp_pagefooter_sites', 'WPTimeCapsule' );
				return;
			}
		}

		self::render_tabs( $website );
	}

	public static function render_tabs( $pWebsite = null ) {

		global $mainwpWPTimeCapsuleExtensionActivator;
		$dbwebsites_plugins = array();
		$sitesName          = array();

		if ( ! self::is_managesites_page() ) {
			$websites  = apply_filters( 'mainwp_getsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), null );
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

			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $sites_ids, array(), $option );
			// print_r($dbwebsites);
			$selected_group = null;

			if ( isset( $_POST['mainwp_time_capsule_plugin_groups_select'] ) ) {
				$selected_group = intval( $_POST['mainwp_time_capsule_plugin_groups_select'] );
			}

			$pluginDataSites = array();
			if ( count( $sites_ids ) > 0 ) {
				$pluginDataSites = MainWP_TimeCapsule_DB::get_instance()->get_timecapsule_of_sites( $sites_ids );
			}

			$dbwebsites_plugins = MainWP_TimeCapsule_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites, $selected_group, $pluginDataSites );

			foreach ( $dbwebsites_plugins as $site ) {
				$sitesName[ $site['id'] ] = $site['name'];
			}

			unset( $dbwebsites );
			unset( $pluginDataSites );
		}

		$is_user_logged_in = true;
		if ( self::is_managesites_page() ) {
			$is_individual = true;
			$config        = MainWP_WPTC_Factory::get( 'config' );
			if ( ! $config->get_option( 'is_user_logged_in' ) ) {
				$is_user_logged_in = false;
			}
		} else {
			$is_individual = false;
		}

		$current_tab = '';
		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'general' ) {
				if ( isset( $_GET['change_store'] ) && $_GET['change_store'] ) {
					$current_tab = 'store';
				} else {
					$current_tab = 'general';
				}
			} elseif ( $_GET['tab'] == 'backup' ) {
				$current_tab = 'backup';
			} elseif ( $_GET['tab'] == 'activity_log' ) {
				$current_tab = 'activities';
			} elseif ( $_GET['tab'] == 'backup_opts' ) {
				$current_tab = 'backup_opts';
			} elseif ( $_GET['tab'] == 'auto' ) {
				$current_tab = 'auto';
			} elseif ( $_GET['tab'] == 'vulns' ) {
				$current_tab = 'vulns';
			} elseif ( $_GET['tab'] == 'staging_opts' ) {
				$current_tab = 'staging_opts';
			} elseif ( $_GET['tab'] == 'staging' ) {
				$current_tab = 'staging';
			} elseif ( $_GET['tab'] == 'adv' ) {
				$current_tab = 'adv';
			} elseif ( $_GET['tab'] == 'info' ) {
				$current_tab = 'info';
			} else {
				$current_tab = 'dashboard';
			}
		} else {
			if ( $is_individual ) {
				$current_tab = 'general';
			} else {
				$current_tab = 'dashboard';
			}
		}

		$current_site_id = ! empty( $pWebsite ) ? $pWebsite->id : 0;
		$store_link      = $dashboard_link = '';
		if ( $is_individual ) {
			$extension_page = 'admin.php?page=ManageSitesWPTimeCapsule&id=' . $current_site_id;
		} else {
			$extension_page = 'admin.php?page=Extensions-Mainwp-Timecapsule-Extension';
			$dashboard_link = '<a id="mwp_capsule_dashboard_tab_lnk" href="' . $extension_page . '" class="item active"><i class="cog icon"></i> ' . __( 'WP Time Capsule Dashboard', 'mainwp-timecapsule-extension' ) . '</a>';
			if ( $current_tab == 'store' ) {
				$store_link = '<a href="' . $extension_page . '&tab=general&change_store=1" class="item active"><i class="cog icon"></i> ' . __( 'Connect your storage app', 'mainwp-timecapsule-extension' ) . '</a>';
			}
		}
		$current_site_url = '';
		if ( $current_site_id ) {
			$results = apply_filters( 'mainwp_getsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $current_site_id );

			if ( $results && is_array( $results ) ) {
				$_website         = current( $results );
				$current_site_url = $_website['url'];
			}
		}

		$primary_backup = get_option( 'mainwp_primaryBackup', null );

		if ( $is_individual ) {
			do_action( 'mainwp_pageheader_sites', 'WPTimeCapsule' );
		}
		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-timecapsule-menu">
			<?php echo $dashboard_link; ?>
			<a href="<?php echo $extension_page . '&tab=general'; ?>" class="item <?php echo ( $current_tab == 'general' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'General', 'mainwp-timecapsule-extension' ); ?></a>
			<?php if ( $is_individual ) : ?>
			<a href="admin.php?page=ManageSitesWPTimeCapsule&id=<?php echo $current_site_id; ?>&tab=info" class="item <?php echo ( $current_tab == 'info' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Information', 'mainwp-timecapsule-extension' ); ?></a>
			<a href="admin.php?page=ManageSitesWPTimeCapsule&id=<?php echo $current_site_id; ?>&tab=backup" class="item <?php echo ( $current_tab == 'backup' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Backups', 'mainwp-timecapsule-extension' ); ?></a>
			<a href="admin.php?page=ManageSitesWPTimeCapsule&id=<?php echo $current_site_id; ?>&tab=staging" class="item <?php echo ( $current_tab == 'staging' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Staging', 'mainwp-timecapsule-extension' ); ?></a>
			<a href="admin.php?page=ManageSitesWPTimeCapsule&id=<?php echo $current_site_id; ?>&tab=activity_log" class="item <?php echo ( $current_tab == 'activities' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Activity Log', 'mainwp-timecapsule-extension' ); ?></a>
			<?php endif; ?>
			<a href="<?php echo $extension_page . '&tab=backup_opts'; ?>" class="item <?php echo ( $current_tab == 'backup_opts' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Backup Settings', 'mainwp-timecapsule-extension' ); ?></a>
			<a href="<?php echo $extension_page . '&tab=auto'; ?>" class="item <?php echo ( $current_tab == 'auto' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Backup/Auto Updates', 'mainwp-timecapsule-extension' ); ?></a>
<!--            <a href="<?php echo $extension_page . '&tab=vulns'; ?>" class="item <?php echo ( $current_tab == 'vulns' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Vulnerability Updates', 'mainwp-timecapsule-extension' ); ?></a>-->
			<a href="<?php echo $extension_page . '&tab=staging_opts'; ?>" class="item <?php echo ( $current_tab == 'staging_opts' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Staging', 'mainwp-timecapsule-extension' ); ?></a>
			<a href="<?php echo $extension_page . '&tab=adv'; ?>" class="item <?php echo ( $current_tab == 'adv' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Advanced Options', 'mainwp-timecapsule-extension' ); ?></a>
			<?php echo $store_link; ?>
		</div>
		<input type="hidden" name="mainwp_wptc_current_site_id" id="mainwp_wptc_current_site_id" value="<?php echo intval( $current_site_id ); ?>"/>
		<input type="hidden" name="mainwp_wptc_current_site_url" id="mainwp_wptc_current_site_url" value="<?php echo $current_site_url; ?>"/>

		<div class="ui message" id="mainwp-wptc-message-zone" style="display:none"></div>
		<?php if ( ! $is_individual ) : ?>
			<?php if ( $current_tab == 'dashboard' ) : ?>
				<div id="mainwp-time-capsule-dashboard">
					<?php MainWP_TimeCapsule_Plugin::gen_select_sites(); ?>
					<div class="ui segment">
						<div class="ui message" id="mainwp-message-zone" style="display:none"></div>
						<?php MainWP_TimeCapsule_Plugin::gen_tab_dashboard( $dbwebsites_plugins ); ?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $current_tab == 'backup' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_backups(); ?>
								</div>
						</div>
				<div class="mainwp-side-content">
					<?php self::render_help_message(); ?>
				</div>
				<div class="ui hidden clearing divider"></div>
			</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'activities' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_activities(); ?>
					</div>
				</div>
				<div class="mainwp-side-content">
					<?php self::render_help_message(); ?>
			</div>
				<div class="ui hidden clearing divider"></div>
		</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'backup_opts' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_backup_opts(); ?>
					</div>
				</div>
				<div class="mainwp-side-content">
					<?php _e( 'Restore window option allows you to set the number of past days from which you would be able to restore your website or files. This will reflect in the Calendar view.', 'mainwp-timecapsule-extension' ); ?>
					<?php self::render_help_message(); ?>
				</div>
				<div class="ui hidden clearing divider"></div>
			</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'auto' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_backup_auto(); ?>
					</div>
				</div>
				<div class="mainwp-side-content">
					<?php self::render_help_message(); ?>
				</div>
				<div class="ui hidden clearing divider"></div>

				<script type="text/javascript" language="javascript">
					jQuery( document ).ready(function($) {
						$( 'input[name="auto_update_wptc_setting"]' ).change( function() {
							if( $(this).val() == "yes") {
								$( this ).closest( '.mainwp-parent-field' ).next( '.mainwp-child-field' ).fadeIn();
							} else {
								$( this ).closest( '.mainwp-parent-field' ).next( '.mainwp-child-field' ).fadeOut();
							}
						} );

						$( '.mainwp-parent-field input[type="checkbox"]' ).change( function() {
							if( this.checked ) {
								$( this ).closest( '.mainwp-parent-field' ).next( '.mainwp-child-field' ).fadeIn();
							} else {
								$( this ).closest( '.mainwp-parent-field' ).next( '.mainwp-child-field' ).fadeOut();
							}
						} );
					});
				</script>

			</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'staging_opts' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_staging_opts(); ?>
					</div>
				</div>
				<div class="mainwp-side-content">
					<p><?php echo __( 'Reduce the number of the DB Rows Cloning Limit by a few hundred if staging process hangs at <strong>Failed to clone database</strong>', 'mainwp-timecapsule-extension' ); ?></p>
					<p><?php echo __( 'Reduce the number of the Files Cloning Limit by a few hundred if staging process hangs at <strong>Failed to copy files.</strong>', 'mainwp-timecapsule-extension' ); ?></p>
					<p><?php echo __( 'Reduce the number of the Deep Link Replacing Limit by a few hundred if staging process hangs at <strong>Failed to replace links.</strong>', 'mainwp-timecapsule-extension' ); ?></p>
					<p><?php echo __( 'Enabling the Reset Permalink option will reset the permalink to default one in staging site.', 'mainwp-timecapsule-extension' ); ?></p>
					<p><?php echo __( 'If you want to remove the requirement to login to the staging site you can deactivate it here. <br>If you disable authentication everyone can see your staging site.', 'mainwp-timecapsule-extension' ); ?></p>
					<?php self::render_help_message(); ?>
				</div>
				<div class="ui hidden clearing divider"></div>
			</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'staging' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_staging_area(); ?>
					</div>
				</div>
				<div class="mainwp-side-content">

					<?php self::render_help_message(); ?>
				</div>
				<div class="ui hidden clearing divider"></div>
			</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'adv' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_adv(); ?>
					</div>
				</div>
				<div class="mainwp-side-content">
					<?php self::render_help_message(); ?>
				</div>
				<div class="ui hidden clearing divider"></div>
			</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'info' ) : ?>
			<div class="ui alt segment">
				<div class="mainwp-main-content">
					<div class="ui form">
						<?php self::gen_tab_info(); ?>
					</div>
				</div>
				<div class="mainwp-side-content">
					<?php self::render_help_message(); ?>
				</div>
				<div class="ui hidden clearing divider"></div>
			</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'general' || $current_tab == 'store' ) : ?>
			<?php if ( $is_individual ) : ?>
				<div class="ui alt segment">
					<div class="mainwp-main-content">
						<div class="ui form">
							<?php self::gen_wptc_settings_box( $current_site_id ); ?>
							<?php echo mainwp_wordpress_time_capsule_admin_menu_contents(); ?>
						</div>
					</div>
					<div class="mainwp-side-content">
						<?php self::render_help_message(); ?>
					</div>
					<div class="ui hidden clearing divider"></div>
				</div>
			<?php else : ?>
				<div class="ui alt segment">
					<div class="mainwp-main-content">
						<div class="ui form">
							<?php echo mainwp_wordpress_time_capsule_admin_menu_contents(); ?>
						</div>
					</div>
					<div class="mainwp-side-content">
						<?php self::render_help_message(); ?>
					</div>
					<div class="ui hidden clearing divider"></div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php
	}

	public static function render_help_message() {
		?>
	<p class="ui info message"><?php echo sprintf( __( 'If you are having issues with the WP Time Capsule plugin, help documentation can be %1$sfound here%2$s.', 'mainwp-timecapsule-extension' ), '<a href="https://docs.wptimecapsule.com" target="_blank">', '</a>' ); ?></p>
	<a class="ui green big fluid button" target="_blank" href="https://mainwp.com/help/docs/time-capsule-extension/"><?php echo __( 'Extension Documentation', 'mainwp-timecapsule-extension' ); ?></a>
		<?php
	}

	public static function get_schedule_times_div_wptc( $type = 'backup', $current_time = false, $config = false ) {

		$div = '';

		if ( $type === 'backup' && $config ) {
			$current_time = $config->get_option( 'schedule_time_str' );
		}
		for ( $i = 1; $i <= 24; $i++ ) {
			$time = date( 'g:i a', strtotime( "$i:00" ) );
			$div .= self::get_dropdown_option_html( $time, $time, $current_time );
		}
		return $div;
	}

	public static function get_dropdown_option_html( $value, $name, $selected_value, $extra_text = '' ) {

		if ( $selected_value == $value ) {
			return "<option value='" . $value . "' selected >" . $name . ' ' . $extra_text . '</option>';
		}
		return "<option value='" . $value . "' >" . $name . ' ' . $extra_text . '</option>';
	}

	static function get_schedule_times_backup_wptc( &$config ) {
		$times = array();
		$div   = '';

		$this_time_zone = $config->get_option( 'wptc_timezone' );
		if ( ! $this_time_zone ) {
			$wp_default_time_zone = get_option( 'timezone_string' );
			if ( ! $wp_default_time_zone ) {
				$this_time_zone = 'UTC';
			} else {
				$this_time_zone = $wp_default_time_zone;
			}
		}

		$already_selected_schedule = $config->get_option( 'schedule_time_str' );

		for ( $i = 1; $i <= 24; $i++ ) {
			$selected       = '';
			$this_date_text = date( 'g:i a', strtotime( "$i:00" ) );
			$frequency      = 'daily';
			$this_data_val  = $this_date_text;
			if ( $already_selected_schedule == $this_data_val ) {
				$div .= '<option selected value="' . $this_data_val . '">' . $this_date_text . '</option>';
			} else {
				$div .= '<option value="' . $this_data_val . '">' . $this_date_text . '</option>';
			}
		}

		return $div;
	}

	public function get_html( $other_process_going_on ) {

		$disable           = ( $other_process_going_on === 'Staging Process' ) ? 'disabled' : '';
		$status_start_note = ( $other_process_going_on === 'Backup Process' ) ? 'style="display: none;"' : 'style="display: block;"';
		$status_stop_note  = ( $other_process_going_on === 'Backup Process' ) ? "style='display: block;'" : "style='display: none;'";

		return '<tr>
				<th scope="row"> ' . __( 'On-Demand Backup', 'mainwp-timecapsule-extension' ) . ' </th>
				<td>
					<fieldset>
						<label>
							<a id="start_backup_from_settings" action="start" class="button-secondary ' . $disable . '" style="margin-top: -3px;" >Backup now</a>
						</label>
						<p ' . $status_start_note . '  class="description setting_backup_start_note_wptc"><?php esc_attr_e( "Click Backup Now to backup the latest changes.", "wp-time-capsule" ); ?></p>
					<p ' . $status_stop_note . ' class="description setting_backup_stop_note_wptc"><?php esc_attr_e( "Clicking on Stop Backup will erase all progress made in the current backup..", "wp-time-capsule" ); ?></p>
					</fieldset>
				</td>
			</tr>';
	}

	// function for return the timezone select options
	static function select_wptc_timezone() {
		return '<optgroup label="Africa">
                    <option value="Africa/Abidjan">Abidjan</option><option value="Africa/Accra">Accra</option><option value="Africa/Addis_Ababa">Addis Ababa</option><option value="Africa/Algiers">Algiers</option><option value="Africa/Asmara">Asmara</option><option value="Africa/Bamako">Bamako</option><option value="Africa/Bangui">Bangui</option><option value="Africa/Banjul">Banjul</option><option value="Africa/Bissau">Bissau</option><option value="Africa/Blantyre">Blantyre</option><option value="Africa/Brazzaville">Brazzaville</option><option value="Africa/Bujumbura">Bujumbura</option><option value="Africa/Cairo">Cairo</option><option value="Africa/Casablanca">Casablanca</option><option value="Africa/Ceuta">Ceuta</option><option value="Africa/Conakry">Conakry</option><option value="Africa/Dakar">Dakar</option><option value="Africa/Dar_es_Salaam">Dar es Salaam</option><option value="Africa/Djibouti">Djibouti</option><option value="Africa/Douala">Douala</option><option value="Africa/El_Aaiun">El Aaiun</option><option value="Africa/Freetown">Freetown</option><option value="Africa/Gaborone">Gaborone</option><option value="Africa/Harare">Harare</option><option value="Africa/Johannesburg">Johannesburg</option><option value="Africa/Juba">Juba</option><option value="Africa/Kampala">Kampala</option><option value="Africa/Khartoum">Khartoum</option><option value="Africa/Kigali">Kigali</option><option value="Africa/Kinshasa">Kinshasa</option><option value="Africa/Lagos">Lagos</option><option value="Africa/Libreville">Libreville</option><option value="Africa/Lome">Lome</option><option value="Africa/Luanda">Luanda</option><option value="Africa/Lubumbashi">Lubumbashi</option><option value="Africa/Lusaka">Lusaka</option><option value="Africa/Malabo">Malabo</option><option value="Africa/Maputo">Maputo</option><option value="Africa/Maseru">Maseru</option><option value="Africa/Mbabane">Mbabane</option><option value="Africa/Mogadishu">Mogadishu</option><option value="Africa/Monrovia">Monrovia</option><option value="Africa/Nairobi">Nairobi</option><option value="Africa/Ndjamena">Ndjamena</option><option value="Africa/Niamey">Niamey</option><option value="Africa/Nouakchott">Nouakchott</option><option value="Africa/Ouagadougou">Ouagadougou</option><option value="Africa/Porto-Novo">Porto-Novo</option><option value="Africa/Sao_Tome">Sao Tome</option><option value="Africa/Tripoli">Tripoli</option><option value="Africa/Tunis">Tunis</option><option value="Africa/Windhoek">Windhoek</option>
                </optgroup>
                <optgroup label="America">
                    <option value="America/Adak">Adak</option><option value="America/Anchorage">Anchorage</option><option value="America/Anguilla">Anguilla</option><option value="America/Antigua">Antigua</option><option value="America/Araguaina">Araguaina</option><option value="America/Argentina/Buenos_Aires">Argentina - Buenos Aires</option><option value="America/Argentina/Catamarca">Argentina - Catamarca</option><option value="America/Argentina/Cordoba">Argentina - Cordoba</option><option value="America/Argentina/Jujuy">Argentina - Jujuy</option><option value="America/Argentina/La_Rioja">Argentina - La Rioja</option><option value="America/Argentina/Mendoza">Argentina - Mendoza</option><option value="America/Argentina/Rio_Gallegos">Argentina - Rio Gallegos</option><option value="America/Argentina/Salta">Argentina - Salta</option><option value="America/Argentina/San_Juan">Argentina - San Juan</option><option value="America/Argentina/San_Luis">Argentina - San Luis</option><option value="America/Argentina/Tucuman">Argentina - Tucuman</option><option value="America/Argentina/Ushuaia">Argentina - Ushuaia</option><option value="America/Aruba">Aruba</option><option value="America/Asuncion">Asuncion</option><option value="America/Atikokan">Atikokan</option><option value="America/Bahia">Bahia</option><option value="America/Bahia_Banderas">Bahia Banderas</option><option value="America/Barbados">Barbados</option><option value="America/Belem">Belem</option><option value="America/Belize">Belize</option><option value="America/Blanc-Sablon">Blanc-Sablon</option><option value="America/Boa_Vista">Boa Vista</option><option value="America/Bogota">Bogota</option><option value="America/Boise">Boise</option><option value="America/Cambridge_Bay">Cambridge Bay</option><option value="America/Campo_Grande">Campo Grande</option><option value="America/Cancun">Cancun</option><option value="America/Caracas">Caracas</option><option value="America/Cayenne">Cayenne</option><option value="America/Cayman">Cayman</option><option value="America/Chicago">Chicago</option><option value="America/Chihuahua">Chihuahua</option><option value="America/Costa_Rica">Costa Rica</option><option value="America/Creston">Creston</option><option value="America/Cuiaba">Cuiaba</option><option value="America/Curacao">Curacao</option><option value="America/Danmarkshavn">Danmarkshavn</option><option value="America/Dawson">Dawson</option><option value="America/Dawson_Creek">Dawson Creek</option><option value="America/Denver">Denver</option><option value="America/Detroit">Detroit</option><option value="America/Dominica">Dominica</option><option value="America/Edmonton">Edmonton</option><option value="America/Eirunepe">Eirunepe</option><option value="America/El_Salvador">El Salvador</option><option value="America/Fortaleza">Fortaleza</option><option value="America/Glace_Bay">Glace Bay</option><option value="America/Godthab">Godthab</option><option value="America/Goose_Bay">Goose Bay</option><option value="America/Grand_Turk">Grand Turk</option><option value="America/Grenada">Grenada</option><option value="America/Guadeloupe">Guadeloupe</option><option value="America/Guatemala">Guatemala</option><option value="America/Guayaquil">Guayaquil</option><option value="America/Guyana">Guyana</option><option value="America/Halifax">Halifax</option><option value="America/Havana">Havana</option><option value="America/Hermosillo">Hermosillo</option><option value="America/Indiana/Indianapolis">Indiana - Indianapolis</option><option value="America/Indiana/Knox">Indiana - Knox</option><option value="America/Indiana/Marengo">Indiana - Marengo</option><option value="America/Indiana/Petersburg">Indiana - Petersburg</option><option value="America/Indiana/Tell_City">Indiana - Tell City</option><option value="America/Indiana/Vevay">Indiana - Vevay</option><option value="America/Indiana/Vincennes">Indiana - Vincennes</option><option value="America/Indiana/Winamac">Indiana - Winamac</option><option value="America/Inuvik">Inuvik</option><option value="America/Iqaluit">Iqaluit</option><option value="America/Jamaica">Jamaica</option><option value="America/Juneau">Juneau</option><option value="America/Kentucky/Louisville">Kentucky - Louisville</option><option value="America/Kentucky/Monticello">Kentucky - Monticello</option><option value="America/Kralendijk">Kralendijk</option><option value="America/La_Paz">La Paz</option><option value="America/Lima">Lima</option><option value="America/Los_Angeles">Los Angeles</option><option value="America/Lower_Princes">Lower Princes</option><option value="America/Maceio">Maceio</option><option value="America/Managua">Managua</option><option value="America/Manaus">Manaus</option><option value="America/Marigot">Marigot</option><option value="America/Martinique">Martinique</option><option value="America/Matamoros">Matamoros</option><option value="America/Mazatlan">Mazatlan</option><option value="America/Menominee">Menominee</option><option value="America/Merida">Merida</option><option value="America/Metlakatla">Metlakatla</option><option value="America/Mexico_City">Mexico City</option><option value="America/Miquelon">Miquelon</option><option value="America/Moncton">Moncton</option><option value="America/Monterrey">Monterrey</option><option value="America/Montevideo">Montevideo</option><option value="America/Montserrat">Montserrat</option><option value="America/Nassau">Nassau</option><option value="America/New_York">New York</option><option value="America/Nipigon">Nipigon</option><option value="America/Nome">Nome</option><option value="America/Noronha">Noronha</option><option value="America/North_Dakota/Beulah">North Dakota - Beulah</option><option value="America/North_Dakota/Center">North Dakota - Center</option><option value="America/North_Dakota/New_Salem">North Dakota - New Salem</option><option value="America/Ojinaga">Ojinaga</option><option value="America/Panama">Panama</option><option value="America/Pangnirtung">Pangnirtung</option><option value="America/Paramaribo">Paramaribo</option><option value="America/Phoenix">Phoenix</option><option value="America/Port-au-Prince">Port-au-Prince</option><option value="America/Port_of_Spain">Port of Spain</option><option value="America/Porto_Velho">Porto Velho</option><option value="America/Puerto_Rico">Puerto Rico</option><option value="America/Rainy_River">Rainy River</option><option value="America/Rankin_Inlet">Rankin Inlet</option><option value="America/Recife">Recife</option><option value="America/Regina">Regina</option><option value="America/Resolute">Resolute</option><option value="America/Rio_Branco">Rio Branco</option><option value="America/Santa_Isabel">Santa Isabel</option><option value="America/Santarem">Santarem</option><option value="America/Santiago">Santiago</option><option value="America/Santo_Domingo">Santo Domingo</option><option value="America/Sao_Paulo">Sao Paulo</option><option value="America/Scoresbysund">Scoresbysund</option><option value="America/Sitka">Sitka</option><option value="America/St_Barthelemy">St Barthelemy</option><option value="America/St_Johns">St Johns</option><option value="America/St_Kitts">St Kitts</option><option value="America/St_Lucia">St Lucia</option><option value="America/St_Thomas">St Thomas</option><option value="America/St_Vincent">St Vincent</option><option value="America/Swift_Current">Swift Current</option><option value="America/Tegucigalpa">Tegucigalpa</option><option value="America/Thule">Thule</option><option value="America/Thunder_Bay">Thunder Bay</option><option value="America/Tijuana">Tijuana</option><option value="America/Toronto">Toronto</option><option value="America/Tortola">Tortola</option><option value="America/Vancouver">Vancouver</option><option value="America/Whitehorse">Whitehorse</option><option value="America/Winnipeg">Winnipeg</option><option value="America/Yakutat">Yakutat</option><option value="America/Yellowknife">Yellowknife</option>
                </optgroup>
                <optgroup label="Antarctica">
                    <option value="Antarctica/Casey">Casey</option><option value="Antarctica/Davis">Davis</option><option value="Antarctica/DumontDUrville">DumontDUrville</option><option value="Antarctica/Macquarie">Macquarie</option><option value="Antarctica/Mawson">Mawson</option><option value="Antarctica/McMurdo">McMurdo</option><option value="Antarctica/Palmer">Palmer</option><option value="Antarctica/Rothera">Rothera</option><option value="Antarctica/Syowa">Syowa</option><option value="Antarctica/Troll">Troll</option><option value="Antarctica/Vostok">Vostok</option>
                </optgroup>
                <optgroup label="Arctic">
                    <option value="Arctic/Longyearbyen">Longyearbyen</option>
                </optgroup>
                <optgroup label="Asia">
                    <option value="Asia/Aden">Aden</option><option value="Asia/Almaty">Almaty</option><option value="Asia/Amman">Amman</option><option value="Asia/Anadyr">Anadyr</option><option value="Asia/Aqtau">Aqtau</option><option value="Asia/Aqtobe">Aqtobe</option><option value="Asia/Ashgabat">Ashgabat</option><option value="Asia/Baghdad">Baghdad</option><option value="Asia/Bahrain">Bahrain</option><option value="Asia/Baku">Baku</option><option value="Asia/Bangkok">Bangkok</option><option value="Asia/Beirut">Beirut</option><option value="Asia/Bishkek">Bishkek</option><option value="Asia/Brunei">Brunei</option><option value="Asia/Chita">Chita</option><option value="Asia/Choibalsan">Choibalsan</option><option value="Asia/Colombo">Colombo</option><option value="Asia/Damascus">Damascus</option><option value="Asia/Dhaka">Dhaka</option><option value="Asia/Dili">Dili</option><option value="Asia/Dubai">Dubai</option><option value="Asia/Dushanbe">Dushanbe</option><option value="Asia/Gaza">Gaza</option><option value="Asia/Hebron">Hebron</option><option value="Asia/Ho_Chi_Minh">Ho Chi Minh</option><option value="Asia/Hong_Kong">Hong Kong</option><option value="Asia/Hovd">Hovd</option><option value="Asia/Irkutsk">Irkutsk</option><option value="Asia/Jakarta">Jakarta</option><option value="Asia/Jayapura">Jayapura</option><option value="Asia/Jerusalem">Jerusalem</option><option value="Asia/Kabul">Kabul</option><option value="Asia/Kamchatka">Kamchatka</option><option value="Asia/Karachi">Karachi</option><option value="Asia/Kathmandu">Kathmandu</option><option value="Asia/Khandyga">Khandyga</option><option value="Asia/Kolkata">Kolkata</option><option value="Asia/Krasnoyarsk">Krasnoyarsk</option><option value="Asia/Kuala_Lumpur">Kuala Lumpur</option><option value="Asia/Kuching">Kuching</option><option value="Asia/Kuwait">Kuwait</option><option value="Asia/Macau">Macau</option><option value="Asia/Magadan">Magadan</option><option value="Asia/Makassar">Makassar</option><option value="Asia/Manila">Manila</option><option value="Asia/Muscat">Muscat</option><option value="Asia/Nicosia">Nicosia</option><option value="Asia/Novokuznetsk">Novokuznetsk</option><option value="Asia/Novosibirsk">Novosibirsk</option><option value="Asia/Omsk">Omsk</option><option value="Asia/Oral">Oral</option><option value="Asia/Phnom_Penh">Phnom Penh</option><option value="Asia/Pontianak">Pontianak</option><option value="Asia/Pyongyang">Pyongyang</option><option value="Asia/Qatar">Qatar</option><option value="Asia/Qyzylorda">Qyzylorda</option><option value="Asia/Rangoon">Rangoon</option><option value="Asia/Riyadh">Riyadh</option><option value="Asia/Sakhalin">Sakhalin</option><option value="Asia/Samarkand">Samarkand</option><option value="Asia/Seoul">Seoul</option><option value="Asia/Shanghai">Shanghai</option><option value="Asia/Singapore">Singapore</option><option value="Asia/Srednekolymsk">Srednekolymsk</option><option value="Asia/Taipei">Taipei</option><option value="Asia/Tashkent">Tashkent</option><option value="Asia/Tbilisi">Tbilisi</option><option value="Asia/Tehran">Tehran</option><option value="Asia/Thimphu">Thimphu</option><option value="Asia/Tokyo">Tokyo</option><option value="Asia/Ulaanbaatar">Ulaanbaatar</option><option value="Asia/Urumqi">Urumqi</option><option value="Asia/Ust-Nera">Ust-Nera</option><option value="Asia/Vientiane">Vientiane</option><option value="Asia/Vladivostok">Vladivostok</option><option value="Asia/Yakutsk">Yakutsk</option><option value="Asia/Yekaterinburg">Yekaterinburg</option><option value="Asia/Yerevan">Yerevan</option>
                </optgroup>
                <optgroup label="Atlantic">
                    <option value="Atlantic/Azores">Azores</option><option value="Atlantic/Bermuda">Bermuda</option><option value="Atlantic/Canary">Canary</option><option value="Atlantic/Cape_Verde">Cape Verde</option><option value="Atlantic/Faroe">Faroe</option><option value="Atlantic/Madeira">Madeira</option><option value="Atlantic/Reykjavik">Reykjavik</option><option value="Atlantic/South_Georgia">South Georgia</option><option value="Atlantic/Stanley">Stanley</option><option value="Atlantic/St_Helena">St Helena</option>
                </optgroup>
                <optgroup label="Australia">
                    <option value="Australia/Adelaide">Adelaide</option><option value="Australia/Brisbane">Brisbane</option><option value="Australia/Broken_Hill">Broken Hill</option><option value="Australia/Currie">Currie</option><option value="Australia/Darwin">Darwin</option><option value="Australia/Eucla">Eucla</option><option value="Australia/Hobart">Hobart</option><option value="Australia/Lindeman">Lindeman</option><option value="Australia/Lord_Howe">Lord Howe</option><option value="Australia/Melbourne">Melbourne</option><option value="Australia/Perth">Perth</option><option value="Australia/Sydney">Sydney</option>
                </optgroup>
                <optgroup label="Europe">
                    <option value="Europe/Amsterdam">Amsterdam</option><option value="Europe/Andorra">Andorra</option><option value="Europe/Athens">Athens</option><option value="Europe/Belgrade">Belgrade</option><option value="Europe/Berlin">Berlin</option><option value="Europe/Bratislava">Bratislava</option><option value="Europe/Brussels">Brussels</option><option value="Europe/Bucharest">Bucharest</option><option value="Europe/Budapest">Budapest</option><option value="Europe/Busingen">Busingen</option><option value="Europe/Chisinau">Chisinau</option><option value="Europe/Copenhagen">Copenhagen</option><option value="Europe/Dublin">Dublin</option><option value="Europe/Gibraltar">Gibraltar</option><option value="Europe/Guernsey">Guernsey</option><option value="Europe/Helsinki">Helsinki</option><option value="Europe/Isle_of_Man">Isle of Man</option><option value="Europe/Istanbul">Istanbul</option><option value="Europe/Jersey">Jersey</option><option value="Europe/Kaliningrad">Kaliningrad</option><option value="Europe/Kiev">Kiev</option><option value="Europe/Lisbon">Lisbon</option><option value="Europe/Ljubljana">Ljubljana</option><option value="Europe/London">London</option><option value="Europe/Luxembourg">Luxembourg</option><option value="Europe/Madrid">Madrid</option><option value="Europe/Malta">Malta</option><option value="Europe/Mariehamn">Mariehamn</option><option value="Europe/Minsk">Minsk</option><option value="Europe/Monaco">Monaco</option><option value="Europe/Moscow">Moscow</option><option value="Europe/Oslo">Oslo</option><option value="Europe/Paris">Paris</option><option value="Europe/Podgorica">Podgorica</option><option value="Europe/Prague">Prague</option><option value="Europe/Riga">Riga</option><option value="Europe/Rome">Rome</option><option value="Europe/Samara">Samara</option><option value="Europe/San_Marino">San Marino</option><option value="Europe/Sarajevo">Sarajevo</option><option value="Europe/Simferopol">Simferopol</option><option value="Europe/Skopje">Skopje</option><option value="Europe/Sofia">Sofia</option><option value="Europe/Stockholm">Stockholm</option><option value="Europe/Tallinn">Tallinn</option><option value="Europe/Tirane">Tirane</option><option value="Europe/Uzhgorod">Uzhgorod</option><option value="Europe/Vaduz">Vaduz</option><option value="Europe/Vatican">Vatican</option><option value="Europe/Vienna">Vienna</option><option value="Europe/Vilnius">Vilnius</option><option value="Europe/Volgograd">Volgograd</option><option value="Europe/Warsaw">Warsaw</option><option value="Europe/Zagreb">Zagreb</option><option value="Europe/Zaporozhye">Zaporozhye</option><option value="Europe/Zurich">Zurich</option>
                </optgroup>
                <optgroup label="Indian">
                    <option value="Indian/Antananarivo">Antananarivo</option><option value="Indian/Chagos">Chagos</option><option value="Indian/Christmas">Christmas</option><option value="Indian/Cocos">Cocos</option><option value="Indian/Comoro">Comoro</option><option value="Indian/Kerguelen">Kerguelen</option><option value="Indian/Mahe">Mahe</option><option value="Indian/Maldives">Maldives</option><option value="Indian/Mauritius">Mauritius</option><option value="Indian/Mayotte">Mayotte</option><option value="Indian/Reunion">Reunion</option>
                </optgroup>
                <optgroup label="Pacific">
                    <option value="Pacific/Apia">Apia</option><option value="Pacific/Auckland">Auckland</option><option value="Pacific/Chatham">Chatham</option><option value="Pacific/Chuuk">Chuuk</option><option value="Pacific/Easter">Easter</option><option value="Pacific/Efate">Efate</option><option value="Pacific/Enderbury">Enderbury</option><option value="Pacific/Fakaofo">Fakaofo</option><option value="Pacific/Fiji">Fiji</option><option value="Pacific/Funafuti">Funafuti</option><option value="Pacific/Galapagos">Galapagos</option><option value="Pacific/Gambier">Gambier</option><option value="Pacific/Guadalcanal">Guadalcanal</option><option value="Pacific/Guam">Guam</option><option value="Pacific/Honolulu">Honolulu</option><option value="Pacific/Johnston">Johnston</option><option value="Pacific/Kiritimati">Kiritimati</option><option value="Pacific/Kosrae">Kosrae</option><option value="Pacific/Kwajalein">Kwajalein</option><option value="Pacific/Majuro">Majuro</option><option value="Pacific/Marquesas">Marquesas</option><option value="Pacific/Midway">Midway</option><option value="Pacific/Nauru">Nauru</option><option value="Pacific/Niue">Niue</option><option value="Pacific/Norfolk">Norfolk</option><option value="Pacific/Noumea">Noumea</option><option value="Pacific/Pago_Pago">Pago Pago</option><option value="Pacific/Palau">Palau</option><option value="Pacific/Pitcairn">Pitcairn</option><option value="Pacific/Pohnpei">Pohnpei</option><option value="Pacific/Port_Moresby">Port Moresby</option><option value="Pacific/Rarotonga">Rarotonga</option><option value="Pacific/Saipan">Saipan</option><option value="Pacific/Tahiti">Tahiti</option><option value="Pacific/Tarawa">Tarawa</option><option value="Pacific/Tongatapu">Tongatapu</option><option value="Pacific/Wake">Wake</option><option value="Pacific/Wallis">Wallis</option>
                </optgroup>
                <optgroup label="UTC">
                    <option value="UTC">UTC</option>
                </optgroup>';
	}


	public static function get_backup_slots_html( $current_slot ) {
		$backup_timing = array(
			'daily'          => 'Daily',
			'every_12_hours' => 'Every 12h',
			'every_6_hours'  => 'Every 6h',
			'every_1_hour'   => 'Realtime',
		);

		$html = '';

		foreach ( $backup_timing as $value => $name ) {
			$html .= self::get_dropdown_option_html( $value, $name, $current_slot );
		}

		return $html;
	}

	public static function get_user_excluded_files_more_than_size( &$config ) {

		$settings = self::get_excluded_files_more_than_size_settings( $config );

		$set_excluded_enabled = ( true == $settings['status'] ) ? 'checked="checked"' : '';

		$style = '';
		if ( true != $settings['status'] ) {
			$style = 'style="display: none"';
		}

		return '			
		<div class="two wide column ui toggle checkbox mainwp-parent-field">
				<input type="checkbox" id="user_excluded_files_more_than_size_status" name="user_excluded_files_more_than_size_status" value="1" ' . $set_excluded_enabled . '>
		</div>	
		<br /><br />
		<div id="user_excluded_files_more_than_size_div" ' . $style . '>
			<input class="wptc-split-column" type="text" name="user_excluded_files_more_than_size" id="user_excluded_files_more_than_size" placeholder="50" value=' . $settings['hr'] . '>MB
		</div>';
	}


	public static function get_database_encryption_settings( &$config, $type ) {
		$settings = $config->get_option( 'database_encrypt_settings' );
		$settings = ! empty( $settings ) ? unserialize( $settings ) : array();

		if ( empty( $settings ) ) {
			return false;
		}

		if ( $type === 'status' ) {
			return $settings['status'];
		} else {
			return ! empty( $settings['key'] ) ? base64_decode( $settings['key'] ) : '';
		}
	}

	public static function database_encryption_html( &$config ) {

			$status = self::get_database_encryption_settings( $config, 'status' );
			$key    = self::get_database_encryption_settings( $config, 'key' );

			$set_encryption_enabled = ( 'yes' === $status ) ? 'checked="checked"' : '';

			$style = '';

		if ( 'yes' !== $status ) {
			$style = 'style="display: none"';
		}

			return '
			<div class="two wide column ui toggle checkbox mainwp-parent-field">
					<input type="checkbox" id="database_encryption_status" name="database_encryption_status" value="1" ' . $set_encryption_enabled . '>
			</div>			
			<br /><br />
			<input ' . $style . ' type="text" name="database_encryption_key" id="database_encryption_key" placeholder="Enter encryption phrase" value=' . $key . '>';
	}


	public static function get_current_timezone( &$config ) {
		$current_timezone = $config->get_option( 'wptc_timezone' );
		return empty( $current_timezone ) ? 'UTC' : $current_timezone;
	}

	public static function get_all_timezone_html( &$config ) {
		$html = '<optgroup label="Africa">
				<option value="Africa/Abidjan">Abidjan</option><option value="Africa/Accra">Accra</option><option value="Africa/Addis_Ababa">Addis Ababa</option><option value="Africa/Algiers">Algiers</option><option value="Africa/Asmara">Asmara</option><option value="Africa/Bamako">Bamako</option><option value="Africa/Bangui">Bangui</option><option value="Africa/Banjul">Banjul</option><option value="Africa/Bissau">Bissau</option><option value="Africa/Blantyre">Blantyre</option><option value="Africa/Brazzaville">Brazzaville</option><option value="Africa/Bujumbura">Bujumbura</option><option value="Africa/Cairo">Cairo</option><option value="Africa/Casablanca">Casablanca</option><option value="Africa/Ceuta">Ceuta</option><option value="Africa/Conakry">Conakry</option><option value="Africa/Dakar">Dakar</option><option value="Africa/Dar_es_Salaam">Dar es Salaam</option><option value="Africa/Djibouti">Djibouti</option><option value="Africa/Douala">Douala</option><option value="Africa/El_Aaiun">El Aaiun</option><option value="Africa/Freetown">Freetown</option><option value="Africa/Gaborone">Gaborone</option><option value="Africa/Harare">Harare</option><option value="Africa/Johannesburg">Johannesburg</option><option value="Africa/Juba">Juba</option><option value="Africa/Kampala">Kampala</option><option value="Africa/Khartoum">Khartoum</option><option value="Africa/Kigali">Kigali</option><option value="Africa/Kinshasa">Kinshasa</option><option value="Africa/Lagos">Lagos</option><option value="Africa/Libreville">Libreville</option><option value="Africa/Lome">Lome</option><option value="Africa/Luanda">Luanda</option><option value="Africa/Lubumbashi">Lubumbashi</option><option value="Africa/Lusaka">Lusaka</option><option value="Africa/Malabo">Malabo</option><option value="Africa/Maputo">Maputo</option><option value="Africa/Maseru">Maseru</option><option value="Africa/Mbabane">Mbabane</option><option value="Africa/Mogadishu">Mogadishu</option><option value="Africa/Monrovia">Monrovia</option><option value="Africa/Nairobi">Nairobi</option><option value="Africa/Ndjamena">Ndjamena</option><option value="Africa/Niamey">Niamey</option><option value="Africa/Nouakchott">Nouakchott</option><option value="Africa/Ouagadougou">Ouagadougou</option><option value="Africa/Porto-Novo">Porto-Novo</option><option value="Africa/Sao_Tome">Sao Tome</option><option value="Africa/Tripoli">Tripoli</option><option value="Africa/Tunis">Tunis</option><option value="Africa/Windhoek">Windhoek</option>
			</optgroup>
			<optgroup label="America">
				<option value="America/Adak">Adak</option><option value="America/Anchorage">Anchorage</option><option value="America/Anguilla">Anguilla</option><option value="America/Antigua">Antigua</option><option value="America/Araguaina">Araguaina</option><option value="America/Argentina/Buenos_Aires">Argentina - Buenos Aires</option><option value="America/Argentina/Catamarca">Argentina - Catamarca</option><option value="America/Argentina/Cordoba">Argentina - Cordoba</option><option value="America/Argentina/Jujuy">Argentina - Jujuy</option><option value="America/Argentina/La_Rioja">Argentina - La Rioja</option><option value="America/Argentina/Mendoza">Argentina - Mendoza</option><option value="America/Argentina/Rio_Gallegos">Argentina - Rio Gallegos</option><option value="America/Argentina/Salta">Argentina - Salta</option><option value="America/Argentina/San_Juan">Argentina - San Juan</option><option value="America/Argentina/San_Luis">Argentina - San Luis</option><option value="America/Argentina/Tucuman">Argentina - Tucuman</option><option value="America/Argentina/Ushuaia">Argentina - Ushuaia</option><option value="America/Aruba">Aruba</option><option value="America/Asuncion">Asuncion</option><option value="America/Atikokan">Atikokan</option><option value="America/Bahia">Bahia</option><option value="America/Bahia_Banderas">Bahia Banderas</option><option value="America/Barbados">Barbados</option><option value="America/Belem">Belem</option><option value="America/Belize">Belize</option><option value="America/Blanc-Sablon">Blanc-Sablon</option><option value="America/Boa_Vista">Boa Vista</option><option value="America/Bogota">Bogota</option><option value="America/Boise">Boise</option><option value="America/Cambridge_Bay">Cambridge Bay</option><option value="America/Campo_Grande">Campo Grande</option><option value="America/Cancun">Cancun</option><option value="America/Caracas">Caracas</option><option value="America/Cayenne">Cayenne</option><option value="America/Cayman">Cayman</option><option value="America/Chicago">Chicago</option><option value="America/Chihuahua">Chihuahua</option><option value="America/Costa_Rica">Costa Rica</option><option value="America/Creston">Creston</option><option value="America/Cuiaba">Cuiaba</option><option value="America/Curacao">Curacao</option><option value="America/Danmarkshavn">Danmarkshavn</option><option value="America/Dawson">Dawson</option><option value="America/Dawson_Creek">Dawson Creek</option><option value="America/Denver">Denver</option><option value="America/Detroit">Detroit</option><option value="America/Dominica">Dominica</option><option value="America/Edmonton">Edmonton</option><option value="America/Eirunepe">Eirunepe</option><option value="America/El_Salvador">El Salvador</option><option value="America/Fortaleza">Fortaleza</option><option value="America/Glace_Bay">Glace Bay</option><option value="America/Godthab">Godthab</option><option value="America/Goose_Bay">Goose Bay</option><option value="America/Grand_Turk">Grand Turk</option><option value="America/Grenada">Grenada</option><option value="America/Guadeloupe">Guadeloupe</option><option value="America/Guatemala">Guatemala</option><option value="America/Guayaquil">Guayaquil</option><option value="America/Guyana">Guyana</option><option value="America/Halifax">Halifax</option><option value="America/Havana">Havana</option><option value="America/Hermosillo">Hermosillo</option><option value="America/Indiana/Indianapolis">Indiana - Indianapolis</option><option value="America/Indiana/Knox">Indiana - Knox</option><option value="America/Indiana/Marengo">Indiana - Marengo</option><option value="America/Indiana/Petersburg">Indiana - Petersburg</option><option value="America/Indiana/Tell_City">Indiana - Tell City</option><option value="America/Indiana/Vevay">Indiana - Vevay</option><option value="America/Indiana/Vincennes">Indiana - Vincennes</option><option value="America/Indiana/Winamac">Indiana - Winamac</option><option value="America/Inuvik">Inuvik</option><option value="America/Iqaluit">Iqaluit</option><option value="America/Jamaica">Jamaica</option><option value="America/Juneau">Juneau</option><option value="America/Kentucky/Louisville">Kentucky - Louisville</option><option value="America/Kentucky/Monticello">Kentucky - Monticello</option><option value="America/Kralendijk">Kralendijk</option><option value="America/La_Paz">La Paz</option><option value="America/Lima">Lima</option><option value="America/Los_Angeles">Los Angeles</option><option value="America/Lower_Princes">Lower Princes</option><option value="America/Maceio">Maceio</option><option value="America/Managua">Managua</option><option value="America/Manaus">Manaus</option><option value="America/Marigot">Marigot</option><option value="America/Martinique">Martinique</option><option value="America/Matamoros">Matamoros</option><option value="America/Mazatlan">Mazatlan</option><option value="America/Menominee">Menominee</option><option value="America/Merida">Merida</option><option value="America/Metlakatla">Metlakatla</option><option value="America/Mexico_City">Mexico City</option><option value="America/Miquelon">Miquelon</option><option value="America/Moncton">Moncton</option><option value="America/Monterrey">Monterrey</option><option value="America/Montevideo">Montevideo</option><option value="America/Montserrat">Montserrat</option><option value="America/Nassau">Nassau</option><option value="America/New_York">New York</option><option value="America/Nipigon">Nipigon</option><option value="America/Nome">Nome</option><option value="America/Noronha">Noronha</option><option value="America/North_Dakota/Beulah">North Dakota - Beulah</option><option value="America/North_Dakota/Center">North Dakota - Center</option><option value="America/North_Dakota/New_Salem">North Dakota - New Salem</option><option value="America/Ojinaga">Ojinaga</option><option value="America/Panama">Panama</option><option value="America/Pangnirtung">Pangnirtung</option><option value="America/Paramaribo">Paramaribo</option><option value="America/Phoenix">Phoenix</option><option value="America/Port-au-Prince">Port-au-Prince</option><option value="America/Port_of_Spain">Port of Spain</option><option value="America/Porto_Velho">Porto Velho</option><option value="America/Puerto_Rico">Puerto Rico</option><option value="America/Rainy_River">Rainy River</option><option value="America/Rankin_Inlet">Rankin Inlet</option><option value="America/Recife">Recife</option><option value="America/Regina">Regina</option><option value="America/Resolute">Resolute</option><option value="America/Rio_Branco">Rio Branco</option><option value="America/Santa_Isabel">Santa Isabel</option><option value="America/Santarem">Santarem</option><option value="America/Santiago">Santiago</option><option value="America/Santo_Domingo">Santo Domingo</option><option value="America/Sao_Paulo">Sao Paulo</option><option value="America/Scoresbysund">Scoresbysund</option><option value="America/Sitka">Sitka</option><option value="America/St_Barthelemy">St Barthelemy</option><option value="America/St_Johns">St Johns</option><option value="America/St_Kitts">St Kitts</option><option value="America/St_Lucia">St Lucia</option><option value="America/St_Thomas">St Thomas</option><option value="America/St_Vincent">St Vincent</option><option value="America/Swift_Current">Swift Current</option><option value="America/Tegucigalpa">Tegucigalpa</option><option value="America/Thule">Thule</option><option value="America/Thunder_Bay">Thunder Bay</option><option value="America/Tijuana">Tijuana</option><option value="America/Toronto">Toronto</option><option value="America/Tortola">Tortola</option><option value="America/Vancouver">Vancouver</option><option value="America/Whitehorse">Whitehorse</option><option value="America/Winnipeg">Winnipeg</option><option value="America/Yakutat">Yakutat</option><option value="America/Yellowknife">Yellowknife</option>
			</optgroup>
			<optgroup label="Antarctica">
				<option value="Antarctica/Casey">Casey</option><option value="Antarctica/Davis">Davis</option><option value="Antarctica/DumontDUrville">DumontDUrville</option><option value="Antarctica/Macquarie">Macquarie</option><option value="Antarctica/Mawson">Mawson</option><option value="Antarctica/McMurdo">McMurdo</option><option value="Antarctica/Palmer">Palmer</option><option value="Antarctica/Rothera">Rothera</option><option value="Antarctica/Syowa">Syowa</option><option value="Antarctica/Troll">Troll</option><option value="Antarctica/Vostok">Vostok</option>
			</optgroup>
			<optgroup label="Arctic">
				<option value="Arctic/Longyearbyen">Longyearbyen</option>
			</optgroup>
			<optgroup label="Asia">
				<option value="Asia/Aden">Aden</option><option value="Asia/Almaty">Almaty</option><option value="Asia/Amman">Amman</option><option value="Asia/Anadyr">Anadyr</option><option value="Asia/Aqtau">Aqtau</option><option value="Asia/Aqtobe">Aqtobe</option><option value="Asia/Ashgabat">Ashgabat</option><option value="Asia/Baghdad">Baghdad</option><option value="Asia/Bahrain">Bahrain</option><option value="Asia/Baku">Baku</option><option value="Asia/Bangkok">Bangkok</option><option value="Asia/Beirut">Beirut</option><option value="Asia/Bishkek">Bishkek</option><option value="Asia/Brunei">Brunei</option><option value="Asia/Chita">Chita</option><option value="Asia/Choibalsan">Choibalsan</option><option value="Asia/Colombo">Colombo</option><option value="Asia/Damascus">Damascus</option><option value="Asia/Dhaka">Dhaka</option><option value="Asia/Dili">Dili</option><option value="Asia/Dubai">Dubai</option><option value="Asia/Dushanbe">Dushanbe</option><option value="Asia/Gaza">Gaza</option><option value="Asia/Hebron">Hebron</option><option value="Asia/Ho_Chi_Minh">Ho Chi Minh</option><option value="Asia/Hong_Kong">Hong Kong</option><option value="Asia/Hovd">Hovd</option><option value="Asia/Irkutsk">Irkutsk</option><option value="Asia/Jakarta">Jakarta</option><option value="Asia/Jayapura">Jayapura</option><option value="Asia/Jerusalem">Jerusalem</option><option value="Asia/Kabul">Kabul</option><option value="Asia/Kamchatka">Kamchatka</option><option value="Asia/Karachi">Karachi</option><option value="Asia/Kathmandu">Kathmandu</option><option value="Asia/Khandyga">Khandyga</option><option value="Asia/Kolkata">Kolkata</option><option value="Asia/Krasnoyarsk">Krasnoyarsk</option><option value="Asia/Kuala_Lumpur">Kuala Lumpur</option><option value="Asia/Kuching">Kuching</option><option value="Asia/Kuwait">Kuwait</option><option value="Asia/Macau">Macau</option><option value="Asia/Magadan">Magadan</option><option value="Asia/Makassar">Makassar</option><option value="Asia/Manila">Manila</option><option value="Asia/Muscat">Muscat</option><option value="Asia/Nicosia">Nicosia</option><option value="Asia/Novokuznetsk">Novokuznetsk</option><option value="Asia/Novosibirsk">Novosibirsk</option><option value="Asia/Omsk">Omsk</option><option value="Asia/Oral">Oral</option><option value="Asia/Phnom_Penh">Phnom Penh</option><option value="Asia/Pontianak">Pontianak</option><option value="Asia/Pyongyang">Pyongyang</option><option value="Asia/Qatar">Qatar</option><option value="Asia/Qyzylorda">Qyzylorda</option><option value="Asia/Rangoon">Rangoon</option><option value="Asia/Riyadh">Riyadh</option><option value="Asia/Sakhalin">Sakhalin</option><option value="Asia/Samarkand">Samarkand</option><option value="Asia/Seoul">Seoul</option><option value="Asia/Shanghai">Shanghai</option><option value="Asia/Singapore">Singapore</option><option value="Asia/Srednekolymsk">Srednekolymsk</option><option value="Asia/Taipei">Taipei</option><option value="Asia/Tashkent">Tashkent</option><option value="Asia/Tbilisi">Tbilisi</option><option value="Asia/Tehran">Tehran</option><option value="Asia/Thimphu">Thimphu</option><option value="Asia/Tokyo">Tokyo</option><option value="Asia/Ulaanbaatar">Ulaanbaatar</option><option value="Asia/Urumqi">Urumqi</option><option value="Asia/Ust-Nera">Ust-Nera</option><option value="Asia/Vientiane">Vientiane</option><option value="Asia/Vladivostok">Vladivostok</option><option value="Asia/Yakutsk">Yakutsk</option><option value="Asia/Yekaterinburg">Yekaterinburg</option><option value="Asia/Yerevan">Yerevan</option>
			</optgroup>
			<optgroup label="Atlantic">
				<option value="Atlantic/Azores">Azores</option><option value="Atlantic/Bermuda">Bermuda</option><option value="Atlantic/Canary">Canary</option><option value="Atlantic/Cape_Verde">Cape Verde</option><option value="Atlantic/Faroe">Faroe</option><option value="Atlantic/Madeira">Madeira</option><option value="Atlantic/Reykjavik">Reykjavik</option><option value="Atlantic/South_Georgia">South Georgia</option><option value="Atlantic/Stanley">Stanley</option><option value="Atlantic/St_Helena">St Helena</option>
			</optgroup>
			<optgroup label="Australia">
				<option value="Australia/Adelaide">Adelaide</option><option value="Australia/Brisbane">Brisbane</option><option value="Australia/Broken_Hill">Broken Hill</option><option value="Australia/Currie">Currie</option><option value="Australia/Darwin">Darwin</option><option value="Australia/Eucla">Eucla</option><option value="Australia/Hobart">Hobart</option><option value="Australia/Lindeman">Lindeman</option><option value="Australia/Lord_Howe">Lord Howe</option><option value="Australia/Melbourne">Melbourne</option><option value="Australia/Perth">Perth</option><option value="Australia/Sydney">Sydney</option>
			</optgroup>
			<optgroup label="Europe">
				<option value="Europe/Amsterdam">Amsterdam</option><option value="Europe/Andorra">Andorra</option><option value="Europe/Athens">Athens</option><option value="Europe/Belgrade">Belgrade</option><option value="Europe/Berlin">Berlin</option><option value="Europe/Bratislava">Bratislava</option><option value="Europe/Brussels">Brussels</option><option value="Europe/Bucharest">Bucharest</option><option value="Europe/Budapest">Budapest</option><option value="Europe/Busingen">Busingen</option><option value="Europe/Chisinau">Chisinau</option><option value="Europe/Copenhagen">Copenhagen</option><option value="Europe/Dublin">Dublin</option><option value="Europe/Gibraltar">Gibraltar</option><option value="Europe/Guernsey">Guernsey</option><option value="Europe/Helsinki">Helsinki</option><option value="Europe/Isle_of_Man">Isle of Man</option><option value="Europe/Istanbul">Istanbul</option><option value="Europe/Jersey">Jersey</option><option value="Europe/Kaliningrad">Kaliningrad</option><option value="Europe/Kiev">Kiev</option><option value="Europe/Lisbon">Lisbon</option><option value="Europe/Ljubljana">Ljubljana</option><option value="Europe/London">London</option><option value="Europe/Luxembourg">Luxembourg</option><option value="Europe/Madrid">Madrid</option><option value="Europe/Malta">Malta</option><option value="Europe/Mariehamn">Mariehamn</option><option value="Europe/Minsk">Minsk</option><option value="Europe/Monaco">Monaco</option><option value="Europe/Moscow">Moscow</option><option value="Europe/Oslo">Oslo</option><option value="Europe/Paris">Paris</option><option value="Europe/Podgorica">Podgorica</option><option value="Europe/Prague">Prague</option><option value="Europe/Riga">Riga</option><option value="Europe/Rome">Rome</option><option value="Europe/Samara">Samara</option><option value="Europe/San_Marino">San Marino</option><option value="Europe/Sarajevo">Sarajevo</option><option value="Europe/Simferopol">Simferopol</option><option value="Europe/Skopje">Skopje</option><option value="Europe/Sofia">Sofia</option><option value="Europe/Stockholm">Stockholm</option><option value="Europe/Tallinn">Tallinn</option><option value="Europe/Tirane">Tirane</option><option value="Europe/Uzhgorod">Uzhgorod</option><option value="Europe/Vaduz">Vaduz</option><option value="Europe/Vatican">Vatican</option><option value="Europe/Vienna">Vienna</option><option value="Europe/Vilnius">Vilnius</option><option value="Europe/Volgograd">Volgograd</option><option value="Europe/Warsaw">Warsaw</option><option value="Europe/Zagreb">Zagreb</option><option value="Europe/Zaporozhye">Zaporozhye</option><option value="Europe/Zurich">Zurich</option>
			</optgroup>
			<optgroup label="Indian">
				<option value="Indian/Antananarivo">Antananarivo</option><option value="Indian/Chagos">Chagos</option><option value="Indian/Christmas">Christmas</option><option value="Indian/Cocos">Cocos</option><option value="Indian/Comoro">Comoro</option><option value="Indian/Kerguelen">Kerguelen</option><option value="Indian/Mahe">Mahe</option><option value="Indian/Maldives">Maldives</option><option value="Indian/Mauritius">Mauritius</option><option value="Indian/Mayotte">Mayotte</option><option value="Indian/Reunion">Reunion</option>
			</optgroup>
			<optgroup label="Pacific">
				<option value="Pacific/Apia">Apia</option><option value="Pacific/Auckland">Auckland</option><option value="Pacific/Chatham">Chatham</option><option value="Pacific/Chuuk">Chuuk</option><option value="Pacific/Easter">Easter</option><option value="Pacific/Efate">Efate</option><option value="Pacific/Enderbury">Enderbury</option><option value="Pacific/Fakaofo">Fakaofo</option><option value="Pacific/Fiji">Fiji</option><option value="Pacific/Funafuti">Funafuti</option><option value="Pacific/Galapagos">Galapagos</option><option value="Pacific/Gambier">Gambier</option><option value="Pacific/Guadalcanal">Guadalcanal</option><option value="Pacific/Guam">Guam</option><option value="Pacific/Honolulu">Honolulu</option><option value="Pacific/Johnston">Johnston</option><option value="Pacific/Kiritimati">Kiritimati</option><option value="Pacific/Kosrae">Kosrae</option><option value="Pacific/Kwajalein">Kwajalein</option><option value="Pacific/Majuro">Majuro</option><option value="Pacific/Marquesas">Marquesas</option><option value="Pacific/Midway">Midway</option><option value="Pacific/Nauru">Nauru</option><option value="Pacific/Niue">Niue</option><option value="Pacific/Norfolk">Norfolk</option><option value="Pacific/Noumea">Noumea</option><option value="Pacific/Pago_Pago">Pago Pago</option><option value="Pacific/Palau">Palau</option><option value="Pacific/Pitcairn">Pitcairn</option><option value="Pacific/Pohnpei">Pohnpei</option><option value="Pacific/Port_Moresby">Port Moresby</option><option value="Pacific/Rarotonga">Rarotonga</option><option value="Pacific/Saipan">Saipan</option><option value="Pacific/Tahiti">Tahiti</option><option value="Pacific/Tarawa">Tarawa</option><option value="Pacific/Tongatapu">Tongatapu</option><option value="Pacific/Wake">Wake</option><option value="Pacific/Wallis">Wallis</option>
			</optgroup>
			<optgroup label="UTC">
				<option value="UTC">UTC</option>
			</optgroup>';

		$current_timezone = self::get_current_timezone( $config );

		if ( $current_timezone ) {
			$html = str_replace( $current_timezone . '"', $current_timezone . '" selected ', $html );
		}

			return $html;
	}

	public function convert_mb_to_bytes( $size ) {
		$size = trim( $size );
		return $size * pow( 1024, 2 );
	}

	public static function convert_bytes_to_mb( $size ) {

		if ( empty( $size ) ) {
			return 0;
		}

		$size = trim( $size );
		return ( ( $size / 1024 ) / 1024 );
	}

	public static function get_excluded_files_more_than_size_settings( &$config ) {

		$raw_settings = $config->get_option( 'user_excluded_files_more_than_size_settings' );

		if ( empty( $raw_settings ) ) {
			return array(
				'status' => 'no',
				'size'   => 50 * 1024 * 1024,
				'hr'     => 50,
			);
		}

		$settings       = unserialize( $raw_settings );
		$settings['hr'] = self::convert_bytes_to_mb( $settings['size'] );
		return $settings;
	}


	public static function gen_tab_backup_opts() {

		global $mainwp_timecapsule_current_site_id;
		$config                 = MainWP_WPTC_Factory::get( 'config' );
		$is_free_user_wptc      = false;
		$other_process_going_on = false;

		$revision_limit = $config->get_option( 'revision_limit' );

		$get_backup_db_query_limit = $config->get_option( 'backup_db_query_limit' );
		if ( ! $get_backup_db_query_limit ) {
			$get_backup_db_query_limit = 300;
		}

		$current_slot = $config->get_option( 'backup_slot' );
		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php _e( 'Backup Settings', 'mainwp-timecapsule-extension' ); ?></h3>
		<div <?php echo $is_free_user_wptc ? "style='display: block;'" : "style='display: none;'"; ?>  class="ui yellow message"><?php esc_attr_e( 'Sheduled backup will happen every 7 days once.', 'mainwp-timecapsule-extension' ); ?></div>
		<div <?php echo ( $other_process_going_on ) ? "style='display: block;'" : "style='display: none;'"; ?>  class="setting_backup_progress_note_wptc disable-setting-wptc ui yellow message">
						<?php
						$message = ( $other_process_going_on ) ? $other_process_going_on : 'Backup';
						esc_attr_e( $message . ' is currently running. Please wait until it finishes to change above settings.', 'mainwp-timecapsule-extension' );
						?>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Backup schedule & timezone', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="four wide column">
				<select <?php echo ( $other_process_going_on ) ? 'disabled' : ''; ?> name="select_wptc_backup_slots" id="select_wptc_backup_slots">
					<?php echo self::get_backup_slots_html( $current_slot ); ?>
									</select>
			</div>
			<div class="two wide column">
				<select  <?php echo ( $other_process_going_on ) ? 'disabled' : ''; ?>  style="display:none" name="select_wptc_default_schedule" id="select_wptc_default_schedule">
					<?php echo self::get_schedule_times_div_wptc( $type = 'backup', false, $config ); ?>
									</select>
			</div>
			<div class="four wide column">
				<select id="wptc_timezone" <?php echo ( $other_process_going_on ) ? 'disabled' : ''; ?> name="wptc_timezone">
									<?php echo self::get_all_timezone_html( $config ); ?>
								</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Restore window', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="six wide column">
				<div id="wptc-restore-window-slider"></div>
			</div>
			<div class="four wide column">
				<div id="wptc_settings_revision_limit"><?php echo $revision_limit . ' Days'; ?></div>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'On-demand backup', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="six wide column">
				<?php if ( $mainwp_timecapsule_current_site_id ) { ?>
					<a class="ui green button" id="start_backup_from_settings" action="start"><?php _e( 'Backup Now', 'mainwp-timecapsule-extension' ); ?></a>
								<?php } else { ?>
					<a class="ui green button" onclick="if (!confirm('Are you sure you want to backup now?')) return false; mainwp_wptc_general_load_sites('start_backup'); return false" action="start"><?php _e( 'Backup Now', 'mainwp-timecapsule-extension' ); ?></a>
								<?php } ?>
			</div>
		</div>

		<?php if ( $mainwp_timecapsule_current_site_id ) { ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Include/Exclude content', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="four wide column">
				<button class="ui green basic button wptc_dropdown" id="toggle_exlclude_files_n_folders"><?php _e( 'Folders & Files', 'mainwp-timecapsule-extension' ); ?></button>
			</div>
			<div class="six wide column">
								<div style="display:none" id="wptc_exc_files"></div>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( '', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="four wide column">
				<button class="ui green basic button wptc_dropdown" id="toggle_wptc_db_tables"><?php _e( 'Database', 'mainwp-timecapsule-extension' ); ?></button>
			</div>
			<div class="six wide column">
									<div style="display:none" id="wptc_exc_db_files"></div>
								</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( '', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="ten wide column">
				<a class="ui button" id="wptc_analyze_inc_exc_lists"><?php _e( 'Analyze Tables', 'mainwp-timecapsule-extension' ); ?></a>
			</div>
		</div>
				<?php } ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Encrypt database', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="ten wide column">
				<?php echo self::database_encryption_html( $config ); ?>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Exclude files of these extensions', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="ten wide column">
				<?php	$user_excluded_extenstions = $config->get_option( 'user_excluded_extenstions' ); ?>
				<input class="wptc-split-column" type="text" name="user_excluded_extenstions" id="user_excluded_extenstions" placeholder="Eg. .mp4, .mov" value="<?php echo $user_excluded_extenstions; ?>" />
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Exclude any files bigger than a specific size', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="ten wide column">
				<?php echo self::get_user_excluded_files_more_than_size( $config ); ?>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'DB rows backup limit', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="ten wide column">
				<input name="backup_db_query_limit" type="number" min="10" id="backup_db_query_limit" value="<?php echo intval( $get_backup_db_query_limit ); ?>">
			</div>
		</div>
			<?php self::gen_save_settings_button( 'backup' ); ?>
			<script type="text/javascript" language="javascript">
				jQuery(document).ready(function($) {
					show_schedule_time_wptc();
					jQuery( "#wptc-restore-window-slider" ).slider({
						value : <?php echo intval( $revision_limit ); ?>,
						min   : 3,
						max   : 365,
						step  : 1,
						slide: function( event, ui ) {
							jQuery( "#wptc_settings_revision_limit" ).html(  ui.value + " Days");
						}
					});
					
					
					jQuery('#database_encryption_status').change(function() {
						if(jQuery(this).is(":checked")) {
							jQuery('#database_encryption_key').show();
						} else {
							jQuery('#database_encryption_key').hide();
						}
					});
					
					
				});

		<?php
		if ( $mainwp_timecapsule_current_site_id ) {
			?>
				mainwp_get_current_backup_status_wptc();
			<?php } ?>

				jQuery( '#start_backup_from_settings' ).click( function() {
				  if ( jQuery( "#start_backup_from_settings" ).hasClass( 'disabled' ) ) {
						console.log( 'button disabled' );
						return false;
					}
					mainwp_start_manual_backup_wptc( this );
				  } );
			</script>

		<?php
	}

	public static function get_checkbox_input_wptc( $id, $value = '', $current_setting = '', $name = '' ) {
		$is_checked = '';
		if ( $current_setting == $value ) {
			$is_checked = 'checked';
		}

		$input  = '';
		$input .= '<input name="' . $name . '" type="checkbox" id="' . $id . '"	' . $is_checked . ' value="' . $value . '">';

		return $input;
	}

	public static function get_auto_update_settings( $settings_serialized ) {
		if ( empty( $settings_serialized ) ) {
			return false;
		}

		$settings = unserialize( $settings_serialized );
		return $settings['update_settings'];
	}

	public static function gen_tab_backup_auto() {

			global $mainwp_timecapsule_current_site_id;

			$config          = MainWP_WPTC_Factory::get( 'config' );
			$current_setting = $config->get_option( 'backup_before_update_setting' );
			$settings_rows   = '';

			$settings_rows .= '
                <div class="ui hidden divider"></div>
                <h3 class="ui dividing header">' . __( 'Backup/Auto Updates', 'mainwp-timecapsule-extension' ) . '</h3>

                <div class="ui grid field">
                    <label class="six wide column middle aligned">' . __( 'Backup before manual updates', 'mainwp-timecapsule-extension' ) . '</label>
                    <div class="ten wide column ui toggle checkbox">' . self::get_checkbox_input_wptc( 'backup_before_update_always', 'always', $current_setting, 'backup_before_update_setting' ) . '</div>
                </div>';

			$settings_serialized = $config->get_option( 'wptc_auto_update_settings' );
			$settings_rows      .= self::get_auto_update_settings_html( $current_setting, $settings_serialized );

			echo $settings_rows;

			self::gen_save_settings_button( 'backup_auto' );
	}

	public static function get_auto_update_settings_html( $bbu_setting, $settings_serialized ) {
		$auto_updater_settings      = self::get_auto_update_settings( $settings_serialized );
		$enable_auto_update_wptc    = $disable_auto_update_wptc = $show_options = $schedule_time_show_status = '';
		$schedule_auto_update_style = '';

		if ( $auto_updater_settings['status'] == 'yes' ) {
			$enable_auto_update_wptc = 'checked';
		} else {
			$disable_auto_update_wptc   = 'checked';
			$schedule_auto_update_style = 'style="display:none"';
		}

		$core_major         = $auto_updater_settings['core']['major']['status'];
		$core_major_checked = ( $core_major ) ? 'checked="checked"' : '';

		$core_minor         = $auto_updater_settings['core']['minor']['status'];
		$core_minor_checked = ( $core_minor ) ? 'checked="checked"' : '';

		$plugins         = $auto_updater_settings['plugins']['status'];
		$plugins_checked = ( $plugins ) ? 'checked="checked"' : '';

		$themes         = $auto_updater_settings['themes']['status'];
		$themes_checked = ( $themes ) ? 'checked="checked"' : '';

		$schedule_enabled     = ! empty( $auto_updater_settings['schedule']['enabled'] ) ? $auto_updater_settings['schedule']['enabled'] : '';
		$set_schedule_enabled = ( $schedule_enabled ) ? 'checked="checked"' : '';

		$schedule_time = ! empty( $auto_updater_settings['schedule']['time'] ) ? $auto_updater_settings['schedule']['time'] : '';

		if ( ! $schedule_enabled ) {
			$schedule_time_show_status = 'style="display:none"';
		}

		$settings_rows  = '';
		$settings_rows .= '
		<div class="ui grid field mainwp-parent-field">
			<label class="six wide column middle aligned">' . __( 'Enable auto-updates (for WP, Themes, Plugins)', 'mainwp-timecapsule-extension' ) . '</label>
			<div class="ten wide column">
				<div class="ui radio checkbox"><input name="auto_update_wptc_setting" id="enable_auto_update_wptc" type="radio" ' . $enable_auto_update_wptc . ' value="yes"><label>' . __( 'Yes', 'mainwp-timecapsule-extension' ) . '</label></div>
				<div class="ui radio checkbox"><input name="auto_update_wptc_setting" id="disable_auto_update_wptc" type="radio" ' . $disable_auto_update_wptc . ' value="no"><label>' . __( 'No', 'mainwp-timecapsule-extension' ) . '</label></div>
			</div>
		</div>
		<div class="mainwp-child-field" ' . $schedule_auto_update_style . ' >
			<div class="ui grid field">
				<label class="six wide column middle aligned">' . __( 'Set update time', 'mainwp-timecapsule-extension' ) . '</label>
				<div class="two wide column ui toggle checkbox mainwp-parent-field">
						<input type="checkbox" id="wptc_auto_update_schedule_enabled" name="wptc_auto_update_schedule_enabled" value="1" ' . $set_schedule_enabled . '>
				</div>
				<div class="four wide column mainwp-child-field" ' . $schedule_time_show_status . '>
				<select name="wptc_auto_update_schedule_time" id="wptc_auto_update_schedule_time" class="ui dropdown">' . self::get_schedule_times_div_wptc( $type = 'auto_update', $schedule_time ) . '</select>
				</div>
			</div>
			<div class="ui grid field">
				<label class="six wide column middle aligned">' . __( 'Update WordPress Core automatically?', 'mainwp-timecapsule-extension' ) . '</label>
				<div class="ten wide column">
					<div class="ui toggle checkbox"><input type="checkbox" id="wptc_auto_core_major" name="wptc_auto_core_major" value="1" ' . $core_major_checked . '><label>' . __( 'Major versions', 'mainwp-timecapsule-extension' ) . '</label></div>
					<br /><br />
					<div class="ui toggle checkbox"><input type="checkbox" id="wptc_auto_core_minor" name="wptc_auto_core_minor" value="1" ' . $core_minor_checked . '><label>' . __( 'Minor and security versions <strong>(Strongly Recommended)', 'mainwp-timecapsule-extension' ) . '</label></div>
				</div>
			</div>
			<div class="ui grid field" >
				<label class="six wide column middle aligned">' . __( 'Update your plugins automatically?', 'mainwp-timecapsule-extension' ) . '</label>
				<div class="ten wide column ui toggle checkbox">
					<input type="checkbox" id="wptc_auto_plugins" name="wptc_auto_plugins" value="1" ' . $plugins_checked . '>
						<div id="wptc-select-all-plugins-au" style="display:none; cursor: pointer; width: 100px; margin: 7px 14px 10px 19px;">
							<span class="fancytree-checkbox"></span>
							<a>Select All</a>
						</div>
							<div style="display: none; width:400px" id="wptc_auto_update_plugins_dw"></div>
							<input style="display: none;" type="hidden" id="auto_include_plugins_wptc" name="auto_include_plugins_wptc"/>
				</div>
			</div>
			<div class="ui grid field" >
				<label class="six wide column middle aligned">' . __( 'Update your themes automatically?', 'mainwp-timecapsule-extension' ) . '</label>
				<div class="ten wide column ui toggle checkbox">
					<input type="checkbox" id="wptc_auto_themes" name="wptc_auto_themes" value="1" ' . $themes_checked . '>
							<div id="wptc-select-all-themes-au" style="display:none; cursor: pointer; width: 100px; margin: 7px 14px 10px 19px;">
								<span class="fancytree-checkbox"></span>
								<a>Select All</a>
							</div>
								<div style="display: none; width:400px" id="wptc_auto_update_themes_dw"></div>
								<input style="display: none;" type="hidden" id="auto_include_themes_wptc" name="auto_include_themes_wptc"/>
				</div>
			</div>
		</div>';

		return $settings_rows;
	}


	public static function gen_tab_adv() {
		$site_id          = isset( $_GET['id'] ) ? $_GET['id'] : 0;
		$is_backup_paused = false;
		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php _e( 'Communication Status', 'mainwp-timecapsule-extension' ); ?></h3>

		<div class="ui grid field" id="wptc_cron_status_paused" <?php echo ( $is_backup_paused ) ? "style='display:block'" : "style='display:none'"; ?>>
			<label class="six wide column middle aligned"><?php _e( 'Backup Status', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="eight wide column">
				<span class='cron_current_status ui red mini label'><?php _e( 'Backup stopped due to server communication error', 'mainwp-timecapsule-extension' ); ?></span> - <a class="resume_backup_wptc ui green mini button"><?php _e( 'Resume Backup', 'mainwp-timecapsule-extension' ); ?></a>
			</div>
		</div>

		<div class="ui grid field" id="wptc_cron_status_passed" <?php echo ( $is_backup_paused ) ? "style='display:none'" : "style='display:block'"; ?>>
			<label class="six wide column middle aligned"><?php _e( 'Plugin - Server Communication Status', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="three wide column" style="display: none">
				<span class="cron_current_status ui green label" ><?php _e( 'Success', 'mainwp-timecapsule-extension' ); ?></span>
			</div>
			<div class="six wide column">
				<a class="mainwp_test_cron_wptc ui mini green basic button"><?php _e( 'Test Again', 'mainwp-timecapsule-extension' ); ?></a>
			</div>
		</div>

		<?php if ( $site_id ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Manually decrypt a database backup file', 'mainwp-timecapsule-extension' ); ?></label>
			<div class="eight wide column">
						<?php
						$location = '/wp-admin/admin.php?page=wp-time-capsule-settings#wp-time-capsule-tab-advanced';
						$location = base64_encode( $location );
						$link     = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $site_id . '&openUrl=yes&location=' . $location . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' );
						echo '<a href="' . $link . '" target="_blank" class="ui green basic mini button">Open to process</a>';
						?>
		</div>
		</div>
	  <?php endif; ?>
		  <?php
	}

	public static function gen_tab_info() {
		?>
		<div class="ui segment" id="wp-time-capsule-tab-information">
			<div class="ui divider hidden"></div>
		  <div class="ui active inverted dimmer status">
			<div class="ui text loader"><?php _e( 'Loading...', 'mainwp-timecapsule-extension' ); ?></div>
		</div>
		</div>
		 <script type="text/javascript" language="javascript">
		  jQuery( document ).ready(function($) {
			jQuery.post( ajaxurl, {
				action: 'mainwp_wptc_get_system_info',
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			}, function(response) {
						if (response != undefined) {
							if ( response.error ) {
							  jQuery('#wp-time-capsule-tab-information .status').html( response.error );
							} else if ( response.result ) {
							  jQuery('#wp-time-capsule-tab-information').html( response.result );
							}
						}
			}, 'json');
		  } );
		</script>
		<?php
	}
	public static function gen_tab_staging_opts() {
		$site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
		self::page_settings_content_staging( $site_id );
	}

	public static function gen_tab_staging_area() {
		echo "
		 	<h1><a style='display:none' class='ui green button' id='wptc_staging_submit'>Test staging</a></h1>
    	<div id='staging_area_wptc'></div>
			<div id=\"dashboard_activity\">
				<div class=\"ui segment\">
				  <div class=\"ui active inverted dimmer\">
				    <div class=\"ui text loader\">Loading...</div>
				  </div>
				  <p></p>
				</div>
			</div>
      <div id='staging_current_progress' style='display:none'>
				<div class=\"ui segment\">
					<div class=\"ui active inverted dimmer\">
						<div class=\"ui text loader\">Checking Status...</div>
					</div>
					<p></p>
				</div>
			</div>
          <div id='wptc-content-id' style='display:none;'>
        <div class=\"ui info message\"> This is my hidden content! It will appear in ThickBox when the link is clicked.</div>
          </div>
          <a style='display:none' href='#TB_inline?width=600&height=550&inlineId=wptc-content-id' class='thickbox wptc-thickbox'>View my inline content!</a>";
	}

	public static function page_settings_content_staging( $site_id ) {
		$config = MainWP_WPTC_Factory::get( 'config' );

		$internal_staging_db_rows_copy_limit = $config->get_option( 'internal_staging_db_rows_copy_limit' );
		$internal_staging_db_rows_copy_limit = ( $internal_staging_db_rows_copy_limit ) ? $internal_staging_db_rows_copy_limit : 1000;

		$internal_staging_file_copy_limit = $config->get_option( 'internal_staging_file_copy_limit' );
		$internal_staging_file_copy_limit = ( $internal_staging_file_copy_limit ) ? $internal_staging_file_copy_limit : 500;

		$internal_staging_deep_link_limit = $config->get_option( 'internal_staging_deep_link_limit' );
		$internal_staging_deep_link_limit = ( $internal_staging_deep_link_limit ) ? $internal_staging_deep_link_limit : 5000;

		$internal_staging_enable_admin_login = $config->get_option( 'internal_staging_enable_admin_login' );
		$internal_staging_enable_admin_login = ( $internal_staging_enable_admin_login ) ? 'checked="checked"' : '';

		$reset_permalink_wptc = $config->get_option( 'staging_is_reset_permalink' );
		$reset_permalink_wptc = ( $reset_permalink_wptc ) ? 'checked="checked"' : '';

		$staging_login_custom_link = '';

		if ( isset( $side_id ) && $side_id ) {
			$staging_login_custom_link = $config->get_option( 'staging_login_custom_link' );
			$style                     = '';
		}

		$enable_admin_login = $config->get_option( 'internal_staging_enable_admin_login' );

		if ( $enable_admin_login === 'yes' ) {
			$enable_admin_login  = 'checked="checked"';
			$disable_admin_login = '';
			$login_custom_link   = '';
		} else {
			$enable_admin_login  = '';
			$disable_admin_login = 'checked="checked"';
			$login_custom_link   = "style='display:none'";
		}

		$user_excluded_extenstions_staging = $config->get_option( 'user_excluded_extenstions_staging' );

		$settings_rows = '
			<div class="ui hidden clearing divider"></div>
			<h3 class="ui dividing header">' . __( 'Common settings for Live-to-Staging & Staging-to-Live processes', 'mainwp' ) . '</h3>
		';

		if ( isset( $side_id ) && $side_id ) {
			$settings_rows .= '
			<div class="ui grid field">
				<label class="six wide column middle aligned">' . __( 'Include/Exclude Content (Files)', 'mainwp-timecapsule-extension' ) . '</label>
				<div class="ten wide column">
					<button class="ui green basic button wptc_dropdown ui " id="toggle_exlclude_files_n_folders_staging">Folders &amp; Files</button>
						<div style="display:none" id="wptc_exc_files_staging"></div>
				</div>
			</div>
			<div class="ui grid field">
				<label class="six wide column middle aligned">' . __( 'Include/Exclude Content (Database)', 'mainwp-timecapsule-extension' ) . '</label>
				<div class="ten wide column">
					<button class="ui green basic button wptc_dropdown" id="toggle_wptc_db_tables_staging">Database</button>
							<div style="display:none" id="wptc_exc_db_files_staging"></div>
				</div>
			</div>
			';
		}

		$settings_rows .= '

        <div class="ui grid field">
			<label class="six wide column middle aligned">' . __( 'Exclude Files of These Extensions', 'mainwp-timecapsule-extension' ) . '</label>
			<div class="ten wide column">
				<input name="user_excluded_extenstions_staging" type="text" placeholder="Eg. .mp4, .mov" id="user_excluded_extenstions_staging" value="' . $user_excluded_extenstions_staging . '">
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned">' . __( 'DB Rows Cloning Limit', 'mainwp-timecapsule-extension' ) . '</label>
			<div class="ten wide column">
				<input name="db_rows_clone_limit_wptc" type="text" id="db_rows_clone_limit_wptc" value="' . $internal_staging_db_rows_copy_limit . '">
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned">' . __( 'Files Cloning Limit', 'mainwp-timecapsule-extension' ) . '</label>
			<div class="ten wide column">
				<input name="files_clone_limit_wptc" type="text" id="files_clone_limit_wptc" value="' . $internal_staging_file_copy_limit . '">
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned">' . __( 'Deep Link Replacing Limit', 'mainwp-timecapsule-extension' ) . '</label>
			<div class="ten wide column">
				<input name="deep_link_replace_limit_wptc" type="text" id="deep_link_replace_limit_wptc" value="' . $internal_staging_deep_link_limit . '">
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned">' . __( 'Reset Permalink', 'mainwp-timecapsule-extension' ) . '</label>
			<div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="reset_permalink_wptc" name="reset_permalink_wptc" value="1" ' . $reset_permalink_wptc . '>
				<label></label>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned">' . __( 'Enable Admin Login', 'mainwp-timecapsule-extension' ) . '</label>
			<div class="ten wide column">
				<div class="ui radio checkbox"><input name="enable_admin_login_wptc" type="radio" ' . $enable_admin_login . ' value="yes"><label>' . __( 'Yes', 'mainwp-timecapsule-extension' ) . '</label></div>
				<div class="ui radio checkbox"><input name="enable_admin_login_wptc" type="radio" ' . $disable_admin_login . ' value="no"><label>' . __( 'No', 'mainwp-timecapsule-extension' ) . '</label></div>
			</div>
		</div>
				';

		if ( isset( $side_id ) && $side_id ) {
			$settings_rows .= '
			<div class="ui grid field" ' . $login_custom_link . '>
				<label class="six wide column middle aligned">' . __( 'Login Custom Link', 'mainwp-timecapsule-extension' ) . '</label>
				<div class="ten wide column">
					<div class="ui labeled input">
				  	<div class="ui label">http://childsite.com/</div>
						<input name="custom_admin_url" type="text" id="login_custom_link_wptc" value="' . $staging_login_custom_link . '">
					</div>
				</div>
			</div>
			';
		}

		echo $settings_rows;
		self::gen_save_settings_button( 'staging_opts' );
	}


	public static function gen_save_settings_button( $tab ) {
		?>
		<div class="ui divider"></div>
		<input type="submit" id="wptc_save_changes" name="wptc_save_changes" class="ui big green right floating button" value="<?php _e( 'Save Settings', 'mainwp-timecapsule-extension' ); ?>">
		<div style="display:none" id="message_save_settings_wptc"></div>
	<div style="display:none" id="cannot_save_settings_wptc"><?php _e( '(A backup is currently running. Please wait until it finishes to change settings.)', 'mainwp-timecapsule-extension' ); ?></div>
				<input type="hidden" id="mainwp_wptc_tab_name" name="mainwp_wptc_tab_name" value="<?php echo $tab; ?>">
		<?php
	}

	public static function gen_wptc_settings_box( $site_id ) {

		if ( empty( $site_id ) ) {
			return;
		}

		$override = false;
		$settings = MainWP_TimeCapsule_DB::get_instance()->get_data_by( 'site_id', $site_id );

		if ( $settings ) {
			$override = $settings->override ? true : false;
		}
		?>

		<div class="ui segment">
		<h3 class="ui dividing header"><?php echo __( 'WP Time Capsule Site Settings', 'mainwp-rocket-extension' ); ?></h3>
		<div class="ui form">
			<div class="ui field">
				<label><?php echo __( 'Override General Settings', 'mainwp-rocket-extension' ); ?></label>
				<div class="ui toggle checkbox">
					<input type="checkbox" value="1" name="mainwp_wptc_override_global_setting" <?php checked( $override, 1 ); ?> id="mainwp_wptc_override_global_setting">
				</div>
			</div>
			<div class="ui mini message status" style="display:none"></div>
		</div>
		</div>
		<?php
	}


	public static function gen_login_box() {
		mainwp_wordpress_time_capsule_login();
	}

	public static function gen_change_store_box() {
		mainwp_wordpress_time_capsule_change_store();
	}

	public static function gen_tab_activities() {
		mainwp_wordpress_time_capsule_activity();
	}

	public static function gen_tab_backups() {
		if ( defined( 'MAINWP_WPTC_DEFAULT_REPO' ) ) {
			$this_repo = MAINWP_WPTC_DEFAULT_REPO;
		}
		$uri = rtrim( MAINWP_WP_TIME_CAPSULE_URL, '/' );
		include MAINWP_WPTC_PLUGIN_DIR . 'source/Views/wptc-monitor.php';
	}

	public static function is_managesites_page() {
		if ( isset( $_GET['page'] ) && ( 'ManageSitesWPTimeCapsule' == $_GET['page'] ) ) {
			return true;
		}
		return false;
	}

	public static function get_current_manage_site_id() {
		if ( isset( $_POST['timecapsuleSiteID'] ) && ! empty( $_POST['timecapsuleSiteID'] ) ) {
			return $_POST['timecapsuleSiteID'];
		} elseif ( isset( $_GET['page'] ) && ( 'ManageSitesWPTimeCapsule' == $_GET['page'] ) ) {
			return isset( $_GET['id'] ) ? $_GET['id'] : 0;
		}
		return false;
	}


}
