jQuery( document ).ready(function ($) {
	$( '#mwp_time_capsule_settings_save_btn' ).on('click', function () {
		var statusEl = jQuery( '#mwp_time_capsule_perform_individual_status' );
		var loaderEl = jQuery( '#the_plugin_site_settings i.loader' );
		statusEl.hide();
		loaderEl.show();
		data = {
			action: 'mainwp_time_capsule_site_override_settings',
			timecapsuleSiteID: $( 'input[name=mainwp_time_capsule_settings_site_id]' ).val(),
			override: $( '#mainwp_time_capsule_override_general_settings' ).is( ":checked" ) ? 1 : 0,
			nonce: mainwp_timecapsule_loc.nonce
		};
		jQuery.post(ajaxurl, data, function (response) {
			loaderEl.hide();
			if (response) {
				if (response.error) {
					statusEl.css( 'color', 'red' );
					statusEl.html( response.error );
				} else if (response.result == 'success') {
					statusEl.css( 'color', '#21759B' );
					statusEl.html( '<i class="fa fa-check-circle"></i> ' + __( 'Saved' ) );
					setTimeout(function ()
						{
						statusEl.fadeOut();
						location.href = location.href;
					}, 2000);
				} else {
					statusEl.css( 'color', 'red' );
					statusEl.html( '<i class="fa fa-exclamation-circle"></i> ' + 'Undefined Error' );
				}
			} else {
				statusEl.css( 'color', 'red' );
				statusEl.html( '<i class="fa fa-exclamation-circle"></i> ' + 'Undefined Error' );
			}
			statusEl.fadeIn();
		}, 'json');

		return false;
	});

});


jQuery( document ).ready(function ($) {

	$( '.mwp_time_capsule_active_plugin' ).on('click', function () {
		mainwp_time_capsule_plugin_active_start_specific( $( this ), false );
		return false;
	});

	$( '.mwp_time_capsule_upgrade_plugin' ).on('click', function () {
		mainwp_time_capsule_plugin_upgrade_start_specific( $( this ), false );
		return false;
	});

	$( '.mwp_time_capsule_showhide_plugin' ).on('click', function () {
		mainwp_time_capsule_plugin_showhide_start_specific( $( this ), false );
		return false;
	});

	$( '#mwp_time_capsule_doaction_btn' ).on('click', function () {
		var bulk_act = $( '#mwp_time_capsule_plugin_action' ).val();
		mainwp_time_capsule_plugin_do_bulk_action( bulk_act );
	});

});

var timecapsule_bulkMaxThreads = 3;
var timecapsule_bulkTotalThreads = 0;
var timecapsule_bulkCurrentThreads = 0;
var timecapsule_bulkFinishedThreads = 0;

mainwp_time_capsule_plugin_do_bulk_action = function (act) {
	var selector = '';
	switch (act) {
            case 'activate-selected':
                    selector = '#the-time-capsule-list tr.plugin-update-tr .mwp_time_capsule_active_plugin';
                    jQuery( selector ).addClass( 'queue' );
                    mainwp_time_capsule_plugin_active_start_next( selector );
                    break;
            case 'update-selected':
                    selector = '#the-time-capsule-list tr.plugin-update-tr .mwp_time_capsule_upgrade_plugin';
                    jQuery( selector ).addClass( 'queue' );
                    mainwp_time_capsule_plugin_upgrade_start_next( selector );
                    break;
            case 'hide-selected':
                    selector = '#the-time-capsule-list tr .mwp_time_capsule_showhide_plugin[showhide="hide"]';
                    jQuery( selector ).addClass( 'queue' );
                    mainwp_time_capsule_plugin_showhide_start_next( selector );
                    break;
            case 'show-selected':
                    selector = '#the-time-capsule-list tr .mwp_time_capsule_showhide_plugin[showhide="show"]';
                    jQuery( selector ).addClass( 'queue' );
                    mainwp_time_capsule_plugin_showhide_start_next( selector );
                    break;
	}
}

mainwp_time_capsule_plugin_showhide_start_next = function (selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (timecapsule_bulkCurrentThreads < timecapsule_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_time_capsule_plugin_showhide_start_specific( objProcess, true, selector );
	}
}

