<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
require_once PGC_SGB_PATH . '/blocks/simply_post.php';
require_once PGC_SGB_PATH . '/blocks/simply_widget.php';
require_once PGC_SGB_PATH . '/blocks/class-elementor.php';
require_once PGC_SGB_PATH . '/blocks/simply_dashboard_widget.php';
function pgc_sgb_amp_item( $item )
{
    if ( !isset( $item ) ) {
        return '';
    }
    $assetsFolder = PGC_SGB_URL . 'assets/';
    $itemElemant = null;
    $image = array();
    
    if ( $item['type'] === 'video' || $item['type'] === 'audio' ) {
        
        if ( isset( $item['image'] ) && isset( $item['image']['width'] ) && intval( $item['image']['width'] ) >= 150 ) {
            $image['src'] = $item['image']['src'];
            $image['width'] = ( isset( $item['image']['width'] ) ? $item['image']['width'] : '300' );
            $image['height'] = ( isset( $item['image']['height'] ) ? $item['image']['height'] : '300' );
        } else {
            
            if ( $item['type'] === 'audio' ) {
                $image['src'] = $assetsFolder . 'holder-mp3.png';
                $image['width'] = '300';
                $image['height'] = '300';
            }
        
        }
    
    } else {
        
        if ( $item['type'] === 'image' ) {
            $srcset = '"';
            
            if ( isset( $item['sizes'] ) && isset( $item['sizes']['medium'] ) ) {
                $srcset = $srcset . esc_url( $item['sizes']['medium']['url'] ) . ' ';
                $srcset = $srcset . (( isset( $item['sizes']['medium']['width'] ) ? $item['sizes']['medium']['width'] : '300' )) . 'w';
            }
            
            
            if ( isset( $item['sizes'] ) && isset( $item['sizes']['large'] ) ) {
                $srcset = $srcset . ',';
                $srcset = $srcset . esc_url( $item['sizes']['large']['url'] ) . ' ';
                $srcset = $srcset . (( isset( $item['sizes']['large']['width'] ) ? $item['sizes']['large']['width'] : '300' )) . 'w';
            }
            
            $srcset = $srcset . ',';
            $srcset = $srcset . esc_url( $item['url'] ) . ' ';
            $srcset = $srcset . (( isset( $item['width'] ) ? $item['width'] : '300' )) . 'w';
            $srcset = $srcset . '" sizes="250px"';
            
            if ( isset( $item['sizes'] ) && isset( $item['sizes']['medium'] ) ) {
                $image['src'] = $item['sizes']['medium']['url'];
                $image['width'] = ( isset( $item['sizes']['medium']['width'] ) ? $item['sizes']['medium']['width'] : '300' );
                $image['height'] = ( isset( $item['sizes']['medium']['height'] ) ? $item['sizes']['medium']['height'] : '300' );
            } else {
                $image['src'] = $item['url'];
                $image['width'] = ( isset( $item['width'] ) ? $item['width'] : '300' );
                $image['height'] = ( isset( $item['height'] ) ? $item['height'] : '300' );
            }
        
        }
    
    }
    
    
    if ( $item['type'] === 'image' || $item['type'] === 'audio' ) {
        $itemElemant = '<img alt="' . esc_attr( ( isset( $item['alt'] ) ? $item['alt'] : '' ) ) . '" width="' . esc_attr( $image['width'] ) . '" height="' . esc_attr( $image['height'] ) . '" loading="lazy" ' . 'src="' . esc_url( $image['src'] ) . '"' . (( isset( $srcset ) ? ' srcset=' . $srcset : '' )) . '/>';
        
        if ( $item['type'] === 'audio' ) {
            $audioEl = '<audio controls src="' . esc_url( $item['url'] ) . '"></audio>';
            $itemElemant = $itemElemant . $audioEl;
        } else {
            
            if ( isset( $item['postlink'] ) ) {
                $itemElemant = '<a href="' . esc_url( $item['postlink'] ) . '" target="_blank">' . $itemElemant . '</a>';
            } else {
                $itemElemant = '<a href="' . esc_url( $item['url'] ) . '">' . $itemElemant . '</a>';
            }
        
        }
    
    } else {
        
        if ( $item['type'] === 'video' ) {
            $poster = ( $image ? 'poster="' . $image['src'] . '"' : '' );
            $itemElemant = '<video controls preload="none" ' . $poster . ' src="' . esc_url( $item['url'] ) . '"></video>';
        }
    
    }
    
    
    if ( isset( $itemElemant ) ) {
        
        if ( isset( $item['caption'] ) && $item['caption'] !== '' ) {
            $captionWrap = '<div class="sgb-item-caption"><em>' . wp_kses_post( $item['caption'] ) . '</em></div>';
            $itemElemant = $itemElemant . $captionWrap;
        }
        
        return $itemWrap = '<div class="sgb-item">' . $itemElemant . '</div>';
    }
    
    return '';
}

