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
		mode: "javascript",
		indentUnit: 4,
		indentWithTabs: true,
		enterMode: "keep",
		tabMode: "shift"
	};

	var mainwp_custom_dashboard_code_editor = null;

	if ( jQuery( '#mainwp-custom-dashboard-code-editor' ).length > 0 ) {
            mainwp_custom_dashboard_code_editor = CodeMirror.fromTextArea( document.getElementById( "mainwp-custom-dashboard-code-editor" ), atts );
            mainwp_custom_dashboard_code_editor.setSize( "100%", 600 );
	}

  jQuery( '#mainwp_cust_dashboard_form' ).submit( function( event ) {
        mainwp_custom_dashboard_snippet_save();
        return false;
  } );

mainwp_custom_dashboard_snippet_save = function() {
    var type = jQuery( 'input[name="mainwp_custom_dashboard_snippet_type"]' ).val();

    var data = {
      action:'mainwp_custom_dashboard_save_snippet',
      code: mainwp_custom_dashboard_code_editor.getValue(),
      type: type,
      nonce: jQuery( '#cust_dashboard_nonce' ).val()
    };

    var msg = __( 'Saving, please wait...' );

    jQuery( '#mainwp-cust-dash-message-zone' ).html( msg ).show();

    jQuery.post( ajaxurl, data, function ( response ) {
      if ( response && response['status'] == 'SUCCESS' ) {
        jQuery( '#mainwp-cust-dash-message-zone' ).html( __( 'Snippet saved successfully. The page will reload now.' ) );
        setTimeout( function () {
          jQuery( '#mainwp-cust-dash-message-zone' ).hide();
          var refresh_url = location.href;
          if ( refresh_url.search( 'donotexec' ) != -1 )
            refresh_url = 'admin.php?page=Extensions-Mainwp-Custom-Dashboard-Extension&tab=php'; // php tab
          location.href = refresh_url;
        }, 1500 );
      } else {
        jQuery( '#mainwp-cust-dash-message-zone' ).hide();
        jQuery( '#mainwp-cust-dash-error-zone' ).html( '<i class="ui close icon"></i>' + __( 'Undefined error. Please try again.' ) );
      }
    }, 'json');
  };
} );
