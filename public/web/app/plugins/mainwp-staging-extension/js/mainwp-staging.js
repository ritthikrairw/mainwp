
jQuery(document).ready(function ($) {

    $('.mwp_staging_active_plugin').on('click', function () {
        mainwp_staging_plugin_active_start_specific($(this), false);
        return false;
    });

    $('.mwp_staging_upgrade_plugin').on('click', function () {
        mainwp_staging_plugin_upgrade_start_specific($(this), false);
        return false;
    });

    $('.mwp_staging_showhide_plugin').on('click', function () {
        mainwp_staging_plugin_showhide_start_specific($(this), false);
        return false;
    });

    $('#staging_plugin_doaction_btn').on('click', function () {
        var bulk_act = $('#mwp_staging_plugin_action').val();
        mainwp_staging_plugin_do_bulk_action(bulk_act);
    });

});

jQuery(document).ready(function ($) {
    $('#mainwp_staging_override_general_settings').on('change', function () {
        var statusEl = $('.staging_change_override_working');
        statusEl.html('<i class="notched circle loading icon"></i> Saving ...').show();
        var data = {
            action: 'mainwp_staging_site_override_settings',
            stagingSiteID: $('input[name=mainwp_staging_site_id]').val(),
            override: $(this).is(':checked') ? 1 : 0,
            _stagingNonce: mainwp_staging_loc.nonce
        }
        jQuery.post(ajaxurl, data, function (response) {
            statusEl.html('');
            if (response) {
                if (response['error']) {

                    statusEl.html('<i class="red times icon"></i>').show();
                } else if (response['ok']) {
                    statusEl.html('<i class="greem check icon"></i>').show();
                    setTimeout(function () {
                        statusEl.fadeOut();
                    }, 5000);
                } else {
                    statusEl.html(__("Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.")).show();
                }
            } else {
                statusEl.html(__("Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.")).show();
            }
        }, 'json');

        return false;
    });


    // Delete clone - confirmation
    jQuery(document).on('click', '.wpstg-remove-clone[data-clone]', function (e) {
        e.preventDefault();
        var $existingClones = jQuery("#mainwp-staging-extension-clones-table");

        mainwp_staging_$workFlow.removeClass('active');

        //jQuery("#wpstg-loader").show();
        var wpstgLoaderEl = mainwp_staging_$working.find('div.mwp-loading');
        wpstgLoaderEl.show();
        jQuery.post(ajaxurl,
            {
                action: "mainwp_staging_confirm_delete_clone",
                stagingSiteID: staging_elementsCache.site_id,
                _stagingNonce: mainwp_staging_loc.nonce,
                clone: $(this).data("clone")
            },
            function (response) {
                wpstgLoaderEl.hide();
                jQuery("#wpstg-removing-clone").html(response).show();
                $existingClones.children("img").remove();

                //jQuery("#wpstg-loader").hide();
            }, "HTML");
    });

    // Cancel deleting clone
    jQuery(document).on('click', '#wpstg-cancel-removing', function (e) {
        e.preventDefault();
        $(".wpstg-clone").removeClass("active");
        jQuery("#wpstg-removing-clone").html('');
    })

    jQuery(document).on('click', '.wpstg-execute-clone', function (e) {
        e.preventDefault();
        if (!confirm("Are you sure you want to update the staging site? All your staging site modifications will be overwritten with the data from the live site. So make sure that your live site is up to date.")) {
            return false;
        }

        var statusEl = mainwp_staging_$working.find('.status');
        var loadingEl = mainwp_staging_$working.find('div.mwp-loading');
        loadingEl.show();
        statusEl.hide();

        var clone = $(this).data("clone");
        mainwp_staging_$workFlow.addClass("loading");

        jQuery.post(ajaxurl,
            {
                action: "mainwp_staging_scanning",
                stagingSiteID: staging_elementsCache.site_id,
                clone: clone,
                _stagingNonce: mainwp_staging_loc.nonce
            },
            function (response) {
                loadingEl.hide();
                var err = true;
                if (response) {
                    if (response.error) {
                        statusEl.css('color', '#a00');
                        statusEl.html(response.error);
                    } else if (response.result) {
                        mainwp_staging_$workFlow.html(response.result);
                        err = false;
                    } else {
                        statusEl.css('color', '#a00');
                        statusEl.html('Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.');
                    }
                } else {
                    statusEl.css('color', '#a00');
                    statusEl.html('Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.');
                }
                if (err)
                    statusEl.show();
            },
            "json"
        );
    });

    // When a Directory is Selected
    $("#mwp-wpstg-workflow").on("change", ".wpstg-check-dir", function () {
        var $directory = $(this).parent(".wpstg-dir");

        if (this.checked) {
            $directory.parents(".wpstg-dir").children(".wpstg-check-dir").prop("checked", true);
            $directory.find(".wpstg-expand-dirs").removeClass("disabled");
            $directory.find(".wpstg-subdir .wpstg-check-dir").prop("checked", true);
            $directory.children(".wpstg-subdir").slideDown();
        } else {
            $directory.find(".wpstg-dir .wpstg-check-dir").prop("checked", false);
            $directory.find(".wpstg-expand-dirs, .wpstg-check-subdirs").addClass("disabled");
            $directory.find(".wpstg-check-subdirs").data("action", "check").text("check");
            $directory.children(".wpstg-subdir").slideUp();
        }
    });

    // Expand Directories
    jQuery(document).on("click", ".wpstg-expand-dirs", function (e) {
        e.preventDefault();

        var $this = $(this);

        if (!$this.hasClass("disabled")) {
            $this.siblings(".wpstg-subdir").slideToggle();
        }
    })


    // Check the max length of the clone name and if the clone name already exists
    // Previous Button
    $("#mwp-wpstg-workflow").on("click", ".wpstg-prev-step-link", function (e) {
        e.preventDefault();
        mainwp_staging_loadOverview(true);
        return false;
    });

    // Check / Un-check Database Tables
    $("#mwp-wpstg-workflow").on("click", ".wpstg-button-unselect", function (e) {
        e.preventDefault();

        if (false === staging_elementsCache.isAllChecked) {
            $(".wpstg-db-table-checkboxes").prop("checked", true);
            $(".wpstg-button-unselect").text("Un-check All");
            staging_elementsCache.isAllChecked = true;
        } else {
            $(".wpstg-db-table-checkboxes").prop("checked", false);
            $(".wpstg-button-unselect").text("Check All");
            staging_elementsCache.isAllChecked = false;
        }
    })

    $("#mwp-wpstg-workflow").on("click", ".wpstg-button-select", function (e) {
        e.preventDefault();
        var me = this;
        $(".wpstg-db-table input").each(function () {
            if ($(this).attr('name').match("^" + $(me).attr('tblprefix'))) {
                $(this).prop("checked", true);
            } else {
                $(this).prop("checked", false);
            }
        });
    });

    $("#mwp-wpstg-workflow").on("click", "#mwp-wpstg-check-space", function (e) {
        e.preventDefault();
        mainwp_staging_checkDiskSpace();
    });

    jQuery(document).on('keyup', "#wpstg-new-clone-id", function () {
        var $field = $(this);
        //var beforeVal = $field.val();
        setTimeout(function () {
            jQuery('#wpstg_site_url').html($field.val());
        }, 0);

        // This request was already sent, clear it up!
        if ("number" === typeof (staging_elementsCache.timer)) {
            clearInterval(staging_elementsCache.timer);
        }
        staging_elementsCache.timer = setTimeout(
            function () {
                mainwp_staging_check_clone();
            }, 500);
    });

});
mainwp_staging_deleteClone = function (clone) {

    var wpstgLoaderEl = mainwp_staging_$working.find('div.mwp-loading');
    wpstgLoaderEl.show();
    jQuery.post(ajaxurl,
        {
            action: "mainwp_staging_delete_clone",
            stagingSiteID: staging_elementsCache.site_id,
            clone: clone,
            _stagingNonce: mainwp_staging_loc.nonce,
            excludedTables: mainwp_staging_getExcludedTables(),
            deleteDir: jQuery("#deleteDirectory:checked").data("deletepath"),
        },
        function (response) {
            wpstgLoaderEl.hide();
            if (response) {
                if ("undefined" !== typeof response.error && "undefined" !== typeof response.message) {
                    mainwp_staging_showError(
                        "Something went wrong! Error: " + response.message
                    );
                    console.log(response.message);
                }

                if ("undefined" !== typeof response.delete && response.delete === 'finished') {

                    jQuery("#wpstg-removing-clone").removeClass("loading").html('');
                    jQuery(".mwp-wpstg-clone#" + clone).remove();

                    if (jQuery(".mwp-wpstg-clone").length < 1) {
                        jQuery("#mainwp-staging-extension-clones-table").find("h3").text('');
                    }

                    mainwp_staging_deletesiteclone(clone);
                    //jQuery("#wpstg-loader").hide();
                    return;
                }
            }
            // continue
            if (true !== response) {
                mainwp_staging_deleteClone(clone);
                return;
            }
        }, 'json');
};

