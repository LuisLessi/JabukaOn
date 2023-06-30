<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
function pgc_sgb_plugin_init()
{
    global  $pgc_sgb_global_lightbox_use ;
    $pgc_sgb_global_lightbox_use = get_option( 'pgc_sgb_global_lightbox_use' );
    register_meta( 'post', 'pgc_sgb_lightbox_settings', array(
        'show_in_rest'      => true,
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function () {
        return current_user_can( 'edit_posts' );
    },
    ) );
    wp_register_style(
        PGC_SGB_PLUGIN_SLUG . '-editor',
        PGC_SGB_URL . 'dist/plugin.build.style.css',
        array( 'wp-edit-blocks' ),
        PGC_SGB_VERSION
    );
    wp_register_script(
        PGC_SGB_PLUGIN_SLUG . '-script',
        PGC_SGB_URL . 'dist/plugin.build.js',
        array(
        'wp-plugins',
        'wp-edit-post',
        'wp-element',
        'wp-i18n',
        'wp-components',
        'wp-data'
    ),
        PGC_SGB_VERSION,
        true
    );
    $globalJS = array(
        'ajaxurl'        => admin_url( 'admin-ajax.php' ),
        'nonce'          => wp_create_nonce( 'pgc-sgb-nonce' ),
        'lightboxPreset' => get_option( 'pgc_sgb_lightbox' ),
        'globalLightbox' => $pgc_sgb_global_lightbox_use,
    );
    wp_localize_script( PGC_SGB_PLUGIN_SLUG . '-script', 'PGC_SGB_LIGHTBOX', $globalJS );
    if ( function_exists( 'wp_set_script_translations' ) ) {
        wp_set_script_translations( PGC_SGB_PLUGIN_SLUG . '-script', 'simply-gallery-block', PGC_SGB_URL . 'languages' );
    }
}

function pgc_sgb_plugin_frontend_scripts()
{
    global  $post, $pgc_sgb_global_lightbox_use ;
    if ( is_404() || is_search() ) {
        return;
    }
    
    if ( $pgc_sgb_global_lightbox_use && is_object( $post ) && ($post->post_type === 'post' || $post->post_type === 'page') ) {
        $lightboxURL = PGC_SGB_URL . 'plugins/pgc_sgb_lightbox.min.js';
        $lightboxStyleURL = PGC_SGB_URL . 'plugins/pgc_sgb_lightbox.min.style.css';
        $lightboxPreset = get_option( 'pgc_sgb_lightbox' );
        $field_value = get_post_meta( $post->ID, 'pgc_sgb_lightbox_settings', true );
        
        if ( isset( $field_value ) && $field_value !== '' ) {
            $field_value = json_decode( $field_value, true );
            if ( isset( $field_value ) ) {
                if ( isset( $field_value['enableLightbox'] ) ) {
                    if ( $field_value['enableLightbox'] === false ) {
                        return;
                    }
                }
            }
        }
        
        wp_enqueue_style(
            PGC_SGB_PLUGIN_SLUG . '-lightbox-style',
            $lightboxStyleURL,
            array(),
            PGC_SGB_VERSION
        );
        wp_enqueue_script(
            PGC_SGB_PLUGIN_SLUG . '-lightbox-script',
            $lightboxURL,
            false,
            PGC_SGB_VERSION,
            true
        );
        $globalJS = array(
            'lightboxPreset'  => $lightboxPreset,
            'postType'        => $post->post_type,
            'lightboxSettigs' => $field_value,
        );
        wp_localize_script( PGC_SGB_PLUGIN_SLUG . '-lightbox-script', 'PGC_SGB_LIGHTBOX', $globalJS );
    }

}

function pgc_sgb_plugin_enqueue_assets()
{
    /** Block Editor - Global Lightbox Panel/Plugin */
    global  $post, $pgc_sgb_global_lightbox_use, $pagenow ;
    if ( !$pgc_sgb_global_lightbox_use || $pgc_sgb_global_lightbox_use === false ) {
        return;
    }
    if ( is_object( $post ) && ($post->post_type === 'post' || $post->post_type === 'page') ) {
        
        if ( $pagenow !== 'widgets.php' ) {
            wp_enqueue_script( PGC_SGB_PLUGIN_SLUG . '-script' );
            wp_enqueue_style( PGC_SGB_PLUGIN_SLUG . '-editor' );
        }
    
    }
}

function pgc_sgb_activation_hook()
{
    if ( get_option( 'pgc_sgb_global_lightbox_use', null ) === null ) {
        add_option( 'pgc_sgb_global_lightbox_use', true );
    }
    flush_rewrite_rules();
}

