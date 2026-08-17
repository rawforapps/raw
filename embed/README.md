# PinsDownload — Embeddable Tool (Kadence / any theme)

Two files, no theme dependency. You design the page in Kadence; these
just make the tool itself work anywhere on that page.

## Install (2 steps, ~2 minutes)

1. **Backend** — `pinsdownload-backend-wpcode.php`
   WPCode → Add Snippet → Add Your Custom Code → Code Type: **PHP
   Snippet** → paste the whole file → Insertion: **Auto Insert → Run
   Everywhere** → Save & Activate. If you installed an earlier version
   of this snippet, **replace it entirely** — this version changes how
   pins are fetched, not just the error messages.

2. **Widget** — two ways to embed it, pick one:

   - **Recommended: shortcode.** Once the backend snippet above is
     active, just add a **Shortcode** block anywhere on your Kadence
     page and type:
     ```
     [pinsdownload_widget]
     ```
     No second file to paste. This is the reliable path — see "Why the
     shortcode is now the recommended path" below.

   - **Alternative: `pinsdownload-widget.html`.** Add a **Custom HTML**
     block and paste the whole file's contents in directly. Keep this
     around only if you want to hand-edit the widget's markup/CSS
     yourself outside of WPCode.

The widget calls relative paths (`/wp-json/...`), so it works on
whatever domain you embed it on with zero editing, as long as the
backend snippet is active on that same site.

## First thing to do after installing

**Step 0 — confirm the right code is actually running.** Visit this,
no login needed:

```
yoursite.com/wp-json/pinsdownload/v1/version
```

It should return `"build":"2026-08-17-r3"`. If you get a 404
(`rest_no_route`) here, the backend PHP snippet isn't active at all —
check WPCode shows it as Active, and that you pasted the *entire*
file (it's long, ~2000 lines; a partial paste from a browser-based
code view can silently truncate). If it returns a build tag that
isn't `2026-08-17-r3`, an older copy of the snippet is what's actually
running — delete every WPCode snippet related to PinsDownload and
paste this file fresh into one new one.

**Step 1 — check the actual Pinterest fetch.** While logged into
wp-admin:

```
yoursite.com/wp-json/pinsdownload/v1/debug?url=https://www.pinterest.com/pin/1103804189961515698/
```

Check the `primary_api_attempt` field in the response. If
`pin_data_found` is `true`, the fix works and the tool will resolve
that pin. If it's `false`, the `raw_response_snippet` field shows
exactly what Pinterest sent back instead — send that to me and it's a
fast, targeted fix rather than another guess.

If `/debug` 404s while `/resolve` and `/version` both work: that's
not possible with this version of the file (all three are registered
together) — it means `/version` will already have told you a stale
copy is active; fix that first and `/debug` comes back with it.

## Widget shows an empty box, no input field, nothing happens on click

This means the outer container div rendered (its CSS applied — that's
the visible rounded box) but the `<form>` inside it never reached the
browser. Since the form is static markup, not something JS builds, the
only way for it to go missing is something between WPCode and the
page stripping `<form>`/`<script>`/`<style>` tags before output —
Gutenberg's `the_content` pipeline (and some plugin shortcode
renderers) run untrusted-looking HTML through `wp_kses`, which allows
plain tags like `<div>` but strips `<form>`, `<input>`, `<button>`,
`<script>`, and `<style>` outright. That fits exactly what you
described: box visible, everything inside it gone, nothing clickable.

**Fix: use the shortcode, not the separate HTML snippet.** This
version adds `[pinsdownload_widget]`, registered with PHP's own
`add_shortcode()` straight from the backend snippet you already have
active (the same proven mechanism the full theme build uses for its
own tool box) — its output is inserted by WordPress core exactly as
returned, with no separate HTML-snippet-rendering path in between to
strip anything.

Steps:
1. In WPCode, delete (or deactivate) the separate widget HTML
   snippet, if you made one.
2. On the Kadence page, replace it with a **Shortcode** block
   containing just `[pinsdownload_widget]`.
3. Purge any page cache (see below) and reload.

