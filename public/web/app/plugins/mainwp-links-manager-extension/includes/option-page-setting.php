<?php

$post_types = array(
	'bulkpost' => 'Post',
	'bulkpage' => 'Page',
);
$args = array(
	'hide_empty' => 0,
	'orderby' => 'name',
	'order' => 'ASC',
);
$categories = get_categories( $args );
$cats = array();
foreach ( $categories as $category ) {
	$cats[ $category->term_id ] = $category->name;
}
$get_taxonomies = get_taxonomies(
	array(
		'show_in_nav_menus' => true,
	),
	'objects'
);
$taxonomies = array();
foreach ( $get_taxonomies as $taxonomy ) {
	$taxonomies[ $taxonomy->name ] = $taxonomy->label;
}
?>
<h3><?php _e( 'General Settings' ) ?></h3>
<?php
$this->create_option_field( 'replace_max', __( 'Maximum number of replacements per article' ), 'text', '-1', '', __( 'Use -1 for unlimited' ) );
$this->create_option_field( 'replace_max_keyword', __( 'Maximum times to replace the same keyword per article' ), 'text', '-1', '', __( 'Use -1 for unlimited' ) );
$this->create_option_field( 'default_link_nofollow', __( 'Default link attribute' ), 'select', '0', array( 'Follow', 'No follow' ) );
$this->create_option_field( 'default_link_newtab', __( 'Open link in new tab by default?' ), 'select', '0', array( 'No', 'Yes' ) );
$this->create_option_field( 'replace_keyword_in_h_tag', __( 'Replace keyword in H tag?' ), 'select', '0', array( 'No', 'Yes' ) );
$this->create_option_field( 'default_link_class', __( 'Default link class name' ), 'text', '' );
$this->create_option_field( 'enable_post_type', __( 'Post type' ), 'checkbox', array( 'bulkpost' ), $post_types );
$this->create_option_field( 'redirection_folder', __( 'Redirection Folder' ), 'text_help', '','' );
?>
<div class="option-list">
    <label for="clear_all_data">Clear statistic</label>
    <div class="option-field">
		<button id="clear_all_data" class="button" name="clear_all_data" value="<?php echo wp_create_nonce( $this->plugin_handle.'-clear' ) ?>"><?php _e( 'Clear All Data' ) ?></button>
                <img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" class="kwl-link-loading" />
    </div>
</div>

<h3><?php _e( 'Internal Links' ) ?></h3>
<div class="mainwp_info-box"><?php _e( 'Notice: Internal Links are only within the same site not through your Network.' ); ?></div>
<p>Automatically create internal links to selected post types.</p>
<?php
$this->create_option_field( 'post_match_title', __( 'Match post title' ), 'select', '1', array( 'No', 'Yes' ) );
$this->create_option_field( 'enable_post_type_link', __( 'Enable link to post type' ), 'checkbox', array( 'bulkpost' ), $post_types, __( 'Selected post type will be linked automatically based on the post keywords' ) );

?>

<script type="text/javascript">
        var kwlDoClearNonce = "<?php echo wp_create_nonce( 'kwlDoClear' );?>";
</script>
