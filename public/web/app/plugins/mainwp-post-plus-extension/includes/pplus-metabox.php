<?php
$chked_privelege = $chked_admin = $chked_author = $chked_editor = $chked_contributor = $chked_category = $chked_date = $random_date_from = $random_date_to = '';

if ( ! empty( $post ) ) {
	$random_privelege = get_post_meta( $post->ID, '_saved_draft_random_privelege', true );
	$random_privelege = unserialize( base64_decode( $random_privelege ) );

	if ( is_array( $random_privelege ) ) {
		$chked_admin = in_array( 'administrator', $random_privelege ) ? ' checked ' : '';
		$chked_author = in_array( 'author', $random_privelege ) ? ' checked ' : '';
		$chked_editor = in_array( 'editor', $random_privelege ) ? ' checked ' : '';
		$chked_contributor = in_array( 'contributor', $random_privelege ) ? ' checked ' : '';
		$chked_privelege = count( $random_privelege ) == 4 ? ' checked ' : '';
	}

	$random_category = get_post_meta( $post->ID, '_saved_draft_random_category', true );
	$chked_category = ! empty( $random_category ) ? ' checked ' : '';

	$random_date = get_post_meta( $post->ID, '_saved_draft_random_publish_date', true );
	$chked_date = ! empty( $random_date ) ? ' checked ' : '';

	if ( ! empty( $random_date ) ) {
		$random_date_from = get_post_meta( $post->ID, '_saved_draft_publish_date_from', true );
		$random_date_from = ! empty( $random_date_from ) ? date( 'Y-m-d', $random_date_from ) : '';

		$random_date_to = get_post_meta( $post->ID, '_saved_draft_publish_date_to', true );
		$random_date_to = ! empty( $random_date_to ) ? date( 'Y-m-d', $random_date_to ) : '';
	}
}

?>
<h3 class="header"><?php echo __( 'Post Plus', 'mainwp-post-dripper-extension' ); ?></h3>
<div class="mainwp_pplus_metabox">
  <div class="pplus_metabox_field">
		<div class="ui relaxed list">
		  <div class="item">
				<div class="ui master checkbox">
					<input type="checkbox" name="pplus_meta_privelege_check_all" id="pplus_meta_privelege_check_all" <?php echo $chked_privelege; ?> >
					<label><?php _e( 'Set random author with privilege', 'mainwp-post-plus-extension' ); ?></label>
				</div>
		    <div class="list pplus_meta_privelege_group">
		      <div class="item">
		        <div class="ui child checkbox">
							<input type="checkbox" name="pplus_meta_privelege[]" <?php echo $chked_admin; ?> value="administrator">
			      	<label><?php _e( 'Administrator', 'mainwp-post-plus-extension' ); ?></label>
		        </div>
		      </div>
		      <div class="item">
		        <div class="ui child checkbox">
							<input type="checkbox" name="pplus_meta_privelege[]" <?php echo $chked_author; ?> value="author">
			      	<label><?php _e( 'Author', 'mainwp-post-plus-extension' ); ?></label>
		        </div>
		      </div>
					<div class="item">
		        <div class="ui child checkbox">
							<input type="checkbox" name="pplus_meta_privelege[]" <?php echo $chked_editor; ?> value="editor">
			      	<label><?php _e( 'Editor', 'mainwp-post-plus-extension' ); ?></label>
		        </div>
		      </div>
					<div class="item">
		        <div class="ui child checkbox">
							<input type="checkbox" name="pplus_meta_privelege[]" <?php echo $chked_contributor; ?> value="contributor">
			      	<label><?php _e( 'Contributor', 'mainwp-post-plus-extension' ); ?></label>
		        </div>
		      </div>
		    </div>
		  </div>
			<?php if ( ! empty( $post ) && $post->post_type !== 'bulkpage' ) : ?>
			<div class="item">
				<div class="ui checkbox">
		      <input type="checkbox" name="pplus_meta_random_category" <?php echo $chked_category; ?> value="1">
		      <label><?php _e( 'Set random category', 'mainwp-post-plus-extension' ); ?></label>
		    </div>
			</div>
			<?php endif; ?>
		</div>
		<div class="pplus_metabox_field">
			<div class="ui grid">
				<div class="eight wide middle aligned column">
					<div class="ui checkbox">
			      <input type="checkbox" name="pplus_meta_random_publish_date" <?php echo $chked_date; ?> value="1">
			      <label><?php _e( 'Set random publishing date in date range: ' ); ?></label>
					</div>
				</div>
				<div class="four wide middle aligned column">
					<div class="ui calendar mainwp_datepicker">
						<div class="ui input left icon">
							<i class="calendar icon"></i>
							<input type="text" placeholder="<?php echo __( 'From', 'mainwp-post-plus-extension' ); ?>" value="<?php echo $random_date_from; ?>" size="12" class="mainwp_datepicker" name="pplus_random_date_from">
						</div>
					</div>
				</div>
				<div class="four wide middle aligned column">
					<div class="ui calendar mainwp_datepicker" >
						<div class="ui input left icon">
							<i class="calendar icon"></i>
							<input type="text" placeholder="<?php echo __( 'To', 'mainwp-post-plus-extension' ); ?>" value="<?php echo $random_date_to; ?>" size="12" class="mainwp_datepicker" name="pplus_random_date_to">
						</div>
					</div>
				</div>
			</div>
	  </div>
  </div>
</div>
<script type="text/javascript">
jQuery( document ).ready( function () {
	jQuery( '.list .master.checkbox' ).checkbox( {
	    // check all children
	    onChecked: function() {
	      var $childCheckbox  = jQuery( this ).closest( '.checkbox' ).siblings( '.list' ).find( '.checkbox' );
	      $childCheckbox.checkbox( 'check' );
	    },
	    // uncheck all children
	    onUnchecked: function() {
	      var $childCheckbox  = jQuery( this ).closest( '.checkbox' ).siblings( '.list' ).find( '.checkbox' );
	      $childCheckbox.checkbox( 'uncheck' );
	    }
	  } );

	  jQuery( '.pplus_metabox_field .ui.calendar' ).calendar({
			type: 'date',
			monthFirst: false,
			formatter: {
				date: function ( date ) {
					if (!date) return '';
					var day = date.getDate();
					var month = date.getMonth() + 1;
					var year = date.getFullYear();

					if (month < 10) {
						month = '0' + month;
					}
					if (day < 10) {
						day = '0' + day;
					}
					return year + '-' + month + '-' + day;
				}
			}
	});

} );

</script>
<input type="hidden" name="mainwp_pplus_metabox_submit" value="1">
