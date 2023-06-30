<?php
if (!defined('ABSPATH')) {
	exit;
}

class SimpLy_Gallery_Elementor
{
	public function __construct()
	{
		add_action('elementor/widgets/widgets_registered', array($this, 'widgets_registered'));
	}
	public function widgets_registered()
	{
		require_once PGC_SGB_PATH . '/blocks/simply_posts_elementor_widget.php';

		\Elementor\Plugin::instance()->widgets_manager->register_widget_type(new SimpLy_Widgets_Elementor());
	}
}

new SimpLy_Gallery_Elementor();
