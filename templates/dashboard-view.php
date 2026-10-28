<?php
/**
 * Dedicated Dashboard Page View
 *
 * @package CF_Edge_Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

$summary       = $analytics_data['summary'] ?? [];
$is_demo       = !empty($analytics_data['is_demo']);
$error_message = $analytics_data['error_message'] ?? '';
$data          = $analytics_data; // For widget includes

$periods = [
    '24h' => __('Last 24 Hours', 'cf-edge-analytics'),
    '7d'  => __('Last 7 Days', 'cf-edge-analytics'),
    '14d' => __('Last 14 Days', 'cf-edge-analytics'),
    '30d' => __('Last 30 Days', 'cf-edge-analytics'),
];

$current_p     = !empty($current_period) ? $current_period : ($analytics_data['period'] ?? '30d');
if (!isset($periods[$current_p])) {
    $current_p = '30d';
}
$period_lbl    = $periods[$current_p];
$is_24h        = ($current_p === '24h');
?>

<div class="wrap cf-analytics-wrap">
    <!-- Top Action Bar -->
    <header class="cf-dashboard-header">
        <div class="cf-header-title-box">
            <div class="cf-logo-icon">
                <span class="dashicons dashicons-cloud"></span>
            </div>
            <div>
                <h1 class="cf-page-title"><?php esc_html_e('Cloudflare Web Analytics', 'cf-edge-analytics'); ?></h1>
                <p class="cf-page-subtitle"><?php esc_html_e('Privacy-first real user metrics & global visitor intelligence powered by Cloudflare', 'cf-edge-analytics'); ?></p>
            </div>
        </div>

        <div class="cf-header-actions">
            <!-- Duration Filter Dropdown -->
            <div class="cf-duration-dropdown" id="cf-duration-dropdown">
                <button type="button" class="button cf-btn cf-duration-btn" id="cf-duration-btn" aria-haspopup="true" aria-expanded="false" title="<?php esc_attr_e('Select time range', 'cf-edge-analytics'); ?>">
                    <span class="dashicons dashicons-calendar-alt cf-btn-icon"></span>
                    <span id="cf-current-period-label"><?php echo esc_html($period_lbl); ?></span>
                    <span class="dashicons dashicons-arrow-down-alt2 cf-btn-caret"></span>
                </button>
                <div class="cf-duration-menu" id="cf-duration-menu">
                    <?php
                    foreach ($periods as $pkey => $plabel) :
                        $is_active = ($pkey === $current_p);
                        $url = add_query_arg(['period' => $pkey]);
                    ?>
                        <a href="<?php echo esc_url($url); ?>" class="cf-duration-item <?php echo $is_active ? 'cf-duration-active' : ''; ?>" data-period="<?php echo esc_attr($pkey); ?>">
                            <span class="dashicons <?php echo $is_active ? 'dashicons-yes' : 'dashicons-marker'; ?> cf-duration-icon"></span>
                            <span><?php echo esc_html($plabel); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cache / Edge Status Badge -->
            <div class="cf-status-pill <?php echo $is_demo ? 'cf-status-demo' : 'cf-status-live'; ?>" id="cf-status-pill">
                <span class="cf-status-indicator"></span>
                <span id="cf-cache-label">
                    <?php if ($is_demo) : ?>
                        <?php esc_html_e('Preview Mode', 'cf-edge-analytics'); ?>
                    <?php else : ?>
                        <?php printf(esc_html__('Cached %s', 'cf-edge-analytics'), esc_html($cache_info['formatted_time'] ?? 'Just now')); ?>
                    <?php endif; ?>
                </span>
            </div>

            <!-- Refresh Button (AJAX + Fallback) -->
            <button type="button" class="button cf-btn cf-btn-refresh" id="cf-btn-refresh" data-period="<?php echo esc_attr($current_p); ?>" title="<?php esc_attr_e('Clear transient and re-fetch from Cloudflare', 'cf-edge-analytics'); ?>">
                <span class="dashicons dashicons-update cf-btn-icon" id="cf-refresh-icon"></span>
                <span id="cf-refresh-text"><?php esc_html_e('Refresh Data', 'cf-edge-analytics'); ?></span>
            </button>

            <!-- Export CSV Button -->
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=cf_analytics_export_csv&period=' . esc_attr($current_p)), 'cf_analytics_export_csv_action')); ?>" class="button cf-btn cf-btn-export" title="<?php esc_attr_e('Download CSV report for selected period', 'cf-edge-analytics'); ?>">
                <span class="dashicons dashicons-media-spreadsheet cf-btn-icon"></span>
                <span><?php esc_html_e('Export CSV', 'cf-edge-analytics'); ?></span>
            </a>

            <!-- Settings Button (Admins only) -->
            <?php if (current_user_can('manage_options')) : ?>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=cf-edge-analytics-settings')); ?>" class="button cf-btn cf-btn-settings">
                    <span class="dashicons dashicons-admin-generic cf-btn-icon"></span>
                    <span><?php esc_html_e('Settings', 'cf-edge-analytics'); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Error / Demo Notification Banner if needed -->
    <?php if ($is_demo) : ?>
        <div class="cf-notice-banner">
            <span class="dashicons dashicons-info cf-banner-icon"></span>
            <div class="cf-banner-content">
                <strong><?php esc_html_e('Demo Preview Mode Active', 'cf-edge-analytics'); ?>:</strong>
                <span><?php echo esc_html($error_message ?: __('Enter your Cloudflare Zone ID and Analytics API Token in settings to start streaming live production analytics.', 'cf-edge-analytics')); ?></span>
            </div>
            <a href="<?php echo esc_url(admin_url('options-general.php?page=cf-edge-analytics-settings')); ?>" class="cf-banner-link">
                <?php esc_html_e('Configure Credentials', 'cf-edge-analytics'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Top 4 Metric Overview Cards -->
    <section class="cf-metrics-grid">
        <!-- Card 1: Unique Visitors (Period) -->
        <div class="cf-metric-card">
            <div class="cf-metric-icon-box cf-icon-cyan">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="cf-metric-details">
                <span class="cf-metric-title"><?php printf(esc_html__('Unique Visitors (%s)', 'cf-edge-analytics'), esc_html($period_lbl)); ?></span>
                <span class="cf-metric-value"><?php echo esc_html($summary['period_visitors_fmt'] ?? '0'); ?></span>
                <span class="cf-metric-badge cf-badge-subtle">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                    <span><?php esc_html_e('Unique edge devices', 'cf-edge-analytics'); ?></span>
                </span>
            </div>
        </div>

        <!-- Card 2: Total Pageviews (Period) -->
        <div class="cf-metric-card">
            <div class="cf-metric-icon-box cf-icon-blue">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="cf-metric-details">
                <span class="cf-metric-title"><?php printf(esc_html__('Total Pageviews (%s)', 'cf-edge-analytics'), esc_html($period_lbl)); ?></span>
                <span class="cf-metric-value"><?php echo esc_html($summary['period_views_fmt'] ?? '0'); ?></span>
                <span class="cf-metric-badge cf-badge-subtle">
                    <span class="dashicons dashicons-chart-line"></span>
                    <span><?php esc_html_e('Direct HTML page loads', 'cf-edge-analytics'); ?></span>
                </span>
            </div>
        </div>

        <!-- Card 3: 24h Pageviews Baseline or Top Path -->
        <div class="cf-metric-card">
            <div class="cf-metric-icon-box cf-icon-indigo">
                <span class="dashicons <?php echo $is_24h ? 'dashicons-admin-page' : 'dashicons-chart-area'; ?>"></span>
            </div>
            <div class="cf-metric-details">
                <span class="cf-metric-title">
                    <?php echo $is_24h ? esc_html__('Top Visited Page', 'cf-edge-analytics') : esc_html__('Pageviews (Today / 24h)', 'cf-edge-analytics'); ?>
                </span>
                <span class="cf-metric-value" style="<?php echo $is_24h ? 'font-size: 16px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;' : ''; ?>">
                    <?php
                    if ($is_24h) {
                        echo esc_html(!empty($data['top_paths'][0]['title']) ? $data['top_paths'][0]['title'] : __('Home Page', 'cf-edge-analytics'));
                    } else {
                        echo esc_html($summary['today_pageviews_fmt'] ?? '0');
                    }
                    ?>
                </span>
                <span class="cf-metric-badge cf-badge-subtle">
                    <span class="dashicons <?php echo $is_24h ? 'dashicons-visibility' : 'dashicons-clock'; ?>"></span>
                    <span>
                        <?php
                        if ($is_24h) {
                            printf(esc_html__('%s views', 'cf-edge-analytics'), esc_html($data['top_paths'][0]['count_fmt'] ?? '0'));
                        } else {
                            esc_html_e('Direct 24h activity', 'cf-edge-analytics');
                        }
                        ?>
                    </span>
                </span>
            </div>
        </div>

        <!-- Card 4: 24h Visitors Baseline or Top Country -->
        <div class="cf-metric-card">
            <div class="cf-metric-icon-box cf-icon-green">
                <span class="dashicons <?php echo $is_24h ? 'dashicons-location' : 'dashicons-admin-users'; ?>"></span>
            </div>
            <div class="cf-metric-details">
                <span class="cf-metric-title">
                    <?php echo $is_24h ? esc_html__('Top Traffic Country', 'cf-edge-analytics') : esc_html__('Unique Visitors (Today)', 'cf-edge-analytics'); ?>
                </span>
                <span class="cf-metric-value">
                    <?php
                    if ($is_24h) {
                        echo esc_html(!empty($data['top_countries'][0]['name']) ? $data['top_countries'][0]['name'] : __('Global', 'cf-edge-analytics'));
                    } else {
                        echo esc_html($summary['today_visitors_fmt'] ?? '0');
                    }
                    ?>
                </span>
                <span class="cf-metric-badge cf-badge-green">
                    <span class="dashicons <?php echo $is_24h ? 'dashicons-admin-site' : 'dashicons-yes-alt'; ?>"></span>
                    <span>
                        <?php
                        if ($is_24h) {
                            printf(esc_html__('%s visitors', 'cf-edge-analytics'), esc_html($data['top_countries'][0]['count_fmt'] ?? '0'));
                        } else {
                            esc_html_e('Unique human sessions', 'cf-edge-analytics');
                        }
                        ?>
                    </span>
                </span>
            </div>
        </div>
    </section>

    <!-- Main Chart Section: Selected Period Graph -->
    <div class="cf-charts-main-grid">
        <div class="cf-chart-slot cf-slot-main">
            <?php
            if ($is_24h) {
                $show_breakdowns = false;
                include CF_ANALYTICS_PATH . 'templates/widgets/widget-24h.php';
            } else {
                $show_button = false;
                include CF_ANALYTICS_PATH . 'templates/widgets/widget-30d.php';
            }
            ?>
        </div>
    </div>

    <!-- Three Top 10 Breakdown Lists Widget -->
    <section class="cf-breakdowns-section">
        <?php include CF_ANALYTICS_PATH . 'templates/widgets/widget-breakdowns.php'; ?>
    </section>

    <!-- Plugin Footer / Credits -->
    <footer class="cf-card" style="margin-top: 24px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: var(--cf-card-bg); border: 1px solid var(--cf-border);">
        <div style="font-size: 13px; color: var(--cf-text-sub);">
            <strong>Cloudflare Web Analytics</strong> &bull; v<?php echo esc_html(defined('CF_ANALYTICS_VERSION') ? CF_ANALYTICS_VERSION : '1.0.2'); ?> &bull; <?php esc_html_e('Crafted by', 'cf-edge-analytics'); ?> <a href="https://ripon.in" target="_blank" rel="noopener" style="color: var(--cf-blue); font-weight: 600; text-decoration: none;">RipoN</a>
        </div>
        <div style="display: flex; gap: 14px; font-size: 12px;">
            <a href="https://github.com/RipoN4/Cloudflare-Web-Analytics" target="_blank" rel="noopener" style="color: var(--cf-text-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <span class="dashicons dashicons-external"></span> <?php esc_html_e('GitHub Repository', 'cf-edge-analytics'); ?>
            </a>
        </div>
    </footer>
</div>
