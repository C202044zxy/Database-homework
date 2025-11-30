<?php
/**
 * Staff - Orders
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_STAFF]);

$pageTitle = 'Orders';
$db = new Database();
$conn = $db->getConnection();

$branchId = $_SESSION['branch_id'];
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $stmt = $conn->prepare("UPDATE customer_order SET status = ? WHERE order_id = ? AND branch_id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id'], $branchId]);
    $message = 'Order status updated!';
}

$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT co.*, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, c.phone AS customer_phone,
        p.payment_method, p.status AS payment_status
        FROM customer_order co
        JOIN customer c ON co.customer_id = c.customer_id
        LEFT JOIN payment p ON co.order_id = p.order_id
        WHERE co.branch_id = ?";
$params = [$branchId];

if ($filterStatus) {
    $sql .= " AND co.status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY co.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-cart"></i> Orders</h1>
    </div>
    <div class="col-auto">
        <a href="new_order.php" class="btn btn-primary">
            <i class="bi bi-plus"></i> New Sale
        </a>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="search-filter">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (ORDER_STATUS as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo $filterStatus === $status ? 'selected' : ''; ?>>
                    <?php echo $status; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
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
                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                </td>
                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                <td>
                    <span class="badge bg-<?php echo $order['payment_status'] === 'Completed' ? 'success' : 'warning'; ?>">
                        <?php echo $order['payment_method'] ?? 'Pending'; ?>
                    </span>
                </td>
                <td>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                            <?php foreach (ORDER_STATUS as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
                <td><?php echo formatDateTime($order['order_date']); ?></td>
                <td>
                    <a href="order_detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
