-- ============================================
-- SummitSphere Advanced SQL Queries
-- Version: 1.0
-- Description: Demonstrates advanced SQL techniques including:
--   1. Window Functions (ROW_NUMBER, RANK, DENSE_RANK, LAG, LEAD, SUM OVER)
--   2. Common Table Expressions (CTEs) - Recursive and Non-recursive
--   3. Complex Subqueries with Aggregation
--   4. CASE statements and conditional logic
--   5. Multi-table JOINs with aggregation
-- ============================================

USE summitsphere;

-- ============================================
-- ADVANCED QUERY 1: Window Functions
-- Sales Performance Ranking with Moving Averages
-- ============================================

-- 1.1 Rank employees by sales within each branch
SELECT
    e.employee_id,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
    b.name AS branch_name,
    COUNT(co.order_id) AS total_orders,
    SUM(co.total_amount) AS total_sales,
    RANK() OVER (PARTITION BY e.branch_id ORDER BY SUM(co.total_amount) DESC) AS sales_rank,
    DENSE_RANK() OVER (PARTITION BY e.branch_id ORDER BY COUNT(co.order_id) DESC) AS order_count_rank,
    ROUND(AVG(SUM(co.total_amount)) OVER (PARTITION BY e.branch_id), 2) AS branch_avg_sales,
    ROUND(SUM(co.total_amount) - AVG(SUM(co.total_amount)) OVER (PARTITION BY e.branch_id), 2) AS variance_from_avg
FROM employee e
JOIN branch b ON e.branch_id = b.branch_id
LEFT JOIN customer_order co ON e.employee_id = co.employee_id
WHERE e.role = 'Staff' AND e.is_active = TRUE
GROUP BY e.employee_id, e.first_name, e.last_name, b.name, e.branch_id
ORDER BY b.name, sales_rank;

-- 1.2 Monthly sales with running total and month-over-month comparison
SELECT
    DATE_FORMAT(order_date, '%Y-%m') AS month,
    COUNT(*) AS order_count,
    SUM(total_amount) AS monthly_sales,
    SUM(SUM(total_amount)) OVER (ORDER BY DATE_FORMAT(order_date, '%Y-%m')) AS running_total,
    LAG(SUM(total_amount), 1) OVER (ORDER BY DATE_FORMAT(order_date, '%Y-%m')) AS previous_month_sales,
    ROUND(
        (SUM(total_amount) - LAG(SUM(total_amount), 1) OVER (ORDER BY DATE_FORMAT(order_date, '%Y-%m')))
        / LAG(SUM(total_amount), 1) OVER (ORDER BY DATE_FORMAT(order_date, '%Y-%m')) * 100,
        2
    ) AS growth_percentage,
    ROUND(AVG(SUM(total_amount)) OVER (
        ORDER BY DATE_FORMAT(order_date, '%Y-%m')
        ROWS BETWEEN 2 PRECEDING AND CURRENT ROW
    ), 2) AS three_month_moving_avg
FROM customer_order
WHERE status NOT IN ('Cancelled', 'Refunded')
GROUP BY DATE_FORMAT(order_date, '%Y-%m')
ORDER BY month;

-- 1.3 Top 3 products by category using ROW_NUMBER
SELECT *
FROM (
    SELECT
        cat.name AS category_name,
        p.name AS product_name,
        p.unit_price,
        COALESCE(SUM(oi.quantity), 0) AS total_quantity_sold,
        COALESCE(SUM(oi.subtotal), 0) AS total_revenue,
        ROW_NUMBER() OVER (PARTITION BY cat.category_id ORDER BY COALESCE(SUM(oi.subtotal), 0) DESC) AS rank_in_category
    FROM category cat
    JOIN product p ON cat.category_id = p.category_id
    LEFT JOIN order_item oi ON p.product_id = oi.product_id
    GROUP BY cat.category_id, cat.name, p.product_id, p.name, p.unit_price
) ranked_products
WHERE rank_in_category <= 3
ORDER BY category_name, rank_in_category;

-- ============================================
-- ADVANCED QUERY 2: Recursive CTE
-- Category Hierarchy Traversal
-- ============================================

-- 2.1 Full category hierarchy with path and depth
WITH RECURSIVE category_tree AS (
    -- Base case: root categories (no parent)
    SELECT
        category_id,
        name,
        parent_category_id,
        name AS full_path,
        0 AS depth,
        CAST(category_id AS CHAR(200)) AS path_ids
    FROM category
    WHERE parent_category_id IS NULL

    UNION ALL

    -- Recursive case: child categories
    SELECT
        c.category_id,
        c.name,
        c.parent_category_id,
        CONCAT(ct.full_path, ' > ', c.name) AS full_path,
        ct.depth + 1 AS depth,
        CONCAT(ct.path_ids, ' > ', c.category_id) AS path_ids
    FROM category c
    INNER JOIN category_tree ct ON c.parent_category_id = ct.category_id
)
SELECT
    category_id,
    CONCAT(REPEAT('  ', depth), name) AS indented_name,
    full_path,
    depth,
    path_ids
