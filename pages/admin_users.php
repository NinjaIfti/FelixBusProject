<?php
session_start();
include_once('../basedados/basedados.h');

// Include security check
include_once('admin_security_check.php');

// Check if user is logged in and is admin or staff
if(!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'staff')) {
    header("Location: login.php");
    exit;
}

// Connect to database
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['user_type'] === 'admin');

// Get current user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = $conn->query($user_query);
$current_user = $user_result->fetch_assoc();

// Process actions if admin
$success_message = '';
$error_message = '';

if($is_admin && $_SERVER["REQUEST_METHOD"] == "POST") {
    // Create new user (admin only)
    if(isset($_POST['create_user'])) {
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $user_type = $conn->real_escape_string($_POST['user_type']);
        
        // Check if username or email already exists
        $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = $conn->query($check_query);
        
        if($check_result->num_rows > 0) {
            $error_message = "Username or email already exists.";
        } else {
            $insert_query = "INSERT INTO users (username, password, email, first_name, last_name, user_type) 
                            VALUES ('$username', '$password', '$email', '$first_name', '$last_name', '$user_type')";
            
            if($conn->query($insert_query) === TRUE) {
                $new_user_id = $conn->insert_id;
                
                // Create wallet for the user if it's a client
                if($user_type === 'client') {
                    $wallet_query = "INSERT INTO wallets (user_id, balance) VALUES ($new_user_id, 0.00)";
                    $conn->query($wallet_query);
                }
                
                $success_message = "User created successfully.";
            } else {
                $error_message = "Error creating user: " . $conn->error;
            }
        }
    }
    
    // Update user
    if(isset($_POST['update_user'])) {
        $update_id = intval($_POST['user_id']);
        $email = $conn->real_escape_string($_POST['email']);
        $first_name = $conn->real_escape_string($_POST['first_name']);
        $last_name = $conn->real_escape_string($_POST['last_name']);
        $user_type = '';
        
        if($is_admin) {
            $user_type = $conn->real_escape_string($_POST['user_type']);
            $update_query = "UPDATE users SET email = '$email', first_name = '$first_name', last_name = '$last_name', user_type = '$user_type' WHERE id = $update_id";
        } else {
            $update_query = "UPDATE users SET email = '$email', first_name = '$first_name', last_name = '$last_name' WHERE id = $update_id";
        }
        
        if($conn->query($update_query) === TRUE) {
            $success_message = "User updated successfully.";
        } else {
            $error_message = "Error updating user: " . $conn->error;
        }
    }
    
    // Block/Unblock user (admin only)
    if(isset($_POST['toggle_user_status'])) {
        $user_id_to_toggle = intval($_POST['user_id']);
        $new_status = $_POST['new_status'];
        
        // Only allow toggling clients for safety
        $check_query = "SELECT user_type FROM users WHERE id = $user_id_to_toggle";
        $check_result = $conn->query($check_query);
        $user_to_toggle = $check_result->fetch_assoc();
        
        if($user_to_toggle['user_type'] === 'admin' && $user_id_to_toggle !== $user_id) {
            $error_message = "Cannot block other admin users.";
        } else if($user_id_to_toggle === $user_id) {
            $error_message = "Cannot block your own account.";
        } else {
            $toggle_query = "UPDATE users SET status = '$new_status' WHERE id = $user_id_to_toggle";
            
            if($conn->query($toggle_query) === TRUE) {
                $status_message = $new_status === 'active' ? 'unblocked' : 'blocked';
                $success_message = "User $status_message successfully.";
            } else {
                $error_message = "Error updating user status: " . $conn->error;
            }
        }
    }
}

// Get all users, ordered by type and then username
$all_users_query = "SELECT * FROM users ORDER BY 
                   CASE 
                       WHEN user_type = 'admin' THEN 1
                       WHEN user_type = 'staff' THEN 2
                       ELSE 3
                   END, 
                   username ASC";
$all_users_result = $conn->query($all_users_query);