mainwp_staging_showError = function (message) {
    jQuery("#wpstg-try-again").css("display", "inline-block");
    jQuery("#wpstg-cancel-cloning").text("Reset");
    jQuery("#wpstg-cloning-result").text("Fail");
    jQuery("#wpstg-error-wrapper").show();
    jQuery("#wpstg-error-details")
        .show()
        .html(message);
    //jQuery("#wpstg-loader").hide();
};

mainwp_staging_scanning = function () {
    var statusEl = mainwp_staging_$working.find('.status');
    var loadingEl = mainwp_staging_$working.find('div.mwp-loading');

    var data = {
        action: 'mainwp_staging_scanning',
        stagingSiteID: staging_elementsCache.site_id,
        _stagingNonce: mainwp_staging_loc.nonce,
    };
    loadingEl.show();
    statusEl.hide();

    jQuery.post(ajaxurl, data, function (response) {
        loadingEl.hide();
        var err = true;
        if (response) {
            if (response.error) {
                statusEl.css('color', '#a00');
                statusEl.html(response.error);
            } else if (response.result) {
                mainwp_staging_$workFlow.html(response.result);
                err = false;
            } else {
                statusEl.css('color', '#a00');
                statusEl.html('Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.');
            }
        } else {
            statusEl.css('color', '#a00');
            statusEl.html('Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.');
        }
        if (err)
            statusEl.show();
    }, 'json');
    return false;
}

mainwp_staging_loadOverview = function (pBack) {

    var statusEl = mainwp_staging_$working.find('.status');
    var loadingEl = mainwp_staging_$working.find('div.mwp-loading');
    loadingEl.show();
    statusEl.hide();


    var data = {
        action: 'mainwp_staging_overview',
        stagingSiteID: staging_elementsCache.site_id,
        _stagingNonce: mainwp_staging_loc.nonce,
    };

    jQuery.post(ajaxurl, data, function (response) {
        loadingEl.hide();
        var err = 'Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.';
        if (response) {
            if (response.error) {
                err = response.error;
            } else if (response.result) {
                mainwp_staging_$workFlow.html(response.result);
                err = '';
            }
        }

        if (err != '') {
            if (pBack) {
                statusEl.css('color', '#a00');
                statusEl.html(err);
                statusEl.show();
            } else {
                mainwp_staging_$workFlow.css('color', '#a00');
                mainwp_staging_$workFlow.html(err);
            }
        }
    }, 'json');
    return false;
}

