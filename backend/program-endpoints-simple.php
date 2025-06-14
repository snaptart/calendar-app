<?php
/**
 * Minimal Program API Endpoints
 * Include this in api.php GET handler before default case
 */

// Add these endpoints to handleGetRequest function:

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

// Add to handlePostRequest function:

case 'create_program':
    $auth->requireAuth();
    validateRequiredFields($input, ['name', 'program_type_id']);
    $program = $programModel->createProgram($input);
    sendResponse($program, 201);
    break;

?>