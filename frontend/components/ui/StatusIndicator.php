/**
* StatusIndicator Component - HTML Generation Only
* Location: frontend/components/ui/StatusIndicator.php
*/
class StatusIndicator {
private $config;
private $statusId;

private $defaultConfig = [
'statusId' => 'status',
'initialState' => 'ready',
'realTime' => true,
'showText' => true,
'showIndicator' => true,
'autoUpdate' => true,
'updateInterval' => 30000,
'states' => [
'ready' => ['text' => 'Ready', 'class' => 'status-ready', 'color' => '#28a745'],
'loading' => ['text' => 'Loading...', 'class' => 'status-loading', 'color' => '#ffc107'],
'connected' => ['text' => 'Connected', 'class' => 'status-connected', 'color' => '#28a745'],
'disconnected' => ['text' => 'Disconnected', 'class' => 'status-disconnected', 'color' => '#dc3545'],
'error' => ['text' => 'Error', 'class' => 'status-error', 'color' => '#dc3545'],
'success' => ['text' => 'Success', 'class' => 'status-success', 'color' => '#28a745']
],
'classes' => [
'wrapper' => 'status-indicator',
'dot' => 'status-dot',
'text' => 'status-text'
]
];

public function __construct($config = []) {
$this->config = array_merge_recursive($this->defaultConfig, $config);
$this->statusId = $this->config['statusId'];
}

public function render() {
$currentState = $this->config['states'][$this->config['initialState']];

?>
<div id="<?php echo htmlspecialchars($this->statusId); ?>"
             class="<?php echo htmlspecialchars($this->config['classes']['wrapper']); ?> <?php echo htmlspecialchars($currentState['class']); ?>"
             data-component="status"
             data-component-id="<?php echo htmlspecialchars($this->statusId); ?>"
             data-config='<?php echo json_encode($this->config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-initial-state="<?php echo htmlspecialchars($this->config['initialState']); ?>"
             data-real-time="<?php echo $this->config['realTime'] ? 'true' : 'false'; ?>"
             data-auto-update="<?php echo $this->config['autoUpdate'] ? 'true' : 'false'; ?>"
             data-update-interval="<?php echo intval($this->config['updateInterval']); ?>"
             data-states='<?php echo json_encode($this->config['states'], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-auto-init="true">

	<?php
	if ($this->config['showIndicator']) : ?>
	<span class="<?php echo htmlspecialchars($this->config['classes']['dot']); ?>"
	style="background-color: <?php echo htmlspecialchars($currentState['color']); ?>"
	data-status-indicator="true"></span>
	<?php
endif; ?>

	<?php
	if ($this->config['showText']) : ?>
	<span class="<?php echo htmlspecialchars($this->config['classes']['text']); ?>"
	data-status-text="true">
	<?php echo htmlspecialchars($currentState['text']); ?>
	</span>
	<?php
endif; ?>
</div>
<?php
}

public function setState($state)
{
	$this->config['initialState'] = $state;
	return $this;
}

public function addState($name, $config)
{
	$this->config['states'][$name] = $config;
	return $this;
}

public static function create($config = [])
{
	return new self($config);
}

public static function render($config = [])
{
	$status = new self($config);
	$status->render();
}
}