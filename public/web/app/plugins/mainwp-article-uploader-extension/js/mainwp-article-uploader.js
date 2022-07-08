var aup_post_type = '';
var aup_allowed_comment = '';
var aup_categories = '';
var aup_selected_sites = [];
var aup_selected_groups = [];

jQuery( document ).ready( function($) {

	// Trigger the upload/import modals
	$( '#mainwp-article-uploader-upload-button' ).on( 'click', function() {
		$( '#mainwp-article-uploader-upload-modal' ).modal( 'show' );
	} );

	$( '#mainwp-article-uploader-import-button' ).on( 'click', function() {
		$( '#mainwp-article-uploader-import-modal' ).modal( 'show' );
	} );

	// Hide modal and reload page
	$( '#mainwp-article-uploader-publishing-modal .cancel' ).on( 'click', function() {
		$( '#mainwp-article-uploader-publishing-modal' ).modal( 'hide' );
		window.location.reload();
	} );

	// Show/Hide Drip action link
	$( '#aup_use_post_dripper_import' ).change( function() {
		if ( $( this ).is( ':checked' ) ) {
			jQuery( '.aup_posts_list_drip_post' ).show();
		} else {
			jQuery( '.aup_posts_list_drip_post' ).hide();
		}
	} );

	// Show/Hide drip options
	$( '#aup_use_post_dripper_upload' ).change(function(){
		if ($( this ).is( ':checked' )) {
			jQuery( '#aup_upload_drip_articles_btn_wrapper' ).show();
			jQuery( '#aup_upload_publish_article_btn' ).hide();
		} else {
			jQuery( '#aup_upload_drip_articles_btn_wrapper' ).hide();
			jQuery( '#aup_upload_publish_article_btn' ).show();
		}
	} );

	// Trigger the single imported article process
	$( '.aup_posts_list_publish_post' ).on( 'click', function () {
		mainwp_aup_publish_start_specific( $( this ), false );
		return false;
	} );

	// Trigger the delete imported article process
	$( '.aup_posts_list_delete_post' ).on( 'click', function () {
		if ( ! confirm( "Are you sure?" )) {
			return false;
		}
		mainwp_aup_delete_start_specific( $( this ), false )
		return false;
	} );

	/////////////////// TO DO ////////////////////
	$( '.aup_posts_list_drip_post' ).on('click', function(){
		aup_import_articles_drip_start_specific( $( this ), false );
		return false;
	})

	/////////////////// TO DO ////////////////////
	$( '#aup_upload_drip_articles_btn' ).on('click', function(){
		var errors = [];
		var selected_sites = [];
		var selected_groups = [];
		$( '#selected_sites' ).removeClass( 'form-invalid' );
		$( this ).attr( 'disabled', 'disabled' );

		$( '#mainwp_aup_pulish_ajax_error_zone' ).hide();
		var workingEl = jQuery( '#aup_actions_working' );
		var statusEl = workingEl.find( '.status' );
		statusEl.hide();

		if ($( '#aup_upload_list_data' ).attr( 'count_articles' ) <= 0) {
			 errors.push( __( "No articles have been found." ) );
		}

		if ($( '#select_by' ).val() == 'site') {
			$( "input[name='selected_sites[]']:checked" ).each(function (i) {
				selected_sites.push( $( this ).val() );
			});
		} else {
			$( "input[name='selected_groups[]']:checked" ).each(function (i) {
				selected_groups.push( $( this ).val() );
			});
		}

		if ( selected_groups.length == 0 && selected_sites.length == 0) {
			errors.push( __( "Please select websites or groups." ) );
			$( '#selected_sites' ).addClass( 'form-invalid' );
		}

		if (errors.length > 0) {
			$( '#mainwp_aup_pulish_ajax_error_zone' ).html( errors.join( '<br />' ) ).show();
			$( this ).removeAttr( 'disabled' );
			return false;
		}

		aup_post_type = jQuery( 'select[name="aup_select_publish_type"]' ).val();
		aup_allowed_comment = jQuery( 'select[name="aup_select_allowed_comment"]' ).val();
		aup_categories = jQuery( 'input[name="aup_publish_categories"]' ).val();

		aup_selected_sites = selected_sites;
		aup_selected_groups = selected_groups;

		aup_bulkTotalThreads = 0;
		aup_bulkCurrentThreads = 0;
		aup_bulkFinishedThreads = 0;
		aup_upload_articles_drip_start_next();
		return false;
	})

	// Confirm cancelling all articles
	$( '#aup_upload_cancel_all' ).on( 'click', function () {
		if ( ! confirm( "Are you sure?" )) {
			return false;
		}
	} );

	// Publish uploaded articles
	$( '#aup_upload_publish_article_btn' ).on( 'click', function () {
		var errors = [];
		var selected_sites = [];
		var selected_groups = [];

		$( '#mainwp-message-zone' ).html( '' ).hide();
		$( '#mainwp-message-zone' ).removeClass( 'green yellow red' );

		if ( $( '#aup_upload_list_data' ).attr( 'count_articles' ) <= 0) {
			errors.push( __( 'No articles found.' ) );
		}

		if ( $( '#select_by' ).val() == 'site' ) {
			$( "input[name='selected_sites[]']:checked" ).each( function (i) {
				selected_sites.push( $( this ).val() );
			} );
		} else {
			$( "input[name='selected_groups[]']:checked" ).each( function (i) {
				selected_groups.push( $( this ).val() );
			} );
		}

		if ( selected_groups.length == 0 && selected_sites.length == 0 ) {
			errors.push( __( "Please select at least one website or group." ) );
		}

		if ( errors.length > 0 ) {
			$( '#mainwp-message-zone' ).html( '<i class="close icon"></i> ' + errors.join( '<br />' ) ).show();
			$( '#mainwp-message-zone' ).addClass( 'yellow' );
			return false;
		}

		aup_post_type = jQuery( 'select[name="aup_select_publish_type"]' ).val();
		aup_allowed_comment = jQuery( 'select[name="aup_select_allowed_comment"]' ).val();
		aup_categories = jQuery( 'input[name="aup_publish_categories"]' ).val();
		aup_selected_sites = selected_sites;
		aup_selected_groups = selected_groups;

		var data = {
			action: 'mainwp_article_uploader_publish_loading',
		}

		$( '#mainwp-message-zone' ).html( '<i class="notched circle loading icon"></i> ' + __( 'Publishing articles. Please wait...' ) ).show();

		jQuery.post( ajaxurl, data, function ( response ) {
			$( '#mainwp-message-zone' ).html( '' ).hide();
			if ( response ) {
				if ( response.error ) {
					$( '#mainwp-message-zone' ).html( '<i class="close icon"></i> ' + response.error ).show();
					$( '#mainwp-message-zone' ).addClass( 'red' );
				} else if (response.status == 'OK') {
					$( '#mainwp-article-uploader-publishing-modal' ).modal( 'show' );
					$( '#mainwp-article-uploader-publishing-modal' ).find( '.content' ).html( response.result );
					aup_bulkTotalThreads = 0;
					aup_bulkCurrentThreads = 0;
					aup_bulkFinishedThreads = 0;
					aup_upload_articles_start_next();
				} else {
					$( '#mainwp-message-zone' ).html( '<i class="close icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) ).show();
					$( '#mainwp-message-zone' ).addClass( 'red' );
				}
			} else {
				$( '#mainwp-message-zone' ).html( '<i class="close icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) ).show();
				$( '#mainwp-message-zone' ).addClass( 'red' );
			}
		}, 'json');
	} );

	// Remove categories field if "Post" post type selected
	$( 'select[name="aup_select_publish_type"]' ).on( 'change', function() {
		if ( $( this ).val() == 'bulkpage' ) {
			$( 'input[name="aup_publish_categories"]' ).val( "" ).attr( 'disabled', 'disabled' );
		} else {
			$( 'input[name="aup_publish_categories"]' ).removeAttr( 'disabled' );
		}
	} );

	// Trigger the Delete uploaded article processs
	$( '.aup_list_publish_delete_post' ).on( 'click', function () {
		if ( ! confirm( "Are you sure?" ) ) {
			return false;
		}
		mainwp_aup_delete_start_specific( $( this ), false );
		return false;
	} );

	// Check all checkboxes
	jQuery( '#mainwp-article-uploader-articles-table th input[type="checkbox"]' ).change( function () {
		var checkboxes = jQuery( '#mainwp-article-uploader-articles-table' ).find( ':checkbox' );
		if ( jQuery( this ).prop( 'checked' ) ) {
    	checkboxes.prop( 'checked', true );
    } else {
      checkboxes.prop( 'checked', false );
    }
	});

	// Trigger the bulk actions
	$( '#mainwp_article_uploader_action_apply' ).on( 'click', function() {
		var bulk_act = $( '#mainwp_bulk_action' ).val();
		mainwp_aup_do_bulk_action( bulk_act );
	} );

	// Trigger the upload articles button
	jQuery( '.aup_upload_articles_btn' ).on( 'click', function() {
		var master = $( this ).closest( '.mainwp-aup-uploader-content' );
		var type = master.attr( 'type' );
		var selected_files = [];
		var fileUpload = master.find( '.qq-upload-file' );

		if ( fileUpload ) {
			master.find( '.qq-upload-file' ).each( function (i) {
				_file = $( this ).attr( 'filename' );
				selected_files.push( _file );
			});
		}

		if ( selected_files.length > 0 ) {
			aup_bulkTotalThreads = 0;
			aup_bulkCurrentThreads = 0;
			aup_bulkFinishedThreads = 0;
			aup_upload_file_next( master, type );
		} else {
			return false;
		}

		return false;
	});
});

var aup_bulkMaxThreads = 3;
var aup_bulkMaxImportThreads = 1;
var aup_bulkTotalThreads = 0;
var aup_bulkCurrentThreads = 0;
var aup_bulkFinishedThreads = 0;

// Init bulk actions
mainwp_aup_bulk_action_init = function() {
	aup_bulkMaxThreads = 3;
	aup_bulkTotalThreads = 0;
	aup_bulkCurrentThreads = 0;
	aup_bulkFinishedThreads = 0;
}

mainwp_aup_do_bulk_action = function( act ) {
	mainwp_aup_bulk_action_init();
	var selector = '#mainwp-article-uploader-articles-table tbody tr td.checkbox input[type="checkbox"]:checked';
	jQuery( selector ).addClass( 'queue' );
	switch ( act ) {
		case 'publish':
			mainwp_aup_publish_start_next( selector );
			break;
		case 'drip':
			aup_import_articles_drip_start_next( selector );
			break;
		case 'delete':
			if ( ! confirm( "Are you sure?" )) {
				return false;
			}
			mainwp_aup_delete_start_next( selector );
			break;
	}
}

// Loop through publish process
mainwp_aup_publish_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( aup_bulkCurrentThreads < aup_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		mainwp_aup_publish_start_specific( objProcess, true, selector );
	}
}

// Publish specific
mainwp_aup_publish_start_specific = function( pObj, bulk, selector ) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent;
	var data = {
		action: 'mainwp_article_uploader_publish_post',
		postId: parent.attr( 'post-id' )
	}

	if ( bulk ) {
		aup_bulkCurrentThreads++;
	}

	statusEl.html( '<td colspan="999"><i class="notched circle loading icon"></i> ' + __( 'Publishing. Please wait...' ) + '</td>' );
	jQuery.post(ajaxurl, data, function (response) {
		if ( response ) {
			if ( response['result'] ) {
				statusEl.html( '<td colspan="999">' + response['result'] + '</td>' );
			} else if ( response['error'] ) {
				statusEl.html( '<td colspan="999">' + response['error'] + '</td>' );
			} else {
				statusEl.html( '<td colspan="999"><i class="red times circle icon"></i>' + __( 'Undefined error occurred. Please, try again.' ) + '</td>' );
			}
		} else {
			statusEl.html( '<td colspan="999"><i class="red times circle icon"></i>' + __( 'Undefined error occurred. Please, try again.' ) + '</td>' );
		}
		if ( bulk ) {
			aup_bulkCurrentThreads--;
			aup_bulkFinishedThreads++;
			mainwp_aup_publish_start_next( selector );
		}
	}, 'json');
	return false;
}

