<?php
// Utility functions for Kenya Coastal Student Housing

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is property owner
 */
function is_owner() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'owner';
}

/**
 * Check if user is student/tenant
 */
function is_tenant() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'tenant';
}

/**
 * Get user data from session
 */
function get_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'avatar' => $_SESSION['user_avatar'] ?? null
    ];
}

/**
 * Format currency
 */
function format_currency($amount) {
    return 'KES ' . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date, $format = 'F j, Y') {
    return date($format, strtotime($date));
}

/**
 * Get relative time
 */
function relative_time($date) {
    $time = strtotime($date);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
    } else {
        return format_date($date);
    }
}

/**
 * Upload file with validation
 */
function upload_file($file, $target_dir, $allowed_types = ['image/jpeg', 'image/png', 'image/gif']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
    }
    
    // Check file type
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_extension;
    $target_path = $target_dir . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }
}

/**
 * Delete file
 */
function delete_file($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

/**
 * Get pagination data
 */
function get_pagination_data($current_page, $items_per_page, $total_items) {
    $total_pages = ceil($total_items / $items_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'current_page' => $current_page,
        'items_per_page' => $items_per_page,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'offset' => $offset
    ];
}

/**
 * Generate pagination HTML
 */
function generate_pagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page - 1) . '" class="pagination-btn"><i class="fas fa-chevron-left"></i></a>';
    }
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $start_page + 4);
    
    if ($end_page - $start_page < 4) {
        $start_page = max(1, $end_page - 4);
    }
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = $i == $current_page ? ' active' : '';
        $html .= '<a href="' . $base_url . '?page=' . $i . '" class="pagination-btn' . $active . '">' . $i . '</a>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . '?page=' . ($current_page + 1) . '" class="pagination-btn"><i class="fas fa-chevron-right"></i></a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Send email
 */
function send_email($to, $subject, $message, $headers = []) {
    $default_headers = [
        'From: ' . ADMIN_EMAIL,
        'Reply-To: ' . ADMIN_EMAIL,
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $all_headers = array_merge($default_headers, $headers);
    
    return mail($to, $subject, $message, implode("\r\n", $all_headers));
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $details = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

/**
 * Get settings
 */
function get_settings() {
    global $pdo;
    
    static $settings = null;
    
    if ($settings === null) {
        $stmt = $pdo->query("SELECT name, value FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    
    return $settings;
}

/**
 * Get setting value
 */
function get_setting($name, $default = null) {
    $settings = get_settings();
    return isset($settings[$name]) ? $settings[$name] : $default;
}