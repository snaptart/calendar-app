/**
* Notification Component - HTML Generation Only
* Location: frontend/components/ui/Notification.php
*/
class Notification {
private $config;
private $notificationId;

private $defaultConfig = [
'notificationId' => 'notification',
'type' => 'info', // success, error, warning, info
'message' => '',
'title' => '',
'autoHide' => true,
'hideDelay' => 5000,
'closable' => true,
'position' => 'top-right', // top-left, top-right, bottom-left, bottom-right
'classes' => [
'wrapper' => 'notification',
'icon' => 'notification-icon',
'content' => 'notification-content',
'title' => 'notification-title',
'message' => 'notification-message',
'close' => 'notification-close'
]
];

public function __construct($config = []) {
$this->config = array_merge_recursive($this->defaultConfig, $config);
$this->notificationId = $this->config['notificationId'];
}

public function render() {
$icons = [
'success' => '✓',
'error' => '✗',
'warning' => '⚠',
'info' => 'ℹ'
];

$icon = $icons[$this->config['type']] ?? $icons['info'];

?>
<div id="<?php echo htmlspecialchars($this->notificationId); ?>"
             class="<?php echo htmlspecialchars($this->config['classes']['wrapper']); ?> notification-<?php echo htmlspecialchars($this->config['type']); ?> notification-<?php echo htmlspecialchars($this->config['position']); ?>"
             data-component="notification"
             data-component-id="<?php echo htmlspecialchars($this->notificationId); ?>"
             data-config='<?php echo json_encode($this->config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-type="<?php echo htmlspecialchars($this->config['type']); ?>"
             data-auto-hide="<?php echo $this->config['autoHide'] ? 'true' : 'false'; ?>"
             data-hide-delay="<?php echo intval($this->config['hideDelay']); ?>"
             data-auto-init="true"
             style="display: none;">

	<div class="<?php echo htmlspecialchars($this->config['classes']['icon']); ?>">
		<?php echo $icon; ?>
	</div>

	<div class="<?php echo htmlspecialchars($this->config['classes']['content']); ?>">
		<?php
		if ($this->config['title']) : ?>
		<div class="<?php echo htmlspecialchars($this->config['classes']['title']); ?>">
			<?php echo htmlspecialchars($this->config['title']); ?>
		</div>
		<?php
	endif; ?>

		<div class="<?php echo htmlspecialchars($this->config['classes']['message']); ?>">
			<?php echo htmlspecialchars($this->config['message']); ?>
		</div>
	</div>

	<?php
	if ($this->config['closable']) : ?>
	<button class="<?php echo htmlspecialchars($this->config['classes']['close']); ?>"
                        data-action="close-notification"
                        data-target="#<?php echo htmlspecialchars($this->notificationId); ?>">
		&times;
	</button>
	<?php
endif; ?>
</div>
<?php
}

public function setMessage($message, $title = '')
{
	$this->config['message'] = $message;
	if ($title) {
		$this->config['title'] = $title;
	}
	return $this;
}

public function setType($type)
{
	$this->config['type'] = $type;
	return $this;
}

public static function create($config = [])
{
	return new self($config);
}

public static function render($config = [])
{
	$notification = new self($config);
	$notification->render();
}

public static function success($message, $title = '', $config = [])
{
	$notification = new self(array_merge(['type' => 'success', 'message' => $message, 'title' => $title], $config));
	$notification->render();
}

public static function error($message, $title = '', $config = [])
{
	$notification = new self(array_merge(['type' => 'error', 'message' => $message, 'title' => $title], $config));
	$notification->render();
}

public static function warning($message, $title = '', $config = [])
{
	$notification = new self(array_merge(['type' => 'warning', 'message' => $message, 'title' => $title], $config));
	$notification->render();
}

public static function info($message, $title = '', $config = [])
{
	$notification = new self(array_merge(['type' => 'info', 'message' => $message, 'title' => $title], $config));
	$notification->render();
}
}