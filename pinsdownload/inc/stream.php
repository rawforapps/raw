<?php
/**
 * Streaming download proxy.
 *
 * Downloads are streamed straight from Pinterest's own media CDN through
 * this server to the browser, chunk by chunk, and never written to disk
 * or buffered in full server memory. This is what makes the frontend's
 * one-click download button actually force a save (proper
 * Content-Disposition + filename) instead of depending on cross-origin
 * browser behavior, which is inconsistent for video files.
 *
 * Only pinimg.com media URLs are allowed through, so this can't be used
 * as an open proxy to fetch arbitrary sites.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'pinsdownload_maybe_stream' );

function pinsdownload_maybe_stream() {
	if ( empty( $_GET['pinsdownload_stream'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'pinsdownload_stream' ) ) {
		status_header( 403 );
		exit;
	}

	$url = isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : '';
	$host = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';

	if ( ! $host || ! preg_match( '/(^|\.)pinimg\.com$/i', $host ) ) {
		status_header( 400 );
		exit;
	}

	$filename = isset( $_GET['filename'] ) ? sanitize_file_name( wp_unslash( $_GET['filename'] ) ) : 'pinsdownload-file';

	if ( ! function_exists( 'curl_init' ) ) {
		// Fall back to a redirect if cURL isn't available; not ideal
		// (browser handles cross-origin download behavior itself) but
		// keeps the tool working on minimal hosts.
		wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect
		exit;
	}

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 300 );
	curl_setopt( $ch, CURLOPT_USERAGENT, PinsDownload_Resolver::USER_AGENT );

	$headers_sent = false;

	curl_setopt(
		$ch,
		CURLOPT_HEADERFUNCTION,
		function ( $curl_handle, $header_line ) use ( &$headers_sent, $filename ) {
			if ( ! $headers_sent && 0 === stripos( $header_line, 'content-type:' ) ) {
				header( trim( $header_line ) );
			}
			if ( ! $headers_sent && 0 === stripos( $header_line, 'content-length:' ) ) {
				header( trim( $header_line ) );
			}
			return strlen( $header_line );
		}
	);

	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Cache-Control: no-store' );
	header( 'X-Content-Type-Options: nosniff' );

	curl_setopt(
		$ch,
		CURLOPT_WRITEFUNCTION,
		function ( $curl_handle, $chunk ) {
			echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			flush();
			return strlen( $chunk );
		}
	);

	while ( ob_get_level() > 0 ) {
		ob_end_flush();
	}

	curl_exec( $ch );
	curl_close( $ch );
	exit;
}
