<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * PinsDownload — Core (icons, styles, tool widget, editable zones)
 * WPCode PHP Snippet (or paste into an mu-plugin / regular plugin file)
 *
 * Install this ONCE, site-wide (Auto Insert -> Run Everywhere in
 * WPCode). It's the shared infrastructure behind every PinsDownload
 * page template — you do NOT need a separate copy of this file per
 * page. Two template files use it:
 *
 *   - front-page.php            the homepage
 *   - page-tool-landing.php     any additional tool page (Image
 *                                Downloader, GIF Downloader, ...) —
 *                                select "PinsDownload Tool Landing"
 *                                from Page Attributes -> Template on
 *                                any new Page you create
 *
 * Both are thin: they just lay out which sections exist and in what
 * order, then call functions this file defines for everything
 * reusable (icons, CSS, the downloader tool itself, and the editable
 * content zones below). Fix a bug or restyle something here once,
 * and every page using either template picks it up immediately — no
 * per-page file to hunt down and re-edit.
 *
 * =========================================================
 * EDITABLE CONTENT — no separate admin screen
 * =========================================================
 * Each page (the homepage, and every "PinsDownload Tool Landing"
 * page) is edited on ITS OWN, directly in the normal wp-admin block
 * editor — Pages -> that page -> Edit, the same place you already
 * edit any WordPress Page. Structure the content with Heading (H2)
 * markers, in any order, with whatever you want underneath each one:
 *
 *   ## Feature Strip
 *   ## Quick Steps
 *   ## How to Use
 *   ## Images
 *   ## Works and Doesnt
 *   ## Features
 *   ## Content Types
 *   ## Explanations
 *   ## Comparison
 *   ## Devices
 *   ## Safety
 *   ## Trust Badges
 *   ## Testimonials
 *   ## Whats New
 *   ## Guides
 *   ## FAQ
 *   ## Quick Answers
 *   ## Other Tools
 *   ## Final CTA
 *
 * The template reads that page's own content, splits it at these
 * marker headings, and drops each chunk into its own already-styled
 * section — so the surrounding design/spacing/background never
 * moves, only what's written under each marker is editable. Marker
 * headings themselves are never displayed (the template prints its
 * own visible heading per section) — they only tell this code where
 * one zone ends and the next begins.
 *
 * First time a page using either template loads after this snippet
 * is active, if that page is completely empty, it gets auto-filled:
 * the homepage with the real PinsDownload copy, any new Tool Landing
 * page with a lightweight skeleton (marker headings + a one-line hint
 * per zone, no fabricated tool-specific claims) so the pattern is
 * obvious without inventing content for a tool that doesn't exist
 * yet. Never overwrites again after that first fill.
 *
 * Still fixed everywhere, not editable from wp-admin — and why: the
 * site header/footer (owned by your theme); the Hero's eyebrow/H1/
 * subtitle (one H1 per page, tightly bound to the tool right under
 * it — H1 comes from the page's own title, subtitle from its excerpt
 * if set); the downloader tool itself (id="pdl-tool" — working form +
 * PHP logic, not text content); the FAQ accordion's open/close
 * mechanics (its Q&A text IS editable, via the FAQ zone); and the
 * final CTA's scroll-to-tool button.
 */

/* =========================================================
   ICON HELPER — inline SVG only, no external icon library.
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

/* =========================================================
   SHARED STYLES + SCRIPT — call once per page from the template,
   anywhere after get_header(). Identical output regardless of
   which template/page calls it.
========================================================= */

function pd_output_styles() {
	echo '<style>';
	echo <<<'CSS'
/* =========================================================
   PinsDownload — scoped design system (.pd- prefix)
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
   uploaded in wp-admin picks up the same design tokens
   automatically, without needing exact markup. */
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

/* "Features"/"Quick Steps" zones: style core Columns output as cards. */
.pd-gutenberg-zone .wp-block-columns { max-width: none; gap: 22px; margin-bottom: 22px; }
.pd-gutenberg-zone .wp-block-column {
	background: #fff; border: 1px solid var(--pd-border); border-radius: var(--pd-radius-md);
	padding: 26px; transition: transform .25s ease, box-shadow .25s ease;
}
.pd-gutenberg-zone .wp-block-column:hover { transform: translateY(-4px); box-shadow: var(--pd-shadow-hover); }
.pd-gutenberg-zone .wp-block-column .wp-block-heading, .pd-gutenberg-zone .wp-block-column h3 { margin-top: 0; font-size: 16.5px; }
.pd-gutenberg-zone .wp-block-column p:last-child { margin-bottom: 0; font-size: 14px; }
@media (max-width: 700px) { .pd-gutenberg-zone .wp-block-columns { flex-wrap: wrap; } }

/* "Comparison" zone: style a core Table block, scrollable on mobile. */
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
CSS;
	echo '</style>';
}

