<?php
/**
 * This file contains the contents of the Dropbox admin options page.
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


require_once dirname( __FILE__ ) . '/wptc-options-helper.php';

$options_helper = new Wptc_Options_Helper();

try {

	$config = MainWP_WPTC_Factory::get( 'config' );

	$dropbox = MainWP_WPTC_Factory::get( MAINWP_WPTC_DEFAULT_REPO );

	$is_user_logged_in_var  = $config->get_option( 'is_user_logged_in' );
	$main_account_email_var = $config->get_option( 'main_account_email' );


	$backup = new MainWP_WPTC_BackupController();

	$tcStartBackupNow = false;

	list($unixtime, $frequency) = $config->get_schedule();
	if ( ! $frequency ) {
		$frequency = 'weekly';
	}

	if ( ! get_settings_errors( 'wptc_options' ) ) {
		$dropbox_location   = $config->get_option( 'dropbox_location' );
		$store_in_subfolder = $config->get_option( 'store_in_subfolder' );
	}

	$time = date( 'H:i', $unixtime );
	$day  = date( 'D', $unixtime );
	add_thickbox();

	// getting schedule options
	$schedule_backup   = $config->get_option( 'schedule_backup' );
	$auto_backup       = $config->get_option( 'auto_backup_switch' );
	$schedule_interval = $config->get_option( 'schedule_interval' );
	$schedule_day      = $config->get_option( 'schedule_day' );
	$schedule_time_str = $config->get_option( 'schedule_time_str' );
	$wptc_timezone     = $config->get_option( 'wptc_timezone' );
	$hightlight        = '';
	if ( isset( $_GET['highlight'] ) ) {
		$hightlight = $_GET['highlight'];
	}
	/*
	if(isset($_GET['error'])){
		if($dropbox){
		$dropbox->unlink_account()->init();
		}
	*/
	?>
	<link rel="stylesheet" type="text/css" href="<?php echo $uri; ?>/source/wptc-dialog.css"/>
	<div class="wrap" id="wptc">

	<form id="backup_to_dropbox_options" name="backup_to_dropbox_options" action="" method="post">
	<?php

		$is_error_empty         = empty( $_GET['error'] );
		$is_user_logged_in      = $config->get_option( 'is_user_logged_in' );
		$default_repo_connected = $config->get_option( 'default_repo' );
		$is_uuid                = isset( $_GET['uid'] );
		$is_new_backup          = isset( $_GET['new_backup'] );
		$show_connect_pane      = isset( $_GET['show_connect_pane'] );
		$is_initial_setup       = isset( $_GET['initial_setup'] );
		$is_cloud_auth_action   = isset( $_GET['cloud_auth_action'] );
		$privileges_wptc        = $options_helper->get_unserialized_privileges();
		$is_auth                = false;

		$saved_email = $saved_pwd = '';
	if ( ! empty( $config->get_option( 'encoded_acc_email' ) ) ) {
		$saved_email = base64_decode( $config->get_option( 'encoded_acc_email' ) );
		$saved_pwd   = base64_decode( $config->get_option( 'encoded_acc_pwd' ) );
	}

		global $mainwp_timecapsule_current_site_id;

	?>

	<?php settings_errors(); ?>
		<input type="hidden" name="mainwp_wptc_general_settings_page" id="mainwp_wptc_general_settings_page" value="<?php echo empty( $mainwp_timecapsule_current_site_id ) && ! MainWP_TimeCapsule::is_managesites_page() ? 1 : 0; ?>"/>

	<?php
	if ( $mainwp_timecapsule_current_site_id ) {
		$main_account_email_var = $config->get_option( 'main_account_email' );
		$signed_in_repos_var    = $config->get_option( 'signed_in_repos' );
		$plan_name_var          = $config->get_option( 'plan_name' );
		$plan_interval_var      = $config->get_option( 'plan_interval' );

		if ( empty( $main_account_email_var ) ) {
			$location = '/wp-admin/admin.php?page=wp-time-capsule';
		} else {
			$location = '/wp-admin/admin.php?page=wp-time-capsule&logout=true';
		}

		$location         = base64_encode( $location );
		$account_open_url = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $mainwp_timecapsule_current_site_id . '&openUrl=yes&location=' . $location . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' );
	} else {
		$main_account_email_var = $saved_email;
		$signed_in_repos_var    = '';
		$plan_name_var          = '';
		$plan_interval_var      = '';
		$account_open_url       = '';
	}


	?>


				<h3 class="ui dividing header"><?php echo __( 'WP Time Capsule Login', 'mainwp-timecapsule-extension' ); ?></h3>
				<div class="ui red message" style="display:none" id="mainwp_wptc_error_div"></div>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo __( 'Login to your WP Time Capsule account', 'mainwp-timecapsule-extension' ); ?></label>
					<div class="three wide column">
						<input type="text" id="wptc_main_acc_email" name="wptc_main_acc_email" placeholder="Email" value="<?php echo $saved_email; ?>" autofocus>
					</div>
					<div class="three wide column">
						<input type="password" id="wptc_main_acc_pwd" name="wptc_main_acc_pwd" value="<?php echo $saved_pwd; ?>" placeholder="Password" >
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"></label>
					<div class="six wide column">
						<a href=<?php echo MainWP_WPTC_APSERVER_URL_FORGET; ?> target="_blank" class="forgot_password ui button">Forgot Password?</a> <input type="submit" name="mainwp_wptc_login" id="mainwp_wptc_login" class="btn_pri ui green basic button" value="Login" />
					</div>
				</div>

				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo __( 'Dont have an account yet?', 'mainwp-timecapsule-extension' ); ?></label>
					<div class="ten wide column">
						<a href=<?php echo MainWP_WPTC_APSERVER_URL_SIGNUP; ?> target="_blank" class="ui green button">Signup Now</a>
					</div>
				</div>
		<script type="text/javascript" language="javascript">
			jQuery(document).ready(function ($) {
				$(".mainwp_wtc_wcard").on('keypress', '#wptc_main_acc_email', function(e){
					mainwp_triggerLoginWptc(e);
				});
				$(".mainwp_wtc_wcard").on('keypress', '#wptc_main_acc_pwd', function(e){
					mainwp_triggerLoginWptc(e);
				});
			});
		</script>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo __( 'Account', 'mainwp-timecapsule-extension' ); ?></label>
					<div class="ten wide column">
							<?php echo ! empty( $main_account_email_var ) ? $main_account_email_var : ''; ?>
						<?php if ( $mainwp_timecapsule_current_site_id ) { ?>
							<a class="change_dbox_user_tc ui mini green basic button" href="<?php echo $account_open_url; ?>" target="_blank"><?php echo empty( $main_account_email_var ) ? 'Login' : 'Logout'; ?></a>
					<?php } ?>
					</div>
				</div>
	<?php
	$show_plan_note = true;
	if ( $mainwp_timecapsule_current_site_id ) {
		if ( ! empty( $plan_name_var ) ) {
			 $show_plan_note = false;
			?>
						<div class="ui grid field">
							<label class="six wide column middle aligned"><?php echo __( 'Active Plan', 'mainwp-timecapsule-extension' ); ?></label>
							<div class="ten wide column">
								<span><?php echo $plan_name_var; ?></span>  <a class="ui basic green button change_dbox_user_tc " href="<?php echo MainWP_WPTC_APSERVER_URL . '/my-account.php'; ?>" target="_blank"> Change </a>
								<input type="button" class="ui green button" id="wptc_sync_purchase" value="Sync Purchase" />
							</div>
						</div>
		<?php } ?>
	<?php } ?>

		<?php
		$initial_setup = MainWP_WPTC_Base_Factory::get( 'MainWP_Wptc_InitialSetup' );

		if ( $mainwp_timecapsule_current_site_id ) {
			$location        = '/wp-admin/admin.php?page=wp-time-capsule&show_connect_pane=set';
			$location        = base64_encode( $location );
			$change_open_url = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $mainwp_timecapsule_current_site_id . '&openUrl=yes&location=' . $location . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' );
		} else {
			$change_open_url = '';
		}

		$change_store_link = '';
		if ( ! empty( $change_open_url ) ) {
			$change_store_link = '<a class="change_dbox_user_tc ui green basic button" id="change_repo_wptc" href="' . $change_open_url . '" ' . ( $mainwp_timecapsule_current_site_id ? 'target="_blank"' : '' ) . '>Change</a>';
		}

		$show_store_note = true;
		if ( $mainwp_timecapsule_current_site_id ) {
			if ( ! empty( $signed_in_repos_var ) ) {
				$show_store_note = false;
				?>
								<div class="ui grid field">
									<label class="six wide column middle aligned"><?php echo __( 'Cloud Storage Account', 'mainwp-timecapsule-extension' ); ?></label>
									<div class="ten wide column">
								   <?php echo $signed_in_repos_var; ?>
									</div>
								</div>
				<?php } ?>
		<?php } ?>

	  <?php
		if ( $show_store_note ) {
			?>
				<div class="ui grid field">
					<label class="six wide column middle aligned"><?php echo __( 'Cloud Storage Account', 'mainwp-timecapsule-extension' ); ?></label>
					<div class="ten wide column">
						<select name="select_wptc_cloud_storage" disabled id="select_wptc_cloud_storage" class="wptc_general_inputs ui dropdown">
						<option value="dropbox" label="Dropbox">Dropbox</option>
						<option value="g_drive" label="Google Drive">Google Drive</option>
						<option value="s3" label="Amazon S3">Amazon S3</option>
					</select>
					</div>
				</div>



		<?php } ?>


		<?php wp_nonce_field( 'mainwp_wordpress_time_capsule_options_save' ); ?>
	</form>
	<?php

} catch ( Exception $e ) {
	echo '<h3>Error</h3>';
	echo '<p>' . __( 'There was a fatal error loading WordPress Time Capsule. Please fix the problems listed and reload the page.', 'wptc' ) . '</h3>';
	echo '<p>' . __( 'If the problem persists please re-install WordPress Time Capsule.', 'wptc' ) . '</h3>';
	echo '<p><strong>' . __( 'Error message:' ) . '</strong> ' . $e->getMessage() . '</p>';

	// dark_debug($e, "--------e errors--------");

	// MainWP_WPTC_Factory::get('config')->set_option('default_repo', false);
}
?>
<div id="dialog_content_id" style="display:none;"> <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p></div>
<a style="display:none" href="#TB_inline?width=600&height=550&inlineId=dialog_content_id" class="thickbox wptc-thickbox">View my inline content!</a>
</div>
<?php

