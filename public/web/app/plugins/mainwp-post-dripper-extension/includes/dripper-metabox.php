<?php
/**
 * MainWP Post Dripper Metabox
 *
 * Renders the Post Dripper metabox content.
 *
 * @package MainWP/Extensions/Post Dripper
 */

$nb_sites = get_post_meta( $post->ID, '_mainwp_post_dripper_sites_number', true );
$nb_sites = empty( $nb_sites ) ? 1 : $nb_sites;

$nb_time = get_post_meta( $post->ID, '_mainwp_post_dripper_time_number', true );
$nb_time = empty( $nb_time ) ? 1 : $nb_time;

$select_time = get_post_meta( $post->ID, '_mainwp_post_dripper_select_time', true );
$select_time = empty( $select_time ) ? 'days' : $select_time;

$use_dripper = get_post_meta( $post->ID, '_mainwp_post_dripper_use_post_dripper', true );
$use_dripper = empty( $use_dripper ) ? false : true;

$_checked_use_dripper = '';
if ( $use_dripper ) {
	$_checked_use_dripper = 'checked';
}

$times = array(
	__( 'hours', 'mainwp-post-dripper-extension' ),
	__( 'days', 'mainwp-post-dripper-extension' ),
	__( 'weeks', 'mainwp-post-dripper-extension' ),
	__( 'months', 'mainwp-post-dripper-extension' )
);

?>
<h3 class="header"><?php echo __( 'Post Dripper', 'mainwp-post-dripper-extension' ); ?></h3>
<div id="mainwp-post-dripper-options" class="ui form">
	<div class="ui field">
		<label><?php _e( 'Use the post dripper', 'mainwp-post-dripper-extension' ); ?></label>
		<div class="ui toggle checkbox">
			<input type="checkbox" <?php echo $_checked_use_dripper; ?> name="mainwp_dripper_use_post_dripper" id="mainwp_dripper_use_post_dripper" value="1"><label></label>
		</div>
	</div>
	<div class="ui fields" id="metabox-drip-options" style="display:none">
		<div class="three wide field">
			<label><?php echo __( 'Sites', 'mainwp-post-dripper-extension' ); ?></label>
			<div class="ui input"><input name="mainwp_dripper_sites_number" value="<?php echo $nb_sites; ?>" min="1" max="200" /></div>
			<em><?php echo __( 'Enter the number of sites to post to', 'mainwp-post-dripper-extension' ); ?></em>
		</div>
		<div class="three wide field">
			<label><?php echo __( 'Times', 'mainwp-post-dripper-extension' ); ?></label>
			<div class="ui input"><input name="mainwp_dripper_time_number" value="<?php echo $nb_time; ?>" min="1" max="500" /></div>
			<em><?php echo __( 'Enter the number of times per selected frequency', 'mainwp-post-dripper-extension' ); ?></em>
		</div>
		<div class="three wide field">
			<label><?php echo __( 'Frequency', 'mainwp-post-dripper-extension' ); ?></label>
			<div class="dripper-option-list">
				<select class="ui dropdown" name="mainwp_dripper_select_time">
					<?php
					foreach ( $times as $time ) {
						echo '<option value="' . $time . '" ' . ( $select_time == $time ? ' selected ' : '' ) . ' >' . $time . '</option>';
					}
					?>
				</select>
			</div>
			<input type="hidden" name="dripper-nonce" value="<?php echo wp_create_nonce( 'dripper_' . $post->ID ); ?>">
			<em><?php echo __( 'Select frequency', 'mainwp-post-dripper-extension' ); ?></em>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery( document ).ready(function () {
	// Toggle Drip options
	jQuery( '#mainwp_dripper_use_post_dripper' ).on( 'change', function() {
		jQuery( '#metabox-drip-options' ).toggle();
	} );
} );
</script>
