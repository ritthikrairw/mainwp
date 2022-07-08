// For all generic errors
var mainwp_backwpup_generic_error = function (message) {
    return _mwbwpupt("Generic error: ") + message;
};

var mainwp_backwpup_display_error = function (message) {
    mainwp_backwpup_show_error('backwpup_error', message);
};

var mainwp_backwpup_clear_error = function () {
    mainwp_backwpup_hide_error('backwpup_error');
    mainwp_backwpup_hide_error('backwpup_message');
}

var mainwp_backwpup_display_message = function (message) {
    mainwp_backwpup_show_error('backwpup_message', message);
};

/**
 * Required
 */
mainwp_backwpup_show_error = function (id, text, append) {
    if (append == true) {
        var currentHtml = jQuery('#' + id).html();
        if (currentHtml == null) currentHtml = "";
        if (currentHtml.indexOf('<p>') == 0) {
            currentHtml = currentHtml.substr(3, currentHtml.length - 7);
        }
        if (currentHtml != '') {
            currentHtml += '<br />' + text;
        }
        else {
            currentHtml = text;
        }
        jQuery('#' + id).html('<p>' + currentHtml + '</p>');
    }
    else {
        jQuery('#' + id).html('<p>' + text + '</p>');
    }
    jQuery('#' + id).show();
    // automatically scroll to error message if it's not visible
    var scrolltop = jQuery(window).scrollTop();
    var off = jQuery('#' + id).offset();
    if (scrolltop > off.top - 40)
        jQuery('html, body').animate({
            scrollTop:off.top - 40
        }, 1000, function () {
            //shake_element('#' + id)
        });
    else {
        //shake_element('#' + id); // shake the error message to get attention :)
    }

};
mainwp_backwpup_hide_error = function (id) {
    var idElement = jQuery('#' + id);
    idElement.html("");
    idElement.hide();
};
/**
 * Custom options for ng-table
 **/
var mainwp_backwpup_custom_generate_pages = function(currentPage, totalItems, pageSize) {
    var pages = [];
    numPages = Math.ceil(totalItems / pageSize);
    pages.push({
        type: 'first',
        number: 1
    });
    pages.push({
        type: 'prev',
        number: Math.max(1, currentPage - 1)
    });
    pages.push({
        type: 'page',
        number: (((currentPage-1)*pageSize)+1)+' to '+(currentPage*pageSize > totalItems ? totalItems : currentPage*pageSize)+' of '+totalItems+' rows'
    });
    pages.push({
        type: 'next',
        number: Math.min(numPages, currentPage + 1)
    });
    pages.push({
        type: 'last',
        number: numPages,
        active: currentPage !== numPages
    });
    return pages;
};

/**
 * Check each ajax request using this function
 * We now if something goes wrong
 **/
var mainwp_backwpup_check_error_in_request = function (data, message, display) {
    if (data.constructor === {}.constructor) {
        if ('success' in data) {
            return true;
        } else if ('error' in data) {
            if (display === undefined) {
                mainwp_backwpup_display_error(data.error);
                return false;
            } else {
                return data.error
            }
        } else {
            if (display === undefined) {
                mainwp_backwpup_display_error(mainwp_backwpup_generic_error(message));
                return false;
            } else {
                return mainwp_backwpup_generic_error(message);
            }
        }
    }
     else {
        if (display === undefined) {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error(message));
            return false;
        } else {
            return mainwp_backwpup_generic_error(message);
        }
    }
};

// MainWp BackWpUp translation
function _mwbwpupt(text, _var1, _var2, _var3) {
    if (text == undefined || text == '') return text;
    var strippedText = text.replace(/ /g, '_');
    strippedText = strippedText.replace(/[^A-Za-z0-9_]/g, '');

    if (strippedText == '') return text.replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);

    if (backwpup_extension_translations == undefined) return text.replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);
    if (backwpup_extension_translations[strippedText] == undefined) return text.replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);

    return backwpup_extension_translations[strippedText].replace('%1', _var1).replace('%2', _var2).replace('%3', _var3);
    return text;
}

// We need this in order to $_POST and $_GET work properly in PHP
angular.module('httpPostFix', [], function ($httpProvider) {
    // Use x-www-form-urlencoded Content-Type
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';

    // Override $http service's default transformRequest
    $httpProvider.defaults.transformRequest = [function (data) {
        /**
         * The workhorse; converts an object to x-www-form-urlencoded serialization.
         * @param {Object} obj
         * @return {String}
         */
        var param = function (obj) {
            var query = '';
            var name, value, fullSubName, subName, subValue, innerObj, i;

            for (name in obj) {
                value = obj[name];

                if (value instanceof Array) {
                    for (i = 0; i < value.length; ++i) {
                        subValue = value[i];
                        fullSubName = name + '[' + i + ']';
                        innerObj = {};
                        innerObj[fullSubName] = subValue;
                        query += param(innerObj) + '&';
                    }
                }
                else if (value instanceof Object) {
                    for (subName in value) {
                        subValue = value[subName];
                        fullSubName = name + '[' + subName + ']';
                        innerObj = {};
                        innerObj[fullSubName] = subValue;
                        query += param(innerObj) + '&';
                    }
                }
                else if (value !== undefined && value !== null) {
                    query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
                }
            }

            return query.length ? query.substr(0, query.length - 1) : query;
        };

        return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
    }];
});

// From https://github.com/exceptionless/angular-filters/blob/master/src/bytes/bytes-filter.js
angular.module('filters', []).filter('bytes', function() {
    return function(bytes, precision) {
        if (isNaN(parseFloat(bytes)) || !isFinite(bytes)) return '-';
        if (typeof precision === 'undefined') precision = 1;
        var units = ['bytes', 'kB', 'MB', 'GB', 'TB', 'PB'],
            number = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, Math.floor(number))).toFixed(precision) +  ' ' + units[number];
    }
});


var app = angular.module('ngBackWPupApp', ['ngTable', 'httpPostFix', 'ngSanitize', 'filters']);

