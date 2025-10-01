<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not logged in or not a tenant
if (!checkLoggedIn() || $_SESSION['user_type'] != 'tenant') {
    redirect('login.php');
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('properties.php');
}

$property_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Get property details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE property_id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    redirect('properties.php');
}

// Check if property is available
if ($property['status'] != 'available') {
    $_SESSION['error'] = "This property is not available for booking";
    redirect('properties.php');
}

// Check if user already has a pending booking for this property
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE property_id = ? AND tenant_id = ? AND status = 'pending'");
$stmt->execute([$property_id, $user_id]);
$existingBooking = $stmt->fetch();

if ($existingBooking) {
    $_SESSION['error'] = "You already have a pending booking for this property";
    redirect('view-property.php?id=' . $property_id);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $message = trim($_POST['message']);
    
    // Validation
    if (empty($start_date)) $errors[] = "Start date is required";
    if (empty($end_date)) $errors[] = "End date is required";
    if (new DateTime($start_date) < new DateTime()) $errors[] = "Start date cannot be in the past";
    if (new DateTime($end_date) <= new DateTime($start_date)) $errors[] = "End date must be after start date";
    
    // Calculate total amount
    $nights = (new DateTime($start_date))->diff(new DateTime($end_date))->days;
    $total_amount = $property['price'] * $nights;
    
    if (empty($errors)) {
        try {
            // Create booking
            $stmt = $pdo->prepare("INSERT INTO bookings (property_id, tenant_id, start_date, end_date, total_amount, status) 
                                  VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$property_id, $user_id, $start_date, $end_date, $total_amount]);
            $booking_id = $pdo->lastInsertId();
            
            // Send message to owner
            if (!empty($message)) {
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, booking_id, message) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $property['owner_id'], $booking_id, $message]);
            }
            
            $_SESSION['success'] = "Booking request sent successfully! The property owner will review your request.";
            redirect('my-bookings.php');
            
        } catch (PDOException $e) {
            $errors[] = "Error creating booking: " . $e->getMessage();
        }
    }
}

// Get property images
$images = !empty($property['images']) ? json_decode($property['images'], true) : [];
$mainImage = !empty($images) ? '../assets/images/properties/' . $images[0] : '../assets/images/default-property.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Property - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="form-section">
        <div class="container">
            <div class="booking-container">
                <div class="booking-property-info">
                    <h2>Book Property: <?php echo $property['title']; ?></h2>
                    
                    <div class="property-card">
                        <img src="<?php echo $mainImage; ?>" alt="<?php echo $property['title']; ?>" 
                             class="property-image" onerror="this.src='../assets/images/default-property.jpg'">
                        
                        <div class="property-content">
                            <h3><?php echo $property['title']; ?></h3>
                            <p class="property-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo $property['location']; ?>, <?php echo $property['county']; ?>
                            </p>
                            
                            <div class="property-price">KES <?php echo number_format($property['price']); ?>/month</div>
                            
                            <div class="property-meta">
                                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bedrooms</span>
                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bathrooms</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="booking-form-container">
                    <h2>Booking Details</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="start_date">Check-in Date*</label>
                                <input type="date" class="form-input" id="start_date" name="start_date" required 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="end_date">Check-out Date*</label>
                                <input type="date" class="form-input" id="end_date" name="end_date" required 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                       value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="message">Message to Owner</label>
                            <textarea class="form-input" id="message" name="message" rows="4" 
                                      placeholder="Tell the owner about your booking request..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <div class="booking-summary">
                            <h3>Booking Summary</h3>
                            <div class="summary-item">
                                <span>Price per month:</span>
                                <span>KES <?php echo number_format($property['price']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span>Duration:</span>
                                <span id="duration">0 nights</span>
                            </div>
                            <div class="summary-item total">
                                <span>Total Amount:</span>
                                <span id="total-amount">KES 0</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary btn-block">Confirm Booking Request</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Calculate booking duration and total amount
        function calculateBooking() {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            const pricePerMonth = <?php echo $property['price']; ?>;
            
            if (startDate && endDate && endDate > startDate) {
                const nights = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                const totalAmount = (pricePerMonth / 30) * nights; // Approximate daily rate
                
                document.getElementById('duration').textContent = nights + ' nights';
                document.getElementById('total-amount').textContent = 'KES ' + totalAmount.toLocaleString('en-KE', {maximumFractionDigits: 2});
            } else {
                document.getElementById('duration').textContent = '0 nights';
                document.getElementById('total-amount').textContent = 'KES 0';
            }
        }
        
        // Add event listeners for date changes
        document.getElementById('start_date').addEventListener('change', calculateBooking);
        document.getElementById('end_date').addEventListener('change', calculateBooking);
        
        // Initial calculation
        calculateBooking();
    </script>
</body>
</html>