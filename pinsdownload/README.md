# PinsDownload — WordPress theme

One Pinterest extraction engine, reused on the homepage and on every
content-type landing page. Built from the Tool Blueprint, Homepage
Blueprint, and finalized homepage copy. No plugin required — the tool
engine, REST API, download proxy, PWA, and mood board are all bundled
in this theme.

**Design system**: Pinterest-red/pink palette, card-based sections on
alternating bands, self-hosted Baloo 2 (headings) + Inter (body) fonts
(`assets/fonts`, both SIL Open Font License, license files included,
no external font CDN), an inline SVG icon set (`inc/icons.php`, no icon
font dependency), scroll-reveal + animated FAQ accordion + sticky
header (`assets/js/ui.js`). This was a markup/CSS/JS-only pass — every
piece of copy on every page is byte-for-byte the same as the source
content docs; only how it's presented changed.

## Install

1. Zip the `pinsdownload` folder as-is, so the archive's top level is
   a single `pinsdownload/` directory containing `style.css`,
   `functions.php`, etc. (WordPress's uploader expects exactly this —
   one wrapper folder, not the files loose at the zip root.)
2. WordPress admin → Appearance → Themes → Add New → Upload Theme.
3. Activate. The homepage works immediately at `/`, front-page.php is
   used automatically regardless of the Settings → Reading choice.
   **This is why the homepage never appears in Pages → All Pages** —
   it isn't a Page post at all, it's a template file that WordPress
   renders directly for the site root. That's normal, not a bug: there
   is nothing to click into and edit there, because the homepage's
   content lives in code (`front-page.php`), not in the database. If
   you want the homepage's copy itself editable from wp-admin the way
   a normal Page is, that needs a deliberate rebuild of that one file
   into a Page-backed template — say so and it can be done, but every
   other page in this theme (legal pages, GIF/Image downloaders, etc.)
   already works that way.
4. Appearance → Menus: create a Primary menu and a Footer menu and
   assign them to the "Primary Menu" / "Footer Menu" locations. Add
   your legal pages (Privacy Policy, DMCA, etc.) once you've created
   them (Pages → Add New, default template, just write the content).
5. Requires PHP's cURL extension for the streaming download proxy
   (near-universal on real hosting; if missing, downloads fall back to
   a plain redirect instead of a forced Save-As).

## Importing the Phase 1 content (GIF/Image pages, legal pages)

`content-import/pinsdownload-phase1-content.xml` (repo root, next to this
theme folder) is a standard WordPress export file (WXR) containing:

- **Pinterest GIF Downloader** and **Pinterest Image Downloader** —
  already set up as Tool Landing Pages with their own title tag, meta
  description, format strip, intro paragraph, and schema-ready FAQ.
  Nothing to configure, they publish exactly as written.
- **About Us, Contact Us, Privacy Policy, Terms of Service, DMCA
  Policy** — standard pages with their own title tags.

To import: after activating the theme, go to Tools → Import →
WordPress (install the "WordPress" importer plugin if prompted), then
upload the XML file. Assign the imported content to an existing user
(or create one) when asked. All 7 pages appear immediately, and the
homepage's "Other Tools" section will start linking to the two new
downloader pages automatically (no menu setup required for that part,
though you should still add them to your Primary/Footer menu for
navigation).

