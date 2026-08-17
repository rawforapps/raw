<?php
/**
 * Meta boxes for pages.
 *
 * Two boxes:
 * - "SEO Title & Description" — every page (Technical foundation" in
 *   every content doc specifies an exact title tag + meta description
 *   per page, so this applies universally, not just to landing pages).
 * - "PinsDownload Landing Page Settings" — only meaningful when the
 *   page's template is "Tool Landing Page." Content type, intro
 *   paragraph, a page-specific format strip, and a page-specific FAQ,
 *   all editable from wp-admin with no code changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pinsdownload_tool_types() {
	return array(
		'video'    => __( 'Video pins', 'pinsdownload' ),
		'image'    => __( 'Image pins', 'pinsdownload' ),
		'gif'      => __( 'GIF pins', 'pinsdownload' ),
		'reels'    => __( 'Reels / short videos', 'pinsdownload' ),
		'story'    => __( 'Story / Idea pins', 'pinsdownload' ),
		'carousel' => __( 'Carousels', 'pinsdownload' ),
		'board'    => __( 'Boards', 'pinsdownload' ),
		'profile'  => __( 'Profiles', 'pinsdownload' ),
		'ideas'    => __( 'Ideas pages', 'pinsdownload' ),
		'answers'  => __( 'Answers pages', 'pinsdownload' ),
		'share'    => __( 'Multi-pin share links', 'pinsdownload' ),
		'general'  => __( 'General / homepage', 'pinsdownload' ),
	);
}

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'pinsdownload_seo',
			__( 'SEO Title & Description', 'pinsdownload' ),
			'pinsdownload_render_seo_metabox',
			'page',
			'normal',
			'high'
		);
		add_meta_box(
			'pinsdownload_landing_settings',
			__( 'PinsDownload Landing Page Settings', 'pinsdownload' ),
			'pinsdownload_render_landing_metabox',
			'page',
			'normal',
			'high'
		);
	}
);

function pinsdownload_render_seo_metabox( $post ) {
	wp_nonce_field( 'pinsdownload_seo_meta', 'pinsdownload_seo_meta_nonce' );

	$title = get_post_meta( $post->ID, '_pinsdownload_meta_title', true );
	$desc  = get_post_meta( $post->ID, '_pinsdownload_meta_description', true );
	?>
	<p>
		<label for="pinsdownload_meta_title"><strong><?php esc_html_e( 'Title tag', 'pinsdownload' ); ?></strong></label><br>
		<input type="text" name="pinsdownload_meta_title" id="pinsdownload_meta_title" style="width:100%;" value="<?php echo esc_attr( $title ); ?>">
		<span class="description"><?php esc_html_e( 'Leave blank to use the page title. This is the exact <title> tag text.', 'pinsdownload' ); ?></span>
	</p>
	<p>
		<label for="pinsdownload_meta_description"><strong><?php esc_html_e( 'Meta description', 'pinsdownload' ); ?></strong></label><br>
		<textarea name="pinsdownload_meta_description" id="pinsdownload_meta_description" rows="2" style="width:100%;"><?php echo esc_textarea( $desc ); ?></textarea>
	</p>
	<?php
}

function pinsdownload_render_landing_metabox( $post ) {
	wp_nonce_field( 'pinsdownload_landing_meta', 'pinsdownload_landing_meta_nonce' );

	$type         = get_post_meta( $post->ID, '_pinsdownload_tool_type', true ) ?: 'general';
	$intro        = get_post_meta( $post->ID, '_pinsdownload_intro', true );
	$format_strip = get_post_meta( $post->ID, '_pinsdownload_format_strip', true );
	$faq_raw      = get_post_meta( $post->ID, '_pinsdownload_faq_raw', true );
	?>
	<p>
		<em>
			<?php esc_html_e( 'Only used when this page\'s template is set to "Tool Landing Page" (Page Attributes box, right side). Set the content type, write one short intro paragraph, and publish, no code changes needed.', 'pinsdownload' ); ?>
		</em>
	</p>
	<p>
		<label for="pinsdownload_tool_type"><strong><?php esc_html_e( 'Content type', 'pinsdownload' ); ?></strong></label><br>
		<select name="pinsdownload_tool_type" id="pinsdownload_tool_type">
			<?php foreach ( pinsdownload_tool_types() as $key => $label ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="pinsdownload_intro"><strong><?php esc_html_e( 'Intro paragraph (shown under the H1, above the tool box)', 'pinsdownload' ); ?></strong></label><br>
		<textarea name="pinsdownload_intro" id="pinsdownload_intro" rows="3" style="width:100%;"><?php echo esc_textarea( $intro ); ?></textarea>
	</p>
	<p>
		<label for="pinsdownload_format_strip"><strong><?php esc_html_e( 'Format strip text (leave blank for the default HD/2K/4K strip)', 'pinsdownload' ); ?></strong></label><br>
		<input type="text" name="pinsdownload_format_strip" id="pinsdownload_format_strip" style="width:100%;" value="<?php echo esc_attr( $format_strip ); ?>">
	</p>
	<p>
		<label for="pinsdownload_faq_raw"><strong><?php esc_html_e( 'Page FAQ (schema-ready)', 'pinsdownload' ); ?></strong></label><br>
		<span class="description">
			<?php esc_html_e( 'One question per "Q:" line, its answer on the following "A:" line(s). Blank line between pairs. Leave empty to skip the FAQ block on this page.', 'pinsdownload' ); ?>
		</span><br>
		<textarea name="pinsdownload_faq_raw" id="pinsdownload_faq_raw" rows="10" style="width:100%;font-family:monospace;"><?php echo esc_textarea( $faq_raw ); ?></textarea>
	</p>
	<?php
}

add_action(
	'save_post_page',
	function ( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['pinsdownload_seo_meta_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pinsdownload_seo_meta_nonce'] ) ), 'pinsdownload_seo_meta' ) ) {
			if ( isset( $_POST['pinsdownload_meta_title'] ) ) {
				update_post_meta( $post_id, '_pinsdownload_meta_title', sanitize_text_field( wp_unslash( $_POST['pinsdownload_meta_title'] ) ) );
			}
			if ( isset( $_POST['pinsdownload_meta_description'] ) ) {
				update_post_meta( $post_id, '_pinsdownload_meta_description', sanitize_textarea_field( wp_unslash( $_POST['pinsdownload_meta_description'] ) ) );
			}
		}

		if ( isset( $_POST['pinsdownload_landing_meta_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pinsdownload_landing_meta_nonce'] ) ), 'pinsdownload_landing_meta' ) ) {

			if ( isset( $_POST['pinsdownload_tool_type'] ) ) {
				$type  = sanitize_text_field( wp_unslash( $_POST['pinsdownload_tool_type'] ) );
				$types = array_keys( pinsdownload_tool_types() );
				if ( in_array( $type, $types, true ) ) {
					update_post_meta( $post_id, '_pinsdownload_tool_type', $type );
				}
			}

			if ( isset( $_POST['pinsdownload_intro'] ) ) {
				update_post_meta( $post_id, '_pinsdownload_intro', sanitize_textarea_field( wp_unslash( $_POST['pinsdownload_intro'] ) ) );
			}

			if ( isset( $_POST['pinsdownload_format_strip'] ) ) {
				update_post_meta( $post_id, '_pinsdownload_format_strip', sanitize_text_field( wp_unslash( $_POST['pinsdownload_format_strip'] ) ) );
			}

			if ( isset( $_POST['pinsdownload_faq_raw'] ) ) {
				update_post_meta( $post_id, '_pinsdownload_faq_raw', sanitize_textarea_field( wp_unslash( $_POST['pinsdownload_faq_raw'] ) ) );
			}
		}
	}
);
