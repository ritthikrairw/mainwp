<?php

class MainWP_Article_Uploader {
	public static $instance = null;
	private $allowedExtensions = array( 'csv', 'txt' );
	protected $option_handle = 'mainwp_article_uploader_options';
	protected $option = null;

	static function get_instance() {
		if ( null === MainWP_Article_Uploader::$instance ) {
			MainWP_Article_Uploader::$instance = new MainWP_Article_Uploader();
		}
		return MainWP_Article_Uploader::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
		if ( isset( $_GET['page'] ) && 'Extensions-Mainwp-Article-Uploader-Extension' == $_GET['page'] && isset( $_GET['action'] ) && 'download-sample' == $_GET['action'] ) {
			$this->download_sample();
		}
		add_action( 'init', array( &$this, 'init' ) );
	}

	public static function parse_query( $var ) {
		$var  = parse_url( $var, PHP_URL_QUERY );
		$var  = html_entity_decode( $var );
		$var  = explode( '&', $var );
		$arr  = array();

		foreach ( $var as $val ) {
			$x = explode( '=', $val );
			$arr[ $x[0] ] = $x[1];
		}
		unset( $val, $x, $var );
		return $arr;
	}

	function download_sample() {
		$path = MAINWP_ARTICLE_UPLOADER_EXTENSION_DIR.'download/sample_articles.csv';
		if ( file_exists( $path ) ) {
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename=sample_articles.csv' );
			header( 'Pragma: no-cache' );
			header( 'Content-Length: '.filesize( $path ) );
			if ( ob_get_level() ) {
				ob_end_clean();
			}
			readfile( $path );
			exit();
		}
	}
	public function init() {

	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_article_uploader_import_articles', array( $this, 'ajax_import_upload_articles' ) );
		add_action( 'wp_ajax_mainwp_article_uploader_publish_post', array( $this, 'ajax_posting' ) );
		//add_filter( 'mainwp_bulkpost_saved_categories', array( &$this, 'saved_categories' ), 10, 2 );
		add_action( 'wp_ajax_mainwp_article_uploader_delete_post', array( &$this, 'ajax_delete_post' ) );
		add_action( 'wp_ajax_mainwp_article_uploader_publish_loading', array( &$this, 'ajax_publish_loading' ) );
		add_action( 'wp_ajax_mainwp_article_uploader_perform_publish_articles', array( &$this, 'ajax_perform_publish_articles' ) );
		add_action( 'wp_ajax_mainwp_article_uploader_drip_post', array( &$this, 'ajax_article_drip_post' ) );

		if ( isset( $_REQUEST['uploader_do'] ) ) {
			// max file size in bytes
			$sizeLimit = 20 * 1024 * 1024; //20MB = max allowed
			if ( 'ArticleUploader-uploadfile' == $_REQUEST['uploader_do'] ) {
				$uploader = apply_filters( 'mainwp_qq2fileuploader', $this->allowedExtensions, $sizeLimit );
				$path = apply_filters( 'mainwp_getspecificdir', 'article_uploader/upload/' );
				$result = $uploader->handleUpload( $path, true );
				// to pass data through iframe you will need to encode all html tags
				die( htmlspecialchars( json_encode( $result ), ENT_NOQUOTES ) );
			} else if ( 'ArticleImport-uploadfile' == $_REQUEST['uploader_do'] ) {
				$uploader = apply_filters( 'mainwp_qq2fileuploader', $this->allowedExtensions, $sizeLimit );
				$path = apply_filters( 'mainwp_getspecificdir', 'article_uploader/import/' );
				$result = $uploader->handleUpload( $path, true );
				// to pass data through iframe you will need to encode all html tags
				die( htmlspecialchars( json_encode( $result ), ENT_NOQUOTES ) );
			}
		}

		if ( isset( $_POST['aup_upload_cancel_all'] ) && ! empty( $_POST['aup_upload_cancel_all'] ) ) {
			$args = array(
				'post_type' => array( 'bulkpost' ),
					'posts_per_page'  => -1,
					'post_status' 		=> 'any',
					'post_parent' 		=> null,
					'meta_key'   			=> '_mainwp_is_aup_upload',
					'meta_value' 			=> 'yes',
					'orderby' 				=> 'post_modified',
					'order' 					=> 'desc',
			);

			$articles = get_posts( $args );

			if ( is_array( $articles ) ) {
				foreach ( $articles as $post ) {
					wp_delete_post( $post->ID, true );
				}
			}

			wp_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Article-Uploader-Extension&message=1' ) );
			exit();
		}
	}

	function get_sites_dripper( $post, $all_sites, $countdown ) {
		if ( empty( $post ) ) {
			return;
		}

		$last_posted_sites = get_post_meta( $post->ID, '_mainwp_aup_upload_last_posted_sites', true );
		$last_posted_sites = unserialize( base64_decode( $last_posted_sites ) );

		if ( ! is_array( $last_posted_sites ) ) {
			$last_posted_sites = array();
		}

		$diff_sites = array_diff( $all_sites, $last_posted_sites );

		if ( ! is_array( $diff_sites ) || count( $diff_sites ) == 0 ) {
			return array();
		}

		if ( count( $diff_sites ) <= $countdown ) {
			return $diff_sites;
		}

		$return = array();

		for ( $i = 0; $i < $countdown; $i++ ) {
			$nb_rand = rand( 0, count( $diff_sites ) - 1 );
			$return[]  = $diff_sites[ $nb_rand ];
		}

		return $return;
	}

//	public function saved_categories( $post, $categories = array() ) {
//		if ( ! empty( $post ) ) {
//			$cats = base64_decode( get_post_meta( $post->ID, '_categories', true ) );
//			$cats = explode( ',', $cats );
//			$categories = array_merge( $categories, $cats );
//		}
//		return $categories;
//	}

	public function ajax_import_upload_articles() {
		if ( 'import' == $_POST['type'] ) {
			$this->ajax_import_articles();
		} else if ( 'upload' == $_POST['type'] ) {
			$this->ajax_upload_articles();
		}
		die();
	}

	function ajax_upload_articles() {
		$filename = $_POST['filename'];
		$path = apply_filters( 'mainwp_getspecificdir', 'article_uploader/upload/' );
		$file_path = $path . $filename;
		$count = 0;
		if ( ! empty( $filename ) && file_exists( $file_path ) ) {
			if ( ($handle = fopen( $file_path, 'r' ) ) !== false ) {
				$title = '';
				$content = file_get_contents( $file_path );
				if ( ! empty( $content ) ) {
          $lines = mb_split( "\n" , $content, 2 );
          if ( is_array( $lines ) && count( $lines ) > 0 ) {
	          $title = $lines[0];
	          $title =  $this->trim_multibyte( $title );
	          $content = $lines[1];
          }
				}
				$new_post = array(
					'post_title' =>  $title ,
					'post_content' => $content,
					'post_type' => 'bulkpost',
				);
				$new_meta = array( '_mainwp_is_aup_upload' => 'yes' );
				if ( false !== $this->insert_post( $new_post, $new_meta ) ) {
					$count++;
				}
			}
			fclose( $handle );
			@unlink( $file_path );
		} else {
			die( json_encode( array( 'error' => __( 'File could not be opened.', 'mainwp-article-uploader-extension' ) ) ) );
		}

		die( json_encode( array( 'success' => true, 'count' => $count ) ) );
	}

