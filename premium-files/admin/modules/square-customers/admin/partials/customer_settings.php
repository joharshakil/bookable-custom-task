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
<?php 

$Woosquare_Plus = new Woosquare_Plus();


?>
        <div id="tabs" class="md-elevation-4dp bg-theme-primary">
           <?php echo $Woosquare_Plus->wooplus_get_toptabs(); ?>
        </div>

        <div class="welcome-panel ext-panel">

            <h1><svg height="20px" viewBox="0 0 512 511" width="20px" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="m405.332031 256.484375c-11.796875 0-21.332031 9.558594-21.332031 21.332031v170.667969c0 11.753906-9.558594 21.332031-21.332031 21.332031h-298.667969c-11.777344 0-21.332031-9.578125-21.332031-21.332031v-298.667969c0-11.753906 9.554687-21.332031 21.332031-21.332031h170.667969c11.796875 0 21.332031-9.558594 21.332031-21.332031 0-11.777344-9.535156-21.335938-21.332031-21.335938h-170.667969c-35.285156 0-64 28.714844-64 64v298.667969c0 35.285156 28.714844 64 64 64h298.667969c35.285156 0 64-28.714844 64-64v-170.667969c0-11.796875-9.539063-21.332031-21.335938-21.332031zm0 0" />
                    <path
                        d="m200.019531 237.050781c-1.492187 1.492188-2.496093 3.390625-2.921875 5.4375l-15.082031 75.4375c-.703125 3.496094.40625 7.101563 2.921875 9.640625 2.027344 2.027344 4.757812 3.113282 7.554688 3.113282.679687 0 1.386718-.0625 2.089843-.210938l75.414063-15.082031c2.089844-.429688 3.988281-1.429688 5.460937-2.925781l168.789063-168.789063-75.414063-75.410156zm0 0" />
                    <path
                        d="m496.382812 16.101562c-20.796874-20.800781-54.632812-20.800781-75.414062 0l-29.523438 29.523438 75.414063 75.414062 29.523437-29.527343c10.070313-10.046875 15.617188-23.445313 15.617188-37.695313s-5.546875-27.648437-15.617188-37.714844zm0 0" />
                </svg> Square Customer Sync Settings </h1>

            <form method="post">
                <input type="hidden" value="1" name="woo_square_customer_settings" />

                <div class="formWrap">
                    <ul>
                        <li>
                            <strong>Auto Synchronize</strong>
                            <div class="elementBlock">
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_auto_sync'))?'checked':''; ?>
                                        value="1" name="woo_square_customer_auto_sync"> On </label>
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_auto_sync'))?'':'checked'; ?>
                                        value="0" name="woo_square_customer_auto_sync"> Off </label>
                            </div>
                        </li>
                        <li id="auto_customer_sync_duration_div"
                        style="<?php echo (get_option('woo_square_customer_auto_sync'))?'':'display: none';?>">
                            <strong>Auto Sync each</strong>
                            <div class="elementBlock">
                                <select name="woo_square_customer_auto_sync_duration">
                                    <option
                                        <?php if (get_option('woo_square_customer_auto_sync_duration') == '60'): ?>selected=""
                                        <?php endif; ?> value="60"> 1 hour </option>
                                    <option
                                        <?php if (get_option('woo_square_customer_auto_sync_duration') == '720'): ?>selected=""
                                        <?php endif; ?> value="720"> 12 hours </option>
                                    <option
                                        <?php if (get_option('woo_square_customer_auto_sync_duration') == '1440'): ?>selected=""
                                        <?php endif; ?> value="1440"> 24 hours </option>
                                </select>
                            </div>
                        </li>
                        <li>
                            <strong>Merging Option</strong>
                            <div class="elementBlock">
                                <label class="m-r-10"><input type="radio"
                                    <?php echo (get_option('woo_square_customer_merging_option') == "1")?'checked':''; ?>
                                    value="1" name="woo_square_customer_merging_option"> WooCommerce Customer Override
                                    <p class="help-text help-text2">Customers on WooCommerce will override the data of the customers on Square</p>
                                </label>
                            <label><input type="radio"
                                    <?php echo (get_option('woo_square_customer_merging_option') == "2")?'checked':''; ?>
                                    value="2" name="woo_square_customer_merging_option"> Square Customer Override
                                    <p class="help-text help-text2">Customers on Square will override the data of the customers on WooCommerce</p>
                                </label>
                            </div>
                        </li>
                        <li>
                            <strong>Do you want to synchronize your customer’s data after every add, update or delete event in WooCommerce?</strong>
                            <p class="description ext">Sync and update your customer's data every time there is a change in their record (addition, update, deletion) on WooCommerce.</p>
                                <div class="elementBlock">
                                    <label><input type="radio"
                                        <?php echo (get_option('sync_on_customer_add_edit') == "1")?'checked':''; ?>
                                        value="1" name="sync_on_customer_add_edit"> Yes </label>
                                <label><input type="radio"
                                        <?php echo (get_option('sync_on_customer_add_edit') == "2")?'checked':''; ?>
                                        value="2" name="sync_on_customer_add_edit"> No </label>
                                </div>
                        </li>
                         <li>
                            <strong>Enable customer sync on square order syncc</strong>
                            <div class="elementBlock">
                               <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_sync_square_order_sync') == "1")?'checked':''; ?>
                                        value="1" name="woo_square_customer_sync_square_order_sync"> Yes </label>
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_sync_square_order_sync') == "2")?'checked':''; ?>
                                        value="2" name="woo_square_customer_sync_square_order_sync"> No </label>
                          </div>
                        </li>
                        <li>
                             <strong>Enable create customer on guest checkout with square payment</strong>
                                   <div class="elementBlock">
                                <label><input type="radio"   <?php echo (get_option('woo_square_create_customer_guest') == "1")?'checked':''; ?>
                                     value="1" name="woo_square_create_customer_guest"> Yes </label>
                                       <label><input type="radio" <?php echo (get_option('woo_square_create_customer_guest') == "2")?'checked':''; ?>
                                          value="2" name="woo_square_create_customer_guest"> No </label>
                                                       </div>
                                                 </li>
                    </ul>

                  <div class="row m-t-20">
                    <div class="col-md-6">
                        <span class="submit">
                            <input type="submit" value="Save Changes" class="btn waves-effect waves-light btn-rounded btn-success">
                        </span>
                    </div>
                  </div>

                 

                </div>
                <!-- <table class="form-table"> -->
                    <!-- <tbody> -->

                        <!-- <tr>
                            <th scope="row"><label>Auto Synchronize</label></th>
                            <td>
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_auto_sync'))?'checked':''; ?>
                                        value="1" name="woo_square_customer_auto_sync"> On </label>
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_auto_sync'))?'':'checked'; ?>
                                        value="0" name="woo_square_customer_auto_sync"> Off </label>
                            </td>
                        </tr> -->
                        <!-- <tr id="auto_customer_sync_duration_div"
                            style="<?php echo (get_option('woo_square_customer_auto_sync'))?'':'display: none';?>">
                            <th scope="row">Auto Sync each</th>
                            <td>
                                <select name="woo_square_customer_auto_sync_duration">
                                    <option
                                        <?php if (get_option('woo_square_customer_auto_sync_duration') == '60'): ?>selected=""
                                        <?php endif; ?> value="60"> 1 hour </option>
                                    <option
                                        <?php if (get_option('woo_square_customer_auto_sync_duration') == '720'): ?>selected=""
                                        <?php endif; ?> value="720"> 12 hours </option>
                                    <option
                                        <?php if (get_option('woo_square_customer_auto_sync_duration') == '1440'): ?>selected=""
                                        <?php endif; ?> value="1440"> 24 hours </option>
                                </select>
                            </td>
                        </tr> -->
                        <!-- <tr>
                            <th scope="row"><label>Merging Option</label></th>
                            <td>
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_merging_option') == "1")?'checked':''; ?>
                                        value="1" name="woo_square_customer_merging_option"> Woo commerce customer
                                    Override square customer</label><br><br>
                                <label><input type="radio"
                                        <?php echo (get_option('woo_square_customer_merging_option') == "2")?'checked':''; ?>
                                        value="2" name="woo_square_customer_merging_option"> Square customer Override
                                    Woo commerce customer</label><br><br>
                            </td>
                        </tr> -->
                        <!-- <tr>
                            <th scope="row"><label>Would you like to synchronize your customer's on every Add , update
                                    or delete events in WooCommerce?</label></th>
                            <td>
                                <label><input type="radio"
                                        <?php echo (get_option('sync_on_customer_add_edit') == "1")?'checked':''; ?>
                                        value="1" name="sync_on_customer_add_edit"> Yes </label><br><br>
                                <label><input type="radio"
                                        <?php echo (get_option('sync_on_customer_add_edit') == "2")?'checked':''; ?>
                                        value="2" name="sync_on_customer_add_edit"> No </label><br><br>
                            </td>
                        </tr> -->



                    <!-- </tbody> -->
                <!-- </table> -->
                <!-- <p class="submit">
                    <input type="submit" value="Save Changes" class="button button-primary">
                </p> -->
            </form>

        </div>

    </div>

</div>

<?php endif; ?>