**Before publishing DMCA Policy**: it still has bracketed placeholders
(`[Name / Company]`, `[dmca@pinsdownload.org]`, `[physical address]`).
Register a designated agent at
[dmca.copyright.gov](https://dmca.copyright.gov) and fill in those
exact details, otherwise the page doesn't give you the legal
protection it's meant to (this was flagged in the source content doc
too, it's not something code can fill in for you).

## Adding a new content-type landing page

This is the reusable piece: one engine, many thin pages, per the Tool
Blueprint's "one engine, not eleven tools" architecture.

1. Pages → Add New.
2. Page Attributes box (right sidebar) → Template → **Tool Landing
   Page**.
3. A **PinsDownload Landing Page Settings** box appears below the
   editor. Pick the content type (video, image, GIF, board, profile,
   etc.), write one short intro paragraph, and optionally set a custom
   format strip (e.g. "Native GIF format · Full animation kept...")
   and a page-specific FAQ (one `Q:` line, one or more `A:` lines,
   blank line between pairs — this becomes both the visible FAQ
   accordion and FAQPage schema automatically).
4. A separate **SEO Title & Description** box lets you set the exact
   `<title>` tag and meta description for this page, matching each
   content doc's "Technical foundation" section.
5. Set the page title (this becomes the H1) and, optionally, add extra
   content in the main editor (it renders below the format strip).
6. Publish. The page automatically gets the tool box, the shared
   "What Works / What Doesn't" table, trust badges, an "Other Tools"
   list of every other landing page, and breadcrumb + SoftwareApplication
   + HowTo + BreadcrumbList schema (plus FAQPage schema if you filled
   in the FAQ field). It also starts showing up in the homepage's
   "Other Tools" section and in any menu you add it to — nothing else
   to wire up.

Ordinary content pages (Privacy Policy, DMCA, "Is It Legal," About)
just use the default page template — Pages → Add New, leave the
template as Default, write and publish.

Blog posts (guides, "how to" articles) use the standard Posts screen.
The homepage's "Guides & Tips" section is hidden until at least one
post is published, then shows your 3 most recent automatically.

## Mood board

Create one Page with template **Moodboard** (suggested slug:
`/moodboard/`) and add it to a menu. "Save to Moodboard" buttons on
every tool result save to that visitor's own browser storage — no
account, no database. This is the one feature in this build that goes
beyond the original Tool Blueprint (added per your explicit request,
inspired by a competitor's mood-board maker); it's scoped as a basic
version, not a full editor.

## Ads (Option B: minimal, disclosed ads)

The homepage copy already states "a small number of ads keep this
tool free" and that they never sit on the Download button. No ad
network code is wired in (that needs your own AdSense/ad-network
account), but two hook points exist so ads never get styled to look
like the download flow:

```php
add_action( 'pinsdownload_ad_slot', function ( $position ) {
    if ( 'homepage_after_strip' === $position ) {
        echo '<div class="pd-ad-slot">/* your ad code */</div>';
    }
} );
```

Fires after the format strip on the homepage (`homepage_after_strip`)
and on every Tool Landing Page (`landing_after_strip`) — both are
below the tool box, never on top of it.

## Adding real images to the empty placeholder slots

Every dashed placeholder box on the site (how-to screenshots, the
"what is" illustration, testimonial avatars, etc.) is an empty slot on
purpose, not a stock photo. Two different ways to fill them in,
depending on which page:

**Homepage + the works/doesn't table (shown on every page)** — these
are fixed, code-level slots with no page content behind them, so they
get a dedicated native picker: **Appearance → Customize → PinsDownload
Images**. Ten labeled slots, each a standard WordPress Media Library
control — upload a new file or pick an existing one, hit Publish. Any
slot left empty just keeps showing its placeholder; nothing breaks.

**Tool Landing Pages (GIF Downloader, Image Downloader, and any new
one you add)** — their content comes from the normal WordPress editor
(Pages → [that page] → edit). Just insert an Image block wherever you
want a screenshot, in the flow of the text under whichever heading
it belongs to, and publish. The page automatically detects it and
moves it into that section's image slot instead of showing the dashed
placeholder — no shortcode, no special field, it's picked up by
whatever real `<img>` (or image block) you already put there.

## Before you go live: things that need YOUR input, not code

- **Trust badges, comparison table, testimonials**: all three are
  marked `DUMMY` in `front-page.php` (view source, search `DUMMY`).
  Trust badge links are already wired to `pinsdownload.org`; they'll
  show "no data yet" for a few weeks after launch, that's normal.
  Comparison table claims must be verified against the live tool.
  Testimonials must be replaced with real reviews or deleted.
- **PWA icons**: `assets/icons/icon-192.png` and `icon-512.png` are
  solid-color placeholders (this build environment had no image
  tooling to generate real ones). Swap them for real branded icons
  before launch — same filenames, same dimensions.
- **Pinterest extraction engine — needs live verification.** This
  environment's network access could not reach pinterest.com to test
  against live pages, so `inc/class-pinsdownload-resolver.php` was
  built against the well-documented, stable technique long-standing
  open-source Pinterest tools use (reading Pinterest's own embedded
  `__PWS_DATA__` JSON and public resource-API endpoints — no login,
  no credentials, no access-control bypass). Test it against real
  pin/board/profile/story/carousel URLs on your actual host once it's
  live. Every place in that file worth checking first if extraction
  breaks is commented `Verify:`. Multi-pin-share links
  (`type=share` in the meta box) don't have a confirmed URL pattern
  yet — paste a real example share link and I can wire up its
  detector.
- **Monetization wording**: copy already reflects Option B (disclosed
  minimal ads), as chosen.

## Architecture notes

- `inc/class-pinsdownload-resolver.php` — the one engine. URL
  detection/routing, fetch, and normalization for every content type.
- `inc/rest-api.php` — `POST /wp-json/pinsdownload/v1/resolve`, the
  only endpoint the frontend calls to resolve a pasted link.
- `inc/stream.php` — streams the actual media file through the server
  (never stored, never buffered in full) so the download button forces
  a real Save-As regardless of Pinterest CDN CORS behavior.
- `assets/js/tool.js` — one frontend for every content type: auto-fetch
  on paste, live progress, quality selector, bulk checkboxes, ZIP via
  bundled JSZip, confirmation state, Add-to-Home-Screen prompt after
  first successful download.
- `page-templates/template-tool-landing.php` + `inc/metaboxes.php` —
  the reusable landing-page template described above.
- `inc/pwa-root-files.php` — serves `/sw.js` and `/manifest.json` from
  the site root (from inside the theme folder) so the service worker
  can control the whole site, not just the theme directory.
- `inc/seo.php` — outputs the exact per-page `<title>` and meta
  description from the SEO meta box (homepage falls back to the fixed
  blueprint copy).
- `inc/metaboxes.php` — the two wp-admin meta boxes described above.
- `template-parts/trust-badges.php`, `template-parts/other-tools.php`,
  `template-parts/works-doesnt.php` — shared blocks reused on the
  homepage and every Tool Landing Page.