FROM category_tree
ORDER BY full_path;

-- 2.2 Category statistics including all descendants
WITH RECURSIVE category_descendants AS (
    SELECT category_id, category_id AS root_id, name AS root_name
    FROM category
    WHERE parent_category_id IS NULL

    UNION ALL

    SELECT c.category_id, cd.root_id, cd.root_name
    FROM category c
    INNER JOIN category_descendants cd ON c.parent_category_id = cd.category_id
)
SELECT
    cd.root_id,
    cd.root_name AS parent_category,
    COUNT(DISTINCT p.product_id) AS total_products,
    COALESCE(SUM(oi.quantity), 0) AS total_units_sold,
    COALESCE(SUM(oi.subtotal), 0) AS total_revenue
FROM category_descendants cd
LEFT JOIN product p ON cd.category_id = p.category_id
LEFT JOIN order_item oi ON p.product_id = oi.product_id
GROUP BY cd.root_id, cd.root_name
ORDER BY total_revenue DESC;

-- ============================================
-- ADVANCED QUERY 3: Complex Subqueries with Aggregation
-- Customer Segmentation and Analysis
-- ============================================

-- 3.1 Customer RFM Analysis (Recency, Frequency, Monetary)
SELECT
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.membership_level,
    -- Recency: days since last order
    DATEDIFF(CURRENT_DATE, (
        SELECT MAX(order_date)
        FROM customer_order
        WHERE customer_id = c.customer_id AND status != 'Cancelled'
    )) AS days_since_last_order,
    -- Frequency: number of orders
    (SELECT COUNT(*)
     FROM customer_order
     WHERE customer_id = c.customer_id AND status != 'Cancelled'
    ) AS order_count,
    -- Monetary: total spend
    (SELECT COALESCE(SUM(total_amount), 0)
     FROM customer_order co
     JOIN payment p ON co.order_id = p.order_id
     WHERE co.customer_id = c.customer_id AND p.status = 'Completed'
    ) AS total_spent,
    -- Average order value
    (SELECT ROUND(AVG(total_amount), 2)
     FROM customer_order
     WHERE customer_id = c.customer_id AND status != 'Cancelled'
    ) AS avg_order_value,
    -- Customer segment based on spending
    CASE
        WHEN (SELECT COALESCE(SUM(total_amount), 0)
              FROM customer_order co
              JOIN payment p ON co.order_id = p.order_id
              WHERE co.customer_id = c.customer_id AND p.status = 'Completed') >= 10000 THEN 'VIP'
        WHEN (SELECT COALESCE(SUM(total_amount), 0)
              FROM customer_order co
              JOIN payment p ON co.order_id = p.order_id
              WHERE co.customer_id = c.customer_id AND p.status = 'Completed') >= 5000 THEN 'High Value'
        WHEN (SELECT COALESCE(SUM(total_amount), 0)
              FROM customer_order co
              JOIN payment p ON co.order_id = p.order_id
              WHERE co.customer_id = c.customer_id AND p.status = 'Completed') >= 1000 THEN 'Regular'
        ELSE 'New/Occasional'
    END AS customer_segment
FROM customer c
WHERE c.is_active = TRUE
ORDER BY total_spent DESC;

-- 3.2 Products that are frequently bought together (Market Basket Analysis)
SELECT
    p1.name AS product_1,
    p2.name AS product_2,
    COUNT(*) AS times_bought_together,
    ROUND(COUNT(*) * 100.0 / (
        SELECT COUNT(DISTINCT order_id) FROM order_item WHERE product_id = oi1.product_id
    ), 2) AS association_percentage
FROM order_item oi1
JOIN order_item oi2 ON oi1.order_id = oi2.order_id AND oi1.product_id < oi2.product_id
JOIN product p1 ON oi1.product_id = p1.product_id
JOIN product p2 ON oi2.product_id = p2.product_id
GROUP BY oi1.product_id, oi2.product_id, p1.name, p2.name
HAVING COUNT(*) >= 2
ORDER BY times_bought_together DESC
LIMIT 20;

-- 3.3 Identify at-risk customers (no orders in last 90 days but previously active)
SELECT
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.email,
    c.membership_level,
    c.total_spent,
    last_order.last_order_date,
    DATEDIFF(CURRENT_DATE, last_order.last_order_date) AS days_inactive,
    last_order.last_order_amount
