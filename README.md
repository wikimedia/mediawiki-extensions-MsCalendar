MsCalendar
==========
The MsCalendar extension adds the <mscalendar> tag to insert calendars into wiki pages.

Installation
------------
To install MsCalendar, add the following to your LocalSettings.php:

require_once "$IP/extensions/MsCalendar/MsCalendar.php";

Usage
-----
To insert a calendar into a wiki page, edit the page and add the following minimal wikitext:

<mscalendar>Name of the calendar</mscalendar>

Configuration
-------------
You can sort the content of the calendar alphabetically (default) or by id. To sort by id, simply do:

<mscalendar sort="id">Name of the calendar</mscalendar>

And to sort alphabetically, just omit the sort option, or do:

<mscalendar sort="abc">Name of the calendar</mscalendar>

Credits
-------
* Developed and coded by Martin Schwindl (wiki@ratin.de)
* Idea, project management and bug fixing by Martin Keyler (wiki@keyler-consult.de)
* Updated, debugged and enhanced by Luis Felipe Schenone (schenonef@gmail.com
* This extension uses jquery.calendario.js v1.0.0 (http://www.codrops.com) - Licensed under the MIT license (http://www.opensource.org/licenses/mit-license.php) - Copyright 2012 - Codrops (http://www.codrops.com)