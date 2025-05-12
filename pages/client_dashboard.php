<?php
session_start();
include_once('../database/basedados.h');

// Check if user is authenticated and is a client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit();
}

$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Get user wallet balance
$wallet_query = "SELECT balance FROM wallets WHERE user_id = $user_id";
$wallet_result = $conn->query($wallet_query);
$wallet_balance = 0;

if ($wallet_result && $wallet_result->num_rows > 0) {
    $wallet_balance = $wallet_result->fetch_assoc()['balance'];
}

// Get user's active tickets count
$active_tickets_query = "SELECT COUNT(*) as count FROM tickets WHERE user_id = $user_id AND status = 'active'";
$active_tickets_result = $conn->query($active_tickets_query);
$active_tickets_count = $active_tickets_result->fetch_assoc()['count'];

// Check if the alerts table exists, if not create it
$check_table_query = "SHOW TABLES LIKE 'alerts'";
$table_exists = $conn->query($check_table_query);

if($table_exists->num_rows == 0) {
    $create_table_query = "CREATE TABLE alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        active BOOLEAN DEFAULT TRUE
    )";
    $conn->query($create_table_query);
    
    // Insert sample alerts for demonstration
    $insert_alerts = "INSERT INTO alerts (title, message, type, active) VALUES 
    ('System Maintenance', 'Our system will be undergoing maintenance on June 30th. Service may be temporarily unavailable between 2-4 AM.', 'warning', TRUE),
    ('New Routes Available', 'We\'ve added new routes to our network! Check out the latest destinations.', 'info', TRUE),
    ('Holiday Schedule', 'Special holiday schedules will be in effect from December 20-27. Please check your trip details.', 'important', TRUE)";
    $conn->query($insert_alerts);
}

// Get alerts - updated to work with existing database structure
$alerts_query = "SELECT id, title, content as message, type, created_at FROM alerts ORDER BY created_at DESC LIMIT 5";
$alerts_result = $conn->query($alerts_query);

