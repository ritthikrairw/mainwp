<?php

/*
Plugin-Name: Wordfence Security
Plugin-URI: http://www.wordfence.com/
Description: Wordfence Security - Anti-virus, Firewall and High Speed Cache
Author: Wordfence
Version: 5.2.1
Author-URI: http://www.wordfence.com/
*/

class MainWP_Wordfence_Config {
	// aka wfConfig.
	const AUTOLOAD      = 'yes';
	const DONT_AUTOLOAD = 'no';

	const TYPE_STRING = 'string';

	public static $option_handle   = 'mainwp_wordfence_config_option';
	public static $option          = array();
	public static $apiKeys         = null;
	public static $isPaids         = null;
	public static $current_version = '5.2.2';

	const SCAN_TYPE_QUICK            = 'quick';
	const SCAN_TYPE_LIMITED          = 'limited';
	const SCAN_TYPE_STANDARD         = 'standard';
	const SCAN_TYPE_HIGH_SENSITIVITY = 'highsensitivity';
	const SCAN_TYPE_CUSTOM           = 'custom';

	const OPTIONS_TYPE_GLOBAL       = 'global';
	const OPTIONS_TYPE_FIREWALL     = 'firewall';
	const OPTIONS_TYPE_BLOCKING     = 'blocking';
	const OPTIONS_TYPE_SCANNER      = 'scanner';
	const OPTIONS_TYPE_TWO_FACTOR   = 'twofactor';
	const OPTIONS_TYPE_LIVE_TRAFFIC = 'livetraffic';
	const OPTIONS_TYPE_COMMENT_SPAM = 'commentspam';
	const OPTIONS_TYPE_DIAGNOSTICS  = 'diagnostics';
	const OPTIONS_TYPE_ALL          = 'alloptions';

	const TYPE_INT = 'integer';

	const SEVERITY_NONE     = 0;
	const SEVERITY_LOW      = 25;
	const SEVERITY_MEDIUM   = 50;
	const SEVERITY_HIGH     = 75;
	const SEVERITY_CRITICAL = 100;


