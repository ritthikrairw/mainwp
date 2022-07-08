<?php
require_once( $this->plugin_dir . '/libs/pChart/class/pData.class.php' );
require_once( $this->plugin_dir . '/libs/pChart/class/pDraw.class.php' );
require_once( $this->plugin_dir . '/libs/pChart/class/pImage.class.php' );
$raw_clicks = $unique_clicks = $time_labels = array();
$now = current_time( 'timestamp' );
$today = mktime( 0, 0, 0, date( 'n', $now ), date( 'j', $now ), date( 'Y', $now ) );
$this_month = date( 'n', $now );
$this_year = date( 'Y', $now );
$query_table = sprintf( 'FROM `%s` s WHERE 1=1', $this->table_name( 'keyword_links_statistic' ) );
//click on statistic for a keyword in a day.
if ( isset( $_REQUEST['link_id'] ) && '' != $_REQUEST['link_id'] ) {
	$keyword_condition = sprintf( 'AND link_id = %s', $_REQUEST['link_id'] );
	$keyword_name = ' for "' . $wpdb->get_var( sprintf( 'SELECT `name` FROM `%s` s WHERE 1=1 AND `id` = %s', $this->table_name( 'keyword_links_link' ), $_REQUEST['link_id'] ) ) . '"';
} else {
	$keyword_condition = $keyword_name = '';
}
//if click on a day in a week
if ( isset( $_REQUEST['type'] ) && 'week' == $_REQUEST['type'] ) {
	$time_label = date( 'l, F j Y', $_REQUEST['time'] );
	$clock = intval( $this->get_option( '12_24_clock' ) );
	for ( $i = 0; $i < 24; $i++ ) {
		$query_date = sprintf( "AND s.`date` > '%s' AND s.`date` < '%s'", date( 'Y-m-d H:i:s', $_REQUEST['time'] + $i * 3600 ), date( 'Y-m-d H:i:s', $_REQUEST['time'] + (($i + 1) * 3600 -1) ) );
		if ( 24 == $clock ) {
			$time_labels[] = $i + 1 . 'h';
		} else {
			$temp = $i + 1;
			$time_labels[] = DATE( 'gA', STRTOTIME( "$temp:00" ) );
		}
		$raw_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(*) FROM `%s` s WHERE 1=1 %s %s', $this->table_name( 'keyword_links_statistic' ), $keyword_condition, $query_date ) );
		$unique_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) FROM `%s` s WHERE 1=1 %s %s', $this->table_name( 'keyword_links_statistic' ), $keyword_condition, $query_date ) );
	}
	//if click on a month
} elseif ( isset( $_REQUEST['type'] ) && 'month' == $_REQUEST['type'] ) {
	$time_label = date( 'F Y', $_REQUEST['s'] );
	//get all day in month
	$now = current_time( 'timestamp' );
	$_REQUEST['time'] = $_REQUEST['s'];
	$i = 1;
	for ( $s = $_REQUEST['s']; $s <= $_REQUEST['e'] && $s <= $now; $s += 86400 ) {
		$e = $s + 86400;
		$time_labels[] = $i++;
		$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
		$raw_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_date ) );
		$unique_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_date ) );
	}
	//if click on a year
} elseif ( isset( $_REQUEST['type'] ) && 'year' == $_REQUEST['type'] ) {
		$time_label = $_REQUEST['y'];
		//get all month in year
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
		$time_labels[] = $m;
		$e = mktime( 0, 0, 0, $em, 1, $ey ) - 1;
		$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
		$raw_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s', $query_table, $query_date ) );
		$unique_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s', $query_table, $query_date ) );
	}
		//if click on a referer
} elseif ( isset( $_REQUEST['type'] ) && 'referer' == $_REQUEST['type'] ) {
		$time_label = 'last 12 month';
		$keyword_name = ' for "' . $_REQUEST['url'] . '"';
	for ( $m = $this_month - 12 + 1; $m <= $this_month; $m++ ) {
		$mn = $m > 0 ? $m : $m + 12;
		$y = $m > 0 ? $this_year : $this_year -1;
		$s = mktime( 0, 0, 0, $mn, 1, $y );
		$e = mktime( 0, 0, 0, ($mn < 12 ? $mn + 1 : 1), 1, ( 12 == $mn ? $y + 1 : $y ) ) -1;
		$query_date = sprintf( "AND s.date > '%s' AND s.date < '%s'", date( 'Y-m-d H:i:s', $s ), date( 'Y-m-d H:i:s', $e ) );
		if ( 'None' == $_REQUEST['url'] ) {
			$query_ref = "AND s.referer=''"; } else {
			$query_ref = $wpdb->prepare( 'AND s.referer=%s', $_REQUEST['url'] ); }
			$time_labels[] = $mn;
			$raw_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(*) %s %s %s', $query_table, $query_ref, $query_date ) );
			$unique_clicks[] = $wpdb->get_var( sprintf( 'SELECT COUNT(DISTINCT ip) %s %s %s', $query_table, $query_ref, $query_date ) );
	}
}

		ini_set( 'memory_limit', '-1' ); // To fix bug limit memory
		$myData = new pData();
		$myData->addPoints( $raw_clicks,'Serie1' );
		$myData->setSerieDescription( 'Serie1','Raw Clicks' );
		$myData->setSerieOnAxis( 'Serie1',0 );
		$myData->addPoints( $unique_clicks,'Serie2' );
		$myData->setSerieDescription( 'Serie2','Unique Clicks' );
		$myData->setSerieOnAxis( 'Serie2',0 );
		$myData->addPoints( $time_labels,'Absissa' );
		$myData->setAbscissa( 'Absissa' );
		$myData->setAxisPosition( 0,AXIS_POSITION_LEFT );
		$myData->setAxisName( 0,'Clicks' );
		$myData->setAxisUnit( 0,'' );
		$myPicture = new pImage( 840,460,$myData );
		$Settings = array( 'R' => 245, 'G' => 245, 'B' => 245 );
		$myPicture->drawFilledRectangle( 0,0,840,460,$Settings );
		$myPicture->setFontProperties( array( 'FontName' => $this->plugin_dir . '/libs/pChart/fonts/Forgotte.ttf', 'FontSize' => 14 ) );
		$TextSettings = array(
		'Align' => TEXT_ALIGN_MIDDLEMIDDLE,
		'R' => 0,
		'G' => 0,
		'B' => 0,
		);
		$myPicture->drawText( 420,25,"Clicks statistic $keyword_name on " . $time_label,$TextSettings );
		$myPicture->setGraphArea( 50,50,815,420 );
		$myPicture->setFontProperties( array( 'R' => 0, 'G' => 0, 'B' => 0, 'FontName' => $this->plugin_dir . '/libs/pChart/fonts/verdana.ttf', 'FontSize' => 6.5 ) );
		$Settings = array(
		'Pos' => SCALE_POS_LEFTRIGHT,
		'Mode' => SCALE_MODE_ADDALL_START0,
		'LabelingMethod' => LABELING_ALL,
		'GridR' => 149,
		'GridG' => 216,
		'GridB' => 240,
		'GridAlpha' => 50,
		'TickR' => 0,
		'TickG' => 0,
		'TickB' => 0,
		'TickAlpha' => 50,
		'LabelRotation' => 0,
		'CycleBackground' => 1,
		'DrawXLines' => 1,
		'DrawSubTicks' => 1,
		'SubTickR' => 255,
		'SubTickG' => 0,
		'SubTickB' => 0,
		'SubTickAlpha' => 50,
		'DrawYLines' => ALL,
		'MinDivHeight' => 50,
		'ScaleSpacing' => 40,
		);
		$myPicture->drawScale( $Settings );
		$Config = '';
		$myPicture->drawLineChart( $Config );
		$myPicture->drawPlotChart(array(
			'DisplayValues' => true,
							'PlotBorder' => true,
							'BorderSize' => 2,
							'Surrounding' => -60,
							'BorderAlpha' => 80,
		));
		$Config = array(
		'FontR' => 0,
		'FontG' => 0,
		'FontB' => 0,
		'FontName' => $this->plugin_dir . '/libs/pChart/fonts/verdana.ttf',
		'FontSize' => 8,
		'Margin' => 6,
		'Alpha' => 30,
		'BoxSize' => 5,
		'Style' => LEGEND_BOX,
		'Mode' => LEGEND_VERTICAL,
		);
		$myPicture->drawLegend( 740,16,$Config );
		$myPicture->Render( $this->plugin_dir . '/graphs/stat.jpg' );
