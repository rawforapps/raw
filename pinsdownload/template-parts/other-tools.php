<?php
/**
 * Reusable "Other Tools" links block. Auto-discovers every published
 * Tool Landing Page and links to it, excluding the current page. Stays
 * empty (and hidden) until at least one other landing page exists, per
 * the copy rule "don't link to pages that don't exist yet."
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pinsdownload_landing_pages = get_posts(
	array(
		'post_type'      => 'page',
		'posts_per_page' => 20,
		'post_status'    => 'publish',
		'post__not_in'   => array( get_the_ID() ),
		'meta_key'       => '_wp_page_template',
		'meta_value'     => 'page-templates/template-tool-landing.php',
	)
);

if ( $pinsdownload_landing_pages ) :
	?>
	<section class="pd-section pd-container">
		<h2><?php esc_html_e( 'Other Tools', 'pinsdownload' ); ?></h2>
		<p>
			<?php
			$pinsdownload_links = array();
			foreach ( $pinsdownload_landing_pages as $pinsdownload_lp ) {
				$pinsdownload_links[] = '<a href="' . esc_url( get_permalink( $pinsdownload_lp ) ) . '">' . esc_html( get_the_title( $pinsdownload_lp ) ) . '</a>';
			}
			echo wp_kses_post( implode( ' · ', $pinsdownload_links ) );
			?>
		</p>
	</section>
<?php endif; ?>
