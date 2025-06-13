<?php

// Page configuration for the calendar application
return [
    'calendar' => [
        'title' => 'Calendar',
        'styles' => ['components.css', 'calendar.css'],
        'scripts' => ['calendar.js'],
        'requires' => ['jquery-datetimepicker', 'fullcalendar'],
        'sidebar' => true
    ],
    'events' => [
        'title' => 'Events',
        'styles' => ['components.css', 'data-tables.css', 'table.css'],
        'scripts' => ['events.js'],
        'requires' => ['datatables'],
        'sidebar' => true
    ],
    'users' => [
        'title' => 'Users',
        'styles' => ['components.css', 'data-tables.css', 'table.css'],
        'scripts' => ['users.js'],
        'requires' => ['datatables'],
        'sidebar' => true
    ],
    'import' => [
        'title' => 'Import',
        'styles' => ['components.css', 'import.css'],
        'scripts' => ['import.js'],
        'requires' => [],
        'sidebar' => false
    ]
];