<?php
namespace MainWP\Extensions\Domain_Monitor;

class MainWP_Domain_Monitor_Core {

	/**
	 * Static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Domain_Monitor_Core
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * Runs each time the class is called.
	 */
	public function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
	}

	/**
	 * Initiate Hooks
	 *
	 * Initiates hooks for the Domain Monitor extension.
	 */
	public function init() {
		add_action( 'mainwp_domain_monitor_action_cron_start', array( $this, 'mainwp_domain_monitor_cron_start' ), 10, 2 );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
	}

	/**
	 * WHOIS Query
	 *
	 * Performs the WHOIS Query.
	 *
	 * @param string $domain Child site Domain.
	 *
	 * @return string $output WHOIS response.
	 */
	public static function whois_servers() {

		// the list of whois servers
		// most taken from www.iana.org/domains/root/db/
		$whois_servers = array(
			'ac'      => 'whois.nic.ac', // Ascension Island.
		// ad - Andorra - no whois server assigned.
			'ae'      => 'whois.nic.ae', // United Arab Emirates.
			'aero'    => 'whois.aero',
			'af'      => 'whois.nic.af', // Afghanistan.
			'ag'      => 'whois.nic.ag', // Antigua And Barbuda.
			'ai'      => 'whois.ai', // Anguilla.
			'al'      => 'whois.ripe.net', // Albania.
			'am'      => 'whois.amnic.net',  // Armenia.
		// an - Netherlands Antilles - no whois server assigned.
		// ao - Angola - no whois server assigned.
		// aq - Antarctica (New Zealand) - no whois server assigned.
		// ar - Argentina - no whois server assigned.
			'arpa'    => 'whois.iana.org',
			'as'      => 'whois.nic.as', // American Samoa.
			'asia'    => 'whois.nic.asia',
			'at'      => 'whois.nic.at', // Austria.
			'au'      => 'whois.aunic.net', // Australia.
		// aw - Aruba - no whois server assigned.
			'ax'      => 'whois.ax', // Aland Islands.
			'az'      => 'whois.ripe.net', // Azerbaijan.
		// ba - Bosnia And Herzegovina - no whois server assigned.
		// bb - Barbados - no whois server assigned.
		// bd - Bangladesh - no whois server assigned.
			'be'      => 'whois.dns.be', // Belgium.
			'bg'      => 'whois.register.bg', // Bulgaria.
			'bi'      => 'whois.nic.bi', // Burundi.
			'biz'     => 'whois.biz',
			'bj'      => 'whois.nic.bj', // Benin.
		// bm - Bermuda - no whois server assigned.
			'bn'      => 'whois.bn', // Brunei Darussalam.
			'bo'      => 'whois.nic.bo', // Bolivia.
			'br'      => 'whois.registro.br', // Brazil.
			'bt'      => 'whois.netnames.net', // Bhutan.
		// bv - Bouvet Island (Norway) - no whois server assigned.
		// bw - Botswana - no whois server assigned.
			'by'      => 'whois.cctld.by', // Belarus.
			'bz'      => 'whois.belizenic.bz', // Belize.
			'ca'      => 'whois.cira.ca', // Canada.
			'cat'     => 'whois.cat', // Spain.
			'cc'      => 'whois.nic.cc', // Cocos (Keeling) Islands.
			'cd'      => 'whois.nic.cd', // Congo, The Democratic Republic Of The.
		// cf - Central African Republic - no whois server assigned.
			'ch'      => 'whois.nic.ch', // Switzerland.
			'ci'      => 'whois.nic.ci', // Cote d'Ivoire.
			'ck'      => 'whois.nic.ck', // Cook Islands.
			'cl'      => 'whois.nic.cl', // Chile.
		// cm - Cameroon - no whois server assigned.
			'cn'      => 'whois.cnnic.net.cn', // China.
			'co'      => 'whois.nic.co', // Colombia.
			'com'     => 'whois.verisign-grs.com',
			'coop'    => 'whois.nic.coop',
			// cr - Costa Rica - no whois server assigned.
			// cu - Cuba - no whois server assigned.
			// cv - Cape Verde - no whois server assigned.
			// cw - Curacao - no whois server assigned.
			'cx'      => 'whois.nic.cx', // Christmas Island.
		// cy - Cyprus - no whois server assigned.
			'cz'      => 'whois.nic.cz', // Czech Republic.
			'de'      => 'whois.denic.de', // Germany.
		// dj - Djibouti - no whois server assigned.
			'dk'      => 'whois.dk-hostmaster.dk', // Denmark.
			'dm'      => 'whois.nic.dm', // Dominica.
		// do - Dominican Republic - no whois server assigned.
			'dz'      => 'whois.nic.dz', // Algeria.
			'ec'      => 'whois.nic.ec', // Ecuador.
			'edu'     => 'whois.educause.edu',
			'ee'      => 'whois.eenet.ee', // Estonia.
			'eg'      => 'whois.ripe.net', // Egypt.
		// er - Eritrea - no whois server assigned.
			'es'      => 'whois.nic.es', // Spain.
		// et - Ethiopia - no whois server assigned.
			'eu'      => 'whois.eu',
			'fi'      => 'whois.ficora.fi', // Finland.
		// fj - Fiji - no whois server assigned.
		// fk - Falkland Islands - no whois server assigned.
		// fm - Micronesia, Federated States Of - no whois server assigned.
			'fo'      => 'whois.nic.fo', // Faroe Islands.
			'fr'      => 'whois.nic.fr', // France.
		// ga - Gabon - no whois server assigned.
			'gd'      => 'whois.nic.gd', // Grenada.
		// ge - Georgia - no whois server assigned.
		// gf - French Guiana - no whois server assigned.
			'gg'      => 'whois.gg', // Guernsey.
		// gh - Ghana - no whois server assigned.
			'gi'      => 'whois2.afilias-grs.net', // Gibraltar.
			'gl'      => 'whois.nic.gl', // Greenland (Denmark).
		// gm - Gambia - no whois server assigned.
		// gn - Guinea - no whois server assigned.
			'gov'     => 'whois.nic.gov',
			// gr - Greece - no whois server assigned.
			// gt - Guatemala - no whois server assigned.
			'gs'      => 'whois.nic.gs', // South Georgia And The South Sandwich Islands.
		// gu - Guam - no whois server assigned.
		// gw - Guinea-bissau - no whois server assigned.
			'gy'      => 'whois.registry.gy', // Guyana.
			'hk'      => 'whois.hkirc.hk', // Hong Kong.
		// hm - Heard and McDonald Islands (Australia) - no whois server assigned.
			'hn'      => 'whois.nic.hn', // Honduras.
			'hr'      => 'whois.dns.hr', // Croatia.
			'ht'      => 'whois.nic.ht', // Haiti.
			'hu'      => 'whois.nic.hu', // Hungary.
		// id - Indonesia - no whois server assigned.
			'ie'      => 'whois.domainregistry.ie', // Ireland.
			'il'      => 'whois.isoc.org.il', // Israel.
			'im'      => 'whois.nic.im', // Isle of Man.
			'in'      => 'whois.inregistry.net', // India.
			'info'    => 'whois.afilias.net',
			'int'     => 'whois.iana.org',
			'io'      => 'whois.nic.io', // British Indian Ocean Territory.
			'iq'      => 'whois.cmc.iq', // Iraq.
			'ir'      => 'whois.nic.ir', // Iran, Islamic Republic Of.
			'is'      => 'whois.isnic.is', // Iceland.
			'it'      => 'whois.nic.it', // Italy.
			'je'      => 'whois.je', // Jersey.
		// jm - Jamaica - no whois server assigned.
		// jo - Jordan - no whois server assigned.
			'jobs'    => 'jobswhois.verisign-grs.com',
			'jp'      => 'whois.jprs.jp', // Japan.
			'ke'      => 'whois.kenic.or.ke', // Kenya.
			'kg'      => 'www.domain.kg', // Kyrgyzstan.
		// kh - Cambodia - no whois server assigned.
			'ki'      => 'whois.nic.ki', // Kiribati.
		// km - Comoros - no whois server assigned.
		// kn - Saint Kitts And Nevis - no whois server assigned.
		// kp - Korea, Democratic People's Republic Of - no whois server assigned.
			'kr'      => 'whois.kr', // Korea, Republic Of.
		// kw - Kuwait - no whois server assigned.
		// ky - Cayman Islands - no whois server assigned.
			'kz'      => 'whois.nic.kz', // Kazakhstan.
			'la'      => 'whois.nic.la', // Lao People's Democratic Republic.
		// lb - Lebanon - no whois server assigned.
		// lc - Saint Lucia - no whois server assigned.
			'li'      => 'whois.nic.li', // Liechtenstein.
		// lk - Sri Lanka - no whois server assigned.
			'lt'      => 'whois.domreg.lt', // Lithuania.
			'lu'      => 'whois.dns.lu', // Luxembourg.
			'lv'      => 'whois.nic.lv', // Latvia.
			'ly'      => 'whois.nic.ly', // Libya.
			'ma'      => 'whois.iam.net.ma', // Morocco.
		// mc - Monaco - no whois server assigned.
			'md'      => 'whois.nic.md', // Moldova.
			'me'      => 'whois.nic.me', // Montenegro.
			'mg'      => 'whois.nic.mg', // Madagascar.
		// mh - Marshall Islands - no whois server assigned.
			'mil'     => 'whois.nic.mil',
			// mk - Macedonia, The Former Yugoslav Republic Of - no whois server assigned.
			'ml'      => 'whois.dot.ml', // Mali.
		// mm - Myanmar - no whois server assigned.
			'mn'      => 'whois.nic.mn', // Mongolia.
			'mo'      => 'whois.monic.mo', // Macao.
			'mobi'    => 'whois.dotmobiregistry.net',
			'mp'      => 'whois.nic.mp', // Northern Mariana Islands.
		// mq - Martinique (France) - no whois server assigned.
		// mr - Mauritania - no whois server assigned.
			'ms'      => 'whois.nic.ms', // Montserrat.
		// mt - Malta - no whois server assigned.
			'mu'      => 'whois.nic.mu', // Mauritius.
			'museum'  => 'whois.museum',
			// mv - Maldives - no whois server assigned.
			// mw - Malawi - no whois server assigned.
			'mx'      => 'whois.mx', // Mexico.
			'my'      => 'whois.domainregistry.my', // Malaysia.
		// mz - Mozambique - no whois server assigned.
			'na'      => 'whois.na-nic.com.na', // Namibia.
			'name'    => 'whois.nic.name',
			'nc'      => 'whois.nc', // New Caledonia.
		// ne - Niger - no whois server assigned.
			'net'     => 'whois.verisign-grs.net',
			'nf'      => 'whois.nic.nf', // Norfolk Island.
			'ng'      => 'whois.nic.net.ng', // Nigeria.
		// ni - Nicaragua - no whois server assigned.
			'nl'      => 'whois.domain-registry.nl', // Netherlands.
			'no'      => 'whois.norid.no', // Norway.
		// np - Nepal - no whois server assigned.
		// nr - Nauru - no whois server assigned.
			'nu'      => 'whois.nic.nu', // Niue.
			'nz'      => 'whois.srs.net.nz', // New Zealand.
			'om'      => 'whois.registry.om', // Oman.
			'org'     => 'whois.pir.org',
			// pa - Panama - no whois server assigned.
			'pe'      => 'kero.yachay.pe', // Peru.
			'pf'      => 'whois.registry.pf', // French Polynesia.
		// pg - Papua New Guinea - no whois server assigned.
		// ph - Philippines - no whois server assigned.
		// pk - Pakistan - no whois server assigned.
			'pl'      => 'whois.dns.pl', // Poland.
			'pm'      => 'whois.nic.pm', // Saint Pierre and Miquelon (France).
		// pn - Pitcairn (New Zealand) - no whois server assigned.
			'post'    => 'whois.dotpostregistry.net',
			'pr'      => 'whois.nic.pr', // Puerto Rico.
			'pro'     => 'whois.dotproregistry.net',
			// ps - Palestine, State of - no whois server assigned.
			'pt'      => 'whois.dns.pt', // Portugal.
			'pw'      => 'whois.nic.pw', // Palau.
		// py - Paraguay - no whois server assigned.
			'qa'      => 'whois.registry.qa', // Qatar.
			're'      => 'whois.nic.re', // Reunion (France).
			'ro'      => 'whois.rotld.ro', // Romania.
			'rs'      => 'whois.rnids.rs', // Serbia.
			'ru'      => 'whois.tcinet.ru', // Russian Federation.
		// rw - Rwanda - no whois server assigned.
			'sa'      => 'whois.nic.net.sa', // Saudi Arabia.
			'sb'      => 'whois.nic.net.sb', // Solomon Islands.
			'sc'      => 'whois2.afilias-grs.net', // Seychelles.
		// sd - Sudan - no whois server assigned.
			'se'      => 'whois.iis.se', // Sweden.
			'sg'      => 'whois.sgnic.sg', // Singapore.
			'sh'      => 'whois.nic.sh', // Saint Helena.
			'si'      => 'whois.arnes.si', // Slovenia.
			'sk'      => 'whois.sk-nic.sk', // Slovakia.
		// sl - Sierra Leone - no whois server assigned.
			'sm'      => 'whois.nic.sm', // San Marino.
			'sn'      => 'whois.nic.sn', // Senegal.
			'so'      => 'whois.nic.so', // Somalia.
		// sr - Suriname - no whois server assigned.
			'st'      => 'whois.nic.st', // Sao Tome And Principe.
			'su'      => 'whois.tcinet.ru', // Russian Federation.
		// sv - El Salvador - no whois server assigned.
			'sx'      => 'whois.sx', // Sint Maarten (dutch Part).
			'sy'      => 'whois.tld.sy', // Syrian Arab Republic.
		// sz - Swaziland - no whois server assigned.
			'tc'      => 'whois.meridiantld.net', // Turks And Caicos Islands.
		// td - Chad - no whois server assigned.
			'tel'     => 'whois.nic.tel',
			'tf'      => 'whois.nic.tf', // French Southern Territories.
		// tg - Togo - no whois server assigned.
			'th'      => 'whois.thnic.co.th', // Thailand.
			'tj'      => 'whois.nic.tj', // Tajikistan.
			'tk'      => 'whois.dot.tk', // Tokelau.
			'tl'      => 'whois.nic.tl', // Timor-leste.
			'tm'      => 'whois.nic.tm', // Turkmenistan.
			'tn'      => 'whois.ati.tn', // Tunisia.
			'to'      => 'whois.tonic.to', // Tonga.
			'tp'      => 'whois.nic.tl', // Timor-leste.
			'tr'      => 'whois.nic.tr', // Turkey.
			'travel'  => 'whois.nic.travel',
			// tt - Trinidad And Tobago - no whois server assigned.
			'tv'      => 'tvwhois.verisign-grs.com', // Tuvalu.
			'tw'      => 'whois.twnic.net.tw', // Taiwan.
			'tz'      => 'whois.tznic.or.tz', // Tanzania, United Republic Of.
			'ua'      => 'whois.ua', // Ukraine.
			'ug'      => 'whois.co.ug', // Uganda.
		// "uk" => "whois.nic.uk", // United Kingdom.
			'uk'      => 'uk.whois-servers.net',
			'us'      => 'whois.nic.us', // United States.
			'uy'      => 'whois.nic.org.uy', // Uruguay.
			'uz'      => 'whois.cctld.uz', // Uzbekistan.
		// va - Holy See (vatican City State) - no whois server assigned.
			'vc'      => 'whois2.afilias-grs.net', // Saint Vincent And The Grenadines.
			've'      => 'whois.nic.ve', // Venezuela.
			'vg'      => 'whois.adamsnames.tc', // Virgin Islands, British.
		// vi - Virgin Islands, US - no whois server assigned.
		// vn - Viet Nam - no whois server assigned.
		// vu - Vanuatu - no whois server assigned.
			'wf'      => 'whois.nic.wf', // Wallis and Futuna.
			'ws'      => 'whois.website.ws', // Samoa.
			'xxx'     => 'whois.nic.xxx',
			// ye - Yemen - no whois server assigned.
			'yt'      => 'whois.nic.yt', // Mayotte.
			'yu'      => 'whois.ripe.net',

			'design'  => 'whois.nic.design', // Design
			'digital' => 'whois.donuts.co', // Digital
			'live'    => 'whois.donuts.co', // LIVE domains
		);

		return $whois_servers;
	}

	public static function lookup_domain( $domain, $id, $site_id, $site_url, $update ) {
		$whoisservers = self::whois_servers();
		$domain_parts = explode( '.', $domain );
		$tld          = strtolower( array_pop( $domain_parts ) );
		$whoisserver  = $whoisservers[ $tld ];

		if ( ! $whoisserver ) {
			return 'Error: No appropriate WHOIS server found for the domain!';
		}

		$result = self::whois_query( $whoisserver, $domain, $id, $site_id, $site_url, $update, $tld );

		if ( ! $result ) {
			return 'Error: No results retrieved from the WHOIS server for the child site!';
		}

		return $result;
	}

	public static function whois_query( $whoisserver, $domain, $id, $site_id, $site_url, $update, $tld ) {
		$port    = 43;
		$timeout = 60;
		$fp      = @fsockopen( $whoisserver, $port, $errno, $errstr, $timeout ) or die( 'Socket Error ' . $errno . ' - ' . $errstr );

		fputs( $fp, $domain . "\r\n" );

		$out = '';

		while ( ! feof( $fp ) ) {
			$out .= fgets( $fp );
		}

		$output = array();
		fclose( $fp );

		if ( 'uk' == $tld ) {
			$output = self::parse_values_uk( $out );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'it' == $tld ) {
			$output = self::parse_values_it( $out );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'br' == $tld ) {
			$values = self::parse_values_other( $out );
			$output = self::parse_values_br( $values );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'pt' == $tld ) {
			$values = self::parse_values_other( $out );
			$output = self::parse_values_pt( $values );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'be' == $tld ) {
			$values = self::parse_values_other( $out );
			$output = self::parse_values_be( $values, $out );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'de' == $tld ) {
			$values = self::parse_values_other( $out );
			$output = self::parse_values_de( $values );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'nl' == $tld ) {
			$values = self::parse_values_other( $out );
			$output = self::parse_values_nl( $values, $out );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'au' == $tld ) {
			$values = self::parse_values_other( $out );
			$output = self::parse_values_au( $values );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} elseif ( 'es' == $tld ) {
			// Conditions of use for the whois service via port 43 for .es domains
			// Access will only be enabled for  IP addresses  authorised  by Red.es.  A maximum of one  IP address per
			// user/organisation is permitted.
			$values = self::parse_values_other( $out );
			$output = self::parse_values_es( $values );
			self::save_values_formatted( $output, $id, $site_id, $site_url, $update );
		} else {
			$output = self::parse_values_other( $out );
			self::save_values( $output, $id, $site_id, $site_url, $update );
		}
		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_uk( $content ) {

		$fields_pattern = array(
			'domain_name'                   => 'Domain name:(.*?)Registrant:',
			'registry_domain_id'            => '',
			'registrar_whois_server'        => '',
			'registrar_url'                 => '',
			'updated_date'                  => 'Last updated:(.*?)Registration status:',
			'creation_date'                 => 'Registered on:(.*?)Last updated:',
			'expiry_date'                   => 'Expiry date:(.*?)Last updated:',
			'registrar'                     => 'Registrar:(.*?)Relevant dates:',
			'registrar_iana_id'             => '',
			'registrar_abuse_contact_email' => '',
			'registrar_abuse_contact_phone' => '',
			'domain_status_1'               => 'Registration status:(.*?)Name servers:',
			'domain_status_2'               => '',
			'domain_status_3'               => '',
			'domain_status_4'               => '',
			'domain_status_5'               => '',
			'domain_status_6'               => '',
			'name_server_1'                 => 'Name servers:(.*?)', // end: DNSSEC: or WHOIS lookup made at.
			'name_server_2'                 => '',
			'name_server_3'                 => '',
			'name_server_4'                 => '',
			'dnssec'                        => 'DNSSEC:(.*?)', // end: WHOIS lookup made at.
		);

		if ( false !== strpos( $content, 'DNSSEC' ) ) {
			$fields_pattern['name_server_1'] = $fields_pattern['name_server_1'] . 'DNSSEC:';
			$fields_pattern['dnssec']        = $fields_pattern['dnssec'] . 'WHOIS lookup made at';
		} else {
			$fields_pattern['name_server_1'] = $fields_pattern['name_server_1'] . 'WHOIS lookup made at';
			$fields_pattern['dnssec']        = ''; // not existed.
		}

		$output = array();
		foreach ( $fields_pattern as $field => $pattern ) {
			if ( '' === $pattern ) {
				$output[ $field ] = '';
			} else {
				$found = preg_match( '/' . $pattern . '/ims', $content, $matches );
				if ( $found ) {
					$value            = $matches[1];
					$value            = trim( $value );
					$output[ $field ] = $value;
				} else {
					$output[ $field ] = '';
				}
			}
		}

		if ( empty( $output['domain_name'] ) ) {
			$field   = 'domain_name';
			$pattern = 'Domain name:(.*?)Data validation:';
			$found   = preg_match( '/' . $pattern . '/ims', $content, $matches );
			if ( $found ) {
				$value            = $matches[1];
				$value            = trim( $value );
				$output[ $field ] = $value;
			} else {
				$output[ $field ] = '';
			}
		}
		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_it( $content ) {
		$fields_pattern = array(
			'domain_name'                   => 'Domain:(.*?)Status:',
			'registry_domain_id'            => '',
			'registrar_whois_server'        => '',
			'registrar_url'                 => '',
			'updated_date'                  => 'Last update:(.*?)Expire date:',
			'creation_date'                 => 'Created:(.*?)Last update:',
			'expiry_date'                   => 'Expire Date:(.*?)Registrant',
			'registrar'                     => 'Registrar(.*?)Name:',
			'registrar_iana_id'             => '',
			'registrar_abuse_contact_email' => '',
			'registrar_abuse_contact_phone' => '',
			'domain_status_1'               => 'Status:(.*?)Signed:',
			'domain_status_2'               => '',
			'domain_status_3'               => '',
			'domain_status_4'               => '',
			'domain_status_5'               => '',
			'domain_status_6'               => '',
			'name_server_1'                 => 'Nameservers(.*$)', // end: DNSSEC: or WHOIS lookup made at.
			'name_server_2'                 => '',
			'name_server_3'                 => '',
			'name_server_4'                 => '',
			'dnssec'                        => 'DNSSEC:(.*?)Nameservers', // end: WHOIS lookup made at.
		);

		$output = array();
		foreach ( $fields_pattern as $field => $pattern ) {
			if ( '' === $pattern ) {
				$output[ $field ] = '';
			} else {
				$found = preg_match( '/' . $pattern . '/ims', $content, $matches );
				if ( $found ) {
					$value = $matches[1];
					if ( strpos( $value, 'Organization:' ) !== false ) {
						$value = explode( ':', $value )[1]; }
					$value            = trim( $value );
					$output[ $field ] = $value;
				} else {
					$output[ $field ] = '';
				}
			}
		}
		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_es( $content ) {

		$fields_match = array(
			'domain_name'                   => 'Domain',
			'creation_date'                 => 'Creation Date',
			'expiry_date'                   => 'Expiration Date',
			'registrar'                     => 'Owner Name',
			'registrar_abuse_contact_email' => 'Admin Email',
			'domain_status_1'               => 'Domain Status 1',
			'name_server_1'                 => 'Name Server 1',
			'name_server_2'                 => 'Name Server 2',
			'name_server_3'                 => 'Name Server 3',
			'name_server_4'                 => 'Name Server 4',
		);

		$output = array();
		if ( is_array( $values ) ) {
			foreach ( $fields_match as $field => $match ) {
				if ( isset( $values[ $match ] ) ) {
					$output[ $field ] = $values[ $match ];
				} else {
					$output[ $field ] = '';
				}
			}
		}
		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_au( $values ) {

		$fields_match = array(
			'domain_name'                   => 'Domain Name',
			'registry_domain_id'            => 'Registry Domain ID',
			'registrar_whois_server'        => 'Registrar WHOIS Server',
			'registrar_url'                 => 'Registrar URL',
			'updated_date'                  => 'Last Modified',
			'registrar'                     => 'Registrar Name',
			'domain_status_1'               => 'Status',
			'name_server_1'                 => 'Name Server 1',
			'name_server_2'                 => 'Name Server 2',
			'dnssec'                        => 'DNSSEC',
			'registrar_abuse_contact_email' => 'Registrar Abuse Contact Email',
		);

		$output = array();
		if ( is_array( $values ) ) {
			foreach ( $fields_match as $field => $match ) {
				if ( empty( $match ) ) {
					$output[ $field ] = '';
					continue;
				}
				if ( isset( $values[ $match ] ) ) {
					$output[ $field ] = $values[ $match ];
				} else {
					$output[ $field ] = '';
				}
			}
		}
		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_nl( $values, $content ) {

		$fields_match = array(
			'domain_name'     => 'Domain name',
			'updated_date'    => 'Updated Date',
			'creation_date'   => 'Creation Date',
			'domain_status_1' => 'Status',
			'dnssec'          => 'DNSSEC',
			'registrar'       => '',
			'name_server_1'   => '',
		);

		$output = array();
		if ( is_array( $values ) ) {
			foreach ( $fields_match as $field => $match ) {
				if ( empty( $match ) ) {
					$output[ $field ] = '';
					continue;
				}
				if ( isset( $values[ $match ] ) ) {
					$output[ $field ] = $values[ $match ];
				} else {
					$output[ $field ] = '';
				}
			}
		}
		
		if ( empty( $output['registrar'] ) ) {
			$field   = 'registrar';
			$pattern = 'Registrar:(.*?)Abuse Contact:';
			$found   = preg_match( '/' . $pattern . '/ims', $content, $matches );
			if ( $found ) {
				$value            = $matches[1];
				$value            = trim( $value );
				$output[ $field ] = $value;
			} else {
				$output[ $field ] = '';
			}
		}

		if ( empty( $output['name_server_1'] ) ) {
			$field   = 'name_server_1';
			$pattern = 'Domain nameservers:(.*?)Record maintained by:';
			$found   = preg_match( '/' . $pattern . '/ims', $content, $matches );
			if ( $found ) {
				$value            = $matches[1];
				$value            = trim( $value );
				$output[ $field ] = $value;
			} else {
				$output[ $field ] = '';
			}
		}
		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_de( $values ) {

		$fields_match = array(
			'domain_name'     => 'Domain',
			'domain_status_1' => 'Status',
		);

		$output = array();
		if ( is_array( $values ) ) {
			foreach ( $fields_match as $field => $match ) {
				if ( isset( $values[ $match ] ) ) {
					$output[ $field ] = $values[ $match ];
				} else {
					$output[ $field ] = '';
				}
			}
		}

		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_be( $values, $content ) {

		$fields_match = array(
			'domain_name'                   => 'Domain',
			'creation_date'                 => 'Registered',
			'registrar'                     => 'Organisation',
			'registrar_abuse_contact_email' => 'Admin Email',
			'domain_status_1'               => 'Status',
			'name_server_1'                 => 'Nameservers',
		);

		$output = array();
		if ( is_array( $values ) ) {
			foreach ( $fields_match as $field => $match ) {
				if ( isset( $values[ $match ] ) ) {
					$output[ $field ] = $values[ $match ];
				} else {
					$output[ $field ] = '';
				}
			}
		}

		if ( empty( $output['name_server_1'] ) ) {
			$field   = 'name_server_1';
			$pattern = 'Nameservers:(.*?)Keys:';
			$found   = preg_match( '/' . $pattern . '/ims', $content, $matches );
			if ( $found ) {
				$value            = $matches[1];
				$value            = trim( $value );
				$output[ $field ] = $value;
			} else {
				$output[ $field ] = '';
			}
		}

		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_pt( $content ) {

		$fields_match = array(
			'domain_name'                   => 'Domain',
			'creation_date'                 => 'Creation Date',
			'expiry_date'                   => 'Expiration Date',
			'registrar'                     => 'Owner Name',
			'registrar_abuse_contact_email' => 'Admin Email',
			'domain_status_1'               => 'Domain Status 1',
			'name_server_1'                 => 'Name Server 1',
			'name_server_2'                 => 'Name Server 2',
			'name_server_3'                 => 'Name Server 3',
			'name_server_4'                 => 'Name Server 4',
		);

		$output = array();
		if ( is_array( $values ) ) {
			foreach ( $fields_match as $field => $match ) {
				if ( isset( $values[ $match ] ) ) {
					$output[ $field ] = $values[ $match ];
				} else {
					$output[ $field ] = '';
				}
			}
		}

		return $output;
	}


	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_br( $values ) {
		$fields_match = array(
			'domain_name'     => 'domain',
			'updated_date'    => 'changed',
			'creation_date'   => 'created',
			'domain_status_1' => 'status',
			'name_server_1'   => 'nserver',
		);

		$output = array();
		if ( is_array( $values ) ) {
			foreach ( $fields_match as $field => $match ) {
				if ( isset( $values[ $match ] ) ) {
					$output[ $field ] = $values[ $match ];
				} else {
					$output[ $field ] = '';
				}
			}
		}

		return $output;
	}

	/**
	 * Parse Values
	 *
	 * Parse the Domain Lookup report values.
	 *
	 * @param string $content Content.
	 */
	public static function parse_values_other( $content ) {
		$output = array();
		$i      = 1;
		$k      = 1;

		if ( ( false === strpos( strtolower( $content ), 'error' ) ) && ( false === strpos( strtolower( $content ), 'not allocated' ) ) ) {
			$rows = explode( "\n", $content );
			foreach ( $rows as $row ) {
				$row            = trim( $row );
				$colon_position = strpos( $row, ':' );
				if ( ( '' != $row ) && ( '#' != $row[0] ) && ( '%' != $row[0] ) && ( false !== $colon_position ) ) {
					if ( 'Domain Status' == substr( $row, 0, $colon_position ) ) {
						  $output[ substr( $row, 0, $colon_position ) . ' ' . $i ] = trim( substr( $row, $colon_position + 1 ) );
						$i++;
					} elseif ( 'Name Server' == substr( $row, 0, $colon_position ) ) {
						$output[ substr( $row, 0, $colon_position ) . ' ' . $k ] = trim( substr( $row, $colon_position + 1 ) );
						$k++;
					} else {
						$output[ substr( $row, 0, $colon_position ) ] = trim( substr( $row, $colon_position + 1 ) );
					}
				}
			}
		}
		return $output;
	}

	/**
	 * Save UK Values
	 *
	 * Saves theUK  Domain Lookup report values.
	 *
	 * @param array  $result     Domain lookup report.
	 * @param int    $id         Item ID.
	 * @param int    $site_id    Site ID.
	 * @param string $site_url   Site URL.
	 * @param bool   $update     Update or save new values.
	 */
	public static function save_values_formatted( $result, $id, $site_id, $site_url, $update ) {
		// compatible name.
		global $wpdb;
		$domain_data = array();

		$domain_data['URL']                           = $site_url;
		$domain_data['site_id']                       = $site_id;
		$domain_data['domain_name']                   = isset( $result['domain_name'] ) ? $result['domain_name'] : '';
		$domain_data['registry_domain_id']            = isset( $result['registry_domain_id'] ) ? $result['registry_domain_id'] : '';
		$domain_data['registrar_whois_server']        = isset( $result['registrar_whois_server'] ) ? $result['registrar_whois_server'] : '';
		$domain_data['registrar_url']                 = isset( $result['registrar_url'] ) ? $result['registrar_url'] : '';
		$domain_data['updated_date']                  = isset( $result['updated_date'] ) && ! empty( $result['updated_date'] ) ? strtotime( substr( $result['updated_date'], 0, 11 ) ) : '';
		$domain_data['creation_date']                 = isset( $result['creation_date'] ) && ! empty( $result['creation_date'] ) ? strtotime( substr( $result['creation_date'], 0, 11 ) ) : '';
		$domain_data['expiry_date']                   = isset( $result['expiry_date'] ) && ! empty( $result['expiry_date'] ) ? strtotime( substr( $result['expiry_date'], 0, 11 ) ) : '';
		$domain_data['registrar']                     = isset( $result['registrar'] ) ? $result['registrar'] : '';
		$domain_data['registrar_iana_id']             = isset( $result['registrar_iana_id'] ) ? $result['registrar_iana_id'] : '';
		$domain_data['registrar_abuse_contact_email'] = isset( $result['registrar_abuse_contact_email'] ) ? $result['registrar_abuse_contact_email'] : '';
		$domain_data['registrar_abuse_contact_phone'] = isset( $result['registrar_abuse_contact_phone'] ) ? $result['registrar_abuse_contact_phone'] : '';
		$domain_data['domain_status_1']               = isset( $result['domain_status_1'] ) ? strtok( $result['domain_status_1'], ' ' ) : '';
		$domain_data['domain_status_2']               = isset( $result['domain_status_2'] ) ? strtok( $result['domain_status_2'], ' ' ) : '';
		$domain_data['domain_status_3']               = isset( $result['domain_status_3'] ) ? strtok( $result['domain_status_3'], ' ' ) : '';
		$domain_data['domain_status_4']               = isset( $result['domain_status_4'] ) ? strtok( $result['domain_status_4'], ' ' ) : '';
		$domain_data['domain_status_5']               = isset( $result['domain_status_5'] ) ? strtok( $result['domain_status_5'], ' ' ) : '';
		$domain_data['domain_status_6']               = isset( $result['domain_status_6'] ) ? strtok( $result['domain_status_6'], ' ' ) : '';
		$domain_data['name_server_1']                 = isset( $result['name_server_1'] ) ? $result['name_server_1'] : '';
		$domain_data['name_server_2']                 = isset( $result['name_server_2'] ) ? $result['name_server_2'] : '';
		$domain_data['name_server_3']                 = isset( $result['name_server_3'] ) ? $result['name_server_3'] : '';
		$domain_data['name_server_4']                 = isset( $result['name_server_4'] ) ? $result['name_server_4'] : '';
		$domain_data['dnssec']                        = isset( $result['dnssec'] ) ? $result['dnssec'] : '';
		$domain_data['last_check']                    = current_time( 'timestamp' );

		if ( $id ) {
			$domain_data['id'] = $id;
		}

		MainWP_Domain_Monitor_DB::get_instance()->update_domain_monitor( $domain_data );
	}

	/**
	 * Save Values
	 *
	 * Saves the Domain Lookup report values.
	 *
	 * @param array  $result     Domain lookup report.
	 * @param int    $id         Item ID.
	 * @param int    $site_id    Site ID.
	 * @param string $site_url   Site URL.
	 * @param bool   $update     Update or save new values.
	 */
	public static function save_values( $result, $id, $site_id, $site_url, $update ) {
		// compatible name.
		global $wpdb;
		$domain_data = array();

		$domain_data['URL']                           = $site_url;
		$domain_data['site_id']                       = $site_id;
		$domain_data['domain_name']                   = isset( $result['Domain Name'] ) ? $result['Domain Name'] : '';
		$domain_data['registry_domain_id']            = isset( $result['Registry Domain ID'] ) ? $result['Registry Domain ID'] : '';
		$domain_data['registrar_whois_server']        = isset( $result['Registrar WHOIS Server'] ) ? $result['Registrar WHOIS Server'] : '';
		$domain_data['registrar_url']                 = isset( $result['Registrar URL'] ) ? $result['Registrar URL'] : '';
		$domain_data['updated_date']                  = isset( $result['Updated Date'] ) ? strtotime( substr( $result['Updated Date'], 0, 10 ) ) : '';
		$domain_data['creation_date']                 = isset( $result['Creation Date'] ) ? strtotime( substr( $result['Creation Date'], 0, 10 ) ) : '';
		$domain_data['expiry_date']                   = isset( $result['Registry Expiry Date'] ) ? strtotime( substr( $result['Registry Expiry Date'], 0, 10 ) ) : '';
		$domain_data['registrar']                     = isset( $result['Registrar'] ) ? $result['Registrar'] : '';
		$domain_data['registrar_iana_id']             = isset( $result['Registrar IANA ID'] ) ? $result['Registrar IANA ID'] : '';
		$domain_data['registrar_abuse_contact_email'] = isset( $result['Registrar Abuse Contact Email'] ) ? $result['Registrar Abuse Contact Email'] : '';
		$domain_data['registrar_abuse_contact_phone'] = isset( $result['Registrar Abuse Contact Phone'] ) ? $result['Registrar Abuse Contact Phone'] : '';
		$domain_data['domain_status_1']               = isset( $result['Domain Status 1'] ) ? strtok( $result['Domain Status 1'], ' ' ) : '';
		$domain_data['domain_status_2']               = isset( $result['Domain Status 2'] ) ? strtok( $result['Domain Status 2'], ' ' ) : '';
		$domain_data['domain_status_3']               = isset( $result['Domain Status 3'] ) ? strtok( $result['Domain Status 3'], ' ' ) : '';
		$domain_data['domain_status_4']               = isset( $result['Domain Status 4'] ) ? strtok( $result['Domain Status 4'], ' ' ) : '';
		$domain_data['domain_status_5']               = isset( $result['Domain Status 5'] ) ? strtok( $result['Domain Status 5'], ' ' ) : '';
		$domain_data['domain_status_6']               = isset( $result['Domain Status 6'] ) ? strtok( $result['Domain Status 6'], ' ' ) : '';
		$domain_data['name_server_1']                 = isset( $result['Name Server 1'] ) ? $result['Name Server 1'] : '';
		$domain_data['name_server_2']                 = isset( $result['Name Server 2'] ) ? $result['Name Server 2'] : '';
		$domain_data['name_server_3']                 = isset( $result['Name Server 3'] ) ? $result['Name Server 3'] : '';
		$domain_data['name_server_4']                 = isset( $result['Name Server 4'] ) ? $result['Name Server 4'] : '';
		$domain_data['dnssec']                        = isset( $result['DNSSEC'] ) ? $result['DNSSEC'] : '';
		$domain_data['last_check']                    = current_time( 'timestamp' );

		if ( $id ) {
			$domain_data['id'] = $id;
		}

		MainWP_Domain_Monitor_DB::get_instance()->update_domain_monitor( $domain_data );
	}

	/**
	 * Start Domain Monitor Cron
	 *
	 * Starts the Domain Monitor cron jobs.
	 */
	public function mainwp_domain_monitor_cron_start() {
		MainWP_Domain_Monitor_Utility::set_cron_working();
		$busy       = false;
		$mutex_lock = $this->get_lock();
		if ( ! $mutex_lock ) {
			$busy = true;
		} else {
			$websites = MainWP_Domain_Monitor_Admin::get_websites();
			$update   = false;
			$id       = '';
			foreach ( $websites as $website ) {
				$domain      = MainWP_Domain_Monitor_Utility::get_domain( MainWP_Domain_Monitor_Utility::get_nice_url( $website['url'] ) );
				$site_id     = $website['id'];
				$site_url    = $website['url'];
				$domain_site = MainWP_Domain_Monitor_DB::get_instance()->get_domain_monitor_by( 'site_id', $site_id );
				$id          = isset( $domain_site->id ) ? $domain_site->id : 0;
				if ( 0 < $id ) {
					$update = true;
				}
				self::lookup_domain( $domain, $id, $site_id, $site_url, $update );
			}
			$this->release_lock();
		}
		return $busy;
	}

	/**
	 * Cron Schedules
	 *
	 * Defines cron schedules.
	 *
	 * @param array $schedules Cron schedules.
	 *
	 * @return array $schedules Cron schedules.
	 */
	public function cron_schedules( $schedules ) {
		if ( ! isset( $schedules['mainwp_domain_monitor_start_interval'] ) ) {
			$schedules['mainwp_domain_monitor_start_interval'] = array(
				'interval' => MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'frequency' ),
				'display'  => __( 'MainWP Domain Monitor Continue Interval', 'mainwp-domain-monitor-extension' ),
			);
		}
		return $schedules;
	}

	public function get_lock() {
		global $wpdb;
		$lock = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, %d)', 'gpi_lock_' . MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'mutex_id' ), 0 ) );
		return $lock == 1;
	}

	public function release_lock() {
		global $wpdb;
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'gpi_lock_' . MainWP_Domain_Monitor_Utility::get_instance()->get_option( 'mutex_id' ) ) );
	}
}
