var commentCountSent = 0;
var commentCountReceived = 0;

jQuery( document ).ready( function () {

  if ( jQuery('#mainwp-coments-extension').length > 0 ) {
    jQuery( '#mainwp-coments-extension .ui.calendar' ).calendar({
      type: 'date',
      monthFirst: false,
      formatter: {
        date: function (date, settings) {
          if (!date) return '';
          var day = date.getDate();
          var month = date.getMonth() + 1;
          var year = date.getFullYear();
          return year + '-' + month + '-' + day;
        }
      }
    } );
  }

	// Trigger the fetch comments action
	jQuery( '#mainwp_show_comments' ).on( 'click', function () {
		mainwp_fetch_comments();
	} );

	// Trigger the Delete action
	jQuery( '.comment_submitdelete' ).on( 'click', function () {
		mainwpcomment_postAction( jQuery( this ), 'trash' );
		return false;
	} );

	// Trigger the Delete permanently action
	jQuery( '.comment_submitdelete_perm' ).on( 'click', function () {
		mainwpcomment_postAction( jQuery( this ), 'delete' );
		return false;
	} );

	// Trigger the Restore action
	jQuery( '.comment_submitrestore' ).on('click', function () {
		mainwpcomment_postAction( jQuery( this ), 'restore' );
		return false;
	} );

	// Trigger the Spam action
	jQuery( '.comment_submitspam' ).on('click', function () {
		mainwpcomment_postAction( jQuery( this ), 'spam' );
		return false;
	} );

	// Trigger the Unspam action
	jQuery( '.comment_submitunspam' ).on('click', function () {
		mainwpcomment_postAction( jQuery( this ), 'unspam' );
		return false;
	} );

	// Trigger the Approve action
	jQuery( '.comment_submitapprove' ).on('click', function () {
		mainwpcomment_postAction( jQuery( this ), 'approve' );
		return false;
	} );

	// Trigger the Unapprove action
	jQuery( '.comment_submitunapprove' ).on( 'click', function () {
		mainwpcomment_postAction( jQuery( this ), 'unapprove' );
		return false;
	} );

  // Check all checkboxes
  jQuery( '#mainwp-comments-table-wrapper table th input[type="checkbox"]' ).change( function () {
    var checkboxes = jQuery( '#mainwp-comments-table-wrapper table' ).find( 'input:checkbox' );
		if ( jQuery( this ).prop( 'checked' ) ) {
    	checkboxes.prop( 'checked', true );
    } else {
      checkboxes.prop( 'checked', false );
    }
	} );

	// Trigger bulk actions
	jQuery( '#mainwp_comments_bulk_action_apply' ).on( 'click', function () {

		var action = jQuery( '#mainwp-bulk-actions' ).val();

		if ( action == 'none' ) {
			return false;
		}

		var tmp = jQuery( "input[name='comment[]']:checked" );

		commentCountSent = tmp.length;

		tmp.each(
			function ( index, elem ) {
				mainwpcomment_postAction( elem, action );
			}
		);

		return false;
	} );

} );

// Show comments
mainwp_show_comments = function( siteId, postId ) {
	var siteElement = jQuery( 'input[name="selected_sites[]"][siteid="' + siteId + '"]' );
	siteElement.prop( 'checked', true );
	siteElement.trigger( "change" );
	mainwp_fetch_comments( postId );
};

// execute Comments actions (Approve, Unapprove, Spam, Unspam, ...)
mainwpcomment_postAction = function ( elem, what ) {

	var rowElement = jQuery( elem ).closest( '.mainwp-comment-item' );
	var commentId = rowElement.find( '.commentId' ).val();
	var websiteId = rowElement.find( '.websiteId' ).val();

	var data = {
		action:'mainwp_comment_' + what,
		commentId:commentId,
		websiteId:websiteId,
		security: mainwp_comments_security_nonces['mainwp_comment_' + what]
	};

	rowElement.html( '<td colspan="999"><i class="notched circle loading icon"></i> ' + __( 'Please wait...' ) + '</td>' );

	jQuery.post( ajaxurl, data, function ( response ) {
		if ( response.result ) {
			rowElement.html( '<td colspan="999">' + response.result + '</td>' );
      setTimeout( function () {
        rowElement.fadeOut();
      }, 3000 );
		}

		commentCountReceived++;

		if ( commentCountReceived == commentCountSent ) {
			commentCountReceived = 0;
			commentCountSent = 0;
		}
	}, 'json');

	return false;
};

