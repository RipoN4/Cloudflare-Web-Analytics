=== Web Analytics for Cloudflare ===
Contributors: ripon4
Donate link: https://ripon.in
Tags: cloudflare, analytics, web analytics, pageviews, privacy, visitors, traffic
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, privacy-first Web Analytics for Cloudflare on WordPress. Zero database bloat, no cookies, 100% free edge analytics in your dashboard.

== Description ==

Bring the power of **Cloudflare Web Analytics** directly into your WordPress Admin Dashboard for 100% free!

In the past, website owners relied on Jetpack for basic traffic statistics, but Jetpack has become bloated, slow, and resource-heavy. Other alternatives like Google Analytics require intrusive GDPR cookie banners and heavy client-side tracking scripts, while independent database analytics plugins can quickly bloat your MySQL database by millions of rows.

Cloudflare offers an enterprise-grade, privacy-first **Web Analytics (RUM)** engine that is 100% free with unlimited sites. However, checking it previously required logging into the Cloudflare dashboard manually every day.

**Web Analytics for Cloudflare** solves this by connecting directly to Cloudflare's GraphQL API to stream pure human pageviews, visits, referrers, and locations straight to your WordPress dashboard.

### Key Benefits

* **100% Free Forever**: Powered entirely by Cloudflare's free Web Analytics infrastructure.
* **Zero Database Bloat**: No tracking tables, no row bloat, and no slow queries. Your MySQL database remains untouched.
* **100% Privacy-First (No Cookies, GDPR Friendly)**: Does not track individual visitors, IP addresses, or personal data. No cookie consent banners required.
* **Real Human Data Only**: Automatically excludes web bots, scrapers, and automated crawlers (`excludeBots: Yes`).
* **Zero External JS Library Bloat**: Pure native SVG spline graphs and vanilla CSS. No heavy Chart.js or D3.js libraries slowing down your admin.
* **Smart Auto-Detection**: Enter only your Cloudflare API Token, and the plugin automatically detects your Account ID, Site Tag, and Zone ID matching your site.
* **Two Core Dashboard Widgets**:
  1. **24-Hour Traffic**: Live hourly area graph + Top 5 Referrers & Top 5 Pages.
  2. **30-Day Visits & Pageviews**: Dual-tone candle bar chart with hover tooltips + total counts.
* **Dedicated Analytics Screen**: Deep-dive overview featuring 24h/7d/14d/30d filters and Top 10 lists (Top Referrers, Top Paths, Top Countries with native flags).
* **WordPress Admin Bar Quick Counter**: Real-time 24h traffic badge and dropdown stats directly in the WordPress top toolbar.
* **Role-Based Report Access**: Grant viewing access to Editors or Authors while keeping API credentials strictly restricted to Administrators.
* **Quick "Edit Post" Shortcuts**: Jump directly into the WordPress post editor from your Top 10 Pages list with 1 click.
* **1-Click CSV Export**: Download a full analytics report (summary, timeline, top pages, referrers, and locations) formatted with UTF-8 BOM for Microsoft Excel.

---

== Privacy & Data Storage Notice ==

This plugin complies fully with WordPress.org guidelines and international privacy standards (GDPR, CCPA, PECR):

