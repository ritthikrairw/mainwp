jQuery(document).ready(function($) {

    mainwp_get_current_backup_status_wptc();

	//restart staging process
	is_staging_need_request_wptc();


	jQuery('body').on('click', '#yes_delete_site', function (){
		delete_staging_site_wptc();
	});

	jQuery('body').on('click', '#delete_staging, #no_delete_site', function (){
		jQuery("#staging_delete_options").toggle();
	});

	jQuery('body').on('click', '#edit_staging_wptc', function (){

		if(jQuery(this).hasClass('disabled')){
			return false;
		}

		choose_staging_wptc(true);
	});

	jQuery('body').on('click', '#ask_copy_staging_wptc', function (e){
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}

		swal({
			title              : mainwp_wptc_get_dialog_header('Copy live to staging?'),
			html               : mainwp_wptc_get_dialog_body('Clicking on Yes will continue to copy your live site to staging site. Are you sure want to continue ?', ''),
			padding            : '0px 0px 10px 0',
			buttonsStyling     : false,
			showCancelButton   : true,
			confirmButtonColor : '',
			cancelButtonColor  : '',
			confirmButtonClass : 'button-primary wtpc-button-primary',
			cancelButtonClass  : 'button-secondary wtpc-button-secondary',
			confirmButtonText  : 'Yes',
			cancelButtonText   : 'Cancel',
			}).then(function () {
				select_copy_staging_type_wptc(true);
			}, function (dismiss) {

			}
		);
	});

	jQuery('body').on('click', '#copy_staging_wptc', function (e){
		select_copy_staging_type_wptc(true);
	});

	jQuery('body').on('click', '#stop_staging_wptc', function (e){


		swal({
			title              : mainwp_wptc_get_dialog_header('Stop staging process?'),
			html               : mainwp_wptc_get_dialog_body('Clicking on Yes will delete your current staging site. Are you sure want to continue ?', ''),
			padding            : '0px 0px 10px 0',
			buttonsStyling     : false,
			showCancelButton   : true,
			confirmButtonColor : '',
			cancelButtonColor  : '',
			confirmButtonClass : 'button-primary wtpc-button-primary',
			cancelButtonClass  : 'button-secondary wtpc-button-secondary',
			confirmButtonText  : 'Yes',
			cancelButtonText   : 'Cancel',
		}).then(function () {
			wptc_stop_staging_confirmed();
		});

	});

	jQuery('body').on('click', '#refresh-s-status-area-wtpc', function (e){
		if (jQuery(this).hasClass('disabled')) {
			return false;
		}
		get_staging_details_wptc();
	});


	jQuery("body").on("click", ".upgrade-plugins-staging-wptc" ,function(e) {
		handle_plugin_upgrade_request_wptc(this, e , false , true, 'plugin');
	});

	jQuery("body").on("click", ".update-link-plugins-staging-wptc, .update-now-plugins-staging-wptc" ,function(e) {
		handle_plugin_themes_link_request_wptc(this, e, false, true , 'plugin');
	});

	jQuery("body").on("click", ".update-link-themes-staging-wptc, .update-now-themes-staging-wptc" ,function(e) {
		handle_plugin_themes_link_request_wptc(this, e, false, true , 'theme');
	});

	jQuery("body").on("click", ".button-action-plugins-staging-wptc" ,function(e) {
		handle_plugin_themes_button_action_request_wptc(this, e , false, true, 'plugin');
	});

	jQuery("body").on("click", ".button-action-themes-staging-wptc" ,function(e) {
		handle_plugin_themes_button_action_request_wptc(this, e , false, true, 'theme');
	});

	jQuery("body").on("click", ".upgrade-themes-staging-wptc" ,function(e) {
		handle_themes_upgrade_request_wptc(this, e , false, true);
	});

	jQuery("body").on("click", ".upgrade-core-staging-wptc" ,function(e) {
		handle_core_upgrade_request_wptc(this, e , false, true);
	});

	jQuery("body").on("click", ".upgrade-translations-staging-wptc" ,function(e) {
		handle_translation_upgrade_request_wptc(this, e , false, true);
	});

	jQuery('body').on("click", '.plugin-update-from-iframe-staging-wptc', function(e) {
		handle_iframe_requests_wptc(this, e , false, true);
	});

	jQuery('body').on("click", '#same_server_submit_wptc', function(e) {
		jQuery('#internal_staging_error_wptc').html('');
		if(jQuery(this).hasClass('disabled')){
			return false;
		}

		jQuery(this).addClass('disabled');
		jQuery(this).val('Processing...');
		var path = jQuery('#same_server_path_staging_wptc').val();
		if(path.length < 1){
			jQuery('#internal_staging_error_wptc').html('Error : Staging path cannot be empty.');
			jQuery('#same_server_submit_wptc').val('Start Staging').removeClass('disabled');
			return false;
		}

		wptc_start_staging(path);

	});

	jQuery('body').on("click", '#select_same_server_wptc', function(e) {
		if(jQuery(this).hasClass('disabled')){
			return false;
		}
		same_server_wptc();
	});

});

