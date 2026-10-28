<?php
/**
 * Widget: Three Top 10 Breakdown Lists (Pages, Referrers, Locations)
 * Designed for dedicated Analytics Dashboard page (page=cf-edge-analytics)
 *
 * @package CF_Edge_Analytics
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_24h        = !empty($is_24h) || (($data['period'] ?? '') === '24h');
$top_paths     = $data['top_paths'] ?? [];
$top_referrers = $data['top_referrers'] ?? [];
$top_countries = $data['top_countries'] ?? [];
$period_label  = !empty($period_lbl) ? $period_lbl : ($data['period_label'] ?? __('Last 30 Days', 'cf-edge-analytics'));
?>

<div class="cf-breakdowns-grid">
    <!-- Top 10 Pages -->
    <div class="cf-card cf-breakdown-card">
        <div class="cf-card-header">
            <div class="cf-header-title-wrap">
                <span class="dashicons dashicons-admin-page cf-header-icon"></span>
                <h3 class="cf-card-title"><?php esc_html_e('Top 10 Pages', 'cf-edge-analytics'); ?></h3>
            </div>
            <span class="cf-tag-pill"><?php echo esc_html($period_label); ?></span>
        </div>
        <div class="cf-breakdown-list">
            <?php if (!empty($top_paths)) :
                $rank = 1;
                foreach ($top_paths as $path_item) :
                    $page_url = home_url($path_item['path']);
                ?>
                    <div class="cf-list-item">
                        <div class="cf-item-progress-bg" style="width: <?php echo esc_attr($path_item['percentage']); ?>%;"></div>
                        <div class="cf-item-content">
                            <span class="cf-rank-badge <?php echo $rank <= 3 ? 'cf-rank-top' : ''; ?>"><?php echo esc_html($rank++); ?></span>
                            <div class="cf-item-info">
                                <div class="cf-item-title-row">
                                    <a href="<?php echo esc_url($page_url); ?>" target="_blank" rel="noopener noreferrer" class="cf-item-primary cf-page-anchor" title="<?php echo esc_attr($path_item['title']); ?>">
                                        <?php echo esc_html($path_item['title']); ?>
                                    </a>
                                    <?php if (!empty($path_item['edit_url']) && !empty($path_item['post_id']) && current_user_can('edit_post', $path_item['post_id'])) : ?>
                                        <a href="<?php echo esc_url($path_item['edit_url']); ?>" class="cf-edit-post-link" title="<?php esc_attr_e('Edit in WordPress', 'cf-edge-analytics'); ?>" target="_blank" rel="noopener">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <span class="cf-item-secondary" title="<?php echo esc_attr($path_item['path']); ?>"><?php echo esc_html($path_item['path']); ?></span>
                            </div>
                            <span class="cf-item-count"><?php echo esc_html($path_item['count_fmt']); ?></span>
                        </div>
                    </div>
                <?php endforeach;
            else : ?>
                <div class="cf-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php esc_html_e('No path data available yet.', 'cf-edge-analytics'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top 10 Referrers -->
    <div class="cf-card cf-breakdown-card">
        <div class="cf-card-header">
            <div class="cf-header-title-wrap">
                <span class="dashicons dashicons-networking cf-header-icon"></span>
                <h3 class="cf-card-title"><?php esc_html_e('Top 10 Referrers', 'cf-edge-analytics'); ?></h3>
            </div>
            <span class="cf-tag-pill"><?php echo esc_html($period_label); ?></span>
        </div>
        <div class="cf-breakdown-list">
            <?php if (!empty($top_referrers)) :
                foreach ($top_referrers as $ref) : ?>
                    <div class="cf-list-item">
                        <div class="cf-item-progress-bg" style="width: <?php echo esc_attr($ref['percentage']); ?>%;"></div>
                        <div class="cf-item-content">
                            <span class="cf-rank-badge <?php echo $ref['rank'] <= 3 ? 'cf-rank-top' : ''; ?>"><?php echo esc_html($ref['rank']); ?></span>
                            <div class="cf-item-icon-box">
                                <span class="dashicons <?php echo esc_attr($ref['dashicon'] ?? 'dashicons-admin-site'); ?>"></span>
                            </div>
                            <div class="cf-item-info">
                                <span class="cf-item-primary"><?php echo esc_html($ref['name']); ?></span>
                                <span class="cf-item-secondary"><?php echo esc_html($ref['domain']); ?></span>
                            </div>
                            <span class="cf-item-count"><?php echo esc_html($ref['visits_fmt']); ?></span>
                        </div>
                    </div>
                <?php endforeach;
            else : ?>
                <div class="cf-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php esc_html_e('No referrer data available.', 'cf-edge-analytics'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top 10 Locations (With Real Country Flags, No ISO Codes) -->
    <div class="cf-card cf-breakdown-card">
        <div class="cf-card-header">
            <div class="cf-header-title-wrap">
                <span class="dashicons dashicons-location cf-header-icon"></span>
                <h3 class="cf-card-title"><?php esc_html_e('Top 10 Locations', 'cf-edge-analytics'); ?></h3>
            </div>
            <span class="cf-tag-pill"><?php echo esc_html($period_label); ?></span>
        </div>
        <div class="cf-breakdown-list">
            <?php if (!empty($top_countries)) :
                $c_rank = 1;
                foreach ($top_countries as $country) :
                    $iso_code = strtolower(trim($country['iso'] ?? ''));
                    $flag_1x  = 'https://flagcdn.com/24x18/' . esc_attr($iso_code) . '.png';
                    $flag_2x  = 'https://flagcdn.com/48x36/' . esc_attr($iso_code) . '.png 2x';
                ?>
                    <div class="cf-list-item">
                        <div class="cf-item-progress-bg" style="width: <?php echo esc_attr($country['percentage']); ?>%;"></div>
                        <div class="cf-item-content">
                            <span class="cf-rank-badge <?php echo $c_rank <= 3 ? 'cf-rank-top' : ''; ?>"><?php echo esc_html($c_rank++); ?></span>
                            <div class="cf-flag-box">
                                <img src="<?php echo esc_url($flag_1x); ?>"
                                     srcset="<?php echo esc_attr($flag_2x); ?>"
                                     width="24"
                                     height="18"
                                     alt="<?php echo esc_attr($country['name']); ?>"
                                     class="cf-flag-img"
                                     loading="lazy" />
                            </div>
                            <div class="cf-item-info">
                                <span class="cf-item-primary"><?php echo esc_html($country['name']); ?></span>
                            </div>
                            <span class="cf-item-count"><?php echo esc_html($country['count_fmt']); ?></span>
                        </div>
                    </div>
                <?php endforeach;
            else : ?>
                <div class="cf-empty-state">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php esc_html_e('No country data available.', 'cf-edge-analytics'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
