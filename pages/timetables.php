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
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-image {
            background-image: url('https://images.unsplash.com/photo-1594663864734-bf35d8dec268?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
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
        
        tr {
            transition: all 0.2s ease;
        }
        
        tr:hover td {
            background-color: rgba(239, 68, 68, 0.05);
        }
        
        .route-card {
            transition: all 0.3s ease;
        }
        
        .route-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
                    <a href="routes.php" class="nav-link hover:text-red-500">Routes</a>
                    <a href="timetables.php" class="nav-link text-red-500 font-medium">Timetables</a>
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
                                <a href="client_dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                                <a href="client_tickets.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                                <a href="client_wallet.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                            <?php elseif($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin'): ?>
                                <a href="admin_dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Admin Panel</a>
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
                <a href="routes.php" class="text-white py-2 hover:text-red-500">Routes</a>
                <a href="timetables.php" class="text-white py-2 hover:text-red-500">Timetables</a>
                <a href="prices.php" class="text-white py-2 hover:text-red-500">Prices</a>
                <a href="contact.php" class="text-white py-2 hover:text-red-500">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative">
        <div class="hero-image h-64 bg-black relative">
            <div class="absolute inset-0 bg-gradient-to-r from-black to-transparent"></div>
            <div class="container mx-auto px-4 h-full flex items-center relative z-10">
                <div class="text-white max-w-2xl">
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">Bus <span class="text-red-600">Timetables</span></h1>
                    <p class="text-lg text-gray-300">View all available bus schedules and plan your journey with FelixBus.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-12">
        <!-- Route Selection -->
        <div class="bg-gray-800 rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-white mb-4">Find Your Bus Schedule</h2>
            
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="route_id" class="block text-sm font-medium text-gray-300 mb-2">Select Route</label>
                    <div class="relative custom-select-container">
                        <div class="custom-select-header w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white flex justify-between items-center cursor-pointer">
                            <span id="selected-route-text">-- Select a Route --</span>
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                        <div class="custom-select-dropdown absolute z-50 hidden w-full mt-1 bg-gray-800 border border-gray-600 rounded-md shadow-lg max-h-80 overflow-y-auto">
                            <div class="sticky top-0 bg-gray-800 p-2 border-b border-gray-700">
                                <div class="relative">
                                    <input type="text" class="route-search-input w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white" placeholder="Search routes...">
                                    <button type="button" class="clear-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-white">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="custom-select-options p-1">
                                
                                <?php if($routes_result && $routes_result->num_rows > 0): ?>
                                    <?php mysqli_data_seek($routes_result, 0); ?>
                                    <?php while($route = $routes_result->fetch_assoc()): ?>
                                        <div class="custom-select-option px-4 py-2 cursor-pointer hover:bg-gray-700 rounded" 
                                             data-value="<?php echo $route['id']; ?>"
                                             data-origin="<?php echo strtolower(htmlspecialchars($route['origin'])); ?>"
                                             data-destination="<?php echo strtolower(htmlspecialchars($route['destination'])); ?>">
                                            <?php echo htmlspecialchars($route['origin']); ?> to <?php echo htmlspecialchars($route['destination']); ?>
                                        </div>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <input type="hidden" name="route_id" id="route_id" value="<?php echo isset($_GET['route_id']) ? $_GET['route_id'] : ''; ?>">
                    </div>
                </div>
                
                <div>
                    <label for="day" class="block text-sm font-medium text-gray-300 mb-2">Day of Week (Optional)</label>
                    <select id="day" name="day" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 text-white">
                        <option value="">Any Day</option>
                        <?php foreach($days_of_week as $day): ?>
                            <option value="<?php echo $day; ?>" <?php echo (isset($_GET['day']) && $_GET['day'] == $day) ? 'selected' : ''; ?>>
                                <?php echo $day; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md transition duration-300 btn-primary">
                        <i class="fas fa-search mr-2"></i> View Schedules
                    </button>
                </div>
            </form>
        </div>
        
        <?php if($selected_route): ?>
            <!-- Schedules for Selected Route -->
            <div class="bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-white mb-2">
                    <span class="text-red-500"><?php echo htmlspecialchars($selected_route['origin']); ?></span> to <span class="text-red-500"><?php echo htmlspecialchars($selected_route['destination']); ?></span>
                </h2>
                <p class="text-gray-400 mb-6">
                    <i class="fas fa-route mr-2"></i> Distance: <?php echo $selected_route['distance']; ?> km | 
                    <i class="fas fa-tag mr-2"></i> Base Price: $<?php echo number_format($selected_route['base_price'], 2); ?>
                </p>
                
                <?php if(count($schedules) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                            <thead>
                                <tr>
                                    <th class="py-3 px-6 bg-gray-800 text-left text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-700">
                                        Departure Time
                                    </th>
                                    <th class="py-3 px-6 bg-gray-800 text-left text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-700">
                                        Arrival Time
                                    </th>
                                    <th class="py-3 px-6 bg-gray-800 text-left text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-700">
                                        Days of Operation
                                    </th>
                                    <th class="py-3 px-6 bg-gray-800 text-center text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-700">
                                        Journey Time
                                    </th>
                                    <th class="py-3 px-6 bg-gray-800 text-center text-xs font-medium text-gray-300 uppercase tracking-wider border-b border-gray-700">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
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
                                    <tr class="hover:bg-gray-800">
                                        <td class="py-4 px-6 text-sm font-medium text-gray-200">
                                            <i class="fas fa-clock text-red-500 mr-2"></i><?php echo $departure_time; ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-200">
                                            <i class="fas fa-clock text-green-500 mr-2"></i><?php echo $arrival_time; ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-200">
                                            <i class="fas fa-calendar-alt text-gray-400 mr-2"></i><?php echo htmlspecialchars($schedule['days']); ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm text-gray-200 text-center">
                                            <i class="fas fa-hourglass-half text-yellow-500 mr-2"></i><?php echo $journey_time; ?>
                                        </td>
                                        <td class="py-4 px-6 text-sm font-medium text-center">
                                            <a href="booking.php?schedule_id=<?php echo $schedule['id']; ?>&travel_date=<?php echo date('Y-m-d'); ?>" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded text-sm transition duration-300 inline-block">
                                                <i class="fas fa-ticket-alt mr-1"></i> Book Now
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 bg-gray-800 rounded-lg">
                        <div class="text-gray-400 text-5xl mb-4">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <p class="text-gray-300">No schedules found for this route<?php echo isset($_GET['day']) && !empty($_GET['day']) ? ' on ' . $_GET['day'] : ''; ?>.</p>
                        <?php if(isset($_GET['day']) && !empty($_GET['day'])): ?>
                            <p class="mt-2">
                                <a href="?route_id=<?php echo $selected_route['id']; ?>" class="text-red-500 hover:text-red-400">
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
                    echo '<a href="?route_id=' . $reverse_route_id . '" class="text-red-500 hover:text-red-400 inline-flex items-center">';
                    echo '<i class="fas fa-exchange-alt mr-2"></i> View return journey: ' . htmlspecialchars($origin) . ' to ' . htmlspecialchars($destination);
                    echo '</a>';
                    echo '</div>';
                }
                ?>
            </div>
        <?php else: ?>
            <!-- No route selected -->
            <div class="bg-gray-800 rounded-lg shadow-md p-6 text-center">
                <div class="py-10">
                    <div class="text-red-500 text-6xl mb-6">
                        <i class="fas fa-bus"></i>
                    </div>
                    <p class="text-gray-200 text-lg mb-4">Please select a route to view available schedules.</p>
                    <p class="text-gray-400">Our buses connect to multiple destinations across the country with comfortable and reliable service.</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Popular Routes -->
        <div class="mt-12">
            <h2 class="text-2xl font-semibold text-white mb-6 flex items-center">
                <span class="text-red-500 mr-3"><i class="fas fa-star"></i></span>
                Popular Routes
            </h2>
            
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
                    <div class="bg-gray-800 rounded-lg shadow-md overflow-hidden route-card border border-gray-700">
                        <div class="bg-gradient-to-r from-red-600 to-red-800 text-white py-3 px-4">
                            <h3 class="font-bold"><?php echo htmlspecialchars($route['origin']); ?> to <?php echo htmlspecialchars($route['destination']); ?></h3>
                        </div>
                        <div class="p-5">
                            <p class="text-gray-300 mb-4">
                                <span class="block flex items-center"><i class="fas fa-route mr-2 text-gray-400"></i> Distance: <?php echo $route['distance']; ?> km</span>
                                <span class="block mt-2 flex items-center"><i class="fas fa-tag mr-2 text-gray-400"></i> From $<?php echo number_format($route['base_price'], 2); ?></span>
                            </p>
                            <a href="?route_id=<?php echo $route['id']; ?>" class="block text-center bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded transition duration-300 btn-primary">
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
    <footer class="bg-black text-white py-12 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">About FelixBus</h3>
                    <p class="text-gray-400">We provide reliable and comfortable bus transportation services connecting cities across the country.</p>
                    <div class="mt-4 flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300 social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300 social-icon">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300 social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300 social-icon">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="routes.php" class="text-gray-400 hover:text-red-500 transition duration-300">Routes</a></li>
                        <li><a href="timetables.php" class="text-gray-400 hover:text-red-500 transition duration-300">Timetables</a></li>
                        <li><a href="prices.php" class="text-gray-400 hover:text-red-500 transition duration-300">Prices</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-red-500 transition duration-300">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Information</h3>
                    <p class="text-gray-400 flex items-center mb-2"><i class="fas fa-map-marker-alt mr-3 text-red-500"></i> 123 Bus Station Road, City Center</p>
                    <p class="text-gray-400 flex items-center mb-2"><i class="fas fa-phone mr-3 text-red-500"></i> (123) 456-7890</p>
                    <p class="text-gray-400 flex items-center"><i class="fas fa-envelope mr-3 text-red-500"></i> info@felixbus.com</p>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-6 text-center">
                <p class="text-gray-500">&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
        
        // Custom select with integrated search
        document.addEventListener('DOMContentLoaded', function() {
            const selectContainer = document.querySelector('.custom-select-container');
            const selectHeader = document.querySelector('.custom-select-header');
            const dropdown = document.querySelector('.custom-select-dropdown');
            const options = document.querySelectorAll('.custom-select-option');
            const hiddenInput = document.getElementById('route_id');
            const selectedText = document.getElementById('selected-route-text');
            const searchInput = document.querySelector('.route-search-input');
            const clearSearchButton = document.querySelector('.clear-search');
            
            // Set initial selected value if exists
            const initialValue = hiddenInput.value;
            if (initialValue) {
                const selectedOption = Array.from(options).find(opt => opt.getAttribute('data-value') === initialValue);
                if (selectedOption) {
                    selectedText.textContent = selectedOption.textContent;
                    selectedText.classList.add('text-white');
                    selectedText.classList.remove('text-gray-400');
                }
            }
            
            // Toggle dropdown
            selectHeader.addEventListener('click', function() {
                dropdown.classList.toggle('hidden');
                if (!dropdown.classList.contains('hidden')) {
                    searchInput.focus();
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!selectContainer.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
            
            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                let anyVisible = false;
                options.forEach(option => {
                    if (option.getAttribute('data-value') === '') return; // Skip the placeholder
                    
                    const origin = option.getAttribute('data-origin') || '';
                    const destination = option.getAttribute('data-destination') || '';
                    const text = (origin + ' to ' + destination).toLowerCase();
                    
                    if (searchTerm === '' || text.includes(searchTerm)) {
                        option.style.display = '';
                        anyVisible = true;
                    } else {
                        option.style.display = 'none';
                    }
                });
                
                // Show no results message if needed
                const noResultsEl = dropdown.querySelector('.no-results');
                if (!anyVisible) {
                    if (!noResultsEl) {
                        const noResults = document.createElement('div');
                        noResults.className = 'no-results px-4 py-2 text-gray-400 text-center';
                        noResults.textContent = 'No routes found';
                        dropdown.querySelector('.custom-select-options').appendChild(noResults);
                    }
                } else if (noResultsEl) {
                    noResultsEl.remove();
                }
            });
            
            // Clear search
            clearSearchButton.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
            
            // Select option
            options.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    hiddenInput.value = value;
                    
                    if (value) {
                        selectedText.textContent = this.textContent;
                        selectedText.classList.add('text-white');
                        selectedText.classList.remove('text-gray-400');
                    } else {
                        selectedText.textContent = '-- Select a Route --';
                        selectedText.classList.remove('text-white');
                        selectedText.classList.add('text-gray-400');
                    }
                    
                    dropdown.classList.add('hidden');
                    
                    // Submit form if a valid option is selected
                    if (value) {
                        const form = hiddenInput.closest('form');
                        form.submit();
                    }
                });
            });
            
            // Handle keyboard events
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    // Select the first visible option
                    const visibleOptions = Array.from(options).filter(opt => 
                        opt.getAttribute('data-value') !== '' && 
                        opt.style.display !== 'none'
                    );
                    
                    if (visibleOptions.length === 1) {
                        visibleOptions[0].click();
                    }
                    e.preventDefault();
                } else if (e.key === 'Escape') {
                    dropdown.classList.add('hidden');
                }
            });
        });
        
        // Auto-submit form when day changes
        document.addEventListener('DOMContentLoaded', function() {
            // When day changes
            document.getElementById('day').addEventListener('change', function() {
                const routeInput = document.getElementById('route_id');
                if (routeInput.value !== '') {
                    // Route is selected, submit the form
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?> 