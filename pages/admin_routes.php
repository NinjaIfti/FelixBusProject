<?php
session_start();
include_once('../database/basedados.h');

// Check if user is authenticated and is an admin/staff
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'staff')) {
    header("Location: login.php");
    exit();
}

$conn = connectDatabase();

// Handle route operations (add, edit, delete)
$operation_message = '';
$operation_status = '';

// Add new route
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_route'])) {
    $origin = $conn->real_escape_string($_POST['origin']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $base_price = floatval($_POST['base_price']);
    $distance = floatval($_POST['distance']);
    $capacity = intval($_POST['capacity']);
    
    if (empty($origin) || empty($destination) || $base_price <= 0 || $distance <= 0 || $capacity <= 0) {
        $operation_message = "All fields are required and must be valid values";
        $operation_status = "error";
    } else {
        // Check if route already exists
        $check_query = "SELECT id FROM routes WHERE origin = '$origin' AND destination = '$destination'";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows > 0) {
            $operation_message = "Route from $origin to $destination already exists";
            $operation_status = "error";
        } else {
            $insert_query = "INSERT INTO routes (origin, destination, base_price, distance, capacity) 
                            VALUES ('$origin', '$destination', $base_price, $distance, $capacity)";
            
            if ($conn->query($insert_query)) {
                $operation_message = "Route from $origin to $destination added successfully";
                $operation_status = "success";
            } else {
                $operation_message = "Error adding route: " . $conn->error;
                $operation_status = "error";
            }
        }
    }
}

// Delete route
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_route'])) {
    $route_id = intval($_POST['route_id']);
    
    // Delete associated schedules first
    $delete_schedules_query = "DELETE FROM schedules WHERE route_id = $route_id";
    $conn->query($delete_schedules_query);
    
    // Then delete the route
    $delete_query = "DELETE FROM routes WHERE id = $route_id";
    
    if ($conn->query($delete_query)) {
        $operation_message = "Route and associated schedules deleted successfully";
        $operation_status = "success";
    } else {
        $operation_message = "Error deleting route: " . $conn->error;
        $operation_status = "error";
    }
}

// Edit route
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_route'])) {
    $route_id = intval($_POST['route_id']);
    $origin = $conn->real_escape_string($_POST['origin']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $base_price = floatval($_POST['base_price']);
    $distance = floatval($_POST['distance']);
    $capacity = intval($_POST['capacity']);
    
    if (empty($origin) || empty($destination) || $base_price <= 0 || $distance <= 0 || $capacity <= 0) {
        $operation_message = "All fields are required and must be valid values";
        $operation_status = "error";
    } else {
        $update_query = "UPDATE routes 
                        SET origin = '$origin', destination = '$destination', 
                            base_price = $base_price, distance = $distance, capacity = $capacity 
                        WHERE id = $route_id";
        
        if ($conn->query($update_query)) {
            $operation_message = "Route updated successfully";
            $operation_status = "success";
        } else {
            $operation_message = "Error updating route: " . $conn->error;
            $operation_status = "error";
        }
    }
}

// Add new schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $route_id = intval($_POST['route_id']);
    $departure_time = $conn->real_escape_string($_POST['departure_time']);
    $arrival_time = $conn->real_escape_string($_POST['arrival_time']);
    $days = isset($_POST['days']) ? implode(',', $_POST['days']) : '';
    
    if (empty($departure_time) || empty($arrival_time) || empty($days)) {
        $operation_message = "All schedule fields are required";
        $operation_status = "error";
    } else {
        $insert_query = "INSERT INTO schedules (route_id, departure_time, arrival_time, days) 
                        VALUES ($route_id, '$departure_time', '$arrival_time', '$days')";
        
        if ($conn->query($insert_query)) {
            $operation_message = "Schedule added successfully";
            $operation_status = "success";
        } else {
            $operation_message = "Error adding schedule: " . $conn->error;
            $operation_status = "error";
        }
    }
}

