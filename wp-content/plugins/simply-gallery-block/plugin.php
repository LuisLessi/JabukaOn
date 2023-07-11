<?php

/**
 * Plugin Name: SimpLy Gallery Block & Lightbox
 * Plugin URI: https://simplygallery.co/
 * Description: The highly customizable Lightbox for native WordPress Gallery/Image. And beautiful gallery blocks with advanced Lightbox for photographers, video creators, writers and content marketers. This blocks set will help you create responsive Images, Video, Audio gallery. Three desired layout in one plugin - Masonry, Justified and Grid.
 * Author: GalleryCreator
 * Author URI: https://blockslib.com/
 * Version: 3.1.5
 * Text Domain: simply-gallery-block
 * Domain Path: /languages
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package SimpLy Gallery Block
 */
/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'pgc_sgb_fs' ) ) {
    pgc_sgb_fs()->set_basename( false, __FILE__ );
} else {
    define( 'PGC_SGB_VERSION', '3.1.5' );
    define( 'PGC_SGB_SLUG', 'simply-gallery-block' );
    define( 'PGC_SGB_BLOCK_PREF', 'wp-block-pgcsimplygalleryblock-' );
    define( 'PGC_SGB_PLUGIN_SLUG', 'pgc-simply-gallery-plugin' );
    define( 'PGC_SGB_POST_TYPE', 'pgc_simply_gallery' );
    define( 'PGC_SGB_TAXONOMY', 'pgc_simply_category' );
    define( 'PGC_SGB_FILE', __FILE__ );
    define( 'PGC_SGB_PATH', __DIR__ );
    define( 'PGC_SGB_DIRNAME', basename( PGC_SGB_PATH ) );
    $pgc_sgb_skins_list = array();
    $pgc_sgb_skins_presets = array();
    $pgc_sgb_global_lightbox_use = false;
    $pgc_sgb_wc_to_sgb = null;
    
    if ( !function_exists( 'pgc_sgb_fs' ) ) {
        // Create a helper function for easy SDK access.
        function pgc_sgb_fs()
        {
            global  $pgc_sgb_fs ;
            
            if ( !isset( $pgc_sgb_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $pgc_sgb_fs = fs_dynamic_init( array(
                    'id'             => '7208',
                    'slug'           => 'simply-gallery-block',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_0e7076e3ce718684690406736d9be',
                    'is_premium'     => false,
                    'premium_suffix' => 'Pro',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                    'menu'           => array(
                    'slug'       => 'edit.php?post_type=pgc_simply_gallery',
                    'first-path' => 'edit.php?post_type=pgc_simply_gallery&page=pgc-simply-welcome',
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $pgc_sgb_fs;
        }
        
        // Init Freemius.
        pgc_sgb_fs();
        // Signal that SDK was initiated.
        do_action( 'pgc_sgb_fs_loaded' );
    }
    
    function pgc_sgb_fs_uninstall_cleanup()
    {
        delete_option( "pgc_sgb_global_lightbox_use" );
        delete_site_option( 'pgc_sgb_global_lightbox_use' );
    }
    
    pgc_sgb_fs()->add_action( 'after_uninstall', 'pgc_sgb_fs_uninstall_cleanup' );
    function pgc_sgb_load_textdomain()
    {
        define( 'PGC_SGB_URL', plugin_dir_url( __FILE__ ) );
        load_plugin_textdomain( 'simply-gallery-block', false, basename( PGC_SGB_URL ) . '/languages' );
    }
    
    add_action( 'plugins_loaded', 'pgc_sgb_load_textdomain' );
    function pgc_sgb_getGlobalPresets()
    {
        global  $pgc_sgb_skins_list, $pgc_sgb_skins_presets ;
        $skins_folders = glob( realpath( dirname( __FILE__ ) ) . '/blocks/skins/*.js' );
        foreach ( $skins_folders as $file ) {
            $fileName = substr( $file, strrpos( $file, "/" ) + 1 );
            $skinSlug = substr( $fileName, 0, -3 );
            $pgc_sgb_skins_list[$skinSlug] = PGC_SGB_URL . 'blocks/skins/' . $fileName . '?ver=' . PGC_SGB_VERSION;
            $pgc_sgb_skins_presets[$skinSlug] = get_option( $skinSlug );
        }
    }
    
    add_action( 'init', 'pgc_sgb_getGlobalPresets', 1 );
    function pgc_sgb_prepare_attachment_post_for_sgb( $attachment )
    {
        if ( !$attachment ) {
            return;
        }
        if ( 'attachment' !== $attachment->post_type ) {
            return;
        }
        $meta = wp_get_attachment_metadata( $attachment->ID );
        
        if ( false !== strpos( $attachment->post_mime_type, '/' ) ) {
            list( $type, $subtype ) = explode( '/', $attachment->post_mime_type );
        } else {
            list( $type, $subtype ) = array( $attachment->post_mime_type, '' );
        }
        
        $attachment_url = wp_get_attachment_url( $attachment->ID );
        $base_url = str_replace( wp_basename( $attachment_url ), '', $attachment_url );
        $response = array(
            'id'          => $attachment->ID,
            'title'       => $attachment->post_title,
            'filename'    => wp_basename( get_attached_file( $attachment->ID ) ),
            'url'         => $attachment_url,
            'link'        => get_attachment_link( $attachment->ID ),
            'alt'         => get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ),
            'description' => $attachment->post_content,
            'caption'     => $attachment->post_excerpt,
            'date'        => strtotime( $attachment->post_date_gmt ) * 1000,
            'mime'        => $attachment->post_mime_type,
            'type'        => $type,
            'subtype'     => $subtype,
            'meta'        => false,
        );
        if ( $meta && ('image' === $type || !empty($meta['image_meta'])) ) {
            $response['imageMeta'] = $meta['image_meta'];
        }
        
        if ( $meta && ('image' === $type || !empty($meta['sizes'])) ) {
            $sizes = array();
            $possible_sizes = apply_filters( 'image_size_names_choose', array(
                'thumbnail' => __( 'Thumbnail' ),
                'medium'    => __( 'Medium' ),
                'large'     => __( 'Large' ),
                'full'      => __( 'Full Size' ),
            ) );
            unset( $possible_sizes['full'] );
            foreach ( $possible_sizes as $size => $label ) {
                $downsize = apply_filters(
                    'image_downsize',
                    false,
                    $attachment->ID,
                    $size
                );
                
                if ( $downsize ) {
                    if ( empty($downsize[3]) ) {
                        continue;
                    }
                    $sizes[$size] = array(
                        'height'      => $downsize[2],
                        'width'       => $downsize[1],
                        'url'         => $downsize[0],
                        'orientation' => ( $downsize[2] > $downsize[1] ? 'portrait' : 'landscape' ),
                    );
                } elseif ( isset( $meta['sizes'][$size] ) ) {
                    $size_meta = $meta['sizes'][$size];
                    $height = ( isset( $size_meta['height'] ) ? $size_meta['height'] : 300 );
                    $width = ( isset( $size_meta['width'] ) ? $size_meta['width'] : 300 );
                    $sizes[$size] = array(
                        'height'      => $height,
                        'width'       => $width,
                        'url'         => $base_url . $size_meta['file'],
                        'orientation' => ( $height > $width ? 'portrait' : 'landscape' ),
                    );
                }
            
            }
            
            if ( 'image' === $type ) {
                
                if ( !empty($meta['original_image']) ) {
                    $response['originalImageURL'] = wp_get_original_image_url( $attachment->ID );
                    $response['originalImageName'] = wp_basename( wp_get_original_image_path( $attachment->ID ) );
                }
                
                $sizes['full'] = array(
                    'url' => $attachment_url,
                );
                
                if ( isset( $meta['height'], $meta['width'] ) ) {
                    $sizes['full']['height'] = $meta['height'];
                    $sizes['full']['width'] = $meta['width'];
                    $sizes['full']['orientation'] = ( $meta['height'] > $meta['width'] ? 'portrait' : 'landscape' );
                }
                
                $response = array_merge( $response, $sizes['full'] );
            } elseif ( $meta['sizes']['full']['file'] ) {
                $sizes['full'] = array(
                    'url'         => $base_url . $meta['sizes']['full']['file'],
                    'height'      => $meta['sizes']['full']['height'],
                    'width'       => $meta['sizes']['full']['width'],
                    'orientation' => ( $meta['sizes']['full']['height'] > $meta['sizes']['full']['width'] ? 'portrait' : 'landscape' ),
                );
            }
            
            $response = array_merge( $response, array(
                'sizes' => $sizes,
            ) );
        }
        
        
        if ( $meta && 'video' === $type ) {
            if ( isset( $meta['width'] ) ) {
                $response['width'] = (int) $meta['width'];
            }
            if ( isset( $meta['height'] ) ) {
                $response['height'] = (int) $meta['height'];
            }
        }
        
        
        if ( $meta && ('audio' === $type || 'video' === $type) ) {
            
            if ( isset( $meta['length_formatted'] ) ) {
                $response['fileLength'] = $meta['length_formatted'];
                $response['fileLengthHumanReadable'] = human_readable_duration( $meta['length_formatted'] );
            }
            
            $response['meta'] = array();
            foreach ( wp_get_attachment_id3_keys( $attachment, 'js' ) as $key => $label ) {
                $response['meta'][$key] = false;
                if ( !empty($meta[$key]) ) {
                    $response['meta'][$key] = $meta[$key];
                }
            }
            $id = get_post_thumbnail_id( $attachment->ID );
            
            if ( !empty($id) ) {
                list( $src, $width, $height ) = wp_get_attachment_image_src( $id, 'full' );
                $response['image'] = compact( 'src', 'width', 'height' );
                list( $src, $width, $height ) = wp_get_attachment_image_src( $id, 'thumbnail' );
                $response['thumb'] = compact( 'src', 'width', 'height' );
            } else {
                $src = wp_mime_type_icon( $attachment->ID );
                $width = 48;
                $height = 64;
                $response['image'] = compact( 'src', 'width', 'height' );
                $response['thumb'] = compact( 'src', 'width', 'height' );
            }
        
        }
        
        return $response;
    }
    
    function pgc_sgb_woocommerce_helper()
    {
        global  $pgc_sgb_wc_to_sgb ;
    }
    
    add_action( 'init', 'pgc_sgb_woocommerce_helper' );
    /** Frontend Script and Style */
    function pgc_sgb_menager_script()
    {
        global  $pgc_sgb_skins_list, $pgc_sgb_skins_presets ;
        /**  Block style CSS. */
        wp_register_style(
            PGC_SGB_SLUG . '-frontend',
            PGC_SGB_URL . 'blocks/pgc_sgb.min.style.css',
            array(),
            PGC_SGB_VERSION
        );
        /** Parser */
        wp_register_script(
            PGC_SGB_SLUG . '-script',
            PGC_SGB_URL . 'blocks/pgc_sgb.min.js',
            array(),
            PGC_SGB_VERSION,
            true
        );
        $globalJS = array(
            'assets'        => PGC_SGB_URL . 'assets/',
            'skinsFolder'   => PGC_SGB_URL . 'blocks/skins/',
            'skinsList'     => $pgc_sgb_skins_list,
            'wpApiRoot'     => esc_url_raw( rest_url() ),
            'postType'      => PGC_SGB_POST_TYPE,
            'skinsSettings' => $pgc_sgb_skins_presets,
        );
        wp_localize_script( PGC_SGB_SLUG . '-script', 'PGC_SGB', $globalJS );
    }
    
    add_action( 'wp_enqueue_scripts', 'pgc_sgb_menager_script' );
    function pgc_sgb_update_tags_list( $tagsArr, $delete = NULL )
    {
        $tagsListString = get_option( 'pgc_sgb_tags_list' );
        if ( $tagsListString ) {
            $tagsList = explode( ",", $tagsListString );
        }
        $tagsString = '';
        
        if ( $tagsList && !empty($tagsList) ) {
            foreach ( $tagsArr as $value ) {
                
                if ( is_null( $delete ) ) {
                    if ( array_search( $value, $tagsList ) === false ) {
                        $tagsString = $tagsString . ',' . $value;
                    }
                } else {
                    
                    if ( ($key = array_search( $value, $tagsList )) !== false ) {
                        unset( $tagsList[$key] );
                        //$tagsString = $tagsString . ',' . $value;
                    }
                
                }
            
            }
            
            if ( is_null( $delete ) ) {
                
                if ( $tagsString !== '' ) {
                    $tagsString = $tagsListString . $tagsString;
                } else {
                    $tagsString = $tagsListString;
                }
            
            } else {
                $tagsString = implode( ",", $tagsList );
            }
        
        } else {
            if ( is_null( $delete ) ) {
                $tagsString = implode( ",", $tagsArr );
            }
        }
        
        $res = array();
        if ( !is_null( $delete ) ) {
            $res['delete'] = true;
        }
        $tagsString = sanitize_text_field( $tagsString );
        $res['tagsList'] = $tagsString;
        $res['status'] = update_option( 'pgc_sgb_tags_list', $tagsString );
        return $res;
    }
    
    function pgc_sgb_can_write_direct( $path )
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        
        if ( get_filesystem_method( array(), $path, true ) === 'direct' ) {
            $creds = request_filesystem_credentials(
                site_url() . '/wp-admin/',
                '',
                false,
                false,
                array()
            );
            if ( !WP_Filesystem( $creds ) ) {
                return false;
            }
            return true;
        }
        
        return false;
    }
    
    function pgc_sgb_action_wizard()
    {
        global  $post, $pgc_sgb_wc_to_sgb ;
        check_ajax_referer( 'pgc-sgb-nonce', 'nonce' );
        $globaldata = sanitize_text_field( stripslashes( $_POST['props'] ) );
        $json = json_decode( $globaldata, true );
        $out = array();
        $out['message'] = array();
        $data = array();
        switch ( $json['type'] ) {
            case 'create_post_thumbnail':
                
                if ( current_user_can( 'add_post_meta', intval( $json['postId'] ) ) ) {
                    $videoName = sanitize_text_field( wp_unslash( $json['name'] ) );
                    $imgData = wp_unslash( $_POST['thumb_raw_data'] );
                    
                    if ( isset( $imgData ) ) {
                        $uploadsDir = wp_upload_dir();
                        $posterName = $videoName . '_poster';
                        wp_mkdir_p( $uploadsDir['path'] . '/poster_tmp' );
                        $tmpPosterPath = $uploadsDir['path'] . '/poster_tmp/' . $posterName . '.jpg';
                        $raw_png = str_replace( 'data:image/png;base64,', '', $imgData );
                        $raw_png = str_replace( 'data:image/jpeg;base64,', '', $imgData );
                        $raw_png = str_replace( ' ', '+', $raw_png );
                        $decoded_png = base64_decode( $raw_png );
                        
                        if ( pgc_sgb_can_write_direct( dirname( $tmpPosterPath ) ) ) {
                            global  $wp_filesystem ;
                            $success = $wp_filesystem->put_contents( $tmpPosterPath, $decoded_png );
                            
                            if ( $success ) {
                                $file = array(
                                    'name'     => $posterName . '.jpg',
                                    'type'     => mime_content_type( $tmpPosterPath ),
                                    'tmp_name' => $tmpPosterPath,
                                    'size'     => filesize( $tmpPosterPath ),
                                );
                                $sideload = wp_handle_sideload( $file, array(
                                    'test_form' => false,
                                ) );
                                
                                if ( !empty($sideload['error']) ) {
                                    $out['message']['poster'] = 'false';
                                    $out['message']['error'] = $sideload;
                                } else {
                                    $attachment_id = wp_insert_attachment( array(
                                        'guid'           => $sideload['url'],
                                        'post_mime_type' => $sideload['type'],
                                        'post_title'     => preg_replace( '/\\.[^.]+$/', '', basename( $sideload['file'] ) ),
                                        'post_content'   => '',
                                        'post_status'    => 'inherit',
                                    ), $sideload['file'] );
                                    
                                    if ( is_wp_error( $attachment_id ) || !$attachment_id ) {
                                        $out['message']['poster'] = 'false';
                                        $out['message']['error'] = 'Error: insert attachment';
                                    } else {
                                        require_once ABSPATH . 'wp-admin/includes/image.php';
                                        $meta_data = wp_generate_attachment_metadata( $attachment_id, $sideload['file'] );
                                        wp_update_attachment_metadata( $attachment_id, $meta_data );
                                        $wp_filesystem->delete( $tmpPosterPath );
                                        $out['message']['metaData'] = $meta_data;
                                        $out['message']['sideload'] = $sideload;
                                        $out['message']['posterId'] = $attachment_id;
                                        $out['message']['poster'] = set_post_thumbnail( intval( $json['postId'] ), $attachment_id );
                                    }
                                
                                }
                            
                            } else {
                                $out['message']['poster'] = 'false';
                                $out['message']['error'] = 'Error: temp file';
                            }
                        
                        }
                    
                    } else {
                        $out['message']['poster'] = 'false';
                        $out['message']['error'] = 'Error: Image data';
                    }
                
                }
                
                break;
            case 'update_post_thumbnail':
                if ( current_user_can( 'add_post_meta', intval( $json['postId'] ) ) ) {
                    
                    if ( intval( $json['value'] ) === 0 ) {
                        $out['message'][$json['key']] = delete_post_thumbnail( intval( $json['postId'] ) );
                    } else {
                        $out['message'][$json['key']] = set_post_thumbnail( intval( $json['postId'] ), intval( $json['value'] ) );
                    }
                
                }
                break;
            case 'update_post_meta':
                if ( current_user_can( 'add_post_meta', intval( $json['postId'] ) ) ) {
                    $out['message'][$json['key']] = update_post_meta( $json['postId'], $json['key'], $json['value'] );
                }
                break;
            case 'add_posts_meta':
                $tagsArr = $json['value'];
                $postIDs = $json['postIDs'];
                $key = $json['key'];
                
                if ( isset( $tagsArr ) && isset( $postIDs ) && isset( $key ) && $key === 'pgc_sgb_tag' ) {
                    $out['message'][$json['key']] = true;
                    foreach ( $postIDs as $postId ) {
                        
                        if ( current_user_can( 'add_post_meta', intval( $postId ) ) ) {
                            $itemTags = get_post_meta( $postId, 'pgc_sgb_tag' );
                            foreach ( $tagsArr as $val ) {
                                
                                if ( $val !== '' ) {
                                    if ( !isset( $data[$postId] ) ) {
                                        $data[$postId] = array();
                                    }
                                    
                                    if ( !empty($itemTags) ) {
                                        if ( array_search( $val, $itemTags ) === false ) {
                                            if ( add_post_meta(
                                                $postId,
                                                $key,
                                                $val,
                                                false
                                            ) ) {
                                                array_push( $data[$postId], $val );
                                            }
                                        }
                                    } else {
                                        if ( add_post_meta(
                                            $postId,
                                            $key,
                                            $val,
                                            false
                                        ) ) {
                                            array_push( $data[$postId], $val );
                                        }
                                    }
                                
                                }
                            
                            }
                        }
                        
                        $out['message']['tags_list'] = pgc_sgb_update_tags_list( $tagsArr );
                    }
                }
                
                break;
            case 'delete_posts_meta':
                $tagsArr = $json['value'];
                $postIDs = $json['postIDs'];
                $key = $json['key'];
                
                if ( isset( $tagsArr ) && isset( $postIDs ) && isset( $key ) && $key === 'pgc_sgb_tag' ) {
                    $out['message'][$json['key']] = true;
                    foreach ( $postIDs as $postId ) {
                        if ( current_user_can( 'delete_post_meta', intval( $postId ) ) ) {
                            foreach ( $tagsArr as $val ) {
                                if ( !isset( $data[$postId] ) ) {
                                    $data[$postId] = array();
                                }
                                if ( delete_post_meta( $postId, $json['key'], $val ) ) {
                                    array_push( $data[$postId], $val );
                                }
                            }
                        }
                    }
                }
                
                break;
            case 'get_attachments_for_admin':
                $query = ( array_key_exists( 'query', $json ) ? $json['query'] : null );
                
                if ( !current_user_can( 'upload_files' ) || !$query ) {
                    $out['message']['success'] = false;
                } else {
                    $q_args = array(
                        'post_mime_type' => array( 'image', 'video', 'audio' ),
                        'post_status'    => 'inherit',
                        'post_type'      => 'attachment',
                        'orderby'        => 'post__in',
                        'order'          => 'DESC',
                        'posts_per_page' => -1,
                        'paged'          => 1,
                    );
                    
                    if ( isset( $query['tax_query'] ) ) {
                        $tax_query = array();
                        foreach ( $query['tax_query'] as $tax ) {
                            array_push( $tax_query, (array) $tax );
                        }
                        $query['tax_query'] = $tax_query;
                    }
                    
                    
                    if ( isset( $query['meta_query'] ) ) {
                        $meta_query = array(
                            'relation' => 'OR',
                        );
                        foreach ( $query['meta_query'] as $meta ) {
                            array_push( $meta_query, (array) $meta );
                        }
                        $query['meta_query'] = $meta_query;
                    }
                    
                    $query = array_merge( $q_args, (array) $query );
                    $attachments_query = new WP_Query( $query );
                    $posts = array_map( 'wp_prepare_attachment_for_js', $attachments_query->posts );
                    $postsWithMeta = array();
                    $itemsMetaDataCollection = array();
                    foreach ( $posts as $my_post ) {
                        $meta_data = get_post_meta( $my_post['id'] );
                        $attachment_meta_data = wp_get_attachment_metadata( $my_post['id'], true );
                        if ( $meta_data ) {
                            
                            if ( isset( $meta_data['pgc_sgb_link'] ) || isset( $meta_data['pgc_sgb_tag'] ) ) {
                                $itemSubMeta = array();
                                $itemSubMeta['id'] = $my_post['id'];
                                
                                if ( isset( $meta_data['pgc_sgb_link'] ) ) {
                                    $linkMeta = json_decode( $meta_data['pgc_sgb_link'][0], true );
                                    if ( $linkMeta ) {
                                        $itemSubMeta = array_merge( $itemSubMeta, $linkMeta );
                                    }
                                }
                                
                                if ( isset( $meta_data['pgc_sgb_tag'] ) ) {
                                    $itemSubMeta['tags'] = $meta_data['pgc_sgb_tag'];
                                }
                                array_push( $itemsMetaDataCollection, $itemSubMeta );
                            }
                        
                        }
                        $postExt = $my_post;
                        
                        if ( $attachment_meta_data ) {
                            
                            if ( isset( $attachment_meta_data['sizes'] ) && isset( $postExt['sizes'] ) ) {
                                
                                if ( isset( $postExt['sizes']['large'] ) && isset( $attachment_meta_data['sizes']['large'] ) ) {
                                    $postExt['sizes']['large']['height'] = $attachment_meta_data['sizes']['large']['height'];
                                    $postExt['sizes']['large']['width'] = $attachment_meta_data['sizes']['large']['width'];
                                }
                                
                                
                                if ( isset( $postExt['sizes']['medium'] ) && isset( $attachment_meta_data['sizes']['medium'] ) ) {
                                    $postExt['sizes']['medium']['height'] = $attachment_meta_data['sizes']['medium']['height'];
                                    $postExt['sizes']['medium']['width'] = $attachment_meta_data['sizes']['medium']['width'];
                                }
                            
                            }
                            
                            if ( isset( $attachment_meta_data['image_meta'] ) ) {
                                $postExt['imageMeta'] = $attachment_meta_data['image_meta'];
                            }
                        }
                        
                        array_push( $postsWithMeta, $postExt );
                    }
                    $out['message']['success'] = true;
                    $out['message']['itemsMetaData'] = $itemsMetaDataCollection;
                    $data = $postsWithMeta;
                }
                
                wp_reset_query();
                break;
            case 'get_attachments_metadata':
                foreach ( $json['iDs'] as $i => $value ) {
                    if ( current_user_can( 'read_post', intval( $json['iDs'][$i] ) ) ) {
                        $data[$json['iDs'][$i]] = wp_get_attachment_metadata( $json['iDs'][$i], true );
                    }
                }
                break;
            case 'get_posts_metadata':
                foreach ( $json['iDs'] as $i => $value ) {
                    
                    if ( current_user_can( 'read_post', intval( $json['iDs'][$i] ) ) ) {
                        $main_meta = get_post_meta( $json['iDs'][$i], ( $json['key'] ? $json['key'] : '' ), true );
                        $tags = get_post_meta( $json['iDs'][$i], 'pgc_sgb_tag' );
                        
                        if ( !$main_meta || !empty($main_meta) ) {
                            $main_meta = json_decode( $main_meta, true );
                        } else {
                            $main_meta = array();
                        }
                        
                        if ( $tags || !empty($main_meta) ) {
                            $main_meta['tags'] = $tags;
                        }
                        if ( !empty($main_meta) ) {
                            $data[$json['iDs'][$i]] = json_encode( $main_meta );
                        }
                    }
                
                }
                break;
            case 'update_tags_list':
                $value = $json['value'];
                
                if ( current_user_can( 'edit_posts' ) ) {
                    $out['message'][$json['key']] = true;
                    $out['message']['tags_list'] = pgc_sgb_update_tags_list( $value, ( $json['action'] === 'delete' ? true : NULL ) );
                }
                
                break;
            case 'update_option':
                if ( current_user_can( 'edit_posts' ) ) {
                    foreach ( $json['options'] as $key => $value ) {
                        if ( strpos( $key, 'pgc_sgb' ) === 0 ) {
                            $out['message'][$key] = update_option( $key, $value );
                        }
                    }
                }
                break;
            case 'get_option':
                if ( current_user_can( 'edit_posts' ) ) {
                    foreach ( $json['options'] as $key => $value ) {
                        if ( strpos( $key, 'pgc_sgb' ) === 0 ) {
                            $out['message'][$key] = get_option( $key );
                        }
                    }
                }
                break;
            case 'get_categories_by_taxonomy':
                $taxonomy = $json['taxonomy'];
                $categories = get_categories( [
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => 1,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ] );
                $data = [];
                foreach ( $categories as $cat ) {
                    $catData = array();
                    $catData['term_name'] = $cat->name;
                    $catData['term_id'] = $cat->term_id;
                    $catData['count'] = $cat->count;
                    $catData['description'] = $cat->category_description;
                    array_push( $data, $catData );
                }
                break;
            case 'get_posts_by_type':
                $my_query = null;
                $postType = $json['postType'];
                $extended = ( array_key_exists( 'extended', $json ) ? true : false );
                $term_id = ( isset( $json['term_id'] ) ? $json['term_id'] : null );
                $q_args = [
                    'post_type'      => $postType,
                    'post_status'    => 'publish',
                    'posts_per_page' => -1,
                ];
                if ( isset( $term_id ) ) {
                    $q_args['tax_query'] = array( array(
                        'taxonomy'         => PGC_SGB_TAXONOMY,
                        'terms'            => $term_id,
                        'include_children' => false,
                    ) );
                }
                $my_query = new WP_Query( $q_args );
                
                if ( $my_query->have_posts() ) {
                    $postslist = $my_query->posts;
                    $data = [];
                    foreach ( $postslist as $post ) {
                        $postData = array();
                        $postData['title'] = ( $post->post_title !== '' ? $post->post_title : $post->ID );
                        $postData['ID'] = $post->ID;
                        
                        if ( current_user_can( 'read_post', intval( $postData['ID'] ) ) ) {
                            
                            if ( $extended ) {
                                $postData['id'] = $post->ID;
                                $postData['modified'] = $post->post_modified;
                                $postData['date'] = $post->post_date;
                                $postData['postLink'] = get_post_permalink( $post->ID );
                                $postData['type'] = $post->post_type;
                                $postData['slug'] = $post->post_name;
                            }
                            
                            
                            if ( has_post_thumbnail( $post ) ) {
                                $postData['thumbURL'] = get_the_post_thumbnail_url( $post, 'thumbnail' );
                                
                                if ( $extended ) {
                                    $thumbId = get_post_thumbnail_id( $post );
                                    
                                    if ( $thumbId !== false ) {
                                        $thumbPost = get_post( $thumbId );
                                        $thumbData = wp_prepare_attachment_for_js( $thumbPost );
                                        $postData['thumb'] = ( isset( $thumbData['sizes'] ) ? $thumbData['sizes'] : null );
                                    }
                                
                                }
                            
                            } else {
                                
                                if ( $extended ) {
                                    $postData['thumbURL'] = PGC_SGB_URL . 'assets/coverAlbum-400x400.png';
                                } else {
                                    $postData['thumbURL'] = PGC_SGB_URL . 'assets/icon-150x150.png';
                                }
                            
                            }
                            
                            array_push( $data, $postData );
                        }
                    
                    }
                }
                
                wp_reset_query();
                break;
            case 'get_post_content':
                $id = intval( $json['postID'] );
                
                if ( current_user_can( 'read_post', $id ) ) {
                    $postData = get_post_field( 'post_content', $id, 'display' );
                    $output = '';
                    
                    if ( $postData !== '' || !is_wp_error( $postData ) ) {
                        $blocks = parse_blocks( $postData );
                        foreach ( $blocks as $block ) {
                            $output .= render_block( $block );
                        }
                    }
                    
                    $data['raw'] = $output;
                }
                
                break;
            case 'get_terms_for_taxonomy':
                $taxonomyName = $json['name'];
                $terms = get_terms( [
                    'taxonomy'   => $taxonomyName,
                    'hide_empty' => false,
                ] );
                $data[$taxonomyName] = $terms;
                break;
            case 'deletePosts':
                $p_arg = array(
                    'post_type'   => $json['post_type'],
                    'post_status' => 'publish',
                );
                if ( isset( $json['name'] ) ) {
                    $p_arg['name'] = $json['name'];
                }
                $posts = get_posts( $p_arg );
                if ( !empty($posts) ) {
                    foreach ( $posts as $dl_post ) {
                        $postId = intval( $dl_post->ID );
                        
                        if ( current_user_can( 'delete_post', intval( $postId ) ) ) {
                            $deleted = is_object( wp_delete_post( $postId ) );
                            array_push( $data, array(
                                $postId . '' => $deleted,
                            ) );
                        }
                    
                    }
                }
                break;
            case 'get_products_for_admin':
                
                if ( isset( $pgc_sgb_wc_to_sgb ) ) {
                    $query = ( array_key_exists( 'query', $json ) ? $json['query'] : null );
                    
                    if ( isset( $query['naviHelper'] ) ) {
                        $out['message']['naviHelper'] = $query['naviHelper'];
                        unset( $query['naviHelper'] );
                    }
                    
                    $q_args = array();
                    $q_args = array(
                        'post_status' => 'publish',
                        'perm'        => 'readable',
                    );
                    
                    if ( isset( $query['tax_query'] ) ) {
                        $tax_query = array();
                        foreach ( $query['tax_query'] as $tax ) {
                            array_push( $tax_query, (array) $tax );
                        }
                        $query['tax_query'] = $tax_query;
                    }
                    
                    $query = array_merge( $q_args, (array) $query );
                    $products_query = new WP_Query( $query );
                    $posts = $products_query->posts;
                    $productsData = $pgc_sgb_wc_to_sgb( $posts );
                    $products = $productsData['products'];
                    $itemsMetaDataCollection = $productsData['itemsMetaData'];
                    $out['message']['success'] = true;
                    $out['message']['itemsMetaData'] = $itemsMetaDataCollection;
                    $data = $products;
                    wp_reset_query();
                } else {
                    $out['message']['success'] = false;
                    $data = array();
                }
                
                break;
        }
        $out['message']['data'] = $data;
        header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
        echo  json_encode( $out ) ;
        wp_die();
    }
    
    if ( wp_doing_ajax() ) {
        add_action( 'wp_ajax_pgc_sgb_action_wizard', 'pgc_sgb_action_wizard' );
    }
    require_once plugin_dir_path( __FILE__ ) . 'blocks/init.php';
    require_once plugin_dir_path( __FILE__ ) . 'plugins/init.php';
}
