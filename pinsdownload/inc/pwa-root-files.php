<?php
/**
 * Serve /sw.js and /manifest.json from the site root even though the
 * actual files live inside the theme folder.
 *
 * A service worker's default scope is the directory it's served from.
 * If sw.js were only reachable at
 * /wp-content/themes/pinsdownload/sw.js, it could only ever control
 * that one folder, not the whole site, so "offline-capable UI shell"
 * would silently not work. Intercepting the root-level request and
 * streaming the theme file's contents (with the right Content-Type)
 * gives the service worker full-site scope without needing a rewrite
 * rule flush or server config the user would have to remember to set.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'pinsdownload_serve_root_pwa_files' );

function pinsdownload_serve_root_pwa_files() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$raw_uri       = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$request_path  = trim( (string) wp_parse_url( $raw_uri, PHP_URL_PATH ), '/' );
	$site_path     = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

	if ( $site_path && 0 === strpos( $request_path, $site_path ) ) {
		$request_path = trim( substr( $request_path, strlen( $site_path ) ), '/' );
	}

	if ( 'sw.js' === $request_path ) {
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: no-cache' );
		readfile( PINSDOWNLOAD_DIR . '/sw.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		exit;
	}

	if ( 'manifest.json' === $request_path ) {
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		header( 'Cache-Control: no-cache' );
		readfile( PINSDOWNLOAD_DIR . '/manifest.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		exit;
	}
}
