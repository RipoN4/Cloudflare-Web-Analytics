<?php
/**
 * Widget: Last 24 Hours Area Line Graph
 * With Simple Text & Number 2-Column Section for Dashboard Widget
 *
 * @package CF_Edge_Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

$summary       = $data['summary'] ?? [];
$chart_24h     = $data['chart_24h'] ?? [];
$points        = $chart_24h['points'] ?? [];
$y_ticks       = $chart_24h['y_ticks'] ?? [];
$x_ticks       = $chart_24h['x_ticks'] ?? [];
$timezone      = $chart_24h['timezone'] ?? 'GMT+5:30';

// Slice top 5 for the 2-column breakdown based on 24-hour data
$top_referrers = !empty($data['top_referrers_24h']) ? array_slice($data['top_referrers_24h'], 0, 5) : array_slice($data['top_referrers'] ?? [], 0, 5);
$top_paths     = !empty($data['top_paths_24h']) ? array_slice($data['top_paths_24h'], 0, 5) : array_slice($data['top_paths'] ?? [], 0, 5);
$show_columns  = !isset($show_breakdowns) || $show_breakdowns !== false;
?>

<div class="cf-card cf-24h-widget" id="cf-24h-container">
    <div class="cf-card-header">
        <div class="cf-24h-metric-badge">
            <span class="cf-pulse-dot"></span>
            <span class="cf-metric-label"><?php esc_html_e('Total page views', 'cf-edge-analytics'); ?></span>
            <span class="cf-metric-number"><?php echo esc_html($summary['total_24h_views_fmt'] ?? '0'); ?></span>
        </div>
        <div class="cf-card-meta">
            <span class="dashicons dashicons-clock"></span>
            <span><?php esc_html_e('Last 24 Hours', 'cf-edge-analytics'); ?></span>
        </div>
    </div>

    <div class="cf-chart-interactive-wrapper" id="cf-svg-chart-wrapper">
        <!-- Dynamic Floating Pill Tooltip -->
        <div class="cf-spline-tooltip" id="cf-spline-tooltip">
            <div class="cf-spline-tooltip-time" id="cf-spline-tooltip-time">--:--</div>
            <div class="cf-spline-tooltip-val">
                <span class="cf-dot-small"></span>
                <span><?php esc_html_e('Total page views', 'cf-edge-analytics'); ?>:</span>
                <strong class="cf-spline-tooltip-num" id="cf-spline-tooltip-num">0</strong>
            </div>
        </div>

        <svg class="cf-spline-svg" viewBox="<?php echo esc_attr($chart_24h['viewBox'] ?? '0 0 900 240'); ?>" preserveAspectRatio="none" id="cf-spline-svg">
            <defs>
                <linearGradient id="cfAreaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.30" />
                    <stop offset="70%" stop-color="#93c5fd" stop-opacity="0.08" />
                    <stop offset="100%" stop-color="#ffffff" stop-opacity="0.0" />
                </linearGradient>
                <filter id="cfLineGlow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="1.5" stdDeviation="2.5" flood-color="#2563eb" flood-opacity="0.3" />
                </filter>
            </defs>

            <!-- Y-Axis Gridlines & Ticks -->
            <g class="cf-grid-lines">
                <?php if (!empty($y_ticks)) : ?>
                    <?php foreach ($y_ticks as $yt) : ?>
                        <line x1="<?php echo esc_attr($chart_24h['pad_left'] ?? 60); ?>" y1="<?php echo esc_attr($yt['y']); ?>" x2="<?php echo esc_attr(($chart_24h['width'] ?? 900) - ($chart_24h['pad_right'] ?? 20)); ?>" y2="<?php echo esc_attr($yt['y']); ?>" stroke="#e2e8f0" stroke-dasharray="4 4" stroke-width="1" />
                        <text x="<?php echo esc_attr(($chart_24h['pad_left'] ?? 60) - 12); ?>" y="<?php echo esc_attr($yt['y'] + 4); ?>" fill="#64748b" font-size="11" text-anchor="end" font-family="-apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif"><?php echo esc_html($yt['label']); ?></text>
                    <?php endforeach; ?>
                <?php endif; ?>
            </g>

            <!-- Gradient Area Fill -->
            <?php if (!empty($chart_24h['area_path'])) : ?>
                <path class="cf-path-area" d="<?php echo esc_attr($chart_24h['area_path']); ?>" fill="url(#cfAreaGradient)" />
            <?php endif; ?>

            <!-- Smooth Cubic Bezier Line -->
            <?php if (!empty($chart_24h['line_path'])) : ?>
                <path class="cf-path-line" d="<?php echo esc_attr($chart_24h['line_path']); ?>" fill="none" stroke="#2563eb" stroke-width="2.5" filter="url(#cfLineGlow)" />
            <?php endif; ?>

            <!-- Interactive Tracking Guidelines & Cursor Point (Moved via JS) -->
            <g class="cf-cursor-group" id="cf-cursor-group" style="display: none;">
                <line id="cf-cursor-vline" x1="0" y1="<?php echo esc_attr($chart_24h['pad_top'] ?? 25); ?>" x2="0" y2="<?php echo esc_attr($chart_24h['baseline_y'] ?? 205); ?>" stroke="#3b82f6" stroke-width="1.5" stroke-dasharray="3 3" opacity="0.9" />
                <circle id="cf-cursor-dot-glow" cx="0" cy="0" r="7" fill="#3b82f6" opacity="0.25" />
                <circle id="cf-cursor-dot" cx="0" cy="0" r="4.5" fill="#ffffff" stroke="#1d4ed8" stroke-width="2.5" />
            </g>
        </svg>

        <!-- Hidden JSON Points for JS Hover Engine -->
        <script type="application/json" id="cf-hourly-points-data">
            <?php echo wp_json_encode($points); ?>
        </script>
    </div>

    <!-- Bottom X-Axis -->
    <div class="cf-x-axis-row">
        <div class="cf-x-ticks">
            <?php if (!empty($x_ticks)) : ?>
                <?php foreach ($x_ticks as $xt) : ?>
                    <span class="cf-x-tick-label"><?php echo esc_html($xt['label']); ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="cf-timezone-label">
            <span><?php printf(esc_html__('Time (%s)', 'cf-edge-analytics'), esc_html($timezone)); ?></span>
        </div>
    </div>

    <?php if ($show_columns) : ?>
        <!-- 2 Columns: Top 5 Referrers & Top 5 Pages by Views (Simple text & number) -->
        <div class="cf-widget-two-cols">
            <!-- Column 1: Top 5 Referrers -->
            <div class="cf-subcard">
                <div class="cf-subcard-header">
                    <div class="cf-header-title-wrap">
                        <span class="dashicons dashicons-networking cf-header-icon"></span>
                        <h4 class="cf-subcard-title"><?php esc_html_e('Top Referrers (24h)', 'cf-edge-analytics'); ?></h4>
                    </div>
                </div>
                <div class="cf-simple-list">
                    <?php if (!empty($top_referrers)) :
                        foreach ($top_referrers as $ref) : ?>
                            <div class="cf-simple-row">
                                <span class="cf-simple-text" title="<?php echo esc_attr($ref['name']); ?>"><?php echo esc_html($ref['name']); ?></span>
                                <span class="cf-simple-count"><?php echo esc_html($ref['visits_fmt']); ?></span>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <div class="cf-empty-state">
                            <p><?php esc_html_e('No referrer data available.', 'cf-edge-analytics'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Column 2: Top 5 Pages by Views (24h) -->
            <div class="cf-subcard">
                <div class="cf-subcard-header">
                    <div class="cf-header-title-wrap">
                        <span class="dashicons dashicons-admin-page cf-header-icon"></span>
                        <h4 class="cf-subcard-title"><?php esc_html_e('Top Pages by Views (24h)', 'cf-edge-analytics'); ?></h4>
                    </div>
                </div>
                <div class="cf-simple-list">
                    <?php if (!empty($top_paths)) :
                        foreach ($top_paths as $path_item) :
                            $path_val = $path_item['path'] ?? '/';
                            $page_url = home_url($path_val);

                            // Resolve actual WordPress title if available
                            $post_title = '';
                            if ($path_val === '/' || empty($path_val)) {
                                $site_name = get_bloginfo('name');
                                $post_title = $site_name ? $site_name : __('Home Page', 'cf-edge-analytics');
                            } else {
                                $post_id = url_to_postid($page_url);
                                if (!$post_id) {
                                    $post_id = url_to_postid(trailingslashit($page_url));
                                }
                                if ($post_id) {
                                    $post_title = get_the_title($post_id);
                                }
                                if (empty($post_title)) {
                                    $page_obj = get_page_by_path(trim($path_val, '/'));
                                    if ($page_obj && !empty($page_obj->post_title)) {
                                        $post_title = $page_obj->post_title;
                                    }
                                }
                            }

                            if (empty($post_title)) {
                                $post_title = !empty($path_item['title']) ? $path_item['title'] : $path_val;
                            }
                        ?>
                            <div class="cf-simple-row">
                                <a href="<?php echo esc_url($page_url); ?>" target="_blank" rel="noopener noreferrer" class="cf-simple-link" title="<?php echo esc_attr($post_title . ' (' . $path_val . ')'); ?>">
                                    <?php echo esc_html($post_title); ?>
                                </a>
                                <span class="cf-simple-count"><?php echo esc_html($path_item['count_fmt']); ?></span>
                            </div>
                        <?php endforeach;
                    else : ?>
                        <div class="cf-empty-state">
                            <p><?php esc_html_e('No page data available.', 'cf-edge-analytics'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
