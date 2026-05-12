<?php
/**
 * Beds24 Availability Calendar — Elementor Widget
 *
 * Copyright (C) 2026 Jacques Leisy
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Beds24_Availability_Calendar
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
		return esc_html__( 'Availability Calendar', 'beds24-availability-calendar' );
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
				'label' => esc_html__( 'Beds24 Connection', 'beds24-availability-calendar' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'id_notice',
			array(
				'type'        => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'content'     => esc_html__( 'Set either a Room ID or a Property ID — not both.', 'beds24-availability-calendar' ),
			)
		);

		$this->add_control(
			'roomid',
			array(
				'label'       => esc_html__( 'Room ID', 'beds24-availability-calendar' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'input_type'  => 'number',
				'placeholder' => esc_html__( 'e.g. 12345', 'beds24-availability-calendar' ),
				'description' => esc_html__( 'Beds24 room ID for a single room calendar.', 'beds24-availability-calendar' ),
			)
		);

		$this->add_control(
			'propid',
			array(
				'label'       => esc_html__( 'Property ID', 'beds24-availability-calendar' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'input_type'  => 'number',
				'placeholder' => esc_html__( 'e.g. 67890', 'beds24-availability-calendar' ),
				'description' => esc_html__( 'Beds24 property ID for a whole-property calendar.', 'beds24-availability-calendar' ),
			)
		);

		$this->end_controls_section();

		/* ── Section: Display ── */
		$this->start_controls_section(
			'section_display',
			array(
				'label' => esc_html__( 'Display', 'beds24-availability-calendar' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'nummonths',
			array(
				'label'      => esc_html__( 'Months to Display', 'beds24-availability-calendar' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array(),
				'range'      => array(
					'px' => array(
						'min'  => 1,
						'max'  => 24,
						'step' => 1,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 5,
				),
			)
		);

		$this->add_control(
			'startmonth',
			array(
				'label'   => esc_html__( 'Start Month', 'beds24-availability-calendar' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '0',
				'options' => array(
					'0'  => esc_html__( 'Current month', 'beds24-availability-calendar' ),
					'1'  => esc_html__( 'January', 'beds24-availability-calendar' ),
					'2'  => esc_html__( 'February', 'beds24-availability-calendar' ),
					'3'  => esc_html__( 'March', 'beds24-availability-calendar' ),
					'4'  => esc_html__( 'April', 'beds24-availability-calendar' ),
					'5'  => esc_html__( 'May', 'beds24-availability-calendar' ),
					'6'  => esc_html__( 'June', 'beds24-availability-calendar' ),
					'7'  => esc_html__( 'July', 'beds24-availability-calendar' ),
					'8'  => esc_html__( 'August', 'beds24-availability-calendar' ),
					'9'  => esc_html__( 'September', 'beds24-availability-calendar' ),
					'10' => esc_html__( 'October', 'beds24-availability-calendar' ),
					'11' => esc_html__( 'November', 'beds24-availability-calendar' ),
					'12' => esc_html__( 'December', 'beds24-availability-calendar' ),
				),
			)
		);

		$this->add_control(
			'startyear',
			array(
				'label'       => esc_html__( 'Start Year', 'beds24-availability-calendar' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'input_type'  => 'number',
				'placeholder' => esc_html__( 'Leave empty for current year', 'beds24-availability-calendar' ),
			)
		);

		$this->end_controls_section();

		/* ── Section: Language ── */
		$this->start_controls_section(
			'section_language',
			array(
				'label' => esc_html__( 'Language', 'beds24-availability-calendar' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'lang_notice',
			array(
				'type'        => \Elementor\Controls_Manager::NOTICE,
				'notice_type' => 'info',
				'content'     => esc_html__( 'Leave blank to use the language detected from TranslatePress (or WordPress locale if TranslatePress is not active).', 'beds24-availability-calendar' ),
			)
		);

		$this->add_control(
			'lang',
			array(
				'label'       => esc_html__( 'Language Override', 'beds24-availability-calendar' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'e.g. fr, de, en, it', 'beds24-availability-calendar' ),
				'description' => esc_html__( 'Any BCP-47 language tag. Leave blank for automatic detection.', 'beds24-availability-calendar' ),
			)
		);

		$this->end_controls_section();
	}

	// ─── Render (front-end + Elementor preview) ────────────

	/**
	 * Render the widget output on the frontend.
	 */
	protected function render(): void {
		$s = $this->get_settings_for_display();

		// Build raw values from widget settings.
		$raw = array(
			'roomid'     => $s['roomid'] ?? '',
			'propid'     => $s['propid'] ?? '',
			'nummonths'  => (int) ( $s['nummonths']['size'] ?? 5 ),
			'startmonth' => $s['startmonth'] ?? '',
			'startyear'  => $s['startyear'] ?? '',
			'lang'       => $s['lang'] ?? '',
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
    if ( typeof Beds24Calendar !== 'undefined' ) {
      new Beds24Calendar( cfg );
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
