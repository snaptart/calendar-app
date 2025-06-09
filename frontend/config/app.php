<?php
/**
* Frontend Application Configuration
*/

return [
	'app' => [
		'name' => 'Ice Time Management System',
		'description' => 'Collaborative ice time management for arenas and skating programs',
		'version' => '2.0.0',
		'timezone' => 'America/Chicago'
	],

	'api' => [
		'timeout' => 30,
		'debug' => true, // Set to false in production
	],

	'calendar' => [
		'default_view' => 'dayGridMonth',
		'snap_duration' => '00:05:00',
		'time_interval' => 15,
		'height' => 'auto'
	],

	'import' => [
		'max_file_size' => 5 * 1024 * 1024, // 5MB
		'max_events' => 20,
		'allowed_formats' => ['.json', '.csv', '.ics', '.ical', '.txt']
	]
];