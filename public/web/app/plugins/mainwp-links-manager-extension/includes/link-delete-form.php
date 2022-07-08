<div id="kwl-setting-link-delete-form" class="link-form kwl-popup-container">        <div class="kwl-ajax-message-zone mainwp_info-box-yellow" style="display: none;"></div>    <?php     
	$link_id = isset( $_REQUEST['link_id'] ) ? $_REQUEST['link_id'] : 0;     
	if ( ! $link_id ) {
		echo '<h4>Wrong link id</h4>'; } else {
	?>    
    <form method="post" action="">        
        <h4>Would you like to keep the existing links on the child sites?</h4>        
        <div class="option-list-wrapper">            
            <?php $this->create_option_field( 'kwl_delete_link_child_site', __( '' ), 'radio', 1, array( 'Yes', 'No' ) ); ?>        
        </div>        
        <div class="option-submit">            
            <input type="submit" id="kwl-settings-delete-link" class="button button-primary" value="Delete Link" link-id="<?php echo $link_id; ?>"/>            
            <img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" class="kwl-link-loading" />        
        </div>    
    </form>
    <?php } ?>
</div>
