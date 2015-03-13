$( function () {

	if ( $( '#calendar' ).length === 0 ) {
		return false;
	}

	var calendarName = $( '#calendar' ).attr( 'data-calendar-name' );
	if ( !calendarName ) {
		return false;
	}

	var calendarSort = $( '#calendar' ).attr( 'data-calendar-sort' );
	var calendarId = $( '#calendar' ).attr( 'data-calendar-id' );

	var calendar = $( '#calendar' ).calendario({
		onEventClick: function ( $el, dateProperties ) {
			//console.log( dateProperties );
			first_day = dateProperties.day_of_set == 1 ? true : false,
			createDialog( mw.msg( 'msc-eventedit' ), true, first_day );
			$( '#dialog-form input[name="form_datum"]' ).val( dateProperties.dateger );
			$( '#dialog-form input[name="form_inhalt"]' ).val( dateProperties.event );
			$( '#dialog-form input[name="form_id"]' ).val( dateProperties.event_id );
			$( '#dialog-form input[name="form_duration"]' ).val( dateProperties.duration );
			$( '#dialog-form input[name="form_yearly"]' ).attr( 'checked', ( dateProperties.yearly == 1 ? true : false ) );
		},

		onAddEventClick: function ( $el, dateProperties ) {
			createDialog( mw.msg( 'msc-eventcreate' ), false, true );
			$( '#dialog-form input[name="form_datum"]' ).val( dateProperties.dateger );
			$( '#dialog-form input[name="form_inhalt"]' ).val( '' );
		},

		caldata: ''
	});

	var monthField = $( '<select/>' ).change( function () {
		var month = parseInt( $( 'option:selected', this ).val() );
		//calendar.month = month;
		gotoMonth( month );
	}).appendTo( $( '#custom-month' ) );

	var yearField = $( '<input/>' ).change( function () {
		var year = parseInt( $( this ).val() );
		calendar.year = year;
		gotoYear( year );
	}).appendTo( $( '.custom-year' ) );

	$( '#custom-next' ).click( function () {
		calendar.gotoNextMonth( updateMonthYear );
		loadMonth();
	});
	$( '#custom-prev' ).click( function () {
		calendar.gotoPreviousMonth( updateMonthYear );
		loadMonth();
	});
	$( '#custom-current' ).click( function () {
		calendar.gotoNow( updateMonthYear );
		loadMonth();
	});
	$( '#custom-prev-year' ).click( function () {
		calendar.gotoPreviousYear( updateMonthYear );
		loadMonth();
	});
	$( '#custom-next-year' ).click( function () {
		calendar.gotoNextYear( updateMonthYear );
		loadMonth();
	});

	function updateMonthYear() {
		monthField[0].selectedIndex = -1; // Unselect all
		monthField[0].selectedIndex = calendar.month; // Select the selected month
		yearField.val( calendar.year );
	}

	function gotoMonth( month ) {
		//console.log( month );
		loadMonth( month + 1 );
		calendar.gotoX( month, calendar.year, updateMonthYear );
	}

	function gotoYear( year ) {
		//console.log( year );
		loadMonth();
		calendar.gotoX( calendar.month, year, updateMonthYear );
	}

	function loadMonth( month ) {
		if ( month === undefined ) {
			month = calendar.getMonth();
		}
		var year = calendar.getYear();
		calendar.caldata = {};
		$.get( mw.util.wikiScript(), {
			action: 'ajax',
			rs: 'MsCalendar::getMonth',
			rsargs: [ month, year, calendarId, calendarSort ]
		}, function ( data ) {
			//console.log( data );
			calendar.setData( data );
			//console.log( calendar.caldata );
		}, 'json' );
	}
	loadMonth();

	function fillMonthsYears() {
		for ( var i = 0; i < 12; i++ ) {
			$( '<option />', { value: i, text: calendar.options.months[ i ] } ).appendTo( monthField );
		}
		$( 'option[value="' + calendar.month + '"]', monthField ).attr( 'selected', true );

		yearField.val( calendar.year );
	}
	fillMonthsYears();

	function createDialog( title, buttons, first_day ) {
		//alert( first_day );
		var info = first_day ? '' : '<div class="exclamation">' + mw.msg( 'msc-notfirstday' ) + '</div>';
		var remove = '';

		var dialog_buttons = {};
		if ( buttons ) {
			dialog_buttons[ mw.msg('msc-change') ] = function () {
				dialogPressButton( 'MsCalendar::update', $( this ) );
			};
			remove = '<br /><label><input type="checkbox" name="remove_event" /> ' + mw.msg( 'msc-remove' ) + '</label>';
		} else {
			dialog_buttons[ mw.msg( 'msc-create' ) ]  = function () {
				dialogPressButton( 'MsCalendar::saveNew', $( this ) );
			};
		}
		dialog_buttons[ mw.msg( 'msc-cancel' ) ] = function () {
			$( this ).dialog( 'close' );
			$( this ).remove();
		}

		$( document.createElement( 'div' ) ).attr({
			'id': 'dialog-form',
			'class': 'calendar_dialog_form'
		}).html( info + '<form><fieldset>' +
			'<label>' + mw.msg( 'msc-eventname' ) + ' <input type="text" size="39" name="form_inhalt" class="text ui-widget-content ui-corner-all" /></label>' +
			'<br />' +
			'<label>' + mw.msg( 'msc-eventdate' ) + ' <input type="text" size="10" maxlength="10" name="form_datum" class="text ui-widget-content ui-corner-all" /></label>' +
			'<br />' +
			'<label>' + mw.msg( 'msc-eventduration' ) + ' <input type="text" size="1" name="form_duration" value="1" class="text ui-widget-content ui-corner-all" /></label>' +
			'<label class="yearly"><input type="checkbox" name="form_yearly" id="form_yearly" checked /> ' + mw.msg( 'msc-eventyearly' ) + '</label>' +
			remove +
			'<input type="hidden" name="form_id" />' +
			'</fieldset></form>' ).insertBefore( $( '#calendar' ) )
		.dialog({
			autoOpen: true,
			title: title,
			width: 350,
			modal: true,
			buttons: dialog_buttons,
			close: function ( event, ui ) {
				$( this ).remove();
			}
		})
		.find( 'input[name="form_datum"]' ).datepicker({
			showOn: 'button',
			buttonImage: wgExtensionAssetsPath + '/MsCalendar/images/calendar-select.png',
			buttonImageOnly: true
			//defaultDate: dateProperties.day + '.' + dateProperties.month + '.' + dateProperties.year
		});
	}

	function dialogPressButton( rs_var, this_dialog ) {
		if ( this_dialog.find( 'input[name="remove_event"]' ).is( ':checked' ) ) {
			rs_var = 'MsCalendar::remove';
		}
		var inhalt = this_dialog.find( 'input[name="form_inhalt"]' );
		var datum = this_dialog.find( 'input[name="form_datum"]' );
		var event_id = this_dialog.find( 'input[name="form_id"]' );
		var duration = this_dialog.find( 'input[name="form_duration"]' );
		var yearly = this_dialog.find( 'input[name="form_yearly"]' ).is( ':checked' ) ? 1 : 0;
		var bValid = true;
		//allFields.removeClass( 'ui-state-error' );
		if ( bValid ) {
			//console.log( datum.val() );
			$.get( mw.util.wikiScript(), {
				action: 'ajax',
				rs: rs_var,
				rsargs: [ calendarId, datum.val(), inhalt.val(), event_id.val(), duration.val(), yearly ]
			}, function ( data ) {
				loadMonth();
			}, 'json' );
			this_dialog.dialog( 'close' );
			this_dialog.remove();
		}
	}
});