<?php
class MainWP_Team_Control {

	public function __construct() {

	}

	public function admin_init() {
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-team-control' ) ) {
			return;
		}

		add_action( 'wp_ajax_mainwp_team_control_load_role_caps', array( &$this, 'ajax_load_role_caps' ) );
		add_action( 'wp_ajax_mainwp_team_control_delete_role', array( &$this, 'ajax_delete_role' ) );
		add_action( 'wp_ajax_mainwp_team_control_save_role', array( &$this, 'ajax_save_role' ) );
		add_action( 'wp_ajax_mainwp_team_control_users_search', array( &$this, 'ajax_users_search' ) );
		add_action( 'wp_ajax_mainwp_team_control_user_change_role', array( &$this, 'ajax_user_change_role' ) );
		add_action( 'wp_ajax_mainwp_team_control_user_delete', array( &$this, 'ajax_user_delete' ) );
		add_action( 'wp_ajax_mainwp_team_control_user_notes_save', array( &$this, 'ajax_user_notes_save' ) );
		add_action( 'wp_ajax_mainwp_team_control_delete_role_load_users', array( &$this, 'ajax_delete_role_load_users' ) );
	}

	public function render() {
		$this->render_tabs();
	}

	public function render_tabs() {
		global $current_user;
		MainWP_Team_Control_Role::get_instance()->check_mainwp_roles();
		$mainwp_roles = MainWP_Team_Control_DB::get_instance()->get_roles();

		if ( ! is_array( $mainwp_roles ) ) {
			$mainwp_roles = array();
		}

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'users' ) {
				$current_tab = 'users';
			} elseif ( $_GET['tab'] == 'roles' ) {
				$current_tab = 'roles';
			}
		} else {
			$current_tab = 'users';
		}

		?>

		<div id="mainwp-team-control">
		  <div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-team-control-menu">
			<a href="admin.php?page=Extensions-Mainwp-Team-Control&tab=users" class="item <?php echo ( $current_tab == 'users' ? 'active' : '' ); ?>"><i class="user icon"></i> <?php _e( 'Manage Dashboard Users', 'mainwp-team-control' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Team-Control&tab=roles" class="item <?php echo ( $current_tab == 'roles' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Roles and Permissions', 'mainwp-team-control' ); ?></a>
		  </div>
			<?php if ( $current_tab == 'users' || $current_tab == '' ) : ?>
				<div class="ui alt segment" id="mainwp-team-control-users-tab">
					<?php $this->gen_users_tab( $mainwp_roles ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $current_tab == 'roles' ) : ?>
				<div class="ui segment" id="mainwp-teram-control-roles-tab">
					<?php $this->gen_roles_tab( $mainwp_roles ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function gen_users_tab( $roles ) {
		global $wp_version;
		?>
		<div class="mainwp-main-content">
			<div class="mainwp-actions-bar">
		<div class="ui grid">
		  <div class="ui two column row">
			<div class="column">
							<select class="ui dropdown" id="mainwp-team-control-bulk-actions">
								<option value="none"><?php _e( 'Bulk actions', 'mainwp-team-control' ); ?></option>
								<option value="delete"><?php _e( 'Delete', 'mainwp-team-control' ); ?></option>
							</select> <input type="button" id="mainwp-team-control-bulk-actions-button" class="ui button" value="<?php _e( 'Apply', 'mainwp-team-control' ); ?>"/>
						</div>
			<div class="right aligned column">
							<select class="ui dropdown" id="mainwp-team-control-change-users-role">
								<option value="none"><?php _e( 'Change role', 'mainwp-team-control' ); ?></option>
								<option value="administrator"> <?php _e( 'Administrator', 'mainwp-team-control' ); ?></option>
								<option value="editor"> <?php _e( 'Editor', 'mainwp-team-control' ); ?></option>
								<option value="author"> <?php _e( 'Author', 'mainwp-team-control' ); ?></option>
								<option value="contributor"> <?php _e( 'Contributor', 'mainwp-team-control' ); ?></option>
								<option value="subscriber"> <?php _e( 'Subscriber', 'mainwp-team-control' ); ?></option>
								<?php foreach ( $roles as $role ) : ?>
								<option value="<?php echo $role->role_name_id; ?>"> <?php echo $role->role_name; ?></option>
								<?php endforeach; ?>
							</select> <input type="button" id="mainwp-team-control-change-users-role-button" class="ui button" value="<?php _e( 'Change Role', 'mainwp-team-control' ); ?>"/>
						</div>
		  </div>
		</div>
	  </div>
			<div class="ui segment">
				<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
				<table class="ui tablet stackable single line table" id="mainwp-team-control-users-table">
					<thead>
						<tr>
							<th class="no-sort collapsing"><span class="ui checkbox"><input type="checkbox"></span></th>
							<th><?php echo __( 'Username', 'mainwp-team-control' ); ?></th>
							<th><?php echo __( 'Name', 'mainwp-team-control' ); ?></th>
							<th><?php echo __( 'Role', 'mainwp-team-control' ); ?></th>
							<th><?php echo __( 'E-mail', 'mainwp-team-control' ); ?></th>
							<th class="no-sort collapsing"><i class="sticky note icon"></i></th>
							<th class="no-sort collapsing"><?php echo __( '', 'mainwp-team-control' ); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
					<tfoot class="full-width">
						<tr>
							<th></th>
							<th colspan="6">
								<a href="user-new.php" class="ui green button"><?php echo __( 'Create New User', 'mainwp-team-control' ); ?></a>
							</th>
						</tr>
					</tfoot>
				</table>
				<div class="ui inverted dimmer">
				<div class="ui text loader"><?php echo __( 'Loading users', 'mainwp-team-control' ); ?></div>
			  </div>
			</div>
		</div>
		<div id="mainwp-notes" class="ui modal">
		  <div class="header"><?php esc_html_e( 'Notes', 'mainwp' ); ?></div>
		  <div class="content" id="mainwp-notes-content">
				<div id="mainwp-notes-html"></div>
				<div id="mainwp-notes-editor" class="ui form" style="display:none;">
					<div class="field">
						<label><?php esc_html_e( 'Edit note', 'mainwp' ); ?></label>
						<textarea id="mainwp-notes-note"></textarea>
					</div>
					<div><?php _e( 'Allowed HTML tags:', 'mainwp' ); ?> &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, &lt;hr&gt;, &lt;a&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;h1&gt;, &lt;h2&gt; </div>
				</div>
		  </div>
			<div class="actions">
				<div class="ui grid">
				  <div class="eight wide left aligned middle aligned column">
						<div id="mainwp-notes-status" class="left aligned"></div>
					</div>
				  <div class="eight wide column">
						<form>
							<input type="button" class="ui green button" id="mainwp-team-notes-save" value="<?php esc_attr_e( 'Save note', 'mainwp' ); ?>" style="display:none;"/>
							<input type="button" class="ui basic green button" id="mainwp-team-notes-edit" value="<?php esc_attr_e( 'Edit', 'mainwp' ); ?>"/>
							<input type="button" class="ui red button" id="mainwp-team-notes-cancel" value="<?php esc_attr_e( 'Close', 'mainwp' ); ?>"/>
							<input type="hidden" id="mainwp-notes-userid" value=""/>
							<input type="hidden" id="mainwp-notes-slug" value=""/>
			</form>
					</div>
				</div>
		  </div>
		</div>
		<div class="mainwp-side-content mainwp-no-padding">
			<div class="ui hidden divider"></div>
			<div class="mainwp-search-options">
				<div class="ui header"><?php echo __( 'Search Options', 'mainwp-team-control' ); ?></div>
				<div class="ui mini form">
					<div class="field">
						<select class="ui fluid search dropdown" multiple="" id="mainwp-team-control-role-selection" name="mainwp-team-control-role-selection">
							<option value=""><?php echo __( 'Select role', 'mainwp-team-control' ); ?></option>
							<option value="administrator"><?php echo __( 'Administrator', 'mainwp-team-control' ); ?></option>
							<option value="editor"><?php echo __( 'Editor', 'mainwp-team-control' ); ?></option>
							<option value="author"><?php echo __( 'Author', 'mainwp-team-control' ); ?></option>
							<option value="contributor"><?php echo __( 'Contributor', 'mainwp-team-control' ); ?></option>
							<option value="subscriber"><?php echo __( 'Subscriber', 'mainwp-team-control' ); ?></option>
							<?php foreach ( $roles as $role ) : ?>
							<option value="<?php echo $role->role_name_id; ?>"><?php echo $role->role_name; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
			<div class="ui divider"></div>
			<div class="mainwp-search-submit">
				<input type="button" name="mainwp_team_control_show_users" id="mainwp_team_control_show_users" class="ui big green fluid button" value="<?php _e( 'Show Users', 'mainwp-team-control' ); ?>"/>
			</div>
		</div>
		<div class="ui clearing hidden divider"></div>
		<?php
	}

	function ajax_users_search() {
		$role   = isset( $_POST['role'] ) ? $_POST['role'] : '';
		$result = $this->render_table( $role );
		exit( json_encode( $result ) );
	}

	public function render_table( $role ) {
		$data   = $this->get_all_users( $role );
		$users  = $data['search_users'];
		$result = $this->users_search_renderer( $data );

		if ( count( $users ) == 0 ) {
			ob_start();
			?>
	  <tr>
		<td colspan="7"><?php echo __( 'No users found for the selected search parameters.', 'mainwp-team-control' ); ?></td>
	  </tr>
			<?php
			$newOutput            = ob_get_clean();
			$result['table_html'] = $newOutput;
		}
		$result['count'] = count( $users );
		return $result;
	}

	private function get_role_names( $roles, $mainwp_roles = array(), $withlink = true ) {
		if ( ! is_array( $mainwp_roles ) ) {
			$mainwp_roles = array();
		}

		if ( is_array( $roles ) ) {
			$wp_roles_name = array( 'subscriber', 'administrator', 'editor', 'author', 'contributor' );
			$ret           = '';
			foreach ( $roles as $role ) {
				if ( in_array( $role, $wp_roles_name ) ) {
					$ret .= ucfirst( $role ) . ', '; } elseif ( isset( $mainwp_roles[ $role ] ) ) {
					if ( $withlink ) {
						$ret .= '<a href="admin.php?page=Extensions-Mainwp-Team-Control&tab=roles&roleid=' . $mainwp_roles[ $role ]['id'] . '">' . $mainwp_roles[ $role ]['name'] . '</a>, ';
					} else {
						$ret .= $mainwp_roles[ $role ]['name'] . ', '; }
					} else {
						$ret .= ucfirst( $role ) . ', ';
					}
			}
			$ret = rtrim( $ret, ', ' );
			if ( '' == $ret ) {
				$ret = 'None';
			}
			return $ret;
		} elseif ( empty( $roles ) ) {
			$ret = 'None';
		} elseif ( isset( $mainwp_roles[ $roles ] ) ) {
			if ( $withlink ) {
				 $ret = '<a href="admin.php?page=Extensions-Mainwp-Team-Control&tab=roles&roleid=' . $mainwp_roles[ $roles ]['id'] . '">' . $mainwp_roles[ $roles ]['name'] . '</a>';
			} else {
				$ret = $mainwp_roles[ $roles ]['name']; }
		} else {
			$ret = ucfirst( $roles );
		}

		return $ret;
	}

	protected function users_search_renderer( $data ) {
		$users        = isset( $data['search_users'] ) ? $data['search_users'] : array();
		$all_users    = isset( $data['all_users'] ) ? $data['all_users'] : array();
		$mainwp_roles = MainWP_Team_Control_DB::get_instance()->get_roles();
		$roles_names  = array();

		foreach ( $mainwp_roles as $mr ) {
			$roles_names[ $mr->role_name_id ] = array(
				'name' => $mr->role_name,
				'id'   => $mr->id,
			);
		}

		$current_user_id = get_current_user_id();
		$return          = array( 'table_html' => '' );

		foreach ( $users as $user ) {
			$role_name = $this->get_role_names( $user['role'], $roles_names );
			ob_start();
			?>
	  <tr user-id="<?php echo $user['id']; ?>" user-login="<?php echo $user['login']; ?>" current-role="<?php echo $user['role']; ?>">
		<td><span class="ui checkbox"><input type="checkbox" name="user[]" value="1"></span></td>
		<td><input class="userId" type="hidden" name="id" value="<?php echo $user['id']; ?>"/><?php echo $user['login']; ?></td>
				<td><?php echo $user['display_name']; ?></td>
		<td><?php echo $role_name; ?></td>
		<td><a href="mailto:<?php echo $user['email']; ?>"><?php echo $user['email']; ?></a></td>
		<td class="center aligned">
					<?php
					$user_note        = get_user_option( '_mainwp_team_control_user_notes', $user['id'] );
							$esc_note = $this->esc_content( $user_note );
					?>
					<?php if ( $user_note == '' ) : ?>
					<a href="#" id="mainwp-user-notes-<?php echo $user['id']; ?>" class="mainwp-edit-user-note"><i class="sticky note outline icon"></i></a>
					<?php else : ?>
					<a href="#" id="mainwp-user-notes-<?php echo $user['id']; ?>" class="mainwp-edit-user-note"><i class="sticky note icon"></i></a>
					<?php endif; ?>
					<span style="display:none" id="mainwp-user-notes-<?php echo $user['id']; ?>-note"><?php echo $esc_note; ?></span>
				</td>
				<td>
					<div class="ui dropdown">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="user-edit.php?user_id=<?php echo $user['id']; ?>"><?php _e( 'Edit', 'mainwp' ); ?></a>
							<?php if ( $user['id'] != $current_user_id ) : ?>
							<a class="item team_control_user_submitdelete" href="#"><?php _e( 'Delete', 'mainwp' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</td>
	  </tr>
			<?php
			$newOutput = ob_get_clean();
			if ( ! empty( $newOutput ) ) {
				$return['table_html'] .= $newOutput;
			}
		}
		return $return;
	}

	function get_all_users( $str_roles, $search = '' ) {
		$searchusers = $allusers = array();
		$new_users   = get_users();

		foreach ( $new_users as $new_user ) {
			$allusers[] = $this->map_user_data( $new_user );
		}

		if ( empty( $search ) ) {
			if ( empty( $str_roles ) ) {
				$searchusers = $allusers;
			} elseif ( 'none' == $str_roles ) {
				foreach ( $allusers as $user ) {
					if ( 'none' == $user['role'] ) {
						$searchusers[] = $user;
					}
				}
			} else {
				$roles = explode( ',', $str_roles );
				if ( is_array( $roles ) ) {
					foreach ( $roles as $role ) {
						$new_users = get_users( 'role=' . $role );
						foreach ( $new_users as $new_user ) {
							$searchusers[] = $this->map_user_data( $new_user );
						}
					}
				}
			}
		} else {
			$new_users = get_users(
				array(
					'blog_id'        => false,
					'search'         => '*' . $search . '*',
					'search_columns' => array( 'user_login', 'user_nicename', 'user_email' ),
				)
			);
			foreach ( $new_users as $new_user ) {
				$searchusers[] = $this->map_user_data( $new_user );
			}
		}
		return array(
			'all_users'    => $allusers,
			'search_users' => $searchusers,
		);
	}

	function map_user_data( $new_user ) {
		$usr                 = array();
		$usr['id']           = $new_user->ID;
		$usr['login']        = $new_user->user_login;
		$usr['nicename']     = $new_user->user_nicename;
		$usr['email']        = $new_user->user_email;
		$usr['registered']   = $new_user->user_registered;
		$usr['status']       = $new_user->user_status;
		$usr['display_name'] = $new_user->display_name;
		if ( empty( $new_user->roles ) ) {
			$role = 'none';
		} else {
			$role = implode( ',', $new_user->roles );
		}
		$usr['role']       = $role;
		$usr['post_count'] = count_user_posts( $new_user->ID );
		$usr['avatar']     = get_avatar( $new_user->ID, 32 );
		return $usr;
	}

	function gen_roles_tab( $roles ) {
		?>
		<table class="ui tablet stackable single line table" id="mainwp-team-control-roles-table">
			<thead>
				<tr>
					<th><?php echo __( 'Role', 'mainwp-team-control' ); ?></th>
					<th><?php echo __( 'Role Description', 'mainwp-team-control' ); ?></th>
					<th class="collapsing"></th>
				</tr>
			</thead>
			<tbody>
			<?php
			if ( is_array( $roles ) && count( $roles ) > 0 ) {
				foreach ( (array) $roles as $role ) {
					if ( ! $role ) {
						continue;
					}
					echo $this->create_role_item( $role, true );
				}
			} else {
				echo '<tr><td colspan="3">' . __( 'No roles created. Please create roles.', 'mainwp-team-control' ) . '</td></tr>';
			}
			?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="3"><a id="mainwp-team-control-create-role" class="ui green right floated button"><?php echo __( 'Create Custom Role', 'mainwp-team-control' ); ?></a></th>
				</tr>
			</tfoot>
		</table>
		<div class="ui modal" id="mainwp-team-control-create-role-modal">
			<div class="header"><?php echo __( 'Create Role', 'mainwp-team-control' ); ?></div>
			<div class="content">
				<div class="ui message red" style="display:none"></div>
				<div class="ui form">
					<div class="field">
						<label><?php echo __( 'Enter role name', 'mainwp-team-control' ); ?></label>
						<input type="text" value="" id="mainwp-team-control-role-name" name="mainwp-team-control-role-name">
					</div>
					<div class="field">
						<label><?php echo __( 'Enter role description', 'mainwp-team-control' ); ?></label>
						<input type="text" value="" id="mainwp-team-control-role-description" name="mainwp-team-control-role-description">
					</div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-team-control' ); ?></div>
				<a href="#" id="mainwp-team-control-create-role-button" class="ui green button"><?php echo __( 'Create Role', 'mainwp-team-control' ); ?></a>
			</div>
		</div>

		<div class="ui small modal" id="mainwp-team-control-edit-role-modal">
			<div class="header"><?php echo __( 'Edit Role / Manage Permissions', 'mainwp-team-control' ); ?></div>
			<div class="scrolling content">
				<div id="mainwp-team-control-modal-message-zone" class="ui red message" style="display:none"></div>
				<h2 class="ui dividing header"><?php echo __( 'Role Info', 'mainwp-team-control' ); ?></h2>
				<div class="ui form">
					<div class="field">
						<label><?php echo __( 'Enter role name', 'mainwp-team-control' ); ?></label>
						<input type="text" value="" id="mainwp-team-control-role-name" name="mainwp-team-control-role-name">
					</div>
					<div class="field">
						<label><?php echo __( 'Enter role description', 'mainwp-team-control' ); ?></label>
						<input type="text" value="" id="mainwp-team-control-role-description" name="mainwp-team-control-role-description">
					</div>
				</div>
				<div id="mainwp-team-control-permissions-form" current-roleid="0"></div>
				<div class="ui inverted dimmer">
				<div class="ui text loader"><?php echo __( 'Loading permissions...', 'mainwp-team-control' ); ?></div>
			  </div>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-team-control' ); ?></div>
				<a href="#" id="mainwp-team-control-update-role-button" class="ui green button"><?php echo __( 'Update Role', 'mainwp-team-control' ); ?></a>
			</div>
		</div>

		<div class="ui modal"	id="mainwp-team-control-delete-role-modal">
			<div class="header"><?php echo __( 'Delete Role', 'mainwp-team-control' ); ?></div>
			<div class="content" role-id="0">
				<div class="ui inverted dimmer">
				<div class="ui text loader"><?php echo __( 'Loading users...', 'mainwp-team-control' ); ?></div>
			  </div>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-team-control' ); ?></div>
				<input type="button" class="ui green button" disabled="disabled" value="<?php _e( 'Delete Role' ); ?>" id="mainwp-team-control-confirm-delete-role" />
			</div>
		</div>

		<?php
		$role_id = isset( $_GET['roleid'] ) ? $_GET['roleid'] : 0;
		if ( $role_id ) {
			?>
		<script type="text/javascript">
		jQuery( document ).ready( function() {
		  jQuery( 'tr[role-id=<?php echo $role_id; ?>]' ).find( '#mainwp-team-control-edit-role' ).click();
		} );
		</script>
			<?php
		}
	}

	function ajax_load_role_caps() {
		$role_id = isset( $_POST['roleId'] ) ? $_POST['roleId'] : 0;
		if ( empty( $role_id ) ) {
			exit( __( 'Undefined role ID.', 'mainwp-team-control' ) );
		}

		$role = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'id', $role_id );

		if ( empty( $role ) ) {
			exit( __( 'Role not found.', 'mainwp-team-control' ) );
		}

		global $mainWPTeamControlRole;

		$all_capabilities = $mainWPTeamControlRole->get_capabilities( true );
		$dashboard_caps   = $all_capabilities['dashboard_capabilities_grouped'];
		$extensions_caps  = isset( $all_capabilities['extension_capabilities'] ) ? $all_capabilities['extension_capabilities'] : array();
		$websites         = isset( $all_capabilities['site_capabilities'] ) ? $all_capabilities['site_capabilities'] : array();
		$groups           = isset( $all_capabilities['group_capabilities'] ) ? $all_capabilities['group_capabilities'] : array();
		$role_caps        = unserialize( base64_decode( $role->role_capabilities ) );

		if ( ! is_array( $role_caps ) ) {
			$role_caps = array(); }
		$role_dashboard_caps  = ( isset( $role_caps['dashboard'] ) && is_array( $role_caps['dashboard'] ) ) ? $role_caps['dashboard'] : array();
		$role_extensions_caps = ( isset( $role_caps['extension'] ) && is_array( $role_caps['extension'] ) ) ? $role_caps['extension'] : array();
		$role_sites_caps      = ( isset( $role_caps['site'] ) && is_array( $role_caps['site'] ) ) ? $role_caps['site'] : array();
		$role_groups_caps     = ( isset( $role_caps['group'] ) && is_array( $role_caps['group'] ) ) ? $role_caps['group'] : array();
		?>
		<div class="ui clearing hidden divider"></div>
	  <h2 class="ui left floated header">
		<?php echo __( 'Role Permissions', 'mainwp-team-control' ); ?>
	  </h2>
	  <h2 class="ui right floated header">
			<a href="#" class="ui mini button" id="mainwp-team-control-check-all-permissions"><?php echo __( 'Select All', 'mainwp-team-control' ); ?></a>
			<a href="#" class="ui mini button" id="mainwp-team-control-uncheck-all-permissions"><?php echo __( 'Select None', 'mainwp-team-control' ); ?></a>
	  </h2>
		<div class="ui clearing hidden divider"></div>
		<?php if ( is_array( $dashboard_caps ) && count( $dashboard_caps ) > 0 ) : ?>
		<div id="mainwp-team-control-dashboard-permissions">
			<?php foreach ( $dashboard_caps as $group_caps ) : ?>
				<h4 class="ui dividing header"><?php echo $group_caps['title']; ?></h4>
		  <div class="ui relaxed divided selection list">
				<?php foreach ( $group_caps['caps'] as $key => $value ) : ?>
						<?php $checked = isset( $role_dashboard_caps[ $key ] ) ? 'checked="checked"' : ''; ?>
			  <div class="item">
							<div class="ui checkbox">
					<input type="checkbox" <?php echo $checked; ?> value="1" id="mainwp_team_control_dashboard_<?php echo $key; ?>" capability="<?php echo $key; ?>">
					<label for="mainwp_team_control_dashboard_<?php echo $key; ?>"><?php echo stripslashes( $value ); ?></label>
							</div>
			  </div>
			<?php endforeach; ?>
		  </div>
		<?php endforeach; ?>
		</div>
		<?php else : ?>
			<div class="ui yellow message"><?php _e( 'Permissions not defined.', 'mainwp-team-control' ); ?></div>
	<?php endif; ?>
		<div class="ui clearing hidden divider"></div>
	  <h2 class="ui left floated header">
		<?php echo __( 'Allowed Extensions for the Role', 'mainwp-team-control' ); ?>
	  </h2>
	  <h2 class="ui right floated header">
			<a href="#" class="ui mini button" id="mainwp-team-control-check-all-extensions"><?php echo __( 'Select All', 'mainwp-team-control' ); ?></a>
			<a href="#" class="ui mini button" id="mainwp-team-control-uncheck-all-extensions"><?php echo __( 'Select None', 'mainwp-team-control' ); ?></a>
	  </h2>
		<div class="ui clearing hidden divider"></div>
		<?php if ( is_array( $extensions_caps ) && count( $extensions_caps ) > 0 ) : ?>
			<?php
			global $mainwp_current_user;
			$is_current_user_role = MainWP_Team_Control_Role::mainwp_roles_has( $mainwp_current_user->roles, $role->role_name_id );
			?>
	  <div id="mainwp-team-control-extensions-permissions" class="ui relaxed divided selection list">
				<?php
				foreach ( $extensions_caps as $key => $value ) {
					if ( 'mainwp-team-control' == $key ) {
						continue;
					}
					$checked = isset( $role_extensions_caps[ $key ] ) ? 'checked="checked"' : '';
					?>
					<div class="item">
						<div class="ui checkbox">
							<input type="checkbox" <?php echo $checked; ?> value="1" id="mainwp_team_control_extension_<?php echo $key; ?>" capability="<?php echo $key; ?>">
							<label for="mainwp_team_control_extension_<?php echo $key; ?>"><?php echo stripslashes( $value ); ?></label>
						</div>
					</div>
		  <?php } ?>
			</div>
		<?php else : ?>
			<div class="ui yellow message"><?php _e( 'Permissions not defined.', 'mainwp-team-control' ); ?></div>
	<?php endif; ?>

		<div class="ui clearing hidden divider"></div>
		<h2 class="ui left floated header">
			<?php echo __( 'Allowed Child Sites for the Role', 'mainwp-team-control' ); ?>
		</h2>
		<h2 class="ui right floated header active">
			<a href="#" class="ui mini button" id="mainwp-team-control-check-all-sites"><?php echo __( 'Select All', 'mainwp-team-control' ); ?></a>
			<a href="#" class="ui mini button" id="mainwp-team-control-uncheck-all-sites"><?php echo __( 'Select None', 'mainwp-team-control' ); ?></a>
		</h2>
		<div class="ui clearing hidden divider"></div>
		<div class="ui tabular top attached menu permissions">
			<a class="active item" data-tab="allowed-sites"><?php echo __( 'Select by Website', 'mainwp-team-control' ); ?></a>
			<a class="item" data-tab="allowed-groups"><?php echo __( 'Select by Group', 'mainwp-team-control' ); ?></a>
		</div>
		<div id="mainwp-team-control-sites-permissions" class="ui bottom attached tab active segment" data-tab="allowed-sites" style="border-left: 1px solid #d4d4d5; border-right: 1px solid #d4d4d5; border-bottom: 1px solid #d4d4d5;">
			<div class="ui fluid icon input">
				<input type="text" id="mainwp-sites-tc-filter" value="" placeholder="<?php esc_attr_e( 'Type to filter sites', 'mainwp-team-control' ); ?>" />
				<i class="filter icon"></i>
			</div>
			<?php if ( is_array( $websites ) && count( $websites ) > 0 ) : ?>
			<div class="ui relaxed divided selection list" id="mainwp-team-control-sites-list">
				<?php foreach ( $websites as $site ) : ?>
					<?php $checked = isset( $role_sites_caps[ $site['id'] ] ) ? 'checked="checked"' : ''; ?>
					<div class="item">
						<div class="ui checkbox">
							<input type="checkbox" <?php echo $checked; ?> value="1" id="mainwp_team_control_site_capabilities_<?php echo $site['id']; ?>"  capability="<?php echo $site['id']; ?>">
							<label for="mainwp_team_control_site_capabilities_<?php echo $site['id']; ?>" class="mainwp-item-name"><?php echo stripslashes( $site['name'] ); ?></label>
						</div>
			</div>
		  <?php endforeach; ?>
			</div>
		<?php else : ?>
				<div class="ui yellow message"><?php _e( 'No child sites found. You need to connect your websites first.', 'mainwp-team-control' ); ?></div>
		<?php endif; ?>
		</div>
		<div id="mainwp-team-control-groups-permissions" class="ui bottom attached tab segment" data-tab="allowed-groups" style="border-left: 1px solid #d4d4d5; border-right: 1px solid #d4d4d5; border-bottom: 1px solid #d4d4d5;">
			<div class="ui fluid icon input">
				<input type="text" id="mainwp-groups-tc-filter" value="" placeholder="<?php esc_attr_e( 'Type to filter groups', 'mainwp-team-control' ); ?>" />
				<i class="filter icon"></i>
			</div>
			<?php if ( is_array( $groups ) && count( $groups ) > 0 ) : ?>
			<div class="ui relaxed divided selection list" id="mainwp-team-control-sites-list">
				<?php foreach ( $groups as $group ) : ?>
					<?php $checked = isset( $role_groups_caps[ $group['id'] ] ) ? 'checked="checked"' : ''; ?>
					<div class="item">
						<div class="ui checkbox">
							<input type="checkbox" <?php echo $checked; ?> value="1" id="mainwp_team_control_site_capabilities_<?php echo $group['id']; ?>"  capability="<?php echo $group['id']; ?>">
							<label for="mainwp_team_control_site_capabilities_<?php echo $group['id']; ?>" class="mainwp-item-name"><?php echo stripslashes( $group['name'] ); ?></label>
						</div>
			</div>
		  <?php endforeach; ?>
			</div>
		<?php else : ?>
				<div class="ui yellow message"><?php _e( 'No groups found. You need to create website groups first.', 'mainwp-team-control' ); ?></div>
		<?php endif; ?>
		</div>
		<script type="text/javascript">
		jQuery( '.ui.menu.permissions .item' ).tab();
		// Select all/none Permissions
		jQuery( '#mainwp-team-control-check-all-permissions' ).on( 'click', function () {
			jQuery( '#mainwp-team-control-dashboard-permissions' ).find( "input:checkbox" ).each( function () {
				jQuery( this ).attr( 'checked', true );
			} );
			return false;
		} );
		jQuery( '#mainwp-team-control-uncheck-all-permissions' ).on( 'click', function () {
			jQuery( '#mainwp-team-control-dashboard-permissions' ).find( "input:checkbox" ).each( function () {
				jQuery( this ).attr( 'checked', false );
			} );
			return false;
		} );

		// Select all/none Extensions
		jQuery( '#mainwp-team-control-check-all-extensions' ).on( 'click', function () {
			jQuery( '#mainwp-team-control-extensions-permissions' ).find( "input:checkbox" ).each( function () {
				jQuery( this ).attr( 'checked', true );
			} );
			return false;
		} );
		jQuery( '#mainwp-team-control-uncheck-all-extensions' ).on( 'click', function () {
			jQuery( '#mainwp-team-control-extensions-permissions' ).find( "input:checkbox" ).each( function () {
				jQuery( this ).attr( 'checked', false );
			} );
			return false;
		} );

		// Select all/none Sites
		jQuery( '#mainwp-team-control-check-all-sites' ).on( 'click', function () {
			jQuery( '.ui.active.tab #mainwp-team-control-sites-list' ).find( "input:checkbox" ).each( function () {
				jQuery( this ).attr( 'checked', true );
			} );
			return false;
		} );
		jQuery( '#mainwp-team-control-uncheck-all-sites' ).on( 'click', function () {
			jQuery( '.ui.active.tab #mainwp-team-control-sites-list' ).find( "input:checkbox" ).each( function () {
				jQuery( this ).attr( 'checked', false );
			} );
			return false;
		} );

		// Filter sites in the sites element
		jQuery( '#mainwp-sites-tc-filter' ).on( 'keyup', function () {
		  var filter = jQuery( this ).val().toLowerCase();
		  var parent = jQuery( this ).closest( '#mainwp-team-control-sites-permissions' );
		  var siteItems = [];

		  siteItems = parent.find( '.item' );

		  for ( var i = 0; i < siteItems.length; i++ ) {
			var currentElement = jQuery( siteItems[i] );
			var value = currentElement.find( '.mainwp-item-name' ).text().toLowerCase();
			if ( value.indexOf( filter ) > -1 ) {
			  currentElement.show();
			} else {
			  currentElement.hide();
			}
		  }
		} );

		// Filter groups in the sites element
		jQuery( '#mainwp-groups-tc-filter' ).on( 'keyup', function () {
		  var filter = jQuery( this ).val().toLowerCase();
		  var parent = jQuery( this ).closest( '#mainwp-team-control-groups-permissions' );
		  var siteItems = [];

		  siteItems = parent.find( '.item' );

		  for ( var i = 0; i < siteItems.length; i++ ) {
			var currentElement = jQuery( siteItems[i] );
			var value = currentElement.find( '.mainwp-item-name' ).text().toLowerCase();
			if ( value.indexOf( filter ) > -1 ) {
			  currentElement.show();
			} else {
			  currentElement.hide();
			}
		  }
		} );
		</script>
		<?php
		exit();
	}

	public function ajax_delete_role() {
		global $wpdb;
		$this->ajax_check_permissions();
		$return = array( 'success' => false );

		$role_id = intval( $_POST['role_id'] );
		$role    = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'id', $role_id );
		if ( $role ) {
			global $mainWPTeamControlRole;
			if ( current_user_can( $role->role_name_id ) ) {
				$return['error'] = __( 'Role could not be deleted.', 'mainwp-team-control' );
				die( json_encode( $return ) );
			}

			$userIds = $_POST['user_ids'];

			if ( ! is_array( $userIds ) ) {
				$userIds = array();
			}

			$rolesAssignedTo = $_POST['roles_assigned'];

			foreach ( $userIds as $i => $uId ) {
				$to_role = $rolesAssignedTo[ $i ];
				if ( 'none' == $to_role ) {
					$to_role = '';
				}

				$my_user = new WP_User( $uId );
				$my_user->remove_role( $role->role_name_id );
				$my_user->add_role( $to_role );
			}

			if ( $mainWPTeamControlRole->delete_wp_roles( array( $role->role_name_id ) ) ) {
				if ( MainWP_Team_Control_DB::get_instance()->delete_role_by( 'id', $role_id ) ) {
					$return['success'] = 1;
				} else {
					$return['error'] = __( 'The role has been deleted successfully.', 'mainwp-team-control' );
				}
			} else {
				$return['error'] = __( 'Deleting failed. Please, try again.', 'mainwp-team-control' );
			}
		} else {
			$return['error'] = __( 'Deleting failed. Role could not be found.', 'mainwp-team-control' );
		}
		die( json_encode( $return ) );
	}

	public function ajax_save_role() {
		$this->ajax_check_permissions();
		global $wpdb;

		$return           = array(
			'success' => false,
			'error'   => '',
			'message' => '',
		);
		$role_name        = isset( $_POST['role_name'] ) ? sanitize_text_field( $_POST['role_name'] ) : '';
		$role_description = isset( $_POST['role_description'] ) ? sanitize_text_field( $_POST['role_description'] ) : '';

		if ( isset( $_POST['role_id'] ) && ( $role_id = intval( $_POST['role_id'] ) ) ) {
			$mainwp_role = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'id', $role_id );
			if ( ! $mainwp_role ) {
				$return['error'] = __( 'Role not found.', 'mainwp-team-control' );
			} elseif ( ( $check = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'role_name', $role_name ) ) && $check->id != $role_id ) {
				$return['error'] = __( 'Role name is empty. Please enter the role name.', 'mainwp-team-control' );
			} else {
				global $mainWPTeamControlRole;
				$result = $mainWPTeamControlRole->rename_role( $mainwp_role->role_name_id, $role_name );
				if ( isset( $result['success'] ) ) {
					global $mainwp_current_user;
					$caps                       = $mainWPTeamControlRole->prepare_capabilities_to_save();
					$update                     = array( 'role_capabilities' => base64_encode( serialize( $caps ) ) );
					$update['role_name']        = $role_name;
					$update['role_description'] = $role_description;
					$role                       = MainWP_Team_Control_DB::get_instance()->update_role( $role_id, $update );

					if ( false !== $role ) {
						$return['success'] = true;
					} else {
						$return['success'] = true;
						$return['message'] = __( 'Saved without of changes.', 'mainwp-team-control' );
						$role              = $mainwp_role;
					}
					$return['row_data'] = $this->create_role_item( $role, false );
				} elseif ( isset( $result['error'] ) ) {
					$return['error'] = $result['error'];
				}
			}
		} else {
			if ( empty( $role_name ) ) {
				$return['error'] = __( 'Role name is empty. Please enter the role name.', 'mainwp-team-control' );
			} elseif ( $current = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'role_name', $role_name ) ) {
				$return['error'] = __( 'Role name already exist. Please use different role name.', 'mainwp-team-control' );
			} else {
				$role_name_id = MainWP_Team_Control_Utility::gen_role_id( $role_name );
				global $mainWPTeamControlRole;
				$result = $mainWPTeamControlRole->add_new_role( $role_name, $role_name_id );
				if ( is_array( $result ) && isset( $result['success'] ) && $result['success'] ) {
					$default_caps = $mainWPTeamControlRole->get_default_mainwp_capabilities_for_role();
					$new          = array(
						'role_name'         => $role_name,
						'role_description'  => $role_description,
						'type'              => 0,
						'role_name_id'      => $role_name_id,
						'role_capabilities' => base64_encode( serialize( $default_caps ) ),
					);
					if ( $role = MainWP_Team_Control_DB::get_instance()->add_role( $new ) ) {
						$return['success']  = true;
						$return['row_data'] = $this->create_role_item( $role );
					} else {
						$return['error'] = __( 'Creating role failed. Please, try again.', 'mainwp-team-control' );
					}
				} elseif ( isset( $result['error'] ) ) {
					$return['error'] = $result['error'];
				}
			}
		}
		echo json_encode( $return );
		exit;
	}

	function ajax_user_delete() {

		$this->ajax_check_permissions();
		$this->user_action( 'delete' );
		die( json_encode( array( 'result' => 'User has been deleted' ) ) );
	}

	function ajax_user_notes_save() {

		$this->ajax_check_permissions( false );

		if ( isset( $_POST['user_id'] ) ) {
			$note = trim( $_POST['note'] );

			if ( ! empty( $note ) ) {
				$note = $this->esc_content( $note );
			}

			if ( update_user_option( $_POST['user_id'], '_mainwp_team_control_user_notes', $note, true ) ) {
				if ( empty( $note ) ) {
					die( 'EMPTY' ); } else {
					die( 'SUCCESS' ); }
			} else {
				die( 'NOTCHANGE' ); }
		}
		die( 'ERROR' );
	}


	public function esc_content( $content, $type = 'note' ) {
		if ( $type == 'note' ) {

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
			);

			$content = wp_kses( $content, $allowed_html );

		} else {
			$content = wp_kses_post( $content );
		}

		return $content;
	}

	function ajax_delete_role_load_users() {

		$this->ajax_check_permissions( false );

		if ( isset( $_POST['roleId'] ) && $_POST['roleId'] ) {
			$del_role = MainWP_Team_Control_DB::get_instance()->get_roles_by( 'id', $_POST['roleId'] );
			if ( empty( $del_role ) ) {
				exit( __( 'Role not found.', 'mainwp-team-control' ) );
			}

			$users = get_users( 'role=' . $del_role->role_name_id );
			if ( ! is_array( $users ) ) {
				$users = array();
			}

			$msg = '';

			if ( count( $users ) == 0 ) {
				$msg .= sprintf( esc_html__( 'There are no users with the "%s" role detected.', 'mainwp-team-control' ), $del_role->role_name );
			} else {
				$msg .= sprintf( esc_html__( 'There is %1$d users with the "%2$s" role detected. What should we do with these users?', 'mainwp-team-control' ), count( $users ), $del_role->role_name );
			}
			ob_start();
			?>

			<div class="ui message"><?php echo $msg; ?></div>

			<?php
			if ( count( $users ) > 0 ) {
				$mainwp_roles = MainWP_Team_Control_DB::get_instance()->get_roles();
				if ( ! is_array( $mainwp_roles ) ) {
					$mainwp_roles = array();
				}
				?>
				<div id="mainwp-team-control-reassign-user-roles" class="ui grid" >
				<?php
				foreach ( $users as $user ) {
					?>
					<div class="ui two column row" user-id="<?php echo $user->ID; ?>">
						<div class="middle aligned column"><strong><?php echo $user->user_login; ?></strong> <em>(<?php echo $user->user_email; ?>)</em></div>
						<div class="middle aligned right aligned column">
							<?php _e( 'Set role to ', 'mainwp-team-control' ); ?>
							<select id="mainwp-team-control-change-role" class="ui dropdown mainwp-team-control-change-role">
								<option value="none"><?php _e( 'None', 'mainwp-team-control' ); ?></option>
								<option value="administrator"><?php _e( 'Administrator', 'mainwp-team-control' ); ?></option>
								<option value="editor"><?php _e( 'Editor', 'mainwp-team-control' ); ?></option>
								<option value="author"><?php _e( 'Author', 'mainwp-team-control' ); ?></option>
								<option value="contributor"><?php _e( 'Contributor', 'mainwp-team-control' ); ?></option>
								<option value="subscriber" selected="selected"><?php _e( 'Subscriber', 'mainwp-team-control' ); ?></option>
								<?php
								foreach ( $mainwp_roles as $role ) {
									if ( $role->role_name_id == $del_role->role_name_id ) {
										continue;
									}
									?>
									<option value="<?php echo $role->role_name_id; ?>"><?php echo $role->role_name; ?></option>
									<?php
								}
								?>
			  </select>
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<?php
			}
			$html = ob_get_clean();
			die( $html );
		} else {
			die( __( 'Undefined Role ID.', 'mainwp-team-control' ) );
		}
	}

	function ajax_user_change_role() {
		$this->ajax_check_permissions();
		$role          = isset( $_POST['role'] ) ? $_POST['role'] : '';
		$role_name     = isset( $_POST['role_name'] ) ? $_POST['role_name'] : '';
		$current_roles = isset( $_POST['currentRoles'] ) ? $_POST['currentRoles'] : '';
		$this->user_action( 'change_role', $role, $current_roles );
		die( json_encode( array( 'result' => 'Role has been changed to ' . $role_name ) ) );
	}

	function user_action( $action, $extra = '', $extra2 = '' ) {

		$userId = $_POST['userId'];

		$curr_userid = get_current_user_id();

		if ( ( ( 'delete' == $action ) || ( 'change_role' == $action ) ) && ( $userId == $curr_userid ) ) {
			die( json_encode( array( 'error' => __( 'This user is you, you can not delete or change role yourself.', 'mainwp-team-control' ) ) ) );
		}

		global $current_user;

		$reassign = ( isset( $current_user ) && isset( $current_user->ID ) ) ? $current_user->ID : 0;

		if ( 'delete' == $action ) {
			include_once ABSPATH . '/wp-admin/includes/user.php';
			wp_delete_user( $userId, $reassign );
		} elseif ( 'change_role' == $action ) {
			$my_user      = new WP_User( $userId );
			$currentRoles = explode( ',', $extra2 );
			foreach ( $currentRoles as $remove_role ) {
				$my_user->remove_role( $remove_role );
			}
			$my_user->add_role( $extra );
		} elseif ( 'update_password' == $action ) {
			$user_pass            = $_POST['update_password'];
			$my_user              = array();
			$my_user['ID']        = $userId;
			$my_user['user_pass'] = $user_pass;
			wp_update_user( $my_user );
		} else {
			$information['status'] = 'FAIL';
		}

		if ( ! isset( $information['status'] ) ) {
			$information['status'] = 'SUCCESS';
		}

		if ( ! isset( $information['status'] ) || ( 'SUCCESS' != $information['status'] ) ) {
			die( json_encode( array( 'error' => __( 'Undefined error occurred. Please, try again.', 'mainwp-team-control' ) ) ) );
		}
	}


	protected function ajax_check_permissions( $json = true ) {
		if ( ! isset( $_REQUEST['wp_nonce'] ) || ! wp_verify_nonce( $_REQUEST['wp_nonce'], 'mainwp-teamcon-nonce' ) ) {
			echo $json ? json_encode( array( 'error' => 'Error: Wrong or expired request' ) ) : 'Error: Wrong or expired request';
			die;
		}
	}

	function create_role_item( $role, $with_tr = true ) {
		$html = '';

		if ( $with_tr ) {
			$html = '<tr class="mainwp-team-control-custom-role" role-id="' . $role->id . '" role-type="' . $role->type . '" >';
		}

		$html .= '<td id="mainwp-team-control-role-name-td" value="' . htmlspecialchars( stripslashes( $role->role_name ) ) . '"><a href="#" id="mainwp-team-control-edit-role">' . stripslashes( $role->role_name ) . '</a></td>';
		$html .= '<td id="mainwp-team-control-role-description-td" value="' . htmlspecialchars( stripslashes( $role->role_description ) ) . '">' . stripslashes( $role->role_description ) . '</td>';
		$html .= '<td class="aligned right">';
		$html .= '<a href="#" id="mainwp-team-control-edit-role" class="ui mini basic green button">' . __( 'Edit Role / Manage Permissions', 'mainwp-team-control' ) . '</a> ';
		$html .= '<a href="#" id="mainwp-team-control-delete-role" class="ui mini button">' . __( 'Delete Role', 'mainwp-team-control' ) . '</a>';
		$html .= '</td>';

		if ( $with_tr ) {
			$html .= '</tr>';
		}

		return $html;
	}
}
