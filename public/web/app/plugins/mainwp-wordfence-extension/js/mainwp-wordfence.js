jQuery(document).ready(function ($) {
	jQuery('#mfc_redirectForm').submit();

	$('.mainwp-wordfence-menu a').on('click', function () {
		jQuery('.mainwp-wordfence-menu a').removeClass('active');
		jQuery(this).addClass('active');

		if (typeof MWP_WFAD !== undefined)
			MWP_WFAD._tabHasFocus = false;

		if (jQuery(this).attr('href') != '#') {
			return;
		}

		jQuery('.mainwp_wfc_tabs_content').hide();
		jQuery('.mainwp_wfc_tabs_content[data-tab="' + jQuery(this).data('tab') + '"]').show();
		return false;
	});

	$('.wfc_plugin_upgrade_noti_dismiss').on('click', function () {
		var parent = $(this).closest('.ext-upgrade-noti');
		parent.hide();
		var data = {
			action: 'mainwp_wfc_upgrade_noti_dismiss',
			siteId: parent.attr('id'),
			new_version: parent.attr('version'),
		}
		jQuery.post(ajaxurl, data, function (response) {

		});
		return false;
	});

	$('.mwp_wfc_active_plugin').on('click', function () {
		mainwp_wfc_plugin_active_start_specific($(this), false);
		return false;
	});

	$('.mwp_wfc_upgrade_plugin').on('click', function () {
		mainwp_wfc_plugin_upgrade_start_specific($(this), false);
		return false;
	});

	$('.mwp_wfc_showhide_plugin').on('click', function () {
		mainwp_wfc_plugin_showhide_start_specific($(this), false);
		//return false;
	});

	$('#wfc_plugin_doaction_btn').on('click', function () {
		var bulk_act = $('#mwp_wfc_plugin_action').val();
		mainwp_wfc_plugin_do_bulk_action(bulk_act);

	});

	$('.mwp_wfc_scan_now_lnk').on('click', function (e) {
		var trObj = $(this).closest('tr');
		mainwp_wfc_scan_start_specific(trObj, false);
	});

	$('.wfc_metabox_scan_now_lnk').on('click', function () {
		var statusEl = $('#wfc_metabox_working_row').find('.status');
		var loader = $('#wfc_metabox_working_row').find('.loading');
		var data = {
			action: 'mainwp_wfc_scan_now',
			siteId: $(this).attr('site-id')
		}
		loader.show();
		statusEl.hide();
		jQuery.post(ajaxurl, data, function (response) {
			loader.hide();
			if (response) {
				if (response['error']) {
					if (response['error'] == 'SCAN_RUNNING') {
						statusEl.css('color', 'red');
						statusEl.html(__("A scan is already running")).show();
					} else {
						statusEl.css('color', 'red');
						statusEl.html(response['error']).show();
					}
				} else if (response['result'] == 'SUCCESS') {
					statusEl.css('color', '#21759B');
					statusEl.html(__('Requesting a New Scan')).show();
					setTimeout(function () {
						statusEl.fadeOut();
					}, 3000);
				} else {
					statusEl.css('color', 'red');
					statusEl.html(__("Undefined error")).show();
				}
			} else {
				statusEl.css('color', 'red');
				statusEl.html(__("Undefined error")).show();
			}
		}, 'json');
		return false;
	});

	$('#mainwp-wfc-run-scan').on('click', function () {
		if (!confirm('You are about to scan all Child Sites?'))
			return false;
		var selector = '#mainwp-wordfence-sites-table tbody tr.plugin-active';
		jQuery(selector).each(function () {
			jQuery(this).find('.check-column input[type="checkbox"]:checked').closest('tr').addClass('queue');
		});

		if (jQuery(selector + '.queue:first').length == 0) {
			alert('Please select websites to scan');
		}

		mainwp_wfc_scan_start_next(selector);
		return false;
	});

	$('#mainwp-wfc-kill-scan').on('click', function () {
		if (!confirm('You are about to Stop the Scan Process on Child Sites?'))
			return false;
		var selector = '#mainwp-wordfence-sites-table tr.plugin-active';
		jQuery(selector).each(function () {
			jQuery(this).find('.check-column input[type="checkbox"]:checked').closest('tr').addClass('queue');
		});

		if (jQuery(selector + '.queue:first').length == 0) {
			alert('Please select websites to Stop the Scan Process');
		}

		mainwp_wfc_kill_start_next(selector);
		return false;
	});

	$('#mainwp-wfc-widget-run-scan').on('click', function () {
		return false;
	});

	$('#wfc_btn_savegeneral').on('click', function () {
		if (!confirm('Are you sure?'))
			return false;

		var statusEl = $('#wfc_save_settings_status');
		statusEl.css('color', '#21759B');
		statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Saving ...');
		var data = {
			action: 'mainwp_wfc_save_general_settings_to_child',
			siteId: $('#wfc_individual_settings_site_id').val(),
			nonce: mainwp_WordfenceAdminVars.nonce
		}
		var me = this;
		jQuery(me).attr('disabled', true);
		jQuery.post(ajaxurl, data, function (response) {
			jQuery(me).attr('disabled', false);
			statusEl.html('');
			if (response) {
				if (response['error']) {
					statusEl.html('<span style="color:red">' + response['error'] + '</span>').show();
				} else if (response['ok']) {
					statusEl.html(__('Saved')).show();
					setTimeout(function () {
						statusEl.fadeOut();
					}, 5000);
				} else {
					statusEl.html('<span style="color:red">' + __("Undefined error") + '</span>').show();
				}
			} else {
				statusEl.html('<span style="color:red">' + __("Undefined error") + '</span>').show();
			}
		}, 'json');
		return false;

	});

	$('#mainwp_wfc_override_global_setting').on('change', function () {
		var statusEl = $('.wfc_change_override_working');
		statusEl.css('color', '#21759B');
		statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Saving ...');

		var data = {
			action: 'mainwp_wfc_change_override_general_settings',
			siteId: $('#wfc_individual_settings_site_id').val(),
			override: $(this).is(':checked') ? 1 : 0
		}
		jQuery.post(ajaxurl, data, function (response) {
			statusEl.html('');
			if (response) {
				if (response['error']) {
					statusEl.css('color', 'red');
					statusEl.html(response['error']).show();
				} else if (response['ok']) {
					statusEl.css('color', '#21759B');
					statusEl.html(__('Saved')).show();
					setTimeout(function () {
						statusEl.fadeOut();
					}, 5000);
				} else {
					statusEl.css('color', 'red');
					statusEl.html(__("Undefined error")).show();
				}
			} else {
				statusEl.css('color', 'red');
				statusEl.html(__("Undefined error")).show();
			}
		}, 'json');
		return false;

	});

	$('#mwp_wordfence_general_use_premium').on('change', function () {
		var statusEl = $('#wfc_change_use_premium_working');
		statusEl.css('color', '#21759B');
		statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Saving ...');
		var data = {
			action: 'mainwp_wfc_change_general_settings_use_premium',
			value: $(this).is(':checked') ? 1 : 0,
			nonce: mainwp_WordfenceAdminVars.nonce
		}

		jQuery.post(ajaxurl, data, function (response) {
			statusEl.html('');
			if (response) {
				if (response['error']) {
					statusEl.css('color', 'red');
					statusEl.html(response['error']).show();
				} else if (response['ok']) {
					statusEl.css('color', '#21759B');
					statusEl.html(__('Saved')).show();
					setTimeout(function () {
						statusEl.fadeOut();
						window.location.href = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_setting';
					}, 1000);

				} else {
					statusEl.css('color', 'red');
					statusEl.html(__("Undefined error")).show();
				}
			} else {
				statusEl.css('color', 'red');
				statusEl.html(__("Undefined error")).show();
			}
		}, 'json');
		return false;

	});

	jQuery('#wfc-load-more-api').on('click', function () {
		mainwp_wfc_load_more_keys();
		return false;
	})
});


