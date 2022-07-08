<?php
require_once 'bootstrap.php';

if ( class_exists( 'MainWP\Extensions\Domain_Monitor\MainWP_Domain_Monitor_Core' ) ) {
	MainWP\Extensions\Domain_Monitor\MainWP_Domain_Monitor_Core::get_instance()->mainwp_domain_monitor_cron_start();
}
