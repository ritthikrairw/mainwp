backupclickProgress = false;
started_fresh_backup = false;
last_cron_triggered_time_js_wptc = '';
jQuery(document).ready(function ($) {
	if (jQuery('#mainwp_wptc_backups_page').length > 0) {
		mainwp_get_current_backup_status_wptc(); //Get backup status on page reload
	}

	jQuery(document).keyup(function (e) {
		if (e.which == 27) {
			dialog_close_wptc();
		}
	});
	status_area_wptc = "#bp_progress_bar_note, #staging_progress_bar_note";

	jQuery('body').on('click', '.sub_tree_class', function () {
		if (jQuery(this).hasClass('sub_tree_class') == true) {
			if (!jQuery(this).hasClass('selected')) {
				jQuery(this).addClass('selected');
				var this_file_name = jQuery(this).find('.folder').attr('file_name');
				jQuery.each(jQuery(this).nextAll(), function (key, value) {
					jQuery.each(jQuery(value).find('.this_leaf_node'), function (key1, value1) {
						var parent_dir = jQuery(value1).find('.file_path').attr('parent_dir');
						if (jQuery(value1).hasClass('this_leaf_node') == true && parent_dir.indexOf(this_file_name) != -1) {
							jQuery(value1).find('li').addClass('selected');
						}
					});
					jQuery.each(jQuery(value).find('.sub_tree_class'), function (key2, value2) {
						jQuery(value2).addClass('selected');
					});
				});
			} else {
				jQuery(this).removeClass('selected');
				jQuery.each(jQuery(this).nextAll(), function (key, value) {
					jQuery.each(jQuery(value).find('.this_leaf_node'), function (key1, value1) {
						if (jQuery(value1).hasClass('this_leaf_node') == true) {
							jQuery(value1).find('li').removeClass('selected');
						}
					});
					jQuery.each(jQuery(value).find('.sub_tree_class'), function (key2, value2) {
						jQuery(value2).removeClass('selected');
					});
				});
			}
		}
		if (jQuery(this).parents('.bu_files_list_cont').find('.selected').length > 0) {
			jQuery(this).parents('.bu_files_list_cont').parent().find('.this_restore').removeClass('disabled');
		} else {
			jQuery(this).parents('.bu_files_list_cont').parent().find('.this_restore').addClass('disabled');
		}
	});

	jQuery('body').on('click', '.this_leaf_node li', function () {
		if (!jQuery(this).hasClass('selected')) {
			jQuery(this).addClass('selected');
		} else {
			jQuery(this).removeClass('selected');
		}
		if (jQuery(this).parents('.bu_files_list_cont').find('.selected').length > 0) {
			jQuery(this).parents('.bu_files_list_cont').parent().find('.this_restore').removeClass('disabled');
		} else {
			jQuery(this).parents('.bu_files_list_cont').parent().find('.this_restore').addClass('disabled');
		}
	});

	jQuery('body').on('click', '#save_manual_backup_name_wptc', function () {
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		var custom_name = jQuery("#manual_backup_custom_name").val();
		if (!custom_name) {
			jQuery("#manual_backup_custom_name").css('border-color', '#cc0000').focus();
			return false;
		}
		jQuery(this).addClass('disabled').html('Saving...');
		mainwp_save_manual_backup_name_wptc(custom_name);
	});

	jQuery("#report_issue").on("click", function (e) {
		if (jQuery(this).text() == 'Report issue') {
			e.preventDefault();
			e.stopImmediatePropagation();
			issue_repoting_form();
		}
	});

	jQuery("#form_report_close, .close").on("click", function () {
		tb_remove();
	});

	jQuery(".notice-dismiss").on("click", function () {
		jQuery('.notice, #update-nag').remove();
	});
	jQuery(".mainwp_test_cron_wptc").on("click", function () {
		if (jQuery('#mainwp_wptc_current_site_id').val() > 0) {
			mainwp_test_connection_wptc_cron();
		} else {
			mainwp_wptc_general_load_sites('test_communication');
		}

	});

	jQuery('body').on('click', '.dialog_close, .close', function () {
		if (!jQuery(this).hasClass('no_exit_restore_wptc')) {
			dialog_close_wptc();
		}
	});

	jQuery("#show_file_db_exp_for_exc, #exc_files_db_cancel").on("click", function () {
		change_init_setup_button_state();
	});


	jQuery("#select_wptc_backup_slots").on('change', function () {
		show_schedule_time_wptc();
	});

	if (jQuery("#select_wptc_backup_slots").val() === 'daily') {
		jQuery('#select_wptc_default_schedule').show();
	}

	jQuery(document).on("click", "#wptc_save_changes", function () {
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		jQuery('#calculating_file_db_size_temp, #show_final_size').toggle();
		jQuery(this).addClass('disabled').attr('disabled', 'disabled').val('Saving new changes...').html('Saving...');
		jQuery('#exc_files_db_cancel').css('color', '#c4c4c4').bind('click', false);
		mainwp_save_settings_wptc();
		return false;
	});

	jQuery("#disable_vulns_wptc").on("click", function () {
		jQuery("#enable_vulns_options_wptc").hide();
	});

	jQuery("#enable_vulns_wptc").on("click", function () {
		jQuery("#enable_vulns_options_wptc").show();
	});

	jQuery("input[name=wptc_vulns_plugins]:checkbox").change(function () {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_vulns_plugins_dw, #wptc-select-all-plugins-vulns').hide();
		if (jQuery(this).is(':checked')) {
			jQuery('#wptc_vulns_plugins_dw, #wptc-select-all-plugins-vulns').show();
			fancy_tree_init_vulns_plugins_wptc();
		}
	});

	jQuery("input[name=wptc_vulns_themes]:checkbox").change(function () {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_vulns_themes_dw, #wptc-select-all-themes-vulns').hide();
		if (jQuery(this).is(':checked')) {
			jQuery('#wptc_vulns_themes_dw, #wptc-select-all-themes-vulns').show();
			fancy_tree_init_vulns_themes_wptc();
		}
	});

	if (jQuery("input[name=wptc_vulns_plugins]").is(':checked')) {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_vulns_plugins_dw, #wptc-select-all-plugins-vulns').show();
		fancy_tree_init_vulns_plugins_wptc();
	}

	if (jQuery("input[name=wptc_vulns_themes]").is(':checked')) {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_vulns_themes_dw, #wptc-select-all-themes-vulns').show();
		fancy_tree_init_vulns_themes_wptc();
	}

	jQuery("#send_issue_wptc").on("click", function () {
		var issueForm = jQuery("#TB_window form").serializeArray();
		if ((issueForm[0]['value'] == "") && (issueForm[1]['value'] == "")) {
			$("input[name='cemail']").css("box-shadow", "0px 0px 2px #FA1818");
			$("textarea[name='desc']").css("box-shadow", "0px 0px 2px #FA1818");
		} else if (issueForm[0]['value'] == "") {
			$("input[name='cemail']").css("box-shadow", "0px 0px 2px #FA1818");
		} else if (issueForm[1]['value'] == "") {
			$("input[name='cemail']").css("box-shadow", "0px 0px 2px #028202");
			$("textarea[name='desc']").css("box-shadow", "0px 0px 2px #FA1818");
		} else {
			$("input[name='cemail']").css("box-shadow", "0px 0px 2px #028202");
			$("textarea[name='desc']").css("box-shadow", "0px 0px 2px #028202");
			sendWTCIssueReport(issueForm);
		}
	});


	jQuery("input[name=wptc_auto_themes]:checkbox").change(function () {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_auto_update_themes_dw, #wptc-select-all-themes-au').hide();
		if (jQuery(this).is(':checked')) {
			jQuery('#wptc_auto_update_themes_dw, #wptc-select-all-themes-au').show();
			fancy_tree_init_auto_update_themes_wptc();
		}
	});


	if (jQuery("input[name=wptc_auto_themes]").is(':checked')) {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_auto_update_themes_dw, #wptc-select-all-themes-au').show();
		fancy_tree_init_auto_update_themes_wptc();
	}


	jQuery("input[name=wptc_auto_plugins]:checkbox").change(function () {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_auto_update_plugins_dw, #wptc-select-all-plugins-au').hide();
		if (jQuery(this).is(':checked')) {
			jQuery('#wptc_auto_update_plugins_dw, #wptc-select-all-plugins-au').show();
			fancy_tree_init_auto_update_plugins_wptc();
		}
		return false;
	});

	// below the change() event
	if (jQuery("input[name=wptc_auto_plugins]").is(':checked')) {
		if (jQuery('#mainwp_wptc_current_site_id').val() == 0)
			return;
		jQuery('#wptc_auto_update_plugins_dw, #wptc-select-all-plugins-au').show();
		fancy_tree_init_auto_update_plugins_wptc();
	}


	jQuery("body").on("click", "#wptc-select-all-plugins-au, #wptc-select-all-themes-au", function (e) {

		var current_id = jQuery(this).attr('id');

		if (current_id === 'wptc-select-all-plugins-au') {
			var tree = jQuery('#wptc_auto_update_plugins_dw').fancytree('getTree');
		} else {
			var tree = jQuery('#wptc_auto_update_themes_dw').fancytree('getTree');
		}

		if (!jQuery(this).hasClass('fancytree-selected')) {

			jQuery(this).addClass('fancytree-selected');

			if (!jQuery.isFunction(tree.getDeSelectedNodes)) {
				return;
			}

			jQuery.each(tree.getDeSelectedNodes(), function (key, value) {
				value.setSelected(true);
			});


			return;
		}

		jQuery(this).removeClass('fancytree-selected');

		if (!jQuery.isFunction(tree.getSelectedNodes)) {
			return;
		}

		jQuery.each(tree.getSelectedNodes(), function (key, value) {
			value.setSelected(false);
		});

	});


	jQuery("body").on("click", "#wptc-select-all-plugins-vulns", function (e) {

		var tree = jQuery('#wptc_vulns_plugins_dw').fancytree('getTree');

		if (!jQuery(this).hasClass('fancytree-selected')) {

			jQuery(this).addClass('fancytree-selected');

			if (!jQuery.isFunction(tree.getDeSelectedNodes)) {
				return;
			}

			jQuery.each(tree.getDeSelectedNodes(), function (key, value) {
				value.setSelected(true);
			});


			return;
		}

		jQuery(this).removeClass('fancytree-selected');

		if (!jQuery.isFunction(tree.getSelectedNodes)) {
			return;
		}

		jQuery.each(tree.getSelectedNodes(), function (key, value) {
			value.setSelected(false);
		});

	});


	jQuery("body").on("click", "#wptc-select-all-themes-vulns", function (e) {

		var tree = jQuery('#wptc_vulns_themes_dw').fancytree('getTree');

		if (!jQuery(this).hasClass('fancytree-selected')) {

			jQuery(this).addClass('fancytree-selected');

			if (!jQuery.isFunction(tree.getDeSelectedNodes)) {
				return;
			}

			jQuery.each(tree.getDeSelectedNodes(), function (key, value) {
				value.setSelected(true);
			});


			return;
		}

		jQuery(this).removeClass('fancytree-selected');

		if (!jQuery.isFunction(tree.getSelectedNodes)) {
			return;
		}

		jQuery.each(tree.getSelectedNodes(), function (key, value) {
			value.setSelected(false);
		});

	});

	jQuery('#wptc_auto_update_schedule_enabled').change(function () {
		if (jQuery(this).is(":checked")) {
			jQuery('#wptc_auto_update_schedule_time').show();
		} else {
			jQuery('#wptc_auto_update_schedule_time').hide();
		}
	});

	jQuery("#cancel_issue").on("click", function () {
		tb_remove();
	});


	jQuery("#refresh-c-status-area-wtpc").on("click", function () {
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		mainwp_get_current_backup_status_wptc();
	});

	jQuery(".report_issue_wptc").on('click', function (e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		jQuery('.notice, #update-nag').remove();
		var log_id = $(this).attr('id');
		if (log_id != "" && log_id != 'undefined') {
			e.preventDefault();
			e.stopImmediatePropagation();

			var log_id = $(this).attr('id');
			swal({
				title: mainwp_wptc_get_dialog_header('Add description'),
				input: 'textarea',
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Submit',
				width: 400,
				showLoaderOnConfirm: true,
				preConfirm: function (description) {
					return new Promise(function (resolve, reject) {
						if (description) {
							return mainwp_send_report_issue_wptc(description, log_id);
						} else {
							return mainwp_send_report_issue_wptc('', log_id);
						}
					})
				},
				allowOutsideClick: true
			});

		}
	});

	jQuery("#mwp_wptc_clear_log").on('click', function () {

		swal({
			title: mainwp_wptc_get_dialog_header('Are you sure?'),
			html: mainwp_wptc_get_dialog_body('Are you sure you want to permanently delete these logs?', ''),
			padding: '0px 0px 10px 0',
			buttonsStyling: false,
			showCancelButton: true,
			confirmButtonColor: '',
			cancelButtonColor: '',
			confirmButtonClass: 'button-primary wtpc-button-primary',
			cancelButtonClass: 'button-secondary wtpc-button-secondary',
			confirmButtonText: 'Delete',
			cancelButtonText: 'Cancel',
		}).then(function () {
			mainwp_yes_delete_logs_wptc();
		}, function (dismiss) {

		}
		);
	});


	jQuery("#toggle_exlclude_files_n_folders, #toggle_exlclude_files_n_folders_staging, #wptc_init_toggle_files").on("click", function (e) {
		e.stopImmediatePropagation();
		e.preventDefault();

		var id = '#wptc_exc_files';
		var category = 'backup';
		var init = false;

		switch (this.id) {
			case 'toggle_exlclude_files_n_folders_staging':
				id = '#wptc_exc_files_staging';
				category = 'staging';
				break;
			case 'wptc_init_toggle_files':
				init = true;
				break;
		}

		jQuery(id).toggle();

		if (jQuery(id).css('display') === 'block') {
			fancy_tree_init_exc_files_wptc(init, id, category);
		}

		return false;
	});

	//	jQuery("#toggle_exlclude_files_n_folders").on("click", function(e){
	//
	//		e.stopImmediatePropagation();
	//		e.preventDefault();
	//		jQuery("#wptc_exc_files").toggle();
	//		if (jQuery("#wptc_exc_files").css('display') === 'block') {
	//			fancy_tree_init_exc_files_wptc();
	//		}
	//		return false;
	//	});

	//	jQuery("#wptc_init_toggle_files").on("click", function(e){
	//		e.stopImmediatePropagation();
	//		e.preventDefault();
	//		if (jQuery("#wptc_exc_files").css('display') === 'block') {
	//			return false;
	//		}
	//		jQuery("#wptc_exc_files").toggle();
	//		if (jQuery("#wptc_exc_files").css('display') === 'block') {
	//			if (typeof wptc_file_size_in_bytes != 'undefined') {
	//				jQuery("#included_file_size").html(convert_bytes_to_hr_format(wptc_file_size_in_bytes));
	//				jQuery("#file_size_in_bytes").html(wptc_file_size_in_bytes);
	//			}
	//			fancy_tree_init_exc_files_wptc(1);
	//
	//		}
	//		return false;
	//	});


	jQuery("#toggle_wptc_db_tables, #toggle_wptc_db_tables_staging, #wptc_init_toggle_tables").on("click", function (e) {
		e.stopImmediatePropagation();
		e.preventDefault();

		var id = '#wptc_exc_db_files';
		var category = 'backup';
		var init = false;

		switch (this.id) {
			case 'toggle_wptc_db_tables_staging':
				id = '#wptc_exc_db_files_staging';
				category = 'staging';
				break;
			case 'wptc_init_toggle_tables':
				init = true;
				break;
		}

		jQuery(id).toggle();

		if (jQuery(id).css('display') === 'block') {
			fancy_tree_init_exc_tables_wptc(false, id, category);
		}

		return false;
	});

	//	jQuery("#toggle_wptc_db_tables, #toggle_wptc_db_tables_staging").on("click", function(e){
	//		e.stopImmediatePropagation();
	//		e.preventDefault();
	//		jQuery("#wptc_exc_db_files").toggle();
	//		if (jQuery("#wptc_exc_db_files").css('display') === 'block') {
	//			fancy_tree_init_exc_tables_wptc();
	//		}
	//		return false;
	//	});

	//	jQuery("#wptc_init_toggle_tables").on("click", function(e){
	//		e.stopImmediatePropagation();
	//		e.preventDefault();
	//		if (jQuery("#wptc_exc_db_files").css('display') === 'block') {
	//			return false;
	//		}
	//		jQuery("#wptc_exc_db_files").toggle();
	//		if (jQuery("#wptc_exc_db_files").css('display') === 'block') {
	//			fancy_tree_init_exc_tables_wptc(1);
	//		}
	//		return false;
	//	});

	jQuery('.resume_backup_wptc').on("click", function () {
		//resume_backup_wptc();
	});



	jQuery("#wptc_sync_purchase").click(function (e) {
		e.stopImmediatePropagation();
		e.preventDefault();

		if (jQuery(this).hasClass('disabled')) {
			return;
		}

		console.log(jQuery('#mainwp_wptc_current_site_id').val());

		if (jQuery('#mainwp_wptc_current_site_id').val() == 0) // general sync
		{
			mainwp_wptc_general_load_sites('sync_purchase');
			return;
		}
		jQuery('#wptc_sync_purchase').val('Syncing Purchase').addClass('disabled');

		jQuery.post(ajaxurl, {
			action: 'mainwp_wptc_sync_purchase',
			timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
			nonce: mainwp_timecapsule_loc.nonce
		}, function (response) {

			jQuery('#wptc_sync_purchase').val('Sync Purchase').removeClass('disabled');

			swal({
				title: mainwp_wptc_get_dialog_header('Success'),
				html: mainwp_wptc_get_dialog_body('Purchase data synced successfully!', 'success'),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				showCancelButton: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Reload',
			}).then(function () {
				location.reload();
			});

			//add_suggested_files_lists_wptc(response.files);

		});
	});


	jQuery('.close-image-wptc').on("click", function () {
		jQuery(this).remove();
	});

	jQuery('#mainwp_wptc_connect_to_cloud').on("click", function () {
		//jQuery(this).attr('disabled', 'disabled').addClass('disabled').val('Redirecting...');
		jQuery('.cloud_error_mesg').removeClass('cloud_acc_connection_error').html('').hide();
		var cloud_type_wptc = $(this).attr("cloud_type");
		var auth_url_func = '';
		var data = {};


		if (cloud_type_wptc == 's3') {
			data['as3_access_key'] = jQuery('#as3_access_key').val();
			data['as3_secure_key'] = jQuery('#as3_secure_key').val();
			data['as3_bucket_region'] = jQuery('#as3_bucket_region').val();
			data['as3_bucket_name'] = jQuery('#as3_bucket_name').val();
			auth_url_func = 'mainwp_get_s3_authorize_url_wptc';
			cloud_type = 'Amazon S3';
		}

		if (auth_url_func != '') {
			jQuery.post(ajaxurl, {
				action: auth_url_func,
				credsData: data
			}, function (data) {
				try {
					var obj = jQuery.parseJSON(data);
				} catch (e) {
					// console.log(data);
					jQuery('.cloud_error_mesg').addClass('cloud_acc_connection_error').html(data).show();
					jQuery('#mainwp_wptc_connect_to_cloud').removeClass('disabled').removeAttr("disabled").val('Connect to ' + cloud_type);
					return false;
				}
				if (typeof obj.error != 'undefined') {
					return false;
				}
				parent.location.assign(obj.authorize_url);
			});
		}
	});

	$('#mainwp_wptc_override_global_setting').on('change', function () {
		var statusEl = $(this).closest('.segment').find('.status');
		statusEl.html('<i class="notched circle loading icon"></i>').show();
		var data = {
			action: 'mainwp_wptc_override_general_settings',
			timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
			override: $(this).is(':checked') ? 1 : 0,
			nonce: mainwp_timecapsule_loc.nonce
		}
		jQuery.post(ajaxurl, data, function (response) {
			if (response) {
				if (response['error']) {
					statusEl.html('<i class="red times icon"></i> ' + response['error']);
				} else if (response.result == 'ok') {
					statusEl.html('<i class="green check icon"></i> ' + __('Saved'));
					setTimeout(function () {
						statusEl.fadeOut();
					}, 3000);
				} else {
					statusEl.html('<i class="red times icon"></i> ' + __("Undefined error"));
				}
			} else {
				statusEl.html('<i class="red times icon"></i> ' + __("Undefined error"));
			}
		}, 'json');
		return false;
	});


	jQuery("#wptc_analyze_inc_exc_lists").click(function (e) {
		e.stopImmediatePropagation();
		e.preventDefault();
		mainwp_wptc_analyze_inc_exc_lists();
	});

	jQuery('#user_excluded_files_more_than_size_status').change(function () {
		if (jQuery(this).is(":checked")) {
			jQuery('#user_excluded_files_more_than_size_div').show();
		} else {
			jQuery('#user_excluded_files_more_than_size_div').hide();
		}
	});


	jQuery('input[name="enable_admin_login_wptc"]').on("click", function (e) {
		if (jQuery('#mainwp_wptc_current_site_id').val() != 0) { // individual settings
			if (jQuery(this).val() === 'yes') {
				jQuery('#login_custom_link').show();
			} else {
				jQuery('#login_custom_link').hide();
			}
		}
	});

});

