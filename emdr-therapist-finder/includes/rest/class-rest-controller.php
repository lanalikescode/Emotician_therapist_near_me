<?php
class EMDR_Rest_Controller {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('emdr/v1', '/therapists', [
            'methods' => 'GET',
            'callback' => [$this, 'get_therapists'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('emdr/v1', '/therapists/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_therapist'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('emdr/v1', '/therapists', [
            'methods' => 'POST',
            'callback' => [$this, 'create_therapist'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('emdr/v1', '/therapists/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_therapist'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    public function get_therapists($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'emdr_therapists';
        $therapists = $wpdb->get_results("SELECT * FROM $table_name");
        return rest_ensure_response($therapists);
    }

    public function get_therapist($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'emdr_therapists';
        $therapist = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $request['id']));
        if (empty($therapist)) {
            return new WP_Error('no_therapist', 'Therapist not found', ['status' => 404]);
        }
        return rest_ensure_response($therapist);
    }

    public function create_therapist($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'emdr_therapists';
        $data = [
            'name' => sanitize_text_field($request['name']),
            'location' => sanitize_text_field($request['location']),
            'specialty' => sanitize_text_field($request['specialty']),
        ];
        $wpdb->insert($table_name, $data);
        return rest_ensure_response($data);
    }

    public function delete_therapist($request) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'emdr_therapists';
        $deleted = $wpdb->delete($table_name, ['id' => $request['id']]);
        if ($deleted) {
            return rest_ensure_response(['success' => true]);
        }
        return new WP_Error('no_therapist', 'Therapist not found', ['status' => 404]);
    }
}
?>