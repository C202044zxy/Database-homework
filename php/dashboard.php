<?php
/**
 * Dashboard
 * SummitSphere Retail Management System
 */

require_once 'config/config.php';
requireLogin();

$pageTitle = 'Dashboard';

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Initialize stats array
$stats = [];

try {
    switch ($_SESSION['role']) {
        case ROLE_MANAGER:
            // Manager dashboard stats
            $stats['branches'] = $conn->query("SELECT COUNT(*) FROM branch WHERE is_active = TRUE")->fetchColumn();
            $stats['employees'] = $conn->query("SELECT COUNT(*) FROM employee WHERE is_active = TRUE")->fetchColumn();
            $stats['products'] = $conn->query("SELECT COUNT(*) FROM product WHERE is_active = TRUE")->fetchColumn();
            $stats['customers'] = $conn->query("SELECT COUNT(*) FROM customer WHERE is_active = TRUE")->fetchColumn();
            $stats['orders_today'] = $conn->query("SELECT COUNT(*) FROM customer_order WHERE DATE(order_date) = CURRENT_DATE")->fetchColumn();
            $stats['revenue_today'] = $conn->query("SELECT COALESCE(SUM(total_amount), 0) FROM customer_order WHERE DATE(order_date) = CURRENT_DATE AND status != 'Cancelled'")->fetchColumn();
            $stats['low_stock'] = $conn->query("SELECT COUNT(*) FROM inventory WHERE quantity <= min_stock_level")->fetchColumn();
            $stats['pending_orders'] = $conn->query("SELECT COUNT(*) FROM customer_order WHERE status = 'Pending'")->fetchColumn();

            // Recent orders
            $recentOrders = $conn->query("SELECT co.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, b.name AS branch_name
                                          FROM customer_order co
                                          JOIN customer c ON co.customer_id = c.customer_id
                                          JOIN branch b ON co.branch_id = b.branch_id
                                          ORDER BY co.order_date DESC LIMIT 5")->fetchAll();

            // Low stock alerts
            $lowStockItems = $conn->query("SELECT p.name AS product_name, b.name AS branch_name, i.quantity, i.min_stock_level
                                           FROM inventory i
                                           JOIN product p ON i.product_id = p.product_id
                                           JOIN branch b ON i.branch_id = b.branch_id
                                           WHERE i.quantity <= i.min_stock_level
                                           ORDER BY (i.min_stock_level - i.quantity) DESC LIMIT 5")->fetchAll();
            break;

        case ROLE_STAFF:
            // Staff dashboard stats (branch specific)
            $branchId = $_SESSION['branch_id'];
            $stmt = $conn->prepare("SELECT name FROM branch WHERE branch_id = ?");
            $stmt->execute([$branchId]);
            $stats['branch_name'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM customer_order WHERE branch_id = ? AND DATE(order_date) = CURRENT_DATE");
            $stmt->execute([$branchId]);
            $stats['orders_today'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM customer_order WHERE branch_id = ? AND DATE(order_date) = CURRENT_DATE AND status != 'Cancelled'");
            $stmt->execute([$branchId]);
            $stats['revenue_today'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND quantity <= min_stock_level");
            $stmt->execute([$branchId]);
            $stats['low_stock'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM customer_order WHERE branch_id = ? AND status = 'Pending'");
            $stmt->execute([$branchId]);
            $stats['pending_orders'] = $stmt->fetchColumn();

            // Recent orders for branch
            $stmt = $conn->prepare("SELECT co.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name
                                    FROM customer_order co
                                    JOIN customer c ON co.customer_id = c.customer_id
                                    WHERE co.branch_id = ?
                                    ORDER BY co.order_date DESC LIMIT 5");
            $stmt->execute([$branchId]);
            $recentOrders = $stmt->fetchAll();
            break;

        case ROLE_SUPPLIER:
            // Supplier dashboard stats
            $supplierId = $_SESSION['supplier_id'];

            $stmt = $conn->prepare("SELECT COUNT(*) FROM product WHERE supplier_id = ? AND is_active = TRUE");
            $stmt->execute([$supplierId]);
            $stats['products'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM purchase_order WHERE supplier_id = ? AND status IN ('Submitted', 'Confirmed')");
            $stmt->execute([$supplierId]);
            $stats['pending_orders'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM shipment WHERE supplier_id = ? AND status IN ('Pending', 'In Transit')");
            $stmt->execute([$supplierId]);
            $stats['active_shipments'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM purchase_order WHERE supplier_id = ? AND status = 'Received'");
            $stmt->execute([$supplierId]);
            $stats['total_revenue'] = $stmt->fetchColumn();

            // Recent purchase orders
            $stmt = $conn->prepare("SELECT po.*, b.name AS branch_name
                                    FROM purchase_order po
                                    JOIN branch b ON po.branch_id = b.branch_id
                                    WHERE po.supplier_id = ?
                                    ORDER BY po.order_date DESC LIMIT 5");
            $stmt->execute([$supplierId]);
            $recentOrders = $stmt->fetchAll();
            break;

        case ROLE_CUSTOMER:
            // Customer dashboard stats
            $customerId = $_SESSION['customer_id'];

            $stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
            $stmt->execute([$customerId]);
            $customerInfo = $stmt->fetch();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM customer_order WHERE customer_id = ?");
            $stmt->execute([$customerId]);
            $stats['total_orders'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM customer_order WHERE customer_id = ? AND status NOT IN ('Delivered', 'Cancelled')");
            $stmt->execute([$customerId]);
            $stats['active_orders'] = $stmt->fetchColumn();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM review WHERE customer_id = ?");
            $stmt->execute([$customerId]);
            $stats['reviews'] = $stmt->fetchColumn();

            // Recent orders
            $stmt = $conn->prepare("SELECT co.*, b.name AS branch_name
                                    FROM customer_order co
                                    JOIN branch b ON co.branch_id = b.branch_id
                                    WHERE co.customer_id = ?
                                    ORDER BY co.order_date DESC LIMIT 5");
            $stmt->execute([$customerId]);
            $recentOrders = $stmt->fetchAll();
            break;
    }
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    </div>
</div>

<?php if ($_SESSION['role'] === ROLE_MANAGER): ?>
<!-- Manager Dashboard -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Branches</h6>
                        <h3 class="mb-0"><?php echo $stats['branches']; ?></h3>
                    </div>
                    <i class="bi bi-building card-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Today's Revenue</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($stats['revenue_today']); ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar card-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Orders</h6>
                        <h3 class="mb-0"><?php echo $stats['pending_orders']; ?></h3>
                    </div>
                    <i class="bi bi-clock-history card-icon text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Low Stock Items</h6>
                        <h3 class="mb-0"><?php echo $stats['low_stock']; ?></h3>
                    </div>
                    <i class="bi bi-exclamation-triangle card-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Employees</h6>
                        <h3 class="mb-0"><?php echo $stats['employees']; ?></h3>
                    </div>
                    <i class="bi bi-people card-icon text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Products</h6>
                        <h3 class="mb-0"><?php echo $stats['products']; ?></h3>
                    </div>
                    <i class="bi bi-box-seam card-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Customers</h6>
                        <h3 class="mb-0"><?php echo $stats['customers']; ?></h3>
                    </div>
                    <i class="bi bi-person-check card-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Today's Orders</h6>
                        <h3 class="mb-0"><?php echo $stats['orders_today']; ?></h3>
                    </div>
                    <i class="bi bi-cart-check card-icon text-info"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentOrders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['branch_name']); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><span class="order-status <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                <td><?php echo formatDateTime($order['order_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No recent orders</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($lowStockItems)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($lowStockItems as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($item['branch_name']); ?></small>
                        </div>
                        <span class="badge bg-danger"><?php echo $item['quantity']; ?> / <?php echo $item['min_stock_level']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-success text-center"><i class="bi bi-check-circle"></i> All stock levels are healthy!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($_SESSION['role'] === ROLE_STAFF): ?>
<!-- Staff Dashboard -->
<div class="alert alert-info">
    <i class="bi bi-building"></i> You are logged in at: <strong><?php echo htmlspecialchars($stats['branch_name']); ?></strong>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Today's Revenue</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($stats['revenue_today']); ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar card-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Today's Orders</h6>
                        <h3 class="mb-0"><?php echo $stats['orders_today']; ?></h3>
                    </div>
                    <i class="bi bi-cart card-icon text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Orders</h6>
                        <h3 class="mb-0"><?php echo $stats['pending_orders']; ?></h3>
                    </div>
                    <i class="bi bi-clock card-icon text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Low Stock Items</h6>
                        <h3 class="mb-0"><?php echo $stats['low_stock']; ?></h3>
                    </div>
                    <i class="bi bi-exclamation-triangle card-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
                <a href="views/staff/new_order.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus"></i> New Sale
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentOrders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><span class="order-status <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                <td><?php echo formatDateTime($order['order_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No recent orders</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="views/staff/new_order.php" class="btn btn-primary">
                        <i class="bi bi-cart-plus"></i> Process New Sale
                    </a>
                    <a href="views/staff/inventory.php" class="btn btn-outline-primary">
                        <i class="bi bi-boxes"></i> Check Inventory
                    </a>
                    <a href="views/staff/orders.php" class="btn btn-outline-primary">
                        <i class="bi bi-list-check"></i> View All Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($_SESSION['role'] === ROLE_SUPPLIER): ?>
<!-- Supplier Dashboard -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">My Products</h6>
                        <h3 class="mb-0"><?php echo $stats['products']; ?></h3>
                    </div>
                    <i class="bi bi-box-seam card-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Orders</h6>
                        <h3 class="mb-0"><?php echo $stats['pending_orders']; ?></h3>
                    </div>
                    <i class="bi bi-file-earmark-text card-icon text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active Shipments</h6>
                        <h3 class="mb-0"><?php echo $stats['active_shipments']; ?></h3>
                    </div>
                    <i class="bi bi-truck card-icon text-info"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Revenue</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($stats['total_revenue']); ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar card-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Recent Purchase Orders</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($recentOrders)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Branch</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Expected Delivery</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['purchase_order_id']; ?></td>
                        <td><?php echo htmlspecialchars($order['branch_name']); ?></td>
                        <td><?php echo formatCurrency($order['total_amount']); ?></td>
                        <td><span class="order-status <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                        <td><?php echo formatDate($order['expected_delivery']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p class="text-muted text-center">No recent purchase orders</p>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($_SESSION['role'] === ROLE_CUSTOMER): ?>
<!-- Customer Dashboard -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card dashboard-card">
            <div class="card-body text-center">
                <div class="membership-badge <?php echo strtolower($customerInfo['membership_level']); ?> mb-2">
                    <?php echo $customerInfo['membership_level']; ?> Member
                </div>
                <h4><?php echo htmlspecialchars($_SESSION['full_name']); ?></h4>
                <p class="text-muted mb-2"><?php echo htmlspecialchars($customerInfo['email']); ?></p>
                <p class="mb-0">Total Spent: <strong><?php echo formatCurrency($customerInfo['total_spent']); ?></strong></p>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-4">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Orders</h6>
                                <h3 class="mb-0"><?php echo $stats['total_orders']; ?></h3>
                            </div>
                            <i class="bi bi-bag card-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card stat-card info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Active Orders</h6>
                                <h3 class="mb-0"><?php echo $stats['active_orders']; ?></h3>
                            </div>
                            <i class="bi bi-truck card-icon text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card stat-card success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">My Reviews</h6>
                                <h3 class="mb-0"><?php echo $stats['reviews']; ?></h3>
                            </div>
                            <i class="bi bi-star card-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body">
                <h6>Membership Benefits</h6>
                <div class="progress mb-2" style="height: 20px;">
                    <?php
                    $nextLevel = '';
                    $progress = 0;
                    $spent = $customerInfo['total_spent'];
                    if ($spent < 1000) {
                        $nextLevel = 'Silver ($1,000)';
                        $progress = ($spent / 1000) * 100;
                    } elseif ($spent < 5000) {
                        $nextLevel = 'Gold ($5,000)';
                        $progress = (($spent - 1000) / 4000) * 100;
                    } elseif ($spent < 10000) {
                        $nextLevel = 'Platinum ($10,000)';
                        $progress = (($spent - 5000) / 5000) * 100;
                    } else {
                        $nextLevel = 'Maximum Level';
                        $progress = 100;
                    }
                    ?>
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%">
                        <?php echo round($progress); ?>%
                    </div>
                </div>
                <small class="text-muted">Progress to <?php echo $nextLevel; ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
                <a href="views/customer/products.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-shop"></i> Continue Shopping
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentOrders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td><span class="order-status <?php echo strtolower($order['status']); ?>"><?php echo $order['status']; ?></span></td>
                                <td><?php echo formatDateTime($order['order_date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-bag"></i>
                    <p>You haven't placed any orders yet.</p>
                    <a href="views/customer/products.php" class="btn btn-primary">Start Shopping</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="views/customer/products.php" class="btn btn-primary">
                        <i class="bi bi-shop"></i> Browse Products
                    </a>
                    <a href="views/customer/orders.php" class="btn btn-outline-primary">
                        <i class="bi bi-bag"></i> View All Orders
                    </a>
                    <a href="views/customer/reviews.php" class="btn btn-outline-primary">
                        <i class="bi bi-star"></i> My Reviews
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
