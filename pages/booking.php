<?php
session_start();
include_once('../database/basedados.h');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Save booking info in session for after login
    if(isset($_GET['schedule_id']) && isset($_GET['travel_date'])) {
        $_SESSION['booking_schedule_id'] = $_GET['schedule_id'];
        $_SESSION['booking_travel_date'] = $_GET['travel_date'];
    }
    
    // Redirect to login
    header("Location: login.php?redirect=booking");
    exit;
}

// Connect to database
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Get schedule ID and travel date
$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 
               (isset($_SESSION['booking_schedule_id']) ? intval($_SESSION['booking_schedule_id']) : 0);
$travel_date = isset($_GET['travel_date']) ? $_GET['travel_date'] : 
               (isset($_SESSION['booking_travel_date']) ? $_SESSION['booking_travel_date'] : '');

// Clear booking session data if it was used
if(isset($_SESSION['booking_schedule_id'])) {
    unset($_SESSION['booking_schedule_id']);
}
if(isset($_SESSION['booking_travel_date'])) {
    unset($_SESSION['booking_travel_date']);
}

// Check if schedule_id and travel_date are provided
if(empty($schedule_id) || empty($travel_date)) {
    header("Location: routes.php");
    exit;
}

// Get schedule details
$schedule_query = "SELECT s.*, r.origin, r.destination, r.base_price, r.distance, r.capacity 
                  FROM schedules s 
                  JOIN routes r ON s.route_id = r.id 
                  WHERE s.id = $schedule_id";
$schedule_result = $conn->query($schedule_query);

if(!$schedule_result || $schedule_result->num_rows == 0) {
    $_SESSION['error_message'] = "Invalid schedule selected.";
    header("Location: routes.php");
    exit;
}

$schedule = $schedule_result->fetch_assoc();

// Check if there are available seats
$booked_seats_query = "SELECT COUNT(*) as booked_seats 
                      FROM tickets 
                      WHERE schedule_id = $schedule_id 
                      AND travel_date = '$travel_date' 
                      AND status != 'cancelled'";
$booked_seats_result = $conn->query($booked_seats_query);

if(!$booked_seats_result) {
    $_SESSION['error_message'] = "Error checking seat availability.";
    header("Location: routes.php");
    exit;
}

$booked_seats = $booked_seats_result->fetch_assoc()['booked_seats'];
$available_seats = isset($schedule['capacity']) ? $schedule['capacity'] - $booked_seats : 50 - $booked_seats;

if($available_seats <= 0) {
    $_SESSION['error_message'] = "Sorry, this bus is fully booked for the selected date.";
    header("Location: routes.php");
    exit;
}

// Get user's wallet balance
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
$balance = $wallet['balance'];

// Get the FelixBus company wallet
$company_wallet_query = "SELECT w.id, w.balance FROM wallets w 
                         JOIN users u ON w.user_id = u.id 
                         WHERE u.username = 'felixbus'";
$company_wallet_result = $conn->query($company_wallet_query);

if(!$company_wallet_result || $company_wallet_result->num_rows == 0) {
    error_log("FelixBus company wallet not found. Please run the database setup script.");
    $company_wallet_id = 0;
} else {
    $company_wallet = $company_wallet_result->fetch_assoc();
    $company_wallet_id = $company_wallet['id'];
}

