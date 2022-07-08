<?php

class MainWP_Wordfence_Firewall {

	public static function gen_individual_firewall_basic() {

		$site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;

		if ( empty( $site_id ) ) {
			return;
		}

		$w = MainWP_Wordfence_Setting::get_instance()->load_configs( $site_id );

		if ( empty( $w ) ) {
			return;
		}

		$open_url     = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site';
		$wafConfigURL = $open_url . '&websiteid=' . $site_id . '&open_location=' . base64_encode( '/wp-admin/admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend' );
		$settings     = array(
			'wafStatus'                      => $w->get( 'wafStatus', 'disabled' ),
			'learningModeGracePeriod'        => $w->get( 'learningModeGracePeriod' ),
			'learningModeGracePeriodEnabled' => $w->get( 'learningModeGracePeriodEnabled' ),
		);
		?>
	<div class="ui dividing header"><?php echo __( 'Basic Firewall Options', 'mainwp-wordfence-extension' ); ?></div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Protection level', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		Basic WordPress Protection
	  </div>
	</div>

		<?php self::gen_firewall_status_settings( $settings, $site_id ); ?>

	<script type="text/javascript">
	  ( function($) {
		$( '#waf-config-form' ).on( "submit", function() {
		  MWP_WFAD.wafConfigSave('config', $(this).serializeArray(), null, null,  <?php esc_html_e( $site_id ); ?>);
		} );
	  } );
	</script>
		<?php
	}

