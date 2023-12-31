<?php
/**
 * The admin functionality of the Elementor integration.
 *
 * @package    Joinchat
 */

/**
 * The admin functionality of the Elementor integration.
 *
 * @since      4.1.10
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Elementor_Admin {

	/**
	 * Initialize all hooks
	 *
	 * @since    4.1.10
	 * @param    Joinchat $joinchat       Joinchat object.
	 * @return   void
	 */
	public function init( $joinchat ) {

		$loader = $joinchat->get_loader();

		$loader->add_filter( 'joinchat_custom_post_types', $this, 'custom_post_types' );
		$loader->add_filter( 'joinchat_post_types_meta_box', $this, 'custom_post_types' );
	}

	/**
	 * Include Elementor Landing pages CPT
	 *
	 * @since    4.1.10
	 * @param    array $custom_post_types       current tab sections and fields.
	 * @return   array
	 */
	public function custom_post_types( $custom_post_types ) {

		if ( post_type_exists( 'e-landing-page' ) ) {
			$custom_post_types = array_merge( $custom_post_types, array( 'e-landing-page' ) );
		}

		return $custom_post_types;
	}

}
