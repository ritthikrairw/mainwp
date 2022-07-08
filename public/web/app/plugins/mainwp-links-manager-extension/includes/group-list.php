<?php
Header( 'Cache-Control: no-cache' );
Header( 'Pragma: no-cache' );
?>
<table width="100%">
    <tr>
		<th class="group-name"><?php _e( 'Name' ) ?></th>
		<th class="group-link"><?php _e( 'Links' ) ?></th>
		<th class="group-option"><?php _e( 'Options' ) ?></th>
    </tr>
<?php
	$groups = $wpdb->get_results( sprintf( 'SELECT * FROM `%s`', $this->table_name( 'keyword_links_group' ) ) );
foreach ( (array) $groups as $group ) :
	if ( ! $group ) {
		continue; }
	$count = $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s` gr JOIN `%s` k ON k.id=gr.link_id WHERE gr.group_id='%d'", $this->table_name( 'keyword_links_link_group' ), $this->table_name( 'keyword_links_link' ), $group->id ) );
?>
<tr>	<td class="group-name"><?php echo $group->name ?></td>
	<td class="group-link"><?php echo $count ?></td>
	<td class="group-option">			<a href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_edit_group&amp;group_id=<?php echo $group->id ?>&amp;height=125;max-height=125" class="thickbox" title="<?php _e( 'Edit Link Group' ) ?>"><?php _e( 'Edit' ) ?></a> | 
			<a href="#" id="kwl-setting-group-delete" group-id="<?php echo $group->id; ?>"><?php _e( 'Delete' ) ?></a>                    
	</td></tr><?php
	endforeach;
?>
</table>
