<?php

class MsCalendar {

	static function updateDatabase( DatabaseUpdater $updater ) {
		global $wgDBprefix;
		$updater->addExtensionTable( $wgDBprefix . 'mscal_list', __DIR__ . '/MsCalendar.sql' );
		$updater->addExtensionTable( $wgDBprefix . 'mscal_names', __DIR__ . '/MsCalendar.sql' );
		$updater->addExtensionTable( $wgDBprefix . 'mscal_content', __DIR__ . '/MsCalendar.sql' );
		return true;
	}

	static function setHook( Parser $parser ) {
		$parser->setHook( 'mscalendar', 'MsCalendar::render' );
		return true;
	}

	static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
		global $wgOut;

		if ( $input ) {
			$name = $input;
		} else if ( array_key_exists( 'name', $args ) ) {
			$name = $args['name']; // For backwards compatibility
		} else {
			return 'The calendar must have a name';
		}

		$sort = 'abc'; // Default
		if ( array_key_exists( 'sort', $args ) ) {
			$sort = $args['sort'];
		}

		// Get the id of the calendar
		$dbr = wfGetDB( DB_SLAVE );
		$table = $dbr->tableName( 'mscal_names' );
		$result = $dbr->query( "SELECT ID FROM $table WHERE Cal_Name = '$name'", __METHOD__ );
		$row = $dbr->fetchRow( $result );
		if ( $row ) {
			$id = $row['ID'];
		} else {
			$dbr->query( "INSERT INTO $table ( ID, Cal_Name ) VALUES ( '', '$name' )", __METHOD__ );
			$id = $dbr->insert_id;
		}

