(function(){
function showAndHideSyncDuration() {
    if (jQuery("[name='woo_square_auto_sync']:checked").val() == "1") {
        jQuery('.auto_sync_duration_div').show();
    } else {
        jQuery('.auto_sync_duration_div').hide();
    }
}

function manualSync(event) {
	
	
    jQuery('#woo_square_error').remove();
    var way = event.data.name;
    jQuery("#manual_sync_"+way+"_btn").off("click");
    var button = jQuery("#manual_sync_"+way+"_btn");
    var old_text = button.text();
    button.text('Processing ...');
    button.attr('disabled', true);
    jQuery.ajax({
        type: "GET",
        url: myAjax.ajaxurl,
        data: 'action=manual_sync&way=' + way,
        success: function (html)
        {
            if(html){
                jQuery('#manual_sync_wootosqu_btn').parents('.welcome-panel').before('<div id="woo_square_error" class="error"><p>'+html+'</p></div>')
            }
            button.text(old_text);
            button.attr('disabled', false);
            jQuery("#manual_sync_"+way+"_btn").on("click", {name: way}, manualSync);
        }
    });
}

function initPopup(){
    jQuery("#sync-error,#sync-content-woo,#sync-content-square").html('');
    jQuery("#sync-content,#sync-error,.cd-buttons.end,.cd-buttons.start").hide();
    jQuery("#sync-loader").show();
    jQuery('.cd-popup').addClass('is-visible');
}

function processPopup(){
    jQuery('.cd-buttons.start').hide();
    jQuery('#sync-processing').text('Processing ...').prop('disabled', 'true');
    jQuery('.cd-buttons.end').show();
    
    //disable all checkboxes
    jQuery("#sync-content input:checkbox").prop('disabled', 'true');
    
}

function endPopup(){
    jQuery('#sync-processing').text('Close');
    jQuery('#sync-processing').prop('disabled', false);


}




function  show_woo_popup(action) {
	jQuery('.cd-popup').css("display", "block");
    sync.caller = "woo";
    jQuery('#start-process').data("caller",sync.caller);
    initPopup();
	var customparams = '';
	var page = 1;
	var limit;
	var totalPages;
	if(action == 'optionsaved'){
		customparams = '&optionsaved=true&from=woo';
	}
    getitems(myAjax,action,customparams,page,limit);
}

function getitems(myAjax,action,customparams,page,limit){
	
    jQuery.ajax({
        type: "GET",
        url: myAjax.ajaxurl,
        data: 'action=get_non_sync_woo_data'+customparams,
        success: function (response)
        {

            //ensure last user ckick was on woo->square
            if (sync.caller === 'woo'){
                response = JSON.parse(response);
                if(response.error){
                    jQuery("#sync-content, #sync-loader").hide();
                    jQuery("#sync-error").show().html(response.error);
                    endPopup();
                    return;
                }else if(!response.data){
                    return;
                }
                // response = response.data;
				
				
				

                jQuery("#sync-loader").hide();
				if(response.offset == 0){
				 jQuery("#sync-content-"+sync.caller).html(response.data);
				}
                jQuery("#sync-content,.cd-buttons.start").show();
				if(page != 1){
					var filtered = jQuery(jQuery.parseHTML(response.data));
					var filte = filtered.find('#sync-product').html();
					jQuery( filte ).appendTo( "#sync-product" );
				}
				if(action == 'optionsaved'){
					var cusurl = '&optionsaved=true&from=woo';
					var btntxt = 'UPDATE';
					jQuery('#start-process').text(btntxt).delay(3000);
					jQuery('#sync-product h3').hide();
					//jQuery('#sync-category h3').hide();
				} else {
					var cusurl = '';
					var btntxt = 'Start Synchronization';
					jQuery('#start-process').text(btntxt).delay(3000);
				}
				if(page < response.totalPages){
					jQuery('#start-process').text('PRODUCTS LOADING...').delay(3000);
					jQuery('#start-process').attr('disabled', true);
					page = page+1;
					customparams = '';
					customparams = cusurl;
					customparams = customparams+'&limit='+response.limit+'&page='+page;
					
					getitems(myAjax,action,customparams,page,limit)
				} else {
					jQuery('#start-process').text(btntxt).delay(3000);
					jQuery('#start-process').attr('disabled', false);
				}
            }
          /*  jQuery('.check:button').toggle(function(){
				var chfrom = jQuery(this).attr('class').split(' ');
				if('extpro' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_product"]:checkbox').attr('checked','checked');
					jQuery(this).val('Uncheck All')
				} else if('extcat' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_category"]:checkbox').attr('checked','checked');
					jQuery(this).val('Uncheck All')  
				}
			},function(){
				var chfrom = jQuery(this).attr('class').split(' ');
				if('extpro' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_product"]:checkbox').removeAttr('checked');
					jQuery(this).val('Check All');       
				} else if('extcat' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_category"]:checkbox').removeAttr('checked');
					jQuery(this).val('Check All');       
				}
				 
			});*/
        }
    });
}
function  show_square_popup(action) {
	jQuery('.cd-popup').css("display", "block");
    sync.caller = "square";
    jQuery('#start-process').data("caller",sync.caller);
    initPopup();
	
	var customparams = '';
	if(action == 'optionsaved'){
		customparams = '&optionsaved=true&from=square';
	}
    jQuery.ajax({
        type: "GET",
        url: myAjax.ajaxurl,
        data: 'action=get_non_sync_square_data'+customparams,
        success: function (response)
        {           
            //ensure last user ckick was on square->woo
            if (sync.caller === 'square'){
                response = JSON.parse(response);           
                if(response.error){
                    jQuery("#sync-content, #sync-loader").hide();
                    jQuery("#sync-error").show().html(response.error);
                    endPopup();
                    return;
                }else if(!response.data){
                    return;
                }

                response = response.data;

                jQuery("#sync-loader").hide();
                jQuery("#sync-content-"+sync.caller).html(response);
                jQuery("#sync-content,.cd-buttons.start").show();
				if(action == 'optionsaved'){
					jQuery('#start-process').text('UPDATE').delay(3000);
					jQuery('#sync-product h3').hide();
					//jQuery('#sync-category h3').hide();
				} else {
					jQuery('#start-process').text('Start Synchronization').delay(3000);
				}
				
            } 
	/*		 jQuery('.check:button').toggle(function(){
				var chfrom = jQuery(this).attr('class').split(' ');
				if('extpro' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_product"]:checkbox').attr('checked','checked');
					jQuery(this).val('Uncheck All')
				} else if('extcat' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_category"]:checkbox').attr('checked','checked');
					jQuery(this).val('Uncheck All')  
				}
			},function(){
				var chfrom = jQuery(this).attr('class').split(' ');
				if('extpro' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_product"]:checkbox').removeAttr('checked');
					jQuery(this).val('Check All');       
				} else if('extcat' == chfrom[7]){
					jQuery('.square-action input[name*="woo_square_category"]:checkbox').removeAttr('checked');
					jQuery(this).val('Check All');       
				}
				 
			});*/
                       
        }
    });
}


var sync = [];
sync.product = [];
sync.category = [];
sync.caller = '';

function startManualSync(caller) {
      
    processPopup();
	// if(caller == 'listsaved_square' || caller == 'listsaved_woo'){
		var sync = [];
		sync.product = [];
		sync.category = [];
	// }
    jQuery("#sync-product input:checkbox[name=woo_square_product]:checked").each(function(){
           sync.product.push(jQuery(this).val());
    });
    jQuery("#sync-category input:checkbox[name=woo_square_category]:checked").each(function(){
           sync.category.push(jQuery(this).val());
    });
	
	
	if(caller == 'listsaved_square' || caller == 'listsaved_woo'){
		var action = 'listsaved';
		var method = "POST";
		var ajAxdata =  { 
				action: action ,
				products: JSON.stringify(sync.product),
				categories: JSON.stringify(sync.category), 
				saveto: caller=='listsaved_square'?'square':'wooco', 
				};
	} else {
		var action = caller=='woo'?'woo_to_square':'square_to_woo';
		var method = "GET";
		var ajAxdata =  'action='+"start_manual_"+action+"_sync";
	}
    
	
	 
    jQuery.ajax({
        type: method,
        url: myAjax.ajaxurl,
        data: ajAxdata,
        success: function (response)
        {
            if(response.trim() == '1'){
				if(caller == 'listsaved_square' || caller == 'listsaved_woo'){
					jQuery("#sync-content").hide();
					jQuery("#sync-error").show().html("<span class=\"dashicons dashicons-yes right\"></span>Sync Preference Successfully Saved!");
					jQuery('#sync-processing').text('Close');
					jQuery('#sync-processing').prop('disabled', false);
					
					  
				} else {
					syncCategoryOrProduct(caller, 'category',sync);
				}
                
            }else{
                jQuery("#sync-content").hide();
                jQuery("#sync-error").show().html(response);
            }
        },
        error: function (response)
        {
            jQuery("#sync-content").hide();
			 
            jQuery("#sync-error").show().html("Error occurred!");
        }
    }); 
     
}


function syncCategoryOrProduct(caller, target,sync){
   
    var currentProdId = sync[target].shift();
	
    if( !currentProdId){
        if (target == 'category'){
            syncCategoryOrProduct(caller, 'product',sync);
        }else{
            terminateManualSync(jQuery('#start-process').data("caller"));
        }
        return;
    }
    var action = caller=='woo'?'sync_woo_'+target+'_to_square':
            'sync_square_'+target+'_to_woo';
    
    jQuery.ajax({
        type: "POST",
        url: myAjax.ajaxurl,
        data: 'action='+action+'&id=' + currentProdId,
        success: function (response)
        {
            if (response == 1){
              /*  console.log(response);
                alert("sss");
                console.log('#sync-'+target);*/
                if (target == 'category'){
                       jQuery("#sync-"+target+" input:checkbox[name=woo_square_"+target+"].woo_square_category[value="+currentProdId+"]").parent("div").append("<span class='dashicons dashicons-yes right'></span>").addClass('sync-success');

                } else {
                    jQuery("#sync-"+target+" input:checkbox[name=woo_square_"+target+"].woo_square_product[value="+currentProdId+"]").parent("div").append("<span class='dashicons dashicons-yes right'></span>").addClass('sync-success');
                    
                }
            }else{
				if(currentProdId == "update_products"){
					var ress = JSON.parse( response );
					var len = ress.length-1;
					if(ress.length > 0){
						var i = 0;  
						
						var xReturn = ajaxCall(ress,len,my_ajax_backend_scripts,i);
					}
					 jQuery("#sync-"+target+" input:checkbox[name=woo_square_"+target+"][value="+currentProdId+"]").parent("div").append("<span class='dashicons dashicons-yes right'></span>").addClass('sync-success');
				} else {
                 //   console.log(response);
                    jQuery("#sync-"+target+" input:checkbox[name="+target+"][value="+currentProdId+"]").parent("div").append("<span class='dashicons dashicons-no-alt right'></span>").addClass('sync-failure');
				}
            }
            
            
        },
        error: function (error){
            jQuery("#sync-"+target+" input:checkbox[name=woo_square_"+target+"][value="+currentProdId+"]").parent("div").append("<span class='dashicons dashicons-no-alt right'></span>").addClass('sync-failure');
        },
        complete: function (){
            syncCategoryOrProduct(caller, target,sync);

        }
    });
}


function ajaxCall(ress,len,my_ajax_backend_scripts,i){
	console.log(JSON.stringify(ress[i]));
    jQuery.ajax({
		type: "POST",
		url: my_ajax_backend_scripts.ajax_url,
		data: 'action=update_square_to_woo&import_js_item=' + JSON.stringify(ress[i])+'&session_targets='+JSON.stringify(ress[len]),
	}).always(function(html) {
			i++;
			jQuery('#sync-processing').text('Processing ...').prop('disabled', 'true');
			jQuery('#sync-processing').prop('disabled', true);
			if (typeof ress[i].name  !== "undefined"){			
				jQuery('<div class="square-action sync-success"><input name="woo_square_product" type="checkbox" value="update_products" checked="" disabled="">'+ress[i].name+'<span class="dashicons dashicons-yes right"></span> </div>').appendTo('.square-update');
				jQuery('.sync-data').animate({scrollTop:jQuery('.sync-elements').height()+jQuery('.square-update').height()}, 'fast');
				
			}
			if(ress[i].name){
					console.log(ress[i].name);
					ajaxCall(ress,len,my_ajax_backend_scripts,i);
				
			} else {
				jQuery('#sync-processing').text('Close');
				jQuery('#sync-processing').prop('disabled', false);
			}
			
			// var totalTime = new Date().getTime()-ajaxTime;
			var htmls = jQuery.parseJSON( html );
			// jQuery('.store_id_'+stores_json_encoded[del].Code).css('background-color','#dff0d8');
			 // setTimeout(function(){ 		
				// jQuery('.store_id_'+stores_json_encoded[del].Code).css('text-decoration','line-through');
				// jQuery('.store_id_'+stores_json_encoded[del].Code +' + br').remove();
				// jQuery('.store_id_'+stores_json_encoded[del].Code).fadeOut('slow');
				// jQuery('#current-count').text(del);
				// del++;
			// }, 500); 
	  });
}

/* 
function ajaxDone(ress,len,my_ajax_backend_scripts){

    // if successful, place second call
    // if(parseInt(msg)==1){
        xReturn = ajaxCall(ress,len,my_ajax_backend_scripts);

        // Bind a callback to this *new* object
        xReturn.success(ajaxDone);
    // }
} */

function terminateManualSync(caller){
    jQuery.ajax({
        type: "POST",
        url: myAjax.ajaxurl,
        data: 'action=terminate_manual_'+caller+'_sync',
        success: function (html)
        {
            endPopup();            
        }
    });
}





//Bind events to the page
jQuery(document).ready(function (jQuery) {
	
	
    jQuery("#manual_sync_squtowoo_btn").on("click", {name: 'squtowoo'}, show_square_popup);
    jQuery("#manual_sync_wootosqu_btn").on("click", {name: 'wootosqu'}, show_woo_popup);
	
    //pop-up
    //close popup
    jQuery('.cd-popup').on('click', function (event) {
        if (jQuery(event.target).is('.cd-popup-close') || jQuery(event.target).is('.cd-popup')) {
            event.preventDefault();
            jQuery(this).removeClass('is-visible');           
            // terminateManualSync(jQuery('#start-process').data("caller"));           

        }
    });
    //close popup when clicking the esc keyboard button
    jQuery(document).keyup(function (event) {
        if (event.which == '27') {
            jQuery('.cd-popup').removeClass('is-visible');
            // terminateManualSync(jQuery('#start-process').data("caller"));
        }
    });
    
    //cron settings on change event
    jQuery("[name='woo_square_auto_sync']").on('change', function(){
        showAndHideSyncDuration();
    });
	
	
    jQuery(".woo_square_sync_preference").on('click', function(){
		
		
		if(jQuery("[name='woo_square_sync_preference']:checked").val() != 1){
			if(jQuery(this).val() == 0){
				if(jQuery("[name='woo_square_merging_option']:checked").val() == 1){
					show_woo_popup('optionsaved');
					jQuery('#start-process').data("caller",'listsaved_woo');
				} else if(jQuery("[name='woo_square_merging_option']:checked").val() == 2){
					show_square_popup('optionsaved');
					jQuery('#start-process').data("caller",'listsaved_square');
				}
				
			}
		}
		
	});
    jQuery("[name='woo_square_sync_preference']").on('change', function(){
		
		if(jQuery(this).val() == 0){
			if(jQuery("[name='woo_square_merging_option']:checked").val() == 1){
				show_woo_popup('optionsaved');
				jQuery('#start-process').data("caller",'listsaved_woo');
			} else if(jQuery("[name='woo_square_merging_option']:checked").val() == 2){
				show_square_popup('optionsaved');
				jQuery('#start-process').data("caller",'listsaved_square');
			}
			
		} 
    });
	
	if(jQuery("[name='sync_on_add_edit']:checked").val() != 1){
		jQuery('.pro_fields').fadeOut();
	}
	
    jQuery("[name='sync_on_add_edit']").on('change', function(){
		
		if(jQuery(this).val() == 1){
			jQuery('.pro_fields').fadeIn();
		} else {
			jQuery('.pro_fields').fadeOut();
		} 
    });
    jQuery("[name='woo_square_merging_option']").on('change', function(){
		var sync = [];
		sync.product = [];
		sync.category = [];
		
		jQuery(".woo_square_merging_option").removeAttr('checked');
		jQuery(this).prop('checked', true);  
    });
    
    jQuery('.cancel-process').on('click', function (event) {
        event.preventDefault();
       jQuery('.cd-popup').removeClass('is-visible');
       terminateManualSync(jQuery('#start-process').data("caller"));
    });
    
    

    jQuery('#start-process').on('click', function (event) {
        event.preventDefault();
		startManualSync(jQuery('#start-process').data("caller"));
		/* if(
			jQuery('#start-process').data("caller") == 'woo'
			||
			jQuery('#start-process').data("caller") == 'square'
			
			){
			startManualSync(jQuery('#start-process').data("caller"));
			} else if(jQuery('#start-process').data("caller") == 'listsaved'){
				
				startManualSync(jQuery('#start-process').data("caller"));
			} */
        // 
    });
    
    jQuery('#sync-processing').on('click', function (event) {
        event.preventDefault();
        jQuery('.cd-popup').removeClass('is-visible');
    });
    
    
    jQuery('.collapse').on('click', function () {
        jQuery(this).siblings('.grid-div').toggleClass( "hidden collapse-content-show" );
        jQuery(this).children(".dashicons").toggleClass('collapse-open')
    });

    showAndHideSyncDuration();
    


});



})();


