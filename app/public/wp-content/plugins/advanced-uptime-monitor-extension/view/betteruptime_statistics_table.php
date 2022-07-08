<?php
/**
 * NodePing Stats
 *
 * Renders Stats modal content when NodePing service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

?>

<table id="advanced-uptime-nodeping-stats-table" class="ui single line table">
<thead>
	<tr>
	<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
	<th><?php _e( 'URL', 'advanced-uptime-monitor-extension' ); ?></th>
	<th><?php _e( 'Interval', 'advanced-uptime-monitor-extension' ); ?></th>
	<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
	</tr>
</thead>
<tbody>
	<tr>
	<td><?php echo esc_html( $url->url_name ); ?></td>
	<td><a href="<?php echo $url->url_address; ?>" target="_blank"><?php echo $url->url_address; ?></a></td>
	<td><?php echo esc_html( $url->monitor_interval ); ?></td>
	<td><?php echo MainWP_AUM_BetterUptime_Controller::get_betteruptime_monitor_state( $url ); ?></td>
	</tr>
</tbody>
</table>
<?php
if ( ! empty( $stats ) ) {
	?>
	<table class="ui single line table">
	<thead>
		<tr>
			<th><?php _e( 'Cause', 'advanced-uptime-monitor-extension' ); ?></th>
			<th><?php _e( 'Started at', 'advanced-uptime-monitor-extension' ); ?></th>
			<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $stats as $event ) {
		?>
		<tr>
		<td><?php echo esc_html( $event->cause ); ?></td>
		<td><?php echo MainWP_AUM_Main::format_timestamp( $event->started_at ); ?></td>
		<td><?php echo MainWP_AUM_BetterUptime_Controller::get_betteruptime_event_state( $event ); ?></td></tr>
		<?php } ?>
		</tbody>
	</table>
	<?php
}
