jQuery(document).ready(function () {
	jQuery('#infobox-uptime').insertBefore('#mainwp-tabs');
	jQuery('#errorbox-uptime').insertBefore('#mainwp-tabs');

	jQuery(document).on('click', '.mainwp_aum_auth_links', function () {
		jQuery(this).addClass('disabled');

		var data = {
			action: 'mainwp_advanced_uptime_go_auth',
			scope: jQuery(this).attr('auth-scope'),
			wp_nonce: jQuery('input[name=nonce_auth]').val()
		};
		var authlink = jQuery(this).attr('go-link');
		jQuery.ajax({
			url: ajaxurl,
			type: "POST",
			data: data,
			error: function () {
			},
			success: function (response) {
				if (response && response.result == 'ok') {
					window.location = authlink;
				}
			},
			timeout: 20000
		});
	});
})

mainwp_aum_js_nodeping_region_onchange = function (me) {

	var data = {
		action: 'mainwp_advanced_uptime_info_location',
		region: jQuery(me).val(),
		wp_nonce: jQuery('input[name=wp_js_nonce]').val()
	};

	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: data,
		error: function () {
			jQuery('#aum_nodeping_select_location').html('');
		},
		success: function (response) {
			jQuery('#aum_nodeping_select_location').html(response);
		},
		timeout: 20000
	});
}

mainwp_aum_js_nodeping_type_onchange = function (me) {
	var valType = jQuery(me).val();
	if (valType == 'AGENT' || valType == 'DNS' || valType == 'PUSH' || valType == 'SPEC10DNS' || valType == 'SPEC10RDDS') {
		jQuery('#aum_nodeping_edit_url_address').hide();
	} else {
		jQuery('#aum_nodeping_edit_url_address').show();
	}
}

mainwp_aum_js_betteruptime_endpoint_type_onchange = function (me) {
	var valType = jQuery(me).val();
	if ('keyword' == valType || 'keyword_absence' == valType || 'tcp' == valType || 'udp' == valType || 'dns' == valType) {
		jQuery('#mainwp_aum_js_betteruptime_showhide_required_keyword').show();
	} else {
		jQuery('#mainwp_aum_js_betteruptime_showhide_required_keyword').hide();
	}

	if ('dns' == valType) {
		jQuery('#mainwp_aum_js_betteruptime_showhide_required_keyword').find('span.title').text('Keyword to find in the DNS response');
	} else {
		jQuery('#mainwp_aum_js_betteruptime_showhide_required_keyword').find('span.title').text('Keyword to find in page');
	}

	if ('tcp' == valType || 'udp' == valType || 'smtp' == valType || 'pop' == valType || 'imap' == valType) {
		jQuery('#mainwp_aum_js_betteruptime_showhide_port').show();
	} else {
		jQuery('#mainwp_aum_js_betteruptime_showhide_port').hide();
	}

	if ('ping' == valType || 'tcp' == valType || 'udp' == valType || 'smtp' == valType || 'pop' == valType || 'imap' == valType || 'dns' == valType ) {
		jQuery('.mainwp_aum_js_betteruptime_hide_response').hide();
		jQuery('.mainwp_aum_js_betteruptime_show_response').show();		
	} else {
		jQuery('.mainwp_aum_js_betteruptime_hide_response').show();
		jQuery('.mainwp_aum_js_betteruptime_show_response').hide();
	}

	if ('tcp' == valType) {
		jQuery('#mainwp_aum_js_betteruptime_showhide_port').find('span.title').text('TCP Ports');
		jQuery('#mainwp_aum_js_betteruptime_showhide_request_body').find('span.title').text('Send data to port');
	} else if('udp' == valType)	{
		jQuery('#mainwp_aum_js_betteruptime_showhide_port').find('span.title').text('UDP Ports');
		jQuery('#mainwp_aum_js_betteruptime_showhide_request_body').find('span.title').text('Send data to port');
	} else if('smtp' == valType)	{
		jQuery('#mainwp_aum_js_betteruptime_showhide_port').find('span.title').text('SMTP Ports');
	} else if('pop' == valType)	{
		jQuery('#mainwp_aum_js_betteruptime_showhide_port').find('span.title').text('POP3 Ports');
	} else if('imap' == valType)	{
		jQuery('#mainwp_aum_js_betteruptime_showhide_port').find('span.title').text('IMAP Ports');
	}

	if ('dns' == valType) {
		jQuery('#mainwp_aum_js_betteruptime_showhide_request_body').show();
		jQuery('#mainwp_aum_js_betteruptime_showhide_request_body').find('span.title').text('Domain to query the DNS server with');		
	} else {
		jQuery('#mainwp_aum_js_betteruptime_showhide_request_body').hide();
	}
}

