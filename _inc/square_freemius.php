<?php

if ( !function_exists( 'woosquare_fs' ) ) {
    // Create a helper function for easy SDK access.
    function woosquare_fs()
    {
        global  $woosquare_fs ;
        
        if ( !isset( $woosquare_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $woosquare_fs = fs_dynamic_init( array(
                'id'             => '1378',
                'slug'           => 'woosquare',
                'type'           => 'plugin',
                'public_key'     => 'pk_823382e5b579047e3a8bb6fa6790d',
                'is_premium'     => true,
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'           => 'square-settings',
                'override_exact' => true,
                'contact'        => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $woosquare_fs;
    }
    
    // Init Freemius.
    woosquare_fs();
    // Signal that SDK was initiated.
    do_action( 'woosquare_fs_loaded' );
    function woosquare_fs_settings_url()
    {
        return admin_url( 'admin.php?page=square-settings' );
    }
    
    woosquare_fs()->add_filter( 'connect_url', 'woosquare_fs_settings_url' );
    woosquare_fs()->add_filter( 'after_skip_url', 'woosquare_fs_settings_url' );
    woosquare_fs()->add_filter( 'after_connect_url', 'woosquare_fs_settings_url' );
    woosquare_fs()->add_filter( 'after_pending_connect_url', 'woosquare_fs_settings_url' );
}
