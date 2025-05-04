<?php
session_start();
include_once('../database/basedados.h');

// Check if already logged in
if(isset($_SESSION['user_id'])) {
    // Redirect based on user type
    if($_SESSION['user_type'] == 'admin' || $_SESSION['user_type'] == 'staff') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: client/dashboard.php");
    }
    exit;
}

$errors = [];
$success = false;

// Process registration
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = connectDatabase();
    
    // Get form data
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    $address = $conn->real_escape_string($_POST['address'] ?? '');
    
    // Basic validation
    if(empty($username)) {
        $errors[] = "Username is required";
    }
    
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if(empty($password)) {
        $errors[] = "Password is required";
    } elseif(strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if(empty($first_name)) {
        $errors[] = "First name is required";
    }
    
    if(empty($last_name)) {
        $errors[] = "Last name is required";
    }
    
    // Check if username exists
    $check_username = "SELECT id FROM users WHERE username = '$username'";
    $result = $conn->query($check_username);
    if($result->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    
    // Check if email exists
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);
    if($result->num_rows > 0) {
        $errors[] = "Email already exists";
    }
    
    // If no errors, register the user
    if(empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $sql = "INSERT INTO users (username, password, email, first_name, last_name, phone, address, user_type) 
                VALUES ('$username', '$hashed_password', '$email', '$first_name', '$last_name', '$phone', '$address', 'client')";
        
        if($conn->query($sql) === TRUE) {
            $user_id = $conn->insert_id;
            
            // Create wallet for the user
            $wallet_sql = "INSERT INTO wallets (user_id, balance) VALUES ('$user_id', 0.00)";
            $conn->query($wallet_sql);
            
            $success = true;
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FelixBus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-2xl font-bold">FelixBus</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="login.php" class="hover:text-blue-200">Login</a>
                <a href="register.php" class="bg-white text-blue-600 px-4 py-2 rounded-full font-medium hover:bg-blue-100">Register</a>
            </div>
        </div>
    </nav>

    <!-- Registration Form -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
                <div class="py-4 px-6 bg-blue-600 text-white text-center">
                    <h2 class="text-2xl font-bold">Create a New Account</h2>
                </div>
                <div class="py-8 px-6">
                    <?php if($success): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p>Registration successful! You can now <a href="login.php" class="font-bold">login</a>.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <ul class="list-disc pl-4">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!$success): ?>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username *</label>
                                <input type="text" id="username" name="username" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div>
                                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                                <input type="email" id="email" name="email" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div>
                                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password *</label>
                                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <p class="text-gray-600 text-xs mt-1">Password must be at least 6 characters</p>
                            </div>
                            <div>
                                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div>
                                <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div>
                                <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            <div>
                                <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-gray-700 text-sm font-bold mb-2">Address</label>
                                <textarea id="address" name="address" rows="3" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex items-center">
                            <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-blue-600" required>
                            <label for="terms" class="ml-2 block text-gray-700 text-sm">I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">Terms and Conditions</a> *</label>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full">
                                Register
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                    
                    <div class="mt-6 text-center">
                        <p class="text-gray-600 text-sm">Already have an account? <a href="login.php" class="text-blue-600 hover:text-blue-800">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-blue-800 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> FelixBus. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 