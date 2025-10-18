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

        // Admin-only: test NPI Registry endpoint directly with a provided query
        register_rest_route('emdr/v1', '/therapists/test-npi', [
            'methods' => 'GET',
            'callback' => [$this, 'test_npi'],
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
        $location = isset($params['location']) ? sanitize_text_field( $params['location'] ) : '';
        $city = isset($params['city']) ? sanitize_text_field( $params['city'] ) : '';
        $state = isset($params['state']) ? sanitize_text_field( $params['state'] ) : '';
        $zip = isset($params['zip']) ? sanitize_text_field( $params['zip'] ) : '';
        $radius = isset($params['radius']) ? sanitize_text_field( $params['radius'] ) : '50km';

        // Parse fallback location if only a single 'location' or 'query' string is provided
        if ( empty($city) && empty($state) && empty($zip) ) {
            $loc = !empty($location) ? $location : $query;
            if ( !empty($loc) ) {
                $loc = trim($loc);
                if ( preg_match('/^\d{5}$/', $loc) ) {
                    $zip = $loc;
                } elseif ( preg_match('/^\s*([^,]+?)\s*,\s*([A-Za-z]{2})\s*$/', $loc, $m) ) {
                    $city = trim($m[1]);
                    $state = strtoupper(trim($m[2]));
                } else {
                    $city = $loc;
                }
            }
        }

        $options = get_option('emdr_options', []);
        $places_api_key = $options['map_api_key'] ?? '';

        $debug = [ 'npi' => [], 'matching' => [] ];

        // 1) Fetch NPI results (source of truth)
        $npi_url = 'https://npiregistry.cms.hhs.gov/api/?version=2.1&address_purpose=LOCATION&country_code=US&limit=100';
        if ( !empty($zip) ) {
            $npi_url .= '&postal_code=' . rawurlencode($zip);
        } elseif ( !empty($city) ) {
            $npi_url .= '&city=' . rawurlencode($city);
            if ( !empty($state) ) {
                $npi_url .= '&state=' . rawurlencode($state);
            }
        } elseif ( !empty($query) ) {
            // Fallback if only a query is provided
            $npi_url .= '&organization_name=' . rawurlencode($query);
        }

        $debug['npi']['request_url'] = $npi_url;
        $npi_resp = wp_remote_get( $npi_url, [ 'timeout' => 12 ] );
        if ( is_wp_error( $npi_resp ) ) {
            return rest_ensure_response([ 'items' => [], 'debug' => [ 'error' => $npi_resp->get_error_message(), 'npi' => $debug['npi'] ] ]);
        }
        $npi_body = wp_remote_retrieve_body( $npi_resp );
        $npi_data = json_decode( $npi_body, true );
        if ( isset($npi_data['Errors']) ) {
            $debug['npi']['errors'] = $npi_data['Errors'];
        }
        $npi_results = $npi_data['results'] ?? [];

        // Helper to ensure place_link table exists (lightweight, no-op if already exists)
        $this->maybe_create_place_link_table();

        // 2) For each NPI row, attempt to resolve to Google Place to get photo/ratings
        $items = [];
        $match_threshold = 0.75; // only include high-confidence matches
        if ( !empty($npi_results) ) {
            $count = 0;
            foreach ( $npi_results as $r ) {
                if ( $count++ >= 100 ) { break; }

                $basic = $r['basic'] ?? [];
                $addresses = $r['addresses'] ?? [];
                $npi_number = $r['number'] ?? '';
                $name = $basic['organization_name'] ?? ($basic['name'] ?? '');
                $credentials = $basic['credential'] ?? '';
                $addr_line1 = $addresses[0]['address_1'] ?? '';
                $addr_line2 = $addresses[0]['address_2'] ?? '';
                $addr_city = $addresses[0]['city'] ?? '';
                $addr_state = $addresses[0]['state'] ?? '';
                $addr_zip = $addresses[0]['postal_code'] ?? '';
                $addr_phone = $addresses[0]['telephone_number'] ?? '';
                $website = $basic['website'] ?? '';
                $address_str = trim( $addr_line1 . ( $addr_line2 ? (' ' . $addr_line2) : '' ) . ', ' . $addr_city . ', ' . $addr_state . ' ' . $addr_zip );

                $place = null;
                $matched_via = '';
                $match_conf = 0.0;
                $place_attrib = '';
                $place_photo_url = '';
                $place_rating = null;
                $place_reviews = null;
                $place_open_now = null;
                $place_website = '';

                // Shortcut: if we already have a mapping, use it (still fetch fresh details, but skip search)
                $existing_link = $this->get_place_link( $npi_number );
                if ( $existing_link && !empty($existing_link['place_id']) && !empty($places_api_key) ) {
                    $place = $this->fetch_place_details($existing_link['place_id'], $places_api_key);
                    if ( $place ) { $matched_via = 'cache'; $match_conf = max(0.9, floatval($existing_link['match_confidence'])); }
                }

                if ( ! $place && ! empty($places_api_key) ) {
                    // Step 1: Phone match via Find Place
                    if ( ! empty($addr_phone) ) {
                        $input_phone = preg_replace('/[^\d\+]/', '', $addr_phone);
                        if ( ! empty($input_phone) ) {
                            $find_url = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input=' . rawurlencode($input_phone) . '&inputtype=phonenumber&fields=place_id&key=' . rawurlencode($places_api_key);
                            $fresp = wp_remote_get( $find_url, [ 'timeout' => 8 ] );
                            if ( is_array($fresp) && ! is_wp_error($fresp) ) {
                                $fbody = wp_remote_retrieve_body($fresp);
                                $fdata = json_decode($fbody, true);
                                if ( !empty($fdata['candidates'][0]['place_id']) ) {
                                    $place = $this->fetch_place_details($fdata['candidates'][0]['place_id'], $places_api_key);
                                    if ( $place ) { $matched_via = 'phone'; $match_conf = 1.0; }
                                }
                            }
                        }
                    }

                    // Step 2: Name + full address via Text Search
                    if ( ! $place ) {
                        $q = trim($name . ' ' . $addr_line1 . ' ' . $addr_city . ' ' . $addr_state . ' ' . $addr_zip);
                        if ( ! empty($q) ) {
                            $text_url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . rawurlencode($q) . '&key=' . rawurlencode($places_api_key);
                            $tresp = wp_remote_get( $text_url, [ 'timeout' => 8 ] );
                            if ( is_array($tresp) && ! is_wp_error($tresp) ) {
                                $tbody = wp_remote_retrieve_body($tresp);
                                $tdata = json_decode($tbody, true);
                                if ( !empty($tdata['results'][0]['place_id']) ) {
                                    $place = $this->fetch_place_details($tdata['results'][0]['place_id'], $places_api_key);
                                    if ( $place ) { $matched_via = 'name_address'; $match_conf = 0.85; }
                                }
                            }
                        }
                    }

                    // Step 3: Website domain search
                    if ( ! $place && ! empty($website) ) {
                        $host = parse_url($website, PHP_URL_HOST);
                        if ( $host ) {
                            $q = $host . ' ' . $addr_city;
                            $text_url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . rawurlencode($q) . '&key=' . rawurlencode($places_api_key);
                            $tresp = wp_remote_get( $text_url, [ 'timeout' => 8 ] );
                            if ( is_array($tresp) && ! is_wp_error($tresp) ) {
                                $tbody = wp_remote_retrieve_body($tresp);
                                $tdata = json_decode($tbody, true);
                                if ( !empty($tdata['results'][0]['place_id']) ) {
                                    $place = $this->fetch_place_details($tdata['results'][0]['place_id'], $places_api_key);
                                    if ( $place ) { $matched_via = 'website'; $match_conf = 0.7; }
                                }
                            }
                        }
                    }

                    if ( $place ) {
                        $place_photo_url = $place['photo_url'] ?? '';
                        $place_attrib = $place['photo_attribution'] ?? '';
                        $place_rating = $place['rating'] ?? null;
                        $place_reviews = $place['user_ratings_total'] ?? null;
                        $place_open_now = $place['open_now'] ?? null;
                        $place_website = $place['website'] ?? '';

                        // persist mapping light-weight
                        if ( !empty($npi_number) && !empty($place['place_id']) ) {
                            $this->upsert_place_link( $npi_number, $place['place_id'], $matched_via, $match_conf );
                        }
                    }
                }

                // Only include high-confidence matches
                if ( $match_conf >= $match_threshold ) {
                    $items[] = [
                        'source' => 'npi_merged',
                        'npi_id' => $npi_number,
                        'place_id' => $place['place_id'] ?? '',
                        'match_confidence' => $match_conf,
                        'matched_via' => $matched_via,
                        'name' => $name,
                        'credentials' => $credentials,
                        'address' => $address_str,
                        'phone' => $addr_phone,
                        'website' => !empty($website) ? $website : ($place_website ?: ''),
                        'photo' => $place_photo_url,
                        'photo_attribution' => $place_attrib,
                        'rating' => $place_rating,
                        'review_count' => $place_reviews,
                        'open_now' => $place_open_now,
                        'emdr_verified' => false,
                    ];
                }
            }
        }

        return rest_ensure_response([ 'items' => $items, 'debug' => $debug ]);
    }

    private function fetch_place_details( $place_id, $api_key ) {
        if ( empty($place_id) || empty($api_key) ) return null;
        $fields = 'place_id,website,photos,rating,user_ratings_total,opening_hours';
        $url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id=' . rawurlencode($place_id) . '&fields=' . rawurlencode($fields) . '&key=' . rawurlencode($api_key);
        $resp = wp_remote_get( $url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $resp ) ) return null;
        $body = wp_remote_retrieve_body( $resp );
        $data = json_decode( $body, true );
        if ( empty($data['result']) ) return null;
        $res = $data['result'];
        $photo_url = '';
        $attrib = '';
        if ( !empty($res['photos'][0]['photo_reference']) ) {
            $photo_ref = $res['photos'][0]['photo_reference'];
            $photo_url = 'https://maps.googleapis.com/maps/api/place/photo?maxwidth=400&photoreference=' . rawurlencode($photo_ref) . '&key=' . rawurlencode($api_key);
            if ( !empty($res['photos'][0]['html_attributions'][0]) ) {
                $attrib = wp_kses_post( $res['photos'][0]['html_attributions'][0] );
            }
        }
        return [
            'place_id' => $place_id,
            'website' => $res['website'] ?? '',
            'rating' => $res['rating'] ?? null,
            'user_ratings_total' => $res['user_ratings_total'] ?? null,
            'open_now' => isset($res['opening_hours']['open_now']) ? (bool)$res['opening_hours']['open_now'] : null,
            'photo_url' => $photo_url,
            'photo_attribution' => $attrib,
        ];
    }

    private function maybe_create_place_link_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'emdr_place_link';
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $exists === $table ) return;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            npi_id VARCHAR(20) NOT NULL,
            place_id VARCHAR(128) NOT NULL,
            matched_via VARCHAR(32) NOT NULL,
            match_confidence FLOAT NOT NULL DEFAULT 0,
            verified_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY npi_unique (npi_id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function get_place_link( $npi_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'emdr_place_link';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT place_id, matched_via, match_confidence FROM $table WHERE npi_id = %s", $npi_id ), ARRAY_A );
        return $row;
    }

    private function upsert_place_link( $npi_id, $place_id, $matched_via, $match_confidence ) {
        global $wpdb;
        $table = $wpdb->prefix . 'emdr_place_link';
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE npi_id = %s", $npi_id ) );
        if ( $existing ) {
            $wpdb->update( $table, [
                'place_id' => $place_id,
                'matched_via' => $matched_via,
                'match_confidence' => $match_confidence,
                'verified_at' => current_time('mysql'),
            ], [ 'id' => $existing ] );
        } else {
            $wpdb->insert( $table, [
                'npi_id' => $npi_id,
                'place_id' => $place_id,
                'matched_via' => $matched_via,
                'match_confidence' => $match_confidence,
                'verified_at' => current_time('mysql'),
            ] );
        }
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

    // Admin-only: Test NPI Registry with a query; returns only NPI response and any error details
    public function test_npi($request) {
        if ( ! current_user_can('manage_options') ) {
            return new WP_Error('forbidden', 'Not allowed', ['status' => 403]);
        }

        $query = sanitize_text_field( $request->get_param('query') ?? '' );
        $location = sanitize_text_field( $request->get_param('location') ?? '' );
        $npi_url = 'https://npiregistry.cms.hhs.gov/api/?version=2.1&limit=10&address_purpose=LOCATION&country_code=US';

        // Prefer location-based testing if a location string is provided
        if ( ! empty( $location ) ) {
            $loc = trim($location);
            // If it's a 5-digit ZIP, use postal_code
            if ( preg_match('/^\d{5}$/', $loc) ) {
                $npi_url .= '&postal_code=' . rawurlencode($loc);
            } else {
                // Try "City, ST" format first
                if ( preg_match('/^\s*([^,]+?)\s*,\s*([A-Za-z]{2})\s*$/', $loc, $m) ) {
                    $city = trim($m[1]);
                    $state = strtoupper(trim($m[2]));
                    $npi_url .= '&city=' . rawurlencode($city) . '&state=' . rawurlencode($state);
                } else {
                    // Fallback: set city only (NPI may still return results)
                    $npi_url .= '&city=' . rawurlencode($loc);
                }
            }
        } elseif ( ! empty( $query ) ) {
            // Fallback: Use organization_name if no location provided
            $npi_url .= '&organization_name=' . rawurlencode( $query );
        }

        $result = [ 'request_url' => $npi_url, 'response' => null, 'error' => null ];
        $resp = wp_remote_get( $npi_url, [ 'timeout' => 10 ] );
        if ( is_wp_error( $resp ) ) {
            $result['error'] = $resp->get_error_message();
            return rest_ensure_response( $result );
        }
        $body = wp_remote_retrieve_body( $resp );
        $data = json_decode( $body, true );
        $result['response'] = $data;
        if ( isset($data['Errors']) ) {
            $result['error'] = $data['Errors'];
        }
        return rest_ensure_response( $result );
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