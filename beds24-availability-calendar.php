<?php
/**
 * Plugin Name:       Beds24 Availability Calendar
 * Plugin URI:        https://github.com/jacamac/beds24-availability-calendar
 * Description:       Displays a Beds24 room or property availability calendar via the [avail_calendar] shortcode.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Jacques Leisy
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       beds24-availability-calendar
 */

defined( 'ABSPATH' ) || exit;

define( 'BAC_VERSION',    '1.0.0' );
define( 'BAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/* ═══════════════════════════════════════════════════════════
   ASSET ENQUEUING
   CSS and JS are registered once per page, regardless of how
   many [avail_calendar] shortcodes appear.
   ═══════════════════════════════════════════════════════════ */

add_action( 'wp_enqueue_scripts', 'bac_register_assets' );

function bac_register_assets() {
    wp_register_style(
        'bac-calendar',
        BAC_PLUGIN_URL . 'assets/calendar.css',
        [],
        BAC_VERSION
    );

    wp_register_script(
        'bac-calendar',
        BAC_PLUGIN_URL . 'assets/calendar.js',
        [],           // no dependencies (vanilla JS)
        BAC_VERSION,
        true          // load in footer
    );
}

/* ═══════════════════════════════════════════════════════════
   SHORTCODE  [avail_calendar]
   
   Attributes:
     roomid      — Beds24 room ID  (roomid OR propid, not both)
     propid      — Beds24 property ID
     nummonths   — number of months to display (default 5, max 24)
     startmonth  — starting month 1-12 (default: current month)
     startyear   — starting year (default: current year)
     lang        — BCP-47 locale, e.g. 'fr', 'en', 'de' (default 'en')

   Example:
     [avail_calendar roomid="12345" nummonths="5" lang="fr"]
     [avail_calendar propid="67890" nummonths="3" lang="en"]
   ═══════════════════════════════════════════════════════════ */

add_shortcode( 'avail_calendar', 'bac_shortcode' );

/* Collect all shortcode instances on the page so we can emit
   a single deferred init script at wp_footer. */
$bac_instances = [];

function bac_shortcode( $atts ) {
    global $bac_instances;

    // Sanitise and validate attributes
    $atts = shortcode_atts( [
        'roomid'     => '',
        'propid'     => '',
        'nummonths'  => '5',
        'startmonth' => '',
        'startyear'  => '',
        'lang'       => 'en',
    ], $atts, 'avail_calendar' );

    // roomid / propid — positive integers only
    $roomid = absint( $atts['roomid'] );
    $propid = absint( $atts['propid'] );

    // nummonths — clamped 1..24
    $nummonths = max( 1, min( 24, absint( $atts['nummonths'] ) ?: 5 ) );

    // startmonth — clamped 1..12, 0 = use current
    $startmonth = absint( $atts['startmonth'] );
    $startmonth = ( $startmonth >= 1 && $startmonth <= 12 ) ? $startmonth : 0;

    // startyear — clamped 2020..2100, 0 = use current
    $startyear = absint( $atts['startyear'] );
    $startyear = ( $startyear >= 2020 && $startyear <= 2100 ) ? $startyear : 0;

    // lang — BCP-47 format, letters only
    $lang = preg_match( '/^[a-z]{2,3}(-[A-Za-z]{2,4})*$/i', $atts['lang'] )
        ? sanitize_text_field( $atts['lang'] )
        : 'en';

    // Unique ID for this instance — scopes JS to this container
    $instance_id = 'bac-' . wp_unique_id();

    // Build config array for this instance (only include non-zero values)
    $config = [ 'containerId' => $instance_id ];
    if ( $roomid )     $config['roomid']     = $roomid;
    if ( $propid )     $config['propid']     = $propid;
    if ( $nummonths )  $config['numMonths']  = $nummonths;
    if ( $startmonth ) $config['startMonth'] = $startmonth;
    if ( $startyear )  $config['startYear']  = $startyear;
    $config['lang'] = $lang;

    // Register this instance for the footer script
    $bac_instances[] = $config;

    // Enqueue assets (safe to call multiple times — WP deduplicates)
    wp_enqueue_style( 'bac-calendar' );
    wp_enqueue_script( 'bac-calendar' );

    // Return the container div — JS will render into it
    return sprintf(
        '<div id="%s" class="bac-wrapper" aria-label="%s"></div>',
        esc_attr( $instance_id ),
        esc_attr__( 'Availability calendar', 'beds24-availability-calendar' )
    );
}

/* ═══════════════════════════════════════════════════════════
   DEFERRED INIT SCRIPT
   Runs at wp_footer (priority 20, after scripts are printed).
   Emits a single <script> that instantiates all calendar
   instances collected during shortcode processing.
   ═══════════════════════════════════════════════════════════ */

add_action( 'wp_footer', 'bac_footer_init', 20 );

function bac_footer_init() {
    global $bac_instances;

    if ( empty( $bac_instances ) ) return;

    // wp_json_encode handles escaping — safe to echo directly
    $json = wp_json_encode( $bac_instances );

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo "\n<script>\n";
    echo "(function(){\n";
    echo "  var instances = {$json};\n";
    echo "  instances.forEach(function(cfg){\n";
    echo "    if(typeof Beds24Calendar !== 'undefined'){\n";
    echo "      new Beds24Calendar(cfg);\n";
    echo "    }\n";
    echo "  });\n";
    echo "})();\n";
    echo "</script>\n";
}
