<?php
// Database connection info
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS felixbus";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
    exit;
}

// Select the database
$conn->select_db("felixbus");

// Create tables
$tables = [
    // Users table - for all types of users
    "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        user_type ENUM('client', 'staff', 'admin') NOT NULL DEFAULT 'client',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Wallet table
    "CREATE TABLE IF NOT EXISTS wallets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Wallet transactions
    "CREATE TABLE IF NOT EXISTS wallet_transactions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        wallet_id INT(11) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        transaction_type ENUM('deposit', 'withdrawal', 'purchase', 'refund') NOT NULL,
        reference VARCHAR(100),
        processed_by INT(11) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
        FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
    )",
    
    // Routes
    "CREATE TABLE IF NOT EXISTS routes (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        origin VARCHAR(100) NOT NULL,
        destination VARCHAR(100) NOT NULL,
        distance DECIMAL(10,2),
        base_price DECIMAL(10,2) NOT NULL,
        capacity INT(11) NOT NULL DEFAULT 50,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Schedules
    "CREATE TABLE IF NOT EXISTS schedules (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        route_id INT(11) NOT NULL,
        departure_time TIME NOT NULL,
        arrival_time TIME NOT NULL,
        days VARCHAR(100) NOT NULL,
        FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
    )",
    
    // Tickets
    "CREATE TABLE IF NOT EXISTS tickets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        schedule_id INT(11) NOT NULL,
        travel_date DATE NOT NULL,
        ticket_number VARCHAR(20) NOT NULL UNIQUE,
        price DECIMAL(10,2) NOT NULL,
        status ENUM('active', 'used', 'cancelled') NOT NULL DEFAULT 'active',
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        purchased_by INT(11) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
        FOREIGN KEY (purchased_by) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // Alerts/Information/Promotions
    "CREATE TABLE IF NOT EXISTS alerts (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        type ENUM('alert', 'info', 'promotion') NOT NULL,
        start_date DATE,
        end_date DATE,
        created_by INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )"
];

// Execute each table creation query
foreach ($tables as $table_query) {
    if ($conn->query($table_query) === TRUE) {
        echo "Table created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Create an admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_sql = "INSERT INTO users (username, password, email, first_name, last_name, user_type) 
              VALUES ('admin', '$admin_password', 'admin@felixbus.com', 'Admin', 'User', 'admin')
              ON DUPLICATE KEY UPDATE id=id";

if ($conn->query($admin_sql) === TRUE) {
    echo "Admin user created successfully<br>";
    
    // Get admin user id
    $admin_id = $conn->insert_id ?: $conn->query("SELECT id FROM users WHERE username='admin'")->fetch_assoc()['id'];
    
    // Create wallet for admin
    $wallet_sql = "INSERT INTO wallets (user_id, balance) 
                  VALUES ('$admin_id', 1000.00)
                  ON DUPLICATE KEY UPDATE id=id";
    
    if ($conn->query($wallet_sql) === TRUE) {
        echo "Admin wallet created successfully<br>";
    } else {
        echo "Error creating admin wallet: " . $conn->error . "<br>";
    }
} else {
    echo "Error creating admin user: " . $conn->error . "<br>";
}

// Create a staff user
$staff_password = password_hash('staff123', PASSWORD_DEFAULT);
$staff_sql = "INSERT INTO users (username, password, email, first_name, last_name, user_type) 
              VALUES ('staff', '$staff_password', 'staff@felixbus.com', 'Staff', 'Member', 'staff')
              ON DUPLICATE KEY UPDATE id=id";

if ($conn->query($staff_sql) === TRUE) {
    echo "Staff user created successfully<br>";
} else {
    echo "Error creating staff user: " . $conn->error . "<br>";
}

// Create a demo client user
$client_password = password_hash('client123', PASSWORD_DEFAULT);
$client_sql = "INSERT INTO users (username, password, email, first_name, last_name, user_type) 
               VALUES ('client', '$client_password', 'client@example.com', 'Demo', 'Client', 'client')
               ON DUPLICATE KEY UPDATE id=id";

if ($conn->query($client_sql) === TRUE) {
    echo "Demo client created successfully<br>";
    
    // Get client user id
    $client_id = $conn->insert_id ?: $conn->query("SELECT id FROM users WHERE username='client'")->fetch_assoc()['id'];
    
    // Create wallet for client
    $wallet_sql = "INSERT INTO wallets (user_id, balance) 
                  VALUES ('$client_id', 100.00)
                  ON DUPLICATE KEY UPDATE id=id";
    
    if ($conn->query($wallet_sql) === TRUE) {
        echo "Client wallet created successfully<br>";
    } else {
        echo "Error creating client wallet: " . $conn->error . "<br>";
    }
} else {
    echo "Error creating demo client: " . $conn->error . "<br>";
}

$conn->close();
echo "Database setup completed.";
?> 