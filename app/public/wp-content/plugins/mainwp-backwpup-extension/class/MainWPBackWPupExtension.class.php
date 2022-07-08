<?php

class MainWPBackWPupExtension {
	public $plugin_name          = 'BackWPup Extension';
	public $plugin_handle        = 'backwpup_extension';
	public $plugin_translate     = 'backwpup_extension';
	public static $nonce_token   = 'backwpup_nonce_';
	public static $option_handle = 'mainwp_updraftplus_plugin_option';
	public static $option        = - 1;
	public $synchronize          = '';
	public $plugin_slug;
	public static $instance = null;

	// Here is main array of available classes
	public static $jobs_and_destinations = array(
		// Jobs
		'job'              => array(
			'first'    => 1,
			'class'    => 'MainWPBackWPupJobGeneral',
			'type'     => '',
			'tab_name' => 'General',
		),
		'cron'             => array(
			'class'    => 'MainWPBackWPupJobSchedule',
			'type'     => '',
			'tab_name' => 'Schedule',
		),
		'jobtype-DBDUMP'   => array(
			'class'    => 'MainWPBackWPupJobDatabase',
			'type'     => 'job',
			'tab_name' => 'DB Backup',
			'name'     => 'Database backup',
		),
		'jobtype-FILE'     => array(
			'class'    => 'MainWPBackWPupJobFile',
			'type'     => 'job',
			'tab_name' => 'Files',
			'name'     => 'File backup',
		),
		'jobtype-WPEXP'    => array(
			'class'    => 'MainWPBackWPupJobXml',
			'type'     => 'job',
			'tab_name' => 'XML Export',
			'name'     => 'WordPress XML export',
		),
		'jobtype-WPPLUGIN' => array(
			'class'    => 'MainWPBackWPupJobPlugin',
			'type'     => 'job',
			'tab_name' => 'Plugins',
			'name'     => 'Installed plugins list',
		),
		'jobtype-DBCHECK'  => array(
			'class'    => 'MainWPBackWPupJobTable',
			'type'     => 'job',
			'tab_name' => 'DB check',
			'name'     => 'Check database tables',
		),
		// Destinations
		'dest-FOLDER'      => array(
			'class'    => 'MainWPBackWPupDestinationFolder',
			'type'     => 'destination',
			'tab_name' => 'To: Folder',
			'name'     => 'Backup to Folder',
		),
		'dest-EMAIL'       => array(
			'class'    => 'MainWPBackWPupDestinationEmail',
			'type'     => 'destination',
			'tab_name' => 'To: Email',
			'name'     => 'Backup sent via email',
		),
		'dest-FTP'         => array(
			'class'    => 'MainWPBackWPupDestinationFtp',
			'type'     => 'destination',
			'tab_name' => 'To: FTP',
			'name'     => 'Backup to FTP',
		),
		'dest-DROPBOX'     => array(
			'class'    => 'MainWPBackWPupDestinationDropbox',
			'type'     => 'destination',
			'tab_name' => 'To: Dropbox',
			'name'     => 'Backup to Dropbox',
		),
		'dest-S3'          => array(
			'class'    => 'MainWPBackWPupDestinationS3',
			'type'     => 'destination',
			'tab_name' => 'To: S3 Service',
			'name'     => 'Backup to an S3 Service',
		),
		'dest-MSAZURE'     => array(
			'class'    => 'MainWPBackWPupDestinationAzure',
			'type'     => 'destination',
			'tab_name' => 'To: MS Azure',
			'name'     => 'Backup to Microsoft Azure (Blob)',
		),
		'dest-RSC'         => array(
			'class'    => 'MainWPBackWPupDestinationRsc',
			'type'     => 'destination',
			'tab_name' => 'To: RSC',
			'name'     => 'Backup to Rackspace Cloud Files',
		),
		'dest-SUGARSYNC'   => array(
			'class'    => 'MainWPBackWPupDestinationSugar',
			'type'     => 'destination',
			'tab_name' => 'To: SugarSync',
			'name'     => 'Backup to SugarSync',
		),
		'dest-GLACIER'     => array(
			'class'    => 'MainWPBackWPupDestinationGlacier',
			'type'     => 'destination',
			'tab_name' => 'To: Glacier',
			'name'     => 'Backup to Amazon Glacier',
			'is_pro'   => true,
		),
		'dest-GDRIVE'      => array(
			'class'    => 'MainWPBackWPupDestinationGdrive',
			'type'     => 'destination',
			'tab_name' => 'To: GDrive',
			'name'     => 'Backup to Google Drive',
			'is_pro'   => true,
		),
	);

	// We can display messages for user
	public static $messages       = array();
	public static $error_messages = array();

	public static function get_option( $key = null, $default = '' ) {
		if ( self::$option === - 1 ) {
			self::$option = get_option( self::$option_handle );
		}

		if ( isset( self::$option[ $key ] ) ) {
			return self::$option[ $key ];
		}

		return $default;
	}

	public static function set_option( $key, $value ) {
		if ( self::$option === - 1 ) {
			self::$option = get_option( self::$option_handle );
		}

		self::$option[ $key ] = $value;

		return update_option( self::$option_handle, self::$option );
	}

	public static function add_error_message( $message ) {
		self::$error_messages[] = wp_strip_all_tags( $message );
	}

	public static function add_message( $message ) {
		self::$messages[] = wp_strip_all_tags( $message );
	}

	// Sometimes we don't want to render whole page but we want to display error message
	public static function end_with_message_gracefully( $message ) {
		self::add_error_message( $message );
		MainWPBackWPUpView::display_messages();
	}

