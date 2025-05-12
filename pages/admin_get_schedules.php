<?php
session_start();
include_once('../../database/basedados.h');

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Connect to database
$conn = connectDatabase();

// Check if route_id is provided
if(!isset($_GET['route_id']) || !is_numeric($_GET['route_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid route ID']);
    exit;
}

$route_id = intval($_GET['route_id']);

// Get route details
$route_query = "SELECT * FROM routes WHERE id = $route_id";
$route_result = $conn->query($route_query);

if($route_result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Route not found']);
    exit;
}

$route = $route_result->fetch_assoc();

// Get schedules for this route
$schedules_query = "SELECT * FROM schedules WHERE route_id = $route_id ORDER BY departure_time";
$schedules_result = $conn->query($schedules_query);

$schedules = [];
if($schedules_result->num_rows > 0) {
    while($schedule = $schedules_result->fetch_assoc()) {
        // Format times for display
        $schedule['departure_time'] = date('g:i A', strtotime($schedule['departure_time']));
        $schedule['arrival_time'] = date('g:i A', strtotime($schedule['arrival_time']));
        $schedules[] = $schedule;
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode([
    'route' => $route,
    'schedules' => $schedules
]);

$conn->close(); 