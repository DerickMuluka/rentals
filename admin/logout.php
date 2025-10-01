<?php
session_start();

// Unset all admin session variables
unset($_SESSION['admin_logged_in']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();