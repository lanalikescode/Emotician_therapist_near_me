<?php
// settings-page.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class EMDR_Therapist_Finder_Settings_Page {
    
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'settings_init' ) );
    }

    public function add_settings_page() {
        add_menu_page(
            'EMDR Therapist Finder Settings',
            'EMDR Settings',
            'manage_options',
            'emdr-therapist-finder',
            array( $this, 'create_settings_page' ),
            'dashicons-admin-generic'
        );
    }

    public function create_settings_page() {
        ?>
        <div class="wrap">
            <h1>EMDR Therapist Finder Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'emdr_options_group' );
                do_settings_sections( 'emdr-therapist-finder' );
                submit_button();
                ?>
            </form>

            <h2>Diagnostics</h2>
            <p>Run a live API test using the currently saved API keys (admin only).</p>
            <button id="emdr-run-test" class="button">Run API test</button>
            <pre id="emdr-test-output" style="white-space:pre-wrap;background:#f7f7f7;border:1px solid #ddd;padding:10px;margin-top:10px;display:none;"></pre>

            <script>
            document.getElementById('emdr-run-test').addEventListener('click', function() {
                const out = document.getElementById('emdr-test-output');
                out.style.display = 'block';
                out.textContent = 'Running...';
                fetch('<?php echo esc_url_raw( rest_url('emdr/v1/therapists/test') ); ?>', { credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(data => {
                        out.textContent = JSON.stringify(data, null, 2);
                    })
                    .catch(err => {
                        out.textContent = 'Error: ' + err;
                    });
            });
            </script>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting( 'emdr_options_group', 'emdr_options' );

        add_settings_section(
            'emdr_settings_section',
            'General Settings',
            null,
            'emdr-therapist-finder'
        );

        add_settings_field(
            'emdr_map_api_key',
            'Google Maps API Key',
            array( $this, 'map_api_key_render' ),
            'emdr-therapist-finder',
            'emdr_settings_section'
        );


        add_settings_field(
            'emdr_npi_api_key',
            'NPI API Key (if applicable)',
            array( $this, 'npi_api_key_render' ),
            'emdr-therapist-finder',
            'emdr_settings_section'
        );
    }

    public function map_api_key_render() {
        $options = get_option( 'emdr_options' );
        ?>
        <input type='text' name='emdr_options[map_api_key]' value='<?php echo esc_attr( $options['map_api_key'] ?? '' ); ?>'>
        <?php
    }

    /* Google Places uses the same API key as Google Maps; no separate field needed */

    public function npi_api_key_render() {
        $options = get_option( 'emdr_options' );
        ?>
        <input type='text' name='emdr_options[npi_api_key]' value='<?php echo esc_attr( $options['npi_api_key'] ?? '' ); ?>'>
        <?php
    }
}

new EMDR_Therapist_Finder_Settings_Page();
?>