mainwp_wfc_load_more_keys = function () {
	var statusEl = jQuery('.load-more-status');
	var paged = jQuery('#wfc-load-more-api').attr('load-paged');
	paged = parseInt(paged, 10);
	statusEl.html('<i class="ui active inline loader tiny"></i> Loading ...').show();
	var data = {
		action: 'mainwp_wfc_load_more_keys',
		paged: paged + 1,
		nonce: mainwp_WordfenceAdminVars.nonce
	}
	jQuery.post(ajaxurl, data, function (response) {
		statusEl.hide();
		if (response) {
			if (response['error']) {
				statusEl.html(response['error']).show();
			} else if ('result' in response) {
				if ('' != response['result']) {
					jQuery('.wfc-load-more-api-wrapper').before(response['result']);
				}
				jQuery('#wfc-load-more-api').attr('load-paged', paged + 1);
				if (response['last']) {
					jQuery('#wfc-load-more-api').hide();
				} else {
					mainwp_wfc_load_more_keys();
				}
			} else {
				statusEl.html(__("Undefined error")).show();
			}
		} else {
			statusEl.html(__("Undefined error")).show();
		}
	}, 'json');
}

mainwp_wfc_diagnostic_load_more_sites = function () {
	var statusEl = jQuery('.load-more-status');
	var paged = jQuery('.mainwp_wfc_select_diagnostic_wrapper').attr('load-paged');
	paged = parseInt(paged, 10);
	statusEl.html('<i class="ui active inline loader tiny"></i> Loading ...').show();
	var data = {
		action: 'mainwp_wfc_diagnostic_load_more_sites',
		paged: paged + 1,
		nonce: mainwp_WordfenceAdminVars.nonce
	}
	jQuery.post(ajaxurl, data, function (response) {
		statusEl.hide();
		if (response) {
			if ('result' in response) {
				if ('' != response['result']) {
					jQuery('#mainwp_wfc_diagnostic_info').append(response['result']);
				}
				jQuery('.mainwp_wfc_select_diagnostic_wrapper').attr('load-paged', paged + 1);
				if (!response['last']) {
					mainwp_wfc_diagnostic_load_more_sites();
				} else {
					jQuery('.mainwp_wfc_select_diagnostic_wrapper .ui.dropdown').dropdown();
				}
			}
		}
	}, 'json');
}


