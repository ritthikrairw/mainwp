<?php
if ( 'mainwp_kl_edit_group' == $_REQUEST['action'] ) {
	$action = 'edit'; } else if ( 'mainwp_kl_new_group' == $_REQUEST['action'] ) {
	$action = 'add'; } else {
		die( __( 'An error occured' ) ); }
	if ( 'edit' == $action ) {
		$group_id = intval( $_REQUEST['group_id'] );
		if ( $group_id ) {
			$group = $wpdb->get_row( sprintf( "SELECT * FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_group' ), $group_id ) ); }
	}
?>
<div id="kwl-setting-group-form" class="link-form kwl-popup-container">
    <form method="post" action="">
        <div class="option-list-wrapper">
			<?php
				$this->create_option_field( 'group_name', __( 'Link Group name' ), 'text', ( isset( $group ) ? $group->name : '' ) );
			?>
        </div>
                    
        <div class="option-submit">
			<?php if ( isset( $group ) ) :  ?>
				<input type="hidden" name="group_id" value="<?php echo $group->id ?>" />
			<?php endif ?>
			<input type="submit" class="button button-primary" value="<?php  echo ( 'add' == $action ) ? __( 'Add Link Group' ) : __( 'Save Link Group' ) ?>" />
			<img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" class="kwl-link-loading" />
        </div>
    </form>
</div>
