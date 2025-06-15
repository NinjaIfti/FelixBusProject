<?php
/**
 * AJAX endpoint for session validation
 * Used by admin pages to check if session is still valid
 */

// Include database connections
include_once('../basedados/basedados.h');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header for JSON response
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Check if action is set
if (!isset($input['action']) || $input['action'] !== 'check_session') {
    echo json_encode(['valid' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate session
$security_check = validateSessionSecurity();

// Return response
if ($security_check === true) {
    echo json_encode(['valid' => true]);
} else {
    echo json_encode(['valid' => false, 'message' => $security_check]);
}
exit;
?> 