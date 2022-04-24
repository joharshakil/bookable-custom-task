<?php
if ( ! function_exists( 'qu_fs' ) ) {
    // Create a helper function for easy SDK access.
    function qu_fs() {
        global $qu_fs;

        if ( ! isset( $qu_fs ) ) {
            // Include Freemius SDK.
            require_once dirname(__FILE__) . '/freemius/start.php';

            $qu_fs = fs_dynamic_init( array(
                'id'                  => '4705',
                'slug'                => 'Square_plus',
                'type'                => 'plugin',
                'public_key'          => 'pk_99bc252f369ee6421bd7c67d0968c',
                'is_premium'          => true,
                'premium_suffix'      => 'Square +',
                // If your plugin is a serviceware, set this option to false.
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'menu'                => array(
                    'slug'           => 'woosquare-plus-module',
                    'first-path'     => 'admin.php?page=woosquare-plus-module',
                    'support'        => false,
                ),
                // Set the SDK to work in a sandbox mode (for development & testing).
                // IMPORTANT: MAKE SURE TO REMOVE SECRET KEY BEFORE DEPLOYMENT.
                'secret_key'          => 'sk_7)=OpE*MBel+9M1rjNwqkfjfQP7Pf',
            ) );
        }

        return $qu_fs;
    }

    // Init Freemius.
    qu_fs();
    // Signal that SDK was initiated.
    do_action( 'qu_fs_loaded' );
}