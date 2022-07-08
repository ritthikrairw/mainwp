<?php

require_once( dirname( __FILE__ ) . '/class-notification-center.php' );

$center = new MainWP_ITSEC_Notification_Center();
//$center->run();
MainWP_ITSEC_Core::set_notification_center( $center );