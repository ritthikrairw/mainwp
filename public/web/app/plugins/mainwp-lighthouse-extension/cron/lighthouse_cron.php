<?php
require_once 'bootstrap.php';

if ( class_exists( 'MainWP\Extensions\Lighthouse\MainWP_Lighthouse_Admin' ) ) {
	MainWP\Extensions\Lighthouse\MainWP_Lighthouse_Admin::cron_lighthouse_alert();
}
