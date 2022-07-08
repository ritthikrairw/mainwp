<?php
require_once MAINWP_WPTC_CLASSES_DIR.'ActivityLog.php';

$wptc_list_table = new MainWP_WPTC_List_Table();
//$wptc_list_table->prepare_items();

if (isset($_GET['type'])) {
	$type = $_GET['type'];
} else {
	$type = 'all';
}
add_thickbox();

global $mainwpWPTimeCapsuleExtensionActivator;
?>
<div class="tablenav">
			<ul class="subsubsub">
				<li>
					<a href="<?php echo $uri; ?>" id="all" <?php echo ($type == 'all') ? 'class="current"' : ""; ?>>All Activities <span class="count"></span></a> |
				</li>
				<li>
					<a href="<?php echo $uri . '&type=backups'; ?>" id="backups" <?php echo ($type == 'backups') ? 'class="current"' : ""; ?>>Backups <span class="count"></span></a> |
				</li>
				<li>
					<a href="<?php echo $uri . '&type=restores'; ?>" id="restore" <?php echo ($type == 'restores') ? 'class="current"' : ""; ?>>Restores<span class="count"></span></a> |
				</li>

                <li>
					<a href="<?php echo $uri . '&type=staging'; ?>" id="staging" <?php echo ($type == 'staging') ? 'class="current"' : ""; ?>>Staging<span class="count"></span></a> |
				</li>
                <li>
					<a href="<?php echo $uri . '&type=backup_and_update'; ?>" id="backup_and_update" <?php echo ($type == 'backup_and_update') ? 'class="current"' : ""; ?>>Backup and update<span class="count"></span></a> |
				</li>
                <li>
					<a href="<?php echo $uri . '&type=auto_update'; ?>" id="auto_update" <?php echo ($type == 'auto_update') ? 'class="current"' : ""; ?>>Auto-update<span class="count"></span></a> |
				</li>

				<li>
					<a href="<?php echo $uri . '&type=others'; ?>" id="other" <?php echo ($type == 'others') ? 'class="current"' : ""; ?>>Others <span class="count"></span></a>
				</li>
</ul>
    <ul class="subsubsub" style="float: right; margin-right: 20px; cursor: pointer;">
        <li>
            <a id="mwp_wptc_clear_log">Clear Logs</a>
	</li>
    </ul>
</div>
	<div class="wrap">
<?php

    global $mainwpWPTimeCapsuleExtensionActivator;
    $paged = !empty($_GET["paged"]) ? intval($_GET["paged"]) : 1;
//    if (empty($paged) || !is_numeric($paged) || $paged <= 0) {$paged = 1;} //Page Number
	
	if (empty($paged))
		$paged = 0;
	
    $post_data = array(
        'mwp_action' => 'get_logs_rows',
        'type' => $type,
        'paged' => $paged
    );

    $information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $current_site_id, 'time_capsule', $post_data );

    if (is_array($information) && isset($information['items'])) {
        $display_rows = unserialize(base64_decode($information['display_rows']));
        $wptc_list_table->prepare_data($information);
        $wptc_list_table->prepare_items();
        $wptc_list_table->display();
    } else if (is_array($information) && isset($information['error'])) {
        echo '<div class="mainwp-notice mainwp-notice-red">' . $information['error'] . '</div>';
    } else {
        echo '<div class="mainwp-notice mainwp-notice-red">' . __('Undefined error', 'mainwp-timecapsule-extension') . '</div>';
    }
?>

<?php //Table of elements
//$wptc_list_table->display();
?>

	</div>
<div id="dialog_content_id" style="display:none;"> <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p></div>
<a style="display:none" href="#TB_inline?width=600&height=550&inlineId=dialog_content_id" class="thickbox wptc-thickbox">View my inline content!</a>
<?php


wp_enqueue_script('mainwp-wptc-activity', MAINWP_WP_TIME_CAPSULE_URL . '/source/Views/wptc-activity.js', array(), MainWP_WPTC_VERSION);

