<?php
/**
 * Staff - New Order/Sale
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_STAFF]);

$pageTitle = 'New Sale';
$db = new Database();
$conn = $db->getConnection();

$branchId = $_SESSION['branch_id'];
$employeeId = $_SESSION['employee_id'];
$message = '';
$error = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $conn->beginTransaction();

        // Create order
        $stmt = $conn->prepare("INSERT INTO customer_order (customer_id, branch_id, employee_id, shipping_address, status)
                                VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([
            $_POST['customer_id'],
            $branchId,
            $employeeId,
            sanitize($_POST['shipping_address'])
        ]);
        $orderId = $conn->lastInsertId();

        // Add order items
        $subtotal = 0;
        foreach ($_POST['products'] as $productId => $data) {
            if (empty($data['quantity']) || $data['quantity'] <= 0) continue;

            $qty = (int)$data['quantity'];

            // Get product price
            $priceStmt = $conn->prepare("SELECT unit_price FROM product WHERE product_id = ?");
            $priceStmt->execute([$productId]);
            $price = $priceStmt->fetchColumn();

            $itemSubtotal = $price * $qty;
            $subtotal += $itemSubtotal;

            $stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $productId, $qty, $price, $itemSubtotal]);

            // Update inventory
            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE branch_id = ? AND product_id = ?");
            $stmt->execute([$qty, $branchId, $productId]);
        }

        // Calculate totals
        $tax = $subtotal * 0.10;
        $total = $subtotal + $tax;

        // Update order totals
        $stmt = $conn->prepare("UPDATE customer_order SET subtotal = ?, tax_amount = ?, total_amount = ? WHERE order_id = ?");
        $stmt->execute([$subtotal, $tax, $total, $orderId]);

        // Create payment if method selected
        if (!empty($_POST['payment_method'])) {
            $stmt = $conn->prepare("INSERT INTO payment (order_id, amount, payment_method, status) VALUES (?, ?, ?, 'Completed')");
            $stmt->execute([$orderId, $total, $_POST['payment_method']]);

            $stmt = $conn->prepare("UPDATE customer_order SET status = 'Processing' WHERE order_id = ?");
            $stmt->execute([$orderId]);
        }

        $conn->commit();
        $message = "Order #$orderId created successfully!";

    } catch (PDOException $e) {
        $conn->rollBack();
        $error = 'Error creating order: ' . $e->getMessage();
    }
}

// Get customers
$customers = $conn->query("SELECT customer_id, CONCAT(first_name, ' ', last_name) AS name, email, address
                           FROM customer WHERE is_active = TRUE ORDER BY first_name")->fetchAll();

// Get products with stock
$products = $conn->prepare("SELECT p.*, c.name AS category_name, i.quantity AS stock
                            FROM product p
                            JOIN category c ON p.category_id = c.category_id
                            LEFT JOIN inventory i ON p.product_id = i.product_id AND i.branch_id = ?
                            WHERE p.is_active = TRUE AND (i.quantity > 0 OR i.quantity IS NULL)
                            ORDER BY p.name");
$products->execute([$branchId]);
$products = $products->fetchAll();

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-cart-plus"></i> New Sale</h1>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" class="needs-validation" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Select Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th width="120">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo $product['sku']; ?> | <?php echo $product['category_name']; ?></small>
                                    </td>
                                    <td><?php echo formatCurrency($product['unit_price']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($product['stock'] ?? 0) > 0 ? 'success' : 'danger'; ?>">
                                            <?php echo $product['stock'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number" name="products[<?php echo $product['product_id']; ?>][quantity]"
                                               class="form-control form-control-sm" min="0" max="<?php echo $product['stock'] ?? 0; ?>" value="0">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Customer *</label>
                        <select name="customer_id" class="form-select" required id="customerSelect">
                            <option value="">Select Customer...</option>
                            <?php foreach ($customers as $cust): ?>
                            <option value="<?php echo $cust['customer_id']; ?>" data-address="<?php echo htmlspecialchars($cust['address']); ?>">
                                <?php echo htmlspecialchars($cust['name']); ?> (<?php echo $cust['email']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shipping Address</label>
                        <textarea name="shipping_address" class="form-control" rows="3" id="shippingAddress"></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="">Pay Later</option>
                            <?php foreach (PAYMENT_METHODS as $method): ?>
                            <option value="<?php echo $method; ?>"><?php echo $method; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle"></i> Create Order
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.getElementById('customerSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    document.getElementById('shippingAddress').value = selected.dataset.address || '';
});
</script>

<?php include '../../includes/footer.php'; ?>
