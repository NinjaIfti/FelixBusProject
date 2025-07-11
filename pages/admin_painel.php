<?php
session_start();
include_once('../basedados/basedados.h');
include_once('controle_de_acesso.php');
include_once('admin_security_check.php');

// Check if user has access to admin pages
checkPageAccess(['admin', 'staff']);

// Get user information
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_type'] === 'admin');

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get statistics
// Total users
$users_query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'client'";
$users_result = $conn->query($users_query);
$total_users = $users_result->fetch_assoc()['total'];

// Total routes
$routes_query = "SELECT COUNT(*) as total FROM routes";
$routes_result = $conn->query($routes_query);
$total_routes = $routes_result->fetch_assoc()['total'];

// Active tickets for today
$today = date('Y-m-d');
$tickets_query = "SELECT COUNT(*) as total FROM tickets WHERE travel_date = '$today' AND status = 'active'";
$tickets_result = $conn->query($tickets_query);
$today_tickets = $tickets_result->fetch_assoc()['total'];

// Get system statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM users WHERE user_type = 'client') as total_clients,
                (SELECT COUNT(*) FROM tickets WHERE status = 'active') as active_tickets,
                (SELECT COUNT(*) FROM routes) as total_routes,
                (SELECT SUM(balance) FROM wallets JOIN users ON wallets.user_id = users.id WHERE users.user_type = 'client') as total_client_balances";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get company wallet balance
$company_wallet_query = "SELECT w.balance 
                         FROM wallets w 
                         JOIN users u ON w.user_id = u.id 
                         WHERE u.username = 'felixbus'";
$company_wallet_result = $conn->query($company_wallet_query);
$company_wallet_balance = ($company_wallet_result && $company_wallet_result->num_rows > 0) ? 
                          $company_wallet_result->fetch_assoc()['balance'] : 0;

// Recent tickets (limited to 5)
$recent_tickets_query = "SELECT t.*, u.username, r.origin, r.destination, s.departure_time 
                        FROM tickets t 
                        JOIN users u ON t.user_id = u.id 
                        JOIN schedules s ON t.schedule_id = s.id 
                        JOIN routes r ON s.route_id = r.id
                        ORDER BY t.purchased_at DESC LIMIT 5";
$recent_tickets_result = $conn->query($recent_tickets_query);

// Recent users (limited to 5)
$recent_users_query = "SELECT * FROM users WHERE user_type = 'client' ORDER BY created_at DESC LIMIT 5";
$recent_users_result = $conn->query($recent_users_query);

// Recent transactions (limited to 5)
$recent_transactions_query = "SELECT wt.*, w.user_id, u.username 
                             FROM wallet_transactions wt 
                             JOIN wallets w ON wt.wallet_id = w.id 
                             JOIN users u ON w.user_id = u.id
                             ORDER BY wt.created_at DESC LIMIT 5";