function pgc_sgb_add_albums_preset_page()
{
    function pgc_sgb_plugin_albums_sh_options()
    {
        global  $pgc_sgb_skins_presets ;
        wp_enqueue_style(
            PGC_SGB_PLUGIN_SLUG . '-prem-albums-sh-page-settings',
            // Handle.
            PGC_SGB_URL . 'dist/albums.page.build.style.css',
            array( 'wp-components', 'code-editor' ),
            PGC_SGB_VERSION
        );
        wp_enqueue_script(
            PGC_SGB_PLUGIN_SLUG . '-prem-albums-page-sh-settings-script',
            PGC_SGB_URL . 'dist/albums.page.build.js',
            array(
            'wp-api',
            'wp-element',
            'wp-i18n',
            'wp-components',
            'code-editor',
            'csslint'
        ),
            PGC_SGB_VERSION,
            true
        );
        $globalJS = array(
            'adminurl'       => get_admin_url(),
            'postType'       => PGC_SGB_POST_TYPE,
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'pgc-sgb-nonce' ),
            'isPremium'      => json_encode( pgc_sgb_fs()->can_use_premium_code() ),
            'isPro'          => json_encode( pgc_sgb_fs()->is_plan_or_trial( 'pro' ) ),
            'skinsSettings'  => $pgc_sgb_skins_presets,
            'albumShcPreset' => get_option( 'pgc_sgb_album_shc_preset' ),
            'version'        => PGC_SGB_VERSION,
        );
        wp_localize_script( PGC_SGB_PLUGIN_SLUG . '-prem-albums-page-sh-settings-script', 'PGC_SGB_OPTIONS_PAGE', $globalJS );
    }
    
    function pgc_sgb_plugin_albums_sh_page()
    {
        echo  '<div id="' . PGC_SGB_PLUGIN_SLUG . '-prem-page"></div>' ;
    }
    
    $pr_sub_page_albums_hook_suffix = add_submenu_page(
        'edit.php?post_type=' . PGC_SGB_POST_TYPE,
        'SimpLy Premium',
        esc_html__( 'Albums Preset', 'simply-gallery-block' ),
        'manage_options',
        'pgc-simply-albums-presets',
        'pgc_sgb_plugin_albums_sh_page'
    );
    add_action( "admin_print_scripts-{$pr_sub_page_albums_hook_suffix}", 'pgc_sgb_plugin_albums_sh_options' );
}

add_action( 'admin_menu', 'pgc_sgb_add_albums_preset_page' );
function pgc_sgb_add_blocks_preset_page()
{
    function pgc_sgb_plugin_options_assets()
    {
        global  $pgc_sgb_global_lightbox_use, $pgc_sgb_skins_presets, $user_ID ;
        wp_enqueue_style(
            PGC_SGB_PLUGIN_SLUG . '-page-settings',
            PGC_SGB_URL . 'dist/page.build.style.css',
            array( 'wp-components', 'code-editor' ),
            PGC_SGB_VERSION
        );
        wp_enqueue_script(
            PGC_SGB_PLUGIN_SLUG . '-page-settings-script',
            PGC_SGB_URL . 'dist/page.build.js',
            array(
            'wp-api',
            'wp-element',
            'wp-i18n',
            'wp-components',
            'code-editor',
            'csslint'
        ),
            PGC_SGB_VERSION,
            true
        );
        $globalJS = array(
            'adminurl'       => get_admin_url(),
            'postType'       => PGC_SGB_POST_TYPE,
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'pgc-sgb-nonce' ),
            'globalLightbox' => $pgc_sgb_global_lightbox_use,
            'lightboxPreset' => get_option( 'pgc_sgb_lightbox' ),
            'skinsSettings'  => $pgc_sgb_skins_presets,
            'version'        => PGC_SGB_VERSION,
        );
        wp_localize_script( PGC_SGB_PLUGIN_SLUG . '-page-settings-script', 'PGC_SGB_OPTIONS_PAGE', $globalJS );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( PGC_SGB_PLUGIN_SLUG . '-page-settings-script', 'simply-gallery-block', PGC_SGB_URL . 'languages' );
        }
    }
    
    function pgc_sgb_print_global_preset()
    {
        echo  '<div id="' . PGC_SGB_PLUGIN_SLUG . '-settings-page"></div>' ;
    }
    
    $pr_sub_page_hook_suffix = add_submenu_page(
        'edit.php?post_type=' . PGC_SGB_POST_TYPE,
        'SimpLy Blocks Presets',
        ( pgc_sgb_fs()->can_use_premium_code() ? esc_html__( 'Blocks Presets - FREE', 'simply-gallery-block' ) : esc_html__( 'Blocks Presets', 'simply-gallery-block' ) ),
        'manage_options',
        'pgc-simply-presets',
        'pgc_sgb_print_global_preset'
    );
    add_action( "admin_print_scripts-{$pr_sub_page_hook_suffix}", 'pgc_sgb_plugin_options_assets' );
}

