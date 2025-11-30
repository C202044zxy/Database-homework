<?php
/**
 * Staff - Branch Inventory
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_STAFF]);

$pageTitle = 'Inventory';
$db = new Database();
$conn = $db->getConnection();

$branchId = $_SESSION['branch_id'];

// Get branch name
$stmt = $conn->prepare("SELECT name FROM branch WHERE branch_id = ?");
$stmt->execute([$branchId]);
$branchName = $stmt->fetchColumn();

$inventory = $conn->prepare("SELECT i.*, p.name AS product_name, p.sku, p.unit_price, c.name AS category_name
                             FROM inventory i
                             JOIN product p ON i.product_id = p.product_id
                             JOIN category c ON p.category_id = c.category_id
                             WHERE i.branch_id = ? AND p.is_active = TRUE
                             ORDER BY p.name");
$inventory->execute([$branchId]);
$items = $inventory->fetchAll();

// Stats
$stats = ['total' => 0, 'low' => 0, 'out' => 0];
foreach ($items as $item) {
    $stats['total']++;
    if ($item['quantity'] == 0) $stats['out']++;
    elseif ($item['quantity'] <= $item['min_stock_level']) $stats['low']++;
}

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-boxes"></i> Inventory - <?php echo htmlspecialchars($branchName); ?></h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Total Products</h6>
                <h3><?php echo $stats['total']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Low Stock</h6>
                <h3><?php echo $stats['low']; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">Out of Stock</h6>
                <h3><?php echo $stats['out']; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Min Level</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr class="<?php echo $item['quantity'] <= $item['min_stock_level'] ? 'table-warning' : ''; ?>">
                <td><code><?php echo htmlspecialchars($item['sku']); ?></code></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                <td><?php echo formatCurrency($item['unit_price']); ?></td>
                <td><strong><?php echo $item['quantity']; ?></strong></td>
                <td><?php echo $item['min_stock_level']; ?></td>
                <td>
                    <?php if ($item['quantity'] == 0): ?>
                    <span class="stock-status out-of-stock">Out of Stock</span>
                    <?php elseif ($item['quantity'] <= $item['min_stock_level']): ?>
                    <span class="stock-status low-stock">Low Stock</span>
                    <?php else: ?>
                    <span class="stock-status in-stock">In Stock</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
