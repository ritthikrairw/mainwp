jQuery( document ).ready(function($) {

	mainwp_uploader_upload_init = function () {
		jQuery( '#mainwp-message-zone' ).html( '' ).hide();
		jQuery( '#mainwp-message-zone' ).removeClass( "red green" );
	};

  jQuery( '.mainwp-file-uploader-delete-file' ).on( 'click', function() {
    var parent = jQuery( this ).closest( '.uploader-setting-upload-new-files' );
		var data = {
			action: 'mainwp_uploader_delete_file',
			filename: parent.find( '.qq-upload-file' ).attr( 'filename' ),
 			nonce: jQuery( '#uploader_nonce' ).val(),
    };

		parent.find( '.status' ).html( '<span data-tooltip="Removing the file..." data-inverted="" data-position="top left"><i class="notched circle loading icon"></i></span>' );

		jQuery.post( ajaxurl, data, function( response ) {
			parent.find( '.status' ).html( '' );
      if ( response && response['error'] ) {
        parent.find( '.status' ).html( '<span data-tooltip="' + response['error'] + '" data-inverted="" data-position="top left"><i class="ui red circle times icon"></i></span>' );
      } else if ( response && response['ok'] ) {
        window.location.reload();
      } else {
        parent.find( '.status' ).html( '<span data-tooltip="Undefined error occurred. Please, try again." data-inverted="" data-position="top left"><i class="ui red circle times icon"></i></span>' );
      }
		}, 'json');
		return false;
  } );

	jQuery( '#mainwp_uploader_btn_upload' ).on( 'click', function() {
		var errors = [];
		var selected_sites = [];
		var selected_groups = [];
		var selected_files = [];

		mainwp_uploader_upload_init();

		var fileUpload = jQuery( '.uploader-setting-upload-file[status="queue"]' );

		if ( fileUpload ) {
			jQuery( '.uploader-setting-upload-file[status="queue"]' ).each( function (i) {
				_file = $( this ).attr( 'filename' );
				selected_files.push( _file );
			} );
		}

		var newFileUpload = jQuery( '.uploader-setting-upload-new-files' );

		if ( newFileUpload ) {
			jQuery( '.uploader-setting-upload-new-files' ).each( function (i) {
				_file = $( this ).find( '.qq-upload-file' ).attr( 'filename' );
				selected_files.push( _file );
			} );
		}

		if ( selected_files.length == 0 ) {
			errors.push( __( 'There is no files to upload.', 'mainwp-file-uploader-extension' ) );
		}

		if ( jQuery( '#select_by' ).val() == 'site' ) {
			jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
				selected_sites.push( jQuery( this ).val() );
			} );
			if ( selected_sites.length == 0 ) {
				errors.push( __( 'Please select at least one website or group.', 'mainwp-file-uploader-extension' ) );
			}
		} else {
			jQuery( "input[name='selected_groups[]']:checked" ).each( function (i) {
				selected_groups.push( jQuery( this ).val() );
			} );
			if ( selected_groups.length == 0 ) {
				errors.push( __( 'Please select at least one website or group.', 'mainwp-file-uploader-extension' ) );
			}
		}

		if ( errors.length > 0 ) {
			jQuery( '#mainwp-message-zone' ).addClass( 'red' );
			jQuery( '#mainwp-message-zone' ).html( '<i class="icon close"></i>' + errors.join( '<br />' ) ).show();
			return;
		}

		var path = $( '#mainwp_uploader_select_path' ).val();

		if ( path !== '/' ) {
			path += '/';
		}

		if ( $( '#mainwp_uploader_path_option' ).val().trim() !== '' ) {
			path += $( '#mainwp_uploader_path_option' ).val().trim();
		}

		var data = {
			action:'mainwp_uploader_load_sites',
			path: path,
			'files[]': selected_files,
			'sites[]': selected_sites,
			'groups[]': selected_groups
		};

		$( '#mainwp-message-zone' ).html( '<i class="notched circle loading icon"></i> ' + 'Please wait...' ).show();
		jQuery.post(ajaxurl, data, function(response) {
			$( '#mainwp-message-zone' ).hide();
			if ( response === 'NOSITE' ) {
				$( '#mainwp-message-zone' ).html( 'Please select at least one website or group.' ).addClass( 'red' ).show();
			} else if (response === 'NOFILE') {
				$( '#mainwp-message-zone' ).html( 'No files to upload.' ).addClass( 'red' ).show();
			} else {
				$( '#mainwp-uploader-settings-inside' ).html( response );
				uploader_setting_upload_file_next();
			}
		} );
		return false;
	} );
} );

function mainwpUploaderCreateUploaderFile() {
	var uploader = new qq.FileUploader({
		element: document.getElementById( 'mainwp-uploader-file-uploader' ),
		action: location.href,
		template: '<div class="qq-uploader" data-tooltip="Upload a file that you want to send to your child sites." data-position="top left" data-inverted="">' +
							'<div class="qq-upload-button ui big labeled icon green button"><i class="upload icon"></i>Choose File</div>' +
							'<div class="ui raised green padded segment qq-upload-drop-area">Drop files here to upload</span></div>' +
							'<div class="ui divided list qq-upload-list"></div>' +
							'</div>',
		onSubmit: function() { jQuery( '.qq-upload-button' ).addClass( 'disabled' ) },
		onComplete: function( id, fileName, result ) { uploader_setting_uploadbulk_oncomplete( id, fileName, result ) },
		params: { uploader_do: 'UploaderInstallBulk-uploadfile' }

	});
}

