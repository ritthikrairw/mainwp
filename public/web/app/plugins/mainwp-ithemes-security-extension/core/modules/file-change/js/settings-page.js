jQuery( document ).ready( function ( $ ) {
	/**
	 * Show the file tree in the settings.
	 */
	$( '.jquery_file_tree' ).fileTree(
		{
			root         : itsec_file_change_settings.ABSPATH,
			script       : ajaxurl,
			expandSpeed  : -1,
			collapseSpeed: -1,
			multiFolder  : false

		}, function ( file ) {

			$( '#itsec-file-change-file_list' ).val( file.substring( itsec_file_change_settings.ABSPATH.length ) + "\n" + $( '#itsec-file-change-file_list' ).val() );

		}, function ( directory ) {

			$( '#itsec-file-change-file_list' ).val( directory.substring( itsec_file_change_settings.ABSPATH.length ) + "\n" + $( '#itsec-file-change-file_list' ).val() );

		}
	);

	/**
	 * Performs a one-time file scan
	 */
	$( '#itsec-file-change-one_time_check' ).click(function( e ) {
		e.preventDefault();

                if ( ! mainwp_itsec_page.individualSite ) {
                    mainwp_itsecSettingsPage.mainwpLoadSites( 'file-change', 'one_time_check' );
                    return;
                }

		//let user know we're working
		$( '#itsec-file-change-one_time_check' )
			//.removeClass( 'button-primary' )
			.addClass( 'button-secondary' )
			.attr( 'value', itsec_file_change_settings.scanning_button_text )
			.prop( 'disabled', true );

		var data = {
			'method': 'one-time-scan'
		};
		var statusEl = $( '#itsec_file_change_status' );
		statusEl.html('');

		mainwp_itsecSettingsPage.sendModuleAJAXRequest( 'file-change', data, function( response ) {

			var mainwp_response = false;

                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;


                        var message = '';
                        
						if (mainwp_response.message) {
                            message = mainwp_response.message;
                        }

                        if ( message != '' ) {
                            if (mainwp_response.error) {
                                message = '<i class="red times icon"></i>';
                            } else if (mainwp_response.result == 'success') {
                                if (message == '')
                                    message = '<i class="green check icon"></i>';
                            } else {

                                message = '<i class="red times icon"></i>';
                            }
                        }


                        statusEl.html( message );
                        statusEl.fadeIn();

			$( '#itsec-file-change-one_time_check' )
				.removeClass( 'button-secondary' )
				//.addClass( 'button-primary' )
				.attr( 'value', itsec_file_change_settings.button_text )
				.prop( 'disabled', false );
		} );
	});

} );

jQuery( window ).load( function () {

	/**
	 * Shows and hides the red selector icon on the file tree allowing users to select an
	 * individual element.
	 */
	jQuery( document ).on( 'mouseover mouseout', '.jqueryFileTree > li a', function ( event ) {

		if ( event.type == 'mouseover' ) {

			jQuery( this ).children( '.itsec_treeselect_control' ).css( 'visibility', 'visible' );

		} else {

			jQuery( this ).children( '.itsec_treeselect_control' ).css( 'visibility', 'hidden' );

		}

	} );

} );
