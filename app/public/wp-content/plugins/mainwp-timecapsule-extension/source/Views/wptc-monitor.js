
function mainwp_process_wtc_reload(data) {
    jQuery('#progress').html('<div class="calendar_wrapper"></div>');
    jQuery("#progress").append('<div id="dialog_content_id" style="display:none;"> <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p></div><a class="thickbox wptc-thickbox" style="display:none" href="#TB_inline?width=500&height=500&inlineId=dialog_content_id&modal=true"></a>');

    if (typeof data == 'undefined' || !data) {
        return;
    }

    jQuery('.calendar_wrapper').fullCalendar({
        theme: false,
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultDate: defaultDateWPTC, //setting from global var
        editable: false,
        events: data.stored_backups,
        eventAfterAllRender: function () {
            var first_one = jQuery('.fc-header-right')[0];
            jQuery(first_one).html('<div class="last-bp-taken-wptc">Last backup on - <span class="last-bp-taken-time">' + data.last_backup_time + '</span> </div>');
        }
    });

    var backup_progress = data.backup_progress;
    if (backup_progress != '') {
        showLoadingDivInCalendarBoxWptc();
    } else {
        resetLoadingDivInCalendarBoxWptc();
    }
}

function getThisDayBackups(backupIds) {
    remove_other_thickbox_wptc();
    jQuery('.notice, #update-nag').remove();
    var loading = '<div class="dialog_cont" style="padding:2%"><div class="loaders"><div class="loader_strip"><div class="wptc-loader_strip_cl" style="background:url(' + wptcOptionsPageURl + '/source/images/loader_line.gif)"></div></div></div></div>';
    jQuery("#dialog_content_id").html(loading);
    jQuery(".wptc-thickbox").click();
    styling_thickbox_tc();
    registerDialogBoxEventsTC();
    //to show all the backup list when a particular date is clicked
    get_this_day_backups_ajax(backupIds);
}

function registerDialogBoxEventsTC() {
    if (typeof cuurent_bridge_file_name == 'undefined') {
        cuurent_bridge_file_name = '';
    }
    jQuery.curCSS = jQuery.css;
    jQuery('.checkbox_click').on('click', function () {

        if (!(jQuery(this).hasClass("active"))) {
            jQuery(this).addClass("active");
        } else {
            jQuery(this).removeClass("active");
        }
    });

    jQuery('.single_backup_head').on('click', function () {
        var this_obj = jQuery(this).closest(".single_group_backup_content");

        if (!(jQuery(this).hasClass("active"))) {
            jQuery(".single_backup_content_body", this_obj).show();
        } else {
            jQuery(".single_backup_content_body", this_obj).hide();
        }
    });



    //UI actions for the file selection
    jQuery(".toggle_files").on("click", function (e) {
        var par_obj = jQuery(this).closest(".single_group_backup_content");
        if (!jQuery(par_obj).hasClass("open")) {
            //close all other restore tabs ; remove the active items
            jQuery(".this_leaf_node li").removeClass("selected");
            jQuery(".toggle_files.selection_mode_on").click();

            jQuery(par_obj).addClass("open");
            jQuery(".changed_files_count, .this_restore", par_obj).show();
            jQuery(".this_restore_point_wptc", par_obj).hide();
            jQuery(".restore_to_staging_wptc", par_obj).hide();
            jQuery(this).addClass("selection_mode_on");
        } else {
            jQuery(par_obj).removeClass("open");
            jQuery(".changed_files_count, .this_restore", par_obj).hide();
            jQuery(".this_restore_point_wptc", par_obj).show();
            jQuery(".restore_to_staging_wptc", par_obj).show();
            jQuery(this).removeClass("selection_mode_on");
        }
        e.stopImmediatePropagation();
        if (typeof styling_thickbox_wptc !== 'undefined' && jQuery.isFunction(styling_thickbox_wptc)) {
            styling_thickbox_wptc("");
        }
        return false;
    });

    jQuery(".folder").on("click", function (e) {
        if (jQuery(this).hasClass('disabled')) {
            return false;
        }
        mainwp_get_sibling_files_wptc(this);
        e.stopImmediatePropagation();
        return false;
    });

    jQuery(".restore_the_db").on("click", function () {
        var par_obj = jQuery(this).closest(".single_group_backup_content");
        if (!jQuery(this).hasClass("selected")) {
            jQuery(".sql_file", par_obj).parent(".this_parent_node").prev(".sub_tree_class").removeClass("selected");
            jQuery(".sql_file li", par_obj).removeClass("selected");
        } else {
            jQuery(".sql_file", par_obj).parent(".this_parent_node").prev(".sub_tree_class").addClass("selected");
            jQuery(".sql_file li", par_obj).addClass("selected");
        }

        if ((!jQuery(".this_leaf_node li", par_obj).hasClass("selected")) && (!jQuery(".sub_tree_class", par_obj).hasClass("selected"))) {
            jQuery(".this_restore", par_obj).addClass("disabled");
        } else {
            jQuery(".this_restore", par_obj).removeClass("disabled");
        }
    });

    jQuery('.this_restore').on('click', function (e) {
        if (jQuery(this).hasClass("disabled")) {
            return false;
        }
        restore_obj = this;
        restore_type = 'selected_files';

        //mainwp_wptc_restore_confirmation_pop_up();

        setTimeout(function () {
            var site_id = jQuery('#mainwp_wptc_current_site_id').val();
            var loc = 'admin.php?page=wp-time-capsule-monitor';
            loc = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' + site_id + '&location=' + mainwp_wptc_utf8_to_b64(loc) + '&_opennonce=' + mainwpParams._wpnonce;
            window.open(loc, '_blank');
        }, 2000);

        return false;

    });

    jQuery('body').on('click', '.no_exit_restore_wptc', function (e) {
        e.stopImmediatePropagation()
        revert_confirmation_backup_popups();
    });

    jQuery('.this_restore_point_wptc').on('click', function (e) {
        restore_obj = this;
        restore_type = 'to_point';
        // mainwp_wptc_restore_confirmation_pop_up();
        setTimeout(function () {
            var site_id = jQuery('#mainwp_wptc_current_site_id').val();
            var loc = 'admin.php?page=wp-time-capsule-monitor';
            loc = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' + site_id + '&location=' + mainwp_wptc_utf8_to_b64(loc) + '&_opennonce=' + mainwpParams._wpnonce;
            window.open(loc, '_blank');
        }, 2000);
        return false;
    });

    jQuery("#TB_overlay").on("click", function () {
        if ((typeof is_backup_started == 'undefined' || is_backup_started == false) && !on_going_restore_process) { //for enabling dialog close on complete
            tb_remove();
            backupclickProgress = false;
        }
    });

    jQuery(".dialog_close").on("click", function () {
        tb_remove();
    });
}

