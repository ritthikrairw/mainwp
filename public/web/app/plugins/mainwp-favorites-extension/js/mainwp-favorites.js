/**
 * Administration Javascript for MainWP Favorites Extension
 * For Install Themes/Plugins pages
 */

jQuery( document ).ready( function ($) {
  jQuery(document).on( 'click', '.mainwp-content-wrap a[id^="add-favorite-"]',  function () {
      var valueId = $(this).attr('id');
      if ( divId = /^add-favorite-([^\-]*)-(.*)$/.exec( valueId ) ) {
        jQuery( this ).bind( 'click', function ( what, slug ) {
			    var linkId = valueId;
			    return function () {
				    favorites_add_favorite( linkId, what, slug );
				    return false;
			    }();
		    }( divId[1], divId[2] ) );
	    }
    } );

    // Show modal to create a new group
    jQuery( '#mainwp-add-new-group' ).on( 'click', function () {
    	jQuery( '#mainwp-favorites-create-group-modal' ).modal( 'show' );
    	return false;
    } );

    //Select Favorites menu item
    jQuery( '#mainwp-favorite-groups-menu' ).find( 'a.item' ).on( 'click', function () {
    	jQuery( this ).addClass( 'active' );
    	jQuery( this ).siblings().removeClass( 'active' );
    	jQuery( this ).find( '.label' ).addClass( 'green' );
    	jQuery( this ).siblings().find( '.label' ).removeClass( 'green' );
    	jQuery( '#mainwp-favorites-rename-group' ).removeClass( 'disabled' );
    	jQuery( '#mainwp-favorites-delete-group' ).removeClass( 'disabled' );
    	jQuery( '#mainwp-favorites-save-selection' ).removeClass( 'disabled' );
    	favorites_show_group_items( this );
    } );

    // Save new group
    jQuery( '#managefavgroups-savenew' ).on( 'click', function () {
    	jQuery( this ).attr( 'disabled', 'disabled' );
    	var parentObj = jQuery( this ).parents( '.modal' );
    	parentObj.find( 'input' ).attr( 'disabled', 'disabled' );
    	var newName = parentObj.find( 'input' ).val();
    	var newType = parentObj.attr( 'type' );

    	var data = {
    		action: 'favorites_group_add',
    		newName: newName,
    		type: newType
    	};

    	jQuery.post( ajaxurl, data, function ( response ) {
    		response = jQuery.trim( response );
    		if ( response == 'ERROR' ) {
    			return;
    		}
    		window.location.reload();
    	} );

    	return false;
    } );

  	// Edit group (trigger modal)
  	jQuery( '#mainwp-favorites-rename-group' ).on( 'click', function () {
  		jQuery( '#mainwp-favorites-rename-group-modal' ).modal( 'show' );
  		var parentObj = jQuery( this ).parents( '.modal' );
  		var groupNeme = jQuery( '#mainwp-favorite-groups-menu' ).find( '.item.active' ).find( 'input#mainwp-hidden-group-name' ).val();
  		jQuery( 'input#mainwp-favorites-group-name' ).val( groupNeme );
  		return false;
  	} );

  	// Save new Group name
  	jQuery( '#mainwp-favorites-save-group-name' ).on( 'click', function () {
  		var parentObj = jQuery( this ).parents( '.modal' );
  		var groupId = jQuery( '#mainwp-favorite-groups-menu' ).find( '.item.active' ).find( 'input#mainwp-hidden-group-id' ).val();
  		var newName = parentObj.find( 'input' ).val();
  		var type = parentObj.attr( 'type' );

  		var data = {
  			action: 'favorites_group_rename',
  			groupId: groupId,
  			newName: newName,
  			type: type,
  		}

  		jQuery( this ).attr( 'disabled', 'disabled' );
  		parentObj.find( 'input' ).attr( 'disabled', 'disabled' );

  		jQuery.post( ajaxurl, data, function ( pParentObj ) {
  			return function ( response ) {
  				response = jQuery.trim( response );
  				window.location.reload();
  			}
  		}( parentObj ) );

  		return false;
  	} );

  	// Delete group
  	jQuery( '#mainwp-favorites-delete-group' ).on( 'click', function () {
  		var confirmed = confirm( __( 'Are you sure?' ) );
  		if ( confirmed ) {
  			var parentObj = jQuery( '#mainwp-favorite-groups-menu' );
  			var group = jQuery( '#mainwp-favorite-groups-menu' ).find( '.item.active' );
  			var groupId = group.attr( 'id' );

  			var data = {
  				action: 'favorites_group_delete',
  				groupId: groupId
  			}

  			jQuery( this ).attr( 'disabled', 'disabled' );
  			group.removeClass( 'active' );
  			group.addClass( 'disabled' );

  			jQuery.post( ajaxurl, data, function( pParentObj ) {
  				return function ( response ) {
  					response = jQuery.trim( response );
            if ( response == 'OK' ) {
  						window.location.reload();
  					}
  		    }
  			}( parentObj ) );
  		}

  		return false;
  	} );

  	// Trigger the upload modal
  	jQuery( '#mainwp-favorites-upload-button' ).on( 'click', function () {
  		jQuery( '#mainwp-favorite-upload-modal' ).modal( 'show' );
  	} );

  	// Close the upload modal and finish the uploader
  	jQuery( '#mainwp-favorites-complete-upload-button' ).on( 'click', function () {
  		window.location.reload();
  	} );

  	// Select all plugins
  	jQuery( '#mainwp-favorites-plugin-table th input[type="checkbox"]' ).change( function () {
  		var checkboxes = jQuery( '#mainwp-favorites-plugin-table' ).find( ':checkbox' );
  		if ( jQuery( this ).prop( 'checked' ) ) {
      	checkboxes.prop( 'checked', true );
      } else {
        checkboxes.prop( 'checked', false );
      }
  	} );

  	// Select all themes
  	jQuery( '#mainwp-favorites-theme-table th input[type="checkbox"]' ).change( function () {
  		var checkboxes = jQuery( '#mainwp-favorites-theme-table' ).find( ':checkbox' );
  		if ( jQuery( this ).prop( 'checked' ) ) {
      	checkboxes.prop( 'checked', true );
      } else {
        checkboxes.prop( 'checked', false );
      }
  	} );

  	// Save group items
  	jQuery( '#mainwp-favorites-save-selection' ).on( 'click', function () {
  		var groupsMenu = jQuery( '#mainwp-favorite-groups-menu' );
  		var favoritesTable = jQuery( this ).parents( '.table' );
  		var type = favoritesTable.attr( 'type' );
  		var selectedGroup = groupsMenu.find( '.item.active' );
  		var groupId = selectedGroup.attr( 'id' );
  		if ( groupId == undefined )
  			return;
  		var favorites = favoritesTable.find( 'input[favorite="' + type + '"]:checked' );
  		var favoritesIds = [];

  		for ( var i = 0; i < favorites.length; i++ ) {
  			favoritesIds.push( jQuery( favorites[i] ).val() );
  		}

  		var data = {
  			action: 'favorites_group_updategroup',
  			groupId: groupId,
  			favoriteIds: favoritesIds
  		};

  		jQuery( this ).addClass( 'disabled' );
  		selectedGroup.find( '.label' ).html( __( 'Updating...' ) );

  		jQuery.post( ajaxurl, data, function ( response ) {
  			response = jQuery.trim( response );
  			jQuery( '#mainwp-favorites-save-selection' ).removeClass( 'disabled' );
  			selectedGroup.find( '.label' ).html( i );
  			return;
  		} );
  	} );

  	// Cancel notes modal
  	jQuery( '#mainwp-notes-cancel' ).on( 'click', function () {
      jQuery( '#mainwp-notes-status' ).html('');
      mainwp_notes_hide();
      window.location.reload();
    } );

  	// Save note
    jQuery( '#mainwp-notes-save' ).on( 'click', function () {
  		var type = jQuery( '#mainwp-notes-type' ).val();
      mainwp_notes_save( type );
      var newnote = jQuery( '#mainwp-notes-note' ).val();
      jQuery( '#mainwp-notes-html' ).html( newnote );
      return false;
    } );

  	// Trigger edit note
    jQuery( '.mainwp-edit-favorite-note' ).on( 'click', function () {
  		var type = jQuery( this ).attr( 'type' );
      mainwp_show_notes( jQuery( this ).attr( 'id' ).substr( 22 ), type );
      return false;
    } );

  	// Edit note
    jQuery( '#mainwp-notes-edit' ).on( 'click', function () {
      var value = jQuery( '#mainwp-notes-html' ).html();
      jQuery( '#mainwp-notes-html' ).hide()
      jQuery( '#mainwp-notes-editor' ).show();
      jQuery( '#mainwp-notes-note').val( value );
      jQuery( this ).hide();
      jQuery( '#mainwp-notes-save' ).show();
      jQuery( '#mainwp-notes-status' ).html('');
      return false;
    } );

    // to support mainwp qq uploader add to favorites
    jQuery( document ).on( 'click', '.qq-upload-add-to-favorites a', function() {

        var parent = jQuery(this).closest('#mainwp-file-uploader');
        var type = parent.hasClass('qq-upload-plugins') ? 'plugin' : 'theme';
        var item = jQuery(this).closest('.file-uploaded-item').find('.qq-upload-file');

        var data = {
            action: 'favorites_uploadbulkaddtofavorites',
            type: type,
            file: item.attr('filename'),
            copy: 'yes',
            nonce: security_nonces['mainwp-common-nonce'] // from mainwp
	};

        var parentToAdd  = jQuery(this).parent();
        parentToAdd.html( '<i class="ui active inline loader tiny"></i> ' + __( 'Adding to favorites.' ) );
	jQuery.post(ajaxurl, data, function (pFavoriteToAdd) {
		return function (response) {
			if ( response == 'NEWER_EXISTED' ) {
				pFavoriteToAdd.html( __( 'Newer version exists. Adding to favorites skipped.' ) );
			} else if (response == 'SUCCESS') {
				pFavoriteToAdd.html( __( 'Added to favorites.' ) );
			} else {
        pFavoriteToAdd.html( __( 'Adding to favorites failed. Please, try again.' ) );
      }
		}
	}(parentToAdd));
	return false;

    } );

} );