mainwp_wfc_load_more_dashboard_sites = function ( pSelgroups ) {
	var statusEl = jQuery('.load-more-status');
	var paged = jQuery('#mainwp-wordfence-sites-table-body').attr('load-paged');
	paged = parseInt(paged, 10);
	statusEl.html('<i class="ui active inline loader tiny"></i> Loading ...').show();
	var data = {
		action: 'mainwp_wfc_load_more_dashboard_sites',
		paged: paged + 1,
		selected_groups: pSelgroups,
		nonce: mainwp_WordfenceAdminVars.nonce
	}
	jQuery.post(ajaxurl, data, function (response) {
		statusEl.hide();
		if (response) {
			if ('result' in response) {
				if ('' != response['result']) {
					jQuery('#mainwp-wordfence-sites-table-body').append(response['result']);
				}
				jQuery('#mainwp-wordfence-sites-table-body').attr('load-paged', paged + 1);
				if (!response['last']) {
					mainwp_wfc_load_more_dashboard_sites( pSelgroups );
				} else {
					mainwp_wfc_dashboard_init_table();
				}
			}
		}
	}, 'json');
}


mainwp_wfc_dashboard_init_table = function () {
	jQuery('#mainwp-wordfence-sites-table').DataTable({
		"stateSave": true,
		"stateDuration": 0, // forever
		"scrollX": true,
		"colReorder": true,
		"columnDefs": [{ "orderable": false, "targets": "no-sort" }],
		"order": [[1, "asc"]],
		"lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
		"language": { "emptyTable": "No websites were found with the Wordfence plugin installed." },
		"drawCallback": function (settings) {
			jQuery('#mainwp-wordfence-sites-table .ui.checkbox').checkbox();
			jQuery('#mainwp-wordfence-sites-table .ui.dropdown').dropdown();
			// to fix
			jQuery('.mwp_wfc_showhide_plugin').on('click', function () {
				mainwp_wfc_plugin_showhide_start_specific(jQuery(this), false);
				//return false;
			});
			if (typeof mainwp_table_check_columns_init === 'function') {
				mainwp_table_check_columns_init();
			};
			if (typeof mainwp_datatable_fix_menu_overflow != 'undefined') {
				mainwp_datatable_fix_menu_overflow();
			}
			jQuery('#mainwp-wfc-sites-table-loader').hide();
		},
	});
	if (typeof mainwp_datatable_fix_menu_overflow != 'undefined') {
		mainwp_datatable_fix_menu_overflow();
	}
}



// this function call from code on child plugin
function mainwp_wfc_get_donwnloadlink(site_id, logfile) {
	var location = 'admin-ajax.php?&action=wordfence_downloadLogFile&_mwpNoneName=nonce&_mwpNoneValue=wp-ajax&logfile=' + encodeURIComponent(logfile);
	return 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site&websiteid=' + site_id + '&open_location=' + MWP_WFAD.utf8_to_b64(location);
}

var wfc_bulkMaxThreads = 3;
var wfc_bulkTotalThreads = 0;
var wfc_bulkCurrentThreads = 0;
var wfc_bulkFinishedThreads = 0;

mainwp_wfc_plugin_do_bulk_action = function (act) {
	var selector = '';
	switch (act) {
		case 'activate-selected':
			selector = '#mainwp-wordfence-sites-table tbody tr.negative';
			jQuery(selector).addClass('queue');
			mainwp_wfc_plugin_active_start_next(selector);
			break;
		case 'update-selected':
			selector = '#mainwp-wordfence-sites-table tbody tr.warning';
			jQuery(selector).addClass('queue');
			mainwp_wfc_plugin_upgrade_start_next(selector);
			break;
		case 'hide-selected':
			selector = '#mainwp-wordfence-sites-table tbody tr .mwp_wfc_showhide_plugin[showhide="hide"]';
			jQuery(selector).addClass('queue');
			mainwp_wfc_plugin_showhide_start_next(selector);
			break;
		case 'show-selected':
			selector = '#mainwp-wordfence-sites-table tbody tr .mwp_wfc_showhide_plugin[showhide="show"]';
			jQuery(selector).addClass('queue');
			mainwp_wfc_plugin_showhide_start_next(selector);
			break;
	}
}

