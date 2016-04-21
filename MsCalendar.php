<?php

$wgExtensionCredits['parserhook'][] = array(
	'name' => 'MsCalendar',
	'url' => 'https://www.mediawiki.org/wiki/Extension:MsCalendar',
	'version' => '2.2',
	'descriptionmsg' => 'msc-desc',
	'license-name' => 'GPLv3',
	'author' => array(
		'[mailto:wiki@ratin.de Martin Schwindl]',
		'[mailto:wiki@keyler-consult.de Martin Keyler]',
		'[https://www.mediawiki.org/wiki/User:Luis_Felipe_Schenone Luis Felipe Schenone]',
		'[https://www.mediawiki.org/wiki/User:Fraifrai Frédéric Souchon]'
	),
);

$wgResourceModules['ext.MsCalendar'] = array(
	'scripts' => array(
		'js/jquery.calendario.js',
		'js/MsCalendar.js'
	),
	'styles' => 'MsCalendar.css',
	'messages' => array(
		'msc-desc',
		'msc-notfirstday',
		'msc-change',
		'msc-remove',
		'msc-create',
		'msc-cancel',
		'msc-eventname',
		'msc-eventdate',
		'msc-eventduration',
		'msc-eventyearly',
		'msc-eventedit',
		'msc-eventcreate',
		'msc-today',
		'msc-todaylabel',
		'msc-months',
		'msc-days'
	),
	'dependencies' => array(
		'jquery.ui.dialog',
		'jquery.ui.datepicker'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'MsCalendar'
);

$wgAutoloadClasses['MsCalendar'] = __DIR__ . '/MsCalendar.body.php';

$wgExtensionMessagesFiles['MsCalendar'] = __DIR__ . '/MsCalendar.i18n.php';
$wgMessagesDirs['MsCalendar'] = __DIR__ . '/i18n';

$wgHooks['ParserFirstCallInit'][] = 'MsCalendar::setHook';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'MsCalendar::updateDatabase';

$wgAjaxExportList[] = 'MsCalendar::getMonth';
$wgAjaxExportList[] = 'MsCalendar::saveNew';
$wgAjaxExportList[] = 'MsCalendar::update';
$wgAjaxExportList[] = 'MsCalendar::remove';
$wgAjaxExportList[] = 'MsCalendar::checkDB';