function fancy_tree_init_auto_update_plugins_wptc() {
	jQuery("#wptc_auto_update_plugins_dw").fancytree({
		checkbox: true,
		selectMode: 2,
		icon: true,
		debugLevel: 0,
		source: {
			url: ajaxurl,
			data: {
				"action": "mainwp_get_installed_plugins_wptc",
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
			});
		},
		select: function (event, data) {
			// Get a list of all selected nodes, and convert to a key array:
			var selKeys = jQuery.map(data.tree.getSelectedNodes(), function (node) {
				return node.key;
			});
			jQuery("#auto_include_plugins_wptc").val(selKeys.join(","));
		},
		dblclick: function (event, data) {
			data.node.toggleSelected();
		},
		keydown: function (event, data) {
			if (event.which === 32) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	});
}


function fancy_tree_init_auto_update_themes_wptc() {
	jQuery("#wptc_auto_update_themes_dw").fancytree({
		checkbox: true,
		selectMode: 2,
		icon: true,
		debugLevel: 0,
		source: {
			url: ajaxurl,
			data: {
				"action": "mainwp_get_installed_themes_wptc",
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
			});
		},
		select: function (event, data) {
			var selKeys = jQuery.map(data.tree.getSelectedNodes(), function (node) {
				return node.key;
			});
			jQuery("#auto_include_themes_wptc").val(selKeys.join(","));
		},
		dblclick: function (event, data) {
			data.node.toggleSelected();
		},
		keydown: function (event, data) {
			if (event.which === 32) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	});
}


function show_schedule_time_wptc() {
	var value = jQuery('#select_wptc_backup_slots').val();
	if (value === 'daily') {
		jQuery('#select_wptc_default_schedule').show();
	} else {
		jQuery('#select_wptc_default_schedule').hide();
	}
}


function fancy_tree_init_vulns_plugins_wptc() {
	jQuery("#wptc_vulns_plugins_dw").fancytree({
		checkbox: true,
		selectMode: 2,
		icon: true,
		debugLevel: 0,
		source: {
			url: ajaxurl,
			data: {
				"action": "mainwp_get_installed_plugins_vulns_wptc",
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			}
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
			});
		},
		select: function (event, data) {
			// Get a list of all selected nodes, and convert to a key array:
			var selKeys = jQuery.map(data.tree.getSelectedNodes(), function (node) {
				return node.key;
			});
			jQuery("#vulns_include_plugins_wptc").val(selKeys.join(","));
		},
		dblclick: function (event, data) {
			data.node.toggleSelected();
		},
		keydown: function (event, data) {
			if (event.which === 32) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	});
}

function fancy_tree_init_vulns_themes_wptc() {
	jQuery("#wptc_vulns_themes_dw").fancytree({
		checkbox: true,
		selectMode: 2,
		icon: true,
		debugLevel: 0,
		source: {
			url: ajaxurl,
			data: {
				"action": "mainwp_get_installed_themes_vulns_wptc",
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			}
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
			});
		},
		select: function (event, data) {
			var selKeys = jQuery.map(data.tree.getSelectedNodes(), function (node) {
				return node.key;
			});
			jQuery("#vulns_include_themes_wptc").val(selKeys.join(","));
		},
		dblclick: function (event, data) {
			data.node.toggleSelected();
		},
		keydown: function (event, data) {
			if (event.which === 32) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	});
}


function mainwp_wptc_analyze_inc_exc_lists(is_continue) {

	if (!is_continue) {
		wptc_cache_lists_of_files = [];
		swal({
			title: mainwp_wptc_get_dialog_header('Analyzing ...'),
			html: mainwp_wptc_get_dialog_body('Do not close the window, it will take few mins', ''),
			padding: '0px 0px 10px 0',
			buttonsStyling: false,
			showCancelButton: false,
			confirmButtonColor: '',
			confirmButtonClass: 'button-primary wtpc-button-primary',
			confirmButtonText: 'Ok',
		});
	}

	jQuery.post(ajaxurl, {
		action: 'mainwp_analyze_inc_exc_lists_wptc',
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (response) {
		response = jQuery.parseJSON(response)

		if (response.status == 'continue') {
			wptc_combine_cache_lists_of_files(response.files);
			wptc_analyze_inc_exc_lists('continue');
			return;
		}

		if (response.error) {
			swal({
				title: mainwp_wptc_get_dialog_header('Error'),
				html: mainwp_wptc_get_dialog_body('<i class="red times icon"></i> ' + response.error, ''),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				showCancelButton: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Ok',
			});
			return;
		}
		swal({
			title: mainwp_wptc_get_dialog_header('Optimize MySQL backups'),
			html: mainwp_wptc_get_dialog_body('Please make changes to the exclusion by moving your mouse near the table name.', ''),
			padding: '0px 0px 10px 0',
			buttonsStyling: false,
			showCancelButton: false,
			confirmButtonColor: '',
			confirmButtonClass: 'button-primary wtpc-button-primary',
			confirmButtonText: 'Save',
		}).then(function () {
			wptc_exclude_all_suggested();
		}, function (dismiss) {
			if (dismiss === 'cancel') {
				swal({
					title: mainwp_wptc_get_dialog_header('Success'),
					html: mainwp_wptc_get_dialog_body('You custom changes are saved!', 'success'),
					padding: '0px 0px 10px 0',
					buttonsStyling: false,
					showCancelButton: false,
					confirmButtonColor: '',
					confirmButtonClass: 'button-primary wtpc-button-primary',
					confirmButtonText: 'Ok',
				});
			}
		});

		if (Object.keys(response).length == 0) {
			swal({
				title: mainwp_wptc_get_dialog_header('Your database has been analyzed'),
				html: mainwp_wptc_get_dialog_body('Everything looks good!', ''),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				showCancelButton: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Ok',
			});

			return;
		}

		if ((!response.tables || !response.tables.length) && (!response.files || !response.files.length)) {
			swal({
				title: mainwp_wptc_get_dialog_header('Your database has been analyzed'),
				html: mainwp_wptc_get_dialog_body('Everything looks good!', ''),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				showCancelButton: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Ok',
			});
			return;
		}

		if (!response.tables || !response.tables.length) {
			jQuery("#wptc-suggested-exclude-tables").html('All tables are good !');
		} else {
			mainwp_add_suggested_tables_lists_wptc(response.tables);
		}
	});
}



function mainwp_add_suggested_tables_lists_wptc(source_data) {

	jQuery("#wptc-suggested-exclude-tables").fancytree({
		checkbox: false,
		selectMode: 1,
		icon: false,
		debugLevel: 0,
		source: source_data,
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) {
					node.setSelected(true);
					node.selected = true;
					node.addClass('fancytree-selected ');
					if (node.data.content_excluded && node.data.content_excluded == 1) {
						node.addClass('fancytree-partial-selected ');
					}
				}
			});
		},
		loadChildren: function (event, ctx) {
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function (event, data) {
			return false;
		},
		keydown: function (event, data) {
			if (event.which === 32) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function (event) {
		mouse_enter_tables_wptc(event);
	}).on("mouseleave", '.fancytree-node', function (event) {
		mouse_leave_tables_wptc(event);
	}).on("click", '.fancytree-table-exclude-key', function (event) {
		mouse_click_table_exclude_key_wptc(event);
	}).on("click", '.fancytree-table-include-key', function (event) {
		mouse_click_table_include_key_wptc(event);
	}).on("click", '.fancytree-table-exclude-content', function (event) {
		mouse_click_table_exclude_content_wptc(event);
	});

}


