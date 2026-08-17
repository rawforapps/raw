# PinsDownload — WordPress theme

One Pinterest extraction engine, reused on the homepage and on every
content-type landing page. Built from the Tool Blueprint, Homepage
Blueprint, and finalized homepage copy. No plugin required — the tool
engine, REST API, download proxy, PWA, and mood board are all bundled
in this theme.

## Install

1. Zip the `pinsdownload` folder (the zip's top level must contain
   `style.css`, `functions.php`, etc. directly — not a nested folder).
2. WordPress admin → Appearance → Themes → Add New → Upload Theme.
3. Activate. The homepage works immediately at `/`, front-page.php is
   used automatically regardless of the Settings → Reading choice.
4. Appearance → Menus: create a Primary menu and a Footer menu and
   assign them to the "Primary Menu" / "Footer Menu" locations. Add
   your legal pages (Privacy Policy, DMCA, etc.) once you've created
   them (Pages → Add New, default template, just write the content).
5. Requires PHP's cURL extension for the streaming download proxy
   (near-universal on real hosting; if missing, downloads fall back to
   a plain redirect instead of a forced Save-As).

## Adding a new content-type landing page

This is the reusable piece: one engine, many thin pages, per the Tool
Blueprint's "one engine, not eleven tools" architecture.

1. Pages → Add New.
2. Page Attributes box (right sidebar) → Template → **Tool Landing
   Page**.
3. A **PinsDownload Landing Page Settings** box appears below the
   editor. Pick the content type (video, image, GIF, board, profile,
   etc.) and write one short intro paragraph.
4. Set the page title (this becomes the H1) and, optionally, add extra
   content in the main editor (it renders below the format strip).
5. Publish. The page automatically gets the tool box, the shared
   "What Works / What Doesn't" table, and breadcrumb + schema markup.
   It also starts showing up in the homepage's "Other Tools" section
   and in any menu you add it to — nothing else to wire up.

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