function wptc_stop_staging_confirmed(){
	if (jQuery('#stop_staging_wptc').hasClass('disabled')) {
		return false;
	}
	jQuery('#stop_staging_wptc').val('Stopping...').addClass('disabled');
	stop_staging_wptc();
	bp_in_progress = false;
}

function is_staging_need_request_wptc(){
    if (jQuery('#mainwp_wptc_current_site_id').val() == 0) {
        console.log('Error: Site id empty');
        return;
    }

	jQuery.post(ajaxurl, {
		action: 'mainwp_is_staging_need_request_wptc',
        timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
		dataType: "json",
	}, function(data) {

		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			console.log(err);
			return ;
		}

		if(typeof data == 'undefined' || typeof data.status == 'undefined' || typeof data.check_again == 'undefined'){
			return false;
		}

		if(data.status) {
			get_staging_details_wptc();
			return false;
		}
		if(data.check_again){
			if (typeof is_staging_need_request_var_wptc != 'undefined') {
				delete is_staging_need_request_var_wptc;
			}

			is_staging_need_request_var_wptc = setTimeout(function(){
				is_staging_need_request_wptc();
			}, 10000)

			get_staging_details_wptc(true);

			return false;
		}

		if (typeof is_staging_need_request_var_wptc != 'undefined') {
			delete is_staging_need_request_var_wptc;
		}

		get_staging_details_wptc(true);

	});
}

function wptc_start_staging(path){
	jQuery.post(ajaxurl, {
		action: 'mainwp_start_fresh_staging_wptc',
        timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
		path: path,
		dataType: "json",
	}, function(data) {
		wptc_process_init_request(data);
	});
}

function wptc_staging_in_progress(){
	if(jQuery('.wptc_prog_wrap_staging').length != 0){
		return ;
	}

	remove_prev_activity_ui();

	var html = wptc_staging_in_progress_template();
	jQuery('#staging_area_wptc').after(html);

	jQuery('#stop_staging_wptc').val('Stop and clear staging');

	wptc_get_staging_url();

}


function wptc_continue_staging(){
	jQuery.post(ajaxurl, {
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
		action: 'mainwp_continue_staging_wptc',
		dataType: "json",
	}, function(data) {
		console.log('wptc_continue_staging');
		console.log(data);
        forced_update_clone_site_wptc = true;
		jQuery('#same_server_submit_wptc').val('Start Staging').removeClass('disabled');
		try{
			var data = jQuery.parseJSON(data);
			if(typeof data.percentage != 'undefined' && data.percentage){
				jQuery('.staging_progress_bar').css('width', data.percentage+'%');
			}
			if(data.status === 'continue'){
				jQuery("#staging_progress_bar_note").html(data.msg);
				wptc_continue_staging();
			} else if(data.status === 'error'){
				jQuery("#staging_progress_bar_note").html('Error: '+ data.msg);
			} else if(data.status === 'success'){
				get_staging_details_wptc();
				console.log(data);
				if (data.is_restore_to_staging) {
					startRestore(null, null, null, null, true);
				}
			} else {
				jQuery("#staging_progress_bar_note").html('Error: Something went wrong, please refresh the page to resume process.').css('color', '#ca4a1f');
			}
		} catch(err){
			jQuery("#staging_progress_bar_note").html('Error: Something went wrong, please refresh the page to resume process.').css('color', '#ca4a1f');
			console.log(err);
		}
	}). fail(function(request) {
		console.log(request);
		get_staging_current_status_key_wptc(request.status, request.statusText);
	});
}

function wptc_process_init_request(data){
	jQuery('#same_server_submit_wptc').val('Start Staging').removeClass('disabled');
	try{
		var data = jQuery.parseJSON(data);
		if(data.status === 'continue'){
			if (typeof wptc_redirect_to_staging_page !== 'undefined' && jQuery.isFunction(wptc_redirect_to_staging_page)) {
				wptc_redirect_to_staging_page();
			}
			wptc_staging_in_progress();
			wptc_continue_staging();
		} else if(data.status === 'error'){
			jQuery('#internal_staging_error_wptc').html('Error: '+ data.msg);
		} else {
			jQuery('#internal_staging_error_wptc').html('Error: Something went wrong, try again.');
		}
	} catch(err){
		console.log(err);
		// alert("Cannot make ajax calls");
		jQuery('#internal_staging_error_wptc').html('Error: Something went wrong, try again.');
	}
}

