<?php
/**
 * Availability Calendar for Beds24 — Elementor Widget
 *
 * Copyright (C) 2026 Jacques Leisy
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Availability_Calendar_For_Beds24
 */

namespace BAC;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor widget that renders a Beds24 availability calendar.
 */
class Avail_Calendar_Widget extends \Elementor\Widget_Base {

	// ─── Identity ────────────────────────────────────────

	/**
	 * Returns the widget's unique name identifier.
	 *
	 * @return string Widget name.
	 */
	public function get_name(): string {
		return 'avail_calendar';
	}

	/**
	 * Returns the widget's display title.
	 *
	 * @return string Widget title.
	 */
	public function get_title(): string {
		return esc_html__( 'Availability Calendar', 'availability-calendar-for-beds24' );
	}

	/**
	 * Returns the widget's icon class.
	 *
	 * @return string Icon class name.
	 */
	public function get_icon(): string {
		return 'eicon-calendar';
	}

	/**
	 * Returns the widget's Elementor editor categories.
	 *
	 * @return array<int, string> Category slugs.
	 */
	public function get_categories(): array {
		return array( 'general' );
	}

	/**
	 * Returns search keywords for this widget in the Elementor panel.
	 *
	 * @return array<int, string> Keyword strings.
	 */
	public function get_keywords(): array {
		return array( 'beds24', 'availability', 'calendar', 'booking' );
	}

	// ─── Controls (editor panel) ──────────────────────────

