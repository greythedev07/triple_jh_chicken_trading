-- Triple JH Chicken Trading Database Setup
-- Run this script to create all required tables and indexes
-- Version: 2.0 - Updated with all recent improvements

CREATE DATABASE IF NOT EXISTS commissioned_app_database;
USE commissioned_app_database;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phonenumber VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    barangay VARCHAR(100),
    city VARCHAR(100),
    zipcode VARCHAR(20),
    landmark VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin keys table (for admin registration)
CREATE TABLE IF NOT EXISTS admin_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_key VARCHAR(255) UNIQUE NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Drivers table
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    vehicle_type VARCHAR(50),
    license_no VARCHAR(100),
    address TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    date_joined TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Pending delivery table
CREATE TABLE IF NOT EXISTS pending_delivery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NULL,
    user_id INT NOT NULL,
    driver_id INT NULL,
    payment_method VARCHAR(50) DEFAULT 'COD',
    payment_status ENUM('pending', 'verified', 'failed') DEFAULT 'pending',
    gcash_reference VARCHAR(100) NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'pending',
    delivery_address TEXT NOT NULL,
    landmark VARCHAR(255),
    total_amount DECIMAL(10,2) NOT NULL,
    date_requested TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
);

-- Pending delivery items table
CREATE TABLE IF NOT EXISTS pending_delivery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pending_delivery_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pending_delivery_id) REFERENCES pending_delivery(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- To be delivered table
CREATE TABLE IF NOT EXISTS to_be_delivered (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pending_delivery_id INT NOT NULL,
    driver_id INT NOT NULL,
    user_id INT NOT NULL,
    delivery_address TEXT NOT NULL,
    pickup_proof VARCHAR(500),
    pickup_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(32) DEFAULT 'picked_up',
    FOREIGN KEY (pending_delivery_id) REFERENCES pending_delivery(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- To be delivered items table
CREATE TABLE IF NOT EXISTS to_be_delivered_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_be_delivered_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (to_be_delivered_id) REFERENCES to_be_delivered(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- History of delivery table
CREATE TABLE IF NOT EXISTS history_of_delivery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_be_delivered_id INT NOT NULL,
    driver_id INT NOT NULL,
    user_id INT NOT NULL,
    order_number VARCHAR(20),
    payment_method VARCHAR(50),
    delivery_address TEXT NOT NULL,
    payment_received DECIMAL(10,2),
    change_given DECIMAL(10,2),
    delivery_time TIMESTAMP,
    proof_image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (to_be_delivered_id) REFERENCES to_be_delivered(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- History of delivery items table
CREATE TABLE IF NOT EXISTS history_of_delivery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    history_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (history_id) REFERENCES history_of_delivery(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- GCash QR codes table for storing QR code information
CREATE TABLE IF NOT EXISTS gcash_qr_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qr_code_url VARCHAR(500) NOT NULL,
    qr_code_image VARCHAR(500) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample admin key
INSERT INTO admin_keys (admin_key) VALUES ('80085') ON DUPLICATE KEY UPDATE admin_key = admin_key;

-- Insert sample products
INSERT INTO products (name, price, stock, image) VALUES 
('Whole Chicken', 180.00, 50, NULL),
('Chicken Wings', 120.00, 30, NULL),
('Chicken Breasts', 150.00, 25, NULL)
ON DUPLICATE KEY UPDATE name = name;

-- Insert sample GCash QR code entry
INSERT INTO gcash_qr_codes (qr_code_url, qr_code_image, amount, is_active) 
VALUES ('https://qr.gcash.com/sample', 'uploads/qr_codes/gcash_qr_sample.png', 0.00, TRUE)
ON DUPLICATE KEY UPDATE qr_code_url = qr_code_url;

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_admins_username ON admins(username);
CREATE INDEX idx_drivers_email ON drivers(email);
CREATE INDEX idx_pending_delivery_user ON pending_delivery(user_id);
CREATE INDEX idx_pending_delivery_driver ON pending_delivery(driver_id);
CREATE INDEX idx_pending_delivery_status ON pending_delivery(status);
CREATE INDEX idx_pending_delivery_order_number ON pending_delivery(order_number);
CREATE INDEX idx_pending_delivery_gcash_ref ON pending_delivery(gcash_reference);
CREATE INDEX idx_pending_delivery_payment_status ON pending_delivery(payment_status);
CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_cart_product ON cart(product_id);
CREATE INDEX idx_history_delivery_user ON history_of_delivery(user_id);
CREATE INDEX idx_history_delivery_driver ON history_of_delivery(driver_id);
CREATE INDEX idx_history_delivery_order_number ON history_of_delivery(order_number);
CREATE INDEX idx_history_delivery_payment_method ON history_of_delivery(payment_method);
CREATE INDEX idx_to_be_delivered_driver ON to_be_delivered(driver_id);
CREATE INDEX idx_to_be_delivered_user ON to_be_delivered(user_id);
CREATE INDEX idx_to_be_delivered_status ON to_be_delivered(status);


-- ========================================
-- ORDER STATUS FLOW
-- ========================================

-- pending_delivery.status values:
-- - 'pending': Order placed, waiting for driver assignment
-- - 'assigned': Driver assigned, ready for pickup
-- - 'picked_up': Driver has picked up the order
-- - 'delivered': Order completed and delivered
-- - 'cancelled': Order was cancelled

-- to_be_delivered.status values:
-- - 'picked_up': Order picked up by driver
-- - 'delivered': Order completed and delivered

-- ========================================
-- DATABASE STRUCTURE OVERVIEW
-- ========================================

-- Core Tables:
-- 1. users - Customer information and accounts
-- 2. admins - Admin user accounts with special privileges
-- 3. drivers - Driver accounts and vehicle information
-- 4. products - Product catalog with pricing and stock
-- 5. cart - Shopping cart items for users

-- Order Management Tables:
-- 6. pending_delivery - Orders waiting for driver assignment/pickup
-- 7. pending_delivery_items - Items in pending orders
-- 8. to_be_delivered - Orders picked up and ready for delivery
-- 9. to_be_delivered_items - Items in active deliveries
-- 10. history_of_delivery - Completed delivery records
-- 11. history_of_delivery_items - Items in completed deliveries

-- Payment Tables:
-- 12. gcash_qr_codes - GCash QR code information for payments
-- 13. admin_keys - Admin registration keys

-- ========================================
-- INSTALLATION INSTRUCTIONS
-- ========================================

-- 1. Run this script in your MySQL/MariaDB database
-- 2. Update config.php with your database credentials
-- 3. Set up file upload directories (uploads/products/, uploads/deliveries/, etc.)
-- 4. Configure admin account using the provided admin key (80085)
-- 5. Add your GCash QR code information
-- 6. Test the system with sample data