mainwp_wfc_plugin_showhide_start_next = function (selector) {
	while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		objProcess.removeClass('queue');
		if (objProcess.closest('tr').find('.check-column input[type="checkbox"]:checked').length == 0) {
			continue;
		}
		mainwp_wfc_plugin_showhide_start_specific(objProcess, true, selector);
	}
}

mainwp_wfc_plugin_showhide_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest('tr');
	var showhide = pObj.attr('showhide');
	var statusEl = parent.find('.wp-wordfence-visibility');

	if (bulk) {
		wfc_bulkCurrentThreads++;
	}

	var data = {
		action: 'mainwp_wfc_showhide_plugin',
		websiteId: parent.attr('website-id'),
		showhide: showhide
	}

	statusEl.html('<i class="notched circle loading icon"></i>');

	jQuery.post(ajaxurl, data, function (response) {
		pObj.removeClass('queue');
		if (response && response['error']) {
			statusEl.html('<i class="red times icon"></i>');
		} else if (response && response['result'] == 'SUCCESS') {
			if (showhide == 'show') {
				pObj.text("Hide Plugin");
				pObj.attr('showhide', 'hide');
				parent.find('.wp-wordfence-visibility').html(__('No'));
			} else {
				pObj.text("Unhide Plugin");
				pObj.attr('showhide', 'show');
				parent.find('.wp-wordfence-visibility').html(__('Yes'));
			}
		} else {
			statusEl.html('<i class="red times icon"></i>');
		}

		if (bulk) {
			wfc_bulkCurrentThreads--;
			wfc_bulkFinishedThreads++;
			mainwp_wfc_plugin_showhide_start_next(selector);
		}

	}, 'json');
	return false;
}

mainwp_wfc_plugin_upgrade_start_next = function (selector) {
	while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (objProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		objProcess.removeClass('queue');
		if (objProcess.closest('tr').find('.check-column input[type="checkbox"]:checked').length == 0) {
			continue;
		}
		mainwp_wfc_plugin_upgrade_start_specific(objProcess, true, selector);
	}
}

mainwp_wfc_plugin_upgrade_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest('tr');
	var statusEl = parent.find('.updating');
	var slug = parent.attr('plugin-slug');

	var data = {
		action: 'mainwp_wfc_upgrade_plugin',
		websiteId: parent.attr('website-id'),
		type: 'plugin',
		'slugs[]': [slug]
	}

	if (bulk) {
		wfc_bulkCurrentThreads++;
	}

	statusEl.html('<i class="notched circle loading icon"></i>');

	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html('');
		pObj.removeClass('queue');

		if (response && response['error']) {
			statusEl.html('<i class="red times icon"></i>');
		} else if (response && response['upgrades'][slug]) {
			pObj.remove();
			parent.removeClass('warning');
		} else {
			statusEl.html('<i class="red times icon"></i>');
		}

		if (bulk) {
			wfc_bulkCurrentThreads--;
			wfc_bulkFinishedThreads++;
			mainwp_wfc_plugin_upgrade_start_next(selector);
		}

	}, 'json');
	return false;
}

mainwp_wfc_plugin_active_start_next = function (selector) {
	while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (objProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		objProcess.removeClass('queue');
		if (objProcess.closest('tr').find('.check-column input[type="checkbox"]:checked').length == 0) {
			continue;
		}
		mainwp_wfc_plugin_active_start_specific(objProcess, true, selector);
	}
}

mainwp_wfc_plugin_active_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest('tr');
	var statusEl = parent.find('.updating');
	var slug = parent.attr('plugin-slug');

	var data = {
		action: 'mainwp_wfc_active_plugin',
		websiteId: parent.attr('website-id'),
		'plugins[]': [slug]
	}

	if (bulk) {
		wfc_bulkCurrentThreads++;
	}

	statusEl.html('<i class="notched circle loading icon"></i>');

	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html('');
		pObj.removeClass('queue');
		if (response && response['error']) {
			statusEl.html('<i class="red times icon"></i>');
		} else if (response && response['result']) {
			parent.removeClass('negative');
			pObj.remove();
		}
		if (bulk) {
			wfc_bulkCurrentThreads--;
			wfc_bulkFinishedThreads++;
			mainwp_wfc_plugin_active_start_next(selector);
		}

	}, 'json');
	return false;
}

mainwp_wfc_scan_start_next = function (selector) {
	while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		objProcess.removeClass('queue');
		mainwp_wfc_scan_start_specific(objProcess, true, selector);
	}
}

