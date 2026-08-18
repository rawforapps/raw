<?php
/**
 * Template Name: PinsDownload Tool Landing
 *
 * Reusable page template for any additional PinsDownload tool page
 * (Image Downloader, GIF Downloader, Story Downloader, Board
 * Downloader, ...). Drop this file into the same active theme folder
 * as front-page.php, then for any new Page: Page Attributes ->
 * Template -> "PinsDownload Tool Landing".
 *
 * Same shell as front-page.php, same shared core (icons, CSS, the
 * downloader tool, the editable-zone system) from
 * embed/pinsdownload-editable-sections.php — install that ONCE,
 * it's shared by every page using either template, not copied per
 * page. See that file's header comment for the full picture.
 *
 * What's dynamic per page, so one template file works for all of
 * them without editing PHP per tool:
 *   - The Hero H1 is this page's own title (Pages -> title field) —
 *     name the page "Pinterest Image Downloader", "Pinterest GIF
 *     Downloader", etc. and the H1 follows automatically.
 *   - The Hero subtitle is this page's Excerpt if you set one
 *     (Page editor -> Page panel -> Excerpt; enable it from the
 *     "..." menu -> Preferences -> Panels if you don't see it), else
 *     a generic fallback line.
 *   - Every other section's content comes from this page's own
 *     editable zones (Feature Strip, Quick Steps, How to Use, ...) —
 *     independent from the homepage's and from every other tool
 *     page's. First time you open a brand-new page using this
 *     template, it's auto-filled with a lightweight skeleton (marker
 *     headings + a one-line hint each) — no fabricated tool-specific
 *     copy, since this template doesn't know what tool it's about.
 *
 * Technical foundation for each page you create with this template
 * (set outside this file via your SEO plugin, same reasoning as
 * front-page.php): its own title tag, meta description, and — if the
 * tool it's about is genuinely different from the homepage's —
 * BreadcrumbList schema, which belongs on inner pages like this one,
 * not the homepage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Graceful fallbacks if pinsdownload-editable-sections.php isn't
   active — sections show a notice to editors instead of fataling. */
if ( ! function_exists( 'pd_process_tool_submission' ) ) {
	function pd_process_tool_submission() {
		return array( null, '', '' );
	}
}
if ( ! function_exists( 'pd_render_tool_widget' ) ) {
	function pd_render_tool_widget( $result, $error, $submitted_url = '' ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">Core snippet not active — activate embed/pinsdownload-editable-sections.php to make the downloader tool work.</p>';
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
if ( ! function_exists( 'pd_render_content_zone' ) ) {
	function pd_render_content_zone( $slug ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">Core snippet not active — activate embed/pinsdownload-editable-sections.php to make "' . esc_html( $slug ) . '" editable.</p>';
		}
	}
}
if ( ! function_exists( 'pd_render_faq_zone' ) ) {
	function pd_render_faq_zone( $slug = 'pd-faq' ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">Core snippet not active — activate embed/pinsdownload-editable-sections.php to manage the FAQ.</p>';
		}
	}
}
if ( ! function_exists( 'pd_get_faq_pairs' ) ) {
	function pd_get_faq_pairs( $slug = 'pd-faq' ) {
		return array();
	}
}

list( $pdl_result, $pdl_error, $pdl_submitted_url ) = pd_process_tool_submission();

$pd_page_title    = get_the_title();
$pd_page_subtitle = has_excerpt() ? get_the_excerpt() : 'Download from Pinterest in one click. Free, fast, and no account needed.';

get_header();
pd_output_styles();
?>

