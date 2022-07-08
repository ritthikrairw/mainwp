<?php

class MainWP_CS {
	public static $instance = null;
	protected $option_handle = 'mainwp_child_branding_options';
	protected $option;

	static function get_instance() {

		if ( null === MainWP_CS::$instance ) {
			MainWP_CS::$instance = new MainWP_CS();
		}
		return MainWP_CS::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
	}

	public function init() {
		add_action( 'wp_ajax_mainwp_snippet_run_snippet_loading', array( $this, 'run_snippet_loading' ) );
		add_action( 'wp_ajax_mainwp_snippet_run_snippet', array( $this, 'run_snippet' ) );
		add_action( 'wp_ajax_mainwp_snippet_delete_snippet', array( $this, 'delete_snippet' ) );
		add_action( 'wp_ajax_mainwp_snippet_save_snippet', array( $this, 'save_snippet' ) );
		add_action( 'wp_ajax_mainwp_snippet_clear_on_site_loading', array( $this, 'snippet_clear_site_loading' ) );
		add_action( 'wp_ajax_mainwp_snippet_clear_on_site', array( $this, 'delete_snippet_site' ) );
		add_action( 'wp_ajax_mainwp_snippet_update_site_loading', array( $this, 'update_snippet_site_loading' ) );
		add_action( 'wp_ajax_mainwp_snippet_update_site', array( $this, 'update_snippet_site' ) );
		add_action( 'wp_ajax_mainwp_snippet_delete_on_site', array( $this, 'delete_snippet_site' ) );
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

	public function delete_snippet() {
		$id = $_POST['snippet_id'];
		if ( ! $id ) {
			die( 'FAIL' );
		}
		if ( MainWP_CS_DB::get_instance()->remove_codesnippet( $id ) ) {
			die( 'SUCCESS' );
		}
		die( 'FAIL' );
	}

	public function save_snippet() {
		if ( isset( $_POST['snippet_title'] ) ) {
			$snippet = array(
		   'code' 			 => $_POST['code'],
		   'title' 			 => $_POST['snippet_title'],
			   'description' => $_POST['desc'],
			);

			if ( $_POST['snippet_id'] ) {
				$snippet['id'] = $_POST['snippet_id'];
			} else { // create new snippet
				$snippet['snippet_slug'] = MainWP_CS_Utility::rand_string( 5 );
			}

			$snippet['type'] = 'R';

			if ( isset( $_POST['type'] ) ) {
				$snippet['type'] = $_POST['type'];
			}

			$selected_wp = $selected_group = array();

			if ( isset( $_POST['select_by'] ) ) {
				if ( isset( $_POST['sites'] ) && is_array( $_POST['sites'] ) ) {
					foreach ( $_POST['sites'] as $selected ) {
						$selected_wp[] = $selected;
					}
				}
				if ( isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ) {
					foreach ( $_POST['groups'] as $selected ) {
						$selected_group[] = $selected;
					}
				}
			}

			$snippet['sites'] = base64_encode( serialize( $selected_wp ) );
			$snippet['groups']  = base64_encode( serialize( $selected_group ) );

			if ( false !== ( $get_snippet = MainWP_CS_DB::get_instance()->update_codesnippet( $snippet ) ) ) {
				$return = array();
				$return['id'] = $get_snippet->id;
				$return['slug'] = $get_snippet->snippet_slug;
				$return['type'] = $get_snippet->type;
				$return['status'] = 'SUCCESS';
				die( json_encode( $return ) );
			}
		}
		die( json_encode( array() ) );
	}

	public function run_snippet_loading() {
		global $mainWPCSExtensionActivator;
		$sites = $groups = array();
		if ( isset( $_POST['sites'] ) && is_array( $_POST['sites'] ) && count( $_POST['sites'] ) > 0 ) {
			$sites = $_POST['sites'];
		}

		if (  isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) && count( $_POST['groups'] ) > 0 ) {
			$groups = $_POST['groups'];
		}

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $sites, $groups );

