<?php
/**
 * Fallback template (required by WordPress for a theme to be valid).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="pd-container pd-section">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class( 'pd-article' ); ?>>
				<h1 class="pd-article__title"><?php the_title(); ?></h1>
				<div class="pd-article__content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Nothing found.', 'pinsdownload' ); ?></p>
	<?php endif; ?>
</div>

<?php get_footer(); ?>
