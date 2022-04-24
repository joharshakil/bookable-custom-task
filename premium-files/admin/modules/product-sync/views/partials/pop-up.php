<div class="pop-up-content">
    <p><?php echo __('Choose items to synchronize:') ?></p>
	
    <div class="sync-data">
        <div class="sync-elements">
            <h2><?php echo __('Categories'); ?> 
            <span class="checkuncheck">
				  <input type="button" class="check button button-primary button-hero load-customize hide-if-no-customize extcheck extcat" value="Check / Uncheck All" />
            </span>
        </h2>
			
            <?php if (!empty($targetCategories)):?>
                <div class="scrollwrap">
                    <div id="sync-category">
                        <?php if (!empty($addCategories)): ?>
                            <h3><?php echo __('CREATE'); ?></h3>
                            <div class="square-create ">
                                    <?php
                                    $targetObject = 'addCategories';
                                    include "cat-display.php";
                                    ?>
                            </div>
                        <?php endif; ?>
                            <?php if (!empty($updateCategories)): ?>
                            <h3><?php echo __('Sync/Update.'); ?></h3>
                            <div class="square-update ">
                                <?php
                                    $targetObject = 'updateCategories';
                                    include "cat-display.php";
                                ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($deleteCategories)): ?>
                            <h3><?php echo __('DELETE'); ?></h3>
                            <div class="square-delete ">
                                <?php
                                    $targetObject = 'deleteCategories';
                                    include "cat-display.php";
                                ?>
                            </div>
                        <?php endif; ?>
    
    
                    </div>
                </div>
            <?php else:?>
                <?php echo __('No Categories found to synchronize');?>
            <?php endif;?>
        </div>

        <div class="sync-elements">   
			
            <h2><?php echo __('Products'); ?>
            <span class="checkuncheck">
				  <input type="button" class="check button button-primary button-hero load-customize hide-if-no-customize extcheck extpro" value="Check / Uncheck All" />
            </span>	
        </h2>   

        <div class="scrollwrap">
            <div id="sync-product">
                <?php if (!empty($targetProducts) || $oneProductsUpdateCheckbox ):?>
                    <?php if (!empty($addProducts)): ?>
                        <h3><?php echo __('CREATE'); ?></h3>
                        <div class="square-create ">
                            <?php
                                $targetObject = 'addProducts';
                                include "prod-display.php";
                            ?>
                        </div>
                    <?php endif;?>
    
    
                    <?php if ($oneProductsUpdateCheckbox):?>
                        <h3><?php echo __('Sync/Update.'); ?></h3>
                        <div class="square-update ">
                        <div class='square-action'>
                            <input name='woo_square_product' type='checkbox' value='update_products' checked />Update other products
                        </div>
                        </div>
                    <?php else: ?>           
                        <?php if (!empty($updateProducts)): ?>
                            <h3><?php echo __('Sync/Update.'); ?></h3>
                            <div class="square-update ">
                                <?php
                                    $targetObject = 'updateProducts';
                                    include "prod-display.php";
                                ?>
    
                            </div>
                        <?php endif;?>
                    <?php endif; ?>
                    <?php if (!empty($deleteProducts)): ?>
                        <h3><?php echo __('DELETE'); ?></h3>
                        <div class="square-delete ">
                            <?php
                                $targetObject = 'deleteProducts';
                                include "prod-display.php";
                            ?>
                        </div>
                    <?php endif;?>
                <?php else:?>
                    <?php echo __('No Products found to synchronize'); ?>
                <?php endif;?>
                
                
                <?php if(!empty($sku_missin_inside_product)): ?>
                <h2><?php echo __('Sku Missing Products'); ?></h2> 
                        <div class="square-create ">
                            <?php
                                $targetObject = 'sku_missin_inside_product';
                                include "prod-display.php";
                            ?>
                        </div>
                <?php endif; ?>
                
                </div>
        </div>
					
            
        </div>
    </div>
</div>