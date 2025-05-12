<?php
session_start();
include_once('../database/basedados.h');

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    // Redirect based on user type
    if($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'staff') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: client_dashboard.php");
    }
    exit;
}

$error = '';

// Process login
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connectDatabase();
    
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, username, password, user_type FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if(password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            
            // Redirect based on user type
            if($user['user_type'] == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif($user['user_type'] == 'staff') {
                header("Location: admin_dashboard.php");  // Staff go to admin dashboard as well
            } else {
                header("Location: client_dashboard.php");
            }
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Username not found";
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-image {
            background-image: url('https://images.unsplash.com/photo-1503525148566-ef5c2b9c93bd?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
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
        
        .form-card {
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .form-card:hover {
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
                <a href="login.php" class="nav-link text-red-500 font-medium">Login</a>
                <a href="register.php" class="bg-red-600 text-white px-4 py-2 rounded-md font-medium hover:bg-red-700 transition duration-300 btn-primary">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-image py-24 relative">
        <div class="absolute inset-0 bg-gradient-to-r from-black to-transparent"></div>
        <div class="container mx-auto px-4 text-center relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome <span class="text-red-600">Back</span></h1>
            <p class="text-xl max-w-2xl mx-auto">Sign in to your account to access your bookings, wallet, and more.</p>
        </div>
    </div>

    <!-- Login Form -->
    <section class="py-16">
        <div class="container mx-auto px-4 -mt-32 relative z-20">
            <div class="max-w-md mx-auto bg-gray-800 rounded-lg shadow-xl overflow-hidden border border-gray-700 form-card">
                <div class="py-4 px-6 bg-red-600 text-white text-center">
                    <h2 class="text-2xl font-bold">Login to Your Account</h2>
                </div>
                <div class="py-8 px-6">
                    <?php if($error): ?>
                        <div class="bg-red-900 border-l-4 border-red-500 text-white p-4 mb-6" role="alert">
                            <p><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                        <div>
                            <label for="username" class="block text-gray-300 text-sm font-medium mb-2">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                                <input type="text" id="username" name="username" class="bg-gray-700 border border-gray-600 pl-10 rounded w-full py-3 px-4 text-white leading-tight focus:outline-none focus:ring-2 focus:ring-red-500" required>
                            </div>
                        </div>
                        <div>
                            <label for="password" class="block text-gray-300 text-sm font-medium mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-500"></i>
                                </div>
                                <input type="password" id="password" name="password" class="bg-gray-700 border border-gray-600 pl-10 rounded w-full py-3 px-4 text-white leading-tight focus:outline-none focus:ring-2 focus:ring-red-500" required>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-red-600 bg-gray-700 border-gray-600 rounded focus:ring-red-500">
                                <label for="remember" class="ml-2 block text-gray-300 text-sm">Remember me</label>
                            </div>
                            <a href="#" class="text-red-500 text-sm hover:text-red-400 transition duration-300">Forgot Password?</a>
                        </div>
                        <div>
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 w-full transition duration-300 btn-primary">
                                <i class="fas fa-sign-in-alt mr-2"></i> Login
                            </button>
                        </div>
                    </form>
                    <div class="mt-6 text-center">
                        <p class="text-gray-400 text-sm">Don't have an account? <a href="register.php" class="text-red-500 hover:text-red-400 transition duration-300">Register now</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black text-white py-12 mt-12">
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
</body>
</html> 