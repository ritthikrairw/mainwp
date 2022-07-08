<?php
/**
 * Site24x7 Monitor
 *
 * Renders Monitor content when Site24x7 service is selected.
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
?>
<!-- Add New Monitor Form -->
<?php
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
		<label class="six wide column middle aligned"><?php _e( 'Display Name', 'advanced-uptime-monitor-extension' ); ?></label>
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
		<label class="six wide column middle aligned"><?php _e( 'Web page URL', 'advanced-uptime-monitor-extension' ); ?></label>
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
					'value'   => ! empty( $data['check_frequency'] ) ? $data['check_frequency'] : 5,
					'options' => array(
						1    => __( '1 min', 'advanced-uptime-monitor-extension' ),
						3    => __( '3 mins', 'advanced-uptime-monitor-extension' ),
						5    => __( '5 mins', 'advanced-uptime-monitor-extension' ),
						10   => __( '10 mins', 'advanced-uptime-monitor-extension' ),
						15   => __( '15 mins', 'advanced-uptime-monitor-extension' ),
						20   => __( '20 mins', 'advanced-uptime-monitor-extension' ),
						30   => __( '30 mins', 'advanced-uptime-monitor-extension' ),
						60   => __( '1 hr', 'advanced-uptime-monitor-extension' ),
						120  => __( '2 hrs', 'advanced-uptime-monitor-extension' ),
						180  => __( '3 hrs', 'advanced-uptime-monitor-extension' ),
						360  => __( '6 hrs', 'advanced-uptime-monitor-extension' ),
						1440 => __( '1 day', 'advanced-uptime-monitor-extension' ),
					),
					'class'   => 'ui dropdown',
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Monitoring locations', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'location_profile_id',
				array(
					'type'    => 'select',
					'options' => $location_profiles,
					'value'   => ! empty( $data['location_profile_id'] ) ? $data['location_profile_id'] : 0,
				)
			);
			?>
		</div>
	</div>

	<div class="ui diving header"><?php echo __( 'Advanced Configuration', 'advanced-uptime-monito-extension' ); ?></div>
	
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Connection timeout (secs)', 'advanced-uptime-monitor-extension' ); ?></label>
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
		<label class="six wide column middle aligned"><?php _e( 'Monitor Groups', 'advanced-uptime-monitor-extension' ); ?></label>
			<div class="ten wide column">				
				<div class="ui multiple selection dropdown">
					<input type="hidden" name="data[site24x7][monitor_group_ids]" value="<?php echo ! empty( $data['monitor_group_ids'] ) ? esc_html( $data['monitor_group_ids'] ) : ''; ?>">
					<i class="dropdown icon"></i>
					<div class="default text"><?php esc_html_e( 'No items selected', 'advanced-uptime-monitor-extension' ); ?></div>
					<div class="menu">
						<?php
						foreach ( $monitors_groups as $mo_gid => $mo_gname ) {
							?>
							<div class="item" data-value="<?php echo esc_attr( $mo_gid ); ?>"><?php echo esc_html( $mo_gname ); ?></div>
							<?php
						}
						?>
					</div>
				</div>					
			</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Dependent on Monitor', 'advanced-uptime-monitor-extension' ); ?></label>
			<div class="ten wide column">				
				<div class="ui multiple selection dropdown">
					<input type="hidden" name="data[site24x7][dependent_monitors]" value="<?php echo ! empty( $data['dependent_monitors'] ) ? esc_html( $data['dependent_monitors'] ) : ''; ?>">
					<i class="dropdown icon"></i>
					<div class="default text"><?php esc_html_e( 'No items selected', 'advanced-uptime-monitor-extension' ); ?></div>
					<div class="menu">
						<?php
						foreach ( $dependent_site247_monitors as $mo_id => $mo_name ) {
							?>
							<div class="item" data-value="<?php echo esc_attr( $mo_id ); ?>"><?php echo esc_html( $mo_name ); ?></div>
							<?php
						}
						?>
					</div>
				</div>					
			</div>
	</div>

	<h5 class="ui diving header"><?php echo __( 'HTTP Configuration', 'advanced-uptime-monito-extension' ); ?></h5>
	
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'HTTP Method', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'http_method',
				array(
					'type'    => 'select',
					'options' => array(
						'P' => 'POST',
						'G' => 'GET',
						'H' => 'HEAD',
					),
					'value'   => ! empty( $data['http_method'] ) ? $data['http_method'] : 'G',
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'HTTP Username (Optional)', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'http_username',
				array(
					'label' => '',
					'value' => ! empty( $data['http_username'] ) ? $data['http_username'] : '',
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'HTTP Password (Optional)', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'http_password',
				array(
					'label' => '',
					'value' => ! empty( $data['http_password'] ) ? $data['http_password'] : '',
				)
			);
			?>
		</div>
	</div>

		<h5 class="ui diving header"><?php echo __( 'Configuration Profiles', 'advanced-uptime-monito-extension' ); ?></h5>
		
		<div class="ui grid field" >
			<label class="six wide column middle aligned"><?php _e( 'Threshold and Availability', 'advanced-uptime-monitor-extension' ); ?></label>
			<div class="ten wide column">
				<?php
				echo MainWP_AUM_Html_UI_Helper::instance()->input(
					'threshold_profile_id',
					array(
						'type'    => 'select',
						'options' => $threshold_profiles,
						'value'   => ! empty( $data['threshold_profile_id'] ) ? $data['threshold_profile_id'] : 0,
					)
				);
				?>
			</div>
		</div>

		<div class="ui grid field" >
			<label class="six wide column middle aligned"><?php _e( 'Notification Profile', 'advanced-uptime-monitor-extension' ); ?></label>
			<div class="ten wide column">
				<?php
				echo MainWP_AUM_Html_UI_Helper::instance()->input(
					'notification_profile_id',
					array(
						'type'    => 'select',
						'options' => $notification_profiles,
						'value'   => ! empty( $data['notification_profile_id'] ) ? $data['notification_profile_id'] : 0,
					)
				);
				?>
			</div>
		</div>

		<div class="ui grid field" >
			<label class="six wide column middle aligned"><?php _e( 'User Alert Group', 'advanced-uptime-monitor-extension' ); ?></label>
			<div class="ten wide column">				
				<div class="ui multiple selection dropdown">
					<input type="hidden" name="data[site24x7][user_group_ids]" value="<?php echo ! empty( $data['user_group_ids'] ) ? esc_html( $data['user_group_ids'] ) : ''; ?>">
					<i class="dropdown icon"></i>
					<div class="default text"><?php esc_html_e( 'Select groups', 'mainwp' ); ?></div>
					<div class="menu">
						<?php
						foreach ( $user_groups as $gid => $gname ) {
							?>
							<div class="item" data-value="<?php echo esc_attr( $gid ); ?>"><?php echo esc_html( $gname ); ?></div>
							<?php
						}
						?>
					</div>
				</div>					
			</div>
		</div>
</div>
<?php
$timestamp   = time();
$submit_text = empty( $data['url_id'] ) ? __( 'Create Monitor', 'advanced-uptime-monitor-extension' ) : __( 'Save Monitor', 'advanced-uptime-monitor-extension' );
?>
<input type="hidden" name="service" value="<?php echo esc_html( $this->service_name ); ?>">
<div class="ui divider"></div>
<input type="button" value="<?php echo $submit_text; ?>" class="ui green button" id="aum_edit_monitor_button_<?php echo $timestamp; ?>">
<input type="hidden" name="action" value="mainwp_advanced_uptime_edit_monitor">
<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_monitors_page' ); ?>">
</form>
<!-- End Add New Monitor Form -->

<script type="text/javascript">

		jQuery( document ).ready( function( $ ) {
			jQuery( '.ui.dropdown').dropdown();
		});	
		
		jQuery( '#aum_edit_monitor_button_<?php echo $timestamp; ?>' ).on('click', function (event) {
				event.preventDefault();
				var errors = [];
				jQuery('div.error').hide();

				var contacts_chossen = '';
				jQuery('input:checkbox[name=checkbox_contact]').each(function () {
					if (this.checked)
					{
						contacts_chossen += jQuery.trim(jQuery(this).val()) + '-';
					}
				});

				contacts_chossen = contacts_chossen.slice( 0, -1);

				jQuery('input[name=monitor_contacts_notification]').val(contacts_chossen);

				
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
				}, 3000 );
				<?php
			}
			?>
</script>
