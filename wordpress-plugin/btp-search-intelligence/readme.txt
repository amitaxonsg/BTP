=== BTP Search Intelligence ===
Contributors: axon1pro
Tags: seo, aeo, schema, analytics, bing, google, llms
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Non-visual SEO/AEO companion for British Theatre Playhouse.

== Description ==

BTP Search Intelligence improves search and AI discoverability without changing the current WordPress design.

Features:
* Google Analytics 4 integration (ID entered in WordPress admin)
* Google Search Console verification meta
* Bing Webmaster Tools verification meta
* Optional Microsoft Clarity tracking
* Event/TheaterEvent JSON-LD for the October 2026 production
* Public AI-readable REST endpoint: /wp-json/btp/v1/event
* robots.txt discover sitemap hints
* /llms.txt fallback when a physical llms.txt is not served
* Admin links to Google Analytics, Search Console, Bing Webmaster Tools and Clarity

The plugin intentionally does not rewrite titles/descriptions globally and therefore avoids fighting with an existing SEO plugin.

== Installation ==

1. Upload the btp-search-intelligence folder to /wp-content/plugins/
2. Activate "BTP Search Intelligence"
3. Go to Settings > BTP Search Intelligence
4. Enter only the confirmed GA4 / Google verification / Bing verification / Clarity values
5. Save changes
6. Confirm:
   * https://britishtheatreplayhouse.com/wp-json/btp/v1/event
   * https://britishtheatreplayhouse.com/llms.txt
   * https://britishtheatreplayhouse.com/discover/sitemap.xml

== Safety ==

No visible theme or page layout changes are made.
Analytics scripts are only printed when their corresponding IDs are valid and enabled.
