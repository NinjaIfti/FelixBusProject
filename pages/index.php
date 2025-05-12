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
    <title>FelixBus - Premium Bus Services</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-image {
            background-image: url('https://images.unsplash.com/photo-1570125909232-eb263c188f7e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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
        
        .social-icon {
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            transform: translateY(-5px);
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
                    <a href="routes.php" class="nav-link hover:text-red-500">Routes</a>
                    <a href="timetables.php" class="nav-link hover:text-red-500">Timetables</a>
                    <a href="prices.php" class="nav-link hover:text-red-500">Prices</a>
                    <a href="contact.php" class="nav-link hover:text-red-500">Contact</a>
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
                <?php else: ?>
                    <a href="login.php" class="nav-link hover:text-red-500">Login</a>
                    <a href="register.php" class="bg-red-600 text-white px-4 py-2 rounded-md font-medium hover:bg-red-700 transition duration-300 btn-primary">Register</a>
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
                <a href="routes.php" class="text-white py-2 hover:text-red-500">Routes</a>
                <a href="timetables.php" class="text-white py-2 hover:text-red-500">Timetables</a>
                <a href="prices.php" class="text-white py-2 hover:text-red-500">Prices</a>
                <a href="contact.php" class="text-white py-2 hover:text-red-500">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative">
        <div class="hero-image h-screen max-h-[600px] bg-black relative">
            <div class="absolute inset-0 bg-gradient-to-r from-black to-transparent"></div>
            <div class="container mx-auto px-4 h-full flex items-center relative z-10">
                <div class="text-white max-w-2xl">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">Experience <span class="text-red-600">Premium</span> Travel</h1>
                    <p class="text-xl mb-8">FelixBus redefines luxury bus travel with unmatched comfort, reliability, and style. Join us for an exceptional journey.</p>
                    <div class="flex space-x-4">
                        <a href="routes.php" class="bg-red-600 text-white px-6 py-3 rounded-md font-medium hover:bg-red-700 transition duration-300 btn-primary">
                            Explore Routes
                        </a>
                        <a href="register.php" class="bg-transparent border-2 border-white text-white px-6 py-3 rounded-md font-medium hover:border-red-600 hover:text-red-600 transition duration-300">
                            Join Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alerts/Promotions Section -->
    <?php if($alerts_result && $alerts_result->num_rows > 0): ?>
    <section class="py-16 bg-gray-900">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-white mb-2">Latest Updates</h2>
            <p class="text-center text-gray-400 mb-12">Stay informed with our latest offers and announcements</p>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while($alert = $alerts_result->fetch_assoc()): ?>
                    <div class="bg-gray-800 p-6 rounded-lg shadow-md border-l-4 transform transition duration-300 hover:scale-105 hover:shadow-lg <?php 
                        echo $alert['type'] === 'alert' ? 'border-red-600' : 
                             ($alert['type'] === 'promotion' ? 'border-green-500' : 'border-gray-500'); 
                    ?>">
                        <h3 class="text-xl font-semibold mb-2 <?php echo $alert['type'] === 'alert' ? 'text-red-500' : 'text-white'; ?>"><?php echo htmlspecialchars($alert['title']); ?></h3>
                        <p class="text-gray-300 mb-4"><?php echo htmlspecialchars($alert['content']); ?></p>
                        <?php if($alert['end_date']): ?>
                            <p class="text-sm text-gray-400">Valid until: <?php echo date('F j, Y', strtotime($alert['end_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section class="py-16 bg-black">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-white mb-2">The FelixBus Experience</h2>
            <p class="text-center text-gray-400 mb-12">What sets us apart from the rest</p>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-800 p-8 rounded-lg shadow-md text-center feature-card">
                    <div class="text-red-600 text-4xl mb-4">
                        <i class="fas fa-bus"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-white">Luxury Fleet</h3>
                    <p class="text-gray-300">Experience ultimate comfort with our premium buses featuring plush seating, extra legroom, and modern amenities.</p>
                </div>
                <div class="bg-gray-800 p-8 rounded-lg shadow-md text-center feature-card">
                    <div class="text-red-600 text-4xl mb-4">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-white">Strategic Network</h3>
                    <p class="text-gray-300">Our extensive network connects major destinations with convenient schedules and strategic routes.</p>
                </div>
                <div class="bg-gray-800 p-8 rounded-lg shadow-md text-center feature-card">
                    <div class="text-red-600 text-4xl mb-4">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-white">Seamless Booking</h3>
                    <p class="text-gray-300">Book tickets in seconds with our intuitive platform. Manage reservations, changes, and travel details effortlessly.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 bg-gray-900">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-white mb-2">What Our Customers Say</h2>
            <p class="text-center text-gray-400 mb-12">Real experiences from satisfied travelers</p>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-gray-800 p-6 rounded-lg shadow-md transition duration-300 hover:shadow-xl">
                    <div class="text-red-500 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-300 mb-4">"The comfort and service on FelixBus is unmatched. Their punctuality and professional staff make every journey a pleasure."</p>
                    <div class="flex items-center">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80" alt="Customer" class="w-10 h-10 rounded-full mr-4 object-cover">
                        <div>
                            <h4 class="font-semibold text-white">Maria Rodriguez</h4>
                            <p class="text-sm text-gray-400">Frequent Traveler</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-lg shadow-md transition duration-300 hover:shadow-xl">
                    <div class="text-red-500 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-300 mb-4">"I always choose FelixBus for business travel. The onboard amenities allow me to work comfortably while on the move."</p>
                    <div class="flex items-center">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80" alt="Customer" class="w-10 h-10 rounded-full mr-4 object-cover">
                        <div>
                            <h4 class="font-semibold text-white">James Wilson</h4>
                            <p class="text-sm text-gray-400">Business Executive</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-lg shadow-md transition duration-300 hover:shadow-xl">
                    <div class="text-red-500 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="text-gray-300 mb-4">"The online booking system is incredibly easy to use. I love how I can manage my tickets directly from my phone."</p>
                    <div class="flex items-center">
                        <img src="https://images.unsplash.com/photo-1567532939604-b6b5b0db2604?ixlib=rb-4.0.3&auto=format&fit=crop&w=150&q=80" alt="Customer" class="w-10 h-10 rounded-full mr-4 object-cover">
                        <div>
                            <h4 class="font-semibold text-white">Sarah Johnson</h4>
                            <p class="text-sm text-gray-400">Student</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4">Felix<span class="text-red-600">Bus</span></h3>
                    <p class="mb-4 text-gray-400">Redefining luxury travel with comfort, reliability, and exceptional service.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-red-500 social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-red-500 social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="routes.php" class="text-gray-400 hover:text-red-500 transition duration-300">Routes</a></li>
                        <li><a href="timetables.php" class="text-gray-400 hover:text-red-500 transition duration-300">Timetables</a></li>
                        <li><a href="prices.php" class="text-gray-400 hover:text-red-500 transition duration-300">Prices</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-red-500 transition duration-300">Contact Us</a></li>
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