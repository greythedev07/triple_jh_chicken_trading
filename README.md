# 🐔 Triple JH Chicken Trading - Delivery Management System

A comprehensive web-based delivery management system for chicken trading business with multi-role support (Customers, Drivers, Admins) and integrated payment processing.

## 📋 Table of Contents

- [Features](#-features)
- [Technology Stack](#-technology-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Database Setup](#-database-setup)
- [User Roles](#-user-roles)
- [API Endpoints](#-api-endpoints)
- [Project Structure](#-project-structure)
- [Usage Guide](#-usage-guide)
- [Screenshots](#-screenshots)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### 🛒 Customer Features

- **User Registration & Authentication** - Secure account creation and login
- **Product Catalog** - Browse available chicken products with images and pricing
- **Shopping Cart** - Add/remove items with quantity management
- **Multiple Payment Methods** - Cash on Delivery (COD) and GCash integration
- **Order Tracking** - Real-time order status updates
- **Order History** - Complete delivery history with details
- **Address Management** - Multiple delivery addresses with landmarks

### 🚚 Driver Features

- **Driver Registration** - Join the delivery network with vehicle details
- **Order Management** - View assigned pickups and ongoing deliveries
- **Pickup Confirmation** - Photo proof of pickup with timestamp
- **Delivery Completion** - Payment collection with automatic change calculation
- **Delivery History** - Track completed deliveries and earnings
- **Real-time Validation** - Payment amount validation and change calculation

### 👨‍💼 Admin Features

- **Dashboard Analytics** - Sales summaries, order statistics, and performance metrics
- **Product Management** - Add, edit, delete products with stock tracking
- **Order Management** - View all orders, assign drivers, verify payments
- **Driver Management** - Activate/deactivate drivers, view driver performance
- **Payment Verification** - Verify GCash payments and approve orders
- **User Management** - View customer accounts and order history
- **Admin Key System** - Secure admin registration with unique keys

### 💳 Payment System

- **Cash on Delivery (COD)** - Traditional payment method
- **GCash Integration** - Digital payment with QR code support
- **Payment Verification** - Admin approval system for GCash payments
- **Automatic Change Calculation** - Smart change calculation for drivers

## 🛠 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3.2
- **Icons**: Bootstrap Icons
- **Server**: Apache/Nginx (XAMPP recommended)

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (recommended for local development)

### Step 1: Clone the Repository

```bash
git clone https://github.com/yourusername/commissioned_app.git
cd commissioned_app
```

### Step 2: Setup Web Server

1. Copy the project folder to your web server directory:
   - **XAMPP**: `C:\xampp\htdocs\commissioned_app`
   - **WAMP**: `C:\wamp64\www\commissioned_app`
   - **LAMP**: `/var/www/html/commissioned_app`

### Step 3: Start Services

- Start Apache and MySQL services
- Ensure both services are running

## ⚙️ Configuration

### Database Configuration

Edit `config.php` to match your database settings:

```php
<?php
$db_user = "root";           // Your MySQL username
$db_pass = "";               // Your MySQL password
$db_name = "commissioned_app_database";  // Database name

$db = new PDO('mysql:host=localhost;dbname=' . $db_name . ';charset=utf8', $db_user, $db_pass);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### File Permissions

Ensure the `uploads/` directory has write permissions:

```bash
chmod 755 uploads/
chmod 755 uploads/deliveries/
chmod 755 uploads/pickups/
chmod 755 uploads/items/
chmod 755 uploads/qr_codes/
```

## 🗄️ Database Setup

### Option 1: Automatic Setup (Recommended)

1. Navigate to: `http://localhost/commissioned_app/database_setup.sql`
2. Import the SQL file through phpMyAdmin or MySQL command line
3. The script will create all necessary tables and sample data

### Option 2: Manual Setup

1. Create a new MySQL database named `commissioned_app_database`
2. Import the `database_setup.sql` file
3. Verify all tables are created successfully

### Database Schema

The system includes the following main tables:

- `users` - Customer accounts
- `drivers` - Driver accounts and vehicle information
- `admins` - Admin accounts
- `products` - Product catalog
- `pending_delivery` - Active orders
- `to_be_delivered` - Orders in transit
- `history_of_delivery` - Completed deliveries
- `cart` - Shopping cart items
- `gcash_qr_codes` - Payment QR codes

## 👥 User Roles

### 🛒 Customer

- **Access**: `http://localhost/commissioned_app/`
- **Features**: Browse products, place orders, track deliveries
- **Registration**: Public registration available

### 🚚 Driver

- **Access**: `http://localhost/commissioned_app/drivers/driver_login.php`
- **Features**: Manage deliveries, confirm pickups, complete deliveries
- **Registration**: Public registration with vehicle details

### 👨‍💼 Admin

- **Access**: `http://localhost/commissioned_app/adminaccounts/admin_login.php`
- **Features**: Manage products, orders, drivers, and system settings
- **Registration**: Requires admin key (default: `80085`)

## 🔌 API Endpoints

### Admin Endpoints

- `POST /admin/verify_gcash_payment.php` - Verify GCash payments
- `POST /admin/assign_driver.php` - Assign driver to order
- `POST /admin/cancel_order_admin.php` - Cancel orders
- `GET /admin/fetch_pending_deliveries.php` - Get pending orders
- `GET /admin/get_stats.php` - Get dashboard statistics

### Driver Endpoints

- `POST /drivers/driver_pickup_process.php` - Confirm pickup
- `POST /drivers/driver_delivery_process.php` - Complete delivery
- `POST /drivers/driver_settings_process.php` - Update driver settings

### Customer Endpoints

- `POST /carts/add_to_cart.php` - Add item to cart
- `POST /carts/remove_from_cart.php` - Remove item from cart
- `POST /checkout/checkout_process.php` - Process checkout
- `POST /orders/cancel_order.php` - Cancel order

## 📁 Project Structure

```
commissioned_app/
├── admin/                    # Admin management files
│   ├── add_product.php
│   ├── assign_driver.php
│   ├── verify_gcash_payment.php
│   └── ...
├── adminaccounts/           # Admin authentication
├── carts/                   # Shopping cart functionality
├── checkout/                # Checkout process
├── drivers/                 # Driver management
├── orders/                  # Order management
├── useraccounts/            # User authentication
├── includes/                # Helper functions
├── uploads/                 # File uploads
│   ├── deliveries/          # Delivery proof images
│   ├── pickups/             # Pickup proof images
│   ├── items/               # Product images
│   └── qr_codes/            # Payment QR codes
├── css/                     # Bootstrap CSS files
├── js/                      # JavaScript files
├── config.php               # Database configuration
├── database_setup.sql       # Database schema
├── index.php                # Landing page
├── dashboard.php            # Customer dashboard
├── driver_dashboard.php     # Driver dashboard
├── admin_dashboard.php      # Admin dashboard
└── README.md               # This file
```

## 📖 Usage Guide

### For Customers

1. **Register/Login** at the main page
2. **Browse Products** in the dashboard
3. **Add to Cart** desired items
4. **Checkout** with delivery address
5. **Choose Payment** method (COD or GCash)
6. **Track Order** in the orders section

### For Drivers

1. **Register** with vehicle and license details
2. **Login** to driver dashboard
3. **View Assigned Orders** in pickup tab
4. **Confirm Pickup** with photo proof
5. **Complete Delivery** with payment collection
6. **View History** of completed deliveries

### For Admins

1. **Login** with admin credentials
2. **Manage Products** - add, edit, delete items
3. **Verify Payments** - approve GCash transactions
4. **Assign Drivers** to orders
5. **Monitor Performance** through dashboard analytics

## 📸 Screenshots

### Customer Interface

- **Product Catalog** - Browse available chicken products
- **Shopping Cart** - Manage selected items
- **Order Tracking** - Real-time delivery status
- **Order History** - Complete delivery records

### Driver Interface

- **Pickup Management** - View assigned pickups
- **Delivery Tracking** - Manage ongoing deliveries
- **Payment Collection** - Smart change calculation
- **Delivery History** - Completed deliveries

### Admin Interface

- **Dashboard Analytics** - Sales and performance metrics
- **Product Management** - Inventory control
- **Order Management** - Process and assign orders
- **Driver Management** - Manage delivery network

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Add comments for complex logic
- Test all features before submitting
- Update documentation for new features

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:

- Create an issue in the GitHub repository
- Check the documentation in the `/docs` folder
- Review the code comments for implementation details

## 🎯 Roadmap

- [ ] Mobile app development
- [ ] Real-time notifications
- [ ] Advanced analytics dashboard
- [ ] Multi-language support
- [ ] API documentation with Swagger
- [ ] Automated testing suite
- [ ] Docker containerization

---

**Made with ❤️ for Triple JH Chicken Trading**

_Last updated: December 2024_