// Show group items
favorites_show_group_items = function( group ) {
	var groupId = jQuery( group ).attr( 'id' );
	var type = jQuery( group ).parents( '.menu' ).attr( 'type' );

	jQuery( '.dimmer' ).addClass( 'active' );

	var data = {
		action: 'favorites_group_getfavorites',
		groupId: groupId
	}

	jQuery.post( ajaxurl, data, function ( response ) {
		jQuery( '.dimmer' ).removeClass( 'active' );
		response = jQuery.trim( response );
		if ( response == 'ERROR' ) {
			return;
		}
		jQuery( 'input[favorite="' + type + '"]' ).attr( 'checked', false );
		jQuery( 'input[favorite="' + type + '"]' ).closest( 'tr' ).removeClass( 'active' );
		var favoritesIds = jQuery.parseJSON( response );
		for ( var i = 0; i < favoritesIds.length; i++ ) {
			jQuery( 'input[favorite="' + type + '"][value="' + favoritesIds[i] + '"]' ).attr( 'checked', true );
			jQuery( 'input[favorite="' + type + '"][value="' + favoritesIds[i] + '"]' ).closest( 'tr' ).addClass( 'active' );
		}
	} );
	return false;
};

// Remove favorite item
managefavorite_remove = function ( type, file, id, obj ) {
	var confirmed = confirm( __( 'Are you sure?' ) );
	if ( confirmed ) {
		var data = {
			action: 'favorite_removefavorite',
			id: id,
			type: type,
			file: file
		};
		jQuery( obj ).attr( 'disabled', 'disabled' );

		jQuery.post( ajaxurl, data, function ( response ) {
			response = jQuery.trim( response );
			if (response == 'SUCCESS') {
				jQuery( obj ).parents( 'tr' ).remove()
			} else {
				jQuery( obj ).parents( 'tr' ).html( '<td colspan="5">error</td>');
			}
		});
	}
	return false;
};

