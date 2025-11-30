<?php
/**
 * Reports Dashboard
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Reports & Analytics';
$db = new Database();
$conn = $db->getConnection();

// Date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Sales by Branch
$salesByBranch = $conn->query("
    SELECT b.name AS branch_name, COUNT(co.order_id) AS order_count,
           COALESCE(SUM(co.total_amount), 0) AS total_sales,
           COUNT(DISTINCT co.customer_id) AS unique_customers
    FROM branch b
    LEFT JOIN customer_order co ON b.branch_id = co.branch_id
        AND co.status NOT IN ('Cancelled', 'Refunded')
        AND DATE(co.order_date) BETWEEN '$startDate' AND '$endDate'
    GROUP BY b.branch_id, b.name
    ORDER BY total_sales DESC
")->fetchAll();

// Top Products
$topProducts = $conn->query("
    SELECT p.name AS product_name, p.sku, c.name AS category_name,
           SUM(oi.quantity) AS units_sold, SUM(oi.subtotal) AS revenue
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    JOIN category c ON p.category_id = c.category_id
    JOIN customer_order co ON oi.order_id = co.order_id
    WHERE co.status NOT IN ('Cancelled', 'Refunded')
        AND DATE(co.order_date) BETWEEN '$startDate' AND '$endDate'
    GROUP BY p.product_id
    ORDER BY revenue DESC
    LIMIT 10
")->fetchAll();

// Sales by Category
$salesByCategory = $conn->query("
    SELECT c.name AS category_name, SUM(oi.quantity) AS units_sold,
           SUM(oi.subtotal) AS revenue
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    JOIN category c ON p.category_id = c.category_id
    JOIN customer_order co ON oi.order_id = co.order_id
    WHERE co.status NOT IN ('Cancelled', 'Refunded')
        AND DATE(co.order_date) BETWEEN '$startDate' AND '$endDate'
    GROUP BY c.category_id
    ORDER BY revenue DESC
")->fetchAll();

// Customer Membership Distribution
$membershipDist = $conn->query("
    SELECT membership_level, COUNT(*) AS count,
           SUM(total_spent) AS total_spent
    FROM customer WHERE is_active = TRUE
    GROUP BY membership_level
    ORDER BY FIELD(membership_level, 'Bronze', 'Silver', 'Gold', 'Platinum')
")->fetchAll();

// Top Customers
$topCustomers = $conn->query("
    SELECT c.customer_id, CONCAT(c.first_name, ' ', c.last_name) AS name,
           c.email, c.membership_level, c.total_spent,
           COUNT(co.order_id) AS order_count
    FROM customer c
    LEFT JOIN customer_order co ON c.customer_id = co.customer_id
    WHERE c.is_active = TRUE
    GROUP BY c.customer_id
    ORDER BY c.total_spent DESC
    LIMIT 10
")->fetchAll();

// Daily Sales Trend
$dailySales = $conn->query("
    SELECT DATE(order_date) AS date, COUNT(*) AS orders,
           SUM(total_amount) AS revenue
    FROM customer_order
    WHERE status NOT IN ('Cancelled', 'Refunded')
        AND DATE(order_date) BETWEEN '$startDate' AND '$endDate'
    GROUP BY DATE(order_date)
    ORDER BY date
")->fetchAll();

// Summary Stats
$summary = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM customer_order WHERE status NOT IN ('Cancelled', 'Refunded')
         AND DATE(order_date) BETWEEN '$startDate' AND '$endDate') AS total_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM customer_order WHERE status NOT IN ('Cancelled', 'Refunded')
         AND DATE(order_date) BETWEEN '$startDate' AND '$endDate') AS total_revenue,
        (SELECT COUNT(DISTINCT customer_id) FROM customer_order WHERE status NOT IN ('Cancelled', 'Refunded')
         AND DATE(order_date) BETWEEN '$startDate' AND '$endDate') AS unique_customers,
        (SELECT COALESCE(AVG(total_amount), 0) FROM customer_order WHERE status NOT IN ('Cancelled', 'Refunded')
         AND DATE(order_date) BETWEEN '$startDate' AND '$endDate') AS avg_order_value
")->fetch();

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-graph-up"></i> Reports & Analytics</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Reports</li>
            </ol>
        </nav>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-primary btn-print">
            <i class="bi bi-printer"></i> Print Report
        </button>
    </div>
</div>

<!-- Date Filter -->
<div class="search-filter">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            <a href="reports.php" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Total Orders</h6>
                <h3><?php echo number_format($summary['total_orders']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Total Revenue</h6>
                <h3><?php echo formatCurrency($summary['total_revenue']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <h6 class="text-muted">Unique Customers</h6>
                <h3><?php echo number_format($summary['unique_customers']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Avg Order Value</h6>
                <h3><?php echo formatCurrency($summary['avg_order_value']); ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Sales by Branch -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Sales by Branch</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                            <th>Customers</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesByBranch as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['branch_name']); ?></td>
                            <td><?php echo number_format($row['order_count']); ?></td>
                            <td><?php echo formatCurrency($row['total_sales']); ?></td>
                            <td><?php echo number_format($row['unique_customers']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sales by Category -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Sales by Category</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesByCategory as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td><?php echo number_format($row['units_sold']); ?></td>
                            <td><?php echo formatCurrency($row['revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Products -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Top 10 Products</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $i => $row): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['product_name']); ?><br>
                                <small class="text-muted"><?php echo $row['sku']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td><?php echo number_format($row['units_sold']); ?></td>
                            <td><?php echo formatCurrency($row['revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Customer Membership -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Membership Distribution</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Count</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membershipDist as $row): ?>
                        <tr>
                            <td>
                                <span class="membership-badge <?php echo strtolower($row['membership_level']); ?>">
                                    <?php echo $row['membership_level']; ?>
                                </span>
                            </td>
                            <td><?php echo number_format($row['count']); ?></td>
                            <td><?php echo formatCurrency($row['total_spent']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Top Customers -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Top 10 Customers</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Membership</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topCustomers as $i => $row): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <span class="membership-badge <?php echo strtolower($row['membership_level']); ?>">
                            <?php echo $row['membership_level']; ?>
                        </span>
                    </td>
                    <td><?php echo number_format($row['order_count']); ?></td>
                    <td><?php echo formatCurrency($row['total_spent']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
