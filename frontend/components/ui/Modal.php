/**
* Modal Component - HTML Generation Only
* Location: frontend/components/ui/Modal.php
*/
class Modal {
private $config;
private $modalId;

private $defaultConfig = [
'modalId' => 'modal',
'title' => 'Modal',
'size' => 'medium', // small, medium, large, fullscreen
'backdrop' => 'static',
'keyboard' => true,
'closeOnEscape' => true,
'autoShow' => false,
'content' => '',
'footer' => '',
'classes' => [
'modal' => 'modal',
'content' => 'modal-content',
'header' => 'modal-header',
'body' => 'modal-body',
'footer' => 'modal-footer'
]
];

public function __construct($config = []) {
$this->config = array_merge_recursive($this->defaultConfig, $config);
$this->modalId = $this->config['modalId'];
}

public function render() {
$modalConfig = [
'size' => $this->config['size'],
'backdrop' => $this->config['backdrop'],
'keyboard' => $this->config['keyboard'],
'closeOnEscape' => $this->config['closeOnEscape'],
'autoShow' => $this->config['autoShow']
];

?>
<div id="<?php echo htmlspecialchars($this->modalId); ?>"
             class="<?php echo htmlspecialchars($this->config['classes']['modal']); ?> modal-<?php echo htmlspecialchars($this->config['size']); ?>"
             data-component="modal"
             data-component-id="<?php echo htmlspecialchars($this->modalId); ?>"
             data-config='<?php echo json_encode($modalConfig, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-auto-init="true">

	<div class="<?php echo htmlspecialchars($this->config['classes']['content']); ?>">
		<div class="<?php echo htmlspecialchars($this->config['classes']['header']); ?>">
			<h2 class="modal-title">
				<?php echo htmlspecialchars($this->config['title']); ?>
			</h2>
			<span class="close" data-action="close-modal">&times;</span>
		</div>

		<div class="<?php echo htmlspecialchars($this->config['classes']['body']); ?>">
			<?php echo $this->config['content']; ?>
		</div>

		<?php
		if ($this->config['footer']) : ?>
		<div class="<?php echo htmlspecialchars($this->config['classes']['footer']); ?>">
			<?php echo $this->config['footer']; ?>
		</div>
		<?php
	endif; ?>
	</div>
</div>
<?php
}

public function setContent($content)
{
	$this->config['content'] = $content;
	return $this;
}

public function setFooter($footer)
{
	$this->config['footer'] = $footer;
	return $this;
}

public static function create($config = [])
{
	return new self($config);
}

public static function render($config = [])
{
	$modal = new self($config);
	$modal->render();
}
}