<?php
Header( 'Cache-Control: no-cache' );
Header( 'Pragma: no-cache' );
$now = current_time( 'timestamp' );
$today = mktime( 0, 0, 0, date( 'n', $now ), date( 'j', $now ), date( 'Y', $now ) );
$this_month = date( 'n', $now );
$this_year = date( 'Y', $now );
$query_table = sprintf( 'FROM `%s` s WHERE 1=1', $this->table_name( 'keyword_links_statistic' ) );
$raw_clicks = $unique_clicks = $time_labels = $times = array();
if ( isset( $_REQUEST['type'] ) && 'week' == $_REQUEST['type'] ) {
	$query_date = sprintf( "AND s.`date` > '%s' AND s.`date` < '%s'", date( 'Y-m-d H:i:s', $_REQUEST['s'] ), date( 'Y-m-d H:i:s', $_REQUEST['s'] + (86400 -1) ) );
	$clicks = $wpdb->get_results( "SELECT DATE_FORMAT(`date`,'%h:%i %p') `date`, `ip`" . sprintf( ' FROM `%s` s WHERE link_id = %s %s ORDER BY s.`date`', $this->table_name( 'keyword_links_statistic' ), $_REQUEST['link_id'], $query_date, $this->table_name( 'keyword_links_statistic' ) ) );
	?>
    <td colspan="4" style="padding: 0;">
        <table width="100%">
    <?php
	if ( sizeof( $clicks ) ) {
		$i = 0;
		foreach ( $clicks as $click ) :
			$i++;
			?>
            <tr class="contentmainwpsub">
                <td class="bgmainwpsub">&nbsp;
                </td>
                <td class="bgmainwpsubkey">
                <?php
				echo sprintf( 'Click %s: %s', $i, $click->date );
				?>
                </td>
                <td class="bgmainwpsubraw">
                <?php echo $click->ip; ?>
                </td>
                <td class="bgmainwpsubunique">&nbsp;
                </td>
            </tr>
        <?php
		endforeach;
	} else {
		?>
        <tr class="statsub">
            <td colspan="4" style="text-align: center;">No Data</td>
        </tr>
    <?php
	}
	?>
    </table>
    </td>
    <?php
	exit;
}
?>
<td colspan="3">
<table width="100%">
<tr class="headermainwp">
    <td class="bgmainwpsubname">Day</td>
    <td class="bgmainwpsubraw">Raw Clicks</td>
    <td class="bgmainwpsubunique">Unique Clicks</td>
</tr>
<?php
if ( isset( $_REQUEST['type'] ) && 'month' == $_REQUEST['type'] ) {
	$_REQUEST['time'] = $_REQUEST['s'];
	for ( $s = $_REQUEST['s']; $s <= $_REQUEST['e'] && $s <= $now; $s += 86400 ) {
		$e = $s + 86400;
		$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
		$time_labels[] = date( 'j F Y', $s );
		$times[] = $s;
		$raw_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_date ) );
		$unique_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_date ) );
	}
} elseif ( isset( $_REQUEST['type'] ) && 'year' == $_REQUEST['type'] ) {
	$max_month = ($_REQUEST['y'] == $this_year) ? $this_month : 12;
	for ( $m = 1; $m <= $max_month; $m ++ ) {
		$s = mktime( 0, 0, 0, $m, 1, $_REQUEST['y'] );
		if ( 12 == $m ) {
			$em = 1;
			$ey = $_REQUEST['y'] + 1;
		} else {
			$em = $m + 1;
			$ey = $_REQUEST['y'];
		}
		$e = mktime( 0, 0, 0, $em, 1, $ey ) - 1;
		$time_labels[] = date( 'F Y', $s );
		$times[] = array( 's' => $s, 'e' => $e );
		$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
		$raw_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_date ) );
		$unique_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_date ) );
	}
} elseif ( isset( $_REQUEST['type'] ) && 'referer' == $_REQUEST['type'] ) {
	for ( $m = $this_month; $m > ($this_month -(12)); $m-- ) {
		$mn = $m > 0 ? $m : $m + 12;
		$y = $m > 0 ? $this_year : $this_year -1;
		$s = mktime( 0, 0, 0, $mn, 1, $y );
		$e = mktime( 0, 0, 0, ($mn < 12 ? $mn + 1 : 1), 1, ( 12 == $mn ? $y + 1 : $y ) ) -1;
		$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
		if ( 'None' == $_REQUEST['label'] ) {
			$query_ref = "AND s.referer=''"; } else {
			$query_ref = $wpdb->prepare( 'AND s.referer=%s', $_REQUEST['label'] ); }
			$time_labels[] = date( 'F Y', $s );
			$raw_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s %s', $query_table, $query_ref, $query_date ) );
			$unique_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s %s', $query_table, $query_ref, $query_date ) );
	}
}
for ( $i = 0, $n = count( $time_labels ); $i < $n; $i++ ) {
	?>
    <tr class="contentmainwp">
    <td class="bgmainwpsubname">
        <span class="labelday">
        <?php echo $time_labels[ $i ]; ?>
        </span>
        &nbsp;
        <?php
		if ( isset( $_REQUEST['type'] ) && 'month' == $_REQUEST['type'] ) {
			if ( isset( $times[ $i - 1 ] ) ) {
				$previous_day = $times[ $i - 1 ]; } else {
				$previous_day = 0; }
				if ( isset( $times[ $i + 1 ] ) ) {
					$next_day = $times[ $i + 1 ]; } else {
					$next_day = 0; }
			?>
            <a id="stat-day-in-month-<?php echo $times[ $i ]; ?>" class="thickbox thickboximg" href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_graph&amp;type=week&amp;time=<?php echo $times[ $i ]; ?>&amp;previous_day_in_month=<?php echo $previous_day; ?>&amp;next_day_in_month=<?php echo $next_day; ?>&amp;height=480">
                <img src="<?php echo $this->plugin_url . '/img/chart.png'; ?>" />
            </a>
            <?php
		} elseif ( isset( $_REQUEST['type'] ) && 'year' == $_REQUEST['type'] ) {
			if ( isset( $times[ $i - 1 ] ) ) {
				$previous_month = $times[ $i - 1 ]['s']; } else {
				$previous_month = 0; }
				if ( isset( $times[ $i + 1 ] ) ) {
					$next_month = $times[ $i + 1 ]['s']; } else {
					$next_month = 0; }
			?>
            <a class="thickbox thickboximg" 
            id="stat-month-in-year-<?php echo $times[ $i ]['s']; ?>" href="<?php echo admin_url( 'admin-ajax.php' ) ?>?action=mainwp_kl_graph&amp;type=month&amp;s=<?php echo $times[ $i ]['s']; ?>&amp;e=<?php echo $times[ $i ]['e']; ?>&amp;previous_month_in_year=<?php echo $previous_month; ?>&amp;next_month_in_year=<?php echo $next_month; ?>&amp;height=480"> 
            <img src="<?php echo $this->plugin_url . '/img/chart.png'; ?>" />
            </a>
            <?php
		}
		?>
    </td>
    <td class="bgmainwpsubraw"><?php echo $raw_clicks[ $i ]; ?></td>
    <td class="bgmainwpsubunique"><?php echo $unique_clicks[ $i ]; ?></td>
    </tr>
    <?php
}
?>
</table>
</td>
