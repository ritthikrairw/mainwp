<?php
if (!defined('MAINWP_WORDFENCE_PATH')) { exit; }
/**
 * Presents the scan activity log and its controls.
 *
 * Expects $scanner.
 *
 * @var wfScanner $scanner The scanner state.
 */

$view_full_log_link = '?_wfsf=viewActivityLog&nonce=child_temp_nonce';
$view_full_log_link = 'admin.php?page=Extensions-Mainwp-Wordfence-Extension&action=open_site&websiteid=' . $site_id . '&open_location=' . base64_encode($view_full_log_link);

?>
<div class="wf-alert wf-alert-danger" id="wf-scan-failed" style="display: none;">
	<h4><?php _e('Scan Failed', 'wordfence'); ?></h4>
	<p><?php _e('The current scan looks like it has failed. Its last status update was <span id="wf-scan-failed-time-ago"></span> ago. You may continue to wait in case it resumes or stop and restart the scan. Some sites may need adjustments to run scans reliably.', 'wordfence'); ?> </p>
	<p class="wf-padding-add-top"><a href="#" class="wf-btn wf-btn-primary wf-btn-callout-subtle" onclick="MWP_WFAD.killScan(null, <?php echo intval($site_id); ?>); return false;"><?php _e('Cancel Scan', 'wordfence'); ?></a></p>
</div>
<ul class="wf-flex-horizontal wf-flex-vertical-xs wf-flex-full-width wf-no-top wf-no-bottom">
	<li id="wf-scan-last-status"></li>
	<li id="wf-scan-activity-log-controls">
<!--        <a href="#" id="wf-scan-email-activity-log"><?php _e('Email<span class="wf-hidden-xs"> activity</span> log', 'wordfence'); ?></a>&nbsp;&nbsp;&nbsp;&nbsp;-->
        <a href="<?php echo esc_attr($view_full_log_link); ?>" id="wf-scan-full-activity-log" target="_blank"><?php _e('View<span class="wf-hidden-xs"> full</span> log', 'wordfence'); ?></a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="#" id="wf-scan-toggle-activity-log" class="<?php echo (MainWP_wfPersistenceController::shared()->isActive('wf-scan-activity-log') ? 'wf-active' : '') ?>"><span class="wf-scan-activity-log-visible"><?php _e('Hide log', 'wordfence'); ?></span><span class="wf-scan-activity-log-hidden"><?php _e('Show log', 'wordfence'); ?></span></a></li>
</ul>
<div id="wf-scan-running-bar" style="display: none;"><div id="wf-scan-running-bar-pill"></div></div>
<ul id="wf-scan-activity-log" class="<?php echo (MainWP_wfPersistenceController::shared()->isActive('wf-scan-activity-log') ? ' wf-active' : '') ?>"></ul>
<script type="application/javascript">
	(function($) {
		$(function() {
			$('#wf-scan-email-activity-log').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				MWP_WFAD.emailActivityLog();
			});

			$('#wf-scan-toggle-activity-log').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var isActive = $('#wf-scan-activity-log').hasClass('wf-active');
				if (isActive) {
					$('#wf-scan-activity-log').slideUp({
						always: function() {
							$('#wf-scan-activity-log').removeClass('wf-active');
							$('#wf-scan-toggle-activity-log').removeClass('wf-active');
						}
					});
				}
				else {
					$('#wf-scan-activity-log').slideDown({
						always: function() {
							$('#wf-scan-activity-log').addClass('wf-active');
							$('#wf-scan-toggle-activity-log').addClass('wf-active');
						}
					});
				}

				MWP_WFAD.ajax('mainwp_wfc_saveDisclosureState', {name: 'wf-scan-activity-log', state: !isActive}, function() {});
			});
		});
	})(jQuery);
</script>