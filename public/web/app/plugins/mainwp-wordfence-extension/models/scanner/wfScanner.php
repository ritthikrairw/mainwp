<?php
// credit of Wordfence
class MainWP_wfScanner {
	const SCAN_TYPE_QUICK = 'quick';
	const SCAN_TYPE_LIMITED = 'limited';
	const SCAN_TYPE_STANDARD = 'standard';
	const SCAN_TYPE_HIGH_SENSITIVITY = 'highsensitivity';
	const SCAN_TYPE_CUSTOM = 'custom';
	
	const SCAN_SCHEDULING_MODE_AUTOMATIC = 'auto';
	const SCAN_SCHEDULING_MODE_MANUAL = 'manual';
	
	const MANUAL_SCHEDULING_ONCE_DAILY = 'onceDaily';
	const MANUAL_SCHEDULING_TWICE_DAILY = 'twiceDaily';
	const MANUAL_SCHEDULING_EVERY_OTHER_DAY = 'everyOtherDay';
	const MANUAL_SCHEDULING_WEEKDAYS = 'weekdays';
	const MANUAL_SCHEDULING_WEEKENDS = 'weekends';
	const MANUAL_SCHEDULING_ODD_DAYS_WEEKENDS = 'oddDaysWE';
	const MANUAL_SCHEDULING_CUSTOM = 'custom';
	
	const SIGNATURE_MODE_PREMIUM = 'premium';
	const SIGNATURE_MODE_COMMUNITY = 'community';
	
	const STATUS_PENDING = 'pending';
	const STATUS_RUNNING = 'running';
	const STATUS_RUNNING_WARNING = 'running-warning';
	const STATUS_COMPLETE_SUCCESS = 'complete-success';
	const STATUS_COMPLETE_WARNING = 'complete-warning';
	const STATUS_PREMIUM = 'premium';
	const STATUS_DISABLED = 'disabled';

	const STAGE_SPAMVERTISING_CHECKS = 'spamvertising';
	const STAGE_SPAM_CHECK = 'spam';
	const STAGE_BLACKLIST_CHECK = 'blacklist';
	const STAGE_SERVER_STATE = 'server';
	const STAGE_FILE_CHANGES = 'changes';
	const STAGE_PUBLIC_FILES = 'public';
	const STAGE_MALWARE_SCAN = 'malware';
	const STAGE_CONTENT_SAFETY = 'content';
	const STAGE_PASSWORD_STRENGTH = 'password';
	const STAGE_VULNERABILITY_SCAN = 'vulnerability';
	const STAGE_OPTIONS_AUDIT = 'options';
	
	const SUMMARY_TOTAL_USERS = 'totalUsers';
	const SUMMARY_TOTAL_PAGES = 'totalPages';
	const SUMMARY_TOTAL_POSTS = 'totalPosts';
	const SUMMARY_TOTAL_COMMENTS = 'totalComments';
	const SUMMARY_TOTAL_CATEGORIES = 'totalCategories';
	const SUMMARY_TOTAL_TABLES = 'totalTables';
	const SUMMARY_TOTAL_ROWS = 'totalRows';
	const SUMMARY_SCANNED_POSTS = 'scannedPosts';
	const SUMMARY_SCANNED_COMMENTS = 'scannedComments';
	const SUMMARY_SCANNED_FILES = 'scannedFiles';
	const SUMMARY_SCANNED_PLUGINS = 'scannedPlugins';
	const SUMMARY_SCANNED_THEMES = 'scannedThemes';
	const SUMMARY_SCANNED_USERS = 'scannedUsers';
	const SUMMARY_SCANNED_URLS = 'scannedURLs';
		
	private $_summary = false;

    public static function shared() {
		static $_scanner = null;
		if ($_scanner === null) {
			$_scanner = new MainWP_wfScanner();
		}
		return $_scanner;
	}
	
	public function getSummaryItem($key, $default = false) {
		$this->_fetchSummaryItems();
		if (isset($this->_summary[$key])) {
			return $this->_summary[$key];
		}
		return $default;
	}
	
    private function _fetchSummaryItems() {
		if ($this->_summary !== false) { 
			return;
		}
		
		$this->_summary = MainWP_Wordfence_Config::get_ser('wf_summaryItems', array());
	}
    
    public function stageStatus() {
		$status = $this->_defaultStageStatuses();
//		$runningStatus = MainWP_Wordfence_Config::get_ser('scanStageStatuses', array(), false);
//		$status = array_merge($status, $runningStatus);
		
		foreach ($status as $stage => &$value) { //Convert value array into status only
			$value = $value['status'];
//			if (!$this->isRunning() && $value == self::STATUS_RUNNING) {
//				$value = self::STATUS_PENDING;
//			}
		}
		
		return $status;
	}
    
    function isRunning() {
        return false;
    }
    
