<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not logged in or not an owner
if (!checkLoggedIn() || $_SESSION['user_type'] != 'owner') {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get property ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('my-properties.php');
}

$property_id = $_GET['id'];

// Verify ownership
$stmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ? AND owner_id = ?");
$stmt->execute([$property_id, $user_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    redirect('my-properties.php');
}

// Get universities and existing proximity data
$universities = $pdo->query("SELECT * FROM universities ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM university_properties WHERE property_id = ?");
$stmt->execute([$property_id]);
$university_proximity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Decode JSON fields
$property['amenities'] = !empty($property['amenities']) ? json_decode($property['amenities'], true) : [];
$property['security_features'] = !empty($property['security_features']) ? json_decode($property['security_features'], true) : [];
$property['images'] = !empty($property['images']) ? json_decode($property['images'], true) : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input (similar to add-property.php)
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $property_type = $_POST['property_type'];
    $price = $_POST['price'];
    $location = trim($_POST['location']);
    $county = $_POST['county'];
    $bedrooms = $_POST['bedrooms'];
    $bathrooms = $_POST['bathrooms'];
    $size = $_POST['size'];
    $amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
    $security_features = isset($_POST['security_features']) ? $_POST['security_features'] : [];
    
    // Validation (same as add-property.php)
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($price) || $price <= 0) $errors[] = "Valid price is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($bedrooms) || $bedrooms <= 0) $errors[] = "Valid number of bedrooms is required";
    if (empty($bathrooms) || $bathrooms <= 0) $errors[] = "Valid number of bathrooms is required";
    
    // Handle image upload and deletion
    $current_images = $property['images'];
    $images_to_keep = isset($_POST['keep_images']) ? $_POST['keep_images'] : [];
    
    // Remove images marked for deletion
    $updated_images = array_intersect($current_images, $images_to_keep);
    
    // Handle new image uploads
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['images']['name'][$key];
                $file_size = $_FILES['images']['size'][$key];
                $file_tmp = $_FILES['images']['tmp_name'][$key];
                $file_type = $_FILES['images']['type'][$key];
                
                // Validate file
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = "Only JPG, PNG, and GIF files are allowed";
                    break;
                }
                
                if ($file_size > $max_size) {
                    $errors[] = "File size must be less than 5MB";
                    break;
                }
                
                // Generate unique filename
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_ext;
                $upload_path = '../assets/images/properties/' . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $updated_images[] = $new_filename;
                }
            }
        }
    }
    
    // If no errors, update property
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Update property
            $stmt = $pdo->prepare("UPDATE properties SET title = ?, description = ?, property_type = ?, price = ?, location = ?, county = ?, bedrooms = ?, bathrooms = ?, size = ?, amenities = ?, security_features = ?, images = ?, updated_at = NOW() WHERE property_id = ?");
            
            $amenities_json = !empty($amenities) ? json_encode($amenities) : null;
            $security_json = !empty($security_features) ? json_encode($security_features) : null;
            $images_json = !empty($updated_images) ? json_encode($updated_images) : null;
            
            $stmt->execute([$title, $description, $property_type, $price, $location, $county, $bedrooms, $bathrooms, $size, $amenities_json, $security_json, $images_json, $property_id]);
            
            // Update university proximity information
            $pdo->prepare("DELETE FROM university_properties WHERE property_id = ?")->execute([$property_id]);
            
            if (isset($_POST['university_id']) && !empty($_POST['university_id'])) {
                foreach ($_POST['university_id'] as $index => $university_id) {
                    if (!empty($university_id) && !empty($_POST['distance'][$index])) {
                        $stmt = $pdo->prepare("INSERT INTO university_properties (property_id, university_id, distance, transport_options, student_discount) 
                                              VALUES (?, ?, ?, ?, ?)");
                        $transport = $_POST['transport_options'][$index] ?? '';
                        $discount = $_POST['student_discount'][$index] ?? 0;
                        $stmt->execute([$property_id, $university_id, $_POST['distance'][$index], $transport, $discount]);
                    }
                }
            }
            
            $pdo->commit();
            $success = "Property updated successfully!";
            
            // Refresh property data
            $stmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ?");
            $stmt->execute([$property_id]);
            $property = $stmt->fetch(PDO::FETCH_ASSOC);
            $property['amenities'] = !empty($property['amenities']) ? json_decode($property['amenities'], true) : [];
            $property['security_features'] = !empty($property['security_features']) ? json_decode($property['security_features'], true) : [];
            $property['images'] = !empty($property['images']) ? json_decode($property['images'], true) : [];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="form-section">
        <div class="container">
            <div class="form-container">
                <h2 class="form-title">Edit Property</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <!-- Form fields are the same as add-property.php but with pre-filled values -->
                    <div class="form-group">
                        <label class="form-label" for="title">Property Title*</label>
                        <input type="text" class="form-input" id="title" name="title" required 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : $property['title']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description*</label>
                        <textarea class="form-input" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : $property['description']; ?></textarea>
                    </div>
                    
                    <!-- Continue with all other form fields from add-property.php, pre-filled with $property data -->
                    
                    <!-- Current Images -->
                    <div class="form-group">
                        <label class="form-label">Current Images</label>
                        <div class="current-images">
                            <?php if (!empty($property['images'])): ?>
                                <?php foreach ($property['images'] as $image): ?>
                                    <div class="image-preview">
                                        <img src="../assets/images/properties/<?php echo $image; ?>" 
                                             alt="Property image" 
                                             onerror="this.src='../assets/images/default-property.jpg'">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="keep_images[]" value="<?php echo $image; ?>" checked>
                                            Keep this image
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No images uploaded yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- New Image Upload -->
                    <div class="form-group">
                        <label class="form-label" for="images">Add More Images</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" class="form-input">
                        <small>Maximum 5 images, each less than 5MB</small>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-block">Update Property</button>
                </form>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Dynamic university proximity fields (same as add-property.php)
        // Pre-fill existing university proximity data
        <?php if (!empty($university_proximity)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const container = document.getElementById('university-proximity');
                container.innerHTML = ''; // Clear default row
                
                <?php foreach ($university_proximity as $index => $proximity): ?>
                    const row = document.createElement('div');
                    row.className = 'university-row';
                    row.innerHTML = `
                        <select name="university_id[]" class="form-input">
                            <option value="">Select University</option>
                            <?php foreach ($universities as $university): ?>
                                <option value="<?php echo $university['university_id']; ?>" 
                                    <?php echo $proximity['university_id'] == $university['university_id'] ? 'selected' : ''; ?>>
                                    <?php echo $university['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="distance[]" placeholder="Distance (km)" step="0.1" min="0" 
                               value="<?php echo $proximity['distance']; ?>" class="form-input">
                        <input type="text" name="transport_options[]" placeholder="Transport options" 
                               value="<?php echo htmlspecialchars($proximity['transport_options']); ?>" class="form-input">
                        <input type="number" name="student_discount[]" placeholder="Student discount %" min="0" max="100" 
                               value="<?php echo $proximity['student_discount']; ?>" class="form-input">
                        <button type="button" class="btn-secondary remove-university">Remove</button>
                    `;
                    container.appendChild(row);
                    
                    row.querySelector('.remove-university').addEventListener('click', function() {
                        container.removeChild(row);
                    });
                <?php endforeach; ?>
            });
        <?php endif; ?>
    </script>
</body>
</html>
