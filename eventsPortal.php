<?php

# Online event listing system
# Version 1.0.6
# 
# Licence: GPL
# (c) Martin Lucas-Smith, Cambridge University Students' Union
# (c) Martin Lucas-Smith, Department of Geography, University of Cambridge


/* Proposed developments */

#!#  iCal exort
#!#  Map panel for locations
#!#  Events over several days
#!#  Event recurrence - see:   http://mywebland.com/forums/showtopic.php?p=3806   http://forums.devx.com/showthread.php?threadid=136165   http://archives.postgresql.org/pgsql-sql/2002-08/msg00302.php
#!#  Admins to get to old events
#!#  Export as a proper CSV file, not an HTML table shown on the page
#!#  Allow multiple categories, e.g. an LBGT freshers event would have two categories, max two
#!#  Feed configuration
#!#  Move feeds to /feeds/
#!#  Automatic mailshot; configure starting text, e-mail recipient and sending period


/* # Apache setup:

# Events portal system
<Macro MacroEventsPortal $baselocation $description>
	Use MacroFrontControllerApplication "$baselocation/" "$description"
	<Location $baselocation/>
		php_value output_buffering 32768
		AAAlwaysDecode On
	</Location>
	RewriteRule ^$baselocation/listings/$ $baselocation/index.html?action=eventlistings [L]
	RewriteRule ^$baselocation$ $baselocation/ [R]
	RewriteRule ^$baselocation/feed.html$ $baselocation/index.html?action=eventsfeedpage [L]
	RewriteRule ^$baselocation/([^/]+)/feed.html$ $baselocation/index.html?action=eventsfeedpage [L]
	RewriteRule ^$baselocation/feed(|unstyled).(js|xml)$ $baselocation/index.html?action=eventsfeed&type=$2&style=$1 [L]
	RewriteRule ^$baselocation/([^/]+)/feed(|unstyled).(js|xml)$ $baselocation/index.html?action=eventsfeed&type=$3&category=$1&style=$2 [L]
	RewriteRule ^$baselocation/add.html$ $baselocation/index.html?action=addevents [L]
	RewriteRule ^$baselocation/(logo|logosmall).png$ - [L]
	RewriteRule ^$baselocation/([^/]+)/$ $baselocation/index.html?action=home&item=$1 [L]
	RewriteRule ^$baselocation/([^/]+)$ $baselocation/$1/ [R]
	RewriteRule ^$baselocation/([0-9]+)/([a-z0-9-]+)/$ $baselocation/index.html?action=showevent&item=$1&urlslug=$2 [L]
	RewriteRule ^$baselocation/([0-9]+)/([a-z0-9-]+)$ $baselocation/$1/$2/ [R]
	RewriteRule ^$baselocation/([0-9]+)/([a-z0-9-]+)/(edit|clone|delete).html$ $baselocation/index.html?action=$3event&item=$1&urlslug=$2 [L]
	<LocationMatch "$baselocation/(.*)feed(|unstyled)\.(js|xml)$">
		php_admin_value auto_prepend_file none
		php_admin_value auto_append_file none
	</LocationMatch>
</Macro>

# Events@cam (native)
Use MacroEventsPortal "/events" "Events system"

*/


# Define a class for creating an online event listing system
require_once ('frontControllerApplication.php');
class eventsPortal extends frontControllerApplication
{
	# Function to assign defaults additional to the general application defaults
	public function defaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$defaults = array (
			
			# Database credentials
			'hostname' => 'localhost',
			'database' => 'eventsportal',
			'username' => 'eventsportal',
			'password' => NULL,
			'table' => 'events',
			'administrators' => 'administrators',
			'settingsTableExplodeTextarea' => true,
			
			# E-mail addresses
			'feedbackRecipient' => false,
			
			# GUI
			'div' => 'eventsportal',
			
			# Branding and configuration
			'applicationName' => 'Events',
			'applicationNameExtended' => 'Events',
			'welcomeTextHtml' => '<strong>Welcome</strong> to our event listings service!',
			'whereHappening' => '%s lists events taking place.',
			'feedDescriptionDefault' => 'Events',
			'feedDescriptionType' => '%s events',
			'headerLogo' => false,	// http://creatr.cc/creatr/ is good for creating a logo
			'logoSmall' => false,
			'faqHtml' => false,
			'feedCssPrefix' => 'eventsfeed',
			'eligibilityOptions' => "Event is open to all\nEvent is limited to members",
			
			# Images
			'imageMaxSize' => 200,
			'imageOutputFormat' => 'jpg',	// JPG/PNG seem more likely to remove transparency
			#!# Remove hard-coded /events/
			'eventsImageStoreRoot' => $_SERVER['DOCUMENT_ROOT'] . '/events' . '/images/',
			
			# Events
			'eventsOnProviderPage' => 4,
			'eventsMaxInFeed' => 20,
			
			# Auth
			'internalAuth' => false,
		);
		