function get_staging_current_status_key_wptc(status, statusText){
		jQuery.post(ajaxurl, {
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
		action: 'mainwp_get_staging_current_status_key_wptc',
		dataType: "json",
	}, function(data) {
		console.log(data);
		jQuery('#same_server_submit_wptc').val('Start Staging').removeClass('disabled');
		try{
			var data = jQuery.parseJSON(data);
			jQuery("#staging_progress_bar_note").html('Error : ' + status + ' ('+ statusText + '). ' + data.msg).css('color', '#ca4a1f');
		} catch(err){
			console.log(err);
			jQuery("#staging_progress_bar_note").html('Unknown error, email us at <a href="mailto:help@wptimecapsule.com?Subject=Contact" target="_top">help@wptimecapsule.com</a> ').css('color', '#ca4a1f');
		}
	}). fail(function(request) {
		console.log(request);
		jQuery("#staging_progress_bar_note").html('Unknown error, email us at <a href="mailto:help@wptimecapsule.com?Subject=Contact" target="_top">help@wptimecapsule.com</a> ').css('color', '#ca4a1f');
	});
}

function choose_staging_wptc(){
	//its not a staging page
//	if(window.location.href.indexOf('wp-time-capsule-staging-options') === -1){
//		return ;
//	}

	remove_prev_activity_ui();
	jQuery('#dashboard_activity').remove();

	var html = same_server_template_wptc();

	jQuery('#staging_area_wptc').after(html);
}

function same_server_wptc(){
	remove_prev_activity_ui();
	var template = same_server_template_wptc();
	jQuery('#staging_area_wptc').after(template);
}


function same_server_template_wptc(){
	var head_div = '<div id="dashboard_activity">';
	var title = '<h3 class="ui dividing header">Staging Setup</h3>';
	var content = '<div class="stage-on-the-server"></div>';
	var input = '<div class="ui grid field"><label class="six wide column middle aligned">Staging Path</label><div class="ten wide column"><div class="ui labeled input"><div class="ui label">' + get_home_url_wptc() + '</div><input id="same_server_path_staging_wptc" type="text" value="staging" class="staging-path-input-wptc"><div id="internal_staging_error_wptc"></div></div></div></div>';
	var button = '<div class="ui divider"></div><input id="same_server_submit_wptc" type="submit" value="Start Staging" class="ui green big right floating button">';
	var footer = '';
	return head_div+title+content+input+button+footer;
}

function choose_staging_template_wptc(){
	var head_div = '<div id="dashboard_activity" class="ui segment" >';
	var title = '<h3 class="ui dividing header">Staging Setup</h3>';
	var border = '<div class="staging-border-wptc" style="position: relative; left: 49%;"></div>';
	var inside_block_start = '<div style="position: relative;">';
	var same_server_content = '<div class="staging-same-server-block" style="position: absolute;top: -160px;left: 88px;"><span class="stage-on-the-server">Stage on the same server</span><div class="staging-recommended">(Recommended)</div><input id="select_same_server_wptc" type="submit" value="Stage Now" style="position: absolute; margin: 50px 0px 0px -146px;width: 140px;" class="button-primary"><div class="staging-speed-note">Faster!</div></div>';
	var diff_server_content = '<div class="staging-different-server-block" style="position: absolute;top: -160px;right: 91px;"><span class="stage-on-the-server">Stage on different server</span><input id="select_different_server_wptc" type="submit" value="Stage Now" style="margin: 50px 0px 0px -146px;width: 140px; position: absolute;" class="button-primary"><div class="staging-speed-note">Slower...</div></div>';
	var inside_block_end = '</div>';
	var footer = '';
	return head_div+title+border+inside_block_start+same_server_content+diff_server_content+inside_block_end+footer;
}

function get_home_url_wptc() {
  var childUrl = jQuery('#mainwp_wptc_current_site_url').val();
  return childUrl;
}


