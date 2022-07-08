jQuery( document ).ready(function ($) {

	// Verify import form
	jQuery( document ).on('click', '#mainwp_wpseo_btn_import', function () {
		var errors = [];

		if (jQuery( '#mainwp_wpseo_in_template_title' ).val().trim() == '') {
			errors.push( 'Please, enter a template title' );
		}

		if (jQuery( '#mainwp_wpseo_in_template_description' ).val().trim() == '') {
			errors.push( 'Please, enter a template description' );
		}

		if (errors.length > 0) {
			jQuery( '#mainwp_wpseo_import_error_box' ).html( '<p>' + errors.join( '<br />' ) + '</p>' );
			jQuery( '#mainwp_wpseo_import_error_box' ).show();
			return false;
		} else {
			jQuery( '#mainwp_wpseo_import_error_box' ).html( "" );
			jQuery( '#mainwp_wpseo_import_error_box' ).hide();
		}
	});

	// Delete template
	jQuery( document ).on( 'click', '.mainwp-yst-delete', function () {
		if ( ! confirm( "Are you sure?" )) {
			return false;
		}
		var row = $( this ).closest( 'tr' );
		var loadingEl = row.find( '.loading' );
		var statusEl = row.find( '.status' );
		var data = {
			action: 'mainwp_wpseo_delete_template',
			tempId: row.attr( 'tempid' )
		}
		loadingEl.find( 'i' ).show();
		jQuery.post(ajaxurl, data, function (response) {
			loadingEl.find( 'i' ).hide();
			if (response == 'success') {
				row.remove();
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
				setTimeout(function () {
					statusEl.fadeOut();
				}, 2000);
			}
		});
		return false;
	});

	//Trigger Plugin Activation
	$( '.mwp_seo_active_plugin' ).on('click', function () {
		mainwp_seo_active_start_specific( $( this ) );
		return false;
	});

	//Trigger Plugin Update
	$( '.mwp_seo_upgrade_plugin' ).on('click', function () {
		mainwp_seo_upgrade_start_specific( $( this ) );
		return false;
	});

	// Apply Settings
	jQuery( document ).on( 'click', '.mainwp-yst-set', function () {
		var selected_sites = [];
		var selected_groups = [];
		jQuery( '#yst_seo_error_box' ).hide();

		if ( jQuery( '#select_by' ).val() == 'site' ) {
			jQuery( "input[name='selected_sites[]']:checked" ).each(function (i) {
				selected_sites.push( jQuery( this ).val() );
			});
		} else {
			jQuery( "input[name='selected_groups[]']:checked" ).each(function (i) {
				selected_groups.push( jQuery( this ).val() );
			});
		}

		if ( selected_groups.length == 0 && selected_sites.length == 0 ) {
			jQuery( '#yst_seo_error_box' ).html( '<i class="close icon"></i>' + __( 'Please select at least one website or group.' ) ).show();
			return false;
		}

		var row = $( this ).closest( 'tr' );
		var loadingEl = row.find( '.loading' );
		var statusEl = row.find( '.status' );
		var data = {
			action: 'mainwp_wpseo_set_template_loading',
			tempId: row.attr( 'tempid' ),
			sites: selected_sites,
			groups: selected_groups
		}

		loadingEl.find( 'i' ).show();
		jQuery.post( ajaxurl, data, function ( response ) {
			loadingEl.find( 'i' ).hide();
			var error = false;
			if ( response ) {
				if ( response['result'] ) {
					$( '#mainwp-wordpress-seo-settings-tab' ).find( '.mainwp-main-content' ).html( response['result'] );
					$( '#mainwp-wordpress-seo-sync-modal' ).modal( 'show' );
					mainwp_wpseo_set_temp_start();
				} else if ( response['error'] ) {
					statusEl.html( '<i class="red check icon"></i>' );
					error = true;
				} else {
					statusEl.html( '<i class="red check icon"></i>' );
					error = true;
				}
			} else {
				statusEl.html( '<i class="red check icon"></i>' );
				error = true;
			}
			if ( error ) {
				setTimeout(function () {
					statusEl.fadeOut();
				}, 3000 );
			}
		}, 'json');
		return false;
	});
});

//Activate Plugin
mainwp_seo_active_start_specific = function (pObj) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.updating' );
	var slug = parent.attr( 'plugin-slug' );
	var data = {
		action: 'mainwp_seo_active_plugin',
		websiteId: parent.attr( 'website-id' ),
		'plugins[]': [slug]
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );
	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html( '' );
		if (response && response['error']) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if (response && response['result']) {
			parent.removeClass( 'negative' );
			pObj.remove();
		}
	}, 'json');
	return false;
}

//Update plugin
mainwp_seo_upgrade_start_specific = function (pObj) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.updating' );
	var slug = parent.attr( 'plugin-slug' );
	var data = {
		action: 'mainwp_seo_upgrade_plugin',
		websiteId: parent.attr( 'website-id' ),
		type: 'plugin',
		'slugs[]': [slug]
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );
	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html( '' );
		if (response && response['error']) {
		statusEl.html( '<i class="red times icon"></i>' );
		} else if (response && response['upgrades'][slug]) {
			parent.removeClass( 'warning' );
			pObj.remove();
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

	}, 'json');
	return false;
}

var seo_MaxThreads = 3;
var seo_CurrentThreads = 0;
var seo_TotalThreads = 0;
var seo_FinishedThreads = 0;

mainwp_wpseo_set_temp_start = function () {
	mainwp_wpseo_set_temp_start_next();
}

mainwp_wpseo_set_temp_start_next = function () {
	if (seo_TotalThreads == 0) {
		seo_TotalThreads = jQuery( '.mainwpSetSiteItem[status="queue"]' ).length;
	}
	while ((siteToRun = jQuery( '.mainwpSetSiteItem[status="queue"]:first' )) && (siteToRun.length > 0) && (seo_CurrentThreads < seo_MaxThreads)) {
		mainwp_wpseo_set_temp_start_specific( siteToRun );
	}
}

mainwp_wpseo_set_temp_start_specific = function ( pSiteToRun ) {
	seo_CurrentThreads++;
	pSiteToRun.attr( 'status', 'progress' );
	var statusEl = pSiteToRun.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );
	var data = {
		action: 'mainwp_wpseo_set_template',
		siteId: pSiteToRun.attr( 'siteid' ),
		tempId: jQuery( '#mainwp_yst_set_temp_id' ).val()
	};

	jQuery.post(ajaxurl, data, function (response) {
		pSiteToRun.attr( 'status', 'done' );
		if ( ! response) {
			statusEl.html( '<i class="red times icon"></i>' ).show();
		} else {
			if ( response['error'] && response['error'] === 'NO_WPSEO' ) {
				statusEl.html( '<i class="red times icon"></i>' ).show();
			} else if ( response['error'] ) {
				statusEl.html( '<i class="red times icon"></i>' ).show();
			} else if ( response['success'] ) {
				statusEl.html( '<i class="green check icon"></i>' ).show();
			} else {
				statusEl.html( '<i class="red times icon"></i>' ).show();
			}
		}

		seo_CurrentThreads--;
		seo_FinishedThreads++;

		if ( seo_FinishedThreads == seo_TotalThreads && seo_FinishedThreads != 0 ) {
			window.location.reload();
		}
		mainwp_wpseo_set_temp_start_next();
	}, 'json');
};
