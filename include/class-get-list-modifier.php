<?php

if ( class_exists( 'Get_List_Modifier', false ) ) {
    return new Get_List_Modifier();
}

class Get_List_Modifier
{
    function __construct()
    {

        add_action( 'add_modifier_list_sync',       array( $this, 'wsm_get_list_modifier' ), 10  );


    }


    public function wsm_get_list_modifier()
    {

        if(in_array('woosquare-premium/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            // define('WSM_WOOSQUAREMODIFIER_ACCESS_TOKEN_PRO', WOOSQU_PLUS_APPID);
            define('WSM_WOOSQUAREMODIFIER_ACCESS_TOKEN_PRO', get_option('woo_square_access_token'));
            define('WSM_WOOSQUAREMODFIER_LIST', 'MODIFIER_LIST');
        }
        $token = WSM_WOOSQUAREMODIFIER_ACCESS_TOKEN_PRO;
        $modifier_cursor = " ";
        $fields = array(
            "cursor" => $modifier_cursor,
            "types" => WSM_WOOSQUAREMODFIER_LIST,
        );

        //need to add order creation function and get the order id.

        $url = esc_url("https://connect." . WSM_WOOSQUAREMODIFIER_STAGING_URL . ".com/v2/catalog/list");


        $headers = array(
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '. $token,
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache'
        );


        $result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
                        'method' => 'GET',
                        'headers' => $headers,
                        'httpversion' => '1.0',
                        'sslverify' => false,
                        'body' => json_encode($fields)
                    )
                )
            )
        );


        if ($result->errors) {
            wp_send_json_error($result->errors);
        }
        else{
            $this->syncFromSquareModifierToWooModifier($result);
        }

    }

    public function syncFromSquareModifierToWooModifier($result) {

        foreach($result as $key => $val) {

           /* echo "<pre>";
            print_r($val);
            echo "</pre>";*/

            /* foreach($val as  $va){

                if($va->type == "MODIFIER_LIST") {

                    foreach ($va as $a) {

                        $this->process_add_modifieir($a->name, $a->selection_type, $va->id);
                     }
                  }
                }*/
            }
        }




    public function process_add_modifieir($modifieir_name , $selection_type , $modifier_set_id)
    {

        global $wpdb;
        $modifier = array();
        if(!empty($modifieir_name) && !empty($selection_type) && !empty($modifier_set_id)) {

            if($selection_type == 'MULTIPLE'){
                $modifier_public = '0';
                $modifier_option = '0';
            }elseif($selection_type == 'SINGLE'){
                $modifier_public = '1';
                $modifier_option = 'select';
            }

          /*  $modifier_taxonomies  = WooSquare_Modifier::wsm_get_modifier();

            foreach ($modifier_taxonomies as $key => $modifier) :
                $modifier_taxonomy_name = $modifier->modifier_set_name;
                if(!empty($modifier_taxonomy_name) == !empty($modifieir_name)){

                }
                else {
                    $modifier = array(
                        'modifier_set_name'    =>  $modifieir_name ,
                        'modifier_public'  => $modifier_public,
                        'modifier_option'    => $modifier_option,
                        'modifier_set_unique_id' => $modifier_set_id
                    );
                    $wpdb->insert($wpdb->prefix . 'woosquare_modifier', $modifier);
                }
               endforeach;


            do_action( 'woocommerce_modifier_added', $wpdb->insert_id, $modifier );
            wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
            delete_transient( 'wsm_modifier' );
            WC_Cache_Helper::invalidate_cache_group( 'woosquare-modifier' );
            return true;*/
        }


    }

    public  function insertModifierToWoo($category) {
        $product_categories = get_terms('product_cat', 'hide_empty=0');
        foreach ($product_categories as $categoryw) {
            $wooCategories[] = array('square_id' => get_option('category_square_id_' . $categoryw->term_id), 'name' => $categoryw->name, 'term_id' => $categoryw->term_id);
        }

        $wooCategory = Helpers::searchInMultiDimensionArray($wooCategories, 'square_id', $category->id);
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $category->name);
        remove_action('edited_product_cat', 'woo_square_edit_category');
        remove_action('create_product_cat', 'woo_square_add_category');

        if ($wooCategory) {
            wp_update_term($wooCategory['term_id'], 'product_cat', array('name' => $category->name, 'slug' => $slug));
            update_option('category_square_id_' . $wooCategory['term_id'], $category->id);
        } else {
            $result = wp_insert_term($category->name, 'product_cat', array('slug' => $slug));
            if (!is_wp_error($result) && isset($result['term_id'])) {
                update_option('category_square_id_' . $result['term_id'], $category->id);
            }
        }
        add_action('edited_product_cat', 'woo_square_edit_category');
        add_action('create_product_cat', 'woo_square_add_category');
    }



}



?>