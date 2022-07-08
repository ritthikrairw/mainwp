"use strict";

var mainwp_itsecSettingsPage = {
        events: jQuery( {} ),
	init: function() {
		jQuery( '.itsec-module-settings-container' ).hide();

		mainwp_itsecSettingsPage.bindEvents();
		jQuery( '.itsec-settings-view-toggle .itsec-selected' ).removeClass( 'itsec-selected' ).trigger( 'click' );
		jQuery( '.itsec-settings-toggle' ).trigger( 'change' );

		jQuery(window).on("popstate", function(e, data) {
			if ( null !== e.originalEvent.state && 'string' == typeof e.originalEvent.state.module_type && '' !== e.originalEvent.state.module_type.replace( /[^\w-]/g, '' ) ) {
				jQuery( '#itsec-module-filter-' + e.originalEvent.state.module_type.replace( /[^\w-]/g, '' ) + ' a' ).trigger( 'itsec-popstate' );
			}
		});

		var module_type = mainwp_itsecSettingsPage.getUrlParameter( 'module_type' );
		if ( false === module_type || 0 === jQuery( '#itsec-module-filter-' + module_type.replace( /[^\w-]/g, '' ) ).length ) {
			module_type = 'recommended';
		}
		jQuery( '#itsec-module-filter-' + module_type.replace( /[^\w-]/g, '' ) + ' a' ).trigger( 'click' );

		var module = mainwp_itsecSettingsPage.getUrlParameter( 'module' );
		if ( 'string' === typeof module ) {
			jQuery( '.itsec-module-wrapper[data-tab="' + module.replace( /[^\w-]/g, '' ) + '"] button.itsec-toggle-settings' ).trigger( 'click' );
		}
	},

	bindEvents: function() {
		var $container = jQuery( '#wpcontent' );

		$container.on( 'click', '.itsec-module-filter a', mainwp_itsecSettingsPage.filterView );
		$container.on( 'itsec-popstate', '.itsec-module-filter a', mainwp_itsecSettingsPage.filterView );
		$container.on( 'click', '.itsec-settings-view-toggle a', mainwp_itsecSettingsPage.toggleView );
		$container.on( 'click', '.list .itsec-module-card:not(.itsec-module-pro-upsell) .itsec-module-card-content, .itsec-toggle-settings', mainwp_itsecSettingsPage.toggleSettings );
		$container.on( 'itsec-popstate', '.list .itsec-module-card-content, .itsec-toggle-settings', mainwp_itsecSettingsPage.toggleSettings );
		$container.on( 'click', '.itsec-toggle-activation', mainwp_itsecSettingsPage.toggleModuleActivation );
		$container.on( 'click', '.itsec-module-settings-save', mainwp_itsecSettingsPage.saveSettings );
        $container.on( 'click', '.itsec-reload-module', this.reloadModule );
        $container.on( 'click', '.siteItemProcess input[name=enable_network_brute_force]', this.mainwpEnableNetworkBruteForce );
		$container.on( 'change', '#itsec-filter', mainwp_itsecSettingsPage.logPageChangeFilter );
        // For use by module content to show/hide settings sections based upon an input.
        $container.on( 'change', '.itsec-settings-toggle', mainwp_itsecSettingsPage.toggleModuleContent );
        $container.on( 'change', '#itsec-two-factor-available_methods', mainwp_itsecSettingsPage.toggleAvailableMethods );
    },

	logPageChangeFilter: function( e ) {
		var filter = jQuery( this ).val();
		var url = mainwp_itsec_page.logs_page_url + '&filter=' + filter;
		window.location.href = url;
	},    
	toggleModuleContent: function( e ) {
		if ( 'checkbox' === jQuery(this).attr( 'type' ) ) {
			var show = jQuery(this).prop( 'checked' );
		} else {
			var show = ( jQuery(this).val() ) ? true : false;
		}
        
		var $content = jQuery( '.' + jQuery(this).attr( 'id' ) + '-content' );

		if ( show ) {
			$content.show();
		} else {
			$content.hide();
		}
	},
    toggleAvailableMethods: function( e ) {
        var meth = jQuery( this ).dropdown('get value');
		var $content = jQuery( '.' + jQuery(this).attr( 'id' ) + '-content' );

		if ( meth == 'custom' ) {
			$content.show();			
		} else {
			$content.hide();
		}
	},
 	saveSettings: function( e ) {
		e.preventDefault();

		var $button = jQuery(this);

		if ( $button.hasClass( 'itsec-module-settings-save' ) ) {
			var module = $button.parents( '.itsec-module-wrapper' ).attr( 'data-tab' );
		} else {
			var module = '';
		}

		if ( module == 'database-prefix' ) {
			mainwp_itsecSettingsPage.mainwpChangeDatabasePrefix(module);
			return;
		}

		$button.prop( 'disabled', true );

		var data = {
			'--itsec-form-serialized-data': jQuery( '#itsec-module-settings-form' ).serialize()
		};

		mainwp_itsecSettingsPage.sendAJAXRequest( module, 'save', data, mainwp_itsecSettingsPage.saveSettingsCallback );
	},
        mainwpChangeDatabasePrefix: function( module ) {
            if (jQuery('#itsec-database-prefix-change_prefix').val() == 'no')
                return false;

            var moduleCaller = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]');
            moduleCaller.find( 'button.itsec-module-settings-save' ).prop( 'disabled', true );
            var statusEl = mainwp_itsecSettingsPage.mainwpGetStatusElement( module );
            if ( mainwp_itsec_page.individualSite && mainwp_itsec_page.individualSite > 0) {
                statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Running ...').show();
                mainwp_itsecSettingsPage.mainwpIndividualChangeDatabasePrefix( module );
            } else {
                mainwp_itsecSettingsPage.mainwpLoadSites( module, 'database_prefix' );
            }
	},
        mainwpIndividualChangeDatabasePrefix: function( module ) {
            var moduleCaller = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]');
            var statusEl =  mainwp_itsecSettingsPage.mainwpGetStatusElement( module );
            var method = 'mainwp_itheme_change_database_prefix';
            mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                    statusEl.html('');
                    moduleCaller.find( 'button.itsec-module-settings-save' ).prop( 'disabled', false );
                    var mainwp_response = false;

                    if (response.hasOwnProperty('mainwp_response'))
                        mainwp_response = response.mainwp_response;

                    var message = '';
                    var error = false;

                    if (mainwp_response.message) {
                        message = mainwp_response.message;
                    }

                    if ( mainwp_response ) {
                        if (mainwp_response.error) {
                            error = true;
                            message = mainwp_response.error;
                        } else if (mainwp_response.result == 'success') {
                            if (message == '')
                                message = __( 'Successful' );
                        } else {
                            error = true;
                            message = __( 'Undefined error' );
                        }
                    }
                    else
                    {
                        error = true;
                        message = __( 'Undefined error' );
                    }

                    statusEl.html( message );
                    statusEl.fadeIn();
            } );
        },
        mainwpChangeDatabasePrefixStartSpecific: function(objProcess, module, pWhat) {
                var siteID = objProcess.attr('site-id');
                var method = 'mainwp_itheme_change_database_prefix';
                var loadingEl = objProcess.find('i');
                var statusEl = objProcess.find('.status');
                statusEl.html('');
                loadingEl.show();
                ithemes_bulkCurrentThreads++;
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                        statusEl.html('');
                        loadingEl.hide();
                        var mainwp_response = false;
                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        if (mainwp_response.message) {
                            message = mainwp_response.message;
                        }

                        var error = false;
                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                error = true;
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                if (message == '')
                                    message = __( 'Successful' );
                            } else {
                                error = true;
                                message = __('Undefined error');
                            }
                        }
                        else
                        {
                            error = true;
                            message = __('Undefined error');
                        }

                        statusEl.html( message );

                        ithemes_bulkCurrentThreads--;
                        ithemes_bulkFinishedThreads++;
                        mainwp_itsecSettingsPage.mainwpProcessGeneralStartNext( module, pWhat);
                } , siteID );
        },
        mainwpScanFileChangeStartSpecific: function(objProcess, module, pWhat) {
                var siteID = objProcess.attr('site-id');
                var method = 'mainwp_itheme_scan_file_change';
                var loadingEl = objProcess.find('i');
                var statusEl = objProcess.find('.status');
                statusEl.html('');
                loadingEl.show();
                ithemes_bulkCurrentThreads++;
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                        statusEl.html('');
                        loadingEl.hide();
                        var mainwp_response = false;
                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        if (mainwp_response.message) {
                            message = mainwp_response.message;
                        }

                        var error = false;
                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                error = true;
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                if (message == '')
                                    message = __( 'Successful' );
                            } else {
                                error = true;
                                message = __('Undefined error');
                            }
                        }
                        else
                        {
                            error = true;
                            message = __('Undefined error');
                        }

               
                        statusEl.html( message );

                        ithemes_bulkCurrentThreads--;
                        ithemes_bulkFinishedThreads++;
                        mainwp_itsecSettingsPage.mainwpProcessGeneralStartNext( module, pWhat);
                } , siteID );
        },
        mainwpSecureSiteStartSpecific: function(objProcess, module, pWhat) {
                var siteID = objProcess.attr('site-id');
                var method = 'mainwp_itheme_secure_site';
                var loadingEl = objProcess.find('i');
                var statusEl = objProcess.find('.status');
                statusEl.html('');
                loadingEl.show();
                ithemes_bulkCurrentThreads++;
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                        statusEl.html('');
                        loadingEl.hide();
                        var mainwp_response = false;
                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        if (mainwp_response.message) {
                            message = mainwp_response.message;
                        }

                        var error = false;
                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                error = true;
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                statusEl.after(mainwp_response.response);
                            } else {
                                error = true;
                                message = __('Undefined error');
                            }
                        }
                        else
                        {
                            error = true;
                            message = __('Undefined error');
                        }

                        statusEl.html( message );
                        ithemes_bulkCurrentThreads--;
                        ithemes_bulkFinishedThreads++;
                        mainwp_itsecSettingsPage.mainwpProcessGeneralStartNext( module, pWhat);
                } , siteID );
        },

        mainwpResetApiKeyStartSpecific: function(objProcess, module, pWhat) {
                var siteID = objProcess.attr('site-id');
                var method = 'mainwp_itheme_reset_api_key';
                var loadingEl = objProcess.find('i');
                var statusEl = objProcess.find('.status');
                statusEl.html('');
                loadingEl.show();
                ithemes_bulkCurrentThreads++;
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                        statusEl.html('');
                        loadingEl.hide();
                        var mainwp_response = false;
                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        if (mainwp_response.message) {
                            message = mainwp_response.message;
                        }

                        var error = false;
                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                error = true;
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                if (message == '')
                                    message = __( 'Successful' );
                            } else {
                                error = true;
                                message = __('Undefined error');
                            }
                        }
                        else
                        {
                            error = true;
                            message = __('Undefined error');
                        }

                        statusEl.html( message );
                        ithemes_bulkCurrentThreads--;
                        ithemes_bulkFinishedThreads++;
                        mainwp_itsecSettingsPage.mainwpProcessGeneralStartNext( module, pWhat);
                } , siteID );
        },

        setModuleToActive: function( module ) {
		var args = {
			'module': module,
			'method': 'activate',
			'errors': []
		};

		mainwp_itsecSettingsPage.toggleModuleActivationCallback( args );
	},
        mainwpEnableNetworkBruteForce: function( e ) {
                e.preventDefault();
                var objProcess = jQuery(this).closest('.siteItemProcess');
		var siteID = objProcess.attr('site-id');

                var $button = jQuery( this );
		var $container = jQuery( this ).parents( '.itsec-security-check-container-is-interactive' );
		var inputs = $container.find( ':input' ).serializeArray();
		var data = {};

		for ( var i = 0; i < inputs.length; i++ ) {
			var input = inputs[i];

			if ( '[]' === input.name.substr( -2 ) ) {
				var name = input.name.substr( 0, input.name.length - 2 );

				if ( data[name] ) {
					data[name].push( input.value );
				} else {
					data[name] = [input.value];
				}
			} else {
				data[input.name] = input.value;
			}
		};


		$button
			//.removeClass( 'button-primary' )
			.addClass( 'button-secondary' )
			.prop( 'disabled', true );

		if ( $button.data( 'clicked-value' ) ) {
			$button
				.data( 'original-value', jQuery( this ).val() )
				.attr( 'value', jQuery( this ).data( 'clicked-value' ) )
		}


		mainwp_itsecSettingsPage.sendModuleAJAXRequest( 'security-check', data, function( results ) {
                        $button
				.removeClass( 'button-secondary' )
				//.addClass( 'button-primary' )
				.prop( 'disabled', false );

			if ( $button.data( 'original-value' ) ) {
				$button
					.attr( 'value', jQuery( this ).data( 'original-value' ) )
			}

                        var $feedback = $container.find( '.itsec-security-check-feedback' );
			$feedback.html( '' );

                        var mainwp_response = false;

                        if (results.hasOwnProperty('mainwp_response'))
                            mainwp_response = results.mainwp_response;

                        var error = false;
                        var message = '';

                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                error = true;
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                 message = '<p>' + __( 'Your site is now using Network Brute Force.', 'l10n-mainwp-ithemes-security-extension' ) + '</p>';
                            } else {
                                error = true;
                                message = __( 'Undefined error' );
                            }
                        }
                        else
                        {
                            error = true;
                            message = __( 'Undefined error' );
                        }

                        if (error) {
				$container
					.removeClass( 'itsec-security-check-container-call-to-action' )
					.removeClass( 'itsec-security-check-container-confirmation' )
					.addClass( 'itsec-security-check-container-error' );

					$feedback.append( '<div class="error inline"><p><strong>' + error + '</strong></p></div>' );

			} else {
				$container
					.removeClass( 'itsec-security-check-container-call-to-action' )
					.removeClass( 'itsec-security-check-container-error' )
					.addClass( 'itsec-security-check-container-confirmation' );

				$container.html( message );
				jQuery( '#itsec-notice-network-brute-force' ).hide();
			}

		}, siteID );
	},

	saveSettingsCallback: function( results ) {
		if ( '' === results.module ) {
			jQuery( '#itsec-save' ).prop( 'disabled', false );
		} else {
			jQuery( '.itsec-module-wrapper[data-tab="' + results.module + '"] button.itsec-module-settings-save' ).prop( 'disabled', false );
		}
     
		var $container = jQuery( '.itsec-module-cards-container' );

		if ( $container.hasClass( 'grid' ) ) {
			var view = 'grid';
		} else {
			var view = 'list';
		}

		mainwp_itsecSettingsPage.clearMessages();

		if ( results.errors.length > 0 || ! results.closeModal ) {
			mainwp_itsecSettingsPage.showErrors( results.errors, results.module, 'open' );
			mainwp_itsecSettingsPage.showMessages( results.messages, results.module, 'open' );

			if ( 'grid' === view ) {
				$container.find( '.itsec-module-settings-content-container:visible' ).animate( {'scrollTop': 0}, 'fast' );
			}

			if ( 'list' === view ) {
				jQuery(document).scrollTop( 0 );
			}
		} else {
			mainwp_itsecSettingsPage.showMessages( results.messages, results.module, 'closed' );

			if ( 'grid' === view ) {
				$container.find( '.itsec-module-settings-content-container:visible' ).scrollTop( 0 );
			}
            mainwp_itsecSettingsPage.mainwpSaveModuleSettings(results.module);
		}
	},
        mainwpSaveModuleSettings: function( module ) {
		var moduleCaller =  jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]');
                moduleCaller.find( 'button.itsec-module-settings-save' ).prop( 'disabled', true );
                if ( mainwp_itsec_page.individualSite && mainwp_itsec_page.individualSite > 0) {
                    mainwp_itsecSettingsPage.mainwpIndividualSaveModuleSettings( module );
                } else {
                    mainwp_itsecSettingsPage.mainwpLoadSites( module, 'save_settings' );
                }
	},
        mainwpIndividualSaveModuleSettings: function( module ) {
                var moduleCaller = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]');
                 if ( jQuery( '.itsec-module-cards-container' ).hasClass( 'list' ) ) {
                    statusEl = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"] .mwp_itheme_module_container_list_working_status' );
				}
				var statusEl = mainwp_itsecSettingsPage.mainwpGetStatusElement( module );
                statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Saving Settings to child site ...').show();

                var method = 'mainwp_itheme_save_settings';
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                        statusEl.html('');
                        moduleCaller.find( 'button.itsec-module-settings-save' ).prop( 'disabled', false );
                        var mainwp_response = false;

                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        var error = false;
                        var extra_message = '';

                        if (mainwp_response.message) {
                            message = mainwp_response.message;
                        }

                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                error = true;
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                if (message == '')
                                    message = __( 'Successful' );
                                if (module == 'hide-backend' && mainwp_response.require_permalinks) {
                                    if (jQuery('#itsec-hide-backend-enabled').is(":checked")) {
                                        jQuery('#itsec-hide-backend-enabled').prop('checked', false).trigger( 'change' );
                                    }
                                }
                            } else if (mainwp_response.result == 'CHILD_ADMIN') { // for admin user
                                error = true;
                                message = mainwp_itsec_admin_user_local.child_admin_msg;
                            } else if (mainwp_response.result == 'noupdate') {
                                message = __( 'No change.' );
                            } else {
                                error = true;
                                message = __( 'Undefined error' );
                            }
                            if (mainwp_response.extra_message) {
                                extra_message = mainwp_response.extra_message.join('<br />');
                            }
                        }
                        else
                        {
                            error = true;
                            message = __( 'Undefined error' );
                        }

                        if (extra_message != '') {
                            message += '<br/><span style="color: red">' + extra_message + '</span>';
                        }

                        statusEl.html( message );
                        statusEl.fadeIn();
                } );

        },
        mainwpSaveModuleSettingsStartSpecific: function(objProcess, module, pWhat) {
                var siteID = objProcess.attr('site-id');
                var method = 'mainwp_itheme_save_settings';
                var statusEl = objProcess.find('.status');
                statusEl.html('<i class="notched circle loading icon"></i>');

                ithemes_bulkCurrentThreads++;
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {

                        var mainwp_response = false;
                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        if (mainwp_response.message) {
                            message = mainwp_response.message;
                        }

                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                message = '<i class="red times icon"></i>';
                            } else if (mainwp_response.result == 'success') {
                                if (message == '')
                                    message =  '<i class="green check icon"></i>';
                            } else if ( mainwp_response.result == 'CHILD_ADMIN' ) {
                                message =  '<i class="red times icon"></i>';
                            } else if (response.result == 'noupdate') {
                                message = '<i class="green check icon"></i>';
                            } else {
                                 message = '<i class="red times icon"></i>';
                            }
                        }
                        else
                        {
                            message = '<i class="red times icon"></i>';
                        }

                        statusEl.html( message );

                        ithemes_bulkCurrentThreads--;
                        ithemes_bulkFinishedThreads++;
                        mainwp_itsecSettingsPage.mainwpProcessGeneralStartNext( module, pWhat);
                } , siteID );
        },
	clearMessages: function() {
		jQuery( '#itsec-settings-messages-container, .itsec-module-messages-container' ).empty();
	},

	showErrors: function( errors, module, containerStatus ) {
		jQuery.each( errors, function( index, error ) {
			mainwp_itsecSettingsPage.showError( error, module, containerStatus );
		} );
	},

	showError: function( error, module, containerStatus ) {
		if ( jQuery( '.itsec-module-cards-container' ).hasClass( 'grid' ) ) {
			var view = 'grid';
		} else {
			var view = 'list';
		}

		if ( 'closed' !== containerStatus && 'open' !== containerStatus ) {
			containerStatus = 'closed';
		}

		if ( 'string' !== typeof module ) {
			module = '';
		}


		if ( 'closed' === containerStatus || '' === module ) {
			var container = jQuery( '#itsec-settings-messages-container' );

			if ( '' === module ) {
				container.addClass( 'no-module' );
			}
		} else {
			var container = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"] .itsec-module-messages-container' );
		}

		container.append( '<div class="error"><p><strong>' + error + '</strong></p></div>' ).addClass( 'visible' );
	},

	showMessages: function( messages, module, containerStatus ) {
		jQuery.each( messages, function( index, message ) {
			mainwp_itsecSettingsPage.showMessage( message, module, containerStatus );
		} );
	},

	showMessage: function( message, module, containerStatus ) {
		if ( jQuery( '.itsec-module-cards-container' ).hasClass( 'grid' ) ) {
			var view = 'grid';
		} else {
			var view = 'list';
		}

		if ( 'closed' !== containerStatus && 'open' !== containerStatus ) {
			containerStatus = 'closed';
		}

		if ( 'string' !== typeof module ) {
			module = '';
		}


		if ( 'closed' === containerStatus || '' === module ) {
			var container = jQuery( '#itsec-settings-messages-container' );

			setTimeout( function() {
				container.removeClass( 'visible' );
				setTimeout( function() {
					container.find( 'div' ).remove();
				}, 500 );
			}, 4000 );
		} else {
			var container = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"] .itsec-module-messages-container' );
		}

		container.append( '<div class="updated fade"><p><strong>' + message + '</strong></p></div>' ).addClass( 'visible' );
	},

	filterView: function( e ) {
		e.preventDefault();

		var $activeLink = jQuery(this),
			$oldLink = $activeLink.parents( '.itsec-feature-tabs' ).find( '.current' ),
			type = $activeLink.parent().attr( 'id' ).substr( 20 );

		$oldLink.removeClass( 'current' );
		$activeLink.addClass( 'current' );

		if ( 'all' === type ) {
			jQuery( '.itsec-module-card' ).show();
		} else {
			jQuery( '.itsec-module-type-' + type ).show();
			jQuery( '.itsec-module-card' ).not( '.itsec-module-type-' + type ).hide();
		}

		// We use this to avoid pushing a new state when we're trying to handle a popstate
		if ( 'itsec-popstate' !== e.type ) {
			var url = mainwp_itsec_page.settings_page_url + '&module_type=' + type;
			var module = mainwp_itsecSettingsPage.getUrlParameter( 'module' );
			if ( 'string' === typeof module ) {
				url += '&module=' + module;
			}

			window.history.pushState( {'module_type':type}, type, url );
		}
	},

	toggleView: function( e ) {
		e.preventDefault();

		var $self = jQuery(this);

		if ( $self.hasClass( 'itsec-selected' ) ) {
			// Do nothing if already selected.
			return;
		}

		var $view = $self.attr( 'class' ).replace( 'itsec-', '' );

		$self.addClass( 'itsec-selected' ).siblings().removeClass( 'itsec-selected' );
		jQuery( '.itsec-module-settings-container' ).hide();

		jQuery( '.itsec-toggle-settings' ).each(function( index ) {
			var $button = jQuery( this );

			if ( $button.parents( '.itsec-module-card' ).hasClass( 'itsec-module-type-enabled' ) ) {
				$button.html( mainwp_itsec_page.translations.show_settings );
			} else if ( $button.hasClass( 'information-only' ) ) {
				$button.html( mainwp_itsec_page.translations.information_only );
			} else {
				$button.html( mainwp_itsec_page.translations.show_description );
			}
		});

		var $cardContainer = jQuery( '.itsec-module-cards-container' );
		jQuery.post( ajaxurl, {
			'action':                   'mainwp-itsec-set-user-setting',
			'mainwp-itsec-user-setting-nonce': $self.parent().data( 'nonce' ),
			'setting':                  'mainwp-itsec-settings-view',
			'value':                    $view
		} );

		$cardContainer.fadeOut( 100, function() {
			$cardContainer.removeClass( 'grid list' ).addClass( $view );
		} );
		$cardContainer.fadeIn( 100 );
	},

	toggleSettings: function( e ) {
		e.stopPropagation();
		// We use this to avoid pushing a new state when we're trying to handle a popstate
		if ( 'itsec-popstate' !== e.type ) {
			var module_id = jQuery(this).closest('.itsec-module-card').data( 'module-id' );

			var module_type = mainwp_itsecSettingsPage.getUrlParameter( 'module_type' );
			if ( false === module_type || 0 === jQuery( '#itsec-module-filter-' + module_type.replace( /[^\w-]/g, '' ) ).length ) {
				module_type = 'recommended';
			}

			window.history.pushState( {'module':module_id}, module_id, mainwp_itsec_page.settings_page_url + '&module=' + module_id + '&module_type=' + module_type );
		}
	},
	mainwpGetStatusElement : function( module ) {
		return jQuery( '.itsec-module-wrapper[data-tab="' + module + '"] .mwp_itheme_module_working_status' );
    },

	toggleModuleActivation: function( e ) {
		e.preventDefault();
		e.stopPropagation();

		var $button = jQuery(this),
			$card = $button.parents( '.itsec-module-wrapper' ),
			$buttons = $card.find( '.itsec-toggle-activation' ),
			module = $button.parents( '.itsec-module-wrapper' ).attr( 'data-tab' );

		$buttons.prop( 'disabled', true );

		if ( $button.html() == mainwp_itsec_page.translations.activate ) {
			var method = 'activate';
		} else {
			var method = 'deactivate';
		}

		var statusEl = mainwp_itsecSettingsPage.mainwpGetStatusElement( module );
		statusEl.html('').hide();
		mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, mainwp_itsecSettingsPage.toggleModuleActivationCallback );
	},

	toggleModuleActivationCallback: function( results ) {
		var module = results.module;
		var method = results.method;

        var $card = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]'),
			$buttons = $card.find( '.itsec-toggle-activation' )

		if ( results.errors.length > 0 ) {
			$buttons
				.html( mainwp_itsec_page.translations.error )
				.addClass( 'button-secondary' )
				//.removeClass( 'button-primary' );

			setTimeout( function() {
				mainwp_itsecSettingsPage.isModuleActive( module );
			}, 1000 );

			return;
		}

		if ( 'activate' === method ) {
			$buttons
				.html( mainwp_itsec_page.translations.deactivate )
				.addClass( 'button-secondary' )
				//.removeClass( 'button-primary' )
				.prop( 'disabled', false );

			$card
				.addClass( 'itsec-module-type-enabled' )
				.removeClass( 'itsec-module-type-disabled' );

			var newToggleSettingsLabel = mainwp_itsec_page.translations.show_settings;
		} else {
			$buttons
				.html( mainwp_itsec_page.translations.activate )
				//.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.prop( 'disabled', false );

			$card
				.addClass( 'itsec-module-type-disabled' )
				.removeClass( 'itsec-module-type-enabled' );

			var newToggleSettingsLabel = mainwp_itsec_page.translations.show_description;
		}

		$card.find( '.itsec-toggle-settings' ).html( newToggleSettingsLabel );
        mainwp_itsecSettingsPage.mainwpUpdateModuleStatus(module);
	},
        mainwpUpdateModuleStatus: function( module ) {
		var moduleCaller = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]');
                moduleCaller.find( 'button.itsec-toggle-activation' ).prop( 'disabled', true );
                var statusEl = mainwp_itsecSettingsPage.mainwpGetStatusElement( module );
                if ( mainwp_itsec_page.individualSite && mainwp_itsec_page.individualSite > 0) {
                    statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Updating to child site ...').show();
                    mainwp_itsecSettingsPage.mainwpIndividualUpdateModuleStatus( module );
                } else {
                    mainwp_itsecSettingsPage.mainwpLoadSites( module, 'update_module_status' );
                }
	},
        mainwpIndividualUpdateModuleStatus: function( module ) {
                var moduleCaller =  jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]');
                var statusEl = mainwp_itsecSettingsPage.mainwpGetStatusElement( module );
                var method = 'mainwp_update_module_status';
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                        statusEl.html('');
                        moduleCaller.find( 'button.itsec-toggle-activation' ).prop( 'disabled', false );
                        var mainwp_response = false;

                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        var error = false;
                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                error = true;
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                message = __( 'Successful' );
                            } else {
                                error = true;
                                message = __( 'Undefined error' );
                            }
                        }
                        else
                        {
                            error = true;
                            message = __( 'Undefined error' );
                        }

                        if (error) {
                            
                        } else {
                           
                            setTimeout(function () {
                                statusEl.fadeOut();
                            }, 3000);
                        }
                        statusEl.html( message );
                        statusEl.fadeIn();
                } );

        },
        mainwpLoadSites: function(module, pWhat) {
            var method = 'mainwp_itheme_load_sites';
            var data = {
                what: pWhat
            }
            mainwp_itsecSettingsPage.sendAJAXRequest( module, method, data, function( response ) {
                var mainwp_response = false;               
                if (response.hasOwnProperty('mainwp_response'))
                    mainwp_response = response.mainwp_response;
                if (mainwp_response) {
                    jQuery('#mainwp-ithemes-security-tabs').append(mainwp_response);
                    jQuery( '#mainwp-ithemes-security-sync-modal' ).modal( 'show' );
                    ithemes_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
                    if (ithemes_bulkTotalThreads > 0) {
                        mainwp_itsecSettingsPage.mainwpProcessGeneralStartNext( module , pWhat);
                    }
                } else {
                    jQuery('#mainwp-ithemes-security-tabs').html('<div class="ui red message">' + __("Undefined error occurred. Please reload the page and try again.") + '</div>');
                }
            });
        },
        mainwpProcessGeneralStartNext: function( module, pWhat ) {
                var objProcess;
                while ((objProcess = jQuery('.siteItemProcess[status=queue]:first')) && (objProcess.length > 0) && (ithemes_bulkCurrentThreads < ithemes_bulkMaxThreads))
                {
                    objProcess.attr('status', 'processed');
                    if ( pWhat == 'update_module_status' ) {
                        mainwp_itsecSettingsPage.mainwpUpdateModuleStatusStartSpecific(objProcess, module, pWhat);
                    } else if ( pWhat == 'save_settings' ) {
                        mainwp_itsecSettingsPage.mainwpSaveModuleSettingsStartSpecific(objProcess, module, pWhat);
                    } else if ( pWhat == 'database_prefix' ) {
                        mainwp_itsecSettingsPage.mainwpChangeDatabasePrefixStartSpecific(objProcess, module, pWhat);
                    } else if ( pWhat == 'one_time_check' ) {
                        mainwp_itsecSettingsPage.mainwpScanFileChangeStartSpecific(objProcess, module, pWhat);
                    } else if ( pWhat == 'secure-site' ) {
                        mainwp_itsecSettingsPage.mainwpSecureSiteStartSpecific(objProcess, module, pWhat);
                    } else if ( pWhat == 'reset-api-key' ) {
                        mainwp_itsecSettingsPage.mainwpResetApiKeyStartSpecific(objProcess, module, pWhat);
                    }
                }
                if (ithemes_bulkFinishedThreads > 0 && ithemes_bulkFinishedThreads == ithemes_bulkTotalThreads) {
                    //window.location.reload();
                }
        },
        mainwpUpdateModuleStatusStartSpecific: function(objProcess, module, pWhat) {
                var siteID = objProcess.attr('site-id');
                var method = 'mainwp_update_module_status';
                var statusEl = objProcess.find('.status');
                statusEl.html('<i class="notched circle loading icon"></i>');
                ithemes_bulkCurrentThreads++;
                mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                        var mainwp_response = false;
                        if (response.hasOwnProperty('mainwp_response'))
                            mainwp_response = response.mainwp_response;

                        var message = '';
                        if ( mainwp_response ) {
                            if (mainwp_response.error) {
                                message = mainwp_response.error;
                            } else if (mainwp_response.result == 'success') {
                                message = '<i class="green check icon"></i>';
                            } else {
                                message = '<i class="red times icon"></i>';
                            }
                        }
                        else {
                            message = '<i class="red times icon"></i>';
                        }

                        statusEl.html( message );

                        ithemes_bulkCurrentThreads--;
                        ithemes_bulkFinishedThreads++;
                        mainwp_itsecSettingsPage.mainwpProcessGeneralStartNext( module, pWhat );
                } , siteID );
        },

        isModuleActive: function( module ) {
		mainwp_itsecSettingsPage.sendAJAXRequest( module, 'is_active', {}, mainwp_itsecSettingsPage.isModuleActiveCallback );
	},

	isModuleActiveCallback: function( results ) {
		if ( true === results.response ) {
			results.method = 'activate';
		} else if ( false === results.response ) {
			results.method = 'deactivate';
		} else {
			return;
		}

		mainwp_itsecSettingsPage.toggleModuleActivationCallback( results );
	},

	reloadModule: function( module ) {
                if ( module.preventDefault ) {
			module.preventDefault();

			module = jQuery(this).parents( '.itsec-module-card' ).attr( 'id' ).replace( 'itsec-module-card-', '' );
		}

		var method = 'get_refreshed_module_settings';
		var data = {};

                if (module == 'file-permissions') {
                    mainwp_itsecSettingsPage.mainwpIndividualLoadFilesPermissions(module);
                    return;
                }

		mainwp_itsecSettingsPage.sendAJAXRequest( module, method, data, function( results ) {
			if ( results.success && results.response ) {
				jQuery( '.itsec-module-wrapper[data-tab="' + module + '"] .itsec-module-settings-content-main' ).html( results.response );
			} else if ( results.errors && results.errors.length > 0 ) {
                            mainwp_itsecSettingsPage.showErrors( results.errors, results.module, 'open' );
			}
		} );
	},

         mainwpIndividualLoadFilesPermissions: function( module ) {
            var moduleCaller = jQuery( '.itsec-module-wrapper[data-tab="' + module + '"]');
            moduleCaller.find( 'input.itsec-reload-module' ).prop( 'disabled', true );
            var statusEl = mainwp_itsecSettingsPage.mainwpGetStatusElement( module );
            statusEl.html('<i class="fa fa-spinner fa-pulse"></i> Running ...').show();
            var method = 'mainwp_itheme_load_files_permissions';
            mainwp_itsecSettingsPage.sendAJAXRequest( module, method, {}, function( response ) {
                    statusEl.html('');
                    moduleCaller.find( 'input.itsec-reload-module' ).prop( 'disabled', false );
                    var mainwp_response = false;

                    if (response.hasOwnProperty('mainwp_response'))
                        mainwp_response = response.mainwp_response;

                    var message = '';
                    var error = false;

                    if (mainwp_response.message) {
                        message = mainwp_response.message;
                    }

                    if ( mainwp_response ) {
                        if (mainwp_response.error) {
                            error = true;
                            message = mainwp_response.error;
                        } else if (mainwp_response.html) {
                            jQuery( '.itsec-module-wrapper[data-tab="' + module + '"] .itsec-module-settings-content-main' ).html( mainwp_response.html );
                        } else {
                            error = true;
                            message = __( 'Undefined error' );
                        }
                    }
                    else
                    {
                        error = true;
                        message = __( 'Undefined error' );
                    }

                    statusEl.html( message );
                    statusEl.fadeIn();
            } );
        },

        sendAJAXRequest: function( module, method, data, callback, pSiteID ) {

			var postData = {
				'action': mainwp_itsec_page.ajax_action,
				'nonce':  mainwp_itsec_page.ajax_nonce,
				'module': module,
				'method': method,
				'data':   data,
			};

			if (mainwp_itsec_page.individualSite) {
				postData.individualSite = mainwp_itsec_page.individualSite
				postData.ithemeSiteID = mainwp_itsec_page.ithemeSiteID
			} else if (pSiteID) {
				postData.ithemeSiteID = pSiteID;
			}

			jQuery.post( ajaxurl, postData )
				.always(function( a, status, b ) {
					console.log(status);
					mainwp_itsecSettingsPage.processAjaxResponse( a, status, b, module, method, data, callback );
			});
	},

	processAjaxResponse: function( a, status, b, module, method, data, callback ) {
		var results = {
			'module':        module,
			'method':        method,
			'data':          data,
			'status':        status,
			'jqxhr':         null,
			'success':       false,
			'response':      null,
			'errors':        [],
			'messages':      [],
			'functionCalls': [],
			'redirect':      false,
			'closeModal':    true,
            'mainwp_response': []
		};


		if ( 'MainWP_ITSEC_Response' === a.source && 'undefined' !== a.response ) {
			// Successful response with a valid format.
			results.jqxhr = b;
			results.success = a.success;
			results.response = a.response;
			results.errors = a.errors;
			results.messages = a.messages;
			results.functionCalls = a.functionCalls;
			results.redirect = a.redirect;
			results.closeModal = a.closeModal;
            results.mainwp_response = a.mainwp_response;
		} else if ( a.responseText ) {
			// Failed response.
			results.jqxhr = a;
			var errorThrown = b;

			if ( 'undefined' === typeof results.jqxhr.status ) {
				results.jqxhr.status = -1;
			}

			if ( 'timeout' === status ) {
				var error = mainwp_itsec_page.translations.ajax_timeout;
			} else if ( 'parsererror' === status ) {
				var error = mainwp_itsec_page.translations.ajax_parsererror;
			} else if ( 403 == results.jqxhr.status ) {
				var error = mainwp_itsec_page.translations.ajax_forbidden;
			} else if ( 404 == results.jqxhr.status ) {
				var error = mainwp_itsec_page.translations.ajax_not_found;
			} else if ( 500 == results.jqxhr.status ) {
				var error = mainwp_itsec_page.translations.ajax_server_error;
			} else {
				var error = mainwp_itsec_page.translations.ajax_unknown;
			}

			error = error.replace( '%1$s', status );
			error = error.replace( '%2$s', errorThrown );

			results.errors = [ error ];
		} else {
			// Successful response with an invalid format.
			results.jqxhr = b;

			results.response = a;
			results.errors = [ mainwp_itsec_page.translations.ajax_invalid ];
		}


		if ( results.redirect ) {
			window.location = results.redirect;
		}


		if ( 'function' === typeof callback ) {
			callback( results );
		} else if ( 'function' === typeof console.log ) {
			console.log( 'ERROR: Unable to handle settings AJAX request due to an invalid callback:', callback, {'data': postData, 'results': results} );
		}


		if ( results.functionCalls ) {
			for ( var i = 0; i < results.functionCalls.length; i++ ) {
				if ( 'object' === typeof results.functionCalls[i] && 'string' === typeof results.functionCalls[i][0] && 'function' === typeof mainwp_itsecSettingsPage[results.functionCalls[i][0]] ) {
					mainwp_itsecSettingsPage[results.functionCalls[i][0]]( results.functionCalls[i][1] );
				} else if ( 'string' === typeof results.functionCalls[i] && 'function' === typeof window[results.functionCalls[i]] ) {
					window[results.functionCalls[i]]();
				} else if ( 'object' === typeof results.functionCalls[i] && 'string' === typeof results.functionCalls[i][0] && 'function' === typeof window[results.functionCalls[i][0]] ) {
					window[results.functionCalls[i][0]]( results.functionCalls[i][1] );
				} else if ( 'function' === typeof console.log ) {
					console.log( 'ERROR: Unable to call missing function:', results.functionCalls[i] );
				}
			}
		}
	},

	sendModuleAJAXRequest: function( module, data, callback, pSiteID ) {
		mainwp_itsecSettingsPage.sendAJAXRequest( module, 'handle_module_request', data, callback, pSiteID );
	},

	sendWidgetAJAXRequest: function( widget, data, callback ) {
		mainwp_itsecSettingsPage.sendAJAXRequest( widget, 'handle_widget_request', data, callback );
	},

	getUrlParameter: function( name ) {
		var pageURL = decodeURIComponent( window.location.search.substring( 1 ) ),
			URLParameters = pageURL.split( '&' ),
			parameterName,
			i;

		// Loop through all parameters
		for ( i = 0; i < URLParameters.length; i++ ) {
			parameterName = URLParameters[i].split( '=' );

			// If this is the parameter we're looking for
			if ( parameterName[0] === name ) {
				// Return the value or true if there is no value
				return parameterName[1] === undefined ? true : parameterName[1];
			}
		}
		// If the requested parameter doesn't exist, return false
		return false;
	}
};

jQuery(document).ready(function() {
	mainwp_itsecSettingsPage.init();


	jQuery( '.dialog' ).click( function ( event ) {

		event.preventDefault();

		var target = jQuery( this ).attr( 'href' );
		var title = jQuery( this ).parents( '.inside' ).siblings( 'h3.hndle' ).children( 'span' ).text();

		jQuery( '#' + target ).dialog( {
			                               dialogClass  : 'wp-dialog itsec-dialog itsec-dialog-logs',
			                               modal        : true,
			                               closeOnEscape: true,
			                               title        : title,
			                               height       : ( jQuery( window ).height() * 0.8 ),
			                               width        : ( jQuery( window ).width() * 0.8 ),
			                               open         : function ( event, ui ) {

				                               jQuery( '.ui-widget-overlay' ).bind( 'click', function () {
					                               jQuery( this ).siblings( '.ui-dialog' ).find( '.ui-dialog-content' ).dialog( 'close' );
				                               } );

			                               }

		                               } );

		jQuery( '.ui-dialog :button' ).blur();

	} );
});
