# PinsDownload — Embeddable Tool (Kadence / any theme)

Two deliverables here, pick based on what you're doing:

- **`pinsdownload-backend-wpcode.php`** — a WPCode PHP Snippet. Paste
  it into an existing page/theme via WPCode when you just want the
  downloader tool itself, dropped into a page you're building some
  other way (Kadence, Elementor, whatever).
- **`front-page.php`** — a complete, ready-to-upload WordPress
  homepage template. Drop it straight into an active theme's folder
  as `front-page.php` and it becomes the whole homepage: full
  PinsDownload homepage copy (all 23 sections, word for word), a
  premium Pinterest-red-accented design system, and this same
  downloader tool embedded live in the hero — not a placeholder. See
  "The homepage template" below.

Both files are self-contained: no theme dependency, no REST API,
nothing else to install alongside them.

## Install the tool alone (1 step, ~1 minute)

`pinsdownload-backend-wpcode.php`

WPCode → Add Snippet → Add Your Custom Code → Code Type: **PHP
Snippet** → paste the whole file → Insertion:

- **Auto Insert → Run Everywhere** if you want it to just appear
  wherever your theme's Run Everywhere hook fires, or
- **Shortcode** — leave insertion as "Shortcode Only" and place the
  snippet with the shortcode WPCode assigns it (shown in the
  snippet's editor, looks like `[wpcode id="XXXX"]`) inside a
  Shortcode block on whichever page you want the tool on.

Save & Activate. If you installed an earlier version of this
snippet (the one that scraped pinterest.com pages directly, with
`/wp-json/pinsdownload/v1/...` routes and a separate
`pinsdownload-widget.html` Custom HTML block), **delete that snippet
and the HTML block entirely** — this version replaces both with a
single file and doesn't register any REST routes.

## How it works

The form POSTs back to the same page. On submit, the snippet:

1. Validates the submitted URL is a real URL on `pinterest.com` or
   `pin.it` (after stripping `www.`).
2. Calls a third-party resolver API, `https://pintsave.net/api/fetch-media`,
   with the Pinterest URL, via `wp_remote_post()`.
3. Renders whatever media entries come back — image or video, with
   quality/resolution/duration when the API provides them — plus a
   direct download link per item.

This is a deliberate architecture change from the previous version.
The old snippet tried to extract media by fetching the Pinterest pin
page itself (HTML scrape, then Pinterest's internal resource API as
a fallback) and could never be verified end-to-end because this
sandbox's network cannot reach `pinterest.com` at all. Routing the
actual extraction through pintsave.net's API sidesteps that: the
WordPress site just needs outbound HTTPS to `pintsave.net`, which
this sandbox also cannot reach, but which is a normal, unrestricted
outbound host for a live WordPress server — and it's the version
confirmed working there.

## Known trade-offs of this approach

- **Third-party dependency.** Every lookup calls `pintsave.net`. If
  that service is down, rate-limits you, or changes its response
  shape, the tool breaks until pintsave.net is fixed — there's no
  fallback extraction path in this file. Watch for `$pdl_error`
  showing "Unable to connect to the media service" or "The media
  service returned an error" in normal use; that's this dependency,
  not a bug in the snippet.
- **Single pin only.** Unlike the previous build, there's no
  board/profile bulk-download or ZIP export here — one URL in, one
  result set out. Say the word if you want that layered back on top
  of this API.
- **No caching/rate limiting** of your own on top of pintsave.net's.
  If this page gets heavy traffic, you're sending that same volume
  of requests to their API.

## The homepage template (`front-page.php`)

A single, self-contained WordPress homepage template — no page
builder, no separate widget or shortcode install. It integrates with
your active theme via `get_header()` / `get_footer()`, so the theme's
own header, nav, and footer stay exactly as they are; this file only
owns the content between them.

**Install:**

1. Copy `front-page.php` into your active theme's folder, e.g.
   `/wp-content/themes/YOUR-THEME/front-page.php`.
2. In wp-admin: **Settings → Reading → Your homepage displays**. If
   your theme already resolves `front-page.php` automatically for the
   site root (most themes do once "A static page" is selected, even
   with the front page dropdown left on the theme's default), that's
   it. Otherwise pick or create a blank page and set it as the front
   page — the theme file still takes over the layout.
3. Purge any page cache after uploading.

**What's on it:**

- All 23 homepage sections from `Homepage_Content_PinsDownload.md`,
  reproduced word for word — hero, format/quality strip, four
  device-specific how-to guides, what works/doesn't, why-use grid,
  content-type grid, "what is / what for" sections, comparison table,
  device compatibility, safety, trust badges, legal, testimonials,
  changelog, guides, FAQ (with accordion), quick answers, other
  tools, and a final CTA that scrolls back to the tool.
- The downloader tool itself, embedded directly in the hero
  (`id="pdl-tool"`) — the same pintsave.net-backed logic as
  `pinsdownload-backend-wpcode.php` above, not a placeholder. One
  less thing to install separately.
- A scoped `.pd-` prefixed design system (CSS variables, Pinterest-red
  accents used sparingly, cards, split layouts, a feather-style inline
  SVG icon set, an accessible FAQ accordion, scroll-reveal — all in
  plain CSS/JS, no framework, no external library).
- `SoftwareApplication`, `HowTo`, and `FAQPage` JSON-LD schema, built
  from the same FAQ array the page displays (one source of truth).
  No `aggregateRating`/review schema — there are no real reviews yet.

**Left as placeholders, on purpose (per the content doc's own DUMMY
notes) — fill these in before launch:**

- **Tutorial screenshots** (6 total: how-to, app, computer, iPhone,
  Android, "what is" illustration) — each placeholder `<div>` carries
  an HTML comment with the exact filename, pixel size, and aspect
  ratio to use when you swap it in.
- **Testimonials** (§18) — the source copy's three reviews are
  fabricated/DUMMY. This template does **not** publish them; it shows
  three neutral "Real user review will appear here" cards instead.
  Replace with 5–10 genuine reviews, or delete the section.
- **Trust badges** (§16) — real Google Safe Browsing / Norton / Sucuri
  links, already wired to `pinsdownload.org`, shown with a
  "Verification pending" status until the domain has scan history.
- **Guides & Tips / Other Tools** (§20, §23) — non-clickable cards
  until those article/tool pages actually exist; each has an `<!--
  ARTICLE URL PLACEHOLDER -->` comment marking where to add the link.
- **Comparison table** (§13) — flagged `REVIEW REQUIRED` in a comment;
  every claim needs testing against the live tool before publishing.

## After any snippet change: purge the cache

If this site runs a page or object cache (e.g. LiteSpeed Cache),
purge it after saving a snippet change before you re-test — otherwise
you may be looking at the old cached page even though the new code is
already live.
