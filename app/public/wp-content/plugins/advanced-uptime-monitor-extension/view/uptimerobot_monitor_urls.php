<?php
/**
 * Uptime Robot Monitors
 *
 * Renders Monitors table content when Uptime Robot service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

$this->display_flash();

if ( ! empty( $urls ) ) {

	$event_statuses = MainWP_AUM_Settings_Page::get_event_statuses();

	$current_gmt_time = time(); // timezone independent (=UTC).

	$hour  = (int) date( 'H', $current_gmt_time + $log_gmt_offset * 60 * 60 );
	$hours = '';
	if ( 24 == $hour ) {
		for ( $i = 0; $i < $hour; $i++ ) {
			$hours .= '<div>' . $i . '</div>'; }
	} else {
		$begin_hour = $hour + 1;
		for ( $i = $begin_hour; $i < 24; $i++ ) {
			$hours .= '<div>' . $i . '</div>';
		}
		for ( $i = 0; $i <= $hour; $i++ ) {
			$hours .= '<div>' . $i . '</div>';
		}
	}

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
					<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><?php _e( 'Site', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing"><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
					<th class="collapsing"><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
				<th><div class="stratch-hours"><?php echo $hours; ?></div></th>
					<th class="collapsing"><?php _e( 'Uptime Ratio', 'advanced-uptime-monitor-extension' ); ?></th>
				<th class="collapsing no-sort"><?php _e( '', 'advanced-uptime-monitor-extension' ); ?></th>
				</tr>
			</thead>
	  <tbody>
		<?php
		foreach ( $urls as $url ) :
			$monitor_status = false;
			if ( isset( $url->status ) ) {
				$monitor_status = $url->status;
			}
			?>
		<tr  url_id="<?php echo $url->url_id; ?>">
		  <td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checkbox_url" class="checkbox_url"></span></td>
					<td><?php echo ( ! empty( $url->url_name ) ? $url->url_name : '' ); ?></td>
					<td><a href="<?php echo ( ! empty( $url->url_address ) ? $url->url_address : '' ); ?>" target="_blank"><?php echo ( ! empty( $url->url_address ) ? $url->url_address : '' ); ?></a></td>
					<td class="center aligned">
			<?php
			if ( $monitor_status !== false && isset( $url->alltimeuptimeratio ) ) {
				echo MainWP_AUM_UptimeRobot_Controller::get_uptime_monitor_state( $url );
			} else {
				echo 'N / A';
			}
			?>
		  </td>
		<td class="center aligned"><?php echo MainWP_AUM_UptimeRobot_Controller::get_monitor_types( $url->monitor_type ); ?></td>
		  <td class="aum_monitors_list">
			<?php
			$first  = true;
			$status = '';

			if ( is_array( $stats ) ) {
				$total_events = ( isset( $stats[ $url->url_id ] ) && is_array( $stats[ $url->url_id ] ) ) ? count( $stats[ $url->url_id ] ) : 0;
				$i            = 0;
				if ( $total_events > 0 ) {
					if ( isset( $stats[ $url->url_id ] ) ) {
						foreach ( $stats[ $url->url_id ] as $event ) {
							$i++;
							if ( ! $first ) { // avoid display the beging status
								$class = isset( $event_statuses[ $log_type ] ) ? $event_statuses[ $log_type ] : 'not_checked';
								$style = '';
								if ( $event->status_bar_length > 0 ) {
									  $style = 'style = "width: ' . $event->status_bar_length . '%"';
								} else {
									$style = 'style = "width: 1px"';
								}
								$last_event = '';
								if ( $i == $total_events ) {
									$last_event = ' last_event="' . $class . '"';
									$class     .= ' last_event';
								}
								$status = '<div class="event_fill ' . $class . '" ' . $style . $last_event . ' event-time="' . $event->event_datetime_gmt . '"></div>' . $status;
							} else {
								$first = false;
							}
							$log_type = $event->type;
						}
					}
				}
			}
			if ( empty( $status ) ) {
				$status = '<div class="event_fill" style = "width: 2px; margin-right: -2px;"></div>';
			}
			echo $status;
			?>
		  </td>
					<td class="center aligned">
			<?php
			if ( $monitor_status !== false && isset( $url->alltimeuptimeratio ) ) {
				echo MainWP_AUM_Settings_Page::get_instance()->render_ratio_value( $monitor_status, $url->alltimeuptimeratio );
			} else {
				echo 'N / A';
			}
			?>
		  </td>
		  <td class="url_actions">
						<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999">
							<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
							<div class="menu">
								<span onclick="mainwp_aum_js_status_monitor_button(this, <?php echo $url->url_id; ?>,event, '<?php echo wp_create_nonce( 'mainwp_aum_nonce_url_sp' ); ?>')" class="item aum_action_link <?php echo ( $monitor_status == false ) ? 'start' : 'pause'; ?>"><?php echo ( $monitor_status == false ) ? 'Start' : 'Pause'; ?></span>
								<span onclick="mainwp_aum_js_stats_monitor_button(<?php echo $url->url_id; ?>)" class="item aum_action_link stats_link"><?php echo __( 'Info', 'advanced-uptime-monitor-extension' ); ?></span>
								<span onclick="mainwp_aum_js_edit_monitor_button(<?php echo $url->url_id; ?>)" class="item aum_action_link url_edit_link"><?php echo __( 'Edit', 'advanced-uptime-monitor-extension' ); ?></span>
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
	  <th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
	  <th><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
	  <th><div class="stratch-hours"><?php echo $hours; ?></div></th>
	  <th><?php _e( 'Uptime Ratio', 'advanced-uptime-monitor-extension' ); ?></th>
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
						<?php
						for ( $i = 1; $i <= $count_page; $i++ ) {
							?>
							<option <?php echo ( $get_page == $i ? 'selected="selected"' : '' ); ?> value="<?php echo $i; ?>" ><?php echo $i; ?></option>
							<?php
						}
						?>
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
	<?php
	$offset = get_option('mainwp_aum_uptime_reload_monitors_offset' );
	?>
		jQuery( document ).ready( function ($) {
			mainwp_aum_reload_uptime_monitors( <?php echo intval( $offset ); ?>, 'uptimerobot' );
		} );		
		</script>
