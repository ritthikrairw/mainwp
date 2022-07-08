<?php
/**
 * NodePing Metabox
 *
 * Renders Metabox content when NodePing service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

if ( ! empty( $urls ) ) {
	$per_page = get_option( 'mainwp_aum_setting_monitors_per_page', 10 );

	?>
	<table id="mainwp-aum-monitors-widget-table" class="ui tablet stackable table">
	<thead>
	  <tr>
		<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Site', 'advanced-uptime-monitor-extension' ); ?></th>
			<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></div></th>
		<th><?php _e( 'Interval', 'advanced-uptime-monitor-extension' ); ?></th>
	  </tr>
	</thead>
	<tbody>
	<?php foreach ( $urls as $url ) : ?> 
			<tr>
			<td><?php echo ( ! empty( $url->url_name ) ? $url->url_name : '' ); ?></td>
			<td><?php echo ( ! empty( $url->url_address ) ? $url->url_address : '' ); ?></td>			
			<td><?php echo MainWP_AUM_BetterUptime_Controller::get_betteruptime_monitor_state( $url ); ?></td>
			<td><?php echo intval( $url->monitor_interval); ?></td>
		</tr>				
		<?php endforeach; ?>
	</tbody>
	</table>
	<?php
	if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE && $total > $per_page ) {
		$get_page = isset( $_POST['get_page'] ) && ! empty( $_POST['get_page'] ) ? intval( $_POST['get_page'] ) : 1;
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
			"stateDuration": 0, // forever
			"language": { "emptyTable": "No monitors created yet." }
		} );
	<?php } ?>	
</script>
	<?php
} else {
	MainWP_AUM_Settings_Page::render_api_not_existed( 'betteruptime' );
}
