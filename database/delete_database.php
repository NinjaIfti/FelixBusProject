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

// Drop database if exists
$sql = "DROP DATABASE IF EXISTS felixbus";
if ($conn->query($sql) === TRUE) {
    echo "Database dropped successfully";
} else {
    echo "Error dropping database: " . $conn->error;
}

$conn->close();
?> 