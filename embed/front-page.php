<?php
/**
 * PinsDownload — standalone WordPress homepage template.
 *
 * Drop this file into any active theme as front-page.php (e.g.
 * /wp-content/themes/YOUR-THEME/front-page.php), then set
 * Settings -> Reading -> "Your homepage displays" -> A static page,
 * or simply activate it as the theme's own front-page.php — WordPress
 * uses front-page.php automatically once "A static page" (with no
 * page picked) or a matching setup is in place. No page builder, no
 * separate widget/shortcode install: the downloader tool itself is
 * embedded directly in the hero below (same pintsave.net-backed logic
 * as embed/pinsdownload-backend-wpcode.php), and every homepage
 * section from Homepage_Content_PinsDownload.md is reproduced here
 * word for word. This file only owns markup/CSS/JS/layout — no
 * homepage copy was added, removed, or reworded.
 *
 * Technical foundation (set outside this file, WordPress already
 * owns <title>/<meta> — via your SEO plugin or the theme's own
 * header.php):
 *   Title tag:       Pinterest Video Downloader – Save Videos, Images, GIFs & Stories Free | PinsDownload
 *   Meta description: Download Pinterest videos, images, and GIFs in HD for free with PinsDownload. No login, no watermark, no app needed.
 *   Schema on this page: SoftwareApplication, HowTo, FAQPage (all three are output inline near the
 *   bottom of this file as JSON-LD). BreadcrumbList belongs on inner pages, not the homepage.
 *   URL: homepage stays at the site root, not a subfolder.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================
   TOOL LOGIC — same pintsave.net-backed resolver as
   embed/pinsdownload-backend-wpcode.php, embedded directly so the
   hero renders a real, working downloader instead of a placeholder.
   Runs before get_header() so nothing is echoed before it.
========================================================= */

$pdl_result = null;
$pdl_error  = '';

if (
	isset( $_SERVER['REQUEST_METHOD'] ) &&
	$_SERVER['REQUEST_METHOD'] === 'POST' &&
	isset( $_POST['pdl_action'] ) &&
	$_POST['pdl_action'] === 'fetch'
) {

	$pdl_url = '';

	if ( isset( $_POST['pinterest_url'] ) ) {
		$pdl_url = trim( wp_unslash( $_POST['pinterest_url'] ) );
	}

	if ( $pdl_url === '' ) {

		$pdl_error = 'Please enter a Pinterest URL.';

	} elseif ( ! filter_var( $pdl_url, FILTER_VALIDATE_URL ) ) {

		$pdl_error = 'Please enter a valid Pinterest URL.';

	} else {

		$pdl_host = wp_parse_url( $pdl_url, PHP_URL_HOST );

		if ( ! $pdl_host ) {

			$pdl_error = 'Invalid Pinterest URL.';

		} else {

			$pdl_host = strtolower( $pdl_host );
			$pdl_host = preg_replace( '/^www\./', '', $pdl_host );

			$pdl_allowed_hosts = array( 'pinterest.com', 'pin.it' );

			if ( ! in_array( $pdl_host, $pdl_allowed_hosts, true ) ) {

				$pdl_error = 'Please enter a Pinterest URL.';

			} else {

				$pdl_api_response = wp_remote_post(
					'https://pintsave.net/api/fetch-media',
					array(
						'timeout' => 45,
						'headers' => array(
							'Accept'           => '*/*',
							'X-Requested-With' => 'XMLHttpRequest',
						),
						'body'    => array(
							'url' => $pdl_url,
						),
					)
				);

				if ( is_wp_error( $pdl_api_response ) ) {

					$pdl_error = 'Unable to connect to the media service. Please try again.';

				} else {

					$pdl_status = wp_remote_retrieve_response_code( $pdl_api_response );
					$pdl_body   = wp_remote_retrieve_body( $pdl_api_response );

					if ( $pdl_status !== 200 ) {

						$pdl_error = 'The media service returned an error. Please try again.';

					} else {

						$pdl_result = json_decode( $pdl_body, true );

						if (
							! is_array( $pdl_result ) ||
							empty( $pdl_result['media'] ) ||
							! is_array( $pdl_result['media'] )
						) {
							$pdl_error  = 'No downloadable media was found for this Pinterest URL.';
							$pdl_result = null;
						}
					}
				}
			}
		}
	}
}

/* =========================================================
   ICON HELPER — inline SVG only, no external icon library.
   A small shared set (~2 dozen), reused across sections.
========================================================= */

