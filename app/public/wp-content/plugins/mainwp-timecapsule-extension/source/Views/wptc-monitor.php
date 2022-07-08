<?php
/**
* A class with functions the perform a backup of WordPress
*
* @copyright Copyright (C) 2011-2014 Awesoft Pty. Ltd. All rights reserved.
* @author Michael De Wildt (http://www.mikeyd.com.au/)
* @license This program is free software; you can redistribute it and/or modify
*          it under the terms of the GNU General Public License as published by
*          the Free Software Foundation; either version 2 of the License, or
*          (at your option) any later version.
*
*          This program is distributed in the hope that it will be useful,
*          but WITHOUT ANY WARRANTY; without even the implied warranty of
*          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*          GNU General Public License for more details.
*
*          You should have received a copy of the GNU General Public License
*          along with this program; if not, write to the Free Software
*          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
*/

$current_site_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
try {
	//Checking fresh backup
	global $wpdb;

	$fresh = 'no'; //(!($fcount[0]->files > 0)) ? 'yes' : 'no';

	//Initial backup option
	$freshbackupPopUp = false;
	if (isset($_GET['action'])) {
		if ($_GET['action'] == 'initial_setup') {
			$initial_setup = true;
		}
	}
	if (isset($_GET['oauth_token'])) {
		$initial_setup = true;
	}
	if ($fresh == 'yes' && isset($initial_setup) && $initial_setup === true) {
		$freshbackupPopUp = true;
	}
	$start_backup_from_settings = false;
	if (isset($_GET['start_backup_from_settings'])) {
		$start_backup_from_settings = true;
	}

	?>

<link href='<?php echo $uri; ?>/source/fullcalendar-2.0.2/fullcalendar.css?v=<?php echo MainWP_WPTC_VERSION; ?>' rel='stylesheet' />
<link href='<?php echo $uri; ?>/source/fullcalendar-2.0.2/fullcalendar.print.css?v=<?php echo MainWP_WPTC_VERSION; ?>' rel='stylesheet' media='print' />
<link href='<?php echo $uri; ?>/source/tc-ui.css?v=<?php echo MainWP_WPTC_VERSION; ?>' rel='stylesheet' />
<script src='<?php echo $uri; ?>/source/fullcalendar-2.0.2/lib/moment.min.js?v=<?php echo MainWP_WPTC_VERSION; ?>'></script>
<script src='<?php echo $uri; ?>/source/fullcalendar-2.0.2/fullcalendar.js?v=<?php echo MainWP_WPTC_VERSION; ?>'></script>

<?php add_thickbox();?>

<?php

global $mainwpWPTimeCapsuleExtensionActivator;

$site_name = $site_url = $option = '';

if (!empty($current_site_id)) {
  $dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), array($current_site_id), array(), $option );
  if ( count( $dbwebsites ) > 0 ) {
    $current_site = current( $dbwebsites );
    $site_name = $current_site->name;
    $site_url = $current_site->url;
  }
}

?>
<div class="ui segment" id="wptc">
	<?php settings_errors();?>

	<form id="backup_to_dropbox_options" name="backup_to_dropbox_options" action="<?php echo network_admin_url("admin.php?page=wp-time-capsule-monitor"); ?>"  method="post" style=" width: 100%;">
			<div class="bp-progress-calender" style="display: none">
				<div class="l1 wptc_prog_wrap">
					<div class="bp_progress_bar_cont">
						<span id="bp_progress_bar_note"></span>
						<div class="bp_progress_bar" style="width:0%"></div>
					</div>
					<span class="rounded-rectangle-box-wptc reload-image-wptc" id="refresh-c-status-area-wtpc"></span><div class="last-c-sync-wptc">Last reload: - </div>
				</div>
			</div>
			</h2>  <?php wp_nonce_field('mainwp_wordpress_time_capsule_monitor_stop');?>
			<div class="top-links-wptc">
				<?php if (defined('MainWP_WPTC_ENV') && MainWP_WPTC_ENV != 'production'): ?>
					<a style="position: absolute;right: 170px;top: 10px;display: none;" class="restore_err_demo_wptc">Restore error demo</a>
				<?php endif;?>
			</div>
	</form>
    <input type="hidden" name="mainwp_wptc_backups_page" id="mainwp_wptc_backups_page">
	<div id="progress">
		<div class="loading"><i class="fa fa-spinner fa-pulse" style=""></i> <?php _e('Loading...')?></div>
	</div>
</div>

<?php
} catch (Exception $e) {
	echo '<h3>Error</h3>';
	echo '<p>' . __('There was a fatal error loading WordPress Time Capsule. Please fix the problems listed and reload the page.', 'wptc') . '</h3>';
	echo '<p>' . __('If the problem persists please re-install WordPress Time Capsule.', 'wptc') . '</h3>';
	echo '<p><strong>' . __('Error message:') . '</strong> ' . $e->getMessage() . '</p>';
}
?>

<script type="text/javascript" language="javascript">
	//initiating Global Variables here

	var sitenameWPTC = '<?php echo get_bloginfo('name'); ?>';
	freshBackupWptc = '<?php echo $fresh; ?>';

	var bp_in_progress = false;
	var wp_base_prefix_wptc = '<?php global $wpdb;
    echo $wpdb->base_prefix;?>';		//am sending the prefix ; since it is a bridge


	var defaultDateWPTC = '<?php echo date('Y-m-d', microtime(true)) ?>' ;
	var wptcOptionsPageURl = '<?php echo MAINWP_WP_TIME_CAPSULE_URL; ?>' ;
	var this_plugin_url_wptc = '<?php echo plugins_url(); ?>' ;
	var wptcMonitorPageURl = '<?php echo network_admin_url('admin.php?page=wp-time-capsule-monitor'); ?>';

	var on_going_restore_process = false;
	var cuurent_bridge_file_name = seperate_bridge_call = '';

</script>

<?php
wp_enqueue_script('mainwp-wptc-monitor', MAINWP_WP_TIME_CAPSULE_URL. '/source/Views/wptc-monitor.js', array(), MainWP_WPTC_VERSION);
