<?php
class MainWP_Wordfence_Log
{
	//Singleton
	private static $instance = null;

	static function get_instance() {

		if ( null == MainWP_Wordfence_Log::$instance ) {
			MainWP_Wordfence_Log::$instance = new MainWP_Wordfence_Log();
		}
		return MainWP_Wordfence_Log::$instance;
	}

	public function __construct() {

	}

	public static function gen_result_tab( $site_id, $site_name = '' ) {
		if ( empty( $site_id ) ) { ?>
      <div class="ui info message"><?php _e( 'Click on a "Show results" link at "Wordfence Dashboard" to view Scan results on a site.', 'mainwp-wordfence-extension' ); ?></div>
    <?php
		return;
		}

    $scanner = MainWP_wfScanner::shared();
    //$issues = wfIssues::shared();
    $debugOn = MainWP_Wordfence_Config_Site::get( 'debugOn', 0, $site_id );

		?>
    <div id="wfLiveTrafficOverlayAnchor"></div>
	  <div id="wfLiveTrafficDisabledMessage">
	      <h2>Status Updates Paused<br /><small>Click inside window to resume</small></h2>
	  </div>

		<div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_scan" debug-on="<?php echo intval($debugOn); ?>" site-id="<?php echo intval( $site_id ); ?>"></div>
		<div class="mwp_wordfence_network_scan_box" site-id="<?php echo $site_id?>" visibleIssuesPanel="new"> <?php

		?>
    <div class="mwp_wordfence_network_scan_inner" >
    	<div class="ui segment" id="mwp_wfc_activity_log_box">
        <h3 class="ui dividing header"><?php echo $site_name . ' ' . __( 'Scan Results', 'mainwp-wordfence-extension' ); ?></h3>
				<div class="ui hidden divider"></div>
				<div class="ui hidden divider"></div>



                <div class="wf-row">
                    <div class="wf-col-xs-12">
                        <div class="wf-block wf-active">
                            <div class="wf-block-content">
                                <ul class="wf-block-list">
                                    <li>
										<?php
										echo MainWP_wfView::create('scanner/scan-starter', array(
											'running' => $scanner->isRunning(),
                                            'site_id' => $site_id
										))->render();
										?>
									</li>
                                    <li id="wf-scan-progress-bar">
                                        <?php
                                        echo MainWP_wfView::create('scanner/scan-progress', array(
                                            'scanner' => $scanner,
                                            'running' => false,
                                        ))->render();
                                        ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

               <div class="wf-row">
                    <div class="wf-col-xs-12">
                        <?php
                        echo MainWP_wfView::create('scanner/scan-progress-detailed', array(
                            'scanner' => $scanner,
                            'site_id' => $site_id
                        ))->render();
                        ?>
                    </div>
                </div>
                <div class="wf-row">
                    <div class="wf-col-xs-12">
                      <?php
                      echo MainWP_wfView::create('scanner/scan-results', array(
                        'scanner' => $scanner,
                        'site_id' => $site_id
                         //'issues' => $issues,
                      ))->render();
                      ?>
                    </div>
                </div>
          </div>


<script type="text/x-jquery-template" id="wfTmpl_scannerDelete">
	<?php
	echo MainWP_wfView::create('common/modal-prompt', array(
		'title' => __('Are you sure you want to delete?', 'wordfence'),
		'messageHTML' => '<p class="wf-callout-warning"><i class="wf-fa wf-fa-exclamation-triangle" aria-hidden="true"></i> ' . __('<strong>WARNING:</strong> If you delete the wrong file, it could cause your WordPress website to stop functioning, and you will probably have to restore from a backup.', 'wordfence') . '</p>' .
			'<p>' . __('Do not delete files on your system unless you\'re ABSOLUTELY sure you know what you\'re doing. If you delete the wrong file it could cause your WordPress website to stop functioning and you will probably have to restore from backups. If you\'re unsure, Cancel and work with your hosting provider to clean your system of infected files.', 'wordfence') . '</p>',
		'primaryButton' => array('id' => 'wf-scanner-prompt-cancel', 'label' => __('Cancel', 'wordfence'), 'link' => '#', 'type' => 'wf-btn-default'),
		'secondaryButtons' => array(array('id' => 'wf-scanner-prompt-confirm', 'label' => __('Delete Files', 'wordfence'), 'link' => '#', 'type' => 'wf-btn-danger')),
	))->render();
	?>
</script>

<script type="text/x-jquery-template" id="wfTmpl_scannerRepair">
	<?php
	echo MainWP_wfView::create('common/modal-prompt', array(
		'title' => __('Are you sure you want to repair?', 'wordfence'),
		'message' => __('Do not repair files on your system unless you\'re ABSOLUTELY sure you know what you\'re doing. If you repair the wrong file it could cause your WordPress website to stop functioning and you will probably have to restore from backups. If you\'re unsure, Cancel and work with your hosting provider to clean your system of infected files.', 'wordfence'),
		'primaryButton' => array('id' => 'wf-scanner-prompt-cancel', 'label' => __('Cancel', 'wordfence'), 'link' => '#'),
		'secondaryButtons' => array(array('id' => 'wf-scanner-prompt-confirm', 'label' => __('Repair Files', 'wordfence'), 'link' => '#')),
	))->render();
	?>
</script>


        <?php

        $location = 'update-core.php';
		$update_url = 'admin.php?page=SiteOpen&newWindow=yes&websiteid='. $site_id . '&location=' . base64_encode( $location ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' );

        echo MainWP_wfView::create('scanner/site-cleaning', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/site-cleaning-high-sense', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/no-issues', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-wfUpgrade', array('update_url' => $update_url))->render();
        echo MainWP_wfView::create('scanner/issue-wfPluginUpgrade', array('update_url' => $update_url))->render();
        echo MainWP_wfView::create('scanner/issue-wfThemeUpgrade', array('update_url' => $update_url))->render();
        echo MainWP_wfView::create('scanner/issue-wfPluginRemoved', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-wfPluginAbandoned', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-wfPluginVulnerable', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-file', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-knownfile', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-configReadable', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-publiclyAccessible', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-coreUnknown', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-dnsChange', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-diskSpace', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-easyPassword', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-commentBadURL', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-postBadURL', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-postBadTitle', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-optionBadURL', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-database', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-checkSpamIP', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-spamvertizeCheck', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-checkGSB', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-checkHowGetIPs', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-suspiciousAdminUsers', array('site_id' => $site_id))->render();
        echo MainWP_wfView::create('scanner/issue-timelimit', array('site_id' => $site_id))->render();

		echo '</div>';
		echo '</div>';
	}

}
