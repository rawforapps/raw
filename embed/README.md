# PinsDownload — Embeddable Tool (Kadence / any theme)

Two files, no theme dependency. You design the page in Kadence; these
just make the tool itself work anywhere on that page.

## Install (2 steps, ~2 minutes)

1. **Backend** — `pinsdownload-backend-wpcode.php`
   WPCode → Add Snippet → Add Your Custom Code → Code Type: **PHP
   Snippet** → paste the whole file → Insertion: **Auto Insert → Run
   Everywhere** → Save & Activate.

2. **Widget** — `pinsdownload-widget.html`
   On the page you're building in Kadence, add a **Custom HTML** block
   (or a WPCode "HTML Snippet" inserted wherever you want it) and paste
   the whole file's contents in. That's it — paste a Pinterest link,
   it resolves and downloads, right there in your own page design.

The widget calls relative paths (`/wp-json/...`), so it works on
whatever domain you embed it on with zero editing, as long as the
backend snippet is active on that same site.

## What's included vs. left out (on purpose, per your instruction)

Included: paste-and-auto-fetch, live progress, quality selector for
single pins, checkbox selection + ZIP for boards/profiles, distinct
error messages, streamed downloads (not stored on the server).

Left out of this lean embed: the mood board feature and the PWA
install prompt from the full theme build — those are page/site-level
features, not part of "just the tool." Say the word if you want either
folded into the widget too.

## The extraction bug — what changed and why

**Before:** any failure to extract pin data (missing data blob,
unparsable JSON, structure mismatch — three different problems) fell
through to one line that returned the "deleted" error, regardless of
cause. A real 404 was already handled separately above it, so in
practice "deleted" was firing for extraction failures, not actual
deletions.

**After:**
- Distinct error codes: `deleted` (real 404 only), `private`,
  `blocked_or_changed` (page fetched fine, data couldn't be read —
  the honest "something changed or we're being blocked" state),
  `fetch_failed` (couldn't reach Pinterest at all), `unsupported`.
  Each has its own accurate user-facing message now.
- A second extraction attempt via Open Graph meta tags before giving
  up. Pinterest renders `og:image` / `og:video` tags for link-preview
  and SEO purposes; those tend to be more stable than the internal
  data blob's exact field names, so this survives some structure
  changes that would break the primary path. Lower confidence (one
  resolution only, labeled "limited info available"), but real data
  instead of a false "deleted."
- `WP_DEBUG`-gated diagnostic logging (`error_log`) on every failure
  path: HTTP status, whether the data blob was found, a body snippet.
- A live diagnostic endpoint, admin-only:
  `yoursite.com/wp-json/pinsdownload/v1/debug?url=<pinterest-url>`
  (visit it in a browser tab while logged into wp-admin). Returns raw
  JSON: HTTP status, whether Pinterest's data blob was present, whether
  a CAPTCHA/bot-challenge page was served instead, and a body snippet.
  **Run this on the test pin URL first** if resolve still fails after
  install — it will tell you exactly which of the above is happening,
  which this sandbox could not determine (see below).

## Honest limitation — please read before assuming it's fixed

This sandbox's network cannot reach `pinterest.com` in any form
(confirmed again for this task — `pinterest.com`, `i.pinimg.com`,
`api.pinterest.com`, `widgets.pinterest.com` all return a 403 at the
network policy level). That means steps 3, 4, and 5 of your debugging
plan — live fetch test, raw response capture, live verification —
could not be executed from here, and the test pin URL has not actually
been confirmed working.

What was done instead: the pipeline was traced end to end (confirmed
real, not a stub or mock — `fetch_page()` is a genuine `wp_remote_get`
call), the exact catch-all bug was found and fixed, error states were
split into accurate categories, a second extraction path was added,
and diagnostics were wired in so you get a real answer in one request
once this is live. Please run the `/debug` URL above against the test
pin as the actual first verification step — I want to know what it
says as much as you do.
