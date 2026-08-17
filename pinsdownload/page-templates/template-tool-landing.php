<?php
/**
 * Template Name: Tool Landing Page
 * Description: Thin wrapper around the shared PinsDownload engine. Set
 * the content type + intro paragraph (and optionally a custom format
 * strip and page FAQ) in the "PinsDownload Landing Page Settings" box
 * below the editor, publish, and this page gets its own URL, H1, and
 * the exact same tool as the homepage underneath. This is how every
 * content-type page (image downloader, board downloader, story
 * downloader, and so on) gets added without touching code.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$post_id      = get_the_ID();
$type         = get_post_meta( $post_id, '_pinsdownload_tool_type', true ) ?: 'general';
$intro        = get_post_meta( $post_id, '_pinsdownload_intro', true );
$format_strip = get_post_meta( $post_id, '_pinsdownload_format_strip', true );
$faq_pairs    = pinsdownload_parse_faq_raw( get_post_meta( $post_id, '_pinsdownload_faq_raw', true ) );

if ( ! $format_strip ) {
	$format_strip = __( 'HD · 2K · 4K quality · No watermark · MP4, JPG, PNG, GIF supported · Works on phone, tablet, and computer', 'pinsdownload' );
}
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
	<?php echo esc_html( $format_strip ); ?>
</div>

<?php do_action( 'pinsdownload_ad_slot', 'landing_after_strip' ); ?>

<?php if ( get_the_content() ) : ?>
	<section class="pd-section pd-container pd-article__content">
		<?php the_content(); ?>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/works-doesnt' ); ?>

<?php if ( $faq_pairs ) : ?>
	<section class="pd-section pd-container pd-faq">
		<h2><?php esc_html_e( 'Frequently Asked Questions', 'pinsdownload' ); ?></h2>
		<?php foreach ( $faq_pairs as $pair ) : ?>
			<details>
				<summary><?php echo esc_html( $pair[0] ); ?></summary>
				<p><?php echo esc_html( $pair[1] ); ?></p>
			</details>
		<?php endforeach; ?>
	</section>
<?php else : ?>
	<section class="pd-section pd-container">
		<h2><?php esc_html_e( 'Is This Safe to Use?', 'pinsdownload' ); ?></h2>
		<p>
			<?php esc_html_e( 'Yes. We never ask for your Pinterest username or password. You paste a public link, we fetch the file, and nothing you download is stored on our servers afterward.', 'pinsdownload' ); ?>
		</p>
	</section>
<?php endif; ?>

<?php get_template_part( 'template-parts/trust-badges' ); ?>
<?php get_template_part( 'template-parts/other-tools' ); ?>

<?php
pinsdownload_output_softwareapplication_schema();
pinsdownload_output_howto_schema(
	array(
		__( 'Open Pinterest and find the pin you want.', 'pinsdownload' ),
		__( 'Tap the share icon and choose "Copy Link."', 'pinsdownload' ),
		__( 'Paste the link above and tap Download.', 'pinsdownload' ),
	)
);
if ( $faq_pairs ) {
	pinsdownload_output_faqpage_schema( $faq_pairs );
}
pinsdownload_output_breadcrumb_schema();
get_footer();
