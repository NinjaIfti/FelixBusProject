<?php
session_start();
include_once('../database/basedados.h');

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$conn = connectDatabase();
$response = [];

// Test database connectivity
$response['database_connection'] = ($conn !== false) ? "Success" : "Failed";

// Check for required tables
$tables = [
    'users',
    'wallets',
    'wallet_transactions',
    'routes',
    'schedules',
    'tickets',
    'alerts'
];

$missing_tables = [];
foreach ($tables as $table) {
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows === 0) {
        $missing_tables[] = $table;
    }
}

$response['tables_check'] = empty($missing_tables) ? "All required tables exist" : "Missing tables: " . implode(", ", $missing_tables);

// Check total users
$users_query = "SELECT COUNT(*) as count FROM users";
$users_result = $conn->query($users_query);
$response['users_count'] = $users_result ? $users_result->fetch_assoc()['count'] : "Query failed";

// Check wallets
$wallets_query = "SELECT COUNT(*) as count FROM wallets";
$wallets_result = $conn->query($wallets_query);
$response['wallets_count'] = $wallets_result ? $wallets_result->fetch_assoc()['count'] : "Query failed";

// Check users without wallets
$no_wallet_query = "SELECT COUNT(*) as count FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE w.id IS NULL";
$no_wallet_result = $conn->query($no_wallet_query);
$response['users_without_wallets'] = $no_wallet_result ? $no_wallet_result->fetch_assoc()['count'] : "Query failed";

// Check tickets
$tickets_query = "SELECT COUNT(*) as count FROM tickets";
$tickets_result = $conn->query($tickets_query);
$response['tickets_count'] = $tickets_result ? $tickets_result->fetch_assoc()['count'] : "Query failed";

// Check for routes without schedules
$orphan_routes_query = "SELECT COUNT(*) as count FROM routes r LEFT JOIN schedules s ON r.id = s.route_id WHERE s.id IS NULL";
$orphan_routes_result = $conn->query($orphan_routes_query);
$response['routes_without_schedules'] = $orphan_routes_result ? $orphan_routes_result->fetch_assoc()['count'] : "Query failed";

// Check PHP error log
$error_log_path = ini_get('error_log');
$recent_errors = "Error log path: $error_log_path\n";

if (file_exists($error_log_path) && is_readable($error_log_path)) {
    $log_content = file_get_contents($error_log_path);
    // Get the last few lines
    $lines = explode("\n", $log_content);
    $recent_lines = array_slice($lines, -20); // Last 20 lines
    $recent_errors .= implode("\n", $recent_lines);
} else {
    $recent_errors .= "Error log not accessible";
}

$response['recent_errors'] = $recent_errors;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Debug - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-2xl font-bold">FelixBus</a>
                <div class="hidden md:flex space-x-4">
                    <a href="routes.php" class="hover:text-blue-200">Routes</a>
                    <a href="timetables.php" class="hover:text-blue-200">Timetables</a>
                    <a href="prices.php" class="hover:text-blue-200">Prices</a>
                    <a href="contact.php" class="hover:text-blue-200">Contact</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" class="flex items-center space-x-1">
                        <span>My Account</span>
                        <i class="fas fa-chevron-down text-xs" :class="{ 'transform rotate-180': open }"></i>
                    </button>
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="absolute right-0 w-48 py-2 mt-2 bg-white rounded-md shadow-xl z-20">
                        <a href="admin_dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Admin Panel</a>
                        <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Profile</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-blue-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">System Debug</h1>
            <p class="text-lg">Check system status and diagnose issues</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">System Status</h2>
            
            <div class="space-y-4">
                <?php foreach ($response as $key => $value): ?>
                    <?php if ($key !== 'recent_errors'): ?>
                        <div class="flex items-start border-b border-gray-200 pb-3">
                            <div class="w-1/3 font-medium text-gray-700">
                                <?php echo ucwords(str_replace('_', ' ', $key)); ?>:
                            </div>
                            <div class="w-2/3">
                                <?php if (is_numeric($value) || $value === '0'): ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $value > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $value; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo strpos($value, 'Success') !== false || strpos($value, 'All required') !== false ? 'bg-green-100 text-green-800' : (strpos($value, 'Failed') !== false || strpos($value, 'Missing') !== false ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'); ?>">
                                        <?php echo $value; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Recent Error Logs</h2>
            <pre class="bg-gray-800 text-gray-200 p-4 rounded-lg overflow-x-auto text-sm font-mono"><?php echo htmlspecialchars($response['recent_errors']); ?></pre>
        </div>
        
        <!-- Quick Fixes -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Maintenance Tools</h2>
            
            <div class="grid md:grid-cols-2 gap-4">
                <a href="?action=create_missing_wallets" class="border border-blue-500 text-blue-500 p-4 rounded-lg hover:bg-blue-50 flex flex-col items-center justify-center text-center">
                    <i class="fas fa-wallet text-3xl mb-2"></i>
                    <span class="font-medium">Create Missing Wallets</span>
                    <span class="text-sm text-gray-600 mt-1">Create wallets for users who don't have one</span>
                </a>
                
                <a href="../database/create_database.php" class="border border-blue-500 text-blue-500 p-4 rounded-lg hover:bg-blue-50 flex flex-col items-center justify-center text-center">
                    <i class="fas fa-database text-3xl mb-2"></i>
                    <span class="font-medium">Repair Database</span>
                    <span class="text-sm text-gray-600 mt-1">Recreate missing tables and add default data</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Action Handlers -->
    <?php
    if (isset($_GET['action'])) {
        if ($_GET['action'] === 'create_missing_wallets') {
            $users_query = "SELECT id FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE w.id IS NULL";
            $users_result = $conn->query($users_query);
            
            $created = 0;
            if ($users_result && $users_result->num_rows > 0) {
                while ($user = $users_result->fetch_assoc()) {
                    $user_id = $user['id'];
                    $create_wallet = "INSERT INTO wallets (user_id, balance) VALUES ($user_id, 0.00)";
                    if ($conn->query($create_wallet)) {
                        $created++;
                    }
                }
                echo "<script>alert('Created $created new wallets for users.');</script>";
            } else {
                echo "<script>alert('No users without wallets found.');</script>";
            }
        }
    }
    ?>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?> 