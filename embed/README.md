# PinsDownload — Embeddable Tool (Kadence / any theme)

Three deliverables here, pick based on what you're doing:

- **`pinsdownload-backend-wpcode.php`** — a WPCode PHP Snippet. Paste
  it into an existing page/theme via WPCode when you just want the
  downloader tool itself, dropped into a page you're building some
  other way (Kadence, Elementor, whatever).
- **`front-page.php`** — a complete, ready-to-upload WordPress
  homepage template. Drop it straight into an active theme's folder
  as `front-page.php` and it becomes the whole homepage: full
  PinsDownload homepage copy, a premium Pinterest-red-accented design
  system, and this same downloader tool embedded live in the hero —
  not a placeholder. See "The homepage template" below.
- **`pinsdownload-editable-sections.php`** — a companion WPCode PHP
  Snippet that pairs with `front-page.php`. Makes five specific parts
  of the homepage (How to Use, Images, Explanations, Features, FAQ)
  editable — text, images, adding/removing items — **directly on your
  existing homepage Page**, the same one you already open under Pages
  in wp-admin. No separate admin screen to go find. Everything else
  on the page (hero + tool, header, footer, and every other structured
  section) stays fixed. See "Editing content in Gutenberg" below.

All three files are self-contained: no theme dependency, no REST API,
nothing else to install beyond what's described here.

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
2. Also install `pinsdownload-editable-sections.php` as its own
   WPCode PHP Snippet (Auto Insert → Run Everywhere), the same way
   you'd install the tool snippet. `front-page.php` calls functions
   this file defines (`pd_render_content_zone()`, `pd_render_faq_zone()`,
   `pd_get_faq_pairs()`) — without it active, those sections just show
   a small notice to logged-in editors instead of a fatal error, but
   nothing will be editable until it's active.
3. In wp-admin: **Settings → Reading → Your homepage displays** must
   be "A static page", with a real Page chosen (Pages → Add New if you
   don't have one yet — title doesn't matter). That Page's own content
   is where the five editable zones below live; `front-page.php`
   overrides everything else about how that Page looks, but still
   reads its content for those five zones specifically.
4. Open that Page in wp-admin and reload it once. If it's completely
   empty, the snippet fills it in automatically with starter content
   the first time it loads after being installed — refresh if you
   opened it before installing the snippet.
5. Purge any page cache after uploading.

**What's on it:**

The full PinsDownload homepage copy from `Homepage_Content_PinsDownload.md`
— hero, format/quality strip, three-step explainer, what works/
doesn't, content-type grid, comparison table, device compatibility,
safety, trust badges, testimonials, changelog, guides, quick answers,
other tools, and a final CTA that scrolls back to the tool — plus five
sections (How to Use, Images, Explanations, Features, FAQ) that pull
their content from wp-admin instead of being hard-coded. See "Editing
content in Gutenberg" below for what's editable and how.

- The downloader tool itself, embedded directly in the hero
  (`id="pdl-tool"`) — the same pintsave.net-backed logic as
  `pinsdownload-backend-wpcode.php` above, not a placeholder. One
  less thing to install separately.
- A scoped `.pd-` prefixed design system (CSS variables, Pinterest-red
  accents used sparingly, cards, a feather-style inline SVG icon set,
  an accessible FAQ accordion, scroll-reveal — all in plain CSS/JS, no
  framework, no external library). The same tokens style native
  Gutenberg block output (`.wp-block-*`) inside the editable zones, so
  content typed in wp-admin matches the rest of the page automatically.
- `SoftwareApplication`, `HowTo`, and `FAQPage` JSON-LD schema. The
  FAQ schema is generated from the same Heading/Paragraph pairs the
  FAQ zone displays — edit the FAQ in wp-admin and the schema updates
  with it, no separate place to keep in sync.

**Left as placeholders, on purpose (per the content doc's own DUMMY
notes) — fill these in before launch:**

- **Tutorial screenshot** for the fixed three-step explainer section
  — the placeholder `<div>` carries an HTML comment with the exact
  filename, pixel size, and aspect ratio to use when you swap it in.
  The App/Computer/iPhone/Android guide screenshots now live inside
  the "How to Use" Gutenberg zone instead (see below) — add those as
  ordinary Image blocks in wp-admin.
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

## Editing content in Gutenberg

