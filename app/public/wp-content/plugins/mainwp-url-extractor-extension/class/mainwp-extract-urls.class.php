<?php

class MainWP_Extract_Urls {

	public static $availableTokens = array(
		'[post.title]',
		'[post.url]',
		'[post.date]',
		'[post.status]',
		'[post.author]',
		'[post.website.url]',
		'[post.website.name]',
	);

	public function __construct() {

	}

	public function init() {

	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_extract_preview_ouput', array( &$this, 'extract_preview_ouput' ) );
		add_action( 'wp_ajax_mainwp_extract_urls_load_template', array( &$this, 'load_template' ) );
	}

	public function init_menu() {

	}

	public function extract_preview_ouput() {
		$return = array();

		try {
			$return['result'] = self::render_preview_ouput( $_POST['keyword'], $_POST['dtsstart'], $_POST['dtsstop'], $_POST['status'], ( isset( $_POST['groups'] ) ? $_POST['groups'] : '' ), ( isset( $_POST['sites'] ) ? $_POST['sites'] : '' ), $_POST['postId'], $_POST['userId'], $_POST['post_type'] );
		} catch ( Exception $e ) {
			$return['error'] = $e->getMessage();
		}

		die( json_encode( $return ) );
	}

	public function load_template() {
		$id     = $_POST['tempId'];
		$return = array();
		if ( $id ) {
			$template = MainWP_Extract_Urls_DB::get_instance()->get_template_by( 'id', $id );
			if ( is_object( $template ) ) {
				$return = array(
					'format_output' => stripslashes( $template->format_output ),
					'separator'     => stripslashes( $template->separator ),
					'status'        => 'success',
				);
				die( json_encode( $return ) );
			}
		}
		die( json_encode( $return ) );
	}

	public static function save_template() {

		if ( isset( $_POST['ext_save_template_btn_save'] ) ) {
			$template = array(
				'title'         => trim( $_POST['ext_save_template_title'] ),
				'separator'     => $_POST['mainwp_extract_separator'],
				'format_output' => $_POST['mainwp_extract_format_output'],
			);

			if ( MainWP_Extract_Urls_DB::get_instance()->update_template( $template ) ) {
				return true;
			}
			return false;
		}
	}

	public static function delete_template() {
		if ( isset( $_POST['ext_detele_selected_template'] ) ) {
			return MainWP_Extract_Urls_DB::get_instance()->delete_template( intval( $_POST['ext_detele_selected_template'] ) );
		}
	}

	public static function render_preview_ouput( $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId, $userId, $post_type ) {
		global $mainwpUrlExtractorExtensionActivator;

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpUrlExtractorExtensionActivator->get_child_file(), $mainwpUrlExtractorExtensionActivator->get_child_key(), $sites, $groups );