mainwp_wfc_scan_start_specific = function (pObj, bulk, selector) {

	var statusEl = pObj.find('.wfc-scan-working .status');
	var loader = pObj.find('.wfc-scan-working i');

	var data = {
		action: 'mainwp_wfc_scan_now',
		siteId: pObj.attr('website-id')
	}

	if (bulk) {
		wfc_bulkCurrentThreads++;
	}

	loader.show();

	jQuery.post(ajaxurl, data, function (response) {
		loader.hide();

		if (response) {
			if (response['error']) {
				if (response['error'] == 'SCAN_RUNNING') {
					statusEl.html('<span data-tooltip="Scan already running. Please try again later." data-inverted="inverted"><i class="exclamation yellow triangle icon"></i></span>');
				} else {
					statusEl.html('<span data-tooltip="' + response['error'] + '" data-inverted="inverted"><i class="red times icon"></i></span>');
				}
			} else if (response['result'] == 'SUCCESS') {
				statusEl.html('<span data-tooltip="Scan process requested." data-inverted="inverted"><i class="green check icon" ></i></span>');
				setTimeout(function () {
					statusEl.html('');
				}, 3000);
			} else {
				statusEl.html('<span data-tooltip="Undefined error occurred. Please try again." data-inverted="inverted"><i class="red times icon"></i></span>');
			}
		} else {
			statusEl.html('<span data-tooltip="Undefined error occurred. Please try again." data-inverted="inverted"><i class="red times icon"></i></span>');
		}

		if (bulk) {
			wfc_bulkCurrentThreads--;
			wfc_bulkFinishedThreads++;
			mainwp_wfc_scan_start_next(selector);
		}

	}, 'json');
	return false;
}



mainwp_wfc_kill_start_next = function (selector) {
	while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		objProcess.removeClass('queue');
		mainwp_wfc_kill_start_specific(objProcess, true, selector);
	}
}

mainwp_wfc_kill_start_specific = function (pObj, bulk, selector) {
	//var parent = pObj.closest( 'tr' );

	var statusEl = pObj.find('.wfc-scan-working .status');
	var loader = pObj.find('.wfc-scan-working i');

	var data = {
		action: 'mainwp_wfc_kill_scan_now',
		siteId: pObj.attr('website-id')
	}

	if (bulk) {
		wfc_bulkCurrentThreads++;
	}

	loader.show();

	jQuery.post(ajaxurl, data, function (response) {
		loader.hide();

		if (response) {
			if (response['error']) {
				statusEl.html('<i class="red times icon" data-tooltip="' + response['error'] + '" data-inverted="inverted"></i>');
			} else if (response['ok']) {
				statusEl.html('<span data-tooltip="' + response['error'] + '" data-inverted="inverted"><i class="red times icon"></i></span>');
				setTimeout(function () {
					statusEl.fadeOut();
				}, 3000);
			} else {
				statusEl.html('<span data-tooltip="Undefined error occurred. Please try again." data-inverted="inverted"><i class="red times icon"></i></span>');
			}
		} else {
			statusEl.html('<span data-tooltip="Undefined error occurred. Please try again." data-inverted="inverted"><i class="red times icon"></i></span>');
		}

		if (bulk) {
			wfc_bulkCurrentThreads--;
			wfc_bulkFinishedThreads++;
			mainwp_wfc_kill_start_next(selector);
		}

	}, 'json');
	return false;
}


mainwp_wfc_save_setting_start_next = function () {
	if (wfc_bulkTotalThreads == 0) {
		wfc_bulkTotalThreads = jQuery('.itemToProcess[status="queue"]').length;
	}
	while ((itemProcess = jQuery('.itemToProcess[status="queue"]:first')) && (itemProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		mainwp_wfc_save_setting_start_specific(itemProcess);
	}

	if (wfc_bulkFinishedThreads == wfc_bulkTotalThreads && wfc_bulkFinishedThreads != 0) {
		//        var _section = jQuery('#_post_saving_section').val();
		//        var _tab = 'network_setting';
		//        switch(_section) {
		//            case 'firewall':
		//                _tab = 'network_firewall';
		//                break;
		//            case 'blocking':
		//                _tab = 'network_blocking';
		//                break;
		//            case 'scanner':
		//                _tab = 'network_scan';
		//                break;
		//            case 'livetraffic':
		//                _tab = 'network_traffic';
		//                break;
		//            case 'diagnostics':
		//                _tab = 'diagnostics';
		//                break;
		//        }
		//        window.location.replace('admin.php?page=Extensions-Mainwp-Wordfence-Extension');
	}
};

mainwp_wfc_bulk_import_start_next = function (pToken) {
	if (wfc_bulkTotalThreads == 0) {
		wfc_bulkTotalThreads = jQuery('.itemToProcess[status="queue"]').length;
	}
	while ((itemProcess = jQuery('.itemToProcess[status="queue"]:first')) && (itemProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		mainwp_wfc_bulk_import_start_specific(itemProcess, pToken);
	}
	if (wfc_bulkFinishedThreads == wfc_bulkTotalThreads && wfc_bulkFinishedThreads != 0) {
		window.location.replace('admin.php?page=Extensions-Mainwp-Wordfence-Extension');
	}
};

mainwp_wfc_bulk_import_start_specific = function (pItemProcess, pToken) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_importSettings',
		token: pToken,
		site_id: pItemProcess.attr('siteid'),
		bulk_import: 1,
		save_import_settings: wfc_save_general_import_settings ? 1 : 0,
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	if (wfc_save_general_import_settings) {
		wfc_save_general_import_settings = false;
	}

	loaderEl.show();
	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
			} else {
				if (response.ok) {
					statusEl.html('<i class="green check icon"></i>').show();
				} else if (response.errorImport) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else {
					statusEl.html('<i class="red times icon"></i>').show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_import_start_next(pToken);
	}, 'json');
};

