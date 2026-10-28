<?php
/**
 * Widget: Daily Candle Bar Chart (Period-Aware)
 * With Total Counts and "View More" Button Link
 *
 * @package CF_Edge_Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

$chart_data      = $data['chart_period'] ?? $data['chart_30d'] ?? [];
$days            = $chart_data['days'] ?? [];
$max_views       = $chart_data['max_views'] ?? 100;
$period_short    = $chart_data['period_short_label'] ?? '30D';
$show_view_more  = !isset($show_button) || $show_button !== false;

// Calculate total period Pageviews & Unique Visitors
$total_period_pageviews = 0;
$total_period_visitors  = 0;
foreach ($days as $day_stat) {
    $total_period_pageviews += (int) ($day_stat['pageviews'] ?? 0);
    $total_period_visitors  += (int) ($day_stat['uniques'] ?? 0);
}

$format_stat = function($num) {
    $num = (float) $num;
    if ($num >= 1000000000) return round($num / 1000000000, 2) . 'B';
    if ($num >= 1000000) return round($num / 1000000, 2) . 'M';
    if ($num >= 1000) return round($num / 1000, 1) . 'K';
    return number_format($num);
};

$total_period_views_fmt    = $format_stat($total_period_pageviews);
$total_period_visitors_fmt = $format_stat($total_period_visitors);
?>

<div class="cf-card cf-30d-widget" id="cf-30d-container">
    <div class="cf-card-header">
        <div class="cf-30d-totals-box">
            <div class="cf-30d-total-stat">
                <span class="cf-total-label"><?php printf(esc_html__('%s Pageviews', 'cf-edge-analytics'), esc_html($period_short)); ?></span>
                <span class="cf-total-value cf-val-pageviews"><?php echo esc_html($total_period_views_fmt); ?></span>
            </div>
            <div class="cf-30d-total-stat">
                <span class="cf-total-label"><?php printf(esc_html__('%s Visitors', 'cf-edge-analytics'), esc_html($period_short)); ?></span>
                <span class="cf-total-value cf-val-uniques"><?php echo esc_html($total_period_visitors_fmt); ?></span>
            </div>
        </div>
        <div class="cf-legend-group">
            <div class="cf-legend-item">
                <span class="cf-legend-color cf-color-pageviews"></span>
                <span><?php esc_html_e('Pageviews', 'cf-edge-analytics'); ?></span>
            </div>
            <div class="cf-legend-item">
                <span class="cf-legend-color cf-color-uniques"></span>
                <span><?php esc_html_e('Unique Visitors', 'cf-edge-analytics'); ?></span>
            </div>
        </div>
    </div>

    <div class="cf-30d-chart-body">
        <!-- Left Y-Axis Labels -->
        <div class="cf-30d-y-axis">
            <span class="cf-y-label"><?php echo esc_html($chart_30d['max_views_fmt'] ?? '0'); ?></span>
            <span class="cf-y-label"><?php echo esc_html($chart_30d['mid_views_fmt'] ?? '0'); ?></span>
            <span class="cf-y-label">0</span>
        </div>

        <!-- 30 Bars Container -->
        <div class="cf-30d-bars-track" id="cf-30d-bars-track">
            <!-- Floating Hover Tooltip -->
            <div class="cf-bar-tooltip" id="cf-bar-tooltip" style="display: none;">
                <div class="cf-bar-tooltip-date" id="cf-bar-tooltip-date">--</div>
                <div class="cf-bar-tooltip-divider"></div>
                <div class="cf-bar-tooltip-columns">
                    <div class="cf-bar-tooltip-col">
                        <span class="cf-tt-label"><?php esc_html_e('Visitors', 'cf-edge-analytics'); ?></span>
                        <strong class="cf-tt-val cf-text-cyan cf-bar-tooltip-visitors" id="cf-bar-tooltip-visitors">0</strong>
                    </div>
                    <div class="cf-bar-tooltip-col">
                        <span class="cf-tt-label"><?php esc_html_e('Pageviews', 'cf-edge-analytics'); ?></span>
                        <strong class="cf-tt-val cf-text-blue cf-bar-tooltip-views" id="cf-bar-tooltip-views">0</strong>
                    </div>
                </div>
            </div>

            <div class="cf-bars-flex">
                <?php if (!empty($days)) : ?>
                    <?php foreach ($days as $day) :
                        $view_pct = $max_views > 0 ? min(100, max(4, round(($day['pageviews'] / $max_views) * 100, 1))) : 4;
                        $uniq_pct = $day['pageviews'] > 0 ? min(100, max(2, round(($day['uniques'] / $day['pageviews']) * 100, 1))) : 0;
                    ?>
                        <div class="cf-candle-col"
                             data-date="<?php echo esc_attr($day['formatted_date']); ?>"
                             data-views="<?php echo esc_attr(number_format($day['pageviews'])); ?>"
                             data-uniques="<?php echo esc_attr(number_format($day['uniques'])); ?>">
                            <div class="cf-candle-bar-slot">
                                <!-- Sky blue bar (Pageviews) -->
                                <div class="cf-candle-bar-outer" style="height: <?php echo esc_attr($view_pct); ?>%;">
                                    <!-- Dark navy blue inner base (Unique Visitors) -->
                                    <div class="cf-candle-bar-inner" style="height: <?php echo esc_attr($uniq_pct); ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bottom X-Axis Date Range -->
    <div class="cf-30d-date-range">
        <span class="cf-range-start">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php echo esc_html($chart_30d['start_date'] ?? ''); ?>
        </span>
        <span class="cf-range-end">
            <?php echo esc_html($chart_30d['end_date'] ?? ''); ?>
            <span class="dashicons dashicons-calendar-alt"></span>
        </span>
    </div>

    <?php if ($show_view_more) : ?>
        <!-- View More Link / Button -->
        <div class="cf-widget-footer">
            <a href="<?php echo esc_url(admin_url('index.php?page=cf-edge-analytics')); ?>" class="button cf-btn cf-btn-view-more">
                <span><?php esc_html_e('View More', 'cf-edge-analytics'); ?></span>
                <span class="dashicons dashicons-arrow-right-alt2 cf-btn-icon"></span>
            </a>
        </div>
    <?php endif; ?>
</div>