function mouse_enter_tables_wptc(event) {
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	jQuery(node.span).addClass('fancytree-background-color');
	jQuery(node.span).find('.fancytree-size-key').hide();
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	if (node.selected || (node.extraClasses && node.extraClasses.indexOf('fancytree-selected') !== -1)) {
		if (!node.extraClasses || node.extraClasses.indexOf('fancytree-partial-selected') === -1) {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key' style='margin-left: 10px;position: absolute;right: 120px;'><a>Exclude Table</a></span>");
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-content' style='position: absolute;right: 4px;'><a>Exclude Content</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key'><a>Exclude Table</a></span>");
		}
	} else {
		jQuery(node.span).append("<span role='button' class='fancytree-table-include-key'><a>Include Table</a></span>");
	}
}

function mouse_leave_tables_wptc(event) {
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function mainwp_send_report_issue_wptc(description, log_id) {

	jQuery.post(ajaxurl, {
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce,
		action: 'mainwp_wptc_send_issue_report',
		data: {
			log_id: log_id,
			description: description,
		}
	}, function (data) {
		try {
			var data = jQuery.parseJSON(data);
		} catch (err) {
			return;
		}

		if (data.success) {
			swal({
				title: mainwp_wptc_get_dialog_header('Success'),
				html: mainwp_wptc_get_dialog_body('Issue sent successfully, We will get in touch with you shortly.', 'success'),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Ok',
			});
		} else {
			swal({
				title: mainwp_wptc_get_dialog_header('Failed'),
				html: mainwp_wptc_get_dialog_body('Issue sending failed, try after sometime or email us at <a href="mailto:help@wptimecapsule.com?Subject=Contact" target="_top">help@wptimecapsule.com</a>.', 'error'),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Ok',
			});
		}
	});
}

function mainwp_wptc_get_dialog_header(title) {
	return '<div class="ui-dialog-titlebar ui-widget-header  ui-helper-clearfix" style="text-align: left;padding-left: 10px;"><span class="ui-dialog-title" style="font-size: 15px;line-height: 29px;text-align: left !important;">' + title + '</span></div>';
}

function mainwp_wptc_get_dialog_body(content, status) {

	var status_icon = '';

	if (status != undefined) {
		switch (status) {
			case 'success':
				status_icon = '<span class=" wptc-model-icon dashicons dashicons-yes" style="color: #79ba49;"></span>';
				break;
			case 'warning':
				status_icon = '<span class="wptc-model-alert-icon dashicons dashicons-warning" style="color: #ffb900;"></span>';
				break;
			case 'error':
				status_icon = '<span class=" wptc-model-icon dashicons dashicons-no-alt" style="color: #dc3232;"></span>';
				break;
		}
	}

	return '<div class="ui-dialog-content ui-widget-content" style="font-size: 14px;text-align: left;padding: 20px 30px 20px 30px;"> ' + status_icon + content + '</div><hr>';
}



function fancy_tree_init_exc_tables_wptc(init, id, category) {

	if (init) {
		jQuery('#wptc_init_table_div').css('position', 'absolute');
	}

	jQuery(id).fancytree({
		checkbox: false,
		selectMode: 2,
		icon: false,
		debugLevel: 0,
		source: {
			url: ajaxurl,
			data: (init === false) ? {
				action: "mainwp_wptc_get_tables",
				category: category,
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			} : {
				action: "mainwp_wptc_get_init_tables",
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			},
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) {
					node.setSelected(true);
					if (node.data.content_excluded && node.data.content_excluded == 1) {
						node.addClass('fancytree-partial-selected');
					}
				}
			});
		},
		loadChildren: function (event, ctx) {
			// ctx.node.fixSelection3AfterClick();
			// ctx.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function (event, data) {
			return false;
		},
		keydown: function (event, data) {
			if (event.which === 32) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function (event) {
		mouse_enter_tables_wptc(event);
	}).on("mouseleave", '.fancytree-node', function (event) {
		mouse_leave_tables_wptc(event);
	}).on("click", '.fancytree-table-exclude-key', function (event) {
		mouse_click_table_exclude_key_wptc(event);
	}).on("click", '.fancytree-table-include-key', function (event) {
		mouse_click_table_include_key_wptc(event);
	}).on("click", '.fancytree-table-exclude-content', function (event) {
		mouse_click_table_exclude_content_wptc(event);
	});
}

function fancy_tree_init_exc_files_wptc(init, id, category) {
	wptc_recent_category = category;

	jQuery(id).fancytree({
		checkbox: false,
		selectMode: 3,
		clickFolderMode: 3,
		debugLevel: 0,
		source: {
			url: ajaxurl,
			data: (init === false) ? {
				action: "mainwp_wptc_get_root_files",
				category: category,
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			} : {
				action: "mainwp_wptc_get_init_root_files",
				category: category,
				timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
				nonce: mainwp_timecapsule_loc.nonce
			},
		},
		postProcess: function (event, data) {
			data.result = data.response;
		},
		init: function (event, data) {
			data.tree.getRootNode().visit(function (node) {
				if (node.data.preselected) node.setSelected(true);
				if (node.data.partial) node.addClass('fancytree-partsel');
			});
		},
		lazyLoad: function (event, ctx) {
			var key = ctx.node.key;
			ctx.result = {
				url: ajaxurl,
				data: (init === false) ? {
					action: "mainwp_wptc_get_files_by_key",
					key: key,
					category: wptc_recent_category,
					timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
					nonce: mainwp_timecapsule_loc.nonce
				} : {
					action: "mainwp_wptc_get_init_files_by_key",
					key: key,
					category: wptc_recent_category,
					timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
					nonce: mainwp_timecapsule_loc.nonce
				},
			};
		},
		renderNode: function (event, data) { // called for every toggle
			if (!data.node.getChildren())
				return false;
			if (data.node.expanded === false) {
				data.node.resetLazy();
			}
			jQuery.each(data.node.getChildren(), function (key, value) {
				if (value.data.preselected) {
					value.setSelected(true);
				} else {
					value.setSelected(false);
				}
			});
		},
		loadChildren: function (event, data) {
			data.node.fixSelection3AfterClick();
			data.node.fixSelection3FromEndNodes();
			last_lazy_load_call = jQuery.now();
		},
		dblclick: function (event, data) {
			return false;
			// data.node.toggleSelected();
		},
		keydown: function (event, data) {
			if (event.which === 32) {
				data.node.toggleSelected();
				return false;
			}
		},
		cookieId: "fancytree-Cb3",
		idPrefix: "fancytree-Cb3-"
	}).on("mouseenter", '.fancytree-node', function (event) {
		mouse_enter_files_wptc(event);
	}).on("mouseleave", '.fancytree-node', function (event) {
		mouse_leave_files_wptc(event);
	}).on("click", '.fancytree-file-exclude-key', function (event) {
		mouse_click_files_exclude_key_wptc(event);
	}).on("click", '.fancytree-file-include-key', function (event) {
		mouse_click_files_include_key_wptc(event);
	});

	return false;
}


function mouse_enter_files_wptc(event) {
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node &&
		typeof node.span != 'undefined'
		&& (!node.getParentList().length
			|| node.getParent().selected !== false
			|| node.getParent().partsel !== false
			|| (node.getParent()
				&& node.getParent()[0]
				&& node.getParent()[0].extraClasses
				&& node.getParent()[0].extraClasses.indexOf("fancytree-selected") !== false)
			|| (node.getParent()
				&& node.getParent()[0]
				&& node.getParent()[0].extraClasses
				&& node.getParent()[0].extraClasses.indexOf("fancytree-partsel") !== false)
		)
	) {
		jQuery(node.span).addClass('fancytree-background-color');
		jQuery(node.span).find('.fancytree-size-key').hide();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		if (node.selected) {
			jQuery(node.span).append("<span role='button' class='fancytree-file-exclude-key'><a>Exclude</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-file-include-key'><a>Include</a></span>");
		}
	}
}

function mouse_leave_files_wptc(event) {
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function mouse_click_files_exclude_key_wptc(event) {
	var node = jQuery.ui.fancytree.getNode(event);

	if (!node) {
		return;
	}

	if (node != undefined && node.getChildren() != undefined) {
		var children = node.getChildren();
		jQuery.each(children, function (index, value) {
			value.selected = false;
			value.setSelected(false);
			value.removeClass('fancytree-partsel fancytree-selected')
		});
	}

	folder = (node.folder) ? 1 : 0;
	node.removeClass('fancytree-partsel fancytree-selected');
	node.selected = false;
	node.partsel = false;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	save_inc_exc_data_wptc('exclude_file_list_wptc', node.key, folder);
}

function mouse_click_files_include_key_wptc(event) {
	var node = jQuery.ui.fancytree.getNode(event);

	if (!node) {
		return;
	}

	if (node != undefined && node.getChildren() != undefined) {
		var children = node.getChildren();
		jQuery.each(children, function (index, value) {
			value.selected = true;
			value.setSelected(true);
			value.addClass('fancytree-selected')
		});
	}

	folder = (node.folder) ? 1 : 0;
	node.addClass('fancytree-selected');
	node.selected = true;
	jQuery(node.span).find(".fancytree-file-include-key, .fancytree-file-exclude-key").remove();
	save_inc_exc_data_wptc('include_file_list_wptc', node.key, folder);
}

function mouse_enter_tables_wptc(event) {
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	jQuery(node.span).addClass('fancytree-background-color');
	jQuery(node.span).find('.fancytree-size-key').hide();
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	if (node.selected || (node.extraClasses && node.extraClasses.indexOf('fancytree-selected') !== -1)) {
		if (!node.extraClasses || node.extraClasses.indexOf('fancytree-partial-selected') === -1) {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key' style='margin-left: 10px;position: absolute;right: 120px;'><a>Exclude Table</a></span>");
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-content' style='position: absolute;right: 4px;'><a>Exclude Content</a></span>");
		} else {
			jQuery(node.span).append("<span role='button' class='fancytree-table-exclude-key'><a>Exclude Table</a></span>");
		}
	} else {
		jQuery(node.span).append("<span role='button' class='fancytree-table-include-key'><a>Include Table</a></span>");
	}
}

function mouse_leave_tables_wptc(event) {
	// Add a hover handler to all node titles (using event delegation)
	var node = jQuery.ui.fancytree.getNode(event);
	if (node && typeof node.span != 'undefined') {
		jQuery(node.span).find('.fancytree-size-key').show();
		jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
		jQuery(node.span).removeClass('fancytree-background-color');
		jQuery(node.span).removeClass('fancytree-background-color');
	}
}

function mouse_click_table_exclude_key_wptc(event) {
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.removeClass('fancytree-partsel fancytree-selected fancytree-partial-selected');
	node.partsel = node.selected = false;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wptc('exclude_table_list_wptc', node.key, false);
}

function mouse_click_table_include_key_wptc(event) {
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.removeClass('fancytree-partial-selected');
	node.addClass('fancytree-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wptc('include_table_list_wptc', node.key, false);
}

function mouse_click_table_exclude_content_wptc(event) {
	event.stopImmediatePropagation();
	event.preventDefault();
	var node = jQuery.ui.fancytree.getNode(event);
	node.addClass('fancytree-partial-selected ');
	node.selected = true;
	jQuery(node.span).find(".fancytree-table-include-key, .fancytree-table-exclude-key, .fancytree-table-exclude-content").remove();
	save_inc_exc_data_wptc('include_table_structure_only_wptc', node.key, false);
}

function save_inc_exc_data_wptc(request, file, isdir) {
	jQuery.post(ajaxurl, {
		action: 'mainwp_' + request,
		data: { file: file, isdir: isdir, category: get_current_category_wptc() },
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		console.log(data);
	});
}


function get_current_category_wptc() {
	return (jQuery('#wp-time-capsule-tab-staging').length > 0) ? 'staging' : 'backup';
}

function parse_wptc_response_from_raw_data(raw_response) {
	//return substring closed by <wptc_head> and </wptc_head>
	return raw_response.split('<wptc_head>').pop().split('</wptc_head>').shift();
}

var this_home_url_wptc = '';

function getTcRestoreProgress() {

	if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
		var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/restore-progress-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
	} else {
		var this_url = 'restore-progress-ajax.php';
	}

	jQuery.ajax({
		traditional: true,
		type: 'post',
		url: this_url,
		data: {
			wptc_request: true
		},
		success: function (request) {
			request = parse_wptc_response_from_raw_data(request);
			request = jQuery.parseJSON(request);
			if ((typeof request != 'undefined') && request != null && request['status'] != null) {
				if (request['status'] === 'process' || request['status'] === 'analyze') {
					jQuery(".progress_reverse .progress_cont").html(request['msg']);
				} else if (request['status'] === 'download' || request['status'] === 'copy') {
					jQuery('.progress_reverse .progress_cont').html(request['msg']);
					if (request['percentage'] != 0) {
						jQuery('.progress_reverse .progress_bar').css('width', request['percentage'] + '%');
					}
				} else {
					jQuery('.progress_reverse .progress_cont').html(request['msg']);
					jQuery('.progress_reverse .progress_bar').css('width', '0%');
				}
			} else if (request == null) {
				if (typeof getRestoreProgressTimeout != 'undefined') {
					clearTimeout(getRestoreProgressTimeout);
				}
			}
		},
		error: function () {

		}
	});
	getRestoreProgressTimeout = setTimeout(function () {
		getTcRestoreProgress();
	}, 10000);
}

function mainwp_wptc_utf8_to_b64(str) {
	return window.btoa(str);
	//return window.btoa(encodeURIComponent( escape( str )));
}

function startBridgeDownload(data) {
	console.log('startBridgeDownload', data);

	start_time_tc = Date.now();

	//	if (typeof getRestoreProgressTimeout == 'undefined') {
	//		getTcRestoreProgress();
	//	}

	if (jQuery('.restore_process').length == 0 && jQuery('#TB_ajaxContent').length == 0) {
		jQuery('body').append("<div class='restore_process'><div id='TB_ajaxContent'><div class='pu_title'>Restoring your website</div><div class='wcard progress_reverse' style='height:60px; padding:0;'><div class='progress_bar' style='width: 0%;'></div>  <div class='progress_cont'>Preparing files to restore...</div></div><div style='padding: 10px; text-align: center;'>Note: Please do not close this tab until restore completes.</div></div>");
	}

	var this_data = {};

	var is_restore_in_staging = false;

	if (typeof data != 'undefined' && typeof data.redirect_url != 'undefined' && data.redirect_url) {
		this_home_url_wptc = data.redirect_url;
		var is_restore_in_staging = true;
	}

	if (typeof data != 'undefined' && typeof data.site_url != 'undefined' && data.site_url) {
		this_home_url_wptc = data.site_url;
	}

	if (window.location.href.indexOf('wp-tcapsule-bridge') === -1) {
		if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
			if (is_restore_in_staging) {
				// not run into there
				var parser = document.createElement('a');
				parser.href = this_home_url_wptc;
				var pathname = parser.pathname;
				var site_id = jQuery('#mainwp_wptc_current_site_id').val();
				var this_url = pathname + '/' + cuurent_bridge_file_name + '/index.php?continue=true&position=beginning&is_restore_in_staging=' + is_restore_in_staging; //cuurent_bridge_file_name is a global variable and is set already
				this_url = 'admin.php?page=SiteOpen&newWindow=yes&openUrl=yes&websiteid=' + site_id + '&location=' + mainwp_wptc_utf8_to_b64(this_url) + '&_opennonce=' + mainwpParams._wpnonce;
			} else {
				var this_url = this_home_url_wptc + cuurent_bridge_file_name + '/index.php?continue=true&position=beginning&is_restore_in_staging=' + is_restore_in_staging; //cuurent_bridge_file_name is a global variable and is set already
			}
		} else {
			// not run into there
			var this_url = this_home_url_wptc + 'index.php?continue=true&position=beginning&is_restore_in_staging=' + is_restore_in_staging; //cuurent_bridge_file_name is a global variable and is set already
		}
		//window.location.assign(this_url);
		window.open(this_url, '_blank');
		return false;
	}

}

