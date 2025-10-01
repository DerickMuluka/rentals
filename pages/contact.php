<?php
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Basic validation
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        // Here you would typically send an email or save to database
        $success = "Thank you for your message! We'll get back to you within 24 hours.";
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="form-section">
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Contact Us</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <p><?php echo $success; ?></p>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name*</label>
                        <input type="text" class="form-input" id="name" name="name" required 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email*</label>
                        <input type="email" class="form-input" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="subject">Subject*</label>
                    <input type="text" class="form-input" id="subject" name="subject" required 
                           value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="message">Message*</label>
                    <textarea class="form-input" id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn-primary btn-block">Send Message</button>
            </form>
        </div>
        
        <div class="contact-info">
            <h3>Other Ways to Reach Us</h3>
            <div class="contact-methods">
                <div class="contact-method">
                    <i class="fas fa-phone"></i>
                    <h4>Phone</h4>
                    <p>+254 700 123 456</p>
                </div>
                <div class="contact-method">
                    <i class="fas fa-envelope"></i>
                    <h4>Email</h4>
                    <p>info@coastalstudenthousing.co.ke</p>
                </div>
                <div class="contact-method">
                    <i class="fas fa-map-marker-alt"></i>
                    <h4>Office</h4>
                    <p>Mombasa Road, Nyali<br>Mombasa, Kenya</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>