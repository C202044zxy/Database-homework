-- ============================================
-- SummitSphere Database Schema
-- Version: 1.0
-- Description: Complete database schema for SummitSphere retail management system
-- ============================================

-- Create database
DROP DATABASE IF EXISTS summitsphere;
CREATE DATABASE summitsphere CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE summitsphere;

-- ============================================
-- Table: branch
-- Description: Physical store locations
-- ============================================
CREATE TABLE branch (
    branch_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20),
    email VARCHAR(100),
    opening_hour TIME DEFAULT '09:00:00',
    closing_hour TIME DEFAULT '21:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_branch_location (location),
    INDEX idx_branch_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- Table: employee
-- Description: Staff information with role differentiation
-- ============================================
CREATE TABLE employee (
    employee_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    date_of_birth DATE,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    role ENUM('Manager', 'Staff') NOT NULL DEFAULT 'Staff',
    hire_date DATE NOT NULL,
    salary DECIMAL(10, 2),
    id_card_number VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (branch_id) REFERENCES branch(branch_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_employee_branch (branch_id),
    INDEX idx_employee_role (role),
    INDEX idx_employee_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- Table: supplier
-- Description: Vendor information for supply chain
-- ============================================
CREATE TABLE supplier (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    contact_email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    cooperation_status ENUM('Active', 'Inactive', 'Pending', 'Terminated') DEFAULT 'Pending',
    contract_start_date DATE,
    contract_end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_supplier_status (cooperation_status)
) ENGINE=InnoDB;

-- ============================================
-- Table: customer
-- Description: Customer profiles with membership details
-- ============================================
CREATE TABLE customer (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    gender ENUM('Male', 'Female', 'Other'),
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    registration_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    membership_level ENUM('Bronze', 'Silver', 'Gold', 'Platinum') DEFAULT 'Bronze',
    total_spent DECIMAL(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_customer_membership (membership_level),
    INDEX idx_customer_email (email),
    INDEX idx_customer_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- Table: category
-- Description: Hierarchical product categories
-- ============================================
CREATE TABLE category (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_category_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (parent_category_id) REFERENCES category(category_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_category_parent (parent_category_id),
    INDEX idx_category_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- Table: product
-- Description: Items sold, linked to categories and suppliers
-- ============================================
CREATE TABLE product (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    supplier_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    sku VARCHAR(50) UNIQUE NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    cost_price DECIMAL(10, 2),
    weight DECIMAL(8, 2),
    dimensions VARCHAR(50),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_product_category (category_id),
    INDEX idx_product_supplier (supplier_id),
    INDEX idx_product_sku (sku),
    INDEX idx_product_active (is_active),
    INDEX idx_product_price (unit_price)
) ENGINE=InnoDB;

-- ============================================
-- Table: inventory
-- Description: Stock levels per branch per product
-- ============================================
CREATE TABLE inventory (
    inventory_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    max_stock_level INT DEFAULT 1000,
    last_restocked TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branch(branch_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uk_branch_product (branch_id, product_id),
    INDEX idx_inventory_quantity (quantity),
    INDEX idx_inventory_low_stock (quantity, min_stock_level)
) ENGINE=InnoDB;

-- ============================================
-- Table: customer_order
-- Description: Customer purchase orders
-- ============================================
CREATE TABLE customer_order (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    branch_id INT NOT NULL,
    employee_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded') DEFAULT 'Pending',
    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(10, 2) DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    shipping_address VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branch(branch_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_order_customer (customer_id),
    INDEX idx_order_branch (branch_id),
    INDEX idx_order_status (status),
    INDEX idx_order_date (order_date)
) ENGINE=InnoDB;

-- ============================================
-- Table: order_item
-- Description: Products within orders (many-to-many)
-- ============================================
CREATE TABLE order_item (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    discount_percent DECIMAL(5, 2) DEFAULT 0.00,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES customer_order(order_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_orderitem_order (order_id),
    INDEX idx_orderitem_product (product_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: payment
-- Description: Payment records linked to orders
-- ============================================
CREATE TABLE payment (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(12, 2) NOT NULL,
    payment_method ENUM('Cash', 'Credit Card', 'Debit Card', 'PayPal', 'Bank Transfer', 'Other') NOT NULL,
    status ENUM('Pending', 'Completed', 'Failed', 'Refunded') DEFAULT 'Pending',
    transaction_reference VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES customer_order(order_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_payment_order (order_id),
    INDEX idx_payment_status (status),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB;

-- ============================================
-- Table: shipment
-- Description: Supplier deliveries to branches
-- ============================================
CREATE TABLE shipment (
    shipment_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    branch_id INT NOT NULL,
    shipment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_arrival DATE,
    actual_arrival DATE,
    status ENUM('Pending', 'In Transit', 'Delivered', 'Cancelled', 'Delayed') DEFAULT 'Pending',
    tracking_number VARCHAR(100),
    total_cost DECIMAL(12, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branch(branch_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_shipment_supplier (supplier_id),
    INDEX idx_shipment_branch (branch_id),
    INDEX idx_shipment_status (status),
    INDEX idx_shipment_date (shipment_date)
) ENGINE=InnoDB;

-- ============================================
-- Table: shipment_item
-- Description: Products within shipments
-- ============================================
CREATE TABLE shipment_item (
    shipment_item_id INT PRIMARY KEY AUTO_INCREMENT,
    shipment_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipment(shipment_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_shipmentitem_shipment (shipment_id),
    INDEX idx_shipmentitem_product (product_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: review
-- Description: Customer product reviews
-- ============================================
CREATE TABLE review (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(150),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (order_id) REFERENCES customer_order(order_id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uk_customer_product_review (customer_id, product_id),
    INDEX idx_review_product (product_id),
    INDEX idx_review_rating (rating),
    INDEX idx_review_approved (is_approved)
) ENGINE=InnoDB;

-- ============================================
-- Table: user
-- Description: Authentication and authorization
-- ============================================
CREATE TABLE user (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Manager', 'Staff', 'Supplier', 'Customer') NOT NULL,
    employee_id INT,
    supplier_id INT,
    customer_id INT,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    is_locked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customer(customer_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_username (username),
    INDEX idx_user_role (role),
    INDEX idx_user_active (is_active)
) ENGINE=InnoDB;

-- ============================================
-- Table: audit_log
-- Description: Audit trail for sensitive operations
-- ============================================
CREATE TABLE audit_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_table (table_name),
    INDEX idx_audit_date (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Table: purchase_order
-- Description: Orders placed with suppliers
-- ============================================
CREATE TABLE purchase_order (
    purchase_order_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_id INT NOT NULL,
    branch_id INT NOT NULL,
    employee_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expected_delivery DATE,
    status ENUM('Draft', 'Submitted', 'Confirmed', 'Shipped', 'Received', 'Cancelled') DEFAULT 'Draft',
    total_amount DECIMAL(12, 2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES supplier(supplier_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branch(branch_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employee(employee_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_po_supplier (supplier_id),
    INDEX idx_po_branch (branch_id),
    INDEX idx_po_status (status),
    INDEX idx_po_date (order_date)
) ENGINE=InnoDB;

-- ============================================
-- Table: purchase_order_item
-- Description: Products within purchase orders
-- ============================================
CREATE TABLE purchase_order_item (
    po_item_id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_order(purchase_order_id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_poitem_po (purchase_order_id),
    INDEX idx_poitem_product (product_id)
) ENGINE=InnoDB;

-- Display success message
SELECT 'Database schema created successfully!' AS Status;
