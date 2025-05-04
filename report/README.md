# FelixBus Project

A comprehensive bus booking system with ticket management, wallet functionality, and role-based access control.

## Project Structure

The project is organized into three main folders:

1. **database**: Contains database connection files and setup scripts
2. **pages**: Contains all the website pages and assets
3. **report**: Documentation and project information

## Features

### Visitor Features
- View company information (location, contact details, operating hours)
- Browse bus routes, timetables, and prices
- View dynamic alerts/information/promotions

### Client Features
- Registration and authentication
- Account management
- Wallet system (check balance, add/withdraw funds)
- Purchase tickets for specific routes, dates, and times
- View purchased tickets

### Staff Member Features
- Access to a Management Area
- Edit personal information
- Purchase tickets on behalf of clients
- Manage client wallet balances

### Administrator Features
- All staff permissions
- Route management (create/edit routes with origin, destination, schedules)
- User management
- Manage alerts/information/promotions
- Edit personal information

## Technologies Used

- **Frontend**: HTML, Tailwind CSS
- **Backend**: PHP
- **Database**: MySQL

## Setup Instructions

### Database Setup

1. Navigate to the `database` folder
2. Run `create_database.php` to set up the database schema and tables
3. If needed, you can run `delete_database.php` to drop the database

### Application Access

1. Visit the homepage to access the application
2. Default admin credentials:
   - Username: admin
   - Password: admin123

## Core Functionalities

### Authentication System
- Login/logout functionality
- Role-based access control

### Wallet System
- Add funds
- Withdraw funds
- Transaction history
- Automatic debit for ticket purchases

### Ticket Management
- Purchase tickets
- View ticket details
- Each ticket has a unique identifier
- Tickets are valid only for specific route, date, and time

### Route Management (Admin)
- Add and edit routes
- Set origin, destination, schedules, and pricing
- Manage seating capacity

## Database Schema

### Main Tables

1. **users**: Stores user information (clients, staff, admins)
2. **wallets**: Manages user wallet balances
3. **wallet_transactions**: Logs all wallet operations
4. **routes**: Stores bus routes
5. **schedules**: Contains schedule information for routes
6. **tickets**: Stores ticket information
7. **alerts**: Stores alerts/promotions

## Security Considerations

- Password hashing
- SQL injection protection
- Input validation
- Session security

## Future Enhancements

- Email notification system
- Mobile-responsive design improvements
- Payment gateway integration
- Route rating and review system
- Expansion of reporting and analytics 