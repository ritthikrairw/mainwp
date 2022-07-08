<?php

define( 'MainWP_WPTC_VERSION', '1.8.9' );

if ( ! defined( 'MainWP_WPTC_ENV' ) ) {
	define( 'MainWP_WPTC_ENV', 'production' );
}

if ( MainWP_WPTC_ENV === 'production' ) {
	define( 'MainWP_WPTC_APSERVER_URL', 'https://service.wptimecapsule.com' );
	define( 'MainWP_WPTC_APSERVER_URL_FORGET', 'https://service.wptimecapsule.com/?show_forgot_pwd=true' );
	define( 'MainWP_WPTC_APSERVER_URL_SIGNUP', 'https://service.wptimecapsule.com/signup' );
} elseif ( MainWP_WPTC_ENV === 'staging' ) {
	define( 'MainWP_WPTC_APSERVER_URL', 'https://wptc-dev-service.rxforge.in/service' );
	define( 'MainWP_WPTC_APSERVER_URL_FORGET', 'https://service.wptimecapsule.com/?show_forgot_pwd=true' );
	define( 'MainWP_WPTC_APSERVER_URL_SIGNUP', 'https://service.wptimecapsule.com/signup' );
} else {
	define( 'MainWP_WPTC_APSERVER_URL', 'http://dark.dev.com/wptc-service' );
	define( 'MainWP_WPTC_APSERVER_URL_FORGET', 'https://service.wptimecapsule.com/?show_forgot_pwd=true' );
	define( 'MainWP_WPTC_APSERVER_URL_SIGNUP', 'https://service.wptimecapsule.com/signup' );
}