mainwp_time_capsule_plugin_showhide_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.wp-timecapsule-visibility' );
	var showhide = pObj.attr( 'showhide' );

    if (bulk) {
		timecapsule_bulkCurrentThreads++;
    }

	var data = {
		action: 'mainwp_time_capsule_showhide_plugin',
		timecapsuleSiteID: parent.attr( 'website-id' ),
		showhide: showhide,
        nonce: mainwp_timecapsule_loc.nonce
	}
	statusEl.html('<i class="notched circle loading icon"></i>').show();
	jQuery.post(ajaxurl, data, function (response) {
		pObj.removeClass( 'queue' );
		if (response && response['error']) {
			statusEl.html( '<i class="red times icon"></i> ' + response['error'] );
		} else if (response && response['result'] == 'SUCCESS') {
			if (showhide == 'show') {
				pObj.text( "Hide Plugin" );
				pObj.attr( 'showhide', 'hide' );
                statusEl.html( 'No' );
			} else {
				pObj.text( "Unhide Plugin" );
				pObj.attr( 'showhide', 'show' );
                statusEl.html( 'Yes' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i> ' + __( "Undefined Error" ) )
		}

		if (bulk) {
			timecapsule_bulkCurrentThreads--;
			timecapsule_bulkFinishedThreads++;
			mainwp_time_capsule_plugin_showhide_start_next( selector );
		}

	}, 'json');
	return false;
}

mainwp_time_capsule_plugin_upgrade_start_next = function (selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length > 0) && (timecapsule_bulkCurrentThreads < timecapsule_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_time_capsule_plugin_upgrade_start_specific( objProcess, true, selector );
	}
}

mainwp_time_capsule_plugin_upgrade_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest( '.ext-upgrade-noti' );
	var workingRow = parent.find( '.mwp-plugin-working-row' );
	var slug = parent.attr( 'plugin-slug' );
	workingRow.find( '.status' ).html( '' );
	var data = {
		action: 'mainwp_time_capsule_upgrade_plugin',
		timecapsuleSiteID: parent.attr( 'website-id' ),
		type: 'plugin',
		'slugs[]': slug
	}

	if (bulk) {
		timecapsule_bulkCurrentThreads++; }

	parent.closest( 'tr' ).show();
	workingRow.find( 'i' ).show();
	jQuery.post(ajaxurl, data, function (response) {
		workingRow.find( 'i' ).hide();
		pObj.removeClass( 'queue' );
		if (response && response['error']) {
			workingRow.find( '.status' ).html( '<font color="red">' + response['error'] + '</font>' );
		} else if (response && response['upgrades'][slug]) {
			pObj.after( 'WP Time Capsule plugin has been updated' );
			pObj.remove();
		} else {
			workingRow.find( '.status' ).html( '<font color="red">' + '<i class="fa fa-exclamation-circle"></i> ' + __( "Undefined Error" ) + '</font>' );
		}

		if (bulk) {
			timecapsule_bulkCurrentThreads--;
			timecapsule_bulkFinishedThreads++;
			mainwp_time_capsule_plugin_upgrade_start_next( selector );
		}

	}, 'json');
	return false;
}

mainwp_time_capsule_plugin_active_start_next = function (selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length > 0) && (timecapsule_bulkCurrentThreads < timecapsule_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_time_capsule_plugin_active_start_specific( objProcess, true, selector );
	}
}


mainwp_time_capsule_plugin_active_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( 'td.website-name .status' );
	var slug = parent.attr( 'plugin-slug' );
    pObj.removeClass( 'queue' );
	var data = {
		action: 'mainwp_time_capsule_active_plugin',
		timecapsuleSiteID: parent.attr( 'website-id' ),
		'plugins[]': [slug]
	};

	if (bulk) {
		timecapsule_bulkCurrentThreads++;
    }
	statusEl.html( '<i class="notched circle loading icon"></i>' ).show();
	jQuery.post(ajaxurl, data, function (response) {
		if (response && response['error']) {
			statusEl.html(response['error']);
		} else if (response && response['result']) {
            statusEl.html( 'WP Time Capsule plugin has been activated' );
		} else {
            statusEl.html( '<i class="red times icon"></i> ' + __('Undefined error occurred. Please, try again.') );
		}
		if (bulk) {
			timecapsule_bulkCurrentThreads--;
			timecapsule_bulkFinishedThreads++;
			mainwp_time_capsule_plugin_active_start_next( selector );
		}
	}, 'json');

	return false;
}
