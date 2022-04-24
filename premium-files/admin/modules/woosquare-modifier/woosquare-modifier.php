<?php

/**
 * Plugin Name:      Woosquare Modifier
 * Plugin URI:        https://wpexperts.io/wordpress-plugins/
 * Description:       Order itemization will help you to send item information like taxes, discount and breakdown of items from woocommerce to square dashboard.
 * Version:           1.1.1
 * Author:            Wpexperts
 * Author URI:        https://wpexperts.io/wordpress-plugins/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       square-order-sync-add-on
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Woosquare_Modifier_Admin', false ) ) {
    return new Woosquare_Modifier_Admin();
}

/*if ( ! class_exists( 'WooCommerce', false ) ) {
    include_once dirname( WC_PLUGIN_FILE ) . '/include/class-woocommerce.php';
}*/

/**
 * Woosquare_Modifier_Admin_Menus Class.
 */

class Woosquare_Modifier_Admin {

// Plugin Version
    public $version             = '3.7.5';

// Plugin Slug
    public $slug                = 'woosquare-modifier';

// Textdomain
    public $domain              = 'woosquare_modifier';

// Plugin name
    public $plugin_name         = 'Woosquare Modifier';



    public function __construct() {

        $this->wsm_check_woosquare();
        $this->wsm_define_constants();
        $this->wsm_load_gateways();
        // $this->wsm_modifier_textnomy();
        //  $this->wsm_register_scripts();
        add_action( 'init',  array( $this, 'wsm_modifier_textnomy' ) );
        add_action( 'admin_menu', array( $this, 'wsm_admin_menu' ), 9 );
        add_action( 'admin_init',       array( $this, 'wsm_register_scripts' ) );
        add_action( 'wp_footer',       array( $this, 'wsm_register_style' ) );
      //  add_action('woocommerce_modifier_deleted',$this,'deleteModifier' ,10,3);

        //    print_r(WSM_WOOSQUAREMODIFIER_ACCESS_TOKEN);
    }






