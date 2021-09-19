<?php

class MsCalendar {

	public static function onRegistration() {
		global $wgAjaxExportList;
		$wgAjaxExportList[] = 'MsCalendar::getMonth';
		$wgAjaxExportList[] = 'MsCalendar::saveNew';
		$wgAjaxExportList[] = 'MsCalendar::update';
		$wgAjaxExportList[] = 'MsCalendar::remove';
		$wgAjaxExportList[] = 'MsCalendar::checkDB';
	}

	/**
	 * @param DatabaseUpdater $updater
	 */
	static function updateDatabase( DatabaseUpdater $updater ) {
		global $wgDBprefix;
		$updater->addExtensionTable( $wgDBprefix . 'mscal_list', __DIR__ . '/../sql/MsCalendar.sql' );
		$updater->addExtensionTable( $wgDBprefix . 'mscal_names', __DIR__ . '/../sql/MsCalendar.sql' );
		$updater->addExtensionTable( $wgDBprefix . 'mscal_content', __DIR__ . '/../sql/MsCalendar.sql' );
	}

	/**
	 * @param Parser $parser
	 */
	static function setHook( Parser $parser ) {
		$parser->setHook( 'MsCalendar', 'MsCalendar::render' );
	}

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return string
	 */
	static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( $input ) {
			$name = $input;
		} elseif ( array_key_exists( 'name', $args ) ) {
			$name = $args['name']; // For backwards compatibility
		} else {
			return wfMessage( 'msc-noname' );
		}

		$sort = 'abc'; // Default
		if ( array_key_exists( 'sort', $args ) ) {
			$sort = $args['sort'];
		}

		// Get the id of the calendar
		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select( 'mscal_names', [ 'ID' ], [ 'Cal_Name' => $name ] );
		$row = $dbr->fetchRow( $result );
		if ( $row ) {
			$id = $row['ID'];
		} else {
			$dbw = wfGetDB( DB_PRIMARY );
			$dbw->insert(
				'mscal_names',
				[
					'ID' => null,
					'Cal_Name' => $name,
				]
			);
			$id = $dbw->insertId();
		}

