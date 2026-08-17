<?php
/**
 * REST API: POST /wp-json/pinsdownload/v1/resolve
 *
 * Thin wrapper around PinsDownload_Resolver. Adds sanitization and a
 * light per-IP rate limit so normal human traffic never hits friction
 * (no CAPTCHA, no forced waits) while scripted abuse gets throttled.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'pinsdownload/v1',
			'/resolve',
			array(
				'methods'             => 'POST',
				'callback'            => 'pinsdownload_rest_resolve',
				'permission_callback' => '__return_true',
				'args'                => array(
					'url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
);

/**
 * Reasonable per-IP throttle: 20 requests per 60 seconds. Wide enough
 * that no normal human hits it, tight enough to blunt scripted abuse.
 */
function pinsdownload_rate_limited() {
	$ip  = pinsdownload_client_ip();
	$key = 'pdrl_' . md5( $ip );
	$hits = (int) get_transient( $key );

	if ( $hits >= 20 ) {
		return true;
	}

	set_transient( $key, $hits + 1, 60 );
	return false;
}

function pinsdownload_client_ip() {
	foreach ( array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ) as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$val = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			$parts = explode( ',', $val );
			return trim( $parts[0] );
		}
	}
	return '0.0.0.0';
}

function pinsdownload_rest_resolve( WP_REST_Request $request ) {
	if ( pinsdownload_rate_limited() ) {
		return new WP_REST_Response(
			array(
				'ok'    => false,
				'error' => 'rate_limited',
			),
			429
		);
	}

	$url = $request->get_param( 'url' );

	$resolver = new PinsDownload_Resolver();
	$result   = $resolver->resolve( $url );

	if ( empty( $result['ok'] ) ) {
		$status_map = array(
			'invalid_url'   => 400,
			'private'       => 403,
			'deleted'       => 404,
			'unsupported'   => 422,
			'fetch_failed'  => 502,
		);
		$status = $status_map[ $result['error'] ] ?? 400;
		return new WP_REST_Response( $result, $status );
	}

	return new WP_REST_Response( $result, 200 );
}
