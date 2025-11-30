<?php
/**
 * Category Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Category Management';
$db = new Database();
$conn = $db->getConnection();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $stmt = $conn->prepare("INSERT INTO category (name, description, parent_category_id) VALUES (?, ?, ?)");
                $stmt->execute([
                    sanitize($_POST['name']),
                    sanitize($_POST['description']),
                    $_POST['parent_category_id'] ?: null
                ]);
                $message = 'Category created!';
                break;
            case 'update':
                $stmt = $conn->prepare("UPDATE category SET name = ?, description = ?, parent_category_id = ?, is_active = ? WHERE category_id = ?");
                $stmt->execute([
                    sanitize($_POST['name']),
                    sanitize($_POST['description']),
                    $_POST['parent_category_id'] ?: null,
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['category_id']
                ]);
                $message = 'Category updated!';
                break;
        }
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

$categories = $conn->query("SELECT c.*, p.name AS parent_name,
                            (SELECT COUNT(*) FROM product WHERE category_id = c.category_id) AS product_count
                            FROM category c
                            LEFT JOIN category p ON c.parent_category_id = p.category_id
                            ORDER BY COALESCE(p.name, c.name), c.name")->fetchAll();

$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM category WHERE category_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-tags"></i> Category Management</h1>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
            <i class="bi bi-plus"></i> Add Category
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category</th>
                <th>Parent</th>
                <th>Products</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><?php echo $cat['category_id']; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($cat['name']); ?></strong>
                    <?php if ($cat['description']): ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($cat['description'], 0, 50)); ?>...</small>
                    <?php endif; ?>
                </td>
                <td><?php echo $cat['parent_name'] ? htmlspecialchars($cat['parent_name']) : '<span class="text-muted">Root</span>'; ?></td>
                <td><span class="badge bg-info"><?php echo $cat['product_count']; ?></span></td>
                <td>
                    <span class="badge bg-<?php echo $cat['is_active'] ? 'success' : 'secondary'; ?>">
                        <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </td>
                <td>
                    <a href="?edit=<?php echo $cat['category_id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="<?php echo $editCategory ? 'update' : 'create'; ?>">
                <?php if ($editCategory): ?>
                <input type="hidden" name="category_id" value="<?php echo $editCategory['category_id']; ?>">
                <?php endif; ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editCategory ? 'Edit' : 'Add'; ?> Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_category_id" class="form-select">
                            <option value="">None (Root Category)</option>
                            <?php foreach ($categories as $cat): ?>
                            <?php if (!$editCategory || $cat['category_id'] != $editCategory['category_id']): ?>
                            <option value="<?php echo $cat['category_id']; ?>"
                                <?php echo ($editCategory && $editCategory['parent_category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($editCategory): ?>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive"
                               <?php echo $editCategory['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editCategory): ?>
<script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('categoryModal')).show());</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