$recent_transactions_result = $conn->query($recent_transactions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .nav-link {
            transition: all 0.3s ease;
        }
        
        .stats-card {
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .status-badge {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">
    <!-- Display any alerts -->
    <?php echo displayAlert(); ?>

    <!-- Sidebar -->
    <div class="flex flex-1">
        <div class="bg-black text-white w-64 py-6 flex-shrink-0 hidden md:block">
            <div class="px-6">
                <a href="admin_painel.php" class="text-2xl font-bold mb-8 flex items-center">
                    <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span> 
                    <span>Felix<span class="text-red-600">Bus</span></span>
                </a>
            </div>
            <nav class="mt-10">
                <a href="admin_painel.php" class="flex items-center py-3 px-6 bg-red-900 text-white nav-link">
                    <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                </a>
                <a href="admin_users.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-users mr-3"></i> Users
                </a>
                <a href="admin_rotas.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-route mr-3"></i> Routes
                </a>
                <a href="admin_bilhetes.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-ticket-alt mr-3"></i> Tickets
                </a>
                <a href="admin_gerir_carteira.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-wallet mr-3"></i> Manage Wallets
                </a>
                <?php if($is_admin): ?>
                <a href="admin_carteira_empresa.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-building mr-3"></i> Company Wallet
                </a>
                <a href="admin_alertas.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
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
            <header class="bg-gray-800 shadow-md">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="mr-4 text-gray-300 md:hidden">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h1 class="text-2xl font-semibold text-white">Dashboard</h1>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-2 text-sm text-gray-300"><?php echo htmlspecialchars($user['username']); ?></span>
                        <span class="bg-red-600 text-white text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            <?php echo ucfirst($user['user_type']); ?>
                        </span>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="container mx-auto px-4 py-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700 stats-card">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-white">Total Clients</h3>
                            <div class="text-3xl text-red-500">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['total_clients']); ?></p>
                    </div>
                    
                    <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700 stats-card">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-white">Active Tickets</h3>
                            <div class="text-3xl text-red-500">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['active_tickets']); ?></p>
                    </div>
                    
                    <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700 stats-card">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-white">Total Routes</h3>
                            <div class="text-3xl text-red-500">
                                <i class="fas fa-route"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-white"><?php echo number_format($stats['total_routes']); ?></p>
                    </div>
                    
                    <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700 stats-card">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-white">Company Wallet</h3>
                            <div class="text-3xl text-red-500">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-white">$<?php echo number_format($company_wallet_balance, 2); ?></p>
                        <?php if($is_admin): ?>
                        <a href="admin_carteira_empresa.php" class="text-red-400 hover:text-red-300 text-sm transition duration-300">View Details</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Tickets -->
                    <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-white">Recent Tickets</h2>
                            <a href="admin_bilhetes.php" class="text-red-400 hover:text-red-300 text-sm font-medium transition duration-300">View All</a>
                        </div>
                        
                        <?php if($recent_tickets_result && $recent_tickets_result->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while($ticket = $recent_tickets_result->fetch_assoc()): ?>
                                    <div class="border-b border-gray-700 pb-4">
                                        <div class="flex justify-between">
                                            <div>
                                                <p class="font-semibold text-white"><?php echo htmlspecialchars($ticket['username']); ?></p>
                                                <p class="text-sm text-gray-400">
                                                    <?php echo htmlspecialchars($ticket['origin']); ?> to <?php echo htmlspecialchars($ticket['destination']); ?><br>
                                                    <?php echo date('M j, Y', strtotime($ticket['travel_date'])); ?> at <?php echo date('g:i A', strtotime($ticket['departure_time'])); ?>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded status-badge
                                                <?php
                                                    echo $ticket['status'] === 'active' ? 'bg-green-900 text-green-300' : 
                                                        ($ticket['status'] === 'used' ? 'bg-gray-700 text-gray-300' : 'bg-red-900 text-red-300');
                                                ?>">
                                                    <?php echo ucfirst($ticket['status']); ?>
                                                </span>
                                                <p class="text-sm text-gray-400 mt-1">
                                                    $<?php echo number_format($ticket['price'], 2); ?><br>
                                                    <span class="text-xs"><?php echo date('M j, g:i A', strtotime($ticket['purchased_at'])); ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400">No recent ticket purchases found.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Recent Users -->
                    <div class="bg-gray-800 rounded-lg shadow-md p-6 border border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-white">New Users</h2>
                            <a href="admin_users.php" class="text-red-400 hover:text-red-300 text-sm font-medium transition duration-300">View All</a>
                        </div>
                        
                        <?php if($recent_users_result && $recent_users_result->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while($user = $recent_users_result->fetch_assoc()): ?>
                                    <div class="border-b border-gray-700 pb-4">
                                        <div class="flex justify-between">
                                            <div>
                                                <p class="font-semibold text-white"><?php echo htmlspecialchars($user['username']); ?></p>
                                                <p class="text-sm text-gray-400">
                                                    <?php echo htmlspecialchars($user['email']); ?><br>
                                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm text-gray-400">
                                                    Joined on:<br>
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400">No recent users found.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="bg-gray-800 rounded-lg shadow-md p-6 mt-8 border border-gray-700">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-white">Recent Wallet Transactions</h2>
                    </div>
                    
                    <?php if($recent_transactions_result && $recent_transactions_result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-gray-800">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 border-b border-gray-700 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                                        <th class="py-3 px-4 border-b border-gray-700 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                                        <th class="py-3 px-4 border-b border-gray-700 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Reference</th>
                                        <th class="py-3 px-4 border-b border-gray-700 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="py-3 px-4 border-b border-gray-700 bg-gray-900 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($transaction = $recent_transactions_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-700">
                                            <td class="py-4 px-4 border-b border-gray-700 text-sm text-gray-300">
                                                <?php echo htmlspecialchars($transaction['username']); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-700 text-sm">
                                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded status-badge
                                                <?php
                                                    echo $transaction['transaction_type'] === 'deposito' ? 'bg-green-900 text-green-300' : 
                                                        ($transaction['transaction_type'] === 'withdrawal' ? 'bg-red-900 text-red-300' : 
                                                        ($transaction['transaction_type'] === 'compra' ? 'bg-blue-900 text-blue-300' : 'bg-yellow-900 text-yellow-300'));
                                                ?>">
                                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-700 text-sm text-gray-300">
                                                <?php echo $transaction['reference'] ? htmlspecialchars($transaction['reference']) : '-'; ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-700 text-sm text-gray-300">
                                                <?php echo date('M j, Y, g:i A', strtotime($transaction['created_at'])); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-700 text-right text-sm font-medium 
                                            <?php
                                                echo ($transaction['transaction_type'] === 'deposito' || $transaction['transaction_type'] === 'refund') ? 'text-green-400' : 'text-red-400';
                                            ?>">
                                                <?php echo ($transaction['transaction_type'] === 'deposito' || $transaction['transaction_type'] === 'refund') ? '+' : '-'; ?>
                                                $<?php echo number_format(abs($transaction['amount']), 2); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-400">No recent transactions found.</p>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.bg-black').classList.toggle('hidden');
        });
        
        // Initialize any charts or dashboard components
        // ... any existing script ...
    </script>
    
    <!-- Session security checker -->
    <script src="admin_session_check.js"></script>
</body>
</html>
<?php $conn->close(); ?> 