// saving after save general settings
mainwp_wfc_save_setting_start_specific = function (pItemProcess) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');
	var detailedEl = pItemProcess.find('.detailed');

	var data = {
		action: 'mainwp_wfc_save_settings',
		siteId: pItemProcess.attr('siteid'),
		_ajax_saving_section: jQuery('#_post_popup_saving_section').val()
	};
	loaderEl.show();
	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');

		var detail = '';
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
			} else {
				if (response.ok) {
					if (response['paidKeyMsg']) {
						statusEl.html('<i class="green check icon"></i>').show();
					} else {
						statusEl.html('<i class="green check icon"></i>').show();
					}
				} else if (response['error']) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else {
					statusEl.html(__('<i class="red times icon"></i>')).show();
				}
				if (response['invalid_users']) {
					detail += __('<i class="red times icon"></i>') + response['invalid_users'];
				}
				if (detail !== '') {
					detailedEl.css('color', 'red');
					detailedEl.html(detail).show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}

		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_save_setting_start_next();
	}, 'json');
};

// saving after save individual settings
mainwp_wfc_save_site_settings = function (site_id) {
	var process = jQuery('#mwp_wfc_ajax_message');
	var statusEl = process.find('.status');
	var loaderEl = process.find('.loading');
	var detailedEl = process.find('.detailed');

	var data = {
		action: 'mainwp_wfc_save_settings',
		siteId: site_id,
		individual: 1,
		_ajax_saving_section: jQuery('#_post_saving_section').val()
	};
	loaderEl.show();
	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('Not Updated - Individual site settings are in use').show();
				statusEl.css('color', 'red');
			} else {
				var detail = '';
				if (response.ok) {
					if (response['paidKeyMsg']) {
						statusEl.html('Congratulations! You have been upgraded to Premium Scanning. You have upgraded to a Premium API key. Once this page reloads, you can choose which premium scanning options you would like to enable and then click save.').show();
					} else {
						statusEl.html('Successful').show();
					}
					if (response['reload'] == 'reload') {
						mainwp_wfc_save_site_settings_reload(site_id);
					}
				} else if (response['error']) {
					statusEl.html(response['error']).show();
					statusEl.css('color', 'red');
				} else {
					statusEl.html(__('Undefined Error')).show();
					statusEl.css('color', 'red');
				}
				if (response['invalid_users']) {
					detail += __("The following users you selected to ignore in live traffic reports are not valid on the child site: ") + response['invalid_users'];
				}
				if (detail !== '') {
					detailedEl.css('color', 'red');
					detailedEl.html(detail).show();
				}
			}
		} else {
			statusEl.html(__('Undefined Error')).show();
			statusEl.css('color', 'red');
		}
	}, 'json');
};

mainwp_wfc_save_site_settings_reload = function (site_id) {
	var data = {
		action: 'mainwp_wfc_save_settings_reload',
		siteId: site_id
	};
	var reload = jQuery('#mwp_wfc_license_body');
	reload.html('<i class="fa fa-spinner fa-pulse"></i> ' + __('Reloading ...'));
	jQuery.post(ajaxurl, data, function (response) {
		reload.html(response);
	})
}

