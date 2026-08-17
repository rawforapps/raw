<?php
/**
 * PinsDownload — Backend (WPCode PHP Snippet)
 * ============================================
 *
 * Self-contained Pinterest extraction engine + REST API + streaming
 * download proxy. No theme dependency — install this as a WPCode
 * "PHP Snippet" (or in a small mu-plugin file) and it works with any
 * active theme, including a page you design yourself in Kadence.
 *
 * INSTALL (WPCode):
 *   1. WPCode -> Add Snippet -> Add Your Custom Code
 *   2. Code Type: PHP Snippet
 *   3. Paste this entire file's contents
 *   4. Insertion: Auto Insert -> Run Everywhere
 *   5. Save + Activate
 *
 * If you already installed an earlier version of this snippet,
 * REPLACE it entirely with this file rather than pasting alongside —
 * this version changes how pins are fetched, not just error messages.
 *
 * This registers:
 *   GET  /wp-json/pinsdownload/v1/version   — no login needed, returns a
 *                                              build tag. Check this
 *                                              FIRST if anything seems
 *                                              stale — it's the fastest
 *                                              way to confirm the code
 *                                              actually running on your
 *                                              site is this exact file.
 *   POST /wp-json/pinsdownload/v1/resolve   — paste a Pinterest URL, get
 *                                              back structured media data
 *   GET  /wp-json/pinsdownload/v1/debug     — admin-only (must be logged
 *                                              into wp-admin), returns
 *                                              raw diagnostics for a URL
 *                                              including whether the
 *                                              direct API call succeeded.
 *                                              Try this first:
 *                                              yoursite.com/wp-json/pinsdownload/v1/debug?url=https://www.pinterest.com/pin/1103804189961515698/
 *   GET  /?pinsdownload_stream=1&url=...&filename=...
 *                                            — streams the actual media
 *                                              file through this server
 *                                              (never stored on disk).
 *
 * WHAT CHANGED IN THIS VERSION (the actual fix, not just error
 * messages): the previous version's primary path scraped the pin
 * page's HTML for an embedded __PWS_DATA__ script tag. That was the
 * likely root cause of the "always says deleted" bug. This version's
 * primary path instead calls Pinterest's own public resource API
 * directly —
 *
 *     GET https://www.pinterest.com/resource/PinResource/get/
 *         ?data={"options":{"id":"<pin_id>","field_set_key":"detailed"}}
 *
 * — which is the actual technique every maintained open-source
 * Pinterest client uses (cross-checked against three independent, real
 * implementations: yt-dlp's pinterest.py, gallery-dl's pinterest.py,
 * and seregazhuk/php-pinterest-bot). No page load, no real session —
 * anonymous reads work with a placeholder CSRF token ('1234', the same
 * value the PHP bot hardcodes for its logged-out state). Boards use a
 * Board lookup + paginated BoardFeed the same way; profiles use
 * UserActivityPins. The old HTML-scrape method is kept as an automatic
 * fallback if the direct API call ever fails.
 *
 * IMPORTANT — this was fixed and cross-checked against real reference
 * source code, but still not verified against a live Pinterest
 * response: this build environment's network cannot reach
 * pinterest.com at all (sandboxed). Test it against the real test pin
 * on your actual server, and use the /debug endpoint above if it still
 * doesn't resolve — its `primary_api_attempt` field will tell you
 * exactly what Pinterest's API sent back.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Bumped every time this file changes. Check it at
// yoursite.com/wp-json/pinsdownload/v1/version — if it doesn't say
// "2026-08-17-r3", the code actually running on your site is not this
// file (an older WPCode snippet is still active, or the paste got cut
// short). That single check answers "is this even the version I think
// it is?" without guessing.
if ( ! defined( 'PINSDOWNLOAD_BACKEND_BUILD' ) ) {
	define( 'PINSDOWNLOAD_BACKEND_BUILD', '2026-08-17-r3' );
}

/**
 * Core Pinterest extraction engine.
 *
 * One entry point (resolve) handles every content type: single pin
 * (video/image/gif), carousel, story/idea pin, board, profile, ideas page,
 * answers page, and multi-pin-share links. Only the URL pattern and the
 * returned shape differ; the fetch/parse plumbing is shared.
 *
 * Verification note: this reads Pinterest's own public page data (the
 * __PWS_DATA__ JSON Pinterest embeds in every public pin/board/profile
 * page, the same technique used by long-standing open-source Pinterest
 * extractors). No login, no credentials, no access-control bypass anywhere
 * in this file. Pinterest's exact JSON field names can drift over time and
 * this class was written without live access to pinterest.com, so treat
 * the field paths marked "verify" as the first place to check if
 * extraction stops working, not the overall approach.
 */

if ( ! class_exists( 'PinsDownload_Resolver' ) ) :
class PinsDownload_Resolver {

	const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

	/**
	 * Pinterest's public resource API accepts an unvalidated placeholder
	 * CSRF token for anonymous/logged-out reads — confirmed independently
	 * by three real, maintained Pinterest clients (yt-dlp, gallery-dl,
	 * and seregazhuk/php-pinterest-bot, which literally hardcodes this
	 * same value for its logged-out state). No page load or real session
	 * is needed to obtain one.
	 */
	const CSRF_TOKEN = '1234';

