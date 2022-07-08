<?php
/**
 * MainWP Virusdie Extension
 *
 * @package MainWP/Extensions
 */

namespace MainWP\Extensions\Virusdie;

/**
 * Class MainWP_Virusdie_Reports_Data
 *
 * MainWP Virusdie Extension.
 */
class MainWP_Virusdie_Reports_Data {

	/**
	 * Public static variable to hold the single instance of MainWP_Virusdie.
	 *
	 * @var mixed Default null
	 */
	public static $instance = null;

	/**
	 * Creates a public static instance of MainWP_Virusdie.
	 *
	 * @return MainWP_Virusdie|mixed|null
	 */
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Virusdie_Reports_Data();
		}
		return self::$instance;
	}

	/**
	 * MainWP_Virusdie constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get Virusdie scan data.
	 *
	 * @param int    $website_id   Child Site ID.
	 * @param string $date_from    Start date.
	 * @param string $date_to      End date.
	 * @param array  $sections     Sections.
	 * @param array  $other_tokens Other tokens.
	 *
	 * @return array Report data.
	 */
	public function virusdie_get_data( $website_id, $date_from, $date_to, $sections, $other_tokens ) {
		if ( empty( $website_id ) ) {
			return array();
		}
		$virusdie = MainWP_Virusdie_DB::get_instance()->get_virusdie_by( 'site_id', $website_id );
		if ( empty( $virusdie ) ) {
			return array();
		}
		return $this->get_reports_data( $virusdie->virusdie_item_id, $date_from, $date_to, $sections, $other_tokens );
	}


	/**
	 * Get stream.
	 *
	 * @param int    $virusdie_item_id   Virdusdie item ID.
	 * @param string $date_from          Start date.
	 * @param string $date_to            End date.
	 * @param array  $sections           Sections.
	 * @param array  $other_tokens       Other tokens.
	 *
	 * @return array Report data.
	 */
	public function get_reports_data( $virusdie_item_id, $date_from, $date_to, $sections, $other_tokens ) {

		$params  = array(
			'by_value'   => $virusdie_item_id,
			'start_date' => $date_from,
			'end_date'   => $date_to,
		);
		$records = MainWP_Virusdie_DB::get_instance()->get_report_by( 'virusdie_item_id', $params );

		if ( ! is_array( $records ) ) {
			$records = array();
		}

		$other_tokens_data = $this->get_other_tokens_data( $records, $other_tokens );
		$sections_data     = $this->get_stream_sections_data( $records, $sections );

		$information = array(
			'other_tokens_data' => $other_tokens_data,
			'sections_data'     => $sections_data,
		);
		return $information;
	}

	/**
	 * Get the other tokens data.
	 *
	 * @param array $records An array containg actions records.
	 * @param array $tokens  An array containg the tokens list.
	 *
	 * @return array An array containg the tokens values.
	 */
	public function get_other_tokens_data( $records, $tokens ) {

		$token_values = array();

		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}

		foreach ( $tokens as $token ) {

			if ( isset( $token_values[ $token ] ) ) {
				continue;
			}

			$str_tmp   = str_replace( array( '[', ']' ), '', $token );
			$array_tmp = explode( '.', $str_tmp );
			if ( is_array( $array_tmp ) ) {
				$context = '';
				$action  = '';
				$data    = '';
				if ( 2 === count( $array_tmp ) ) {
					list( $context, $data ) = $array_tmp;
				} elseif ( 3 === count( $array_tmp ) ) {
					list( $context, $action, $data ) = $array_tmp;
				}

				switch ( $data ) {
					case 'count':
						$token_values[ $token ] = count( $records );
						break;
				}
			}
		}

		return $token_values;
	}

	/**
	 * Get the Stream sections data.
	 *
	 * @param array $records  An array containg actions records.
	 * @param array $sections An array containing sections.
	 *
	 * @return array Sections data.
	 */
	public function get_stream_sections_data( $records, $sections ) {
		$sections_data = array();
		if ( isset( $sections['section_token'] ) && is_array( $sections['section_token'] ) && ! empty( $sections['section_token'] ) ) {
			foreach ( $sections['section_token'] as $index => $sec ) {
				$tokens                  = $sections['section_content_tokens'][ $index ];
				$sections_data[ $index ] = $this->get_section_loop_data( $records, $tokens, $sec );
			}
		}
		return $sections_data;
	}


	/**
	 * Get the section loop data.
	 *
	 * @param object $records Object containng reports records.
	 * @param array  $tokens  An array containing report tokens.
	 * @param string $section Section name.
	 *
	 * @return array Section loop records.
	 */
	public function get_section_loop_data( $records, $tokens, $section ) {

		$context = '';
		$action  = '';

		$str_tmp   = str_replace( array( '[', ']' ), '', $section );
		$array_tmp = explode( '.', $str_tmp );
		if ( is_array( $array_tmp ) ) {
			if ( 3 === count( $array_tmp ) ) {
				list( $str1, $context, $action ) = $array_tmp;
			}
		}

		return $this->get_section_loop_records( $records, $tokens );
	}


	/**
	 * Get the section loop records.
	 *
	 * @param object $records Object containng reports records.
	 * @param array  $tokens  An array containing report tokens.
	 *
	 * @return array Loops.
	 */
	public function get_section_loop_records( $records, $tokens ) {  // phpcs:ignore -- Current complexity is the only way to achieve desired results, pull request solutions appreciated.
		$loops      = array();
		$loop_count = 0;
		foreach ( $records as $record ) {
			$token_values = $this->get_section_loop_token_values( $record, $tokens );
			if ( ! empty( $token_values ) ) {
				$loops[ $loop_count ] = $token_values;
				$loop_count ++;
			}
		}
		return $loops;
	}



	/**
	 * Get the section loop token values.
	 *
	 * @param object $record Object containing the record data.
	 * @param array  $tokens An array containg the report tokens.
	 *
	 * @return array Token values.
	 *
	 * @uses \MainWP\Child\MainWP_Helper::log_debug()
	 */
	private function get_section_loop_token_values( $record, $tokens ) {

		$token_values = array();
		foreach ( $tokens as $token ) {
			$data       = '';
			$token_name = str_replace( array( '[', ']' ), '', $token );
			$array_tmp  = explode( '.', $token_name );

			if ( 1 === count( $array_tmp ) ) {
				list( $data ) = $array_tmp;
			} elseif ( 2 === count( $array_tmp ) ) {
				list( $str1, $data ) = $array_tmp;
			} elseif ( 3 === count( $array_tmp ) ) {
				list( $str1, $str2, $data ) = $array_tmp;
			}

			$tok_value = $this->get_section_token_value( $record, $data, $token );

			$token_values[ $token ] = $tok_value;
		}
		return $token_values;
	}

	/**
	 * Get the section token value.
	 *
	 * @param object $record  Object containing the record data.
	 * @param string $data    Data to process.
	 * @param string $token   Requested token.
	 *
	 * @return array Token value.
	 *
	 * @uses \MainWP\Child\MainWP_Helper::format_date()
	 * @uses \MainWP\Child\MainWP_Helper::format_time()
	 */
	public function get_section_token_value( $record, $data, $token ) {  // phpcs:ignore -- Current complexity is the only way to achieve desired results, pull request solutions appreciated.

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		
		$tok_value = '';
		switch ( $data ) {
			case 'date':
				$tok_value = gmdate( $date_format, MainWP_Virusdie::get_timestamp( $record->scandate ) );
				break;
			case 'time':
				$tok_value = gmdate( $time_format, MainWP_Virusdie::get_timestamp( $record->scandate ) );
				break;
			case 'status':
			case 'details':
				$tok_value = $this->get_result_data_token_value( $record, $data );
				break;
			default:
				$tok_value = 'N/A';
				break;
		}
		return $tok_value;
	}

		/**
		 * Get the result data token value.
		 *
		 * @param object $record Object containing the record data.
		 * @param string $data   Data to process.
		 *
		 * @return string Result data token value.
		 */
	private function get_result_data_token_value( $record, $data ) {
		$tok_value = '';
		if ( 'details' == $data ) {
			$tok_value .= esc_html__( 'Checked bytes', 'mainwp-virusdie-extension' ) . ': ' . ( $record->checkedbytes > 1024 * 1024 * 1024 ) ? ( ( round( $record->checkedbytes / ( 1024 * 1024 * 1024 ), 2 ) ) . ' GB' ) : ( ( round( $record->checkedbytes / ( 1024 * 1024 ), 2 ) ) . ' MB' );
			$tok_value .= ', ' . esc_html__( 'Detected files', 'mainwp-virusdie-extension' ) . ': ' . intval( $record->detectedfiles );
			$tok_value .= ', ' . esc_html__( 'Incurable files', 'mainwp-virusdie-extension' ) . ': ' . intval( $record->incurablefiles );
			$tok_value .= ', ' . esc_html__( 'Vulnerabilities', 'mainwp-virusdie-extension' ) . ': ' . intval( $record->malicious );
			$tok_value .= ', ' . esc_html__( 'Checked dirs', 'mainwp-virusdie-extension' ) . ': ' . intval( $record->checkeddirs );
			$tok_value .= ', ' . esc_html__( 'Checked files', 'mainwp-virusdie-extension' ) . ': ' . intval( $record->checkedfiles );
			$tok_value .= ', ' . esc_html__( 'Treated files', 'mainwp-virusdie-extension' ) . ': ' . intval( $record->treatedfiles );
			$tok_value .= ', ' . esc_html__( 'Deleted files', 'mainwp-virusdie-extension' ) . ': ' . intval( $record->deletedfiles );
		} elseif ( 'status' == $data ) {
			$tok_value = MainWP_Virusdie::get_instance()->get_status_message( $record->status );
		}
		return $tok_value;
	}

}
