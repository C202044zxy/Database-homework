<?php
/**
 * Employee Management
 * SummitSphere Retail Management System
 */

require_once '../../config/config.php';
requireRole([ROLE_MANAGER]);

$pageTitle = 'Employee Management';
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
                $conn->beginTransaction();

                // Create employee
                $stmt = $conn->prepare("INSERT INTO employee (branch_id, first_name, last_name, gender, date_of_birth,
                                        email, phone, address, role, hire_date, salary, id_card_number)
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['branch_id'],
                    sanitize($_POST['first_name']),
                    sanitize($_POST['last_name']),
                    $_POST['gender'],
                    $_POST['date_of_birth'],
                    sanitize($_POST['email']),
                    sanitize($_POST['phone']),
                    sanitize($_POST['address']),
                    $_POST['role'],
                    $_POST['hire_date'],
                    $_POST['salary'],
                    sanitize($_POST['id_card_number'])
                ]);
                $employeeId = $conn->lastInsertId();

                // Create user account if requested
                if (!empty($_POST['create_account']) && !empty($_POST['username'])) {
                    $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO user (username, password_hash, role, employee_id)
                                           VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        sanitize($_POST['username']),
                        $passwordHash,
                        $_POST['role'],
                        $employeeId
                    ]);
                }

                $conn->commit();
                $message = 'Employee created successfully!';
                break;

            case 'update':
                $stmt = $conn->prepare("UPDATE employee SET branch_id = ?, first_name = ?, last_name = ?, gender = ?,
                                        date_of_birth = ?, email = ?, phone = ?, address = ?, role = ?,
                                        salary = ?, id_card_number = ?, is_active = ? WHERE employee_id = ?");
                $stmt->execute([
                    $_POST['branch_id'],
                    sanitize($_POST['first_name']),
                    sanitize($_POST['last_name']),
                    $_POST['gender'],
                    $_POST['date_of_birth'],
                    sanitize($_POST['email']),
                    sanitize($_POST['phone']),
                    sanitize($_POST['address']),
                    $_POST['role'],
                    $_POST['salary'],
                    sanitize($_POST['id_card_number']),
                    isset($_POST['is_active']) ? 1 : 0,
                    $_POST['employee_id']
                ]);
                $message = 'Employee updated successfully!';
                break;

            case 'delete':
                $stmt = $conn->prepare("UPDATE employee SET is_active = FALSE WHERE employee_id = ?");
                $stmt->execute([$_POST['employee_id']]);
                // Also deactivate user account
                $stmt = $conn->prepare("UPDATE user SET is_active = FALSE WHERE employee_id = ?");
                $stmt->execute([$_POST['employee_id']]);
                $message = 'Employee deactivated successfully!';
                break;
        }
    } catch (PDOException $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Fetch employees
$filterBranch = $_GET['branch'] ?? '';
$filterRole = $_GET['role'] ?? '';

$sql = "SELECT e.*, b.name AS branch_name,
        (SELECT username FROM user WHERE employee_id = e.employee_id) AS username
        FROM employee e
        JOIN branch b ON e.branch_id = b.branch_id WHERE 1=1";
$params = [];

if ($filterBranch) {
    $sql .= " AND e.branch_id = ?";
    $params[] = $filterBranch;
}
if ($filterRole) {
    $sql .= " AND e.role = ?";
    $params[] = $filterRole;
}
$sql .= " ORDER BY e.last_name, e.first_name";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Get branches for dropdown
$branches = $conn->query("SELECT * FROM branch WHERE is_active = TRUE ORDER BY name")->fetchAll();

// For editing
$editEmployee = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM employee WHERE employee_id = ?");
    $stmt->execute([$_GET['edit']]);
    $editEmployee = $stmt->fetch();
}