function get_s3_select_box_div( $selected_bucket_region ) {
	$buc_region_arr = array(
		''               => 'Select Bucket Region',
		''               => 'US Standard',
		'us-west-2'      => 'US West (Oregon) Region',
		'us-west-1'      => 'US West (Northern California) Region',
		'eu-west-1'      => 'EU (Ireland) Region',
		'ap-southeast-1' => 'Asia Pacific (Singapore) Region',
		'ap-southeast-2' => 'Asia Pacific (Sydney) Region',
		'ap-northeast-1' => 'Asia Pacific (Tokyo) Region',
		'sa-east-1'      => 'South America (Sao Paulo) Region',
		'eu-central-1'   => 'EU (Frankfurt)',
		'cn-north-1'     => 'China (Beijing) Region',
	);

	$div = '<select name="as3_bucket_region" id="as3_bucket_region" class="wptc_general_inputs" style="width:45%; height: 38px;">';

	foreach ( $buc_region_arr as $k => $v ) {
		$selected = '';
		if ( $k == $selected_bucket_region ) {
			$selected = 'selected';
		}
		$div = $div . '<option value="' . $k . '" ' . $selected . ' class="dropOption" >' . $v . '</option>';
	}
	$div = $div . '</select>';
	return $div;
}

function get_signed_in_repos_div( &$config ) {
	$div                 = '';
	$signed_in_repos_arr = $config->get_option( 'signed_in_repos' );
	if ( empty( $signed_in_repos_arr ) ) {
		$signed_in_repos_arr = array();
	} else {
		$signed_in_repos_arr = unserialize( $signed_in_repos_arr );
	}
	$currently_selected = array();
	foreach ( $signed_in_repos_arr as $k => $v ) {
		if ( $k == MAINWP_WPTC_DEFAULT_REPO ) {
			$div .= $v;
		}
	}
	return $div;
}