mainwp_staging_checkDiskSpace = function () {
    var $working = jQuery('#mwp-wpstg-working-overview');
    var statusEl = $working.find('.status');
    var loadingEl = $working.find('div.mwp-loading');
    jQuery("#mwp-wpstg-clone-id-error").hide();
    loadingEl.show();
    statusEl.hide();
    var data = {
        action: 'mainwp_staging_check_disk_space',
        stagingSiteID: staging_elementsCache.site_id,
        _stagingNonce: mainwp_staging_loc.nonce,
    };
    jQuery.post(ajaxurl, data, function (response) {
        loadingEl.hide();
        var err = '';
        if (response) {
            if (response.error) {
                err = response.error;
            } else if (response.freespace) {
                // Not enough disk space
                jQuery("#mwp-wpstg-clone-id-error").text('Available free disk space ' + response.freespace + ' | Estimated necessary disk space: ' + response.usedspace).show();
            } else {
                err = 'Disc space could not be detected.';
            }
        } else {
            err = 'Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.';
        }

        if (err != '') {
            statusEl.html('<i class="red times icon"></i>');
            statusEl.show();
        }

    }, 'json');
    return false;
}

mainwp_staging_check_clone = function () {
    var statusEl = mainwp_staging_$working.find('.status');
    var loadingEl = mainwp_staging_$working.find('div.mwp-loading');

    loadingEl.show();
    statusEl.hide();
    var data = {
        action: 'mainwp_staging_check_clone',
        stagingSiteID: staging_elementsCache.site_id,
        cloneID: jQuery('#wpstg-new-clone-id').val(),
        _stagingNonce: mainwp_staging_loc.nonce,
    };
    jQuery.post(ajaxurl, data, function (response) {
        loadingEl.hide();
        var err = '';
        if (response) {
            if (response.error) {
                err = response.error;
            } else if (response.status) {
                if (response.status === "success") {
                    jQuery("#wpstg-new-clone-id").removeClass("mwp-wpstg-error-input");
                    jQuery("#wpstg-start-cloning").removeAttr("disabled");
                } else {
                    jQuery("#wpstg-new-clone-id").addClass("mwp-wpstg-error-input");
                    jQuery("#wpstg-start-cloning").prop("disabled", true);
                    err = response.message;
                }
            } else {
                err = 'Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.';
            }
        } else {
            err = 'Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.';
        }

        if (err != '') {
            statusEl.css('color', '#a00');
            statusEl.html(err);
            statusEl.show();
        }

    }, 'json');
    return false;
}

mainwp_staging_addsite = function (cloneUrl, cloneID) {
    var wpstgLoaderEl = mainwp_staging_$working.find('div.mwp-loading');
    wpstgLoaderEl.show();
    jQuery.post(ajaxurl,
        {
            action: "mainwp_staging_add_clone_website",
            stagingSiteID: staging_elementsCache.site_id,
            clone: cloneID,
            clone_url: cloneUrl,
            _stagingNonce: mainwp_staging_loc.nonce
        },
        function (response) {
            wpstgLoaderEl.hide();

        }, 'json');
}

mainwp_staging_deletesiteclone = function (clone) {
    var wpstgLoaderEl = mainwp_staging_$working.find('div.mwp-loading');
    wpstgLoaderEl.show();
    jQuery.post(ajaxurl,
        {
            action: "mainwp_staging_delete_clone_website",
            stagingSiteID: staging_elementsCache.site_id,
            clone: clone,
            _stagingNonce: mainwp_staging_loc.nonce
        },
        function (response) {
            wpstgLoaderEl.hide();

        }, 'json');
}

mainwp_staging_getExcludedTables = function () {
    var excludedTables = [];

    jQuery(".wpstg-db-table input:not(:checked)").each(function () {
        excludedTables.push(this.name);
    });

    return excludedTables;
};

mainwp_staging_save_individual_settings = function (site_id) {
    var process = jQuery('#mwp_staging_setting_ajax_message');
    var statusEl = process.find('.status');
    var loadingEl = process.find('.loading');

    var data = {
        action: 'mainwp_staging_save_settings',
        stagingSiteID: site_id,
        individual: 1,
        _stagingNonce: mainwp_staging_loc.nonce
    };
    loadingEl.show();
    jQuery.post(ajaxurl, data, function (response) {
        loadingEl.hide();
        if (response) {
            if (response.error) {
                statusEl.html('<i class="red times icon"></i>');
            } else if (response.result == 'success') {
                var msg = __('<i class="green check icon"></i>');
                statusEl.html(msg);
            } else {
                statusEl.html('<i class="red times icon"></i>');
            }
        } else {
            statusEl.html('<i class="red times icon"></i>');
        }
        statusEl.show();
    }, 'json');
};


var staging_bulkMaxThreads = 3;
var staging_bulkTotalThreads = 0;
var staging_bulkCurrentThreads = 0;
var staging_bulkFinishedThreads = 0;
var staging_elementsCache = {
    isAllChecked: true,
    timer: null,
};

