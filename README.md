MsCalendar
==========
The MsCalendar extension adds the <mscalendar> tag to insert calendars into wiki pages.

Installation
------------
To install MsCalendar, add the following to your LocalSettings.php:

require_once "$IP/extensions/MsCalendar/MsCalendar.php";

Then run the update script to create the necessary tables (see https://www.mediawiki.org/wiki/Manual:Update.php).

Usage
-----
To insert a calendar into a wiki page, simply edit a page and add the following wikitext:

<mscalendar>Name of the calendar</mscalendar>

You can insert as many calendars as you want, but each must have a unique name.

Configuration
-------------
The events of each day are sorted alphabetically. You can also sort them by id by doing:

<mscalendar sort="id">Name of the calendar</mscalendar>

Credits
-------
* Developed and coded by Martin Schwindl (wiki@ratin.de)
* Idea, project management and bug fixing by Martin Keyler (wiki@keyler-consult.de)
* Updated, debugged and enhanced by Luis Felipe Schenone (schenonef@gmail.com)
* Support for multiple calendars by Frédéric Souchon (aka Fraifrai)
* This extension uses jquery.calendario.js v1.0.0 (http://www.codrops.com) - Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php) - Copyright 2012 - Codrops (http://www.codrops.com)