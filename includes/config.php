<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'house_rental_kenya');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'Kenya Coastal Student Housing');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('BASE_URL', SITE_URL . '/house-rental-system/');
define('APP_ROOT', dirname(dirname(__FILE__)) . '/');
define('UPLOAD_PATH', APP_ROOT . 'assets/images/properties/');

// Attempt to connect to database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
// Utility functions
function redirect($url) {
    // Remove any leading slash to prevent double slashes
    $url = ltrim($url, '/');
    header("Location: " . $url);
    exit();
}
*/

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// File upload function
function uploadImage($file, $targetDir = UPLOAD_PATH) {
    $errors = [];
    $fileNames = [];
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (is_array($file['name'])) {
        // Multiple files
        foreach ($file['name'] as $key => $name) {
            if ($file['error'][$key] === UPLOAD_ERR_OK) {
                $tmpName = $file['tmp_name'][$key];
                $fileSize = $file['size'][$key];
                $fileType = $file['type'][$key];
                
                // Validate file
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = "Only JPG, PNG, GIF, and WEBP files are allowed";
                    continue;
                }
                
                if ($fileSize > $maxSize) {
                    $errors[] = "File size must be less than 5MB";
                    continue;
                }
                
                // Generate unique filename
                $fileExt = pathinfo($name, PATHINFO_EXTENSION);
                $newFilename = uniqid() . '.' . $fileExt;
                $targetPath = $targetDir . $newFilename;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $fileNames[] = $newFilename;
                } else {
                    $errors[] = "Failed to upload file: " . $name;
                }
            }
        }
    } else {
        // Single file
        if ($file['error'] === UPLOAD_ERR_OK) {
            $tmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];
            $name = $file['name'];
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = "Only JPG, PNG, GIF, and WEBP files are allowed";
            } elseif ($fileSize > $maxSize) {
                $errors[] = "File size must be less than 5MB";
            } else {
                // Generate unique filename
                $fileExt = pathinfo($name, PATHINFO_EXTENSION);
                $newFilename = uniqid() . '.' . $fileExt;
                $targetPath = $targetDir . $newFilename;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $fileNames[] = $newFilename;
                } else {
                    $errors[] = "Failed to upload file: " . $name;
                }
            }
        }
    }
    
    return ['files' => $fileNames, 'errors' => $errors];
}

// CSRF protection
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Ensure upload directory exists
function ensureUploadDirectory($path = UPLOAD_PATH) {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
        // Create an index.html file to prevent directory listing
        file_put_contents($path . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>');
    }
}

// Call this function to ensure directories exist
ensureUploadDirectory();
ensureUploadDirectory(APP_ROOT . 'assets/images/properties/');
ensureUploadDirectory(APP_ROOT . 'assets/images/universities/');

// Basic email function
function sendEmail($to, $subject, $message) {
    $headers = "From: no-reply@coastalstudenthousing.co.ke\r\n";
    $headers .= "Reply-To: no-reply@coastalstudenthousing.co.ke\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>