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

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($route);

$conn->close(); 