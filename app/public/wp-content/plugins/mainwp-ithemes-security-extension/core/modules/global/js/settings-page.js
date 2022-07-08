function mainwp_itsec_change_show_error_codes( args ) {
	var show = args[0];

	if ( show ) {
		jQuery( 'body' ).addClass( 'itsec-show-error-codes' );
	} else {
		jQuery( 'body' ).removeClass( 'itsec-show-error-codes' );
	}
}

function mainwp_itsec_change_write_files( args ) {
	var enabled = args[0];

	if ( enabled ) {
		jQuery( 'body' ).removeClass( 'itsec-write-files-disabled' ).addClass( 'itsec-write-files-enabled' );
	} else {
		jQuery( 'body' ).removeClass( 'itsec-write-files-enabled' ).addClass( 'itsec-write-files-disabled' );
	}
}

jQuery( document ).ready(function() {
	var $container = jQuery( '#wpcontent' );

	$container.on( 'click', '#itsec-global-add-to-whitelist', function( e ) {
		e.preventDefault();

		var whitelist = jQuery( '#itsec-global-lockout_white_list' ).val();
		whitelist = whitelist.trim();
		whitelist += "\n" + itsec_global_settings_page.ip;
		jQuery( '#itsec-global-lockout_white_list' ).val( whitelist );
	} );

	$container.on( 'click', '#itsec-global-reset-log-location', function( e ) {
		e.preventDefault();

		jQuery( '#itsec-global-log_location' ).val( itsec_global_settings_page.log_location );
	} );


  jQuery('#itsec-module-settings-save-all').on('click', function(event) {
      event.preventDefault();
      if ( mainwp_itsec_page.individualSite && mainwp_itsec_page.individualSite > 0) {
          mainwp_itheme_individual_save_all_settings( mainwp_itsec_page.ithemeSiteID );
      } else {
        var data = {
            action:'mainwp_itheme_load_sites',
            what: 'save_all_settings'
        };
          jQuery.post(ajaxurl, data, function (response) {
              if ( response ) {
                  jQuery('#mainwp-ithemes-security-tabs').append( response );
                jQuery( '#mainwp-ithemes-security-sync-modal' ).modal( 'show' );
                  ithemes_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
                  if (ithemes_bulkTotalThreads > 0)
                      mainwp_itheme_save_all_settings_start_next();
              } else {
                  jQuery('#mwp_itheme_error_zone').html( '<i class="close icon"></i> ' + 'Undefined error occurred. Please, try again.' ).show();
              }
          })
      }
  } );

  jQuery( document ).on( 'click', '#mwp_itheme_btn_savegeneral', function () {
             if (!confirm('Are you sure?'))
                    return false;

            var statusEl = jQuery('#itsec_save_all_status');
           
            statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Running ...').show();
            var data = {
                        action: 'mainwp_itheme_save_all_settings',
                        ithemeSiteID: mainwp_itsec_page.ithemeSiteID,
                        saveGeneralSettings: 1,
                        nonce:  mainwp_itsec_page.ajax_nonce
                    };

            jQuery.post( ajaxurl, data, function ( response ) {
                    var message = '';
                    var error = false;
                    if ( response) {
                        if (response.error) {
                            error = true;
                            message = response.error;
                        } else if (response.result == 'success') {
                            message= 'Successful';
                        } else {
                            error = true;
                            message = __( 'Undefined error' );
                        }
                    }
                    else
                    {
                        error = true;
                        message = __( 'Undefined error' );
                    }

                    statusEl.html( message );
                    statusEl.fadeIn();
            }, 'json' );
            return false;
	});

});


mainwp_itheme_individual_save_all_settings = function(pSiteId) {
    var statusEl = jQuery('#itsec_save_all_status');
    statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Running ...').show();
    var data = {
                action: 'mainwp_itheme_save_all_settings',
                ithemeSiteID: pSiteId,
                individualSite: 1,
                nonce:  mainwp_itsec_page.ajax_nonce
            };
    //call the ajax
    jQuery.post( ajaxurl, data, function ( response ) {
            var message = '';
            var error = false;
            if ( response) {
                if (response.error) {
                    error = true;
                    message = response.error;
                } else if (response.result == 'success') {
                    message= 'Successful';
                } else {
                    error = true;
                    message = __( 'Undefined error' );
                }
            }
            else
            {
                error = true;
                message = __( 'Undefined error' );
            }

            statusEl.html( message );
            statusEl.fadeIn();
    }, 'json' );
}