function pgc_sgb_noscript( $items )
{
    if ( !$items ) {
        return '';
    }
    $noscript = '';
    foreach ( $items as $item ) {
        $noscript = $noscript . pgc_sgb_amp_item( $item );
    }
    return $noscript;
}

function pgc_sgb_render_callback( $atr, $content )
{
    wp_enqueue_style( PGC_SGB_SLUG . '-frontend' );
    wp_enqueue_script( PGC_SGB_SLUG . '-script' );
    /** galleryType-1.1.0  galleryData-1.7.0 */
    if ( isset( $atr['galleryType'] ) === false ) {
        return $content;
    }
    $galleryDataArr = $atr;
    unset( $galleryDataArr['attachmentsIDsVerified'] );
    unset( $galleryDataArr['startPosIndex'] );
    unset( $galleryDataArr['selectedItems'] );
    $galleryQueryData = null;
    
    if ( isset( $atr['images'] ) ) {
        $galleryDataArr['images'] = array_map( 'pgc_sgb_prepare_item_for_js', $atr['images'] );
        $galleryDataArr['itemsMetaDataCollection'] = ( isset( $atr['itemsMetaDataCollection'] ) ? $atr['itemsMetaDataCollection'] : array() );
        $galleryData = serialize_block_attributes( $galleryDataArr );
    }
    
    $skinType = substr( $atr['galleryType'], 8 );
    $align = '';
    if ( isset( $atr['align'] ) ) {
        $align = $align . 'align' . $atr['align'];
    }
    $className = PGC_SGB_BLOCK_PREF . $skinType . ' ' . $align;
    if ( isset( $atr['className'] ) ) {
        $className = $className . ' ' . $atr['className'];
    }
    
    if ( $skinType === 'slider' || $skinType === 'splitcarousel' || $skinType === 'horizon' || $skinType === 'accordion' || $skinType === 'showcase' ) {
        $minHeight = ( isset( $atr['sliderMaxHeight'] ) ? esc_attr( $atr['sliderMaxHeight'] ) : 400 );
        $style = ' style="min-height:' . $minHeight . 'px"';
    }
    
    $noscript = '<div class="simply-gallery-amp pgc_sgb_slider ' . esc_attr( $align ) . '" style="display: none;"><div class="sgb-gallery">' . pgc_sgb_noscript( $atr['images'] ) . '</div></div>';
    $preloaderColor = ( isset( $galleryDataArr['galleryPreloaderColor'] ) ? $galleryDataArr['galleryPreloaderColor'] : '#d4d4d4' );
    $preloder = '<div class="sgb-preloader" id="pr_' . $atr['galleryId'] . '">
	<div class="sgb-square" style="background:' . esc_attr( $preloaderColor ) . '"></div>
	<div class="sgb-square" style="background:' . esc_attr( $preloaderColor ) . '"></div>
	<div class="sgb-square" style="background:' . esc_attr( $preloaderColor ) . '"></div>
	<div class="sgb-square" style="background:' . esc_attr( $preloaderColor ) . '"></div></div>';
    $html = '<div class="pgc-sgb-cb ' . $className . '" data-gallery-id="' . $atr['galleryId'] . '"' . (( isset( $style ) ? $style : '' )) . '>' . $preloder . $noscript . '<script type="application/json" class="sgb-data">' . $galleryData . '</script>' . '<script>(function(){if(window.PGC_SGB && window.PGC_SGB.searcher){window.PGC_SGB.searcher.initBlocks()}})()</script>' . '</div>';
    return $html;
}