FROM customer c
JOIN (
    SELECT
        customer_id,
        MAX(order_date) AS last_order_date,
        (SELECT total_amount
         FROM customer_order
         WHERE customer_id = co.customer_id
         ORDER BY order_date DESC LIMIT 1) AS last_order_amount
    FROM customer_order co
    WHERE status != 'Cancelled'
    GROUP BY customer_id
    HAVING MAX(order_date) < DATE_SUB(CURRENT_DATE, INTERVAL 90 DAY)
       AND MAX(order_date) > DATE_SUB(CURRENT_DATE, INTERVAL 365 DAY)
) last_order ON c.customer_id = last_order.customer_id
WHERE c.total_spent > 500
ORDER BY c.total_spent DESC;

-- ============================================
-- ADVANCED QUERY 4: Inventory Optimization
-- Using CTEs and Window Functions
-- ============================================

-- 4.1 Inventory health analysis with reorder suggestions
WITH inventory_metrics AS (
    SELECT
        i.branch_id,
        b.name AS branch_name,
        i.product_id,
        p.name AS product_name,
        s.name AS supplier_name,
        s.supplier_id,
        i.quantity AS current_stock,
        i.min_stock_level,
        i.max_stock_level,
        p.cost_price,
        p.unit_price,
        -- Calculate average daily sales (last 30 days)
        COALESCE((
            SELECT SUM(oi.quantity) / 30
            FROM order_item oi
            JOIN customer_order co ON oi.order_id = co.order_id
            WHERE oi.product_id = i.product_id
              AND co.branch_id = i.branch_id
              AND co.order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
              AND co.status != 'Cancelled'
        ), 0) AS avg_daily_sales
    FROM inventory i
    JOIN branch b ON i.branch_id = b.branch_id
    JOIN product p ON i.product_id = p.product_id
    JOIN supplier s ON p.supplier_id = s.supplier_id
)
SELECT
    branch_name,
    product_name,
    supplier_name,
    current_stock,
    min_stock_level,
    ROUND(avg_daily_sales, 2) AS avg_daily_sales,
    CASE
        WHEN avg_daily_sales > 0 THEN ROUND(current_stock / avg_daily_sales, 0)
        ELSE NULL
    END AS days_of_stock_remaining,
    CASE
        WHEN current_stock <= min_stock_level THEN 'CRITICAL - Reorder Now'
        WHEN avg_daily_sales > 0 AND (current_stock / avg_daily_sales) <= 14 THEN 'WARNING - Reorder Soon'
        WHEN current_stock >= max_stock_level THEN 'OVERSTOCKED'
        ELSE 'OK'
    END AS stock_status,
    CASE
        WHEN current_stock < min_stock_level THEN
            GREATEST(min_stock_level * 2 - current_stock, 0)
        ELSE 0
    END AS suggested_reorder_quantity,
    ROUND(current_stock * cost_price, 2) AS inventory_cost_value
FROM inventory_metrics
ORDER BY
    CASE
        WHEN current_stock <= min_stock_level THEN 1
        WHEN avg_daily_sales > 0 AND (current_stock / avg_daily_sales) <= 14 THEN 2
        ELSE 3
    END,
    days_of_stock_remaining;

-- ============================================
-- ADVANCED QUERY 5: Time-Series Analysis
-- Sales Trends with Year-over-Year Comparison
-- ============================================

-- 5.1 Year-over-year sales comparison with growth rate
WITH monthly_sales AS (
    SELECT
        YEAR(order_date) AS year,
        MONTH(order_date) AS month,
        DATE_FORMAT(order_date, '%Y-%m') AS year_month,
        COUNT(*) AS order_count,
        SUM(total_amount) AS total_sales,
        COUNT(DISTINCT customer_id) AS unique_customers
    FROM customer_order
    WHERE status NOT IN ('Cancelled', 'Refunded')
    GROUP BY YEAR(order_date), MONTH(order_date), DATE_FORMAT(order_date, '%Y-%m')
)
SELECT
    ms.year_month,
    ms.order_count,
    ROUND(ms.total_sales, 2) AS total_sales,
    ms.unique_customers,
    -- Previous year same month
    prev.total_sales AS prev_year_sales,
    -- Year-over-year growth
    CASE
        WHEN prev.total_sales IS NOT NULL AND prev.total_sales > 0 THEN
            ROUND((ms.total_sales - prev.total_sales) / prev.total_sales * 100, 2)
        ELSE NULL
    END AS yoy_growth_pct,
    -- Quarter-over-quarter
    ROUND(ms.total_sales - LAG(ms.total_sales, 3) OVER (ORDER BY ms.year, ms.month), 2) AS qoq_change
FROM monthly_sales ms
LEFT JOIN monthly_sales prev ON ms.month = prev.month AND ms.year = prev.year + 1
ORDER BY ms.year_month;