		$parser->disableCache();
		$wgOut->addModules( 'ext.MsCalendar' );
		$output = '<div class="custom-header">';
		$output .= '<div class="righty">';
		$output .= '<span id="custom-prev" class="custom-prev">&#10094;</span>';
		$output .= '<span class="custom-year-month"><span id="custom-month" class="custom-month"></span></span>';
		$output .= '<span id="custom-next" class="custom-next">&#10095;</span>';
		$output .= '<span id="custom-prev-year" class="custom-prev">&#10094;</span>';
		$output .= '<span class="custom-year-year"><span class="custom-year"></span></span>';
		$output .= '<span id="custom-next-year" class="custom-next">&#10095;</span>';
		$output .= '</div>';
		$output .= '<span id="custom-current" class="custom-current" title="' . wfMessage( 'msc-todaylabel' ) . '">' . wfMessage( 'msc-today' ) . '</span>';
		$output .= '</div>';
		$output .= '<div id="calendar" data-calendar-id="' . $id . '" data-calendar-name="' . $name . '" data-calendar-sort="' . $sort . '" class="fc-calendar-container"></div>';
		return $output;
	}

	static function getMonth( $month, $year, $calendarId, $calendarSort ) {
		if ( $calendarId === 0 ) {
			return false;
		}

		$order = 'ORDER BY Text'; // Default
		if ( $calendarSort == 'id' ) {
			$order = 'ORDER BY ID';
		}

		$vars = array();
		$dbr = wfGetDB( DB_SLAVE );

		$listTable = $dbr->tableName( 'mscal_list' );
		$contentTable = $dbr->tableName( 'mscal_content' );

		$query = "SELECT Date,
			DATE_FORMAT(Date, '%m') as monat,
			YEAR(date) as jahr,
			DAY(date) as tag,
			DATE_FORMAT(Date, '%m-%d-%Y') as Datum,
			Text_ID, b.ID, Text, Duration, Start_Date, Yearly, Day_of_Set
			FROM $listTable a, $contentTable b
			WHERE MONTH(Date) = '$month' AND YEAR(Date) = '$year' AND a.Text_ID = b.ID AND a.Cal_ID = $calendarId $order";

		$result = $dbr->query( $query, __METHOD__ );
		while ( $row = $dbr->fetchRow( $result ) ) {
			if ( $row['jahr'] == $year)	{
				$vars[ $row['Datum'] ][] = array(
					'ID' => $row['Text_ID'],
					'Text' => $row['Text'],
					'Duration' => $row['Duration'],
					'Day' => $row['Day_of_Set'],
					'Yearly' => $row['Yearly']
				);
			} else if ( $row['Yearly'] == 1 ) {
				$new_date = $row['monat'] . '-' . $row['tag'] . '-' . $year;
				$vars[ $new_date ][] = array(
					'ID' => $row['Text_ID'],
					'Text' => $row['Text'],
					'Duration' => $row['Duration'],
					'Day' => $row['Day_of_Set'],
					'Yearly' => $row['Yearly']
				);
			}
		}
		$dbr->freeResult( $result );
		return json_encode( $vars );
	}

	static function saveNew( $calendarId, $date, $title, $eventId, $duration, $yearly ) {
		$newDate = date( 'Y-m-d', strtotime( $date ) );
		$newDate2 = date( 'm-d-Y', strtotime( $date ) );

		$dbr = wfGetDB( DB_SLAVE );
		$contentTable = $dbr->tableName( 'mscal_content' );

		$query = "INSERT INTO $contentTable ( ID, Text, Start_Date, Duration, Yearly ) VALUES ( '', '$title', '$newDate', $duration, $yearly )";
		$result = $dbr->query( $query, __METHOD__ );

		$query = "SELECT MAX(ID) as maxid FROM $contentTable";
		$result = $dbr->query( $query, __METHOD__ );
		$row = $dbr->fetchRow( $result );
		$maxId =  $row['maxid'];

		$listTable = $dbr->tableName( 'mscal_list' );

		for ( $i = 0; $i < $duration; $i++ ) {
			$addDate = date( 'Y-m-d', strtotime( $newDate. ' + ' . $i . ' days' ) );
			$query = "INSERT INTO $listTable (ID, Date, Text_ID, Day_of_Set, Cal_ID ) VALUES ( '', '$addDate', $maxId, " . ( $i + 1 ) . ", $calendarId )";
			$result = $dbr->query( $query, __METHOD__ );
		}

		$vars[ $newDate2 ][] = array(
			'ID' => $maxId,
			'Text' => $title,
			'Duration' => $duration,
			'Yearly' => $yearly
		);
		return json_encode( $vars );
	}

	static function update( $calendarId, $date, $title, $eventId, $duration, $yearly ) {
		$newDate = date( 'Y-m-d', strtotime( $date ) );
		$newDate2 = date( 'm-d-Y', strtotime( $date ) );

		$dbr = wfGetDB( DB_SLAVE );

		$table = $dbr->tableName( 'mscal_content' );
		$query = "UPDATE $table SET Text = '$title', Start_Date = '$newDate', Duration = $duration, Yearly = $yearly WHERE ID = $eventId";
		$dbr->query( $query, __METHOD__ );

		$table = $dbr->tableName( 'mscal_list' );
		$query = "DELETE FROM $table WHERE Text_ID = $eventId";
		$dbr->query( $query, __METHOD__ );

		for ( $i = 0; $i < $duration; $i++ ) {
			$addDate = date( 'Y-m-d', strtotime( $newDate. ' + ' . $i . ' days' ) );
			$query = "INSERT INTO $table ( ID, Date, Text_ID, Day_of_Set, Cal_ID ) VALUES ( '', '$addDate', $eventId, " . ( $i + 1 ) . ", $calendarId )";
			$dbr->query( $query, __METHOD__ );
		}

		$data[ $newDate2 ][] = array(
			'ID' => $eventId,
			'Text' => $title,
			'Duration' => $duration,
			'Yearly' => $yearly
		);
		return json_encode( $data );
	}

	static function remove( $calendarId, $date, $title, $eventId, $duration, $yearly ) {
		$newDate = date( 'm-d-Y', strtotime( $date ) );
		$newDate2 = date( 'm-d-Y', strtotime( $date ) );

		$dbr = wfGetDB( DB_SLAVE );

		$table = $dbr->tableName( 'mscal_content' );
		$query = "DELETE FROM $table WHERE ID = $eventId";
		$result = $dbr->query( $query, __METHOD__ );

		$table = $dbr->tableName( 'mscal_list' );
		$query = "DELETE FROM $table WHERE Text_ID = $eventId";
		$result = $dbr->query( $query, __METHOD__ );

		$data[ $newDate2 ][] = array(
			'ID' => $eventId,
			'Text' => $title,
			'Duration' => $duration,
			'Yearly' => $yearly
		);
		return json_encode( $data );
	}
}