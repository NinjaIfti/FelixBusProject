<?php
session_start();
include_once('../../database/basedados.h');

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Connect to database
$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Process actions
$success_message = '';
$error_message = '';

// Create alert
if(isset($_POST['create_alert'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $type = $conn->real_escape_string($_POST['type']);
    $start_date = !empty($_POST['start_date']) ? "'".$conn->real_escape_string($_POST['start_date'])."'" : "NULL";
    $end_date = !empty($_POST['end_date']) ? "'".$conn->real_escape_string($_POST['end_date'])."'" : "NULL";
    
    $query = "INSERT INTO alerts (title, content, type, start_date, end_date, created_by) 
              VALUES ('$title', '$content', '$type', $start_date, $end_date, $user_id)";
              
    if($conn->query($query)) {
        $success_message = "Alert created successfully!";
    } else {
        $error_message = "Error creating alert: " . $conn->error;
    }
}

// Update alert
if(isset($_POST['update_alert'])) {
    $alert_id = intval($_POST['alert_id']);
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $type = $conn->real_escape_string($_POST['type']);
    $start_date = !empty($_POST['start_date']) ? "'".$conn->real_escape_string($_POST['start_date'])."'" : "NULL";
    $end_date = !empty($_POST['end_date']) ? "'".$conn->real_escape_string($_POST['end_date'])."'" : "NULL";
    
    $query = "UPDATE alerts SET 
              title = '$title', 
              content = '$content', 
              type = '$type', 
              start_date = $start_date, 
              end_date = $end_date 
              WHERE id = $alert_id";
              
    if($conn->query($query)) {
        $success_message = "Alert updated successfully!";
    } else {
        $error_message = "Error updating alert: " . $conn->error;
    }
}

// Delete alert
if(isset($_POST['delete_alert'])) {
    $alert_id = intval($_POST['alert_id']);
    
    $query = "DELETE FROM alerts WHERE id = $alert_id";
    
    if($conn->query($query)) {
        $success_message = "Alert deleted successfully!";
    } else {
        $error_message = "Error deleting alert: " . $conn->error;
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
    <title>Alert Management - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Sidebar -->
    <div class="flex flex-1">
        <div class="bg-blue-800 text-white w-64 py-6 flex-shrink-0 hidden md:block">
            <div class="px-6">
                <a href="dashboard.php" class="text-2xl font-bold mb-8 flex items-center">
                    <i class="fas fa-bus mr-3"></i> FelixBus
                </a>
            </div>
            <nav class="mt-10">
                <a href="dashboard.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                </a>
                <a href="users.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-users mr-3"></i> Users
                </a>
                <a href="routes.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-route mr-3"></i> Routes
                </a>
                <a href="tickets.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-ticket-alt mr-3"></i> Tickets
                </a>
                <a href="alerts.php" class="flex items-center py-3 px-6 bg-blue-700 bg-opacity-60">
                    <i class="fas fa-bullhorn mr-3"></i> Alerts
                </a>
                <a href="../index.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60">
                    <i class="fas fa-home mr-3"></i> Main Website
                </a>
                <a href="../logout.php" class="flex items-center py-3 px-6 hover:bg-blue-700 hover:bg-opacity-60 mt-auto">
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
                        <h1 class="text-2xl font-semibold text-gray-800">Alert Management</h1>
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
                
                <!-- Create Alert Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Alert</h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" id="title" name="title" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                <select id="type" name="type" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <option value="info">Information</option>
                                    <option value="alert">Alert</option>
                                    <option value="promotion">Promotion</option>
                                </select>
                            </div>
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date (optional)</label>
                                <input type="date" id="start_date" name="start_date" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date (optional)</label>
                                <input type="date" id="end_date" name="end_date" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                                <textarea id="content" name="content" rows="4" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                            </div>
                        </div>
                        <div class="mt-6 text-right">
                            <button type="submit" name="create_alert" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                                Create Alert
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Alerts List -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">All Alerts</h2>
                    
                    <?php if($alerts_result && $alerts_result->num_rows > 0): ?>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <?php while($alert = $alerts_result->fetch_assoc()): ?>
                                <div class="border rounded-lg overflow-hidden">
                                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                                        <div>
                                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded 
                                            <?php 
                                                echo $alert['type'] === 'alert' ? 'bg-red-100 text-red-800' : 
                                                    ($alert['type'] === 'promotion' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'); 
                                            ?>">
                                                <?php echo ucfirst($alert['type']); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Created by: <?php echo htmlspecialchars($alert['username']); ?>
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($alert['title']); ?></h3>
                                        <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($alert['content'])); ?></p>
                                        <div class="text-sm text-gray-500">
                                            <?php if($alert['start_date']): ?>
                                                <p>Start Date: <?php echo date('F j, Y', strtotime($alert['start_date'])); ?></p>
                                            <?php endif; ?>
                                            <?php if($alert['end_date']): ?>
                                                <p>End Date: <?php echo date('F j, Y', strtotime($alert['end_date'])); ?></p>
                                            <?php endif; ?>
                                            <p>Created: <?php echo date('F j, Y, g:i a', strtotime($alert['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                                        <button 
                                            type="button" 
                                            onclick="editAlert(<?php echo $alert['id']; ?>)" 
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button 
                                            type="button" 
                                            onclick="deleteAlert(<?php echo $alert['id']; ?>, '<?php echo htmlspecialchars($alert['title']); ?>')" 
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-5xl mb-4">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <p class="text-gray-600">No alerts found. Create a new alert to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Alert Modal -->
    <div id="editAlertModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Edit Alert</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('editAlertModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="editAlertForm">
                    <input type="hidden" id="edit_alert_id" name="alert_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="edit_title" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" id="edit_title" name="title" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label for="edit_type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select id="edit_type" name="type" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="info">Information</option>
                                <option value="alert">Alert</option>
                                <option value="promotion">Promotion</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit_start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date (optional)</label>
                            <input type="date" id="edit_start_date" name="start_date" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="edit_end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date (optional)</label>
                            <input type="date" id="edit_end_date" name="end_date" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label for="edit_content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                            <textarea id="edit_content" name="content" rows="4" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" onclick="closeModal('editAlertModal')">
                            Cancel
                        </button>
                        <button type="submit" name="update_alert" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update Alert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Alert Modal -->
    <div id="deleteAlertModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('deleteAlertModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mt-2">
                    <p class="text-gray-700">Are you sure you want to delete the alert "<span id="delete_alert_title" class="font-semibold"></span>"? This action cannot be undone.</p>
                </div>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="deleteAlertForm">
                    <input type="hidden" id="delete_alert_id" name="alert_id">
                    <div class="mt-4 flex justify-end space-x-3">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300" onclick="closeModal('deleteAlertModal')">
                            Cancel
                        </button>
                        <button type="submit" name="delete_alert" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.bg-blue-800').classList.toggle('hidden');
        });
        
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        
        function editAlert(alertId) {
            // Fetch alert data via AJAX and populate form (in a real implementation)
            // For now, we'll just open the modal
            openModal('editAlertModal');
            document.getElementById('edit_alert_id').value = alertId;
        }
        
        function deleteAlert(alertId, title) {
            document.getElementById('delete_alert_id').value = alertId;
            document.getElementById('delete_alert_title').textContent = title;
            openModal('deleteAlertModal');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?> 