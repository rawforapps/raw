<?php
/**
 * Template Name: Pinterest Shared Pins Downloader Page
 * The template file for the Pinterest Shared Pins Downloader page (slug: pinterest-shared-pins-downloader)
 *
 * Pairs with pinsdownload-core.php (WPCode snippet or mu-plugin) — same
 * core engine as every other PinsDownload page (icons, styles, tool
 * logic, section-band / smart-icon JS). Do not duplicate any of that
 * here, this file only lays out this page's own sections.
 *
 * @package PinsDownload
 */

get_header();

// Process the downloader tool submission if POSTed
list( $pdl_result, $pdl_error, $pdl_url ) = pd_process_tool_submission();

// Output unified modern Pinterest-themed CSS styles
pd_output_styles();
?>

<main id="pd-page" class="pd-site-main">

	<!-- =========================================================
	     1. HERO & TOOL SECTION (SHARED PINS DOWNLOADER)
	========================================================= -->
	<section class="pd-hero-section" aria-label="Pinterest Shared Pins Downloader">
		<div class="pd-hero-glow" aria-hidden="true"></div>
		<div class="pd-container pd-hero">

			<div class="pd-hero__eyebrow">
				<span>Multi-Pin Download &bull; ZIP Support &bull; Free</span>
			</div>

			<h1 class="pd-hero__title">
				Pinterest Shared Pins Downloader
			</h1>

			<p class="pd-hero__subtitle">
				Save every pin from a Pinterest multi-pin share link in one go. Free, no watermark, no account needed.
			</p>

			<!-- Downloader Tool Widget (Same API Backend) -->
			<div class="pd-tool-card" id="pdl-tool">
				<?php pd_render_tool_widget( $pdl_result, $pdl_error, $pdl_url ); ?>
			</div>

		</div>
	</section>

	<!-- =========================================================
	     2. EDITABLE WORDPRESS BLOCK CONTENT
	     Managed 100% via standard WordPress block editor
	========================================================= -->
	<section class="pd-section pd-content-section" aria-label="Shared Pins Guide and Details">
		<div class="pd-container">
			<div class="pd-content">
				<?php
				if ( have_posts() ) :
					while ( have_posts() ) :
						the_post();
						the_content();
					endwhile;
				endif;
				?>
			</div>
		</div>
	</section>

	<!-- =========================================================
	     3. BOTTOM CONVERSION CTA
	========================================================= -->
	<section class="pd-cta" aria-label="Call to Action">
		<div class="pd-container">
			<h2>Ready to Save the Whole Share?</h2>
			<a href="#pdl-tool" id="pd-cta-scroll" class="pd-btn-primary">
				<?php pd_icon( 'arrow-up' ); ?>
				<span>Back to Downloader</span>
			</a>
		</div>
	</section>

</main>

<?php
// Output interactive JavaScript enhancements
pd_output_scripts();

/* =========================================================
   SCHEMA — SoftwareApplication (generic, same on every tool
   page) + HowTo (this page's 3 steps) + FAQPage (built live
   from this page's own "Details" blocks, not hardcoded) +
   BreadcrumbList. Reuses pd_collect_details_pairs() from the
   core engine, which already works on any block array — no
   core-file changes needed for this.
========================================================= */
$pd_page_id   = get_queried_object_id();
$pd_page_post = $pd_page_id ? get_post( $pd_page_id ) : null;

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
	'description'         => 'Download every pin from a Pinterest multi-pin share link with PinsDownload. Free, no watermark, no login needed.',
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_software ); ?></script>
<?php
$pd_schema_howto = array(
	'@context'         => 'https://schema.org',
	'@type'            => 'HowTo',
	'name'             => 'How to Download From a Pinterest Multi-Pin Share Link',
	'step'             => array(
		array(
			'@type' => 'HowToStep',
			'text'  => 'Open the shared link someone sent you.',
		),
		array(
			'@type' => 'HowToStep',
			'text'  => "Copy the link from your browser's address bar, or use the share icon if you're in the app.",
		),
		array(
			'@type' => 'HowToStep',
			'text'  => 'Paste the link above and tap Download.',
		),
	),
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_howto ); ?></script>
<?php
if ( $pd_page_post && function_exists( 'pd_collect_details_pairs' ) ) {
	$pd_faq_pairs = array();
	pd_collect_details_pairs( parse_blocks( $pd_page_post->post_content ), $pd_faq_pairs );
	if ( ! empty( $pd_faq_pairs ) ) {
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
	}
}

$pd_schema_breadcrumb = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Home',
			'item'     => home_url( '/' ),
		),
		array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => 'Pinterest Shared Pins Downloader',
			'item'     => $pd_page_id ? get_permalink( $pd_page_id ) : home_url( '/pinterest-shared-pins-downloader/' ),
		),
	),
);
?>
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_breadcrumb ); ?></script>
<?php
get_footer();
