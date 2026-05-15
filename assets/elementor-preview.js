/**
 * Elementor editor live-preview handler for the Availability Calendar widget.
 *
 * Settings are read from the data-bac-cfg attribute on the .bac-wrapper element,
 * which is rendered by content_template() with the full Underscore.js settings
 * object.  The template re-renders whenever a non-CSS setting changes, so the
 * attribute is always current — no dependency on elementorFrontend.config or
 * frontend_available.
 *
 * Colors update without re-initialisation: Elementor injects them as inline CSS
 * via the selectors declared on each color control.
 */
( function () {
	'use strict';

	var AvailCalendarHandler = elementorModules.frontend.handlers.Base.extend( {

		onInit: function () {
			elementorModules.frontend.handlers.Base.prototype.onInit.apply( this, arguments );
			this._initCalendar();
		},

		onElementChange: function () {
			this._initCalendar();
		},

		_initCalendar: function () {
			var $wrapper = this.$element.find( '.bac-wrapper' );
			if ( ! $wrapper.length || typeof AvailCalendar === 'undefined' ) {
				return;
			}

			// Stop any existing refresh timer before re-initialising.
			if ( this._calInstance && this._calInstance._refreshTimer ) {
				clearInterval( this._calInstance._refreshTimer );
			}

			// Wipe rendered DOM so AvailCalendar rebuilds cleanly.
			$wrapper.empty();

			// Read settings from the data attribute embedded by content_template().
			// This is more reliable than getElementSettings(), which on initial load
			// only includes controls marked frontend_available in the server config.
			var cfg;
			try {
				cfg = JSON.parse( $wrapper.attr( 'data-bac-cfg' ) || '{}' );
			} catch ( _ ) {
				cfg = {};
			}

			cfg.containerId = $wrapper.attr( 'id' );

			// Remove falsy IDs so AvailCalendar falls back to demo mode gracefully.
			if ( ! cfg.roomid ) {
				delete cfg.roomid;
			}
			if ( ! cfg.propid ) {
				delete cfg.propid;
			}

			this._calInstance = new AvailCalendar( cfg );
		},
	} );

	jQuery( window ).on( 'elementor/frontend/init', function () {
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/avail_calendar.default',
			function ( $scope ) {
				new AvailCalendarHandler( { $element: $scope } );
			}
		);
	} );
} )();