// Fetch comments from selected sites
mainwp_fetch_comments = function ( postId ) {
	var errors = [];
	var selected_sites = [];
	var selected_groups = [];

	jQuery( '#mainwp-message-zone' ).html( '' ).hide();
	jQuery( '#mainwp-message-zone' ).removeClass( 'red green yellow' );

	if ( jQuery( '#select_by' ).val() == 'site' ) {
		jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
			selected_sites.push( jQuery( this ).val() );
		});
		if ( selected_sites.length == 0 ) {
			errors.push( __( 'Please select at least one website or group.' ) );
		}
	} else {
		jQuery( "input[name='selected_groups[]']:checked" ).each(function (i) {
			selected_groups.push( jQuery( this ).val() );
		});
		if ( selected_groups.length == 0 ) {
			errors.push( __( 'Please select at least one website or group.' ) );
		}
	}

	var status = "";
	var statuses = jQuery( '#mainwp_comment_search_type' ).dropdown( "get value" );

	if ( statuses == null )
		errors.push( __( 'Please select at least one comment status.' ) );
	else {
		status = statuses.join( ',' );
	}

	if ( errors.length > 0 ) {
		jQuery( '#mainwp-message-zone' ).html( errors.join( '<br />' ) ).show();
		jQuery( '#mainwp-message-zone' ).addClass( 'yellow' );
		return;
	}

	var data = {
		action:'mainwp_comments_search',
		keyword:jQuery( '#mainwp_comment_search_by_keyword' ).val(),
		dtsstart:jQuery( '#mainwp_comment_search_by_dtsstart' ).val(),
		dtsstop:jQuery( '#mainwp_comment_search_by_dtsstop' ).val(),
		status:status,
		'groups[]':selected_groups,
		'sites[]':selected_sites,
		postId: postId,
		security: mainwp_comments_security_nonces['mainwp_comments_search']
	};

	jQuery( '#mainwp-loading-comments-row' ).show();

	jQuery.post( ajaxurl, data, function ( response ) {
		response = jQuery.trim( response );
		jQuery( '#mainwp-loading-comments-row' ).hide();
		jQuery( '#mainwp-comments-table-wrapper' ).html( response );

    // Trigger the Delete action
  	jQuery( '.comment_submitdelete' ).on( 'click', function () {
  		mainwpcomment_postAction( jQuery( this ), 'trash' );
  		return false;
  	} );

    // Trigger the Delete permanently action
  	jQuery( '.comment_submitdelete_perm' ).on( 'click', function () {
  		mainwpcomment_postAction( jQuery( this ), 'delete' );
  		return false;
  	} );

  	// Trigger the Restore action
  	jQuery( '.comment_submitrestore' ).on('click', function () {
  		mainwpcomment_postAction( jQuery( this ), 'restore' );
  		return false;
  	} );

  	// Trigger the Spam action
  	jQuery( '.comment_submitspam' ).on('click', function () {
  		mainwpcomment_postAction( jQuery( this ), 'spam' );
  		return false;
  	} );

  	// Trigger the Unspam action
  	jQuery( '.comment_submitunspam' ).on('click', function () {
  		mainwpcomment_postAction( jQuery( this ), 'unspam' );
  		return false;
  	} );

  	// Trigger the Approve action
  	jQuery( '.comment_submitapprove' ).on('click', function () {
  		mainwpcomment_postAction( jQuery( this ), 'approve' );
  		return false;
  	} );

  	// Trigger the Unapprove action
  	jQuery( '.comment_submitunapprove' ).on( 'click', function () {
  		mainwpcomment_postAction( jQuery( this ), 'unapprove' );
  		return false;
  	} );

    // Check all checkboxes
    jQuery( '#mainwp-comments-table-wrapper table th input[type="checkbox"]' ).change( function () {
      var checkboxes = jQuery( '#mainwp-comments-table-wrapper table' ).find( 'input:checkbox' );
  		if ( jQuery( this ).prop( 'checked' ) ) {
      	checkboxes.prop( 'checked', true );
      } else {
        checkboxes.prop( 'checked', false );
      }
  	} );
  } );
};
