<?php
session_start();
include_once('../basedados/basedados.h');

// Check if user is logged in and is admin or staff
if(!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'staff')) {
    header("Location: login.php");
    exit;
}

// Connect to database
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_type'] === 'admin');

// Process actions
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

// Get client information if client_id is set
$client = null;
$client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;

if($client_id > 0) {
    $client_query = "SELECT u.*, w.id as wallet_id, w.balance 
                     FROM users u 
                     LEFT JOIN wallets w ON u.id = w.user_id 
                     WHERE u.id = $client_id AND u.user_type = 'client'";
    $client_result = $conn->query($client_query);
    
    if($client_result && $client_result->num_rows > 0) {
        $client = $client_result->fetch_assoc();
    } else {
        $error_message = "Client not found or not a valid client account.";
    }
}

// Add funds to wallet
if(isset($_POST['add_funds']) && $client) {
    $amount = floatval($_POST['amount']);
    $wallet_id = intval($client['wallet_id']);
    $reference = $conn->real_escape_string($_POST['reference']);
    
    if($amount <= 0) {
        $_SESSION['error_message'] = "Amount must be greater than zero.";
        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"] . "?client_id=" . $client_id));
        exit;
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update wallet balance
            $update_query = "UPDATE wallets SET balance = balance + $amount WHERE id = $wallet_id";
            if(!$conn->query($update_query)) {
                throw new Exception("Failed to update wallet balance.");
            }
            
            // Log transaction
            $transaction_type = 'deposito';
            $transaction_query = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference, processed_by) 
                                VALUES ($wallet_id, $amount, '$transaction_type', '$reference', $user_id)";
            if(!$conn->query($transaction_query)) {
                throw new Exception("Failed to log transaction.");
            }
            
            // Commit transaction
            $conn->commit();
            $_SESSION['success_message'] = "Successfully added $" . number_format($amount, 2) . " to client's wallet.";
            
            // Redirect to prevent form resubmission
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"] . "?client_id=" . $client_id));
            exit;
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            
            // Redirect to prevent form resubmission
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"] . "?client_id=" . $client_id));
            exit;
        }
    }
}

// Withdraw funds from wallet
if(isset($_POST['withdraw_funds']) && $client) {
    $amount = floatval($_POST['amount']);
    $wallet_id = intval($client['wallet_id']);
    $reference = $conn->real_escape_string($_POST['reference']);
    
    if($amount <= 0) {
        $_SESSION['error_message'] = "Amount must be greater than zero.";
        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"] . "?client_id=" . $client_id));
        exit;
    } elseif($amount > $client['balance']) {
        $_SESSION['error_message'] = "Insufficient funds in client's wallet.";
        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"] . "?client_id=" . $client_id));
        exit;
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update wallet balance
            $update_query = "UPDATE wallets SET balance = balance - $amount WHERE id = $wallet_id";
            if(!$conn->query($update_query)) {
                throw new Exception("Failed to update wallet balance.");
            }
            
            // Log transaction
            $transaction_type = 'withdrawal';
            $transaction_query = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference, processed_by) 
                                VALUES ($wallet_id, $amount, '$transaction_type', '$reference', $user_id)";
            if(!$conn->query($transaction_query)) {
                throw new Exception("Failed to log transaction.");
            }
            
            // Commit transaction
            $conn->commit();
            $_SESSION['success_message'] = "Successfully withdrew $" . number_format($amount, 2) . " from client's wallet.";
            
            // Redirect to prevent form resubmission
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"] . "?client_id=" . $client_id));
            exit;
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            
            // Redirect to prevent form resubmission
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"] . "?client_id=" . $client_id));
            exit;
        }
    }
}

// Get list of all clients for the dropdown
$clients_query = "SELECT u.id, u.username, u.first_name, u.last_name, w.balance 
                  FROM users u 
                  LEFT JOIN wallets w ON u.id = w.user_id 
                  WHERE u.user_type = 'client' 
                  ORDER BY u.username";
$clients_result = $conn->query($clients_query);

