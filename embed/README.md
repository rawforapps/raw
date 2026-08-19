# PinsDownload — Embeddable Tool (Kadence / any theme)

Three files here:

- **`pinsdownload-backend-wpcode.php`** — a standalone WPCode PHP
  Snippet with just the downloader tool, no homepage design around
  it. Use this only if you're building the page some other way
  (Kadence's own builder, Elementor, ...) and just want the tool
  dropped in somewhere. **Skip this one if you're using
  `front-page.php` below** — it already embeds the same tool.
- **`front-page.php`** — the homepage template. Copy into your active
  theme's folder, once.
- **`frontpage-editable-sections.php`** — everything shared: icons,
  CSS, the downloader tool's logic, and the full homepage copy used
  to prefill the page the first time. Install once, site-wide.

## Install (2 steps)

1. **`front-page.php`** → copy into your active theme's folder as
   exactly `front-page.php` (the hyphen matters — WordPress only
   recognizes that exact filename as the homepage template; a file
   named `frontpage.php` is silently ignored, which is the single
   most common thing that goes wrong here).
2. **`frontpage-editable-sections.php`** → install once, site-wide,
   either as its own WPCode PHP Snippet (Auto Insert → Run Everywhere)
   or as a single file dropped directly into `wp-content/mu-plugins/`
   (create that folder if it doesn't exist) via File Manager. Either
   works — pick one, not both.

Then in wp-admin: **Settings → Reading → Your homepage displays** →
"A static page", with a real Page selected (Pages → Add New if you
don't have one — the title doesn't matter, it's not shown). Open that
Page once — if it's completely empty, it auto-fills with the full
PinsDownload homepage copy the moment the page loads after both files
above are in place. Purge any page cache and reload if you don't see
it fill in.

**This auto-fill only ever happens once, ever, on purpose** — so
clearing a section later never gets silently refilled and overwrite
an edit you meant to keep. If you deliberately empty the whole page
and want the starter content back, don't just re-upload the files —
that alone won't bring it back, since what's blocking it lives in the
database, not the file. Instead, look for a blue **"PinsDownload:
Reset homepage content to the starter copy"** notice at the top of
the Dashboard, the Pages list, or the homepage Page's own edit
screen (only shown to editors, and only while a static homepage is
set) — click it, confirm, and it refills immediately.

**If you already have an older `front-page.php` or `pinsdownload-editable-sections.php` / `page-tool-landing.php` installed from
an earlier round** — delete/deactivate those. This version replaces
all of that with a much simpler two-file setup (see "Why this got
simpler" below).

## How editing works — the plain version

There is **no custom system to learn.** The homepage Page is edited
exactly like any other WordPress Page, in the normal block editor
(Pages → your homepage → Edit). Whatever you type, add, delete, or
reorder there is what shows up on the site, in that order —
`front-page.php` just calls `the_content()` on it, the same core
WordPress function every theme uses for every page, nothing custom.

- **Add an Image block** anywhere you want a picture — under any
  how-to guide, wherever.
- **Add, edit, delete, or reorder Heading / Paragraph / List / Table /
  Columns blocks** freely. Every Heading (H2) you type becomes a
  visible section title.
- **FAQ items use WordPress's own native "Details" block** — one per
  question. The block's own **Summary** field is the question; put
  the answer as a Paragraph inside it. This is a real WordPress core
  block: it expands and collapses in every modern browser with **zero
  custom JavaScript**, and nothing can drift out of sync because
  there's no custom parsing involved — it just is what it is.
- Delete a whole section (heading + everything under it) if you don't
  want it. Add a brand new one the same way — type a Heading, then
  whatever content belongs under it.

**Optional visual polish — still the same native mechanism, zero
custom code.** Every block has an "Additional CSS class(es)" field
(select the block → right sidebar → **Advanced**). Add one of these
and the block gets extra styling automatically — this is exactly how
the prefilled content below is built, so open any section in the
editor to see a live example:

| Class | On this block | Effect |
|---|---|---|
| `pd-steps` | Columns | Auto-numbers each column 01/02/03… with a connecting line (the "Quick Steps" look) |
| `pd-good` | a Column | Green-tinted "this works" card |
| `pd-bad` | a Column | Soft-red "this doesn't" card |
| `pd-pill-list` | List | Renders as rounded pill chips in a row |
| `pd-card-list` | List | Renders as a responsive card grid |
| `pd-callout` | Paragraph | Centered, bordered info card (the Safety/Legal look) |

**All of this is now handled automatically, with zero extra fields to
fill in:**

- **Every Heading (H2) gets a small icon badge above it**, cycling
  through 8 icons by position — purely decorative, no editor field to
  set.
- **Sections alternate a light tinted background** (added by a small
  script that groups the content between one H2 and the next — if
  JavaScript is off for any reason, the page still renders fine, just
  without the alternating tint).
- **List items in `pd-card-list`/`pd-pill-list` get a small icon or
  number badge**, and column cards, the comparison table, and the FAQ
  accordion all get hover states and a bit more depth.
- **In a Table block, the second column is treated as "our product"**
  and gets a highlighted band down the table (the classic
  comparison-table look) — put your product's column second. Exact
  `Yes`/`No` cells in that column also get turned into a small pill
  automatically (deliberately not colored green/red for "good/bad" —
  a "No" answer is sometimes the right one, e.g. "Login needed: No").

**Fixed, not part of the editable content** — the site header/footer
(your theme's own); the Hero's eyebrow, H1, and subtitle (one H1 per
page, tightly bound to the tool right under it); the downloader tool
itself (`id="pdl-tool"` — a working form + PHP logic, not text); and
the closing "Back to the Downloader" button. Everything else on the
page is yours.

## What's prefilled on first load

The full homepage copy — feature strip (pill chips), the numbered
three-step explainer, all four device how-to guides (with an
image-block hint under each, ready for a real screenshot), what
works/doesn't (green/red card columns), why people use it (two rows
of card-style Columns), everything else you can download (card
grid), what it is / what it's for / is it legal (callout cards), the
comparison table (Table block, marked REVIEW REQUIRED — verify every
claim before launch), device compatibility (card grid), safety, trust
badges (real links, already pointed at pinsdownload.org), what's new,
guides & tips, all 12 FAQ items (as native Details blocks), quick
answers, and other tools. Two things are deliberately **not**
prefilled with fake content, per the source copy's own DUMMY note:

- **What Users Say** — left as a one-line instruction only. Add 5–10
  real reviews as Paragraph blocks once you have them, or delete the
  heading. Never fabricated.
- Anything the source copy flags DUMMY (the comparison table, trust
  badges) is prefilled with the real draft copy but marked for review
  — read the note right under each before publishing.

## How it works (the tool itself)

The form POSTs back to the same page. On submit:

1. Validates the submitted URL is a real URL on `pinterest.com` or
   `pin.it` (after stripping `www.`).
2. Calls a third-party resolver API, `https://pintsave.net/api/fetch-media`,
   with the Pinterest URL, via `wp_remote_post()`.
3. Renders whatever media entries come back — image or video, with
   quality/resolution/duration when the API provides them — plus a
   direct download link per item.

**Trade-offs to know about:** every lookup depends on `pintsave.net`
being up (no fallback extraction path); it's single-pin only (no
board/profile bulk-download or ZIP, unlike an earlier build of this
tool); and there's no caching of your own on top of pintsave.net's.

## Why this got simpler

Earlier versions of this tried to make specific named sections
editable through a custom "zone" system — Heading markers the code
would detect and split content around. It kept breaking in practice
(a hidden custom post type nobody could find; a hero that ended up
mid-page because the marker logic didn't match what was actually on
the page) for one underlying reason: it was solving a problem
WordPress already solves natively. This version deletes all of that
in favor of the plainest possible mechanism — `the_content()` for
everything, and the native Details block for the one part (FAQ) that
needed some structure. Fewer moving parts, nothing to keep in sync,
nothing to explain beyond "edit the page like a page."

## Additional pages (`pages/` folder)

Beyond the homepage, the site now has one page per tool (Image
Downloader, Story Downloader, ...), each sharing the same core engine
(`pinsdownload-core.php`, already deployed as an mu-plugin — a further
evolution of `frontpage-editable-sections.php` above, with more icons,
a smarter card-icon auto-mapper, and other polish). Each page needs
exactly 2 files, both in `pages/`:

- **`page-<slug>.php`** — a WordPress Page Template (has a
  `Template Name:` header comment, so it shows up in the Page
  Attributes → Template dropdown in wp-admin). Copy into the active
  theme's folder once per page. Hero copy, the CTA, and the JSON-LD
  schema (`SoftwareApplication` + `HowTo` + `FAQPage`, built live from
  that page's own Details blocks, + `BreadcrumbList`) are specific to
  that one page; everything else — icons, styles, the tool's logic —
  comes from the shared core engine.
- **`<slug>-content.html`** — the page's body copy as raw Gutenberg
  block markup. Create a real WordPress Page, select the matching
  Template in Page Attributes, open the block editor's Code Editor
  (⋮ menu → "Code editor"), paste this in, and switch back to the
  visual editor. Uses the same utility classes documented above
  (`pd-steps`, `pd-good`/`pd-bad`, `pd-pill-list`, `pd-card-list`,
  `pd-callout`) plus a few semantic `pd-section-*` labels on headings
  (`pd-section-how`, `pd-section-faq`, ...) for consistency — those
  labels aren't required for anything to render correctly, they're
  just there so every page's sections are named the same way.

## After any file change: purge the cache

If this site runs a page or object cache (e.g. LiteSpeed Cache),
purge it after uploading/saving before you re-test — otherwise you
may be looking at the old cached page even though the new code is
already live.
