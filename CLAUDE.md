# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

A zero-dependency WordPress plugin that displays a Beds24 room/property availability calendar via the `[avail_calendar]` shortcode. No build step, no npm, no Composer.

## File layout

```
availability-calendar-for-beds24.php  ← Plugin entry point: shortcode handler + wp_footer init
calendar.js                           ← AvailCalendar vanilla-JS class (also usable standalone)
calendar.css                          ← Scoped styles (.bac-wrapper namespace)
readme.txt                            ← WordPress.org plugin readme
```

> **Note:** The PHP plugin file enqueues assets from `assets/calendar.css` / `assets/calendar.js` (relative to the plugin root). When deploying to WordPress, `calendar.js` and `calendar.css` must live in an `assets/` subdirectory inside the plugin folder.

## Testing

No automated test suite. Test manually in two ways:

**Standalone (no WordPress):**
```html
<link rel="stylesheet" href="calendar.css">
<script src="calendar.js"></script>
<div id="test"></div>
<script>new AvailCalendar({ containerId: 'test' });</script>
```
Omitting `roomid`/`propid` triggers demo mode with randomised data.

**WordPress:** Copy the plugin folder to `wp-content/plugins/`, activate, and use the shortcode on a page.

## Architecture

### WordPress flow
1. `[avail_calendar]` shortcode runs `bac_shortcode()`, which renders a `<div id="bac-{uid}">` and appends config to the `$bac_instances` global.
2. `bac_footer_init()` (hooked at `wp_footer` priority 20) emits a single `<script>` that instantiates `new AvailCalendar(cfg)` for every collected instance.
3. CSS and JS are registered once via `bac_register_assets` / `wp_enqueue_scripts`; calling `wp_enqueue_style/script` inside the shortcode is idempotent.

### JavaScript class (`AvailCalendar`)
- **Constructor** → `_buildDOM()` → `_render()` → `_fetchAvailability()` → `_render()` again once data arrives.
- **Data source:** `https://media.xmlcal.com/widget/1.00/scripts/availability.php?roomid=X` returns a JSON object keyed by ISO date (`"YYYY-MM-DD": 0|1`). Absent keys mean available.
- **Cache:** `sessionStorage` with a 5-minute TTL keyed as `beds24_avail_roomid_{id}` or `beds24_avail_propid_{id}`. Also refreshes on a 5-minute `setInterval`.
- **Locale:** All labels (month names, weekday headers, first-day-of-week) come from the browser's `Intl` API; only tooltip strings are hard-coded per-language in `_t()`.

### Tile classification logic
`_tileClass(dateObj)` compares today's API value (`cur`) with yesterday's (`prev`):

| cur | prev        | CSS class              | Visual              |
|-----|-------------|------------------------|---------------------|
| —   | —           | `bac-past`             | Grey text           |
| `0` | `0` or past | `bac-unavailable`      | Full red            |
| `0` | `1`         | `bac-split-avail-am`   | Green TL / red BR   |
| `1` | `1`/absent  | `bac-available`        | Full green          |
| `1` | `0`         | `bac-split-avail-pm`   | Red TL / green BR   |

Only `bac-available` and `bac-split-avail-pm` tiles are bookable (wrapped in `<a>`).

Split tiles use a CSS `::before` triangle (`clip-path: polygon(0 0, 100% 0, 0 100%)`) — the base colour is the PM half; the pseudo-element overlays the AM half.

### CSS scoping
All styles are scoped under `.bac-wrapper` and use CSS custom properties for colours and sizing. Dark mode is handled via a single `@media (prefers-color-scheme: dark)` block that overrides the custom properties.