function pd_output_scripts() {
	echo '<script>';
	echo <<<'JS'
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
JS;
	echo '</script>';
}

/* =========================================================
   THE DOWNLOADER TOOL — pintsave.net-backed, shared by every
   template. Call pd_process_tool_submission() BEFORE get_header()
   (it just reads $_POST and calls the API, nothing that needs to
   run early for header reasons, but keeping the pattern consistent
   with the original single-file snippet), then pd_render_tool_widget()
   with its result inside your own <div id="pdl-tool"> wrapper.
========================================================= */

function pd_process_tool_submission() {
	$result = null;
	$error  = '';

	if (
		! isset( $_SERVER['REQUEST_METHOD'] ) ||
		$_SERVER['REQUEST_METHOD'] !== 'POST' ||
		! isset( $_POST['pdl_action'] ) ||
		$_POST['pdl_action'] !== 'fetch'
	) {
		return array( $result, $error, '' );
	}

	$url = isset( $_POST['pinterest_url'] ) ? trim( wp_unslash( $_POST['pinterest_url'] ) ) : '';

	if ( $url === '' ) {

		$error = 'Please enter a Pinterest URL.';

	} elseif ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {

		$error = 'Please enter a valid Pinterest URL.';

	} else {

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! $host ) {

			$error = 'Invalid Pinterest URL.';

		} else {

			$host = preg_replace( '/^www\./', '', strtolower( $host ) );

			if ( ! in_array( $host, array( 'pinterest.com', 'pin.it' ), true ) ) {

				$error = 'Please enter a Pinterest URL.';

			} else {

				$response = wp_remote_post(
					'https://pintsave.net/api/fetch-media',
					array(
						'timeout' => 45,
						'headers' => array(
							'Accept'           => '*/*',
							'X-Requested-With' => 'XMLHttpRequest',
						),
						'body'    => array( 'url' => $url ),
					)
				);

				if ( is_wp_error( $response ) ) {

					$error = 'Unable to connect to the media service. Please try again.';

				} else {

					$status = wp_remote_retrieve_response_code( $response );
					$body   = wp_remote_retrieve_body( $response );

					if ( $status !== 200 ) {

						$error = 'The media service returned an error. Please try again.';

					} else {

						$result = json_decode( $body, true );

						if ( ! is_array( $result ) || empty( $result['media'] ) || ! is_array( $result['media'] ) ) {
							$error  = 'No downloadable media was found for this Pinterest URL.';
							$result = null;
						}
					}
				}
			}
		}
	}

	return array( $result, $error, $url );
}

/**
 * Outputs the form + loading state + error + results markup. The
 * caller wraps this in its own <div class="pd-tool-card" id="pdl-tool">.
 */
function pd_render_tool_widget( $result, $error, $submitted_url = '' ) {
	?>
	<form method="post" class="pdl-form" id="pdl-form">
		<input
			type="url"
			name="pinterest_url"
			class="pdl-input"
			placeholder="Paste Pinterest link here..."
			value="<?php echo esc_attr( $submitted_url ); ?>"
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

	<?php if ( $error !== '' ) : ?>
		<div class="pdl-error"><?php echo esc_html( $error ); ?></div>
	<?php endif; ?>

	<?php if ( is_array( $result ) && ! empty( $result['media'] ) && is_array( $result['media'] ) ) : ?>
		<div class="pdl-results">
			<?php foreach ( $result['media'] as $media ) : ?>
				<?php
				if ( ! is_array( $media ) || empty( $media['url'] ) ) {
					continue;
				}
				$media_url  = $media['url'];
				$media_type = ! empty( $media['type'] ) ? strtolower( $media['type'] ) : 'image';
				$width      = ! empty( $media['width'] ) ? $media['width'] : '';
				$height     = ! empty( $media['height'] ) ? $media['height'] : '';
				$quality    = ! empty( $media['quality'] ) ? $media['quality'] : '';
				$duration   = ! empty( $media['duration'] ) ? $media['duration'] : '';
				$thumbnail  = ! empty( $media['thumbnail'] ) ? $media['thumbnail'] : '';
				?>
				<div class="pdl-result">
					<?php if ( $media_type === 'video' ) : ?>
						<video class="pdl-media" controls playsinline preload="metadata" <?php if ( $thumbnail !== '' ) : ?>poster="<?php echo esc_url( $thumbnail ); ?>"<?php endif; ?>>
							<source src="<?php echo esc_url( $media_url ); ?>" type="video/mp4">
							Your browser does not support video playback.
						</video>
					<?php else : ?>
						<img class="pdl-media" src="<?php echo esc_url( $media_url ); ?>" alt="Pinterest image" loading="lazy">
					<?php endif; ?>

					<div class="pdl-info">
						<div class="pdl-info-item">
							<span class="pdl-info-label">Type</span>
							<span class="pdl-info-value"><?php echo esc_html( ucfirst( $media_type ) ); ?></span>
						</div>
						<?php if ( $quality !== '' ) : ?>
							<div class="pdl-info-item">
								<span class="pdl-info-label">Quality</span>
								<span class="pdl-info-value"><?php echo esc_html( $quality ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $width !== '' && $height !== '' ) : ?>
							<div class="pdl-info-item">
								<span class="pdl-info-label">Resolution</span>
								<span class="pdl-info-value"><?php echo esc_html( $width . ' × ' . $height ); ?></span>
							</div>
						<?php endif; ?>
						<?php if ( $duration !== '' ) : ?>
							<div class="pdl-info-item">
								<span class="pdl-info-label">Duration</span>
								<span class="pdl-info-value"><?php echo esc_html( $duration . ' seconds' ); ?></span>
							</div>
						<?php endif; ?>
					</div>

					<a class="pdl-download" href="<?php echo esc_url( $media_url ); ?>" download target="_blank" rel="noopener noreferrer">
						Download <?php echo $media_type === 'video' ? 'Video' : 'Image'; ?>
					</a>
				</div>
			<?php endforeach; ?>

			<?php $result_title = ! empty( $result['title'] ) ? $result['title'] : ''; ?>
			<?php if ( $result_title !== '' ) : ?>
				<div class="pdl-meta"><strong><?php echo esc_html( $result_title ); ?></strong></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php
}