mainwp_staging_plugin_do_bulk_action = function (act) {
    var selector = '';
    switch (act) {
        case 'activate-selected':
            selector = 'tbody tr.negative .mwp_staging_active_plugin';
            jQuery(selector).addClass('queue');
            mainwp_staging_plugin_active_start_next(selector);
            break;
        case 'update-selected':
            selector = 'tbody tr.warning .mwp_staging_upgrade_plugin';
            jQuery(selector).addClass('queue');
            mainwp_staging_plugin_upgrade_start_next(selector);
            break;
        case 'hide-selected':
            selector = 'tbody tr .mwp_staging_showhide_plugin[showhide="hide"]';
            jQuery(selector).addClass('queue');
            mainwp_staging_plugin_showhide_start_next(selector);
            break;
        case 'show-selected':
            selector = 'tbody tr .mwp_staging_showhide_plugin[showhide="show"]';
            jQuery(selector).addClass('queue');
            mainwp_staging_plugin_showhide_start_next(selector);
            break;
    }
}

mainwp_staging_plugin_showhide_start_next = function (selector) {
    while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (staging_bulkCurrentThreads < staging_bulkMaxThreads)) {
        objProcess.removeClass('queue');
        if (objProcess.closest('tr').find('.check-column input[type="checkbox"]:checked').length == 0) {
            continue;
        }
        mainwp_staging_plugin_showhide_start_specific(objProcess, true, selector);
    }
}

mainwp_staging_plugin_showhide_start_specific = function (pObj, bulk, selector) {
    var parent = pObj.closest('tr');
    var showhide = pObj.attr('showhide');
    var pluginName = parent.attr('plugin-name');
    var statusEl = parent.find('.visibility');
    var pluginName = parent.attr('plugin-name');
    if (bulk) {
        staging_bulkCurrentThreads++;
    }

    var data = {
        action: 'mainwp_staging_showhide_plugin',
        stagingSiteID: parent.attr('website-id'),
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
                parent.find('.wp-staging-visibility').html(__('No'));
            } else {
                pObj.text("Unhide Plugin");
                pObj.attr('showhide', 'show');
                parent.find('.wp-staging-visibility').html(__('Yes'));
            }
        } else {
            statusEl.html('<i class="red times icon"></i>');
        }

        if (bulk) {
            staging_bulkCurrentThreads--;
            staging_bulkFinishedThreads++;
            mainwp_staging_plugin_showhide_start_next(selector);
        }

    }, 'json');
    return false;
}

mainwp_staging_plugin_upgrade_start_next = function (selector) {
    while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (objProcess.length > 0) && (staging_bulkCurrentThreads < staging_bulkMaxThreads)) {
        objProcess.removeClass('queue');
        if (objProcess.closest('tr').prev('tr').find('.check-column input[type="checkbox"]:checked').length == 0) {
            continue;
        }
        mainwp_staging_plugin_upgrade_start_specific(objProcess, true, selector);
    }
}

mainwp_staging_plugin_upgrade_start_specific = function (pObj, bulk, selector) {
    var parent = pObj.closest('tr');
    var statusEl = parent.find('.updating');
    var slug = parent.attr('plugin-slug');
    var data = {
        action: 'mainwp_staging_upgrade_plugin',
        stagingSiteID: parent.attr('website-id'),
        type: 'plugin',
        'slugs[]': slug
    }

    if (bulk) {
        staging_bulkCurrentThreads++;
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
            staging_bulkCurrentThreads--;
            staging_bulkFinishedThreads++;
            mainwp_staging_plugin_upgrade_start_next(selector);
        }

    }, 'json');
    return false;
}

mainwp_staging_plugin_active_start_next = function (selector) {
    while ((objProcess = jQuery(selector + '.queue:first')) && (objProcess.length > 0) && (objProcess.length > 0) && (staging_bulkCurrentThreads < staging_bulkMaxThreads)) {
        objProcess.removeClass('queue');
        if (objProcess.closest('tr').prev('tr').find('.check-column input[type="checkbox"]:checked').length == 0) {
            continue;
        }
        mainwp_staging_plugin_active_start_specific(objProcess, true, selector);
    }
}

mainwp_staging_plugin_active_start_specific = function (pObj, bulk, selector) {
    var parent = pObj.closest('tr');
    var statusEl = parent.find('.updating');
    var slug = parent.attr('plugin-slug');

    var data = {
        action: 'mainwp_staging_active_plugin',
        stagingSiteID: parent.attr('website-id'),
        'plugins[]': [slug]
    }

    if (bulk) {
        staging_bulkCurrentThreads++;
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
            staging_bulkCurrentThreads--;
            staging_bulkFinishedThreads++;
            mainwp_staging_plugin_active_start_next(selector);
        }

    }, 'json');
    return false;
}

var staging_workingBox = '';

mainwp_staging_bulk_load_sites = function (pWhat, args) {
    var data = {
        action: 'mainwp_staging_load_sites',
        what: pWhat,
        _stagingNonce: mainwp_staging_loc.nonce
    };

    if (pWhat == 'general_settings' || pWhat == 'reset_defaults') {
        staging_workingBox = 'pb_staging_settings_tab_general';
    } else if (pWhat == 'advanced_settings') {
        staging_workingBox = 'pb_staging_settings_tab_advanced';
    } else if ('save_settings') {
        staging_workingBox = 'pb_staging_bulk_perform_content';
    }


    jQuery('#' + staging_workingBox).html('<div class="ui active inverted dimmer"><div class="ui text loader">Loading...</div></div>');

    jQuery.post(ajaxurl, data, function (response) {
        if (response) {
            jQuery('#' + staging_workingBox).html(response);
            staging_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
            if (staging_bulkTotalThreads > 0) {
                if (pWhat == 'save_settings') {
                    mainwp_staging_save_settings_start_next();
                }
            }
        } else {
            jQuery('#' + staging_workingBox).html('<div class="ui red message">' + __("Undefined error! Please try again. If the issue occurs again, please contact the MainWP support.") + '</div>');
        }
    })
}

