<?php
/**
 * DataTable Component - REFACTORED (HTML Generation Only)
 * Location: frontend/components/tables/DataTable.php
 * 
 * Generates semantic HTML with data attributes for JavaScript initialization
 * All behavior and DataTables logic handled by JavaScript
 */

class DataTable {
    private $config;
    private $tableId;
    private $columns;
    private $data;
    private $options;
    
    /**
     * Default configuration
     */
    private $defaultConfig = [
        'tableId' => 'dataTable',
        'responsive' => true,
        'pageLength' => 25,
        'lengthMenu' => [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        'order' => [[0, 'asc']],
        'searching' => true,
        'paging' => true,
        'info' => true,
        'autoWidth' => false,
        'processing' => true,
        'serverSide' => false,
        'stateSave' => false,
        'dom' => 'Bfrtip',
        'realTime' => false,
        'apiUrl' => null,
        'refreshInterval' => 30000,
        'buttons' => [
            'csv' => [
                'extend' => 'csv',
                'text' => '📄 Export CSV',
                'filename' => 'data_export',
                'enabled' => true
            ],
            'excel' => [
                'extend' => 'excel',
                'text' => '📊 Export Excel',
                'filename' => 'data_export',
                'enabled' => true
            ],
            'pdf' => [
                'extend' => 'pdf',
                'text' => '📑 Export PDF',
                'filename' => 'data_export',
                'enabled' => true
            ],
            'print' => [
                'extend' => 'print',
                'text' => '🖨️ Print',
                'enabled' => true
            ]
        ],
        'language' => [
            'search' => 'Search:',
            'lengthMenu' => 'Show _MENU_ entries per page',
            'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
            'infoEmpty' => 'No entries found',
            'infoFiltered' => '(filtered from _MAX_ total entries)',
            'paginate' => [
                'first' => 'First',
                'last' => 'Last',
                'next' => 'Next',
                'previous' => 'Previous'
            ],
            'emptyTable' => 'No data available in table'
        ],
        'classes' => [
            'table' => 'table table-striped table-hover',
            'wrapper' => 'table-wrapper',
            'container' => 'table-container'
        ],
        'features' => [
            'rowSelection' => false,
            'columnVisibility' => false,
            'fixedHeader' => false,
            'scrollX' => false,
            'scrollY' => false
        ],
        'permissions' => [
            'canExport' => true,
            'canFilter' => true,
            'canSort' => true,
            'canSelect' => false
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct($config = []) {
        $this->config = array_merge_recursive($this->defaultConfig, $config);
        $this->tableId = $this->config['tableId'];
        $this->columns = [];
        $this->data = [];
        $this->options = [];
    }
    
    /**
     * Add column definition
     */
    public function addColumn($config) {
        $defaultColumn = [
            'data' => null,
            'name' => null,
            'title' => 'Column',
            'orderable' => true,
            'searchable' => true,
            'visible' => true,
            'width' => null,
            'className' => '',
            'render' => null,
            'type' => 'string',
            'targets' => null
        ];
        
        $this->columns[] = array_merge($defaultColumn, $config);
        return $this;
    }
    
    /**
     * Add multiple columns
     */
    public function addColumns($columns) {
        foreach ($columns as $column) {
            $this->addColumn($column);
        }
        return $this;
    }
    
    /**
     * Set table data
     */
    public function setData($data) {
        $this->data = is_array($data) ? $data : [];
        return $this;
    }
    
    /**
     * Set DataTables option
     */
    public function setOption($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }
    
    /**
     * Set multiple options
     */
    public function setOptions($options) {
        $this->options = array_merge($this->options, $options);
        return $this;
    }
    
    /**
     * Enable/disable button
     */
    public function enableButton($buttonName, $enabled = true) {
        if (isset($this->config['buttons'][$buttonName])) {
            $this->config['buttons'][$buttonName]['enabled'] = $enabled;
        }
        return $this;
    }
    
    /**
     * Set button configuration
     */
    public function configureButton($buttonName, $config) {
        if (isset($this->config['buttons'][$buttonName])) {
            $this->config['buttons'][$buttonName] = array_merge(
                $this->config['buttons'][$buttonName], 
                $config
            );
        }
        return $this;
    }
    
    /**
     * Add custom button
     */
    public function addCustomButton($config) {
        $this->config['customButtons'][] = $config;
        return $this;
    }
    
    /**
     * Set API configuration
     */
    public function setApi($url, $serverSide = false, $refreshInterval = null) {
        $this->config['apiUrl'] = $url;
        $this->config['serverSide'] = $serverSide;
        
        if ($refreshInterval !== null) {
            $this->config['refreshInterval'] = $refreshInterval;
        }
        
        return $this;
    }
    
    /**
     * Enable real-time updates
     */
    public function setRealTime($enabled = true, $interval = 30000) {
        $this->config['realTime'] = $enabled;
        $this->config['refreshInterval'] = $interval;
        return $this;
    }
    
    /**
     * Set permissions
     */
    public function setPermissions($permissions) {
        $this->config['permissions'] = array_merge($this->config['permissions'], $permissions);
        return $this;
    }
    
    /**
     * Render the DataTable HTML with data attributes
     */
    public function render() {
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['wrapper']); ?>">
            <?php $this->renderControls(); ?>
            
            <div class="<?php echo htmlspecialchars($this->config['classes']['container']); ?>">
                <table id="<?php echo htmlspecialchars($this->tableId); ?>" 
                       class="<?php echo htmlspecialchars($this->config['classes']['table']); ?>" 
                       style="width:100%"
                       data-component="datatable"
                       data-component-id="<?php echo htmlspecialchars($this->tableId); ?>"
                       data-config='<?php echo json_encode($this->buildTableConfig(), JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-columns='<?php echo json_encode($this->buildColumnDefinitions(), JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-column-defs='<?php echo json_encode($this->buildColumnDefs(), JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       <?php if (!empty($this->data) && !$this->config['serverSide']): ?>
                           data-data='<?php echo json_encode($this->data, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       <?php endif; ?>
                       <?php if ($this->config['apiUrl']): ?>
                           data-api-url="<?php echo htmlspecialchars($this->config['apiUrl']); ?>"
                       <?php endif; ?>
                       data-server-side="<?php echo $this->config['serverSide'] ? 'true' : 'false'; ?>"
                       data-real-time="<?php echo $this->config['realTime'] ? 'true' : 'false'; ?>"
                       data-refresh-interval="<?php echo intval($this->config['refreshInterval']); ?>"
                       data-permissions='<?php echo json_encode($this->config['permissions'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-buttons='<?php echo json_encode($this->buildButtonsConfig(), JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
                       data-auto-init="true">
                    
                    <thead>
                        <tr>
                            <?php foreach ($this->columns as $column): ?>
                                <th data-column="<?php echo htmlspecialchars($column['data'] ?? ''); ?>"
                                    data-orderable="<?php echo $column['orderable'] ? 'true' : 'false'; ?>"
                                    data-searchable="<?php echo $column['searchable'] ? 'true' : 'false'; ?>"
                                    data-type="<?php echo htmlspecialchars($column['type']); ?>"
                                    <?php if ($column['width']): ?>
                                        data-width="<?php echo htmlspecialchars($column['width']); ?>"
                                    <?php endif; ?>
                                    <?php if ($column['className']): ?>
                                        class="<?php echo htmlspecialchars($column['className']); ?>"
                                    <?php endif; ?>>
                                    <?php echo htmlspecialchars($column['title']); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php if (!empty($this->data) && !$this->config['serverSide']): ?>
                            <?php $this->renderTableRows(); ?>
                        <?php endif; ?>
                    </tbody>
                    
                    <?php if ($this->hasFooter()): ?>
                        <tfoot>
                            <tr>
                                <?php foreach ($this->columns as $column): ?>
                                    <th><?php echo htmlspecialchars($column['title']); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    <?php endif; ?>
                </table>
            </div>
            
            <?php $this->renderTableInfo(); ?>
        </div>
        <?php
    }
    
    /**
     * Render table controls
     */
    private function renderControls() {
        if (!$this->hasEnabledButtons() && !$this->config['permissions']['canFilter']) {
            return;
        }
        
        ?>
        <div class="table-controls" 
             data-component="table-controls"
             data-target="#<?php echo htmlspecialchars($this->tableId); ?>">
            
            <?php if ($this->config['permissions']['canFilter']): ?>
                <div class="table-filters">
                    <div class="filter-group">
                        <label for="<?php echo $this->tableId; ?>Search">Search:</label>
                        <input type="search" 
                               id="<?php echo $this->tableId; ?>Search"
                               class="form-control table-search"
                               placeholder="Search table..."
                               data-target="#<?php echo htmlspecialchars($this->tableId); ?>"
                               data-action="search">
                    </div>
                    
                    <div class="filter-group">
                        <label for="<?php echo $this->tableId; ?>Length">Show:</label>
                        <select id="<?php echo $this->tableId; ?>Length"
                                class="form-control table-length"
                                data-target="#<?php echo htmlspecialchars($this->tableId); ?>"
                                data-action="change-length">
                            <?php foreach ($this->config['lengthMenu'][0] as $index => $value): ?>
                                <option value="<?php echo $value; ?>"
                                        <?php echo $value == $this->config['pageLength'] ? 'selected' : ''; ?>>
                                    <?php echo $this->config['lengthMenu'][1][$index]; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($this->hasEnabledButtons()): ?>
                <div class="table-actions">
                    <?php foreach ($this->config['buttons'] as $buttonName => $buttonConfig): ?>
                        <?php if ($buttonConfig['enabled']): ?>
                            <button type="button"
                                    class="btn btn-outline btn-small"
                                    data-component="button"
                                    data-action="export"
                                    data-export-type="<?php echo htmlspecialchars($buttonConfig['extend']); ?>"
                                    data-target="#<?php echo htmlspecialchars($this->tableId); ?>"
                                    data-filename="<?php echo htmlspecialchars($buttonConfig['filename']); ?>">
                                <?php echo htmlspecialchars($buttonConfig['text']); ?>
                            </button>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($this->config['customButtons'])): ?>
                        <?php foreach ($this->config['customButtons'] as $button): ?>
                            <button type="button"
                                    class="<?php echo htmlspecialchars($button['class'] ?? 'btn btn-outline btn-small'); ?>"
                                    data-component="button"
                                    data-action="<?php echo htmlspecialchars($button['action'] ?? 'custom'); ?>"
                                    data-target="#<?php echo htmlspecialchars($this->tableId); ?>"
                                    <?php if (isset($button['data'])): ?>
                                        <?php foreach ($button['data'] as $key => $value): ?>
                                            data-<?php echo htmlspecialchars($key); ?>="<?php echo htmlspecialchars($value); ?>"
                                        <?php endforeach; ?>
                                    <?php endif; ?>>
                                <?php echo htmlspecialchars($button['text']); ?>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if ($this->config['realTime']): ?>
                        <button type="button"
                                class="btn btn-outline btn-small"
                                id="<?php echo $this->tableId; ?>Refresh"
                                data-component="button"
                                data-action="refresh"
                                data-target="#<?php echo htmlspecialchars($this->tableId); ?>">
                            🔄 Refresh
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render table rows (for client-side processing)
     */
    private function renderTableRows() {
        foreach ($this->data as $rowIndex => $row) {
            echo '<tr data-row-index="' . $rowIndex . '">';
            
            foreach ($this->columns as $column) {
                $value = $this->getCellValue($row, $column);
                $formattedValue = $this->formatCellValue($value, $column);
                
                echo '<td';
                
                if ($column['className']) {
                    echo ' class="' . htmlspecialchars($column['className']) . '"';
                }
                
                echo ' data-column="' . htmlspecialchars($column['data'] ?? '') . '"';
                echo ' data-type="' . htmlspecialchars($column['type']) . '"';
                echo ' data-raw-value="' . htmlspecialchars($value) . '"';
                
                echo '>' . $formattedValue . '</td>';
            }
            
            echo '</tr>';
        }
    }
    
    /**
     * Render table information
     */
    private function renderTableInfo() {
        ?>
        <div class="table-info" 
             data-component="table-info"
             data-target="#<?php echo htmlspecialchars($this->tableId); ?>">
            
            <div class="table-status">
                <span id="<?php echo $this->tableId; ?>Info" class="table-info-text">
                    Ready
                </span>
            </div>
            
            <?php if ($this->config['realTime']): ?>
                <div class="table-realtime">
                    <span class="realtime-indicator"
                          data-component="status"
                          data-component-id="<?php echo $this->tableId; ?>-realtime"
                          data-auto-init="true">
                        <span class="indicator-dot"></span>
                        Live Updates
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get cell value from row data
     */
    private function getCellValue($row, $column) {
        if (!$column['data']) {
            return '';
        }
        
        $keys = explode('.', $column['data']);
        $value = $row;
        
        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $value = $value->$key;
            } else {
                return '';
            }
        }
        
        return $value;
    }
    
    /**
     * Format cell value based on column configuration
     */
    private function formatCellValue($value, $column) {
        if ($value === null || $value === '') {
            return '';
        }
        
        // Apply custom render function if defined (for future JS processing)
        if (isset($column['render'])) {
            return '<span data-render="' . htmlspecialchars($column['render']) . '">' . htmlspecialchars($value) . '</span>';
        }
        
        // Default formatting based on type
        switch ($column['type']) {
            case 'date':
                return $this->formatDate($value);
            case 'datetime':
                return $this->formatDateTime($value);
            case 'currency':
                return $this->formatCurrency($value);
            case 'number':
                return $this->formatNumber($value);
            case 'boolean':
                return $this->formatBoolean($value);
            case 'badge':
                return $this->formatBadge($value);
            case 'link':
                return $this->formatLink($value, $column);
            default:
                return htmlspecialchars($value);
        }
    }
    
    /**
     * Format date value
     */
    private function formatDate($value) {
        try {
            $date = new DateTime($value);
            return '<span data-format="date" data-value="' . htmlspecialchars($value) . '">' . 
                   $date->format('M j, Y') . '</span>';
        } catch (Exception $e) {
            return htmlspecialchars($value);
        }
    }
    
    /**
     * Format datetime value
     */
    private function formatDateTime($value) {
        try {
            $date = new DateTime($value);
            return '<span data-format="datetime" data-value="' . htmlspecialchars($value) . '">' . 
                   $date->format('M j, Y g:i A') . '</span>';
        } catch (Exception $e) {
            return htmlspecialchars($value);
        }
    }
    
    /**
     * Format currency value
     */
    private function formatCurrency($value) {
        $formatted = '$' . number_format((float)$value, 2);
        return '<span data-format="currency" data-value="' . htmlspecialchars($value) . '">' . 
               $formatted . '</span>';
    }
    
    /**
     * Format number value
     */
    private function formatNumber($value) {
        $formatted = number_format((float)$value);
        return '<span data-format="number" data-value="' . htmlspecialchars($value) . '">' . 
               $formatted . '</span>';
    }
    
    /**
     * Format boolean value
     */
    private function formatBoolean($value) {
        $display = $value ? '✓' : '✗';
        $class = $value ? 'text-success' : 'text-muted';
        return '<span class="' . $class . '" data-format="boolean" data-value="' . 
               ($value ? 'true' : 'false') . '">' . $display . '</span>';
    }
    
    /**
     * Format badge value
     */
    private function formatBadge($value) {
        return '<span class="badge" data-format="badge">' . htmlspecialchars($value) . '</span>';
    }
    
    /**
     * Format link value
     */
    private function formatLink($value, $column) {
        $url = $column['linkUrl'] ?? '#';
        $target = $column['linkTarget'] ?? '_self';
        
        return '<a href="' . htmlspecialchars($url) . '" target="' . htmlspecialchars($target) . '" ' .
               'data-format="link">' . htmlspecialchars($value) . '</a>';
    }
    
    /**
     * Build table configuration for DataTables
     */
    private function buildTableConfig() {
        $config = [
            'responsive' => $this->config['responsive'],
            'pageLength' => $this->config['pageLength'],
            'lengthMenu' => $this->config['lengthMenu'],
            'order' => $this->config['order'],
            'searching' => $this->config['searching'],
            'paging' => $this->config['paging'],
            'info' => $this->config['info'],
            'autoWidth' => $this->config['autoWidth'],
            'processing' => $this->config['processing'],
            'serverSide' => $this->config['serverSide'],
            'stateSave' => $this->config['stateSave'],
            'dom' => $this->config['dom'],
            'language' => $this->config['language']
        ];
        
        // Add optional features
        if ($this->config['features']['rowSelection']) {
            $config['select'] = true;
        }
        
        if ($this->config['features']['fixedHeader']) {
            $config['fixedHeader'] = true;
        }
        
        if ($this->config['features']['scrollX']) {
            $config['scrollX'] = true;
        }
        
        if ($this->config['features']['scrollY']) {
            $config['scrollY'] = $this->config['features']['scrollY'];
        }
        
        // Merge with custom options
        return array_merge($config, $this->options);
    }
    
    /**
     * Build column definitions for DataTables
     */
    private function buildColumnDefinitions() {
        $columns = [];
        
        foreach ($this->columns as $column) {
            $columnDef = [
                'data' => $column['data'],
                'name' => $column['name'],
                'title' => $column['title'],
                'orderable' => $column['orderable'],
                'searchable' => $column['searchable'],
                'visible' => $column['visible'],
                'type' => $column['type']
            ];
            
            if ($column['width']) {
                $columnDef['width'] = $column['width'];
            }
            
            if ($column['className']) {
                $columnDef['className'] = $column['className'];
            }
            
            if ($column['render']) {
                $columnDef['render'] = $column['render'];
            }
            
            $columns[] = $columnDef;
        }
        
        return $columns;
    }
    
    /**
     * Build column definitions array
     */
    private function buildColumnDefs() {
        $columnDefs = [];
        
        foreach ($this->columns as $index => $column) {
            $def = [];
            $needsDef = false;
            
            if (!empty($column['className'])) {
                $def['className'] = $column['className'];
                $needsDef = true;
            }
            
            if (isset($column['width'])) {
                $def['width'] = $column['width'];
                $needsDef = true;
            }
            
            if (isset($column['targets'])) {
                $def['targets'] = $column['targets'];
                $needsDef = true;
            } else {
                $def['targets'] = $index;
            }
            
            if ($needsDef) {
                $columnDefs[] = $def;
            }
        }
        
        return $columnDefs;
    }
    
    /**
     * Check if any buttons are enabled
     */
    private function hasEnabledButtons() {
        foreach ($this->config['buttons'] as $button) {
            if ($button['enabled']) {
                return true;
            }
        }
        
        return !empty($this->config['customButtons']);
    }
    
    /**
     * Check if table has footer
     */
    private function hasFooter() {
        return $this->config['features']['columnVisibility'] || 
               $this->config['searching'] || 
               $this->config['stateSave'];
    }
    
    /**
     * Build buttons configuration
     */
    private function buildButtonsConfig() {
        $buttons = [];
        
        // Add enabled built-in buttons
        foreach ($this->config['buttons'] as $buttonName => $buttonConfig) {
            if ($buttonConfig['enabled']) {
                $button = [
                    'extend' => $buttonConfig['extend'],
                    'text' => $buttonConfig['text'],
                    'filename' => $buttonConfig['filename'] . '_' . date('Y-m-d')
                ];
                
                $buttons[] = $button;
            }
        }
        
        // Add custom buttons
        if (!empty($this->config['customButtons'])) {
            foreach ($this->config['customButtons'] as $customButton) {
                $buttons[] = $customButton;
            }
        }
        
        return $buttons;
    }
    
    /**
     * Create a DataTable with configuration
     */
    public static function create($config = []) {
        return new self($config);
    }
    
    /**
     * Quick render method for simple tables
     */
    public static function render($config = [], $columns = [], $data = []) {
        $table = new self($config);
        $table->addColumns($columns);
        $table->setData($data);
        $table->render();
    }
    
    /**
     * Render minimal table (basic HTML table without DataTables features)
     */
    public static function renderSimple($tableId, $columns = [], $data = [], $classes = 'table') {
        ?>
        <table id="<?php echo htmlspecialchars($tableId); ?>" 
               class="<?php echo htmlspecialchars($classes); ?>"
               data-component="simple-table"
               data-component-id="<?php echo htmlspecialchars($tableId); ?>">
            <thead>
                <tr>
                    <?php foreach ($columns as $column): ?>
                        <th><?php echo htmlspecialchars($column['title'] ?? $column); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <td>
                                <?php 
                                $value = is_array($row) ? ($row[$column['data'] ?? $column] ?? '') : $row;
                                echo htmlspecialchars($value);
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}