function wptc_staging_in_progress_template(){
	var header = '<div id="dashboard_activity" style="width: 700px;margin: 20px 0px 0px 0px;"> <span class="title-bar-staging-wptc"><span style="position: relative;">Staging Progress</span><input id="stop_staging_wptc" type="submit" class="ui green button" value="Stop Staging" style="float:right;position: relative;bottom: 11px;right: 19px;display:block"><span style="margin-left: 15px;position: relative;bottom: 8px;float: right;right: 35px; display:none" id="staging_err_retry"><a style="cursor: pointer;text-decoration: underline; font-size: 14px; float: right;">Try again</a></span></span><div style="width: 500px; height: 180px;">';
	var inside = '<div class="l1" style="margin: 0px 0px 10px 100px;text-align: center;width: 100%;position: relative;top: 15px;">Your site will be staged to <span class="staging_completed_dest_url"> </span></div> <div style="min-height: 40px;background: #fef4f4;border-left: 5px solid #e82828;width: 330px;position: absolute;left: 102px;top: 21px; display:none"><span style="position: relative;left: 5px;top: 10px;word-break: break-word;">Error: Folder Paths mismatch</span></div> <div class="l1 wptc_prog_wrap_staging" style=" top: 40px;position: relative; margin: 0px 0px 0px 90px; width: 100% !important;"><div class="staging_progress_bar_cont"><span id="staging_progress_bar_note">Syncing changes</span><div class="staging_progress_bar" style="width:0%"></div></div></div>';
	var footer = '<div class="l1" style="position: relative;top: 90px;text-align: center;left: 100px;">Note : Please do not close this tab until restore completes.</div></div></div><?php';
	var final_html = header + inside + footer;
	return final_html;
}

function wptc_staging_completed(completed_time, destination_url){
	remove_prev_activity_ui();
	var html = wptc_staging_completed_template();
	jQuery('#staging_area_wptc').after(html);
	jQuery("#staging_completed_time").html(completed_time);
	jQuery(".staging_completed_dest_url").html("<a href='"+destination_url+"' target='_blank'>"+destination_url+"</a>");
	wptc_disable_staging_button_after_dom_loaded('staging_completed');
	wptc_request_staging_tool_tip('staging_completed');
}

function wptc_staging_completed_template(){
	var header = '<div id="dashboard_activity" style="margin: 40px 20px 30px 150px;"><div style="margin: 0px 0px 10px 0px;"><strong>Staging status:</strong><span style="color:#0aa018;margin-left: 10px;">Successfully Completed !</span> </div>';
	var staging_detail =	'<div style="margin: 0px 0px 10px 0px;">Last staging was taken on <span id="staging_completed_time">Jun 24, 2016 @7:34PM</span>. Access it here <span class="staging_completed_dest_url"> </span></div>';
	var push_to_live =	'<div style="margin: 0px 0px 10px 0px;"> <a href="http://docs.wptimecapsule.com/article/28-how-push-staging-site-to-production" target="_blank"> How do I push my staging changes to the live site? </a></div>';
	var staging_options = '<div style="margin: 0px 0px 10px 0px; position:relative"><a class="wptc_link" id="edit_staging_wptc">Edit Staging</a> | <a class="wptc_link" style="color: #e95d5d;" id="delete_staging">Delete</a>';
	var copy_staging = '<div id="staging_delete_options" style="top: 0px;position: absolute;left: 190px; display:none">Are you sure you want to delete the staging site? <span id="delete_staging_progress"><a style="color: #e95d5d;" class="wptc_link" id="yes_delete_site">Yes</a></span> | <span><a class="wptc_link" id="no_delete_site">No</a></div> </div> <div style="margin: 0px 0px 10px 0px;"><a id="ask_copy_staging_wptc" class="ui green button hide-if-no-customize">Copy site from live to stage</a></div> </div>';
	return header + staging_detail + push_to_live + staging_options + copy_staging;
}

function stop_staging_wptc(){
	jQuery.post(ajaxurl, {
		action: 'mainwp_stop_staging_wptc',
        timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
	}, function(data){

	});
	setTimeout(function(){
		get_staging_details_wptc();
	}, 35000)
}

function wptc_get_staging_url(){

	jQuery.post(ajaxurl, {
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
		action: 'mainwp_get_staging_url_wptc',
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
			jQuery(".staging_completed_dest_url").html(' '+data.destination_url);
		} catch(err){
			//
		}
	});
}

function delete_staging_site_wptc(){
	jQuery('#staging_delete_options').html('Removing database and files...');
	jQuery.post(ajaxurl, {
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
		action: 'mainwp_delete_staging_wptc',
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return ;
		}
		if (data == undefined || !data) {
			jQuery('#staging_current_progress').html('I cannot work without data');
			return false;
		}
		if (data.status === 'success') {
			jQuery('#staging_delete_options').addClass('success_wptc');
			if (data.deleted === 'both') {
				jQuery('#staging_delete_options').html('Staging site deleted completely !');
			} else if (data.deleted === 'files') {
				jQuery('#staging_delete_options').html('Files deleted completely but we cannot delete database !');
			} else if (data.deleted === 'db') {
				jQuery('#staging_delete_options').html('Database deleted completely but we cannot delete files !');
			} else {
				jQuery('#staging_delete_options').removeClass('.success_wptc').addClass('error_wptc');
				jQuery('#staging_delete_options').html('We could not delete staging site, please do it manually');
			}
			setTimeout(function(){
				parent.location.assign(parent.location.href);
			}, 3000);
		} else {
			jQuery('#staging_delete_options').addClass('error_wptc');
			jQuery('#staging_delete_options').html('We could not delete staging site, please do it manually');
		}
	});
}