// Loop through articles to delete
mainwp_aup_delete_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( aup_bulkCurrentThreads < aup_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		mainwp_aup_delete_start_specific( objProcess, true, selector );
	}
}

aup_import_articles_drip_start_next = function(selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (aup_bulkCurrentThreads < aup_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		aup_import_articles_drip_start_specific( objProcess, true, selector );
	}
}

aup_import_articles_drip_start_specific = function(pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var workingEl = parent.find( '.row-working' );
	var statusEl = workingEl.find( '.status' );
	var master = jQuery( '#aup_import_dripper_options' ).closest( '.aup-dripper-options' );
	var data = {
		action: 'mainwp_article_uploader_drip_post',
		postId: parent.attr( 'post-id' ),
		type: 'import',
		number_sites: master.find( '.aup_dripper_sites_number' ).val(),
		number_time: master.find( '.aup_dripper_time_number' ).val(),
		time_select: master.find( '.aup_dripper_select_time' ).val(),
	}

	if (bulk) {
		aup_bulkCurrentThreads++; }

	workingEl.find( 'i' ).show();
	statusEl.hide();
	jQuery.post(ajaxurl, data, function (response) {
		workingEl.find( 'i' ).hide();
		if ( ! response) {
			statusEl.html( __( 'Undefined error.' ) ).show();
			statusEl.css( 'color', 'red' );
		} else {
			if (response.success) {
				parent.html( '<td colspan="8">Item has been drip.</td>' );
			} else if (response.error) {
				statusEl.html( response.error ).show();
				statusEl.css( 'color', 'red' );
			} else {
				statusEl.html( __( 'Drip failed' ).show() );
				statusEl.css( 'color', 'red' );
			}
		}
		if (bulk) {
			aup_bulkCurrentThreads--;
			aup_bulkFinishedThreads++;
			aup_import_articles_drip_start_next( selector );
		}
	}, 'json');
	return false;
}

