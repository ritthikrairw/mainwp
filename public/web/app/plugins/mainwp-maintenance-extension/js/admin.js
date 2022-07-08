jQuery( document ).ready(function ($) {

	// Close modal and reload screen after
	$( '.close-reload-modal' ).on( 'click', function() {
		bulkTaskRunning = false;
		jQuery('#mainwp-vulnerability-checker-modal').modal( 'hide' );
		window.location.reload();
		return false;
	});

	// Run Maintenance job
	jQuery( '#maintenance_run_btn' ).on( 'click', function ( event ) {
		var errors = [];
		var selected_sites = [];
		var selected_groups = [];
		var statusEl = jQuery( '#mainwp-message-zone' );

		statusEl.html( '' ).hide();
		statusEl.removeClass( 'red yellow green' );

		if ( jQuery( '#select_by' ).val() == 'site' ) {
			jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
				selected_sites.push( jQuery( this ).val() );
			});
			if ( selected_sites.length == 0 ) {
				errors.push( __( 'Please select at least one website or group.' ) );
			}
		} else {
			jQuery( "input[name='selected_groups[]']:checked" ).each( function (i) {
				selected_groups.push( jQuery( this ).val() );
			});
			if ( selected_groups.length == 0 ) {
				errors.push( __( 'Please select at least one website or group.' ) );
			}
		}

		if ( jQuery( "input[name='maintenance_options[]']:checked" ).length <= 0 ) {
			errors.push( __( 'Please select at least one maintenance option.' ) );
		}

		if ( errors.length > 0 ) {
			statusEl.html( '<i class="close icon"></i> ' + errors.join( '<br />' ) ).show();
			statusEl.addClass( 'yellow' );
			return;
		} else {
			statusEl.html( '' ).hide();
			statusEl.removeClass( 'red yellow green' );
		}

		// Confirm
		if ( jQuery( "#mainte_option_optimize" ).is( ':checked' ) ) {
			if ( ! confirm( __( 'Attention! Before running a maintenance process, it is highly recommended to make a database backup. In rare cases, database optimization function can cause issues and crash database. Please confirm that you want to proceed.' ) ) ) {
				return false;
			}
		}

		var data = {
			action: 'maintenance_selected_sites',
			'groups[]': selected_groups,
			'sites[]': selected_sites,
		};

		statusEl.html( __( 'Initiating the mainenance job. Please wait...' ) ).show();
		jQuery.post( ajaxurl, data, function ( response ) {
			statusEl.html( '' ).hide();

			if ( response !== 'FAIL' ) {
				jQuery( '#mainwp-maintanence-process-modal' ).modal( 'show' );
				jQuery( '#mainwp-maintanence-process-modal' ).find( '.content' ).html( response );
				var allWebsiteIds = jQuery( "input[name='maintenance_wp_ids[]']" ).map( function ( indx, el ) {
					return jQuery( el ).val();
				});

				if ( allWebsiteIds === null || typeof allWebsiteIds === "undefined" ) {
					return;
				}

				maintenance_perform( allWebsiteIds );
			} else {
				statusEl.addClass( 'red' );
				statusEl.html( '<i class="close icon"></i> ' + 'Can not reach sites to perform maintenance.' ).show();
			}

		} );
	});


	// Trigger manual schedule run
	jQuery( '.maintenance_run_now' ).on('click', function (event) {
		if ( jQuery( this ).attr( 'optimize-db' ) == 1 ) {
			if ( ! confirm( __( 'Attention! Before running a maintenance process, it is highly recommended to make a database backup. In rare cases, database optimization function can cause issues and crash database. Please confirm that you want to proceed.' ) ) ) {
				return false; }
		}
		managemaintenance_trigger_run_now( jQuery( this ) );
		return false;
	});


	// Trigger new schedule submission
	jQuery( '#managemaintenance_add' ).on( 'click', function ( event ) {
		managemaintenance_add();
		return false;
	});

	// Trigger update schedule submission
	jQuery( '#managemaintenance_update' ).on( 'click', function ( event ) {
		managemaintenance_update();
		return false;
	});

	// Trigger 404 Alerts settings submission
	jQuery( '#maintenance_save_settings' ).on('click', function (event) {
		managemaintenance_save_settings();
		return false;
	});

	//Scheduling fields logic
  $( '#maintenance_perform_number' ).change( function () {
		var val = $( this ).val();
		if ( val == 1 ) {
    	$( '#mainwp_maint_send_on_wrap' ).show();
    	var val = $( '#maintenance_perform_schedule' ).val();
    	mainwp_maint_perform_schedule_date_init( val );
		} else {
	    $( '#mainwp_maint_send_on_wrap' ).hide();
		}
  });

	//Scheduling fields logic
	$( '#maintenance_perform_schedule' ).change( function () {
  	var perform_num = $( '#maintenance_perform_number' ).val();
    if ( perform_num == 1 ) {
      var val = $( this ).val();
      mainwp_maint_perform_schedule_date_init( val );
    }
	});

	//Scheduling fields logic
  $( '#mainwp_maint_schedule_month' ).change( function () {
    mainwp_maint_select_date_init();
	});

});

