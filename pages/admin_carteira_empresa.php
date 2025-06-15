<?php
session_start();
include_once('../basedados/basedados.h');
include_once('controle_de_acesso.php');

// Check if user has access to admin company wallet page
checkPageAccess(['admin']);

// Connect to database
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

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

// Get the FelixBus company wallet
$company_wallet_query = "SELECT w.id, w.balance, u.username, u.first_name, u.last_name 
                         FROM wallets w 
                         JOIN users u ON w.user_id = u.id 
                         WHERE u.username = 'felixbus'";
$company_wallet_result = $conn->query($company_wallet_query);

if(!$company_wallet_result || $company_wallet_result->num_rows == 0) {
    $error_message = "FelixBus company wallet not found. Please run the database setup script.";
    $company_wallet = null;
} else {
    $company_wallet = $company_wallet_result->fetch_assoc();
}

// Handle withdrawal from company wallet (e.g., for expenses, payouts, etc.)
if(isset($_POST['withdraw_funds']) && $company_wallet) {
    $amount = floatval($_POST['amount']);
    $wallet_id = intval($company_wallet['id']);
    $reference = $conn->real_escape_string($_POST['reference']);
    
    if($amount <= 0) {
        $_SESSION['error_message'] = "Amount must be greater than zero.";
        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
        exit;
    } elseif($amount > $company_wallet['balance']) {
        $_SESSION['error_message'] = "Insufficient funds in company wallet.";
        header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
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
            $_SESSION['success_message'] = "Successfully withdrew $" . number_format($amount, 2) . " from company wallet.";
            
            // Redirect to prevent form resubmission
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
            exit;
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
            
            // Redirect to prevent form resubmission
            header("Location: " . htmlspecialchars($_SERVER["PHP_SELF"]));
            exit;
        }
    }
}