if ( ! function_exists( 'pd_icon' ) ) {
	function pd_icon( $name ) {
		$icons = array(
			'check'           => '<circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>',
			'cross'           => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
			'spark'           => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>',
			'shield'          => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
			'globe'           => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 2.5 14.3 0 18M12 3C9.5 5.7 9.5 17.3 12 21"/>',
			'phone'           => '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/>',
			'lock'            => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
			'tag'             => '<path d="M12.6 3.5H5.5v7.1c0 .5.2 1 .6 1.4l8.6 8.6c.8.8 2 .8 2.8 0l4.2-4.2c.8-.8.8-2 0-2.8L13.1 4.1c-.4-.4-.9-.6-1.4-.6z"/><circle cx="9" cy="9" r="1.4"/>',
			'slash'           => '<circle cx="12" cy="12" r="9"/><path d="M6.5 6.5l11 11"/>',
			'mega'            => '<path d="M3 10v4h3l6 4V6l-6 4H3z"/><path d="M16 9.5a4 4 0 0 1 0 5M19 7a7.5 7.5 0 0 1 0 10"/>',
			'image'           => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.6"/><path d="M21 16l-5.5-5.5L6 19"/>',
			'gif'             => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 9.5v5M11 9.5v5M11 12h2.5M16 9.5c-1.4 0-2.3 1-2.3 2.5s.9 2.5 2.3 2.5c.7 0 1.3-.2 1.6-.5v-1.7H16"/>',
			'film'            => '<rect x="3" y="4.5" width="18" height="15" rx="2"/><path d="M8 4.5v15M16 4.5v15M3 9.5h5M16 9.5h5M3 15h5M16 15h5"/>',
			'layers'          => '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5M3 8l9 5 9-5"/>',
			'grid'            => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.4"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.4"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.4"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.4"/>',
			'user'            => '<circle cx="12" cy="8" r="3.6"/><path d="M4.5 20c1.4-4 4-6 7.5-6s6.1 2 7.5 6"/>',
			'bulb'            => '<path d="M9 18h6M9.5 21h5M8 14.5A4.9 4.9 0 1 1 16 14.5c-.8 1-1.5 1.8-1.5 3H9.5c0-1.2-.7-2-1.5-3z"/>',
			'message'         => '<path d="M4 5.5h16v11H9l-4 3.5v-3.5H4v-11z"/>',
			'link'            => '<path d="M9.5 14.5l5-5"/><path d="M13 6.5l1.4-1.4a3.5 3.5 0 0 1 5 5L18 11.5M11 17.5l-1.4 1.4a3.5 3.5 0 0 1-5-5L6 12.5"/>',
			'clock'           => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
			'chevron'         => '<path d="M6 9l6 6 6-6"/>',
			'arrow'           => '<path d="M4 12h15M13 6l6 6-6 6"/>',
			'quote'           => '<path d="M7 8.5c-2 .6-3 2.2-3 4.4 0 2 1.3 3.6 3.3 3.6S10.6 15 10.6 13 9.4 9.5 7.5 9.5c0-.5.6-1 1.3-1zM16 8.5c-2 .6-3 2.2-3 4.4 0 2 1.3 3.6 3.3 3.6s3.3-1.5 3.3-3.5-1.2-3.5-3.1-3.5c0-.5.6-1 1.3-1z"/>',
			'device-phone'    => '<rect x="8" y="2.5" width="8" height="19" rx="1.8"/><path d="M11 18.3h2"/>',
			'device-monitor'  => '<rect x="3" y="4.5" width="18" height="12" rx="1.8"/><path d="M9 20h6M12 16.5V20"/>',
			'device-terminal' => '<rect x="3" y="4.5" width="18" height="15" rx="1.8"/><path d="M7 9.5l3 2.5-3 2.5M13 15h4"/>',
			'search'          => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.5-4.5"/>',
			'copy'            => '<rect x="8.5" y="8.5" width="11" height="11" rx="2"/><path d="M15 8.5V6.5a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h2"/>',
			'download'        => '<path d="M12 3v12M7.5 10.5L12 15l4.5-4.5"/><path d="M5 19h14"/>',
		);

		if ( ! isset( $icons[ $name ] ) ) {
			return;
		}

		echo '<svg class="pd-i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>'; // phpcs:ignore -- static, hand-written SVG, no user input.
	}
}


/*
 * FAQ, "How to Use", "Images", "Explanations", and "Features" all
 * come from the Homepage Sections custom post type registered in
 * embed/pinsdownload-editable-sections.php (must be active alongside
 * this file — as its own WPCode PHP snippet, or pasted into an
 * mu-plugin). It provides pd_render_content_zone(), pd_render_faq_zone(),
 * and pd_get_faq_pairs() used throughout this template. Everything
 * else on this page (hero+tool, strip, works/doesn't, comparison,
 * devices, safety, trust badges, testimonials, timeline, guides,
 * quick answers, other tools, final CTA) is intentionally hard-coded
 * here, not editable from wp-admin — see that file's header comment
 * for why.
 */
