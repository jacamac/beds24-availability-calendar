/**
 * Elementor editor live-preview handler for the Availability Calendar widget.
 *
 * Initialises AvailCalendar inside the Elementor preview iframe and
 * re-initialises it whenever widget settings change, so adjustments to
 * room/property ID, month count, language, and start month are reflected
 * instantly without a page reload.
 *
 * Colors update without re-initialisation — Elementor injects them as
 * inline CSS automatically via the `selectors` declared on each color control.
 */
( function () {
	'use strict';

	/**
	 * Read a slider setting that may be stored as { size, unit } or as a plain number.
	 *
	 * Elementor stores slider values as objects in the model, but in some
	 * editor contexts getElementSettings() resolves responsive controls to a
	 * plain numeric value instead.  This helper handles both forms.
	 *
	 * @param {*}      val      Raw value from getElementSettings().
	 * @param {number} fallback Returned when val is absent or invalid.
	 * @return {number}
	 */
	function sliderVal( val, fallback ) {
		if ( val === undefined || val === null || val === '' ) {
			return fallback;
		}
		var n = ( typeof val === 'object' && val !== null )
			? parseInt( val.size, 10 )
			: parseInt( val, 10 );
		return ( isNaN( n ) || n < 1 ) ? fallback : n;
	}

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

			var numMonths       = sliderVal( s.nummonths,        3 );
			var numMonthsTablet = sliderVal( s.nummonths_tablet, 2 );
			var numMonthsMobile = sliderVal( s.nummonths_mobile, 1 );

			// startmonth 0 = "current month" → pass null to AvailCalendar.
			var startMonth = ( s.startmonth && parseInt( s.startmonth, 10 ) > 0 )
				? parseInt( s.startmonth, 10 ) : null;
			var startYear = ( s.startyear && parseInt( s.startyear, 10 ) > 0 )
				? parseInt( s.startyear, 10 ) : null;

			// Pass roomid / propid when configured so the preview shows live data.
			var roomid = s.roomid ? String( s.roomid ).trim() : '';
			var propid = s.propid ? String( s.propid ).trim() : '';

			var cfg = {
				containerId:     $wrapper.attr( 'id' ),
				numMonths:       numMonths,
				numMonthsTablet: numMonthsTablet,
				numMonthsMobile: numMonthsMobile,
				startMonth:      startMonth,
				startYear:       startYear,
				lang:            s.lang || 'en',
			};

			if ( roomid ) {
				cfg.roomid = roomid;
			} else if ( propid ) {
				cfg.propid = propid;
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
