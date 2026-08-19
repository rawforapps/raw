<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * PinsDownload — Homepage Core (icons, styles, tool, content seed)
 * WPCode PHP Snippet (or paste into /wp-content/mu-plugins/ as its
 * own .php file — either works, just pick one, not both)
 *
 * Pairs with front-page.php. Install this ONCE, site-wide.
 *
 * =========================================================
 * HOW EDITING WORKS — deliberately the plainest possible model
 * =========================================================
 * There is no custom zone system, no marker headings, no hidden
 * matching logic. The homepage Page (Settings -> Reading -> Your
 * homepage displays -> A static page) is edited exactly like any
 * other WordPress Page, in the normal block editor. Whatever you
 * type, add, delete, or reorder there is what shows up on the site,
 * in that order — front-page.php just calls the_content() on it, the
 * same core WordPress function every theme uses for every page.
 *
 * - Add an Image block anywhere you want a picture.
 * - Add/edit/delete Heading, Paragraph, List, Table, Columns blocks
 *   freely — every H2 you type becomes a visible section heading.
 * - FAQ items use WordPress's native "Details" block (one per
 *   question: the block's own Summary field is the question, the
 *   Paragraph inside it is the answer) — this is a real WordPress
 *   core block, expands/collapses with zero custom JavaScript, nothing
 *   can get out of sync because there's nothing custom to parse.
 *
 * OPTIONAL POLISH — same native mechanism, zero custom code: every
 * block in the editor has an "Additional CSS class(es)" field
 * (select the block -> right sidebar -> Advanced). Add one of these
 * and the block picks up extra styling automatically:
 *
 *   pd-steps       on a Columns block   -> auto-numbers each column
 *                                          01/02/03... with a
 *                                          connecting line (the
 *                                          "Quick Steps" look)
 *   pd-good        on a Column          -> green-tinted "this works" card
 *   pd-bad         on a Column          -> soft-red "this doesn't" card
 *   pd-pill-list   on a List block      -> renders as rounded pill chips
 *   pd-card-list   on a List block      -> renders as a card grid
 *   pd-callout     on a Paragraph block -> centered, bordered info card
 *
 * These are exactly how the prefilled content below is built — open
 * any prefilled section in the editor to see a live example of each.
 *
 * The first time this runs, if the homepage Page is completely
 * empty, it fills in with the full PinsDownload homepage copy —
 * every section, prefilled, ready to tweak — so you start from real
 * content, not a blank page. It will NEVER overwrite the page again
 * after that first fill, so your edits are always safe.
 *
 * Still fixed in front-page.php, not part of the editable content
 * (and why): the site header/footer (owned by your theme); the
 * Hero's eyebrow/H1/subtitle and the downloader tool itself
 * (id="pdl-tool" — a working form + PHP logic, not text); and the
 * closing "Back to the Downloader" button. Everything else is the
 * Page's own content.
 */

/* =========================================================
   ICON HELPER — only used by the fixed hero/CTA, not content.
========================================================= */

if ( ! function_exists( 'pd_icon' ) ) {
	function pd_icon( $name ) {
		$icons = array(
			'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/>',
			'arrow'  => '<path d="M4 12h15M13 6l6 6-6 6"/>',
		);
		if ( ! isset( $icons[ $name ] ) ) {
			return;
		}
		echo '<svg class="pd-i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[ $name ] . '</svg>'; // phpcs:ignore -- static, hand-written SVG.
	}
}

/* =========================================================
   SHARED STYLES + SCRIPT
========================================================= */

function pd_output_styles() {
	echo '<style>';
	echo <<<'CSS'
#pd-page, #pd-page *, #pd-page *::before, #pd-page *::after { box-sizing: border-box; }

#pd-page {
	--pd-red: #E60023;
	--pd-red-dark: #c90020;
	--pd-red-light: #ff4d68;
	--pd-dark: #171717;
	--pd-text-secondary: #666666;
	--pd-text-muted: #777777;
	--pd-bg: #FFFFFF;
	--pd-bg-soft: #FAF7F7;
	--pd-border: #EAEAEA;
	--pd-red-tint: #FFF1F3;
	--pd-radius-lg: 24px;
	--pd-radius-md: 18px;
	--pd-shadow: 0 2px 6px rgba(23,23,23,.04), 0 10px 40px rgba(23,23,23,.06);
	--pd-shadow-hover: 0 4px 10px rgba(23,23,23,.05), 0 20px 54px rgba(23,23,23,.11);
	--pd-ease: cubic-bezier(.22,.9,.36,1);
	--pd-max: 1200px;

	font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
	color: var(--pd-dark);
	background: var(--pd-bg);
	line-height: 1.7;
	-webkit-font-smoothing: antialiased;
	text-rendering: optimizeLegibility;
}

#pd-page h1, #pd-page h2, #pd-page h3 { font-weight: 800; line-height: 1.22; letter-spacing: -0.015em; color: var(--pd-dark); text-wrap: balance; }
#pd-page a { color: var(--pd-red); }
#pd-page svg.pd-i { width: 20px; height: 20px; flex-shrink: 0; }
#pd-page :focus-visible { outline: 2px solid var(--pd-red); outline-offset: 3px; border-radius: 4px; }
#pd-page ::selection { background: var(--pd-red); color: #fff; }

.pd-container { max-width: var(--pd-max); margin: 0 auto; padding: 0 18px; }
@media (min-width: 640px) { .pd-container { padding: 0 32px; } }

.pd-section { padding: clamp(45px, 7vw, 90px) 0; }

/* Scroll-reveal — progressive enhancement, added by JS only when
   IntersectionObserver is available; no JS means everything just
   shows at full opacity, so nothing here can hide content. */
.pd-reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s var(--pd-ease), transform .6s var(--pd-ease); }
.pd-reveal.pd-in-view { opacity: 1; transform: none; }
@media (prefers-reduced-motion: reduce) { .pd-reveal { opacity: 1; transform: none; transition: none; } }