	public static function gen_advanced_firewall_options( $individual = false ) {

		$site_id = isset( $_GET['id'] ) ? $_GET['id'] : null;

		if ( $individual && empty( $site_id ) ) {
			return;
		}

		$w = MainWP_Wordfence_Setting::get_instance()->load_configs( $site_id );

		if ( empty( $w ) ) {
			return;
		}

		$dashboard_ip = $_SERVER['SERVER_ADDR'];
		$your_ip      = $_SERVER['REMOTE_ADDR'];
		$white_list   = $w->getHTML( 'whitelisted' );

		if ( empty( $white_list ) ) {
			$white_list = $dashboard_ip;
		} else {
			if ( strpos( $white_list, $dashboard_ip ) === false ) {
				$white_list = $dashboard_ip . ',' . $white_list;
			}
		}
		?>
	<div class="ui dividing header"><?php echo __( 'Advanced Firewall Options', 'mainwp-wordfence-extension' ); ?></div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Delay IP and Country blocking until after WordPress and plugins have loaded (only process firewall rules early)', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="disableWAFIPBlocking" name="disableWAFIPBlocking" value="1" <?php $w->cb( 'disableWAFIPBlocking' ); ?> />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Allowlisted IP addresses that bypass all rules', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<input type="text" name="whitelisted" id="whitelisted" value="<?php echo $white_list; ?>" />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( '', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		We recommend whitelisting your Dashboard and your IP before making changes<br>
		Dashboard IP: <?php echo $dashboard_ip; ?><br/>
		Your IP: <?php echo $your_ip; ?><br/>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( '', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<span  style="color: #999;">Whitelisted IP's must be separated by commas. You can
			specify ranges using the following format: 123.23.34.[1-50]<br/>Wordfence automatically
			whitelists <a href="http://en.wikipedia.org/wiki/Private_network" target="_blank">private
			networks</a> because these are not routable on the public Internet.</span>
	  </div>
	</div>
		<?php

		$whlServices = $w->get( 'whitelistedServices' );

		if ( is_string( $whlServices ) && ! empty( $whlServices ) ) {
			$whlServices = @json_decode( $whlServices, true );
		}

		if ( ! is_array( $whlServices ) ) {
			$whlServices = array();
		}

		?>
	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Allowlisted services', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
			<div class="ui toggle checkbox">
				<input type="checkbox" id="whitelistedServices_sucuri" name="whitelistedServices_sucuri" value="1" <?php echo isset( $whlServices['sucuri'] ) && ! empty( $whlServices['sucuri'] ) ? 'checked' : ''; ?> /><label>Sucuri</label>
			</div>
			<div class="ui toggle checkbox">
				<input type="checkbox" id="whitelistedServices_facebook" name="whitelistedServices_facebook" value="1" <?php echo isset( $whlServices['facebook'] ) && ! empty( $whlServices['facebook'] ) ? 'checked' : ''; ?> /><label>Facebook</label>
			</div>
			<div class="ui toggle checkbox">
				<input type="checkbox" id="whitelistedServices_uptimerobot" name="whitelistedServices_uptimerobot" value="1" <?php echo isset( $whlServices['uptimerobot'] ) && ! empty( $whlServices['uptimerobot'] ) ? 'checked' : ''; ?> /><label>Uptime Robot</label>
			</div>
			<div class="ui toggle checkbox">
				<input type="checkbox" id="whitelistedServices_statuscake" name="whitelistedServices_statuscake" value="1" <?php echo isset( $whlServices['statuscake'] ) && ! empty( $whlServices['statuscake'] ) ? 'checked' : ''; ?> /><label>StatusCake</label>
			</div>
			<div class="ui toggle checkbox">
				<input type="checkbox" id="whitelistedServices_managewp" name="whitelistedServices_managewp" value="1" <?php echo isset( $whlServices['managewp'] ) && ! empty( $whlServices['managewp'] ) ? 'checked' : ''; ?> /><label>ManageWP</label>
			</div>
			<div class="ui toggle checkbox">
				<input type="checkbox" id="whitelistedServices_seznam" name="whitelistedServices_seznam" value="1" <?php echo isset( $whlServices['seznam'] ) && ! empty( $whlServices['seznam'] ) ? 'checked' : ''; ?> /><label>Seznam Search Engine</label>
			</div>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Immediately block IP\'s that access these URLs', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<input type="text" name="bannedURLs" id="bannedURLs" value="<?php echo $w->getHTML( 'bannedURLs' ); ?>" />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( '', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<span  style="color: #999;">Separate multiple URL's with commas. If you see an attacker
			repeatedly probing your site for a known vulnerability you can use this to immediately block them.<br/>
			All URL's must start with a '/' without quotes and must be relative. e.g. /badURLone/,
			/bannedPage.html, /dont-access/this/URL/</span>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Ignored IP addresses for Wordfence Web Application Firewall alerting', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<textarea id="wafAlertWhitelist" class="mwp-wf-form-control" rows="4" name="wafAlertWhitelist"><?php echo esc_html( preg_replace( '/,/', "\n", $w->get( 'wafAlertWhitelist' ) ) ); ?></textarea>
	  </div>
	</div>
		<?php
	}

	public static function gen_firewall_rules() {
		$current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : null;
		if ( $current_site_id ) {
			?>

	<div id="waf-rules-wrapper"></div>
		<?php } ?>

	<br/>
	<button type="button" onclick="<?php echo $current_site_id ? 'MWP_WFAD.wafUpdateRules(' . $current_site_id . ');' : 'MWP_WFAD.bulkWAFUpdateRules()'; ?>" class="ui green button">Manually Refresh Rules</button>

		<?php
		if ( $current_site_id ) {
			?>
	<em id="waf-rules-next-update"></em>
			<?php
		}

	}

	public static function gen_whitelisted_url() {

		$site_id = isset( $_GET['id'] ) ? $_GET['id'] : null;

		if ( empty( $site_id ) ) {
			?>
		<button type="button" onclick="MWP_WFAD.bulkWAFUpdateRules()" class="ui big green button">Manually Refresh Rules</button>
			<?php
			return;
		}

		$w = MainWP_Wordfence_Setting::get_instance()->load_configs( $site_id );

		if ( empty( $w ) ) {
			return;
		}

		$wafData = array();
		if ( $site_id ) {
			$open_url       = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site';
			$isPaid         = false;
			$extra_settings = $settings = array();
			if ( $site_id ) {
				$wafConfigURL   = $open_url . '&websiteid=' . $site_id . '&open_location=' . base64_encode( '/wp-admin/admin.php?page=WordfenceWAF&wafAction=configureAutoPrepend' );
				$site_settings  = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $site_id );
				$extra_settings = unserialize( base64_decode( $site_settings->extra_settings ) );
				$settings       = unserialize( $site_settings->settings );
				$isPaid         = $site_settings->isPaid;
			} else {
				$wafConfigURL = '#';
			}

			if ( ! is_array( $settings ) ) {
				$settings = array();
			}

			if ( isset( $extra_settings['wafData'] ) ) {
				$wafData = $extra_settings['wafData'];
			}
		}
		?>

	<div id="waf-settings-loading">
	  <div class="ui active inverted dimmer">
		<div class="ui text loader">Loading...</div>
	  </div>
	</div>
	<div class="ui dividing header">Firewall Rules and Whitelisted URLs for</div>
		<?php
		echo MainWP_wfView::create( 'waf/option-rules', array( 'site_id' => $site_id ) )->render();
		echo MainWP_wfView::create( 'waf/option-whitelist', array() )->render();
		?>
	<div id="waf-monitor-requests-wrapper"></div>
	<div class="ui divider"></div>
	<a id="mwp-wf-save-changes" class="ui green basic button" style="display: none" href="#"><?php _e( 'Save Changess', 'mainwp-wordfence-extension' ); ?></a>

<script type="text/x-jquery-template" id="waf-whitelisted-urls-tmpl">
	<div class="whitelist-table-container">
		<table class="wf-striped-table whitelist-table">
			<thead>
			<tr>
				<th style="width: 2%;text-align: center"><div class="wf-whitelist-bulk-select wf-option-checkbox"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></div></th>
				<th style="width: 5%;"><?php _e( 'Enabled', 'wordfence' ); ?></th>
				<th><?php _e( 'URL', 'wordfence' ); ?></th>
				<th><?php _e( 'Param', 'wordfence' ); ?></th>
				<th><?php _e( 'Created', 'wordfence' ); ?></th>
				<th><?php _e( 'Source', 'wordfence' ); ?></th>
				<th><?php _e( 'User', 'wordfence' ); ?></th>
				<th><?php _e( 'IP', 'wordfence' ); ?></th>
			</tr>
			</thead>
			{{if whitelistedURLParams.length > 5}}
			<tfoot>
			<tr>
				<th style="width: 2%;text-align: center"><div class="wf-whitelist-bulk-select wf-option-checkbox"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></div></th>
				<th style="width: 5%;"><?php _e( 'Enabled', 'wordfence' ); ?></th>
				<th><?php _e( 'URL', 'wordfence' ); ?></th>
				<th><?php _e( 'Param', 'wordfence' ); ?></th>
				<th><?php _e( 'Created', 'wordfence' ); ?></th>
				<th><?php _e( 'Source', 'wordfence' ); ?></th>
				<th><?php _e( 'User', 'wordfence' ); ?></th>
				<th><?php _e( 'IP', 'wordfence' ); ?></th>
			</tr>
			{{/if}}
			</tfoot>
			<tbody>
			{{each(idx, whitelistedURLParam) whitelistedURLParams}}
			<tr data-index="${idx}" data-adding="{{if (whitelistedURLParam.adding)}}1{{else}}0{{/if}}" data-key="${whitelistedURLParam.path}|${whitelistedURLParam.paramKey}">
				<td style="text-align: center;"><div class="wf-whitelist-table-bulk-checkbox wf-option-checkbox"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></div></td>
				<td style="text-align: center;"><div class="wf-whitelist-item-enabled wf-option-checkbox{{if (!whitelistedURLParam.data.disabled)}} wf-checked{{/if}}" data-original-value="{{if (!whitelistedURLParam.data.disabled)}}1{{else}}0{{/if}}"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></div></td>
				<td data-column="url">
					<input name="replaceWhitelistedPath" type="hidden" value="${whitelistedURLParam.path}">
					<span class="whitelist-display">${MWP_WFAD.htmlEscape(MWP_WFAD.base64_decode(whitelistedURLParam.path))}</span>
					<input name="whitelistedPath" class="whitelist-edit whitelist-path" type="text"
						   value="${MWP_WFAD.htmlEscape(MWP_WFAD.base64_decode(whitelistedURLParam.path))}">
				</td>
				<td data-column="param">
					<input name="replaceWhitelistedParam" type="hidden" value="${whitelistedURLParam.paramKey}">
					<span class="whitelist-display">${MWP_WFAD.htmlEscape(MWP_WFAD.base64_decode(whitelistedURLParam.paramKey))}</span>
					<input name="whitelistedParam" class="whitelist-edit whitelist-param-key"
						   type="text" value="${MWP_WFAD.htmlEscape(MWP_WFAD.base64_decode(whitelistedURLParam.paramKey))}">
				</td>
				<td>
					{{if (whitelistedURLParam.data.timestamp)}}
					${MWP_WFAD.dateFormat((new Date(whitelistedURLParam.data.timestamp * 1000)))}
					{{else}}
					-
					{{/if}}
				</td>
				<td data-column="source">
					{{if (whitelistedURLParam.data.description)}}
					${whitelistedURLParam.data.description}
					{{else}}
					-
					{{/if}}
				</td>
				<td data-column="user">
					{{if (whitelistedURLParam.data.userID)}}
					{{if (whitelistedURLParam.data.username)}}
					${whitelistedURLParam.data.username}
					{{else}}
					${whitelistedURLParam.data.userID}
					{{/if}}
					{{else}}
					-
					{{/if}}
				</td>
				<td data-column="ip">
					{{if (whitelistedURLParam.data.ip)}}
					${whitelistedURLParam.data.ip}
					{{else}}
					-
					{{/if}}
				</td>
			</tr>
			{{/each}}
			{{if (whitelistedURLParams.length == 0)}}
			<tr>
				<td colspan="8"><?php _e( 'No whitelisted URLs currently set.', 'wordfence' ); ?></td>
			</tr>
			{{/if}}
			</tbody>
		</table>
	</div>
</script>

<script type="text/x-jquery-template" id="waf-rules-tmpl">
	<table class="wf-striped-table">
		<thead>
		<tr>
			<th style="width: 5%"></th>
			<th><?php _e( 'Category', 'wordfence' ); ?></th>
			<th><?php _e( 'Description', 'wordfence' ); ?></th>
		</tr>
		</thead>
		<tbody>
		{{each(idx, rule) rules}}
		<tr data-rule-id="${rule.ruleID}" data-original-value="{{if (!disabledRules[rule.ruleID])}}1{{else}}0{{/if}}">
			<td style="text-align: center">
				<div class="wf-rule-toggle wf-boolean-switch{{if (!disabledRules[rule.ruleID])}} wf-active{{/if}}"><a href="#" class="wf-boolean-switch-handle"></a></div>
			</td>
			<td>${rule.category}</td>
			<td>${rule.description}</td>
		</tr>
		{{/each}}
		{{if (rules.length == 0)}}
		<tr>
			<td colspan="4"><?php _e( 'No rules currently set.', 'wordfence' ); ?>
			</td>
		</tr>
		{{/if}}
		</tbody>
		<tfoot>
		{{if (ruleCount >= 10)}}
		<tr id="waf-show-all-rules">
			<td class="wf-center" colspan="4"><a href="#" id="waf-show-all-rules-button"><?php _e( 'SHOW ALL RULES', 'wordfence' ); ?></a></td>
		</tr>
		{{/if}}
		</tfoot>
	</table>
</script>

<script type="text/x-jquery-template" id="waf-monitor-requests-tmpl">
	<ul class="wf-option wf-option-toggled-multiple">
		<li class="wf-option-title">Monitor background requests from an administrator's web browser for false positives</li>
		<li class="wf-option-checkboxes">
			<ul data-option="ajaxWatcherDisabled_front" data-enabled-value="0" data-disabled-value="1" data-original-value="{{if (front)}}1{{else}}0{{/if}}">
					<li class="wf-option-checkbox {{if (!front)}}wf-checked{{/if}}"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></li>
					<li class="wf-option-title">Front-end Website</li>
			</ul>
			<ul data-option="ajaxWatcherDisabled_admin" data-enabled-value="0" data-disabled-value="1" data-original-value="{{if (admin)}}1{{else}}0{{/if}}">
					<li class="wf-option-checkbox {{if (!admin)}}wf-checked{{/if}}"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></li>
					<li class="wf-option-title">Admin Panel</li>
			</ul>
		</li>
	</ul>
</script>

<script type="text/javascript">
		(function($) {

			$('#mwp-wf-save-changes').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				MWP_WFAD.saveOptions(<?php echo intval( $site_id ); ?>, function() {
					MWP_WFAD.pendingChanges = {};
					MWP_WFAD.updatePendingChanges();
					window.location.reload(true);
				});
			});

			$(document).ready(function() {
				MWP_WFAD.load_wafData(function() {
					MWP_WFAD.wafConfigPageRender();
				}, <?php echo intval( $site_id ); ?>);
			});

			function whitelistCheckAllVisible() {
				$('.wf-whitelist-bulk-select.wf-option-checkbox').toggleClass('wf-checked', true);
				$('.wf-whitelist-table-bulk-checkbox.wf-option-checkbox').each(function() {
					$(this).toggleClass('wf-checked', $(this).closest('tr').is(':visible'));
				});
			}

			function whitelistUncheckAll() {
				$('.wf-whitelist-bulk-select.wf-option-checkbox').toggleClass('wf-checked', false);
				$('.wf-whitelist-table-bulk-checkbox.wf-option-checkbox').toggleClass('wf-checked', false);
			}

			$(window).on('wordfenceWAFInstallWhitelistEventHandlers', function() {
			//Enabled/Disabled
			$('.wf-whitelist-item-enabled.wf-option-checkbox').each(function() {
				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var row = $(this).closest('tr');
					var key = row.data('key');
					var value = $(this).hasClass('wf-checked') ? 1 : 0;
					if (value) {
						$(this).removeClass('wf-checked');
						value = 0;
					}
					else {
						$(this).addClass('wf-checked');
						value = 1;
					}

					MWP_WFAD.wafWhitelistedChangeEnabled(key, value);
					MWP_WFAD.updatePendingChanges();
				});
			});

			//Header/Footer Bulk Action
			$('.wf-whitelist-bulk-select.wf-option-checkbox').each(function() {
				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					if ($(this).hasClass('wf-checked')) {
						$(this).removeClass('wf-checked');
						whitelistUncheckAll();
					}
					else {
						$(this).addClass('wf-checked');
						whitelistCheckAllVisible();
					}
				});
			});

			//Row Bulk Action
			$('.wf-whitelist-table-bulk-checkbox.wf-option-checkbox').each(function() {
				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var row = $(this).closest('tr');
					var key = row.data('key');
					var value = $(this).hasClass('wf-checked') ? 1 : 0;
					if (value) {
						$(this).removeClass('wf-checked');
					}
					else {
						$(this).addClass('wf-checked');
					}

					var totalCount = $('.wf-whitelist-table-bulk-checkbox.wf-option-checkbox:visible').length;
					var checkedCount = $('.wf-whitelist-table-bulk-checkbox.wf-option-checkbox.wf-checked:visible').length;
					if (totalCount == 0 || (checkedCount != totalCount)) {
						$('.wf-whitelist-bulk-select.wf-option-checkbox').removeClass('wf-checked');
					}
					else {
						$('.wf-whitelist-bulk-select.wf-option-checkbox').addClass('wf-checked');
					}
				});
			});

			//On/Off Multiple Option
			$('#waf-monitor-requests-wrapper .wf-option-checkbox').each(function() {
				$(this).on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();

						var optionElement = $(this).closest('.wf-option');
						if (optionElement.hasClass('wf-option-premium') || optionElement.hasClass('wf-disabled')) {
								return;
						}

						var checkboxElement = $(this).closest('ul');
						var option = checkboxElement.data('option');
						var value = false;
						var isActive = $(this).hasClass('wf-checked');
						if (isActive) {
								$(this).removeClass('wf-checked');
								value = checkboxElement.data('disabledValue');
						}
						else {
								$(this).addClass('wf-checked');
								value = checkboxElement.data('enabledValue');
						}

						var originalValue = checkboxElement.data('originalValue');
						if (originalValue == value) {
								delete MWP_WFAD.pendingChanges[option];
						}
						else {
								MWP_WFAD.pendingChanges[option] = value;
						}

						$(optionElement).trigger('change', [false]);
						MWP_WFAD.updatePendingChanges();
				});
			});
			$(window).trigger('wordfenceWAFApplyWhitelistFilter');
		});

		$(window).on('wordfenceWAFApplyWhitelistFilter', function() {
			if (MWP_WFAD.wafData.whitelistedURLParams.length == 0) {
				return;
			}

			var filterColumn = $('#whitelist-table-controls select').val();
			var filterValue = $('input[name="filterValue"]').val();
			if (typeof filterValue != 'string' || filterValue.length == 0) {
				$('#waf-whitelisted-urls-wrapper .whitelist-table > tbody > tr[data-index]').show();
			}
			else {
				$('#waf-whitelisted-urls-wrapper .whitelist-table > tbody > tr[data-index]').each(function() {
					var text = $(this).find('td[data-column="' + filterColumn + '"]').text();
					if (text.indexOf(filterValue) > -1) {
						$(this).show();
					}
					else {
						$(this).hide();
					}
				});
			}
		});

		$(window).on('wordfenceWAFConfigPageRender', function() {
			delete MWP_WFAD.pendingChanges['wafRules'];

			//Add event handler to rule checkboxes
			$('.wf-rule-toggle.wf-boolean-switch').each(function() {
				$(this).on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					$(this).find('.wf-boolean-switch-handle').trigger('click');
				});

				$(this).find('.wf-boolean-switch-handle').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					var control = $(this).closest('.wf-boolean-switch');
					var row = $(this).closest('tr');
					var ruleID = row.data('ruleId');
					var value = control.hasClass('wf-active') ? 1 : 0;
					if (value) {
						control.removeClass('wf-active');
						value = 0;
					}
					else {
						control.addClass('wf-active');
						value = 1;
					}

					var originalValue = row.data('originalValue');
					if (originalValue == value) {
						delete MWP_WFAD.pendingChanges['wafRules'][ruleID];
						if (Object.keys(MWP_WFAD.pendingChanges['wafRules']).length == 0) {
							delete MWP_WFAD.pendingChanges['wafRules']
						}
					}
					else {
						if (!(MWP_WFAD.pendingChanges['wafRules'] instanceof Object)) {
							MWP_WFAD.pendingChanges['wafRules'] = {};
						}
						MWP_WFAD.pendingChanges['wafRules'][ruleID] = value;
					}

					$(control).trigger('change', [false]);
					MWP_WFAD.updatePendingChanges();
				});
			});

			//Add event handler to whitelist checkboxes
			$(window).trigger('wordfenceWAFInstallWhitelistEventHandlers');
		});

	})(jQuery);