mainwp_wfc_bulk_performance_setup_start_next = function (what) {
	if (wfc_bulkTotalThreads == 0) {
		wfc_bulkTotalThreads = jQuery('.itemToProcess[status="queue"]').length;
	}
	while ((itemProcess = jQuery('.itemToProcess[status="queue"]:first')) && (itemProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		switch (what) {
			case 'save_caching_type':
				mainwp_wfc_bulk_savecachingtype_start_specific(itemProcess, what);
				break;
			case 'save_cache_options':
				mainwp_wfc_bulk_savecacheoptions_start_specific(itemProcess, what);
				break;
			case 'clear_page_cache':
				mainwp_wfc_bulk_clearpagecache_start_specific(itemProcess, what);
				break;
			case 'get_cache_stats':
				mainwp_wfc_bulk_getcachestats_start_specific(itemProcess, what);
				break;
			case 'add_cache_exclusion':
				mainwp_wfc_bulk_addcacheexclusion_start_specific(itemProcess, what);
				break;
			case 'remove_cache_exclusion':
				mainwp_wfc_bulk_removecacheexclusion_start_specific(itemProcess, what);
				break;
		}
	}
	if (wfc_bulkFinishedThreads == wfc_bulkTotalThreads && wfc_bulkFinishedThreads != 0) {
		window.location.replace('admin.php?page=Extensions-Mainwp-Wordfence-Extension');
	}
}

mainwp_wfc_bulk_savecachingtype_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_saveCacheConfig',
		site_id: pItemProcess.attr('siteid'),
		individual: 0,
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="red times icon"></i>').show();
			} else {
				if (response.ok) {
					if (response.heading)
						statusEl.html('<i class="red times icon"></i>').show();
					else
						statusEl.html('<i class="green check icon"></i>').show();
				} else if (response.error) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else if (response.errorMsg) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else {
					statusEl.html('<i class="red times icon"></i>').show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_performance_setup_start_next(pWhat);
	}, 'json');
};


mainwp_wfc_bulk_savecacheoptions_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status')
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_saveCacheOptions',
		site_id: pItemProcess.attr('siteid'),
		individual: 0,
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="red times icon"></i>').show();
			} else {
				if (response.ok) {
					statusEl.html('<i class="green check icon"></i>').show();
				} else if (response.error) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else if (response.errorMsg) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else {
					statusEl.html('<i class="red times icon"></i>').show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_performance_setup_start_next(pWhat);
	}, 'json');
};


mainwp_wfc_bulk_clearpagecache_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_clearPageCache',
		site_id: pItemProcess.attr('siteid'),
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
			} else {
				if (response.ok) {
					var msg = '';
					if (response.heading) {
						msg = response.heading + '. ';
					}
					if (response.body) {
						msg += response.body;
					}
					if (msg == '')
						msg = '<i class="green check icon"></i>';
					statusEl.html(msg).show();
				} else if (response.error) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else if (response.errorMsg) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else {
					statusEl.html('<i class="red times icon"></i>').show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_performance_setup_start_next(pWhat);
	}, 'json');
};

mainwp_wfc_bulk_getcachestats_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_getCacheStats',
		site_id: pItemProcess.attr('siteid'),
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
			} else {
				if (response.ok) {
					var msg = '';
					if (response.heading) {
						msg = response.heading + '. ';
					}
					if (response.body) {
						msg += response.body;
					}
					if (msg == '')
						msg = '<i class="green check icon"></i>';
					statusEl.html(msg).show();
				} else if (response.error) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else if (response.errorMsg) {
					statusEl.html('<i class="red times icon"></i>').show();
				} else {
					statusEl.html('<i class="red times icon"></i>').show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_performance_setup_start_next(pWhat);
	}, 'json');
};

mainwp_wfc_bulk_addcacheexclusion_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_addCacheExclusion',
		site_id: pItemProcess.attr('siteid'),
		id: jQuery('#mainwp_wfc_bulk_cache_exclusion_id').attr('value'),
		individual: 0,
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
				statusEl.css('color', 'red');
			} else {
				if (response.ok) {
					statusEl.html('<i class="green check icon"></i>').show();
				} else if (response.error) {
					statusEl.html(response.error).show();
					statusEl.css('color', 'red');
				} else if (response.errorMsg) {
					statusEl.html(response.errorMsg).show();
					statusEl.css('color', 'red');
				} else {
					statusEl.html('An unknown error').show();
					statusEl.css('color', 'red');
				}
			}
		} else {
			statusEl.html(__('Undefined Error')).show();
			statusEl.css('color', 'red');
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_performance_setup_start_next(pWhat);
	}, 'json');
};

mainwp_wfc_bulk_removecacheexclusion_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_removeCacheExclusion',
		site_id: pItemProcess.attr('siteid'),
		id: jQuery('#mainwp_wfc_bulk_cache_exclusion_id').attr('value'),
		individual: 0,
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
			} else {
				if (response.ok) {
					statusEl.html('<i class="green check icon"></i>').show();
				} else if (response.error) {
					statusEl.html(response.error).show();
				} else if (response.errorMsg) {
					statusEl.html(response.errorMsg).show();
				} else {
					statusEl.html('An unknown error').show();
				}
			}
		} else {
			statusEl.html(__('Undefined Error')).show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_performance_setup_start_next(pWhat);
	}, 'json');
};

