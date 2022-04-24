<?php 

$this->enqueue_styles();
$this->enqueue_scripts();

?>


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


	<?php if (get_option('woo_square_access_token')): 
	
	?>

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
				</svg> Order Synchronization Settings</h1>
			<!--style="opacity:0.5;pointer-events:none;"-->
			<form method="post">
				<div class="squ-order-sync-description" style="display: block;">
					<p>
						For Square order sync to WooCommerce you need to follow below instruction.
					</p>
					<p>If you don't have an account, go to <a target="_blank"
							href="https://squareup.com/signup">https://squareup.com/signup</a> to create one. You need a
						Square account to register an application with Square.
						Register your application with Square
					</p>
					<p>
						Then go to <a target="_blank"
							href="https://connect.squareup.com/apps">https://connect.squareup.com/apps</a> and sign in
						to your Square account. Then <b>click New Application</b> and give the name for your application
						to Create App.

						The application dashboard displays your new app's sandbox credentials. Insert below these
						sandbox credentials.
					</p>
					<p>
						Then goto <b>Webhooks</b> tab and insert this link
						<a><?php echo WOO_SQUARE_PLUGIN_URL . "admin/modules/order-sync/callback.php"; ?></a> in textbox "Notification URL".
					</p>

				</div>

				<hr class="hrS">

				<div class="formWrap">

					<ul>
						<li>
							<strong>Enable Square to WooCommerce Order synchronization ?</strong>
							<div class="elementBlock">
								<label><input type="checkbox" id="squ_woo_order_sync"
										<?php echo (get_option('squ_woo_order_sync') == "1")?'checked':''; ?> value="1"
										name="squ_woo_order_sync"> Yes </label>
							</div>
						</li>
                        	<li>
							<strong>Enable Square order sync email notification ?</strong>
							<div class="elementBlock">
								<label><input type="checkbox"
											<?php echo (get_option('sync_square_order_notify') == "1")?'checked':''; ?>
											  value="1" name="sync_square_order_notify"> Yes </label>
							</div>

						</li>
                        
						<li>
							<strong>Application id</strong>
							<div class="elementBlock">
								<fieldset>
									<legend class="screen-reader-text"><span>Application id</span></legend>
									<input class="form-control m-b-10" type="textbox"
										name="woocommerce_square_application_id" id="woocommerce_square_application_id"
										style=""
										value="<?php echo get_option('woo_square_application_id_for_callback') ?>"
										placeholder="">
									<p class="help-text">Add Square Application ID settings to integrate with square
										order sync.</p>
								</fieldset>
							</div>
						</li>
						<li>
							<strong>Access token</strong>
							<div class="elementBlock">
								<fieldset>
									<legend class="screen-reader-text"><span>Access token</span></legend>
									<input class="form-control m-b-10" type="textbox" name="woocommerce_square_access_token"
										id="woocommerce_square_access_token" style=""
										value="<?php echo get_option('woo_square_access_token_for_callback') ?>"
										placeholder="">
									<p class="help-text">Add Square Access token settings to integrate with square
										order sync.</p>
								</fieldset>
							</div>
						</li>
						<li>
							<strong>Location id</strong>
							<div class="elementBlock">
								<fieldset>
									<legend class="screen-reader-text"><span>Location id</span></legend>
									<input class="form-control m-b-10" type="textbox" name="woocommerce_square_location_id"
										id="woocommerce_square_location_id" style=""
										value="<?php echo get_option('woo_square_location_id_for_callback') ?>"
										placeholder="">
									<p class="help-text">Add Square Location ID settings to integrate with square
										order sync.</p>
								</fieldset>
							</div>
						</li>
					</ul>

				</div>

				<div class="row m-t-20">
					<div class="col-md-12">
                        <span class="submit">
							<input type="submit" value="Save Changes" class="btn waves-effect waves-light btn-rounded btn-success">
                        </span>
                    </div>
				</div>



				<!-- <table class="form-table"> -->
					<!-- <tbody> -->
						<!-- <tr>
							<th scope="row"><label>Enable Square to WooCommerce Order synchronization ?</label></th>
							<td>
								<label><input type="checkbox" id="squ_woo_order_sync"
										<?php echo (get_option('squ_woo_order_sync') == "1")?'checked':''; ?> value="1"
										name="squ_woo_order_sync"> Yes </label><br>
							</td>
						</tr> -->
						<!-- <tr valign="top" style="display: table-row;">
							<th scope="row" class="titledesc">
								<label for="woocommerce_square_application_id">Application id </label>
							</th>
							<td class="forminp">
								<fieldset>
									<legend class="screen-reader-text"><span>Application id</span></legend>
									<input class="form-control " type="textbox"
										name="woocommerce_square_application_id" id="woocommerce_square_application_id"
										style=""
										value="<?php echo get_option('woo_square_application_id_for_callback') ?>"
										placeholder="">
									<p class="description">Add Square Application ID settings to integrate with square
										order sync.</p>
								</fieldset>
							</td>
						</tr> -->
						<!-- <tr valign="top" style="display: table-row;">
							<th scope="row" class="titledesc">
								<label for="woocommerce_square_access_token">Access token </label>
							</th>
							<td class="forminp">
								<fieldset>
									<legend class="screen-reader-text"><span>Access token</span></legend>
									<input class="form-control " type="textbox"
										name="woocommerce_square_access_token" id="woocommerce_square_access_token"
										style=""
										value="<?php echo get_option('woo_square_access_token_for_callback') ?>"
										placeholder="">
									<p class="description">Add Square Access token settings to integrate with square
										order sync.</p>
								</fieldset>
							</td>
						</tr> -->
						<!-- <tr valign="top" style="display: table-row;">
							<th scope="row" class="titledesc">
								<label for="woocommerce_square_location_id">Location id </label>
							</th>
							<td class="forminp">
								<fieldset>
									<legend class="screen-reader-text"><span>Location id</span></legend>
									<input class="form-control " type="textbox"
										name="woocommerce_square_location_id" id="woocommerce_square_location_id"
										style="" value="<?php echo get_option('woo_square_location_id_for_callback') ?>"
										placeholder="">
									<p class="description">Add Square Location ID settings to integrate with square
										order sync.</p>
								</fieldset>
							</td>
						</tr> -->
					<!-- </tbody> -->
				<!-- </table> -->
			
				<!-- <p class="submit">
					<input type="submit" value="Save Changes" class="btn waves-effect waves-light btn-rounded btn-success">
				</p> -->
			</form>




			<div class="orderGrid">

				<div class="ext-panel <?=$_GET['page']?>-2">
					<h1><i class="fa fa-list-ul" aria-hidden="true"></i> Square Order Sync history WooCommerce to Square</h1>
					<table class="widefat fixed" cellspacing="2">
						<thead>
							<tr>
								<th>WooCommerce Order id</th>
								<th>Square order id</th>
							</tr>
						</thead>
						<tbody>
							<?php 
						
						global $wpdb;
						$square_orders = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key =\"WooSquare_Order_create_response\"; " );
						
						if(!empty($square_orders)){
							$i = 0;
							foreach($square_orders as $order){
								if(!empty(json_decode($order->meta_value)->order)){
									$i++;
									if($i%2 == 0){
										$cl = 'alternate';
									} else {
										$cl = '';
									}
									?>
							<tr class="<?=$cl?>">
								<td class="column-columnname"><a
										href="<?=site_url()."/wp-admin/post.php?post=".$order->post_id."&action=edit"?>"
										target="_blank"><?=$order->post_id?></a></td>
								<td class="column-columnname"><?=json_decode($order->meta_value)->order->id?></td>
							</tr>
							<?php 
								}
				
								
							}
						}
						
						
						?>


						</tbody>
					</table>
				</div>

			</div>


		</div>

	</div>
	<!-- end body container -->



</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>


<?php endif; ?>