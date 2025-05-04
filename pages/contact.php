<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FelixBus</title>
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
                    <a href="contact.php" class="hover:text-blue-200 font-medium">Contact</a>
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
                <a href="contact.php" class="text-white py-2 font-medium">Contact</a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-blue-700 py-16 text-white">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl font-bold mb-4">Contact Us</h1>
            <p class="text-xl max-w-2xl mx-auto">We're here to help! Reach out to us with any questions or concerns about our bus services.</p>
        </div>
    </div>

    <!-- Contact Information -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-12">
                <div>
                    <h2 class="text-3xl font-bold mb-6 text-gray-800">Get in Touch</h2>
                    <p class="text-gray-600 mb-8">Feel free to contact us using any of the methods below. Our customer service team is available to assist you with any inquiries.</p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="text-blue-600 text-xl mr-4">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-1">Main Office</h3>
                                <p class="text-gray-600">123 Bus Station Road, City Center, State, 12345</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="text-blue-600 text-xl mr-4">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-1">Phone</h3>
                                <p class="text-gray-600">Customer Service: (123) 456-7890</p>
                                <p class="text-gray-600">Booking Inquiries: (123) 456-7891</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="text-blue-600 text-xl mr-4">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-1">Email</h3>
                                <p class="text-gray-600">General Inquiries: info@felixbus.com</p>
                                <p class="text-gray-600">Customer Support: support@felixbus.com</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="text-blue-600 text-xl mr-4">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-1">Operating Hours</h3>
                                <p class="text-gray-600">Monday - Friday: 8:00 AM - 8:00 PM</p>
                                <p class="text-gray-600">Saturday: 9:00 AM - 6:00 PM</p>
                                <p class="text-gray-600">Sunday: 10:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <h3 class="font-bold text-gray-800 mb-4">Follow Us</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="text-blue-600 hover:text-blue-800 text-2xl"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-blue-600 hover:text-blue-800 text-2xl"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-blue-600 hover:text-blue-800 text-2xl"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-blue-600 hover:text-blue-800 text-2xl"><i class="fab fa-linkedin-in"></i></a>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-3xl font-bold mb-6 text-gray-800">Send a Message</h2>
                    <form class="space-y-6">
                        <div>
                            <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Full Name *</label>
                            <input type="text" id="name" name="name" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                            <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <label for="subject" class="block text-gray-700 text-sm font-bold mb-2">Subject *</label>
                            <input type="text" id="subject" name="subject" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div>
                            <label for="message" class="block text-gray-700 text-sm font-bold mb-2">Message *</label>
                            <textarea id="message" name="message" rows="6" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required></textarea>
                        </div>
                        
                        <div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="py-8 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 text-center text-gray-800">Find Us</h2>
            <div class="h-96 bg-gray-300 rounded-lg">
                <!-- Replace with actual map embed code -->
                <div class="w-full h-full flex items-center justify-center">
                    <p class="text-gray-600">Map will be embedded here</p>
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