// Process booking
$success_message = '';
$error_message = '';

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book'])) {
    // Check if user has enough balance
    if($balance < $schedule['base_price']) {
        $error_message = "Insufficient funds in your wallet. Please add funds before booking.";
        
        // Log the error for reference
        error_log("Booking failed: Insufficient funds for user $user_id. Balance: $balance, Price: {$schedule['base_price']}");
    } else {
        // Double-check seat availability before booking
        $booked_seats_query = "SELECT COUNT(*) as booked_seats 
                             FROM tickets 
                             WHERE schedule_id = $schedule_id 
                             AND travel_date = '$travel_date' 
                             AND status != 'cancelled'";
        $booked_seats_result = $conn->query($booked_seats_query);
        
        if(!$booked_seats_result) {
            $error_message = "Database error when checking seat availability: " . $conn->error;
            error_log("Booking DB Error: " . $conn->error);
        } else {
            $booked_seats = $booked_seats_result->fetch_assoc()['booked_seats'];
            $available_seats = isset($schedule['capacity']) ? $schedule['capacity'] - $booked_seats : 50 - $booked_seats;

            if($available_seats <= 0) {
                $error_message = "Sorry, this bus is now fully booked. Please select another bus or date.";
                error_log("Booking failed: No available seats for schedule $schedule_id on $travel_date");
            } else {
                // Generate a unique ticket number
                $ticket_number = 'TIX' . time() . rand(1000, 9999);
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Debug logging
                    error_log("Starting ticket booking transaction for user $user_id, schedule $schedule_id, date $travel_date");
                    
                    // Create ticket
                    $create_ticket = "INSERT INTO tickets (user_id, schedule_id, travel_date, ticket_number, price, status, purchased_at, purchased_by) 
                                    VALUES ($user_id, $schedule_id, '$travel_date', '$ticket_number', {$schedule['base_price']}, 'active', NOW(), $user_id)";
                    
                    if($conn->query($create_ticket) === TRUE) {
                        $ticket_id = $conn->insert_id;
                        error_log("Ticket created with ID: $ticket_id");
                        
                        // Deduct from user wallet
                        $update_wallet = "UPDATE wallets SET balance = balance - {$schedule['base_price']} WHERE id = $wallet_id";
                        
                        if($conn->query($update_wallet) === TRUE) {
                            error_log("User wallet updated, deducted {$schedule['base_price']}");
                            
                            // Add to FelixBus company wallet
                            if($company_wallet_id > 0) {
                                $update_company_wallet = "UPDATE wallets SET balance = balance + {$schedule['base_price']} WHERE id = $company_wallet_id";
                                if(!$conn->query($update_company_wallet)) {
                                    throw new Exception("Error updating company wallet: " . $conn->error);
                                }
                                error_log("Company wallet updated, added {$schedule['base_price']}");
                            }
                            
                            // Log transaction for user
                            $transaction_type = 'purchase';
                            $reference = "Ticket #$ticket_number";
                            
                            $log_transaction = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference) 
                                            VALUES ($wallet_id, {$schedule['base_price']}, '$transaction_type', '$reference')";
                            
                            if($conn->query($log_transaction) === TRUE) {
                                // Log transaction for company
                                if($company_wallet_id > 0) {
                                    $company_transaction = "INSERT INTO wallet_transactions (wallet_id, amount, transaction_type, reference) 
                                                          VALUES ($company_wallet_id, {$schedule['base_price']}, 'deposit', 'Payment for $reference')";
                                    if(!$conn->query($company_transaction)) {
                                        throw new Exception("Error logging company transaction: " . $conn->error);
                                    }
                                }
                                
                                // Commit transaction
                                $conn->commit();
                                error_log("Transaction complete, ticket booked successfully");
                                $success_message = "Ticket booked successfully! Your ticket number is $ticket_number.";
                                
                                // Reload wallet balance
                                $wallet_query = "SELECT balance FROM wallets WHERE id = $wallet_id";
                                $wallet_result = $conn->query($wallet_query);
                                if($wallet_result && $wallet_result->num_rows > 0) {
                                    $balance = $wallet_result->fetch_assoc()['balance'];
                                }
                            } else {
                                throw new Exception("Error logging transaction: " . $conn->error);
                            }
                        } else {
                            throw new Exception("Error updating wallet: " . $conn->error);
                        }
                    } else {
                        throw new Exception("Error creating ticket: " . $conn->error);
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $error_message = $e->getMessage();
                    error_log("Booking transaction error: " . $e->getMessage());
                }
            }
        }
    }
}

// Calculate duration
$departure = new DateTime($schedule['departure_time']);
$arrival = new DateTime($schedule['arrival_time']);
$duration = $departure->diff($arrival);
$duration_text = ($duration->h > 0 ? $duration->h . 'h ' : '') . $duration->i . 'm';