1. **No Personal Data Collected**: This plugin does not collect, track, or transmit any Personally Identifiable Information (PII) such as visitor IP addresses, emails, or device fingerprints.
2. **No Cookies Created**: This plugin does not set any cookies on visitors' browsers.
3. **No Database Clutter**: Visitor traffic logs are never stored in your WordPress database. The plugin only fetches aggregated metrics on demand from Cloudflare and temporarily caches the response in WordPress Transients (default 5 minutes) to avoid API rate limits.
4. **Third-Party Service Integration**:
   * This plugin connects to Cloudflare's official API (`https://api.cloudflare.com`) to retrieve aggregated analytics data for your configured domain.
   * Service: Cloudflare API & Web Analytics
   * Cloudflare Terms of Service: [https://www.cloudflare.com/terms/](https://www.cloudflare.com/terms/)
   * Cloudflare Privacy Policy: [https://www.cloudflare.com/privacypolicy/](https://www.cloudflare.com/privacypolicy/)

---

== Installation ==

### Quick 2-Minute Setup

1. Install and activate the plugin through the WordPress **Plugins > Add New** menu or upload the folder to `/wp-content/plugins/`.
2. Generate an API Token in Cloudflare:
   * Go to **dash.cloudflare.com** > Click your avatar (top right) > **My Profile** > **API Tokens**.
   * Click **Create Token** > select **Create Custom Token**.
   * Set Token permissions:
     * `Account` > `Account Analytics` > `Read`
     * `Account` > `Account Settings` > `Read`
   * Under **Account Resources**, choose **Include > All accounts** (or your specific account).
   * Create and copy the token.
3. In WordPress, navigate to **Settings > Cloudflare Analytics**.
4. Paste your token and click **Connect & Auto-Detect Properties**.
5. Click **Save Changes**. Your live analytics will stream immediately!

---

== Frequently Asked Questions ==

= Does my site need an active Cloudflare Beacon? =
Yes. Cloudflare Web Analytics operates via a lightweight, cookieless JavaScript beacon. If your domain is proxied through Cloudflare (Orange Cloud in DNS), Cloudflare automatically injects the beacon into your HTML pages with zero code changes. If your site is not proxied, you can enable Web Analytics in your Cloudflare dashboard and copy the lightweight JS beacon snippet into your site header.

= Can I use 1 API token across all my WordPress sites? =
Yes! A single Cloudflare Account-level API Token with Account Analytics and Account Settings read permissions is sufficient for all your WordPress sites under the same Cloudflare account. When installed on each site, the plugin automatically inspects that site's domain host, queries Cloudflare for the matching Web Analytics Site Tag and Zone ID, and streams the correct metrics automatically without any manual ID copying.

= Does this plugin slow down WordPress or bloat my database? =
Zero database bloat. Unlike older analytics plugins (like Jetpack or self-hosted loggers) that write millions of hit rows into your MySQL database causing database bloat and slow queries, this plugin writes zero custom tables. Analytics data is fetched directly from Cloudflare's ultra-fast GraphQL edge and cached in a standard WordPress transient for 5 minutes, ensuring your admin stays lightning fast.

= What is the difference between Web Analytics (RUM) and Zone HTTP Requests? =
Cloudflare Web Analytics (RUM) measures real browser pageviews and human visits while automatically filtering out web crawlers, search bots, and background scraper requests. Zone Analytics measures all raw edge HTTP requests (including image assets, API calls, and bots). This plugin uses Web Analytics (RUM) as its primary engine for 100% accurate human visitor insights.

= Why do I see "Preview Mode" / Demo Data? =
Preview Mode activates when an API token is not yet configured or when a newly created Cloudflare Web Analytics property has not yet collected visitor data. It allows you to explore the dashboard widgets and layout. As soon as your token is saved and real visits are detected, the status changes to "Live" with your real traffic metrics.

= Is it compliant with GDPR, CCPA, and cookie laws? =
100% compliant. Cloudflare Web Analytics does not use cookies, does not collect IP addresses or Personally Identifiable Information (PII), and never tracks users across different websites. You do not need to show an intrusive GDPR cookie banner for Cloudflare Web Analytics, and it is immune to most ad blockers.

---

== Screenshots ==

1. screenshot-1.png - Dedicated Cloudflare Analytics Dashboard with 30-day activity bar chart and Top 10 breakdown cards.
2. screenshot-2.png - 24-Hour Traffic Widget with live hourly spline graph and top referrers & pages on the WordPress core dashboard.
3. screenshot-3.png - 30-Day Visits & Pageviews core dashboard widget.
4. screenshot-4.png - Modern Settings screen with 1-click Auto-Detection, Role-based access controls, and closable FAQ guide.

---

== Changelog ==

= 1.1.0 =
* Added: Interactive Property Picker Modal allowing users to search, browse, and manually select any site property from their Cloudflare account.
* Added: 1-Click "Switch Domain" capability for websites that change domain names or operate in multi-domain Cloudflare accounts.
* Added: Complete "Reset All Data" option beside Save Changes with confirmation prompt to wipe all credentials and cached transients back to a clean installation state.
* Added: WordPress standard `uninstall.php` handler for zero leftover footprint upon plugin deletion.
* Enhanced: GraphQL RUM query schema compatibility with exact Cloudflare enum ordering (`date_ASC`) and dimension typing.

= 1.0.3 =
* Enhanced: Dynamic 24-hour breakdown lists (Top Pages, Referrers, Locations) dynamically scaled to active pageviews.
* Enhanced: Automatic period validation and transient cache self-healing.
* Added: Manual cache bust support via `&refresh=1` URL parameter.
* Updated: High-resolution screenshots and detailed setup guide documentation.

= 1.0.2 =
* Enhanced: Multi-period filter engine (24h, 7d, 14d, 30d) with dynamic GraphQL queries for Zone Analytics fallback.
* Fixed: CSV Export nonce action validation across all admin screens.
* Enhanced: Admin bar quick traffic counter data resolution with multiple fallbacks.
* Added: 6 detailed closable FAQ accordions in the settings interface.

= 1.0.0 =
* Initial production release.
* Added Cloudflare Web Analytics (RUM GraphQL) engine with bot filtering (`excludeBots: Yes`).
* Added 1-Click Auto-Detection for Account ID, Site Tag, and Zone ID.
* Added Active Connection status card with collapsible progressive disclosure drawer.
* Added 2 WordPress core dashboard widgets (24-Hour Traffic & 30-Day Visits).
* Added dedicated Analytics screen with Top 10 Referrers, Pages, and Countries.
* Added dynamic timeframe selector (24h, 7d, 14d, 30d).
* Added WordPress Admin Bar Quick Traffic Counter with 24h views & visitors.
* Added Role-Based Report Access permissions (Admins, Editors, Authors).
* Added quick "Edit Post" shortcuts in Top 10 visited pages list.
* Added 1-Click CSV Export formatted with UTF-8 BOM for Microsoft Excel.
* Added closable Frequently Asked Questions card in settings screen.
* Pure SVG spline and bar rendering with zero external JavaScript library dependencies.

