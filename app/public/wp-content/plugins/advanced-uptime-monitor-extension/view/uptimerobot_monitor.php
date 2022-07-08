<?php
/**
 * Uptime Robot Monitor
 *
 * Renders Monitor content when Uptime Robot service is selected.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

// process monitor that don't exist.
global $current_user;

$result                  = MainWP_AUM_DB::instance()->get_monitor_urls();
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

$edit_url_id       = 0;
$list_noti_contact = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'list_notification_contact' );
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
	} elseif ( isset( $this->params['data'][ $this->service_name ]['url_id'] ) ) {
		$edit_url_id = $this->params['data'][ $this->service_name ]['url_id'];
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

		$monitor_id = false;
	if ( isset( $monitor ) ) {
		$monitor_id = $monitor->monitor_id;
	} elseif ( isset( $this->params['monitor_id'] ) ) {
		$monitor_id = $this->params['monitor_id'];
	}

	if ( false !== $monitor_id ) {
		echo MainWP_AUM_Html_UI_Helper::instance()->input(
			'monitor_id',
			array(
				'value' => $monitor_id,
				'type'  => 'hidden',
			)
		);
	}

	if ( isset( $this->params['api_key'] ) ) {
		echo MainWP_AUM_Html_UI_Helper::instance()->input(
			'add_monitor_api_key',
			array(
				'value' => $this->params['api_key'],
				'type'  => 'hidden',
			)
		);
	}

	?>

	<div class="ui diving header"><?php echo __( 'Monitor Information', 'advanced-uptime-monito-extension' ); ?></div>

	<?php
	// Check if this is Edit form, if yes, hide fields that can't be edited by Uptime Robot API.
	if ( $edit_url_id == 0 ) :
		?>
	<div class="ui grid field monitor_type">
		<label class="six wide column middle aligned"><?php _e( 'Monitor types', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			$args = array(
				'type'    => 'select',
				'options' => MainWP_AUM_UptimeRobot_Controller::get_monitor_types(),
			);
			if ( isset( $this->params['data'][ $this->service_name ] ) ) {
				$args['value'] = $this->params['data'][ $this->service_name ]['monitor_type'];
			}
			echo MainWP_AUM_Html_UI_Helper::instance()->input( 'monitor_type', $args );
			?>
		</div>
	</div>

	<div class="ui grid field monitor_subtype" style="display:none">
		<label class="six wide column middle aligned"><?php _e( 'Monitor subtype', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			$monitors_subtypes = array(
				'1' => 'HTTP',
				'2' => 'HTTPS',
				'3' => 'FTP',
				'4' => 'SMTP',
				'5' => 'POP3',
				'6' => 'IMAP',
			);

			$args = array(
				'type'    => 'select',
				'options' => $monitors_subtypes,
			);
			if ( isset( $this->params['data'][ $this->service_name ] ) ) {
				$args['value'] = $this->params['data'][ $this->service_name ]['monitor_subtype'];
			}
			echo MainWP_AUM_Html_UI_Helper::instance()->input( 'monitor_subtype', $args );
			?>
		</div>
	</div>

	<div class="ui grid field url_monitor_keywordtype" style="display:none">
		<label class="six wide column middle aligned"><?php _e( 'Alert when', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			$args_keywt          = $args;
			$args_keywt['style'] = 'ui radio checkbox';
			$args_keywt['type']  = 'radio';
			$args_keywt['label'] = __( 'exists', 'advanced-uptime-monitor-extension' );
			$args_keywt['value'] = 1;
			echo MainWP_AUM_Html_UI_Helper::instance()->input( 'monitor_keywordtype', $args_keywt );
			echo '<br/>';
			$args_keywt['label'] = __( 'not exists', 'advanced-uptime-monitor-extension' );
			$args_keywt['value'] = 2;
			echo MainWP_AUM_Html_UI_Helper::instance()->input( 'monitor_keywordtype', $args_keywt );
			?>
		</div>
	</div>

	<div class="ui grid field url_monitor_keywordvalue" style="display:none">
		<label class="six wide column middle aligned"><?php _e( 'Keyword', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input( 'monitor_keywordvalue' );
			?>
						
		</div>
	</div>

	<?php endif; ?>

	<?php
	$style_select = 'style="display:none"';
	$style_text   = 'style="display:flex"';
	if ( isset( $this->params['checkbox_show_select'] ) && count( $other_site_urls ) >= 1 ) {
		$style_select = 'style="display:flex"';
		$style_text   = 'style="display:none"';
	} else {
		$friendly_n = '';
		if ( isset( $this->params['data'][ $this->service_name ]['url_name'] ) ) {
			$friendly_n = $this->params['data'][ $this->service_name ]['url_name'];
		}
	}
	?>

	<div class="ui grid field monitor_url_name_text" <?php echo $style_text; ?>>
		<label class="six wide column middle aligned"><?php _e( 'Friendly name', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<input type="text" name="url_name_textbox" value="<?php echo esc_attr( $friendly_n ); ?>">
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'URL (or IP)', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			if ( isset( $this->params['data'][ $this->service_name ]['url_address'] ) ) {
				echo MainWP_AUM_Html_UI_Helper::instance()->input(
					'url_address',
					array(
						'label' => '',
						'value' => $this->params['data'][ $this->service_name ]['url_address'],
					)
				);
			} else {
				echo MainWP_AUM_Html_UI_Helper::instance()->input(
					'url_address',
					array(
						'label' => '',
						'value' => 'http://',
					)
				);
			}
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Monitoring interval (in minutes)', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'monitor_interval',
				array(
					'label' => '',
					'value' => ! empty( $this->params['data'][ $this->service_name ]['monitor_interval'] ) ? $this->params['data'][ $this->service_name ]['monitor_interval'] : 5,
				)
			);
			?>
		</div>
	</div>

	<?php if ( is_array( $list_noti_contact ) && count( $list_noti_contact ) > 0 ) { ?>

		<?php
		$alert_contacts = array();
		if ( ! empty( $this->params['monitor_contacts_notification'] ) ) {
			$alert_contacts = explode( '-', $this->params['monitor_contacts_notification'] );
		} elseif ( isset( $this->params['data'][ $this->service_name ]['url_not_email'] ) && count( $this->params['data'][ $this->service_name ]['url_not_email'] ) > 0 ) {
			$alert_contacts = $this->params['data'][ $this->service_name ]['url_not_email'];
		}

		$default_contact_id = MainWP_AUM_UptimeRobot_API::instance()->get_option( 'uptime_default_notification_contact_id' );
		?>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Select alert contacts to notify', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			if ( count( $alert_contacts ) > 0 ) {
				foreach ( $list_noti_contact as $key => $val ) {
					$checked_flag = '';
					if ( in_array( $key, $alert_contacts ) ) {
						$checked_flag = 'checked="checked"';
					}

					echo '<label class="label2"><input type="checkbox" name="checkbox_contact" value="' . esc_attr( $key ) . '"' . $checked_flag . '> ' . esc_html( $val ) . '</label><br/>';
				}
			} else {

				foreach ( $list_noti_contact as $key => $val ) {
					if ( $default_contact_id == $key ) {
						$checked = empty( $this->params['data'][ $this->service_name ]['url_id'] ) ? ' checked="checked"' : '';
					} else {
						$checked = '';
					}
						echo '<label class="label2"><input type="checkbox" name="checkbox_contact" value="' . esc_attr( $key ) . '" ' . $checked . '> ' . esc_html( $val ) . '</label><br/>';
				}
			}
			?>
		</div>
	</div>

	<div class="ui diving header"><?php echo __( 'HTTP Basic Authentication Settings', 'advanced-uptime-monito-extension' ); ?></div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'HTTP Username (Optional)', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'http_username',
				array(
					'label' => '',
					'value' => ! empty( $this->params['data'][ $this->service_name ]['http_username'] ) ? $this->params['data'][ $this->service_name ]['http_username'] : '',
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
					'value' => ! empty( $this->params['data'][ $this->service_name ]['http_password'] ) ? $this->params['data'][ $this->service_name ]['http_password'] : '',
				)
			);
			?>
		</div>
	</div>

	<?php } ?>

</div>


<input type="hidden" name="monitor_contacts_notification" value="<?php echo ( isset( $this->params['monitor_contacts_notification'] ) ? esc_attr( $this->params['monitor_contacts_notification'] ) : esc_attr( $default_contact_id ) ); ?>" />
<?php
$timestamp   = time();
$submit_text = empty( $this->params['data'][ $this->service_name ]['url_id'] ) ? __( 'Create Monitor', 'advanced-uptime-monitor-extension' ) : __( 'Save Monitor', 'advanced-uptime-monitor-extension' );
?>
<div class="ui divider"></div>
<input type="button" value="<?php echo $submit_text; ?>" class="ui green button" id="aum_edit_monitor_button_<?php echo $timestamp; ?>">
<input type="hidden" name="action" value="mainwp_advanced_uptime_edit_monitor">
<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'mainwp_aum_nonce_monitors_page' ); ?>">
</form>
<!-- End Add New Monitor Form -->

<script type="text/javascript">
	jQuery(document).ready(function () {
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
			contacts_chossen = contacts_chossen.slice(0, -1);
			jQuery('input[name=monitor_contacts_notification]').val(contacts_chossen);

			if (jQuery('input[name=monitor_contacts_notification]').val() == '')
			{
				errors.push('Please select at least one Alert Contact');
			}
			if (!jQuery('input[name=checkbox_show_select]').is(':checked') && jQuery('input[name=url_name_textbox]').val() == '')
			{
				errors.push('Please enter Monitor Friendly Name');

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
					jQuery('#mainwp-create-edit-monitor-modal #popup_message_error_box').html('Unexpected error. Please try again.');
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
				jQuery('tr.monitor_url_name_text').hide(); 
				jQuery('tr.monitor_url_name').show(); 
				jQuery('tr.monitor_url_name div select').prop('selectedIndex', 0);
			} else {
				jQuery('tr.monitor_url_name').hide(); 
				jQuery('tr.monitor_url_name_text').show(); 
				jQuery('tr.monitor_url_name_text input').val('');
			}
		});

		jQuery('.monitor_type select').change(function () {
			if (jQuery(this).val() == '4')
				jQuery('.monitor_subtype').show();
			else
				jQuery('.monitor_subtype').hide();

			if (jQuery(this).val() == '2') {
				jQuery('.url_monitor_keywordtype').show();
				jQuery('.url_monitor_keywordvalue').show();
			} else {
				jQuery('.url_monitor_keywordtype').hide();
				jQuery('.url_monitor_keywordvalue').hide();
			}
		})

		jQuery('.monitor_url_name').change(function ()
		{
			var select = jQuery('.monitor_url_name select option:selected').val();
			jQuery('input#UptimeUrlUrlAddress').val(select);
		})
		<?php
		if ( isset( $this->params['data'][ $this->service_name ] ) && isset( $this->params['data'][ $this->service_name ]['monitor_type'] ) && $this->params['data'][ $this->service_name ]['monitor_type'] == '4' ) {
			echo "jQuery('.monitor_subtype').show();";
		}

		if ( isset( $this->params['data'][ $this->service_name ] ) && isset( $this->params['data'][ $this->service_name ]['monitor_type'] ) && $this->params['data'][ $this->service_name ]['monitor_type'] == '2' ) {
			echo "jQuery('.url_monitor_keywordtype').show();jQuery('.url_monitor_keywordvalue').show();";
		}
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
	});
</script>
