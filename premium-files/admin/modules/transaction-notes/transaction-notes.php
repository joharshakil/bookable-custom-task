<?php 

	if(!function_exists('woosquare_transaction_note_modified')){
	  function woosquare_transaction_note_modified($order_note = null,$order = null){
			$selected_order_info = get_option('selected_order_info');
			
			// {order_id} {billing_first_name} {billing_last_name} {billing_company} {billing_country} {billing_address_1} {billing_address_2} {billing_city} {billing_state} {billing_postcode} {billing_phone} {billing_email}
			$order_note_modification = @$selected_order_info;
			$order_note_modification = str_replace('{order_id}',(string) $order->get_order_number(),$order_note_modification);
			$order_note_modification = str_replace('{billing_first_name}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name(),$order_note_modification);
			$order_note_modification = str_replace('{billing_last_name}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name(),$order_note_modification);
			$order_note_modification = str_replace('{billing_company}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_company : $order->get_billing_company(),$order_note_modification);
			$order_note_modification = str_replace('{billing_country}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_country : $order->get_billing_country(),$order_note_modification);
			$order_note_modification = str_replace('{billing_address_1}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1(),$order_note_modification);
			$order_note_modification = str_replace('{billing_address_2}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2(),$order_note_modification);
			$order_note_modification = str_replace('{billing_city}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_city : $order->get_billing_city(),$order_note_modification);
			$order_note_modification = str_replace('{billing_postcode}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode(),$order_note_modification);
			$order_note_modification = str_replace('{billing_phone}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_phone : $order->get_billing_phone(),$order_note_modification);
			$order_note_modification = str_replace('{billing_email}', version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_email : $order->get_billing_email(),$order_note_modification);
			
			
			if(!empty($order_note_modification)){
				if(strlen($order_note_modification) > 60) $order_note_modification = substr($order_note_modification, 0, 56).'...';
				return $order_note_modification;
			} else {
				return $order_note;
			}
	}
	}
	
	if(!function_exists('_get_transaction_note')){
		function _get_transaction_note($selected_order_info,$keywords){
			
			
			$Woosquare_Plus = new Woosquare_Plus();
			

			 $transaction_note = '<div class="bodycontainerWrap"> 

				<div class="bodycontainer">

					<div id="tabs" class="md-elevation-4dp bg-theme-primary">
						
							'.$Woosquare_Plus->wooplus_get_toptabs().'
						
					</div>

					<div class="welcome-panel ext-panel '.$_GET['page'].'">
			
						<form method="post"  >
							 <!-- <h3>Transaction Notes Settings</h3> -->
							 <h1><svg height="20px" viewBox="0 0 512 511" width="20px" xmlns="http://www.w3.org/2000/svg">
								<path
									d="m405.332031 256.484375c-11.796875 0-21.332031 9.558594-21.332031 21.332031v170.667969c0 11.753906-9.558594 21.332031-21.332031 21.332031h-298.667969c-11.777344 0-21.332031-9.578125-21.332031-21.332031v-298.667969c0-11.753906 9.554687-21.332031 21.332031-21.332031h170.667969c11.796875 0 21.332031-9.558594 21.332031-21.332031 0-11.777344-9.535156-21.335938-21.332031-21.335938h-170.667969c-35.285156 0-64 28.714844-64 64v298.667969c0 35.285156 28.714844 64 64 64h298.667969c35.285156 0 64-28.714844 64-64v-170.667969c0-11.796875-9.539063-21.332031-21.335938-21.332031zm0 0" />
								<path
									d="m200.019531 237.050781c-1.492187 1.492188-2.496093 3.390625-2.921875 5.4375l-15.082031 75.4375c-.703125 3.496094.40625 7.101563 2.921875 9.640625 2.027344 2.027344 4.757812 3.113282 7.554688 3.113282.679687 0 1.386718-.0625 2.089843-.210938l75.414063-15.082031c2.089844-.429688 3.988281-1.429688 5.460937-2.925781l168.789063-168.789063-75.414063-75.410156zm0 0" />
								<path
									d="m496.382812 16.101562c-20.796874-20.800781-54.632812-20.800781-75.414062 0l-29.523438 29.523438 75.414063 75.414062 29.523437-29.527343c10.070313-10.046875 15.617188-23.445313 15.617188-37.695313s-5.546875-27.648437-15.617188-37.714844zm0 0" />
							</svg> Transaction Notes Settings</h1>
							 '.@$suc.'

							 <div class="formWrap">

								<ul>
									<li>
										<strong>WooCommerce Order Details</strong>
										<div class="elementBlock">
											<fieldset>
												<legend class="screen-reader-text"><span></span></legend>
												<label for="Send_order_info">
												<textarea class="form-control form-wide" cols="250" rows="10"  name="selected_order_info" placeholder="WooCommerce Order # 123 Customer first name {billing_first_name} and last name {billing_last_name}...">'.@$selected_order_info.'</textarea>
												<p class="description">Send transaction note in square transaction. <br><b><i>( Note : Only 60 characters are allowed to send in transaction note. If characters exceed limit of 60 characters will automatically removed. )</i></b>.
												<br/> 
												Use these tags '.$keywords.'
												</p>
											   
											 </fieldset>
										</div>

									</li>
								</ul>

								<div class="row">
									<div class="col-md-6">
										<p class="submit">
											<input type="submit" value="Save Changes" class="btn waves-effect waves-light btn-rounded btn-success">
										</p>
									</div>
								</div>

							 </div>
										<!-- <table>
										<th scope="row" class="titledesc">
											   <label for="Send_order_info"></label>
											</th>
											<td class="forminp">
											   <fieldset>
												  <legend class="screen-reader-text"><span></span></legend>
												  <label for="Send_order_info">
												  <textarea cols="250" rows="10"  name="selected_order_info" placeholder="WooCommerce Order # 123 Customer first name {billing_first_name} and last name {billing_last_name}...">'.@$selected_order_info.'</textarea>
												  <p class="description">Send transaction note in square transaction. <br><b><i>( Note : Only 60 characters are allowed to send in transaction note. If characters exceed limit of 60 characters will automatically removed. )</i></b>.
												  <br/> 
												  Use these tags '.$keywords.'
												  </p>
												 
											   </fieldset>
											</td>
											</table>
											<p class="submit">
												<input type="submit" value="Save Changes" class="button button-primary">
											</p> -->
											</form>
											</div>


				</div>
				
		
								</div>
								';
		
			return $transaction_note;
		}
	}



?>