// Delete uploaded/imported article from the table
mainwp_aup_delete_start_specific = function( pObj, bulk, selector ) {
	var parent = pObj.closest( 'tr' );

	var data = {
		action: 'mainwp_article_uploader_delete_post',
		postId: parent.attr( 'post-id' )
	}

	if ( bulk ) {
		aup_bulkCurrentThreads++;
	}

	pObj.attr( 'disabled', 'disabled' );

	jQuery.post(ajaxurl, data, function (response) {
		if ( response.success ) {
			parent.remove();
		} else {
			parent.html( '<td colspan="999">' + __( 'Deleting failed. Please, refresh the screen and try again.' ) + '</td>' );
			pObj.removeAttr( 'disabled' );
		}

		if ( bulk ) {
			aup_bulkCurrentThreads--;
			aup_bulkFinishedThreads++;
			mainwp_aup_delete_start_next( selector );
		}

	}, 'json');
	return false;
}

// Create uploader buttons
function mainwpArticleUploaderCreateUploaderFile() {
	new qq.FileUploader( {
		element: document.getElementById( 'mainwp-article-uploader-file-uploader' ),
		action: location.href,
		template:
			'<div class="qq-uploader">' +
			'<div class="ui secondary center aligned segment">' +
			'<div class="qq-upload-button ui inline button">Choose File or Drop files here</div>' +
			'<div class="qq-upload-drop-area">Drop here.</div>' +
			'</div>' +
			'</div>' +
			'<ul class="qq-upload-list ui relaxed divided list"></ul>',
		onComplete: function( id, fileName, result ){ aup_uploader_uploadbulk_oncomplete( id, fileName, result ) },
		params: { uploader_do: 'ArticleUploader-uploadfile' }
	} );
}

