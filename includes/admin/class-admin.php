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

        add_settings_field( 
            'emdr_text_field_0', 
            __( 'Field 1', 'emdr-therapist-finder' ), 
            array( $this, 'text_field_0_render' ), 
            'pluginPage', 
            'emdr_pluginPage_section' 
        );

        // API keys for maps/places/NPI
        add_settings_field(
            'emdr_map_api_key',
            __( 'Google Maps API Key', 'emdr-therapist-finder' ),
            array( $this, 'map_api_key_render' ),
            'pluginPage',
            'emdr_pluginPage_section'
        );

        add_settings_field(
            'emdr_places_api_key',
            __( 'Google Places API Key', 'emdr-therapist-finder' ),
            array( $this, 'places_api_key_render' ),
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
        $options = get_option( 'emdr_options' );
        ?>
        <input type='text' name='emdr_options[emdr_text_field_0]' value='<?php echo esc_attr( $options['emdr_text_field_0'] ?? '' ); ?>'>
        <?php
    }

    public function map_api_key_render() {
        $options = get_option( 'emdr_options' );
        ?>
        <input type='text' name='emdr_options[map_api_key]' value='<?php echo esc_attr( $options['map_api_key'] ?? '' ); ?>'>
        <?php
    }

    public function places_api_key_render() {
        $options = get_option( 'emdr_options' );
        ?>
        <input type='text' name='emdr_options[places_api_key]' value='<?php echo esc_attr( $options['places_api_key'] ?? '' ); ?>'>
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
        <form action='options.php' method='post'>
            <h2>EMDR Therapist Finder</h2>
            <?php
            settings_fields( 'pluginPage' );
            do_settings_sections( 'pluginPage' );
            submit_button();
            ?>
        </form>
        <?php
    }
}
?>