function startRestore(files_to_restore, cur_res_b_id, selectedID, is_first_call, is_restore_to_staging_request) {
	start_time_tc = Date.now(); //global variable which will be used to see the activity so as to trigger new call when there is no activity for 60secs
	on_going_restore_process = true;

	if (typeof reloadFuncTimeout != 'undefined') {
		clearTimeout(reloadFuncTimeout);
	}

	jQuery.post(ajaxurl, {
		action: 'mainwp_start_restore_tc_wptc',
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce,
		data: {
			cur_res_b_id: cur_res_b_id,
			files_to_restore: files_to_restore,
			selectedID: selectedID,
			is_first_call: is_first_call,
			wptc_request: true,
			is_latest_restore_point: (is_restore_to_staging_request != undefined) ? 'already_set' : wptc_is_latest_restore_point_click(restore_obj),
		},
		dataType: 'json',
	}, function (request) {
		console.log('startRestore', request);
		if ((typeof request != 'undefined') && request != null) {
			if (request.indexOf("wptcs_callagain_wptce") != -1) {
				startRestore();
			} else if (request.indexOf("restoreInitiatedResult") != -1) {
				request = jQuery.parseJSON(request);
				if (typeof request['restoreInitiatedResult'] != 'undefined' && typeof request['restoreInitiatedResult']['bridgeFileName'] != 'undefined' && request['restoreInitiatedResult']['bridgeFileName']) {
					// not run into there
					cuurent_bridge_file_name = request['restoreInitiatedResult']['bridgeFileName'];
					//getTcRestoreProgress();
					if (request['restoreInitiatedResult']['is_restore_to_staging']) {
						request['initialize'] = true;
						request['redirect_url'] = request['restoreInitiatedResult']['staging_url'];
						startBridgeDownload(request);
						setTimeout(function () {
							dialog_close_wptc();
						}, 2000);

					} else {
						request['initialize'] = true;
						request['site_url'] = jQuery('#mainwp_wptc_current_site_url').val();
						startBridgeDownload(request);
						setTimeout(function () {
							dialog_close_wptc();
						}, 2000);
					}
					//					checkIfNoResponse('startBridgeDownload');
				} else {
					show_error_dialog_and_clear_timeout({ error: 'Didnt get required values to initiated restore.' });
				}
			} else if (request.indexOf("error") != -1) {
				request = jQuery.parseJSON(request);
				if (typeof request['error'] != 'undefined') {
					show_error_dialog_and_clear_timeout(request);
				}
			} else {
				show_error_dialog_and_clear_timeout({ error: 'Initiating Restore failed.' });
			}
		}
	});
}

function dialog_close_wptc() {
	tb_remove();
	if (backupclickProgress) {
		backupclickProgress = false;
	}
	if (typeof update_click_obj_wptc != 'undefined' && update_click_obj_wptc) {
		parent.location.assign(parent.location.href);
	}
}

function mainwp_disable_refresh_button_wptc() {
	jQuery('#refresh-c-status-area-wtpc, #refresh-s-status-area-wtpc').css('opacity', '0.5').addClass('disabled');
}

function mainwp_enable_refresh_button_wptc() {
	jQuery('#refresh-c-status-area-wtpc, #refresh-s-status-area-wtpc').css('opacity', '1').removeClass('disabled');
}

function mainwp_is_email_and_pwd_not_empty_wptc() {
	if ((jQuery("#wptc_main_acc_pwd").val() !== '') && (jQuery("#wptc_main_acc_email").val() !== '')) {
		return true;
	}
	return false;
}

function mainwp_get_sibling_files_wptc(obj) {
	var file_name = jQuery(obj).attr('file_name');
	var backup_id = jQuery(obj).attr('backup_id');
	var recursive_count = parseInt(jQuery(obj).parent().siblings('.this_leaf_node').attr('recursive_count'));
	if (!recursive_count) {
		recursive_count = parseInt((jQuery(obj).parent().attr('recursive_count'))) + 1;
	} else {
		recursive_count += 1;
	}
	last_lazy_load = obj;
	pushed_to_dom = 0;
	var trigger_filename = jQuery(obj).attr('file_name');
	var current_filename = '';
	var current_recursive_count = jQuery(obj).parents('.sub_tree_class').attr('recursive_count');
	jQuery.each(jQuery(obj).parents('.sub_tree_class').siblings(), function (key, value) {
		if (jQuery(value).attr('recursive_count') > current_recursive_count) {
			var current_filename = jQuery(value).find('.file_path').attr('parent_dir');
			if (current_filename == undefined) {
				var current_filename = jQuery(value).find('.folder').attr('parent_dir');
			}
			if (current_filename != undefined && current_filename.indexOf(trigger_filename) != -1) {
				jQuery(value).remove();
			}
		}
	});
	jQuery(obj).parents('.sub_tree_class').find('.this_leaf_node').remove();
	if (jQuery(obj).hasClass('open')) {
		jQuery(obj).removeClass('open').addClass('close');
		return false;
	} else {
		jQuery(obj).removeClass('close').addClass('loader');
	}
	jQuery.post(ajaxurl, {
		action: 'mainwp_get_sibling_files_wptc',
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce,
		data: { file_name: file_name, backup_id: backup_id, recursive_count: recursive_count },
	}, function (data) {
		if (typeof pushed_to_dom != 'undefined' && pushed_to_dom == 0) {
			jQuery(obj).removeClass('loader close').addClass('open');
			jQuery(last_lazy_load).parent().after(data)
			styling_thickbox_tc("");
			pushed_to_dom = 1;
		}
		registerDialogBoxEventsTC();
		jQuery(obj).removeClass('disabled');
	});
}

function mainwp_triggerLoginWptc(e) {
	if (!mainwp_is_email_and_pwd_not_empty_wptc()) {
		return false;
	}
	var key = e.which;
	if (key == 13) {
		jQuery("#mainwp_wptc_login").click();
		return false;
	}
}

jQuery(document).on("click", "#mainwp_wptc_login", function () {
	if (jQuery(this).hasClass('disabled')) {
		return false;
	}
	if (!mainwp_is_email_and_pwd_not_empty_wptc()) {
		return false;
	}
	jQuery(this).addClass('disabled').attr('disabled', 'disabled').val('Logging...').html('Logging...');
	mainwp_login_wptc();
	return false;
});


function mainwp_get_current_backup_status_wptc(dont_push_button) {
	mainwp_disable_refresh_button_wptc();
	dont_push_button_wptc = dont_push_button;
	jQuery.post(ajaxurl, {
		action: 'mainwp_progress_wptc',
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		mainwp_enable_refresh_button_wptc();
		if (typeof data == 'undefined' || !data.length) {
			return false;
		}

		try {
			data = jQuery.parseJSON(data);
		} catch (err) {
			return;
		}

		if (data == 0) {
			delete reloadFuncTimeout;
			return false;
		}

		if (data.error) {
			jQuery('#mainwp-wptc-message-zone').html(data.error).show();
			jQuery('#dashboard_activity').hide();
			jQuery('#progress').hide();
			return false;
		}

		last_backup_time = data.last_backup_time;
		var progress_val = 0.0;
		var prog_con = '';
		var backup_progress = data.backup_progress;

		//Last backup taken
		if (typeof last_backup_time != 'undefined' && last_backup_time != null && last_backup_time) {
			// jQuery(status_area_wptc).text('Last backup taken : ' + last_backup_time );
		} else {
			// jQuery(status_area_wptc).text('No backups taken');
		}

		//show_own_cron_status_wptc(data);

		//Notify backup failed
		if (data.start_backups_failed_server) {
			wptc_backup_start_failed_note(data.start_backups_failed_server);
			return false;
		}

		//get backup type
		if (data.starting_first_backup != 'undefined' && data.starting_first_backup) {
			backup_type = 'starting_backup';
		} else {
			backup_type = 'manual_backup';
		}

		if (backup_progress != '') {
			jQuery('.bp-progress-calender').show();
			progress_val = backup_progress.progress_percent;

			//First backup progress bar
			prog_con = '<div class="bp_progress_bar_cont"><span id="bp_progress_bar_note"></span><div class="bp_progress_bar" style="width:' + progress_val + '%"></div></div><span class="rounded-rectangle-box-wptc reload-image-wptc" id="refresh-c-status-area-wtpc"></span><div class="last-c-sync-wptc">Last reload: Processing...</div>';
			wptc_backup_running = true;
			//Settings page UI
			disable_settings_wptc();
			disable_pro_button_wptc();
			showLoadingDivInCalendarBoxWptc(); //show calender page details
		} else {
			wptc_backup_running = false;
			var this_percent = 0;
			var thisCompletedText = 'Initiating the backup...';

			//Will show after backup completed things
			if (data.progress_complete) {
				if (typeof backup_type != 'undefined' && backup_type != '') { // change after backup completed
					if (typeof backup_started_time_wptc == 'undefined' || (backup_started_time_wptc + 7000) <= jQuery.now()) {
						this_percent = 100;
						thisCompletedText = '<span style="top: 3px; font-size: 13px;  position: relative; left: 10px;">Backup Completed</span>';
					}

					//redirect once first backup is done
					if (backup_type == 'starting_backup') {
						backup_type = '';
						// parent.location.assign(adminUrlWptc+'admin.php?page=wp-time-capsule-monitor');
					} else if (backup_type == 'manual_backup') { //once manual backup is done check for other stuffs like staging.
						backup_type = '';
						setTimeout(function () {
							// tb_remove();
							if (typeof update_click_obj_wptc != 'undefined') {
								delete update_click_obj_wptc;
							}

							if (typeof backup_before_update != 'undefined' && backup_before_update == 'yes') {
								delete backup_before_update;
								tb_remove();
								// parent.location.assign(parent.location.href);
							}
						}, 3000);
					}
				}
			}

			//checking some Dom element to show whether backup text needs to shown or not
			if ((progress_val == 0) && (jQuery('.progress_bar').css('width') != '0px') && (jQuery('.wptc_prog_wrap').length == 0 || (jQuery('.bp_progress_bar').css('width') != '0px' && jQuery('.wptc_prog_wrap').length != 0 && jQuery('.bp_progress_bar').css('width') != undefined))) {
				this_percent = 100;
				progress_val = 100;
				thisCompletedText = '<span style="top: 3px; font-size: 13px;  position: relative; left: 10px;">Backup Completed</span>';
			}

			//Once backup completed then check for backup before updates
			if (thisCompletedText == '<span style="top: 3px; font-size: 13px;  position: relative; left: 10px;">Backup Completed</span>') {

				if (window.location.href.indexOf('?page=wp-time-capsule&new_backup=set') !== -1) {
					setTimeout(function () {
						parent.location.assign(adminUrlWptc + 'admin.php?page=wp-time-capsule-monitor');
					}, 3000)
				}
				if (jQuery(".this_modal_div .backup_progress_tc .progress_cont").text().indexOf('Updating') === -1) {
					jQuery('.bp-progress-calender').hide();
					prog_con = '<div class="bp_progress_bar_cont"><span id="bp_progress_bar_note"></span><div class="bp_progress_bar" style="width:' + this_percent + '%">' + thisCompletedText + '</div></div><span class="rounded-rectangle-box-wptc reload-image-wptc" id="refresh-c-status-area-wtpc"></span><div class="last-c-sync-wptc">Last reload: Processing...</div>';
				} else {
					thisCompletedText = 'Updated successfully.';
					prog_con = '<div class="bp_progress_bar_cont"><span id="bp_progress_bar_note"></span><div class="bp_progress_bar" style="width:' + this_percent + '%">' + thisCompletedText + '</div></div><span class="rounded-rectangle-box-wptc reload-image-wptc" id="refresh-c-status-area-wtpc"></span><div class="last-c-sync-wptc">Last reload: Processing...</div>';
				}
			} else {
				jQuery('.bp-progress-calender').show();
			}

			enable_settings_wptc();
			enable_pro_button_wptc();
			resetLoadingDivInCalendarBoxWptc();
		}

		//show backup percentage in staging area also
		if (typeof show_backup_status_staging !== 'undefined' && jQuery.isFunction(show_backup_status_staging)) {
			show_backup_status_staging(backup_progress, progress_val);
		}

		jQuery('.wptc_prog_wrap').html('');
		jQuery('.wptc_prog_wrap').append(prog_con);
		if (jQuery('.l1.wptc_prog_wrap').hasClass('bp-progress-first-bp')) {
			jQuery('.bp_progress_bar_cont').addClass('bp_progress_bar_cont-first-b-wptc');
			jQuery('.rounded-rectangle-box-wptc').addClass('rounded-rectangle-box-wptc-first-c-wptc');
		}

		//backup before update showing data
		if (typeof bbu_message_update_progress_bar !== 'undefined' && jQuery.isFunction(bbu_message_update_progress_bar)) {
			bbu_message_update_progress_bar(data);
		}

		//Showing all the data here
		process_backup_status(backup_progress, progress_val);

		//Load new update pop up
		//load_custom_popup_wptc(data.user_came_from_existing_ver , 'new_updates')


		//If staging running do not start backup
		stop_starting_new_backups(data);
		//show_notification_bar(data);
		if (dont_push_button_wptc !== 1) {
			push_extra_button_wptc(data);
		} else {
			dont_push_button_wptc = 0;
		}
		update_backup_status_in_staging(data);
		update_last_sync_wptc();
		if (typeof mainwp_process_wtc_reload !== 'undefined' && jQuery.isFunction(mainwp_process_wtc_reload)) {
			mainwp_process_wtc_reload(data);
		}
	});
}