// Delete schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_schedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    
    // Check if there are any tickets for this schedule
    $check_tickets_query = "SELECT COUNT(*) as ticket_count FROM tickets WHERE schedule_id = $schedule_id";
    $check_result = $conn->query($check_tickets_query);
    $ticket_count = $check_result->fetch_assoc()['ticket_count'];
    
    if ($ticket_count > 0) {
        $operation_message = "Cannot delete schedule with associated tickets";
        $operation_status = "error";
    } else {
        $delete_query = "DELETE FROM schedules WHERE id = $schedule_id";
        
        if ($conn->query($delete_query)) {
            $operation_message = "Schedule deleted successfully";
            $operation_status = "success";
        } else {
            $operation_message = "Error deleting schedule: " . $conn->error;
            $operation_status = "error";
        }
    }
}

// Fetch all routes
$routes_query = "SELECT * FROM routes ORDER BY origin, destination";
$routes_result = $conn->query($routes_query);
$routes = [];

if ($routes_result->num_rows > 0) {
    while ($route = $routes_result->fetch_assoc()) {
        // Get schedules for this route
        $route_id = $route['id'];
        $schedules_query = "SELECT * FROM schedules WHERE route_id = $route_id ORDER BY departure_time";
        $schedules_result = $conn->query($schedules_query);
        $schedules = [];
        
        if ($schedules_result->num_rows > 0) {
            while ($schedule = $schedules_result->fetch_assoc()) {
                $schedules[] = $schedule;
            }
        }
        
        $route['schedules'] = $schedules;
        $routes[] = $route;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Routes - FelixBus Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .route-card {
            transition: all 0.3s ease;
        }
        
        .route-card:hover {
            transform: translateY(-5px);
        }
        
        .nav-link { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">
    <!-- Sidebar -->
    <div class="flex flex-1">
        <div class="bg-black text-white w-64 py-6 flex-shrink-0 hidden md:block">
            <div class="px-6">
                <a href="admin_dashboard.php" class="text-2xl font-bold mb-8 flex items-center">
                    <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span>
                    <span>Felix<span class="text-red-600">Bus</span></span>
                </a>
            </div>
            <nav class="mt-10">
                <a href="admin_dashboard.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                </a>
                <a href="admin_users.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-users mr-3"></i> Users
                </a>
                <a href="admin_routes.php" class="flex items-center py-3 px-6 bg-red-900 text-white nav-link">
                    <i class="fas fa-route mr-3"></i> Routes
                </a>
                <a href="admin_tickets.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-ticket-alt mr-3"></i> Tickets
                </a>
                <a href="admin_manage_wallet.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-wallet mr-3"></i> Manage Wallets
                </a>
                <?php if($_SESSION['user_type'] === 'admin'): ?>
                <a href="admin_company_wallet.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-building mr-3"></i> Company Wallet
                </a>
                <a href="admin_alerts.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-bullhorn mr-3"></i> Alerts
                </a>
                <?php endif; ?>
                <a href="index.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-home mr-3"></i> Main Website
                </a>
                <a href="logout.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link mt-auto">
                    <i class="fas fa-sign-out-alt mr-3"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="mr-4 text-gray-600 md:hidden">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h1 class="text-2xl font-semibold text-gray-800">Manage Routes</h1>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="container mx-auto px-4 py-8">
                <?php if(!empty($operation_message)): ?>
                    <div class="mb-6 p-4 rounded-md <?php echo $operation_status === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700'; ?>">
                        <?php echo $operation_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Add New Route Form -->
                <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 border border-gray-700" x-data="{ addRouteOpen: false }">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Add New Route</h2>
                        <button @click="addRouteOpen = !addRouteOpen" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-1 rounded-md text-sm transition duration-300">
                            <span x-text="addRouteOpen ? 'Close Form' : 'Add Route'"></span>
                        </button>
                    </div>
                    
                    <div x-show="addRouteOpen" x-transition>
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-gray-400 text-sm mb-1">Origin City</label>
                                <input type="text" name="origin" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 text-sm mb-1">Destination City</label>
                                <input type="text" name="destination" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 text-sm mb-1">Base Price (€)</label>
                                <input type="number" name="base_price" step="0.01" min="0.01" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 text-sm mb-1">Distance (km)</label>
                                <input type="number" name="distance" step="0.1" min="0.1" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                            </div>
                            
                            <div>
                                <label class="block text-gray-400 text-sm mb-1">Bus Capacity</label>
                                <input type="number" name="capacity" min="1" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" name="add_route" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-md transition duration-300">
                                    <i class="fas fa-plus mr-2"></i> Add Route
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Routes List -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach($routes as $route): ?>
                        <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700 route-card" x-data="{ editOpen: false, scheduleOpen: false }">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?></h3>
                                    <p class="text-gray-400 text-sm">
                                        Distance: <?php echo number_format($route['distance'], 1); ?> km • 
                                        Capacity: <?php echo $route['capacity']; ?> passengers
                                    </p>
                                    <p class="text-green-400 font-semibold mt-1">
                                        €<?php echo number_format($route['base_price'], 2); ?>
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <button @click="editOpen = !editOpen; scheduleOpen = false" class="text-yellow-500 hover:text-yellow-400">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this route? This will also delete all associated schedules.')">
                                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                        <button type="submit" name="delete_route" class="text-red-500 hover:text-red-400">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Edit Route Form -->
                            <div x-show="editOpen" x-transition class="border-t border-gray-700 pt-4 mt-4">
                                <h4 class="text-md font-semibold mb-3">Edit Route</h4>
                                <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                    
                                    <div>
                                        <label class="block text-gray-400 text-sm mb-1">Origin City</label>
                                        <input type="text" name="origin" value="<?php echo htmlspecialchars($route['origin']); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-gray-400 text-sm mb-1">Destination City</label>
                                        <input type="text" name="destination" value="<?php echo htmlspecialchars($route['destination']); ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-gray-400 text-sm mb-1">Base Price (€)</label>
                                        <input type="number" name="base_price" step="0.01" min="0.01" value="<?php echo $route['base_price']; ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-gray-400 text-sm mb-1">Distance (km)</label>
                                        <input type="number" name="distance" step="0.1" min="0.1" value="<?php echo $route['distance']; ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-gray-400 text-sm mb-1">Bus Capacity</label>
                                        <input type="number" name="capacity" min="1" value="<?php echo $route['capacity']; ?>" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" required>
                                    </div>
                                    
                                    <div class="flex items-end">
                                        <button type="submit" name="edit_route" class="bg-yellow-600 hover:bg-yellow-500 text-white px-4 py-2 rounded-md transition duration-300">
                                            <i class="fas fa-save mr-2"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Schedules Section -->
                            <div class="border-t border-gray-700 pt-4 mt-4">
                                <button @click="scheduleOpen = !scheduleOpen; editOpen = false" class="flex justify-between items-center w-full">
                                    <h4 class="text-md font-semibold">
                                        Schedules 
                                        <span class="text-gray-400 text-sm">(<?php echo count($route['schedules']); ?>)</span>
                                    </h4>
                                    <i class="fas" :class="scheduleOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                                
                                <div x-show="scheduleOpen" x-transition class="mt-3 space-y-4">
                                    <!-- Add New Schedule Form -->
                                    <div class="bg-gray-700 rounded-md p-3">
                                        <h5 class="text-sm font-semibold mb-2">Add New Schedule</h5>
                                        <form method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                            
                                            <div>
                                                <label class="block text-gray-400 text-xs mb-1">Departure Time</label>
                                                <input type="time" name="departure_time" class="w-full px-3 py-1 bg-gray-800 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white text-sm" required>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-gray-400 text-xs mb-1">Arrival Time</label>
                                                <input type="time" name="arrival_time" class="w-full px-3 py-1 bg-gray-800 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white text-sm" required>
                                            </div>
                                            
                                            <div class="sm:col-span-2">
                                                <label class="block text-gray-400 text-xs mb-1">Operating Days</label>
                                                <div class="flex flex-wrap gap-2">
                                                    <label class="flex items-center space-x-1">
                                                        <input type="checkbox" name="days[]" value="Monday" class="text-red-600 rounded-sm bg-gray-800 border-gray-600 focus:ring-red-500">
                                                        <span class="text-xs">Mon</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1">
                                                        <input type="checkbox" name="days[]" value="Tuesday" class="text-red-600 rounded-sm bg-gray-800 border-gray-600 focus:ring-red-500">
                                                        <span class="text-xs">Tue</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1">
                                                        <input type="checkbox" name="days[]" value="Wednesday" class="text-red-600 rounded-sm bg-gray-800 border-gray-600 focus:ring-red-500">
                                                        <span class="text-xs">Wed</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1">
                                                        <input type="checkbox" name="days[]" value="Thursday" class="text-red-600 rounded-sm bg-gray-800 border-gray-600 focus:ring-red-500">
                                                        <span class="text-xs">Thu</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1">
                                                        <input type="checkbox" name="days[]" value="Friday" class="text-red-600 rounded-sm bg-gray-800 border-gray-600 focus:ring-red-500">
                                                        <span class="text-xs">Fri</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1">
                                                        <input type="checkbox" name="days[]" value="Saturday" class="text-red-600 rounded-sm bg-gray-800 border-gray-600 focus:ring-red-500">
                                                        <span class="text-xs">Sat</span>
                                                    </label>
                                                    <label class="flex items-center space-x-1">
                                                        <input type="checkbox" name="days[]" value="Sunday" class="text-red-600 rounded-sm bg-gray-800 border-gray-600 focus:ring-red-500">
                                                        <span class="text-xs">Sun</span>
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <button type="submit" name="add_schedule" class="bg-green-600 hover:bg-green-500 text-white px-3 py-1 rounded-md text-sm transition duration-300">
                                                    <i class="fas fa-plus mr-1"></i> Add Schedule
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    
                                    <!-- Schedules List -->
                                    <?php if(count($route['schedules']) > 0): ?>
                                        <div class="bg-gray-700 rounded-md p-3">
                                            <h5 class="text-sm font-semibold mb-2">Current Schedules</h5>
                                            <div class="space-y-2">
                                                <?php foreach($route['schedules'] as $schedule): ?>
                                                    <div class="flex justify-between items-center bg-gray-800 p-2 rounded-md">
                                                        <div>
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-sm font-medium"><?php echo date('H:i', strtotime($schedule['departure_time'])); ?></span>
                                                                <span class="text-gray-400">→</span>
                                                                <span class="text-sm font-medium"><?php echo date('H:i', strtotime($schedule['arrival_time'])); ?></span>
                                                            </div>
                                                            <div class="text-xs text-gray-400 mt-1">
                                                                <?php 
                                                                    $days_array = explode(',', $schedule['days']);
                                                                    $days_short = array(
                                                                        'Monday' => 'Mon',
                                                                        'Tuesday' => 'Tue',
                                                                        'Wednesday' => 'Wed',
                                                                        'Thursday' => 'Thu',
                                                                        'Friday' => 'Fri',
                                                                        'Saturday' => 'Sat',
                                                                        'Sunday' => 'Sun'
                                                                    );
                                                                    $short_days = array();
                                                                    foreach($days_array as $day) {
                                                                        $short_days[] = $days_short[$day] ?? $day;
                                                                    }
                                                                    echo implode(', ', $short_days);
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this schedule?')">
                                                            <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                            <button type="submit" name="delete_schedule" class="text-red-400 hover:text-red-300">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-400">No schedules added yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if(count($routes) === 0): ?>
                        <div class="col-span-full text-center py-8">
                            <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
                                <div class="text-red-500 text-5xl mb-4">
                                    <i class="fas fa-route"></i>
                                </div>
                                <p class="text-gray-400 text-lg">No routes found</p>
                                <p class="text-gray-500 mt-2">Add your first route using the form above.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Toggle JavaScript -->
    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.bg-black').classList.toggle('hidden');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?> 