app.factory("backwpupRequest", function ($http) {
    return {
        // Get all values for tables
        getAll: function (type, website_id) {
            return $http.post(ajaxurl, {
                'action': (type == 'global_jobs' ? 'mainwp_backwpup_contact_with_root' : 'mainwp_backwpup_contact_with_child'),
                'method': (type == 'global_jobs' ? 'global_jobs' : 'backwpup_tables'),
                'website_id': website_id,
                'wp_nonce': (type == 'global_jobs' ? backwpup_extension_security_nonce['contact_with_root'] : backwpup_extension_security_nonce['contact_with_child']),
                'type': type
            }).error(function () {
                mainwp_backwpup_display_error(mainwp_backwpup_generic_error("backwpupRequest.error"));
            });
        }
    };
});

app.controller('ngBackWPupController', function ($scope, $http, $filter, $q, $sanitize, $timeout, ngTableParams, backwpupRequest) {
    if (typeof mainwp_backwpup_backup_jobs_ids != 'undefined') {
        $scope.backup_jobs_ids = mainwp_backwpup_backup_jobs_ids;
    }

    if (typeof mainwp_backwpup_backup_global_jobs_ids != 'undefined') {
        $scope.backup_global_jobs_ids = mainwp_backwpup_backup_global_jobs_ids;
    }

    if (typeof  mainwpbackwpup_job_file_folders != 'undefined') {
        $scope.scope_job_file_folders = mainwpbackwpup_job_file_folders;
    }

    if (typeof backwpup_dbdumpexclude != 'undefined') {
        //$scope.scope_backwpup_dbdumpexclude = backwpup_dbdumpexclude;
    }

    $scope.first_tab_click = {};

    $scope.abort_backup_now = 0;

    $scope.get_information_loading = 0;

    $scope.scope_wizard_system_scan = 0;

    $scope.get_child_tables_loading = 0;

    $scope.backup_now_temporary_website_id = 0;

    var data_table_logs = null;
    var data_table_backups = null;
    var data_table_jobs = null;

    $scope.first_tab_click = {};
    $scope.scope_table_backups_global_counter = 0;
    $scope.scope_table_backups_global_div = 0;

    $scope.syncing_message_settings_back = 0;
    $scope.syncing_message_job_back = 0;

    angular.element('#ngBackWPupAppId').show()

    $scope.select_tab = function (setTab) {
        mainwp_backwpup_clear_error();
        $scope.tab = setTab;

        // Few actions are fired only on first click
        if ($scope.first_tab_click[setTab] != undefined) {
            return;
        }

        switch (setTab) {
            case 'backups':
                if (backwpup_website_id == 0) {
                    if (backwpup_backups_website_array.length > 0) {
                        var get_all_wrapper_global_promise = $scope.get_all_wrapper_global(0,'backups');

                        for (i = 1; i < backwpup_backups_website_array.length; ++i) {
                            get_all_wrapper_global_promise = get_all_wrapper_global_promise.then(function (ii) {
                                return $scope.get_all_wrapper_global(ii, 'backups');
                            });
                        }

                        get_all_wrapper_global_promise.then(function() {
                            if (data_table_backups.length == 0) {
                                data_table_backups = [{'filename': 'No backups'}];
                                $scope.table_backups.reload();
                            }
                            $scope.scope_table_backups_global_div = 1;
                        });
                        //$scope.init_datatable_backups();
                    }
                } else {
                    $scope.reload_table_backups();                    
                }
            break;

            case 'logs':
                $scope.reload_table_logs();
                break;

            case 'informations':
                $scope.get_information();
                break;

            case 'jobs':
                $scope.reload_table_jobs();

                break;
        }

        $scope.first_tab_click[setTab] = 1;
    };

    $scope.is_selected = function (checkTab) {
        return $scope.tab === checkTab;
    };

    $scope.get_all_wrapper_global = function (i, type) {
        var deferred = $q.defer();
        $scope.scope_table_backups_global_counter += 1;
        backwpupRequest.getAll(type, backwpup_backups_website_array[i]['id']).then(function (d) {
            d = d.data;
            if (mainwp_backwpup_check_error_in_request(d, "get_all_wrapper_global")) {
                if (data_table_backups == null) {
                    data_table_backups = d.data.response;
                    $scope.table_backups = new ngTableParams({
                            page: 1,
                            count: 10,
                            sorting: {
                                name: 'asc'
                            },
                            filter: {
                                name: undefined
                            }
                        }, filter_table_backups()
                    );
                } else {
                    jQuery.merge(data_table_backups, d.data.response);
                    $scope.table_backups.reload();
                }
                //$scope.init_datatable_backups();
                mainwp_backwpup_clear_error();
            }
            deferred.resolve(i+1);                      
            if ( $scope.scope_table_backups_global_counter == backwpup_backups_website_array.length ) {
                $scope.init_datatable_backups();
            }

        });

         return deferred.promise;
    };

    
    $scope.init_datatable_jobs = function () {
        jQuery(document).ready(function () {
            jQuery('#backwpup-data-table-jobs').DataTable( {
                    "pagingType": "full_numbers",
                    "order": [],
                    "language": { "emptyTable": "No jobs." },
                    "columnDefs": [ {
                        "targets": 'no-sort',
                        "orderable": false
                    } ],
                    
            } );                                
        });
    };    

    $scope.init_datatable_backups = function () {
        jQuery(document).ready(function () {
            jQuery("#backwpup-data-table-backups").DataTable().destroy(); // to fix.
            jQuery('#backwpup-data-table-backups').DataTable( {
                    "pagingType": "full_numbers",
                    "order": [],
                    "language": { "emptyTable": "No backups." },
                    "columnDefs": [ {
                        "targets": 'no-sort',
                        "orderable": false
                    } ],                    
            } );                                
        });
    };  

    $scope.init_datatable_logs = function () {
        jQuery(document).ready(function () {
            jQuery('#backwpup-data-table-logs').DataTable( {
                    "pagingType": "full_numbers",
                    "order": [],
                    "language": { "emptyTable": "No logs." },
                    "columnDefs": [ {
                        "targets": 'no-sort',
                        "orderable": false
                    } ],                   
            } );                                
        });
    };  

    $scope.synchronize_global_settings = function () {
        mainwp_backwpup_clear_error();
        $scope.global_settings_ids = [];

        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_synchronize_global_settings',
            'wp_nonce': backwpup_extension_security_nonce['synchronize_global_settings']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "synchronize_global_settings")) {
                $scope.global_settings_ids = d.data.ids;
                $scope.global_settings_urls = d.data.urls;

                angular.element(document.getElementById('syncing_message')).empty();

                if ($scope.global_settings_ids.length == 0) {
                    angular.element(document.getElementById('syncing_message')).append('<div class="ui yellow message">'+_mwbwpupt('No websites to synchronize')+'</div>');
                    return;
                }

                //jQuery("#syncing_current").html('0');
                //jQuery("#syncing_total").html($scope.global_settings_ids.length);
                //jQuery('#syncing_progress').progressbar({value: 0, max: $scope.global_settings_ids.length});
                //jQuery('#syncing_progress_text').show();

                var global_edit_promise = $scope.synchronize_global_settings_step_2(0);

                for (i = 1; i < $scope.global_settings_ids.length; ++i) {
                    global_edit_promise = global_edit_promise.then(function (ii) {
                        return $scope.synchronize_global_settings_step_2(ii);
                    });
                }

                global_edit_promise.then(function() {
                    $scope.syncing_message_settings_back = 1;
                    angular.element(document.getElementById('syncing_message')).append('<div class="ui green message">'+_mwbwpupt('Process completed successfully.')+'</div>');
                });

            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("synchronize_global_settings.error"));
        });
    };

    $scope.synchronize_global_settings_step_2 = function(i) {
        var deferred = $q.defer();

        angular.element(document.getElementById('syncing_message')).append('<div class="item">'+$sanitize($scope.global_settings_urls[i])+' <span class="right floated" id="synchronize_global_settings_tr_id_'+i+'" ><i class="notched circle loading icon"></i></span></div>');

        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_synchronize_global_settings_step_2',
            'website_id': $scope.global_settings_ids[i],
            'wp_nonce': backwpup_extension_security_nonce['synchronize_global_settings_step_2']
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "synchronize_global_settings_step_2", 1);
            if (message !== true) {
                angular.element(document.getElementById('synchronize_global_settings_tr_id_'+i)).html('<i class="red times icon"></i>');

            } else {
                angular.element(document.getElementById('synchronize_global_settings_tr_id_'+i)).html('<i class="green check icon"></i>');
            }

            //jQuery("#syncing_current").html(i+1);
            //jQuery('#syncing_progress').progressbar({value: i+1});

            deferred.resolve(i+1);
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("synchronize_global_settings_step_2.error"));
            deferred.resolve(i+1);
        });

        return deferred.promise;
    };


    $scope.get_all_wrapper = function (type) {
        mainwp_backwpup_clear_error();

        backwpupRequest.getAll(type, backwpup_website_id).then(function (d) {
            d = d.data;
            if (mainwp_backwpup_check_error_in_request(d, "get_all_wrapper")) {
                if (type == 'logs') {
                    if (d.data.response.length == 0) {
                        data_table_logs = [{'name': 'No logs'}];
                    }
                    else {
                        data_table_logs = d.data.response;
                    }

                    if ($scope.table_logs == null) {
                        $scope.table_logs = new ngTableParams({
                                page: 1,
                                count: 1000,
                                sorting: {
                                    name: 'asc'
                                },
                                filter: {
                                    name: undefined
                                }
                            }, filter_table_logs()
                        );
                    } else {
                        $scope.table_logs.reload();
                    }
                    $scope.init_datatable_logs();
                } else if (type == 'backups') {
                    if (d.data.response.length == 0) {
                        data_table_backups = [{'filename': 'No backups'}];
                    } else {
                        data_table_backups = d.data.response;
                    }

                    if ($scope.table_backups == null) {
                        $scope.table_backups = new ngTableParams({
                                page: 1,
                                count: 1000,
                                sorting: {
                                    name: 'asc'
                                },
                                filter: {
                                    name: undefined
                                }
                            }, filter_table_backups()
                        );                       
                    } else {
                        $scope.table_backups.reload();
                    }
                    //$scope.init_datatable_backups();
                } else if (type == 'jobs' || type == 'global_jobs') {
                    if (d.data.response.length == 0) {
                        data_table_jobs = [{'name': 'No jobs'}];
                    } else {
                        data_table_jobs = d.data.response;
                    }

                    if ($scope.table_jobs == null) {
                        $scope.table_jobs = new ngTableParams({
                                page: 1,
                                count: 1000,
                                sorting: {
                                    name: 'asc'
                                },
                                filter: {
                                    name: undefined
                                }
                            }, filter_table_jobs()
                        );
                    }
                    else {
                        $scope.table_jobs.reload();
                    }
                    $scope.init_datatable_jobs();
                }
            }
        });
    };

 $scope.select_tab_2 = function (setTab) {
        mainwp_backwpup_clear_error();
        $scope.tab_2 = setTab;

        if ($scope.first_tab_click[setTab] != undefined) {
            return;
        }

        switch (setTab) {
            case 'DBDUMP':
                jQuery('input[name="dbdumpdbhost"]').change(function() {$scope.get_child_tables();});
                jQuery('input[name="dbdumpdbuser"]').change(function() {$scope.get_child_tables();});
                jQuery('input[name="dbdumpdbpassword"]').change(function() {$scope.get_child_tables();});

                $scope.get_child_tables(1);
                break;

            case 'FILE':
                $scope.get_job_files();
                break;

            case 'S3':
                jQuery('input[name="s3accesskey"]').change(function() {$scope.get_s3_buckets();});
                jQuery('input[name="s3secretkey"]').change(function() {$scope.get_s3_buckets();});
                jQuery('input[name="s3base_url"]').change(function() {$scope.get_s3_buckets();});
                jQuery('#s3region').change(function() {$scope.get_s3_buckets()});

                $scope.get_s3_buckets();
                break;

            case 'MSAZURE':
                jQuery('#msazureaccname').change(function() {$scope.get_azure_container();});
                jQuery('#msazurekey').change(function() {$scope.get_azure_container();});

                $scope.get_azure_container();
                break;

            case 'RSC':
                jQuery('#rscregion').change(function() {$scope.get_rsc_container();});
                jQuery('#rscusername').change(function() {$scope.get_rsc_container();});
                jQuery('#rscapikey').change(function() {$scope.get_rsc_container();});

                $scope.get_rsc_container();
                break;

            case 'SUGARSYNC':
                jQuery('#sugaremail').change(function() {$scope.get_sugarsync_folder();});
                jQuery('#sugarpass').change(function() {$scope.get_sugarsync_folder();});
                $scope.get_sugarsync_folder();
                break;

            case 'GLACIER':
                jQuery('input[name="glacieraccesskey"]').change(function() {$scope.get_glacier_vault();});
                jQuery('input[name="glaciersecretkey"]').change(function() {$scope.get_glacier_vault();});
                jQuery('#glacierregion').change(function() {$scope.get_glacier_vault();});

                $scope.get_glacier_vault();
            break;

            case 'GDRIVE':

            break;
        }

        $scope.first_tab_click[setTab] = 1;
    };

    $scope.is_selected_2 = function (checkTab) {
        return $scope.tab_2 === checkTab;
    };

    $scope.get_job_files = function() {
        if (backwpup_website_id == 0) {
            return;
        }
        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_get_job_files',
            'website_id': backwpup_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "get_job_files")) {
                $scope.scope_job_main = d.data.main;
                $scope.scope_job_files = d.data.folders;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_job_files.error"));
        });
    };

    $scope.destination_email_test_email = function() {
        $scope.destination_email_message = "";
        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_destination_email_check_email',
            'website_id': backwpup_website_id,
            'emailaddress': $scope.destination_email_emailaddress,
            'emailsndemail': $scope.destination_email_emailsndemail,
            'emailmethod': $scope.destination_email_emailmethod,
            'emailsendmail': $scope.destination_email_emailsendmail,
            'emailsndemailname': $scope.destination_email_emailsndemailname,
            'emailhost': $scope.destination_email_emailhost,
            'emailhostport': $scope.destination_email_emailhostport,
            'emailsecure': $scope.destination_email_emailsecure,
            'emailuser': $scope.destination_email_emailuser,
            'emailpass': $scope.destination_email_emailpass,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "destination_email_test_email")) {
                $scope.destination_email_message = d.data.message;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("destination_email_test_email.error"));
        });
    };

    $scope.get_s3_buckets = function() {
        mainwp_backwpup_clear_error();
        $scope.scope_s3_bucket_message = "";
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_get_buckets',
            'type': 's3',
            'wp_nonce': backwpup_extension_security_nonce['get_buckets'],
            's3accesskey': jQuery('input[name="s3accesskey"]').val(),
            's3secretkey': jQuery('input[name="s3secretkey"]').val(),
            's3base_url': jQuery('input[name="s3base_url"]').val(),
            's3region': jQuery('#s3region').val(),
            s3base_region      : jQuery( 'input[name="s3base_region"]' ).val(),
            s3base_version      : jQuery( 'input[name="s3base_version"]' ).val(),
            s3base_signature      : jQuery( 'input[name="s3base_signature"]' ).val(),
            s3base_multipart      : jQuery( 'input[name="s3base_multipart"]' ).is(':checked'),
            s3base_pathstyle      : jQuery( 'input[name="s3base_pathstyle"]' ).is(':checked')
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "get_s3_buckets", 1);
            if (message !== true) {
                $scope.scope_s3_bucket_message = message;
            } else {
                $scope.scope_s3_buckets = d.data.data;
                jQuery('#s3bucket').dropdown();
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_s3_buckets.error"));
        });
    };

    $scope.get_azure_container = function() {
        mainwp_backwpup_clear_error();
        $scope.scope_azure_bucket_message = "";
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_get_buckets',
            'type': 'azure',
            'wp_nonce': backwpup_extension_security_nonce['get_buckets'],
            'msazureaccname': jQuery('#msazureaccname').val(),
            'msazurekey': jQuery('#msazurekey').val()
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "get_azure_container", 1);
            if (message !== true) {
                $scope.scope_azure_bucket_message = message;
            } else {
                $scope.scope_azure_buckets = d.data.data;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_azure_container.error"));
        });
    };

    $scope.get_rsc_container = function() {
        mainwp_backwpup_clear_error();
        $scope.scope_rsc_bucket_message = "";
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_get_buckets',
            'type': 'rsc',
            'wp_nonce': backwpup_extension_security_nonce['get_buckets'],
            'rscusername': jQuery('#rscusername').val(),
            'rscapikey': jQuery('#rscapikey').val(),
            'rscregion': jQuery('#rscregion').val()
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "get_rsc_container", 1);
            if (message !== true) {
                $scope.scope_rsc_bucket_message = message;
            } else {
                $scope.scope_rsc_buckets = d.data.data;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_rsc_container.error"));
        });
    };

    $scope.get_sugarsync_folder = function() {
        mainwp_backwpup_clear_error();
        $scope.scope_sugar_folder_message = "";
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_get_buckets',
            'type': 'sugar',
            'wp_nonce': backwpup_extension_security_nonce['get_buckets'],
            'sugaremail': jQuery('#sugaremail').val(),
            'sugarpass': jQuery('#sugarpass').val(),
            'website_id' : backwpup_website_id

        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "get_sugarsync_folder", 1);
            if (message !== true) {
                $scope.scope_sugar_folder_message = message;
            } else {
                $scope.scope_sugar_folders = d.data.data;
                jQuery("#sugarrefreshtoken").val($sanitize(d.data.token));
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_sugarsync_folder.error"));
        });
    };

    $scope.get_glacier_vault = function() {
        mainwp_backwpup_clear_error();
        $scope.scope_glacier_buckets = "";
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_get_buckets',
            'type': 'glacier',
            'wp_nonce': backwpup_extension_security_nonce['get_buckets'],
            'glacieraccesskey': jQuery('input[name="glacieraccesskey"]').val(),
            'glaciersecretkey': jQuery('input[name="glaciersecretkey"]').val(),
            'glacierregion': jQuery('#glacierregion').val()
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "get_glacier_vault", 1);
            if (message !== true) {
                $scope.scope_glacier_bucket_message = message;
            } else {
                $scope.scope_glacier_buckets = d.data.data;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_glacier_vault.error"));
        });
    };

    function clear_backup_now() {
        mainwp_backwpup_clear_error();

        jQuery("#abortbutton").show();
        jQuery('#logpos').val('');
        $scope.backup_now_errors = "0";
        $scope.backup_now_warnings = '0';
        $scope.backup_now_progressstep = "0%";
        $scope.backup_now_progresssteps = "0%";
        $scope.backup_now_runtime = "0";
        $scope.backup_now_onstep = "";
        $scope.backup_now_lastmsg = "";
        angular.element(document.getElementById('backup_now_last_error_msg')).html('');
        $scope.abort_backup_now = 0;
        jQuery('#runningjob').show();
    };

    $scope.backup_now_global = function(job_id) {
        clear_backup_now();

        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_root',
            'method': 'backup_now_global',
            'website_id': backwpup_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_root'],
            'job_id': job_id
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "backup_now_global")) {
                $scope.backup_now_global_ids = d.data.ids;
                $scope.backup_now_global_urls = d.data.urls;
                $scope.backup_now_job_id = d.data.jobs;
                $scope.backup_now_is_global = true;
                $scope.backup_now_is_end = false;

                if ($scope.backup_now_global_ids.length == 0) {
                    angular.element(document.getElementById('backup_now_global_message')).append('<p>'+_mwbwpupt('No websites to backup')+'</p>');
                    return;
                }

                $scope.backup_now('1');
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("backup_now_global.error"));
        });
    };

    $scope.backup_now_prepare = function(job_id, website_id) {
        $scope.backup_now_global_ids = [website_id];
        $scope.backup_now_global_urls = [];
        $scope.backup_now_job_id = [job_id];
        $scope.backup_now_is_global = false;
        $scope.backup_now_is_end = false;
        $scope.backup_now('1');
    };

    $scope.backup_now = function(first) {
        clear_backup_now();
        if ($scope.backup_now_is_end) {
            return;
        }

        if (first == '1') {
        	angular.element(document.getElementById('backup_now_global_message')).html('');
        }

        if ($scope.backup_now_job_id.length > 0 && $scope.backup_now_global_ids.length > 0) {
            website_id = $scope.backup_now_global_ids[0];
            $scope.backup_now_temporary_website_id = website_id;
            $scope.backup_now_global_ids.shift();

            var current_job_id = $scope.backup_now_job_id[0];
            $scope.backup_now_job_id.shift();

            if ($scope.backup_now_is_global) {
                var temp_url = $scope.backup_now_global_urls[0];
                $scope.backup_now_global_urls.shift();
                angular.element(document.getElementById('backup_now_global_message')).append('<div class="item">Start processing website '+$sanitize(temp_url)+'<span class="ui right floated"><i class="notched loading circle icon"></i></div>');
            }
        } else {
            // All jobs done
            jQuery("#abortbutton").hide();
            jQuery('#runningjob').hide();
            angular.element(document.getElementById( 'backup_now_global_message')).append('<div class="ui greed message">Processing finished.</div>');
            $scope.backup_now_is_end = true;
            $scope.backup_now_temporary_website_id = 0;
            return;
        }

        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_backup_now',
            'website_id': website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child'],
            'job_id': current_job_id
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "backup_now", 1);

            if (message !== true) {
                angular.element(document.getElementById('backup_now_global_message')).append('<p style="color: #a00">Error: <b>'+$sanitize(message)+'</b></p>');
                $scope.backup_now_lastmsg = message;
                // Go to next one
                $scope.backup_now('0');
            } else if ('logfile' in d.data) {
                $scope.ajax_working(d.data.logfile, website_id);
                $scope.backup_now_lastmsg = d.data.response;
            } else {
                $scope.backup_now_progressstep = "100%";
                $scope.backup_now_progresssteps = "100%";
                jQuery("#abortbutton").hide();

                $scope.backup_now_lastmsg = _mwbwpupt("Job executes too fast. Move to Logs tab in order to view log.");
                angular.element(document.getElementById('backup_now_global_message')).append('<p style="color: #a00">'+_mwbwpupt("Job executes too fast. Move to Logs tab in order to view log.")+'</p>');
                // Go to next one
                $scope.backup_now('0');
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("backup_now.error"));
        });
    };

    $scope.ajax_working = function(logfile, website_id) {
        mainwp_backwpup_clear_error();
        if ($scope.abort_backup_now == 1) {
            return;
        }

        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_ajax_working',
            'website_id': website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child'],
            'logfile': logfile,
            'logpos': jQuery('#logpos').val()
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "ajax_working", 1);
            if (message !== true) {
                angular.element(document.getElementById('backup_now_global_message')).append('<div class="ui red message">'+$sanitize(message)+'</div>');
                $scope.backup_now_lastmsg = message;
                // Go to next one
                $scope.backup_now('0');
            } else {
                var rundata = JSON.parse(d.data.response);

                if (0 < rundata.log_pos) {
                    jQuery('#logpos').val($sanitize(rundata.log_pos));
                }
                if ('' != rundata.log_text) {
                    jQuery('#showworking').append(rundata.log_text);
                }
                if (0 < rundata.error_count) {
                    $scope.backup_now_errors = rundata.error_count;
                }
                if (0 < rundata.warning_count) {
                    $scope.backup_now_warnings = rundata.warning_count;
                }
                if (0 < rundata.step_percent) {
                    $scope.backup_now_progressstep = rundata.step_percent + '%';
                }
                if (0 < rundata.sub_step_percent) {
                    $scope.backup_now_progresssteps = parseFloat(rundata.sub_step_percent) + '%';
                }
                if (0 < rundata.running_time) {
                    $scope.backup_now_runtime = rundata.running_time;
                }
                if ( '' != rundata.on_step ) {
                    $scope.backup_now_onstep =  rundata.on_step;
                }
                if ( '' != rundata.last_msg ) {
                    $scope.backup_now_lastmsg = rundata.last_msg;
                }
                if ( '' != rundata.last_error_msg ) {
                    angular.element(document.getElementById('backup_now_last_error_msg')).html('<div class="ui red message">'+$sanitize(rundata.last_error_msg)+'</p>');
                }
                if ( rundata.job_done == 1 ) {
                    if (parseInt($scope.backup_now_errors) > 0) {
                        angular.element(document.getElementById('backup_now_global_message')).find( '.ui.right.floated' ).last().html('<i class="red times icon"></i>');
                    } else if (parseInt($scope.backup_now_warnings) > 0) {
                        angular.element(document.getElementById('backup_now_global_message')).find( '.ui.right.floated' ).last().html('<i class="yellow exclamation triangle icon"></i>');
                    } else {
                        angular.element(document.getElementById('backup_now_global_message')).find( '.ui.right.floated' ).last().html('<i class="green check icon"></i>');
                    }
                    // Maybe there is more job to do?
                    $scope.backup_now();
                } else {
                    $timeout(function(){$scope.ajax_working(logfile, website_id)}, 6000);
                }
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("ajax_working.error"));
        });
    };

    $scope.abort_backup = function() {
        mainwp_backwpup_clear_error();
        $scope.abort_backup_now = 1;
        var temp_website_id = $scope.backup_now_temporary_website_id;
        $scope.backup_now_temporary_website_id = 0;
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_backup_abort',
            'website_id': temp_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child']
        }).success(function (d) {
                jQuery('#runningjob').hide();
                $scope.backup_now_global_ids = [];
                $scope.backup_now_global_urls = [];
                $scope.backup_now_job_id = [];
                $scope.backup_now_is_global = false;
                angular.element(document.getElementById('backup_now_global_message')).append('<p style="color: #0073aa">'+_mwbwpupt('Job aborted')+'</p>');
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("abort_backup.error"));
        });
    };

    var filter_table_logs = function () {
        return {
            getData: function ($defer, params) {
                params.generatePagesArray = mainwp_backwpup_custom_generate_pages;
                var filteredData = params.filter() ? $filter('filter')(data_table_logs, params.filter()) : data_table_logs;
                var orderedData = params.sorting() ? $filter('orderBy')(data_table_logs, params.orderBy()) : filteredData;
                params.total(data_table_logs.length);
                $defer.resolve($scope.scope_data_table_logs = orderedData.slice((params.page() - 1) * params.count(), params.page() * params.count()));
            }
        }
    };

    var filter_table_backups = function () {
        return {
            getData: function ($defer, params) {
                params.generatePagesArray = mainwp_backwpup_custom_generate_pages;
                var filteredData = params.filter() ? $filter('filter')(data_table_backups, params.filter()) : data_table_backups;
                var orderedData = params.sorting() ? $filter('orderBy')(data_table_backups, params.orderBy()) : filteredData;
                params.total(data_table_backups.length);
                $defer.resolve($scope.scope_data_table_backups = orderedData.slice((params.page() - 1) * params.count(), params.page() * params.count()));
            }
        }
    };

    var filter_table_jobs = function () {
        return {
            getData: function ($defer, params) {
                params.generatePagesArray = mainwp_backwpup_custom_generate_pages;
                var filteredData = params.filter() ? $filter('filter')(data_table_jobs, params.filter()) : data_table_jobs;
                var orderedData = params.sorting() ? $filter('orderBy')(data_table_jobs, params.orderBy()) : filteredData;
                params.total(data_table_jobs.length);
                $defer.resolve($scope.scope_data_table_jobs = orderedData.slice((params.page() - 1) * params.count(), params.page() * params.count()));
            }
        }
    };

    $scope.reload_table_logs = function() {
        $scope.scope_data_table_logs = null;
        $scope.get_all_wrapper('logs');
    };

    $scope.reload_table_backups = function() {
        $scope.scope_data_table_backups = null;
        $scope.get_all_wrapper('backups');
    };

    $scope.reload_table_jobs = function() {
        $scope.scope_data_table_jobs = null;
        if (backwpup_website_id == 0) {
            $scope.get_all_wrapper('global_jobs');
        } else {
            $scope.get_all_wrapper('jobs');
        }
    };

    $scope.view_log = function(logfile) {
        mainwp_backwpup_clear_error();
        jQuery('#view_log_inline').html('Loading ...');
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_view_log',
            'website_id': backwpup_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child'],
            'logfile': logfile
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "view_log")) {
                jQuery('#TB_ajaxContent').html(d.data.response);
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("view_log.error"));
        });
    };

    $scope.delete_log = function(logfile) {
        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_delete_log',
            'website_id': backwpup_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child'],
            'logfile': logfile
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "delete_log")) {
                $scope.reload_table_logs();
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("delete_log.error"));
        });
    };

    $scope.delete_backup = function(website_id, backupfile, dest) {
        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_delete_backup',
            'website_id': website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child'],
            'backupfile': backupfile,
            'dest' : dest
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "delete_backup")) {
                var temp_data_table_backups = [];

                angular.forEach($scope.scope_data_table_backups, function(value, key) {
                    if (value.file == backupfile && value.website_id == website_id) {
                        mainwp_backwpup_display_message(_mwbwpupt('The Backup has been deleted successfully'));
                    } else {
                        temp_data_table_backups.push(value);
                    }
                });

                $scope.scope_data_table_backups = temp_data_table_backups;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("delete_backup.error"));
        });
    };


    $scope.delete_job = function(website_id, job_id, is_child_job) {
        mainwp_backwpup_clear_error();
        $scope.scope_wizard_system_scan = 1;
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_root',
            'method': 'delete_job',
            'website_id': website_id,
            'job_id': job_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_root'],
            'is_global' : '0',
            'is_child_job': is_child_job
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "delete_job")) {
                if (website_id == 0) {
                    $scope.global_delete_job_ids = d.data.job_ids;
                    $scope.global_delete_urls = d.data.website_urls;
                    $scope.global_delete_website_ids = d.data.website_ids;


                    angular.element(document.getElementById('syncing_message')).empty();

                    if ($scope.global_delete_job_ids.length == 0) {
                        $scope.reload_table_jobs();
                        return;
                    }

                    // When user has synchronize param and wants to delete job
                    angular.element(document.getElementById('syncing_box_wait')).replaceWith(_mwbwpupt('<h2>'+_mwbwpupt('Synchronize deleting job to child sites')+'</h2>'));


                    $scope.select_tab('synchronize');

                    //jQuery("#syncing_current").html('0');
                    //jQuery("#syncing_total").html($scope.global_delete_job_ids.length);
                    //jQuery('#syncing_progress').progressbar({value: 0, max: $scope.global_delete_job_ids.length});
                    //jQuery('#syncing_progress_text').show();

                    var global_delete_promise = $scope.delete_job_step_2(0);

                    for (i = 1; i < $scope.global_delete_job_ids.length; ++i) {
                        global_delete_promise = global_delete_promise.then(function (ii) {
                            return $scope.delete_job_step_2(ii);
                        });
                    }

                    global_delete_promise.then(function() {
                        angular.element(document.getElementById('syncing_message')).append('<div class="ui green message">'+_mwbwpupt('Process completed successfully.')+'</div>');
                        $scope.reload_table_jobs();
                    });

                } else {
                    $scope.reload_table_jobs();
                }
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("delete_job.error"));
        });
    };

    $scope.delete_job_step_2 = function(i) {
        var deferred = $q.defer();

        angular.element(document.getElementById('syncing_message')).append('<div><b>'+$sanitize($scope.global_delete_urls[i])+'</b> <span id="synchronize_global_delete_tr_id_'+i+'" class="refresh-status-wp">' + _mwbwpupt("Pending") + '</span></div>');

        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_root',
            'method': 'delete_job',
            'website_id': $scope.global_delete_website_ids[i],
            'job_id': $scope.global_delete_job_ids[i],
            'is_global' : '1',
            'wp_nonce': backwpup_extension_security_nonce['contact_with_root']
        }).success(function (d) {
            var message = mainwp_backwpup_check_error_in_request(d, "delete_job_step_2", 1);
            if (message !== true) {
                angular.element(document.getElementById('synchronize_global_delete_tr_id_'+i)).replaceWith('<span class="refresh-status-wp">ERROR: ' + $sanitize(message) + '</span>');
            } else {
                angular.element(document.getElementById('synchronize_global_delete_tr_id_'+i)).replaceWith('<span class="refresh-status-wp">' +  _mwbwpupt("Deleted") + '</span>');
            }

          //  jQuery("#syncing_current").html(i+1);
            //jQuery('#syncing_progress').progressbar({value: i+1});

            deferred.resolve(i+1);
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("delete_job_step_2.error"));
            deferred.resolve(i+1);
        });

        return deferred.promise;
    };

    $scope.get_child_tables = function(first) {
        if (backwpup_website_id == 0) {
            return;
        }

        $scope.get_child_tables_loading = 1;
        $scope.scope_child_message = "";
        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_get_child_tables',
            'website_id': backwpup_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child'],
            'dbhost': jQuery("#dbdumpdbhost").val(),
            'dbuser': jQuery("#dbdumpdbuser").val(),
            'dbpassword': jQuery("#dbdumpdbpassword").val(),
            'dbname': jQuery("#dbdumpdbname").val(),
            'first': (first === 'undefined' ? '0' : '1'),
            'job_id': backwpup_job_id
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "get_child_tables")) {
                if ('dbdumpexclude' in d.data.return) {
                    $scope.scope_backwpup_dbdumpexclude = d.data.return.dbdumpexclude;
                }
                if ('tables' in d.data.return) {
                    $scope.scope_child_tables = d.data.return.tables;
                }
                if ('databases' in d.data.return) {
                    $scope.scope_child_databases = d.data.return.databases;
                }
                if ('message' in d.data.return) {
                    $scope.scope_child_message = d.data.return.message;
                }


                $scope.get_child_tables_loading = 0;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_child_tables.error"));
        });
    };

    $scope.get_information = function() {
        mainwp_backwpup_clear_error();
        $scope.get_information_loading = 0;
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_information',
            'website_id': backwpup_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "get_information")) {
                angular.element(document.getElementById('backwpup_information_tab_html')).append($sanitize(d.data.response));
                $scope.get_information_loading = 1;
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("get_information.error"));
        });
    };

    $scope.open_child_site = function(website_id, url) {
        mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_open_child_site',
            'website_id': website_id,
            'open_location': url,
            'wp_nonce': backwpup_extension_security_nonce['open_child_site']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "open_child_site")) {
                window.open(d.data.url);
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("open_child_site.error"));
        });
    };

    $scope.wizard_system_scan = function() {
        mainwp_backwpup_clear_error();
        $scope.scope_wizard_system_scan = 1;
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_child',
            'method': 'backwpup_wizard_system_scan',
            'website_id': backwpup_website_id,
            'wp_nonce': backwpup_extension_security_nonce['contact_with_child']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "wizard_system_scan")) {
                $scope.scope_wizard_system_scan = 2;
                angular.element(document.getElementById('backwpup_wizard_system_scan_div')).append($sanitize(d.data.response));
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("wizard_system_scan.error"));
        });
    };

    $scope.synchronize_global_edit = function(job_id) {
        mainwp_backwpup_clear_error();
        $scope.global_edit_ids = [];
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_synchronize_global_job',
            'job_id': job_id,
            'wp_nonce': backwpup_extension_security_nonce['synchronize_global_job']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "synchronize_global_edit")) {
                $scope.global_edit_ids = d.data.ids;
                $scope.global_edit_urls = d.data.urls;

                angular.element(document.getElementById('syncing_message')).empty();

                if ($scope.global_edit_urls.length == 0) {
                    angular.element(document.getElementById('syncing_message')).append('<div class="ui yellow message">'+_mwbwpupt('No websites to synchronize')+'</div>');
                    return;
                }

                //jQuery("#syncing_current").html('0');
                //jQuery("#syncing_total").html($scope.global_edit_ids.length);
                //jQuery('#syncing_progress').progressbar({value: 0, max: $scope.global_edit_ids.length});
                //jQuery('#syncing_progress_text').show();

                var global_edit_promise = $scope.synchronize_global_edit_step_2(0, job_id);

                for (i = 1; i < $scope.global_edit_ids.length; ++i) {
                    global_edit_promise = global_edit_promise.then(function (ii) {
                        return $scope.synchronize_global_edit_step_2(ii, job_id);
                    });
                }

                global_edit_promise.then(function() {
                    $scope.syncing_message_job_back = 1;
                    angular.element(document.getElementById('syncing_message')).append('<div class="ui green message">'+_mwbwpupt('Syncing finished')+'</div>');
                });

            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("synchronize_global_edit.error"));
        });
    };

    $scope.synchronize_global_edit_step_2 = function(i, job_id) {
        var deferred = $q.defer();

        angular.element(document.getElementById('syncing_message')).append('<div class="item">'+$sanitize($scope.global_edit_urls[i])+' <span id="synchronize_global_tr_id_'+i+'" class="refresh-status-wp right floated"><i class="notched circle loading icon"></i></span></div>');

        mainwp_backwpup_clear_error();
        $scope.scope_wizard_system_scan = 1;
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_synchronize_global_job_step_2',
            'website_id': $scope.global_edit_ids[i],
            'wp_nonce': backwpup_extension_security_nonce['synchronize_global_job_step_2'],
            'job_id': job_id
        }).success(function (d) {

            var message = mainwp_backwpup_check_error_in_request(d, "synchronize_global_edit_step_2", 1);
            if (message !== true) {
                angular.element(document.getElementById('synchronize_global_tr_id_'+i)).html('<span class="refresh-status-wp"><i class="red times icon"></i></span>');
            } else {
                angular.element(document.getElementById('synchronize_global_tr_id_'+i)).html('<span class="refresh-status-wp"><i class="green check icon"></i></span>');
                if (d.data.info_messages.length > 0) {
                    d.data.info_messages.forEach(function(message) {
                        if (message.indexOf("Changes for job") == -1) {
                            angular.element(document.getElementById('syncing_message')).append('<div><i>' + $sanitize(message) + '</i></div>');
                        }
                    });
                }
            }

            //jQuery("#syncing_current").html(i+1);
            //jQuery('#syncing_progress').progressbar({value: i+1});

            deferred.resolve(i+1, job_id);
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("synchronize_global_edit_step_2.error"));
            deferred.resolve(i+1, job_id);
        });

        return deferred.promise;
    };

    $scope.upgrade_plugin = function(website_id, slug) {
        mainwp_backwpup_clear_error();
        $scope.scope_wizard_system_scan = 1;
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_root',
            'method': 'upgrade_plugin',
            'website_id': website_id,
            'slugs': [slug],
            'wp_nonce': backwpup_extension_security_nonce['contact_with_root']
        }).success(function (d) {
            if (d.upgrades[slug]) {
                mainwp_backwpup_display_message(_mwbwpupt('Plugin Upgraded. Please reload page.'));
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("upgrade_plugin.error"));
        });
    };

    $scope.show_hide = function(model, website_id, show_hide) {
        mainwp_backwpup_clear_error();
        var temp_value = model[website_id];
        model[website_id] = -1;
        $scope.scope_wizard_system_scan = 1;
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_root',
            'method': 'show_hide',
            'website_id': website_id,
            'show_hide': (temp_value == '1' ? '0' : '1'),
            'wp_nonce': backwpup_extension_security_nonce['contact_with_root']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "show_hide")) {
                if (temp_value == '1') {
                    model[website_id] = '0';
                } else {
                    model[website_id] = '1'
                }
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("show_hide.error"));
        });
    };

    $scope.save_premium = function(website_id) {
       mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_root',
            'method': 'save_premium',
            'website_id': website_id,
            'is_premium' : jQuery('#is_premium').is(':checked') ? 1 : 0, //( $scope.settings_is_premium  ? 1 : 0 ),
            'wp_nonce': backwpup_extension_security_nonce['contact_with_root']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "save_premium")) {
                mainwp_backwpup_display_message(_mwbwpupt('Settings saved'));
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("save_premium.error"));
        });
    };

    
    $scope.save_override = function(website_id) {
       mainwp_backwpup_clear_error();
        $http.post(ajaxurl, {
            'action': 'mainwp_backwpup_contact_with_root',
            'method': 'save_override',
            'website_id': website_id,
            'override' : ($scope.settings_is_override  ? 1 : 0),
            'wp_nonce': backwpup_extension_security_nonce['contact_with_root']
        }).success(function (d) {
            if (mainwp_backwpup_check_error_in_request(d, "save_override")) {
                mainwp_backwpup_display_message(_mwbwpupt('Settings saved'));
            }
        }).error(function () {
            mainwp_backwpup_display_error(mainwp_backwpup_generic_error("save_override.error"));
        });
    };
});
