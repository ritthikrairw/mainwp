<?php
/**
 * NodePing Recent Events
 *
 * Renders Recent Events content when NodePing service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

if ( ! empty( $stats ) && ! empty( $urls_info ) ) {
	?>
	<table id="mainwp-aum-recent-events-table" class="ui single line table">
		<thead>
			<tr>
				<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Time', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing"><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing"><?php _e( 'Location', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing"><?php _e( 'Run time', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing"><?php _e( 'Response', 'advanced-uptime-monitor-extension' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $stats as $event ) :
			if ( ! empty( $urls_info[ $event->url_id ]['url_name'] ) ) {
				$loc = ( $event->location != '' ) ? json_decode( $event->location, true ) : '';
				if ( is_array( $loc ) ) {
					$loc = array_values( $loc );
					$loc = $loc[0];
				} else {
					$loc = '';
				}
				?>
				<tr>
					<td><?php echo esc_html( $urls_info[ $event->url_id ]['url_name'] ); ?></td>
					<td><?php echo MainWP_AUM_Main::format_timestamp( $event->check_timestamp / 1000 ); ?></td>
					<td class="center aligned"><?php echo $event->result ? __( 'PASS', 'advanced-uptime-monitor-extension' ) : __( 'FAIL', 'advanced-uptime-monitor-extension' ); ?></td>
					<td class="center aligned"><?php echo esc_html( strtoupper( $loc ) ); ?></td>
					<td class="center aligned"><?php echo intval( $event->runtime ); ?></td>
					<td class="center aligned"><?php echo esc_html( $event->response ); ?></td>
				</tr>
				<?php
			}
			?>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Time', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Location', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Run time', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Response', 'advanced-uptime-monitor-extension' ); ?></th>
			</tr>
		</tfoot>
	</table>
	<script type="text/javascript">
	jQuery( '#mainwp-aum-recent-events-table' ).DataTable( {
		"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
		"order": [ [ 1, "asc" ] ],
		"stateSave":  true,
		"language": { "emptyTable": "No events recorded yet." }
	} );
	</script>
	<?php
}