if ( ! function_exists( 'pd_render_content_zone' ) ) {
	function pd_render_content_zone( $slug ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">Editable Sections plugin/snippet not active — activate embed/pinsdownload-editable-sections.php to make "' . esc_html( $slug ) . '" editable.</p>';
		}
	}
}
if ( ! function_exists( 'pd_render_faq_zone' ) ) {
	function pd_render_faq_zone( $slug = 'pd-faq' ) {
		if ( current_user_can( 'edit_posts' ) ) {
			echo '<p class="pd-zone-missing">Editable Sections plugin/snippet not active — activate embed/pinsdownload-editable-sections.php to manage the FAQ.</p>';
		}
	}
}
if ( ! function_exists( 'pd_get_faq_pairs' ) ) {
	function pd_get_faq_pairs( $slug = 'pd-faq' ) {
		return array();
	}
}

get_header();
?>

<style>
/* =========================================================
   PinsDownload homepage — scoped design system (.pd- prefix)
========================================================= */
#pd-page, #pd-page *, #pd-page *::before, #pd-page *::after { box-sizing: border-box; }

#pd-page {
	--pd-red: #E60023;
	--pd-red-dark: #c90020;
	--pd-dark: #171717;
	--pd-text-secondary: #666666;
	--pd-text-muted: #777777;
	--pd-bg: #FFFFFF;
	--pd-bg-soft: #FAF7F7;
	--pd-border: #EAEAEA;
	--pd-red-tint: #FFF1F3;
	--pd-radius-lg: 24px;
	--pd-radius-md: 18px;
	--pd-shadow: 0 10px 40px rgba(0,0,0,.06);
	--pd-shadow-hover: 0 16px 46px rgba(0,0,0,.10);
	--pd-max: 1200px;

	font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
	color: var(--pd-dark);
	background: var(--pd-bg);
	line-height: 1.7;
	-webkit-font-smoothing: antialiased;
}

#pd-page h1, #pd-page h2, #pd-page h3 { font-weight: 800; line-height: 1.2; letter-spacing: -0.01em; margin: 0 0 16px; color: var(--pd-dark); }
#pd-page p { margin: 0 0 16px; color: var(--pd-text-secondary); font-size: 16px; }
#pd-page ul, #pd-page ol { margin: 0; padding: 0; list-style: none; }
#pd-page a { color: var(--pd-red); }
#pd-page svg.pd-i { width: 20px; height: 20px; flex-shrink: 0; }
#pd-page :focus-visible { outline: 2px solid var(--pd-red); outline-offset: 3px; }

.pd-container { max-width: var(--pd-max); margin: 0 auto; padding: 0 18px; }
@media (min-width: 640px) { .pd-container { padding: 0 32px; } }

.pd-section { padding: clamp(55px, 8vw, 110px) 0; }
.pd-band--soft { background: var(--pd-bg-soft); }
.pd-eyebrow-icon { width: 46px; height: 46px; display: flex; align-items: center; justify-content: center; border-radius: 14px; background: var(--pd-red-tint); color: var(--pd-red); margin-bottom: 18px; }
.pd-eyebrow-icon svg { width: 22px; height: 22px; }

/* Scroll reveal */
.pd-reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s ease, transform .6s ease; }
.pd-reveal.pd-in-view { opacity: 1; transform: none; }
@media (prefers-reduced-motion: reduce) {
	.pd-reveal { opacity: 1; transform: none; transition: none; }
}

