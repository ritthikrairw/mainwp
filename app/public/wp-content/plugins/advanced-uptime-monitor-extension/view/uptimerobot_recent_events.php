<?php
/**
 * Uptime Robot Recent Events
 *
 * Renders Recent Events table content when Uptime Robot service is selected.
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
				<th><?php _e( 'Date / Time', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Details', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Duration', 'advanced-uptime-monitor-extension' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ( $stats as $event ) :
				$status = '';

				$type   = $event->type;
				$status = ucfirst( $event_statuses[ $type ] );

				if ( ! empty( $urls_info[ $event->url_id ]['url_name'] ) ) {
					?>
						<tr>
							<td>
							<?php
							switch ( $status ) {
								case 'Started':
									echo '<span class="ui center aligned mini fluid yellow label">Started</span>';
									break;
								case 'Up':
									echo '<span class="ui center aligned mini fluid green label">Up</span>';
									break;
								case 'Paused':
									echo '<span class="ui center aligned mini fluid grey label">Paused</span>';
									break;
								case 'Down':
									echo '<span class="ui center aligned mini fluid red label">Down</span>';
									break;
							}
							?>
							</td>
							<td><?php echo isset( $urls_info[ $event->url_id ] ) ? esc_html( $urls_info[ $event->url_id ]['url_name'] ) : ''; ?></td>
							<td>
							<?php
							$datetime = strtotime( $event->event_datetime_gmt ) + $offset_time;
							$datetime = MainWP_AUM_Main::format_timestamp( $datetime );
							echo $datetime;
							?>
							</td>
							<td>
								<?php
								switch ( $status ) {
									case 'Started':
											echo '<span class="play bold">' . __( 'Started', 'advanced-uptime-monitor-extension' ) . '</span>';
										break;
									case 'Up':
											echo '<span class="positive bold">' . __( 'Ok', 'advanced-uptime-monitor-extension' ) . ' (' . $event->code . ')</span>';
										break;
									case 'Paused':
											echo '<span class="pause bold">' . __( 'Paused', 'advanced-uptime-monitor-extension' ) . '</span>';
										break;
									case 'Down':
											echo '<span class="negative bold">' . $event->detail . '</span>';
										break;
								}
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
						<?php
				}
				?>
				<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th><?php _e( 'Event', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Date / Time', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Details', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Duration', 'advanced-uptime-monitor-extension' ); ?></th>
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
