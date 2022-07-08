
var favorites_overwrite_existing = false;
var favorites_activate_plugin = false;

favorite_install_bulk = function (type, pElement, slug, url, selected_sites, selected_groups) {
	var selected = (selected_sites.length > 0 ? 'site' : 'group');

	var data = {
		action: 'favorite_prepareinstallplugintheme',
		type: type,
		slug: slug,
		selected_by: selected
	};

	if ( url ) {
		data['url'] = url;
	}

	if (selected == 'site') {
		data['selected_sites[]'] = selected_sites;
	} else {
		data['selected_groups[]'] = selected_groups;
	}

	favorites_overwrite_existing = jQuery( '#chk_overwrite' ).is( ':checked' );

	if (type == 'plugin') {
		favorites_activate_plugin = jQuery( '#chk_activate_plugin' ).is( ':checked' );
	}

	pElement.html( '<i class="notched circle loading icon"></i> ' + __( 'Preparing %1 installation.', type ) );

	jQuery.post(ajaxurl, data, function (pType) {
		return function (response) {
			var installQueue = '<h3>Installing ' + type + '</h3>';
			for (var siteId in response.sites) {
				var site = response.sites[siteId];
				var site_name = site['name'].replace( /\\(.)/mg, "$1" );
				installQueue += '<span class="siteBulkInstall" download-url="' + response.url + '" siteid="' + siteId + '" status="queue"><strong>' + site_name + '</strong>: <span class="queue">' + __( 'Queued' ) + '</span><span class="progress"><i class="notched circle loading icon"></i> ' + __( 'In progress' ) + '</span><span class="status"></span></span><br />';
			}
			pElement.html( installQueue );
			favorite_install_bulk_start_next( pType );
			mainwp_favorite_bulk_install_slug_next( type, selected_sites, selected_groups );
		}
	}(type), 'json');

};

fav_bulkInstallMaxThreads = 2;
fav_bulkInstallCurrentThreads = 0;

