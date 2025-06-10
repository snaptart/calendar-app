/**
* FileUpload Component - HTML Generation Only
* Location: frontend/components/ui/FileUpload.php
*/
class FileUpload {
private $config;
private $uploadId;

private $defaultConfig = [
'uploadId' => 'fileUpload',
'multiple' => false,
'dragDrop' => true,
'autoUpload' => false,
'showProgress' => true,
'maxFileSize' => 5242880, // 5MB
'allowedTypes' => ['.jpg', '.png', '.pdf', '.csv', '.json', '.ics'],
'uploadUrl' => '/api/upload',
'method' => 'POST',
'fieldName' => 'file',
'classes' => [
'wrapper' => 'file-upload-wrapper',
'dropzone' => 'file-dropzone',
'input' => 'file-input',
'preview' => 'file-preview',
'progress' => 'upload-progress'
],
'text' => [
'dropzone' => 'Drag & drop files here or click to browse',
'browse' => 'Browse Files',
'uploading' => 'Uploading...',
'success' => 'Upload completed successfully',
'error' => 'Upload failed'
]
];

public function __construct($config = []) {
$this->config = array_merge_recursive($this->defaultConfig, $config);
$this->uploadId = $this->config['uploadId'];
}

public function render() {
?>
<div id="<?php echo htmlspecialchars($this->uploadId); ?>"
             class="<?php echo htmlspecialchars($this->config['classes']['wrapper']); ?>"
             data-component="upload"
             data-component-id="<?php echo htmlspecialchars($this->uploadId); ?>"
             data-config='<?php echo json_encode($this->config, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'
             data-upload-url="<?php echo htmlspecialchars($this->config['uploadUrl']); ?>"
             data-max-file-size="<?php echo intval($this->config['maxFileSize']); ?>"
             data-allowed-types="<?php echo htmlspecialchars(implode(',', $this->config['allowedTypes'])); ?>"
             data-multiple="<?php echo $this->config['multiple'] ? 'true' : 'false'; ?>"
             data-drag-drop="<?php echo $this->config['dragDrop'] ? 'true' : 'false'; ?>"
             data-auto-upload="<?php echo $this->config['autoUpload'] ? 'true' : 'false'; ?>"
             data-show-progress="<?php echo $this->config['showProgress'] ? 'true' : 'false'; ?>"
             data-auto-init="true">

	<div class="<?php echo htmlspecialchars($this->config['classes']['dropzone']); ?>"
                 data-dropzone="true">
		<div class="dropzone-content">
			<div class="dropzone-icon">
				📁
			</div>
			<div class="dropzone-text">
				<?php echo htmlspecialchars($this->config['text']['dropzone']); ?>
			</div>
			<button type="button" class="btn btn-primary btn-small">
				<?php echo htmlspecialchars($this->config['text']['browse']); ?>
			</button>
		</div>

		<input type="file"
		class="<?php echo htmlspecialchars($this->config['classes']['input']); ?>"
		name="<?php echo htmlspecialchars($this->config['fieldName']); ?>"
		<?php echo $this->config['multiple'] ? 'multiple' : ''; ?>
		accept="<?php echo htmlspecialchars(implode(',', $this->config['allowedTypes'])); ?>"
		style="display: none;">
	</div>

	<div class="<?php echo htmlspecialchars($this->config['classes']['preview']); ?>"
                 data-file-preview="true"
                 style="display: none;">
		<!-- File preview will be populated by JavaScript -->
	</div>

	<?php
	if ($this->config['showProgress']) : ?>
	<div class="<?php echo htmlspecialchars($this->config['classes']['progress']); ?>"
                     data-upload-progress="true"
                     style="display: none;">
		<div class="progress-bar">
			<div class="progress-fill" style="width: 0%">
			</div>
		</div>
		<div class="progress-text">
			0%
		</div>
	</div>
	<?php
endif; ?>
</div>
<?php
}

public static function create($config = [])
{
	return new self($config);
}

public static function render($config = [])
{
	$upload = new self($config);
	$upload->render();
}
}