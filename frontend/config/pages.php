<?php

// Page configuration for the calendar application
return [
    'calendar' => [
        'title' => 'Calendar',
        'styles' => ['calendar.css'],
        'scripts' => ['calendar.js'],
        'requires' => ['jquery-datetimepicker', 'fullcalendar'],
        'sidebar' => true
    ],
    'events' => [
        'title' => 'Events',
        'styles' => ['events.css', 'table.css'],
        'scripts' => ['events.js'],
        'requires' => ['datatables'],
        'sidebar' => true
    ],
    'users' => [
        'title' => 'Users',
        'styles' => ['events.css', 'table.css'],
        'scripts' => ['users.js'],
        'requires' => ['datatables'],
        'sidebar' => true
    ],
    'import' => [
        'title' => 'Import',
        'styles' => ['import.css'],
        'scripts' => ['import.js'],
        'requires' => [],
        'sidebar' => false
    ]
];