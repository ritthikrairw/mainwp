<?php
/**
 * MainWP Sucuri security scan notifications.
 *
 * @uses MainWP_Sucuri::cronsecurityscan_notification()
 */

/**
 * Include Cron bootstrap.
 */
include_once( 'bootstrap.php' );

if ( class_exists( 'MainWP_Sucuri' ) ) {
  MainWP_Sucuri::cronsecurityscan_notification();
}