	/**
	 * Register Elementor editor controls for this widget.
	 */
	protected function register_controls(): void {

		/* ── Section: Beds24 Connection ── */
		$this->start_controls_section(
			'section_connection',
			array(
				'label' => esc_html__( 'Beds24 Connection', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'id_notice',
			array(
				'type'        => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'content'     => esc_html__( 'Set either a Room ID or a Property ID — not both.', 'availability-calendar-for-beds24' ),
			)
		);

		$this->add_control(
			'roomid',
			array(
				'label'       => esc_html__( 'Room ID', 'availability-calendar-for-beds24' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'input_type'  => 'number',
				'placeholder' => esc_html__( 'e.g. 12345', 'availability-calendar-for-beds24' ),
				'description' => esc_html__( 'Beds24 room ID for a single room calendar.', 'availability-calendar-for-beds24' ),
			)
		);

		$this->add_control(
			'propid',
			array(
				'label'       => esc_html__( 'Property ID', 'availability-calendar-for-beds24' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'input_type'  => 'number',
				'placeholder' => esc_html__( 'e.g. 67890', 'availability-calendar-for-beds24' ),
				'description' => esc_html__( 'Beds24 property ID for a whole-property calendar.', 'availability-calendar-for-beds24' ),
			)
		);

		$this->end_controls_section();

		/* ── Section: Display ── */
		$this->start_controls_section(
			'section_display',
			array(
				'label' => esc_html__( 'Display', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'nummonths',
			array(
				'label'          => esc_html__( 'Months to Display', 'availability-calendar-for-beds24' ),
				'type'           => \Elementor\Controls_Manager::SLIDER,
				'size_units'     => array(),
				'range'          => array(
					'px' => array(
						'min'  => 1,
						'max'  => 12,
						'step' => 1,
					),
				),
				'default'        => array(
					'unit' => 'px',
					'size' => 3,
				),
				'tablet_default' => array(
					'unit' => 'px',
					'size' => 2,
				),
				'mobile_default' => array(
					'unit' => 'px',
					'size' => 1,
				),
			)
		);

		$this->add_control(
			'startmonth',
			array(
				'label'   => esc_html__( 'Start Month', 'availability-calendar-for-beds24' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '0',
				'options' => array(
					'0'  => esc_html__( 'Current month', 'availability-calendar-for-beds24' ),
					'1'  => esc_html__( 'January', 'availability-calendar-for-beds24' ),
					'2'  => esc_html__( 'February', 'availability-calendar-for-beds24' ),
					'3'  => esc_html__( 'March', 'availability-calendar-for-beds24' ),
					'4'  => esc_html__( 'April', 'availability-calendar-for-beds24' ),
					'5'  => esc_html__( 'May', 'availability-calendar-for-beds24' ),
					'6'  => esc_html__( 'June', 'availability-calendar-for-beds24' ),
					'7'  => esc_html__( 'July', 'availability-calendar-for-beds24' ),
					'8'  => esc_html__( 'August', 'availability-calendar-for-beds24' ),
					'9'  => esc_html__( 'September', 'availability-calendar-for-beds24' ),
					'10' => esc_html__( 'October', 'availability-calendar-for-beds24' ),
					'11' => esc_html__( 'November', 'availability-calendar-for-beds24' ),
					'12' => esc_html__( 'December', 'availability-calendar-for-beds24' ),
				),
			)
		);

		$this->add_control(
			'startyear',
			array(
				'label'       => esc_html__( 'Start Year', 'availability-calendar-for-beds24' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'input_type'  => 'number',
				'placeholder' => esc_html__( 'Leave empty for current year', 'availability-calendar-for-beds24' ),
			)
		);

		$this->end_controls_section();

		/* ── Section: Language ── */
		$this->start_controls_section(
			'section_language',
			array(
				'label' => esc_html__( 'Language', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'lang_notice',
			array(
				'type'        => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'content'     => esc_html__( 'Leave blank to use the language detected from TranslatePress (or WordPress locale if TranslatePress is not active).', 'availability-calendar-for-beds24' ),
			)
		);

		$this->add_control(
			'lang',
			array(
				'label'       => esc_html__( 'Language Override', 'availability-calendar-for-beds24' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'e.g. fr, de, en, it', 'availability-calendar-for-beds24' ),
				'description' => esc_html__( 'Any BCP-47 language tag. Leave blank for automatic detection.', 'availability-calendar-for-beds24' ),
			)
		);

		$this->end_controls_section();

		/* ══════════════════════════════════════════════════════
		   STYLE TAB
		   ══════════════════════════════════════════════════════ */

		/* ── Section: Colors ── */
		$this->start_controls_section(
			'section_style_colors',
			array(
				'label' => esc_html__( 'Colors', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'heading_avail_colors',
			array(
				'label' => esc_html__( 'Availability', 'availability-calendar-for-beds24' ),
				'type'  => \Elementor\Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'color_available',
			array(
				'label'     => esc_html__( 'Available', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#34a853',
				'selectors' => array(
					'{{WRAPPER}} .bac-day.bac-available'              => 'background: {{VALUE}};',
					'{{WRAPPER}} .bac-day.bac-split-avail-am::before' => 'background: {{VALUE}};',
					'{{WRAPPER}} .bac-day.bac-split-avail-pm'         => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_unavailable',
			array(
				'label'     => esc_html__( 'Unavailable', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ea4335',
				'selectors' => array(
					'{{WRAPPER}} .bac-day.bac-unavailable'            => 'background: {{VALUE}};',
					'{{WRAPPER}} .bac-day.bac-split-avail-am'         => 'background: {{VALUE}};',
					'{{WRAPPER}} .bac-day.bac-split-avail-pm::before' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'heading_calendar_colors',
			array(
				'label'     => esc_html__( 'Calendar', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'color_background',
			array(
				'label'     => esc_html__( 'Background', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .bac-wrapper' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_text',
			array(
				'label'     => esc_html__( 'Text', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .bac-wrapper'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .bac-nav button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_past',
			array(
				'label'     => esc_html__( 'Past Days', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#cccccc',
				'selectors' => array(
					'{{WRAPPER}} .bac-day.bac-past' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_month_title',
			array(
				'label'     => esc_html__( 'Month Title', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .bac-month-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_day_headers',
			array(
				'label'     => esc_html__( 'Day Headers', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#999999',
				'selectors' => array(
					'{{WRAPPER}} .bac-dow-cell' => 'color: {{VALUE}};',
					'{{WRAPPER}} .bac-status'   => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_today',
			array(
				'label'     => esc_html__( 'Today Ring', 'availability-calendar-for-beds24' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#1a1a1a',
				'selectors' => array(
					'{{WRAPPER}} .bac-day.bac-today' => 'outline-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();

		/* ── Section: Typography ── */
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Typography', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_calendar',
				'label'    => esc_html__( 'Calendar Font', 'availability-calendar-for-beds24' ),
				'selector' => '{{WRAPPER}} .bac-wrapper',
			)
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'typography_month_title',
				'label'    => esc_html__( 'Month Title', 'availability-calendar-for-beds24' ),
				'selector' => '{{WRAPPER}} .bac-month-title',
			)
		);

		$this->end_controls_section();

		/* ── Section: Tiles ── */
		$this->start_controls_section(
			'section_style_tiles',
			array(
				'label' => esc_html__( 'Tiles', 'availability-calendar-for-beds24' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'tile_size',
			array(
				'label'      => esc_html__( 'Tile Size', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 24, 'max' => 60, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 36 ),
				'selectors'  => array(
					'{{WRAPPER}} .bac-day'     => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bac-dow-row' => 'grid-template-columns: repeat(7, {{SIZE}}{{UNIT}});',
					'{{WRAPPER}} .bac-grid'    => 'grid-template-columns: repeat(7, {{SIZE}}{{UNIT}});',
				),
			)
		);

		$this->add_control(
			'tile_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 50, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 7 ),
				'selectors'  => array(
					'{{WRAPPER}} .bac-day' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'tile_gap',
			array(
				'label'      => esc_html__( 'Tile Gap', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 16, 'step' => 1 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 4 ),
				'selectors'  => array(
					'{{WRAPPER}} .bac-dow-row' => 'gap: {{SIZE}}{{UNIT}}; margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .bac-grid'    => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'month_gap',
			array(
				'label'      => esc_html__( 'Month Gap', 'availability-calendar-for-beds24' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 0, 'max' => 80, 'step' => 4 ),
				),
				'default'    => array( 'unit' => 'px', 'size' => 28 ),
				'selectors'  => array(
					'{{WRAPPER}} .bac-strip' => 'gap: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	// ─── Render (front-end + Elementor preview) ────────────

	/**
	 * Extract an integer size from a slider setting value.
	 * Elementor stores slider values as ['unit' => 'px', 'size' => n] but
	 * some contexts return a plain scalar, so handle both formats.
	 *
	 * @param mixed $val      Raw setting value (array or scalar).
	 * @param int   $fallback Value to return when the setting is absent or empty.
	 * @return int
	 */
	private function slider_size( $val, int $fallback ): int {
		if ( is_array( $val ) && isset( $val['size'] ) && '' !== $val['size'] ) {
			return (int) $val['size'];
		}
		if ( is_numeric( $val ) ) {
			return (int) $val;
		}
		return $fallback;
	}

	/**
	 * Render the widget output on the frontend.
	 */
	protected function render(): void {
		$s = $this->get_settings_for_display();

		// DEBUG: dump raw responsive values from get_settings_for_display() to the
		// error log so we can verify whether defaults are applied on the front-end.
		// Remove once confirmed.
		error_log( '[BAC debug] nummonths raw from get_settings_for_display: ' . wp_json_encode( array(
			'nummonths'        => $s['nummonths']        ?? 'MISSING',
			'nummonths_tablet' => $s['nummonths_tablet'] ?? 'MISSING',
			'nummonths_mobile' => $s['nummonths_mobile'] ?? 'MISSING',
		) ) );

		// Build raw values from widget settings.
		$raw = array(
			'roomid'          => $s['roomid'] ?? '',
			'propid'          => $s['propid'] ?? '',
			// Fallbacks 3 / 2 / 1 must match default / tablet_default / mobile_default in register_controls().
			'nummonths'       => $this->slider_size( $s['nummonths'] ?? array(), 3 ),
			'nummonthstablet' => $this->slider_size( $s['nummonths_tablet'] ?? array(), 2 ),
			'nummonthsmobile' => $this->slider_size( $s['nummonths_mobile'] ?? array(), 1 ),
			'startmonth'      => $s['startmonth'] ?? '',
			'startyear'       => $s['startyear'] ?? '',
			'lang'            => $s['lang'] ?? '',
		);

		$config = bac_sanitize_config( $raw );
		$html   = bac_register_instance( $config );

		/*
		 * Safety rationale for the phpcs:ignore below:
		 * $html is the return value of bac_register_instance(), which uses
		 * sprintf() with esc_attr() on every interpolated value. The only
		 * dynamic content is the auto-generated instance ID (wp_unique_id)
		 * and a translated string (esc_attr__). Both are safe to output raw.
		 */
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see rationale above
		echo $html;

		// In the Elementor editor the wp_footer hook doesn't fire inside the
		// preview iframe, so we attach the init script directly to our
		// already-enqueued bac-calendar handle via wp_add_inline_script().
		// This keeps all script management through WordPress APIs and avoids
		// raw echo <script> blocks.
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			/*
			 * Safety rationale:
			 * $config is the validated output of bac_sanitize_config():
			 *   all integers via absint(), lang via regex whitelist.
			 * wp_json_encode() serialises it to safe JSON.
			 * The surrounding JS is a static string literal with no
			 * user-controlled interpolation other than the JSON blob.
			 */
			$json   = wp_json_encode( $config );
			$script = "(function(){
  var cfg = {$json};
  function tryInit() {
    if ( typeof AvailCalendar !== 'undefined' ) {
      new AvailCalendar( cfg );
    } else {
      setTimeout( tryInit, 50 );
    }
  }
  tryInit();
})();";
			wp_add_inline_script( 'bac-calendar', $script );
		}
	}

	// ─── Elementor editor content template (JS preview) ───

	/**
	 * Render the live preview placeholder in the Elementor editor.
	 */
	protected function content_template(): void {
		// The JS preview template shown while editing in Elementor.
		// We output a placeholder — the PHP render() drives the real preview
		// via the server-side rendering mechanism.
		?>
		<div class="bac-editor-placeholder" style="
			background: #f0f0f0;
			border: 2px dashed #ccc;
			border-radius: 8px;
			padding: 32px;
			text-align: center;
			color: #999;
			font-family: sans-serif;
			font-size: 14px;
		">
			<span class="eicon-calendar" style="font-size: 32px; display: block; margin-bottom: 8px;"></span>
			Availability Calendar
		</div>
		<?php
	}
}