		# Return the defaults
		return $defaults;
	}
	
	
	# Function to assign supported actions
	public function actions ()
	{
		# Define available tasks
		$actions = array (
			'events' => array (
				'description' => 'Events',
				'url' => 'events/',
				'heading' => false,
			),
			'addevents' => array (
				'description' => 'Add an event',
				'url' => '/events/add.html',
				'tab' => '<img src="/images/icons/add.png" alt="" class="icon"> Add an event',
				'authentication' => true,
			),
			'listorganisationevents' => array (
				'description' => 'List events',
				'usetab' => 'events',
				'heading' => false,
			),
		/* # The actual events form is always done by the hosted application
			'addevent' => array (),
		*/
			'showevent' => array (
				'description' => 'Event details',
				'usetab' => 'events',
				'heading' => false,
			),
			'editevent' => array (
				'description' => 'Edit this event',
				'usetab' => 'events',
				'authentication' => true,
				'heading' => false,
			),
			'cloneevent' => array (
				'description' => 'Clone this event',
				'usetab' => 'events',
				'authentication' => true,
				'heading' => false,
			),
			'deleteevent' => array (
				'description' => 'Delete this event',
				'usetab' => 'events',
				'authentication' => true,
				'heading' => false,
			),
			'eventlistings' => array (
				'description' => 'Listings export',
				'usetab' => 'events',
			),
			'eventsfeedpage' => array (
				'description' => 'Events feeds',
				'url' => 'feed.html',
				'usetab' => NULL,
			),
			'eventsfeed' => array (
				'description' => 'Events feed',
				'usetab' => NULL,
				'export' => true,
			),
		);
		
		# Overwrite global settings
		$this->globalActions['home']['tab'] = '<img src="/images/icons/application_view_list.png" alt="" class="icon"> Listing of all events';
		
		# Add in external provider links
		$actions += $this->providerApi->getProviderTabs ();
		
		# Return the actions
		return $actions;
	}
	
	
	# Database structure definition
	public function databaseStructure ()
	{
		return "
			CREATE TABLE IF NOT EXISTS `{$this->settings['administrators']}` (
			  `username` varchar(" . ($this->settings['internalAuth'] ? '255' : '10') . ") COLLATE utf8_unicode_ci NOT NULL,
			  `active` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y',
			  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (`username`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Administrators';
			
			/* INSERT INTO `{$this->settings['administrators']}` VALUES ('{$this->user}',  'Y',  'Administrator', '{$this->settings['administratorEmail']}'); */
			
			CREATE TABLE IF NOT EXISTS `{$this->settings['table']}` (
			  `eventId` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique key',
			  `urlSlug` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'URL part',
			  `provider` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Event provider',
			  `organisation` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Organisation',
			  `eventName` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Name of event',
			  `description` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Description of event',
			  `locationName` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Where is the event taking place?',
			  `locationLongitude` float(11,6) DEFAULT NULL COMMENT 'Longitude from map',
			  `locationLatitude` float(10,6) DEFAULT NULL COMMENT 'Latitude from map',
			  `picture` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Image',
			  `startTime` time DEFAULT NULL COMMENT 'Start time',
			  `startDate` date NOT NULL COMMENT 'Start date',
			  `endTime` time DEFAULT NULL COMMENT 'End time',
			  `endDate` date DEFAULT NULL COMMENT 'End date',
			  `recurrence` enum('Just this day/time','Event recurs at the same day/time each week during full term','Event recurs at the same day/time each week during the whole year') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Just this day/time' COMMENT 'Does the event recur each week?',
			  `eligibility` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Who can attend?',
			  `visible` enum('Yes','No') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Yes' COMMENT 'Make this event visible in listings online?',
			  `deleted` int(1) DEFAULT NULL COMMENT 'Event has been deleted?',
			  `user` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Added/updated by user',
			  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Event details last updated',
			  `submissionTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Event details original submission date/time',
			  `cost` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Cost to attend',
			  `contactInfo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Contact info (if not main organisation details)',
			  `webpageUrl` text COLLATE utf8_unicode_ci COMMENT 'URL of webpage about the event (if any)',
			  `facebookUrl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Facebook URL about the event (if any)',
			  `eventType__JOIN__" . $this->settings['database'] . "__types__reserved` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Type of event',
			  `adminBan` int(1) DEFAULT NULL COMMENT 'Admin ban for this event',
			  PRIMARY KEY (`eventId`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Events';
			
			CREATE TABLE IF NOT EXISTS `{$this->settings['settingsTable']}` (
			  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Automatic key (ignored)',
			  `feedbackRecipient` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'E-mail of feedback recipient',
			  `applicationName` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Events' COMMENT 'Brand name of the application',
			  `applicationNameExtended` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Events' COMMENT 'Brand name of the application (extended)',
			  `welcomeTextHtml` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '<strong>Welcome</strong> to our event listings service!' COMMENT 'HTML fragment for welcome text',
			  `whereHappening` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '%s lists events taking place.' COMMENT 'Phrase for where the events are happening (%s becomes application name)',
			  `feedDescriptionDefault` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Events' COMMENT 'Feed description (default)',
			  `eligibilityOptions` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Eligibility options (one per line; first will be the default)',
			  `feedDescriptionType` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '%s events' COMMENT 'Feed description (category = %s)',
			  `feedCssPrefix` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'eventsfeed' COMMENT 'CSS prefix for JS feed classes/IDs',
			  `headerLogo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Header logo (if any)',
			  `logoSmall` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Logo - small (if any)',
			  `faqHtml` text COLLATE utf8_unicode_ci COMMENT 'FAQ (block of HTML)',
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Settings';
			
			CREATE TABLE IF NOT EXISTS `types` (
			  `eventTypeId` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Internal name',
			  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Event type',
			  `ordering` tinyint(2) DEFAULT '5' COMMENT 'Ordering (10 = nearest top of list)',
			  PRIMARY KEY (`eventTypeId`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='List of event types';
		";
	}
	
	
	# Processing before actions loaded
	public function mainPreActions ()
	{
		# Explicitly set the eventsBaseUrl
		#!# Ideally remove this hard-code
		$this->eventsBaseUrl = '/events';
		
		# Get organisation providers
		require_once ('providers.php');
		$this->providerApi = new providers ();
		#!# Needs to have error handling
		$this->providers = $this->providerApi->getProviders ();
		
	}
	
	
	# Additional initialisation
	public function main ()
	{
		# Load required libraries
		require_once ('image.php');
		
	}
	
	
	# Events front page
	public function home ($eventType = false)
	{
		# Start the HTML
		$html  = '';
		
		# Get event types
		$eventTypes = $this->getEventTypes ();
		
		# Redirect the user if the jump form has been posted
		pureContent::jumplistProcessor ();
		
		# Validate event type if supplied
		if ($eventType && !array_key_exists ($eventType, $eventTypes)) {
			application::sendHeader (404);
			#!# Entities in the URL may not appear correctly; using utf8_encode (urldecode ($eventType)) fails to fix this, however
			$html .= "\n<p>You seem to have selected an invalid event type, <em>" . htmlspecialchars ($eventType) . "</em>. Please check the URL, or <a href=\"{$this->eventsBaseUrl}/\">browse the event listings</a>.</p>";
			echo $html;
			return false;
		}
		
		# Show the list including navigation links
		$html .= $this->eventsList (false, false, false, $eventType);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Administrator options
	public function admin ()
	{
		# Create the HTML
		parent::admin ();
		$html  = "\n
		<h3>Events</h3>
		<ul>
			<li><a href=\"" . $this->baseUrl . "/listings/\">Listings export</a></li>
		</ul>
		";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Events addition portal page
	public function addevents ()
	{
		# Start the HTML
		$html  = '';
		
		# Specify the organisation fields required
		$fieldsRequired = array ('logoLocation', 'organisationName', 'eventsBaseUrl', );
		
		# Get a manager's organisations or end
		#!# Need to change the parameters so that only visible & approved organisations are listed
		if (!$organisationsOfUser = $this->providerApi->getOrganisationsOfUser ($this->user, $fieldsRequired)) {
			$html .= "\n<p>You do not appear to be registered as a manager of any organisation.</p>";
			$html .= "\n<p id=\"claimform\"><strong>If you think you should be the manager for an organisation's entry</strong>, please use the manager claim form for the relevant area, or add the organisation if it is not listed already, using these links:</p>";
			$list = array ();
			foreach ($this->providers as $providerId => $provider) {
				$list[] = "<a href=\"{$provider['baseUrl']}/\"><strong>{$provider['name']}</strong></a> and its <a href=\"{$provider['baseUrl']}{$provider['managerClaimFormLocation']}\">manager claim form</a>";
			}
			$html .= application::htmlUl ($list);
			echo $html;
			return false;
		}
		
		# Compile the HTML
		$html .= "\n<p>Please select below the organisation to add the event for.";
		//$html .= "\n<p class=\"faded\">At present, only <a href=\"{$this->providers['societiesDirectory']['baseUrl']}/\">Societies</a> can add events, but other groups will be able to shortly.</p>";
		$html .= $this->organisationSelectionTable ($organisationsOfUser);
		
		# Show the HTML
		echo $html;
	}
	
	
	# General function to provide a organisation selection list
	private function organisationSelectionTable ($organisationsOfUser)
	{
		# Start the HTML
		$html  = '';
		
		# Create the table
		$links = array ();
		foreach ($organisationsOfUser as $providerId => $organisations) {
			
			# Add the title for this type of provider
			$html .= "\n<h3 class=\"selectlist\">" . htmlspecialchars ($this->providers[$providerId]['name']) . ':</h3>';
			if ($this->providers[$providerId]['managerClaimFormLocation']) {
				$html .= "\n<p>" . ($organisations ? "If you think you should be the manager for a {$this->providers[$providerId]['typeSingularUcfirst']} not listed here" : "You do not appear to be not registered as the manager for any {$this->providers[$providerId]['typeSingularUcfirst']}.<br />If you think you should be") . ", please use the <a href=\"{$this->providers[$providerId]['baseUrl']}{$this->providers[$providerId]['managerClaimFormLocation']}\">{$this->providers[$providerId]['typeSingularUcfirst']} manager claim form</a>.</p>";
			} else {
				$html .= "\n<p>You do not appear to be not registered as the manager for any {$this->providers[$providerId]['typeSingularUcfirst']}.</p>";
			}
			
			# Add each organisation
			$links = array ();
			foreach ($organisations as $organisationId => $organisation) {
				$linkStart = "<a href=\"{$organisation['eventsBaseUrl']}/add.html\">";
				$links[$organisationId][] = ($organisation['logoLocation'] ? $linkStart . image::imgTag ($organisation['logoLocation'], $organisation['organisationName'], 'right') . '</a>' : '');
				$links[$organisationId][] = "<h4>{$linkStart}" . htmlspecialchars ($organisation['organisationName']) . '</a></h4>';
			}
			
			# Compile the HTML
			$html .= application::htmlTable ($links, false, $class = 'selectlist spaced lines', $showKey = false, $uppercaseHeadings = false, $allowHtml = true);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Instruction page for feeds
	public function eventsfeedpage ($organisationId = false, $withFormatting = true)
	{
		# Determine the base link
		$baseLink = $_SERVER['_SITE_URL'] . dirname ($_SERVER['REQUEST_URI']) . '/';
		
		# Give instructions
		$html  = '';
		$html .= "\n<p>To embed this events listing in your own webpage, just place the one-line HTML snippets below at the place you want it to appear. In all cases, you can use CSS to style things exactly how you want.</p>";
		$html .= "\n<ul>\n\t<li><a href=\"#styled\">Styled listing</a></li>\n\t<li><a href=\"#unstyled\">Unstyled listing</a></li>\n\t<li><a href=\"#rss\">RSS 2.0 (XML) feed</a></li>\n</ul>";
		$html .= "\n<br />";
		$html .= "\n\n<h3 id=\"styled\">Styled listing</h3>";
		$html .= "\n<p>Paste this HTML into your page where you want the listing to appear:</p>";
		$html .= "\n<div class=\"graybox\">" . htmlspecialchars ('<script type="text/javascript" src="' . $baseLink . 'feed.js"></script>');
		$html .= nl2br (htmlspecialchars ("\n
<!-- Suggested CSS styles -->
<style type=\"text/css\">
	#{$this->settings['feedCssPrefix']} {padding: 7px;}
	#{$this->settings['feedCssPrefix']} p.{$this->settings['feedCssPrefix']}image {padding: 0;}
	#{$this->settings['feedCssPrefix']} p.{$this->settings['feedCssPrefix']}image a {border: 0;}
	#{$this->settings['feedCssPrefix']} h4 {margin: 8px 0 0; padding: 0; line-height: 1.3em;}
	#{$this->settings['feedCssPrefix']} h4 a {color: #005bb4;}
	#{$this->settings['feedCssPrefix']} p {margin: 0; padding: 4px 0 0; color: #999; font-size: 0.9em; line-height: 1.1em;}
	#{$this->settings['feedCssPrefix']} em {font-style: normal;}
	#{$this->settings['feedCssPrefix']} p.{$this->settings['feedCssPrefix']}tagline {padding-top: 1.5em; color: #333;}
</style>"));
		$html .= "\n</div>";
		$html .= "\n<p>which looks like this:</p>";
		$html .= "\n" . '<script language="javascript" src="' . $baseLink . 'feed.js"></script>';
		$html .= "\n\n<h3 id=\"unstyled\">Unstyled listing</h3>";
		$html .= "\n<p>Paste this HTML into your page where you want the listing to appear:</p>";
		$html .= "\n<div class=\"graybox\">" . htmlspecialchars ('<script type="text/javascript" src="' . $baseLink . 'feedunstyled.js"></script>') . '</div>';
		$html .= "\n<p>which looks like this:</p>";
		$html .= "\n" . '<script language="javascript" src="' . $baseLink . 'feedunstyled.js"></script>';
		$html .= "\n\n<h3 id=\"rss\">RSS 2.0 (XML) feed</h3>";
		$html .= "\n<p>An RSS2.0 XML feed is also available for those with programming knowledge. The URL to use is:</p>";
		$html .= "\n<div class=\"graybox\"><a href=\"{$baseLink}feed.xml\">{$baseLink}feed.xml</a></div>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Feeds (RSS/JS)
	public function eventsfeed ($providerId = false, $organisationId = false)
	{
		# End if no format supplied
		if (!isSet ($_GET['type'])) {return false;}
		
		# Ensure the format is supported
		$supportedFormats = array ('xml', 'js');
		if (!in_array ($_GET['type'], $supportedFormats)) {return false;}
		
		# Get the organisation name; if the supplied category (e.g. 'arts') or organisation ID is validated, or throw a 404 if it is not found
		$feedDescription = $this->settings['feedDescriptionDefault'];	// Default
		if (isSet ($_GET['category'])) {
			$eventTypes = $this->getEventTypes ();
			if (!isSet ($eventTypes[$_GET['category']])) {
				application::sendHeader (404);
				return false;
			}
			$feedDescription = sprintf ($this->settings['feedDescriptionType'], $eventTypes[$_GET['category']]);
		} else {
			if ($organisationId) {
				if (!$data = $this->providerApi->getOrganisationDetails ($providerId, $organisationId)) {
					application::sendHeader (404);
					return false;
				}
				$feedDescription = $data['organisationName'];
			}
		}
		
		# Get the events
		$eventType = (isSet ($_GET['category']) ? $_GET['category'] : false);
		$eventData = $this->getEvents ($providerId = false, $organisationId, $eventId = false, $eventType);
		
		# Limit to maximum number of events
		$events = array ();
		$counter = 0;
		foreach ($eventData as $event => $attributes) {
			if ($counter == $this->settings['eventsMaxInFeed']) {break;}
			$counter++;
			$events[$event] = $attributes;
		}
		
		# Add in a fake 'add your event' event entry if not an organisation
		if (!$organisationId) {
			$events[] = array (
				'eventId' => false,
				'lastUpdated' => time (),	// Now
				'urlSlug' => false,
				'eventName' => 'Add your event ' . chr(226).chr(128).chr(166),	// ... character (&hellip;), but as pure Unicode
				'organisationNameUnabbreviated' => false,
				'startDateFormatted' => false,
				'timeCompiledBrief' => false,
				'datetime' => false,
				'description' => 'Add your event ...',
				"eventType__JOIN__{$this->settings['database']}__types__reserved" => '',
			);
		}
		
		# Make entity-safe
		foreach ($events as $event => $attributes) {
			foreach ($attributes as $key => $value) {
				$events[$event][$key] = str_replace (array ("\r\n", "\n"), array ("\n", '<br />'), htmlspecialchars (trim ($value)));
			}
		}
		
		# Define which function to use
		$function = 'eventsfeed' . $_GET['type'];
		
		# Return the data
		return $this->$function ($events, $organisationId, $feedDescription);
	}
	
	
	# RSS feeds; RSS spec at http://cyber.law.harvard.edu/rss/rss.html
	private function eventsfeedxml ($events, $organisationId = false, $feedDescription)
	{
		# Send the correct header
		header ('Content-Type: application/xml; charset=utf-8');
		
		# Build the XML
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= "\n" . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
		$xml .= "\n" . '<channel>';
		$xml .= "\n\t" . '<title>' . htmlspecialchars ($this->settings['applicationNameExtended']) . '</title>';
		$xml .= "\n\t" . '<link>' . "http://{$_SERVER['SERVER_NAME']}{$this->baseUrl}/" . '</link>';
		$xml .= "\n\t" . '<atom:link href="' . $_SERVER['_PAGE_URL'] . '" rel="self" type="application/rss+xml" />';
		$xml .= "\n\t" . '<description>' . htmlspecialchars ($feedDescription) . '</description>';
		$xml .= "\n\t" . '<language>en-gb</language>';
		$xml .= "\n\t" . "<image>\n\t\t<url>http://{$_SERVER['SERVER_NAME']}{$this->eventsBaseUrl}/logo.png</url>\n\t\t<title>" . htmlspecialchars ($this->settings['applicationNameExtended']) . "</title>\n\t\t<link>http://{$_SERVER['SERVER_NAME']}{$this->baseUrl}/</link>\n\t</image>";
		$xml .= "\n";
		
		# Add the items
		foreach ($events as $key => $event) {
			
			# Pre-format certain fields
			if (!$event['eventId'] && !$event['urlSlug']) {
				$link = "http://{$_SERVER['SERVER_NAME']}{$this->eventsBaseUrl}/add.html";	// Special case
			} else {
				$link = "http://{$_SERVER['SERVER_NAME']}{$this->eventsBaseUrl}/{$event['eventId']}/{$event['urlSlug']}/";
			}
			$date = date ('r', $event['lastUpdated']);
			
			# Create the XML
			$xml .= "\n\t<item>";
			$xml .= "\n\t\t" . "<title>{$event['eventName']}" . ((!$organisationId && $event['organisationNameUnabbreviated']) ? " ({$event['organisationNameUnabbreviated']})" : '') . rtrim ($event['startDateFormatted'] ? " - {$event['startDateFormatted']} {$event['timeCompiledBrief']}" : '') . '</title>';
			$xml .= "\n\t\t" . "<link>{$link}</link>";
			$xml .= "\n\t\t" . "<description><![CDATA[{$event['description']}]]></description>";
			//$xml .= "\n\t\t" . "<author>{$event['organisationNameUnabbreviated']}</author>";
			$xml .= "\n\t\t" . '<category>' . $event["eventType__JOIN__{$this->settings['database']}__types__reserved"] . '</category>';
			$xml .= "\n\t\t" . "<guid>{$link}</guid>";
			//$xml .= "\n\t\t" . "<pubDate>{$date}</pubDate>";
			$xml .= "\n\t</item>";
		}
		
		# Complete the RSS feed
		$xml .= "\n" . '</channel>';
		$xml .= "\n" . '</rss>';
		
		# Output the XML
		echo $xml;
	}
	
	
	
	# Javascript embeddable page
	private function eventsfeedjs ($events, $organisationId = false, $feedDescription)
	{
		# Determine whether to add style
		$styled = true;
		if (isSet ($_GET['style'])) {
			switch ($_GET['style']) {
				case 'unstyled':
					$styled = false;
					break;
				// More stylings to go here
			}
		}
		
		# Start the listing
		$html  = "\n" . '<div' . ($styled ? ' style="width: 150px; font-family: verdana, arial, sans-serif; border: 1px solid #ddd; padding: 5px 7px; margin: 0 10px 10px 0; background-color: #fcfcfc;"' : '') . " id=\"{$this->settings['feedCssPrefix']}\" class=\"{$this->settings['feedCssPrefix']}\">";
		$html .= "\n\t" . "<p" . ($styled ? ' style="padding-left: 0; margin-left: 0; text-align: left;"' : '') . " id=\"{$this->settings['feedCssPrefix']}image\" class=\"{$this->settings['feedCssPrefix']}image\"><a" . ($styled ? ' style="border: 0;"' : '') . " href=\"http://{$_SERVER['SERVER_NAME']}{$this->baseUrl}/\">";
		if ($this->settings['logoSmall']) {
			$html .= image::imgTag ($this->eventsBaseUrl . $this->settings['logoSmall'], $this->settings['applicationNameExtended'], false, $preventCaching = false, $includeServerName = true, $border = 0);
		} else {
			$html .= htmlspecialchars ($this->settings['applicationNameExtended']);
		}
		$html .= '</a></p>';
		
		# Add the items
		foreach ($events as $key => $event) {
			
			# Pre-construct the link
			if (!$event['eventId'] && !$event['urlSlug']) {
				$link = "http://{$_SERVER['SERVER_NAME']}{$this->eventsBaseUrl}/add.html";	// Special case
			} else {
				$link = "http://{$_SERVER['SERVER_NAME']}{$this->eventsBaseUrl}/{$event['eventId']}/{$event['urlSlug']}/";
			}
			
			# Convert the datetime
			$datetime = str_replace (htmlspecialchars ('<br />'), '<br />', $event['datetime']);
			$datetime = str_replace (array ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), array ('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'), $datetime);
			$datetime = str_replace (array ('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'), array ('Jan', 'Febr', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'), $datetime);
			
			# Create the HTML for each item
			$html .= "\n\t<div" . ($styled ? ' style=""' : '') . " id=\"{$this->settings['feedCssPrefix']}item{$key}\" class=\"{$this->settings['feedCssPrefix']}item\">";
			$html .= "\n\t\t" . "<h4" . ($styled ? ' style="color: black; margin-top: 1em; font-size: 1em; font-weight: bold; border-bottom: 1px solid #eee; margin-bottom: 4px;"' : '') . " class=\"{$this->settings['feedCssPrefix']}itemheading\"><a href=\"{$link}\">{$event['eventName']}" . /* ($organisationId ? '' : " ({$event['organisationNameUnabbreviated']})") . */ '</a></h4>';
			if ($datetime) {
				$html .= "\n\t\t" . "<p" . ($styled ? ' style="font-size: 0.9em; line-height: 1.1em; padding-left: 0; margin-left: 0; color: #888; margin-top: 0px; margin-bottom: 1px;"' : '') . " class=\"{$this->settings['feedCssPrefix']}itemdatetime\"><em>" . $datetime . '</em></p>';
			}
			if ($organisationId) {$html .= "\n\t\t" . "<p" . ($styled ? ' style="font-size: 0.83em; padding-left: 0; margin-left: 0; color: #888; margin-top: 1px; margin-bottom: 4px;"' : '') . " class=\"{$this->settings['feedCssPrefix']}itemlocation\"><em>{$event['locationName']}</em></p>";}
			if ($organisationId) {$html .= "\n\t\t" . "<p" . ($styled ? ' style="margin-top: 2px;"' : '') . " class=\"{$this->settings['feedCssPrefix']}itemdescription\">{$event['description']}</p>";}
			$html .= "\n\t</div>";
		}
		
		# Complete the feed
		$html .= "\n\t" . "<p" . ($styled ? ' style="border-top: 1px solid #eee; padding-top: 1em;"' : '') . " id=\"{$this->settings['feedCssPrefix']}tagline\" class=\"{$this->settings['feedCssPrefix']}tagline\"><em>Events listing powered by <a href=\"http://{$_SERVER['SERVER_NAME']}{$this->baseUrl}/\">" . htmlspecialchars ($this->settings['applicationNameExtended']) . "</a></em></p>";
		$html .= "\n" . '</div>';
		
		# Convert to javascript
		$js = str_replace ("'", "\'", $html);
		$js = str_replace ("\n", "' + \"" . '\n' . "\" + '", $js);
		$js = "document.write('" . $js . "');";
		
		# Output the Javascript
		echo $js;
	}
	
	
	# Feedback page
	public function feedback ()
	{
		# Add a box if required
		if ($this->settings['faqHtml']) {
			#!# Fix typo - both the third-party library filename and the code in it need to be fixed
			/*	Currently disabled - is clearer with the FAQs expanded
			if (substr_count ($this->settings['faqHtml'], 'collapsable')) {
				// echo "\n" . '<script type="text/javascript" src="/sitetech/collapsable.js"></script>';
			}
			*/
			echo "\n<div class=\"graybox\">";
			echo "\n<h3>Common queries:</h3>";
			echo $this->settings['faqHtml'];
			echo "\n</div>";
		}
		
		# Add the rest of the feedback page
		parent::feedback ();
	}
	
	
	# General function to check whether the user has rights to this organisation
	private function userIsManager ($providerId, $organisationId)
	{
		# If allowing admins to edit, return true if they are an administrator
		if ($this->userIsAdministrator) {return true;}
		
		# Hand off to the providers infrastructure
		return $this->providerApi->userIsManager ($providerId, $organisationId, $this->user);
	}
	
	
	# Organisation-specific event addition, always called by the host application
	public function addevent ($providerId, $organisation, $headingLevel = 2)
	{
		# Hand off to the event manipulation
		return $this->manipulate ($action = str_replace ('event', '', __FUNCTION__), $eventId = NULL, $providerId, $organisation, $headingLevel);
	}
	
	
	# Function to edit an event
	public function editevent ($eventId)
	{
		# Hand off to the event manipulation
		return $this->manipulate ($action = str_replace ('event', '', __FUNCTION__), $eventId);
	}
	
	
	# Function to delete an event
	public function deleteevent ($eventId)
	{
		# Hand off to the event manipulation
		return $this->manipulate ($action = str_replace ('event', '', __FUNCTION__), $eventId);
	}
	
	
	# Function to clone an event
	public function cloneevent ($eventId)
	{
		# Hand off to the event manipulation
		return $this->manipulate ($action = str_replace ('event', '', __FUNCTION__), $eventId);
	}
	
	
	# Function to list a specific organisation's upcoming events
	public function showevent ($eventId)
	{
		# Hand off to the event manipulation
		return $this->manipulate ($action = str_replace ('event', '', __FUNCTION__), $eventId);
	}
	
	
	# Organisation-specific event addition
	#!# This is very long and needs refactoring into a "validate and get details" function that the above loaders can call
	private function manipulate ($action = 'show', $eventId = NULL, $providerId = NULL, $organisation = NULL, $headingLevel = 2)
	{
		# Start the HTML
		$html  = '';
		
		# Determine available actions against database actions
		$actions = array (
			'add' => 'insert',		// This is the only one that will have the providerId and organisation being supplied
			'show' => 'show',		// All these do not have an provider/organisation being supplied, so those must be derived from the event details
			'edit' => 'update',
			'delete' => 'delete',
			'clone' => 'insert',
		);
		
		# Validate action (in case the query string has been entered manually rather than through mod_rewrite)
		if (!isSet ($actions[$action])) {return false;}
		
		# Cache the original event ID for later use
		$originalEventId = $eventId;
		
		# If not adding, get the event data and the organisationId
		if ($action == 'add') {	// i.e. there is a $providerId and an $organisation
			$data = array ();
			$organisationId = $organisation['id'];
		} else {
			if (!$data = ($this->getEvents (false, false, $eventId, false, $forthcomingOnly = (!$eventId), $allowHidden = ($action != 'show')))) {
				#!# This stage should be supressed for ones being hidden
				#!# Throw 404
				$html .= "\n<p>There is no such event; perhaps you followed an incorrect link or mistyped the URL?</p>";
				echo $html;
				return false;
			}
			
			# Assign the providerId and organisationId
			$providerId		= $data['provider'];
			$organisationId	= $data['organisation'];
			
			# Validate the organisation and get its details (which will not have entity conversion done); this will be empty if no events are wanted
			if (!$organisation = $this->providerApi->getOrganisationDetails ($providerId, $organisationId)) {
			#!# Needs to report there is no such organisation or they have events switched off
				return false;
			}
		}
		
		# Determine whether the user is a manager of this organisation, using the providers infrastructure
#!# userIsManager should be an additional check but effectively an assert as the caller should not allow eventsPortal::addevent (and thus eventsPortal::manipulate) to be called
#!# Refactor userIsManager to use $organisation rather than $organisation['id']
		$userIsManager = $this->userIsManager ($providerId, $organisation['id']);
		
		# Check that the user has rights to this organisation or that they are an administrator
		if ($action != 'show') {
			if (!$userIsManager) {
				$provider = $this->providers[$providerId];
				echo "\n<p>You are not a manager of this {$organisation['typeFormatted']}'s entry and so cannot make changes to it. If you think you should be, please use the <a rel=\"nofollow\" href=\"{$provider['baseUrl']}{$provider['managerClaimFormLocation']}?organisation=" . htmlspecialchars ($organisation['id']) . "\">manager claim form</a>.</p>";
				return false;
			}
		}
		
		# Check that the organisation wants events listings, if that setting exists
		if (isSet ($organisation['events']) && !$organisation['events']) {
			#!# Send a 404 header?
			$html .= "\n<p>The <a href=\"{$organisation['profileBaseUrl']}/update.html?events#events\">settings for your " . htmlspecialchars ($organisation['typeFormatted']) . "</a> currently have event listings switched off. Please <a href=\"{$organisation['profileBaseUrl']}/update.html?events#events\"><strong>update the events setting</strong></a> to enable adding events.</p>";
			echo $html;
			return false;
		}
		
		# Introduce the webform
		if ($action == 'edit' || $action == 'add' || $action == 'clone') {
			$html .= "\n<h{$headingLevel} id=\"sectionheading\">" . ucfirst ($action) . " an event for {$organisation['typeFormatted']}: <a href=\"{$organisation['profileBaseUrl']}/\">" . htmlspecialchars ($organisation['organisationName']) . "</a></h{$headingLevel}>";
			//if ($data['visible'] == 'Yes') {
				#!# This is still being visible after editing
				#!# /events/<id>/<eventslug>/edit.html gives two 'Cancel editing' buttons, one returning to /<hostapplication>/events/<id>/<eventslug>/ , and the other to /events/<id>/<eventslug>/
				$html .= "\n\n<ul class=\"actions noprint\">
						<li>" . ($action == 'edit' ? 'Edit' : 'Add') . " the event details below or:</li>
						<li><a href=\"{$organisation['profileBaseUrl']}/" . ($eventId ? "{$eventId}/{$data['urlSlug']}/" : '') . "\"><img src=\"/images/icons/cross.png\" class=\"icon\" alt=\"*\" /> " . ($eventId ? ($action == 'clone' ? 'Cancel cloning' : 'Cancel editing') : 'Cancel and return to main profile page') . "</a></li>
						<li><a href=\"{$this->eventsBaseUrl}/" . ($eventId ? "{$eventId}/{$data['urlSlug']}/" : '') . "\"><img src=\"/images/icons/cross.png\" class=\"icon\" alt=\"*\" /> " . ($eventId ? ($action == 'clone' ? 'Cancel cloning' : 'Cancel editing') : 'Cancel and return to ' . $this->settings['applicationName']) . "</a></li>
					</ul>";
			//}
		}
		
		# Show non-visibility message
		if ($action != 'add') {
			if ($data['visible'] == 'No') {
				$html .= "\n<p class=\"comment\"><em><strong>Note</strong>: The event below is currently not visible in listings.</em></p>";
			}
		}
		
		# Show edit links if they have rights
		if ($action == 'show') {
			if ($userIsManager) {
				$html .= "
				<ul id=\"eventformeditactions\" class=\"actions noprint\">
					<li><h4>" . ($this->userIsAdministrator ? 'Administrator' : 'Manager') . " options:</h4></li>
					<li><a href=\"{$this->eventsBaseUrl}/{$eventId}/{$data['urlSlug']}/edit.html\"><img src=\"/images/icons/pencil.png\" alt=\"*\" /> Edit event details</a></li>
					<li class=\"spaced\"><a href=\"{$this->eventsBaseUrl}/{$eventId}/{$data['urlSlug']}/delete.html\"><img src=\"/images/icons/cross.png\" alt=\"*\" /> Delete the event</a></li>
					<li><a href=\"{$this->eventsBaseUrl}/{$eventId}/{$data['urlSlug']}/clone.html\"><img src=\"/images/icons/application_double.png\" class=\"icon\" alt=\"*\" /> Clone to a similar event</a></li>
					<li class=\"spaced\"><a href=\"{$organisation['eventsBaseUrl']}/add.html\"><img src=\"/images/icons/add.png\" class=\"icon\" alt=\"*\" /> Add a new event</a></li>
					<li><a href=\"{$organisation['profileBaseUrl']}/#events\"><img src=\"/images/icons/application_view_list.png\" class=\"icon\" alt=\"*\" /> Return to {$organisation['typeFormatted']}'s events list</a></li>
					<li><a href=\"{$this->eventsBaseUrl}/\"><img src=\"/images/icons/application_view_list.png\" class=\"icon\" alt=\"*\" /> Return to full events list</a></li>
				</ul>";
			}
			
			# Show the details
			$html .= $this->eventHtml ($data, $organisation['id'], $organisation['organisationName']);
			
			# Add a contact form if relevant
			#!# This all feels very messy and over-complex
			$email = ($data['contactInfo'] && application::validEmail ($data['contactInfo']) ? $data['contactInfo'] : ((isSet ($organisation['emailVisible']) && $organisation['emailVisible']) ? $organisation['emailVisible'] : false));
			if ($email && !$data['isRetrospective']) {
				$html .= $this->addMailForm ($providerId, $email, $organisation['organisationName'], $organisation['timestamp'], $eventSpecificContact = $data['contactInfo'], $eventMode = true, $subject = $data['eventName']);
			}
			
			# End here
			echo $html;
			return true;
		}
		
		# If deleting, confirm
		if ($action == 'delete') {
			
			# Deletion form
			require_once ('ultimateForm.php');
			$form = new form (array (
				'developmentEnvironment' => ini_get ('display_errors'),
				'formCompleteText' => false,
				'databaseConnection' => $this->databaseConnection,
				'displayRestrictions' => false,
			));
			$form->select (array (
				'name' => 'confirm',
				'title' => 'Are you sure you want to delete the event below?',
				'values' => array ('', $yes = 'Yes, delete this event', 'No'),
				'required' => true,
				'nullText' => '',
			));
			$result = $form->process ($html);
			if (!$result) {$html .= $this->eventHtml ($data, $organisation['id'], $organisation['organisationName']);}
			
			if ($result) {
				# Check status
				if ($result['confirm'] != $yes) {
					$html .= "\n<p>The <a href=\"{$this->baseUrl}/{$eventId}/{$data['urlSlug']}/\">event</a> has <strong>not</strong> been deleted.</p>";
				} else {
					
					# Delete the event
					if (!$this->databaseConnection->update ($this->settings['database'], 'events', array ('deleted' => 1), array ('eventId' => $eventId))) {
						$html .= "\n<p>There was a technical problem deleting the event. This has been reported to the Webmaster.</p>";
						#!# Report to webmaster
					} else {
						
						# Confirm success
						$html .= "\n<p>The event has now been deleted.</p>\n<p>You may wish to <a href=\"{$organisation['eventsBaseUrl']}/add.html\">add a new event</a> or <a href=\"{$organisation['eventsBaseUrl']}/\">return to the {$organisation['typeFormatted']}'s main events listing</a>.</p>";
					}
				}
			}
			
			# Show the HTML
			echo $html;
			
			# End here
			return;
		}
		
		# Determine the picture message
		$pictureAlready = ($data && file_exists ($this->settings['eventsImageStoreRoot'] . $data['eventId'] . '.' . $this->settings['imageOutputFormat']));
		$pictureMessage = 'You can upload an image if you wish. It will be automatically resized where necessary.';
		if (($action != 'add') && $pictureAlready) {
			$pictureMessage = 'You can upload a different image if you wish; otherwise the original image will be used. Any new image will be automatically resized where necessary.';
		}
		
		# Event form, binded against the database structure
		require_once ('ultimateForm.php');
		$form = new form (array (
			'developmentEnvironment' => ini_get ('display_errors'),
			'formCompleteText' => false,
			'div' => 'graybox lines',
			'databaseConnection' => $this->databaseConnection,
			'displayRestrictions' => false,
			'autofocus' => true,
		));
		$dataBindingAttributes = array (
			'eventName' => array ('disallow' => array ("^([-A-Z[:space:]\*\"\']+)$" => 'In the title, please type out non-acronyms in normal sentence case rather than in ALL CAPS'), ),	// This regexp is not foolproof but will catch most
			'locationName' => array ('heading' => array ('3' => 'Where and when?'), ),
			/*
			'locationLongitude' => array ('type' => 'hidden', 'values' => array ('locationLongitude' => 0), ),
			'locationLatitude' => array ('type' => 'hidden', 'values' => array ('locationLatitude' => 0), ),
			'recurrence' => array ('editable' => false, 'default' => 'Just this day/time', ),
			*/
			'webpageUrl' => array ('type' => 'input', 'regexpi' => '(http|https)://', 'placeholder' => 'http://...', ),
			'facebookUrl' => array ('regexpi' => '(http|https)://', 'placeholder' => 'http://...', ),
			'description' => array ('cols' => 60, 'rows' => 4, ),
			"eventType__JOIN__{$this->settings['database']}__types__reserved" => array ('type' => 'select', 'values' => $this->getEventTypes (), ),
			'startDate' => array ('picker' => true, 'default' => ($data ? $data['startDate'] : date ('Y') . '0000'), ),
			//'endDate' => array ('picker' => true, ),
			'contactInfo' => array ('heading' => array ('3' => 'Who people can contact for more details'), 'title' => 'Contact' . ($organisation['typeFormatted'] ? ' (if not main ' . $organisation['typeFormatted'] . ' details)' : '') . ', e.g. e-mail address'),
			'eligibility' => array ('heading' => array ('3' => 'Other details'), 'type' => 'select', 'values' => $this->settings['eligibilityOptions']),
			'picture' => array ('heading' => array ('3' => 'Image/picture/logo (if any)', 'p' => $pictureMessage, ), 'type' => 'upload', 'directory' => $imageDirectory = $this->settings['eventsImageStoreRoot'], 'flatten' => true, ),
		);
		if (!$data) {
			$dataBindingAttributes['eligibility']['default'] = $this->settings['eligibilityOptions'][0];
		}
		$form->dataBinding (array (
			'database' => $this->settings['database'],
			'table' => $this->settings['table'],
			'data' => ($data ? $data : array ()),
			#!# visible is a misfeature
			#!# recurrence,locationLongitude,locationLatitude all not yet supported - need to be removed from database structure until they are
			'exclude' => array ('eventId', 'urlSlug', 'provider', 'organisation', 'user', 'lastUpdated', 'submissionTime', 'adminBan', 'deleted', 'visible', 'recurrence', 'locationLongitude', 'locationLatitude', 'endDate', ),
			'attributes' => $dataBindingAttributes,
			#!# Need to reorder fields in database table
			'ordering' => array ('eventName', 'description', "eventType__JOIN__{$this->settings['database']}__types__reserved", 'locationName', 'startDate', 'startTime', 'endDate', 'endTime', 'contactInfo', 'eligibility', 'cost', 'webpageUrl', 'facebookUrl', 'picture', ),
		));
		
		# Add sanity-checking constraints
		#!# This could be generalised and moved into the form somehow, or to timedate.php
		if ($unfinalisedData = $form->getUnfinalisedData ()) {
			
			# Compile the start/end date/time
			$startDate = (int) str_replace ('-', '', $unfinalisedData['startDate']);
			$startTime = (int) str_replace (':', '', $unfinalisedData['startTime']);
			$endDate = $startDate;	// Multi-day support not currently enabled
			$endTime = (int) str_replace (':', '', $unfinalisedData['endTime']);
			
			# If there is an end date, and days are different, ensure the dates are in order
			if ($endDate && ($endDate < $startDate)) {
				$form->registerProblem ('dateMismatch', 'The end date cannot be before the start date.');
			}
			
			# If the days are the same, ensure the times are in order
			if (($startDate == $endDate) && $endTime && ($endTime <= $startTime)) {
				$form->registerProblem ('timeMismatch', 'The end time must be after the start time.');
			}
			
			# If an end time is present, ensure there is an end date
			if ($endTime && !$endDate) {
				$form->registerProblem ('endDateMissing', "If entering an end time, please enter an end date also.");
			}
		}
		
		# Process the form or end
		if (!$result = $form->process ($html)) {
			echo $html;
			return false;
		}
		
		# Add in the automatic data
		$result += array (
#!# Provider needs to be presupplied into the entered data
			// 'eventId',	// This is added automatically by the database
			'provider' => $providerId,
			'organisation' => $organisationId,
			'user' => $this->user,
			// 'lastUpdated',	// This is added automatically by the database
			'submissionTime' => 'NOW()',	// This is treated as a special string by the database.php wrapper library
			// 'adminBan',	// No need to add this
		);
		
		/*
		# Flatten hierarchical data
		$result['locationLongitude'] = $result['locationLongitude']['locationLongitude'];
		$result['locationLatitude'] = $result['locationLatitude']['locationLatitude'];
		*/
		
		# Ensure MySQL compatibility with NULL values
		if (!$result['startTime']) {$result['startTime'] = NULL;}
		if (!$result['endTime']) {$result['endTime'] = NULL;}
		$result['endDate'] = $result['startDate'];
		//if (!$result['deleted']) {$result['deleted'] = NULL;}
		
		# Discard the image, first caching its name
		$imageName = $result['picture'];
		unset ($result['picture']);
		
		# Generate the URL slug; note this is deliberately only done during addition to ensure stable URLs; changes require deletion and re-creation of the event
		if (($action == 'add') || ($action == 'clone')) {
			$result['urlSlug'] = application::createUrlSlug ($result['eventName']);
		}
		
		# Insert the data into the database or report error
		$do = $actions[$action];
		$conditions = ($do == 'update' ? array ('eventId' => $eventId) : NULL);
		if ($action == 'clone') {
			$do = 'insert';
			$eventId = false;
		}
		if (!$this->databaseConnection->{$do} ($this->settings['database'], $this->settings['table'], $result, $conditions)) {
			$descriptions = array (
				'add' => 'adding the new event',
				'edit' => 'editing the event',
				'delete' => 'deleting the event',
				'clone' => 'creating the new, cloned event',
			);
			$html .= "\n<p>Apologies - there was a technical problem {$descriptions[$action]}. This problem has been reported to the Webmaster. Please kindly try again later.</p>";
			echo $html;
			$error = $this->databaseConnection->error ();
			application::utf8mail ($this->settings['administratorEmail'], $this->settings['applicationName'] . ' problem', 'The database connection reported: ' . print_r ($error, 1), "From: {$this->settings['administratorEmail']}");
			return false;
		}
		
		/*
		# Insert the recurrence data
		#!# Recurrence data insert here
		*/
		
		# Get the ID of the inserted event and compute the URL of it online
		$eventId = ($eventId ? $eventId : $this->databaseConnection->getLatestId ());
		$urlSlug = ($action == 'edit' ? $data['urlSlug'] : $result['urlSlug']);
		$location = "{$this->eventsBaseUrl}/{$eventId}/{$urlSlug}/";
		$url = $_SERVER['_SITE_URL'] . $location;
		
		# If an image has been uploaded, resize it if the size/format is wrong and convert to output format
		image::resizeAndReformat ($imageName, $this->settings['eventsImageStoreRoot'], "{$eventId}.{$this->settings['imageOutputFormat']}", $this->settings['imageMaxSize'], $this->settings['imageOutputFormat']);
		
		# If cloning, copy any image that exists, but prefer an uploaded image
		if ($action != 'add') {
			if (!$imageName) {
				if (file_exists ($file = $this->settings['eventsImageStoreRoot'] . $originalEventId . '.' . $this->settings['imageOutputFormat'])) {
					if (!copy ($file, $this->settings['eventsImageStoreRoot'] . $eventId . '.' . $this->settings['imageOutputFormat'])) {
						#!# Inform webmaster
					}
				}
			}
		}
		
		# Take the user to the event or create a link in case direct redirection fails
		application::sendHeader (302, $url);
		$html .= "\n<p>The event has been successfully " . ($action == 'edit' ? 'updated' : 'added') . ". <a href=\"{$location}\">View it now.</a></p>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get event types
	private function getEventTypes ()
	{
		# Get the data
		$data = $this->databaseConnection->select ($this->settings['database'], 'types', $conditions = array (), $columns = array ('eventTypeId', 'name'), $associative = true, $orderBy = 'ordering,name');
		
		# Rearrange the data
		$eventTypes = array ();
		foreach ($data as $key => $eventType) {
			$eventTypes[$key] = $eventType['name'];
		}
		
		# Return the data
		return $eventTypes;
	}
	
	
	# Function to produce a listings export
	public function eventlistings ()
	{
		# Get the event data
		if (!$data = $this->getEvents ($providerId = false, $organisationId = false, $eventId = false, $eventType = false, $forthcomingOnly = true, $allowHidden = false, $ensureNotDeleted = true)) {
			$html  = "\n<p>There are no forthcoming events registered.</p>";
			echo $html;
			return false;
		}
		
		# Reformat and exclude unwanted $fields
		$fields = array (
			'startDateFormatted' => 'Date',
			'startTimeFormatted' => 'Time',
			'endDateFormatted' => 'End date',
			'endTimeFormatted' => 'End time',
			'eventName' => 'Event name',
			'description' => 'Description',
			'organisationName' => 'Group',
			'eventTypeFormatted' => 'Event type',
			'locationName' => 'Location',
			'eligibility' => 'Open to?',
			'cost' => 'Cost',
		);
		$events = array ();
		foreach ($data as $eventId => $event) {
			$events[$eventId]['Info page'] = "<a href=\"{$this->eventsBaseUrl}/{$eventId}/{$event['urlSlug']}/\">[Info]</a>";
			foreach ($fields as $field => $description) {
				$cell = htmlspecialchars ($data[$eventId][$field]);
				$events[$eventId][$description] = (($field == 'description') ? nl2br ($cell) : $cell);
			}
		}
		
		# Compile the HTML
		$html  = "\n<p class=\"comment\">This is a exported table of listings. This can be copied and pasted into a spreadsheet.</p>\n<hr /><br />";
		$html .= "\n<p class=\"faded\">Copy from after this point</p>\n";
		$html .= "\n<p><strong>Listings</strong> - submit yours at <strong>{$_SERVER['SERVER_NAME']}{$this->eventsBaseUrl}</strong></p>";
		$html .= application::htmlTable ($events, array (), 'border lines', $showKey = false, false, $allowHtml = true);
		$html .= "\n<p class=\"faded\">Copy until this point</p>\n";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to list upcoming events
	private function eventsList ($providerId, $organisation = false, $organisationStandalonePage = false, $eventType = false)
	{
		# Start the HTML
		$html  = '';
		
		# End if event listings are not wanted
		if ($organisation && isSet ($organisation['events']) && $organisation['events'] != 'Yes') {
			if ($organisationStandalonePage) {
				application::sendHeader (404);
				echo "\n<p>The {$organisation['typeFormatted']} (<a href=\"{$organisation['profileBaseUrl']}/\">{$organisation['organisationName']}</a>) has switched off event listings in their settings, so none are listed here.</p>";
			}
			return false;
		}
		
		# Get the event types
		$eventTypes = $this->getEventTypes ();
		
		# Get the event data
		$data = $this->getEvents ($providerId, ($organisation ? $organisation['id'] : false), false, $eventType);
		
		# Add a link to event addition
		if ($organisation) {
			$organisationId = $organisation['id'];
			if ($this->userIsManager ($providerId, $organisationId)) {
				#!# $this->userIsAdministrator here is referring to eventsPortal admins, not the calling application admin, which is not correct - it should be the calling application
				$html .= "\n<p class=\"actions\">As " . ($this->userIsAdministrator ? 'an administrator' : "a manager of this {$organisation['typeFormatted']}") . ", you can <a href=\"{$organisation['eventsBaseUrl']}/add.html\"><img src=\"/images/icons/add.png\" alt=\"Add\" /> Add an event</a>" . ($data ? " or make changes via the links below." : '') . '</p>';
			}
		} else {
			
			# Create a sidebar of event addition plus types list
			#!# Is $eventType validated by this point?
			$html .= "\n" . '<div id="sidebar">';
			$html .= "\n<ul class=\"actions noprint\">
				<li><a href=\"{$this->baseUrl}/add.html\"><img src=\"/images/icons/add.png\" alt=\"Add\" /> Add an event</a></li>
				<li class=\"spaced\"><a href=\"{$this->baseUrl}/" . ($eventType ? "{$eventType}/" : '') . "feed.html\"><img src=\"/images/icons/layout_sidebar.png\" alt=\"Embeddable feed\" /> Add this list to your website</a></li>
				<li><a href=\"{$this->baseUrl}" . ($eventType ? "/{$eventType}" : '') . "/feed.xml\"><img src=\"/images/icons/feed.png\" alt=\"Event feed\" /> Events RSS feed</a></li>
			</ul>";
			
			# Create the types list
			$html .= "\n<h4>Show:</h4>";
			$eventTypesIncludingNull = array_merge (array ('' => 'All events'), $eventTypes);
			foreach ($eventTypesIncludingNull as $key => $value) {
				$list[$key] = (($key == $eventType) ? "<strong>{$value}</strong>" : "<a href=\"{$this->baseUrl}/" . ($key ? "{$key}/" : '') . "\">{$value}</a>");
			}
			$html .= application::htmlUl ($list, 4, 'eventtypes');
			$html .= "\n" . '</div>';
		}
		
		# Show the header
		if (!$organisation) {
			$html .= "\n<h2>" . ($eventType ? ucfirst ($eventTypes[$eventType]) . ' events' : 'All events') . '</h2>';
			if (!$eventType) {
				$html .= "\n<p>{$this->settings['welcomeTextHtml']} <a href=\"{$this->baseUrl}/add.html\">Add your event.</a></p>";
			}
		}
		
		# Show event type header
		if (!$organisation) {
			$eventTypes = array ('' => 'All types of events') + $eventTypes;
			$eventTypesLinked = array ();
			foreach ($eventTypes as $key => $value) {
				$key = $this->baseUrl . '/' . ($key == '' ? '' : $key . '/');
				$eventTypesLinked[$key] = $value;
			}
			$selected = ($eventType == '' ? false : "{$this->baseUrl}/{$eventType}/");
			$html .= pureContent::htmlJumplist ($eventTypesLinked, $selected, $this->baseUrl . '/', 'jumplist', 0, 'jumplist', 'Show event type: ');
		}
		
		# End if no events
		if (!$data) {
			$html .= "\n<p>There are no forthcoming " . ($eventType ? '<strong>' . htmlspecialchars ($eventTypes[$eventType]) . '</strong>' : '') . ' events registered.</p>';
			$html = $this->surroundOrganisationalListingWithBox ($html, $organisation);
#!# Not clear that this block of code is ever being used
			if ($organisation && $organisationStandalonePage) {
				$html = "\n<h3 id=\"sectionheading\">Forthcoming events for <a href=\"{$organisation['profileBaseUrl']}/\">{$organisation['organisationName']}</a></h3>" . $html;
			}
			return $html;
		}
		
		# Regroup by date
		$data = application::regroup ($data, 'startDate');
		
		# Start an event counter
		$counter = 0;
		
		# Compile the data into a table
		$organisationBaseUrls = array ();
		foreach ($data as $date => $thatDaysEvents) {
			$counter += count ($thatDaysEvents);
			$table = array ();
			foreach ($thatDaysEvents as $eventId => $event) {
				
				$table[$eventId]['key'] = strtolower ($event['timeCompiledBrief']);
				$table[$eventId]['value'] = "<a class=\"name\" href=\"{$this->eventsBaseUrl}/{$eventId}/{$event['urlSlug']}/\">" . htmlspecialchars ($event['eventName']) . '</a>';
				if (!$organisation) {$table[$eventId]['value'] .= " - <a href=\"{$event['profileBaseUrl']}/\">" . htmlspecialchars ($event['organisationName']) . '</a>';}
				#!# Add editing links here; but table fields must be consistent
			}
			$html .= "\n<h4 class=\"eventslist\">{$event['startDateFormatted']}</h4>";	// The startDateFormatted will be the same for each in the same date, so it's safe to use the most recent
			$html .= application::htmlTable ($table, false /* array ('key' => 'Time', 'value' => 'Event details') */, 'lines eventslist alternate hover', $showKey = false, false, $allowHtml = true);
			
			# End if counter reached, showing a list to the total
			if ($organisation && !$organisationStandalonePage) {
				if ($counter > $this->settings['eventsOnProviderPage']) {
					$html .= "\n<p class=\"completelist\"><a href=\"{$organisation['eventsBaseUrl']}/\">View complete list ...</a></p>";
					$completeListLinkHasBeenShown = true;
					break;
				}
			}
		}
		
		# Give a permalink to the events page
		if ($organisation && !$organisationStandalonePage) {
			if (!isSet ($completeListLinkHasBeenShown)) {
				$html .= "\n<p class=\"completelist\"><a href=\"{$organisation['eventsBaseUrl']}/\">[Link for events-only page]</a></p>";
			}
		}
		
		# Compile a box for the organisation mode view
		$html = $this->surroundOrganisationalListingWithBox ($html, $organisation);
		
		# Compile the HTML, showing the logo
		if ($organisation && $organisationStandalonePage) {
			$html = "\n<h2>Forthcoming events for <a href=\"{$organisation['profileBaseUrl']}/\">{$organisation['organisationName']}</a></h2>" . $html;
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to box an organisation's events
	private function surroundOrganisationalListingWithBox ($listingHtml, $organisation)
	{
		# Do nothing if not organisational
		if (!$organisation) {return $listingHtml;}
		
		# Build the HTML
		$html  = "\n" . '<div class="graybox clearfix" id="events">';
		$html .= "\n" . '<h3>Events</h3>';
		if ($this->settings['logoSmall']) {
			$html .= "\n" . "<p class=\"right\"><a href=\"{$this->eventsBaseUrl}/\"><img border=\"0\" width=\"150\" height=\"59\" src=\"http://{$_SERVER['SERVER_NAME']}{$this->eventsBaseUrl}/{$this->settings['logoSmall']}\" alt=\"" . htmlspecialchars ($this->settings['applicationNameExtended']) . "\" /></a></p>";
		}
		$html .= "\n" . '<div class="actionbuttons">';
		$html .= "\n<p class=\"actions embed\"><a href=\"{$organisation['eventsBaseUrl']}/feed.html\"><img src=\"/images/icons/layout_sidebar.png\" alt=\"Embeddable feed\" /> Add to your website</a></p>";
		$html .= "\n<p class=\"actions feed\"><a href=\"{$organisation['eventsBaseUrl']}/feed.xml\"><img src=\"/images/icons/feed.png\" alt=\"Event feed\" /> Events RSS feed</a></p>";
		$html .= "\n" . '</div>';
		$html .= $listingHtml;
		$html .= "\n" . '</div>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to retrieve a list of events from the database
	private function getEvents ($providerId = false, $organisationId = false, $eventId = false, $eventType = false, $forthcomingOnly = true, $allowHidden = false, $ensureNotDeleted = true)
	{
		# Construct the query
		#!# Migrate addslashes to prepared statements
		$query = "SELECT
			events.*,
			/* Preformatted times */
				DATE_FORMAT(events.startDate,'%W %D %M') AS startDateFormatted,
				DATE_FORMAT(events.startTime,'%l.%i%p') AS startTimeFormatted,
				DATE_FORMAT(events.endDate,'%W %D %M') AS endDateFormatted,
				DATE_FORMAT(events.endTime,'%l.%i%p') AS endTimeFormatted,
				IF((  (endDate = '') || (endDate IS NULL) || (endDate = startDate) || ((DATEDIFF(endDate,startDate) = 1) && (DATE_FORMAT(events.endTime,'%l') <= 7))  ), 1, 0) AS sameDay,
				IF((startDate < CAST(NOW() as DATE)), 1, 0) AS isRetrospective,
				IF((startDate = CAST(NOW() as DATE)), 1, 0) AS isToday,
				IF((startDate = DATE_ADD(CAST(NOW() as DATE), INTERVAL 1 DAY)), 1, 0) AS isTomorrow,
				UNIX_TIMESTAMP(lastUpdated) as lastUpdated,
			types.name as eventTypeFormatted
			FROM
				`{$this->settings['database']}`.`events`,
				`{$this->settings['database']}`.`types`
			WHERE
			/* Joins */
				eventType__JOIN__{$this->settings['database']}__types__reserved = {$this->settings['database']}.types.eventTypeId"
			/* General */
				. ($allowHidden ? '' : " AND visible = 'Yes'")
				. ($ensureNotDeleted ? " AND (events.deleted = '' OR events.deleted IS NULL)" : '')
				. ($eventId ? " AND eventId = '" . addslashes ($eventId) . "'" : '')
				. ($forthcomingOnly ? " AND CAST(NOW() as DATE) <= startDate " : '')
			/* Event types */
				. ($eventType ? " AND eventType__JOIN__{$this->settings['database']}__types__reserved = '" . addslashes ($eventType) . "'" : '')
			/* Match the provider and organisation */
				. ($providerId ? " AND provider = '" . addslashes ($providerId) . "'" : '')
				. ($organisationId ? " AND organisation = '" . addslashes ($organisationId) . "'" : '') . "
			ORDER BY
				startDate, startTime, eventName
		;";
		
		# Get the data
		$data = $this->databaseConnection->getData ($query, "{$this->settings['database']}.events");
		
		# Adjust aspects of the data
		foreach ($data as $key => $event) {
			$data[$key] = $this->formatEventDatetime ($event);
		}
		
		# Obtain the details for the organisation(s) within the events
		if ($providerId && $organisationId) {
			$organisationDetails[$providerId][$organisationId] = $this->providerApi->getOrganisationDetails ($providerId, $organisationId);
		} else {
			
#!# This version is being called on e.g. /events/3548/charity-photo-competition/ even though that page only actually ever needs to get a single entry
			# Otherwise, get the details of each organisation (listed as the provider(s) of the event(s) in the events list) who allow their events listed
			$organisationIdsByProvider = array ();
			foreach ($data as $key => $event) {
				$provider = $event['provider'];
				$organisationIdsByProvider[$provider][] = $event['organisation'];
			}
			foreach ($organisationIdsByProvider as $providerId => $organisationIds) {
				$organisationIds = array_unique ($organisationIds);
				$organisationDetails[$providerId] = $this->providerApi->getOrganisationDetails ($providerId, $organisationIds);
			}
		}
		
		# Loop through each event
		foreach ($data as $key => $event) {
			$providerId = $event['provider'];
			$organisationId = $event['organisation'];
			
			# Remove events of organisations who want their events switched off
			if (empty ($organisationDetails[$providerId][$organisationId])) {
				unset ($data[$key]);
				continue;
			}
			
			# Inject details about the organisation (which have been retrieved by the provider API) into the event data
			$data[$key]['organisationName']					= $organisationDetails[$providerId][$organisationId]['organisationName'];
			$data[$key]['organisationNameUnabbreviated']	= $organisationDetails[$providerId][$organisationId]['organisationNameUnabbreviated'];
			$data[$key]['baseUrl']							= $organisationDetails[$providerId][$organisationId]['baseUrl'];
			$data[$key]['profileBaseUrl']					= $organisationDetails[$providerId][$organisationId]['profileBaseUrl'];
		}
		
		# Flatten for a single event
		if ($eventId) {
			$data = (isSet ($data[$eventId]) ? $data[$eventId] : array ());
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to format an event time period
	private function formatEventDatetime ($data)
	{
		# Format the time
		$data['startTimeFormatted'] = strtolower (str_replace ('.00', '', $data['startTimeFormatted']));
		$data['endTimeFormatted'] = strtolower (str_replace ('.00', '', $data['endTimeFormatted']));
		
		# Construct the overall date/time string
		$data['datetime'] = $data['startDateFormatted'] . ($data['startTimeFormatted'] ? ", {$data['startTimeFormatted']}" : '');	// Default - showing start date & time
		if ($data['endTime'] && $data['sameDay']) {	// Event with end time that is the same day (which includes early next morning)
			$data['datetime'] .= "-{$data['endTimeFormatted']}";
		} else if ($data['endDate']) {	// All other events with an end date
			$data['datetime'] .= ' -<br />' . $data['endDateFormatted'] . ($data['endTimeFormatted'] ? ", {$data['endTimeFormatted']}" : '');
		}
		
		# Construct a brief time summary for use in aggregrated listings
		$data['timeCompiledBrief'] = (($data['endTime'] && $data['sameDay']) ? "{$data['startTimeFormatted']}-{$data['endTimeFormatted']}" : $data['startTimeFormatted']);
		
		# Deal with 'today' and 'tomorrow' as special cases
		if ($data['isToday']) {
			$data['startDateFormatted'] = "Today ({$data['startDateFormatted']})";
		} else if ($data['isTomorrow']) {
			$data['startDateFormatted'] = "Tomorrow ({$data['startDateFormatted']})";
		}
		
		// # Clarify 12am/12pm
		// $data['datetime'] = str_replace ('12am', '12am (midnight)', $data['datetime']);
		// $data['datetime'] = str_replace ('12pm', '12pm (midday)', $data['datetime']);
		
		# Return the data
		return $data;
	}
	
	
	# Function to show the event
	private function eventHtml ($event, $organisationId, $name)
	{
		# Start the HTML
		$html  = '';
		
		# Note retrospectivity
		if ($event['isRetrospective']) {
			$html .= "<p class=\"warning\"><em>This event has now passed.</em></p>";
			application::sendHeader (410);
		}
		
		# Build the HTML
		$html .= "
		<div id=\"event\" class=\"graybox clearfix\">
			<div class=\"title\">
				<h2>" . htmlspecialchars ($event['eventName']) . "</h2>
				<p class=\"runby\"><a href=\"{$event['profileBaseUrl']}/\">" . htmlspecialchars ($name) . "</a></p>
				" . image::imgTag (image::fnmatchImage ($event['eventId'], $this->baseUrl . '/images/'), 'Event image', 'right') . "
				<p class=\"when\">{$event['datetime']}</p>
				<p class=\"where\">" . htmlspecialchars ($event['locationName']) . "</p>
			</div>
			<div class=\"description\">
				" . application::formatTextBlock (application::makeClickableLinks (htmlspecialchars ($event['description']))) . "
			</div>
			<div class=\"details\">";
		
		$field = "eventType__JOIN__{$this->settings['database']}__types__reserved";
		$table = array (
			'Type of event' => "<a href=\"{$this->eventsBaseUrl}/{$event[$field]}/\"><strong>{$event['eventTypeFormatted']} event</strong></a>",
			'Open to' => htmlspecialchars ($event['eligibility']),
			'Cost' => htmlspecialchars ($event['cost']),
			'Contact' => application::encodeEmailAddress (htmlspecialchars ($event['contactInfo'])),
			'More info' => ($event['webpageUrl'] ? "<a href=\"{$event['webpageUrl']}\" target=\"_blank\">Event webpage</a>" : false),
			'Facebook' => ($event['facebookUrl'] ? "<a href=\"{$event['facebookUrl']}\" target=\"_blank\">" . '<img src="http://static.ak.facebook.com/images/share/facebook_share_icon.gif" alt="[F]" width="16" height="16" border="0" /> Facebook event page</a>' : false),
		);
		$html .= application::htmlTableKeyed ($table, array (), true, 'lines', $allowHtml = true);
		
		$html .= "</div>
		</div>";
		
		# Return the HTML
		return $html;
	}
	
	
	
	
	
	
	
	
	public function getBaseUrl ()
	{
		return $this->eventsBaseUrl;
	}
	
	
	
	# Function to export tabs
	public function exportTabs ()
	{
		# Define the actions
		$actions = array (
			# External items
			'eventsportal' => array (
				'tab' => '&raquo; ' . $this->settings['applicationName'],
				'description' => 'Go to ' . $this->settings['applicationName'],
#!# During login phase, we get: PHP Notice:  Undefined property:  eventsPortal::$eventsBaseUrl in /files/websites/common/php/eventsPortal.php on line 1347
				'url' => $this->eventsBaseUrl . '/',
			),
			'listorganisationevents' => array (
				'description' => 'List events',
				'usetab' => 'events',
				'heading' => false,
			),
			'addevent' => array (
				'description' => 'Add an event',
				'usetab' => 'events',
				'authentication' => true,
				'heading' => false,
			),
		);
		
		# Return the actions
		return $actions;
	}
	
	
	# Home page
	public function frontPageBox ()
	{
		# Define the HTML
		$html  = "\n" . '<div class="graybox">';
		//$html .= "\n" . "<a href=\"{$this->baseUrl}/\">" . '<img class="diagram right" src="/images/general/events.jpg" alt="*" width="48" height="48" border="0" /></a>';
		if ($this->settings['logoSmall']) {
			$html .= "\n<a href=\"{$this->eventsBaseUrl}/\"><img class=\"diagram right\" border=\"0\" width=\"150\" height=\"59\" src=\"http://{$_SERVER['SERVER_NAME']}/events/{$this->settings['logoSmall']}\" alt=\"" . htmlspecialchars ($this->settings['applicationNameExtended']) . '" /></a>';
		}
		$html .= "\n<h2>See what's on - " . $this->settings['applicationName'] . "</h2>";
		$html .= sprintf ("\n<p>{$this->settings['whereHappening']}</p>", "<a href=\"{$this->eventsBaseUrl}/\"><strong>" . $this->settings['applicationName'] . '</strong></a>');
		$html .= "\n" . '</div>';
		
		# Show the HTML
		return $html;
	}
	
	
	
	public function eventsAdvert ()
	{
		# Define an advert
		$html  = "\n<div class=\"graybox\">";
		$html .= "\n<h3>Add events?</h3>";
		$html .= "\n<p>Do you have events that you would like to publicise? If so, use <a href=\"{$this->eventsBaseUrl}/\">" . $this->settings['applicationName'] . '</a>.</p>';
		$html .= "\n<p><a href=\"{$this->eventsBaseUrl}/\">{$this->settings['eventsLogo']}</a></p>";
		$html .= "\n</div>";
		
		# Return the HTML
		return $html;
	}
	
	
	#!# Possibly needs further refactoring to remove this dependency
	private function addMailForm ($providerId, $email, $name, $timestamp, $eventSpecificContact = false, $eventMode = false, $subject = false)
	{
		# Add the mail form
		require_once ($this->providers[$providerId]['classFile']);
		$instance = new $providerId (array (), true);
		return $instance->addMailForm ($email, $name, $timestamp, $eventSpecificContact, $eventMode, $subject);
	}
}

?>