<?php
session_start();
include_once('../basedados/basedados.h');

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Helper function to determine ticket class from price
function determineTicketClass($ticket_price, $base_price = null) {
    // If we don't have the base price, we have to estimate based on typical price ranges
    if ($base_price === null) {
        // Classification based on price ranges when base price is unknown
        if ($ticket_price <= 0) {
            return 'Standard';
        } elseif ($ticket_price >= 120) { // High value typically means Business
            return 'Business';
        } elseif ($ticket_price >= 60) { // Medium-high value typically means Premium
            return 'Premium';
        } else {
            return 'Standard';
        }
    }
    
    // If we have the base price, we can calculate the extra amount paid for the class
    $price_difference = $ticket_price - $base_price;
    
    if ($price_difference >= 35) { // Business class typically costs $40 more
        return 'Business';
    } elseif ($price_difference >= 20) { // Premium class typically costs $25 more
        return 'Premium';
    } else {
        return 'Standard';
    }
}

// Check if ticket ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: cliente_bilhetes.php");
    exit;
}

$ticket_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$conn = connectDatabase();

// Fetch ticket details with security check (ensure the ticket belongs to the user)
$ticket_query = "SELECT t.*, r.origin, r.destination, r.distance, r.base_price, 
                r.capacity, s.departure_time, s.arrival_time, s.days, 
                u.first_name, u.last_name, u.email, u.phone
                FROM tickets t 
                JOIN schedules s ON t.schedule_id = s.id 
                JOIN routes r ON s.route_id = r.id 
                JOIN users u ON t.user_id = u.id
                WHERE t.id = $ticket_id AND t.user_id = $user_id";
$ticket_result = $conn->query($ticket_query);

// Check if ticket exists and belongs to the user
if(!$ticket_result || $ticket_result->num_rows == 0) {
    header("Location: cliente_bilhetes.php");
    exit;
}

$ticket = $ticket_result->fetch_assoc();

// Calculate duration
$departure = new DateTime($ticket['departure_time']);
$arrival = new DateTime($ticket['arrival_time']);
$duration = $departure->diff($arrival);
$duration_text = ($duration->h > 0 ? $duration->h . 'h ' : '') . $duration->i . 'm';

// Format travel date
$formatted_travel_date = date('l, F j, Y', strtotime($ticket['travel_date']));

// Format days
$days_mapping = [
    '1' => 'Segunda',
    '2' => 'Terça',
    '3' => 'Quarta',
    '4' => 'Quinta',
    '5' => 'Sexta',
    '6' => 'Sabado',
    '7' => 'Domingo'
];