mainwp_show_notes = function ( id, type ) {
  var note = jQuery( '#mainwp-favorite-notes-' + id + '-note' ).html();
  jQuery( '#mainwp-notes-html' ).html( note == '' ? __( 'No saved notes. Click the Edit button to edit site notes.' ) : note );
  jQuery( '#mainwp-notes-note' ).val( note );
  jQuery( '#mainwp-notes-favroriteid' ).val( id );
	jQuery( '#mainwp-notes-type' ).val( type );
  jQuery( '#mainwp-notes' ).modal( 'show' );
  jQuery( '#mainwp-notes-html' ).show()
  jQuery( '#mainwp-notes-editor' ).hide();
  jQuery( '#mainwp-notes-save' ).hide();
  jQuery( '#mainwp-notes-edit' ).show();
};

// Hide the notes modal
mainwp_notes_hide = function ( type ) {
  jQuery( '#mainwp-notes' ).modal( 'hide' );
};

// Save the note
mainwp_notes_save = function ( type ) {
  var normalid = jQuery( '#mainwp-notes-favroriteid' ).val();
  var newnote = jQuery( '#mainwp-notes-note' ).val();

	if ( type == 'group' ) {
		var data = mainwp_secure_data( {
	    action: 'group_notes_save',
	    groupid: normalid,
	    note: newnote
	  } );
	} else {
		var data = mainwp_secure_data( {
	    action: 'favorite_notes_save',
	    favoriteid: normalid,
	    note: newnote
	  } );
	}

  jQuery( '#mainwp-notes-status' ).html( '<i class="notched circle loading icon"></i> ' + __( 'Saving note. Please wait...' ) );

	jQuery.post( ajaxurl, data, function ( pId ) {
		return function ( response ) {
			if ( response == 'SUCCESS' ) {
				jQuery( '#mainwp-notes-status' ).html( '<i class="check circle green icon"></i> ' +  __( 'Note saved.' ) );
	      if ( jQuery( '#mainwp-favorite-notes-' + pId + '-note' ) ) {
	        jQuery( '#mainwp-favorite-notes-' + pId + '-note' ).html( jQuery( '#mainwp_notes_note' ).val() );
	      }
			} else {
				jQuery( '#mainwp-notes-status' ).html( '<i class="times circle red icon"></i> ' + __( 'Undefined error occured while saving your note!' ) + '.' );
			}
		}
	}( normalid ) );

  jQuery( '#mainwp-notes-html' ).show();
  jQuery( '#mainwp-notes-editor' ).hide();
  jQuery( '#mainwp-notes-save' ).hide();
  jQuery( '#mainwp-notes-edit' ).show();
};


