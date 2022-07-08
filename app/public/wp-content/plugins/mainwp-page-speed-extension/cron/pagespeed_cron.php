<?php
include_once( 'bootstrap.php' );

if ( class_exists( 'Mainwp_Page_Speed' ) ) {
    Mainwp_Page_Speed::pagespeed_cron_alert();
}
