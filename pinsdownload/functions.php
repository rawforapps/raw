<?php
/**
 * PinsDownload theme bootstrap.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PINSDOWNLOAD_VERSION', '1.0.0' );
define( 'PINSDOWNLOAD_DIR', get_template_directory() );
define( 'PINSDOWNLOAD_URI', get_template_directory_uri() );

/**
 * Theme setup.
 */
function pinsdownload_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'script', 'style' ) );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo' );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'pinsdownload' ),
			'footer'  => __( 'Footer Menu', 'pinsdownload' ),
		)
	);
}
add_action( 'after_setup_theme', 'pinsdownload_setup' );

/**
 * Styles and scripts.
 */
function pinsdownload_assets() {
	wp_enqueue_style( 'pinsdownload-style', PINSDOWNLOAD_URI . '/assets/css/style.css', array(), PINSDOWNLOAD_VERSION );

	wp_enqueue_script( 'pinsdownload-sw-register', PINSDOWNLOAD_URI . '/assets/js/sw-register.js', array(), PINSDOWNLOAD_VERSION, true );

	// Site-wide visual/interaction layer: scroll-reveal, FAQ accordion,
	// sticky header. Purely presentational, no content or tool logic.
	wp_enqueue_script( 'pinsdownload-ui', PINSDOWNLOAD_URI . '/assets/js/ui.js', array(), PINSDOWNLOAD_VERSION, true );

	// JSZip is only needed on pages that render the tool (bulk ZIP download).
	if ( pinsdownload_page_has_tool() ) {
		wp_enqueue_script( 'jszip', PINSDOWNLOAD_URI . '/assets/js/vendor/jszip.min.js', array(), '3.10.1', true );
		wp_enqueue_script( 'pinsdownload-tool', PINSDOWNLOAD_URI . '/assets/js/tool.js', array( 'jszip' ), PINSDOWNLOAD_VERSION, true );

		wp_localize_script(
			'pinsdownload-tool',
			'PinsDownloadConfig',
			array(
				'restUrl'    => esc_url_raw( rest_url( 'pinsdownload/v1/resolve' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'streamBase' => esc_url( trailingslashit( home_url( '/' ) ) ),
				'streamNonce' => wp_create_nonce( 'pinsdownload_stream' ),
				'bulkLimit'  => (int) apply_filters( 'pinsdownload_bulk_limit', 100 ),
				'zipAfter'   => 5,
				'strings'    => array(
					'fetching'     => __( 'Fetching your link...', 'pinsdownload' ),
					'failed'       => __( 'We could not read that link. Check it is a public Pinterest link and try again.', 'pinsdownload' ),
					'private'      => __( 'This content is private, login-only, or an invitation link. We only work with public Pinterest links.', 'pinsdownload' ),
					'gone'         => __( 'This pin looks like it has been deleted or is no longer available.', 'pinsdownload' ),
					'zipping'      => __( 'Building your ZIP file...', 'pinsdownload' ),
					'saved'        => __( 'Saved to your device.', 'pinsdownload' ),
					'rateLimited'  => __( 'You are going a bit fast. Please wait a few seconds and try again.', 'pinsdownload' ),
					'savedToMoodboard' => __( 'Saved to Moodboard', 'pinsdownload' ),
				),
			)
		);
	}

	// Loaded everywhere: the "Save to Moodboard" button can appear on any
	// tool result (homepage or a landing page), not only on the moodboard
	// page itself. The script is tiny (localStorage only, no network).
	wp_enqueue_script( 'pinsdownload-moodboard', PINSDOWNLOAD_URI . '/assets/js/moodboard.js', array(), PINSDOWNLOAD_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'pinsdownload_assets' );

/**
 * Whether the current view renders the download tool (homepage or a
 * Tool Landing Page). Used to conditionally load the heavier JS.
 */
function pinsdownload_page_has_tool() {
	if ( is_front_page() ) {
		return true;
	}
	if ( is_page_template( 'page-templates/template-tool-landing.php' ) ) {
		return true;
	}
	return (bool) apply_filters( 'pinsdownload_page_has_tool', false );
}

/**
 * PWA: manifest link + theme color + apple touch icon.
 */
function pinsdownload_pwa_head() {
	echo '<link rel="manifest" href="' . esc_url( home_url( '/manifest.json' ) ) . '">' . "\n";
	echo '<meta name="theme-color" content="#e60023">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( PINSDOWNLOAD_URI . '/assets/icons/icon-192.png' ) . '">' . "\n";
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
}
add_action( 'wp_head', 'pinsdownload_pwa_head' );

/**
 * Preload the two font files used above the fold (heading + body) so
 * the hero doesn't flash unstyled text while the rest of style.css's
 * @font-face rules resolve. Kept to two files on purpose, page weight
 * matters more here than completeness.
 */
function pinsdownload_font_preload() {
	echo '<link rel="preload" href="' . esc_url( PINSDOWNLOAD_URI . '/assets/fonts/baloo2-700.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	echo '<link rel="preload" href="' . esc_url( PINSDOWNLOAD_URI . '/assets/fonts/inter-400.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'pinsdownload_font_preload', 1 );

/**
 * Includes.
 */
require_once PINSDOWNLOAD_DIR . '/inc/class-pinsdownload-resolver.php';
require_once PINSDOWNLOAD_DIR . '/inc/rest-api.php';
require_once PINSDOWNLOAD_DIR . '/inc/stream.php';
require_once PINSDOWNLOAD_DIR . '/inc/pwa-root-files.php';
require_once PINSDOWNLOAD_DIR . '/inc/shortcode-tool.php';
require_once PINSDOWNLOAD_DIR . '/inc/shortcode-moodboard.php';
require_once PINSDOWNLOAD_DIR . '/inc/metaboxes.php';
require_once PINSDOWNLOAD_DIR . '/inc/schema.php';
require_once PINSDOWNLOAD_DIR . '/inc/seo.php';
require_once PINSDOWNLOAD_DIR . '/inc/icons.php';
require_once PINSDOWNLOAD_DIR . '/inc/customizer.php';