		$output         = new stdClass();
		$output->errors = array();
		$output->posts  = 0;
		$output->result = '';

		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'keyword'           => $keyword,
				'dtsstart'          => $dtsstart,
				'dtsstop'           => $dtsstop,
				'status'            => $status,
				'where_post_date'   => 1,
				'extract_post_type' => $post_type,
				'maxRecords'        => ( ( get_option( 'mainwp_maximumPosts' ) === false ) ? 50 : get_option( 'mainwp_maximumPosts' ) ),
			);

			if ( isset( $postId ) && ( '' != $postId ) ) {
				$post_data['postId'] = $postId;
			} elseif ( isset( $userId ) && ( '' != $userId ) ) {
				$post_data['userId'] = $userId;
			}

			$format_output = isset( $_POST['format_output'] ) ? trim( $_POST['format_output'] ) : '';

			$matches = array();

			if ( preg_match_all( '/(\[[^\]]+\])/is', $format_output, $matches ) ) {
				$matches = $matches[1];
			}

			$tokens = array();
			foreach ( $matches as $token ) {
				if ( in_array( trim( $token ), self::$availableTokens ) ) {
					$tokens[] = $token;
				}
			}

			if ( ! is_array( $tokens ) || count( $tokens ) == 0 ) {
				throw new Exception( __( 'Format output is empty.', 'mainwp-url-extractor-extension' ) );
			}

			$post_data['extract_tokens'] = base64_encode( serialize( $tokens ) );
			add_filter( 'mainwp_response_json_format', '__return_true' ); // going to remove
			do_action( 'mainwp_fetchurlsauthed', $mainwpUrlExtractorExtensionActivator->get_child_file(), $mainwpUrlExtractorExtensionActivator->get_child_key(), $dbwebsites, 'get_all_posts', $post_data, array( 'MainWP_Extract_Urls', 'posts_extract_handler' ), $output );
		}

		// Sort if required
		if ( $output->posts == 0 ) {
			throw new Exception( __( 'No posts found.', 'mainwp-url-extractor-extension' ) );
		}

		return $output->result;
	}

	public static function posts_extract_handler( $data, $website, &$output ) {

		$separator     = isset( $_POST['separator'] ) ? $_POST['separator'] : '';
		$format_output = isset( $_POST['format_output'] ) ? $_POST['format_output'] : '';

		$matches = array();

		if ( preg_match_all( '/(\[[^\]]+\])/is', $format_output, $matches ) ) {
			$matches = $matches[1];
		}

		$tokens = array();

		foreach ( $matches as $token ) {
			if ( in_array( trim( $token ), self::$availableTokens ) ) {
				$tokens[] = $token;
			}
		}

		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			// $posts = unserialize( base64_decode( $results[1] ) );
			$posts = json_decode( base64_decode( $results[1] ), true );

			unset( $results );
			foreach ( $posts as $post ) {
				if ( isset( $post['dts'] ) ) {
					if ( ! stristr( $post['dts'], '-' ) ) {
						$post['dts'] = MainWP_Extract_Urls_Utility::format_timestamp( MainWP_Extract_Urls_Utility::get_timestamp( $post['dts'] ) );
					}
				}

				if ( ! isset( $post['title'] ) || ( '' == $post['title'] ) ) {
					$post['title'] = '(No Title)';
				}

				$str = $format_output;

				foreach ( $tokens as $token ) {
					if ( in_array( $token, array( '[post.url]', '[post.website.url]', '[post.website.name]' ) ) ) {
						if ( isset( $post[ $token ] ) ) {
							$str = str_replace( $token, $post[ $token ], $str );
						}
					} else {
						switch ( $token ) {
							case '[post.title]':
								if ( isset( $post['title'] ) ) {
									$str = str_replace( $token, $post['title'], $str );
								}
								break;
							case '[post.date]':
								if ( isset( $post['dts'] ) ) {
									$str = str_replace( $token, $post['dts'], $str );
								}
								break;
							case '[post.status]':
								if ( isset( $post['status'] ) ) {
									$str = str_replace( $token, $post['status'], $str );
								}
								break;
							case '[post.author]':
								if ( isset( $post['author'] ) ) {
									$str = str_replace( $token, $post['author'], $str );
								}
								break;
						}
					}
				}

				$str = stripslashes( $str );

				if ( empty( $separator ) ) {
					$output->result .= $str . "\n";
				} else {
					$output->result .= $str . $separator;
				}

				$output->posts++;
			}
			$output->result = rtrim( $output->result, $separator );
			unset( $posts );
		} else {
			$output->errors[ $website->id ] = MainWPErrorHelper::getErrorMessage( new MainWPException( 'NOMAINWP', $website->url ) );
		}
	}


	public static function render() {
		$str_info   = array();
		$info_class = 'display:none';
		$info_style = '';

		if ( isset( $_POST['ext_save_template_btn_save'] ) ) {
			$info_class    = 'display:block';
			$save_template = self::save_template();
			if ( $save_template ) {
				$str_info[] = __( 'Template saved successfully.', 'mainwp-url-extractor-extension' );
				$info_style = 'green';
			} else {
				$str_info[] = __( 'Saving template failed.', 'mainwp-url-extractor-extension' );
				$info_style = 'red';
			}
		}

		if ( isset( $_POST['ext_detele_selected_template'] ) ) {
			$info_class = 'display:block';
			$deleted    = self::delete_template();
			if ( $deleted ) {
				$str_info[] = __( 'Template deleted successfully.', 'mainwp-url-extractor-extension' );
				$info_style = 'green';
			} else {
				$str_info[] = __( 'Deleting template failed.', 'mainwp-url-extractor-extension' );
				$info_style = 'red';
			}
		}
		?>
		<form action="" method="post" id="mainwp-url-extractor-form">
			<div class="ui alt segment" id="mainwp-url-extractor">
				<div class="mainwp-main-content">
					<div class="ui message <?php echo $info_style; ?>" style="<?php echo $info_class; ?>"><i class="close icon"></i><?php echo count( $str_info ) > 0 ? implode( '<br />', $str_info ) : ''; ?></div>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<div class="ui modal" id="mainwp-url-extractor-output-modal">
						<div class="header"><?php _e( 'Save URL Extractor Template' ); ?></div>
						<div class="content">
							<div class="ui message" id="mainwp-modal-message-zone" style="display:none"></div>
							<div class="ui inverted dimmer">
							<div class="ui text loader"><?php echo __( 'Loading content. Please wait...', 'mainwp-url-extractor-extension' ); ?></div>
						  </div>
							<div class="ui form">
								<div class="field"><textarea name="mainwp_extract_preview_output" wrap="off" id="mainwp_extract_preview_output"></textarea></div>
							</div>
						</div>
						<div class="actions">
							<input type="hidden" name="mainwp_extract_enable_export" id="mainwp_extract_enable_export" value="0"/>
							<div class="ui cancel button"><?php _e( 'Close', 'mainwp-url-extractor-extension' ); ?></div>
							<input type="button" class="ui green button mainwp_extract_btn_export_txt" value="<?php _e( 'Export As .txt', 'mainwp' ); ?>"/>
							<input type="button" class="ui green button mainwp_extract_btn_export_csv" value="<?php _e( 'Export As .csv', 'mainwp' ); ?>"/>
						</div>
					</div>
					<div class="ui form">
						<div class="ui hidden divider"></div>
						<div class="field">
							<div class="ui stackable grid">
								<div class="fourteen wide column"><input type="text" placeholder="<?php esc_attr_e( 'Output format', 'mainwp-url-extractor-extension' ); ?>" name="mainwp_extract_format_output" id="mainwp_extract_format_output" value="" /></div>
								<div class="two wide column"><input type="text" placeholder="<?php esc_attr_e( 'Separator', 'mainwp-url-extractor-extension' ); ?>" name="mainwp_extract_separator" id="mainwp_extract_separator" value=""/></div>
							</div>
						</div>
						<div class="ui hidden divider"></div>
						<div class="ui hidden divider"></div>
					</div>
					<div class="ui stackable grid">
						<div class="two column row">
							<div class="column">
								<div class="ui secondary segment">
									<h3 class="header"><?php echo __( 'Available Tokens', 'mainwp-url-extractor-extension' ); ?></h3>
									<div class="ui list">
										<div class="item"><a class="ui mini green basic button ext_url_add_token" href="#">[post.title]</a> - <em><?php echo __( 'Extracts Post/Page title', 'mainwp-url-extractor-extension' ); ?></em></div>
										<div class="item"><a class="ui mini green basic button ext_url_add_token" href="#">[post.url]</a> - <em><?php echo __( 'Extracts Post/Page URL', 'mainwp-url-extractor-extension' ); ?></em></div>
										<div class="item"><a class="ui mini green basic button ext_url_add_token" href="#">[post.date]</a> - <em><?php echo __( 'Extracts Post/Page publishing date', 'mainwp-url-extractor-extension' ); ?></em></div>
										<div class="item"><a class="ui mini green basic button ext_url_add_token" href="#">[post.status]</a> - <em><?php echo __( 'Extracts Post/Page status', 'mainwp-url-extractor-extension' ); ?></em></div>
										<div class="item"><a class="ui mini green basic button ext_url_add_token" href="#">[post.author]</a> - <em><?php echo __( 'Extracts Post/Page author', 'mainwp-url-extractor-extension' ); ?></em></div>
										<div class="item"><a class="ui mini green basic button ext_url_add_token" href="#">[post.website.url]</a> - <em><?php echo __( 'Extracts Post/Page website URL', 'mainwp-url-extractor-extension' ); ?></em></div>
										<div class="item"><a class="ui mini green basic button ext_url_add_token" href="#">[post.website.name]</a> - <em><?php echo __( 'Extracts Post/Page website name', 'mainwp-url-extractor-extension' ); ?></em></div>
									</div>
								</div>
							</div>
							<div class="column">
								<div class="ui secondary segment">
									<h3 class="header"><?php echo __( 'Save Current Format', 'mainwp-url-extractor-extension' ); ?></h3>
									<div class="ui form">
										<div class="ui fluid field">
											<input type="text" name="ext_save_template_title" id="ext_save_template_title" placeholder="<?php esc_attr_e( 'Enter template title' ); ?>"/>
										</div>
										<input type="submit" class="ui green basic button" name="ext_save_template_btn_save" id="ext_save_template_btn_save" value="<?php esc_attr_e( 'Save Template', 'mainwp-url-extractor-extension' ); ?>" />
									</div>
									<h3 class="header"><?php echo __( 'Available Templates', 'mainwp-url-extractor-extension' ); ?></h3>
									<?php
									$templates = MainWP_Extract_Urls_DB::get_instance()->get_template_by( 'all' );
									if ( is_array( $templates ) && count( $templates ) > 0 ) {
										?>
										<div class="ui form">
											<div class="ui fluid field">
												<?php echo self::gen_template_select( $templates ); ?>
											</div>
						  <input type="button" class="ui green basic button" name="ext_template_btn_use" id="ext_template_btn_use" value="<?php esc_attr_e( 'Use Template', 'mainwp-url-extractor-extension' ); ?>" />
											<a href="#" class="ui basic button" id="mainwp-url-extreactor-delete-template"><?php _e( 'Delete', 'mainwp-url-extractor-extension' ); ?></a>
											<input type="hidden" name="ext_detele_selected_template" id="ext_detele_selected_template" value="0"/>
										</div>
										<?php
									} else {
										echo 'No Saved Templates';
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="mainwp-side-content mainwp-no-padding">
					<div class="mainwp-select-sites">
						<div class="ui header"><?php echo __( 'Select Sites', 'mainwp-url-extractor-extension' ); ?></div>
						<?php do_action( 'mainwp_select_sites_box' ); ?>
					</div>
					<div class="ui divider"></div>
					<div class="mainwp-search-options">
						<div class="ui header"><?php echo __( 'Search Options', 'mainwp-url-extractor-extension' ); ?></div>
						<div class="ui mini form">
							<div class="field">
								<select multiple="" class="ui multiple fluid dropdown" id="mainwp_post_search_type">
									<option value=""><?php echo __( 'Select post type', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="1"><?php echo __( 'Post', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="2"><?php echo __( 'Page', 'mainwp-url-extractor-extension' ); ?></option>
								</select>
							</div>
							<div class="field">
								<select multiple="" class="ui multiple fluid dropdown" id="mainwp_post_search_status">
									<option value=""><?php echo __( 'Select status', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="publish"><?php echo __( 'Published', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="pending"><?php echo __( 'Pending', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="private"><?php echo __( 'Private', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="future"><?php echo __( 'Scheduled', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="draft"><?php echo __( 'Draft', 'mainwp-url-extractor-extension' ); ?></option>
									<option value="trash"><?php echo __( 'Trash', 'mainwp-url-extractor-extension' ); ?></option>
								</select>
							</div>
							<div class="field">
								<div class="ui input fluid">
									<input type="text" placeholder="<?php esc_attr_e( 'Containing keyword', 'mainwnp-url-extractor-extension' ); ?>" id="mainwp_post_search_by_keyword" value="">
								</div>
							</div>
							<div class="field">
								<div class="field">
									<label><?php _e( 'Date Range:', 'mainwp-url-extractor-extension' ); ?></label>
									<div class="two fields">
										<div class="field">
											<div class="ui calendar mainwp_datepicker" >
								  <div class="ui input left icon">
									<i class="calendar icon"></i>
									<input type="text" placeholder="<?php esc_attr_e( 'Date', 'mainwp' ); ?>" id="mainwp_post_search_by_dtsstart" value=""/>
								  </div>
								</div>
					  </div>
										<div class="field">
											<div class="ui calendar mainwp_datepicker" >
								  <div class="ui input left icon">
									<i class="calendar icon"></i>
									<input type="text" placeholder="<?php esc_attr_e( 'Date', 'mainwp' ); ?>" id="mainwp_post_search_by_dtsstop" value=""/>
								  </div>
								</div>
					  </div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="ui divider"></div>
					<div class="mainwp-search-submit">
						<input type="button" name="mainwp_extract_btn_preview_ouput" id="mainwp_extract_btn_preview_ouput" class="ui green fluid big button" value="<?php _e( 'Preview Ouput', 'mainwp-url-extractor' ); ?>"/>
					</div>
				</div>
				<div class="ui clearing hidden divider"></div>
			</div>
		</form>
		<?php
	}

	public static function gen_template_select( $templates ) {
		$str = '';
		if ( is_array( $templates ) && count( $templates ) > 0 ) {
			$str  = '<select name="ext_urls_template_select" id="ext_urls_template_select" class="ui dropdown">';
			$str .= '<option value=""></option>';
			foreach ( $templates as $template ) {
				if ( ! empty( $template ) ) {
					$str .= '<option value="' . $template->id . '">' . $template->title . '</option>'; }
			}
			$str .= '</select>';
		}
		return $str;
	}

}
