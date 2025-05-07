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
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-image {
            background-image: url('https://images.unsplash.com/photo-1464219789935-c2d9d9eefd40?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #ef4444;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .route-card {
            transition: all 0.3s ease;
        }
        
        .route-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            z-index: -1;
        }
        
        .btn-primary:hover::before {
            left: 0;
        }
        
        .search-form {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        tr {
            transition: all 0.2s ease;
        }
        
        tr:hover td {
            background-color: rgba(239, 68, 68, 0.05);
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-gray-100">
    <!-- Navigation -->
    <nav class="bg-black text-white shadow-lg">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-2xl font-bold flex items-center">
                    <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span> 
                    <span>Felix<span class="text-red-600">Bus</span></span>
                </a>
                <div class="hidden md:flex space-x-6">
                    <a href="routes.php" class="nav-link text-red-500 font-medium">Routes</a>
                    <a href="timetables.php" class="nav-link hover:text-red-500">Timetables</a>
                    <a href="prices.php" class="nav-link hover:text-red-500">Prices</a>
                    <a href="contact.php" class="nav-link hover:text-red-500">Contact</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="flex items-center space-x-1 hover:text-red-500 transition duration-300">
                            <span>My Account</span>
                            <i class="fas fa-chevron-down text-xs transition duration-300" :class="{ 'transform rotate-180': open }"></i>
                        </button>
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 w-48 py-2 mt-2 bg-gray-800 rounded-md shadow-xl z-20">
                            <?php if($_SESSION['user_type'] === 'client'): ?>
                                <a href="client/dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                                <a href="client/tickets.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                                <a href="client/wallet.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                            <?php elseif($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin'): ?>
                                <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Admin Panel</a>
                            <?php endif; ?>
                            <a href="profile.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Profile</a>
                            <a href="logout.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link hover:text-red-500">Login</a>
                    <a href="register.php" class="bg-red-600 text-white px-4 py-2 rounded-md font-medium hover:bg-red-700 transition duration-300 btn-primary">Register</a>
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
        <div id="mobile-menu" class="md:hidden hidden bg-gray-800 pb-4">
            <div class="container mx-auto px-4 flex flex-col space-y-2">
                <a href="routes.php" class="text-red-500 py-2 font-medium">Routes</a>
                <a href="timetables.php" class="text-white py-2 hover:text-red-500">Timetables</a>
                <a href="prices.php" class="text-white py-2 hover:text-red-500">Prices</a>
                <a href="contact.php" class="text-white py-2 hover:text-red-500">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="hero-image py-24 relative">
        <div class="absolute inset-0 bg-gradient-to-r from-black to-transparent"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Find Your <span class="text-red-600">Perfect</span> Route</h1>
            <p class="text-xl max-w-2xl mx-auto">Discover premium bus connections between cities with our extensive network.</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="container mx-auto px-4">
        <div class="bg-gray-800 rounded-lg shadow-lg p-6 -mt-16 search-form relative z-20 border border-gray-700">
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label for="origin" class="block text-gray-300 text-sm font-medium mb-2">Origin</label>
                        <select id="origin" name="origin" class="w-full py-3 px-4 bg-gray-700 text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 transition-all" required>
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
                        <label for="destination" class="block text-gray-300 text-sm font-medium mb-2">Destination</label>
                        <select id="destination" name="destination" class="w-full py-3 px-4 bg-gray-700 text-white border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 transition-all" required>
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
                        <button type="submit" name="search" class="bg-red-600 hover:bg-red-700 text-white font-medium py-3 px-6 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 w-full transition duration-300 btn-primary">
                            <i class="fas fa-search mr-2"></i> Find Routes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <div class="container mx-auto px-4 py-12">
        <?php if($error_message): ?>
            <div class="bg-red-900 border-l-4 border-red-500 text-white p-4 mb-6 rounded-md" role="alert">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="ml-3">
                        <p><?php echo $error_message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if($search_performed): ?>
            <h2 class="text-2xl font-bold text-white mb-2">
                <?php if(!empty($routes)): ?>
                    Routes from <span class="text-red-500"><?php echo htmlspecialchars($_POST['origin']); ?></span> to <span class="text-red-500"><?php echo htmlspecialchars($_POST['destination']); ?></span>
                <?php else: ?>
                    No Routes Found
                <?php endif; ?>
            </h2>
            <?php if(!empty($routes)): ?>
                <p class="text-gray-400 mb-6">Select from our available routes and schedules</p>
            <?php else: ?>
                <p class="text-gray-400 mb-6">We couldn't find any routes matching your search. Please try different locations.</p>
            <?php endif; ?>
        <?php elseif(!empty($routes)): ?>
            <h2 class="text-2xl font-bold text-white mb-2">Popular Routes</h2>
            <p class="text-gray-400 mb-6">Our most traveled premium routes</p>
        <?php endif; ?>
        
        <?php if(!empty($routes)): ?>
            <div class="bg-gray-800 rounded-lg shadow-lg overflow-hidden border border-gray-700">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Origin</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Destination</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Departure</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Arrival</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Days</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Duration</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Price</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
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
                            $days_array = explode(',', $route['days']);
                            $days_text = [];
                            foreach($days_array as $day_num) {
                                if(isset($days_mapping[$day_num])) {
                                    $days_text[] = substr($days_mapping[$day_num], 0, 3);
                                }
                            }
                            $days_formatted = implode(', ', $days_text);
                        ?>
                            <tr class="route-card">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                    <?php echo htmlspecialchars($route['origin']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                    <?php echo htmlspecialchars($route['destination']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo date('g:i A', strtotime($route['departure_time'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo date('g:i A', strtotime($route['arrival_time'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <?php echo $days_formatted; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-red-500 mr-2"></i>
                                        <?php echo $duration_text; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-red-500">
                                    $<?php echo number_format($route['base_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="booking.php?schedule_id=<?php echo $route['schedule_id']; ?>&travel_date=<?php echo date('Y-m-d'); ?>" class="inline-flex items-center px-3 py-2 border border-red-500 text-red-500 rounded-md hover:bg-red-500 hover:text-white transition duration-300">
                                        <i class="fas fa-ticket-alt mr-2"></i> Book Now
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif($search_performed): ?>
            <div class="bg-gray-800 text-center py-16 rounded-lg border border-gray-700">
                <div class="text-red-500 text-5xl mb-4">
                    <i class="fas fa-route"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">No routes found</h3>
                <p class="text-gray-400 mb-6 max-w-md mx-auto">We couldn't find any routes matching your search criteria. Please try a different origin or destination.</p>
                <a href="routes.php" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-300">
                    <i class="fas fa-search mr-2"></i> New Search
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Popular Destinations -->
    <section class="py-12 bg-black">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-white mb-2 text-center">Popular Destinations</h2>
            <p class="text-center text-gray-400 mb-10">Explore some of our most requested locations</p>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="relative group overflow-hidden rounded-lg h-64 route-card">
                    <img src="https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="New York" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-6">
                        <h3 class="text-xl font-bold text-white mb-1">New York</h3>
                        <p class="text-gray-300 text-sm mb-3">Experience the Big Apple</p>
                        <a href="#" class="inline-flex items-center text-white text-sm">
                            <span class="mr-2">View Routes</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="relative group overflow-hidden rounded-lg h-64 route-card">
                    <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Chicago" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-6">
                        <h3 class="text-xl font-bold text-white mb-1">Chicago</h3>
                        <p class="text-gray-300 text-sm mb-3">Windy City Adventures</p>
                        <a href="#" class="inline-flex items-center text-white text-sm">
                            <span class="mr-2">View Routes</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="relative group overflow-hidden rounded-lg h-64 route-card">
                    <img src="https://images.unsplash.com/photo-1501594907352-04cda38ebc29?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Los Angeles" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-6">
                        <h3 class="text-xl font-bold text-white mb-1">Los Angeles</h3>
                        <p class="text-gray-300 text-sm mb-3">City of Angels</p>
                        <a href="#" class="inline-flex items-center text-white text-sm">
                            <span class="mr-2">View Routes</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="relative group overflow-hidden rounded-lg h-64 route-card">
                    <img src="https://images.unsplash.com/photo-1574555059045-3bc478721f0a?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Boston" class="w-full h-full object-cover transition duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-black to-transparent"></div>
                    <div class="absolute bottom-0 left-0 p-6">
                        <h3 class="text-xl font-bold text-white mb-1">Boston</h3>
                        <p class="text-gray-300 text-sm mb-3">Historic New England</p>
                        <a href="#" class="inline-flex items-center text-white text-sm">
                            <span class="mr-2">View Routes</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Felix<span class="text-red-600">Bus</span></h3>
                    <p class="mb-4 text-gray-400">Redefining luxury travel with comfort, reliability, and exceptional service.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="routes.php" class="text-gray-400 hover:text-red-500 transition duration-300">Routes</a></li>
                        <li><a href="timetables.php" class="text-gray-400 hover:text-red-500 transition duration-300">Timetables</a></li>
                        <li><a href="prices.php" class="text-gray-400 hover:text-red-500 transition duration-300">Prices</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-red-500 transition duration-300">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-red-500 transition duration-300">Terms & Conditions</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-red-500 transition duration-300">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Contact Information</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-red-500"></i>
                            <span class="text-gray-400">123 Transport Avenue, Downtown<br>Business District, 10001</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3 text-red-500"></i>
                            <span class="text-gray-400">(123) 456-7890</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-red-500"></i>
                            <span class="text-gray-400">info@felixbus.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock mr-3 text-red-500"></i>
                            <span class="text-gray-400">Mon-Sun: 8:00 AM - 10:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500">&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html> 