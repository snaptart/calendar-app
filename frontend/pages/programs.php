<?php
// Programs page - Program management and overview
// This page is included by index.php, so all variables and authentication are already available

// Include the shared data section header component
require_once __DIR__ . '/../components/data-section-header.php';

// Calculate program stats server-side
function getProgramStats($pdo) {
    try {
        // Total programs
        $totalPrograms = $pdo->query("SELECT COUNT(*) FROM program")->fetchColumn();
        
        // Active programs
        $activePrograms = $pdo->query("
            SELECT COUNT(*) 
            FROM program 
            WHERE program_Status = 'Active'
        ")->fetchColumn();
        
        // Programs with recent episodes (last 30 days)
        $recentPrograms = $pdo->query("
            SELECT COUNT(DISTINCT p.program_ID) 
            FROM program p 
            JOIN episode e ON p.program_ID = e.program_ID 
            WHERE e.episode_Start_Date_Time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetchColumn();
        
        // Total program types
        $totalProgramTypes = $pdo->query("SELECT COUNT(*) FROM program_type")->fetchColumn();
        
        return [
            'total' => $totalPrograms ?: 0,
            'active' => $activePrograms ?: 0,
            'recent' => $recentPrograms ?: 0,
            'types' => $totalProgramTypes ?: 0
        ];
    } catch (Exception $e) {
        error_log("Error calculating program stats: " . $e->getMessage());
        return ['total' => 0, 'active' => 0, 'recent' => 0, 'types' => 0];
    }
}

$programStats = getProgramStats($pdo);
?>

<script>
// Real-time program statistics updates
document.addEventListener('DOMContentLoaded', function() {
    // Function to update program stats in real-time
    const updateProgramStats = async () => {
        try {
            const response = await fetch('backend/api.php?action=programs_with_stats');
            const data = await response.json();
            
            if (data.success && data.stats) {
                document.querySelector('.stat-item:nth-child(1) .stat-number').textContent = data.stats.total || 0;
                document.querySelector('.stat-item:nth-child(2) .stat-number').textContent = data.stats.active || 0;
                document.querySelector('.stat-item:nth-child(3) .stat-number').textContent = data.stats.recent || 0;
                document.querySelector('.stat-item:nth-child(4) .stat-number').textContent = data.stats.types || 0;
            }
        } catch (error) {
            console.error('Error updating program stats:', error);
        }
    };
    
    // Listen for program-related events to update stats
    if (window.EventBus) {
        EventBus.on('programs:refresh', updateProgramStats);
        EventBus.on('programs:updated', updateProgramStats);
        EventBus.on('program:created', updateProgramStats);
        EventBus.on('program:deleted', updateProgramStats);
    }
});
</script>

<div class="stats-section" style="display: none;">
    <div class="stats-grid">
        <div class="stat-item">
            <div class="stat-number"><?php echo $programStats['total']; ?></div>
            <div class="stat-label">Total Programs</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $programStats['active']; ?></div>
            <div class="stat-label">Active Programs</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $programStats['recent']; ?></div>
            <div class="stat-label">Recent Activity</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $programStats['types']; ?></div>
            <div class="stat-label">Program Types</div>
        </div>
    </div>
</div>

<?php renderProgramsPageHeader(); ?>

<div class="table-container">
    <table id="programsTable" class="display">
        <thead>
            <tr>
                <th>Color</th>
                <th>Program Name</th>
                <th>Type</th>
                <th>Facility</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Episodes</th>
                <th>Teams</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="programsTableBody">
            <!-- Data will be populated by JavaScript -->
        </tbody>
    </table>
</div>

<!-- Program Modal -->
<div id="programModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Program Details</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <!-- Program Details View -->
            <div id="programDetails">
                <!-- Content populated by JavaScript -->
            </div>
            
            <!-- Program Edit Form -->
            <div id="programForm" style="display: none;">
                <form id="editProgramForm">
                    <input type="hidden" id="programId">
                    
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="editProgramName">Program Name</label>
                            <input type="text" id="editProgramName" placeholder="Enter program name" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="programColor">Color</label>
                            <div class="color-picker">
                                <input type="color" id="programColor" value="#3498db">
                                <span class="color-preview" id="colorPreview"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="programType">Program Type</label>
                            <select id="programType" required>
                                <option value="">Select a program type</option>
                                <!-- Options populated by JavaScript -->
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="programFacility">Facility</label>
                            <select id="programFacility">
                                <option value="">No Facility</option>
                                <!-- Options populated by JavaScript -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="programDescription">Description</label>
                        <textarea id="programDescription" rows="3" placeholder="Enter program description"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="contactName">Contact Name</label>
                            <input type="text" id="contactName" placeholder="Enter contact name">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contactEmail">Contact Email</label>
                            <input type="email" id="contactEmail" placeholder="Enter contact email">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="contactPhone">Contact Phone</label>
                            <input type="tel" id="contactPhone" placeholder="Enter contact phone">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="programStatus">Status</label>
                        <select id="programStatus" required>
                            <option value="">Select status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Program</button>
                        <button type="button" class="btn btn-outline" id="cancelEditBtn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

