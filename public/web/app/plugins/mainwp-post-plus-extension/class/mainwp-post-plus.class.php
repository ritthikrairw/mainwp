<?php
class MainWP_Post_Plus {
	private static $instance = null;

	static function get_instance() {
		if ( null === MainWP_Post_Plus::$instance ) {
			MainWP_Post_Plus::$instance = new MainWP_Post_Plus();
		}
		return MainWP_Post_Plus::$instance;
	}

	function __construct() {

	}

	public  function admin_init() {
		add_action( 'wp_ajax_mainwp_pplus_delete_post', array( $this, 'delete_post' ) );
		add_action( 'wp_ajax_mainwp_pplus_publish_post', array( $this, 'posting' ) );

        add_action( 'mainwp_bulkpost_edit', array( &$this, 'postplus_metabox' ), 10, 2 );

		add_filter( 'post_updated_messages', array( &$this, 'post_updated_messages' ) );
		add_action( 'mainwp_bulkpost_categories_handle', array( $this, 'postplus_categories_handle' ), 10, 2 );// seem not used ?
		add_action( 'mainwp_bulkpost_tags_handle', array( $this, 'postplus_tags_handle' ), 10, 3 );
		add_filter( 'mainwp_bulkpost_saved_categories', array( &$this, 'postplus_saved_categories' ), 10, 2 );

        add_action( 'mainwp_save_bulkpost', array( &$this, 'postplus_metabox_handle' ) );
        add_action( 'mainwp_save_bulkpage', array( &$this, 'postplus_metabox_handle' ) );
        add_filter( 'mainwp_posting_bulkpost_post_status', array( &$this, 'posting_post_status' ), 10, 2 );

        add_filter( 'mainwp_after_posting_bulkpost_result', array( &$this, 'after_posting_bulk' ), 10, 4 );
        add_filter( 'mainwp_after_posting_bulkpage_result', array( &$this, 'after_posting_bulk' ), 10, 4 );

        add_action( 'mainwp_edit_posts_after_submit_button', array( &$this, 'posts_after_submit_button' ) );

	}

	function get_sub_pages_post( $pArray ) {
		$pArray[] = array(
			'slug' 			=> 'Draft',
			'title' 		=> 'Drafts',
			'callback'  => array( &$this, 'render_draft_post' )
		);
		return $pArray;
	}

	function get_sub_pages_page( $pArray ) {
		$pArray[] = array(
			'slug' 			=> 'Draft',
			'title' 		=> 'Drafts',
			'callback'  => array( &$this, 'render_draft_page' )
		);
		return $pArray;
	}

	public function delete_post() {
		$post_id = isset( $_POST['postId'] ) ? $_POST['postId'] : 0;
		if ( ! empty( $post_id ) && wp_delete_post( $post_id ) ) {
			die( 'success' );
		}
		die( 'failed' );
	}

	function post_updated_messages( $messages ) {
		$messages['post'][96] = __( 'Draft updated successfully!', 'mainwp-post-plus-extension' );
		return $messages;
	}

	public function postplus_categories_handle( $post_id, $categories ) {
		if ( $post_id ) {
			update_post_meta( $post_id, '_saved_draft_categories', base64_encode( implode( ', ', $categories ) ) );
		}
	}

	public function postplus_tags_handle( $post_id, $post_type, $tags ) {
		$post = get_post( $post_id );
		if ( ! empty( $post ) && $post->post_type == $post_type ) {
			update_post_meta( $post_id, '_saved_draft_tags', base64_encode( $tags ) );
		}
	}

	public function postplus_metabox( $post, $post_type  ) {
		require_once MAINWP_POST_PLUS_PLUGIN_DIR . '/includes/pplus-metabox.php';
	}

    public function check_boilerplate_saving( $post_id ) {
        if ( ! isset( $_POST['mainwp_boilerplate_nonce'] ) || ! wp_verify_nonce( $_POST['mainwp_boilerplate_nonce'], 'boilerplate_' . $post_id ) ) {
			return false;
		}
        return true;
    }

    function posts_after_submit_button() {
        // this button will support saving posts/pages to sites as draft posts/pages
        ?>
            <div class="mainwp-search-submit">
                <input type="submit" name="postplus_save_as_draft" id="publish" class="ui big fluid button" value="<?php esc_attr_e( 'Save as Draft', 'mainwp' ); ?>">
            </div>
        <?php
    }

