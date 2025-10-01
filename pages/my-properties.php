<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not logged in or not an owner
if (!checkLoggedIn() || $_SESSION['user_type'] != 'owner') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get user's properties
$stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$properties = $stmt->fetchAll();

// Handle property deletion
if (isset($_GET['delete'])) {
    $property_id = $_GET['delete'];
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $user_id]);
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
            $stmt->execute([$property_id]);
            
            $_SESSION['success'] = "Property deleted successfully!";
            redirect('my-properties.php');
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error deleting property: " . $e->getMessage();
            redirect('my-properties.php');
        }
    } else {
        $_SESSION['error'] = "Property not found or access denied";
        redirect('my-properties.php');
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $property_id = $_POST['property_id'];
    $status = $_POST['status'];
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $user_id]);
    $property = $stmt->fetch();
    
    if ($property) {
        try {
            $stmt = $pdo->prepare("UPDATE properties SET status = ?, updated_at = NOW() WHERE property_id = ?");
            $stmt->execute([$status, $property_id]);
            
            $_SESSION['success'] = "Property status updated successfully!";
            redirect('my-properties.php');
            
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating property status: " . $e->getMessage();
            redirect('my-properties.php');
        }
    } else {
        $_SESSION['error'] = "Property not found or access denied";
        redirect('my-properties.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="properties">
        <div class="container">
            <div class="properties-header">
                <h2>My Properties</h2>
                <a href="add-property.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Add New Property
                </a>
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
            
            <?php if (!empty($properties)): ?>
                <div class="properties-grid">
                    <?php foreach ($properties as $property): 
                        $images = !empty($property['images']) ? json_decode($property['images'], true) : [];
                        $firstImage = !empty($images) ? '../assets/images/properties/' . $images[0] : '../assets/images/default-property.jpg';
                    ?>
                        <div class="property-card">
                            <img src="<?php echo $firstImage; ?>" alt="<?php echo $property['title']; ?>" 
                                 class="property-image" onerror="this.src='../assets/images/default-property.jpg'">
                            
                            <div class="property-content">
                                <h3 class="property-title"><?php echo $property['title']; ?></h3>
                                <p class="property-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo $property['location']; ?>, <?php echo $property['county']; ?>
                                </p>
                                
                                <div class="property-price">KES <?php echo number_format($property['price']); ?>/month</div>
                                
                                <div class="property-meta">
                                    <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bedrooms</span>
                                    <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bathrooms</span>
                                </div>
                                
                                <div class="property-status">
                                    <span class="status-badge status-<?php echo $property['status']; ?>">
                                        <?php echo ucfirst($property['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="property-actions">
                                    <a href="view-property.php?id=<?php echo $property['property_id']; ?>" class="btn-outline">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit-property.php?id=<?php echo $property['property_id']; ?>" class="btn-outline">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="my-properties.php?delete=<?php echo $property['property_id']; ?>" class="btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this property? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                                
                                <div class="property-status-form">
                                    <form method="POST">
                                        <input type="hidden" name="property_id" value="<?php echo $property['property_id']; ?>">
                                        <select name="status" class="form-input">
                                            <option value="available" <?php echo $property['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="rented" <?php echo $property['status'] == 'rented' ? 'selected' : ''; ?>>Rented</option>
                                            <option value="unavailable" <?php echo $property['status'] == 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-secondary">Update Status</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-properties">
                    <i class="fas fa-home fa-3x"></i>
                    <h3>No Properties Yet</h3>
                    <p>You haven't listed any properties yet. Start by adding your first property!</p>
                    <a href="add-property.php" class="btn-primary">Add Your First Property</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
</body>
</html>