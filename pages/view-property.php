<?php
include '../includes/config.php';
include '../includes/auth.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: properties.php");
    exit();
}

$propertyId = $_GET['id'];

// Get property details
$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.phone as owner_phone, u.email as owner_email 
                      FROM properties p 
                      JOIN users u ON p.owner_id = u.user_id 
                      WHERE p.property_id = ?");
$stmt->execute([$propertyId]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: properties.php");
    exit();
}

// Decode JSON fields
$amenities = !empty($property['amenities']) ? json_decode($property['amenities'], true) : [];
$securityFeatures = !empty($property['security_features']) ? json_decode($property['security_features'], true) : [];
$images = !empty($property['images']) ? json_decode($property['images'], true) : [];

// Get university proximity information
$universityProximity = $pdo->prepare("SELECT u.name, up.distance, up.transport_options, up.student_discount 
                                     FROM university_properties up 
                                     JOIN universities u ON up.university_id = u.university_id 
                                     WHERE up.property_id = ?");
$universityProximity->execute([$propertyId]);
$universities = $universityProximity->fetchAll();

// Get reviews
$reviews = $pdo->prepare("SELECT r.*, u.first_name, u.last_name 
                         FROM reviews r 
                         JOIN users u ON r.user_id = u.user_id 
                         WHERE r.property_id = ? 
                         ORDER BY r.created_at DESC");
$reviews->execute([$propertyId]);
$reviews = $reviews->fetchAll();

// Calculate average rating
$avgRating = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                           FROM reviews 
                           WHERE property_id = ?");
$avgRating->execute([$propertyId]);
$ratingData = $avgRating->fetch();
$averageRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
$totalReviews = $ratingData['total_reviews'];

// Check if user has already reviewed this property
$userReview = null;
if (checkLoggedIn()) {
    $checkReview = $pdo->prepare("SELECT * FROM reviews WHERE property_id = ? AND user_id = ?");
    $checkReview->execute([$propertyId, $_SESSION['user_id']]);
    $userReview = $checkReview->fetch();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review']) && checkLoggedIn()) {
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);
    
    if (empty($userReview)) {
        $insertReview = $pdo->prepare("INSERT INTO reviews (property_id, user_id, rating, comment) 
                                      VALUES (?, ?, ?, ?)");
        $insertReview->execute([$propertyId, $_SESSION['user_id'], $rating, $comment]);
        $_SESSION['success'] = "Review submitted successfully!";
        header("Location: view-property.php?id=$propertyId");
        exit();
    }
}

// Handle booking inquiry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_inquiry']) && checkLoggedIn()) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        // Create a booking inquiry (pending status)
        $checkBooking = $pdo->prepare("SELECT * FROM bookings WHERE property_id = ? AND tenant_id = ? AND status = 'pending'");
        $checkBooking->execute([$propertyId, $_SESSION['user_id']]);
        
        if (!$checkBooking->fetch()) {
            $insertBooking = $pdo->prepare("INSERT INTO bookings (property_id, tenant_id, start_date, end_date, total_amount, status) 
                                          VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), ?, 'pending')");
            $insertBooking->execute([$propertyId, $_SESSION['user_id'], $property['price']]);
            $bookingId = $pdo->lastInsertId();
            
            // Send message to owner
            $insertMessage = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, booking_id, message) 
                                           VALUES (?, ?, ?, ?)");
            $insertMessage->execute([$_SESSION['user_id'], $property['owner_id'], $bookingId, $message]);
            
            $_SESSION['success'] = "Inquiry sent successfully! The property owner will contact you soon.";
            header("Location: view-property.php?id=$propertyId");
            exit();
        } else {
            $_SESSION['error'] = "You already have a pending inquiry for this property.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $property['title']; ?> - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="property-details">
        <div class="container">
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
            
            <div class="property-gallery">
                <?php if (!empty($images)): ?>
                    <img src="../assets/images/properties/<?php echo $images[0]; ?>" alt="<?php echo $property['title']; ?>" 
                         class="main-image" onerror="this.src='../assets/images/default-property.jpg'">
                    
                    <?php if (count($images) > 1): ?>
                        <div class="thumbnail-grid">
                            <?php foreach ($images as $image): ?>
                                <img src="../assets/images/properties/<?php echo $image; ?>" alt="Thumbnail" 
                                     class="thumbnail" onerror="this.src='../assets/images/default-property.jpg'">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <img src="../assets/images/default-property.jpg" alt="<?php echo $property['title']; ?>" class="main-image">
                <?php endif; ?>
            </div>
            
            <div class="property-info">
                <div class="property-header">
                    <h1><?php echo $property['title']; ?></h1>
                    <div class="property-price">KES <?php echo number_format($property['price']); ?>/month</div>
                </div>
                
                <p class="property-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?php echo $property['location']; ?>, <?php echo $property['county']; ?>
                </p>
                
                <div class="property-meta">
                    <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bedrooms</span>
                    <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bathrooms</span>
                    <span><i class="fas fa-ruler-combined"></i> <?php echo $property['size'] ?: 'N/A'; ?></span>
                    <span><i class="fas fa-home"></i> <?php echo ucfirst($property['property_type']); ?></span>
                </div>
                
                <div class="property-description">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                </div>
                
                <?php if (!empty($amenities)): ?>
                    <div class="property-amenities">
                        <h3>Amenities</h3>
                        <div class="amenities-list">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="amenity-item">
                                    <i class="fas fa-check"></i>
                                    <span><?php echo ucfirst($amenity); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($securityFeatures)): ?>
                    <div class="property-security">
                        <h3>Security Features</h3>
                        <div class="security-list">
                            <?php foreach ($securityFeatures as $feature): ?>
                                <div class="security-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <span><?php echo ucfirst($feature); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($universities)): ?>
                    <div class="property-universities">
                        <h3>Nearby Universities</h3>
                        <div class="universities-list">
                            <?php foreach ($universities as $university): ?>
                                <div class="university-item">
                                    <h4><?php echo $university['name']; ?></h4>
                                    <p>Distance: <?php echo $university['distance']; ?> km</p>
                                    <?php if (!empty($university['transport_options'])): ?>
                                        <p>Transport: <?php echo $university['transport_options']; ?></p>
                                    <?php endif; ?>
                                    <?php if ($university['student_discount'] > 0): ?>
                                        <p class="discount">Student Discount: <?php echo $university['student_discount']; ?>%</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="property-owner">
                    <h3>Property Owner</h3>
                    <div class="owner-info">
                        <div class="owner-details">
                            <h4><?php echo $property['first_name'] . ' ' . $property['last_name']; ?></h4>
                            <p><i class="fas fa-phone"></i> <?php echo $property['owner_phone']; ?></p>
                            <p><i class="fas fa-envelope"></i> <?php echo $property['owner_email']; ?></p>
                        </div>
                        
                        <?php if (checkLoggedIn() && $_SESSION['user_type'] == 'tenant'): ?>
                            <div class="owner-actions">
                                <button class="btn-primary" id="inquiry-btn">Send Inquiry</button>
                                <a href="book-property.php?id=<?php echo $propertyId; ?>" class="btn-secondary">Book Now</a>
                            </div>
                        <?php elseif (!checkLoggedIn()): ?>
                            <div class="owner-actions">
                                <p><a href="login.php">Login</a> to contact the owner or book this property</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Inquiry Modal -->
            <div class="modal" id="inquiry-modal">
                <div class="modal-content">
                    <span class="modal-close">&times;</span>
                    <h2>Send Inquiry to Owner</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label" for="message">Your Message*</label>
                            <textarea class="form-input" id="message" name="message" rows="4" required 
                                      placeholder="Tell the owner about your interest in this property..."></textarea>
                        </div>
                        <button type="submit" name="send_inquiry" class="btn-primary btn-block">Send Inquiry</button>
                    </form>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="reviews-container">
                <h2>Reviews (<?php echo $totalReviews; ?>)</h2>
                
                <?php if ($totalReviews > 0): ?>
                    <div class="average-rating">
                        <div class="rating-score"><?php echo $averageRating; ?></div>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= round($averageRating) ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p>Based on <?php echo $totalReviews; ?> reviews</p>
                    </div>
                    
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="review-author">
                                        <h4><?php echo $review['first_name'] . ' ' . $review['last_name']; ?></h4>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this property!</p>
                <?php endif; ?>
                
                <?php if (checkLoggedIn() && $_SESSION['user_type'] == 'tenant' && empty($userReview)): ?>
                    <div class="add-review">
                        <h3>Add Your Review</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Rating*</label>
                                <div class="rating-input">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="comment">Review*</label>
                                <textarea class="form-input" id="comment" name="comment" rows="4" required 
                                          placeholder="Share your experience with this property..."></textarea>
                            </div>
                            
                            <button type="submit" name="submit_review" class="btn-primary">Submit Review</button>
                        </form>
                    </div>
                <?php elseif (checkLoggedIn() && !empty($userReview)): ?>
                    <div class="user-review">
                        <h3>Your Review</h3>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $userReview['rating'] ? 'active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="review-date"><?php echo date('M d, Y', strtotime($userReview['created_at'])); ?></span>
                            </div>
                            <p class="review-comment"><?php echo nl2br(htmlspecialchars($userReview['comment'])); ?></p>
                        </div>
                    </div>
                <?php elseif (!checkLoggedIn()): ?>
                    <p><a href="login.php">Login</a> to leave a review for this property</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Image gallery functionality
        const mainImage = document.querySelector('.main-image');
        const thumbnails = document.querySelectorAll('.thumbnail');
        
        if (thumbnails.length > 0) {
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', () => {
                    mainImage.src = thumbnail.src;
                });
            });
        }
        
        // Modal functionality
        const inquiryModal = document.getElementById('inquiry-modal');
        const inquiryBtn = document.getElementById('inquiry-btn');
        const closeModal = document.querySelector('.modal-close');
        
        if (inquiryBtn) {
            inquiryBtn.addEventListener('click', () => {
                inquiryModal.style.display = 'block';
            });
        }
        
        if (closeModal) {
            closeModal.addEventListener('click', () => {
                inquiryModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target === inquiryModal) {
                inquiryModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>