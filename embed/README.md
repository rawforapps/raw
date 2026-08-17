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

2. **Widget** — `pinsdownload-widget.html`
   On the page you're building in Kadence, add a **Custom HTML** block
   (or a WPCode "HTML Snippet" inserted wherever you want it) and paste
   the whole file's contents in. That's it — paste a Pinterest link,
   it resolves and downloads, right there in your own page design.

The widget calls relative paths (`/wp-json/...`), so it works on
whatever domain you embed it on with zero editing, as long as the
backend snippet is active on that same site.

## First thing to do after installing

Visit this in a browser tab while logged into wp-admin (swap in the
real domain once it's live):

```
yoursite.com/wp-json/pinsdownload/v1/debug?url=https://www.pinterest.com/pin/1103804189961515698/
```

Check the `primary_api_attempt` field in the response. If
`pin_data_found` is `true`, the fix works and the tool will resolve
that pin. If it's `false`, the `raw_response_snippet` field shows
exactly what Pinterest sent back instead — send that to me and it's a
fast, targeted fix rather than another guess.

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