If you'd rather confirm the diagnosis first: open the page, right
click → **View Page Source** (not "Inspect" — inspect shows the
browser's cleaned-up DOM, source shows what the server actually sent).
Search (Ctrl/Cmd+F) for `pdw-form`. If it's not there, the server-side
stripping theory above is confirmed and the shortcode fix applies. If
it *is* there, something client-side is hiding it instead and that's
a different, narrower fix — tell me and send a screenshot of the
`<div id="pinsdownload-widget">` block from that source view.

## After any snippet change: purge the cache

This site runs **LiteSpeed Cache**, which can serve a stale cached
copy of the page (and sometimes of REST responses) after you edit a
WPCode snippet. After saving any change here, purge it: LiteSpeed
Cache → Toolbox → Purge All (or the purge-all button in the admin
bar). Do this before re-testing, otherwise you may be looking at the
old broken version even though the new code is already live.

## What's included vs. left out (on purpose, per your instruction)

Included: paste-and-auto-fetch, live progress, quality selector for
single pins, checkbox selection + ZIP for boards/profiles, distinct
error messages, streamed downloads (not stored on the server).

Left out of this lean embed: the mood board feature and the PWA
install prompt from the full theme build — those are page/site-level
features, not part of "just the tool." Say the word if you want either
folded into the widget too.

## The extraction bug — what changed and why

### Round 1 (the honesty fix)

The original code's only path to get pin data was scraping the pin
page's HTML for an embedded `__PWS_DATA__` script tag. Any failure in
that — the tag missing, the JSON not parsing, the structure not
matching — fell through to one line that returned a "deleted" error,
regardless of cause. A real 404 was already handled separately above
it, so in practice "deleted" was firing for extraction failures, not
actual deletions. That got split into distinct, accurate error codes
(`deleted`, `private`, `blocked_or_changed`, `fetch_failed`,
`unsupported`), each with its own message, plus an Open Graph
meta-tag fallback and diagnostic logging. Good for honesty, but it
didn't address why extraction was failing in the first place.

### Round 2 (the actual root-cause fix)

Cross-checked the extraction *method itself* against three
independent, real, actively-maintained open-source Pinterest clients:
[yt-dlp's pinterest.py](https://github.com/yt-dlp/yt-dlp/blob/master/yt_dlp/extractor/pinterest.py),
[gallery-dl's pinterest.py](https://github.com/mikf/gallery-dl/blob/master/gallery_dl/extractor/pinterest.py),
and [seregazhuk/php-pinterest-bot](https://github.com/seregazhuk/php-pinterest-bot).
All three agree: **none of them scrape the HTML page for embedded
JSON.** They call Pinterest's own public resource API directly:

```
GET https://www.pinterest.com/resource/PinResource/get/
    ?data={"options":{"id":"<pin_id>","field_set_key":"detailed"}}
```

No page load first, no real session — anonymous reads work with a
placeholder CSRF token (`'1234'`, the exact value the PHP bot
hardcodes for its logged-out state; gallery-dl just generates a random
one, same effect). Boards go through a `Board` lookup for the board ID
then a paginated `BoardFeed`; profiles use `UserActivityPins` — both
resource names and their options payloads confirmed against the same
three sources.

This is very likely the actual reason nothing ever worked: the tool
was scraping for data using a technique none of the real, currently-
functioning Pinterest tools actually rely on. The rebuilt resolver now
calls the resource API directly as the primary path, with the old
HTML-scrape method kept as an automatic fallback if the API call ever
fails.

### Round 3 (delivery, not extraction)

Live testing on pinsdownload.org surfaced a different problem: the
`/debug` route wasn't appearing in `/wp-json/`'s route list even
though `/resolve` (registered in the same code block) was — pointing
at a stale/partial snippet paste rather than a code bug. Added
`/wp-json/pinsdownload/v1/version` (no login required) specifically so
that question never has to be guessed at again — it reports a build
tag that only matches this exact file.

Separately, the embedded widget was rendering as an empty styled box
with no input field, not even clickable — consistent with something
stripping `<form>`/`<script>`/`<style>` tags out of the WPCode HTML
snippet's output before it reached the browser (`wp_kses`-style
filtering does exactly this: allows `<div>`, drops those specific
tags). Added a `[pinsdownload_widget]` shortcode, registered with
plain `add_shortcode()` from the same backend PHP snippet that was
already proven active (since `/resolve` worked) — this is the same
delivery mechanism the full theme build already uses successfully for
its own tool box, and it bypasses whatever was rendering the separate
HTML snippet incorrectly, since PHP-shortcode output is inserted by
WordPress core as-is.

## Honest limitation — please read before assuming it's fixed

This sandbox's network still cannot reach `pinterest.com` in any form
(checked again for this round too — 403 at the network policy level).
That means this still has not been confirmed against a live response
— what changed is the *strength of the evidence* behind the fix: it's
no longer a best-guess reading of Pinterest's page structure, it's the
same method three independent, real, currently-working tools use,
cross-checked against each other for the exact request shape.

The `/debug` endpoint above is the actual verification step. Please
run it against the test pin and tell me what `primary_api_attempt`
says — that closes the loop properly, one way or the other.
