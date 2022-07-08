var team_control_roles_caps_processing = false;

jQuery( document ).ready( function($) {

	// Trigger show users
	jQuery( '#mainwp_team_control_show_users' ).on( 'click', function () {
		mainwp_team_control_fetch_users();
	} );

	// Show the Create Role modal
	jQuery( '#mainwp-team-control-create-role' ).on( 'click', function() {
		jQuery( '#mainwp-team-control-create-role-modal' ).modal( 'show' );
	} );

	// Create custom role
	$( '#mainwp-team-control-create-role-button' ).on( 'click', function() {
		var parent = jQuery( '#mainwp-team-control-create-role-modal' );
		var roleName = parent.find( 'input[name="mainwp-team-control-role-name"]' ).val().trim();
		var roleDesc = parent.find( 'input[name="mainwp-team-control-role-description"]' ).val().trim();

		if ( roleDesc == '' ) {
			roleDesc = roleName;
		}

		$( this ).attr( 'disabled', true );

		var fields = {
			role_name: roleName,
			role_description: roleDesc,
			action: 'mainwp_team_control_save_role',
			wp_nonce: mainwp_teamcon_data.wp_nonce
		};

		$.post( ajaxurl, fields, function (response) {
			if ( response ) {
				if ( response['success'] ) {
					window.location.reload();
				} else {
					if ( response['error'] ) {
						$( '#mainwp-team-control-create-role-modal' ).find( '.ui.message' ).html( '<i class="close icon"></i>' + response['error'] ).show();
						$( '#mainwp-team-control-create-role-button' ).removeAttr( 'disabled' );
					} else {
						$( '#mainwp-team-control-create-role-modal' ).find( '.ui.message' ).html( '<i class="close icon"></i>' + __( 'Undefined error occurred. Please, try again.' ) ).show();
						$( '#mainwp-team-control-create-role-button' ).removeAttr( 'disabled' );
					}
				}
			} else {
				$( '#mainwp-team-control-create-role-modal' ).find( '.ui.message' ).html( '<i class="close icon"></i>' + __( 'Undefined error occurred. Please, try again.' ) ).show();
				$( '#mainwp-team-control-create-role-button' ).removeAttr( 'disabled' );
			}
		}, 'json');
		return false;
	} );

	// Show Edit role modal
	jQuery( document ).on( 'click', '#mainwp-team-control-edit-role', function() {
		team_control_roles_caps_processing = true;

		var parent = $( this ).parents( '.mainwp-team-control-custom-role' );
		var roleId = parent.attr( 'role-id' );
		var roleName = parent.find( '#mainwp-team-control-role-name-td').attr( 'value' );
		var roleDesc = parent.find( '#mainwp-team-control-role-description-td').attr( 'value' );

		$( '#mainwp-team-control-edit-role-modal' ).modal( 'show' );
		$( '#mainwp-team-control-edit-role-modal' ).attr( 'role-id', roleId );
		$( '#mainwp-team-control-permissions-form' ).attr( 'current-roleid', roleId );

		$( '#mainwp-team-control-edit-role-modal' ).find( '.ui.dimmer' ).addClass( 'active' );

		$( 'input#mainwp-team-control-role-name' ).val( roleName );
		$( 'input#mainwp-team-control-role-description' ).val( roleDesc );

		jQuery.post( ajaxurl, {
			action: 'mainwp_team_control_load_role_caps',
			roleId: parent.attr( 'role-id' )
		}, function ( data ) {
			team_control_roles_caps_processing = false;
			$( '#mainwp-team-control-edit-role-modal' ).find( '.ui.dimmer' ).removeClass( 'active' );

			if ( $( '#mainwp-team-control-permissions-form' ).attr( 'current-roleid' ) == roleId ) {
				$( '#mainwp-team-control-permissions-form' ).html( data );
			}
		} );

		return false;
	} );

	// Update role settings
	$( '#mainwp-team-control-update-role-button' ).on( 'click', function() {

		if ( $( '#mainwp-team-control-permissions-form' ).attr( 'current-roleid' ) == 0 ) {
			return false;
		}

		if ( team_control_roles_caps_processing ) {
			return false;
		}

		var parent = $( this ).closest( '.modal' );

		$( '#mainwp-team-control-modal-message-zone' ).removeClass( 'red green yellow' );
		$( '#mainwp-team-control-modal-message-zone' ).html( '' ).hide();

		var errors = [];
		var roleName = '';
		var roleDesc = '';

		roleName = parent.find( 'input[name="mainwp-team-control-role-name"]' ).val().trim();
		roleDesc = parent.find( 'input[name="mainwp-team-control-role-description"]' ).val().trim();

		if ( roleName == '' ) {
			errors.push( __( 'Role name is required. Please enter the role name.', 'mainwp-team-control' ) );
		}

		if ( roleDesc == '' ) {
			roleDesc = roleName;
		}

		if ( errors.length > 0 ) {
			$( '#mainwp-team-control-modal-message-zone' ).html( '<i class="close icon"></i>' + errors.join( '<br />' ) ).show();
			$( '#mainwp-team-control-modal-message-zone' ).addClass( 'yellow' );
			return false;
		}

		parent.find( '.ui.dimmer' ).addClass( 'active' );
		parent.find( '.ui.dimmer' ).find( '.ui.text' ).html( 'Saving...' );

		var dashboard_caps = [];
		var extensions_caps = [];
		var sites_caps = [];
		var groups_caps = [];

		$( "#mainwp-team-control-dashboard-permissions" ).find( "input:checkbox:enabled:checked" ).each( function() {
			dashboard_caps.push( $( this ).attr( 'capability' ) );
		} );

		$( "#mainwp-team-control-extensions-permissions" ).find( "input:checkbox:enabled:checked" ).each( function() {
			extensions_caps.push( $( this ).attr( 'capability' ) );
		} );

		$( "#mainwp-team-control-sites-permissions" ).find( "input:checkbox:enabled:checked" ).each( function() {
			sites_caps.push( $( this ).attr( 'capability' ) );
		} );

		$( "#mainwp-team-control-groups-permissions" ).find( "input:checkbox:enabled:checked" ).each( function() {
			groups_caps.push( $( this ).attr( 'capability' ) );
		} );

		var fields = {
			role_id: parent.attr( 'role-id' ),
			action: 'mainwp_team_control_save_role',
			wp_nonce: mainwp_teamcon_data.wp_nonce,
			dashboard_caps: dashboard_caps,
			extensions_caps: extensions_caps,
			sites_caps: sites_caps,
			groups_caps: groups_caps,
			role_name: roleName,
			role_description: roleDesc
		};

		team_control_roles_caps_processing = true;

		$.post( ajaxurl, fields, function ( response ) {
			if ( $( '#mainwp-team-control-permissions-form' ).attr( 'current-roleid' ) != parent.attr( 'role-id' ) ) {
				return false;
			}

			team_control_roles_caps_processing = false;

			if ( response ) {
				if ( response['success'] ) {
					window.location.reload();
				} else {
					parent.find( '.ui.dimmer' ).removeClass( 'active' );
					if ( response['error'] ) {
						$( '#mainwp-team-control-modal-message-zone' ).html( '<i class="close icon"></i>' + response['error'] ).show();
					} else {
						$( '#mainwp-team-control-modal-message-zone' ).html( '<i class="close icon"></i> Undefined error occurred. Please, try again.' ).show();
					}
				}
			} else {
				parent.find( '.ui.dimmer' ).removeClass( 'active' );
				$( '#mainwp-team-control-modal-message-zone' ).html( '<i class="close icon"></i> Undefined error occurred. Please, try again.' ).show();
			}
		}, 'json');
		return false;
	} );

	//Show Delete role modal
	jQuery( document ).on( 'click', '#mainwp-team-control-delete-role', function() {

		if ( team_control_roles_caps_processing ) {
			return false;
		}

		var parent = jQuery( this ).closest( '.mainwp-team-control-custom-role' );
		var roleId = parent.attr( 'role-id' );
		mainwp_teamcontrol_show_deleterole_box( roleId );
		return false;
	} );

	// Confirm and finalize the role delete process.
	$( '#mainwp-team-control-confirm-delete-role' ).on( 'click', function() {
		team_control_roles_caps_processing = true;
		var roleId = jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).attr( 'role-id' );
		var parent = jQuery( '.mainwp-team-control-custom-role[role-id=' + roleId + ']' );
		var userIds = [];
		var rolesAssignedTo = [];

		jQuery( '#mainwp-team-control-reassign-user-roles .row' ).each( function () {
			userIds.push( $( this ).attr( 'user-id' ) );
			rolesAssignedTo.push( $( this ).find( '#mainwp-team-control-change-role' ).val() );
		} );

		jQuery( '#mainwp-team-control-confirm-delete-role' ).attr( 'disabled', 'disabled' );
		jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).after( '<div class="ui inverted dimmer"><div class="ui text loader">Deleting role...</div></div>' );
		jQuery( '.ui.dimmer' ).addClass( 'active' );
		jQuery.post( ajaxurl, {
			action: 'mainwp_team_control_delete_role',
			role_id: roleId,
			user_ids: userIds,
			roles_assigned: rolesAssignedTo,
			wp_nonce: mainwp_teamcon_data.wp_nonce
			}, function ( data ) {
				team_control_roles_caps_processing = false;

				if ( data ) {
					if ( data.success ) {
						window.location.reload();
					} else if ( data.error ) {
						jQuery( '.ui.dimmer' ).removeClass( 'active' );
						jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).html( '<div class="ui message red"><i class="close icon"></i>' + data.error + '</div>' ).show();
					} else {
						jQuery( '.ui.dimmer' ).removeClass( 'active' );
						jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).html( '<div class="ui message red"><i class="close icon"></i>' + __( 'Undefined error occurred. Please, try again.' ) + '</div>' ).show();
					}
				} else {
					jQuery( '.ui.dimmer' ).removeClass( 'active' );
					jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).html( '<div class="ui message red"><i class="close icon"></i>' + __( 'Undefined error occurred. Please, try again.' ) + '</div>' ).show();
				}
			}, 'json');
		return false;
	} );

	//Select all users
	jQuery( '#mainwp-team-control-users-table th input[type="checkbox"]' ).change( function () {
		var checkboxes = jQuery( '#mainwp-team-control-users-table' ).find( ':checkbox' );
		if ( jQuery( this ).prop( 'checked' ) ) {
    	checkboxes.prop( 'checked', true );
    } else {
      checkboxes.prop( 'checked', false );
    }
	} );


	//Trigger the bulk actions
	jQuery( '#mainwp-team-control-bulk-actions-button' ).on( 'click', function() {
		var action = jQuery( '#mainwp-team-control-bulk-actions' ).val();
		if ( action == 'none' ) {
			return false;
		}

		var users = jQuery( "input[name='user[]']:checked" );
		userCountSent = users.length;

		users.each(
			function (index, elem ) {
				mainwp_team_control_user_postAction( elem, action, false, false );
			}
		);

		return false;
	} );

	// Trigger bulk role change process
	jQuery( '#mainwp-team-control-change-users-role-button' ).on('click', function () {
		var change_to_role = jQuery( '#mainwp-team-control-change-users-role' ).val();

		if ( change_to_role == 'none' ) {
			return false;
		}

		var roleName = $( '#mainwp-team-control-change-users-role option:selected' ).text();
		var users = jQuery( "input[name='user[]']:checked" );

		userCountSent = users.length;

		users.each(
			function ( index, elem ) {
				mainwp_team_control_user_postAction( elem, 'change_role', change_to_role, roleName );
			}
		);

		return false;
	} );
	
	// Trigger User deletion process
	jQuery( document ).on( 'click', '.team_control_user_submitdelete', function() {
		if ( confirm( __( 'Are you sure?', 'mainwp-team-control' ) ) ) {
			mainwp_team_control_user_postAction( jQuery( this ), 'delete', false, false );
		}
		return false;
	} );

	// Cancel notes modal
	jQuery( '#mainwp-team-notes-cancel' ).on( 'click', function () {
    jQuery( '#mainwp-notes-status' ).html('');
    mainwp_team_notes_hide();
    window.location.reload();
  } );

	// Save note
  jQuery( '#mainwp-team-notes-save' ).on( 'click', function () {
		var type = jQuery( '#mainwp-notes-type' ).val();
    mainwp_team_notes_save( type );
    var newnote = jQuery( '#mainwp-notes-note' ).val();
    jQuery( '#mainwp-notes-html' ).html( newnote );
    return false;
  } );

	// Trigger edit note
  jQuery( document ).on( 'click', '.mainwp-edit-user-note', function () {
    mainwp_team_show_notes( jQuery( this ).attr( 'id' ).substr( 18 ) );
    return false;
  } );

	// Edit note
  jQuery( '#mainwp-team-notes-edit' ).on( 'click', function () {
    var value = jQuery( '#mainwp-notes-html' ).html();
    jQuery( '#mainwp-notes-html' ).hide()
    jQuery( '#mainwp-notes-editor' ).show();
    jQuery( '#mainwp-notes-note').val( value );
    jQuery( this ).hide();
    jQuery( '#mainwp-team-notes-save' ).show();
    jQuery( '#mainwp-notes-status' ).html('');
    return false;
  } );

});