function revert_confirmation_backup_popups() {
    jQuery('.wptc_restore_confirmation').remove();
    jQuery('#TB_ajaxContent').show();
}
function wtc_initializeRestore(obj, type) {
    //this function returns the files to be restored ; shows the dialog box ; clear the reload timeout for backup ajax function
    var files_to_restore = {};

    var par_obj = jQuery(obj).closest('.single_group_backup_content');

    if (type == 'all') {
        var is_selected = ''; //a trick to include all files during restoring-at-a-point;

        var sql_obj_wptc = jQuery(".this_leaf_node li.sql_file_li", par_obj);

        var this_revision_id = jQuery(sql_obj_wptc).find(".file_path").attr("revision_id");

        files_to_restore[this_revision_id] = {};
        files_to_restore[this_revision_id]['file_name'] = jQuery(sql_obj_wptc).find(".file_path").attr("file_name");
        files_to_restore[this_revision_id]['file_size'] = jQuery(sql_obj_wptc).find(".file_path").attr("file_size");
        files_to_restore[this_revision_id]['g_file_id'] = jQuery(sql_obj_wptc).find(".file_path").attr("g_file_id");
        files_to_restore[this_revision_id]['mtime_during_upload'] = jQuery(sql_obj_wptc).find(".file_path").attr("mod_time");

    } else {
        var is_selected = '.selected';
        var files_to_restore = {};
        files_to_restore['folders'] = {};
        files_to_restore['files'] = {};
        var folders_count = 0;
        var files_count = 0;
        var selected_items = jQuery(par_obj).find(is_selected);
        jQuery.each(selected_items, function (key, value) {
            if (jQuery(value).hasClass('sub_tree_class') && jQuery(value).hasClass('restore_the_db') == false) {
                files_to_restore['folders'][folders_count] = {};
                files_to_restore['folders'][folders_count]['file_name'] = jQuery(value).children().attr('file_name');
                files_to_restore['folders'][folders_count]['backup_id'] = jQuery(value).children().attr('backup_id');
                folders_count++;
            } else {
                files_to_restore['files'][files_count] = {};
                files_to_restore['files'][files_count]['file_name'] = jQuery(value).children().attr('file_name');
                files_to_restore['files'][files_count]['backup_id'] = jQuery(value).children().attr('backup_id');
                files_to_restore['files'][files_count]['revision_id'] = jQuery(value).children().attr('revision_id');
                files_to_restore['files'][files_count]['mtime_during_upload'] = jQuery(value).children().attr('mod_time');
                files_to_restore['files'][files_count]['g_file_id'] = jQuery(value).children().attr('g_file_id');
                files_count++;
            }
        });
    }

    prepareRestoreProgressDialogWPTC();

    if (typeof reloadFuncTimeout != 'undefined') {
        clearTimeout(reloadFuncTimeout);
    }
    return files_to_restore;
}

