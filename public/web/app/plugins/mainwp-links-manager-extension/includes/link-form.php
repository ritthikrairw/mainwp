<?php
if ( 'mainwp_kl_edit_link' == $_REQUEST['action'] ) {
	$action = 'edit'; } else if ( 'mainwp_kl_new_link' == $_REQUEST['action'] ) {
	$action = 'add'; } else {
		die( __( 'An error occured' ) ); }
	if ( 'edit' == $action ) {
		$link_id = intval( $_REQUEST['link_id'] );
		if ( $link_id ) {
			$link = $wpdb->get_row( sprintf( "SELECT * FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_link' ), $link_id ) );
			$link_type = $link->type;
		}
	}
	$link_type = empty( $link_type ) ? 1 : $link_type; // to set link type and fix link type if does not correct

	$groups = $wpdb->get_results( sprintf( 'SELECT * FROM `%s`', $this->table_name( 'keyword_links_group' ) ) );
	$option_groups = array(
	0 => __( 'None' ),
	);
	foreach ( (array) $groups as $group ) {
		if ( ! $group ) {
			continue; }
		$option_groups[ $group->id ] = $group->name;
	}
	if ( isset( $link ) ) {
		$current_group_id = $wpdb->get_var( sprintf( "SELECT `group_id` FROM `%s` WHERE `link_id`='%d'", $this->table_name( 'keyword_links_link_group' ), $link->id ) ); }

	global $current_user;
	$orderby = 'wp.url';

?>
<div id="kwl-edit-link-form" class="link-form">
    <form method="post" action="">
        <div class="option-list-wrapper">
			<?php
				$this->create_option_field( 'link_name', __( 'Link name' ), 'text', ( isset( $link ) ? $link->name : '' ) );
				$this->create_option_field( 'link_destination_url', __( 'Destination URL' ), 'text', ( isset( $link ) ? $link->destination_url : '' ) );
								$redirection_folder = $this->get_option( 'redirection_folder', '' );
			if ( ! empty( $redirection_folder ) ) {
				$redirection_folder = '/' . $redirection_folder; }
				$this->create_option_field( 'link_cloak_path', __( 'Cloak URL' ), 'text', ( isset( $link ) ? $link->cloak_path : '' ), '', '', 'Yourchildsite.com'. $redirection_folder . '/' );
				$this->create_option_field( 'link_keyword', __( 'Keywords' ), 'textarea', ( isset( $link ) ? $link->keyword : '' ), '', __( 'Use comma, semi colon or pipe to separate keywords' ) );
				$this->create_option_field( 'link_group', __( 'Link Group' ), 'select', ( isset( $current_group_id ) ? $current_group_id : ''), $option_groups );
				$this->create_option_field('link_nofollow', __( 'Link attribute' ), 'select', ( isset( $link ) ? ( $link->link_rel == 'nofollow' ? '1' : ( $link->link_rel == '-1' ? '-1' : '0' ) ) : '-1' ), array(
					'-1' => __( 'Use default' ),
					'0' => 'Follow',
					'1' => 'No Follow',
				));
				$this->create_option_field('link_newtab', __( 'Open link in new tab' ), 'select', ( isset( $link ) ? ( $link->link_target == '_blank' ? '1' : ( $link->link_target == '-1' ? '-1' : '0' ) ) : '-1' ), array(
					'-1' => __( 'Use default' ),
					'0' => 'No',
					'1' => 'Yes',
				));
				$this->create_option_field( 'link_class', __( 'Link class name' ), 'text', ( isset( $link ) ? $link->link_class : '' ) );

								$this->create_option_field( 'link_exact_match', __( 'Exact match' ), 'select', ( isset( $link ) ? ($link->exact_match ? '1' : '0') : '1'), array( 'No', 'Yes' ) );
								$this->create_option_field( 'link_case_sensitive', __( 'Case sensitive' ), 'select', ( isset( $link ) ? ($link->case_sensitive ? '1' : '0') : '1'), array( 'No', 'Yes' ) );

				?>                                          
                                <div class="option-list">                        
                                    <label>Choose sites for apply filter</label>   
                                    <div class="option-field  kwl_select_sites_box">
                                    <?php
									   $websitesid = $groupsid = $selected_websites = $selected_groups = array();
									if ( isset( $link ) ) {
										if ( $link->sites != '' ) {
											 $websitesid = explode( ';', $link->sites ); }
										if ( $link->groups != '' ) {
											 $groupsid = explode( ';', $link->groups ); }
										foreach ( $websitesid as $id ) {
											 $selected_websites[] = $id; }
										foreach ( $groupsid as $id ) {
											 $selected_groups[] = $id; }
									}
										$selected_websites = is_array( $selected_websites ) ? $selected_websites : array();
										$selected_groups = is_array( $selected_groups ) ? $selected_groups : array();
										do_action( 'mainwp_select_sites_box', __( 'Select Sites', 'mainwp' ), 'checkbox', true, true, 'mainwp_select_sites_box_right', '', $selected_websites, $selected_groups );
									?>
                                    </div>
                                    <br class="clearfix" />
                                </div>  
                                <?php
								if ( 2 == $link_type || 3 == $link_type ) {
									?>
									<div class="option-list">                                                
										<label><?php echo __( 'Applied Sites - Post IDs' ) ?></label>   
										<div class="option-field">
										<?php
										$list_posts = '';
										if ( $link->id && is_array( $this->keyword_links_specific_posts[ $link->id ] ) && count( $this->keyword_links_specific_posts[ $link->id ] ) > 0 ) {
											foreach ( $this->keyword_links_specific_posts[ $link->id ] as $wpid => $post_id ) {
												$website = apply_filters( 'mainwp-getsites', Links_Manager_Extension::get_file_name(), $this->childKey, $wpid );
												if ( $website && is_array( $website ) ) {
													$website = current( $website );
													if ( $website ) {
														$list_posts .= $website['name'] . ' - Post ID: ' . $post_id . '<br />';
													}
												}
											}
										}
										$list_posts = rtrim( $list_posts, ', ' );
										echo empty( $list_posts ) ? __( '(Not found)' ) : $list_posts;
										?>
										</div>
                                        </div>
								<?php
								}
								?>
                                <input type="hidden" name="link_type" value="<?php echo $link_type ?>"/>
                                <div style="display: none;" id="kwl-edit-link-ajax-message-zone"></div>                                
        </div>                  
        <div class="option-submit">                        
			<?php if ( isset( $link ) ) :
								$current_sites = $link->sites;
								$current_groups = $link->groups;
								?>
				<input type="hidden" name="link_id" value="<?php echo $link->id ?>" />
                                
			<?php endif ?>          
                        <input type="hidden" id="link_current_sites" value="<?php echo $current_sites ?>" />
                        <input type="hidden" id="link_current_groups" value="<?php echo $current_groups ?>" />
                        <br />
			<input type="submit" name = "submit_savelink" id = "submit_savelink" class="button button-primary" value="<?php  echo ( 'add' == $action ) ? __( 'Add Link' ) : __( 'Save Link' ) ?>" />
                        <input type="button" name = "close_btn" id = "close_btn" class="button button-primary hidden" value="<?php  echo __( 'Close' ); ?>" />
			<img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" class="kwl-link-loading" />
        </div>
    </form>
</div>
