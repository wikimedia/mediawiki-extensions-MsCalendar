<?php

use MediaWiki\MediaWikiServices;

class MsCalendar {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function updateDatabase( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'mscal_list', __DIR__ . '/../sql/MsCalendar.sql' );
		$updater->addExtensionTable( 'mscal_names', __DIR__ . '/../sql/MsCalendar.sql' );
		$updater->addExtensionTable( 'mscal_content', __DIR__ . '/../sql/MsCalendar.sql' );
	}

	/**
	 * @param Parser $parser
	 */
	public static function setHook( Parser $parser ) {
		$parser->setHook( 'MsCalendar', [ self::class, 'render' ] );
	}

	/**
	 * @param string $input -- value between the html tag <MsCalendar>...</MsCalendar>
	 * @param array $args -- args array which ar html tag attributes.
	 * @param Parser $parser -- Parent MediaWiki Parser object.
	 * @param PPFrame $frame -- Parent MediaWiki parent frame.
	 * @return string
	 */
	public static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( $input ) {
			$name = $input;
		} elseif ( array_key_exists( 'name', $args ) ) {
			// For backwards compatibility
			$name = $args['name'];
		} else {
			return wfMessage( 'msc-noname' );
		}

		// Default
		$sort = 'abc';
		if ( array_key_exists( 'sort', $args ) ) {
			$sort = $args['sort'];
		}

		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();

		// Get the id of the calendar
		$row = $dbr->selectRow( 'mscal_names', [ 'ID' ], [ 'Cal_Name' => $name ] );
		if ( $row ) {
			$id = $row->ID;
		} else {
			$dbw = $provider->getPrimaryDatabase();
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
		$parser->getOutput()->addModules( [ 'ext.calendario', 'ext.MsCalendar' ] );
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
}
