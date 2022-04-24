<?php

?>

<form class="modifier-ajax-form" id="product_modifier" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
	<div  class="product-modifier" >
		<div class="toolbar toolbar-top">

			<?php

			$modifier_taxonomies  = WooSquare_Modifier::wsm_get_modifier();
			if ( ! empty( $modifier_taxonomies ) ) {

				global $product;
				$product = wc_get_product();
				$id = $product->get_id();



				foreach ( $modifier_taxonomies as $modifier ) {


					$modifier_taxonomy_name = WooSquare_Modifier::wsm_modifier_set_name($modifier->modifier_slug."_".$modifier->modifier_id);
					$label = $modifier->modifier_set_name ? $modifier->modifier_set_name : $modifier->modifier_set_name;
					$modifier_value = get_post_meta( $id, 'product_modifier_group_name' , true);
					$modifier_required_set = get_post_meta( $id, 'product_required_set' , true);
					
					$set_label = " Required Set ?";
					if($modifier_value) {
						//echo "<pre>modifier_texonomy";
					//  print_r($modifier_taxonomy_name);
				//	  echo "</pre>modifier_texonomy";
            //        print_r($modifier_value);
						if (in_array($modifier_taxonomy_name, $modifier_value)  || in_array($label, $modifier_value) ) {

							echo "<span>";
							echo '<label> ' . '' . esc_html($label) . ' ' . '</label> ' . '';
							echo '<input type="checkbox" id="product_modifier_texonomy" class="product_modifier_texonomy" name="product_modifier_group_name[]" value="' . esc_attr($modifier_taxonomy_name) . '" checked>  ';
							//echo '<b> ' . '' . esc_html($set_label) . ' ' . '</b> ' . '';
							//echo '<input type="checkbox" id="required_value" class="required_value" name="required_value[]" value="' . esc_attr($modifier_taxonomy_name) . ' ">  ';
							echo "</span>";
						} else {
							echo "<span>";
							echo '<label> ' . '' . esc_html($label) . ' ' . '</label> ' . '';
							echo '<input type="checkbox" id="product_modifier_texonomy" class="product_modifier_texonomy" name="product_modifier_group_name[]" value="' . esc_attr($modifier_taxonomy_name) . '">  ';
							//echo '<b> ' . '' . esc_html($set_label) . ' ' . '</b> ' . '';
							//echo '<input type="checkbox" id="required_value" class="required_value" name="required_value[]" value="' . esc_attr($modifier_taxonomy_name) . ' ">  ';
							echo "</span>";

						}
					} else {
						if ($modifier_taxonomy_name) {

							echo "<span>";
							echo '<label> ' . '' . esc_html($label) . ' ' . '</label> ' . '';
							echo '<input type="checkbox" id="product_modifier_texonomy" class="product_modifier_texonomy" name="product_modifier_group_name[]" value="' . esc_attr($modifier_taxonomy_name) . '">  ';
							//echo '<b> ' . '' . esc_html($set_label) . ' ' . '</b> ' . '';
						//	echo '<input type="checkbox" id="required_value" class="required_value" name="required_value[]" value="' . esc_attr($modifier_taxonomy_name) . ' ">  ';
							echo "</span>";

						}
					}
				}
			}

			?>




		</div>
		<input type="hidden" name="product_id" id="product_id"  value="<?php echo $id; ?>" readonly="readonly">
		<?php wp_nonce_field( 'wsm_woosquare_save_fields', 'modifer_nonce_field' ); ?>
		<div class="toolbar">
			<button type="button"  name="add_modifier_product" class="button save_modifier button-primary"><?php esc_html_e( 'Save Modifier', 'woosquare_modifier' ); ?></button>
		</div>
		<?php do_action( 'woocommerce_product_modifier' ); ?>
	</div>

	<div class="sucess-msg"> Modifier Successfully Updated </div>

</form>

<?php
//

?>
<script>



</script>