// Show users
mainwp_team_control_fetch_users = function() {
	var errors = [];
	var role = "";

	jQuery( '#mainwp-message-zone' ).html( '' ).hide();
	jQuery( '#mainwp-message-zone' ).removeClass( 'red green yellow' );

	var roles = jQuery( '#mainwp-team-control-role-selection' ).dropdown( "get value" );

	if ( roles == null ) {
		errors.push( __( 'Please select at least one role.' ) );
	} else {
		role = roles.join(',');
	}

	if ( errors.length > 0 ) {
		jQuery( '#mainwp-message-zone' ).html( errors.join( '<br />' ) ).show();
		jQuery( '#mainwp-message-zone' ).addClass( 'yellow' );
		return;
	} else {
		jQuery( '#mainwp-message-zone' ).html( "" ).hide();
	}

	jQuery( '.ui.dimmer' ).addClass( 'active' );

	var data = {
		action: 'mainwp_team_control_users_search',
		role: role
	};

	jQuery.post( ajaxurl, data, function ( response ) {
		var table = ( response && response.table_html ) ? response.table_html : null;
		table = jQuery.trim( table );
		jQuery( '.ui.dimmer' ).removeClass( 'active' );
		jQuery( '#mainwp-team-control-users-table tbody' ).html( table );
		jQuery( '#mainwp-team-control-users-table .ui.checkbox' ).checkbox();
		jQuery( '#mainwp-team-control-users-table .ui.dropdown' ).dropdown();                
                
                // to fix
                if ( jQuery.fn.dataTable.isDataTable( '#mainwp-team-control-users-table' ) ) {
                    jQuery('#mainwp-team-control-users-table').DataTable();
                }
                else 
                {
                    jQuery( '#mainwp-team-control-users-table' ).DataTable( {
                        "columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
                    } );
                };
                
	}, 'json' );
};


