<?php
session_start();
include_once('../database/basedados.h');

$conn = connectDatabase();

// Fetch all unique origins and destinations for dropdowns
$origins_query = "SELECT DISTINCT origin FROM routes ORDER BY origin";
$origins_result = $conn->query($origins_query);

$destinations_query = "SELECT DISTINCT destination FROM routes ORDER BY destination";
$destinations_result = $conn->query($destinations_query);

// Initialize variables
$routes = [];
$search_performed = false;
$error_message = '';

// Process search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $origin = $conn->real_escape_string($_POST['origin'] ?? '');
    $destination = $conn->real_escape_string($_POST['destination'] ?? '');
    
    if (empty($origin) || empty($destination)) {
        $error_message = "Please select both origin and destination.";
    } else {
        // Query for routes
        $routes_query = "SELECT r.id as route_id, r.origin, r.destination, r.base_price, r.distance, r.capacity,
                        s.id as schedule_id, s.departure_time, s.arrival_time, s.days
                        FROM routes r
                        JOIN schedules s ON r.id = s.route_id
                        WHERE r.origin = '$origin' 
                        AND r.destination = '$destination'
                        ORDER BY s.departure_time";
        
        $routes_result = $conn->query($routes_query);
        
        if ($routes_result && $routes_result->num_rows > 0) {
            while ($route = $routes_result->fetch_assoc()) {
                $routes[] = $route;
            }
        }
        
        $search_performed = true;
    }
}

