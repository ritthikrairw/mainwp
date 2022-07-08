jQuery(document).ready(function ($) {

  $( '#virusdie_btn_display' ).on( 'click', function () {
		$( this ).closest( 'form' ).submit();
	} );

	// Filter groups
	$( '#mainwp_virusdie_groups_select' ).dropdown( {
		onChange: function ( group_id ) {
			var url = 'admin.php?page=Extensions-Mainwp-Virusdie-Extension';
			if ( group_id > 0 )
				url += '&group_id=' + group_id;
			location.href = url;
		}
	} );

	$( '#virusdie_signout_btn' ).on( 'click', function () {
		if ( confirm('If you Sign Out from the Virusdie account, you will have to sign up again to get the one-time password. Are you sure you want to proceed?' ) ) {
			$( '#mainwp_virusdie_form_action' ).val('signout');
			$(this).closest("form").submit();
	    } else {
			$( '#mainwp_virusdie_form_action' ).val('');
		}
		return false;
	} );

  // Trigger the CAT modal after clicking the CLEAN UP button
	$( '.mainwp-virusdie-clean-up' ).on( 'click', function () {
		$( '#mainwp-virusdie-sign-up-modal' ).modal( {
			closable: true,
			onHide: function () {
				location.reload();
			}
		} ).modal( 'show' );
	} );

	// Trigger the Scan process after clikcing the Scan button
	jQuery(document).on( 'click', '.mainwp-virusdie-row-scan-site', function () {
		mainwp_virusdie_row_scan_site( this, false );
		return false;
	} );

	// Trigger the Remove process after clikcing the button
	jQuery(document).on( 'click', '.mainwp-virusdie-row-remove-site', function () {
		if ( !confirm( 'Are you sure you want to remove the site from your Virusdie account?' ) )
			return false;
		mainwp_virusdie_row_remove_site( this );
		return false;
	} );

	// Trigger the set option process after clikcing the button
	$( '.mainwp-virusdie-row-set-site-option' ).on( 'click', function () {
		mainwp_virusdie_row_set_site_option( this );
		return false;
	} );


	// Remove site from the Virusdie account (Virusdie Extension page)
	mainwp_virusdie_row_set_site_option = function ( pObj ) {
		var data = {
			action: 'mainwp_virusdie_setoption_site',
			virusdie_id: $( pObj ).closest( 'tr' ).attr( 'virusdie-item-id' ),
			opt_name: $( pObj ).attr( 'opt-name' ),
			opt_value: $( pObj ).attr( 'opt-new-value' ),
			wp_nonce: $( 'input[name="mainwp_virusdie_nonce"]' ).val(),
		}

		jQuery( "#mainwp-virusdie-scan-modal" ).modal( {
			closable: false,
			onHide: function () {
				location.reload();
			}
		} ).modal( 'show' );

		jQuery( "#mainwp-virusdie-scan-modal" ).find( '.ui.loader' ).html( 'Running...' );

		var statusEl = $( '.ui.dimmer' );
		statusEl.addClass( 'active' );

		jQuery.post( ajaxurl, data, function ( response ) {
			$( '#mainwp-virusdie-scan-modal' ).find( '.content' ).html( response );
		} );
	}


	// Trigger the add site process after clikcing the button
	jQuery(document).on( 'click', '.mainwp-virusdie-row-add-site', function () {

		if ( jQuery( this ).prop( 'disabled' ) ) {
			return false;
		}

		var site_id = $( this ).closest( 'tr' ).attr( 'site-id' );
		if ( ! site_id )
			return false;

		if ( !confirm( 'In order to connect your websites to your Virusdie account, extension needs to place your unique sync file to the root directory of child sites. Are you sure you want to proceed?' ) )
			return false;

		jQuery( this ).attr( 'disabled', 'disabled' );
		mainwp_virusdie_action_popup( 'add_site', site_id );
		return false;
	} );

	// Scan site (Virusdie Extension page)
	mainwp_virusdie_row_scan_site = function ( pObj, retring ) {

		var data = {
			action: 'mainwp_virusdie_security_scan',
			virusdie_id: $( pObj ).closest( 'tr' ).attr( 'virusdie-item-id' ),
			wp_nonce: $( 'input[name="mainwp_virusdie_nonce"]' ).val(),
		}

		jQuery( "#mainwp-virusdie-scan-modal" ).modal( {
			closable: false,
			onHide: function () {
				location.reload();
			}
		} ).modal( 'show' );

		var statusEl = $( '.ui.dimmer' );

		statusEl.addClass( 'active' );

		if ( retring == true ) {
			statusEl.find( ".ui.text" ).html( ' ' + __( "Connection error detected. The Verify Certificate option has been switched to NO. Retrying..." ) );
		}

		jQuery.post( ajaxurl, data, function ( response ) {
			if ( response == 'retry_action' ) {
				jQuery( "#mainwp_virusdie_verify_certificate" ).val( 0 );
				mainwp_virusdie_row_scan_site( pObj, true );
			} else {
				$( '#mainwp-virusdie-scan-modal' ).find( '.content' ).html( '' );
				$( '#mainwp-virusdie-scan-modal' ).find( '.content' ).html( response );
			}
		} );
	}

	// Remove site from the Virusdie account (Virusdie Extension page)
	mainwp_virusdie_row_remove_site = function ( pObj ) {
		var data = {
			action: 'mainwp_virusdie_remove_site',
			virusdie_id: $( pObj ).closest( 'tr' ).attr( 'virusdie-item-id' ),
			wp_nonce: $( 'input[name="mainwp_virusdie_nonce"]' ).val(),
		}

		jQuery( "#mainwp-virusdie-scan-modal" ).modal( {
			closable: false,
			onHide: function () {
				location.reload();
			}
		} ).modal( 'show' );

		jQuery( "#mainwp-virusdie-scan-modal" ).find( '.ui.loader' ).html( 'Removing...' );

		var statusEl = $( '.ui.dimmer' );
		statusEl.addClass( 'active' );

		jQuery.post( ajaxurl, data, function ( response ) {
			$( '#mainwp-virusdie-scan-modal' ).find( '.content' ).html( response );
		} );
	}

	// Show saved repot
	$( document ).on( 'click', '.mainwp-virusdie-saved-report-show', function () {

		var parent = $( this ).closest( '.item' );

		$( '#mainwp-virusdie-report-modal' ).modal( 'show' );
		$( '#mainwp-virusdie-report-modal' ).find( '.content' ).html( '' );

		var statusEl = $( '.ui.dimmer' ).find( '.text' );

		statusEl.html( __( 'Loading report...' ) );

		var data = {
			action: 'mainwp_virusdie_show_report',
			report_id: $( parent ).attr( 'virusdie-report-id' ),
			wp_nonce: $( 'input[name="mainwp_virusdie_site_nonce"]' ).val()
		};

		$.post( ajaxurl, data, function ( response ) {
			if ( ! response ) {
				statusEl.html( __( 'Loading Report failed. Please try again.' ) );
			} else {
				$( '#mainwp-virusdie-report-modal' ).find( '.content' ).html( response.result );
				$( '#mainwp-virusdie-report-modal' ).find( '#scan-time' ).html( response.scan_date );
			}
		}, 'json' );

		return false;
	} );

} );

