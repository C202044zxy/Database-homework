<?php
/**
 * Common Header
 * SummitSphere Retail Management System
 */

require_once dirname(__DIR__) . '/config/config.php';

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/dashboard.php">
                <i class="bi bi-mountain"></i> <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (hasRole(ROLE_MANAGER)): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-building"></i> Management
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/branches.php">Branches</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/employees.php">Employees</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/suppliers.php">Suppliers</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/customers.php">Customers</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-box-seam"></i> Inventory
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/categories.php">Categories</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/products.php">Products</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/inventory.php">Stock Levels</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/orders.php">Customer Orders</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/purchase_orders.php">Purchase Orders</a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/views/manager/shipments.php">Shipments</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/manager/reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (hasRole(ROLE_STAFF)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/staff/products.php">
                            <i class="bi bi-box-seam"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/staff/inventory.php">
                            <i class="bi bi-boxes"></i> Inventory
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/staff/orders.php">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/staff/new_order.php">
                            <i class="bi bi-cart-plus"></i> New Sale
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (hasRole(ROLE_SUPPLIER)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/supplier/products.php">
                            <i class="bi bi-box-seam"></i> My Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/supplier/purchase_orders.php">
                            <i class="bi bi-file-earmark-text"></i> Purchase Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/supplier/shipments.php">
                            <i class="bi bi-truck"></i> Shipments
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (hasRole(ROLE_CUSTOMER)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/customer/products.php">
                            <i class="bi bi-shop"></i> Shop
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/customer/cart.php">
                            <i class="bi bi-cart3"></i> Cart
                            <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/customer/orders.php">
                            <i class="bi bi-bag"></i> My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/customer/reviews.php">
                            <i class="bi bi-star"></i> My Reviews
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                            <span class="badge bg-light text-dark"><?php echo $_SESSION['role']; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php">
                                <i class="bi bi-person"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/change_password.php">
                                <i class="bi bi-key"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo APP_URL; ?>/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <main class="<?php echo isLoggedIn() ? 'container-fluid py-4' : ''; ?>">
        <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
