<?php
Header( 'Cache-Control: no-cache' );
Header( 'Pragma: no-cache' );
if ( isset( $_REQUEST['sort_type'] ) and '' != $_REQUEST['sort_type'] ) {
	$sort_type = $_REQUEST['sort_type']; } else {
	$sort_type = 'name'; }
	if ( isset( $_REQUEST['sort_direction'] ) and '' != $_REQUEST['sort_direction'] ) {
		$sort_direction = $_REQUEST['sort_direction']; } else {
		$sort_direction = 'asc'; }
?>
<table width="100%">
    <tr>
        <th id="name" class="link-sort manage-column column-title sorted <?php echo $sort_direction; ?>" scope="col"><a href="#"><span><?php _e( 'Name' ) ?></span><span class="sorting-indicator"></span></a></th>
        <th id="keyword" class="link-sort manage-column column-keyword sorted <?php echo $sort_direction; ?>" scope="col"><a href="#"><span><?php _e( 'Keyword' ) ?></span><span class="sorting-indicator"></span></a></th>
        <th id="group" class="link-sort manage-column column-group sorted <?php echo $sort_direction; ?>" scope="col"><a href="#"><span><?php _e( 'Link Group' ) ?></span><span class="sorting-indicator"></span></a></th>
        <th class="link-option"><?php _e( 'Options' ) ?></th>
        <th class="link-link"><?php _e( 'Link' ) ?></th>
    </tr>
    <?php
	//$links = $wpdb->get_results(sprintf("SELECT * FROM `%s`  order by %s %s", $this->table_name('keyword_links_link'), $sort_type, $sort_direction));
	$links = $wpdb->get_results(sprintf('SELECT `%s`.*, 
                                        `%s`.`name` as `group`, 
                                        `%s`.id as `group_id`, 
                                        COUNT(`%s`.`id`) as `clicks`
                                        FROM `%s`
                                        LEFT JOIN (
                                            `%s` INNER JOIN `%s` ON `%s`.`group_id` = `%s`.`id`
                                        ) ON `%s`.`id` = `%s`.`link_id` 
                                        LEFT JOIN `%s` ON `%s`.`id` = `%s`.`link_id`
                                       
                                        GROUP BY (`%s`.`id`)  
                                        ORDER BY `%s` %s', $this->table_name( 'keyword_links_link' ), $this->table_name( 'keyword_links_group' ), $this->table_name( 'keyword_links_group' ), $this->table_name( 'keyword_links_statistic' ), $this->table_name( 'keyword_links_link' ), $this->table_name( 'keyword_links_group' ), $this->table_name( 'keyword_links_link_group' ), $this->table_name( 'keyword_links_link_group' ), $this->table_name( 'keyword_links_group' ), $this->table_name( 'keyword_links_link' ), $this->table_name( 'keyword_links_link_group' ), $this->table_name( 'keyword_links_statistic' ), $this->table_name( 'keyword_links_link' ), $this->table_name( 'keyword_links_statistic' ), $this->table_name( 'keyword_links_link' ), $sort_type, $sort_direction));
	foreach ( (array) $links as $link ) :
		if ( ! $link ) {
			continue; }
		$query_table = sprintf( "FROM `%s` s WHERE `link_id`='%d'", $this->table_name( 'keyword_links_statistic' ), $link->id );
		$unique_click = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s', $query_table ) );
		?>
        <tr>
            <td class="link-name"><a href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_edit_link&amp;link_id=<?php echo $link->id ?>&amp;height=460" class="thickbox" title="<?php _e( 'Edit Link' ) ?>"><?php echo $link->name; ?></a></td>
            <td class="link-keyword"><?php echo $link->keyword; ?></td>
            <td class="link-group">
                <?php
				if ( $link->group != '' ) {
					?>
                    <a href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_edit_group&amp;group_id=<?php echo $link->group_id ?>&amp;height=125" class="thickbox" title="<?php _e( 'Edit Link Group' ) ?>">
                        <?php echo $link->group; ?>
                    </a>
                    <?php
				} else {
					echo 'None';
				}
				?>
            </td>
            <td class="link-option">
                <a href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_edit_link&amp;link_id=<?php echo $link->id ?>&amp;height=460" class="thickbox" title="<?php _e( 'Edit Link' ) ?>"><?php _e( 'Edit' ) ?></a> | 
                <a href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=keyword_links_delete_link_popup&amp;link_id=<?php echo $link->id ?>&amp;height=125" class="thickbox"><?php _e( 'Delete' ) ?></a>
<!--            <a href="#" id="kwl-settings-delete-link" link-id="<?php echo $link->id ?>"><?php _e( 'Delete' ) ?></a>-->
            </td>
            <td class="link-link"><a href="<?php echo $link->destination_url; ?>" target="_blank"><?php echo $link->destination_url; ?></a></td>
        </tr>
        <?php
	endforeach;
	?>
</table>
