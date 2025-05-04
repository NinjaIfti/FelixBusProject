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
            if($user['user_type'] == 'admin' || $user['user_type'] == 'staff') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: client/dashboard.php");
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
                <a href="login.php" class="font-medium hover:text-blue-200">Login</a>
                <a href="register.php" class="bg-white text-blue-600 px-4 py-2 rounded-full font-medium hover:bg-blue-100">Register</a>
            </div>
        </div>
    </nav>

    <!-- Login Form -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
                <div class="py-4 px-6 bg-blue-600 text-white text-center">
                    <h2 class="text-2xl font-bold">Login to Your Account</h2>
                </div>
                <div class="py-8 px-6">
                    <?php if($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-6">
                            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                            <input type="text" id="username" name="username" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <div class="mb-6">
                            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                            <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600">
                                <label for="remember" class="ml-2 block text-gray-700 text-sm">Remember me</label>
                            </div>
                            <a href="#" class="text-blue-600 text-sm hover:text-blue-800">Forgot Password?</a>
                        </div>
                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline w-full">
                                Login
                            </button>
                        </div>
                    </form>
                    <div class="mt-6 text-center">
                        <p class="text-gray-600 text-sm">Don't have an account? <a href="register.php" class="text-blue-600 hover:text-blue-800">Register now</a></p>
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