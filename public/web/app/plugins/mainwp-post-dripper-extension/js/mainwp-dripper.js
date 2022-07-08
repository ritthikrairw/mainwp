jQuery( document ).ready( function ( $ ) {

	// Cancel Drip
	$( '.drp_posts_list_delete_drip' ).on( 'click', function () {
		if ( ! confirm( __( 'Are you sure?' ) ) ) {
			return false;
		}
		var post_id = $( this ).attr( 'post-id' );
		$( 'tr#post-' + post_id ).html( '<td colspan="6"><i class="notched circle loading icon"></i> ' + __( 'Canceling. Please wait...' ) + '</td>' );
		$.ajax( {
			url: ajaxurl,
			data: {
				action: 'mainwp_dripper_delete_post',
				post_id: post_id,
			},
			success: function ( data ) {
				data = $.parseJSON( data );
				if ( data && data.success === true ) {
					$( 'tr#post-' + post_id ).fadeOut();
				} else {
					$( 'tr#post-' + post_id ).html( '<td colspan="6"><i class="red times icon"></i> ' + __( 'Undefined error occurred. Please, try again.' ) + '</td>' );
				}
			}, type: 'POST'
		} );
		return false;
	} );

	// Show drip details
	$( '.drp_posts_list_show_drip' ).on( 'click', function () {
		var post_id = $( this ).attr( 'post-id' );
		$( '#mainwp-drip-info-' + post_id ).modal( 'show' );
		return false;
	} );
} );
