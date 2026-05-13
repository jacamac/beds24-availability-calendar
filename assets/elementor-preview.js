/**
 * Elementor editor live-preview handler for the Availability Calendar widget.
 *
 * Initialises AvailCalendar in demo mode (no roomid/propid) inside the
 * Elementor preview iframe and re-initialises it whenever widget settings
 * change, so adjustments to month count, language, and start month are
 * reflected instantly without a page reload.
 *
 * Colors update without re-initialisation — Elementor injects them as
 * inline CSS automatically via the `selectors` declared on each color control.
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

			var s = this.getElementSettings();

			var numMonths = ( s.nummonths && s.nummonths.size )
				? parseInt( s.nummonths.size, 10 ) : 3;
			var numMonthsTablet = ( s.nummonths_tablet && s.nummonths_tablet.size )
				? parseInt( s.nummonths_tablet.size, 10 ) : 2;
			var numMonthsMobile = ( s.nummonths_mobile && s.nummonths_mobile.size )
				? parseInt( s.nummonths_mobile.size, 10 ) : 1;

			// startmonth 0 = "current month" → pass null to AvailCalendar.
			var startMonth = ( s.startmonth && parseInt( s.startmonth, 10 ) > 0 )
				? parseInt( s.startmonth, 10 ) : null;
			var startYear = ( s.startyear && parseInt( s.startyear, 10 ) > 0 )
				? parseInt( s.startyear, 10 ) : null;

			this._calInstance = new AvailCalendar( {
				containerId:     $wrapper.attr( 'id' ),
				numMonths:       numMonths,
				numMonthsTablet: numMonthsTablet,
				numMonthsMobile: numMonthsMobile,
				startMonth:      startMonth,
				startYear:       startYear,
				lang:            s.lang || 'en',
				// No roomid/propid → demo mode with randomised availability data.
			} );
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
