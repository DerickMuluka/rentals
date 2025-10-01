<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle booking actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($booking_id > 0) {
        switch ($action) {
            case 'update_status':
                $status = isset($_GET['status']) ? $_GET['status'] : '';
                if (in_array($status, ['pending', 'approved', 'rejected', 'completed'])) {
                    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
                    if ($stmt->execute([$status, $booking_id])) {
                        $_SESSION['success'] = "Booking status updated successfully";
                    } else {
                        $_SESSION['error'] = "Error updating booking status";
                    }
                }
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE booking_id = ?");
                if ($stmt->execute([$booking_id])) {
                    $_SESSION['success'] = "Booking deleted successfully";
                } else {
                    $_SESSION['error'] = "Error deleting booking";
                }
                break;
        }
    }
    
    // Redirect to avoid form resubmission
    redirect('admin/bookings.php');
}

// Get all bookings with related information
$bookings = $pdo->query("SELECT b.*, p.title as property_title, p.location, 
                         t.first_name as tenant_first, t.last_name as tenant_last, t.email as tenant_email,
                         o.first_name as owner_first, o.last_name as owner_last, o.email as owner_email
                         FROM bookings b
                         JOIN properties p ON b.property_id = p.property_id
                         JOIN users t ON b.tenant_id = t.user_id
                         JOIN users o ON p.owner_id = o.user_id
                         ORDER BY b.created_at DESC")->fetchAll();

// Get booking counts by status
$bookingCounts = $pdo->query("SELECT status, COUNT(*) as count FROM bookings GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php include 'admin-header.php'; ?>

<div class="admin-content">
    <div class="content-header">
        <h2>Booking Management</h2>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($bookingCounts['pending'] ?? 0); ?></h3>
                <p>Pending</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($bookingCounts['approved'] ?? 0); ?></h3>
                <p>Approved</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($bookingCounts['rejected'] ?? 0); ?></h3>
                <p>Rejected</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($bookingCounts['completed'] ?? 0); ?></h3>
                <p>Completed</p>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Property</th>
                    <th>Tenant</th>
                    <th>Owner</th>
                    <th>Dates</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo $booking['booking_id']; ?></td>
                        <td>
                            <strong><?php echo $booking['property_title']; ?></strong>
                            <br>
                            <small><?php echo $booking['location']; ?></small>
                        </td>
                        <td>
                            <?php echo $booking['tenant_first'] . ' ' . $booking['tenant_last']; ?>
                            <br>
                            <small><?php echo $booking['tenant_email']; ?></small>
                        </td>
                        <td>
                            <?php echo $booking['owner_first'] . ' ' . $booking['owner_last']; ?>
                            <br>
                            <small><?php echo $booking['owner_email']; ?></small>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> 
                            to 
                            <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                        </td>
                        <td>KES <?php echo number_format($booking['total_amount']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="booking-view.php?id=<?php echo $booking['booking_id']; ?>" 
                                   class="btn-sm btn-outline" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <div class="dropdown">
                                    <button class="btn-sm btn-outline dropdown-toggle" title="Status">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="bookings.php?action=update_status&id=<?php echo $booking['booking_id']; ?>&status=pending" 
                                           class="dropdown-item">Mark Pending</a>
                                        <a href="bookings.php?action=update_status&id=<?php echo $booking['booking_id']; ?>&status=approved" 
                                           class="dropdown-item">Approve</a>
                                        <a href="bookings.php?action=update_status&id=<?php echo $booking['booking_id']; ?>&status=rejected" 
                                           class="dropdown-item">Reject</a>
                                        <a href="bookings.php?action=update_status&id=<?php echo $booking['booking_id']; ?>&status=completed" 
                                           class="dropdown-item">Mark Completed</a>
                                    </div>
                                </div>
                                <a href="bookings.php?action=delete&id=<?php echo $booking['booking_id']; ?>" 
                                   class="btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Close admin-main and admin-container divs
echo '</main></div>'; 
include '../includes/footer.php'; 
?>