// Get transaction history for the company wallet
$transactions = [];
if($company_wallet) {
    $wallet_id = $company_wallet['id'];
    $transactions_query = "SELECT wt.*, u.username as processed_by_username 
                          FROM wallet_transactions wt
                          LEFT JOIN users u ON wt.processed_by = u.id
                          WHERE wt.wallet_id = $wallet_id
                          ORDER BY wt.created_at DESC
                          LIMIT 100";
    $transactions_result = $conn->query($transactions_query);
    
    if($transactions_result && $transactions_result->num_rows > 0) {
        while($row = $transactions_result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
}

// Get daily revenue summary (last 7 days)
$revenue_data = [];
if($company_wallet) {
    $wallet_id = $company_wallet['id'];
    $revenue_query = "SELECT DATE(created_at) as date, 
                            SUM(CASE WHEN transaction_type = 'deposito' THEN amount ELSE 0 END) as income,
                            SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END) as expense,
                            SUM(CASE WHEN transaction_type = 'deposito' THEN amount 
                                 WHEN transaction_type = 'withdrawal' THEN -amount
                                 ELSE 0 END) as net_amount
                     FROM wallet_transactions 
                     WHERE wallet_id = $wallet_id
                     GROUP BY DATE(created_at)
                     ORDER BY date DESC
                     LIMIT 7";
    $revenue_result = $conn->query($revenue_query);
    
    if($revenue_result && $revenue_result->num_rows > 0) {
        while($row = $revenue_result->fetch_assoc()) {
            $revenue_data[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Wallet - FelixBus</title>
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
                <a href="admin_gerir_carteira.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-wallet mr-3"></i> Manage Client Wallets
                </a>
                <?php if($_SESSION['user_type'] === 'admin'): ?>
                <a href="admin_carteira_empresa.php" class="flex items-center py-3 px-6 bg-red-900 text-white nav-link">
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
        
        <div class="flex-1">
            <!-- Top Bar -->
            <div class="bg-white shadow-md p-4 flex items-center justify-between md:hidden">
                <a href="admin_painel.php" class="text-xl font-bold">FelixBus</a>
                <button id="sidebar-toggle" class="p-2 rounded-md hover:bg-gray-100">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Main Content -->
            <main class="container mx-auto px-4 py-8">
                <h1 class="text-2xl font-bold mb-6">Company Wallet Management</h1>
                
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
                
                <?php if($company_wallet): ?>
                    <div class="grid md:grid-cols-3 gap-6 mb-8">
                        <!-- Company Wallet Card -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-800">Company Wallet</h2>
                                <div class="text-4xl text-blue-600">
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <p class="text-sm text-blue-600">Current Balance</p>
                                <p class="text-3xl font-bold text-blue-800">$<?php echo number_format($company_wallet['balance'], 2); ?></p>
                            </div>
                            
                            <div class="text-sm text-gray-600">
                                <p>FelixBus Company Account</p>
                                <p class="mt-1">This wallet collects all payments from ticket purchases</p>
                            </div>
                        </div>
                        
                        <!-- Revenue Summary -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Transaction Summary</h2>
                            
                            <div class="space-y-2">
                                <?php if(count($revenue_data) > 0): ?>
                                    <div class="grid grid-cols-4 gap-2 mb-2 text-xs text-gray-500 font-medium border-b pb-2">
                                        <div>Date</div>
                                        <div class="text-right">Income</div>
                                        <div class="text-right">Expense</div>
                                        <div class="text-right">Net</div>
                                    </div>
                                    <?php foreach($revenue_data as $revenue): ?>
                                        <div class="grid grid-cols-4 gap-2 py-2 border-b border-gray-100">
                                            <span class="text-gray-600"><?php echo date('M j, Y', strtotime($revenue['date'])); ?></span>
                                            <span class="text-green-600 text-right">$<?php echo number_format($revenue['income'], 2); ?></span>
                                            <span class="text-red-600 text-right">$<?php echo number_format($revenue['expense'], 2); ?></span>
                                            <span class="font-semibold text-right <?php echo $revenue['net_amount'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                                $<?php echo number_format(abs($revenue['net_amount']), 2); ?>
                                                <?php echo $revenue['net_amount'] >= 0 ? '+' : '-'; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-4">No transaction data available</p>
                                    <p class="text-sm text-center text-gray-400">Complete a transaction to see data here</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Withdrawal Form -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Withdraw Funds</h2>
                            
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <div class="space-y-4">
                                    <div>
                                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <span class="text-gray-500">$</span>
                                            </div>
                                            <input type="number" id="amount" name="amount" step="0.01" min="0.01" max="<?php echo $company_wallet['balance']; ?>" 
                                                  class="pl-7 block w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">Reference/Description</label>
                                        <input type="text" id="reference" name="reference" placeholder="e.g., Expenses payment, Maintenance costs, etc." 
                                              class="block w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    </div>
                                    
                                    <button type="submit" name="withdraw_funds" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                                        Withdraw Funds
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Transaction History -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                        <div class="px-6 py-4 bg-blue-600 text-white flex justify-between items-center">
                            <h2 class="text-lg font-semibold">Recent Transactions</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    if($company_wallet) {
                                        $wallet_id = $company_wallet['id'];
                                        $transactions_query = "SELECT * FROM wallet_transactions 
                                                         WHERE wallet_id = $wallet_id 
                                                         ORDER BY created_at DESC 
                                                         LIMIT 50";
                                        $transactions_result = $conn->query($transactions_query);
                                        
                                        if($transactions_result && $transactions_result->num_rows > 0) {
                                            while($transaction = $transactions_result->fetch_assoc()) {
                                                $amount = $transaction['amount'];
                                                $type = $transaction['transaction_type'];
                                                $status_class = 'bg-gray-100 text-gray-800';
                                                
                                                if($type == 'deposito') {
                                                    $amount_class = 'text-green-600';
                                                    $status_class = 'bg-green-100 text-green-800';
                                                } else if($type == 'withdrawal') {
                                                    $amount_class = 'text-red-600';
                                                    $status_class = 'bg-red-100 text-red-800';
                                                }
                                                ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <?php echo date('M j, Y, g:i A', strtotime($transaction['created_at'])); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">
                                                        <?php echo $type; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($transaction['reference']); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right <?php echo $amount_class; ?>">
                                                        $<?php echo number_format($amount, 2); ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                            Completed
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                                    <p>No transaction history found.</p>
                                                    <p class="text-sm mt-1">Complete a transaction to see your history here.</p>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                                <p>Company wallet not found.</p>
                                                <p class="text-sm mt-1">Please set up the company wallet first.</p>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg mb-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-medium text-red-600">Company Wallet Not Found</h3>
                                <div class="mt-2 text-red-700">
                                    <p>The FelixBus company wallet has not been set up. This wallet is required for processing payments.</p>
                                    <p class="mt-2 text-sm">You need to run the database setup script to create the company wallet.</p>
                                    <a href="../database/setup_company_wallet.php" class="inline-flex items-center px-4 py-2 mt-4 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:border-red-800 focus:ring focus:ring-red-200 transition">
                                        Setup Company Wallet Now
                                    </a>
                                </div>
                            </div>
                        </div>
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
    </script>
</body>
</html>
<?php $conn->close(); ?> 