jQuery(document).ready(function(){
  
	 jQuery(document).on('click','.check:button' ,function(evv){
	     evv.preventDefault();
      
         var chfrom = jQuery(this).attr('class').split(' ');
         if('extpro' == chfrom[7]){
                var checkbox = jQuery('.square-action input[name="woo_square_product"]');
             if(checkbox.attr('checked') =='checked'){
                 	     jQuery('.square-action input[name="woo_square_product"]').removeAttr('checked');
		         jQuery( 'square-action input[name="woo_square_product"]' ).prop( "checked", false);
                 jQuery('.square-action input[name="woo_square_product"].modifier_end').prop('checked', true );
				    jQuery(this).val('check All');
                 
             }else if(checkbox.attr('checked') !='checked'){
                           jQuery('.square-action input[name="woo_square_product"]').attr('checked','checked');
                          jQuery( '.square-action  input[name="woo_square_product"]' ).prop( "checked", true );
                 jQuery('.square-action input[name="woo_square_product"].modifier_end').prop('checked', true );
                         jQuery(this).val('Uncheck All'); 
                 }
             }else if('extcat' == chfrom[7]){
                    
				          var checkbox = jQuery('.square-action input[name="woo_square_category"]');
				            if(checkbox.attr('checked') =='checked'){
				              jQuery('.square-action input[name="woo_square_category"]').removeAttr('checked');
				              jQuery( '.square-action input[name="woo_square_category"]' ).prop( "checked", false);
				              jQuery(this).val('check All');
				            } else if(checkbox.attr('checked') !='checked'){
				                 jQuery('.square-action input[name="woo_square_category"]').attr('checked','checked');
                               jQuery( '.square-action input[name="woo_square_category"]' ).prop( "checked", true );
	                  		jQuery(this).val('Uncheck All'); 
				     }
                   
                 }
        	})	 
	
	
	jQuery( ".subsubsub li" ).each(function(index,key) {
		
		if(jQuery( this ).text().trim() == 'Square'){
			jQuery(this).remove();
			
		}
		if(jQuery( this ).text().trim() == 'Square |'){
			jQuery(this).remove();
		}
	
	   
	 });
		
		var textt=jQuery( ".subsubsub li" ).last().html(  );
		if(textt){
		var splittext = textt.split('|');
		
			jQuery( ".subsubsub li" ).last().html( splittext[0] );
		}
		
		
	
});


