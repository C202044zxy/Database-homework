-- ============================================
-- SummitSphere Stored Procedures and Triggers
-- Version: 1.0
-- Description: Business logic, automation, and data integrity
-- ============================================

USE summitsphere;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- --------------------------------------------
-- Procedure: Create a new customer order
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_create_order;
DELIMITER //
CREATE PROCEDURE sp_create_order(
    IN p_customer_id INT,
    IN p_branch_id INT,
    IN p_employee_id INT,
    IN p_shipping_address VARCHAR(255),
    IN p_notes TEXT,
    OUT p_order_id INT
)
BEGIN
    DECLARE v_customer_exists INT;
    DECLARE v_branch_exists INT;

    -- Validate customer exists and is active
    SELECT COUNT(*) INTO v_customer_exists
    FROM customer WHERE customer_id = p_customer_id AND is_active = TRUE;

    IF v_customer_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid or inactive customer';
    END IF;

    -- Validate branch exists and is active
    SELECT COUNT(*) INTO v_branch_exists
    FROM branch WHERE branch_id = p_branch_id AND is_active = TRUE;

    IF v_branch_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid or inactive branch';
    END IF;

    -- Create the order
    INSERT INTO customer_order (customer_id, branch_id, employee_id, shipping_address, notes, status)
    VALUES (p_customer_id, p_branch_id, p_employee_id, p_shipping_address, p_notes, 'Pending');

    SET p_order_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Add item to order
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_add_order_item;
DELIMITER //
CREATE PROCEDURE sp_add_order_item(
    IN p_order_id INT,
    IN p_product_id INT,
    IN p_quantity INT,
    IN p_discount_percent DECIMAL(5,2)
)
BEGIN
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_branch_id INT;
    DECLARE v_available_qty INT;
    DECLARE v_order_status VARCHAR(20);

    -- Check order exists and is not completed
    SELECT status, branch_id INTO v_order_status, v_branch_id
    FROM customer_order WHERE order_id = p_order_id;

    IF v_order_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Order not found';
    END IF;

    IF v_order_status NOT IN ('Pending', 'Processing') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot modify order in current status';
    END IF;

    -- Get product price
    SELECT unit_price INTO v_unit_price
    FROM product WHERE product_id = p_product_id AND is_active = TRUE;

    IF v_unit_price IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Product not found or inactive';
    END IF;

    -- Check inventory availability
    SELECT quantity INTO v_available_qty
    FROM inventory WHERE branch_id = v_branch_id AND product_id = p_product_id;

    IF v_available_qty IS NULL OR v_available_qty < p_quantity THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient inventory';
    END IF;

    -- Calculate subtotal
    SET v_subtotal = p_quantity * v_unit_price * (1 - COALESCE(p_discount_percent, 0) / 100);

    -- Insert order item
    INSERT INTO order_item (order_id, product_id, quantity, unit_price, discount_percent, subtotal)
    VALUES (p_order_id, p_product_id, p_quantity, v_unit_price, COALESCE(p_discount_percent, 0), v_subtotal);

    -- Update order totals
    CALL sp_recalculate_order_totals(p_order_id);
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Recalculate order totals
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_recalculate_order_totals;
DELIMITER //
CREATE PROCEDURE sp_recalculate_order_totals(
    IN p_order_id INT
)
BEGIN
    DECLARE v_subtotal DECIMAL(12,2);
    DECLARE v_tax_rate DECIMAL(5,2) DEFAULT 0.10; -- 10% tax
    DECLARE v_tax_amount DECIMAL(10,2);
    DECLARE v_total DECIMAL(12,2);
    DECLARE v_discount DECIMAL(10,2);
    DECLARE v_membership VARCHAR(20);
    DECLARE v_customer_id INT;

    -- Get customer membership for discount
    SELECT co.customer_id, c.membership_level INTO v_customer_id, v_membership
    FROM customer_order co
    JOIN customer c ON co.customer_id = c.customer_id
    WHERE co.order_id = p_order_id;

    -- Calculate subtotal from order items
    SELECT COALESCE(SUM(subtotal), 0) INTO v_subtotal
    FROM order_item WHERE order_id = p_order_id;

    -- Apply membership discount
    SET v_discount = CASE v_membership
        WHEN 'Bronze' THEN v_subtotal * 0.00
        WHEN 'Silver' THEN v_subtotal * 0.05
        WHEN 'Gold' THEN v_subtotal * 0.10
        WHEN 'Platinum' THEN v_subtotal * 0.15
        ELSE 0
    END;

    -- Calculate tax and total
    SET v_tax_amount = (v_subtotal - v_discount) * v_tax_rate;
    SET v_total = v_subtotal - v_discount + v_tax_amount;

    -- Update order
    UPDATE customer_order
    SET subtotal = v_subtotal,
        discount_amount = v_discount,
        tax_amount = v_tax_amount,
        total_amount = v_total
    WHERE order_id = p_order_id;
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Process payment
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_process_payment;
DELIMITER //
CREATE PROCEDURE sp_process_payment(
    IN p_order_id INT,
    IN p_payment_method VARCHAR(20),
    IN p_transaction_reference VARCHAR(100),
    OUT p_payment_id INT
)
BEGIN
    DECLARE v_order_total DECIMAL(12,2);
    DECLARE v_order_status VARCHAR(20);
    DECLARE v_existing_payment INT;

    -- Check order exists
    SELECT total_amount, status INTO v_order_total, v_order_status
    FROM customer_order WHERE order_id = p_order_id;

    IF v_order_total IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Order not found';
    END IF;

    -- Check if already paid
    SELECT COUNT(*) INTO v_existing_payment
    FROM payment WHERE order_id = p_order_id AND status = 'Completed';

    IF v_existing_payment > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Order already paid';
    END IF;

    -- Create payment record
    INSERT INTO payment (order_id, amount, payment_method, status, transaction_reference)
    VALUES (p_order_id, v_order_total, p_payment_method, 'Completed', p_transaction_reference);

    SET p_payment_id = LAST_INSERT_ID();

    -- Update order status
    UPDATE customer_order SET status = 'Processing' WHERE order_id = p_order_id;
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Update membership level based on spending
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_update_membership_level;
DELIMITER //
CREATE PROCEDURE sp_update_membership_level(
    IN p_customer_id INT
)
BEGIN
    DECLARE v_total_spent DECIMAL(12,2);
    DECLARE v_new_level VARCHAR(20);

    -- Calculate total spent from completed orders
    SELECT COALESCE(SUM(co.total_amount), 0) INTO v_total_spent
    FROM customer_order co
    JOIN payment p ON co.order_id = p.order_id
    WHERE co.customer_id = p_customer_id AND p.status = 'Completed';

    -- Determine membership level
    SET v_new_level = CASE
        WHEN v_total_spent >= 10000 THEN 'Platinum'
        WHEN v_total_spent >= 5000 THEN 'Gold'
        WHEN v_total_spent >= 1000 THEN 'Silver'
        ELSE 'Bronze'
    END;

    -- Update customer
    UPDATE customer
    SET membership_level = v_new_level, total_spent = v_total_spent
    WHERE customer_id = p_customer_id;
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Create purchase order for supplier
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_create_purchase_order;
DELIMITER //
CREATE PROCEDURE sp_create_purchase_order(
    IN p_supplier_id INT,
    IN p_branch_id INT,
    IN p_employee_id INT,
    IN p_expected_delivery DATE,
    IN p_notes TEXT,
    OUT p_po_id INT
)
BEGIN
    DECLARE v_supplier_status VARCHAR(20);

    -- Check supplier is active
    SELECT cooperation_status INTO v_supplier_status
    FROM supplier WHERE supplier_id = p_supplier_id;

    IF v_supplier_status != 'Active' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Supplier is not active';
    END IF;

    INSERT INTO purchase_order (supplier_id, branch_id, employee_id, expected_delivery, notes, status)
    VALUES (p_supplier_id, p_branch_id, p_employee_id, p_expected_delivery, p_notes, 'Draft');

    SET p_po_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Update shipment status
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_update_shipment_status;
DELIMITER //
CREATE PROCEDURE sp_update_shipment_status(
    IN p_shipment_id INT,
    IN p_status VARCHAR(20),
    IN p_tracking_number VARCHAR(100)
)
BEGIN
    DECLARE v_current_status VARCHAR(20);

    SELECT status INTO v_current_status
    FROM shipment WHERE shipment_id = p_shipment_id;

    IF v_current_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Shipment not found';
    END IF;

    -- Update shipment
    UPDATE shipment
    SET status = p_status,
        tracking_number = COALESCE(p_tracking_number, tracking_number),
        actual_arrival = CASE WHEN p_status = 'Delivered' THEN CURRENT_DATE ELSE actual_arrival END
    WHERE shipment_id = p_shipment_id;

    -- If delivered, update inventory
    IF p_status = 'Delivered' THEN
        CALL sp_receive_shipment(p_shipment_id);
    END IF;
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Receive shipment and update inventory
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_receive_shipment;
DELIMITER //
CREATE PROCEDURE sp_receive_shipment(
    IN p_shipment_id INT
)
BEGIN
    DECLARE v_branch_id INT;
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;

    DECLARE cur_items CURSOR FOR
        SELECT product_id, quantity FROM shipment_item WHERE shipment_id = p_shipment_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    SELECT branch_id INTO v_branch_id FROM shipment WHERE shipment_id = p_shipment_id;

    OPEN cur_items;

    read_loop: LOOP
        FETCH cur_items INTO v_product_id, v_quantity;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Update or insert inventory
        INSERT INTO inventory (branch_id, product_id, quantity, last_restocked)
        VALUES (v_branch_id, v_product_id, v_quantity, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            quantity = quantity + v_quantity,
            last_restocked = CURRENT_TIMESTAMP;
    END LOOP;

    CLOSE cur_items;
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Get low stock items for a branch
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_get_low_stock_items;
DELIMITER //
CREATE PROCEDURE sp_get_low_stock_items(
    IN p_branch_id INT
)
BEGIN
    SELECT
        i.inventory_id,
        p.product_id,
        p.name AS product_name,
        p.sku,
        s.name AS supplier_name,
        s.contact_email AS supplier_email,
        i.quantity AS current_stock,
        i.min_stock_level,
        (i.min_stock_level - i.quantity) AS reorder_quantity
    FROM inventory i
    JOIN product p ON i.product_id = p.product_id
    JOIN supplier s ON p.supplier_id = s.supplier_id
    WHERE i.branch_id = p_branch_id
        AND i.quantity <= i.min_stock_level
    ORDER BY (i.min_stock_level - i.quantity) DESC;
END //
DELIMITER ;

-- --------------------------------------------
-- Procedure: Generate sales report by date range
-- --------------------------------------------
DROP PROCEDURE IF EXISTS sp_sales_report;
DELIMITER //
CREATE PROCEDURE sp_sales_report(
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_branch_id INT
)
BEGIN
    SELECT
        DATE(co.order_date) AS sale_date,
        COUNT(co.order_id) AS total_orders,
        SUM(co.subtotal) AS gross_sales,
        SUM(co.discount_amount) AS total_discounts,
        SUM(co.tax_amount) AS total_tax,
        SUM(co.total_amount) AS net_sales,
        COUNT(DISTINCT co.customer_id) AS unique_customers
    FROM customer_order co
    WHERE co.order_date BETWEEN p_start_date AND p_end_date
        AND (p_branch_id IS NULL OR co.branch_id = p_branch_id)
        AND co.status NOT IN ('Cancelled', 'Refunded')
    GROUP BY DATE(co.order_date)
    ORDER BY sale_date;
END //
DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

-- --------------------------------------------
-- Trigger: Deduct inventory on order confirmation
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_deduct_inventory_after_order;
DELIMITER //
CREATE TRIGGER trg_deduct_inventory_after_order
AFTER UPDATE ON customer_order
FOR EACH ROW
BEGIN
    IF NEW.status = 'Processing' AND OLD.status = 'Pending' THEN
        UPDATE inventory i
        JOIN order_item oi ON i.product_id = oi.product_id
        SET i.quantity = i.quantity - oi.quantity
        WHERE oi.order_id = NEW.order_id AND i.branch_id = NEW.branch_id;
    END IF;
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Restore inventory on order cancellation
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_restore_inventory_on_cancel;
DELIMITER //
CREATE TRIGGER trg_restore_inventory_on_cancel
AFTER UPDATE ON customer_order
FOR EACH ROW
BEGIN
    IF NEW.status = 'Cancelled' AND OLD.status IN ('Pending', 'Processing') THEN
        UPDATE inventory i
        JOIN order_item oi ON i.product_id = oi.product_id
        SET i.quantity = i.quantity + oi.quantity
        WHERE oi.order_id = NEW.order_id AND i.branch_id = NEW.branch_id;
    END IF;
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Update membership after payment
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_update_membership_after_payment;
DELIMITER //
CREATE TRIGGER trg_update_membership_after_payment
AFTER INSERT ON payment
FOR EACH ROW
BEGIN
    DECLARE v_customer_id INT;

    IF NEW.status = 'Completed' THEN
        SELECT customer_id INTO v_customer_id
        FROM customer_order WHERE order_id = NEW.order_id;

        CALL sp_update_membership_level(v_customer_id);
    END IF;
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Audit log for sensitive employee changes
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_audit_employee_update;
DELIMITER //
CREATE TRIGGER trg_audit_employee_update
AFTER UPDATE ON employee
FOR EACH ROW
BEGIN
    IF OLD.salary != NEW.salary OR OLD.role != NEW.role OR OLD.is_active != NEW.is_active THEN
        INSERT INTO audit_log (action, table_name, record_id, old_values, new_values)
        VALUES (
            'UPDATE',
            'employee',
            NEW.employee_id,
            JSON_OBJECT('salary', OLD.salary, 'role', OLD.role, 'is_active', OLD.is_active),
            JSON_OBJECT('salary', NEW.salary, 'role', NEW.role, 'is_active', NEW.is_active)
        );
    END IF;
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Validate review - must have purchased product
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_validate_review;
DELIMITER //
CREATE TRIGGER trg_validate_review
BEFORE INSERT ON review
FOR EACH ROW
BEGIN
    DECLARE v_purchased INT;

    SELECT COUNT(*) INTO v_purchased
    FROM customer_order co
    JOIN order_item oi ON co.order_id = oi.order_id
    WHERE co.customer_id = NEW.customer_id
        AND oi.product_id = NEW.product_id
        AND co.status IN ('Delivered', 'Processing', 'Shipped');

    IF v_purchased > 0 THEN
        SET NEW.is_verified_purchase = TRUE;
    ELSE
        SET NEW.is_verified_purchase = FALSE;
    END IF;
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Auto-calculate order item subtotal
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_calc_order_item_subtotal;
DELIMITER //
CREATE TRIGGER trg_calc_order_item_subtotal
BEFORE INSERT ON order_item
FOR EACH ROW
BEGIN
    SET NEW.subtotal = NEW.quantity * NEW.unit_price * (1 - COALESCE(NEW.discount_percent, 0) / 100);
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Auto-calculate PO item subtotal
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_calc_po_item_subtotal;
DELIMITER //
CREATE TRIGGER trg_calc_po_item_subtotal
BEFORE INSERT ON purchase_order_item
FOR EACH ROW
BEGIN
    SET NEW.subtotal = NEW.quantity * NEW.unit_cost;
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Update PO total on item insert
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_update_po_total;
DELIMITER //
CREATE TRIGGER trg_update_po_total
AFTER INSERT ON purchase_order_item
FOR EACH ROW
BEGIN
    UPDATE purchase_order
    SET total_amount = (
        SELECT COALESCE(SUM(subtotal), 0)
        FROM purchase_order_item
        WHERE purchase_order_id = NEW.purchase_order_id
    )
    WHERE purchase_order_id = NEW.purchase_order_id;
END //
DELIMITER ;

-- --------------------------------------------
-- Trigger: Prevent deleting branch with employees
-- --------------------------------------------
DROP TRIGGER IF EXISTS trg_prevent_branch_delete;
DELIMITER //
CREATE TRIGGER trg_prevent_branch_delete
BEFORE DELETE ON branch
FOR EACH ROW
BEGIN
    DECLARE v_emp_count INT;
    SELECT COUNT(*) INTO v_emp_count FROM employee WHERE branch_id = OLD.branch_id AND is_active = TRUE;
    IF v_emp_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot delete branch with active employees';
    END IF;
END //
DELIMITER ;

SELECT 'Stored procedures and triggers created successfully!' AS Status;
