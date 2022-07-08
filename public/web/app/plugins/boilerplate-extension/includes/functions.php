<?php
function mainwp_boilerplate_admin_print_footer_scripts() {
  $tokens = Boilerplate_DB::get_instance()->get_tokens();
  ?>
  <script type="text/javascript">
  jQuery( document ).on( 'tinymce-editor-setup', function ( event, editor ) {
    editor.addButton( 'inserttoken', {
      type: 'menubutton',
      text: 'Boilerplate Tokens',
      icon: false,
      tooltip: 'Click to insert Boilerplate tokens to the text editor.',
      menu:  [
        <?php foreach ( $tokens as $token ) : ?>
        {
          text: '[<?php echo $token->token_name; ?>]',
          type: 'menuitem',
          onclick: function() {
            editor.insertContent( '[<?php echo $token->token_name; ?>]' );
          }
        },
        <?php endforeach; ?>
      ]
    } );
  } );
  </script>
  <?php
}