/* =========================================================
   ZONE MAP — the H2 marker names every page (front-page.php or
   any page using the Tool Landing template) can use.
========================================================= */

function pd_zone_map() {
	return array(
		'pd-feature-strip' => 'Feature Strip',
		'pd-quick-steps'   => 'Quick Steps',
		'pd-how-to-use'    => 'How to Use',
		'pd-images'        => 'Images',
		'pd-works-doesnt'  => 'Works and Doesnt',
		'pd-features'      => 'Features',
		'pd-content-types' => 'Content Types',
		'pd-explanations'  => 'Explanations',
		'pd-comparison'    => 'Comparison',
		'pd-devices'       => 'Devices',
		'pd-safety'        => 'Safety',
		'pd-trust-badges'  => 'Trust Badges',
		'pd-testimonials'  => 'Testimonials',
		'pd-whats-new'     => 'Whats New',
		'pd-guides'        => 'Guides',
		'pd-faq'           => 'FAQ',
		'pd-quick-answers' => 'Quick Answers',
		'pd-other-tools'   => 'Other Tools',
		'pd-final-cta'     => 'Final CTA',
	);
}

function pd_zone_marker_text( $slug ) {
	$map = pd_zone_map();
	return isset( $map[ $slug ] ) ? $map[ $slug ] : false;
}

function pd_all_zone_markers() {
	return array_values( pd_zone_map() );
}

/* =========================================================
   WHICH PAGE IS THE SOURCE OF ZONE CONTENT?
   At render time (called from inside a template, after the main
   query has resolved) this is simply "whatever page is currently
   being displayed" — the homepage when front-page.php runs, or
   whichever Page is using the Tool Landing template. Each page's
   zones are independent of every other page's.
========================================================= */

function pd_get_zone_source_post() {
	$id = get_queried_object_id();
	if ( ! $id ) {
		return null;
	}
	$post = get_post( $id );
	if ( ! $post || $post->post_type !== 'page' || $post->post_status === 'trash' ) {
		return null;
	}
	return $post;
}

/* =========================================================
   SPLIT THE PAGE'S CONTENT AT THE H2 MARKERS
========================================================= */

/**
 * Returns the parsed blocks that sit under the given zone's H2
 * marker heading, up to (not including) the next marker heading or
 * the end of the content. The marker heading block itself is never
 * included in the result — the template supplies its own visible
 * heading for each section.
 */
function pd_get_zone_blocks( $slug ) {
	$marker = pd_zone_marker_text( $slug );
	$post   = pd_get_zone_source_post();

	if ( ! $marker || ! $post || trim( $post->post_content ) === '' ) {
		return array();
	}

	$blocks     = parse_blocks( $post->post_content );
	$markers    = pd_all_zone_markers();
	$collecting = false;
	$out        = array();

	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) && $block['blockName'] === 'core/heading' ) {
			$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
			$text  = trim( wp_strip_all_tags( render_block( $block ) ) );

			if ( $level === 2 ) {
				$is_this_marker  = ( strcasecmp( $text, $marker ) === 0 );
				$is_other_marker = false;
				foreach ( $markers as $m ) {
					if ( strcasecmp( $text, $m ) === 0 && strcasecmp( $text, $marker ) !== 0 ) {
						$is_other_marker = true;
						break;
					}
				}

				if ( $is_this_marker ) {
					$collecting = true;
					continue;
				}
				if ( $is_other_marker ) {
					if ( $collecting ) {
						break;
					}
					continue;
				}
			}
		}

		if ( $collecting ) {
			$out[] = $block;
		}
	}

	return $out;
}

