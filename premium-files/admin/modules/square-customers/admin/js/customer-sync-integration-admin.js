jQuery(document).ready(function($) {
    $('.deleteCreditCard').click(function (e) {
        var result = confirm("Are you sure to delete this credit card?");
        if (!result) {
            e.preventDefault()
        }
    });
	
	function showAndHideSyncDuration() {
		if (jQuery("[name='woo_square_customer_auto_sync']:checked").val() == "1") {
			jQuery('#auto_customer_sync_duration_div').show();
		} else {
			jQuery('#auto_customer_sync_duration_div').hide();
		}
	}
	
	//cron settings on change event
    jQuery("[name='woo_square_customer_auto_sync']").on('change', function(){
        showAndHideSyncDuration();
    });
})
