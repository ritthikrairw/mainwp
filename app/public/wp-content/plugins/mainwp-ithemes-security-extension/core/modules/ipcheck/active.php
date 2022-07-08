<?php

require_once( 'class-itsec-ipcheck.php' );
$itsec_ip_check = new MainWP_ITSEC_IPCheck( MainWP_ITSEC_Core::get_instance() );
$itsec_ip_check->run();
