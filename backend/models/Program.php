<?php
/**
 * Program Model Class for itmdev
 * Location: backend/models/Program.php
 * 
 * Manages programs and program types in the ice time management system
 */

class Program {
    private $pdo;
    private $calendarUpdate;
    
    public function __construct($pdo, $calendarUpdate = null) {
        $this->pdo = $pdo;
        $this->calendarUpdate = $calendarUpdate;
    }
    
    /**
     * Get all programs with their types and facilities
     * 
     * @return array Array of programs
     */
    public function getAllPrograms() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    p.program_ID as id,
                    p.program_Name as name,
                    p.program_Desc as description,
                    p.program_Color as color,
                    p.program_Contact_Name as contact_name,
                    p.program_Contact_Email as contact_email,
                    p.program_Contact_Phone as contact_phone,
                    p.program_Status as status,
                    p.create_ts as created_at,
                    p.update_ts as updated_at,
                    pt.program_Type_Name as program_type_name,
                    pt.program_Type_ID as program_type_id,
                    f.facility_Name as facility_name,
                    f.facility_ID as facility_id
                FROM program p
                LEFT JOIN program_type pt ON p.program_Type_ID = pt.program_Type_ID
                LEFT JOIN facility f ON p.facility_ID = f.facility_ID
                ORDER BY p.program_Name ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching programs: " . $e->getMessage());
            throw new Exception("Failed to fetch programs");
        }
    }
    
    /**
     * Get all programs with statistics
     * 
     * @return array Array of programs with stats
     */
    public function getAllProgramsWithStats() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    p.program_ID as id,
                    p.program_Name as name,
                    p.program_Desc as description,
                    p.program_Color as color,
                    p.program_Contact_Name as contact_name,
                    p.program_Contact_Email as contact_email,
                    p.program_Contact_Phone as contact_phone,
                    p.program_Status as status,
                    p.create_ts as created_at,
                    p.update_ts as updated_at,
                    pt.program_Type_Name as program_type_name,
                    pt.program_Type_ID as program_type_id,
                    f.facility_Name as facility_name,
                    f.facility_ID as facility_id,
                    COUNT(DISTINCT e.episode_ID) as episode_count,
                    COUNT(DISTINCT t.team_ID) as team_count
                FROM program p
                LEFT JOIN program_type pt ON p.program_Type_ID = pt.program_Type_ID
                LEFT JOIN facility f ON p.facility_ID = f.facility_ID
                LEFT JOIN episode e ON p.program_ID = e.program_ID
                LEFT JOIN team t ON p.program_ID = t.program_ID
                GROUP BY p.program_ID, p.program_Name, p.program_Desc, p.program_Color, 
                         p.program_Contact_Name, p.program_Contact_Email, p.program_Contact_Phone,
                         p.program_Status, p.create_ts, p.update_ts, pt.program_Type_Name, 
                         pt.program_Type_ID, f.facility_Name, f.facility_ID
                ORDER BY p.program_Name ASC
            ");
            
            $programs = $stmt->fetchAll();
            
            // Calculate additional statistics
            $stats = $this->getProgramStats();
            
            return [
                'success' => true,
                'data' => $programs,
                'stats' => $stats
            ];
        } catch (PDOException $e) {
            error_log("Error fetching programs with stats: " . $e->getMessage());
            throw new Exception("Failed to fetch programs with statistics");
        }
    }
    
    /**
     * Get program statistics
     * 
     * @return array Program statistics
     */
    public function getProgramStats() {
        try {
            // Total programs
            $totalPrograms = $this->pdo->query("SELECT COUNT(*) FROM program")->fetchColumn();
            
            // Active programs
            $activePrograms = $this->pdo->query("
                SELECT COUNT(*) FROM program WHERE program_Status = 'Active'
            ")->fetchColumn();
            
            // Programs with recent episodes (last 30 days)
            $recentPrograms = $this->pdo->query("
                SELECT COUNT(DISTINCT p.program_ID) 
                FROM program p 
                JOIN episode e ON p.program_ID = e.program_ID 
                WHERE e.episode_Start_Date_Time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ")->fetchColumn();
            
            // Total program types
            $totalProgramTypes = $this->pdo->query("SELECT COUNT(*) FROM program_type")->fetchColumn();
            
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
    
    /**
     * Get programs for DataTable with pagination and search
     * 
     * @param int $start Starting offset
     * @param int $length Number of records to return
     * @param string $searchValue Search term
     * @param string $orderBy Column to order by
     * @param string $orderDir Order direction (asc/desc)
     * @param array|null $programTypeIds Filter by program type IDs
     * @return array DataTable formatted result
     */
    public function getProgramsForDataTable($start, $length, $searchValue = '', $orderBy = 'program_Name', $orderDir = 'asc', $programTypeIds = null) {
        try {
            // Base query
            $baseQuery = "
                FROM program p
                LEFT JOIN program_type pt ON p.program_Type_ID = pt.program_Type_ID
                LEFT JOIN facility f ON p.facility_ID = f.facility_ID
            ";
            
            $whereConditions = [];
            $params = [];
            
            // Search filter
            if (!empty($searchValue)) {
                $whereConditions[] = "(
                    p.program_Name LIKE :search OR 
                    p.program_Desc LIKE :search OR 
                    pt.program_Type_Name LIKE :search OR 
                    f.facility_Name LIKE :search OR
                    p.program_Contact_Name LIKE :search
                )";
                $params[':search'] = '%' . $searchValue . '%';
            }
            
            // Program type filter
            if ($programTypeIds && !empty($programTypeIds)) {
                $placeholders = [];
                foreach ($programTypeIds as $index => $id) {
                    $placeholder = ":type_id_$index";
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $id;
                }
                $whereConditions[] = "p.program_Type_ID IN (" . implode(',', $placeholders) . ")";
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count
            $countQuery = "SELECT COUNT(*) " . $baseQuery . $whereClause;
            $countStmt = $this->pdo->prepare($countQuery);
            $countStmt->execute($params);
            $totalFiltered = $countStmt->fetchColumn();
            
            // Get total count without search
            $totalQuery = "SELECT COUNT(*) FROM program";
            $totalCount = $this->pdo->query($totalQuery)->fetchColumn();
            
            // Valid columns for ordering
            $validColumns = [
                'program_Name' => 'p.program_Name',
                'program_type_name' => 'pt.program_Type_Name',
                'facility_name' => 'f.facility_Name',
                'program_Status' => 'p.program_Status',
                'create_ts' => 'p.create_ts'
            ];
            
            $orderColumn = $validColumns[$orderBy] ?? 'p.program_Name';
            $orderDirection = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            
            // Main data query
            $dataQuery = "
                SELECT 
                    p.program_ID as id,
                    p.program_Name as name,
                    p.program_Desc as description,
                    p.program_Color as color,
                    p.program_Contact_Name as contact_name,
                    p.program_Contact_Email as contact_email,
                    p.program_Contact_Phone as contact_phone,
                    p.program_Status as status,
                    p.create_ts as created_at,
                    p.update_ts as updated_at,
                    pt.program_Type_Name as program_type_name,
                    pt.program_Type_ID as program_type_id,
                    f.facility_Name as facility_name,
                    f.facility_ID as facility_id
                " . $baseQuery . $whereClause . "
                ORDER BY {$orderColumn} {$orderDirection}
                LIMIT :start, :length
            ";
            
            $params[':start'] = (int)$start;
            $params[':length'] = (int)$length;
            
            $stmt = $this->pdo->prepare($dataQuery);
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            return [
                'data' => $data,
                'total' => (int)$totalCount,
                'filtered' => (int)$totalFiltered
            ];
            
        } catch (PDOException $e) {
            error_log("Error in getProgramsForDataTable: " . $e->getMessage());
            throw new Exception("Failed to fetch programs for data table");
        }
    }
    
    /**
     * Create a new program
     * 
     * @param array $data Program data
     * @return array Created program
     */
    public function createProgram($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO program (
                    program_Name, program_Desc, program_Type_ID, facility_ID,
                    program_Color, program_Contact_Name, program_Contact_Email,
                    program_Contact_Phone, program_Status, created_by
                ) VALUES (
                    :name, :description, :program_type_id, :facility_id,
                    :color, :contact_name, :contact_email, 
                    :contact_phone, :status, :created_by
                )
            ");
            
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':program_type_id' => $data['program_type_id'],
                ':facility_id' => $data['facility_id'] ?? null,
                ':color' => $data['color'] ?? '#3498db',
                ':contact_name' => $data['contact_name'] ?? null,
                ':contact_email' => $data['contact_email'] ?? null,
                ':contact_phone' => $data['contact_phone'] ?? null,
                ':status' => $data['status'] ?? 'Active',
                ':created_by' => 'api_user'
            ]);
            
            $programId = $this->pdo->lastInsertId();
            
            // Broadcast update
            if ($this->calendarUpdate) {
                $this->calendarUpdate->recordUpdate('program_created', [
                    'program_id' => $programId,
                    'program_name' => $data['name']
                ]);
            }
            
            return $this->getProgramById($programId);
            
        } catch (PDOException $e) {
            error_log("Error creating program: " . $e->getMessage());
            throw new Exception("Failed to create program");
        }
    }
    
    /**
     * Update an existing program
     * 
     * @param int $programId Program ID
     * @param array $data Program data
     * @return array Updated program
     */
    public function updateProgram($programId, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE program SET
                    program_Name = :name,
                    program_Desc = :description,
                    program_Type_ID = :program_type_id,
                    facility_ID = :facility_id,
                    program_Color = :color,
                    program_Contact_Name = :contact_name,
                    program_Contact_Email = :contact_email,
                    program_Contact_Phone = :contact_phone,
                    program_Status = :status,
                    updated_by = :updated_by
                WHERE program_ID = :program_id
            ");
            
            $stmt->execute([
                ':program_id' => $programId,
                ':name' => $data['name'],
                ':description' => $data['description'] ?? null,
                ':program_type_id' => $data['program_type_id'],
                ':facility_id' => $data['facility_id'] ?? null,
                ':color' => $data['color'] ?? '#3498db',
                ':contact_name' => $data['contact_name'] ?? null,
                ':contact_email' => $data['contact_email'] ?? null,
                ':contact_phone' => $data['contact_phone'] ?? null,
                ':status' => $data['status'] ?? 'Active',
                ':updated_by' => 'api_user'
            ]);
            
            // Broadcast update
            if ($this->calendarUpdate) {
                $this->calendarUpdate->recordUpdate('program_updated', [
                    'program_id' => $programId,
                    'program_name' => $data['name']
                ]);
            }
            
            return $this->getProgramById($programId);
            
        } catch (PDOException $e) {
            error_log("Error updating program: " . $e->getMessage());
            throw new Exception("Failed to update program");
        }
    }
    
    /**
     * Delete a program
     * 
     * @param int $programId Program ID
     * @return bool Success status
     */
    public function deleteProgram($programId) {
        try {
            // Check if program has episodes
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM episode WHERE program_ID = :program_id");
            $stmt->execute([':program_id' => $programId]);
            $episodeCount = $stmt->fetchColumn();
            
            if ($episodeCount > 0) {
                throw new Exception("Cannot delete program: it has associated episodes");
            }
            
            // Get program name for broadcast
            $program = $this->getProgramById($programId);
            
            $stmt = $this->pdo->prepare("DELETE FROM program WHERE program_ID = :program_id");
            $stmt->execute([':program_id' => $programId]);
            
            // Broadcast update
            if ($this->calendarUpdate && $program) {
                $this->calendarUpdate->recordUpdate('program_deleted', [
                    'program_id' => $programId,
                    'program_name' => $program['name']
                ]);
            }
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Error deleting program: " . $e->getMessage());
            throw new Exception("Failed to delete program");
        }
    }
    
    /**
     * Get program by ID
     * 
     * @param int $programId Program ID
     * @return array|null Program data
     */
    public function getProgramById($programId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.program_ID as id,
                    p.program_Name as name,
                    p.program_Desc as description,
                    p.program_Color as color,
                    p.program_Contact_Name as contact_name,
                    p.program_Contact_Email as contact_email,
                    p.program_Contact_Phone as contact_phone,
                    p.program_Status as status,
                    p.create_ts as created_at,
                    p.update_ts as updated_at,
                    pt.program_Type_Name as program_type_name,
                    pt.program_Type_ID as program_type_id,
                    f.facility_Name as facility_name,
                    f.facility_ID as facility_id
                FROM program p
                LEFT JOIN program_type pt ON p.program_Type_ID = pt.program_Type_ID
                LEFT JOIN facility f ON p.facility_ID = f.facility_ID
                WHERE p.program_ID = :program_id
            ");
            
            $stmt->execute([':program_id' => $programId]);
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Error fetching program by ID: " . $e->getMessage());
            throw new Exception("Failed to fetch program");
        }
    }
    
    /**
     * Get all program types
     * 
     * @return array Array of program types
     */
    public function getAllProgramTypes() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    program_Type_ID as id,
                    program_Type_Name as name,
                    program_Type_Desc as description,
                    create_ts as created_at
                FROM program_type
                ORDER BY program_Type_Name ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching program types: " . $e->getMessage());
            throw new Exception("Failed to fetch program types");
        }
    }
    
    /**
     * Get all facilities
     * 
     * @return array Array of facilities
     */
    public function getAllFacilities() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    facility_ID as id,
                    facility_Name as name,
                    facility_City as city,
                    facility_State as state,
                    facility_Status as status
                FROM facility
                WHERE facility_Status = 'Active'
                ORDER BY facility_Name ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching facilities: " . $e->getMessage());
            throw new Exception("Failed to fetch facilities");
        }
    }
}
?>