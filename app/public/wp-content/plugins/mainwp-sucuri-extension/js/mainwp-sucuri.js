jQuery( document ).ready( function ($) {

	// Filter groups
	$( '#sucuri_btn_display' ).on( 'click', function () {
		$( this ).closest( 'form' ).submit();
	} );

	// Initiate Scan (Site Securty Scan page)
	$( '#mainwp-sucuri-run-scan' ).on( 'click', function () {
		mainwp_sucuri_run_scan( this, false );
		return false;
	});


	$('#mainwp_sucuri_groups_select').dropdown( {
		onChange: function (group_id, text, $choice) {
			var url = 'admin.php?page=Extensions-Mainwp-Sucuri-Extension';
			if ( group_id > 0)
				url += '&group_id=' + group_id;
			location.href = url;
		}
	} );

	// Scan site (Site Securty Scan page)
	mainwp_sucuri_run_scan = function ( pObj, retring ) {

		var data = {
			action: 'mainwp_sucuri_security_scan',
			siteId: $( 'input[name="mainwp_sucuri_site_id"]' ).val(),
			wp_nonce: $( 'input[name="mainwp_sucuri_scan_nonce"]' ).val()
		}

		$( '#mainwp-sucuri-run-scan' ).attr( 'disabled', 'disabled' );

		$( '#mainwp-sucuri-scan-modal' ).modal({
      closable: false,
      onHide: function() {
        location.reload();
      }
    }).modal( 'show' );

		var statusEl = $( '.ui.dimmer' );

		statusEl.addClass( 'active' );

		if ( retring == true ) {
			statusEl.html( __( "Connection error detected. The Verify Certificate option has been switched to NO. Retrying in progress." ) ).show();
		}

		statusEl.find( '.text' ).html( __( "Scanning the child site. Please wait..." ) );

		jQuery.post( ajaxurl, data, function ( response ) {
			if ( response.result == 'retry_action' ) {
				jQuery( "#mainwp_sucuri_verify_certificate" ).val( 0 );
				mainwp_sucuri_run_scan( pObj, true );
			} else {
				statusEl.removeClass( 'active' );
				$( '#mainwp-sucuri-run-scan' ).removeAttr( 'disabled' );
				$( '#mainwp-sucuri-security-scan-result' ).html( response.result );
        $( '#mainwp-sucuri-scan-list' ).prepend( response.item );
			}
		}, 'json' );
	}

	// Initiate Scan (Sucuri Extension page)
	jQuery( '.mainwp-sucuri-scan-site' ).on( 'click', function () {
		mainwp_sucuri_scan_site( this, false );
		return false;
	} );

	// Scan site (Sucuri Extension page)
	mainwp_sucuri_scan_site = function ( obj, retring ) {

		var siteId = jQuery( obj ).attr( 'site_id' );
		var nonce = jQuery( obj ).attr( 'nonce' );
		var data = {
			action: 'mainwp_sucuri_scan_site_action',
			siteId: siteId,
			wp_nonce: nonce
		}

    jQuery( "#mainwp-sucuri-scan-modal" ).modal( {
      closable: false,
      onHide: function() {
        location.reload();
      }
    } ).modal( 'show' );

		var statusEl = jQuery( '.ui.dimmer' );

		statusEl.addClass( 'active' );

		if ( retring == true ) {
			statusEl.find( ".ui.text" ).html( ' ' + __( "Connection error detected. The Verify Certificate option has been switched to NO. Retrying..." ) );
		}

		jQuery.post( ajaxurl, data, function ( response ) {
			if ( response == 'retry_action' ) {
				jQuery( "#mainwp_sucuri_verify_certificate" ).val( 0 );
				mainwp_sucuri_scan_site( obj, true );
			} else {
				jQuery( '#mainwp-sucuri-scan-modal' ).find( '.content' ).html('');
				jQuery( '#mainwp-sucuri-scan-modal' ).find( '.content' ).html( response );
			}
		});
	}

	// Show saved repot
	jQuery( document ).on( 'click', '.mainwp-sucuri-saved-report-show', function () {

		var parent = $( this ).closest( '.item' );

		jQuery( '#mainwp-sucuri-scan-modal' ).modal( 'show' );
		jQuery( '#mainwp-sucuri-scan-modal' ).find( '.content' ).html( '' );

    var statusEl = jQuery( '.ui.dimmer' ).find( '.text' );

		statusEl.html( __( 'Loading report...' ) );

		var data = {
			action: 'mainwp_sucuri_show_report',
			reportId: $( this ).attr( 'report-id' ),
			siteId: $( 'input[name="mainwp_sucuri_site_id"]' ).val(),
			wp_nonce: $( 'input[name="mainwp_sucuri_show_report_nonce"]' ).val()
		};

		jQuery.post(ajaxurl, data, function (response) {
			if ( ! response || response === 'FAIL') {
				statusEl.html( __( 'Loading Report failed. Please try again.' ) );
			} else {
				jQuery( '#mainwp-sucuri-scan-modal' ).find( '.content' ).html( response );
			}
		});

		return false;
	});

	// Delete saved report
	$( document ).on( 'click', '.mainwp-sucuri-saved-report-delete', function () {
		var parent = $( this ).closest( '.item' );

		var data = {
			action: 'mainwp_sucuri_delete_report',
			reportId: $( this ).attr( 'report-id' ),
			wp_nonce: $( 'input[name="mainwp_sucuri_delete_report_nonce"]' ).val()
		}

		$.post(ajaxurl, data, function ( response ) {
			if ( response && response === 'SUCCESS' ) {
				parent.html( 'Report deleted successfully.' );
				parent.fadeOut( 2000 );
			} else {
				parent.html( 'Report could not be deleted.' );
				location.reload();
			}
		});
		return false;
	});

	// Set reminder
	$( '#mainwp_sucuri_remind_scan' ).on( 'change', function (e) {
		var data = {
			action: 'mainwp_sucuri_change_remind',
			siteId: $( 'input[name="mainwp_sucuri_site_id"]' ).val(),
			remind: $( this ).val(),
			wp_nonce: $( 'input[name="mainwp_sucuri_change_remind_nonce"]' ).val()
		}

		var statusEl = $( '#mainwp-sucuri-message-zone' );
        statusEl.html( '' );

		$.post(ajaxurl, data, function (response) {
			if (response == 'SUCCESS') {
				statusEl.addClass( 'green' );
				statusEl.html( "Reminder saved successfully." ).show().fadeOut( 2000 );
				statusEl.removeClass( 'green' );
			} else {
				statusEl.addClass( 'red' );
				statusEl.html( "Reminder could not be saved. Please try again." ).show().fadeOut( 2000 );
				statusEl.removeClass( 'red' );
			}
		});
	});

	// Set SSL Cert verificaion
	$( '#mainwp_sucuri_verify_certificate' ).on( 'change', function (e) {

		var data = {
			action: 'mainwp_sucuri_sslverify_certificate',
			security_sslverify: $( this ).val()
		}

		var statusEl = $( '#mainwp-sucuri-message-zone' );
        statusEl.html( '' );

		$.post( ajaxurl, data, function ( response ) {
			if ( response ) {
				if ( response.saved == '1' ) {
					statusEl.addClass( 'green' );
					statusEl.html( "Changes saved successfully." ).show().fadeOut( 2000 );
					statusEl.removeClass( 'green' );
				} else if ( response.error ) {
					statusEl.addClass( 'red' );
					statusEl.html( response.error ).show().fadeOut( 2000 );
					statusEl.removeClass( 'red' );
				} else {
					statusEl.addClass( 'red' );
					statusEl.html( "Saving chagnes failed. Please try again." ).show().fadeOut( 2000 );
					statusEl.removeClass( 'red' );
				}
			} else {
				statusEl.addClass( 'red' );
				statusEl.html( "Saving chagnes failed. Please try again." ).show().fadeOut( 2000 );
				statusEl.removeClass( 'red' );
			}
		}, 'json');
	});
});
