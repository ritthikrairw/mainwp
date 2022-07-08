<?php

 MainWP_TimeCapsule::ajax_check_data();

$websiteId = intval($_REQUEST['timecapsuleSiteID']);

global $mainwpWPTimeCapsuleExtensionActivator;

$post_data = array(
    'mwp_action' => 'progress_wptc'    
);

$information = apply_filters( 'mainwp_fetchurlauthed', $mainwpWPTimeCapsuleExtensionActivator->get_child_file(), $mainwpWPTimeCapsuleExtensionActivator->get_child_key(), $websiteId, 'time_capsule', $post_data );

if ( is_array( $information ) && isset( $information['result'] )) {
    die( json_encode( $information['result'] ) );
}

die( json_encode( array( 'error' => 'Undefined error.', 'extra' => $information) ) );
        