<?php
session_start();
include_once('../../database/basedados.h');

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

if(!isset($_GET['alert_id']) || !is_numeric($_GET['alert_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid alert ID']);
    exit;
}

$conn = connectDatabase();
$alert_id = intval($_GET['alert_id']);

// Fetch alert details
$query = "SELECT * FROM alerts WHERE id = $alert_id";
$result = $conn->query($query);

if($result && $result->num_rows > 0) {
    $alert = $result->fetch_assoc();
    
    // Format dates for the response
    if($alert['start_date']) {
        $alert['start_date'] = date('Y-m-d', strtotime($alert['start_date']));
    }
    
    if($alert['end_date']) {
        $alert['end_date'] = date('Y-m-d', strtotime($alert['end_date']));
    }
    
    header('Content-Type: application/json');
    echo json_encode($alert);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Alert not found']);
}

$conn->close();
?> 