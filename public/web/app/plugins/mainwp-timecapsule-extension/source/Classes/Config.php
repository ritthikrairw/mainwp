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

class MainWP_WPTC_Config {
	const MAX_HISTORY_ITEMS = 20;

	private $db, $options;

	public function __construct() {
		$this->db = MainWP_WPTC_Factory::db();
	}


	public function replace_slashes($data) {
		return str_replace('/', DIRECTORY_SEPARATOR, $data);
	}

	public function set_option($name, $value) {
        if (empty($name))
            return false;
        global $mainwp_timecapsule_current_site_id;
        MainWP_TimeCapsule_DB::get_instance()->update_settings_fields( (int)$mainwp_timecapsule_current_site_id, array($name => $value) );
	}

	public function delete_option($option_name){
        if (empty($option_name))
            return false;
        global $mainwp_timecapsule_current_site_id;
        MainWP_TimeCapsule_DB::get_instance()->delete_settings_fields((int)$mainwp_timecapsule_current_site_id, array($option_name) );
	}

	public function get_option($name, $no_cache = false) {
        global $mainwp_timecapsule_current_site_id;
        $site_id = (int)$mainwp_timecapsule_current_site_id;

		if (!isset($this->options[$site_id]) || $no_cache) {
            $this->options[$site_id] = MainWP_TimeCapsule_DB::get_instance()->get_settings($site_id );
		}
		return isset($this->options[$site_id][$name]) ? $this->options[$site_id][$name] : null;
	}

	public function get_schedule() {
		$schedule = null;

		return $schedule;
	}

	public function get_history() {
		$history = $this->get_option('history');
		if (!$history) {
			return array();
		}

		return explode(',', $history);
	}


	public function remove_http($url = '') {
		if ($url == 'http://' OR $url == 'https://') {
			return $url;
		}
		return preg_replace('/^(http|https)\:\/\/(www.)?/i', '', $url);

	}

	public function complete($this_process = null, $ignored_backup = false, $is_error = false) {

	}

	public function hash_pwd($str) {
		return md5($str);
	}

}
