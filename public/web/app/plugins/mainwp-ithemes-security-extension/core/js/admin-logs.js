jQuery( document ).ready( function () {

	var $_GET = {};

	document.location.search.replace( /\??(?:([^=]+)=([^&]*)&?)/g, function () {
		function decode( s ) {
			return decodeURIComponent( s.split( "+" ).join( " " ) );
		}

		$_GET[decode( arguments[1] )] = decode( arguments[2] );
	} );

//	var uri = URI( window.location.href )
//
//	jQuery( '#itsec_log_filter' ).on( 'change', function () {
//
//		uri.removeSearch( 'itsec_log_filter' ).removeSearch( 'orderby' ).removeSearch( 'order' ).removeSearch( 'paged' ).addSearch( { itsec_log_filter : [this.value] } );
//		window.location.replace( uri );
//
//	} );

} );

//process add to whitelist
jQuery( '.mwp_itheme_clear_logs_btn' ).bind( 'click', function ( event ) {
        if (confirm(__('Are you sure?')) == false)
            return false;
        
        event.preventDefault(); 
        var caller = this;
        jQuery( caller ).closest('.submit').find('i').show();
        jQuery( caller ).attr('disabled', 'disabled');

        var site_id = jQuery('#mainwp_itheme_managesites_site_id').attr('site-id');
        if ( site_id > 0) {                        
            mainwp_itheme_individual_clearlogs(site_id, caller);
        } else {  
            var data = {
                action:'mainwp_itheme_load_sites',
                what: 'clearlogs'
            };            
            jQuery.post(ajaxurl, data, function (response) {
                if (response) {
                    jQuery('#mainwp_itheme_screens_tab').html(response);    
                    ithemes_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
                    if (ithemes_bulkTotalThreads > 0) 
                        mainwp_itheme_clearlogs_start_next();
                } else {
                    jQuery('#mainwp_itheme_screens_tab').html('<div class="mainwp_info-box-red">' + __("Undefined error.") + '</div>');

                }
            })
        }
} );

mainwp_itheme_individual_clearlogs = function(pSiteId, pCaller) {
    var statusEl = jQuery('.mwp_itheme_clear_logs_status'); 
    statusEl.hide();
    var data = {
                action: 'mainwp_itheme_clear_all_logs',
                ithemeSiteID: pSiteId,                
                individualSite: true                
            };   
    //call the ajax
    jQuery.post( ajaxurl, data, function ( response ) {
            jQuery( pCaller ).closest('.submit').find('i').hide();
            jQuery( pCaller ).removeAttr('disabled'); 
            var success = false;
            if ( response) {
                if (response.error) {
                    statusEl.html( response.error );
                } else if (response.message) {
                    statusEl.html(response.message);
                } else if (response.result == 'success') {                               
                    statusEl.html(__('Successful.'));
                    success = true;
                } else {
                    statusEl.html( 'Undefined error.' );
                }
            }
            else 
            {
                statusEl.html( 'Undefined error.' );
            }        
            statusEl.fadeIn();
            if (success) {
                setTimeout(function ()
                {
                    statusEl.fadeOut();        
                }, 3000);
            }
    }, 'json' );
}

mainwp_itheme_clearlogs_start_next = function() {    
    while ((objProcess = jQuery('.siteItemProcess[status=queue]:first')) && (objProcess.length > 0) && (ithemes_bulkCurrentThreads < ithemes_bulkMaxThreads))
    {       
        objProcess.attr('status', 'processed');       
        mainwp_itheme_clearlogs_start_specific(objProcess);
    }
    
    if (ithemes_bulkFinishedThreads > 0 && ithemes_bulkFinishedThreads == ithemes_bulkTotalThreads) {
        jQuery('#mainwp_itheme_screens_tab').append('<div class="mainwp_info-box">' + __("Finished.") + '</div><p><a class="button ui green" href="' + mainwp_itsec_page.settings_page_url + '">Return to Settings</a></p>');        
    }
    
}

mainwp_itheme_clearlogs_start_specific = function(objProcess) {    
    var loadingEl = objProcess.find('i');  
    var statusEl = objProcess.find('.status');       
    ithemes_bulkCurrentThreads++;
    var data = {
                action: 'mainwp_itheme_clear_all_logs',
                ithemeSiteID: objProcess.attr('site-id')                
            };
            
    statusEl.html('');
    loadingEl.show();
    //call the ajax
    jQuery.post( ajaxurl, data, function ( response ) {
            loadingEl.hide();
            if ( response) {
                if (response.error) {
                    statusEl.html( response.error );
                } else if (response.message) {
                    statusEl.html(response.message);
                } else if (response.result == 'success') {                               
                    statusEl.html(__('Successful.'));
                } else {
                    statusEl.html( 'Undefined error.' );
                }
            }
            else 
            {
                statusEl.html( 'Undefined error.' );
            }
            
            ithemes_bulkCurrentThreads--;
            ithemes_bulkFinishedThreads++;
            mainwp_itheme_clearlogs_start_next();
    }, 'json' );
}