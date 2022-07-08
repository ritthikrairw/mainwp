<h3><?php _e( 'Import' ) ?></h3>

<form action="" method="post" id="kwl-import-export-form" enctype="multipart/form-data">
<?php
	$this->create_option_field( 'import_file', __( 'Upload import file' ), 'file' );
?>
<div class="option-list">
    <div class="option-field">
		<button id="kwlImport" class="button" name="kwlImport" value="<?php echo wp_create_nonce( 'kwlDoImport' ) ?>"><?php _e( 'Import' ) ?></button>
    </div>
</div>
</form>
<h3><?php _e( 'Export' ) ?></h3>
<?php
	$this->create_option_field('export_data', __( 'Select data to export' ), 'checkbox', array( 'config' ), array(
		'config' => 'Configuration and Do Not Link',
		'link' => 'Links',
				'statistic' => 'Statistic',
	));
?>
<?php
	$this->create_option_field( 'format_export', __( 'Export format' ), 'checkbox', null, array( 'easy_reading' => __( 'Easy reading format' ) ), __( '(Will export Links data to easy reading format)' ) );
?>
<div class="option-list">
    <div class="option-field">
		<button id="export_download" class="button"><?php _e( 'Download' ) ?></button>
                <img src="<?php echo admin_url( '/images/wpspin_light.gif' ) ?>" alt="<?php _e( 'Loading' ) ?>" class="kwl-link-loading" />
                <div id="kwl-export-status"></div>
    </div>
</div>