?>
<script src="<?php echo $uri; ?>/source/wptime-capsule.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" language="javascript">

	<?php
	global $wpdb;
	$fcount = false; // $wpdb->get_results('SELECT COUNT(*) as files FROM ' . $wpdb->base_prefix . 'wptc_processed_files');
	$fresh  = ( ! empty( $fcount ) && ! empty( $fcount[0]->files ) && $fcount[0]->files > 0 ) ? 'yes' : 'no';
	?>

	jQuery(document).ready(function ($) {
		var temp_obj = '<?php $config = MainWP_WPTC_Factory::get( 'config' ); ?>';
		wptc_update_progress = '<?php echo $config->get_option( 'wptc_update_progress' ); ?>';
		adminUrlWptc = '<?php echo network_admin_url(); ?>';
		freshBackupWptc = '<?php echo $fresh; ?>';
		var tcStartBackupNow = '<?php echo $tcStartBackupNow; ?>';
		var cur_backup_type = $("#backup_type").val();
		if (cur_backup_type == 'WEEKLYBACKUP' || cur_backup_type == 'AUTOBACKUP') {
			$('#select_wptc_default_schedule').hide();
			$('.init_backup_time_n_zone').html('Timezone');
		}

		if(tcStartBackupNow){
			mainwp_wtc_start_backup_func('');
		}

		$('#store_in_subfolder').click(function (e) {
			if ($('#store_in_subfolder').is(':checked')) {
				$('.dropbox_location').show('fast', function() {
					$('#dropbox_location').focus();
				});
			} else {
				$('#dropbox_location').val('');
				$('.dropbox_location').hide();
			}
		});

		$("#backup_type").on('change', function(){
			var cur_backup_type = $(this).val();
			if(cur_backup_type == 'WEEKLYBACKUP' || cur_backup_type == 'AUTOBACKUP'){
				$('#select_wptc_default_schedule').hide();
				$('.init_backup_time_n_zone').html('Timezone');
			} else {
				$('#select_wptc_default_schedule').show();
				$('.init_backup_time_n_zone').html('Backup Schedule and Timezone');
			}
		});


		$('#skip_initial_set_up').click(function(){
			parent.location.assign('<?php echo network_admin_url( 'admin.php?page=wp-time-capsule&new_backup=set' ); ?>');
		});

		$('#continue_to_initial_setup').click(function(){
			jQuery.post(ajaxurl, { action : 'continue_with_wtc' }, function(data) {
				if(data=='authorized'){
					parent.location.assign('<?php echo network_admin_url( 'admin.php?page=wp-time-capsule&initial_setup=set' ); ?>');
				}
				else{
					var data_str = '';
					if(typeof data == 'string'){
						data_str = data;
					}
					parent.location.assign('<?php echo network_admin_url( 'admin.php?page=wp-time-capsule' ); ?>&error='+data_str);;
				}
			});
		});

		//call trigger when page is load by options on DB
		<?php	if ( $wptc_timezone != '' ) { ?>
			$('#wptc_timezone').val('<?php echo $wptc_timezone; ?>');
			<?php
		}
		?>
	});

	function getCloudLabelFromVal(val){
		if(typeof val == 'undefined' || val == ''){
			return 'Cloud';
		}
		var cloudLabels = {};
		cloudLabels['g_drive'] = 'Google Drive';
		cloudLabels['s3'] = 'Amazon S3';
		cloudLabels['dropbox'] = 'Dropbox';

		return cloudLabels[val];
	}

	function yes_change_acc(){
		document.getElementById('unlink').click();
	}

	function no_change(){
		tb_remove();
	}

	function dropbox_authorize(url) {
		window.open(url);
		document.getElementById('continue').style.display = "block";
		document.getElementById('authorize').style.display = "none";
		document.getElementById('mess').style.display = "none";
		document.getElementById('donot_touch_note').style.display = "none";
	}

</script>
<script type="text/javascript" language="javascript">
	var service_url_wptc = '<?php echo MainWP_WPTC_APSERVER_URL; ?>';
	var wptcOptionsPageURl = '<?php echo MAINWP_WP_TIME_CAPSULE_URL; ?>' ;
</script>
