jQuery( document ).ready( function ($) {

  // Create new token
  jQuery( document ).on( 'click', '#mainwp-boilerplate-create-new-token', function (e) {
    var parent = jQuery( this ).parents( '#mainwp-boilerplate-new-token-modal' );
    var errors = [];

    if ( parent.find( 'input[name="token-name"]' ).val().trim() == '' ) {
      errors.push('Token name is required.');
    }

    if ( parent.find( 'input[name="token-description"]' ).val().trim() == '' ) {
      errors.push( 'Token description is required.' );
    }

    if ( errors.length > 0 ) {
      parent.find( '.ui.message' ).html( errors.join( '<br />' ) ).show();
      return false;
    }

    var fields = {
      token_name: parent.find( 'input[name="token-name"]' ).val(),
      token_description: parent.find( 'input[name="token-description"]' ).val(),
      action: 'mainwp_boilerplate_save_token'
    };

    parent.find( '.ui.message' ).html( '<i class="notched circle loading icon"></i> Saving token. Please wait...' ).show().removeClass( 'yellow' );

    $.post( ajaxurl, fields, function ( response ) {
      if ( response ) {
        if ( response['success'] ) {
          window.location.reload();
        } else {
          if ( response['error'] ) {
            parent.find( '.ui.message' ).html( response['error'] ).show().removeClass( 'yellow' ).addClass( 'red' );
          } else {
            parent.find( '.ui.message' ).html( 'Undefined error occurred. Please, try again.' ).show().removeClass( 'yellow' ).addClass( 'red' );
          }
        }
      } else {
        parent.find( '.ui.message' ).html( 'Undefined error occurred. Please, try again.' ).show().removeClass( 'yellow' ).addClass( 'red' );
      }

    }, 'json');
    return false;
  } );

  // Edit custom token
  jQuery(document).on('click', '#mainwp-boilerplate-edit-token', function (e) {
    var parent = jQuery( this ).closest( '.mainwp-token' );
    var token_name = parent.find( 'td.token-name' ).html();
    var token_description = parent.find( 'td.token-description' ).html();
    var token_id = parent.attr( 'token_id' );

    token_name = token_name.replace( /\[|\]/gi, "" );

    jQuery( '#mainwp-boilerplate-update-token-modal' ).modal( 'show' );

    jQuery( 'input[name="token-name"]' ).val( token_name );
    jQuery( 'input[name="token-description"]' ).val( token_description );
    jQuery( 'input[name="token-id"]' ).val( token_id );

    return false;
  } );

  // Update token
  jQuery( document ).on( 'click', '#mainwp-save-boilerplate-token', function (e) {
    var parent = jQuery( this ).parents( '#mainwp-boilerplate-update-token-modal' );
    var errors = [];

    if ( parent.find( 'input[name="token-name"]' ).val().trim() == '' ) {
      errors.push( 'Token name is required.' );
    }

    if ( parent.find( 'input[name="token-description"]' ).val().trim() == '' ) {
      errors.push( 'Token description is required.' );
    }

    if ( errors.length > 0 ) {
      parent.find( '.ui.message' ).html( errors.join( '<br />' ) ).show();
      return false;
    }

    var fields = {
      token_name: parent.find( 'input[name="token-name"]' ).val(),
      token_description: parent.find( 'input[name="token-description"]' ).val(),
      token_id: parent.find( 'input[name="token-id"]' ).val(),
      action: 'mainwp_boilerplate_save_token'
    };

    parent.find( '.ui.message' ).html( '<i class="notched circle loading icon"></i> Saving token. Please wait...' ).show().removeClass( 'yellow' );

    $.post( ajaxurl, fields, function ( response ) {
      if ( response ) {
        if ( response['success'] ) {
          window.location.reload();
        } else {
          if ( response['error'] ) {
            parent.find( '.ui.message' ).html( response['error'] ).show().removeClass( 'yellow' ).addClass( 'red' );
          } else {
            parent.find( '.ui.message' ).html( 'Undefined error occurred. Please try again.' ).show().removeClass( 'yellow' ).addClass( 'red' );
          }
        }
      } else {
        parent.find( '.ui.message' ).html( 'Undefined error occurred. Please try again.' ).show().removeClass( 'yellow' ).addClass( 'red' );
      }
    }, 'json' );
    return false;
  } );

  jQuery( '.mainwp-boilerplate-delete-post-action' ).on( 'click', function () {
    var post_id = jQuery( this ).attr( 'boilerplate-id' );
    mainwp_confirm( 'Are you sure you want to delete the boilerplate?', function () {
      jQuery( 'tr#post-' + post_id ).html( '<td colspan="3"><i class="notched circle loading icon"></i> Deleting boilerplate. Please wait...</td>' );
      jQuery.ajax( {
        url: ajaxurl,
        data: {
          action: 'mainwp_boilerplate_delete_post',
          post_id: post_id,
        },
        success: function ( data ) {
          data = jQuery.parseJSON( data );
          if ( data && data.success === true ) {
            jQuery( 'tr#post-' + post_id ).html( '<td colspan="3"><i class="green check icon"></i> Boilerplate deleted successfully!</td>' );
            setTimeout( function () {
              jQuery( 'tr#post-' + post_id ).fadeOut( 1000 );
            }, 2000 );
          } else {
            jQuery( 'tr#post-' + post_id ).html( '<td colspan="3"><i class="red times icon"></i> Boilerplate could not be deleted. Please, try again.</td>' );
          }
        },
        type: 'POST'
      } );
      return false;
    } );
  } );

  // Delete token
  jQuery( document ).on( 'click', '#mainwp-boilerplate-delete-token', function (e) {
    var parent = $( this ).closest( '.mainwp-token' );
    mainwp_confirm( 'Are you sure you want to delete the token?', function () {
      parent.html( '<td colspan="2"><i class="notched circle loading icon"></i> Deleting token. Please wait...</td>' );
      jQuery.post( ajaxurl, {
        action: 'mainwp_boilerplate_delete_token',
        token_id: parent.attr( 'token_id' )
      }, function ( data ) {
        if ( data && data.success ) {
          parent.html( '<td colspan="2"><i class="green check icon"></i> Token deleted successfully!</td>' );
          setTimeout( function () {
            parent.fadeOut( 1000 );
          }, 2000 );
        } else {
          parent.html( '<td colspan="2"><i class="red times icon"></i> Token could not be deleted. Please, try again.</td>' );
        }
      }, 'json');
      return false;
    } );
  } );
} );
