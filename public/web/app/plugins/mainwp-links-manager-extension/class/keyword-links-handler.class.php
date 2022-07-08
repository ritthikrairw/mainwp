<?php
class Keyword_Links_Handler
{
	public static function add_link_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['existed_keywords'] ) ) {
				$output->existed_keywords[ $website->id ] = $information['existed_keywords'];
			}
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->infor[ $website->id ] = __( 'Link saved successfully.', 'mainwp' );
			} else if ( isset( $information['error'] ) && ! empty( $information['error'] ) ) {
				$output->infor[ $website->id ] = $information['error'];
			} else {
				$output->infor[ $website->id ] = __( 'Not change.','mainwp' );
			}
		} else {
			$output->infor[ $website->id ] = __( 'An error occured while add link.', 'mainwp' );
		}
	}

	public static function update_config_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->infor[ $website->id ] = __( 'Save configuration successfully.', 'mainwp' );
			} else if ( isset( $information['error'] ) && ! empty( $information['error'] ) ) {
				$output->infor[ $website->id ] = $information['error'];
			} else {
				$output->infor[ $website->id ] = __( 'Configuration does not change.','mainwp' );
			}
			if ( isset( $information['message'] ) ) {
				$output->message[ $website->id ] = $information['message'];
			}
		} else {
			$output->infor[ $website->id ] = __( 'An error occured while update configuration.', 'mainwp' );
		}
	}


	public static function delete_link_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->infor[ $website->id ] = __( 'Delete link successfully.', 'mainwp' );
				$output->status[ $website->id ] = 'success';
			} else if ( isset( $information['error'] ) && ! empty( $information['error'] ) ) {
				$output->infor[ $website->id ] = $information['error'];
			} else {
				$output->infor[ $website->id ] = __( 'Delete link failed.','mainwp' );
			}
		} else {
			$output->infor[ $website->id ] = __( 'An error occured while delete link.', 'mainwp' );
		}
	}

	public static function clear_link_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->infor[ $website->id ] = __( 'Clear link successfully.', 'mainwp' );
			} else if ( isset( $information['error'] ) && ! empty( $information['error'] ) ) {
				$output->infor[ $website->id ] = $information['error'];
			} else {
				$output->infor[ $website->id ] = __( 'Clear link failed.','mainwp' );
			}
		} else {
			$output->infor[ $website->id ] = __( 'An error occured while clear link.', 'mainwp' );
		}
	}
	public static function refresh_data_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->infor[ $website->id ] = __( 'Clear data successfully.', 'mainwp' );
			} else if ( isset( $information['error'] ) && ! empty( $information['error'] ) ) {
				$output->infor[ $website->id ] = $information['error'];
			} else {
				$output->infor[ $website->id ] = __( 'Clear data failed.','mainwp' );
			}
		} else {
			$output->infor[ $website->id ] = __( 'An error occured while clear data.', 'mainwp' );
		}
	}

	public static function import_config_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->ok[ $website->id ] = 1;
			} else {
				$output->status[ $website->id ] = __( 'Configuration does not change.','mainwp' );
			}
		} else {
			$output->status[ $website->id ] = __( 'Error import configuration.' );
		}
	}

	public static function import_link_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->ok[ $website->id ] = 1;
			} else {
				$output->status[ $website->id ] = __( 'Link data does not change.','mainwp' );
			}
		} else {
			$output->status[ $website->id ] = __( 'Error import link.' );
		}
	}

	public static function do_not_link_site_block_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->ok[ $website->id ] = 1;
			} else {
				$output->status[ $website->id ] = __( 'No change.','mainwp' );
			}
		} else {
			$output->status[ $website->id ] = __( 'Error while apply.' );
		}
	}

	public static function do_not_link_clear_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];
			$information = unserialize( base64_decode( $result ) );
			if ( isset( $information['status'] ) && 'SUCCESS' == $information['status'] ) {
				$output->status[ $website->id ] = __( 'Cleared successfully.' );
			} else {
				$output->status[ $website->id ] = __( 'Not change.' );
			}
		} else {
			$output->status[ $website->id ] = __( 'Error occurs.' );
		}
	}
}
