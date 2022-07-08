<?php
require_once 'bootstrap.php';

if ( class_exists( 'MainWP_Pro_Reports' ) ) {
	MainWP_Pro_Reports::cron_notice_ready_reports();
}
