<?php
/**
 * Site24x7 Monitors
 *
 * Renders Monitors table content when Site24x7 service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

$this->display_flash();

if ( ! empty( $urls ) ) {

	$event_statuses = MainWP_AUM_Settings_Page::get_event_statuses();

	if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
		$per_page = get_option( 'mainwp_aum_setting_monitors_per_page', 10 );

		?>
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

		<table id="mainwp-aum-monitors-table" class="aum-monitors-tbl ui single line table" style="width:100%">
			<thead>
				<tr>
				<th class="collapsing no-sort check-column"><span class="ui checkbox"><input type="checkbox" name="checkall" class="url_checkall" id="url_checkall"></span></th>
					<th class="collapsing no-sort"><?php _e( 'State', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Site', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing"><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
					<th class="collapsing"><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
					<th class="collapsing"><?php _e( 'Last Polled', 'advanced-uptime-monitor-extension' ); ?></th>
					<th class="collapsing"><?php _e( 'Poll Frequency (m)', 'advanced-uptime-monitor-extension' ); ?></th>
					<th class="collapsing"><?php _e( 'Performance (ms)', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing no-sort"><?php _e( '', 'advanced-uptime-monitor-extension' ); ?></th>
				</tr>
			</thead>
	  <tbody>
				<?php foreach ( $urls as $url ) : ?>
		<tr  url_id="<?php echo $url->url_id; ?>">
		  <td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checkbox_url" class="checkbox_url"></span></td>
					<td><?php echo MainWP_AUM_Site24x7_Controller::get_site24x7_monitor_state( $url ); ?></td>
					<td><?php echo ( ! empty( $url->url_name ) ? $url->url_name : '' ); ?></td>
					<td><a href="<?php echo ( ! empty( $url->url_address ) ? $url->url_address : '' ); ?>" target="_blank"><?php echo ( ! empty( $url->url_address ) ? $url->url_address : '' ); ?></a></td>
					<td><?php echo MainWP_AUM_Site24x7_Controller::render_monitor_status( $url->status ); ?></td>
		  <td><?php echo MainWP_AUM_Site24x7_Controller::get_monitor_types( $url->monitor_type ); ?></td>
					<td><?php echo human_time_diff( strtotime( $url->last_polled_datetime_gmt ) ) . ' ago'; ?></td>
					<td class="center aligned"><?php echo $url->monitor_interval; ?></td>
					<td class="center aligned">
					<?php
					echo intval( $url->response_time ) . ' ms';
					$reports_today = property_exists( $url, 'reports_today' ) ? $url->reports_today : array();
					if ( false && ! empty( $reports_today ) && is_array( $reports_today ) ) {
						$dns_time        = isset( $reports_today['dns_time'] ) ? intval( isset( $reports_today['dns_time'] ) ) : 0;
						$connection_time = isset( $reports_today['connection_time'] ) ? intval( isset( $reports_today['connection_time'] ) ) : 0;
						$response_time   = isset( $reports_today['response_time'] ) ? intval( isset( $reports_today['response_time'] ) ) : 0;

						$not_zero = ( $dns_time + $connection_time + $response_time > 0 ) ? true : false;

						$dns_percent        = $not_zero ? ( $dns_time / ( $dns_time + $connection_time + $response_time ) ) * 100 : 0;
						$connection_percent = $not_zero ? ( $connection_time / ( $dns_time + $connection_time + $response_time ) ) * 100 : 0;
						$responses_percent  = $not_zero ? ( $response_time / ( $dns_time + $connection_time + $response_time ) ) * 100 : 0;

						$tool_tip       = '';
						$reports_status = '';
						if ( $dns_time ) {
							$tool_tip       .= 'DNS time: ' . $reports_today['dns_time'] . ' ms<br>';
							$reports_status .= '<div class="dns_time" style="width:' . $dns_percent . '%"></div>';
						}
						if ( $connection_time ) {
							$tool_tip       .= 'Connection time: ' . $connection_time . ' ms<br>';
							$reports_status .= '<div class="connection_time" style="width:' . $connection_percent . '%"></div>';
						}
						if ( $response_time ) {
							$tool_tip       .= 'First Byte Time: ' . $response_time . ' ms<br>';
							$reports_status .= '<div class="response_time" style="width:' . $response_time . '%"></div>';
						}
						echo '<div class="aum_reports_today" data-tooltip="' . $tool_tip . '">' . $reports_status . '</div>';
					}
					?>
		  </td>
					<?php
					$monitor_state = false;
					if ( isset( $url->state ) ) {
						$monitor_state = $url->state;
					}
					?>
		  <td class="url_actions">
						<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
							<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
							<div class="menu">
								<span onclick="mainwp_aum_js_status_monitor_button(this, <?php echo $url->url_id; ?>,event, '<?php echo wp_create_nonce( 'mainwp_aum_nonce_url_sp' ); ?>')" class="item aum_action_link <?php echo ( $monitor_status == false ) ? 'start' : 'pause'; ?>"><?php echo ( $monitor_status == false ) ? 'Start' : 'Pause'; ?></span>
								<span onclick="mainwp_aum_js_stats_monitor_button(<?php echo $url->url_id; ?>)" class="item aum_action_link stats_link"><?php echo __( 'Info', 'advanced-uptime-monitor-extension' ); ?></span>
								<span onclick="mainwp_aum_site24x7_edit_monitor_button(<?php echo $url->url_id; ?>)" class="item aum_action_link url_edit_link"><?php echo __( 'Edit', 'advanced-uptime-monitor-extension' ); ?></span>
								<span onclick="if (!confirm('Are you sure to delele selected item?')) return; mainwp_aum_js_delete_monitor_button(jQuery(this) )" class="item aum_action_link aum-delete-link"><?php echo __( 'Delete', 'advanced-uptime-monitor-extension' ); ?></span>
							</div>
						</div>
		  </td>
	  </tr>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
			<th class="collapsing no-sort check-column"><span class="ui checkbox"><input type="checkbox" name="checkall" class="url_checkall" id="url_checkall"></span></th>
			<th class="collapsing"><?php _e( 'State', 'advanced-uptime-monitor-extension' ); ?></th>
	  <th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
	  <th><?php _e( 'Site', 'advanced-uptime-monitor-extension' ); ?></th>
			<th class="collapsing"><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
			<th class="collapsing"><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
			<th class="collapsing"><?php _e( 'Last Polled', 'advanced-uptime-monitor-extension' ); ?></th>
			<th class="collapsing"><?php _e( 'Poll Frequency (m)', 'advanced-uptime-monitor-extension' ); ?></th>
			<th class="collapsing"><?php _e( 'Performance (ms)', 'advanced-uptime-monitor-extension' ); ?></th>
	  <th class="collapsing no-sort"><?php _e( '', 'advanced-uptime-monitor-extension' ); ?></th>
	</tfoot>
  </table>
	<?php
	if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
		echo __( 'Total:', 'advanced-uptime-monitor-extension' ) . ' ' . $total . ' ' . __( 'monitors', 'advanced-uptime-monitor-extension' ) . ' ';
		if ( $total > $per_page ) {
			$count_page = ceil( $total / $per_page );
			?>
			<label><?php echo __( 'Monitor pages', 'advanced-uptime-monitor-extension' ); ?>
				<div class="aum-min-width" style="display: inline-block;">
					<select name="mainwp_aum_monitor_select_page" id="mainwp_aum_monitor_select_page" class="ui dropdown">
					<?php for ( $i = 1; $i <= $count_page; $i++ ) { ?>
						<option <?php echo ( $get_page == $i ? 'selected="selected"' : '' ); ?> value="<?php echo $i; ?>" ><?php echo $i; ?></option>
					<?php } ?>
					</select>
				</div>
			</label>
			<?php
		}
	}
	?>
	<div class="ui hidden divider"></div>
	<?php
}

?>
<script type="text/javascript">
<?php if ( $total <= MAINWP_MONITOR_API_LIMIT_PER_PAGE ) { ?>
				jQuery( '#mainwp-aum-monitors-table' ).DataTable( {
					"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
		"order": [ [ 2, "asc" ] ],
					"drawCallback": function( settings ) {
						jQuery('#mainwp-aum-monitors-table .ui.checkbox').checkbox();
						jQuery('#mainwp-aum-monitors-table .ui.dropdown').dropdown();
						jQuery( '#mainwp-aum-monitors-table table th input[type="checkbox"]' ).change( function () {
						var checkboxes = jQuery( '#mainwp-aum-monitors-table' ).find( 'input:checkbox' );
							if ( jQuery( this ).prop( 'checked' ) ) {
							checkboxes.prop( 'checked', true );
						} else {
							checkboxes.prop( 'checked', false );
						}
						} );
						if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
								mainwp_datatable_fix_menu_overflow();
							}
					},
		  "stateSave": true,
		"stateDuration": 0,
		  "scrollX": true,
		  "colReorder" : {
			fixedColumnsLeft: 1,
			fixedColumnsRight: 1
		  },
					"language": { "emptyTable": "No monitors created yet." }
			} );

			if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
					mainwp_datatable_fix_menu_overflow();
				}

<?php } else { ?>
	jQuery( "#aum_monitors_per_page" ).change( function() {
		jQuery( this ).closest( "form" ).submit();
	} );
<?php } ?>
		jQuery('#mainwp-aum-monitors-table .ui.checkbox').checkbox();
		jQuery('#mainwp-advanced-uptime-monitor-monitors .ui.dropdown').dropdown();
	jQuery( '#mainwp-aum-monitors-table table th input[type="checkbox"]' ).change( function () {
	  var checkboxes = jQuery( '#mainwp-aum-monitors-table' ).find( 'input:checkbox' );
	  if ( jQuery( this ).prop( 'checked' ) ) {
		checkboxes.prop( 'checked', true );
	  } else {
		checkboxes.prop( 'checked', false );
	  }
	} );
</script>