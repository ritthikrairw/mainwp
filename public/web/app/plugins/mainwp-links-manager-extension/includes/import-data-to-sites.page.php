<?php
echo '<h3>' . __( 'Importing data to child sites' ) . '</h3><br />';
echo '<h4>' . __( 'Clearing links data on child sites' ) . '</h4>';
?>
<div id="kwl-import-resfresh-data-sites"><img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" /></div>
<div id="kwl-import-config-to-sites"></div>
<div id="kwl-import-links-to-sites"></div>
<br />
<h4 id="kwl-dnl-apply-to-sites-title" class="kwl-hidden"><?php echo __( 'Apply Do Not Link to Sites' ) ?></h4>
<div id="kwl-dnl-clear-on-sites" class="kwl-hidden"><img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" /></div>
<div id="kwl-dnl-apply-to-sites"></div>
    
<div class="kwl-hidden" id="kwl-import-data-ajax-message-zone"></div>
<input type="hidden" name="kwl-dnl-presave-nonce" value="<?php echo wp_create_nonce( 'kwlPreSaveToSites' ) ?>">
<script type="text/javascript">
    jQuery(document).ready(function($) {
        keyword_links_import_data_to_sites();
    })
    
    kwl_OnDoNotLinkApplyDone = function() {
        jQuery('#kwl-import-data-ajax-message-zone').html('<br /><div class="mainwp_info-box-yellow">' + __('Import data done.') + '</div>').show();
    }
</script>
<?php
