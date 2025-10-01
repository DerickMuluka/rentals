<?php
include '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Initialize email variable to prevent undefined key warning
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step === 1) {
        // Step 1: Email verification - use the initialized $email variable
        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (empty($errors)) {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate verification code
                $verification_code = rand(100000, 999999);
                
                // Store code in session for verification
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_code'] = $verification_code;
                $_SESSION['reset_expiry'] = time() + 900; // 15 minutes expiry
                
                // In a real application, you would send this code via email
                // For now, we'll just show it (remove this in production)
                $success = "Verification code generated: " . $verification_code;
                
                // Move to next step
                header("Location: forgot-password.php?step=2");
                exit();
            } else {
                $errors[] = "No account found with this email address";
            }
        }
    } elseif ($step === 2) {
        // Step 2: Code verification
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        
        if (empty($code)) {
            $errors[] = "Verification code is required";
        } elseif (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_expiry'])) {
            $errors[] = "Session expired. Please start over.";
            header("Location: forgot-password.php?step=1");
            exit();
        } elseif (time() > $_SESSION['reset_expiry']) {
            $errors[] = "Verification code has expired. Please request a new one.";
            session_destroy();
            header("Location: forgot-password.php?step=1");
            exit();
        } elseif ($code != $_SESSION['reset_code']) {
            $errors[] = "Invalid verification code";
        }
        
        if (empty($errors)) {
            // Code verified, move to password reset step
            header("Location: forgot-password.php?step=3");
            exit();
        }
    } elseif ($step === 3) {
        // Step 3: Password reset
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        } elseif ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($errors) && isset($_SESSION['reset_email'])) {
            // Update password in database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            
            if ($stmt->execute([$hashed_password, $_SESSION['reset_email']])) {
                // Clear reset session
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_code']);
                unset($_SESSION['reset_expiry']);
                
                $success = "Password reset successfully! You can now <a href='login.php'>login</a> with your new password.";
                $step = 4; // Completion step
            } else {
                $errors[] = "Error resetting password. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        .form-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .form-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 30%;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: bold;
        }
        
        .step.active .step-number {
            background: var(--primary-color);
            color: white;
        }
        
        .step.completed .step-number {
            background: var(--success-color);
            color: white;
        }
        
        .step-text {
            font-size: 0.9rem;
            color: #666;
        }
        
        .step.active .step-text {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .step.completed .step-text {
            color: var(--success-color);
        }
        
        .text-success {
            color: var(--success-color);
        }
        
        .verification-code {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .verification-code input {
            width: 40px;
            height: 40px;
            text-align: center;
            font-size: 1.2rem;
            border: 2px solid var(--gray-color);
            border-radius: 4px;
        }
        
        .verification-code input:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        @media (max-width: 576px) {
            .step-text {
                font-size: 0.8rem;
            }
            
            .verification-code {
                gap: 0.25rem;
            }
            
            .verification-code input {
                width: 35px;
                height: 35px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="form-section">
        <div class="container">
            <div class="form-container">
                <h2 class="form-title">Password Recovery</h2>
                
                <!-- Progress Steps -->
                <div class="form-steps">
                    <div class="step <?php echo $step >= 1 ? 'completed' : ''; ?> <?php echo $step == 1 ? 'active' : ''; ?>">
                        <div class="step-number">1</div>
                        <div class="step-text">Verify Email</div>
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'completed' : ''; ?> <?php echo $step == 2 ? 'active' : ''; ?>">
                        <div class="step-number">2</div>
                        <div class="step-text">Enter Code</div>
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'completed' : ''; ?> <?php echo $step >= 3 ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-text">New Password</div>
                    </div>
                </div>
                
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
                
                <?php if ($step === 1): ?>
                    <p>Enter your email address to receive a verification code.</p>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address*</label>
                            <input type="email" class="form-input" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <button type="submit" class="btn-primary btn-block">Send Verification Code</button>
                    </form>
                    
                <?php elseif ($step === 2): ?>
                    <p>Check your email for the verification code and enter it below.</p>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="code">Verification Code*</label>
                            <div class="verification-code">
                                <input type="text" id="code" name="code" required 
                                       placeholder="0" maxlength="6" pattern="[0-9]{6}" 
                                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length > 6) this.value = this.value.slice(0,6);">
                            </div>
                            <small class="form-text">Enter the 6-digit code sent to your email</small>
                        </div>
                        <button type="submit" class="btn-primary btn-block">Verify Code</button>
                    </form>
                    
                    <div class="form-footer">
                        <p>Didn't receive the code? <a href="?step=1">Request a new one</a></p>
                    </div>
                    
                <?php elseif ($step === 3): ?>
                    <p>Enter your new password below.</p>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="password">New Password*</label>
                            <input type="password" class="form-input" id="password" name="password" required>
                            <small class="form-text">Must be at least 6 characters long</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password*</label>
                            <input type="password" class="form-input" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn-primary btn-block">Reset Password</button>
                    </form>
                    
                <?php elseif ($step === 4): ?>
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success" style="color: var(--success-color);"></i>
                        <h3>Password Reset Successful!</h3>
                        <p>Your password has been reset successfully. You can now login with your new password.</p>
                        <a href="login.php" class="btn-primary">Go to Login</a>
                    </div>
                <?php endif; ?>
                
                <div class="form-footer">
                    <p>Remember your password? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Auto-focus on code input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('code');
            if (codeInput) {
                codeInput.focus();
                
                // Auto-advance to next input (for multi-digit code entry)
                codeInput.addEventListener('input', function() {
                    if (this.value.length === 6) {
                        this.form.submit();
                    }
                });
            }
        });
    </script>
</body>
</html>