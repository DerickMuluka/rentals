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

// Get universities for proximity information
$universities = $pdo->query("SELECT * FROM universities ORDER BY name")->fetchAll();

// Define available amenities and security features
$availableAmenities = [
    'wifi', 'water', 'electricity', 'furnished', 'parking', 'security', 
    'garden', 'balcony', 'laundry', 'kitchen', 'tv', 'air_conditioning'
];

$availableSecurity = [
    'cctv', 'security_guard', 'alarm_system', 'gated_community', 
    'secure_access', 'fire_safety'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
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
    $proximity_to_beach = !empty($_POST['proximity_to_beach']) ? $_POST['proximity_to_beach'] : null;
    $semester_pricing = isset($_POST['semester_pricing']) ? 1 : 0;
    
    // Validation
    if (empty($title)) $errors[] = "Title is required";
    if (empty($description)) $errors[] = "Description is required";
    if (empty($price) || $price <= 0) $errors[] = "Valid price is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($bedrooms) || $bedrooms <= 0) $errors[] = "Valid number of bedrooms is required";
    if (empty($bathrooms) || $bathrooms <= 0) $errors[] = "Valid number of bathrooms is required";
    
    // Handle image upload
    $uploadedImages = [];
    if (!empty($_FILES['images']['name'][0])) {
        $uploadResult = uploadImage($_FILES['images']);
        if (!empty($uploadResult['errors'])) {
            $errors = array_merge($errors, $uploadResult['errors']);
        } else {
            $uploadedImages = $uploadResult['files'];
        }
    } else {
        $errors[] = "At least one image is required";
    }
    
    // If no errors, insert property
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert property
            $stmt = $pdo->prepare("INSERT INTO properties (owner_id, title, description, price, location, county, property_type, bedrooms, bathrooms, size, amenities, security_features, images, proximity_to_beach, semester_pricing) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $amenities_json = !empty($amenities) ? json_encode($amenities) : null;
            $security_json = !empty($security_features) ? json_encode($security_features) : null;
            $images_json = !empty($uploadedImages) ? json_encode($uploadedImages) : null;
            
            $stmt->execute([
                $user_id, $title, $description, $price, $location, $county, $property_type, 
                $bedrooms, $bathrooms, $size, $amenities_json, $security_json, $images_json, 
                $proximity_to_beach, $semester_pricing
            ]);
            
            $property_id = $pdo->lastInsertId();
            
            // Insert university proximity information
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
            $success = "Property added successfully!";
            
            // Redirect to my properties page
            $_SESSION['success'] = $success;
            redirect('my-properties.php');
            
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
    <title>Add Property - Kenya Coastal Student Housing</title>
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
                <h2 class="form-title">Add New Property</h2>
                
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
                    <div class="form-group">
                        <label class="form-label" for="title">Property Title*</label>
                        <input type="text" class="form-input" id="title" name="title" required 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="description">Description*</label>
                        <textarea class="form-input" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="property_type">Property Type*</label>
                            <select class="form-select" id="property_type" name="property_type" required>
                                <option value="">Select Property Type</option>
                                <option value="apartment" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                                <option value="house" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] == 'house') ? 'selected' : ''; ?>>House</option>
                                <option value="bedsitter" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] == 'bedsitter') ? 'selected' : ''; ?>>Bedsitter</option>
                                <option value="studio" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] == 'studio') ? 'selected' : ''; ?>>Studio</option>
                                <option value="hostel" <?php echo (isset($_POST['property_type']) && $_POST['property_type'] == 'hostel') ? 'selected' : ''; ?>>Hostel</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="price">Monthly Price (KES)*</label>
                            <input type="number" class="form-input" id="price" name="price" min="0" step="0.01" required 
                                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="location">Location*</label>
                            <input type="text" class="form-input" id="location" name="location" required 
                                   value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="county">County*</label>
                            <select class="form-select" id="county" name="county" required>
                                <option value="">Select County</option>
                                <option value="Mombasa" <?php echo (isset($_POST['county']) && $_POST['county'] == 'Mombasa') ? 'selected' : ''; ?>>Mombasa</option>
                                <option value="Kilifi" <?php echo (isset($_POST['county']) && $_POST['county'] == 'Kilifi') ? 'selected' : ''; ?>>Kilifi</option>
                                <option value="Kwale" <?php echo (isset($_POST['county']) && $_POST['county'] == 'Kwale') ? 'selected' : ''; ?>>Kwale</option>
                                <option value="Lamu" <?php echo (isset($_POST['county']) && $_POST['county'] == 'Lamu') ? 'selected' : ''; ?>>Lamu</option>
                                <option value="Tana River" <?php echo (isset($_POST['county']) && $_POST['county'] == 'Tana River') ? 'selected' : ''; ?>>Tana River</option>
                                <option value="Taita-Taveta" <?php echo (isset($_POST['county']) && $_POST['county'] == 'Taita-Taveta') ? 'selected' : ''; ?>>Taita-Taveta</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="bedrooms">Bedrooms*</label>
                            <input type="number" class="form-input" id="bedrooms" name="bedrooms" min="1" required 
                                   value="<?php echo isset($_POST['bedrooms']) ? htmlspecialchars($_POST['bedrooms']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="bathrooms">Bathrooms*</label>
                            <input type="number" class="form-input" id="bathrooms" name="bathrooms" min="1" required 
                                   value="<?php echo isset($_POST['bathrooms']) ? htmlspecialchars($_POST['bathrooms']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="size">Size (sq ft)</label>
                            <input type="text" class="form-input" id="size" name="size" 
                                   value="<?php echo isset($_POST['size']) ? htmlspecialchars($_POST['size']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="proximity_to_beach">Distance to Beach (km)</label>
                            <input type="number" class="form-input" id="proximity_to_beach" name="proximity_to_beach" min="0" step="0.1" 
                                   value="<?php echo isset($_POST['proximity_to_beach']) ? htmlspecialchars($_POST['proximity_to_beach']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="semester_pricing" value="1" <?php echo (isset($_POST['semester_pricing']) && $_POST['semester_pricing'] == '1') ? 'checked' : ''; ?>>
                                Offer semester-based pricing
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Amenities</label>
                        <div class="checkbox-group">
                            <?php foreach ($availableAmenities as $amenity): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="amenities[]" value="<?php echo $amenity; ?>" 
                                        <?php echo (isset($_POST['amenities']) && in_array($amenity, $_POST['amenities'])) ? 'checked' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $amenity)); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Security Features</label>
                        <div class="checkbox-group">
                            <?php foreach ($availableSecurity as $feature): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="security_features[]" value="<?php echo $feature; ?>" 
                                        <?php echo (isset($_POST['security_features']) && in_array($feature, $_POST['security_features'])) ? 'checked' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $feature)); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">University Proximity</label>
                        <div id="university-proximity">
                            <div class="university-row">
                                <select name="university_id[]" class="form-input">
                                    <option value="">Select University</option>
                                    <?php foreach ($universities as $university): ?>
                                        <option value="<?php echo $university['university_id']; ?>"><?php echo $university['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="distance[]" placeholder="Distance (km)" step="0.1" min="0" class="form-input">
                                <input type="text" name="transport_options[]" placeholder="Transport options" class="form-input">
                                <input type="number" name="student_discount[]" placeholder="Student discount %" min="0" max="100" class="form-input">
                                <button type="button" class="btn-secondary remove-university">Remove</button>
                            </div>
                        </div>
                        <button type="button" class="btn-outline" id="add-university">Add Another University</button>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="images">Property Images*</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" class="form-input" required>
                        <small>Maximum 5 images, each less than 5MB. First image will be used as the main display image.</small>
                        <div class="image-preview-container"></div>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-block">Add Property</button>
                </form>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Initialize university proximity fields
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('university-proximity');
            const addButton = document.getElementById('add-university');
            
            if (addButton && container) {
                addButton.addEventListener('click', function() {
                    const row = document.createElement('div');
                    row.className = 'university-row';
                    row.innerHTML = `
                        <select name="university_id[]" class="form-input">
                            <option value="">Select University</option>
                            <?php foreach ($universities as $university): ?>
                                <option value="<?php echo $university['university_id']; ?>"><?php echo $university['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="distance[]" placeholder="Distance (km)" step="0.1" min="0" class="form-input">
                        <input type="text" name="transport_options[]" placeholder="Transport options" class="form-input">
                        <input type="number" name="student_discount[]" placeholder="Student discount %" min="0" max="100" class="form-input">
                        <button type="button" class="btn-secondary remove-university">Remove</button>
                    `;
                    
                    container.appendChild(row);
                    
                    // Add remove functionality
                    row.querySelector('.remove-university').addEventListener('click', function() {
                        container.removeChild(row);
                    });
                });
                
                // Initialize existing remove buttons
                const removeButtons = container.querySelectorAll('.remove-university');
                removeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        this.closest('.university-row').remove();
                    });
                });
            }
        });
    </script>
</body>
</html>