	public function postplus_metabox_handle( $post_id ) {

		if ( ! isset( $_POST['mainwp_pplus_metabox_submit'] ) ) {
			return;
		}

		$post = get_post( $post_id );

		update_post_meta( $post_id, '_mainwp_post_plus', 'yes' ); // this meta will use to process on child

		$privelege = isset( $_POST['pplus_meta_privelege'] ) && is_array( $_POST['pplus_meta_privelege'] ) ? $_POST['pplus_meta_privelege'] : '';

		update_post_meta( $post_id, '_saved_draft_random_privelege', base64_encode( serialize( $privelege ) ) );

		if ( $post->post_type != 'bulkpage' ) {
			$cat = isset( $_POST['pplus_meta_random_category'] ) ? 1 : '';
			update_post_meta( $post_id, '_saved_draft_random_category', $cat );
		}

		$publish_date = isset( $_POST['pplus_meta_random_publish_date'] ) ? 1 : '';
		update_post_meta( $post_id, '_saved_draft_random_publish_date', $publish_date );

		$date_from = $date_to = '';

		if ( ! empty( $publish_date ) ) {
			$date_from = isset( $_POST['pplus_random_date_from'] ) ? trim( $_POST['pplus_random_date_from'] ) : 0;
			if ( ! empty( $date_from ) ) {
				$date_from = strtotime( $date_from );
			}
			$date_to = isset( $_POST['pplus_random_date_to'] ) ? trim( $_POST['pplus_random_date_to'] ) : 0;
			if ( ! empty( $date_to ) ) {
				$date_to = strtotime( $date_to );
				$date_to += 24 * 3600 - 1;
			}
		}

		update_post_meta( $post_id, '_saved_draft_publish_date_from', $date_from );
		update_post_meta( $post_id, '_saved_draft_publish_date_to', $date_to );

        if ( $post->post_status == 'draft' ) {
             if ( ! $this->check_boilerplate_saving( $post_id ) ) {
                update_post_meta( $post_id, '_saved_as_draft', 'yes' );
             }
		}

        $draft = '';
        if (isset($_POST['postplus_save_as_draft'])) {
            $draft = 'draft';
        }

        update_post_meta( $post_id, '_postplus_edit_post_status', $draft );
	}

    public function posting_post_status($post_status, $post_id){
        $draft_status		 = get_post_meta( $post_id, '_postplus_edit_post_status', true );
        if ($draft_status == 'draft')
            $post_status = 'draft';

        return $post_status;
    }

