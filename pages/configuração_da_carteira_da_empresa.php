<?php
session_start();
include_once('../basedados/basedados.h');

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>";
    echo "<h2 style='color: #d32f2f;'>Error: Unauthorized Access</h2>";
    echo "<p>You must be logged in as an administrator to run this script.</p>";
    echo "<p><a href='login.php' style='color: #1976d2; text-decoration: none;'>Login as Administrator</a></p>";
    echo "</div>";
    exit;
}

// Connect to database
$conn = connectDatabase();

// Execute SQL statements directly
$sql = "
-- Create company wallet if it doesn't exist
INSERT INTO users (username, password, email, first_name, last_name, user_type) 
SELECT 'felixbus', '$2y$10$someFixedHashForCompany', 'company@felixbus.com', 'FelixBus', 'Company', 'admin'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'felixbus');

-- Get company ID
SET @company_id = (SELECT id FROM users WHERE username = 'felixbus');

-- Create wallet if it doesn't exist
INSERT INTO wallets (user_id, balance)
SELECT @company_id, 10000.00
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM wallets WHERE user_id = @company_id);
";

// Execute statement
$success = true;
$error_message = '';

if(!$conn->multi_query($sql)) {
    $success = false;
    $error_message = "Error executing SQL: " . $conn->error;
}

// Output results
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>FelixBus Company Wallet Setup</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</head>
<body class='bg-gray-100 min-h-screen flex items-center justify-center'>
    <div class='bg-white p-8 rounded-lg shadow-md max-w-md w-full'>
        <div class='text-center mb-6'>
            <div class='inline-flex items-center justify-center w-16 h-16 rounded-full " . ($success ? "bg-green-100 text-green-600" : "bg-red-100 text-red-600") . " mb-4'>
                <i class='fas " . ($success ? "fa-check" : "fa-times") . " text-2xl'></i>
            </div>
            <h1 class='text-2xl font-bold text-gray-800'>" . ($success ? "Setup Complete" : "Setup Failed") . "</h1>
        </div>
        
        <div class='mb-6'>
            <p class='text-gray-600 mb-4'>" . ($success ? "The FelixBus company wallet has been successfully set up. You can now use the system to process payments." : "There was an error setting up the company wallet:") . "</p>
            
            " . (!$success ? "<div class='bg-red-50 p-4 rounded-lg text-red-800 text-sm mb-4 overflow-auto max-h-40'>" . $error_message . "</div>" : "") . "
        </div>
        
        <div class='flex justify-center'>
            <a href='admin_carteira_empresa.php' class='px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200'>
                Go to Company Wallet
            </a>
        </div>
    </div>
</body>
</html>";

// Close connection
$conn->close();
?> 