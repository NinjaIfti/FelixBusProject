<?php
session_start();
include_once('../../database/basedados.h');

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: ../login.php");
    exit;
}

// Get user information
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get wallet balance
$wallet_query = "SELECT balance FROM wallets WHERE user_id = $user_id";
$wallet_result = $conn->query($wallet_query);
$wallet = $wallet_result->fetch_assoc();

// Get recent tickets (limited to 5)
$tickets_query = "SELECT t.*, r.origin, r.destination, s.departure_time, s.arrival_time 
                 FROM tickets t 
                 JOIN schedules s ON t.schedule_id = s.id 
                 JOIN routes r ON s.route_id = r.id 
                 WHERE t.user_id = $user_id 
                 ORDER BY t.purchased_at DESC LIMIT 5";
$tickets_result = $conn->query($tickets_query);

// Get recent wallet transactions (limited to 5)
$transactions_query = "SELECT wt.* 
                      FROM wallet_transactions wt 
                      JOIN wallets w ON wt.wallet_id = w.id 
                      WHERE w.user_id = $user_id 
                      ORDER BY wt.created_at DESC LIMIT 5";
$transactions_result = $conn->query($transactions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FelixBus</title>
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
                <a href="../index.php" class="text-2xl font-bold flex items-center">
                    <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span>
                    <span>Felix<span class="text-red-600">Bus</span></span>
                </a>
                <div class="hidden md:flex space-x-4 ml-8">
                    <a href="../routes.php" class="hover:text-red-500 nav-link">Routes</a>
                    <a href="../timetables.php" class="hover:text-red-500 nav-link">Timetables</a>
                    <a href="../prices.php" class="hover:text-red-500 nav-link">Prices</a>
                    <a href="../contact.php" class="hover:text-red-500 nav-link">Contact</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative group">
                    <button class="flex items-center space-x-1">
                        <span>My Account</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 w-48 py-2 mt-2 bg-gray-800 rounded-md shadow-xl z-20 hidden group-hover:block">
                        <a href="dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                        <a href="tickets.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                        <a href="wallet.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                        <a href="../profile.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Profile</a>
                        <a href="../logout.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="bg-red-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
            <p class="text-lg">Manage your tickets and wallet from your personal dashboard.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 flex-1">
        <!-- Quick Stats -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <!-- Wallet Balance -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-md border border-gray-700 card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Wallet Balance</h2>
                    <span class="text-red-600 text-2xl">
                        <i class="fas fa-wallet"></i>
                    </span>
                </div>
                <p class="text-3xl font-bold text-white">$<?php echo number_format($wallet['balance'], 2); ?></p>
                <div class="mt-4">
                    <a href="wallet.php" class="text-red-500 hover:text-red-400 text-sm font-medium">Manage Wallet <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
            
            <!-- Active Tickets -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-md border border-gray-700 card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Active Tickets</h2>
                    <span class="text-red-600 text-2xl">
                        <i class="fas fa-ticket-alt"></i>
                    </span>
                </div>
                <?php
                $active_tickets_query = "SELECT COUNT(*) as total FROM tickets WHERE user_id = $user_id AND status = 'active'";
                $active_tickets_result = $conn->query($active_tickets_query);
                $active_tickets = $active_tickets_result->fetch_assoc();
                ?>
                <p class="text-3xl font-bold text-white"><?php echo $active_tickets['total']; ?></p>
                <div class="mt-4">
                    <a href="tickets.php" class="text-red-500 hover:text-red-400 text-sm font-medium">View Tickets <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
            
            <!-- Account Info -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-md border border-gray-700 card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-white">Account Info</h2>
                    <span class="text-red-600 text-2xl">
                        <i class="fas fa-user-circle"></i>
                    </span>
                </div>
                <p class="text-gray-300 mb-1">Email: <?php echo htmlspecialchars($user['email']); ?></p>
                <p class="text-gray-300">Member since: <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                <div class="mt-4">
                    <a href="../profile.php" class="text-red-500 hover:text-red-400 text-sm font-medium">Edit Profile <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="grid md:grid-cols-2 gap-8">
            <!-- Recent Tickets -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-md border border-gray-700 card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-white">Recent Tickets</h2>
                    <a href="tickets.php" class="text-red-500 hover:text-red-400 text-sm font-medium">View All</a>
                </div>
                
                <?php if($tickets_result && $tickets_result->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while($ticket = $tickets_result->fetch_assoc()): ?>
                            <div class="border-b border-gray-700 pb-4">
                                <div class="flex justify-between">
                                    <div>
                                        <h3 class="font-semibold text-white"><?php echo htmlspecialchars($ticket['origin']); ?> to <?php echo htmlspecialchars($ticket['destination']); ?></h3>
                                        <p class="text-sm text-gray-400">
                                            Date: <?php echo date('F j, Y', strtotime($ticket['travel_date'])); ?><br>
                                            Time: <?php echo date('g:i A', strtotime($ticket['departure_time'])); ?> - <?php echo date('g:i A', strtotime($ticket['arrival_time'])); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block px-2 py-1 text-xs font-semibold rounded 
                                        <?php
                                            echo $ticket['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                ($ticket['status'] === 'used' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800');
                                        ?>">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                        <p class="text-sm text-gray-400 mt-1">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-400">No tickets found.</p>
                <?php endif; ?>
            </div>
            
            <!-- Recent Transactions -->
            <div class="bg-gray-800 p-6 rounded-lg shadow-md border border-gray-700 card">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-white">Recent Transactions</h2>
                    <a href="wallet.php" class="text-red-500 hover:text-red-400 text-sm font-medium">View All</a>
                </div>
                <?php if($transactions_result && $transactions_result->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while($transaction = $transactions_result->fetch_assoc()): ?>
                            <div class="border-b border-gray-700 pb-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="font-semibold text-white capitalize"><?php echo htmlspecialchars($transaction['transaction_type']); ?></h3>
                                        <p class="text-sm text-gray-400">Ref: <?php echo htmlspecialchars($transaction['reference'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-bold <?php echo in_array($transaction['transaction_type'], ['deposit', 'refund']) ? 'text-green-400' : 'text-red-400'; ?>">
                                            <?php echo in_array($transaction['transaction_type'], ['deposit', 'refund']) ? '+' : '-'; ?>$
                                            <?php echo number_format($transaction['amount'], 2); ?>
                                        </span>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo date('M j, Y, g:i a', strtotime($transaction['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-400">No transactions found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-white mb-6">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="../routes.php" class="bg-gray-800 p-4 rounded-lg shadow-md text-center hover:bg-red-900 card">
                    <div class="text-red-600 text-3xl mb-2">
                        <i class="fas fa-search"></i>
                    </div>
                    <p class="font-medium text-white">Find Routes</p>
                </a>
                <a href="tickets.php" class="bg-gray-800 p-4 rounded-lg shadow-md text-center hover:bg-red-900 card">
                    <div class="text-red-600 text-3xl mb-2">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <p class="font-medium text-white">My Tickets</p>
                </a>
                <a href="wallet.php" class="bg-gray-800 p-4 rounded-lg shadow-md text-center hover:bg-red-900 card">
                    <div class="text-red-600 text-3xl mb-2">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <p class="font-medium text-white">Add Funds</p>
                </a>
                <a href="../profile.php" class="bg-gray-800 p-4 rounded-lg shadow-md text-center hover:bg-red-900 card">
                    <div class="text-red-600 text-3xl mb-2">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <p class="font-medium text-white">Edit Profile</p>
                </a>
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