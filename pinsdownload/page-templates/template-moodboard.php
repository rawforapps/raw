<?php
/**
 * Template Name: Moodboard
 * Description: My Moodboard page. Create one WP Page using this
 * template (e.g. at /moodboard/) and it renders the visitor's saved
 * items automatically. No other setup needed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div class="pd-container pd-section">
	<h1><?php the_title(); ?></h1>
	<div class="pd-article__content">
		<?php the_content(); ?>
	</div>

	<?php echo do_shortcode( '[pinsdownload_moodboard]' ); ?>
</div>

<?php get_footer(); ?>