function pgc_sgb_noscript_style()
{
    echo  '<noscript><style>.simply-gallery-amp{ display: block !important; }</style></noscript>' ;
    echo  '<noscript><style>.sgb-preloader{ display: none !important; }</style></noscript>' ;
}

add_action( 'wp_head', 'pgc_sgb_noscript_style' );
function pgc_sgb_action_customize_preview_init()
{
    wp_enqueue_style( PGC_SGB_SLUG . '-frontend' );
    wp_enqueue_script( PGC_SGB_SLUG . '-script' );
}

function pgc_sgb_ajaxQueryAttachmentsArgs( $query )
{
    
    if ( isset( $_REQUEST['query']['pgc_sgb'] ) && isset( $_REQUEST['query']['terms'] ) && isset( $_REQUEST['query']['taxonomy'] ) ) {
        $taxonomy = sanitize_text_field( $_REQUEST['query']['taxonomy'] );
        $terms = sanitize_text_field( $_REQUEST['query']['terms'] );
        
        if ( is_array( $terms ) ) {
            $terms = array_map( 'intval', $terms );
        } else {
            $terms = intval( $terms );
        }
        
        $tax_query = array( array(
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => $terms,
        ) );
    }
    
    $query['tax_query'] = $tax_query;
    return $query;
}

