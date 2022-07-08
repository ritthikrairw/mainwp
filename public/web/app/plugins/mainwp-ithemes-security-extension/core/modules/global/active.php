<?php

function mainwp_itsec_global_filter_whitelisted_ips( $whitelisted_ips ) {
	return array_merge( $whitelisted_ips, MainWP_ITSEC_Modules::get_setting( 'global', 'lockout_white_list', array() ) );
}
add_action( 'mainwp_itsec_white_ips', 'mainwp_itsec_global_filter_whitelisted_ips', 0 );

