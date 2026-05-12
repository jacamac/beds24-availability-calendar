<?php
/**
 * Plugin Name:       Beds24 Availability Calendar
 * Plugin URI:        https://github.com/jacamac/beds24-availability-calendar
 * Description:       Displays a Beds24 room or property availability calendar via the [avail_calendar] shortcode and an Elementor widget.
 * Version:           1.1.2
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Jacques Leisy
 * Author URI:        https://github.com/jacamac
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       beds24-availability-calendar
 * GitHub Plugin URI: jacamac/beds24-availability-calendar
 * GitHub Branch:     main
 * Primary Branch:    main
 *
 * @package Beds24_Availability_Calendar
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'BAC_VERSION', '1.1.2' );
define( 'BAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BAC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/*
═══════════════════════════════════════════════════════════
	LANGUAGE DETECTION
	Priority:
	1. Explicit lang attribute/setting on the instance
	2. TranslatePress active language (if plugin is active)
	3. WordPress locale
	4. Fallback: 'en'

	TranslatePress stores languages in WordPress locale format
	(e.g. 'fr_FR', 'de_DE') — we normalise to BCP-47 ('fr', 'de').
	═══════════════════════════════════════════════════════════
*/

/**
 * Convert a WordPress locale string to a BCP-47 language tag.
 * Examples: 'fr_FR' → 'fr', 'zh_TW' → 'zh-TW', 'en_US' → 'en'
 *
 * @param string $locale WordPress locale string, e.g. 'fr_FR'.
 * @return string BCP-47 language tag.
 */
function bac_locale_to_bcp47( string $locale ): string {
	// Replace underscore with hyphen.
	$bcp47 = str_replace( '_', '-', $locale );

	// Split into parts: language[-script][-region].
	$parts = explode( '-', $bcp47 );

	// Keep only language if region is redundant (e.g. fr-FR → fr, en-US → en).
	// Keep region when it carries meaning (e.g. zh-TW, zh-CN, pt-BR).
	$meaningful_regions = array( 'zh-TW', 'zh-CN', 'zh-HK', 'pt-BR', 'pt-PT', 'sr-Latn' );
	if ( count( $parts ) > 1 && ! in_array( $bcp47, $meaningful_regions, true ) ) {
		return strtolower( $parts[0] );
	}

	return $bcp47;
}

/**
 * Detect the current language from TranslatePress, falling back to
 * the WordPress locale and then 'en'.
 *
 * @return string BCP-47 language tag.
 */
function bac_detect_language(): string {

	// TranslatePress — returns visitor's actively selected language (WP locale format).
	if ( function_exists( 'trp_get_current_language' ) ) {
		$trp_lang = trp_get_current_language();
		if ( ! empty( $trp_lang ) ) {
			return bac_locale_to_bcp47( $trp_lang );
		}
	}

	// Older TranslatePress API (< 2.x fallback).
	if ( function_exists( 'trp_get_language' ) ) {
		$trp_lang = trp_get_language();
		if ( ! empty( $trp_lang ) ) {
			return bac_locale_to_bcp47( $trp_lang );
		}
	}

	// WordPress locale — TranslatePress already set this to the visitor's
	// chosen language, so this is a safe fallback even without TranslatePress.
	$wp_locale = get_locale();
	if ( ! empty( $wp_locale ) ) {
		return bac_locale_to_bcp47( $wp_locale );
	}

	return 'en';
}

/**
 * Validate and sanitise a BCP-47 language tag supplied by the user.
 * Returns the sanitised tag or null if invalid.
 *
 * @param string $lang BCP-47 language tag to validate.
 * @return string|null Sanitised tag or null if invalid.
 */
function bac_sanitize_lang( string $lang ): ?string {
	$lang = trim( sanitize_text_field( $lang ) );
	return preg_match( '/^[a-z]{2,3}(-[A-Za-z]{2,4})*$/i', $lang ) ? $lang : null;
}