//Scheduling fields logic
mainwp_maint_perform_schedule_date_init = function (val) {
  jQuery( '.show_if_monthly' ).hide();
  if ( val == 'weekly' || val == 'monthly' || val == 'yearly' ) {
    if ( val == 'weekly' ) {
      jQuery( '#scheduled_send_on_day_of_week_wrap' ).show();
      jQuery( '#scheduled_send_on_day_of_month_wrap' ).hide();
      jQuery( '#scheduled_send_on_month_wrap' ).hide();
    } else if (val == 'monthly') {
      jQuery( '#scheduled_send_on_day_of_week_wrap' ).hide();
      jQuery( '#scheduled_send_on_day_of_month_wrap' ).show();
      jQuery( '#scheduled_send_on_month_wrap' ).hide();
      jQuery( '.show_if_monthly' ).show();
    } else {
      jQuery( '#scheduled_send_on_day_of_week_wrap' ).hide();
      jQuery( '#scheduled_send_on_day_of_month_wrap' ).show();
      jQuery( '#scheduled_send_on_month_wrap' ).show();
    }
    jQuery( '#mainwp_maint_send_on_wrap' ).show();
  } else {
    jQuery( '#mainwp_maint_send_on_wrap' ).hide();
  }
  mainwp_maint_select_date_init();
}

//Scheduling fields logic
mainwp_maint_select_date_init = function () {
  var recurring = jQuery('#maintenance_perform_schedule').val();
  var selected_month = jQuery('#mainwp_maint_schedule_month').val();
  jQuery( '#mainwp_maint_schedule_day_of_month option').show(); // show all days of month
  if (recurring == 'yearly') {
    if (selected_month == 2) { // Feb
      jQuery( '#mainwp_maint_schedule_day_of_month option').filter(function(){ return (this.value == 31 || this.value == 30 || this.value == 29); }).hide();
    } else if (selected_month == 4 || selected_month == 6 || selected_month == 9 || selected_month == 11 ) {
      jQuery( '#mainwp_maint_schedule_day_of_month option').filter(function(){ console.log(this.value); return (this.value == 31 ); }).hide();
    }
  }
}


// Continue maintenance action
maintenance_perform = function ( allWebsiteIds, pOptions, pRevisions ) {

	for ( var i = 0; i < allWebsiteIds.length; i++ ) {
		managemaintenance_run_status( allWebsiteIds[i], '<i class="clock outline icon"></i>' );
	}

	maintenance_run_now( allWebsiteIds, pOptions, pRevisions );
}

var websitesToMaintenance = [];
var websitesTotal = 0;
var websitesLeft = 0;
var websitesDone = 0;
var currentWebsite = 0;
var bulkTaskRunning = false;
var currentThreads = 0;
var maxThreads = 15;
var man_countErrors = 0;

// Continue maintenance action
maintenance_run_now = function ( websiteIds, pOptions, pRevisions ) {
	websitesToMaintenance = websiteIds;
	currentWebsite = 0;
	websitesDone = 0;
	websitesTotal = websitesLeft = websitesToMaintenance.length;
	bulkTaskRunning = true;

	var revisions;
	var options;

	if ( typeof pOptions !== "undefined" && typeof pRevisions !== "undefined" ) { // trigger
		options = pOptions;
		revisions = pRevisions;

		if ( options == "" ) {
			return false;
		}

	} else {
		options = jQuery.map( jQuery( "input[name='maintenance_options[]']:checked" ), function (el) {
			return jQuery( el ).val();
		});

		revisions = jQuery( "#maintenance_options_revisions_count" ).val();

		if ( options.length == 0 ) {
			return false;
		}
	}



	managemaintenance_run_next( options, revisions );
};

// Manage action status for a site
managemaintenance_run_status = function ( siteId, newStatus ) {
	jQuery( '.maintenance-status-wp[siteid="' + siteId + '"]' ).html( newStatus );
};

