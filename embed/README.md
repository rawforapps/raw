# PinsDownload — Embeddable Tool (Kadence / any theme)

Four files here. **Only one of them gets installed per-page-you-add —
the other three are installed once, ever, and stay shared:**

- **`pinsdownload-backend-wpcode.php`** — a standalone WPCode PHP
  Snippet with the downloader tool only (no homepage design around
  it). Use this if you're NOT using the templates below at all —
  dropping just the tool into a page you're building some other way
  (Kadence, Elementor, whatever). Skip this one if you're using
  `front-page.php` / `page-tool-landing.php`, which already embed the
  same tool.
- **`pinsdownload-editable-sections.php`** — the shared "core": icons,
  CSS, the downloader tool logic, and the editable-content-zone
  system. **Install this once, site-wide**, as its own WPCode PHP
  Snippet (Auto Insert → Run Everywhere) — it's what your question was
  really about, see "One core file, many pages" below for the full
  answer.
- **`front-page.php`** — the homepage template. Copy into your active
  theme's folder once; WordPress uses it automatically for the site's
  static front page.
- **`page-tool-landing.php`** — a **reusable page template** for every
  *additional* tool page you create (Image Downloader, GIF Downloader,
  Story Downloader, ...). Also copied into the theme folder just
  once — then for each new Page you create, pick "PinsDownload Tool
  Landing" from Page Attributes → Template in wp-admin. No new PHP
  file per page.

All four are self-contained: no page builder dependency, no REST API,
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

## One core file, many pages

This answers the actual question directly: **`pinsdownload-editable-sections.php`
is universal — one copy, installed once, works for every page.**
You do NOT create a new `.php` file per page, and you do NOT paste
per-page copies into `mu-plugins` — that would mean re-pasting the
same ~700 lines every time you add a tool page, and re-pasting them
*again* in every copy whenever something needs fixing. Instead:

- **Install `pinsdownload-editable-sections.php` exactly once** — as
  a WPCode PHP Snippet (Auto Insert → Run Everywhere) is the simplest,
  matching how you already installed the tool snippet. Pasting it into
  `wp-content/mu-plugins/` (via File Manager, as a single `.php` file
  directly in that folder — mu-plugins doesn't support subfolders for
  auto-loading) works exactly as well and survives theme switches,
  but isn't required — either is fine, just pick one, not both.
- **`front-page.php` and `page-tool-landing.php` are also each
  installed once** — copied into the active theme's folder. Neither
  is duplicated per page either.
- **What *is* per-page is content, not code.** Every Page that uses
  the "PinsDownload Tool Landing" template (Page Attributes → Template
  in wp-admin, after copying `page-tool-landing.php` into the theme
  once) gets its own independent set of editable zones — the same 19
  H2-marker names as the homepage (see the table below), but each
  page's "How to Use", "FAQ", etc. only affects that page. Create
  "Pinterest Image Downloader" and "Pinterest GIF Downloader" as two
  Pages with that template, and they're two separate, independently
  editable pages sharing the same design/tool/code.
- **The page's own title becomes its H1 automatically** — name the
  Page "Pinterest Image Downloader" and that's what shows in the hero,
  no code edit needed. Set that Page's **Excerpt** (Page editor →
  Page panel → Excerpt; enable the panel from the "⋮" menu →
  Preferences → Panels if it's hidden) for the hero subtitle, or leave
  it unset for a generic fallback line.
