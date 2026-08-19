<?php
/**
 * PinsDownload — homepage template.
 *
 * Drop into the active theme's folder as front-page.php (exact
 * filename, with the hyphen — WordPress only recognizes it under
 * that exact name). Settings -> Reading -> "Your homepage displays"
 * -> A static page, with a real Page selected.
 *
 * Deliberately the simplest possible model: the Hero + downloader
 * tool are fixed (below), and everything else is that homepage
 * Page's own content, rendered with the_content() — the same core
 * WordPress function every theme uses for every page. No custom
 * zone system, no marker headings to get right, nothing to keep in
 * sync. Edit the Page in wp-admin like any other Page and it shows
 * up here, in that order. See frontpage-editable-sections.php
 * (install that too, once, alongside this file) for full details,
 * including how the FAQ's native "Details" blocks work.
 *
 * Technical foundation (set outside this file — WordPress already
 * owns <title>/<meta>, via your SEO plugin or the theme's header.php):
 *   Title tag:        Pinterest Video Downloader – Save Videos, Images, GIFs & Stories Free | PinsDownload
 *   Meta description: Download Pinterest videos, images, and GIFs in HD for free with PinsDownload. No login, no watermark, no app needed.
 *   Schema: SoftwareApplication (below) + FAQPage (below, generated live from the page's own Details blocks).
 *   URL: homepage stays at the site root, not a subfolder.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Graceful fallbacks if frontpage-editable-sections.php isn't active
   — the page still shows, with a notice to editors instead of a
   fatal error. */
if ( ! function_exists( 'pd_process_tool_submission' ) ) {
	function pd_process_tool_submission() {
		return array( null, '', '' );
	}
}
if ( ! function_exists( 'pd_render_tool_widget' ) ) {
	function pd_render_tool_widget( $result, $error, $submitted_url = '' ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">frontpage-editable-sections.php isn\'t active — the downloader tool needs it.</p>';
		}
	}
}
if ( ! function_exists( 'pd_output_styles' ) ) {
	function pd_output_styles() {}
}
if ( ! function_exists( 'pd_output_scripts' ) ) {
	function pd_output_scripts() {}
}
if ( ! function_exists( 'pd_icon' ) ) {
	function pd_icon( $name ) {}
}
if ( ! function_exists( 'pd_get_homepage_faq_pairs' ) ) {
	function pd_get_homepage_faq_pairs() {
		return array();
	}
}

list( $pdl_result, $pdl_error, $pdl_submitted_url ) = pd_process_tool_submission();

get_header();
pd_output_styles();
?>

<div class="pd-page" id="pd-page">

	<!-- =====================================================
	     HERO + TOOL — fixed. pintsave.net downloader, embedded live.
	     ===================================================== -->
	<section class="pd-hero-section">
		<div class="pd-hero-glow" aria-hidden="true"></div>
		<div class="pd-container pd-hero">
			<p class="pd-hero__eyebrow">Pinterest Downloader</p>
			<h1 class="pd-hero__title">Pinterest Video Downloader</h1>
			<p class="pd-hero__subtitle">Download Pinterest videos, images, GIFs, and stories in one click. Free, fast, and no account needed. Works as a full Pinterest video downloader online, no software to install.</p>

			<div class="pd-tool-card" id="pdl-tool">
				<?php pd_render_tool_widget( $pdl_result, $pdl_error, $pdl_submitted_url ); ?>
			</div>
		</div>
	</section>

	<!-- =====================================================
	     EVERYTHING BELOW — the homepage Page's own content.
	     Edit it in wp-admin (Pages -> your homepage -> Edit) like
	     any normal WordPress Page. Whatever's there, in whatever
	     order, is what renders here.
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-content">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					the_content();
				endwhile;
			elseif ( current_user_can( 'edit_posts' ) ) :
				?>
				<p class="pd-zone-missing">This page has no content yet. Edit it in wp-admin — it should auto-fill with the full PinsDownload homepage copy the first time this loads; reload if you just installed frontpage-editable-sections.php.</p>
				<?php
			endif;
			?>
		</div>
	</section>

	<!-- =====================================================
	     FINAL CTA — fixed heading + button; scrolls back to the tool.
	     ===================================================== -->
	<section class="pd-cta pd-band--soft">
		<div class="pd-container">
			<h2>Ready to Download?</h2>
			<a href="#pdl-tool" class="pd-btn-primary" id="pd-cta-scroll">
				<?php pd_icon( 'arrow' ); ?> Back to the Downloader
			</a>
		</div>
	</section>

</div>

<?php pd_output_scripts(); ?>

<?php
/* =========================================================
   SCHEMA — SoftwareApplication + FAQPage. FAQPage is generated
   live from the homepage Page's own native "Details" blocks — edit
   the FAQ in wp-admin and this updates with it automatically.
========================================================= */
$pd_schema_software = array(
	'@context'            => 'https://schema.org',
	'@type'               => 'SoftwareApplication',
	'name'                => 'PinsDownload',
	'applicationCategory' => 'MultimediaApplication',
	'operatingSystem'     => 'Any (web-based)',
	'url'                 => home_url( '/' ),
	'offers'              => array(
		'@type'         => 'Offer',
		'price'         => '0',
		'priceCurrency' => 'USD',
	),
	'description'         => 'Download Pinterest videos, images, and GIFs in HD for free with PinsDownload. No login, no watermark, no app needed.',
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_software ); ?></script>
<?php
$pd_faq_pairs = pd_get_homepage_faq_pairs();
if ( ! empty( $pd_faq_pairs ) ) :
	$pd_schema_faq_items = array();
	foreach ( $pd_faq_pairs as $pd_pair ) {
		$pd_schema_faq_items[] = array(
			'@type'          => 'Question',
			'name'           => $pd_pair[0],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $pd_pair[1],
			),
		);
	}
	$pd_schema_faq = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $pd_schema_faq_items,
	);
	?>
	<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_faq ); ?></script>
	<?php
endif;

get_footer();
