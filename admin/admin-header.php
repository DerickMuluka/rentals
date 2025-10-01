<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <div class="header-container">
                <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                    <i class="fas fa-home"></i> Coastal<span>Housing</span> <small>Admin</small>
                </a>

                <nav class="admin-nav">
                    <ul class="nav-menu">
                        <li><a href="index.php" class="nav-link">Dashboard</a></li>
                        <li><a href="users.php" class="nav-link">Users</a></li>
                        <li><a href="properties.php" class="nav-link">Properties</a></li>
                        <li><a href="bookings.php" class="nav-link">Bookings</a></li>
                        <li><a href="universities.php" class="nav-link">Universities</a></li>
                    </ul>
                </nav>

                <div class="user-menu">
                    <div class="user-info">
                        <img src="<?php echo BASE_URL . $_SESSION['profile_image']; ?>" alt="Profile" class="user-avatar" 
                             onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.jpg'">
                        <span><?php echo $_SESSION['first_name']; ?></span>
                    </div>
                    <div class="user-dropdown">
                        <a href="<?php echo BASE_URL; ?>pages/profile.php" class="btn btn-outline">Profile</a>
                        <a href="<?php echo BASE_URL; ?>pages/logout.php" class="btn btn-secondary">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h3>Admin Panel</h3>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php" class="sidebar-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="users.php" class="sidebar-link">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li>
                    <a href="properties.php" class="sidebar-link">
                        <i class="fas fa-home"></i>
                        <span>Properties</span>
                    </a>
                </li>
                <li>
                    <a href="bookings.php" class="sidebar-link">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="universities.php" class="sidebar-link">
                        <i class="fas fa-university"></i>
                        <span>Universities</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo BASE_URL; ?>index.php" class="sidebar-link">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Site</span>
                    </a>
                </li>
            </ul>
        </div>

        <main class="admin-main">