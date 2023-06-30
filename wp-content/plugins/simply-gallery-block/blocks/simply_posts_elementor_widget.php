<?php

if (!defined('ABSPATH')) exit;

class SimpLy_Widgets_Elementor extends \Elementor\Widget_Base
{
	public function __construct($data = array(), $args = null)
	{
		parent::__construct($data, $args);

		if ($this->is_preview_mode()) {
			pgc_sgb_menager_script();
		}
	}
	public function is_preview_mode()
	{
		return \Elementor\Plugin::$instance->preview->is_preview_mode() || \Elementor\Plugin::$instance->editor->is_edit_mode();
	}
	public function get_name()
	{
		return 'simply-galleries';
	}
	public function get_title()
	{
		return 'SimpLy Gallery';
	}
	public function get_icon()
	{
		return 'eicon-gallery-grid';
	}
	public function get_categories()
	{
		return array('general');
	}
	public function get_keywords()
	{
		return array('simply', 'gallery', 'images', 'portfolio');
	}
	public function get_script_depends()
	{
		if ($this->is_preview_mode()) {
			return array(PGC_SGB_SLUG . '-script');
		}
		return array();
	}
	public function get_style_depends()
	{
		if ($this->is_preview_mode()) {
			return array(PGC_SGB_SLUG . '-frontend');
		}
		return array();
	}
	protected function _register_controls()
	{
		$all_galleries = get_posts(
			[
				'post_type'      => [PGC_SGB_POST_TYPE],
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			]
		);
		$galleries     = array();
		foreach ($all_galleries as $_gall) {
			$galleries[$_gall->ID] = $_gall->post_title !== '' ? $_gall->post_title : $_gall->ID;
		}
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __('General', 'simply-gallery-block'),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'saved_id',
			array(
				'label'   => esc_html__('Select Gallery', 'simply-gallery-block'),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => $galleries,
				'dynamic' => array(
					'active' => true,
				),
			)
		);

		$this->end_controls_section();
	}
	protected function render()
	{
		$settings = array_merge(
			array(
				'saved_id' => false,
				'class'    => '',
			),
			$this->get_settings()
		);
		if (!$settings['saved_id']) {
			return;
		}
		echo '<div>';
		echo do_shortcode('[' . PGC_SGB_POST_TYPE . ' id="' . esc_attr($settings['saved_id']) . '"]');
		echo '</div>';
	}
	/**
	 * Render shortcode widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function content_template()
	{
	}

	/**
	 * Render Plain Content
	 *
	 * @param array $instance instance data.
	 */
	public function render_plain_content($instance = array())
	{
	}
}