	/**
	 * Calls Pinterest's own public resource API directly — the approach
	 * every maintained open-source Pinterest client actually uses, in
	 * place of scraping a page for embedded JSON. GET
	 * https://www.pinterest.com/resource/{Resource}Resource/get/ with
	 * the options payload as a JSON query param. No cookies/session
	 * beyond the placeholder CSRF token above.
	 *
	 * @return array{code:int,json:array}|null Null on transport/JSON failure.
	 */
	private function call_resource( $resource, $options ) {
		$query = array(
			'source_url' => '',
			'data'       => wp_json_encode(
				array(
					'options' => $options,
					'context' => new stdClass(),
				)
			),
		);

		$endpoint = 'https://www.pinterest.com/resource/' . rawurlencode( $resource ) . 'Resource/get/?' . http_build_query( $query );

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 15,
				'headers' => array(
					'User-Agent'              => self::USER_AGENT,
					'Accept'                  => 'application/json, text/javascript, */*, q=0.01',
					'X-Requested-With'        => 'XMLHttpRequest',
					'X-Pinterest-AppState'    => 'active',
					'X-Pinterest-PWS-Handler' => 'www/[username].js',
					'X-CSRFToken'             => self::CSRF_TOKEN,
					'Cookie'                  => 'csrftoken=' . self::CSRF_TOKEN . ';',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->log_diag( 'resource_call_failed:' . $resource, $endpoint, $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			$this->log_diag( 'resource_call_bad_json:' . $resource, $endpoint, array( 'code' => $code, 'body' => $body ) );
			return null;
		}

		return array(
			'code' => $code,
			'json' => $json,
		);
	}

	/**
	 * Resolve a public Pinterest URL into normalized media metadata.
	 *
	 * @param string $url Raw user-submitted URL.
	 * @return array {
	 *     @type bool   $ok
	 *     @type string $error       One of: invalid_url, private, deleted, blocked_or_changed, unsupported, fetch_failed.
	 *     @type string $type        pin_video|pin_image|pin_gif|carousel|story|board|profile|ideas|answers|share
	 *     @type string $title
	 *     @type array  $items       List of normalized media items (see normalize_pin()).
	 * }
	 */
	public function resolve( $url ) {
		$url = $this->sanitize_input_url( $url );
		if ( ! $url ) {
			return $this->err( 'invalid_url' );
		}

		$resolved = $this->resolve_short_link( $url );
		if ( is_wp_error( $resolved ) ) {
			return $this->err( 'fetch_failed' );
		}

		if ( ! $this->is_pinterest_host( $resolved ) ) {
			return $this->err( 'invalid_url' );
		}

		$route = $this->detect_route( $resolved );

		switch ( $route['type'] ) {
			case 'pin':
				return $this->resolve_pin( $resolved, $route );
			case 'board':
				return $this->resolve_collection( $resolved, 'board', $route );
			case 'profile':
				return $this->resolve_collection( $resolved, 'profile', $route );
			case 'ideas':
				return $this->resolve_collection( $resolved, 'ideas', $route );
			case 'answers':
				return $this->resolve_collection( $resolved, 'answers', $route );
			default:
				return $this->err( 'unsupported' );
		}
	}

	/* ------------------------------------------------------------------ *
	 * URL handling
	 * ------------------------------------------------------------------ */

	private function sanitize_input_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return false;
		}
		if ( 0 === strpos( $url, '//' ) ) {
			$url = 'https:' . $url;
		}
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}
		$url = esc_url_raw( $url );
		return $url ? $url : false;
	}

	private function is_pinterest_host( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$host = strtolower( $host );
		// Covers www.pinterest.com, uk.pinterest.com, in.pinterest.com,
		// and ccTLD variants like pinterest.co.uk, pinterest.de, pinterest.ca.
		return (bool) preg_match( '/(^|\.)pinterest\.[a-z.]+$/', $host ) || (bool) preg_match( '/(^|\.)pin\.it$/', $host );
	}

	/**
	 * pin.it short links redirect to the real pinterest.com URL. Follow
	 * that redirect (no auth involved) and return the final URL.
	 */
	private function resolve_short_link( $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! preg_match( '/(^|\.)pin\.it$/', $host ) ) {
			return $url;
		}

		$response = wp_remote_head(
			$url,
			array(
				'redirection' => 5,
				'timeout'     => 12,
				'user-agent'  => self::USER_AGENT,
			)
		);

		if ( is_wp_error( $response ) ) {
			// Some short-link services block HEAD; fall back to GET.
			$response = wp_remote_get(
				$url,
				array(
					'redirection' => 5,
					'timeout'     => 12,
					'user-agent'  => self::USER_AGENT,
				)
			);
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$final = wp_remote_retrieve_header( $response, 'x-final-url' );
		if ( $final ) {
			return $final;
		}

		// WP's HTTP API follows redirects internally; the effective URL
		// isn't exposed directly, so if we didn't get a final-url header
		// we re-request without following redirects to read Location.
		$manual = wp_remote_get(
			$url,
			array(
				'redirection' => 0,
				'timeout'     => 12,
				'user-agent'  => self::USER_AGENT,
			)
		);
		if ( ! is_wp_error( $manual ) ) {
			$location = wp_remote_retrieve_header( $manual, 'location' );
			if ( $location ) {
				return $this->resolve_short_link( $location );
			}
		}

		return $url;
	}

	/**
	 * Classify the URL by path shape. Ambiguous cases fall through and are
	 * disambiguated later from the fetched page data.
	 */
	private function detect_route( $url ) {
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		$segments = $path === '' ? array() : explode( '/', $path );

		if ( isset( $segments[0] ) && 'pin' === $segments[0] && ! empty( $segments[1] ) ) {
			return array(
				'type'   => 'pin',
				'pin_id' => preg_replace( '/[^0-9]/', '', $segments[1] ),
			);
		}

		if ( isset( $segments[0] ) && 'amp' === $segments[0] && isset( $segments[1] ) && 'pin' === $segments[1] && ! empty( $segments[2] ) ) {
			return array(
				'type'   => 'pin',
				'pin_id' => preg_replace( '/[^0-9]/', '', $segments[2] ),
			);
		}

		if ( isset( $segments[0] ) && 'ideas' === $segments[0] ) {
			return array( 'type' => 'ideas' );
		}

		if ( isset( $segments[0] ) && 'answers' === $segments[0] ) {
			return array( 'type' => 'answers' );
		}

		if ( count( $segments ) >= 2 && 'answers' === $segments[1] ) {
			return array( 'type' => 'answers' );
		}

		// /{username}/{board-slug}/ -> board.
		if ( count( $segments ) >= 2 ) {
			$reserved = array( 'pin', 'ideas', 'answers', 'search', 'today', 'business', 'about', 'careers', 'login', 'signup' );
			if ( ! in_array( $segments[0], $reserved, true ) ) {
				return array(
					'type'     => 'board',
					'username' => $segments[0],
					'slug'     => $segments[1],
				);
			}
		}

		// /{username}/ -> profile.
		if ( count( $segments ) === 1 ) {
			$reserved = array( 'pin', 'ideas', 'answers', 'search', 'today', 'business', 'about', 'careers', 'login', 'signup' );
			if ( ! in_array( $segments[0], $reserved, true ) ) {
				return array(
					'type'     => 'profile',
					'username' => $segments[0],
				);
			}
		}

		return array( 'type' => 'unknown' );
	}

	/* ------------------------------------------------------------------ *
	 * Page fetch + __PWS_DATA__ extraction
	 * ------------------------------------------------------------------ */

	/**
	 * GET a public Pinterest page and return [ html, csrf_token, cookies ].
	 */
	private function fetch_page( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 15,
				'redirection' => 5,
				'user-agent'  => self::USER_AGENT,
				'headers'     => array(
					'Accept'          => 'text/html,application/xhtml+xml',
					'Accept-Language' => 'en-US,en;q=0.9',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		$cookies    = wp_remote_retrieve_cookies( $response );
		$csrf_token = '';
		foreach ( $cookies as $cookie ) {
			if ( 'csrftoken' === $cookie->name ) {
				$csrf_token = $cookie->value;
			}
		}

		return array(
			'code'    => $code,
			'body'    => $body,
			'cookies' => $cookies,
			'csrf'    => $csrf_token,
		);
	}

	/**
	 * Extract the __PWS_DATA__ JSON blob Pinterest embeds in every public
	 * page. This is the same initial-state approach used by established
	 * open-source Pinterest extractors.
	 */
	private function extract_pws_data( $html ) {
		if ( ! preg_match( '/<script[^>]+id=["\']__PWS_DATA__["\'][^>]*>(.*?)<\/script>/is', $html, $m ) ) {
			return null;
		}
		$json = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
		$data = json_decode( $json, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return null;
		}
		return $data;
	}

	private function looks_like_login_wall( $html ) {
		return (bool) preg_match( '/id=["\']UnauthenticatedRoot["\']|class=["\'][^"\']*login[^"\']*wall/i', $html );
	}

	/* ------------------------------------------------------------------ *
	 * Single pin (video / image / gif / carousel / story)
	 * ------------------------------------------------------------------ */

	private function resolve_pin( $url, $route ) {
		// Primary path: call Pinterest's own public Pin resource API
		// directly, no page load needed first. field_set_key "detailed"
		// is what returns the full images.orig / videos.video_list data
		// this tool needs (confirmed via seregazhuk/php-pinterest-bot,
		// which explicitly reads images.orig.url from this same
		// field set).
		$api = $this->call_resource(
			'Pin',
			array(
				'id'            => $route['pin_id'],
				'field_set_key' => 'detailed',
			)
		);

		if ( $api ) {
			if ( 404 === (int) $api['code'] ) {
				return $this->err( 'deleted' );
			}
			$pin = $api['json']['resource_response']['data'] ?? null;
			if ( is_array( $pin ) && ! empty( $pin ) ) {
				return $this->normalize_pin( $pin );
			}
			$this->log_diag( 'pin_resource_empty_data', $url, array( 'code' => $api['code'], 'body' => wp_json_encode( $api['json'] ) ) );
		}

		// Resource API attempt failed (transport error, unexpected
		// shape, or Pinterest requiring a real session in some region).
		// Fall back to scraping the public page before giving up.
		$page = $this->fetch_page( $url );
		if ( is_wp_error( $page ) ) {
			$this->log_diag( 'fetch_failed', $url, $page->get_error_message() );
			return $this->err( 'fetch_failed' );
		}
		if ( 404 === (int) $page['code'] ) {
			return $this->err( 'deleted' );
		}
		if ( in_array( (int) $page['code'], array( 401, 403 ), true ) || $this->looks_like_login_wall( $page['body'] ) ) {
			return $this->err( 'private' );
		}

		$data = $this->extract_pws_data( $page['body'] );
		$pin  = $data ? $this->find_pin_in_pws_data( $data, $route['pin_id'] ) : null;

		if ( $pin ) {
			return $this->normalize_pin( $pin );
		}

		// Primary extraction failed (data blob missing, unparsable, or
		// pin not present in it). Before treating this as a dead end,
		// try the Open Graph tags Pinterest renders for link-preview/SEO
		// purposes — those are a much more stable contract than an
		// internal data blob's exact shape, so they tend to survive page
		// structure changes that break __PWS_DATA__ parsing. Yields only
		// one resolution (whatever size the tag points to), clearly
		// lower-confidence than the primary path, but real data beats a
		// false "deleted" message.
		$og = $this->extract_open_graph( $page['body'] );
		if ( $og ) {
			$this->log_diag( 'used_og_fallback', $url, $page );
			return $og;
		}

		$this->log_diag( null === $data ? 'no_pws_data_found_in_html' : 'pin_id_missing_from_pws_data', $url, $page );
		return $this->err( 'blocked_or_changed' );
	}

	/**
	 * Fallback extraction from Open Graph / Twitter Card meta tags. See
	 * the note in resolve_pin() for why this exists as a second attempt
	 * rather than the primary path.
	 */
	private function extract_open_graph( $html ) {
		$get_meta = function ( $property ) use ( $html ) {
			if ( preg_match( '/<meta[^>]+(?:property|name)=["\']' . preg_quote( $property, '/' ) . '["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m ) ) {
				return html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			}
			// Attribute order can be reversed (content before property/name).
			if ( preg_match( '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+(?:property|name)=["\']' . preg_quote( $property, '/' ) . '["\']/i', $html, $m ) ) {
				return html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
			}
			return '';
		};

		$title     = $get_meta( 'og:title' );
		$video_url = $get_meta( 'og:video:secure_url' ) ?: ( $get_meta( 'og:video' ) ?: $get_meta( 'og:video:url' ) );
		$image_url = $get_meta( 'og:image:secure_url' ) ?: $get_meta( 'og:image' );

		if ( $video_url ) {
			return array(
				'ok'    => true,
				'type'  => 'pin_video',
				'title' => $title,
				'items' => array(
					array(
						'media_type'  => 'video',
						'thumbnail'   => $image_url,
						'resolutions' => array(
							array(
								'label'  => __( 'Original (limited info available)', 'pinsdownload' ),
								'url'    => $video_url,
								'width'  => 0,
								'height' => 0,
							),
						),
					),
				),
			);
		}

		if ( $image_url ) {
			$is_gif = (bool) preg_match( '/\.gif(\?|$)/i', $image_url );
			return array(
				'ok'    => true,
				'type'  => $is_gif ? 'pin_gif' : 'pin_image',
				'title' => $title,
				'items' => array(
					array(
						'media_type'  => $is_gif ? 'gif' : 'image',
						'thumbnail'   => $image_url,
						'resolutions' => array(
							array(
								'label'  => __( 'Original (limited info available)', 'pinsdownload' ),
								'url'    => $image_url,
								'width'  => 0,
								'height' => 0,
							),
						),
					),
				),
			);
		}

		return null;
	}

	/**
	 * Diagnostic logging, silent unless WP_DEBUG is on. This is the
	 * concrete "what did Pinterest actually send back" answer that a
	 * blanket error message couldn't give — enable WP_DEBUG_LOG and
	 * check wp-content/debug.log after a failed resolve.
	 */
	private function log_diag( $label, $url, $page_or_message ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		if ( is_array( $page_or_message ) ) {
			$snippet = substr( (string) ( $page_or_message['body'] ?? '' ), 0, 500 );
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'[PinsDownload] %s | url=%s | status=%s | body_snippet=%s',
					$label,
					$url,
					$page_or_message['code'] ?? 'n/a',
					str_replace( array( "\n", "\r" ), ' ', $snippet )
				)
			);
		} else {
			error_log( sprintf( '[PinsDownload] %s | url=%s | %s', $label, $url, (string) $page_or_message ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Verify: Pinterest nests pin objects at
	 * props.initialReduxState.pins.{id} inside __PWS_DATA__. If Pinterest
	 * changes this shape, this is the method to update.
	 */
	private function find_pin_in_pws_data( $data, $pin_id ) {
		if ( ! is_array( $data ) ) {
			return null;
		}
		$pins = $data['props']['initialReduxState']['pins'] ?? null;
		if ( ! is_array( $pins ) ) {
			return null;
		}
		if ( $pin_id && isset( $pins[ $pin_id ] ) ) {
			return $pins[ $pin_id ];
		}
		// Fall back to the first (and usually only) pin present.
		return reset( $pins ) ?: null;
	}

	/**
	 * Turn a raw Pinterest pin object into our normalized shape. Handles
	 * plain video/image/gif pins, carousels, and story/idea pins by
	 * looping the same extraction across every item in the set.
	 */
	private function normalize_pin( $pin ) {
		$title = $pin['title'] ?? ( $pin['description'] ?? '' );

		// Story / Idea pin: multi-slide, each slide has its own media.
		if ( ! empty( $pin['story_pin_data']['pages'] ) && is_array( $pin['story_pin_data']['pages'] ) ) {
			$items = array();
			foreach ( $pin['story_pin_data']['pages'] as $page_block ) {
				$blocks = $page_block['blocks'] ?? array();
				foreach ( $blocks as $block ) {
					$item = $this->normalize_media_block( $block );
					if ( $item ) {
						$items[] = $item;
					}
				}
			}
			return array(
				'ok'    => true,
				'type'  => 'story',
				'title' => $title,
				'items' => $items,
			);
		}

		// Carousel pin: multiple images/videos in one pin.
		if ( ! empty( $pin['carousel_data']['carousel_slots'] ) && is_array( $pin['carousel_data']['carousel_slots'] ) ) {
			$items = array();
			foreach ( $pin['carousel_data']['carousel_slots'] as $slot ) {
				$item = $this->normalize_media_block( $slot );
				if ( $item ) {
					$items[] = $item;
				}
			}
			return array(
				'ok'    => true,
				'type'  => 'carousel',
				'title' => $title,
				'items' => $items,
			);
		}

		// Plain video pin.
		if ( ! empty( $pin['videos']['video_list'] ) && is_array( $pin['videos']['video_list'] ) ) {
			return array(
				'ok'    => true,
				'type'  => 'pin_video',
				'title' => $title,
				'items' => array( $this->normalize_video( $pin['videos']['video_list'], $pin['images'] ?? array(), true ) ),
			);
		}

		// Image or GIF pin.
		if ( ! empty( $pin['images'] ) && is_array( $pin['images'] ) ) {
			$is_gif = $this->looks_like_gif( $pin['images'] );
			return array(
				'ok'    => true,
				'type'  => $is_gif ? 'pin_gif' : 'pin_image',
				'title' => $title,
				'items' => array( $this->normalize_image( $pin['images'], $is_gif, true ) ),
			);
		}

		return $this->err( 'unsupported' );
	}

	private function normalize_media_block( $block ) {
		if ( ! empty( $block['videos']['video_list'] ) ) {
			return $this->normalize_video( $block['videos']['video_list'], $block['image']['images'] ?? ( $block['images'] ?? array() ) );
		}
		if ( ! empty( $block['image']['images'] ) ) {
			return $this->normalize_image( $block['image']['images'], false );
		}
		if ( ! empty( $block['images'] ) ) {
			return $this->normalize_image( $block['images'], $this->looks_like_gif( $block['images'] ) );
		}
		return null;
	}

	private function looks_like_gif( $images ) {
		foreach ( $images as $variant ) {
			if ( ! empty( $variant['url'] ) && preg_match( '/\.gif(\?|$)/i', $variant['url'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * video_list is keyed by quality label (e.g. V_360P, V_720P, V_EXP7,
	 * V_HLSV3_MOBILE). We keep only progressive MP4-looking variants and
	 * label them by resolution, largest first. We never invent a quality
	 * tier that isn't actually present in the source data.
	 */
	private function normalize_video( $video_list, $images, $fetch_size = false ) {
		$resolutions = array();
		foreach ( $video_list as $key => $variant ) {
			if ( empty( $variant['url'] ) ) {
				continue;
			}
			// HLS manifests aren't a single downloadable file; skip them
			// in favor of progressive MP4 variants.
			if ( preg_match( '/\.m3u8(\?|$)/i', $variant['url'] ) ) {
				continue;
			}
			$height = (int) ( $variant['height'] ?? 0 );
			$resolutions[] = array(
				'label'  => $height ? $height . 'p' : $key,
				'url'    => $variant['url'],
				'width'  => (int) ( $variant['width'] ?? 0 ),
				'height' => $height,
			);
		}
		usort(
			$resolutions,
			function ( $a, $b ) {
				return $b['height'] <=> $a['height'];
			}
		);

		if ( $fetch_size && ! empty( $resolutions[0]['url'] ) ) {
			$resolutions[0]['filesize'] = $this->fetch_content_length( $resolutions[0]['url'] );
		}

		return array(
			'media_type'  => 'video',
			'thumbnail'   => $this->best_image_url( $images ),
			'resolutions' => $resolutions,
		);
	}

	private function normalize_image( $images, $is_gif, $fetch_size = false ) {
		$resolutions = array();
		foreach ( $images as $key => $variant ) {
			if ( empty( $variant['url'] ) ) {
				continue;
			}
			$width = (int) ( $variant['width'] ?? 0 );
			$resolutions[] = array(
				'label'  => 'orig' === $key ? __( 'Original', 'pinsdownload' ) : ( $width ? $width . 'px' : $key ),
				'url'    => $variant['url'],
				'width'  => $width,
				'height' => (int) ( $variant['height'] ?? 0 ),
			);
		}
		usort(
			$resolutions,
			function ( $a, $b ) {
				return $b['width'] <=> $a['width'];
			}
		);

		if ( $fetch_size && ! empty( $resolutions[0]['url'] ) ) {
			$resolutions[0]['filesize'] = $this->fetch_content_length( $resolutions[0]['url'] );
		}

		return array(
			'media_type'  => $is_gif ? 'gif' : 'image',
			'thumbnail'   => $this->best_image_url( $images, 400 ),
			'resolutions' => $resolutions,
		);
	}

	/**
	 * Best-effort HEAD request for a file size estimate. Only called for
	 * the single top resolution of a single-pin resolve (never for bulk
	 * collections), so it can't blow the 5-second link-to-preview target.
	 */
	private function fetch_content_length( $url ) {
		$response = wp_remote_head(
			$url,
			array(
				'timeout'    => 4,
				'user-agent' => self::USER_AGENT,
			)
		);
		if ( is_wp_error( $response ) ) {
			return null;
		}
		$length = wp_remote_retrieve_header( $response, 'content-length' );
		return $length ? (int) $length : null;
	}

	private function best_image_url( $images, $target_width = null ) {
		if ( empty( $images ) || ! is_array( $images ) ) {
			return '';
		}
		if ( $target_width ) {
			foreach ( $images as $variant ) {
				if ( ! empty( $variant['width'] ) && (int) $variant['width'] >= $target_width && ! empty( $variant['url'] ) ) {
					return $variant['url'];
				}
			}
		}
		$first = reset( $images );
		return $first['url'] ?? '';
	}

	/* ------------------------------------------------------------------ *
	 * Bulk collections: board / profile / ideas / answers
	 * ------------------------------------------------------------------ */

	private function resolve_collection( $url, $kind, $route ) {
		// Primary path for board/profile: call Pinterest's resource API
		// directly (Board -> BoardFeed, or UserActivityPins), the same
		// approach every maintained open-source Pinterest client uses.
		// Ideas/answers pages don't have an independently-confirmed
		// resource name, so they go straight to the page-scrape fallback.
		if ( 'board' === $kind ) {
			$result = $this->resolve_board_via_api( $route );
			if ( $result ) {
				return $result;
			}
		} elseif ( 'profile' === $kind ) {
			$result = $this->resolve_profile_via_api( $route );
			if ( $result ) {
				return $result;
			}
		}

		return $this->resolve_collection_via_page_scrape( $url, $kind, $route );
	}

	/**
	 * Board resource lookup (gets board_id from {username, slug}) then
	 * paginated BoardFeed. Options payload confirmed against
	 * seregazhuk/php-pinterest-bot's Board/BoardFeed provider methods.
	 * Returns null (not an error state) on any failure so the caller
	 * falls back to page-scraping instead.
	 */
	private function resolve_board_via_api( $route ) {
		if ( empty( $route['username'] ) || empty( $route['slug'] ) ) {
			return null;
		}

		$board_api = $this->call_resource(
			'Board',
			array(
				'username'      => $route['username'],
				'slug'          => $route['slug'],
				'field_set_key' => 'detailed',
			)
		);
		$board = $board_api['json']['resource_response']['data'] ?? null;
		if ( ! is_array( $board ) || empty( $board['id'] ) ) {
			return null;
		}

		$limit = (int) apply_filters( 'pinsdownload_bulk_limit', 100 );
		$items = $this->paginate_resource_items(
			'BoardFeed',
			array(
				'board_id'      => $board['id'],
				'field_set_key' => 'react_grid_pin',
				'prepend'       => false,
			),
			$limit
		);

		if ( empty( $items ) ) {
			return null;
		}

		return array(
			'ok'    => true,
			'type'  => 'board',
			'title' => $board['name'] ?? $route['slug'],
			'items' => $items,
		);
	}

	/**
	 * UserActivityPins resource, paginated. Resource name confirmed
	 * against seregazhuk/php-pinterest-bot's user_activity_pins() method.
	 * Returns null on any failure so the caller falls back to
	 * page-scraping instead.
	 */
	private function resolve_profile_via_api( $route ) {
		if ( empty( $route['username'] ) ) {
			return null;
		}

		$limit = (int) apply_filters( 'pinsdownload_bulk_limit', 100 );
		$items = $this->paginate_resource_items(
			'UserActivityPins',
			array(
				'username'            => $route['username'],
				'field_set_key'       => 'grid_item',
				'is_own_profile_pins' => false,
			),
			$limit
		);

		if ( empty( $items ) ) {
			return null;
		}

		return array(
			'ok'    => true,
			'type'  => 'profile',
			'title' => $route['username'],
			'items' => $items,
		);
	}

	/**
	 * Shared pagination loop for any resource that returns a flat list
	 * of pin-shaped results plus an echoed bookmarks cursor for the next
	 * page. Stops on an empty/"-end-" bookmark, hitting $limit, or after
	 * 20 pages as a hard safety cap.
	 */
	private function paginate_resource_items( $resource, $base_options, $limit ) {
		$items     = array();
		$bookmarks = null;
		$guard     = 0;

		do {
			$options = $base_options;
			if ( $bookmarks ) {
				$options['bookmarks'] = $bookmarks;
			}

			$api     = $this->call_resource( $resource, $options );
			$results = $api['json']['resource_response']['data'] ?? null;
			if ( ! is_array( $results ) ) {
				break;
			}

			foreach ( $results as $pin ) {
				if ( ! is_array( $pin ) || empty( $pin['id'] ) ) {
					continue;
				}
				if ( isset( $pin['type'] ) && 'pin' !== $pin['type'] ) {
					continue;
				}
				$normalized = $this->normalize_pin( $pin );
				if ( ! empty( $normalized['ok'] ) && ! empty( $normalized['items'][0] ) ) {
					$item              = $normalized['items'][0];
					$item['pin_id']    = $pin['id'];
					$item['pin_title'] = $normalized['title'];
					$items[]           = $item;
				}
			}

			$bookmarks = $api['json']['resource']['options']['bookmarks'] ?? null;
			$guard++;
		} while ( $bookmarks && ! empty( $bookmarks[0] ) && '-end-' !== $bookmarks[0] && count( $items ) < $limit && $guard < 20 );

		return array_slice( $items, 0, $limit );
	}

	/**
	 * Fallback path: scrape the public page's embedded __PWS_DATA__ for
	 * an initial batch, then continue pagination the same way. Used when
	 * the direct resource-API attempt above fails, and always used for
	 * ideas/answers pages (no independently-confirmed resource name for
	 * those yet).
	 */
	private function resolve_collection_via_page_scrape( $url, $kind, $route ) {
		$page = $this->fetch_page( $url );
		if ( is_wp_error( $page ) ) {
			$this->log_diag( 'fetch_failed', $url, $page->get_error_message() );
			return $this->err( 'fetch_failed' );
		}
		if ( 404 === (int) $page['code'] ) {
			return $this->err( 'deleted' );
		}
		if ( in_array( (int) $page['code'], array( 401, 403 ), true ) || $this->looks_like_login_wall( $page['body'] ) ) {
			return $this->err( 'private' );
		}

		$data      = $this->extract_pws_data( $page['body'] );
		$pin_ids   = $this->collect_pin_ids_from_resource_cache( $data );
		$pins_map  = $data['props']['initialReduxState']['pins'] ?? array();
		$limit     = (int) apply_filters( 'pinsdownload_bulk_limit', 100 );

		$bookmarks = $this->collect_bookmarks_from_resource_cache( $data );
		$resource  = $this->resource_name_for( $kind );

		// Keep paginating through Pinterest's own resource endpoint,
		// public data only, until we hit the limit or run out of pages.
		$guard = 0;
		while ( count( $pin_ids ) < $limit && $bookmarks && '-end-' !== reset( $bookmarks ) && $guard < 20 ) {
			$guard++;
			$page_data = $this->fetch_resource_page( $resource, $route, $bookmarks, $page['csrf'], $page['cookies'] );
			if ( ! $page_data ) {
				break;
			}
			foreach ( $page_data['ids'] as $id ) {
				$pin_ids[] = $id;
			}
			foreach ( $page_data['pins'] as $id => $pin ) {
				$pins_map[ $id ] = $pin;
			}
			$bookmarks = $page_data['bookmarks'];
		}

		$pin_ids = array_slice( array_values( array_unique( $pin_ids ) ), 0, $limit );

		if ( empty( $pin_ids ) ) {
			$this->log_diag( null === $data ? 'no_pws_data_found_in_html' : 'no_pins_in_resource_cache', $url, $page );
			return $this->err( 'blocked_or_changed' );
		}

		$items = array();
		foreach ( $pin_ids as $id ) {
			if ( empty( $pins_map[ $id ] ) ) {
				continue;
			}
			$normalized = $this->normalize_pin( $pins_map[ $id ] );
			if ( ! empty( $normalized['ok'] ) && ! empty( $normalized['items'][0] ) ) {
				$item                 = $normalized['items'][0];
				$item['pin_id']       = $id;
				$item['pin_title']    = $normalized['title'];
				$items[]              = $item;
			}
		}

		return array(
			'ok'    => true,
			'type'  => $kind,
			'title' => $data['props']['initialReduxState']['boards'][ array_key_first( $data['props']['initialReduxState']['boards'] ?? array() ) ]['name'] ?? ucfirst( $kind ),
			'items' => $items,
		);
	}

	private function resource_name_for( $kind ) {
		switch ( $kind ) {
			case 'board':
				return 'BoardFeedResource';
			case 'profile':
				return 'UserActivityPinsResource';
			case 'ideas':
				return 'IdeasResource';
			case 'answers':
				return 'AnswersResource';
		}
		return 'BoardFeedResource';
	}

	/**
	 * Verify: initial-batch pin ids for a board/profile/ideas/answers page
	 * live in __PWS_DATA__.props.initialReduxState.resourceDataCache,
	 * keyed by resource name. This walks every cache entry defensively
	 * rather than assuming one exact key, since the exact cache key
	 * (which includes serialized request options) varies per page.
	 */
	private function collect_pin_ids_from_resource_cache( $data ) {
		$ids   = array();
		$cache = $data['props']['initialReduxState']['resourceDataCache'] ?? array();
		if ( ! is_array( $cache ) ) {
			return $ids;
		}
		foreach ( $cache as $entry ) {
			$results = $entry['data']['data'] ?? ( $entry['data'] ?? null );
			if ( ! is_array( $results ) ) {
				continue;
			}
			foreach ( $results as $result ) {
				if ( is_array( $result ) && ! empty( $result['id'] ) && ! empty( $result['type'] ) && 'pin' === $result['type'] ) {
					$ids[] = $result['id'];
				}
			}
		}
		return $ids;
	}

	private function collect_bookmarks_from_resource_cache( $data ) {
		$cache = $data['props']['initialReduxState']['resourceDataCache'] ?? array();
		if ( ! is_array( $cache ) ) {
			return array();
		}
		foreach ( $cache as $entry ) {
			if ( ! empty( $entry['data']['bookmark'] ) ) {
				return array( $entry['data']['bookmark'] );
			}
			if ( ! empty( $entry['bookmarks'] ) ) {
				return $entry['bookmarks'];
			}
		}
		return array();
	}

	/**
	 * Fallback-path pagination only (see resolve_collection_via_page_scrape).
	 * The primary path for board/profile is paginate_resource_items(),
	 * which doesn't need a page-load-derived csrf token or cookies at
	 * all. This older method is kept for ideas/answers pages and as a
	 * second attempt if the primary API call fails.
	 *
	 * Verify: exact "options" payload for ideas/answers resource names
	 * against a live request — these aren't independently confirmed the
	 * way board/profile now are.
	 */
	private function fetch_resource_page( $resource, $route, $bookmarks, $csrf, $cookies ) {
		$options = array(
			'bookmarks' => $bookmarks,
		);
		if ( ! empty( $route['username'] ) ) {
			$options['username'] = $route['username'];
		}
		if ( ! empty( $route['slug'] ) ) {
			$options['board_url'] = '/' . $route['username'] . '/' . $route['slug'] . '/';
		}

		$query = array(
			'source_url' => '/',
			'data'       => wp_json_encode(
				array(
					'options' => $options,
					'context' => new stdClass(),
				)
			),
		);

		$endpoint = 'https://www.pinterest.com/resource/' . rawurlencode( $resource ) . '/get/?' . http_build_query( $query );

		$cookie_header = '';
		foreach ( $cookies as $cookie ) {
			$cookie_header .= $cookie->name . '=' . $cookie->value . '; ';
		}

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout' => 15,
				'headers' => array(
					'User-Agent'       => self::USER_AGENT,
					'Accept'           => 'application/json',
					'X-Requested-With' => 'XMLHttpRequest',
					'X-CSRFToken'      => $csrf,
					'X-Pinterest-PWS-Handler' => 'www/[username]/[slug].js',
					'Cookie'           => $cookie_header,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return null;
		}

		$results = $json['resource_response']['data'] ?? null;
		if ( ! is_array( $results ) ) {
			return null;
		}

		$ids  = array();
		$pins = array();
		foreach ( $results as $result ) {
			if ( is_array( $result ) && ! empty( $result['id'] ) ) {
				$ids[]              = $result['id'];
				$pins[ $result['id'] ] = $result;
			}
		}

		$next_bookmarks = $json['resource']['options']['bookmarks'] ?? array( '-end-' );

		return array(
			'ids'       => $ids,
			'pins'      => $pins,
			'bookmarks' => $next_bookmarks,
		);
	}

	/* ------------------------------------------------------------------ *
	 * Live diagnostics (admin-only, see the /debug REST route)
	 * ------------------------------------------------------------------ */

	/**
	 * Fetches a URL and reports exactly what was found, without
	 * normalizing or hiding anything behind an error code. This is the
	 * concrete, on-your-own-server version of "log the raw response"
	 * that couldn't be run from this sandbox — hit the debug endpoint
	 * once the site is live and this shows you the real cause.
	 */
	public function debug_fetch( $url ) {
		$url = $this->sanitize_input_url( $url );
		if ( ! $url ) {
			return array( 'error' => 'invalid_url' );
		}

		$resolved = $this->resolve_short_link( $url );
		if ( is_wp_error( $resolved ) ) {
			return array( 'error' => 'short_link_fetch_failed', 'message' => $resolved->get_error_message() );
		}

		$route = $this->detect_route( $resolved );

		// Check the primary path first — this is what resolve() actually
		// tries before ever falling back to a page scrape, so it's the
		// most useful thing to report here.
		$api_report = array( 'attempted' => false );
		if ( 'pin' === $route['type'] ) {
			$api_report['attempted'] = true;
			$api                     = $this->call_resource( 'Pin', array( 'id' => $route['pin_id'], 'field_set_key' => 'detailed' ) );
			if ( null === $api ) {
				$api_report['result'] = 'transport_or_json_error';
			} else {
				$api_report['http_status'] = $api['code'];
				$pin_data                  = $api['json']['resource_response']['data'] ?? null;
				$api_report['pin_data_found'] = is_array( $pin_data ) && ! empty( $pin_data );
				$api_report['response_top_level_keys'] = is_array( $api['json'] ) ? array_keys( $api['json'] ) : array();
				if ( ! $api_report['pin_data_found'] ) {
					$api_report['raw_response_snippet'] = substr( wp_json_encode( $api['json'] ), 0, 1000 );
				}
			}
		}

		$page = $this->fetch_page( $resolved );
		if ( is_wp_error( $page ) ) {
			return array(
				'error'         => 'fetch_failed',
				'message'       => $page->get_error_message(),
				'resolved_url'  => $resolved,
			);
		}

		$data           = $this->extract_pws_data( $page['body'] );
		$og             = $this->extract_open_graph( $page['body'] );
		$pin            = ( $data && 'pin' === $route['type'] ) ? $this->find_pin_in_pws_data( $data, $route['pin_id'] ) : null;

		return array(
			'resolved_url'         => $resolved,
			'detected_route'       => $route,
			'primary_api_attempt'  => $api_report,
			'http_status'          => $page['code'],
			'looks_like_login_wall' => $this->looks_like_login_wall( $page['body'] ),
			'pws_data_script_found' => null !== $data,
			'pin_found_in_pws_data' => (bool) $pin,
			'open_graph_fallback_available' => (bool) $og,
			'body_length'          => strlen( $page['body'] ),
			'body_snippet_first_1000' => substr( $page['body'], 0, 1000 ),
			'body_snippet_contains_pws_data_id' => false !== strpos( $page['body'], '__PWS_DATA__' ),
			'body_snippet_contains_captcha_or_challenge' => (bool) preg_match( '/captcha|are you human|unusual traffic|verify you are/i', $page['body'] ),
		);
	}

	/* ------------------------------------------------------------------ */

	private function err( $code ) {
		return array(
			'ok'    => false,
			'error' => $code,
			'type'  => '',
			'title' => '',
			'items' => array(),
		);
	}
}
endif;

/**
 * REST API: POST /wp-json/pinsdownload/v1/resolve
 *
 * Thin wrapper around PinsDownload_Resolver. Adds sanitization and a
 * light per-IP rate limit so normal human traffic never hits friction
 * (no CAPTCHA, no forced waits) while scripted abuse gets throttled.
 */

add_action(
	'rest_api_init',
	function () {
		// Unauthenticated, trivial to check from a plain browser tab —
		// exists purely so a stale/duplicate copy of this snippet is
		// obvious at a glance instead of a silent guessing game. If this
		// route 404s while /resolve works, the active code is not this
		// file; delete the old WPCode snippet and re-paste this one.
		register_rest_route(
			'pinsdownload/v1',
			'/version',
			array(
				'methods'             => 'GET',
				'callback'            => 'pinsdownload_rest_version',
				'permission_callback' => '__return_true',
			)
		);

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

		// Admin-only: hits a URL and reports exactly what was found, no
		// error-code normalization. Use this from the browser (while
		// logged into wp-admin) to see the real cause of a failed
		// resolve — GET /wp-json/pinsdownload/v1/debug?url=...
		register_rest_route(
			'pinsdownload/v1',
			'/debug',
			array(
				'methods'             => 'GET',
				'callback'            => 'pinsdownload_rest_debug',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
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
if ( ! function_exists( 'pinsdownload_rate_limited' ) ) :

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
			'invalid_url'        => 400,
			'private'            => 403,
			'deleted'            => 404,
			'blocked_or_changed' => 503,
			'unsupported'        => 422,
			'fetch_failed'       => 502,
		);
		$status = $status_map[ $result['error'] ] ?? 400;
		return new WP_REST_Response( $result, $status );
	}

	return new WP_REST_Response( $result, 200 );
}

function pinsdownload_rest_debug( WP_REST_Request $request ) {
	$resolver = new PinsDownload_Resolver();
	return new WP_REST_Response( $resolver->debug_fetch( $request->get_param( 'url' ) ), 200 );
}

function pinsdownload_rest_version() {
	return new WP_REST_Response(
		array(
			'build'                => defined( 'PINSDOWNLOAD_BACKEND_BUILD' ) ? PINSDOWNLOAD_BACKEND_BUILD : 'unknown',
			'has_debug_route'      => true,
			'has_direct_api_fetch' => method_exists( 'PinsDownload_Resolver', 'call_resource' ),
		),
		200
	);
}

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
 * as an open proxy to fetch arbitrary sites — that allowlist is the
 * actual protection here, deliberately not a nonce. A nonce would tie
 * this to a specific logged-in-page-load context, which breaks the
 * moment this endpoint needs to be callable from a static Custom HTML
 * block/WPCode embed that WordPress never templates a nonce into. This
 * mirrors /resolve, which was already fully public for the same reason.
 */

add_action( 'init', 'pinsdownload_maybe_stream' );

function pinsdownload_maybe_stream() {
	if ( empty( $_GET['pinsdownload_stream'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$url = isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

/**
 * [pinsdownload_widget] shortcode.
 *
 * Added so the widget can be embedded through a plain WordPress
 * Shortcode block without going through WPCode's separate "HTML
 * Snippet" rendering path at all. Some WPCode setups have been seen to
 * strip <form>/<script>/<style> tags from HTML-type snippet output
 * when it is rendered as a shortcode inside page content — a plain
 * add_shortcode() registered from a PHP snippet does not go through
 * that path; its return value is inserted by WordPress core exactly
 * as-is, the same mechanism the theme build already uses successfully
 * for [pinsdownload_tool]. If the separate widget HTML snippet in
 * WPCode was not rendering, delete that HTML snippet and put
 * [pinsdownload_widget] in a Shortcode block instead — same tool, more
 * reliable delivery path.
 */
function pinsdownload_widget_shortcode() {
	return <<<'PDW_WIDGET_HTML'
<div id="pinsdownload-widget" class="pdw">
	<form class="pdw-form" novalidate>
		<label class="pdw-sr-only" for="pdw-input">Pinterest link</label>
		<input type="url" id="pdw-input" class="pdw-input" inputmode="url" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Paste your Pinterest link here">
		<button type="submit" class="pdw-submit">
			<svg class="pdw-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 19h16"/></svg>
			Download
		</button>
	</form>

	<div class="pdw-progress" hidden>
		<div class="pdw-progress-bar"></div>
		<span class="pdw-progress-label">0%</span>
	</div>

	<div class="pdw-status" role="status" aria-live="polite"></div>

	<div class="pdw-results" hidden></div>
</div>

<style>
	#pinsdownload-widget, #pinsdownload-widget * {
		box-sizing: border-box;
	}
	.pdw {
		--pdw-red: #e60023;
		--pdw-red-hover: #cc001f;
		--pdw-ink: #111111;
		--pdw-ink-soft: #5b5b62;
		--pdw-bg: #fafafa;
		--pdw-border: #ececee;
		--pdw-radius: 14px;
		--pdw-shadow: 0 4px 14px rgba(17,17,17,0.08);
		max-width: 640px;
		margin: 0 auto;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
		color: var(--pdw-ink);
		background: #fff;
		border-radius: 18px;
		box-shadow: var(--pdw-shadow);
		padding: 20px;
	}
	.pdw-sr-only {
		position: absolute;
		width: 1px;
		height: 1px;
		overflow: hidden;
		clip: rect(1px,1px,1px,1px);
	}
	.pdw-form {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
	}
	.pdw-input {
		flex: 1 1 240px;
		min-width: 0;
		padding: 15px 16px;
		font-size: 1rem;
		border: 2px solid var(--pdw-border);
		border-radius: 12px;
		background: var(--pdw-bg);
		min-height: 44px;
	}
	.pdw-input:focus {
		outline: none;
		border-color: var(--pdw-red);
		box-shadow: 0 0 0 4px rgba(230,0,35,0.12);
	}
	.pdw-submit {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		background: var(--pdw-red);
		color: #fff;
		border: none;
		padding: 15px 22px;
		font-size: 1rem;
		font-weight: 700;
		border-radius: 12px;
		cursor: pointer;
		min-height: 44px;
		touch-action: manipulation;
	}
	.pdw-submit:hover { background: var(--pdw-red-hover); }
	.pdw-submit:disabled { cursor: default; position: relative; color: transparent; }
	.pdw-submit:disabled::after {
		content: "";
		position: absolute;
		width: 18px; height: 18px;
		border: 2.5px solid rgba(255,255,255,0.4);
		border-top-color: #fff;
		border-radius: 50%;
		animation: pdw-spin 0.7s linear infinite;
	}
	@keyframes pdw-spin { to { transform: rotate(360deg); } }
	.pdw-icon { width: 20px; height: 20px; flex-shrink: 0; }

	.pdw-progress {
		margin-top: 14px;
		background: var(--pdw-border);
		border-radius: 999px;
		height: 8px;
		overflow: hidden;
	}
	.pdw-progress-bar {
		height: 100%;
		background: linear-gradient(90deg, var(--pdw-red), #ff5470);
		width: 0%;
	}
	.pdw-progress-label {
		display: block;
		font-size: 0.8rem;
		color: var(--pdw-ink-soft);
		margin-top: 4px;
	}
	.pdw-status {
		margin-top: 10px;
		font-size: 0.95rem;
		min-height: 1.3em;
		font-weight: 500;
	}
	.pdw-status.is-error { color: var(--pdw-red); }
	.pdw-results { margin-top: 18px; text-align: left; }

	.pdw-single {
		display: flex;
		gap: 16px;
		background: var(--pdw-bg);
		border: 1px solid var(--pdw-border);
		border-radius: var(--pdw-radius);
		padding: 16px;
		flex-wrap: wrap;
	}
	.pdw-thumb {
		width: 130px; height: 130px;
		object-fit: cover;
		border-radius: 10px;
		background: var(--pdw-border);
		flex-shrink: 0;
	}
	.pdw-single-body { flex: 1 1 200px; min-width: 0; }
	.pdw-title { font-weight: 600; margin: 0 0 10px; word-break: break-word; }
	.pdw-quality-label { display: block; font-size: 0.82rem; color: var(--pdw-ink-soft); margin-bottom: 4px; }
	.pdw-quality { width: 100%; padding: 10px; border-radius: 10px; border: 1.5px solid var(--pdw-border); margin-bottom: 10px; }
	.pdw-btn {
		display: inline-flex; align-items: center; gap: 8px;
		border: none; padding: 12px 18px; border-radius: 10px;
		font-weight: 700; cursor: pointer; font-size: 0.92rem; min-height: 44px;
	}
	.pdw-btn-primary { background: var(--pdw-red); color: #fff; }
	.pdw-btn-primary:hover { background: var(--pdw-red-hover); }
	.pdw-btn-ghost { background: #fff; color: var(--pdw-ink); border: 1.5px solid var(--pdw-border); }

	.pdw-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(110px,1fr));
		gap: 10px;
	}
	.pdw-grid-item {
		position: relative;
		background: var(--pdw-bg);
		border: 1px solid var(--pdw-border);
		border-radius: 10px;
		padding: 8px;
		font-size: 0.72rem;
	}
	.pdw-grid-item img { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 6px; background: var(--pdw-border); }
	.pdw-grid-item input { position: absolute; top: 8px; left: 8px; width: 18px; height: 18px; }
	.pdw-grid-meta { color: var(--pdw-ink-soft); text-transform: capitalize; }
	.pdw-actions { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; }

	.pdw-done {
		margin-top: 14px;
		padding: 12px 16px;
		background: #e6f6ee;
		border-radius: 10px;
		display: flex; align-items: center; justify-content: space-between;
		gap: 10px; flex-wrap: wrap;
	}
	.pdw-done p { margin: 0; font-weight: 600; color: #1e8e5a; }

	@media (max-width: 480px) {
		.pdw-form { flex-direction: column; }
		.pdw-submit { width: 100%; justify-content: center; }
	}
</style>

<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script>
( function () {
	'use strict';

	var widget = document.getElementById( 'pinsdownload-widget' );
	if ( ! widget ) { return; }

	var STRINGS = {
		fetching: 'Fetching your link...',
		failed: 'We could not read that link. Check it is a public Pinterest link and try again.',
		private: 'This content is private, login-only, or an invitation link. We only work with public Pinterest links.',
		gone: 'This pin looks like it has been deleted or is no longer available.',
		blockedOrChanged: 'We reached Pinterest but could not read this pin. Pinterest may be blocking automated requests right now, or changed its page format. Please try again in a moment.',
		zipping: 'Building your ZIP file...',
		saved: 'Saved to your device.',
		rateLimited: 'You are going a bit fast. Please wait a few seconds and try again.'
	};

	var REST_URL = window.location.origin + '/wp-json/pinsdownload/v1/resolve';
	var STREAM_BASE = window.location.origin + '/';
	var ZIP_AFTER = 5;

	var form = widget.querySelector( '.pdw-form' );
	var input = widget.querySelector( '.pdw-input' );
	var submitBtn = widget.querySelector( '.pdw-submit' );
	var progress = widget.querySelector( '.pdw-progress' );
	var progressBar = widget.querySelector( '.pdw-progress-bar' );
	var progressLabel = widget.querySelector( '.pdw-progress-label' );
	var status = widget.querySelector( '.pdw-status' );
	var results = widget.querySelector( '.pdw-results' );

	var progressTimer = null;
	var lastUrl = '';

	function isLikelyPinterestUrl( value ) {
		return /pinterest\.[a-z.]+\/|pin\.it\//i.test( value );
	}

	function formatBytes( bytes ) {
		if ( ! bytes ) { return ''; }
		var units = [ 'B', 'KB', 'MB', 'GB' ];
		var i = 0, n = bytes;
		while ( n >= 1024 && i < units.length - 1 ) { n /= 1024; i++; }
		return n.toFixed( n >= 10 || i === 0 ? 0 : 1 ) + ' ' + units[ i ];
	}

	function guessExtension( mediaType, url ) {
		var m = /\.(mp4|jpg|jpeg|png|gif|webp)(\?|$)/i.exec( url || '' );
		if ( m ) { return m[1].toLowerCase(); }
		if ( mediaType === 'video' ) { return 'mp4'; }
		if ( mediaType === 'gif' ) { return 'gif'; }
		return 'jpg';
	}

	function slugFilename( title, suffix, ext ) {
		var base = ( title || 'pinsdownload' ).toString().toLowerCase()
			.replace( /[^a-z0-9]+/g, '-' ).replace( /(^-|-$)/g, '' ).slice( 0, 60 );
		return ( base || 'pinsdownload' ) + ( suffix ? '-' + suffix : '' ) + '.' + ext;
	}

	function streamUrlFor( mediaUrl, filename ) {
		var params = new URLSearchParams();
		params.set( 'pinsdownload_stream', '1' );
		params.set( 'url', mediaUrl );
		params.set( 'filename', filename );
		return STREAM_BASE + '?' + params.toString();
	}

	function triggerDownload( href ) {
		var a = document.createElement( 'a' );
		a.href = href;
		a.rel = 'noopener';
		document.body.appendChild( a );
		a.click();
		a.remove();
	}

	function setStatus( message, isError ) {
		status.textContent = message || '';
		status.classList.toggle( 'is-error', !! isError );
	}

	function startProgress() {
		var pct = 0;
		progress.hidden = false;
		progressBar.style.width = '0%';
		progressLabel.textContent = '0%';
		submitBtn.disabled = true;
		progressTimer = setInterval( function () {
			pct = Math.min( pct + Math.random() * 18, 90 );
			progressBar.style.width = pct + '%';
			progressLabel.textContent = Math.round( pct ) + '%';
		}, 220 );
	}

	function finishProgress() {
		clearInterval( progressTimer );
		progressBar.style.width = '100%';
		progressLabel.textContent = '100%';
		submitBtn.disabled = false;
		setTimeout( function () { progress.hidden = true; }, 300 );
	}

	function showDone() {
		setStatus( STRINGS.saved );
		var existing = widget.querySelector( '.pdw-done' );
		if ( existing ) { existing.remove(); }
		var box = document.createElement( 'div' );
		box.className = 'pdw-done';
		box.innerHTML = '<p>Saved to your device.</p><button type="button" class="pdw-btn pdw-btn-ghost pdw-again">Download this link again</button>';
		box.querySelector( '.pdw-again' ).addEventListener( 'click', function () {
			start( lastUrl );
		} );
		widget.appendChild( box );
	}

	function start( url ) {
		url = ( url || '' ).trim();
		if ( ! url ) { return; }
		lastUrl = url;
		results.hidden = true;
		results.innerHTML = '';
		setStatus( STRINGS.fetching );
		startProgress();

		fetch( REST_URL, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( { url: url } )
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( data ) {
				finishProgress();
				handleResponse( data );
			} )
			.catch( function () {
				finishProgress();
				setStatus( STRINGS.failed, true );
			} );
	}

	function handleResponse( data ) {
		if ( ! data || ! data.ok ) {
			var key = data && data.error ? data.error : 'failed';
			var map = {
				private: STRINGS.private,
				deleted: STRINGS.gone,
				blocked_or_changed: STRINGS.blockedOrChanged,
				invalid_url: STRINGS.failed,
				unsupported: STRINGS.failed,
				fetch_failed: STRINGS.failed,
				rate_limited: STRINGS.rateLimited
			};
			setStatus( map[ key ] || STRINGS.failed, true );
			return;
		}

		setStatus( '' );
		results.hidden = false;

		var isBulk = [ 'carousel', 'story', 'board', 'profile', 'ideas', 'answers' ].indexOf( data.type ) !== -1;
		if ( isBulk ) { renderMulti( data ); } else { renderSingle( data ); }
	}

	function renderSingle( data ) {
		var item = data.items[0];
		var wrap = document.createElement( 'div' );
		wrap.className = 'pdw-single';

		var thumb = document.createElement( 'img' );
		thumb.className = 'pdw-thumb';
		thumb.src = item.thumbnail || '';
		thumb.alt = data.title || '';
		wrap.appendChild( thumb );

		var body = document.createElement( 'div' );
		body.className = 'pdw-single-body';

		var title = document.createElement( 'p' );
		title.className = 'pdw-title';
		title.textContent = data.title || '';
		body.appendChild( title );

		var label = document.createElement( 'label' );
		label.className = 'pdw-quality-label';
		label.textContent = 'Choose quality';
		body.appendChild( label );

		var select = document.createElement( 'select' );
		select.className = 'pdw-quality';
		item.resolutions.forEach( function ( res, i ) {
			var opt = document.createElement( 'option' );
			opt.value = String( i );
			opt.textContent = res.label + ( res.filesize ? ' (' + formatBytes( res.filesize ) + ')' : '' );
			select.appendChild( opt );
		} );
		body.appendChild( select );

		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'pdw-btn pdw-btn-primary';
		btn.textContent = 'Download';
		btn.addEventListener( 'click', function () {
			var res = item.resolutions[ Number( select.value ) ];
			if ( ! res ) { return; }
			var ext = guessExtension( item.media_type, res.url );
			triggerDownload( streamUrlFor( res.url, slugFilename( data.title, res.label, ext ) ) );
			showDone();
		} );
		body.appendChild( btn );

		wrap.appendChild( body );
		results.innerHTML = '';
		results.appendChild( wrap );
	}

	function renderMulti( data ) {
		var wrap = document.createElement( 'div' );
		var grid = document.createElement( 'div' );
		grid.className = 'pdw-grid';

		data.items.forEach( function ( item, index ) {
			var cell = document.createElement( 'label' );
			cell.className = 'pdw-grid-item';

			var check = document.createElement( 'input' );
			check.type = 'checkbox';
			check.checked = true;
			check.dataset.index = String( index );
			cell.appendChild( check );

			var img = document.createElement( 'img' );
			img.src = item.thumbnail || '';
			img.alt = item.pin_title || data.title || '';
			cell.appendChild( img );

			var meta = document.createElement( 'span' );
			meta.className = 'pdw-grid-meta';
			meta.textContent = item.media_type + ( item.resolutions[0] ? ' - ' + item.resolutions[0].label : '' );
			cell.appendChild( meta );

			grid.appendChild( cell );
		} );
		wrap.appendChild( grid );

		var actions = document.createElement( 'div' );
		actions.className = 'pdw-actions';

		var dlSelected = document.createElement( 'button' );
		dlSelected.type = 'button';
		dlSelected.className = 'pdw-btn pdw-btn-primary';
		dlSelected.textContent = 'Download selected';
		dlSelected.addEventListener( 'click', function () {
			getSelected( grid ).forEach( function ( i, order ) {
				var item = data.items[ i ];
				var res = item.resolutions[0];
				if ( ! res ) { return; }
				var filename = slugFilename( item.pin_title || data.title, String( order + 1 ), guessExtension( item.media_type, res.url ) );
				setTimeout( function () { triggerDownload( streamUrlFor( res.url, filename ) ); }, order * 250 );
			} );
			showDone();
		} );
		actions.appendChild( dlSelected );

		if ( data.items.length > ZIP_AFTER ) {
			var zipBtn = document.createElement( 'button' );
			zipBtn.type = 'button';
			zipBtn.className = 'pdw-btn pdw-btn-ghost';
			zipBtn.textContent = 'Download all as ZIP';
			zipBtn.addEventListener( 'click', function () { downloadAsZip( data, grid ); } );
			actions.appendChild( zipBtn );
		}

		wrap.appendChild( actions );
		results.innerHTML = '';
		results.appendChild( wrap );
	}

	function getSelected( grid ) {
		return Array.prototype.slice.call( grid.querySelectorAll( 'input:checked' ) )
			.map( function ( el ) { return Number( el.dataset.index ); } );
	}

	function downloadAsZip( data, grid ) {
		var indexes = getSelected( grid );
		if ( ! indexes.length || typeof JSZip === 'undefined' ) { return; }

		setStatus( STRINGS.zipping );
		startProgress();

		var zip = new JSZip();
		var fetchOne = function ( i ) {
			var item = data.items[ i ];
			var res = item.resolutions[0];
			if ( ! res ) { return Promise.resolve(); }
			var filename = slugFilename( item.pin_title || data.title, String( i + 1 ), guessExtension( item.media_type, res.url ) );
			return fetch( streamUrlFor( res.url, filename ) )
				.then( function ( r ) { return r.blob(); } )
				.then( function ( blob ) { zip.file( filename, blob ); } )
				.catch( function () {} );
		};

		Promise.all( indexes.map( fetchOne ) ).then( function () {
			zip.generateAsync( { type: 'blob' } ).then( function ( blob ) {
				finishProgress();
				var url = URL.createObjectURL( blob );
				var a = document.createElement( 'a' );
				a.href = url;
				a.download = slugFilename( data.title || 'pinsdownload', 'collection', 'zip' );
				document.body.appendChild( a );
				a.click();
				a.remove();
				setTimeout( function () { URL.revokeObjectURL( url ); }, 4000 );
				showDone();
			} );
		} );
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		start( input.value );
	} );

	input.addEventListener( 'paste', function () {
		setTimeout( function () {
			var value = input.value.trim();
			if ( isLikelyPinterestUrl( value ) ) { start( value ); }
		}, 30 );
	} );
} )();
</script>
PDW_WIDGET_HTML;
}

add_shortcode( 'pinsdownload_widget', 'pinsdownload_widget_shortcode' );

endif;
