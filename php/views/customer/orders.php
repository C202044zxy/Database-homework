<?php
/**
 * Customer - My Orders
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_CUSTOMER]);

$pageTitle = 'My Orders';
$db = new Database();
$conn = $db->getConnection();

$customerId = $_SESSION['customer_id'];

$orders = $conn->prepare("SELECT co.*, b.name AS branch_name, p.payment_method, p.status AS payment_status
                          FROM customer_order co
                          JOIN branch b ON co.branch_id = b.branch_id
                          LEFT JOIN payment p ON co.order_id = p.order_id
                          WHERE co.customer_id = ?
                          ORDER BY co.order_date DESC");
$orders->execute([$customerId]);
$orders = $orders->fetchAll();

// View order detail
$viewOrder = null;
$orderItems = [];
if (isset($_GET['view'])) {
    $stmt = $conn->prepare("SELECT co.*, b.name AS branch_name, b.location, p.payment_method, p.status AS payment_status
                            FROM customer_order co
                            JOIN branch b ON co.branch_id = b.branch_id
                            LEFT JOIN payment p ON co.order_id = p.order_id
                            WHERE co.order_id = ? AND co.customer_id = ?");
    $stmt->execute([$_GET['view'], $customerId]);
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

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-bag"></i> My Orders</h1>
    </div>
</div>

<?php if ($viewOrder): ?>
<!-- Order Detail -->
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Order #<?php echo $viewOrder['order_id']; ?></h5>
        <a href="orders.php" class="btn btn-outline-secondary btn-sm">Back to Orders</a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p>
                    <strong>Order Date:</strong> <?php echo formatDateTime($viewOrder['order_date']); ?><br>
                    <strong>Status:</strong>
                    <span class="order-status <?php echo strtolower($viewOrder['status']); ?>">
                        <?php echo $viewOrder['status']; ?>
                    </span><br>
                    <strong>Payment:</strong>
                    <?php echo $viewOrder['payment_method'] ?? 'N/A'; ?>
                    <span class="badge bg-<?php echo $viewOrder['payment_status'] === 'Completed' ? 'success' : 'warning'; ?>">
                        <?php echo $viewOrder['payment_status'] ?? 'Pending'; ?>
                    </span>
                </p>
            </div>
            <div class="col-md-6">
                <p>
                    <strong>Branch:</strong> <?php echo htmlspecialchars($viewOrder['branch_name']); ?><br>
                    <strong>Location:</strong> <?php echo htmlspecialchars($viewOrder['location']); ?><br>
                    <strong>Shipping:</strong> <?php echo htmlspecialchars($viewOrder['shipping_address']); ?>
                </p>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th>Review</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td><?php echo formatCurrency($item['unit_price']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo formatCurrency($item['subtotal']); ?></td>
                    <td>
                        <?php if ($viewOrder['status'] === 'Delivered'): ?>
                        <a href="reviews.php?product=<?php echo $item['product_id']; ?>&order=<?php echo $viewOrder['order_id']; ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-star"></i> Review
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end">Subtotal:</td>
                    <td><?php echo formatCurrency($viewOrder['subtotal']); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Discount:</td>
                    <td>-<?php echo formatCurrency($viewOrder['discount_amount']); ?></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Tax:</td>
                    <td><?php echo formatCurrency($viewOrder['tax_amount']); ?></td>
                    <td></td>
                </tr>
                <tr class="table-dark">
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td><strong><?php echo formatCurrency($viewOrder['total_amount']); ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Orders List -->
<?php if (empty($orders)): ?>
<div class="empty-state">
    <i class="bi bi-bag"></i>
    <p>You haven't placed any orders yet</p>
    <a href="products.php" class="btn btn-primary">Start Shopping</a>
</div>
<?php else: ?>
<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Date</th>
                <th>Branch</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                <td><?php echo formatDateTime($order['order_date']); ?></td>
                <td><?php echo htmlspecialchars($order['branch_name']); ?></td>
                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                <td>
                    <span class="order-status <?php echo strtolower($order['status']); ?>">
                        <?php echo $order['status']; ?>
                    </span>
                </td>
                <td>
                    <a href="?view=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
