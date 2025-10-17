<?php
// Register REST routes for the EMDR therapist finder plugin

add_action('rest_api_init', function () {
    register_rest_route('emdr/v1', '/therapists', array(
        'methods' => 'GET',
        'callback' => 'get_therapists',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('emdr/v1', '/therapists/claim', array(
        'methods' => 'POST',
        'callback' => 'claim_therapist',
        'permission_callback' => 'is_user_logged_in',
    ));

    register_rest_route('emdr/v1', '/therapists/report', array(
        'methods' => 'POST',
        'callback' => 'report_issue',
        'permission_callback' => 'is_user_logged_in',
    ));
});

// Callback function to get therapists based on search criteria
function get_therapists(WP_REST_Request $request) {
    // Implement the logic to retrieve therapists from the database
    // based on the search criteria provided in the request
}

// Callback function to claim a therapist listing
function claim_therapist(WP_REST_Request $request) {
    // Implement the logic to claim a therapist listing
    // This should validate the request and update the database accordingly
}

// Callback function to report an issue with a therapist listing
function report_issue(WP_REST_Request $request) {
    // Implement the logic to report an issue
    // This should validate the request and log the issue in the database
}
?>