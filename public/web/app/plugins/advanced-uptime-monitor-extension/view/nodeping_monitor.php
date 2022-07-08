<?php
/**
 * NodePing Monitor
 *
 * Renders Monitor content when NodePing service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

$data = isset( $this->params['data'][ $this->service_name ] ) ? $this->params['data'][ $this->service_name ] : array();

// process monitor that don't exist.
global $current_user;

$result                  = MainWP_AUM_DB::instance()->get_monitor_urls( $this->service_name );
$current_sites_addresses = array();
$count                   = $result ? count( $result ) : 0;
if ( $result ) {
	for ( $i = 0; $i < $count; $i++ ) {
		$current_sites_addresses[ $i ] = $result[ $i ]->url_address;
	}
}
$other_site_urls = array();
global $mainwpAUMExtensionActivator;
$pWebsites = apply_filters( 'mainwp_getsites', $mainwpAUMExtensionActivator->get_child_file(), $mainwpAUMExtensionActivator->get_child_key(), null );
if ( count( $current_sites_addresses ) > 0 ) {
	foreach ( $pWebsites as $website ) {
		$url = rtrim( $website['url'], '/' );
		if ( ! in_array( $url, $current_sites_addresses ) ) {
			$other_site_urls[ $url ] = stripslashes( $website['name'] );
		}
	}
} else {
	foreach ( $pWebsites as $website ) {
		$url                     = rtrim( $website['url'], '/' );
		$other_site_urls[ $url ] = stripslashes( $website['name'] );
	}
}


if ( count( $other_site_urls ) > 0 ) {
	$_urls        = array_keys( $other_site_urls );
	$tring_result = array_shift( $_urls );
} else {
	$tring_result = 'http://';
}

$timestamp = time();

echo MainWP_AUM_Html_UI_Helper::instance()->create( $this->service_name );
?>
<div class="ui form">
	<div id="popup_message_info_box" class="ui green message" style="display: none"></div>
	<div id="popup_message_error_box" class="ui red message" style="display: none"></div>
	<?php $this->display_flash(); ?>
	<?php
	$edit_url_id = false;
	if ( isset( $this->params['url_id'] ) ) {
		$edit_url_id = $this->params['url_id'];
	} elseif ( isset( $data['url_id'] ) ) {
		$edit_url_id = $data['url_id'];
	}

	if ( false !== $edit_url_id ) {
		echo MainWP_AUM_Html_UI_Helper::instance()->input(
			'url_id',
			array(
				'value' => $edit_url_id,
				'type'  => 'hidden',
			)
		);
	}

	$url_name     = '';
	$style_select = 'style="display:none"';
	$style_text   = 'style="display:flex"';
	if ( isset( $this->params['checkbox_show_select'] ) && count( $other_site_urls ) >= 1 ) {
		$style_select = 'style="display:flex"';
		$style_text   = 'style="display:none"';
	} else {
		if ( isset( $data['url_name'] ) ) {
			$url_name = $data['url_name'];
		}
	}

	?>
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Check Label', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			if ( isset( $data['url_name'] ) ) {
				$url_name = $data['url_name'];
			} else {
				$url_name = '';
			}
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'url_name',
				array(
					'label' => '',
					'value' => $url_name,
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'monitor_type',
				array(
					'type'     => 'select',
					'label'    => '',
					'id'       => 'aum_nodeping_edit_select_type',
					'value'    => ! empty( $data['monitor_type'] ) ? $data['monitor_type'] : 'HTTP',
					'options'  => array(
						''            => 'Select a Check Type',
						'AGENT'       => 'AGENT',
						'AUDIO'       => 'Audio Stream',
						'CLUSTER'     => 'Cluster',
						'DNS'         => 'DNS',
						'FTP'         => 'FTP',
						'HTTP'        => 'HTTP',
						'HTTPADV'     => 'HTTP Advanced',
						'HTTPCONTENT' => 'HTTP Content',
						'HTTPPARSE'   => 'HTTP Parse',
						'IMAP4'       => 'IMAP4',
						'MYSQL'       => 'MYSQL',
						'NTP'         => 'NTP',
						'PING'        => 'PING',
						'POP3'        => 'POP3',
						'PORT'        => 'PORT',
						'PUSH'        => 'PUSH',
						'RBL'         => 'RBL',
						'RDP'         => 'RDP',
						'SIP'         => 'SIP',
						'SMTP'        => 'SMTP',
						'SNMP'        => 'SNMP',
						'SPEC10DNS'   => 'S10DNS',
						'SPEC10RDDS'  => 'S10RDDS',
						'SSH'         => 'SSH',
						'SSL'         => 'SSL',
						'WEBSOCKET'   => 'WebSocket',
						'WHOIS'       => 'WHOIS',
					),
					'onchange' => 'mainwp_aum_js_nodeping_type_onchange(this)',
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Enable Check', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'enable',
				array(
					'type'              => 'checkbox',
					'label'             => '',
					'value'             => '1',
					'checked'           => ( ! empty( $data['enable'] ) && ( 'active' == $data['enable'] || 1 == $data['enable'] ) ) ? true : false,
					'class'             => '',
					'with_hidden_input' => false,
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Description', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			if ( isset( $data['description'] ) ) {
				$description = $data['description'];
			} else {
				$description = '';
			}

			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'description',
				array(
					'type'  => 'textarea',
					'label' => '',
					'value' => $description,
				)
			);

			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Regions & Home Location', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php

			$regions = MainWP_AUM_Main_Controller::instance()->get_info_regions();
			$region  = isset( $data['region'] ) ? $data['region'] : '---';

			echo __( 'Regions', 'advanced-uptime-monitor-extension' ) . '<br/>';

			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'region',
				array(
					'type'     => 'select',
					'id'       => 'aum_nodeping_select_region',
					'label'    => '',
					'value'    => $region,
					'options'  => $regions,
					'onchange' => 'mainwp_aum_js_nodeping_region_onchange(this)',
				)
			);

			$locations = MainWP_AUM_Main_Controller::instance()->get_info_locations( $region );

			echo '<br/>' . __( 'Home Location', 'advanced-uptime-monitor-extension' ) . '<br/>';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'location',
				array(
					'type'    => 'select',
					'id'      => 'aum_nodeping_select_location',
					'label'   => '',
					'value'   => isset( $data['location'] ) ? $data['location'] : 'none',
					'options' => $locations,
				)
			);
			?>
		</div>
	</div>


	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Check Frequency', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'check_frequency',
				array(
					'type'    => 'select',
					'label'   => '',
					'value'   => ! empty( $data['check_frequency'] ) ? $data['check_frequency'] : 15,
					'options' => array(
						1    => __( '1 min', 'advanced-uptime-monitor-extension' ),
						3    => __( '3 mins', 'advanced-uptime-monitor-extension' ),
						5    => __( '5 mins', 'advanced-uptime-monitor-extension' ),
						10   => __( '10 mins', 'advanced-uptime-monitor-extension' ),
						15   => __( '15 mins', 'advanced-uptime-monitor-extension' ),
						30   => __( '30 mins', 'advanced-uptime-monitor-extension' ),
						60   => __( '1 hr', 'advanced-uptime-monitor-extension' ),
						240  => __( '4 hrs', 'advanced-uptime-monitor-extension' ),
						360  => __( '6 hrs', 'advanced-uptime-monitor-extension' ),
						720  => __( '12 hrs', 'advanced-uptime-monitor-extension' ),
						1440 => __( '1 day', 'advanced-uptime-monitor-extension' ),
					),
					'class'   => 'ui dropdown',
				)
			);
			?>
		</div>
	</div>
	<div class="ui grid field" id="aum_nodeping_edit_url_address">
		<label class="six wide column middle aligned"><?php _e( 'URL or HOST:', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			if ( isset( $data['url_address'] ) ) {
				$url_address = $data['url_address'];
			} else {
				$url_address = 'http://';
			}
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'url_address',
				array(
					'label' => '',
					'value' => $url_address,
				)
			);
			?>
			<br>
			<em><?php echo __( 'Note that for HTTP, HTTPCONTENT, HTTPPARSE, and HTTPADV checks this must begin with "http://" or "https://".', 'advanced-uptime-monitor-extension' ); ?></em>
		</div>
	</div>
	
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Timeout', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'timeout',
				array(
					'label' => '',
					'value' => ! empty( $data['timeout'] ) ? $data['timeout'] : 15,
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Sensitivity', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'sensitivity',
				array(
					'type'    => 'select',
					'label'   => '',
					'value'   => ! empty( $data['sensitivity'] ) ? $data['sensitivity'] : 2,
					'options' => array(
						0  => __( 'Very High (no rechecks)', 'advanced-uptime-monitor-extension' ),
						2  => __( 'High (2 rechecks)', 'advanced-uptime-monitor-extension' ),
						5  => __( 'Medium (5 rechecks)', 'advanced-uptime-monitor-extension' ),
						7  => __( 'Low (7 rechecks)', 'advanced-uptime-monitor-extension' ),
						10 => __( 'Very Low (10 rechecks)', 'advanced-uptime-monitor-extension' ),
					),
					'class'   => 'ui dropdown',
				)
			);
			?>
		</div>
	</div>

	<div class="ui diving header"><?php echo __( 'Check Notifications', 'advanced-uptime-monito-extension' ); ?></div>
		
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Dependency', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'dependency',
				array(
					'type'    => 'select',
					'label'   => '',
					'value'   => ! empty( $data['dependency'] ) ? $data['dependency'] : '',
					'options' => ! empty( $dependency_nodeping_monitors ) ? $dependency_nodeping_monitors : array(),
					'class'   => 'ui dropdown',
				)
			);
			?>
		</div>
	</div>

	<?php
	$opts_delay = array(
		0  => __( 'Immediate', 'advanced-uptime-monitor-extension' ),
		1  => __( '1 minute', 'advanced-uptime-monitor-extension' ),
		2  => __( '2 minutes', 'advanced-uptime-monitor-extension' ),
		3  => __( '3 minutes', 'advanced-uptime-monitor-extension' ),
		5  => __( '5 minutes', 'advanced-uptime-monitor-extension' ),
		8  => __( '8 minutes', 'advanced-uptime-monitor-extension' ),
		10 => __( '10 minutes', 'advanced-uptime-monitor-extension' ),
		15 => __( '15 minutes', 'advanced-uptime-monitor-extension' ),
		20 => __( '20 minutes', 'advanced-uptime-monitor-extension' ),
		30 => __( '30 minutes', 'advanced-uptime-monitor-extension' ),
		45 => __( '45 minutes', 'advanced-uptime-monitor-extension' ),
		60 => __( '60 minutes', 'advanced-uptime-monitor-extension' ),
	);

	$opts_schedule = array(
		'All'      => __( 'All the time', 'advanced-uptime-monitor-extension' ),
		'Weekends' => __( 'Weekends', 'advanced-uptime-monitor-extension' ),
		'Weekdays' => __( 'Weekdays', 'advanced-uptime-monitor-extension' ),
		'Days'     => __( 'Days', 'advanced-uptime-monitor-extension' ),
		'Nights'   => __( 'Nights', 'advanced-uptime-monitor-extension' ),
	);
	?>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Notify', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			$opts_contacts = array(
				0 => __( 'Select a contact to add', 'advanced-uptime-monitor-extension' ),
			);
			if ( ! empty( $contacts ) ) {
				$contacts = current( $contacts );
				if ( isset( $contacts['addresses'] ) ) {
					foreach ( $contacts['addresses'] as $add_id => $val ) {
						$opts_contacts[ $add_id ] = $val['address'];
					}
				}
			}
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'select_notify',
				array(
					'id'      => 'nodeping_select_contact_' . $timestamp,
					'type'    => 'select',
					'label'   => '',
					'value'   => ! empty( $data['notify'] ) ? $data['notify'] : '',
					'options' => $opts_contacts,
					'class'   => '',
				)
			);

			$notifications = isset( $data['notify_contacts'] ) ? $data['notify_contacts'] : false;

			$idx_noti = 0;

			if ( is_array( $notifications ) && ! empty( $notifications ) ) {

				foreach ( $notifications as $noti_contact ) {

					$noti_id = $noti_contact['contact'];

					if ( ! isset( $opts_contacts[ $noti_id ] ) ) {
						continue;
					}

					$contact_email = $opts_contacts[ $noti_id ];

					$delay    = $noti_contact['delay'];
					$schedule = $noti_contact['schedule'];

					?>
					<div class="remove-contact-row">	
						<br><input type="hidden" name="data[nodeping][notify_contacts][<?php echo $idx_noti; ?>][contact]" value="<?php echo esc_html( $noti_id ); ?>"><span><?php echo esc_html( $contact_email ); ?></span><br>
						<?php
						echo MainWP_AUM_Html_UI_Helper::instance()->input(
							'delay',
							array(
								'id'      => 'nodeping_select_contact_delay_tmpl',
								'type'    => 'select',
								'name'    => 'data[nodeping][notify_contacts][' . $idx_noti . '][delay]',
								'label'   => '',
								'value'   => $delay,
								'options' => $opts_delay,
								'class'   => 'ui dropdown',
							)
						);
						echo MainWP_AUM_Html_UI_Helper::instance()->input(
							'schedule',
							array(
								'id'      => 'nodeping_select_contact_schedule_tmpl',
								'type'    => 'select',
								'name'    => 'data[nodeping][notify_contacts][' . $idx_noti . '][schedule]',
								'label'   => '',
								'value'   => $schedule,
								'options' => $opts_schedule,
								'class'   => 'ui dropdown',
							)
						);
						?>
						<a href="javascript:void(0);" onclick="return on_remove_contact_row(this);"><?php _e( 'Remove', 'advanced-uptime-monitor-extension' ); ?></a>
					</div>
					<?php
					$idx_noti++;

				}
			}

			?>
		</div>
	</div>
</div>
<div style="display:none">
<?php
echo MainWP_AUM_Html_UI_Helper::instance()->input(
	'delay',
	array(
		'id'      => 'nodeping_select_contact_delay_tmpl',
		'type'    => 'select',
		'name'    => 'temp[nodeping][notify_contacts][delay]',
		'label'   => '',
		'value'   => '',
		'options' => $opts_delay,
		'class'   => 'ui dropdown',
	)
);
echo MainWP_AUM_Html_UI_Helper::instance()->input(
	'schedule',
	array(
		'id'      => 'nodeping_select_contact_schedule_tmpl',
		'type'    => 'select',
		'name'    => 'temp[nodeping][notify_contacts][schedule]',
		'label'   => '',
		'value'   => '',
		'options' => $opts_schedule,
		'class'   => 'ui dropdown',
	)
);
?>
</div>
<?php
$submit_text = empty( $data['url_id'] ) ? __( 'Create Monitor', 'advanced-uptime-monitor-extension' ) : __( 'Save Monitor', 'advanced-uptime-monitor-extension' );
?>
<input type="hidden" name="service" value="<?php echo esc_html( $this->service_name ); ?>">
<div class="ui divider"></div>
<input type="button" value="<?php echo $submit_text; ?>" class="ui green button" id="aum_edit_monitor_button_<?php echo $timestamp; ?>">
<input type="hidden" name="action" value="mainwp_advanced_uptime_edit_monitor">
<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_monitors_page' ); ?>">
</form>
<script type="text/javascript">

		jQuery( document ).ready( function( $ ) {
			jQuery( '.ui.dropdown').dropdown();
			jQuery('.ui.checkbox:not(.not-auto-init)').checkbox();
			on_remove_contact_row = function( elem ){
				jQuery(elem).closest('div.remove-contact-row').remove();
				console.log(jQuery(elem).closest('.remove-contact-row'));
				return true;
			}
			jQuery( '#nodeping_select_contact_<?php echo $timestamp; ?>' ).change( function( ev ){
				var text = jQuery( '#nodeping_select_contact_<?php echo $timestamp; ?> option:selected' ).text();
				var value = jQuery(this).val();
				var idx = jQuery('input[type="hidden"][name^="data[nodeping][notify_contacts]"]').length;
				if ( '0' != value ) {
					var wrapper = jQuery('<div class="remove-contact-row"></div>');	
					jQuery('<br><input type="hidden" name="data[nodeping][notify_contacts][' + idx + '][contact]" value="' + value + '"><span>' + text + '</span><br>').appendTo(wrapper);
					jQuery('#nodeping_select_contact_delay_tmpl').clone().show().attr('id', 'select_contact_delay').attr('name', 'data[nodeping][notify_contacts][' + idx + '][delay]').dropdown().appendTo(wrapper);
					jQuery('#nodeping_select_contact_schedule_tmpl').clone().show().attr('id', 'select_contact_schedule').attr('name', 'data[nodeping][notify_contacts][' + idx + '][schedule]').dropdown().appendTo(wrapper);
					jQuery('<a href="javascript:void(0);" onclick="return on_remove_contact_row(this);">Remove</a>').appendTo(wrapper);
					jQuery(wrapper).insertAfter(this);
				}		
				jQuery(this).val('0');
			});
			
		});	
		

		jQuery( '#aum_edit_monitor_button_<?php echo $timestamp; ?>' ).on('click', function (event) {
				event.preventDefault();
				var errors = [];
				jQuery('div.error').hide();

				
				if (!jQuery('input[name=checkbox_show_select]').is(':checked') && jQuery('input[name="data[site24x7][url_address]"]').val() == '')
				{
					errors.push('Please enter Monitor Name');

				}
				
				if (errors.length > 0) {
					jQuery('#popup_message_error_box').html(errors.join('<br />')).show();
					return false;
				}

				jQuery('div.monitor_url_name select option').each(function () {
					var html_name = jQuery(this).html();
					jQuery(this).val( html_name );
				} )

				jQuery('#popup_message_info_box').html('<i class="notched circle loading icon"></i> Action in progress. Please wait...').show();
				jQuery('#popup_message_error_box').html('').hide();
				jQuery(this).attr('disabled', 'disabled');
				var data = jQuery(this).closest('form').serialize();
				jQuery.ajax({
					url: ajaxurl,
					type: 'GET',
					data: data,
					error: function () {
						jQuery('#mainwp-create-edit-monitor-modal #popup_message_error_box').html('Unexpected error. Please try again later.');
					},
					success: function (response) {
						jQuery('#mainwp-create-edit-monitor-modal').find('.content').html( response );
					},
					timeout: 20000
				});
				return false;
			});

			jQuery('input[name=checkbox_show_select]').change(function () {
				var item = '<?php echo $tring_result; ?>';
				if (jQuery(this).is(':checked')) {
					jQuery('tr.monitor_url_name_text').hide(); // css('display', 'none');
					jQuery('tr.monitor_url_name').show(); // css('display', 'block');
					jQuery('tr.monitor_url_name div select').prop('selectedIndex', 0);
				} else {
					jQuery('tr.monitor_url_name').hide(); //css('display', 'none');
					jQuery('tr.monitor_url_name_text').show(); //css('display', 'block');
					jQuery('tr.monitor_url_name_text input').val('');
				}
			});

			<?php
			if ( isset( $url_saved ) && $url_saved ) {
				?>
				jQuery('.url_form').find('input').attr('disabled', 'disabled');
				jQuery('.url_form').find('select').attr('disabled', 'disabled');
				jQuery('.url_form').find('textarea').attr('disabled', 'disabled');
				setTimeout(function () {
					location.href = location.href
				}, 3000);
				<?php
			}
			?>
</script>
