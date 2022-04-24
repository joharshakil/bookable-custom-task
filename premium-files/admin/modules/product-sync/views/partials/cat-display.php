<div>
    <?php 
	
	
	if(!$_REQUEST['optionsaved']){
		$checked = 'checked';
	} else {
		if($_REQUEST['from'] == 'square'){ 
			$prd = get_option('woo_square_listsaved_categories_square');
		} else {
			$prd = get_option('woo_square_listsaved_categories_wooco');
		}
	}

    
	uasort($$targetObject, function($a, $b) {
		return strcmp($a['name'], $b['name']);
	});
	
	
	foreach ($$targetObject as $row):?>                                              
        <div class='square-action'>
		
		<?php 
			if($_REQUEST['optionsaved'] and !empty($prd) ){
				if(in_array($row['checkbox_val'], $prd)){
					$checked = 'checked';
				} else {
					$checked = '';
				}
			}
			?>
            <input name='woo_square_category' class="woo_square_category" type='checkbox' value='<?php echo $row['checkbox_val']; ?>' <?php  echo $checked; ?> />

            <?php if ( !empty($row['woo_id'])):?>
                <a target='_blank' href='<?php echo admin_url(); ?>edit-tags.php?action=edit&taxonomy=product_cat&tag_ID=<?php echo $row['woo_id']; ?>&post_type=product'><?php echo $row['name']; ?></a>
            <?php else:?>
                <?php echo $row['name']; ?>
            <?php endif;?>

        </div>                        
    <?php endforeach; ?>
</div>