/**
 * Resolve the final language for an instance:
 *   - Use explicit value if provided and valid
 *   - Otherwise auto-detect from TranslatePress / WP locale
 *
 * @param string $explicit Explicit language override.
 * @return string Resolved BCP-47 language tag.
 */
function bac_resolve_lang( string $explicit = '' ): string {
	if ( ! empty( $explicit ) ) {
		$sanitised = bac_sanitize_lang( $explicit );
		if ( $sanitised ) {
			return $sanitised;
		}
	}
	return bac_detect_language();
}

/*
═══════════════════════════════════════════════════════════
	ASSET REGISTRATION & ENQUEUING
	Registration runs on 'init' so handles exist in all contexts
	(front-end, admin, Elementor editor) before any enqueue call.
	Actual enqueuing is triggered by shortcode/widget or editor hook.
	═══════════════════════════════════════════════════════════
*/

add_action( 'init', 'bac_register_assets' );

/**
 * Register the plugin's CSS and JS handles.
 */
function bac_register_assets(): void {
	wp_register_style(
		'bac-calendar',
		BAC_PLUGIN_URL . 'assets/calendar.css',
		array(),
		BAC_VERSION
	);

	wp_register_script(
		'bac-calendar',
		BAC_PLUGIN_URL . 'assets/calendar.js',
		array(),
		BAC_VERSION,
		true    // load in footer.
	);
}

/*
═══════════════════════════════════════════════════════════
	SHARED INSTANCE REGISTRY
	Both the shortcode handler and the Elementor widget push
	their config here. A single wp_footer script initialises
	all instances at once.
	═══════════════════════════════════════════════════════════
*/

$bac_instances = array();

/**
 * Register a calendar instance and ensure assets are enqueued.
 * Returns the container div HTML.
 *
 * @param array<string, mixed> $config  Validated config array for Beds24Calendar JS class.
 * @return string Container div HTML.
 */
function bac_register_instance( array $config ): string {
	global $bac_instances;

	$instance_id           = 'bac-' . wp_unique_id();
	$config['containerId'] = $instance_id;
	$bac_instances[]       = $config;

	wp_enqueue_style( 'bac-calendar' );
	wp_enqueue_script( 'bac-calendar' );

	return sprintf(
		'<div id="%s" class="bac-wrapper" aria-label="%s"></div>',
		esc_attr( $instance_id ),
		esc_attr__( 'Availability calendar', 'beds24-availability-calendar' )
	);
}

/*
═══════════════════════════════════════════════════════════
	SHARED ATTRIBUTE SANITISER
	Used by both shortcode and Elementor widget so validation
	logic is never duplicated.
	═══════════════════════════════════════════════════════════
*/

/**
 * Sanitise raw attribute values into a clean config array.
 *
 * @param array<string, mixed> $raw  Associative array with keys:
 *                                   roomid, propid, nummonths, startmonth, startyear, lang.
 * @return array<string, mixed> Clean config array for Beds24Calendar JS class.
 */
function bac_sanitize_config( array $raw ): array {
	$config = array();

	$roomid = absint( $raw['roomid'] ?? 0 );
	$propid = absint( $raw['propid'] ?? 0 );
	if ( $roomid ) {
		$config['roomid'] = $roomid;
	}
	if ( $propid ) {
		$config['propid'] = $propid;
	}

	$raw_months          = absint( $raw['nummonths'] ?? 5 );
	$nummonths           = max( 1, min( 24, $raw_months ? $raw_months : 5 ) );
	$config['numMonths'] = $nummonths;

	$tablet_months = absint( $raw['nummonthstablet'] ?? 0 );
	if ( $tablet_months >= 1 ) {
		$config['numMonthsTablet'] = min( 24, $tablet_months );
	}
	$mobile_months = absint( $raw['nummonthsmobile'] ?? 0 );
	if ( $mobile_months >= 1 ) {
		$config['numMonthsMobile'] = min( 24, $mobile_months );
	}

	$startmonth = absint( $raw['startmonth'] ?? 0 );
	if ( $startmonth >= 1 && $startmonth <= 12 ) {
		$config['startMonth'] = $startmonth;
	}

	$startyear = absint( $raw['startyear'] ?? 0 );
	if ( $startyear >= 2020 && $startyear <= 2100 ) {
		$config['startYear'] = $startyear;
	}

	$config['lang'] = bac_resolve_lang( $raw['lang'] ?? '' );

	return $config;
}

