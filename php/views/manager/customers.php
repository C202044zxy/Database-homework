<?php
/**
 * Customer Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Customer Management';
$db = new Database();
$conn = $db->getConnection();

$filterMembership = $_GET['membership'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT c.*, COUNT(co.order_id) AS order_count,
        (SELECT MAX(order_date) FROM customer_order WHERE customer_id = c.customer_id) AS last_order
        FROM customer c
        LEFT JOIN customer_order co ON c.customer_id = co.customer_id
        WHERE 1=1";
$params = [];

if ($filterMembership) {
    $sql .= " AND c.membership_level = ?";
    $params[] = $filterMembership;
}
if ($search) {
    $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " GROUP BY c.customer_id ORDER BY c.total_spent DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-people"></i> Customer Management</h1>
    </div>
</div>

<div class="search-filter">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name or email..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="membership" class="form-select">
                <option value="">All Memberships</option>
                <?php foreach (['Bronze', 'Silver', 'Gold', 'Platinum'] as $level): ?>
                <option value="<?php echo $level; ?>" <?php echo $filterMembership === $level ? 'selected' : ''; ?>>
                    <?php echo $level; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="customers.php" class="btn btn-outline-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Contact</th>
                <th>Membership</th>
                <th>Orders</th>
                <th>Total Spent</th>
                <th>Last Order</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $cust): ?>
            <tr>
                <td><?php echo $cust['customer_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?></strong></td>
                <td>
                    <?php echo htmlspecialchars($cust['email']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($cust['phone']); ?></small>
                </td>
                <td>
                    <span class="membership-badge <?php echo strtolower($cust['membership_level']); ?>">
                        <?php echo $cust['membership_level']; ?>
                    </span>
                </td>
                <td><?php echo $cust['order_count']; ?></td>
                <td><strong><?php echo formatCurrency($cust['total_spent']); ?></strong></td>
                <td><?php echo $cust['last_order'] ? formatDate($cust['last_order']) : 'Never'; ?></td>
                <td>
                    <?php if ($cust['is_active']): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../../includes/footer.php'; ?>
