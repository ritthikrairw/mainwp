jQuery( document ).ready( function($) {

	// Initiate datapicker
	//jQuery( '.mainwp_extractor_datepicker' ).datepicker( { dateFormat:"yy-mm-dd" } );

	// Trigger the fetch process
	$( '#mainwp_extract_btn_preview_ouput' ).on( 'click', function () {
		mainwp_extract_preview_ouput();
		return false;
	});

	// Trigger the save template process
	$( '#ext_save_template_btn_save' ).on( 'click', function () {
		var errors = [];

		if ( $( '#ext_save_template_title' ).val().trim() == '' ) {
			errors.push( __( 'Template title can not be empty.' ) );
		}

		if ( $( '#mainwp_extract_format_output' ).val().trim() == '' ) {
			errors.push( __( 'Template format can not be empty.' ) );
		}

		if ( errors.length > 0 ) {
			confirm( errors.join( "\n" ) );
			return false;
		}
	});

	// Trigger the delete process
	$( '#mainwp-url-extreactor-delete-template' ).on( 'click', function () {
		if ( ! confirm( "Are you sure?" ) ) {
			return false;
		}

		$( '#ext_detele_selected_template' ).val( $( '#ext_urls_template_select' ).val() );

		$( '#mainwp-url-extractor-form' ).submit();

		return false;
	})

	// Load Select template
	jQuery( document ).on( 'click', '#ext_template_btn_use', function () {
		var data = {
			action: 'mainwp_extract_urls_load_template',
			tempId: $( '#ext_urls_template_select' ).val()
		}
		jQuery( '#mainwp_ext_url_error' ).hide();
		jQuery( '#ext_url_loading' ).show();
		jQuery.post(ajaxurl, data, function (response) {
			jQuery( '#ext_url_loading' ).hide();
			if (response && response['status'] == 'success') {
				if (response['format_output']) {
					$( '#mainwp_extract_format_output' ).val( response['format_output'] ); }
				if (response['separator']) {
					$( '#mainwp_extract_separator' ).val( response['separator'] ); }
			} else {
				jQuery( '#mainwp_ext_url_error' ).html( 'Error loading template.' );
				jQuery( '#mainwp_ext_url_error' ).show();
			}
		},'json')
	});

	// Export TXT output
	$( '.mainwp_extract_btn_export_txt' ).on( 'click', function () {
		if ( $( '#mainwp_extract_enable_export' ).val() == 1 ) {
			var extract_data = jQuery( '#mainwp_extract_preview_output' ).val();
			var blob = new Blob( [extract_data], { type: "text/plain;charset=utf-8"} );
			var file_name = "extract_urls_data.txt";
			saveAs( blob, file_name );
		}
		return false;
	});

	// Export CSV output
	$( '.mainwp_extract_btn_export_csv' ).on( 'click', function () {
		if ( $( '#mainwp_extract_enable_export' ).val() == 1 ) {
			var extract_data = jQuery( '#mainwp_extract_preview_output' ).val();
			var blob = new Blob( [extract_data], { type: "text/plain;charset=utf-8"} );
			var file_name = "extract_urls_data.csv";
			saveAs( blob, file_name );
		}
		return false;
	});

	// Insert token in the format field
	$( 'a.ext_url_add_token' ).on( 'click', function( e ) {
		var replace_text = jQuery( this ).html();
		var formatObj = $( '#mainwp_extract_format_output' );
		var str = formatObj.val();
		var pos = ext_getPos( formatObj[0] );
		str = str.substring( 0, pos ) + replace_text + str.substring( pos, str.length )
		formatObj.val( str );
		return false;
	});
});


function ext_getPos(obj) {
	var pos = 0;	// IE Support
	if ( document.selection ) {
		obj.focus();
		var range = document.selection.createRange();
		range.moveStart( 'character', -obj.value.length );
		pos = range.text.length;
	} // Firefox support
		else if (obj.selectionStart || obj.selectionStart == '0') {
		pos = obj.selectionStart;
	}
	return (pos);
}

// Generate output conntent
mainwp_extract_preview_ouput = function ( postId, userId ) {
	var errors = [];
	var selected_sites = [];
	var selected_groups = [];

	jQuery( '#mainwp-message-zone' ).html( '' ).hide();
	jQuery( '#mainwp-message-zone' ).removeClass( 'red green yellow' );

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
		if (selected_groups.length == 0) {
			errors.push( __( 'Please select at least one website or group.' ) );
		}
	}

	var post_type = "";
	var post_types = jQuery( '#mainwp_post_search_type' ).dropdown( "get value" );

	if ( post_types == null ) {
		errors.push( __( 'Please select at least one post type.' ) );
	} else {
		post_types = post_types.map( Number );
		post_type = post_types.reduce( function( a, b ) {
			return a+b
		}, 0);
	}

	var status = "";
	var statuses = jQuery( '#mainwp_post_search_status' ).dropdown( "get value" );

	if (statuses == null) {
		errors.push( __( 'Please select at least one post status.' ) );
	} else {
		status = statuses.join(',');
	}

	if ( jQuery( '#mainwp_extract_format_output' ).val().trim() == '' ) {
		errors.push( 'Format output can not be empty.' );
	}

	if ( errors.length > 0 ) {
		jQuery( '#mainwp-message-zone' ).html( errors.join( '<br />' ) ).show();
		jQuery( '#mainwp-message-zone' ).addClass( 'yellow' );
		return false;
	} else {
		jQuery( '#mainwp-message-zone' ).html( '' ).hide();
		jQuery( '#mainwp-message-zone' ).removeClass( 'yellow' );
	}

	var data = {
		action:'mainwp_extract_preview_ouput',
		keyword:jQuery( '#mainwp_post_search_by_keyword' ).val(),
		dtsstart:jQuery( '#mainwp_post_search_by_dtsstart' ).val(),
		dtsstop:jQuery( '#mainwp_post_search_by_dtsstop' ).val(),
		post_type:post_type,
		status:status,
		'groups[]':selected_groups,
		'sites[]':selected_sites,
		postId: (postId == undefined ? '' : postId),
		userId: (userId == undefined ? '' : userId),
		format_output: jQuery( '#mainwp_extract_format_output' ).val(),
		separator: jQuery( '#mainwp_extract_separator' ).val(),
	};

	jQuery( '#mainwp_extract_preview_output' ).val( '' );
	jQuery( '#mainwp_extract_enable_export' ).val( 0 );
	jQuery( '#mainwp-url-extractor-output-modal' ).modal( 'show' );
	jQuery( '#mainwp-url-extractor-output-modal' ).find( '.dimmer' ).addClass( 'active' );

	jQuery.post(ajaxurl, data, function (response) {
		jQuery( '#mainwp-url-extractor-output-modal' ).find( '.dimmer' ).removeClass( 'active' );
		if ( response ) {
			if ( response['error'] ) {
				jQuery( '#mainwp_extract_preview_output' ).html( response['error'] );
			} else if ( response['result'] != undefined ) {
				jQuery( '#mainwp_extract_preview_output' ).val( response['result'] );
				jQuery( '#mainwp_extract_enable_export' ).val( 1 );
			} else {
				jQuery( '#mainwp_extract_preview_output' ).html( __( "Undefined error occurred. Please try again." ) );
			}
		} else {
			jQuery( '#mainwp_extract_preview_output' ).html( __( "Undefined error occurred. Please try again." ) );
		}
	}, 'json');
};
