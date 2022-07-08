<?php

class MainWP_Recent_Comments {

	public static function get_class_name() {
		return __CLASS__;
	}

	public static function test() {

	}

	public static function get_name() {
		$html = '';

		return $html;
	}

	public static function render() {
		self::render_sites( false, false );
	}

	public static function render_sites( $renew, $pExit = true ) {

		global $mainwpCommentsExtensionActivator;
		$websites = apply_filters( 'mainwp_getdashboardsites', $mainwpCommentsExtensionActivator->get_child_file(), $mainwpCommentsExtensionActivator->get_child_key() );

		$allComments = array();

		if ( $websites ) {
			while ( $websites && ( $website = @MainWP_Comment_DB::fetch_object( $websites ) ) ) {
				if ( $website->recent_comments == '' ) {
					continue; }
				$comments = json_decode( $website->recent_comments, 1 );
				if ( count( $comments ) == 0 ) {
					continue; }
				foreach ( $comments as $comment ) {
					$comment['website'] = (object) array(
						'id'   => $website->id,
						'url'  => $website->url,
						'name' => $website->name,
					);
					$allComments[]      = $comment;
				}
			}
			@MainWP_Comment_DB::free_result( $websites );
		}

		$recent_comments_approved = MainWP_Comment_Utility::get_sub_array_having( $allComments, 'status', 'approved' );
		$recent_comments_approved = MainWP_Comment_Utility::sortmulti( $recent_comments_approved, 'dts', 'desc' );
		$recent_comments_pending  = MainWP_Comment_Utility::get_sub_array_having( $allComments, 'status', 'unapproved' );
		$recent_comments_pending  = MainWP_Comment_Utility::sortmulti( $recent_comments_pending, 'dts', 'desc' );

		$individual = false;

		if ( isset( $_GET['dashboard'] ) && '' != $_GET['dashboard'] ) {
			$individual = true;
		}

		?>

		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag"><?php echo __( 'Recent Comments', 'mainwp-comments-extension' ); ?>
					<div class="sub header"><?php echo __( 'The most recent comments from your websites', 'mainwp-comments-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned">
				<div class="ui dropdown top pointing mainwp-dropdown-tab">
						<div class="text"><?php echo __( 'Approved', 'mainwp-comments-extension' ); ?></div>
						<i class="dropdown icon"></i>
						<div class="menu">
						<a class="item recent_comments_approved_lnk" data-tab="comm-approved" data-value="comm-approved" href="#"><?php echo __( 'Approved', 'mainwp-comments-extension' ); ?></a>
						<a class="item recent_comments_pending_lnk" data-tab="comm-pending" data-value="comm-pending" href="#"><?php echo __( 'Pending', 'mainwp-comments-extension' ); ?></a>
						</div>
				</div>
			</div>
		</div>
		<div class="ui section hidden divider"></div>

		<!-- Published List -->
		<div class="recent_comments_approved ui tab active" data-tab="comm-approved">
		<?php
		if ( count( $recent_comments_approved ) == 0 ) {
			?>
			<h2 class="ui icon header">
				<i class="folder open outline icon"></i>
				<div class="content">
					<?php _e( 'No approved comments found!', 'mainwp-comments-extension' ); ?>
				</div>
			</h2>
			<?php
		}
		?>
			<div class="ui middle aligned divided selection list">
				<?php
				for ( $i = 0; $i < count( $recent_comments_approved ) && $i < 5; $i ++ ) {
					if ( isset( $recent_comments_approved[ $i ]['dts'] ) ) {
						if ( ! stristr( $recent_comments_approved[ $i ]['dts'], '-' ) ) {
							$recent_comments_approved[ $i ]['dts'] = MainWP_Comment_Utility::format_timestamp( MainWP_Comment_Utility::get_timestamp( $recent_comments_approved[ $i ]['dts'] ) );
						}
					}
					?>
					<div class="item mainwp-comment-item">
						<div class="ui grid">
							<input class="commentId" type="hidden" name="id" value="<?php echo $recent_comments_approved[ $i ]['id']; ?>"/>
							<input class="websiteId" type="hidden" name="id" value="<?php echo base64_encode( $recent_comments_approved[ $i ]['website']->id ); ?>"/>
							<div class="<?php echo $individual ? 'ten' : 'six'; ?> wide middle aligned column">
								<?php echo $recent_comments_approved[ $i ]['author']; ?> on <a href="<?php echo $recent_comments_approved[ $i ]['website']->url; ?>?p=<?php echo $recent_comments_approved[ $i ]['postId']; ?>" target="_blank"><?php echo $recent_comments_approved[ $i ]['postName']; ?></a>
							</div>
							<div class="four wide middle aligned column">
								<?php echo esc_html( $recent_comments_approved[ $i ]['dts'] ); ?>
							</div>
							<?php if ( ! $individual ) : ?>
							<div class="four wide middle aligned column">
								<a href="admin.php?page=managesites&dashboard=<?php echo $recent_comments_approved[ $i ]['website']->id; ?>"><?php echo wp_strip_all_tags( $recent_comments_approved[ $i ]['website']->name ); ?></a>
							</div>
							<?php endif; ?>
							<div class="two wide column right aligned middle aligned">
								<div class="ui left pointing dropdown icon mini basic green button" style="z-index: 999;">
									<i class="ellipsis horizontal icon"></i>
										<div class="menu">
											<a class="item comment_submitunapprove" href="#"><?php _e( 'Unapprove', 'mainwp-comments-extension' ); ?></a>
											<a class="item" href="admin.php?page=SiteOpen&websiteid=<?php echo $recent_comments_approved[ $i ]['website']->id; ?>&location=<?php echo base64_encode( 'comment.php?action=editcomment&c=' . $recent_comments_approved[ $i ]['id'] ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><?php _e( 'Edit', 'mainwp-comments-extension' ); ?></a>
											<a class="item comment_submitdelete" href="#"><?php _e( 'Trash', 'mainwp-comments-extension' ); ?></a>
											<a class="item comment_submitspam" href="#"><?php _e( 'Spam', 'mainwp-comments-extension' ); ?></a>
											<a class="item" href="admin.php?page=Extensions-Mainwp-Comments-Extension"><?php _e( 'View all', 'mainwp-comments-extension' ); ?></a>
										</div>
								</div>
							</div>
						</div>
						<div class="mainwp-row-actions-working"><i class="notched circle loading icon"></i><?php _e( 'Please wait...', 'mainwp' ); ?></div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<!-- END Published List -->

		<!-- Published List -->
		<div class="recent_comments_pending ui tab" data-tab="comm-pending">
		<?php
		if ( count( $recent_comments_pending ) == 0 ) {
			?>
			<h2 class="ui icon header">
				<i class="folder open outline icon"></i>
				<div class="content">
					<?php _e( 'No pending comments found!', 'mainwp-comments-extension' ); ?>
				</div>
			</h2>
			<?php
		}
		?>
			<div class="ui middle aligned divided selection list">
				<?php
				for ( $i = 0; $i < count( $recent_comments_pending ) && $i < 5; $i ++ ) {
					if ( isset( $recent_comments_pending[ $i ]['dts'] ) ) {
						if ( ! stristr( $recent_comments_pending[ $i ]['dts'], '-' ) ) {
							$recent_comments_pending[ $i ]['dts'] = MainWP_Comment_Utility::format_timestamp( MainWP_Comment_Utility::get_timestamp( $recent_comments_pending[ $i ]['dts'] ) );
						}
					}
					?>
					<div class="item mainwp-comment-item">
						<div class="ui grid">
							<input class="commentId" type="hidden" name="id" value="<?php echo $recent_comments_pending[ $i ]['id']; ?>"/>
							<input class="websiteId" type="hidden" name="id" value="<?php echo base64_encode( $recent_comments_pending[ $i ]['website']->id ); ?>"/>
							<div class="<?php echo $individual ? 'ten' : 'six'; ?> wide middle aligned column">
								<?php echo $recent_comments_pending[ $i ]['author']; ?> on <a href="<?php echo $recent_comments_pending[ $i ]['website']->url; ?>?p=<?php echo $recent_comments_pending[ $i ]['postId']; ?>" target="_blank"><?php echo $recent_comments_pending[ $i ]['postName']; ?></a>
							</div>
							<div class="four wide middle aligned column">
								<?php echo esc_html( $recent_comments_pending[ $i ]['dts'] ); ?>
							</div>
							<?php if ( ! $individual ) : ?>
							<div class="four wide middle aligned column">
								<a href="admin.php?page=managesites&dashboard=<?php echo $recent_comments_pending[ $i ]['website']->id; ?>"><?php echo wp_strip_all_tags( $recent_comments_approved[ $i ]['website']->name ); ?></a>
							</div>
							<?php endif; ?>
							<div class="two wide column right aligned middle aligned">
								<div class="ui left pointing dropdown icon mini basic green button" style="z-index: 999;">
									<i class="ellipsis horizontal icon"></i>
										<div class="menu">
											<a class="item comment_submitapprove" href="#"><?php _e( 'Approve', 'mainwp-comments-extension' ); ?></a>
											<a class="item" href="admin.php?page=SiteOpen&websiteid=<?php echo $recent_comments_pending[ $i ]['website']->id; ?>&location=<?php echo base64_encode( 'comment.php?action=editcomment&c=' . $recent_comments_pending[ $i ]['id'] ); ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><?php _e( 'Edit', 'mainwp-comments-extension' ); ?></a>
											<a class="item comment_submitdelete" href="#"><?php _e( 'Trash', 'mainwp-comments-extension' ); ?></a>
											<a class="item comment_submitspam" href="#" class=""><?php _e( 'Spam', 'mainwp-comments-extension' ); ?></a>
											<a class="item" href="admin.php?page=Extensions-Mainwp-Comments-Extension" class=""><?php _e( 'View all', 'mainwp-comments-extension' ); ?></a>
										</div>
								</div>
							</div>
						</div>
						<div class="mainwp-row-actions-working"><i class="notched circle loading icon"></i><?php _e( 'Please wait...', 'mainwp' ); ?></div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<!-- END Published List -->

		<?php
		if ( true == $pExit ) {
			exit();
		}
	}

	public static function limit_string( $pInput, $pMax = 500 ) {
		$pMax   = apply_filters( 'mainwp_comments_widget_limit_string', $pMax );
		$output = strip_tags( $pInput );
		if ( strlen( $output ) > $pMax ) {
			// truncate string
			$outputCut = substr( $output, 0, $pMax );
			// make sure it ends in a word so assassinate doesn't become ass...
			$output = substr( $outputCut, 0, strrpos( $outputCut, ' ' ) ) . '...';
		}
		echo esc_html( $output );
	}

	public static function approve() {
		self::action( 'approve' );
		die( json_encode( array( 'result' => __( 'Comment has been approved', 'mainwp-comments-extension' ) ) ) );
	}

	public static function unapprove() {
		self::action( 'unapprove' );
		die( json_encode( array( 'result' => __( 'Comment has been unapproved', 'mainwp-comments-extension' ) ) ) );
	}

	public static function spam() {
		self::action( 'spam' );
		die( json_encode( array( 'result' => __( 'Comment has been marked as spam', 'mainwp-comments-extension' ) ) ) );
	}

	public static function unspam() {
		self::action( 'unspam' );
		die( json_encode( array( 'result' => __( 'Comment is no longer marked as spam', 'mainwp-comments-extension' ) ) ) );
	}

	public static function trash() {
		self::action( 'trash' );
		die( json_encode( array( 'result' => __( 'Comment has been moved to trash', 'mainwp-comments-extension' ) ) ) );
	}

	public static function restore() {
		self::action( 'restore' );
		die( json_encode( array( 'result' => __( 'Comment has been restored', 'mainwp-comments-extension' ) ) ) );
	}

	public static function delete() {
		self::action( 'delete' );
		die( json_encode( array( 'result' => __( 'Comment has been permanently deleted', 'mainwp-comments-extension' ) ) ) );
	}

	protected static function action( $pAction ) {

		if ( isset( $_POST['comment_ids'] ) && $_POST['comment_ids'] ) {
			self::bulk_action( $pAction );
			return;
		}

		$commentId    = $_POST['commentId'];
		$websiteIdEnc = $_POST['websiteId'];

		if ( ! MainWP_Comment_Utility::ctype_digit( $commentId ) ) {
			die( json_encode( array( 'error' => __( 'Invalid Request.', 'mainwp-comments-extension' ) ) ) );
		}

		$websiteId = base64_decode( $websiteIdEnc );

		if ( ! MainWP_Comment_Utility::ctype_digit( $websiteId ) ) {
			die( json_encode( array( 'error' => __( 'Invalid Request.', 'mainwp-comments-extension' ) ) ) );
		}

		global $mainwpCommentsExtensionActivator;

		$information = apply_filters(
			'mainwp_fetchurlauthed',
			$mainwpCommentsExtensionActivator->get_child_file(),
			$mainwpCommentsExtensionActivator->get_child_key(),
			$websiteId,
			'comment_action',
			array(
				'action' => $pAction,
				'id'     => $commentId,
			)
		);

		if ( is_array( $information ) && isset( $information['error'] ) ) {
			die( json_encode( $information ) );
		}

		if ( ! isset( $information['status'] ) || ( 'SUCCESS' != $information['status'] ) ) {
			die( json_encode( array( 'error' => __( 'Undefined error occurred. Please, try again.', 'mainwp-comments-extension' ) ) ) );
		}
	}

	public static function action_message( $act ) {
		$mess = '';

		switch ( $act ) {
			case 'approve':
				$mess = __( 'Comment(s) approved', 'mainwp-comments-extension' );
				break;
			case 'unapprove':
				$mess = __( 'Comment(s) unapproved', 'mainwp-comments-extension' );
				break;
			case 'spam':
				$mess = __( 'Comment(s) marked as spam', 'mainwp-comments-extension' );
				break;
			case 'unspam':
				$mess = __( 'Comment(s) no longer marked as spam', 'mainwp-comments-extension' );
				break;
			case 'trash':
				$mess = __( 'Comment(s) moved to trash', 'mainwp-comments-extension' );
				break;
			case 'restore':
				$mess = __( 'Comment(s) restored', 'mainwp-comments-extension' );
				break;
			case 'delete':
				$mess = __( 'Comment(s) permanently deleted', 'mainwp-comments-extension' );
				break;
		}

		return $mess;
	}

	protected static function bulk_action( $pAction ) {

		$commentIds = explode( ',', $_POST['comment_ids'] );
		$websiteIds = explode( ',', $_POST['comment_wpids'] );
		$websites   = array();

		for ( $i = 0; $i < count( $websiteIds ); $i++ ) {
			$websites[ $websiteIds[ $i ] ]['_commentids'] .= $commentIds[ $i ] . ',';
		}

		$success = 0;
		if ( count( $websites ) > 0 ) {
			foreach ( $websites as $wpid => $web ) {
				$comm_ids = rtrim( $web['_commentids'], ',' );

				if ( ! MainWP_Comment_Utility::ctype_digit( $wpid ) ) {
					continue;
				}

				global $mainwpCommentsExtensionActivator;

				$information = apply_filters(
					'mainwp_fetchurlauthed',
					$mainwpCommentsExtensionActivator->get_child_file(),
					$mainwpCommentsExtensionActivator->get_child_key(),
					$wpid,
					'comment_bulk_action',
					array(
						'action' => $pAction,
						'ids'    => $comm_ids,
					)
				);

				$success += intval( isset( $information['success'] ) ? $information['success'] : 0 );
			}
		}
		$ret['message'] = $success . ' ' . self::action_message( $pAction );
		// die right here to return json
		die( json_encode( $ret ) );
	}
}
