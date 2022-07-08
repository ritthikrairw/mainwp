jQuery( document ).ready(function($) {

	jQuery( '#mainwp-ithemes-security-sites-filter-button' ).on( 'click', function(e) {
		var groups = jQuery( '#mainwp-ithemes-security-groups-selection' ).dropdown( "get value" );
		console.log( groups );
		if ( groups !== null && groups != '' ) {
			groups = '&group=' + groups.join('-');
		} else {
			groups = '';
		}
		location.href = 'admin.php?page=Extensions-Mainwp-Ithemes-Security-Extension&tab=dashboard' + groups;
	} );

	$( '#mainwp_itheme_override_general_settings' ).on('change', function (){
		var statusEl = jQuery( '#mwp_itheme_site_save_settings_status' );
		statusEl.removeClass( 'red green' )
		statusEl.html( '<i class="notched circle loading icon"></i> Updating. Please wait...').show();
		data = {
			action:'mainwp_itheme_site_override_settings',
			ithemeSiteID: $( 'input[name=mainwp_itheme_settings_site_id]' ).val(),
			override: $( '#mainwp_itheme_override_general_settings' ).is( ":checked" ) ? 1 : 0
		};
		jQuery.post(ajaxurl, data, function (response) {
			if ( response) {
				if (response.error) {
					statusEl.addClass( 'red' );
					statusEl.html( response.error );
				} else if (response.result == 'success') {
					statusEl.addClass( 'green' );
					statusEl.html( __( 'Settings saved successfully!' ) );
					setTimeout(function () {
						statusEl.fadeOut();
					}, 3000 );
				} else {
					statusEl.addClass( 'red' );
					statusEl.html( 'Undefined error occurred. Please try again.' );
				}
			} else {
				statusEl.addClass( 'red' );
				statusEl.html( 'Undefined error occurred. Please try again.' );
			}
			statusEl.fadeIn();
		}, 'json' );

		return false;
	});

  jQuery( '.itsec_add_dashboard_ip_to_whitelist' ).click( function ( event ) {
	event.preventDefault();
	jQuery( '#itsec-global-lockout_white_list' ).val( jQuery( '#itsec-global-lockout_white_list' ).val() + jQuery( '.itsec_add_dashboard_ip_to_whitelist' ).attr( 'href' ) );

	} );

});

jQuery( document ).ready(function($) {

	$( '.mwp_ithemes_active_plugin' ).on('click', function() {
		mainwp_ithemes_plugin_active_start_specific( $( this ), false );
		return false;
	});

	$( '.mwp_ithemes_upgrade_plugin' ).on('click', function() {
		mainwp_ithemes_plugin_upgrade_start_specific( $( this ), false );
		return false;
	});

	$( '.mwp_ithemes_showhide_plugin' ).on('click', function() {
		mainwp_ithemes_plugin_showhide_start_specific( $( this ), false );
		return false;
	});

	$( '#ithemes_plugin_doaction_btn' ).on('click', function() {
		var bulk_act = $( '#mwp_ithemes_plugin_action' ).val();
			mainwp_ithemes_plugin_do_bulk_action( bulk_act );
	});
});

var ithemes_bulkMaxThreads = 1;
var ithemes_bulkTotalThreads = 0;
var ithemes_bulkCurrentThreads = 0;
var ithemes_bulkFinishedThreads = 0;

mainwp_ithemes_plugin_do_bulk_action = function(act) {
	var selector = '';
	switch (act) {
		case 'activate-selected':
			selector = 'tbody tr.negative .mwp_ithemes_active_plugin';
			jQuery( selector ).addClass( 'queue' );
			mainwp_ithemes_plugin_active_start_next( selector );
			break;
		case 'update-selected':
			selector = 'tbody tr.warning .mwp_ithemes_upgrade_plugin';
			jQuery( selector ).addClass( 'queue' );
			mainwp_ithemes_plugin_upgrade_start_next( selector );
			break;
		case 'hide-selected':
			selector = 'tbody tr .mwp_ithemes_showhide_plugin[showhide="hide"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_ithemes_plugin_showhide_start_next( selector );
			break;
		case 'show-selected':
			selector = 'tbody tr .mwp_ithemes_showhide_plugin[showhide="show"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_ithemes_plugin_showhide_start_next( selector );
			break;
	}
}

