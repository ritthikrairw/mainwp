<?php
/**
 * Site24x7 Recent Events
 *
 * Renders Recent Events table content when Site24x7 service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

if ( ! empty( $stats ) && ! empty( $urls_info ) ) {
	?>
	<table id="mainwp-aum-recent-events-table" class="ui single line table">
		<thead>
			<tr>
				<th class="collapsing"><?php _e( 'Event', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Performance', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Last Polled', 'advanced-uptime-monitor-extension' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $stats as $event ) :
			$status = '';
			if ( ! empty( $urls_info[ $event->url_id ]['url_name'] ) ) {
				?>
				<tr>
					<td><?php echo MainWP_AUM_Site24x7_Controller::render_monitor_status( $event->status ); ?></td>
					<td><?php echo isset( $urls_info[ $event->url_id ] ) ? esc_html( $urls_info[ $event->url_id ]['url_name'] ) : ''; ?></td>
					<td><?php echo intval( $event->response_time ) . ' ms'; ?></td>
					<td>
						<?php
						$datetime = strtotime( $event->last_polled_datetime_gmt );
						echo human_time_diff( $datetime ) . ' ' . __( 'ago', 'advanced-uptime-monitor-extension' );
						?>
					</td>
				</tr>
				<?php
			}
			?>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<th><?php _e( 'Event', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Performance', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Last Polled', 'advanced-uptime-monitor-extension' ); ?></th>
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