/* ---------- Hero + tool ---------- */
.pd-hero-section { position: relative; overflow: hidden; padding: clamp(56px, 9vw, 116px) 0 clamp(48px, 7vw, 84px); background: linear-gradient(180deg, #ffffff 0%, var(--pd-bg-soft) 100%); }
.pd-hero-glow {
	position: absolute; inset: 0; pointer-events: none;
	background:
		radial-gradient(560px 380px at 18% -8%, rgba(230,0,35,.09), transparent 65%),
		radial-gradient(620px 420px at 82% 0%, rgba(230,0,35,.07), transparent 65%),
		radial-gradient(900px 500px at 50% 8%, rgba(230,0,35,.06), transparent 70%);
}
.pd-hero { position: relative; text-align: center; }
.pd-hero__eyebrow { display: inline-flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: .16em; font-size: 12.5px; font-weight: 700; color: var(--pd-red); margin: 0 0 20px; }
.pd-hero__eyebrow::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--pd-red); box-shadow: 0 0 0 4px var(--pd-red-tint); }
.pd-hero__title { font-size: clamp(40px, 6vw, 72px); text-align: center; margin: 0 0 20px; background: linear-gradient(180deg, var(--pd-dark) 60%, #3a3a3a 100%); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
.pd-hero__subtitle { max-width: 680px; margin: 0 auto 42px; font-size: clamp(16px, 1.6vw, 19px); text-align: center; color: var(--pd-text-secondary); }

.pd-tool-card {
	position: relative; max-width: 800px; margin: 0 auto;
	background: linear-gradient(#fff, #fff) padding-box, linear-gradient(135deg, var(--pd-red-tint), var(--pd-border) 40%, var(--pd-red-tint)) border-box;
	border: 1px solid transparent;
	border-radius: var(--pd-radius-lg);
	padding: clamp(20px, 4vw, 38px);
	box-shadow: 0 1px 2px rgba(23,23,23,.04), 0 30px 70px -18px rgba(230,0,35,.22), var(--pd-shadow);
	text-align: left;
}

.pdl-form { display: flex; width: 100%; gap: 10px; margin: 0; }
.pdl-input { flex: 1; width: 100%; min-width: 0; height: 54px; padding: 0 16px; border: 1px solid #ddd; border-radius: 12px; background: #fff; color: var(--pd-dark); font-size: 16px; outline: none; transition: border-color .2s var(--pd-ease), box-shadow .2s var(--pd-ease); }
.pdl-input:hover { border-color: #c9c9c9; }
.pdl-input:focus { border-color: var(--pd-red); box-shadow: 0 0 0 4px var(--pd-red-tint); }
.pdl-button { position: relative; overflow: hidden; height: 54px; padding: 0 28px; border: 0; border-radius: 12px; background: linear-gradient(135deg, var(--pd-red-light), var(--pd-red) 55%, var(--pd-red-dark)); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; white-space: nowrap; box-shadow: 0 8px 20px rgba(230,0,35,.28); transition: transform .2s var(--pd-ease), box-shadow .2s var(--pd-ease), filter .2s ease; }
.pdl-button::after { content: ""; position: absolute; inset: 0; background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,.35) 50%, transparent 70%); transform: translateX(-120%); transition: transform .6s var(--pd-ease); }
.pdl-button:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(230,0,35,.34); filter: brightness(1.03); }
.pdl-button:hover::after { transform: translateX(120%); }
.pdl-button:active { transform: translateY(0); filter: brightness(.97); }
.pdl-button:disabled { opacity: .7; cursor: wait; transform: none; box-shadow: none; }
.pdl-loading { display: none; margin-top: 18px; text-align: center; color: var(--pd-text-secondary); font-size: 14px; }
.pdl-spinner { display: inline-block; width: 18px; height: 18px; margin-right: 7px; vertical-align: middle; border: 3px solid #dddddd; border-top-color: var(--pd-red); border-radius: 50%; animation: pdl-spin .8s linear infinite; }
@keyframes pdl-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .pdl-spinner { animation-duration: 1.6s; } .pdl-button::after { display: none; } }
.pdl-error { margin-top: 20px; padding: 14px 16px; border: 1px solid #ffd0d0; border-radius: 12px; background: #fff0f0; color: #b00020; font-size: 14px; line-height: 1.5; }
.pdl-results { margin-top: 26px; }
.pdl-result { margin-bottom: 20px; padding: 18px; border: 1px solid var(--pd-border); border-radius: 16px; background: var(--pd-bg-soft); transition: box-shadow .25s var(--pd-ease); }
.pdl-result:hover { box-shadow: var(--pd-shadow); }
.pdl-media { display: block; width: 100%; max-height: 560px; margin: 0 auto 16px; border-radius: 12px; background: #111; object-fit: contain; }
.pdl-info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
.pdl-info-item { min-width: 0; padding: 11px; border: 1px solid var(--pd-border); border-radius: 10px; background: #fff; }
.pdl-info-label { display: block; margin-bottom: 4px; color: var(--pd-text-muted); font-size: 12px; }
.pdl-info-value { display: block; color: var(--pd-dark); font-size: 14px; font-weight: 600; word-break: break-word; }
.pdl-download { display: block; width: 100%; padding: 14px 18px; border-radius: 12px; background: linear-gradient(135deg, var(--pd-red-light), var(--pd-red) 60%, var(--pd-red-dark)); color: #fff !important; text-align: center; text-decoration: none !important; font-size: 15px; font-weight: 700; box-shadow: 0 6px 16px rgba(230,0,35,.22); transition: transform .2s var(--pd-ease), box-shadow .2s var(--pd-ease); }
.pdl-download:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(230,0,35,.28); }
.pdl-download:active { transform: translateY(0); }
.pdl-meta { margin-top: 18px; padding: 15px; border-radius: 12px; background: var(--pd-bg-soft); color: var(--pd-text-secondary); font-size: 14px; }
.pdl-meta strong { color: var(--pd-dark); }

/* ---------- Editable content — the Page's the_content() output ----------
   Targets native block classes so ordinary editing (headings,
   paragraphs, lists, images, tables, columns, details/FAQ) always
   looks on-brand with zero special markup required. */
.pd-content { max-width: 900px; margin: 0 auto; }
.pd-content > *:first-child { margin-top: 0; }
.pd-content h2, .pd-content .wp-block-heading:is(h2) { position: relative; font-size: clamp(26px, 3.4vw, 38px); margin: 64px 0 22px; text-align: center; padding-top: 22px; }
.pd-content h2::before, .pd-content .wp-block-heading:is(h2)::before { content: ""; position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 46px; height: 3px; border-radius: 999px; background: linear-gradient(90deg, var(--pd-red-light), var(--pd-red)); }
.pd-content h2:first-child { margin-top: 0; }
.pd-content h3, .pd-content .wp-block-heading:is(h3) { font-size: clamp(18px, 2.2vw, 20px); margin: 22px 0 8px; }
.pd-content > p { max-width: 680px; margin: 0 auto 16px; text-align: center; }
.pd-content p { color: var(--pd-text-secondary); font-size: 16px; line-height: 1.75; }
.pd-content ol, .pd-content ul { padding-left: 22px; margin: 0 auto 18px; color: var(--pd-text-secondary); max-width: 680px; }
.pd-content li { padding: 3px 0; line-height: 1.7; }
.pd-content a { color: var(--pd-red); text-decoration-color: var(--pd-red-tint); text-decoration-thickness: 2px; text-underline-offset: 3px; transition: text-decoration-color .2s ease, color .2s ease; }
.pd-content a:hover { color: var(--pd-red-dark); text-decoration-color: currentColor; }
.pd-content strong { color: var(--pd-dark); }
.pd-content img { border-radius: var(--pd-radius-md); box-shadow: var(--pd-shadow); max-width: 100%; height: auto; transition: box-shadow .25s var(--pd-ease); }
.pd-content img:hover { box-shadow: var(--pd-shadow-hover); }
.pd-content .wp-block-image, .pd-content .wp-block-gallery { margin: 22px auto; }
.pd-content figcaption { text-align: center; font-size: 13px; color: var(--pd-text-muted); margin-top: 8px; }
.pd-content em { color: var(--pd-text-muted); }

/* Columns -> card grid (Features, Quick Steps, Works/Doesn't...) */
.pd-content .wp-block-columns { gap: 20px; margin: 30px 0; max-width: none; }
.pd-content .wp-block-column { position: relative; background: #fff; border: 1px solid var(--pd-border); border-radius: var(--pd-radius-md); padding: 26px; text-align: left; transition: transform .25s var(--pd-ease), box-shadow .25s var(--pd-ease), border-color .25s var(--pd-ease); }
.pd-content .wp-block-column:hover { transform: translateY(-4px); box-shadow: var(--pd-shadow-hover); border-color: var(--pd-red-tint); }
.pd-content .wp-block-column .wp-block-heading, .pd-content .wp-block-column h3 { margin-top: 0; font-size: 16.5px; text-align: left; }
.pd-content .wp-block-column p { text-align: left; max-width: none; margin: 0 auto; }
.pd-content .wp-block-column p:last-child { margin-bottom: 0; font-size: 14px; }
@media (max-width: 700px) { .pd-content .wp-block-columns { flex-wrap: wrap; } }
@media (prefers-reduced-motion: reduce) { .pd-content .wp-block-column:hover, .pd-content img:hover { transform: none; } }

/* Utility: pd-steps on a Columns block — auto-numbers 01/02/03 with a connecting line. */
.pd-content .wp-block-columns.pd-steps { counter-reset: pd-step; position: relative; margin-top: 46px; }
.pd-content .wp-block-columns.pd-steps::before { content: ""; position: absolute; top: 40px; left: 17%; right: 17%; height: 2px; background: linear-gradient(90deg, var(--pd-red-tint), var(--pd-border), var(--pd-red-tint)); z-index: 0; }
.pd-content .wp-block-columns.pd-steps > .wp-block-column { counter-increment: pd-step; position: relative; z-index: 1; }
.pd-content .wp-block-columns.pd-steps > .wp-block-column::before {
	content: counter(pd-step, decimal-leading-zero);
	display: flex; align-items: center; justify-content: center;
	width: 34px; height: 34px; margin-bottom: 14px;
	border-radius: 50%; background: var(--pd-red-tint); color: var(--pd-red);
	font-weight: 800; font-size: 13px;
}

/* Utility: pd-good / pd-bad on a Column — the "what works / doesn't" look. */
.pd-content .wp-block-column.pd-good { background: #fff; border-color: #bfe6cc; padding-left: 30px; }
.pd-content .wp-block-column.pd-good::before { content: "✓"; position: absolute; top: 22px; left: 24px; display: flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #e8f7ec; color: #1a7a3c; font-size: 13px; font-weight: 800; }
.pd-content .wp-block-column.pd-good .wp-block-heading, .pd-content .wp-block-column.pd-good h3 { color: #1a7a3c; padding-left: 30px; }
.pd-content .wp-block-column.pd-bad { background: var(--pd-bg-soft); border-color: #f3c9c9; padding-left: 30px; }
.pd-content .wp-block-column.pd-bad::before { content: "✕"; position: absolute; top: 22px; left: 24px; display: flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #fbe3e3; color: #a03; font-size: 12px; font-weight: 800; }
.pd-content .wp-block-column.pd-bad .wp-block-heading, .pd-content .wp-block-column.pd-bad h3 { color: #a03; padding-left: 30px; }
.pd-content .wp-block-column.pd-good:hover, .pd-content .wp-block-column.pd-bad:hover { transform: none; box-shadow: var(--pd-shadow); }

/* Utility: pd-pill-list on a List — rounded chip row (feature strip, tags). */
.pd-content ul.pd-pill-list { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; list-style: none; padding-left: 0; max-width: none; }
.pd-content ul.pd-pill-list > li { background: #fff; border: 1px solid var(--pd-border); border-radius: 999px; padding: 10px 18px; font-size: 13.5px; font-weight: 600; color: var(--pd-dark); transition: transform .2s var(--pd-ease), box-shadow .2s var(--pd-ease), border-color .2s ease, color .2s ease; }
.pd-content ul.pd-pill-list > li:hover { transform: translateY(-2px); box-shadow: var(--pd-shadow); border-color: var(--pd-red-tint); color: var(--pd-red); }

/* Utility: pd-card-list on a List — card grid (content types, devices, guides...). */
.pd-content ul.pd-card-list, .pd-content ol.pd-card-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 14px; list-style: none; padding-left: 0; max-width: none; }
.pd-content .pd-card-list > li { position: relative; overflow: hidden; background: #fff; border: 1px solid var(--pd-border); border-radius: 16px; padding: 18px; font-size: 14px; text-align: left; transition: transform .25s var(--pd-ease), box-shadow .25s var(--pd-ease), border-color .25s var(--pd-ease); }
.pd-content .pd-card-list > li::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--pd-red-light), var(--pd-red)); transform: scaleX(0); transform-origin: left; transition: transform .3s var(--pd-ease); }
.pd-content .pd-card-list > li:hover { transform: translateY(-4px); box-shadow: var(--pd-shadow-hover); border-color: var(--pd-red-tint); }
.pd-content .pd-card-list > li:hover::before { transform: scaleX(1); }
.pd-content .pd-card-list > li strong { display: block; color: var(--pd-dark); margin-bottom: 4px; }
@media (prefers-reduced-motion: reduce) { .pd-content .pd-card-list > li:hover, .pd-content ul.pd-pill-list > li:hover { transform: none; } }

/* Utility: pd-callout on a Paragraph — centered bordered info card (safety, legal). */
.pd-content p.pd-callout { position: relative; max-width: 800px; margin: 0 auto 16px; text-align: center; background: linear-gradient(180deg, #fff, var(--pd-bg-soft)); border: 1px solid var(--pd-border); border-top: 3px solid var(--pd-red); border-radius: var(--pd-radius-lg); padding: 38px 36px 34px; box-shadow: var(--pd-shadow); font-size: 16px; }

/* Native Table block (comparison, works/doesn't if used as a table). */
.pd-content .wp-block-table { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: var(--pd-radius-md); border: 1px solid var(--pd-border); background: #fff; margin: 0 0 18px; max-width: none; box-shadow: var(--pd-shadow); }
.pd-content .wp-block-table table { width: 100%; border-collapse: collapse; min-width: 480px; margin: 0; }
.pd-content .wp-block-table th, .pd-content .wp-block-table td { padding: 15px 16px; text-align: left; font-size: 14.5px; border-bottom: 1px solid var(--pd-border); }
.pd-content .wp-block-table thead th { font-size: 12.5px; text-transform: uppercase; letter-spacing: .05em; color: var(--pd-text-muted); background: var(--pd-bg-soft); border-bottom: 1px solid var(--pd-border); }
.pd-content .wp-block-table tbody tr { transition: background-color .15s ease; }
.pd-content .wp-block-table tbody tr:hover { background-color: var(--pd-red-tint); }
.pd-content .wp-block-table tbody tr:last-child td { border-bottom: 0; }

/* Native Media & Text block, if used for a how-to guide (image beside text). */
.pd-content .wp-block-media-text { max-width: none; margin: 30px 0; border-radius: var(--pd-radius-md); overflow: hidden; }
.pd-content .wp-block-media-text .wp-block-media-text__media img { border-radius: 0; box-shadow: none; height: 100%; object-fit: cover; }
.pd-content .wp-block-media-text .wp-block-media-text__content { padding: 0 0 0 32px; display: flex; flex-direction: column; justify-content: center; }
.pd-content .wp-block-media-text.has-media-on-the-right .wp-block-media-text__content { padding: 0 32px 0 0; }
@media (max-width: 600px) { .pd-content .wp-block-media-text .wp-block-media-text__content, .pd-content .wp-block-media-text.has-media-on-the-right .wp-block-media-text__content { padding: 24px 0 0; } }

/* Native "Details" block = FAQ accordion. No JS needed. */
.pd-content details.wp-block-details { border-bottom: 1px solid var(--pd-border); max-width: 680px; margin-left: auto; margin-right: auto; transition: background-color .2s ease; border-radius: 10px; }
.pd-content details.wp-block-details:first-of-type { border-top: 1px solid var(--pd-border); margin-top: 8px; }
.pd-content details.wp-block-details:hover { background-color: var(--pd-bg-soft); }
.pd-content details[open] { background-color: var(--pd-bg-soft); }
.pd-content summary { cursor: pointer; list-style: none; padding: 18px 34px 18px 16px; font-weight: 600; font-size: 15.5px; color: var(--pd-dark); position: relative; text-align: left; transition: color .2s ease; }
.pd-content summary:hover { color: var(--pd-red); }
.pd-content summary::-webkit-details-marker { display: none; }
.pd-content summary::after { content: ""; position: absolute; right: 12px; top: 50%; width: 9px; height: 9px; border-right: 2px solid var(--pd-red); border-bottom: 2px solid var(--pd-red); transform: translateY(-70%) rotate(45deg); transition: transform .25s var(--pd-ease); }
.pd-content details[open] summary::after { transform: translateY(-30%) rotate(-135deg); }
.pd-content details p { padding: 0 16px 20px; margin: 0; text-align: left; max-width: none; }
@media (prefers-reduced-motion: reduce) { .pd-content summary::after { transition: none; } }

.pd-zone-missing { max-width: 820px; margin: 0 auto; padding: 16px 20px; border: 1px dashed var(--pd-border); border-radius: 12px; background: var(--pd-bg-soft); color: var(--pd-text-muted); font-size: 14px; text-align: center; }

/* ---------- Final CTA ---------- */
.pd-cta { position: relative; text-align: center; padding: clamp(50px, 7vw, 90px) 0; overflow: hidden; }
.pd-cta::before { content: ""; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(680px 320px at 50% 100%, rgba(230,0,35,.07), transparent 70%); }
.pd-cta h2 { position: relative; font-size: clamp(26px, 3.4vw, 38px); margin-bottom: 26px; }
.pd-btn-primary { position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, var(--pd-red-light), var(--pd-red) 55%, var(--pd-red-dark)); color: #fff !important; text-decoration: none !important; font-weight: 700; font-size: 16px; padding: 16px 32px; border-radius: 12px; box-shadow: 0 8px 20px rgba(230,0,35,.28); transition: transform .2s var(--pd-ease), box-shadow .2s var(--pd-ease), filter .2s ease; min-height: 44px; }
.pd-btn-primary::after { content: ""; position: absolute; inset: 0; background: linear-gradient(120deg, transparent 30%, rgba(255,255,255,.35) 50%, transparent 70%); transform: translateX(-120%); transition: transform .6s var(--pd-ease); }
.pd-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(230,0,35,.34); filter: brightness(1.03); }
.pd-btn-primary:hover::after { transform: translateX(120%); }
.pd-btn-primary:active { transform: translateY(0); filter: brightness(.97); }
@media (prefers-reduced-motion: reduce) { .pd-btn-primary::after { display: none; } }

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

	var ctaScroll = document.getElementById('pd-cta-scroll');
	if (ctaScroll) {
		ctaScroll.addEventListener('click', function (e) {
			var target = document.getElementById('pdl-tool');
			if (target) {
				e.preventDefault();
				var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
				target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
			}
		});
	}

	/* Scroll-reveal for the editable content — progressive
	   enhancement only. If IntersectionObserver isn't available,
	   this block does nothing and every element stays fully visible
	   (the .pd-reveal class, which starts things at opacity: 0, is
	   never added in that case). */
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (!reduceMotion && 'IntersectionObserver' in window) {
		var content = document.querySelector('.pd-content');
		if (content) {
			var targets = content.querySelectorAll(':scope > *');
			var observer = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						entry.target.classList.add('pd-in-view');
						observer.unobserve(entry.target);
					}
				});
			}, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
			targets.forEach(function (el, i) {
				el.classList.add('pd-reveal');
				el.style.transitionDelay = (Math.min(i % 3, 2) * 0.08) + 's';
				observer.observe(el);
			});
		}
	}
})();
JS;
	echo '</script>';
}

/* =========================================================
   THE DOWNLOADER TOOL — pintsave.net-backed.
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
   FAQ SCHEMA — reads native "Details" blocks straight out of the
   homepage Page's content. No custom parsing rules to keep in sync;
   core/details is a real, unambiguous WordPress block.
========================================================= */

function pd_collect_details_pairs( $blocks, &$pairs ) {
	foreach ( $blocks as $block ) {
		if ( ! empty( $block['blockName'] ) && $block['blockName'] === 'core/details' ) {
			$question = isset( $block['attrs']['summary'] ) ? trim( wp_strip_all_tags( $block['attrs']['summary'] ) ) : '';
			$answer   = '';
			if ( ! empty( $block['innerBlocks'] ) ) {
				foreach ( $block['innerBlocks'] as $inner ) {
					$answer .= wp_strip_all_tags( render_block( $inner ) ) . ' ';
				}
			}
			$answer = trim( $answer );
			if ( $question !== '' && $answer !== '' ) {
				$pairs[] = array( $question, $answer );
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			pd_collect_details_pairs( $block['innerBlocks'], $pairs );
		}
	}
}

function pd_get_homepage_faq_pairs() {
	$front_id = (int) get_option( 'page_on_front' );
	if ( ! $front_id ) {
		return array();
	}
	$post = get_post( $front_id );
	if ( ! $post ) {
		return array();
	}
	$pairs = array();
	pd_collect_details_pairs( parse_blocks( $post->post_content ), $pairs );
	return $pairs;
}

/* =========================================================
   SEED THE HOMEPAGE — once, only while it's still empty.
========================================================= */

add_action( 'init', 'pd_maybe_seed_homepage_content', 20 );

function pd_maybe_seed_homepage_content() {
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
	if ( get_post_meta( $post->ID, '_pd_content_seeded', true ) ) {
		return;
	}

	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => pd_default_homepage_markup(),
		)
	);
	update_post_meta( $post->ID, '_pd_content_seeded', 1 );
}

/* =========================================================
   MANUAL RESET — a small wp-admin button, for when you've cleared
   the homepage content yourself and want the starter content back.
   The auto-seed above only ever fires once, on purpose, so it won't
   do this automatically — this is the deliberate, explicit way to
   ask for it again. Shows up on the Dashboard, the Pages list, and
   the homepage Page's own edit screen.
========================================================= */

add_action( 'admin_notices', 'pd_reseed_admin_notice' );

function pd_reseed_admin_notice() {
	if ( ! current_user_can( 'edit_posts' ) || ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	$front_id = (int) get_option( 'page_on_front' );
	if ( ! $front_id ) {
		return;
	}
	$on_this_page = ( 'page' === $screen->id && isset( $_GET['post'] ) && (int) $_GET['post'] === $front_id ); // phpcs:ignore -- read-only, display logic only.
	if ( 'dashboard' !== $screen->id && 'edit-page' !== $screen->id && ! $on_this_page ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=pd_reseed_homepage' ), 'pd_reseed_homepage' );
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong>PinsDownload:</strong>
			<a href="<?php echo esc_url( $url ); ?>" onclick="return confirm('This replaces the current homepage content with the default PinsDownload starter content — anything on the page right now will be overwritten. Continue?');">Reset homepage content to the starter copy</a>
			— use this only if the homepage looks empty or broken and you want to start fresh.
		</p>
	</div>
	<?php
}

add_action( 'admin_post_pd_reseed_homepage', 'pd_handle_reseed_homepage' );

function pd_handle_reseed_homepage() {
	if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'pd_reseed_homepage' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'pinsdownload' ) );
	}
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id ) {
		wp_update_post(
			array(
				'ID'           => $front_id,
				'post_content' => pd_default_homepage_markup(),
			)
		);
		update_post_meta( $front_id, '_pd_content_seeded', 1 );
	}
	wp_safe_redirect( $front_id ? get_edit_post_link( $front_id, '' ) : admin_url() );
	exit;
}

function pd_default_homepage_markup() {

	$h2 = function ( $text ) {
		return "<!-- wp:heading -->\n<h2>" . $text . "</h2>\n<!-- /wp:heading -->\n\n";
	};
	$p = function ( $text, $class = '' ) {
		$attrs      = $class !== '' ? ' ' . wp_json_encode( array( 'className' => $class ) ) : '';
		$class_attr = $class !== '' ? ' class="' . esc_attr( $class ) . '"' : '';
		return "<!-- wp:paragraph$attrs -->\n<p$class_attr>" . $text . "</p>\n<!-- /wp:paragraph -->\n\n";
	};
	$hint = function ( $text ) {
		return "<!-- wp:paragraph {\"placeholder\":true} -->\n<p><em>" . $text . '</em></p>' . "\n<!-- /wp:paragraph -->\n\n";
	};
	$list = function ( $items, $ordered = false, $class = '' ) {
		$tag       = $ordered ? 'ol' : 'ul';
		$attrs_arr = array();
		if ( $ordered ) {
			$attrs_arr['ordered'] = true;
		}
		if ( $class !== '' ) {
			$attrs_arr['className'] = $class;
		}
		$attrs      = ! empty( $attrs_arr ) ? ' ' . wp_json_encode( $attrs_arr ) : '';
		$class_attr = $class !== '' ? ' class="' . esc_attr( $class ) . '"' : '';
		$html       = "<!-- wp:list$attrs -->\n<$tag$class_attr>";
		foreach ( $items as $item ) {
			$html .= '<li>' . $item . '</li>';
		}
		$html .= "</$tag>\n<!-- /wp:list -->\n\n";
		return $html;
	};
	/* A single Column, given arbitrary inner block HTML, with an optional
	   "Additional CSS class" (pd-good / pd-bad, same field the editor exposes). */
	$column = function ( $inner, $class = '' ) {
		$attrs      = $class !== '' ? ' ' . wp_json_encode( array( 'className' => $class ) ) : '';
		$class_attr = 'wp-block-column' . ( $class !== '' ? ' ' . esc_attr( $class ) : '' );
		return "<!-- wp:column$attrs -->\n<div class=\"$class_attr\">\n" . $inner . "</div>\n<!-- /wp:column -->\n\n";
	};
	/* A Column pre-filled with a Heading (H3) + Paragraph — the "feature card" shape. */
	$feature_col = function ( $title, $text, $class = '' ) use ( $column ) {
		$inner = "<!-- wp:heading {\"level\":3} -->\n<h3>$title</h3>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>$text</p>\n<!-- /wp:paragraph -->\n";
		return $column( $inner, $class );
	};
	$columns_open = function ( $class = '' ) {
		$attrs      = $class !== '' ? ' ' . wp_json_encode( array( 'className' => $class ) ) : '';
		$class_attr = 'wp-block-columns' . ( $class !== '' ? ' ' . esc_attr( $class ) : '' );
		return "<!-- wp:columns$attrs -->\n<div class=\"$class_attr\">\n";
	};
	$columns_close = "</div>\n<!-- /wp:columns -->\n\n";
	$table         = function ( $rows ) {
		$html = '<!-- wp:table --><figure class="wp-block-table"><table><thead><tr>';
		foreach ( $rows[0] as $cell ) {
			$html .= '<th>' . $cell . '</th>';
		}
		$html .= '</tr></thead><tbody>';
		for ( $i = 1; $i < count( $rows ); $i++ ) {
			$html .= '<tr>';
			foreach ( $rows[ $i ] as $cell ) {
				$html .= '<td>' . $cell . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= "</tbody></table></figure>\n<!-- /wp:table -->\n\n";
		return $html;
	};
	$details = function ( $question, $answer ) {
		$attrs = wp_json_encode( array( 'summary' => $question ) );
		return "<!-- wp:details $attrs -->\n<details class=\"wp-block-details\"><summary>" . esc_html( $question ) . '</summary><!-- wp:paragraph -->' . "\n<p>" . esc_html( $answer ) . "</p>\n<!-- /wp:paragraph --></details>\n<!-- /wp:details -->\n\n";
	};

	$out = '';

	/* 2. Feature strip */
	$out .= $list( array(
		'HD &middot; 2K &middot; 4K quality',
		'No watermark',
		'MP4, JPG, PNG, GIF supported',
		'Works on phone, tablet, and computer',
	), false, 'pd-pill-list' );

	/* 3. How to Download a Pinterest Video — pd-steps auto-numbers the columns. */
	$out .= $h2( 'How to Download a Pinterest Video' );
	$out .= $p( 'A Pinterest video downloader works in three steps. Open Pinterest and find the video you want. Tap the share icon and choose "Copy Link." Paste the link above and tap Download.' );
	$out .= $columns_open( 'pd-steps' );
	$out .= $feature_col( 'Open Pinterest', 'Open Pinterest and find the video you want.' );
	$out .= $feature_col( 'Copy Link', 'Tap the share icon and choose &quot;Copy Link.&quot;' );
	$out .= $feature_col( 'Paste &amp; Download', 'Paste the link above and tap Download.' );
	$out .= $columns_close;
	$out .= $p( 'Your video saves straight to your device. No app, no sign up.' );
	$out .= $hint( "Add an Image block here for a tutorial screenshot (use the block inserter's \"+\")." );

	/* 4. Downloading From the Pinterest App */
	$out .= $h2( 'Downloading From the Pinterest App' );
	$out .= $p( 'You can download Pinterest videos straight from the Pinterest app without leaving it open. Copy the link, come back here, and paste it.' );
	$out .= $list( array(
		'Open the Pinterest app and find your pin.',
		'Tap the three dots (&bull;&bull;&bull;) on the pin.',
		'Tap Copy Link.',
		'Come back here, paste the link, and tap Download.',
		'Your file saves to your Photos or Downloads folder.',
	), true );
	$out .= $hint( 'Add an Image block here for a screenshot.' );

	/* 5. Downloading on a Computer */
	$out .= $h2( 'Downloading on a Computer' );
	$out .= $p( 'You can also download Pinterest videos on a computer, using any browser.' );
	$out .= $list( array(
		'Open Pinterest.com in your browser.',
		'Click the pin, then copy the link from your address bar.',
		'Paste it above and click Download.',
		"The file lands in your computer's Downloads folder.",
	), true );
	$out .= $hint( 'Add an Image block here for a screenshot.' );

	/* 6. iPhone */
	$out .= $h2( 'How to Download Pinterest Videos on iPhone' );
	$out .= $p( "Yes, PinsDownload works on iPhone. You don't need an app, just Safari and a Pinterest link." );
	$out .= $list( array(
		'Open the Pinterest app on your iPhone.',
		'Tap the share icon on the video, then Copy Link.',
		'Open Safari and go to pinsdownload.org.',
		'Paste the link and tap Download.',
		'Save the video to your Photos app when it finishes.',
	), true );
	$out .= $hint( 'Add an Image block here for a screenshot.' );

	/* 7. Android */
	$out .= $h2( 'How to Download Pinterest Videos on Android' );
	$out .= $p( 'Yes, PinsDownload works on Android too, right inside Chrome.' );
	$out .= $list( array(
		'Open the Pinterest app and find your video.',
		'Tap Share, then Copy Link.',
		'Open Chrome and visit pinsdownload.org.',
		'Paste the link and tap Download.',
		'The video saves to your Gallery or Downloads folder.',
	), true );
	$out .= $hint( 'Add an Image block here for a screenshot.' );

	/* 8. What This Tool Can and Can't Download — pd-good/pd-bad columns. */
	$out .= $h2( "What This Tool Can and Can't Download" );
	$out .= $p( "PinsDownload works with any public Pinterest link. It can't open anything that needs a Pinterest login." );
	$works_inner   = "<!-- wp:heading {\"level\":3} -->\n<h3>&#10003; What Works</h3>\n<!-- /wp:heading -->\n\n" . $list( array(
		'Public pins and pin.it links',
		'Videos, images, GIFs, stories, carousels',
		'Public boards and profiles',
		'Idea Pins and Ideas pages',
	) );
	$doesnt_inner  = "<!-- wp:heading {\"level\":3} -->\n<h3>&#10007; What Doesn't Work</h3>\n<!-- /wp:heading -->\n\n" . $list( array(
		'Private or login-only pins',
		'Deleted or removed pins',
		'Invitation-only boards',
		"Content you don't have rights to save",
	) );
	$out .= $columns_open();
	$out .= $column( $works_inner, 'pd-good' );
	$out .= $column( $doesnt_inner, 'pd-bad' );
	$out .= $columns_close;

	/* 9. Why People Use This Tool */
	$out .= $h2( 'Why People Use This Tool' );
	$out .= $p( "PinsDownload is built to be simple, honest, and free. Here's what that means in practice." );
	$out .= $columns_open();
	$out .= $feature_col( 'Free, always.', 'No hidden charges, no daily limit.' );
	$out .= $feature_col( 'No watermark.', 'Your download looks exactly like the original.' );
	$out .= $feature_col( 'No login.', 'We never ask for your Pinterest password.' );
	$out .= $columns_close;
	$out .= $columns_open();
	$out .= $feature_col( 'Original quality.', 'Videos and images save in the same resolution Pinterest gives us.' );
	$out .= $feature_col( 'Works everywhere.', 'Phone, tablet, or computer, any browser.' );
	$out .= $feature_col( 'Honest about ads.', 'A small number of ads keep this tool free. They never sit on top of or look like the Download button.' );
	$out .= $columns_close;

	/* 10. What Else You Can Download From Pinterest */
	$out .= $h2( 'What Else You Can Download From Pinterest' );
	$out .= $p( "PinsDownload isn't only for videos. It handles every kind of Pinterest content." );
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
	), false, 'pd-card-list' );

	/* 11. What Is a Pinterest Video Downloader? */
	$out .= $h2( 'What Is a Pinterest Video Downloader?' );
	$out .= $p( "A Pinterest video downloader is a free online tool that saves Pinterest videos, images, and GIFs to your device. Pinterest doesn't let you download videos directly from its app or website, so this tool reads the pin's link and gives you a direct file to save. You don't need an account, and nothing is stored on our end after your download finishes." );

	/* 12. What People Use It For */
	$out .= $h2( 'What People Use It For' );
	$out .= $p( 'People download Pinterest content for all kinds of projects. Here are the most common ones.' );
	$out .= $p( 'Home d&eacute;cor ideas &middot; Recipes and food photography &middot; Fashion inspiration &middot; DIY and craft projects &middot; Wedding planning &middot; Travel photos &middot; Fitness routines &middot; Study notes and aesthetics &middot; Art references &middot; Mood boards' );

	/* 13. How This Compares to Other Downloaders (DUMMY) */
	$out .= $h2( 'How This Compares to Other Downloaders' );
	$out .= $p( 'PinsDownload is built to beat the basics that most Pinterest downloaders get wrong.' );
	$out .= $table( array(
		array( '', 'PinsDownload', 'Typical Free Downloaders', 'Downloader Apps' ),
		array( 'Quality', 'Up to 4K', 'Often capped at 720p', 'Sometimes compressed' ),
		array( 'Watermark', 'None', 'Usually none', 'Often adds app logo' ),
		array( 'Login needed', 'No', 'No', 'Often yes' ),
		array( 'Speed', 'Seconds', 'Slow, ad-heavy', 'Medium' ),
		array( 'Bulk/ZIP download', 'Yes, up to 100 pins', 'Rare', 'Rare' ),
		array( 'Privacy', 'Nothing stored', 'Varies', 'Often collects data' ),
	) );
	$out .= "<!-- wp:paragraph -->\n<p><em>REVIEW REQUIRED &mdash; DUMMY in the source copy. Every row in the PinsDownload column must be true and tested before this goes live.</em></p>\n<!-- /wp:paragraph -->\n\n";

	/* 14. Works on Every Device */
	$out .= $h2( 'Works on Every Device' );
	$out .= $p( 'PinsDownload runs in a browser, so it works on almost anything with an internet connection.' );
	$out .= $list( array(
		'<strong>Android</strong> &mdash; Chrome, Firefox',
		'<strong>iPhone / iPad</strong> &mdash; Safari, Chrome',
		'<strong>Windows</strong> &mdash; Chrome, Edge',
		'<strong>Mac</strong> &mdash; Safari, Chrome',
		'<strong>Linux</strong> &mdash; Firefox, Chrome',
	), false, 'pd-card-list' );

	/* 15. Is This Safe to Use? — pd-callout gives it the centered info-card look. */
	$out .= $h2( 'Is This Safe to Use?' );
	$out .= $p( 'Yes. We never ask for your Pinterest username or password. You paste a public link, we fetch the file, and nothing you download is stored on our servers afterward. We use standard analytics to see which pages are useful, the same as most websites, but your download history stays private.', 'pd-callout' );

	/* 16. Trust Badges (DUMMY) */
	$out .= $h2( 'Check Our Current Reputation' );
	$out .= $p( 'Links are wired to the real domain already. They will show &quot;no data yet&quot; until the site has been live and crawled for a few weeks &mdash; that is normal.' );
	$out .= $list( array(
		'<a href="https://transparencyreport.google.com/safe-browsing/search?url=pinsdownload.org">Google Safe Browsing</a>',
		'<a href="https://safeweb.norton.com/report?url=pinsdownload.org">Norton Safe Web</a>',
		'<a href="https://sitecheck.sucuri.net/results/pinsdownload.org">Sucuri Scanner</a>',
	), false, 'pd-card-list' );

	/* 17. Is It Legal to Download Pinterest Videos? */
	$out .= $h2( 'Is It Legal to Download Pinterest Videos?' );
	$out .= $p( "Downloading a Pinterest video for personal, offline use is generally fine. Reposting, selling, or reusing someone else's video without permission is not. Pinterest content belongs to the person who posted it, so always ask before using it publicly.", 'pd-callout' );

	/* 18. What Users Say (DUMMY — left empty, no fake reviews) */
	$out .= $h2( 'What Users Say' );
	$out .= $hint( 'No reviews published yet. Add 5-10 real ones as Paragraph blocks once you have them, or delete this heading &mdash; never fabricate names, ratings, or quotes here.' );

	/* 19. What's New */
	$out .= $h2( "What's New" );
	$out .= $p( '<strong>Aug 2026 &mdash; Launched:</strong> PinsDownload is live, with video, image, GIF, story, carousel, board, and profile downloads all working from day one.' );

	/* 20. Guides & Tips */
	$out .= $h2( 'Guides &amp; Tips' );
	$out .= $list( array(
		'How to Download a Full Pinterest Board (not published yet)',
		'Is Downloading Pinterest Content Legal? (not published yet)',
		'PinsDownload vs Other Downloaders (not published yet)',
	), false, 'pd-card-list' );

	/* 21. FAQ — native Details blocks, real accordion, zero JS */
	$out .= $h2( 'Frequently Asked Questions' );
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
	foreach ( $faq_pairs as $pair ) {
		$out .= $details( $pair[0], $pair[1] );
	}

	/* 22. Quick Answers */
	$out .= $h2( 'Quick Answers' );
	$out .= $list( array(
		"<strong>Can I download Pinterest GIFs?</strong> Yes, paste the GIF's link the same way as a video.",
		"<strong>Where do my downloads go?</strong> Your device's default Downloads folder, unless you choose another location.",
		"<strong>Does this cost anything?</strong> No, it's free with no limits on single downloads.",
		'<strong>Is this the same as a "pin saver"?</strong> Yes. PinsDownload works as a Pinterest saver too, paste any pin link and save it the same way.',
	), false, 'pd-card-list' );

	/* 23. Other Tools */
	$out .= $h2( 'Other Tools' );
	$out .= $list( array(
		'Pinterest Video Downloader (you&rsquo;re here)',
		'Pinterest Image Downloader (not published yet)',
		'Pinterest GIF Downloader (not published yet)',
		'Pinterest Story Downloader (not published yet)',
		'Pinterest Board Downloader (not published yet)',
	), false, 'pd-card-list' );

	return $out;
}
