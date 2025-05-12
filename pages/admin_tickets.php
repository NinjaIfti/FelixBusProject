<?php
session_start();
include_once('../database/basedados.h');

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

// Cancel ticket
if(isset($_POST['cancel_ticket'])) {
    $ticket_id = intval($_POST['ticket_id']);
    
    // Get ticket details
    $ticket_query = "SELECT t.*, w.id as wallet_id FROM tickets t 
                    JOIN users u ON t.user_id = u.id 
                    JOIN wallets w ON u.id = w.user_id 
                    WHERE t.id = $ticket_id";
    $ticket_result = $conn->query($ticket_query);
    
    if($ticket_result && $ticket_result->num_rows > 0) {
        $ticket = $ticket_result->fetch_assoc();
        
        // Only active tickets can be cancelled
        if($ticket['status'] !== 'active') {
            $error_message = "Only active tickets can be cancelled.";
        } else {
            // Get company wallet
            $company_wallet_query = "SELECT w.id FROM wallets w 
                                    JOIN users u ON w.user_id = u.id 
                                    WHERE u.username = 'felixbus'";
            $company_wallet_result = $conn->query($company_wallet_query);
            
            if(!$company_wallet_result || $company_wallet_result->num_rows == 0) {
                $error_message = "FelixBus company wallet not found. Cannot process refund.";
            } else {
                $company_wallet = $company_wallet_result->fetch_assoc();
                $company_wallet_id = $company_wallet['id'];
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Update ticket status
                    $update_query = "UPDATE tickets SET status = 'cancelled' WHERE id = $ticket_id";
                    if(!$conn->query($update_query)) {
                        throw new Exception("Failed to update ticket status.");
                    }
                    
                    // Refund from company wallet to user wallet
                    $wallet_id = $ticket['wallet_id'];
                    $refund_amount = $ticket['price'];
                    
                    // Deduct from company wallet
                    $update_company_wallet_query = "UPDATE wallets SET balance = balance - $refund_amount WHERE id = $company_wallet_id";
                    if(!$conn->query($update_company_wallet_query)) {
                        throw new Exception("Failed to update company wallet balance for refund.");
                    }
                    
                    // Add to user wallet
                    $update_wallet_query = "UPDATE wallets SET balance = balance + $refund_amount WHERE id = $wallet_id";
                    if(!$conn->query($update_wallet_query)) {
                        throw new Exception("Failed to update user wallet balance.");
                    }
                    
                    // Log transaction for user
                    $transaction_type = 'refund';
                    $reference = "Refund for cancelled ticket #" . $ticket['ticket_number'];
                    
                    $log_transaction_query = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference, processed_by) 
                                            VALUES ($wallet_id, $refund_amount, '$transaction_type', '$reference', $user_id)";
                    if(!$conn->query($log_transaction_query)) {
                        throw new Exception("Failed to log user transaction.");
                    }
                    
                    // Log transaction for company
                    $company_transaction_query = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference, processed_by) 
                                                VALUES ($company_wallet_id, $refund_amount, 'withdrawal', 'Refund payment for ticket #{$ticket['ticket_number']}', $user_id)";
                    if(!$conn->query($company_transaction_query)) {
                        throw new Exception("Failed to log company transaction.");
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $success_message = "Ticket cancelled successfully and refund processed.";
                } catch (Exception $e) {
                    // Rollback on error
                    $conn->rollback();
                    $error_message = "Error: " . $e->getMessage();
                }
            }
        }
    } else {
        $error_message = "Ticket not found.";
    }
}

// Filters
$date_filter = $conn->real_escape_string($_GET['date'] ?? '');
$status_filter = $conn->real_escape_string($_GET['status'] ?? '');
$username_filter = $conn->real_escape_string($_GET['username'] ?? '');

// Build base query
$tickets_query = "SELECT t.*, u.username, u.email, r.origin, r.destination, s.departure_time, s.arrival_time 
                 FROM tickets t 
                 JOIN users u ON t.user_id = u.id 
                 JOIN schedules s ON t.schedule_id = s.id 
                 JOIN routes r ON s.route_id = r.id
                 WHERE 1=1";

// Add filters if provided
if(!empty($date_filter)) {
    $tickets_query .= " AND t.travel_date = '$date_filter'";
}
if(!empty($status_filter)) {
    $tickets_query .= " AND t.status = '$status_filter'";
}
if(!empty($username_filter)) {
    $tickets_query .= " AND u.username LIKE '%$username_filter%'";
}

