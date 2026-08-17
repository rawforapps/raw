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
	<section class="pd-band pd-band--pink">
		<div class="pd-container pd-section pd-reveal">
			<h2><?php esc_html_e( 'Other Tools', 'pinsdownload' ); ?></h2>
			<div class="pd-link-row">
				<?php foreach ( $pinsdownload_landing_pages as $pinsdownload_lp ) : ?>
					<a href="<?php echo esc_url( get_permalink( $pinsdownload_lp ) ); ?>"><?php echo esc_html( get_the_title( $pinsdownload_lp ) ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>
