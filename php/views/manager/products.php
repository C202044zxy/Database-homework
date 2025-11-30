<?php
/**
 * Product Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Product Management';
$db = new Database();
$conn = $db->getConnection();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create':
                $stmt = $conn->prepare("INSERT INTO product (category_id, supplier_id, name, description, sku,
                                        unit_price, cost_price, weight, dimensions, image_url)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['category_id'],
                    $_POST['supplier_id'],
                    sanitize($_POST['name']),
                    sanitize($_POST['description']),
                    sanitize($_POST['sku']),
                    $_POST['unit_price'],
                    $_POST['cost_price'] ?: null,
                    $_POST['weight'] ?: null,
                    sanitize($_POST['dimensions']),
                    sanitize($_POST['image_url'])
                ]);
                $message = 'Product created successfully!';
                break;

            case 'update':
                $stmt = $conn->prepare("UPDATE product SET category_id = ?, supplier_id = ?, name = ?, description = ?,
                                        sku = ?, unit_price = ?, cost_price = ?, weight = ?, dimensions = ?,
                                        image_url = ?, is_active = ? WHERE product_id = ?");
                $stmt->execute([
                    $_POST['category_id'],
                    $_POST['supplier_id'],
                    sanitize($_POST['name']),
                    sanitize($_POST['description']),
                    sanitize($_POST['sku']),
                    $_POST['unit_price'],
                    $_POST['cost_price'] ?: null,
                    $_POST['weight'] ?: null,
                    sanitize($_POST['dimensions']),
                    sanitize($_POST['image_url']),
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['product_id']
                ]);
                $message = 'Product updated successfully!';
                break;

            case 'delete':
                $stmt = $conn->prepare("UPDATE product SET is_active = FALSE WHERE product_id = ?");
                $stmt->execute([$_POST['product_id']]);
                $message = 'Product deactivated successfully!';
                break;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch products with filters
$filterCategory = $_GET['category'] ?? '';
$filterSupplier = $_GET['supplier'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, c.name AS category_name, s.name AS supplier_name,
        (SELECT COALESCE(SUM(quantity), 0) FROM inventory WHERE product_id = p.product_id) AS total_stock,
        (SELECT ROUND(AVG(rating), 1) FROM review WHERE product_id = p.product_id AND is_approved = TRUE) AS avg_rating
        FROM product p
        JOIN category c ON p.category_id = c.category_id
        JOIN supplier s ON p.supplier_id = s.supplier_id
        WHERE 1=1";
$params = [];

if ($filterCategory) {
    $sql .= " AND p.category_id = ?";
    $params[] = $filterCategory;
}
if ($filterSupplier) {
    $sql .= " AND p.supplier_id = ?";
    $params[] = $filterSupplier;
}
if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.name";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories and suppliers for dropdowns
$categories = $conn->query("SELECT * FROM category WHERE is_active = TRUE ORDER BY name")->fetchAll();
$suppliers = $conn->query("SELECT * FROM supplier WHERE cooperation_status = 'Active' ORDER BY name")->fetchAll();

// For editing
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM product WHERE product_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-box-seam"></i> Product Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Products</li>
            </ol>
        </nav>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
            <i class="bi bi-plus"></i> Add Product
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter -->
<div class="search-filter">
    <form method="GET" class="row g-3">
        <div class="col-md-3">
            <input type="text" name="search" class="form-control" placeholder="Search products..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo $filterCategory == $cat['category_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="supplier" class="form-select">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $sup): ?>
                <option value="<?php echo $sup['supplier_id']; ?>" <?php echo $filterSupplier == $sup['supplier_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($sup['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-outline-primary me-2">Filter</button>
            <a href="products.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Products Table -->
<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Price</th>
                <th>Cost</th>
                <th>Stock</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><code><?php echo htmlspecialchars($product['sku']); ?></code></td>
                <td>
                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                </td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td><?php echo htmlspecialchars($product['supplier_name']); ?></td>
                <td><?php echo formatCurrency($product['unit_price']); ?></td>
                <td><?php echo $product['cost_price'] ? formatCurrency($product['cost_price']) : '-'; ?></td>
                <td>
                    <?php if ($product['total_stock'] == 0): ?>
                    <span class="stock-status out-of-stock">Out of Stock</span>
                    <?php elseif ($product['total_stock'] < 20): ?>
                    <span class="stock-status low-stock"><?php echo $product['total_stock']; ?></span>
                    <?php else: ?>
                    <span class="stock-status in-stock"><?php echo $product['total_stock']; ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($product['avg_rating']): ?>
                    <span class="text-warning"><i class="bi bi-star-fill"></i></span>
                    <?php echo $product['avg_rating']; ?>
                    <?php else: ?>
                    <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($product['is_active']): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="action-buttons">
                    <a href="?edit=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a href="inventory.php?product=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-info" title="View Stock">
                        <i class="bi bi-boxes"></i>
                    </a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this product?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="<?php echo $editProduct ? 'update' : 'create'; ?>">
                <?php if ($editProduct): ?>
                <input type="hidden" name="product_id" value="<?php echo $editProduct['product_id']; ?>">
                <?php endif; ?>

                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Product Name *</label>
                            <input type="text" name="name" class="form-control" required
                                   value="<?php echo $editProduct ? htmlspecialchars($editProduct['name']) : ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">SKU *</label>
                            <input type="text" name="sku" class="form-control" required
                                   value="<?php echo $editProduct ? htmlspecialchars($editProduct['sku']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"
                                    <?php echo ($editProduct && $editProduct['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier *</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">Select Supplier...</option>
                                <?php foreach ($suppliers as $sup): ?>
                                <option value="<?php echo $sup['supplier_id']; ?>"
                                    <?php echo ($editProduct && $editProduct['supplier_id'] == $sup['supplier_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sup['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Unit Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="unit_price" class="form-control" step="0.01" min="0" required
                                       value="<?php echo $editProduct ? $editProduct['unit_price'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Cost Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="cost_price" class="form-control" step="0.01" min="0"
                                       value="<?php echo $editProduct ? $editProduct['cost_price'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" name="weight" class="form-control" step="0.01" min="0"
                                   value="<?php echo $editProduct ? $editProduct['weight'] : ''; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Dimensions</label>
                            <input type="text" name="dimensions" class="form-control" placeholder="LxWxH"
                                   value="<?php echo $editProduct ? htmlspecialchars($editProduct['dimensions']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Image URL</label>
                        <input type="text" name="image_url" class="form-control"
                               value="<?php echo $editProduct ? htmlspecialchars($editProduct['image_url']) : ''; ?>">
                    </div>

                    <?php if ($editProduct): ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive"
                                   <?php echo $editProduct['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> <?php echo $editProduct ? 'Update' : 'Create'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editProduct): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('productModal')).show();
});
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
