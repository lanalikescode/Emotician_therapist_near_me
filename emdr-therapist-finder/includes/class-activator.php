<?php
class EMDR_Therapist_Finder_Activator {
    public static function activate() {
        self::create_database_tables();
    }

    private static function create_database_tables() {
        global $wpdb;

        $table_name_providers = $wpdb->prefix . 'emdr_therapists';
        $table_name_addresses = $wpdb->prefix . 'emdr_addresses';

        $charset_collate = $wpdb->get_charset_collate();

        $sql_providers = "CREATE TABLE $table_name_providers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            website varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_addresses = "CREATE TABLE $table_name_addresses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            provider_id mediumint(9) NOT NULL,
            address_line1 varchar(255) NOT NULL,
            address_line2 varchar(255),
            city varchar(100) NOT NULL,
            state varchar(100) NOT NULL,
            zip_code varchar(20) NOT NULL,
            country varchar(100) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (provider_id) REFERENCES $table_name_providers(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_providers);
        dbDelta($sql_addresses);
    }
}
?>