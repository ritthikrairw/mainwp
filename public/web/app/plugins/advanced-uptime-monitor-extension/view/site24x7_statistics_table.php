<?php
/**
 * Site24x7 Stats
 *
 * Renders Stats table content when Site24x7 service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

?>
<table class="ui single line table"><thead>
<tr>
<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'URL', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'State', 'advanced-uptime-monitor-extension' ); ?></th>
<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
</tr>
</thead>
<tbody>
	<tr>
	<td><?php echo esc_html( $url->url_name ); ?></td>
	<td><a href="<?php echo $url->url_address; ?>" target="_blank"><?php echo $url->url_address; ?></a></td>
	<td><?php echo MainWP_AUM_Site24x7_Controller::get_monitor_types( $url->monitor_type ); ?></td>
	<td><?php echo MainWP_AUM_Site24x7_Controller::get_site24x7_monitor_state( $url ); ?></td>
<td><?php echo MainWP_AUM_Site24x7_Controller::render_monitor_status( $url->status ); ?></td>
	</tr>
	</tbody>
</table>
<?php if ( ! empty( $stats ) ) { ?>
	<table class="ui single line table">
	<thead>
		<tr>
		<th class="collapsing"><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Performance', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Last Polled', 'advanced-uptime-monitor-extension' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $stats as $event ) {
			?>
			<tr>
			<td><?php echo MainWP_AUM_Site24x7_Controller::render_monitor_status( $event->status ); ?></td>
			<td><?php echo intval( $event->response_time ) . ' ms'; ?></td>
			<td>
			<?php
					$datetime = strtotime( $event->last_polled_datetime_gmt );
					echo human_time_diff( $datetime ) . ' ' . __( 'ago', 'advanced-uptime-monitor-extension' );
			?>
			</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>

		<?php

}
