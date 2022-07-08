<?php
require_once 'bootstrap.php';

if ( class_exists( 'MainWP\Extensions\Lighthouse\MainWP_Lighthouse_Core' ) ) {
	MainWP\Extensions\Lighthouse\MainWP_Lighthouse_Core::get_instance()->mainwp_lighthouse_cron_start();
}