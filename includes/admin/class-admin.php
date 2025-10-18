<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class EMDR_Admin {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
    }

    public function add_admin_menu() {
        add_menu_page( 
            'EMDR Therapist Finder', 
            'EMDR Finder', 
            'manage_options', 
            'emdr_therapist_finder', 
            array( $this, 'settings_page' ), 
            'dashicons-location-alt', 
            100 
        );
    }

    public function settings_init() {
        // Use emdr_options as the option name so frontend and other settings code read the same option
        register_setting( 'pluginPage', 'emdr_options' );

        add_settings_section(
            'emdr_pluginPage_section', 
            __( 'Settings', 'emdr-therapist-finder' ), 
            array( $this, 'settings_section_callback' ), 
            'pluginPage'
        );

        // API keys for maps/places (same key) and NPI
        add_settings_field(
            'emdr_map_api_key',
            __( 'Google Maps & Places API Key', 'emdr-therapist-finder' ),
            array( $this, 'map_api_key_render' ),
            'pluginPage',
            'emdr_pluginPage_section'
        );

        add_settings_field(
            'emdr_npi_api_key',
            __( 'NPI API Key (optional)', 'emdr-therapist-finder' ),
            array( $this, 'npi_api_key_render' ),
            'pluginPage',
            'emdr_pluginPage_section'
        );
    }

    public function text_field_0_render() {
        // removed unused field
        return;
    }

    public function map_api_key_render() {
        $options = get_option( 'emdr_options' );
        ?>
        <input type='text' name='emdr_options[map_api_key]' value='<?php echo esc_attr( $options['map_api_key'] ?? '' ); ?>'>
        <?php
    }

    public function npi_api_key_render() {
        $options = get_option( 'emdr_options' );
        ?>
        <input type='text' name='emdr_options[npi_api_key]' value='<?php echo esc_attr( $options['npi_api_key'] ?? '' ); ?>'>
        <?php
    }

    public function settings_section_callback() {
        echo __( 'Configure your EMDR Therapist Finder settings below:', 'emdr-therapist-finder' );
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <form action='options.php' method='post'>
                <h2>EMDR Therapist Finder</h2>
                <?php
                settings_fields( 'pluginPage' );
                do_settings_sections( 'pluginPage' );
                submit_button();
                ?>
            </form>

            <h2>API Diagnostics</h2>
            <p>Run a live API test using the currently saved API keys to verify external services are working.</p>
            <button id="emdr-run-test" class="button">Test API Connections</button>
            <hr>
            <h3>NPI Registry quick test</h3>
            <p>Enter a name or organization to test the NPI Registry directly:</p>
            <input type="text" id="emdr-npi-test-query" placeholder="e.g., EMDR Los Angeles" style="width: 320px;">
            <button id="emdr-run-npi-test" class="button">Test NPI</button>
            <pre id="emdr-test-output" style="white-space:pre-wrap;background:#f7f7f7;border:1px solid #ddd;padding:10px;margin-top:10px;display:none;max-height:400px;overflow:auto;"></pre>

            <script>
            jQuery(document).ready(function($) {
                $('#emdr-run-test').on('click', function() {
                    const $btn = $(this);
                    const $out = $('#emdr-test-output');
                    $btn.prop('disabled', true);
                    $out.show().text('Running API tests...');

                    $.ajax({
                        url: '<?php echo esc_url_raw( rest_url('emdr/v1/therapists/test') ); ?>',
                        method: 'GET',
                        beforeSend: function ( xhr ) {
                            xhr.setRequestHeader( 'X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' ); ?>' );
                        }
                    })
                    .done(function(data) {
                        let errorSummary = '';
                        if (data.google_places && (data.google_places.error || data.google_places.error_message || data.google_places.status)) {
                            errorSummary += 'Google Places: ' + (data.google_places.error || data.google_places.error_message || data.google_places.status) + '\n';
                        }
                        if (data.npi_registry && (data.npi_registry.error || data.npi_registry.error_message)) {
                            errorSummary += 'NPI Registry: ' + (data.npi_registry.error || data.npi_registry.error_message) + '\n';
                        }
                        if (data.geocode && (data.geocode.error || data.geocode.error_message || data.geocode.status)) {
                            errorSummary += 'Geocode: ' + (data.geocode.error || data.geocode.error_message || data.geocode.status) + '\n';
                        }
                        if (errorSummary) {
                            $out.html('<strong>Errors Detected:</strong>\n' + errorSummary.replace(/\n/g,'<br>') + '<hr>' + '<code>' + JSON.stringify(data, null, 2) + '</code>');
                        } else {
                            $out.html('<code>' + JSON.stringify(data, null, 2) + '</code>');
                        }
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        let msg = 'Error: ' + textStatus + '\n' + errorThrown;
                        if (jqXHR && jqXHR.responseText) {
                            msg += '\n' + jqXHR.responseText;
                        }
                        $out.text(msg);
                    })
                    .always(function() {
                        $btn.prop('disabled', false);
                    });
                });

                // NPI only test
                $('#emdr-run-npi-test').on('click', function() {
                    const $btn = $(this);
                    const $out = $('#emdr-test-output');
                    const q = $('#emdr-npi-test-query').val() || '';
                    $btn.prop('disabled', true);
                    $out.show().text('Running NPI test...');

                    $.ajax({
                        url: '<?php echo esc_url_raw( rest_url('emdr/v1/therapists/test-npi') ); ?>' + (q ? ('?query=' + encodeURIComponent(q)) : ''),
                        method: 'GET',
                        beforeSend: function ( xhr ) {
                            xhr.setRequestHeader( 'X-WP-Nonce', '<?php echo wp_create_nonce( 'wp_rest' ); ?>' );
                        }
                    })
                    .done(function(data) {
                        let summary = '';
                        if (data.error) {
                            summary += 'Error: ' + (typeof data.error === 'string' ? data.error : JSON.stringify(data.error)) + '\n';
                        }
                        summary += 'Request URL: ' + (data.request_url || '') + '\n\n';
                        $out.html('<code>' + (summary ? summary.replace(/\n/g,'<br>') : '') + JSON.stringify(data.response || data, null, 2) + '</code>');
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        let msg = 'Error: ' + textStatus + '\n' + errorThrown;
                        if (jqXHR && jqXHR.responseText) {
                            msg += '\n' + jqXHR.responseText;
                        }
                        $out.text(msg);
                    })
                    .always(function() {
                        $btn.prop('disabled', false);
                    });
                });
            });
            </script>
        </div>
        <?php
    }
}
?>