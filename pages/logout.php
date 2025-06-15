<?php
session_start();
include_once('../basedados/basedados.h');

// Deactivate session in database if session token exists
if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
    $conn = connectDatabase();
    $user_id = $_SESSION['user_id'];
    $session_token = $_SESSION['session_token'];
    
    // Mark session as inactive
    $sql = "UPDATE active_sessions SET is_active = 0 
            WHERE user_id = ? AND session_token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $session_token);
    $stmt->execute();
    
    $conn->close();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
?> 