<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION_WOOSQUARE_PLUS', '1.0.0' );
define('WOO_SQUARE_TABLE_DELETED_DATA','woo_square_integration_deleted_data');
define('WOO_SQUARE_TABLE_SYNC_LOGS','woo_square_integration_logs');
define('WOO_SQUARE_PLUGIN_URL_PLUS', plugin_dir_url(__FILE__));
define('WOO_SQUARE_PLUS_PLUGIN_PATH', plugin_dir_path(__FILE__));
//if (!defined('WOO_SQUARE_PLUGIN_URL')) define('WOO_SQUARE_PLUGIN_URL',plugin_dir_url(__FILE__));
//inc freemius
// require_once( plugin_dir_path(__FILE__) . 'includes/square_freemius.php' );


//connection auth credentials

if (!defined('WOOSQU_PLUS_CONNECTURL')) define('WOOSQU_PLUS_CONNECTURL','https://connect.apiexperts.io');

$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
	if (!defined('WOOSQU_PLUS_APPID')) define('WOOSQU_PLUS_APPID',$woocommerce_square_plus_settings['sandbox_application_id']);
} else {
	if (!defined('WOOSQU_PLUS_APPID')) define('WOOSQU_PLUS_APPID','sq0idp-z7vv-p7qmRlqcMRJLinEkA');
}


if (!defined('WOOSQU_PLUS_APPNAME')) define('WOOSQU_PLUS_APPNAME','Woo Plus');




	if(!defined('WOO_SQUARE_MAX_SYNC_TIME')){
		//max sync running time
		// numofpro*60
		if (get_option('_transient_timeout_transient_get_products' ) > time()){
			$total_productcount = get_transient( 'transient_get_products');
		} else {
			$args     = array( 	'post_type' => 'product', 
								'posts_per_page' => -1 
			);
			$products = get_posts( $args ); 		
			$total_productcount = count($products);
			set_transient( 'transient_get_products', $total_productcount , 720 );
			
		}
		if($total_productcount > 1){
			define('WOO_SQUARE_MAX_SYNC_TIME', $total_productcount*60 );
		} else {
			define('WOO_SQUARE_MAX_SYNC_TIME', 10*60 );
		}
	}


// define( 'WooSquare_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
// define( 'WooSquare_PLUGIN_URL_PAYMENT', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
	if ( ! defined( 'WC_SQUARE_ENABLE_STAGING' ) ) {
		define( 'WC_SQUARE_ENABLE_STAGING', true );
		define( 'WC_SQUARE_STAGING_URL', 'squareupsandbox' );
	}
} else {
	if ( ! defined( 'WC_SQUARE_ENABLE_STAGING' ) ) {
		define( 'WC_SQUARE_ENABLE_STAGING', false );
		define( 'WC_SQUARE_STAGING_URL', 'squareup' );
	}
}


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woosquare-plus-activator.php
 */
function activate_woosquare_plus() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosquare-plus-activator.php';
	Woosquare_Plus_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woosquare-plus-deactivator.php
 */
function deactivate_woosquare_plus() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woosquare-plus-deactivator.php';
	Woosquare_Plus_Deactivator::deactivate();
}

