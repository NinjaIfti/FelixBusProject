<?php
session_start();
include_once('../basedados/basedados.h');

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
        'color' => 'red-500'
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
        'color' => 'red-600'
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
        'color' => 'red-700'
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-image {
            background-image: url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #ef4444;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .btn-primary {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            z-index: -1;
        }
        
        .btn-primary:hover::before {
            left: 0;
        }
        
        .pricing-card {
            transition: all 0.3s ease;
        }
        
        .pricing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-gray-100">
    <!-- Navigation -->
    <nav class="bg-black text-white shadow-lg">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-2xl font-bold flex items-center">
                    <span class="text-red-600 mr-1"><i class="fas fa-bus"></i></span> 
                    <span>Felix<span class="text-red-600">Bus</span></span>
                </a>
                <div class="hidden md:flex space-x-6">
                    <a href="rotas.php" class="nav-link hover:text-red-500">Routes</a>
                    <a href="horários.php" class="nav-link hover:text-red-500">Timetables</a>
                    <a href="preços.php" class="nav-link text-red-500 font-medium">Prices</a>
                    <a href="contactos.php" class="nav-link hover:text-red-500">Contact</a>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button @click="open = !open" class="flex items-center space-x-1 hover:text-red-500 transition duration-300">
                            <span>My Account</span>
                            <i class="fas fa-chevron-down text-xs transition duration-300" :class="{ 'transform rotate-180': open }"></i>
                        </button>
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 w-48 py-2 mt-2 bg-gray-800 rounded-md shadow-xl z-20">
                            <?php if($_SESSION['user_type'] === 'client'): ?>
                                <a href="cliente_painel.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Dashboard</a>
                                <a href="cliente_bilhetes.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">My Tickets</a>
                                <a href="cliente_carteira.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Wallet</a>
                            <?php elseif($_SESSION['user_type'] === 'staff' || $_SESSION['user_type'] === 'admin'): ?>
                                <a href="admin_painel.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Admin Panel</a>
                            <?php endif; ?>
                            <a href="perfil.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Profile</a>
                            <a href="logout.php" class="block px-4 py-2 text-gray-200 hover:bg-red-600 hover:text-white">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link hover:text-red-500">Login</a>
                    <a href="registar.php" class="bg-red-600 text-white px-4 py-2 rounded-md font-medium hover:bg-red-700 transition duration-300 btn-primary">Register</a>
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
        <div id="mobile-menu" class="md:hidden hidden bg-gray-800 pb-4">
            <div class="container mx-auto px-4 flex flex-col space-y-2">
                <a href="rotas.php" class="text-white py-2 hover:text-red-500">Routes</a>
                <a href="horários.php" class="text-white py-2 hover:text-red-500">Timetables</a>
                <a href="preços.php" class="text-red-500 py-2 font-medium">Prices</a>
                <a href="contactos.php" class="text-white py-2 hover:text-red-500">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-image py-24 relative">
        <div class="absolute inset-0 bg-gradient-to-r from-black to-transparent"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Our <span class="text-red-600">Pricing</span> Plans</h1>
            <p class="text-xl max-w-2xl mx-auto">Choose the perfect ticket class for your journey with transparent and competitive pricing.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-12 -mt-16 relative z-20">
        <!-- Pricing Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            <?php foreach($pricing_tiers as $tier_key => $tier): ?>
            <div class="bg-gray-800 rounded-lg shadow-xl overflow-hidden border border-gray-700 pricing-card <?php echo $tier['name'] === 'Premium' ? 'transform md:scale-105 z-10' : ''; ?>">
                <div class="bg-<?php echo $tier['color']; ?> text-white p-6">
                    <h3 class="text-2xl font-bold mb-2"><?php echo $tier['name']; ?></h3>
                    <p class="text-gray-100"><?php echo $tier['description']; ?></p>
                </div>
                <div class="p-6">
                    <div class="text-3xl font-bold text-white mb-4">$<?php echo $tier['price']; ?><span class="text-lg text-gray-400">/ticket</span></div>
                    <ul class="space-y-3 mb-6">
                        <?php foreach($tier['features'] as $feature): ?>
                        <li class="flex items-center">
                            <i class="fas fa-check text-red-500 mr-2"></i>
                            <span class="text-gray-300"><?php echo $feature; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="rotas.php?plan=<?php echo $tier_key; ?>" class="block text-center bg-<?php echo $tier['color']; ?> text-white py-2 rounded-md hover:bg-<?php echo str_replace('500', '600', str_replace('600', '700', str_replace('700', '800', $tier['color']))); ?> transition duration-300 btn-primary">Book Now</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Additional Information -->
        <div class="bg-gray-800 rounded-lg shadow-xl p-8 mb-8 border border-gray-700">
            <h2 class="text-2xl font-bold text-white mb-6">Additional Information</h2>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-gray-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <span class="text-red-500 mr-3"><i class="fas fa-tag"></i></span>
                        Discounts Available
                    </h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>10% off for students with valid ID</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>15% off for seniors (65+)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>20% off for children under 12</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>Group discounts available for 10+ people</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gray-900 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <span class="text-red-500 mr-3"><i class="fas fa-file-alt"></i></span>
                        Terms & Conditions
                    </h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>Prices may vary based on route and season</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>All prices include taxes and fees</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>Cancellation policy applies</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-red-500 mr-2 mt-1"></i>
                            <span>Special rates available for frequent travelers</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="bg-gray-800 rounded-lg shadow-xl p-8 border border-gray-700">
            <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                <span class="text-red-500 mr-3"><i class="fas fa-question-circle"></i></span>
                Frequently Asked Questions
            </h2>
            
            <div class="space-y-4" x-data="{selected:0}">
                <div class="border border-gray-700 rounded-lg overflow-hidden">
                    <div 
                        @click="selected !== 1 ? selected = 1 : selected = null"
                        class="flex justify-between items-center p-4 cursor-pointer bg-gray-900 hover:bg-gray-800 transition-colors"
                        :class="{'bg-gray-800': selected === 1}"
                    >
                        <h3 class="text-white font-medium">How do I book a ticket?</h3>
                        <span class="text-red-500" x-show="selected !== 1"><i class="fas fa-plus"></i></span>
                        <span class="text-red-500" x-show="selected === 1"><i class="fas fa-minus"></i></span>
                    </div>
                    <div
                        x-show="selected === 1"
                        x-transition:enter="transition-all ease-out duration-300"
                        x-transition:enter-start="opacity-0 max-h-0"
                        x-transition:enter-end="opacity-100 max-h-96"
                        x-transition:leave="transition-all ease-in duration-200"
                        x-transition:leave-start="opacity-100 max-h-96"
                        x-transition:leave-end="opacity-0 max-h-0"
                        class="p-4 bg-gray-900 text-gray-300"
                    >
                        You can book a ticket by visiting our Routes page, selecting your desired journey, and following the booking process. You can also book through our mobile app or by calling our customer service.
                    </div>
                </div>
                
                <div class="border border-gray-700 rounded-lg overflow-hidden">
                    <div 
                        @click="selected !== 2 ? selected = 2 : selected = null"
                        class="flex justify-between items-center p-4 cursor-pointer bg-gray-900 hover:bg-gray-800 transition-colors"
                        :class="{'bg-gray-800': selected === 2}"
                    >
                        <h3 class="text-white font-medium">Can I change my booking class after purchase?</h3>
                        <span class="text-red-500" x-show="selected !== 2"><i class="fas fa-plus"></i></span>
                        <span class="text-red-500" x-show="selected === 2"><i class="fas fa-minus"></i></span>
                    </div>
                    <div
                        x-show="selected === 2"
                        x-transition:enter="transition-all ease-out duration-300"
                        x-transition:enter-start="opacity-0 max-h-0"
                        x-transition:enter-end="opacity-100 max-h-96"
                        x-transition:leave="transition-all ease-in duration-200"
                        x-transition:leave-start="opacity-100 max-h-96"
                        x-transition:leave-end="opacity-0 max-h-0"
                        class="p-4 bg-gray-900 text-gray-300"
                    >
                        Yes, you can upgrade your booking class up to 24 hours before departure, subject to availability. Please contact our customer service for assistance. Downgrading to a lower class may be subject to our refund policy.
                    </div>
                </div>
                
                <div class="border border-gray-700 rounded-lg overflow-hidden">
                    <div 
                        @click="selected !== 3 ? selected = 3 : selected = null"
                        class="flex justify-between items-center p-4 cursor-pointer bg-gray-900 hover:bg-gray-800 transition-colors"
                        :class="{'bg-gray-800': selected === 3}"
                    >
                        <h3 class="text-white font-medium">Are there any seasonal discounts available?</h3>
                        <span class="text-red-500" x-show="selected !== 3"><i class="fas fa-plus"></i></span>
                        <span class="text-red-500" x-show="selected === 3"><i class="fas fa-minus"></i></span>
                    </div>
                    <div
                        x-show="selected === 3"
                        x-transition:enter="transition-all ease-out duration-300"
                        x-transition:enter-start="opacity-0 max-h-0"
                        x-transition:enter-end="opacity-100 max-h-96"
                        x-transition:leave="transition-all ease-in duration-200"
                        x-transition:leave-start="opacity-100 max-h-96"
                        x-transition:leave-end="opacity-0 max-h-0"
                        class="p-4 bg-gray-900 text-gray-300"
                    >
                        Yes, we offer seasonal promotions and special discounts throughout the year. Subscribe to our newsletter or follow us on social media to stay informed about our latest offers and promotions.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-black text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Felix<span class="text-red-600">Bus</span></h3>
                    <p class="mb-4 text-gray-400">Redefining luxury travel with comfort, reliability, and exceptional service.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 transition duration-300"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="rotas.php" class="text-gray-400 hover:text-red-500 transition duration-300">Routes</a></li>
                        <li><a href="horários.php" class="text-gray-400 hover:text-red-500 transition duration-300">Timetables</a></li>
                        <li><a href="preços.php" class="text-gray-400 hover:text-red-500 transition duration-300">Prices</a></li>
                        <li><a href="contactos.php" class="text-gray-400 hover:text-red-500 transition duration-300">Contact Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-red-500 transition duration-300">Terms & Conditions</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-red-500 transition duration-300">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Contact Information</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-red-500"></i>
                            <span class="text-gray-400">123 Transport Avenue, Downtown<br>Business District, 10001</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3 text-red-500"></i>
                            <span class="text-gray-400">(123) 456-7890</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-red-500"></i>
                            <span class="text-gray-400">info@felixbus.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock mr-3 text-red-500"></i>
                            <span class="text-gray-400">Mon-Sun: 8:00 AM - 10:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="mt-12 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500">&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
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
<?php $conn->close(); ?> 