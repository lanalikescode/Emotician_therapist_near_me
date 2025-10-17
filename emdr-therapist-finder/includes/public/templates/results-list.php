<?php
// This file displays the list of EMDR therapists based on search results.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Fetch therapists from the database based on search criteria.
$therapists = get_therapists(); // Assume this function retrieves the therapists.

if ( ! empty( $therapists ) ) : ?>
    <div class="emdr-therapist-results">
        <h2><?php esc_html_e( 'Therapists Found', 'emdr-therapist-finder' ); ?></h2>
        <ul>
            <?php foreach ( $therapists as $therapist ) : ?>
                <li>
                    <h3><?php echo esc_html( $therapist->name ); ?></h3>
                    <p><?php echo esc_html( $therapist->address ); ?></p>
                    <p><?php echo esc_html( $therapist->phone ); ?></p>
                    <p><?php echo esc_html( $therapist->email ); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php else : ?>
    <div class="emdr-therapist-results">
        <h2><?php esc_html_e( 'No Therapists Found', 'emdr-therapist-finder' ); ?></h2>
    </div>
<?php endif; ?>