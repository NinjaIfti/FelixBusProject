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

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    
    if($action === 'deposit' && $amount > 0) {
        // Add funds to wallet
        $update_wallet = "UPDATE wallets SET balance = balance + $amount WHERE id = $wallet_id";
        
        if($conn->query($update_wallet) === TRUE) {
            // Log transaction
            $transaction_type = 'deposit';
            $reference = "Manual deposit";
            
            $log_transaction = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference) 
                               VALUES ($wallet_id, $amount, '$transaction_type', '$reference')";
            
            if($conn->query($log_transaction) === TRUE) {
                $success_message = "Successfully added $" . number_format($amount, 2) . " to your wallet.";
            } else {
                $error_message = "Error logging transaction: " . $conn->error;
            }
        } else {
            $error_message = "Error updating wallet: " . $conn->error;
        }
    } elseif($action === 'withdraw' && $amount > 0) {
        // Check if sufficient funds
        if($amount <= $current_balance) {
            // Withdraw funds from wallet
            $update_wallet = "UPDATE wallets SET balance = balance - $amount WHERE id = $wallet_id";
            
            if($conn->query($update_wallet) === TRUE) {
                // Log transaction
                $transaction_type = 'withdrawal';
                $reference = "Manual withdrawal";
                
                $log_transaction = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference) 
                                   VALUES ($wallet_id, $amount, '$transaction_type', '$reference')";
                
                if($conn->query($log_transaction) === TRUE) {
                    $success_message = "Successfully withdrawn $" . number_format($amount, 2) . " from your wallet.";
                } else {
                    $error_message = "Error logging transaction: " . $conn->error;
                }
            } else {
                $error_message = "Error updating wallet: " . $conn->error;
            }
        } else {
            $error_message = "Insufficient funds for withdrawal.";
        }
    } else {
        $error_message = "Invalid amount or action.";
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
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="../index.php" class="text-2xl font-bold">FelixBus</a>
                <div class="hidden md:flex space-x-4">
                    <a href="../routes.php" class="hover:text-blue-200">Routes</a>
                    <a href="../timetables.php" class="hover:text-blue-200">Timetables</a>
                    <a href="../prices.php" class="hover:text-blue-200">Prices</a>
                    <a href="../contact.php" class="hover:text-blue-200">Contact</a>
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
                         class="absolute right-0 w-48 py-2 mt-2 bg-white rounded-md shadow-xl z-20">
                        <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Dashboard</a>
                        <a href="tickets.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">My Tickets</a>
                        <a href="wallet.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Wallet</a>
                        <a href="../profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Profile</a>
                        <a href="../logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-blue-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Wallet Management</h1>
            <p class="text-lg">Add funds, withdraw, and view your transaction history.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <?php if($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Wallet Balance Card -->
            <div class="md:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Wallet Balance</h2>
                        <span class="text-blue-600 text-2xl">
                            <i class="fas fa-wallet"></i>
                        </span>
                    </div>
                    <p class="text-4xl font-bold text-gray-800 mb-4">$<?php echo number_format($wallet['balance'], 2); ?></p>
                    
                    <div class="border-t pt-4">
                        <p class="text-gray-600 mb-4">Use the forms below to add funds or withdraw from your wallet.</p>
                        <a href="dashboard.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
                
                <!-- Deposit Form -->
                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Add Funds</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="action" value="deposit">
                        <div class="mb-4">
                            <label for="deposit_amount" class="block text-gray-700 text-sm font-bold mb-2">Amount ($)</label>
                            <input type="number" step="0.01" min="0.01" id="deposit_amount" name="amount" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full">
                            Add Funds
                        </button>
                    </form>
                </div>
                
                <!-- Withdraw Form -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Withdraw Funds</h2>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="action" value="withdraw">
                        <div class="mb-4">
                            <label for="withdraw_amount" class="block text-gray-700 text-sm font-bold mb-2">Amount ($)</label>
                            <input type="number" step="0.01" min="0.01" max="<?php echo $wallet['balance']; ?>" id="withdraw_amount" name="amount" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full">
                            Withdraw Funds
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Transaction History -->
            <div class="md:col-span-2">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Transaction History</h2>
                    
                    <?php if($transactions_result && $transactions_result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr>
                                        <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($transaction = $transactions_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                <?php echo date('M j, Y, g:i A', strtotime($transaction['created_at'])); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded 
                                                <?php
                                                    echo $transaction['transaction_type'] === 'deposit' ? 'bg-green-100 text-green-800' : 
                                                        ($transaction['transaction_type'] === 'withdrawal' ? 'bg-red-100 text-red-800' : 
                                                        ($transaction['transaction_type'] === 'purchase' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'));
                                                ?>">
                                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                <?php echo $transaction['reference'] ? htmlspecialchars($transaction['reference']) : '-'; ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-right text-sm font-medium 
                                            <?php
                                                echo ($transaction['transaction_type'] === 'deposit' || $transaction['transaction_type'] === 'refund') ? 'text-green-600' : 'text-red-600';
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
                        <p class="text-gray-600">No transaction history found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-8 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?> 