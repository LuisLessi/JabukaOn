<?php

if (!defined('ABSPATH')) exit;

class SimpLy_Widgets extends WP_Widget
{
	function __construct()
	{
		$widget_name = 'SimpLy ' . __('Gallery', 'simply-gallery-block');
		$widget_ops = array(
			'description' => __('Place a SimpLy Gallery into a widgetized area.', 'simply-gallery-block'),
			//'customize_selective_refresh' => true,
		);
		parent::__construct('pgc_sgb_widget_gallery', $widget_name, $widget_ops);
	}
	public function widget($args, $instance)
	{
		$title = !empty($instance['title']) ? $instance['title'] : null;
		$title = apply_filters('widget_title', $title, $instance, $this->id_base);
		$galleryId  = empty($instance['galleryId']) ? '' : $instance['galleryId'];
		if ($galleryId === '') {
			return;
		}
		$shortcode = '[' . PGC_SGB_POST_TYPE . ' id="' . $galleryId . '"]';
		echo $args['before_widget'];
		if ($title) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		echo do_shortcode($shortcode);
		echo $args['after_widget'];
	}
	public function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['galleryId'] = (!empty($new_instance['galleryId'])) ? sanitize_text_field($new_instance['galleryId']) : '';
		return $instance;
	}
	public function form($instance)
	{
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'galleryId'  => '',
				'title'   => '',
			)
		);
		$all_galleries = get_posts(
			[
				'post_type'      => [PGC_SGB_POST_TYPE],
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			]
		);
		$galleries     = [];
		foreach ($all_galleries as $_gall) {
			$gallery = array();
			$gallery['title'] = $_gall->post_title !== '' ? $_gall->post_title : $_gall->ID;
			$gallery['ID'] = $_gall->ID;
			array_push($galleries, $gallery);
		}
?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title', 'simply-gallery-block'); ?></label>
			<input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($instance['title']); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr($this->get_field_id('galleryId')); ?>"><?php esc_html_e('Gallery', 'simply-gallery-block'); ?></label>
			<select class="widefat" id="<?php echo esc_attr($this->get_field_id('galleryId')); ?>" name="<?php echo esc_attr($this->get_field_name('galleryId')); ?>">
				<option value="" <?php selected('', $instance['galleryId']); ?>><?php esc_attr_e('Choose Gallery', 'simply-gallery-block'); ?></option>
				<?php
				if (!empty($galleries)) {
					foreach ($galleries as $gallery) {
						$value = $gallery['ID'];
				?>
						<option value="<?php echo esc_attr($value); ?>" <?php selected($value, $instance['galleryId']); ?>><?php echo esc_attr($gallery['title']); ?></option>
				<?php
					}
				}
				?>
			</select>
		</p>
<?php
	}
}

function pgc_sgb_register_widget()
{
	register_widget('SimpLy_Widgets');
}
add_action('widgets_init', 'pgc_sgb_register_widget');