?>
<img src="<?php echo $this->plugin_url . 'graphs/stat.jpg?id=' . rand( 1,100000 ); ?>" />
<?php
$next = $previous = false;
//for day
if ( isset( $_REQUEST['previous'] ) && 0 != $_REQUEST['previous'] ) {
	$id_previous = $_REQUEST['previous'] . (($_REQUEST['link_id']) ? '-' . $_REQUEST['link_id'] : '');
	$previous = true;
}
if ( isset( $_REQUEST['next'] ) && 0 != $_REQUEST['next'] ) {
	$id_next = $_REQUEST['next'] . ( ($_REQUEST['link_id']) ? '-' . $_REQUEST['link_id'] : '');
	$next = true;
}
//for month
if ( isset( $_REQUEST['previous_month'] ) && 0 != $_REQUEST['previous_month'] ) {
	$id_previous = 'month-' . $_REQUEST['previous_month'];
	$previous = true;
}
if ( isset( $_REQUEST['next_month'] ) && 0 != $_REQUEST['next_month'] ) {
	$id_next = 'month-' . $_REQUEST['next_month'];
	$next = true;
}
//for day in month
if ( isset( $_REQUEST['previous_day_in_month'] ) && 0 != $_REQUEST['previous_day_in_month'] ) {
	$id_previous = 'stat-day-in-month-' . $_REQUEST['previous_day_in_month'];
	$previous = true;
}
if ( isset( $_REQUEST['next_day_in_month'] ) && 0 != $_REQUEST['next_day_in_month'] ) {
	$id_next = 'stat-day-in-month-' . $_REQUEST['next_day_in_month'];
	$next = true;
}
//for year
if ( isset( $_REQUEST['previous_year'] ) && 0 != $_REQUEST['previous_year'] ) {
	$id_previous = 'year-' . $_REQUEST['previous_year'];
	$previous = true;
}
if ( isset( $_REQUEST['next_year'] ) && 0 != $_REQUEST['next_year'] ) {
	$id_next = 'year-' . $_REQUEST['next_year'];
	$next = true;
}
//for month in year
if ( isset( $_REQUEST['previous_month_in_year'] ) && 0 != $_REQUEST['previous_month_in_year'] ) {
	$id_previous = 'stat-month-in-year-' . $_REQUEST['previous_month_in_year'];
	$previous = true;
}
if ( isset( $_REQUEST['next_month_in_year'] ) && 0 != $_REQUEST['next_month_in_year'] ) {
	$id_next = 'stat-month-in-year-' . $_REQUEST['next_month_in_year'];
	$next = true;
}
//for referer
if ( isset( $_REQUEST['previous_referer'] ) && -1 != $_REQUEST['previous_referer'] ) {
	$id_previous = 'referer-' . $_REQUEST['previous_referer'];
	$previous = true;
}
if ( isset( $_REQUEST['next_referer'] ) && -1 != $_REQUEST['next_referer'] ) {
	$id_next = 'referer-' . $_REQUEST['next_referer'];
	$next = true;
}
if ( true == $previous ) {
	?>
    <div style='float: left; margin-top:-5px;' class='next_previous_graph' time-value='<?php echo $id_previous; ?>'>
        Previous
    </div>
    <?php
}
if ( true == $next ) {
	?>
    <div style='float: right; margin-top:-5px;' class='next_previous_graph' time-value='<?php echo $id_next; ?>'>
        Next
    </div>
    <?php
}