mainwp_favorite_bulk_install = function (pType) {

	var selected_sites = [];
	var selected_groups = [];

	if ( jQuery( '#select_by' ).val() == 'site' ) {
		jQuery( "input[name='selected_sites[]']:checked" ).each( function (i) {
			selected_sites.push( jQuery( this ).val() );
		} );
		if ( selected_sites.length == 0 ) {
			show_error( 'ajax-error-zone', __( 'Please select websites or groups on the right side to install files.' ) );
			return;
		}
	} else {
		jQuery( "input[name='selected_groups[]']:checked" ).each( function (i) {
			selected_groups.push( jQuery( this ).val() );
		} );
		if ( selected_groups.length == 0 ) {
			show_error( 'ajax-error-zone', __( 'Please select websites or groups on the right side to install files.' ) )
			return;
		}
	}

	if ( pType == 'plugin' ) {
		if ( jQuery( "#mainwp-favorite-plugins-groups-tab" ).hasClass( 'active' ) ) {
			isGroup = true;
		} else {
			isGroup = false;
		}

		var installList = '';

		if ( isGroup ) {
			jQuery( "#mainwp-favorite-plugin-groups-table tbody  .check-column INPUT:checkbox:checked" ).each( function (i) {
				var parent = jQuery( this ).closest('tr');
				var name = parent.attr( 'group_name' ).replace( /\+/g, '%20' );
				name = decodeURIComponent( name );
				installList += '<div class="bulk-install-slug" status="queue" group_id="' + parent.attr( 'group_id' ) + '" group_name="' + parent.attr( 'group_name' ) + '" >' + name + '</div><br />';
			} );
		} else {
			jQuery( "#mainwp-favorite-plugins-table tbody  .check-column INPUT:checkbox:checked" ).each( function (i) {
				var parent = jQuery( this ).closest( 'tr' );
				var name = parent.attr( 'favorite_name' ).replace( /\+/g, '%20' );
				name = decodeURIComponent( name );
				installList += '<div class="bulk-install-slug" status="queue" download-url="' + parent.attr( 'download_url' ) + '" favorite_id="' + parent.attr( 'favorite_id' ) + '" favorite_slug="' + parent.attr( 'favorite_slug' ) + '" >' + name + '</div><br />';
			} );
		}

		if ( installList == '' )
			return;

		jQuery('#favorites-bulk-install-modal .content').html( installList );
		jQuery('#favorites-bulk-install-modal').modal( {
			onHide: function() {
				location.href = 'admin.php?page=PluginsFavorite';
			}
		} ).modal( 'show' );

		if ( isGroup ) {
			mainwp_favorite_bulk_group_install_next( 'plugin', selected_sites, selected_groups );
		} else {
			mainwp_favorite_bulk_install_slug_next( 'plugin', selected_sites, selected_groups );
		}
	} else if ( pType == 'theme' ) {
		if ( jQuery( "#mainwp-favorite-themes-groups-tab" ).hasClass( 'active' ) ) {
			isGroup = true;
		} else {
			isGroup = false;
		}

		var installList = '';

		if ( isGroup ) {
			jQuery( "#mainwp-favorite-theme-groups-table tbody  .check-column INPUT:checkbox:checked" ).each( function (i) {
				var parent = jQuery( this ).closest( 'tr' );
				var name = parent.attr( 'group_name' ).replace( /\+/g, '%20' );
				name = decodeURIComponent( name );
				installList += '<div class="bulk-install-slug" status="queue" group_id="' + parent.attr( 'group_id' ) + '" group_name="' + parent.attr( 'group_name' ) + '" >' + name + '</div><br />';
			} );
		} else {
			jQuery( "#mainwp-favorite-themes-table tbody  .check-column INPUT:checkbox:checked" ).each( function (i) {
				var parent = jQuery( this ).closest( 'tr' );
				var name = parent.attr( 'favorite_name' ).replace( /\+/g, '%20' ); // plus
				name = decodeURIComponent( name );
				installList += '<div class="bulk-install-slug" status="queue" download-url="' + parent.attr( 'download_url' ) + '" favorite_id="' + parent.attr( 'favorite_id' ) + '" favorite_slug="' + parent.attr( 'favorite_slug' ) + '" >' + name + '</div><br />';
			} );
		}

		if ( installList == '' )
			return;

		jQuery( '#favorites-bulk-install-modal .content' ).html( installList );
		jQuery( '#favorites-bulk-install-modal' ).modal( {
			onHide: function() {
				location.href = 'admin.php?page=ThemesFavorite';
			}
		} ).modal( 'show' );

		if ( isGroup ) {
			mainwp_favorite_bulk_group_install_next( 'theme', selected_sites, selected_groups );
		} else {
			mainwp_favorite_bulk_install_slug_next( 'theme', selected_sites, selected_groups );
		}
	}
};

mainwp_favorite_bulk_group_install_next = function ( type, pSites, pGroups) {
    var slugToInstall = jQuery( '.bulk-install-slug[status="queue"]:first' );
    if ( slugToInstall.length > 0 ) {
        slugToInstall.attr('status', 'process');
        group_install_bulk( type, slugToInstall, slugToInstall.attr('group_id'), pSites, pGroups);
    }
};

mainwp_favorite_bulk_install_slug_next = function ( pType, pSites, pGroups) {
    var slugToInstall = jQuery( '.bulk-install-slug[status="queue"]:first' );

    if ( slugToInstall.length > 0 ) {
        slugToInstall.attr('status', 'process');
        favorite_install_bulk( pType, slugToInstall, slugToInstall.attr('favorite_slug'), slugToInstall.attr( 'download-url' ), pSites, pGroups );
    }
};


favorite_install_bulk_start_next = function (pType) {
    while ((siteToInstall = jQuery( '.siteBulkInstall[status="queue"]:first' )) && (siteToInstall.length > 0) && (fav_bulkInstallCurrentThreads < fav_bulkInstallMaxThreads)) {
        favorite_install_bulk_start_specific( pType, siteToInstall );
    }
};

