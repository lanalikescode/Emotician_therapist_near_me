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
        register_setting( 'pluginPage', 'emdr_settings' );

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
    }

    public function text_field_0_render() {
        $options = get_option( 'emdr_settings' );
        ?>
        <input type='text' name='emdr_settings[emdr_text_field_0]' value='<?php echo $options['emdr_text_field_0']; ?>'>
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