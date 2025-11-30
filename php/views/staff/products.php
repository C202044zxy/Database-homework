<?php
/**
 * Staff - Product Catalog
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_STAFF]);

$pageTitle = 'Products';
$db = new Database();
$conn = $db->getConnection();

$branchId = $_SESSION['branch_id'];
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT p.*, c.name AS category_name, i.quantity AS stock,
        COALESCE(AVG(r.rating), 0) AS avg_rating
        FROM product p
        JOIN category c ON p.category_id = c.category_id
        LEFT JOIN inventory i ON p.product_id = i.product_id AND i.branch_id = ?
        LEFT JOIN review r ON p.product_id = r.product_id AND r.is_approved = TRUE
        WHERE p.is_active = TRUE";
$params = [$branchId];

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
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

$categories = $conn->query("SELECT * FROM category WHERE is_active = TRUE ORDER BY name")->fetchAll();

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-box-seam"></i> Products</h1>
    </div>
</div>

<div class="search-filter">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search products..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock (This Branch)</th>
                <th>Rating</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><code><?php echo htmlspecialchars($product['sku']); ?></code></td>
                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td><?php echo formatCurrency($product['unit_price']); ?></td>
                <td>
                    <?php
                    $stock = $product['stock'] ?? 0;
                    if ($stock == 0) {
                        echo '<span class="stock-status out-of-stock">Out of Stock</span>';
                    } elseif ($stock < 10) {
                        echo '<span class="stock-status low-stock">' . $stock . '</span>';
                    } else {
                        echo '<span class="stock-status in-stock">' . $stock . '</span>';
                    }
                    ?>
                </td>
                <td>
                    <?php if ($product['avg_rating'] > 0): ?>
                    <span class="text-warning"><i class="bi bi-star-fill"></i></span>
                    <?php echo number_format($product['avg_rating'], 1); ?>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
