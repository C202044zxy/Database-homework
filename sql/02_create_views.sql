-- ============================================
-- SummitSphere Role-Based Views
-- Version: 1.0
-- Description: Views for access control - users access data through views, not direct tables
-- ============================================

USE summitsphere;

-- ============================================
-- MANAGER VIEWS (Full Access)
-- ============================================

-- Manager: Complete branch overview with statistics
DROP VIEW IF EXISTS view_manager_branches;
CREATE VIEW view_manager_branches AS
SELECT
    b.branch_id,
    b.name AS branch_name,
    b.location,
    b.contact_phone,
    b.email,
    b.opening_hour,
    b.closing_hour,
    b.is_active,
    COUNT(DISTINCT e.employee_id) AS employee_count,
    COUNT(DISTINCT CASE WHEN e.role = 'Manager' THEN e.employee_id END) AS manager_count,
    COUNT(DISTINCT CASE WHEN e.role = 'Staff' THEN e.employee_id END) AS staff_count,
    COALESCE(SUM(i.quantity), 0) AS total_inventory
FROM branch b
LEFT JOIN employee e ON b.branch_id = e.branch_id AND e.is_active = TRUE
LEFT JOIN inventory i ON b.branch_id = i.branch_id
GROUP BY b.branch_id;

-- Manager: Complete employee list with sensitive data
DROP VIEW IF EXISTS view_manager_employees;
CREATE VIEW view_manager_employees AS
SELECT
    e.employee_id,
    e.first_name,
    e.last_name,
    CONCAT(e.first_name, ' ', e.last_name) AS full_name,
    e.gender,
    e.date_of_birth,
    TIMESTAMPDIFF(YEAR, e.date_of_birth, CURDATE()) AS age,
    e.email,
    e.phone,
    e.address,
    e.role,
    e.hire_date,
    e.salary,
    e.id_card_number,
    e.is_active,
    b.name AS branch_name,
    b.location AS branch_location
FROM employee e
JOIN branch b ON e.branch_id = b.branch_id;

-- Manager: Supplier overview with cooperation status
DROP VIEW IF EXISTS view_manager_suppliers;
CREATE VIEW view_manager_suppliers AS
SELECT
    s.supplier_id,
    s.name AS supplier_name,
    s.contact_person,
    s.contact_email,
    s.phone,
    s.address,
    s.cooperation_status,
    s.contract_start_date,
    s.contract_end_date,
    COUNT(DISTINCT p.product_id) AS products_supplied,
    COUNT(DISTINCT po.purchase_order_id) AS total_orders,
    COALESCE(SUM(po.total_amount), 0) AS total_order_value
FROM supplier s
LEFT JOIN product p ON s.supplier_id = p.supplier_id
LEFT JOIN purchase_order po ON s.supplier_id = po.supplier_id
GROUP BY s.supplier_id;

-- Manager: Complete customer list with spending data
DROP VIEW IF EXISTS view_manager_customers;
CREATE VIEW view_manager_customers AS
SELECT
    c.customer_id,
    c.first_name,
    c.last_name,
    CONCAT(c.first_name, ' ', c.last_name) AS full_name,
    c.gender,
    c.email,
    c.phone,
    c.address,
    c.registration_date,
    c.membership_level,
    c.total_spent,
    c.is_active,
    COUNT(DISTINCT co.order_id) AS total_orders,
    COUNT(DISTINCT r.review_id) AS reviews_written
FROM customer c
LEFT JOIN customer_order co ON c.customer_id = co.customer_id
LEFT JOIN review r ON c.customer_id = r.customer_id
GROUP BY c.customer_id;

-- Manager: Sales dashboard - orders with full details
DROP VIEW IF EXISTS view_manager_orders;
CREATE VIEW view_manager_orders AS
SELECT
    co.order_id,
    co.order_date,
    co.status,
    co.subtotal,
    co.tax_amount,
    co.discount_amount,
    co.total_amount,
    co.shipping_address,
    co.notes,
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.email AS customer_email,
    c.membership_level,
    b.branch_id,
    b.name AS branch_name,
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS processed_by,
    p.payment_id,
    p.payment_method,
    p.status AS payment_status
