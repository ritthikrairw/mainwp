jQuery( document ).ready(function ($) {

  //Init progress bar
  $( '#mainwp-pagespeed-dashboard-tab .ui.progress' ).progress( {
		showActivity : false,
		label: 'ratio',
    text: {
      ratio: '{value}'
    }
	} );
	$( '.column-score_desktop .ui.progress' ).progress( {
		showActivity : false,
		label: 'ratio',
    text: {
      ratio: '{value}'
    }
	} );
	$( '.column-score_mobile .ui.progress' ).progress( {
		showActivity : false,
		label: 'ratio',
    text: {
      ratio: '{value}'
    }
	} );



	// Trigger the plugin activation action
	$( '.pagespeed_active_plugin' ).on( 'click', function() {
		mainwp_pagespeed_active_start_specific( $( this ), false );
		return false;
	} );

	// Trigger the update plugin action
	$( '.pagespeed_upgrade_plugin' ).on( 'click', function() {
		mainwp_pagespeed_upgrade_start_specific( $( this ), false );
		return false;
	} );

	// Check all checkboxes
	jQuery( '#mainwp-page-speed-sites-table th input[type="checkbox"]' ).change( function() {
		var checkboxes = jQuery( '#mainwp-page-speed-sites-table' ).find( ':checkbox' );
		if ( jQuery( this ).prop( 'checked' ) ) {
      checkboxes.prop( 'checked', true );
    } else {
      checkboxes.prop( 'checked', false );
    }
	} );

	// Trigger the Show/Hide plugin action
	$( '.pagespeed_showhide_plugin' ).on( 'click', function() {
		mainwp_pagespeed_showhide_start_specific( $( this ), false );
		return false;
	} );

	// Trigger the bulk actions
	$( '#mwp_pagespeed_action_btn' ).on( 'click', function() {
		var bulk_act = $( '#mwp_pagespeed_bulk_action' ).dropdown( "get value" );
    console.log( bulk_act );
		mainwp_pagespeed_do_bulk_action( bulk_act );
	} );

	$( '#mainwp-pagespeed-save-individual-settings-button' ).on('click', function () {
		var data = {
			action: 'mainwp_pagespeed_save_ext_setting',
			scoreNoti: $( 'select[name="mainwp_pagespeed_score_noti"]' ).val(),
			scheduleNoti: $( 'select[name="mainwp_pagespeed_schedule_noti"]' ).val()
		}
		var statusEl = $( '#mwps-setting-ext-working .status' );
		statusEl.html( '' );
		$( '#mwps-setting-ext-working .loading' ).show();
		jQuery.post(ajaxurl, data, function (response) {
			$( '#mwps-setting-ext-working .loading' ).hide();
			if (response) {
				if (response == 'SUCCESS') {
					statusEl.css( 'color', '#21759B' );
					statusEl.html( __( 'Updated', 'mainwp-pagespeed-extension' ) ).show();
					statusEl.fadeOut( 3000 );
				} else {
					statusEl.css( 'color', 'red' );
					statusEl.html( __( "Update failed", 'mainwp-pagespeed-extension' ) ).show();
				}
			} else {
				statusEl.css( 'color', 'red' );
				statusEl.html( __( "Undefined error", 'mainwp-pagespeed-extension' ) ).show();
			}
		}, 'json');
	});

	// Close modal and reload page.
	$( '#mainwp-pagespeed-sync-modal .ui.reload.cancel.button' ).on( 'click', function() {
		window.location.href = "admin.php?page=Extensions-Mainwp-Page-Speed-Extension&tab=settings";
	} );

});

var pspeed_bulkMaxThreads = 3;
var pspeed_bulkTotalThreads = 0;
var pspeed_bulkCurrentThreads = 0;
var pspeed_bulkFinishedThreads = 0;

