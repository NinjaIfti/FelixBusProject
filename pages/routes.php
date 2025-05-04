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
    $travel_date = $conn->real_escape_string($_POST['travel_date'] ?? '');
    
    if (empty($origin) || empty($destination) || empty($travel_date)) {
        $error_message = "Please fill in all search fields.";
    } else {
        // Get day of week (1 = Monday, 7 = Sunday)
        $day_of_week = date('N', strtotime($travel_date));
        
        // Query for routes
        $routes_query = "SELECT r.id as route_id, r.origin, r.destination, r.base_price, r.distance, r.capacity,
                        s.id as schedule_id, s.departure_time, s.arrival_time,
                        (SELECT COUNT(*) FROM tickets 
                         WHERE schedule_id = s.id AND travel_date = '$travel_date' AND status != 'cancelled') as booked_seats
                        FROM routes r
                        JOIN schedules s ON r.id = s.route_id
                        WHERE r.origin = '$origin' 
                        AND r.destination = '$destination'
                        AND s.days LIKE '%$day_of_week%'
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
            <p class="text-xl max-w-2xl mx-auto">Search for bus routes between cities and book your tickets online.</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 -mt-16">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="grid md:grid-cols-4 gap-4">
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
                    <div>
                        <label for="travel_date" class="block text-gray-700 text-sm font-bold mb-2">Travel Date</label>
                        <input type="date" id="travel_date" name="travel_date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['travel_date']) ? $_POST['travel_date'] : date('Y-m-d'); ?>" class="shadow border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
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
                    Available Routes from <?php echo htmlspecialchars($_POST['origin']); ?> to <?php echo htmlspecialchars($_POST['destination']); ?> on <?php echo date('F j, Y', strtotime($_POST['travel_date'])); ?>
                <?php else: ?>
                    No Routes Found
                <?php endif; ?>
            </h2>
            
            <?php if(!empty($routes)): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departure</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrival</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Availability</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($routes as $route): ?>
                                <?php 
                                    // Calculate duration
                                    $departure = new DateTime($route['departure_time']);
                                    $arrival = new DateTime($route['arrival_time']);
                                    $duration = $departure->diff($arrival);
                                    $duration_text = ($duration->h > 0 ? $duration->h . 'h ' : '') . $duration->i . 'm';
                                    
                                    // Calculate availability
                                    $available_seats = $route['capacity'] - $route['booked_seats'];
                                    $availability_percentage = ($available_seats / $route['capacity']) * 100;
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-semibold text-gray-900"><?php echo date('g:i A', strtotime($route['departure_time'])); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($route['origin']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-semibold text-gray-900"><?php echo date('g:i A', strtotime($route['arrival_time'])); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($route['destination']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $duration_text; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $route['distance'] ? $route['distance'] . ' km' : 'N/A'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-lg font-semibold text-gray-900">$<?php echo number_format($route['base_price'], 2); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $available_seats; ?> seats available
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                            <div class="
                                                <?php 
                                                    echo $availability_percentage > 50 ? 'bg-green-500' : 
                                                        ($availability_percentage > 20 ? 'bg-yellow-500' : 'bg-red-500'); 
                                                ?> 
                                                h-2 rounded-full" 
                                                style="width: <?php echo $availability_percentage; ?>%">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if($available_seats > 0): ?>
                                            <a href="booking.php?schedule_id=<?php echo $route['schedule_id']; ?>&travel_date=<?php echo $_POST['travel_date']; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring focus:ring-blue-200 disabled:opacity-25 transition">
                                                Book Now
                                            </a>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest">
                                                Sold Out
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-8 text-center">
                    <div class="text-gray-400 text-5xl mb-4">
                        <i class="fas fa-route"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Routes Available</h3>
                    <p class="text-gray-600 mb-6">We couldn't find any routes matching your search criteria.</p>
                    <p class="text-gray-600">Try searching with different locations or dates.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-md p-8">
                <div class="text-center mb-8">
                    <div class="text-blue-600 text-5xl mb-4">
                        <i class="fas fa-bus"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Popular Routes</h2>
                    <p class="text-gray-600">Check out some of our most popular bus routes.</p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">New York to Boston</h3>
                            <p class="text-gray-600 mb-4">Daily departures, 4h journey</p>
                            <p class="text-xl font-bold text-blue-600 mb-4">From $45.00</p>
                            <button onclick="setRoute('New York', 'Boston')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Check availability <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Los Angeles to San Francisco</h3>
                            <p class="text-gray-600 mb-4">Multiple daily departures, 6h journey</p>
                            <p class="text-xl font-bold text-blue-600 mb-4">From $65.00</p>
                            <button onclick="setRoute('Los Angeles', 'San Francisco')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Check availability <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Chicago to Detroit</h3>
                            <p class="text-gray-600 mb-4">Daily departures, 5h journey</p>
                            <p class="text-xl font-bold text-blue-600 mb-4">From $55.00</p>
                            <button onclick="setRoute('Chicago', 'Detroit')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Check availability <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-10 mt-12">
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
        
        // Function to set the route in the search form
        function setRoute(origin, destination) {
            document.getElementById('origin').value = origin;
            document.getElementById('destination').value = destination;
            document.getElementById('travel_date').focus();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?> 