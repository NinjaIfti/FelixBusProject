<?php
/**
 * Admin security check script
 * Include this at the top of all admin pages to ensure proper security
 */

include_once('../basedados/basedados.h');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'staff')) {
    header("Location: login.php");
    exit;
}

// Validate session security
$security_check = validateSessionSecurity();
if ($security_check !== true) {
    // Clear session data
    $_SESSION = array();
    session_destroy();
    
    // Set a temporary message to display after redirect
    session_start();
    $_SESSION['security_error'] = $security_check;
    
    // Redirect to login
    header("Location: login.php");
    exit;
}

// Periodically clean up old sessions (once per 100 page loads on average)
if (rand(1, 100) === 1) {
    $conn = connectDatabase();
    // Delete sessions inactive for more than 24 hours
    $cleanup_sql = "DELETE FROM active_sessions WHERE is_active = 0 AND last_activity < NOW() - INTERVAL 24 HOUR";
    $conn->query($cleanup_sql);
    $conn->close();
}
?> 