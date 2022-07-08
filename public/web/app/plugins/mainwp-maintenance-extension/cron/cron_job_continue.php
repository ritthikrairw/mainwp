<?php
require_once 'bootstrap.php';

if ( class_exists( 'Maintenance_Extension' ) ) {
	Maintenance_Extension::cron_get_scheduled_to_continue();
}
