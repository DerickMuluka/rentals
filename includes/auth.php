<?php
/**
 * Authentication and authorization functions for the Coastal Student Housing System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validate password strength
 * @param string $password Password to validate
 * @return bool True if password is strong enough, false otherwise
 */
function validatePassword($password) {
    // Minimum 8 characters, at least one letter and one number
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

/**
 * Check if user has access to a specific resource
 * @param int $resourceUserId User ID of the resource owner
 * @return bool True if user has access, false otherwise
 */
function hasAccess($resourceUserId) {
    if (!checkLoggedIn()) {
        return false;
    }
    
    // Admin has access to everything
    if (isAdmin()) {
        return true;
    }
    
    // Users have access to their own resources
    return $_SESSION['user_id'] == $resourceUserId;
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!checkLoggedIn()) {
        header("Location: ../pages/login.php");
        exit();
    }
}

/**
 * Require user to be admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        // Redirect to dashboard with error message
        $_SESSION['error'] = "You don't have permission to access this page";
        header("Location: ../pages/dashboard.php");
        exit();
    }
}

/**
 * Require user to be owner
 */
function requireOwner() {
    requireLogin();
    
    if (!isOwner()) {
        // Redirect to dashboard with error message
        $_SESSION['error'] = "You don't have permission to access this page";
        header("Location: ../pages/dashboard.php");
        exit();
    }
}

/**
 * Require user to be tenant
 */
function requireTenant() {
    requireLogin();
    
    if (!isTenant()) {
        // Redirect to dashboard with error message
        $_SESSION['error'] = "You don't have permission to access this page";
        header("Location: ../pages/dashboard.php");
        exit();
    }
}

/**
 * Generate a secure random token
 * @param int $length Token length
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Set flash message for next request
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null Flash message array or null if none exists
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    return null;
}

/**
 * Display flash message if exists
 */
function displayFlash() {
    $flash = getFlash();
    
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        
        echo "<div class='alert alert-$type'>";
        echo "<p>$message</p>";
        echo "</div>";
        
        // Add JavaScript to auto-hide after 5 seconds
        echo "<script>
            setTimeout(function() {
                const alert = document.querySelector('.alert');
                if (alert) {
                    alert.style.display = 'none';
                }
            }, 5000);
        </script>";
    }
}

/**
 * Login user with email and password
 */
function loginUser($email, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['profile_image'] = $user['profile_image'];
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        
        return true;
    }
    
    return false;
}

/**
 * Register a new user
 */
function register_user($name, $email, $password, $role = 'tenant', $phone = null, $university_id = null) {
    global $pdo;
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'All fields are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format'];
    }
    
    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters long'];
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, university_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    
    try {
        $stmt->execute([$name, $email, $password_hash, $role, $phone, $university_id]);
        $user_id = $pdo->lastInsertId();
        
        // Log activity
        log_activity($user_id, 'register', 'New user registration');
        
        return ['success' => true, 'user_id' => $user_id];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

/**
 * Login user
 */
function login_user($email, $password) {
    global $pdo;
    
    // Validate input
    if (empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Email and password are required'];
    }
    
    // Get user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Check if user is active
    if (!$user['is_active']) {
        return ['success' => false, 'error' => 'Account is deactivated. Please contact support.'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_avatar'] = $user['avatar'];
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Log activity
    log_activity($user['id'], 'login', 'User logged in');
    
    return ['success' => true, 'user' => $user];
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function checkLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is an admin
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return checkLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if user is a property owner
 * @return bool True if user is owner, false otherwise
 */
function isOwner() {
    return checkLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner';
}

/**
 * Check if user is a tenant
 * @return bool True if user is tenant, false otherwise
 */
function isTenant() {
    return checkLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'tenant';
}

/**
 * Redirect to a specific URL
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Logout user
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: ../pages/login.php");
    exit();
}

/**
 * Update user profile
 */
function update_profile($user_id, $name, $email, $phone = null, $university_id = null, $avatar = null) {
    global $pdo;
    
    // Validate input
    if (empty($name) || empty($email)) {
        return ['success' => false, 'error' => 'Name and email are required'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email format'];
    }
    
    // Check if email is already used by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already used by another account'];
    }
    
    // Build update query
    $updates = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'university_id' => $university_id,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    if ($avatar) {
        $updates['avatar'] = $avatar;
    }
    
    $set_clause = implode(', ', array_map(function($field) {
        return "$field = ?";
    }, array_keys($updates)));
    
    $values = array_values($updates);
    $values[] = $user_id;
    
    // Update user
    $stmt = $pdo->prepare("UPDATE users SET $set_clause WHERE id = ?");
    
    try {
        $stmt->execute($values);
        
        // Update session if current user
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            if ($avatar) {
                $_SESSION['user_avatar'] = $avatar;
            }
        }
        
        // Log activity
        log_activity($user_id, 'profile_update', 'Profile updated');
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Profile update failed. Please try again.'];
    }
}

/**
 * Change password
 */
function change_password($user_id, $current_password, $new_password) {
    global $pdo;
    
    // Validate input
    if (empty($current_password) || empty($new_password)) {
        return ['success' => false, 'error' => 'Current and new password are required'];
    }
    
    if (strlen($new_password) < 6) {
        return ['success' => false, 'error' => 'New password must be at least 6 characters long'];
    }
    
    // Get current password hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'User not found'];
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        return ['success' => false, 'error' => 'Current password is incorrect'];
    }
    
    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    
    try {
        $stmt->execute([$new_password_hash, $user_id]);
        
        // Log activity
        log_activity($user_id, 'password_change', 'Password changed');
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Password change error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Password change failed. Please try again.'];
    }
}

/**
 * Request password reset
 */
function request_password_reset($email) {
    global $pdo;
    
    // Validate input
    if (empty($email)) {
        return ['success' => false, 'error' => 'Email is required'];
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Return success even if user doesn't exist to prevent email enumeration
        return ['success' => true];
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store token in database
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    
    try {
        $stmt->execute([$user['id'], $token, $expires]);
        
        // Send reset email
        $reset_url = BASE_URL . "/reset-password.php?token=$token";
        $subject = "Password Reset Request - " . SITE_NAME;
        $message = "
            <h2>Password Reset Request</h2>
            <p>Hello {$user['name']},</p>
            <p>You have requested to reset your password. Click the link below to reset your password:</p>
            <p><a href='$reset_url' style='display: inline-block; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px;'>Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you didn't request this reset, please ignore this email.</p>
        ";
        
        send_email($email, $subject, $message);
        
        // Log activity
        log_activity($user['id'], 'password_reset_request', 'Password reset requested');
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Password reset request error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Password reset request failed. Please try again.'];
    }
}

/**
 * Reset password with token
 */
function reset_password($token, $new_password) {
    global $pdo;
    
    // Validate input
    if (empty($token) || empty($new_password)) {
        return ['success' => false, 'error' => 'Token and new password are required'];
    }
    
    if (strlen($new_password) < 6) {
        return ['success' => false, 'error' => 'New password must be at least 6 characters long'];
    }
    
    // Get reset token
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if (!$reset) {
        return ['success' => false, 'error' => 'Invalid or expired reset token'];
    }
    
    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password and mark token as used
    $pdo->beginTransaction();
    
    try {
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_password_hash, $reset['user_id']]);
        
        // Mark token as used
        $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);
        
        $pdo->commit();
        
        // Log activity
        log_activity($reset['user_id'], 'password_reset', 'Password reset successfully');
        
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Password reset failed. Please try again.'];
    }
}