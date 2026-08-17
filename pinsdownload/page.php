<?php
/**
 * Default template for ordinary pages (Privacy Policy, DMCA, "Is It
 * Legal," etc.). No tool, no special meta, just the page content, so
 * these need zero setup beyond writing them in wp-admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
pinsdownload_breadcrumb();
?>

<div class="pd-band pd-band--plain">
	<div class="pd-container pd-section">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'pd-article pd-reveal' ); ?>>
				<h1 class="pd-article__title"><?php the_title(); ?></h1>
				<div class="pd-article__content">
					<?php the_content(); ?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</div>

<?php
pinsdownload_output_breadcrumb_schema();
get_footer();
