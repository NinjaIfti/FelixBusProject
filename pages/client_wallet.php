<?php
session_start();
include_once('../database/basedados.h');

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit;
}

// Get user information
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Get wallet information and ensure it exists
$wallet_query = "SELECT id, balance FROM wallets WHERE user_id = $user_id";
$wallet_result = $conn->query($wallet_query);

// Check if wallet exists, if not create one
if($wallet_result->num_rows == 0) {
    // Create a wallet for the user
    $create_wallet_query = "INSERT INTO wallets (user_id, balance) VALUES ($user_id, 0.00)";
    if($conn->query($create_wallet_query) === TRUE) {
        // Fetch the newly created wallet
        $wallet_query = "SELECT id, balance FROM wallets WHERE user_id = $user_id";
        $wallet_result = $conn->query($wallet_query);
    } else {
        $error_message = "Error creating wallet: " . $conn->error;
    }
}

$wallet = $wallet_result->fetch_assoc();
$wallet_id = $wallet['id'];
$current_balance = $wallet['balance'];

// Process deposit/withdrawal
$success_message = '';
$error_message = '';

// Check for success/error messages in session (from redirects)
if(isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if(isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    
    if($action === 'deposit' && $amount > 0) {
        // Begin transaction to ensure data integrity
        $conn->begin_transaction();
        try {
            // Add funds to wallet
            $update_wallet = "UPDATE wallets SET balance = balance + $amount WHERE id = $wallet_id";
            
            if($conn->query($update_wallet) === TRUE) {
                // Log transaction
                $transaction_type = 'deposit';
                $reference = "Manual deposit";
                
                $log_transaction = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference) 
                                VALUES ($wallet_id, $amount, '$transaction_type', '$reference')";
                
                if($conn->query($log_transaction) === TRUE) {
                    // Commit the transaction
                    $conn->commit();
                    $_SESSION['success_message'] = "Successfully added $" . number_format($amount, 2) . " to your wallet.";
                    
                    // Redirect to prevent form resubmission
                    header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
                    exit;
                } else {
                    throw new Exception("Error logging transaction: " . $conn->error);
                }
            } else {
                throw new Exception("Error updating wallet: " . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback the transaction on error
            $conn->rollback();
            $_SESSION['error_message'] = $e->getMessage();
            
            // Redirect to prevent form resubmission
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
            exit;
        }
    } elseif($action === 'withdraw' && $amount > 0) {
        // Check if sufficient funds
        if($amount <= $current_balance) {
            // Begin transaction
            $conn->begin_transaction();
            try {
                // Withdraw funds from wallet
                $update_wallet = "UPDATE wallets SET balance = balance - $amount WHERE id = $wallet_id";
                
                if($conn->query($update_wallet) === TRUE) {
                    // Log transaction
                    $transaction_type = 'withdrawal';
                    $reference = "Manual withdrawal";
                    
                    $log_transaction = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference) 
                                    VALUES ($wallet_id, $amount, '$transaction_type', '$reference')";
                    
                    if($conn->query($log_transaction) === TRUE) {
                        // Commit the transaction
                        $conn->commit();
                        $_SESSION['success_message'] = "Successfully withdrawn $" . number_format($amount, 2) . " from your wallet.";
                        
                        // Redirect to prevent form resubmission
                        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
                        exit;
                    } else {
                        throw new Exception("Error logging transaction: " . $conn->error);
                    }
                } else {
                    throw new Exception("Error updating wallet: " . $conn->error);
                }
            } catch (Exception $e) {
                // Rollback the transaction on error
                $conn->rollback();
                $_SESSION['error_message'] = $e->getMessage();
                
                // Redirect to prevent form resubmission
                header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
                exit;
            }
        } else {
            $_SESSION['error_message'] = "Insufficient funds for withdrawal.";
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Invalid amount or action.";
        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
        exit;
    }
}

// Get updated wallet balance after transactions
$wallet_query = "SELECT balance FROM wallets WHERE user_id = $user_id";
$wallet_result = $conn->query($wallet_query);
$wallet = $wallet_result->fetch_assoc();

// Get transaction history
$transactions_query = "SELECT wt.* 
                      FROM wallet_transactions wt 
                      JOIN wallets w ON wt.wallet_id = w.id 
                      WHERE w.user_id = $user_id 
                      ORDER BY wt.created_at DESC";
$transactions_result = $conn->query($transactions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - FelixBus</title>
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
                    <a href="routes.php" class="hover:text-red-500 nav-link">Routes</a>
                    <a href="timetables.php" class="hover:text-red-500 nav-link">Timetables</a>
                    <a href="prices.php" class="hover:text-red-500 nav-link">Prices</a>
                    <a href="contact.php" class="hover:text-red-500 nav-link">Contact</a>
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
                        <a href="client_dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                        <a href="client_tickets.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                        <a href="client_wallet.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                        <a href="profile.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Profile</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-red-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Wallet Management</h1>
            <p class="text-lg">Add funds, withdraw, and view your transaction history.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 flex-1">
        <?php if($success_message): ?>
            <div class="bg-green-900 border-l-4 border-green-500 text-green-100 p-4 mb-6" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="bg-red-900 border-l-4 border-red-500 text-red-100 p-4 mb-6" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Wallet Balance Card -->
            <div class="md:col-span-1">
                <div class="bg-gray-800 p-6 rounded-lg shadow-md mb-6 card border border-gray-700">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-white">Wallet Balance</h2>
                        <span class="text-red-600 text-2xl">
                            <i class="fas fa-wallet"></i>
                        </span>
                    </div>
                    <p class="text-4xl font-bold text-white mb-4">$<?php echo number_format($wallet['balance'], 2); ?></p>
                    
                    <div class="border-t border-gray-700 pt-4">
                        <a href="client_dashboard.php" class="text-red-500 hover:text-red-400 flex items-center">
                            <i class="fas fa-chevron-left mr-2"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Transaction Forms -->
            <div class="md:col-span-2">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Deposit Form -->
                    <div class="bg-gray-800 p-6 rounded-lg shadow-md card border border-gray-700">
                        <h3 class="text-lg font-semibold text-white mb-4">Deposit Funds</h3>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-4">
                                <label for="deposit_amount" class="block text-gray-400 mb-2">Amount to Deposit ($)</label>
                                <input type="number" name="amount" id="deposit_amount" min="1" step="0.01" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-red-500" required>
                            </div>
                            <input type="hidden" name="action" value="deposit">
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md">
                                <i class="fas fa-plus-circle mr-2"></i> Deposit
                            </button>
                        </form>
                    </div>
                    
                    <!-- Withdrawal Form -->
                    <div class="bg-gray-800 p-6 rounded-lg shadow-md card border border-gray-700">
                        <h3 class="text-lg font-semibold text-white mb-4">Withdraw Funds</h3>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-4">
                                <label for="withdraw_amount" class="block text-gray-400 mb-2">Amount to Withdraw ($)</label>
                                <input type="number" name="amount" id="withdraw_amount" min="1" max="<?php echo $wallet['balance']; ?>" step="0.01" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-red-500" required>
                            </div>
                            <input type="hidden" name="action" value="withdraw">
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md">
                                <i class="fas fa-minus-circle mr-2"></i> Withdraw
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transaction History -->
        <div class="mt-8">
            <div class="bg-gray-800 p-6 rounded-lg shadow-md border border-gray-700">
                <h2 class="text-xl font-semibold text-white mb-6">Transaction History</h2>
                
                <?php if($transactions_result && $transactions_result->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-transparent">
                            <thead>
                                <tr class="border-b border-gray-700">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Reference</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($transaction = $transactions_result->fetch_assoc()): ?>
                                    <tr class="border-b border-gray-700 hover:bg-gray-700">
                                        <td class="px-4 py-3 text-sm text-gray-300">
                                            <?php echo date('M j, Y, g:i A', strtotime($transaction['created_at'])); ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                            <?php 
                                                echo $transaction['transaction_type'] === 'deposit' ? 'bg-green-900 text-green-300' : 
                                                    ($transaction['transaction_type'] === 'refund' ? 'bg-indigo-900 text-indigo-300' : 
                                                    'bg-red-900 text-red-300'); 
                                            ?>">
                                                <?php echo ucfirst($transaction['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-300">
                                            <?php echo htmlspecialchars($transaction['reference']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right 
                                        <?php 
                                            echo ($transaction['transaction_type'] === 'deposit' || $transaction['transaction_type'] === 'refund') 
                                                ? 'text-green-400' : 'text-red-400'; 
                                        ?>">
                                            <?php echo ($transaction['transaction_type'] === 'deposit' || $transaction['transaction_type'] === 'refund') ? '+' : '-'; ?>
                                            $<?php echo number_format(abs($transaction['amount']), 2); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 text-5xl mb-4">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-white mb-2">No Transactions</h3>
                        <p class="text-gray-400">You haven't made any transactions yet.</p>
                    </div>
                <?php endif; ?>
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