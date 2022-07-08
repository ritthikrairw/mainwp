<?php
if ( isset( $_GET['kwlSaveToSites'] ) && wp_verify_nonce( $_GET['kwlSaveToSites'], 'kwlSaveToSites' ) ) {
	?>
    <h3><?php echo __( 'Clear Do Not Link on Sites' ) ?></h3>
    <div id="kwl-dnl-clear-on-sites"><img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" /></div>
    <h3 id="kwl-dnl-apply-to-sites-title" class="kwl-hidden"><?php echo __( 'Apply Do Not Link to Sites' ) ?></h3>
    <div id="kwl-dnl-apply-to-sites"></div>
    <div id="kwl-dnl-apply-to-sites-ajax-message-zone"></div>
    
    <input type="hidden" name="kwl-dnl-presave-nonce" value="<?php echo wp_create_nonce( 'kwlPreSaveToSites' ) ?>">

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            keyword_links_do_not_link_pre_apply_to_sites();
        })
    </script>
    <?php
} else {
							   $do_not_links = $this->get_option( 'keyword_links_do_not_links', '' );
	$desc = 'Add one url per row.<br /><storng>For example:</strong><br />Full URL: http://mysite.com/this-is-my-full-url - Only blocks that URL<br />
    Partial URL: /about/ - Blocks every .../about/... page in your network<br />
    Site URL: http://mysite.com/ - Completely blocks that site';

	echo '<h3>' . __( 'Do Not Link' ) . '</h3>';

	$this->create_option_field( 'kwl_do_not_links', __( 'Do Not Link' ), 'textarea',  $do_not_links,  null, $desc );
	?>
    <input type="hidden" name="kwl-dnl-save-nonce" value="<?php echo wp_create_nonce( 'kwlSaveToSites' ) ?>">
    <?php
}
