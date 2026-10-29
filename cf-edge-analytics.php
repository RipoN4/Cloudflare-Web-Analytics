<?php
/**
 * Plugin Name: Web Analytics for Cloudflare
 * Plugin URI: https://github.com/RipoN4/Cloudflare-Web-Analytics
 * Description: Privacy-first, lightweight Cloudflare Web Analytics directly in your WordPress dashboard. 100% free, zero database bloat, no cookies, powered by Cloudflare GraphQL API.
 * Version: 1.1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: RipoN
 * Author URI: https://ripon.in
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf-edge-analytics
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CF_ANALYTICS_VERSION', '1.1.0');
define('CF_ANALYTICS_PATH', plugin_dir_path(__FILE__));
define('CF_ANALYTICS_URL', plugin_dir_url(__FILE__));
define('CF_ANALYTICS_BASENAME', plugin_basename(__FILE__));

// Load Core API and Helper Class
require_once CF_ANALYTICS_PATH . 'includes/class-cf-api.php';

/**
 * Main Plugin Class
 */
class CF_Edge_Analytics {

    private static $instance = null;
    public $api;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api = new CF_Analytics_API();

        // Hooks
        add_action('admin_menu', [$this, 'register_admin_menus']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_dashboard_setup', [$this, 'register_dashboard_widgets']);

        // AJAX and Admin Post handlers for cache refresh
        add_action('wp_ajax_cf_analytics_refresh', [$this, 'ajax_refresh_cache']);
        add_action('admin_post_cf_analytics_refresh', [$this, 'post_refresh_cache']);

        // Admin Post handler for complete plugin reset
        add_action('admin_post_cf_analytics_reset', [$this, 'post_reset_plugin']);

        // Admin Bar Quick Traffic Counter
        add_action('admin_bar_menu', [$this, 'add_admin_bar_node'], 99);
        add_action('wp_head', [$this, 'admin_bar_css']);
        add_action('admin_head', [$this, 'admin_bar_css']);

        // CSV Export Handler
        add_action('admin_post_cf_analytics_export_csv', [$this, 'export_analytics_csv']);

        // Auto-detection and Property Picker handlers
        add_action('wp_ajax_cf_analytics_autodetect', [$this, 'ajax_autodetect_credentials']);
        add_action('wp_ajax_cf_analytics_get_sites', [$this, 'ajax_get_account_sites']);
        add_action('wp_ajax_cf_analytics_save_selected_site', [$this, 'ajax_save_selected_site']);
        add_action('update_option_cf_analytics_api_token', [$this, 'on_api_token_updated'], 10, 2);

        // Plugin Action Links
        add_filter('plugin_action_links_' . CF_ANALYTICS_BASENAME, [$this, 'plugin_action_links']);
    }

    /**
     * Get Required User Capability to View Analytics Reports
     *
     * @return string
     */
    public function get_view_capability() {
        $cap = get_option('cf_analytics_view_capability', 'manage_options');
        $valid = ['manage_options', 'edit_others_posts', 'publish_posts'];
        return in_array($cap, $valid, true) ? $cap : 'manage_options';
    }

    /**
     * Sanitize Capability Setting
     *
     * @param string $value
     * @return string
     */
    public function sanitize_capability($value) {
        $valid = ['manage_options', 'edit_others_posts', 'publish_posts'];
        return in_array($value, $valid, true) ? $value : 'manage_options';
    }