function prepareRestoreProgressDialogWPTC() {
    var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1; color: #444;padding: 0px 34px 26px 34px; left:20%; z-index:1000 "><div class="pu_title">Restoring ' + sitenameWPTC + '</div><div class="mainwp_wtc_wcard progress_reverse" style="height:60px; padding:0;"><div class="progress_bar" style="width:0%;"></div>  <div class="progress_cont">Preparing files to restore...</div></div><div style="padding: 10px; text-align: center;">Note: Please do not close this tab until restore completes.</div></div>';

    jQuery("#TB_ajaxContent").html(this_html);
    styling_thickbox_tc('restore');
}

function getAndStoreBridgeURL() {
    var this_plugin_url = this_plugin_url_wptc;
    var this_data = '';
    var post_array = {};
    post_array['getAndStoreBridgeURL'] = 1;
    this_data = jQuery.param(post_array);

    jQuery.post(ajaxurl, {
        action: 'mainwp_start_restore_tc_wptc',
        data: post_array,
        timecapsuleSiteID: jQuery('#mainwp_wptc_current_site_id').val(),
        nonce: mainwp_timecapsule_loc.nonce
    }, function (request) {
        if ((typeof request != 'undefined') && request != null) {
            cuurent_bridge_file_name = request;
        }
    });
}

function dialogOpenCallBack() {

}


jQuery(document).ready(function ($) {

    jQuery('body').on('click', '.restore_to_staging_wptc', function () {
        if (jQuery(this).hasClass('disabled')) {
            return;
        }
        restore_obj = this;

        jQuery('#TB_overlay, #TB_ajaxContent').hide();


        swal({
            title: mainwp_wptc_get_dialog_header('Are you sure?'),
            html: mainwp_wptc_get_dialog_body('This will erase your entire staging site and do fresh staging then initiate the restore !'),
            padding: '0px 0px 10px 0',
            buttonsStyling: false,
            showCancelButton: true,
            confirmButtonColor: '',
            cancelButtonColor: '',
            confirmButtonClass: 'button-primary wtpc-button-primary',
            cancelButtonClass: 'button-secondary wtpc-button-secondary',
            confirmButtonText: 'Do it',
            cancelButtonText: 'Cancel',
        }).then(function () {
            dialog_close_wptc();
            // jQuery('#TB_overlay, #TB_ajaxContent').show();
            backupclickProgress = false;

            // swal({
            // 	title              : mainwp_wptc_get_dialog_header('Process started !'),
            // 	html               : mainwp_wptc_get_dialog_body('During the restore process on your staging site, there will be multiple page redirects. Don\'t close the window during this process and kindly wait till it completes.'),
            // 	padding            : '0px 0px 10px 0',
            // 	buttonsStyling     : false,
            // 	showCancelButton   : false,
            // 	confirmButtonColor : '',
            // 	confirmButtonClass : 'button-primary wtpc-button-primary',
            // 	confirmButtonText  : 'Ok',
            // });

            setTimeout(function () {
                var site_id = jQuery('#mainwp_wptc_current_site_id').val();
                var loc = 'admin.php?page=wp-time-capsule-monitor';
                loc = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' + site_id + '&location=' + mainwp_wptc_utf8_to_b64(loc) + '&_opennonce=' + mainwpParams._wpnonce;
                window.open(loc, '_blank');
            }, 2000);

        }, function (dismiss) {
            // dismiss can be 'cancel', 'overlay',
            // 'close', and 'timer'
            jQuery('#TB_overlay, #TB_ajaxContent').show();
            revert_confirmation_backup_popups();
            backupclickProgress = false;
            // if (dismiss === 'cancel') {
            // }
        })
    });

});


