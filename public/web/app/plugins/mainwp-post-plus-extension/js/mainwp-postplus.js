jQuery( document ).ready( function( $ ) {

	// Trigger the publish action
	$( '.mainwp-post-plus-publish-action' ).on( 'click', function() {
		mainwp_pplus_draft_publish_start_specific( $( this ), false )
		return false;
	} );

	// Trigger the delete action
	$( '.mainwp-post-plus-delete-action' ).on('click', function () {
		if ( ! confirm( "Are you sure?" ) ) {
			return false;
		}
		mainwp_pplus_draft_delete_start_specific( $( this ), false )
		return false;
	} );

	// Trigger the bulk actions
	$( '#mainwp-post-plus-bulk-actions-button' ).on( 'click', function() {
		var bulk_act = $( '#mainwp-post-plus-bulk-actions' ).val();
		mainwp_pplus_draft_do_bulk_action( bulk_act );
	} );

	// Check all checkboxes
	jQuery( '#mainwp-post-plug-drafts-table th input[type="checkbox"]' ).change( function () {
		var checkboxes = jQuery( '#mainwp-post-plug-drafts-table' ).find( ':checkbox' );
		if ( jQuery( this ).prop( 'checked' ) ) {
    	checkboxes.prop( 'checked', true );
    } else {
      checkboxes.prop( 'checked', false );
    }
	} );



	$( '#pplus_meta_privelege_check_all' ).on('click', function() {
		var chk = $( this ).is( ':checked' ) ? true : false;
		$( '.pplus_meta_privelege_group' ).find( 'input[type=checkbox]' ).each(function(){
			$( this ).prop( 'checked', chk );
			if ( ! chk) {
				$( this ).attr( 'disabled', true ); } else {
				$( this ).attr( 'disabled', false ); }
		});
	});

});

var pplus_bulkMaxThreads = 3;
var pplus_bulkTotalThreads = 0;
var pplus_bulkCurrentThreads = 0;
var pplus_bulkFinishedThreads = 0;

// Init bulk actions
mainwp_pplus_draft_bulk_action_init = function() {
	pplus_bulkMaxThreads = 3;
	pplus_bulkTotalThreads = 0;
	pplus_bulkCurrentThreads = 0;
	pplus_bulkFinishedThreads = 0;
}

// Trigger bulk actions
mainwp_pplus_draft_do_bulk_action = function( act ) {
	mainwp_pplus_draft_bulk_action_init();
	var selector = 'td input[type="checkbox"]:checked';
	jQuery( selector ).addClass( 'queue' );
	switch ( act ) {
		case 'publish-selected':
			mainwp_pplus_draft_publish_start_next( selector );
			break;
		case 'delete-selected':
			if ( ! confirm( "Are you sure?" ) ) {
				return false;
			}
			mainwp_pplus_draft_delete_start_next( selector );
			break;
		case 'preview-selected':
			mainwp_pplus_draft_preview_open( selector );
			break;
	}
}

// Loop through drafts to publish
mainwp_pplus_draft_publish_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( pplus_bulkCurrentThreads < pplus_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		mainwp_pplus_draft_publish_start_specific( objProcess, true, selector );
	}
}

// Publish drafts
mainwp_pplus_draft_publish_start_specific = function( pObj, bulk, selector ) {
	var row = pObj.closest( 'tr' );
	var data = {
		action: 'mainwp_pplus_publish_post',
		postId: row.attr( 'post-id' )
	}

	if ( bulk ) {
		pplus_bulkCurrentThreads++;
	}

	row.html( '<td colspan="999"><i class="notched circle loading icon"></i> ' + __( 'Publishing. Please wait...' ) + '</td>');

	jQuery.post( ajaxurl, data, function ( response ) {
		if ( response ) {
			if ( response['result'] ) {
				if ( response['failed_posts'] && response['failed_posts'] > 0 ) {
					row.html( '<td colspan="999"><i class="red times icon"></i> ' + response['result'] + '</td>' );
				} else {
					row.html( '<td colspan="999">' + response['result'] + '</td>' );
				}
			} else if ( response['error'] ) {
				row.html( '<td colspan="999"><i class="red times icon"></i> ' + response['error'] + '</td>' );
			} else {
				row.html( '<td colspan="999"><i class="red times icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) + '</td>' );
			}
		} else {
			row.html( '<td colspan="999"><i class="red times icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) + '</td>' );
		}

		if ( bulk ) {
			pplus_bulkCurrentThreads--;
			pplus_bulkFinishedThreads++;
			mainwp_pplus_draft_publish_start_next( selector );
		}
	}, 'json');
	return false;
}

// Loop drafts to delete
mainwp_pplus_draft_delete_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( pplus_bulkCurrentThreads < pplus_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		mainwp_pplus_draft_delete_start_specific( objProcess, true, selector );
	}
}

// Delete drafts
mainwp_pplus_draft_delete_start_specific = function( pObj, bulk, selector ) {
	var row = pObj.closest( 'tr' );
	var data = {
		action: 'mainwp_pplus_delete_post',
		postId: row.attr( 'post-id' )
	}

	if ( bulk ) {
		pplus_bulkCurrentThreads++;
	}

	row.html( '<td colspan="999"><i class="notched circle loading icon"></i> ' + __( 'Deleting. Please wait...' ) + '</td>');

	jQuery.post( ajaxurl, data, function ( response ) {
		if ( response == 'success' ) {
			row.fadeOut();
		} else {
			row.html( '<td colspan="999"><i class="red times icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) + '</td>' );
		}

		if ( bulk ) {
			pplus_bulkCurrentThreads--;
			pplus_bulkFinishedThreads++;
			mainwp_pplus_draft_delete_start_next( selector );
		}
	} );
	return false;
}

// Open preview
mainwp_pplus_draft_preview_open = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) ) {
		objProcess.removeClass( 'queue' );
		var row = objProcess.closest( 'tr' );
		var link = row.find( '.mainwp-post-plus-preview-action' ).attr( 'href' );
		window.open( link, '_blank' );
	}
}