/*
═══════════════════════════════════════════════════════════
	SHORTCODE  [avail_calendar]

	Attributes:
	roomid      — Beds24 room ID  (roomid OR propid, not both)
	propid      — Beds24 property ID
	nummonths   — months to display (1-24, default 5)
	startmonth  — starting month 1-12 (default: current month)
	startyear   — starting year (default: current year)
	lang        — BCP-47 locale override, e.g. 'fr'
					Omit to use TranslatePress / WP locale automatically

	Examples:
	[avail_calendar roomid="12345" nummonths="5"]
	[avail_calendar propid="67890" nummonths="3" lang="de"]
	═══════════════════════════════════════════════════════════
*/

add_shortcode( 'avail_calendar', 'bac_shortcode' );

/**
 * Render the [avail_calendar] shortcode.
 *
 * @param array<string, string>|string $atts Shortcode attributes.
 * @return string Container div HTML.
 */
function bac_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'roomid'     => '',
			'propid'     => '',
			'nummonths'  => '5',
			'startmonth' => '',
			'startyear'  => '',
			'lang'       => '',   // empty = auto-detect.
		),
		$atts,
		'avail_calendar'
	);

	$config = bac_sanitize_config( $atts );
	return bac_register_instance( $config );
}

/*
═══════════════════════════════════════════════════════════
	DEFERRED INIT SCRIPT
	wp_footer priority 20 — after all scripts are printed.
	Emits one <script> block that boots every registered instance.
	═══════════════════════════════════════════════════════════
*/

add_action( 'wp_footer', 'bac_footer_init', 20 );

/**
 * Emit the inline script that boots all registered calendar instances.
 */
function bac_footer_init(): void {
	global $bac_instances;
	if ( empty( $bac_instances ) ) {
		return;
	}

	/*
	 * Safety rationale for the phpcs:ignore below:
	 * $bac_instances is built entirely from bac_sanitize_config() output:
	 *   - roomid/propid/nummonths/startmonth/startyear are cast via absint()
	 *   - lang is validated against a strict BCP-47 regex
	 * wp_json_encode() then serialises the array to safe JSON with all
	 * special characters escaped. The result cannot contain executable code.
	 */
	$json = wp_json_encode( $bac_instances );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see rationale above
	echo "\n<script>\n";
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output is safe
	echo "(function(){\n  var instances = {$json};\n  instances.forEach(function(cfg){\n    if(typeof Beds24Calendar !== 'undefined'){\n      new Beds24Calendar(cfg);\n    }\n  });\n})();\n";
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string literal
	echo "</script>\n";
}

/*
═══════════════════════════════════════════════════════════
	ELEMENTOR WIDGET
	Loaded only when Elementor is active, after it is ready.
	═══════════════════════════════════════════════════════════
*/

add_action( 'elementor/widgets/register', 'bac_register_elementor_widget' );

/**
 * Register the Elementor widget when the Elementor widgets manager is ready.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager instance.
 */
function bac_register_elementor_widget( $widgets_manager ): void {
	// Guard: Elementor Widget_Base must exist.
	if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
		return;
	}

	require_once BAC_PLUGIN_DIR . 'widgets/class-avail-calendar-widget.php';
	$widgets_manager->register( new \BAC\Avail_Calendar_Widget() );
}

// Enqueue assets in the Elementor editor so the live preview works.
// Handles are registered on 'init' above, so they exist in all contexts
// including the editor — no need to repeat URL or version.
add_action(
	'elementor/editor/before_enqueue_scripts',
	function () {
		wp_enqueue_style( 'bac-calendar' );
		wp_enqueue_script( 'bac-calendar' );
	}
);
