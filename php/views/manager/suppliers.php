<?php
/**
 * Supplier Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Supplier Management';
$db = new Database();
$conn = $db->getConnection();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'create':
                $stmt = $conn->prepare("INSERT INTO supplier (name, contact_person, contact_email, phone, address,
                                        cooperation_status, contract_start_date, contract_end_date)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    sanitize($_POST['name']),
                    sanitize($_POST['contact_person']),
                    sanitize($_POST['contact_email']),
                    sanitize($_POST['phone']),
                    sanitize($_POST['address']),
                    $_POST['cooperation_status'],
                    $_POST['contract_start_date'] ?: null,
                    $_POST['contract_end_date'] ?: null
                ]);
                $message = 'Supplier created successfully!';
                break;
            case 'update':
                $stmt = $conn->prepare("UPDATE supplier SET name = ?, contact_person = ?, contact_email = ?,
                                        phone = ?, address = ?, cooperation_status = ?,
                                        contract_start_date = ?, contract_end_date = ? WHERE supplier_id = ?");
                $stmt->execute([
                    sanitize($_POST['name']),
                    sanitize($_POST['contact_person']),
                    sanitize($_POST['contact_email']),
                    sanitize($_POST['phone']),
                    sanitize($_POST['address']),
                    $_POST['cooperation_status'],
                    $_POST['contract_start_date'] ?: null,
                    $_POST['contract_end_date'] ?: null,
                    $_POST['supplier_id']
                ]);
                $message = 'Supplier updated successfully!';
                break;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

$suppliers = $conn->query("SELECT s.*, COUNT(p.product_id) AS product_count,
                           (SELECT COUNT(*) FROM purchase_order WHERE supplier_id = s.supplier_id) AS order_count
                           FROM supplier s
                           LEFT JOIN product p ON s.supplier_id = p.supplier_id AND p.is_active = TRUE
                           GROUP BY s.supplier_id ORDER BY s.name")->fetchAll();

$editSupplier = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM supplier WHERE supplier_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editSupplier = $stmt->fetch();
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-truck"></i> Supplier Management</h1>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#supplierModal">
            <i class="bi bi-plus"></i> Add Supplier
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Name</th>
                <th>Contact</th>
                <th>Products</th>
                <th>Orders</th>
                <th>Status</th>
                <th>Contract</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($suppliers as $sup): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($sup['name']); ?></strong></td>
                <td>
                    <?php echo htmlspecialchars($sup['contact_person']); ?><br>
                    <small><?php echo htmlspecialchars($sup['contact_email']); ?></small>
                </td>
                <td><span class="badge bg-info"><?php echo $sup['product_count']; ?></span></td>
                <td><?php echo $sup['order_count']; ?></td>
                <td>
                    <span class="badge bg-<?php echo $sup['cooperation_status'] === 'Active' ? 'success' : ($sup['cooperation_status'] === 'Pending' ? 'warning' : 'secondary'); ?>">
                        <?php echo $sup['cooperation_status']; ?>
                    </span>
                </td>
                <td>
                    <?php if ($sup['contract_start_date']): ?>
                    <?php echo formatDate($sup['contract_start_date']); ?> -
                    <?php echo formatDate($sup['contract_end_date']); ?>
                    <?php else: ?>
                    <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="?edit=<?php echo $sup['supplier_id']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="<?php echo $editSupplier ? 'update' : 'create'; ?>">
                <?php if ($editSupplier): ?>
                <input type="hidden" name="supplier_id" value="<?php echo $editSupplier['supplier_id']; ?>">
                <?php endif; ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editSupplier ? 'Edit' : 'Add'; ?> Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Company Name *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['name']) : ''; ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control"
                                   value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['contact_person']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="contact_email" class="form-control" required
                                   value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['contact_email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?php echo $editSupplier ? htmlspecialchars($editSupplier['phone']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="cooperation_status" class="form-select">
                                <?php foreach (['Active', 'Inactive', 'Pending', 'Terminated'] as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo ($editSupplier && $editSupplier['cooperation_status'] === $status) ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo $editSupplier ? htmlspecialchars($editSupplier['address']) : ''; ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contract Start</label>
                            <input type="date" name="contract_start_date" class="form-control"
                                   value="<?php echo $editSupplier ? $editSupplier['contract_start_date'] : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contract End</label>
                            <input type="date" name="contract_end_date" class="form-control"
                                   value="<?php echo $editSupplier ? $editSupplier['contract_end_date'] : ''; ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editSupplier): ?>
<script>document.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('supplierModal')).show());</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
