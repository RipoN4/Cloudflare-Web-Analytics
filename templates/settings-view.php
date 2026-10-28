<?php
/**
 * Settings Page View
 *
 * @package CF_Edge_Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

$api_token  = get_option('cf_analytics_api_token', '');
$account_id = get_option('cf_analytics_account_id', '');
$site_tag   = get_option('cf_analytics_site_tag', '');
$zone_id    = get_option('cf_analytics_zone_id', '');
$cache_ttl  = (int) get_option('cf_analytics_cache_ttl', 300);
$view_cap   = get_option('cf_analytics_view_capability', 'manage_options');
$admin_bar_counter = (int) get_option('cf_analytics_admin_bar_counter', 1);
?>

<div class="wrap cf-analytics-wrap cf-settings-wrap">
    <header class="cf-dashboard-header">
        <div class="cf-header-title-box">
            <div class="cf-logo-icon">
                <span class="dashicons dashicons-admin-generic"></span>
            </div>
            <div>
                <h1 class="cf-page-title"><?php esc_html_e('Cloudflare Web Analytics Settings', 'cf-edge-analytics'); ?></h1>
                <p class="cf-page-subtitle"><?php esc_html_e('Configure Cloudflare Web Analytics credentials & automatic property detection', 'cf-edge-analytics'); ?></p>
            </div>
        </div>

        <div class="cf-header-actions">
            <a href="<?php echo esc_url(admin_url('index.php?page=cf-edge-analytics')); ?>" class="button cf-btn cf-btn-settings">
                <span class="dashicons dashicons-chart-area cf-btn-icon"></span>
                <span><?php esc_html_e('View Analytics Dashboard', 'cf-edge-analytics'); ?></span>
            </a>
        </div>
    </header>

    <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) : ?>
        <div class="cf-notice-banner cf-notice-success">
            <span class="dashicons dashicons-yes-alt cf-banner-icon"></span>
            <div class="cf-banner-content">
                <strong><?php esc_html_e('Settings Saved Successfully!', 'cf-edge-analytics'); ?></strong>
                <span><?php esc_html_e('Credentials stored. Transient cache cleared for instant synchronization with Cloudflare Web Analytics.', 'cf-edge-analytics'); ?></span>
            </div>
        </div>
        <?php
        delete_transient('cf_analytics_data_cache');
        delete_transient('cf_analytics_cache_timestamp');
        ?>
    <?php endif; ?>

<?php
$current_host = strtolower(parse_url(home_url(), PHP_URL_HOST));
$is_connected = !empty($api_token) && (!empty($site_tag) || !empty($account_id) || !empty($zone_id));
?>

    <div class="cf-settings-container">
        <!-- Settings Form Card -->
        <div class="cf-card cf-settings-card">
            <div class="cf-card-header">
                <div class="cf-header-title-wrap">
                    <span class="dashicons dashicons-shield-alt cf-header-icon"></span>
                    <h3 class="cf-card-title"><?php esc_html_e('Cloudflare Connection', 'cf-edge-analytics'); ?></h3>
                </div>
            </div>

            <form method="post" action="options.php" class="cf-form" id="cf-settings-form">
                <?php settings_fields('cf_analytics_settings_group'); ?>

                <?php if ($is_connected) : ?>
                    <!-- State 1: Active Connection Verified Status Card -->
                    <div class="cf-connection-status-card cf-connected" id="cf-connection-card">
                        <div class="cf-conn-status-header">
                            <div class="cf-conn-status-badge">
                                <span class="cf-status-indicator-dot"></span>
                                <strong class="cf-conn-badge-title"><?php esc_html_e('Connected to Cloudflare Web Analytics', 'cf-edge-analytics'); ?></strong>
                            </div>
                            <button type="button" class="button cf-btn-secondary-sm" id="cf-btn-toggle-token-edit">
                                <span class="dashicons dashicons-edit"></span>
                                <span id="cf-edit-token-label"><?php esc_html_e('Change Token', 'cf-edge-analytics'); ?></span>
                            </button>
                        </div>

                        <div class="cf-conn-metadata-grid">
                            <div class="cf-conn-meta-item">
                                <span class="cf-conn-meta-label"><?php esc_html_e('Detected Domain', 'cf-edge-analytics'); ?></span>
                                <strong class="cf-conn-meta-value" id="cf-meta-domain"><?php echo esc_html($current_host); ?></strong>
                            </div>
                            <div class="cf-conn-meta-item">
                                <span class="cf-conn-meta-label"><?php esc_html_e('Site Tag', 'cf-edge-analytics'); ?></span>
                                <code class="cf-conn-meta-code" id="cf-meta-site-tag"><?php echo esc_html(!empty($site_tag) ? $site_tag : __('Auto-detected on request', 'cf-edge-analytics')); ?></code>
                            </div>
                            <div class="cf-conn-meta-item">
                                <span class="cf-conn-meta-label"><?php esc_html_e('Account ID', 'cf-edge-analytics'); ?></span>
                                <code class="cf-conn-meta-code" id="cf-meta-account-id"><?php echo esc_html(!empty($account_id) ? $account_id : __('Auto-detected on request', 'cf-edge-analytics')); ?></code>
                            </div>
                            <div class="cf-conn-meta-item">
                                <span class="cf-conn-meta-label"><?php esc_html_e('Zone ID', 'cf-edge-analytics'); ?></span>
                                <code class="cf-conn-meta-code" id="cf-meta-zone-id"><?php echo esc_html(!empty($zone_id) ? $zone_id : __('Auto-resolved', 'cf-edge-analytics')); ?></code>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Token Input Area (Visible if not connected, or when clicking "Change Token") -->
                <div class="cf-token-setup-box <?php echo $is_connected ? 'cf-token-hidden' : ''; ?>" id="cf-token-setup-box">
                    <div class="cf-form-group">
                        <label for="cf_analytics_api_token" class="cf-label">
                            <?php esc_html_e('Cloudflare API Token', 'cf-edge-analytics'); ?>
                            <span class="cf-required">*</span>
                        </label>
                        <div class="cf-input-wrap">
                            <input type="password"
                                   name="cf_analytics_api_token"
                                   id="cf_analytics_api_token"
                                   class="cf-input"
                                   value="<?php echo esc_attr($api_token); ?>"
                                   placeholder="<?php esc_attr_e('Paste your Cloudflare Bearer API Token here', 'cf-edge-analytics'); ?>"
                                   required />
                            <button type="button" class="cf-toggle-password" id="cf-toggle-token" title="<?php esc_attr_e('Show/Hide Token', 'cf-edge-analytics'); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                            </button>
                        </div>
                        <span class="cf-help-text">
                            <?php esc_html_e('Requires Account > Account Analytics > Read. Include Account Settings > Read for auto-detection.', 'cf-edge-analytics'); ?>
                        </span>

                        <div class="cf-token-action-row">
                            <button type="button" class="button button-primary cf-btn cf-btn-connect" id="cf-btn-autodetect">
                                <span class="dashicons dashicons-cloud"></span>
                                <span id="cf-btn-autodetect-text"><?php echo $is_connected ? esc_html__('Re-Detect & Update Credentials', 'cf-edge-analytics') : esc_html__('Connect & Auto-Detect Properties', 'cf-edge-analytics'); ?></span>
                            </button>
                            <span id="cf-autodetect-status" class="cf-status-pill"></span>
                        </div>
                    </div>
                </div>

                <!-- Progressive Disclosure: Collapsible Advanced Settings & Overrides -->
                <details class="cf-advanced-accordion" id="cf-advanced-accordion">
                    <summary class="cf-advanced-summary">
                        <span class="dashicons dashicons-admin-generic cf-summary-icon"></span>
                        <strong class="cf-summary-title"><?php esc_html_e('Advanced & Manual Overrides', 'cf-edge-analytics'); ?></strong>
                        <span class="cf-summary-pill"><?php esc_html_e('Optional', 'cf-edge-analytics'); ?></span>
                        <span class="dashicons dashicons-arrow-down-alt2 cf-summary-arrow"></span>
                    </summary>

                    <div class="cf-advanced-content">
                        <p class="cf-advanced-desc">
                            <?php esc_html_e('These properties are automatically detected from your Cloudflare account. You only need to edit them if you want to manually override auto-detection.', 'cf-edge-analytics'); ?>
                        </p>

                        <!-- Account ID -->
                        <div class="cf-form-group">
                            <label for="cf_analytics_account_id" class="cf-label">
                                <?php esc_html_e('Cloudflare Account ID', 'cf-edge-analytics'); ?>
                                <span class="cf-tag-pill"><?php esc_html_e('Auto-Detected', 'cf-edge-analytics'); ?></span>
                            </label>
                            <div class="cf-input-wrap">
                                <input type="text"
                                       name="cf_analytics_account_id"
                                       id="cf_analytics_account_id"
                                       class="cf-input"
                                       value="<?php echo esc_attr($account_id); ?>"
                                       placeholder="<?php esc_attr_e('Leave empty to auto-detect from API token', 'cf-edge-analytics'); ?>" />
                            </div>
                        </div>

                        <!-- Web Analytics Site Tag -->
                        <div class="cf-form-group">
                            <label for="cf_analytics_site_tag" class="cf-label">
                                <?php esc_html_e('Web Analytics Site Tag', 'cf-edge-analytics'); ?>
                                <span class="cf-tag-pill"><?php esc_html_e('Auto-Detected', 'cf-edge-analytics'); ?></span>
                            </label>
                            <div class="cf-input-wrap">
                                <input type="text"
                                       name="cf_analytics_site_tag"
                                       id="cf_analytics_site_tag"
                                       class="cf-input"
                                       value="<?php echo esc_attr($site_tag); ?>"
                                       placeholder="<?php esc_attr_e('Leave empty to auto-detect from API token', 'cf-edge-analytics'); ?>" />
                            </div>
                        </div>

                        <!-- Zone ID Fallback -->
                        <div class="cf-form-group">
                            <label for="cf_analytics_zone_id" class="cf-label">
                                <?php esc_html_e('Cloudflare Zone ID (Optional Fallback)', 'cf-edge-analytics'); ?>
                                <span class="cf-tag-pill"><?php esc_html_e('Fallback', 'cf-edge-analytics'); ?></span>
                            </label>
                            <div class="cf-input-wrap">
                                <input type="text"
                                       name="cf_analytics_zone_id"
                                       id="cf_analytics_zone_id"
                                       class="cf-input"
                                       value="<?php echo esc_attr($zone_id); ?>"
                                       placeholder="e.g. 023e105f4ecef8ad9ca31a8372d0c353" />
                            </div>
                        </div>

                        <!-- Cache Lifetime -->
                        <div class="cf-form-group">
                            <label for="cf_analytics_cache_ttl" class="cf-label">
                                <?php esc_html_e('Transient Cache Lifetime (Seconds)', 'cf-edge-analytics'); ?>
                            </label>
                            <div class="cf-input-wrap">
                                <input type="number"
                                       name="cf_analytics_cache_ttl"
                                       id="cf_analytics_cache_ttl"
                                       class="cf-input"
                                       min="60"
                                       max="86400"
                                       step="30"
                                       value="<?php echo esc_attr($cache_ttl); ?>" />
                            </div>
                            <span class="cf-help-text">
                                <?php esc_html_e('Recommended: 300 seconds (5 minutes). Keeps WordPress admin instant and avoids rate limits.', 'cf-edge-analytics'); ?>
                            </span>
                        </div>
                    </div>
                </details>

                <!-- Display & Permissions Settings Section -->
                <div class="cf-settings-section-box">
                    <div class="cf-settings-subheading">
                        <span class="dashicons dashicons-admin-users cf-subheading-icon"></span>
                        <h4 class="cf-subheading-title"><?php esc_html_e('Display & Access', 'cf-edge-analytics'); ?></h4>
                    </div>

                    <div class="cf-settings-rows">
                        <!-- Role-Based Report Access -->
                        <div class="cf-setting-row">
                            <div class="cf-setting-row-text">
                                <label for="cf_analytics_view_capability" class="cf-setting-title"><?php esc_html_e('Report Access', 'cf-edge-analytics'); ?></label>
                                <p class="cf-setting-hint"><?php esc_html_e('Minimum user role required to view analytics', 'cf-edge-analytics'); ?></p>
                            </div>
                            <div class="cf-setting-row-input">
                                <select name="cf_analytics_view_capability" id="cf_analytics_view_capability" class="cf-select cf-select-compact">
                                    <option value="manage_options" <?php selected($view_cap, 'manage_options'); ?>>
                                        <?php esc_html_e('Administrators only', 'cf-edge-analytics'); ?>
                                    </option>
                                    <option value="edit_others_posts" <?php selected($view_cap, 'edit_others_posts'); ?>>
                                        <?php esc_html_e('Administrators & Editors', 'cf-edge-analytics'); ?>
                                    </option>
                                    <option value="publish_posts" <?php selected($view_cap, 'publish_posts'); ?>>
                                        <?php esc_html_e('Administrators, Editors & Authors', 'cf-edge-analytics'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Admin Bar Quick Traffic Counter -->
                        <div class="cf-setting-row">
                            <div class="cf-setting-row-text">
                                <label for="cf_analytics_admin_bar_counter" class="cf-setting-title"><?php esc_html_e('Admin Bar Counter', 'cf-edge-analytics'); ?></label>
                                <p class="cf-setting-hint"><?php esc_html_e('Show 24h traffic badge in the top WordPress admin bar', 'cf-edge-analytics'); ?></p>
                            </div>
                            <div class="cf-setting-row-input">
                                <label class="cf-clean-checkbox" for="cf_analytics_admin_bar_counter">
                                    <input type="checkbox"
                                           name="cf_analytics_admin_bar_counter"
                                           id="cf_analytics_admin_bar_counter"
                                           value="1"
                                           <?php checked($admin_bar_counter, 1); ?> />
                                    <span><?php esc_html_e('Enabled', 'cf-edge-analytics'); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cf-form-actions">
                    <?php submit_button(__('Save Changes', 'cf-edge-analytics'), 'button cf-btn cf-btn-submit', 'submit', false); ?>

                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=cf_analytics_refresh'), 'cf_analytics_refresh_action')); ?>" class="button cf-btn cf-btn-refresh-manual">
                        <span class="dashicons dashicons-update cf-btn-icon"></span>
                        <span><?php esc_html_e('Flush Transient Cache', 'cf-edge-analytics'); ?></span>
                    </a>
                </div>
            </form>
        </div>

        <!-- Sidebar Column (Setup Guide + Closable FAQs) -->
        <div class="cf-settings-sidebar">
            <!-- Instructions Guide Card -->
            <div class="cf-card cf-guide-card">
                <div class="cf-card-header">
                    <div class="cf-header-title-wrap">
                        <span class="dashicons dashicons-welcome-learn-more cf-header-icon"></span>
                        <h3 class="cf-card-title"><?php esc_html_e('Setup Guide: Creating an Account API Token', 'cf-edge-analytics'); ?></h3>
                    </div>
                </div>
                <div class="cf-guide-content">
                    <ol class="cf-steps-list">
                        <li>
                            <strong><?php esc_html_e('1. Open Cloudflare API Tokens', 'cf-edge-analytics'); ?></strong>
                            <p><?php esc_html_e('Go to dash.cloudflare.com > Click your avatar (top right) > "My Profile" > "API Tokens".', 'cf-edge-analytics'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('2. Create Custom Token', 'cf-edge-analytics'); ?></strong>
                            <p><?php esc_html_e('Click "Create Token", scroll to "Custom Token" at the bottom and click "Get started".', 'cf-edge-analytics'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('3. Set Required Permissions', 'cf-edge-analytics'); ?></strong>
                            <p><?php esc_html_e('Add these two permissions:', 'cf-edge-analytics'); ?></p>
                            <p><strong>&bull; Account | Account Analytics | Read</strong> <?php esc_html_e('(fetches live pageviews & visits)', 'cf-edge-analytics'); ?></p>
                            <p><strong>&bull; Account | Account Settings | Read</strong> <?php esc_html_e('(allows automatic siteTag detection)', 'cf-edge-analytics'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('4. Account Resources', 'cf-edge-analytics'); ?></strong>
                            <p><?php esc_html_e('Select: Include | All accounts (or choose your specific Cloudflare Account).', 'cf-edge-analytics'); ?></p>
                        </li>
                        <li>
                            <strong><?php esc_html_e('5. Save and Connect', 'cf-edge-analytics'); ?></strong>
                            <p><?php esc_html_e('Copy your generated token, paste it into "Cloudflare API Token" on the left, and click "Save Changes". The plugin auto-detects properties and streams your analytics immediately!', 'cf-edge-analytics'); ?></p>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- Frequently Asked Questions (FAQ) Card -->
            <div class="cf-card cf-faq-card">
                <div class="cf-card-header">
                    <div class="cf-header-title-wrap">
                        <span class="dashicons dashicons-editor-help cf-header-icon"></span>
                        <h3 class="cf-card-title"><?php esc_html_e('Frequently Asked Questions', 'cf-edge-analytics'); ?></h3>
                    </div>
                </div>
                <div class="cf-faq-list">
                    <!-- FAQ 1: Requirements & Beacon -->
                    <details class="cf-faq-item">
                        <summary class="cf-faq-question">
                            <span class="cf-faq-title"><?php esc_html_e('Requirements: Does my site need an active Cloudflare Beacon?', 'cf-edge-analytics'); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2 cf-faq-arrow"></span>
                        </summary>
                        <div class="cf-faq-answer">
                            <p><?php esc_html_e('Yes. Cloudflare Web Analytics operates via a lightweight, cookieless JavaScript beacon. If your domain is proxied through Cloudflare (Orange Cloud in DNS), Cloudflare can automatically inject the beacon into your HTML pages with zero code changes. If your site is not proxied, you can enable Web Analytics in your Cloudflare dashboard and copy the lightweight JS beacon snippet into your site header.', 'cf-edge-analytics'); ?></p>
                            <p><?php esc_html_e('To fetch and display the analytics inside WordPress, the plugin requires an API Token with Account Analytics: Read permission.', 'cf-edge-analytics'); ?></p>
                        </div>
                    </details>

                    <!-- FAQ 2: Multi-Domain Usage -->
                    <details class="cf-faq-item">
                        <summary class="cf-faq-question">
                            <span class="cf-faq-title"><?php esc_html_e('Usage: Can I use 1 API token across all my WordPress sites?', 'cf-edge-analytics'); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2 cf-faq-arrow"></span>
                        </summary>
                        <div class="cf-faq-answer">
                            <p><?php esc_html_e('Yes! A single Cloudflare Account API Token is sufficient for all your WordPress sites under the same Cloudflare account. When installed on each site, the plugin automatically inspects that site’s domain host (home_url), queries Cloudflare for the matching Web Analytics Site Tag and Zone ID, and streams the correct metrics automatically without any manual ID copying.', 'cf-edge-analytics'); ?></p>
                        </div>
                    </details>

                    <!-- FAQ 3: Performance & Zero DB Bloat -->
                    <details class="cf-faq-item">
                        <summary class="cf-faq-question">
                            <span class="cf-faq-title"><?php esc_html_e('Performance: Does this plugin slow down WordPress or bloat my database?', 'cf-edge-analytics'); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2 cf-faq-arrow"></span>
                        </summary>
                        <div class="cf-faq-answer">
                            <p><?php esc_html_e('Zero bloat. Unlike older analytics plugins (like Jetpack or self-hosted loggers) that write millions of hit rows into your MySQL database causing database bloat and slow queries, this plugin writes zero custom tables. Analytics data is fetched directly from Cloudflare’s ultra-fast GraphQL edge and cached in a standard WordPress transient for 5 minutes, ensuring your admin stays lightning fast.', 'cf-edge-analytics'); ?></p>
                        </div>
                    </details>

                    <!-- FAQ 4: Web Analytics vs Zone HTTP Analytics -->
                    <details class="cf-faq-item">
                        <summary class="cf-faq-question">
                            <span class="cf-faq-title"><?php esc_html_e('Accuracy: Web Analytics (RUM) vs Zone HTTP Requests?', 'cf-edge-analytics'); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2 cf-faq-arrow"></span>
                        </summary>
                        <div class="cf-faq-answer">
                            <p><?php esc_html_e('Cloudflare Web Analytics (RUM) measures real browser pageviews and human visits while automatically filtering out web crawlers, search bots, and background scraper requests. Zone Analytics measures all raw edge HTTP requests (including image assets, API calls, and bots). This plugin uses Web Analytics (RUM) as its primary engine for 100% accurate human visitor insights.', 'cf-edge-analytics'); ?></p>
                        </div>
                    </details>

                    <!-- FAQ 5: Preview Mode -->
                    <details class="cf-faq-item">
                        <summary class="cf-faq-question">
                            <span class="cf-faq-title"><?php esc_html_e('Dashboard: Why do I see "Preview Mode" / Demo Data?', 'cf-edge-analytics'); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2 cf-faq-arrow"></span>
                        </summary>
                        <div class="cf-faq-answer">
                            <p><?php esc_html_e('Preview Mode activates when an API token is not yet configured or when a newly created Cloudflare Web Analytics property has not yet collected visitor data. It allows you to explore the dashboard widgets and layout. As soon as your token is saved and real visits are detected, the status changes to "Live" with your real traffic metrics.', 'cf-edge-analytics'); ?></p>
                        </div>
                    </details>

                    <!-- FAQ 6: Privacy & GDPR Compliance -->
                    <details class="cf-faq-item">
                        <summary class="cf-faq-question">
                            <span class="cf-faq-title"><?php esc_html_e('Privacy: Is it compliant with GDPR, CCPA, and cookie laws?', 'cf-edge-analytics'); ?></span>
                            <span class="dashicons dashicons-arrow-down-alt2 cf-faq-arrow"></span>
                        </summary>
                        <div class="cf-faq-answer">
                            <p><?php esc_html_e('100% compliant. Cloudflare Web Analytics does not use cookies, does not collect IP addresses or Personally Identifiable Information (PII), and never tracks users across different websites. You do not need to show an intrusive GDPR cookie banner for Cloudflare Web Analytics, and it is immune to most ad blockers.', 'cf-edge-analytics'); ?></p>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <!-- Plugin Footer / Credits -->
    <footer class="cf-card" style="margin-top: 24px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: var(--cf-card-bg); border: 1px solid var(--cf-border);">
        <div style="font-size: 13px; color: var(--cf-text-sub);">
            <strong>Cloudflare Web Analytics</strong> &bull; v1.0.0 &bull; <?php esc_html_e('Crafted by', 'cf-edge-analytics'); ?> <a href="https://ripon.in" target="_blank" rel="noopener" style="color: var(--cf-blue); font-weight: 600; text-decoration: none;">RipoN</a>
        </div>
        <div style="display: flex; gap: 14px; font-size: 12px;">
            <a href="https://github.com/RipoN4/Cloudflare-Web-Analytics" target="_blank" rel="noopener" style="color: var(--cf-text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <span class="dashicons dashicons-external"></span> <?php esc_html_e('GitHub Repository', 'cf-edge-analytics'); ?>
            </a>
        </div>
    </footer>
</div>