    public function after_posting_bulk( $input, $post, $dbwebsites, $output) {

        if ($post) {

            $saved_draft = get_post_meta( $post->ID, '_saved_as_draft', true );
            if ( $saved_draft == 'yes' ) {

                $failed_posts = array();
                foreach ( $dbwebsites as $website ) {
                    if ( isset($output->ok[ $website->id ]) && ( $output->ok[ $website->id ] == 1 ) && ( isset( $output->added_id[ $website->id ] ) ) ) {
                        // ok, success
                    } else {
                        $failed_posts[] = $website->id;
                    }
                }
                $del_post	 = true;
                if ( count( $failed_posts ) > 0 ) {
                    $del_post = false;
                    update_post_meta( $post->ID, '_selected_sites', base64_encode( serialize( $failed_posts ) ) );
                    update_post_meta( $post->ID, '_selected_groups', '' );
                    wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) );
                }

                if ( ! $del_post )
                    update_post_meta( $post->ID, '_bulkpost_do_not_del', 'yes' ); // this will prevent delete the post after about posing
            }

        }

        return $input; // return false here
    }

	public function postplus_saved_categories( $post, $categories = array() ) {
		if ( ! empty( $post ) ) {
			$cats = get_post_meta( $post->ID, '_saved_draft_categories', true );
			$cats = base64_decode( $cats );
			$cats = explode( ', ', $cats );
			if ( ! empty( $cats ) ) {
				$categories = array_merge( $categories, $cats );
			}
		}
		return $categories;
	}

	public function posting() {
		$post_id = isset( $_POST['postId'] ) ? $_POST['postId'] : 0;
		$error = '';
		if ( $post_id ) {
			try {
				$result = $this->draft_posting( $post_id );
				if ( ! empty( $result ) ) {
					die( json_encode( $result ) );
				}
			} catch ( Exception $ex ) {
				$error = $ex->getMessage();
			}
		}

		if ( ! empty( $error ) ) {
			die( json_encode( array( 'error' => $error ) ) );
		}

		die();
	}

    public static function posting_bulk_handler( $data, $website, &$output ) {
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
				$output->errors[ $website->id ] = $information['error'];
			} else {
				$output->errors[ $website->id ] = __( 'Undefined error occurred. Please, try again. If the issue continues, please contact the MainWP support.', 'mainwp-post-plus-extension' );
			}
		} else {
			$output->errors[ $website->id ] = apply_filters( 'mainwp_getErrorMessage', 'NOMAINWP', $website->url );;
		}
	}


	public function draft_posting( $id ) {
		$post = get_post( $id );
		if ( $post ) {
			$selected_by = get_post_meta( $id, '_selected_by', true );
			$selected_sites = unserialize( base64_decode( get_post_meta( $id, '_selected_sites', true ) ) );
			$selected_groups = unserialize( base64_decode( get_post_meta( $id, '_selected_groups', true ) ) );

			if ( empty( $selected_by ) || ( empty( $selected_sites ) && empty( $selected_groups ) ) ) {
				throw new Exception( __( 'No websites selected. Please select at least one child site or group.' ) );
				return;
			}

			$post_category = base64_decode( get_post_meta( $id, '_categories', true ) );

			$post_tags = base64_decode( get_post_meta( $id, '_tags', true ) );
			$post_slug = base64_decode( get_post_meta( $id, '_slug', true ) );
			$post_custom = get_post_custom( $id );

      $galleries = get_post_gallery( $id, false );
      $post_gallery_images = array();

      if ( is_array( $galleries ) && isset( $galleries['ids'] ) ) {
        $attached_images = explode( ',', $galleries['ids'] );
        foreach( $attached_images as $attachment_id ) {
          $attachment = get_post( $attachment_id );
          if ( $attachment ) {
            $post_gallery_images[] = array(
              'id' 					=> $attachment_id,
              'alt' 				=> get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
              'caption' 		=> $attachment->post_excerpt,
              'description' => $attachment->post_content,
              'src' 				=> $attachment->guid,
              'title' 			=> $attachment->post_title
            );
          }
        }
      }

			include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'post-thumbnail-template.php' );

			$post_featured_image = get_post_thumbnail_id( $id );
			$mainwp_upload_dir = wp_upload_dir();

			$new_post = array(
				'post_title' 			=> $post->post_title,
				'post_content' 		=> $post->post_content,
				'post_status' 		=> 'publish',
				'post_date' 			=> $post->post_date,
				'post_date_gmt'	  => $post->post_date_gmt,
				'post_tags' 			=> $post_tags,
				'post_name' 			=> $post_slug,
				'post_excerpt' 		=> $post->post_excerpt,
				'comment_status'  => $post->comment_status,
				'ping_status'		  => $post->ping_status,
				'id_spin' 				=> $post->ID,
			);

			if ( $post->post_type == 'bulkpage' ) {
				$new_post['post_type'] = 'page';
				unset( $new_post['post_tags'] );
			}

			if ( null != $post_featured_image ) { //Featured image is set, retrieve URL
				$img = wp_get_attachment_image_src( $post_featured_image, 'full' );
				$post_featured_image = $img[0];
			}

			global $mainWPPostPlusExtensionActivator;
			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPPostPlusExtensionActivator->get_child_file(), $mainWPPostPlusExtensionActivator->get_child_key(), $selected_sites, $selected_groups );

			$output = new stdClass();
			$output->ok = array();
			$output->errors = array();
			$output->link = array();
			$output->added_id = array();

			if ( count( $dbwebsites ) > 0 ) {
        $post_data = array(
					'new_post' 						=> base64_encode( serialize( $new_post ) ),
					'post_custom' 				=> base64_encode( serialize( $post_custom ) ),
					'post_category' 			=> base64_encode( $post_category ),
					'post_featured_image' => base64_encode( $post_featured_image ),
                    'post_gallery_images' => base64_encode( serialize( $post_gallery_images ) ),
					'mainwp_upload_dir' 	=> base64_encode( serialize( $mainwp_upload_dir ) ),
				);

				if ( $post->post_type == 'bulkpage' ) {
					unset( $post_data['post_category'] );
				}

				do_action( 'mainwp_fetchurlsauthed', $mainWPPostPlusExtensionActivator->get_child_file(), $mainWPPostPlusExtensionActivator->get_child_key(), $dbwebsites, 'newpost', $post_data, array( 'MainWP_Post_Plus', 'posting_bulk_handler' ), $output );
			}

			$failed_posts = array();

			foreach ( $dbwebsites as $website ) {
				if ( isset( $output->added_id[ $website->id ] ) && $output->ok[ $website->id ] == 1 ) {
					do_action( 'mainwp_bulkposting_done', $post, $website, $output );
				} else {
					$failed_posts[] = $website->id;
				}
			}

			if ( count( $failed_posts ) == 0 ) {
				wp_delete_post( $id, true );
			} else {
				update_post_meta( $post->ID, '_selected_sites', base64_encode( serialize( $failed_posts ) ) );
				update_post_meta( $post->ID, '_selected_groups', '' );
			}

			$result = '';
			$result .= '<div class="ui list">';
			foreach ( $dbwebsites as $website ) {
				$result .= '<div class="item">';
				$result .= ( isset( $output->ok[ $website->id ] ) && $output->ok[ $website->id ] == 1 ? '<i class="green check icon"></i> ' . $website->name . ' - ' . __( 'New post created successfully!', 'mainwp-post-plus-extension' ) : '<i class="red times icon"></i> ' . $website->name . ' - ' . $output->errors[ $website->id ] ) ;
				$result .= '</div>';
			}
			$result .= '</div>';

			return array( 'result' => $result, 'failed_posts' => count( $failed_posts ) );
		}
		return false;
	}

	public function render_draft_post() {
		do_action( 'mainwp_pageheader_post', 'Draft' );
		echo self::render_drafts_list( 'bulkpost' );
		do_action( 'mainwp_pagefooter_post', 'Draft' );
	}

	public function render_draft_page() {
		do_action( 'mainwp_pageheader_page', 'Draft' );
		echo self::render_drafts_list( 'bulkpage' );
		do_action( 'mainwp_pagefooter_page', 'Draft' );
	}

	public static function render_drafts_list( $post_type ) {
		if ( 'bulkpage' != $post_type && 'bulkpost' != $post_type ) {
			return;
		}

		$add_new_link = 'PostBulkAdd';
		if ( 'bulkpage' == $post_type ) {
			$add_new_link = 'PageBulkAdd';
		}

		$args = array(
			'post_type' 		 => $post_type,
			'post_status' 	 => array( 'draft' ),
			'post_parent'	   => null,
			'orderby'		  	 => 'post_modified',
			'order' 				 => 'desc',
			'posts_per_page' => -1,
			'meta_key'   		 => '_saved_as_draft',
			'meta_value' 		 => 'yes',
		);

		$draft_posts = get_posts( $args );
		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid">
				<div class="ui two column row">
					<div class="column">
						<select class="ui dropdown" id="mainwp-post-plus-bulk-actions">
							<option value="none"><?php _e( 'Bulk Actions', 'mainwp-post-plus-extension' ); ?></option>
							<option value="publish-selected"><?php _e( 'Publish', 'mainwp-post-plus-extension' ); ?></option>
							<option value="preview-selected"><?php _e( 'Preview', 'mainwp-post-plus-extension' ); ?></option>
							<option value="delete-selected"><?php _e( 'Delete', 'mainwp-post-plus-extension' ); ?></option>
						</select>
						<input type="button" name="" id="mainwp-post-plus-bulk-actions-button" class="ui basic button" value="<?php _e( 'Apply', 'mainwp-post-plus-extension' ); ?>"/>
						<?php do_action( 'mainwp_post_plus_actions_bar_left' ); ?>
					</div>
					<div class="right aligned column">
						<?php do_action( 'mainwp_post_plus_actions_bar_right' ); ?>
					</div>
				</div>
			</div>
		</div>
		<div class="ui segment" id="mainwp-post-plug-drafts">
			<table class="ui single line table" id="mainwp-post-plug-drafts-table" style="width:100%">
				<thead>
					<tr>
						<th class="collapsing no-sort check-column"><span class="ui checkbox"><input type="checkbox"/></span></th>
						<th><?php _e( 'Title', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Author', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Categories', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Tags', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Last Edit', 'mainwp-post-plus-extension' ); ?></th>
						<th class="collapsing no-sort"></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( is_array( $draft_posts ) && count( $draft_posts ) > 0 ) : ?>
						<?php self::render_drafts_list_table_rows( $draft_posts, $post_type ); ?>
					<?php endif; ?>
				</tbody>
				<tfoot class="full-width">
					<tr>
						<th colspan="7"><a href="admin.php?page=<?php echo $add_new_link; ?>" class="ui mini green button"><?php _e( 'Create New', 'mainwp-post-plus-extension' ); ?></a></th>
					</tr>
				</tfoot>
			</table>
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				jQuery( '#mainwp-post-plug-drafts-table' ).DataTable( {
					"stateSave": true,
					"stateDuration": 0, // forever
					"scrollX": true,
					"colReorder" : {
						fixedColumnsLeft: 1,
						fixedColumnsRight: 1
					},
					"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
					"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
					"order": [ [ 1, "asc" ] ],
					"language": { "emptyTable": "No saved drafts found." },
					"drawCallback": function( settings ) {
						jQuery('#mainwp-post-plug-drafts-table .ui.checkbox').checkbox();
						jQuery( '#mainwp-post-plug-drafts-table .ui.dropdown').dropdown();
						if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
							mainwp_datatable_fix_menu_overflow();
						}
					},
				} );
				if ( typeof mainwp_datatable_fix_menu_overflow != 'undefined' ) {
					mainwp_datatable_fix_menu_overflow();
				}
			});

			</script>
		</div>
    <?php
	}

	public static function render_drafts_list_table_rows( $draft_posts, $post_type ) {

		$edit_link_slug = 'PostBulkEdit';
		if ( 'bulkpage' == $post_type ) {
			$edit_link_slug = 'PostBulkEdit';
		}

		foreach ( $draft_posts as $post ) {
			$author_info = get_userdata( $post->post_author );
			?>
			<tr id="post-<?php echo $post->ID; ?>" post-id="<?php echo $post->ID; ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" value="1" name="post[]"></span></td>
				<td><a href="?page=<?php echo $edit_link_slug; ?>&post_id=<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></a></td>
				<td><?php echo $author_info->user_login; ?></td>
				<td><?php echo self::column_categories( $post, 'categories' ); ?></td>
				<td><?php echo self::column_categories( $post, 'tags' ); ?></td>
				<td><?php echo self::column_date( $post ); ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a href="?page=<?php echo $edit_link_slug; ?>&post_id=<?php echo $post->ID; ?>" class="item"><?php _e( 'Edit' ); ?></a>
	 					  <a href="#" class="item mainwp-post-plus-publish-action"><?php _e( 'Publish' ); ?></a>
	 					  <a href="<?php echo get_permalink( $post->ID ) . '&preview=true'; ?>" class="item mainwp-post-plus-preview-action" target="_blank" ><?php _e( 'Preview' ); ?></a>
	 					  <a href="#" class="item mainwp-post-plus-delete-action" ><?php _e( 'Delete' ); ?></a>
						</div>
					</div>
				</td>
			</tr>
    <?php
		}
	}

	public static function render_all_drafts_list() {
		$args = array(
			'post_type' 			=> array( 'bulkpage', 'bulkpost' ),
			'post_status' 		=> array( 'draft' ),
			'post_parent' 		=> null,
			'orderby' 				=> 'post_modified',
			'order' 					=> 'desc',
			'posts_per_page' 	=> -1,
			'meta_key'   			=> '_saved_as_draft',
			'meta_value' 			=> 'yes',
		);

		$draft_posts = get_posts( $args );
		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid">
				<div class="ui two column row">
					<div class="column">
						<select class="ui dropdown" id="mainwp-post-plus-bulk-actions">
							<option value="none"><?php _e( 'Bulk Actions', 'mainwp-post-plus-extension' ); ?></option>
							<option value="publish-selected"><?php _e( 'Publish', 'mainwp-post-plus-extension' ); ?></option>
							<option value="preview-selected"><?php _e( 'Preview', 'mainwp-post-plus-extension' ); ?></option>
							<option value="delete-selected"><?php _e( 'Delete', 'mainwp-post-plus-extension' ); ?></option>
						</select>
						<input type="button" name="" id="mainwp-post-plus-bulk-actions-button" class="ui basic button" value="<?php _e( 'Apply', 'mainwp-post-plus-extension' ); ?>"/>
						<?php do_action( 'mainwp_post_plus_actions_bar_left' ); ?>
					</div>
					<div class="right aligned column">
						<?php do_action( 'mainwp_post_plus_actions_bar_right' ); ?>
					</div>
				</div>
			</div>
		</div>
		<div class="ui segment" id="mainwp-post-plug-drafts">
			<table class="ui single line table" id="mainwp-post-plug-drafts-table" style="width:100%">
				<thead>
					<tr>
						<th class="collapsing no-sort check-column"><span class="ui checkbox"><input type="checkbox"/></span></th>
						<th><?php _e( 'Title', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Type', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Author', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Categories', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Tags', 'mainwp-post-plus-extension' ); ?></th>
						<th><?php _e( 'Last Edit', 'mainwp-post-plus-extension' ); ?></th>
						<th class="collapsing no-sort"></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( is_array( $draft_posts ) && count( $draft_posts ) > 0 ) : ?>
						<?php foreach ( $draft_posts as $post ) : ?>
							<?php $author_info = get_userdata( $post->post_author ); ?>
							<?php

							$post_type = get_post_type( $post );
							$type_text = ( $post_type == 'bulkpost' ) ? 'Post' : 'Page';
							?>
							<tr id="post-<?php echo $post->ID; ?>" post-id="<?php echo $post->ID; ?>">
								<td class="check-column"><span class="ui checkbox"><input type="checkbox" value="1" name="post[]"></span></td>
								<td><a href="?page=<?php echo ( $post_type == 'bulkpost' ? 'PostBulkEdit' : 'PageBulkEdit' ); ?>&post_id=<?php echo $post->ID; ?>"><?php echo $post->post_title; ?></a></td>
								<td><?php echo $type_text; ?></td>
								<td><?php echo $author_info->user_login; ?></td>
								<td><?php echo self::column_categories( $post, 'categories' ); ?></td>
								<td><?php echo self::column_categories( $post, 'tags' ); ?></td>
								<td><?php echo self::column_date( $post ); ?></td>
								<td>
									<div class="ui left pointing dropdown icon mini basic green button">
										<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
										<div class="menu">
											<a href="?page=<?php echo ( $post_type == 'bulkpost' ? 'PostBulkEdit' : 'PageBulkEdit' ); ?>&post_id=<?php echo $post->ID; ?>" class="item"><?php _e( 'Edit' ); ?></a>
					 					  <a href="#" class="item mainwp-post-plus-publish-action"><?php _e( 'Publish' ); ?></a>
					 					  <a href="<?php echo get_permalink( $post->ID ) . '&preview=true'; ?>" class="item mainwp-post-plus-preview-action" target="_blank" ><?php _e( 'Preview' ); ?></a>
					 					  <a href="#" class="item mainwp-post-plus-delete-action" ><?php _e( 'Delete' ); ?></a>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
				<tfoot class="full-width">
					<tr>
						<th colspan="8">
							<a href="admin.php?page=PostBulkAdd" class="ui mini green button"><?php _e( 'Create New Post', 'mainwp-post-plus-extension' ); ?></a>
							<a href="admin.php?page=PageBulkAdd" class="ui mini green button"><?php _e( 'Create New Page', 'mainwp-post-plus-extension' ); ?></a>
						</th>
					</tr>
				</tfoot>
			</table>
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					jQuery( '#mainwp-post-plug-drafts-table' ).DataTable( {
						"stateSave": true,
						"stateDuration": 0, // forever
						"scrollX": true,
						"colReorder" : {
							fixedColumnsLeft: 1,
							fixedColumnsRight: 1
						},
						"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
						"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
						"order": [ [ 1, "asc" ] ],
						"language": { "emptyTable": "No saved drafts found." },
						"drawCallback": function( settings ) {
							jQuery('#mainwp-post-plug-drafts-table .ui.checkbox').checkbox();
							jQuery( '#mainwp-post-plug-drafts-table .ui.dropdown').dropdown();
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
	}

    public static function format_time_stamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	public static function column_date( $post ) {
		if ( empty( $post ) ) {
			return '';
		}
		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$h_time = __( 'Unpublished', 'mainwp-post-plus-extension' );
		} else {
			$time = mysql2date( 'U', $post->post_modified, false );
			$h_time = self::format_time_stamp( $time );
		}
		ob_start();
		echo $h_time;
		$output = ob_get_clean();
		return $output;
	}

	public static function column_categories( $post, $column_name ) {
		if ( empty( $post ) ) {
			return '';
		}
		$result = '';
		if ( 'categories' === $column_name ) {
			$saved_categories = get_post_meta( $post->ID, '_saved_draft_categories', true );
			$result = rawurldecode( base64_decode( $saved_categories ) );
		} else if ( 'tags' === $column_name ) {
			$saved_tags = get_post_meta( $post->ID, '_saved_draft_tags', true );
			$result = base64_decode( $saved_tags );
		}
		return $result;
	}
}
