<?php
	echo '<div class="mainwp_info-box">' . __( "Use this tool if you have a keyword that doesn't remove when using the standard link edit feature." ) . '</div>';
?> 
    <div  class="mainwp_info-box-yellow hidden" id="mwp-kwl-remove-links-info-box"></div>                
    <div  class="mainwp_error error" id="mwp-kwl-remove-links-error-box"></div>
<?php
	echo '<h3>' . __( 'Remove Keywords from Child Sites' ) . '</h3>';
	?>
    <div id="mainwp_kwl_remove_keywords_inside">
        <div id="kwl_select_sites_box" class="mainwp_config_box_right">
            <?php do_action( 'mainwp_select_sites_box', __( 'Select Site', 'mainwp' ), 'checkbox', true, true, 'mainwp_select_sites_box_right', '', array(), array() ); ?>
        </div> 
        <fieldset>
            <?php
			$desc = __( 'Separate Keywords with commas' );
			$this->create_option_field( 'kwl_remove_keywords', __( 'Enter Keywords' ), 'textarea',  $do_not_links,  null, $desc );
			?>
            <div class="option-list">
                <label for="kwl_remove_settings"><?php _e( 'Remove Keywords and Settings' ); ?></label>            
                <div class="option-field">
                    <label><input type="checkbox" value="1" id="kwl_remove_settings"></label><br>
                    <small><?php _e( 'Select this option will remove all Keywords and Settings data on selected child sites.' ); ?></small>
                </div>
            </div>
        </fieldset>
    </div>
<?php