favorite_install_bulk_start_specific = function (pType, pSiteToInstall) {
	fav_bulkInstallCurrentThreads++;
	pSiteToInstall.attr( 'status', 'progress' );

	pSiteToInstall.find( '.queue' ).hide();
	pSiteToInstall.find( '.progress' ).show();

	var data = mainwp_secure_data({
		action: 'favorite_performinstallplugintheme',
		type: pType,
		url: pSiteToInstall.attr( 'download-url' ),
		siteId: pSiteToInstall.attr( 'siteid' ),
		activatePlugin: favorites_activate_plugin,
		overwrite: favorites_overwrite_existing
	});

	jQuery.post(ajaxurl, data, function (pType, pSiteToInstall) {
		return function (response) {
			pSiteToInstall.attr( 'status', 'done' );

			pSiteToInstall.find( '.progress' ).hide();
			var statusEl = pSiteToInstall.find( '.status' );
			statusEl.show();

			if ((response.ok != undefined) && (response.ok[pSiteToInstall.attr( 'siteid' )] != undefined)) {
				statusEl.html( __( 'Installation successful' ) );
			} else if ((response.errors != undefined) && (response.errors[pSiteToInstall.attr( 'siteid' )] != undefined)) {
				statusEl.html( __( 'Installation failed' ) + ': ' + response.errors[pSiteToInstall.attr( 'siteid' )][1] );
				statusEl.css( 'color', 'red' );
			} else {
				statusEl.html( __( 'Installation failed' ) );
				statusEl.css( 'color', 'red' );
			}

			fav_bulkInstallCurrentThreads--;

			favorite_install_bulk_start_next( pType );
		}
	}(pType, pSiteToInstall), 'json');
};

group_install_bulk = function (type, pElement, groupid, selected_sites, selected_groups ) {
        var selected = (selected_sites.length > 0 ? 'site' : 'group');
	var data = {
		action: 'favorite_prepareinstallgroupplugintheme',
		groupid: groupid
	};

	var params = {
		selected_by: selected
	}

        favorites_overwrite_existing = jQuery( '#chk_overwrite' ).is( ':checked' );

        if (type == 'plugin') {
            favorites_activate_plugin = jQuery( '#chk_activate_plugin' ).is( ':checked' );
        }

	if ( selected == 'site' ) {
            params['selected_sites'] = selected_sites;
        } else {
            params['selected_groups'] = selected_groups;
        }

//	if (jQuery( '#select_by' ).val() == 'site') {
//		var selected_sites = [];
//		jQuery( "input[name='selected_sites[]']:checked" ).each(function (i) {
//			selected_sites.push( jQuery( this ).val() );
//		});
//
//		if (selected_sites.length == 0) {
//			show_error( 'ajax-error-zone', __( 'Please select websites or groups on the right side to install files.' ) );
//			return;
//		}
//		params['selected_sites'] = selected_sites;
//	} else {
//		var selected_groups = [];
//		jQuery( "input[name='selected_groups[]']:checked" ).each(function (i) {
//			selected_groups.push( jQuery( this ).val() );
//		});
//		if (selected_groups.length == 0) {
//			show_error( 'ajax-error-zone', __( 'Please select websites or groups on the right side to install files.' ) )
//			return;
//		}
//		params['selected_groups'] = selected_groups;
//	}

        pElement.html( '<i class="notched circle loading icon"></i> ' + __( 'Preparing %1 installation.', type ) );

	jQuery.post(ajaxurl, data, function (pType, pParams) {
		return function (response) {
			var installFavoriteQueue = '<h3>Installing ' + pType + ' group:</h3>';
			for (var favoriteId in response.favorites) {
				var favorite = response.favorites[favoriteId];
				installFavoriteQueue += '<span class="favoriteBulkInstall" favoriteName="' + favorite['name'] + '" favoriteDownloadUrl="' + favorite['download_url'] + '" favoriteSlug="' + favorite['slug'] + '" status="queue"><h3>' + favorite['name'] + '</h3> <span class="progress"><i class="notched circle loading icon"></i> ' + __( 'sites loading..' ) + '</span><span class="status"></span></span><br />';
			}

			pElement.html( installFavoriteQueue );
			group_loading_sites_start_next( pType, pParams );
			mainwp_favorite_bulk_group_install_next( type, selected_sites, selected_groups );
		}
	}(type, params), 'json');
}

