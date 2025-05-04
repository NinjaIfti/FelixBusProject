<?php
session_start();
include_once('../database/basedados.h');

// Connect to database
$conn = connectDatabase();

// Get all routes
$routes_query = "SELECT * FROM routes ORDER BY origin, destination";
$routes_result = $conn->query($routes_query);

// Get route details if route_id is provided
$selected_route = null;
$schedules = [];

if(isset($_GET['route_id']) && is_numeric($_GET['route_id'])) {
    $route_id = intval($_GET['route_id']);
    
    // Get route details
    $route_query = "SELECT * FROM routes WHERE id = $route_id";
    $route_result = $conn->query($route_query);
    
    if($route_result && $route_result->num_rows > 0) {
        $selected_route = $route_result->fetch_assoc();
        
        // Get schedules for this route
        $day_filter = isset($_GET['day']) ? $conn->real_escape_string($_GET['day']) : '';
        
        $schedules_query = "SELECT * FROM schedules WHERE route_id = $route_id";
        if(!empty($day_filter)) {
            $schedules_query .= " AND days LIKE '%$day_filter%'";
        }
        $schedules_query .= " ORDER BY departure_time";
        
        $schedules_result = $conn->query($schedules_query);
        
        if($schedules_result && $schedules_result->num_rows > 0) {
            while($schedule = $schedules_result->fetch_assoc()) {
                $schedules[] = $schedule;
            }
        }
    }
}

// Get all origins and destinations for filters
$origins_query = "SELECT DISTINCT origin FROM routes ORDER BY origin";
$origins_result = $conn->query($origins_query);

$destinations_query = "SELECT DISTINCT destination FROM routes ORDER BY destination";
$destinations_result = $conn->query($destinations_query);