mainwp_ithemes_plugin_showhide_start_next = function(selector) {
	while ((objProcess = jQuery( selector + '.queue:first' ) ) && (objProcess.length > 0) && (ithemes_bulkCurrentThreads < ithemes_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_ithemes_plugin_showhide_start_specific( objProcess, true, selector );
	}
}

mainwp_ithemes_plugin_showhide_start_specific = function(pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var showhide = pObj.attr( 'showhide' );
	var pluginName = parent.attr( 'plugin-name' );
	var statusEl = parent.find( '.visibility' );

	if (bulk) {
		ithemes_bulkCurrentThreads++;
	}

	var data = {
		action: 'mainwp_ithemes_showhide_plugin',
		ithemeSiteID: parent.attr( 'website-id' ),
		showhide: showhide
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html( '' );

		pObj.removeClass( 'queue' );
		if (response && response['error']) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if ( response && response['result'] == 'success' ) {
			if ( showhide == 'show' ) {
				pObj.text( "Hide Plugin" );
				pObj.attr( 'showhide', 'hide' );
				parent.find( '.wp-ithemes-visibility' ).html( __( 'No' ) );
			} else {
				pObj.text( "Unhide Plugin" );
				pObj.attr( 'showhide', 'show' );
				parent.find( '.wp-ithemes-visibility' ).html( __( 'Yes' ) );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		if (bulk) {
			ithemes_bulkCurrentThreads--;
			ithemes_bulkFinishedThreads++;
			mainwp_ithemes_plugin_showhide_start_next( selector );
		}

	},'json');
	return false;
}

mainwp_ithemes_plugin_upgrade_start_next = function(selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.length > 0) && (ithemes_bulkCurrentThreads < ithemes_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_ithemes_plugin_upgrade_start_specific( objProcess, true, selector );
	}
}

mainwp_ithemes_plugin_upgrade_start_specific = function(pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.updating' );
	var slug = parent.attr( 'plugin-slug' );

	statusEl.html( '' );

	var data = {
		action: 'mainwp_ithemes_upgrade_plugin',
		ithemeSiteID: parent.attr( 'website-id' ),
		type: 'plugin',
		'slugs[]': [slug]
	}

	if (bulk) {
		ithemes_bulkCurrentThreads++; }

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html( '' );
		pObj.removeClass( 'queue' );
		if (response && response['error']) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if ( response && response['upgrades'][slug] ) {
			pObj.remove();
			parent.removeClass( 'warning' );
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		if (bulk) {
			ithemes_bulkCurrentThreads--;
			ithemes_bulkFinishedThreads++;
			mainwp_ithemes_plugin_upgrade_start_next( selector );
		}

	},'json');
	return false;
}

mainwp_ithemes_plugin_active_start_next = function(selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.length > 0) && (ithemes_bulkCurrentThreads < ithemes_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_ithemes_plugin_active_start_specific( objProcess, true, selector );
	}
}

mainwp_ithemes_plugin_active_start_specific = function(pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.updating' );
	var slug = parent.attr( 'plugin-slug' );

	var data = {
		action: 'mainwp_ithemes_active_plugin',
		ithemeSiteID: parent.attr( 'website-id' ),
		'plugins[]': [slug]
	}

	if (bulk) {
		ithemes_bulkCurrentThreads++;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html( '' );
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if (response && response['result']) {
			parent.removeClass( 'negative' );
			pObj.remove();
		}

		if (bulk) {
			ithemes_bulkCurrentThreads--;
			ithemes_bulkFinishedThreads++;
			mainwp_ithemes_plugin_active_start_next( selector );
		}

	},'json');
	return false;
}


jQuery( document ).ready( function () {
        jQuery( '#mwp_itheme_lockouts_release_btn' ).bind( 'click', function ( event ) {
            event.preventDefault();
            var caller = this;
            jQuery( caller ).closest('.submitleft').find('i').show();
            jQuery( caller ).attr('disabled', 'disabled');

            var site_id = jQuery('#mainwp_itheme_managesites_site_id').attr('site-id');
            if ( site_id > 0) {
                mainwp_itheme_individual_lockouts_release(site_id, caller);
            }
    } );

});

mainwp_itheme_individual_lockouts_release = function(pSiteId, pCaller) {
    var statusEl = jQuery('.mwp_itheme_lockouts_status');
    statusEl.hide();
    var ids = jQuery(pCaller).closest('.inside').find('input[type="checkbox"]:checked').map(function(){
                                                                                                        return jQuery( this ).val();
                                                                                                    }).get();
    if (ids.length == 0)
        return;

    var data = {
                action: 'mainwp_itheme_release_lockouts',
                siteId: pSiteId,
                lockout_ids: ids,
                individual: true
            };
    //call the ajax
    jQuery.post( ajaxurl, data, function ( response ) {
            jQuery( pCaller ).closest('.submitleft').find('i').hide();
            jQuery( pCaller ).removeAttr('disabled');
            if ( response) {
                if (response.error) {
                    statusEl.html( response.error );
                } else if (response.message) {
                    statusEl.html(response.message);
                } else if (response.result == 'success') {
                    statusEl.html('Successful.');
                    setTimeout(function ()
                    {
                        location.href = location.href;
                    }, 3000);
                } else {
                    statusEl.html( 'Undefined error.' );
                }
            }
            else
            {
                statusEl.html( 'Undefined error.' );
            }
            statusEl.fadeIn();
    }, 'json' );
}