add_action( 'plugins_loaded', 'activate_woosquare_plus' );
add_action( 'plugins_loaded', 'deactivate_woosquare_plus' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woosquare-plus.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woosquare_plus() {

	$plugin = new Woosquare_Plus();
	// global $qu_fs;
	// if (qu_fs()->can_use_premium_code()) {
		$plugin->run();
	// }

}

add_action('plugins_loaded', 'run_woosquare_plus', 0);

if ( class_exists( 'WC_Bookings' ) ) {
	/**
	 * Adds the custom product tab.
	 */

	function woosquare_custom_product_booking_sku_tab($tabs)
	{

		$tabs['booking-sku'] = array(
				'label' => __('Booking SKU', 'woocommerce'),
				'target' => 'booking_sku_options',
				'class' => array('show_if_booking'),
		);

		return $tabs;

	}

	add_filter('woocommerce_product_data_tabs', 'woosquare_custom_product_booking_sku_tab');

	/**
	 * Contents of the booking SKU options product tab.
	 */

	function woosquare_booking_sku_options_product_tab_content()
	{

		global $post;

		?>
		<div id='booking_sku_options' class='panel woocommerce_options_panel'><?php

		?>
		<div class='options_group'>

			<?php
			global $product;

			if(@$_GET['post'] && !empty(get_post_meta( @$_GET['post'], '_sku' ))){
			$get_sku =	get_post_meta( $_GET['post'], '_sku' ,true);
			}


		woocommerce_wp_text_input(array(
				'id' => '_booking_sku',
				'label' => __('Booking SKU', 'woocommerce'),
				'desc_tip' => 'true',
				'description' => __('Enter the booking SKU number.', 'woocommerce'),
				'type' => 'text',
				'value' =>  @$get_sku ? $get_sku : NULL,
		));

		?></div>

		</div><?php

	}

	add_filter('woocommerce_product_data_panels', 'woosquare_booking_sku_options_product_tab_content');


	/**
	 * Save the custom fields.
	 */

	function woosquare_save_booking_sku_option_fields($post_id)
	{
		global $product;
		$product = wc_get_product( $post_id );
		if($product->is_type('booking')) {
			if (isset($_POST['_booking_sku'])) :
				update_post_meta($post_id, '_sku', $_POST['_booking_sku']);
			endif;

		}

	}

	add_action('woocommerce_process_product_meta_booking', 'woosquare_save_booking_sku_option_fields');

	function woosquare_show_booking_sku_single_product()
	{
		global $product;

		?>

		<div class="product_meta">
			<?php if ($product->get_meta('_sku') && $product->is_type('booking'))  : ?>
				<span class="sku_wrapper"><?php esc_html_e('Booking SKU:', 'woocommerce'); ?> <span
							class="sku"><?php echo ($sku = $product->get_meta('_sku')) ? $sku : esc_html__('N/A', 'woocommerce'); ?></span></span>
			<?php endif; ?>
		</div>
	<?php
}

	add_action('woocommerce_single_product_summary', 'woosquare_show_booking_sku_single_product', 40);

	/**
	 * Build column for product page
	 */

	function woosquare_add_booking_sku_column($columns)
	{
		$columns['_sku'] = 'Booking SKU';
		return $columns;
	}

	add_filter('manage_edit-product_columns', 'woosquare_add_booking_sku_column');

	/**
	 * Populate the column in product page
	 */

	function woosquare_populate_booking_sku_column($column_name)
	{
		global $product;

		if ($column_name == '_sku') {
			if ($product->get_meta('_sku') || $product->is_type('booking')) {
				echo($sku = $product->get_meta('_sku'));
			}
		}
	}

	add_action('manage_posts_custom_column', 'woosquare_populate_booking_sku_column');

	/**
	 * Show the Booking SKU in cart table
	 */

	add_filter('woocommerce_cart_item_name', 'woosquare_showing_booking_sku_in_cart_items', 99, 3);
	function woosquare_showing_booking_sku_in_cart_items($item_name, $cart_item, $cart_item_key)
	{

		$product = $cart_item['data'];
		$sku = $product->get_meta('_sku');
		if (empty($sku)) return $item_name;

		$item_name .= '<br><small class="product-sku">' . __("Booking SKU: ", "woocommerce") . $sku . '</small>';

		return $item_name;
	}

	/**
	 * Show the Booking SKU in order data
	 */

   function woosquare_booking_sku_order_item_headers($order)
	{
		echo '<th class="line_sku sortable" data-sort="your-sort-option">SKU</th>';
	}

	add_action('woocommerce_admin_order_item_headers', 'woosquare_booking_sku_order_item_headers', 10, 1);

    // Add content
	function woosquare_booking_sku_order_item_values($product, $item, $item_id)
	{
		if ($product) {
			$sku = $product->get_meta('_sku');
			echo '<td class="sku_wrapper">' . $sku . '</td>';
		}
	}

	add_action('woocommerce_admin_order_item_values', 'woosquare_booking_sku_order_item_values', 10, 3);

}
