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
				<th><?php _e( 'Cause', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Started at', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing"><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $stats as $event ) :
			if ( ! empty( $urls_info[ $event->url_id ]['url_name'] ) ) {
				?>
				<tr>
					<td><?php echo esc_html( $event->incident_name ); ?></td>
					<td><?php echo esc_html( $event->cause ); ?></td>
					<td>
					<?php
					$datetime = $event->started_at;
					echo human_time_diff( $datetime ) . ' ' . __( 'ago', 'advanced-uptime-monitor-extension' );
					?>
					</td>					
					<td class="center aligned"><?php echo MainWP_AUM_BetterUptime_Controller::get_betteruptime_event_state( $event ); ?></td>
				</tr>
				<?php
			}
			?>
		<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Cause', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Started at', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
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