- **New Tool Landing pages start with a lightweight skeleton**, not
  fabricated copy — every marker heading plus a one-line hint of what
  to put underneath (e.g. "Add a Table block comparing this tool to
  alternatives"), since this template doesn't know what a given tool
  page is about. Open the new page in wp-admin once after creating it
  to see the skeleton auto-fill in.

## The homepage template (`front-page.php`)

A single WordPress homepage template — no page builder, no separate
widget or shortcode install. It integrates with your active theme via
`get_header()` / `get_footer()`, so the theme's own header, nav, and
footer stay exactly as they are; this file only owns the content
between them. (`page-tool-landing.php`, for additional tool pages, is
installed the same way — see "One core file, many pages" above.)

**Install:**

1. Copy `front-page.php` into your active theme's folder, e.g.
   `/wp-content/themes/YOUR-THEME/front-page.php`.
2. Also install `pinsdownload-editable-sections.php` — **once,
   site-wide** (see "One core file, many pages" above for exactly what
   that means and why it's not per-page). `front-page.php` calls
   functions this file defines — without it active, sections show a
   small notice to logged-in editors instead of a fatal error, but
   nothing works until it's active.
3. In wp-admin: **Settings → Reading → Your homepage displays** must
   be "A static page", with a real Page chosen (Pages → Add New if you
   don't have one yet — title doesn't matter). That Page's own content
   is where the editable zones below live; `front-page.php` overrides
   everything else about how that Page looks, but still reads its
   content for those zones specifically.
4. Open that Page in wp-admin and reload it once. If it's completely
   empty, the snippet fills it in automatically with starter content
   the first time it loads after being installed — refresh if you
   opened it before installing the snippet.
5. Purge any page cache after uploading.

## Adding another tool page (`page-tool-landing.php`)

1. Copy `page-tool-landing.php` into the same theme folder as
   `front-page.php` (one-time, not per page).
2. In wp-admin: **Pages → Add New**. Title it exactly what the page
   is about, e.g. "Pinterest Image Downloader" — that's what becomes
   the H1. Optionally set an Excerpt for the subtitle line.
3. In the **Page** panel on the right, under **Template**, choose
   **PinsDownload Tool Landing**. Publish.
4. Reload the page in wp-admin — it auto-fills with the marker-heading
   skeleton described above. Fill in each zone's real content the
   same way as the homepage (see "Editing content in Gutenberg"
   below — it applies to every page using this template, not only the
   homepage).
5. Repeat for each additional tool page. Every one of them shares the
   same `pinsdownload-editable-sections.php` core — nothing to
   reinstall.

**What's on it:**

The full PinsDownload homepage copy from `Homepage_Content_PinsDownload.md`
— hero, feature strip, three-step explainer, how-to guides, what
works/doesn't, content-type grid, comparison table, device
compatibility, safety, trust badges, testimonials, changelog, guides,
FAQ, quick answers, other tools, and a final CTA that scrolls back to
the tool. Nearly every one of those pulls its content from wp-admin
instead of being hard-coded — see "Editing content in Gutenberg"
below for the full list of what's editable and how.

- The downloader tool itself, embedded directly in the hero
  (`id="pdl-tool"`) — the same pintsave.net-backed logic as
  `pinsdownload-backend-wpcode.php` above, not a placeholder. One
  less thing to install separately.
- A scoped `.pd-` prefixed design system (CSS variables, Pinterest-red
  accents used sparingly, cards, a feather-style inline SVG icon set,
  an accessible FAQ accordion, scroll-reveal — all in plain CSS/JS, no
  framework, no external library). The same tokens style native
  Gutenberg block output (`.wp-block-*`, including Columns and Table
  blocks) inside the editable zones, so content typed in wp-admin
  matches the rest of the page automatically.
- `SoftwareApplication`, `HowTo`, and `FAQPage` JSON-LD schema. The
  FAQ schema is generated from the same Heading/Paragraph pairs the
  FAQ zone displays — edit the FAQ in wp-admin and the schema updates
  with it, no separate place to keep in sync.

**Content that needs your attention before launch (per the source
copy's own DUMMY notes — these are seeded honestly, not filled with
fake data):**

- **Tutorial screenshots** — every guide (Quick Steps, and each of the
  App/Computer/iPhone/Android guides inside "How to Use") is seeded
  with an instructional paragraph telling you where to add an Image
  block; none are pre-filled with a real screenshot.
- **Testimonials** — the source copy's three reviews are
  fabricated/DUMMY. The seeded "Testimonials" zone is intentionally
  left with just an instructional note, not fake names/ratings/quotes.
  Add 5–10 genuine reviews as Paragraph or Quote blocks once you have
  them, or leave it empty.
- **Trust badges** — real Google Safe Browsing / Norton / Sucuri
  links, already wired to `pinsdownload.org`, with a note that they'll
  show "no data yet" until the domain has scan history.
- **Guides / Other Tools** — listed as "not published yet"; once those
  pages are live, edit the list items into real links.
- **Comparison table** — seeded as an editable Table block, marked
  "REVIEW REQUIRED" in the paragraph underneath; every claim needs
  testing against the live tool before publishing.

## Editing content in Gutenberg

Applies the same way to the homepage and to every "PinsDownload Tool
Landing" page. There is **no separate admin screen** for this — you
edit the Page itself, in wp-admin (Pages → that page → Edit), the
normal block editor exactly as it looks for any other Page. The
template reads that Page's own content and splits it into zones;
everything about how the page *renders* (header, hero, tool, footer,
section order/spacing/backgrounds) is fixed in the template regardless
of what's on the Page — only the content inside each zone is yours to
edit, and each page's zones are independent of every other page's.

Zones are marked by **Heading blocks (H2)** with these exact names,
typed as ordinary content on the page, in any order you like:

```
Feature Strip
Quick Steps
How to Use
Images
Works and Doesnt
Features
Content Types
Explanations
Comparison
Devices
Safety
Trust Badges
Testimonials
Whats New
Guides
FAQ
Quick Answers
Other Tools
Final CTA
```

Whatever you put underneath one of these headings — and above the
next one — becomes that section's content. The marker heading itself
is never shown on the live page (front-page.php prints its own
visible heading for each section); it only tells the code where one
zone ends and the next begins. The first time the page loads after
installing the snippet, if it's completely empty, every marker and
its starter content gets filled in automatically — reload the page
editor after installing if you don't see this yet.

How to structure the content under each marker so it renders
correctly:

| Zone (H2 marker) | Shows up as | How to structure it underneath |
|---|---|---|
| **Feature Strip** | The thin strip right under the tool | A single Paragraph block. |
| **Quick Steps** | The 3-step "How to Download a Pinterest Video" cards | One row of a **Columns** block (3 columns), each with a Heading (H3) + Paragraph, then a closing Paragraph and an image-block hint for the tutorial screenshot. |
| **How to Use** | The App/Computer/iPhone/Android guides | A Heading (H3) block per guide, then a numbered List block, then optionally an Image block for that guide's screenshot. Repeat per guide. Add, remove, or reorder freely. |
| **Images** | A standalone gallery section after "How to Use" | Empty by default — add Image or Gallery blocks. Leave it empty and the section just won't show anything extra. |
| **Works and Doesnt** | "What This Tool Can and Can't Download" | A Heading (H3) "What Works" + a List, then a Heading (H3) "What Doesn't Work" + a List. |
| **Features** | The "Why People Use This Tool" grid | Two rows of a **Columns** block, each column holding a Heading (H3) + Paragraph — that's what turns into the card grid. |
| **Content Types** | "What Else You Can Download" | A single (bulleted) List block, one item per content type. |
| **Explanations** | "What Is / What For / Legal" | A Heading (H3) block per topic, then a Paragraph under it. |
| **Comparison** | The comparison table | A **Table** block (first row = header row), plus a closing Paragraph for the review note. |
| **Devices** | "Works on Every Device" | A single List block, one item per device/browser line. |
| **Safety** | "Is This Safe to Use?" | A single Paragraph block. |
| **Trust Badges** | "Check Our Current Reputation" | A Paragraph, then a List with a link per badge. |
| **Testimonials** | "What Users Say" | Empty by default. Add real reviews as Paragraph or Quote blocks once available — never fabricate names, ratings, or quotes. |
| **Whats New** | The changelog line | A single Paragraph block — add a new one at the top each time you ship something. |
| **Guides** | "Guides & Tips" | A single List block, one item per guide (turn into a real link once the guide is published). |
| **FAQ** | The FAQ accordion | **Important:** each question needs a Heading (H3) block immediately followed by a Paragraph block (its answer) — that exact Heading→Paragraph pairing is what becomes one accordion item **and** one entry in the FAQPage schema. Anything else (a List, an Image, two Paragraphs in a row) is ignored by the accordion. |
| **Quick Answers** | "Quick Answers" | Same Heading (H3) + Paragraph pattern as Explanations, one pair per question. |
| **Other Tools** | "Other Tools" | A single List block, one item per tool (turn into a real link once that tool page is published). |
| **Final CTA** | The supporting line under "Ready to Download?" | A single Paragraph block. The heading and the "Back to the Downloader" button stay fixed either way. |

Note the marker headings are **H2** and everything under them is
**H3** (or a List/Table/Columns/Image block) — that's how the code
tells "this is a new zone" apart from "this is just a subheading
inside the current zone."

**Still fixed, not editable as a zone** — and why: the site header
and footer (owned by your theme); the Hero's eyebrow and H1/subtitle
(a page should only have one H1 — on the homepage it's fixed text, on
a Tool Landing page it's that Page's own title/excerpt, set in the
normal Page fields, not a content zone); the downloader tool itself
(`id="pdl-tool"` — it's a working form and PHP logic, not text
content); the FAQ accordion's open/close mechanics (its questions and
answers *are* editable, via the FAQ zone above — just not the
click-to-expand behavior itself); and the final CTA's scroll-to-tool
button. Say the word if you want any of those opened up too.

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
