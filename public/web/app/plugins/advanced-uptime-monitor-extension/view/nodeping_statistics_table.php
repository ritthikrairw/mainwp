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
	<th><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
	<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
	</tr>
</thead>
<tbody>
	<tr>
	<td><?php echo esc_html( $url->url_name ); ?></td>
	<td><a href="<?php echo $url->url_address; ?>" target="_blank"><?php echo $url->url_address; ?></a></td>
	<td><?php echo esc_html( $url->monitor_type ); ?></td>
	<td><?php echo ( 'inactive' == $url->enable || 0 == $url->enable ) ? 'inactive' : ( 1 == $url->state ? __( 'PASS', 'advanced-uptime-monitor-extension' ) : __( 'FAIL', 'advanced-uptime-monitor-extension' ) ); ?></td>
	</tr>
</tbody>
</table>
<?php
if ( ! empty( $stats ) ) {
	?>
	<table class="ui single line table">
	<thead>
		<tr>
			<th><?php _e( 'Time', 'advanced-uptime-monitor-extension' ); ?></th>
			<th><?php _e( 'Location', 'advanced-uptime-monitor-extension' ); ?></th>
			<th><?php _e( 'Run time', 'advanced-uptime-monitor-extension' ); ?></th>
			<th><?php _e( 'Response', 'advanced-uptime-monitor-extension' ); ?></th>
			<th><?php _e( 'Result', 'advanced-uptime-monitor-extension' ); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ( $stats as $event ) {
		$loc = ( $event->location != '' ) ? json_decode( $event->location, true ) : '';
		if ( is_array( $loc ) ) {
			$loc = array_values( $loc );
			$loc = $loc[0];
		} else {
			$loc = '';
		}
		?>
				<tr>
				<td><?php echo MainWP_AUM_Main::format_timestamp( $event->check_timestamp / 1000 ); ?></td>
				<td><?php echo esc_html( strtoupper( $loc ) ); ?></td>
				<td><?php echo intval( $event->runtime ); ?></td>
				<td><?php echo esc_html( $event->response ); ?></td>
				<td><?php echo $url->result ? __( 'PASS', 'advanced-uptime-monitor-extension' ) : __( 'DISABLED', 'advanced-uptime-monitor-extension' ); ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php
}
