<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if already logged in
if (checkLoggedIn()) {
    redirect('index.php'); // Changed from '../index.php'
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $userType = $_POST['user_type'];
    $idNumber = trim($_POST['id_number']);
    
    // Student-specific fields
    $currentInstitution = $userType == 'tenant' ? trim($_POST['current_institution']) : null;
    $studentId = $userType == 'tenant' ? trim($_POST['student_id']) : null;
    $yearOfStudy = $userType == 'tenant' ? $_POST['year_of_study'] : null;
    $course = $userType == 'tenant' ? trim($_POST['course']) : null;
    
    // Validation
    if (empty($firstName)) $errors[] = "First name is required";
    if (empty($lastName)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($phone)) $errors[] = "Phone number is required";
    if (empty($idNumber)) $errors[] = "ID number is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Email is already registered";
    }
    
    // Student-specific validation
    if ($userType == 'tenant') {
        if (empty($currentInstitution)) $errors[] = "Current institution is required for students";
        if (empty($studentId)) $errors[] = "Student ID is required for students";
        if (empty($yearOfStudy)) $errors[] = "Year of study is required for students";
        if (empty($course)) $errors[] = "Course is required for students";
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, user_type, id_number, current_institution, student_id, year_of_study, course) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $firstName, $lastName, $email, $phone, $hashedPassword, $userType, $idNumber,
                $currentInstitution, $studentId, $yearOfStudy, $course
            ]);
            
            $_SESSION['success'] = "Registration successful! You can now login.";
            redirect('login.php');
            
        } catch (PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Kenya Coastal Student Housing</title>
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
                <h2 class="form-title">Create Your Account</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" id="registration-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name*</label>
                            <input type="text" class="form-input" id="first_name" name="first_name" required 
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name*</label>
                            <input type="text" class="form-input" id="last_name" name="last_name" required 
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address*</label>
                            <input type="email" class="form-input" id="email" name="email" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number*</label>
                            <input type="tel" class="form-input" id="phone" name="phone" required 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="user_type">I am a*</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="">Select Account Type</option>
                                <option value="tenant" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'tenant') ? 'selected' : ''; ?>>Student (Looking for housing)</option>
                                <option value="owner" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'owner') ? 'selected' : ''; ?>>Property Owner</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="id_number">ID Number*</label>
                            <input type="text" class="form-input" id="id_number" name="id_number" required 
                                   value="<?php echo isset($_POST['id_number']) ? htmlspecialchars($_POST['id_number']) : ''; ?>">
                        </div>
                    </div>
                    
                    <!-- Student-specific fields -->
                    <div id="student-fields" style="display: <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'tenant') ? 'block' : 'none'; ?>;">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="current_institution">Current Institution*</label>
                                <input type="text" class="form-input" id="current_institution" name="current_institution" 
                                       value="<?php echo isset($_POST['current_institution']) ? htmlspecialchars($_POST['current_institution']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="student_id">Student ID*</label>
                                <input type="text" class="form-input" id="student_id" name="student_id" 
                                       value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="year_of_study">Year of Study*</label>
                                <select class="form-select" id="year_of_study" name="year_of_study">
                                    <option value="">Select Year</option>
                                    <option value="1" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '1') ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '2') ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '3') ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '4') ? 'selected' : ''; ?>>4th Year</option>
                                    <option value="5" <?php echo (isset($_POST['year_of_study']) && $_POST['year_of_study'] == '5') ? 'selected' : ''; ?>>5th Year</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="course">Course/Program*</label>
                                <input type="text" class="form-input" id="course" name="course" 
                                       value="<?php echo isset($_POST['course']) ? htmlspecialchars($_POST['course']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="password">Password*</label>
                            <input type="password" class="form-input" id="password" name="password" required>
                            <small class="form-text">Must be at least 6 characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password*</label>
                            <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required>
                            I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>*
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-block">Create Account</button>
                </form>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
         // Toggle student fields based on user type
        document.getElementById('user_type').addEventListener('change', function() {
            const studentFields = document.getElementById('student-fields');
            studentFields.style.display = this.value === 'tenant' ? 'block' : 'none';
            
            // Toggle required attribute for student fields
            const studentInputs = studentFields.querySelectorAll('input, select');
            studentInputs.forEach(input => {
                input.required = this.value === 'tenant';
            });
        });
    </script>
</body>
</html>