/* ---------- Hero + tool ---------- */
.pd-hero-section { position: relative; overflow: hidden; padding: clamp(56px, 9vw, 116px) 0 clamp(48px, 7vw, 84px); background: linear-gradient(180deg, #ffffff 0%, var(--pd-bg-soft) 100%); }
.pd-hero-glow { position: absolute; inset: 0; pointer-events: none; background: radial-gradient(620px 420px at 50% 10%, rgba(230,0,35,.10), transparent 70%); }
.pd-hero-shape { position: absolute; border-radius: 50%; filter: blur(2px); opacity: .5; pointer-events: none; }
.pd-hero-shape--1 { width: 90px; height: 90px; border: 2px solid var(--pd-red-tint); top: 14%; left: 6%; }
.pd-hero-shape--2 { width: 46px; height: 46px; background: var(--pd-red-tint); top: 60%; right: 8%; }
.pd-hero { position: relative; text-align: center; }
.pd-hero__eyebrow { text-transform: uppercase; letter-spacing: .16em; font-size: 12.5px; font-weight: 700; color: var(--pd-red); margin: 0 0 18px; }
.pd-hero__title { font-size: clamp(40px, 6vw, 72px); text-align: center; margin-bottom: 20px; }
.pd-hero__subtitle { max-width: 680px; margin: 0 auto 42px; font-size: clamp(16px, 1.6vw, 19px); text-align: center; }

.pd-tool-card { position: relative; max-width: 800px; margin: 0 auto; background: #fff; border: 1px solid var(--pd-border); border-radius: var(--pd-radius-lg); padding: clamp(20px, 4vw, 38px); box-shadow: 0 24px 64px rgba(230,0,35,.12), var(--pd-shadow); text-align: left; }

.pdl-form { display: flex; width: 100%; gap: 10px; margin: 0; }
.pdl-input { flex: 1; width: 100%; min-width: 0; height: 54px; padding: 0 16px; border: 1px solid #d9d9d9; border-radius: 12px; background: #fff; color: var(--pd-dark); font-size: 16px; outline: none; transition: border-color .2s ease, box-shadow .2s ease; }
.pdl-input:focus { border-color: var(--pd-red); box-shadow: 0 0 0 3px rgba(230,0,35,.08); }
.pdl-button { height: 54px; padding: 0 28px; border: 0; border-radius: 12px; background: var(--pd-red); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: background .2s ease, transform .2s ease; }
.pdl-button:hover { background: var(--pd-red-dark); transform: translateY(-1px); }
.pdl-button:disabled { opacity: .7; cursor: wait; transform: none; }
.pdl-loading { display: none; margin-top: 18px; text-align: center; color: var(--pd-text-secondary); font-size: 14px; }
.pdl-spinner { display: inline-block; width: 18px; height: 18px; margin-right: 7px; vertical-align: middle; border: 3px solid #dddddd; border-top-color: var(--pd-red); border-radius: 50%; animation: pdl-spin .8s linear infinite; }
@keyframes pdl-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .pdl-spinner { animation-duration: 1.6s; } }
.pdl-error { margin-top: 20px; padding: 14px 16px; border: 1px solid #ffd0d0; border-radius: 12px; background: #fff0f0; color: #b00020; font-size: 14px; line-height: 1.5; }
.pdl-results { margin-top: 26px; }
.pdl-result { margin-bottom: 20px; padding: 18px; border: 1px solid var(--pd-border); border-radius: 16px; background: var(--pd-bg-soft); }
.pdl-media { display: block; width: 100%; max-height: 560px; margin: 0 auto 16px; border-radius: 12px; background: #111; object-fit: contain; }
.pdl-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
.pdl-info-item { min-width: 0; padding: 11px; border: 1px solid var(--pd-border); border-radius: 10px; background: #fff; }
.pdl-info-label { display: block; margin-bottom: 4px; color: var(--pd-text-muted); font-size: 12px; }
.pdl-info-value { display: block; color: var(--pd-dark); font-size: 14px; font-weight: 600; word-break: break-word; }
.pdl-download { display: block; width: 100%; padding: 14px 18px; border-radius: 12px; background: var(--pd-red); color: #fff !important; text-align: center; text-decoration: none !important; font-size: 15px; font-weight: 700; }
.pdl-download:hover { background: var(--pd-red-dark); }
.pdl-meta { margin-top: 18px; padding: 15px; border-radius: 12px; background: var(--pd-bg-soft); color: var(--pd-text-secondary); font-size: 14px; }
.pdl-meta strong { color: var(--pd-dark); }

/* ---------- Feature strip ---------- */
.pd-strip { background: var(--pd-bg-soft); border-top: 1px solid var(--pd-border); border-bottom: 1px solid var(--pd-border); }
.pd-strip-row { max-width: var(--pd-max); margin: 0 auto; padding: 22px 18px; display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; }
.pd-strip .pd-gutenberg-zone { max-width: none; }
.pd-strip .pd-gutenberg-zone p { margin: 0; text-align: center; font-weight: 600; font-size: 14px; color: var(--pd-dark); }

/* ---------- Gutenberg-editable zones ----------
   Targets core block output (.wp-block-*) so anything typed or
   uploaded in wp-admin -> Homepage Sections picks up the same
   design tokens automatically, without needing exact markup. */
.pd-gutenberg-zone { max-width: 900px; margin: 0 auto; }
.pd-gutenberg-zone > *:first-child { margin-top: 0; }
.pd-gutenberg-zone h2, .pd-gutenberg-zone h3, .pd-gutenberg-zone h4,
.pd-gutenberg-zone .wp-block-heading { color: var(--pd-dark); font-weight: 800; line-height: 1.25; margin: 34px 0 12px; }
.pd-gutenberg-zone h2, .pd-gutenberg-zone .wp-block-heading:is(h2) { font-size: clamp(24px, 3vw, 32px); }
.pd-gutenberg-zone h3, .pd-gutenberg-zone .wp-block-heading:is(h3) { font-size: clamp(19px, 2.4vw, 23px); }
.pd-gutenberg-zone p, .pd-gutenberg-zone li { color: var(--pd-text-secondary); font-size: 16px; line-height: 1.75; }
.pd-gutenberg-zone ol, .pd-gutenberg-zone ul { list-style: revert; padding-left: 22px; margin: 0 0 18px; }
.pd-gutenberg-zone ol li, .pd-gutenberg-zone ul li { padding: 3px 0; }
.pd-gutenberg-zone a { color: var(--pd-red); text-decoration: underline; text-underline-offset: 2px; }
.pd-gutenberg-zone strong { color: var(--pd-dark); }
.pd-gutenberg-zone img { border-radius: var(--pd-radius-md); box-shadow: var(--pd-shadow); }
.pd-gutenberg-zone .wp-block-image, .pd-gutenberg-zone .wp-block-gallery { margin: 24px 0; }
.pd-gutenberg-zone figcaption { text-align: center; font-size: 13px; color: var(--pd-text-muted); margin-top: 8px; }
.pd-gutenberg-zone em { color: var(--pd-text-muted); }

/* "Features" zone: style core Columns output as the same card look
   the rest of the design uses (.pd-feature-card equivalent). */
.pd-gutenberg-zone .wp-block-columns { max-width: none; gap: 22px; margin-bottom: 22px; }
.pd-gutenberg-zone .wp-block-column {
	background: #fff; border: 1px solid var(--pd-border); border-radius: var(--pd-radius-md);
	padding: 26px; transition: transform .25s ease, box-shadow .25s ease;
}
.pd-gutenberg-zone .wp-block-column:hover { transform: translateY(-4px); box-shadow: var(--pd-shadow-hover); }
.pd-gutenberg-zone .wp-block-column .wp-block-heading, .pd-gutenberg-zone .wp-block-column h3 { margin-top: 0; font-size: 16.5px; }
.pd-gutenberg-zone .wp-block-column p:last-child { margin-bottom: 0; font-size: 14px; }
@media (max-width: 700px) { .pd-gutenberg-zone .wp-block-columns { flex-wrap: wrap; } }

/* Comparison zone: style a core Table block like the old bespoke
   comparison table, with a horizontal-scroll wrapper on mobile. */
.pd-gutenberg-zone .wp-block-table { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: var(--pd-radius-md); border: 1px solid var(--pd-border); background: #fff; margin: 0 0 18px; }
.pd-gutenberg-zone .wp-block-table table { width: 100%; border-collapse: collapse; min-width: 560px; margin: 0; }
.pd-gutenberg-zone .wp-block-table th, .pd-gutenberg-zone .wp-block-table td { padding: 15px 18px; text-align: left; font-size: 14.5px; border-bottom: 1px solid var(--pd-border); white-space: nowrap; }
.pd-gutenberg-zone .wp-block-table thead th { font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: var(--pd-text-muted); }
.pd-gutenberg-zone .wp-block-table tbody tr:last-child td { border-bottom: 0; }

.pd-zone-missing { max-width: 900px; margin: 0 auto; padding: 16px 20px; border: 1px dashed var(--pd-border); border-radius: 12px; background: var(--pd-bg-soft); color: var(--pd-text-muted); font-size: 14px; text-align: center; }

/* ---------- Section heading (non-split sections) ---------- */
.pd-section-head { text-align: center; max-width: 680px; margin: 0 auto 44px; }
.pd-section-head h2 { font-size: clamp(28px, 3.6vw, 44px); }

/* ---------- Info card (safety / legal) ---------- */
.pd-info-card { max-width: 800px; margin: 0 auto; text-align: center; background: #fff; border: 1px solid var(--pd-border); border-radius: var(--pd-radius-lg); padding: 42px; box-shadow: var(--pd-shadow); }
.pd-info-card .pd-eyebrow-icon { margin: 0 auto 18px; }
.pd-info-card .pd-gutenberg-zone { text-align: left; }

/* ---------- FAQ accordion ---------- */
.pd-faq-list { max-width: 820px; margin: 0 auto; border-top: 1px solid var(--pd-border); }
.pd-faq-item { border-bottom: 1px solid var(--pd-border); }
.pd-faq-q { width: 100%; display: flex; justify-content: space-between; align-items: center; gap: 16px; background: none; border: 0; padding: 21px 4px; font-size: 15.5px; font-weight: 600; color: var(--pd-dark); cursor: pointer; text-align: left; font-family: inherit; }
.pd-faq-q svg { color: var(--pd-red); transition: transform .25s ease; }
.pd-faq-item[data-open="true"] .pd-faq-q svg { transform: rotate(180deg); }
.pd-faq-a { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .25s ease; }
.pd-faq-a > div { overflow: hidden; }
.pd-faq-item[data-open="true"] .pd-faq-a { grid-template-rows: 1fr; }
.pd-faq-a p { padding: 0 4px 21px; margin: 0; font-size: 14.5px; }
@media (prefers-reduced-motion: reduce) { .pd-faq-a { transition: none; } }

/* ---------- Final CTA ---------- */
.pd-cta { text-align: center; padding: clamp(60px, 8vw, 100px) 0; }
.pd-cta h2 { font-size: clamp(28px, 3.6vw, 42px); margin-bottom: 12px; }
.pd-cta p { max-width: 480px; margin: 0 auto 30px; }
.pd-btn-primary { display: inline-flex; align-items: center; gap: 10px; background: var(--pd-red); color: #fff !important; text-decoration: none !important; font-weight: 700; font-size: 16px; padding: 16px 32px; border-radius: 12px; transition: background .2s ease, transform .2s ease; min-height: 44px; }
.pd-btn-primary:hover { background: var(--pd-red-dark); transform: translateY(-2px); }

@media (max-width: 480px) {
	.pdl-form { flex-direction: column; }
	.pdl-info { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="pd-page" id="pd-page">

	<!-- =====================================================
	     1. HERO + TOOL — pintsave.net downloader embedded live.
	     ===================================================== -->
	<section class="pd-hero-section">
		<div class="pd-hero-glow" aria-hidden="true"></div>
		<span class="pd-hero-shape pd-hero-shape--1" aria-hidden="true"></span>
		<span class="pd-hero-shape pd-hero-shape--2" aria-hidden="true"></span>
		<div class="pd-container pd-hero">
			<p class="pd-hero__eyebrow">Pinterest Downloader</p>
			<h1 class="pd-hero__title">Pinterest Video Downloader</h1>
			<p class="pd-hero__subtitle">Download Pinterest videos, images, GIFs, and stories in one click. Free, fast, and no account needed. Works as a full Pinterest video downloader online, no software to install.</p>

			<div class="pd-tool-card" id="pdl-tool">

				<form method="post" class="pdl-form" id="pdl-form">
					<input
						type="url"
						name="pinterest_url"
						class="pdl-input"
						placeholder="Paste Pinterest link here..."
						value="<?php echo isset( $pdl_url ) ? esc_attr( $pdl_url ) : ''; ?>"
						autocomplete="off"
						required
						aria-label="Pinterest URL"
					>
					<input type="hidden" name="pdl_action" value="fetch">
					<button type="submit" class="pdl-button">Download</button>
				</form>

				<div id="pdl-loading" class="pdl-loading">
					<span class="pdl-spinner"></span>
					Fetching Pinterest media...
				</div>

				<?php if ( $pdl_error !== '' ) : ?>
					<div class="pdl-error"><?php echo esc_html( $pdl_error ); ?></div>
				<?php endif; ?>

				<?php if ( is_array( $pdl_result ) && ! empty( $pdl_result['media'] ) && is_array( $pdl_result['media'] ) ) : ?>
					<div class="pdl-results">
						<?php foreach ( $pdl_result['media'] as $pdl_media ) : ?>
							<?php
							if ( ! is_array( $pdl_media ) || empty( $pdl_media['url'] ) ) {
								continue;
							}
							$pdl_media_url  = $pdl_media['url'];
							$pdl_media_type = ! empty( $pdl_media['type'] ) ? strtolower( $pdl_media['type'] ) : 'image';
							$pdl_width      = ! empty( $pdl_media['width'] ) ? $pdl_media['width'] : '';
							$pdl_height     = ! empty( $pdl_media['height'] ) ? $pdl_media['height'] : '';
							$pdl_quality    = ! empty( $pdl_media['quality'] ) ? $pdl_media['quality'] : '';
							$pdl_duration   = ! empty( $pdl_media['duration'] ) ? $pdl_media['duration'] : '';
							$pdl_thumbnail  = ! empty( $pdl_media['thumbnail'] ) ? $pdl_media['thumbnail'] : '';
							?>
							<div class="pdl-result">
								<?php if ( $pdl_media_type === 'video' ) : ?>
									<video class="pdl-media" controls playsinline preload="metadata" <?php if ( $pdl_thumbnail !== '' ) : ?>poster="<?php echo esc_url( $pdl_thumbnail ); ?>"<?php endif; ?>>
										<source src="<?php echo esc_url( $pdl_media_url ); ?>" type="video/mp4">
										Your browser does not support video playback.
									</video>
								<?php else : ?>
									<img class="pdl-media" src="<?php echo esc_url( $pdl_media_url ); ?>" alt="Pinterest image" loading="lazy">
								<?php endif; ?>

								<div class="pdl-info">
									<div class="pdl-info-item">
										<span class="pdl-info-label">Type</span>
										<span class="pdl-info-value"><?php echo esc_html( ucfirst( $pdl_media_type ) ); ?></span>
									</div>
									<?php if ( $pdl_quality !== '' ) : ?>
										<div class="pdl-info-item">
											<span class="pdl-info-label">Quality</span>
											<span class="pdl-info-value"><?php echo esc_html( $pdl_quality ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( $pdl_width !== '' && $pdl_height !== '' ) : ?>
										<div class="pdl-info-item">
											<span class="pdl-info-label">Resolution</span>
											<span class="pdl-info-value"><?php echo esc_html( $pdl_width . ' × ' . $pdl_height ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( $pdl_duration !== '' ) : ?>
										<div class="pdl-info-item">
											<span class="pdl-info-label">Duration</span>
											<span class="pdl-info-value"><?php echo esc_html( $pdl_duration . ' seconds' ); ?></span>
										</div>
									<?php endif; ?>
								</div>

								<a class="pdl-download" href="<?php echo esc_url( $pdl_media_url ); ?>" download target="_blank" rel="noopener noreferrer">
									Download <?php echo $pdl_media_type === 'video' ? 'Video' : 'Image'; ?>
								</a>
							</div>
						<?php endforeach; ?>

						<?php $pdl_title = ! empty( $pdl_result['title'] ) ? $pdl_result['title'] : ''; ?>
						<?php if ( $pdl_title !== '' ) : ?>
							<div class="pdl-meta"><strong><?php echo esc_html( $pdl_title ); ?></strong></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</section>

	<!-- =====================================================
	     2. GUTENBERG ZONE — "Feature Strip"
	     ===================================================== -->
	<div class="pd-strip">
		<div class="pd-strip-row">
			<?php pd_render_content_zone( 'pd-feature-strip' ); ?>
		</div>
	</div>

	<!-- =====================================================
	     3. GUTENBERG ZONE — "Quick Steps"
	     Seeded as 3 Columns (Heading+Paragraph each), which reuses
	     the same card styling as the "Features" zone below.
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>How to Download a Pinterest Video</h2>
			</div>
			<?php pd_render_content_zone( 'pd-quick-steps' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     4-7. GUTENBERG ZONE — "How to Use"
	     Edit in wp-admin: Homepage Sections -> How to Use.
	     Seeded with the App / Computer / iPhone / Android guides;
	     add Image blocks for real screenshots, edit the wording,
	     add or remove guides freely. Design (band background,
	     spacing, container) stays fixed here; only the content
	     inside is from the CPT.
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
	     Edit in wp-admin: Homepage Sections -> Images. Empty by
	     default — add Image/Gallery blocks for product shots,
	     app screenshots, whatever's useful. Purely supplementary;
	     leave it empty and it just won't render anything extra.
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>See PinsDownload in Action</h2>
			</div>
			<?php pd_render_content_zone( 'pd-images' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     8. GUTENBERG ZONE — "Works and Doesnt"
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
	     9. GUTENBERG ZONE — "Features"
	     Edit in wp-admin: Homepage Sections -> Features. Seeded
	     with the "Why People Use This Tool" 6-item grid, built as
	     two rows of Columns blocks (Heading + Paragraph per
	     column). Add/remove columns or whole Columns blocks and
	     the card styling below still applies automatically.
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Why People Use This Tool</h2>
				<p>PinsDownload is built to be simple, honest, and free. Here's what that means in practice.</p>
			</div>
			<?php pd_render_content_zone( 'pd-features' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     10. GUTENBERG ZONE — "Content Types"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>What Else You Can Download From Pinterest</h2>
			</div>
			<?php pd_render_content_zone( 'pd-content-types' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     11-12 & 17. GUTENBERG ZONE — "Explanations"
	     Edit in wp-admin: Homepage Sections -> Explanations.
	     Seeded with "What Is a Pinterest Video Downloader?",
	     "What People Use It For", and "Is It Legal to Download
	     Pinterest Videos?" as Heading + Paragraph blocks. Expand,
	     reorder, or add new explanatory topics freely.
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<?php pd_render_content_zone( 'pd-explanations' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     13. GUTENBERG ZONE — "Comparison"
	     Seeded with a Table block. REVIEW REQUIRED: section marked
	     DUMMY in the source copy — verify every claim before publishing.
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>How This Compares to Other Downloaders</h2>
			</div>
			<?php pd_render_content_zone( 'pd-comparison' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     14. GUTENBERG ZONE — "Devices"
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
	     15. GUTENBERG ZONE — "Safety"
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
	     16. GUTENBERG ZONE — "Trust Badges"
	     Links already point at the real domain. They'll show "no
	     data yet" until pinsdownload.org has been live and crawled
	     for a few weeks — that's expected, not a bug.
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Check Our Current Reputation</h2>
			</div>
			<?php pd_render_content_zone( 'pd-trust-badges' ); ?>
		</div>
	</section>

	<!-- 17. Is It Legal to Download Pinterest Videos? — content now lives in the "Explanations" zone above. -->

	<!-- =====================================================
	     18. GUTENBERG ZONE — "Testimonials"
	     Seeded EMPTY on purpose — do not add fake names, ratings,
	     dates, or quotes here. Add real reviews as Paragraph/Quote
	     blocks once available.
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>What Users Say</h2>
			</div>
			<?php pd_render_content_zone( 'pd-testimonials' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     19. GUTENBERG ZONE — "Whats New"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head" style="margin-bottom:36px;">
				<div class="pd-eyebrow-icon" style="margin-left:auto;margin-right:auto;"><?php pd_icon( 'clock' ); ?></div>
				<h2>What's New</h2>
			</div>
			<?php pd_render_content_zone( 'pd-whats-new' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     20. GUTENBERG ZONE — "Guides"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Guides &amp; Tips</h2>
			</div>
			<?php pd_render_content_zone( 'pd-guides' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     21. GUTENBERG ZONE — "FAQ"
	     Edit in wp-admin: Homepage Sections -> FAQ. Add a Heading
	     block (the question) followed by a Paragraph block (the
	     answer) for each item, in order — that pairing is what
	     gets turned into the accordion below and into the
	     FAQPage schema at the bottom of this page, so the two
	     never drift out of sync. The accordion markup/CSS/JS
	     itself stays fixed here regardless of how many items you
	     add, remove, or reorder.
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Frequently Asked Questions</h2>
			</div>
			<?php pd_render_faq_zone( 'pd-faq' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     22. GUTENBERG ZONE — "Quick Answers"
	     ===================================================== -->
	<section class="pd-section pd-band--soft">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Quick Answers</h2>
			</div>
			<?php pd_render_content_zone( 'pd-quick-answers' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     23. GUTENBERG ZONE — "Other Tools"
	     ===================================================== -->
	<section class="pd-section">
		<div class="pd-container pd-reveal">
			<div class="pd-section-head">
				<h2>Other Tools</h2>
			</div>
			<?php pd_render_content_zone( 'pd-other-tools' ); ?>
		</div>
	</section>

	<!-- =====================================================
	     FINAL CTA — heading/button stay fixed (the button's
	     scroll-to-tool behavior needs to always exist); the
	     supporting line is the "Final CTA" GUTENBERG ZONE.
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

<script>
(function () {
	'use strict';

	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Tool form loading state */
	var form = document.getElementById('pdl-form');
	if (form) {
		form.addEventListener('submit', function () {
			var btn = form.querySelector('.pdl-button');
			var loading = document.getElementById('pdl-loading');
			if (btn) {
				btn.disabled = true;
				btn.innerText = 'Fetching...';
			}
			if (loading) {
				loading.style.display = 'block';
			}
		});
	}

	/* FAQ accordion */
	var faqButtons = document.querySelectorAll('.pd-faq-q');
	faqButtons.forEach(function (btn) {
		btn.addEventListener('click', function () {
			var item = btn.closest('.pd-faq-item');
			var isOpen = item.getAttribute('data-open') === 'true';
			item.setAttribute('data-open', isOpen ? 'false' : 'true');
			btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
		});
	});

	/* Smooth scroll to tool from final CTA */
	var ctaScroll = document.getElementById('pd-cta-scroll');
	if (ctaScroll) {
		ctaScroll.addEventListener('click', function (e) {
			var target = document.getElementById('pdl-tool');
			if (target) {
				e.preventDefault();
				target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
			}
		});
	}

	/* Scroll reveal */
	var reveals = document.querySelectorAll('.pd-reveal');
	if (reduceMotion || !('IntersectionObserver' in window)) {
		reveals.forEach(function (el) { el.classList.add('pd-in-view'); });
	} else {
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('pd-in-view');
					observer.unobserve(entry.target);
				}
			});
		}, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
		reveals.forEach(function (el) { observer.observe(el); });
	}
})();
</script>

<?php
/* =========================================================
   SCHEMA — SoftwareApplication, HowTo, FAQPage. No ratings/
   reviews are included since no real testimonials exist yet.
========================================================= */
$pd_schema_software = array(
	'@context'          => 'https://schema.org',
	'@type'             => 'SoftwareApplication',
	'name'              => 'PinsDownload',
	'applicationCategory' => 'MultimediaApplication',
	'operatingSystem'   => 'Any (web-based)',
	'url'               => home_url( '/' ),
	'offers'            => array(
		'@type'         => 'Offer',
		'price'         => '0',
		'priceCurrency' => 'USD',
	),
	'description'       => 'Download Pinterest videos, images, and GIFs in HD for free with PinsDownload. No login, no watermark, no app needed.',
);

$pd_schema_howto = array(
	'@context'    => 'https://schema.org',
	'@type'       => 'HowTo',
	'name'        => 'How to Download a Pinterest Video',
	'step'        => array(
		array(
			'@type' => 'HowToStep',
			'text'  => 'Open Pinterest and find the video you want.',
		),
		array(
			'@type' => 'HowToStep',
			'text'  => 'Tap the share icon and choose "Copy Link."',
		),
		array(
			'@type' => 'HowToStep',
			'text'  => 'Paste the link above and tap Download.',
		),
	),
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
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_howto ); ?></script>
<script type="application/ld+json"><?php echo wp_json_encode( $pd_schema_faq ); ?></script>

<?php
get_footer();
