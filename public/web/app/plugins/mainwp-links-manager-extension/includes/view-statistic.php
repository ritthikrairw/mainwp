<?php



if ( ! isset( $type ) ) {
	$type = 'link'; }
if ( 'link' == $type && isset( $_REQUEST['link_id'] ) ) {
	$link_id = intval( $_REQUEST['link_id'] );
	$name = $wpdb->get_var( sprintf( "SELECT name FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_link' ), $link_id ) );
	$query_table = sprintf( "FROM `%s` s WHERE `link_id`='%d'", $this->table_name( 'keyword_links_statistic' ), $link_id );
} else if ( 'group' == $type && isset( $_REQUEST['group_id'] ) ) {
	$group_id = intval( $_REQUEST['group_id'] );
	$name = $wpdb->get_var( sprintf( "SELECT name FROM `%s` WHERE `id`='%d'", $this->table_name( 'keyword_links_group' ), $group_id ) );
	$query_table = sprintf( "FROM `%s` s JOIN `%s` gr ON s.link_id=gr.link_id WHERE gr.group_id='%d'", $this->table_name( 'keyword_links_statistic' ), $this->table_name( 'keyword_links_link_group' ), $group_id );
} else {
	$query_table = sprintf( 'FROM `%s` s WHERE 1=1', $this->table_name( 'keyword_links_statistic' ) );
	$name = __( 'All Links' );
}
//get list of keywords:
$query_keyword = sprintf( 'FROM `%s` k WHERE 1=1', $this->table_name( 'keyword_links_link' ) );
$keyword = $wpdb->get_results( sprintf( 'SELECT * %s', $query_keyword ) );
$now = current_time( 'timestamp' );
$today = mktime( 0, 0, 0, date( 'n', $now ), date( 'j', $now ), date( 'Y', $now ) );
$this_month = date( 'n', $now );
$this_year = date( 'Y', $now );
$data = array();
// Last 7 days
$data['week'] = array();
for ( $s = $today; $s > ($today -(86400 * 7)); $s -= 86400 ) {
	$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $s + (86400 -1) ) );
	$raw_click = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_date ) );
	if ( $raw_click > 0 ) {
		$unique_click = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_date ) );
		$data['week'][] = array(
			'id' => date( 'l-F-j-Y', $s ),
			's' => $s,
			'label' => date( 'l, F j Y', $s ),
			'unique_click' => $unique_click,
			'raw_click' => $raw_click,
		);
	}
}
// Monthly
$data['month'] = array();
for ( $m = $this_month; $m > ($this_month -(12)); $m-- ) {
	$mn = $m > 0 ? $m : $m + 12;
	$y = $m > 0 ? $this_year : $this_year -1;
	$s = mktime( 0, 0, 0, $mn, 1, $y );
	$e = mktime( 0, 0, 0, ($mn < 12 ? $mn + 1 : 1), 1, ( 12 == $mn ? $y + 1 : $y ) ) -1;
	$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
	$raw_click = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_date ) );
	if ( $raw_click > 0 ) {
		$unique_click = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_date ) );
		$data['month'][] = array(
			's' => $s,
			'e' => $e,
			'label' => date( 'F Y', $s ),
			'unique_click' => $unique_click,
			'raw_click' => $raw_click,
		);
	}
}
// Yearly
$data['yearly'] = array();
$y = $this_year;
while ( $wpdb->get_var( sprintf( "SELECT COUNT(*) %s AND s.date > '%s' AND s.date < '%s'", $query_table, date( 'Y-m-d H:i:s', mktime( 0, 0, 0, 1, 1, $y ) ), date( 'Y-m-d H:i:s', mktime( 0, 0, 0, 1, 1, $y + 1 ) -1 ) ) ) > 0 ) {
	$s = mktime( 0, 0, 0, 1, 1, $y );
	$e = mktime( 0, 0, 0, 1, 1, $y + 1 ) -1;
	$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
	$raw_click = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_date ) );
	if ( $raw_click > 0 ) {
		$unique_click = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_date ) );
		$data['year'][] = array(
			'y' => $y,
			's' => $s,
			'e' => $e,
			'label' => date( 'Y', $s ),
			'unique_click' => $unique_click,
			'raw_click' => $raw_click,
		);
	}
	$y--;
}
// Referer
$ref_no = 0;
$data['referer'] = array();
$referers = $wpdb->get_col( sprintf( 'SELECT DISTINCT s.referer %s', $query_table ) );
$raw_click = $wpdb->get_var( sprintf( "SELECT COUNT(DISTINCT ip) %s AND s.referer=''", $query_table ) );
if ( $raw_click > 0 ) {
	$unique_click = $wpdb->get_var( sprintf( "SELECT COUNT(*) %s AND s.referer=''", $query_table ) );
	$data['referer'][] = array(
		'id' => $ref_no++,
		'label' => __( 'None' ),
		'unique_click' => $unique_click,
		'raw_click' => $raw_click,
	);
}
foreach ( (array) $referers as $ref ) {
	if ( ! $ref ) {
		continue; }
	$query_ref = $wpdb->prepare( 'AND s.referer=%s', $ref );
	$data['referer'][] = array(
		'id' => $ref_no++,
		'label' => $ref,
		'unique_click' => $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_ref ) ),
		'raw_click' => $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_ref ) ),
	);
}