// Manage Bulk Actions
mainwp_pagespeed_do_bulk_action = function( act ) {
	var selector = '';
	switch ( act ) {
		case 'activate-selected':
			selector += '#mainwp-page-speed-sites-table tbody tr.negative';
			jQuery( selector ).addClass( 'queue' );
			mainwp_pagespeed_active_start_next( selector );
			break;
		case 'update-selected':
			selector += '#mainwp-page-speed-sites-table tbody tr.warning';
			jQuery( selector ).addClass( 'queue' );
			mainwp_pagespeed_upgrade_start_next( selector );
			break;
		case 'hide-selected':
			selector += '#mainwp-page-speed-sites-table tbody tr .pagespeed_showhide_plugin[showhide="hide"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_pagespeed_showhide_start_next( selector );
			break;
		case 'show-selected':
			selector += '#mainwp-page-speed-sites-table tbody tr .pagespeed_showhide_plugin[showhide="show"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_pagespeed_showhide_start_next( selector );
			break;
		case 'check-pages':
			selector += '#mainwp-page-speed-sites-table tbody tr.pagespeed-active';
			jQuery( selector ).addClass( 'queue' );
			mainwp_pagespeed_check_pages_start_next( selector, false );
			break;
		case 'force-recheck-pages':
			selector += '#mainwp-page-speed-sites-table tbody tr.pagespeed-active';
			jQuery( selector ).addClass( 'queue' );
			mainwp_pagespeed_check_pages_start_next( selector, true );
			break;
	}
}

mainwp_pagespeed_check_pages_start_next = function (selector, forceRecheck) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && (objProcess.length > 0) && ( pspeed_bulkCurrentThreads < pspeed_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).find( 'input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_pagespeed_check_pages_start_specific( objProcess, selector, forceRecheck );
	}
}

mainwp_pagespeed_check_pages_start_specific = function (pObj, selector, forceRecheck) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.status-el' );
	var bulk = true;
	if (bulk) {
		pspeed_bulkCurrentThreads++;
    }
	var data = {
		action: 'mainwp_pagespeed_perform_check_pages',
		websiteId: parent.attr( 'website-id' ),
		force_recheck: forceRecheck,
		nonce: mainwpPagespeed.nonce
	}
	statusEl.html('<i class="notched circle loading icon"></i>').show();
	jQuery.post(ajaxurl, data, function (response) {
		pObj.removeClass( 'queue' );
		if (response) {
			if (response['error']) {
				statusEl.html( response['error'] );
			} else if (response['result'] == 'SUCCESS') {

//				var str = '';
//				if (response['checked_pages']) {
//					str = __( 'Starting Reporting. Google Pagespeed will work in the background to load and report on each URL.', 'mainwp-pagespeed-extension' );
//				} else {
//					str = __( 'Not check pages.', 'mainwp-pagespeed-extension' );
//				}

				statusEl.html( '<i class="green check icon"></i>' );
                setTimeout(function ()
                {
                    statusEl.fadeOut();
                }, 3000);
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		if (bulk) {
			pspeed_bulkCurrentThreads--;
			pspeed_bulkFinishedThreads++;
			mainwp_pagespeed_check_pages_start_next( selector );
		}

	}, 'json');
	return false;
}

// Loop through sites to Show/Hide the plugin
mainwp_pagespeed_showhide_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( pspeed_bulkCurrentThreads < pspeed_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_pagespeed_showhide_start_specific( objProcess, true, selector );
	}
}

// Show/Hide the plugin
mainwp_pagespeed_showhide_start_specific = function( pObj, bulk, selector ) {
	var parent = pObj.closest( 'tr' );
	var showhide = pObj.attr( 'showhide' );
	var statusEl = parent.find( '.pagespeed-visibility' );

	if ( bulk ) {
		pspeed_bulkCurrentThreads++;
	}

	var data = {
		action: 'mainwp_pagespeed_showhide_pagespeed',
		websiteId: parent.attr( 'website-id' ),
		showhide: showhide
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if ( response && response['result'] == 'SUCCESS' ) {
			if ( showhide == 'show' ) {
				pObj.text( "Hide plugin" );
				pObj.attr( 'showhide', 'hide' );
				statusEl.html( __( 'No' ) );
			} else {
				pObj.text( "Unhide plugin" );
				pObj.attr( 'showhide', 'show' );
				statusEl.html( __( 'Yes' ) );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
		if ( bulk ) {
			pspeed_bulkCurrentThreads--;
			pspeed_bulkFinishedThreads++;
			mainwp_pagespeed_showhide_start_next( selector );
		}
	}, 'json');
	return false;
}

// Loop through sites to update the plugin
mainwp_pagespeed_upgrade_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( pspeed_bulkCurrentThreads < pspeed_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_pagespeed_upgrade_start_specific( objProcess, true, selector );
	}
}

