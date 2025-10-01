<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not logged in
if (!checkLoggedIn()) {
    redirect('pages/login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('pages/login.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $id_number = trim($_POST['id_number']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Student-specific fields
    $current_institution = $user['user_type'] == 'tenant' ? trim($_POST['current_institution']) : null;
    $student_id = $user['user_type'] == 'tenant' ? trim($_POST['student_id']) : null;
    $year_of_study = $user['user_type'] == 'tenant' ? $_POST['year_of_study'] : null;
    $course = $user['user_type'] == 'tenant' ? trim($_POST['course']) : null;
    
    // Validation
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($id_number)) $errors[] = "ID number is required";
    
    // Check if email is already taken by another user
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $user_id]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already taken by another user";
    }
    
    // Student-specific validation
    if ($user['user_type'] == 'tenant') {
        if (empty($current_institution)) $errors[] = "Current institution is required";
        if (empty($student_id)) $errors[] = "Student ID is required";
        if (empty($year_of_study)) $errors[] = "Year of study is required";
        if (empty($course)) $errors[] = "Course is required";
    }
    
    // Password change validation
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to change password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
    }
    
    // Handle profile image upload
    $profile_image = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadResult = uploadImage($_FILES['profile_image'], '../assets/images/');
        if (!empty($uploadResult['errors'])) {
            $errors = array_merge($errors, $uploadResult['errors']);
        } else {
            $profile_image = $uploadResult['files'][0];
            
            // Delete old profile image if it's not the default
            if ($user['profile_image'] != 'default-avatar.jpg' && file_exists('../assets/images/' . $user['profile_image'])) {
                unlink('../assets/images/' . $user['profile_image']);
            }
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        try {
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, password = ?, profile_image = ?, current_institution = ?, student_id = ?, year_of_study = ?, course = ? WHERE user_id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $id_number, $hashed_password, $profile_image, $current_institution, $student_id, $year_of_study, $course, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, profile_image = ?, current_institution = ?, student_id = ?, year_of_study = ?, course = ? WHERE user_id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $id_number, $profile_image, $current_institution, $student_id, $year_of_study, $course, $user_id]);
            }
            
            $success = "Profile updated successfully!";
            
            // Update session data
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['email'] = $email;
            $_SESSION['profile_image'] = $profile_image;
            
        } catch (PDOException $e) {
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
    <title>My Profile - Kenya Coastal Student Housing</title>
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
                <h2 class="form-title">My Profile</h2>
                
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
                    <div class="form-group text-center">
                        <div class="profile-image-container">
                            <img src="../assets/images/<?php echo $user['profile_image']; ?>" 
                                 alt="Profile Image" class="profile-image"
                                 onerror="this.src='../assets/images/default-avatar.jpg'">
                            <label for="profile_image" class="profile-image-upload">
                                <i class="fas fa-camera"></i>
                                Change Photo
                            </label>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" class="hidden">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name*</label>
                            <input type="text" class="form-input" id="first_name" name="first_name" required 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : $user['first_name']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name*</label>
                            <input type="text" class="form-input" id="last_name" name="last_name" required 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : $user['last_name']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address*</label>
                            <input type="email" class="form-input" id="email" name="email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : $user['email']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number*</label>
                            <input type="tel" class="form-input" id="phone" name="phone" required 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : $user['phone']; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="id_number">ID Number*</label>
                            <input type="text" class="form-input" id="id_number" name="id_number" required 
                                   value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : $user['id_number']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="user_type">Account Type</label>
                            <input type="text" class="form-input" id="user_type" value="<?php echo ucfirst($user['user_type']); ?>" disabled>
                            <small class="form-text">Account type cannot be changed</small>
                        </div>
                    </div>
                    
                    <?php if ($user['user_type'] == 'tenant'): ?>
                        <div class="form-section-divider">
                            <h3>Student Information</h3>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="current_institution">Current Institution*</label>
                                <input type="text" class="form-input" id="current_institution" name="current_institution" required 
                                       value="<?php echo isset($_POST['current_institution']) ? htmlspecialchars($_POST['current_institution']) : $user['current_institution']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="student_id">Student ID*</label>
                                <input type="text" class="form-input" id="student_id" name="student_id" required 
                                       value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : $user['student_id']; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="year_of_study">Year of Study*</label>
                                <select class="form-select" id="year_of_study" name="year_of_study" required>
                                    <option value="">Select Year</option>
                                    <option value="1" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '1') || $user['year_of_study'] == '1' ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '2') || $user['year_of_study'] == '2' ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '3') || $user['year_of_study'] == '3' ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '4') || $user['year_of_study'] == '4' ? 'selected' : ''; ?>>4th Year</option>
                                    <option value="5" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '5') || $user['year_of_study'] == '5' ? 'selected' : ''; ?>>5th Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="course">Course/Program*</label>
                                <input type="text" class="form-input" id="course" name="course" required 
                                       value="<?php echo isset($_POST['course']) ? htmlspecialchars($_POST['course']) : $user['course']; ?>">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-section-divider">
                        <h3>Change Password</h3>
                        <p class="form-note">Leave blank if you don't want to change your password</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="current_password">Current Password</label>
                            <input type="password" class="form-input" id="current_password" name="current_password">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="new_password">New Password</label>
                            <input type="password" class="form-input" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-input" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-block">Update Profile</button>
                </form>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Profile image preview
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Trigger file input when clicking on the upload label
        document.querySelector('.profile-image-upload').addEventListener('click', function() {
            document.getElementById('profile_image').click();
        });
    </script>
</body>
</html>