mainwp_aum_js_apply_check = function (me, event) {

	var action = jQuery('#aum_monitor_action select[name=monitor_action]').val();
	var number_checked = -1;

	number_checked = jQuery('#mainwp-aum-monitors-table input[name=checkbox_url]:checkbox:checked').length;

	if (number_checked < 1) {
		alert('Please select at least one monitor!');
		return;
	} else {
		switch (action) {
			case 'display':
			case 'hidden':
				jQuery('#mainwp-aum-monitors-table input[name=checkbox_url]:checked').each(function () {
					click_link = jQuery(this).closest('tr').find('span.url_showhide_link');
					mainwp_aum_js_showhide_monitor_in_widget(click_link, action);
				});
				break;
			case 'delete':
				if (!confirm('Are you sure to delele selected monitors?')) {
					break;
				}
				jQuery('#mainwp-aum-monitors-table input[name=checkbox_url]:checked').each(function () {
					click_link = jQuery(this).closest('tr').find('span.aum-delete-link');
					mainwp_aum_js_delete_monitor_button(click_link);
				});
				break;
			case 'pause':
				jQuery('#mainwp-aum-monitors-table input[name=checkbox_url]:checked').each(function () {
					url_row_obj = jQuery(this).closest('tr');
					if (url_row_obj.find('.aum-action-link').hasClass('pause')) {
						url_row_obj.find('span.loading_status').show();
						url_row_obj.find('.pause').click();
					}
					jQuery(this).removeAttr('checked');
				});
				break;
			case 'start':
				jQuery('#mainwp-aum-monitors-table input[name=checkbox_url]:checked').each(function (event) {
					url_row_obj = jQuery(this).closest('tr');
					if (url_row_obj.find('.aum-action-link').hasClass('start')) {
						url_row_obj.find('span.loading_status').show();
						url_row_obj.find('.start').click();
					}
					jQuery(this).removeAttr('checked');
				});
				break;
			default:
				break;
		}
		jQuery('#mainwp-aum-monitors-table input[name=checkall]').removeAttr('checked');
	}
}

function mainwp_aum_uptimerobot_popup(action, item_id) {

	jQuery('#mainwp-create-edit-monitor-modal').modal({
		closable: true,
		onHide: function () {
			location.href = 'admin.php?page=Extensions-Advanced-Uptime-Monitor-Extension';
		}
	}).modal('show');

	var data = {
		action: 'mainwp_advanced_uptime_' + action
	};

	if (typeof item_id === "undefined") {
		item_id = 0;
	}

	var ser_name = jQuery('#mainwp-aum-form-field-service').val();
	var title = '';

	if (ser_name == 'site24x7') {
		if (item_id) {
			data['url_id'] = item_id;
			title = 'Edit Website Monitor';
		} else {
			title = 'Add a Website Monitor';
		}
	} else if (ser_name == 'nodeping') {
		if (item_id) {
			data['url_id'] = item_id;
			title = 'Edit Check';
		} else {
			title = 'Add a New Check';
		}
	} else if (ser_name == 'betteruptime') {
		if (item_id) {
			data['url_id'] = item_id;
			title = 'Edit Monitor';
		} else {
			title = 'Add a New Monitor';
		}
	} else {
		if ('edit_monitor' == action) {
			title = 'Create Monitor';
		} else if ('update_url' == action) {
			title = 'Edit Monitor';
		}

		if (action == 'statistics_table' || action == 'update_url')
			data['url_id'] = item_id;
		else
			data['monitor_id'] = item_id;

		if (action == 'statistics_table') {
			if (typeof (pPage) != 'undefined') {
				data['stats_page'] = pPage;
			}
		}
	}

	if (action == 'auto_add_sites') {
		title = 'Creating monitors...';
	} else if (action == 'statistics_table') {
		title = 'Monitor Statistics And Reports';
	}

	data['wp_nonce'] = jQuery('input[name=wp_js_nonce]').val();
	data['service'] = ser_name;

	jQuery('#mainwp-create-edit-monitor-modal').find('.header').html(title);
	jQuery('#mainwp-create-edit-monitor-modal').find('.content').html('loading...');


	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: data,
		error: function () {
			jQuery('#mainwp-create-edit-monitor-modal').find('.content').html('error...');
		},
		success: function (response) {
			jQuery('#mainwp-create-edit-monitor-modal').find('.content').html(response);
		},
		timeout: 20000
	});
}

function show_loading(event) {
	jQuery('body').append('<i class="fa fa-spinner fa-pulse aumloading"></i>');
	jQuery('.aumloading').css('left', (event.pageX + 17) + 'px');
	jQuery('.aumloading').css('top', (event.pageY + 27) + 'px');
}
function hide_loading() {
	jQuery('.aumloading').remove();
}

mainwp_aum_on_add_monitor = function () {
	mainwp_aum_uptimerobot_popup('edit_monitor');
}