// Update the plugin
mainwp_pagespeed_upgrade_start_specific = function( pObj, bulk, selector ) {
	var row = pObj.closest( 'tr' );
  var statusEl = row.find( '.updating' );
	var slug = row.attr( 'plugin-slug' );
	var data = {
		action: 'mainwp_pagespeed_upgrade_plugin',
		websiteId: row.attr( 'website-id' ),
		type: 'plugin',
		'slugs[]': [slug]
	}

	if ( bulk ) {
		pspeed_bulkCurrentThreads++;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
    statusEl.html( '' );
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if ( response && response['upgrades'][slug] ) {
      pObj.remove();
			row.removeClass( 'warning' );
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
		if ( bulk ) {
			pspeed_bulkCurrentThreads--;
			pspeed_bulkFinishedThreads++;
			mainwp_pagespeed_upgrade_start_next( selector );
		}
	}, 'json');
	return false;
}

// Loop through sites to activate the plugin
mainwp_pagespeed_active_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( pspeed_bulkCurrentThreads < pspeed_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_pagespeed_active_start_specific( objProcess, true, selector );
	}
}

// Activate the plugin
mainwp_pagespeed_active_start_specific = function( pObj, bulk, selector ) {
	var row = pObj.closest( 'tr' );
  var statusEl = row.find( '.updating' );
	var slug = row.attr( 'plugin-slug' );

	var data = {
		action: 'mainwp_pagespeed_active_plugin',
		websiteId: row.attr( 'website-id' ),
		'plugins[]': [slug]
	}

	if ( bulk ) {
		pspeed_bulkCurrentThreads++;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function ( response ) {
    statusEl.html( '' );
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if ( response && response['result'] ) {
      row.removeClass( 'negative' );
			pObj.remove();
		}
		if ( bulk ) {
			pspeed_bulkCurrentThreads--;
			pspeed_bulkFinishedThreads++;
			mainwp_pagespeed_active_start_next( selector );
		}
	}, 'json');
	return false;
}

mainwp_pagespeed_update_individual_site = function (site_id) {
	var data = {
		action: 'mainwp_pagespeed_performsavepagespeedsettings',
		siteId: site_id,
		individual: 1,
		nonce: mainwpPagespeed.nonce
	};
	statusEl = jQuery( '#mainwp-message-zone' );
    statusEl.html( '<i class="notched circle loading icon"></i> Saving to child site...').show();
	jQuery.post(ajaxurl, data, function (response) {
		if (response) {
			if (response['result'] == 'SUCCESS') {
				statusEl.html( '<i class="green check icon"></i> Child site updated.' );

                setTimeout( function() {
                    statusEl.hide();
                }, 3000 );

			} else if (response['result'] == 'NOTCHANGE') {
				statusEl.html( '<i class="green check icon"></i> Settings saved with no changes.' );

                setTimeout( function() {
                    statusEl.hide();
                }, 3000 );

			} else if (response['error']) {
				statusEl.html( '<i class="red times icon"></i> ' + response['error'] );
			} else {
				statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error', 'mainwp-pagespeed-extension' ) );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error', 'mainwp-pagespeed-extension' ) );
		}
	}, 'json');
}

// Loop through sites
mainwp_pagespeed_perform_action_start_next = function( doAction ) {
	if (doAction == 'check_new_pages' || doAction == 'recheck_all_pages') {
        var forceRecheck = false;
        if (doAction == 'recheck_all_pages') {
            forceRecheck = true;
        }
        mainwp_pagespeed_do_check_pages_start_next( forceRecheck );
    } else if ( doAction == 'save_options' ) {
        mainwp_pagespeed_save_settings_start_next();
    }
};