mainwp_staging_save_settings_start_next = function () {
    while ((objProcess = jQuery('.siteItemProcess[status=queue]:first')) && (objProcess.length > 0) && (staging_bulkCurrentThreads < staging_bulkMaxThreads)) {
        objProcess.attr('status', 'processed');
        mainwp_staging_save_settings_start_specific(objProcess);
    }
    if (staging_bulkFinishedThreads > 0 && staging_bulkFinishedThreads == staging_bulkTotalThreads) {
        window.location.reload();
    }
}

mainwp_staging_save_settings_start_specific = function (objProcess) {
    var loadingEl = objProcess.find('i');
    var statusEl = objProcess.find('.status');
    staging_bulkCurrentThreads++;
    var data = {
        action: 'mainwp_staging_save_settings',
        stagingSiteID: objProcess.attr('site-id'),
        _stagingNonce: mainwp_staging_loc.nonce
    };

    statusEl.html('');
    loadingEl.show();
    jQuery.post(ajaxurl, data, function (response) {
        loadingEl.hide();
        if (response) {
            if (response.error) {
                statusEl.html('<i class="red times icon"></i>');
            } else if (response.result == 'success') {
                var msg = __('<i class="green check icon"></i>');
                statusEl.html(msg);
            } else {
                statusEl.html('<i class="red times icon"></i>');
            }
        } else {
            statusEl.html('<i class="red times icon"></i>');
        }

        staging_bulkCurrentThreads--;
        staging_bulkFinishedThreads++;
        mainwp_staging_save_settings_start_next();
    }, 'json');
}

"use strict";