</script>

		<?php
	}

	public static function gen_settings_login_security( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
		if ( empty( $w ) ) {
			return;
		}

		?>
	<div class="ui dividing header"><?php echo __( 'Wordfence Brute Force Protection', 'mainwp-wordfence-extension' ); ?></div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Enable brute force protection', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="loginSecurityEnabled" class="wfConfigElem" name="loginSecurityEnabled" value="1" <?php $w->cb( 'loginSecurityEnabled' ); ?> />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Lock out after how many login failures', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<select id="loginSec_maxFailures" class="wfConfigElem ui dropdown" name="loginSec_maxFailures">
		  <option value="2"<?php $w->sel( 'loginSec_maxFailures', '2' ); ?>>2</option>
		  <option value="3"<?php $w->sel( 'loginSec_maxFailures', '3' ); ?>>3</option>
		  <option value="4"<?php $w->sel( 'loginSec_maxFailures', '4' ); ?>>4</option>
		  <option value="5"<?php $w->sel( 'loginSec_maxFailures', '5' ); ?>>5</option>
		  <option value="6"<?php $w->sel( 'loginSec_maxFailures', '6' ); ?>>6</option>
		  <option value="7"<?php $w->sel( 'loginSec_maxFailures', '7' ); ?>>7</option>
		  <option value="8"<?php $w->sel( 'loginSec_maxFailures', '8' ); ?>>8</option>
		  <option value="9"<?php $w->sel( 'loginSec_maxFailures', '9' ); ?>>9</option>
		  <option value="10"<?php $w->sel( 'loginSec_maxFailures', '10' ); ?>>10</option>
		  <option value="20"<?php $w->sel( 'loginSec_maxFailures', '20' ); ?>>20</option>
		  <option value="30"<?php $w->sel( 'loginSec_maxFailures', '30' ); ?>>30</option>
		  <option value="40"<?php $w->sel( 'loginSec_maxFailures', '40' ); ?>>40</option>
		  <option value="50"<?php $w->sel( 'loginSec_maxFailures', '50' ); ?>>50</option>
		  <option value="100"<?php $w->sel( 'loginSec_maxFailures', '100' ); ?>>100</option>
		  <option value="200"<?php $w->sel( 'loginSec_maxFailures', '200' ); ?>>200</option>
		  <option value="500"<?php $w->sel( 'loginSec_maxFailures', '500' ); ?>>500</option>
		</select>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Lock out after how many forgot password attempts', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<select id="loginSec_maxForgotPasswd" class="wfConfigElem ui dropdown" name="loginSec_maxForgotPasswd">
		  <option value="1"<?php $w->sel( 'loginSec_maxForgotPasswd', '1' ); ?>>1</option>
		  <option value="2"<?php $w->sel( 'loginSec_maxForgotPasswd', '2' ); ?>>2</option>
		  <option value="3"<?php $w->sel( 'loginSec_maxForgotPasswd', '3' ); ?>>3</option>
		  <option value="4"<?php $w->sel( 'loginSec_maxForgotPasswd', '4' ); ?>>4</option>
		  <option value="5"<?php $w->sel( 'loginSec_maxForgotPasswd', '5' ); ?>>5</option>
		  <option value="6"<?php $w->sel( 'loginSec_maxForgotPasswd', '6' ); ?>>6</option>
		  <option value="7"<?php $w->sel( 'loginSec_maxForgotPasswd', '7' ); ?>>7</option>
		  <option value="8"<?php $w->sel( 'loginSec_maxForgotPasswd', '8' ); ?>>8</option>
		  <option value="9"<?php $w->sel( 'loginSec_maxForgotPasswd', '9' ); ?>>9</option>
		  <option value="10"<?php $w->sel( 'loginSec_maxForgotPasswd', '10' ); ?>>10</option>
		  <option value="20"<?php $w->sel( 'loginSec_maxForgotPasswd', '20' ); ?>>20</option>
		  <option value="30"<?php $w->sel( 'loginSec_maxForgotPasswd', '30' ); ?>>30</option>
		  <option value="40"<?php $w->sel( 'loginSec_maxForgotPasswd', '40' ); ?>>40</option>
		  <option value="50"<?php $w->sel( 'loginSec_maxForgotPasswd', '50' ); ?>>50</option>
		  <option value="100"<?php $w->sel( 'loginSec_maxForgotPasswd', '100' ); ?>>100 </option>
		  <option value="200"<?php $w->sel( 'loginSec_maxForgotPasswd', '200' ); ?>>200 </option>
		  <option value="500"<?php $w->sel( 'loginSec_maxForgotPasswd', '500' ); ?>>500 </option>
		</select>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Count failures over what time period', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<select id="loginSec_countFailMins" class="wfConfigElem ui dropdown" name="loginSec_countFailMins">
		  <option value="5"<?php $w->sel( 'loginSec_countFailMins', '5' ); ?>>5 minutes</option>
		  <option value="10"<?php $w->sel( 'loginSec_countFailMins', '10' ); ?>>10 minutes</option>
		  <option value="30"<?php $w->sel( 'loginSec_countFailMins', '30' ); ?>>30 minutes</option>
		  <option value="60"<?php $w->sel( 'loginSec_countFailMins', '60' ); ?>>1 hour</option>
		  <option value="120"<?php $w->sel( 'loginSec_countFailMins', '120' ); ?>>2 hours</option>
		  <option value="120"<?php $w->sel( 'loginSec_countFailMins', '240' ); ?>>4 hours</option>
		  <option value="360"<?php $w->sel( 'loginSec_countFailMins', '360' ); ?>>6 hours</option>
		  <option value="720"<?php $w->sel( 'loginSec_countFailMins', '720' ); ?>>12 hours</option>
		  <option value="1440"<?php $w->sel( 'loginSec_countFailMins', '1440' ); ?>>1 day</option>
		</select>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Amount of time a user is locked out', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<select id="loginSec_lockoutMins" class="wfConfigElem" name="loginSec_lockoutMins">
		  <option value="5"<?php $w->sel( 'loginSec_lockoutMins', '5' ); ?>>5 minutes</option>
		  <option value="10"<?php $w->sel( 'loginSec_lockoutMins', '10' ); ?>>10 minutes</option>
		  <option value="30"<?php $w->sel( 'loginSec_lockoutMins', '30' ); ?>>30 minutes</option>
		  <option value="60"<?php $w->sel( 'loginSec_lockoutMins', '60' ); ?>>1 hour</option>
		  <option value="120"<?php $w->sel( 'loginSec_lockoutMins', '120' ); ?>>2 hours</option>
		  <option value="360"<?php $w->sel( 'loginSec_lockoutMins', '360' ); ?>>6 hours</option>
		  <option value="720"<?php $w->sel( 'loginSec_lockoutMins', '720' ); ?>>12 hours</option>
		  <option value="1440"<?php $w->sel( 'loginSec_lockoutMins', '1440' ); ?>>1 day</option>
		  <option value="2880"<?php $w->sel( 'loginSec_lockoutMins', '2880' ); ?>>2 days</option>
		  <option value="7200"<?php $w->sel( 'loginSec_lockoutMins', '7200' ); ?>>5 days</option>
		  <option value="14400"<?php $w->sel( 'loginSec_lockoutMins', '14400' ); ?>>10 days</option>
		  <option value="28800"<?php $w->sel( 'loginSec_lockoutMins', '28800' ); ?>>20 days</option>
		  <option value="43200"<?php $w->sel( 'loginSec_lockoutMins', '43200' ); ?>>1 month</option>
		  <option value="86400"<?php $w->sel( 'loginSec_lockoutMins', '86400' ); ?>>2 months</option>
		</select>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Immediately lock out invalid usernames', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="loginSec_lockInvalidUsers" class="wfConfigElem" name="loginSec_lockInvalidUsers" <?php $w->cb( 'loginSec_lockInvalidUsers' ); ?> />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Immediately block the IP of users who try to sign in as these usernames', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<textarea name="loginSec_userBlacklist" class="mwp-wf-form-control" cols="40" rows="4" id="loginSec_userBlacklist">
		  <?php echo MainWP_Wordfence_Utility::cleanupOneEntryPerLine( $w->getHTML( 'loginSec_userBlacklist' ) ); ?>
		</textarea>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Prevent the use of passwords leaked in data breaches', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="one wide column middle aligned ui toggle checkbox">
		<input type="checkbox" id="loginSec_breachPasswds_enabled" class="wfConfigElem" name="loginSec_breachPasswds_enabled" <?php $w->cb( 'loginSec_breachPasswds_enabled' ); ?> />
	  </div>
	  <div class="nine wide column">
		<select id="loginSec_breachPasswds" class="wfConfigElem ui dropdown" name="loginSec_breachPasswds">
		  <option value="admins" <?php $w->sel( 'loginSec_breachPasswds', 'admins' ); ?>>For admins only</option>
		  <option value="pubs" <?php $w->sel( 'loginSec_breachPasswds', 'pubs' ); ?>>For all users with "publish posts" capability</option>
		</select>
	  </div>
	</div>

	<div class="ui dividing header"><?php echo __( 'Additional Options', 'mainwp-wordfence-extension' ); ?></div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Enforce strong passwords?', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="one wide column middle aligned ui toggle checkbox">
		<input type="checkbox" id="loginSec_strongPasswds_enabled" class="wfConfigElem" name="loginSec_strongPasswds_enabled" <?php $w->cb( 'loginSec_strongPasswds_enabled' ); ?> />
	  </div>
	  <div class="nine wide column">
		<select class="wfConfigElem" id="loginSec_strongPasswds ui dropdown" name="loginSec_strongPasswds">
		  <option value="pubs"<?php $w->sel( 'loginSec_strongPasswds', 'pubs' ); ?>>Force admins and publishers to use strong passwords (recommended)</option>
		  <option value="all"<?php $w->sel( 'loginSec_strongPasswds', 'all' ); ?>>Force all members to use strong passwords</option>
		</select>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Don\'t let WordPress reveal valid users in login errors', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="loginSec_maskLoginErrors" class="wfConfigElem" name="loginSec_maskLoginErrors" <?php $w->cb( 'loginSec_maskLoginErrors' ); ?> />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Prevent users registering \'admin\' username if it doesn\'t exist', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="loginSec_blockAdminReg" class="wfConfigElem" name="loginSec_blockAdminReg" <?php $w->cb( 'loginSec_blockAdminReg' ); ?> />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Prevent discovery of usernames through \'/?author=N\' scans, the oEmbed API, and the WordPress REST API', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="loginSec_disableAuthorScan" class="wfConfigElem" name="loginSec_disableAuthorScan" <?php $w->cb( 'loginSec_disableAuthorScan' ); ?> />
	  </div>
	</div>
	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Disable WordPress application passwords', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="loginSec_disableApplicationPasswords" class="wfConfigElem" name="loginSec_disableApplicationPasswords" <?php $w->cb( 'loginSec_disableApplicationPasswords' ); ?> />
	  </div>
	</div>
	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Block IPs who send POST requests with blank User-Agent and Referer', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="other_blockBadPOST" class="wfConfigElem" name="other_blockBadPOST" value="1" <?php $w->cb( 'other_blockBadPOST' ); ?> />
	  </div>
	</div>
	
	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Custom text shown on block pages', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<textarea name="blockCustomText" class="mwp-wf-form-control" cols="40" rows="4" id="blockCustomText">
		  <?php echo $w->getHTML( 'blockCustomText' ); ?>
		</textarea>

		$customText = wpautop(wp_strip_all_tags(wfConfig::get('blockCustomText', '')));
		
		
		<span  style="color: #999;">
		  	<?php _e( 'HTML tags will be stripped prior to output and line breaks will be converted into the appropriate tags.', 'mainwp-wordfence-extension' ); ?>
		</span>
	  </div>
	</div>
	
	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Check password strength on profile update', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="other_pwStrengthOnUpdate" class="wfConfigElem" name="other_pwStrengthOnUpdate" value="1" <?php $w->cb( 'other_pwStrengthOnUpdate' ); ?> />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Participate in the Real-Time WordPress Security Network', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="other_WFNet" class="wfConfigElem" name="other_WFNet" value="1" <?php $w->cb( 'other_WFNet' ); ?> />
	  </div>
	</div>
		<?php
	}

	public static function gen_settings_rate_limiting_rules( $individual = false ) {

		$current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;

		if ( $individual && empty( $current_site_id ) ) {
			return;
		}

		$w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
		if ( empty( $w ) ) {
			return;
		}
		?>
	<div class="ui dividing header"><?php echo __( 'Wordfence Rate Limiting', 'mainwp-wordfence-extension' ); ?></div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Enable rate limiting and advanced blocking', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column ui toggle checkbox">
		<input type="checkbox" id="firewallEnabled" class="wfConfigElem" name="firewallEnabled" value="1" <?php $w->cb( 'firewallEnabled' ); ?> />
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'How should we treat Google\'s crawlers', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<select id="neverBlockBG" class="wfConfigElem ui dropdown" name="neverBlockBG">
		  <option value="neverBlockVerified"<?php $w->sel( 'neverBlockBG', 'neverBlockVerified' ); ?>>Verified Google crawlers have unlimited access to this site</option>
		  <option value="neverBlockUA"<?php $w->sel( 'neverBlockBG', 'neverBlockUA' ); ?>>Anyone claiming to be Google has unlimited access</option>
		  <option value="treatAsOtherCrawlers"<?php $w->sel( 'neverBlockBG', 'treatAsOtherCrawlers' ); ?>>Treat Google like any other Crawler</option>
		</select>
	  </div>
	</div>

		<?php $include_dir = MainWP_Wordfence_Extension::$plugin_dir . 'includes/'; ?>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'If anyone\'s requests exceed', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="six wide column">
		<?php
		$rateName = 'maxGlobalRequests';
		require $include_dir . 'wfRate.php';
		?>
	  </div>
	  <div class="one wide middle aligned center aligned column">than</div>
	  <div class="three wide column">
		<?php
		$throtName = 'maxGlobalRequests_action';
		require $include_dir . 'wfAction.php';
		?>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'If a crawler\'s page views exceed', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="six wide column">
		<?php
		$rateName = 'maxRequestsCrawlers';
		require $include_dir . 'wfRate.php';
		?>
	  </div>
	  <div class="one wide middle aligned center aligned column">than</div>
	  <div class="three wide column">
		<?php
		$throtName = 'maxRequestsCrawlers_action';
		require $include_dir . 'wfAction.php';
		?>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'If a crawler\'s pages not found (404s) exceed', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="six wide column">
		<?php
		$rateName = 'max404Crawlers';
		require $include_dir . 'wfRate.php';
		?>
	  </div>
	  <div class="one wide middle aligned center aligned column">than</div>
	  <div class="three wide column">
		<?php
		$throtName = 'max404Crawlers_action';
		require $include_dir . 'wfAction.php';
		?>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'If a human\'s page views exceed', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="six wide column">
		<?php
		$rateName = 'maxRequestsHumans';
		require $include_dir . 'wfRate.php';
		?>
	  </div>
	  <div class="one wide middle aligned center aligned column">than</div>
	  <div class="three wide column">
		<?php
		$throtName = 'maxRequestsHumans_action';
		require $include_dir . 'wfAction.php';
		?>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'If a human\'s pages not found (404s) exceed', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="six wide column">
		<?php
		$rateName = 'max404Humans';
		require $include_dir . 'wfRate.php';
		?>
	  </div>
	  <div class="one wide middle aligned center aligned column">than</div>
	  <div class="three wide column">
		<?php
		$throtName = 'max404Humans_action';
		require $include_dir . 'wfAction.php';
		?>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'How long is an IP address blocked when it breaks a rule', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<select id="blockedTime" class="wfConfigElem ui dropdown" name="blockedTime">
		  <option value="60"<?php $w->sel( 'blockedTime', '60' ); ?>>1 minute</option>
		  <option value="300"<?php $w->sel( 'blockedTime', '300' ); ?>>5 minutes</option>
		  <option value="1800"<?php $w->sel( 'blockedTime', '1800' ); ?>>30 minutes</option>
		  <option value="3600"<?php $w->sel( 'blockedTime', '3600' ); ?>>1 hour</option>
		  <option value="7200"<?php $w->sel( 'blockedTime', '7200' ); ?>>2 hours</option>
		  <option value="21600"<?php $w->sel( 'blockedTime', '21600' ); ?>>6 hours</option>
		  <option value="43200"<?php $w->sel( 'blockedTime', '43200' ); ?>>12 hours</option>
		  <option value="86400"<?php $w->sel( 'blockedTime', '86400' ); ?>>1 day</option>
		  <option value="172800"<?php $w->sel( 'blockedTime', '172800' ); ?>>2 days</option>
		  <option value="432000"<?php $w->sel( 'blockedTime', '432000' ); ?>>5 days</option>
		  <option value="864000"<?php $w->sel( 'blockedTime', '864000' ); ?>>10 days</option>
		  <option value="2592000"<?php $w->sel( 'blockedTime', '2592000' ); ?>>1 month</option>
		</select>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Allowlisted 404 URLs (one per line)', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<textarea name="allowed404s" id="" class="mwp-wf-form-control" cols="40" rows="4"><?php echo $w->getHTML( 'allowed404s' ); ?></textarea>
	  </div>
	</div>
		<?php
	}

	public static function gen_firewall_status_settings( $extra_settings = array(), $site_id = 0 ) {
		$wafStatus = ( is_array( $extra_settings ) && isset( $extra_settings['wafStatus'] ) ) && ! empty( $extra_settings['wafStatus'] ) ? $extra_settings['wafStatus'] : 'disabled'; // (!WFWAF_ENABLED ? 'disabled' : $config->getConfig('wafStatus'));
		?>
	<div class="ui dividing header"><?php echo __( 'Wordfence Basic WordPress Protection', 'mainwp-wordfence-extension' ); ?></div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( 'Web Application Firewall Status', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<select id="input-wafStatus" name="wafStatus" class="mwp-wf-form-control">
		  <option<?php echo $wafStatus == 'enabled' ? ' selected' : ''; ?> class="wafStatus-enabled" value="enabled">Enabled and Protecting</option>
		  <option<?php echo $wafStatus == 'learning-mode' ? ' selected' : ''; ?> class="wafStatus-learning-mode" value="learning-mode">Learning Mode</option>
		  <option<?php echo $wafStatus == 'disabled' ? ' selected' : ''; ?> class="wafStatus-disabled" value="disabled">Disabled</option>
		</select>
	  </div>
	</div>

	<div class="ui grid field">
	  <label class="six wide column middle aligned"><?php _e( '', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="ten wide column">
		<div class="ui info message wafStatus-description" id="wafStatus-enabled-description">
			In this mode, the Wordfence Web Application Firewall is actively blocking requests
			matching known attack patterns, and is actively protecting your site from attackers.
		</div>
		<div class="ui info message wafStatus-description" id="wafStatus-learning-mode-description">
			When you first install the Wordfence Web Application Firewall, it will be in
			learning
			mode. This allows
			Wordfence to learn about your site so that we can understand how to protect it and
			how
			to allow normal visitors through the firewall. We recommend you let Wordfence learn
			for
			a week before you enable the firewall.
		</div>
		<div class="ui info message wafStatus-description" id="wafStatus-disabled-description">
			In this mode, the Wordfence Web Application Firewall is functionally turned off and
			does not run any of its rules or analyze the request in any way.
		</div>
	  </div>
	</div>

	<div class="ui grid field" id="waf-learning-mode-grace-row">
	  <label class="six wide column middle aligned"><?php _e( 'Automatically enable on', 'mainwp-wordfence-extension' ); ?></label>
	  <div class="two wide column ui toggle checkbox">
		<input type="checkbox" name="learningModeGracePeriodEnabled" value="1"<?php echo isset( $extra_settings['learningModeGracePeriodEnabled'] ) && $extra_settings['learningModeGracePeriodEnabled'] ? ' checked' : ''; ?>>
	  </div>
	  <div class="eight wide column">
		<input type="text" name="learningModeGracePeriod" id="input-learningModeGracePeriod" class="wf-datetime mwp-wf-form-control" placeholder="Enabled until..." data-value="<?php echo esc_attr( isset( $extra_settings['learningModeGracePeriod'] ) ? (int) $extra_settings['learningModeGracePeriod'] : '' ); ?>" data-original-value="<?php echo esc_attr( isset( $extra_settings['learningModeGracePeriod'] ) ? (int) $extra_settings['learningModeGracePeriod'] : '' ); ?>">
	  </div>
	</div>


	<script type="application/javascript">
		(function($) {
			$('#input-wafStatus').val(<?php echo json_encode( $wafStatus ); ?>)
				.on('change', function() {
					var val = $(this).val();
					$('.wafStatus-description').hide();
					$('#wafStatus-' + val + '-description').show();
				});

			$('#input-wafStatus').on('change', function() {
				var gracePeriodRow = $('#waf-learning-mode-grace-row');
				if ($(this).val() == 'learning-mode') {
					gracePeriodRow.show();
				} else {
					gracePeriodRow.hide();
				}
			}).triggerHandler('change');


			$('#waf-general-config-form').on("submit", function() {
				MWP_WFAD.wafConfigSave('general_config', $(this).serializeArray(), null, null,  0);
			});


			$('#input-wafStatus').select2({
				minimumResultsForSearch: -1
			}).on('change', function() {
				var select = $(this);
				var container = $($(this).data('select2').$container);
				container.removeClass('wafStatus-enabled wafStatus-learning-mode wafStatus-disabled')
					.addClass('wafStatus-' + select.val());
			}).triggerHandler('change');

		$(function() {
			$('.wf-datetime').datetimepicker({
				timeFormat: 'hh:mmtt z'
			}).each(function() {
				var el = $(this);
				if (el.attr('data-value')) {
					el.datetimepicker('setDate', new Date(el.attr('data-value') * 1000));
				}
			});

			var learningModeGracePeriod = $('input[name=learningModeGracePeriod]');
			$('input[name=learningModeGracePeriodEnabled]').on('click', function() {

				if (this.value == '1' && this.checked) {
					learningModeGracePeriod.attr('disabled', false);
					if (!learningModeGracePeriod.val()) {
						var date = new Date();
						date.setDate(date.getDate() + 7);
						learningModeGracePeriod.datetimepicker('setDate', date);
					}
				} else {
					learningModeGracePeriod.attr('disabled', true);
					learningModeGracePeriod.val('');
				}
			}).triggerHandler('click');
		});

		})(jQuery);
	</script>

		<?php
	}

	public static function gen_general_firewall_basic() {

		$w = MainWP_Wordfence_Setting::get_instance()->load_configs();

		if ( empty( $w ) ) {
			return;
		}

		$settings = array(
			'wafStatus'                      => $w->get( 'wafStatus' ),
			'learningModeGracePeriod'        => $w->get( 'learningModeGracePeriod' ),
			'learningModeGracePeriodEnabled' => $w->get( 'learningModeGracePeriodEnabled' ),
		);

		self::gen_firewall_status_settings( $settings );
	}

}
