jQuery( document ).ready(function() {
    jQuery(".sucess-msg").hide();
    jQuery(".save_modifier").click(function (e) {

        var product_id =  jQuery('#product_id').val();
        var product_modifier_group_name = [ ];
        var required_set = [ ];
        i = 0;
        jQuery(".product-modifier span input.product_modifier_texonomy[type='checkbox']:checked ").each(function(){
            product_modifier_group_name[i++]  = jQuery(this).val(); // This is the jquery object of the input, do what you will

        });
        j = 0;
        jQuery(".product-modifier  input.required_value[type='checkbox']:checked ").each(function(){
            required_set[j++]  = jQuery(this).val(); // This is the jquery object of the input, do what you will

        });

        // console.log(product_modifier_group_name);
        jQuery.ajax({
            type: "POST",
            url: ajaxurl,

            data: {
                action: 'wsm_woosquare_save_fields',
                product_id: product_id,
                product_modifier_group_name : product_modifier_group_name,
                required_set : required_set,
            },
            success: function (output) {
                jQuery(".sucess-msg").show().delay(1000).fadeOut();
            }
        });
    });




});