    /**
     * Register Admin Menus
     */
    public function register_admin_menus() {
        // Dedicated Analytics Dashboard Page under Dashboard
        $dashboard_hook = add_submenu_page(
            'index.php',
            __('Cloudflare Analytics', 'cf-edge-analytics'),
            __('CF Analytics', 'cf-edge-analytics'),
            $this->get_view_capability(),
            'cf-edge-analytics',
            [$this, 'render_dashboard_page']
        );

        // Settings Page under Settings (Strictly manage_options for Admins only)
        $settings_hook = add_options_page(
            __('Cloudflare Analytics Settings', 'cf-edge-analytics'),
            __('Cloudflare Analytics', 'cf-edge-analytics'),
            'manage_options',
            'cf-edge-analytics-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register WordPress Dashboard Widgets for wp-admin/index.php
     * Exactly 2 Widgets:
     * 1. Cloudflare: 24-Hour Traffic (Graph + Top 5 Referrers & Top 5 Pages)
     * 2. Cloudflare: 30-Day Visits & Pageviews (Graph + View More button)
     */
    public function register_dashboard_widgets() {
        if (!current_user_can($this->get_view_capability())) {
            return;
        }

        // Widget 1: 24h Area Graph + Top 5 Referrers & Top 5 Pages in 2 columns
        wp_add_dashboard_widget(
            'cf_widget_24h_overview',
            __('Cloudflare: 24-Hour Traffic', 'cf-edge-analytics'),
            [$this, 'render_widget_24h_overview']
        );

        // Widget 2: 30-Day Activity Bar Chart + View More link
        wp_add_dashboard_widget(
            'cf_widget_30d_analytics',
            __('Cloudflare: 30-Day Visits & Pageviews', 'cf-edge-analytics'),
            [$this, 'render_widget_30d_chart']
        );
    }

    /**
     * Register Settings
     */
    public function register_settings() {
        register_setting('cf_analytics_settings_group', 'cf_analytics_api_token', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);

        register_setting('cf_analytics_settings_group', 'cf_analytics_account_id', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);

        register_setting('cf_analytics_settings_group', 'cf_analytics_site_tag', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);

        register_setting('cf_analytics_settings_group', 'cf_analytics_zone_id', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);

        register_setting('cf_analytics_settings_group', 'cf_analytics_cache_ttl', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 300,
        ]);

        register_setting('cf_analytics_settings_group', 'cf_analytics_view_capability', [
            'type'              => 'string',
            'sanitize_callback' => [$this, 'sanitize_capability'],
            'default'           => 'manage_options',
        ]);

        register_setting('cf_analytics_settings_group', 'cf_analytics_admin_bar_counter', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ]);
    }