function update_last_sync_wptc() {
	jQuery('.last-c-sync-wptc, .last-s-sync-wptc').html('Last reload: ' + gettime_wptc());
}

function stop_starting_new_backups(data) {
	if (data.is_staging_running && data.is_staging_running == 1) {
		jQuery("#wptc_save_changes").addClass('disabled').attr('disabled', 'disabled');
		jQuery('#start_backup_from_settings').attr('action', 'disabled').addClass('disabled');
	}
}

function update_backup_status_in_staging(data) {
	if (data.is_staging_running && data.is_staging_running == 1) {
		jQuery("#wptc_save_changes").addClass('disabled').attr('disabled', 'disabled');
		jQuery('#start_backup_from_settings').attr('action', 'disabled').addClass('disabled');

		jQuery("#select_wptc_default_schedule, #wptc_timezone, #wptc_auto_update_schedule_time").addClass('disabled').attr('disabled', 'disabled');
		jQuery('#start_backup_from_settings').attr('action', 'disabled').addClass('disabled');
		jQuery('.change_dbox_user_tc').addClass('wptc-link-disabled');
		jQuery('.setting_backup_progress_note_wptc').show();

	}
}

function show_notification_bar(data) {
	if (data.bbu_note_view) {
		jQuery('.success-bar-wptc, .error-bar-wptc, .warning-bar-wptc, .message-bar-wptc').remove();
		jQuery('.success-bar-wptc, .error-bar-wptc, .warning-bar-wptc, .message-bar-wptc', window.parent.document).remove();
		if (jQuery("#wpadminbar").length > 0) {
			var adminbar = "#wpadminbar";
			var iframe = false;
		} else {
			var adminbar = jQuery('#wpadminbar', window.parent.document);
			var iframe = true;
		}
		if (data.bbu_note_view.type === 'success') {
			jQuery(adminbar).after("<div style='display:none' class='success-bar-wptc success-image-wptc close-image-wptc'><span id='bar-note-wptc'>" + data.bbu_note_view.note + "</span></div>");
			setTimeout(function () {
				if (iframe) {
					if (!jQuery('.success-bar-wptc', window.parent.document).is(':visible')) {
						jQuery('.success-bar-wptc', window.parent.document).slideToggle(); //sample
					}
				} else {
					if (!jQuery('.success-bar-wptc').is(':visible')) {
						jQuery('.success-bar-wptc').slideToggle(); //sample
					}
				}
				if (typeof clear_bbu_notes !== 'undefined' && jQuery.isFunction(clear_bbu_notes)) {
					clear_bbu_notes();
				}
			}, 1000);
		} else if (data.bbu_note_view.type === 'error') {
			jQuery(adminbar).after("<div style='display:none' class='error-bar-wptc error-image-wptc close-image-wptc'><span id='bar-note-wptc'>" + data.bbu_note_view.note + "</span></div>");
			setTimeout(function () {
				if (iframe) {
					if (!jQuery('.error-bar-wptc', window.parent.document).is(':visible')) {
						jQuery('.error-bar-wptc', window.parent.document).slideToggle(); //sample
					}
				} else {
					if (!jQuery('.error-bar-wptc').is(':visible')) {
						jQuery('.error-bar-wptc').slideToggle(); //sample
					}
				}

				if (typeof clear_bbu_notes !== 'undefined' && jQuery.isFunction(clear_bbu_notes)) {
					clear_bbu_notes();
				}
			}, 1000);
		} else if (data.bbu_note_view.type === 'warning') {
			jQuery(adminbar).after("<div style='display:none' class='warning-bar-wptc warning-image-wptc close-image-wptc'><span id='bar-note-wptc'>" + data.bbu_note_view.note + "</span></div>");
			setTimeout(function () {
				if (iframe) {
					if (!jQuery('.warning-bar-wptc', window.parent.document).is(':visible')) {
						jQuery('.warning-bar-wptc', window.parent.document).slideToggle(); //sample
					}
				} else {
					if (!jQuery('.warning-bar-wptc').is(':visible')) {
						jQuery('.warning-bar-wptc').slideToggle(); //sample
					}
				}

				if (typeof clear_bbu_notes !== 'undefined' && jQuery.isFunction(clear_bbu_notes)) {
					clear_bbu_notes();
				}
			}, 1000);
		} else if (data.bbu_note_view.type === 'message') {
			jQuery(adminbar).after("<div style='display:none' class='message-bar-wptc message-image-wptc close-image-wptc'><span id='bar-note-wptc'>" + data.bbu_note_view.note + "</span></div>");
			setTimeout(function () {
				if (iframe) {
					if (!jQuery('.message-bar-wptc', window.parent.document).is(':visible')) {
						jQuery('.message-bar-wptc', window.parent.document).slideToggle(); //sample
					}
				} else {
					if (!jQuery('.message-bar-wptc').is(':visible')) {
						jQuery('.message-bar-wptc').slideToggle(); //sample
					}
				}

				if (typeof clear_bbu_notes !== 'undefined' && jQuery.isFunction(clear_bbu_notes)) {
					clear_bbu_notes();
				}
			}, 1000);
		}
	}
	// jQuery("#wpadminbar").after("<div style='display:block' class='warning-bar-wptc warning-image-wptc close-image-wptc'><span id='bar-note-wptc'>Just informed you</span></div>");
	// jQuery("#wpadminbar").after("<div style='display:block' class='message-bar-wptc message-image-wptc close-image-wptc'><span id='bar-note-wptc'>Just informed you</span></div>");
	// jQuery("#wpadminbar").after("<div style='display:block' class='error-bar-wptc error-image-wptc close-image-wptc'><span id='bar-note-wptc'>Just informed you</span></div>");
	// jQuery("#wpadminbar").after("<div style='display:block' class='info-bar-wptc error-image-wptc close-image-wptc'><span id='bar-note-wptc'>Just informed you</span></div>");
}

function push_extra_button_wptc(data) {
	if (window.location.href.indexOf('update-core.php') === -1 && window.location.href.indexOf('plugins.php') === -1 && window.location.href.indexOf('themes.php') === -1 && window.location.href.indexOf('plugin-install.php') === -1) {
		return false;
	}
	if (typeof push_staging_button_wptc !== 'undefined' && jQuery.isFunction(push_staging_button_wptc)) {
		push_staging_button_wptc(data);
	}
	if (typeof push_bbu_button_wptc !== 'undefined' && jQuery.isFunction(push_bbu_button_wptc)) {
		push_bbu_button_wptc(data);
	}
}

function disable_pro_button_wptc() {
	if (typeof disable_staging_button_wptc !== 'undefined' && jQuery.isFunction(disable_staging_button_wptc)) {
		disable_staging_button_wptc();
	}
}

function enable_pro_button_wptc() {
	if (typeof enable_staging_button_wptc !== 'undefined' && jQuery.isFunction(enable_staging_button_wptc)) {
		enable_staging_button_wptc();
	}
}

function show_own_cron_status_wptc(data) {
	if (!data.wptc_own_cron_status) {
		return false;
	}
	if (typeof data.wptc_own_cron_status.status != 'undefined') {
		if (data.wptc_own_cron_status.status == 'success') {
			//leave it
		} else if (data.wptc_own_cron_status.status == 'error') {
			//load_cron_status_failed_popup(data.wptc_own_cron_status.statusCode, data.wptc_own_cron_status.body, data.wptc_own_cron_status.cron_url, data.wptc_own_cron_status.ips, data.wptc_own_cron_status_notified);
			return false;
		}
	}
}



function disable_settings_wptc() {
	if (jQuery("#start_backup_from_settings").text() != 'Stopping backup...') {
		jQuery("#start_backup_from_settings").attr("action", "stop").text("Stop Backup");
		jQuery("#backup_button_status_wptc").text("Clicking on Stop Backup will erase all progress made in the current backup.");
		jQuery("#select_wptc_backup_slots, #select_wptc_default_schedule, #wptc_timezone, #wptc_auto_update_schedule_time").addClass('disabled').attr('disabled', 'disabled');
		jQuery('.change_dbox_user_tc').addClass('wptc-link-disabled');
		jQuery('.setting_backup_stop_note_wptc').show();
		jQuery('.setting_backup_start_note_wptc').hide();
		jQuery('.setting_backup_progress_note_wptc').show();
	}
}


function enable_settings_wptc() {
	if (jQuery("#start_backup_from_settings").text() != 'Starting backup...') {
		jQuery("#start_backup_from_settings").attr("action", "start").text("Backup now");
		jQuery("#backup_button_status_wptc").text("Click Backup Now to backup the latest changes.");
		jQuery("#select_wptc_backup_slots, #select_wptc_default_schedule, #wptc_timezone, #wptc_auto_update_schedule_time").removeClass('disabled').removeAttr('disabled');
		jQuery('.change_dbox_user_tc').removeClass('wptc-link-disabled');
		jQuery('.setting_backup_stop_note_wptc').hide();
		jQuery('.setting_backup_start_note_wptc').show();
		jQuery('.setting_backup_progress_note_wptc').hide();
	}
}

//function disable_settings_wptc(){
//	if (jQuery("#start_backup_from_settings").text() != 'Stopping backup...') {
//		jQuery("#start_backup_from_settings").attr("action", "stop").text("Stop Backup");
//		jQuery("#backup_button_status_wptc").text("Clicking on Stop Backup will erase all progress made in the current backup.");
//		jQuery("#wptc_save_changes").addClass('disabled').attr('disabled', 'disabled');
//		jQuery("#cannot_save_settings_wptc").show();
//		jQuery('#change_repo_wptc').css('color','#c4c4c4').bind('click', false);
//	}
//}
//
//
//function enable_settings_wptc(){
//	if (jQuery("#start_backup_from_settings").text() != 'Starting backup...') {
//		jQuery("#start_backup_from_settings").attr("action", "start").text("Backup now");
//		jQuery("#backup_button_status_wptc").text("Click Backup Now to backup the latest changes.");
//		jQuery("#wptc_save_changes").removeClass('disabled');
//		jQuery("#cannot_save_settings_wptc").hide();
//		jQuery("#wptc_save_changes").removeAttr('disabled');
//		jQuery('#change_repo_wptc').css('color', '#0073aa').unbind('click', false);
//	}
//}

function mainwp_show_backup_progress_dialog(obj, type) {
	//this function updates the progress bar in the dialog box ; during backup
	// jQuery(window.parent.document.body).find('iframe').remove();
	remove_other_thickbox_wptc();
	if (jQuery("#dialog_content_id").length === 0) {
		jQuery("#dialog_content_id").remove();
		jQuery(".wrap").append('<div id="dialog_content_id" style="display:none;"> <p> hidden cont. </p></div><a class="thickbox wptc-thickbox" style="display:none" href="#TB_inline?width=500&height=500&inlineId=dialog_content_id&modal=true"></a>');
	}

	// var dialog_content = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 35px 35px 35px; width: 450px; left:20%; z-index:1000"><span class="dialog_close" style="display:none"></span><div class="mainwp_wtc_wcard hii backup_progress_tc" style="height:60px; padding:0; margin-top:27px; display:none;"><div class="progress_bar" style="width:0%;"></div> <div class="progress_cont">Backing up files before updating...</div></div><div style="display:none;font-size: 12px; text-align:center">If you want to check the overall backup status of all your sites, <a href="https://service.wptimecapsule.com/" style="text-decoration:underline; cursor:pointer" target="_blank">click here</a>.</div><div id="manual_backup_name_div" style="font-size: 12px;text-align:center;padding-top: 27px;"><input type="text" id="manual_backup_custom_name" placeholder="Give this backup a name" style="border-radius: 3px;height: 35px;width: 50%;z-index: 1;position: relative;"><a class="button button-primary" id="save_manual_backup_name" style="margin: 1px 0px 0px -3px;height: 34px;z-index: 2;width: 15%;font-weight: 700;position: relative;border-top-right-radius: 5px;border-top-left-radius: 0px;line-height: 30px;border-bottom-left-radius: 0px;border-bottom-right-radius: 5px;">Save</a>'+bbu_note+'</div></div>';
	var dialog_content = mainwp_ask_backup_name_wptc(type);
	if (dialog_content === false) {
		return false;
	}
	if (jQuery('.tc_backup_before_update').length > 0) {
		tb_remove();
	} else if (jQuery('.mainwp_wtc_wcard.clearfix').length != 0 || jQuery('#backup_to_dropbox_options h2').text() == 'Backups') {
		return;
	}
	if (type == 'fresh' && jQuery('.this_modal_div').is(':visible') === false) {
		jQuery("#dialog_content_id").html(dialog_content); //since it is the first call we are generating thickbox like this
		jQuery(".wptc-thickbox").click().removeClass('thickbox-loading');
		jQuery(".bp_progress_bar").text("Initiating the backup...");
		styling_thickbox_tc('progress');
	} else if ((type == 'bbu' || type == 'incremental') && jQuery('.this_modal_div').is(':visible') === false) {
		if (jQuery('#TB_window').length == 0) {
			jQuery("#dialog_content_id").html(dialog_content); //since it is the first call we are generating thickbox like this
		} else {
			if (jQuery('body').find('iframe').length) {
				jQuery('body').find('iframe').remove();
				jQuery('body').find('#TB_window').remove();
				jQuery('body').find('#TB_overlay').remove();
				jQuery("#dialog_content_id").html(dialog_content); //since it is the first call we are generating thickbox like this
			}
		}
		jQuery(".wptc-thickbox").click().removeClass('thickbox-loading');
		jQuery(".bp_progress_bar").text("Initiating the backup...");
		styling_thickbox_tc('progress');
	} else if (jQuery('.this_modal_div').is(':visible') === false) {
		jQuery("#TB_ajaxContent").html(dialog_content);
		styling_thickbox_tc('backup_yes');
	}
	jQuery('#manual_backup_custom_name').focus();

	jQuery('#manual_backup_custom_name').keypress(function (e) {
		var key = e.which;
		console.log(key);
		if (key == 13) {
			jQuery('#save_manual_backup_name_wptc').click();
		}
	});
	// tb_remove();
	// ask_backup_name_wptc();
	jQuery("#TB_load").remove();
	jQuery("#TB_window").css({ 'margin-left': '', 'left': '40%' });

	jQuery("#TB_overlay").on("click", function () {
		if (typeof is_backup_completed != 'undefined' && is_backup_completed == true && !on_going_restore_process) { //for enabling dialog close on complete
			tb_remove();
		}
	});

	jQuery(".dialog_close").on("click", function () {
		tb_remove();
		if (typeof update_click_obj_wptc != 'undefined' && update_click_obj_wptc) {
			parent.location.assign(parent.location.href);
		}
	});
}

