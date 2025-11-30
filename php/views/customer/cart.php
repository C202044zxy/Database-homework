<?php
/**
 * Customer - Shopping Cart
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_CUSTOMER]);

$pageTitle = 'Shopping Cart';
$db = new Database();
$conn = $db->getConnection();

$customerId = $_SESSION['customer_id'];

// Get customer info for membership discount
$stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();
$discount = MEMBERSHIP_LEVELS[$customer['membership_level']]['discount'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $productId => $qty) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = (int)$qty;
            }
        }
    } elseif (isset($_POST['remove'])) {
        unset($_SESSION['cart'][$_POST['product_id']]);
    } elseif (isset($_POST['checkout']) && verifyCSRFToken($_POST['csrf_token'])) {
        // Process checkout
        try {
            $conn->beginTransaction();

            // Create order
            $stmt = $conn->prepare("INSERT INTO customer_order (customer_id, branch_id, shipping_address, status)
                                    VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$customerId, $_POST['branch_id'], sanitize($_POST['shipping_address'])]);
            $orderId = $conn->lastInsertId();

            $subtotal = 0;

            // Add items
            foreach ($_SESSION['cart'] as $productId => $qty) {
                $priceStmt = $conn->prepare("SELECT unit_price FROM product WHERE product_id = ?");
                $priceStmt->execute([$productId]);
                $price = $priceStmt->fetchColumn();

                $itemSubtotal = $price * $qty;
                $subtotal += $itemSubtotal;

                $stmt = $conn->prepare("INSERT INTO order_item (order_id, product_id, quantity, unit_price, subtotal)
                                        VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $productId, $qty, $price, $itemSubtotal]);
            }

            // Calculate totals
            $discountAmount = $subtotal * ($discount / 100);
            $taxAmount = ($subtotal - $discountAmount) * 0.10;
            $total = $subtotal - $discountAmount + $taxAmount;

            $stmt = $conn->prepare("UPDATE customer_order SET subtotal = ?, discount_amount = ?, tax_amount = ?, total_amount = ?
                                    WHERE order_id = ?");
            $stmt->execute([$subtotal, $discountAmount, $taxAmount, $total, $orderId]);

            // Create payment
            if (!empty($_POST['payment_method'])) {
                $stmt = $conn->prepare("INSERT INTO payment (order_id, amount, payment_method, status) VALUES (?, ?, ?, 'Completed')");
                $stmt->execute([$orderId, $total, $_POST['payment_method']]);

                $stmt = $conn->prepare("UPDATE customer_order SET status = 'Processing' WHERE order_id = ?");
                $stmt->execute([$orderId]);
            }

            $conn->commit();
            $_SESSION['cart'] = [];
            setFlashMessage('success', "Order #$orderId placed successfully!");
            header('Location: orders.php');
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            setFlashMessage('error', 'Error processing order: ' . $e->getMessage());
        }
    }
}

// Get cart items
$cartItems = [];
$subtotal = 0;
if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $conn->prepare("SELECT * FROM product WHERE product_id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_KEY_PAIR | PDO::FETCH_GROUP);

    foreach ($_SESSION['cart'] as $productId => $qty) {
        $stmt = $conn->prepare("SELECT * FROM product WHERE product_id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if ($product) {
            $product['cart_qty'] = $qty;
            $product['line_total'] = $product['unit_price'] * $qty;
            $subtotal += $product['line_total'];
            $cartItems[] = $product;
        }
    }
}

$discountAmount = $subtotal * ($discount / 100);
$taxAmount = ($subtotal - $discountAmount) * 0.10;
$total = $subtotal - $discountAmount + $taxAmount;

// Get branches
$branches = $conn->query("SELECT * FROM branch WHERE is_active = TRUE ORDER BY name")->fetchAll();

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-cart3"></i> Shopping Cart</h1>
    </div>
    <div class="col-auto">
        <a href="products.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Continue Shopping
        </a>
    </div>
</div>

<?php if (empty($cartItems)): ?>
<div class="empty-state">
    <i class="bi bi-cart-x"></i>
    <p>Your cart is empty</p>
    <a href="products.php" class="btn btn-primary">Start Shopping</a>
</div>

<?php else: ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" id="cartForm">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <p class="text-muted mb-0"><?php echo formatCurrency($item['unit_price']); ?> each</p>
                        </div>
                        <div class="mx-3">
                            <input type="number" name="quantity[<?php echo $item['product_id']; ?>]"
                                   class="form-control" style="width: 80px;"
                                   value="<?php echo $item['cart_qty']; ?>" min="0" max="99">
                        </div>
                        <div class="text-end" style="width: 100px;">
                            <strong><?php echo formatCurrency($item['line_total']); ?></strong>
                        </div>
                        <form method="POST" class="ms-2">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <button type="submit" name="remove" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>

                    <div class="mt-3">
                        <button type="submit" name="update_cart" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-clockwise"></i> Update Cart
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card cart-summary">
            <div class="card-body">
                <h5 class="card-title">Order Summary</h5>

                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span><?php echo formatCurrency($subtotal); ?></span>
                </div>

                <?php if ($discount > 0): ?>
                <div class="d-flex justify-content-between mb-2 text-success">
                    <span><?php echo $customer['membership_level']; ?> Discount (<?php echo $discount; ?>%):</span>
                    <span>-<?php echo formatCurrency($discountAmount); ?></span>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mb-2">
                    <span>Tax (10%):</span>
                    <span><?php echo formatCurrency($taxAmount); ?></span>
                </div>

                <hr>

                <div class="d-flex justify-content-between mb-3">
                    <strong>Total:</strong>
                    <strong class="text-primary"><?php echo formatCurrency($total); ?></strong>
                </div>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                    <div class="mb-3">
                        <label class="form-label">Pickup Branch *</label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">Select Branch...</option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo $branch['branch_id']; ?>">
                                <?php echo htmlspecialchars($branch['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Shipping Address</label>
                        <textarea name="shipping_address" class="form-control" rows="2"><?php echo htmlspecialchars($customer['address']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment Method *</label>
                        <select name="payment_method" class="form-select" required>
                            <?php foreach (PAYMENT_METHODS as $method): ?>
                            <option value="<?php echo $method; ?>"><?php echo $method; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="checkout" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Place Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
