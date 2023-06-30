<?php
if (!defined('ABSPATH')) {
	exit;
}
function pgc_sgb_shortcode_render($atts, $content = null)
{
	if (!is_array($atts) && !isset($atts['id'])) {
		return '';
	}
	if (!empty($atts['id'])) {
		$post_id = intval($atts['id']);
		$postData = get_post_field('post_content', $post_id, 'raw');
	} elseif (!empty($atts['slug'])) {
		$post_slug = $atts['slug'];
		$post = get_page_by_path($post_slug, ARRAY_A, PGC_SGB_POST_TYPE);
		if (isset($post)) {
			$postData = $post['post_content'];
		} else {
			$postData = '';
		}
	}
	if ($postData === '' || is_wp_error($postData)) return '';
	$blocks = parse_blocks($postData);
	$output = '';

	foreach ($blocks as $block) {
		$output .= render_block($block);
	}
	$priority = has_filter('the_content', 'wpautop');
	if (false !== $priority && doing_filter('the_content') && has_blocks($content)) {
		remove_filter('the_content', 'wpautop', $priority);
		add_filter('the_content', '_restore_wpautop_hook', $priority + 1);
	}
	return $output;
}
function pgc_sgb_shortcode_album_render($atts, $content = null)
{
	global $pgc_sgb_skins_list, $pgc_sgb_skins_presets;
	if (!is_array($atts) && !isset($atts['id'])) {
		return '';
	}
	$galleryAtr = array();
	$galleryAtr['skin'] = 'albums';
	$galleryAtr['useGlobalSettings'] = true;

	$albumPreset = get_option('pgc_sgb_album_shc_preset');
	if (isset($albumPreset) && count(explode("~", $albumPreset)) === 2) {
		$skinName = explode("~", $albumPreset)[0];
		if (isset($pgc_sgb_skins_list['pgc_sgb_' . $skinName])) {
			$galleryAtr['skin'] = $skinName;
		}
		if (isset($pgc_sgb_skins_presets['pgc_sgb_' . $skinName])) {
			$blockPresets = (array)$pgc_sgb_skins_presets['pgc_sgb_' . $skinName];
			$presetName = explode("~", $albumPreset)[1];
			if (isset($blockPresets[$presetName])) {
				$galleryAtr['presetName'] = $presetName;
				$galleryAtr['useGlobalSettings'] = false;
			}
		}
	}
	$galleryAtr['taxonomyId'] = $atts['id'];
	$galleryAtr['galleryId'] = 'album_' . $atts['id'];
	return pgc_sgb_render_albums_blocks_callback($galleryAtr, null);
}
/** SimpLy Galleries Block */
function pgc_sgb_get_galleries($termId, $orderby, $order)
{
	$data = [];
	$q_args = [
		'post_type'      => PGC_SGB_POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby' => $orderby,
		'order' => $order,
		'tax_query'      => array(
			array(
				'taxonomy' => PGC_SGB_TAXONOMY,
				'terms' => $termId,
				'include_children' => false
			)
		)
	];
	$my_query = new WP_Query($q_args);
	if ($my_query->have_posts()) {
		$postslist = 	$my_query->posts;
		foreach ($postslist as $post) {
			$postData = array();
			$postData['title'] = $post->post_title !== '' ? $post->post_title : $post->ID;
			$postData['ID'] = $post->ID;
			$postData['id'] = $post->ID;
			$postData['modified'] = $post->post_modified;
			$postData['date'] = $post->post_date;
			$postData['postLink'] = get_post_permalink($post->ID);
			$postData['type'] = $post->post_type;
			$postData['slug'] = $post->post_name;
			if (has_post_thumbnail($post)) {
				$postData['thumbURL'] = get_the_post_thumbnail_url($post, 'thumbnail');
				$thumbId = get_post_thumbnail_id($post);
				if ($thumbId !== false) {
					$thumbPost = get_post($thumbId);
					$thumbData = pgc_sgb_prepare_attachment_post_for_sgb($thumbPost);
					$postData['thumb'] = isset($thumbData['sizes']) ? $thumbData['sizes'] : null;
				}
			} else {
				$postData['thumbURL'] = PGC_SGB_URL . 'assets/coverAlbum-400x400.png';
			}
			array_push($data, $postData);
		}
	}
	wp_reset_query();
	return $data;
}
function pgc_sgb_amp_cover($item)
{
	$captionWrap = isset($item['title'])
		? '<div class="sgb-item-caption"><em>' . $item['title'] . '</em></div>'
		: '';
	$itemElemant = '<div class="sgb-item" title="'
		. esc_attr($item['title']) . '"><a href="'
		. esc_url($item['postLink']) . '" target="_blank"><img alt="'
		. esc_attr(isset($item['title']) ? $item['title'] : '')
		. '" width="250'
		. '" height="250'
		//. '" data-lazy-src="" class="skip-lazy no-lazyload noLazy" '
		. '" loading="lazy" '
		. 'src="' . $item['thumbURL'] . '"/></a>' . $captionWrap . '</div>';
	return $itemElemant;
}
function pgc_sgb_noscript_covers($items)
{
	if (!$items) return '';
	$noscript = '';
	foreach ($items as $item) {
		$noscript = $noscript . pgc_sgb_amp_cover($item);
	}
	return wp_kses_post($noscript);
}
function pgc_sgb_render_albums_blocks_callback($atr, $content)
{
	global $pgc_sgb_skins_presets;
	wp_enqueue_style(PGC_SGB_SLUG . '-frontend');
	wp_enqueue_script(PGC_SGB_SLUG . '-script');
	if (
		!isset($atr['galleryId'])
		|| !isset($atr['skin'])
	) {
		if (isset($content)) return $content;
		return '';
	}
	$skinType = $atr['skin'];
	$termId = $atr['taxonomyId'];
	$orderby = isset($atr['orderBy']) ? $atr['orderBy'] : 'ID';
	$order = isset($atr['order']) ? $atr['order'] : 'ASC';
	$galleries = array();
	if (isset($atr['useGlobalSettings']) && $atr['useGlobalSettings']) {
		/** Depreciated 2.3.5 */
		$skinGlobalOptions = (array)$pgc_sgb_skins_presets['pgc_sgb_' . $skinType];
		if (isset($skinGlobalOptions) && $skinGlobalOptions) {
			if (isset($skinGlobalOptions['Default'])) {
				$atr = array_merge($atr, (array)$skinGlobalOptions['Default']);
			} else {
				$atr = array_merge($atr, (array)$skinGlobalOptions);
			}
			$orderby = isset($atr['orderBy']) ? $atr['orderBy'] : 'ID';
			$order = isset($atr['order']) ? $atr['order'] : 'ASC';
		}
	} else if (array_key_exists('presetName', $atr) && $atr['presetName'] !== 'none') {
		$atrFromPreset = array();
		$presetName =  $atr['presetName'];
		$skinGlobalOptions = (array)$pgc_sgb_skins_presets['pgc_sgb_' . $skinType];
		if (array_key_exists($presetName, $skinGlobalOptions)) {
			$atrFromPreset = array_merge($atrFromPreset, (array)$skinGlobalOptions[$presetName]);
		} else {
			$atrFromPreset = array_merge($atrFromPreset, (array)$skinGlobalOptions['Default']);
		}
		$orderby = isset($atrFromPreset['orderBy']) ? $atrFromPreset['orderBy'] : 'ID';
		$order = isset($atrFromPreset['order']) ? $atrFromPreset['order'] : 'ASC';
		if (isset($galleries)) {
			$atrFromPreset['galleries'] = $galleries;
		}
	} else {
		$orderby = isset($atr['orderBy']) ? $atr['orderBy'] : 'ID';
		$order = isset($atr['order']) ? $atr['order'] : 'ASC';
	}
	$galleries = pgc_sgb_get_galleries($termId, $orderby, $order);
	if (isset($galleries) && count($galleries) !== 0) {
		if (isset($atrFromPreset)) {
			$atrFromPreset['galleries'] = $galleries;
		} else {
			$atr['galleries'] = $galleries;
		}
	} else {
		return '<div class="pgc-sgb-cb" data-gallery-id="'
			. esc_attr($atr['galleryId']) . '">'
			. esc_html__('SimpLy Album EMPTY', 'simply-gallery-block') . '</div>';
	}
	$galleryData = isset($atrFromPreset) ? json_encode($atrFromPreset) : json_encode($atr);
	$align = '';
	if (isset($atr['align'])) {
		$align = $align . 'align' . $atr['align'];
	}
	$className = PGC_SGB_BLOCK_PREF . $skinType . ' ' . $align;

	if (isset($atr['className'])) {
		$className = $className . ' ' . $atr['className'];
	}
	$noscript = '<div class="simply-gallery-amp pgc-sgb-album ' . esc_attr($align) . '" style="display: none;"><div class="sgb-gallery">'
		. pgc_sgb_noscript_covers($galleries)
		. '</div></div>';
	$preloaderColor = isset($galleryDataArr['galleryPreloaderColor']) ? $galleryDataArr['galleryPreloaderColor'] : '#d4d4d4';
	$preloder = '<div class="sgb-preloader" id="pr_' . $atr['galleryId'] . '">
	<div class="sgb-square" style="background:' . $preloaderColor . '"></div>
	<div class="sgb-square" style="background:' . $preloaderColor . '"></div>
	<div class="sgb-square" style="background:' . $preloaderColor . '"></div>
	<div class="sgb-square" style="background:' . $preloaderColor . '"></div></div>';
	$html = '<div class="pgc-sgb-cb ' . esc_attr($className)	. '" data-gallery-id="' . $atr['galleryId'] . '">'
		. $preloder . $noscript
		. '<script type="application/json" class="sgb-data">' . wp_kses_post($galleryData) . '</script>'
		. '<script type="text/javascript">(function(){if(window.PGC_SGB && window.PGC_SGB.searcher){window.PGC_SGB.searcher.initBlocks()}})()</script>'
		. '</div>';
	return $html;
}
function pgc_sgb_render_post_blocks_callback($atr, $content)
{
	if (isset($atr['galleryId'])) {
		$shortcode = '[' . PGC_SGB_POST_TYPE . ' id="' . sanitize_text_field($atr['galleryId']) . '"]';
		return do_shortcode($shortcode);
	}
	return '<div>SIMPLY GALLERY NOT AVAILABLE</div>';
}
function pgc_sgb_get_albums_list()
{
	$categories = get_categories(
		[
			'taxonomy'      => PGC_SGB_TAXONOMY,
			'hide_empty'    => 1,
			'orderby' => 'name',
			'order' => 'ASC'
		]
	);
	$data     = [];
	foreach ($categories as $cat) {
		$catData = array();
		$catData['term_name'] = $cat->name;
		$catData['term_id'] = $cat->term_id;
		$catData['count'] = $cat->count;
		$catData['description'] = $cat->category_description;
		array_push($data, $catData);
	}
	return $data;
}
function pgc_sgb_register_posts_block()
{
	wp_register_style(
		PGC_SGB_SLUG . '-post-blocks-style',
		PGC_SGB_URL . 'blocks/dist/blocks/blocks.build.style.css',
		array('code-editor', 'dashicons'),
		PGC_SGB_VERSION
	);
	wp_register_script(
		PGC_SGB_SLUG . '-post-blocks-script',
		PGC_SGB_URL . 'blocks/dist/blocks/blocks.build.js',
		array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-block-editor', 'wplink', 'wp-data', 'media', 'media-grid', 'backbone', 'code-editor', 'csslint', PGC_SGB_SLUG . '-script'),
		PGC_SGB_VERSION,
		false
	);
	$globalJS = array(
		'albums' => pgc_sgb_get_albums_list()
	);
	wp_localize_script(
		PGC_SGB_SLUG . '-post-blocks-script',
		'PGC_SGB_ALBUMS_ADMIN',
		$globalJS
	);
	register_block_type(
		'pgcsimplygalleryblock/galleries',
		array(
			'title' 				=> 'Saved SimlpLy Gallery',
			'style'         => PGC_SGB_SLUG . '-frontend',
			'editor_script' => PGC_SGB_SLUG . '-post-blocks-script',
			'editor_style'  => PGC_SGB_SLUG . '-post-blocks-style',
			'render_callback' => 'pgc_sgb_render_post_blocks_callback'
		)
	);
	/** Albums Start */
	wp_register_style(
		PGC_SGB_SLUG . '-albums',
		PGC_SGB_URL . 'blocks/skins/pgc_sgb_albums.style.css',
		array(PGC_SGB_SLUG . '-post-blocks-style'),
		PGC_SGB_VERSION
	);
	register_block_type(
		'pgcsimplygalleryblock/albums',
		array(
			'title' 				=> 'Albums',
			'style'         => PGC_SGB_SLUG . '-frontend',
			'editor_script' => PGC_SGB_SLUG . '-post-blocks-script',
			'editor_style'  => PGC_SGB_SLUG . '-albums',
			'render_callback' => 'pgc_sgb_render_albums_blocks_callback'
		)
	);
	/** AlbumNavigator */
	wp_register_style(
		PGC_SGB_SLUG . '-albumnavigator',
		PGC_SGB_URL . 'blocks/skins/pgc_sgb_albumnavigator.style.css',
		array(PGC_SGB_SLUG . '-post-blocks-style'),
		PGC_SGB_VERSION
	);
	register_block_type(
		'pgcsimplygalleryblock/albumnavigator',
		array(
			'title' 				=> 'Album Navigator',
			'style'         => PGC_SGB_SLUG . '-frontend',
			'editor_script' => PGC_SGB_SLUG . '-post-blocks-script',
			'editor_style'  => PGC_SGB_SLUG . '-albumnavigator',
			'render_callback' => 'pgc_sgb_render_albums_blocks_callback'
		)
	);
	/** Main Blocks Translatrion */
	if (function_exists('wp_set_script_translations')) {
		wp_set_script_translations(PGC_SGB_SLUG . '-post-blocks-script', 'simply-gallery-block', PGC_SGB_URL . 'languages');
	}
}
/** Plug Editor And Meta Box Render */
function pgc_sgb_post_enqueue_scripts()
{
	global $pgc_sgb_skins_list;
	$screen = get_current_screen();
	if (PGC_SGB_POST_TYPE === $screen->post_type) {
		if ('post' === $screen->base) {
			wp_register_style(
				PGC_SGB_SLUG . '-post-edit-style',
				PGC_SGB_URL . 'blocks/dist/post.editor.build.style.css',
				array('wp-components'),
				PGC_SGB_VERSION
			);
			wp_enqueue_style(PGC_SGB_SLUG . '-post-edit-style');

			/** Parser */
			wp_register_script(
				PGC_SGB_SLUG . '-post-editor-script',
				PGC_SGB_URL . 'blocks/dist/post.editor.build.js',
				array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-components', 'wp-data'),
				PGC_SGB_VERSION,
				true
			);
			wp_enqueue_script(PGC_SGB_SLUG . '-post-editor-script');
			$globalJS = array(
				// 'assets' => PGC_SGB_URL.'assets/',
				// 'ajaxurl'   => admin_url('admin-ajax.php'),
				// 'nonce' => wp_create_nonce('pgc-sgb-nonce'),
				'postType' => PGC_SGB_POST_TYPE,
				'skinsList' => $pgc_sgb_skins_list,
			);
			wp_localize_script(
				PGC_SGB_SLUG . '-post-editor-script',
				'PGC_SGB_POST',
				$globalJS
			);
			if (function_exists('wp_set_script_translations')) {
				wp_set_script_translations(PGC_SGB_SLUG . '-post-editor-script', 'simply-gallery-block', PGC_SGB_URL . 'languages');
			}
		} else if ('edit' === $screen->base || 'edit-tags' === $screen->base) {
			wp_enqueue_style(
				PGC_SGB_SLUG . '-post-editor-halper-style',
				PGC_SGB_URL . 'blocks/dist/post.editor.helper.build.style.css',
				array(),
				PGC_SGB_VERSION
			);
			wp_enqueue_script(
				PGC_SGB_SLUG . '-post-editor-halper-script',
				PGC_SGB_URL . 'blocks/dist/post.editor.helper.build.js',
				array(),
				PGC_SGB_VERSION,
				true
			);
		}
	}
}
function pgc_sgb_register_post_type()
{
	$default_base = 'pgc_simply_gallery';
	$curren_galleries_base = get_option('pgc_sgb_galleries_base');
	$curren_galleries_base = $curren_galleries_base ? $curren_galleries_base : $default_base;

	$default_archive_base = 'simply_galleries';
	$curren_archive_base = get_option('pgc_sgb_archive_galleries_base');
	$curren_archive_galleries_base = $curren_archive_base ? $curren_archive_base : $default_archive_base;

	$tax_labels = array(
		'name'              => __('SimpLy Albums', 'simply-gallery-block'),
		'singular_name'     => __('Album', 'simply-gallery-block'),
		'search_items'      => __('Search Albums', 'simply-gallery-block'),
		'all_items'         => __('All SimpLy Albums', 'simply-gallery-block'),
		'view_item '        => __('View SunoLy Album', 'simply-gallery-block'),
		'parent_item'       => __('Parent Album', 'simply-gallery-block'),
		'parent_item_colon' => __('Parent Album:', 'simply-gallery-block'),
		'edit_item'         => __('Edit Album', 'simply-gallery-block'),
		'update_item'       => __('Update Album', 'simply-gallery-block'),
		'add_new_item'      => __('Add New Album', 'simply-gallery-block'),
		'new_item_name'     => __('New Album Name', 'simply-gallery-block'),
		'menu_name'         => __('Albums', 'simply-gallery-block'),
	);
	register_taxonomy(
		PGC_SGB_TAXONOMY,
		PGC_SGB_POST_TYPE,
		array(
			'label'             => $tax_labels['name'],
			'singular_name'     => $tax_labels['name'],
			'labels'            => $tax_labels,
			'hierarchical'          => true,
			'public'                => true,
			'publicly_queryable'    => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'show_in_rest'          => true,
			'show_tagcloud'         => false,
			'show_in_quick_edit'    => true,
			'show_admin_column'     => true,
			'rewrite'               => false,
			'capability_type'     	=> PGC_SGB_POST_TYPE,
		)
	);
	register_post_type(
		PGC_SGB_POST_TYPE,
		array(
			'labels'              => array(
				'name'                  => _x('SimpLy Galleries', 'Post Type General Name', 'imply-gallery-block'),
				'singular_name'         => _x('SimpLy Gallery', 'simply-gallery-block'),
				'menu_name'             => __('SimpLy Gallery', 'simply-gallery-block'),
				'all_items'         		=> __('Galleries', 'simply-gallery-block'),
				'add_new'               => __('Add New', 'simply-gallery-block'),
				'add_new_item'          => __('Add New SimpLy Gallery', 'simply-gallery-block'),
				'edit_item'             => __('Edit Gallery', 'simply-gallery-block'),
				'view_item'             => __('View SimpLy Gallery', 'simply-gallery-block'),
				'search_items'          => __('Search SimpLy Gallery', 'simply-gallery-block'),
				'not_found'             => __('No Galleries Found', 'simply-gallery-block'),
				'not_found_in_trash'    => __('No Galleries Found in Trash', 'simply-gallery-block'),
				'parent_item_colon'     => __('Parent Gallery', 'simply-gallery-block'),
				'filter_items_list'     => __('Filter Galleries list', 'simply-gallery-block'),
				'items_list_navigation' => __('Galleries list navigation', 'simply-gallery-block'),
				'items_list'            => __('Galleries list', 'simply-gallery-block')
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'with_front'          => true,
			'query_var'           => true,
			//'hierarchical'        => true,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'can_export'          => true,
			'capability_type'     => 'page',
			'show_in_menu'        => true,
			'menu_position'       => 11,
			'menu_icon'						=> 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjxzdmcgIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDI0IDI0OyIgdmVyc2lvbj0iMS4xIiB4bWw6c3BhY2U9InByZXNlcnZlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB3aWR0aD0iMjJweCIgaGVpZ2h0PSIyMnB4IiB2aWV3Qm94PSIwIDAgMjk4LjczIDI5OC43MyIgZmlsbD0iIzAwODViYSIgZmlsbC1ydWxlPSJub256ZXJvIj48ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgZD0iTTI2NC45NTksOS4zNUgzMy43ODdDMTUuMTUzLDkuMzUsMCwyNC40OTgsMCw0My4xNTR2MjEyLjQ2MWMwLDE4LjYzNCwxNS4xNTMsMzMuNzY2LDMzLjc4NywzMy43NjYgICBoMjMxLjE3MWMxOC42MzQsMCwzMy43NzEtMTUuMTMyLDMzLjc3MS0zMy43NjZWNDMuMTU0QzI5OC43MywyNC40OTgsMjgzLjU5Myw5LjM1LDI2NC45NTksOS4zNXogTTE5My4xNzQsNTkuNjIzICAgYzE4LjAyLDAsMzIuNjM0LDE0LjYxNSwzMi42MzQsMzIuNjM0cy0xNC42MTUsMzIuNjM0LTMyLjYzNCwzMi42MzRjLTE4LjAyNSwwLTMyLjYzNC0xNC42MTUtMzIuNjM0LTMyLjYzNCAgIFMxNzUuMTQ5LDU5LjYyMywxOTMuMTc0LDU5LjYyM3ogTTI1NC4zNjMsMjU4LjE0OUgxNDkuMzYySDQ5LjAzOWMtOS4wMTMsMC0xMy4wMjctNi41MjEtOC45NjQtMTQuNTY2bDU2LjAwNi0xMTAuOTMgICBjNC4wNTgtOC4wNDQsMTEuNzkyLTguNzYyLDE3LjI2OS0xLjYwNWw1Ni4zMTYsNzMuNTk2YzUuNDc3LDcuMTU4LDE1LjA1LDcuNzY3LDIxLjM4NiwxLjM1NGwxMy43NzctMTMuOTUxICAgYzYuMzMxLTYuNDEzLDE1LjY1OS01LjYxOSwyMC44MjYsMS43NjJsMzUuNjc1LDUwLjk1OUMyNjYuNDg3LDI1Mi4xNiwyNjMuMzc2LDI1OC4xNDksMjU0LjM2MywyNTguMTQ5eiI+PC9wYXRoPjwvZz48L3N2Zz4=',
			'show_in_rest'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'has_archive'        => $curren_archive_galleries_base,
			'rewrite'            => array(
				'slug'       => $curren_galleries_base,
				'with_front' => false,
			),
			'supports'            => array(
				'title',
				'thumbnail',
				'editor',
				'comments'
			),
			'taxonomies' => array(PGC_SGB_TAXONOMY),
		)
	);
	add_shortcode('pgc_simply_gallery', 'pgc_sgb_shortcode_render');
	add_shortcode('pgc_simply_album', 'pgc_sgb_shortcode_album_render');

	pgc_sgb_register_posts_block();
}
function pgc_sgb_filter_custom_post_by_taxonomies($post_type)
{
	if ($post_type !== PGC_SGB_POST_TYPE) {
		return;
	}
	$taxonomies = array(PGC_SGB_TAXONOMY);
	foreach ($taxonomies as $taxonomy_slug) {
		$taxonomy_obj  = get_taxonomy($taxonomy_slug);
		$taxonomy_name = $taxonomy_obj->labels->name;

		$terms = get_terms($taxonomy_slug);

		echo '<select name="' . esc_attr($taxonomy_slug) . '" id="' . esc_attr($taxonomy_slug) . '" class="postform">';
		echo '<option value="">' . sprintf(esc_html__('Show All', 'simply-gallery-block'), esc_html($taxonomy_name)) . '</option>';
		foreach ($terms as $term) {
			printf(
				'<option value="%1$s" %2$s>%3$s (%4$s)</option>',
				esc_attr($term->slug),
				isset($_GET[$taxonomy_slug]) && $_GET[$taxonomy_slug] === $term->slug ? ' selected="selected"' : '',
				esc_html($term->name),
				esc_html($term->count)
			);
		}
		echo '</select>';
	}
}
/** Init permalink settings */
function pgc_sgb_galleries_permalink_settings()
{
	$default_base = 'pgc_simply_gallery';
	$curren_galleries_base = get_option('pgc_sgb_galleries_base');
	$curren_galleries_base = $curren_galleries_base ? $curren_galleries_base : $default_base;
	$current_base = $curren_galleries_base;

	$default_archive_base = 'simply_galleries';
	$curren_archive_base = get_option('pgc_sgb_archive_galleries_base');
	$curren_archive_galleries_base = $curren_archive_base ? $curren_archive_base : $default_archive_base;

	echo wp_kses_post(wpautop(sprintf(
		__('If you like, you may enter custom structures for your SimpLy gallery URLs here. For example, using <code>pgc_simply_gallery</code> would make your gallery links like <code>%spgc_simply_gallery/simply-gallery/</code>. This setting affects gallery URLs only.', 'simply-gallery-block'),
		esc_url(home_url('/'))
	)));
?>
	<table class="form-table pgc-sgb-permalink-structure">
		<tbody>
			<tr>
				<td>
					<code class="default-example"><?php echo esc_html(home_url()); ?>/</code>
					<input name="pgc_sgb_permalink_structure" id="pgc_sgb_permalink_structure" type="text" value="<?php echo esc_attr($current_base ? $current_base : $default_base); ?>" class="regular-text code">
					<code class="default-example">/simply-gallery/</code>
				</td>
			</tr>
			<tr>
				<td><?php echo wp_kses_post(wpautop(sprintf(
							__('If you like, you may enter custom Archive page slug for your SimpLy galleries. For example, using <code>simply_galleries</code> would make your galleries archive links like <code>%ssimply_galleries/</code>. This setting affects gallery URLs only.', 'simply-gallery-block'),
							esc_url(home_url('/'))
						))); ?></td>
			</tr>
			<tr>
				<td>
					<code class="default-example"><?php echo esc_html(home_url()); ?>/</code>
					<input name="pgc_sgb_arvhive_permalink_structure" id="pgc_sgb_arvhive_permalink_structure" type="text" value="<?php echo esc_attr($curren_archive_galleries_base ? $curren_archive_galleries_base : $default_archive_base); ?>" class="regular-text code">
					<code class="default-example">/</code>
				</td>
			</tr>
		</tbody>
	</table>
	<?php wp_nonce_field('pgc-sgb-permalink', 'pgc-sgb-permalinks-nonce'); ?>
<?php
}
function pgc_sgb_clean($var)
{
	if (is_array($var)) {
		return array_map('pgc_sgb_clean', $var);
	} else {
		return is_scalar($var) ? sanitize_text_field($var) : $var;
	}
}
function pgc_sgb_sanitize_permalink($value)
{
	global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column($wpdb->options, 'option_value', $value);

	if (is_wp_error($value)) {
		$value = '';
	}

	$value = esc_url_raw(trim($value));
	$value = str_replace('http://', '', $value);
	return untrailingslashit($value);
}
function pgc_sgb_galleries_permalink_settings_save()
{
	if (!is_admin()) {
		return;
	}
	if (
		isset($_POST['permalink_structure'], $_POST['pgc-sgb-permalinks-nonce'])
		&& wp_verify_nonce(wp_unslash($_POST['pgc-sgb-permalinks-nonce']), 'pgc-sgb-permalink')
	) {
		$gallery_base = 'pgc_simply_gallery';
		if (isset($_POST['pgc_sgb_permalink_structure'])) {
			$gallery_base = preg_replace('#/+#', '', '' . str_replace('#', '', trim(wp_unslash($_POST['pgc_sgb_permalink_structure']))));
		}
		$gallery_base_for_save = pgc_sgb_sanitize_permalink($gallery_base);
		update_option('pgc_sgb_galleries_base', $gallery_base_for_save);

		$archive_base = 'simply_galleries';
		if (isset($_POST['pgc_sgb_arvhive_permalink_structure'])) {
			$archive_base = preg_replace('#/+#', '', '' . str_replace('#', '', trim(wp_unslash($_POST['pgc_sgb_arvhive_permalink_structure']))));
		}
		$archive_base_for_save = pgc_sgb_sanitize_permalink($archive_base);
		update_option('pgc_sgb_archive_galleries_base', $archive_base_for_save);
	}
}
function pgc_sgb_galleries_permalink_settings_init()
{
	add_settings_section(
		'pgc-sgb-gallery-permalink',
		esc_html__('SimpLy Gallery permalinks', 'simply-gallery-block'),
		'pgc_sgb_galleries_permalink_settings',
		'permalink'
	);
}
/** SimpLy Items List  */
function pgc_sgb_prepare_item_for_js($img_att_data)
{
	$response = $img_att_data;
	if (isset($response['alt'])) {
		$response['alt'] = esc_attr($response['alt']);
	}
	if (isset($response['title'])) {
		$response['title'] = wp_kses_post($response['title']);
	}
	if (isset($response['caption'])) {
		$response['caption'] = wp_kses_post($response['caption']);
	}
	if (isset($response['description'])) {
		$response['description'] = wp_kses_post($response['description']);
	}
	return $response;
}
function pgc_sgb_get_simply_album(WP_REST_Request $request)
{
	$termId = $request['id'];
	$orderby = $request['orderby'];
	$order = $request['order'];
	return pgc_sgb_get_galleries($termId, $orderby, $order);
}
function pgc_sgb_get_gallery_atr(WP_REST_Request $request)
{
	$postId = $request['id'];
	if (
		get_post_field('post_type', $postId, 'raw') === PGC_SGB_POST_TYPE
		&& get_post_status($postId) !== 'publish'
	) return null;
	$postData = get_post_field('post_content', $postId, 'raw');
	if ($postData === '' || is_wp_error($postData)) return null;
	$blocks = parse_blocks($postData);
	$output = [];
	foreach ($blocks as $block) {
		if (
			isset($block['blockName'])
			&& strpos($block['blockName'], 'pgcsimplygalleryblock') === 0
			&& isset($block['attrs'])
		) {
			$attrs = $block['attrs'];
			$gallery = array();
			if (isset($attrs['images'])) {
				$gallery['images'] = array_map('pgc_sgb_prepare_item_for_js', $attrs['images']);
			}
			if (isset($attrs['itemsMetaDataCollection'])) {
				$gallery['itemsMetaDataCollection'] = $attrs['itemsMetaDataCollection'];
			}
			if (isset($attrs['tagCloudAll'])) {
				$gallery['tagCloudAll'] = $attrs['tagCloudAll'];
			}
			if (isset($attrs['tagsListCustomMode'])) {
				$gallery['tagsListCustomMode'] = $attrs['tagsListCustomMode'];
			}
			if (isset($attrs['galleryTagsList'])) {
				$gallery['galleryTagsList'] = $attrs['galleryTagsList'];
			}
			if (isset($attrs['orderBy'])) {
				$gallery['orderBy'] = $attrs['orderBy'];
			}
			if (function_exists('pgc_sgb_get_query_data') && isset($attrs['galleryQuery'])) {
				$gallery['galleryQuery'] = $attrs['galleryQuery'];
				$galleryQueryData = pgc_sgb_get_query_data($attrs['galleryQuery']);
				if (isset($galleryQueryData)) $gallery = array_merge($gallery, $galleryQueryData);
			}
			if (function_exists('pgc_sgb_get_yt_query_data') && isset($attrs['galleryYTQuery'])) {
				$gallery['galleryYTQuery'] = $attrs['galleryYTQuery'];
				$galleryQueryData = pgc_sgb_get_yt_query_data($attrs['galleryYTQuery']);
				if (isset($galleryQueryData)) $gallery = array_merge($gallery, $galleryQueryData);
			}
			if (function_exists('pgc_sgb_get_vm_query_data')  && isset($attrs['galleryVMQuery'])) {
				$galleryQueryData = pgc_sgb_get_vm_query_data($attrs['galleryVMQuery']);
				if (isset($galleryQueryData)) $gallery = array_merge($gallery, $galleryQueryData);
			}
			if (function_exists('pgc_sgb_get_woo_query_data')  && isset($attrs['galleryWooQuery'])) {
				$gallery['galleryWooQuery'] = $attrs['galleryWooQuery'];
				$galleryQueryData = pgc_sgb_get_woo_query_data($attrs['galleryWooQuery']);
				if (isset($galleryQueryData)) $gallery = array_merge($gallery, $galleryQueryData);
			}
			array_push($output, $gallery);
		}
	}
	$priority = has_filter('the_content', 'wpautop');
	if (false !== $priority && doing_filter('the_content') && has_blocks($content)) {
		remove_filter('the_content', 'wpautop', $priority);
		add_filter('the_content', '_restore_wpautop_hook', $priority + 1);
	}
	return $output;
}
function pgc_sgb_register_rest_route()
{
	register_rest_route(PGC_SGB_POST_TYPE . '/v2', '/gallery/(?P<id>\d+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'pgc_sgb_get_gallery_atr',
		'permission_callback' => '__return_true',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param);
				},
			),
		)
	));
	register_rest_route(PGC_SGB_POST_TYPE . '/v2', '/album/(?P<id>[\d]+)/(?P<orderby>[\w]+)/(?P<order>[\w]+)', array(
		'methods'  => WP_REST_Server::READABLE,
		'callback' => 'pgc_sgb_get_simply_album',
		'permission_callback' => '__return_true',
		'args' => array(
			'id' => array(
				'validate_callback' => function ($param, $request, $key) {
					return is_numeric($param);
				},
			),
			'orderby' => array(
				'validate_callback' => function ($param, $request, $key) {
					return ($param === 'ID'
						|| $param === 'title'
						|| $param === 'modified'
						|| $param === 'date');
				},
			),
			'order' => array(
				'validate_callback' => function ($param, $request, $key) {
					return ($param === 'ASC' || $param === 'DESC');
				},
			)
		)
	));
}
function pgc_sgb_is_classic_editor_plugin_active()
{
	if (!function_exists('is_plugin_active')) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	if (is_plugin_active('classic-editor/classic-editor.php')) {
		return true;
	}
	return false;
}
function pgc_sgb_simply_directory_notice()
{
	$current_screen = get_current_screen();
	if (
		isset($current_screen->id)
		&& $current_screen->id === 'edit-' . PGC_SGB_POST_TYPE
		&& pgc_sgb_is_classic_editor_plugin_active() === true
	) {
		echo '<div class="notice notice-error ">'
			. '<div class="pgc-notic-text" style="font-size: larger; padding: 20px;	font-weight: 600;">'
			. esc_html__('You are using the legacy "Classic Editor" page builder. This makes visiting this page meaningless. Time to step forward and try the Block Editor!', 'simply-gallery-block')
			. '</div></div>';
	}
	if (
		!isset($current_screen->id)
		|| $current_screen->id !== 'edit-' . PGC_SGB_POST_TYPE
		|| $current_screen->taxonomy !== ''
		|| $current_screen->action === 'add'
	) {
		return;
	}
	echo '<div class="notice notice-info pgc-sgb-notic">'
		. '<div class="pgc-close-button"><span class="dashicons dashicons-no-alt"></span></div>'
		. '<span class="dashicons dashicons-welcome-learn-more pgc-notic-icon"></span>'
		. '<div class="pgc-notic-text">' . esc_html__('The SimpLy Gallery Directory allows you to intelligently structure your media content. It will also allow you to easily manage the content of galleries that need frequent updates. Galleries from this directory can be easily used as WordPress Widgets, as well as added to third-party Page Builders (such as Elementor and others that support the Shortcodes system).', 'simply-gallery-block')
		. '</div></div>';
}
function pgc_sgb_albums_table_columns($columns)
{
	unset($columns['slug']);
	unset($columns['description']);
	$pgc_sgb_columns = [
		'cb'        => '<input type="checkbox" />',
		'name'     => __('Name', 'simply-gallery-block'),
		'shortcode' => __('Shortcode', 'simply-gallery-block'),
	];
	return array_merge($pgc_sgb_columns, $columns);
}
function pgc_sgb_custom_albums_table_columns_data($out, $column_name, $tax_id)
{
	$album = get_term($tax_id, PGC_SGB_TAXONOMY);
	switch ($column_name) {
		case 'shortcode':
			echo '<code class="pgc-sgb-onclick-selection" role="button" tabIndex="0" aria-hidden="true">';
			echo '[pgc_simply_album id="' . esc_html($album->term_id) . '"]';
			echo '</code>';
			break;
		default:
			break;
	}
	return $out;
}
function pgc_sgb_table_columns($columns)
{
	$pgc_sgb_columns = [
		'cb'        => '<input type="checkbox" />',
		'image'     => __('Cover', 'simply-gallery-block'),
		'title'     => __('Title', 'simply-gallery-block'),
		'shortcode' => __('Shortcode', 'simply-gallery-block'),
		'date'      => __('Date', 'simply-gallery-block'),
	];

	$pgc_sgb_columns = apply_filters('pgc_sgb_table_columns', $pgc_sgb_columns, $columns, PGC_SGB_POST_TYPE);
	return array_merge($pgc_sgb_columns, $columns);
}
function pgc_sgb_custom_columns_data($column, $post_id)
{
	$post = get_post($post_id);
	switch ($column) {
		case 'image':
			// Get Gallery Images.
			if (has_post_thumbnail($post)) {
				$src = get_the_post_thumbnail_url($post, 'thumbnail');
			} else {
				$src = PGC_SGB_URL . 'assets/icon-75x75.png';
			}
			// Display the cover.
			echo '<img src="' . esc_url($src) . '" width="75" />'; // @codingStandardsIgnoreLine
			break;
		case 'shortcode':
			echo '<code class="pgc-sgb-onclick-selection" role="button" tabIndex="0" aria-hidden="true">';
			echo '[pgc_simply_gallery id="' . get_the_ID() . '"]';
			echo '</code>';
			break;
	}
}
/** Meta Box */
function pgc_sgb_adding_custom_meta_boxes($post)
{
	add_meta_box('pgc-sgb-sc-meta-box', 'SimpLy Gallery Shortcode', 'pgc_sgb_render_meta_box', PGC_SGB_POST_TYPE, 'side', 'high');
}
function pgc_sgb_render_meta_box($post)
{
	echo '<div id="' . PGC_SGB_SLUG . '-post-editor"></div>';
}
/** 5.8 */
function pgc_sgb_allow_my_block_types($allowed_block_types_all, $block_editor_context)
{
	$post = $block_editor_context->post;
	if (!isset($post) || $post === '') {
		if (isset($block_editor_context->post_type)) {
			$post = $block_editor_context;
		}
	}
	if (!isset($post)) return null;
	global $pgc_sgb_skins_list;
	$exc = array('albums', 'albumnavigator');
	$allowed_blocks = array();
	foreach ($pgc_sgb_skins_list as $key => $value) {
		$skinName = substr($key, 8);
		if (isset($skinName) && in_array($skinName, $exc) === false) {
			$skinName = 'pgcsimplygalleryblock/' . $skinName;
			array_push($allowed_blocks, $skinName);
		}
	}
	if (in_array($post->post_type, [PGC_SGB_POST_TYPE])) {
		return $allowed_blocks;
	}
	return $allowed_block_types_all;
}
/** Menu */
function pgc_sgb_add_galleries_admin_pages()
{
	/** Remove Add New */
	remove_submenu_page('edit.php?post_type=' . PGC_SGB_POST_TYPE, 'post-new.php?post_type=' . PGC_SGB_POST_TYPE);
}
if (version_compare($GLOBALS['wp_version'], '5.8', '<')) {
	add_filter('allowed_block_types', 'pgc_sgb_allow_my_block_types', 10, 2);
} else {
	add_filter('allowed_block_types_all', 'pgc_sgb_allow_my_block_types', 10, 2);
}
add_filter('admin_notices', 'pgc_sgb_simply_directory_notice');
add_filter('manage_edit-' . PGC_SGB_POST_TYPE . '_columns', 'pgc_sgb_table_columns');
add_action('manage_' . PGC_SGB_POST_TYPE . '_posts_custom_column', 'pgc_sgb_custom_columns_data', 10, 2);
add_filter('manage_edit-' . PGC_SGB_TAXONOMY . '_columns', 'pgc_sgb_albums_table_columns');
add_action('manage_' . PGC_SGB_TAXONOMY . '_custom_column', 'pgc_sgb_custom_albums_table_columns_data', 10, 3);
add_action('add_meta_boxes_' . PGC_SGB_POST_TYPE, 'pgc_sgb_adding_custom_meta_boxes');
add_action('admin_menu', 'pgc_sgb_add_galleries_admin_pages', 99);
add_action('rest_api_init', 'pgc_sgb_register_rest_route');
add_action('init', 'pgc_sgb_register_post_type');
add_action('restrict_manage_posts', 'pgc_sgb_filter_custom_post_by_taxonomies', 10);
add_action('admin_enqueue_scripts', 'pgc_sgb_post_enqueue_scripts');
add_action('admin_init', 'pgc_sgb_galleries_permalink_settings_init');
add_action('admin_init', 'pgc_sgb_galleries_permalink_settings_save');
