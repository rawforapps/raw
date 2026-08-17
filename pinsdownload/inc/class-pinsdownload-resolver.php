<?php
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PinsDownload_Resolver {

	const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

	/**
	 * Resolve a public Pinterest URL into normalized media metadata.
	 *
	 * @param string $url Raw user-submitted URL.
	 * @return array {
	 *     @type bool   $ok
	 *     @type string $error       One of: invalid_url, private, deleted, unsupported, fetch_failed.
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
		$page = $this->fetch_page( $url );
		if ( is_wp_error( $page ) ) {
			return $this->err( 'fetch_failed' );
		}
		if ( 404 === (int) $page['code'] ) {
			return $this->err( 'deleted' );
		}
		if ( in_array( (int) $page['code'], array( 401, 403 ), true ) || $this->looks_like_login_wall( $page['body'] ) ) {
			return $this->err( 'private' );
		}

		$data = $this->extract_pws_data( $page['body'] );
		$pin  = $this->find_pin_in_pws_data( $data, $route['pin_id'] );

		if ( ! $pin ) {
			return $this->err( 'deleted' );
		}

		return $this->normalize_pin( $pin );
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
		$page = $this->fetch_page( $url );
		if ( is_wp_error( $page ) ) {
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
			return $this->err( 'unsupported' );
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
	 * Call Pinterest's own public resource endpoint for the next page of
	 * a board/profile/ideas/answers feed. Uses only the csrf token +
	 * cookies obtained from the public page load; no credentials.
	 *
	 * Verify: exact "options" payload per resource type against a live
	 * request before relying on this for production traffic.
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
