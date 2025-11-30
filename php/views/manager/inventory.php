<?php
/**
 * Inventory Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Inventory Management';
$db = new Database();
$conn = $db->getConnection();

$message = '';
$error = '';

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ?, min_stock_level = ?, max_stock_level = ?,
                                last_restocked = CURRENT_TIMESTAMP WHERE inventory_id = ?");
        $stmt->execute([
            $_POST['quantity'],
            $_POST['min_stock_level'],
            $_POST['max_stock_level'],
            $_POST['inventory_id']
        ]);
        $message = 'Stock updated successfully!';
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Filters
$filterBranch = $_GET['branch'] ?? '';
$filterProduct = $_GET['product'] ?? '';
$filterStatus = $_GET['stock_status'] ?? '';

$sql = "SELECT i.*, p.name AS product_name, p.sku, p.unit_price, p.cost_price,
        c.name AS category_name, s.name AS supplier_name, b.name AS branch_name,
        (i.quantity * p.cost_price) AS inventory_value
        FROM inventory i
        JOIN product p ON i.product_id = p.product_id
        JOIN category c ON p.category_id = c.category_id
        JOIN supplier s ON p.supplier_id = s.supplier_id
        JOIN branch b ON i.branch_id = b.branch_id
        WHERE p.is_active = TRUE";
$params = [];

if ($filterBranch) {
    $sql .= " AND i.branch_id = ?";
    $params[] = $filterBranch;
}
if ($filterProduct) {
    $sql .= " AND i.product_id = ?";
    $params[] = $filterProduct;
}
if ($filterStatus === 'low') {
    $sql .= " AND i.quantity <= i.min_stock_level";
} elseif ($filterStatus === 'out') {
    $sql .= " AND i.quantity = 0";
} elseif ($filterStatus === 'over') {
    $sql .= " AND i.quantity >= i.max_stock_level";
}
$sql .= " ORDER BY b.name, p.name";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$inventory = $stmt->fetchAll();

// Get branches and products for filters
$branches = $conn->query("SELECT * FROM branch WHERE is_active = TRUE ORDER BY name")->fetchAll();
$products = $conn->query("SELECT product_id, name FROM product WHERE is_active = TRUE ORDER BY name")->fetchAll();

// Statistics
$stats = [
    'total_items' => count($inventory),
    'low_stock' => 0,
    'out_of_stock' => 0,
    'total_value' => 0
];

foreach ($inventory as $item) {
    $stats['total_value'] += $item['inventory_value'] ?? 0;
    if ($item['quantity'] == 0) {
        $stats['out_of_stock']++;
    } elseif ($item['quantity'] <= $item['min_stock_level']) {
        $stats['low_stock']++;
    }
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-boxes"></i> Inventory Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Inventory</li>
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

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Total SKUs</h6>
                <h3><?php echo number_format($stats['total_items']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Low Stock</h6>
                <h3><?php echo number_format($stats['low_stock']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">Out of Stock</h6>
                <h3><?php echo number_format($stats['out_of_stock']); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Total Value</h6>
                <h3><?php echo formatCurrency($stats['total_value']); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
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
        <div class="col-md-3">
            <select name="stock_status" class="form-select">
                <option value="">All Stock Levels</option>
                <option value="low" <?php echo $filterStatus === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                <option value="out" <?php echo $filterStatus === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                <option value="over" <?php echo $filterStatus === 'over' ? 'selected' : ''; ?>>Overstocked</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-outline-primary me-2">Filter</button>
            <a href="inventory.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Inventory Table -->
<div class="table-container">
    <table class="table table-hover" id="inventoryTable">
        <thead>
            <tr>
                <th>Branch</th>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Min/Max</th>
                <th>Status</th>
                <th>Value</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inventory as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['branch_name']); ?></td>
                <td><code><?php echo htmlspecialchars($item['sku']); ?></code></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                <td><strong><?php echo $item['quantity']; ?></strong></td>
                <td><?php echo $item['min_stock_level']; ?> / <?php echo $item['max_stock_level']; ?></td>
                <td>
                    <?php if ($item['quantity'] == 0): ?>
                    <span class="stock-status out-of-stock">Out of Stock</span>
                    <?php elseif ($item['quantity'] <= $item['min_stock_level']): ?>
                    <span class="stock-status low-stock">Low Stock</span>
                    <?php elseif ($item['quantity'] >= $item['max_stock_level']): ?>
                    <span class="badge bg-info">Overstocked</span>
                    <?php else: ?>
                    <span class="stock-status in-stock">Normal</span>
                    <?php endif; ?>
                </td>
                <td><?php echo formatCurrency($item['inventory_value'] ?? 0); ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                            data-id="<?php echo $item['inventory_id']; ?>"
                            data-product="<?php echo htmlspecialchars($item['product_name']); ?>"
                            data-branch="<?php echo htmlspecialchars($item['branch_name']); ?>"
                            data-quantity="<?php echo $item['quantity']; ?>"
                            data-min="<?php echo $item['min_stock_level']; ?>"
                            data-max="<?php echo $item['max_stock_level']; ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="inventory_id" id="edit_inventory_id">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong id="edit_product"></strong> at <span id="edit_branch"></span></p>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="quantity" id="edit_quantity" class="form-control" min="0" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Min Stock Level</label>
                            <input type="number" name="min_stock_level" id="edit_min" class="form-control" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Stock Level</label>
                            <input type="number" name="max_stock_level" id="edit_max" class="form-control" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    document.getElementById('edit_inventory_id').value = button.dataset.id;
    document.getElementById('edit_product').textContent = button.dataset.product;
    document.getElementById('edit_branch').textContent = button.dataset.branch;
    document.getElementById('edit_quantity').value = button.dataset.quantity;
    document.getElementById('edit_min').value = button.dataset.min;
    document.getElementById('edit_max').value = button.dataset.max;
});
</script>

<?php include '../../includes/footer.php'; ?>
