<?php
/**
 * NodePing Monitors
 *
 * Renders Monitors content when NodePing service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

$this->display_flash();

if ( ! empty( $urls ) ) {
	$per_page = get_option( 'mainwp_aum_setting_monitors_per_page', 10 );

	$event_statuses = MainWP_AUM_Settings_Page::get_event_statuses();

	if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
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
		<?php
	}
	?>
		<table id="mainwp-aum-monitors-table" class="aum-monitors-tbl ui single line table" style="width:100%">
			<thead>
				<tr>
					<th class="collapsing no-sort check-column"><span class="ui checkbox"><input type="checkbox" name="checkall" class="url_checkall" id="url_checkall"></span></th>
					<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Site', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Availability', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Interval', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Last checked', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Incidents', 'advanced-uptime-monitor-extension' ); ?></th>
					<th class="collapsing no-sort"><?php _e( '', 'advanced-uptime-monitor-extension' ); ?></th>
				</tr>
			</thead>
	  <tbody>
		<?php
		foreach ( $urls as $url ) :
				$monitor_status = '';
				$paused         = false;
			if ( 'up' == $url->status ) {
				$monitor_status = '<span class="ui green fluid center aligned label">' . __( 'Up', 'advanced-uptime-monitor-extension' ) . '</span>';
			} elseif ( 'down' == $url->status ) {
				$monitor_status = '<span class="ui red fluid center aligned label">' . __( 'Down', 'advanced-uptime-monitor-extension' ) . '</span>';
			} elseif ( 'paused' == $url->status ) {
				$paused         = true;
				$monitor_status = '<span class="ui yellow fluid center aligned label">' . __( 'Paused', 'advanced-uptime-monitor-extension' ) . '</span>';
			} elseif ( 'pending' == $url->status ) {
				$monitor_status = '<span class="ui gray fluid center aligned label">' . __( 'Pending', 'advanced-uptime-monitor-extension' ) . '</span>';
			}

				$last_checked_at = strtotime( $url->last_checked_at );

				$incidents_contditions = array( 'url_id' => $url->url_id );

				$count_incidents = MainWP_AUM_DB_Events_BetterUptime::instance()->get_cond_events(
					array(
						'conds'      => $incidents_contditions,
						'count_only' => true,
					)
				);

			?>
			<tr  url_id="<?php echo $url->url_id; ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checkbox_url" class="checkbox_url"></span></td>
				<td><?php echo ( ! empty( $url->url_name ) ? $url->url_name : '' ); ?></td>
					<td><a href="<?php echo ( ! empty( $url->url_address ) ? $url->url_address : '' ); ?>" target="_blank"><?php echo ( ! empty( $url->url_address ) ? $url->url_address : '' ); ?></a></td>
					<td><?php echo property_exists( $url, 'availability' ) && $url->availability ? esc_html( $url->availability ) : 'N/A'; ?></td>
					<td class="status-result"><?php echo $monitor_status; ?></td>
					<td><?php echo MainWP_AUM_BetterUptime_Controller::get_monitor_interval_options( $url->monitor_interval ); ?></td>
					<td class="status-result"><?php echo MainWP_AUM_Main::format_timestamp( $last_checked_at, true ); ?></td>
					<td><?php echo $count_incidents; ?></td>
					<td class="url_actions">
						<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
							<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
							<div class="menu">
								<span onclick="mainwp_aum_js_status_monitor_button(this, <?php echo $url->url_id; ?>,event, '<?php echo wp_create_nonce( 'mainwp_aum_nonce_url_sp' ); ?>')" class="item aum_action_link <?php echo ( $paused == true ) ? 'start' : 'pause'; ?>"><?php echo ( $paused == true ) ? 'Start' : 'Pause'; ?></span>
								<span onclick="mainwp_aum_js_stats_monitor_button(<?php echo $url->url_id; ?>)" class="item aum_action_link stats_link"><?php echo __( 'Info', 'advanced-uptime-monitor-extension' ); ?></span>
								<span onclick="mainwp_aum_bettermonitor_edit_monitor_button(<?php echo $url->url_id; ?>)" class="item aum_action_link url_edit_link"><?php echo __( 'Edit', 'advanced-uptime-monitor-extension' ); ?></span>
								<span onclick="if (!confirm('Are you sure to delele selected item?')) return; mainwp_aum_js_delete_monitor_button(jQuery(this) )" class="item aum_action_link aum-delete-link"><?php echo __( 'Delete', 'advanced-uptime-monitor-extension' ); ?></span>
							</div>
						</div>
					</td>
			</tr>
	<?php endforeach; ?>
	</tbody>
	<tfoot>
		<th><span class="ui checkbox"><input type="checkbox" name="checkall" class="url_checkall" id="url_checkall"></span></th>
		<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Site', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Availability', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Interval', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Last checked', 'advanced-uptime-monitor-extension' ); ?></th>
		<th><?php _e( 'Incidents', 'advanced-uptime-monitor-extension' ); ?></th>
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
						<?php	} ?>
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
					"order": [ [ 1, "asc" ] ],
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
			jQuery("#aum_monitors_per_page").change(function() {
				jQuery(this).closest("form").submit();
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
	<?php
	$offset = get_option('mainwp_aum_betteruptime_reload_monitors_offset' );
	?>
	jQuery( document ).ready( function ($) {
		mainwp_aum_reload_uptime_monitors( <?php echo intval( $offset ); ?>, 'betteruptime' );
	} );	
	
</script>

