<?php
/**
 * Application Configuration
 * SummitSphere Retail Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application constants
define('APP_NAME', 'SummitSphere');
define('APP_VERSION', '1.0.0');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8000');

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODELS_PATH', ROOT_PATH . '/models');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('ASSETS_PATH', ROOT_PATH . '/assets');

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Pagination
define('ITEMS_PER_PAGE', 10);

// User roles
define('ROLE_MANAGER', 'Manager');
define('ROLE_STAFF', 'Staff');
define('ROLE_SUPPLIER', 'Supplier');
define('ROLE_CUSTOMER', 'Customer');

// Order statuses
define('ORDER_STATUS', [
    'Pending' => 'Pending',
    'Processing' => 'Processing',
    'Shipped' => 'Shipped',
    'Delivered' => 'Delivered',
    'Cancelled' => 'Cancelled',
    'Refunded' => 'Refunded'
]);

// Payment methods
define('PAYMENT_METHODS', [
    'Cash' => 'Cash',
    'Credit Card' => 'Credit Card',
    'Debit Card' => 'Debit Card',
    'PayPal' => 'PayPal',
    'Bank Transfer' => 'Bank Transfer'
]);

// Membership levels
define('MEMBERSHIP_LEVELS', [
    'Bronze' => ['discount' => 0, 'threshold' => 0],
    'Silver' => ['discount' => 5, 'threshold' => 1000],
    'Gold' => ['discount' => 10, 'threshold' => 5000],
    'Platinum' => ['discount' => 15, 'threshold' => 10000]
]);

// Include required files
require_once CONFIG_PATH . '/database.php';

/**
 * Helper function to sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Helper function to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Helper function to check user role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Helper function to require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header('Location: ' . APP_URL . '/index.php?timeout=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Helper function to require specific role
 */
function requireRole($roles) {
    requireLogin();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!in_array($_SESSION['role'], $roles)) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Helper function for CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Helper function to verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper function to format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Helper function to format date
 */
function formatDate($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

/**
 * Helper function to format datetime
 */
function formatDateTime($datetime) {
    return date('M d, Y H:i', strtotime($datetime));
}

/**
 * Helper function for flash messages
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Helper function to get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