	public static $options_filter = array(
		'displayTopLevelOptions',
		'displayTopLevelBlocking',
		'displayTopLevelLiveTraffic',
		'alertEmails',
		'alertOn_adminLogin',
		'alertOn_firstAdminLoginOnly',
		'alertOn_scanIssues', // new.
		'alertOn_wafDeactivated', // new.
		'alertOn_severityLevel', // new.
		'alertOn_block',
		// 'alertOn_critical',
		'alertOn_loginLockout',
		'alertOn_breachLogin',
		'alertOn_lostPasswdForm',
		'alertOn_nonAdminLogin',
		'alertOn_firstNonAdminLoginOnly',
		'alertOn_wordfenceDeactivated',
		'alertOn_update',
		// 'alertOn_warnings',
		'alert_maxHourly',
		'autoUpdate',
		'firewallEnabled',
		'howGetIPs',
		'liveTrafficEnabled',
		'loginSec_blockAdminReg',
		'loginSec_countFailMins',
		'loginSec_disableAuthorScan',
		'notification_updatesNeeded',
		'notification_securityAlerts',
		'notification_promotions',
		'notification_blogHighlights',
		'notification_productUpdates',
		'notification_scanStatus',
		'loginSec_lockInvalidUsers',
		'loginSec_breachPasswds_enabled',
		'loginSec_breachPasswds',
		'loginSec_lockoutMins',
		'loginSec_maskLoginErrors',
		'loginSec_maxFailures',
		'loginSec_maxForgotPasswd',
		'loginSec_strongPasswds_enabled',
		'loginSec_strongPasswds',
		'loginSec_userBlacklist',
		'loginSecurityEnabled',
		'other_scanOutside',
		'scan_exclude',
		'scan_maxIssues',
		'scan_maxDuration',
		'scansEnabled_checkReadableConfig',
		'scansEnabled_suspectedFiles',
		'scansEnabled_comments',
		'scansEnabled_core',
		'scansEnabled_diskSpace',
		// 'scansEnabled_dns',
		'scansEnabled_fileContents',
		'scansEnabled_fileContentsGSB',
		'scan_include_extra',
		// 'scansEnabled_heartbleed',
		'scansEnabled_suspiciousOptions',
		'scansEnabled_checkHowGetIPs',      // 'scansEnabled_highSense',
		'lowResourceScansEnabled',
		'scansEnabled_malware',
		'scansEnabled_oldVersions',
		'scansEnabled_suspiciousAdminUsers',
		'scansEnabled_passwds',
		'scansEnabled_plugins',
		'scansEnabled_coreUnknown',
		'scansEnabled_posts',
		'scansEnabled_scanImages',
		'scansEnabled_themes',
		'scheduledScansEnabled',
		'securityLevel',
		'scheduleScan', // mainwp custom options, send to child but not save
		// 'blockFakeBots',
		'neverBlockBG',
		'maxGlobalRequests',
		'maxGlobalRequests_action',
		'maxRequestsCrawlers',
		'maxRequestsCrawlers_action',
		'max404Crawlers',
		'max404Crawlers_action',
		'maxRequestsHumans',
		'maxRequestsHumans_action',
		'max404Humans',
		'max404Humans_action',
		'blockedTime',
		'liveTraf_ignorePublishers',
		'liveTraf_displayExpandedRecords',
		'liveTraf_ignoreUsers',
		'liveTraf_ignoreIPs',
		'liveTraf_ignoreUA',
		'liveTraf_maxRows',
		'liveTraf_maxAge',
		'displayTopLevelLiveTraffic',
		'whitelisted',
		'whitelistedServices',
		'bannedURLs',
		'other_hideWPVersion',
		// 'other_noAnonMemberComments',
		// 'other_scanComments',
		'other_pwStrengthOnUpdate',
		'other_WFNet',
		'maxMem',
		'maxExecutionTime',
		'actUpdateInterval',
		'debugOn',
		'deleteTablesOnDeact',
		// 'disableCookies',
		'liveActivityPauseEnabled',
		'startScansRemotely',
		// 'disableConfigCaching',
		// 'addCacheComment', // removed
		'disableCodeExecutionUploads',
		// 'isPaid',
		// 'advancedCommentScanning',
		'scansEnabled_checkGSB',
		'checkSpamIP',
		'spamvertizeCheck',
		// 'scansEnabled_public',
		'email_summary_enabled',
		'email_summary_dashboard_widget_enabled',
		'ssl_verify',
		'email_summary_interval',
		'email_summary_excluded_directories',
		'allowed404s',
		'wafAlertWhitelist',
		// 'ajaxWatcherDisabled_front',
		// 'ajaxWatcherDisabled_admin',
		'wafAlertOnAttacks',
		'howGetIPs_trusted_proxies',
		'other_bypassLitespeedNoabort',
		'disableWAFIPBlocking',
		'other_blockBadPOST',
		'blockCustomText',
		'displayTopLevelBlocking',
		'betaThreatDefenseFeed',
		'wordfenceI18n',
		'avoid_php_input',
		'scanType',
		'schedMode', // paid, if free then auto
		'wafStatus',
		'learningModeGracePeriodEnabled',
		'learningModeGracePeriod',
	);