// Create uploader buttons
function mainwpArticleUploaderCreateUploaderFile2() {
	new qq.FileUploader( {
		element: document.getElementById( 'mainwp-article-uploader-file-uploader2' ),
		action: location.href,
		template:
		'<div class="qq-uploader">' +
		'<div class="ui secondary center aligned segment">' +
		'<div class="qq-upload-button ui inline button">Choose File or Drop files here</div>' +
		'<div class="qq-upload-drop-area">Drop here.</div>' +
		'</div>' +
		'</div>' +
		'<ul class="qq-upload-list ui relaxed divided list"></ul>',
		onComplete: function( id, fileName, result ){ aup_uploader_uploadbulk_oncomplete( id, fileName, result ) },
		params: { uploader_do: 'ArticleImport-uploadfile' }
	} );
}

aup_uploader_uploadbulk_oncomplete = function ( id, fileName, result ) {
	if ( result.success ) {
		if ( totalSuccess > 0 ) { // global variable
			jQuery( ".qq-upload-file" ).each( function (i) {
				if ( jQuery( this ).parent().attr( 'class' ) && jQuery( this ).parent().attr( 'class' ).replace( /^\s+|\s+$/g, "" ) == 'qq-upload-success' ) {
					_file = jQuery( this ).attr( 'filename' );
					if (jQuery( this ).next().next().attr( 'class' ) != 'aup-upload-file') {
						jQuery( this ).next().after( '<span class="aup-upload-file" status="queue" id="' + id + '" filename ="' + _file + '">&nbsp;&nbsp;<span class="status">' + __( 'Ready to import ...' ) + '</span></span> ' );
					}
				}
			});
		}
	}
}

