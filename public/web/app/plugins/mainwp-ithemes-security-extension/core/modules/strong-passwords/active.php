<?php

require_once( 'class-itsec-strong-passwords.php' );
$itsec_strong_passwords = new MainWP_ITSEC_Strong_Passwords();
$itsec_strong_passwords->run( MainWP_ITSEC_Core::get_instance() );
