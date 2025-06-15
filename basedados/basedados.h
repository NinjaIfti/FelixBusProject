<?php
/**
 * Database connection utility for FelixBus
 * Provides standard connection to database
 */

/**
 * Connect to the FelixBus database
 * 
 * @return mysqli Database connection object
 */
function connectDatabase() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "felixbus";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Generate a unique session token
 * 
 * @return string Unique session token
 */
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Check if session is valid and secure
 * Prevents session hijacking and enforces single user login
 * 
 * @return bool|string Returns true if session is valid, otherwise returns error message
 */
function validateSessionSecurity() {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
        return "Session expired. Please log in again.";
    }
    
    // Check if session token exists
    if (!isset($_SESSION['session_token']) || !isset($_SESSION['last_activity'])) {
        return "Invalid session. Please log in again.";
    }
    
    // Check for session timeout (30 minutes)
    $timeout = 30 * 60; // 30 minutes in seconds
    if (time() - $_SESSION['last_activity'] > $timeout) {
        return "Session expired due to inactivity. Please log in again.";
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Verify session token in database
    $conn = connectDatabase();
    $user_id = $_SESSION['user_id'];
    $session_token = $_SESSION['session_token'];
    
    $query = "SELECT id FROM active_sessions WHERE user_id = ? AND session_token = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $session_token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $conn->close();
        return "Your session is no longer valid. Another user has logged in.";
    }
    
    $conn->close();
    return true;
}
?> 