function mainwp_ask_backup_name_wptc(type) {
	var bbu_note = '';
	var height = '88px';
	if (type === 'bbu') {
		// var bbu_note = '<div style=" text-align: center; top: 18px; position: relative;">We will notify you once the backup and updates are completed.</div>';
		// var height = '118px';
		var data = {
			bbu_note_view: {
				type: 'message', note: 'We will notify you once the backup and updates are completed.',
			},
		};
		show_notification_bar(data);
		return false;
	}
	var html = '<div class="theme-overlay" style="z-index: 1000;"><div class="inside" id="backup_custom_name_model" style="height: ' + height + ';"><div id="manual_backup_name_div" style="font-size: 12px;text-align:center;padding-top: 27px;"><input type="text" id="manual_backup_custom_name" placeholder="Give this backup a name" style="border-radius: 3px;height: 35px;width: 50%;z-index: 1;position: relative;"><a class="button button-primary" id="save_manual_backup_name_wptc" style="margin: 1px 0px 0px -3px;height: 34px;z-index: 2;width: 15%;font-weight: 700;position: relative;border-top-right-radius: 5px;border-top-left-radius: 0px;line-height: 30px;border-bottom-left-radius: 0px;border-bottom-right-radius: 5px;">Save</a>' + bbu_note + '</div></div></div>';
	return html;
	// jQuery('#TB_load').remove();
	// jQuery('#TB_window').append(html).removeClass('thickbox-loading');
	// jQuery('#TB_ajaxContent').hide();
}

function mainwp_wtc_start_backup_func(type, update_items, update_ptc_type, backup_before_update_always) {
	bp_in_progress = true;
	backup_started_time_wptc = jQuery.now();
	var is_staging_req = 0;
	mainwp_get_current_backup_status_wptc();
	if (type == 'from_setting') {
		mainwp_show_backup_progress_dialog('', 'incremental');
	} else if (type == 'from_staging') {
		if (typeof copy_staging_wptc != 'undefined' && copy_staging_wptc) {
			is_staging_req = 2;
		} else {
			is_staging_req = 1;
		}
	} else if (type == 'from_bbu') {
		mainwp_show_backup_progress_dialog('', 'bbu');
	}
	database_backup_competed = processing_files_completed = '';
	jQuery.post(ajaxurl, {
		action: 'mainwp_start_fresh_backup_tc_wptc',
		type: 'manual',
		backup_before_update: update_items,
		update_ptc_type: update_ptc_type,
		is_auto_update: 0,
		is_staging_req: is_staging_req,
		backup_before_update_setting: backup_before_update_always,
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		if (typeof freshBackupWptc != 'undefined' && freshBackupWptc == 'yes') {
			started_fresh_backup = true;
			var inicontent = '<div style="margin-top: 24px; background: none repeat scroll 0% 0% rgb(255, 255, 255); padding: 0px 7px; box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);"><p style="text-align: center; line-height: 24px;">This is your first backup. This might take any where between 20 minutes to a few hours based on your site\'s size. <br>Subsequent backups will be instantaneous since they are incremental. <br>Please be patient and don\'t close the window.</p></div>';
			jQuery(".backup_progress_tc").first().parent().append(inicontent);
			//initial_backup_name_store();    //may require this when we want to rename the backups so dont remove this comment
		} else if (jQuery('#staging_area_wptc').length > 0) {
			//staging area
		} else {
			if (window.location.href.indexOf('page=wp-time-capsule') !== -1) {
				mainwp_show_backup_progress_dialog('', 'incremental');
			}
			started_fresh_backup = false;
		}
		backup_end = true;
		start_backup_from_settings = false;
		trigger_wtc_settings_reload = setTimeout(function () {
			bp_in_progress = force_trigger_ajax_load = true;
			mainwp_get_current_backup_status_wptc();
		}, 5000);
	}, 'json');
}

function mainwp_wtc_stop_backup_func() {
	var this_obj = jQuery(this);
	backup_type = '';
	jQuery.post(ajaxurl, {
		action: 'mainwp_stop_fresh_backup_tc_wptc',
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		jQuery('#start_backup').text("Stop Backup");
		jQuery(this_obj).hide();
		bp_in_progress = false;
		mainwp_get_current_backup_status_wptc();
		//window.location = adminUrlWptc + '?page=wp-time-capsule';
	});
}


function showLoadingDivInCalendarBoxWptc() {
	jQuery('.tc_backup_before_update').addClass('disabled backup_is_going');
	bp_in_progress = true;
	return;
	resetLoadingDivInCalendarBoxWptc();
	jQuery('.fc-today div').hide();
	jQuery('.fc-today').append('<div class="tc-backingup-loading"></div>');
}

function resetLoadingDivInCalendarBoxWptc() {
	bp_in_progress = false;
	jQuery('.tc_backup_before_update').removeClass('disabled backup_is_going');
	return;
	jQuery('.tc-backingup-loading').remove();
	jQuery('.fc-today div').show();
}

