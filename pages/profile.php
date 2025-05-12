<?php
session_start();
include_once('../database/basedados.h');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Connect to database
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$user = $user_result->fetch_assoc();

// Process form submission
$success_message = '';
$error_message = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update profile information
    if(isset($_POST['update_profile'])) {
        $email = $conn->real_escape_string($_POST['email']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        $address = $conn->real_escape_string($_POST['address'] ?? '');
        
        // Check if email exists for another user
        $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
        $email_result = $conn->query($check_email);
        
        if($email_result->num_rows > 0) {
            $error_message = "Email already in use by another account.";
        } else {
            $update_query = "UPDATE users SET email = '$email', first_name = '$first_name', last_name = '$last_name', 
                            phone = '$phone', address = '$address' WHERE id = $user_id";
            
            if($conn->query($update_query) === TRUE) {
                $success_message = "Profile updated successfully.";
                
                // Refresh user data
                $user_result = $conn->query($user_query);
                $user = $user_result->fetch_assoc();
            } else {
                $error_message = "Error updating profile: " . $conn->error;
            }
        }
    }
    
    // Change password
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if(!password_verify($current_password, $user['password'])) {
            $error_message = "Current password is incorrect.";
        } elseif($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif(strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if($conn->query($update_query) === TRUE) {
                $success_message = "Password changed successfully.";
            } else {
                $error_message = "Error changing password: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FelixBus</title>
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
                <div class="relative group">
                    <button class="flex items-center space-x-1">
                        <span>My Account</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 w-48 py-2 mt-2 bg-gray-800 rounded-md shadow-xl z-20 hidden group-hover:block">
                        <?php if($_SESSION['user_type'] === 'client'): ?>
                            <a href="client_dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                            <a href="client_tickets.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                            <a href="client_wallet.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                        <?php elseif($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin'): ?>
                            <a href="admin_dashboard.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Admin Panel</a>
                        <?php endif; ?>
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
            <h1 class="text-3xl font-bold mb-2">My Profile</h1>
            <p class="text-lg">View and edit your personal information.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 flex-1">
        <!-- Navigation Links -->
        <div class="mb-6">
            <?php if($_SESSION['user_type'] === 'client'): ?>
                <a href="client_dashboard.php" class="text-red-500 hover:text-red-400">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            <?php elseif($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="text-red-500 hover:text-red-400">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            <?php endif; ?>
        </div>
        
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
            <!-- Account Overview -->
            <div class="md:col-span-1">
                <div class="bg-gray-800 p-6 rounded-lg shadow-md mb-6 card border border-gray-700">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-white">Account Overview</h2>
                    </div>
                    <div class="text-center mb-4">
                        <div class="w-24 h-24 bg-red-100 text-red-600 rounded-full mx-auto flex items-center justify-center text-4xl">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                    <p class="text-xl font-bold text-center mb-1 text-white"><?php echo htmlspecialchars($user['username']); ?></p>
                    <p class="text-sm text-gray-400 text-center mb-4"><?php echo ucfirst($user['user_type']); ?></p>
                    <div class="border-t border-gray-700 pt-4">
                        <p class="text-sm text-gray-400">Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                        <?php if($_SESSION['user_type'] === 'client'): ?>
                            <div class="mt-4">
                                <a href="client_wallet.php" class="text-red-500 hover:text-red-400 text-sm font-medium">
                                    <i class="fas fa-wallet mr-1"></i> Manage Wallet
                                </a>
                            </div>
                            <div class="mt-2">
                                <a href="client_tickets.php" class="text-red-500 hover:text-red-400 text-sm font-medium">
                                    <i class="fas fa-ticket-alt mr-1"></i> View My Tickets
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Profile Information -->
            <div class="md:col-span-2">
                <div class="bg-gray-800 p-6 rounded-lg shadow-md mb-6 card border border-gray-700">
                    <h2 class="text-xl font-semibold text-white mb-6">Personal Information</h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-300 mb-2">Username</label>
                                <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="w-full px-4 py-2 border rounded-md bg-gray-700 text-gray-400" disabled>
                                <p class="text-xs text-gray-500 mt-1">Username cannot be changed</p>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-300 mb-2">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white" required>
                            </div>
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-300 mb-2">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white" required>
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-300 mb-2">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white" required>
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-300 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white">
                            </div>
                            <div>
                                <label for="user_type" class="block text-sm font-medium text-gray-300 mb-2">Account Type</label>
                                <input type="text" id="user_type" value="<?php echo ucfirst($user['user_type']); ?>" class="w-full px-4 py-2 border rounded-md bg-gray-700 text-gray-400" disabled>
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-300 mb-2">Address</label>
                                <textarea id="address" name="address" rows="3" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="mt-6 text-right">
                            <button type="submit" name="update_profile" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Change Password -->
                <div class="bg-gray-800 p-6 rounded-lg shadow-md card border border-gray-700">
                    <h2 class="text-xl font-semibold text-white mb-6">Change Password</h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-300 mb-2">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white" required>
                            </div>
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-300 mb-2">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white" required>
                                <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters</p>
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-2">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-red-600 bg-gray-900 text-white" required>
                            </div>
                        </div>
                        <div class="mt-6 text-right">
                            <button type="submit" name="change_password" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-md">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
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