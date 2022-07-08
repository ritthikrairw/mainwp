<?php

require_once 'bootstrap.php';

if ( class_exists( 'MainWP_Dripper' ) ) {
	MainWP_Dripper::cron_posting();
}
