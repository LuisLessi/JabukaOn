<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
function pgc_sgb_dashboard_enqueue_scripts()
{
    global  $wp_meta_boxes, $pgc_sgb_skins_list ;
    $screen = get_current_screen();
    
    if ( 'dashboard' === $screen->base ) {
        wp_register_style(
            PGC_SGB_SLUG . '-dashboard-style',
            PGC_SGB_URL . 'blocks/dist/dashboard.widget.build.style.css',
            array( 'wp-components' ),
            PGC_SGB_VERSION
        );
        wp_enqueue_style( PGC_SGB_SLUG . '-dashboard-style' );
        /** Parser */
        wp_register_script(
            PGC_SGB_SLUG . '-dashboard-script',
            PGC_SGB_URL . 'blocks/dist/dashboard.widget.build.js',
            array( 'wp-element', 'wp-i18n', 'wp-components' ),
            PGC_SGB_VERSION,
            true
        );
        wp_enqueue_script( PGC_SGB_SLUG . '-dashboard-script' );
        $globalJS = array(
            'assets'    => PGC_SGB_URL . 'assets/',
            'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            'adminurl'  => get_admin_url(),
            'nonce'     => wp_create_nonce( 'pgc-sgb-nonce' ),
            'postType'  => PGC_SGB_POST_TYPE,
            'skinsList' => $pgc_sgb_skins_list,
            'version'   => PGC_SGB_VERSION,
        );
        wp_localize_script( PGC_SGB_SLUG . '-dashboard-script', 'PGC_SGB_DASHBOARD', $globalJS );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( PGC_SGB_SLUG . '-dashboard-script', 'simply-gallery-block', PGC_SGB_URL . 'languages' );
        }
    }

}

function pgc_sgb_dashboard_widget_render()
{
    echo  '<div id="' . PGC_SGB_SLUG . '-dashboard-widget"><div class="pgc-sgb-preloader">LOADING...</div></div>' ;
}

function pgc_sgb_add_dashboard_widget()
{
    if ( current_user_can( 'edit_others_posts' ) ) {
        wp_add_dashboard_widget( 'pgc_sgb_dashboard_widget', 'SimpLy ' . esc_html__( 'Gallery', 'simply-gallery-block' ), 'pgc_sgb_dashboard_widget_render' );
    }
}

add_action( 'wp_dashboard_setup', 'pgc_sgb_add_dashboard_widget' );
add_action( 'admin_enqueue_scripts', 'pgc_sgb_dashboard_enqueue_scripts' );