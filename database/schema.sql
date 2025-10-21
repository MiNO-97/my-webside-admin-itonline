-- Database schema for POS IT Online System
-- Lao language support with UTF-8 encoding

CREATE DATABASE IF NOT EXISTS pos_itonline CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_itonline;

-- Product Categories Table
CREATE TABLE product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    image_url VARCHAR(500),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id)
);

-- Customers Table
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employees Table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    position VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refund_requested') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_phone VARCHAR(20),
    transfer_slip VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Order Items Table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Reviews Table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    product_id INT,
    order_id INT,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Purchase Orders (Admin ordering products into store)
CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255),
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES employees(id)
);

-- Purchase Order Items
CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id INT,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id)
);

-- Cart Table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    product_id INT,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_type ENUM('customer', 'employee') NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO product_categories (name, description) VALUES
('ຄອມພິວເຕີ', 'ຄອມພິວເຕີແລະອຸປະກອນທີ່ກ່ຽວຂ້ອງ'),
('ໂທລະສັບ', 'ໂທລະສັບມືຖືແລະອຸປະກອນທີ່ກ່ຽວຂ້ອງ'),
('ອຸປະກອນເຄືອຂ່າຍ', 'ອຸປະກອນເຄືອຂ່າຍແລະການເຊື່ອມຕໍ່'),
('ອຸປະກອນພິມ', 'ອຸປະກອນພິມແລະສະແກນ');

INSERT INTO products (category_id, name, description, price, stock_quantity) VALUES
(1, 'Laptop Dell Inspiron 15', 'ຄອມພິວເຕີພົກພາ Dell Inspiron 15 ຈໍ 15.6 ນິ້ວ', 2500000, 10),
(1, 'Desktop HP Pavilion', 'ຄອມພິວເຕີໂຕະງານ HP Pavilion', 1800000, 5),
(2, 'iPhone 15 Pro', 'ໂທລະສັບມືຖື iPhone 15 Pro 128GB', 4500000, 15),
(2, 'Samsung Galaxy S24', 'ໂທລະສັບມືຖື Samsung Galaxy S24 256GB', 3800000, 12),
(3, 'Router TP-Link Archer', 'ເຄືອຂ່າຍ WiFi Router TP-Link Archer C6', 450000, 20),
(4, 'Printer HP LaserJet', 'ອຸປະກອນພິມ HP LaserJet Pro M404n', 1200000, 8);

-- Insert sample admin employee
INSERT INTO employees (first_name, last_name, email, phone, position, password) VALUES
('ອະດີມິນ', 'ລະບົບ', 'admin@positonline.com', '02012345678', 'ຜູ້ບໍລິຫານລະບົບ', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample customer
INSERT INTO customers (first_name, last_name, email, phone, password) VALUES
('ສົມສະໄໝ', 'ວົງສະດີ', 'som@example.com', '02012345679', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample reviews
INSERT INTO reviews (customer_id, product_id, rating, comment, status) VALUES
(1, 1, 5, 'ຄອມພິວເຕີດີເລີຍ ໃຊ້ງານງ່າຍ ຄຸນນະພາບດີ', 'approved'),
(1, 2, 4, 'ຄອມພິວເຕີໂຕະງານດີ ແຕ່ລາຄາຄ່ອນຂ້ອນສູງ', 'approved'),
(1, 3, 5, 'iPhone ຄຸນນະພາບດີຫຼາຍ ກ້ອງຖ່າຍຮູບຊັດເຈນ', 'approved'),
(1, 4, 3, 'Samsung ດີ ແຕ່ບາດເຈັບງ່າຍ', 'pending'),
(1, 5, 4, 'Router ຄວາມໄວດີ ເຊື່ອມຕໍ່ງ່າຍ', 'approved'); 