uploader_setting_uploadbulk_oncomplete = function ( id, fileName, result ) {
	if ( result.success ) {
		if ( totalSuccess > 0 ) {
      jQuery( ".qq-upload-file" ).each( function ( i ) {
        var parent = jQuery( this ).closest( '.file-uploaded-item' );
        if ( parent.hasClass( 'qq-upload-success' ) ) {
          _file = jQuery( this ).attr( 'filename' );
          parent.find( '.qq-upload-msg-success' ).html( '<span class="uploader-setting-upload-file" status="queue" id="' + id + '" filename ="' + _file + '"><i class="green check icon"></i></span>' );
					window.location.reload();
				}
      } );
		}
	}
}

var uploader_CurrentThreads = 0;
var uploader_MaxThreads = 3;
var uploader_TotalThreads = 0;
var uploader_FinishedThreads = 0;
var uploader_errors = 0

uploader_setting_upload_file_next = function(){
	if ( uploader_TotalThreads == 0 ) {
		uploader_TotalThreads = jQuery( '.mainwpUploaderFileItem[status="queue"]' ).length;
	}
	while ( ( fileToUpload = jQuery( '.mainwpUploaderFileItem[status="queue"]:first' ) ) && ( fileToUpload.length > 0 )  && ( uploader_CurrentThreads < uploader_MaxThreads ) ) {
		uploader_setting_upload_file_start_specific( fileToUpload );
	}
}

uploader_setting_upload_file_start_specific = function ( pFileToUpload ) {

	uploader_CurrentThreads++;

	pFileToUpload.attr( 'status', 'progress' );
	pFileToUpload.find( '.status' ).html( '<span data-tooltip="Uploading the file. Please wait..." data-inverted="" data-position="left center"><i class="notched circle loading icon"></i></span>' );

	var data = {
		action:'mainwp_uploader_uploadbulk_file',
		siteId: pFileToUpload.closest( '.mainwpUploaderSiteItem' ).attr( 'siteid' ),
		filename: pFileToUpload.attr( 'filename' ),
		path: jQuery( '#mainwp_uploader_upload_file_path' ).val()
	};

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response == 'NOTEXIST' ) {
				pFileToUpload.find( '.status' ).html( '<span data-tooltip="Upload failed. File not found." data-inverted="" data-position="left center"><i class="icon red times"></i></span>' );
				uploader_errors++;
			} else if ( response == 'ERRORCREATEDIR' ) {
				pFileToUpload.find( '.status' ).html( '<span data-tooltip="Upload failed. Directory could not be created. Please review server permissiosn configuration." data-inverted="" data-position="left center"><i class="icon red times"></i></span>' );
				uploader_errors++;
			} else if ( response['success'] ) {
				pFileToUpload.find( '.status' ).html( '<span data-tooltip="Upload completed successfully." data-inverted="" data-position="left center"><i class="icon green check"></i></span>' );
			} else if ( response['error'] ) {
				pFileToUpload.find( '.status' ).html( '<span data-tooltip="Upload failed. Please make sure the site is connected properly." data-inverted="" data-position="left center"><i class="icon red times"></i></span>' );
				uploader_errors++;
			} else {
				pFileToUpload.find( '.status' ).html( '<span data-tooltip="Undefined error. Please try again." data-inverted="" data-position="left center"><i class="icon red times"></i></span>' );
				uploader_errors++;
			}
		} else {
			pFileToUpload.find( '.status' ).html( '<span data-tooltip="Undefined error. Please try again." data-inverted="" data-position="left center"><i class="icon red times"></i></span>' );
			uploader_errors++;
		}

		uploader_CurrentThreads--;
		uploader_FinishedThreads++;

		if ( uploader_FinishedThreads == uploader_TotalThreads && uploader_FinishedThreads != 0 ) {

			if ( uploader_errors > 0 ) {
				jQuery( '#mainwp-modal-message-zone' ).html( 'Process completed with error(s). Hover over the error icon <i class="red times icon"></i> to see the response from the child site.' ).addClass( 'ui red message' ).show();
			} else {
				jQuery( '#mainwp-modal-message-zone' ).html( 'All process completed successfully!' ).addClass( 'ui green message' ).show();
				setTimeout( function () {
					jQuery( '#mainwp-file-uploader-process-modal' ).modal( 'hide' );
					location.href = 'admin.php?page=Extensions-Mainwp-File-Uploader-Extension';
		    }, 3000 );
			}

			var data = {
				action:'mainwp_uploader_delete_temp_file',
				tmp_files: jQuery( '#mainwp_uploader_tmp_files_name' ).val()
			};

			jQuery.post( ajaxurl, data, function( response ) {

			} );

		}

		jQuery( '#mainwp-file-uploader-process-modal .mainwp-modal-progress' ).progress( { value: uploader_FinishedThreads, total:  uploader_TotalThreads } );
		jQuery( '#mainwp-file-uploader-process-modal .mainwp-modal-progress' ).find( '.label' ).html( uploader_FinishedThreads + '/' + uploader_TotalThreads + ' ' + __( 'Completed' ) );

		uploader_setting_upload_file_next();
	},'json');
};
