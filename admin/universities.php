<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle university actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $university_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($university_id > 0) {
        switch ($action) {
            case 'delete':
                // Verify university exists
                $stmt = $pdo->prepare("SELECT * FROM universities WHERE university_id = ?");
                $stmt->execute([$university_id]);
                $university = $stmt->fetch();
                
                if ($university) {
                    // Delete university logo if it's not the default
                    if ($university['logo'] != 'default-university.png' && file_exists('../assets/images/universities/' . $university['logo'])) {
                        unlink('../assets/images/universities/' . $university['logo']);
                    }
                    
                    // Delete university
                    $stmt = $pdo->prepare("DELETE FROM universities WHERE university_id = ?");
                    if ($stmt->execute([$university_id])) {
                        $_SESSION['success'] = "University deleted successfully";
                    } else {
                        $_SESSION['error'] = "Error deleting university";
                    }
                }
                break;
        }
    }
    
    // Redirect to avoid form resubmission
    redirect('admin/universities.php');
}

// Handle form submission for adding/editing universities
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $county = trim($_POST['county']);
    $description = trim($_POST['description']);
    $university_id = isset($_POST['university_id']) ? (int)$_POST['university_id'] : 0;
    
    // Validation
    $errors = [];
    if (empty($name)) $errors[] = "University name is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($county)) $errors[] = "County is required";
    
    if (empty($errors)) {
        try {
            // Handle logo upload
            $logo = null;
            if (!empty($_FILES['logo']['name'])) {
                $uploadResult = uploadImage($_FILES['logo'], '../assets/images/universities/');
                if (!empty($uploadResult['errors'])) {
                    $errors = array_merge($errors, $uploadResult['errors']);
                } else {
                    $logo = $uploadResult['files'][0];
                }
            }
            
            if (empty($errors)) {
                if ($university_id > 0) {
                    // Update existing university
                    if ($logo) {
                        $stmt = $pdo->prepare("UPDATE universities SET name = ?, location = ?, county = ?, description = ?, logo = ? WHERE university_id = ?");
                        $stmt->execute([$name, $location, $county, $description, $logo, $university_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE universities SET name = ?, location = ?, county = ?, description = ? WHERE university_id = ?");
                        $stmt->execute([$name, $location, $county, $description, $university_id]);
                    }
                    $_SESSION['success'] = "University updated successfully";
                } else {
                    // Insert new university
                    $stmt = $pdo->prepare("INSERT INTO universities (name, location, county, description, logo) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $location, $county, $description, $logo ?: 'default-university.png']);
                    $_SESSION['success'] = "University added successfully";
                }
                
                redirect('admin/universities.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Check if editing a university
$editingUniversity = null;
if (isset($_GET['edit'])) {
    $university_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM universities WHERE university_id = ?");
    $stmt->execute([$university_id]);
    $editingUniversity = $stmt->fetch();
}

// Get all universities
$universities = $pdo->query("SELECT u.*, COUNT(up.property_id) as properties_count 
                            FROM universities u 
                            LEFT JOIN university_properties up ON u.university_id = up.university_id 
                            GROUP BY u.university_id 
                            ORDER BY u.name")->fetchAll();
?>

<?php include 'admin-header.php'; ?>

<div class="admin-content">
    <div class="content-header">
        <h2>University Management</h2>
        <div class="header-actions">
            <button class="btn-primary" id="toggle-university-form">
                <i class="fas fa-plus"></i> <?php echo $editingUniversity ? 'Edit' : 'Add'; ?> University
            </button>
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
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- University Form -->
    <div class="form-container <?php echo $editingUniversity ? 'active' : ''; ?>" id="university-form">
        <h3><?php echo $editingUniversity ? 'Edit University' : 'Add New University'; ?></h3>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editingUniversity): ?>
                <input type="hidden" name="university_id" value="<?php echo $editingUniversity['university_id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">University Name*</label>
                    <input type="text" class="form-input" id="name" name="name" required 
                           value="<?php echo $editingUniversity ? htmlspecialchars($editingUniversity['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="location">Location*</label>
                    <input type="text" class="form-input" id="location" name="location" required 
                           value="<?php echo $editingUniversity ? htmlspecialchars($editingUniversity['location']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="county">County*</label>
                    <select class="form-select" id="county" name="county" required>
                        <option value="">Select County</option>
                        <option value="Mombasa" <?php echo ($editingUniversity && $editingUniversity['county'] == 'Mombasa') ? 'selected' : ''; ?>>Mombasa</option>
                        <option value="Kilifi" <?php echo ($editingUniversity && $editingUniversity['county'] == 'Kilifi') ? 'selected' : ''; ?>>Kilifi</option>
                        <option value="Kwale" <?php echo ($editingUniversity && $editingUniversity['county'] == 'Kwale') ? 'selected' : ''; ?>>Kwale</option>
                        <option value="Lamu" <?php echo ($editingUniversity && $editingUniversity['county'] == 'Lamu') ? 'selected' : ''; ?>>Lamu</option>
                        <option value="Tana River" <?php echo ($editingUniversity && $editingUniversity['county'] == 'Tana River') ? 'selected' : ''; ?>>Tana River</option>
                        <option value="Taita-Taveta" <?php echo ($editingUniversity && $editingUniversity['county'] == 'Taita-Taveta') ? 'selected' : ''; ?>>Taita-Taveta</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="logo">University Logo</label>
                    <input type="file" class="form-input" id="logo" name="logo" accept="image/*">
                    <?php if ($editingUniversity && $editingUniversity['logo']): ?>
                        <div class="current-logo">
                            <img src="../assets/images/universities/<?php echo $editingUniversity['logo']; ?>" 
                                 alt="Current Logo" 
                                 onerror="this.src='../assets/images/default-university.png'">
                            <small>Current logo</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-input" id="description" name="description" rows="4"><?php echo $editingUniversity ? htmlspecialchars($editingUniversity['description']) : ''; ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary"><?php echo $editingUniversity ? 'Update' : 'Add'; ?> University</button>
                <a href="universities.php" class="btn-outline">Cancel</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Logo</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>County</th>
                    <th>Properties</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($universities as $university): ?>
                    <tr>
                        <td><?php echo $university['university_id']; ?></td>
                        <td>
                            <img src="../assets/images/universities/<?php echo $university['logo']; ?>" 
                                 alt="<?php echo $university['name']; ?>" 
                                 class="university-thumb"
                                 onerror="this.src='../assets/images/default-university.png'">
                        </td>
                        <td>
                            <strong><?php echo $university['name']; ?></strong>
                            <?php if ($university['description']): ?>
                                <br>
                                <small><?php echo substr($university['description'], 0, 50); ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $university['location']; ?></td>
                        <td><?php echo $university['county']; ?></td>
                        <td><?php echo $university['properties_count']; ?> properties</td>
                        <td>
                            <div class="action-buttons">
                                <a href="../pages/university-properties.php?id=<?php echo $university['university_id']; ?>" 
                                   class="btn-sm btn-outline" title="View Properties" target="_blank">
                                    <i class="fas fa-home"></i>
                                </a>
                                <a href="universities.php?edit=<?php echo $university['university_id']; ?>" 
                                   class="btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="universities.php?action=delete&id=<?php echo $university['university_id']; ?>" 
                                   class="btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this university? This action cannot be undone.')">
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

<script>
    // Toggle university form visibility
    document.getElementById('toggle-university-form').addEventListener('click', function() {
        const form = document.getElementById('university-form');
        form.classList.toggle('active');
        this.innerHTML = form.classList.contains('active') 
            ? '<i class="fas fa-times"></i> Cancel' 
            : '<i class="fas fa-plus"></i> Add University';
    });
</script>