// Format travel date
$formatted_travel_date = date('l, F j, Y', strtotime($travel_date));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Ticket - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-2xl font-bold">FelixBus</a>
                <div class="hidden md:flex space-x-4">
                    <a href="routes.php" class="hover:text-blue-200 font-medium">Routes</a>
                    <a href="timetables.php" class="hover:text-blue-200">Timetables</a>
                    <a href="prices.php" class="hover:text-blue-200">Prices</a>
                    <a href="contact.php" class="hover:text-blue-200">Contact</a>
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
                        <?php if($_SESSION['user_type'] === 'client'): ?>
                            <a href="client/dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Dashboard</a>
                            <a href="client/tickets.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">My Tickets</a>
                            <a href="client/wallet.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Wallet</a>
                        <?php elseif($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin'): ?>
                            <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Admin Panel</a>
                        <?php endif; ?>
                        <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Profile</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-500 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-blue-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Book Your Ticket</h1>
            <p class="text-lg">Confirm your travel details and complete your booking.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Actions Bar -->
        <div class="mb-6">
            <a href="routes.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Routes
            </a>
        </div>
        
        <?php if($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $success_message; ?></p>
                <div class="mt-4">
                    <a href="client/tickets.php" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:border-green-800 focus:ring focus:ring-green-200 transition">
                        View My Tickets
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error_message; ?></p>
                <?php if(strpos($error_message, "Insufficient funds") !== false): ?>
                    <div class="mt-4">
                        <a href="client/wallet.php" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring focus:ring-blue-200 transition">
                            Add Funds to Wallet
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Booking Details -->
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Trip Details -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Trip Details</h2>
                    
                    <div class="border-b pb-4 mb-4">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($schedule['origin']); ?> to <?php echo htmlspecialchars($schedule['destination']); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo $formatted_travel_date; ?></p>
                            </div>
                            <div class="text-right">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                                    <?php echo $available_seats; ?> seats available
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-sm text-gray-500">Departure</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo date('g:i A', strtotime($schedule['departure_time'])); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($schedule['origin']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Arrival</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo date('g:i A', strtotime($schedule['arrival_time'])); ?></p>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($schedule['destination']); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center border-t pt-4">
                        <div>
                            <p class="text-sm text-gray-500">Duration</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo $duration_text; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Distance</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo $schedule['distance'] ? $schedule['distance'] . ' km' : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Booking Terms & Conditions</h2>
                    
                    <div class="text-sm text-gray-600 space-y-2">
                        <p>1. Tickets are valid only for the specific route, date, and time.</p>
                        <p>2. Please arrive at least 30 minutes before departure time.</p>
                        <p>3. Each passenger is allowed one piece of luggage and one carry-on item.</p>
                        <p>4. Tickets cannot be transferred to another person.</p>
                        <p>5. For cancellations made at least 24 hours before departure, a refund may be issued.</p>
                    </div>
                </div>
            </div>
            
            <!-- Payment & Confirmation -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Payment Summary</h2>
                    
                    <div class="border-b pb-4 mb-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Ticket Price</span>
                            <span class="text-gray-800 font-semibold">$<?php echo number_format($schedule['base_price'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Service Fee</span>
                            <span class="text-gray-800 font-semibold">$0.00</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mb-6">
                        <span class="text-gray-800 font-semibold">Total</span>
                        <span class="text-xl text-blue-600 font-bold">$<?php echo number_format($schedule['base_price'], 2); ?></span>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Your Wallet Balance</span>
                            <span class="text-gray-800 font-semibold">$<?php echo number_format($balance, 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">After Transaction</span>
                            <span class="text-gray-800 font-semibold">$<?php echo number_format($balance - $schedule['base_price'], 2); ?></span>
                        </div>
                    </div>
                    
                    <?php if(!$success_message): ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?schedule_id=$schedule_id&travel_date=$travel_date"); ?>">
                        <div class="mb-4">
                            <div class="flex items-center mb-4">
                                <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-blue-600" required>
                                <label for="terms" class="ml-2 block text-gray-700 text-sm">I agree to the Terms & Conditions</label>
                            </div>
                        </div>
                        <button type="submit" name="book" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full
                                  <?php echo ($balance < $schedule['base_price']) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                  <?php echo ($balance < $schedule['base_price']) ? 'disabled' : ''; ?>>
                            <?php echo ($balance < $schedule['base_price']) ? 'Insufficient Funds' : 'Complete Booking'; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if($balance < $schedule['base_price']): ?>
                    <div class="mt-4 text-center">
                        <a href="client/wallet.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Add funds to your wallet <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
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