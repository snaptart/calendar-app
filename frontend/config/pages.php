<?php

// Page configuration for the calendar application
return [
    'calendar' => [
        'title' => 'Calendar',
        'styles' => ['components.css', 'calendar.css'],
        'scripts' => [],
        'requires' => ['jquery-datetimepicker', 'fullcalendar'],
        'sidebar' => true
    ],
    'events' => [
        'title' => 'Events',
        'styles' => ['components.css', 'data-tables.css', 'table.css'],
        'scripts' => [],
        'requires' => ['datatables'],
        'sidebar' => true
    ],
    'users' => [
        'title' => 'Users',
        'styles' => ['components.css', 'data-tables.css', 'table.css'],
        'scripts' => [],
        'requires' => ['datatables'],
        'sidebar' => true
    ],
    'programs' => [
        'title' => 'Programs',
        'styles' => ['components.css', 'data-tables.css', 'table.css'],
        'scripts' => ['programs.js'],
        'requires' => ['datatables'],
        'sidebar' => true
    ],
    'import' => [
        'title' => 'Import',
        'styles' => ['components.css', 'import.css'],
        'scripts' => [],
        'requires' => [],
        'sidebar' => true
    ]
];