# 🚌 FelixBus - Premium Bus Service Platform

![FelixBus Banner](https://images.unsplash.com/photo-1570125909232-eb263c188f7e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80)

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

## 📋 Overview

FelixBus is a comprehensive bus service management system that redefines luxury bus travel with unmatched comfort, reliability, and style. The platform provides an intuitive interface for both customers and administrators to manage bookings, routes, tickets, and more.

## ✨ Features

### 👥 User Types
- **Clients**: Book tickets, manage wallet, view ticket history
- **Staff**: Manage tickets, routes, and customer inquiries
- **Admin**: Full system control, including user management and analytics

### 🎫 Core Functionality

#### 🧑‍💼 Client Features
- **User Registration & Authentication**
- **Route Exploration & Ticket Booking**
- **E-Wallet Management**
- **Ticket History & Management**
- **Profile Management**

#### 👨‍💻 Admin Features
- **Dashboard with Analytics**
- **User Management**
- **Route & Schedule Configuration**
- **Ticket Management**
- **Alert & Promotion Management**
- **Company Wallet Overview**

## 🔧 Technologies Used

- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML, CSS, JavaScript, TailwindCSS, AlpineJS
- **Icons**: Font Awesome

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx Web Server
- Composer (optional)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/FelixBusProject.git
   cd FelixBusProject
   ```

2. **Database Setup**
   - Import the SQL file to your MySQL server
   ```bash
   mysql -u username -p yourdbname < felixbus.sql
   ```
   - Or use the database creation script:
   ```bash
   php database/create_database.php
   ```

3. **Configure Database Connection**
   - Edit `database/basedados.h` with your MySQL credentials:
   ```php
   // Update these values with your database credentials
   $host = 'localhost';
   $username = 'your_username';
   $password = 'your_password';
   $database = 'felixbus';
   ```

4. **Web Server Configuration**
   - Configure your web server to point to the project directory
   - Ensure the document root is set to the project root

5. **Company Wallet Setup** (Optional)
   ```bash
   php database/setup_company_wallet.php
   ```

6. **Access the Application**
   - Open your web browser and navigate to `http://localhost/FelixBusProject`

## 🔐 Default Login Credentials

### Admin
- **Username**: admin
- **Password**: admin123

### Staff
- **Username**: staff
- **Password**: staff123

### Test Client
- **Username**: client
- **Password**: client123

## 📱 Screenshots

<div align="center">
  <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&q=80&fit=crop&w=400&h=300" alt="Homepage" width="30%">
  <img src="https://images.unsplash.com/photo-1612825173281-9a193378527e?ixlib=rb-4.0.3&q=80&fit=crop&w=400&h=300" alt="Booking Page" width="30%">
  <img src="https://images.unsplash.com/photo-1560264280-88b68371db39?ixlib=rb-4.0.3&q=80&fit=crop&w=400&h=300" alt="Admin Dashboard" width="30%">
</div>

## 🏗️ Project Structure

```
FelixBusProject/
├── database/               # Database configuration and setup scripts
├── pages/                  # Frontend pages
│   ├── admin/              # Admin panel pages
│   ├── client/             # Client area pages
│   └── ...                 # Public pages
├── report/                 # Project documentation
├── .git/                   # Git repository
├── .gitattributes          # Git attributes file
├── FelixBusProject.rar     # Compressed project archive
├── felixbus.sql            # Database SQL dump
└── README.md               # This file
```

## 🔄 Usage Flow

1. **Client**:
   - Register for an account
   - Browse available routes and schedules
   - Add funds to wallet
   - Book tickets
   - View ticket history and details

2. **Admin/Staff**:
   - Manage users, routes, and schedules
   - Create alerts and promotions
   - View booking statistics
   - Handle wallet transactions

## 🛠️ Development

### Extending the Project

1. **Adding New Routes**:
   - Log in as admin
   - Navigate to Routes management
   - Add new origin/destination with pricing

2. **Custom Alerts & Promotions**:
   - Use the Alerts section in admin panel
   - Set date ranges for time-limited promotions

## 👥 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgements

- [TailwindCSS](https://tailwindcss.com/) - For the beautiful UI
- [AlpineJS](https://alpinejs.dev/) - For interactive components
- [FontAwesome](https://fontawesome.com/) - For icons
- [Unsplash](https://unsplash.com/) - For stock images

---

<div align="center">
  <h3>💼 Developed by FelixBus Team 💼</h3>
  <p>For a premium bus travel experience</p>
</div> 