FROM customer_order co
JOIN customer c ON co.customer_id = c.customer_id
JOIN branch b ON co.branch_id = b.branch_id
LEFT JOIN employee e ON co.employee_id = e.employee_id
LEFT JOIN payment p ON co.order_id = p.order_id;

-- Manager: Inventory overview across all branches
DROP VIEW IF EXISTS view_manager_inventory;
CREATE VIEW view_manager_inventory AS
SELECT
    i.inventory_id,
    b.branch_id,
    b.name AS branch_name,
    p.product_id,
    p.name AS product_name,
    p.sku,
    cat.name AS category_name,
    s.name AS supplier_name,
    i.quantity,
    i.min_stock_level,
    i.max_stock_level,
    i.last_restocked,
    p.unit_price,
    p.cost_price,
    (i.quantity * p.unit_price) AS inventory_value,
    CASE
        WHEN i.quantity <= i.min_stock_level THEN 'Low Stock'
        WHEN i.quantity >= i.max_stock_level THEN 'Overstocked'
        ELSE 'Normal'
    END AS stock_status
FROM inventory i
JOIN branch b ON i.branch_id = b.branch_id
JOIN product p ON i.product_id = p.product_id
JOIN category cat ON p.category_id = cat.category_id
JOIN supplier s ON p.supplier_id = s.supplier_id;

-- Manager: Low stock alerts
DROP VIEW IF EXISTS view_manager_low_stock_alerts;
CREATE VIEW view_manager_low_stock_alerts AS
SELECT
    i.inventory_id,
    b.name AS branch_name,
    b.location AS branch_location,
    p.product_id,
    p.name AS product_name,
    p.sku,
    s.name AS supplier_name,
    s.contact_email AS supplier_email,
    i.quantity AS current_stock,
    i.min_stock_level,
    (i.min_stock_level - i.quantity) AS units_needed
FROM inventory i
JOIN branch b ON i.branch_id = b.branch_id
JOIN product p ON i.product_id = p.product_id
JOIN supplier s ON p.supplier_id = s.supplier_id
WHERE i.quantity <= i.min_stock_level
ORDER BY (i.min_stock_level - i.quantity) DESC;

-- Manager: Sales analytics by branch
DROP VIEW IF EXISTS view_manager_sales_by_branch;
CREATE VIEW view_manager_sales_by_branch AS
SELECT
    b.branch_id,
    b.name AS branch_name,
    b.location,
    COUNT(co.order_id) AS total_orders,
    COALESCE(SUM(co.total_amount), 0) AS total_revenue,
    COALESCE(AVG(co.total_amount), 0) AS avg_order_value,
    COUNT(DISTINCT co.customer_id) AS unique_customers
FROM branch b
LEFT JOIN customer_order co ON b.branch_id = co.branch_id AND co.status != 'Cancelled'
GROUP BY b.branch_id;

-- ============================================
-- STAFF VIEWS (Branch-Specific Access)
-- ============================================

-- Staff: Products catalog (no cost prices)
DROP VIEW IF EXISTS view_staff_products;
CREATE VIEW view_staff_products AS
SELECT
    p.product_id,
    p.name AS product_name,
    p.description,
    p.sku,
    p.unit_price,
    p.weight,
    p.dimensions,
    p.image_url,
    p.is_active,
    cat.category_id,
    cat.name AS category_name,
    s.name AS supplier_name
FROM product p
JOIN category cat ON p.category_id = cat.category_id
JOIN supplier s ON p.supplier_id = s.supplier_id
WHERE p.is_active = TRUE;

-- Staff: Branch inventory (will be filtered by branch_id in application)
DROP VIEW IF EXISTS view_staff_inventory;
CREATE VIEW view_staff_inventory AS
SELECT
    i.inventory_id,
    i.branch_id,
    p.product_id,
    p.name AS product_name,
    p.sku,
    p.unit_price,
    cat.name AS category_name,
    i.quantity,
    i.min_stock_level,
    CASE
        WHEN i.quantity <= i.min_stock_level THEN 'Low Stock'
        WHEN i.quantity <= 0 THEN 'Out of Stock'
        ELSE 'In Stock'
    END AS availability
