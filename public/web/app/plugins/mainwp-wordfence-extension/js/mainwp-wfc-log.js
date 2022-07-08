/*
 Plugin-Name: Wordfence Security
 Plugin-URI: http://www.wordfence.com/
 Description: Wordfence Security - Anti-virus, Firewall and High Speed Cache
 Author: Wordfence
 Version: 5.2.1
 Author-URI: http://www.wordfence.com/
 */
(function ($) {
        if (!window['mainwp_wordfenceAdmin']) {
                window['mainwp_wordfenceAdmin'] = {
                        isSmallScreen: false,
                        loadingCount: 0,
                        colorboxQueue: [],
                        colorboxOpen: false,
                        mode: '',
                        networkActivity: false,
                        visibleIssuesPanel: 'new',
                        nonce: false,
                        activityLogUpdatePending: [],
                        tickerUpdatePending: false,
                        newestActivityTime: [], //must be 0 to force loading of all initially
                        lastALogCtime: [],
                        reloadConfigPage: false,
                        doneLoadedFirstNetworkActivityLog: false,
                        activityQueue: [],
                        totalActAdded: [],
                        lastIssueUpdateTime: 0,
                        actNextUpdateAt: [],
                        maxActivityLogItems: 1000,
                        debugOn: false,
                        siteId: 0,
                        cacheType: '',
                        liveTrafficEnabled: 0,
                        forceUpdate: false,
                        bulkMaxThreads: 3,
                        bulkTotalThreads: 0,
                        bulkCurrentThreads: 0,
                        bulkFinishedThreads: 0,
                        isFirstSaving: true,
                        _windowHasFocus: true,
                        _tabHasFocus: false,
                        //serverTimestampOffset: 0,
                        serverMicrotime: 0,
                        pendingChanges: {},
                        scanFailed: false,
                        siteCleaningIssueTypes: ['file', 'checkGSB', 'checkSpamIP', 'commentBadURL', 'dnsChange', 'knownfile', 'optionBadURL', 'postBadTitle', 'postBadURL', 'spamvertizeCheck', 'suspiciousAdminUsers'],
                        site_id: 0,
                        current_ip: null,
                        wfLiveTraffic: null,
                        scanRunning: false,
                        scanFailed: false,
                        wafData: {
                                whitelistedURLParams: []
                        },
                        restoreWAFData: {
                                whitelistedURLParams: []
                        },
                        watcherOptions: {
                                front: 0,
                                admin: 0
                        },
                        init: function () {
                                this.isSmallScreen = window.matchMedia("only screen and (max-width: 500px)").matches;
                                this.nonce = mainwp_WordfenceAdminVars.firstNonce;

                                var startTicker = false;
                                var site_id = null;
                                var self = this;

                                $(window).on('blur', function () {
                                        self._windowHasFocus = false;
                                }).on('focus', function () {
                                        self._windowHasFocus = true;
                                }).focus();

                                jQuery('.mwp-do-show').click(function () {
                                        var $this = jQuery(this);
                                        $this.hide();
                                        jQuery($this.data('selector')).show();
                                        return false;
                                });

                                if (jQuery('#mwp_wordfenceMode_scan').length > 0) {
                                        site_id = jQuery('#mwp_wordfenceMode_scan').attr('site-id');
                                        this.debugOn = jQuery('#mwp_wordfenceMode_scan').attr('debug-on');

                                        this.mode = 'scan';


                                        jQuery('#wfALogViewLink').prop('href', /*mainwp_WordfenceAdminVars.siteBaseURL +*/ '?_wfsf=viewActivityLog&nonce=' + this.nonce);
                                        jQuery('#consoleActivity').scrollTop(jQuery('#consoleActivity').prop('scrollHeight'));
                                        jQuery('#consoleSummary').scrollTop(jQuery('#consoleSummary').prop('scrollHeight'));
                                        //this.noScanHTML = jQuery('#wfNoScanYetTmpl').tmpl().html();


                                        var loadingIssues = true;

                                        this.loadIssues(function () {
                                                loadingIssues = false;
                                        }, false, false, site_id);

                                        //this.startActivityLogUpdates(site_id);
                                        this.loadFirstActivityLog(site_id);

                                        //var issuesWrapper = $('#wfScanIssuesWrapper');
                                        var hasScrolled = false;
                                        $(window).on('scroll', function () {
                                                var win = $(this);
                                                // console.log(win.scrollTop() + window.innerHeight, liveTrafficWrapper.outerHeight() + liveTrafficWrapper.offset().top);
                                                var currentScrollBottom = win.scrollTop() + window.innerHeight;
                                                var scrollThreshold = true; //issuesWrapper.outerHeight() + issuesWrapper.offset().top;
                                                if (hasScrolled && !loadingIssues && currentScrollBottom >= scrollThreshold) {
                                                        // console.log('infinite scroll');

                                                        loadingIssues = true;
                                                        hasScrolled = false;
                                                        var offset = $('div.wfIssue').length;
                                                        MWP_WFAD.loadMoreIssues(function () {
                                                                loadingIssues = false;
                                                        }, offset, null, site_id);
                                                } else if (currentScrollBottom < scrollThreshold) {
                                                        hasScrolled = true;
                                                        // console.log('no infinite scroll');
                                                }
                                        });

                                } else if (jQuery('#mwp_wordfenceMode_activity').length > 0) {
                                        this.mode = 'activity';
                                        this.networkActivity = false;
                                        this._tabHasFocus = true;
                                        site_id = jQuery('#mwp_wfc_current_site_id').attr('site-id');
                                        this.site_id = site_id; // save for individual activity
                                        this.liveTrafficEnabled = jQuery('#mwp_wordfenceMode_activity').attr('liveTrafficEnabled');
                                        this.cacheType = jQuery('#mwp_wordfenceMode_activity').attr('cacheType');
                                        if (this.liveTrafficEnabled == 1 && this.cacheType != 'php' && this.cacheType != 'falcon') {
                                                this.activityMode = 'hit';
                                        } else {
                                                this.activityMode = 'loginLogout';
                                                this.switchTab(jQuery('#wfLoginLogoutTab'), 'wfTab1', 'wfDataPanel', 'wfActivity_loginLogout', function () {
                                                        MWP_WFAD.activityTabChanged(site_id);
                                                });
                                        }
                                        startTicker = true;
                                } else if (jQuery('#mwp_wordfenceMode_network_activity').length > 0) {
                                        var itemProcess = jQuery('.wfc_NetworkTrafficItemProcess[status="queue"]:first');
                                        this._tabHasFocus = true;
                                        if (itemProcess.length > 0) {
                                                site_id = itemProcess.attr('site-id');
                                                this.cacheType = itemProcess.attr('cacheType');
                                                itemProcess.attr('status', 'processing');
                                                this.mode = 'network_activity';
                                                this.networkActivity = true;
                                                this.activityMode = 'hit';
                                                startTicker = true;
                                        }
                                } else if (jQuery('#mwp_wordfenceMode_blockedIPs').length > 0) {
                                        site_id = jQuery('#mwp_wfc_current_site_id').attr('site-id');
                                        this.mode = 'blocked';
                                        this.staticTabChanged(site_id);
                                        this.updateTicker(true, site_id);
                                        startTicker = true;
                                }
                                // loading after blockedIPs loaded
                                //                else if (jQuery('#mwp_wordfenceMode_rangeBlocking').length > 0) {
                                //                        site_id = jQuery('#mwp_wfc_current_site_id').attr('site-id');
                                //                        if (site_id > 0) {
                                //                            this.mode = 'rangeBlocking';
                                //                            startTicker = false;
                                //                            this.calcRangeTotal();
                                //                            this.loadBlockRanges(site_id);
                                //                        }
                                //                }
                                else if (jQuery('#mwp_wordfenceMode_settings').length > 0) {
                                        this.mode = 'settings';
                                } else {
                                        this.mode = false;
                                }

                                if (jQuery('#mwp_wordfenceMode_caching').length > 0) {
                                        var siteId = jQuery('#mwp_wordfenceMode_caching').attr('site-id');
                                        if (siteId > 0)
                                                this.loadCacheExclusions(siteId);
                                }

                                if (jQuery('#mainwp_wfc_override_global_setting').length > 0 && jQuery('#wfc_individual_settings_site_id').val() > 0) {
                                        //this.getDiagnostics(jQuery('#wfc_individual_settings_site_id').val());
                                }

                                if (this.mode) { //We are in a Wordfence page
                                        var self = this;
                                        if (startTicker) {

                                                console.log('startTicker:' + mainwp_WordfenceAdminVars.actUpdateInterval);

                                                //console.log('startTicker: ' + site_id);
                                                this.updateTicker(false, site_id);
                                                this.liveInt = setInterval(function () {
                                                        self.updateTicker(false, site_id);
                                                }, mainwp_WordfenceAdminVars.actUpdateInterval);
                                        }
                                        jQuery(document).bind('cbox_closed', function () {
                                                self.colorboxIsOpen = false;
                                                self.colorboxServiceQueue();
                                        });
                                }
                        },
                        initScan: function (site_id) {
                                this.activityLogUpdatePending[site_id] = false;
                                this.lastALogCtime[site_id] = 0;
                                this.activityQueue[site_id] = [];
                                this.totalActAdded[site_id] = 0;
                                this.actNextUpdateAt[site_id] = 0;
                        },
                        windowHasFocus: function () {
                                if (typeof document.hasFocus === 'function') {
                                        return document.hasFocus();
                                }
                                // Older versions of Opera
                                return this._windowHasFocus;
                        },
                        htmlEscape: function (html) {
                                return String(html)
                                        .replace(/&/g, '&amp;')
                                        .replace(/"/g, '&quot;')
                                        .replace(/'/g, '&#39;')
                                        .replace(/</g, '&lt;')
                                        .replace(/>/g, '&gt;');
                        },
                        base64_decode: function (s) {
                                var e = {}, i, b = 0, c, x, l = 0, a, r = '', w = String.fromCharCode, L = s.length;
                                var A = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
                                for (i = 0; i < 64; i++) {
                                        e[A.charAt(i)] = i;
                                }
                                for (x = 0; x < L; x++) {
                                        c = e[s.charAt(x)];
                                        b = (b << 6) + c;
                                        l += 6;
                                        while (l >= 8) {
                                                ((a = (b >>> (l -= 8)) & 0xff) || (x < (L - 2))) && (r += w(a));
                                        }
                                }
                                return r;
                        },
                        updatePendingChanges: function () {
                                //$(window).off('beforeunload', MWP_WFAD._unsavedOptionsHandler);
                                if (Object.keys(MWP_WFAD.pendingChanges).length) {
                                        //$('#wf-cancel-changes').removeClass('wf-disabled');
                                        //$('#wf-save-changes').removeClass('wf-disabled');
                                        //        $(window).on('beforeunload', MWP_WFAD._unsavedOptionsHandler);
                                }
                                else {
                                        //                            $('#wf-cancel-changes').addClass('wf-disabled');
                                        //                            $('#wf-save-changes').addClass('wf-disabled');
                                }
                        },
                        _unsavedOptionsHandler: function (e) {
                                var message = "You have unsaved changes to your options. If you leave this page, those changes will be lost."; //Only shows on older browsers, newer browsers don't allow message customization
                                e = e || window.event;
                                if (e) {
                                        e.returnValue = message; //IE and Firefox
                                }
                                return message; //Others
                        },
                        wafWhitelistedBulkChangeEnabled: function (enabled) {
                                $('.wf-whitelist-table-bulk-checkbox.wf-option-checkbox.wf-checked').each(function () {
                                        $(this).closest('tr').find('.wf-whitelist-item-enabled.wf-option-checkbox').each(function () {
                                                if (($(this).hasClass('wf-checked') && !enabled) || (!$(this).hasClass('wf-checked') && enabled)) {
                                                        var tr = $(this).closest('tr');
                                                        if (tr.is(':visible')) {
                                                                MWP_WFAD.wafWhitelistedChangeEnabled(tr.data('key'), enabled);
                                                        }
                                                }
                                        });
                                });
                        },
                        wafWhitelistedChangeEnabled: function (key, enabled) {
                                $('#waf-whitelisted-urls-wrapper .whitelist-table > tbody > tr[data-key="' + key + '"]').each(function () {
                                        var adding = !!$(this).data('adding');
                                        if (adding) {
                                                MWP_WFAD.pendingChanges['whitelistedURLParams']['add'][key]['data']['disabled'] = !enabled ? 1 : 0;
                                        }
                                        else {
                                                if (!(MWP_WFAD.pendingChanges['whitelistedURLParams'] instanceof Object)) {
                                                        MWP_WFAD.pendingChanges['whitelistedURLParams'] = {};
                                                }

                                                if (!(MWP_WFAD.pendingChanges['whitelistedURLParams']['enabled'] instanceof Object)) {
                                                        MWP_WFAD.pendingChanges['whitelistedURLParams']['enabled'] = {};
                                                }

                                                MWP_WFAD.pendingChanges['whitelistedURLParams']['enabled'][key] = !!enabled ? 1 : 0;
                                        }
                                        $(this).find('.wf-whitelist-item-enabled.wf-option-checkbox').toggleClass('wf-checked', !!enabled);
                                });
                        },

                        wafWhitelistedBulkDelete: function () {
                                $('.wf-whitelist-table-bulk-checkbox.wf-option-checkbox.wf-checked').each(function () {
                                        $(this).closest('tr').find('.wf-whitelist-item-enabled.wf-option-checkbox').each(function () {
                                                var tr = $(this).closest('tr');
                                                if (tr.is(':visible')) {
                                                        MWP_WFAD.wafWhitelistedDelete(tr.data('key'));
                                                }
                                        });
                                });
                        },

                        wafWhitelistedDelete: function (key) {
                                $('#waf-whitelisted-urls-wrapper .whitelist-table > tbody > tr[data-key="' + key + '"]').each(function () {
                                        var adding = !!$(this).data('adding');
                                        if (adding) {
                                                delete MWP_WFAD.pendingChanges['whitelistedURLParams']['add'][key];
                                        }
                                        else {
                                                if (!(MWP_WFAD.pendingChanges['whitelistedURLParams'] instanceof Object)) {
                                                        MWP_WFAD.pendingChanges['whitelistedURLParams'] = {};
                                                }

                                                if (!(MWP_WFAD.pendingChanges['whitelistedURLParams']['delete'] instanceof Object)) {
                                                        MWP_WFAD.pendingChanges['whitelistedURLParams']['delete'] = {};
                                                }

                                                MWP_WFAD.pendingChanges['whitelistedURLParams']['delete'][key] = 1;
                                        }

                                        for (var i = 0; i < MWP_WFAD.wafData.whitelistedURLParams.length; i++) {
                                                var testKey = MWP_WFAD.wafData.whitelistedURLParams[i].path + '|' + MWP_WFAD.wafData.whitelistedURLParams[i].paramKey;
                                                if (testKey == key) {
                                                        MWP_WFAD.wafData.whitelistedURLParams.splice(i, 1);
                                                        break;
                                                }
                                        }
                                });
                        },
                        dateFormat: function (date) {
                                if (date instanceof Date) {
                                        if (date.toLocaleString) {
                                                return date.toLocaleString();
                                        }
                                        return date.toString();
                                }
                                return date;
                        },
                        saveOptions: function (website_id, successCallback, failureCallback) {
                                if (!Object.keys(MWP_WFAD.pendingChanges).length) {
                                        return;
                                }

                                var self = this;

                                this.ajax('mainwp_wfc_saveOptions', { changes: JSON.stringify(MWP_WFAD.pendingChanges), site_id: website_id, individual: 1, nonce: self.nonce }, function (res) {
                                        if (res.success) {
                                                typeof successCallback == 'function' && successCallback(res);
                                        }
                                        else {
                                                MWP_WFAD.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'Error Saving Options', res.error);
                                                typeof failureCallback == 'function' && failureCallback
                                        }
                                });
                        },
                        showLoading: function () {
                                this.loadingCount++;
                                if (this.loadingCount == 1) {
                                        jQuery('<div id="mwp_wordfenceWorking">Wordfence is working...</div>').appendTo('body');
                                }
                        },
                        removeLoading: function () {
                                this.loadingCount--;
                                if (this.loadingCount == 0) {
                                        jQuery('#mwp_wordfenceWorking').remove();
                                }
                        },
                        startActivityLogUpdates: function (site_id) {
                                var self = this;
                                setInterval(function () {
                                        self.updateActivityLog(site_id);
                                }, parseInt(mainwp_WordfenceAdminVars.actUpdateInterval));
                        },
                        //            updateActivityLog_Old: function (site_id) {
                        //                // if network scan then process next site id
                        //                if (this.mode == 'network_scan') {
                        //                    var itemProcess = jQuery('.wfc_NetworkResultItemProcess[status="queue"]:first');
                        //                    if (itemProcess.length == 0) {
                        //                        jQuery('.wfc_NetworkResultItemProcess').attr('status', 'queue');
                        //                        itemProcess = jQuery('.wfc_NetworkResultItemProcess[status="queue"]:first');
                        //                    }
                        //                    site_id = itemProcess.attr('site-id');
                        //                }
                        //
                        //                if (typeof site_id == "undefined" || !site_id)
                        //                    return;
                        //
                        //                if (this.activityLogUpdatePending[site_id]) {
                        //                    return;
                        //                }
                        //                this.activityLogUpdatePending[site_id] = true;
                        //                var self = this;
                        //                this.ajax('mainwp_wfc_activityLogUpdate',
                        //                    {
                        //                        lastctime: this.lastALogCtime[site_id],
                        //                        site_id: site_id
                        //                    },
                        //                    function (res) {
                        //                        self.doneUpdateActivityLog(res, site_id);
                        //                        if (typeof itemProcess != "undefined")
                        //                            itemProcess.attr('status', 'processed');
                        //                    },
                        //                    function () {
                        //                        self.activityLogUpdatePending[site_id] = false;
                        //                        if (typeof itemProcess != "undefined")
                        //                            itemProcess.attr('status', 'processed');
                        //                    },
                        //                    true);
                        //            },
                        //


                        updateActivityLog: function (site_id) {
                                if (this.activityLogUpdatePending[site_id] || (!this.windowHasFocus() && mainwp_WordfenceAdminVars.allowsPausing == '1')) {
                                        if (!jQuery('body').hasClass('wordfenceLiveActivityPaused') && !this.activityLogUpdatePending[site_id]) {
                                                jQuery('body').addClass('wordfenceLiveActivityPaused');
                                        }
                                        console.log('wordfenceLiveActivityPaused');
                                        return;
                                }
                                if (jQuery('body').hasClass('wordfenceLiveActivityPaused')) {
                                        jQuery('body').removeClass('wordfenceLiveActivityPaused');
                                }
                                this.activityLogUpdatePending[site_id] = true;
                                var self = this;

                                this.ajax('mainwp_wfc_activityLogUpdate', {
                                        lastctime: this.lastALogCtime[site_id],
                                        lastissuetime: this.lastIssueUpdateTime,
                                        site_id: site_id
                                }, function (res) {
                                        self.doneUpdateActivityLog(res, site_id);
                                }, function () {
                                        self.activityLogUpdatePending[site_id] = false;
                                }, true);

                        },

                        //            doneUpdateActivityLog_Old: function (res, site_id) {
                        //                this.actNextUpdateAt[site_id] = (new Date()).getTime() + parseInt(mainwp_WordfenceAdminVars.actUpdateInterval);
                        //                if (res.ok) {
                        //                    if (res.items.length > 0) {
                        //                        this.activityQueue[site_id].push.apply(this.activityQueue[site_id], res.items);
                        //                        this.lastALogCtime[site_id] = res.items[res.items.length - 1].ctime;
                        //                        this.processActQueue(res.currentScanID, site_id);
                        //                    }
                        //                }
                        //                this.activityLogUpdatePending[site_id] = false;
                        //            },
                        doneUpdateActivityLog: function (res, site_id) {
                                this.actNextUpdateAt[site_id] = (new Date()).getTime() + parseInt(mainwp_WordfenceAdminVars.actUpdateInterval);
                                if (res.ok) {
                                        if (res.items.length > 0) {
                                                this.activityQueue[site_id].push.apply(this.activityQueue[site_id], res.items);
                                                this.lastALogCtime[site_id] = res.items[res.items.length - 1].ctime;
                                                this.processActQueue(site_id);
                                        }
                                        if (res.signatureUpdateTime) {
                                                this.updateSignaturesTimestamp(res.signatureUpdateTime);
                                        }

                                        MWP_WFAD.scanFailed = (res.scanFailed == '1' ? true : false);
                                        if (res.scanFailed) {
                                                jQuery('#wf-scan-failed-time-ago').text(res.scanFailedTiming);
                                                jQuery('#wf-scan-failed').show();
                                        }
                                        else {
                                                jQuery('#wf-scan-failed').hide();
                                        }

                                        if (res.lastMessage) {
                                                $('#wf-scan-last-status').html(res.lastMessage);
                                        }

                                        if (res.issues) {
                                                this.lastIssueUpdateTime = res.issueUpdateTimestamp;
                                                this.displayIssues(res, null, site_id);
                                        }

                                        if (res.issueCounts) {
                                                MWP_WFAD.updateIssueCounts(res.issueCounts);
                                        }

                                        if (res.scanStats) {
                                                var keys = Object.keys(res.scanStats);
                                                for (var i = 0; i < keys.length; i++) {
                                                        $('.' + keys[i]).text(res.scanStats[keys[i]]);
                                                }
                                        }

                                        if (res.scanStages) {
                                                var keys = Object.keys(res.scanStages);
                                                for (var i = 0; i < keys.length; i++) {
                                                        var element = $('#wf-scan-' + keys[i]);
                                                        if (element) {

                                                                var existingClasses = element.attr('class');
                                                                if (existingClasses.match(/ /)) {
                                                                        existingClasses = existingClasses.split(' ');
                                                                }
                                                                else {
                                                                        existingClasses = [existingClasses];
                                                                }

                                                                var newClasses = res.scanStages[keys[i]];
                                                                if (newClasses.match(/ /)) {
                                                                        newClasses = newClasses.split(' ');
                                                                }
                                                                else {
                                                                        newClasses = [newClasses];
                                                                }

                                                                var mismatch = false;
                                                                if (existingClasses.length != newClasses.length) {
                                                                        mismatch = true;
                                                                }
                                                                else {
                                                                        var intersection = existingClasses.filter(function (value) {
                                                                                for (var n = 0; n < newClasses.length; n++) {
                                                                                        if (newClasses[n] == value) {
                                                                                                return true;
                                                                                        }
                                                                                }
                                                                                return false;
                                                                        });
                                                                        mismatch = (intersection.length != newClasses.length);
                                                                }

                                                                if (mismatch) {
                                                                        element.removeClass();
                                                                        element.addClass(newClasses.join(' '));
                                                                }

                                                                var oldScanRunning = MWP_WFAD.scanRunning;
                                                                MWP_WFAD.scanRunning = (res.scanRunning == '1' && !MWP_WFAD.scanFailed) ? true : false;
                                                                if (oldScanRunning != MWP_WFAD.scanRunning) {
                                                                        if (MWP_WFAD.scanRunning) {
                                                                                $('#wf-scan-running-bar').show();
                                                                        }
                                                                        else {
                                                                                $('#wf-scan-running-bar').hide();
                                                                        }
                                                                        $(window).trigger('wfScanUpdateButtons');
                                                                }
                                                        }
                                                }
                                        }
                                }
                                this.activityLogUpdatePending[site_id] = false;
                        },
                        updateIssueCounts: function (issueCounts) {
                                var newCount = (typeof issueCounts['new'] === 'undefined' ? 0 : parseInt(issueCounts['new']));
                                var ignoredCount = (typeof issueCounts['ignoreC'] === 'undefined' ? 0 : parseInt(issueCounts['ignoreC'])) + (typeof issueCounts['ignoreP'] === 'undefined' ? 0 : parseInt(issueCounts['ignoreP']));

                                $('#wf-scan-tab-new a').html($('#wf-scan-tab-new').data('tabTitle') + ' (' + newCount + ')');
                                $('#wf-scan-tab-ignored a').html($('#wf-scan-tab-ignored').data('tabTitle') + ' (' + ignoredCount + ')');

                                if (newCount == 0) {
                                        var existing = $('.wf-issue[data-issue-id="no-issues-new"]');
                                        if (existing.length == 0) {
                                                var issue = $('#issueTmpl_noneFound').tmpl({ shortMsg: 'No new issues have been found.', id: 'no-issues-new' });
                                                $('#wf-scan-results-new').append(issue);
                                        }
                                }
                                else {
                                        $('.wf-issue[data-issue-id="no-issues-new"]').remove();
                                }

                                if (ignoredCount == 0) {
                                        var existing = $('.wf-issue[data-issue-id="no-issues-ignored"]');
                                        if (existing.length == 0) {
                                                var issue = $('#issueTmpl_noneFound').tmpl({ shortMsg: 'No issues have been ignored.', id: 'no-issues-ignored' });
                                                $('#wf-scan-results-ignored').append(issue);
                                        }
                                }
                                else {
                                        $('.wf-issue[data-issue-id="no-issues-ignored"]').remove();
                                }
                        },
                        updateSignaturesTimestamp: function (signatureUpdateTime) {
                                var date = new Date(signatureUpdateTime * 1000);

                                var dateString = date.toString();
                                if (date.toLocaleString) {
                                        dateString = date.toLocaleString();
                                }

                                var sigTimestampEl = $('#wf-scan-sigs-last-update');
                                var newText = 'Last Updated: ' + dateString;
                                if (sigTimestampEl.text() !== newText) {
                                        sigTimestampEl.text(newText)
                                                .css({
                                                        'opacity': 0
                                                })
                                                .animate({
                                                        'opacity': 1
                                                }, 500);
                                }
                        },
                        processActQueue_Old: function (currentScanID, site_id) {
                                var parentBox = jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + ']');
                                if (this.activityQueue[site_id].length > 0) {
                                        this.addActItem(this.activityQueue[site_id].shift(), site_id);

                                        this.totalActAdded[site_id]++;
                                        if (this.totalActAdded[site_id] > this.maxActivityLogItems) {
                                                parentBox.find('.mwp_consoleActivity div:first').remove();
                                                this.totalActAdded[site_id]--;
                                        }

                                        var timeTillNextUpdate = this.actNextUpdateAt[site_id] - (new Date()).getTime();
                                        var maxRate = 50 / 1000; //Rate per millisecond
                                        var bulkTotal = 0;
                                        while (this.activityQueue[site_id].length > 0 && this.activityQueue[site_id].length / timeTillNextUpdate > maxRate) {
                                                var item = this.activityQueue[site_id].shift();
                                                if (item) {
                                                        bulkTotal++;
                                                        this.addActItem(item, site_id);
                                                }
                                        }
                                        this.totalActAdded[site_id] += bulkTotal;
                                        if (this.totalActAdded[site_id] > this.maxActivityLogItems) {
                                                parentBox.find('.mwp_consoleActivity div:lt(' + bulkTotal + ')').remove();
                                                this.totalActAdded[site_id] -= bulkTotal;
                                        }

                                        var minDelay = 100;
                                        var delay = minDelay;
                                        if (timeTillNextUpdate < 1) {
                                                delay = minDelay;
                                        } else {
                                                delay = Math.round(timeTillNextUpdate / this.activityQueue[site_id].length);
                                                if (delay < minDelay) {
                                                        delay = minDelay;
                                                }
                                        }
                                        var self = this;
                                        setTimeout(function () {
                                                self.processActQueue(site_id);
                                        }, delay);
                                }
                                parentBox.find('.mwp_consoleActivity').scrollTop(parentBox.find('.mwp_consoleActivity').prop('scrollHeight'));
                        },
                        processActQueue: function (site_id) {
                                if (this.activityQueue[site_id].length > 0) {
                                        this.addActItem(this.activityQueue[site_id].shift(), site_id);
                                        this.totalActAdded[site_id]++;
                                        if (this.totalActAdded[site_id] > this.maxActivityLogItems) {
                                                jQuery('#wf-scan-activity-log > li:first').remove();
                                                this.totalActAdded[site_id]--;
                                        }
                                        var timeTillNextUpdate = this.actNextUpdateAt[site_id] - (new Date()).getTime();
                                        var maxRate = 50 / 1000; //Rate per millisecond
                                        var bulkTotal = 0;
                                        while (this.activityQueue[site_id].length > 0 && this.activityQueue[site_id].length / timeTillNextUpdate > maxRate) {
                                                var item = this.activityQueue[site_id].shift();

                                                if (item) {
                                                        bulkTotal++;
                                                        this.addActItem(item, site_id);
                                                }
                                        }
                                        this.totalActAdded[site_id] += bulkTotal;
                                        if (this.totalActAdded[site_id] > this.maxActivityLogItems) {
                                                jQuery('#wf-scan-activity-log > li:lt(' + bulkTotal + ')').remove();
                                                this.totalActAdded[site_id] -= bulkTotal;
                                        }
                                        var minDelay = 100;
                                        var delay = minDelay;
                                        if (timeTillNextUpdate < 1) {
                                                delay = minDelay;
                                        } else {
                                                delay = Math.round(timeTillNextUpdate / this.activityQueue[site_id].length);
                                                if (delay < minDelay) {
                                                        delay = minDelay;
                                                }
                                        }
                                        var self = this;
                                        setTimeout(function () {
                                                self.processActQueue(site_id);
                                        }, delay);
                                }
                                jQuery('#wf-scan-activity-log').scrollTop(jQuery('#wf-scan-activity-log').prop('scrollHeight'));
                        },

                        processActArray: function (arr, site_id) {
                                for (var i = 0; i < arr.length; i++) {
                                        this.addActItem(arr[i], site_id);
                                }
                        },
                        //            addActItem_Old: function (item, site_id) {
                        //                if (!item) {
                        //                    return;
                        //                }
                        //                if (!item.msg) {
                        //                    return;
                        //                }
                        //                var parentBox = jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + ']');
                        //                if (item.msg.indexOf('SUM_') == 0) {
                        //                    this.processSummaryLine(item, site_id);
                        //                    parentBox.find('.mwp_consoleSummary').scrollTop(parentBox.find('.mwp_consoleSummary').prop('scrollHeight'));
                        //                    parentBox.find('.wfStartingScan').addClass('wfc-summary-ok').html('Done.');
                        //                } else if (this.debugOn || item.level < 4) {
                        //
                        //                    var html = '<div class="wfActivityLine';
                        //                    if (this.debugOn) {
                        //                        html += ' wf' + item.type;
                        //                    }
                        //                    html += '">[' + item.date + ']&nbsp;' + item.msg + '</div>';
                        //                    parentBox.find('.mwp_consoleActivity').append(html);
                        //                    if (/Scan complete\./i.test(item.msg)) {
                        //                        this.loadIssues(false, site_id);
                        //                    }
                        //                }
                        //            },
                        addActItem: function (item, site_id) {
                                if (!item) {
                                        return;
                                }
                                if (!item.msg) {
                                        return;
                                }
                                if (item.msg.indexOf('SUM_') == 0) {
                                        this.processSummaryLine(item, site_id);
                                }
                                else if (this.debugOn || item.level < 4) {

                                        var html = '<li class="wfActivityLine';
                                        if (this.debugOn) {
                                                html += ' wf' + item.type;
                                        }
                                        html += '">[' + item.date + ']&nbsp;' + item.msg + '</div>';
                                        jQuery('#wf-scan-activity-log').append(html);
                                        if (/Scan complete\./i.test(item.msg) || /Scan interrupted\./i.test(item.msg)) {
                                                this.loadIssues(false, null, null, site_id);
                                        }
                                }
                        },
                        processSummaryLine: function (item, site_id) {
                                var msg, summaryUpdated;
                                if (item.msg.indexOf('SUM_START:') != -1) {
                                        msg = item.msg.replace('SUM_START:', '');
                                        jQuery('#consoleSummary').append('<div class="wfSummaryLine"><div class="wfSummaryDate">[' + item.date + ']</div><div class="wfSummaryMsg">' + msg + '</div><div class="wfSummaryResult"><div class="wfSummaryLoading"></div></div><div class="wfClear"></div>');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDBAD') != -1) {
                                        msg = item.msg.replace('SUM_ENDBAD:', '');
                                        jQuery('div.wfSummaryMsg:contains("' + msg + '")').next().addClass('wfSummaryBad').html('Problems found.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDFAILED') != -1) {
                                        msg = item.msg.replace('SUM_ENDFAILED:', '');
                                        jQuery('div.wfSummaryMsg:contains("' + msg + '")').next().addClass('wfSummaryBad').html('Failed.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDOK') != -1) {
                                        msg = item.msg.replace('SUM_ENDOK:', '');
                                        jQuery('div.wfSummaryMsg:contains("' + msg + '")').next().addClass('wfSummaryOK').html('Secure.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDSUCCESS') != -1) {
                                        msg = item.msg.replace('SUM_ENDSUCCESS:', '');
                                        jQuery('div.wfSummaryMsg:contains("' + msg + '")').next().addClass('wfSummaryOK').html('Success.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDERR') != -1) {
                                        msg = item.msg.replace('SUM_ENDERR:', '');
                                        jQuery('div.wfSummaryMsg:contains("' + msg + '")').next().addClass('wfSummaryErr').html('An error occurred.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDSKIPPED') != -1) {
                                        msg = item.msg.replace('SUM_ENDSKIPPED:', '');
                                        jQuery('div.wfSummaryMsg:contains("' + msg + '")').next().addClass('wfSummaryResult').html('Skipped.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDIGNORED') != -1) {
                                        msg = item.msg.replace('SUM_ENDIGNORED:', '');
                                        jQuery('div.wfSummaryMsg:contains("' + msg + '")').next().addClass('wfSummaryIgnored').html('Ignored.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_DISABLED:') != -1) {
                                        msg = item.msg.replace('SUM_DISABLED:', '');
                                        jQuery('#consoleSummary').append('<div class="wfSummaryLine"><div class="wfSummaryDate">[' + item.date + ']</div><div class="wfSummaryMsg">' + msg + '</div><div class="wfSummaryResult">Disabled</div><div class="wfClear"></div>');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_PAIDONLY:') != -1) {
                                        msg = item.msg.replace('SUM_PAIDONLY:', '');
                                        jQuery('#consoleSummary').append('<div class="wfSummaryLine"><div class="wfSummaryDate">[' + item.date + ']</div><div class="wfSummaryMsg">' + msg + '</div><div class="wfSummaryResult"><a href="https://www.wordfence.com/wordfence-signup/" target="_blank"  rel="noopener noreferrer">Paid Members Only</a></div><div class="wfClear"></div>');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_FINAL:') != -1) {
                                        msg = item.msg.replace('SUM_FINAL:', '');
                                        jQuery('#consoleSummary').append('<div class="wfSummaryLine"><div class="wfSummaryDate">[' + item.date + ']</div><div class="wfSummaryMsg wfSummaryFinal">' + msg + '</div><div class="wfSummaryResult wfSummaryOK">Scan Complete.</div><div class="wfClear"></div>');
                                } else if (item.msg.indexOf('SUM_PREP:') != -1) {
                                        msg = item.msg.replace('SUM_PREP:', '');
                                        jQuery('#consoleSummary').empty().html('<div class="wfSummaryLine"><div class="wfSummaryDate">[' + item.date + ']</div><div class="wfSummaryMsg">' + msg + '</div><div class="wfSummaryResult" id="wfStartingScan"><div class="wfSummaryLoading"></div></div><div class="wfClear"></div>');
                                } else if (item.msg.indexOf('SUM_KILLED:') != -1) {
                                        msg = item.msg.replace('SUM_KILLED:', '');
                                        jQuery('#consoleSummary').empty().html('<div class="wfSummaryLine"><div class="wfSummaryDate">[' + item.date + ']</div><div class="wfSummaryMsg">' + msg + '</div><div class="wfSummaryResult wfSummaryOK">Scan Complete.</div><div class="wfClear"></div>');
                                }
                        },
                        processSummaryLine_Old: function (item, site_id) {
                                var parentBox = jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + ']');

                                if (item.msg.indexOf('SUM_START:') != -1) {
                                        var msg = item.msg.replace('SUM_START:', '');
                                        parentBox.find('.mwp_consoleSummary').append('<div class="wfSummaryLine"><div class="wfc-summary-date">[' + item.date + ']</div><div class="wfc-summary-msg">' + msg + '</div><div class="wfc-summary-result"><div class="wfc-summary-loading"></div></div><div class="wfc-clear"></div>');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDBAD') != -1) {
                                        var msg = item.msg.replace('SUM_ENDBAD:', '');
                                        parentBox.find('div.wfc-summary-msg:contains("' + msg + '")').next().addClass('wfc-summary-bad').html('Problems found.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDFAILED') != -1) {
                                        var msg = item.msg.replace('SUM_ENDFAILED:', '');
                                        parentBox.find('div.wfc-summary-msg:contains("' + msg + '")').next().addClass('wfc-summary-bad').html('Failed.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDOK') != -1) {
                                        var msg = item.msg.replace('SUM_ENDOK:', '');
                                        parentBox.find('div.wfc-summary-msg:contains("' + msg + '")').next().addClass('wfc-summary-ok').html('Secure.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDSUCCESS') != -1) {
                                        var msg = item.msg.replace('SUM_ENDSUCCESS:', '');
                                        parentBox.find('div.wfc-summary-msg:contains("' + msg + '")').next().addClass('wfc-summary-ok').html('Success.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_ENDERR') != -1) {
                                        var msg = item.msg.replace('SUM_ENDERR:', '');
                                        parentBox.find('div.wfc-summary-msg:contains("' + msg + '")').next().addClass('wfc-summary-err').html('An error occurred.');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_DISABLED:') != -1) {
                                        var msg = item.msg.replace('SUM_DISABLED:', '');
                                        parentBox.find('.mwp_consoleSummary').append('<div class="wfSummaryLine"><div class="wfc-summary-date">[' + item.date + ']</div><div class="wfc-summary-msg">' + msg + '</div><div class="wfc-summary-result">Disabled [<a href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_setting">Visit Options to Enable</a>]</div><div class="wfc-clear"></div>');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_PAIDONLY:') != -1) {
                                        var msg = item.msg.replace('SUM_PAIDONLY:', '');
                                        parentBox.find('.mwp_consoleSummary').append('<div class="wfSummaryLine"><div class="wfc-summary-date">[' + item.date + ']</div><div class="wfc-summary-msg">' + msg + '</div><div class="wfc-summary-result"><a href="https://www.wordfence.com/wordfence-signup/" target="_blank">Paid Members Only</a></div><div class="wfc-clear"></div>');
                                        summaryUpdated = true;
                                } else if (item.msg.indexOf('SUM_FINAL:') != -1) {
                                        var msg = item.msg.replace('SUM_FINAL:', '');
                                        parentBox.find('.mwp_consoleSummary').append('<div class="wfSummaryLine"><div class="wfc-summary-date">[' + item.date + ']</div><div class="wfc-summary-msg wfc-summary-final">' + msg + '</div><div class="wfc-summary-result wfc-summary-ok">Scan Complete.</div><div class="wfc-clear"></div>');
                                } else if (item.msg.indexOf('SUM_PREP:') != -1) {
                                        var msg = item.msg.replace('SUM_PREP:', '');
                                        parentBox.find('.mwp_consoleSummary').empty().html('<div class="wfSummaryLine"><div class="wfc-summary-date">[' + item.date + ']</div><div class="wfc-summary-msg">' + msg + '</div><div class="wfc-summary-result wfStartingScan" ><div class="wfc-summary-loading"></div></div><div class="wfc-clear"></div>');
                                } else if (item.msg.indexOf('SUM_KILLED:') != -1) {
                                        var msg = item.msg.replace('SUM_KILLED:', '');
                                        parentBox.find('.mwp_consoleSummary').empty().html('<div class="wfSummaryLine"><div class="wfc-summary-date">[' + item.date + ']</div><div class="wfc-summary-msg">' + msg + '</div><div class="wfc-summary-result wfc-summary-ok">Scan Complete.</div><div class="wfc-clear"></div>');
                                }
                        },
                        killScan: function (callback, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_killScan', { site_id: site_id }, function (res) {
                                        if (res.ok) {
                                                typeof callback === 'function' && callback(true);
                                                MWP_WFAD.scanRunning = false;
                                                MWP_WFAD.scanFailed = false;
                                                $(window).trigger('wfScanUpdateButtons');
                                        } else {
                                                typeof callback === 'function' && callback(false);
                                        }
                                });
                        },
                        startScan: function (site_id) {
                                this.ajax('mainwp_wfc_scan', { site_id: site_id }, function (res) {
                                        if (res.ok) {
                                                MWP_WFAD.scanRunning = true;
                                                $('#wf-scan-results-new').empty();
                                                $('#wf-scan-bulk-buttons-delete, #wf-scan-bulk-buttons-repair').addClass('wf-disabled');
                                                MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                $(window).trigger('wfScanUpdateButtons');
                                        }
                                });
                        },
                        downgradeLicense: function (site_id) {
                                jQuery.wfcolorbox('400px', "Confirm Downgrade", "Are you sure you want to downgrade your Wordfence Premium License? This will disable all Premium features and return you to the free version of Wordfence. <a href=\"https://www.wordfence.com/manage-wordfence-api-keys/\" target=\"_blank\">Click here to renew your paid membership</a> or click the button below to confirm you want to downgrade.<br /><br /><input type=\"button\" value=\"Downgrade and disable Premium features\" onclick=\"MWP_WFAD.downgradeLicenseConfirm(" + site_id + ");\" /><br />");
                        },
                        downgradeLicenseConfirm: function (site_id) {
                                jQuery.colorbox.close();
                                this.ajax('mainwp_wfc_downgradeLicense', { site_id: site_id }, function (res) {
                                        location.reload(true);
                                });
                        },
                        loadFirstActivityLog: function (site_id) {
                                this.initScan(site_id);
                                jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + '] .mwp_wordfence_network_scan_inner').show();
                                //this.activityLogUpdatePending[site_id] = true;
                                this.startActivityLogUpdates(site_id);
                                //                this.ajax('mainwp_wfc_loadFirstActivityLog', {site_id: site_id},
                                //                    function (res) {
                                //                        self.doneLoadFirstActivityLog(res, site_id);
                                //                        self.loadIssues(false, null, null, site_id);
                                //                        self.startActivityLogUpdates(site_id);
                                //                    },
                                //                    function () {
                                //                        self.activityLogUpdatePending[site_id] = false;
                                //                        self.loadIssues(false, null, null, site_id);
                                //                        self.startActivityLogUpdates(site_id);
                                //                    });
                        },
                        doneLoadFirstActivityLog: function (res, site_id) {
                                var parentBox = jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + ']');
                                parentBox.find('.mwp_consoleActivity').html(res['result']);
                                this.lastALogCtime[site_id] = res['lastctime'];
                                if (res['not_found_events'])
                                        parentBox.find('.mwp_consoleSummary').html('Welcome to Wordfence!<br><br>To get started, simply click the "Scan Now" link at the "Wordfence Dashboard" tab to start your first scan.');
                                else if (res['summary'])
                                        this.processActArray(res['summary'], site_id);
                                parentBox.find('.mwp_consoleActivity').scrollTop(parentBox.find('.mwp_consoleActivity').prop('scrollHeight'));
                                parentBox.find('.mwp_consoleSummary').scrollTop(parentBox.find('.mwp_consoleSummary').prop('scrollHeight'));
                                this.activityLogUpdatePending[site_id] = false;
                        },
                        //            loadIssues_Old: function (callback, site_id) {
                        //                if (typeof site_id == "undefined")
                        //                    return;
                        //                if (this.mode != 'scan' && this.mode != 'network_scan') {
                        //                    return;
                        //                }
                        //                var self = this;
                        //                this.ajax('mainwp_wfc_loadIssues', {site_id: site_id}, function (res) {
                        //                    self.displayIssues(res, callback, site_id);
                        //                });
                        //            },
                        loadIssues: function (callback, offset, limit, site_id) {
                                if (this.mode != 'scan') {
                                        return;
                                }
                                offset = offset || 0;
                                limit = limit || mainwp_WordfenceAdminVars.scanIssuesPerPage;
                                var self = this;
                                this.ajax('mainwp_wfc_loadIssues', { offset: offset, limit: limit, site_id: site_id }, function (res) {
                                        var newCount = parseInt(res.issueCounts.new) || 0;
                                        var ignoredCount = (parseInt(res.issueCounts.ignoreP) || 0) + (parseInt(res.issueCounts.ignoreC) || 0);
                                        jQuery('#wfNewIssuesTab .wfIssuesCount').text(' (' + newCount + ')');
                                        jQuery('#wfIgnoredIssuesTab .wfIssuesCount').text(' (' + ignoredCount + ')');
                                        self.displayIssues(res, callback, site_id);
                                });
                        },
                        loadMoreIssues: function (callback, offset, limit, site_id) {
                                offset = offset || 0;
                                limit = limit || mainwp_WordfenceAdminVars.scanIssuesPerPage;
                                var self = this;
                                this.ajax('mainwp_wfc_loadIssues', { offset: offset, limit: limit, site_id: site_id }, function (res) {
                                        self.appendIssues(res.issuesLists, callback, site_id);
                                });
                        },
                        appendIssues: function (issues, callback, site_id) {
                                for (var issueStatus in issues) {
                                        var containerID = 'wf-scan-results-' + issueStatus;
                                        console.log(containerID);
                                        if ($('#' + containerID).length < 1) {
                                                continue;
                                        }

                                        var container = $('#' + containerID);
                                        for (var i = 0; i < issues[issueStatus].length; i++) {
                                                var issueObject = issues[issueStatus][i];
                                                MWP_WFAD.appendIssue(issueObject, container, site_id);
                                        }
                                }

                                MWP_WFAD.sortIssues();

                                /*if (callback) {
                                        jQuery('#wfIssues_' + this.visibleIssuesPanel).fadeIn(500, function() {
                                                callback();
                                        });
                                } else {
                                        jQuery('#wfIssues_' + this.visibleIssuesPanel).fadeIn(500);
                                }*/
                        },
                        appendIssue: function (issueObject, container, site_id) {
                                var issueType = issueObject.type;
                                var tmplName = 'issueTmpl_' + issueType;
                                var template = $('#' + tmplName);
                                console.log(tmplName);
                                if (template.length) {
                                        var issue = template.tmpl(issueObject);
                                        issue.data('sourceData', issueObject);
                                        issue.data('templateName', tmplName);
                                        if (this.isIssueExpanded(issueObject.id)) {
                                                issue.addClass('wf-active');
                                        }

                                        if (issueObject.data.canDelete) {
                                                $('#wf-scan-bulk-buttons-delete').removeClass('wf-disabled');
                                        }

                                        if (issueObject.data.canFix) {
                                                $('#wf-scan-bulk-buttons-repair').removeClass('wf-disabled');
                                        }

                                        //Hook up Details button
                                        issue.find('.wf-issue-control-show-details').on('click', function (e) {
                                                e.preventDefault();
                                                e.stopPropagation();

                                                var isActive = $(this).closest('.wf-issue').hasClass('wf-active');
                                                var issueID = $(this).closest('.wf-issue').data('issueId');
                                                MWP_WFAD.expandIssue(issueID, !isActive);
                                                $(this).closest('.wf-issue').toggleClass('wf-active', !isActive);
                                        });

                                        //Hook up Ignore button
                                        issue.find('.wf-issue-control-ignore').each(function () {
                                                var issueID = $(this).closest('.wf-issue').data('issueId');
                                                var nextMenuEl = $(this).next('.wf-issue-control-ignore-menu')[0];
                                                var menu = $(nextMenuEl).menu().hide();

                                                $(this).on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        var ignoreAction = $(this).data('ignoreAction');
                                                        if (ignoreAction == 'choice') {
                                                                menu.show().position({
                                                                        my: "left top",
                                                                        at: "left bottom",
                                                                        of: this
                                                                });

                                                                $(document).on('click', function () {
                                                                        menu.hide();
                                                                });
                                                        }
                                                        else {
                                                                var self = this;
                                                                MWP_WFAD.updateIssueStatus(issueID, ignoreAction, function (res) {
                                                                        if (res.ok) {
                                                                                var issueContainer = $(self).closest('.wf-scan-results-issues');
                                                                                var issueElement = $(self).closest('.wf-issue');
                                                                                var sourceData = issueElement.data('sourceData');
                                                                                sourceData['status'] = ignoreAction;

                                                                                var targetContainerID = 'wf-scan-results-' + (issueContainer.attr('id') == 'wf-scan-results-new' ? 'ignored' : 'new');
                                                                                var targetContainer = $('#' + targetContainerID);
                                                                                issueElement.remove();
                                                                                MWP_WFAD.appendIssue(sourceData, targetContainer);
                                                                                MWP_WFAD.sortIssues();
                                                                                MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                                                MWP_WFAD.repositionSiteCleaningCallout();
                                                                        }
                                                                }, site_id);
                                                        }
                                                });

                                                menu.find('.wf-issue-control-ignore-menu-ignorec').on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        var self = this;
                                                        MWP_WFAD.updateIssueStatus(issueID, 'ignoreC', function (res) {
                                                                if (res.ok) {
                                                                        var issueContainer = $(self).closest('.wf-scan-results-issues');
                                                                        var issueElement = $(self).closest('.wf-issue');
                                                                        var sourceData = issueElement.data('sourceData');
                                                                        sourceData['status'] = 'ignoreC';

                                                                        var targetContainerID = 'wf-scan-results-' + (issueContainer.attr('id') == 'wf-scan-results-new' ? 'ignored' : 'new');
                                                                        var targetContainer = $('#' + targetContainerID);
                                                                        issueElement.remove();
                                                                        MWP_WFAD.appendIssue(sourceData, targetContainer);
                                                                        MWP_WFAD.sortIssues();
                                                                        MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                                        MWP_WFAD.repositionSiteCleaningCallout();
                                                                }
                                                        }, site_id);
                                                });

                                                menu.find('.wf-issue-control-ignore-menu-ignorep').on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        var self = this;
                                                        MWP_WFAD.updateIssueStatus(issueID, 'ignoreP', function (res) {
                                                                if (res.ok) {
                                                                        var issueContainer = $(self).closest('.wf-scan-results-issues');
                                                                        var issueElement = $(self).closest('.wf-issue');
                                                                        var sourceData = issueElement.data('sourceData');
                                                                        sourceData['status'] = 'ignoreP';

                                                                        var targetContainerID = 'wf-scan-results-' + (issueContainer.attr('id') == 'wf-scan-results-new' ? 'ignored' : 'new');
                                                                        var targetContainer = $('#' + targetContainerID);
                                                                        issueElement.remove();
                                                                        MWP_WFAD.appendIssue(sourceData, targetContainer);
                                                                        MWP_WFAD.sortIssues();
                                                                        MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                                        MWP_WFAD.repositionSiteCleaningCallout();
                                                                }
                                                        }, site_id);
                                                });
                                        });

                                        //Hook up Mark as Fixed button
                                        issue.find('.wf-issue-control-mark-fixed').each(function () {
                                                var issueID = $(this).closest('.wf-issue').data('issueId');

                                                $(this).on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        var self = this;
                                                        MWP_WFAD.updateIssueStatus(issueID, 'delete', function (res) {
                                                                if (res.ok) {
                                                                        var issueElement = $(self).closest('.wf-issue');
                                                                        issueElement.remove();
                                                                        MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                                        MWP_WFAD.repositionSiteCleaningCallout();
                                                                }
                                                        }, site_id);
                                                });
                                        });

                                        //Hook up Delete File button
                                        issue.find('.wf-issue-control-delete-file').each(function () {
                                                var issueID = $(this).closest('.wf-issue').data('issueId');

                                                $(this).on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        var self = this;
                                                        MWP_WFAD.deleteFile(issueID, false, function (res) {
                                                                if (res.ok) {
                                                                        var issueElement = $(self).closest('.wf-issue');
                                                                        issueElement.remove();
                                                                        MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                                        MWP_WFAD.repositionSiteCleaningCallout();
                                                                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), "Success deleting file", "The file " + res.file + " was successfully deleted.");
                                                                }
                                                                else if (res.errorMsg) {
                                                                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.errorMsg);
                                                                }
                                                        });
                                                });
                                        });

                                        //Hook up Repair button
                                        issue.find('.wf-issue-control-repair').each(function () {
                                                var issueID = $(this).closest('.wf-issue').data('issueId');

                                                $(this).on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        var self = this;
                                                        MWP_WFAD.restoreFile(issueID, function (res) {
                                                                if (res.ok) {
                                                                        var issueElement = $(self).closest('.wf-issue');
                                                                        issueElement.remove();
                                                                        MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                                        MWP_WFAD.repositionSiteCleaningCallout();
                                                                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), "Success restoring file", "The file " + res.file + " was successfully restored.");
                                                                }
                                                                else if (res.errorMsg) {
                                                                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.errorMsg);
                                                                }
                                                        });
                                                });
                                        });

                                        //Hook up Hide File button
                                        issue.find('.wf-issue-control-hide-file').each(function () {
                                                var issueID = $(this).closest('.wf-issue').data('issueId');

                                                $(this).on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        var self = this;
                                                        MWP_WFAD.hideFile(issueID, function (res) {
                                                                if (res.ok) {
                                                                        var issueElement = $(self).closest('.wf-issue');
                                                                        issueElement.remove();
                                                                        MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                                        MWP_WFAD.repositionSiteCleaningCallout();
                                                                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), "File hidden successfully", "The file " + res.file + " was successfully hidden from public view.");
                                                                }
                                                                else if (res.errorMsg) {
                                                                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.errorMsg);
                                                                }
                                                        }, site_id);
                                                });
                                        });

                                        //Swap out if the row already exists
                                        var existing = $('.wf-issue[data-issue-id="' + issueObject.id + '"]');
                                        if (existing.length) {
                                                existing.replaceWith(issue);
                                        }
                                        else {
                                                container.append(issue);
                                        }

                                        //Make row tappable
                                        issue.find('.wf-issue-summary').on('mousedown', function (e) {
                                                $(this).data('clickTapX', e.pageX).data('clickTapY', e.pageY);
                                        }).on('click', function (e) {
                                                var buffer = 10;
                                                var clickTapX = $(this).data('clickTapX');
                                                var clickTapY = $(this).data('clickTapY');
                                                if (clickTapX > e.pageX - buffer && clickTapX < e.pageX + buffer && clickTapY > e.pageY - buffer && clickTapY < e.pageY + buffer) {
                                                        var links = $(this).find('a');
                                                        for (var i = 0; i < links.length; i++) {
                                                                var t = $(links[i]).offset().top;
                                                                var l = $(links[i]).offset().left;
                                                                var b = t + $(links[i]).height();
                                                                var r = l + $(links[i]).width();

                                                                if (e.pageX > l - buffer && e.pageX < r + buffer && e.pageY > t - buffer && e.pageY < b + buffer) {
                                                                        return;
                                                                }
                                                        }

                                                        $(this).closest('.wf-issue').find('li.wf-issue-controls .wf-issue-control-show-details').trigger('click');
                                                }
                                        }).css('cursor', 'pointer');
                                }
                        },
                        expandIssue: function (issueID, makeVisible) {
                                var key = 'wf-scan-issue-expanded-' + issueID;
                                if (window.localStorage) {
                                        window.localStorage.setItem(key, makeVisible ? 1 : 0);
                                }
                        },
                        repositionSiteCleaningCallout: function () {
                                $('.wf-issue-site-cleaning').remove();

                                var issueTypes = MWP_WFAD.siteCleaningIssueTypes;
                                for (var i = 0; i < issueTypes.length; i++) {
                                        if ($('#wf-scan-results-new .wf-issue-' + issueTypes[i]).length) {
                                                if (!!$('#wf-scan-results-new .wf-issue-' + issueTypes[i]).data('highSensitivity')) {
                                                        $('#wf-scan-results-new .wf-issue').first().after($('#siteCleaningHighSenseTmpl').tmpl());
                                                }
                                                else {
                                                        $('#wf-scan-results-new .wf-issue').first().after($('#siteCleaningTmpl').tmpl());
                                                }
                                                return;
                                        }
                                }
                        },
                        isIssueExpanded: function (issueID) {
                                var key = 'wf-scan-issue-expanded-' + issueID;
                                if (window.localStorage) {
                                        return !!parseInt(window.localStorage.getItem(key));
                                }
                                return false;
                        },
                        sortIssues: function () {
                                var issueTypes = ['new', 'ignored'];
                                for (var i = 0; i < issueTypes.length; i++) {
                                        var containerID = 'wf-scan-results-' + issueTypes[i];
                                        if ($('#' + containerID).length < 1) {
                                                continue;
                                        }

                                        var container = $('#' + containerID);
                                        var issuesDOM = container.find('.wf-issue');
                                        issuesDOM.detach();
                                        issuesDOM.sort(function (a, b) {
                                                var severityA = $(a).data('issueSeverity');
                                                var severityB = $(b).data('issueSeverity');
                                                if (severityA < severityB) { return -1; }
                                                else if (severityA > severityB) { return 1; }

                                                var typeA = $(a).data('issueType');
                                                var typeB = $(b).data('issueType');

                                                var typeAIndex = MWP_WFAD.siteCleaningIssueTypes.indexOf(typeA);
                                                var typeBIndex = MWP_WFAD.siteCleaningIssueTypes.indexOf(typeB);
                                                if (typeAIndex > -1 && typeBIndex > -1) {
                                                        if (typeAIndex < typeBIndex) { return -1; }
                                                        else if (typeAIndex > typeBIndex) { return 1; }
                                                        return 0;
                                                }
                                                else if (typeAIndex > -1) {
                                                        return -1;
                                                }
                                                else if (typeBIndex > -1) {
                                                        return 1;
                                                }

                                                if (typeA < typeB) { return -1; }
                                                else if (typeA > typeB) { return 1; }

                                                return 0;
                                        });
                                        container.append(issuesDOM);
                                }

                                MWP_WFAD.repositionSiteCleaningCallout();
                        },
                        load_wafData: function (callback, site_id) {
                                if (typeof site_id == "undefined")
                                        return;

                                var self = this;
                                this.ajax('mainwp_wfc_load_wafData', { site_id: site_id }, function (res) {

                                        $('#waf-settings-loading').hide();

                                        if (res && res.error) {
                                                self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.error);
                                        }

                                        if (res && res.wafData) {

                                                self.wafData = res.wafData;
                                                self.current_ip = res.ip;
                                                self.watcherOptions.front = res.ajaxWatcherDisabled_front;
                                                self.watcherOptions.admin = res.ajaxWatcherDisabled_admin;

                                                //                        var checkBox = $('.wf-option.wf-option-toggled-multiple ul[data-option="ajaxWatcherDisabled_admin"]').find('.wf-option-checkbox');
                                                //                        var isActive = checkBox.hasClass('wf-checked');
                                                //                        if (res.ajaxWatcherDisabled_admin){
                                                //                            if (isActive)
                                                //                                checkBox.removeClass('wf-checked'); // disable so uncheck it
                                                //                        }
                                                //                        else {
                                                //                            if (!isActive)
                                                //                                checkBox.addClass('wf-checked'); // enable so check it
                                                //                        }
                                                //
                                                //                        var checkBox = $('.wf-option.wf-option-toggled-multiple ul[data-option="ajaxWatcherDisabled_front"]').find('.wf-option-checkbox');
                                                //                        var isActive = checkBox.hasClass('wf-checked');
                                                //                        if (res.ajaxWatcherDisabled_front){
                                                //                            if (isActive)
                                                //                                checkBox.removeClass('wf-checked'); // disable so uncheck it
                                                //                        }
                                                //                        else {
                                                //                            if (!isActive)
                                                //                                checkBox.addClass('wf-checked'); // enable so check it
                                                //
                                                //                        }
                                                $('#mwp-wf-save-changes').show();
                                                typeof callback === 'function' && callback();
                                        }
                                });
                        },
                        switchTab: function (tabElement, tabClass, contentClass, selectedContentID, callback) {
                                jQuery('.' + tabClass).removeClass('selected');
                                jQuery(tabElement).addClass('selected');
                                jQuery('.' + contentClass).hide().html('<div class="wfLoadingWhite32"></div>');
                                var func = function () {
                                };
                                if (callback) {
                                        func = function () {
                                                callback();
                                        };
                                }
                                jQuery('#' + selectedContentID).fadeIn(func);
                        },
                        activityTabChanged: function (site_id) {
                                var mode = jQuery('.wfDataPanel:visible').length > 0 ? jQuery('.wfDataPanel:visible')[0].id.replace('wfActivity_', '') : '';
                                if (!mode) {
                                        return;
                                }
                                this.activityMode = mode;
                                this.reloadActivities(site_id);
                        },
                        reloadActivities: function (site_id) {
                                jQuery('#wfActivity_' + this.activityMode).html('<div class="wfLoadingWhite32"></div>');
                                this.newestActivityTime[site_id] = 0;
                                if (this.networkActivity) {
                                        jQuery('.wfc_NetworkTrafficItemProcess').attr('status', 'queue');
                                }
                                this.updateTicker(true, site_id);
                        },
                        initActivities: function (site_id) {
                                if (this.activityModeSaved)
                                        this.activityMode = this.activityModeSaved;
                                this.tickerUpdatePending = false;

                                jQuery('#wfActivity_' + this.activityMode).html('<div class="wfLoadingWhite32"></div>');
                                this.newestActivityTime[site_id] = 0;
                                if (this.networkActivity) {
                                        jQuery('.wfc_NetworkTrafficItemProcess').attr('status', 'queue');
                                }
                        },
                        updateTicker: function (forceUpdate, site_id) {
                                console.log('updateTicker');
                                if ((!forceUpdate) && (this.tickerUpdatePending || !this.windowHasFocus() || !this._tabHasFocus)) {
                                        if (this.tickerUpdatePending)
                                                console.log('Pending');
                                        if (!this.windowHasFocus())
                                                console.log('Win lost focus');
                                        if (!this._tabHasFocus)
                                                console.log('Tab lost focus');
                                        console.log('Skip');
                                        return;
                                }

                                if (this.networkActivity) {
                                        var itemProcess = jQuery('.wfc_NetworkTrafficItemProcess[status="queue"]:first');
                                        if (itemProcess.length == 0) {
                                                jQuery('.wfc_NetworkTrafficItemProcess').attr('status', 'queue');
                                                //                        if (jQuery('.wfc_NetworkTrafficItemProcess[status="queue"]').length > 0) {
                                                //                            this.updateTicker(forceUpdate);
                                                //                        }
                                                return;
                                        }
                                        site_id = itemProcess.attr('site-id');
                                        this.cacheType = itemProcess.attr('cacheType');
                                        itemProcess.attr('status', 'processed');
                                }

                                //console.log('updateTicker: ' + this.mode + ' site_id ' + site_id);

                                if (typeof site_id == "undefined" || !site_id || site_id < 1)
                                        return;

                                if (forceUpdate)
                                        this.forceUpdate = forceUpdate;

                                this.tickerUpdatePending = true;
                                var self = this;
                                var alsoGet = '';
                                var otherParams = '';
                                var data = '';

                                if (this.mode == 'liveTraffic') {
                                        alsoGet = 'liveTraffic';
                                        otherParams = this.newestActivityTime[site_id];;
                                        if (typeof MWP_WFAD.wfLiveTraffic !== undefined) {
                                                data += this.wfLiveTraffic.getCurrentQueryString({
                                                        since: this.newestActivityTime[site_id]
                                                });
                                        }
                                } else if (this.mode == 'liveTraffic' && /^(?:404|hit|human|ruser|gCrawler|crawler|loginLogout)$/.test(this.activityMode)) {
                                        alsoGet = 'logList_' + this.activityMode;
                                        otherParams = this.newestActivityTime[site_id];
                                }

                                data += '&alsoGet=' + encodeURIComponent(alsoGet) +
                                        '&otherParams=' + encodeURIComponent(otherParams) +
                                        '&site_id=' + encodeURIComponent(site_id) +
                                        '&forceUpdate=' + encodeURIComponent(forceUpdate) +
                                        '&cacheType=' + encodeURIComponent(this.cacheType);

                                this.ajax('mainwp_wfc_ticker', data, function (res) {
                                        self.tickerUpdatePending = false;

                                        if (typeof res === 'undefined' || !res) {
                                                return;
                                        }

                                        if (res['reload'] == 'reload') {
                                                jQuery.wfcolorbox('400px', "Please reload this page", "A config option on the site has been change and requires a page reload. Click the button below to reload this page to update the menu.<br /><br /><center><input type='button' name='wfReload' value='Reload page' onclick='window.location.reload(true);' /></center>");
                                                return;
                                        }

                                        // to fix display
                                        if (self.forceUpdate && !res['forceUpdate']) {
                                                return;
                                        } else if (self.forceUpdate)
                                                self.forceUpdate = false;

                                        self.handleTickerReturn(res);
                                }, function () {
                                        self.tickerUpdatePending = false;
                                }, true);
                        },
                        handleTickerReturn: function (res) {
                                var newMsg = "";
                                var siteProcess;
                                if (this.networkActivity) {
                                        siteProcess = jQuery('.wfc_NetworkTrafficItemProcess[site-id="' + res.site_id + '"]');
                                        var statusSite = jQuery('#wfLiveStatusSite');
                                        if (res.site_id != statusSite.attr('site-id')) {
                                                statusSite.attr('site-id', res.site_id);
                                                statusSite.hide().html(siteProcess.attr('site-name') + ' -&nbsp;').fadeIn(200);
                                        }
                                }

                                var oldMsg = jQuery('#wfLiveStatus').text();
                                if (res.msg) {
                                        newMsg = res.msg;
                                } else {
                                        newMsg = "Idle";
                                }
                                if (newMsg && newMsg != oldMsg) {
                                        jQuery('#wfLiveStatus').hide().html(newMsg).fadeIn(200);
                                }

                                var haveEvents, newElem;
                                //this.serverTimestampOffset = (new Date().getTime() / 1000) - res.serverTime;
                                this.serverMicrotime = res.serverMicrotime;

                                if (this.mode == 'liveTraffic') {
                                        if (res.events.length > 0) {
                                                this.newestActivityTime[res.site_id] = res.events[0]['ctime'];
                                        }
                                        if (typeof MWP_WFAD.wfLiveTraffic !== undefined) {
                                                MWP_WFAD.wfLiveTraffic.prependListings(res.events, res);
                                                this.reverseLookupIPs(res.site_id);
                                                this.updateTimeAgo();
                                        }
                                } else if (this.mode == 'activity') {  // This mode is deprecated as of 6.1.0
                                        if (res.alsoGet != 'logList_' + this.activityMode) {
                                                return;
                                        } //user switched panels since ajax request started
                                        if (res.events.length > 0) {
                                                this.newestActivityTime[res.site_id] = res.events[0]['ctime'];
                                        }
                                        var haveEvents = false;
                                        if (jQuery('#wfActivity_' + this.activityMode + ' .wfActEvent').length > 0) {
                                                haveEvents = true;
                                        }
                                        if (res.events.length > 0) {
                                                if (!haveEvents) {
                                                        jQuery('#wfActivity_' + this.activityMode).empty();
                                                }
                                                for (i = res.events.length - 1; i >= 0; i--) {
                                                        var elemID = '#wfActEvent_' + res.events[i].id;
                                                        if (jQuery(elemID).length < 1) {
                                                                res.events[i]['activityMode'] = this.activityMode;
                                                                res.events[i]['site_id'] = res.site_id
                                                                var newElem;
                                                                if (this.activityMode == 'loginLogout') {
                                                                        newElem = jQuery('#wfLoginLogoutEventTmpl').tmpl(res.events[i]);
                                                                } else {
                                                                        newElem = jQuery('#wfHitsEventTmpl').tmpl(res.events[i]);
                                                                }
                                                                jQuery(newElem).find('.wfTimeAgo').data('wfctime', res.events[i].ctime);
                                                                newElem.prependTo('#wfActivity_' + this.activityMode).fadeIn();
                                                        }
                                                }
                                                this.reverseLookupIPs(res.site_id);
                                                this.updateTimeAgo(res.site_id);
                                        } else {
                                                if (!haveEvents) {
                                                        jQuery('#wfActivity_' + this.activityMode).html('<div>No events to report yet.</div>');
                                                }
                                        }
                                        var self = this;
                                        jQuery('.wfTimeAgo').each(function (idx, elem) {
                                                jQuery(elem).html(self.makeTimeAgo(res.serverTime - jQuery(elem).data('wfctime')) + ' ago');
                                        });
                                }
                        },
                        utf8_to_b64: function (str) {
                                return window.btoa(str);
                                //return window.btoa(encodeURIComponent( escape( str )));
                        },
                        staticNetworkTabChanged: function () {
                                var mode = jQuery('.wfDataPanel:visible').length > 0 ? jQuery('.wfDataPanel:visible')[0].id.replace('wfActivity_', '') : '';
                                if (!mode) {
                                        return;
                                }
                                this.activityMode = mode;
                                jQuery('.wfc_NetworkTrafficItemProcess').attr('status', 'queue');
                                var contentElem = '#wfActivity_' + this.activityMode;
                                this.loadStaticNetworkPanelNext(contentElem, true);
                        },
                        loadStaticNetworkPanelNext: function (contentEl, first) {
                                var itemProcess = jQuery('.wfc_NetworkTrafficItemProcess[status="queue"]:first');
                                if (itemProcess.length <= 0)
                                        return;

                                var site_id = itemProcess.attr('site-id');
                                var site_name = itemProcess.attr('site-name');
                                var self = this;
                                this.ajax('mainwp_wfc_loadStaticPanel', {
                                        site_id: site_id,
                                        mode: this.activityMode
                                }, function (res) {
                                        res.site_id = site_id;
                                        res.site_name = site_name;
                                        if (first)
                                                jQuery(contentEl).empty();
                                        //self.completeLoadNetworkStaticPanel(res, contentEl);
                                        itemProcess.attr('status', 'processed');
                                        self.loadStaticNetworkPanelNext(contentEl);
                                });
                        },
                        completeLoadNetworkStaticPanel: function (res, contentEl) {
                                if (res.results && res.results.length > 0) {
                                        var tmpl;
                                        if (this.activityMode == 'topScanners' || this.activityMode == 'topLeechers') {
                                                tmpl = '#wfLeechersTmpl';
                                        } else if (this.activityMode == 'blockedIPs') {
                                                tmpl = '#wfBlockedIPsTmpl';
                                        } else if (this.activityMode == 'lockedOutIPs') {
                                                tmpl = '#wfLockedOutIPsTmpl';
                                        } else if (this.activityMode == 'throttledIPs') {
                                                tmpl = '#wfThrottledIPsTmpl';
                                        } else {
                                                return;
                                        }
                                        var i, j, chunk = 1000;
                                        var bigArray = res.results.slice(0);
                                        res.results = false;
                                        for (i = 0, j = bigArray.length; i < j; i += chunk) {
                                                res.results = bigArray.slice(i, i + chunk);
                                                jQuery(tmpl).tmpl(res).appendTo(contentEl);
                                        }
                                        this.reverseLookupIPs(res.site_id);
                                } else {
                                        if (this.activityMode == 'topScanners' || this.activityMode == 'topLeechers') {
                                                jQuery("<span>" + res.site_name + ": No site hits have been logged yet. Check back soon.</span><br>").appendTo(contentEl);
                                        } else if (this.activityMode == 'blockedIPs') {
                                                jQuery("<span>" + res.site_name + ": No IP addresses have been blocked yet. If you manually block an IP address or if Wordfence automatically blocks one, it will appear here.</span><br>").appendTo(contentEl);
                                        } else if (this.activityMode == 'lockedOutIPs') {
                                                jQuery("<span>" + res.site_name + ": No IP addresses have been locked out from signing in or using the password recovery system.</span><br>").appendTo(contentEl);
                                        } else if (this.activityMode == 'throttledIPs') {
                                                jQuery("<span>" + res.site_name + ": No IP addresses have been throttled yet. If an IP address accesses the site too quickly and breaks one of the Wordfence rules, it will appear here.</span><br>").appendTo(contentEl);
                                        } else {
                                                return;
                                        }
                                }
                        },
                        staticTabChanged: function (site_id) {
                                if (site_id < 1)
                                        return;

                                var mode = jQuery('.wfDataPanel:visible').length > 0 ? jQuery('.wfDataPanel:visible')[0].id.replace('wfActivity_', '') : '';
                                if (!mode) {
                                        return;
                                }
                                this.activityMode = mode;

                                var self = this;
                                this.ajax('mainwp_wfc_loadStaticPanel', {
                                        site_id: site_id,
                                        mode: this.activityMode
                                }, function (res) {
                                        res.site_id = site_id;
                                        self.completeLoadStaticPanel(res);
                                });
                        },
                        completeLoadStaticPanel: function (res) {
                                var contentElem = '#wfActivity_' + this.activityMode;
                                jQuery(contentElem).empty();
                                var load_BlockRanges = false;
                                if (res.results && res.results.length > 0) {
                                        var tmpl;
                                        if (this.activityMode == 'topScanners' || this.activityMode == 'topLeechers') {
                                                tmpl = '#wfLeechersTmpl';
                                        } else if (this.activityMode == 'blockedIPs') {
                                                tmpl = '#wfBlockedIPsTmpl';
                                                load_BlockRanges = true;
                                        } else if (this.activityMode == 'lockedOutIPs') {
                                                tmpl = '#wfLockedOutIPsTmpl';
                                        } else if (this.activityMode == 'throttledIPs') {
                                                tmpl = '#wfThrottledIPsTmpl';
                                        } else {
                                                return;
                                        }
                                        var i, j, chunk = 1000;
                                        var bigArray = res.results.slice(0);
                                        res.results = false;
                                        for (i = 0, j = bigArray.length; i < j; i += chunk) {
                                                res.results = bigArray.slice(i, i + chunk);
                                                jQuery(tmpl).tmpl(res).appendTo(contentElem);
                                        }
                                        this.reverseLookupIPs(res.site_id);
                                } else {
                                        if (this.activityMode == 'topScanners' || this.activityMode == 'topLeechers') {
                                                jQuery(contentElem).html("No site hits have been logged yet. Check back soon.");
                                        } else if (this.activityMode == 'blockedIPs') {
                                                jQuery(contentElem).html("No IP addresses have been blocked yet. If you manually block an IP address or if Wordfence automatically blocks one, it will appear here.");
                                                load_BlockRanges = true;
                                        } else if (this.activityMode == 'lockedOutIPs') {
                                                jQuery(contentElem).html("No IP addresses have been locked out from signing in or using the password recovery system.");
                                        } else if (this.activityMode == 'throttledIPs') {
                                                jQuery(contentElem).html("No IP addresses have been throttled yet. If an IP address accesses the site too quickly and breaks one of the Wordfence rules, it will appear here.");
                                        } else {
                                                return;
                                        }
                                }

                                // load block ranges
                                if (load_BlockRanges) {
                                        this.mode = 'rangeBlocking';
                                        var site_id = jQuery('#mwp_wfc_current_site_id').attr('site-id');
                                        this.calcRangeTotal();
                                        this.loadBlockRanges(site_id);
                                }

                        },
                        reverseLookupIPs: function (site_id) {
                                if (typeof site_id == "undefined")
                                        return;

                                var self = this;
                                var ips = [];
                                jQuery('.wfReverseLookup').each(function (idx, elem) {
                                        var txt = jQuery(elem).text().trim();
                                        if (/^\d+\.\d+\.\d+\.\d+$/.test(txt) && (!jQuery(elem).data('wfReverseDone'))) {
                                                jQuery(elem).data('wfReverseDone', true);
                                                ips.push(txt);
                                        }
                                });
                                //console.log('reverseLookupIPs: ' + ips.length + ' site_id ' + site_id);
                                if (ips.length < 1) {
                                        return;
                                }
                                var uni = {};
                                var uniqueIPs = [];
                                for (var i = 0; i < ips.length; i++) {
                                        if (!uni[ips[i]]) {
                                                uni[ips[i]] = true;
                                                uniqueIPs.push(ips[i]);
                                        }
                                }
                                this.ajax('mainwp_wfc_reverseLookup', {
                                        ips: uniqueIPs.join(','),
                                        site_id: site_id
                                },
                                        function (res) {
                                                if (res.ok) {
                                                        jQuery('.wfReverseLookup').each(function (idx, elem) {
                                                                var txt = jQuery(elem).text().trim();
                                                                for (var ip in res.ips) {
                                                                        if (txt == ip) {
                                                                                if (res.ips[ip]) {
                                                                                        jQuery(elem).html('<strong>Hostname:</strong>&nbsp;' + self.htmlEscape(res.ips[ip]));
                                                                                } else {
                                                                                        jQuery(elem).html('');
                                                                                }
                                                                        }
                                                                }
                                                        });
                                                }
                                        }, false, false);
                        },
                        makeIPTrafLink: function (IP, site_id) {
                                var url = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site';
                                var loc = '?_wfsf=IPTraf&nonce=child_temp_nonce&IP=' + encodeURIComponent(IP);
                                url = url + '&websiteid=' + site_id + '&open_location=' + this.utf8_to_b64(loc);
                                return url;
                        },
                        makeBlockNetworkLink: function (IP, site_id) {
                                var loc = this.utf8_to_b64('admin.php?page=WordfenceWhois&wfnetworkblock=1&whoisval=' + IP);
                                return "admin.php?page=SiteOpen&newWindow=yes&websiteid=" + site_id + "&location=" + loc + '&_opennonce=' + mainwpParams._wpnonce;
                        },
                        makeWhoIsLink: function (IP, site_id) {
                                var loc = this.utf8_to_b64('admin.php?page=WordfenceWhois&whoisval=' + IP);
                                return "admin.php?page=SiteOpen&newWindow=yes&websiteid=" + site_id + "&location=" + loc + '&_opennonce=' + mainwpParams._wpnonce;
                        },
                        unblockIP: function (IP, site_id, callback) {
                                var self = this;
                                this.ajax('mainwp_wfc_unblockIP', {
                                        IP: IP,
                                        site_id: site_id
                                }, function (res) {
                                        var msg = '';
                                        if (res) {
                                                if (res.error) {
                                                        msg = res.error;
                                                } else if (res.errorMsg) {
                                                        msg = res.errorMsg;
                                                } else {
                                                        self.reloadActivities(site_id);
                                                        typeof callback === 'function' && callback();
                                                        return;
                                                }
                                        } else {
                                                msg = 'Undefined Error';
                                        }
                                        if (msg !== '') {
                                                self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', msg);
                                        }
                                });
                        },
                        blockIP: function (IP, reason, site_id, callback) {
                                var self = this;
                                this.ajax('mainwp_wfc_blockIP', {
                                        IP: IP,
                                        reason: reason,
                                        site_id: site_id
                                }, function (res) {
                                        var msg = '';
                                        if (res) {
                                                if (res.error) {
                                                        msg = res.error;
                                                } else if (res.errorMsg) {
                                                        msg = res.errorMsg;
                                                } else {
                                                        self.reloadActivities(site_id);
                                                        typeof callback === 'function' && callback();
                                                        return;
                                                }
                                        } else {
                                                msg = 'Undefined Error';
                                        }
                                        if (msg !== '') {
                                                MWP_WFAD.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', msg);
                                        }
                                });
                        },
                        unblockIPNetwork: function (IP) {
                                this.processBlockUnBlockIPNetwork(IP, '', 'unblock');
                        },
                        unblockIPNetwork: function (IP) {
                                this.processBlockUnBlockIPNetwork(IP, '', 'unblock');
                        },
                        blockIPNetwork: function (IP, reason) {
                                this.processBlockUnBlockIPNetwork(IP, reason, 'block');
                        },
                        processBlockUnBlockIPNetwork: function (IP, reason, what) {
                                this.activityModeSaved = this.activityMode;
                                //console.log(this.activityMode);
                                var currentActContent = '#wf-live-traffic';
                                this.tickerUpdatePending = true;
                                this.activityMode = 'network_blockip';
                                clearInterval(this.liveInt);
                                var html = '';
                                if (what == 'block')
                                        html += "<h3>Block IP " + IP + " across network</h3>";
                                else
                                        html += "<h3>Un-Block IP " + IP + " across network</h3>";

                                jQuery('.wfc_NetworkTrafficItemProcess').each(function () {
                                        var siteName = jQuery(this).attr('site-name');
                                        var siteId = jQuery(this).attr('site-id');
                                        html += '<div style="margin-bottom: 5px"><strong>' + siteName + '</strong>: ';
                                        html += '<span class="itemToProcess" site-id="' + siteId + '" status="queue"> <span class="status">Queue</span><br />';
                                        html += '</div>';
                                });
                                html += '<div id="wfc_block_ip_ajax_message" class="mainwp_info-box-yellow hidden"></div>';

                                jQuery(currentActContent).empty();
                                jQuery(html).appendTo(currentActContent);

                                this.bulkTotalThreads = jQuery('.itemToProcess[status="queue"]').length;
                                this.bulkCurrentThreads = 0;
                                this.bulkFinishedThreads = 0;

                                this.blockIPNetworkStartNext(IP, reason, what);

                        },
                        blockIPNetworkStartNext: function (IP, reason, what) {
                                while ((itemProcess = jQuery('.itemToProcess[status="queue"]:first')) && (itemProcess.length > 0) && (this.bulkCurrentThreads < this.bulkMaxThreads)) {
                                        itemProcess.removeClass('queue');
                                        this.blockIPNetworkStartSpecific(itemProcess, IP, reason, what);
                                }
                        },
                        blockIPNetworkStartSpecific: function (pItemProcess, IP, reason, what) {
                                this.bulkCurrentThreads++;
                                pItemProcess.attr('status', 'processed');
                                var statusEl = pItemProcess.find('.status').html('<i class="fa fa-spinner fa-pulse"></i> Running ...');
                                var site_id = pItemProcess.attr('site-id');
                                var self = this;
                                var action = 'mainwp_wfc_blockIP';

                                if (what == 'unblock')
                                        action = 'mainwp_wfc_unblockIP';

                                this.ajax(action, {
                                        IP: IP,
                                        reason: reason,
                                        site_id: site_id,
                                        network: 1
                                }, function (response) {
                                        if (response) {
                                                if (response.success) {
                                                        statusEl.html('Successful').show();
                                                } else if (response['_error']) {
                                                        statusEl.html(response['_error']).show();
                                                        statusEl.css('color', 'red');
                                                } else if (response['_errorMsg']) {
                                                        statusEl.html(response['_errorMsg']).show();
                                                        statusEl.css('color', 'red');
                                                } else {
                                                        statusEl.html(__('Undefined Error')).show();
                                                        statusEl.css('color', 'red');
                                                }
                                        } else {
                                                statusEl.html(__('Undefined Error')).show();
                                                statusEl.css('color', 'red');
                                        }

                                        self.bulkCurrentThreads--;
                                        self.bulkFinishedThreads++;
                                        if (self.bulkFinishedThreads == self.bulkTotalThreads && self.bulkFinishedThreads != 0) {
                                                var msg = 'Block IP finished.';
                                                if (what == 'unblock')
                                                        msg = 'Un-Block IP finished.'
                                                jQuery('#wfc_block_ip_ajax_message').html(msg).fadeIn(100);
                                                jQuery('#wfc_block_ip_ajax_message').after('<p><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_traffic">' + __('Return to Network Live Traffic') + '</a></p>');
                                        }
                                        self.blockIPNetworkStartNext(IP, reason, what);
                                })
                        },
                        //           
                        confirmSwitchToFalcon: function (site_id, noEditHtaccess) {
                                jQuery.colorbox.close();
                                var cacheType = 'falcon';
                                var self = this;
                                this.ajax('mainwp_wfc_saveCacheConfig', {
                                        cacheType: cacheType,
                                        noEditHtaccess: noEditHtaccess,
                                        site_id: site_id
                                }, function (res) {
                                        if (res.ok) {
                                                jQuery.wfcolorbox('400px', res.heading, res.body);
                                        }
                                }
                                );
                        },
                        saveCacheConfig: function (site_id) {
                                var cacheType = jQuery('input:radio[name=cacheType]:checked').val();
                                if (cacheType == 'falcon') {
                                        return this.switchToFalcon(site_id);
                                }
                                var self = this;
                                this.ajax('mainwp_wfc_saveCacheConfig', {
                                        cacheType: cacheType,
                                        site_id: site_id,
                                        individual: 1
                                }, function (res) {
                                        if (res.ok) {
                                                jQuery.wfcolorbox('400px', res.heading, res.body);
                                        }
                                }
                                );
                        },
                        openChildSite: function (website_id, url) {
                                this.ajax('mainwp_wfc_openChildSite', {
                                        site_id: website_id,
                                        open_location: url
                                }, function (res) {
                                        if (res.ok) {
                                                window.open(res.url);
                                        }
                                }
                                );
                        },
                        saveCacheOptions: function (pObj, website_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_saveCacheOptions', {
                                        allowHTTPSCaching: (jQuery('#wfallowHTTPSCaching').is(':checked') ? 1 : 0),
                                        /*addCacheComment: (jQuery('#wfaddCacheComment').is(':checked') ? 1 : 0),*/
                                        clearCacheSched: (jQuery('#wfclearCacheSched').is(':checked') ? 1 : 0),
                                        individual: 1,
                                        site_id: website_id
                                }, function (res) {
                                        if (res.updateErr) {
                                                jQuery.wfcolorbox('400px', "You need to manually update your .htaccess", res.updateErr + "<br />Your option was updated but you need to change the Wordfence code in your .htaccess to the following:<br /><textarea style='width: 300px; height: 120px;'>" + jQuery('<div/>').text(res.code).html() + '</textarea>');
                                        }
                                        if (res.ok) {
                                                self.showFloatingMessage(pObj, 'Successful');
                                        }
                                }
                                );
                        },
                        bulkSaveCacheOptions: function () {
                                this.ajax('mainwp_wfc_bulkSaveCacheOptions', {
                                        allowHTTPSCaching: (jQuery('#wfallowHTTPSCaching').is(':checked') ? 1 : 0),
                                        /*addCacheComment: (jQuery('#wfaddCacheComment').is(':checked') ? 1 : 0),*/
                                        clearCacheSched: (jQuery('#wfclearCacheSched').is(':checked') ? 1 : 0),
                                        individual: 1
                                }, function (res) {
                                        if (res.ok) {
                                                location.href = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=save_cache_options&bulk_perform_action=1&nonce=' + mainwp_WordfenceAdminVars.nonce;
                                        }
                                }
                                );
                        },
                        clearPageCache: function (website_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_clearPageCache', { site_id: website_id }, function (res) {
                                        if (res.ok) {
                                                jQuery.wfcolorbox('400px', res.heading, res.body);
                                        }
                                });
                        },
                        pulse: function (sel) {
                                jQuery(sel).fadeIn(function () {
                                        setTimeout(function () {
                                                jQuery(sel).fadeOut();
                                        }, 2000);
                                });
                        },
                        getCacheStats: function (website_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_getCacheStats', { site_id: website_id }, function (res) {
                                        if (res.ok) {
                                                jQuery.wfcolorbox('400px', res.heading, res.body);
                                        }
                                });
                        },
                        getDiagnostics: function (website_id) {
                                if (website_id == '-1') {
                                        jQuery('#mainwp_diagnostics_child_resp').hide();
                                        return;
                                }
                                jQuery('#mainwp_wfc_diagnostics_child_loading').show();
                                jQuery('#mainwp_diagnostics_child_resp').hide();

                                this.ajax('mainwp_wfc_getDiagnostics', { site_id: website_id }, function (res) {
                                        jQuery('#mainwp_wfc_diagnostics_child_loading').hide();
                                        if (res.ok) {
                                                jQuery('#mainwp_diagnostics_child_resp').html(res.html).show();
                                                var open_url = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site';
                                                var conf_href = open_url + "&websiteid=" + website_id + "&open_location=" + MWP_WFAD.utf8_to_b64("?_wfsf=sysinfo&nonce=child_temp_nonce");
                                                var test_href = open_url + "&websiteid=" + website_id + "&open_location=" + MWP_WFAD.utf8_to_b64("?_wfsf=testmem&nonce=child_temp_nonce");
                                                jQuery('#mwp_wfc_system_conf_lnk').attr('href', conf_href);
                                                jQuery('#mwp_wfc_test_mem_lnk').attr('href', test_href);

                                                jQuery('#mwp_wfc_other_test_box').show();

                                                jQuery('.downloadLogFile').each(function () {
                                                        var url = mainwp_wfc_get_donwnloadlink(website_id, jQuery(this).data('logfile'));
                                                        $(this).attr('href', url);
                                                })

                                        } else {
                                                if (res.error) {
                                                        jQuery('#mainwp_diagnostics_child_resp').html(res.error).show();
                                                } else {
                                                        jQuery('#mainwp_diagnostics_child_resp').html('<span style="color:red">' + __('Undefined Error') + '</span>').show();
                                                }
                                        }
                                }, false, false, true);
                        },
                        whois: function (val) {
                                val = val.replace(' ', '');
                                if (!/\w+/.test(val)) {
                                        this.colorboxModal('300px', "Enter a valid IP or domain", "Please enter a valid IP address or domain name for your whois lookup.");
                                        return;
                                }
                                var self = this;
                                jQuery('#whoisbutton').attr('disabled', 'disabled');
                                jQuery('#whoisbutton').attr('value', 'Loading...');
                                this.ajax('mainwp_wfc_whois', {
                                        val: val
                                }, function (res) {
                                        jQuery('#whoisbutton').removeAttr('disabled');
                                        jQuery('#whoisbutton').attr('value', 'Look up IP or Domain');
                                        if (res.ok) {
                                                self.completeWhois(res, false);
                                        }
                                });
                        },
                        colorboxModal: function (width, heading, body, settings) {
                                if (typeof settings === 'undefined') {
                                        settings = {};
                                }

                                var prompt = $.tmpl(mainwp_WordfenceAdminVars.modalTemplate, { title: heading, message: body });
                                var promptHTML = $("<div />").append(prompt).html();
                                var callback = settings.onComplete;
                                settings.overlayClose = false;
                                settings.closeButton = false;
                                settings.className = 'wf-modal';
                                settings.onComplete = function () {
                                        $('#wf-generic-modal-close').on('click', function (e) {
                                                e.preventDefault();
                                                e.stopPropagation();

                                                MWP_WFAD.colorboxClose();
                                        });

                                        typeof callback === 'function' && callback();
                                };
                                this.colorboxHTML(width, promptHTML, settings)
                        },
                        colorboxModalHTML: function (width, heading, body, settings) {
                                if (typeof settings === 'undefined') {
                                        settings = {};
                                }

                                var prompt = $.tmpl(mainwp_WordfenceAdminVars.modalHTMLTemplate, { title: heading, message: body });
                                var promptHTML = $("<div />").append(prompt).html();
                                var callback = settings.onComplete;
                                settings.overlayClose = false;
                                settings.closeButton = false;
                                settings.className = 'wf-modal';
                                settings.onComplete = function () {
                                        $('#wf-generic-modal-close').on('click', function (e) {
                                                e.preventDefault();
                                                e.stopPropagation();

                                                MWP_WFAD.colorboxClose();
                                        });

                                        typeof callback === 'function' && callback();
                                };
                                this.colorboxHTML(width, promptHTML, settings)
                        },
                        colorboxHTML: function (width, html, settings) {
                                if (typeof settings === 'undefined') {
                                        settings = {};
                                }
                                this.colorboxQueue.push([width, html, settings]);
                                this.colorboxServiceQueue();
                        },
                        colorboxServiceQueue: function () {
                                if (this.colorboxIsOpen) {
                                        return;
                                }
                                if (this.colorboxQueue.length < 1) {
                                        return;
                                }
                                var elem = this.colorboxQueue.shift();
                                this.colorboxOpen(elem[0], elem[1], elem[2]);
                        },
                        colorboxOpen: function (width, html, settings) {
                                var self = this;
                                this.colorboxIsOpen = true;
                                jQuery.extend(settings, {
                                        width: width,
                                        html: html,
                                        onClosed: function () {
                                                self.colorboxClose();
                                        }
                                });
                                jQuery.wfcolorbox(settings);
                        },
                        colorboxClose: function () {
                                this.colorboxIsOpen = false;
                                jQuery.wfcolorbox.close();
                        },
                        completeWhois: function (res, ret, site_id) {
                                ret = ret === undefined ? false : !!ret;
                                var self = this;
                                var rawhtml = "";
                                var ipRangeTmpl = jQuery("<div><div class='wf-flex-row'>" +
                                        "<a class=\"wf-btn wf-btn-default wf-flex-row-0\" href=\"${adminUrl}\">Block This Network</a>" +
                                        "<span class='wf-flex-row-1 wf-padding-add-left'>{{html totalStr}}{{if totalStr.indexOf(ipRange) == -1}} (${ipRange}){{/if}}" +
                                        '{{if (totalIPs)}}<br>[${totalIPs} addresses in this network]{{/if}}' +
                                        "</span></div></div>");
                                if (res.ok && res.result && res.result.rawdata && res.result.rawdata.length > 0) {
                                        for (var i = 0; i < res.result.rawdata.length; i++) {
                                                res.result.rawdata[i] = jQuery('<div />').text(res.result.rawdata[i]).html();
                                                res.result.rawdata[i] = res.result.rawdata[i].replace(/([a-zA-Z0-9\-._+]+@[a-zA-Z0-9\-._]+)/, "<a href=\"mailto:$1\">$1<\/a>");
                                                res.result.rawdata[i] = res.result.rawdata[i].replace(/(https?:\/\/[a-zA-Z0-9\-._+\/?&=#%:@;]+)/, "<a target=\"_blank\" rel=\"noopener noreferrer\" href=\"$1\">$1<\/a>");

                                                function wfm21(str, startStr, ipRange, offset, totalStr) {
                                                        var ips = ipRange.split(/\s*\-\s*/);
                                                        var totalIPs = NaN;
                                                        if (ips[0].indexOf(':') < 0) {
                                                                var ip1num = self.inet_aton(ips[0]);
                                                                var ip2num = self.inet_aton(ips[1]);
                                                                totalIPs = ip2num - ip1num + 1;
                                                        }
                                                        //var adminUrl = "admin.php?page=WordfenceWAF&wfBlockRange=" + encodeURIComponent(ipRange) + "#top#blocking";
                                                        var adminUrl = 'admin.php?page=ManageSitesWordfence&tab=blocking&id=' + site_id + '&blockRange=' + encodeURIComponent(ipRange);
                                                        return jQuery(ipRangeTmpl).tmpl({
                                                                adminUrl: adminUrl,
                                                                totalStr: totalStr,
                                                                ipRange: ipRange,
                                                                totalIPs: totalIPs
                                                        }).wrapAll('<div>').parent().html();
                                                }

                                                function buildRangeLink2(str, startStr, octet1, octet2, octet3, octet4, cidrRange, offset, totalStr) {

                                                        octet3 = octet3.length > 0 ? octet3 : '0';
                                                        octet4 = octet4.length > 0 ? octet4 : '0';

                                                        var rangeStart = [octet1, octet2, octet3, octet4].join('.');
                                                        var rangeStartNum = self.inet_aton(rangeStart);
                                                        cidrRange = parseInt(cidrRange, 10);
                                                        if (!isNaN(rangeStartNum) && cidrRange > 0 && cidrRange < 32) {
                                                                var rangeEndNum = rangeStartNum;
                                                                for (var i = 32, j = 1; i >= cidrRange; i--, j *= 2) {
                                                                        rangeEndNum |= j;
                                                                }
                                                                rangeEndNum = rangeEndNum >>> 0;
                                                                var ipRange = self.inet_ntoa(rangeStartNum) + ' - ' + self.inet_ntoa(rangeEndNum);
                                                                var totalIPs = rangeEndNum - rangeStartNum + 1;
                                                                //var adminUrl = "admin.php?page=WordfenceWAF&wfBlockRange=" + encodeURIComponent(ipRange) + "#top#blocking";
                                                                var adminUrl = 'admin.php?page=ManageSitesWordfence&tab=blocking&id=' + site_id + '&blockRange=' + encodeURIComponent(ipRange);
                                                                return jQuery(ipRangeTmpl).tmpl({
                                                                        adminUrl: adminUrl,
                                                                        totalStr: totalStr,
                                                                        ipRange: ipRange,
                                                                        totalIPs: totalIPs
                                                                }).wrapAll('<div>').parent().html();

                                                        }
                                                        return str;
                                                }

                                                var rangeRegex = /(.*?)(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3} - \d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}|[a-f0-9:.]{3,} - [a-f0-9:.]{3,}).*$/i;
                                                var cidrRegex = /(.*?)(\d{1,3})\.(\d{1,3})\.?(\d{0,3})\.?(\d{0,3})\/(\d{1,3}).*$/i;
                                                if (rangeRegex.test(res.result.rawdata[i])) {
                                                        res.result.rawdata[i] = res.result.rawdata[i].replace(rangeRegex, wfm21);
                                                        rawhtml += res.result.rawdata[i];
                                                } else if (cidrRegex.test(res.result.rawdata[i])) {
                                                        res.result.rawdata[i] = res.result.rawdata[i].replace(cidrRegex, buildRangeLink2);
                                                        rawhtml += res.result.rawdata[i];
                                                } else {
                                                        rawhtml += res.result.rawdata[i] + "<br />";
                                                }
                                        }
                                        rawhtml = rawhtml.replace(/<\/div><br \/>/g, '</div>');
                                        if (ret) {
                                                return rawhtml;
                                        }
                                        jQuery('#wfrawhtml').html(rawhtml);
                                } else {
                                        rawhtml = '<span style="color: #F00;">Sorry, but no data for that IP or domain was found.</span>';
                                        if (ret) {
                                                return rawhtml;
                                        }
                                        jQuery('#wfrawhtml').html(rawhtml);
                                }
                        },
                        wafUpdateRules: function (website_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_updateWAFRules', { site_id: website_id, individual: 1 }, function (res) {
                                        self.wafData = res;
                                        if (res.ok) {
                                                if (!res.isPaid) {
                                                        jQuery.wfcolorbox('400px', 'Rules Updated', 'Your rules have been updated successfully. You are ' +
                                                                'currently using the the free version of Wordfence. ' +
                                                                'Upgrade to Wordfence premium to have your rules updated automatically as new threats emerge. ' +
                                                                '<a href="https://www.wordfence.com/wafUpdateRules1/wordfence-signup/">Click here to purchase a premium API key</a>. ' +
                                                                '<em>Note: Your rules will still update every 30 days as a free user.</em>');
                                                } else {
                                                        jQuery.wfcolorbox('400px', 'Rules Updated', 'Your rules have been updated successfully.');
                                                }
                                        } else if (res.error) {
                                                jQuery.wfcolorbox('400px', 'An error occurred', res.error);
                                        } else {
                                                jQuery.wfcolorbox('400px', 'An error occurred', 'An unknown error');
                                        }
                                });
                        },
                        wafUpdateRules_New: function (website_id, onSuccess) {
                                var self = this;
                                this.ajax('mainwp_wfc_updateWAFRules_New', { site_id: website_id, individual: 1 }, function (res) {
                                        if (res.error) {
                                                self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.error);
                                        } else {
                                                self.wafData = res;
                                                self.restoreWAFData.rules = res.rules;
                                                self.restoreWAFData.rulesLastUpdated = res.rulesLastUpdated;
                                                self.wafConfigPageRender();

                                                if (self.wafData['updated']) {
                                                        if (!self.wafData['isPaid']) {
                                                                self.colorboxModalHTML((self.isSmallScreen ? '300px' : '400px'), 'Rules Updated', 'Your rules have been updated successfully. You are ' +
                                                                        'currently using the free version of Wordfence. ' +
                                                                        'Upgrade to Wordfence premium to have your rules updated automatically as new threats emerge. ' +
                                                                        '<a href="https://www.wordfence.com/wafUpdateRules1/wordfence-signup/">Click here to purchase a premium API key</a>. ' +
                                                                        '<em>Note: Your rules will still update every 30 days as a free user.</em>');
                                                        } else {
                                                                self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'Rules Updated', 'Your rules have been updated successfully.');
                                                        }
                                                }
                                                else {
                                                        self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'Rule Update Failed', 'No rules were updated. Please verify you have permissions to write to the /wp-content/wflogs directory.');
                                                }
                                                if (typeof onSuccess === 'function') {
                                                        return onSuccess.apply(this, arguments);
                                                }
                                        }
                                });
                        },
                        bulkWAFUpdateRules: function () {
                                location.href = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=waf_update_rules&bulk_diagnostic_action=1&nonce=' + mainwp_WordfenceAdminVars.nonce;
                        },
                        saveDebuggingConfig: function (site_id, individual) {
                                var qstr = jQuery('#wfDebuggingConfigForm').serialize();
                                qstr += '&site_id=' + site_id;
                                qstr += '&individual=' + individual;

                                var self = this;
                                jQuery('.wfSavedMsg').hide();
                                jQuery('.wfAjax24').show();
                                this.ajax('mainwp_wfc_saveDebuggingConfig', qstr, function (res) {
                                        jQuery('.wfAjax24').hide();
                                        if (res.ok) {
                                                if (individual) {
                                                        self.pulse('.wfSavedMsg');
                                                } else {
                                                        location.href = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=save_debugging_options&bulk_diagnostic_action=1&nonce=' + mainwp_WordfenceAdminVars.nonce;
                                                }
                                        } else {
                                                jQuery.wfcolorbox('400px', 'An error occurred', 'We encountered an error trying to save your changes.');
                                        }
                                });
                        },
                        loadCacheExclusions: function (website_id) {
                                this.ajax('mainwp_wfc_loadCacheExclusions', { site_id: website_id }, function (res) {
                                        if (res.ex instanceof Array && res.ex.length > 0) {
                                                for (var i = 0; i < res.ex.length; i++) {
                                                        var args = res.ex[i];
                                                        args.site_id = website_id;
                                                        var newElem = jQuery('#mwp_wfCacheExclusionTmpl').tmpl(args);
                                                        newElem.prependTo('#wfCacheExclusions').fadeIn();
                                                }
                                                jQuery('<h2>Cache Exclusions</h2>').prependTo('#wfCacheExclusions');
                                        } else {
                                                jQuery('<h2>Cache Exclusions</h2><p style="width: 500px;">There are not currently any exclusions. If you have a site that does not change often, it is perfectly normal to not have any pages you want to exclude from the cache.</p>').prependTo('#wfCacheExclusions');
                                        }

                                });
                        },
                        addCacheExclusion: function (website_id, patternType, pattern) {
                                if (/^https?:\/\//.test(pattern)) {
                                        jQuery.wfcolorbox('400px', "Incorrect pattern for exclusion", "You can not enter full URL's for exclusion from caching. You entered a full URL that started with http:// or https://. You must enter relative URL's e.g. /exclude/this/page/. You can also enter text that might be contained in the path part of a URL or at the end of the path part of a URL.");
                                        return;
                                }
                                this.ajax('mainwp_wfc_addCacheExclusion', {
                                        patternType: patternType,
                                        pattern: pattern,
                                        site_id: website_id,
                                        individual: 1,
                                }, function (res) {
                                        if (res.ok) { //Otherwise errorMsg will get caught
                                                window.location.reload(true);
                                        }
                                });
                        },
                        bulkAddCacheExclusion: function (pPatternType, pPattern) {
                                if (/^https?:\/\//.test(pPattern)) {
                                        jQuery.wfcolorbox('400px', "Incorrect pattern for exclusion", "You can not enter full URL's for exclusion from caching. You entered a full URL that started with http:// or https://. You must enter relative URL's e.g. /exclude/this/page/. You can also enter text that might be contained in the path part of a URL or at the end of the path part of a URL.");
                                        return;
                                }
                                this.ajax('mainwp_wfc_bulkAddCacheExclusion', {
                                        patternType: pPatternType,
                                        pattern: pPattern,
                                        individual: 0
                                }, function (res) {
                                        if (res.ok && res.id) {
                                                location.href = "admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=add_cache_exclusion&bulk_perform_action=1" + '&id=' + res.id + '&nonce=' + mainwp_WordfenceAdminVars.nonce;
                                        }
                                }
                                );
                        },
                        removeCacheExclusion: function (id, website_id) {
                                this.ajax('mainwp_wfc_removeCacheExclusion', { id: id, site_id: website_id, individual: 1 }, function (res) {
                                        window.location.reload(true);
                                });
                        },
                        bulkRemoveCacheExclusion: function (id) {
                                this.ajax('mainwp_wfc_bulkRemoveCacheExclusion', { id: id }, function (res) {
                                        if (res.ok) {
                                                location.href = "admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=remove_cache_exclusion&bulk_perform_action=1" + '&id=' + id + '&nonce=' + mainwp_WordfenceAdminVars.nonce;
                                        }
                                });
                        },
                        showFloatingMessage: function (pObj, pMess) {
                                jQuery('.wfcFloatingMessage').remove();
                                jQuery(pObj).after(' <span class="wfcFloatingMessage" style="display:none; color:#006799"><em>' + pMess + '<em><span>');
                                jQuery('.wfcFloatingMessage').fadeIn(500);
                                setTimeout(function () {
                                        jQuery('.wfcFloatingMessage').fadeOut(1000);
                                }, 3000);
                        },
                        makeTimeAgo: function (t) {
                                var months = Math.floor(t / (86400 * 30));
                                var days = Math.floor(t / 86400);
                                var hours = Math.floor(t / 3600);
                                var minutes = Math.floor(t / 60);

                                if (months > 0) {
                                        days -= months * 30;
                                        return this.pluralize(months, 'month', days, 'day');
                                } else if (days > 0) {
                                        hours -= days * 24;
                                        return this.pluralize(days, 'day', hours, 'hour');
                                } else if (hours > 0) {
                                        minutes -= hours * 60;
                                        return this.pluralize(hours, 'hour', minutes, 'min');
                                } else if (minutes > 0) {
                                        //t -= minutes * 60;
                                        return this.pluralize(minutes, 'minute');
                                } else {
                                        return Math.round(t) + " seconds";
                                }
                        },
                        pluralize: function (m1, t1, m2, t2) {
                                if (m1 != 1) {
                                        t1 = t1 + 's';
                                }
                                if (m2 != 1) {
                                        t2 = t2 + 's';
                                }
                                if (m1 && m2) {
                                        return m1 + ' ' + t1 + ' ' + m2 + ' ' + t2;
                                } else {
                                        return m1 + ' ' + t1;
                                }
                        },
                        sev2num: function (str) {
                                if (/wfProbSev1/.test(str)) {
                                        return 1;
                                } else if (/wfProbSev2/.test(str)) {
                                        return 2;
                                } else {
                                        return 0;
                                }
                        },
                        //            displayIssues_Old: function (res, callback, site_id) {
                        //                var self = this;
                        //
                        //                if (res.summary) {
                        //                    try {
                        //                        res.summary['lastScanCompleted'] = res['lastScanCompleted'];
                        //                    } catch (err) {
                        //                        res.summary['lastScanCompleted'] = 'Never';
                        //                    }
                        //                }
                        //
                        //                var parentBox = jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + ']');
                        //                parentBox.find('.wfIssuesContainer').hide();
                        //
                        //                for (issueStatus in res.issuesLists) {
                        //                    var containerID = 'wfIssues_dataTable_' + issueStatus;
                        //                    var tableID = 'wfIssuesTable_' + issueStatus;
                        //                    if (parentBox.find('.' + containerID).length < 1) {
                        //                        //Invalid issue status
                        //                        continue;
                        //                    }
                        //
                        //                    if (res.issuesLists[issueStatus].length < 1) {
                        //                        if (issueStatus == 'new') {
                        //                            if (res.lastScanCompleted == 'ok') {
                        //                                jQuery(parentBox.find('.' + containerID)).html('<p style="font-size: 20px; color: #0A0;">Congratulations! You have no security issues on your site.</p>');
                        //                            } else if (res['lastScanCompleted']) {
                        //                                //jQuery('#' + containerID).html('<p style="font-size: 12px; color: #A00;">The latest scan failed: ' + res.lastScanCompleted + '</p>');
                        //                            } else {
                        //                                jQuery(parentBox.find('.' + containerID)).html();
                        //                            }
                        //
                        //                        } else {
                        //                            jQuery(parentBox.find('.' + containerID)).html('<p>There are currently <strong>no issues</strong> being ignored on this site.</p>');
                        //                        }
                        //                        continue;
                        //                    }
                        //                    jQuery(parentBox.find('.' + containerID)).html('<table cellpadding="0" cellspacing="0" border="0" class="display" id="' + tableID + '"></table>');
                        //
                        //                    jQuery.fn.dataTableExt.oSort['severity-asc'] = function (y, x) {
                        //                        x = MWP_WFAD.sev2num(x);
                        //                        y = MWP_WFAD.sev2num(y);
                        //                        if (x < y) {
                        //                            return 1;
                        //                        }
                        //                        if (x > y) {
                        //                            return -1;
                        //                        }
                        //                        return 0;
                        //                    };
                        //                    jQuery.fn.dataTableExt.oSort['severity-desc'] = function (y, x) {
                        //                        x = MWP_WFAD.sev2num(x);
                        //                        y = MWP_WFAD.sev2num(y);
                        //                        if (x > y) {
                        //                            return 1;
                        //                        }
                        //                        if (x < y) {
                        //                            return -1;
                        //                        }
                        //                        return 0;
                        //                    };
                        //
                        //                    parentBox.find('#' + tableID).dataTable({
                        //                        "bFilter": false,
                        //                        "bInfo": false,
                        //                        "bPaginate": false,
                        //                        "bLengthChange": false,
                        //                        "bAutoWidth": false,
                        //                        "aaData": res.issuesLists[issueStatus],
                        //                        "aoColumns": [
                        //                            {
                        //                                "sTitle": '<div class="th_wrapp">Severity</div>',
                        //                                "sWidth": '128px',
                        //                                "sClass": "center",
                        //                                "sType": 'severity',
                        //                                "fnRender": function (obj) {
                        //                                    var cls = "";
                        //                                    cls = 'wfProbSev' + obj.aData.severity;
                        //                                    return '<span class="' + cls + '"></span>';
                        //                                }
                        //                            },
                        //                            {
                        //                                "sTitle": '<div class="th_wrapp">Issue</div>',
                        //                                "bSortable": false,
                        //                                "sWidth": '400px',
                        //                                "sType": 'html',
                        //                                fnRender: function (obj) {
                        //                                    var issueType = (obj.aData.type == 'knownfile' ? 'file' : obj.aData.type);
                        //                                    var tmplName = 'issueTmpl_' + issueType;
                        //                                    return jQuery(parentBox.find('#' + tmplName).tmpl(obj.aData)).html();
                        //                                }
                        //                            }
                        //                        ]
                        //                    });
                        //                }
                        //                if (callback) {
                        //                    parentBox.find('.wfIssues_' + parentBox.attr('visibleIssuesPanel')).fadeIn(500, function () {
                        //                        callback();
                        //                    });
                        //                } else {
                        //                    parentBox.find('.wfIssues_' + parentBox.attr('visibleIssuesPanel')).fadeIn(500);
                        //                }
                        //                return true;
                        //            },

                        displayIssues: function (res, callback, site_id) {
                                for (var issueStatus in res.issues) {
                                        var containerID = 'wf-scan-results-' + issueStatus;
                                        if ($('#' + containerID).length < 1) {
                                                continue;
                                        }

                                        if (res.issues[issueStatus].length < 1) {
                                                continue;
                                        }

                                        $('#' + containerID).empty();
                                }

                                this.appendIssues(res.issues, callback, site_id);

                                return true;
                        },

                        saveConfig: function () {
                                var qstr = jQuery('#mainwp_wfc_tabs_wrapper input, #mainwp_wfc_tabs_wrapper select, #mainwp_wfc_tabs_wrapper textarea').serialize();
                                var self = this;
                                jQuery('.wfSavedMsg').hide();
                                jQuery('.wfcSaveOpts').show();
                                var _section = jQuery('#_post_saving_section').val();

                                this.ajax('mainwp_wfc_saveConfig', qstr, function (res) {
                                        jQuery('.wfcSaveOpts').hide();
                                        if (res.ok) {
                                                jQuery('.wfSavedMsg').show();
                                                setTimeout(function () {
                                                        jQuery('.wfSavedMsg').fadeOut();
                                                        location.href = "admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_setting&save=settings&wfSavingSection=" + _section;
                                                }, 2000);
                                        } else if (res.errorMsg) {
                                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.errorMsg);
                                                return;
                                        } else {
                                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', 'We encountered an error trying to save your changes.');
                                        }
                                });
                        },
                        ajax: function (action, data, cb, cbErr, noLoading, noPopError) {
                                if (typeof (data) == 'string') {
                                        if (data.length > 0) {
                                                data += '&';
                                        }
                                        data += 'action=' + action + '&nonce=' + this.nonce;
                                } else if (typeof (data) == 'object' && data instanceof Array) {
                                        // jQuery serialized form data
                                        data.push({
                                                name: 'action',
                                                value: action
                                        });
                                        data.push({
                                                name: 'nonce',
                                                value: this.nonce
                                        });
                                } else if (typeof (data) == 'object') {
                                        data['action'] = action;
                                        data['nonce'] = this.nonce;
                                }
                                if (!cbErr) {
                                        cbErr = function () {
                                        };
                                }
                                var self = this;
                                if (!noLoading) {
                                        this.showLoading();
                                }

                                jQuery.ajax({
                                        type: 'POST',
                                        url: mainwp_WordfenceAdminVars.ajaxURL,
                                        dataType: "json",
                                        data: data,
                                        success: function (json) {
                                                if (!noLoading) {
                                                        self.removeLoading();
                                                }

                                                if (json && json.nonce) {
                                                        self.nonce = json.nonce;
                                                }

                                                if (json && json.error) {
                                                        var msg = json.error;
                                                        if (json.error == 'NOMAINWP') {
                                                                msg = 'No MainWP Child plugin detected, first install and activate the plugin and add your site to MainWP afterwards.';
                                                        }

                                                        if (typeof noPopError === 'undefined' || noPopError == 0) {
                                                                jQuery.wfcolorbox('400px', 'An error occurred', msg);
                                                        }
                                                        if (json.error == 'NOMAINWP') {
                                                                json.error = 'Unknown error occurred! If you continue experiencing this issue, please contact <a href="https://mainwp.com/support/" target="_blank">MainWP Support</a>.';
                                                                return;
                                                        }
                                                }

                                                if (json && json.errorMsg) {
                                                        if (typeof noPopError === 'undefined' || noPopError == 0)
                                                                jQuery.wfcolorbox('400px', 'An error occurred', json.errorMsg);
                                                }

                                                cb(json);
                                        },
                                        error: function () {
                                                if (!noLoading) {
                                                        self.removeLoading();
                                                }
                                                cbErr();
                                        }
                                });
                        },
                        colorbox: function (width, heading, body) {
                                this.colorboxQueue.push([width, heading, body]);
                                this.colorboxServiceQueue();
                        },
                        colorboxServiceQueue: function () {
                                if (this.colorboxIsOpen) {
                                        return;
                                }
                                if (this.colorboxQueue.length < 1) {
                                        return;
                                }
                                var elem = this.colorboxQueue.shift();
                                this.colorboxOpen(elem[0], elem[1], elem[2]);
                        },
                        scanRunningMsg: function () {
                                jQuery.wfcolorbox('400px', "A scan is running", "A scan is currently in progress. Please wait until it finishes before starting another scan.");
                        },
                        errorMsg: function (msg) {
                                jQuery.wfcolorbox('400px', "An error occurred:", msg);
                        },
                        bulkOperation: function (op, site_id) {
                                var self = this;
                                var parentBox = jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + ']');
                                if (op == 'del' || op == 'repair') {
                                        var ids = parentBox.find('input.wf' + op + 'Checkbox:checked').map(function () {
                                                return jQuery(this).val();
                                        }).get();
                                        if (ids.length < 1) {
                                                jQuery.wfcolorbox('400px', "No files were selected", "You need to select files to perform a bulk operation. There is a checkbox in each issue that lets you select that file. You can then select a bulk operation and hit the button to perform that bulk operation.");
                                                return;
                                        }
                                        if (op == 'del') {
                                                jQuery.wfcolorbox('400px', "Are you sure you want to delete?", "Are you sure you want to delete a total of " + ids.length + " files? Do not delete files on your system unless you're ABSOLUTELY sure you know what you're doing. If you delete the wrong file it could cause your WordPress website to stop functioning and you will probably have to restore from backups. If you're unsure, Cancel and work with your hosting provider to clean your system of infected files.<br /><br /><input type=\"button\" value=\"Delete Files\" onclick=\"MWP_WFAD.bulkOperationConfirmed('" + op + "'," + site_id + ");\" />&nbsp;&nbsp;<input type=\"button\" value=\"Cancel\" onclick=\"jQuery.colorbox.close();\" /><br />");
                                        } else if (op == 'repair') {
                                                jQuery.wfcolorbox('400px', "Are you sure you want to repair?", "Are you sure you want to repair a total of " + ids.length + " files? Do not repair files on your system unless you're sure you have reviewed the differences between the original file and your version of the file in the files you are repairing. If you repair a file that has been customized for your system by a developer or your hosting provider it may leave your system unusable. If you're unsure, Cancel and work with your hosting provider to clean your system of infected files.<br /><br /><input type=\"button\" value=\"Repair Files\" onclick=\"MWP_WFAD.bulkOperationConfirmed('" + op + "'," + site_id + ");\" />&nbsp;&nbsp;<input type=\"button\" value=\"Cancel\" onclick=\"jQuery.colorbox.close();\" /><br />");
                                        }
                                } else {
                                        return;
                                }
                        },
                        //            bulkOperationConfirmed: function (op, site_id) {
                        //                if (typeof site_id == "undefined")
                        //                    return;
                        //                var parentBox = jQuery('.mwp_wordfence_network_scan_box[site-id=' + site_id + ']');
                        //                jQuery.colorbox.close();
                        //                var self = this;
                        //                this.ajax('mainwp_wfc_bulkOperation', {
                        //                    op: op,
                        //                    site_id: site_id,
                        //                    ids: parentBox.find('input.wf' + op + 'Checkbox:checked').map(function () {
                        //                        return jQuery(this).val();
                        //                    }).get()
                        //                }, function (res) {
                        //                    self.doneBulkOperation(res, site_id);
                        //                });
                        //            },
                        bulkOperationConfirmed: function (op, site_id) {
                                MWP_WFAD.colorboxClose();
                                this.ajax('mainwp_wfc_bulkOperation', {
                                        op: op,
                                        site_id: site_id
                                }, function (res) {
                                        if (res.ok) {
                                                for (var i = 0; i < res.idsRemoved.length; i++) {
                                                        $('.wf-issue[data-issue-id="' + res.idsRemoved[i] + '"]').remove();
                                                }

                                                MWP_WFAD.updateIssueCounts(res.issueCounts);
                                                MWP_WFAD.repositionSiteCleaningCallout();
                                                setTimeout(function () {
                                                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), res.bulkHeading, res.bulkBody);
                                                }, 500);
                                        }
                                });
                        },
                        deleteFile: function (issueID, site_id) {
                                if (typeof site_id == "undefined")
                                        return;
                                var self = this;
                                this.ajax('mainwp_wfc_deleteFile', {
                                        issueID: issueID,
                                        site_id: site_id
                                }, function (res) {
                                        self.doneDeleteFile(res, site_id);
                                });
                        },
                        doneDeleteFile: function (res, site_id) {
                                var cb = false;
                                var self = this;
                                if (res.ok) {
                                        this.loadIssues(function () {
                                                jQuery.wfcolorbox('400px', "Success deleting file", "The file " + res.file + " was successfully deleted.");
                                        }, site_id);
                                } else if (res.cerrorMsg) {
                                        this.loadIssues(function () {
                                                jQuery.wfcolorbox('400px', 'An error occurred', res.cerrorMsg);
                                        }, site_id);
                                }
                        },
                        deleteDatabaseOption: function (issueID, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_deleteDatabaseOption', {
                                        issueID: issueID,
                                        site_id: site_id
                                }, function (res) {
                                        self.doneDeleteDatabaseOption(res, site_id);
                                });
                        },
                        doneDeleteDatabaseOption: function (res, site_id) {
                                var cb = false;
                                var self = this;
                                if (res.ok) {
                                        this.loadIssues(function () {
                                                jQuery.wfcolorbox('400px', "Success removing option", "The option " + res.option_name + " was successfully removed.");
                                        }, site_id);
                                } else if (res.cerrorMsg) {
                                        this.loadIssues(function () {
                                                jQuery.wfcolorbox('400px', 'An error occurred', res.cerrorMsg);
                                        }, site_id);
                                }
                        },
                        useRecommendedHowGetIPs: function (issueID, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_misconfiguredHowGetIPsChoice', {
                                        issueID: issueID,
                                        choice: 'yes',
                                        site_id: site_id
                                }, function (res) {
                                        if (res.ok) {
                                                jQuery('#wordfenceMisconfiguredHowGetIPsNotice').fadeOut();

                                                self.loadIssues(function () {
                                                        jQuery.wfcolorbox((self.isSmallScreen ? '300px' : '400px'), "Success updating option", "The 'How does Wordfence get IPs' option was successfully updated to the recommended value.");
                                                });
                                        } else if (res.cerrorMsg) {
                                                self.loadIssues(function () {
                                                        jQuery.wfcolorbox((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.cerrorMsg);
                                                });
                                        }
                                });
                        },
                        fixFPD: function (issueID, site_id) {
                                var self = this;
                                var title = "Full Path Disclosure";
                                issueID = parseInt(issueID);
                                var download_link = self.makeDonwnloadHtaccessLink(site_id);
                                this.ajax('mainwp_wfc_checkHtaccess', { site_id: site_id }, function (res) {
                                        if (res.ok) {
                                                self.colorboxModalHTML((self.isSmallScreen ? '300px' : '400px'), title, 'We are about to change your <em>.htaccess</em> file. Please make a backup of this file proceeding'
                                                        + '<br/>'
                                                        + '<a href="' + download_link + '" target="_blank" onclick="jQuery(\'#wfFPDNextBut\').prop(\'disabled\', false); return true;">Click here to download a backup copy of your .htaccess file now</a><br /><br /><input type="button" class="wf-btn wf-btn-default" name="but1" id="wfFPDNextBut" value="Click to fix .htaccess" disabled="disabled" onclick="MWP_WFAD.fixFPD_WriteHtAccess(' + issueID + ',' + site_id + ');" />');
                                        } else if (res.nginx) {
                                                self.colorboxModalHTML((self.isSmallScreen ? '300px' : '400px'), title, 'You are using an Nginx web server and using a FastCGI processor like PHP5-FPM. You will need to manually modify your php.ini to disable <em>display_error</em>');
                                        } else if (res.err) {
                                                self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), "We encountered a problem", "We can't modify your .htaccess file for you because: " + res.err);
                                        }
                                });
                        },
                        fixFPD_WriteHtAccess: function (issueID, site_id) {
                                var self = this;
                                self.colorboxClose();
                                this.ajax('mainwp_wfc_fixFPD', {
                                        issueID: issueID
                                }, function (res) {
                                        if (res.ok) {

                                                self.loadIssues(function () {
                                                        self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), "File restored OK", "The Full Path disclosure issue has been fixed");
                                                }, false, false, site_id);
                                        } else {
                                                self.loadIssues(function () {
                                                        self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.cerrorMsg);
                                                }, false, false, site_id);
                                        }
                                });
                        },
                        _handleHtAccess: function (issueID, callback, title, nginx, site_id) {
                                var self = this;
                                return function (res) {
                                        var download_link = self.makeDonwnloadHtaccessLink(site_id);
                                        if (res.ok) {
                                                jQuery.wfcolorbox("400px", title, 'We are about to change your <em>.htaccess</em> file. Please make a backup of this file proceeding'
                                                        + '<br/>'
                                                        + '<a id="dlButton" href="' + download_link + '" target="_blank">Click here to download a backup copy of your .htaccess file now</a>'
                                                        + '<br /><br /><input type="button" name="but1" id="wfFPDNextBut" value="Click to fix .htaccess" disabled="disabled" />'
                                                );
                                                jQuery('#dlButton').click('click', function () {
                                                        jQuery('#wfFPDNextBut').prop('disabled', false);
                                                });
                                                jQuery('#wfFPDNextBut').one('click', function () {
                                                        self[callback](issueID, site_id);
                                                });
                                        } else if (res.nginx) {
                                                jQuery.wfcolorbox("400px", title, 'You are using an Nginx web server and using a FastCGI processor like PHP5-FPM. ' + nginx);
                                        } else if (res.err) {
                                                jQuery.wfcolorbox('400px', "We encountered a problem", "We can't modify your .htaccess file for you because: " + res.err);
                                        }
                                };
                        },
                        _hideFile: function (issueID, site_id) {
                                var self = this;
                                var title = 'Modifying .htaccess';
                                this.ajax('mainwp_wfc_hideFileHtaccess', {
                                        issueID: issueID,
                                        site_id: site_id
                                }, function (res) {
                                        jQuery.colorbox.close();
                                        self.loadIssues(function () {
                                                if (res.ok) {
                                                        jQuery.wfcolorbox("400px", title, 'Your .htaccess file has been updated successfully.');
                                                } else {
                                                        jQuery.wfcolorbox("400px", title, 'We encountered a problem while trying to update your .htaccess file.');
                                                }
                                        }, site_id);
                                });
                        },
                        //            hideFile: function(issueID, reason, site_id) {
                        //                    var self = this;
                        //                    var title = "Backup your .htaccess file";
                        //                    var nginx = "You will need to manually delete those files";
                        //                    issueID = parseInt(issueID, 10);
                        //
                        //                    this.ajax('mainwp_wfc_checkFalconHtaccess', {site_id: site_id}, this._handleHtAccess(issueID, '_hideFile', title, nginx, site_id));
                        //            },
                        hideFile: function (issueID, callback, site_id) {
                                var self = this;
                                MWP_WFAD.ajax('mainwp_wfc_checkHtaccess', { site_id: site_id }, function (checkRes) {
                                        if (checkRes.ok) {
                                                var download_link = self.makeDonwnloadHtaccessLink(site_id);
                                                MWP_WFAD.colorboxModalHTML((MWP_WFAD.isSmallScreen ? '300px' : '400px'), '.htaccess change', 'We are about to change your <em>.htaccess</em> file. Please make a backup of this file proceeding'
                                                        + '<br/>'
                                                        + '<a id="dlButton" href="' + download_link + '" target="_blank">Click here to download a backup copy of your .htaccess file now</a>'
                                                        + '<br /><br /><input type="button" class="wf-btn wf-btn-default" name="but1" id="wfFPDNextBut" value="Click to fix .htaccess" disabled="disabled" />'
                                                );
                                                $('#dlButton').on('click', function (e) {
                                                        $('#wfFPDNextBut').prop('disabled', false);
                                                });
                                                $('#wfFPDNextBut').on('click', function (e) {
                                                        e.preventDefault();
                                                        e.stopPropagation();

                                                        MWP_WFAD.ajax('mainwp_wfc_hideFileHtaccess', {
                                                                issueID: issueID, site_id: site_id
                                                        }, function (res) {
                                                                MWP_WFAD.colorboxClose();
                                                                typeof callback === 'function' && callback(res);
                                                        });
                                                });
                                        }
                                        else if (checkRes.nginx) {
                                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'Unable to automatically hide file', 'You are using an Nginx web server and using a FastCGI processor like PHP5-FPM. You will need to manually delete or hide those files.');
                                        }
                                        else if (checkRes.err) {
                                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), "We encountered a problem", "We can't modify your .htaccess file for you because: " + res.err);
                                        }
                                });
                        },
                        calcRangeTotal: function () {
                                var range = jQuery('#ipRange').val();
                                if (!range) {
                                        return;
                                }
                                range = range.replace(/ /g, '');
                                if (range && /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s*\-\s*\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(range)) {
                                        var ips = range.split('-');
                                        var total = this.inet_aton(ips[1]) - this.inet_aton(ips[0]) + 1;
                                        if (total < 1) {
                                                jQuery('#wfShowRangeTotal').html("<span style=\"color: #F00;\">Invalid. Starting IP is greater than ending IP.</span>");
                                                return;
                                        }
                                        jQuery('#wfShowRangeTotal').html("<span style=\"color: #0A0;\">Valid: " + total + " addresses in range.</span>");
                                } else {
                                        jQuery('#wfShowRangeTotal').empty();
                                }
                        },
                        restoreFile: function (issueID, site_id) {
                                if (typeof site_id == "undefined")
                                        return;
                                var self = this;
                                this.ajax('mainwp_wfc_restoreFile', {
                                        issueID: issueID,
                                        site_id: site_id
                                }, function (res) {
                                        self.doneRestoreFile(res, site_id);
                                });
                        },
                        doneRestoreFile: function (res, site_id) {
                                var self = this;
                                if (res.ok) {
                                        this.loadIssues(function () {
                                                jQuery.wfcolorbox("400px", "File restored OK", "The file " + res.file + " was restored succesfully.");
                                        }, site_id);
                                } else if (res.cerrorMsg) {
                                        this.loadIssues(function () {
                                                jQuery.wfcolorbox('400px', 'An error occurred', res.cerrorMsg);
                                        }, site_id);
                                }
                        },
                        deleteAdminUser: function (issueID, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_deleteAdminUser', {
                                        issueID: issueID,
                                        site_id: site_id
                                }, function (res) {
                                        if (res.ok) {
                                                self.loadIssues(function () {
                                                        jQuery.wfcolorbox('400px', "Successfully deleted admin", "The admin user " +
                                                                self.htmlEscape(res.user_login) + " was successfully deleted.");
                                                }, site_id);
                                        } else if (res.errorMsg) {
                                                self.loadIssues(function () {
                                                        jQuery.wfcolorbox('400px', 'An error occurred', res.errorMsg);
                                                }, site_id);
                                        }
                                });
                        },

                        revokeAdminUser: function (issueID, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_revokeAdminUser', {
                                        issueID: issueID,
                                        site_id: site_id
                                }, function (res) {
                                        if (res.ok) {
                                                self.loadIssues(function () {
                                                        jQuery.wfcolorbox('400px', "Successfully revoked admin", "All capabilties of admin user " +
                                                                self.htmlEscape(res.user_login) + " were successfully revoked.");
                                                }, site_id);
                                        } else if (res.errorMsg) {
                                                self.loadIssues(function () {
                                                        jQuery.wfcolorbox('400px', 'An error occurred', res.errorMsg);
                                                }, site_id);
                                        }
                                });
                        },
                        disableDirectoryListing: function (issueID, site_id) {
                                var self = this;
                                var title = "Disable Directory Listing";
                                issueID = parseInt(issueID);

                                this.ajax('mainwp_wfc_checkHtaccess', {}, function (res) {
                                        if (res.ok) {
                                                var download_link = self.makeDonwnloadHtaccessLink(site_id);
                                                self.colorboxModalHTML((self.isSmallScreen ? '300px' : '400px'), title, 'We are about to change your <em>.htaccess</em> file. Please make a backup of this file proceeding'
                                                        + '<br/>'
                                                        + '<a href="' + download_link + '" target="_blank" onclick="jQuery(\'#wf-htaccess-confirm\').prop(\'disabled\', false); return true;">Click here to download a backup copy of your .htaccess file now</a>' +
                                                        '<br /><br />' +
                                                        '<button class="wf-btn wf-btn-default" type="button" id="wf-htaccess-confirm" disabled="disabled" onclick="MWP_WFAD.confirmDisableDirectoryListing(' + issueID + ',' + site_id + ');">Add code to .htaccess</button>');
                                        } else if (res.nginx) {
                                                self.colorboxModalHTML((self.isSmallScreen ? '300px' : '400px'), "You are using Nginx as your web server. " +
                                                        "You'll need to disable autoindexing in your nginx.conf. " +
                                                        "See the <a target='_blank'  rel='noopener noreferrer' href='http://nginx.org/en/docs/http/ngx_http_autoindex_module.html'>Nginx docs for more info</a> on how to do this.");
                                        } else if (res.err) {
                                                self.colorboxModal((self.isSmallScreen ? '300px' : '400px'), "We encountered a problem", "We can't modify your .htaccess file for you because: " + res.err);
                                        }
                                });
                        },
                        confirmDisableDirectoryListing: function (issueID, site_id) {
                                var self = this;
                                this.colorboxClose();
                                this.ajax('mainwp_wfc_disableDirectoryListing', {
                                        issueID: issueID,
                                        site_id: site_id
                                }, function (res) {
                                        if (res.ok) {
                                                self.loadIssues(function () {
                                                        jQuery.wfcolorbox("400px", "Directory Listing Disabled", "Directory listing has been disabled on your server.");
                                                }, site_id);
                                        } else {

                                        }
                                });
                        },
                        loadBlockRanges: function (site_id) {
                                var self = this;
                                jQuery('#currentBlocks').html('<i class="fa fa-spinner fa-pulse"></i> Loading ...');
                                this.ajax('mainwp_wfc_loadBlockRanges', { site_id: site_id }, function (res) {
                                        //self.completeLoadBlockRanges(res);
                                });

                        },
                        showErrorMsg: function (errors) {
                                if ('' != errors) {
                                        jQuery('#mwp_wfc_ajax_error_message').show();
                                        jQuery('#mwp_wfc_ajax_error_message .error-message').html(errors);
                                        scrollElementTop('mwp_wfc_ajax_error_message');
                                        return false;
                                }
                        },
                        hideErrorMsg: function () {
                                jQuery('#mwp_wfc_ajax_error_message').fadeOut(1000);
                                jQuery('#mwp_wfc_ajax_error_message .error-message').html("");
                        },
                        blockIPUARange: function (ipRange, hostname, uaRange, referer, reason, site_id) {
                                MWP_WFAD.hideErrorMsg(); // hide.
                                if (!/\w+/.test(reason)) {
                                        MWP_WFAD.showErrorMsg("Please specify a reason. You forgot to include a reason you're blocking this IP range. We ask you to include this for your own record keeping.");
                                        return;
                                }
                                ipRange = ipRange.replace(/ /g, '').toLowerCase();
                                if (ipRange) {
                                        var range = ipRange.split('-'),
                                                validRange;
                                        if (range.length !== 2) {
                                                validRange = false;
                                        } else if (range[0].match(':')) {
                                                validRange = this.inet_pton(range[0]) !== false && this.inet_pton(range[1]) !== false;
                                        } else if (range[0].match('.')) {
                                                validRange = this.inet_aton(range[0]) !== false && this.inet_aton(range[1]) !== false;
                                        }
                                        if (!validRange) {
                                                jQuery.wfcolorbox('300px', 'Specify a valid IP range', "Please specify a valid IP address range in the form of \"1.2.3.4 - 1.2.3.5\" without quotes. Make sure the dash between the IP addresses in a normal dash (a minus sign on your keyboard) and not another character that looks like a dash.");
                                                return;
                                        }
                                }
                                if (hostname && !/^[a-z0-9\.\*\-]+$/i.test(hostname)) {
                                        MWP_WFAD.showErrorMsg('Specify a valid hostname <i>' + this.htmlEscape(hostname) + '</i> is not valid hostname');
                                        return;
                                }
                                if (!(/\w+/.test(ipRange) || /\w+/.test(uaRange) || /\w+/.test(referer) || /\w+/.test(hostname))) {
                                        MWP_WFAD.showErrorMsg('Specify an IP range, Hostname or Browser pattern', "Please specify either an IP address range, Hostname or a web browser pattern to match.");
                                        return;
                                }
                                var data = {
                                        ipRange: ipRange,
                                        hostname: hostname,
                                        uaRange: uaRange,
                                        referer: referer,
                                        reason: reason
                                }

                                if (site_id == 0) {
                                        this.network_BlockIPUARange(data);
                                        return;
                                } else {
                                        data.site_id = site_id;
                                }

                                var self = this;
                                this.ajax('mainwp_wfc_blockIPUARange', data, function (res) {
                                        MWP_WFAD.loadingBlocks = false;
                                        if (res.success) {
                                                $(window).trigger('wordfenceRefreshBlockList', [res, false]);

                                                $('.wf-blocks-table > tbody > tr').removeClass('wf-editing');
                                                $('#wf-block-parameters-title').text($('#wf-block-parameters-title').data('newTitle'));
                                                $('#wf-block-type > li').removeClass('wf-active');
                                                $('.wf-block-add-common, .wf-block-add-ip, .wf-block-add-country, .wf-block-add-pattern').hide();
                                                $('#wf-block-duration, #wf-block-reason, #wf-block-ip, #wf-block-ip-range, #wf-block-hostname, #wf-block-user-agent, #wf-block-referrer').val('');
                                        }
                                        else if (res.error) {
                                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'Error Saving Block', res.error);
                                        } else {
                                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'Error Saving Block', 'Undefined Error');
                                        }
                                });
                        },
                        clearAllBlocked: function (op, site_id) {
                                if (op == 'blocked') {
                                        body = "Are you sure you want to clear all blocked IP addresses and allow visitors from those addresses to access the site again?";
                                } else if (op == 'locked') {
                                        body = "Are you sure you want to clear all locked IP addresses and allow visitors from those addresses to sign in again?";
                                } else {
                                        return;
                                }
                                jQuery.wfcolorbox('450px', "Please confirm", body +
                                        '<br /><br /><center><input type="button" name="but1" value="Cancel" onclick="jQuery.colorbox.close();" />&nbsp;&nbsp;&nbsp;' +
                                        '<input type="button" name="but2" value="Yes I\'m sure" onclick="jQuery.colorbox.close(); MWP_WFAD.confirmClearAllBlocked(\'' + op + '\',' + site_id + ');"><br />');
                        },
                        confirmClearAllBlocked: function (op, site_id) {
                                var data = {
                                        op: op,
                                }

                                if (site_id == 0) {
                                        this.network_confirmClearAllBlocked(data);
                                        return;
                                } else {
                                        data.site_id = site_id;
                                }

                                var self = this;
                                this.ajax('mainwp_wfc_clearAllBlocked', data, function (res) {
                                        self.staticTabChanged(site_id);
                                });
                        },
                        base64_decode: function (s) {
                                var e = {}, i, b = 0, c, x, l = 0, a, r = '', w = String.fromCharCode, L = s.length;
                                var A = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
                                for (i = 0; i < 64; i++) {
                                        e[A.charAt(i)] = i;
                                }
                                for (x = 0; x < L; x++) {
                                        c = e[s.charAt(x)];
                                        b = (b << 6) + c;
                                        l += 6;
                                        while (l >= 8) {
                                                ((a = (b >>> (l -= 8)) & 0xff) || (x < (L - 2))) && (r += w(a));
                                        }
                                }
                                return r;
                        },

                        base64_encode: function (input) {
                                var chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
                                var output = "";
                                var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
                                var i = 0;

                                while (i < input.length) {
                                        chr1 = input.charCodeAt(i++);
                                        chr2 = input.charCodeAt(i++);
                                        chr3 = input.charCodeAt(i++);

                                        enc1 = chr1 >> 2;
                                        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                                        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                                        enc4 = chr3 & 63;

                                        if (isNaN(chr2)) {
                                                enc3 = enc4 = 64;
                                        }
                                        else if (isNaN(chr3)) {
                                                enc4 = 64;
                                        }

                                        output = output + chars.charAt(enc1) + chars.charAt(enc2) + chars.charAt(enc3) + chars.charAt(enc4);
                                }

                                return output;
                        },
                        network_confirmClearAllBlocked: function (data) {
                                var what = '';
                                if (data.op == 'blocked')
                                        what = 'confirmClearAllBlocked';
                                else
                                        what = 'confirmClearAlllocked';
                                this.generalProcess_loadingSites(data, what, jQuery('#mwp_wfc_network_blocking_box'), 'network_confirmClearAllBlocked_Start');
                        },
                        network_confirmClearAllBlocked_Start: function (data) {
                                this.network_confirmClearAllBlocked_StartNext(data);
                        },
                        network_confirmClearAllBlocked_StartNext: function (data) {
                                this.generalProcess_StartNext(data, 'network_confirmClearAllBlocked_StartSpecific');
                        },
                        network_confirmClearAllBlocked_StartSpecific: function (pItem, data) {
                                var self = this;
                                pItem.attr('status', 'processed');
                                var site_id = pItem.attr('site-id');
                                var statusEl = pItem.find('.status').html('<i class="fa fa-spinner fa-pulse"></i> Running ...');
                                if (site_id) {
                                        this.bulkCurrentThreads++;
                                        data.site_id = site_id;
                                        this.ajax('mainwp_wfc_clearAllBlocked', data, function (response) {
                                                if (response) {
                                                        if (response.ok) {
                                                                statusEl.html('Successful').show();
                                                        } else if (response['error']) {
                                                                statusEl.html(response['error']).show();
                                                                statusEl.css('color', 'red');
                                                        } else {
                                                                statusEl.html(__('Undefined Error')).show();
                                                                statusEl.css('color', 'red');
                                                        }
                                                } else {
                                                        statusEl.html(__('Undefined Error')).show();
                                                        statusEl.css('color', 'red');
                                                }

                                                self.bulkCurrentThreads--;
                                                self.bulkFinishedThreads++;

                                                if (self.bulkFinishedThreads == self.bulkTotalThreads && self.bulkFinishedThreads != 0) {
                                                        jQuery('#mwp_wfc_network_blocking_box').append('<div class="mainwp_info-box-yellow">' + __('Finished') + '</div><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_blocking">' + __('Return to Network Blocking') + '</a>');
                                                }
                                                self.network_confirmClearAllBlocked_StartNext(data);
                                        }, true);
                                }
                        },
                        setOwnCountry: function (code) {
                                this.ownCountry = (code + "").toUpperCase();
                        },
                        loadBlockedCountries: function (str) {
                                var codes = str.split(',');
                                for (var i = 0; i < codes.length; i++) {
                                        jQuery('#wfCountryCheckbox_' + codes[i]).addClass('active');
                                }
                        },
                        saveCountryBlocking: function (site_id) {
                                var action = jQuery('#wfBlockAction').val();
                                var redirURL = jQuery('#wfRedirURL').val();
                                var bypassRedirURL = jQuery('#wfBypassRedirURL').val();
                                var bypassRedirDest = jQuery('#wfBypassRedirDest').val();
                                var bypassViewURL = jQuery('#wfBypassViewURL').val();

                                if (action == 'redir' && (!/^https?:\/\/[^\/]+/i.test(redirURL))) {
                                        jQuery.wfcolorbox((this.isSmallScreen ? '300px' : '400px'), "Please enter a URL for redirection", "You have chosen to redirect blocked countries to a specific page. You need to enter a URL in the text box provided that starts with http:// or https://");
                                        return;
                                }
                                if (bypassRedirURL || bypassRedirDest) {
                                        if (!(bypassRedirURL && bypassRedirDest)) {
                                                jQuery.wfcolorbox((this.isSmallScreen ? '300px' : '400px'), "Missing data from form", "If you want to set up a URL that will bypass country blocking, you must enter a URL that a visitor can hit and the destination they will be redirected to. You have only entered one of these components. Please enter both.");
                                                return;
                                        }
                                        if (bypassRedirURL == bypassRedirDest) {
                                                jQuery.wfcolorbox((this.isSmallScreen ? '300px' : '400px'), "URLs are the same", "The URL that a user hits to bypass country blocking and the URL they are redirected to are the same. This would cause a circular redirect. Please fix this.");
                                                return;
                                        }
                                }
                                if (bypassRedirURL && (!/^(?:\/|http:\/\/)/.test(bypassRedirURL))) {
                                        this.invalidCountryURLMsg(bypassRedirURL);
                                        return;
                                }
                                if (bypassRedirDest && (!/^(?:\/|http:\/\/)/.test(bypassRedirDest))) {
                                        this.invalidCountryURLMsg(bypassRedirDest);
                                        return;
                                }
                                if (bypassViewURL && (!/^(?:\/|http:\/\/)/.test(bypassViewURL))) {
                                        this.invalidCountryURLMsg(bypassViewURL);
                                        return;
                                }

                                var codesArr = [];
                                var ownCountryBlocked = false;
                                var self = this;
                                jQuery('.wf-blocked-countries li').each(function (idx, elem) {
                                        if (jQuery(elem).hasClass('active')) {
                                                var code = jQuery(elem).data('country');
                                                codesArr.push(code);
                                                if (code == self.ownCountry) {
                                                        ownCountryBlocked = true;
                                                }
                                        }
                                });
                                this.countryCodesToSave = codesArr.join(',');
                                if (ownCountryBlocked) {
                                        jQuery.wfcolorbox((this.isSmallScreen ? '300px' : '400px'), "Please confirm blocking yourself", "You are about to block your own country. This could lead to you being locked out. Please make sure that your user profile on this machine has a current and valid email address and make sure you know what it is. That way if you are locked out, you can send yourself an unlock email. If you're sure you want to block your own country, click 'Confirm' below, otherwise click 'Cancel'.<br />" +
                                                '<input class="wf-btn wf-btn-default" type="button" name="but1" value="Confirm" onclick="jQuery.colorbox.close(); WFAD.confirmSaveCountryBlocking(' + site_id + ');" />&nbsp;<input class="wf-btn wf-btn-default" type="button" name="but1" value="Cancel" onclick="jQuery.colorbox.close();" />');
                                } else {
                                        this.confirmSaveCountryBlocking(site_id);
                                }
                        },
                        invalidCountryURLMsg: function (URL) {
                                jQuery.wfcolorbox((this.isSmallScreen ? '300px' : '400px'), "Invalid URL", "URL's that you provide for bypassing country blocking must start with '/' or 'http://' without quotes. The URL that is invalid is: " + this.htmlEscape(URL));
                                return;
                        },
                        confirmSaveCountryBlocking: function (site_id) {

                                var action = jQuery('#wfBlockAction').val();
                                var redirURL = jQuery('#wfRedirURL').val();
                                var loggedInBlocked = jQuery('#wfLoggedInBlocked').is(':checked') ? '1' : '0';
                                var loginFormBlocked = jQuery('#wfLoginFormBlocked').is(':checked') ? '1' : '0';
                                var restOfSiteBlocked = jQuery('#wfRestOfSiteBlocked').is(':checked') ? '1' : '0';
                                var bypassRedirURL = jQuery('#wfBypassRedirURL').val();
                                var bypassRedirDest = jQuery('#wfBypassRedirDest').val();
                                var bypassViewURL = jQuery('#wfBypassViewURL').val();

                                var data = {
                                        blockAction: action,
                                        redirURL: redirURL,
                                        loggedInBlocked: loggedInBlocked,
                                        loginFormBlocked: loginFormBlocked,
                                        restOfSiteBlocked: restOfSiteBlocked,
                                        bypassRedirURL: bypassRedirURL,
                                        bypassRedirDest: bypassRedirDest,
                                        bypassViewURL: bypassViewURL,
                                        codes: this.countryCodesToSave
                                };

                                if (site_id == 0) {
                                        this.network_confirmSaveCountryBlocking(data);
                                        return;
                                } else {
                                        data.site_id = site_id;
                                        data.individual = 1;
                                }

                                jQuery('.wfAjax24').show();
                                var self = this;
                                this.ajax('mainwp_wfc_saveCountryBlocking', data, function (res) {
                                        jQuery('.wfAjax24').hide();
                                        self.pulse('.wfSavedMsg');
                                }); // noPopError
                        },

                        network_confirmSaveCountryBlocking: function (data) {
                                this.generalProcess_loadingSites(data, 'confirmSaveCountryBlocking', jQuery('#mwp_wfc_network_blocking_box'), 'network_confirmSaveCountryBlocking_Start');
                        },
                        network_confirmSaveCountryBlocking_Start: function (data) {
                                this.network_confirmSaveCountryBlocking_StartNext(data);
                        },
                        network_confirmSaveCountryBlocking_StartNext: function (data) {
                                this.generalProcess_StartNext(data, 'network_confirmSaveCountryBlocking_StartSpecific');
                        },
                        network_confirmSaveCountryBlocking_StartSpecific: function (pItem, data) {
                                var self = this;
                                pItem.attr('status', 'processed');
                                var site_id = pItem.attr('site-id');
                                var statusEl = pItem.find('.status').html('<i class="fa fa-spinner fa-pulse"></i> Running ...');
                                if (site_id) {
                                        this.bulkCurrentThreads++;
                                        data.site_id = site_id;

                                        if (this.isFirstSaving) {
                                                data.isFirstSaving = 1;
                                        }

                                        this.ajax('mainwp_wfc_saveCountryBlocking', data, function (response) {
                                                if (self.isFirstSaving) {
                                                        self.isFirstSaving = false;
                                                }
                                                if (response) {
                                                        if (response.ok) {
                                                                statusEl.html('Successful').show();
                                                        } else if (response['error']) {
                                                                statusEl.html(response['error']).show();
                                                                statusEl.css('color', 'red');
                                                        } else {
                                                                statusEl.html(__('Undefined Error')).show();
                                                                statusEl.css('color', 'red');
                                                        }
                                                } else {
                                                        statusEl.html(__('Undefined Error')).show();
                                                        statusEl.css('color', 'red');
                                                }

                                                self.bulkCurrentThreads--;
                                                self.bulkFinishedThreads++;

                                                if (self.bulkFinishedThreads == self.bulkTotalThreads && self.bulkFinishedThreads != 0) {
                                                        jQuery('#mwp_wfc_network_blocking_box').append('<div class="mainwp_info-box-yellow">' + __('Finished') + '</div><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_blocking">' + __('Return to Network Blocking') + '</a>');
                                                }
                                                self.network_confirmSaveCountryBlocking_StartNext(data);
                                        }, true, '', 1); // noPopError = 1
                                }
                        },

                        blockIPTwo: function (IP, reason, perm, site_id) {
                                MWP_WFAD.hideErrorMsg(); // hide.
                                if (!/\w+/.test(reason)) {
                                        MWP_WFAD.showErrorMsg("Please specify a reason. You forgot to include a reason you're blocking this IP. We ask you to include this for your own record keeping.");
                                        return;
                                }

                                IP = IP.replace(/ /g, '').toLowerCase();

                                if (IP == '')
                                        return;

                                var data = {
                                        IP: IP,
                                        reason: reason,
                                        perm: (perm ? '1' : '0'),
                                }

                                if (site_id == 0) {
                                        this.network_blockIPTwo(data);
                                        return;
                                } else {
                                        data.site_id = site_id;
                                }

                                var self = this;
                                this.ajax('mainwp_wfc_blockIP', data, function (res) {
                                        if (res.errorMsg) {
                                                return;
                                        } else {
                                                $(window).trigger('wordfenceRefreshBlockList', [res, false]);
                                                //self.staticTabChanged(site_id);
                                        }
                                });
                        },
                        network_blockIPTwo: function (data) {
                                this.generalProcess_loadingSites(data, 'blockIPTwo', jQuery('#mwp_wfc_network_blocking_box'), 'network_blockIPTwo_Start');
                        },
                        network_blockIPTwo_Start: function (data) {
                                this.network_blockIPTwo_StartNext(data);
                        },
                        network_blockIPTwo_StartNext: function (data) {
                                this.generalProcess_StartNext(data, 'network_blockIPTwo_StartSpecific');
                        },
                        network_blockIPTwo_StartSpecific: function (pItem, data) {
                                var self = this;
                                pItem.attr('status', 'processed');
                                var site_id = pItem.attr('site-id');
                                var statusEl = pItem.find('.status').html('<i class="fa fa-spinner fa-pulse"></i> Running ...');
                                if (site_id) {
                                        this.bulkCurrentThreads++;
                                        data.site_id = site_id;
                                        this.ajax('mainwp_wfc_blockIP', data, function (response) {
                                                if (response) {
                                                        if (response.success) {
                                                                statusEl.html('Successful').show();
                                                        } else if (response['error']) {
                                                                statusEl.html(response['error']).show();
                                                                statusEl.css('color', 'red');
                                                        } else {
                                                                statusEl.html(__('Undefined Error')).show();
                                                                statusEl.css('color', 'red');
                                                        }
                                                } else {
                                                        statusEl.html(__('Undefined Error')).show();
                                                        statusEl.css('color', 'red');
                                                }

                                                self.bulkCurrentThreads--;
                                                self.bulkFinishedThreads++;

                                                if (self.bulkFinishedThreads == self.bulkTotalThreads && self.bulkFinishedThreads != 0) {
                                                        jQuery('#mwp_wfc_network_blocking_box').append('<div class="mainwp_info-box-yellow">' + __('Finished') + '</div><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_blocking">' + __('Return to Network Blocking') + '</a>');
                                                }
                                                self.network_blockIPTwo_StartNext(data);
                                        }, true);
                                }
                        },
                        unblockIPTwo: function (IP, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_unblockIP', {
                                        IP: IP,
                                        site_id: site_id
                                }, function (res) {
                                        self.staticTabChanged(site_id);
                                });
                        },
                        permanentlyBlockAllIPs: function (type, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_permanentlyBlockAllIPs', {
                                        type: type,
                                        site_id: site_id
                                }, function (res) {
                                        $('#wfTabs').find('.wfTab1').eq(0).trigger('click');
                                });
                        },
                        unlockOutIP: function (IP, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_unlockOutIP', {
                                        IP: IP,
                                        site_id: site_id
                                }, function (res) {
                                        self.staticTabChanged(site_id);
                                });
                        },
                        permBlockIP: function (IP, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_permBlockIP', {
                                        IP: IP,
                                        site_id: site_id
                                }, function (res) {
                                        self.staticTabChanged(site_id);
                                });
                        },
                        calcRangeTotal: function () {
                                var range = jQuery('#ipRange').val();
                                if (!range) {
                                        return;
                                }
                                range = range.replace(/ /g, '');
                                if (range && /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\s*\-\s*\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(range)) {
                                        var ips = range.split('-');
                                        var total = this.inet_aton(ips[1]) - this.inet_aton(ips[0]) + 1;
                                        if (total < 1) {
                                                jQuery('#wfShowRangeTotal').html("<span style=\"color: #F00;\">Invalid. Starting IP is greater than ending IP.</span>");
                                                return;
                                        }
                                        jQuery('#wfShowRangeTotal').html("<span style=\"color: #0A0;\">Valid: " + total + " addresses in range.</span>");
                                } else {
                                        jQuery('#wfShowRangeTotal').empty();
                                }
                        },
                        unblockNetwork: function (id, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_unblockRange', {
                                        id: id,
                                        site_id: site_id
                                }, function (res) {
                                        self.reloadActivities(site_id);
                                });
                        },
                        inet_aton: function (dot) {
                                var d = dot.split('.');
                                return ((((((+d[0]) * 256) + (+d[1])) * 256) + (+d[2])) * 256) + (+d[3]);
                        },
                        generalProcess_loadingSites: function (pData, pAction, containerEl, callbackName) {
                                var self = this;
                                this.ajax('mainwp_wfc_loadingSites', {
                                        what: pAction
                                }, function (res) {
                                        if (res.html) {
                                                var title = '';
                                                if ('blockIPUARange' == pAction) {
                                                        title = 'Blocking IP Range on child sites ...';
                                                } else if ('blockIPTwo' == pAction) {
                                                        title = 'Blocking IP on child sites ...';
                                                } else if ('confirmClearAllBlocked' == pAction) {
                                                        title = 'Clearing all blocked IP addresses on child sites ...';
                                                } else if ('confirmClearAlllocked' == pAction) {
                                                        title = 'Clearing all locked IP addresses on child sites ...';
                                                } else if ('confirmSaveCountryBlocking' == pAction) {
                                                        title = 'Country Blocking on child sites ...';
                                                }

                                                jQuery('#mainwp-wordfence-sync-modal2').find('.content').html(res.html);
                                                jQuery('#mainwp-wordfence-sync-modal2').find('.header').html(title);

                                                jQuery('#mainwp-wordfence-sync-modal2').modal({
                                                        onHide: function () {
                                                                var _tab = 'network_blocking';
                                                                switch (pAction) {
                                                                        case 'confirmSaveCountryBlocking':
                                                                                _tab = 'network_blocking';
                                                                                break;
                                                                        case 'blockIPTwo':
                                                                                _tab = 'network_blocking';
                                                                                break;
                                                                        case 'blockIPUARange':
                                                                                _tab = 'network_blocking';
                                                                                break;
                                                                        case 'confirmClearAllBlocked':
                                                                                _tab = 'network_scan';
                                                                                break;
                                                                        case 'confirmClearAlllocked':
                                                                                _tab = 'network_traffic';
                                                                                break;
                                                                }
                                                                window.location.replace('admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=' + _tab);
                                                        }
                                                }).modal('show');

                                                self.bulkTotalThreads = jQuery('.siteItemProcess[status="queue"]').length;
                                                self.bulkCurrentThreads = 0;
                                                self.bulkFinishedThreads = 0;
                                                if (self[callbackName]) {
                                                        self[callbackName](pData);
                                                };
                                        }
                                });
                        },
                        generalProcess_StartNext: function (data, callbackName) {
                                var self = this;
                                while ((itemProcess = jQuery('.siteItemProcess[status="queue"]:first')) && (itemProcess.length > 0) && (this.bulkCurrentThreads < this.bulkMaxThreads)) {
                                        itemProcess.removeClass('queue');
                                        if (self[callbackName]) {
                                                self[callbackName](itemProcess, data);
                                        }
                                }
                        },
                        network_BlockIPUARange: function (data) {
                                this.generalProcess_loadingSites(data, 'blockIPUARange', jQuery('#mwp_wfc_network_blocking_box'), 'network_BlockIPUARange_Start');
                        },
                        network_BlockIPUARange_Start: function (data) {
                                this.network_BlockIPUARange_StartNext(data);
                        },
                        network_BlockIPUARange_StartNext: function (data) {
                                this.generalProcess_StartNext(data, 'network_BlockIPUARange_StartSpecific');
                        },
                        network_BlockIPUARange_StartSpecific: function (pItem, data) {
                                var self = this;
                                pItem.attr('status', 'processed');
                                var site_id = pItem.attr('site-id');
                                var statusEl = pItem.find('.status').html('<i class="fa fa-spinner fa-pulse"></i> Running ...');
                                if (site_id) {
                                        this.bulkCurrentThreads++;
                                        data.site_id = site_id;
                                        this.ajax('mainwp_wfc_blockIPUARange', data, function (response) {
                                                if (response) {
                                                        if (response.success) {
                                                                statusEl.html('Successful').show();
                                                        } else if (response['error']) {
                                                                statusEl.html(response['error']).show();
                                                                statusEl.css('color', 'red');
                                                        } else {
                                                                statusEl.html(__('Undefined Error')).show();
                                                                statusEl.css('color', 'red');
                                                        }
                                                } else {
                                                        statusEl.html(__('Undefined Error')).show();
                                                        statusEl.css('color', 'red');
                                                }

                                                self.bulkCurrentThreads--;
                                                self.bulkFinishedThreads++;

                                                if (self.bulkFinishedThreads == self.bulkTotalThreads && self.bulkFinishedThreads != 0) {
                                                        jQuery('#mwp_wfc_network_blocking_box').append('<div class="mainwp_info-box-yellow">' + __('Finished') + '</div><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_blocking">' + __('Return to Blocking') + '</a>');
                                                }
                                                self.network_BlockIPUARange_StartNext(data);
                                        }, true);
                                }
                        },
                        wafConfigPageRender: function () {
                                this.wafData.ruleCount = 0;
                                if (this.wafData.rules) {
                                        this.wafData.ruleCount = Object.keys(this.wafData.rules).length;
                                }

                                var whitelistedIPsEl = $('#waf-whitelisted-urls-tmpl').tmpl(this.wafData);
                                $('#waf-whitelisted-urls-wrapper').html(whitelistedIPsEl);

                                var rulesEl = $('#waf-rules-tmpl').tmpl(this.wafData);
                                $('#waf-rules-wrapper').html(rulesEl);

                                $('#waf-show-all-rules-button').on('click', function (e) {
                                        e.preventDefault();
                                        e.stopPropagation();

                                        $('#waf-rules-wrapper').addClass('wf-show-all');
                                });

                                if (this.wafData['rulesLastUpdated']) {
                                        var date = new Date(this.wafData['rulesLastUpdated'] * 1000);
                                        this.renderWAFRulesLastUpdated(date);
                                }
                                var monitorRequests = $('#waf-monitor-requests-tmpl').tmpl(this.watcherOptions);
                                $('#waf-monitor-requests-wrapper').html(monitorRequests);

                                $(window).trigger('wordfenceWAFConfigPageRender');
                        },
                        renderWAFRulesLastUpdated: function (date) {
                                var dateString = date.toString();
                                if (date.toLocaleString) {
                                        dateString = date.toLocaleString();
                                }
                                $('#waf-rules-last-updated').text('Last Updated: ' + dateString)
                                        .css({
                                                'opacity': 0
                                        })
                                        .animate({
                                                'opacity': 1
                                        }, 500);
                        },

                        renderWAFRulesNextUpdate: function (date) {
                                var dateString = date.toString();
                                if (date.toLocaleString) {
                                        dateString = date.toLocaleString();
                                }
                                $('#waf-rules-next-update').text('Next Update Check: ' + dateString)
                                        .css({
                                                'opacity': 0
                                        })
                                        .animate({
                                                'opacity': 1
                                        }, 500);
                        },
                        wafConfigSave: function (action, data, onSuccess, showColorBox, site_id) {

                                var general = false;
                                if (action == 'general_config') {
                                        general = true;
                                        action = 'config';
                                }

                                showColorBox = showColorBox === undefined ? true : !!showColorBox;
                                var self = this;
                                if (typeof (data) == 'string') {
                                        if (data.length > 0) {
                                                data += '&';
                                        }
                                        data += 'wafConfigAction=' + action;
                                        data += '&site_id=' + site_id;

                                        if (general) {
                                                data += '&general=1';
                                        }
                                } else if (typeof (data) == 'object' && data instanceof Array) {
                                        // jQuery serialized form data
                                        data.push({
                                                name: 'wafConfigAction',
                                                value: action
                                        });
                                        data.push({
                                                name: 'site_id',
                                                value: site_id
                                        });
                                        if (general) {
                                                data.push({
                                                        name: 'general',
                                                        value: 1
                                                });
                                        }
                                } else if (typeof (data) == 'object') {
                                        data['wafConfigAction'] = action;
                                        data['site_id'] = site_id;
                                        if (general) {
                                                data['general'] = 1;
                                        }

                                }

                                this.ajax('mainwp_wfc_saveWAFConfig', data, function (res) {
                                        if (general) {
                                                if (res.ok) {
                                                        jQuery('.wfSavedMsg').show();
                                                        setTimeout(function () {
                                                                jQuery('.wfSavedMsg').fadeOut();
                                                                location.href = "admin.php?page=Extensions-Mainwp-Wordfence-Extension&tab=network_firewall&save=firewall";
                                                        }, 2000);
                                                } else if (res.errorMsg) {
                                                        return;
                                                } else {
                                                        jQuery.wfcolorbox('400px', 'An error occurred', 'We encountered an error trying to save your changes.');
                                                }
                                                return;
                                        }

                                        if (typeof res === 'object' && res.success) {
                                                if (showColorBox) {
                                                        jQuery.wfcolorbox('400px', 'Firewall Configuration', 'The Wordfence Web Application Firewall ' +
                                                                'configuration was saved successfully.');
                                                }
                                                self.wafData = res.data;
                                                self.wafConfigPageRender();
                                                if (typeof onSuccess === 'function') {
                                                        return onSuccess.apply(this, arguments);
                                                }
                                        } else {
                                                jQuery.wfcolorbox('400px', 'Error saving Firewall configuration', 'There was an error saving the ' +
                                                        'Web Application Firewall configuration settings.');
                                        }
                                });
                        },
                        updateConfig: function (key, val, cb, site_id) {
                                this.ajax('mainwp_wfc_updateConfig', { key: key, val: val, site_id: site_id }, function () {
                                        cb();
                                });
                        },
                        deleteIssue: function (id, site_id) {
                                if (typeof site_id == "undefined")
                                        return;
                                var self = this;
                                this.ajax('mainwp_wfc_deleteIssue', {
                                        id: id,
                                        site_id: site_id
                                }, function (res) {
                                        self.loadIssues(false, site_id);
                                });
                        },
                        //            updateIssueStatus: function (id, st, site_id) {
                        //                if (site_id == "undefined")
                        //                    return;
                        //
                        //                var self = this;
                        //                this.ajax('mainwp_wfc_updateIssueStatus', {id: id, 'status': st, site_id: site_id}, function (res) {
                        //                    if (res.ok) {
                        //                        self.loadIssues(false, site_id);
                        //                    }
                        //                });
                        //            },
                        updateIssueStatus: function (id, st, callback, site_id) {
                                this.ajax('mainwp_wfc_updateIssueStatus', { id: id, 'status': st, site_id: site_id }, function (res) {
                                        typeof callback === 'function' && callback(res);
                                });
                        },
                        updateAllIssues: function (op, site_id) { // deleteIgnored, deleteNew, ignoreAllNew
                                if (site_id == "undefined")
                                        return;
                                var head = "Please confirm";
                                if (op == 'deleteIgnored') {
                                        body = "You have chosen to remove all ignored issues. Once these issues are removed they will be re-scanned by Wordfence and if they have not been fixed, they will appear in the 'new issues' list. Are you sure you want to do this?";
                                } else if (op == 'deleteNew') {
                                        body = "You have chosen to mark all new issues as fixed. If you have not really fixed these issues, they will reappear in the new issues list on the next scan. If you have not fixed them and want them excluded from scans you should choose to 'ignore' them instead. Are you sure you want to mark all new issues as fixed?";
                                } else if (op == 'ignoreAllNew') {
                                        body = "You have chosen to ignore all new issues. That means they will be excluded from future scans. You should only do this if you're sure all new issues are not a problem. Are you sure you want to ignore all new issues?";
                                } else {
                                        return;
                                }
                                jQuery.wfcolorbox('450px', head, body + '<br /><br /><center><input type="button" name="but1" value="Cancel" onclick="jQuery.colorbox.close();" />&nbsp;&nbsp;&nbsp;<input type="button" name="but2" value="Yes I\'m sure" onclick="jQuery.colorbox.close(); MWP_WFAD.confirmUpdateAllIssues(\'' + op + '\', ' + site_id + ');" /><br />');
                        },
                        confirmUpdateAllIssues: function (op, site_id) {
                                var self = this;
                                this.ajax('mainwp_wfc_updateAllIssues', { op: op, site_id: site_id }, function (res) {
                                        self.loadIssues(false, site_id);
                                });
                        },
                        exportSettings: function (pObj, site_id) {
                                jQuery(pObj).attr("disabled", "true");
                                this.ajax('mainwp_wfc_exportSettings', { site_id: site_id }, function (res) {
                                        jQuery(pObj).removeAttr("disabled");
                                        if (res.ok && res.token) {
                                                jQuery.wfcolorbox('400px', "Export Successful", "We successfully exported your site settings. To import your site settings on another site, copy and paste the token below into the import text box on the destination site. Keep this token secret. It is like a password. If anyone else discovers the token it will allow them to import your settings excluding your API key.<br /><br />Token:<input type=\"text\" size=\"20\" value=\"" + res.token + "\" onclick=\"this.select();\" /><br />");
                                        } else if (res.errorExport) {
                                                jQuery.wfcolorbox('400px', "Error during Export", res.errorExport);
                                        } else {
                                                jQuery.wfcolorbox('400px', "An unknown error occurred", "An unknown error occurred during the export. We received an undefined error from your web server.");
                                        }
                                });
                        },
                        importSettings: function (pObj, token, site_id) {
                                var self = this;
                                if (token.trim() == '')
                                        return false;
                                if (!confirm('Are you sure?'))
                                        return false;
                                jQuery(pObj).attr("disabled", "true");
                                this.ajax('mainwp_wfc_importSettings', { token: token, site_id: site_id }, function (res) {
                                        jQuery(pObj).removeAttr("disabled");
                                        if (res.ok) {
                                                jQuery.wfcolorbox('400px', "Import Successful", "You successfully imported " + res.totalSet + " options. Your import is complete. Please reload this page or click the button below to reload it:<br /><br /><input type=\"button\" value=\"Reload Page\" onclick=\"window.location.reload(true);\" />");
                                        } else if (res.errorImport) {
                                                jQuery.wfcolorbox('400px', "Error during Import", res.errorImport);
                                        } else {
                                                jQuery.wfcolorbox('400px', "Error during Export", "An unknown error occurred during the import");
                                        }
                                });
                        },
                        onBulkImportSettings: function (token) {
                                var self = this;
                                if (token.trim() == '')
                                        return false;
                                if (!confirm('Are you sure?'))
                                        return false;
                                window.location = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=bulk_import' + '&nonce=' + mainwp_WordfenceAdminVars.nonce + '&token=' + token;
                        },
                        ucfirst: function (str) {
                                str = "" + str;
                                return str.charAt(0).toUpperCase() + str.slice(1);
                        },
                        makeDiffLink: function (dat, site_id) {
                                var loc = '?_wfsf=diff&nonce=child_temp_nonce' +
                                        '&file=' + encodeURIComponent(this.es(dat['file'])) +
                                        '&cType=' + encodeURIComponent(this.es(dat['cType'])) +
                                        '&cKey=' + encodeURIComponent(this.es(dat['cKey'])) +
                                        '&cName=' + encodeURIComponent(this.es(dat['cName'])) +
                                        '&cVersion=' + encodeURIComponent(this.es(dat['cVersion']));
                                return 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site&websiteid=' + site_id + '&open_location=' + this.utf8_to_b64(loc);
                        },
                        makeViewFileLink: function (file, site_id) {
                                var loc = '?_wfsf=view&nonce=child_temp_nonce&file=' + encodeURIComponent(file);
                                return 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site&websiteid=' + site_id + '&open_location=' + this.utf8_to_b64(loc);
                        },
                        makeDonwnloadHtaccessLink: function (site_id) {
                                var location = 'admin-ajax.php?&action=wordfence_downloadHtaccess&_mwpNoneName=nonce&_mwpNoneValue=wp-ajax';
                                return 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site&websiteid=' + site_id + '&open_location=' + MWP_WFAD.utf8_to_b64(location);
                        },
                        es: function (val) {
                                if (val) {
                                        return val;
                                } else {
                                        return "";
                                }
                        },
                        noQuotes: function (str) {
                                return str.replace(/"/g, '&#34;').replace(/\'/g, '&#145;');
                        },
                        commify: function (num) {
                                return ("" + num).replace(/(\d)(?=(\d\d\d)+(?!\d))/g, "$1,");
                        },
                        showTimestamp: function (timestamp, serverTime, format) {
                                serverTime = serverTime === undefined ? new Date().getTime() / 1000 : serverTime;
                                format = format === undefined ? '${dateTime} (${timeAgo} ago)' : format;
                                var date = new Date(timestamp * 1000);

                                return jQuery.tmpl(format, {
                                        dateTime: date.toLocaleDateString() + ' ' + date.toLocaleTimeString(),
                                        timeAgo: this.makeTimeAgo(serverTime - timestamp)
                                });
                        },
                        updateTimeAgo: function () {
                                var self = this;
                                jQuery('.wfTimeAgo-timestamp').each(function (idx, elem) {
                                        var el = jQuery(elem);
                                        var timestamp = el.data('wfctime');
                                        if (!timestamp) {
                                                timestamp = el.attr('data-timestamp');
                                        }
                                        var serverTime = self.serverMicrotime;
                                        var format = el.data('wfformat');
                                        if (!format) {
                                                format = el.attr('data-format');
                                        }
                                        el.html(self.showTimestamp(timestamp, serverTime, format));
                                });
                        },
                        switchIssuesTab: function (elem, type) {
                                var parentBox = jQuery(elem).closest('.mwp_wordfence_network_scan_box');
                                parentBox.find('.wfTab2').removeClass('selected');
                                parentBox.find('.wfIssuesContainer').hide();
                                jQuery(elem).addClass('selected');
                                parentBox.attr('visibleIssuesPanel', type);
                                parentBox.find('.wfIssues_' + type).fadeIn();
                        },
                };
                window['MWP_WFAD'] = window['mainwp_wordfenceAdmin'];
                setInterval(function () {
                        MWP_WFAD.updateTimeAgo();
                }, 1000);
        }

        jQuery(function () {
                mainwp_wordfenceAdmin.init();
        });

})(jQuery);
