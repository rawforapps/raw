<?php
/**
 * [pinsdownload_moodboard] shortcode.
 *
 * Renders the saved-items grid (populated client-side from localStorage
 * by assets/js/moodboard.js) plus print/export and clear-all controls.
 * No account, no database: this lives entirely in the visitor's browser.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'pinsdownload_moodboard', 'pinsdownload_moodboard_shortcode' );

function pinsdownload_moodboard_shortcode() {
	ob_start();
	?>
	<div class="pd-moodboard">
		<div
			class="pd-moodboard-grid"
			data-empty-text="<?php esc_attr_e( 'Nothing saved yet. Download something above and choose "Save to Moodboard."', 'pinsdownload' ); ?>"
		></div>

		<div class="pd-moodboard-actions">
			<button type="button" class="pd-btn pd-btn--primary pd-moodboard-print">
				<?php esc_html_e( 'Print / Export as image', 'pinsdownload' ); ?>
			</button>
			<button type="button" class="pd-btn pd-btn--ghost pd-moodboard-clear">
				<?php esc_html_e( 'Clear moodboard', 'pinsdownload' ); ?>
			</button>
		</div>

		<p class="pd-moodboard-note">
			<?php esc_html_e( 'Your moodboard is saved only in this browser. It is not uploaded anywhere and clearing your browser data will clear it too.', 'pinsdownload' ); ?>
		</p>
	</div>
	<?php
	return ob_get_clean();
}
