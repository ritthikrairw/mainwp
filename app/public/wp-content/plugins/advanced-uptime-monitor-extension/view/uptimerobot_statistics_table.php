<?php
/**
 * Uptime Robot Stats
 *
 * Renders Stats table content when Uptime Robot service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

?>
<table class="ui single line table">
<thead>
<tr>
<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'URL', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
</tr>
</thead>
<tbody>
<tr>
<td><?php echo $url->url_name; ?></td>
<td><a href="<?php echo $url->url_address; ?>" target="_blank"><?php echo $url->url_address; ?></a></td>
<td><?php echo MainWP_AUM_UptimeRobot_Controller::get_monitor_types( $url->monitor_type ); ?></td>
<td>
<?php
$last_status = $stats[0];
if ( $last_status->monitor_type == '-1' ) {
	$type = $last_status->type;
	echo ucfirst( $event_statuses[ $type ] );
} else {
	$type = $last_status->monitor_type;
	switch ( $type ) {
		case '0':
			echo __( 'Paused', 'advanced-uptime-monitor-extension' );
			break;
		case '1':
			echo __( 'Started', 'advanced-uptime-monitor-extension' );
			break;
	}
}
?>
</td>
</tr>
</tbody>
</table>
<?php
$api_timezone = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'api_timezone', false );
$offset_time  = 0;
if ( is_array( $api_timezone ) && isset( $api_timezone['offset_time'] ) ) {
	$offset_time = $api_timezone['offset_time'];
	$offset_time = $offset_time * 60 * 60;
}
if ( ! empty( $stats ) ) {
	?>
<table class="ui single line table">
<thead>
<tr>
<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'Details', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'Date / Time', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'Duration', 'advanced-uptime-monitor-extension' ); ?></th>
</tr>
</thead>
<tbody>
	<?php
	foreach ( $stats as $event ) {
		$type   = $event->type;
		$status = ucfirst( $event_statuses[ $type ] );
		?>
<tr>
<td class="<?php echo strtolower( $status ); ?>">
		<?php
		switch ( $status ) {
			case 'Started':
				echo __( 'Started', 'advanced-uptime-monitor-extension' );
				break;
			case 'Up':
				echo __( 'Up', 'advanced-uptime-monitor-extension' );
				break;
			case 'Paused':
				echo __( 'Paused', 'advanced-uptime-monitor-extension' );
				break;
			case 'Down':
				echo __( 'Down', 'advanced-uptime-monitor-extension' );
				break;
		}
		?>
</td>
<td>
		<?php
		switch ( $status ) {
			case 'Started':
				echo __( 'The monitor is started manually', 'advanced-uptime-monitor-extension' );
				break;
			case 'Up':
				echo __( 'Successful response received.', 'advanced-uptime-monitor-extension' );
				break;
			case 'Paused':
				echo __( 'The monitor is paused manually', 'advanced-uptime-monitor-extension' );
				break;
			case 'Down':
				if ( $url->monitor_type == '2' ) {
					echo __( 'The keyword exists.', 'advanced-uptime-monitor-extension' );
				} else {
					echo __( 'No Response From The Website.', 'advanced-uptime-monitor-extension' );
				}
				break;
		}
		?>
</td>
<td>
		<?php
		$datetime = strtotime( $event->event_datetime_gmt ) + $offset_time;
		$datetime = MainWP_AUM_Main::format_timestamp( $datetime );
		echo $datetime;
		?>
</td>
<td>
		<?php
		$duration = $event->duration;
		$hrs      = floor( $duration / 3600 );
		$mins     = floor( ( $duration - $hrs * 3600 ) / 60 );
		echo $hrs . ' hrs, ' . $mins . ' mins';
		?>
</td>
</tr>
	<?php } ?>
</tbody>
</table>
	<?php
}
