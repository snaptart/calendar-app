<?php
/**
 * Shared Data Section Header Component
 * Location: frontend/components/data-section-header.php
 * 
 * This component eliminates code duplication by providing a reusable
 * calendar-events-section structure that can be configured for different pages.
 */

/**
 * Renders a standardized data section header with filters and action buttons
 * 
 * @param array $config Configuration array with the following structure:
 * [
 *     'title' => 'Page Title',
 *     'description' => 'Page description text',
 *     'filters' => [
 *         [
 *             'id' => 'filterElementId',
 *             'label' => 'FILTER LABEL',
 *             'options' => [
 *                 'value' => 'Display Text',
 *                 ...
 *             ]
 *         ],
 *         ...
 *     ],
 *     'actions' => [
 *         [
 *             'id' => 'buttonElementId',
 *             'text' => 'Button Text',
 *             'class' => 'btn btn-primary',
 *             'icon' => '🔄' // optional
 *         ],
 *         ...
 *     ]
 * ]
 */
function renderDataSectionHeader($config) {
    // Validate required config
    if (!isset($config['title']) || !isset($config['description'])) {
        throw new InvalidArgumentException('Title and description are required in config');
    }
    
    $title = htmlspecialchars($config['title']);
    $description = htmlspecialchars($config['description']);
    $filters = $config['filters'] ?? [];
    $actions = $config['actions'] ?? [];
    ?>
    
    <div class="calendar-events-section">
        <div class="section-header">
            <div class="section-info">
                <h3><?= $title ?></h3>
                <!-- <p class="section-description"><?= $description ?></p> -->
            </div>
            <div class="section-controls">
                <div class="filter-controls">
                    <?php foreach ($filters as $filter): ?>
                        <div class="filter-group">
                            <label for="<?= htmlspecialchars($filter['id']) ?>"><?= htmlspecialchars($filter['label']) ?>:</label>
                            <select id="<?= htmlspecialchars($filter['id']) ?>" class="filter-select">
                                <?php foreach ($filter['options'] as $value => $text): ?>
                                    <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($text) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($actions)): ?>
                        <div class="action-buttons">
                            <?php foreach ($actions as $action): ?>
                                <button id="<?= htmlspecialchars($action['id']) ?>" class="<?= htmlspecialchars($action['class']) ?>">
                                    <?php if (isset($action['icon'])): ?>
                                        <?= $action['icon'] ?> 
                                    <?php endif; ?>
                                    <?= htmlspecialchars($action['text']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="connection-status">
                        <span id="connectionStatus" class="status">Connected</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
}

/**
 * Predefined configuration for Events page
 */
function getEventsPageConfig() {
    return [
        'title' => 'Events',
        'description' => 'View and manage calendar events with advanced filtering and search',
        'filters' => [
            [
                'id' => 'eventViewFilter',
                'label' => 'VIEW',
                'options' => [
                    'all' => 'All Events',
                    'my' => 'My Events Only',
                    'others' => 'Others\' Events'
                ]
            ],
            [
                'id' => 'eventStatusFilter',
                'label' => 'STATUS',
                'options' => [
                    '' => 'All Status',
                    'upcoming' => 'Upcoming',
                    'ongoing' => 'Ongoing',
                    'past' => 'Past'
                ]
            ],
            [
                'id' => 'eventUserFilter',
                'label' => 'USER',
                'options' => [
                    '' => 'All Users'
                    // Additional users will be populated by JavaScript
                ]
            ]
        ],
        'actions' => [
            [
                'id' => 'refreshEventsBtn',
                'text' => 'Refresh Data',
                'class' => 'btn btn-outline',
                'icon' => '🔄'
            ],
            [
                'id' => 'addEventBtn',
                'text' => 'Add Event',
                'class' => 'btn btn-primary',
                'icon' => '+'
            ]
        ]
    ];
}

/**
 * Predefined configuration for Users page
 */
function getUsersPageConfig() {
    return [
        'title' => 'User Management',
        'description' => 'View and manage system users with advanced filtering and search',
        'filters' => [
            [
                'id' => 'userStatusFilter',
                'label' => 'STATUS',
                'options' => [
                    '' => 'All Status',
                    'active' => 'Active',
                    'recent' => 'Recent',
                    'inactive' => 'Inactive',
                    'new' => 'New',
                    'guest' => 'Guest'
                ]
            ],
            [
                'id' => 'userRoleFilter',
                'label' => 'ROLE',
                'options' => [
                    '' => 'All Roles',
                    'Calendar User' => 'Calendar User',
                    'Admin' => 'Administrator',
                    'Manager' => 'Manager'
                ]
            ]
        ],
        'actions' => [
            [
                'id' => 'refreshUsersBtn',
                'text' => 'Refresh Data',
                'class' => 'btn btn-outline',
                'icon' => '🔄'
            ],
            [
                'id' => 'addUserBtn',
                'text' => 'Add User',
                'class' => 'btn btn-primary',
                'icon' => '+'
            ]
        ]
    ];
}

/**
 * Helper function to render Events page header
 */
function renderEventsPageHeader() {
    renderDataSectionHeader(getEventsPageConfig());
}

/**
 * Predefined configuration for Programs page
 */
function getProgramsPageConfig() {
    return [
        'title' => 'Programs',
        'description' => 'Manage skating programs and their details',
        'filters' => [
            [
                'id' => 'typeFilter',
                'label' => 'TYPE',
                'options' => [
                    '' => 'All Types'
                    // Additional program types will be populated by JavaScript
                ]
            ],
            [
                'id' => 'statusFilter',
                'label' => 'STATUS',
                'options' => [
                    '' => 'All Statuses',
                    'Active' => 'Active',
                    'Inactive' => 'Inactive'
                ]
            ],
            [
                'id' => 'facilityFilter',
                'label' => 'FACILITY',
                'options' => [
                    '' => 'All Facilities'
                    // Additional facilities will be populated by JavaScript
                ]
            ]
        ],
        'actions' => [
            [
                'id' => 'refreshBtn',
                'text' => 'Refresh',
                'class' => 'btn btn-outline-secondary',
                'icon' => '🔄'
            ],
            [
                'id' => 'exportBtn',
                'text' => 'Export',
                'class' => 'btn btn-outline-secondary',
                'icon' => '📥'
            ],
            [
                'id' => 'addProgramBtn',
                'text' => 'Add Program',
                'class' => 'btn btn-primary',
                'icon' => '+'
            ]
        ]
    ];
}

/**
 * Helper function to render Users page header
 */
function renderUsersPageHeader() {
    renderDataSectionHeader(getUsersPageConfig());
}

/**
 * Helper function to render Programs page header
 */
function renderProgramsPageHeader() {
    renderDataSectionHeader(getProgramsPageConfig());
}
?>