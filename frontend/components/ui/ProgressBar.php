/**
* ProgressBar Component - HTML Generation Only
* Location: frontend/components/ui/ProgressBar.php
*/
class ProgressBar {
private $config;
private $progressId;

private $defaultConfig = [
'progressId' => 'progressBar',
'value' => 0,
'max' => 100,
'showPercentage' => true,
'showLabel' => true,
'label' => '',
'animated' => true,
'striped' => false,
'size' => 'medium', // small, medium, large
'color' => 'primary', // primary, success, warning, danger, info
'classes' => [
'wrapper' => 'progress-wrapper',
'container' => 'progress-container',
'bar' => 'progress-bar',
'label' => 'progress-label',
'percentage' => 'progress-percentage'
]
];

public function __construct($config = []) {
$this->config = array_merge_recursive($this->defaultConfig, $config);
$this->progressId = $this->config['progressId'];
}

public function render() {
$percentage = ($this->config['value'] / $this->config['max']) * 100;

?>
<div id="<?php echo htmlspecialchars($this->progressId); ?>"
             class="<?php echo htmlspecialchars($this->config['classes']['wrapper']); ?> progress-<?php echo htmlspecialchars($this->config['size']); ?>"
             data-component="progress"
             data-component-id="<?php echo htmlspecialchars($this->progressId); ?>"
             data-config='<?php echo json_encode($this->config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-value="<?php echo floatval($this->config['value']); ?>"
             data-max="<?php echo floatval($this->config['max']); ?>"
             data-animated="<?php echo $this->config['animated'] ? 'true' : 'false'; ?>"
             data-auto-init="true">

	<?php
	if ($this->config['showLabel'] && $this->config['label']) : ?>
	<div class="<?php echo htmlspecialchars($this->config['classes']['label']); ?>">
		<?php echo htmlspecialchars($this->config['label']); ?>
	</div>
	<?php
endif; ?>

	<div class="<?php echo htmlspecialchars($this->config['classes']['container']); ?>">
		<div class="<?php echo htmlspecialchars($this->config['classes']['bar']); ?> progress-<?php echo htmlspecialchars($this->config['color']); ?><?php echo $this->config['striped'] ? ' progress-striped' : ''; ?><?php echo $this->config['animated'] ? ' progress-animated' : ''; ?>"
                     style="width: <?php echo $percentage; ?>%"
                     data-progress-bar="true">
		</div>
	</div>

	<?php
	if ($this->config['showPercentage']) : ?>
	<div class="<?php echo htmlspecialchars($this->config['classes']['percentage']); ?>"
                     data-progress-percentage="true">
		<?php echo round($percentage, 1); ?>%
	</div>
	<?php
endif; ?>
</div>
<?php
}

public function setValue($value)
{
	$this->config['value'] = $value;
	return $this;
}

public function setMax($max)
{
	$this->config['max'] = $max;
	return $this;
}

public function setLabel($label)
{
	$this->config['label'] = $label;
	$this->config['showLabel'] = true;
	return $this;
}

public static function create($config = [])
{
	return new self($config);
}

public static function render($config = [])
{
	$progress = new self($config);
	$progress->render();
}
}