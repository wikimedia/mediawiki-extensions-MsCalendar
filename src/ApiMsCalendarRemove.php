<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

class ApiMsCalendarRemove extends ApiBase {

	public function execute() {

		// Get the parameters
		$params = $this->extractRequestParams();

		// Split parameter values out to separate variables.
		$calendarId = $params['calendarId'];
		$date = $params['date'];
		$title = $params['title'];
		$eventId = $params['eventId'];
		$duration = $params['duration'];
		$yearly = $params['yearly'];

		$newDate = date( 'm-d-Y', strtotime( $date ) );
		$newDate2 = date( 'm-d-Y', strtotime( $date ) );

		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbw = $lb->getConnectionRef( DB_PRIMARY );

		$dbw->delete( 'mscal_content', [ 'ID' => $eventId ] );
		$dbw->delete( 'mscal_list', [ 'Text_ID' => $eventId ] );

		$data[ $newDate2 ][] = [
			'ID' => $eventId,
			'Text' => $title,
			'Duration' => $duration,
			'Yearly' => $yearly
		];

		$this->getResult()->addValue( null, "data", json_encode( $data ), 0 );
	}

	/**
	 * Get allowed parameters
	 * @return array Array of allowed parameters
	 */
	public function getAllowedParams() {
		return [
			'calendarId' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'date' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'title' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			],
			'eventId' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'duration' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'yearly' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
