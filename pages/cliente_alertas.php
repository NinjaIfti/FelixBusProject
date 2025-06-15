<?php
session_start();
include_once('../basedados/basedados.h');

// Check if user is logged in
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit;
}

$conn = connectDatabase();
$user_id = $_SESSION['user_id'];

// Get active alerts
$alerts_query = "SELECT id, title, content as message, type, created_at FROM alerts ORDER BY created_at DESC";
$alerts_result = $conn->query($alerts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts - FelixBus</title>
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
                    <a href="rotas.php" class="hover:text-red-500 nav-link">Routes</a>
                    <a href="horários.php" class="hover:text-red-500 nav-link">Timetables</a>
                    <a href="preços.php" class="hover:text-red-500 nav-link">Prices</a>
                    <a href="contactos.php" class="hover:text-red-500 nav-link">Contact</a>
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
                        <a href="cliente_painel.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                        <a href="cliente_bilhetes.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                        <a href="cliente_carteira.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                        <a href="perfil.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Profile</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-red-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Alerts & Notifications</h1>
            <p class="text-lg">Stay updated with the latest announcements from FelixBus</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 flex-1">
        <!-- Actions Bar -->
        <div class="mb-6">
            <a href="cliente_painel.php" class="text-red-500 hover:text-red-400">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Alerts List -->
        <div class="bg-gray-800 rounded-lg shadow-md p-6 card">
            <h2 class="text-xl font-semibold text-white mb-6">All Notifications</h2>
            
            <?php if($alerts_result && $alerts_result->num_rows > 0): ?>
                <div class="space-y-6">
                    <?php while($alert = $alerts_result->fetch_assoc()): ?>
                        <div class="bg-gray-700 rounded-lg p-6 border 
                            <?php 
                                if($alert['type'] == 'warning') {
                                    echo 'border-yellow-600';
                                } elseif($alert['type'] == 'important' || $alert['type'] == 'alert') {
                                    echo 'border-red-600';
                                } elseif($alert['type'] == 'success') {
                                    echo 'border-green-600';
                                } elseif($alert['type'] == 'promotion') {
                                    echo 'border-purple-600';
                                } else {
                                    echo 'border-blue-600'; // info or default
                                }
                            ?>">
                            <div class="flex flex-col md:flex-row justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center mb-3">
                                        <span class="inline-block mr-3 px-3 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                            if($alert['type'] == 'warning') {
                                                echo 'bg-yellow-900 text-yellow-300';
                                            } elseif($alert['type'] == 'important' || $alert['type'] == 'alert') {
                                                echo 'bg-red-900 text-red-300';
                                            } elseif($alert['type'] == 'success') {
                                                echo 'bg-green-900 text-green-300';
                                            } elseif($alert['type'] == 'promotion') {
                                                echo 'bg-purple-900 text-purple-300';
                                            } else {
                                                echo 'bg-blue-900 text-blue-300'; // info or default
                                            }
                                        ?>">
                                            <?php 
                                                if($alert['type'] == 'alert') {
                                                    echo 'Alert';
                                                } elseif($alert['type'] == 'promotion') {
                                                    echo 'Promotion';
                                                } else {
                                                    echo ucfirst($alert['type']); 
                                                }
                                            ?>
                                        </span>
                                        <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($alert['title']); ?></h3>
                                    </div>
                                    <div class="text-gray-300 mb-4">
                                        <?php echo nl2br(htmlspecialchars($alert['message'])); ?>
                                    </div>
                                </div>
                                <div class="text-right text-sm text-gray-400 mt-2 md:mt-0 md:ml-6 flex-shrink-0">
                                    <?php echo date('F j, Y', strtotime($alert['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-gray-700 rounded-lg p-8 text-center">
                    <div class="text-red-500 text-5xl mb-4">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-white mb-2">No Alerts</h3>
                    <p class="text-gray-400">There are no active notifications right now.</p>
                </div>
            <?php endif; ?>
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