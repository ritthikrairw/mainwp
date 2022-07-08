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

	?>
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'URL to monitor', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			if ( isset( $data['url_address'] ) ) {
				$url_address = $data['url_address'];
			} else {
				$url_address = 'https://';
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
		<label class="six wide column middle aligned"><?php _e( 'Alert us when the URL above', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			$endp_type = ! empty( $data['endpoint_type'] ) ? $data['endpoint_type'] : 'status';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'endpoint_type',
				array(
					'type'     => 'select',
					'label'    => '',
					'id'       => 'aum_betteruptime_edit_endpoint_type',
					'value'    => $endp_type,
					'class'    => 'mainwp-aum-input-select',
					'options'  => array(
						'status'          => 'Becomes unavailable',
						'keyword'         => 'Doesn\'t contain keyword',
						'keyword_absence' => 'Contains a keyword',
						'ping'            => 'Doesn\'t respond to ping',
						'tcp'             => 'Doesn\'t respond at a TCP port',
						'udp'             => 'Doesn\'t respond at an UDP port',
						'smtp'            => 'SMTP server doesn\'t respond',
						'pop'             => 'POP3 server doesn\'t respond',
						'imap'            => 'IMAP server doesn\'t respond',
						'dns'             => 'DNS server doesn\'t respond (beta)',
					),
					'onchange' => 'mainwp_aum_js_betteruptime_endpoint_type_onchange(this)',
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" <?php echo ( 'keyword' == $endp_type || 'keyword_absence' == $endp_type || 'tcp' == $endp_type || 'udp' == $endp_type || 'dns' == $endp_type ) ? '' : 'style="display:none"'; ?> id="mainwp_aum_js_betteruptime_showhide_required_keyword">
		<label class="six wide column middle aligned"></label>
		<div class="ten wide column">
			<?php
			echo '<span class="title">' . esc_html__( 'Keyword to find in page', 'advanced-uptime-monitor-extension' ) . '</span>';
			echo '<br/>';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'required_keyword',
				array(
					'label' => '',
					'value' => ! empty( $data['required_keyword'] ) ? $data['required_keyword'] : '',
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" <?php echo ( 'tcp' == $endp_type || 'udp' == $endp_type || 'smtp' == $endp_type || 'pop' == $endp_type || 'imap' == $endp_type ) ? '' : 'style="display:none"'; ?> id="mainwp_aum_js_betteruptime_showhide_port">
		<label class="six wide column middle aligned"></label>
		<div class="ten wide column">
			<?php
			echo '<span class="title">' . esc_html__( 'Port', 'advanced-uptime-monitor-extension' ) . '</span>';
			echo '<br/>';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'port',
				array(
					'label' => '',
					'value' => ! empty( $data['port'] ) ? $data['port'] : '',
				)
			);
			?>
		</div>
	</div>
	
	<div class="ui grid field" <?php echo 'dns' == $endp_type ? '' : 'style="display:none"'; ?> id="mainwp_aum_js_betteruptime_showhide_request_body">
		<label class="six wide column middle aligned"></label>
		<div class="ten wide column">
			<?php
			'<span class="title">' . esc_html_e( 'Domain to query the DNS server with', 'advanced-uptime-monitor-extension' ) . '</span>';
			echo '<br/>';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'request_body',
				array(
					'label' => '',
					'value' => ! empty( $data['request_body'] ) ? $data['request_body'] : '',
				)
			);
			?>
		</div>
	</div>
	
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'On-call escalation', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'email',
				array(
					'type'              => 'checkbox',
					'label'             => 'Send e-mail',
					'value'             => '1',
					'checked'           => ( ( ! empty( $data['email'] ) && 1 == $data['email'] ) || ! $edit_url_id ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
				)
			);
			echo '<br />';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'call',
				array(
					'type'              => 'checkbox',
					'label'             => 'Call',
					'value'             => '1',
					'checked'           => ( ! empty( $data['call'] ) && 1 == $data['call'] ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
				)
			);
			echo '<br />';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'sms',
				array(
					'type'              => 'checkbox',
					'label'             => 'Send SMS',
					'value'             => '1',
					'checked'           => ( ! empty( $data['sms'] ) && 1 == $data['sms'] ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
				)
			);
			echo '<br />';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'push',
				array(
					'type'              => 'checkbox',
					'label'             => 'Push notification',
					'value'             => '1',
					'checked'           => ( ! empty( $data['push'] ) && 1 == $data['push'] ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
				)
			);
			echo '<br />';
			echo '<em>' . esc_html__( 'Call, SMS and Push notification options are available only for premium Better Uptime users.', 'advanced-uptime-monitor-extension' ) . '<em><br />';
			echo '<br />' . esc_html__( 'If the on-call person doesn\'t acknowledge the incident', 'advanced-uptime-monitor-extension' ) . '<br />';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'team_wait',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_team_wait',
					'value'   => ! empty( $data['team_wait'] ) ? $data['team_wait'] : 'HTTP',
					'options' => array(
						''    => 'Do nothing',
						'0'   => 'Immediately alert all other team members',
						'180' => 'Within 3 minutes, alert all other team members',
						'300' => 'Within 5 minutes, alert all other team members',
						'600' => 'Within 10 minutes, alert all other team members',
					),
				)
			);
			?>
		</div>
	</div>
	
	<h2 class="ui diving header"><?php echo __( 'Advanced Settings', 'advanced-uptime-monito-extension' ); ?></h2>
	
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Pronounceable monitor name', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'url_name',
				array(
					'label' => '',
					'value' => ! empty( $data['url_name'] ) ? $data['url_name'] : '',
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Recovery period', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'recovery_period',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_recovery_period',
					'value'   => ! empty( $data['recovery_period'] ) ? $data['recovery_period'] : 180,
					'options' => array(
						'0'    => 'Immediate recovery',
						'60'   => '1 minute',
						'180'  => '3 minutes',
						'300'  => '5 minutes',
						'900'  => '15 minutes',
						'1800' => '30 minutes',
						'3600' => '1 hour',
						'7200' => '2 hours',
					),
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field mainwp_aum_js_betteruptime_show_response" style="display: none;">
		<label class="six wide column middle aligned"><?php _e( 'Response timeout', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'tcp_timeout',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_tcp_timeout',
					'value'   => ! empty( $data['tcp_timeout'] ) ? $data['tcp_timeout'] : 180,
					'options' => array(
						'5000' => '5 seconds',
						'3000' => '3 seconds',
						'2000' => '2 seconds',
						'1000' => '1 seconds',
						'500'  => '500 milliseconds',
					),
				)
			);
			?>
		</div>
	</div>
	
	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Confirmation period', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'confirmation_period',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_confirmation_period',
					'value'   => ! empty( $data['confirmation_period'] ) ? $data['confirmation_period'] : 0,
					'options' => array(
						'0'   => 'Immediate start',
						'5'   => '5 seconds',
						'10'  => '10 seconds',
						'15'  => '15 seconds',
						'30'  => '30 seconds',
						'60'  => '1 minute',
						'120' => '2 minutes',
						'180' => '3 minutes',
						'300' => '5 minutes',
					),
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field mainwp_aum_js_betteruptime_hide_response">
		<label class="six wide column middle aligned"><?php _e( 'Check frequency', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			$opts = MainWP_AUM_BetterUptime_Controller::get_monitor_interval_options();
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'monitor_interval',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_monitor_interval',
					'value'   => ! empty( $data['monitor_interval'] ) ? $data['monitor_interval'] : 180,
					'options' => $opts,
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field mainwp_aum_js_betteruptime_hide_response">
		<label class="six wide column middle aligned"><?php _e( 'Domain expiration', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'domain_expiration',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_domain_expiration',
					'value'   => ! empty( $data['domain_expiration'] ) ? $data['domain_expiration'] : '',
					'options' => array(
						''   => __( 'Don\'t check for domain expiration', 'advanced-uptime-monitor-extension' ),
						'1'  => 'Alert 1 day before',
						'2'  => 'Alert 2 day before',
						'3'  => 'Alert 3 day before',
						'7'  => 'Alert 7 day before',
						'14' => 'Alert 14 day before',
						'30' => 'Alert 1 month before',
						'60' => 'Alert 2 month before',
					),
				)
			);
			?>
		</div>
	</div>
	<div class="ui grid field mainwp_aum_js_betteruptime_hide_response">
		<label class="six wide column middle aligned"><?php _e( 'SSL/TLS verification', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			$verify_ssl = isset( $data['verify_ssl'] ) ? $data['verify_ssl'] : 'true';
			if ( '' == $verify_ssl ) {
				$verify_ssl = 'false';
			}
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'verify_ssl',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_verify_ssl',
					'value'   => $verify_ssl,
					'options' => array(
						'true'  => 'On',
						'false' => 'Off',
					),
				)
			);
			?>
		</div>

		<label class="six wide column middle aligned"><?php _e( 'SSL expiration', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'ssl_expiration',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_ssl_expiration',
					'value'   => ! empty( $data['ssl_expiration'] ) ? $data['ssl_expiration'] : 'true',
					'options' => array(
						''   => __( 'Don\'t check for SSL expiration', 'advanced-uptime-monitor-extension' ),
						'1'  => 'Alert 1 day before',
						'2'  => 'Alert 2 day before',
						'3'  => 'Alert 3 day before',
						'7'  => 'Alert 7 day before',
						'14' => 'Alert 14 day before',
						'30' => 'Alert 1 month before',
						'60' => 'Alert 2 month before',
					),
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field mainwp_aum_js_betteruptime_hide_response" >
		<label class="six wide column middle aligned"><?php _e( 'HTTP method used to make the request', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'http_method',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_http_method',
					'value'   => ! empty( $data['http_method'] ) ? $data['http_method'] : 'get',
					'options' => array(
						'get'   => 'GET',
						'post'  => 'POST',
						'patch' => 'PATCH',
						'put'   => 'PUT',
					),
				)
			);
			?>
		</div>

		<label class="six wide column middle aligned"><?php _e( 'Request timeout', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'request_timeout',
				array(
					'type'    => 'select',
					'label'   => '',
					'class'   => 'mainwp-aum-input-select',
					'id'      => 'aum_betteruptime_edit_request_timeout',
					'value'   => ! empty( $data['request_timeout'] ) ? $data['request_timeout'] : 30,
					'options' => array(
						'60' => '1 minute',
						'45' => '45 seconds',
						'30' => '30 seconds',
						'15' => '15 seconds',
						'10' => '10 seconds',
						'5'  => '5 seconds',
						'3'  => '3 seconds',
						'2'  => '2 seconds',
					),
				)
			);
			?>
		</div>
	
		<label class="six wide column middle aligned"><?php _e( 'Request body for POST, PUT, and PATCH requests', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'request_body',
				array(
					'type'        => 'textarea',
					'label'       => '',
					'id'          => 'aum_betteruptime_edit_request_body',
					'value'       => ! empty( $data['request_body'] ) ? $data['request_body'] : '',
					'placeholder' => __( 'parameter1=first_value&parameter2=another_value', 'advanced-uptime-monitor-extension' ),
				)
			);
			?>
		</div>
	</div>

	<div class="ui grid field mainwp_aum_js_betteruptime_hide_response" >
		<label class="six wide column middle aligned"><?php _e( 'Follow redirects', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'follow_redirects',
				array(
					'type'              => 'checkbox',
					'label'             => '',
					'value'             => 1,
					'checked'           => ! empty( $data['follow_redirects'] ) ? true : false,
					'class'             => '',
					'with_hidden_input' => false,
				)
			);
			?>
		</div>
	</div>
	
	<?php
	$request_headers = json_decode( $data['request_headers'], true );
	if ( ! is_array( $request_headers ) ) {
		$request_headers = array();
	}
	?>
	<div class="ui grid field mainwp_aum_js_betteruptime_hide_response" >
		<?php
		$count = count( $request_headers );
		foreach ( $request_headers as $item_idx => $item_headers ) {
			?>
				<label class="six wide column middle aligned"><?php _e( 'Header name', 'advanced-uptime-monitor-extension' ); ?><?php echo $item_idx ? ' ' . $item_idx : ''; ?></label>
				<div class="ten wide column ui">
					<?php
						echo MainWP_AUM_Html_UI_Helper::instance()->input(
							'request_headers',
							array(
								'label'       => '',
								'value'       => ! empty( $item_headers['name'] ) ? $item_headers['name'] : '',
								'placeholder' => __( 'Authorization', 'advanced-uptime-monitor-extension' ),
								'name_suffix' => '[' . $item_idx . '][name]',
							)
						);
					?>
				</div>			
				<label class="six wide column middle aligned"><?php _e( 'Header value', 'advanced-uptime-monitor-extension' ); ?><?php echo $item_idx ? ' ' . $item_idx : ''; ?></label>
				<div class="ten wide column ui">
					<?php
						echo MainWP_AUM_Html_UI_Helper::instance()->input(
							'request_headers',
							array(
								'label'       => '',
								'value'       => ! empty( $item_headers['value'] ) ? $item_headers['value'] : '',
								'placeholder' => __( 'Bearer 12345678abcdef==', 'advanced-uptime-monitor-extension' ),
								'name_suffix' => '[' . $item_idx . '][value]',
							)
						);
					?>
				</div>
				<?php
				echo MainWP_AUM_Html_UI_Helper::instance()->input(
					'request_headers',
					array(
						'type'        => 'hidden',
						'value'       => ! empty( $item_headers['id'] ) ? $item_headers['id'] : '',
						'name_suffix' => '[' . $item_idx . '][id]',
					)
				);
				?>
		<?php } ?>
			<label class="six wide column middle aligned"><?php _e( 'Header name', 'advanced-uptime-monitor-extension' ); ?><?php echo $count ? ' ' . $count : ''; ?></label>
			<div class="ten wide column ui">
				<?php
					echo MainWP_AUM_Html_UI_Helper::instance()->input(
						'request_headers',
						array(
							'label'       => '',
							'value'       => '',
							'placeholder' => __( 'Authorization', 'advanced-uptime-monitor-extension' ),
							'name_suffix' => '[' . $count . '][name]',
						)
					);
					?>
			</div>			
			<label class="six wide column middle aligned"><?php _e( 'Header value', 'advanced-uptime-monitor-extension' ); ?><?php echo $count ? ' ' . $count : ''; ?></label>
			<div class="ten wide column ui">
				<?php
					echo MainWP_AUM_Html_UI_Helper::instance()->input(
						'request_headers',
						array(
							'label'       => '',
							'value'       => '',
							'placeholder' => __( 'Bearer 12345678abcdef==', 'advanced-uptime-monitor-extension' ),
							'name_suffix' => '[' . $count . '][value]',
						)
					);
					?>
			</div>
	</div>


	<div class="ui grid field mainwp_aum_js_betteruptime_hide_response" >
		<label class="six wide column middle aligned"><?php _e( 'Basic HTTP authentication username', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
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
	
		<label class="six wide column middle aligned"><?php _e( 'Basic HTTP authentication password', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
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

	<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Maintenance window between', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column ui">
			<div class="ui calendar" id="aum_start_time" >
				<div class="ui input left icon">
					<i class="calendar icon"></i>
					<?php
					echo MainWP_AUM_Html_UI_Helper::instance()->input(
						'maintenance_from',
						array(
							'label'       => '',
							'value'       => ! empty( $data['maintenance_from'] ) ? $data['maintenance_from'] : '',
							'placeholder' => __( 'Date', 'mainwp' ),
						)
					);
					?>
				</div>
			</div>
			<div class="ui fitted hidden divider"></div>
			<div class="ui calendar" id="aum_end_time" >
				<div class="ui input left icon">
					<i class="calendar icon"></i>
					<?php
					echo MainWP_AUM_Html_UI_Helper::instance()->input(
						'maintenance_to',
						array(
							'label'       => '',
							'value'       => ! empty( $data['maintenance_to'] ) ? $data['maintenance_to'] : '',
							'placeholder' => __( 'Date', 'mainwp' ),
						)
					);
					?>
				</div>
			</div>
		</div>
	</div>

</div>

<div class="ui grid field" >
		<label class="six wide column middle aligned"><?php _e( 'Regions', 'advanced-uptime-monitor-extension' ); ?></label>
		<div class="ten wide column">
			<?php

			$regions = ! empty( $data['regions'] ) ? json_decode( $data['regions'], true ) : array();
			if ( empty( $regions ) ) {
				$regions = array( 'us', 'eu', 'as', 'au' );
			}

			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'regions',
				array(
					'type'              => 'checkbox',
					'label'             => 'North America',
					'value'             => 'us',
					'checked'           => in_array( 'us', $regions ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
					'name_suffix'       => '[]',
				)
			);
			echo '<br />';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'regions',
				array(
					'type'              => 'checkbox',
					'label'             => 'Europe',
					'value'             => 'eu',
					'checked'           => in_array( 'eu', $regions ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
					'name_suffix'       => '[]',
				)
			);
			echo '<br />';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'regions',
				array(
					'type'              => 'checkbox',
					'label'             => 'Asia',
					'value'             => 'as',
					'checked'           => in_array( 'as', $regions ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
					'name_suffix'       => '[]',
				)
			);
			echo '<br />';
			echo MainWP_AUM_Html_UI_Helper::instance()->input(
				'regions',
				array(
					'type'              => 'checkbox',
					'label'             => 'Australia',
					'value'             => 'au',
					'checked'           => in_array( 'au', $regions ) ? true : false,
					'class'             => 'mainwp-aum-input-checkbox',
					'with_hidden_input' => false,
					'name_suffix'       => '[]',
				)
			);
			?>
		</div>
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

			// init calendar
			jQuery( '#aum_start_time' ).calendar({
					type: 'time',
					initialDate: function() {
					}(),
					monthFirst: false,
					formatter: {
						date: function (date) {
							if (!date) return '';
							var jj = date.getDate();
							var mm = date.getMonth() + 1;
							var aa = date.getFullYear();
							return aa + '-' + mm + '-' + jj;
						}
					},
					onChange: function ( attemptedDate, textDate ) {
					}
			});

			// init calendar.
			jQuery( '#aum_end_time' ).calendar({
					type: 'time',
					initialDate: function() {
					}(),
					monthFirst: false,
					formatter: {
						date: function (date) {
							if (!date) return '';
							var jj = date.getDate();
							var mm = date.getMonth() + 1;
							var aa = date.getFullYear();
							return aa + '-' + mm + '-' + jj;
						}
					},
					onChange: function ( attemptedDate, textDate ) {
					}
			});


			jQuery( '.ui.dropdown').dropdown();
			jQuery('.ui.checkbox:not(.not-auto-init)').checkbox();
			on_remove_contact_row = function( elem ){
				jQuery(elem).closest('div.remove-contact-row').remove();
				console.log(jQuery(elem).closest('.remove-contact-row'));
				return true;
			}
		});	
		
		jQuery( '#aum_edit_monitor_button_<?php echo $timestamp; ?>' ).on('click', function (event) {
				event.preventDefault();
				var errors = [];
				jQuery('div.error').hide();
				
				if ( jQuery('input[name="data[site24x7][url_address]"]').val() == '' )
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