function styling_thickbox_tc(styleType) {
	jQuery("#TB_window").removeClass("thickbox-loading");
	jQuery("#TB_title").hide();
	if (styleType == 'progress') {
		jQuery("#TB_window").width("518px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		//jQuery("#TB_ajaxContent").css("max-height", "322px");
		jQuery("#TB_ajaxContent").css("height", "auto");
	} else if (styleType == 'backup_yes') {
		jQuery("#TB_window").width("578px");
		jQuery("#TB_ajaxContent").width("578px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_window").css("height", "auto");
		jQuery("#TB_ajaxContent").css("height", "auto");
		jQuery("#TB_window").css("margin-top", "66px");
		jQuery("#TB_ajaxContent").css("max-height", "322px");
		jQuery("#TB_window").css("max-height", "322px");
	} else if (styleType == 'backup_yes_no') {
		jQuery("#TB_window").width("578px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("578px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("height", "auto");
		jQuery("#TB_window").css("height", "auto");
		jQuery("#TB_window").css("margin-top", "66px");
		jQuery("#TB_window").css("max-height", "274px");
		jQuery("#TB_ajaxContent").css("max-height", "274px");
		jQuery("#TB_window").css("max-width", "578px");
	} else if (styleType == 'restore') {
		jQuery("#TB_window").width("518px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("max-height", "322px");
		jQuery("#TB_ajaxContent").css("height", "auto");
	} else if (styleType == 'change_account') {
		jQuery("#TB_window").width("578px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("578px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("max-height", "500px");
		jQuery("#TB_ajaxContent").css("height", "auto");
	} else if (styleType == 'report_issue') {
		jQuery("#TB_window").width("518px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("max-height", "600px");
		jQuery("#TB_ajaxContent").css("height", "auto");
	} else if (styleType == 'initial_backup') {
		jQuery("#TB_window").width("630px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("630px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("max-height", "500px");
		jQuery("#TB_ajaxContent").css("min-height", "225px");
		jQuery("#TB_ajaxContent").css("height", "auto");
		jQuery("#TB_overlay").attr("onclick", "tb_remove()");
	} else if (styleType == 'backup_before') {
		jQuery("#TB_window").width("518px");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_window").css("height", "220px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
	} else if (styleType == 'backup_before') {
		jQuery("#TB_window").width("518px");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_window").css("height", "220px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
	} else if (styleType == 'staging_db') {
		jQuery("#TB_window").width("612px");
		jQuery("#TB_window").css("margin-top", "-245px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("627px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("height", "auto");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_window").css("top", "300px");
		//jQuery("#TB_window").css("overflow", "hidden");
		var this_height = (jQuery(window).height() * .9) + "px";
		jQuery("#TB_ajaxContent").css("max-height", this_height);
	} else {
		jQuery("#TB_window").width("891px");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_ajaxContent").width("891px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("height", "auto");
		jQuery("#TB_window").height("auto");
		jQuery("#TB_window").css("top", "300px");
		//jQuery("#TB_window").css("overflow", "hidden");
		var this_height = (jQuery(window).height() * .8) + "px";
		jQuery("#TB_ajaxContent").css("max-height", this_height);
		// var win_height = (jQuery("#TB_ajaxContent").height() / 4) + "px";
		// jQuery("#TB_window").css("margin-top", "-" + win_height);
	}
	jQuery("#TB_window").css('margin-bottom', '0px');

}

function issue_repoting_form() {
	jQuery.post(ajaxurl, {
		action: 'get_issue_report_data_wptc'
	}, function (data) {
		var data = jQuery.parseJSON(data);
		var form_content = '<div class=row-wptc style="padding: 0 0 49px 0;"><div class="wptc-float-left">Name</div><div class="wptc-float-right"><input type="text" style="width:96%" name="uname" value="' + data.lname + '"></div></div><div class=row-wptc style="padding: 0 0 49px 0;"><div class="wptc-float-left">Title</div><div class="wptc-float-right"><input type="text" style="width:96%" name="title"></div></div><div class="row-wptc" style="height: 132px;"><div class="wptc-float-left">Issue Data</div><div class="wptc-float-right"><textarea name="issuedata" id="panelHistoryContent" cols="37" rows="5" readonly class="disabled">' + data.idata + '</textarea></div></div><div class=row-wptc style="padding: 0 0 49px 0;"><div class="wptc-float-right"><input id="send_issue_wptc" class="button button-primary" type="button" value="Send"><input id="cancel_issue" style="margin-left: 3%;" class="button button-primary" type="button" value="Cancel"></div></div>';
		var dialog_content = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 35px 35px 35px; width: 450px;left:20%; z-index:1000"><span class="dialog_close" id="form_report_close"></span><div class="pu_title">Send Report</div><form name="issue_form" id="issue_form">' + form_content + '</form></div>';
		remove_other_thickbox_wptc();
		jQuery("#dialog_content_id").html(dialog_content);
		jQuery(".wptc-thickbox").click();
		styling_thickbox_tc('report_issue');
	});

}

function sendWTCIssueReport(issueData) {
	var email = issueData[0]['value'];
	var desc = issueData[1]['value'];
	var issue = issueData[2]['value'];
	var fname = issueData[3]['value'];
	var idata = {
		'email': email,
		'desc': desc,
		'issue_data': issue,
		'name': fname
	};
	jQuery.post(ajaxurl, {
		action: 'mainwp_send_wtc_issue_report_wptc',
		data: idata,
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (response) {
		if (response == "sent") {
			jQuery("#issue_form").html("");
			jQuery("#issue_form").html("<div class='wptc-success_issue'>Issue submitted successfully<div>");
		} else {
			jQuery("#issue_form").html("");
			jQuery("#issue_form").html("<div class='wptc-fail-issue'>Issue sending failed.Try after sometime<div>");
		}
	});
}

function mainwp_yes_delete_logs_wptc() {
	jQuery.post(ajaxurl, {
		action: 'mainwp_clear_wptc_logs',
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (response) {
		if (response == "yes") {
			swal({
				title: mainwp_wptc_get_dialog_header('Success'),
				html: mainwp_wptc_get_dialog_body('Logs are deleted', 'success'),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Ok',
			});

			setTimeout(function () {
				parent.location.assign(parent.location.href);
			}, 2000)

		} else {
			swal({
				title: mainwp_wptc_get_dialog_header('Failed'),
				html: mainwp_wptc_get_dialog_body('Failed to remove logs', 'error'),
				padding: '0px 0px 10px 0',
				buttonsStyling: false,
				confirmButtonColor: '',
				confirmButtonClass: 'button-primary wtpc-button-primary',
				confirmButtonText: 'Ok',
			});
		}
	});
}


function reload_monitor_page() {
	parent.location.assign(parent.location.href);
}

function freshBackupPopUpShow() {
	var StartBackup = jQuery('#start_backup').html();
	var StopBackup = jQuery('#stop_backup').html();
	if (StartBackup != "Stop Backup" && StopBackup != "Stop Backup") {
		var dialog_content = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px; left:20%; z-index:1000"><span class="dialog_close"></span><div class="pu_title">Your first backup</div><div class="mainwp_wtc_wcard clearfix" style="width:480px"><div class="l1">Do you want to backup your site now?</div><a style="margin-left: 29px;" class="btn_pri" onclick="initialSetupBackup()">Yes. Backup now.</a><a class="btn_sec" id="no_change" onclick="tb_remove()">No. I will do it later.</a></div></div>';
		setTimeout(function () {
			remove_other_thickbox_wptc();
			jQuery("#dialog_content_id").html(dialog_content);
			jQuery(".wptc-thickbox").click();
			styling_thickbox_tc('initial_backup');
		}, 3000);
	}
}

function initialSetupBackup() {
	jQuery('#start_backup').click()
	tb_remove();
}


function process_backup_status(backup_progress, prog_percent) {
	if (backup_progress != '') {
		if (backup_progress.db.running) {
			update_backup_status('synchingDB', backup_progress.db.progress);
		} else if (backup_progress.files.processing.running) {
			update_backup_status('checkingForChanges', backup_progress.files.processing.progress);
		} else if (backup_progress.files.processed.running) {
			update_backup_status('synchingFiles', prog_percent);
		}
	} else {
		update_backup_status('backupCompleted');
	}
}

function update_backup_status(type, progPercent) {
	if (type == 'checkingForChanges') {
		jQuery(status_area_wptc).html(' Processing files (' + progPercent + ' files)');
	} else if (type == 'synchingDB') {
		jQuery(status_area_wptc).html(' Syncing database (' + progPercent + ' tables)');
	} else if (type == 'synchingFiles') {
		jQuery(status_area_wptc).html(' Syncing changed files ' + progPercent + '%');
		jQuery('.staging_progress_bar').css('width', progPercent + '%');
	} else {
		if (typeof last_backup_time != 'undefined' && last_backup_time != null && last_backup_time) {
			// jQuery(status_area_wptc).html('Last backup taken : ' + last_backup_time );
		} else {
			// jQuery(status_area_wptc).html('No backups taken');
		}
	}
}

function get_this_day_backups_ajax(backupIds) {
	remove_other_thickbox_wptc();
	jQuery.post(ajaxurl, {
		action: 'mainwp_get_this_day_backups_wptc',
		data: backupIds,
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		jQuery(".dialog_cont").remove();
		jQuery("#dialog_content_id").html(data);
		jQuery(jQuery("#dialog_content_id").find('.bu_name')).each(function (index) {
			if (jQuery(this).text().indexOf('Updated on') == 0) {
				jQuery(this).hide();
			}
		});
		jQuery(".wptc-thickbox").click();

		styling_thickbox_tc();
		registerDialogBoxEventsTC();
		//do the UI action to hide the folders, display the folders based on tree
		jQuery(".this_parent_node .sub_tree_class").hide();
		jQuery(".this_parent_node .this_leaf_node").hide();
		jQuery(".this_leaf_node").show();
		jQuery(".sub_tree_class.sl0").show();

		//for hiding the backups folder and its sql-file
		var sqlFileParent = jQuery(".sql_file").parent(".this_parent_node");
		jQuery(sqlFileParent).hide();
		//jQuery(sqlFileParent).parent(".this_parent_node").hide();
		//jQuery(sqlFileParent).parent(".this_parent_node").prev(".sub_tree_class").hide();
		jQuery(sqlFileParent).prev(".sub_tree_class").hide();
	});
}

function mark_update_pop_up_shown() {
	jQuery.post(ajaxurl, {
		action: 'plugin_update_notice_wptc',
	}, function (data) {
		//tb_remove();
	});
}

function add_notice_wptc(note, all_page) {
	var notice = '<div class="update-nag  notice is-dismissible" id="setting-error-tgmpa"> <p>' + note + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
	if (all_page) {
		jQuery('.wrap').before(notice);
	} else {
		jQuery('#wptc').before(notice);
	}
}


function load_custom_popup_wptc(show, type) {
	if (!show) {
		return false;
	}
	if (location.href.toLowerCase().indexOf('wp-time-capsule') === -1) {
		return false;
	}
	if (type == 'new_updates') {
		header = 'Check out our Latest feature - Backup before Update !';
		message = '<ul style="text-align: justify;"> <li>With the current release, we are wrapping up our free feature set :)</li><li><ul style="padding-left: 20px;"><li style="list-style: disc outside none; display: list-item; margin-left: 1em;">Automated Daily Backups.</li><li style="list-style: disc outside none; display: list-item; margin-left: 1em;">Backup Before Auto Update and Manual Update.</li></ul></li><li>Pro features are scheduled to be released by the End of January 2017.</li><li>Should you have any questions or concerns, please mail us to <a href="mailto:help@wptimecapsule.com">help@wptimecapsule.com</a></li></ul>';
		width = '570px';
		height = '270px';
	}

	jQuery('.notice, #update-nag').remove();
	var header_div = '<div class="theme-overlay" style="z-index: 1000;"><div class="theme-wrap wp-clearfix" style="width: ' + width + ';min-height: ' + height + ';left: 10px;">';
	var header_text = '<div class="theme-header"><button class="close dashicons dashicons-no"><span class="screen-reader-text">Close details dialog</span></button> <h3 style="text-align:center">' + header + '</h3></div>';
	var body_text = '<div class="theme-about wp-clearfix">' + message + '<div><span style="margin: 0px;top: 175px;position: absolute;"></span></div></div><div class="theme-actions"><div class="active-theme">     <a class="button button-secondary">Background</a></div><div class="inactive-theme"><a></a></div></div>';
	var dialog_content = header_div + header_text + body_text;
	setTimeout(function () {
		jQuery("#dialog_content_id").html(dialog_content);
		jQuery(".wptc-thickbox").click();
		styling_thickbox_tc('progress');
		mark_update_pop_up_shown();
	}, 2000);
}

function mainwp_start_manual_backup_wptc(obj, type, update_items, update_ptc_type, backup_before_update_always) {
	freshBackupWptc = '';
	// jQuery('.notice, #update-nag').remove();
	//tb_remove();
	if (type == 'from_bbu') {
		mainwp_backup_started_wptc = 'from_bbu';
		mainwp_wtc_start_backup_func('from_bbu', update_items, update_ptc_type, backup_before_update_always);
		return false;
	}
	if (jQuery(obj).attr("action") == 'start') {
		jQuery(obj).text('Starting backup...');
		mainwp_backup_started_wptc = 'from_setting';
		mainwp_wtc_start_backup_func('from_setting');
	} else if (jQuery(obj).attr("action") == 'stop') {
		jQuery(obj).text('Stopping backup...');
		mainwp_wtc_stop_backup_func();
	}
}


//function showed_processing_files_view_wptc(){
//	 jQuery.post(ajaxurl, {
//			action: 'show_processing_files_view_wptc',
//		}, function(data) {});
//}

function mainwp_test_connection_wptc_cron() {
	if (jQuery('.mainwp_test_cron_wptc').hasClass('disabled')) {
		return false;
	}
	jQuery('.cron_current_status').html('Waiting for response').css('color', '#444').parent().show();
	jQuery('.mainwp_test_cron_wptc').addClass('disabled').html('Connecting...');
	jQuery.post(ajaxurl, {
		action: 'mainwp_test_connection_wptc_cron',
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		try {
			var obj = jQuery.parseJSON(data);
			if (typeof obj.status != 'undefined' && obj.status == "success") {
				jQuery('#wptc_cron_status_failed').hide();
				jQuery('.mainwp_test_cron_wptc').removeClass('disabled').html('Test again');
				jQuery('#wptc_cron_status_passed').show();
				jQuery('.cron_current_status').html('Success').css('color', '');
			} else {
				//load_cron_status_failed_popup(obj.status, obj.err_msg, obj.cron_url, obj.ips, '');
				jQuery('#wptc_cron_status_failed').show();
				jQuery('.mainwp_test_cron_wptc').show();
				jQuery('.mainwp_test_cron_wptc').removeClass('disabled').html('Test again');
				jQuery('#wptc_cron_failed_note').html('Failed').css('color', '');
				jQuery('#wptc_cron_status_passed').hide();
			}
		} catch (e) {
			jQuery('.mainwp_test_cron_wptc').removeClass('disabled').html('Test again');
			jQuery('#wptc_cron_failed_note').html('Failed');
			return false;
		}
	});
}

function wptc_backup_start_failed_note(failed_backups) {
	jQuery("#start_backup_from_settings").attr('action', 'start').html('Backup now');
	jQuery("#backup_button_status_wptc").text("Click Backup Now to backup the latest changes.");
	tb_remove();
	var total_failed_count = jQuery(failed_backups).length;
	var backup_text = (total_failed_count > 1) ? 'backups have ' : 'backup has ';
	var note = 'The plugin is not able to communicate with the server hence backups have been stopped. This is applicable to manual , scheduled backups and Staging. The following ' + backup_text + ' been stopped due to lack of communication between the plugin and server.<br>';
	var backup_list = '';
	jQuery(failed_backups).each(function (index) {
		backup_list = backup_list + failed_backups[index] + "<br>";
	});
	note = note + backup_list;
	jQuery('.notice, #update-nag').remove();
	var notice = '<div class="update-nag  notice is-dismissible" id="setting-error-tgmpa"> <h4>WP Time Capsule</h4> <p>' + note + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>'
	jQuery('.wrap').before(notice);
}

function mainwp_login_wptc() {
	jQuery('#mainwp_wptc_error_div').hide();
	var type = jQuery('#mainwp_wptc_current_site_id').val() > 0 ? 'individual' : 'general';
	jQuery.post(ajaxurl, {
		action: 'mainwp_login_wptc',
		acc_email: jQuery("#wptc_main_acc_email").val(),
		acc_pwd: jQuery('#wptc_main_acc_pwd').val(),
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
		type: type,
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		var success = false;
		if (data != undefined) {
			if (type == 'general') {
				if (data.result && data.result == 'next_step') {
					mainwp_wptc_general_load_sites('logging');
					return;
				}
			} else if (data.result) {
				jQuery("#mainwp_wptc_login").removeAttr('disabled').removeClass('disabled').val("Successful !").html("Successful !");
				setTimeout(function () {
					location.assign('admin.php?page=ManageSitesWPTimeCapsule&tab=general&id=' + jQuery('#mainwp_wptc_current_site_id').val());
				}, 500);
				success = true;
			}

			if (data.error) {
				jQuery('#mainwp_wptc_error_div').html('<i class="red times icon"></i> ' + data.error).show();
			}
		}
		if (!success)
			jQuery("#mainwp_wptc_login").removeAttr('disabled').removeClass('disabled').val("Login").html("Login");
	}, 'json');
}

function enable_settings_button_wptc() {
	jQuery("#wptc_save_changes").removeAttr('disabled').removeClass('disabled').val("Save Changes").html("Save");
	jQuery('#exc_files_db_canc').css('color', '#0073aa').unbind('click', false);
}

function mainwp_save_settings_wptc() {

	var tab_name = jQuery("#mainwp_wptc_tab_name").val();

	var request_params = {};

	if (tab_name == 'backup') {

		var backup_slot = '';
		if (jQuery("#select_wptc_backup_slots").hasClass('disabled') === false) {
			var backup_slot = jQuery("#select_wptc_backup_slots").val();
		}

		var scheduled_time = '';
		if (jQuery("#select_wptc_default_schedule").hasClass('disabled') === false) {
			var scheduled_time = jQuery("#select_wptc_default_schedule").val();
		}

		var timezone = '';
		if (jQuery("#wptc_timezone").hasClass('disabled') === false) {
			var timezone = jQuery("#wptc_timezone").val();
		}

		var revision_limit = jQuery("#wptc-restore-window-slider").slider("value")
		var user_excluded_extenstions = jQuery("#user_excluded_extenstions").val();
		var user_excluded_files_more_than_size_status = jQuery('input[name=user_excluded_files_more_than_size_status]:checked').val() ? 'yes' : 'no';
		var user_excluded_files_more_than_size = jQuery("#user_excluded_files_more_than_size").val();
		var backup_db_query_limit = jQuery("#backup_db_query_limit").val();
		var database_encryption_status = jQuery('input[name=database_encryption_status]:checked').val() ? 'yes' : 'no';
		var database_encryption_key = jQuery("#database_encryption_key").val();

		if (database_encryption_status == 'yes' && !database_encryption_key) {
			enable_settings_button_wptc();
			return alert('Error: Enter Encryption Phrase.');
		}

		if (scheduled_time && timezone) {
			var request_params = {
				"backup_slot": backup_slot,
				"scheduled_time": scheduled_time,
				"timezone": timezone,
				"revision_limit": revision_limit,
				"user_excluded_extenstions": user_excluded_extenstions,
				"user_excluded_files_more_than_size_settings": { status: user_excluded_files_more_than_size_status, size: user_excluded_files_more_than_size },
				"backup_db_query_limit": backup_db_query_limit,
				"database_encryption_settings": { 'status': database_encryption_status, 'key': database_encryption_key }
			};
		} else {
			var request_params = {
				"revision_limit": revision_limit,
				"user_excluded_extenstions": user_excluded_extenstions,
				"user_excluded_files_more_than_size_settings": { status: user_excluded_files_more_than_size_status, size: user_excluded_files_more_than_size }
			};
		}

	} else if (tab_name == 'backup_auto') {

		var backup_before_update_setting = jQuery('#backup_before_update_always').is(":checked");

		var auto_update_wptc_setting = jQuery('input[name=auto_update_wptc_setting]:checked').val();
		var auto_updater_core_major = jQuery('input[name=wptc_auto_core_major]:checked').val();
		var auto_updater_core_minor = jQuery('input[name=wptc_auto_core_minor]:checked').val();
		var auto_updater_plugins = jQuery('input[name=wptc_auto_plugins]:checked').val();
		var auto_updater_plugins_included = jQuery('#auto_include_plugins_wptc').val();
		var auto_updater_themes = jQuery('input[name=wptc_auto_themes]:checked').val();
		var auto_updater_themes_included = jQuery('#auto_include_themes_wptc').val();
		var schedule_enabled = jQuery('input[name=wptc_auto_update_schedule_enabled]:checked').val();

		var schedule_time = '';

		if (jQuery("#wptc_auto_update_schedule_time").hasClass('disabled') === false) {
			var schedule_time = jQuery("#wptc_auto_update_schedule_time").val();
		}

		var request_params = {
			"backup_before_update_setting": backup_before_update_setting,
			"auto_update_wptc_setting": auto_update_wptc_setting,
			"auto_updater_core_major": (auto_updater_core_major) ? auto_updater_core_major : 0,
			"auto_updater_core_minor": (auto_updater_core_minor) ? auto_updater_core_minor : 0,
			"auto_updater_plugins": (auto_updater_plugins) ? auto_updater_plugins : 0,
			"auto_updater_plugins_included": (auto_updater_plugins_included) ? auto_updater_plugins_included : '',
			"auto_updater_themes": (auto_updater_themes) ? auto_updater_themes : 0,
			"auto_updater_themes_included": (auto_updater_themes_included) ? auto_updater_themes_included : '',
			"schedule_time": (schedule_time) ? schedule_time : '',
			"schedule_enabled": (schedule_enabled) ? schedule_enabled : '',
		}
	} else if (tab_name == 'vulns_update') {
		var enable_vulns_email_wptc = jQuery('input[name=enable_vulns_email_wptc]:checked').val();
		var vulns_wptc_setting = jQuery('input[name=vulns_wptc_setting]:checked').val();
		var wptc_vulns_core = jQuery('input[name=wptc_vulns_core]:checked').val();
		var wptc_vulns_plugins = jQuery('input[name=wptc_vulns_plugins]:checked').val();
		var wptc_vulns_themes = jQuery('input[name=wptc_vulns_themes]:checked').val();
		var vulns_include_themes_wptc = jQuery('#vulns_include_themes_wptc').val();
		var vulns_include_plugins_wptc = jQuery('#vulns_include_plugins_wptc').val();

		request_params = {
			"enable_vulns_email_wptc": enable_vulns_email_wptc,
			"vulns_wptc_setting": vulns_wptc_setting,
			"wptc_vulns_core": wptc_vulns_core,
			"wptc_vulns_plugins": wptc_vulns_plugins,
			"wptc_vulns_themes": wptc_vulns_themes,
			"vulns_themes_included": vulns_include_themes_wptc,
			"vulns_plugins_included": vulns_include_plugins_wptc,
		};
	} else if (tab_name == 'staging_opts') {
		var db_rows_clone_limit_wptc = jQuery("#db_rows_clone_limit_wptc").val();
		var files_clone_limit_wptc = jQuery("#files_clone_limit_wptc").val();
		var deep_link_replace_limit_wptc = jQuery("#deep_link_replace_limit_wptc").val();
		var reset_permalink_wptc = jQuery('input[name=reset_permalink_wptc]:checked').val();
		var enable_admin_login_wptc = jQuery('input[name=enable_admin_login_wptc]:checked').val();
		var login_custom_link_wptc = jQuery('#login_custom_link_wptc').val();

		request_params = {
			"db_rows_clone_limit_wptc": db_rows_clone_limit_wptc,
			"files_clone_limit_wptc": files_clone_limit_wptc,
			"deep_link_replace_limit_wptc": deep_link_replace_limit_wptc,
			"enable_admin_login_wptc": enable_admin_login_wptc,
			"reset_permalink_wptc": reset_permalink_wptc,
			"login_custom_link_wptc": login_custom_link_wptc,
		};
	}

	// save_settings_ajax_request_wptc()
	jQuery('#message_save_settings_wptc').hide();
	jQuery.post(ajaxurl, {
		action: 'mainwp_save_settings_wptc',
		data: request_params,
		tabName: tab_name,
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(), // 0 is general settings
		type: jQuery('#mainwp_wptc_current_site_id').val() > 0 ? 'individual' : 'general',
		nonce: mainwp_timecapsule_loc.nonce
	}, function (response) {
		if (response != undefined) {
			if (response.result == 'next_step') {
				mainwp_wptc_general_load_sites('save_settings', tab_name); // save general settings
			} else if (response.error) {
				jQuery('#message_save_settings_wptc').css('color', 'red');
				jQuery('#message_save_settings_wptc').html(response.error).show();
				jQuery("#wptc_save_changes").removeAttr('disabled').removeClass('disabled').val("Save Changes").html("Save");
			} else if (response.result) {
				jQuery("#wptc_save_changes").removeAttr('disabled').removeClass('disabled').val("Done !").html("Done !");
				setTimeout(function () {
					jQuery("#wptc_save_changes").removeAttr('disabled').removeClass('disabled').val("Save Changes").html("Save");
					jQuery('#exc_files_db_cancel').css('color', '#0073aa').unbind('click', false);
				}, 1000);
			}
		}
	}, 'json');
}

var timecapsule_bulkMaxThreads = 3;
var timecapsule_bulkCurrentThreads = 0;
var timecapsule_bulkTotalThreads = 0;
var timecapsule_bulkFinishedThreads = 0;


mainwp_wptc_general_load_sites = function (pWhat, pTabName) {

	var data = {
		action: 'mainwp_wptc_load_sites',
		what: pWhat,
		tabName: pTabName,
		nonce: mainwp_timecapsule_loc.nonce
	};

	var parent = jQuery('.mainwp-main-content');
	parent.html('<div class="ui hidden divider"></div><i class="notched circle loading icon"></i> Loading sites...');
	jQuery.post(ajaxurl, data, function (response) {
		if (response) {
			parent.html(response);
			timecapsule_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
			if (timecapsule_bulkTotalThreads > 0) {
				mainwp_wptc_general_actions_start_next(pWhat);
			}
		} else {
			parent.html('<i class="red times icon"></i> ' + __("Undefined error."));

		}
	})
}

mainwp_wptc_general_actions_start_next = function (pWhat) {
	while ((objProcess = jQuery('.siteItemProcess[status=queue]:first')) && (objProcess.length > 0) && (timecapsule_bulkCurrentThreads < timecapsule_bulkMaxThreads)) {
		objProcess.attr('status', 'processed');
		if (pWhat == 'start_backup') {
			mainwp_wptc_general_start_backup_start_specific(objProcess, pWhat);
		} else if (pWhat == 'test_communication') {
			mainwp_wptc_test_communication_start_specific(objProcess, pWhat);
		} else if (pWhat == 'save_settings') {
			mainwp_wptc_save_general_settings_start_specific(objProcess, pWhat);
		} else if (pWhat == 'logging') {
			mainwp_wptc_logging_start_specific(objProcess, pWhat);
		} else if (pWhat == 'sync_purchase') {
			mainwp_wptc_sync_purchase_start_specific(objProcess, pWhat);
		}
	}
	if (timecapsule_bulkFinishedThreads > 0 && timecapsule_bulkFinishedThreads == timecapsule_bulkTotalThreads) {
		jQuery('.mainwp-main-content').append(__("Finished.") + '<p><a class="ui button green" href="admin.php?page=Extensions-Mainwp-Timecapsule-Extension&tab=general">Return the Settings</a></p>');
	}
}

mainwp_wptc_general_start_backup_start_specific = function (objProcess, pWhat) {
	var statusEl = objProcess.find('.status');
	statusEl.html('<i class="notched circle loading icon"></i>');

	timecapsule_bulkCurrentThreads++;
	jQuery.post(ajaxurl, {
		action: 'mainwp_start_fresh_backup_tc_wptc',
		type: 'manual',
		is_auto_update: 0,
		is_staging_req: 0,
		timecapsuleSiteID: objProcess.attr('site-id'),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (response) {
		if (response) {
			if (response.error) {
				statusEl.html('<i class="red times icon"></i> ' + response.error);
			} else if (response.result == 'success') {
				statusEl.html('<i class="green check icon"></i> ' + __('Successful, starting backup...'));
			} else {
				statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
			}
		} else {
			statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
		}
		timecapsule_bulkCurrentThreads--;
		timecapsule_bulkFinishedThreads++;
		mainwp_wptc_general_actions_start_next(pWhat);
	}, 'json');
}
mainwp_wptc_test_communication_start_specific = function (objProcess, pWhat) {
	var statusEl = objProcess.find('.status');
	statusEl.html('<i class="notched circle loading icon"></i>');

	timecapsule_bulkCurrentThreads++;
	jQuery.post(ajaxurl, {
		action: 'mainwp_test_connection_wptc_cron',
		timecapsuleSiteID: objProcess.attr('site-id'),
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		try {
			var obj = jQuery.parseJSON(data);
			if (obj.error) {
				statusEl.html('<i class="red times icon"></i> ' + obj.error);
			} else if (typeof obj.err_msg != 'undefined') {
				statusEl.html(obj.err_msg);
			} else if (typeof obj.status != 'undefined' && obj.status == "success") {
				statusEl.html('<i class="green check icon"></i> ' + __('Successful.'));
			} else {
				statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
			}
		} catch (e) {
			statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
		}
		timecapsule_bulkCurrentThreads--;
		timecapsule_bulkFinishedThreads++;
		mainwp_wptc_general_actions_start_next(pWhat);
	});
}

mainwp_wptc_logging_start_specific = function (objProcess, pWhat) {
	var statusEl = objProcess.find('.status');
	statusEl.html('<i class="notched circle loading icon"></i>');

	timecapsule_bulkCurrentThreads++;

	jQuery.post(ajaxurl, {
		action: 'mainwp_login_wptc',
		timecapsuleSiteID: objProcess.attr('site-id'),
		type: 'general',
		nonce: mainwp_timecapsule_loc.nonce,
		step: 'general_login'
	}, function (data) {
		if (data) {
			if (data.error) {
				statusEl.html('<i class="red times icon"></i> ' + data.error);
			} else if (data.result) {
				statusEl.html('<i class="green check icon"></i> ' + __('Successful.'));
			} else {
				statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
			}
		} else {
			statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
		}
		timecapsule_bulkCurrentThreads--;
		timecapsule_bulkFinishedThreads++;
		mainwp_wptc_general_actions_start_next(pWhat);
	}, 'json');
}

mainwp_wptc_sync_purchase_start_specific = function (objProcess, pWhat) {
	var statusEl = objProcess.find('.status');
	statusEl.html('<i class="notched circle loading icon"></i>');

	timecapsule_bulkCurrentThreads++;
	jQuery.post(ajaxurl, {
		action: 'mainwp_wptc_sync_purchase',
		timecapsuleSiteID: objProcess.attr('site-id'),
		type: 'general',
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		var err = false;
		if (data) {
			if (data.error) {
				err = true;
				statusEl.html('<i class="red times icon"></i> ' + data.error);
			}
		}

		if (!err) {
			statusEl.html('<i class="green check icon"></i> ' + __('Successful.'));
		}
		timecapsule_bulkCurrentThreads--;
		timecapsule_bulkFinishedThreads++;
		mainwp_wptc_general_actions_start_next(pWhat);
	}, 'json');
}


mainwp_wptc_save_general_settings_start_specific = function (objProcess, pWhat) {
	var statusEl = objProcess.find('.status');
	statusEl.html('<i class="notched circle loading icon"></i>');

	timecapsule_bulkCurrentThreads++;
	jQuery.post(ajaxurl, {
		action: 'mainwp_save_settings_wptc_general',
		timecapsuleSiteID: objProcess.attr('site-id'),
		tabName: jQuery('#mainwp_wptc_saving_tab_settings').val(),
		type: 'general',
		nonce: mainwp_timecapsule_loc.nonce
	}, function (data) {
		if (data) {
			if (data.error) {
				statusEl.html('<i class="red times icon"></i> ' + data.error);
			} else if (data.result) {
				statusEl.html('<i class="green check icon"></i> ' + __('Successful.'));
			} else {
				statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
			}
		} else {
			statusEl.html('<i class="red times icon"></i> ' + 'Undefined error');
		}
		timecapsule_bulkCurrentThreads--;
		timecapsule_bulkFinishedThreads++;
		mainwp_wptc_general_actions_start_next(pWhat);
	}, 'json');
}



function resume_backup_wptc() {
	jQuery(".resume_backup_wptc").addClass("disabled").attr('style', 'cursor:auto; color:gray').text('Reconnecting...');
	jQuery.post(ajaxurl, {
		action: 'resume_backup_wptc',
	}, function (data) {

		if (data != undefined) {
			try {
				var obj = jQuery.parseJSON(data);
				if (typeof obj.status != 'undefined' && obj.status == "success") {
					jQuery(".resume_backup_wptc").text('Backup Resumed').removeAttr('style').attr('style', 'color:green;');
					setTimeout(function () {
						jQuery("#wptc_cron_status_div").show();
						jQuery("#wptc_cron_status_paused").hide();
					}, 1000);
				} else {
					//load_cron_status_failed_popup(obj.status, obj.err_msg, obj.cron_url, obj.ips, '');
					jQuery(".resume_backup_wptc").text('Failed to resume backup');
					setTimeout(function () {
						jQuery(".resume_backup_wptc").removeClass("disabled").removeAttr('style').attr('style', 'cursor:pointer;').text('Resume backup');
					}, 1000);
				}
			} catch (e) {
				jQuery(".resume_backup_wptc").text('Failed to resume backup');
				setTimeout(function () {
					jQuery(".resume_backup_wptc").removeClass("disabled").removeAttr('style').attr('style', 'cursor:pointer;').text('Resume backup');
				}, 1000);
				return false;
			}
		}
	});
}

function basename_wptc(path) {
	return path.split('/').reverse()[0];
}

function change_init_setup_button_state() {
	jQuery("#file_db_exp_for_exc_view").toggle();
	jQuery(".view-user-exc-extensions").toggle();
	jQuery("#wptc_init_toggle_tables").click();
	jQuery("#wptc_init_toggle_files").click();
}

function convert_bytes_to_hr_format(size) {
	if (1024 > size) {
		return size + ' B';
	} else if (1048576 > size) {
		return ((size / 1024)).toFixed(2) + ' KB';
	} else if (1073741824 > size) {
		return ((size / 1024) / 1024).toFixed(2) + ' MB';
	} else if (1099511627776 > size) {
		return (((size / 1024) / 1024) / 1024).toFixed(2) + ' GB';
	}
}

function mainwp_save_manual_backup_name_wptc(name) {
	var site_id = jQuery('#mainwp_wptc_current_site_id').val()
	jQuery.post(ajaxurl, {
		action: 'mainwp_save_manual_backup_name_wptc',
		timecapsuleSiteID: site_id,
		nonce: mainwp_timecapsule_loc.nonce,
		name: name,
	}, function (data) {
		console.log(data)
		var obj = jQuery.parseJSON(data);
		if (obj.status && obj.status == 'success') {
			jQuery("#backup_custom_name_model").css('height', '88px');
			jQuery("#manual_backup_name_div").text('Backup name saved :-)').css({ 'color': 'green', 'padding-top': '36px' });
			setTimeout(function () {
				jQuery("#manual_backup_name_div").hide();
				tb_remove();
				if (typeof mainwp_backup_started_wptc != 'undefined' && mainwp_backup_started_wptc == 'from_setting') {
					var url = '';
					if (site_id) {
						url = 'admin.php?page=ManageSitesWPTimeCapsule&id=' + site_id;
					} else {
						url = 'admin.php?page=Extensions-Mainwp-Timecapsule-Extension';
					}
					location.assign(adminUrlWptc + url);
				}
			}, 3000);
		}
	});
}

function remove_other_thickbox_wptc() {
	jQuery('.thickbox').each(function (index) {
		if (!jQuery(this).hasClass("wptc-thickbox") && !jQuery(this).hasClass("open-plugin-details-modal")) {
			jQuery(this).remove();
		}
	});
}

function gettime_wptc() {
	var d = new Date();
	var month = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	var date = d.getDate() + " " + month[d.getMonth()];
	var nowHour = d.getHours();
	var nowMinutes = d.getMinutes();
	var nowSeconds = d.getSeconds();
	var suffix = nowHour >= 12 ? "PM" : "AM";
	nowHour = (suffix == "PM" & (nowHour > 12 & nowHour < 24)) ? (nowHour - 12) : nowHour;
	nowHour = nowHour == 0 ? 12 : nowHour;
	nowMinutes = nowMinutes < 10 ? "0" + nowMinutes : nowMinutes;
	nowSeconds = nowSeconds < 10 ? "0" + nowSeconds : nowSeconds;
	var currentTime = nowHour + ":" + nowMinutes + ":" + nowSeconds + ' ' + suffix;
	return date + ' ' + currentTime;
}