  function trim_multibyte( $str ) {
    return preg_replace( '/^[\pZ\pC]+([\PZ\PC]*)[\pZ\pC]+$/u', '$1', $str );
  }

	function ajax_import_articles() {
		$filename = $_POST['filename'];
		$path = apply_filters( 'mainwp_getspecificdir', 'article_uploader/import/' );
		$file_path = $path . $filename;
		$default_header = array(
			'TITLE',
			'AUTHOR',
			'CATEGORIES',
			'TAGS',
			'SLUG',
			'EXCERPT',
			'BODY',
			'ALLOW COMMENTS',
			'ALLOW PINGBACKS',
			'SITES',
			'POST TYPE',
			'STATUS',
			'[NEW]',
		);

		$header_index = array(
			'TITLE' => 'post_title',
			'AUTHOR' => '_author',
			'CATEGORIES' => '_categories',
			'TAGS' => '_tags',
			'SLUG' => '_slug',
			'EXCERPT' => 'post_excerpt',
			'BODY' => 'post_content',
			'ALLOW COMMENTS' => 'comment_status',
			'ALLOW PINGBACKS' => 'ping_status',
			'SITES' => '_selected_sites',
			'POST TYPE' => 'post_type',
			'STATUS' => 'post_status',
		);

		$count = 0;
		if ( ! empty( $filename ) && file_exists( $file_path ) ) {
			if ( ($handle = fopen( $file_path, 'r' )) !== false ) {
				$new_post = array();
				$new_meta = array();
				$multi_line_content = '';
				$is_body = $is_sites = $is_excerpt = false;
				while ( ($line = fgets( $handle )) !== false ) {

					$start_line = mb_substr( $line, 0 , 50 ); // get 50 of first characters
					$start_line = $this->trim_multibyte( $start_line );
          $start_line = ltrim($start_line, '"');

					$is_next_header = false;
					foreach ( $default_header as $header ) {
						if ( mb_strpos( $start_line, $header ) === 0 ) {
							$is_next_header = true;
                                error_log('is_next_header');

							if ( $is_body ) {
                                $multi_line_content = rtrim($multi_line_content);
                                $multi_line_content = rtrim($multi_line_content, ',');
                                $multi_line_content = rtrim($multi_line_content, '"');
								$new_post['post_content'] = $multi_line_content;
								$multi_line_content = '';
							} else if ( $is_excerpt ) {
                                $multi_line_content = rtrim($multi_line_content);
                                $multi_line_content = rtrim($multi_line_content, ',');
                                $multi_line_content = rtrim($multi_line_content, '"');
								$new_post['post_excerpt'] = $multi_line_content;
								$multi_line_content = '';
							} else if ( $is_sites ) {
								if ( strtolower( trim( $multi_line_content ) ) == 'all' ) {
									$_sites_ids = 'all';
								} else {
									$_sites_ids = $this->get_article_sites( $multi_line_content );
								}
								$new_meta['_selected_by'] = 'site';
								$new_meta['_selected_sites'] = base64_encode( serialize( $_sites_ids ) );
								$multi_line_content = '';
							}

							$is_body = $is_sites = $is_excerpt = false;

              if ( seems_utf8( $line ) ) {
                $new_content = mb_substr( $line, mb_strlen( $header ) + 2 );
              } else {
                $new_content = substr( $line, strlen( $header ) + 1 );
              }

							switch ( $header ) {
								case 'BODY':
									$is_body = true;
									$multi_line_content = rtrim( $new_content );
									break;
								case 'EXCERPT':
									$is_excerpt = true;
									$multi_line_content = rtrim( $new_content );
									break;
								case 'TITLE':
								case 'STATUS':
									$_index = $header_index[ $header ];
									$new_post[ $_index ] = trim( $new_content );
									break;
								case 'POST TYPE':
									$_index = $header_index[ $header ];
									$_post_type = trim( $new_content );
									$_post_type =  rtrim( $_post_type, ',' );
									if ( 'post' == $_post_type || 'page' == $_post_type ) {
										$new_post['post_type'] = ('post' == $_post_type) ? 'bulkpost' : 'bulkpage';
									}
									break;
								case 'CATEGORIES':
									$_cats = explode( ',', $new_content );
									$_str_cats = '';
									if ( is_array( $_cats ) ) {
										for ( $i = 0; $i < count( $_cats ); $i++ ) {
											$_cats[ $i ] = trim( $_cats[ $i ] );
										}
									} else {
										$_cats = array(); }
									$_str_cats = implode( ',', $_cats );
									$_index = $header_index[ $header ];
									$new_meta[ $_index ] = base64_encode( $_str_cats );
									break;
								case 'TAGS':
								case 'SLUG':
								case 'AUTHOR':
									$_index = $header_index[ $header ];
									$new_content = trim( $new_content );
									$new_meta[ $_index ] = base64_encode( $new_content );
									break;
								case 'SITES':
									$is_sites = true;
									$multi_line_content = $new_content;
									break;
								case 'ALLOW COMMENTS':
								case 'ALLOW PINGBACKS':
									$new_content = trim( $new_content );
									if ( 1 == $new_content ) {
										$new_content = 'open';
                  } else {
										$new_content = 'closed';
                  }
                  $_index = $header_index[ $header ];
                  $new_post[ $_index ] = $new_content;
									break;
								case '[NEW]':
									//print_r($new_post);
									if ( ! empty( $new_post ) ) {
										if ( isset( $new_post['post_type'] ) && isset( $new_post['post_title'] ) ) {
											if ( ! isset( $new_post['post_status'] ) ) {
												$new_post['post_status'] = 'publish';
                      }
											if ( $is_sites ) {
												if ( strtolower( trim( $multi_line_content ) ) == 'all' ) {
													$_sites_ids = 'all';
												} else {
													$_sites_ids = $this->get_article_sites( $multi_line_content ); }
												$new_meta['_selected_by'] = 'site';
												$new_meta['_selected_sites'] = base64_encode( serialize( $_sites_ids ) );
												$is_sites = false;
												$multi_line_content = '';
											}
											$new_meta['_mainwp_is_aup_import'] = 'yes';
											if ( false !== $this->insert_post( $new_post, $new_meta ) ) {
												$count++;
											}
										}
										$new_post = $new_meta = array();
									}
									break;
							}
							break;
						}
					}

					if ( ! $is_next_header ) {
						$multi_line_content .= $line;
					}
				}

				if ( ! empty( $new_post ) ) {
					if ( isset( $new_post['post_type'] ) && isset( $new_post['post_title'] ) ) {
						if ( ! isset( $new_post['post_status'] ) ) {
							$new_post['post_status'] = 'publish'; }
						if ( $is_sites ) {
							if ( strtolower( trim( $multi_line_content ) ) == 'all' ) {
								$_sites_ids = 'all';
							} else {
								$_sites_ids = $this->get_article_sites( $multi_line_content ); }
							$new_meta['_selected_by'] = 'site';
							$new_meta['_selected_sites'] = base64_encode( serialize( $_sites_ids ) );
						}
						$new_meta['_mainwp_is_aup_import'] = 'yes';
						if ( false !== $this->insert_post( $new_post, $new_meta ) ) {
							$count++;
						}
					}
					$new_post = $new_meta = array();
				}
			}
			fclose( $handle );
			@unlink( $file_path );
		} else {
			die( json_encode( array( 'error' => __( 'File could not be opened.', 'mainwp-article-uploader-extension' ) ) ) );
		}
		die( json_encode( array( 'success' => true, 'count' => $count ) ) );
	}