function remove_prev_activity_ui(){
	//if(window.location.href.indexOf('wp-time-capsule-staging-options') !== -1){
		jQuery('#dashboard_activity').remove();
	//}
}
var forced_update_clone_site_wptc = false;

function get_staging_details_wptc(do_not_continue_staging){

	continue_staging_wptc = true;

	if(do_not_continue_staging){
		continue_staging_wptc = false;
	}

	console.log('continue_staging_wptc' , continue_staging_wptc);

	jQuery.post(ajaxurl, {
		action: 'mainwp_get_staging_details_wptc',
        timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        forced_update: forced_update_clone_site_wptc,
        nonce: mainwp_timecapsule_loc.nonce,
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return ;
		}

		if (!data) {
			choose_staging_wptc(true);
			wptc_request_staging_tool_tip('not_staged_yet');
			wptc_disable_staging_button_after_dom_loaded('not_staged_yet');
			return ;
		}

		if (typeof data.is_running != 'undefined' && data.is_running) {
			wptc_request_staging_tool_tip('staging_running');
			wptc_staging_in_progress();
			if(continue_staging_wptc){
				// console.log('staging_continued');
				wptc_continue_staging();
			}
			return ;
		}

		if( (typeof data.destination_url != 'undefined' && data.destination_url ) && (typeof data.human_completed_time != 'undefined' && data.human_completed_time ) ){
			wptc_staging_completed(data.human_completed_time, data.destination_url);
			return ;
		}

		choose_staging_wptc(true);
		wptc_request_staging_tool_tip('not_staged_yet');
		wptc_disable_staging_button_after_dom_loaded('not_staged_yet');
		if (typeof continue_staging_wptc != 'undefined') {
			delete continue_staging_wptc;
		}
	});
}

function wptc_request_staging_tool_tip(type){
	if (wptc_is_backup_running()) {
		return add_tool_tip_staging_wptc('backup_progress');
	}

	add_tool_tip_staging_wptc(type);
}

