<?php
/**
 * [pinsdownload_tool] shortcode.
 *
 * This is the one reusable tool box embedded on the homepage and on
 * every Tool Landing Page. Only the placeholder copy changes per type;
 * the markup and JS behavior are identical everywhere so there is one
 * engine, not eleven.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'pinsdownload_tool', 'pinsdownload_tool_shortcode' );

function pinsdownload_tool_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'type' => 'general',
		),
		$atts,
		'pinsdownload_tool'
	);

	ob_start();
	?>
	<div class="pd-tool" data-type="<?php echo esc_attr( $atts['type'] ); ?>">
		<form class="pd-tool__form" novalidate>
			<label class="screen-reader-text" for="pd-tool-input-<?php echo esc_attr( $atts['type'] ); ?>">
				<?php esc_html_e( 'Pinterest link', 'pinsdownload' ); ?>
			</label>
			<input
				type="url"
				inputmode="url"
				autocomplete="off"
				autocapitalize="off"
				spellcheck="false"
				class="pd-tool__input"
				id="pd-tool-input-<?php echo esc_attr( $atts['type'] ); ?>"
				placeholder="<?php esc_attr_e( 'Paste your Pinterest link here', 'pinsdownload' ); ?>"
			>
			<button type="submit" class="pd-tool__submit">
				<?php pinsdownload_icon_e( 'download', 'pd-icon--btn' ); ?>
				<?php esc_html_e( 'Download', 'pinsdownload' ); ?>
			</button>
		</form>

		<div class="pd-tool__progress" hidden>
			<div class="pd-tool__progress-bar"></div>
			<span class="pd-tool__progress-label">0%</span>
		</div>

		<div class="pd-tool__status" role="status" aria-live="polite"></div>

		<div class="pd-tool__results" hidden></div>
	</div>

	<template class="pd-tpl-single">
		<div class="pd-result pd-result--single">
			<img class="pd-result__thumb" alt="" loading="lazy">
			<div class="pd-result__body">
				<p class="pd-result__title"></p>
				<label class="pd-result__quality-label" for="pd-quality-select"><?php esc_html_e( 'Choose quality', 'pinsdownload' ); ?></label>
				<select class="pd-result__quality"></select>
				<button type="button" class="pd-btn pd-btn--primary pd-result__download">
					<?php pinsdownload_icon_e( 'download' ); ?>
					<?php esc_html_e( 'Download', 'pinsdownload' ); ?>
				</button>
				<button type="button" class="pd-btn pd-btn--ghost pd-tool__save-moodboard pd-result__save-moodboard">
					<?php esc_html_e( 'Save to Moodboard', 'pinsdownload' ); ?>
				</button>
			</div>
		</div>
	</template>

	<template class="pd-tpl-multi-item">
		<div class="pd-multi-item">
			<label>
				<input type="checkbox" class="pd-multi-item__check" checked>
				<img class="pd-multi-item__thumb" alt="" loading="lazy">
				<span class="pd-multi-item__meta"></span>
			</label>
			<button type="button" class="pd-multi-item__save-moodboard" aria-label="<?php esc_attr_e( 'Save to Moodboard', 'pinsdownload' ); ?>" title="<?php esc_attr_e( 'Save to Moodboard', 'pinsdownload' ); ?>">♡</button>
		</div>
	</template>

	<template class="pd-tpl-done">
		<div class="pd-done">
			<p class="pd-done__message"><?php esc_html_e( 'Saved to your device.', 'pinsdownload' ); ?></p>
			<button type="button" class="pd-btn pd-btn--ghost pd-done__again"><?php esc_html_e( 'Download this link again', 'pinsdownload' ); ?></button>
		</div>
	</template>
	<?php
	return ob_get_clean();
}
