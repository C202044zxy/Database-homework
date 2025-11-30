<?php
/**
 * Customer - Shop Products
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_CUSTOMER]);

$pageTitle = 'Shop';
$db = new Database();
$conn = $db->getConnection();

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT p.*, c.name AS category_name,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.review_id) AS review_count,
        (SELECT SUM(quantity) FROM inventory WHERE product_id = p.product_id) AS total_stock
        FROM product p
        JOIN category c ON p.category_id = c.category_id
        LEFT JOIN review r ON p.product_id = r.product_id AND r.is_approved = TRUE
        WHERE p.is_active = TRUE";
$params = [];

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}
$sql .= " GROUP BY p.product_id ORDER BY p.name";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $conn->query("SELECT * FROM category WHERE is_active = TRUE AND parent_category_id IS NULL ORDER BY name")->fetchAll();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }

    setFlashMessage('success', 'Product added to cart!');
    header('Location: products.php');
    exit;
}

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-shop"></i> Shop</h1>
    </div>
    <div class="col-auto">
        <a href="cart.php" class="btn btn-outline-primary">
            <i class="bi bi-cart3"></i> Cart
            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
            <span class="badge bg-danger"><?php echo array_sum($_SESSION['cart']); ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<div class="search-filter">
    <form method="GET" class="row g-3">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Search products..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-4">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="products.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="row">
    <?php foreach ($products as $product): ?>
    <div class="col-md-4 col-lg-3 mb-4">
        <div class="card product-card">
            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                <i class="bi bi-box-seam display-4 text-muted"></i>
            </div>
            <div class="card-body">
                <h6 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                <p class="text-muted small mb-2"><?php echo htmlspecialchars($product['category_name']); ?></p>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="product-price"><?php echo formatCurrency($product['unit_price']); ?></span>
                    <?php if ($product['avg_rating'] > 0): ?>
                    <span class="product-rating">
                        <i class="bi bi-star-fill"></i> <?php echo number_format($product['avg_rating'], 1); ?>
                        <small>(<?php echo $product['review_count']; ?>)</small>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($product['total_stock'] > 0): ?>
                <form method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <div class="input-group input-group-sm">
                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="10">
                        <button type="submit" name="add_to_cart" class="btn btn-primary">
                            <i class="bi bi-cart-plus"></i>
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <button class="btn btn-secondary btn-sm w-100" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($products)): ?>
<div class="empty-state">
    <i class="bi bi-search"></i>
    <p>No products found</p>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
