<?php
/**
 * Authentication Handler
 * SummitSphere Retail Management System
 */

require_once dirname(__DIR__) . '/config/config.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Attempt to log in a user
     * @param string $username
     * @param string $password
     * @return array|false
     */
    public function login($username, $password) {
        try {
            $query = "SELECT u.*,
                      e.first_name as emp_first_name, e.last_name as emp_last_name, e.branch_id,
                      s.name as supplier_name,
                      c.first_name as cust_first_name, c.last_name as cust_last_name
                      FROM user u
                      LEFT JOIN employee e ON u.employee_id = e.employee_id
                      LEFT JOIN supplier s ON u.supplier_id = s.supplier_id
                      LEFT JOIN customer c ON u.customer_id = c.customer_id
                      WHERE u.username = :username AND u.is_active = TRUE";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if account is locked
                if ($user['is_locked']) {
                    return ['error' => 'Account is locked. Please contact support.'];
                }

                // Reset login attempts on successful login
                $this->resetLoginAttempts($user['user_id']);

                // Update last login
                $this->updateLastLogin($user['user_id']);

                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();

                // Set role-specific session data
                switch ($user['role']) {
                    case 'Manager':
                    case 'Staff':
                        $_SESSION['employee_id'] = $user['employee_id'];
                        $_SESSION['branch_id'] = $user['branch_id'];
                        $_SESSION['full_name'] = $user['emp_first_name'] . ' ' . $user['emp_last_name'];
                        break;
                    case 'Supplier':
                        $_SESSION['supplier_id'] = $user['supplier_id'];
                        $_SESSION['full_name'] = $user['supplier_name'];
                        break;
                    case 'Customer':
                        $_SESSION['customer_id'] = $user['customer_id'];
                        $_SESSION['full_name'] = $user['cust_first_name'] . ' ' . $user['cust_last_name'];
                        break;
                }

                // Log the login
                $this->logAudit($user['user_id'], 'LOGIN', 'user', $user['user_id']);

                return ['success' => true, 'role' => $user['role']];
            } else {
                // Increment login attempts
                if ($user) {
                    $this->incrementLoginAttempts($user['user_id']);
                }
                return ['error' => 'Invalid username or password.'];
            }
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            return ['error' => 'An error occurred. Please try again.'];
        }
    }

    /**
     * Log out the current user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAudit($_SESSION['user_id'], 'LOGOUT', 'user', $_SESSION['user_id']);
        }

        session_unset();
        session_destroy();
    }

    /**
     * Register a new customer account
     * @param array $data
     * @return array
     */
    public function registerCustomer($data) {
        try {
            $this->conn->beginTransaction();

            // Check if email already exists
            $checkQuery = "SELECT customer_id FROM customer WHERE email = :email";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->bindParam(':email', $data['email']);
            $checkStmt->execute();

            if ($checkStmt->rowCount() > 0) {
                return ['error' => 'Email already registered.'];
            }

            // Check if username exists
            $checkUser = "SELECT user_id FROM user WHERE username = :username";
            $checkUserStmt = $this->conn->prepare($checkUser);
            $checkUserStmt->bindParam(':username', $data['username']);
            $checkUserStmt->execute();

            if ($checkUserStmt->rowCount() > 0) {
                return ['error' => 'Username already taken.'];
            }

            // Insert customer
            $customerQuery = "INSERT INTO customer (first_name, last_name, gender, email, phone, address, registration_date)
                             VALUES (:first_name, :last_name, :gender, :email, :phone, :address, CURRENT_DATE)";
            $customerStmt = $this->conn->prepare($customerQuery);
            $customerStmt->bindParam(':first_name', $data['first_name']);
            $customerStmt->bindParam(':last_name', $data['last_name']);
            $customerStmt->bindParam(':gender', $data['gender']);
            $customerStmt->bindParam(':email', $data['email']);
            $customerStmt->bindParam(':phone', $data['phone']);
            $customerStmt->bindParam(':address', $data['address']);
            $customerStmt->execute();

            $customerId = $this->conn->lastInsertId();

            // Create user account
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $userQuery = "INSERT INTO user (username, password_hash, role, customer_id)
                         VALUES (:username, :password_hash, 'Customer', :customer_id)";
            $userStmt = $this->conn->prepare($userQuery);
            $userStmt->bindParam(':username', $data['username']);
            $userStmt->bindParam(':password_hash', $passwordHash);
            $userStmt->bindParam(':customer_id', $customerId);
            $userStmt->execute();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Registration successful! You can now log in.'];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Registration Error: " . $e->getMessage());
            return ['error' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Change user password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Verify current password
            $query = "SELECT password_hash FROM user WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return ['error' => 'Current password is incorrect.'];
            }

            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE user SET password_hash = :password_hash WHERE user_id = :user_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':password_hash', $newHash);
            $updateStmt->bindParam(':user_id', $userId);
            $updateStmt->execute();

            $this->logAudit($userId, 'PASSWORD_CHANGE', 'user', $userId);

            return ['success' => true, 'message' => 'Password changed successfully.'];

        } catch (PDOException $e) {
            error_log("Password Change Error: " . $e->getMessage());
            return ['error' => 'Failed to change password. Please try again.'];
        }
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($userId) {
        $query = "UPDATE user SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }

    /**
     * Increment login attempts
     */
    private function incrementLoginAttempts($userId) {
        $query = "UPDATE user SET login_attempts = login_attempts + 1,
                  is_locked = CASE WHEN login_attempts >= 4 THEN TRUE ELSE FALSE END
                  WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }

    /**
     * Reset login attempts
     */
    private function resetLoginAttempts($userId) {
        $query = "UPDATE user SET login_attempts = 0 WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
    }

    /**
     * Log audit trail
     */
    private function logAudit($userId, $action, $tableName, $recordId) {
        $query = "INSERT INTO audit_log (user_id, action, table_name, record_id, ip_address, user_agent)
                  VALUES (:user_id, :action, :table_name, :record_id, :ip_address, :user_agent)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':table_name', $tableName);
        $stmt->bindParam(':record_id', $recordId);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $stmt->bindParam(':ip_address', $ip);
        $stmt->bindParam(':user_agent', $userAgent);
        $stmt->execute();
    }
}
