<?php
/**
 * Per-page title tag + meta description.
 *
 * Every content doc for this site specifies an exact <title> and meta
 * description per page ("Technical foundation" section). The homepage's
 * are fixed by the finalized homepage copy; every other page reads its
 * own values from the SEO meta box (inc/metaboxes.php), falling back to
 * WordPress defaults when a page hasn't set them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PINSDOWNLOAD_HOME_TITLE', 'Pinterest Video Downloader – Save Videos, Images, GIFs & Stories Free | PinsDownload' );
define( 'PINSDOWNLOAD_HOME_DESCRIPTION', 'Download Pinterest videos, images, and GIFs in HD for free with PinsDownload. No login, no watermark, no app needed.' );

add_filter(
	'pre_get_document_title',
	function ( $title ) {
		if ( is_front_page() ) {
			return PINSDOWNLOAD_HOME_TITLE;
		}
		if ( is_singular( 'page' ) ) {
			$custom = get_post_meta( get_the_ID(), '_pinsdownload_meta_title', true );
			if ( $custom ) {
				return $custom;
			}
		}
		return $title;
	}
);

add_action(
	'wp_head',
	function () {
		$description = '';

		if ( is_front_page() ) {
			$description = PINSDOWNLOAD_HOME_DESCRIPTION;
		} elseif ( is_singular( 'page' ) ) {
			$description = get_post_meta( get_the_ID(), '_pinsdownload_meta_description', true );
		}

		if ( $description ) {
			echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
		}
	},
	1
);