mainwp_itheme_save_all_settings_start_next = function() {
    while ((objProcess = jQuery('.siteItemProcess[status=queue]:first')) && (objProcess.length > 0) && (ithemes_bulkCurrentThreads < ithemes_bulkMaxThreads))
    {
        objProcess.attr('status', 'processed');
        mainwp_itheme_save_all_settings_start_specific(objProcess);
    }

    if (ithemes_bulkFinishedThreads > 0 && ithemes_bulkFinishedThreads == ithemes_bulkTotalThreads) {
        window.location.reload();
    }

}

mainwp_itheme_save_all_settings_start_specific = function(objProcess) {
    var statusEl = objProcess.find( '.status' );
    ithemes_bulkCurrentThreads++;
    var data = {
                action: 'mainwp_itheme_save_all_settings',
                ithemeSiteID: objProcess.attr('site-id'),
                nonce:  mainwp_itsec_page.ajax_nonce
            };

    statusEl.html( '<i class="notched circle loading icon"></i>' );
    //call the ajax
    jQuery.post( ajaxurl, data, function ( response ) {
      var message = '';

      if ( response) {
          if ( response.error ) {
              message = '<i class="red times icon"></i>'
          } else if ( response.result == 'success' ) {
              message = '<i class="green check icon"></i>';
          } else {
              message = '<i class="red times icon"></i>';
          }
      }
      else {
          message = '<i class="red times icon"></i>';
      }


      statusEl.html( message );

      ithemes_bulkCurrentThreads--;
      ithemes_bulkFinishedThreads++;
      mainwp_itheme_save_all_settings_start_next();
    }, 'json' );
}



var itsec_log_type_changed = function() {
	var type = jQuery( '#itsec-global-log_type' ).val();
    
	if ( 'both' === type ) {
		jQuery( '#itsec-global-log_rotation' ).parents( '.ui.field' ).show();
		jQuery( '#itsec-global-file_log_rotation' ).parents( '.ui.field' ).show();
		jQuery( '#itsec-global-log_location_container' ).show();
	} else if ( 'file' === type ) {
		jQuery( '#itsec-global-log_rotation' ).parents( '.ui.field' ).hide();
		jQuery( '#itsec-global-file_log_rotation' ).parents( '.ui.field' ).show();
		jQuery( '#itsec-global-log_location_container' ).show();
	} else {
		jQuery( '#itsec-global-log_rotation' ).parents( '.ui.field' ).show();
		jQuery( '#itsec-global-file_log_rotation' ).parents( '.ui.field' ).hide();
		jQuery( '#itsec-global-log_location_container' ).hide();
	}
};

jQuery( document ).ready(function($) {
	var $container = jQuery( '#wpcontent' );

	$container.on( 'click', '#itsec-global-reset-log-location', function( e ) {
		e.preventDefault();

		jQuery( '#itsec-global-log_location' ).val( itsec_global_settings_page.log_location );
	} );

	$container.on( 'change', '#itsec-global-log_type', itsec_log_type_changed );

	itsec_log_type_changed();

    
	function proxyHeaderChanged() {
		var proxy = $( '#itsec-global-proxy' ).dropdown('get value');

		if ( 'security-check' === proxy ) {
			$( '#itsec-global-ip-scan' ).show();
		} else {
			$( '#itsec-global-ip-scan' ).hide();
		}

		if ( 'manual' === proxy ) {
			$( '.itsec-global-proxy_header-container' ).show();
		} else {
			$( '.itsec-global-proxy_header-container' ).hide();
		}
	}

    $( function() {
		
		$( document ).on( 'change', '#itsec-global-proxy', proxyHeaderChanged );
		mainwp_itsecSettingsPage.events.on( 'modulesReloaded', proxyHeaderChanged );
		mainwp_itsecSettingsPage.events.on( 'moduleReloaded', function( e, module ) {
			if ( 'global' === module ) {
				proxyHeaderChanged();
			}
		} );
		proxyHeaderChanged();
	} );


});