add_action( 'admin_menu', 'pgc_sgb_add_blocks_preset_page' );
function pgc_sgb_add_lightbox_admin_page()
{
    function pgc_sgb_plugin_lightbox_options_assets()
    {
        global  $pgc_sgb_global_lightbox_use ;
        wp_enqueue_style(
            PGC_SGB_PLUGIN_SLUG . '-lightbox-page-settings',
            PGC_SGB_URL . 'dist/lightbox.page.build.style.css',
            array( 'wp-components' ),
            PGC_SGB_VERSION
        );
        wp_enqueue_script(
            PGC_SGB_PLUGIN_SLUG . '-lightbox-page-settings-script',
            PGC_SGB_URL . 'dist/lightbox.page.build.js',
            array(
            'wp-api',
            'wp-element',
            'wp-i18n',
            'wp-components'
        ),
            PGC_SGB_VERSION,
            true
        );
        $globalJS = array(
            'adminurl'       => get_admin_url(),
            'postType'       => PGC_SGB_POST_TYPE,
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'pgc-sgb-nonce' ),
            'globalLightbox' => $pgc_sgb_global_lightbox_use,
            'lightboxPreset' => get_option( 'pgc_sgb_lightbox' ),
            'version'        => PGC_SGB_VERSION,
        );
        wp_localize_script( PGC_SGB_PLUGIN_SLUG . '-lightbox-page-settings-script', 'PGC_SGB_OPTIONS_PAGE', $globalJS );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( PGC_SGB_PLUGIN_SLUG . '-lightbox-page-settings-script', 'simply-gallery-block', PGC_SGB_URL . 'languages' );
        }
    }
    
    function pgc_sgb_plugin_lightbox_admin_page()
    {
        echo  '<div id="' . PGC_SGB_PLUGIN_SLUG . '-lightbox-page"></div>' ;
    }
    
    $pr_sub_page_lightbox_hook_suffix = add_submenu_page(
        'edit.php?post_type=' . PGC_SGB_POST_TYPE,
        'SimpLy Lightbox',
        esc_html__( 'Lightbox for native WordPress Gallery', 'simply-gallery-block' ),
        'manage_options',
        'pgc-simply-lightbox-options',
        'pgc_sgb_plugin_lightbox_admin_page'
    );
    add_action( "admin_print_scripts-{$pr_sub_page_lightbox_hook_suffix}", 'pgc_sgb_plugin_lightbox_options_assets' );
}

add_action( 'admin_menu', 'pgc_sgb_add_lightbox_admin_page' );
function pgc_sgb_add_welcome_page()
{
    function pgc_sgb_plugin_welcome_assets()
    {
        wp_enqueue_style(
            PGC_SGB_PLUGIN_SLUG . '-page-welcome',
            PGC_SGB_URL . 'dist/welcome.build.style.css',
            array( 'wp-components' ),
            PGC_SGB_VERSION
        );
        wp_enqueue_script(
            PGC_SGB_PLUGIN_SLUG . '-page-welcome-script',
            PGC_SGB_URL . 'dist/welcome.build.js',
            array(
            'wp-api',
            'wp-element',
            'wp-i18n',
            'wp-components'
        ),
            PGC_SGB_VERSION,
            true
        );
        $globalJS = array(
            'assets'   => PGC_SGB_URL . 'assets/',
            'adminurl' => get_admin_url(),
            'postType' => PGC_SGB_POST_TYPE,
            'version'  => PGC_SGB_VERSION,
        );
        wp_localize_script( PGC_SGB_PLUGIN_SLUG . '-page-welcome-script', 'PGC_SGB_WELCOME_PAGE', $globalJS );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( PGC_SGB_PLUGIN_SLUG . '-page-welcome-script', 'simply-gallery-block', PGC_SGB_URL . 'languages' );
        }
    }
    
    function pgc_sgb_print_welcome_page()
    {
        echo  '<div id="' . PGC_SGB_PLUGIN_SLUG . '-welcome-page"></div>' ;
    }
    
    
    if ( current_user_can( 'upload_files' ) ) {
        $pr_sub_page_hook_suffix = add_submenu_page(
            'edit.php?post_type=' . PGC_SGB_POST_TYPE,
            'Welcome',
            esc_html__( 'FEATURES & FAQ', 'simply-gallery-block' ),
            'read',
            'pgc-simply-welcome',
            'pgc_sgb_print_welcome_page'
        );
        add_action( "admin_print_scripts-{$pr_sub_page_hook_suffix}", 'pgc_sgb_plugin_welcome_assets' );
    }

}

add_action( 'admin_menu', 'pgc_sgb_add_welcome_page' );
add_action( 'init', 'pgc_sgb_plugin_init', 12 );
add_action( 'enqueue_block_editor_assets', 'pgc_sgb_plugin_enqueue_assets' );
add_action( 'wp_enqueue_scripts', 'pgc_sgb_plugin_frontend_scripts' );
register_activation_hook( PGC_SGB_FILE, 'pgc_sgb_activation_hook' );