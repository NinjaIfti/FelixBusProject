<?php
session_start();
include_once('../database/basedados.h');

// Fetch alerts/promotions
$conn = connectDatabase();
$alerts_query = "SELECT * FROM alerts WHERE 
                (start_date IS NULL OR start_date <= CURDATE()) AND 
                (end_date IS NULL OR end_date >= CURDATE())
                ORDER BY created_at DESC LIMIT 5";
$alerts_result = $conn->query($alerts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FelixBus - Bus Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-2xl font-bold">FelixBus</a>
                <div class="hidden md:flex space-x-4">
                    <a href="routes.php" class="hover:text-blue-200">Routes</a>
                    <a href="timetables.php" class="hover:text-blue-200">Timetables</a>
                    <a href="prices.php" class="hover:text-blue-200">Prices</a>
                    <a href="contact.php" class="hover:text-blue-200">Contact</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="relative group">
                        <button class="flex items-center space-x-1">
                            <span>My Account</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 w-48 py-2 mt-2 bg-white rounded-md shadow-xl z-20 hidden group-hover:block">
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
                <?php else: ?>
                    <a href="login.php" class="hover:text-blue-200">Login</a>
                    <a href="register.php" class="bg-white text-blue-600 px-4 py-2 rounded-full font-medium hover:bg-blue-100">Register</a>
                <?php endif; ?>
            </div>
        </div>
        <!-- Mobile menu button -->
        <div class="md:hidden flex justify-center pb-3">
            <button id="mobile-menu-button" class="text-white focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden bg-blue-700 pb-4">
            <div class="container mx-auto px-4 flex flex-col space-y-2">
                <a href="routes.php" class="text-white py-2">Routes</a>
                <a href="timetables.php" class="text-white py-2">Timetables</a>
                <a href="prices.php" class="text-white py-2">Prices</a>
                <a href="contact.php" class="text-white py-2">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative">
        <div class="h-96 bg-gradient-to-r from-blue-500 to-blue-700 relative">
            <div class="container mx-auto px-4 h-full flex items-center">
                <div class="text-white max-w-2xl">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">Travel with Comfort and Style</h1>
                    <p class="text-xl mb-8">FelixBus offers reliable and comfortable bus services across the country. Book your tickets now!</p>
                    <div class="flex space-x-4">
                        <a href="routes.php" class="bg-white text-blue-600 px-6 py-3 rounded-full font-medium hover:bg-blue-100">View Routes</a>
                        <a href="register.php" class="bg-transparent border-2 border-white text-white px-6 py-3 rounded-full font-medium hover:bg-white hover:text-blue-600">Register Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alerts/Promotions Section -->
    <?php if($alerts_result && $alerts_result->num_rows > 0): ?>
    <section class="py-8 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">News & Promotions</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while($alert = $alerts_result->fetch_assoc()): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 <?php 
                        echo $alert['type'] === 'alert' ? 'border-red-500' : 
                             ($alert['type'] === 'promotion' ? 'border-green-500' : 'border-blue-500'); 
                    ?>">
                        <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($alert['title']); ?></h3>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($alert['content']); ?></p>
                        <?php if($alert['end_date']): ?>
                            <p class="text-sm text-gray-500">Valid until: <?php echo date('F j, Y', strtotime($alert['end_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section class="py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Why Choose FelixBus?</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <div class="text-blue-600 text-4xl mb-4">
                        <i class="fas fa-bus"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Modern Fleet</h3>
                    <p class="text-gray-600">Travel in comfort with our modern, well-maintained buses featuring amenities like WiFi and comfortable seating.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <div class="text-blue-600 text-4xl mb-4">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Extensive Network</h3>
                    <p class="text-gray-600">We connect major cities and towns, making it easy to reach your destination.</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md text-center">
                    <div class="text-blue-600 text-4xl mb-4">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Easy Booking</h3>
                    <p class="text-gray-600">Book your tickets online with our simple booking system. Manage your trips with ease.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-10">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">FelixBus</h3>
                    <p class="mb-4">Providing comfortable and reliable bus services.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-white hover:text-blue-200"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white hover:text-blue-200"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white hover:text-blue-200"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="routes.php" class="hover:text-blue-200">Routes</a></li>
                        <li><a href="timetables.php" class="hover:text-blue-200">Timetables</a></li>
                        <li><a href="prices.php" class="hover:text-blue-200">Prices</a></li>
                        <li><a href="contact.php" class="hover:text-blue-200">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Contact Information</h3>
                    <p class="mb-2"><i class="fas fa-map-marker-alt mr-2"></i> 123 Bus Station Road, City</p>
                    <p class="mb-2"><i class="fas fa-phone mr-2"></i> (123) 456-7890</p>
                    <p class="mb-2"><i class="fas fa-envelope mr-2"></i> info@felixbus.com</p>
                    <p><i class="fas fa-clock mr-2"></i> Mon-Fri: 8:00 AM - 8:00 PM</p>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-blue-700 text-center">
                <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html> 