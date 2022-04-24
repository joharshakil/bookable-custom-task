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
    <div class="bodycontainer">
	
        <div id="tabs" class="md-elevation-4dp bg-theme-primary">
            <?php  $Woosquare_Plus = new Woosquare_Plus(); echo $Woosquare_Plus->wooplus_get_toptabs(); ?>
        </div>

        <?php if (get_option('woo_square_access_token')): ?>

        <div class="welcome-panel ext-panel <?=$_GET['page']?>">
            <!-- <h3>Save Cards at Checkout Settings</h3> -->
            <h1><svg height="20px" viewBox="0 0 512 511" width="20px" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="m405.332031 256.484375c-11.796875 0-21.332031 9.558594-21.332031 21.332031v170.667969c0 11.753906-9.558594 21.332031-21.332031 21.332031h-298.667969c-11.777344 0-21.332031-9.578125-21.332031-21.332031v-298.667969c0-11.753906 9.554687-21.332031 21.332031-21.332031h170.667969c11.796875 0 21.332031-9.558594 21.332031-21.332031 0-11.777344-9.535156-21.335938-21.332031-21.335938h-170.667969c-35.285156 0-64 28.714844-64 64v298.667969c0 35.285156 28.714844 64 64 64h298.667969c35.285156 0 64-28.714844 64-64v-170.667969c0-11.796875-9.539063-21.332031-21.335938-21.332031zm0 0" />
                    <path
                        d="m200.019531 237.050781c-1.492187 1.492188-2.496093 3.390625-2.921875 5.4375l-15.082031 75.4375c-.703125 3.496094.40625 7.101563 2.921875 9.640625 2.027344 2.027344 4.757812 3.113282 7.554688 3.113282.679687 0 1.386718-.0625 2.089843-.210938l75.414063-15.082031c2.089844-.429688 3.988281-1.429688 5.460937-2.925781l168.789063-168.789063-75.414063-75.410156zm0 0" />
                    <path
                        d="m496.382812 16.101562c-20.796874-20.800781-54.632812-20.800781-75.414062 0l-29.523438 29.523438 75.414063 75.414062 29.523437-29.527343c10.070313-10.046875 15.617188-23.445313 15.617188-37.695313s-5.546875-27.648437-15.617188-37.714844zm0 0" />
                </svg> Save Cards at Checkout Settings</h1>

            <form method="post">
                <input type="hidden" value="1" name="woo_square_card_settings" />

                <div class="formWrap">
                    <ul>
                        <li>
                            <strong>Customers Can Add Credit card from "My Account Page".</strong>
                            <div class="elementBlock">
                                <label><input type="radio"
                                        <?php echo (get_option('cust_add_myaccount') == "1")?'checked':''; ?> value="1"
                                        name="cust_add_myaccount"> Yes </label>
                                <label><input type="radio"
                                        <?php echo (get_option('cust_add_myaccount') == "2")?'checked':''; ?> value="2"
                                        name="cust_add_myaccount"> No </label>
                            </div>
                        </li>
                    </ul>

                    <div class="row">
                        <div class="col-md-12">
                            <p class="submit">
                                <input type="submit" value="Save Changes" class="btn waves-effect waves-light btn-rounded btn-success">
                            </p>
                        </div>
                    </div>

                </div>

                <!-- <table class="form-table">
                <tbody>
                    <tr>
                        <td><label>Customers Can Add Credit card from there My Account Page.</label>
                            <label><input type="radio"
                                    <?php echo (get_option('cust_add_myaccount') == "1")?'checked':''; ?> value="1"
                                    name="cust_add_myaccount"> Yes </label>
                            <label><input type="radio"
                                    <?php echo (get_option('cust_add_myaccount') == "2")?'checked':''; ?> value="2"
                                    name="cust_add_myaccount"> No </label>
                        </td>
                        <td>

                        </td>
                    </tr>

                </tbody>
            </table> -->
                <!-- <p class="submit">
                <input type="submit" value="Save Changes" class="button button-primary">
            </p> -->
            </form>

        </div>

        <!-- past panel code -->
    </div>
</div>



<?php endif; ?>