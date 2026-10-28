<?php
/**
 * Fired when the plugin is uninstalled/deleted.
 * Completely cleans up all options and transients from the database.
 *
 * @package CF_Edge_Analytics
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 1. Delete all plugin options
delete_option('cf_analytics_api_token');
delete_option('cf_analytics_account_id');
delete_option('cf_analytics_site_tag');
delete_option('cf_analytics_zone_id');
delete_option('cf_analytics_cache_ttl');
delete_option('cf_analytics_view_capability');
delete_option('cf_analytics_admin_bar_counter');

// 2. Clear known transients
delete_transient('cf_analytics_data_cache');
delete_transient('cf_analytics_cache_timestamp');
foreach (['24h', '7d', '14d', '30d'] as $period) {
    delete_transient('cf_analytics_data_cache_' . $period);
    delete_transient('cf_analytics_cache_timestamp_' . $period);
}

// 3. Clear any remaining transients from options table
global $wpdb;
if ($wpdb) {
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cf_analytics_%' OR option_name LIKE '_transient_timeout_cf_analytics_%'");
}
