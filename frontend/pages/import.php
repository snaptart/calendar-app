<?php
/**
* Import Page - Using new modular architecture
*/

// Include service layer
require_once __DIR__ . '/../services/ConfigService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../layouts/AppLayout.php';
require_once __DIR__ . '/../components/forms/ImportForm.php';

// Initialize services
$config = ConfigService::getInstance();
$auth = AuthService::guard(true); // Require authentication

// Get page configuration
$pageConfig = $config->forLayout('import', [
	'pageTitle' => '📥 Import Events',
	'activeNavItem' => 'import',
	'breadcrumbs' => [
		['title' => 'Calendar', 'url' => './calendar.php'],
		['title' => 'Import Events']
	]
]);

// Render the import page
AppLayout::createPage($pageConfig, function() {
?>
<!-- Import Instructions -->
<div class="import-instructions">
	<div class="instruction-content">
		<h3>
			📋 Import Instructions
		</h3>
		<div class="instruction-grid">
			<div class="instruction-item">
				<div class="instruction-icon">
					📄
				</div>
				<div class="instruction-text">
					<h4>
						Supported Formats
					</h4>
					<p>
						JSON, CSV, and iCalendar (.ics) files
					</p>
				</div>
			</div>
			<div class="instruction-item">
				<div class="instruction-icon">
					📊
				</div>
				<div class="instruction-text">
					<h4>
						File Limits
					</h4>
					<p>
						Maximum 5MB file size, up to 20 events
					</p>
				</div>
			</div>
			<div class="instruction-item">
				<div class="instruction-icon">
					👥
				</div>
				<div class="instruction-text">
					<h4>
						User Matching
					</h4>
					<p>
						Events must reference existing user names
					</p>
				</div>
			</div>
			<div class="instruction-item">
				<div class="instruction-icon">
					⏰
				</div>
				<div class="instruction-text">
					<h4>
						Future Events Only
					</h4>
					<p>
						Only future-dated events will be imported
					</p>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Import Form -->
<div class="import-form-container">
	<div class="import-form-content">
		<h3>
			Select File to Import
		</h3>

		<?php ImportForm::renderImportForm(); ?>
	</div>
</div>

<!-- Results Section -->
<div id="resultsSection" class="results-section" style="display: none;">
	<div class="results-content">
		<h3 id="resultsTitle">
			Import Results
		</h3>
		<div id="resultsBody">
			<!-- Results will be populated here -->
		</div>
	</div>
</div>

<!-- Sample Files Section -->
<div class="sample-files-section">
	<div class="sample-files-content">
		<h3>
			📚 Sample Files & Format Guide
		</h3>
		<div class="format-tabs">
			<button class="format-tab active" data-format="json">
				JSON Format
			</button>
			<button class="format-tab" data-format="csv">
				CSV Format
			</button>
			<button class="format-tab" data-format="ics">
				iCalendar Format
			</button>
		</div>

		<div class="format-examples">
			<div id="jsonExample" class="format-example active">
				<h4>
					JSON Format Example
				</h4>
				<pre>
					<code>[
					{
					"title": "Team Meeting",
					"start": "2025-06-15 10:00:00",
					"end": "2025-06-15 11:00:00",
					"user_name": "John Doe",
					"description": "Weekly team sync"
					}
					]</code>
				</pre>
			</div>

			<div id="csvExample" class="format-example">
				<h4>
					CSV Format Example
				</h4>
				<pre>
					<code>title,start,end,user_name,description
					Team Meeting,2025-06-15 10:00:00,2025-06-15 11:00:00,John Doe,Weekly team sync</code>
				</pre>
			</div>

			<div id="icsExample" class="format-example">
				<h4>
					iCalendar (.ics) Format Example
				</h4>
				<pre>
					<code>BEGIN:VCALENDAR
					VERSION:2.0
					BEGIN:VEVENT
					SUMMARY:Team Meeting
					DTSTART:20250615T100000Z
					DTEND:20250615T110000Z
					ORGANIZER:John Doe
					END:VEVENT
					END:VCALENDAR</code>
				</pre>
			</div>
		</div>
	</div>
</div>
<?php
});

// Add authentication status to JavaScript
?>
<script>
	<?php echo $auth->generateAuthJs(); ?>
</script>