<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WooSquare_Sync_To_Square_WordPress_Hooks
 *
 * Attach WooSquare_Sync_To_Square methods to WordPress and WooCommerce core hooks.
 */
class WooSquare_Sync_To_Square_WordPress_Hooks {

	/**
	 * @var WC_Integration
	 */
	protected $integration;

	/**
	 * @var WooSquare_Sync_To_Square
	 */
	protected $square;

	/**
	 * Whether or not to sync product data to Square.
	 *
	 * @var bool
	 */
	protected $sync_products;

	/**
	 * @var bool
	 */
	protected $sync_categories;

	/**
	 * @var bool
	 */
	protected $sync_images;

	/**
	 * @var bool
	 */
	protected $sync_inventory;

	/**
	 * Whether or not hooks should fire.
	 *
	 * @var bool
	 */
	protected $enabled = true;

	/**
	 * WooSquare_Sync_To_Square_WordPress_Hooks constructor.
	 *
	 * @param WC_Integration           $integration
	 * @param WooSquare_Sync_To_Square $square
	 */
	public function __construct( WC_Integration $integration, WooSquare_Sync_To_Square $square ) {

		$this->integration = $integration;
		$this->square      = $square;

		$this->sync_products   = ( 'yes' === $integration->get_option( 'sync_products' ) );
		$this->sync_categories = ( 'yes' === $integration->get_option( 'sync_categories' ) );
		$this->sync_images     = ( 'yes' === $integration->get_option( 'sync_images' ) );
		$this->sync_inventory  = ( 'yes' === $integration->get_option( 'sync_inventory' ) );

		add_action( 'WooSquare_loaded', array( $this, 'attach_hooks' ) );
		add_action( 'WooSquare_save_post_event', array( $this, 'process_save_post_event' ), 10, 2 );
	}

	/**
	 * Dynamically enable WP/WC hook callbacks.
	 */
	public function enable() {

		$this->enabled = true;

	}

	/**
	 * Dynamically disable WP/WC hook callbacks.
	 */
	public function disable() {

		$this->enabled = false;

	}

	/**
	 * Hook into WordPress and WooCommerce core.
	 */
	public function attach_hooks() {

		if ( $this->sync_products ) {

			add_action( 'save_post', array( $this, 'on_save_post' ), 10, 2 );

		}

		if ( $this->sync_categories ) {

			add_action( 'created_product_cat', array( $this, 'on_category_modified' ) );

			add_action( 'edited_product_cat', array( $this, 'on_category_modified' ) );

		}

		if ( $this->sync_inventory  ) {
			$param = isset( $_GET['wc-api'] ) ? $_GET['wc-api'] : '';

			if ( 'WooSquare_Integration' !== $param ) {

				add_action( 'woocommerce_product_set_stock', array( $this, 'on_product_set_stock' ) );

				add_action( 'woocommerce_variation_set_stock', array( $this, 'on_variation_set_stock' ) );
			}
		}

	}

	/**
	 * Sync a WC Product to Square when it is saved.
	 *
	 * @param int $post_id
	 * @param bool $sync_categories
	 * @param bool $sync_inventory
	 * @param bool $sync_images
	 */
	public function process_save_post_event( $post_id ) {
		// clear inventory cache
		delete_transient( 'WooSquare_inventory' );
		
		$wc_product = wc_get_product( $post_id );

		if ( WooSquare_Utils::skip_product_sync( $post_id ) ) {
			return;
		}

		if ( is_object( $wc_product ) && ! empty( $wc_product ) ) {
			$this->square->sync_product( $wc_product, $this->sync_categories, $this->sync_inventory, $this->sync_images );
		}
	}

	/**
	 * Trigger the save post event.
	 *
	 * @see 'save_post'
	 * @param $post_id
	 * @param $post
	 */
	public function on_save_post( $post_id, $post ) {
		if (   ! $this->enabled
			|| ( defined( 'DOING_AJAX' ) && DOING_AJAX ) // TODO: Look into removing this check.
			|| ( defined( 'WP_LOAD_IMPORTERS' ) && WP_LOAD_IMPORTERS )
			|| wp_is_post_revision( $post )
			|| wp_is_post_autosave( $post )
			|| ( 'publish' !== get_post_status( $post ) )
			|| 'product' !== $post->post_type
		) {
			return $post_id;
		}

		$args = array(
			$post_id,
			uniqid() // this is needed due to WP not scheduling new events with same name and args
		);

		wp_schedule_single_event( time(), 'WooSquare_save_post_event', $args );
	}

	/**
	 * Sync categories to Square when a category is created or altered.
	 */
	public function on_category_modified() {

		if ( $this->enabled ) {

			$this->square->sync_categories();

		}

	}

	/**
	 * Sync inventory to Square when a product's stock is altered.
	 *
	 * @param WC_Product $wc_product
	 */
	public function on_product_set_stock( WC_Product $wc_product ) {
		$polling = get_option( 'WooSquare_polling', false );
		
		if ( $this->enabled && ! $polling ) {

			$this->square->sync_inventory( $wc_product );

		}

	}

	/**
	 * Sync inventory to Square when a variation's stock is altered.
	 *
	 * @param WC_Product_Variation $wc_variation
	 */
	public function on_variation_set_stock( WC_Product_Variation $wc_variation ) {
		$polling = get_option( 'WooSquare_polling', false );

		if ( $this->enabled && ! $polling ) {

			$this->square->sync_inventory( $wc_variation->parent );

		}

	}

}