// Show the Delte role modal
mainwp_teamcontrol_show_deleterole_box = function( pRoleId ) {
	jQuery( '#mainwp-team-control-delete-role-modal' ).modal( 'show' );
	mainwp_teamcontrol_deleterole_load_users( pRoleId );
};

// Delete role users
mainwp_teamcontrol_deleterole_load_users = function ( pRoleId ) {
	jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).find( '.ui.dimmer' ).addClass( 'active' );
	jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).attr( 'role-id', pRoleId );
	jQuery.post( ajaxurl, {
		action: 'mainwp_team_control_delete_role_load_users',
		roleId: pRoleId,
		wp_nonce: mainwp_teamcon_data.wp_nonce
		}, function ( data ) {
			jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).find( '.ui.dimmer' ).removeClass( 'active' );
			jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.content' ).html( data );
			jQuery( '#mainwp-team-control-delete-role-modal' ).find( '.mainwp-team-control-change-role' ).dropdown();
			jQuery( '#mainwp-team-control-confirm-delete-role' ).removeAttr( "disabled" );
		} );
}

// Actions process
mainwp_team_control_user_postAction = function( elem, what, role, roleName ) {
	var rowElement = jQuery( elem ).parents( 'tr' );
	var userId = rowElement.find( '.userId' ).val();

	var data = {
		action: 'mainwp_team_control_user_' + what,
		userId: userId,
		update_password: jQuery( '#pass1' ).val(),
		wp_nonce: mainwp_teamcon_data.wp_nonce
	};

	if ( what == 'change_role' ) {
		var curRoles = rowElement.attr( 'current-role' );
		data['role'] = role;
		data['role_name'] = roleName;
		data['currentRoles'] = curRoles;
	}


	rowElement.html( '<td colspan="7"><i class="notched circle loading icon"></i> ' + __( 'Please wait...' ) + '</td>' );
	jQuery.post( ajaxurl, data, function( response ) {
		if ( response.result ) {
			rowElement.html( '<td colspan="7">' + response.result + '</td>' );
		} else {
			if ( response.error ) {
				rowElement.html( '<i class="red times icon"></i> ' + response.error );
			}
		}
		userCountReceived++;

		if ( userCountReceived == userCountSent ) {
			userCountReceived = 0;
			userCountSent = 0;
			jQuery( '#mainwp_team_control_bulk_user_action_apply' ).removeAttr( 'disabled' );
			jQuery( '#mainwp_team_control_bulk_role_action_apply' ).removeAttr( 'disabled' );
		}
	}, 'json');

	return false;
};