group_favoriteBulkInstallCurrentThreads = 0;

group_loading_sites_start_next = function (pType, pParams) {
	while ((favoriteToInstall = jQuery( '.favoriteBulkInstall[status="queue"]:first' )) && (favoriteToInstall.length > 0) && (group_favoriteBulkInstallCurrentThreads < fav_bulkInstallMaxThreads)) {
		url = favoriteToInstall.attr( 'favoriteDownloadUrl' );
		slug = favoriteToInstall.attr( 'favoriteSlug' );
		name = favoriteToInstall.attr( 'favoriteName' );
		group_loading_sites_start_specific( pType, slug, url, name, pParams, favoriteToInstall );
	}
	if (jQuery( '.favoriteBulkInstall[status="queue"]' ).length == 0) {
            group_favorite_install_bulk_start_next( pType );
        }
};

group_loading_sites_start_specific = function (type, slug, url, name, pParams, pFavoriteToInstall) {
	var data = {
		action: 'favorite_prepareinstallplugintheme',
		type: type,
		slug: slug,
		selected_by: pParams['selected_by'],
		'selected_sites[]': pParams['selected_sites'],
		'selected_groups[]': pParams['selected_groups']
	};
	if ( url ) {
            data['url'] = url;
        }
	group_favoriteBulkInstallCurrentThreads++;
	pFavoriteToInstall.attr( 'status', 'sites-loaded' );
	jQuery.post(ajaxurl, data, function (pType) {
		return function (response) {
			var installQueue = '<h3>' + name + '</h3>';
			for (var siteId in response.sites) {
				var site = response.sites[siteId];
				var site_name = site['name'].replace( /\\(.)/mg, "$1" );
				installQueue += '<span class="siteBulkInstall" download-url="' + response.url + '" siteid="' + siteId + '" status="queue"><strong>' + site_name + '</strong>: <span class="queue">' + __( 'waiting..' ) + '</span><span class="progress"><i class="notched circle loading icon"></i>  ' + __( 'In progress' ) + '</span><span class="status"></span></span><br />';
			}
			pFavoriteToInstall.html( installQueue );
			group_favoriteBulkInstallCurrentThreads--;
			group_loading_sites_start_next( type, pParams );
		}
	}(type), 'json');
}


group_favorite_install_bulk_start_next = function (pType) {
	while ((siteToInstall = jQuery( '.siteBulkInstall[status="queue"]:first' )) && (siteToInstall.length > 0) && (fav_bulkInstallCurrentThreads < fav_bulkInstallMaxThreads)) {
		var pUrl = siteToInstall.attr( 'download-url' );
		group_favorite_install_bulk_start_specific( pType, pUrl, siteToInstall );
	}
};

group_favorite_install_bulk_start_specific = function (pType, pUrl, pSiteToInstall) {
	fav_bulkInstallCurrentThreads++;
	pSiteToInstall.attr( 'status', 'progress' );

	pSiteToInstall.find( '.queue' ).hide();
	pSiteToInstall.find( '.progress' ).show();

	var data = mainwp_secure_data({
		action: 'favorite_performinstallplugintheme',
		type: pType,
		url: pUrl,
		siteId: pSiteToInstall.attr( 'siteid' ),
		activatePlugin: favorites_activate_plugin,
		overwrite: favorites_overwrite_existing,
	});

	jQuery.post(ajaxurl, data, function (pType, pUrl, pSiteToInstall) {
		return function (response) {
			pSiteToInstall.attr( 'status', 'done' );

			pSiteToInstall.find( '.progress' ).hide();
			var statusEl = pSiteToInstall.find( '.status' );
			statusEl.show();

			if ((response.ok != undefined) && (response.ok[pSiteToInstall.attr( 'siteid' )] != undefined)) {
				statusEl.html( __( 'Installation successful' ) );
			} else if ((response.errors != undefined) && (response.errors[pSiteToInstall.attr( 'siteid' )] != undefined)) {
				statusEl.html( __( 'Installation failed' ) + ': ' + response.errors[pSiteToInstall.attr( 'siteid' )][1] );
				statusEl.css( 'color', 'red' );
			} else {
				statusEl.html( __( 'Installation failed' ) );
				statusEl.css( 'color', 'red' );
			}

			fav_bulkInstallCurrentThreads--;
			group_favorite_install_bulk_start_next( pType );
		}
	}(pType, pUrl, pSiteToInstall), 'json');
};

