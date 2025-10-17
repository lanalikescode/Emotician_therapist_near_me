<?php
class EMDR_Therapist_Finder_DB {
    private $table_name_providers;
    private $table_name_addresses;

    public function __construct() {
        global $wpdb;
        $this->table_name_providers = $wpdb->prefix . 'emdr_therapists';
        $this->table_name_addresses = $wpdb->prefix . 'emdr_addresses';
    }

    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_providers = "CREATE TABLE IF NOT EXISTS $this->table_name_providers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(15) DEFAULT '' NOT NULL,
            website varchar(100) DEFAULT '' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_addresses = "CREATE TABLE IF NOT EXISTS $this->table_name_addresses (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            provider_id mediumint(9) NOT NULL,
            address text NOT NULL,
            city varchar(100) NOT NULL,
            state varchar(100) NOT NULL,
            zip varchar(10) NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (provider_id) REFERENCES $this->table_name_providers(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_providers);
        dbDelta($sql_addresses);
    }

    public function insert_provider($data) {
        global $wpdb;
        $wpdb->insert($this->table_name_providers, $data);
        return $wpdb->insert_id;
    }

    public function insert_address($data) {
        global $wpdb;
        $wpdb->insert($this->table_name_addresses, $data);
        return $wpdb->insert_id;
    }

    public function get_providers($args = []) {
        global $wpdb;
        $query = "SELECT * FROM $this->table_name_providers";
        return $wpdb->get_results($query);
    }

    public function get_addresses_by_provider($provider_id) {
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM $this->table_name_addresses WHERE provider_id = %d", $provider_id);
        return $wpdb->get_results($query);
    }

    public function delete_provider($id) {
        global $wpdb;
        $wpdb->delete($this->table_name_providers, ['id' => $id]);
    }

    public function delete_address($id) {
        global $wpdb;
        $wpdb->delete($this->table_name_addresses, ['id' => $id]);
    }
}
?>