function push_staging_button_wptc(){
	var extra_class = '';
	if(typeof staging_status_wptc == 'undefined' || staging_status_wptc === false){
		var extra_class = 'disabled button-disabled-staging-4-wptc';
	} else if(staging_status_wptc == 'progress' || staging_status_wptc == 'error'){
		var extra_class = 'disabled button-disabled-staging-1-wptc';
	} else if(staging_status_wptc == 'not_started'){
		var extra_class = 'disabled button-disabled-staging-2-wptc';
	} else if(staging_status_wptc == 'backup_progress'){
		var extra_class = 'disabled button-disabled-staging-3-wptc';
	}

	var current_path = window.location.href;
	if (current_path.toLowerCase().indexOf('update-core') !== -1) {

		if (!wptc_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.upgrade-plugins-staging-wptc, .upgrade-themes-staging-wptc, .upgrade-translations-staging-wptc, .upgrade-core-staging-wptc, .plugin-update-from-iframe-staging-wptc').remove();
		var update_plugins = '&nbsp; <input class="upgrade-plugins-staging-wptc button '+extra_class+'" type="submit" value="Update in staging">';
		var update_themes = '&nbsp; <input class="upgrade-themes-staging-wptc button  '+extra_class+'" type="submit" value="Update in staging">';
		var update_translations = '&nbsp;<input class="upgrade-translations-staging-wptc button  '+extra_class+'" type="submit" value="Update in staging">';
		var update_core = '&nbsp;<input type="submit" class="upgrade-core-staging-wptc button button regular  '+extra_class+'" value="Update in staging">';
		var iframe_update = '<a class="plugin-update-from-iframe-staging-wptc button button-primary right  '+extra_class+'" style=" margin-right: 10px;">Update in staging</a>';
		jQuery('form[name=upgrade-plugins]').find('input[name=upgrade]').after(update_plugins);
		jQuery('form[name=upgrade-themes]').find('input[name=upgrade]').after(update_themes);
		jQuery('form[name=upgrade]').find('input[name=upgrade]').after(update_core);
		jQuery('form[name=upgrade-translations]').find('input[name=upgrade]').after(update_translations);
		setTimeout(function(){
			jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wptc").remove();
			jQuery("#TB_iframeContent").contents().find("#plugin_update_from_iframe").after(iframe_update);
			if(jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wptc").length > 0){
				add_tool_tip_staging_wptc();
			}
		}, 5000);
	} else if(current_path.toLowerCase().indexOf('plugins.php') !== -1){

		if (!wptc_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.wptc-span-spacing-staging , .update-link-plugins-staging-wptc , .button-action-plugins-staging-wptc').remove();
		var in_app_update = '<span class="wptc-span-spacing-staging">&nbsp;or</span> <a href="#" class="update-link-plugins-staging-wptc  '+extra_class+'">Update in staging</a>';
		var selected_update = '<span class="wptc-span-spacing-staging">&nbsp</span><input type="submit" class="button-action-plugins-staging-wptc button  '+extra_class+'" value="Update in staging">';
		var iframe_update = '<a class="plugin-update-from-iframe-staging-wptc button button-primary right  '+extra_class+'" style=" margin-right: 10px;">Update in staging</a>';
		jQuery('form[id=bulk-action-form]').find('.update-link').after(in_app_update);
		jQuery('form[id=bulk-action-form]').find('.button.action').after(selected_update);
		setTimeout(function(){
			jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wptc").remove();
			jQuery("#TB_iframeContent").contents().find("#plugin_update_from_iframe").after(iframe_update);
			if(jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wptc").length > 0){
				add_tool_tip_staging_wptc();
			}
			add_tool_tip_staging_wptc();
		}, 5000);
	} else if(current_path.toLowerCase().indexOf('plugin-install.php') !== -1){

		if (!wptc_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.update-now-plugins-staging-wptc, .plugin-update-from-iframe-staging-wptc').remove();
		var in_app_update = '<li><a class="button update-now-plugins-staging-wptc '+extra_class+'" href="#">Update in staging</a></li>';
		var iframe_update = '<a class="plugin-update-from-iframe-staging-wptc button button-primary right  '+extra_class+'" style=" margin-right: 10px;">Update in staging</a>';
		setTimeout(function(){
			jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wptc").remove();
			jQuery("#TB_iframeContent").contents().find("#plugin_update_from_iframe").after(iframe_update);
			add_tool_tip_staging_wptc();
			if(jQuery("#TB_iframeContent").contents().find(".plugin-update-from-iframe-staging-wptc").length > 0){
				add_tool_tip_staging_wptc();
			}
		}, 5000);
		jQuery('.plugin-action-buttons .update-now.button').parents('.plugin-action-buttons').append(in_app_update);
	} else if(current_path.toLowerCase().indexOf('themes.php?theme=') !== -1){

		if (!wptc_is_allowed_to_show_extra_buttons()) {	return ; }

		var update_link = jQuery('.wptc-span-spacing-staging ~ #update-theme-staging-wptc');
		var spacing = jQuery('.wptc-span-spacing-staging ~ #update-theme-staging-wptc').siblings('.wptc-span-spacing-staging');
		jQuery(update_link).remove();
		jQuery(spacing).remove();
		var popup_update = '<span class="wptc-span-spacing-staging">&nbsp;or</span> <a href="#" id="update-theme-staging-wptc" class=" '+extra_class+'">Update in staging</a>';
		jQuery('#update-theme').after(popup_update);
		add_tool_tip_staging_wptc();
	} else if(current_path.toLowerCase().indexOf('themes.php') !== -1){

		if (!wptc_is_allowed_to_show_extra_buttons()) {	return ; }

		jQuery('.button-link-themes-staging-wptc, .button-action-themes-staging-wptc , #update-theme-staging-wptc, .wptc-span-spacing-staging, .button-action-themes-staging-wptc').remove();
		var in_app_update = '<span class="wptc-span-spacing-staging">&nbsp;or </span><button class="button-link-themes-staging-wptc button-link  '+extra_class+'" type="button">Update in staging</button>';
		var selected_update = '<span class="wptc-span-spacing-staging">&nbsp;</span><input type="submit" class="button-action-themes-staging-wptc button  '+extra_class+'" value="Update in staging">';
		jQuery('.button-link[type=button]').not('.wp-auth-check-close, .button-link-themes-staging-wptc, .button-link-themes-bbu-wptc').after(in_app_update);
		jQuery('form[id=bulk-action-form]').find('.button.action').after(selected_update);

		if (wptc_is_multisite) {
			jQuery('.wptc-span-spacing-staging , .update-link-themes-staging-wptc ').remove();
			var in_app_update = '<span class="wptc-span-spacing-staging">&nbsp;or</span> <a href="#" class="update-link-themes-staging-wptc  '+extra_class+'">Update in staging</a>';
			jQuery('form[id=bulk-action-form]').find('.update-link').after(in_app_update);
		}
	}
	setTimeout(function (){
		jQuery('.theme').on('click', '.button-link-themes-staging-wptc , #update-theme', function(e) {
			handle_theme_button_link_request_wptc(this, e, false, true);
		});
	}, 1000);

	setTimeout(function (){
		jQuery('#update-theme-staging-wptc').on('click', function(e) {
			handle_theme_link_request_wptc(this, e, false, true)
		});
	}, 500);
	// get_staging_details_wptc();
}

function wptc_choose_update_in_stage(update_items, type){
	swal({
		title              : mainwp_wptc_get_dialog_header('Choose option'),
		html               : mainwp_wptc_get_dialog_body('Want to try this update in staging site?'),
		padding            : '0px 0px 10px 0',
		buttonsStyling     : false,
		showCancelButton   : true,
		confirmButtonColor : '',
		cancelButtonColor  : '',
		confirmButtonClass : 'button-primary wtpc-button-primary',
		cancelButtonClass  : 'button-secondary wtpc-button-secondary',
		confirmButtonText  : 'Update in staging',
		cancelButtonText   : 'Stage and update',
	}).then(function () {
			swal({
				title              : mainwp_wptc_get_dialog_header('Are you sure?'),
				html               : mainwp_wptc_get_dialog_body('This will just perform the update in the staging site, Other changes are safe.', ''),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				showCancelButton   : true,
				confirmButtonColor : '',
				cancelButtonColor  : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				cancelButtonClass  : 'button-secondary wtpc-button-secondary',
				confirmButtonText  : 'Yes',
				cancelButtonText   : 'Cancel',
			}).then(function () {
					wptc_save_upgrade_meta_in_staging(update_items, type, 'update_in_staging');
				}, function (dismiss) {
					console.log('action cancelled');
				}
			);
		}, function (dismiss) {

			if (dismiss === 'overlay') {
				return ;
			}

			swal({
				title              : mainwp_wptc_get_dialog_header('Are you sure?'),
				html               : mainwp_wptc_get_dialog_body('This will erase your entire staging site and do fresh staging then initiate the update.', ''),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				showCancelButton   : true,
				confirmButtonColor : '',
				cancelButtonColor  : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				cancelButtonClass  : 'button-secondary wtpc-button-secondary',
				confirmButtonText  : 'Yes',
				cancelButtonText   : 'Cancel',
			}).then(function () {
					wptc_save_upgrade_meta_in_staging(update_items, type, 'update_and_staging');
				}, function (dismiss) {
					console.log('action cancelled');
				}
			);
		}
	);
}

function wptc_save_upgrade_meta_in_staging(update_items, type, choice){
	console.log('wptc_save_upgrade_meta_in_staging');
	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: 'save_upgrade_meta_in_staging_wptc',
		update_items: update_items,
		type: type,
	}, function(data) {
		try{
			var data = jQuery.parseJSON(data);
		} catch(err){
			return wptc_request_failed();
		}

		if(!data.status && data.status === 'success'){
			return wptc_request_failed();
		}

		if (choice === 'update_in_staging') {
			swal({
				title              : mainwp_wptc_get_dialog_header('Update initiated'),
				html               : mainwp_wptc_get_dialog_body('Continue your work, we will update you once update is done on staging site', 'success'),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				confirmButtonColor : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				confirmButtonText  : 'Ok',
			});
			wptc_force_update_in_staging();

		} else if(choice === 'update_and_staging'){
			swal({
				title              : mainwp_wptc_get_dialog_header('Update in staging initiated'),
				html               : mainwp_wptc_get_dialog_body('Continue your work, we will update you once state and update is done', 'success'),
				padding            : '0px 0px 10px 0',
				buttonsStyling     : false,
				confirmButtonColor : '',
				confirmButtonClass : 'button-primary wtpc-button-primary',
				confirmButtonText  : 'Ok',
			});
			select_copy_staging_type_wptc();
		} else {
			wptc_request_failed();
		}
	});
}

function wptc_force_update_in_staging(){
	jQuery.post(ajaxurl, {
		security: wptc_ajax_object.ajax_nonce,
		action: 'force_update_in_staging_wptc',
	}, function(data) {

	});
}

function copy_staging_wptc(){
	jQuery.post(ajaxurl, {
		timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce,
		action: 'mainwp_copy_staging_wptc',
		dataType: "json"
	}, function(data) {
        console.log(data);
		wptc_process_init_request(data);
	});
}

function select_copy_staging_type_wptc(direct_copy){
	// if(direct_copy === undefined){
		// var data = {
		// 	bbu_note_view:{
		// 		type: 'message',note:'Update in staging initiated! We will notify you once it\'s completed.',
		// 	},
		// };

		// show_notification_bar_wptc(data);
	// }

	copy_staging_wptc();
}

function add_tool_tip_staging_wptc(type){
	if(type){
		add_tool_tip_staging_wptc_type = type;
	} else {
		if(typeof add_tool_tip_staging_wptc_type != 'undefined'){
			type = add_tool_tip_staging_wptc_type;
		}
	}
	var class_staging_in_update = '.upgrade-plugins-staging-wptc, .upgrade-themes-staging-wptc, .upgrade-translations-staging-wptc, .upgrade-core-staging-wptc, .update-link-plugins-staging-wptc, .button-action-plugins-staging-wptc, .plugin-update-from-iframe-staging-wptc , .update-now-plugins-staging-wptc, .button-link-themes-staging-wptc, .button-action-plugins-staging-wptc, #update-theme-staging-wptc, .update-link-themes-staging-wptc, .button-action-themes-staging-wptc';
	var class_bbu_in_update = "#update-theme-bbu-wptc, .update-link-plugins-bbu-wptc , .upgrade-plugins-bbu-wptc, .upgrade-themes-bbu-wptc, .upgrade-translations-bbu-wptc, .upgrade-core-bbu-wptc, .plugin-update-from-iframe-bbu-wptc, .update-link-plugins-bbu-wptc, .button-action-plugins-bbu-wptc, .update-now-plugins-bbu-wptc, .button-link-themes-bbu-wptc, .button-action-plugins-bbu-wptc, .update-link-themes-bbu-wptc, .button-action-themes-bbu-wptc";
	if(type === 'staging_running'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Staging is running. Please wait until it finishes.', { style: "dark" });
		});

		jQuery(class_bbu_in_update).each(function(tagElement , key) {
			jQuery(key).addClass('disabled button-disabled-bbu-from-staging-wptc');
			jQuery(key).opentip('Staging is running. Please wait until it finishes.', { style: "dark" });
		});
	} else if(type === 'not_staged_yet'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Set up a staging in WP Time Capsule -> Staging.', { style: "dark" });
		});
	} else if(type === 'backup_progress'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Backup in progress. Please wait until it finishes', { style: "dark" });
		});
	} else if(type === 'staging_error'){
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('Previous staging failed. Please fix it.', { style: "dark" });
		});
	} else if (type === 'staging_completed'){
		jQuery(class_staging_in_update).removeClass('disabled button-disabled-staging-1-wptc button-disabled-staging-2-wptc button-disabled-staging-3-wptc button-disabled-staging-4-wptc');
	} else {
		jQuery(class_staging_in_update).each(function(tagElement , key) {
			jQuery(key).opentip('You cannot stage now, Please try after sometime.', { style: "dark" });
		});
	}
}

