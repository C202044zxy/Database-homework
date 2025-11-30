<?php
/**
 * Supplier - Shipments
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_SUPPLIER]);

$pageTitle = 'Shipments';
$db = new Database();
$conn = $db->getConnection();

$supplierId = $_SESSION['supplier_id'];
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $stmt = $conn->prepare("UPDATE shipment SET status = ?, tracking_number = ?,
                            actual_arrival = CASE WHEN ? = 'Delivered' THEN CURRENT_DATE ELSE actual_arrival END
                            WHERE shipment_id = ? AND supplier_id = ?");
    $stmt->execute([
        $_POST['status'],
        sanitize($_POST['tracking_number']),
        $_POST['status'],
        $_POST['shipment_id'],
        $supplierId
    ]);
    $message = 'Shipment updated!';
}

$shipments = $conn->prepare("SELECT s.*, b.name AS branch_name, b.location AS branch_location
                             FROM shipment s
                             JOIN branch b ON s.branch_id = b.branch_id
                             WHERE s.supplier_id = ?
                             ORDER BY s.shipment_date DESC");
$shipments->execute([$supplierId]);
$shipments = $shipments->fetchAll();

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-truck"></i> Shipments</h1>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Branch</th>
                <th>Ship Date</th>
                <th>Expected</th>
                <th>Tracking</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($shipments as $ship): ?>
            <tr>
                <td><strong>#<?php echo $ship['shipment_id']; ?></strong></td>
                <td>
                    <?php echo htmlspecialchars($ship['branch_name']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($ship['branch_location']); ?></small>
                </td>
                <td><?php echo formatDate($ship['shipment_date']); ?></td>
                <td><?php echo formatDate($ship['expected_arrival']); ?></td>
                <td>
                    <code><?php echo htmlspecialchars($ship['tracking_number'] ?? 'N/A'); ?></code>
                </td>
                <td>
                    <span class="badge bg-<?php
                        echo $ship['status'] === 'Delivered' ? 'success' :
                            ($ship['status'] === 'In Transit' ? 'info' :
                            ($ship['status'] === 'Cancelled' ? 'danger' : 'warning'));
                    ?>">
                        <?php echo $ship['status']; ?>
                    </span>
                </td>
                <td>
                    <?php if ($ship['status'] !== 'Delivered' && $ship['status'] !== 'Cancelled'): ?>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateModal"
                            data-id="<?php echo $ship['shipment_id']; ?>"
                            data-status="<?php echo $ship['status']; ?>"
                            data-tracking="<?php echo htmlspecialchars($ship['tracking_number'] ?? ''); ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="shipment_id" id="modal_shipment_id">
                <div class="modal-header">
                    <h5 class="modal-title">Update Shipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="modal_status" class="form-select">
                            <option value="Pending">Pending</option>
                            <option value="In Transit">In Transit</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Delayed">Delayed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tracking Number</label>
                        <input type="text" name="tracking_number" id="modal_tracking" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('updateModal').addEventListener('show.bs.modal', function(event) {
    var btn = event.relatedTarget;
    document.getElementById('modal_shipment_id').value = btn.dataset.id;
    document.getElementById('modal_status').value = btn.dataset.status;
    document.getElementById('modal_tracking').value = btn.dataset.tracking;
});
</script>

<?php include '../../includes/footer.php'; ?>
