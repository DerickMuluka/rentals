<?php
include '../includes/config.php';
include '../includes/auth.php';

// At the beginning of dashboard.php
if (isAdmin()) {
    redirect('../admin/index.php');
    exit();
}

// Redirect if not logged in
if (!checkLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user statistics
if ($user_type == 'owner') {
    $properties_count = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ?");
    $properties_count->execute([$user_id]);
    $properties_count = $properties_count->fetchColumn();
    
    $bookings_count = $pdo->prepare("SELECT COUNT(*) FROM bookings b 
                                   JOIN properties p ON b.property_id = p.property_id 
                                   WHERE p.owner_id = ?");
    $bookings_count->execute([$user_id]);
    $bookings_count = $bookings_count->fetchColumn();
    
    $recent_properties = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC LIMIT 5");
    $recent_properties->execute([$user_id]);
    $recent_properties = $recent_properties->fetchAll(PDO::FETCH_ASSOC);
} else {
    $bookings_count = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tenant_id = ?");
    $bookings_count->execute([$user_id]);
    $bookings_count = $bookings_count->fetchColumn();
    
    $favorites_count = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $favorites_count->execute([$user_id]);
    $favorites_count = $favorites_count->fetchColumn();
    
    $recent_bookings = $pdo->prepare("SELECT b.*, p.title, p.location 
                                    FROM bookings b 
                                    JOIN properties p ON b.property_id = p.property_id 
                                    WHERE b.tenant_id = ? 
                                    ORDER BY b.created_at DESC LIMIT 5");
    $recent_bookings->execute([$user_id]);
    $recent_bookings = $recent_bookings->fetchAll(PDO::FETCH_ASSOC);
}

// Get recent messages
$recent_messages = $pdo->prepare("SELECT m.*, u.first_name, u.last_name 
                                FROM messages m 
                                JOIN users u ON m.sender_id = u.user_id 
                                WHERE m.receiver_id = ? 
                                ORDER BY m.created_at DESC LIMIT 5");
$recent_messages->execute([$user_id]);
$recent_messages = $recent_messages->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?>!</h1>
                <p>Here's your personalized dashboard</p>
            </div>
            
            <div class="dashboard-stats">
                <?php if ($user_type == 'owner'): ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $properties_count; ?></h3>
                            <p>Properties Listed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $bookings_count; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($recent_messages); ?></h3>
                            <p>New Messages</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $bookings_count; ?></h3>
                            <p>My Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $favorites_count; ?></h3>
                            <p>Favorites</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($recent_messages); ?></h3>
                            <p>New Messages</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="dashboard-content">
                <div class="dashboard-section">
                    <?php if ($user_type == 'owner'): ?>
                        <h2>Recent Properties</h2>
                        <?php if (!empty($recent_properties)): ?>
                            <div class="properties-grid">
                                <?php foreach ($recent_properties as $property): ?>
                                    <div class="property-card">
                                        <img src="<?php echo !empty($property['images']) ? '../assets/images/properties/' . json_decode($property['images'])[0] : '../assets/images/default-property.jpg'; ?>" 
                                             alt="<?php echo $property['title']; ?>" 
                                             onerror="this.src='../assets/images/default-property.jpg'">
                                        <div class="property-details">
                                            <h3><?php echo $property['title']; ?></h3>
                                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo $property['location']; ?></p>
                                            <p class="price">KES <?php echo number_format($property['price']); ?> / month</p>
                                            <a href="view-property.php?id=<?php echo $property['property_id']; ?>" class="btn-secondary">View Details</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>You haven't listed any properties yet.</p>
                            <a href="add-property.php" class="btn-primary">Add Your First Property</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <h2>Recent Bookings</h2>
                        <?php if (!empty($recent_bookings)): ?>
                            <div class="bookings-list">
                                <?php foreach ($recent_bookings as $booking): ?>
                                    <div class="booking-item">
                                        <div class="booking-info">
                                            <h3><?php echo $booking['title']; ?></h3>
                                            <p><i class="fas fa-map-marker-alt"></i> <?php echo $booking['location']; ?></p>
                                            <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - <?php echo date('M d, Y', strtotime($booking['end_date'])); ?></p>
                                            <p><strong>KES <?php echo number_format($booking['total_amount']); ?></strong></p>
                                        </div>
                                        <div class="booking-status">
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>You haven't made any bookings yet.</p>
                            <a href="properties.php" class="btn-primary">Browse Properties</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-section">
                    <h2>Recent Messages</h2>
                    <?php if (!empty($recent_messages)): ?>
                        <div class="messages-list">
                            <?php foreach ($recent_messages as $message): ?>
                                <div class="message-item <?php echo $message['is_read'] ? '' : 'unread'; ?>">
                                    <div class="message-sender">
                                        <strong><?php echo $message['first_name'] . ' ' . $message['last_name']; ?></strong>
                                        <span class="message-time"><?php echo date('M d, H:i', strtotime($message['created_at'])); ?></span>
                                    </div>
                                    <p class="message-preview"><?php echo substr($message['message'], 0, 100); ?>...</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No messages yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <?php if ($user_type == 'owner'): ?>
                    <a href="add-property.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Property</a>
                    <a href="my-properties.php" class="btn-secondary"><i class="fas fa-home"></i> My Properties</a>
                <?php else: ?>
                    <a href="properties.php" class="btn-primary"><i class="fas fa-search"></i> Browse Properties</a>
                    <a href="my-bookings.php" class="btn-secondary"><i class="fas fa-calendar"></i> My Bookings</a>
                <?php endif; ?>
                <a href="profile.php" class="btn-secondary"><i class="fas fa-user"></i> Edit Profile</a>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
</body>
</html>