    public  function wsm_check_woosquare()
    {
        $class = 'notice notice-error';
        if (!in_array('woosquare/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))
            and
            (!in_array('woosquare-pro/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins'))))
            and
            (!in_array('woosquare-premium/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins'))))
            or
            (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
            or
            version_compare( PHP_VERSION, '5.5.0', '<' )
        ) {

            //$message = __('To use "woosquare modifier" Woosquare and Woocommerce must be activated and installed!', 'woosquare');
            //printf('<br><div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
            //include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            // deactivate_plugins('woosquare-modifier/woosquare-modifier.php');
            //wp_die('','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );
        }
    }


    private function wsm_define_constants() {

        define( 'WSM_WOOSQUARE_MODIFIER',   __FILE__ );
        define('USER_SELECT_ONE','1');
        define('SELECT_FIELD','select');
        define('RADIO_FIELD','radio');
        define('USER_SELECT_MULTIPLE','0');
          define('WSM_WOOSQUAREMODIFIER_LIVE_URL', 'https://connect.squareup.com/v2');
        define( 'WSM_WOOSQUARE_MODIFIER_ROOT_DIR',     plugin_dir_path( WSM_WOOSQUARE_MODIFIER ) );
        define( 'WSM_WOOSQUARE_MODIFIER_INCLUDE_DIR', WSM_WOOSQUARE_MODIFIER_ROOT_DIR . 'include/' );
        define('WSM_WOOSQUAREMODIFIER_LOCATION_ID', get_option('woo_square_location_id_free'));
        define('WSM_WOOSQUAREMODIFIER_ACCESS_TOKEN', get_option('woo_square_access_token_free'));
        if (!defined('MS_WC_SQUARE_ENABLE_STAGING'))
            define('WSM_WOOSQUAREMODIFIER_STAGING_URL', 'squareup');

        register_activation_hook(   WSM_WOOSQUARE_MODIFIER, array( __CLASS__, 'wsm_activate_plugin' ) );

    }

    /**
     * Mycred Register
     */

    public function wsm_load_gateways() {

        require_once( WSM_WOOSQUARE_MODIFIER_INCLUDE_DIR . 'class-woosquare-modifier.php' );

        //Dashboard for add panel
        require_once( WSM_WOOSQUARE_MODIFIER_INCLUDE_DIR . 'class-woosquare-product-admin-panel.php' );

        //Get List Modifier
        ///require_once( WSM_WOOSQUARE_MODIFIER_INCLUDE_DIR . 'class-get-list-modifier.php' );

    }

    public function wsm_register_scripts() {

        wp_register_script( 'square-modifier-js', plugins_url( 'assets/js/main.js', WSM_WOOSQUARE_MODIFIER ) );
        wp_register_style( 'square-modifier-css', plugins_url( 'assets/css/main.css', WSM_WOOSQUARE_MODIFIER ) );
        wp_enqueue_script( 'square-modifier-js' );
        wp_enqueue_style( 'square-modifier-css' );
    }

    public function wsm_register_style() {
        wp_register_style( 'square-modifier-js', plugins_url( 'assets/js/payment.js', WSM_WOOSQUARE_MODIFIER ) );
        wp_enqueue_style( 'square-modifier-js' );
        wp_register_style( 'square-modifier-css', plugins_url( 'assets/css/frontend.css', WSM_WOOSQUARE_MODIFIER ) );
        wp_enqueue_style( 'square-modifier-css' );
    }
    public function wsm_admin_menu() {
        add_submenu_page( 'edit.php?post_type=product', __( 'Woosquare Modifier' , 'woosquare_modifier' ), __( 'Woosquare Modifier' , 'woosquare_modifier'), 'manage_product_terms',
            'woosquare_modifier', array($this , 'wsm_modifier_page'));
    }

    /**
     * Init the modifier page.
     */

    public function wsm_modifier_page() {
        WooSquare_Modifier::wsm_output();
    }


    public function wsm_modifier_textnomy(){

        global $wsm_product_modifier;

        $wsm_product_modifier = array();
        $modifier_taxonomies  = WooSquare_Modifier::wsm_get_modifier();

        if ( $modifier_taxonomies ) {
            foreach ($modifier_taxonomies as $modifier) {
                $name = WooSquare_Modifier::wsm_modifier_set_name($modifier->modifier_slug."_".$modifier->modifier_id);
                $label = sanitize_title($modifier->modifier_set_name);
                if ($name) {
                    $modifier->modifier_public = absint(isset($modifier->modifier_public) ? $modifier->modifier_public : 1);
                    $wsm_product_modifier[$name] = $modifier;

                    $taxonomy_data = array(
                        'hierarchical' => false,
                        //'update_count_callback' => '_update_post_term_count',
                        'labels' => array(
                            'name' => sprintf(_x('Product %s', 'Product Modifier', 'woosquare_modifier'), $label),

                            'singular_name' => $name,

                            'search_items' => sprintf(__('Search %s', 'woosquare_modifier'), $label),

                            'all_items' => sprintf(__('All %s', 'woosquare_modifier'), $label),

                            'parent_item' => sprintf(__('Parent %s', 'woosquare_modifier'), $label),

                            'parent_item_colon' => sprintf(__('Parent %s:', 'woosquare_modifier'), $label),

                            'edit_item' => sprintf(__('Edit %s', 'woosquare_modifier'), $label),

                            'update_item' => sprintf(__('Update %s', 'woosquare_modifier'), $label),

                            'add_new_item' => sprintf(__('Add new %s', 'woosquare_modifier'), $label),

                            'new_item_name' => sprintf(__('New %s', 'woosquare_modifier'), $label),

                            'not_found' => sprintf(__('No &quot;%s&quot; found', 'woosquare_modifier'), $label),

                            'back_to_items' => sprintf(__('&larr; Back to "%s" modifier', 'woosquare_modifier'), $label),
                        ),

                        'show_ui' => true,
                        'show_in_quick_edit' => true,
                        'show_in_menu' => false,
                        'meta_box_cb' => false,
                        'query_var' => 1 === $modifier->modifier_public,
                        'rewrite' => false,
                        'sort' => false,
                        'show_in_rest ' => true,
                        'public' => false,
                        'show_in_rest' => true,
                        'show_admin_column' => false,
                        'capabilities' => array(
                            'manage_terms' => 'manage_product_terms',
                            'edit_terms' => 'edit_product_terms',
                            'delete_terms' => 'delete_product_terms',
                            'assign_terms' => 'assign_product_terms',
                        ),


                    );


                    register_taxonomy($name, apply_filters("woocommerce_taxonomy_objects_{$name}", array('product')), apply_filters("woocommerce_taxonomy_args_{$name}", $taxonomy_data));
                    add_action( "{$name}_add_form_fields",array( $this, 'wsm_add_price_field_custom' ), 10, 1 );
                    add_action( "{$name}_edit_form", array( $this,'wsm_hide_description_row'));
                    add_action( "{$name}_add_form", array( $this,'wsm_hide_description_row'));
                    add_filter("manage_edit-{$name}_columns", array( $this,'wsm_modifier_columns' ));
                    add_action( "{$name}_edit_form_fields",  array( $this,'wsm_modifier_edit_field' ), 10, 2 );
                    add_action( "created_{$name}", array( $this,'wsm_save_modifier_field') );
                    add_action( "edited_{$name}", array( $this,'wsm_save_modifier_field') );
                    add_filter("manage_{$name}_custom_column", array( $this, 'wsm_get_list'), 10, 3);
                }
            }
        }
        do_action( 'woocommerce_after_register_taxonomy' );

    }


    Public function wsm_modifier_columns($modifier_columns) {

        $new_columns = array(
            'name' => __('Name'),
            'price' => __('Price')
        );
        return $new_columns;
    }

    public function wsm_modifier_edit_field($term, $taxonomy){
        $value = get_term_meta( $term->term_id, "term_meta_price", true );
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="<?php echo $term->taxonomy; ?>"><?php _e('Price'); ?></label>
            </th>
            <td>
                <input  type="number" name="term_meta_price" id="term_meta_price" style="width: 95%;margin-bottom: 10px;" value="<?php if(isset($value)){echo $value ? $value : ''; } ?>" required><br />
            </td>
        </tr>

    <?php  }




    function wsm_save_modifier_field( $term_id ) {

        update_term_meta($term_id,'term_meta_price',sanitize_text_field( $_POST[ 'term_meta_price' ] ) );

    }


    public function wsm_hide_description_row() {
        echo "<style> .term-description-wrap { display:none; } </style>";
        echo "<style> .term-slug-wrap p{ display:none; } </style>";
        echo "<style> .term-slug-wrap{ display:none; } </style>";
        echo "<style> .actions.bulkactions{ display:none; } </style>";
        echo "<style> .inline.hide-if-no-js{ display:none; } </style>";

    }


    public function  wsm_add_price_field_custom($tag) { ?>

        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="<?php echo $tag; ?>"><?php _e('Price'); ?></label>
            </th>
            <td>
                <input type="text" name="term_meta_price" id="term_meta_price" style="width: 95%;margin-bottom: 10px;" value="" required><br />

            </td>
        </tr>

        <?php
    }



    public function wsm_get_list($out, $column_name, $term_id) {
        $modifier_price = get_term_meta($term_id, 'term_meta_price', true);
        if(isset($modifier_price)) {

            switch ($column_name) {
                case 'price':
                    $out = $modifier_price;
                    break;
                default:
                    break;
            }
            return $out;
        }
    }

    public static function wsm_activate_plugin() {


        global $wpdb;
        $message = array();

        // WordPress check
        $wp_version = $GLOBALS['wp_version'];
        if ( version_compare( $wp_version, '4.0', '<' ) )
            $message[] = esc_html(__( 'This  Add-on requires WordPress 4.0 or higher. Version detected:', 'woosquare_modifier' )) . ' ' . $wp_version;

        // PHP check
        $php_version = phpversion();
        if ( version_compare( $php_version, '5.3.3', '<' ) )
            $message[] = esc_html(__( 'This myCRED Add-on requires PHP 5.3.3 or higher. Version detected: ', 'woosquare_modifier' )) . ' ' . $php_version;

        // SQL check
        $sql_version = $wpdb->db_version();
        if ( version_compare( $sql_version, '5.0', '<' ) )
            $message[] = esc_html(__( 'This myCRED Add-on requires SQL 5.0 or higher. Version detected: ', 'woosquare_modifier' )) . ' ' . $sql_version;


        // Not empty $message means there are issues
        if ( ! empty( $message ) ) {

            $error_message = implode( "\n", $message );
            die( esc_html(__( 'Sorry but your WordPress installation does not reach the minimum requirements for running this add-on. The following errors were given:', 'mycred_stripe' )) . "\n" . $error_message );

        }

    }
    public static function wsm_deactivate_plugins() {}
    public static function wsm_uninstall_plugin() {}






}

return new Woosquare_Modifier_Admin();

?>