		$parser->getOutput()->updateCacheExpiry( 0 );
		$parser->getOutput()->addModules( 'ext.calendario' );
		$parser->getOutput()->addModules( 'ext.MsCalendar' );
		$output = '<div class="ms-calendar-header">';
		$output .= '<div class="righty">';
		$output .= '<span class="ms-calendar-prev">&#10094;</span>';
		$output .= '<span class="ms-calendar-year-month"><span class="ms-calendar-month"></span></span>';
		$output .= '<span class="ms-calendar-next">&#10095;</span>';
		$output .= '<span class="ms-calendar-prev-year">&#10094;</span>';
		$output .= '<span class="ms-calendar-year-year"><span class="ms-calendar-year"></span></span>';
		$output .= '<span class="ms-calendar-next-year">&#10095;</span>';
		$output .= '</div>';
		$output .= '<span class="ms-calendar-current" title="' . wfMessage( 'msc-todaylabel' )->escaped() . '">' . wfMessage( 'msc-today' )->parse() . '</span>';
		$output .= '</div>';
		$output .= '<div class="fc-calendar-container" data-calendar-id="' . htmlspecialchars( $id ) . '" data-calendar-name="' . htmlspecialchars( $name ) . '" data-calendar-sort="' . htmlspecialchars( $sort ) . '"></div>';
		return $output;
	}

	/**
	 * @param int $month
	 * @param int $year
	 * @param int $calendarId
	 * @param string $calendarSort
	 * @return string
	 */
	static function getMonth( $month, $year, $calendarId, $calendarSort ) {
		if ( $calendarId === 0 ) {
			return false;
		}

		$vars = [];
		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select(
			[ 'a' => 'mscal_list', 'b' => 'mscal_content' ],
			[
				"DATE_FORMAT(Date, '%m') as monat", "YEAR(date) as jahr", "DAY(date) as tag",
				"DATE_FORMAT(Date, '%m-%d-%Y') as Datum",
				'Text_ID', 'b.ID', 'Text', 'Duration',
				'Start_Date', 'Yearly', 'Day_of_Set',
			],
			[
				'MONTH(Date)' => $month,
				'YEAR(Date)'  => $year,
				'a.Text_ID = b.ID',
				'a.Cal_ID'    => $calendarId,
			],
			__METHOD__,
			[ 'ORDER BY' => ( $calendarSort == 'id' ) ? 'ID' : 'Text' ]
		);
		// phpcs:ignore MediaWiki.ControlStructures.AssignmentInControlStructures.AssignmentInControlStructures
		while ( $row = $dbr->fetchRow( $result ) ) {
			if ( $row['jahr'] == $year ) {
				$vars[ $row['Datum'] ][] = [
					'ID' => $row['Text_ID'],
					'Text' => $row['Text'],
					'Duration' => $row['Duration'],
					'Day' => $row['Day_of_Set'],
					'Yearly' => $row['Yearly']
				];
			} elseif ( $row['Yearly'] == 1 ) {
				$new_date = $row['monat'] . '-' . $row['tag'] . '-' . $year;
				$vars[ $new_date ][] = [
					'ID' => $row['Text_ID'],
					'Text' => $row['Text'],
					'Duration' => $row['Duration'],
					'Day' => $row['Day_of_Set'],
					'Yearly' => $row['Yearly']
				];
			}
		}
		$dbr->freeResult( $result );
		return json_encode( $vars );
	}

	/**
	 * @param int $calendarId
	 * @param string $date
	 * @param string $title
	 * @param int $eventId
	 * @param int $duration
	 * @param int $yearly
	 * @return string
	 */
	static function saveNew( $calendarId, $date, $title, $eventId, $duration, $yearly ) {
		$newDate = date( 'Y-m-d', strtotime( $date ) );
		$newDate2 = date( 'm-d-Y', strtotime( $date ) );

		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->insert(
			'mscal_content',
			[
				'ID'         => null,
				'Text'       => $title,
				'Start_Date' => $newDate,
				'Duration'   => $duration,
				'Yearly'     => $yearly,
			]
		);

		$result = $dbw->select( 'mscal_content', [ 'MAX(ID) as maxid' ], '' );
		$row = $dbw->fetchRow( $result );
		$maxId = $row['maxid'];

		for ( $i = 0; $i < $duration; $i++ ) {
			$addDate = date( 'Y-m-d', strtotime( $newDate . ' + ' . $i . ' days' ) );
			$dbw->insert(
				'mscal_list',
				[
					'ID'         => null,
					'Date'       => $addDate,
					'Text_ID'    => $maxId,
					'Day_of_Set' => $i + 1,
					'Cal_ID'     => $calendarId,
				]
			);
		}

		$vars[ $newDate2 ][] = [
			'ID' => $maxId,
			'Text' => $title,
			'Duration' => $duration,
			'Yearly' => $yearly
		];
		return json_encode( $vars );
	}

	/**
	 * @param int $calendarId
	 * @param string $date
	 * @param string $title
	 * @param int $eventId
	 * @param int $duration
	 * @param int $yearly
	 * @return string
	 */
	static function update( $calendarId, $date, $title, $eventId, $duration, $yearly ) {
		$newDate = date( 'Y-m-d', strtotime( $date ) );
		$newDate2 = date( 'm-d-Y', strtotime( $date ) );

		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->update(
			'mscal_content',
			[
				'Text'       => $title,
				'Start_Date' => $newDate,
				'Duration'   => $duration,
				'Yearly'     => $yearly,
			],
			[ 'ID' => $eventId ]
		);

		$dbw->delete( 'mscal_list', [ 'Text_ID' => $eventId ] );

		for ( $i = 0; $i < $duration; $i++ ) {
			$addDate = date( 'Y-m-d', strtotime( $newDate . ' + ' . $i . ' days' ) );
			$dbw->insert(
				'mscal_list',
				[
					'ID'         => null,
					'Date'       => $addDate,
					'Text_ID'    => $eventId,
					'Day_of_Set' => $i + 1,
					'Cal_ID'     => $calendarId,
				]
			);
		}

		$data[ $newDate2 ][] = [
			'ID' => $eventId,
			'Text' => $title,
			'Duration' => $duration,
			'Yearly' => $yearly
		];
		return json_encode( $data );
	}

	/**
	 * @param int $calendarId
	 * @param string $date
	 * @param string $title
	 * @param int $eventId
	 * @param int $duration
	 * @param int $yearly
	 * @return string
	 */
	static function remove( $calendarId, $date, $title, $eventId, $duration, $yearly ) {
		$newDate = date( 'm-d-Y', strtotime( $date ) );
		$newDate2 = date( 'm-d-Y', strtotime( $date ) );

		$dbw = wfGetDB( DB_PRIMARY );
		$dbw->delete( 'mscal_content', [ 'ID' => $eventId ] );
		$dbw->delete( 'mscal_list', [ 'Text_ID' => $eventId ] );

		$data[ $newDate2 ][] = [
			'ID' => $eventId,
			'Text' => $title,
			'Duration' => $duration,
			'Yearly' => $yearly
		];
		return json_encode( $data );
	}
}
