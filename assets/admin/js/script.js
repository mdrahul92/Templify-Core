jQuery(document).ready(function($){

    $(document).on("change", "#_edd_product_type", function () {
        var product_type = $(this).val();
           console.log(product_type);
            if ( product_type == "bundle" ) {
                $("#edd_full_access_exclude_single_option").hide();
                $(".edd-full-access-exclude-price-id").hide();
            }else if(product_type == "full_access"){
                $("#edd_downloads_full_access").show();
                $('#edd_product_files').hide();
                $("#edd_full_access_exclude_single_option").hide();
                $(".edd-full-access-exclude-price-id").hide();
            }else{
                $("#edd_downloads_full_access").hide();
            }
    });

    $(document).on('change','#edd_variable_pricing',function(){
        var product_type = $('#_edd_product_type').val();
        if( $("#edd_variable_pricing").is(":checked")){
            $("#edd_full_access_exclude_single_option").hide();
            $(".edd-full-access-exclude-price-id").show();
        }else{
            $("#edd_full_access_exclude_single_option").show();
            $(".edd-full-access-exclude-price-id").hide();
        }
        if(product_type == "full_access"){
            $("#edd_downloads_full_access").show();
        }else{
            $("#edd_downloads_full_access").hide();
        }
    });

    $(document).find("#_edd_product_type").val() == "full_access" ? $("#edd_product_files").hide() : $("#edd_downloads_full_access").hide();


});
