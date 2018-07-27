<?php

/*
Plugin Name: Events portal integration
Plugin URI: https://github.com/cusu/events-portal
Description: Events system integration
Version: 1.0
Author: CUSU Webmaster
Author URI: https://github.com/cusu
License: GPL3
*/


# Events portal integration
# See: https://premium.wpmudev.org/blog/create-custom-wordpress-widget/
class events_portal_integration extends WP_Widget
{
	public function __construct ()
	{
		$widget_options = array (
			'classname' => 'events_portal_integration',
			'description' => 'Events portal integration',
		);
		parent::__construct ('events_portal_integration', 'Events portal integration', $widget_options);
	}
	
	
	public function widget ($args, $instance)
	{
		# Obtain the feed
		$feedLocation = '/events/cusu/feed.json';	// or use /events/feed.json for global feed
		$json = file_get_contents ($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $feedLocation);
		
		# Convert to array
		$events = json_decode ($json, true);
		
		# Limit to 5
		$events = array_slice ($events, 0, 5);
		
		# Add start
		echo '
		<div class="so-panel widget widget_list-event">
			<!--<h4><a href="/events/">Events</a></h4>-->
			<div class="thim-widget-list-event thim-widget-list-event-base">
				<div class="thim-list-event layout-2">
		';
		
		# Add each event
		foreach ($events as $event) {
			echo '
					<div class="item-event post-2948 tp_event type-tp_event status-publish has-post-thumbnail hentry">
						<div class="time-from">
							<div class="date">
								' . $event['day'] . '
							</div>
							<div class="month">
								' . $event['month'] . '
							</div>
						</div>
						<div class="event-wrapper">
							<h5 class="title">
								<a href="' . $event['url'] . '">' . $event['title'] . '</a>
							</h5>
							<div class="meta">
								<div class="time">
									<i class="fa fa-clock-o"></i>
									' . $event['time'] . '
								</div>
								<div class="location">
									<i class="fa fa-map-marker"></i>
									' . mb_substr ($event['location'], 0, 25) . (mb_strlen ($event['location']) > 25 ? '...' : '') . '
								</div>
							</div>
						</div>
					</div>
			';
		}
		
		# Add finish
		echo '
					<a class="view-all" href="/events/">View all events</a><br />
					<a class="view-all" href="/events/add.html">+ Add your event</a>
				</div>
			</div>
		</div>
		';
	}
}

function register_events_portal_integration_widget () {
	register_widget ('events_portal_integration');
}

add_action ('widgets_init', 'register_events_portal_integration_widget');


?>