function mainwp_virusdie_action_popup( action, item_id ) {

	var wp_nonce = jQuery( 'input[name="mainwp_virusdie_nonce"]' ).val();
	var data = {
		action: 'mainwp_virusdie_add_sites',
		wp_nonce: wp_nonce,
	}

	jQuery( "#mainwp-virusdie-add-sites-modal" ).modal( {
		closable: true,
		onHide: function () {
			if ( virusdie_TotalThreads ) {
				location.href = 'admin.php?page=Extensions-Mainwp-Virusdie-Extension&tab=dashboard&virusdie_update_nonce=' + wp_nonce;
			} else {
				location.reload();
			}
		}
	} ).modal( 'show' );

	var title = '';

	if ( action == 'add_site' ) {
		data['site_id'] = item_id;
		title = 'Add a Website to Virusdie';
	}

	if ( title != '' ) {
		jQuery( '#mainwp-virusdie-add-sites-modal' ).find( '.header' ).html( title );
	}

	jQuery.ajax( {
		url: ajaxurl,
		type: "POST",
		data: data,
		error: function () {
			jQuery( '#mainwp-virusdie-add-sites-modal' ).find( '.content' ).html( 'error...' );
		},
		success: function ( response ) {
			jQuery( '#mainwp-virusdie-add-sites-modal' ).find( '.content' ).html( response );
			mainwp_virusdie_upload_file_next();
		},
		timeout: 20000
	} );
}