-- ============================================
-- ADVANCED QUERY 6: Supplier Performance Scorecard
-- ============================================

SELECT
    s.supplier_id,
    s.name AS supplier_name,
    s.cooperation_status,
    COUNT(DISTINCT p.product_id) AS products_count,
    COUNT(DISTINCT po.purchase_order_id) AS total_orders,
    COALESCE(SUM(po.total_amount), 0) AS total_order_value,
    -- Delivery performance
    (SELECT COUNT(*)
     FROM shipment sh
     WHERE sh.supplier_id = s.supplier_id AND sh.status = 'Delivered'
    ) AS completed_shipments,
    (SELECT COUNT(*)
     FROM shipment sh
     WHERE sh.supplier_id = s.supplier_id
       AND sh.status = 'Delivered'
       AND sh.actual_arrival <= sh.expected_arrival
    ) AS on_time_deliveries,
    -- On-time delivery rate
    ROUND(
        (SELECT COUNT(*)
         FROM shipment sh
         WHERE sh.supplier_id = s.supplier_id
           AND sh.status = 'Delivered'
           AND sh.actual_arrival <= sh.expected_arrival)
        /
        NULLIF((SELECT COUNT(*)
                FROM shipment sh
                WHERE sh.supplier_id = s.supplier_id AND sh.status = 'Delivered'), 0)
        * 100, 2
    ) AS on_time_delivery_rate,
    -- Average delivery delay (days)
    (SELECT ROUND(AVG(DATEDIFF(actual_arrival, expected_arrival)), 1)
     FROM shipment
     WHERE supplier_id = s.supplier_id
       AND status = 'Delivered'
       AND actual_arrival > expected_arrival
    ) AS avg_delay_days,
    -- Product quality (based on review ratings)
    (SELECT ROUND(AVG(r.rating), 2)
     FROM review r
     JOIN product prod ON r.product_id = prod.product_id
     WHERE prod.supplier_id = s.supplier_id AND r.is_approved = TRUE
    ) AS avg_product_rating
FROM supplier s
LEFT JOIN product p ON s.supplier_id = p.supplier_id
LEFT JOIN purchase_order po ON s.supplier_id = po.supplier_id
GROUP BY s.supplier_id, s.name, s.cooperation_status
ORDER BY total_order_value DESC;

-- ============================================
-- ADVANCED QUERY 7: Cohort Analysis
-- Customer Retention by Registration Month
-- ============================================

WITH customer_cohorts AS (
    SELECT
        c.customer_id,
        DATE_FORMAT(c.registration_date, '%Y-%m') AS cohort_month,
        DATE_FORMAT(MIN(co.order_date), '%Y-%m') AS first_order_month
    FROM customer c
    LEFT JOIN customer_order co ON c.customer_id = co.customer_id AND co.status != 'Cancelled'
    GROUP BY c.customer_id, DATE_FORMAT(c.registration_date, '%Y-%m')
),
cohort_data AS (
    SELECT
        cc.cohort_month,
        DATE_FORMAT(co.order_date, '%Y-%m') AS order_month,
        PERIOD_DIFF(
            EXTRACT(YEAR_MONTH FROM co.order_date),
            EXTRACT(YEAR_MONTH FROM STR_TO_DATE(CONCAT(cc.cohort_month, '-01'), '%Y-%m-%d'))
        ) AS months_since_registration,
        COUNT(DISTINCT cc.customer_id) AS active_customers
    FROM customer_cohorts cc
    JOIN customer_order co ON cc.customer_id = co.customer_id AND co.status != 'Cancelled'
    GROUP BY cc.cohort_month, DATE_FORMAT(co.order_date, '%Y-%m'),
             PERIOD_DIFF(
                 EXTRACT(YEAR_MONTH FROM co.order_date),
                 EXTRACT(YEAR_MONTH FROM STR_TO_DATE(CONCAT(cc.cohort_month, '-01'), '%Y-%m-%d'))
             )
)
SELECT
    cohort_month,
    MAX(CASE WHEN months_since_registration = 0 THEN active_customers END) AS month_0,
    MAX(CASE WHEN months_since_registration = 1 THEN active_customers END) AS month_1,
    MAX(CASE WHEN months_since_registration = 2 THEN active_customers END) AS month_2,
    MAX(CASE WHEN months_since_registration = 3 THEN active_customers END) AS month_3,
    MAX(CASE WHEN months_since_registration = 6 THEN active_customers END) AS month_6,
    MAX(CASE WHEN months_since_registration = 12 THEN active_customers END) AS month_12
FROM cohort_data
GROUP BY cohort_month
ORDER BY cohort_month;

SELECT 'Advanced queries file created successfully!' AS Status;