// Loop through sites
mainwp_pagespeed_save_settings_start_next = function() {
	if ( pspeed_bulkTotalThreads == 0 ) {
		pspeed_bulkTotalThreads = jQuery( '.mainwpProccessSitesItem[status="queue"]' ).length;
	}
	while ( ( siteToProcess = jQuery( '.mainwpProccessSitesItem[status="queue"]:first' ) ) && ( siteToProcess.length > 0 ) && ( pspeed_bulkCurrentThreads < pspeed_bulkMaxThreads ) ) {
		mainwp_pagespeed_save_settings_start_specific( siteToProcess );
	}
};

// Process sites to sync data or trigger actions
mainwp_pagespeed_save_settings_start_specific = function ( pSiteToProcess ) {
	pspeed_bulkCurrentThreads++;
	pSiteToProcess.attr( 'status', 'progress' );
	var statusEl = pSiteToProcess.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );
	var data = {
		action: 'mainwp_pagespeed_performsavepagespeedsettings',
		siteId: pSiteToProcess.attr( 'siteid' ),
		nonce: mainwpPagespeed.nonce
	};
	jQuery.post( ajaxurl, data, function ( response ) {
		pSiteToProcess.attr( 'status', 'done' );
		if ( response ) {
			if ( response['result'] == 'RUNNING' ) {
				statusEl.html( '<i class="red times icon"></i> ' + __( 'Google Pagespeed Insights plugin is running. Please, try again later.' ) ).show();
			} else if ( response['result'] == 'NOTCHANGE' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else if ( response['result'] == 'SUCCESS' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else if ( response['error'] ) {
				statusEl.html( '<i class="red times icon"></i> ' + response['error'] );
			} else {
				statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) ).show();
			}
		} else {
			statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error', 'mainwp-pagespeed-extension' ) );
		}
		pspeed_bulkCurrentThreads--;
		pspeed_bulkFinishedThreads++;
		mainwp_pagespeed_save_settings_start_next();
	}, 'json');
};


mainwp_pagespeed_do_check_pages_start_next = function( pforced ) {
	if ( pspeed_bulkTotalThreads == 0 ) {
		pspeed_bulkTotalThreads = jQuery( '.mainwpProccessSitesItem[status="queue"]' ).length;
	}
	while ( ( siteToProcess = jQuery( '.mainwpProccessSitesItem[status="queue"]:first' ) ) && ( siteToProcess.length > 0 ) && ( pspeed_bulkCurrentThreads < pspeed_bulkMaxThreads ) ) {
		mainwp_pagespeed_do_check_pages_start_specific( siteToProcess, pforced );
	}
};

// Process sites to sync data or trigger actions
mainwp_pagespeed_do_check_pages_start_specific = function ( pSiteToProcess, forceRecheck ) {
	pspeed_bulkCurrentThreads++;
	pSiteToProcess.attr( 'status', 'progress' );
	var statusEl = pSiteToProcess.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );
	var data = {
        action: 'mainwp_pagespeed_perform_check_pages',
		websiteId: pSiteToProcess.attr( 'siteid' ),
		force_recheck: forceRecheck,
		nonce: mainwpPagespeed.nonce
	};
	jQuery.post( ajaxurl, data, function ( response ) {
		pSiteToProcess.attr( 'status', 'done' );
		if ( response ) {
			if ( response['result'] == 'SUCCESS' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else if ( response['error'] ) {
				statusEl.html( '<i class="red times icon"></i> ' + response['error'] );
			} else {
				statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) ).show();
			}
		} else {
			statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error', 'mainwp-pagespeed-extension' ) );
		}
		pspeed_bulkCurrentThreads--;
		pspeed_bulkFinishedThreads++;
		mainwp_pagespeed_do_check_pages_start_next( forceRecheck );
	}, 'json');
};
