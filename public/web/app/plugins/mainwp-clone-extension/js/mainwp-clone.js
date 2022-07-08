jQuery( document ).ready( function () {
  jQuery( '#mainwp_clone_enabled' ).on( 'change', function() {
    var data = {
      action: 'mainwp_clone_update_clone_enabled',
      cloneEnabled: ( jQuery( '#mainwp_clone_enabled' ).is( ':checked' ) ? 1 : 0 ),
    };
    jQuery.post( ajaxurl, data, function ( response ) {
      window.location.reload();
    }, 'json' );
  } );

  // TR checkbox needs to switch allowed/disallowed class

  jQuery( '.mainwp-allow-disallow-clone' ).on( 'change', function () {
    var cloneItem = jQuery( this ).closest( '.mainwp-clone-item' );

    if ( jQuery( this ).is( ':checked' ) ) {
      cloneItem.removeClass( 'disallowed' );
      cloneItem.addClass( 'allowed' );
    } else {
      cloneItem.removeClass( 'allowed' );
      cloneItem.addClass( 'disallowed' );
    }

  } );

  jQuery( '#mainwp-clone-allow-all' ).on( 'click', function () {
    jQuery( '.mainwp-clone-item' ).find( "input:checkbox" ).each( function () {
      jQuery( this ).attr( 'checked', true );
      jQuery( this ).closest( '.mainwp-clone-item' ).removeClass( 'disallowed' );
      jQuery( this ).closest( '.mainwp-clone-item' ).addClass( 'allowed' );
    } );
  } );

  jQuery( '#mainwp-clone-disallow-all' ).on( 'click', function () {
    jQuery( '.mainwp-clone-item' ).find( "input:checkbox" ).each( function () {
      jQuery( this ).attr( 'checked', false );
      jQuery( this ).closest( '.mainwp-clone-item' ).removeClass( 'allowed' );
      jQuery( this ).closest( '.mainwp-clone-item' ).addClass( 'disallowed' );
    } );
  } );

  jQuery( '#save-clone-settings' ).on( 'click', function () {
    var disallowedSites = jQuery( '.disallowed.mainwp-clone-item' );
    var disallowedSiteIds = [ ];

    for ( var i = 0; i < disallowedSites.length; i++ ) {
      disallowedSiteIds.push( jQuery( disallowedSites[i] ).attr( 'id' ) );
    }

    var data = {
      action: 'mainwp_clone_update_allowed_sites',
      cloneEnabled: ( jQuery( '#mainwp_clone_enabled' ).is( ':checked' ) ? 1 : 0 ),
      websiteIds: disallowedSiteIds
    };

    jQuery.post( ajaxurl, data, function ( response ) {
      window.location.reload();
    }, 'json' );

    return false;
  } );
} );
