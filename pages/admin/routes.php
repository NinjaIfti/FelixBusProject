<?php
session_start();
include_once('../../database/basedados.h');

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Connect to database
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Process actions (create, update, delete routes and schedules)
$success_message = '';
$error_message = '';

// Create route
if(isset($_POST['create_route'])) {
    $origin = $conn->real_escape_string($_POST['origin']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $distance = floatval($_POST['distance']);
    $base_price = floatval($_POST['base_price']);
    $capacity = intval($_POST['capacity']);
    
    // Check if route already exists
    $check_query = "SELECT id FROM routes WHERE origin = '$origin' AND destination = '$destination'";
    $check_result = $conn->query($check_query);
    
    if($check_result->num_rows > 0) {
        $error_message = "A route from $origin to $destination already exists.";
    } else {
        $query = "INSERT INTO routes (origin, destination, distance, base_price, capacity) 
                  VALUES ('$origin', '$destination', $distance, $base_price, $capacity)";
                  
        if($conn->query($query)) {
            $success_message = "Route created successfully!";
        } else {
            $error_message = "Error creating route: " . $conn->error;
        }
    }
}

// Update route
if(isset($_POST['update_route'])) {
    $route_id = intval($_POST['route_id']);
    $origin = $conn->real_escape_string($_POST['origin']);
    $destination = $conn->real_escape_string($_POST['destination']);
    $distance = floatval($_POST['distance']);
    $base_price = floatval($_POST['base_price']);
    $capacity = intval($_POST['capacity']);
    
    // Check if route already exists with different ID
    $check_query = "SELECT id FROM routes WHERE origin = '$origin' AND destination = '$destination' AND id != $route_id";
    $check_result = $conn->query($check_query);
    
    if($check_result->num_rows > 0) {
        $error_message = "A route from $origin to $destination already exists.";
    } else {
        $query = "UPDATE routes SET 
                  origin = '$origin', 
                  destination = '$destination', 
                  distance = $distance, 
                  base_price = $base_price,
                  capacity = $capacity 
                  WHERE id = $route_id";
                  
        if($conn->query($query)) {
            $success_message = "Route updated successfully!";
        } else {
            $error_message = "Error updating route: " . $conn->error;
        }
    }
}

// Delete route
if(isset($_POST['delete_route'])) {
    $route_id = intval($_POST['route_id']);
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First check if there are any active tickets for this route
        $check_tickets_query = "SELECT t.id FROM tickets t 
                               JOIN schedules s ON t.schedule_id = s.id 
                               WHERE s.route_id = $route_id AND t.status = 'active'";
        $tickets_result = $conn->query($check_tickets_query);
        
        if($tickets_result->num_rows > 0) {
            throw new Exception("Cannot delete this route as there are active tickets associated with it.");
        }
        
        // Delete schedules for this route
        $delete_schedules = "DELETE FROM schedules WHERE route_id = $route_id";
        if(!$conn->query($delete_schedules)) {
            throw new Exception("Failed to delete schedules for this route.");
        }
        
        // Delete route
        $delete_route = "DELETE FROM routes WHERE id = $route_id";
        if(!$conn->query($delete_route)) {
            throw new Exception("Failed to delete route.");
        }
        
        // Commit transaction
        $conn->commit();
        $success_message = "Route and all associated schedules deleted successfully!";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}

// Create schedule
if(isset($_POST['create_schedule'])) {
    $route_id = intval($_POST['route_id']);
    $departure_time = $conn->real_escape_string($_POST['departure_time']);
    $arrival_time = $conn->real_escape_string($_POST['arrival_time']);
    
    // Process days of week
    $days = isset($_POST['days']) ? $_POST['days'] : [];
    $days_string = implode(', ', $days);
    
    if(empty($days)) {
        $error_message = "Please select at least one day of the week.";
    } else {
        // Validate that arrival time is after departure time
        if($arrival_time <= $departure_time) {
            $error_message = "Arrival time must be after departure time.";
        } else {
            $query = "INSERT INTO schedules (route_id, departure_time, arrival_time, days) 
                      VALUES ($route_id, '$departure_time', '$arrival_time', '$days_string')";
                      
            if($conn->query($query)) {
                $success_message = "Schedule added successfully!";
            } else {
                $error_message = "Error adding schedule: " . $conn->error;
            }
        }
    }
}

// Delete schedule
if(isset($_POST['delete_schedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    
    // Check if there are active tickets for this schedule
    $check_tickets = "SELECT id FROM tickets WHERE schedule_id = $schedule_id AND status = 'active'";
    $tickets_result = $conn->query($check_tickets);
    
    if($tickets_result->num_rows > 0) {
        $error_message = "Cannot delete this schedule as there are active tickets associated with it.";
    } else {
        $query = "DELETE FROM schedules WHERE id = $schedule_id";
        
        if($conn->query($query)) {
            $success_message = "Schedule deleted successfully!";
        } else {
            $error_message = "Error deleting schedule: " . $conn->error;
        }
    }
}

// Get all routes
$routes_query = "SELECT * FROM routes ORDER BY origin, destination";
$routes_result = $conn->query($routes_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Management - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Sidebar -->
    <div class="flex flex-1">
        <div class="bg-blue-800 text-white w-64 py-6 flex-shrink-0 hidden md:block">
            <div class="px-6">
                <a href="dashboard.php" class="text-2xl font-bold mb-8 flex items-center">
                    <i class="fas fa-bus mr-3"></i> FelixBus
                </a>
            </div>
            <nav class="mt-10">
                <a href="dashboard.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-users mr-3"></i> Users
                </a>
                <a href="routes.php" class="flex items-center py-3 px-6 bg-blue-700 bg-opacity-60">
                    <i class="fas fa-route mr-3"></i> Routes
                </a>
                <a href="tickets.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-ticket-alt mr-3"></i> Tickets
                </a>
                <a href="manage_wallet.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-wallet mr-3"></i> Manage Wallets
                </a>
                <?php if($is_admin): ?>
                <a href="company_wallet.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-building mr-3"></i> Company Wallet
                </a>
                <a href="alerts.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-bullhorn mr-3"></i> Alerts
                </a>
                <?php endif; ?>
                <a href="../index.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-home mr-3"></i> Main Website
                </a>
                <a href="../logout.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60 mt-auto">
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
                        <h1 class="text-2xl font-semibold text-gray-800">Route Management</h1>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="container mx-auto px-4 py-8">
                <?php if($success_message): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <p><?php echo $success_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if($error_message): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p><?php echo $error_message; ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Actions Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">All Routes</h2>
                    </div>
                    <div>
                        <button type="button" onclick="openModal('createRouteModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add New Route
                        </button>
                    </div>
                </div>
                
                <!-- Routes Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origin</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance (km)</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Price</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedules</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($routes_result && $routes_result->num_rows > 0): ?>
                                    <?php while($route = $routes_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($route['origin']); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($route['destination']); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php echo $route['distance'] ? htmlspecialchars($route['distance']) : 'N/A'; ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                $<?php echo number_format($route['base_price'], 2); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php 
                                                    $schedule_query = "SELECT COUNT(*) as total FROM schedules WHERE route_id = " . $route['id'];
                                                    $schedule_result = $conn->query($schedule_query);
                                                    $schedule_count = $schedule_result->fetch_assoc()['total'];
                                                    echo $schedule_count;
                                                ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-right">
                                                <button 
                                                    type="button" 
                                                    onclick="viewSchedules(<?php echo $route['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3"
                                                >
                                                    <i class="fas fa-clock"></i> Schedules
                                                </button>
                                                <button 
                                                    type="button" 
                                                    onclick="editRoute(<?php echo $route['id']; ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                                                >
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button 
                                                    type="button" 
                                                    onclick="deleteRoute(<?php echo $route['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-4 px-4 border-b border-gray-200 text-center text-gray-500">
                                            No routes found. Add a new route to get started.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create Route Modal -->
    <div id="createRouteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Add New Route</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('createRouteModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="createRouteForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="origin" class="block text-sm font-medium text-gray-700 mb-2">Origin</label>
                            <input type="text" id="origin" name="origin" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="destination" class="block text-sm font-medium text-gray-700 mb-2">Destination</label>
                            <input type="text" id="destination" name="destination" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="distance" class="block text-sm font-medium text-gray-700 mb-2">Distance (km)</label>
                            <input type="number" min="1" step="0.1" id="distance" name="distance" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="base_price" class="block text-sm font-medium text-gray-700 mb-2">Base Price ($)</label>
                            <input type="number" min="0.01" step="0.01" id="base_price" name="base_price" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="capacity" class="block text-sm font-medium text-gray-700 mb-2">Bus Capacity</label>
                            <input type="number" min="1" id="capacity" name="capacity" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="50" required>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" onclick="closeModal('createRouteModal')">
                            Cancel
                        </button>
                        <button type="submit" name="create_route" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Create Route
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Route Modal -->
    <div id="editRouteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Edit Route</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editRouteModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="editRouteForm">
                    <input type="hidden" id="edit_route_id" name="route_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="edit_origin" class="block text-sm font-medium text-gray-700 mb-2">Origin</label>
                            <input type="text" id="edit_origin" name="origin" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="edit_destination" class="block text-sm font-medium text-gray-700 mb-2">Destination</label>
                            <input type="text" id="edit_destination" name="destination" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="edit_distance" class="block text-sm font-medium text-gray-700 mb-2">Distance (km)</label>
                            <input type="number" min="1" step="0.1" id="edit_distance" name="distance" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="edit_base_price" class="block text-sm font-medium text-gray-700 mb-2">Base Price ($)</label>
                            <input type="number" min="0.01" step="0.01" id="edit_base_price" name="base_price" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="edit_capacity" class="block text-sm font-medium text-gray-700 mb-2">Bus Capacity</label>
                            <input type="number" min="1" id="edit_capacity" name="capacity" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" onclick="closeModal('editRouteModal')">
                            Cancel
                        </button>
                        <button type="submit" name="update_route" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update Route
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Schedules Modal -->
    <div id="schedulesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Route Schedules: <span id="schedule_route_name"></span></h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('schedulesModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="my-4">
                    <button type="button" onclick="showAddScheduleForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Schedule
                    </button>
                </div>
                
                <!-- Add Schedule Form -->
                <div id="addScheduleForm" class="hidden bg-gray-50 p-4 rounded-md mb-6">
                    <h4 class="text-md font-medium text-gray-800 mb-4">Add New Schedule</h4>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="createScheduleForm">
                        <input type="hidden" id="schedule_route_id" name="route_id">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="departure_time" class="block text-sm font-medium text-gray-700 mb-2">Departure Time</label>
                                <input type="time" id="departure_time" name="departure_time" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label for="arrival_time" class="block text-sm font-medium text-gray-700 mb-2">Arrival Time</label>
                                <input type="time" id="arrival_time" name="arrival_time" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label for="days" class="block text-sm font-medium text-gray-700 mb-2">Days of Week</label>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="days[]" value="Monday" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2">Mon</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="days[]" value="Tuesday" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2">Tue</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="days[]" value="Wednesday" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2">Wed</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="days[]" value="Thursday" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2">Thu</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="days[]" value="Friday" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2">Fri</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="days[]" value="Saturday" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2">Sat</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="days[]" value="Sunday" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2">Sun</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" onclick="hideAddScheduleForm()">
                                Cancel
                            </button>
                            <button type="submit" name="create_schedule" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Add Schedule
                            </button>
                        </div>
                    </form>
                </div>
                
                <div id="schedulesList" class="overflow-x-auto">
                    <!-- Schedule list will be populated via AJAX -->
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departure</th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival</th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                                <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="schedulesTableBody">
                            <!-- Schedules will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Route Confirmation Modal -->
    <div id="deleteRouteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('deleteRouteModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <p class="text-gray-700">Are you sure you want to delete this route? This will also delete all associated schedules and tickets.</p>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="deleteRouteForm">
                    <input type="hidden" id="delete_route_id" name="route_id">
                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" onclick="closeModal('deleteRouteModal')">
                            Cancel
                        </button>
                        <button type="submit" name="delete_route" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.bg-blue-800').classList.toggle('hidden');
        });
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        function viewSchedules(routeId) {
            // Fetch route details and schedules
            fetch(`get_schedules.php?route_id=${routeId}`)
                .then(response => response.json())
                .then(data => {
                    // Set route name in the modal
                    document.getElementById('schedule_route_name').textContent = data.route.origin + ' to ' + data.route.destination;
                    document.getElementById('schedule_route_id').value = routeId;
                    
                    // Populate schedules table
                    const tableBody = document.getElementById('schedulesTableBody');
                    tableBody.innerHTML = '';
                    
                    if (data.schedules.length === 0) {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td colspan="5" class="py-4 px-4 border-b border-gray-200 text-center text-gray-500">
                                No schedules found for this route. Add a new schedule to get started.
                            </td>
                        `;
                        tableBody.appendChild(row);
                    } else {
                        data.schedules.forEach(schedule => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                    ${schedule.id}
                                </td>
                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                    ${schedule.departure_time}
                                </td>
                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                    ${schedule.arrival_time}
                                </td>
                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                    ${schedule.days}
                                </td>
                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-right">
                                    <button 
                                        type="button" 
                                        onclick="editSchedule(${schedule.id})" 
                                        class="text-indigo-600 hover:text-indigo-900 mr-3"
                                    >
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button 
                                        type="button" 
                                        onclick="deleteSchedule(${schedule.id})" 
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                    }
                    
                    // Open the modal
                    openModal('schedulesModal');
                })
                .catch(error => {
                    console.error('Error fetching schedules:', error);
                    alert('Failed to load schedules. Please try again.');
                });
        }
        
        function showAddScheduleForm() {
            document.getElementById('addScheduleForm').classList.remove('hidden');
        }
        
        function hideAddScheduleForm() {
            document.getElementById('addScheduleForm').classList.add('hidden');
        }
        
        function editRoute(routeId) {
            // Fetch route details
            fetch(`get_route.php?route_id=${routeId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate form fields
                    document.getElementById('edit_route_id').value = data.id;
                    document.getElementById('edit_origin').value = data.origin;
                    document.getElementById('edit_destination').value = data.destination;
                    document.getElementById('edit_distance').value = data.distance;
                    document.getElementById('edit_base_price').value = data.base_price;
                    document.getElementById('edit_capacity').value = data.capacity;
                    
                    // Open the modal
                    openModal('editRouteModal');
                })
                .catch(error => {
                    console.error('Error fetching route details:', error);
                    alert('Failed to load route details. Please try again.');
                });
        }
        
        function deleteRoute(routeId) {
            document.getElementById('delete_route_id').value = routeId;
            openModal('deleteRouteModal');
        }
        
        function editSchedule(scheduleId) {
            // To be implemented
            alert('Edit schedule functionality will be implemented soon.');
        }
        
        function deleteSchedule(scheduleId) {
            // To be implemented
            alert('Delete schedule functionality will be implemented soon.');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?> 