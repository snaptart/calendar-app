<?php
/**
 * Program Management API Endpoints
 * Location: backend/endpoints/program-endpoints.php
 * 
 * Contains all program-related API endpoints that will be included in api.php
 */

// This file contains program endpoints that should be added to the main API

// Add these cases to the GET handler:
/*
case 'programs':
    $auth->requireAuth();
    $programs = $programModel->getAllPrograms();
    sendResponse($programs);
    break;
    
case 'programs_with_stats':
    $auth->requireAuth();
    $programs = $programModel->getAllProgramsWithStats();
    sendResponse($programs);
    break;
    
case 'programs_datatable':
    $auth->requireAuth();
    
    // Get DataTables parameters
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 25);
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    // Get ordering parameters
    $orderColumn = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 1;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'asc';
    
    // Map column numbers to database fields
    $columns = ['program_Name', 'program_type_name', 'facility_name', 'program_Status', 'create_ts'];
    $orderBy = $columns[$orderColumn] ?? 'program_Name';
    
    // Get program type filter if provided
    $programTypeIds = $_GET['program_type_ids'] ?? '';
    $programTypeIdsArray = null;
    
    if ($programTypeIds) {
        $programTypeIdsArray = array_filter(explode(',', $programTypeIds), 'is_numeric');
        if (empty($programTypeIdsArray)) {
            sendResponse([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
            return;
        }
    }
    
    // Get paginated programs data
    $result = $programModel->getProgramsForDataTable($start, $length, $searchValue, $orderBy, $orderDir, $programTypeIdsArray);
    
    sendResponse([
        'draw' => $draw,
        'recordsTotal' => $result['total'],
        'recordsFiltered' => $result['filtered'],
        'data' => $result['data']
    ]);
    break;
    
case 'program_types':
    $auth->requireAuth();
    $programTypes = $programModel->getAllProgramTypes();
    sendResponse($programTypes);
    break;
    
case 'facilities':
    $auth->requireAuth();
    $facilities = $programModel->getAllFacilities();
    sendResponse($facilities);
    break;
*/

// Add these cases to the POST handler:
/*
case 'create_program':
    $auth->requireAuth();
    
    validateRequiredFields($input, ['name', 'program_type_id']);
    
    $program = $programModel->createProgram($input);
    sendResponse($program, 201);
    break;
*/

// Add these cases to the PUT handler:
/*
case 'update_program':
    $auth->requireAuth();
    
    validateRequiredFields($input, ['id', 'name', 'program_type_id']);
    
    $programId = (int)$input['id'];
    if ($programId <= 0) {
        throw new Exception('Invalid program ID');
    }
    
    $program = $programModel->updateProgram($programId, $input);
    sendResponse($program);
    break;
*/

// Add these cases to the DELETE handler:
/*
case 'delete_program':
    $auth->requireAuth();
    
    $programId = $_GET['id'] ?? null;
    
    if (!$programId || !is_numeric($programId)) {
        throw new Exception('Missing or invalid program ID');
    }
    
    $programId = (int)$programId;
    if ($programId <= 0) {
        throw new Exception('Invalid program ID');
    }
    
    $success = $programModel->deleteProgram($programId);
    
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Program deleted successfully']);
    } else {
        throw new Exception('Failed to delete program');
    }
    break;
*/

?>