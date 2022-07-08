<?php
$initial_setup = MainWP_WPTC_Base_Factory::get('MainWP_Wptc_InitialSetup');
?>
<div class="mainwp_wtc_wcard clearfix" style="width: 980px;">
<div class="l1"  style="padding-bottom: 10px;">The backup of this website will be stored in a folder in your <?php echo MAINWP_WTC_DEFAULT_REPO_LABEL; ?> app</div>
    <form id="backup_to_dropbox_continue" name="backup_to_dropbox_continue" method="post">
        <?php echo $initial_setup->get_select_cloud_dialog_div(); ?>
    </form>
    <div class="l1 wptc_error_div " style="<?php if (isset($_GET['error']) && !empty($_GET['error'])) {echo "display:block;";} else {echo "display:none;";}
?>"><?php
?></div>

</div>

<script type="text/javascript" language="javascript">
   jQuery(document).ready(function ($) {
          
            $("#select_wptc_cloud_storage").on('change', function(){
                    $(".creds_box_inputs", this_par).hide();
                    jQuery('#mainwp_wptc_connect_to_cloud').hide();
                    jQuery('#s3_seperate_bucket_note').hide();
                    jQuery('.dummy_select').remove();

                    $(".cloud_error_mesg").hide();
                    var cur_cloud = $(this).val();

                    if(cur_cloud == ""){
                        return false;
                    }

                    jQuery("#mainwp_wptc_connect_to_cloud").attr("cloud_type", cur_cloud);

                    if (cur_cloud == 's3') {
                        jQuery('#mainwp_wptc_connect_to_cloud').show();
                    }

                    var this_par = $(this).closest(".mainwp_wtc_wcard");
                    $("#mess").show();
                    $("#donot_touch_note").show();

                    if(cur_cloud == 's3'){
                        jQuery("#mess, #s3_seperate_bucket_note").toggle();
                        $(".s3_inputs", this_par).show();
                    }
                    else if(cur_cloud == 'g_drive'){
                        $(".g_drive_inputs", this_par).show();
                    }
            });

    });
</script>