    private function _defaultStageStatuses() {
		$status = array(
			self::STAGE_SPAMVERTISING_CHECKS => array('status' => ($this->isPremiumScan() ? self::STATUS_PENDING : self::STATUS_PREMIUM), 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_SPAM_CHECK => array('status' => ($this->isPremiumScan() ? self::STATUS_PENDING : self::STATUS_PREMIUM), 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_BLACKLIST_CHECK => array('status' => ($this->isPremiumScan() ? self::STATUS_PENDING : self::STATUS_PREMIUM), 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_SERVER_STATE => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_FILE_CHANGES => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_PUBLIC_FILES => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_MALWARE_SCAN => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_CONTENT_SAFETY => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_PASSWORD_STRENGTH => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_VULNERABILITY_SCAN => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
			self::STAGE_OPTIONS_AUDIT => array('status' => self::STATUS_PENDING, 'started' => 0, 'finished' => 0, 'expected' => 0),
		);
		
		foreach ($status as $stage => &$parameters) {
			if ($parameters['status'] == self::STATUS_PREMIUM) {
				continue;
			}
			
			$options = $this->_scanJobsForStage($stage);
			if (count($options)) {
				$parameters['expected'] = count($options);
			}
			else {
				$parameters['status'] = self::STATUS_DISABLED;
			}
		}
		
		return $status;
	}
        
    public function isPremiumScan() {
        return true;
		//return MainWP_Wordfence_Config_Site::get('isPaid');
	}
    
    private function _scanJobsForStage($stage) {
		$options = array();
		switch ($stage) {
			case self::STAGE_SPAMVERTISING_CHECKS:
				$options = array(
					'spamvertizeCheck',
				);
				break;
			case self::STAGE_SPAM_CHECK:
				$options = array(
					'checkSpamIP',
				);
				break;
			case self::STAGE_BLACKLIST_CHECK:
				$options = array(
					'scansEnabled_checkGSB',
				);
				break;
			case self::STAGE_SERVER_STATE:
				$options = array(
					'scansEnabled_checkHowGetIPs',
					'scansEnabled_diskSpace',
					//'scansEnabled_dns',
				);
				break;
			case self::STAGE_FILE_CHANGES:
				$options = array(
					'scansEnabled_core',
					'scansEnabled_themes',
					'scansEnabled_plugins',
					'scansEnabled_coreUnknown',
				);
				break;
			case self::STAGE_PUBLIC_FILES:
				$options = array(
					'scansEnabled_checkReadableConfig',
					'scansEnabled_suspectedFiles',
				);
				break;
			case self::STAGE_MALWARE_SCAN:
				$options = array(
					'scansEnabled_malware',
					'scansEnabled_fileContents',
				);
				break;
			case self::STAGE_CONTENT_SAFETY:
				$options = array(
					'scansEnabled_posts',
					'scansEnabled_comments',
					'scansEnabled_fileContentsGSB', 
				);
				break;
			case self::STAGE_PASSWORD_STRENGTH:
				$options = array(
					'scansEnabled_passwds',
				);
				break;
			case self::STAGE_VULNERABILITY_SCAN:
				$options = array(
					'scansEnabled_oldVersions',
				);
				break;
			case self::STAGE_OPTIONS_AUDIT:
				$options = array(
					'scansEnabled_suspiciousOptions',
					'scansEnabled_suspiciousAdminUsers',
				);
				break;
		}
		
		$enabledOptions = array(); //$this->scanOptions();
		$filteredOptions = array();
		foreach ($options as $o) {
			if (isset($enabledOptions[$o]) && $enabledOptions[$o]) {
				$filteredOptions[] = $o;
			}
		}
		
		return $filteredOptions;
	}
    
    public function scanOptions() {
		switch ($this->scanType()) {
			case self::SCAN_TYPE_QUICK:
				return self::quickScanTypeOptions();
			case self::SCAN_TYPE_LIMITED:
				return self::limitedScanTypeOptions();
			case self::SCAN_TYPE_STANDARD:
				return self::standardScanTypeOptions();
			case self::SCAN_TYPE_HIGH_SENSITIVITY:
				return self::highSensitivityScanTypeOptions();
			case self::SCAN_TYPE_CUSTOM:
				return self::customScanTypeOptions();
		}
	}
    
    public static function limitedScanTypeOptions($isPaid = null) {
		return array_merge(self::_inactiveScanOptions(), array(
			'scansEnabled_checkHowGetIPs' => true,
			'scansEnabled_malware' => true,
			'scansEnabled_fileContents' => true,
			'scansEnabled_fileContentsGSB' => true,
			'scansEnabled_suspiciousOptions' => true,
			'scansEnabled_oldVersions' => true,
			'lowResourceScansEnabled' => true,
//			'scan_exclude' => wfConfig::get('scan_exclude', ''),
//			'scan_include_extra' => wfConfig::get('scan_include_extra', ''),
		));        
	}
	
	/**
	 * Returns an array of the scan options (as keys) and the corresponding value for the standard scan type.
	 *
	 * @return array
	 */
	public static function standardScanTypeOptions( $isPaid = null ) {
		$return = array_merge(self::_inactiveScanOptions(), array(
			'spamvertizeCheck' => true,
			'checkSpamIP' => true,
			'scansEnabled_checkGSB' => true,
			'scansEnabled_checkHowGetIPs' => true,
			'scansEnabled_checkReadableConfig' => true,
			'scansEnabled_suspectedFiles' => true,
			'scansEnabled_core' => true,
			'scansEnabled_coreUnknown' => true,
			'scansEnabled_malware' => true,
			'scansEnabled_fileContents' => true,
			'scansEnabled_fileContentsGSB' => true,
			'scansEnabled_posts' => true,
			'scansEnabled_comments' => true,
			'scansEnabled_suspiciousOptions' => true,
			'scansEnabled_oldVersions' => true,
			'scansEnabled_suspiciousAdminUsers' => true,
			'scansEnabled_passwds' => true,
			'scansEnabled_diskSpace' => true,
			//'scansEnabled_dns' => true,
//			'scan_exclude' => wfConfig::get('scan_exclude', ''),
//			'scan_include_extra' => wfConfig::get('scan_include_extra', ''),
		));
        
        if ($isPaid === false) {
            unset($return['scansEnabled_checkGSB']);
            unset($return['spamvertizeCheck']);
            unset($return['checkSpamIP']);        
        }        
        
        return $return;                
	}
	
	/**
	 * Returns an array of the scan options (as keys) and the corresponding value for the high sensitivity scan type.
	 *
	 * @return array
	 */
	public static function highSensitivityScanTypeOptions($isPaid = null) {
		$return =  array_merge(self::_inactiveScanOptions(), array(
			'spamvertizeCheck' => true,
			'checkSpamIP' => true,
			'scansEnabled_checkGSB' => true,
			'scansEnabled_checkHowGetIPs' => true,
			'scansEnabled_checkReadableConfig' => true,
			'scansEnabled_suspectedFiles' => true,
			'scansEnabled_core' => true,
			'scansEnabled_themes' => true,
			'scansEnabled_plugins' => true,
			'scansEnabled_coreUnknown' => true,
			'scansEnabled_malware' => true,
			'scansEnabled_fileContents' => true,
			'scansEnabled_fileContentsGSB' => true,
			'scansEnabled_posts' => true,
			'scansEnabled_comments' => true,
			'scansEnabled_suspiciousOptions' => true,
			'scansEnabled_oldVersions' => true,
			'scansEnabled_suspiciousAdminUsers' => true,
			'scansEnabled_passwds' => true,
			'scansEnabled_diskSpace' => true,
			//'scansEnabled_dns' => true,
			'other_scanOutside' => true,
			'scansEnabled_scanImages' => true,
			//'scansEnabled_highSense' => true,
//			'scan_exclude' => wfConfig::get('scan_exclude', ''),
//			'scan_include_extra' => wfConfig::get('scan_include_extra', ''),
		));
        
        if ($isPaid === false) {
            unset($return['scansEnabled_checkGSB']);
            unset($return['spamvertizeCheck']);
            unset($return['checkSpamIP']);        
        }        
        
        return $return;  
	}
	
	/**
	 * Returns an array of the scan options (as keys) and the corresponding value for the custom scan type.
	 *
	 * @return array
	 */
	public static function customScanTypeOptions() {
		$allOptions = self::_inactiveScanOptions();
		
		return $allOptions;
	}
	
	/**
	 * Returns an array of scan options and their inactive values for convenience in merging with the various scan type
	 * option arrays.
	 * 
	 * @return array
	 */
	protected static function _inactiveScanOptions() {
		return array(
			'spamvertizeCheck' => false,
			'checkSpamIP' => false,
			'scansEnabled_checkGSB' => false,
			'scansEnabled_checkHowGetIPs' => false,
			'scansEnabled_checkReadableConfig' => false,
			'scansEnabled_suspectedFiles' => false,
			'scansEnabled_core' => false,
			'scansEnabled_themes' => false,
			'scansEnabled_plugins' => false,
			'scansEnabled_coreUnknown' => false,
			'scansEnabled_malware' => false,
			'scansEnabled_fileContents' => false,
			'scan_include_extra' => '',
			'scansEnabled_fileContentsGSB' => false,
			'scansEnabled_posts' => false,
			'scansEnabled_comments' => false,
			'scansEnabled_suspiciousOptions' => false,
			'scansEnabled_oldVersions' => false,
			'scansEnabled_suspiciousAdminUsers' => false,
			'scansEnabled_passwds' => false,
			'scansEnabled_diskSpace' => false,
			//'scansEnabled_dns' => false,
			'other_scanOutside' => false,
			'scansEnabled_scanImages' => false,
			//'scansEnabled_highSense' => false,
			'lowResourceScansEnabled' => false,
			'scan_exclude' => '',
		);
	}
	
    
    
}