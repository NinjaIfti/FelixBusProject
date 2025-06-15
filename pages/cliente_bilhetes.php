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

// Get user information
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Get all tickets for the user
$tickets_query = "SELECT t.*, r.origin, r.destination, r.base_price, s.departure_time, s.arrival_time 
                 FROM tickets t 
                 JOIN schedules s ON t.schedule_id = s.id 
                 JOIN routes r ON s.route_id = r.id 
                 WHERE t.user_id = $user_id 
                 ORDER BY t.travel_date DESC, s.departure_time ASC";
$tickets_result = $conn->query($tickets_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - FelixBus</title>
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
                    <a href="rotas.php" class="hover:text-red-500 nav-link">Routes</a>
                    <a href="horários.php" class="hover:text-red-500 nav-link">Timetables</a>
                    <a href="preços.php" class="hover:text-red-500 nav-link">Prices</a>
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
            <h1 class="text-3xl font-bold mb-2">My Tickets</h1>
            <p class="text-lg">View and manage your purchased tickets.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 flex-1">
        <!-- Actions Bar -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <a href="cliente_painel.php" class="text-red-500 hover:text-red-400">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            </div>
            <div>
                <a href="cliente_rotas.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                    <i class="fas fa-search mr-2"></i> Find New Routes
                </a>
            </div>
        </div>

        <!-- Tickets List -->
        <div class="bg-gray-800 rounded-lg shadow-md p-6 card">
            <h2 class="text-xl font-semibold text-white mb-6">Your Ticket History</h2>
            
            <?php if($tickets_result && $tickets_result->num_rows > 0): ?>
                <div class="grid grid-cols-1 gap-6">
                    <?php while($ticket = $tickets_result->fetch_assoc()): ?>
                        <div class="border border-gray-700 rounded-lg overflow-hidden shadow-sm bg-gray-900">
                            <div class="grid md:grid-cols-5 divide-x divide-gray-800">
                                <!-- Ticket Status -->
                                <div class="md:col-span-1 p-4
                                <?php 
                                    echo $ticket['status'] === 'active' ? 'bg-green-900' : 
                                        ($ticket['status'] === 'used' ? 'bg-gray-800' : 'bg-red-900'); 
                                ?>">
                                    <div class="flex flex-col h-full justify-between">
                                        <div>
                                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                            <?php 
                                                echo $ticket['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                    ($ticket['status'] === 'used' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800'); 
                                            ?>">
                                                <?php echo ucfirst($ticket['status']); ?>
                                            </span>
                                            <p class="mt-2 text-sm text-gray-400">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                                        </div>
                                        <div class="mt-4">
                                            <p class="text-sm text-gray-400">Purchased:<br><?php echo date('M j, Y', strtotime($ticket['purchased_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Ticket Details -->
                                <div class="md:col-span-4 p-4">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($ticket['origin']); ?> to <?php echo htmlspecialchars($ticket['destination']); ?></h3>
                                            <div class="mt-2 space-y-1">
                                                <p class="text-gray-400"><i class="far fa-calendar-alt mr-2"></i> <?php echo date('l, F j, Y', strtotime($ticket['travel_date'])); ?></p>
                                                <p class="text-gray-400"><i class="far fa-clock mr-2"></i> <?php echo date('g:i A', strtotime($ticket['departure_time'])); ?> - <?php echo date('g:i A', strtotime($ticket['arrival_time'])); ?></p>
                                                <p class="text-gray-400"><i class="fas fa-money-bill-wave mr-2"></i> $<?php echo number_format($ticket['price'], 2); ?></p>
                                                <?php 
                                                    $ticket_class = determineTicketClass($ticket['price'], $ticket['base_price']);
                                                    $class_color = $ticket_class === 'Business' ? 'text-blue-500' : ($ticket_class === 'Premium' ? 'text-yellow-500' : 'text-green-500');
                                                ?>
                                                <p class="text-gray-400"><i class="fas fa-tag mr-2"></i> Class: <span class="<?php echo $class_color; ?> font-medium"><?php echo $ticket_class; ?></span></p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center justify-end">
                                            <?php if($ticket['status'] === 'active'): ?>
                                                <div class="text-center">
                                                    <p class="text-sm text-gray-400 mb-2">Show this QR code at boarding</p>
                                                    <div class="inline-block bg-gray-700 p-2 rounded">
                                                        <!-- Placeholder for a QR code -->
                                                        <div class="w-24 h-24 bg-white flex items-center justify-center">
                                                            <p class="text-xs text-gray-500">QR Code</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if($ticket['status'] === 'active'): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-800 flex justify-end">
                                        <a href="cliente_detalhes_bilhetes.php?id=<?php echo $ticket['id']; ?>" class="text-red-500 hover:text-red-400 mr-4">
                                            <i class="fas fa-info-circle mr-1"></i> Details
                                        </a>
                                        <a href="#" onclick="window.print()" class="text-red-500 hover:text-red-400">
                                            <i class="fas fa-print mr-1"></i> Print Ticket
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="text-gray-400 text-5xl mb-4">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">No Tickets Found</h3>
                    <p class="text-gray-400 mb-6">You haven't purchased any tickets yet.</p>
                    <a href="cliente_rotas.php" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg inline-flex items-center">
                        <i class="fas fa-search mr-2"></i> Find Routes and Book Tickets
                    </a>
                </div>
            <?php endif; ?>
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