( function ( $, mw ) {

	$( '.fc-calendar-container' ).each( function () {

		var calendarContainer = $( this ),
			calendarHeader = calendarContainer.prev( '.ms-calendar-header' ),
			calendarSort = calendarContainer.attr( 'data-calendar-sort' ),
			calendarId = calendarContainer.attr( 'data-calendar-id' ),
			calendar = calendarContainer.calendario({
				onEventClick: function ( $el, dateProperties ) {
					//console.log( dateProperties );
					first_day = dateProperties.day_of_set == 1 ? true : false,
					createDialog( mw.msg( 'msc-eventedit' ), true, first_day, calendarContainer );
					$( '.ms-calendar-dialog-form input[name="form_datum"]' ).val( dateProperties.dateger );
					$( '.ms-calendar-dialog-form input[name="form_inhalt"]' ).val( dateProperties.event );
					$( '.ms-calendar-dialog-form input[name="form_id"]' ).val( dateProperties.event_id );
					$( '.ms-calendar-dialog-form input[name="form_duration"]' ).val( dateProperties.duration );
					$( '.ms-calendar-dialog-form input[name="form_yearly"]' ).attr( 'checked', ( dateProperties.yearly == 1 ? true : false ) );
				},

				onAddEventClick: function ( $el, dateProperties ) {
					createDialog( mw.msg( 'msc-eventcreate' ), false, true, calendarContainer );
					$( '.ms-calendar-dialog-form input[name="form_datum"]' ).val( dateProperties.dateger );
					$( '.ms-calendar-dialog-form input[name="form_inhalt"]' ).val( '' );
				},

				caldata: ''
			});

		var monthField = $( '<select>' ).change( function () {
			var month = parseInt( $( 'option:selected', this ).val() );
			//calendar.month = month;
			gotoMonth( month );
		}).appendTo( $( '.ms-calendar-month', calendarHeader ) );

		var yearField = $( '<input>' ).change( function () {
			var year = parseInt( $( this ).val() );
			calendar.year = year;
			gotoYear( year );
		}).appendTo( $( '.ms-calendar-year', calendarHeader ) );
		
		$( '.ms-calendar-next', calendarHeader ).click( function () {
			calendar.gotoNextMonth( updateMonthYear );
			loadMonth();
		});
		$( '.ms-calendar-prev', calendarHeader ).click( function () {
			calendar.gotoPreviousMonth( updateMonthYear );
			loadMonth();
		});
		$( '.ms-calendar-current', calendarHeader ).click( function () {
			calendar.gotoNow( updateMonthYear );
			loadMonth();
		});
		$( '.ms-calendar-prev-year', calendarHeader ).click( function () {
			calendar.gotoPreviousYear( updateMonthYear );
			loadMonth();
		});
		$( '.ms-calendar-next-year', calendarHeader ).click( function () {
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
			//New code by Patrick Dudics.
			$.ajax({
				// request type ( GET or POST ) use this for ajax prior to v1.9 otherwise use method:"GET"
				type: "GET",

				// the URL to which the request is sent
				url: mw.util.wikiScript('api'),

				// data to be sent to the server				
				data: { 'action':'mscalendargetmonth', 'format':'json', 'month': month, 'year': year, 'calendarId': calendarId, 'calendarSort': calendarSort },

				// The type of data that you're expecting back from the server
				dataType: 'json',

				// Function to be called if the request succeeds
				success: function( jsonDataObj ){
					/*
					if (typeof jsonDataObj === 'string' || jsonDataObj instanceof String) {
						console.log( 'jsonDataObj is a string' );
					}
					else {
						console.log( 'jsonDataObj is an object' );
					}
					*/
					//console.log(JSON.stringify(jsonDataObj));
					//console.log(jsonDataObj.data);
					parsedJsonDataObj = JSON.parse(jsonDataObj.data);
					//console.log(parsedJsonDataObj);
					for ( dateEntry in parsedJsonDataObj ) {
						//Dynamically accessing json object property via [ propertyname ].
						//In this case the dateEntry is the property name.  Something like "03-26-2023".
						arrayOfObjects = parsedJsonDataObj[dateEntry];
						//console.log( JSON.stringify(arrayOfObjects) );
						//
						for ( i = 0; i < arrayOfObjects.length; i++ ) {
							//console.log ( arrayOfObjects[i] );
							for ( propName in arrayOfObjects[i]) {
								//console.log( propName ); //propertyName like 'id' or 'text'
								//console.log( arrayOfObjects[i][propName] ); //property value
								htmlEscapedValue = mw.html.escape( arrayOfObjects[i][propName] );  //Escaped the value.
								//console.log ( htmlEscapedValue );
								arrayOfObjects[i][propName] = htmlEscapedValue; //Store the escaped value.
							}
						}
					}
					calendar.setData( parsedJsonDataObj );
					//console.log( calendar.caldata );
				}
			});
			/*
			{
				"0":"
					{
						"03-26-2023":[
							{
								"ID":"4037",
								"Text":" Turnover - Gustavo P.",
								"Duration":"1",
								"Day":"1",
								"Yearly":"1"
							},
							{
								"ID":"4038",
								"Text":"TurnoverBackup - Clauida C.",
								"Duration":"1",
								"Day":"1",
								"Yearly":"1"
							}
						],
						"03-01-2023":[
							{
								"ID":"3931",
								"Text":"HOLIDAY-Balearic Day (Spain)",
								"Duration":"1",
								"Day":"1",
								"Yearly":"1"
							}
						]
					}
					"
			}
			*/
			/* Original code.
			$.get( mw.util.wikiScript(), {
				action: 'ajax',
				rs: 'MsCalendar::getMonth',
				rsargs: [ month, year, calendarId, calendarSort ]
			}, function ( data ) {
				for ( year in data ) {
					for ( i = 0; i < data[year].length; i++ ) {
						for( items in data[year][i] ) {
							data[year][i][items] = mw.html.escape( data[year][i][items] );
						}
					}
				}
				//console.log( data );
				calendar.setData( data );
				//console.log( calendar.caldata );
			}, 'json' );
			*/
		}
		loadMonth();

		function fillMonthsYears() {
			for ( var i = 0; i < 12; i++ ) {
				$( '<option>', { value: i, text: calendar.options.months[ i ] } ).appendTo( monthField );
			}
			$( 'option[value="' + calendar.month + '"]', monthField ).attr( 'selected', true );

			yearField.val( calendar.year );
		}
		fillMonthsYears();

		function createDialog( title, buttons, first_day, calendarContainer ) {
			var info = first_day ? '' : '<div class="exclamation">' + mw.msg( 'msc-notfirstday' ) + '</div>',
				remove = '',
				dialog_buttons = {};

			if ( buttons ) {
				dialog_buttons[ mw.msg('msc-change') ] = function () {
					dialogPressButton( 'MsCalendar::update', $( this ) );
				};
				remove = '<br><label><input type="checkbox" name="remove_event"> ' + mw.msg( 'msc-remove' ) + '</label>';
			} else {
				dialog_buttons[ mw.msg( 'msc-create' ) ]  = function () {
					dialogPressButton( 'MsCalendar::saveNew', $( this ) );
				};
			}
			dialog_buttons[ mw.msg( 'msc-cancel' ) ] = function () {
				$( this ).dialog( 'close' );
				$( this ).remove();
			}

			$( '<div>' ).addClass( 'ms-calendar-dialog-form' ).html(
				info + '<form><fieldset>' +
				'<label>' + mw.msg( 'msc-eventname' ) + ' <input type="text" size="39" name="form_inhalt" class="text ui-widget-content ui-corner-all"></label>' +
				'<br>' +
				'<label>' + mw.msg( 'msc-eventdate' ) + ' <input type="text" size="10" maxlength="10" name="form_datum" class="text ui-widget-content ui-corner-all"></label>' +
				'<br>' +
				'<label>' + mw.msg( 'msc-eventduration' ) + ' <input type="text" size="1" name="form_duration" value="1" class="text ui-widget-content ui-corner-all"></label>' +
				'<label class="yearly"><input type="checkbox" name="form_yearly" checked> ' + mw.msg( 'msc-eventyearly' ) + '</label>' +
				remove +
				'<input type="hidden" name="form_id">' +
				'</fieldset></form>' )
			.insertBefore( calendarContainer )
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
				buttonImage: mw.config.get( 'wgExtensionAssetsPath' ) + '/MsCalendar/resources/images/calendar-select.png',
				buttonImageOnly: true
				//defaultDate: dateProperties.day + '.' + dateProperties.month + '.' + dateProperties.year
			});
		}

		function dialogPressButton( rs_var, this_dialog ) {
			//console.log ( rs_var );
			if ( this_dialog.find( 'input[name="remove_event"]' ).is( ':checked' ) ) {
				rs_var = 'MsCalendar::remove';
			}
			var inhalt = this_dialog.find( 'input[name="form_inhalt"]' ).val(),
				datum = this_dialog.find( 'input[name="form_datum"]' ).val(),
				event_id = this_dialog.find( 'input[name="form_id"]' ).val(),
				duration = this_dialog.find( 'input[name="form_duration"]' ).val(),
				yearly = this_dialog.find( 'input[name="form_yearly"]' ).is( ':checked' ) ? 1 : 0,
				bValid = true;

			if ( bValid ) {
				//console.log( datum.val() );
				//New code added by Patrick Dudics
				apiAction = '';
				switch ( rs_var.toLowerCase().trim() ) {
					case "mscalendar::savenew":
						apiAction = "mscalendarsavenew";
						event_id = 0;
						break;
					case "mscalendar::update":
						apiAction = "mscalendarupdate";
						break;
					case "mscalendar::remove":
						apiAction = "mscalendarremove";
						break;
					default:
						apiAction = "unknownApiCall";
				}
				
				//console.log( calendarId );
				//console.log( datum );
				//console.log( inhalt );
				//console.log( event_id );
				//console.log( duration );
				//console.log( yearly );
				
				$.ajax({
					// request type ( GET or POST )
					type: "GET",
						
					// the URL to which the request is sent
					url: mw.util.wikiScript('api'),

					// data to be sent to the server
					data: { 'action':apiAction, 'format':'json', 'calendarId':calendarId, 'date':datum, 'title':inhalt, 'eventId':event_id, 'duration':duration, 'yearly':yearly },

					// The type of data that you're expecting back from the server
					dataType: 'json',
						
					// Function to be called if the request succeeds
					success: function( jsonDataObj ){
						//console.log(JSON.stringify(jsonDataObj));
						loadMonth();
					}
				});
				//Orignal code:
				/*
				$.get( mw.util.wikiScript(), {
					action: 'ajax',
					rs: rs_var,
					rsargs: [ calendarId, datum, inhalt, event_id, duration, yearly ]
				}, function ( data ) {
					loadMonth();
				}, 'json' );
				*/
				this_dialog.dialog( 'close' );
				this_dialog.remove();
			}
		}
	}); // each
}( jQuery, mediaWiki ) );
