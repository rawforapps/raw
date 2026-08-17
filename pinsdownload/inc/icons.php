<?php
/**
 * Inline SVG icon set. Outline style, 2px stroke, no external icon
 * font or CDN (keeps the theme self-contained and avoids adding a
 * network dependency just for icons). One function, one switch, so
 * every icon shares the same visual language.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pinsdownload_icon( $name, $class = '' ) {
	$class = 'pd-icon ' . $class;
	$paths = array(
		'video'    => '<path d="M15 10l4.55-2.28A1 1 0 0 1 21 8.62v6.76a1 1 0 0 1-1.45.9L15 14"/><rect x="3" y="6" width="12" height="12" rx="2"/>',
		'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
		'gif'      => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 9v6M11 9v6M11 12h2.5M17 9h-2.5v6M17 12h-2"/>',
		'reels'    => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 4l3 4M13 4l3 4M2 10h20"/>',
		'story'    => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M9 6h6M9 18h6"/>',
		'carousel' => '<rect x="2" y="7" width="14" height="12" rx="2"/><path d="M8 3h12a2 2 0 0 1 2 2v12"/>',
		'board'    => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
		'profile'  => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/>',
		'ideas'    => '<path d="M9 18h6M10 22h4M12 2a6 6 0 0 0-4 10.5c.6.6 1 1.4 1 2.5h6c0-1.1.4-1.9 1-2.5A6 6 0 0 0 12 2z"/>',
		'answers'  => '<path d="M21 11.5a8.4 8.4 0 0 1-1 4A8.5 8.5 0 0 1 12 20a8.4 8.4 0 0 1-4-1L3 20l1-5a8.4 8.4 0 0 1-1-4 8.5 8.5 0 0 1 8.5-8.5A8.5 8.5 0 0 1 21 11.5z"/>',
		'share'    => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 10.5l6.8-4M8.6 13.5l6.8 4"/>',
		'free'     => '<circle cx="12" cy="12" r="9"/><path d="M9 9.5c0-1.4 1.3-2.5 3-2.5s3 1 3 2.2c0 2.3-3.5 2-3.5 4.8M12 17h.01"/>',
		'watermark' => '<path d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z"/><path d="M9.5 12l1.8 1.8L15 10"/>',
		'login'    => '<rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
		'quality'  => '<path d="M12 2l2.6 5.9L21 8.6l-4.6 4.4 1.2 6.5L12 16.4l-5.6 3.1 1.2-6.5L3 8.6l6.4-.7z"/>',
		'everywhere' => '<rect x="4" y="2" width="10" height="16" rx="2"/><rect x="15" y="7" width="6" height="14" rx="1.5"/><path d="M9 15h.01"/>',
		'ads'      => '<circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/>',
		'check'    => '<path d="M20 6L9 17l-5-5"/>',
		'cross'    => '<path d="M18 6L6 18M6 6l12 12"/>',
		'chevron'  => '<path d="M6 9l6 6 6-6"/>',
		'shield'   => '<path d="M12 3l7 4v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V7l7-4z"/>',
		'legal'    => '<path d="M12 3v18M5 8l-3 6a3 3 0 0 0 6 0zM19 8l-3 6a3 3 0 0 0 6 0zM5 8h14M9 3h6"/>',
		'star'     => '<path d="M12 2l3 6.5 7 1-5 5 1.2 7L12 18l-6.2 3.5L7 14.5 2 9.5l7-1z"/>',
		'zip'      => '<path d="M14.5 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.5L14.5 3z"/><path d="M14 3v6h6M10 3v2M10 7v2M10 11v2"/>',
		'download' => '<path d="M12 3v12M7 10l5 5 5-5"/><path d="M4 19h16"/>',
		'photo'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M21 16l-5.2-5.2a1.5 1.5 0 0 0-2.1 0L5 19"/>',
		'sparkle'  => '<path d="M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3z"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
		'heart'    => '<path d="M12 20.5s-7.5-4.6-9.8-9.3C.7 7.9 2.3 4.5 5.6 4c2-.3 3.7.7 4.4 2.3.7-1.6 2.4-2.6 4.4-2.3 3.3.5 4.9 3.9 3.4 7.2-2.3 4.7-9.8 9.3-9.8 9.3z"/>',
		'heart-filled' => '<path d="M12 20.5s-7.5-4.6-9.8-9.3C.7 7.9 2.3 4.5 5.6 4c2-.3 3.7.7 4.4 2.3.7-1.6 2.4-2.6 4.4-2.3 3.3.5 4.9 3.9 3.4 7.2-2.3 4.7-9.8 9.3-9.8 9.3z" fill="currentColor"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg class="' . esc_attr( trim( $class ) ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[ $name ] . '</svg>';
}

function pinsdownload_icon_e( $name, $class = '' ) {
	echo pinsdownload_icon( $name, $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * A visibly-marked empty image slot, sized for its context (square,
 * wide/16:9, tall/portrait). This is a layout placeholder, not content:
 * swap the markup for a real <img> once you have a screenshot/photo for
 * that spot. Ships empty on purpose rather than with a stock photo.
 */
function pinsdownload_image_slot( $ratio = 'wide', $label = '' ) {
	if ( ! $label ) {
		$label = __( 'Add an image here', 'pinsdownload' );
	}
	printf(
		'<div class="pd-img-slot pd-img-slot--%1$s"><span class="pd-img-slot__icon">%2$s</span><span class="pd-img-slot__label">%3$s</span></div>',
		esc_attr( $ratio ),
		pinsdownload_icon( 'photo' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html( $label )
	);
}
