<?php
/**
 * "PinsDownload Images" Customizer panel.
 *
 * Every fixed image slot on the homepage (and the works/doesn't table
 * shared with every Tool Landing Page) gets a native Media Library
 * picker here — Appearance -> Customize -> PinsDownload Images. Pick
 * an existing file or upload a new one; no code changes needed. Slots
 * left empty keep showing the dashed placeholder instead of breaking
 * or showing a broken image icon.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'pinsdownload_images',
			array(
				'title'       => __( 'PinsDownload Images', 'pinsdownload' ),
				'description' => __( 'Upload or pick a photo/screenshot for each spot below. Anything left empty keeps showing as a placeholder on the site, nothing breaks.', 'pinsdownload' ),
				'priority'    => 30,
			)
		);

		$slots = array(
			'pinsdownload_img_howto_video'     => __( 'How to Download a Video — screenshot', 'pinsdownload' ),
			'pinsdownload_img_howto_app'       => __( 'Downloading From the App — screenshot', 'pinsdownload' ),
			'pinsdownload_img_howto_computer'  => __( 'Downloading on a Computer — screenshot', 'pinsdownload' ),
			'pinsdownload_img_howto_iphone'    => __( 'iPhone Guide — screenshot', 'pinsdownload' ),
			'pinsdownload_img_howto_android'   => __( 'Android Guide — screenshot', 'pinsdownload' ),
			'pinsdownload_img_what_is'         => __( '"What Is a Pinterest Video Downloader" — illustration', 'pinsdownload' ),
			'pinsdownload_img_works_doesnt'    => __( 'Works/Doesn\'t Work table — screenshot (shown on homepage + every tool page)', 'pinsdownload' ),
			'pinsdownload_img_testimonial_1'   => __( 'Testimonial 1 (J. Ahmed) — avatar', 'pinsdownload' ),
			'pinsdownload_img_testimonial_2'   => __( 'Testimonial 2 (M. Khan) — avatar', 'pinsdownload' ),
			'pinsdownload_img_testimonial_3'   => __( 'Testimonial 3 (S. Rehman) — avatar', 'pinsdownload' ),
		);

		$priority = 10;
		foreach ( $slots as $setting_id => $label ) {
			$wp_customize->add_setting(
				$setting_id,
				array(
					'default'           => '',
					'sanitize_callback' => 'esc_url_raw',
					'transport'         => 'refresh',
				)
			);

			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					$setting_id,
					array(
						'label'    => $label,
						'section'  => 'pinsdownload_images',
						'priority' => $priority,
					)
				)
			);

			$priority += 10;
		}
	}
);