// Order by travel date, then purchase date
$tickets_query .= " ORDER BY t.travel_date DESC, t.purchased_at DESC";

// Execute query
$tickets_result = $conn->query($tickets_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management - FelixBus</title>
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
                <a href="admin_dashboard.php" class="text-2xl font-bold mb-8 flex items-center">
                    <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span>
                    <span>Felix<span class="text-red-600">Bus</span></span>
                </a>
            </div>
            <nav class="mt-10">
                <a href="admin_dashboard.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                </a>
                <a href="admin_users.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-users mr-3"></i> Users
                </a>
                <a href="admin_routes.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-route mr-3"></i> Routes
                </a>
                <a href="admin_tickets.php" class="flex items-center py-3 px-6 bg-red-900 text-white nav-link">
                    <i class="fas fa-ticket-alt mr-3"></i> Tickets
                </a>
                <a href="admin_manage_wallet.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-wallet mr-3"></i> Manage Wallets
                </a>
                <?php if($is_admin): ?>
                <a href="admin_company_wallet.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
                    <i class="fas fa-building mr-3"></i> Company Wallet
                </a>
                <a href="admin_alerts.php" class="flex items-center py-3 px-6 hover:bg-gray-800 text-gray-300 hover:text-white nav-link">
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
                        <h1 class="text-2xl font-semibold text-gray-800">Ticket Management</h1>
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
                
                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Tickets</h2>
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Travel Date</label>
                                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="status" name="status" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="used" <?php echo $status_filter === 'used' ? 'selected' : ''; ?>>Used</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                                <input type="text" id="username" name="username" value="<?php echo $username_filter; ?>" placeholder="Enter username" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                                    <i class="fas fa-filter mr-2"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Ticket Actions -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">All Tickets</h2>
                    </div>
                    <div>
                        <a href="client_routes.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Book New Ticket
                        </a>
                    </div>
                </div>
                
                <!-- Tickets Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket #</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Travel Date</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($tickets_result && $tickets_result->num_rows > 0): ?>
                                    <?php while($ticket = $tickets_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                <div class="text-gray-900"><?php echo htmlspecialchars($ticket['username']); ?></div>
                                                <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($ticket['email']); ?></div>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php echo htmlspecialchars($ticket['origin']); ?> to <?php echo htmlspecialchars($ticket['destination']); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php echo date('M j, Y', strtotime($ticket['travel_date'])); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                <?php echo date('g:i A', strtotime($ticket['departure_time'])); ?> - <?php echo date('g:i A', strtotime($ticket['arrival_time'])); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-900">
                                                $<?php echo number_format($ticket['price'], 2); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded 
                                                <?php 
                                                    echo $ticket['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                        ($ticket['status'] === 'used' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800'); 
                                                ?>">
                                                    <?php echo ucfirst($ticket['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-right">
                                                <button type="button" onclick="viewTicket(<?php echo $ticket['id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-info-circle"></i> View
                                                </button>
                                                <?php if($ticket['status'] === 'active'): ?>
                                                    <button type="button" onclick="confirmCancel(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($ticket['ticket_number']); ?>')" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-times-circle"></i> Cancel
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="py-4 px-4 border-b border-gray-200 text-center text-gray-500">
                                            No tickets found matching your filters.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Cancel Ticket Modal -->
    <div id="cancelTicketModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Cancellation</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('cancelTicketModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <p class="text-gray-700">Are you sure you want to cancel ticket <span id="cancelTicketNumber" class="font-semibold"></span>? The customer will be refunded.</p>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="cancelTicketForm">
                    <input type="hidden" id="cancel_ticket_id" name="ticket_id">
                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" onclick="closeModal('cancelTicketModal')">
                            Keep Ticket
                        </button>
                        <button type="submit" name="cancel_ticket" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Cancel Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    

    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.bg-black').classList.toggle('hidden');
        });
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        function viewTicket(ticketId) {
            // In a real implementation, this would show detailed ticket info
            alert('View ticket details for ID: ' + ticketId);
        }
        
        function confirmCancel(ticketId, ticketNumber) {
            document.getElementById('cancel_ticket_id').value = ticketId;
            document.getElementById('cancelTicketNumber').textContent = ticketNumber;
            openModal('cancelTicketModal');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?> 