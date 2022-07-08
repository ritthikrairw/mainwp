<?php

class MainWP_Wordfence_Blocking {
	//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Wordfence_Blocking::$instance ) {
			MainWP_Wordfence_Blocking::$instance = new MainWP_Wordfence_Blocking();
		}

		return MainWP_Wordfence_Blocking::$instance;
	}

	public function __construct() {

	}
    // TODO:
    public static function gen_all_blocking_ips_tab( $post, $metabox = null) {
        $current_site_id = isset($metabox['args']['websiteid']) ? $metabox['args']['websiteid'] : null;

        $open_url = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site';
        $export_href = $open_url . "&websiteid=" . $current_site_id . "&open_location=" . base64_encode( "?_wfsf=blockedIPs&nonce=child_temp_nonce" );

        ?>
        <div class="wf-block wf-block-no-header wf-active">
            <div class="wf-block-content wf-padding-add-top-large wf-padding-add-bottom-large">
                <ul class="wf-flex-horizontal wf-flex-full-width wf-no-top">
                    <li></li>
                    <li class="wf-right wf-flex-vertical-xs">
                        <a href="#" id="blocks-bulk-unblock" class="wf-btn wf-btn-callout wf-btn-default"><?php _e('Unblock', 'wordfence'); ?></a>&nbsp;&nbsp;<a href="#" id="blocks-bulk-make-permanent" class="wf-btn wf-btn-callout wf-btn-default"><?php _e('Make Permanent', 'wordfence'); ?></a>&nbsp;&nbsp;<a href="<?php echo $export_href; ?>" id="blocks-export-ips" target="_blank" class="wf-btn wf-btn-callout wf-btn-default"><?php _e('Export<span class="wf-hidden-xs"> All IPs</span>', 'wordfence'); ?></a>
                    </li>
                </ul>
                <div class="wf-block wf-block-no-padding wf-block-no-header wf-active wf-no-bottom wf-overflow-y-auto-xs">
                    <div class="wf-block-content">
                        <div id="wf-blocks-wrapper"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="wf-block wf-block-no-padding wf-block-no-header wf-active wf-no-bottom wf-overflow-y-auto-xs">
            <div class="wf-block-content">
                <div id="wf-blocks-wrapper"></div>
            </div>
        </div> <!-- end block list -->

<script type="text/x-jquery-template" id="wf-blocks-tmpl">
	<div class="wf-blocks-table-container">
		<table class="wf-striped-table wf-blocks-table">
			<thead>
			</thead>
			<tbody>
			</tbody>
			<tfoot>
			</tfoot>
		</table>
	</div>
</script>
<script type="text/x-jquery-template" id="wf-blocks-columns-tmpl">
	<tr class="wf-blocks-columns">
		<th style="width: 2%;text-align: center"><div class="wf-blocks-bulk-select wf-option-checkbox"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></div></th>
		<th><?php _e('Block Type', 'wordfence'); ?></th>
		<th><?php _e('Detail', 'wordfence'); ?></th>
		<th><?php _e('Rule Added', 'wordfence'); ?></th>
		<th><?php _e('Reason', 'wordfence'); ?></th>
		<th><?php _e('Expiration', 'wordfence'); ?></th>
		<th><?php _e('Block Count', 'wordfence'); ?></th>
		<th><?php _e('Last Attempt', 'wordfence'); ?></th>
	</tr>
</script>
<script type="text/x-jquery-template" id="wf-no-blocks-tmpl">
	<tr id="wf-no-blocks">
		<td colspan="8"><?php _e('No blocks are currently active.', 'wordfence'); ?></td>
	</tr>
</script>
<script type="text/x-jquery-template" id="wf-blocks-loading-tmpl">
	<tr id="wf-blocks-loading">
		<td colspan="8" class="wf-center wf-padding-add-top wf-padding-add-bottom">
			<?php
//			echo wfView::create('common/indeterminate-progress', array(
//				'size' => 50,
//			))->render();
			?>
		</td>
	</tr>
</script>
<script type="text/x-jquery-template" id="wf-block-row-tmpl">
	<tr class="wf-block-record" data-id="${id}" data-expiration="${expiration}">
		<td style="text-align: center;"><div class="wf-blocks-table-bulk-checkbox wf-option-checkbox"><i class="wf-ion-ios-checkmark-empty" aria-hidden="true"></i></div></td>
		<td data-column="type" data-sort="${typeSort}">${typeDisplay}</td>
		<td data-column="detail" data-sort="${detailSort}">${detailDisplay}{{if (editable)}}&nbsp;<a href="#" class="wf-block-edit" data-edit-type="${editType}" data-edit-values="${editValues}"><i class="wf-ion-edit" aria-hidden="true"></i></a>{{/if}}</td>
		<td data-column="ruleAdded" data-sort="${ruleAddedSort}">${ruleAddedDisplay}</td>
		<td data-column="reason" data-sort="${reasonSort}">${reasonDisplay}</td>
		<td data-column="expiration" data-sort="${expirationSort}">${expirationDisplay}</td>
		<td data-column="blockCount" data-sort="${blockCountSort}">${blockCountDisplay}</td>
		<td data-column="lastAttempt" data-sort="${lastAttemptSort}">${lastAttemptDisplay}</td>
	</tr>
</script>
<script type="application/javascript">
	(function($) {
		MWP_WFAD.blockHeaderCheckboxAction = function(checkbox) { //Top-level checkboxes
			$('.wf-blocks-bulk-select.wf-option-checkbox').toggleClass('wf-checked');
			var checked = $(checkbox).hasClass('wf-checked');
			$('.wf-blocks-table-bulk-checkbox.wf-option-checkbox').toggleClass('wf-checked', checked);
			$(window).trigger('wordfenceUpdateBlockButtons');
		};


		$(window).on('wordfenceRefreshBlockList', function(e, payload, append) {
			if (!payload.hasOwnProperty('loading')) {
				payload['loading'] = false;
			}

			//Create table if needed
			var table = $(".wf-blocks-table-container");
			if (!append || table.length == 0) {
				$(".wf-blocks-table-container").remove();
				var wrapperTemplate = $('#wf-blocks-tmpl').tmpl();
				$('#wf-blocks-wrapper').append(wrapperTemplate);
				table = $(".wf-blocks-table-container");
			}

			//Create header if needed
			if (table.find('thead > .wf-blocks-columns').length == 0) {
				table.find('thead').append($('#wf-blocks-columns-tmpl').tmpl());
				table.find('thead .wf-blocks-bulk-select.wf-option-checkbox').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					MWP_WFAD.blockHeaderCheckboxAction($(this));
				});
			}

			//Create or remove footer if needed
			if (payload['blocks'].length > 5 && table.find('tfoot > .wf-blocks-columns').length == 0) {
				table.find('tfoot').append($('#wf-blocks-columns-tmpl').tmpl());
				table.find('tfoot .wf-blocks-bulk-select.wf-option-checkbox').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();
					MWP_WFAD.blockHeaderCheckboxAction($(this));
				});
			}
			else {
				table.find('tfoot > .wf-blocks-columns').remove();
			}

			//Add row(s)
			$('#wf-blocks-loading').remove();
			if (!append && payload['blocks'].length == 0) {
				table.find('tbody').append($('#wf-no-blocks-tmpl').tmpl());
			}
			else {
				$('#wf-no-blocks').remove();
				for (var i = 0; i < payload['blocks'].length; i++) {
					var row = $('#wf-block-row-tmpl').tmpl(payload['blocks'][i]);

					row.find('.wf-blocks-table-bulk-checkbox.wf-option-checkbox').on('click', function() { //Individual checkboxes
						e.preventDefault();
						e.stopPropagation();

						$(this).toggleClass('wf-checked');
						$(window).trigger('wordfenceUpdateBulkSelect');
						$(window).trigger('wordfenceUpdateBlockButtons');
					});

					row.find('.wf-block-edit').on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();

						var editType = $(this).data('editType');
						$('#wf-block-type > li > a[data-value="' + editType + '"]').trigger('click');
						if ($('#wf-block-parameters-title').offset().top < $(window).scrollTop()) {
							$("body,html").animate({
								scrollTop: $('#wf-block-parameters-title').offset().top
							}, 800);
						}
					});

					var existing = table.find('tbody tr[data-id="' + payload['blocks'][i]['id'] + '"]');
					if (existing.length > 0) {
						existing.replaceWith(row);
					}
					else {
						table.find('tbody').append(row);
					}
				}
			}

			var hasCountryBlock = $('#wf-blocks-wrapper').data('hasCountryBlock') === 1;
			$('#wf-blocks-wrapper').data('hasCountryBlock', (hasCountryBlock || !!payload.hasCountryBlock) ? 1 : 0);

			$(window).trigger('wordfenceUpdateBlockButtons');
		});

		$(window).on('wordfenceUpdateBlockButtons', function() {
			var totalCount = $('.wf-blocks-table-bulk-checkbox.wf-option-checkbox').length;
			var checked = $('.wf-blocks-table-bulk-checkbox.wf-option-checkbox.wf-checked');
			var allowUnblock = false;
			var allowMakeForever = false;
			for (var i = 0; i < checked.length; i++) {
				var tr = $(checked[i]).closest('tr');
				if (tr.is(':visible')) {
					allowUnblock = true;
					if (tr.data('expiration') > 0) {
						allowMakeForever = true;
					}
				}
			}

			$('#blocks-bulk-unblock').toggleClass('wf-disabled', !allowUnblock);
			$('#blocks-bulk-make-permanent').toggleClass('wf-disabled', !allowMakeForever);
			$('#blocks-export-ips').toggleClass('wf-disabled', (totalCount == 0));
		});

		$(window).on('wordfenceUpdateBulkSelect', function() {
			var totalCount = $('.wf-blocks-table-bulk-checkbox.wf-option-checkbox:visible').length;
			var checkedCount = $('.wf-blocks-table-bulk-checkbox.wf-option-checkbox.wf-checked:visible').length;
			$('.wf-blocks-bulk-select.wf-option-checkbox:visible').toggleClass('wf-checked', (totalCount > 0 && checkedCount == totalCount));
		});

		$(function() {
			$(window).trigger('wordfenceRefreshBlockList', [{blocks: [], loading: true}, false]);
			var siteId = <?php echo intval($current_site_id); ?>;
			MWP_WFAD.loadingBlocks = true;
			MWP_WFAD.ajax('mainwp_wfc_getBlocks', {offset: 0, site_id: siteId}, function(res) {
                var undefined_Error = false;
                if (res) {
                    if (res.blocks) {
                        $(window).trigger('wordfenceRefreshBlockList', [res, false]);
                    } else if (res.error) {
                        MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.error);
                    } else {
                        undefined_Error = true;
                    }
                } else {
                    undefined_Error = true;
                }

                if (undefined_Error)
                    MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', 'Undefined error');


				MWP_WFAD.loadingBlocks = false;
			});

			var issuesWrapper = $('#wf-blocks-wrapper');
			var hasScrolled = false;
			$(window).on('scroll', function() {
				var win = $(this);
				var currentScrollBottom = win.scrollTop() + window.innerHeight;
				var scrollThreshold = issuesWrapper.outerHeight() + issuesWrapper.offset().top;
				if (hasScrolled && !MWP_WFAD.loadingBlocks && currentScrollBottom >= scrollThreshold) {
					hasScrolled = false;
					var offset = $('.wf-block-record').length;
					MWP_WFAD.loadingBlocks = true;
					MWP_WFAD.ajax('mainwp_wfc_getBlocks', {offset: offset, site_id: siteId}, function(res) {
                        var undefined_Error = false;
                        if (res) {
                            if (res.blocks) {
                                $(window).trigger('wordfenceRefreshBlockList', [res, true]);
                            } else if (res.error) {
                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.error);
                            } else {
                                undefined_Error = true;
                            }
                        } else {
                            undefined_Error = true;
                        }

                        if (undefined_Error)
                            MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', 'Undefined error');

						MWP_WFAD.loadingBlocks = false;
					});
				}
				else if (currentScrollBottom < scrollThreshold) {
					hasScrolled = true;
				}
			});

			$('#blocks-bulk-unblock').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var totalCount = $('.wf-blocks-table-bulk-checkbox.wf-option-checkbox:visible').length;
				var checked = $('.wf-blocks-table-bulk-checkbox.wf-option-checkbox.wf-checked:visible');
				var checkedCount = checked.length;
				var blockIDs = [];
				var rows = [];
				for (var i = 0; i < checked.length; i++) {
					var tr = $(checked[i]).closest('tr');
					rows.push(tr);
					blockIDs.push(tr.data('id'));
				}

				var prompt = $('#wfTmpl_unblockPrompt').tmpl({count: checkedCount});

				var promptHTML = $("<div />").append(prompt).html();
				MWP_WFAD.colorboxHTML('400px', promptHTML, {overlayClose: false, closeButton: false, className: 'wf-modal', onComplete: function() {
					$('#wf-blocking-prompt-cancel').on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();

						MWP_WFAD.colorboxClose();
					});

					$('#wf-blocking-prompt-unblock').on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();
                        var siteId = <?php echo intval($current_site_id); ?>;
						MWP_WFAD.loadingBlocks = true;
						MWP_WFAD.ajax('mainwp_wfc_deleteBlocks', {blocks: JSON.stringify(blockIDs), site_id:siteId }, function(res) {
							MWP_WFAD.loadingBlocks = false;
							if (totalCount == checkedCount) {
								$(window).trigger('wordfenceRefreshBlockList', [res, false]); //Everything deleted, just reload it
							}
							else {
								for (var i = 0; i < rows.length; i++) {
									$(rows[i]).remove();
								}
								$(window).trigger('wordfenceUpdateBulkSelect');
								$(window).trigger('wordfenceUpdateBlockButtons');
							}

							MWP_WFAD.colorboxClose();
						});
					});
				}});
			});

			$('#blocks-bulk-make-permanent').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var checked = $('.wf-blocks-table-bulk-checkbox.wf-option-checkbox.wf-checked:visible');
				var updateIDs = [];
				for (var i = 0; i < checked.length; i++) {
					var tr = $(checked[i]).closest('tr');
					if (tr.is(':visible')) {
						updateIDs.push(tr.data('id'));
					}
				}
                var siteId = <?php echo intval($current_site_id); ?>;
				MWP_WFAD.loadingBlocks = true;
				MWP_WFAD.ajax('mainwp_wfc_makePermanentBlocks', {updates: JSON.stringify(updateIDs), site_id: siteId}, function(res) {
					MWP_WFAD.loadingBlocks = false;
					$(window).trigger('wordfenceRefreshBlockList', [res, false]);
				});
			});

			$('.wf-option.wf-option-toggled-boolean-switch[data-option="displayAutomaticBlocks"]').on('change', function() {
				delete MWP_WFAD.pendingChanges['displayAutomaticBlocks'];
				var isOn = $(this).find('.wf-boolean-switch').hasClass('wf-active');
                var siteId = <?php echo intval($current_site_id); ?>;
				MWP_WFAD.setOption($(this).data('option'), (isOn ? $(this).data('enabledValue') : $(this).data('disabledValue')), function() {
					MWP_WFAD.ajax('mainwp_wfc_getBlocks', {offset: 0, site_id: siteId}, function(res) {
                        var undefined_Error = false;
                        if (res) {
                            if (res.blocks) {
                                $(window).trigger('wordfenceRefreshBlockList', [res, false]);
                            } else if (res.error) {
                                MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', res.error);
                            } else {
                                undefined_Error = true;
                            }
                        } else {
                            undefined_Error = true;
                        }

                        if (undefined_Error)
                            MWP_WFAD.colorboxModal((MWP_WFAD.isSmallScreen ? '300px' : '400px'), 'An error occurred', 'Undefined error');
                        MWP_WFAD.loadingBlocks = false;
					});
				});
			});
		});
	})(jQuery);
