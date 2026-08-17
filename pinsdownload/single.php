<?php
/**
 * Default template for blog posts (guides, "how to" articles). Feeds
 * the "Guides & Tips" section on the homepage once posts exist.
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
				<p class="pd-article__meta"><?php echo esc_html( get_the_date() ); ?></p>
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
