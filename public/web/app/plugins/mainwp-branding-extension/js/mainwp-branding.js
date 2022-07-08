var branding_MaxThreads = 3;
var branding_CurrentThreads = 0;
var branding_TotalThreads = 0;
var branding_FinishedThreads = 0;

// Loop through sites
mainwp_branding_start_next = function () {
	if ( branding_TotalThreads == 0 ) {
		branding_TotalThreads = jQuery( '.mainwpBrandingSitesItem[status="queue"]' ).length;
	}
	while ( ( siteToBranding = jQuery( '.mainwpBrandingSitesItem[status="queue"]:first' ) ) && ( siteToBranding.length > 0 ) && ( branding_CurrentThreads < branding_MaxThreads ) ) {
		mainwp_branding_start_specific( siteToBranding );
	}
};

// Rebrend site
mainwp_branding_start_specific = function ( pSiteToBranding ) {
	branding_CurrentThreads++;
	pSiteToBranding.attr( 'status', 'progress' );

	var statusEl = pSiteToBranding.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );

	var data = {
		action: 'mainwp_branding_performbrandingchildplugin',
		siteId: pSiteToBranding.attr( 'siteid' )
	};

	jQuery.post( ajaxurl, data, function ( response ) {
		pSiteToBranding.attr( 'status', 'done' );
		if ( response && response['result'] == 'OVERRIDED' ) {
			statusEl.html( __( 'Skipped. Individual white label settings have been set.' ) ).show();
		} else if ( response && response['result'] == 'SUCCESS' ) {
			statusEl.html( '<span data-tooltip="White Label settings saved successfully!" data-position="left center" data-inverted=""><i class="green check icon"></i></span>' ).show();
			if ( response['error'] && response['error']['login_image'] ) {
				var error = 'Login image:' + response['error']['login_image'];
				statusEl.html( error );
			}
		} else if ( response && response['error'] ) {
			statusEl.html( '<span data-tooltip="' + response['error'] + '" data-position="left center" data-inverted=""><i class="red times icon"></i></span>' ).show();
		} else {
			statusEl.html( '<span data-tooltip="Undefined error occurred. Please, try again." data-position="left center" data-inverted=""><i class="red times icon"></i></span>' ).show();
		}

		branding_CurrentThreads--;
		branding_FinishedThreads++;

		jQuery( '#mainwp-braning-processing-modal .mainwp-modal-progress' ).progress( { value: branding_FinishedThreads, total:  branding_TotalThreads } );
		jQuery( '#mainwp-braning-processing-modal .mainwp-modal-progress' ).find( '.label' ).html( branding_FinishedThreads + '/' + branding_TotalThreads + ' ' + __( 'Completed' ) );

		mainwp_branding_start_next();
	}, 'json');
};


mainwp_branding_update_specical_site = function () {
	var data = {
		action: 'mainwp_branding_performbrandingchildplugin',
		siteId: jQuery( '#branding_individual_settings_site_id' ).val(),
    individual: 1
	};

	statusEl = jQuery( '#mainwp-branding-message-zone' );
	statusEl.html( '' ).hide();
	statusEl.removeClass( 'green' );
	statusEl.removeClass( 'yellow' );
	statusEl.removeClass( 'red' );
  statusEl.html( '<i class="close icon"></i>' + '<i class="notched circle loading icon"></i> ' + 'Applying custom white label settings. Please wait...' ).show();

	jQuery.post( ajaxurl, data, function ( response ) {
		if ( response && response['result'] == 'SUCCESS' ) {
			statusEl.html( '<i class="close icon"></i>' + __( 'Custom white label settings applied successfully.' ) );
			statusEl.addClass( 'green' );
		} else if ( response && response['error'] ) {
			statusEl.html( response['error'] );
			statusEl.addClass( 'red' );
		} else {
			statusEl.html( '<i class="close icon"></i>' + __( 'Undefined error occurred. Please, try again.' ) );
			statusEl.addClass( 'red' );
		}
	}, 'json');
}

var mwp_branding_save_alert = false;