// Finish the maintenance process
managemaintenance_run_done = function ( pOptions, pRevisions ) {
	currentThreads--;
	if ( ! bulkTaskRunning ) {
		return;
	}
	websitesDone++;

	if ( websitesDone == websitesTotal && man_countErrors == 0 ) {
		setTimeout( function () {
			bulkTaskRunning = false;
			jQuery( '#mainwp-maintanence-process-modal' ).modal( 'hide' );
			window.location.reload();
		}, 3000 );
		return;
	}

	managemaintenance_run_next( pOptions, pRevisions );
};

// Continue maintenance action
managemaintenance_run_next = function ( pOptions, pRevisions ) {

	if ( bulkTaskRunning && ( currentThreads < maxThreads ) && ( websitesLeft > 0 ) ) {
		currentThreads++;
		websitesLeft--;
		var websiteId = websitesToMaintenance[currentWebsite++];

		managemaintenance_run_status( websiteId, '<i class="notched circle loading icon"></i>' );

		var data = {
			action: 'maintenance_run_site',
			options: pOptions,
			revisions: pRevisions,
			wp_id: websiteId
		};

		jQuery.post( ajaxurl, data, function ( response ) {
			jQuery( '#maintenance_run_loading' ).hide();
			if ( response && response['status'] == 'SUCCESS' ) {
				managemaintenance_run_status( websiteId, '<span data-tooltip="Process completed successfully!" data-position="left center" data-inverted=""><i class="green check icon"></i></span>' );
				managemaintenance_run_done( pOptions, pRevisions );
			} else {
				man_countErrors++;
				if ( response['error'] ) {
					managemaintenance_run_status( websiteId, '<span data-tooltip="' + response['error'] + '" data-position="left center" data-inverted=""><i class="times circle red icon"></i></span>' );
					managemaintenance_run_done( pOptions, pRevisions );
				} else {
					managemaintenance_run_status( websiteId, '<span data-tooltip="Undefined error occurred. Please, try again." data-position="left center" data-inverted=""><i class="times circle red icon"></i></span>' );
					managemaintenance_run_done( pOptions, pRevisions );
				}
			}
			jQuery( '#mainwp-maintanence-process-modal .mainwp-modal-progress' ).progress( { value: websitesDone, total:  websitesTotal } );
			jQuery( '#mainwp-maintanence-process-modal .mainwp-modal-progress' ).find( '.label' ).html( websitesDone + '/' + websitesTotal + ' ' + __( 'Completed' ) );
		}, 'json');
	}
};

// Submit the new schedule
managemaintenance_add = function () {
	var errors = [];
	var statusEl = jQuery( '#mainwp-message-zone' );

	statusEl.html( '' ).hide();
	statusEl.removeClass( 'red yellow green' );

	if ( jQuery( '#managemaintenance_title' ).val().trim() == '' ) {
		errors.push( __( 'Please enter a title.' ) );
	}

	if ( jQuery( "input[name='maintenance_options[]']:checked" ).length <= 0 ) {
		errors.push( __( 'Please select at least one maintenance option.' ) );
	}

	if ( jQuery( '#select_by' ).val() == 'site' ) {
		var selected_sites = [];
		jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
			selected_sites.push( jQuery( this ).val() );
		});

		if ( selected_sites.length == 0 ) {
			errors.push( __( 'Please select at least one website or group.' ) );
		}
	} else {
		var selected_groups = [];
		jQuery( "input[name='selected_groups[]']:checked" ).each( function (i) {
			selected_groups.push( jQuery( this ).val() );
		});

		if ( selected_groups.length == 0 ) {
			errors.push( __( 'Please select at least one website or group.' ) );
		}
	}

	if ( errors.length > 0 ) {
		statusEl.html( '<i class="close icon"></i> ' + errors.join( '<br />' ) ).show();
		statusEl.addClass( 'yellow' );
		return;
	} else {
		if ( jQuery( "#mainte_option_optimize" ).is( ':checked' ) ) {
      if ( ! confirm( __( 'Attention! Before running a maintenance process, it is highly recommended to make a database backup. In rare cases, database optimization function can cause issues and crash database. Please confirm that you want to proceed.' ) ) ) {
        return false;
      }
		}

		statusEl.html( '<i class="notched circle loading icon"></i> ' + __( 'Adding the maintenance task...' ) ).show();
		jQuery( '#managemaintenance_add' ).attr( 'disabled', 'true' ); //disable button to add.

    var schedule = jQuery( '#maintenance_perform_schedule' ).val();
    var data = {
			action: 'maintenance_addtask',
			title: jQuery( '#managemaintenance_title' ).val(),
			schedule: schedule,
			revisions: jQuery( '#maintenance_options_revisions_count' ).val(),
			perform: jQuery( '#maintenance_perform_number' ).val(),
      recurring_day: maintenance_get_schedule_day( schedule ),
      recurring_hour: jQuery( '#mainwp_maint_schedule_at_time' ).val(),
			'options[]': jQuery.map( jQuery( "input[name='maintenance_options[]']:checked" ), function (el) {
				return jQuery( el ).val();
			}),
			'groups[]': selected_groups,
			'sites[]': selected_sites
		};

		jQuery.post( ajaxurl, data, function ( response ) {
			response = jQuery.trim( response );
			if ( response.substr( 0, 5 ) == 'ERROR' ) {
				statusEl.html( '<i class="close icon"></i> ' + response.substr( 6 ) ).show();
				statusEl.addClass( 'red' );
			} else {
				// Message the maintenance task was added
				window.location.href = 'admin.php?page=Extensions-Mainwp-Maintenance-Extension&tab=schedules';
				return;
			}
			jQuery( '#managemaintenance_add' ).removeAttr( 'disabled' ); //Enable add button
		});
	}
};

