=== Beds24 Availability Calendar ===
Contributors:       jacquesleisy
Tags:               beds24, availability, calendar, booking, hotel, b&b
Requires at least:  5.9
Tested up to:       6.7
Requires PHP:       7.4
Stable tag:         1.0.0
License:            GPLv2 or later
License URI:        https://www.gnu.org/licenses/gpl-2.0.html

Displays a Beds24 room or property availability calendar via the [avail_calendar] shortcode.

== Description ==

A lightweight, dependency-free availability calendar for properties managed with Beds24.com.

Features:
* Displays availability for a specific room (`roomid`) or whole property (`propid`)
* Colour-coded tiles: green (available), red (unavailable), diagonal split for arrival/departure days
* Locale-aware: month names, weekday labels, and first day of week adapt automatically to any BCP-47 language tag via the browser's built-in Intl API
* Click-to-book: available dates link directly to the Beds24 booking page
* SessionStorage cache with 5-minute TTL — avoids repeated API calls on navigation
* Dark mode support via `prefers-color-scheme`
* Multiple instances on the same page are fully supported
* No jQuery, no build step, no external dependencies

== Installation ==

1. Upload the `beds24-availability-calendar` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Add the shortcode to any page or post

== Shortcode Usage ==

Display a calendar for a specific room:
  [avail_calendar roomid="12345" nummonths="5" lang="fr"]

Display a calendar for a whole property:
  [avail_calendar propid="67890" nummonths="3" lang="en"]

= Shortcode Attributes =

| Attribute    | Default | Description                                      |
|--------------|---------|--------------------------------------------------|
| roomid       | —       | Beds24 room ID (use roomid OR propid, not both)  |
| propid       | —       | Beds24 property ID                               |
| nummonths    | 5       | Number of months to display (1–24)               |
| startmonth   | current | Starting month (1–12)                            |
| startyear    | current | Starting year (2020–2100)                        |
| lang         | en      | BCP-47 locale tag, e.g. fr, de, es, it, nl       |

== Privacy Notes ==

* The calendar fetches availability data directly from `media.xmlcal.com` in the visitor's browser.
  No data passes through your WordPress server.
* The Beds24 `roomid` or `propid` is visible in the browser's network requests and in the
  booking URL when a guest clicks a date. This is inherent to how the Beds24 API works
  and is consistent with the behaviour of the official Beds24 booking widget.
* No personal data is collected or stored. SessionStorage is used only to cache
  availability data for 5 minutes and is cleared when the browser tab is closed.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
