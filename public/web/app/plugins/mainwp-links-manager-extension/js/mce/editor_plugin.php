<?php 
/**
 * mainwp Links Manager plugin
 */
require_once('../../../../../wp-load.php');
$expires_offset = 31536000;
header('Content-Type: application/x-javascript; charset=UTF-8');
header('Vary: Accept-Encoding'); // Handle proxies
header('Expires: ' . gmdate( "D, d M Y H:i:s", time() + $expires_offset ) . ' GMT');
header("Cache-Control: public, max-age=$expires_offset");
?>
(function() {
	var DOM = tinymce.DOM;
	tinymce.create('tinymce.plugins.mainwpkeywordlink', {
		mceTout : 0,
		init: function (ed, url) {
			ed.onInit.add(function(ed){
				//ed.dom.loadCSS(url + '/css/content.css');
			});
			ed.onSaveContent.add(function(ed, o) {
				return o;
			});
		},
		createControl: function(n, cm) {
	        switch (n) {
	            case 'mainwpkeywordlink_select':
	                var select = cm.createListBox('mainwpkeywordlink_select', {
	                     title : 'Select Link',
	                     onselect : function(v) {
	                         if ( typeof(v) == 'object' )
	                         {
	                         	var text = tinyMCE.activeEditor.selection.getContent({format: 'raw'});
	                         	tinyMCE.activeEditor.selection.setNode(tinyMCE.activeEditor.dom.create('a', v, text));
	                         	var clean_a = tinyMCE.activeEditor.getContent({format: 'raw'});	                         	
								clean_a = clean_a.replace(/<a[^>]*><\/a>/gim, '');
	                         	clean_a = clean_a.replace(/<a[^>]*>(<a[^>]*>.*?<\/a>)<\/a>/gim, '$1');							
	                         	tinyMCE.activeEditor.setContent(clean_a, {format: 'raw'});
	                         };
	                     }
	                });
	                // Add links to the list box
	                <?php
	                	 Links_Manager_Extension::get_instance()->load_mce_select(); 
	                ?>
	                return select;
	        }
        return null;
    }
	});
	tinymce.PluginManager.add('mainwpkeywordlink', tinymce.plugins.mainwpkeywordlink);
})();
<?php
?>