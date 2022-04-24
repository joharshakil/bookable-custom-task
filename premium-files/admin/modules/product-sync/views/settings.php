<div class="bodycontainerWrap">
    <?php if ($successMessage): ?>
    <div class="updated">
        <p><?php echo $successMessage; ?></p>
    </div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
    <div class="error">
        <p><?php echo $errorMessage; ?></p>
    </div>
    <?php endif; ?>


    <?php if (get_option('woo_square_access_token')): ?>

    <div class="bodycontainer">

        <div id="tabs" class="md-elevation-4dp bg-theme-primary">
             <?php  $Woosquare_Plus = new Woosquare_Plus(); echo $Woosquare_Plus->wooplus_get_toptabs(); ?>
        </div>

        <div class="welcome-panel ext-panel <?=$_GET['page']?>-1">
            <h1><svg height="20px" viewBox="0 0 512 511" width="20px" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="m405.332031 256.484375c-11.796875 0-21.332031 9.558594-21.332031 21.332031v170.667969c0 11.753906-9.558594 21.332031-21.332031 21.332031h-298.667969c-11.777344 0-21.332031-9.578125-21.332031-21.332031v-298.667969c0-11.753906 9.554687-21.332031 21.332031-21.332031h170.667969c11.796875 0 21.332031-9.558594 21.332031-21.332031 0-11.777344-9.535156-21.335938-21.332031-21.335938h-170.667969c-35.285156 0-64 28.714844-64 64v298.667969c0 35.285156 28.714844 64 64 64h298.667969c35.285156 0 64-28.714844 64-64v-170.667969c0-11.796875-9.539063-21.332031-21.335938-21.332031zm0 0" />
                    <path
                        d="m200.019531 237.050781c-1.492187 1.492188-2.496093 3.390625-2.921875 5.4375l-15.082031 75.4375c-.703125 3.496094.40625 7.101563 2.921875 9.640625 2.027344 2.027344 4.757812 3.113282 7.554688 3.113282.679687 0 1.386718-.0625 2.089843-.210938l75.414063-15.082031c2.089844-.429688 3.988281-1.429688 5.460937-2.925781l168.789063-168.789063-75.414063-75.410156zm0 0" />
                    <path
                        d="m496.382812 16.101562c-20.796874-20.800781-54.632812-20.800781-75.414062 0l-29.523438 29.523438 75.414063 75.414062 29.523437-29.527343c10.070313-10.046875 15.617188-23.445313 15.617188-37.695313s-5.546875-27.648437-15.617188-37.714844zm0 0" />
                </svg> Synchronization of Products Settings</h1>

            <?php if ( $currencyMismatchFlag ){ ?>
            <br />
            <div id="woo_square_error" class="error" style="background: #ddd;">
                <p style="color: red;font-weight: bold;">The currency code of your Square account [
                    <?php echo $squareCurrencyCode ?> ] does not match WooCommerce [ <?php echo $wooCurrencyCode ?> ]
                </p>
            </div>
            <?php }
				
			if(empty(get_option('woo_square_merging_option'))){
				update_option('woo_square_merging_option', 1);
				update_option('sync_on_add_edit', 1);
				update_option('sync_square_order_notify','');
				update_option('html_sync_des','');	
				
			}
			
			
			?>
            <form method="post" <?php if ($currencyMismatchFlag): ?> style="opacity:0.5;pointer-events:none;"
                <?php endif; ?>>
                <input type="hidden" value="1" name="woo_square_settings" />


                <div class="formWrap">

                    <ul>
                        <li>
                            <strong>Auto Synchronize</strong>
                            <div class="elementBlock">
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_auto_sync'))?'checked':''; ?> value="1"
                                        name="woo_square_auto_sync"> On </label>
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_auto_sync'))?'':'checked'; ?> value="0"
                                        name="woo_square_auto_sync"> Off </label>
                            </div>

                            <ul class="subData auto_sync_duration_div"
                                style="<?php echo (get_option('woo_square_auto_sync'))?'':'display: none';?>">
                                <li class="auto_sync_duration_div">
                                    <strong>Auto Sync each</strong>
                                    <div class="elementBlock">
                                        <select name="woo_square_auto_sync_duration">
                                            <option
                                                <?php if (get_option('woo_square_auto_sync_duration') == '60'): ?>selected=""
                                                <?php endif; ?> value="60"> 1 hour </option>
                                            <option
                                                <?php if (get_option('woo_square_auto_sync_duration') == '720'): ?>selected=""
                                                <?php endif; ?> value="720"> 12 hours </option>
                                            <option
                                                <?php if (get_option('woo_square_auto_sync_duration') == '1440'): ?>selected=""
                                                <?php endif; ?> value="1440"> 24 hours </option>
                                        </select>
                                    </div>

                                </li>
                                <li>
                                    <strong>Merging Option</strong>

                                    <div class="elementBlock">
                                        <label><input type="radio"
                                                <?php echo (get_option('woo_square_merging_option') == "1")?'checked':''; ?>
                                                value="1" class='woo_square_merging_option'
                                                name="woo_square_merging_option">
                                                WooCommerce Product Override

                                            <p class="help-text help-text2">Products on WooCommerce will override the data of the items on Square</p>
                                        </label>
                                        <label class="m-l-10"><input type="radio"
                                                <?php echo (get_option('woo_square_merging_option') == "2")?'checked':''; ?>
                                                value="2" class='woo_square_merging_option'
                                                name="woo_square_merging_option">
                                                Square Product Override
                                            <p class="help-text help-text2">Items on Square will override the data of the Products on WooCommerce</p>
                                        </label>

                                    </div>
                                </li>
                                <li class="">
                                    <strong>Sync Preference</strong>

                                    <div class="elementBlock ">

                                        <label><input type="radio"
                                                <?php echo (get_option('woo_square_sync_preference'))?'checked':''; ?>
                                                value="1" name="woo_square_sync_preference"> All</label>&nbsp;
                                        <label><input type="radio"
                                                <?php echo (get_option('woo_square_sync_preference'))?'':'checked'; ?>
                                                value="0" name="woo_square_sync_preference"> <a
                                                class='woo_square_sync_preference'>Specific Products </a></label>
                                        <?php if(
                                        !empty(get_option('woo_square_listsaved_products_square')) 
                                            || 
                                        !empty(get_option('woo_square_listsaved_products_wooco'))){  ?>
                                        &nbsp;&nbsp;&nbsp; <br /> <br />

                                        <a class='woo_square_sync_preference'> Edit List </a>
                                        <?php } ?>


                                    </div>
                                </li>
                            </ul>
                        </li>







                        <li class="">

                            <strong>Sync on edit in WooCommerce</strong>

                            <p class="description ext">By enabling this option your products in square will get
                                updated on every edit, update and delete in woocommerce.</p>

                            <div class="elementBlock">

                                <label><input type="radio"
                                        <?php echo (get_option('sync_on_add_edit') == "1")?'checked':''; ?> value="1"
                                        name="sync_on_add_edit"> Yes </label>
                                <label><input type="radio"
                                        <?php echo (get_option('sync_on_add_edit') == "2")?'checked':''; ?> value="2"
                                        name="sync_on_add_edit"> No </label>

                                <div class='pro_fields'> <?php 
                                    $edit_fields = get_option('woosquare_pro_edit_fields');  
                                    if(empty($edit_fields)){
                                        $edit_fields = array(); 
                                    }
                                    ?>
                                    Select Product field to be sync after edit.

                                    <div>
                                        <label><input type="checkbox"
                                                <?php echo (in_array("title", $edit_fields))?'checked':''; ?>
                                                value="title" name="woosquare_pro_edit_fields[]">
                                            Title</label>
                                    </div>
                                    <div>
                                        <label><input type="checkbox"
                                                <?php echo (in_array("description", $edit_fields))?'checked':''; ?>
                                                value="description" name="woosquare_pro_edit_fields[]">
                                            Description</label>
                                    </div>
                                    <div>
                                        <label><input type="checkbox"
                                                <?php echo (in_array("price", $edit_fields))?'checked':''; ?>
                                                value="price" name="woosquare_pro_edit_fields[]">
                                            Price</label>
                                    </div>
                                    <div>
                                        <label><input type="checkbox"
                                                <?php echo (in_array("stock", $edit_fields))?'checked':''; ?>
                                                value="stock" name="woosquare_pro_edit_fields[]">
                                            Stock</label>
                                    </div>
                                    <div>
                                        <label><input type="checkbox"
                                                <?php echo (in_array("category", $edit_fields))?'checked':''; ?>
                                                value="category" name="woosquare_pro_edit_fields[]">
                                            Category</label>
                                    </div>
                                    <div>
                                        <label><input type="checkbox"
                                                <?php echo (in_array("pro_image", $edit_fields))?'checked':''; ?>
                                                value="pro_image" name="woosquare_pro_edit_fields[]">
                                            Product Image</label>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li>
                            <strong>Disable auto delete</strong>
                            <div class="description ext">By enabling this option you would have to manually delete
                                the items from square and WooCommerce.</div>

                            <div class="elementBlock">
                                <label><input type="checkbox"
                                        <?php echo (get_option('disable_auto_delete') == "1")?'checked':''; ?> value="1"
                                        name="disable_auto_delete"> Yes </label>
                            </div>
                        </li>

                   
                        <li>
                            <strong>Enable WooCommerce description synchronization with html ?</strong>
                            <div class="elementBlock">
                                <label><input type="checkbox"
                                        <?php echo (get_option('html_sync_des') == "1")?'checked':''; ?> value="1"
                                        name="html_sync_des"> Yes </label>
                            </div>
                        </li>


                    </ul>

                </div>

                <div class="row m-t-20">
                    <div class="col-md-4">
                        <span class="submit">
                            <input type="submit" value="Save Changes"
                                class="btn waves-effect waves-light btn-rounded btn-success">
                        </span>
                    </div>
                    <div class="col-md-8 text-right">

                        <?php 
                        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
                        $activate_modules_woosquare_plus = get_option('activate_modules_woosquare_plus',true);
                        ?>
                        <span class=" <?=$_GET['page']?>-2"
                            <?php if(($woocommerce_square_plus_settings['enable_sandbox']) == 'yes'){ echo 'style="pointer-events:none;"'; } if ($currencyMismatchFlag): ?>
                            style="opacity:0.5;pointer-events:none;" <?php endif; ?>>

                          

                            <a <?php if(($woocommerce_square_plus_settings['enable_sandbox']) == 'yes' ){ echo 'style="opacity: 0.5;"'; } ?>
                                class="btn waves-effect waves-light btn-rounded btn-secondary load-customize hide-if-no-customize"
                                href="javascript:void(0)" id="manual_sync_wootosqu_btn"> Synchronize Woo To Square </a>
                            <a <?php if(($woocommerce_square_plus_settings['enable_sandbox']) == 'yes' ){ echo 'style="opacity: 0.5;"'; } ?>
                                class="btn waves-effect waves-light btn-rounded btn-secondary load-customize hide-if-no-customize m-l-10"
                                href="javascript:void(0)" id="manual_sync_squtowoo_btn"> Synchronize Square To Woo </a>

                                <?php 
                            
                                if(
                                    ($woocommerce_square_plus_settings['enable_sandbox']) == 'yes'){ ?>
                                <span class="noallwoed">Product Sync functionality only works with live Square Api .</span>
                                <?php } ?>
                        </span>

                    </div>
                </div>


                
            </form>

        </div>

    </div>


</div>



<div class="cd-popup" role="alert" style="display:none;">
    <div class="cd-popup-container">
        <div id="sync-loader">
            <img width=50%; height=50% src="<?php echo plugins_url( '_inc/images/ring.gif', dirname(__FILE__) );?>"
                alt="loading">
        </div>
        <div id="sync-error"></div>
        <div id="sync-content" style="display:none;">
            <div id="sync-content-woo">
            </div>
            <div id="sync-content-square">
            </div>
        </div>
        <ul class="cd-buttons start">
            <li class="liWide"><button id="start-process" href="#" class="btn btn-rounded btn-block btn-info">Start Synchronization</button></li>
            <!-- <li><button class="cancel-process btn btn-rounded btn-block btn-danger" href="#0">Cancel</button></li> -->
        </ul>
        <ul class="cd-buttons end">
            <li><button id="sync-processing" href="#0" class="btn btn-rounded btn-warning">Close</button></li>
        </ul>
        <a href="#0" class="cd-popup-close img-replace"></a>
    </div> <!-- cd-popup-container -->
</div> <!-- cd-popup -->


<?php endif; ?>