		if ( ! is_array( $dbwebsites ) || count( $dbwebsites ) <= 0 ) {
			die( __( 'No child sites found. Please, make sure your sites are properly connected.', 'mainwp-code-snippets-extension' ) );
		}

		foreach ( $dbwebsites as $website ) {
			?>
			<div class="ui segments mainwp-snippet-item" siteid="<?php echo $website->id; ?>" status="queue">
			  <div class="ui segment">
					<div class="ui grid">
						<div class="two column row">
							<div class="column"><a href="<?php echo $website->url; ?>" ><?php echo stripslashes( $website->name ); ?></a></div>
							<div class="right aligned column"><span class="status"><i class="clock outline icon"></i></span></div>
			</div>
					</div>
			  </div>
			  <div class="ui secondary segment">
			    <pre class="mainwp-snippet-output"></pre>
			  </div>
			</div>
			<?php
		}
		exit;
	}

	function run_snippet() {
		$siteid = $_POST['siteId'];
		$code = stripslashes( $_POST['code'] );
		$code = preg_replace( '|^[\s]*<\?(php)?|', '', $code );
		$code = preg_replace( '|\?>[\s]*$|', '', $code );
		$code = trim( $code );
		if ( empty( $siteid ) ) {
			die( json_encode( 'FAIL' ) );
		} else if ( empty( $code ) ) {
			die( json_encode( 'CODEEMPTY' ) );
		}

			global $mainWPCSExtensionActivator;

		$post_data = array(
			'action' => 'run_snippet',
			'code' => $code
		);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $siteid, 'code_snippet', $post_data );

			die( json_encode( $information ) );
	}

	public static function rende_delete_snippet_on_sites( $snippetId ) {
		global $mainWPCSExtensionActivator;
		$return = array();
		$websites = apply_filters( 'mainwp_getsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), null );

		if ( is_array( $websites ) && count( $websites ) > 0 ) {
		?>
		<div class="ui modal" id="mainwp-code-snippets-cleaning-sites-modal">
			<div class="header"><?php echo __( 'Cleaning Sites', 'mainwp-code-snippets-extension' ); ?></div>
			<div class="scrolling content">
				<div id="mainwp-modal-message-zone" class="ui message" style="display:none"></div>
				<?php
		if ( empty( $snippetId ) ) {
					echo '<div class="ui red message">' . __( 'Snippet ID is empty.', 'mainwp-code-snippets-extension' ) . '</div>';
			   return;
		} else {
			$snippet = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'id', $snippetId );
			if ( ! is_object( $snippet ) ) {
						echo '<div class="ui red message">' . __( 'Snippet not found.', 'mainwp-code-snippets-extension' ) . '</div>';
				return;
			}
		}
		?>
				<div class="ui divided list">
				<?php foreach ( $websites as $website ) : ?>
					<div class="item">
						<a href="<?php echo $website->url; ?>"><?php echo $website['name']; ?></a>
						<span class="mainwp-code-snippets-snippet-to-delete right floated" snippetid="<?php echo esc_html($snippedId); ?>" siteid="<?php echo $website['id']; ?>" status="queue">
							<span class="status"><i class="clock outline icon"></i></span>
						</span>
					</div>
				<?php endforeach; ?>
				</div>
			</div>
			<div class="actions">
		    <input type="hidden" id="mainwp_snippet_delete_id" value="<?php echo esc_html($snippet->id); ?>">
		    <input type="hidden" id="mainwp_snippet_slug_value" name="mainwp_snippet_slug_value" value="<?php echo esc_html($snippet->snippet_slug); ?>">
		    <input type="hidden" id="mainwp_snippet_type_value" name="mainwp_snippet_type_value" value="<?php echo esc_html($snippet->type); ?>">
				<div class="cancel ui button"><?php echo __( 'Close', 'mainwp-code-snippets-extension' ); ?></div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery( '#mainwp-code-snippets-cleaning-sites-modal' ).modal( 'show' );
			jQuery( document ).ready( function($) {
	              mainwp_snippet_delete_sites_start_next();
			} );
	      </script>
		<?php
		} else {
			?>
			<div class="ui mini modal" id="mainwp-code-snippets-cleaning-sites-modal">
				<div class="header"><?php echo __( 'Cleaning Sites', 'mainwp-code-snippets-extension' ); ?></div>
				<div class="content"><?php echo __( 'No websites found.', 'mainwp-code-snippets-extension' ); ?></div>
				<div class="actions">
					<a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension" class="cancel ui button"><?php echo __( 'Close', 'mainwp-code-snippets-extension' ); ?></a>
			</div>
		</div>
			<script type="text/javascript">
				jQuery( '#mainwp-code-snippets-cleaning-sites-modal' ).modal( 'show' );
			</script>
		<?php
		}
	}

	function delete_snippet_site() {
		$siteid = $_POST['siteId'];
		$snippetslug = $_POST['snippetSlug'];
		$type = $_POST['type'];

		if ( empty( $siteid ) || empty( $snippetslug ) || empty( $type ) ) {
			die( json_encode( 'FAIL' ) );
		}

		global $mainWPCSExtensionActivator;

		$post_data = array(
			'action' => 'delete_snippet',
			'slug' => $snippetslug,
			'type' => $type
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $siteid, 'code_snippet', $post_data );

		die( json_encode( $information ) );
	}

	function snippet_clear_site_loading() {
		global $mainWPCSExtensionActivator;

		$snippet = self::check_snippet_post_value();
		$dbwebsites = array();
		$websites = apply_filters( 'mainwp_getsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), null );

		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$dbsite = apply_filters( 'mainwp_getdbsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), array( $website['id'] ), null );
				$dbsite = current( $dbsite );
				$dbwebsites[ $website['id'] ] = $dbsite;
			}
		}

		if ( ! is_array( $dbwebsites ) ||  count( $dbwebsites ) <= 0 ) {
			die( 'NOSITES' );
		}

		foreach ( $dbwebsites as $website ) {
			?>
			<div class="ui segments mainwp-clear-snippet-item" snippetid="<?php echo $snippet->id; ?>" siteid="<?php echo $website->id; ?>" status="queue">
				<div class="ui segment">
					<div class="ui grid">
						<div class="two column row">
							<div class="column"><a href="<?php echo $website->url; ?>" ><?php echo stripslashes( $website->name ); ?></a></div>
							<div class="right aligned column"><span class="status"><i class="clock outline icon"></i></span></div>
			</div>
					</div>
				</div>
				<div class="ui secondary segment">
			    <pre class="mainwp-snippet-output"></pre>
			  </div>
			</div>
			<?php
		}
		die();
	}

	public static function check_code_snippet( $code ) {
		$code = stripslashes( $code );
		$code = preg_replace( '|^[\s]*<\?(php)?|', '', $code );
		$code = preg_replace( '|\?>[\s]*$|', '', $code );
		$code = trim( $code );

		if ( empty( $code ) ) {
			return 'CODEEMPTY';
		}
		return $code;
	}

	public static function check_snippet_post_value() {
		$snippet_id = $_POST['snippetId'];
		if ( empty( $snippet_id ) ) {
			die( '<div class="ui red message">' . __( 'Snippet ID empty.', 'mainwp-code-snippets-extension' ) . '</div>' );
		}
		$snippet = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'id', $snippet_id );
		if ( ! is_object( $snippet ) ) {
			die( '<div class="ui red message">' . __( 'Snippet is empty.', 'mainwp-code-snippets-extension' ) . '</div>' );
		}
		return $snippet;
	}

	function get_snippet_dbsites( $snippet ) {
		global $mainWPCSExtensionActivator;
		$sites = unserialize( base64_decode( $snippet->sites ) );
		$groups = unserialize( base64_decode( $snippet->groups ) );
		return apply_filters( 'mainwp_getdbsites', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $sites, $groups );
	}

	public static function update_snippet_site_loading() {
		$snippet = self::check_snippet_post_value();
		$code = self::check_code_snippet( $snippet->code );
		if ( 'CODEEMPTY' === $code ) {
			die( '<div class="ui red message">' . __( 'Snippet is empty.', 'mainwp-code-snippets-extension' ) . '</div>' );
		}
		$dbwebsites = MainWP_CS::get_instance()->get_snippet_dbsites( $snippet );

		if ( ! is_array( $dbwebsites ) ||  count( $dbwebsites ) <= 0 ) {
			die( 'NOSITES' );
		}
		foreach ( $dbwebsites as $website ) {
			?>
			<div class="ui segments mainwp-update-snippet-item" snippetid="<?php echo $snippet->id; ?>" siteid="<?php echo $website->id; ?>" status="queue">
				<div class="ui segment">
					<div class="ui grid">
						<div class="two column row">
							<div class="column"><a href="<?php echo $website->url; ?>" ><?php echo stripslashes( $website->name ); ?></a></div>
							<div class="right aligned column"><span class="status"><i class="clock outline icon"></i></span></div>
			</div>
					</div>
				</div>
				<div class="ui secondary segment">
			    <pre class="mainwp-snippet-output"></pre>
			  </div>
			</div>
			<?php
		}
		die();
	}

	function update_snippet_site() {
		$siteid = $_POST['siteId'];
		$snippetslug = $_POST['snippetSlug'];
		$type = $_POST['type'];

		if ( empty( $siteid ) || empty( $snippetslug ) ) {
			die( json_encode( 'FAIL' ) );
		}

		$code = self::check_code_snippet( $_POST['code'] );

		if ( 'CODEEMPTY' === $code ) {
			die( json_encode( 'CODEEMPTY' ) );
		}

		global $mainWPCSExtensionActivator;
		$post_data = array(
			'action' => 'save_snippet',
			'code' 	 => $code,
			'slug' 	 => $snippetslug,
			'type'	 => $type
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPCSExtensionActivator->get_child_file(), $mainWPCSExtensionActivator->get_child_key(), $siteid, 'code_snippet', $post_data );
		die( json_encode( $information ) );
	}

	public static function render_settings() {
		$snippet = false;

		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			$snippet = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'id' , $_GET['id'] );
		}

		$_code = $_title = $_desc = $sites = $_slug = $_type = '';
		$type_run = $type_save = $type_config = '';
		$selected_sites = $selected_groups = array();
		$code_id = 0;

		if ( is_object( $snippet ) ) {
			$code_id = $snippet->id;
			$_code = $snippet->code;
			$_title = $snippet->title;
			$_desc = $snippet->description;
			$_slug = $snippet->snippet_slug;
			$selected_sites = unserialize( base64_decode( $snippet->sites ) );
			$selected_groups = unserialize( base64_decode( $snippet->groups ) );
			$_type = $snippet->type;
			if ( 'R' == $_type ) {
				$type_run = 'checked';
			} else if ( 'S' == $_type ) {
				$type_save = 'checked';
			} else if ( 'C' == $_type ) {
				$type_config = 'checked';
		}
		}

		if ( empty( $type_run ) && empty( $type_save ) && empty( $type_config ) ) {
			$type_run = 'checked';
		}

		if ( ! is_array( $selected_sites ) ) {
			$selected_sites = array();
		}

		if ( ! is_array( $selected_groups ) ) {
			$selected_groups = array();
		}

		$class = 'green';

		if ( isset( $_GET['message'] ) ) {
			if ( 1 == $_GET['message'] ) {
				$message = __( 'Snippet has been saved.', 'mainwp-code-snippets-extension' );
			} else if ( -1 == $_GET['message'] ) {
				$message = __( 'Saving snippet failed. Please, try again.', 'mainwp-code-snippets-extension' );
				$class = 'red';
			}
		}

		?>
            <form method="POST" id="mainwp_snippet_edit_form" action="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension">
            <input type="hidden" id="mainwp_snippet_id_value" name="mainwp_snippet_id_value" value="<?php echo esc_html($code_id); ?>">
            <input type="hidden" id="mainwp_snippet_slug_value" name="mainwp_snippet_slug_value" value="<?php echo esc_html($_slug); ?>">
            <input type="hidden" id="mainwp_snippet_type_value" name="mainwp_snippet_type_value" value="<?php echo esc_html($_type); ?>">
            <input type="hidden" name="snp_security" value="<?php echo wp_create_nonce( 'mainwp-save-snippet' ); ?>">
            <div class="mainwp-main-content">
                <div class="ui hidden divider"></div>
                <?php if ( !empty( $message ) ) : ?>
                <div class="ui message <?php echo $class; ?>"><i class="close icon"></i> <?php echo $message; ?></div>
                <?php endif; ?>
                <div class="ui message" id="mainwp-message-zone"></div>
                <div class="ui message yellow"><i class="close icon"></i>
                    <?php _e( 'MainWP is not responsible for the code that you run on your sites. Use this tool with extreme care and at your own risk. It is recommended that you run any code on a test site before releasing on live sites.', 'mainwp-code-snippets-extension' ); ?>
                </div>

                <textarea id="mainwp-code-snippets-code-editor" name="mainwp-code-snippets-code-editor" rows="50" spellcheck="false"><?php
                    echo ! empty( $_code ) ? esc_textarea( stripslashes( $_code ) ) : '';
                ?></textarea>
            </div>
		<div class="mainwp-side-content mainwp-no-padding">
			<div class="mainwp-select-sites">
				<h3 class="ui header"><?php echo __( 'Select Sites', 'mainwp-code-snippets-extension' ); ?></h3>
				<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_sites, $selected_groups ); ?>
					</div>
			<div class="ui divider"></div>
			<div class="mainwp-search-options">
				<h3 class="ui header"><?php echo __( 'Snippet Options', 'mainwp-code-snippets-extension' ); ?></h3>
				<div class="ui mini form">
					<div class="field">
						<input type="text" placeholder="<?php esc_attr_e( 'Snippet Title', 'mainwp-code-snippets-extension' ); ?>" id="snp_snippet_title" name="snp_snippet_title" value="<?php echo stripslashes( $_title ); ?>"/>
						</div>
					<div class="field">
						<input type="text" placeholder="<?php esc_attr_e( 'Snippet Description', 'mainwp-code-snippets-extension' ); ?>" id="snp_snippet_desc" name="snp_snippet_desc" value="<?php echo stripslashes( esc_textarea( $_desc ) ); ?>"/>
						</div>
					<div class="field">
						<label><?php _e( 'Snippet Type', 'mainwp-code-snippets-extension' ); ?></label>
						<div class="ui radio checkbox">
						  <input type="radio" id="rad-snippet-type-save" name="snp_snippet_type" value="S" <?php echo $type_save; ?>>
						  <label><?php _e( 'Execute on Child Sites' ); ?></label>
					</div>
						<div class="ui radio checkbox">
						  <input type="radio" id="rad-snippet-type-run" name="snp_snippet_type" value="R" <?php echo $type_run; ?>>
						  <label><?php _e( 'Return info from Child Sites' ); ?></label>
				</div>
						<div class="ui radio checkbox">
						  <input type="radio" id="rad-snippet-type-config" name="snp_snippet_type" value="C" <?php echo $type_config; ?>>
						  <label><?php _e( 'Save to wp-config.php' ); ?></label>
					</div>
					</div>
				</div>
			</div>
			<div class="ui divider"></div>
			<div class="mainwp-search-submit">
				<input type="button" id="mainwp-code-snippetes-save-snippet-button" class="ui green basic fluid big button" value="<?php esc_attr_e( 'Save Snippet', 'mainwp-code-snippets-extension' ); ?>"/>
				<div class="ui hidden divider"></div>
				<input type="button" id="mainwp-code-snippetes-execute-snippet-button" class="ui green fluid big button" value="<?php esc_attr_e( 'Save & Execute Snippet', 'mainwp-code-snippets-extension' ); ?>"/>
				<div class="ui hidden divider"></div>
				<a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=new" class="ui fluid big button"><?php echo __( 'New Snippet', 'mainwp-code-snippets-extension' ); ?></a>
                <?php if ( $code_id ) { ?>
                <div class="ui hidden divider"></div>
                <a href="javascript:void(0);" id="mainwp-code-snippetes-delete-snippet-button" class="ui fluid big basic button"><?php echo __( 'Delete', 'mainwp-code-snippets-extension' ); ?></a>
                <?php } ?>
		</div>
		</div>
		</form>
		<div class="ui clearing hidden divider"></div>
		<div class="ui modal" id="mainwp-code-snippets-console-modal">
			<div class="header"><?php echo __( 'Console', 'mainwp-code-snippets-extension' ); ?></div>
			<div class="scrolling content">
				<h4 class="ui dividing header">
					<div id="mainwp-code-snippet-output-title"><?php echo __( 'Current Progress', 'mainwp-code-snippets-extension' ); ?></div>
					<div class="sub header" id="mainwp-code-snippet-output-log"></div>
				</h4>
				<div id="mainwp-code-snippet-output"></div>
			</div>
			<div class="actions">
				<div class="ui cancel reload button"><?php _e( 'Close', 'mainwp-code-snippets-extension' ); ?></div>
			</div>
		</div>
    <?php

        self::render_delete_modal();
	}

	public static function render_list() {
		$snippets = MainWP_CS_DB::get_instance()->get_codesnippet_by( 'all', null, null, 'title' );
		?>
		<table class="ui tablet stackable table" id="mainwp-code-snippets-table">
          <thead>
				<tr>
					<th><?php _e( 'Snippet Title', 'mainwp-code-snippets-extension' ); ?></th>
					<th><?php _e( 'Snippet Type', 'mainwp-code-snippets-extension' ); ?></th>
					<th><?php _e( 'Snippet Description', 'mainwp-code-snippets-extension' ); ?></th>
					<th><?php _e( 'Last Edited', 'mainwp-code-snippets-extension' ); ?></th>
					<th class="no-sort"></th>
              </tr>
          </thead>
			<tbody>
				<?php if ( is_array( $snippets ) && count( $snippets ) > 0 ) : ?>
					<?php self::render_table_content( $snippets, 'title' ); ?>
				<?php else : ?>
 					<tr><td colspan="5"><?php __( 'No saved snippets.', 'mainwp-code-snippets-extension' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
			<tfoot class="full-width">
              <tr>
					<th colspan="5"><a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=new" class="ui green button"><?php _e( 'New Snippet', 'mainwp-code-snippets-extension' ); ?></a></th>
              </tr>
          </tfoot>
        </table>

		<script type="text/javascript">
		jQuery( '#mainwp-code-snippets-table' ).DataTable( {
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
		} );
		</script>
        <?php
        self::render_delete_modal();
    }

    public static function render_delete_modal() {
        ?>
            <div class="ui mini modal" id="mainwp-code-snippet-delete-snippet-modal">
			<div class="header"><?php echo __( 'Delete Snippet', 'mainwp-code-snippets-extension' ); ?></div>
			<div class="scrolling content">
				<div class="ui form">
					<div class="grouped fields">
						<div class="field">
							<label><?php echo __( 'Keep the existing snippet on the child sites?', 'mainwp-code-snippets-extension' );?></label>
							<div class="ui radio checkbox">
							  <input type="radio" value="0" name="delete_snippet_child_site">
							  <label><?php _e( 'Yes' ,'mainwp-code-snippets-extension' ); ?></label>
                </div>
						</div>
						<div class="field">
							<div class="ui radio checkbox">
							  <input type="radio" value="1" name="delete_snippet_child_site" checked>
							  <label><?php _e( 'No' ,'mainwp-code-snippets-extension' ); ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="actions">
                <input type="hidden" name="delete_snippetid" value="" />
                <div class="ui cancel button"><?php echo __( 'Close', 'mainwp-code-snippets-extension' ); ?></div>
                <input type="submit" value="<?php _e( 'Delete', 'mainwp-code-snippets-extension' ); ?>" class="ui button green" id="mainwp-code-snippets-delete-snippet-button">
            </div>
        </div>
        <?php
        }

        public static function render_table_content( $snippets, $orderby ) {
		foreach ( $snippets as $snippet ) :
						   $date_format = get_option( 'date_format' );
						   $time_format = get_option( 'time_format' );
						?>
   	<tr id="snippet-<?php echo $snippet->id; ?>">
			<td><a href="?page=Extensions-Mainwp-Code-Snippets-Extension&id=<?php echo $snippet->id; ?>"><?php echo stripslashes( $snippet->title ); ?></a></td>
	    <td><?php echo ( $snippet->type == 'S' ) ? __( 'Executes Function', 'mainwp-code-snippets-extension' ) : ( $snippet->type == 'R' ? __( 'Return Information', 'mainwp-code-snippets-extension' ) : ( $snippet->type == 'C' ? __( 'Executes in wp-config.php', 'mainwp-code-snippets-extension' ) : '' ) ); ?></td>
	   	<td><?php echo stripslashes( $snippet->description ); ?></td>
			<td><?php echo date( $date_format, $snippet->date ) . ' ' . date( $time_format, $snippet->date ); ?></td>
			<td class="right aligned"><a class="ui mini green button" href="?page=Extensions-Mainwp-Code-Snippets-Extension&id=<?php echo $snippet->id; ?>"><?php _e( 'Load', 'mainwp-code-snippets-extension' ); ?></a> <a href="#" class="snippet_list_delete_item ui mini button" type="<?php echo $snippet->type; ?>" id="<?php echo $snippet->id; ?>" ><?php _e( 'Delete', 'mainwp-code-snippets-extension' ); ?></a></td>
        </tr>
        <?php
		endforeach;
			}

	public static function render() {
		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'new' ) {
				$current_tab = 'new';
			} else if ( $_GET['tab'] == 'snippets' ) {
				$current_tab = 'snippets';
			}
		} else {
			$current_tab = 'new';
		}

		if ( isset( $_GET['id'] ) && isset( $_GET['deleteonsites'] ) && 1 == $_GET['deleteonsites'] ) {
			self::rende_delete_snippet_on_sites( $_GET['id'] );
			return;
		}
		?>
		<div id="mainwp-code-snippets">
		  <div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-code-snippets-menu">
		    <a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=new" class="item <?php echo ( $current_tab == 'new' ? 'active' : '' ); ?>"><i class="code icon"></i> <?php _e( 'Execute Snippet', 'mainwp-team-control' ); ?></a>
		    <a href="admin.php?page=Extensions-Mainwp-Code-Snippets-Extension&tab=snippets" class="item <?php echo ( $current_tab == 'snippets' ? 'active' : '' ); ?>"><i class="list icon"></i> <?php _e( 'Saved Snippets', 'mainwp-team-control' ); ?></a>
		</div>
			<?php if ( $current_tab == "new" || $current_tab == "" ) : ?>
				<div class="ui alt segment" id="mainwp-code-snippets-new-snippet-tab">
					<?php self::render_settings(); ?>
				</div>
			<?php endif; ?>
			<?php if ( $current_tab == "snippets" ) : ?>
				<div class="ui segment" id="mainwp-code-snippets-saved-snippets-tab">
					<?php self::render_list(); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