jQuery( document ).ready( function ($) {

	// Add New Text Replace field
	$( '.add_text_replace' ).on( 'click', function () {
		var errors = [];

		if ( $.trim( $( '#mainwp_branding_texts_add_value' ).val() ) == '' ) {
			errors.push( __( 'The Search field can not be empty.' ) );
		}

		if ( $.trim( $( '#mainwp_branding_texts_add_replace' ).val() ) == '') {
			errors.push( __( 'The Replace field can not be empty!' ) );
		}

		if ( errors.length > 0 ) {
			$( '#mainwp-branding-text-replace-modal' ).modal( 'show' );
			$( '#mainwp-branding-text-replace-modal' ).find( '.content').html( errors.join( '<br/>' ) );
			return false;
		}

		var parent = $( this ).closest( '.mainwp-branding-text-replace-row' );

		parent.after( $( "#mainwp-branding-text-replace-row-copy" ).html() ).fadeIn();

		var newRow = parent.next();
		newRow.addClass( 'mainwp-branding-text-replace-row' );
		newRow.find( "input[name='mainwp_branding_texts_value[]']" ).val( $( '#mainwp_branding_texts_add_value' ).val() );
		newRow.find( "input[name='mainwp_branding_texts_replace[]']" ).val( $( '#mainwp_branding_texts_add_replace' ).val() );
		$( '#mainwp_branding_texts_add_value' ).val( '' );
		$( '#mainwp_branding_texts_add_replace' ).val( '' );
		return false;
	})

	// Remove Text Replace field
	jQuery( document ).on( 'click', '.delete_text_replace', function () {
		$( this ).closest( '.mainwp-branding-text-replace-row' ).fadeOut();
		return false;
	});

	// Trigger the Rebrand process
  jQuery( document ).on( 'click', '#branding_submit_btn', function () {
    if ( !confirm( 'Are you sure?' ) )
    return false;
  });

	// Reset fields
	$( '#mwp_branding_reset_btn' ).on( 'click', function () {
		if ( ! confirm( "Are you sure you want to reset the current white label settings?" )) {
			return false;
		}

		for ( var k in mainwpBrandingDefaultOpts.checkboxes ) {
			jQuery( '#' + k ).prop( "checked", mainwpBrandingDefaultOpts.checkboxes[k] );
		}

		for (var k in mainwpBrandingDefaultOpts.textareas) {
			jQuery( 'textarea[name="' + k + '"]' ).val( mainwpBrandingDefaultOpts.textareas[k] );
		}

		for (var k in mainwpBrandingDefaultOpts.textbox_id) {
			jQuery( '#' + k ).val( mainwpBrandingDefaultOpts.textbox_id[k] );
		}

		for ( var k in mainwpBrandingDefaultOpts.tinyMCEs ) {
			var editor = window.parent.tinymce.get( k );
			if ( editor != null && typeof( editor ) !== "undefined" && editor.isHidden() == false ) {
				editor.setContent( mainwpBrandingDefaultOpts.tinyMCEs[k] );
			} else {
				var obj = $( '#' + k );
				obj.val( mainwpBrandingDefaultOpts.tinyMCEs[k] );
			}
		}

		for ( var k in mainwpBrandingDefaultOpts.textbox_class ) {
			if ( /^(?:mainwp_branding_texts_value)$/.test( k ) ) {
				jQuery( '.' + k ).each(function () {
					jQuery( this ).closest( '.mainwp-branding-text-replace-row' ).each( function () {
						var row = this;
						if ( jQuery( this ).closest( "#mainwp-branding-text-replace-row-copy" ).length == 0 ) {
							jQuery( row ).remove(); }
					});
				});
			}
		}
		mwp_branding_save_alert = true;
		mwp_branding_save_reminder();
		alert( "Click the Save Settings button in the bottom of the page to save changes." );
	});
});

// Remind user to resave Settings after reseting values
function mwp_branding_save_reminder() {
	setTimeout( function () {
		if ( mwp_branding_save_alert ) {
			alert( __( "Click the Save Settings button in the bottom of the page to save changes." ) );
			mwp_branding_save_reminder();
		}
	}, 1000 * 60 * 10);
}
