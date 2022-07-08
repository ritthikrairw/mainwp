<?php

 ?>
<div class="mainwp_wtc_wcard clearfix">
  <form id="wptc_main_acc_login" action="<?php echo network_admin_url("admin.php?page=wp-time-capsule-settings"); ?>" name="wptc_main_acc_login" method="post">
    <div class="l1 mainwp_wptc_login_msg_div <?php if (!isset($_GET['error'])) {echo 'active';} ?> ">Login to your WP Time Capsule account below</div>
        <div class="l1"  style="padding: 0px;">
            <input type="text" id="wptc_main_acc_email" name="wptc_main_acc_email" placeholder="Email" autofocus>
        </div>
        <div class="l1"  style="padding: 0px; position: relative;">
            <input type="password" id="wptc_main_acc_pwd" name="wptc_main_acc_pwd" placeholder="Password" >
            <a href=<?php echo MainWP_WPTC_APSERVER_URL_FORGET; ?> target="_blank" class="forgot_password">Forgot?</a>
        </div>
        <input type="button" name="mainwp_wptc_login" id="mainwp_wptc_login" class="btn_pri" value="Login" />
        <div style="clear:both"></div>
        <div id="mess" class="wptc_signup_link_div">Dont have an account yet?
            <a href=<?php echo MainWP_WPTC_APSERVER_URL_SIGNUP; ?> target="_blank" >Signup Now</a>
        </div>
        <div class="ui red message" id="mainwp_wptc_error_div"></div>
</form>
</div>

<script type="text/javascript" language="javascript">
  jQuery( document ).ready( function ( $ ) {
	   $( ".mainwp_wtc_wcard" ).on( 'keypress', '#wptc_main_acc_email', function(e) {
	     mainwp_triggerLoginWptc(e);
     } );
    $( ".mainwp_wtc_wcard" ).on( 'keypress', '#wptc_main_acc_pwd', function(e) {
    	mainwp_triggerLoginWptc(e);
    } );
  } );
</script>
