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
        $params = $request->get_query_params();
        $query = isset($params['query']) ? sanitize_text_field( $params['query'] ) : '';
        $lat = isset($params['lat']) ? floatval($params['lat']) : null;
        $lng = isset($params['lng']) ? floatval($params['lng']) : null;

        $options = get_option('emdr_options', []);
        $places_api_key = $options['places_api_key'] ?? '';
        $npi_api_key = $options['npi_api_key'] ?? '';

        $results = [];
        $locations = [];

        // Query Google Places Text Search (if API key present)
        if ( ! empty( $places_api_key ) ) {
            $text = rawurlencode( $query ?: 'EMDR therapist' );
            $location_param = '';
            if ( $lat && $lng ) {
                $location_param = "&location={$lat},{$lng}&radius=50000"; // 50km radius
            }
            $places_url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$text}{$location_param}&key=" . rawurlencode($places_api_key);
            $resp = wp_remote_get( $places_url, [ 'timeout' => 10 ] );
            if ( is_array( $resp ) && ! is_wp_error( $resp ) ) {
                $body = wp_remote_retrieve_body( $resp );
                $data = json_decode( $body, true );
                if ( ! empty( $data['results'] ) ) {
                    foreach ( $data['results'] as $r ) {
                        $item = [
                            'source' => 'google_places',
                            'name' => $r['name'] ?? '',
                            'address' => $r['formatted_address'] ?? '',
                            'place_id' => $r['place_id'] ?? '',
                        ];
                        if ( isset( $r['geometry']['location']['lat'] ) && isset( $r['geometry']['location']['lng'] ) ) {
                            $locations[] = [ 'lat' => $r['geometry']['location']['lat'], 'lng' => $r['geometry']['location']['lng'] ];
                        }
                        $results[] = $item;
                    }
                }
            }
        }

        // Query NPI Registry (NPPES) - https://npiregistry.cms.hhs.gov/api/
        // The NPI API doesn't require a key but supports a version param. We'll search by provider name/location.
        $npi_url = 'https://npiregistry.cms.hhs.gov/api/?version=2.1&limit=20';
        if ( ! empty( $query ) ) {
            $npi_url .= '&organization_name=' . rawurlencode( $query );
        }
        if ( $lat && $lng ) {
            // NPI supports address search; we'll pass city/state if query contains them, but skip complex reverse geocode here.
        }
        $npi_resp = wp_remote_get( $npi_url, [ 'timeout' => 10 ] );
        if ( is_array( $npi_resp ) && ! is_wp_error( $npi_resp ) ) {
            $body = wp_remote_retrieve_body( $npi_resp );
            $data = json_decode( $body, true );
            if ( ! empty( $data['results'] ) ) {
                foreach ( $data['results'] as $r ) {
                    $basic = $r['basic'] ?? [];
                    $addresses = $r['addresses'] ?? [];
                    $address_str = '';
                    $latlng = null;
                    if ( ! empty( $addresses ) ) {
                        $addr = $addresses[0];
                        $address_str = trim( ($addr['address_1'] ?? '') . ' ' . ($addr['address_2'] ?? '') . ', ' . ($addr['city'] ?? '') . ', ' . ($addr['state'] ?? '') );
                    }
                    $item = [
                        'source' => 'npi',
                        'name' => $basic['organization_name'] ?? ($basic['name'] ?? ''),
                        'address' => $address_str,
                        'npi' => $r['number'] ?? '',
                    ];
                    // NPI API doesn't provide lat/lng; skip unless geocoding is added
                    $results[] = $item;
                }
            }
        }

        return rest_ensure_response([ 'items' => $results, 'locations' => $locations ]);
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