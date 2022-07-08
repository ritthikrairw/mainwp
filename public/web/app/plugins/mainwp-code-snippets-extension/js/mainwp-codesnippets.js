/*
 * MainWp Code Snippets Extension
 */
jQuery( document ).ready(function($) {
	// set the CodeMirror editor
	var atts = {
		lineNumbers: true,
		matchBrackets: true,
		styleActiveLine: true,
		theme: "erlang-dark",
		lineWrapping: true,
		mode: "application/x-httpd-php",
		indentUnit: 4,
		indentWithTabs: true,
		enterMode: "keep",
		tabMode: "shift"
	};
	var mainwp_snp_editor = null;
	if ( jQuery( '#mainwp-code-snippets-code-editor' ).length > 0 ) {
		 mainwp_snp_editor = CodeMirror.fromTextArea( document.getElementById( "mainwp-code-snippets-code-editor" ), atts );
		 mainwp_snp_editor.setSize( "100%", 600 );
	}

	// Close Modal and Reload page
	$( '#mainwp-code-snippets-console-modal .ui.reload.cancel.button' ).on( 'click', function() {
		window.location.reload();
	} );

	// Trigger the code execution
	$( '#mainwp-code-snippetes-execute-snippet-button' ).on( 'click', function( event ) {
		var confirmation = confirm( __( 'Are you sure you want to execute this code snippet?' ) );

            // confirm that you want to execute
            if ( confirmation == false ) {
                return;
            }

            jQuery( '#mainwp-code-snippet-output' ).html( '' );

            var errors = [];

            if ( $.trim( $( '#snp_snippet_title' ).val() ) == '' ) {
                errors.push( __( 'Snippet title is required. Please, enter the title and try again.' ) );
            }

            var code = mainwp_snp_editor.getValue();
            code = code.replace( '|^[\s]*<\?(php)?|', '' );
            code = code.replace( '|\?>[\s]*$|', '' );

            if ( code == '' ) {
                errors.push( __( 'Snippet cannot be empty. Please, enter the snippet and try again.' ) );
            }

            if ( errors.length > 0 ) {
                jQuery( '#mainwp-message-zone' ).html( errors.join( '<br />' ) ).show();
                jQuery( '#mainwp-message-zone' ).addClass( 'yellow' );
                    return false;
            } else {
                jQuery( '#mainwp-message-zone' ).removeClass( 'yellow' );
                jQuery( '#mainwp-message-zone' ).html( '' ).hide();
            }

            var snippet_id = jQuery( '#mainwp_snippet_id_value' ).val();
            var current_type = jQuery( '#mainwp_snippet_type_value' ).val();
            var selected_type = jQuery( 'input[name="snp_snippet_type"]:checked' ).val();

            jQuery( '#mainwp-code-snippets-console-modal' )
            .modal( 'show' );

            if ( snippet_id > 0 && ( current_type === 'S' || selected_type === 'S' || current_type === 'C' || selected_type === 'C') ) {
                var data = {
                    action:'mainwp_snippet_clear_on_site_loading',
                    snippetId: snippet_id
                };

            jQuery( this ).attr( 'disabled','disabled' );

            jQuery.post( ajaxurl, data, function ( response ) {
                jQuery( '#mainwp-code-snippetes-execute-snippet-button' ).removeAttr( 'disabled' );
                if ( current_type === 'S' || current_type === 'C' ) {
                    var progress = __( 'Checking if snippet already exists on child sites. Please wait...' );
                    if ( response !== 'NOSITES' ) {
                        jQuery( '#mainwp-code-snippet-output-log' ).html( progress );
                        jQuery( '#mainwp-code-snippet-output' ).html( response );
                        mainwp_snippet_clear_start();
                    } else {
                        jQuery( '#mainwp-code-snippet-output' ).append( '<div class="ui yellow message"><i class="close icon"></i>' + __( 'No selected sites. Please select wanted child sites first.' ) + '</div>' );
                        mainwp_snippet_save( true, false ); // avoid clear on sites
                    }
                } else { // selected_type = S
                    mainwp_snippet_save( true, false );
                }
            } );
        } else {
            if ( selected_type === 'S' || selected_type === 'C' ) {
                mainwp_snippet_save( true, false ); // update on sites
            } else {
                mainwp_snippet_save( false, false ); // do not update on sites
            }
        }
	} );

	// Trigger save snippet process
	$( '#mainwp-code-snippetes-save-snippet-button' ).on( 'click', function( event ) {
		var errors = [];
		if ( $.trim( $( '#snp_snippet_title' ).val() ) == '' ) {
			errors.push( __( 'Snippet title is required. Please, enter the title and try again.' ) );
		}
		var code = mainwp_snp_editor.getValue();
		code = code.replace( '|^[\s]*<\?(php)?|', '' );
		code = code.replace( '|\?>[\s]*$|', '' );
		if ( code == '' ) {
			errors.push( __( 'Snippet cannot be empty. Please, enter the snippet and try again.' ) );
		}
		if ( errors.length > 0 ) {
			jQuery( '#mainwp-message-zone' ).html( errors.join( '<br />' ) ).show();
			jQuery( '#mainwp-message-zone' ).addClass( 'yellow' );
			return false;
		} else {
			jQuery( '#mainwp-message-zone' ).removeClass( 'yellow' );
			jQuery( '#mainwp-message-zone' ).html( '' ).hide();
			jQuery( '#mainwp-message-zone' ).html( '<i class="notched circle loading icon"></i> ' + __( 'Saving snippet. Please wait...' ) ).show();
		}
		mainwp_snippet_save( false, true ); // do not update on sites
	} );

    // onclick delete snippet button
    $('#mainwp-code-snippetes-delete-snippet-button').on('click', function( event ) {
            var type = jQuery( '#mainwp_snippet_type_value' ).val();
            var sid = jQuery( '#mainwp_snippet_id_value' ).val();
            if ( type === "S" || type === "C" ) {
                jQuery( '#mainwp-code-snippet-delete-snippet-modal' ).modal( 'show' );
                jQuery( 'input[name="delete_snippetid"]' ).val( sid );
            } else if ( type === 'R' ) {
                var confirmation = confirm( __( 'Are you sure you want to delete this code snippet?' ) );
                if ( confirmation == false ) {
                        return;
                }
                var data = {
                    action:'mainwp_snippet_delete_snippet',
                    snippet_id: sid
                };
                jQuery( '#mainwp-message-zone' ).html( '<i class="notched circle loading icon"></i> ' + __( ' Deleting. Please wait...' ) ).show();

                jQuery.post( ajaxurl, data, function( response ) {
                    if ( response && response === 'SUCCESS' ) {
                        jQuery( '#mainwp-message-zone' ).fadeOut();
                        location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
                    } else {
                        jQuery( '#mainwp-message-zone' ).html( '<i class="red times icon"></i> ' + __( 'Snippet could not be deleted. Please, reload the page and try again.' ) );
                    }
                } );
            }
            return false;
    });


	// Trigger the delete snippet process
	$( '.snippet_list_delete_item' ).on( 'click', function() {
		var type = $( this ).attr( 'type' );
		if ( type === "S" || type === "C" ) {
            $( 'input[name="delete_snippetid"]' ).val( $( this ).attr( 'id' ) );
			$( '#mainwp-code-snippet-delete-snippet-modal' ).modal( 'show' );
		} else if ( type === 'R' ) {
            var confirmation = confirm( __( 'Are you sure you want to delete this code snippet?' ) );
            if ( confirmation == false ) {
                    return;
            }
			mainwp_snippet_delete( $( this ) );
		}
		return false;
	} );

	// Delete the R type snippets
	mainwp_snippet_delete = function( pItem ) {
		var parent = pItem.closest( 'tr' );
		var data = {
			action:'mainwp_snippet_delete_snippet',
			snippet_id: pItem.attr( 'id' )
		}

		parent.html( '<td colspan="5"><i class="notched circle loading icon"></i> ' + __( ' Deleting. Please wait...' ) + '</td>' ).show();
		$.post( ajaxurl, data, function( response ) {
			if ( response && response === 'SUCCESS' ) {
				parent.fadeOut();
			} else {
				parent.html( '<i class="red times icon"></i> ' + __( 'Snippet could not be deleted. Please, reload the page and try again.' ) );
			}
		} );
		return false;
	};

	// Delete the S and C type snippets
	$( '#mainwp-code-snippets-delete-snippet-button' ).on( 'click', function() {
		$( this ).attr( 'disabled', 'disabled' );
		var snippetid = $( 'input[name="delete_snippetid"]' ).val();
		var delete_on_site = $( 'input[name="delete_snippet_child_site"]:radio:checked' ).val();
		if ( delete_on_site == 1 ) {
			location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&deleteonsites=1&id=' + snippetid;
			return;
		}

		var data = {
			action:'mainwp_snippet_delete_snippet',
			snippet_id: snippetid
		}

		$( '#mainwp-code-snippet-delete-snippet-modal' ).find( '.content' ).html( '<div class="ui message"><i class="notched circle loading icon"></i> ' + __( 'Deleting. Please wait...' ) + '</div>' );

		$.post( ajaxurl, data, function( response ) {
			$( '#mainwp-code-snippet-delete-snippet-modal' ).find( '.content' ).find( '.ui.message' ).html( '' ).hide();
			if ( response && response === 'SUCCESS' ) {
				$( '#mainwp-code-snippet-delete-snippet-modal' ).find( '.content' ).find( '.ui.message' ).html( __( 'Snippet deleted successfully.' ) ).show();
				$( '#mainwp-code-snippet-delete-snippet-modal' ).find( '.content' ).find( '.ui.message' ).addClass( 'green' );
                setTimeout( function() {
                    location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
                }, 3000 );
			}
		} );
		return false;
	} );

	// Reload page aftre closing modal
//	jQuery( '#mainwp-code-snippet-delete-snippet-modal .cancel' ).on( 'click', function() {
//		window.location = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
//	} );

	// Run snippet
	 mainwp_snippet_run = function() {
		var errors = [];
		var selected_sites = [];
		var selected_groups = [];

		if ( jQuery( '#select_by' ).val() == 'site' ) {
			jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
				selected_sites.push( jQuery( this ).val() );
			} );
		} else {
			jQuery( "input[name='selected_groups[]']:checked" ).each( function (i) {
				selected_groups.push( jQuery( this ).val() );
			} );
		}

		if ( selected_groups.length == 0 && selected_sites.length == 0 ) {
			jQuery( '#mainwp-code-snippet-output-log' ).append( '<div class="ui yellow message">' + __( 'Please select wanted child sites first!' ) + '</div>' );
			return;
		}

		var code = mainwp_snp_editor.getValue();
		code = code.replace( '|^[\s]*<\?(php)?|', '' );
		code = code.replace( '|\?>[\s]*$|', '' );

		if ( code == '' ) {
			errors.push( __( 'Snippet cannot be empty. Please, enter the snippet and try again.' ) );
		}

		if ( errors.length > 0 ) {
			jQuery( '#mainwp-code-snippet-output' ).html( '<div class="ui yellow message">' + errors.join( '<br />' ) + '</div>' );
			return false;
		} else {
			jQuery( '#mainwp-code-snippet-output' ).find( '.ui.yellow.message' ).html( '' ).hide();
		}

		var data = {
			action:'mainwp_snippet_run_snippet_loading',
			'groups[]':selected_groups,
			'sites[]':selected_sites
			};

		jQuery.post(ajaxurl, data, function ( response ) {
			var progress = __( 'Executing the snippet on selected sites. Please wait...' );
			jQuery( '#mainwp-code-snippet-output' ).html( response );
			jQuery( '#mainwp-code-snippet-output-log' ).html( progress );
				   mainwp_snippet_run_start();
		} );
	 }

	mainwp_snippet_run_start = function() {
		mainwp_snippet_init_start();
		mainwp_snippet_run_start_next();
	}

	mainwp_snippet_clear_start = function() {
		mainwp_snippet_init_start();
		mainwp_snippet_clear_sites_start_next();
	}

	mainwp_snippet_update_start = function() {
		mainwp_snippet_init_start();
		mainwp_snippet_update_sites_start_next();
	}

	// Process the snippet
	mainwp_snippet_save = function( doUpdate, saveOnly ) {
		var selected_sites = [];
		var selected_groups = [];
		if ( jQuery( '#select_by' ).val() == 'site' ) {
			jQuery( "input[name='selected_sites[]']:checked" ).each(function (i) {
				selected_sites.push( jQuery( this ).val() );
			} );
		} else {
			jQuery( "input[name='selected_groups[]']:checked" ).each(function (i) {
				selected_groups.push( jQuery( this ).val() );
			} );
		}
		var selected_type = jQuery( 'input[name="snp_snippet_type"]:checked' ).val();
		var snippet_id = jQuery( '#mainwp_snippet_id_value' ).val();
		var data = {
			action:'mainwp_snippet_save_snippet',
			snippet_title: jQuery( '#snp_snippet_title' ).val(),
			code: mainwp_snp_editor.getValue(),
			desc: jQuery( '#snp_snippet_desc' ).val(),
			type: selected_type,
			snippet_id: snippet_id,
			sites: selected_sites,
			groups: selected_groups,
			select_by: jQuery( '#select_by' ).val()
		};

		if ( doUpdate ) {
			jQuery( '#mainwp-code-snippets-console-modal' )
                                .modal( 'show' );
		}

		var progress = __( 'Saving the snippet. Please wait...' );

		jQuery( '#mainwp-code-snippet-output-log' ).html( progress );

		jQuery.post( ajaxurl, data, function ( response ) {
			if ( saveOnly ) {
				var id_param = '';
				if ( response['id'] ) {
					id_param = '&id=' + response['id'];
				} else if (snippet_id) {
					id_param = '&id=' + snippet_id;
				}
				if ( response && response['status'] == 'SUCCESS' ) {
						location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&message=1' + id_param;
					} else {
					location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&message=-1' + id_param;
				}                                
                                return;
			} else if ( selected_type == 'R' ) {
				mainwp_snippet_run();
				return;
			}

			if ( response && response['status'] == 'SUCCESS' ) {
				jQuery( '#mainwp_snippet_id_value' ).val( response['id'] );
				jQuery( '#mainwp_snippet_slug_value' ).val( response['slug'] );
				jQuery( '#mainwp_snippet_type_value' ).val( response['type'] );
				jQuery( '#mainwp_snippet_save_status' ).fadeOut( 3000 );
				jQuery( '#mainwp-code-snippet-output-log' ).html( __( 'Saved successfully!' ) );
				if ( doUpdate && ( selected_type === 'S' || selected_type === 'C' ) ) {
					var data = {
						action:'mainwp_snippet_update_site_loading',
						snippetId: response['id']
					};
					jQuery.post( ajaxurl, data, function ( response ) {
						var progress = __( 'Saving the snippet on selected sites. Please wait...' );
						if ( response !== 'NOSITES' ) {
							jQuery( '#mainwp-code-snippet-output' ).html( response );
							jQuery( '#mainwp-code-snippet-output-log' ).html( progress );
							mainwp_snippet_update_start();
						} else {
							jQuery( '#mainwp-code-snippet-output-log' ).html( __( 'No selected sites to proceed. Process completed.' ) );
						}
					} );

				} else {
					jQuery( '#mainwp-code-snippet-output-log' ).html( __( 'Snippet saved successfully!' ) );
					mainwp_snippet_process_done();
				}
			} else {
				jQuery( '#mainwp-code-snippet-output-log' ).html( __( 'Saving process failed.' ) );
			}
		}, 'json');
	}

	// Loop through sites
	mainwp_snippet_run_start_next = function() {
		if ( snippet_TotalThreads == 0 ) {
			snippet_TotalThreads = jQuery( '.mainwp-snippet-item[status="queue"]' ).length;
		}
		while ( ( siteToRun = jQuery( '.mainwp-snippet-item[status="queue"]:first' ) ) && ( siteToRun.length > 0 )  && ( snippet_CurrentThreads < snippet_MaxThreads ) ) {
			mainwp_snippet_run_start_specific( siteToRun );
		}
	}

	// Loop
	mainwp_snippet_update_sites_start_next = function() {
		if ( snippet_TotalThreads == 0 ) {
			snippet_TotalThreads = jQuery( '.mainwp-update-snippet-item[status="queue"]' ).length;
		}
		while ( ( siteToRun = jQuery( '.mainwp-update-snippet-item[status="queue"]:first' ) ) && ( siteToRun.length > 0 )  && ( snippet_CurrentThreads < snippet_MaxThreads ) ) {
			mainwp_snippet_update_sites_start_specific( siteToRun );
		}
	}

	// Execute on specific
	mainwp_snippet_run_start_specific = function( pSiteToRun ) {
		snippet_CurrentThreads++;
		pSiteToRun.attr( 'status', 'progress' );
		var statusEl = pSiteToRun.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );
		var resultEl = pSiteToRun.find( '.mainwp-snippet-output' );
			var data = {
				action:'mainwp_snippet_run_snippet',
				siteId: pSiteToRun.attr( 'siteid' ),
				code: mainwp_snp_editor.getValue()
		};

		jQuery.post( ajaxurl, data, function ( response ) {
				pSiteToRun.attr( 'status', 'done' );
			if ( ! response || response === 'FAIL' ) {
				statusEl.html( __( 'Undefined error occurred. Please, try again.' ) );
			} else if ( response === 'CODEEMPTY' ) {
				statusEl.html( __( 'Snippet cannot be empty.' ) );
			} else {
				if ( response['error'] ) {
					statusEl.html( response['error'] );
				} else if ( response['status'] === 'SUCCESS') {
					statusEl.html( '<i class="green check icon"></i>' );
				} else if ( response['status'] === 'FAIL' ) {
					statusEl.html( '<i class="red times icon"></i> ' + __( 'Process failed. Please, try again.' ) );
				}
				if ( response['result'] !== '' ) {
					resultEl.html( response['result'] );
				}
			}

				snippet_CurrentThreads--;
				snippet_FinishedThreads++;

			if ( snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0 ) {
				jQuery( '#mainwp-code-snippet-output-log' ).html( __( "Process completed successfully." ) );
			}

				mainwp_snippet_run_start_next();
		}, 'json');
	};

	// Start specific
	mainwp_snippet_update_sites_start_specific = function( pSiteToRun ) {
		snippet_CurrentThreads++;
		pSiteToRun.attr( 'status', 'progress' );
		var statusEl = pSiteToRun.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );
		var type = jQuery( '#mainwp_snippet_type_value' ).val();
		var data = {
			action:'mainwp_snippet_update_site',
			siteId: pSiteToRun.attr( 'siteid' ),
			code: mainwp_snp_editor.getValue(),
			snippetSlug: jQuery( '#mainwp_snippet_slug_value' ).val(),
			type: type
		};

		jQuery.post( ajaxurl, data, function( response ) {
			pSiteToRun.attr( 'status', 'done' );
			if ( ! response || response === 'FAIL' ) {
			statusEl.html( __( 'Undefined error occurred. Please, try again.' ) );
			} else if ( response === 'CODEEMPTY' ) {
				statusEl.html( __( 'Snippet cannot be empty.' ) );
			} else {
				if ( response['error'] ) {
					statusEl.html( response['error'] );
				} else if (response['status'] === 'SUCCESS' ) {
					statusEl.html( '<i class="green check icon"></i>' );
				} else if (response['status'] === 'FAIL') {
					tatusEl.html( '<i class="red times icon"></i> ' + __( 'Process failed. Please, try again.' ) );
				}
			}
			snippet_CurrentThreads--;
			snippet_FinishedThreads++;
			if ( snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0 ) {
				jQuery( '#mainwp-code-snippet-output-log' ).html( __( 'Snipped saved successfully!' ) );
				if ( type !== 'C' ) { // do not run if snippet code go to wp-config file
					mainwp_snippet_run();
				}
			}
			mainwp_snippet_update_sites_start_next();
		}, 'json');
	}
	} );

	var snippet_MaxThreads = 3;
	var snippet_CurrentThreads = 0;
	var snippet_TotalThreads = 0;
	var snippet_FinishedThreads = 0;

	mainwp_snippet_init_start = function() {
		snippet_MaxThreads = 3;
		snippet_CurrentThreads = 0;
		snippet_TotalThreads = 0;
		snippet_FinishedThreads = 0;
	}

	// Clean site
	mainwp_snippet_process_done = function() {
		setTimeout( function() {
			location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&message=1';
		}, 3000 );
	}

	// Loop
	mainwp_snippet_clear_sites_start_next = function() {
		if ( snippet_TotalThreads == 0 ) {
			snippet_TotalThreads = jQuery( '.mainwp-clear-snippet-item[status="queue"]' ).length;
		}
		while ( ( siteToRun = jQuery( '.mainwp-clear-snippet-item[status="queue"]:first' ) ) && ( siteToRun.length > 0 )  && ( snippet_CurrentThreads < snippet_MaxThreads ) ) {
			mainwp_snippet_clear_sites_start_specific( siteToRun );
		}
	}

	// Clear Sites
	mainwp_snippet_clear_sites_start_specific = function( pSiteToRun ) {
		snippet_CurrentThreads++;
		pSiteToRun.attr( 'status', 'progress' );
		var statusEl = pSiteToRun.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );
		var data = {
			action:'mainwp_snippet_clear_on_site',
			siteId: pSiteToRun.attr( 'siteid' ),
			snippetSlug: jQuery( '#mainwp_snippet_slug_value' ).val(),
			type: jQuery( '#mainwp_snippet_type_value' ).val()
		};

		jQuery.post( ajaxurl, data, function( response ) {
			pSiteToRun.attr( 'status', 'done' );
			if ( ! response || response === 'FAIL' ) {
				statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error occured. Please, try again.' ) );
			} else {
				if ( response['error'] ) {
					statusEl.html( '<i class="red times icon"></i> ' + response['error'] );
				} else if ( response['status'] === 'SUCCESS' ) {
					statusEl.html( '<i class="green check icon"></i>' );
				} else if ( response['status'] === 'FAIL' ) {
					statusEl.html( __( 'Saved without changes.' ) );
				}
			}
			snippet_CurrentThreads--;
			snippet_FinishedThreads++;
			if ( snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0 ) {
				mainwp_snippet_save( true, false );
			}
			mainwp_snippet_clear_sites_start_next();
		}, 'json');
	}

	// Loop through sites and to delete snippet
	mainwp_snippet_delete_sites_start_next = function() {
		if ( snippet_TotalThreads == 0 ) {
			snippet_TotalThreads = jQuery( '.mainwp-code-snippets-snippet-to-delete[status="queue"]' ).length;
		}
		while ( ( siteToRun = jQuery( '.mainwp-code-snippets-snippet-to-delete[status="queue"]:first' ) ) && ( siteToRun.length > 0 )  && ( snippet_CurrentThreads < snippet_MaxThreads ) ) {
			mainwp_snippet_delete_sites_start_specific( siteToRun );
		}
	}

	// Remove snippet from child sites
	mainwp_snippet_delete_sites_start_specific = function( pSiteToRun ) {
		snippet_CurrentThreads++;
		pSiteToRun.attr( 'status', 'progress' );
		var statusEl = pSiteToRun.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );
		var data = {
			action:'mainwp_snippet_delete_on_site',
			siteId: pSiteToRun.attr( 'siteid' ),
			snippetSlug: jQuery( '#mainwp_snippet_slug_value' ).val(),
			type: jQuery( '#mainwp_snippet_type_value' ).val()
		};

		jQuery.post( ajaxurl, data, function ( response ) {
			pSiteToRun.attr( 'status', 'done' );
			if ( ! response || response === 'FAIL' ) {
				statusEl.html( '<i class="red times icon"></i> ' + __( 'Undefined error occured. Please, try again.' ) );
			} else {
				if ( response['error'] ) {
					statusEl.html( '<i class="red times icon"></i> ' + response['error'] );
				} else if ( response['status'] === 'SUCCESS' ) {
					statusEl.html( '<i class="green check icon"></i>' );
				} else if ( response['status'] === 'FAIL' ) {
					statusEl.html( __( 'Saved without changes.' ) );
				}
			}

			snippet_CurrentThreads--;
			snippet_FinishedThreads++;
			if ( snippet_FinishedThreads == snippet_TotalThreads && snippet_FinishedThreads != 0 ) {
				var data = {
					action:'mainwp_snippet_delete_snippet',
					snippet_id: jQuery( '#mainwp_snippet_delete_id' ).val()
				}
				jQuery.post( ajaxurl, data, function( response ) {
					var mess;
					var mess_class;
					if ( response && response === 'SUCCESS' ) {
						mess = __( 'Process finished successfully.' );
						mess_class = 'green';
					} else {
						mess = __( 'Process finished with errors' );
						mess_class = 'red';
					}

					jQuery( '#mainwp-modal-message-zone' ).html( mess ).show();
					jQuery( '#mainwp-modal-message-zone' ).addClass( mess_class );

					setTimeout( function() {
                        location.href = 'admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets';
					}, 2000 );
				} );
			}
			mainwp_snippet_delete_sites_start_next();
		}, 'json');
	}


