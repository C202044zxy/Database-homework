<?php
/**
 * Supplier - Purchase Orders
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_SUPPLIER]);

$pageTitle = 'Purchase Orders';
$db = new Database();
$conn = $db->getConnection();

$supplierId = $_SESSION['supplier_id'];
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $stmt = $conn->prepare("UPDATE purchase_order SET status = ? WHERE purchase_order_id = ? AND supplier_id = ?");
    $stmt->execute([$_POST['status'], $_POST['po_id'], $supplierId]);
    $message = 'Order status updated!';
}

$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT po.*, b.name AS branch_name, b.location AS branch_location, b.contact_phone
        FROM purchase_order po
        JOIN branch b ON po.branch_id = b.branch_id
        WHERE po.supplier_id = ?";
$params = [$supplierId];

if ($filterStatus) {
    $sql .= " AND po.status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY po.order_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// View details
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("SELECT po.*, b.name AS branch_name, b.location, b.contact_phone
                            FROM purchase_order po
                            JOIN branch b ON po.branch_id = b.branch_id
                            WHERE po.purchase_order_id = ? AND po.supplier_id = ?");
    $stmt->execute([$_GET['view'], $supplierId]);
    $viewOrder = $stmt->fetch();

    if ($viewOrder) {
        $stmt = $conn->prepare("SELECT poi.*, p.name AS product_name, p.sku
                                FROM purchase_order_item poi
                                JOIN product p ON poi.product_id = p.product_id
                                WHERE poi.purchase_order_id = ?");
        $stmt->execute([$_GET['view']]);
        $orderItems = $stmt->fetchAll();
    }
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-file-earmark-text"></i> Purchase Orders</h1>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($viewOrder): ?>
<!-- Order Details -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">PO #<?php echo $viewOrder['purchase_order_id']; ?></h5>
        <a href="purchase_orders.php" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Delivery To:</h6>
                <p>
                    <strong><?php echo htmlspecialchars($viewOrder['branch_name']); ?></strong><br>
                    <?php echo htmlspecialchars($viewOrder['location']); ?><br>
                    Phone: <?php echo htmlspecialchars($viewOrder['contact_phone']); ?>
                </p>
            </div>
            <div class="col-md-6">
                <h6>Order Details:</h6>
                <p>
                    <strong>Order Date:</strong> <?php echo formatDate($viewOrder['order_date']); ?><br>
                    <strong>Expected Delivery:</strong> <?php echo formatDate($viewOrder['expected_delivery']); ?><br>
                    <strong>Status:</strong>
                    <span class="badge bg-<?php echo $viewOrder['status'] === 'Received' ? 'success' : ($viewOrder['status'] === 'Cancelled' ? 'danger' : 'warning'); ?>">
                        <?php echo $viewOrder['status']; ?>
                    </span>
                </p>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Unit Cost</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><code><?php echo $item['sku']; ?></code></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo formatCurrency($item['unit_cost']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo formatCurrency($item['subtotal']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-dark">
                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                    <td><strong><?php echo formatCurrency($viewOrder['total_amount']); ?></strong></td>
                </tr>
            </tfoot>
        </table>

        <?php if (in_array($viewOrder['status'], ['Submitted', 'Confirmed'])): ?>
        <form method="POST" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="po_id" value="<?php echo $viewOrder['purchase_order_id']; ?>">
            <select name="status" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                <option value="Confirmed" <?php echo $viewOrder['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirm Order</option>
                <option value="Shipped" <?php echo $viewOrder['status'] === 'Shipped' ? 'selected' : ''; ?>>Mark as Shipped</option>
            </select>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Orders List -->
<div class="search-filter">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['Draft', 'Submitted', 'Confirmed', 'Shipped', 'Received', 'Cancelled'] as $status): ?>
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
                <th>PO #</th>
                <th>Branch</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Order Date</th>
                <th>Expected</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong>#<?php echo $order['purchase_order_id']; ?></strong></td>
                <td>
                    <?php echo htmlspecialchars($order['branch_name']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($order['branch_location']); ?></small>
                </td>
                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                <td>
                    <span class="badge bg-<?php echo $order['status'] === 'Received' ? 'success' : ($order['status'] === 'Cancelled' ? 'danger' : 'warning'); ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </td>
                <td><?php echo formatDate($order['order_date']); ?></td>
                <td><?php echo formatDate($order['expected_delivery']); ?></td>
                <td>
                    <a href="?view=<?php echo $order['purchase_order_id']; ?>" class="btn btn-sm btn-outline-primary">
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
