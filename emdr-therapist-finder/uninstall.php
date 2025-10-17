<?php
// This file handles the cleanup process when the plugin is uninstalled, removing any custom database tables or options.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Define the custom database table names
global $wpdb;
$table_name_providers = $wpdb->prefix . 'emdr_providers';
$table_name_addresses = $wpdb->prefix . 'emdr_addresses';

// Remove the custom database tables
$wpdb->query( "DROP TABLE IF EXISTS $table_name_providers" );
$wpdb->query( "DROP TABLE IF EXISTS $table_name_addresses" );

// Optionally, delete any options set by the plugin
delete_option( 'emdr_plugin_options' );
?>