// Show notes
mainwp_team_show_notes = function ( id ) {
  var note = jQuery( '#mainwp-user-notes-' + id + '-note' ).html();
  console.log(note);
  jQuery( '#mainwp-notes-html' ).html( note == '' ? __( 'No saved notes. Click the Edit button to edit site notes.' ) : note );
  jQuery( '#mainwp-notes-note' ).val( note );
  jQuery( '#mainwp-notes-userid' ).val( id );
  mainwp_team_notes_show();
};

// Show the notes modal
mainwp_team_notes_show = function () {
	jQuery( '#mainwp-notes' ).modal( 'show' );
	jQuery( '#mainwp-notes-html' ).show();
	jQuery( '#mainwp-notes-editor' ).hide();
	jQuery( '#mainwp-team-notes-save' ).hide();
	jQuery( '#mainwp-team-notes-edit' ).show();
};

// Hide the notes modal
mainwp_team_notes_hide = function () {
  jQuery( '#mainwp-notes' ).modal( 'hide' );
};

// Save the note
mainwp_team_notes_save = function () {
  var normalid = jQuery( '#mainwp-notes-userid' ).val();
  var newnote = jQuery( '#mainwp-notes-note' ).val();

  var data = mainwp_secure_data( {
        action: 'mainwp_team_control_user_notes_save',
        user_id: normalid,
        note: newnote,
        wp_nonce: mainwp_teamcon_data.wp_nonce
  } );

  jQuery( '#mainwp-notes-status' ).html( '<i class="notched circle loading icon"></i> ' + __( 'Saving note. Please wait...' ) );

	jQuery.post( ajaxurl, data, function ( pId ) {
		return function ( response ) {
			if ( response == 'SUCCESS' ) {
				jQuery( '#mainwp-notes-status' ).html( '<i class="check circle green icon"></i> ' +  __( 'Note saved.' ) );
	      if ( jQuery( '#mainwp-user-notes-' + pId + '-note' ) ) {
	        jQuery( '#mainwp-user-notes-' + pId + '-note' ).html( jQuery( '#mainwp_notes_note' ).val() );
	      }
			} else {
				jQuery( '#mainwp-notes-status' ).html( '<i class="times circle red icon"></i> ' + __( 'Undefined error occured while saving your note!' ) + '.' );
			}
		}
	}( normalid ) );

  jQuery( '#mainwp-notes-html' ).show();
  jQuery( '#mainwp-notes-editor' ).hide();
  jQuery( '#mainwp-team-notes-save' ).hide();
  jQuery( '#mainwp-team-notes-edit' ).show();
};
