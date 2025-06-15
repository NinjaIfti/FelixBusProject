-- FelixBus Database Creation Script
-- This script creates the database, tables, and inserts initial data

-- Create database
CREATE DATABASE IF NOT EXISTS felixbus;

-- Use the database
USE felixbus;

-- Users table - for all types of users
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    user_type ENUM('client', 'staff', 'admin') NOT NULL DEFAULT 'client',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Active Sessions table for session security
CREATE TABLE IF NOT EXISTS active_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    user_agent VARCHAR(255),
    ip_address VARCHAR(45),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, session_token)
);

-- Wallet table
CREATE TABLE IF NOT EXISTS wallets (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wallet transactions
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_type ENUM('deposito', 'withdrawal', 'compra', 'refund') NOT NULL,
    reference VARCHAR(100),
    processed_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Routes
CREATE TABLE IF NOT EXISTS routes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance DECIMAL(10,2),
    base_price DECIMAL(10,2) NOT NULL,
    capacity INT(11) NOT NULL DEFAULT 50,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Schedules
CREATE TABLE IF NOT EXISTS schedules (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    route_id INT(11) NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    days VARCHAR(100) NOT NULL,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- Tickets
CREATE TABLE IF NOT EXISTS tickets (
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
);

-- Alerts/Information/Promotions
CREATE TABLE IF NOT EXISTS alerts (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('alert', 'info', 'promotion') NOT NULL,
    start_date DATE,
    end_date DATE,
    created_by INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Create an admin user
-- Note: In a real SQL script we'd use a fixed password hash, but for demonstration:
INSERT INTO users (username, password, email, first_name, last_name, user_type) 
VALUES ('admin', '$2y$10$someFixedHashForAdmin', 'admin@felixbus.com', 'Admin', 'User', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Get admin user id
SET @admin_id = LAST_INSERT_ID();
-- If no insert happened (already exists), get the ID
SELECT @admin_id := id FROM users WHERE username='admin' AND @admin_id=0;

-- Create wallet for admin
INSERT INTO wallets (user_id, balance) 
VALUES (@admin_id, 1000.00)
ON DUPLICATE KEY UPDATE id=id;

-- Create a staff user
INSERT INTO users (username, password, email, first_name, last_name, user_type) 
VALUES ('staff', '$2y$10$someFixedHashForStaff', 'staff@felixbus.com', 'Staff', 'Member', 'staff')
ON DUPLICATE KEY UPDATE id=id;

-- Create a demo client user
INSERT INTO users (username, password, email, first_name, last_name, user_type) 
VALUES ('client', '$2y$10$someFixedHashForClient', 'client@example.com', 'Demo', 'Client', 'client')
ON DUPLICATE KEY UPDATE id=id;

-- Get client user id
SET @client_id = LAST_INSERT_ID();
-- If no insert happened (already exists), get the ID
SELECT @client_id := id FROM users WHERE username='client' AND @client_id=0;

-- Create wallet for client
INSERT INTO wallets (user_id, balance) 
VALUES (@client_id, 100.00)
ON DUPLICATE KEY UPDATE id=id;

-- Create FelixBus company account
INSERT INTO users (username, password, email, first_name, last_name, user_type) 
VALUES ('felixbus', '$2y$10$someFixedHashForCompany', 'company@felixbus.com', 'FelixBus', 'Company', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Get company user id
SET @company_id = LAST_INSERT_ID();
-- If no insert happened (already exists), get the ID
SELECT @company_id := id FROM users WHERE username='felixbus' AND @company_id=0;

-- Create wallet for company with initial balance
INSERT INTO wallets (user_id, balance) 
VALUES (@company_id, 10000.00)
ON DUPLICATE KEY UPDATE id=id; 