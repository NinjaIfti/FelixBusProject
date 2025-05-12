<?php
session_start();
include_once('../database/basedados.h');

// Check if user is authenticated and is an admin or staff
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'staff')) {
    header("Location: login.php");
    exit();
}

$conn = connectDatabase();

// Process alert actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new alert
    if (isset($_POST['add_alert'])) {
        $title = $conn->real_escape_string($_POST['title']);
        $content = $conn->real_escape_string($_POST['message']);
        $type = $conn->real_escape_string($_POST['type']);
        
        // Get the current admin ID
        $created_by = $_SESSION['user_id'];
        
        $insert_query = "INSERT INTO alerts (title, content, type, created_by) 
                         VALUES ('$title', '$content', '$type', $created_by)";
                         
        if ($conn->query($insert_query) === TRUE) {
            $success_message = "Alert added successfully!";
        } else {
            $error_message = "Error adding alert: " . $conn->error;
        }
    }
    
    // Toggle alert status is not supported with current DB structure
    if (isset($_POST['toggle_alert'])) {
        $alert_id = intval($_POST['alert_id']);
        $success_message = "Alert status cannot be changed with current database structure.";
    }
    
    // Delete an alert
    if (isset($_POST['delete_alert'])) {
        $alert_id = intval($_POST['alert_id']);
        
        $delete_query = "DELETE FROM alerts WHERE id = $alert_id";
        
        if ($conn->query($delete_query) === TRUE) {
            $success_message = "Alert deleted successfully!";
        } else {
            $error_message = "Error deleting alert: " . $conn->error;
        }
    }
}

// Get all alerts
$alerts_query = "SELECT a.*, u.username FROM alerts a 
                JOIN users u ON a.created_by = u.id 
                ORDER BY a.created_at DESC";
$alerts_result = $conn->query($alerts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Alerts - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-gray-100">
    <!-- Navigation -->
    <nav class="bg-black p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="admin_dashboard.php" class="text-2xl font-bold flex items-center">
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
            <h1 class="text-3xl font-bold">Manage Client Alerts</h1>
            <a href="admin_dashboard.php" class="bg-gray-700 hover:bg-gray-600 text-gray-200 py-2 px-4 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if(isset($success_message)): ?>
            <div class="bg-green-800 text-green-200 p-4 rounded-lg mb-6">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="bg-red-800 text-red-200 p-4 rounded-lg mb-6">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Alert Form -->
        <div class="bg-gray-800 rounded-lg shadow-lg p-6 mb-8 border border-gray-700">
            <h3 class="text-xl font-bold mb-4">Add New Alert</h3>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-gray-400 mb-2">Alert Title</label>
                        <input type="text" id="title" name="title" required
                              class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div>
                        <label for="type" class="block text-gray-400 mb-2">Alert Type</label>
                        <select id="type" name="type" required
                               class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="info">Info</option>
                            <option value="success">Success</option>
                            <option value="warning">Warning</option>
                            <option value="important">Important</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="message" class="block text-gray-400 mb-2">Alert Message</label>
                    <textarea id="message" name="message" rows="3" required
                             class="w-full bg-gray-700 border border-gray-600 rounded-lg py-2 px-3 text-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                </div>
                
                <div class="mt-6 text-right">
                    <button type="submit" name="add_alert" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-800">
                        <i class="fas fa-plus mr-2"></i> Create Alert
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Alerts List -->
        <div class="bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-700">
            <h3 class="text-xl font-bold mb-4">Current Alerts</h3>
            
            <?php if($alerts_result && $alerts_result->num_rows > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-400 border-b border-gray-700">
                                <th class="pb-3">Title</th>
                                <th class="pb-3">Type</th>
                                <th class="pb-3">Created</th>
                                <th class="pb-3">Status</th>
                                <th class="pb-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($alert = $alerts_result->fetch_assoc()): ?>
                                <tr class="border-b border-gray-700">
                                    <td class="py-3">
                                        <p class="font-medium"><?php echo htmlspecialchars($alert['title']); ?></p>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars(substr($alert['content'], 0, 50)); ?>...</p>
                                    </td>
                                    <td class="py-3">
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold 
                                        <?php 
                                            if($alert['type'] == 'warning') {
                                                echo 'bg-yellow-900 text-yellow-300';
                                            } elseif($alert['type'] == 'important') {
                                                echo 'bg-red-900 text-red-300';
                                            } elseif($alert['type'] == 'success') {
                                                echo 'bg-green-900 text-green-300';
                                            } else {
                                                echo 'bg-blue-900 text-blue-300'; // info or default
                                            }
                                        ?>">
                                            <?php echo ucfirst($alert['type']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 text-sm"><?php echo date('M j, Y', strtotime($alert['created_at'])); ?></td>
                                    <td class="py-3">
                                        <?php if($alert['active']): ?>
                                            <span class="text-green-400"><i class="fas fa-check-circle mr-1"></i> Active</span>
                                        <?php else: ?>
                                            <span class="text-red-400"><i class="fas fa-times-circle mr-1"></i> Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 text-right">
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="inline-block">
                                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $alert['active'] ? 'true' : 'false'; ?>">
                                            <button type="submit" name="toggle_alert" class="text-yellow-500 hover:text-yellow-300 mx-1">
                                                <?php if($alert['active']): ?>
                                                    <i class="fas fa-eye-slash" title="Deactivate"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-eye" title="Activate"></i>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this alert?');">
                                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                            <button type="submit" name="delete_alert" class="text-red-500 hover:text-red-300 mx-1">
                                                <i class="fas fa-trash" title="Delete"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-gray-700 rounded-lg p-4 text-center">
                    <p class="text-gray-400">No alerts have been created yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-black text-white py-6 mt-12">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?> 