jQuery( document ).ready( function( $ ) {

	jQuery( '.mainwp-domain-monitor-status-show-modal' ).on( 'click', function() {
		var parent = jQuery( this ).parent( 'span.mainwp-domain-monitor-status-details' );
		var meaning = parent.find( '.mainwp-domain-monitor-status-meaning' ).val();
		var action = parent.find( '.mainwp-domain-monitor-status-action' ).val();

		jQuery( '#mainwp-domain-status-info-modal' ).modal( {
			onShow : function() {
				jQuery( '#mainwp-domain-status-info-modal .content' ).append( '<h3>What does it mean?</h3>' + meaning + '<h4>Should you do something?</h4>' + action );
	    },
			onHide : function() {
				jQuery( '#mainwp-domain-status-info-modal .content' ).html('');
	    },
		} ).modal( 'show' );

		return false;
	} );

	jQuery( '#mainwp-domain-monitor-automatic-checks' ).change( function() {
		if( this.checked ) {
			jQuery( '#mainwp-domain-monitor-automatic-checks-toggle-area' ).fadeIn();
		} else {
			jQuery( '#mainwp-domain-monitor-automatic-checks-toggle-area' ).fadeOut();
		}
	} );

	jQuery( '#mainwp-domain-monitor-overwrite-general-settings' ).change( function() {
		if( this.checked ) {
			jQuery( '.mainwp-domain-monitor-overwrite-general-settings-toggle-area' ).fadeIn();
		} else {
			jQuery( '.mainwp-domain-monitor-overwrite-general-settings-toggle-area' ).fadeOut();
		}
	} );

	jQuery( '.domain-monitor-action-recheck' ).on( 'click', function() {
		var row = jQuery( this ).closest('tr');
		var columns = 12;
		var site_name = row.find( 'a.mainwp-site-name-link' ).html();
		var data = {
			action: 'mainwp_domain_monitor_check_site_domain',
			websiteId: row.attr( 'website-id' ),
			nonce: mainwpDomainMonitor.nonce
		}

		row.html( '<td></td><td colspan="' + columns  + '"><i class="notched circle loading icon"></i> ' + site_name + ' domain lookup in progress. Please wait...</td>' );
		jQuery.post( ajaxurl, data, function( response ) {
			row.removeClass( 'queue' );
			if ( response ) {
				if ( response['error'] ) {
					row.html( '<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' domain lookup failed. ' + response['error'] + ' Page will reload in 3 seconds.</td>' );
				} else if (response['status'] == 'success') {
					row.html( '<td></td><td colspan="' + columns + '"><i class="green check icon"></i> ' + site_name + ' domain lookup completed successfully. Page will reload in 3 seconds.</td>' );
				} else {
					row.html( '<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' domain lookup failed. Page will reload in 3 seconds.</td>' );
				}
			} else {
				row.html( '<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' domain lookup failed. Page will reload in 3 seconds.</td>' );
			}

			setTimeout( function() {
				window.location.reload();
			}, 3000 );

		}, 'json');
	} );

	// Trigger the bulk actions
	jQuery( '#mainwp-domain-monitor-bulk-actions-button' ).on( 'click', function() {
		var action = jQuery( '#mainwp-domain-monitor-bulk-actions-menu' ).dropdown( "get value" );
		mainwp_domain_monitor_bulk_actions_selector( action );
	} );

} );

var domain_monitor_max_threads = 5;
var domain_monitor_total_threads = 0;
var domain_monitor_current_threads = 0;
var domain_monitor_completed_threads = 0;
var domain_monitor_errors = 0;

// Manage Bulk Actions
mainwp_domain_monitor_bulk_actions_selector = function( action ) {
	var selector = '';
	switch ( action ) {
		case 'check-sites':
			selector += '#mainwp-domain-monitor-sites-table tbody tr';
			jQuery( selector ).addClass( 'queue' );
			mainwp_domain_monitor_bulk_check_start_next( selector, true );
		break;
		case 'open-wpadmin':
			jQuery( '#mainwp-domain-monitor-sites-table .check-column INPUT:checkbox:checked' ).each( function () {
				var row = jQuery( this ).closest( 'tr' );
				var url = row.find( 'a.open_newwindow_wpadmin' ).attr( 'href' );
				window.open( url, '_blank' );
			} );
		break;
		case 'open-frontpage':
			jQuery( '#mainwp-domain-monitor-sites-table .check-column INPUT:checkbox:checked' ).each( function () {
				var row = jQuery( this ).closest( 'tr' );
				var url = row.find( 'a.open_site_url' ).attr( 'href' );
				window.open( url, '_blank' );
			} );
			break;
	}
}

mainwp_domain_monitor_bulk_check_start_next = function( selector ) {
	if ( domain_monitor_total_threads == 0 ) {
		domain_monitor_total_threads = jQuery( '#mainwp-domain-monitor-sites-table tbody' ).find( 'input[type="checkbox"]:checked' ).length;
	}

	while ( ( item_to_process = jQuery( selector + '.queue:first' ) ) && ( item_to_process.length > 0 ) && ( domain_monitor_current_threads < domain_monitor_max_threads ) ) {
		item_to_process.removeClass( 'queue' );
		if ( item_to_process.closest( 'tr' ).find( 'input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_domain_monitor_bulk_check_start_specific( item_to_process, selector );
	}
}

mainwp_domain_monitor_bulk_check_start_specific = function( item_to_process, selector ) {
	var row = item_to_process.closest( 'tr' );
	var columns = 12;
	var site_name = row.find( 'a.mainwp-site-name-link' ).html();
	var bulk = true;

	if ( bulk ) {
		domain_monitor_current_threads++;
	}

	var data = {
		action: 'mainwp_domain_monitor_check_site_domain',
		websiteId: item_to_process.attr( 'website-id' ),
		nonce: mainwpDomainMonitor.nonce
	};

	row.html( '<td></td><td colspan="' + columns  + '"><i class="notched circle loading icon"></i> ' + site_name + ' domain lookup in progress. Please wait...</td>' );

	jQuery.post( ajaxurl, data, function( response ) {
		item_to_process.removeClass( 'queue' );
		if ( response ) {
			if ( response['error'] ) {
				row.html( '<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' domain lookup failed. ' + response['error'] + ' Page will reload in 3 seconds.</td>' );
			} else if ( response['status'] == 'success' ) {
				row.html( '<td></td><td colspan="' + columns + '"><i class="green check icon"></i> ' + site_name + ' domain lookup completed successfully.</td>' );
			} else {
				row.html( '<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' domain lookup failed. Please try again.</td>' );
			}
		} else {
			row.html( '<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' domain lookup failed. Please try again.</td>' );
		}

		if ( bulk ) {
			domain_monitor_current_threads--;
			domain_monitor_completed_threads++;

			mainwp_domain_monitor_bulk_check_start_next( selector );
		}

		if ( domain_monitor_total_threads == domain_monitor_completed_threads ) {
			setTimeout( function() {
				window.location.reload( true );
			}, 3000 );
		}

	}, 'json' );
	return false;
}

// Loop through sites
mainwp_domain_monitor_start_next = function() {
	mainwp_domain_monitor_popup_check_next();
};

mainwp_domain_monitor_popup_check_next = function() {
	if ( domain_monitor_total_threads == 0 ) {
		domain_monitor_total_threads = jQuery( '.item[status="queue"]' ).length;
	}

	while ( ( item_to_process = jQuery( '.item[status="queue"]:first' ) ) && ( item_to_process.length > 0 ) && ( domain_monitor_current_threads < domain_monitor_max_threads ) ) {
		mainwp_domain_monitor_popup_check_specific( item_to_process );
	}
};

mainwp_domain_monitor_popup_check_specific = function( item_to_process ) {

	domain_monitor_current_threads++;
	item_to_process.attr( 'status', 'progress' );

	var statusEl = item_to_process.find( '.status' ).html( '<span data-tooltip="Checking. Please wait..." data-inverted="" data-position="left center"><i class="notched circle loading icon"></i></span>' );
	var data = {
		action: 'mainwp_domain_monitor_check_site_domain',
		websiteId: item_to_process.attr( 'siteid' ),
		nonce: mainwpDomainMonitor.nonce
	};

	jQuery.post( ajaxurl, data, function( response ) {
		item_to_process.attr( 'status', 'done' );
		if ( response ) {
			if ( response['status'] == 'success' ) {
				statusEl.html( '<span data-tooltip="Domain lookup completed successfully." data-inverted="" data-position="left center"><i class="green check icon"></i></span>' );
			} else if ( response['error'] ) {
				statusEl.html( '<span data-tooltip="' + response['error'] + '" data-inverted="" data-position="left center"><i class="red times icon"></i></span>' );
				domain_monitor_errors++;
			} else {
				statusEl.html( '<span data-tooltip="No response from the WHOIS server. Please try again." data-inverted="" data-position="left center"><i class="red times icon"></i></span>' ).show();
				domain_monitor_errors++;
			}
		} else {
			statusEl.html( '<span data-tooltip="No response from the WHOIS server. Please try again." data-inverted="" data-position="left center"><i class="red times icon"></i></span>' );
			domain_monitor_errors++;
		}

		domain_monitor_current_threads--;
		domain_monitor_completed_threads++;

		jQuery( '#mainwp-domain-monitor-sync-modal .mainwp-modal-progress' ).progress( { value: domain_monitor_completed_threads, total:  domain_monitor_total_threads } );
		jQuery( '#mainwp-domain-monitor-sync-modal .mainwp-modal-progress' ).find( '.label' ).html( domain_monitor_completed_threads + '/' + domain_monitor_total_threads + ' ' + __( 'Completed' ) );

		if ( domain_monitor_completed_threads == domain_monitor_total_threads ) {
			if ( domain_monitor_errors == 0 ) {
				jQuery( '#mainwp-domain-monitor-modal-progress-feedback' ).addClass( 'green' );
				jQuery( '#mainwp-domain-monitor-modal-progress-feedback' ).html( 'Process completed without errors. Page will reload in 3 seconds.' );
				setTimeout( function() {
					window.location.href = 'admin.php?page=Extensions-Mainwp-Domain-Monitor-Extension&tab=dashboard';
				}, 3000 );
			} else {
				jQuery( '#mainwp-domain-monitor-modal-progress-feedback' ).addClass( 'red' );
				jQuery( '#mainwp-domain-monitor-modal-progress-feedback' ).html( 'Process completed with errors.' );
			}
		}

		mainwp_domain_monitor_popup_check_next();

	}, 'json');
};