FROM inventory i
JOIN product p ON i.product_id = p.product_id
JOIN category cat ON p.category_id = cat.category_id
WHERE p.is_active = TRUE;

-- Staff: Orders at their branch (will be filtered by branch_id in application)
DROP VIEW IF EXISTS view_staff_orders;
CREATE VIEW view_staff_orders AS
SELECT
    co.order_id,
    co.branch_id,
    co.order_date,
    co.status,
    co.total_amount,
    co.shipping_address,
    co.notes,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.phone AS customer_phone,
    c.email AS customer_email,
    p.payment_method,
    p.status AS payment_status
FROM customer_order co
JOIN customer c ON co.customer_id = c.customer_id
LEFT JOIN payment p ON co.order_id = p.order_id;

-- Staff: Order items for processing
DROP VIEW IF EXISTS view_staff_order_items;
CREATE VIEW view_staff_order_items AS
SELECT
    oi.order_item_id,
    oi.order_id,
    co.branch_id,
    p.product_id,
    p.name AS product_name,
    p.sku,
    oi.quantity,
    oi.unit_price,
    oi.discount_percent,
    oi.subtotal
FROM order_item oi
JOIN customer_order co ON oi.order_id = co.order_id
JOIN product p ON oi.product_id = p.product_id;

-- ============================================
-- SUPPLIER VIEWS (Own Data Only)
-- ============================================

-- Supplier: Their products
DROP VIEW IF EXISTS view_supplier_products;
CREATE VIEW view_supplier_products AS
SELECT
    p.product_id,
    p.supplier_id,
    p.name AS product_name,
    p.description,
    p.sku,
    p.unit_price,
    p.cost_price,
    cat.name AS category_name,
    p.is_active,
    COALESCE(SUM(i.quantity), 0) AS total_stock_across_branches
FROM product p
JOIN category cat ON p.category_id = cat.category_id
LEFT JOIN inventory i ON p.product_id = i.product_id
GROUP BY p.product_id;

-- Supplier: Purchase orders addressed to them
DROP VIEW IF EXISTS view_supplier_purchase_orders;
CREATE VIEW view_supplier_purchase_orders AS
SELECT
    po.purchase_order_id,
    po.supplier_id,
    po.order_date,
    po.expected_delivery,
    po.status,
    po.total_amount,
    po.notes,
    b.name AS branch_name,
    b.location AS branch_location,
    b.contact_phone AS branch_phone
FROM purchase_order po
JOIN branch b ON po.branch_id = b.branch_id;

-- Supplier: Purchase order items
DROP VIEW IF EXISTS view_supplier_po_items;
CREATE VIEW view_supplier_po_items AS
SELECT
    poi.po_item_id,
    poi.purchase_order_id,
    po.supplier_id,
    p.product_id,
    p.name AS product_name,
    p.sku,
    poi.quantity,
    poi.unit_cost,
    poi.subtotal
FROM purchase_order_item poi
JOIN purchase_order po ON poi.purchase_order_id = po.purchase_order_id
JOIN product p ON poi.product_id = p.product_id;

-- Supplier: Their shipments
DROP VIEW IF EXISTS view_supplier_shipments;
CREATE VIEW view_supplier_shipments AS
SELECT
    sh.shipment_id,
    sh.supplier_id,
    sh.shipment_date,
    sh.expected_arrival,
    sh.actual_arrival,
    sh.status,
    sh.tracking_number,
    sh.total_cost,
    sh.notes,
    b.name AS branch_name,
    b.location AS branch_location,
    b.contact_phone AS branch_phone
FROM shipment sh
JOIN branch b ON sh.branch_id = b.branch_id;

-- ============================================
-- CUSTOMER VIEWS (Own Data Only)
-- ============================================

-- Customer: Product catalog for browsing (public)
DROP VIEW IF EXISTS view_customer_products;
CREATE VIEW view_customer_products AS
SELECT
    p.product_id,
    p.name AS product_name,
    p.description,
    p.sku,
    p.unit_price,
    p.weight,
    p.dimensions,
    p.image_url,
    cat.category_id,
    cat.name AS category_name,
    pcat.name AS parent_category_name,
    COALESCE(AVG(r.rating), 0) AS avg_rating,
    COUNT(r.review_id) AS review_count