// Schedule helper
maintenance_get_schedule_day = function (schedule) {
  var sche_day = '';
  if ( schedule == 'yearly' ) {
    sche_day = jQuery( '#mainwp_maint_schedule_month' ).val() + '-' + jQuery( '#mainwp_maint_schedule_day_of_month' ).val();
  } else if ( schedule == 'monthly' ) {
    sche_day = jQuery( '#mainwp_maint_schedule_day_of_month' ).val();
  } else if ( schedule == 'weekly' ) {
    sche_day = jQuery( '#mainwp_maint_schedule_day' ).val();
  }
  return sche_day;
}

managemaintenance_save_settings = function () {
	var selected_sites = [];
	var selected_groups = [];

	var statusEl = jQuery( '#mainwp-message-zone' );

	statusEl.html( '' ).hide();
	statusEl.removeClass( 'red yellow green' );

	if ( jQuery( '#select_by' ).val() == 'site' ) {
		jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
			selected_sites.push( jQuery( this ).val() );
		});
	} else {
		jQuery( "input[name='selected_groups[]']:checked" ).each( function (i) {
			selected_groups.push( jQuery( this ).val() );
		});
	}

	jQuery( '#maintenance_save_settings' ).attr( 'disabled', 'true' ); //disable button to add.
	statusEl.html( '<i class="notched circle loading icon"></i> ' + 'Loading child sites. Please wait...' ).show();

	var data = {
		action: 'maintenance_save_settings',
		email: jQuery( '#managemaintenance_404_alert_email' ).val(),
		enable: jQuery( '#managemaintenance_enable_404_alert' ).attr( "checked" ) ? 1 : 0,
		'groups[]': selected_groups,
		'sites[]': selected_sites
	};

	jQuery.post(ajaxurl, data, function (response) {
		jQuery( '#maintenance_save_settings' ).removeAttr( 'disabled' ); //Enable add button
		statusEl.html( '' ).hide();
		statusEl.removeClass( 'red yellow green' );

		if ( response ) {
			if ( response == 'SUCCESS' ) {
				var data = {
					action: 'maintenance_save_settings_load_sites'
				};
				jQuery.post( ajaxurl, data, function ( response ) {
					if ( response && response !== 'FAIL' ) {
						jQuery( '#mainwp-maintanence-process-modal' ).modal( 'show' );
						jQuery( '#mainwp-maintanence-process-modal' ).find( '.content' ).html( response );

						main_TotalThreads = jQuery( '.mainwpMaintenanceSitesItem[status="queue"]' ).length;

						mainwp_maintenance_save_settings_start_next();
					} else {
						statusEl.html( '<i class="close icon"></i> ' + 'Loading sites failed. Please try again.' ).show();
						statusEl.addClass( 'red' );
					}
				} );
			} else if (response == 'FAIL') {
				statusEl.html( '<i class="close icon"></i> ' + 'Saving settings failed. Please try again.' ).show();
				statusEl.addClass( 'red' );
			} else {
				statusEl.html( '<i class="close icon"></i> ' + response ).show();
				statusEl.addClass( 'red' );
			}
		} else {
			statusEl.html( '<i class="close icon"></i> ' + 'Undefined error occurred. Please try again.' ).show();
			statusEl.addClass( 'red' );
		}
	});
}

