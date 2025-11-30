<?php
/**
 * Login Page
 * SummitSphere Retail Management System
 */

require_once 'config/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $auth = new Auth();

        if ($_POST['action'] === 'login') {
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                $result = $auth->login($username, $password);

                if (isset($result['success'])) {
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = $result['error'];
                }
            }
        } elseif ($_POST['action'] === 'register') {
            $data = [
                'username' => sanitize($_POST['reg_username'] ?? ''),
                'password' => $_POST['reg_password'] ?? '',
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'email' => sanitize($_POST['email'] ?? ''),
                'phone' => sanitize($_POST['phone'] ?? ''),
                'gender' => sanitize($_POST['gender'] ?? ''),
                'address' => sanitize($_POST['address'] ?? '')
            ];

            // Validate
            if (empty($data['username']) || empty($data['password']) || empty($data['first_name'])
                || empty($data['last_name']) || empty($data['email'])) {
                $error = 'Please fill in all required fields.';
            } elseif (strlen($data['password']) < 8) {
                $error = 'Password must be at least 8 characters.';
            } elseif ($_POST['reg_password'] !== $_POST['confirm_password']) {
                $error = 'Passwords do not match.';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $result = $auth->registerCustomer($data);

                if (isset($result['success'])) {
                    $success = $result['message'];
                } else {
                    $error = $result['error'];
                }
            }
        }
    }
}

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please log in again.';
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="bi bi-mountain login-logo"></i>
                <h2 class="mt-2"><?php echo APP_NAME; ?></h2>
                <p class="text-muted">Retail Management System</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Nav tabs -->
            <ul class="nav nav-tabs nav-fill mb-3" id="authTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                        <i class="bi bi-person-plus"></i> Register
                    </button>
                </li>
            </ul>

            <!-- Tab content -->
            <div class="tab-content" id="authTabContent">
                <!-- Login Form -->
                <div class="tab-pane fade show active" id="login" role="tabpanel">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="username" name="username"
                                       placeholder="Enter username" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Enter password" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center text-muted small">
                        <p class="mb-2"><strong>Demo Accounts:</strong></p>
                        <p class="mb-1">Manager: thomas.anderson / password123</p>
                        <p class="mb-1">Staff: emily.parker / password123</p>
                        <p class="mb-1">Supplier: supplier.cyclegear / password123</p>
                        <p class="mb-0">Customer: john.smith / password123</p>
                    </div>
                </div>

                <!-- Registration Form -->
                <div class="tab-pane fade" id="register" role="tabpanel">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select...</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="reg_username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="reg_username" name="reg_username" required minlength="4">
                            <div class="form-text">At least 4 characters</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="reg_password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="reg_password" name="reg_password" required minlength="8">
                                <div class="form-text">At least 8 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-person-plus"></i> Create Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