var virusdie_CurrentThreads = 0;
var virusdie_MaxThreads = 3;
var virusdie_TotalThreads = 0;
var virusdie_FinishedThreads = 0;

mainwp_virusdie_upload_file_next = function(){
	if ( virusdie_TotalThreads == 0 ) {
		virusdie_TotalThreads = jQuery( '.siteItemProcess[status="queue"]' ).length;
	}
	while ( ( fileToUpload = jQuery( '.siteItemProcess[status="queue"]:first' ) ) && ( fileToUpload.length > 0 )  && ( virusdie_CurrentThreads < virusdie_MaxThreads ) ) {
		mainwp_virusdie_upload_file_start_specific( fileToUpload );
	}
}

mainwp_virusdie_upload_file_start_specific = function ( fileUpload ) {

	virusdie_CurrentThreads++;

	fileUpload.attr( 'status', 'progress' );

	var tmpfile = jQuery( '#sync-file-tmp' ).attr( 'file-name' );
	var uploadStatus = fileUpload.find( '.upload-file-status' );

	uploadStatus.html( '<span data-tooltip="Uploading the file. Please wait..." data-inverted="" data-position="left center"><i class="notched circle loading icon"></i></span>' );

	var data = {
		action:'mainwp_virusdie_upload_sync_file',
		siteId: fileUpload.attr( 'site-id' ),
		filename: tmpfile,
		wp_nonce: jQuery( 'input[name="mainwp_virusdie_nonce"]' ).val(),
	};

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response == 'NOTEXIST' ) {
				uploadStatus.html( '<span data-tooltip="Upload failed. File not found. Please, upload the file manually." data-inverted="" data-position="left center"><i class="ui icon red times"></i></span>' );
			} else if ( response['success'] ) {
				uploadStatus.html( '<span data-tooltip="File uploaded successfully!" data-inverted="" data-position="left center"><i class="ui icon green check"></i></span>' );
			} else if ( response['error'] ) {
				uploadStatus.html( '<span data-tooltip="' + response['error'] + '" data-inverted="" data-position="left center"><i class="ui icon red times"></i></span>' );
			} else {
				uploadStatus.html( '<span data-tooltip="Upload failed. Please, upload the file manually." data-inverted="" data-position="left center"><i class="ui icon red times"></i></span>' );
			}
		} else {
			uploadStatus.html( '<span data-tooltip="Upload failed. Please, upload the file manually." data-inverted="" data-position="left center"><i class="ui icon red times"></i></span>' );
		}

		virusdie_CurrentThreads--;
		virusdie_FinishedThreads++;

		if ( virusdie_FinishedThreads == virusdie_TotalThreads && virusdie_FinishedThreads != 0 ) {
			jQuery( '#mainwp-message-zone' ).html( 'Process completed successfully! You can close the modal.' ).addClass( 'green' ).show().fadeIn( 100 );
			var data = {
				action:'mainwp_virusdie_delete_temp_file',
				tmp_file: tmpfile,
				wp_nonce: jQuery( 'input[name="mainwp_virusdie_nonce"]' ).val(),
			};
			jQuery.post( ajaxurl, data, function() {

			} );
		}

		mainwp_virusdie_upload_file_next();
	},'json');
};