function wptc_is_backup_running() {
    if(typeof wptc_backup_running == 'undefined' || !wptc_backup_running){
		return false;
	}
	return true;
}

function wptc_disable_staging_button_after_dom_loaded(type){
    console.log(type);

	if(!wptc_is_backup_running()){
		return false;
	}

	switch(type){
		case 'staging_completed':
			wptc_disable_staging_completed_button();
			break;
		case 'not_staged_yet':
			wptc_disable_staging_start_button();
			break;
	}
}

function wptc_disable_staging_completed_button() {
	jQuery('#ask_copy_staging_wptc, #edit_staging_wptc').addClass('disabled').css('color','gray');

	if(jQuery('#ask_copy_staging_wptc').length > 0)
		jQuery('#ask_copy_staging_wptc').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });

	if(jQuery('#edit_staging_wptc').length > 0)
		jQuery('#edit_staging_wptc').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });
}

function wptc_disable_staging_start_button() {
	jQuery('#select_same_server_wptc, #select_different_server_wptc, #same_server_submit_wptc').addClass('disabled').css('cursor', 'not-allowed');

	if(jQuery('#select_same_server_wptc').length > 0)
		jQuery('#select_same_server_wptc').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });

	if(jQuery('#select_different_server_wptc').length > 0)
		jQuery('#select_different_server_wptc').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });

	if(jQuery('#same_server_submit_wptc').length > 0)
		jQuery('#same_server_submit_wptc').opentip('Backup in progress. Please wait until it finishes', { style: "dark" });
}

function enable_staging_button_wptc(){
	setTimeout(function(){
		jQuery('#select_same_server_wptc, #select_different_server_wptc, #same_server_submit_wptc').removeClass('disabled').css('cursor', 'pointer');
	}, 2000);
}
