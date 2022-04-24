<div class="bodycontainerWrap bodycontainerConnect">
<div class="squareConnectScreen">
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
	<div class="squareConnectBlock1 welcome-panel ext-panel <?=$_GET['page']?>-1">
		<?php if(!get_option('woo_square_access_token_cauth')){ ?>
		<div class="headerin">
			<a href="https://apiexperts.io/woosquare-plus-documentation/?utm_source=WordPress&utm_medium=PluginDocumentation&utm_campaign=InStore#getting-started-2"
				data-toggle="popover" data-trigger="hover"
				data-content="Dear user, by clicking on the symbol of documentation, you will lead to an instructions guide for connecting your square account with WooSquare Plus.">
				<svg class="docico" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
					version="1.1" id="Capa_1" x="0px" y="0px" viewBox="0 0 367.6 367.6"
					style="enable-background:new 0 0 367.6 367.6;" xml:space="preserve" width="18px" height="18px"
					class="">
					<g>
						<g>
							<g>
								<g>
									<path
										d="M328.6,81.6c-0.4,0-0.4-0.4-0.8-0.8c-0.4-0.4-0.4-0.8-0.8-1.2L258.2,2.4c-0.4-0.4-1.2-0.8-2-1.2c-0.4,0-0.4-0.4-0.8-0.4     c-0.8-0.4-2-0.8-3.2-0.8H83.8C59,0,38.6,20.4,38.6,45.2v277.2c0,24.8,20.4,45.2,45.2,45.2h200c24.8,0,45.2-20.4,45.2-45.2v-238     C329,83.6,328.6,82.4,328.6,81.6z M260.2,27.2l44.4,50h-44.4V27.2z M313.8,322c0,16.8-13.2,30.4-30,30.4h-200     c-16.8,0-30-13.6-30-30V44.8c0-16.8,13.6-30,30-30H245v69.6c0,4,3.2,7.6,7.6,7.6h61.2V322z"
										data-original="#000000" class="active-path" data-old_color="#000000"
										fill="#4949E7" />
									<path
										d="M155.4,223.6L111,198l44.4-25.6c3.6-2,4.8-6.8,2.8-10.4c-2-3.6-6.8-4.8-10.4-2.8l-56,32.4c-2.4,1.2-3.6,4-3.6,6.4     c0,2.8,1.6,5.2,3.6,6.4l56,32.4c1.2,0.8,2.4,1.2,3.6,1.2c2.8,0,5.2-1.2,6.4-3.6C160.2,230.4,159,226,155.4,223.6z"
										data-original="#000000" class="active-path" data-old_color="#000000"
										fill="#4949E7" />
									<path
										d="M209.4,162c-2,3.6-0.8,8.4,2.8,10.4l44.4,25.6l-44.4,25.6c-3.6,2-4.8,6.8-2.8,10.4c1.2,2.4,4,3.6,6.4,3.6     c1.2,0,2.4-0.4,3.6-1.2l56-32.4c2.4-1.2,3.6-4,3.6-6.4c0.4-2.4-0.8-4.8-3.2-6l-56-32.4C216.2,157.2,211.4,158.4,209.4,162z"
										data-original="#000000" class="active-path" data-old_color="#000000"
										fill="#4949E7" />
									<path
										d="M197.8,150.8c-4-1.2-8.4,0.8-9.6,4.8l-30.4,86.8c-1.2,4,0.8,8.4,4.8,9.6c0.8,0.4,1.6,0.4,2.4,0.4c3.2,0,6-2,7.2-5.2     l30.4-86.8C203.8,156.4,201.8,152,197.8,150.8z"
										data-original="#000000" class="active-path" data-old_color="#000000"
										fill="#4949E7" />
								</g>
							</g>
						</g>
					</g>
				</svg> Documentation</a>
		</div>

		<h3>WELCOME (We’re glad, you’re here)</h3>
		<?php } else { ?>
		<h3>THUMBS UP! You have done it.</h3>
		<?php } ?>


		<!-- <p class="tagline">Create a Square account if you don't have one</p> -->

		<!-- <p class="maxpara">You need a Square account to register an application with Square. <br> If you don't have an account, go to <a
					target="_blank" href="https://squareup.com/signup">https://squareup.com/signup</a> to create one.

			</p> -->
		<!-- <p class="maxpara">
				Follow instruction to <a target="_blank"
					href="https://apiexperts.io/woosquare-plus-documentation/?utm_source=WordPress&utm_medium=PluginDocumentation&utm_campaign=InStore#getting-started-2">connect
					Square account with WooSquare Plus Documentation.</a>
			</p>  -->

		<?php /* <form method="post">
				<table class="form-table">
					<tbody>
						<tr>
							<th><strong>Live/Sandbox Application ID</strong></th>
							<td>
								<input class="regular-text" value="<?php echo (isset($_POST['woo_square_app_id'])) ? $_POST['woo_square_app_id'] : get_option('woo_square_app_id'); ?>"
		name="woo_square_app_id" type="text"/>
		</td>
		</tr>
		<tr>
			<th><strong>Live/Sandbox Access Token</strong></th>
			<td>
				<input class="regular-text"
					value="<?php echo (isset($_POST['woo_square_access_token'])) ? $_POST['woo_square_access_token'] : get_option('woo_square_access_token'); ?>"
					name="woo_square_access_token" type="text" />
			</td>
		</tr>
		</tbody>
		</table>
		<p class="submit woosquare-auth-btn">
			<input type="submit" value="Authorize" class="button button-primary">
		</p>
		</form>
		*/ ?>

		<form method="post">


			<?php
			$redirect_url = add_query_arg(
				array(
					'page'    => 'square-settings',
					'app_name'    => WOOSQU_PLUS_APPNAME,
					'plug'    => WOOSQU_PLUS_PLUGIN_NAME
				),
				admin_url( 'admin.php' )
			);
	
			$redirect_url = wp_nonce_url( $redirect_url, 'connect_woosquare', 'wc_woosquare_token_nonce' );
	
			$query_args = array(
				'redirect' => urlencode( urlencode( $redirect_url ) ),
				'scopes'   => 'MERCHANT_PROFILE_READ,ITEMS_READ,ITEMS_WRITE,PAYMENTS_READ,PAYMENTS_WRITE,INVENTORY_WRITE,ORDERS_WRITE,CUSTOMERS_READ,CUSTOMERS_WRITE,INVENTORY_READ,LOYALTY_READ,LOYALTY_WRITE,ORDERS_READ',);
			$url = WOOSQU_PLUS_CONNECTURL.'/login/';  
			$production_connect_url = add_query_arg( $query_args, $url );
			
			$disconnect_url = add_query_arg(
				array(
					'page'              => 'square-settings',
					'app_name'    => WOOSQU_PLUS_APPNAME,
					'plug'    => WOOSQU_PLUS_PLUGIN_NAME,
					'disconnect_woosquare' => 1,
				),
				admin_url( 'admin.php' )
			); 
			 $disconnect_url = wp_nonce_url( $disconnect_url, 'disconnect_woosquare', 'wc_woosquare_token_nonce' );
			// echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
			// echo wp_kses_post( wpautop( $this->get_method_description() ) );
			// echo '<div><input type="hidden" name="section" value="' . esc_attr( $this->id ) . '" /></div>';
			// delete_option('woo_square_access_token');
			 
			// if user not connected through auth square button.
			?>

			<div class="squareConnectBlock">



				<?php if(!get_option('woo_square_access_token_cauth')){ ?>
				<span class="statusTitle">
					<small class="iconstatus icondis"></small>
					<?php esc_html_e( 'Connect Now!', 'woocommerce-square' ); ?>
				</span>
				<?php } else { ?>
				<span class="statusTitle">
					<small class="iconstatus iconcon"></small>
					<?php esc_html_e( 'Connected!', 'woocommerce-square' ); ?>
				</span>
				<?php } ?>


				<!-- <p>Connect through auth square to make system more smooth.</p> -->


				<?php if (get_option('woo_square_access_token')): ?>

				<div class="squareConnectBlock2 welcome-panel ext-panel <?=$_GET['page']?>-2">
					<div class="squareConnectBlock2Hold">
						<h4>Select Your Store</h4>
						<?php if ( $currencyMismatchFlag ){ ?>
						<br />
						<div id="woo_square_error" class="error" style="background: #ddd;">
							<p style="color: red;font-weight: bold;">The currency code of your Square account [
								<?php echo $squareCurrencyCode ?> ] does not match WooCommerce [
								<?php echo $wooCurrencyCode ?> ]
							</p>
						</div>
						<?php }?>
						<form class="locationWrap" method="post" <?php if ($currencyMismatchFlag): ?>
							style="opacity:0.5;pointer-events:none;" <?php endif; ?>>
							<input type="hidden" value="1" name="woo_square_settings" />

							<div class="locationhold">
								<?php 
								if(!empty(get_option('woo_square_locations')) and is_array(get_option('woo_square_locations')) ){
									
									
										foreach(get_option('woo_square_locations') as $kk => $valu){
												$loc[($kk)] = $valu;
											}
										} else {
											foreach(get_option('woo_square_locations') as $kk => $valu){
												$loc[key($valu)] = $valu[key($valu)];
											}
										}
										
								?>
								<select name="woo_square_location_id">
									<option selected="" value="">Select Location</option>
									<?php foreach ($loc as $key => $location){ ?>
									<option
										<?php if (get_option('woo_square_location_id') == key($location)): ?>selected=""
										<?php endif; ?> value="<?php echo key($location); ?>">
										<?php print_r($location[key($location)]); ?> </option>
									<?php } ?>
								</select>
								
								<span class="submit">
									<input type="submit" value="Save Changes"
										class="btn-cus btn waves-effect waves-light btn-rounded btn-primary">
								</span>
							</div>
						</form>
					</div>

					
                                
					
					<?php if (get_option('woo_square_location_id')){ ?>
						<div class="moduleslink">
						
							<a href="<?=get_admin_url().'admin.php?page='?>woosquare-plus-module" data-toggle="tooltip" data-placement="right" title="" data-original-title="Activate your modules now">Access your Module</a>
						
						</div>
					<?php } ?>
				</div>
				<?php endif; ?>
				<div class="clearfix"></div>
				<?php if(!get_option('woo_square_access_token_cauth')){ ?>
				<a href="<?php echo esc_attr( $production_connect_url ); ?>"
					class="m-t-10 waves-effect waves-dark btn btn-primary btn-md btn-rounded">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30">
						<path fill="#FFFFFF"
							d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" />
						<path fill="#FFFFFF"
							d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" />
					</svg>
					<span><?php esc_html_e( 'Connect with Square', 'woocommerce-square' ); ?></span>
				</a>
				<div class="signupLink">
					<span>Don't have account? </span> <a href="https://squareup.com/signup" data-placement="bottom"
						data-toggle="popover" data-trigger="hover"
						data-content="You need a Square account to register an application with Square.">
						<strong>Signup</strong></a>
				</div>
				<div class="videoWrapper">
					<iframe width="420" height="225" src="https://www.youtube.com/embed/-uYI_a-k9Eo" frameborder="0"
						allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
						allowfullscreen></iframe>
				</div>
				<?php } else { ?>
					
					
					
	   

				
				<a href="#" data-toggle="modal" data-target=".bs-example-modal-sm" class='m-t-20 waves-effect waves-dark btn btn-primary btn-md btn-rounded btn-danger'>

					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30">
						<path fill="#FFFFFF"
							d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" />
						<path fill="#FFFFFF"
							d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" />
					</svg>

					<span><?php echo esc_html__( 'Disconnect from Square', 'woocommerce-square' ); ?></span>

				</a>

				<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
					<div class="modal-dialog modal-sm modal-dialog-centered">
						<div class="modal-content">
							<button type="button" class="close closecus" data-dismiss="modal" aria-hidden="true">×</button>
							<div class="modal-body">
								<small class="iconBell"></small>

								<p>
									Do you really want to Disconnect ?
								</p>

								<div class="actionsPop">
									<a href="<?php echo esc_attr( $disconnect_url ); ?>"
										class='btn-block waves-effect waves-dark btn btn-danger btn-md btn-rounded'>Confirm</a>
	
								</div>
								
								<!-- <a href="<?php echo esc_attr( $disconnect_url ); ?>"
									class='m-t-10 waves-effect waves-dark btn btn-primary btn-md btn-rounded btn-danger'>
				
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 44" width="30" height="30">
										<path fill="#FFFFFF"
											d="M36.65 0h-29.296c-4.061 0-7.354 3.292-7.354 7.354v29.296c0 4.062 3.293 7.354 7.354 7.354h29.296c4.062 0 7.354-3.292 7.354-7.354v-29.296c.001-4.062-3.291-7.354-7.354-7.354zm-.646 33.685c0 1.282-1.039 2.32-2.32 2.32h-23.359c-1.282 0-2.321-1.038-2.321-2.32v-23.36c0-1.282 1.039-2.321 2.321-2.321h23.359c1.281 0 2.32 1.039 2.32 2.321v23.36z" />
										<path fill="#FFFFFF"
											d="M17.333 28.003c-.736 0-1.332-.6-1.332-1.339v-9.324c0-.739.596-1.339 1.332-1.339h9.338c.738 0 1.332.6 1.332 1.339v9.324c0 .739-.594 1.339-1.332 1.339h-9.338z" />
									</svg>
				
									<span><?php echo esc_html__( 'Disconnect from Square', 'woocommerce-square' ); ?></span>
				
								</a> -->
							</div>
							
						</div>
						<!-- /.modal-content -->
					</div>
					<!-- /.modal-dialog -->
				</div>
				<!-- /.modal -->



				<?php } ?>

				<!-- <table class="form-table">
						<tbody>
							<tr>
								<th>
									
								</th>
								<td>
									
								</td>
							</tr>
						</tbody>
					</table> -->
			</div>


		</form>
	</div>

	<!-- <?php if (get_option('woo_square_access_token')): ?>

	<div class="squareConnectBlock2 welcome-panel ext-panel <?=$_GET['page']?>-2">
		<h4>Woo Square Settings</h4>
		<?php if ( $currencyMismatchFlag ){ ?>
		<br />
		<div id="woo_square_error" class="error" style="background: #ddd;">
			<p style="color: red;font-weight: bold;">The currency code of your Square account [
				<?php echo $squareCurrencyCode ?> ] does not match WooCommerce [ <?php echo $wooCurrencyCode ?> ]
			</p>
		</div>
		<?php }?>
		<form class="locationWrap" method="post" <?php if ($currencyMismatchFlag): ?> style="opacity:0.5;pointer-events:none;"
			<?php endif; ?>>
			<input type="hidden" value="1" name="woo_square_settings" />

			<div class="locationhold">
			<?php if (get_option('woo_square_location_id') != '' && get_option('woo_square_location_id') != 'me' ): 
							if(!empty(get_option('woo_square_locations')) and is_array(get_option('woo_square_locations')) ){
									foreach(get_option('woo_square_locations') as $kk => $valu){
											$loc[($kk)] = $valu;
										}
									} else {
										foreach(get_option('woo_square_locations') as $kk => $valu){
											$loc[key($valu)] = $valu[key($valu)];
										}
									}
							?>
<select name="woo_square_location_id">
<option selected="" value="">Select Location</option>


								<?php foreach ($loc as $key => $location){ ?>
								<option <?php if (get_option('woo_square_location_id') == key($location)): ?>selected=""
									<?php endif; ?> value="<?php echo key($location); ?>">
									<?php print_r($location[key($location)]); ?> </option>
								<?php } ?>
							</select>
							<?php endif; ?>
							<span class="submit">
				<input type="submit" value="Save Changes" class="btn-cus btn waves-effect waves-light btn-rounded btn-info">
			</span>
			</div>

			
			
		</form>
	</div>

<?php endif; ?> -->

</div>
</div>
