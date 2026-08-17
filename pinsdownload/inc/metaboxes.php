<?php
/**
 * "Landing Page Settings" meta box.
 *
 * This is what makes adding a new content-type landing page a wp-admin
 * task instead of a dev task: pick the Tool Landing Page template, pick
 * a type from this box, write one intro paragraph, publish. The template
 * (page-templates/template-tool-landing.php) does the rest.
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
			'pinsdownload_landing_settings',
			__( 'PinsDownload Landing Page Settings', 'pinsdownload' ),
			'pinsdownload_render_landing_metabox',
			'page',
			'normal',
			'high'
		);
	}
);

function pinsdownload_render_landing_metabox( $post ) {
	wp_nonce_field( 'pinsdownload_landing_meta', 'pinsdownload_landing_meta_nonce' );

	$type  = get_post_meta( $post->ID, '_pinsdownload_tool_type', true ) ?: 'general';
	$intro = get_post_meta( $post->ID, '_pinsdownload_intro', true );
	?>
	<p>
		<em>
			<?php esc_html_e( 'Only used when this page\'s template is set to "Tool Landing Page" (Page Attributes box, right side). Set the content type below, write one short intro paragraph, and publish, no code changes needed.', 'pinsdownload' ); ?>
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
	<?php
}

add_action(
	'save_post_page',
	function ( $post_id ) {
		if ( ! isset( $_POST['pinsdownload_landing_meta_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pinsdownload_landing_meta_nonce'] ) ), 'pinsdownload_landing_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

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
	}
);