// Get transaction history for the client if selected
$transactions = [];
if($client) {
    $wallet_id = $client['wallet_id'];
    $transactions_query = "SELECT wt.*, u.username as processed_by_username 
                          FROM wallet_transactions wt
                          LEFT JOIN users u ON wt.processed_by = u.id
                          WHERE wt.wallet_id = $wallet_id
                          ORDER BY wt.created_at DESC
                          LIMIT 20";
    $transactions_result = $conn->query($transactions_query);
    
    if($transactions_result && $transactions_result->num_rows > 0) {
        while($row = $transactions_result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Client Wallet - FelixBus</title>
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
                <a href="admin_painel.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
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
                <a href="admin_gerir_carteira.php" class="flex items-center py-3 px-6 bg-red-900 text-white nav-link">
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
            <header class="bg-white shadow-sm">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="mr-4 text-gray-600 md:hidden">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h1 class="text-2xl font-semibold text-gray-800">Manage Client Wallet</h1>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="container mx-auto px-4 py-8">
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
                
                <!-- Client Selection -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Select Client</h2>
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="clientSelectForm">
                        <div class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
                            <div class="flex-grow">
                                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <select id="client_id" name="client_id" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 text-black" required>
                                    <option value="">-- Select a Client --</option>
                                    <?php if($clients_result && $clients_result->num_rows > 0): ?>
                                        <?php $clients_result->data_seek(0); while($client_row = $clients_result->fetch_assoc()): ?>
                                            <option value="<?php echo $client_row['id']; ?>" <?php echo ($client_id == $client_row['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($client_row['username']); ?> - 
                                                <?php echo htmlspecialchars($client_row['first_name'] . ' ' . $client_row['last_name']); ?> 
                                                (Balance: $<?php echo number_format($client_row['balance'] ?? 0, 2); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if($client): ?>
                    <!-- Client Wallet Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 md:col-span-1">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Client Information</h2>
                            
                            <div class="text-center mb-6">
                                <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full mx-auto flex items-center justify-center text-3xl">
                                    <i class="fas fa-user"></i>
                                </div>
                                <p class="text-xl font-bold mt-2"><?php echo htmlspecialchars($client['username']); ?></p>
                                <p class="text-gray-600"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></p>
                            </div>
                            
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($client['email']); ?></span>
                                </div>
                                <?php if(!empty($client['phone'])): ?>
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($client['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Account Created:</span>
                                    <span class="font-medium"><?php echo date('M j, Y', strtotime($client['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-6 md:col-span-2">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Wallet Management</h2>
                            
                            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm text-blue-600">Current Balance</p>
                                        <p class="text-3xl font-bold text-blue-800">$<?php echo number_format($client['balance'], 2); ?></p>
                                    </div>
                                    <div class="text-4xl text-blue-600">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Add Funds Form -->
                                <div class="border p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-800 mb-3">Add Funds</h3>
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?client_id=' . $client_id); ?>">
                                        <div class="space-y-4">
                                            <div>
                                                <label for="add_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount ($)</label>
                                                <input type="number" id="add_amount" name="amount" min="0.01" step="0.01" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            </div>
                                            <div>
                                                <label for="add_reference" class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                                                <input type="text" id="add_reference" name="reference" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Cash deposit, Credit card" required>
                                            </div>
                                            <button type="submit" name="add_funds" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md">
                                                <i class="fas fa-plus-circle mr-2"></i> Add Funds
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Withdraw Funds Form -->
                                <div class="border p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-800 mb-3">Withdraw Funds</h3>
                                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?client_id=' . $client_id); ?>">
                                        <div class="space-y-4">
                                            <div>
                                                <label for="withdraw_amount" class="block text-sm font-medium text-gray-700 mb-1">Amount ($)</label>
                                                <input type="number" id="withdraw_amount" name="amount" min="0.01" step="0.01" max="<?php echo $client['balance']; ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            </div>
                                            <div>
                                                <label for="withdraw_reference" class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                                                <input type="text" id="withdraw_reference" name="reference" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g., Cash withdrawal, Refund" required>
                                            </div>
                                            <button type="submit" name="withdraw_funds" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md">
                                                <i class="fas fa-minus-circle mr-2"></i> Withdraw Funds
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction History -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Transaction History</h2>
                        
                        <?php if(count($transactions) > 0): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white">
                                    <thead>
                                        <tr>
                                            <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                            <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                            <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($transactions as $transaction): ?>
                                            <tr>
                                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                    <?php echo date('M j, Y, g:i a', strtotime($transaction['created_at'])); ?>
                                                </td>
                                                <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                    <span class="inline-block px-2 py-1 text-xs font-semibold rounded 
                                                    <?php 
                                                        echo $transaction['transaction_type'] === 'deposito' ? 'bg-green-100 text-green-800' : 
                                                            ($transaction['transaction_type'] === 'withdrawal' ? 'bg-red-100 text-red-800' : 
                                                            ($transaction['transaction_type'] === 'compra' ? 'bg-blue-100 text-blue-800' : 
                                                            'bg-yellow-100 text-yellow-800')); 
                                                    ?>">
                                                        <?php echo ucfirst($transaction['transaction_type']); ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                    <span class="<?php echo in_array($transaction['transaction_type'], ['deposito', 'refund']) ? 'text-green-600' : 'text-red-600'; ?>">
                                                        <?php echo in_array($transaction['transaction_type'], ['deposito', 'refund']) ? '+' : '-'; ?>
                                                        $<?php echo number_format($transaction['amount'], 2); ?>
                                                    </span>
                                                </td>
                                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($transaction['reference']); ?>
                                                </td>
                                                <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($transaction['processed_by_username'] ?? 'System'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="text-gray-400 text-5xl mb-4">
                                    <i class="fas fa-exchange-alt"></i>
                                </div>
                                <p class="text-gray-600">No transactions found for this client.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                        <div class="text-gray-400 text-5xl mb-4">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <p class="text-gray-600 text-lg mb-2">No client selected</p>
                        <p class="text-gray-500">Please select a client from the dropdown above to manage their wallet.</p>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.bg-black').classList.toggle('hidden');
        });
        // Auto-submit client selection form on change
        document.getElementById('client_id').addEventListener('change', function() {
            document.getElementById('clientSelectForm').submit();
        });
    </script>
</body>
</html>
<?php $conn->close(); ?> 