jQuery(document).on('change', '#mainwp_wfc_diagnostic_info', function () {
	var siteId = jQuery(this).val();
	jQuery('#mwp_wfc_other_test_box').hide();
	if (siteId == '-1') {
		jQuery('#mainwp_diagnostics_child_resp').hide();
		return;
	}
	jQuery('#mainwp_wfc_diagnostics_child_loading').show();
	jQuery('#mainwp_diagnostics_child_resp').hide();
	jQuery('#mwp_wfc_other_test_box').hide();
	MWP_WFAD.getDiagnostics(siteId);
});

mainwp_wfc_bulk_diagnostics_start_next = function (what) {
	if (wfc_bulkTotalThreads == 0) {
		wfc_bulkTotalThreads = jQuery('.itemToProcess[status="queue"]').length;
	}
	while ((itemProcess = jQuery('.itemToProcess[status="queue"]:first')) && (itemProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		switch (what) {
			case 'waf_update_rules':
				mainwp_wfc_bulk_wafupdaterules_start_specific(itemProcess, what);
				break;
			case 'save_debugging_options':
				mainwp_wfc_bulk_savedebuggingoptions_start_specific(itemProcess, what);
				break;
		}
	}
	if (wfc_bulkFinishedThreads == wfc_bulkTotalThreads && wfc_bulkFinishedThreads != 0) {
		window.location.replace('admin.php?page=Extensions-Mainwp-Wordfence-Extension');
	}
}

mainwp_wfc_bulk_wafupdaterules_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_updateWAFRules',
		site_id: pItemProcess.attr('siteid'),
		individual: 0,
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
			} else {
				if (response.ok) {
					if (!response.isPaid)
						statusEl.html('<i class="green check icon"></i>').show();
					else
						statusEl.html('<i class="green check icon"></i>').show();
				} else if (response.error) {
					statusEl.html(response.error).show();
				} else {
					statusEl.html('<i class="red times icon"></i>').show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_diagnostics_start_next(pWhat);
	}, 'json');
};

mainwp_wfc_bulk_savedebuggingoptions_start_specific = function (pItemProcess, pWhat) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_saveDebuggingSettingsToSite',
		site_id: pItemProcess.attr('siteid'),
		individual: 0,
		nonce: mainwp_WordfenceAdminVars.firstNonce
	};

	loaderEl.show();

	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (response) {
			if (response['result'] == 'OVERRIDED') {
				statusEl.html('<i class="yellow exclamation triangle icon"></i>').show();
			} else {
				if (response.ok) {
					statusEl.html('<i class="green check icon"></i>').show();
				} else if (response.error) {
					statusEl.html(response.error).show();
				} else {
					statusEl.html('<i class="red times icon"></i>').show();
				}
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_bulk_diagnostics_start_next(pWhat);
	}, 'json');
};


mainwp_wfc_save_firewall_start_next = function () {
	if (wfc_bulkTotalThreads == 0) {
		wfc_bulkTotalThreads = jQuery('.itemToProcess[status="queue"]').length;
	}
	while ((itemProcess = jQuery('.itemToProcess[status="queue"]:first')) && (itemProcess.length > 0) && (wfc_bulkCurrentThreads < wfc_bulkMaxThreads)) {
		mainwp_wfc_save_firewall_start_specific(itemProcess);
	}
	if (wfc_bulkFinishedThreads == wfc_bulkTotalThreads && wfc_bulkFinishedThreads != 0) {
		window.location.replace('admin.php?page=Extensions-Mainwp-Wordfence-Extension');
	}
};


mainwp_wfc_save_firewall_start_specific = function (pItemProcess) {
	wfc_bulkCurrentThreads++;
	pItemProcess.attr('status', 'progress');
	var statusEl = pItemProcess.find('.status').html('');
	var loaderEl = pItemProcess.find('.loading');

	var data = {
		action: 'mainwp_wfc_save_firewall_settings',
		siteId: pItemProcess.attr('siteid'),
		nonce: mainwp_WordfenceAdminVars.nonce
	};
	loaderEl.show();
	jQuery.post(ajaxurl, data, function (res) {
		loaderEl.hide();
		pItemProcess.attr('status', 'done');
		if (res) {
			if (res.success) {
				statusEl.html('<i class="green check icon"></i>').show();
			} else if (res.error) {
				statusEl.html('<i class="red times icon"></i>').show();
			} else if (res.errorMsg) {
				statusEl.html('<i class="red times icon"></i>').show();
			} else {
				statusEl.html('<i class="red times icon"></i>').show();
			}
		} else {
			statusEl.html('<i class="red times icon"></i>').show();
		}
		wfc_bulkCurrentThreads--;
		wfc_bulkFinishedThreads++;
		mainwp_wfc_save_firewall_start_next();
	}, 'json');
};