mainwp_aum_js_delete_monitor_button = function (me) {
	var parent_row = me.closest('tr');
	var url_id = parent_row.attr('url_id');

	parent_row.html('<td colspan="8"><i class="notched circle loading icon"></i> Please wait...</td>');

	jQuery.post(ajaxurl, {
		action: 'mainwp_advanced_uptime_delete_url',
		'url_id': url_id,
		service: jQuery('#mainwp-aum-form-field-service').val(),
		'wp_nonce': jQuery('input[name=wp_js_nonce]').val(),
	}, function (response) {
		if (response == 'success') {
			parent_row.remove();
		} else {
			parent_row.remove();
		}
	});
}
mainwp_aum_js_edit_monitor_button = function (url_id) {
	mainwp_aum_uptimerobot_popup('update_url', url_id);
}

mainwp_aum_site24x7_edit_monitor_button = function (url_id) {
	mainwp_aum_uptimerobot_popup('edit_monitor', url_id);
}

mainwp_aum_bettermonitor_edit_monitor_button = function (url_id) {
	mainwp_aum_uptimerobot_popup('edit_monitor', url_id);
}

mainwp_aum_js_status_monitor_button = function (me, url_id, event, nonce) {
	var current_status = jQuery(me).hasClass('start') ? 'start' : 'pause';
	var status_link_obj = jQuery(me);
	var service = jQuery('#mainwp-aum-form-field-service').val();
	var data = {
		action: 'mainwp_advanced_uptime_url_' + (jQuery(me).hasClass('start') ? 'start' : 'pause'),
		url_id: url_id,
		service: service,
		wp_nonce: nonce
	};
	show_loading(event);
	jQuery(me).closest('tr').find('span.loading_status').show();
	jQuery.post(ajaxurl, data, function (response) {
		jQuery(me).closest('tr').find('span.loading_status').hide();
		hide_loading();
		if (response == 'success') {
			if (current_status == 'start') {
				status_link_obj.removeClass('start').addClass('pause');
				status_link_obj.html('Pause');
				// to fix
				var last_stat = status_link_obj.closest('tr').find('.last_event').attr('last_event');
				if (last_stat == 'paused')
					last_stat = 'up';

				status_link_obj.closest('tr').find('.aum-monitor-status').removeClass('paused').addClass(last_stat);
			} else {
				status_link_obj.removeClass('pause').addClass('start');
				status_link_obj.html('Start');
				status_link_obj.closest('tr').find('.aum-monitor-status').removeClass('down').removeClass('up').removeClass('seems_down').removeClass('not_checked').addClass('paused');
			}

			if ('nodeping' == service) {
				if ('pause' == current_status) {
					jQuery(me).closest('tr').find('td.nodeping-result').html('DISABLED');
				} else {
					jQuery(me).closest('tr').find('td.nodeping-result').html('PASS');
				}
			} else if ('betteruptime' == service) {
				if ('pause' == current_status) {
					jQuery(me).closest('tr').find('td.status-result').html('Paused');
				} else {
					jQuery(me).closest('tr').find('td.status-result').html('Up');
				}
			}
		}
		jQuery(me).closest('tr').find('span.loading_status').hide();
	});
}

mainwp_aum_js_showhide_monitor_in_widget = function (me, action) {
	var url_row_obj = me.closest('tr');
	url_row_obj.find('span.loading_status').show();
	var show = 1;
	var icon = '<i class="fa fa-eye mainwp-green" title="Monitor is displayed in Widget" aria-hidden="true"></i>';
	if (action == 'hidden') {
		show = 0;
		icon = '<i class="fa fa-eye-slash" title="Monitor is hidden in Widget" aria-hidden="true"></i>';
	}
	jQuery.post(
		ajaxurl,
		{
			action: 'mainwp_advanced_uptime_display_dashboard',
			url_id: url_row_obj.find('.url_actions').attr('url_id'),
			dashboard: show,
			wp_nonce: jQuery("#mainwp_aum_extension_display_dashboard_nonce").val()
		},
		function (response, status) {
			me.attr('show-hide', show);
			url_row_obj.find('input[name=checkbox_url]').removeAttr('checked'); // for bulk actions
			url_row_obj.find('span.loading_status').hide();
			url_row_obj.find('span.monitor_status').html(icon);
		});
}

mainwp_aum_js_stats_monitor_button = function (url_id) {
	mainwp_aum_uptimerobot_popup('statistics_table', url_id);
}

mainwp_aum_reload_uptime_monitors = function ( offset, service) {
	if ( service != 'uptimerobot' && service != 'betteruptime' ) {
		return;
	}
	jQuery.ajax({
		url: ajaxurl,
		type: "POST",
		data: {
			action: 'mainwp_advanced_uptime_reload_monitors',
			offset: offset,
			service: service,
			wp_nonce: jQuery("#mainwp_aum_reload_monitors_nonce").val()
		},
		error: function () {
			jQuery('#aum-loader').hide();
			console.log('Request timed out. Please, try again later.');
		},
		success: function (response) {
			if ( 'uptimerobot' == service ) {
				if (response && response.offset > 0) {
					mainwp_aum_reload_uptime_monitors( response.offset, service );
				}
			}
		},
		timeout: 20000,
		dataType: 'json'
	});
};