/* =========================================================
   RENDER HELPERS — called from front-page.php / page-tool-landing.php
========================================================= */

function pd_zone_missing_notice( $slug, $extra = '' ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$post = pd_get_zone_source_post();
	if ( ! $post ) {
		echo '<p class="pd-zone-missing">This page isn\'t recognized as an editable-content source yet. If it\'s meant to be the homepage, set it under Settings &rarr; Reading; if it\'s a Tool Landing page, make sure it\'s Published.</p>';
		return;
	}
	$marker   = pd_zone_marker_text( $slug );
	$edit_url = get_edit_post_link( $post->ID, 'raw' );
	echo '<p class="pd-zone-missing">Nothing here yet. Edit <a href="' . esc_url( $edit_url ) . '">this page</a> and add a Heading block reading exactly "' . esc_html( $marker ) . '", then put content underneath it.' . ( $extra ? ' ' . esc_html( $extra ) : '' ) . ' (Only visible to logged-in editors.)</p>';
}

/**
 * Renders a zone's Gutenberg content as-is (headings, paragraphs,
 * images, galleries, columns, tables...) wrapped in a class the
 * shared CSS styles to match the rest of the design.
 */
function pd_render_content_zone( $slug ) {
	$blocks = pd_get_zone_blocks( $slug );

	if ( empty( $blocks ) ) {
		pd_zone_missing_notice( $slug );
		return;
	}

	echo '<div class="pd-gutenberg-zone">';
	foreach ( $blocks as $block ) {
		echo render_block( $block ); // phpcs:ignore -- core block rendering, handles its own escaping/kses.
	}
	echo '</div>';
}

/**
 * Returns [[question, answer], ...] by reading Heading+Paragraph
 * pairs out of the FAQ zone's blocks (in order: every core/heading
 * starts a new question, the next core/paragraph is its answer).
 * Used both to render the accordion and to build FAQPage schema, so
 * the two can never drift out of sync.
 */
function pd_get_faq_pairs( $slug = 'pd-faq' ) {
	$blocks    = pd_get_zone_blocks( $slug );
	$pairs     = array();
	$pending_q = null;

	foreach ( $blocks as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;
		}
		if ( $block['blockName'] === 'core/heading' ) {
			$pending_q = trim( wp_strip_all_tags( render_block( $block ) ) );
		} elseif ( $block['blockName'] === 'core/paragraph' && $pending_q !== null && $pending_q !== '' ) {
			$answer = trim( wp_strip_all_tags( render_block( $block ) ) );
			if ( $answer !== '' ) {
				$pairs[] = array( $pending_q, $answer );
			}
			$pending_q = null;
		}
	}

	return $pairs;
}

/**
 * Renders the FAQ zone using the exact same .pd-faq-item accordion
 * markup as the rest of the design, so the accordion CSS/JS doesn't
 * need to know where content came from.
 */
function pd_render_faq_zone( $slug = 'pd-faq' ) {
	$pairs = pd_get_faq_pairs( $slug );

	if ( empty( $pairs ) ) {
		pd_zone_missing_notice( $slug, 'Under it, add a Heading block (the question) followed by a Paragraph block (the answer), repeated for each item.' );
		return;
	}
	?>
	<div class="pd-faq-list" id="pd-faq">
		<?php foreach ( $pairs as $pair ) : ?>
			<div class="pd-faq-item">
				<button type="button" class="pd-faq-q" aria-expanded="false">
					<span><?php echo esc_html( $pair[0] ); ?></span>
					<?php pd_icon( 'chevron' ); ?>
				</button>
				<div class="pd-faq-a"><div><p><?php echo esc_html( $pair[1] ); ?></p></div></div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/* =========================================================
   SEED THE HOMEPAGE — once, only while it's still empty.
========================================================= */

add_action( 'init', 'pd_maybe_seed_front_page_content', 20 );

function pd_maybe_seed_front_page_content() {
	$front_id = (int) get_option( 'page_on_front' );
	if ( ! $front_id ) {
		return;
	}
	$post = get_post( $front_id );
	if ( ! $post || $post->post_status === 'trash' ) {
		return;
	}
	if ( trim( $post->post_content ) !== '' ) {
		return;
	}
	if ( get_post_meta( $post->ID, '_pd_seeded', true ) ) {
		return;
	}

	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => pd_default_homepage_markup(),
		)
	);
	update_post_meta( $post->ID, '_pd_seeded', 1 );
}

/* =========================================================
   SEED NEW "TOOL LANDING" PAGES — a lightweight skeleton (marker
   headings + a one-line hint each), not fabricated tool copy.
   Only checked in wp-admin (the moment you open the page you just
   created), not on every public page load.
========================================================= */

