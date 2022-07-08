jQuery( document ).ready( function () {
	var $container = jQuery( '#wpcontent' );


	$container.on( 'click', '#itsec-backup-reset_backup_location', function( e ) {
		e.preventDefault();

		jQuery( '#itsec-backup-location' ).val( mainwp_itsec_backup_local.default_backup_location );
	} );

	$container.on( 'change', '#itsec-backup-method', function( e ) {
		var method = jQuery(this).val();

		if ( 1 == method ) {
			jQuery( '.itsec-backup-method-file-content' ).hide();
		} else {
			jQuery( '.itsec-backup-method-file-content' ).show();
		}
	} );

	jQuery( '#itsec-backup-method' ).trigger( 'change' );


	jQuery( '#itsec-backup-exclude' ).multiSelect( {
		selectableHeader: '<div class="custom-header">' + mainwp_itsec_backup_local.available_tables_label + '</div>',
		selectionHeader:  '<div class="custom-header">' + mainwp_itsec_backup_local.excluded_tables_label + '</div>',
		keepOrder:        true
	} );
} );


jQuery( document ).ready( function ($) {
    $('#mwp_itheme_backups_db_btn').on('click', function(event) {
        event.preventDefault();
        var caller = this;
        jQuery( caller ).closest('.submit').find('i').show();
        jQuery( caller ).attr('disabled', 'disabled');

        if ( mainwp_itsec_page.individualSite && mainwp_itsec_page.individualSite > 0) {
            mainwp_itheme_individual_backups(mainwp_itsec_page.ithemeSiteID, caller);
        } else {
            var data = {
                action:'mainwp_itheme_load_sites',
                what: 'backup_db'
            };
            jQuery.post(ajaxurl, data, function (response) {
                if (response) {
									jQuery('#mainwp-ithemes-security-tabs').append(response);
									jQuery( '#mainwp-ithemes-security-sync-modal' ).modal( 'show' );
                    ithemes_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
                    if (ithemes_bulkTotalThreads > 0)
                        mainwp_itheme_backup_db_start_next();
                } else {
									jQuery('#mainwp-ithemes-security-tabs').html('<div class="ui red message">' + __("Undefined error occurred. Please reload the page and try again.") + '</div>');
                }
            })
        }
    });

     $('#mwp_itheme_backups_reload_exclude_tables_btn').on('click', function(event) {
        var statusEl = jQuery('#itsec_reload_exclude_status');
        statusEl.html('<i class="notched circle loading icon"></i> Action in progress. Please wait...').show();
        var data = {
                    action: 'mainwp_itheme_reload_exclude_tables',
                    ithemeSiteID: mainwp_itsec_page.ithemeSiteID,
                    nonce:  mainwp_itsec_page.ajax_nonce
                };
        //call the ajax
        jQuery.post( ajaxurl, data, function ( response ) {
                var message = '';

                if ( response) {
                    if (response.message)
                        message = response.message;

                    if (response.error) {

                        message = response.error;
                    } else if (response.html) {
                        message = __('Successful');
                        jQuery( '#backup_multi_select_wrap').html(response.html);
                        jQuery( '#itsec-backup-exclude' ).multiSelect( {
                            selectableHeader: '<div class="custom-header">' + mainwp_itsec_backup_local.available_tables_label + '</div>',
                            selectionHeader:  '<div class="custom-header">' + mainwp_itsec_backup_local.excluded_tables_label + '</div>',
                            keepOrder:        true
                        } );
                    } else {

                        message = __( 'Undefined error' );
                    }
                }
                else
                {

                    message = __( 'Undefined error' );
                }

                statusEl.html( message );
                statusEl.show();
        }, 'json' );
    });

} );

mainwp_itheme_individual_backups = function(pSiteId, pCaller) {
    var statusEl = jQuery('#itsec_backup_status');
    statusEl.html('<i class="notched circle loading icon"></i> Action in progress. Please wait...').show();
    var data = {
                action: 'mainwp_itheme_backups_database',
                ithemeSiteID: pSiteId,
                individualSite: 1,
                nonce:  mainwp_itsec_page.ajax_nonce
            };
    //call the ajax
    jQuery.post( ajaxurl, data, function ( response ) {
            jQuery( pCaller ).closest('.submit').find('i').hide();
            jQuery( pCaller ).removeAttr('disabled');
            var message = '';

            if ( response) {
                if (response.message)
                    message = response.message;

                if (response.error) {

                    message = response.error;
                } else if (response.result == 'success') {
                    if ( message == '' )
                        message = mainwp_itsec_backup_local.success;
                } else if (response.result == 'fail') {

                    if ( message == '' )
                        message = mainwp_itsec_backup_local.fail;
                } else {
                    message = __( 'Undefined error' );
                }
            }
            else
            {
                message = __( 'Undefined error' );
            }

            statusEl.html( message );
            statusEl.fadeIn();
    }, 'json' );
}

mainwp_itheme_backup_db_start_next = function() {
    while ((objProcess = jQuery('.siteItemProcess[status=queue]:first')) && (objProcess.length > 0) && (ithemes_bulkCurrentThreads < ithemes_bulkMaxThreads))
    {
        objProcess.attr('status', 'processed');
        mainwp_itheme_backup_db_start_specific(objProcess);
    }

    if (ithemes_bulkFinishedThreads > 0 && ithemes_bulkFinishedThreads == ithemes_bulkTotalThreads) {
			window.location.reload();
    }

}

mainwp_itheme_backup_db_start_specific = function(objProcess) {
    var statusEl = objProcess.find('.status');
    ithemes_bulkCurrentThreads++;
    var data = {
                action: 'mainwp_itheme_backups_database',
                ithemeSiteID: objProcess.attr('site-id'),
                nonce:  mainwp_itsec_page.ajax_nonce
            };

    statusEl.html('<i class="notched circle loading icon"></i>');
    //call the ajax
    jQuery.post( ajaxurl, data, function ( response ) {

            var message = '';

            if ( response) {
                if (response.message)
                    message = response.message;

                if (response.error) {

                    message = '<i class="red times icon"></i>';
                } else if (response.result == 'success') {
                    if ( message == '' )
                        message = '<i class="green check icon"></i>';
                } else if (response.result == 'fail') {

                    if ( message == '' )
                        message = '<i class="green check icon"></i>';
                } else {
                    message = '<i class="red times icon"></i>';
                }
            }
            else
            {
                message = '<i class="red times icon"></i>';
            }


            statusEl.html( message );

            ithemes_bulkCurrentThreads--;
            ithemes_bulkFinishedThreads++;
            mainwp_itheme_backup_db_start_next();
    }, 'json' );
}