$csrf_token = generateCSRFToken();
include '../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col">
        <h1 class="h3"><i class="bi bi-people"></i> Employee Management</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Employees</li>
            </ol>
        </nav>
    </div>
    <div class="col-auto">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#employeeModal">
            <i class="bi bi-plus"></i> Add Employee
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
            <input type="text" id="searchInput" class="form-control" placeholder="Search employees...">
        </div>
        <div class="col-md-3">
            <select name="branch" class="form-select" onchange="this.form.submit()">
                <option value="">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                <option value="<?php echo $branch['branch_id']; ?>" <?php echo $filterBranch == $branch['branch_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($branch['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="role" class="form-select" onchange="this.form.submit()">
                <option value="">All Roles</option>
                <option value="Manager" <?php echo $filterRole === 'Manager' ? 'selected' : ''; ?>>Managers</option>
                <option value="Staff" <?php echo $filterRole === 'Staff' ? 'selected' : ''; ?>>Staff</option>
            </select>
        </div>
        <div class="col-md-2">
            <a href="employees.php" class="btn btn-outline-secondary">Clear Filters</a>
        </div>
    </form>
</div>

<!-- Employees Table -->
<div class="table-container">
    <table class="table table-hover searchable-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Branch</th>
                <th>Role</th>
                <th>Contact</th>
                <th>Hire Date</th>
                <th>Salary</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td><?php echo $emp['employee_id']; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong>
                    <?php if ($emp['username']): ?>
                    <br><small class="text-muted">@<?php echo htmlspecialchars($emp['username']); ?></small>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($emp['branch_name']); ?></td>
                <td>
                    <span class="badge <?php echo $emp['role'] === 'Manager' ? 'bg-primary' : 'bg-secondary'; ?>">
                        <?php echo $emp['role']; ?>
                    </span>
                </td>
                <td>
                    <?php echo htmlspecialchars($emp['email']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($emp['phone']); ?></small>
                </td>
                <td><?php echo formatDate($emp['hire_date']); ?></td>
                <td><?php echo formatCurrency($emp['salary']); ?></td>
                <td>
                    <?php if ($emp['is_active']): ?>
                    <span class="badge bg-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="action-buttons">
                    <a href="?edit=<?php echo $emp['employee_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to deactivate this employee?');">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="employee_id" value="<?php echo $emp['employee_id']; ?>">
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

<!-- Employee Modal -->
<div class="modal fade" id="employeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="<?php echo $editEmployee ? 'update' : 'create'; ?>">
                <?php if ($editEmployee): ?>
                <input type="hidden" name="employee_id" value="<?php echo $editEmployee['employee_id']; ?>">
                <?php endif; ?>

                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editEmployee ? 'Edit Employee' : 'Add New Employee'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required
                                   value="<?php echo $editEmployee ? htmlspecialchars($editEmployee['first_name']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required
                                   value="<?php echo $editEmployee ? htmlspecialchars($editEmployee['last_name']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Gender *</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="Male" <?php echo ($editEmployee && $editEmployee['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($editEmployee && $editEmployee['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($editEmployee && $editEmployee['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control"
                                   value="<?php echo $editEmployee ? $editEmployee['date_of_birth'] : ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">ID Card Number</label>
                            <input type="text" name="id_card_number" class="form-control"
                                   value="<?php echo $editEmployee ? htmlspecialchars($editEmployee['id_card_number']) : ''; ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required
                                   value="<?php echo $editEmployee ? htmlspecialchars($editEmployee['email']) : ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control"
                                   value="<?php echo $editEmployee ? htmlspecialchars($editEmployee['phone']) : ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo $editEmployee ? htmlspecialchars($editEmployee['address']) : ''; ?></textarea>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Branch *</label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Select Branch...</option>
                                <?php foreach ($branches as $branch): ?>
                                <option value="<?php echo $branch['branch_id']; ?>"
                                    <?php echo ($editEmployee && $editEmployee['branch_id'] == $branch['branch_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($branch['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="Staff" <?php echo ($editEmployee && $editEmployee['role'] === 'Staff') ? 'selected' : ''; ?>>Staff</option>
                                <option value="Manager" <?php echo ($editEmployee && $editEmployee['role'] === 'Manager') ? 'selected' : ''; ?>>Manager</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Salary</label>
                            <input type="number" name="salary" class="form-control" step="0.01" min="0"
                                   value="<?php echo $editEmployee ? $editEmployee['salary'] : ''; ?>">
                        </div>
                    </div>

                    <?php if (!$editEmployee): ?>
                    <div class="mb-3">
                        <label class="form-label">Hire Date *</label>
                        <input type="date" name="hire_date" class="form-control" required
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <hr>
                    <h6>User Account (Optional)</h6>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="create_account" class="form-check-input" id="createAccount">
                        <label class="form-check-label" for="createAccount">Create login account</label>
                    </div>
                    <div class="row" id="accountFields" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="isActive"
                                   <?php echo $editEmployee['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> <?php echo $editEmployee ? 'Update' : 'Create'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('createAccount')?.addEventListener('change', function() {
    document.getElementById('accountFields').style.display = this.checked ? 'flex' : 'none';
});
</script>

<?php if ($editEmployee): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('employeeModal')).show();
});
</script>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