There is **no separate admin screen** for this. You edit the same
homepage Page you already have open in wp-admin (Pages → your
homepage → Edit) — the normal block editor, exactly as it looks for
any other Page. `front-page.php` reads that Page's own content, but
only for five specific zones; everything else about how that Page
renders (header, hero, tool, footer, and every other section) is
fixed in the template regardless of what's on the Page.

The five zones are marked by **Heading blocks (H2)** with these exact
names, typed as ordinary content on the page, in any order you like:

```
How to Use
Images
Explanations
Features
FAQ
```

Whatever you put underneath one of these headings — and above the
next one — becomes that section's content. The marker heading itself
is never shown on the live page (front-page.php prints its own
visible heading for each section); it only tells the code where one
zone ends and the next begins. The first time the page loads after
installing the snippet, if it's completely empty, all five markers
and their starter content get filled in automatically — reload the
page editor after installing if you don't see this yet.

How to structure the content under each marker so it renders
correctly:

| Zone (H2 marker) | Shows up as | How to structure it underneath |
|---|---|---|
| **How to Use** | A section right after the 3-step explainer | A Heading (H3) block per guide (e.g. "Downloading on a Computer"), then a numbered List block, then optionally an Image block for that guide's screenshot. Repeat per guide. Add, remove, or reorder freely. |
| **Images** | A standalone gallery section after "How to Use" | Empty by default — add Image or Gallery blocks for product shots or extra screenshots. Leave it empty and the section just won't show anything extra. |
| **Explanations** | Replaces the old "What Is / What For / Legal" sections | A Heading (H3) block per topic, then a Paragraph block under it. Add new topics the same way. |
| **Features** | The "Why People Use This Tool" grid | Two rows of a **Columns** block, each column holding a Heading (H3) + Paragraph — that's what turns into the card grid. Add a column (or a whole new Columns block) for a new feature; the card styling applies automatically. |
| **FAQ** | The FAQ accordion | **Important:** each question needs a Heading (H3) block immediately followed by a Paragraph block (its answer) — that exact Heading→Paragraph pairing is what becomes one accordion item **and** one entry in the FAQPage schema. Anything else (a List, an Image, two Paragraphs in a row) is ignored by the accordion, so keep to that pattern. Add/remove/reorder pairs freely. |

Note the marker headings are **H2** and everything under them is
**H3** — that's how the code tells "this is a new zone" apart from
"this is just a subheading inside the current zone."

Everything **not** in that list — the hero and tool, header, footer,
feature strip, works/doesn't cards, content-type grid, comparison
table, device row, safety card, trust badges, testimonials, timeline,
guides/other-tools cards, quick answers, and the final CTA — is
intentionally fixed in `front-page.php` and not editable from
wp-admin. Those are either tied to the tool itself, or built as
precise custom components (accordions, tables, badge grids) where
open-ended editing risks breaking the layout rather than just
updating copy. Say the word if you want any of those opened up too.

## "Why does this page still show up as its own link?"

Setting a Page as your static homepage (Settings → Reading) does
**not** remove it from navigation menus automatically — WordPress
still treats it as a normal Page with its own slug/URL underneath the
hood, it just also happens to be what loads at your domain root `/`.
Two separate things can make it look like it's "showing up" somewhere
it shouldn't:

- **It's in a nav menu as its own item.** If a menu (Appearance →
  Menus, or the block-theme Navigation block) has this page added to
  it by name, remove that menu item — visiting `/` already shows it,
  a duplicate "Pinterest Downloader" link pointing to the same place
  is redundant. Point a "Home" menu item at `/` instead if you want
  one.
- **Its own slug URL still technically works.** e.g.
  `pinsdownload.org/pinterest-downloader/` loads the same content as
  `pinsdownload.org/`. This is normal WordPress behavior for any Page
  used as a static front page, not a bug — WordPress adds a canonical
  tag pointing search engines at `/` so it isn't treated as duplicate
  content. If it bothers you visually, you can rename the Page's slug
  to `home` under its Permalink settings; it won't change anything
  about how the homepage works.

If neither of those matches what you're seeing, send a screenshot of
exactly where the link/page shows up and that'll narrow it down fast.

## After any snippet change: purge the cache

If this site runs a page or object cache (e.g. LiteSpeed Cache),
purge it after saving a snippet change before you re-test — otherwise
you may be looking at the old cached page even though the new code is
already live.