<div class="pd-page" id="pd-page">

	<!-- =====================================================
	     1. HERO + TOOL — pintsave.net downloader embedded live.
	     H1/subtitle come from this page's own title/excerpt.
	     ===================================================== -->
	<section class="pd-hero-section">
		<div class="pd-hero-glow" aria-hidden="true"></div>
		<span class="pd-hero-shape pd-hero-shape--1" aria-hidden="true"></span>
		<span class="pd-hero-shape pd-hero-shape--2" aria-hidden="true"></span>
		<div class="pd-container pd-hero">
			<p class="pd-hero__eyebrow">Pinterest Downloader</p>
			<h1 class="pd-hero__title"><?php echo esc_html( $pd_page_title ); ?></h1>
			<p class="pd-hero__subtitle"><?php echo esc_html( $pd_page_subtitle ); ?></p>

			<div class="pd-tool-card" id="pdl-tool">
				<?php pd_render_tool_widget( $pdl_result, $pdl_error, $pdl_submitted_url ); ?>
			</div>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Feature Strip"
	     ===================================================== -->
	<div class="pd-strip">
		<div class="pd-strip-row">
			<?php pd_render_content_zone( 'pd-feature-strip' ); ?>
		</div>
	</div>

	<!-- =====================================================
	     GUTENBERG ZONE — "Quick Steps"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>How to Use This Tool</h2>
			</div>
			<?php pd_render_content_zone( 'pd-quick-steps' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "How to Use"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>How to Use</h2>
			</div>
			<?php pd_render_content_zone( 'pd-how-to-use' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Images"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>See It in Action</h2>
			</div>
			<?php pd_render_content_zone( 'pd-images' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Works and Doesnt"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>What This Tool Can and Can't Download</h2>
			</div>
			<?php pd_render_content_zone( 'pd-works-doesnt' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Features"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Why People Use This Tool</h2>
			</div>
			<?php pd_render_content_zone( 'pd-features' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Content Types"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>What Else You Can Download</h2>
			</div>
			<?php pd_render_content_zone( 'pd-content-types' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Explanations"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<?php pd_render_content_zone( 'pd-explanations' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Comparison"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>How This Compares</h2>
			</div>
			<?php pd_render_content_zone( 'pd-comparison' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Devices"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Works on Every Device</h2>
			</div>
			<?php pd_render_content_zone( 'pd-devices' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Safety"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-info-card">
				<div class="pd-eyebrow-icon"><?php pd_icon( 'shield' ); ?></div>
				<h2>Is This Safe to Use?</h2>
				<?php pd_render_content_zone( 'pd-safety' ); ?>
			</div>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Trust Badges"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Check Our Current Reputation</h2>
			</div>
			<?php pd_render_content_zone( 'pd-trust-badges' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Testimonials"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>What Users Say</h2>
			</div>
			<?php pd_render_content_zone( 'pd-testimonials' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Whats New"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head" style="margin-bottom:36px;">
				<div class="pd-eyebrow-icon" style="margin-left:auto;margin-right:auto;"><?php pd_icon( 'clock' ); ?></div>
				<h2>What's New</h2>
			</div>
			<?php pd_render_content_zone( 'pd-whats-new' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Guides"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Guides &amp; Tips</h2>
			</div>
			<?php pd_render_content_zone( 'pd-guides' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "FAQ"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Frequently Asked Questions</h2>
			</div>
			<?php pd_render_faq_zone( 'pd-faq' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Quick Answers"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Quick Answers</h2>
			</div>
			<?php pd_render_content_zone( 'pd-quick-answers' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     GUTENBERG ZONE — "Other Tools"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Other Tools</h2>
			</div>
			<?php pd_render_content_zone( 'pd-other-tools' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     FINAL CTA — heading/button stay fixed; the supporting
	     line is the "Final CTA" GUTENBERG ZONE.
	     ===================================================== -->
	<section class="pd-cta pd-band--soft">
		<div class="pd-container pd-reveal">
			<h2>Ready to Download?</h2>
			<?php pd_render_content_zone( 'pd-final-cta' ); ?>
			<a href="#pdl-tool" class="pd-btn-primary pd-scroll-top" id="pd-cta-scroll">
				<?php pd_icon( 'arrow' ); ?> Back to the Downloader
			</a>
		</div>
	</section>

</div>

<?php pd_output_scripts(); ?>

<?php
/* =========================================================
   SCHEMA — SoftwareApplication (name follows this page's title)
   and FAQPage (from this page's own FAQ zone). No HowTo block by
   default since the Quick Steps zone's content is fully editable
   per page, unlike the homepage's fixed 3-step copy; no ratings/
   reviews since there are no real testimonials yet.
========================================================= */
$pd_schema_software = array(
	'@context'            => 'https://schema.org',
	'@type'               => 'SoftwareApplication',
	'name'                => $pd_page_title,
	'applicationCategory' => 'MultimediaApplication',
	'operatingSystem'     => 'Any (web-based)',
	'url'                 => get_permalink(),
	'offers'              => array(
		'@type'         => 'Offer',
		'price'         => '0',
		'priceCurrency' => 'USD',
	),
	'description'         => $pd_page_subtitle,
);

$pd_schema_faq_items = array();
foreach ( pd_get_faq_pairs( 'pd-faq' ) as $pd_pair ) {
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
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_software ); ?></script>
<?php if ( ! empty( $pd_schema_faq_items ) ) : ?>
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_faq ); ?></script>
<?php endif; ?>

<?php
get_footer();