	function insert_post( $post, $post_meta ) {
		if ( isset( $post['post_title'] ) )
      $post['post_title'] = strip_tags( $post['post_title'] );
		if ( isset( $post['post_content'] ) )
      $post['post_content'] = $post['post_content'];

		$id = wp_insert_post( $post );
		if ( $id ) {
			if ( is_array( $post_meta ) ) {
				foreach ( $post_meta as $key => $value ) {
					update_post_meta( $id, $key, $value );
				}
			}
			return $id;
		}
		return false;
	}

	function get_article_sites( $content ) {
		$_sites = str_replace( ';', '', $content );
		$_sites = explode( ',', $_sites );
		$_sites_ids = array();
		foreach ( $_sites as $_url ) {
			$_url = trim( $_url );
			if ( ! preg_match( '/^https?:\/\/.*/is', $_url ) ) {
				$_url = 'http://' . $_url;
			}
			$website = apply_filters( 'mainwp_getwebsitesbyurl', $_url );
			if ( ! empty( $website ) ) {
				$_sites_ids[] = $website[0]->id;
			}
		}
		return $_sites_ids;
	}

	public function ajax_delete_post() {
		global $wpdb;
		$post_id = intval( $_REQUEST['postId'] );
		$ret = array( 'success' => false );
		if ( $post_id && wp_delete_post( $post_id ) ) {
			$ret['success'] = true;
		}
		echo json_encode( $ret );
		exit;
	}


	function ajax_publish_loading() {
		$args = array(
      'post_type' => array( 'bulkpost' ),
      'posts_per_page' => -1,
      'post_status' => 'any',
      'post_parent' => null,
      'meta_key'   => '_mainwp_is_aup_upload',
      'meta_value' => 'yes',
      'orderby' => 'post_modified',
      'order' => 'desc',
    );
		$articles = get_posts( $args );

		if ( ! is_array( $articles ) || count( $articles ) == 0 ) {
			die( json_encode( array( 'error' => __( 'Articles not found.', 'mainwp-article-uploader-extension' ) ) ) );
		}

		$html = '';

		$html .= '<div class="ui relaxed divided list">';
		foreach ( $articles as $post ) {
			$html .= '<div class="item aup_upload_articles_item" status="queue" post_id="' . $post->ID . '">';
				$html .= '<div class="ui grid">';
					$html .= '<div class="two column row">';
						$html .= '<div class="column">' . esc_html( $this->get_short_text( $post->post_title, 50 ) ) . '</div>';
						$html .= '<div class="right aligned column status"><i class="clock outline icon"></i></div>';
					$html .= '</div>';
				$html .= '</div>';
			$html .= '</div>';
		}
		$html .= '</div>';

		$return = array();
		$return['result'] = $html;
		$return['status'] = 'OK';

		die( json_encode( $return ) );
	}

	function ajax_article_drip_post() {
		$post_id = $_POST['postId'];
		$error = '';
		if ( $post_id ) {
			try {
				if ( $this->drip_article( $post_id ) ) {
					die( json_encode( array( 'success' => true ) ) ); }
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}
		}

		if ( ! empty( $error ) ) {
			die( json_encode( array( 'error' => $error ) ) );
		}
		die();
	}

	function drip_article( $id ) {
		$post = get_post( $id );
		if ( empty( $post ) || ( $post->post_type != 'bulkpost' && $post->post_type != 'bulkpage' ) ) {
			throw new Exception( __( 'Unexpected error occurred wile trying to post data.', 'mainwp-article-uploader-extension' ) );
			return false;
		}
		$type = $_POST['type'];
    global $mainWPArticleUploaderExtensionActivator;
		if ( 'import' == $type ) {
			$selected_sites = unserialize( base64_decode( get_post_meta( $id, '_selected_sites', true ) ) );
			$selected_groups = unserialize( base64_decode( get_post_meta( $id, '_selected_groups', true ) ) );

			if ( 'all' == $selected_sites ) {
				$websites = apply_filters( 'mainwp_getsites', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), null );
				$selected_sites = array();
				if ( is_array( $websites ) ) {
					foreach ( $websites as $website ) {
						$selected_sites[] = $website['id'];
					}
				}
			}
		} else {
			$selected_sites = $_POST['sites'];
			$selected_groups = $_POST['groups'];
		}

    if ( !is_array( $selected_sites ) )
    	$selected_sites = array();
		if ( !is_array( $selected_groups ) )
    	$selected_groups = array();

		if ( empty( $selected_sites ) && empty( $selected_groups ) ) {
			throw new Exception( __( 'Please select at least one website or group.', 'mainwp-article-uploader-extension' ) );
			return false;
		}