// If no search performed, show all routes
if (!$search_performed && empty($error_message)) {
    $all_routes_query = "SELECT r.id as route_id, r.origin, r.destination, r.base_price, r.distance, r.capacity,
                        s.id as schedule_id, s.departure_time, s.arrival_time, s.days
                        FROM routes r
                        JOIN schedules s ON r.id = s.route_id
                        ORDER BY r.origin, r.destination, s.departure_time
                        LIMIT 20"; // Limit to 20 to avoid overwhelming the page
    
    $all_routes_result = $conn->query($all_routes_query);
    
    if ($all_routes_result && $all_routes_result->num_rows > 0) {
        while ($route = $all_routes_result->fetch_assoc()) {
            $routes[] = $route;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routes - FelixBus</title>
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
                    <a href="routes.php" class="hover:text-blue-200 font-medium">Routes</a>
                    <a href="timetables.php" class="hover:text-blue-200">Timetables</a>
                    <a href="prices.php" class="hover:text-blue-200">Prices</a>
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
                    <a href="register.php" class="bg-white text-blue-600 px-4 py-2 rounded-full font-medium hover:bg-blue-100">Register</a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Mobile menu button -->
        <div class="md:hidden flex justify-center pb-3">
            <button id="mobile-menu-button" class="text-white focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-blue-700 pb-4">
            <div class="container mx-auto px-4 flex flex-col space-y-2">
                <a href="routes.php" class="text-white py-2 font-medium">Routes</a>
                <a href="timetables.php" class="text-white py-2">Timetables</a>
                <a href="prices.php" class="text-white py-2">Prices</a>
                <a href="contact.php" class="text-white py-2">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-blue-700 py-16 text-white">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-4">Find Your Route</h1>
            <p class="text-xl max-w-2xl mx-auto">Search for bus routes between cities or browse our available routes.</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 -mt-16">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label for="origin" class="block text-gray-700 text-sm font-bold mb-2">Origin</label>
                        <select id="origin" name="origin" class="shadow border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select origin</option>
                            <?php 
                            if($origins_result && $origins_result->num_rows > 0): 
                                while($origin = $origins_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($origin['origin']); ?>" <?php echo isset($_POST['origin']) && $_POST['origin'] == $origin['origin'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($origin['origin']); ?>
                                </option>
                            <?php 
                                endwhile; 
                            endif; 
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="destination" class="block text-gray-700 text-sm font-bold mb-2">Destination</label>
                        <select id="destination" name="destination" class="shadow border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">Select destination</option>
                            <?php 
                            if($destinations_result && $destinations_result->num_rows > 0): 
                                while($destination = $destinations_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo htmlspecialchars($destination['destination']); ?>" <?php echo isset($_POST['destination']) && $_POST['destination'] == $destination['destination'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($destination['destination']); ?>
                                </option>
                            <?php 
                                endwhile; 
                            endif; 
                            ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" name="search" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full">
                            <i class="fas fa-search mr-2"></i> Search Routes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <div class="container mx-auto px-4 py-8">
        <?php if($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if($search_performed): ?>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <?php if(!empty($routes)): ?>
                    Available Routes from <?php echo htmlspecialchars($_POST['origin']); ?> to <?php echo htmlspecialchars($_POST['destination']); ?>
                <?php else: ?>
                    No Routes Found
                <?php endif; ?>
            </h2>
        <?php elseif(!empty($routes)): ?>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Available Bus Routes</h2>
        <?php endif; ?>
        
        <?php if(!empty($routes)): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origin</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departure Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Distance</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php 
                        $days_mapping = [
                            '1' => 'Monday',
                            '2' => 'Tuesday',
                            '3' => 'Wednesday',
                            '4' => 'Thursday',
                            '5' => 'Friday',
                            '6' => 'Saturday',
                            '7' => 'Sunday'
                        ];
                        
                        foreach($routes as $route): 
                            // Calculate duration
                            $departure = new DateTime($route['departure_time']);
                            $arrival = new DateTime($route['arrival_time']);
                            $duration = $departure->diff($arrival);
                            $duration_text = ($duration->h > 0 ? $duration->h . 'h ' : '') . $duration->i . 'm';
                            
                            // Format days
                            $days_array = explode(', ', $route['days']);
                            $days_text = [];
                            foreach($days_array as $day) {
                                if(isset($days_mapping[$day])) {
                                    $days_text[] = substr($days_mapping[$day], 0, 3);
                                }
                            }
                            $days_display = implode(', ', $days_text);
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($route['origin']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($route['destination']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('g:i A', strtotime($route['departure_time'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('g:i A', strtotime($route['arrival_time'])); ?></div>
                                <div class="text-xs text-gray-500">Duration: <?php echo $duration_text; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $days_display; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo number_format($route['distance'], 1); ?> km</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">$<?php echo number_format($route['base_price'], 2); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="#" class="text-blue-600 hover:text-blue-900 inline-flex items-center" 
                                   onclick="showBookingModal(<?php echo $route['schedule_id']; ?>)">
                                    <i class="fas fa-ticket-alt mr-1"></i> Book
                                </a>
                                <?php else: ?>
                                <a href="login.php?redirect=routes" class="text-blue-600 hover:text-blue-900 inline-flex items-center">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Login to Book
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif(!$search_performed): ?>
            <div class="text-center py-8">
                <p class="text-gray-500">Use the search form above to find routes between specific cities.</p>
            </div>
        <?php else: ?>
            <div class="text-center py-8">
                <p class="text-gray-500">No routes found for your search criteria. Please try different locations.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b">
                <h3 class="text-xl font-semibold text-gray-900">Book Ticket</h3>
            </div>
            <div class="p-6">
                <form action="booking.php" method="GET">
                    <input type="hidden" id="schedule_id" name="schedule_id">
                    
                    <div class="mb-4">
                        <label for="travel_date" class="block text-gray-700 text-sm font-bold mb-2">Select Travel Date</label>
                        <input type="date" id="travel_date" name="travel_date" min="<?php echo date('Y-m-d'); ?>" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div class="flex items-center justify-end pt-4 border-t">
                        <button type="button" onclick="hideBookingModal()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded mr-2 hover:bg-gray-300">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Proceed to Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-10 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">FelixBus</h3>
                    <p class="mb-4">Providing comfortable and reliable bus services.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-white hover:text-blue-200"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white hover:text-blue-200"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white hover:text-blue-200"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="routes.php" class="hover:text-blue-200">Routes</a></li>
                        <li><a href="timetables.php" class="hover:text-blue-200">Timetables</a></li>
                        <li><a href="prices.php" class="hover:text-blue-200">Prices</a></li>
                        <li><a href="contact.php" class="hover:text-blue-200">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Contact Information</h3>
                    <p class="mb-2"><i class="fas fa-map-marker-alt mr-2"></i> 123 Bus Station Road, City</p>
                    <p class="mb-2"><i class="fas fa-phone mr-2"></i> (123) 456-7890</p>
                    <p class="mb-2"><i class="fas fa-envelope mr-2"></i> info@felixbus.com</p>
                    <p><i class="fas fa-clock mr-2"></i> Mon-Fri: 8:00 AM - 8:00 PM</p>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-blue-700 text-center">
                <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Booking modal functions
        function showBookingModal(scheduleId) {
            document.getElementById('schedule_id').value = scheduleId;
            document.getElementById('bookingModal').classList.remove('hidden');
        }
        
        function hideBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
        }
    </script>
</body>
</html> 