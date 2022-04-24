jQuery( document ).ready(function() {

    jQuery('.single_add_to_cart_button ').on('submit', '.cart',  function(e) {
        alert();
        e.preventDefault();
        if (jQuery('input, select').not('[type="button"]').filter(function() {
                return this.value!="";
            }).length) {
            alert('valid');
            //$("#myForm").submit()
        }
    });
});