    if ( !empty( $selected_groups ) ) {
      $selected_sites = array();
      $dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), $selected_sites, $selected_groups );
      foreach($dbwebsites as $website) {
        $selected_sites[] = $website->id;
      }
    }

		if ( 'upload' == $type ) {
			$post_type = $_POST['post_type'];
			$post->post_type = $post_type;
			$post->comment_status = $_POST['allowed_comment'];
			wp_insert_post( $post );
			if ( 'bulkpost' == $post_type ) {
				$categories = isset( $_POST['categories'] ) ? $_POST['categories'] : '';
				update_post_meta( $id, '_categories', base64_encode( $categories ) );
			}
		}

		if ( $id ) {
			update_post_meta( $id, '_mainwp_post_dripper', 'yes' );
			delete_post_meta( $id, '_mainwp_is_aup_upload' );
			delete_post_meta( $id, '_mainwp_is_aup_import' );
			update_post_meta( $id, '_mainwp_post_dripper_sites_number', $_POST['number_sites'] );
			update_post_meta( $id, '_mainwp_post_dripper_time_number', $_POST['number_time'] );
			update_post_meta( $id, '_mainwp_post_dripper_select_time', $_POST['time_select'] );
			update_post_meta( $id, '_mainwp_post_dripper_use_post_dripper', 1 );
			update_post_meta( $id, '_mainwp_post_dripper_selected_drip_sites', base64_encode( serialize( $selected_sites ) ) );
			update_post_meta( $id, '_mainwp_post_dripper_total_drip_sites', count( $selected_sites ) );
		}

		return true;
	}

	function ajax_perform_publish_articles() {
		$sites 	= isset( $_POST['sites'] ) ? $_POST['sites'] : array();
		$groups = isset( $_POST['groups'] ) ? $_POST['groups'] : array();

		if ( ! is_array( $sites ) ) {
			$sites = array();
		}
		if ( ! is_array( $groups ) ) {
			$groups = array();
		}

		if ( empty( $sites ) && empty( $groups ) ) {
			die( json_encode( array( 'error' => __( 'Invalid data. Please make sure you have at least one website or group selected.', 'mainwp-article-uploader-extension' ) ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : 0;
		$post_type = 'bulkpost' == $_POST['post_type'] ? 'post' : 'page';
		$allowed_comment = $_POST['allowed_comment'];

		$categories = trim( $_POST['categories'] );
		$post_category = array();

		if ( ! empty( $categories ) ) {
			$categories = explode( ',', $categories );
			if ( is_array( $categories ) ) {
				foreach ( $categories as $cat ) {
                    $cat = trim( $cat );
					$post_category[] = esc_html($cat);
				}
			}
		}

		$post_category = implode( ',', $post_category );

		if ( empty( $post_id ) || ( 'post' != $post_type && 'page' != $post_type ) ) {
			die( json_encode( array( 'error' => __( 'Unexpected error occured. Please, try again.', 'mainwp-article-uploader-extension' ) ) ) );
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			die( json_encode( array( 'error' => __( 'Unexpected error occured. Please make sure that post content is not empty.', 'mainwp-article-uploader-extension' ) ) ) );
		}

		$new_post = array(
			'post_title' => $post->post_title,
			'post_content' => $post->post_content,
			'post_status' => 'publish',
			'comment_status' => $allowed_comment,
			'mainwp_post_id' => $post_id,
		);

		if ( 'page' == $post_type ) {
			$new_post['post_type'] = 'page';
		}

		global $mainWPArticleUploaderExtensionActivator;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), $sites, $groups );

		$output = new stdClass();
		$output->ok = array();
		$output->errors = array();

		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array( 'new_post' => base64_encode( serialize( $new_post ) ) );
			if ( 'post' == $post_type ) {
				$post_data['post_category'] = base64_encode( $post_category );
			}
			add_filter( 'mainwp_response_json_format', '__return_true' ); // going to remove
			do_action( 'mainwp_fetchurlsauthed', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), $dbwebsites, 'newpost', $post_data, array( 'MainWP_Article_Uploader', 'postingbulk_handler' ), $output );
		}

		$html = '';

		$html .= '<div class="ui divided list">';

		foreach ( $dbwebsites as $website ) {
			$html .= '<div class="item">';
			if ( isset( $output->ok[ $website->id ] ) && $output->ok[ $website->id ] == 1 ) {
				$html .= '<i class="green check icon"></i> ' . stripslashes( $website->name );
			} else {
				$html .= '<i class="red times icon"></i> ' . stripslashes( $website->name ) . ' - ' . $output->errors[ $website->id ];
			}
			$html .= '</div>';
		}

		$html .= '</div>';

		wp_delete_post( $post_id, true );

		die( json_encode( array( 'result' => $html, 'status' => 'OK' ) ) );
	}

	public static function postingbulk_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result = $results[1];			
