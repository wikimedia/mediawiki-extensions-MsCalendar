<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class ApiMsCalendarGetMonth extends ApiBase {

	public function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();

		// Split parameter values out to separate variables.
		$month = $params['month'];
		$year = $params['year'];
		$calendarId = $params['calendarId'];
		$calendarSort = $params['calendarSort'];

		if ( $calendarId === 0 ) {
			return false;
		}

		$vars = [];

		$services = MediaWikiServices::getInstance();
		$provider = $services->getConnectionProvider();
		$dbr = $provider->getReplicaDatabase();

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

		foreach ( $result as $row ) {
			if ( $row->jahr == $year ) {
				$vars[ $row->Datum ][] = [
					'ID' => $row->Text_ID,
					'Text' => $row->Text,
					'Duration' => $row->Duration,
					'Day' => $row->Day_of_Set,
					'Yearly' => $row->Yearly
				];
			} elseif ( $row->Yearly == 1 ) {
				$new_date = $row->monat . '-' . $row->tag . '-' . $year;
				$vars[ $new_date ][] = [
					'ID' => $row->Text_ID,
					'Text' => $row->Text,
					'Duration' => $row->Duration,
					'Day' => $row->Day_of_Set,
					'Yearly' => $row->Yearly
				];
			}
		}

		$this->getResult()->addValue( null, "data", json_encode( $vars ), 0 );
	}

	/**
	 * Get allowed parameters
	 * @return array Array of allowed parameters
	 */
	public function getAllowedParams() {
		return [
			'month' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'year' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'calendarId' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'calendarSort' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