function wptc_redirect_to_staging_page() {
    if (typeof wptc_R2S_redirect_to_staging != 'undefined' && wptc_R2S_redirect_to_staging === true) {
        delete wptc_R2S_redirect_to_staging;
        var site_id = jQuery('#mainwp_wptc_current_site_id').val();
        parent.location.assign('admin.php?page=Extensions-Mainwp-Timecapsule-Extension&tab=staging&site_id=' + site_id);
    }
}

function stripquotes(a) {
    if (a.charAt(0) === "'" && a.charAt(a.length - 1) === "'") {
        return a.substr(1, a.length - 2);
    }
    return a;
}


function startRestore_bridge(files_to_restore, cur_res_b_id, selectedID, ignore_file_write_check, is_latest_restore_point) {
    start_time_tc = Date.now(); //global variable which will be used to see the activity so as to trigger new call when there is no activity for 60secs
    on_going_restore_process = true;

    if (typeof reloadFuncTimeout != 'undefined') {
        clearTimeout(reloadFuncTimeout);
    }

    jQuery.post('index.php', {
        traditional: true,
        type: 'post',
        url: 'index.php',
        data: {
            cur_res_b_id: cur_res_b_id,
            files_to_restore: files_to_restore,
            selectedID: selectedID,
            wptc_request: true,
            is_latest_restore_point: is_latest_restore_point,
        },
    }, function (request) {
        request = parse_wptc_response_from_raw_data(request);
        try {
            request = jQuery.parseJSON(request);
        } catch (err) {
            show_error_dialog_and_clear_timeout({ error: 'Didnt get required values to initiated restore.' });
            return;
        }
        if ((typeof request != 'undefined') && request != null) {
            if (typeof request.restoreInitiatedResult != 'undefined') {
                if (typeof request['restoreInitiatedResult'] != 'undefined' && typeof request['restoreInitiatedResult']['bridgeFileName'] != 'undefined' && request['restoreInitiatedResult']['bridgeFileName']) {
                    cuurent_bridge_file_name = request['restoreInitiatedResult']['bridgeFileName'];
                    getTcRestoreProgress();
                    request['initialize'] = true;
                    startBridgeDownload(request);
                    checkIfNoResponse('startBridgeDownload');
                } else {
                    show_error_dialog_and_clear_timeout({ error: 'Didnt get required values to initiated restore.' });
                }
            } else if (typeof request.error != 'undefined') {
                if (typeof request['error'] != 'undefined') {
                    show_error_dialog_and_clear_timeout(request);
                }
            } else {
                show_error_dialog_and_clear_timeout({ error: 'Initiating Restore failed.' });
            }
        }
    });
}

function show_error_dialog_and_clear_timeout(request) {

    if (typeof checkIfNoResponseTimeout != 'undefined') {
        clearTimeout(checkIfNoResponseTimeout);
    }
    if (typeof getRestoreProgressTimeout != 'undefined') {
        clearTimeout(getRestoreProgressTimeout);
    }

    var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1; color: #444;padding: 0px 34px 26px 34px; left:20%; z-index:1000"><div class="pu_title">ERROR DURING RESTORE</div><div class="mainwp_wtc_wcard progress_reverse error" style="overflow: scroll;max-height: 210px; padding:0;">  <div class="" style="text-overflow: ellipsis;word-wrap: break-word;text-align: center;padding-top: 19px;padding-bottom: 19px;">' + request['error'] + '</div></div><div style="padding: 10px; text-align: center;">Note: Please do not close this tab until restore completes.</div></div>';
    jQuery("#TB_ajaxContent").html(this_html);
}

function wptc_restore_retry_limit_checker() {
    console.log('wptc_restore_retry_limit_checker');

    var max_retry = 3;
    if (typeof wptc_restore_retry_count == 'undefined') {
        wptc_restore_retry_count = 1;
    } else {
        wptc_restore_retry_count++;
    }
    console.log("wptc_restore_retry_count: ", wptc_restore_retry_count);
    if (wptc_restore_retry_count >= max_retry) {
        console.log("wptc_restore_retry_limit_checker :", "limit reached");
        get_last_php_error();
        return false;
    } else {
        console.log("wptc_restore_retry_limit_checker :", "under control");
        return true;
    }
}