add_action( 'admin_init', 'pd_maybe_seed_tool_landing_pages' );

function pd_maybe_seed_tool_landing_pages() {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'draft', 'pending' ),
			'posts_per_page' => 20,
			'meta_key'       => '_wp_page_template', // phpcs:ignore -- small admin-only lookup, no better native query for "pages using this template".
			'meta_value'     => 'page-tool-landing.php', // phpcs:ignore
			'fields'         => 'ids',
		)
	);

	foreach ( $pages as $page_id ) {
		if ( get_post_meta( $page_id, '_pd_seeded', true ) ) {
			continue;
		}
		$post = get_post( $page_id );
		if ( ! $post ) {
			continue;
		}
		if ( trim( $post->post_content ) !== '' ) {
			update_post_meta( $page_id, '_pd_seeded', 1 );
			continue;
		}
		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => pd_default_tool_landing_markup(),
			)
		);
		update_post_meta( $page_id, '_pd_seeded', 1 );
	}
}

/* =========================================================
   SEED CONTENT BUILDERS
========================================================= */

function pd_default_homepage_markup() {

	$h2 = function ( $text ) {
		return "<!-- wp:heading -->\n<h2>" . $text . "</h2>\n<!-- /wp:heading -->\n\n";
	};
	$b3 = function ( $text ) {
		return "<!-- wp:heading {\"level\":3} -->\n<h3>" . $text . "</h3>\n<!-- /wp:heading -->\n\n";
	};
	$p = function ( $text ) {
		return "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$list = function ( $items, $ordered = false ) {
		$attrs = $ordered ? ' {"ordered":true}' : '';
		$tag   = $ordered ? 'ol' : 'ul';
		$html  = "<!-- wp:list$attrs -->\n<$tag>";
		foreach ( $items as $item ) {
			$html .= '<li>' . $item . '</li>';
		}
		$html .= "</$tag>\n<!-- /wp:list -->\n\n";
		return $html;
	};
	$image_hint = function () {
		return "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>Add an Image block here for the screenshot (use the block inserter's \"+\").</em></p>\n<!-- /wp:paragraph -->\n\n";
	};
	$col = function ( $title, $text ) {
		return "<!-- wp:column -->\n<div class=\"wp-block-column\">\n<!-- wp:heading {\"level\":3} -->\n<h3>$title</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph -->\n</div>\n<!-- /wp:column -->\n\n";
	};
	$columns_open  = "<!-- wp:columns -->\n<div class=\"wp-block-columns\">\n";
	$columns_close = "</div>\n<!-- /wp:columns -->\n\n";

	$out = '';

	/* ---------- Feature Strip ---------- */
	$out .= $h2( 'Feature Strip' );
	$out .= $p( 'HD &middot; 2K &middot; 4K quality &middot; No watermark &middot; MP4, JPG, PNG, GIF supported &middot; Works on phone, tablet, and computer' );

	/* ---------- Quick Steps ---------- */
	$out .= $h2( 'Quick Steps' );
	$out .= $columns_open;
	$out .= $col( 'Open Pinterest', 'Open Pinterest and find the video you want.' );
	$out .= $col( 'Copy Link', 'Tap the share icon and choose &quot;Copy Link.&quot;' );
	$out .= $col( 'Paste &amp; Download', 'Paste the link above and tap Download.' );
	$out .= $columns_close;
	$out .= $p( 'Your video saves straight to your device. No app, no sign up.' );
	$out .= $image_hint();

	/* ---------- How to Use ---------- */
	$out .= $h2( 'How to Use' );
	$out .= $b3( 'Downloading From the Pinterest App' );
	$out .= $p( 'You can download Pinterest videos straight from the Pinterest app without leaving it open. Copy the link, come back here, and paste it.' );
	$out .= $list( array(
		'Open the Pinterest app and find your pin.',
		'Tap the three dots (&bull;&bull;&bull;) on the pin.',
		'Tap Copy Link.',
		'Come back here, paste the link, and tap Download.',
		'Your file saves to your Photos or Downloads folder.',
	), true );
	$out .= $image_hint();
	$out .= $b3( 'Downloading on a Computer' );
	$out .= $p( 'You can also download Pinterest videos on a computer, using any browser.' );
	$out .= $list( array(
		'Open Pinterest.com in your browser.',
		'Click the pin, then copy the link from your address bar.',
		'Paste it above and click Download.',
		"The file lands in your computer's Downloads folder.",
	), true );
	$out .= $image_hint();
	$out .= $b3( 'How to Download Pinterest Videos on iPhone' );
	$out .= $p( "Yes, PinsDownload works on iPhone. You don't need an app, just Safari and a Pinterest link." );
	$out .= $list( array(
		'Open the Pinterest app on your iPhone.',
		'Tap the share icon on the video, then Copy Link.',
		'Open Safari and go to pinsdownload.org.',
		'Paste the link and tap Download.',
		'Save the video to your Photos app when it finishes.',
	), true );
	$out .= $image_hint();
	$out .= $b3( 'How to Download Pinterest Videos on Android' );
	$out .= $p( 'Yes, PinsDownload works on Android too, right inside Chrome.' );
	$out .= $list( array(
		'Open the Pinterest app and find your video.',
		'Tap Share, then Copy Link.',
		'Open Chrome and visit pinsdownload.org.',
		'Paste the link and tap Download.',
		'The video saves to your Gallery or Downloads folder.',
	), true );
	$out .= $image_hint();

	/* ---------- Images ---------- */
	$out .= $h2( 'Images' );
	$out .= $p( 'Add screenshots or product photos below with the Image or Gallery block.' );
	$out .= "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>Click the \"+\" inserter and add an Image or Gallery block.</em></p>\n<!-- /wp:paragraph -->\n\n";

	/* ---------- Works and Doesnt ---------- */
	$out .= $h2( 'Works and Doesnt' );
	$out .= $b3( 'What Works' );
	$out .= $list( array(
		'Public pins and pin.it links',
		'Videos, images, GIFs, stories, carousels',
		'Public boards and profiles',
		'Idea Pins and Ideas pages',
	) );
	$out .= $b3( "What Doesn't Work" );
	$out .= $list( array(
		'Private or login-only pins',
		'Deleted or removed pins',
		'Invitation-only boards',
		"Content you don't have rights to save",
	) );

	/* ---------- Features ---------- */
	$out .= $h2( 'Features' );
	$out .= $columns_open;
	$out .= $col( 'Free, always.', 'No hidden charges, no daily limit.' );
	$out .= $col( 'No watermark.', 'Your download looks exactly like the original.' );
	$out .= $col( 'No login.', 'We never ask for your Pinterest password.' );
	$out .= $columns_close;
	$out .= $columns_open;
	$out .= $col( 'Original quality.', 'Videos and images save in the same resolution Pinterest gives us.' );
	$out .= $col( 'Works everywhere.', 'Phone, tablet, or computer, any browser.' );
	$out .= $col( 'Honest about ads.', 'A small number of ads keep this tool free. They never sit on top of or look like the Download button.' );
	$out .= $columns_close;

	/* ---------- Content Types ---------- */
	$out .= $h2( 'Content Types' );
	$out .= $list( array(
		'<strong>Images and photos</strong> &mdash; save any pin in full resolution.',
		'<strong>GIFs</strong> &mdash; download animated pins without losing the loop.',
		"<strong>Reels and short videos</strong> &mdash; grab Pinterest's short-form clips.",
		'<strong>Stories and Idea Pins</strong> &mdash; save every slide of a multi-page pin.',
		'<strong>Carousels</strong> &mdash; download every image or video in a multi-item pin.',
		'<strong>Boards</strong> &mdash; save up to 100 pins from a public board at once, or grab the whole thing as a ZIP file.',
		'<strong>Profiles</strong> &mdash; download every public pin from a Pinterest profile.',
		'<strong>Ideas pages</strong> &mdash; save content straight from a Pinterest Ideas collection.',
		'<strong>Answers pages</strong> &mdash; download pins shared on a Pinterest Answers page.',
		'<strong>Shared pin links</strong> &mdash; paste any multi-pin share link and download everything in it.',
	) );

	/* ---------- Explanations ---------- */
	$out .= $h2( 'Explanations' );
	$out .= $b3( 'What Is a Pinterest Video Downloader?' );
	$out .= $p( "A Pinterest video downloader is a free online tool that saves Pinterest videos, images, and GIFs to your device. Pinterest doesn't let you download videos directly from its app or website, so this tool reads the pin's link and gives you a direct file to save. You don't need an account, and nothing is stored on our end after your download finishes." );
	$out .= $b3( 'What People Use It For' );
	$out .= $p( 'People download Pinterest content for all kinds of projects: home d&eacute;cor ideas, recipes and food photography, fashion inspiration, DIY and craft projects, wedding planning, travel photos, fitness routines, study notes and aesthetics, art references, and mood boards.' );
	$out .= $b3( 'Is It Legal to Download Pinterest Videos?' );
	$out .= $p( "Downloading a Pinterest video for personal, offline use is generally fine. Reposting, selling, or reusing someone else's video without permission is not. Pinterest content belongs to the person who posted it, so always ask before using it publicly." );

	/* ---------- Comparison ---------- */
	$out .= $h2( 'Comparison' );
	$rows = array(
		array( '', 'PinsDownload', 'Typical Free Downloaders', 'Downloader Apps' ),
		array( 'Quality', 'Up to 4K', 'Often capped at 720p', 'Sometimes compressed' ),
		array( 'Watermark', 'None', 'Usually none', 'Often adds app logo' ),
		array( 'Login needed', 'No', 'No', 'Often yes' ),
		array( 'Speed', 'Seconds', 'Slow, ad-heavy', 'Medium' ),
		array( 'Bulk/ZIP download', 'Yes, up to 100 pins', 'Rare', 'Rare' ),
		array( 'Privacy', 'Nothing stored', 'Varies', 'Often collects data' ),
	);
	$table  = '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr>';
	foreach ( $rows[0] as $cell ) {
		$table .= '<th>' . $cell . '</th>';
	}
	$table .= '</tr></thead><tbody>';
	for ( $i = 1; $i < count( $rows ); $i++ ) {
		$table .= '<tr>';
		foreach ( $rows[ $i ] as $cell ) {
			$table .= '<td>' . $cell . '</td>';
		}
		$table .= '</tr>';
	}
	$table .= "</tbody></table></figure>\n<!-- /wp:table -->\n\n";
	$out   .= $table;
	$out   .= "<!-- wp:paragraph -->\n<p><em>REVIEW REQUIRED — section marked DUMMY in the source copy. Every row must be true and tested before this goes live.</em></p>\n<!-- /wp:paragraph -->\n\n";

	/* ---------- Devices ---------- */
	$out .= $h2( 'Devices' );
	$out .= $list( array(
		'<strong>Android</strong> &mdash; Chrome, Firefox',
		'<strong>iPhone / iPad</strong> &mdash; Safari, Chrome',
		'<strong>Windows</strong> &mdash; Chrome, Edge',
		'<strong>Mac</strong> &mdash; Safari, Chrome',
		'<strong>Linux</strong> &mdash; Firefox, Chrome',
	) );

	/* ---------- Safety ---------- */
	$out .= $h2( 'Safety' );
	$out .= $p( 'Yes. We never ask for your Pinterest username or password. You paste a public link, we fetch the file, and nothing you download is stored on our servers afterward. We use standard analytics to see which pages are useful, the same as most websites, but your download history stays private.' );

	/* ---------- Trust Badges ---------- */
	$out .= $h2( 'Trust Badges' );
	$out .= $p( 'Links already point at the real domain. They will show &quot;no data yet&quot; until pinsdownload.org has been live and crawled for a few weeks &mdash; that is expected, not a bug.' );
	$out .= $list( array(
		'<a href="https://transparencyreport.google.com/safe-browsing/search?url=pinsdownload.org">Google Safe Browsing</a> &mdash; verification pending',
		'<a href="https://safeweb.norton.com/report?url=pinsdownload.org">Norton Safe Web</a> &mdash; verification pending',
		'<a href="https://sitecheck.sucuri.net/results/pinsdownload.org">Sucuri Scanner</a> &mdash; verification pending',
	) );

	/* ---------- Testimonials — left empty on purpose, no fake reviews ---------- */
	$out .= $h2( 'Testimonials' );
	$out .= "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>No reviews published yet. Add real ones as Paragraph or Quote blocks once you have them &mdash; never fabricate names, ratings, or quotes here.</em></p>\n<!-- /wp:paragraph -->\n\n";

	/* ---------- Whats New ---------- */
	$out .= $h2( 'Whats New' );
	$out .= $p( '<strong>Aug 2026 &mdash; Launched:</strong> PinsDownload is live, with video, image, GIF, story, carousel, board, and profile downloads all working from day one.' );

	/* ---------- Guides ---------- */
	$out .= $h2( 'Guides' );
	$out .= $list( array(
		'How to Download a Full Pinterest Board (guide not published yet)',
		'Is Downloading Pinterest Content Legal? (guide not published yet)',
		'PinsDownload vs Other Downloaders (guide not published yet)',
	) );

	/* ---------- FAQ ---------- */
	$faq_pairs = array(
		array( 'Is PinsDownload safe to use?', "Yes. We never ask for your Pinterest login, and we don't store the files you download." ),
		array( 'Is it legal to download Pinterest videos?', "Downloading for personal use is fine. Reposting someone else's work without permission is not." ),
		array( 'Do I need to log in to my Pinterest account?', 'No. PinsDownload only works with public links, so no login is needed.' ),
		array( 'What video and image formats are supported?', 'Videos save as MP4. Images save as JPG or PNG. GIFs keep their original animation.' ),
		array( 'Does this tool save my downloaded content?', 'No. We fetch the file and send it straight to you. Nothing is kept on our servers.' ),
		array( 'Can I download Pinterest videos without a watermark?', 'Yes. Every download matches the original Pinterest file, with no watermark added.' ),
		array( 'Is there a limit on how many videos I can download?', 'No daily limit for single pins. Board and profile downloads are capped at 100 pins per request.' ),
		array( 'Does this work on iPhone and Android?', 'Yes. PinsDownload runs in your browser, so it works on iPhone, Android, and desktop.' ),
		array( 'Can I download a full Pinterest board or profile?', 'Yes. Paste the board or profile link, then choose to download items one by one or all at once as a ZIP.' ),
		array( 'Can I download private or deleted pins?', 'No. PinsDownload only works with public, active pins.' ),
		array( 'What should I do if a download fails?', 'Check that the link is public and still active. If it still fails, try copying the link again from Pinterest.' ),
		array( 'Will the video lose quality after downloading?', 'No. PinsDownload saves the file at the same resolution Pinterest provides, with no extra compression.' ),
	);
	$out .= $h2( 'FAQ' );
	foreach ( $faq_pairs as $pair ) {
		$out .= $b3( $pair[0] );
		$out .= $p( $pair[1] );
	}

	/* ---------- Quick Answers ---------- */
	$out .= $h2( 'Quick Answers' );
	$qa_pairs = array(
		array( 'Can I download Pinterest GIFs?', "Yes, paste the GIF's link the same way as a video." ),
		array( 'Where do my downloads go?', "Your device's default Downloads folder, unless you choose another location." ),
		array( 'Does this cost anything?', "No, it's free with no limits on single downloads." ),
		array( 'Is this the same as a "pin saver"?', 'Yes. PinsDownload works as a Pinterest saver too, paste any pin link and save it the same way.' ),
	);
	foreach ( $qa_pairs as $pair ) {
		$out .= $b3( $pair[0] );
		$out .= $p( $pair[1] );
	}

	/* ---------- Other Tools ---------- */
	$out .= $h2( 'Other Tools' );
	$out .= $list( array(
		'Pinterest Video Downloader (you&rsquo;re here)',
		'Pinterest Image Downloader (page not published yet)',
		'Pinterest GIF Downloader (page not published yet)',
		'Pinterest Story Downloader (page not published yet)',
		'Pinterest Board Downloader (page not published yet)',
	) );

	/* ---------- Final CTA ---------- */
	$out .= $h2( 'Final CTA' );
	$out .= $p( 'Paste a Pinterest link above and get your file in seconds.' );

	return $out;
}

