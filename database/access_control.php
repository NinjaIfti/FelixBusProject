<?php
/**
 * Access control utility for FelixBus
 * Handles page protection and unauthorized access attempts
 */

/**
 * Checks if the current user has permission to access a page
 * Redirects with access denied alert if unauthorized
 * 
 * @param array $allowed_types Array of allowed user types (e.g. ['admin', 'staff'])
 * @return void
 */
function checkPageAccess($allowed_types = []) {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        // Not logged in, redirect to login
        redirectWithAlert('login.php', 'Unauthorized access. Please log in first.', 'error');
        exit;
    }

    // Check if user type is allowed
    if (!empty($allowed_types) && !in_array($_SESSION['user_type'], $allowed_types)) {
        // User type not allowed to access this page
        redirectWithAlert('index.php', 'Unauthorized access. You do not have permission to view this page.', 'error');
        exit;
    }
}

/**
 * Redirects to specified page with alert message in session
 * 
 * @param string $page Page to redirect to
 * @param string $message Alert message
 * @param string $type Alert type (error, warning, info, success)
 * @return void
 */
function redirectWithAlert($page, $message, $type = 'error') {
    // Store alert in session
    $_SESSION['alert_message'] = $message;
    $_SESSION['alert_type'] = $type;

    // Redirect
    header("Location: $page");
    exit;
}

/**
 * Displays alert message if exists in session and clears it after display
 * 
 * @return string HTML for alert or empty string if no alert
 */
function displayAlert() {
    $html = '';
    
    // Check if alert exists in session
    if (isset($_SESSION['alert_message'])) {
        $message = $_SESSION['alert_message'];
        $type = $_SESSION['alert_type'] ?? 'info';
        
        // Generate alert HTML based on type
        $color_class = '';
        $icon = '';
        
        switch ($type) {
            case 'error':
                $color_class = 'bg-red-100 border-red-500 text-red-700';
                $icon = '<i class="fas fa-exclamation-circle mr-2"></i>';
                break;
            case 'warning':
                $color_class = 'bg-yellow-100 border-yellow-500 text-yellow-700';
                $icon = '<i class="fas fa-exclamation-triangle mr-2"></i>';
                break;
            case 'success':
                $color_class = 'bg-green-100 border-green-500 text-green-700';
                $icon = '<i class="fas fa-check-circle mr-2"></i>';
                break;
            default: // info
                $color_class = 'bg-blue-100 border-blue-500 text-blue-700';
                $icon = '<i class="fas fa-info-circle mr-2"></i>';
        }
        
        // Create alert HTML
        $html = <<<HTML
        <div class="container mx-auto px-4 mt-4">
            <div class="border-l-4 $color_class p-4 rounded shadow-md">
                <div class="flex items-center">
                    $icon
                    <p>$message</p>
                </div>
            </div>
        </div>
        HTML;
        
        // Clear session alert
        unset($_SESSION['alert_message']);
        unset($_SESSION['alert_type']);
    }
    
    return $html;
}
?> 