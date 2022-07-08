<?php

class MainWP_Comment {

	public static $instance = null;

	public static function get_class_name() {
		return __CLASS__;
	}

	public static $subPages;

	public $security_nonces;

	static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	public static function render() {

		$cachedSearch = apply_filters( 'mainwp_cache_getcontext', 'Comment' );

		if ( false === $cachedSearch ) {
			$cachedSearch = null;
		}
		?>
		<div class="ui alt segment" id="mainwp-coments-extension">
			<div class="mainwp-main-content">
				<div class="mainwp-actions-bar ui mini form">
					<div class="ui grid">
						<div class="ui two column row">
							<div class="column">
								<select class="ui dropdown" id="mainwp-bulk-actions">
									<option value="none"><?php _e( 'Bulk Action', 'mainwp-comments-extension' ); ?></option>
									<option value="unapprove"><?php _e( 'Unapprove', 'mainwp-comments-extension' ); ?></option>
									<option value="approve"><?php _e( 'Approve', 'mainwp-comments-extension' ); ?></option>
									<option value="spam"><?php _e( 'Mark as Spam', 'mainwp-comments-extension' ); ?></option>
									<option value="unspam"><?php _e( 'Not Spam', 'mainwp-comments-extension' ); ?></option>
									<option value="trash"><?php _e( 'Move to Trash', 'mainwp-comments-extension' ); ?></option>
									<option value="restore"><?php _e( 'Restore', 'mainwp-comments-extension' ); ?></option>
									<option value="delete"><?php _e( 'Delete Permanently', 'mainwp-comments-extension' ); ?></option>
								</select>
								<input type="button" name="" id="mainwp_comments_bulk_action_apply" class="ui basic mini button" value="<?php _e( 'Apply', 'mainwp-comments-extension' ); ?>"/>
								<input type="hidden" id="bulk_comment_ids" name="bulk_comment_ids" value=""/>
					<input type="hidden" id="bulk_comment_wpids" name="bulk_comment_wpids" value=""/>
								<?php do_action( 'mainwp_comments_actions_bar_left' ); ?>
							</div>
							<div class="right aligned column">
								<?php do_action( 'mainwp_comments_actions_bar_right' ); ?>
							</div>
						</div>
					</div>
				</div>
				<div class="ui segment">
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>

					<div id="mainwp-loading-comments-row" style="display: none;">
						<div class="ui active inverted dimmer">
							<div class="ui indeterminate large text loader"><?php _e( 'Loading Comments...', 'mainwp' ); ?></div>
						</div>
					</div>

					<div id="mainwp-comments-table-wrapper">
						<table id="mainwp-comments-table" class="ui top aligned compact table" style="width:100%">
							<thead>
								<tr>
									<th class="no-sort collapsing"><span class="ui checkbox"><input type="checkbox"></span></th>
									<th><?php _e( 'Author', 'mainwp-comments-extension' ); ?></th>
									<th><?php _e( 'Comment', 'mainwp-comments-extension' ); ?></th>
									<th><?php _e( 'Status', 'mainwp-comments-extension' ); ?></th>
									<th><?php _e( 'Site', 'mainwp-comments-extension' ); ?></th>
									<th class="no-sort collapsing"></th>
								</tr>
							</thead>
							<tbody>
								<?php do_action( 'mainwp_cache_echo_body', 'Comment' ); ?>
							</tbody>
							<tfoot>
								<tr>
									<th class="no-sort collapsing"><span class="ui checkbox"><input type="checkbox"></span></th>
									<th><?php _e( 'Author', 'mainwp-comments-extension' ); ?></th>
									<th><?php _e( 'Comment', 'mainwp-comments-extension' ); ?></th>
									<th><?php _e( 'Status', 'mainwp-comments-extension' ); ?></th>
									<th><?php _e( 'Site', 'mainwp-comments-extension' ); ?></th>
									<th class="no-sort collapsing"></th>
								</tr>
							</tfoot>
						</table>
					</div>

					<script type="text/javascript">
						jQuery( document ).ready( function () {
								jQuery('#mainwp-comments-table').DataTable({
									"stateSave": true,
									"stateDuration": 0, // forever
									"scrollX": true,
									"colReorder" : {
										fixedColumnsLeft: 1,
										fixedColumnsRight: 1
									},
									"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
									"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
									  "drawCallback": function( settings ) {
										jQuery( '#mainwp-comments-table-wrapper .ui.checkbox' ).checkbox();
										jQuery( '#mainwp-comments-table-wrapper .ui.dropdown' ).dropdown();
										if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
											mainwp_datatable_fix_menu_overflow();
										}
								  },
								} );

								if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
									mainwp_datatable_fix_menu_overflow();
								}
						} );
					</script>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<div class="mainwp-select-sites">
					<h3 class="ui header"><?php echo __( 'Select Sites', 'mainwp-comments-extension' ); ?></h3>
					<?php do_action( 'mainwp_select_sites_box' ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-options">
					<div class="ui mini form">
						<h3 class="ui header"><?php echo __( 'Select Comment Status', 'mainwp-comments-extension' ); ?></h3>
						<div class="field">
							<select multiple="" class="ui fluid multiple dropdown" id="mainwp_comment_search_type">
								<option value=""><?php _e( 'Select status', 'mainwp' ); ?></option>
								<option value="approve"><?php _e( 'Approved', 'mainwp-comments-extension' ); ?></option>
								<option value="hold"><?php _e( 'Pending', 'mainwp-comments-extension' ); ?></option>
								<option value="spam"><?php _e( 'Spam', 'mainwp-comments-extension' ); ?></option>
								<option value="trash"><?php _e( 'Trash', 'mainwp-comments-extension' ); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-options">
					<div class="ui mini form">
						<h3 class="ui header"><?php echo __( 'Search Options', 'mainwp-comments-extension' ); ?></h3>
						<div class="field">
							<div class="ui input fluid">
								<input type="text" placeholder="<?php esc_attr_e( 'Containing keyword', 'mainwp-comments-extension' ); ?>" id="mainwp_comment_search_by_keyword" class="text" 
								value="<?php echo ( null != $cachedSearch ) ? esc_html( $cachedSearch['keyword'] ) : ''; ?>" />
							</div>
						</div>
						<div class="field">
							<label><?php _e( 'Date range', 'mainwp-comments-extension' ); ?></label>
							<div class="two fields">
								<div class="field ui calendar">
									<div class="ui input left icon">
										<i class="calendar icon"></i>
										<input type="text" placeholder="<?php esc_attr_e( 'Date', 'mainwp-comments-extension' ); ?>" id="mainwp_comment_search_by_dtsstart" value="
																						  <?php
																							if ( $cachedSearch != null ) {
																								echo esc_attr( $cachedSearch['dtsstart'] ); }
																							?>
										"/>
									</div>
								</div>
								<div class="field ui calendar">
									<div class="ui input left icon">
										<i class="calendar icon"></i>
										<input type="text" placeholder="<?php esc_attr_e( 'Date', 'mainwp-comments-extension' ); ?>" id="mainwp_comment_search_by_dtsstop" 
										value="
										<?php
										if ( $cachedSearch != null ) {
											echo esc_attr( $cachedSearch['dtsstop'] );
										}
										?>
										"/>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input type="button" name="mainwp_show_comments" id="mainwp_show_comments" class="ui green big fluid button" value="<?php _e( 'Show Comments', 'mainwp-comments-extension' ); ?>"/>
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<?php if ( isset( $_REQUEST['siteid'] ) && isset( $_REQUEST['postid'] ) ) : ?>
			<script>
				jQuery( document ).ready( function() {
					mainwp_show_comments( <?php echo intval( $_REQUEST['siteid'] ); ?>, <?php echo intval( $_REQUEST['postid'] ); ?> )
				});
			</script>
		<?php endif; ?>
		<?php

	}

	public static function render_table( $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId ) {

		do_action( 'mainwp_cache_init', 'Comment' );

		// Fetch all!
		// Build websites array
		global $mainwpCommentsExtensionActivator;
		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwpCommentsExtensionActivator->get_child_file(), $mainwpCommentsExtensionActivator->get_child_key(), $sites, $groups );

		$output                = new stdClass();
		$output->errors        = array();
		$output->comments      = array();
		$output->commentscount = 0;
		$output->cmids         = '';
		$output->wpids         = '';
		?>
		<div id="mainwp-comments-table-wrapper">
			<table id="mainwp-comments-table" class="ui top aligned compact table" style="width:100%">
				<thead>
					<tr>
						<th class="no-sort collapsing"><span class="ui checkbox"><input type="checkbox" /></span></th>
						<th><?php _e( 'Author', 'mainwp-comments-extension' ); ?></th>
						<th><?php _e( 'Comment', 'mainwp-comments-extension' ); ?></th>
						<th><?php _e( 'Status', 'mainwp-comments-extension' ); ?></th>
						<th><?php _e( 'Site', 'mainwp-comments-extension' ); ?></th>
						<th class="no-sort collapsing"></th>
					</tr>
				</thead>
				<tbody>

		<?php
		if ( count( $dbwebsites ) > 0 ) {

			$maxRecords = apply_filters( 'mainwp_comments_max_comments', 50 );

			$post_data = array(
				'keyword'    => $keyword,
				'dtsstart'   => $dtsstart,
				'dtsstop'    => $dtsstop,
				'status'     => $status,
				'maxRecords' => $maxRecords,
			);

			if ( isset( $postId ) && ( '' != $postId ) ) {
				$post_data['postId'] = $postId;
			}
			add_filter( 'mainwp_response_json_format', '__return_true' ); // going to remove
			do_action( 'mainwp_fetchurlsauthed', $mainwpCommentsExtensionActivator->get_child_file(), $mainwpCommentsExtensionActivator->get_child_key(), $dbwebsites, 'get_all_comments', $post_data, array( self::get_class_name(), 'comments_search_handler' ), $output );
		}

		do_action(
			'mainwp_cache_add_context',
			'Comment',
			array(
				'count'    => $output->commentscount,
				'keyword'  => $keyword,
				'dtsstart' => $dtsstart,
				'dtsstop'  => $dtsstop,
				'status'   => $status,
			)
		);

		// Sort if required
		ob_start();
		if ( $output->commentscount == 0 ) {
			?>
					<tr>
						<td colspan="7"><?php echo __( 'No comments found. Please readjust the search filters and try again.', 'mainwp-comments-extension' ); ?></td>
					</tr>
			<?php
		} else {
			$cmids = rtrim( $output->cmids, ',' );
			$wpids = rtrim( $output->wpids, ',' );
			?>
			<input type="hidden" id="bulk_comment_ids_tmp" name="bulk_comment_ids_tmp" value="<?php echo esc_html( $cmids ); ?>"/>
			<input type="hidden" id="bulk_comment_wpids_tmp" name="bulk_comment_wpids_tmp" value="<?php echo esc_html( $wpids ); ?>"/>
			<?php
		}

		$newOutput = ob_get_clean();
		echo $newOutput;
		?>

				</tbody>
				<tfoot>
					<tr>
						<th class="no-sort collapsing"><span class="ui checkbox"><input type="checkbox" /></span></th>
						<th><?php _e( 'Author', 'mainwp-comments-extension' ); ?></th>
						<th><?php _e( 'Comment', 'mainwp-comments-extension' ); ?></th>
						<th><?php _e( 'Status', 'mainwp-comments-extension' ); ?></th>
						<th><?php _e( 'Site', 'mainwp-comments-extension' ); ?></th>
						<th class="no-sort collapsing"></th>
					</tr>
				</tfoot>
			</table>
			<script type="text/javascript">
				jQuery( document ).ready( function () {
					jQuery('#mainwp-comments-table').DataTable({
						"stateSave": true,
						"stateDuration": 0, // forever
						"scrollX": true,
						"colReorder" : {
							fixedColumnsLeft: 1,
							fixedColumnsRight: 1
						},
						"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
						"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
						"drawCallback": function( settings ) {
							jQuery( '#mainwp-comments-table-wrapper .ui.checkbox' ).checkbox();
							jQuery( '#mainwp-comments-table-wrapper .ui.dropdown' ).dropdown();
							if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
								mainwp_datatable_fix_menu_overflow();
							}
						},
					} );
					if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
						mainwp_datatable_fix_menu_overflow();
					}
				} );
			</script>
		</div>
		<?php

		do_action( 'mainwp_cache_add_body', 'Comment', $newOutput );

	}


	private static function get_status( $status ) {

		if ( 'unapproved' == $status ) {
			$status = 'pending';
		}

		return ucfirst( $status );
	}

	public static function comments_search_handler( $data, $website, &$output ) {

		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {

			$comments = json_decode( base64_decode( $results[1] ), true );
			unset( $results );

			foreach ( $comments as $comment ) {
				if ( isset( $comment['dts'] ) ) {
					if ( ! stristr( $comment['dts'], '-' ) ) {
						$comment['dts'] = MainWP_Comment_Utility::format_timestamp( MainWP_Comment_Utility::get_timestamp( $comment['dts'] ) );
					}
				}

				$output->cmids .= $comment['id'] . ',';
				$output->wpids .= $website->id . ',';
				$output->commentscount++;
				ob_start();
				?>

				<tr class="mainwp-comment-item">
					<td><span class="ui checkbox"><input type="checkbox" name="comment[]" value="1" /></span></td>
					<td>
						<strong><?php echo $comment['author']; ?></strong><br/>
						<?php
						if ( ( ! empty( $comment['author_email'] ) ) && ( '@' !== $comment['author_email'] ) ) {
							echo '<a href="mailto:' . esc_html( $comment['author_email'] ) . '">' . esc_html( $comment['author_email'] ) . '</a>';
						} else {
							echo esc_html( $comment['author_email'] );
						}
						?>
						<br/>
						<em><?php echo $comment['author_url']; ?></em>
					</td>
					<td>
						<?php echo MainWP_Recent_Comments::limit_string( $comment['content'], 100 ); ?><br/>
						<input class="commentId" type="hidden" name="id" value="<?php echo intval( $comment['id'] ); ?>"/>
						<input class="websiteId" type="hidden" name="id" value="<?php echo base64_encode( $website->id ); ?>"/>
						<strong><?php echo __( 'In response to: ', 'mainwp-comments-extension' ); ?></strong><?php echo esc_html( $comment['postName'] ); ?>
					</td>
					<td>
						<strong><?php echo self::get_status( $comment['status'] ); ?></strong><br/>
						<?php echo esc_html( $comment['dts'] ); ?>
					</td>
					<td><a href="<?php echo $website->url; ?>" target="_blank"><?php echo $website->url; ?></a></td>
					<td class="right aligned">
						<div class="ui right pointing dropdown icon mini basic green button" style="z-index:999;">
							<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
							<div class="menu">
								<?php if ( $comment['status'] == 'approved' ) : ?>
								  <a class="item comment_submitunapprove" href="#"><?php _e( 'Unapprove', 'mainwp-comments-extension' ); ?></a>
								<?php endif; ?>

								<?php if ( $comment['status'] == 'unapproved' ) : ?>
								  <a class="item comment_submitapprove" href="#"><?php _e( 'Approve', 'mainwp-comments-extension' ); ?></a>
								<?php endif; ?>

								<?php if ( $comment['status'] != 'trash' && $comment['status'] != 'spam' ) : ?>
								  <a class="item" href="admin.php?page=SiteOpen&websiteid=<?php echo $website->id; ?>&location=<?php echo base64_encode( 'comment.php?action=editcomment&c=' . $comment['id'] ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><?php _e( 'Edit', 'mainwp-comments-extension' ); ?></a>
									<a class="item comment_submitspam" href="#"><?php _e( 'Spam', 'mainwp-comments-extension' ); ?></a>
									<a class="item comment_submitdelete" href="#"><?php _e( 'Trash', 'mainwp-comments-extension' ); ?></a>
								<?php endif; ?>

								<?php if ( $comment['status'] == 'trash' ) : ?>
									<a class="item comment_submitrestore" href="#"><?php _e( 'Restore', 'mainwp-comments-extension' ); ?></a>
									<a class="item comment_submitunspam" href="#"><?php _e( 'Not Spam', 'mainwp-comments-extension' ); ?></a>
								<?php endif; ?>

								<?php if ( ( 'trash' == $comment['status'] ) || ( 'spam' == $comment['status'] ) ) : ?>
									<a class="item comment_submitdelete_perm" href="#"><?php _e( 'Delete permanently', 'mainwp-comments-extension' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</td>
				</tr>
				<?php
				$newOutput = ob_get_clean();
				echo $newOutput;

				do_action( 'mainwp_cache_add_body', 'Comment', $newOutput );
			}
			unset( $comments );
		} else {
			$output->errors[ $website->id ] = apply_filters( 'mainwp_getErrorMessage', 'NOMAINWP', $website->url );
		}
	}

	public function init_ajax() {

		$this->add_action( 'mainwp_comment_unapprove', array( &$this, 'mainwp_comment_unapprove' ) );
		$this->add_action( 'mainwp_comment_approve', array( &$this, 'mainwp_comment_approve' ) );
		$this->add_action( 'mainwp_comment_spam', array( &$this, 'mainwp_comment_spam' ) );
		$this->add_action( 'mainwp_comment_unspam', array( &$this, 'mainwp_comment_unspam' ) );
		$this->add_action( 'mainwp_comment_trash', array( &$this, 'mainwp_comment_trash' ) );
		$this->add_action( 'mainwp_comment_restore', array( &$this, 'mainwp_comment_restore' ) );
		$this->add_action( 'mainwp_comment_delete', array( &$this, 'mainwp_comment_delete' ) );
		$this->add_action( 'mainwp_comments_search', array( &$this, 'mainwp_comments_search' ) );
	}

	protected function add_action( $action, $callback ) {
		add_action( 'wp_ajax_' . $action, $callback );

		$this->add_security_nonce( $action );
	}

	protected function add_security_nonce( $action ) {

		if ( ! is_array( $this->security_nonces ) ) {
			$this->security_nonces = array();
		}

		if ( ! function_exists( 'wp_create_nonce' ) ) {
			include_once ABSPATH . WPINC . '/pluggable.php';
		}

		$this->security_nonces[ $action ] = wp_create_nonce( $action );
	}


	/**
	 * Page: Comments
	 */
	public function mainwp_comments_search() {
		$this->secure_request( 'mainwp_comments_search' );
		self::render_table( $_POST['keyword'], $_POST['dtsstart'], $_POST['dtsstop'], $_POST['status'], ( isset( $_POST['groups'] ) ? $_POST['groups'] : '' ), ( isset( $_POST['sites'] ) ? $_POST['sites'] : '' ), ( isset( $_POST['postId'] ) ? $_POST['postId'] : '' ) );
		die();
	}

	public function mainwp_comment_unapprove() {
		$this->secure_request( 'mainwp_comment_unapprove' );
		MainWP_Recent_Comments::unapprove();
	}

	public function mainwp_comment_approve() {
		$this->secure_request( 'mainwp_comment_approve' );
		MainWP_Recent_Comments::approve();
	}

	public function mainwp_comment_trash() {
		$this->secure_request( 'mainwp_comment_trash' );
		MainWP_Recent_Comments::trash();
	}

	public function mainwp_comment_restore() {
		$this->secure_request( 'mainwp_comment_restore' );
		MainWP_Recent_Comments::restore();
	}

	public function mainwp_comment_spam() {
		$this->secure_request( 'mainwp_comment_spam' );
		MainWP_Recent_Comments::spam();
	}

	public function mainwp_comment_unspam() {
		$this->secure_request( 'mainwp_comment_unspam' );
		MainWP_Recent_Comments::unspam();
	}

	public function mainwp_comment_delete() {
		$this->secure_request( 'mainwp_comment_delete' );
		MainWP_Recent_Comments::delete();
	}

	public function secure_request( $action, $query_arg = 'security', $exit = true ) {
		if ( ! $this->check_security( $action, $query_arg ) ) {
			if ( $exit ) {
				die( json_encode( array( 'error' => __( 'Invalid Request.', 'mainwp-comments-extension' ) ) ) );
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Method check_security()
	 *
	 * Check security request.
	 *
	 * @param string $action Action to perform.
	 * @param string $query_arg Query argument.
	 *
	 * @return bool true or false
	 */
	public function check_security( $action = - 1, $query_arg = 'security' ) {
		$secure = true;
		if ( - 1 === $action ) {
			$secure = false;
		} else {
			$adminurl = strtolower( admin_url() );
			$referer  = strtolower( wp_get_referer() );
			$result   = isset( $_REQUEST[ $query_arg ] ) ? wp_verify_nonce( sanitize_key( $_REQUEST[ $query_arg ] ), $action ) : false;
			if ( ! $result && 0 !== strpos( $referer, $adminurl ) ) {
				$secure = false;
			}
		}

		if ( ! $secure ) {
			return false;
		}
		return true;
	}

}

?>
