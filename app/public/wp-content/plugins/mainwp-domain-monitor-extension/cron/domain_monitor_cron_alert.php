<?php
require_once 'bootstrap.php';

if ( class_exists( 'MainWP\Extensions\Domain_Monitor\MainWP_Domain_Monitor_Admin' ) ) {
	MainWP\Extensions\Domain_Monitor\MainWP_Domain_Monitor_Admin::cron_domain_monitor_alert();
}
