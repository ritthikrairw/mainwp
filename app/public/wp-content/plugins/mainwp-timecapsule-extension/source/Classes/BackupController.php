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

class MainWP_WPTC_BackupController {
	private
	$dropbox,
	$config,
	$output,
	$processed_file_count,
	$WptcAutoBackupHooksObj,
	$iter_loop_limit,
	$exclude_class_obj;
	public static function construct() {
		return new self();
	}

	public function __construct($output = null) {
		$this->config = MainWP_WPTC_Factory::get('config');
		$this->dropbox = MainWP_WPTC_Factory::get(MAINWP_WPTC_DEFAULT_REPO);
		//$this->output = $output ? $output : MainWP_WPTC_Extension_Manager::construct()->get_output();
		$this->iter_loop_count = 100;
//		$this->exclude_class_obj = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_ExcludeOption');
	}

	public function get_recursive_iterator_objs($path) {
//		manual_debug_wptc('', 'beforeStartingFileList');
//
//		$Mfile_arr = array();
//		$is_auto_backup = $this->config->get_option('wptc_sub_cycle_running');
//		if ($is_auto_backup) {
//			$Mfile_arr = apply_filters('add_auto_backup_record_to_backup', '');
//		} else {
//			//dark_debug(array(), "--------not auto backup--------");
//			$Mfile_arr[] = get_single_iterator_obj($path);
//		}
//		return $Mfile_arr;
	}

	public function replace_slashes($directory_name) {
		return str_replace(array("/"), DIRECTORY_SEPARATOR, $directory_name);
	}

	public function execute($type = '') {

	}

	function add_backup_general_data($complete_data = null){
		//Add Backup general data into DB
		if ($complete_data != null) {
			if ($complete_data['files_count'] > 0) {
				$config = MainWP_WPTC_Factory::get('config');
				$Btype = $config->get_option('wptc_current_backup_type');
				global $wpdb;
				$wpdb->insert($wpdb->base_prefix . 'wptc_backups', array('backup_id' => $complete_data['backup_id'], 'backup_type' => $Btype, 'files_count' => $complete_data['files_count'], 'memory_usage' => $complete_data['memory_usage']));
			}
		}
	}
}