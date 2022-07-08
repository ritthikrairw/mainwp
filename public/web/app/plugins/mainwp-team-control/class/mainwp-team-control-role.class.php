<?php
class MainWP_Team_Control_Role {
	protected $inner_capabilities = null;
	protected $mainwp_roles       = null;
	private static $instance      = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new MainWP_Team_Control_Role();
		}
		return self::$instance;
	}

	public function __construct() {
		add_filter( 'mainwp_currentuserallowedaccesssites', array( $this, 'allowed_access_sites' ), 10, 2 );
		add_filter( 'mainwp_currentuserallowedaccessgroups', array( $this, 'allowed_access_groups' ), 10, 1 );
		add_filter( 'mainwp_current_user_denied_access_groups', array( $this, 'denied_access_groups' ), 10, 1 );
	}

	public function added_new_site( $site_id = null ) {
		if ( empty( $site_id ) ) {
			return;
		}
		$mainwp_user = self::get_current_mainwp_user();
		if ( $mainwp_user ) {
			if ( ! is_array( $mainwp_user->roles ) ) {
				return;
			}
			if ( in_array( 'administrator', $mainwp_user->roles ) ) {
				return; // full access
			}

			$role_name = current( $mainwp_user->roles );

			if ( strpos( $role_name, 'mainwp_' ) === 0 ) {
				$update_caps                     = MainWP_Team_Control_DB::get_instance()->get_caps_by_role( $role_name );
				$update_caps['site'][ $site_id ] = 1;
				$role                            = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'role_name_id', $role_name );
				if ( $role ) {
					$update = array( 'role_capabilities' => base64_encode( serialize( $update_caps ) ) );
					MainWP_Team_Control_DB::get_instance()->update_role( $role->id, $update );
					self::get_current_mainwp_user( true );
				}
			}
		}
	}

	public function added_new_group( $group_id = null ) {
		if ( empty( $group_id ) ) {
			return;
		}
		$mainwp_user = self::get_current_mainwp_user();
		if ( $mainwp_user ) {
			if ( ! is_array( $mainwp_user->roles ) ) {
				return;
			}
			if ( in_array( 'administrator', $mainwp_user->roles ) ) {
				return; // full access
			}

			$role_name = current( $mainwp_user->roles );

			if ( strpos( $role_name, 'mainwp_' ) === 0 ) {
				$update_caps                       = MainWP_Team_Control_DB::get_instance()->get_caps_by_role( $role_name );
				$update_caps['group'][ $group_id ] = 1;
				$role                              = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'role_name_id', $role_name );
				if ( $role ) {
					$update = array( 'role_capabilities' => base64_encode( serialize( $update_caps ) ) );
					MainWP_Team_Control_DB::get_instance()->update_role( $role->id, $update );
					self::get_current_mainwp_user( true );
				}
			}
		}
	}

	function allowed_access_sites( $site_ids, $included_sites_in_allowed_groups = true ) {
		if ( self::current_user_is_supper_mainwp_admin() ) {
			return 'all';
		}
		$mainwp_user = self::get_current_mainwp_user();
		if ( ! is_array( $site_ids ) ) {
			$site_ids = array();
		}
		if ( is_array( $mainwp_user->capabilities['site'] ) && count( $mainwp_user->capabilities['site'] ) > 0 ) {
			foreach ( $mainwp_user->capabilities['site'] as $site_id => $val ) {
				if ( ! in_array( $site_id, $site_ids ) ) {
					$site_ids[] = $site_id;
				}
			}
		}

		if ( $included_sites_in_allowed_groups && isset( $mainwp_user->capabilities['siteids_in_allowed_groups'] ) ) {
			$siteids_in_groups = $mainwp_user->capabilities['siteids_in_allowed_groups'];
			if ( is_array( $siteids_in_groups ) && ( count( $siteids_in_groups ) > 0 ) ) {
				foreach ( $siteids_in_groups as $site_id ) {
					if ( ! in_array( $site_id, $site_ids ) ) {
						$site_ids[] = $site_id;
					}
				}
			}
		}
		return $site_ids;
	}

	function allowed_access_groups( $group_ids ) {
		if ( self::current_user_is_supper_mainwp_admin() ) {
			return 'all';
		}

		$mainwp_user = self::get_current_mainwp_user();

		if ( ! is_array( $group_ids ) ) {
			$group_ids = array();
		}

		if ( is_array( $mainwp_user->capabilities['group'] ) && count( $mainwp_user->capabilities['group'] ) > 0 ) {
			foreach ( $mainwp_user->capabilities['group'] as $group_id => $val ) {
				if ( ! in_array( $group_id, $group_ids ) ) {
					$group_ids[] = $group_id;
				}
			}
		}
		return $group_ids;
	}


	static function current_user_is_supper_mainwp_admin() {
		// to fix missing function error
		if ( ! function_exists( 'wp_get_current_user(' ) ) {
			include_once ABSPATH . WPINC . '/pluggable.php';
		}
		return current_user_can( 'administrator' );
	}

	public static function mainwp_roles_has( $roles, $role ) {
		if ( is_array( $roles ) ) {
			return in_array( $role, $roles );
		} else {
			return $roles == $role;
		}
	}

	static function is_core_mainwp_admin_caps( $cap_type, $cap ) {
		$supper_caps = array(
			'extension' => array(
				'mainwp-team-control' => 1,
			),
		);
		return ( isset( $supper_caps[ $cap_type ] ) && isset( $supper_caps[ $cap_type ][ $cap ] ) && $supper_caps[ $cap_type ][ $cap ] );
	}

	public static function team_control_current_user_can( $user_can, $cap_type = '', $cap = '' ) {
		$current_user = wp_get_current_user();
		if ( empty( $current_user ) || empty( $current_user->ID ) ) {
			return false;
		}

		if ( self::current_user_is_supper_mainwp_admin() ) {
			return true;
		}

		$mainwp_user = self::get_current_mainwp_user();

		if ( empty( $mainwp_user->capabilities ) ) {
			return false;
		}

		$allow_types = array( 'dashboard', 'extension', 'site', 'group', 'role' );

		if ( ! in_array( $cap_type, $allow_types ) ) {
			return $user_can;
		}

		if ( 'role' == $cap_type ) {
			return self::mainwp_roles_has( $mainwp_user->roles, $cap );
		}

		return self::team_control_current_user_has_cap( $cap_type, $cap );
	}

	static function team_control_current_user_has_cap( $cap_type, $caps ) {
		$mainwp_user = self::get_current_mainwp_user();

		if ( is_array( $caps ) ) {
			foreach ( $caps as $cap ) {
				if ( empty( $mainwp_user->capabilities[ $cap_type ][ $cap ] ) ) {
					return false;
				}
			}
		} else {
			if ( empty( $mainwp_user->capabilities[ $cap_type ][ $caps ] ) ) {

				// to fix checking cap site in groups
				if ( $cap_type == 'site' ) {
					if ( in_array( $caps, $mainwp_user->capabilities['siteids_in_allowed_groups'] ) ) {
						return true;
					}
				}

				return false;
			}
		}
		return true;
	}

	static function get_current_mainwp_user( $force_load = false ) {
		global $mainwp_current_user;
		$current_user = wp_get_current_user();
		if ( $force_load || ! isset( $mainwp_current_user ) || $mainwp_current_user->ID != $current_user->ID ) {
			$mainwp_current_user               = new stdClass();
			$mainwp_current_user->ID           = $current_user->ID;
			$mainwp_current_user->roles        = $current_user->roles;
			$mainwp_current_user->capabilities = self::get_capabilities_for_role( $current_user->roles );
		}
		return $mainwp_current_user;
	}

	static function get_capabilities_for_role( $roles ) {
		$caps = array();
		if ( ! empty( $roles ) ) {
			$role_name = '';
			if ( is_array( $roles ) ) {
				foreach ( $roles as $role ) {
					if ( strpos( $role, 'mainwp_' ) === 0 ) {
						$role_name = $role;
						break;
					}
				}
			} else {
				$role_name = $roles;
			}
			if ( ! empty( $role_name ) ) {
				$caps = MainWP_Team_Control_DB::get_instance()->get_caps_by_role( $role_name );
				if ( isset( $caps['group'] ) ) {
					$allowed_groups = array_keys( $caps['group'] );
					$siteids        = array();
					if ( is_array( $allowed_groups ) && ! empty( $allowed_groups ) ) {
						$websites = apply_filters( 'mainwp_getwebsitesbygroupids', $allowed_groups );
						if ( is_array( $websites ) ) {
							foreach ( $websites as  $website ) {
								$_id = $website->id;
								if ( ! in_array( $_id, $siteids ) ) {
									$siteids[] = $_id; }
							}
							$caps['siteids_in_allowed_groups'] = $siteids;
						}
					}
				}
			}
		}
		return $caps;
	}

	public static function team_control_set_current_user() {
		self::get_current_mainwp_user( true );
	}

	public function add_new_role( $user_role_name, $user_role_id ) {
		global $wp_roles;
		$return = array();
		if ( empty( $user_role_name ) || empty( $user_role_id ) ) {
			$return['error'] = __( 'Role name or role ID is empty.', 'mainwp-team-control' );
			return $return;
		}

		// sanitize user input for security
		$valid_name = preg_match( '/[A-Za-z0-9_\-]*/', $user_role_id, $match );

		if ( ! $valid_name || ( $valid_name && ( $match[0] != $user_role_id ) ) ) { // some non-alphanumeric charactes found!
			$return['error'] = esc_html__( 'Role ID must contain latin characters, digits, hyphens or underscore only! Special charactes are not allowed.', 'mainwp-team-control' );
			return $return;
		}

		$numeric_name = preg_match( '/[0-9]*/', $user_role_id, $match );

		if ( $numeric_name && ( $match[0] == $user_role_id ) ) { // numeric name discovered
			$return['error'] = esc_html__( 'WordPress does not support numeric Role name (ID). Use latin characters.', 'mainwp-team-control' );
			return $return;
		}

		if ( $user_role_id ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}

			if ( isset( $wp_roles->roles[ $user_role_id ] ) ) {
				$return['error'] = sprintf( esc_html__( '%s role already exists.', 'mainwp-team-control' ), $user_role_id );
				return $return;
			}

			// init WP capabilities
			$wp_capabilities = $this->get_default_wp_capabilities_for_role( $user_role_id );
			// add new role to the roles array
			$result = add_role( $user_role_id, $user_role_name, $wp_capabilities );
			if ( ! isset( $result ) || empty( $result ) ) {
				$return['error'] = 'Error: ' . esc_html__( 'Error encountered during the process of new role creation. Please try again.', 'mainwp-team-control' );
			} else {
				$return['message'] = sprintf( esc_html__( '%s role created successfully.', 'mainwp-team-control' ), $user_role_name );
				$return['success'] = 1;
			}
		}
		return $return;
	}

	public function rename_role( $user_role_id, $new_role_name ) {
		global $wp_roles;
		$return = array();
		if ( $user_role_id ) {
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}

			if ( isset( $wp_roles->roles[ $user_role_id ] ) ) {
				$wp_roles->roles[ $user_role_id ]['name'] = $new_role_name;
				update_option( $wp_roles->role_key, $wp_roles->roles );
				$return['success'] = 1;
				return $return;
			} else {
				$return['error'] = esc_html__( 'Role could not be found.', 'mainwp-team-control' ); }
		}
		return $return;
	}

	public function check_mainwp_roles() {
		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$mainwp_created_roles = array();

		if ( is_object( $wp_roles ) && property_exists( $wp_roles, 'roles' ) ) {
			foreach ( $wp_roles->roles as $role_id => $role ) {
				if ( strpos( $role_id, 'mainwp' ) === 0 ) {
					$mainwp_created_roles[ $role_id ] = $role;
				}
			}
		}

		$current_roles = MainWP_Team_Control_DB::get_instance()->get_roles();

		$current_roles_ids = array();
		if ( is_array( $current_roles ) && ! empty( $current_roles ) ) {
			foreach ( $current_roles as $role ) {
				$current_roles_ids[] = $role->role_name_id;
			}
		}

		$default_caps = self::get_instance()->get_default_mainwp_capabilities_for_role();

		foreach ( $mainwp_created_roles as $role_id => $role ) {
			if ( ! isset( $current_roles_ids[ $role_id ] ) ) {
				// add into role table
				$new = array(
					'role_name'         => $role['name'],
					'role_description'  => $role['name'] . ' (NEW)',
					'type'              => 0,
					'role_name_id'      => $role_id,
					'role_capabilities' => base64_encode( serialize( array() ) ),
				);
				if ( $role = MainWP_Team_Control_DB::get_instance()->add_role( $new ) ) {
					// ok
				}
			}
		}

	}

	public function get_default_wp_capabilities_for_role( $role ) {
		$capabilities = array(
			'edit_post'          => true,
			'edit_posts'         => true,
			'edit_others_posts'  => true,
			'publish_posts'      => true,
			'read_post'          => true,
			'read_private_posts' => true,
			'delete_post'        => true,
			'level_10'           => true,
		);

		$wp_role = get_role( 'administrator' );

		if ( $wp_role ) {
			$wp_caps = $wp_role->capabilities;
		}

		return array_merge( $capabilities, $wp_caps );
	}

	public function get_capabilities( $grouped_caps = false ) {
		$dashboard_caps_grouped = array(
			'sites'      => array(
				'title' => __( 'Sites', 'mainwp-team-control' ),
				'caps'  => array(
					'add_sites'                     => __( 'Add Sites', 'mainwp-team-control' ),
					'edit_sites'                    => __( 'Edit Sites', 'mainwp-team-control' ),
					'delete_sites'                  => __( 'Delete Sites', 'mainwp-team-control' ),
					'access_individual_dashboard'   => __( 'Access Site Overview', 'mainwp-team-control' ),
					'access_wpadmin_on_child_sites' => __( 'Access WP Admin', 'mainwp-team-control' ),
					'manage_security_issues'        => __( 'Manage Security Issues', 'mainwp-team-control' ),
					'test_connection'               => __( 'Test Connection', 'mainwp-team-control' ),
				),
			),

			'backups'    => array(
				'title' => __( 'Backups', 'mainwp-team-control' ),
				'caps'  => array(
					'execute_backups'           => __( 'Execute Backups', 'mainwp-team-control' ),
					'restore_backups'           => __( 'Restore Backups', 'mainwp-team-control' ),
					'download_backups'          => __( 'Download Backups', 'mainwp-team-control' ),
					'add_backup_tasks'          => __( 'Add Backup Tasks', 'mainwp-team-control' ),
					'edit_backup_tasks'         => __( 'Edit Backup Tasks', 'mainwp-team-control' ),
					'delete_backup_tasks'       => __( 'Delete Backup Tasks', 'mainwp-team-control' ),
					'pause_resume_backup_tasks' => __( 'Pause/Resume Backup Tasks', 'mainwp-team-control' ),
					'run_backup_tasks'          => __( 'Run Backup Tasks', 'mainwp-team-control' ),
				),
			),

			'plugins'    => array(
				'title' => __( 'Plugins', 'mainwp-team-control' ),
				'caps'  => array(
					'install_plugins'             => __( 'Install Plugins', 'mainwp-team-control' ),
					'delete_plugins'              => __( 'Delete Plugins', 'mainwp-team-control' ),
					'activate_deactivate_plugins' => __( 'Activate/Deactivate Plugins', 'mainwp-team-control' ),
				),
			),

			'themes'     => array(
				'title' => __( 'Themes', 'mainwp-team-control' ),
				'caps'  => array(
					'install_themes'  => __( 'Install Themes', 'mainwp-team-control' ),
					'delete_themes'   => __( 'Delete Themes', 'mainwp-team-control' ),
					'activate_themes' => __( 'Activate Themes', 'mainwp-team-control' ),
				),
			),

			'updates'    => array(
				'title' => __( 'Updates', 'mainwp-team-control' ),
				'caps'  => array(
					'update_wordpress'        => __( 'Update WordPress', 'mainwp-team-control' ),
					'update_plugins'          => __( 'Update Plugins', 'mainwp-team-control' ),
					'update_themes'           => __( 'Update Themes', 'mainwp-team-control' ),
					'update_translations'     => __( 'Update Translations', 'mainwp-team-control' ),
					'ignore_unignore_updates' => __( 'Ignore/Unignore Updates', 'mainwp-team-control' ),
					'trust_untrust_updates'   => __( 'Trust/Untrust Updates', 'mainwp-team-control' ),
				),
			),

			'content'    => array(
				'title' => __( 'Content', 'mainwp-team-control' ),
				'caps'  => array(
					'manage_posts'      => __( 'Manage Posts', 'mainwp-team-control' ),
					'manage_pages'      => __( 'Manage Pages', 'mainwp-team-control' ),
					'manage_users'      => __( 'Manage Users', 'mainwp-team-control' ),
					'manage_groups'     => __( 'Manage Groups', 'mainwp-team-control' ),
					'manage_extensions' => __( 'Manage Extensions', 'mainwp-team-control' ),
				),
			),

			'access'     => array(
				'title' => __( 'Access', 'mainwp-team-control' ),
				'caps'  => array(
					'manage_dashboard_settings' => __( 'See Settings', 'mainwp-team-control' ),
					'access_global_dashboard'   => __( 'See Overview', 'mainwp-team-control' ),
					'see_server_information'    => __( 'See Status', 'mainwp-team-control' ),
				),
			),

			'extensions' => array(
				'title' => __( 'Extensions', 'mainwp-team-control' ),
				'caps'  => array(
					'bulk_install_and_activate_extensions' => __( 'Bulk Install and Activate Extensions', 'mainwp-team-control' ),
				),
			),
		);

		if ( $grouped_caps ) {
			$caps['dashboard_capabilities_grouped'] = $dashboard_caps_grouped;
		} else {
			foreach ( $dashboard_caps_grouped as $caps_group ) {
				foreach ( $caps_group['caps'] as $index => $name ) {
					$caps['dashboard_capabilities'][ $index ] = $name;
				}
			}
		}

		$extensions = apply_filters( 'mainwp_manager_getextensions', array() );
		if ( is_array( $extensions ) ) {
			foreach ( $extensions as $ext ) {
				$caps['extension_capabilities'][ dirname( $ext['slug'] ) ] = $ext['name'];
			}
		}

		global $mainWPTeamControlExtensionActivator;
		$websites = apply_filters( 'mainwp_getsites', $mainWPTeamControlExtensionActivator->get_child_file(), $mainWPTeamControlExtensionActivator->get_child_key(), null, true );

		foreach ( $websites as $site ) {
			$caps['site_capabilities'][] = array(
				'id'   => $site['id'],
				'name' => $site['name'],
				'url'  => $site['url'],
			);
		}

		$groups = apply_filters( 'mainwp_getgroups', $mainWPTeamControlExtensionActivator->get_child_file(), $mainWPTeamControlExtensionActivator->get_child_key(), null, true );
		foreach ( $groups as $group ) {
			$caps['group_capabilities'][] = array(
				'id'   => $group['id'],
				'name' => $group['name'],
			);
		}
		return $caps;
	}

	protected function get_all_inner_capabilities( $force_reload = false, $excluded_group = false ) {
		if ( ! $force_reload && $this->inner_capabilities !== null ) {
			return $this->inner_capabilities;
		}

		$all_caps       = $this->get_capabilities();
		$dashboard_caps = $all_caps['dashboard_capabilities'];
		$extension_caps = isset( $all_caps['extension_capabilities'] ) ? $all_caps['extension_capabilities'] : array();
		$websites       = isset( $all_caps['site_capabilities'] ) ? $all_caps['site_capabilities'] : array();
		$groups         = isset( $all_caps['group_capabilities'] ) ? $all_caps['group_capabilities'] : array();

		$inner_dashboard_caps = $inner_extension_caps = $inner_sites_caps = $inner_groups_caps = array();

		foreach ( $dashboard_caps as $cap => $name ) {
			$inner_dashboard_caps[ $cap ] = array(
				'name'   => $name,
				'cap_id' => $cap,
			);
		}

		foreach ( $extension_caps as $cap => $name ) {
			$inner_extension_caps[ $cap ] = array(
				'name'   => $name,
				'cap_id' => $cap,
			);
		}

		foreach ( $websites as $site ) {
			$cap                      = $site['id'];
			$inner_sites_caps[ $cap ] = array(
				'name'   => $site['name'],
				'cap_id' => $cap,
			);
		}

		if ( ! $excluded_group ) {
			foreach ( $groups as $group ) {
				$cap                       = $group['id'];
				$inner_groups_caps[ $cap ] = array(
					'name'   => $group['name'],
					'cap_id' => $cap,
				);
			}
		}

		$all_inner_caps = array(
			'dashboard' => $inner_dashboard_caps,
			'extension' => $inner_extension_caps,
			'site'      => $inner_sites_caps,
			'group'     => $inner_groups_caps,
		);

		$this->inner_capabilities = $all_inner_caps;
		return $all_inner_caps;
	}

	public function get_default_mainwp_capabilities_for_role( $role = '' ) {
		$all_inner_caps = $this->get_all_inner_capabilities( false, true );
		if ( ! is_array( $all_inner_caps ) ) {
			$all_inner_caps = array();
		}

		$not_allowed = array(
			'extension' => array(
				'mainwp-team-control' => 1,
			),
		);

		foreach ( $not_allowed as $cap_type => $not_allowed_caps ) {
			foreach ( $not_allowed_caps as $cap => $val ) {
				unset( $all_inner_caps[ $cap_type ][ $cap ] );
			}
		}

		return $all_inner_caps;
	}

	public function prepare_capabilities_to_save() {

		$inner_caps             = $this->get_all_inner_capabilities();
		$dashboard_caps_to_save = $extensions_caps_to_save = $sites_caps_to_save = $groups_caps_to_save = array();

		if ( isset( $_POST['dashboard_caps'] ) && is_array( $_POST['dashboard_caps'] ) ) {
			foreach ( $inner_caps['dashboard'] as $cap ) {
				$cap_id = $cap['cap_id'];
				if ( in_array( $cap_id, $_POST['dashboard_caps'] ) ) {
					$dashboard_caps_to_save[ $cap_id ] = 1;
				}
			}
		}

		if ( isset( $_POST['extensions_caps'] ) && is_array( $_POST['extensions_caps'] ) ) {
			foreach ( $inner_caps['extension'] as $cap ) {
				$cap_id = $cap['cap_id'];
				if ( in_array( $cap_id, $_POST['extensions_caps'] ) ) {
					$extensions_caps_to_save[ $cap_id ] = 1;
				}
			}
		}

		if ( isset( $_POST['sites_caps'] ) && is_array( $_POST['sites_caps'] ) ) {
			foreach ( $inner_caps['site'] as $cap ) {
				$cap_id = $cap['cap_id'];
				if ( in_array( $cap_id, $_POST['sites_caps'] ) ) {
					$sites_caps_to_save[ $cap_id ] = 1;
				}
			}
		}

		if ( isset( $_POST['groups_caps'] ) && is_array( $_POST['groups_caps'] ) ) {
			foreach ( $inner_caps['group'] as $cap ) {
				$cap_id = $cap['cap_id'];
				if ( in_array( $cap_id, $_POST['groups_caps'] ) ) {
					$groups_caps_to_save[ $cap_id ] = 1;
				}
			}
		}

		$caps_to_save = array(
			'dashboard' => $dashboard_caps_to_save,
			'extension' => $extensions_caps_to_save,
			'site'      => $sites_caps_to_save,
			'group'     => $groups_caps_to_save,
		);

		return $caps_to_save;
	}

	public function delete_wp_roles( $roles_to_del ) {
		global $wp_roles;

		if ( ! is_array( $roles_to_del ) || count( $roles_to_del ) == 0 ) {
			return false;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$result = false;

		foreach ( $roles_to_del as $role_id ) {
			if ( ! isset( $wp_roles->roles[ $role_id ] ) ) {
				$result = false;
				break;
			}

			unset( $wp_roles->role_objects[ $role_id ] );
			unset( $wp_roles->role_names[ $role_id ] );
			unset( $wp_roles->roles[ $role_id ] );

			$result = true;
		}

		if ( $result ) {
			update_option( $wp_roles->role_key, $wp_roles->roles );
		}

		return $result;
	}
}
