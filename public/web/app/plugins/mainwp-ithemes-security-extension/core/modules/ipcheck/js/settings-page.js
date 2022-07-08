jQuery( document ).ready(function ( $ ) {
	var $container = jQuery( '#wpcontent' );
	
	$container.on( 'click', '#itsec-network-brute-force-reset_api_key', function( e ) {
		e.preventDefault();
		
                if ( ! mainwp_itsec_page.individualSite ) { 
                    mainwp_itsecSettingsPage.mainwpLoadSites( 'network-brute-force', 'reset-api-key' );
                    return;
                }
                
		if ( ! itsec_network_brute_force.original_button_text ) {
			itsec_network_brute_force.original_button_text = $( '#itsec-network-brute-force-reset_api_key' ).prop( 'value' );
		}
		
		$( '#itsec-network-brute-force-reset_api_key' )
			//.removeClass( 'button-primary' )
			.addClass( 'button-secondary' )
			.prop( 'value', itsec_network_brute_force.resetting_button_text )
			.prop( 'disabled', true );
		
		var data = {
			'method': 'reset-api-key'
		};
		
        var statusEl = mainwp_itsecSettingsPage.mainwpGetStatusElement( 'network-brute-force' );
        statusEl.html('');
                
		$( '#itsec-network-brute-force-reset-status' ).html( '' );
		
		mainwp_itsecSettingsPage.sendModuleAJAXRequest( 'network-brute-force', data, function( results ) {
                    
                        var mainwp_response = false;

                        if (results.hasOwnProperty('mainwp_response'))
                            mainwp_response = results.mainwp_response;

                        var error = false;
                        var message = '';
                        if (mainwp_response.message) {                                
                            message = mainwp_response.message;
                        } 

                        if ( mainwp_response ) {                           
                            if (mainwp_response.error) {  
                                error = true;
                                message = mainwp_response.error;                                                              
                            } else if (mainwp_response.result == 'success') {  
                                if (message == '')
                                    message = __('Successful'); 
                            } else if (mainwp_response.error_reset_api == 1) {  
                                error = true;
                                message = __( 'An unknown error prevented the API key from being reset properly. An unrecognized response was received. Please wait a few minutes and try again.');
                            }else {                              
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
                        
			$( '#itsec-network-brute-force-reset-status' ).html( '' );
                        
			$( '#itsec-network-brute-force-reset_api_key' )
					.removeClass( 'button-secondary' )
					//.addClass( 'button-primary' )
					.prop( 'value', itsec_network_brute_force.original_button_text )
					.prop( 'disabled', false );
                                
		} );
	} );
});