/**
 * Minimal skeleton for a brand-new Tool Landing page: every marker
 * heading plus a one-line instruction of what to put under it. No
 * invented claims about a tool whose real copy doesn't exist yet.
 */
function pd_default_tool_landing_markup() {

	$h2 = function ( $text ) {
		return "<!-- wp:heading -->\n<h2>" . $text . "</h2>\n<!-- /wp:heading -->\n\n";
	};
	$hint = function ( $text ) {
		return "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>" . $text . '</em></p>' . "\n<!-- /wp:paragraph -->\n\n";
	};

	$hints = array(
		'pd-feature-strip' => 'Add a single short paragraph of feature highlights, e.g. "HD quality &middot; No watermark &middot; Works on any device."',
		'pd-quick-steps'   => 'Add a Columns block (3 columns), each with a Heading (H3) + Paragraph describing one step.',
		'pd-how-to-use'    => 'Add a Heading (H3) + numbered List, plus an optional Image block, per how-to guide.',
		'pd-images'        => 'Add Image or Gallery blocks here.',
		'pd-works-doesnt'  => 'Add a Heading (H3) "What Works" + a List, then a Heading (H3) "What Doesn&rsquo;t Work" + a List.',
		'pd-features'      => 'Add Columns blocks, each column a Heading (H3) + Paragraph.',
		'pd-content-types' => 'Add a bulleted List, one item per content type this tool handles.',
		'pd-explanations'  => 'Add a Heading (H3) + Paragraph per topic you want to explain.',
		'pd-comparison'    => 'Add a Table block comparing this tool to alternatives (first row = header row).',
		'pd-devices'       => 'Add a List, one item per supported device/browser.',
		'pd-safety'        => 'Add a single Paragraph about safety/privacy.',
		'pd-trust-badges'  => 'Add a Paragraph, then a List with a link per trust badge.',
		'pd-testimonials'  => 'Leave empty until you have real reviews &mdash; never fabricate names, ratings, or quotes.',
		'pd-whats-new'     => 'Add a single Paragraph changelog entry; add a new one at the top each time you ship something.',
		'pd-guides'        => 'Add a List of related guide titles.',
		'pd-faq'           => 'Add a Heading (H3, the question) + Paragraph (the answer), repeated per question.',
		'pd-quick-answers' => 'Add a Heading (H3) + Paragraph pair per quick question, same pattern as FAQ.',
		'pd-other-tools'   => 'Add a List of related tool pages.',
		'pd-final-cta'     => 'Add a single short supporting Paragraph.',
	);

	$out = '';
	foreach ( pd_zone_map() as $slug => $marker ) {
		$out .= $h2( $marker );
		if ( isset( $hints[ $slug ] ) ) {
			$out .= $hint( $hints[ $slug ] );
		}
	}

	return $out;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
} );
