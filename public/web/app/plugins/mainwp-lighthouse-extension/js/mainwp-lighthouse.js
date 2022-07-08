/**
	 * After DOM Loaded
	 * Credit: danzubco 
	 * https://meta.mainwp.com/u/danzubco
	 * 
	 */
document.addEventListener('DOMContentLoaded', () => {
	/**
	 * Lighthouse Audit - Automatically scroll to audit results by clicking on the score
	 */
	const lighthousePage = document.querySelector('body.mainwp_page_ManageSitesLighthouse');
	if (lighthousePage) {
		const auditsNavElements = lighthousePage.querySelectorAll('.mainwp-lighthouse-score');
		const offset = -45;
		auditsNavElements.forEach(el => {
			el.addEventListener('click', () => {
				const auditDomain = el.dataset.tab.split('-')[0];
				const activeTab = document.querySelector(`.ui.tab.active[data-tab^=${auditDomain}]`);
				const tabTopPos = activeTab.getBoundingClientRect().top + window.pageYOffset + offset;

				window.scrollTo({
					top: tabTopPos,
					behavior: 'smooth'
				});
			});
		});
	}
});

jQuery(document).ready(function ($) {
	jQuery('#mainwp_lighthouse_use_schedule').change(function () {
		if (this.checked) {
			jQuery('#mainwp-lighthouse-recheck-toggle-area').fadeIn();
		} else {
			jQuery('#mainwp-lighthouse-recheck-toggle-area').fadeOut();
		}
	});

	jQuery('#mainwp_lighthouse_setting_site_override').change(function () {
		if (this.checked) {
			jQuery('.mainwp-lighthouse-site-settings-toggle-area').fadeIn();
		} else {
			jQuery('.mainwp-lighthouse-site-settings-toggle-area').fadeOut();
		}
	});

	// Check all checkboxes
	jQuery('#mainwp-lighthouse-sites-table th input[type="checkbox"]').change(function () {
		var checkboxes = jQuery('#mainwp-lighthouse-sites-table').find(':checkbox');
		if (jQuery(this).prop('checked')) {
			checkboxes.prop('checked', true);
		} else {
			checkboxes.prop('checked', false);
		}
	});

	jQuery('.mainwp-lighthouse-score.label').tab();

	jQuery(document).on('click','.lighthouse-action-recheck', function () {
		var row = jQuery(this).closest('tr');
		if (jQuery(row).hasClass('child')){
			row = jQuery(row).prev();
		}
		var columns = jQuery('#mainwp-lighthouse-sites-table').attr('columns');
		var site_name = row.find('a.mainwp-site-name-link').html();
		var data = {
			action: 'mainwp_lighthouse_perform_check_site',
			websiteId: row.attr('website-id'),
			nonce: mainwpLighthouse.nonce
		}

		row.html('<td></td><td colspan="' + columns + '"><i class="notched circle loading icon"></i> ' + site_name + ' audit in progress. Please wait...</td>');
		jQuery.post(ajaxurl, data, function (response) {
			row.removeClass('queue');
			if (response) {
				if (response['error']) {
					row.html('<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' audit failed. ' + response['error'] + ' Page will reload in 3 seconds.</td>');
				} else if (response['status'] == 'success') {
					row.html('<td></td><td colspan="' + columns + '"><i class="green check icon"></i> ' + site_name + ' audit completed successfully. Page will reload in 3 seconds.</td>');
				} else {
					row.html('<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' audit failed. Page will reload in 3 seconds.</td>');
				}
			} else {
				row.html('<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' audit failed. Page will reload in 3 seconds.</td>');
			}

			setTimeout(function () {
				window.location.reload();
			}, 3000);

		}, 'json');
	})

	// Trigger the bulk actions
	jQuery('#mwp_lighthouse_action_btn').on('click', function () {
		var bulk_act = jQuery('#mwp_lighthouse_bulk_action').dropdown("get value");
		console.log(bulk_act);
		mainwp_lighthouse_table_bulk_action(bulk_act);
	});

	// Close modal and reload page.
	jQuery('#mainwp-lighthouse-sync-modal .ui.reload.cancel.button').on('click', function () {
		window.location.href = 'admin.php?page=Extensions-Mainwp-Lighthouse-Extension&tab=dashboard';
	});

	jQuery('a.mainwp-lighthouse-audits-filter').on('click', function () {
		var strategy = jQuery(this).parents('.strategy.column').attr('strategy');
		var category = jQuery(this).parents('.ui.secondary.segment').next('.audits.accordion').attr('category');
		var type = jQuery(this).attr('type');

		if (type == 'all') {
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status.failed').parents('.title').fadeIn(300);
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status.passed').parents('.title').fadeIn(300);
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status.not-applicable').parents('.title').fadeIn(300);
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status.diagnostics').parents('.title').fadeIn(300);
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status.manual').parents('.title').fadeIn(300);
		} else {
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status.' + type + '').parents('.title').fadeIn(300);
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status:not(.' + type + '').parents('.title').fadeOut(300);
			jQuery('div[strategy="' + strategy + '"] div[category="' + category + '"]').find('span.audit-status:not(.' + type + '').parents('.title').next('.content').fadeOut(300);
		}

		return false;
	});

	// ?
	$('#mainwp-lighthouse-save-individual-settings-button').on('click', function () {
		var data = {
			action: 'mainwp_lighthouse_save_ext_setting',
			scoreNoti: $('select[name="mainwp_lighthouse_score_noti"]').val(),
		}
		var statusEl = $('#mwps-setting-ext-working .status');
		statusEl.html('');
		$('#mwps-setting-ext-working .loading').show();
		jQuery.post(ajaxurl, data, function (response) {
			$('#mwps-setting-ext-working .loading').hide();
			if (response) {
				if (response == 'SUCCESS') {
					statusEl.css('color', '#21759B');
					statusEl.html(__('Updated', 'mainwp-lighthouse-extension')).show();
					statusEl.fadeOut(3000);
				} else {
					statusEl.css('color', 'red');
					statusEl.html(__("Update failed", 'mainwp-lighthouse-extension')).show();
				}
			} else {
				statusEl.css('color', 'red');
				statusEl.html(__("Undefined error", 'mainwp-lighthouse-extension')).show();
			}
		}, 'json');
	});

});

var light_bulkMaxThreads = 4;
var light_bulkTotalThreads = 0;
var light_bulkCurrentThreads = 0;
var light_bulkFinishedThreads = 0;
var lighthouse_audit_errors = 0;

// Manage Bulk Actions
mainwp_lighthouse_table_bulk_action = function (act) {
	var selector = '';
	switch (act) {
		case 'check-pages':
			selector += '#mainwp-lighthouse-sites-table tbody tr';
			jQuery(selector).addClass('queue');
			mainwp_lighthouse_table_check_pages_start_next(selector, true);
			break;
		case 'open-wpadmin':
			jQuery('#mainwp-lighthouse-sites-table .check-column INPUT:checkbox:checked').each(function () {
				var row = jQuery(this).closest('tr');
				var url = row.find('a.open_newwindow_wpadmin').attr('href');
				window.open(url, '_blank');
			});
			break;
		case 'open-frontpage':
			jQuery('#mainwp-lighthouse-sites-table .check-column INPUT:checkbox:checked').each(function () {
				var row = jQuery(this).closest('tr');
				var url = row.find('a.open_site_url').attr('href');
				window.open(url, '_blank');
			});
			break;
	}
}

mainwp_lighthouse_table_check_pages_start_next = function (selector) {
	if (light_bulkTotalThreads == 0) {
		light_bulkTotalThreads = jQuery('#mainwp-lighthouse-sites-table tbody').find('input[type="checkbox"]:checked').length;
	}
	while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (light_bulkCurrentThreads < light_bulkMaxThreads)) {
		objProcess.removeClass('queue');
		if (objProcess.closest('tr').find('input[type="checkbox"]:checked').length == 0) {
			continue;
		}
		mainwp_lighthouse_table_check_pages_start_specific(objProcess, selector);
	}
}

mainwp_lighthouse_table_check_pages_start_specific = function (pObj, selector) {
	var row = pObj.closest('tr');
	var columns = jQuery('#mainwp-lighthouse-sites-table').attr('columns');
	var site_name = row.find('a.mainwp-site-name-link').html();
	var bulk = true;

	if (bulk) {
		light_bulkCurrentThreads++;
	}

	var data = {
		action: 'mainwp_lighthouse_perform_check_site',
		websiteId: row.attr('website-id'),
		nonce: mainwpLighthouse.nonce
	}

	row.html('<td></td><td colspan="' + columns + '"><i class="notched circle loading icon"></i> ' + site_name + ' audit in progress. Please wait...</td>');

	jQuery.post(ajaxurl, data, function (response) {
		pObj.removeClass('queue');
		if (response) {
			if (response['error']) {
				row.html('<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' audit failed. ' + response['error'] + ' Page will reload in 3 seconds.</td>');
			} else if (response['status'] == 'success') {
				row.html('<td></td><td colspan="' + columns + '"><i class="green check icon"></i> ' + site_name + ' audit completed successfully.</td>');
			} else {
				row.html('<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' audit failed. Please try again.</td>');
			}
		} else {
			row.html('<td></td><td colspan="' + columns + '"><i class="times red icon"></i> ' + site_name + ' audit failed. Please try again.</td>');
		}

		if (bulk) {
			light_bulkCurrentThreads--;
			light_bulkFinishedThreads++;

			mainwp_lighthouse_table_check_pages_start_next(selector);
		}

		if (light_bulkTotalThreads == light_bulkFinishedThreads) {
			setTimeout(function () {
				window.location.reload(true);
			}, 3000);
		}

	}, 'json');
	return false;
}

// Loop through sites
mainwp_lighthouse_action_start_next = function (doAction) {
	if (doAction == 'audit_pages') {
		mainwp_lighthouse_popup_check_pages_start_next();
	}
};

mainwp_lighthouse_popup_check_pages_start_next = function () {
	if (light_bulkTotalThreads == 0) {
		light_bulkTotalThreads = jQuery('.mainwpProccessSitesItem[status="queue"]').length;
	}

	while ((siteToProcess = jQuery('.mainwpProccessSitesItem[status="queue"]:first')) && (siteToProcess.length > 0) && (light_bulkCurrentThreads < light_bulkMaxThreads)) {
		mainwp_lighthouse_popup_check_pages_start_specific(siteToProcess);
	}
};

// Process sites to sync data or trigger actions
mainwp_lighthouse_popup_check_pages_start_specific = function (pSiteToProcess) {
	light_bulkCurrentThreads++;
	pSiteToProcess.attr('status', 'progress');

	var statusEl = pSiteToProcess.find('.status').html('<span data-tooltip="Auditing. Please wait..." data-inverted="" data-position="left center"><i class="notched circle loading icon"></i></span>');
	var data = {
		action: 'mainwp_lighthouse_perform_check_site',
		websiteId: pSiteToProcess.attr('siteid'),
		nonce: mainwpLighthouse.nonce
	};

	jQuery.post(ajaxurl, data, function (response) {
		pSiteToProcess.attr('status', 'done');
		if (response) {
			if (response['status'] == 'success') {
				statusEl.html('<span data-tooltip="Auditing completed successfully." data-inverted="" data-position="left center"><i class="green check icon"></i></span>');
			} else if (response['error']) {
				statusEl.html('<span data-tooltip="' + response['error'] + '" data-inverted="" data-position="left center"><i class="red times icon"></i></span>');
				lighthouse_audit_errors++;
			} else {
				statusEl.html('<span data-tooltip="No response from the API server. Please try again." data-inverted="" data-position="left center"><i class="red times icon"></i></span>').show();
				lighthouse_audit_errors++;
			}
		} else {
			statusEl.html('<span data-tooltip="No response from the API server. Please try again." data-inverted="" data-position="left center"><i class="red times icon"></i></span>');
			lighthouse_audit_errors++;
		}

		light_bulkCurrentThreads--;
		light_bulkFinishedThreads++;

		jQuery('#mainwp-lighthouse-sync-modal .mainwp-modal-progress').progress({ value: light_bulkFinishedThreads, total: light_bulkTotalThreads });
		jQuery('#mainwp-lighthouse-sync-modal .mainwp-modal-progress').find('.label').html(light_bulkFinishedThreads + '/' + light_bulkTotalThreads + ' ' + __('Completed'));

		if (light_bulkFinishedThreads == light_bulkTotalThreads) {
			if (lighthouse_audit_errors == 0) {
				jQuery('#mainwp-lighthouse-modal-progress-feedback').addClass('green');
				jQuery('#mainwp-lighthouse-modal-progress-feedback').html('Process completed without errors. Page will reload in 3 seconds.');
				setTimeout(function () {
					window.location.href = 'admin.php?page=Extensions-Mainwp-Lighthouse-Extension&tab=dashboard';
				}, 3000);
			} else {
				jQuery('#mainwp-lighthouse-modal-progress-feedback').addClass('red');
				jQuery('#mainwp-lighthouse-modal-progress-feedback').html('Process completed with errors.');
			}
		}

		mainwp_lighthouse_popup_check_pages_start_next();

	}, 'json');
};
