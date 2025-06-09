<?php
/**
 * DataTable Component - Reusable DataTables wrapper
 * Location: frontend/components/tables/DataTable.php
 * 
 * Configurable DataTables component with advanced features
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
        'dom' => 'Bfrtip',
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
            'stateSave' => false,
            'fixedHeader' => false,
            'scrollX' => false,
            'scrollY' => false
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
            'type' => 'string'
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
        $this->data = $data;
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
     * Render the DataTable HTML
     */
    public function render() {
        ?>
        <div class="<?php echo htmlspecialchars($this->config['classes']['wrapper']); ?>">
            <div class="<?php echo htmlspecialchars($this->config['classes']['container']); ?>">
                <table id="<?php echo htmlspecialchars($this->tableId); ?>" 
                       class="<?php echo htmlspecialchars($this->config['classes']['table']); ?>" 
                       style="width:100%">
                    <thead>
                        <tr>
                            <?php foreach ($this->columns as $column): ?>
                                <th><?php echo htmlspecialchars($column['title']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($this->data) && !$this->config['serverSide']): ?>
                            <?php $this->renderTableRows(); ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php echo $this->generateDataTableJs(); ?>
        });
        </script>
        <?php
    }
    
    /**
     * Render table rows (for client-side processing)
     */
    private function renderTableRows() {
        foreach ($this->data as $row) {
            echo '<tr>';
            foreach ($this->columns as $column) {
                $value = $this->getCellValue($row, $column);
                echo '<td>' . $this->formatCellValue($value, $column) . '</td>';
            }
            echo '</tr>';
        }
    }
    
    /**
     * Get cell value from row data
     */
    private function getCellValue($row, $column) {
        if ($column['data']) {
            $keys = explode('.', $column['data']);
            $value = $row;
            
            foreach ($keys as $key) {
                if (is_array($value) && isset($value[$key])) {
                    $value = $value[$key];
                } elseif (is_object($value) && isset($value->$key)) {
                    $value = $value->$key;
                } else {
                    return null;
                }
            }
            
            return $value;
        }
        
        return null;
    }
    
    /**
     * Format cell value based on column configuration
     */
    private function formatCellValue($value, $column) {
        if ($value === null) {
            return '';
        }
        
        // Apply custom render function if defined
        if (isset($column['render']) && is_callable($column['render'])) {
            return call_user_func($column['render'], $value, $column);
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
            return $date->format('M j, Y');
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
            return $date->format('M j, Y g:i A');
        } catch (Exception $e) {
            return htmlspecialchars($value);
        }
    }
    
    /**
     * Format currency value
     */
    private function formatCurrency($value) {
        return '$' . number_format((float)$value, 2);
    }
    
    /**
     * Format number value
     */
    private function formatNumber($value) {
        return number_format((float)$value);
    }
    
    /**
     * Format boolean value
     */
    private function formatBoolean($value) {
        return $value ? '✓' : '✗';
    }
    
    /**
     * Generate DataTable JavaScript
     */
    private function generateDataTableJs() {
        $tableId = $this->tableId;
        $config = $this->buildDataTableConfig();
        $configJson = json_encode($config, JSON_PRETTY_PRINT);
        
        return "
        // Initialize DataTable: {$tableId}
        (function() {
            const tableConfig = {$configJson};
            
            // Initialize DataTable
            const dataTable = $('#{$tableId}').DataTable(tableConfig);
            
            // Store reference for external access
            window['{$tableId}Instance'] = {
                table: dataTable,
                reload: function(data) {
                    if (data) {
                        dataTable.clear();
                        dataTable.rows.add(data);
                        dataTable.draw();
                    } else {
                        dataTable.ajax.reload();
                    }
                },
                refresh: function() {
                    dataTable.draw();
                },
                getSelectedRows: function() {
                    return dataTable.rows('.selected').data().toArray();
                },
                clearSelection: function() {
                    dataTable.rows().deselect();
                },
                search: function(value) {
                    dataTable.search(value).draw();
                },
                destroy: function() {
                    dataTable.destroy();
                }
            };
            
            // Custom event handlers
            dataTable.on('draw', function() {
                console.log('DataTable {$tableId} redrawn');
            });
            
            // Row selection handling (if enabled)
            if (tableConfig.select) {
                dataTable.on('select', function(e, dt, type, indexes) {
                    if (type === 'row') {
                        const rowData = dataTable.rows(indexes).data().toArray();
                        const event = new CustomEvent('datatableRowSelect', {
                            detail: { tableId: '{$tableId}', rows: rowData }
                        });
                        document.dispatchEvent(event);
                    }
                });
                
                dataTable.on('deselect', function(e, dt, type, indexes) {
                    if (type === 'row') {
                        const event = new CustomEvent('datatableRowDeselect', {
                            detail: { tableId: '{$tableId}', indexes: indexes }
                        });
                        document.dispatchEvent(event);
                    }
                });
            }
            
        })();
        ";
    }
    
    /**
     * Build DataTable configuration object
     */
    private function buildDataTableConfig() {
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
            'dom' => $this->config['dom'],
            'language' => $this->config['language'],
            'columns' => $this->buildColumnDefinitions(),
            'columnDefs' => $this->buildColumnDefs()
        ];
        
        // Add buttons configuration
        if ($this->hasEnabledButtons()) {
            $config['buttons'] = $this->buildButtonsConfig();
        }
        
        // Add data if not server-side
        if (!$this->config['serverSide'] && !empty($this->data)) {
            $config['data'] = $this->data;
        }
        
        // Add optional features
        if ($this->config['features']['rowSelection']) {
            $config['select'] = true;
        }
        
        if ($this->config['features']['stateSave']) {
            $config['stateSave'] = true;
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
                'visible' => $column['visible']
            ];
            
            if ($column['width']) {
                $columnDef['width'] = $column['width'];
            }
            
            if ($column['className']) {
                $columnDef['className'] = $column['className'];
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
            if (!empty($column['className']) || isset($column['width'])) {
                $def = ['targets' => $index];
                
                if (!empty($column['className'])) {
                    $def['className'] = $column['className'];
                }
                
                if (isset($column['width'])) {
                    $def['width'] = $column['width'];
                }
                
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
     * Build buttons configuration
     */
    private function buildButtonsConfig() {
        $buttons = [];
        
        // Add enabled built-in buttons
        foreach ($this->config['buttons'] as $buttonName => $buttonConfig) {
            if ($buttonConfig['enabled']) {
                $button = [
                    'extend' => $buttonConfig['extend'],
                    'text' => $buttonConfig['text']
                ];
                
                if (isset($buttonConfig['filename'])) {
                    $button['filename'] = $buttonConfig['filename'] . '_' . date('Y-m-d');
                }
                
                if (isset($buttonConfig['exportOptions'])) {
                    $button['exportOptions'] = $buttonConfig['exportOptions'];
                }
                
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
}