<?php
/**
 * Template Name: Tool Landing Page
 * Description: Thin wrapper around the shared PinsDownload engine. Set
 * the content type + intro paragraph in the "PinsDownload Landing Page
 * Settings" box below the editor, publish, and this page gets its own
 * URL, H1, and the exact same tool as the homepage underneath. This is
 * how every content-type page (image downloader, board downloader,
 * story downloader, and so on) gets added without touching code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$type       = get_post_meta( get_the_ID(), '_pinsdownload_tool_type', true ) ?: 'general';
$intro      = get_post_meta( get_the_ID(), '_pinsdownload_intro', true );
$type_label = pinsdownload_tool_types()[ $type ] ?? '';
?>

<?php pinsdownload_breadcrumb(); ?>

<section class="pd-hero pd-container">
	<h1><?php the_title(); ?></h1>
	<?php if ( $intro ) : ?>
		<p class="pd-hero__subtitle"><?php echo esc_html( $intro ); ?></p>
	<?php endif; ?>

	<?php echo do_shortcode( '[pinsdownload_tool type="' . esc_attr( $type ) . '"]' ); ?>
</section>

<div class="pd-strip">
	<?php esc_html_e( 'HD · 2K · 4K quality · No watermark · MP4, JPG, PNG, GIF supported · Works on phone, tablet, and computer', 'pinsdownload' ); ?>
</div>

<?php do_action( 'pinsdownload_ad_slot', 'landing_after_strip' ); ?>

<?php if ( get_the_content() ) : ?>
	<section class="pd-section pd-container pd-article__content">
		<?php the_content(); ?>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/works-doesnt' ); ?>

<section class="pd-section pd-container">
	<h2><?php esc_html_e( 'Is This Safe to Use?', 'pinsdownload' ); ?></h2>
	<p>
		<?php esc_html_e( 'Yes. We never ask for your Pinterest username or password. You paste a public link, we fetch the file, and nothing you download is stored on our servers afterward.', 'pinsdownload' ); ?>
	</p>
</section>

<?php
pinsdownload_output_softwareapplication_schema();
pinsdownload_output_breadcrumb_schema();
get_footer();