// To delete

managegroup_remove = function (type, id) {
	var q = confirm( __( 'Are you sure you want to delete this group item?' ) );
	if (q) {
		jQuery( '#fav-status' + id ).html( __( 'Removing the group..' ) );
		var data = {
			action: 'favorite_removegroup',
			id: id
		};
		jQuery.post(ajaxurl, data, function (response) {
			response = jQuery.trim( response );
			var result = '';
			var error = '';
			if (response == 'SUCCESS') {
				result = __( 'Your group has been removed.' );
			} else {
				error = __( 'An unspecified error occured' );
			}

			if (error != '') {
				jQuery( '#group-status-' + id ).html( error );
			} else if (result != '') {
				jQuery( '#group-' + id ).html( '<td colspan="4">' + result + '</td>' );
			}

		});
	}
	return false;
};

favorites_setting_uploadbulk_oncomplete = function (id, fileName, result) {
	if (result.success) {
		if (totalSuccess > 0) { // global variable
			jQuery( ".qq-upload-file" ).each(function (i) {
				if (jQuery( this ).parent().attr( 'class' ) && jQuery( this ).parent().attr( 'class' ).replace( /^\s+|\s+$/g, "" ) == 'qq-upload-success') {
					_file = jQuery( this ).attr( 'filename' );
					if (jQuery( this ).next().next().attr( 'class' ) != 'favorites-setting-add-file') {
						jQuery( this ).next().after( '<span class="favorites-setting-add-file" status="queue" id="' + id + '" realfile = "' + fileName + '" file="' + _file + '">' + __( 'Queue..' ) + '</span> ' );
						favorites_setting_add_to_favorites_next();
					}
				}
			});
		}
	}
}

bulkSettingAddCurrentThreads = 0;

favorites_setting_add_to_favorites_next = function () {
	while ((favoriteToAdd = jQuery( '.favorites-setting-add-file[status="queue"]:first' )) && (favoriteToAdd.length > 0) && (bulkSettingAddCurrentThreads < bulkInstallMaxThreads)) {
		var type = favoriteToAdd.closest( '.favorites-container-box' ).find( ".favorite-type" ).val();
		//console.log(type);
		favorites_add_to_favorites_start_specific( type, favoriteToAdd );
	}
}

favorites_add_to_favorites_start_specific = function (type, pFavoriteToAdd) {
	bulkSettingAddCurrentThreads++;
	pFavoriteToAdd.attr( 'status', 'progress' );
	pFavoriteToAdd.html( '<i class="notched circle loading icon"></i> ' + __( 'Adding to favorites.' ) );
	var data = {
		action: 'favorites_uploadbulkaddtofavorites',
		type: type,
		file: pFavoriteToAdd.attr( 'file' ),
		copy: 'no',
                nonce: security_nonces['mainwp-common-nonce'] // from mainwp
	};
	jQuery.post(ajaxurl, data, function (pFavoriteToAdd) {
		return function (response) {
			if (response == 'NEWER_EXISTED') {
				pFavoriteToAdd.html( __( 'Newer version existed, doesn\'t add to favorites.' ) );
			} else if (response == 'SUCCESS') {
				pFavoriteToAdd.html( __( 'Add to favorites successfully.' ) );
			} else {
                                pFavoriteToAdd.html( __( 'Add to favorites failed.' ) );
                        }
			bulkSettingAddCurrentThreads--;
		}
	}(pFavoriteToAdd));
};
