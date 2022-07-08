<div class="option-list-wrapper">	<input type="hidden" name="kwl_metabox_nonce" id="kwl_metabox_nonce" value="<?php echo wp_create_nonce( $this->plugin_handle . '-metabox' ) ?>" />
	<?php
	$link_name = $link_class = $post_keyword = $link_destination_url = $link_cloak = '';
	$link_nofollow = $link_newtab = '-1';
	$exact_match = $case_sensitive = 1;
	$specific_link_id = get_post_meta( $post->ID, '_mainwp_kwl_meta_specific_link_id', true );
	// $specific_link_id is link in keyword table too, but create in "mainwp Links Manager Options"
	// at this place we only edit/create links and it called post_link  
	if ( $specific_link_id ) {
		$link = $wpdb->get_row( sprintf( "SELECT * FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_link' ), $specific_link_id ) );
		if ( ! empty( $link ) ) {
			$link_name = $link->name;
			$link_nofollow = ($link->link_rel == 'nofollow' ? '1' : ( $link->link_rel == '-1' ? '-1' : '0' ) );
			$link_newtab = ( $link->link_target == '_blank' ? '1' : ( $link->link_target == '-1' ? '-1' : '0' ) );
			$link->link_target;
			$link_class = $link->link_class;
			$post_keyword = $link->keyword;
			$link_destination_url = $link->destination_url;
			$link_cloak = $link->cloak_path;
			$exact_match = $link->exact_match;
			$case_sensitive = $link->case_sensitive;
		}
}
	$all_keywords = $this->get_all_keywords_with_link_to_on_this_post();
	$disable_add_links = $this->get_option( 'disable_add_links_automatically', array() );
	$disable_linking = $this->get_option( 'disable_linking_automatically', array() );
	$this->create_option_field( 'mainwp_kl_disable', __( 'Disable add links automatically' ), 'select', in_array( $post->post_name, (array) $disable_add_links[ $post->post_type ] ) /* get_post_meta($post->ID, '_mainwp_kl_disable', true) */, array( 'No', 'Yes' ) );
	$this->create_option_field( 'mainwp_kl_disable_post_link', __( 'Disable linking to this post' ), 'select', in_array( $post->post_name, (array) $disable_linking[ $post->post_type ] ) /* get_post_meta($post->ID, '_mainwp_kl_disable_post_link', true) */, array( 'No', 'Yes' ) );
	$this->create_option_field( 'mainwp_kl_link_nofollow', __( 'Link attribute' ), 'select', $link_nofollow, array( '-1' => __( 'Use default' ), '0' => 'Follow', '1' => 'No Follow' ) );
	$this->create_option_field( 'mainwp_kl_link_newtab', __( 'Open link in new tab' ), 'select', $link_newtab, array( '-1' => __( 'Use default' ), '0' => 'No', '1' => 'Yes' ) );
	$this->create_option_field( 'mainwp_kl_link_name', __( 'Link name' ), 'text', $link_name );
	$this->create_option_field( 'mainwp_kl_link_destination_url', __( 'Destination URL' ), 'text', $link_destination_url );
	$redirection_folder = $this->get_option( 'redirection_folder', '' );      
	if ( $redirection_folder != '' ) {
		$redirection_folder = '/' . $redirection_folder; }
	$this->create_option_field( 'link_cloak_path', __( 'Cloak URL' ), 'text', $link_cloak, '', '', get_option( 'home' ) . $redirection_folder . '/ ' );
	$this->create_option_field( 'mainwp_kl_post_keyword', __( 'Keywords' ), 'textarea', $post_keyword, '', __( 'Use comma, semi colon or pipe to separate keywords' ) );
	$this->create_option_field( 'mainwp_kl_link_class', __( 'Link class name' ), 'text', $link_class );
	if ( is_array( $all_keywords ) ) {
		$not_allows = get_post_meta( $post->ID, 'mainwp_kl_not_allowed_keywords_on_this_post' );
		if ( is_array( $not_allows ) && count( $not_allows ) > 0 ) {
			$not_allows = $not_allows[0]; }
		//print_r($not_allows);
		if ( empty( $not_allows ) ) {
			$not_allows = array(); }
		$j = 0;
		for ( $i = 0; $i < count( $all_keywords ); $i++ ) {
			if ( ! in_array( $all_keywords[ $i ], $not_allows ) ) {
				if ( isset( $all_keywords[ $i ]['keyword'] ) && $all_keywords[ $i ]['keyword'] ) {
					$fields[ $j ]['value'] = $j;
					$fields[ $j ]['label'] = $all_keywords[ $i ]['keyword'];
					$fields[ $j ]['title'] = $all_keywords[ $i ]['link'];
					$fields[ $j ]['html_assigned'] = '<input type="hidden" name="allowed_keywords[]" value="' . $all_keywords[ $i ]['keyword'] . '"/>' .
							'<input type="hidden" name="allowed_links[]" value="' . $all_keywords[ $i ]['link'] . '"/>';
					$j++;
				}
}
}
		$params = array();
		$params['name'] = 'check_allowed_keywords';
		$params['label'] = __( 'Allowed keywords' );
		$params['type'] = 'checkbox';
		$params['default'] = null;
		$params['fields'] = $fields;
		$params['description'] = __( 'Check keywords to not allow for this post.' );
		$params['before'] = null;
		$params['after'] = null;
		$params['field_class'] = 'kl_al_ky';

		$this->create_extra_option_field( $params );

		$not_allowed_fields = array();
		$j = 0;
		for ( $i = 0; $i < count( $not_allows ); $i++ ) {
			if ( isset( $not_allows[ $i ]['keyword'] ) && $not_allows[ $i ]['keyword'] ) {
				$not_allowed_fields[ $j ]['value'] = $j;
				$not_allowed_fields[ $j ]['label'] = $not_allows[ $i ]['keyword'];
				$not_allowed_fields[ $j ]['title'] = $not_allows[ $i ]['link'];
				$not_allowed_fields[ $j ]['html_assigned'] = '<input type="hidden" name="not_allowed_keywords[]" value="' . $not_allows[ $i ]['keyword'] . '"/>' .
						'<input type="hidden" name="not_allowed_links[]" value="' . $not_allows[ $i ]['link'] . '"/>';

				$j++;
			}
}
		$params = array();
		$params['name'] = 'check_not_allowed_keywords';
		$params['label'] = __( 'Not allowed keywords' );
		$params['type'] = 'checkbox';
		$params['default'] = 'force_check_all';
		$params['fields'] = $not_allowed_fields;
		$params['description'] = __( 'Uncheck keywords to allow for this post' );
		$params['before'] = null;
		$params['after'] = null;
		$params['field_class'] = 'kl_al_ky';
		$this->create_extra_option_field( $params );
		$this->create_option_field( 'mainwp_kl_exact_match', __( 'Exact match' ), 'select', $exact_match, array( 'No', 'Yes' ) );
		$this->create_option_field( 'mainwp_kl_case_sensitive', __( 'Case sensitive' ), 'select', $case_sensitive, array( 'No', 'Yes' ) );
	}
	?>
    <input type="hidden" name="_mainwp_kwl_meta_specific_link_id" value="<?php echo intval( $specific_link_id ) ?>" />
</div>
