-- database/schema.sql

-- Drop tables if they exist to allow for clean re-creation during development
DROP TABLE IF EXISTS deliveries;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS users;

-- Table for Users (Customers, Admins, Delivery Drivers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Hashed password
    phone_number VARCHAR(20) UNIQUE,
    address TEXT,
    role ENUM('customer', 'admin', 'driver') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'inactive', 'on-duty', 'off-duty') NOT NULL DEFAULT 'active', -- For drivers, admins
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for Restaurants
CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    address VARCHAR(255),
    phone_number VARCHAR(20),
    email VARCHAR(100),
    image_url VARCHAR(255), -- Optional: URL to restaurant logo/image
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table for Menu Items
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255), -- Optional: URL to food item image
    category VARCHAR(50), -- e.g., 'Burgers', 'Pizzas', 'Drinks'
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Table for Orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Customer who placed the order
    restaurant_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_address TEXT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'confirmed', 'assigned', 'picked_up', 'on_the_way', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    checkout_request_id VARCHAR(255) UNIQUE, -- From M-Pesa STK Push
    driver_id INT, -- NULL initially, assigned later
    delivery_notes TEXT,
    expected_delivery_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL -- If driver account is deleted, set to NULL
);

-- Table for Items within an Order
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_order DECIMAL(10, 2) NOT NULL, -- Price at the time of order
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- Table for Payments (to record M-Pesa transactions)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    mpesa_receipt_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    transaction_date DATETIME NOT NULL,
    checkout_request_id VARCHAR(255) UNIQUE, -- Linking back to STK Push
    status VARCHAR(50), -- e.g., 'Completed', 'Failed', 'Cancelled'
    raw_response TEXT, -- Store raw M-Pesa callback for debugging
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Table for Deliveries (can be linked to Google Maps tracking)
CREATE TABLE deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL UNIQUE, -- Each order has one delivery record
    driver_id INT NOT NULL,
    current_latitude DECIMAL(10, 8), -- Driver's current location (for tracking)
    current_longitude DECIMAL(11, 8),
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    picked_up_at DATETIME,
    delivered_at DATETIME,
    status ENUM('assigned', 'picked_up', 'on_the_way', 'delivered', 'failed') NOT NULL DEFAULT 'assigned',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
);