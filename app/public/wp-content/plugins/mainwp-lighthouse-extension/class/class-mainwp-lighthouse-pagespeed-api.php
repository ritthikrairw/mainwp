<?php
/**
 * =======================================
 * MainWP_Lighthouse_Pagespeed_API
 * =======================================
 *
 * @copyright Matt Keys <https://profiles.wordpress.org/mattkeys>
 */

namespace MainWP\Extensions\Lighthouse;

class MainWP_Lighthouse_Pagespeed_API {


	private $api_baseurl   = 'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed';
	private $developer_key = null;
	private $lab_data_indexes;
	private $audits_to_skip;

	/**
	 * Static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Lighthouse_Pagespeed_API
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Contructor
	 *
	 * Runs each time the class is called.
	 */
	public function __construct() {
		$this->developer_key    = MainWP_Lighthouse_Utility::get_instance()->get_option( 'google_developer_key' );
		$this->lab_data_indexes = array(
			// 'first-meaningful-paint',
			// 'first-cpu-idle',
			// 'estimated-input-latency',
			'first-contentful-paint',
			'speed-index',
			'largest-contentful-paint',
			'interactive',
			'total-blocking-time',
			'cumulative-layout-shift',
		);
		$this->audits_to_skip   = array(
			'final-screenshot',
			'metrics',
			'network-requests',
		);
	}

	/**
	 * Get Developer Key
	 *
	 * Gets the developer key.
	 *
	 * @return string Developer key.
	 */
	public function get_developer_key() {
		if ( null === $this->developer_key ) {
			$this->developer_key = MainWP_Lighthouse_Utility::get_instance()->get_option( 'google_developer_key' );
		}
		return $this->developer_key;
	}

	public function run_lighthouse( $object_url, $settings, $options = array(), $retrying = false ) {

		// Use max_execution_time set in settings.
		@set_time_limit( MainWP_Lighthouse_Utility::get_instance()->get_option( 'max_execution_time' ) );

		$strategy      = isset( $settings['strategy'] ) ? $settings['strategy'] : '';
		$developer_key = isset( $settings['google_developer_key'] ) ? $settings['google_developer_key'] : '';
		$locale        = $settings['response_language'];

		if ( ( 'mobile' !== $strategy && 'desktop' !== $strategy ) || empty( $developer_key ) ) {
			return false;
		}

		$def_args = array(
			'key'      => $developer_key,
			'strategy' => $strategy,
			'locale'   => $locale,
		);

		$category = '';

		if ( isset( $options['category'] ) && is_array( $options['category'] ) && ! empty( $options['category'] ) ) {
			$category = implode( '&category=', $options['category'] );
			$category = '&category=' . $category;
			unset( $options['category'] );
		}

		$query_args        = array_merge( $def_args, $options );
		$query_args['url'] = rawurlencode( $object_url );

		$api_url  = add_query_arg( $query_args, $this->api_baseurl );
		$api_url .= $category;

		$api_request = wp_remote_get(
			$api_url,
			array(
				'timeout' => apply_filters( 'mainwp_lighthouse_remote_get_timeout', 300 ),
			)
		);

		$api_response_code = wp_remote_retrieve_response_code( $api_request );
		$body              = wp_remote_retrieve_body( $api_request );
		$api_response_body = json_decode( $body );

		if ( is_object( $api_response_body ) && property_exists( $api_response_body, 'error' ) ) {
			if ( ! $retrying ) {
				MainWP_Lighthouse_Utility::log_debug( 'run lighthouse :: retry :: ' . $strategy . ' :: ' . $object_url );

				$this->run_lighthouse( $object_url, $settings, $options, true );
			} else {
				return array( 'error' => $api_response_body->error );
			}
		}

		return array(
			'responseCode' => $api_response_code,
			'data'         => $api_response_body,
		);
	}

	/**
	 * Get Lab Data
	 *
	 * Gets the lab data.
	 *
	 * @param array $result   API response result.
	 * @param array $lab_data Lab data.
	 *
	 * @return array Lab data.
	 */
	public function get_lab_data( $result, $lab_data = array() ) {

		foreach ( $this->lab_data_indexes as $index ) {
			if ( ! isset( $result->lighthouseResult->audits->{$index} ) ) {
				continue;
			}

			$data = array(
				'id'           => $result->lighthouseResult->audits->{$index}->id,
				'title'        => $result->lighthouseResult->audits->{$index}->title,
				'description'  => $this->parse_markdown_style_links( $result->lighthouseResult->audits->{$index}->description ),
				'score'        => $result->lighthouseResult->audits->{$index}->score,
				'displayValue' => $result->lighthouseResult->audits->{$index}->displayValue,
			);

			if ( property_exists( $result->lighthouseResult->audits->{$index}, 'details' ) ) {
				$data['details'] = $result->lighthouseResult->audits->{$index}->details;
			}
			$lab_data[] = $data;
		}
		return wp_json_encode( $lab_data );
	}

	/**
	 * Get Other Data
	 *
	 * Gets the other data.
	 *
	 * @param array $result API response result.
	 *
	 * @return array Other data.
	 */
	public function get_others_data( $result ) {
		$others = array(
			'audits_data'       => $result->lighthouseResult->audits,
			'categories'        => $result->lighthouseResult->categories,
			'requestedUrl'      => $result->lighthouseResult->requestedUrl,
			'finalUrl'          => $result->lighthouseResult->finalUrl,
			'lighthouseVersion' => $result->lighthouseResult->lighthouseVersion,
			'networkUserAgent'  => $result->lighthouseResult->environment->networkUserAgent,
			'hostUserAgent'     => $result->lighthouseResult->environment->hostUserAgent,
			'benchmarkIndex'    => $result->lighthouseResult->environment->benchmarkIndex,
		);
		return wp_json_encode( $others );
	}

	public function get_page_reports( $result, $page_id, $strategy, $options, $page_reports = array() ) {
		$rule_results = $result->lighthouseResult->audits;

		if ( ! empty( $rule_results ) ) {
			foreach ( $rule_results as $rulename => $results_obj ) {

				if ( in_array( $rulename, $this->lab_data_indexes ) ) {
					continue;
				}

				if ( in_array( $rulename, $this->audits_to_skip ) ) {
					continue;
				}

				if ( 'screenshot-thumbnails' == $rulename && ! $options['store_screenshots'] ) {
					continue;
				}

				$page_reports[] = array(
					'page_id'    => $page_id,
					'strategy'   => $strategy,
					'rule_key'   => $rulename,
					'rule_name'  => $results_obj->title,
					'rule_score' => $results_obj->score,
					'rule_type'  => isset( $results_obj->details->type ) ? $results_obj->details->type : 'n/a',
				);
			}
		}

		return $page_reports;
	}

	/**
	 * Parse Markdown Links
	 *
	 * Converts markdown links to HTML links.
	 *
	 * @param string @string String to parse.
	 *
	 * @return string Converted string.
	 */
	private function parse_markdown_style_links( $string ) {
		$replace = '<a href="${2}" target="_blank">${1}</a>';

		return preg_replace( '/\[(.*?)\]\((.*?)\)/', $replace, $string );
	}
}