// Loop through sites to save settings
var main_MaxThreads = 3;
var main_CurrentThreads = 0;
var main_TotalThreads = 0;
var main_FinishedThreads = 0;

mainwp_maintenance_save_settings_start_next = function () {
	while ( ( siteToMain = jQuery( '.mainwpMaintenanceSitesItem[status="queue"]:first' ) ) && ( siteToMain.length > 0 ) && ( main_CurrentThreads < main_MaxThreads ) ) {
		mainwp_maintenance_save_settings_start_specific( siteToMain );
	}
};

// Save settigns to child sites
mainwp_maintenance_save_settings_start_specific = function ( pSiteToMain ) {
	main_CurrentThreads++;
	pSiteToMain.attr( 'status', 'progress' );

	var statusEl = pSiteToMain.find( '.status' ).html( '<i class="notched circle loading icon"></i>' );

	var data = {
		action: 'mainwp_maintenance_performsavesettings',
		siteId: pSiteToMain.attr( 'siteid' ),
		email: jQuery( '#mainwp_maintenance_settings_email' ).val(),
		enable: jQuery( '#mainwp_maintenance_settings_enable_alert' ).val(),
		do_action: pSiteToMain.attr( 'action' ),
	};

	jQuery.post(ajaxurl, data, function ( response ) {
		pSiteToMain.attr( 'status', 'done' );
		if ( response && response['result'] == 'SUCCESS' ) {
			statusEl.html( '<span data-tooltip="Process completed successfully!" data-position="left center" data-inverted=""><i class="green check icon"></i></span>' ).show();
		} else if ( response && response['error'] ) {
			statusEl.html( '<span data-tooltip="' + response['error'] + '" data-position="left center" data-inverted=""><i class="times circle red icon"></i></span>' ).show();
		} else {
			statusEl.html( '<span data-tooltip="Undefined error occurred. Please, try again." data-position="left center" data-inverted=""><i class="times circle red icon"></i></span>' ).show();
		}

		main_CurrentThreads--;
		main_FinishedThreads++;

		jQuery( '#mainwp-maintanence-process-modal .mainwp-modal-progress' ).progress( { value: main_FinishedThreads, total:  main_TotalThreads } );
		jQuery( '#mainwp-maintanence-process-modal .mainwp-modal-progress' ).find( '.label' ).html( main_FinishedThreads + '/' + main_TotalThreads + ' ' + __( 'Completed' ) );

		if ( main_FinishedThreads == main_TotalThreads && main_FinishedThreads != 0 ) {
			setTimeout(function () {
				window.location.reload();
			}, 2000 );
		} else {
      mainwp_maintenance_save_settings_start_next();
    }

	}, 'json');
};

// Update schedule
managemaintenance_update = function (event) {

	var errors = [];
	var statusEl = jQuery( '#mainwp-message-zone' );

	statusEl.html( '' ).hide();
	statusEl.removeClass( 'red yellow green' );

	if ( jQuery( '#managemaintenance_title' ).val().trim() == '' ) {
		errors.push( __( 'Please enter a valid title for your maintenance task' ) );
	}

	if ( jQuery( "input[name='maintenance_options[]']:checked" ).length <= 0 ) {
		errors.push( __( 'Please select at least one maintenance option.' ) );
	}

	if ( jQuery( '#select_by' ).val() == 'site' ) {
		var selected_sites = [];
		jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
			selected_sites.push( jQuery( this ).val() );
		});

		if ( selected_sites.length == 0 ) {
			errors.push( __( 'Please select at least one website or group.' ) );
		}
	} else {
		var selected_groups = [];
		jQuery( "input[name='selected_groups[]']:checked" ).each( function (i) {
			selected_groups.push( jQuery( this ).val() );
		});

		if ( selected_groups.length == 0 ) {
			errors.push( __( 'Please select at least one website or group.' ) );
		}
	}

	if ( errors.length > 0) {
		statusEl.html( '<i class="close icon"></i> ' + errors.join( '<br />' ) ).show();
		statusEl.addClass( 'yellow' );
	} else {
		statusEl.html( '<i class="notched circle loading icon"></i>' + __( 'Updating the maintenance task. Please wait...' ) ).show();
		jQuery( '#managemaintenance_update' ).attr( 'disabled', 'true' ); //disable button to add.

		task_id = jQuery( '#edit_managemaintenance_id' ).val();

    var schedule = jQuery( '#maintenance_perform_schedule' ).val();
		var data = {
			action: 'maintenance_updatetask',
			taskid: task_id,
			title: jQuery( '#managemaintenance_title' ).val(),
			schedule: schedule,
			revisions: jQuery( '#maintenance_options_revisions_count' ).val(),
			perform: jQuery( '#maintenance_perform_number' ).val(),
      recurring_day: maintenance_get_schedule_day(schedule),
      recurring_hour: jQuery( '#mainwp_maint_schedule_at_time' ).val(),
			'options[]': jQuery.map(jQuery( "input[name='maintenance_options[]']:checked" ), function (el) {
				return jQuery( el ).val();
			}),
			'groups[]': selected_groups,
			'sites[]': selected_sites
		};

		jQuery.post( ajaxurl, data, function ( response ) {
			response = jQuery.trim( response );
			if ( response.substr( 0, 5 ) == 'ERROR' ) {
				statusEl.html( '<i class="close icon"></i> ' + response.substr( 6 ) ).show();
				statusEl.addClass( 'red' );
			} else {
				window.location.href = 'admin.php?page=Extensions-Mainwp-Maintenance-Extension&tab=schedules';
				return;
			}
			jQuery( '#managemaintenance_update' ).removeAttr( 'disabled' ); //Enable add button
		});
	}
};