FROM product p
JOIN category cat ON p.category_id = cat.category_id
LEFT JOIN category pcat ON cat.parent_category_id = pcat.category_id
LEFT JOIN review r ON p.product_id = r.product_id AND r.is_approved = TRUE
WHERE p.is_active = TRUE
GROUP BY p.product_id;

-- Customer: Category hierarchy for navigation
DROP VIEW IF EXISTS view_customer_categories;
CREATE VIEW view_customer_categories AS
SELECT
    c.category_id,
    c.name AS category_name,
    c.description,
    c.parent_category_id,
    pc.name AS parent_category_name,
    COUNT(p.product_id) AS product_count
FROM category c
LEFT JOIN category pc ON c.parent_category_id = pc.category_id
LEFT JOIN product p ON c.category_id = p.category_id AND p.is_active = TRUE
WHERE c.is_active = TRUE
GROUP BY c.category_id;

-- Customer: Their orders
DROP VIEW IF EXISTS view_customer_orders;
CREATE VIEW view_customer_orders AS
SELECT
    co.order_id,
    co.customer_id,
    co.order_date,
    co.status,
    co.subtotal,
    co.tax_amount,
    co.discount_amount,
    co.total_amount,
    co.shipping_address,
    b.name AS branch_name,
    p.payment_method,
    p.status AS payment_status
FROM customer_order co
JOIN branch b ON co.branch_id = b.branch_id
LEFT JOIN payment p ON co.order_id = p.order_id;

-- Customer: Their order items
DROP VIEW IF EXISTS view_customer_order_items;
CREATE VIEW view_customer_order_items AS
SELECT
    oi.order_item_id,
    oi.order_id,
    co.customer_id,
    p.product_id,
    p.name AS product_name,
    p.image_url,
    oi.quantity,
    oi.unit_price,
    oi.discount_percent,
    oi.subtotal
FROM order_item oi
JOIN customer_order co ON oi.order_id = co.order_id
JOIN product p ON oi.product_id = p.product_id;

-- Customer: Their reviews
DROP VIEW IF EXISTS view_customer_reviews;
CREATE VIEW view_customer_reviews AS
SELECT
    r.review_id,
    r.customer_id,
    r.product_id,
    p.name AS product_name,
    p.image_url AS product_image,
    r.rating,
    r.title,
    r.comment,
    r.is_verified_purchase,
    r.is_approved,
    r.created_at
FROM review r
JOIN product p ON r.product_id = p.product_id;

-- Customer: Product reviews (public - for viewing on product pages)
DROP VIEW IF EXISTS view_product_reviews;
CREATE VIEW view_product_reviews AS
SELECT
    r.review_id,
    r.product_id,
    r.rating,
    r.title,
    r.comment,
    r.is_verified_purchase,
    r.created_at,
    CONCAT(LEFT(c.first_name, 1), '***') AS reviewer_name
FROM review r
JOIN customer c ON r.customer_id = c.customer_id
WHERE r.is_approved = TRUE;

-- Customer: Branch locations for store selection
DROP VIEW IF EXISTS view_customer_branches;
CREATE VIEW view_customer_branches AS
SELECT
    branch_id,
    name AS branch_name,
    location,
    contact_phone,
    email,
    opening_hour,
    closing_hour
FROM branch
WHERE is_active = TRUE;

-- ============================================
-- SHARED VIEWS
-- ============================================

-- Product availability across branches
DROP VIEW IF EXISTS view_product_availability;
CREATE VIEW view_product_availability AS
SELECT
    p.product_id,
    p.name AS product_name,
    b.branch_id,
    b.name AS branch_name,
    b.location,
    i.quantity,
    CASE
        WHEN i.quantity > 10 THEN 'In Stock'
        WHEN i.quantity > 0 THEN 'Low Stock'
        ELSE 'Out of Stock'
    END AS availability
FROM product p
CROSS JOIN branch b
LEFT JOIN inventory i ON p.product_id = i.product_id AND b.branch_id = i.branch_id
WHERE p.is_active = TRUE AND b.is_active = TRUE;

SELECT 'Views created successfully!' AS Status;