// Add to favorites (install from WP.org)... Continue...
favorites_add_favorite = function ( linkID, type, slug ) {
	var data = {
		action: 'favorites_addplugintheme',
		type: type,
		slug: slug,
	};

	var parent = jQuery( '#' + linkID ).parent().find( 'span' );
	parent.html( __( 'Adding favorites. Please wait...' ) );
	jQuery.post( ajaxurl, data, function ( response ) {
		if ( response == 'FAIL' ) {
			parent.html( __( 'Adding failed. Please, try again.' ) );
		} else {
			parent.html( __( 'Added to favorites.' ) );
		}
	} );
};

favorites_uploadbulk_oncomplete = function (id, fileName, result, type) {
	if (result.success) {
		if (totalSuccess > 0) { // global variable
			jQuery( ".qq-upload-file" ).each(function (i) {
				if (jQuery( this ).parent().attr( 'class' ) && jQuery( this ).parent().attr( 'class' ).replace( /^\s+|\s+$/g, "" ) == 'qq-upload-success') {
					_file = jQuery( this ).attr( 'filename' );
					if (jQuery( this ).next().next().attr( 'class' ) != 'favorites-add-file') {
						jQuery( this ).next().after( '<span class="favorites-add-file"><a class="add-favorites" href="#" onclick="return favorites_upload_add_to_favorites(\'' + _file + '\', this, \'' + type + '\')" title="' + __( "Add To Favorites" ) + '">' + __( "Add To Favorites" ) + '</a></span> ' );
					}
				}
			});
		}
	}
}

favorites_upload_add_to_favorites = function ( file, obj, type ) {
	var pFavoriteToAdd = jQuery( obj ).closest( '.favorites-add-file' );
	pFavoriteToAdd.html( '<i class="ui active inline loader tiny"></i> ' + __( 'Adding to favorites.' ) );

	var data = {
		action: 'favorites_uploadbulkaddtofavorites',
		type: type,
		file: file,
		copy: 'yes',
    nonce: security_nonces['mainwp-common-nonce']
	};
	jQuery.post(ajaxurl, data, function (pFavoriteToAdd) {
		return function (response) {
			if ( response == 'NEWER_EXISTED' ) {
				pFavoriteToAdd.html( __( 'Newer version exists. Adding to favorites skipped.' ) );
			} else if (response == 'SUCCESS') {
				pFavoriteToAdd.html( __( 'Added to favorites.' ) );
			} else {
        pFavoriteToAdd.html( __( 'Adding to favorites failed.' ) );
      }
		}
	}(pFavoriteToAdd));
	return false;
};
