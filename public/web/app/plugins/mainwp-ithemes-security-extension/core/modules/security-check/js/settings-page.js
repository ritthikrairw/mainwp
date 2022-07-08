jQuery( document ).ready( function ( $ ) {
	var $container = $( '#itsec-module-card-security-check' )

	$container.on( 'click', '#itsec-security-check-secure_site', function( e ) {
		e.preventDefault();

                if ( ! mainwp_itsec_page.individualSite ) { 
                    mainwp_itsecSettingsPage.mainwpLoadSites( 'security-check', 'secure-site' );
                    return;
                }
                
		$( '#itsec-security-check-secure_site' )
			//.removeClass( 'button-primary' )
			.addClass( 'button-secondary' )
			.attr( 'value', itsec_security_check_settings.securing_site )
			.prop( 'disabled', true );

		$( '#itsec-security-check-details-container' ).html( '' );

		var data = {
			'method': 'secure-site'
		};
                
                var statusEl = $( '#itsec_security_check_status' );
		statusEl.html('');
                
		mainwp_itsecSettingsPage.sendModuleAJAXRequest( 'security-check', data, function( results ) {
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
                                $( '#itsec-security-check-details-container' ).html( mainwp_response.response );                                
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
                        
			$( '#itsec-security-check-secure_site' )
				//.addClass( 'button-primary' )
				.removeClass( 'button-secondary' )
				.attr( 'value', itsec_security_check_settings.rerun_secure_site )
				.prop( 'disabled', false );
			
		} );
	} );

        $( document ).on( 'click', '#itsec-module-card-security-check .itsec-security-check-container-is-interactive :submit', function( e ) {
		e.preventDefault();

		var $button = $( this );
		var $container = $( this ).parents( '.itsec-security-check-container-is-interactive' );
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
				.data( 'original-value', $( this ).val() )
				.attr( 'value', $( this ).data( 'clicked-value' ) )
		}

		var ajaxFunction = mainwp_itsecSettingsPage.sendModuleAJAXRequest;

		ajaxFunction( 'security-check', data, function( results ) {
			$button
				.removeClass( 'button-secondary' )
				//.addClass( 'button-primary' )
				.prop( 'disabled', false );

			if ( $button.data( 'original-value' ) ) {
				$button
					.attr( 'value', $( this ).data( 'original-value' ) )
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
		} );
	} );       
} );