// Get wallet balances for all users
$wallets_query = "SELECT user_id, balance FROM wallets";
$wallets_result = $conn->query($wallets_query);
$wallets = [];
if($wallets_result) {
    while($wallet = $wallets_result->fetch_assoc()) {
        $wallets[$wallet['user_id']] = $wallet['balance'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - FelixBus</title>
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
                <a href="admin_users.php" class="flex items-center py-3 px-6 bg-red-900 text-white nav-link">
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
            <header class="bg-white shadow-sm">
                <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <button id="sidebar-toggle" class="mr-4 text-gray-600 md:hidden">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h1 class="text-2xl font-semibold text-gray-800">User Management</h1>
                    </div>
                    <div class="flex items-center">
                        <span class="mr-2 text-sm"><?php echo htmlspecialchars($current_user['username']); ?></span>
                        <span class="bg-blue-600 text-white text-xs font-semibold px-2.5 py-0.5 rounded-full">
                            <?php echo ucfirst($current_user['user_type']); ?>
                        </span>
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
                
                <!-- Actions Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">All Users</h2>
                    </div>
                    <div>
                        <?php if($is_admin): ?>
                        <button type="button" onclick="openModal('createUserModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg inline-flex items-center">
                            <i class="fas fa-plus mr-2"></i> Add New User
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wallet Balance</th>
                                    <th class="py-3 px-4 border-b border-gray-200 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($all_users_result && $all_users_result->num_rows > 0): ?>
                                    <?php while($user = $all_users_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="py-4 px-4 border-b border-gray-200">
                                                <div class="flex items-center">
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                <?php 
                                                    echo $user['user_type'] === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                                        ($user['user_type'] === 'staff' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'); 
                                                ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-gray-500">
                                                <?php 
                                                    if($user['user_type'] === 'client') {
                                                        echo isset($wallets[$user['id']]) ? '$' . number_format($wallets[$user['id']], 2) : '$0.00';
                                                    } else {
                                                        echo '-';
                                                    }
                                                ?>
                                            </td>
                                            <td class="py-4 px-4 border-b border-gray-200 text-sm text-right">
                                                <button 
                                                    type="button" 
                                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900 mr-3"
                                                >
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <?php if($is_admin && $user['id'] !== $user_id): ?>
                                                <button 
                                                    type="button" 
                                                    onclick="confirmToggleStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo $user['status']; ?>')" 
                                                    class="<?php echo $user['status'] === 'active' ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'; ?>"
                                                >
                                                    <?php if($user['status'] === 'active'): ?>
                                                        <i class="fas fa-ban"></i> Block
                                                    <?php else: ?>
                                                        <i class="fas fa-check-circle"></i> Unblock
                                                    <?php endif; ?>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="py-4 px-4 border-b border-gray-200 text-center text-gray-500">
                                            No users found.
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

    <!-- Create User Modal -->
    <div id="createUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('createUserModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mt-2 space-y-4">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="username" id="username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" name="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <div>
                            <label for="user_type" class="block text-sm font-medium text-gray-700">User Type</label>
                            <select name="user_type" id="user_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                                <option value="client">Client</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="closeModal('createUserModal')">
                            Cancel
                        </button>
                        <button type="submit" name="create_user" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Edit User</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editUserModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="editUserForm">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="mt-2 space-y-4">
                        <div>
                            <label for="edit_username" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" id="edit_username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border bg-gray-100" disabled>
                        </div>
                        <div>
                            <label for="edit_email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="edit_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <div>
                            <label for="edit_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" name="first_name" id="edit_first_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <div>
                            <label for="edit_last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" name="last_name" id="edit_last_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                        </div>
                        <?php if($is_admin): ?>
                        <div>
                            <label for="edit_user_type" class="block text-sm font-medium text-gray-700">User Type</label>
                            <select name="user_type" id="edit_user_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                                <option value="client">Client</option>
                                <option value="staff">Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="closeModal('editUserModal')">
                            Cancel
                        </button>
                        <button type="submit" name="update_user" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toggle User Status Modal -->
    <div id="toggleStatusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Status Change</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('toggleStatusModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <p class="text-gray-700" id="toggleStatusMessage"></p>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="toggleStatusForm">
                    <input type="hidden" name="user_id" id="toggle_user_id">
                    <input type="hidden" name="new_status" id="new_status">
                    <div class="mt-4 flex justify-end">
                        <button type="button" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="closeModal('toggleStatusModal')">
                            Cancel
                        </button>
                        <button type="submit" name="toggle_user_status" id="confirmToggleBtn" class="px-4 py-2 text-sm font-medium text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Confirm
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
        
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            
            if(document.getElementById('edit_user_type')) {
                document.getElementById('edit_user_type').value = user.user_type;
            }
            
            openModal('editUserModal');
        }
        
        function confirmToggleStatus(userId, username, currentStatus) {
            document.getElementById('toggle_user_id').value = userId;
            
            const newStatus = currentStatus === 'active' ? 'blocked' : 'active';
            document.getElementById('new_status').value = newStatus;
            
            const action = currentStatus === 'active' ? 'block' : 'unblock';
            document.getElementById('toggleStatusMessage').textContent = `Are you sure you want to ${action} the user ${username}?`;
            
            const confirmBtn = document.getElementById('confirmToggleBtn');
            if(newStatus === 'blocked') {
                confirmBtn.classList.add('bg-red-600', 'hover:bg-red-700');
                confirmBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                confirmBtn.innerHTML = '<i class="fas fa-ban mr-2"></i> Block';
            } else {
                confirmBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                confirmBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
                confirmBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Unblock';
            }
            
            openModal('toggleStatusModal');
        }
    </script>
    
    <!-- Session security checker -->
    <script src="admin_session_check.js"></script>
</body>
</html>
<?php $conn->close(); ?> 