	public static $defaultConfig = array(
		'checkboxes'  => array(
			// "alertOn_critical" => array('value' => true, 'autoload' => self::AUTOLOAD),
			'alertOn_update'                         => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			// "alertOn_warnings" => array('value' => true, 'autoload' => self::AUTOLOAD),
			'alertOn_throttle'                       => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_block'                          => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_loginLockout'                   => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_breachLogin'                    => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_lostPasswdForm'                 => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_adminLogin'                     => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_firstAdminLoginOnly'            => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			), // new
			'alertOn_scanIssues'                     => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_wafDeactivated'                 => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_nonAdminLogin'                  => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'alertOn_firstNonAdminLoginOnly'         => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			), // new
			'alertOn_wordfenceDeactivated'           => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			), // new
			'liveTrafficEnabled'                     => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			// "advancedCommentScanning" => array('value' => false, 'autoload' => self::AUTOLOAD),
			'scansEnabled_checkGSB'                  => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'checkSpamIP'                            => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'spamvertizeCheck'                       => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'liveTraf_ignorePublishers'              => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'liveTraf_displayExpandedRecords'        => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),          // "perfLoggingEnabled" => array('value' => false, 'autoload' => self::AUTOLOAD),
			'scheduledScansEnabled'                  => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'lowResourceScansEnabled'                => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			), // new
			// "scansEnabled_public" => array('value' => false, 'autoload' => self::AUTOLOAD),
			// "scansEnabled_heartbleed" => array('value' => true, 'autoload' => self::AUTOLOAD),
			'scansEnabled_checkHowGetIPs'            => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_core'                      => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_themes'                    => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_plugins'                   => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_coreUnknown'               => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			), // new
			'scansEnabled_malware'                   => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_fileContents'              => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_fileContentsGSB'           => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_checkReadableConfig'       => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_suspectedFiles'            => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			), // new
			'scansEnabled_posts'                     => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_comments'                  => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_suspiciousOptions'         => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_passwds'                   => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_diskSpace'                 => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_options'                   => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			), // new
			// "scansEnabled_wpscan_fullPathDisclosure" => array('value' => true, 'autoload' => self::AUTOLOAD), // new
			// "scansEnabled_wpscan_directoryListingEnabled" => array('value' => true, 'autoload' => self::AUTOLOAD), // new
			// "scansEnabled_dns" => array('value' => true, 'autoload' => self::AUTOLOAD),
			'scansEnabled_scanImages'                => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			// "scansEnabled_highSense" => array('value' => false, 'autoload' => self::AUTOLOAD),
			'scansEnabled_oldVersions'               => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'scansEnabled_suspiciousAdminUsers'      => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'firewallEnabled'                        => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			// "blockFakeBots" => array('value' => false, 'autoload' => self::AUTOLOAD),
			'autoBlockScanners'                      => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'loginSecurityEnabled'                   => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'loginSec_lockInvalidUsers'              => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'loginSec_breachPasswds_enabled'         => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'loginSec_strongPasswds_enabled'         => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'loginSec_maskLoginErrors'               => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'loginSec_blockAdminReg'                 => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'loginSec_disableAuthorScan'             => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			// "loginSec_disableOEmbedAuthor" => array('value' => false, 'autoload' => self::AUTOLOAD), // new
			'notification_updatesNeeded'             => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'notification_securityAlerts'            => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'notification_promotions'                => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'notification_blogHighlights'            => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'notification_productUpdates'            => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'notification_scanStatus'                => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'other_hideWPVersion'                    => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			// "other_noAnonMemberComments" => array('value' => true, 'autoload' => self::AUTOLOAD),
			'other_blockBadPOST'                     => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'displayTopLevelBlocking'                => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			// "other_scanComments" => array('value' => true, 'autoload' => self::AUTOLOAD),
			'other_pwStrengthOnUpdate'               => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'other_WFNet'                            => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'other_bypassLitespeedNoabort'           => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'other_scanOutside'                      => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'deleteTablesOnDeact'                    => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'autoUpdate'                             => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			// "disableCookies" => array('value' => false, 'autoload' => self::AUTOLOAD),
			'liveActivityPauseEnabled'               => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'startScansRemotely'                     => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			// "disableConfigCaching" => array('value' => false, 'autoload' => self::AUTOLOAD),
			// "addCacheComment" => array('value' => false, 'autoload' => self::AUTOLOAD),
			'disableCodeExecutionUploads'            => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'allowHTTPSCaching'                      => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'debugOn'                                => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			),
			'email_summary_enabled'                  => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'email_summary_dashboard_widget_enabled' => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			'ssl_verify'                             => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			),
			// 'ajaxWatcherDisabled_front' => array('value' => false, 'autoload' => self::AUTOLOAD), // new
			// 'ajaxWatcherDisabled_admin' => array('value' => false, 'autoload' => self::AUTOLOAD), // new
			'wafAlertOnAttacks'                      => array(
				'value'    => true,
				'autoload' => self::AUTOLOAD,
			), // new
			'disableWAFIPBlocking'                   => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			), // new
			'betaThreatDefenseFeed'                  => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			), // new
			'wordfenceI18n'                          => array(
				'value'    => false,
				'autoload' => self::AUTOLOAD,
			), // new
			'scheduleScan',
		),
		'otherParams' => array(
			'scan_include_extra'                 => '',
			'alertEmails'                        => '',
			'liveTraf_ignoreUsers'               => '',
			'liveTraf_ignoreIPs'                 => '',
			'liveTraf_ignoreUA'                  => '',
			'apiKey'                             => '',
			'maxMem'                             => '256',
			'scan_exclude'                       => '',
			'scan_maxIssues'                     => 1000, /* new */
			'scan_maxDuration'                   => '',
			'whitelisted'                        => '',
			'bannedURLs'                         => '',
			'maxExecutionTime'                   => '',
			'howGetIPs'                          => '',
			'actUpdateInterval'                  => '',
			'alert_maxHourly'                    => 0,
			'loginSec_userBlacklist'             => '',
			'displayTopLevelOptions',
			'displayTopLevelBlocking',
			'displayTopLevelLiveTraffic',
			'liveTraf_maxRows'                   => 2000,
			'liveTraf_maxAge'                    => 30,
			'displayTopLevelLiveTraffic'         => false,
			'neverBlockBG'                       => 'neverBlockVerified',
			'loginSec_countFailMins'             => '240',
			'loginSec_lockoutMins'               => '240',
			'loginSec_strongPasswds'             => 'pubs',
			'loginSec_breachPasswds'             => 'admins',
			'loginSec_maxFailures'               => '20',
			'loginSec_maxForgotPasswd'           => '20',
			'maxGlobalRequests'                  => 'DISABLED',
			'maxGlobalRequests_action'           => 'throttle',
			'maxRequestsCrawlers'                => 'DISABLED',
			'maxRequestsCrawlers_action'         => 'throttle',
			'maxRequestsHumans'                  => 'DISABLED',
			'maxRequestsHumans_action'           => 'throttle',
			'max404Crawlers'                     => 'DISABLED',
			'max404Crawlers_action'              => 'throttle',
			'max404Humans'                       => 'DISABLED',
			'max404Humans_action'                => 'throttle',
			'blockedTime'                        => '300',
			'email_summary_interval'             => 'weekly',
			'alertOn_severityLevel'              => array(
				'value'      => self::SEVERITY_LOW,
				'autoload'   => self::AUTOLOAD,
				'validation' => array( 'type' => self::TYPE_INT ),
			),
			'blockCustomText'                    => array(
				'value'      => '',
				'autoload'   => self::AUTOLOAD,
				'validation' => array( 'type' => self::TYPE_STRING ),
			),

			'email_summary_excluded_directories' => 'wp-content/cache,wp-content/wflogs',
			'allowed404s'                        => "/favicon.ico\n/apple-touch-icon*.png\n/*@2x.png\n/browserconfig.xml",
			'wafAlertWhitelist'                  => '', // new
			'wafAlertInterval'                   => 600, // new, not used
			'wafAlertThreshold'                  => 100, // new , not used
			'howGetIPs_trusted_proxies'          => '',
			'scanType'                           => self::SCAN_TYPE_STANDARD,
			'schedMode'                          => array(
				'value'    => 'auto',
				'autoload' => self::AUTOLOAD,
			),
		),
	);

	public static $diagnosticParams = array(
		// 'addCacheComment',
		'debugOn',
		'startScansRemotely',
		'ssl_verify',
		// 'disableConfigCaching',
		'betaThreatDefenseFeed',
		'wordfenceI18n',
		'avoid_php_input',
	);

	public function __construct() {
		self::load_api_settings();
		self::$option = get_option( self::$option_handle, false );
		if ( false === self::$option ) {
			self::setDefaults();
		}
	}

	public static function getSectionSettings( $section ) {
		$general_opts = array(
			'scheduleScan',
			'apiKey',
			'autoUpdate',
			'alertEmails',
			'displayTopLevelOptions',
			'displayTopLevelBlocking',
			'displayTopLevelLiveTraffic',
			'howGetIPs',
			'howGetIPs_trusted_proxies',
			'other_hideWPVersion',
			'disableCodeExecutionUploads',
			// 'disableCookies',
			'liveActivityPauseEnabled',
			'actUpdateInterval',
			'other_bypassLitespeedNoabort',
			'deleteTablesOnDeact',
			'notification_updatesNeeded',
			'notification_securityAlerts', // paid
			'notification_promotions', // paid
			'notification_blogHighlights', // paid
			'notification_productUpdates', // paid
			'notification_scanStatus',
			'alertOn_update',
			'alertOn_wordfenceDeactivated',
			// 'alertOn_critical',
			// 'alertOn_warnings',
			'alertOn_block',
			'alertOn_loginLockout',
			'alertOn_breachLogin',
			'alertOn_lostPasswdForm',
			'alertOn_adminLogin',
			'alertOn_firstAdminLoginOnly',
			'alertOn_scanIssues', // new.
			'alertOn_wafDeactivated', // new.
			'alertOn_severityLevel', // new.
			'alertOn_nonAdminLogin',
			'alertOn_firstNonAdminLoginOnly',
			'wafAlertOnAttacks',
			'alert_maxHourly',
			'email_summary_enabled',
			'email_summary_interval',
			'email_summary_excluded_directories',
			'email_summary_dashboard_widget_enabled',
			// 'other_noAnonMemberComments',
			// 'other_scanComments',
			// 'advancedCommentScanning' // paid
		);

		$traffic_opts = array(
			'liveTrafficEnabled',
			'liveTraf_ignorePublishers',
			'liveTraf_displayExpandedRecords',
			'liveTraf_ignoreUsers',
			'liveTraf_ignoreIPs',
			'liveTraf_ignoreUA',
			'liveTraf_maxRows',
			'liveTraf_maxAge',
			'displayTopLevelLiveTraffic',
		);

		$firewall_opts    = array(
			'disableWAFIPBlocking',
			'whitelisted',
			'whitelistedServices',
			'bannedURLs',
			'wafAlertWhitelist',
			'firewallEnabled',
			// 'blockFakeBots',
			'neverBlockBG',
			'maxGlobalRequests',
			'maxGlobalRequests_action',
			'maxRequestsCrawlers',
			'maxRequestsCrawlers_action',
			'max404Crawlers',
			'max404Crawlers_action',
			'maxRequestsHumans',
			'maxRequestsHumans_action',
			'max404Humans',
			'max404Humans_action',
			'blockedTime',
			'allowed404s',
			'loginSecurityEnabled',
			'loginSec_maxFailures',
			'loginSec_maxForgotPasswd',
			'loginSec_countFailMins',
			'loginSec_lockoutMins',
			'loginSec_lockInvalidUsers',
			'loginSec_breachPasswds_enabled',
			'loginSec_breachPasswds',
			'loginSec_userBlacklist',
			'loginSec_strongPasswds_enabled',
			'loginSec_strongPasswds',
			'loginSec_maskLoginErrors',
			'loginSec_blockAdminReg',
			'loginSec_disableAuthorScan',
			'other_blockBadPOST',
			'blockCustomText',
			'other_pwStrengthOnUpdate',
			'other_WFNet',
			'wafStatus',
			'learningModeGracePeriodEnabled',
			'learningModeGracePeriod',
		);
		$scan_opts        = array(
			'scansEnabled_checkGSB', // paid
			'spamvertizeCheck', // paid
			'checkSpamIP', // paid
			'scansEnabled_checkHowGetIPs',
			'scansEnabled_checkReadableConfig',
			'scansEnabled_suspectedFiles',
			'scansEnabled_core',
			'scansEnabled_themes',
			'scansEnabled_plugins',
			'scansEnabled_coreUnknown',
			'scansEnabled_malware',
			'scansEnabled_fileContents',
			'scansEnabled_fileContentsGSB',
			'scansEnabled_posts',
			'scansEnabled_comments',
			'scansEnabled_suspiciousOptions',
			'scansEnabled_oldVersions',
			'scansEnabled_suspiciousAdminUsers',
			'scansEnabled_passwds',
			'scansEnabled_diskSpace',
			// 'scansEnabled_dns',
			'other_scanOutside',
			'scansEnabled_scanImages',
			// 'scansEnabled_highSense',
			'scheduledScansEnabled',
			'lowResourceScansEnabled',
			'scan_maxIssues',
			'scan_maxDuration',
			'maxMem',
			'maxExecutionTime',
			'scan_exclude',
			'scan_include_extra',
			'scanType',
			'schedMode',
		);
		$diagnostics_opts = array(
			'debugOn',
			'startScansRemotely',
			'ssl_verify',
			'betaThreatDefenseFeed',
			'wordfenceI18n',
			'avoid_php_input',
		);
		$blocking_opts    = array(
			'displayTopLevelBlocking',
		);

		$options = array();

		switch ( $section ) {
			case self::OPTIONS_TYPE_GLOBAL:
				$options = $general_opts;
				break;
			case self::OPTIONS_TYPE_LIVE_TRAFFIC:
				$options = $traffic_opts;
				break;
			case self::OPTIONS_TYPE_FIREWALL:
				$options = $firewall_opts;
				break;
			case self::OPTIONS_TYPE_SCANNER:
				$options = $scan_opts;
				break;
			case self::OPTIONS_TYPE_DIAGNOSTICS:
				$options = $diagnostics_opts;
				break;
			case self::OPTIONS_TYPE_BLOCKING:
				$options = $blocking_opts;
				break;
			case self::OPTIONS_TYPE_ALL:
				$options = array_merge( $general_opts, $traffic_opts, $firewall_opts, $scan_opts, $diagnostics_opts, $blocking_opts );
				break;
		}
		return $options;
	}

	public static function getExportableOptionsKeys() {
		$ret = array();
		foreach ( self::$defaultConfig['checkboxes'] as $key => $val ) {
			$ret[] = $key;
		}
		foreach ( self::$defaultConfig['otherParams'] as $key => $val ) {
			$ret[] = $key;
		}
		return $ret;
	}
	
	public static function load_api_settings() {
		if ( null === self::$isPaids ) {
			$settings = MainWP_Wordfence_DB::get_instance()->get_settings_fields( array( 'site_id', 'isPaid', 'apiKey' ) );
			if ( $settings ) {
				foreach ( $settings as $setting ) {
					self::$isPaids[ $setting->site_id ] = $setting->isPaid;
					self::$apiKeys[ $setting->site_id ] = $setting->apiKey;
				}
			}
		}
	}

	public static function get_api_settings() {
		$settings = MainWP_Wordfence_DB::get_instance()->get_settings_fields( array( 'site_id', 'isPaid', 'apiKey' ) );
		$resutl = array();
		if ( $settings ) {
			foreach ( $settings as $setting ) {
				$resutl[ $setting->site_id ] = $setting;
			}
		}
		return $resutl;
	}

	public function get_isPaids() {
		self::load_api_settings();
		return self::$isPaids;
	}

	public function get_apiKeys() {
		self::load_api_settings();
		return self::$apiKeys;
	}

	public static function load_settings() {
		self::$option = get_option( self::$option_handle, false );
		if ( false === self::$option ) {
			self::setDefaults();
		}

		return self::$option;
	}

	public static function get( $key = null, $default = false, $site_id = 0 ) {
		if ( $site_id ) {
			if ( 'isPaid' == $key || 'apiKey' == $key ) {
				self::load_api_settings(); // to fix.
			}
			if ( 'isPaid' == $key && isset( self::$isPaids[ $site_id ] ) ) {
				return self::$isPaids[ $site_id ];
			} elseif ( 'apiKey' == $key && isset( self::$apiKeys[ $site_id ] ) ) {
				return self::$apiKeys[ $site_id ];
			}
		} elseif ( isset( self::$option[ $key ] ) ) {
			return self::$option[ $key ];
		}

		return $default;
	}

	public function getVal( $key ) {
		return self::get( $key );
	}

	public static function set( $key, $value ) {
		self::$option[ $key ] = $value;

		return update_option( self::$option_handle, self::$option );
	}

	public static function get_ser( $key, $default = false, $site_id = false ) {
		$serialized = self::get( $key, $default, $site_id );
		return @unserialize( $serialized );
	}

	public static function set_ser( $key, $val ) {
		$data = serialize( $val );
		return self::set( $key, $data );
	}

	public static function setDefaults() {
		foreach ( self::$defaultConfig['checkboxes'] as $key => $val ) {
			if ( in_array( $key, self::$options_filter ) ) {
				if ( self::get( $key ) === false ) {
					self::set( $key, $val ? '1' : '0' );
				}
			}
		}
		foreach ( self::$defaultConfig['otherParams'] as $key => $val ) {
			if ( in_array( $key, self::$options_filter ) ) {
				if ( self::get( $key ) === false ) {
					self::set( $key, $val );
				}
			}
		}

		self::set( 'encKey', substr( MainWP_Wordfence_Utility::big_rando_hex(), 0, 16 ) );
		if ( self::get( 'maxMem', false ) === false ) {
			self::set( 'maxMem', '256' );
		}

		if ( self::get( 'other_scanOutside', false ) === false ) {
			self::set( 'other_scanOutside', 0 );
		}
	}

	public static function parseOptions() {

		$saving_sec  = isset( $_POST['_post_saving_section'] ) ? $_POST['_post_saving_section'] : '';
		$saving_opts = self::getSectionSettings( $saving_sec );

		if ( empty( $saving_sec ) || empty( $saving_opts ) ) {
				return false;
		}
		$ret = array();

		foreach ( $saving_opts as $key ) {
			if ( isset( self::$defaultConfig['checkboxes'][ $key ] ) ) {
				$ret[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0;
			} elseif ( isset( $_POST[ $key ] ) ) {
				$ret[ $key ] = $_POST[ $key ];
			} elseif ( $key == 'schedMode' ) { // value: auto/manual
				$ret[ $key ] = 'auto'; // if schedMode and not is set $_POST['schedMode] then auto
			} elseif ( $key == 'whitelistedServices' ) {
				$cleaned = self::clean( $_POST );
				if ( isset( $cleaned['whitelistedServices'] ) ) {
					$key   = 'whitelistedServices';
					$value = $cleaned['whitelistedServices'];
					// to fix saving on child site.
					$uncheck_services = array(
						'sucuri'      => 0,
						'facebook'    => 0,
						'uptimerobot' => 0,
						'statuscake'  => 0,
						'managewp'    => 0,
						'seznam'      => 0,
					);
					$value            = array_merge( $uncheck_services, $value );
					$ret[ $key ]      = @json_encode( (array) $value );
				}
			} else {
				$ret[ $key ] = '';
			}
		}
		return $ret;

	}

	public static function getHTML( $key ) {
		// return htmlspecialchars( self::get( $key ) );
		return esc_html( self::get( $key ) );
	}

	public static function getJSON( $key, $default = false, $allowCached = true ) {
		$json    = self::get( $key, $default, $allowCached );
		$decoded = @json_decode( $json, true );
		if ( $decoded === null ) {
			return $default;
		}
		return $decoded;
	}

	public static function clean( $changes ) {
		$cleaned = array();
		foreach ( $changes as $key => $value ) {
			if ( preg_match( '/^whitelistedServices_([a-z0-9]+)$/i', $key, $matches ) ) {
				if ( ! isset( $cleaned['whitelistedServices'] ) || ! is_array( $cleaned['whitelistedServices'] ) ) {
					$cleaned['whitelistedServices'] = self::getJSON( 'whitelistedServices', array() );
				}

				$cleaned['whitelistedServices'][ $matches[1] ] = MainWP_wfUtils::truthyToBoolean( $value ) ? 1 : 0;
			}
		}
		return $cleaned;
	}

	public static function inc( $key ) {
		$val = self::get( $key, false );
		if ( ! $val ) {
			$val = 0;
		}
		self::set( $key, $val + 1 );
	}

	public static function f( $key ) {
		echo esc_attr( self::get( $key ) );
	}

	public static function p() {
		return self::get( 'isPaid' );
	}

	public static function cbp( $key, $isPaid = false ) {
		if ( $isPaid && self::get( $key ) ) {
			echo ' checked ';
		}
	}

	public static function cb( $key ) {
		if ( self::get( $key ) ) {
			echo ' checked ';
		}
	}

	public static function sel( $key, $val, $isDefault = false ) {
		if ( ( ! self::get( $key ) ) && $isDefault ) {
			echo ' selected ';
		}
		if ( self::get( $key ) == $val ) {
			echo ' selected ';
		}
	}

	public static function haveAlertEmails() {
		$emails = self::getAlertEmails();

		return sizeof( $emails ) > 0 ? true : false;
	}

	public static function getAlertEmails() {
		$dat    = explode( ',', self::get( 'alertEmails' ) );
		$emails = array();
		foreach ( $dat as $email ) {
			if ( preg_match( '/\@/', $email ) ) {
				$emails[] = trim( $email );
			}
		}
		return $emails;
	}

	public function get_cacheType() {
		return self::get( 'cacheType', '' );
	}

	public function get_AlertEmails() {
		return self::getAlertEmails();
	}

	public static function getAlertLevel() {
		if ( self::get( 'alertOn_warnings' ) ) {
			return 2;
		} elseif ( self::get( 'alertOn_critical' ) ) {
			return 1;
		} else {
			return 0;
		}
	}

	public static function liveTrafficEnabled( $cacheType = null ) {
		if ( ( ! self::get( 'liveTrafficEnabled' ) ) || 'falcon' == $cacheType || 'php' == $cacheType ) {
			return false;
		}

		return true;
	}
}

