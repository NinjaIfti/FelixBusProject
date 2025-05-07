<?php
session_start();
include_once('../database/basedados.h');

$conn = connectDatabase();

// Define pricing tiers
$pricing_tiers = [
    'standard' => [
        'name' => 'Standard',
        'price' => 15,
        'description' => 'Basic travel with comfort',
        'features' => [
            'Comfortable seating',
            'Air conditioning',
            'Basic amenities'
        ],
        'color' => 'blue-600'
    ],
    'premium' => [
        'name' => 'Premium',
        'price' => 25,
        'description' => 'Enhanced comfort and features',
        'features' => [
            'Extra legroom',
            'WiFi access',
            'Power outlets',
            'Priority boarding'
        ],
        'color' => 'blue-800'
    ],
    'business' => [
        'name' => 'Business',
        'price' => 40,
        'description' => 'Luxury travel experience',
        'features' => [
            'Premium seating',
            'Complimentary snacks',
            'Dedicated service',
            'Flexible booking'
        ],
        'color' => 'gray-800'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - FelixBus</title>
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
                    <a href="routes.php" class="hover:text-blue-200">Routes</a>
                    <a href="timetables.php" class="hover:text-blue-200">Timetables</a>
                    <a href="prices.php" class="hover:text-blue-200 font-medium">Prices</a>
                    <a href="contact.php" class="hover:text-blue-200">Contact</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
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

    <!-- Page Header -->
    <div class="bg-blue-700 py-8 text-white">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold mb-2">Our Pricing</h1>
            <p class="text-lg">Transparent and competitive prices for all our routes.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Pricing Cards -->
        <div class="grid md:grid-cols-3 gap-8 mb-12">
            <?php foreach($pricing_tiers as $tier): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $tier['name'] === 'Premium' ? 'transform scale-105' : ''; ?>">
                <div class="bg-<?php echo $tier['color']; ?> text-white p-6">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $tier['name']; ?></h3>
                    <p class="text-<?php echo $tier['color']; ?>-100"><?php echo $tier['description']; ?></p>
                </div>
                <div class="p-6">
                    <div class="text-3xl font-bold text-gray-800 mb-4">$<?php echo $tier['price']; ?><span class="text-lg text-gray-600">/ticket</span></div>
                    <ul class="space-y-3 mb-6">
                        <?php foreach($tier['features'] as $feature): ?>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            <span><?php echo $feature; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="routes.php" class="block text-center bg-<?php echo $tier['color']; ?> text-white py-2 rounded-md hover:bg-<?php echo $tier['color']; ?>-700">Book Now</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Additional Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Additional Information</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Discounts Available</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>• 10% off for students with valid ID</li>
                        <li>• 15% off for seniors (65+)</li>
                        <li>• 20% off for children under 12</li>
                        <li>• Group discounts available for 10+ people</li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Terms & Conditions</h3>
                    <ul class="space-y-2 text-gray-600">
                        <li>• Prices may vary based on route and season</li>
                        <li>• All prices include taxes and fees</li>
                        <li>• Cancellation policy applies</li>
                        <li>• Special rates available for frequent travelers</li>
                    </ul>
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

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?> 