function pgc_sgb_block_assets()
{
    global  $pgc_sgb_skins_list, $pgc_sgb_skins_presets ;
    /** Searcher */
    wp_register_script(
        PGC_SGB_SLUG . '-script',
        PGC_SGB_URL . 'blocks/pgc_sgb.min.js',
        array(),
        PGC_SGB_VERSION,
        true
    );
    
    if ( is_admin() ) {
        register_post_meta( 'attachment', 'pgc_sgb_link', array(
            'show_in_rest'      => true,
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
            return current_user_can( 'edit_posts' );
        },
        ) );
        register_post_meta( 'attachment', 'pgc_sgb_tag', array(
            'show_in_rest'      => true,
            'type'              => 'string',
            'single'            => false,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
            return current_user_can( 'edit_posts' );
        },
        ) );
        $globalJS = array(
            'ajaxurl'       => admin_url( 'admin-ajax.php' ),
            'adminurl'      => get_admin_url(),
            'nonce'         => wp_create_nonce( 'pgc-sgb-nonce' ),
            'assets'        => PGC_SGB_URL . 'assets/',
            'postType'      => PGC_SGB_POST_TYPE,
            'taxonomy'      => PGC_SGB_TAXONOMY,
            'skinsFolder'   => PGC_SGB_URL . 'blocks/skins/',
            'searcher'      => PGC_SGB_URL . 'blocks/pgc_sgb.min.js' . '?ver=' . PGC_SGB_VERSION,
            'skinsList'     => $pgc_sgb_skins_list,
            'wpApiRoot'     => esc_url_raw( rest_url() ),
            'skinsSettings' => $pgc_sgb_skins_presets,
            'admin'         => is_admin(),
        );
        wp_localize_script( PGC_SGB_SLUG . '-script', 'PGC_SGB_ADMIN', $globalJS );
        wp_localize_script( PGC_SGB_SLUG . '-script', 'PGC_SGB', $globalJS );
    }
    
    /** Blocks Styles */
    wp_register_style(
        PGC_SGB_SLUG . '-editor',
        PGC_SGB_URL . 'dist/blocks.build.style.css',
        array( 'code-editor' ),
        PGC_SGB_VERSION
    );
    /** Main Blocks Script */
    wp_register_script(
        PGC_SGB_SLUG . '-js',
        PGC_SGB_URL . 'dist/blocks.build.js',
        array(
        'wp-blocks',
        'wp-i18n',
        'wp-element',
        'wp-block-editor',
        'wplink',
        'wp-data',
        'media',
        'media-grid',
        'backbone',
        'code-editor',
        'csslint',
        PGC_SGB_SLUG . '-script'
    ),
        PGC_SGB_VERSION,
        false
    );
    wp_enqueue_script( PGC_SGB_SLUG . '-editor' );
    /** Main Blocks Translatrion */
    if ( function_exists( 'wp_set_script_translations' ) ) {
        wp_set_script_translations( PGC_SGB_SLUG . '-js', 'simply-gallery-block', PGC_SGB_URL . 'languages' );
    }
    /** Main Blocks */
    /** Masonry */
    wp_register_style(
        PGC_SGB_SLUG . '-masonry',
        PGC_SGB_URL . 'blocks/skins/pgc_sgb_masonry.style.css',
        array( PGC_SGB_SLUG . '-editor' ),
        PGC_SGB_VERSION
    );
    register_block_type( 'pgcsimplygalleryblock/masonry', array(
        'style'           => PGC_SGB_SLUG . '-frontend',
        'editor_script'   => PGC_SGB_SLUG . '-js',
        'editor_style'    => PGC_SGB_SLUG . '-masonry',
        'render_callback' => 'pgc_sgb_render_callback',
    ) );
    /** Justified */
    wp_register_style(
        PGC_SGB_SLUG . '-justified',
        PGC_SGB_URL . 'blocks/skins/pgc_sgb_justified.style.css',
        array( PGC_SGB_SLUG . '-editor' ),
        PGC_SGB_VERSION
    );
    register_block_type( 'pgcsimplygalleryblock/justified', array(
        'style'           => PGC_SGB_SLUG . '-frontend',
        'editor_script'   => PGC_SGB_SLUG . '-js',
        'editor_style'    => PGC_SGB_SLUG . '-justified',
        'render_callback' => 'pgc_sgb_render_callback',
    ) );
    /** Grid */
    wp_register_style(
        PGC_SGB_SLUG . '-grid',
        PGC_SGB_URL . 'blocks/skins/pgc_sgb_grid.style.css',
        array( PGC_SGB_SLUG . '-editor' ),
        PGC_SGB_VERSION
    );
    register_block_type( 'pgcsimplygalleryblock/grid', array(
        'style'           => PGC_SGB_SLUG . '-frontend',
        'editor_script'   => PGC_SGB_SLUG . '-js',
        'editor_style'    => PGC_SGB_SLUG . '-grid',
        'render_callback' => 'pgc_sgb_render_callback',
    ) );
    /** Slider */
    wp_register_style(
        PGC_SGB_SLUG . '-slider',
        PGC_SGB_URL . 'blocks/skins/pgc_sgb_slider.style.css',
        array( PGC_SGB_SLUG . '-editor' ),
        PGC_SGB_VERSION
    );
    register_block_type( 'pgcsimplygalleryblock/slider', array(
        'style'           => PGC_SGB_SLUG . '-frontend',
        'editor_script'   => PGC_SGB_SLUG . '-js',
        'editor_style'    => PGC_SGB_SLUG . '-slider',
        'render_callback' => 'pgc_sgb_render_callback',
    ) );
    /** Viewer */
    wp_register_style(
        PGC_SGB_SLUG . '-viewer',
        PGC_SGB_URL . 'blocks/skins/pgc_sgb_viewer.style.css',
        array( PGC_SGB_SLUG . '-editor' ),
        PGC_SGB_VERSION
    );
    register_block_type( 'pgcsimplygalleryblock/viewer', array(
        'style'           => PGC_SGB_SLUG . '-frontend',
        'editor_script'   => PGC_SGB_SLUG . '-js',
        'editor_style'    => PGC_SGB_SLUG . '-viewer',
        'render_callback' => 'pgc_sgb_render_callback',
    ) );
}

add_action( 'init', 'pgc_sgb_block_assets' );
add_action(
    'customize_preview_init',
    'pgc_sgb_action_customize_preview_init',
    10,
    1
);