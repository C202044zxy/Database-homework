<?php
/**
 * Order Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Order Management';
$db = new Database();
$conn = $db->getConnection();

$message = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $conn->prepare("UPDATE customer_order SET status = ? WHERE order_id = ?");
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        $message = 'Order status updated successfully!';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Filters
$filterBranch = $_GET['branch'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';

$sql = "SELECT co.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, c.email AS customer_email,
        b.name AS branch_name, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
        p.payment_method, p.status AS payment_status
        FROM customer_order co
        JOIN customer c ON co.customer_id = c.customer_id
        JOIN branch b ON co.branch_id = b.branch_id
        LEFT JOIN employee e ON co.employee_id = e.employee_id
        LEFT JOIN payment p ON co.order_id = p.order_id
        WHERE 1=1";
$params = [];

if ($filterBranch) {
    $sql .= " AND co.branch_id = ?";
    $params[] = $filterBranch;
}
if ($filterStatus) {
    $sql .= " AND co.status = ?";
    $params[] = $filterStatus;
}
if ($filterDate) {
    $sql .= " AND DATE(co.order_date) = ?";
    $params[] = $filterDate;
}
$sql .= " ORDER BY co.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get branches for filter
$branches = $conn->query("SELECT * FROM branch ORDER BY name")->fetchAll();

// View order details
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("SELECT co.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                            c.email AS customer_email, c.phone AS customer_phone, c.address AS customer_address,
                            c.membership_level, b.name AS branch_name, b.location AS branch_location,
                            CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                            p.payment_id, p.payment_method, p.status AS payment_status, p.transaction_reference
                            FROM customer_order co
                            JOIN customer c ON co.customer_id = c.customer_id
                            JOIN branch b ON co.branch_id = b.branch_id
                            LEFT JOIN employee e ON co.employee_id = e.employee_id
                            LEFT JOIN payment p ON co.order_id = p.order_id
                            WHERE co.order_id = ?");
    $stmt->execute([$_GET['view']]);
    $viewOrder = $stmt->fetch();

    if ($viewOrder) {
        $stmt = $conn->prepare("SELECT oi.*, p.name AS product_name, p.sku
                                FROM order_item oi
                                JOIN product p ON oi.product_id = p.product_id
                                WHERE oi.order_id = ?");
        $stmt->execute([$_GET['view']]);
        $orderItems = $stmt->fetchAll();
    }
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-cart"></i> Order Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Orders</li>
            </ol>
        </nav>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($viewOrder): ?>
<!-- Order Details View -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Order #<?php echo $viewOrder['order_id']; ?></h5>
        <a href="orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Customer Information</h6>
                <p>
                    <strong><?php echo htmlspecialchars($viewOrder['customer_name']); ?></strong>
                    <span class="membership-badge <?php echo strtolower($viewOrder['membership_level']); ?>">
                        <?php echo $viewOrder['membership_level']; ?>
                    </span><br>
                    <?php echo htmlspecialchars($viewOrder['customer_email']); ?><br>
                    <?php echo htmlspecialchars($viewOrder['customer_phone']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($viewOrder['customer_address']); ?></small>
                </p>

                <h6>Shipping Address</h6>
                <p><?php echo htmlspecialchars($viewOrder['shipping_address']); ?></p>
            </div>
            <div class="col-md-6">
                <h6>Order Information</h6>
                <table class="table table-sm">
                    <tr>
                        <th>Order Date:</th>
                        <td><?php echo formatDateTime($viewOrder['order_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Branch:</th>
                        <td><?php echo htmlspecialchars($viewOrder['branch_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Processed By:</th>
                        <td><?php echo htmlspecialchars($viewOrder['employee_name'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="order_id" value="<?php echo $viewOrder['order_id']; ?>">
                                <select name="status" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                    <?php foreach (ORDER_STATUS as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $viewOrder['status'] === $status ? 'selected' : ''; ?>>
                                        <?php echo $status; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <tr>
                        <th>Payment:</th>
                        <td>
                            <?php echo $viewOrder['payment_method'] ?? 'N/A'; ?>
                            <span class="badge <?php echo $viewOrder['payment_status'] === 'Completed' ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $viewOrder['payment_status'] ?? 'Pending'; ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <h6>Order Items</h6>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Discount</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><code><?php echo htmlspecialchars($item['sku']); ?></code></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo $item['discount_percent']; ?>%</td>
                    <td><?php echo formatCurrency($item['subtotal']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>Subtotal:</strong></td>
                    <td><?php echo formatCurrency($viewOrder['subtotal']); ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end">Discount:</td>
                    <td>-<?php echo formatCurrency($viewOrder['discount_amount']); ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end">Tax:</td>
                    <td><?php echo formatCurrency($viewOrder['tax_amount']); ?></td>
                </tr>
                <tr class="table-dark">
                    <td colspan="5" class="text-end"><strong>Total:</strong></td>
                    <td><strong><?php echo formatCurrency($viewOrder['total_amount']); ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($viewOrder['notes']): ?>
        <h6>Notes</h6>
        <p class="text-muted"><?php echo htmlspecialchars($viewOrder['notes']); ?></p>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Orders List -->
<div class="search-filter">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <select name="branch" class="form-select">
                <option value="">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                <option value="<?php echo $branch['branch_id']; ?>" <?php echo $filterBranch == $branch['branch_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($branch['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach (ORDER_STATUS as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo $filterStatus === $status ? 'selected' : ''; ?>>
                    <?php echo $status; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date" class="form-control" value="<?php echo $filterDate; ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-outline-primary me-2">Filter</button>
            <a href="orders.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Branch</th>
                <th>Amount</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                <td>
                    <?php echo htmlspecialchars($order['customer_name']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($order['branch_name']); ?></td>
                <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                <td>
                    <?php if ($order['payment_status']): ?>
                    <span class="badge <?php echo $order['payment_status'] === 'Completed' ? 'bg-success' : 'bg-warning'; ?>">
                        <?php echo $order['payment_method']; ?>
                    </span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Unpaid</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="order-status <?php echo strtolower($order['status']); ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </td>
                <td><?php echo formatDateTime($order['order_date']); ?></td>
                <td>
                    <a href="?view=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