$days_array = explode(', ', $ticket['days']);
$days_text = [];
foreach($days_array as $day) {
    if(isset($days_mapping[$day])) {
        $days_text[] = $days_mapping[$day];
    }
}
$days_display = implode(', ', $days_text);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .nav-link { transition: all 0.3s ease; }
        .card { transition: all 0.3s ease; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-black text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-2xl font-bold flex items-center">
                    <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span>
                    <span>Felix<span class="text-red-600">Bus</span></span>
                </a>
                <div class="hidden md:flex space-x-4 ml-8">
                    <a href="cliente_rotas.php" class="hover:text-red-500 nav-link">Routes</a>
                    <a href="client_timetables.php" class="hover:text-red-500 nav-link">Timetables</a>
                    <a href="client_prices.php" class="hover:text-red-500 nav-link">Prices</a>
                    <a href="contactos.php" class="hover:text-red-500 nav-link">Contact</a>
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
                         class="absolute right-0 w-48 py-2 mt-2 bg-gray-800 rounded-md shadow-xl z-20">
                        <a href="cliente_painel.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                        <a href="cliente_bilhetes.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                        <a href="cliente_carteira.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                        <a href="perfil.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Profile</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-red-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Ticket Details</h1>
            <p class="text-lg">View all information about your ticket</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 flex-1">
        <!-- Actions Bar -->
        <div class="mb-6">
            <a href="cliente_bilhetes.php" class="text-red-500 hover:text-red-400">
                <i class="fas fa-arrow-left mr-1"></i> Back to My Tickets
            </a>
        </div>
        
        <!-- Ticket Information -->
        <div class="bg-gray-800 rounded-lg shadow-md overflow-hidden card">
            <!-- Ticket Header -->
            <div class="bg-red-700 text-white p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($ticket['origin']); ?> to <?php echo htmlspecialchars($ticket['destination']); ?></h2>
                        <p class="text-red-100"><?php echo $formatted_travel_date; ?></p>
                    </div>
                    <div>
                        <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full
                        <?php 
                            echo $ticket['status'] === 'active' ? 'bg-green-200 text-green-800' : 
                                ($ticket['status'] === 'used' ? 'bg-gray-200 text-gray-800' : 'bg-red-200 text-red-800'); 
                        ?>">
                            <?php echo ucfirst($ticket['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Ticket Details -->
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Trip Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Trip Information</h3>
                        
                        <div class="space-y-4">
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Ticket Number:</div>
                                <div class="w-2/3 font-bold text-white"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                            </div>
                            
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Date:</div>
                                <div class="w-2/3 text-white"><?php echo $formatted_travel_date; ?></div>
                            </div>
                            
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Departure:</div>
                                <div class="w-2/3">
                                    <div class="text-white"><?php echo date('g:i A', strtotime($ticket['departure_time'])); ?></div>
                                    <div class="text-sm text-gray-400"><?php echo htmlspecialchars($ticket['origin']); ?></div>
                                </div>
                            </div>
                            
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Arrival:</div>
                                <div class="w-2/3">
                                    <div class="text-white"><?php echo date('g:i A', strtotime($ticket['arrival_time'])); ?></div>
                                    <div class="text-sm text-gray-400"><?php echo htmlspecialchars($ticket['destination']); ?></div>
                                </div>
                            </div>
                            
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Duration:</div>
                                <div class="w-2/3 text-white"><?php echo $duration_text; ?></div>
                            </div>
                            
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Distance:</div>
                                <div class="w-2/3 text-white"><?php echo $ticket['distance'] ? $ticket['distance'] . ' km' : 'N/A'; ?></div>
                            </div>
                            
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Price:</div>
                                <div class="w-2/3 text-white">$<?php echo number_format($ticket['price'], 2); ?></div>
                            </div>
                            
                            <?php 
                                $ticket_class = determineTicketClass($ticket['price'], $ticket['base_price']);
                                $class_color = $ticket_class === 'Business' ? 'text-blue-500' : 
                                              ($ticket_class === 'Premium' ? 'text-yellow-500' : 'text-green-500');
                            ?>
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Travel Class:</div>
                                <div class="w-2/3 <?php echo $class_color; ?> font-semibold"><?php echo $ticket_class; ?></div>
                            </div>
                            
                            <div class="flex">
                                <div class="w-1/3 font-medium text-gray-400">Purchase Date:</div>
                                <div class="w-2/3 text-white"><?php echo date('F j, Y, g:i A', strtotime($ticket['purchased_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Passenger Information & QR Code -->
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Passenger Information</h3>
                        
                        <div class="space-y-4 mb-8">
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Name:</div>
                                <div class="w-2/3 text-white"><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></div>
                            </div>
                            
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Email:</div>
                                <div class="w-2/3 text-white"><?php echo htmlspecialchars($ticket['email']); ?></div>
                            </div>
                            
                            <?php if(!empty($ticket['phone'])): ?>
                            <div class="flex border-b border-gray-700 pb-3">
                                <div class="w-1/3 font-medium text-gray-400">Phone:</div>
                                <div class="w-2/3 text-white"><?php echo htmlspecialchars($ticket['phone']); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($ticket['status'] === 'active'): ?>
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold text-white mb-4">Boarding Pass</h3>
                            
                            <div class="bg-gray-700 p-4 rounded-lg flex flex-col items-center">
                                <p class="text-sm text-gray-400 mb-4">Present this QR code at boarding</p>
                                <div class="bg-white p-3 rounded-md shadow-md mb-2">
                                    <!-- Placeholder for a QR code -->
                                    <div class="w-32 h-32 flex items-center justify-center">
                                        <p class="text-xs text-gray-500">QR Code</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <?php if($ticket['status'] === 'active'): ?>
                <div class="mt-8 pt-4 border-t border-gray-700 flex justify-end">
                    <button onclick="window.print()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded">
                        <i class="fas fa-print mr-2"></i> Print Ticket
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Terms and Conditions -->
        <div class="bg-gray-800 rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-semibold text-white mb-4">Terms and Conditions</h3>
            
            <div class="text-sm text-gray-400 space-y-2">
                <p>1. This ticket is valid only for the specified date, route, and time.</p>
                <p>2. Please arrive at the station at least 30 minutes before the scheduled departure time.</p>
                <p>3. This ticket is non-transferable and valid identification may be required at boarding.</p>
                <p>4. Cancellations must be made at least 24 hours before departure for a refund.</p>
                <p>5. FelixBus is not responsible for delays caused by traffic, weather, or other circumstances beyond our control.</p>
                <p>6. Each passenger is allowed one piece of luggage (max 20kg) and one carry-on item.</p>
                <p>7. For any questions or assistance, please contact our customer service at support@felixbus.com.</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-black text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?> 