// Delete Scheduled Maintenance task
managemaintenance_remove = function ( id ) {

	var q = confirm( __( 'Are you sure you want to delete this maintenance task?' ) );
	var workingRow = jQuery( '#task-' + id  );

	if ( q ) {
		workingRow.html( '<td colspan="5"><i class="notched circle loading icon"></i> ' + __( 'Removing the task. Please wait...' ) + '</td>' );
		var data = {
			action: 'maintenance_removetask',
			id: id
		};

		jQuery.post( ajaxurl, data, function ( response ) {
			response = jQuery.trim( response );
			var result = '';
			var error = '';

			if ( response == 'SUCCESS' ) {
				result = __( '<td colspan="5"><i class="check green circle icon"></i> ' + __( 'Scheduled task removed.' ) + '</td>' );
			} else {
				error = __( '<td colspan="5"><i class="times red circle icon"></i> ' + __( 'Scheduled task could not be removed. Please try again.' ) + '</td>' );
			}

			if ( error != '' ) {
				setHtml( workingRow, error );
			}

			if ( result != '' ) {
				workingRow.html( result );
			}

			if ( error == '' ) {
				workingRow.remove();
			}
		});
	}
};

// Execute scheduled task
managemaintenance_trigger_run_now = function ( el ) {
	el = jQuery( el );
	el.attr( 'disabled', 'true' );

	var statusEl = jQuery( '#mainwp-message-zone' );

	statusEl.html( '' ).hide();
	statusEl.removeClass( 'red yellow green' );

	statusEl.html( '<i class="notched circle loading icon"></i> ' + __( 'Preparing the maintenance task. Please wait...' ) ).show();

	var taskId = el.closest( 'tr' ).attr( 'taskid' );
	var taskName = el.closest( 'tr' ).attr( 'taskName' );

	var data = {
		action: 'maintenance_maintenancetask_get_sites_to_run',
		task_id: taskId
	};

	jQuery.post( ajaxurl, data, function ( response ) {
		statusEl.html( '' ).hide();

		if ( response !== 'FAIL' ) {
			jQuery( '#mainwp-maintanence-process-modal' ).modal( 'show' );
			jQuery( '#mainwp-maintanence-process-modal' ).find( '.header' ).html( taskName );
			jQuery( '#mainwp-maintanence-process-modal' ).find( '.content' ).html( response );

			var allWebsiteIds = jQuery( "input[name='maintenance_wp_ids[]']" ).map( function ( indx, el ) {
				return jQuery( el ).val();
			});

			if ( allWebsiteIds === null || typeof allWebsiteIds === "undefined" ) {
				return;
			}

			var options = jQuery( '#mainwp_mainten_trigger_data' ).attr( 'options' );
			var revisions = jQuery( '#mainwp_mainten_trigger_data' ).attr( 'revisions' );

			maintenance_perform( allWebsiteIds, options, revisions );
		} else {
			statusEl.addClass( 'red' );
			statusEl.html( '<i class="close icon"></i>' + 'The maintenance task could not be executed. Please, review the task settings and try again.' ).show();
		}
	});
};