var MainWP_WPStaging = (function ($) {
    var that = {
        isCancelled: false,
        isFinished: false,
        getLogs: false,
        time: 1,
        executionTime: false,
        progressBar: 0
    },
        cache = { elements: [] },
        timeout, ajaxSpinner;

    /**
     * Get / Set Cache for Selector
     * @param {String} selector
     * @returns {*}
     */
    cache.get = function (selector) {
        // It is already cached!
        if ($.inArray(selector, cache.elements) !== -1) {
            return cache.elements[selector];
        }

        // Create cache and return
        cache.elements[selector] = jQuery(selector);

        return cache.elements[selector];
    };

    /**
     * Refreshes given cache
     * @param {String} selector
     */
    cache.refresh = function (selector) {
        selector.elements[selector] = jQuery(selector);
    };

    /**
     * Show and Log Error Message
     * @param {String} message
     */
    var showError = function (message) {
        cache.get("#wpstg-try-again").css("display", "inline-block");
        cache.get("#wpstg-cancel-cloning").text("Reset");
        cache.get("#wpstg-cloning-result").text("Fail");
        cache.get("#wpstg-error-wrapper").show();
        cache.get("#wpstg-error-details")
            .show()
            .html(message);
        cache.get("#wpstg-removing-clone").removeClass("loading");
        //cache.get("#wpstg-loader").hide();
    };

    /**
     * Common Elements
     */
    var elements = function () {
        mainwp_staging_$workFlow
            .on("click", "#wpstg-start-cloning", function () { // Restart cloning process
                that.isCancelled = false;
                that.getLogs = false;
                that.progressBar = 0;
            });
        cloneActions();
    };


    /**
     * Clone actions
     */
    var cloneActions = function () {

        console.log('cloneActions');

        mainwp_staging_$workFlow
            // Cancel cloning
            .on("click", "#wpstg-cancel-cloning", function () {
                if (!confirm("Are you sure you want to cancel cloning process?")) {
                    return false;
                }

                var $this = $(this);

                $("#wpstg-try-again, #wpstg-home-link").hide();
                $this.prop("disabled", true);

                that.isCancelled = true;
                that.progressBar = 0;


                //$("#wpstg-cloning-result").text("Please wait...this can take up a while.");
                $("#wpstg-processing-status").text("Please wait...this can take up a while.");
                $("#wpstg-show-log-button").hide();

                //$this.parent().append(ajaxSpinner);

                cancelCloning();
            })
            // Cancel update cloning
            .on("click", "#wpstg-cancel-cloning-update", function () {
                if (!confirm("Are you sure you want to cancel clone updating process?")) {
                    return false;
                }

                var $this = $(this);

                $("#wpstg-try-again, #wpstg-home-link").hide();
                $this.prop("disabled", true);

                that.isCancelled = true;

                $("#wpstg-cloning-result").text("Please wait...this can take up a while.");
                $("#wpstg-show-log-button").hide();

                //$this.parent().append(ajaxSpinner);

                cancelCloningUpdate();
            })

            // Delete clone - confirmed
            .on("click", "#wpstg-remove-clone", function (e) {
                e.preventDefault();
                jQuery("#wpstg-removing-clone").addClass("loading");
                //jQuery("#wpstg-loader").show();
                mainwp_staging_deleteClone($(this).data("clone"));
            })
    };


    /**
     * Ajax Requests
     * @param {Object} data
     * @param {Function} callback
     * @param {String} dataType
     * @param {Boolean} showErrors
     */
    var ajax = function (data, callback, dataType, showErrors) {
        if ("undefined" === typeof (dataType)) {
            dataType = "json";
        }

        if (false !== showErrors) {
            showErrors = true;
        }

        $.ajax({
            url: ajaxurl,
            type: "POST",
            dataType: dataType,
            cache: false,
            data: data,
            error: function (xhr, textStatus, errorThrown) {
                console.log(xhr.status + ' ' + xhr.statusText + '---' + textStatus);
                console.log(textStatus);
                if (false === showErrors) {
                    return false;
                }
                showError(
                    "Fatal Unknown Error."
                );
            },
            success: function (data) {
                if ("function" === typeof (callback)) {
                    callback(data);
                }
            },
            statusCode: {
                404: function (data) {
                    showError(
                        "Something went wrong; can't find ajax request URL!"
                    );
                    // Try again after 10 seconds
                },
                500: function () {
                    showError(
                        "Something went wrong! Internal server error while processing the request!"
                    );
                }
            }
        });
    };

    /**
     * Next / Previous Step Clicks to Navigate Through Staging Job
     */
    var stepButtons = function () {

        // Next Button
        mainwp_staging_$workFlow.on("click", ".wpstg-next-step-link", function (e) {
            e.preventDefault();

            var $this = $(this);

            // Button is disabled
            if ($this.attr("disabled")) {
                return false;
            }

            // Add loading overlay
            mainwp_staging_$workFlow.addClass("loading");

            // Prepare data
            that.data = {
                action: 'mainwp_' + $this.data("action"), // mainwp_staging_update or mainwp_staging_cloning
                _stagingNonce: mainwp_staging_loc.nonce,
                stagingSiteID: staging_elementsCache.site_id
            };

            // Cloning data
            getCloningData();

            console.log(that.data);

            // Send ajax request
            ajax(
                that.data,
                function (response) {
                    if (response.length < 1) {
                        mainwp_staging_showError("Something went wrong, please try again");
                    }
                    //
                    // Styling of elements
                    mainwp_staging_$workFlow.removeClass("loading").html(response);
                    // Start cloning
                    that.startCloning();

                },
                "HTML"
            );
        })
            // Previous Button
            .on("click", ".wpstg-prev-step-link", function (e) {
                e.preventDefault();
                //cache.get("#wpstg-loader").removeClass('wpstg-finished');
                //cache.get("#wpstg-loader").hide();
                mainwp_staging_loadOverview(true);
                return false;
            });
    };

    /**
     * Get Included (Checked) Database Tables
     * @returns {Array}
     */
    var getIncludedTables = function () {
        var includedTables = [];

        $(".wpstg-db-table input:checked").each(function () {
            includedTables.push(this.name);
        });

        return includedTables;
    };
    /**
     * Get Excluded (Unchecked) Database Tables
     * @returns {Array}
     */
    //    var getExcludedTables = function ()
    //    {
    //        var excludedTables = [];
    //
    //        $(".wpstg-db-table input:not(:checked)").each(function () {
    //            excludedTables.push(this.name);
    //        });
    //
    //        return excludedTables;
    //    };

    /**
     * Get Included Directories
     * @returns {Array}
     */
    var getIncludedDirectories = function () {
        var includedDirectories = [];
        // new version.
        if ($('.wpstg-wp-core-dir').length > 0) {
            $(".wpstg-dir input:checked.wpstg-check-dir").each(function () {
                var $this = $(this);
                includedDirectories.push(encodeURIComponent($this.val()));
            });
            console.log('includedDirectories');
            console.log(includedDirectories);
            return includedDirectories;
        } else { // old version.
            $(".wpstg-dir input:checked.wpstg-root").each(function () {
                var $this = $(this);
                includedDirectories.push(encodeURIComponent($this.val()));
            });
            console.log('includedDirectories');
            console.log(includedDirectories);
            return includedDirectories;
        }
    };

    /**
     * Get Excluded Directories
     * @returns {Array}
     */
    var getExcludedDirectories = function () {
        var excludedDirectories = [];
        if ($('.wpstg-wp-core-dir').length > 0) {
            $(".wpstg-dir input:not(:checked).wpstg-check-dir").each(function () {
                var $this = $(this);
                excludedDirectories.push(encodeURIComponent($this.val()));
            });
            console.log('excludedDirectories');
            console.log(excludedDirectories);
            return excludedDirectories;
        } else { // old version.
            $(".wpstg-dir input:not(:checked).wpstg-root").each(function () {
                var $this = $(this);
                excludedDirectories.push(encodeURIComponent($this.val()));
            });
            console.log('excludedDirectories');
            console.log(excludedDirectories);
            return excludedDirectories;
        }
    };

    /**
     * Get Included Extra Directories
     * @returns {Array}
     */
    var getIncludedExtraDirectories = function () {
        var extraDirectories = [];

        if (!$("#wpstg_extraDirectories").val()) {
            return extraDirectories;
        }

        var extraDirectories = $("#wpstg_extraDirectories").val().split(/\r?\n/);
        console.log(extraDirectories);

        //excludedDirectories.push($this.val());

        return extraDirectories;
    };



    /**
     * Get Cloning Step Data
     */
    var getCloningData = function () {
        if ("mainwp_staging_cloning" !== that.data.action && "mainwp_staging_update" !== that.data.action) {
            return;
        }

        that.data.cloneID = $("#wpstg-new-clone-id").val() || new Date().getTime().toString();
        that.data.cloneName = $('#wpstg-new-clone-id').val() || that.data.cloneID; // Remove this to keep &_POST[] small otherwise mod_security will throw error 404

        // Remove this to keep &_POST[] small otherwise mod_security will throw erro 404
        //that.data.excludedTables = getExcludedTables();
        that.data.includedTables = getIncludedTables();
        that.data.includedDirectories = getIncludedDirectories();
        that.data.excludedDirectories = getExcludedDirectories();
        that.data.extraDirectories = getIncludedExtraDirectories();
        console.log(that.data);
    };

    /**
     * Cancel Cloning Process
     */
    var cancelCloning = function () {

        that.timer('stop');


        if (true === that.isFinished) {
            return true;
        }

        ajax(
            {
                action: "mainwp_staging_cancel_clone",
                stagingSiteID: staging_elementsCache.site_id,
                clone: that.data.cloneID,
                _stagingNonce: mainwp_staging_loc.nonce
            },
            function (response) {


                if (response && "undefined" !== typeof (response.delete) && response.delete === "finished") {
                    //cache.get("#wpstg-loader").hide();
                    // Load overview
                    mainwp_staging_loadOverview(true);
                    return;
                }

                if (true !== response) {
                    // continue
                    cancelCloning();
                    return;
                }

                // Load overview
                mainwp_staging_loadOverview(true);
            }
        );
    };
    /**
     * Cancel Cloning Process
     */
    var cancelCloningUpdate = function () {
        if (true === that.isFinished) {
            return true;
        }

        ajax(
            {
                action: "mainwp_staging_cancel_update",
                stagingSiteID: staging_elementsCache.site_id,
                clone: that.data.cloneID,
                _stagingNonce: mainwp_staging_loc.nonce
            },
            function (response) {

                if (response && "undefined" !== typeof (response.delete) && response.delete === "finished") {
                    // Load overview
                    mainwp_staging_loadOverview();
                    return;
                }

                if (true !== response) {
                    // continue
                    cancelCloningUpdate();
                    return;
                }

                // Load overview
                mainwp_staging_loadOverview();
            }
        );
    };

    /**
     * Scroll the window log to bottom
     * @returns void
     */
    var logscroll = function () {
        var $div = cache.get("#wpstg-log-details");
        if ("undefined" !== typeof ($div[0])) {
            $div.scrollTop($div[0].scrollHeight);
        }
    }

    /**
     * Append the log to the logging window
     * @param string log
     * @returns void
     */
    var getLogs = function (log) {
        if (log != null && "undefined" !== typeof (log)) {
            if (log.constructor === Array) {
                $.each(log, function (index, value) {
                    if (value === null) {
                        return;
                    }
                    if (value.type === 'ERROR') {
                        cache.get("#wpstg-log-details").append('<span style="color:red;">[' + value.type + ']</span>-' + '[' + value.date + '] ' + value.message + '</br>');
                    } else {
                        cache.get("#wpstg-log-details").append('[' + value.type + ']-' + '[' + value.date + '] ' + value.message + '</br>');
                    }
                })
            } else {
                cache.get("#wpstg-log-details").append('[' + log.type + ']-' + '[' + log.date + '] ' + log.message + '</br>');
            }
        }
        logscroll();
    };

    /**
     * Check diskspace
     * @returns string json
     */
    //    var checkDiskSpace = function () {
    //        cache.get("#wpstg-check-space").on("click", function (e) {
    //            //cache.get("#wpstg-loader").show();
    //            console.log("check disk space");
    //            ajax(
    //                    {
    //                        action: "wpstg_check_disk_space",
    //                        nonce: wpstg.nonce
    //                    },
    //            function (response)
    //            {
    //                if (false === response)
    //                {
    //                    cache.get("#wpstg-clone-id-error").text('Can not detect disk space').show();
    //                    //cache.get("#wpstg-loader").hide();
    //                    return;
    //                }
    //
    //                // Not enough disk space
    //                cache.get("#wpstg-clone-id-error").text('Available free disk space ' + response.freespace + ' | Estimated necessary disk space: ' + response.usedspace).show();
    //                //cache.get("#wpstg-loader").hide();
    //            },
    //                    "json",
    //                    false
    //                    );
    //        });
    //
    //    }


    /**
     * Count up processing execution time
     * @param string status
     * @returns html
     */
    that.timer = function (status) {

        if (status === 'stop') {
            var time = that.time;
            that.time = 1;
            clearInterval(that.executionTime);
            return that.convertSeconds(time);
        }


        that.executionTime = setInterval(function () {
            if (null !== document.getElementById('wpstg-processing-timer')) {
                document.getElementById('wpstg-processing-timer').innerHTML = 'Elapsed Time: ' + that.convertSeconds(that.time);
            }
            that.time++;
            if (status === 'stop') {
                that.time = 1;
                clearInterval(that.executionTime);
            }
        }, 1000);
    };
    /**
     * Convert seconds to hourly format
     * @param int seconds
     * @returns string
     */
    that.convertSeconds = function (seconds) {
        var date = new Date(null);
        date.setSeconds(seconds); // specify value for SECONDS here
        return date.toISOString().substr(11, 8);
    }


    /**
     * Start Cloning Process
     * @type {Function}
     */
    that.startCloning = (function () {

        // Register function for checking disk space
        mainwp_staging_checkDiskSpace();

        var statusEl = mainwp_staging_$working.find('.status');
        var wpstgLoaderEl = mainwp_staging_$working.find('div.mwp-loading');

        if ("mainwp_staging_cloning" !== that.data.action && "mainwp_staging_update" !== that.data.action) {
            return;
        }

        // Start the process
        start();


        // Functions
        // Start
        function start() {

            console.log("Starting cloning process...");

            //cache.get("#wpstg-loader").show();

            // Clone Database
            setTimeout(function () {
                //cloneDatabase();
                processing();
            }, staging_elementsCache.cpuLoad);

            that.timer('start');

        }



        /**
         * Start ajax processing
         * @returns string
         */
        var processing = function () {

            if (true === that.isCancelled) {
                return false;
            }

            //console.log("Start ajax processing");

            // Show loader gif
            //cache.get("#wpstg-loader").show();
            //cache.get(".wpstg-loader").show();

            // Show logging window
            cache.get('#wpstg-log-details').show();

            MainWP_WPStaging.ajax(
                {
                    action: "mainwp_staging_clone_database",
                    stagingSiteID: staging_elementsCache.site_id,
                    _stagingNonce: mainwp_staging_loc.nonce,
                    //clone: cloneID,
                    excludedTables: mainwp_staging_getExcludedTables(),
                    includedDirectories: getIncludedDirectories(),
                    excludedDirectories: getExcludedDirectories(),
                    extraDirectories: getIncludedExtraDirectories()
                },
                function (response) {
                    console.log(response);
                    // Undefined Error
                    if (false === response) {
                        showError("Unknown Error, please try again");

                        return;
                    }

                    if (true === response) {
                        processing();
                        return;
                    }

                    // Throw Error
                    if ("undefined" !== typeof (response.error) && response.error) {
                        console.log(response.error);
                        showError("Something went wrong! Error: " + response.error + ". Please try again.");
                        return;
                    }

                    // Add Log messages
                    if ("undefined" !== typeof (response.last_msg) && response.last_msg) {
                        getLogs(response.last_msg);
                    }
                    // Continue processing
                    if (false === response.status) {
                        progressBar(response);

                        setTimeout(function () {
                            console.log('continue processing');
                            //cache.get("#wpstg-loader").show();
                            processing();
                        }, staging_elementsCache.cpuLoad);

                    } else if (true === response.status && 'finished' !== response.status) {
                        console.log('Processing...');
                        progressBar(response, true);
                        processing();
                    } else if ('finished' === response.status || ("undefined" !== typeof (response.job_done) && response.job_done)) {
                        finish(response);
                    }
                    ;
                },
                "json",
                false
            );
        };

        // Finish
        function finish(response) {

            if (true === that.getLogs) {
                getLogs();
            }

            progressBar(response);

            // Add Log
            if ("undefined" !== typeof (response.last_msg)) {
                getLogs(response.last_msg);
            }


            console.log("Cloning process finished");

            var $link1 = jQuery("#wpstg-clone-url-1");
            var $link = jQuery("#wpstg-clone-url");

            jQuery("#wpstg_staging_name").html(that.data.cloneID);
            jQuery("#wpstg-finished-result").show();

            jQuery("#wpstg-success-notice").find('.wpstg-clone-name').html(response.blogInfoName); // to fix name
            jQuery("#wpstg-success-notice").find('img').remove(); // remove the img

            if (null !== document.getElementById('wpstg-success-notice')) {
                var content = jQuery("#wpstg-success-notice").html();
                jQuery("#wpstg-success-notice").html(content.replace('You will notice this new name in the admin bar:', '')); // replace the text
            }

            jQuery("#wpstg-cancel-cloning").prop("disabled", true);
            cache.get('#wpstg-cancel-cloning').hide();
            cache.get("#wpstg-cancel-cloning-update").prop("disabled", true);
            cache.get("#wpstg-cancel-cloning-update").hide();

            $link1.attr("href", $link1.attr("href") + '/' + response.directoryName);
            $link1.append('/' + response.directoryName);
            $link.attr("href", $link.attr("href") + '/' + response.directoryName);
            jQuery("#wpstg-remove-clone").data("clone", that.data.cloneID);
            cache.get("#wpstg-processing-header").html('Processing Complete');
            that.isFinished = true;
            that.timer('stop');
            wpstgLoaderEl.hide();

            mainwp_staging_addsite(response.url, that.data.cloneID);
            return false;
        }
        /**
         * Add percentage progress bar
         * @param object response
         * @returns {Boolean}
         */
        var progressBar = function (response, restart) {
            if ("undefined" === typeof (response.percentage))
                return false;

            if (response.job === 'database') {
                cache.get("#wpstg-progress-db").width(response.percentage * 0.2 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 1 of 4 Cloning Database Tables...');
            }

            if (response.job === 'SearchReplace') {
                cache.get("#wpstg-progress-db").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-db").html('1. Database');
                cache.get("#wpstg-progress-sr").width(response.percentage * 0.1 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 2 of 4 Preparing Database Data...');
            }

            if (response.job === 'directories') {
                cache.get("#wpstg-progress-sr").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-sr").html('2. Data');
                cache.get("#wpstg-progress-dirs").width(response.percentage * 0.1 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 3 of 4 Getting files...');
            }
            if (response.job === 'files') {
                cache.get("#wpstg-progress-dirs").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-dirs").html('3. Files');
                cache.get("#wpstg-progress-files").width(response.percentage * 0.6 + '%').html(response.percentage + '%');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Step 4 of 4 Copy files...');
            }
            if (response.job === 'finish') {
                cache.get("#wpstg-progress-files").css('background-color', '#3bc36b');
                cache.get("#wpstg-progress-files").html('4. Copy Files');
                cache.get("#wpstg-processing-status").html(response.percentage.toFixed(0) + '%' + ' - Cloning Process Finished');
            }
        }
    });


    /**
     * Initiation
     * @type {Function}
     */
    that.init = (function () {
        //        //loadOverview();
        elements();
        //        //startUpdate();
        stepButtons();
        //        //tabs();
        //        //optimizer();
    });

    /**
     * Ajax call
     * @type {ajax}
     */
    that.ajax = ajax;
    that.showError = showError;
    that.getLogs = getLogs;
    //that.loadOverview = loadOverview;

    return that;
})(jQuery);
