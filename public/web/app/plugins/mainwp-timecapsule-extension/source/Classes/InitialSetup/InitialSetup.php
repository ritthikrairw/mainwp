<?php

class MainWP_Wptc_InitialSetup extends MainWP_Wptc_InitialSetup_Init {
	public $tabs;
	private $config,
			$options;
	public function __construct(){
		$this->config = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_InitialSetup_Config');
		$this->options = MainWP_WPTC_Factory::get('config');
	}

	public function load_page(){

	}

	public function get_s3_creds_box_div() {
		$div = '';
		$sub_div = '<a class="s3_doc_wptc" href="http://wptc.helpscoutdocs.com/article/4-connect-your-amazon-s3-account" target="_blank">See how to connect my AS3 account</a>';

		$sub_div = $sub_div . '<div class="l1"  style="padding: 0px;"> <input type="text" name="as3_access_key" class="wptc_general_inputs" style="width: 45%;" placeholder="Access Key" id="as3_access_key" required value="' . $this->config->get_option('as3_access_key') . '" /> </div>';

		$sub_div = $sub_div . '<div class="l1"  style="padding: 0px;"> <input type="text" name="as3_secure_key" class="wptc_general_inputs" style="width: 45%;" placeholder="Secure Key" id="as3_secure_key" required value="' . $this->config->get_option('as3_secure_key') . '" /> </div>';

		$sub_div = $sub_div . '<div class="l1"  style="padding: 0px;">'
					. $this->get_s3_select_box_div($this->config->get_option('as3_bucket_region')) .
					'</div>';

		$sub_div = $sub_div . '<div class="l1"  style="padding: 0px;"> <input type="text" class="wptc_general_inputs" style="width: 45%;" name="as3_bucket_name" placeholder="Bucket Name" id="as3_bucket_name" required value="' . $this->config->get_option('as3_bucket_name') . '" /> </div>';

		$div = $div . '<div class="l1 s3_inputs creds_box_inputs"  style="padding-bottom: 10px; display:none; margin-top: -36px; position: relative;"><div style="text-align: center; font-size: 13px; padding-bottom: 10px;">' . $sub_div . '</div></div>';

		return $div;
	}

	private function get_s3_select_box_div($selected_bucket_region) {
		$buc_region_arr = array('' => 'Select Bucket Region', '' => 'US Standard', 'us-west-2' => 'US West (Oregon) Region', 'us-west-1' => 'US West (Northern California) Region', 'eu-west-1' => 'EU (Ireland) Region', 'ap-southeast-1' => 'Asia Pacific (Singapore) Region', 'ap-southeast-2' => 'Asia Pacific (Sydney) Region', 'ap-northeast-1' => 'Asia Pacific (Tokyo) Region', 'sa-east-1' => 'South America (Sao Paulo) Region', 'eu-central-1' => 'EU (Frankfurt)', 'cn-north-1' => 'China (Beijing) Region');

		$div = '<select name="as3_bucket_region" id="as3_bucket_region" class="wptc_general_inputs" style="width:45%; height: 38px;">';

		foreach ($buc_region_arr as $k => $v) {
			$selected = '';
			if ($k == $selected_bucket_region) {
				$selected = 'selected';
			}
			$div = $div . '<option value="' . $k . '" ' . $selected . ' class="dropOption" >' . $v . '</option>';
		}
		$div = $div . '</select>';
		return $div;
	}

	public function store_cloud_access_token_wptc(){
		if ((isset($_GET['cloud_auth_action']) && $_GET['cloud_auth_action'] == 'g_drive') && isset($_GET['code']) && !isset($_GET['error'])) {
			$this->config->set_option('oauth_state_g_drive', 'access');
			$req_token_dets['refresh_token'] = $_GET['code'];
			$this->config->set_option('gdrive_old_token', serialize($req_token_dets));
		} else if ((isset($_GET['cloud_auth_action']) && $_GET['cloud_auth_action'] == 'dropbox') && isset($_GET['code']) && !isset($_GET['error'])) {
			$access_token = base64_decode(urldecode($_GET['code']));
			$this->config->set_option('dropbox_access_token', $access_token);
			$this->config->set_option('dropbox_oauth_state', 'access');
		}
	}


