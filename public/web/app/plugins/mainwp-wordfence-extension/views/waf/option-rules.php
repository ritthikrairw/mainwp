<?php
if (!defined('MAINWP_WORDFENCE_PATH')) { exit; }
?>
<ul class="wf-option wf-flex-vertical wf-flex-align-left">
	<li class="wf-option-title"><strong>Rules</strong> </li>
    <li class="wf-option-subtitle" id="mwp_waf_isSubDirectoryInstallation" style="display: none"><?php echo __('You are currently running the WAF from another WordPress installation. These rules can be disabled or enabled once you configure the firewall to run correctly on this site.', 'wordfence'); ?></li>
	<li id="waf-rules-wrapper" class="wf-add-top"></li>
	<?php //if (!WFWAF_SUBDIRECTORY_INSTALL): ?>
	<li>
		<ul class="wf-option wf-option-footer wf-padding-no-bottom">
			<li><a class="ui green button waf-rules-refresh" href="#">Manually Refresh Rules</a>&nbsp;&nbsp;</li>
			<li class="wf-padding-add-top-xs-small"><em id="waf-rules-next-update"></em></li>
		</ul>
		<script type="application/javascript">
			(function($) {
				$('.waf-rules-refresh').on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					MWP_WFAD.wafUpdateRules_New(<?php echo intval($site_id);?>);
				});
			})(jQuery);

            var lastUpdated = null;
            var nextUpdate = null;

            if (lastUpdated)
                MWP_WFAD.renderWAFRulesLastUpdated(new Date(lastUpdated * 1000));

            if (nextUpdate)
                MWP_WFAD.renderWAFRulesLastUpdated(new Date(nextUpdate * 1000));

		</script>
	</li>
	<?php //endif ?>
</ul>
