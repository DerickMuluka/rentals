<?php
include '../includes/config.php';
include '../includes/auth.php';
	
// Redirect if not logged in
if (!checkLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($user_type == 'owner') {
    // Get bookings for owner's properties
    $stmt = $pdo->prepare("SELECT b.*, p.title, p.location, u.first_name, u.last_name, u.phone 
                          FROM bookings b 
                          JOIN properties p ON b.property_id = p.property_id 
                          JOIN users u ON b.tenant_id = u.user_id 
                          WHERE p.owner_id = ? 
                          ORDER BY b.created_at DESC");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Get bookings for tenant
    $stmt = $pdo->prepare("SELECT b.*, p.title, p.location, u.first_name, u.last_name, u.phone 
                          FROM bookings b 
                          JOIN properties p ON b.property_id = p.property_id 
                          JOIN users u ON p.owner_id = u.user_id 
                          WHERE b.tenant_id = ? 
                          ORDER BY b.created_at DESC");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_type == 'owner') {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT p.owner_id FROM bookings b 
                          JOIN properties p ON b.property_id = p.property_id 
                          WHERE b.booking_id = ?");
    $stmt->execute([$booking_id]);
    $owner_id = $stmt->fetchColumn();
    
    if ($owner_id == $user_id) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        if ($stmt->execute([$status, $booking_id])) {
            $success = "Booking status updated successfully!";
            
            // Send notification to tenant
            $stmt = $pdo->prepare("SELECT tenant_id FROM bookings WHERE booking_id = ?");
            $stmt->execute([$booking_id]);
            $tenant_id = $stmt->fetchColumn();
            
            $message = "Your booking status has been updated to: " . ucfirst($status);
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, booking_id, message, created_at) 
                                  VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$user_id, $tenant_id, $booking_id, $message]);
            
        } else {
            $error = "Failed to update booking status.";
        }
    } else {
        $error = "You don't have permission to update this booking.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="my-bookings">
        <div class="container">
            <h1><?php echo $user_type == 'owner' ? 'Booking Requests' : 'My Bookings'; ?></h1>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($bookings)): ?>
                <div class="bookings-list">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-info">
                                <h3><?php echo $booking['title']; ?></h3>
                                <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo $booking['location']; ?></p>
                                
                                <div class="booking-dates">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - <?php echo date('M d, Y', strtotime($booking['end_date'])); ?></span>
                                </div>
                                
                                <div class="booking-contact">
                                    <?php if ($user_type == 'owner'): ?>
                                        <p><i class="fas fa-user"></i> <?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></p>
                                        <p><i class="fas fa-phone"></i> <?php echo $booking['phone']; ?></p>
                                    <?php else: ?>
                                        <p><i class="fas fa-user"></i> Owner: <?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></p>
                                        <p><i class="fas fa-phone"></i> <?php echo $booking['phone']; ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="booking-amount"><strong>KES <?php echo number_format($booking['total_amount']); ?></strong></p>
                            </div>
                            
                            <div class="booking-status">
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                                
                                <?php if ($user_type == 'owner'): ?>
                                    <form action="" method="POST" class="status-form">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <select name="status" class="form-input">
                                                                                        <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="rejected" <?php echo $booking['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                        <button type="submit" class="btn-secondary">Update</button>
                                    </form>
                                <?php endif; ?>
                                
                                <div class="booking-meta">
                                    <span class="booking-date">Booked on: <?php echo date('M d, Y', strtotime($booking['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="booking-actions">
                                <a href="view-property.php?id=<?php echo $booking['property_id']; ?>" class="btn-secondary">View Property</a>
                                <a href="messages.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn-primary">Message</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-bookings">
                    <i class="fas fa-calendar-check fa-3x"></i>
                    <h3>No Bookings Yet</h3>
                    <p><?php echo $user_type == 'owner' ? 'You haven\'t received any booking requests yet.' : 'You haven\'t made any bookings yet.'; ?></p>
                    <?php if ($user_type == 'tenant'): ?>
                        <a href="properties.php" class="btn-primary">Browse Properties</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
</body>
</html>