// Get recent wallet transactions
$transactions_query = "SELECT wt.* FROM wallet_transactions wt 
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .dashboard-card {
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-gray-100">
    <!-- Navigation -->
    <nav class="bg-black p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold flex items-center">
                <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span> 
                <span>Felix<span class="text-red-600">Bus</span></span>
            </a>
            <div class="flex items-center space-x-4">
                <a href="logout.php" class="hover:text-red-500"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">Client Dashboard</h1>
            <div class="text-gray-400">
                <span>Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Dashboard Cards -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-700 rounded-lg shadow-lg p-6 dashboard-card border border-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-400 text-sm">Wallet Balance</p>
                        <h3 class="text-3xl font-bold mt-2">€<?php echo number_format($wallet_balance, 2); ?></h3>
                    </div>
                    <div class="p-3 bg-green-500 bg-opacity-20 rounded-full text-green-400">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="client_wallet.php" class="text-sm text-green-400 hover:text-green-300 flex items-center">
                        <span>Manage Wallet</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-gray-800 to-gray-700 rounded-lg shadow-lg p-6 dashboard-card border border-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-400 text-sm">Active Tickets</p>
                        <h3 class="text-3xl font-bold mt-2"><?php echo $active_tickets_count; ?></h3>
                    </div>
                    <div class="p-3 bg-blue-500 bg-opacity-20 rounded-full text-blue-400">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="client_tickets.php" class="text-sm text-blue-400 hover:text-blue-300 flex items-center">
                        <span>View Tickets</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
            
            <div class="bg-gradient-to-br from-gray-800 to-gray-700 rounded-lg shadow-lg p-6 dashboard-card border border-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-400 text-sm">Book a Ticket</p>
                        <h3 class="text-3xl font-bold mt-2">New Trip</h3>
                    </div>
                    <div class="p-3 bg-purple-500 bg-opacity-20 rounded-full text-purple-400">
                        <i class="fas fa-route"></i>
                    </div>
                </div>
                <div class="mt-6">
                    <a href="routes.php" class="text-sm text-purple-400 hover:text-purple-300 flex items-center">
                        <span>Browse Routes</span>
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Alerts -->
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
                <h3 class="text-xl font-bold mb-4">Alerts</h3>
                
                <?php if($alerts_result && $alerts_result->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while($alert = $alerts_result->fetch_assoc()): ?>
                            <div class="bg-gray-700 rounded-lg p-4 border 
                                <?php 
                                    if($alert['type'] == 'warning') {
                                        echo 'border-yellow-600';
                                    } elseif($alert['type'] == 'important' || $alert['type'] == 'alert') {
                                        echo 'border-red-600';
                                    } elseif($alert['type'] == 'success') {
                                        echo 'border-green-600';
                                    } elseif($alert['type'] == 'promotion') {
                                        echo 'border-purple-600';
                                    } else {
                                        echo 'border-blue-600'; // info or default
                                    }
                                ?>">
                                <div class="flex flex-col md:flex-row justify-between">
                                    <div>
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($alert['title']); ?></h4>
                                        <p class="text-sm text-gray-400">
                                            <?php echo htmlspecialchars($alert['message']); ?>
                                        </p>
                                    </div>
                                    <div class="mt-2 md:mt-0 md:text-right">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold mb-2
                                        <?php 
                                            if($alert['type'] == 'warning') {
                                                echo 'bg-yellow-900 text-yellow-300';
                                            } elseif($alert['type'] == 'important' || $alert['type'] == 'alert') {
                                                echo 'bg-red-900 text-red-300';
                                            } elseif($alert['type'] == 'success') {
                                                echo 'bg-green-900 text-green-300';
                                            } elseif($alert['type'] == 'promotion') {
                                                echo 'bg-purple-900 text-purple-300';
                                            } else {
                                                echo 'bg-blue-900 text-blue-300'; // info or default
                                            }
                                        ?>">
                                            <?php 
                                                if($alert['type'] == 'alert') {
                                                    echo 'Alert';
                                                } elseif($alert['type'] == 'promotion') {
                                                    echo 'Promotion';
                                                } else {
                                                    echo ucfirst($alert['type']); 
                                                }
                                            ?>
                                        </span>
                                        <p class="text-gray-400 text-xs">
                                            <?php echo date('M j, Y', strtotime($alert['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="mt-4">
                        <a href="client_alerts.php" class="text-sm text-blue-400 hover:text-blue-300">View All Alerts</a>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-700 rounded-lg p-4 text-center">
                        <p class="text-gray-400">No alerts yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Transactions -->
            <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
                <h3 class="text-xl font-bold mb-4">Recent Wallet Activity</h3>
                
                <?php if($transactions_result && $transactions_result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-400 border-b border-gray-700">
                                    <th class="pb-3">Type</th>
                                    <th class="pb-3">Date</th>
                                    <th class="pb-3">Reference</th>
                                    <th class="pb-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($transaction = $transactions_result->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-700">
                                        <td class="py-3">
                                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold 
                                            <?php 
                                                if($transaction['transaction_type'] == 'deposit') {
                                                    echo 'bg-green-900 text-green-300';
                                                } elseif($transaction['transaction_type'] == 'withdrawal') {
                                                    echo 'bg-red-900 text-red-300';
                                                } elseif($transaction['transaction_type'] == 'refund') {
                                                    echo 'bg-blue-900 text-blue-300';
                                                } else {
                                                    echo 'bg-yellow-900 text-yellow-300';
                                                }
                                            ?>">
                                                <?php echo ucfirst($transaction['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 text-sm"><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></td>
                                        <td class="py-3 text-sm"><?php echo $transaction['reference'] ? htmlspecialchars($transaction['reference']) : '-'; ?></td>
                                        <td class="py-3 text-right font-medium 
                                        <?php 
                                            if($transaction['transaction_type'] == 'deposit' || $transaction['transaction_type'] == 'refund') {
                                                echo 'text-green-400';
                                            } else {
                                                echo 'text-red-400';
                                            }
                                        ?>">
                                            <?php 
                                                if($transaction['transaction_type'] == 'deposit' || $transaction['transaction_type'] == 'refund') {
                                                    echo '+';
                                                } else {
                                                    echo '-';
                                                }
                                            ?>
                                            €<?php echo number_format(abs($transaction['amount']), 2); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <a href="client_wallet.php" class="text-sm text-blue-400 hover:text-blue-300">View All Transactions</a>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-700 rounded-lg p-4 text-center">
                        <p class="text-gray-400">No wallet transactions yet.</p>
                        <a href="client_wallet.php" class="inline-block mt-3 bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-md transition duration-300">
                            Add Funds
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?> 