</script>

<script type="text/x-jquery-template" id="wfTmpl_unblockPrompt">
<?php
echo MainWP_wfView::create('common/modal-prompt', array(
	'title' => __('Unblocking', 'wordfence'),
	'message' => '{{if count == 1}}' . __('Are you sure you want to stop blocking the selected IP, range, or country?') . ' {{else}}' . __('Are you sure you want to stop blocking the ${count} selected IPs, ranges, and countries?') . '{{/if}}',
	'primaryButton' => array('id' => 'wf-blocking-prompt-cancel', 'label' => __('Cancel', 'wordfence'), 'link' => '#'),
	'secondaryButtons' => array(array('id' => 'wf-blocking-prompt-unblock', 'label' => __('Unblock', 'wordfence'), 'link' => '#')),
))->render();
?>
</script>

            <?php
    }

	public static function gen_blocking_custom_rules_tab() {
    $site_id = isset( $_GET['id'] ) ? $_GET['id'] : null;
    ?>
		<div class="ui dividing header"><?php echo __( 'WordFence Network Blocking Rule', 'mainwp-wordfence-extension' ); ?></div>
		<div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_rangeBlocking"></div>
		<div id="paidWrap">
			<div class="wordfenceWrap">
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'IP Address Range', 'mainwp-wordfence-extension' ); ?></label>
					<div class="ten wide column">
						<input id="ipRange" class="mwp-wf-form-control" type="text" value="<?php if( isset( $_GET['wfBlockRange'] ) && preg_match('/^[\da-f\.\s\t\-:]+$/i', $_GET['wfBlockRange']) ){ echo wp_kses($_GET['wfBlockRange'], array()); } ?>" onkeyup="MWP_WFAD.calcRangeTotal();">

						<div class="ui message">e.g., 192.168.200.200 - 192.168.200.220 or 192.168.200.0/24</div>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Hostname', 'mainwp-wordfence-extension' ); ?></label>
					<div class="ten wide column">
						<input id="hostname" class="mwp-wf-form-control" type="text" value="<?php if( isset( $_GET['wfBlockHostname'] ) ){ echo esc_attr($_GET['wfBlockHostname']); } ?>" onkeyup="MWP_WFAD.calcRangeTotal();">

						<div class="ui message">e.g, *.amazonaws.com or *.linode.com</div>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Browser user agent', 'mainwp-wordfence-extension' ); ?></label>
					<div class="ten wide column">
						<input id="uaRange" class="mwp-wf-form-control" type="text" >

						<div class="ui message">e.g, *badRobot*, *MSIE*, or *browserSuffix</div>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Referrer', 'mainwp-wordfence-extension' ); ?></label>
					<div class="ten wide column">
						<input id="wfreferer" class="mwp-wf-form-control" type="text" >

						<div class="ui message">e.g., *badwebsite.example.com*</div>
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( 'Block reason', 'mainwp-wordfence-extension' ); ?></label>
					<div class="ten wide column">
						<input id="wfReason" class="mwp-wf-form-control" type="text" >
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php _e( '', 'mainwp-wordfence-extension' ); ?></label>
					<div class="ten wide column">
					<input type="button" name="but3" class="ui green button" value="Block Visitors Matching this Pattern" onclick="MWP_WFAD.blockIPUARange(jQuery('#ipRange').val(), jQuery('#hostname').val(), jQuery('#uaRange').val(), jQuery('#wfreferer').val(), jQuery('#wfReason').val(), jQuery('#mwp_wfc_current_site_id').attr('site-id')); return false;" />
					</div>
				</div>

			</div>
		</div>
		<?php
  }

  public static function gen_blocking_general_settings_tab() {

		$current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
		$w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
			
		if ( empty( $w ) )
			return;
    ?>
		<div class="ui dividing header"><?php echo __( 'Wordfence Blocking Options', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Display top level Blocking menu option', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="displayTopLevelBlocking" class="wfConfigElem" name="displayTopLevelBlocking" value="1" <?php $w->cb( 'displayTopLevelBlocking' ); ?> />
		  </div>
		</div>
    <?php
  }

  public static function gen_blocking_rules_ip_address_tab() {
    $site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
    ?>
		<div class="ui dividing header"><?php echo __( 'WordFence Network Blocking Rule - IP Address', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'IP Address to Block', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" class="mwp-wf-form-control" id="wfManualBlock" value="" onkeydown="if(event.keyCode == 13){ MWP_WFAD.blockIPTwo(jQuery('#wfManualBlock').val(), jQuery('#wfReasonManual').val(), true, <?php esc_html_e($site_id); ?>); return false; }" />
		  </div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Reason*', 'mainwp-wordfence-extension' ); ?></label>
			<div class="ten wide column">
				<input id="wfReasonManual" class="mwp-wf-form-control" type="text">
			</div>
		</div>
		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( '', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="button" name="but1" value="Block this IP Address" class="ui green button" onclick="MWP_WFAD.blockIPTwo(jQuery('#wfManualBlock').val(), jQuery('#wfReasonManual').val(), true, <?php esc_html_e($site_id); ?>); return false;" />
		  </div>
		</div>
    <?php
  }

    // TODO: review later
    public static function gen_country_blocking_tab($post, $metabox = null)
    {
        $site_id = isset($metabox['args']['websiteid']) ? $metabox['args']['websiteid'] : null;
        $include_dir = MainWP_Wordfence_Extension::$plugin_dir . 'includes/';
        require_once( $include_dir . 'menu_blocking_countryBlocking.php' );
    }

}
