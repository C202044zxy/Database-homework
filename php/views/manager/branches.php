<?php
/**
 * Branch Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Branch Management';
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
                $stmt = $conn->prepare("INSERT INTO branch (name, location, contact_phone, email, opening_hour, closing_hour)
                                        VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    sanitize($_POST['name']),
                    sanitize($_POST['location']),
                    sanitize($_POST['contact_phone']),
                    sanitize($_POST['email']),
                    $_POST['opening_hour'],
                    $_POST['closing_hour']
                ]);
                $message = 'Branch created successfully!';
                break;

            case 'update':
                $stmt = $conn->prepare("UPDATE branch SET name = ?, location = ?, contact_phone = ?, email = ?,
                                        opening_hour = ?, closing_hour = ?, is_active = ? WHERE branch_id = ?");
                $stmt->execute([
                    sanitize($_POST['name']),
                    sanitize($_POST['location']),
                    sanitize($_POST['contact_phone']),
                    sanitize($_POST['email']),
                    $_POST['opening_hour'],
                    $_POST['closing_hour'],
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['branch_id']
                ]);
                $message = 'Branch updated successfully!';
                break;

            case 'delete':
                // Check if branch has employees
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM employee WHERE branch_id = ? AND is_active = TRUE");
                $checkStmt->execute([$_POST['branch_id']]);
                if ($checkStmt->fetchColumn() > 0) {
                    $error = 'Cannot delete branch with active employees.';
                } else {
                    $stmt = $conn->prepare("UPDATE branch SET is_active = FALSE WHERE branch_id = ?");
                    $stmt->execute([$_POST['branch_id']]);
                    $message = 'Branch deactivated successfully!';
                }
                break;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch branches with stats
$branches = $conn->query("SELECT b.*,
                          (SELECT COUNT(*) FROM employee WHERE branch_id = b.branch_id AND is_active = TRUE) AS employee_count,
                          (SELECT COUNT(*) FROM customer_order WHERE branch_id = b.branch_id) AS order_count
                          FROM branch b ORDER BY b.name")->fetchAll();

// For editing
$editBranch = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM branch WHERE branch_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editBranch = $stmt->fetch();
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-building"></i> Branch Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Branches</li>
            </ol>
        </nav>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#branchModal">
            <i class="bi bi-plus"></i> Add Branch
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

<!-- Search/Filter -->
<div class="search-filter">
    <div class="row">
        <div class="col-md-4">
            <input type="text" id="searchInput" class="form-control" placeholder="Search branches...">
        </div>
    </div>
</div>

<!-- Branches Table -->
<div class="table-container">
    <table class="table table-hover searchable-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Location</th>
                <th>Contact</th>
                <th>Hours</th>
                <th>Employees</th>
                <th>Orders</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($branches as $branch): ?>
            <tr>
                <td><?php echo $branch['branch_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($branch['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($branch['location']); ?></td>
                <td>
                    <?php echo htmlspecialchars($branch['contact_phone']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($branch['email']); ?></small>
                </td>
                <td><?php echo substr($branch['opening_hour'], 0, 5); ?> - <?php echo substr($branch['closing_hour'], 0, 5); ?></td>
                <td><span class="badge bg-info"><?php echo $branch['employee_count']; ?></span></td>
                <td><?php echo number_format($branch['order_count']); ?></td>
                <td>
                    <?php if ($branch['is_active']): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="action-buttons">
                    <a href="?edit=<?php echo $branch['branch_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this branch?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="branch_id" value="<?php echo $branch['branch_id']; ?>">
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

<!-- Branch Modal -->
<div class="modal fade" id="branchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="<?php echo $editBranch ? 'update' : 'create'; ?>">
                <?php if ($editBranch): ?>
                <input type="hidden" name="branch_id" value="<?php echo $editBranch['branch_id']; ?>">
                <?php endif; ?>

                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editBranch ? 'Edit Branch' : 'Add New Branch'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Branch Name *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo $editBranch ? htmlspecialchars($editBranch['name']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location *</label>
                        <textarea name="location" class="form-control" rows="2" required><?php echo $editBranch ? htmlspecialchars($editBranch['location']) : ''; ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="tel" name="contact_phone" class="form-control"
                                   value="<?php echo $editBranch ? htmlspecialchars($editBranch['contact_phone']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo $editBranch ? htmlspecialchars($editBranch['email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Opening Hour</label>
                            <input type="time" name="opening_hour" class="form-control"
                                   value="<?php echo $editBranch ? substr($editBranch['opening_hour'], 0, 5) : '09:00'; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Closing Hour</label>
                            <input type="time" name="closing_hour" class="form-control"
                                   value="<?php echo $editBranch ? substr($editBranch['closing_hour'], 0, 5) : '21:00'; ?>">
                        </div>
                    </div>
                    <?php if ($editBranch): ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive"
                                   <?php echo $editBranch['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> <?php echo $editBranch ? 'Update' : 'Create'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editBranch): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('branchModal')).show();
});
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