function get_last_php_error() {
    if (typeof seperate_bridge_call == 'undefined' || seperate_bridge_call != 1) {
        var this_url = this_home_url_wptc + '/' + cuurent_bridge_file_name + '/wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
    } else {
        var this_url = 'wptc-ajax.php'; //cuurent_bridge_file_name is a global variable and is set already
    }
    console.log("get_last_php_error");

    var post_data = {};
    post_data['action'] = 'get_last_php_error';
    jQuery.ajax({
        traditional: true,
        type: 'post',
        url: this_url,
        data: post_data,
        success: function (request) {
            console.log("get_last_php_error response");

            console.log(request);
            var deep_err_check = request.replace(/\s+/, "");
            if (request && deep_err_check) {
                show_error_dialog_and_clear_timeout({ error: request });
            } else {
                show_error_dialog_and_clear_timeout({ error: 'unknown error occured  :-(' });
            }
        },
    });
}


function checkIfNoResponse(this_func) {
    if (typeof this_func != 'undefined' && this_func != null) {
        ajax_function_tc = this_func;
    }
    if (typeof checkIfNoResponseTimeout != 'undefined') {
        clearTimeout(checkIfNoResponseTimeout);
    }
    checkIfNoResponseTimeout = setTimeout(function () {
        checkIfNoResponse();
    }, 15000);
}

function dialogCloseCallBack() {

}

function wptc_is_latest_restore_point_click(obj) {
    return jQuery(obj).parent().find('#wptc_restore_latest_point').length;
}


function yes_continue_restore_wptc() {
    revert_confirmation_backup_popups();
    if (restore_type == 'selected_files') {
        trigger_selected_files_restore();
    } else if (restore_type == 'to_point') {
        trigger_to_point_restore();
    }
}

function revert_confirmation_backup_popups() {
    jQuery('.wptc_restore_confirmation').remove();
    jQuery('#TB_ajaxContent').show();
    jQuery('#TB_overlay').show();
}

function mainwp_wptc_restore_confirmation_pop_up() {
    var html_content = 'Clicking on Yes will continue to restore your website. ';
    html_content += wptc_is_latest_restore_point_click(restore_obj) ? 'We will only restore the database changes in this restore <a href="http://docs.wptimecapsule.com/article/34-how-does-the-latest-point-restore-in-real-time-works" target="_blank">Know more</a>. ' : '';
    html_content += 'Are you sure want to continue ?';

    swal({
        title: mainwp_wptc_get_dialog_header('Restore your website?'),
        html: mainwp_wptc_get_dialog_body(html_content, ''),
        padding: '0px 0px 10px 0',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonColor: '',
        cancelButtonColor: '',
        confirmButtonClass: 'button-primary wtpc-button-primary',
        cancelButtonClass: 'button-secondary wtpc-button-secondary',
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel',
    }).then(function () {
        yes_continue_restore_wptc();
    }, function (dismiss) {
        revert_confirmation_backup_popups();
    }
    );

    jQuery('#TB_overlay').hide();
    jQuery('#TB_ajaxContent').hide();
}

function trigger_selected_files_restore() {
    var selectedID = jQuery('.open').attr('this_backup_id');
    var files_to_restore = {};
    files_to_restore = wtc_initializeRestore(jQuery(restore_obj), 'single');
    startRestore(files_to_restore, false, selectedID, true);
    checkIfNoResponse('startRestore');
    // console.log(files_to_restore);
    // console.log(selectedID);
    // e.stopImmediatePropagation();
    return false;
}

function trigger_to_point_restore() {
    var cur_res_b_id = jQuery(restore_obj).closest(".single_group_backup_content").attr("this_backup_id");
    var files_to_restore = {};
    files_to_restore = wtc_initializeRestore(jQuery(restore_obj), 'all');
    // console.log(files_to_restore);
    // console.log(cur_res_b_id);
    startRestore(false, cur_res_b_id, '', true);
    // e.stopImmediatePropagation();
    return false;
}

jQuery(document).ready(function () {


    jQuery('#start_backup').on('click', function () {
        if (jQuery(this).text() != 'Stop Backup') {
            is_backup_started = true; //setting global variable for backup status
            jQuery(this).text("Stop Backup");
            mainwp_wtc_start_backup_func('');
        } else {
            mainwp_wtc_stop_backup_func();
        }
    });

    jQuery('#stop_backup').on('click', function () {
        if (jQuery(this).text() != 'Stop Backup') {
            jQuery(this).text("Stop Backup");
            mainwp_wtc_start_backup_func('');
        } else {
            mainwp_wtc_stop_backup_func();
        }
    });

});
