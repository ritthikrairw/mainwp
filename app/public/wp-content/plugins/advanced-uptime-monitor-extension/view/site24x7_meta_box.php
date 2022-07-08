<?php
/**
 * Site24x7 Metabox
 *
 * Renders Metabox content when Site24x7 service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

if ( ! empty( $urls ) ) {
	$per_page = get_option( 'mainwp_aum_setting_monitors_per_page', 10 );
	?>
	<?php if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) { ?>
	<form method="post" action="">
		<label><?php _e( 'Show', 'advanced-uptime-monitor-extension' ); ?>
			<div class="aum-min-width" style="display: inline-block;">
				<select class="ui dropdown" id="aum_monitors_per_page" name="aum_monitors_per_page">
						<option value="10" <?php selected( $per_page, 10 ); ?>>10</option>
						<option value="20" <?php selected( $per_page, 20 ); ?>>20</option>
						<option value="30" <?php selected( $per_page, 30 ); ?>>30</option>
						<option value="40" <?php selected( $per_page, 40 ); ?>>40</option>
						<option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
				</select> <?php _e( 'entries', 'advanced-uptime-monitor-extension' ); ?>
			</div>
		</label>
	 </form>
	<?php } ?>
	 <table id="mainwp-aum-monitors-widget-table" class="ui tablet stackable table">
	<thead>
	  <tr>
		<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Performance', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Last Polled', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
	  </tr>
	</thead>
	<tbody>
	<?php foreach ( $urls as $url ) : ?>
		<tr>
				<td><?php echo ( ! empty( $url->url_name ) ? esc_html( $url->url_name ) : '' ); ?></td>
				<td><?php echo intval( $url->response_time ) . ' ms'; ?></td>
		<td>
			<?php
					$datetime = strtotime( $url->last_polled_datetime_gmt );
					echo human_time_diff( $datetime ) . ' ' . __( 'ago', 'advanced-uptime-monitor-extension' );
			?>
		</td>
		<td>
			<?php
			if ( isset( $url->status ) ) {
				echo MainWP_AUM_Site24x7_Controller::render_monitor_status( $url->status );
			} else {
				echo 'N / A';
			}
			?>
		</td>
	  </tr>
		<?php endforeach; ?>
	</tbody>
	</table>
	<?php
	if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE && $total > $per_page ) {
		$get_page = isset( $_POST['get_page'] ) ? intval( $_POST['get_page'] ) : 1;
		?>
		<div class="ui grid">
			<div class="sixteen wide column left aligned">
				<label><?php echo __( 'Monitor pages', 'advanced-uptime-monitor-extension' ); ?>
					<div class="aum-min-width" style="display: inline-block;">
						<select name="mainwp_aum_monitor_select_page" id="mainwp_aum_monitor_select_page" class="ui dropdown">
							<?php
							$total_p = ceil( $total / $per_page );
							for ( $i = 1; $i <= $total_p; $i++ ) {
								?>
							<option <?php echo ( $get_page == $i ? 'selected="selected"' : '' ); ?> value="<?php echo $i; ?>" ><?php echo $i; ?></option>
								<?php
							}
							?>
						</select>
					</div>
				</label>
			</div>
		</div>
		<?php
	}
	?>
<script type="text/javascript">
	<?php if ( $total <= MAINWP_MONITOR_API_LIMIT_PER_PAGE ) { ?>
		jQuery( '#mainwp-aum-monitors-widget-table' ).DataTable( {
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"order": [ [ 1, "asc" ] ],
			"stateSave": true,
			"stateDuration": 0,
			"language": { "emptyTable": "No monitors created yet." }
		} );
	<?php } else { ?>
		jQuery("#aum_monitors_per_page").change(function() {
			jQuery( this ).closest( "form" ).submit();
		});
	<?php } ?>

</script>
	<?php
} else {
	MainWP_AUM_Settings_Page::render_api_not_existed( 'site24x7' );
}