    /**
     * Enqueue Admin Styles and Scripts
     */
    public function enqueue_admin_assets($hook_suffix) {
        // Enqueue on Dashboard, CF Analytics page, and CF Settings page
        $allowed_hooks = [
            'index.php',
            'dashboard_page_cf-edge-analytics',
            'settings_page_cf-edge-analytics-settings',
        ];

        if (!in_array($hook_suffix, $allowed_hooks, true)) {
            return;
        }

        // Native WordPress Dashicons
        wp_enqueue_style('dashicons');

        $css_ver = file_exists(CF_ANALYTICS_PATH . 'assets/css/analytics-admin.css') ? filemtime(CF_ANALYTICS_PATH . 'assets/css/analytics-admin.css') : CF_ANALYTICS_VERSION;
        $js_ver  = file_exists(CF_ANALYTICS_PATH . 'assets/js/analytics-charts.js') ? filemtime(CF_ANALYTICS_PATH . 'assets/js/analytics-charts.js') : CF_ANALYTICS_VERSION;

        // Plugin CSS
        wp_enqueue_style(
            'cf-analytics-admin-css',
            CF_ANALYTICS_URL . 'assets/css/analytics-admin.css',
            ['dashicons'],
            $css_ver
        );

        // Plugin JS
        wp_enqueue_script(
            'cf-analytics-admin-js',
            CF_ANALYTICS_URL . 'assets/js/analytics-charts.js',
            [],
            $js_ver,
            true
        );

        // Localize script for AJAX refresh
        wp_localize_script('cf-analytics-admin-js', 'cfAnalyticsData', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('cf_analytics_nonce'),
            'refreshUrl'=> wp_nonce_url(admin_url('admin-post.php?action=cf_analytics_refresh'), 'cf_analytics_refresh_action'),
            'i18n'      => [
                'refreshing' => __('Refreshing...', 'cf-edge-analytics'),
                'refreshed'  => __('Refreshed!', 'cf-edge-analytics'),
                'error'      => __('Failed to refresh', 'cf-edge-analytics'),
                'visitors'   => __('Visitors', 'cf-edge-analytics'),
                'pageviews'  => __('Pageviews', 'cf-edge-analytics'),
                'totalViews' => __('Total page views', 'cf-edge-analytics'),
            ]
        ]);
    }

    /**
     * Render Dedicated Dashboard Page
     */
    public function render_dashboard_page() {
        if (!current_user_can($this->get_view_capability())) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cf-edge-analytics'));
        }

        $period = isset($_GET['period']) ? sanitize_key($_GET['period']) : '30d';
        if (!in_array($period, ['24h', '7d', '14d', '30d'], true)) {
            $period = '30d';
        }

        $force_refresh  = !empty($_GET['refresh']);
        $analytics_data = $this->api->get_analytics_data($force_refresh, $period);
        $is_configured  = $this->api->is_configured();
        $cache_info     = $this->api->get_cache_info($period);
        $current_period = $period;

        include CF_ANALYTICS_PATH . 'templates/dashboard-view.php';
    }

    /**
     * Render Settings Page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'cf-edge-analytics'));
        }

        include CF_ANALYTICS_PATH . 'templates/settings-view.php';
    }

    /**
     * Render Widget: 24h Area Graph + Metrics
     */
    public function render_widget_24h_overview() {
        $data = $this->api->get_analytics_data();
        $is_configured = $this->api->is_configured();
        $is_widget = true;
        include CF_ANALYTICS_PATH . 'templates/widgets/widget-24h.php';
    }

    /**
     * Render Widget: 30-Day Activity Bar Chart
     */
    public function render_widget_30d_chart() {
        $data = $this->api->get_analytics_data();
        $is_configured = $this->api->is_configured();
        $is_widget = true;
        include CF_ANALYTICS_PATH . 'templates/widgets/widget-30d.php';
    }

    /**
     * Render Widget: Top Breakdowns
     */
    public function render_widget_breakdowns() {
        $data = $this->api->get_analytics_data();
        $is_configured = $this->api->is_configured();
        $is_widget = true;
        include CF_ANALYTICS_PATH . 'templates/widgets/widget-breakdowns.php';
    }

    /**
     * AJAX Cache Refresh Handler
     */
    public function ajax_refresh_cache() {
        check_ajax_referer('cf_analytics_nonce', 'nonce');

        if (!current_user_can($this->get_view_capability())) {
            wp_send_json_error(['message' => __('Unauthorized', 'cf-edge-analytics')], 403);
        }

        $period = isset($_POST['period']) ? sanitize_key($_POST['period']) : '30d';
        if (!in_array($period, ['24h', '7d', '14d', '30d'], true)) {
            $period = '30d';
        }

        $result = $this->api->get_analytics_data(true, $period);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message()
            ], 400);
        }

        $cache_info = $this->api->get_cache_info($period);
        wp_send_json_success([
            'message'    => __('Cache refreshed successfully', 'cf-edge-analytics'),
            'cache_time' => $cache_info['formatted_time']
        ]);
    }

    /**
     * Admin POST Cache Refresh Handler (fallback for non-JS)
     */
    public function post_refresh_cache() {
        check_admin_referer('cf_analytics_refresh_action');

        if (!current_user_can($this->get_view_capability())) {
            wp_die(__('Unauthorized action.', 'cf-edge-analytics'));
        }

        $period = isset($_REQUEST['period']) ? sanitize_key($_REQUEST['period']) : '30d';
        if (!in_array($period, ['24h', '7d', '14d', '30d'], true)) {
            $period = '30d';
        }

        $this->api->get_analytics_data(true, $period);

        $redirect = wp_get_referer();
        if (!$redirect) {
            $redirect = admin_url('index.php?page=cf-edge-analytics');
        }

        $redirect = add_query_arg([
            'cf_refreshed' => '1',
            'period'       => $period,
        ], $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * Add Quick Traffic Counter to WordPress Admin Bar
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function add_admin_bar_node($wp_admin_bar) {
        if (!get_option('cf_analytics_admin_bar_counter', 1)) {
            return;
        }

        if (!current_user_can($this->get_view_capability())) {
            return;
        }

        // Fetch cached 24h data (zero blocking remote calls)
        $data = $this->api->get_analytics_data(false, '24h');
        $summary = isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [];

        $pageviews = 0;
        if (isset($summary['period_views'])) {
            $pageviews = (int) $summary['period_views'];
        } elseif (isset($summary['total_24h_views'])) {
            $pageviews = (int) $summary['total_24h_views'];
        } elseif (isset($summary['today_pageviews'])) {
            $pageviews = (int) $summary['today_pageviews'];
        } elseif (isset($summary['pageviews'])) {
            $pageviews = (int) $summary['pageviews'];
        }

        $visitors = 0;
        if (isset($summary['period_visitors'])) {
            $visitors = (int) $summary['period_visitors'];
        } elseif (isset($summary['today_visitors'])) {
            $visitors = (int) $summary['today_visitors'];
        } elseif (isset($summary['visitors'])) {
            $visitors = (int) $summary['visitors'];
        }

        $formatted_views    = number_format_i18n($pageviews);
        $formatted_visitors = number_format_i18n($visitors);

        // Root Node in Admin Bar
        $wp_admin_bar->add_node([
            'id'    => 'cf-analytics-quick-stats',
            'title' => '<span class="ab-icon dashicons dashicons-chart-area"></span><span class="ab-label">' . sprintf(__('%s Views', 'cf-edge-analytics'), $formatted_views) . '</span>',
            'href'  => admin_url('index.php?page=cf-edge-analytics'),
            'meta'  => [
                'title' => __('Cloudflare Web Analytics: Last 24 Hours Traffic', 'cf-edge-analytics'),
            ],
        ]);

        // Sub-node 1: Pageviews
        $wp_admin_bar->add_node([
            'id'     => 'cf-analytics-sub-views',
            'parent' => 'cf-analytics-quick-stats',
            'title'  => sprintf(__('24h Pageviews: %s', 'cf-edge-analytics'), '<strong>' . $formatted_views . '</strong>'),
            'href'   => admin_url('index.php?page=cf-edge-analytics&period=24h'),
        ]);

        // Sub-node 2: Visitors
        $wp_admin_bar->add_node([
            'id'     => 'cf-analytics-sub-visitors',
            'parent' => 'cf-analytics-quick-stats',
            'title'  => sprintf(__('24h Unique Visitors: %s', 'cf-edge-analytics'), '<strong>' . $formatted_visitors . '</strong>'),
            'href'   => admin_url('index.php?page=cf-edge-analytics&period=24h'),
        ]);

        // Sub-node 3: View Full Dashboard
        $wp_admin_bar->add_node([
            'id'     => 'cf-analytics-sub-full',
            'parent' => 'cf-analytics-quick-stats',
            'title'  => __('Full Analytics Dashboard &rarr;', 'cf-edge-analytics'),
            'href'   => admin_url('index.php?page=cf-edge-analytics'),
        ]);

        // Sub-node 4: Settings (Administrators only)
        if (current_user_can('manage_options')) {
            $wp_admin_bar->add_node([
                'id'     => 'cf-analytics-sub-settings',
                'parent' => 'cf-analytics-quick-stats',
                'title'  => __('Cloudflare Settings', 'cf-edge-analytics'),
                'href'   => admin_url('options-general.php?page=cf-edge-analytics-settings'),
            ]);
        }
    }

    /**
     * Minimal CSS for Admin Bar Counter
     */
    public function admin_bar_css() {
        if (!is_admin_bar_showing() || !get_option('cf_analytics_admin_bar_counter', 1) || !current_user_can($this->get_view_capability())) {
            return;
        }
        echo '<style id="cf-analytics-admin-bar-css">
            #wpadminbar #wp-admin-bar-cf-analytics-quick-stats .ab-icon {
                font-family: dashicons;
                font-size: 17px;
                line-height: 1;
                margin-top: 5px;
                color: #f58220;
            }
            #wpadminbar #wp-admin-bar-cf-analytics-quick-stats .ab-label {
                font-weight: 600;
                letter-spacing: -0.01em;
            }
            #wpadminbar #wp-admin-bar-cf-analytics-quick-stats:hover .ab-icon {
                color: #ff9d47;
            }
        </style>';
    }

    /**
     * 1-Click CSV Export Handler
     */
    public function export_analytics_csv() {
        // Support either nonce name for backwards compatibility
        $nonce_verified = false;
        if (isset($_REQUEST['_wpnonce'])) {
            $nonce = sanitize_key($_REQUEST['_wpnonce']);
            if (wp_verify_nonce($nonce, 'cf_analytics_export_csv_action') || wp_verify_nonce($nonce, 'cf_analytics_export_action')) {
                $nonce_verified = true;
            }
        }

        if (!$nonce_verified) {
            wp_die(__('The link you followed has expired. Please try again.', 'cf-edge-analytics'), 403);
        }

        if (!current_user_can($this->get_view_capability())) {
            wp_die(__('Unauthorized action.', 'cf-edge-analytics'), 403);
        }

        $period = isset($_GET['period']) ? sanitize_key($_GET['period']) : '30d';
        if (!in_array($period, ['24h', '7d', '14d', '30d'], true)) {
            $period = '30d';
        }

        $data = $this->api->get_analytics_data(false, $period);
        if (is_wp_error($data) || empty($data)) {
            wp_die(__('Unable to generate export. No analytics data available.', 'cf-edge-analytics'));
        }

        $site_name = sanitize_title(get_bloginfo('name'));
        if (empty($site_name)) {
            $site_name = 'wordpress';
        }
        $filename = sprintf('cf-analytics-%s-%s-%s.csv', $site_name, $period, gmdate('Y-m-d-His'));

        // Clean any preceding output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Document Meta
        fputcsv($output, [__('Cloudflare Web Analytics Report', 'cf-edge-analytics')]);
        fputcsv($output, [__('Website', 'cf-edge-analytics'), get_bloginfo('name') . ' (' . home_url() . ')']);
        fputcsv($output, [__('Timeframe', 'cf-edge-analytics'), strtoupper($period)]);
        fputcsv($output, [__('Export Date (UTC)', 'cf-edge-analytics'), gmdate('Y-m-d H:i:s')]);
        fputcsv($output, []); // separator

        // Summary Section
        $summary       = isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [];
        $total_views   = isset($summary['period_views']) ? (int) $summary['period_views'] : (isset($summary['total_30d_views']) ? (int) $summary['total_30d_views'] : 0);
        $total_uniques = isset($summary['period_visitors']) ? (int) $summary['period_visitors'] : (isset($summary['total_30d_visitors']) ? (int) $summary['total_30d_visitors'] : 0);
        $views_per_visit = $total_uniques > 0 ? round($total_views / $total_uniques, 2) : 1;
        $cache_offload = isset($summary['cache_offload_rate']) ? $summary['cache_offload_rate'] : 100;

        fputcsv($output, [__('SUMMARY METRICS', 'cf-edge-analytics')]);
        fputcsv($output, [__('Metric', 'cf-edge-analytics'), __('Value', 'cf-edge-analytics')]);
        fputcsv($output, [__('Unique Visitors', 'cf-edge-analytics'), $total_uniques]);
        fputcsv($output, [__('Total Pageviews', 'cf-edge-analytics'), $total_views]);
        fputcsv($output, [__('Views Per Visitor', 'cf-edge-analytics'), $views_per_visit]);
        fputcsv($output, [__('Cache Offload Rate', 'cf-edge-analytics'), $cache_offload . '%']);
        fputcsv($output, []); // separator

        // Timeline Data
        fputcsv($output, [__('TRAFFIC TIMELINE', 'cf-edge-analytics')]);
        fputcsv($output, [__('Date / Time', 'cf-edge-analytics'), __('Unique Visitors', 'cf-edge-analytics'), __('Total Pageviews', 'cf-edge-analytics')]);

        $timeline_rows = [];
        if ($period === '24h' && !empty($data['chart_24h']['points'])) {
            $timeline_rows = $data['chart_24h']['points'];
        } elseif (!empty($data['chart_period']['days'])) {
            $timeline_rows = $data['chart_period']['days'];
        } elseif (!empty($data['chart_30d']['days'])) {
            $timeline_rows = $data['chart_30d']['days'];
        }

        if (!empty($timeline_rows) && is_array($timeline_rows)) {
            foreach ($timeline_rows as $row) {
                $label = $row['formatted_date'] ?? ($row['date'] ?? ($row['label'] ?? ''));
                $views = $row['views'] ?? ($row['pageviews'] ?? 0);
                $visitors = $row['visitors'] ?? ($row['uniques'] ?? 0);
                fputcsv($output, [$label, $visitors, $views]);
            }
        }
        fputcsv($output, []); // separator

        // Top Visited Pages
        fputcsv($output, [__('TOP VISITED PAGES', 'cf-edge-analytics')]);
        fputcsv($output, [__('Rank', 'cf-edge-analytics'), __('Page Title', 'cf-edge-analytics'), __('URL Path', 'cf-edge-analytics'), __('Pageviews', 'cf-edge-analytics'), __('Share %', 'cf-edge-analytics')]);
        if (!empty($data['top_paths']) && is_array($data['top_paths'])) {
            $rank = 1;
            foreach ($data['top_paths'] as $path) {
                fputcsv($output, [
                    $rank++,
                    $path['title'] ?? '',
                    $path['path'] ?? '',
                    $path['count'] ?? 0,
                    ($path['percentage'] ?? 0) . '%',
                ]);
            }
        }
        fputcsv($output, []); // separator

        // Top Referrers
        fputcsv($output, [__('TOP REFERRERS', 'cf-edge-analytics')]);
        fputcsv($output, [__('Rank', 'cf-edge-analytics'), __('Referrer Name', 'cf-edge-analytics'), __('Domain', 'cf-edge-analytics'), __('Visits', 'cf-edge-analytics'), __('Share %', 'cf-edge-analytics')]);
        if (!empty($data['top_referrers']) && is_array($data['top_referrers'])) {
            $rank = 1;
            foreach ($data['top_referrers'] as $ref) {
                fputcsv($output, [
                    $rank++,
                    $ref['name'] ?? '',
                    $ref['domain'] ?? '',
                    $ref['visits'] ?? ($ref['count'] ?? 0),
                    ($ref['percentage'] ?? 0) . '%',
                ]);
            }
        }
        fputcsv($output, []); // separator

        // Top Locations / Countries
        fputcsv($output, [__('TOP COUNTRIES / LOCATIONS', 'cf-edge-analytics')]);
        fputcsv($output, [__('Rank', 'cf-edge-analytics'), __('Country Name', 'cf-edge-analytics'), __('Country Code', 'cf-edge-analytics'), __('Pageviews', 'cf-edge-analytics'), __('Share %', 'cf-edge-analytics')]);
        if (!empty($data['top_countries']) && is_array($data['top_countries'])) {
            $rank = 1;
            foreach ($data['top_countries'] as $country) {
                fputcsv($output, [
                    $rank++,
                    $country['name'] ?? '',
                    $country['iso'] ?? '',
                    $country['count'] ?? 0,
                    ($country['percentage'] ?? 0) . '%',
                ]);
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Auto-run detection when API Token is saved or updated
     */
    public function on_api_token_updated($old_value, $new_value) {
        $token = trim($new_value);
        if (!empty($token)) {
            $this->api->clear_all_caches();
            $this->api->auto_detect_credentials($token);
        }
    }

    /**
     * AJAX Auto-Detect Handler
     */
    public function ajax_autodetect_credentials() {
        check_ajax_referer('cf_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'cf-edge-analytics')], 403);
        }

        $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
        if (empty($token)) {
            $token = get_option('cf_analytics_api_token', '');
        }

        if (empty($token)) {
            wp_send_json_error(['message' => __('Please enter your Cloudflare API Token first.', 'cf-edge-analytics')], 400);
        }

        $this->api->clear_all_caches();
        $detected = $this->api->auto_detect_credentials($token);

        if (is_wp_error($detected)) {
            wp_send_json_error([
                'message' => $detected->get_error_message()
            ], 400);
        }

        wp_send_json_success([
            'message'    => __('Credentials auto-detected and saved!', 'cf-edge-analytics'),
            'account_id' => $detected['account_id'] ?? '',
            'site_tag'   => $detected['site_tag'] ?? '',
            'zone_tag'   => $detected['zone_tag'] ?? '',
            'host'       => $detected['host'] ?? '',
        ]);
    }

    /**
     * AJAX Handler: Get all Web Analytics properties in account for modal selection
     */
    public function ajax_get_account_sites() {
        check_ajax_referer('cf_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'cf-edge-analytics')], 403);
        }

        $token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
        if (empty($token)) {
            $token = get_option('cf_analytics_api_token', '');
        }

        if (empty($token)) {
            wp_send_json_error(['message' => __('Please enter your Cloudflare API Token first.', 'cf-edge-analytics')], 400);
        }

        $result = $this->api->fetch_all_account_sites($token);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX Handler: Save selected property from modal
     */
    public function ajax_save_selected_site() {
        check_ajax_referer('cf_analytics_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'cf-edge-analytics')], 403);
        }

        $site_tag   = isset($_POST['site_tag']) ? sanitize_text_field(wp_unslash($_POST['site_tag'])) : '';
        $account_id = isset($_POST['account_id']) ? sanitize_text_field(wp_unslash($_POST['account_id'])) : '';
        $zone_id    = isset($_POST['zone_id']) ? sanitize_text_field(wp_unslash($_POST['zone_id'])) : '';
        $token      = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
        $domain     = isset($_POST['domain']) ? sanitize_text_field(wp_unslash($_POST['domain'])) : '';

        if (empty($site_tag)) {
            wp_send_json_error(['message' => __('Site Tag is required.', 'cf-edge-analytics')], 400);
        }

        if (!empty($token)) {
            update_option('cf_analytics_api_token', $token);
        }
        if (!empty($account_id)) {
            update_option('cf_analytics_account_id', $account_id);
        }
        update_option('cf_analytics_site_tag', $site_tag);
        if (!empty($zone_id)) {
            update_option('cf_analytics_zone_id', $zone_id);
        }

        $this->api->clear_all_caches();

        wp_send_json_success([
            'message'    => __('Site property selected and connected successfully!', 'cf-edge-analytics'),
            'domain'     => $domain,
            'site_tag'   => $site_tag,
            'account_id' => $account_id,
            'zone_id'    => $zone_id,
        ]);
    }

    /**
     * Handle Complete Plugin Reset
     * Wipes all plugin options and transients so it returns to fresh installation state
     */
    public function post_reset_plugin() {
        check_admin_referer('cf_analytics_reset_action', 'cf_reset_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to reset this plugin.', 'cf-edge-analytics'));
        }

        // 1. Delete all plugin options
        delete_option('cf_analytics_api_token');
        delete_option('cf_analytics_account_id');
        delete_option('cf_analytics_site_tag');
        delete_option('cf_analytics_zone_id');
        delete_option('cf_analytics_cache_ttl');
        delete_option('cf_analytics_view_capability');
        delete_option('cf_analytics_admin_bar_counter');

        // 2. Clear all transient caches
        $this->api->clear_all_caches();

        // 3. Delete any orphaned transients from options table
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cf_analytics_%' OR option_name LIKE '_transient_timeout_cf_analytics_%'");

        // 4. Redirect with reset confirmation flag
        wp_safe_redirect(add_query_arg([
            'page'     => 'cf-edge-analytics-settings',
            'cf_reset' => '1',
        ], admin_url('options-general.php')));
        exit;
    }

    /**
     * Plugin Action Links (Settings & Analytics)
     */
    public function plugin_action_links($links) {
        $analytics_link = '<a href="' . esc_url(admin_url('index.php?page=cf-edge-analytics')) . '">' . __('Analytics', 'cf-edge-analytics') . '</a>';
        $settings_link  = '<a href="' . esc_url(admin_url('options-general.php?page=cf-edge-analytics-settings')) . '">' . __('Settings', 'cf-edge-analytics') . '</a>';
        array_unshift($links, $analytics_link, $settings_link);
        return $links;
    }
}

// Initialize
add_action('plugins_loaded', ['CF_Edge_Analytics', 'get_instance']);
