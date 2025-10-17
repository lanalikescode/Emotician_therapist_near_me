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

        register_rest_route('emdr/v1', '/therapists/test', [
            'methods' => 'GET',
            'callback' => [$this, 'test_apis'],
            'permission_callback' => function() { return current_user_can('manage_options'); },
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
    $places_api_key = $options['map_api_key'] ?? ''; // use single Google API key for Maps and Places
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
                    // For each place result, fetch Place Details to get phone, photos, website
                    $count = 0;
                    foreach ( $data['results'] as $r ) {
                        if ( $count++ >= 20 ) break; // limit details requests
                        $place_id = $r['place_id'] ?? '';
                        $item = [
                            'source' => 'google_places',
                            'name' => $r['name'] ?? '',
                            'address' => $r['formatted_address'] ?? '',
                            'place_id' => $place_id,
                            'phone' => '',
                            'website' => '',
                            'photo' => '',
                        ];

                        // geometry
                        if ( isset( $r['geometry']['location']['lat'] ) && isset( $r['geometry']['location']['lng'] ) ) {
                            $locations[] = [ 'lat' => $r['geometry']['location']['lat'], 'lng' => $r['geometry']['location']['lng'] ];
                        }

                        if ( ! empty( $place_id ) ) {
                            $details_url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . rawurlencode($place_id) . '&fields=name,formatted_address,formatted_phone_number,website,photo,geometry&key=' . rawurlencode($places_api_key);
                            $dresp = wp_remote_get( $details_url, [ 'timeout' => 10 ] );
                            if ( is_array( $dresp ) && ! is_wp_error( $dresp ) ) {
                                $dbody = wp_remote_retrieve_body( $dresp );
                                $ddata = json_decode( $dbody, true );
                                if ( ! empty( $ddata['result'] ) ) {
                                    $res = $ddata['result'];
                                    $item['phone'] = $res['formatted_phone_number'] ?? '';
                                    $item['website'] = $res['website'] ?? '';
                                    // photo handling: get first photo reference and build photo URL
                                    if ( ! empty( $res['photos'] ) && is_array( $res['photos'] ) ) {
                                        $photo_ref = $res['photos'][0]['photo_reference'] ?? '';
                                        if ( $photo_ref ) {
                                            $photo_url = 'https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=' . rawurlencode($photo_ref) . '&key=' . rawurlencode($places_api_key);
                                            $item['photo'] = $photo_url;
                                        }
                                    }
                                    // override geometry location if present
                                    if ( isset( $res['geometry']['location']['lat'] ) && isset( $res['geometry']['location']['lng'] ) ) {
                                        $locations[] = [ 'lat' => $res['geometry']['location']['lat'], 'lng' => $res['geometry']['location']['lng'] ];
                                    }
                                }
                            }
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
        $npi_resp = wp_remote_get( $npi_url, [ 'timeout' => 10 ] );
        if ( is_array( $npi_resp ) && ! is_wp_error( $npi_resp ) ) {
            $body = wp_remote_retrieve_body( $npi_resp );
            $data = json_decode( $body, true );
            if ( ! empty( $data['results'] ) ) {
                $count = 0;
                foreach ( $data['results'] as $r ) {
                    if ( $count++ >= 20 ) break;
                    $basic = $r['basic'] ?? [];
                    $addresses = $r['addresses'] ?? [];
                    $address_str = '';
                    $phone = '';
                    if ( ! empty( $addresses ) ) {
                        $addr = $addresses[0];
                        $address_str = trim( ($addr['address_1'] ?? '') . ' ' . ($addr['address_2'] ?? '') . ', ' . ($addr['city'] ?? '') . ', ' . ($addr['state'] ?? '') );
                        $phone = $addr['telephone_number'] ?? '';
                    }
                    $item = [
                        'source' => 'npi',
                        'name' => $basic['organization_name'] ?? ($basic['name'] ?? ''),
                        'address' => $address_str,
                        'phone' => $phone,
                        'npi' => $r['number'] ?? '',
                        'photo' => '',
                        'website' => '',
                    ];

                    // Try to geocode the NPI address to get lat/lng (if Google API key available)
                    if ( ! empty( $places_api_key ) && ! empty( $address_str ) ) {
                        $geocode_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . rawurlencode( $address_str ) . '&key=' . rawurlencode( $places_api_key );
                        $gresp = wp_remote_get( $geocode_url, [ 'timeout' => 10 ] );
                        if ( is_array( $gresp ) && ! is_wp_error( $gresp ) ) {
                            $gbody = wp_remote_retrieve_body( $gresp );
                            $gdata = json_decode( $gbody, true );
                            if ( ! empty( $gdata['results'][0]['geometry']['location'] ) ) {
                                $loc = $gdata['results'][0]['geometry']['location'];
                                $locations[] = [ 'lat' => $loc['lat'], 'lng' => $loc['lng'] ];
                            }
                        }
                    }

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

    // Admin-only: test external APIs using stored keys and a sample query
    public function test_apis($request) {
        if ( ! current_user_can('manage_options') ) {
            return new WP_Error('forbidden', 'Not allowed', ['status' => 403]);
        }

        $sample_query = $request->get_param('query') ?? 'EMDR therapist los angeles';
        $lat = 34.052235;
        $lng = -118.243683;
        $options = get_option('emdr_options', []);
        $places_api_key = $options['map_api_key'] ?? '';
        $npi_api_key = $options['npi_api_key'] ?? '';

        $diagnostics = [
            'google_places' => [],
            'npi_registry' => [],
            'geocode' => [],
            'summary' => [],
        ];

        // Google Places test
        if ( ! empty( $places_api_key ) ) {
            $text = rawurlencode( $sample_query );
            $places_url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query={$text}&location={$lat},{$lng}&radius=50000&key=" . rawurlencode($places_api_key);
            $resp = wp_remote_get( $places_url, [ 'timeout' => 10 ] );
            if ( is_wp_error( $resp ) ) {
                $diagnostics['google_places']['error'] = $resp->get_error_message();
            } else {
                $body = wp_remote_retrieve_body( $resp );
                $data = json_decode( $body, true );
                $diagnostics['google_places']['response'] = $data;
                if ( isset($data['error_message']) ) {
                    $diagnostics['google_places']['error_message'] = $data['error_message'];
                }
                if ( isset($data['status']) && $data['status'] !== 'OK' ) {
                    $diagnostics['google_places']['status'] = $data['status'];
                }
            }
        } else {
            $diagnostics['google_places']['error'] = 'No Google API key set.';
        }

        // NPI Registry test
        $npi_url = 'https://npiregistry.cms.hhs.gov/api/?version=2.1&limit=2&organization_name=' . rawurlencode( $sample_query );
        $npi_resp = wp_remote_get( $npi_url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $npi_resp ) ) {
            $diagnostics['npi_registry']['error'] = $npi_resp->get_error_message();
        } else {
            $body = wp_remote_retrieve_body( $npi_resp );
            $data = json_decode( $body, true );
            $diagnostics['npi_registry']['response'] = $data;
            if ( isset($data['Errors']) ) {
                $diagnostics['npi_registry']['error_message'] = $data['Errors'];
            }
        }

        // Geocode test (only if Google API key set)
        if ( ! empty( $places_api_key ) ) {
            $address = 'Los Angeles, CA';
            $geocode_url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . rawurlencode( $address ) . '&key=' . rawurlencode($places_api_key);
            $gresp = wp_remote_get( $geocode_url, [ 'timeout' => 10 ] );
            if ( is_wp_error( $gresp ) ) {
                $diagnostics['geocode']['error'] = $gresp->get_error_message();
            } else {
                $body = wp_remote_retrieve_body( $gresp );
                $data = json_decode( $body, true );
                $diagnostics['geocode']['response'] = $data;
                if ( isset($data['error_message']) ) {
                    $diagnostics['geocode']['error_message'] = $data['error_message'];
                }
                if ( isset($data['status']) && $data['status'] !== 'OK' ) {
                    $diagnostics['geocode']['status'] = $data['status'];
                }
            }
        }

        // Summary
        $diagnostics['summary'] = [
            'google_api_key_present' => !empty($places_api_key),
            'npi_api_key_present' => !empty($npi_api_key),
            'sample_query' => $sample_query,
        ];

        return rest_ensure_response($diagnostics);
    }
}
?>