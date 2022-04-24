<?php

// don't load directly
if ( !defined('ABSPATH') )
	die('-1');

/**
 * Settings page action
 */
function square_settings_page() {
   
    // checkOrAddPluginTables();
    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

    $errorMessage = '';
    $successMessage = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['terminate_sync'])) {
        
        //clear session variables if exists
        if (isset($_SESSION["square_to_woo"])){ unset($_SESSION["square_to_woo"]); };
        if (isset($_SESSION["woo_to_square"])){ unset($_SESSION["woo_to_square"]); };
        
        update_option('woo_square_running_sync', false);
        update_option('woo_square_running_sync_time', 0);

        $successMessage = 'Sync terminated successfully!';
    }
    
    // check if the location is not setuped
    if (get_option('woo_square_access_token') && !get_option('woo_square_location_id')) {
        $square->authorize();
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // setup account
        
        // save settings
        if (isset($_POST['woo_square_settings'])) {
			
            update_option('woo_square_auto_sync', sanitize_text_field($_POST['woo_square_auto_sync']));
            if ($_POST['woo_square_auto_sync']) {
                update_option('woo_square_auto_sync_duration', sanitize_text_field($_POST['woo_square_auto_sync_duration']));
                wp_clear_scheduled_hook('auto_sync_cron_job_hook');
                switch ($_POST['woo_square_auto_sync_duration']) {
                    case 3:
                        wp_schedule_event(time(), '3min', 'auto_sync_cron_job_hook');
                        break;
                    case 60: 
                        wp_schedule_event(time(), 'hourly', 'auto_sync_cron_job_hook');
                        break;
                    case 720:
                        wp_schedule_event(time(), 'twicedaily', 'auto_sync_cron_job_hook');
                        break;
                    case 1440:
                        wp_schedule_event(time(), 'daily', 'auto_sync_cron_job_hook');
                        break;
                }
            } else {
                wp_clear_scheduled_hook('auto_sync_cron_job_hook');
            }
            update_option('woo_square_merging_option', sanitize_text_field($_POST['woo_square_merging_option']));
            update_option('woo_square_sync_preference', sanitize_text_field($_POST['woo_square_sync_preference']));
            update_option('sync_on_add_edit', sanitize_text_field($_POST['sync_on_add_edit']));
            update_option('disable_auto_delete', sanitize_text_field(@$_POST['disable_auto_delete']));
			if(!empty($_POST['woosquare_pro_edit_fields'])){
				update_option('woosquare_pro_edit_fields',  array_map( 'esc_attr', $_POST['woosquare_pro_edit_fields'] )) ;
			} else {
				update_option('woosquare_pro_edit_fields',  array()) ;
			}
            
            //update location id
            if( !empty($_POST['woo_square_location_id'])){
                $location_id = sanitize_text_field($_POST['woo_square_location_id']);
                update_option('woo_square_location_id', $location_id);               
                $square->setLocationId($location_id);
                $square->getCurrencyCode();
               
            }
			
			update_option('html_sync_des', sanitize_text_field(@$_POST['html_sync_des']));
			
            $successMessage = 'Settings updated successfully!';
        }
    }
    $wooCurrencyCode    = get_option('woocommerce_currency');
    $squareCurrencyCode = get_option('woo_square_account_currency_code');
    
    if(!$squareCurrencyCode){
        $square->getCurrencyCode();
        $square->getapp_id();
        $squareCurrencyCode = get_option('woo_square_account_currency_code');
    }
    if ( $currencyMismatchFlag = ($wooCurrencyCode != $squareCurrencyCode) ){

    }
    include WOO_SQUARE_PLUGIN_PATH . 'views/settings.php';
}

/**
 * Settings customer sync page action
 */
