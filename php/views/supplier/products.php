<?php
/**
 * Supplier - My Products
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_SUPPLIER]);

$pageTitle = 'My Products';
$db = new Database();
$conn = $db->getConnection();

$supplierId = $_SESSION['supplier_id'];

$products = $conn->prepare("SELECT p.*, c.name AS category_name,
                            COALESCE(SUM(i.quantity), 0) AS total_stock,
                            (SELECT ROUND(AVG(rating), 1) FROM review WHERE product_id = p.product_id) AS avg_rating,
                            (SELECT COUNT(*) FROM review WHERE product_id = p.product_id) AS review_count
                            FROM product p
                            JOIN category c ON p.category_id = c.category_id
                            LEFT JOIN inventory i ON p.product_id = i.product_id
                            WHERE p.supplier_id = ?
                            GROUP BY p.product_id
                            ORDER BY p.name");
$products->execute([$supplierId]);
$products = $products->fetchAll();

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-box-seam"></i> My Products</h1>
    </div>
</div>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th>Unit Price</th>
                <th>Cost Price</th>
                <th>Total Stock</th>
                <th>Rating</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><code><?php echo htmlspecialchars($product['sku']); ?></code></td>
                <td>
                    <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)); ?>...</small>
                </td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td><?php echo formatCurrency($product['unit_price']); ?></td>
                <td><?php echo formatCurrency($product['cost_price']); ?></td>
                <td><span class="badge bg-info"><?php echo $product['total_stock']; ?></span></td>
                <td>
                    <?php if ($product['avg_rating']): ?>
                    <span class="text-warning"><i class="bi bi-star-fill"></i></span>
                    <?php echo $product['avg_rating']; ?>
                    <small class="text-muted">(<?php echo $product['review_count']; ?>)</small>
                    <?php else: ?>
                    <span class="text-muted">No reviews</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
