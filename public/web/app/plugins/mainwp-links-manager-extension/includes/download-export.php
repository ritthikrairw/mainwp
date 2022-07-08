<?php
header( 'Content-Description: File Transfer' );
header( 'Content-Disposition: attachment; filename=' . $filename );
header( 'Content-Type: text; charset=' . get_option( 'blog_charset' ), true );
$export = array();
foreach ( $datas as $data ) {
	switch ( $data ) {
		case 'config':
			$export['config'] = $this->option;
						$export['links_posts'] = $this->keyword_links_specific_posts;
			break;
		case 'link':
			$export['link']['keyword'] = $wpdb->get_results( 'SELECT * FROM '.$this->table_name( 'keyword_links_link' ) );
			$export['link']['group'] = $wpdb->get_results( 'SELECT * FROM '.$this->table_name( 'keyword_links_group' ) );
			$export['link']['group_relation'] = $wpdb->get_results( 'SELECT * FROM '.$this->table_name( 'keyword_links_link_group' ) );
			break;
		case 'statistic':
			$export['statistic'] = $wpdb->get_results( sprintf( 'SELECT k.name, s.* FROM %s s JOIN %s k ON k.id=s.link_id', $this->table_name( 'keyword_links_statistic' ), $this->table_name( 'keyword_links_link' ) ) );
			break;
	}
}
if ( 'easy_reading' == $_REQUEST['format_export'] ) {
	$config = $link = $statistic = $links_posts = '';
	foreach ( $export as $field => $val ) {
		if ( 'config' == $field ) {
			$config .= "[configuration][begin]\n";
			$config .= json_encode( $val ) . "\n";
			$config .= "[configuration][end]\n";
		} else if ( 'links_posts' == $field ) {
			$links_posts .= "[links_posts][begin]\n";
			$links_posts .= json_encode( $val ) . "\n";
			$links_posts .= "[links_posts][end]\n";
		} else if ( 'link' == $field ) {
			$link .= "[link][begin]\n";
			$link .= "[keyword][begin]\n";
			$link .= "id,name,destination_url,cloak_path,keyword,link_target,link_rel,link_class,sites,groups,type\n";
			foreach ( $val['keyword'] as $item ) {
				$link .= $item->id.','.$item->name.','.$item->destination_url.','.$item->cloak_path.',"'.$item->keyword.'",'.$item->link_target.','.$item->link_rel.','.$item->link_class.','.$item->sites.','.$item->groups.','.$item->type."\n";
			}
			$link .= "[keyword][end]\n";
			$link .= "[link group][begin]\n";
			$link .= "id,name\n";
			foreach ( $val['group'] as $item ) {
				$link .= $item->id.','.$item->name."\n";
			}
			$link .= "[link group][end]\n";
			$link .= "[group_relation][begin]\n";
			$link .= "id,group_id,link_id\n";
			foreach ( $val['group_relation'] as $item ) {
				$link .= $item->id.','.$item->group_id.','.$item->link_id."\n";
			}
			$link .= "[group_relation][end]\n";
			$link .= "[link][end]\n";
		} else if ( 'statistic' == $field ) {
			$statistic .= "[statistic][begin]\n";
			$statistic .= json_encode( $val ) . "\n";
			$statistic .= "[statistic][end]\n";
		}
	}
	echo "[easy_reading_format]\n" . $link . $config . $links_posts . $statistic;
	exit;
}
echo json_encode( $export );
exit;
