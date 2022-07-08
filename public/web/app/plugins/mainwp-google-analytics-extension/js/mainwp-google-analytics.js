jQuery( document ).ready( function () {

    // Trigger connect account
    jQuery( '#mainwp_ga_connect' ).on('click', function ( event ) {
      mainwp_ga_connect( this, event );
    } );

    // Trigger account removal
    jQuery( '.mainwp_ga_disconnect' ).on( 'click', function () {
      mainwp_ga_disconnect( this );
    } );

    // Reload page after saving settings
    jQuery( '#mainwp_ga_updatenow' ).on( 'click', function ( event ) {
      location.href=window.location.href + '&GAUpdate=1'
    } );

    // Trigger loding data for a specific site
    jQuery( '#mainwp_widget_ga_site' ).on( 'change', function ( event ) {
      mainwp_ga_getstats();
    } );

    // Toggle extra details
    jQuery( document ).on( 'click', '#mainwp-ga-show-extra-details', function( event ) {
      jQuery( '.mainwp-ga-extra-details-data-table' ).toggle();
      return false;
    } );
} );

// Remove account
mainwp_ga_disconnect = function ( pObj ) {
  jQuery( pObj ).attr( 'disabled', 'true' );
  var row = jQuery( pObj ).closest( 'tr' );
  var gaEntryId = jQuery( pObj ).attr( 'mainwp_ga_entry' );
  var data = {
    action:'mainwp_ga_disconnect',
    gaId: gaEntryId
  };

  jQuery( row ).html( '<td colspan="6"><i class="notched circle loading icon"></i> Removing account. Please wait...</td>');

  jQuery.post( ajaxurl, data, function ( response ) {
    response = jQuery.trim( response );
    
    setTimeout( function () {
  		window.location.reload();
  	}, 7000 );
  } );
};

// Connect account
mainwp_ga_connect = function ( pObj, event ) {
  jQuery( pObj ).attr( 'disabled', 'true' ); //Disable
  //var rid = jQuery( pObj ).closest( 'tr' ).attr( 'row-id' );
  var clientId = jQuery( '#client_id' ).val();
  var clientSecret = jQuery( '#client_secret' ).val();
  var accountName = jQuery( '#account_name' ).val();

  var data = {
    action:'mainwp_ga_connect',
    name: accountName,
    client_id: clientId,
    client_secret: clientSecret,
  };

  jQuery( '#mainwp-message-zone' ).html( '' ).hide();
  jQuery( '#mainwp-message-zone' ).removeClass( 'green red' );

  jQuery.post( ajaxurl, data, function ( response ) {
    response = jQuery.trim( response );

    var res = jQuery.parseJSON( response );

    if ( res['error'] ) {
      jQuery( '#mainwp-message-zone' ).html( '<i class="close icon"></i> ' + res['error'] ).show();
      jQuery( '#mainwp-message-zone' ).addClass( 'red' );
    } else if ( res['url'] ) {
      location.href = res['url'];
    }
    jQuery( pObj ).removeAttr( 'disabled' );
  });
};

// Load data for a specific site
mainwp_ga_getstats = function () {
  var mainwp_widgetGACurrentSite = jQuery('#mainwp_widget_ga_site').val();
  if ( mainwp_widgetGACurrentSite ) {
    var data = {
      action:'mainwp_ga_getstats',
      id:mainwp_widgetGACurrentSite
    };
    jQuery.post(ajaxurl, data, function ( response ) {
      response = jQuery.trim(response);
      jQuery('#mainwp-ga-data-content').html( response );
    });
  } else {
    jQuery( '#mainwp-ga-data-content' ).html( __('No data available. Connect your sites using the Settings submenu.') );
  }
};
