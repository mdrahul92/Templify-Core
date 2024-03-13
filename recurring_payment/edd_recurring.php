<?php
add_action( 'admin_menu', 'subscriptions_list' , 10 );


 function subscriptions_list() {
    add_submenu_page(
        'edit.php?post_type=download',
        __( 'Subscriptions', 'edd-recurring' ),
        __( 'Subscriptions', 'edd-recurring' ),
        'view_subscriptions',
        'edd-subscriptions',
        'edd_subscriptions_page'
    );
}


require_once plugin_dir_path( __FILE__ ) . '/includes/admin/subscriptions.php';