	public function __construct() {
		global $pagenow;
		$this->plugin_slug = plugin_basename( __FILE__ );

		if ( $pagenow == 'admin.php' && isset( $_REQUEST['page'] ) &&
			 (
				 strcasecmp( $_REQUEST['page'], 'Extensions-Mainwp-Backwpup-Extension' ) === 0 ||
				 strcasecmp( $_REQUEST['page'], 'ManageSitesBackwpup' ) === 0
			 )
		) {

			$this->ajax_check_permissions( 'admin_page', false );

			add_action( 'init', array( $this, 'init' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );

		add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
		add_filter( 'mainwp_sync_extensions_options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );
		add_action(
			'mainwp_applypluginsettings_mainwp-backwpup-extension',
			array(
				$this,
				'mainwp_apply_plugin_settings',
			)
		);
				$add_managesites_column = false;
		$primary_backup                 = get_option( 'mainwp_primaryBackup', null );

		if ( $primary_backup == 'backwpup' ) {
			add_filter( 'mainwp_managesites_getbackuplink', array( $this, 'managesites_backup_link' ), 10, 2 );
			add_filter( 'mainwp_getcustompage_backups', array( $this, 'add_page_backups' ), 10, 1 );
			add_filter( 'mainwp_getprimarybackup_activated', array( $this, 'primary_backups_activated' ), 10, 1 );
						$add_managesites_column = true;
		} elseif ( empty( $primary_backup ) ) {
					$add_managesites_column = true;
		}

		if ( $add_managesites_column ) {
			add_filter( 'mainwp_managesites_column_url', array( &$this, 'managesites_column_url' ), 10, 2 );
		}

		add_filter( 'mainwp_getprimarybackup_methods', array( $this, 'primary_backups_method' ), 10, 1 );

		add_action( 'wp_ajax_mainwp_backwpup_contact_with_child', array( $this, 'ajax_contact_with_child' ) );
		add_action( 'wp_ajax_mainwp_backwpup_contact_with_root', array( $this, 'ajax_contact_with_root' ) );
		add_action( 'wp_ajax_mainwp_backwpup_open_child_site', array( $this, 'ajax_open_child_site' ) );
		add_action( 'wp_ajax_mainwp_backwpup_synchronize_global_job', array( $this, 'ajax_synchronize_global_job' ) );
		add_action(
			'wp_ajax_mainwp_backwpup_synchronize_global_job_step_2',
			array(
				$this,
				'ajax_synchronize_global_job_step_2',
			)
		);
		add_action(
			'wp_ajax_mainwp_backwpup_synchronize_global_settings',
			array(
				$this,
				'ajax_synchronize_global_settings',
			)
		);
		add_action(
			'wp_ajax_mainwp_backwpup_synchronize_global_settings_step_2',
			array(
				$this,
				'ajax_synchronize_global_settings_step_2',
			)
		);
		add_action( 'wp_ajax_mainwp_backwpup_get_buckets', array( $this, 'ajax_get_buckets' ) );
		add_action( 'wp_ajax_mainwp_backwpup_dest_gdrive', array( $this, 'ajax_dest_gdrive' ) );
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWPBackWPupExtension();
		}
		return self::$instance;
	}

	/**
	 * Add option to Select Primary Backup System inside MainWP Settings
	 **/
	public function primary_backups_method( $methods ) {
		$methods[] = array(
			'value' => 'backwpup',
			'title' => 'MainWP BackWPup Extension',
		);

		return $methods;
	}

	/**
	 * Display "Backup Now" inside admin.php?page=managesites
	 **/
	public function managesites_backup_link( $input, $site_id ) {

		if ( $site_id ) {
			if ( mainwp_current_user_can( 'dashboard', 'execute_backups' ) ) {
				return sprintf( '<a href="' . admin_url( 'admin.php?page=ManageSitesBackwpup&id=%d&tab=jobs' ) . '">' . __( 'Backup Now', 'mainwp' ) . '</a>', intval( $site_id ) );
			}
		}

		return;
	}

	/**
	 * Add "Existing backups" link inside mainwp submenu
	 **/
	public function add_page_backups( $input = null ) {
		return array(
			'title'            => __( 'Existing Backups', 'mainwp' ),
			'slug'             => 'backwpup',
			'managesites_slug' => 'Backwpup',
			'callback'         => array( $this, 'render_redicting' ),
		);
	}

	/**
	 * Render redirect to admin.php?page=ManageSitesBackwpup
	 **/
	public function render_redicting() {
		?>
		<div id="mainwp_background-box">
			<div
				style="font-size: 30px; text-align: center; margin-top: 5em;"><?php _e( 'You will be redirected to the page immediately.', 'mainwp' ); ?></div>
			<script type="text/javascript">
				window.location = '<?php echo admin_url( 'admin.php?page=ManageSitesBackwpup&tab=backups' ); ?>';
			</script>
		</div>
		<?php
	}

	/**
	 * Set global $mainwpUseExternalPrimaryBackupsMethod value
	 **/
	public function primary_backups_activated( $input ) {
		return 'backwpup';
	}

	/**
	 * Settings Google Drive token
	 * Explicitly disable nonce token here
	 * Need to have valid transient set from view page
	 **/
	public function ajax_dest_gdrive() {
		$this->ajax_check_permissions( 'dest_gdrive', false );

		$job_id     = get_option( 'mainwp_gdr_job_' . get_current_user_id() );
		$website_id = get_option( 'mainwp_gdr_website_' . get_current_user_id() );

		if ( empty( $job_id ) ) {
			wp_die( __( 'Missing transient job_id', $this->plugin_translate ) );
		}

		if ( $website_id > 0 ) {
			if ( $this->is_website_exist( $website_id, false ) === false ) {
				wp_die( __( 'Website does not exist', $this->plugin_translate ) );
			}
		}

		$settings_by_website_id = MainWPBackWPupDB::Instance()->get_settings_by_website_id( $website_id );

		if ( isset( $settings_by_website_id['settings'] ) ) {
			$settings = json_decode( $settings_by_website_id['settings'], true );
		} else {
			wp_die( __( 'Please save settings first', $this->plugin_translate ) );
		}

		if ( empty( $settings['googleclientid'] ) || strlen( $settings['googleclientid'] ) < 2 ) {
			wp_die( __( 'Empty googleclientid', $this->plugin_translate ) );
		}

		if ( empty( $settings['googleclientsecret'] ) || strlen( $settings['googleclientsecret'] ) < 2 ) {
			wp_die( __( 'Empty googleclientsecret', $this->plugin_translate ) );
		}

		$job = MainWPBackWPupDB::Instance()->get_job_by_id( $job_id );

		if ( ! isset( $job['id'] ) ) {
			wp_die( __( 'Please save job first', $this->plugin_translate ) );
		}

		if ( $job['website_id'] != $website_id ) {
			wp_die( __( 'website_id mismatch', $this->plugin_translate ) );
		}

		$job_settings = json_decode( $job['settings'], true );

		spl_autoload_register( array( $this, 'vendor_autoloader' ) );

		$client = new Google_Client();
		$client->getIo()->setOptions( array( CURLOPT_SSL_VERIFYPEER => false ) );
		$client->setApplicationName( 'BackWPup' );
		$client->setClientId( $settings['googleclientid'] );
		$client->setClientSecret( $settings['googleclientsecret'] );
		$client->setScopes( array( 'https://www.googleapis.com/auth/drive' ) );
		$client->setRedirectUri( admin_url( 'admin-ajax.php' ) . '?action=mainwp_backwpup_dest_gdrive' );
		$client->setApprovalPrompt( 'force' );
		$client->setAccessType( 'offline' );

		if ( isset( $_GET['code'] ) ) {
			if ( ! isset( $job_settings['dest-GDRIVE'] ) ) {
				$job_settings['dest-GDRIVE'] = array();
			}
			// We got response from Google
			try {
				$client->authenticate( $_GET['code'] );
				$access_token = $client->getAccessToken();
				$access_token = json_decode( $access_token );

				if ( is_null( $access_token ) ) {
					wp_die( __( 'Cannot decode code from response', $this->plugin_translate ) );
				}

				if ( ! empty( $access_token->refresh_token ) ) {
					$job_settings['dest-GDRIVE']['gdriverefreshtoken'] = $access_token->refresh_token;
					update_option( 'mainwp_gdr_job_' . get_current_user_id(), 0 );
				} else {
					wp_die( __( 'Error: missing refresh token', $this->plugin_translate ) );
					$job_settings['dest-GDRIVE']['gdriverefreshtoken'] = '';
				}
			} catch ( Exception $e ) {
				wp_die( __( 'Error: ' . $e->getMessage(), $this->plugin_translate ) );
				$job_settings['dest-GDRIVE']['gdriverefreshtoken'] = '';
			}

			if ( MainWPBackWPupDB::Instance()->insert_or_update_job_by_id( $job_id, $website_id, $job['job_id'], wp_json_encode( $job_settings ) ) === false ) {
				wp_die( __( 'Cannot insert new settings into database', $this->plugin_translate ) );
			}

			if ( $website_id == 0 ) {
				wp_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Backwpup-Extension&id=' . $website_id . '&our_job_id=' . $job_id ), 302 );
			} else {
				wp_redirect( admin_url( 'admin.php?page=ManageSitesBackwpup&id=' . $website_id . '&our_job_id=' . $job_id ), 302 );
			}
		} else {
			$client->setRedirectUri( admin_url( 'admin-ajax.php' ) . '?action=mainwp_backwpup_dest_gdrive' );
			$auth_url = $client->createAuthUrl();
			wp_redirect( $auth_url, 302 );
		}
		wp_die();
	}

	/**
	 * If Team Control is installed - check extension permission
	 * Else use manage_options
	 * Also verivy nonce
	 **/
	protected function ajax_check_permissions( $action, $check_nonce = true ) {
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-backwpup-extension' ) ) {
			$this->json_error( mainwp_do_not_have_permissions( 'MainWP BackWPup Extension ' . $action, false ) );
		}
		if ( $check_nonce ) {
			if ( ! isset( $_POST['wp_nonce'] ) || ! wp_verify_nonce( $_POST['wp_nonce'], self::$nonce_token . $action ) ) {
					$this->json_error( __( 'Error: Wrong or expired request. Please reload page', $this->plugin_translate ) );
			}
		}
	}

	/**
	 * Used for sending error messages through json
	 * We use wp_send_json because it sets header to Content-Type: application/json
	 **/
	public function json_error( $error ) {
		wp_send_json( array( 'error' => esc_html( $error ) ) );
	}

	/**
	 * Used for sending OK messages through json
	 * We use wp_send_json because it sets header to Content-Type: application/json
	 **/
	public function json_ok( $message = null, $data = null ) {
		if ( is_null( $data ) ) {
			if ( is_null( $message ) ) {
				wp_send_json( array( 'success' => 1 ) );
			} else {
				wp_send_json( array( 'success' => esc_html( $message ) ) );
			}
		} else {
			if ( is_null( $message ) ) {
				wp_send_json(
					array(
						'success' => 1,
						'data'    => $data,
					)
				);
			} else {
				wp_send_json(
					array(
						'success' => esc_html( $message ),
						'data'    => $data,
					)
				);
			}
		}

		die();
	}

	/**
	 * Autoloader for all external classes for ./vendor dir
	 **/
	protected function vendor_autoloader( $class ) {
		$autoload = array(
			'Aws\Common'                          => plugin_dir_path( __FILE__ ) . '../vendor/',
			'Aws\S3'                              => plugin_dir_path( __FILE__ ) . '../vendor/',
			'WindowsAzure'                        => plugin_dir_path( __FILE__ ) . '../vendor/',
			'OpenCloud'                           => plugin_dir_path( __FILE__ ) . '../vendor/',
			'Guzzle'                              => plugin_dir_path( __FILE__ ) . '../vendor/',
			'Symfony\\Component\\EventDispatcher' => plugin_dir_path( __FILE__ ) . '../vendor/',
		);

		$classPath = explode( '_', $class );
		if ( $classPath[0] == 'Google' ) {
			if ( count( $classPath ) > 3 ) {
				$classPath = array_slice( $classPath, 0, 3 );
			}
			$filePath = plugin_dir_path( __FILE__ ) . '../vendor/' . implode( '/', $classPath ) . '.php';
			if ( file_exists( $filePath ) ) {
				require $filePath;
			}

			return;
		}

		$pos = strrpos( $class, '\\' );
		if ( $pos !== false ) {
			$class_path = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, 0, $pos ) ) . DIRECTORY_SEPARATOR . str_replace( '_', DIRECTORY_SEPARATOR, substr( $class, $pos + 1 ) ) . '.php';
			foreach ( $autoload as $prefix => $dir ) {
				if ( $class === strstr( $class, $prefix ) ) {
					if ( file_exists( $dir . DIRECTORY_SEPARATOR . $class_path ) ) {
						require $dir . DIRECTORY_SEPARATOR . $class_path;
					}
				}
			}
		}
	}

	/**
	 * Retrive buckets/folders for destination
	 * Based on username/password/access keys
	 **/
	public function ajax_get_buckets() {
		$this->ajax_check_permissions( 'get_buckets' );

		$type = ( isset( $_POST['type'] ) ? $_POST['type'] : '' );

		$error           = null;
		$bucket_array    = array();
		$additional_info = array();

		spl_autoload_register( array( $this, 'vendor_autoloader' ) );

		include_once MAINWP_BACKWPUP_PLUGIN_DIR . '/inc/class-s3-destination.php';

		switch ( $type ) {

			case 's3':
				function mainwp_backwpup_get_s3_base_url( $s3region, $s3base_url = '' ) {

					if ( ! empty( $s3base_url ) ) {
						return $s3base_url;
					}

					switch ( $s3region ) {
						case 'us-east-1':
							return 'https://s3.amazonaws.com';
						case 'us-west-1':
							return 'https://s3-us-west-1.amazonaws.com';
						case 'us-west-2':
							return 'https://s3-us-west-2.amazonaws.com';
						case 'eu-west-1':
							return 'https://s3-eu-west-1.amazonaws.com';
						case 'eu-central-1':
							return 'https://s3-eu-central-1.amazonaws.com';
						case 'ap-northeast-1':
							return 'https://s3-ap-northeast-1.amazonaws.com';
						case 'ap-southeast-1':
							return 'https://s3-ap-southeast-1.amazonaws.com';
						case 'ap-southeast-2':
							return 'https://s3-ap-southeast-2.amazonaws.com';
						case 'sa-east-1':
							return 'https://s3-sa-east-1.amazonaws.com';
						case 'cn-north-1':
							return 'https:/cn-north-1.amazonaws.com';
						case 'google-storage':
							return 'https://storage.googleapis.com';
						case 'dreamhost':
							return 'https://objects.dreamhost.com';
						case 'greenqloud':
							return 'http://s.greenqloud.com';
						default:
							return '';
					}
				}

				$s3accesskey      = ( isset( $_POST['s3accesskey'] ) ? $_POST['s3accesskey'] : '' );
				$s3secretkey      = ( isset( $_POST['s3secretkey'] ) ? $_POST['s3secretkey'] : '' );
				$s3bucketselected = ( isset( $_POST['s3bucketselected'] ) ? $_POST['s3bucketselected'] : '' );
				$s3base_url       = ( isset( $_POST['s3base_url'] ) ? $_POST['s3base_url'] : '' );
				$s3region         = ( isset( $_POST['s3region'] ) ? $_POST['s3region'] : '' );

				if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
					require_once __DIR__ . '/../vendor/autoload.php';
				}

				if ( ! empty( $s3accesskey ) && ! empty( $s3secretkey ) ) {

					if ( empty( $s3base_url ) ) {
						$aws_destination = MainWP_BackWPup_S3_Destination::fromOption( $s3region );

					} else {
						$options         = array(
							'label'                  => __( 'Custom S3 destination', 'backwpup' ),
							'endpoint'               => $s3base_url,
							'region'                 => $s3region,
							'multipart'              => ! empty( $_POST['s3base_multipart'] ) ? true : false,
							'only_path_style_bucket' => ! empty( $_POST['s3base_pathstylebucket'] ) ? true : false,
							'version'                => $_POST['s3base_version'],
							'signature'              => $_POST['s3base_signature'],
						);
						$aws_destination = MainWP_BackWPup_S3_Destination::fromOptionArray( $options );

					}

					try {
						$s3      = $aws_destination->client(
							$s3accesskey,
							$s3secretkey
						);
						$buckets = $s3->listBuckets();
					} catch ( Exception $e ) {
						$error = $e->getMessage();
					}
				}

				if ( empty( $s3accesskey ) ) {
					$this->json_error( __( 'Missing access key', $this->plugin_translate ) );
				} elseif ( empty( $s3secretkey ) ) {
					$this->json_error( __( 'Missing secret access key', $this->plugin_translate ) );
				} elseif ( ! empty( $error ) ) {
					$this->json_error( esc_html( $error ) );
				} elseif ( ! isset( $buckets ) || count( $buckets['Buckets'] ) < 1 ) {
					$this->json_error( __( 'No bucket found', $this->plugin_translate ) );
				}

				if ( ! empty( $buckets['Buckets'] ) ) {
					foreach ( $buckets['Buckets'] as $bucket ) {
						$bucket_array[] = esc_attr( $bucket['Name'] );
					}
				}
				break;

			case 'azure':
				set_include_path( get_include_path() . PATH_SEPARATOR . plugin_dir_path( __FILE__ ) . '../vendor/PEAR/' );
				$msazureaccname = ( isset( $_POST['msazureaccname'] ) ? $_POST['msazureaccname'] : '' );
				$msazurekey     = ( isset( $_POST['msazurekey'] ) ? $_POST['msazurekey'] : '' );

				if ( ! empty( $msazureaccname ) && ! empty( $msazurekey ) ) {
					try {
						$blobRestProxy = WindowsAzure\Common\ServicesBuilder::getInstance()->createBlobService( 'DefaultEndpointsProtocol=https;AccountName=' . $msazureaccname . ';AccountKey=' . $msazurekey );
						$containers    = $blobRestProxy->listContainers()->getContainers();
					} catch ( Exception $e ) {
						$error = $e->getMessage();
					}
				}

				if ( empty( $msazureaccname ) ) {
					$this->json_error( __( 'Missing account name', $this->plugin_translate ) );
				} elseif ( empty( $msazurekey ) ) {
					$this->json_error( __( 'Missing access key', $this->plugin_translate ) );
				} elseif ( ! empty( $error ) ) {
					$this->json_error( esc_html( $error ) );
				} elseif ( empty( $containers ) ) {
					$this->json_error( __( 'No container found', $this->plugin_translate ) );
				}

				if ( ! empty( $containers ) ) {
					foreach ( $containers as $container ) {
						$bucket_array[] = esc_attr( $container->getName() );
					}
				}

				break;

			case 'rsc':
				function mainwp_backwpup_get_auth_url_by_region( $region ) {

					$region = strtoupper( $region );

					if ( $region == 'LON' ) {
						return 'https://lon.identity.api.rackspacecloud.com/v2.0/';
					}

					return 'https://identity.api.rackspacecloud.com/v2.0/';
				}

				$rscusername = ( isset( $_POST['rscusername'] ) ? $_POST['rscusername'] : '' );
				$rscapikey   = ( isset( $_POST['rscapikey'] ) ? $_POST['rscapikey'] : '' );
				$rscregion   = ( isset( $_POST['rscregion'] ) ? $_POST['rscregion'] : '' );

				$container_list = array();
				if ( ! empty( $rscusername ) && ! empty( $rscapikey ) && ! empty( $rscregion ) ) {
					try {
						$conn = new OpenCloud\Rackspace(
							mainwp_backwpup_get_auth_url_by_region( $rscregion ),
							array(
								'username' => $rscusername,
								'apiKey'   => $rscapikey,
							)
						);

						$ostore        = $conn->objectStoreService( 'cloudFiles', $rscregion, 'publicURL' );
						$containerlist = $ostore->listContainers();
						while ( $container = $containerlist->next() ) {
							$container_list[] = $container->name;
						}
					} catch ( Exception $e ) {
						$error = $e->getMessage();
					}
				}

				if ( empty( $rscusername ) ) {
					$this->json_error( __( 'Missing username', $this->plugin_translate ) );
				} elseif ( empty( $rscapikey ) ) {
					$this->json_error( __( 'Missing API Key', $this->plugin_translate ) );
				} elseif ( ! empty( $error ) ) {
					$this->json_error( esc_html( $error ) );
				} elseif ( empty( $container_list ) ) {
					$this->json_error( __( 'A container could not be found', $this->plugin_translate ) );
				}

				if ( ! empty( $container_list ) ) {
					foreach ( $container_list as $container_name ) {
						$bucket_array[] = esc_attr( $container_name );
					}
				}

				break;

			case 'glacier':
				$glacieraccesskey = ( isset( $_POST['glacieraccesskey'] ) ? $_POST['glacieraccesskey'] : '' );
				$glaciersecretkey = ( isset( $_POST['glaciersecretkey'] ) ? $_POST['glaciersecretkey'] : '' );
				$glacierregion    = ( isset( $_POST['glacierregion'] ) ? $_POST['glacierregion'] : '' );

				if ( ! empty( $glacieraccesskey ) && ! empty( $glaciersecretkey ) ) {
					if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
						require_once __DIR__ . '/../vendor/autoload.php';
					}

					try {
						$glacier = Aws\Glacier\GlacierClient::factory(
							array(
								'key'    => $glacieraccesskey,
								'secret' => $glaciersecretkey,
								'region' => $glacierregion,
								'scheme' => 'https',
							)
						);

						$vaults = $glacier->listVaults();
					} catch ( Exception $e ) {
						$error = $e->getMessage();
					}
				}

				if ( empty( $glacieraccesskey ) ) {
					$this->json_error( __( 'Missing access key', $this->plugin_translate ) );
				} elseif ( empty( $glaciersecretkey ) ) {
					$this->json_error( __( 'Missing secret access key', $this->plugin_translate ) );
				} elseif ( ! empty( $error ) ) {
					$this->json_error( esc_html( $error ) );
				} elseif ( ! isset( $vaults ) || count( $vaults['VaultList'] ) < 1 ) {
					$this->json_error( __( 'No vault found', $this->plugin_translate ) );
				}

				if ( ! empty( $vaults['VaultList'] ) ) {
					foreach ( $vaults['VaultList'] as $vault ) {
						$bucket_array[] = esc_attr( $vault['VaultName'] );
					}
				}

				break;

			case 'sugar':
				require_once plugin_dir_path( __FILE__ ) . '../vendor/SygarSyncApi.php';
				$sugaremail = ( isset( $_POST['sugaremail'] ) ? $_POST['sugaremail'] : '' );
				$sugarpass  = ( isset( $_POST['sugarpass'] ) ? $_POST['sugarpass'] : '' );

				if ( empty( $sugaremail ) || empty( $sugarpass ) ) {
					$this->json_error( __( 'Missign password or email', $this->plugin_translate ) );
				}

				$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );

				$this->is_website_exist( $website_id );

				try {
					$sugarsync                = new BackWPup_Destination_SugarSync_API( $website_id );
					$refresh_token            = $sugarsync->get_Refresh_Token( $sugaremail, $sugarpass );
					$additional_info['token'] = $refresh_token;
					if ( ! empty( $refresh_token ) ) {
						$user        = $sugarsync->user();
						$syncfolders = $sugarsync->get( $user->syncfolders );
						if ( ! is_object( $syncfolders ) ) {
							$this->json_error( __( 'No Syncfolders found', $this->plugin_translate ) );
						} else {
							foreach ( $syncfolders->collection as $roots ) {
								$bucket_array[] = esc_attr( $roots->ref );
							}
						}
					} else {
						$this->json_error( __( 'Missign Sugar token', $this->plugin_translate ) );
					}
				} catch ( Exception $e ) {
					$this->json_error( esc_html( $e->getMessage() ) );
				}

				break;

			default:
				$this->json_error( __( 'Missing or type', $this->plugin_translate ) );
		}

		$this->json_ok( null, array_merge( $additional_info, array( 'data' => $bucket_array ) ) );
	}

	/**
	 * Check if given website exists in database
	 **/
	protected function is_website_exist( $website_id, $return_json = true ) {
		global $mainWPBackWPupExtensionActivator;
		$website_id = intval( $website_id );

		$website = apply_filters( 'mainwp_getsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id );
		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( empty( $website ) ) {
			if ( $return_json ) {
				$this->json_error( __( 'Website does not exist', $this->plugin_translate ) );
			} else {
				return false;
			}
		}

		if ( ! $return_json ) {
			return true;
		}
	}

	/**
	 * Synchronize settings globally - step 1
	 * Return list of available websites
	 **/
	public function ajax_synchronize_global_settings() {
		$this->ajax_check_permissions( 'synchronize_global_settings' );

		$this->json_ok( null, $this->get_list_of_overrides() );
	}

	/**
	 * Synchronize settings globally - step 2
	 * Get global settings and check if exists
	 * Check if selected website exists
	 * Update settings in child
	 **/
	public function ajax_synchronize_global_settings_step_2() {
		$this->ajax_check_permissions( 'synchronize_global_settings_step_2' );

		$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );

		$this->is_website_exist( $website_id );

		$global_settings = MainWPBackWPupDB::Instance()->get_settings_by_website_id( 0 );

		if ( ! isset( $global_settings['id'] ) ) {
			$this->json_error( __( 'Please save global settings before first synchronization', $this->plugin_translate ) );
		}

		global $mainWPBackWPupExtensionActivator;

		$settings = $this->replace_tokens( json_decode( $global_settings['settings'], true ), $website_id, true );

		$post_data = array(
			'action'   => 'backwpup_update_settings',
			'settings' => array( 'value' => $settings ),
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

		$this->check_child_response( $information, __( 'Cannot update settings in child', $this->plugin_translate ) );

		$this->json_ok();
	}

	public static function sanitize_file_name( $filename ) {
		$filename = str_replace( array( '|', '/', '\\', ' ', ':' ), array( '-', '-', '-', '-', '-' ), $filename );
		return sanitize_text_field( $filename );
	}

	/**
	 * Add support for %sitename%, %url%, %time%, %date% tokens
	 **/
	protected function replace_tokens( $datas, $website_id, $is_settings = false ) {
		// $website = MainWP_DB::Instance()->getWebsiteById( $website_id );

		global $mainWPBackWPupExtensionActivator;
		$website    = null;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), array( $website_id ), array() );
		if ( $dbwebsites && is_array( $dbwebsites ) ) {
			$website = current( $dbwebsites );
		}

		$sitename = self::sanitize_file_name( $website->name );
		$url      = self::sanitize_file_name( self::get_nice_url( $website->url ) );
		$date     = self::sanitize_file_name( self::date( get_option( 'date_format' ) ) );
		$time     = self::sanitize_file_name( self::date( get_option( 'time_format' ) ) );

		$search  = array( '%sitename%', '%url%', '%date%', '%time%' );
		$replace = array( $sitename, $url, $date, $time );

		$out = array();

		foreach ( $datas as $key => $val ) {
			if ( $is_settings ) {
				$out[ $key ] = str_replace( $search, $replace, $val );
			} else {
				$temp = array();
				foreach ( $val as $input_name => $input_value ) {
					if ( ! is_array( $input_value ) ) {
						$temp[ $input_name ] = str_replace( $search, $replace, $input_value );
					} else {
						$temp[ $input_name ] = $input_value;
					}
				}
				$out[ $key ] = $temp;
			}
		}

		return $out;
	}

	/**
	 * Method get_timestamp()
	 *
	 * Get time stamp in gmt_offset.
	 *
	 * @param mixed $timestamp Time stamp to convert.
	 *
	 * @return string Time stamp in general mountain time offset.
	 */
	public static function get_timestamp( $timestamp ) {
		$gmtOffset = get_option( 'gmt_offset' );

		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}

	/**
	 * Method date()
	 *
	 * Show date in given format.
	 *
	 * @param mixed $format Format to display date in.
	 *
	 * @return string Date.
	 */
	public static function date( $format ) {
		// phpcs:ignore -- use local date function.
		return date( $format, self::get_timestamp( time() ) );
	}


	/**
	 * Method get_nice_url()
	 *
	 * Grab url.
	 *
	 * @param string  $pUrl Website URL.
	 * @param boolean $showHttp Show HTTP.
	 *
	 * @return string $url.
	 */
	public static function get_nice_url( $pUrl, $showHttp = false ) {
		$url = $pUrl;

		if ( self::starts_with( $url, 'http://' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 7 );
			}
		} elseif ( self::starts_with( $pUrl, 'https://' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 8 );
			}
		} else {
			if ( $showHttp ) {
				$url = 'http://' . $url;
			}
		}

		if ( self::ends_with( $url, '/' ) ) {
			if ( ! $showHttp ) {
				$url = substr( $url, 0, strlen( $url ) - 1 );
			}
		} else {
			$url = $url . '/';
		}

		return $url;
	}

	/**
	 * Method starts_with()
	 *
	 * Start of Stack Trace.
	 *
	 * @param mixed $haystack The full stack.
	 * @param mixed $needle The function that is throwing the error.
	 *
	 * @return mixed Needle in the Haystack.
	 */
	public static function starts_with( $haystack, $needle ) {
		return ! strncmp( $haystack, $needle, strlen( $needle ) );
	}

	/**
	 * Method ends_with()
	 *
	 * End of Stack Trace.
	 *
	 * @param mixed $haystack Haystack parameter.
	 * @param mixed $needle Needle parameter.
	 *
	 * @return boolean
	 */
	public static function ends_with( $haystack, $needle ) {
		$length = strlen( $needle );
		if ( 0 === $length ) {
			return true;
		}

		return ( substr( $haystack, - $length ) === $needle );
	}


	/**
	 * Return list of all websites that heve backwp plugin installed
	 * Used in global synchronization
	 **/
	public function get_list_of_overrides( $website_ids = false ) {
		global $mainWPBackWPupExtensionActivator;

		$websites = apply_filters( 'mainwp_getsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), null );

		$sites_ids   = array();
		$return_urls = array();
		$return_ids  = array();

		if ( is_array( $websites ) ) {
			foreach ( $websites as $site ) {
				$sites_ids[] = $site['id'];
			}

			$selected_ids = array();
			if ( is_array( $website_ids ) ) {
				foreach ( $sites_ids as $_siteid ) {
					if ( in_array( $_siteid, $website_ids ) ) {
						$selected_ids[] = $_siteid;
					}
				}
			} else {
				$selected_ids = $sites_ids;
			}

			unset( $websites );

			$option     = array(
				'plugin_upgrades' => true,
				'plugins'         => true,
			);
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $selected_ids, array(), $option );

			if ( is_array( $dbwebsites ) ) {
				$overrides      = MainWPBackWPupDB::Instance()->get_website_id_by_override( 1 );
				$override_array = array();

				if ( is_array( $overrides ) ) {
					foreach ( $overrides as $override ) {
						$override_array[ $override['website_id'] ] = 1;
					}
				}

				foreach ( $dbwebsites as $website ) {
					// Override set to no
					if ( ! isset( $override_array[ $website->id ] ) ) {
						if ( $website->plugins != '' ) {
							$plugins = json_decode( $website->plugins, 1 );
							if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
								foreach ( $plugins as $plugin ) {
									// Has backwpup plugin installed
									if ( ( strcmp( $plugin['slug'], 'backwpup/backwpup.php' ) === 0 || strcmp( $plugin['slug'], 'backwpup-pro/backwpup.php' ) === 0 ) ) {
										if ( $plugin['active'] ) {
											$return_ids[ $website->id ]  = $website->id;
											$return_urls[ $website->id ] = esc_html( $website->url );
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return array(
			'ids'  => array_values( $return_ids ),
			'urls' => array_values( $return_urls ),
		);
	}

	/**
	 * Synchronize jobs globally - step 1
	 * Check if job is global
	 * Return list of available websites
	 **/
	public function ajax_synchronize_global_job() {
		$this->ajax_check_permissions( 'synchronize_global_job' );

		$job_id = ( isset( $_POST['job_id'] ) ? intval( $_POST['job_id'] ) : 0 );

		if ( $job_id == 0 ) {
			$this->json_error( __( 'Missing job id', $this->plugin_translate ) );
		}

		$saved_job = MainWPBackWPupDB::Instance()->get_job_by_id( $job_id );

		if ( ! isset( $saved_job['id'] ) ) {
			$this->json_error( __( 'Given job does not exist', $this->plugin_translate ) );
		}

		if ( $saved_job['website_id'] > 0 ) {
			$this->json_error( __( 'Job is not global', $this->plugin_translate ) );
		}
		$selected_siteids = get_option( 'mainwp_backwpup_synchronize_site_ids' );
		delete_option( 'mainwp_backwpup_synchronize_site_ids' );

		$this->json_ok( null, $this->get_list_of_overrides( $selected_siteids ) );
	}

	/**
	 * Synchronize jobs globally - step 2
	 **/
	public function ajax_synchronize_global_job_step_2() {
		$this->ajax_check_permissions( 'synchronize_global_job_step_2' );

		$job_id = ( isset( $_POST['job_id'] ) ? intval( $_POST['job_id'] ) : 0 );

		if ( $job_id == 0 ) {
			$this->json_error( __( 'Missing job id', $this->plugin_translate ) );
		}

		$saved_job = MainWPBackWPupDB::Instance()->get_job_by_id( $job_id );

		if ( ! isset( $saved_job['id'] ) ) {
			$this->json_error( __( 'Given job does not exist', $this->plugin_translate ) );
		}

		if ( $saved_job['website_id'] > 0 ) {
			$this->json_error( __( 'Job is not global', $this->plugin_translate ) );
		}

		$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );

		$this->is_website_exist( $website_id );

		// If we have child job_id for this global_id and website_id ?
		$global_job = MainWPBackWPupDB::Instance()->get_global_job_by_global_id_and_website_id( $job_id, $website_id );

		if ( isset( $global_job['job_id'] ) ) {
			$child_job_id = $global_job['job_id'];
		} else {
			// It's new job
			$child_job_id = 0;
		}

		global $mainWPBackWPupExtensionActivator;

		$job_settings = json_decode( $saved_job['settings'], true );

		$post_data = array(
			'action'   => 'backwpup_insert_or_update_jobs_global',
			'settings' => array(
				'job_id' => $child_job_id,
				'value'  => $this->replace_tokens( $job_settings, $website_id ),
			),
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

		$this->check_child_response( $information, __( 'Cannot update global job data in child website', $this->plugin_translate ) );

		if ( ! isset( $information['job_id'] ) ) {
			$this->json_error( __( 'Missing job_id information from child', $this->plugin_translate ) );
		}

		if ( $child_job_id == 0 ) {
			if ( MainWPBackWPupDB::Instance()->insert_global_jobs( $job_id, $information['job_id'], $website_id ) === false ) {
				$this->json_error( __( 'Cannot insert job_id into global_jobs', $this->plugin_translate ) );
			}
		}

		$settings = $this->remove_create_bucket_from_settings( $job_settings );

		$serialized_settings = wp_json_encode( $settings );

		if ( MainWPBackWPupDB::Instance()->insert_or_update_job_by_id( $saved_job['id'], $saved_job['website_id'], $saved_job['job_id'], $serialized_settings ) === false ) {
			wp_die( __( 'Cannot locally update job', $this->plugin_translate ) );
		}

		$info_messages = array();

		if ( ! empty( $information['message'] ) ) {
			foreach ( $information['message'] as $message ) {
				$info_message_search  = array( 'Jobs overview | Run now' );
				$info_message_replace = array( '' );
				$info_messages[]      = str_replace( $info_message_search, $info_message_replace, wp_strip_all_tags( $message ) );
			}
		}

		if ( ! empty( $information['changes'] ) ) {
			foreach ( $information['changes'] as $key => $change ) {
				$info_messages[] = wp_strip_all_tags( __( 'Difference in key', $this->plugin_translate ) . " '" . esc_html( $key ) . "' " . __( 'child return', $this->plugin_translate ) . " : '" . esc_html( $change ) . "'" );
			}
		}

		$this->json_ok( null, array( 'info_messages' => $info_messages ) );
	}

	/**
	 * For few destinations it's possible to create new bucket
	 * This option should be send to child website only once in order to prevent creating buckets twice
	 **/
	protected function remove_create_bucket_from_settings( $settings ) {
		$is_new_bucket = false;

		// We want to create bucket only once
		$create_bucket_array = array(
			'dest-S3'      => 's3newbucket',
			'dest-MSAZURE' => 'newmsazurecontainer',
			'dest-RSC'     => 'newrsccontainer',
		);
		foreach ( $create_bucket_array as $bucket_array_key => $bucket_array_name ) {
			if ( isset( $settings[ $bucket_array_key ][ $bucket_array_name ] ) ) {
				unset( $settings[ $bucket_array_key ][ $bucket_array_name ] );
				$is_new_bucket = true;
			}
		}

		if ( $is_new_bucket ) {

			self::add_message( __( 'We send info about creating new bucket to child website. Because of that we delete this option in order to not create bucket twice.', $this->plugin_translate ) );
		}

		return $settings;
	}

	/**
	 * Main ajax function
	 **/
	public function ajax_contact_with_root() {
		$this->ajax_check_permissions( 'contact_with_root' );

		global $mainWPBackWPupExtensionActivator;

		$method = ( isset( $_POST['method'] ) ? $_POST['method'] : '' );

		switch ( $method ) {
			case 'global_jobs':
				$job_array = array();
				$jobs      = MainWPBackWPupDB::Instance()->get_jobs_by_website_id( 0 );

				if ( is_array( $jobs ) ) {
					foreach ( $jobs as $job ) {
						$unserialized = json_decode( $job['settings'], true );
						$job_array[]  = array(
							'id'           => $job['id'],
							'name'         => isset( $unserialized['job']['name'] ) ? $unserialized['job']['name'] : 'Unknown',
							'type'         => ( isset( $unserialized['job']['type'] ) ? $unserialized['job']['type'] : array() ),
							'destinations' => ( isset( $unserialized['job']['destinations'] ) ? $unserialized['job']['destinations'] : array() ),
						);
					}
				}

				$this->json_ok( null, array( 'response' => $job_array ) );
				break;

			case 'upgrade_plugin':
				$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );
				$this->is_website_exist( $website_id );

				$_POST['websiteId'] = $website_id;
				$_POST['type']      = 'plugin';
				do_action( 'mainwp_upgradePluginTheme' );
				die();
				break;

			case 'backup_now_global':
				$job_id = ( isset( $_POST['job_id'] ) ? $_POST['job_id'] : '' );
				$job    = MainWPBackWPupDB::Instance()->get_job_by_id( $job_id );

				if ( ! isset( $job['id'] ) ) {
					$this->json_error( __( 'Job does not exist', $this->plugin_translate ) );
				}

				$global_jobs = MainWPBackWPupDB::Instance()->get_global_job_by_global_id( $job_id );

				$jobs_ids_array = array();
				foreach ( $global_jobs as $global_job ) {
					$jobs_ids_array[ $global_job['website_id'] ] = $global_job['job_id'];
				}

				$return = $this->get_list_of_overrides();

				$jobs_ids = array();
				foreach ( $return['ids'] as $temp_website_id ) {
					if ( ! isset( $jobs_ids_array[ $temp_website_id ] ) ) {
						$this->json_error( __( 'Cannot retrive global job id for website_id ' . intval( $temp_website_id ) . '. Probably you disable `Override General Settings` and don\' synchronize datas. Please save global job and try again.', $this->plugin_translate ) );
					}
					$jobs_ids[] = $jobs_ids_array[ $temp_website_id ];
				}

				$return['jobs'] = $jobs_ids;
				$this->json_ok( null, $return );

				break;

			case 'show_hide':
				$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );
				$this->is_website_exist( $website_id );
				$show_hide = isset( $_POST['show_hide'] ) ? intval( $_POST['show_hide'] ) : 0;

				$post_data   = array(
					'action'    => 'backwpup_show_hide',
					'show_hide' => $show_hide,
				);
				$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

				$this->check_child_response( $information, __( 'Cannot contact with child when show/hide', $this->plugin_translate ), true );

				$hide_backwpup = self::get_option( 'hide_the_plugin' );
				if ( ! is_array( $hide_backwpup ) ) {
					$hide_backwpup = array();
				}

				$hide_backwpup[ $website_id ] = ( $show_hide == 1 ) ? 1 : 0;

				self::set_option( 'hide_the_plugin', $hide_backwpup );

				$this->json_ok();
				break;

			case 'delete_job':
				$website_id   = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );
				$job_id       = ( isset( $_POST['job_id'] ) ? intval( $_POST['job_id'] ) : 0 );
				$is_global    = ( isset( $_POST['is_global'] ) && $_POST['is_global'] == '1' ? true : false );
				$is_child_job = ( isset( $_POST['is_child_job'] ) && $_POST['is_child_job'] == 'true' ? true : false );

				if ( $job_id == 0 ) {
					$this->json_error( __( 'Missing job id', $this->plugin_translate ) );
				}

				if ( $website_id > 0 ) {
					$this->is_website_exist( $website_id );

					if ( $is_global ) {
						if ( $job_id == 0 ) {
							$this->json_error( __( 'Job_id must be greater than 0', $this->plugin_translate ) );
						}
					} else {
						if ( ! $is_child_job ) {
							$our_job_id = $job_id;
							$job_info   = MainWPBackWPupDB::Instance()->get_job_by_website_id_and_id( $website_id, $our_job_id );

							if ( ! isset( $job_info['job_id'] ) ) {
								$this->json_error( __( 'Cannot get child job_id', $this->plugin_translate ) );
							}

							$job_id = $job_info['job_id'];
						}
					}

					$post_data = array(
						'action' => 'backwpup_delete_job',
						'job_id' => $job_id,
					);

					$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

					$this->check_child_response( $information, __( 'Cannot contact with child when delete job', $this->plugin_translate ), true );

					if ( ! $is_global ) {
						if ( MainWPBackWPupDB::Instance()->delete_job_by_website_id_and_id( $website_id, $our_job_id ) === false ) {
							$this->json_error( __( 'Cannot delete job in database', $this->plugin_translate ) );
						}
					}

					$this->json_ok();
				} else {
					// First global request - get child data and delete global data in database
					$global_jobs = MainWPBackWPupDB::Instance()->get_global_job_by_global_id( $job_id );

					if ( MainWPBackWPupDB::Instance()->delete_global_jobs_by_global_id( $job_id ) === false ) {
						$this->json_error( __( 'Cannot delete global jobs connections in database', $this->plugin_translate ) );
					}

					$job_ids          = array();
					$website_ids      = array();
					$website_ids_temp = array();
					$website_urls     = array();
					if ( is_array( $global_jobs ) ) {
						foreach ( $global_jobs as $global_job ) {
							$job_ids[]          = $global_job['job_id'];
							$website_ids_temp[] = $global_job['website_id'];
						}

						$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_ids_temp, array(), array() );

						foreach ( $dbwebsites as $website ) {
							$website_ids[]  = $website->id;
							$website_urls[] = esc_html( $website->url );
						}
					}

					if ( MainWPBackWPupDB::Instance()->delete_job_by_website_id_and_id( $website_id, $job_id ) === false ) {
						$this->json_error( __( 'Cannot delete job in database', $this->plugin_translate ) );
					}

					$this->json_ok(
						null,
						array(
							'job_ids'      => $job_ids,
							'website_ids'  => $website_ids,
							'website_urls' => $website_urls,
						)
					);
				}
				break;

			case 'save_premium':
				$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );
				$is_premium = ( isset( $_POST['is_premium'] ) && $_POST['is_premium'] == '1' ? 1 : 0 );

				if ( $website_id > 0 ) {
					$this->is_website_exist( $website_id );
				}

				$settings = MainWPBackWPupDB::Instance()->get_settings_by_website_id( $website_id );

				if ( ! isset( $settings['id'] ) ) {
					$settings             = array();
					$settings['override'] = 0;
				} else {
					$settings = json_decode( $settings['settings'], true );
				}

				$settings['is_premium'] = $is_premium;
				if ( MainWPBackWPupDB::Instance()->insert_or_update_settings_by_website_id( wp_json_encode( $settings ), $settings['is_premium'], $settings['override'], $website_id ) === false ) {
					$this->json_error( __( 'Cannot update settings in database', $this->plugin_translate ) );
				}

				$this->json_ok();
				break;

			case 'save_override':
				$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );
				$override   = ( isset( $_POST['override'] ) && $_POST['override'] == '1' ? 1 : 0 );

				$this->is_website_exist( $website_id );

				$settings = MainWPBackWPupDB::Instance()->get_settings_by_website_id( $website_id );

				if ( ! isset( $settings['id'] ) ) {
					$settings               = array();
					$settings['is_premium'] = 0;
				} else {
					$settings = json_decode( $settings['settings'], true );
				}

				$settings['override'] = $override;

				if ( MainWPBackWPupDB::Instance()->insert_or_update_settings_by_website_id( wp_json_encode( $settings ), $settings['is_premium'], $settings['override'], $website_id ) === false ) {
					$this->json_error( __( 'Cannot update settings in database', $this->plugin_translate ) );
				}

				$this->json_ok();

				break;
			default:
				$this->json_error( __( 'Missing type in contact with root', $this->plugin_translate ) );
		}
	}

	/**
	 * Function used to contact with child
	 * Check if given child exist
	 * Check if requested method exist
	 * Prepare params
	 * Send request to child
	 * Check response
	 * Send return to user using ajax
	 **/
	public function ajax_contact_with_child() {
		$this->ajax_check_permissions( 'contact_with_child' );

		$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );

		$this->is_website_exist( $website_id );

		global $mainWPBackWPupExtensionActivator;

		$method = ( isset( $_POST['method'] ) ? $_POST['method'] : '' );

		$post_data = array( 'action' => $method );

		$args = array();

		switch ( $method ) {
			case 'backwpup_backup_now':
				$args['job_id'] = ( isset( $_POST['job_id'] ) ? $_POST['job_id'] : '' );
				break;

			case 'backwpup_ajax_working':
				$args['logfile'] = ( isset( $_POST['logfile'] ) ? $_POST['logfile'] : '' );
				$args['logpos']  = ( isset( $_POST['logpos'] ) ? $_POST['logpos'] : '' );
				break;

			case 'backwpup_backup_abort':
				break;

			case 'backwpup_tables':
				$args['website_id'] = $website_id;
				$args['type']       = ( isset( $_POST['type'] ) ? $_POST['type'] : '' );
				break;

			case 'backwpup_view_log':
				$args['logfile'] = ( isset( $_POST['logfile'] ) ? $_POST['logfile'] : '' );
				break;

			case 'backwpup_delete_log':
				$args['logfile'] = ( isset( $_POST['logfile'] ) ? array( $_POST['logfile'] ) : '' );
				break;

			case 'backwpup_delete_backup':
				$args['backupfile'] = ( isset( $_POST['backupfile'] ) ? $_POST['backupfile'] : '' );
				$args['dest']       = ( isset( $_POST['dest'] ) ? $_POST['dest'] : '' );
				break;

			case 'backwpup_get_child_tables':
				$args['dbhost']     = ( isset( $_POST['dbhost'] ) ? $_POST['dbhost'] : '' );
				$args['dbuser']     = ( isset( $_POST['dbuser'] ) ? $_POST['dbuser'] : '' );
				$args['dbpassword'] = ( isset( $_POST['dbpassword'] ) ? $_POST['dbpassword'] : '' );
				$args['dbname']     = ( isset( $_POST['dbname'] ) ? $_POST['dbname'] : '' );
				$args['first']      = ( isset( $_POST['first'] ) ? $_POST['first'] : '' );
				$args['job_id']     = ( isset( $_POST['job_id'] ) ? $_POST['job_id'] : '' );
				break;

			case 'backwpup_information':
				break;

			case 'backwpup_get_glacier_vault':
				$args['glacieraccesskey'] = ( isset( $_POST['glacieraccesskey'] ) ? $_POST['glacieraccesskey'] : '' );
				$args['glaciersecretkey'] = ( isset( $_POST['glaciersecretkey'] ) ? $_POST['glaciersecretkey'] : '' );
				$args['vaultselected']    = ( isset( $_POST['vaultselected'] ) ? $_POST['vaultselected'] : '' );
				$args['glacierregion']    = ( isset( $_POST['glacierregion'] ) ? $_POST['glacierregion'] : '' );
				break;

			case 'backwpup_wizard_system_scan':
				break;

			case 'backwpup_is_pro':
				break;

			case 'backwpup_get_job_files':
				break;

			case 'backwpup_destination_email_check_email':
				$email_params = array(
					'emailaddress',
					'emailsndemail',
					'emailmethod',
					'emailsendmail',
					'emailsndemailname',
					'emailhost',
					'emailhostport',
					'emailsecure',
					'emailuser',
					'emailpass',
				);

				foreach ( $email_params as $email_param ) {
					if ( isset( $_POST[ $email_param ] ) ) {
						$args[ $email_param ] = $_POST[ $email_param ];
					}
				}
				break;

			default:
				$this->json_error( __( 'Wrong method in contact with child', $this->plugin_translate ) );
		}

		if ( ! empty( $args ) ) {
			$post_data['settings'] = $args;
		}

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

		if ( 'backwpup_view_log' == $method ) {
			if ( is_array( $information ) && isset( $information['response'] ) ) {
				$information['response'] = self::esc_content( $information['response'] );
			}
		}

		$this->check_child_response( $information, __( 'Cannot contact with child', $this->plugin_translate ), true );

		$this->json_ok( null, $information );
	}

	/**
	 * Method esc_content()
	 *
	 * Escape content,
	 * allowed content (a,href,title,br,em,strong,p,hr,ul,ol,li,h1,h2).
	 *
	 * @param mixed  $content Content to escape.
	 * @param string $type Type of content. Default = note.
	 *
	 * @return string Filtered content containing only the allowed HTML.
	 */
	public static function esc_content( $content, $type = 'note' ) {
		if ( 'note' === $type ) {

			$allowed_html = array(
				'a'      => array(
					'href'  => array(),
					'title' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'p'      => array(),
				'hr'     => array(),
				'ul'     => array(),
				'ol'     => array(),
				'li'     => array(),
				'h1'     => array(),
				'h2'     => array(),
				'head'   => array(),
				'html'   => array(
					'lang' => array(),
				),
				'meta'   => array(
					'name'       => array(),
					'http-equiv' => array(),
					'content'    => array(),
					'charset'    => array(),
				),
				'title'  => array(),
				'body'   => array(
					'style' => array(),
				),
			);

			$content = wp_kses( $content, $allowed_html );

		} else {
			$content = wp_kses_post( $content );
		}

		return $content;
	}

	/**
	 * When we want to redirect user to specific child page
	 **/
	protected function get_data_authed( $website, $open_location = '' ) {
		$paramValue = 'index.php';
		$params     = array();
		if ( $website && $paramValue != '' ) {
			$nonce = rand( 0, 9999 );
			if ( ( $website->nossl == 0 ) && function_exists( 'openssl_verify' ) ) {
				$nossl = 0;
				openssl_sign( $paramValue . $nonce, $signature, base64_decode( $website->privkey ) );
			} else {
				$nossl     = 1;
				$signature = md5( $paramValue . $nonce . $website->nosslkey );
			}
			$signature = base64_encode( $signature );

			$params = array(
				'login_required'  => 1,
				'user'            => $website->adminname,
				'mainwpsignature' => rawurlencode( $signature ),
				'nonce'           => $nonce,
				'nossl'           => $nossl,
				'open_location'   => base64_encode( $open_location ),
				'where'           => $paramValue,
			);
		}

		$url  = ( isset( $website->siteurl ) && $website->siteurl != '' ? $website->siteurl : $website->url );
		$url .= ( substr( $url, - 1 ) != '/' ? '/' : '' );
		$url .= '?';

		foreach ( $params as $key => $value ) {
			$url .= $key . '=' . $value . '&';
		}

		return rtrim( $url, '&' );
	}

	/**
	 * When we want to download files directly from child website we need to authorize url
	 * We use param open_location and where
	 **/
	public function ajax_open_child_site() {
		$this->ajax_check_permissions( 'open_child_site' );

		$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );

		$this->is_website_exist( $website_id );

		global $mainWPBackWPupExtensionActivator;

		$websites = apply_filters( 'mainwp_getdbsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), array( $website_id ), '' );

		$website = null;
		if ( $websites && is_array( $websites ) ) {
			$website = current( $websites );
		}

		if ( is_null( $website ) ) {
			$this->json_error( __( 'Cannot get child data', $this->plugin_translate ) );
		}

		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'dashboard', 'access_wpadmin_on_child_sites' ) ) {
			$this->json_error( mainwp_do_not_have_permissions( 'WP-Admin on child sites' ) );
		}

		$open_location = ( isset( $_POST['open_location'] ) ? trim( $_POST['open_location'] ) : '' );

		$open_location = substr( $open_location, strpos( $open_location, '/wp-admin' ) );

		if ( strlen( $open_location ) == 0 ) {
			$this->json_error( __( 'Missing open location', $this->plugin_translate ) );
		}

		$this->json_ok( null, array( 'url' => $this->get_data_authed( $website, $open_location ) ) );
	}

	/**
	 * List of nonce tokens used by plugin which are passed to vies using wp_localize_script
	 **/
	public function get_nonce() {
		$nonce_ids = array(
			'contact_with_child',
			'contact_with_root',
			'open_child_site',
			'synchronize_global_job',
			'synchronize_global_job_step_2',
			'synchronize_global_settings',
			'synchronize_global_settings_step_2',
			'get_buckets',
		);

		$generated_nonce = array();

		foreach ( $nonce_ids as $id ) {
			$generated_nonce[ $id ] = wp_create_nonce( self::$nonce_token . $id );
		}

		return $generated_nonce;
	}

	/**
	 * Add translations for JS
	 **/
	protected function add_translation( &$array, $key, $val ) {
		if ( ! is_array( $array ) ) {
			$array = array();
		}

		$text = str_replace( ' ', '_', $key );
		$text = preg_replace( '/[^A-Za-z0-9_]/', '', $text );

		$array[ $text ] = $val;
	}

	/**
	 * Return translations for JS
	 *
	 * @todo add translations
	 **/
	public function get_js_translations() {
		$translations = array();
		$this->add_translation( $translations, 'Text from js', __( 'Text from js', $this->plugin_translate ) );

		return $translations;
	}

	public function init() {
		load_plugin_textdomain( $this->plugin_translate, false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Here we decide if fire our saving events
	 **/
	public function admin_init() {
		$this->ajax_check_permissions( 'admin_init', false );

		if ( isset( $_POST['settings_website_id'] ) ) {
			$this->update_settings();
		}

		if ( isset( $_POST['job_tab'] ) ) {
			$this->update_jobs();
		}
	}

	/**
	 * Update single job in database and in child website
	 **/
	protected function update_jobs() {
		check_admin_referer( self::$nonce_token . 'update_jobs' );

		global $mainWPBackWPupExtensionActivator;

		$website_id = ( isset( $_POST['website_id'] ) ? intval( $_POST['website_id'] ) : 0 );
		$job_id     = ( isset( $_POST['job_id'] ) ? intval( $_POST['job_id'] ) : 0 );
		$our_id     = ( isset( $_POST['our_id'] ) ? intval( $_POST['our_id'] ) : 0 );
		$job_tab    = ( isset( $_POST['job_tab'] ) ? trim( $_POST['job_tab'] ) : '' );

		if ( $website_id > 0 ) {
			$this->is_website_exist( $website_id );
		}

		if ( $our_id > 0 ) {
			$settings_from_db = MainWPBackWPupDB::Instance()->get_job_by_id( $our_id );
			if ( isset( $settings_from_db['id'] ) ) {
				$settings = json_decode( $settings_from_db['settings'], true );
			} else {
				wp_die( __( 'Cannot find our_id in database', $this->plugin_translate ) );
			}
		} else {
			$settings = array();
		}

		if ( ! isset( self::$jobs_and_destinations[ $job_tab ] ) ) {
			wp_die( __( 'Invalid or missing tab ' . esc_html( $job_tab ), $this->plugin_translate ) );
		}

		$job_info = self::$jobs_and_destinations[ $job_tab ];

		$job_class = new $job_info['class']( $job_tab, $job_info['type'], $website_id, $job_id, $our_id );

		if ( isset( $settings[ $job_tab ] ) ) {
			$settings_temp = $job_class->save_form( $settings[ $job_tab ] );
		} else {
			$settings_temp = $job_class->save_form( array() );
		}

		if ( $website_id > 0 ) {
			$post_data = array(
				'action'   => 'backwpup_insert_or_update_jobs',
				'settings' => array(
					'job_id' => $job_id,
					'tab'    => $job_tab,
					'value'  => $this->replace_tokens( $settings_temp, $website_id, true ),
				),
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

			$error = $this->check_child_response( $information, __( 'Cannot update job in child website', $this->plugin_translate ), false );

			if ( ! empty( $error ) ) {
				self::add_error_message( $error );

				return;
			}

			if ( isset( $information['error_message'] ) ) {
				self::add_error_message( $information['error_message'] );

				return;
			}

			$job_id = $information['job_id'];

			if ( ! empty( $information['message'] ) ) {
				self::add_message( __( 'Child return messages: ', $this->plugin_translate ) );
				foreach ( $information['message'] as $message ) {
					self::add_message( $message );
				}
			}

			if ( ! empty( $information['changes'] ) ) {
				foreach ( $information['changes'] as $key => $change ) {
					if ( preg_match( '/%(sitename|url|time|date)%/', $settings_temp[ $key ] ) ) {
						self::add_message( __( 'Difference in key', $this->plugin_translate ) . " '" . esc_html( $key ) . "' " . __( 'child return', $this->plugin_translate ) . " '" . esc_html( $change ) . "'" );
					} else {
						$settings_temp[ $key ] = $change;
						self::add_message( __( 'Automatically change', $this->plugin_translate ) . " '" . esc_html( $key ) . "' " . __( 'to', $this->plugin_translate ) . " '" . esc_html( $change ) . "'" );
					}
				}
			}

			$settings = $this->remove_create_bucket_from_settings( $settings );
		} else {
			$job_id = 0;

			$this->synchronize = 'job';

			// saving global job
			if ( isset( $_POST['select_by'] ) && ( $_POST['select_by'] == 'site' || $_POST['select_by'] == 'group' ) ) {
				$selected_sites = $selected_groups = array();
				if ( $_POST['select_by'] == 'site' ) {
					$selected_sites = $_POST['selected_sites'];
				} else {
					$selected_groups = $_POST['selected_groups'];
				}
				$selected_siteids = false;
				if ( ! empty( $selected_sites ) || ! empty( $selected_groups ) ) {
					$selected_siteids = array();
					$dbwebsites       = apply_filters( 'mainwp_getdbsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $selected_sites, $selected_groups );
					if ( $dbwebsites ) {
						foreach ( $dbwebsites as $website ) {
							$selected_siteids[] = $website->id;
						}
					}
				}
				update_option( 'mainwp_backwpup_synchronize_site_ids', $selected_siteids );
			}
		}

		$settings[ $job_tab ] = $settings_temp;

		$settings = wp_json_encode( $settings );

		$our_job_id = MainWPBackWPupDB::Instance()->insert_or_update_job_by_id( $our_id, $website_id, $job_id, $settings );

		if ( $our_job_id === false ) {
			wp_die( __( 'Cannot locally update job', $this->plugin_translate ) );
		}

		if ( $our_id == 0 ) {
			// We want to have edit page
			$_GET['our_job_id'] = $our_job_id;
			self::add_message( __( 'Job created successfully.', $this->plugin_translate ) );
		} else {
			self::add_message( __( 'Job updated successfully.', $this->plugin_translate ) );
		}

		// Create new global job - need move to sync page
		if ( $website_id == 0 && $our_id == 0 ) {
			wp_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Backwpup-Extension&id=' . $website_id . '&our_job_id=' . $our_job_id . '&synchronize=job' ) );
			die();
		}
	}

	/**
	 * Update single settings
	 **/
	protected function update_settings() {
		check_admin_referer( self::$nonce_token . 'update_settings' );

		global $mainWPBackWPupExtensionActivator;

		$website_id = ( isset( $_POST['settings_website_id'] ) ? $_POST['settings_website_id'] : 0 );

		if ( $website_id > 0 ) {
			$this->is_website_exist( $website_id );
		}

		$settings = array();

		if ( isset( $_POST['showadminbar'] ) ) {
			$settings['showadminbar'] = 1;
		}
		if ( isset( $_POST['showfoldersize'] ) ) {
			$settings['showfoldersize'] = 1;
		}

		if ( isset( $_POST['keepplugindata'] ) ) {
			$settings['keepplugindata'] = 1;
		}

		if ( isset( $_POST['phone_home_client'] ) ) {
			$settings['phone_home_client'] = 1;
		}

		$jobstepretry = 0;
		if ( 100 > $_POST['jobstepretry'] && 0 < $_POST['jobstepretry'] ) {
			$jobstepretry = abs( (int) $_POST['jobstepretry'] );
		}
		if ( empty( $jobstepretry ) or ! is_int( $jobstepretry ) ) {
			$jobstepretry = 3;
		}
		$settings['jobstepretry'] = $jobstepretry;

		$max_exe_time = abs( (int) $_POST['jobmaxexecutiontime'] );
		if ( ! is_int( $max_exe_time ) || $max_exe_time < 0 ) {
			$max_exe_time = 0;
		} elseif ( $max_exe_time > 300 ) {
			$max_exe_time = 300;
		}
		$settings['jobmaxexecutiontime'] = $max_exe_time;

		// $jobziparchivemethod             = ( isset( $_POST['jobziparchivemethod'] ) ? $_POST['jobziparchivemethod'] : '' );
		// $settings['jobziparchivemethod'] = ( strcmp( $jobziparchivemethod, 'PclZip' ) === 0 || strcmp( $jobziparchivemethod, 'ZipArchive' ) === 0 ? $jobziparchivemethod : '' );

		// if ( isset( $_POST['jobnotranslate'] ) ) {
		// $settings['jobnotranslate'] = 1;
		// }

		if ( isset( $_POST['jobdooutput'] ) ) {
			$settings['jobdooutput'] = 1;
		}

		if ( isset( $_POST['windows'] ) ) {
			$settings['windows'] = 1;
		}

		$settings['jobwaittimems'] = ( isset( $_POST['jobwaittimems'] ) ? intval( $_POST['jobwaittimems'] ) : 0 );
		$settings['maxlogs']       = ( isset( $_POST['maxlogs'] ) ? abs( (int) $_POST['maxlogs'] ) : 0 );
		if ( isset( $_POST['gzlogs'] ) ) {
			$settings['gzlogs'] = 1;
		}

		if ( isset( $_POST['loglevel'] ) ) {
			$settings['loglevel'] =
			in_array(
				$_POST['loglevel'],
				array( 'normal_translated', 'normal', 'debug_translated', 'debug' ),
				true
			) ? $_POST['loglevel'] : 'normal_translated';
		}

		if ( isset( $_POST['protectfolders'] ) ) {
			$settings['protectfolders'] = 1;
		}

		if ( isset( $_POST['keepplugindata'] ) ) {
			$settings['keepplugindata'] = 1;
		}

		$settings['httpauthuser']     = ( isset( $_POST['httpauthuser'] ) ? $_POST['httpauthuser'] : '' );
		$settings['httpauthpassword'] = ( isset( $_POST['httpauthpassword'] ) ? $_POST['httpauthpassword'] : '' );
		$settings['jobrunauthkey']    = ( isset( $_POST['jobrunauthkey'] ) ? preg_replace( '/[^a-zA-Z0-9]/', '', trim( $_POST['jobrunauthkey'] ) ) : '' );

		if ( isset( $_POST['is_premium'] ) && $_POST['is_premium'] == '1' ) {
			$settings['is_premium']              = 1;
			$settings['dropboxappkey']           = ( isset( $_POST['dropboxappkey'] ) ? $_POST['dropboxappkey'] : '' );
			$settings['dropboxappsecret']        = ( isset( $_POST['dropboxappsecret'] ) ? $_POST['dropboxappsecret'] : '' );
			$settings['dropboxsandboxappkey']    = ( isset( $_POST['dropboxsandboxappkey'] ) ? $_POST['dropboxsandboxappkey'] : '' );
			$settings['dropboxsandboxappsecret'] = ( isset( $_POST['dropboxsandboxappsecret'] ) ? $_POST['dropboxsandboxappsecret'] : '' );
			$settings['sugarsynckey']            = ( isset( $_POST['sugarsynckey'] ) ? $_POST['sugarsynckey'] : '' );
			$settings['sugarsyncsecret']         = ( isset( $_POST['sugarsyncsecret'] ) ? $_POST['sugarsyncsecret'] : '' );
			$settings['sugarsyncappid']          = ( isset( $_POST['sugarsyncappid'] ) ? $_POST['sugarsyncappid'] : '' );
			$settings['googleclientsecret']      = ( isset( $_POST['googleclientsecret'] ) ? $_POST['googleclientsecret'] : '' );
			$settings['googleclientid']          = ( isset( $_POST['googleclientid'] ) ? $_POST['googleclientid'] : '' );
			if ( ! empty( $_POST['hash'] ) && strlen( $_POST['hash'] ) >= 6 ) {
				$settings['hash'] = $_POST['hash'];
			} else {
				$settings['hash'] = '';
			}
		} else {
			$settings['is_premium'] = 0;
		}

		if ( $website_id > 0 ) {
			if ( isset( $_POST['override'] ) && $_POST['override'] == '1' ) {
				$settings['override'] = 1;
			} else {
				$settings['override'] = 0;
			}
		} else {
			$settings['override'] = 0;
		}

		if ( isset( $_POST['logfolder'] ) ) {
			$logfolder = trim( stripslashes( $_POST['logfolder'] ) );
			if ( strpos( $logfolder, 'uploads/mainwp/' ) === false ) {
				$logfolder = 'uploads/mainwp/' . $logfolder;
			}
		} else {
			$logfolder = '';
		}
		$settings['logfolder'] = $logfolder;

		if ( $website_id > 0 && $settings['override'] == 1 ) {
			$post_data = array(
				'action'   => 'backwpup_update_settings',
				'settings' => array( 'value' => $this->replace_tokens( $settings, $website_id, true ) ),
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

			$error = $this->check_child_response( $information, __( 'Cannot update settings in child website', $this->plugin_translate ), false );

			if ( ! empty( $error ) ) {
				self::add_error_message( $error );

				return;
			}

			if ( ! empty( $information['changes'] ) ) {
				foreach ( $information['changes'] as $key => $change ) {
					if ( preg_match( '/%(sitename|url|time|date)%/', $settings[ $key ] ) ) {
						self::add_message( __( 'Difference in key', $this->plugin_translate ) . " '" . esc_html( $key ) . "' " . __( 'child return', $this->plugin_translate ) . " '" . esc_html( $change ) . "'" );
					} else {
						$settings[ $key ] = $change;
						self::add_message( __( 'Automatically change', $this->plugin_translate ) . " '" . esc_html( $key ) . "' " . __( 'to', $this->plugin_translate ) . " '" . esc_html( $change ) . "'" );
					}
				}
			}

			self::add_message( __( 'Settings saved successfully', $this->plugin_translate ) );
		} elseif ( $website_id == 0 ) {
			$this->synchronize = 'settings';
		}

		// At end because we can get warning from child earlier
		if ( MainWPBackWPupDB::Instance()->insert_or_update_settings_by_website_id( wp_json_encode( $settings ), $settings['is_premium'], $settings['override'], $website_id ) === false ) {
			self::add_error_message( __( 'Cannot update settings i database', $this->plugin_translate ) );

			return;
		}
	}

	protected function do_update_settings() {

	}

	function mainwp_sync_extensions_options( $values = array() ) {
		$values['mainwp-backwpup-extension'] = array(
			'plugin_name' => 'BackWPup',
			'plugin_slug' => 'backwpup/backwpup.php',
		);

		return $values;
	}

	function mainwp_apply_plugin_settings( $website_id ) {
		$global_settings = MainWPBackWPupDB::Instance()->get_settings_by_website_id( 0 );
		if ( ! isset( $global_settings['id'] ) ) {
			$this->json_error( __( 'Please save global settings before first synchronization', $this->plugin_translate ) );
		}

		global $mainWPBackWPupExtensionActivator;

		$post_data = array(
			'action'   => 'backwpup_update_settings',
			'settings' => array( 'value' => json_decode( $global_settings['settings'], true ) ),
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), $website_id, 'backwpup', $post_data );

		$result = array();
		if ( is_array( $information ) ) {
			if ( isset( $information['success'] ) && 1 == $information['success'] ) {
				$result = array( 'result' => 'success' );
			} elseif ( isset( $information['error'] ) ) {
				$result = array( 'error' => $information['error'] );
			} else {
				$result = array( 'result' => 'failed' );
			}
		} else {
			$result = array( 'error' => __( 'Undefined error', $this->plugin_translate ) );
		}
		die( json_encode( $result ) );
	}

	/**
	 * When we send something to child website we check if response is correct
	 **/
	public function check_child_response( $response, $error_message, $json_response = true ) {
		if ( ! isset( $response['success'] ) || $response['success'] != 1 ) {
			if ( isset( $response['error'] ) ) {
				if ( $json_response ) {
					$this->json_error( $error_message . ' : ' . $response['error'] );
				} else {
					return $error_message . ' : ' . $response['error'];
				}
			} else {
				if ( $json_response ) {
					$this->json_error( $error_message );
				} else {
					return $error_message;
				}
			}
		}
	}

	function admin_enqueue_scripts() {

		wp_register_script( $this->plugin_handle . 'angular-core', plugins_url( '../js/angular.min.js', __FILE__ ), array( 'jquery' ) );
		wp_register_script( $this->plugin_handle . 'ng-table', plugins_url( '../js/ng-table.min.js', __FILE__ ), array( $this->plugin_handle . 'angular-core' ) );
		wp_register_script( $this->plugin_handle . 'ng-sanitize', plugins_url( '../js/angular-sanitize.min.js', __FILE__ ), array( $this->plugin_handle . 'angular-core' ) );
		wp_register_script(
			$this->plugin_handle . 'app',
			plugins_url( '../js/app.js', __FILE__ ),
			array(
				$this->plugin_handle . 'ng-table',
				$this->plugin_handle . 'ng-sanitize',
			),
			'20191002'
		);

		wp_register_style( $this->plugin_handle . 'ng-table', plugins_url( '../css/ng-table.min.css', __FILE__ ) );
		wp_register_style( $this->plugin_handle . 'app', plugins_url( '../css/app.css', __FILE__ ) );

		wp_localize_script( $this->plugin_handle . 'app', $this->plugin_handle . '_translations', $this->get_js_translations() );

		wp_localize_script( $this->plugin_handle . 'app', $this->plugin_handle . '_security_nonce', $this->get_nonce() );

		wp_enqueue_script( $this->plugin_handle . 'app' );
		wp_enqueue_style( $this->plugin_handle . 'ng-table' );
		wp_enqueue_style( $this->plugin_handle . 'app' );
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

	public function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title'       => __( 'BackWPup', $this->plugin_translate ),
			'slug'        => 'Backwpup',
			'sitetab'     => true,
			'menu_hidden' => true,
			'callback'    => array( $this, 'render_managesites' ),
		);

		return $subPage;
	}

	public function managesites_column_url( $actions, $websiteid ) {
		$actions['Backwpup'] = sprintf( '<a href="admin.php?page=ManageSitesBackwpup&id=%1$s">' . __( 'BackWPup', $this->plugin_translate ) . '</a>', $websiteid );

		return $actions;
	}

	/**
	 * Render page: http://wp/wp-admin/admin.php?page=ManageSitesBackwpup&id=%website_id%
	 **/
	public function render_managesites() {
		do_action( 'mainwp_pageheader_sites', 'Backwpup' );
		$this->render_extension_page();
		do_action( 'mainwp_pagefooter_sites', 'Backwpup' );
	}

	/**
	 * Render page: http://wp/wp-admin/admin.php?page=Extensions-Mainwp-Backwpup-Extension
	 **/
	public function render_extension_page() {
		global $mainWPBackWPupExtensionActivator;
		$website             = null;
		$is_plugin_activated = false;
		// For global settings set pro
		$is_pro_installed = true;

		$params = array( 'default' => array() );
		if ( isset( $_GET['id'] ) ) {
			$temp_website_id = intval( $_GET['id'] );
			if ( $temp_website_id > 0 ) {
				if ( $this->is_website_exist( $temp_website_id, false ) === false ) {
					self::end_with_message_gracefully( __( 'Website does not exist.', $this->plugin_translate ) );

					return;
				}

				$is_pro_installed = false;
				$options          = array(
					'plugin_upgrades' => true,
					'plugins'         => true,
				);
				$website_info     = apply_filters( 'mainwp_getdbsites', $mainWPBackWPupExtensionActivator->getChildFile(), $mainWPBackWPupExtensionActivator->getChildKey(), array( $temp_website_id ), '', $options );

				$website = current( $website_info );
				if ( $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( strcmp( $plugin['slug'], 'backwpup/backwpup.php' ) === 0 ) ) {
								if ( $plugin['active'] ) {
									$is_plugin_activated = true;
								}
							} elseif ( strcmp( $plugin['slug'], 'backwpup-pro/backwpup.php' ) === 0 ) {
								if ( $plugin['active'] ) {
									$is_plugin_activated = true;
									$is_pro_installed    = true;
								}
							}
						}
					}
				}

				if ( ! $is_plugin_activated ) {
					self::end_with_message_gracefully( __( 'BackWPup plugin is not installed or activated on the site.', $this->plugin_translate ) );

					return;
				}

				// On Backups Jobs page wee need to know which id are in database
				$backup_jobs_ids       = MainWPBackWPupDB::Instance()->get_job_by_website_id( $website->id );
				$backup_jobs_ids_array = array();
				foreach ( $backup_jobs_ids as $backup_job ) {
					$backup_jobs_ids_array[ intval( $backup_job['job_id'] ) ] = intval( $backup_job['id'] );
				}
				$params['backup_jobs_ids'] = $backup_jobs_ids_array;

				// On Backups Jobs page
				$backup_global_jobs_ids       = MainWPBackWPupDB::Instance()->get_global_job_by_website_id( $website->id );
				$backup_global_jobs_ids_array = array();
				foreach ( $backup_global_jobs_ids as $backup_job ) {
					$backup_global_jobs_ids_array[ intval( $backup_job['job_id'] ) ] = intval( $backup_job['id'] );
				}
				$params['backup_global_jobs_ids'] = $backup_global_jobs_ids_array;

			}
		}

		$website_id = ( is_null( $website ) ? 0 : intval( $website->id ) );
		$our_job_id = ( isset( $_GET['our_job_id'] ) ? intval( $_GET['our_job_id'] ) : 0 );

		if ( $our_job_id > 0 ) {
			$job = MainWPBackWPupDB::Instance()->get_job_by_id( $our_job_id );
			if ( ! isset( $job['id'] ) ) {
				self::end_with_message_gracefully( __( 'This job exists on child website but not in our database. Probably it was created manually', $this->plugin_translate ) );

				return;
			}

			$params['default'] = json_decode( $job['settings'], true );
			if ( $job['website_id'] != $website_id ) {
				wp_redirect( admin_url( 'admin.php?page=ManageSitesBackwpup&id=' . $job['website_id'] . '&our_job_id=' . $our_job_id ) );
				die();
			}
		}

		$params['website_id'] = $website_id;

		$jobs_id_array = array();

		$child_job = MainWPBackWPupDB::Instance()->get_child_job_id_by_id( $our_job_id );

		$params['our_job_id']       = intval( $our_job_id );
		$params['job_id']           = ! empty( $child_job['job_id'] ) ? intval( $child_job['job_id'] ) : 0;
		$params['is_pro_installed'] = $is_pro_installed;

		$website_settings       = array();
		$settings_by_website_id = MainWPBackWPupDB::Instance()->get_settings_by_website_id( $website_id );

		if ( isset( $settings_by_website_id['settings'] ) ) {
			$website_settings = json_decode( $settings_by_website_id['settings'], true );

			if ( $website_id == 0 ) {
				// For global check if is_premium is set
				$display_pro_settings = (int) $settings_by_website_id['is_premium'];
			} else {
				// For normal website - first check if has pro plugin installed
				if ( $is_pro_installed ) {
					if ( (int) $settings_by_website_id['override'] ) {
						$display_pro_settings = (int) $settings_by_website_id['is_premium'];
					} else {
						// This website use global settings
						$global_settings = MainWPBackWPupDB::Instance()->get_settings_by_website_id( 0 );
						// They may be not set
						if ( ! isset( $global_settings['is_premium'] ) ) {
							$display_pro_settings = false;
						} else {
							$display_pro_settings = (int) $global_settings['is_premium'];
						}
					}
				} else {
					// No pro plugin - no pro settings
					$display_pro_settings = false;
				}
			}
		} else {
			// No settings for this website
			$display_pro_settings = false;

			if ( $is_pro_installed ) {
				// But maybe global one exists?
				$global_settings = MainWPBackWPupDB::Instance()->get_settings_by_website_id( 0 );
				// They may be not set
				if ( isset( $global_settings['is_premium'] ) ) {
					$display_pro_settings = (int) $global_settings['is_premium'];
				}
			}
		}

		$params['display_pro_settings'] = $display_pro_settings;

		$params['settings'] = $website_settings;

		if ( ! empty( $this->synchronize ) ) {
			$params['synchronize'] = $this->synchronize;
		} elseif ( isset( $_GET['synchronize'] ) && $_GET['synchronize'] == 'job' ) {
			// When we create new global job - we are redirected to new page so $this->synchronize is destroyed
			$params['synchronize'] = 'job';
		}

		MainWPBackWPUpView::render_extension_page( $params );
	}
}