// Days of week array for filter
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetables - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    <a href="timetables.php" class="bg-blue-700 px-2 py-1 rounded">Timetables</a>
                    <a href="contact.php" class="hover:text-blue-200">Contact</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="relative group">
                        <button class="flex items-center space-x-1">
                            <span>My Account</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 w-48 py-2 mt-2 bg-white rounded-md shadow-xl z-20 hidden group-hover:block">
                            <?php if($_SESSION['user_type'] === 'client'): ?>
                                <a href="client/dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Dashboard</a>
                                <a href="client/tickets.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">My Tickets</a>
                                <a href="client/wallet.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Wallet</a>
                            <?php elseif($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin'): ?>
                                <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Admin Panel</a>
                            <?php endif; ?>
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Profile</a>
                            <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="hover:text-blue-200">Login</a>
                    <a href="register.php" class="bg-white text-blue-600 px-4 py-2 rounded-full font-medium hover:bg-blue-50">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-blue-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Bus Timetables</h1>
            <p class="text-lg">View all available bus schedules and plan your journey with FelixBus.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Route Selection -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Find Your Bus Schedule</h2>
            
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="route_id" class="block text-sm font-medium text-gray-700 mb-2">Select Route</label>
                    <select id="route_id" name="route_id" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Select a Route --</option>
                        <?php if($routes_result && $routes_result->num_rows > 0): ?>
                            <?php while($route = $routes_result->fetch_assoc()): ?>
                                <option value="<?php echo $route['id']; ?>" <?php echo (isset($_GET['route_id']) && $_GET['route_id'] == $route['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($route['origin']); ?> to <?php echo htmlspecialchars($route['destination']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div>
                    <label for="day" class="block text-sm font-medium text-gray-700 mb-2">Day of Week (Optional)</label>
                    <select id="day" name="day" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Any Day</option>
                        <?php foreach($days_of_week as $day): ?>
                            <option value="<?php echo $day; ?>" <?php echo (isset($_GET['day']) && $_GET['day'] == $day) ? 'selected' : ''; ?>>
                                <?php echo $day; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                        <i class="fas fa-search mr-2"></i> View Schedules
                    </button>
                </div>
            </form>
        </div>
        
        <?php if($selected_route): ?>
            <!-- Schedules for Selected Route -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">
                    <?php echo htmlspecialchars($selected_route['origin']); ?> to <?php echo htmlspecialchars($selected_route['destination']); ?>
                </h2>
                <p class="text-gray-600 mb-6">
                    Distance: <?php echo $selected_route['distance']; ?> km | 
                    Base Price: $<?php echo number_format($selected_route['base_price'], 2); ?>
                </p>
                
                <?php if(count($schedules) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead>
                                <tr>
                                    <th class="py-3 px-6 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                        Departure Time
                                    </th>
                                    <th class="py-3 px-6 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                        Arrival Time
                                    </th>
                                    <th class="py-3 px-6 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                        Days of Operation
                                    </th>
                                    <th class="py-3 px-6 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                        Journey Time
                                    </th>
                                    <th class="py-3 px-6 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach($schedules as $schedule): ?>
                                    <?php 
                                        // Calculate journey time
                                        $departure = new DateTime($schedule['departure_time']);
                                        $arrival = new DateTime($schedule['arrival_time']);
                                        $interval = $departure->diff($arrival);
                                        $journey_time = $interval->format('%H:%I');
                                        
                                        // Format times for display
                                        $departure_time = date('g:i A', strtotime($schedule['departure_time']));
                                        $arrival_time = date('g:i A', strtotime($schedule['arrival_time']));
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-4 px-6 text-sm font-medium text-gray-900">
                                            <?php echo $departure_time; ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-900">
                                            <?php echo $arrival_time; ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($schedule['days']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-900 text-center">
                                            <?php echo $journey_time; ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm font-medium text-center">
                                            <a href="booking.php?schedule_id=<?php echo $schedule['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded text-sm">
                                                Book Now
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-5xl mb-4">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <p class="text-gray-600">No schedules found for this route<?php echo isset($_GET['day']) && !empty($_GET['day']) ? ' on ' . $_GET['day'] : ''; ?>.</p>
                        <?php if(isset($_GET['day']) && !empty($_GET['day'])): ?>
                            <p class="mt-2">
                                <a href="?route_id=<?php echo $selected_route['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                    View all schedules for this route
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Return journey link -->
                <?php
                // Find reverse route
                $origin = $selected_route['destination'];
                $destination = $selected_route['origin'];
                $reverse_route_query = "SELECT id FROM routes WHERE origin = '$origin' AND destination = '$destination'";
                $reverse_result = $conn->query($reverse_route_query);
                if($reverse_result && $reverse_result->num_rows > 0) {
                    $reverse_route = $reverse_result->fetch_assoc();
                    $reverse_route_id = $reverse_route['id'];
                    echo '<div class="mt-6 text-center">';
                    echo '<a href="?route_id=' . $reverse_route_id . '" class="text-blue-600 hover:text-blue-800">';
                    echo '<i class="fas fa-exchange-alt mr-2"></i> View return journey: ' . htmlspecialchars($origin) . ' to ' . htmlspecialchars($destination);
                    echo '</a>';
                    echo '</div>';
                }
                ?>
            </div>
        <?php else: ?>
            <!-- No route selected -->
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="py-8">
                    <div class="text-gray-400 text-5xl mb-4">
                        <i class="fas fa-bus"></i>
                    </div>
                    <p class="text-gray-600 text-lg mb-4">Please select a route to view available schedules.</p>
                    <p class="text-gray-500">Our buses connect to multiple destinations. Find the schedule that works best for you.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Popular Routes -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Popular Routes</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php
                // Reset pointer
                mysqli_data_seek($routes_result, 0);
                $count = 0;
                if($routes_result && $routes_result->num_rows > 0):
                    while($route = $routes_result->fetch_assoc()):
                        if($count >= 3) break; // Show only 3 routes
                        $count++;
                ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="bg-blue-600 text-white py-3 px-4">
                            <h3 class="font-bold"><?php echo htmlspecialchars($route['origin']); ?> to <?php echo htmlspecialchars($route['destination']); ?></h3>
                        </div>
                        <div class="p-4">
                            <p class="text-gray-600 mb-4">
                                <span class="block"><i class="fas fa-route mr-2"></i> Distance: <?php echo $route['distance']; ?> km</span>
                                <span class="block mt-1"><i class="fas fa-tag mr-2"></i> From $<?php echo number_format($route['base_price'], 2); ?></span>
                            </p>
                            <a href="?route_id=<?php echo $route['id']; ?>" class="block text-center bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                                View Schedules
                            </a>
                        </div>
                    </div>
                <?php 
                    endwhile;
                endif;
                ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">About FelixBus</h3>
                    <p class="text-blue-200">We provide reliable and comfortable bus transportation services connecting cities across the country.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="routes.php" class="text-blue-200 hover:text-white">Routes</a></li>
                        <li><a href="timetables.php" class="text-blue-200 hover:text-white">Timetables</a></li>
                        <li><a href="contact.php" class="text-blue-200 hover:text-white">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Information</h3>
                    <p class="text-blue-200"><i class="fas fa-map-marker-alt mr-2"></i> 123 Bus Station Road, City Center</p>
                    <p class="text-blue-200"><i class="fas fa-phone mr-2"></i> (123) 456-7890</p>
                    <p class="text-blue-200"><i class="fas fa-envelope mr-2"></i> info@felixbus.com</p>
                </div>
            </div>
            <div class="border-t border-blue-700 mt-8 pt-6 text-center">
                <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?> 