//			$information = unserialize( base64_decode( $result ) );
			$information = json_decode( base64_decode( $result ), true );			
			if ( isset( $information['added'] ) ) {
				$output->ok[ $website->id ] = '1';
				if ( isset( $information['link'] ) ) {
					$output->link[ $website->id ] = $information['link'];
				}
				if ( isset( $information['added_id'] ) ) {
					$output->added_id[ $website->id ] = $information['added_id'];
				}
			} else if ( isset( $information['error'] ) ) {
				$output->errors[ $website->id ] = __( 'Error: ','mainwp-article-uploader-extension' ) . $information['error'];
			} else {
				$output->errors[ $website->id ] = __( 'Unexpected error. Please try to reset the MainWP Child plugin on the child site.','mainwp-article-uploader-extension' );
			}
		} else {
			$output->errors[ $website->id ] = apply_filters( 'mainwp_getErrorMessage', 'NOMAINWP', $website->url );
		}
	}

	static function get_short_file_name( $name ) {
		$info = pathinfo( $name );
		$short_name = $name;
		if ( strlen( $info['filename'] ) > 30 ) {
			$short_name = substr( $info['filename'], 0, 19 ) . '...' . substr( $info['filename'], -9 );
			$short_name .= '.' . $info['extension'];
		}
		return $short_name;
	}

	function get_short_text( $string, $len ) {
    if ( seems_utf8( $string ) ) {
      if ( mb_strlen( $string, 'UTF-8' ) <= $len ) {
        return $string;
      } else if ( seems_utf8( $string ) ){
        return mb_substr( $string, 0, $len, 'UTF-8' ) . ' ...';
      }
    } else {
      if ( strlen( $string ) <= $len ) {
        return $string;
      } else {
        return substr( $string, 0, $len ) . ' ...';
      }
    }
	}

	public function scan_upload_folder( $folder ) {
		MainWP_Article_Uploader_Extension::create_folders();
		$uploader_root = apply_filters( 'mainwp_getspecificdir', 'article_uploader' . $folder . '/' );
		$dir = @opendir( $uploader_root );
		$scan_files = array();
		if ( $dir ) {
			while ( ($file = readdir( $dir ) ) !== false ) {
				if ( substr( $file, 0, 1 ) == '.' ) {
					continue;
				}
				$ext = pathinfo( $file, PATHINFO_EXTENSION );
				if ( ! in_array( $ext, $this->allowedExtensions ) ) {
					continue;
				}
				$scan_files[] = $file;
			}
			closedir( $dir );
		}
		return $scan_files;
	}

	public static function render() {
		$new_upload_files = MainWP_Article_Uploader::get_instance()->scan_upload_folder( '/upload' );
		$new_import_files = MainWP_Article_Uploader::get_instance()->scan_upload_folder( '/import' );
		$message = '';

		if ( isset( $_GET['message'] ) && 1 == $_GET['message'] ) {
			$message = __( 'All uploaded articles are cancelled.', 'mainwp-article-uploader-extension' );
		}

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'upload' ) {
				$current_tab = 'upload';
			} else if ( $_GET['tab'] == 'import' ) {
				$current_tab = 'import';
			}
		}

		$args1 = array(
			'post_type' => array( 'bulkpost' ),
				'posts_per_page' => -1,
				'post_status' => 'any',
				'post_parent' => null,
				'meta_key'   => '_mainwp_is_aup_upload',
				'meta_value' => 'yes',
				'orderby' => 'post_modified',
				'order' => 'desc',
		);
		$articles1 = get_posts( $args1 );

		$args2 = array(
			'post_type' => array( 'bulkpost', 'bulkpage' ),
            'posts_per_page' => -1,
            'post_status' => 'any',
            'post_parent' => null,
            'meta_key'   => '_mainwp_is_aup_import',
            'meta_value' => 'yes',
            'orderby' => 'post_modified',
            'order' => 'desc',
		);
		$articles2 = get_posts( $args2 );


		$page_slug = 'PostBulkEdit';

		$post_type = 'post';
		$allowed_comment = 'open';

		$selected_sites = $selected_groups = array();

		$enabled_dripper =  is_plugin_active( 'mainwp-post-dripper-extension/mainwp-post-dripper-extension.php' );
		?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-article-uploader-menu">
				<a href="admin.php?page=Extensions-Mainwp-Article-Uploader-Extension&tab=upload" class="item <?php echo ( $current_tab == 'upload' ? 'active' : '' ); ?>"><i class="upload icon"></i> <?php _e( 'Upload Articles', 'mainwp-article-uploader-extension' ); ?></a>
				<a href="admin.php?page=Extensions-Mainwp-Article-Uploader-Extension&tab=import" class="item <?php echo ( $current_tab == 'import' ? 'active' : '' ); ?>" ><i class="download icon"></i> <?php _e( 'Import Articles', 'mainwp-article-uploader-extension' ); ?></a>
			</div>

			<!-- Upload -->
	    <?php if ( $current_tab == 'upload' || $current_tab == '' ) : ?>
    <div class="ui alt segment" id="mainwp-article-uploader-upload">
				<div class="mainwp-main-content">
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<div class="ui yellow message" style="display:<?php echo empty( $message ) ? 'none' : 'block'; ?>"><i class="close icon"></i><?php echo $message; ?></div>

					<div class="ui modal mainwp-aup-uploader-content" type="upload" id="mainwp-article-uploader-upload-modal">
					  <div class="header"><?php esc_html_e( 'Upload TXT File(s)', 'mainwp-article-uploader-extension' ); ?></div>
						<div class="scrolling content">
							<div id="mainwp-article-uploader-file-uploader"></div>
							<div class="qq-upload-list ui relaxed divided list">
						  <?php foreach ( $new_upload_files as $file ) : ?>
						    <?php $short_name = self::get_short_file_name( $file ); ?>
						    <div class="qq-upload-success item">
						      <div class="ui grid">
						        <div class="two column row">
						          <div class="left aligned middle aligned column"><span class="aup-upload-file qq-upload-file" status="queue" filename="<?php echo $file; ?>"><?php echo $short_name; ?></span></div>
						          <div class="right aligned middle aligned column status"><?php _e( 'Ready', 'mainwp-article-uploader' ); ?></div>
						        </div>
						      </div>
						    </div>
						  <?php endforeach; ?>
						  </div>
						</div>
					  <div class="actions">
							<div class="ui cancel button"><?php _e( 'Close' ); ?></div>
							<input type="button" class="ui green button aup_upload_articles_btn" value="<?php esc_attr_e( 'Upload Articles', 'mainwp-article-uploader-extension' ); ?>">
						</div>
					</div>

				<table class="ui stackable table" id="mainwp-article-uploader-articles-table">
						<thead>
							<tr>
								<th><?php _e( 'Article', 'mainwp-article-uploader-extension' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
						<?php if ( is_array( $articles1 ) && count( $articles1 ) ) :  ?>
						<?php foreach ( $articles1 as $post1 ) : ?>
							<?php $title = MainWP_Article_Uploader::get_instance()->get_short_text( $post1->post_title, 50 ); ?>
							<tr post-id="<?php echo $post1->ID; ?>" class="aup_upload_list_articles_item" status="queue">
								<td><?php echo esc_html( $title ); ?></td>
								<td class="right aligned">
									<a href="?page=<?php echo $page_slug; ?>&post_id=<?php echo $post1->ID; ?>" class="ui mini basic green button"><?php _e( 'Edit', 'mainwp-article-uploader-extension' ); ?></a>
									<a href="#" class="aup_list_publish_delete_post ui mini basic button" ><?php _e( 'Delete', 'mainwp-article-uploader-extension' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php else : ?>
							<tr>
							<td colspan="2">
								<p><?php echo esc_html( 'No uploaded articles for publishing. Upload your articles first.', 'mainwp-article-uploader-extension' ); ?></p>
								<p class="ui info message"><?php echo esc_html( 'The Upload Articles section allows you to upload multiple text files (.txt) containing only a title and content for an article. After uploading the articles, the Extension then gives you the options to publish articles immediately as a post or a page, set the category and allow/disallow comments.', 'mainwp-article-uploader-extension' ); ?></p>
								<p><a href="<?php echo content_url( '/plugins/mainwp-article-uploader-extension/download/sample.txt' ); ?>" class="ui mini button" target="_blank"><?php echo esc_html( 'See Example', 'mainwp-article-uploader-extension' ); ?></a></p>
							</td>
							</tr>
						<?php endif; ?>
						</tbody>
						<tfoot>
							<tr>
								<th>
									<form method="post" action="admin.php?page=Extensions-Mainwp-Article-Uploader-Extension">
			              <input type="submit" class="ui button" name="aup_upload_cancel_all" id="aup_upload_cancel_all" value="<?php _e( 'Remove Articles', 'mainwp-article-uploader-extension' );?>">
									</form>
								</th>
								<th class="right aligned">
									<button class="ui green labeled icon button" id="mainwp-article-uploader-upload-button">
									  <i class="upload icon"></i>
									  <?php _e( 'Upload Articles', 'mainwp-article-uploader-extension' ); ?>
									</button>
								</th>
							</tr>
						</tfoot>
					</table>
				</div>
				<div class="mainwp-side-content mainwp-no-padding">
					<div class="mainwp-select-sites">
						<h3 class="header"><?php _e( 'Select Sites', 'mainwp-article-uploader-extension' ); ?></h3>
						<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_sites, $selected_groups );  ?>
					</div>
					<div class="ui divider"></div>
					<div class="mainwp-search-options">
						<h3 class="header"><?php _e( 'Publish Articles', 'mainwp-article-uploader-extension' ); ?></h3>
						<div class="ui mini form">
							<div class="field">
								<label><?php _e( 'Publish as', 'mainwp-article-uploader-extension' ); ?></label>
								<select name="aup_select_publish_type" class="ui dropdown">
									<option value="bulkpost"><?php _e( 'Post', 'mainwp-article-uploader-extension' ); ?></option>
									<option value="bulkpage"><?php _e( 'Page', 'mainwp-article-uploader-extension' ); ?></option>
								</select>
							</div>
							<div class="field">
								<label><?php _e( 'With comments', 'mainwp-article-uploader-extension' ); ?></label>
								<select name="aup_select_allowed_comment" class="ui dropdown">
									<option value="open" <?php echo 'open' == $allowed_comment ? 'selected' : ''; ?> ><?php echo __( 'Allowed', 'mainwp-article-uploader-extension' ); ?></option>
									<option value="closed" <?php echo 'closed' == $allowed_comment ? 'selected' : ''; ?> ><?php echo __( 'Not allowed', 'mainwp-article-uploader-extension' ); ?></option>
								</select>
							</div>
							<div class="field">
								<label><?php _e( 'In categories', 'mainwp-article-uploader-extension' ); ?></label>
								<input type="text" name="aup_publish_categories" <?php echo 'page' == $post_type ? 'disabled' : ''; ?> value="">
							</div>
						</div>
					</div>
					<?php MainWP_Article_Uploader::get_instance()->gen_dripper_options( $enabled_dripper, 'upload' ); ?>
					<div class="ui divider"></div>
					<div class="mainwp-search-submit">
						<form method="post" action="admin.php?page=Extensions-Mainwp-Article-Uploader-Extension">
              <?php if ( $enabled_dripper ) : ?>
							<input type="button" class="ui big green basic fluid button disabled" id="aup_upload_drip_articles_btn" value="<?php _e( 'Drip Articles', 'mainwp-article-uploader-extension' ); ?>">
							<div class="ui hidden divider"></div>
              <?php endif; ?>
              <input type="button" class="ui big green fluid button" id="aup_upload_publish_article_btn" value="<?php _e( 'Publish Articles', 'mainwp-article-uploader-extension' );?>">
            </form>
						<span class="hidden-field" id="aup_upload_list_data" count_articles="<?php echo count( $articles1 ); ?>"></span>
					</div>
				</div>
				<div class="ui clearing hidden divider"></div>
	    </div>
			<script>mainwpArticleUploaderCreateUploaderFile();</script>
	    <?php endif; ?>

			<!-- Import -->
	    <?php if ( $current_tab == 'import' ) : ?>
	    <div class="ui alt segment" id="mainwp-article-uploader-import">

				<div class="mainwp-main-content">
					<div class="mainwp-actions-bar">
						<div class="ui grid">
							<div class="ui two column row">
								<div class="column">
									<select name="bulk_action" id="mainwp_bulk_action" class="ui dropdown">
								    <option value=""><?php _e( 'Bulk Action','mainwp-article-uploader-extension' ); ?></option>
								    <option value="publish"><?php _e( 'Publish','mainwp-article-uploader-extension' ); ?></option>
								    <option value="delete"><?php _e( 'Delete','mainwp-article-uploader-extension' ); ?></option>
										<?php if ( $enabled_dripper ) :  ?>
								    <option value="drip"><?php _e( 'Drip','mainwp-article-uploader-extension' ); ?></option>
										<?php endif; ?>
									</select><input type="button" name="" id="mainwp_article_uploader_action_apply" class="ui button" value="<?php esc_attr_e( 'Apply','mainwp-article-uploader-extension' ); ?>"/>
								</div>
								<div class="right aligned column"></div>
							</div>
						</div>
					</div>
					<div class="ui segment">
						<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
						<div class="ui yellow message" style="display:<?php echo empty( $message ) ? 'none' : 'block'; ?>"><i class="close icon"></i><?php echo $message; ?></div>
						<div class="ui modal mainwp-aup-uploader-content" type="import" id="mainwp-article-uploader-import-modal">
						  <div class="header"><?php esc_html_e( 'Import CSV File', 'mainwp-article-uploader-extension' ); ?></div>
							<div class="scrolling content">
								<div id="mainwp-article-uploader-file-uploader2"></div>
								<div class="qq-upload-list ui relaxed divided list">
							  <?php foreach ( $new_upload_files as $file ) : ?>
							    <?php $short_name = self::get_short_file_name( $file ); ?>
							    <div class="qq-upload-success item">
							      <div class="ui grid">
							        <div class="two column row">
							          <div class="left aligned middle aligned column"><span class="aup-upload-file qq-upload-file" status="queue" filename="<?php echo $file; ?>"><?php echo $short_name; ?></span></div>
							          <div class="right aligned middle aligned column status"><?php _e( 'Ready', 'mainwp-article-uploader' ); ?></div>
							        </div>
							      </div>
							    </div>
							  <?php endforeach; ?>
							  </div>
							</div>
						  <div class="actions">
								<div class="ui cancel button"><?php _e( 'Close' ); ?></div>
								<input type="button" class="ui green button aup_upload_articles_btn" value="<?php esc_attr_e( 'Import Articles', 'mainwp-article-uploader-extension' ); ?>">
							</div>
						</div>
						<?php MainWP_Article_Uploader::get_instance()->gen_dripper_options( $enabled_dripper, 'import' ); ?>
						<table class="ui stackable single line table" id="mainwp-article-uploader-articles-table">
							<thead>
								<tr>
									<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
									<th><?php _e( 'Title', 'mainwp-article-uploader-extension' ); ?></th>
									<th><?php _e( 'Post Type', 'mainwp-article-uploader-extension' ); ?></th>
									<th><?php _e( 'Author', 'mainwp-article-uploader-extension' ); ?></th>
									<th><?php _e( 'Categories', 'mainwp-article-uploader-extension' ); ?></th>
									<th><?php _e( 'Tags', 'mainwp-article-uploader-extension' ); ?></th>
									<th><?php _e( 'Status', 'mainwp-article-uploader-extension' ); ?></th>
									<th><?php _e( 'Website', 'mainwp-article-uploader-extension' ); ?></th>
									<th class="no-sort collapsing"><?php _e( '', 'mainwp-article-uploader-extension' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php self::render_table_content( $articles2 ); ?>
							</tbody>
							<tfoot>
								<tr class="full-width">
									<th colspan="9" class="right aligned">
										<button class="ui green labeled icon button" id="mainwp-article-uploader-import-button">
										  <i class="download icon"></i>
										  <?php _e( 'Import CSV File', 'mainwp-article-uploader-extension' ); ?>
										</button>
									</th>
								</tr>
							</tfoot>
						</table>
						<script type="text/javascript">
                            jQuery( document ).ready( function () {
                                jQuery( '#mainwp-article-uploader-articles-table' ).DataTable( {
                                    "order": [ [ 1, "desc" ] ],
                                    "columnDefs": [ { "targets": 'no-sort', "orderable": false } ]
                                } );
                            } );
                        </script>
					</div>
				</div>
				<div class="mainwp-side-content">
					<p><?php echo __( 'The MainWP Article Uploader Extension allows you to upload multiple articles to your child sites. You are given the option to upload separate TXT files or to import them using a custom formatted CSV file.', 'mainwp-article-uploader-extension' ); ?></p>
					<h3 class="header"><?php _e( 'Import Articles', 'mainwp-article-uploader-extension' ); ?></h3>
					<p><?php _e( 'A more powerful method that allows you to import multiple articles in one or more TXT or CSV files. This method also enables you to set not only article Title and Body, but also article Slug, Categories, Tags, Excerpt, Author… After uploading articles, the extension also allows you to edit the uploaded content before publishing.', 'mainwp-article-uploader-extension' ); ?></p>
					<a href="admin.php?page=Extensions-Mainwp-Article-Uploader-Extension&action=download-sample" class="ui fluid button"><?php _e( 'Download Sample CSV' ); ?></a>
					<div class="ui hidden divider"></div>
					<a href="https://mainwp.com/help/docs/article-uploader-extension/" class="ui big green fluid button"><?php _e( 'Extension Documentation', 'mainwp-article-uploader' ); ?></a>
				</div>
				<div class="ui clearing hidden divider"></div>
	    </div>
			<script>mainwpArticleUploaderCreateUploaderFile2();</script>

	    <?php endif; ?>

		<div class="ui modal" id="mainwp-article-uploader-publishing-modal">
			<div class="header"><?php _e( 'Publishing Articles', 'mainwp-article-uploader-extension' ); ?></div>
			<div class="scrolling content"></div>
			<div class="actions">
				<div class="ui cancel button"><?php _e( 'Close', 'mainwp-article-uploader-extension' ); ?></div>
			</div>
		</div>
		<?php
	}

	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	function gen_dripper_options( $enabled_dripper = false, $type ) {
		$use_dripper = false;
		$nb_sites = $nb_time = 1;
		$select_time = 'days';

		$_checked_use_dripper = '';

		if ( $use_dripper ) {
			$_checked_use_dripper = 'checked';
		}

		$times = array( 'hours', 'days', 'weeks', 'months' );
		$_class = 'disabled';
		if ( $enabled_dripper ) {
			$_class = 'enabled';
		}
		?>

		<?php if ( $enabled_dripper ) : ?>
			<div class="ui divider"></div>
			<div class="mainwp-search-options">
				<h3 class="header"><?php _e( 'Dripper Options', 'mainwp-article-uploader-extension' ); ?></h3>
			<div class="ui mini form">
				<div class="field">
					<label><?php _e( 'Drip article', 'mainwp-article-uploader-extension' ); ?></label>
					<div class="ui toggle checkbox">
						<input type="checkbox" <?php echo $_checked_use_dripper; ?> class="aup_use_post_dripper" id="<?php echo 'import' == $type ? 'aup_use_post_dripper_import' : 'aup_use_post_dripper_upload'; ?>" value="1"/>
					</div>
				</div>
					<span class="field"><label><?php _e( 'Drip frequency', 'mainwp-article-uploader-extension' ); ?></label></span>
					<div class="ui three fields">
					<div class="field">
						<input class="aup_dripper_sites_number" type="number" value="<?php echo $nb_sites; ?>" min="1" max="200" />
							<label><?php _e( 'Sites', 'mainwp-article-uploader-extension' ); ?></label>
					</div>
					<div class="field">
						<input class="aup_dripper_time_number" type="number" value="<?php echo $nb_time; ?>" min="1" max="500" />
							<label><?php _e( 'Times', 'mainwp-article-uploader-extension' ); ?></label>
					</div>
					<div class="field">
						<select class="aup_dripper_select_time" class="ui dropdown">
						<?php
						foreach ( $times as $time ) {
							echo '<option value="' . $time . '" ' . ( $select_time == $time ? ' selected ' : '' ) . ' >' . $time . '</option>';
						}
						?>
						</select>
							<label><?php _e( 'Frequency', 'mainwp-article-uploader-extension' ); ?></label>
					</div>
				</div>
			</div>
			<div class="aup-dripper-options <?php echo $_class; ?>" type="<?php echo $type; ?>">
			<div id="aup_<?php echo $type; ?>_dripper_options"></div>
				</div>
			</div>
		<?php endif; ?>
    <?php
	}

	public static function render_table_content( $articles ) {

		global $mainWPArticleUploaderExtensionActivator;

		foreach ( $articles as $post ) {
			$page_slug = 'PostBulkEdit';
			$_type = __( 'Post', 'mainwp-article-uploader-extension' );

			if ( $post->post_type == 'bulkpage' ) {
				$page_slug = 'PageBulkEdit';
				$_type = __( 'Page', 'mainwp-article-uploader-extension' );
			}

			$selected_sites = unserialize( base64_decode( get_post_meta( $post->ID, '_selected_sites', true ) ) );
			//print_r($selected_sites);
			$sites = '';
			if ( 'all' == $selected_sites ) {
				$sites = __( 'All sites', 'mainwp-article-uploader-extension' );
			} else if ( is_array( $selected_sites ) ) {
				$sites = array();
				$dbsites = apply_filters( 'mainwp_getdbsites', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), $selected_sites, array() );
				if ( $dbsites ) {
					foreach ( $dbsites as $site ) {
						$sites[] = '<a href="' . $site->url . '" target="_blank">' . $site->url . '</a>';
					}
				}
			}

			?>
      <tr id="post-<?php echo intval($post->ID); ?>" post-id="<?php echo intval($post->ID); ?>">
        <td class="ui checkbox check-column"><input type="checkbox" value="1" name="post[]"></td>
        <td><a href="?page=<?php echo $page_slug; ?>&post_id=<?php echo intval($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a></td>
        <td><?php echo $_type; ?></td>
        <td><?php echo self::get_column( $post, 'author' ); ?></td>
        <td><?php echo self::get_column( $post, 'categories' ); ?></td>
        <td><?php echo self::get_column( $post, 'tags' ); ?></td>
        <td><?php echo self::column_date( $post ); ?></td>
        <td>
        <?php
				if ( is_array( $sites ) ) {
					if ( count( $sites ) > 0 ) {
						echo implode( ',<br>', $sites ); }
				} else {
					echo $sites;
				}
				?>
        </td>
				<td>
					<div class="ui dropdown">
					  <i class="ellipsis horizontal icon"></i>
					  <div class="menu">
					    <a class="item" href="?page=<?php echo $page_slug; ?>&post_id=<?php echo $post->ID; ?>"><?php _e( 'Edit', 'mainwp-article-uploader-extension' ); ?></a>
					    <a class="item aup_posts_list_delete_post" href="#"><?php _e( 'Delete', 'mainwp-article-uploader-extension' ); ?></a>
					    <a class="item aup_posts_list_publish_post" href="#"><?php _e( 'Publish', 'mainwp-article-uploader-extension' ); ?></a>
					    <a class="item aup_posts_list_drip_post" href="#"><?php _e( 'Drip', 'mainwp-article-uploader-extension' ); ?></a>
					  </div>
					</div>
				</td>
      </tr>
    <?php
		}
	}

	public static function get_column( $post, $column_name ) {
		if ( empty( $post ) ) {
			return '';
		}

		$result = 'N/A';

		if ( 'categories' === $column_name ) {
			if ( $post->post_type == 'bulkpost' ) {
				$value = get_post_meta( $post->ID, '_categories', true );
				if ( ! empty( $value ) ) {
					$result = base64_decode( $value );
				}
			}
		} else if ( 'tags' === $column_name ) {
			if ( 'bulkpost' == $post->post_type ) {
				$value = get_post_meta( $post->ID, '_tags', true );
				if ( ! empty( $value ) ) {
					$result = base64_decode( $value );
				}
			}
		} else if ( 'author' === $column_name ) {
			$value = get_post_meta( $post->ID, '_author', true );
			if ( ! empty( $value ) ) {
				$result = base64_decode( $value );
			}
		}
		return $result;
	}

	public static function format_timestamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	public static function column_date( $post ) {
		if ( empty( $post ) ) {
			return '';
		}

		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$h_time = __( 'Unpublished', 'mainwp-article-uploader-extension' );
		} else {
			$time = mysql2date( 'U', $post->post_modified, false );
			$h_time = self::format_timestamp( $time );
		}

		ob_start();
		echo  $h_time;
		echo ' (';
		if ( 'publish' == $post->post_status ) {
			_e( 'Published', 'mainwp-article-uploader-extension' );
		} elseif ( 'future' == $post->post_status ) {
			_e( 'Scheduled', 'mainwp-article-uploader-extension' );
		} else {
			_e( 'Last Modified', 'mainwp-article-uploader-extension' );
		}
		echo ')';
		$output = ob_get_clean();
		return $output;
	}

	public function ajax_posting() {
		$post_id = $_POST['postId'];
		$error = '';
		if ( $post_id ) {
			try {
				$result = $this->posting( $post_id );
				if ( ! empty( $result ) ) {
					die( json_encode( array( 'result' => $result ) ) ); }
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}
		}

		if ( ! empty( $error ) ) {
			die( json_encode( array( 'error' => $error ) ) ); }

		die();
	}

	public static function posting( $id ) {
		$post = get_post( $id );
		if ( empty( $post ) || ($post->post_type != 'bulkpost' && $post->post_type != 'bulkpage') ) {
			throw new Exception( __( 'Invalid article data.', 'mainwp-article-uploader-extension' ) );
			return;
		}

		$selected_by = get_post_meta( $id, '_selected_by', true );
		$selected_sites = unserialize( base64_decode( get_post_meta( $id, '_selected_sites', true ) ) );
		$selected_groups = unserialize( base64_decode( get_post_meta( $id, '_selected_groups', true ) ) );

		global $mainWPArticleUploaderExtensionActivator;

		if ( 'all' == $selected_sites ) {
			$websites = apply_filters( 'mainwp_getsites', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), null );
			$selected_sites = array();
			if ( is_array( $websites ) ) {
				foreach ( $websites as $website ) {
					$selected_sites[] = $website['id'];
				}
			}
		}

		if ( empty( $selected_by ) || (empty( $selected_sites ) && empty( $selected_groups )) ) {
			throw new Exception( __( 'No child sites set.', 'mainwp-article-uploader-extension' ) );
			return;
		}

		$post_category = base64_decode( get_post_meta( $id, '_categories', true ) );

		$post_tags = base64_decode( get_post_meta( $id, '_tags', true ) );
		$post_slug = base64_decode( get_post_meta( $id, '_slug', true ) );
		$post_author = base64_decode( get_post_meta( $id, '_author', true ) );
		$post_custom = get_post_custom( $id );

		include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'post-thumbnail-template.php' );
		$post_featured_image = get_post_thumbnail_id( $id );
		$mainwp_upload_dir = wp_upload_dir();

		$new_post = array(
			'post_title' => $post->post_title,
			'post_content' => $post->post_content,
			'post_status' => 'publish',
			'post_date' => $post->post_date,
			'post_date_gmt' => $post->post_date_gmt,
			'post_tags' => $post_tags,
			'post_name' => $post_slug,
			'post_excerpt' => $post->post_excerpt,
			'comment_status' => $post->comment_status,
			'ping_status' => $post->ping_status,
			'custom_post_author' => $post_author,
            'mainwp_post_id' => $post->ID
		);
		$post_type = 'post';
		if ( $post->post_type == 'bulkpage' ) {
			$new_post['post_type'] = $post_type = 'page';
			unset( $new_post['post_tags'] );
		}

		if ( null != $post_featured_image ) { //Featured image is set, retrieve URL
			$img = wp_get_attachment_image_src( $post_featured_image, 'full' );
			$post_featured_image = $img[0];
		}

		global $mainWPArticleUploaderExtensionActivator;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), $selected_sites, $selected_groups );

		$output = new stdClass();
		$output->ok = array();
		$output->errors = array();
		$output->link = array();
		$output->added_id = array();

		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'new_post' => base64_encode( serialize( $new_post ) ),
				'post_custom' => base64_encode( serialize( $post_custom ) ),
				'post_category' => base64_encode( $post_category ),
				'post_featured_image' => base64_encode( $post_featured_image ),
				'mainwp_upload_dir' => base64_encode( serialize( $mainwp_upload_dir ) ),
			);

			if ( $post->post_type == 'bulkpage' ) {
				unset( $post_data['post_category'] );
			}
			add_filter( 'mainwp_response_json_format', '__return_true' );
			do_action( 'mainwp_fetchurlsauthed', $mainWPArticleUploaderExtensionActivator->get_child_file(), $mainWPArticleUploaderExtensionActivator->get_child_key(), $dbwebsites, 'newpost', $post_data, array( 'MainWP_Article_Uploader', 'postingbulk_handler' ), $output );
		}

		wp_delete_post( $id, true );
		$result = '';
		foreach ( $dbwebsites as $website ) {
			$result .= '<i class="green check circle icon"></i>' . stripslashes( $website->name ) . ': '. (isset( $output->ok[ $website->id ] ) && $output->ok[ $website->id ] == 1 ? 'New ' . $post_type . ' created. '.'<a href="'.$output->link[ $website->id ].'" target="_blank">View Post</a>' : '<i class="red times circle icon"></i> ' . $output->errors[ $website->id ]) . '<br>';
		}
		return $result;
	}

}