function square_customer_sync_settings() {
    
    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

    $errorMessage = '';
    $successMessage = '';
    
    // check if the location is not setuped
    if (get_option('woo_square_access_token') && !get_option('woo_square_location_id')) {
        $square->authorize();
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // setup account
       
        // save settings 
        if (isset($_POST['woo_square_customer_settings'])) {
            update_option('woo_square_customer_auto_sync', sanitize_text_field($_POST['woo_square_customer_auto_sync']));
            if ($_POST['woo_square_customer_auto_sync']) {
                update_option('woo_square_customer_auto_sync_duration', sanitize_text_field($_POST['woo_square_customer_auto_sync_duration']));
                wp_clear_scheduled_hook('auto_sync_customer_cron_job_hook');
                switch ($_POST['woo_square_customer_auto_sync_duration']) {
                    case 3:
                        wp_schedule_event(time(), '3min', 'auto_sync_customer_cron_job_hook');
                        break;
                    case 60: 
                        wp_schedule_event(time(), 'hourly', 'auto_sync_customer_cron_job_hook');
                        break;
                    case 720:
                        wp_schedule_event(time(), 'twicedaily', 'auto_sync_customer_cron_job_hook');
                        break;
                    case 1440:
                        wp_schedule_event(time(), 'daily', 'auto_sync_customer_cron_job_hook');
                        break;
                }
            } else {
                wp_clear_scheduled_hook('auto_sync_customer_cron_job_hook');
            }
          /*  update_option('woo_square_customer_merging_option', sanitize_text_field($_POST['woo_square_customer_merging_option']));
            update_option('woo_square_customer_sync_square_order_sync', sanitize_text_field($_POST['woo_square_customer_sync_square_order_sync']));
			update_option('sync_on_customer_add_edit', sanitize_text_field($_POST['sync_on_customer_add_edit']));
          */
            if(!empty($_POST['woo_square_customer_merging_option'])){
                update_option('woo_square_customer_merging_option', sanitize_text_field($_POST['woo_square_customer_merging_option']));
            }
            if(!empty($_POST['woo_square_customer_sync_square_order_sync'])){
                update_option('woo_square_customer_sync_square_order_sync', sanitize_text_field($_POST['woo_square_customer_sync_square_order_sync']));
            }
            if(!empty($_POST['woo_square_create_customer_guest'])){
                update_option('woo_square_create_customer_guest', sanitize_text_field($_POST['woo_square_create_customer_guest']));
            }
            if(!empty($_POST['sync_on_customer_add_edit'])){
                update_option('sync_on_customer_add_edit', sanitize_text_field($_POST['sync_on_customer_add_edit']));
            }
            $successMessage = 'Settings updated successfully!';
        }
    }
    
    include SQUARE_CUSTOMER_SYNC_PLUGIN_PATH . '/admin/partials/customer_settings.php';
}

/**
 * Logs page action
 * @global type $wpdb
 */
function logs_plugin_page(){
        
        checkOrAddPluginTables();       
        global $wpdb;
        
        $query = "
        SELECT log.id as log_id,log.action as log_action, log.date as log_date,log.sync_type as log_type,log.sync_direction as log_direction, children.*
        FROM ".$wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS." AS log
        LEFT JOIN ".$wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS." AS children
            ON ( log.id = children.parent_id )
        WHERE log.action = %d ";
              
        $parameters = [Helpers::ACTION_SYNC_START];
        
        //get the post params if sent or 'any' option was not chosen
        $sync_type = (isset($_POST['log_sync_type']) && strcmp($_POST['log_sync_type'],'any')) ?intval(sanitize_text_field($_POST['log_sync_type'])):null;
        $sync_direction = (isset($_POST['log_sync_direction']) && strcmp($_POST['log_sync_direction'],'any'))?intval(sanitize_text_field($_POST['log_sync_direction'])):null;
        $sync_date = isset($_POST['log_sync_date'])?
            (strcmp($_POST['log_sync_date'],'any')?intval(sanitize_text_field($_POST['log_sync_date'])):null):1;

        
        if (!is_null($sync_type)){
            $query.=" AND log.sync_type = %d ";
            $parameters[] = $sync_type; 
        }
        if (!is_null($sync_direction)){
           $query.=" AND log.sync_direction = %d ";
           $parameters[] = $sync_direction;  
        }
        if (!is_null($sync_date)){
           $query.=" AND log.date > %s ";
           $parameters[] = date("Y-m-d H:i:s", strtotime("-{$sync_date} days"));
        }
        
        
        $query.="
            ORDER BY log.id DESC,
                     id ASC";

        $sql =$wpdb->prepare($query, $parameters);
        $results = $wpdb->get_results($sql);
        $helper = new Helpers();
        
        include WOO_SQUARE_PLUGIN_PATH . 'views/logs.php';
       
}

