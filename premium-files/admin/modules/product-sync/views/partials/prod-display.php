<div>
    <?php


	if(!$_REQUEST['optionsaved']){
		$checked = 'checked';
	} else {
		if($_REQUEST['from'] == 'square'){ 
			$prd = get_option('woo_square_listsaved_products_square');
		} else {
			$prd = get_option('woo_square_listsaved_products_wooco');
		}
	}

	
	
	uasort($$targetObject, function($a, $b) {
		return strcmp($a['name'], $b['name']);
	});
	
    
	
	foreach ($$targetObject as $row):?>                                              
        <div class='square-action'>
		
		<?php if(
			$row['sku_missin_inside_product'] != 'sku_missin_inside_product' and 
			$row['sku_misin_squ_woo_pro_variable'] != 'sku_misin_squ_woo_pro_variable'
		){
			if($_REQUEST['optionsaved']){
				if(in_array($row['checkbox_val'], $prd)){
					$checked = 'checked';
				} else {
					$checked = '';
				}
			}
			

		?>

            <input name='woo_square_product' class="woo_square_product modifier_update" type='checkbox' value='<?php echo $row['checkbox_val']; ?>' <?php  echo $checked; ?> />
		<?php }

		//die();

		?>
			<?php if ( !empty($row['woo_id'])):?>
				<a target='_blank' href='<?php echo admin_url(); ?>post.php?post=<?php echo $row['woo_id']; ?>&action=edit'><?php echo $row['name']; ?></a>
			<?php else:?>
				<?php echo $row['name']; ?>
			<?php endif;?>
			<br>
			<?php if ( !empty($row['modifier_set_name'])):?>
				<!--<span style="display: block;font-weight: 500;"> Modifier Set Name </span>-->
				<?php foreach ($row['modifier_set_name'] as $modifier_name):
					$modifier_name = (explode("|",$modifier_name));
					$postvalue = base64_encode(serialize($array));
			   
							?>
					<?php if(empty($modifier_name[1])){ ?>
					<input name='woo_square_product' id="woo_square_product" class="modifier_set_name modifier_update" type='checkbox' value='<?php echo str_replace(' ', '-', $modifier_name[0])?>_<?php echo $modifier_name[1] ?>_<?php echo $modifier_name[2] ?>_<?php echo $modifier_name[3] ?>_<?php echo  $modifier_name[4] ?>_<?php  echo str_replace(' ', '-', strtolower($modifier_name[5])) ?>_add_modifier' <?php  echo $checked; ?> />
					<?php } else { ?>
					<input name='woo_square_product' id="woo_square_product" class="modifier_set_name modifier_update" type='checkbox' value='<?php echo str_replace(' ', '-', $modifier_name[0])?>_<?php echo $modifier_name[1]?>_<?php echo $modifier_name[2]?>_<?php echo $modifier_name[3] ?>_<?php echo  $modifier_name[4] ?>_<?php  echo str_replace(' ', '-', strtolower($modifier_name[5])) ?>_modifier' <?php  echo $checked; ?> />
				<?php } ?>
				
						<?php if ( !empty($row['woo_id'])):?>
					<a target='_blank' href='<?php echo admin_url(); ?>post.php?post=<?php echo $row['woo_id']; ?>&action=edit'><?php echo $modifier_name[0]; ?></a><br>
				 <?php else:?>
					<?php echo $modifier_name[0]; ?> <br>
				<?php endif;?>
				<?php endforeach;?>

			<?php endif;?>

		<?php  if (!array_key_exists("direction",$row) && !empty($row['modifier_set_name'])) { ?>

			<input name='woo_square_product' class="modifier_end" type='checkbox' style="display: none" value='modifier_set_end' checked="checked" disabled />

<?php } ?>
        </div>                        
    <?php endforeach; ?>
</div>