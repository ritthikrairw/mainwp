<?php

class MainWP_Wptc_Backup_Analytics extends MainWP_Wptc_Analytics {
	protected $config;
	protected $logger;
	private $cron_server_curl;

	public function __construct() {
		$this->config = MainWP_WPTC_Factory::get('config');
		$this->cron_server_curl = MainWP_WPTC_Base_Factory::get('Wptc_Cron_Server_Curl_Wrapper');

	}


	public function get_total_backup_processsed_files() {
		global $wpdb;
		$total_files = "SELECT COUNT(*) FROM " . $wpdb->base_prefix . "wptc_processed_files WHERE is_dir != 1";
		return $wpdb->get_var($total_files);
	}
	public function get_recent_database_size(){
		global $wpdb;
		$sql = "SELECT uploaded_file_size FROM {$wpdb->base_prefix}wptc_processed_files WHERE file LIKE '%-backup.sql%' ORDER BY  file_id DESC LIMIT 1";
		$size = $wpdb->get_var($sql);
		if (empty($size)) {
			return 7;
		}
		return $size;
	}

	public function get_backup_calls_record_arr() {
		$call_records = $this->config->get_option('backup_calls_record');
		if ($call_records) {
			return json_decode($call_records, true);
		}
		return array();
	}

}