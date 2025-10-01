<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';
$firstName = $isLoggedIn ? $_SESSION['first_name'] : '';
$profileImage = $isLoggedIn && isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'assets/images/default-avatar.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.00/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/responsive.css">
    <style>
        /* Improved navigation styling */
    .nav-menu {
        display: flex;
        list-style: none;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .nav-link {
        color: white !important;
        background: rgba(255, 255, 255, 0.1);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
        padding: 0.5rem 1rem;
        border-radius: 4px;
        white-space: nowrap;
    }
    
    .nav-link:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }
    
    .user-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .dropdown-content a {
        color: var(--dark-color) !important;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        text-align: left;
    }
    
    .dropdown-content a:hover {
        background-color: #f1f1f1;
    }
    
    .user-dropdown:hover .dropdown-content {
        display: block;
    }
    
    /* Mobile responsive improvements */
    @media (max-width: 768px) {
        .nav-menu {
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .user-menu {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .dropdown-content {
            position: static;
            box-shadow: none;
        }
    }

        /* Improved button styles for better readability */
        .btn-outline {
            background: transparent;
            border: 2px solid #fff;
            color: #fff !important;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-outline:hover {
            background: #fff;
            color: var(--primary-color) !important;
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: var(--accent-color);
            color: var(--primary-color) !important;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 2px solid var(--accent-color);
        }
        
        .btn-primary:hover {
            background: transparent;
            color: var(--accent-color) !important;
            transform: translateY(-2px);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #fff;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-container">
                <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                    <i class="fas fa-home"></i> Coastal<span>Housing</span>
                </a>

                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>

                <nav class="nav">
                    <ul class="nav-menu" id="nav-menu">
                        <li><a href="<?php echo BASE_URL; ?>index.php" class="nav-link">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/properties.php" class="nav-link">Properties</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/about.php" class="nav-link">About</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/contact.php" class="nav-link">Contact</a></li>
                        
                        <?php if ($isLoggedIn): ?>
                            <?php if ($userType == 'owner'): ?>
                                <li><a href="<?php echo BASE_URL; ?>pages/my-properties.php" class="nav-link">My Properties</a></li>
                                <li><a href="<?php echo BASE_URL; ?>pages/add-property.php" class="nav-link">Add Property</a></li>
                            <?php endif; ?>
                            <li><a href="<?php echo BASE_URL; ?>pages/my-bookings.php" class="nav-link">My Bookings</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <!-- Updated user menu section -->
                 <div class="user-menu">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-info">
                            <img src="<?php echo BASE_URL . $profileImage; ?>" alt="Profile" class="user-avatar" 
                            onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.jpg'">
                            <span><?php echo htmlspecialchars($firstName); ?></span>
                        </div>
                        <div class="user-dropdown">
                            <button class="btn btn-outline">Menu <i class="fas fa-caret-down"></i></button>
                            <div class="dropdown-content">
                                <a href="<?php echo BASE_URL; ?>pages/profile.php">Profile</a>
                                <a href="<?php echo BASE_URL; ?>pages/dashboard.php">Dashboard</a>
                                <?php if ($userType == 'owner'): ?>
                                    <a href="<?php echo BASE_URL; ?>pages/my-properties.php">My Properties</a>
                                    <a href="<?php echo BASE_URL; ?>pages/add-property.php">Add Property</a>
                                    <?php elseif ($userType == 'tenant'): ?>
                                        <a href="<?php echo BASE_URL; ?>pages/my-bookings.php">My Bookings</a>
                                        <a href="<?php echo BASE_URL; ?>pages/favorites.php">Favorites</a>
                                        <?php endif; ?>
                                        <?php if ($userType == 'admin'): ?>
                                            <a href="<?php echo BASE_URL; ?>admin/index.php">Admin Panel</a>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>pages/logout.php">Logout</a>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>pages/login.php" class="btn btn-outline">Login</a>
                                        <a href="<?php echo BASE_URL; ?>pages/register.php" class="btn btn-primary">Register</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </header>

    <!-- Mobile Navigation -->
    <div class="mobile-nav" id="mobile-nav">
        <div class="mobile-nav-header">
            <a href="<?php echo BASE_URL; ?>index.php" class="logo">
                <i class="fas fa-home"></i> Coastal<span>Housing</span>
            </a>
            <button class="mobile-nav-close" id="mobile-nav-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="mobile-nav-menu">
            <li><a href="<?php echo BASE_URL; ?>index.php" class="mobile-nav-link">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>pages/properties.php" class="mobile-nav-link">Properties</a></li>
            <li><a href="<?php echo BASE_URL; ?>pages/about.php" class="mobile-nav-link">About</a></li>
            <li><a href="<?php echo BASE_URL; ?>pages/contact.php" class="mobile-nav-link">Contact</a></li>
            
            <?php if ($isLoggedIn): ?>
                <?php if ($userType == 'owner'): ?>
                    <li><a href="<?php echo BASE_URL; ?>pages/my-properties.php" class="mobile-nav-link">My Properties</a></li>
                    <li><a href="<?php echo BASE_URL; ?>pages/add-property.php" class="mobile-nav-link">Add Property</a></li>
                <?php endif; ?>
                <li><a href="<?php echo BASE_URL; ?>pages/my-bookings.php" class="mobile-nav-link">My Bookings</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/edit-profile.php" class="mobile-nav-link">Profile</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/logout.php" class="mobile-nav-link">Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo BASE_URL; ?>pages/login.php" class="mobile-nav-link">Login</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/register.php" class="mobile-nav-link">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="overlay" id="overlay"></div>