<?php

class MainWP_wfPersistenceController {
	private $_disclosureStates;
	
	public static function shared() {
		static $_shared = false;
		if ($_shared === false) {
			$_shared = new MainWP_wfPersistenceController();
		}
		return $_shared;
	}
	
	public function __construct() {
		$this->_disclosureStates = MainWP_Wordfence_Config::get_ser('disclosureStates', array());
	}
	
	/**
	 * Returns whether the options block is in an active state. 
	 * 
	 * @param $key
	 * @return bool
	 */
	public function isActive($key) {
		if (!isset($this->_disclosureStates[$key])) {
			return false;
		}
		return !!$this->_disclosureStates[$key];
	}
	
	/**
	 * Returns whether the options block has been set.
	 *
	 * @param $key
	 * @return bool
	 */
	public function isConfigured($key) {
		return isset($this->_disclosureStates[$key]);
	}
}