$unique_id = substr( md5( microtime( 1 ) ), rand( 0, 16 ), 8 );
if ( ('link' == $type && isset( $_REQUEST['link_id'] ) ) or ('group' == $type && isset( $_REQUEST['group_id'] )) ) {
	include( $this->plugin_dir . '/includes/view-statistic-link-group.php' );
	exit;
}

?>

<div id="kwl-stat-data-box">            
<div id="stat-<?php echo $unique_id ?>" class="mainwp-kl-statistic">
    <div class="stat-navigation">
		<a href="#stat-week-<?php echo $unique_id ?>"><?php _e( 'Last 7 Days' ) ?></a> | 
		<a href="#stat-month-<?php echo $unique_id ?>"><?php _e( 'Monthly' ) ?></a> | 
		<a href="#stat-year-<?php echo $unique_id ?>"><?php _e( 'Yearly' ) ?></a> | 
		<a href="#stat-referer-<?php echo $unique_id ?>"><?php _e( 'Referer' ) ?></a>
    </div>    
	<div id="stat-week-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Last 7 Days Statistics for %s', $name ) ) ?></h3>
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Date' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				for ( $d = 0, $n = count( $data['week'] ); $d < $n; $d ++ ) {
					$day = $data['week'][ $d ];
					//first day
					if ( isset( $data['week'][ $d + 1 ]['s'] ) ) {
						$previous_day = $data['week'][ $d + 1 ]['s']; } else {
						$previous_day = 0; }
						if ( isset( $data['week'][ $d - 1 ]['s'] ) ) {
							$next_day = $data['week'][ $d - 1 ]['s']; } else {
							$next_day = 0; }
				?>
                <tr class="mainwpsub">
                    <td class="stat-label">
                        <div id="stat-week-<?php echo $day['id']; ?>" class="stat-toggle">
                        <img class="stat-week-<?php echo $day['id']; ?>" src="<?php echo $this->plugin_url . '/img/Plus.png'; ?>" /> 
                        <img class="stat-week-<?php echo $day['id']; ?>" src="<?php echo $this->plugin_url . '/img/Minus.png'; ?>" style="display: none;" /> 
                        </div>
                        <span class="labelday"><?php echo $day['label'] ?></span>
                        <a id="<?php echo $day['s']; ?>" 
                        class="thickbox thickboximg" 
                        href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_graph&amp;type=week&amp;time=<?php echo $day['s']; ?>&amp;previous=<?php echo $previous_day; ?>&amp;next=<?php echo $next_day; ?>&amp;height=480"><img src="<?php echo $this->plugin_url . '/img/chart.png'; ?>" /></a>
                    </td>
					<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
					<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
                <tr id="stat-week-<?php echo $day['id']; ?>-expanded" style="display: none;">
                    <td colspan="3" class="bgmainwpsub">
                    <table width="100%">
                    <tr class="headermainwp">
                        <td class="bgmainwpsubname">Name</td>
                        <td class="bgmainwpsubkey">Keyword</td>
                        <td class="bgmainwpsubraw">Raw clicks</td>
                        <td class="bgmainwpsubunique">Unique Clicks</td>
                    </tr>    
                    <?php
					$query_date = sprintf( "AND s.`date` > '%s' AND s.`date` < '%s'", date( 'Y-m-d H:i:s', $day['s'] ), date( 'Y-m-d H:i:s', $day['s'] + (86400 -1) ) );
					foreach ( $keyword as $k ) :
						$raw_clicks = $wpdb->get_var( sprintf( 'SELECT COUNT(*) FROM `%s` s WHERE link_id = %s %s', $this->table_name( 'keyword_links_statistic' ), $k->id, $query_date ) );
						$unique_clicks = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) FROM `%s` s WHERE link_id = %s %s', $this->table_name( 'keyword_links_statistic' ), $k->id, $query_date ) );
					?>
                        <tr class="contentmainwp">
                        <td class="bgmainwpsubname">
                            <div id="stat-week-sub-<?php echo $day['id']; ?>-<?php echo $k->id; ?>" class="stat-toggle">
                            <img class="stat-week-sub-<?php echo $day['id']; ?>-<?php echo $k->id; ?>" src="<?php echo $this->plugin_url . '/img/Plus.png'; ?>" />
                            <img class="stat-week-sub-<?php echo $day['id']; ?>-<?php echo $k->id; ?>" src="<?php echo $this->plugin_url . '/img/Minus.png'; ?>" style="display: none;" />
                            </div>
                            <span class="stattogglename"><?php echo $k->name; ?></span>
                            <a class="thickbox thickboximg" 
                            id="<?php echo $day['s']; ?>-<?php echo $k->id; ?>"
                            href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_graph&amp;type=week&amp;time=<?php echo $day['s']; ?>&amp;link_id=<?php echo $k->id; ?>&amp;previous=<?php echo $previous_day; ?>&amp;next=<?php echo $next_day; ?>&amp;height=480">
                            <img src="<?php echo $this->plugin_url . '/img/chart.png'; ?>" />
                            </a>
                        </td>
                        <td class="bgmainwpsubkey"><?php echo $k->keyword; ?></td>
                        <td class="bgmainwpsubraw"><?php echo $raw_clicks; ?></td>
                        <td class="bgmainwpsubunique"><?php echo $unique_clicks; ?></td>
                        </tr>
                        <?php
						$ajax_data = array( 'type' => 'week', 's' => $day['s'], 'link_id' => $k->id );
						$str = json_encode( $ajax_data );
						?>
                        <tr id="stat-week-sub-<?php echo $day['id']; ?>-<?php echo $k->id; ?>-expanded" style="display: none;" data='<?php echo $str; ?>'></tr>
                    <?php
					endforeach;
					?>
                    </table>
                    </td>
                </tr>
				<?php
				}
				?>
				<?php if ( count( $data['week'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
    
	<div id="stat-month-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Monthly Statistics for %s', $name ) ) ?></h3>
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Month' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				$month = 0;
				foreach ( (array) $data['month'] as $day ) :
					if ( isset( $data['month'][ $month + 1 ]['s'] ) ) {
						$previous_month = $data['month'][ $month + 1 ]['s']; } else {
						$previous_month = 0; }
						if ( isset( $data['month'][ $month - 1 ]['s'] ) ) {
							$next_month = $data['month'][ $month - 1 ]['s']; } else {
							$next_month = 0; }
							$month++;
				?>
                <tr class="mainwpsub">
                    <td class="stat-label">
                    <div id="stat-month-<?php echo $day['s']; ?>" class="stat-toggle">
                        <img class="stat-month-<?php echo $day['s']; ?>" src="<?php echo $this->plugin_url . '/img/Plus.png'; ?>" /> 
                        <img class="stat-month-<?php echo $day['s']; ?>" src="<?php echo $this->plugin_url . '/img/Minus.png'; ?>" style="display: none;" /> 
                    </div>
                    <span class="labelday"><?php echo $day['label'] ?></span>
                    <a class="thickbox thickboximg" 
                    id="month-<?php echo $day['s']; ?>"
                    href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_graph&amp;type=month&amp;s=<?php echo $day['s']; ?>&amp;e=<?php echo $day['e']; ?>&amp;previous_month=<?php echo $previous_month; ?>&amp;next_month=<?php echo $next_month; ?>&amp;height=480">
                    <img src="<?php echo $this->plugin_url . '/img/chart.png'; ?>" />
                    </a>
                    </td>
					<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
					<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
                <?php
				$ajax_data = array( 'type' => 'month', 's' => $day['s'], 'e' => $day['e'] );
				$str = json_encode( $ajax_data );
				?>
                <tr style="display: none;" id="stat-month-<?php echo $day['s']; ?>-expanded" data='<?php echo $str; ?>'></tr>
				<?php
				endforeach;
				if ( count( $data['month'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
    
	<div id="stat-year-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Yearly Statistics for %s', $name ) ) ?></h3>
        
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Year' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				$year = 0;
				if ( ! isset( $data['year'] ) ) {
					$data['year'] = array(); }
				foreach ( (array) $data['year'] as $day ) :
					if ( isset( $data['year'][ $year + 1 ]['s'] ) ) {
						$previous_year = $data['year'][ $year + 1 ]['s']; } else {
						$previous_year = 0; }
						if ( isset( $data['year'][ $year - 1 ]['s'] ) ) {
							$next_year = $data['year'][ $year - 1 ]['s']; } else {
							$next_year = 0; }
							$year++;
				?>
                <tr class="mainwpsub">
                    <td class="stat-label">
                        <div id="stat-year-<?php echo $day['s']; ?>" class="stat-toggle">
                            <img class="stat-year-<?php echo $day['s']; ?>" src="<?php echo $this->plugin_url . '/img/Plus.png'; ?>" /> 
                            <img class="stat-year-<?php echo $day['s']; ?>" src="<?php echo $this->plugin_url . '/img/Minus.png'; ?>" style="display: none;" /> 
                        </div>
                        <span class="labelday"><?php echo $day['label'] ?></span>
                        <a class="thickbox thickboximg" 
                        id="year-<?php echo $day['s']; ?>"
                        href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_graph&amp;type=year&amp;y=<?php echo $day['y']; ?>&amp;previous_year=<?php echo $previous_year; ?>&amp;next_year=<?php echo $next_year; ?>&amp;height=480">
                        <img src="<?php echo $this->plugin_url . '/img/chart.png'; ?>" />
                        </a>
                    </td>
					<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
					<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
                <?php
				$ajax_data = array( 'type' => 'year', 'y' => $day['y'] );
				$str = json_encode( $ajax_data );
				?>
                <tr style="display: none;" id="stat-year-<?php echo $day['s']; ?>-expanded" data='<?php echo $str; ?>'></tr>
				<?php
				endforeach
				?>
				<?php if ( count( $data['year'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
    
	<div id="stat-referer-<?php echo $unique_id ?>" class="stat-wrapper">
		<h3><?php _e( sprintf( 'Referer Statistics for %s', $name ) ) ?></h3>
        
        <div class="mainwp-table-list">
            <table width="100%">
                <tr>
					<th class="stat-label"><?php _e( 'Referer' ) ?></th>
					<th class="stat-raw"><?php _e( 'Raw Click' ) ?></th>
					<th class="stat-unique"><?php _e( 'Unique Click' ) ?></th>
                </tr>
				<?php
				$referer = 0;
				foreach ( (array) $data['referer'] as $day ) :
					if ( isset( $data['referer'][ $referer + 1 ]['id'] ) ) {
						$previous_referer = $data['referer'][ $referer + 1 ]['id']; } else {
						$previous_referer = -1; }
						if ( isset( $data['referer'][ $referer - 1 ]['id'] ) ) {
							$next_referer = $data['referer'][ $referer - 1 ]['id']; } else {
							$next_referer = -1; }
							$referer++;
				?>
                <tr class="mainwpsub">
                    <td class="stat-label">
                        <div id="stat-referer-<?php echo $day['id']; ?>" class="stat-toggle">
                            <img class="stat-referer-<?php echo $day['id']; ?>" src="<?php echo $this->plugin_url . '/img/Plus.png'; ?>" /> 
                            <img class="stat-referer-<?php echo $day['id']; ?>" src="<?php echo $this->plugin_url . '/img/Minus.png'; ?>" style="display: none;" /> 
                        </div>
                        <span class="labelday"><?php echo $day['label'] ?></span>
                        <a class="thickbox thickboximg" 
                        id="referer-<?php echo $day['id']; ?>"
                        href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_graph&amp;type=referer&amp;url=<?php echo $day['label']; ?>&amp;previous_referer=<?php echo $previous_referer; ?>&amp;next_referer=<?php echo $next_referer; ?>&amp;height=480">
                        <img src="<?php echo $this->plugin_url . '/img/chart.png'; ?>" />
                        </a>
                    </td>
					<td class="stat-raw"><?php echo $day['raw_click'] ?></td>
					<td class="stat-unique"><?php echo $day['unique_click'] ?></td>
                </tr>
                <?php
				$ajax_data = array( 'type' => 'referer', 'label' => $day['label'] );
				$str = json_encode( $ajax_data );
				?>
                <tr style="display: none;" id="stat-referer-<?php echo $day['id']; ?>-expanded" data='<?php echo $str; ?>'></tr>
                <?php
				endforeach;
				?>
				<?php if ( count( $data['referer'] ) == 0 ) :  ?>
                <tr>
					<td colspan="3"><p class="aligncenter"><?php _e( 'No data available' ) ?></p></td>
                </tr>
				<?php endif ?>
            </table>
        </div>
    </div>
      
	<?php /*
    <div class="stat-summary">
        <p>
            <strong><?php _e("Total Raw Click") ?>:</strong> <?php echo $wpdb->get_var(sprintf("SELECT COUNT(*) %s", $query_table)) ?><br />
            <strong><?php _e("Total Unique Click") ?>:</strong> <?php echo $wpdb->get_var(sprintf("SELECT COUNT(DISTINCT ip) %s", $query_table)) ?>
        </p>
    </div>
	*/ ?>
    
</div>

</div> 
<!--   kwl-stat-data-box  -->
        
    

    
<script type="text/javascript">
    jQuery(document).ready(function($){
		$('#stat-<?php echo $unique_id ?> .stat-wrapper').hide().eq(0).show().addClass("current");
		$('#stat-<?php echo $unique_id ?> .stat-navigation a').eq(0).addClass("current");
    });
</script>