	public function get_select_backup_type_setting(){
		$select_start = '<select id="backup_type" name="backup_type">';
		$current_setting = $this->config->get_option('backup_type_setting');
		$daily_backup_selected = '';
		$weekly_backup_selected = '';
		if ($current_setting == 'SCHEDULE') {
			$daily_backup_selected = 'selected';
		} else if($current_setting == 'WEEKLYBACKUP'){
			$weekly_backup_selected = 'selected';
		}
		$body_content = apply_filters('inside_backup_type_settings_wptc_h', '')."<option value='SCHEDULE' ".$daily_backup_selected.">Daily</option>";
		$select_end = '</select>';
		return $select_start.$body_content.$select_end;
	}

	public function get_select_cloud_dialog_div() {
		$div = '';
		$sub_div = '';
		$display_status = $gdrive_not_eligible = $dropbox_not_eligible = $s3_not_eligible = 'display:none';
		$div .= '<div class="l1"  style="padding-bottom: 10px; padding-top: 10px">
					<select name="select_wptc_cloud_storage" id="select_wptc_cloud_storage" class="wptc_general_inputs" style="width:45%;height: 38px;">
                <option value="" class="dummy_select">Select your cloud storage app</option>';
        $dropbox_not_eligible = 'display:none';
        $div .= '<option value="dropbox" label="Dropbox">Dropbox</option>;';
        $gdrive_not_eligible = 'display:none';
        $div .= '<option value="g_drive" label="Google Drive">Google Drive</option>';
		$div .= '<option value="s3" label="Amazon S3" >Amazon S3</option>';
		$div .= '</select>
				</div>';
        $s3_not_eligible = 'display:none';
        $div .=  $this->get_s3_creds_box_div();
		$display_status = ($s3_not_eligible == 'display:block' || $gdrive_not_eligible == 'display:block' || $dropbox_not_eligible == 'display:block') ? 'display:block' : 'display:none';
		$div = $div . '<div class="cloud_error_mesg"></div><input type="button" id="mainwp_wptc_connect_to_cloud" class="btn_pri cloud_go_btn" style="margin: 0px 32.9% 30px; width: 330px; text-align: center; display: none;" value="Connect my cloud account" >';
		$div .= '<div style="clear:both"></div>';
		$div .= '<div id="mess" style="text-align: center; font-size: 13px; padding-top: 10px; padding-bottom: 10px; display: none;">To change store go to the child site.</div>';
		$div .= "<div class='dashicons-before dashicons-warning' id='s3_seperate_bucket_note' style='display:none; font-style: italic; left: 10px; font-size: 13px;'><span style='line-height: 22px'>Please create a separate bucket on Amazon S3 since we will be enabling versioning on that bucket. We create subfolders for each site, so you don't have to create a new bucket everytime.</span></div>";
		$div .= "<div style='height: 60px; position: relative;".$display_status." ' id='php_req_note_wptc'><div class='dashicons-before dashicons-warning' id='dropbox_php_req_note' style='position: absolute;font-size: 12px;top: -27px;width: 100%;font-style: italic;left: 10px;padding-top: 10px;padding-bottom: 10px; ".$dropbox_not_eligible."'><span style='position: absolute;top: 11px;left: 24px; '>Dropbox requires PHP v5.3.1+. Please upgrade your PHP to use Dropbox.</span></div><div class='dashicons-before dashicons-warning' id='g_drive_php_req_note' style='position: absolute;font-size: 12px;top: 0px;width: 100%;font-style: italic;left: 10px;padding-top: 10px;padding-bottom: 10px; ".$gdrive_not_eligible."'><span style='position: absolute;top: 11px;left: 24px; '>Google Drive requires PHP v5.4.0+. Please upgrade your PHP to use Google Drive.</span></div><div class='dashicons-before dashicons-warning' id='s3_php_req_note' style='position: absolute;font-size: 12px;top: 26px;width: 100%;font-style: italic;left: 10px;padding-top: 10px;padding-bottom: 10px; ".$s3_not_eligible."'><span style='position: absolute;top: 11px;left: 24px;''>Amazon S3 requires PHP v5.3.3+. Please upgrade your PHP to use Amazon S3.</span></div></div>";
		return $div;
	}

	public function is_fresh_backup(){
		global $wpdb;
		$fcount = $wpdb->get_results('SELECT COUNT(*) as files FROM ' . $wpdb->base_prefix . 'wptc_processed_files');
		return (!empty($fcount) && !empty($fcount[0]->files) && $fcount[0]->files > 0) ? 'yes' : 'no';
	}

}