// Loop through files
aup_upload_file_next = function(master, type){
	if ( aup_bulkTotalThreads == 0 ) {
		aup_bulkTotalThreads = master.find( '.qq-upload-file' ).length;
	}
	while ( ( fileToUpload = master.find( '.qq-upload-file:not([status="done"]):first' ) ) && ( fileToUpload.length > 0 )  && ( aup_bulkCurrentThreads < aup_bulkMaxImportThreads ) ) {
		aup_upload_file_start_specific( master, fileToUpload, type );
	}
}

// Upload files
aup_upload_file_start_specific = function ( master, pFileToUpload, pType ) {
	aup_bulkCurrentThreads++;
	pFileToUpload.attr( 'status', 'done' );
	var statusEl = pFileToUpload.closest( '.row' ).find( '.column:last' );
	statusEl.html( '<i class="notched circle loading icon"></i>' );
	var data = {
		action:'mainwp_article_uploader_import_articles',
		filename: pFileToUpload.attr( 'filename' ),
		type: pType
	};

	jQuery.post( ajaxurl, data, function( response ) {
		var _error = false;
		if ( response ) {
			if ( response['success'] ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else if ( response['error'] ) {
				statusEl.html( '<i class="red times icon"></i> ' + response['error'] );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		aup_bulkCurrentThreads--;
		aup_bulkFinishedThreads++;

		if ( aup_bulkFinishedThreads == aup_bulkTotalThreads && aup_bulkFinishedThreads != 0 ) {
			setTimeout( function() {
				location.href = 'admin.php?page=Extensions-Mainwp-Article-Uploader-Extension&tab=' + pType;
			}, 1000 );
		}

		aup_upload_file_next( master, pType );
	},'json');
};

// Loop throuh all articles/sites
aup_upload_articles_start_next = function() {
	if (aup_bulkTotalThreads == 0) {
		aup_bulkTotalThreads = jQuery( '.aup_upload_articles_item[status="queue"]' ).length;
	}
	while ( ( itemToProcess = jQuery( '.aup_upload_articles_item[status="queue"]:first' ) ) && ( itemToProcess.length > 0 )  && ( aup_bulkCurrentThreads < aup_bulkMaxImportThreads ) ) {
		aup_upload_articles_start_specific( itemToProcess );
	}
}

// Publish
aup_upload_articles_start_specific = function ( pItemToProcess ) {
	aup_bulkCurrentThreads++;
	pItemToProcess.attr( 'status', 'progress' );
	var statusEl = pItemToProcess.find( '.status' );

	var data = {
		action:'mainwp_article_uploader_perform_publish_articles',
		post_type: aup_post_type,
		allowed_comment: aup_allowed_comment,
		categories: aup_categories,
		post_id: pItemToProcess.attr( 'post_id' ),
		sites: aup_selected_sites,
		groups: aup_selected_groups
	};

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		pItemToProcess.attr( 'status', 'done' );
		if ( response ) {
			if ( response['status'] == 'OK' ) {
				statusEl.html( response['result'] );
			} else if ( response['error'] ) {
				statusEl.html( response['error'] );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		aup_bulkCurrentThreads--;
		aup_bulkFinishedThreads++;

		if ( aup_bulkFinishedThreads == aup_bulkTotalThreads && aup_bulkFinishedThreads != 0) {
		return false;
		}

		aup_upload_articles_start_next();
	},'json');
};

aup_upload_articles_drip_start_next = function() {
	if (aup_bulkTotalThreads == 0) {
		aup_bulkTotalThreads = jQuery( '.aup_upload_list_articles_item[status="queue"]' ).length;
	}
	while ((itemToProcess = jQuery( '.aup_upload_list_articles_item[status="queue"]:first' )) && (itemToProcess.length > 0)  && (aup_bulkCurrentThreads < aup_bulkMaxImportThreads)) {
		aup_upload_articles_drip_start_specific( itemToProcess );
	}
}

///////// TO DO ///////////////
aup_upload_articles_drip_start_specific = function (pItemToProcess)
{
	aup_bulkCurrentThreads++;
	pItemToProcess.attr( 'status', 'progress' );
	var statusEl = pItemToProcess.find( '.status' );
	var master = jQuery( '#aup_upload_dripper_options' ).closest( '.aup-dripper-options' );
	var data = {
		action: 'mainwp_article_uploader_drip_post',
		postId: pItemToProcess.attr( 'post-id' ),
		type: 'upload',
		number_sites: master.find( '.aup_dripper_sites_number' ).val(),
		number_time: master.find( '.aup_dripper_time_number' ).val(),
		time_select: master.find( '.aup_dripper_select_time' ).val(),
		post_type: aup_post_type,
		allowed_comment: aup_allowed_comment,
		categories: aup_categories,
		sites: aup_selected_sites,
		groups: aup_selected_groups
	};

	statusEl.html( __( 'running ...' ) );
	pItemToProcess.find( 'i' ).show();
	jQuery.post(ajaxurl, data, function(response) {
		pItemToProcess.attr( 'status', 'done' );
		pItemToProcess.find( 'i' ).hide();
		if (response) {
			if (response.success) {
				pItemToProcess.html( '<td colspan="2">Item has been drip.</td>' );
			} else if (response.error) {
				statusEl.html( response.error ).show();
				statusEl.css( 'color', 'red' );
			} else {
				statusEl.html( __( 'Drip failed' ).show() );
				statusEl.css( 'color', 'red' );
			}
		} else {
			statusEl.html( __( 'Undefined Error.' ) );
			statusEl.css( 'color', 'red' );
		}
		aup_bulkCurrentThreads--;
		aup_bulkFinishedThreads++;

		if (aup_bulkFinishedThreads == aup_bulkTotalThreads && aup_bulkFinishedThreads != 0) {
			jQuery( '#mainwp_aup_publish_ajax_message_zone' ).html( 'Drip Articles finished.' ).fadeIn( 100 );
			jQuery( '#aup_upload_drip_articles_btn' ).removeAttr( 'disabled' );
		}
		aup_upload_articles_drip_start_next();

	},'json');
};
