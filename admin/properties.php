<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle property actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($property_id > 0) {
        switch ($action) {
            case 'delete':
                // Verify property exists
                $stmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ?");
                $stmt->execute([$property_id]);
                $property = $stmt->fetch();
                
                if ($property) {
                    try {
                        // Delete property images
                        $images = !empty($property['images']) ? json_decode($property['images'], true) : [];
                        foreach ($images as $image) {
                            $imagePath = '../assets/images/properties/' . $image;
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        }
                        
                        // Delete property
                        $stmt = $pdo->prepare("DELETE FROM properties WHERE property_id = ?");
                        if ($stmt->execute([$property_id])) {
                            $_SESSION['success'] = "Property deleted successfully";
                        } else {
                            $_SESSION['error'] = "Error deleting property";
                        }
                    } catch (PDOException $e) {
                        $_SESSION['error'] = "Database error: " . $e->getMessage();
                    }
                }
                break;
                
            case 'update_status':
                $status = isset($_GET['status']) ? $_GET['status'] : '';
                if (in_array($status, ['available', 'rented', 'unavailable'])) {
                    $stmt = $pdo->prepare("UPDATE properties SET status = ? WHERE property_id = ?");
                    if ($stmt->execute([$status, $property_id])) {
                        $_SESSION['success'] = "Property status updated successfully";
                    } else {
                        $_SESSION['error'] = "Error updating property status";
                    }
                }
                break;
        }
    }
    
    // Redirect to avoid form resubmission
    redirect('admin/properties.php');
}

// Get all properties with owner information
$properties = $pdo->query("SELECT p.*, u.first_name, u.last_name, u.email 
                          FROM properties p 
                          JOIN users u ON p.owner_id = u.user_id 
                          ORDER BY p.created_at DESC")->fetchAll();

// Get property counts by status
$propertyCounts = $pdo->query("SELECT status, COUNT(*) as count FROM properties GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php include 'admin-header.php'; ?>

<div class="admin-content">
    <div class="content-header">
        <h2>Property Management</h2>
        <div class="header-actions">
            <a href="property-add.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Property
            </a>
        </div>
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
                <i class="fas fa-home"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($propertyCounts['available'] ?? 0); ?></h3>
                <p>Available</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-key"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($propertyCounts['rented'] ?? 0); ?></h3>
                <p>Rented</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-ban"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($propertyCounts['unavailable'] ?? 0); ?></h3>
                <p>Unavailable</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format(array_sum($propertyCounts)); ?></h3>
                <p>Total Properties</p>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Type</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($properties as $property): 
                    $images = !empty($property['images']) ? json_decode($property['images'], true) : [];
                    $firstImage = !empty($images) ? '../assets/images/properties/' . $images[0] : '../assets/images/default-property.jpg';
                ?>
                    <tr>
                        <td><?php echo $property['property_id']; ?></td>
                        <td>
                            <img src="<?php echo $firstImage; ?>" 
                                 alt="<?php echo $property['title']; ?>" 
                                 class="property-thumb"
                                 onerror="this.src='../assets/images/default-property.jpg'">
                        </td>
                        <td><?php echo $property['title']; ?></td>
                        <td><?php echo $property['location']; ?>, <?php echo $property['county']; ?></td>
                        <td>KES <?php echo number_format($property['price']); ?></td>
                        <td><?php echo ucfirst($property['property_type']); ?></td>
                        <td>
                            <div class="user-info">
                                <span><?php echo $property['first_name'] . ' ' . $property['last_name']; ?></span>
                                <small><?php echo $property['email']; ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $property['status']; ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($property['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="../pages/view-property.php?id=<?php echo $property['property_id']; ?>" 
                                   class="btn-sm btn-outline" title="View" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="property-edit.php?id=<?php echo $property['property_id']; ?>" 
                                   class="btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <div class="dropdown">
                                    <button class="btn-sm btn-outline dropdown-toggle" title="Status">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="properties.php?action=update_status&id=<?php echo $property['property_id']; ?>&status=available" 
                                           class="dropdown-item">Mark Available</a>
                                        <a href="properties.php?action=update_status&id=<?php echo $property['property_id']; ?>&status=rented" 
                                           class="dropdown-item">Mark Rented</a>
                                        <a href="properties.php?action=update_status&id=<?php echo $property['property_id']; ?>&status=unavailable" 
                                           class="dropdown-item">Mark Unavailable</a>
                                    </div>
                                </div>
                                <a href="properties.php?action=delete&id=<?php echo $property['property_id']; ?>" 
                                   class="btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
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