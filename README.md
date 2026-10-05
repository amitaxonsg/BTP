# British Theatre Playhouse — Discover SEO/AEO Layer

Standalone discovery layer for British Theatre Playhouse. It is designed to sit beside the current WordPress site without replacing or redesigning existing pages.

## Deployment
Deploy repository contents into the live document root so these resolve:
- https://britishtheatreplayhouse.com/discover/
- https://britishtheatreplayhouse.com/llms.txt
- https://britishtheatreplayhouse.com/discover/sitemap.xml

## Branding
Current BTP direction used:
- black / white core palette
- pink campaign accent
- stacked British / Theatre / Playhouse wordmark
- tagline: The Best in Live British Entertainment
- current 2026 BTP Business Connect campaign imagery

## Canonical October 2026 event data
Dedicated BTP event page treated as authoritative where it differs from general homepage copy.

Kuala Lumpur: 21 October 2026 — Hilton Kuala Lumpur, Grand Ballroom — dinner-theatre with curated 4-course meal before the show.

Singapore: 24 October 2026 — Capitol Theatre — 2:00 PM Afternoon Matinee and 7:00 PM Art for Charity Gala.

Do not block /discover/ in robots.txt.
Submit /discover/sitemap.xml to Google Search Console and Bing Webmaster Tools.


## WordPress companion plugin

Source:
`wordpress-plugin/btp-search-intelligence/`

Install:
1. Copy `wordpress-plugin/btp-search-intelligence` to `wp-content/plugins/`
2. Activate **BTP Search Intelligence**
3. Go to **Settings → BTP Search Intelligence**
4. Enter confirmed IDs only:
   - GA4 Measurement ID
   - Google Search Console verification value
   - Bing Webmaster verification value
   - optional Microsoft Clarity Project ID
5. Verify:
   - `/wp-json/btp/v1/event`
   - `/llms.txt`
   - `/discover/sitemap.xml`

The plugin does not alter the visible site design or rewrite the current WordPress pages.
