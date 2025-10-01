<?php
include 'includes/config.php';
include 'includes/auth.php'; // Add this line

// Get featured properties
$featuredProperties = $pdo->query("
    SELECT p.*, u.first_name, u.last_name 
    FROM properties p 
    JOIN users u ON p.owner_id = u.user_id 
    WHERE p.status = 'available' 
    ORDER BY p.created_at DESC 
    LIMIT 6
")->fetchAll();

// Get universities - with Taita Taveta University first
$universities = $pdo->query("SELECT * FROM universities ORDER BY 
    CASE WHEN name LIKE '%Taita Taveta%' THEN 0 ELSE 1 END, 
    name")->fetchAll();

// Get statistics
$totalProperties = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'available'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved'")->fetchColumn();

// Add Taita Taveta University if it doesn't exist
$checkTaita = $pdo->query("SELECT COUNT(*) FROM universities WHERE name LIKE '%Taita Taveta%'")->fetchColumn();
if ($checkTaita == 0) {
    $pdo->exec("INSERT INTO universities (name, location, county, description) VALUES 
        ('Taita Taveta University', 'Voi', 'Taita-Taveta', 'Public university located in Voi offering various academic programs.'),
        ('Technical University of Mombasa', 'Mombasa', 'Mombasa', 'Public university located in Mombasa offering various technical courses.'),
        ('Pwani University', 'Kilifi', 'Kilifi', 'Public university in Kilifi offering diverse academic programs.'),
        ('Kenya Coast National Polytechnic', 'Mombasa', 'Mombasa', 'National polytechnic offering technical education in the coastal region.'),
        ('Mombasa Technical Training Institute', 'Mombasa', 'Mombasa', 'Technical institute providing vocational training in Mombasa.'),
        ('Coastal Institute of Technology', 'Mombasa', 'Mombasa', 'Private institution offering technology-focused education.')");
    
    // Refresh universities list
    $universities = $pdo->query("SELECT * FROM universities ORDER BY 
        CASE WHEN name LIKE '%Taita Taveta%' THEN 0 ELSE 1 END, 
        name")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        /* Add this CSS to your style.css or in the index.php file */
.universities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.university-card {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    transition: var(--transition);
    height: 100%; /* Ensure consistent height */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.university-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.university-logo {
    margin-bottom: 1rem;
    height: 100px; /* Fixed height for logos */
    display: flex;
    align-items: center;
    justify-content: center;
}

.university-logo img {
    max-width: 100%;
    max-height: 80px;
    object-fit: contain;
}

.university-logo i {
    font-size: 3rem;
    color: var(--primary-color);
}
    </style>
    
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Find Your Perfect Student Home</h1>
                <p class="hero-subtitle">Discover affordable, secure, and convenient housing near coastal universities</p>
                
                <div class="search-form">
                    <form action="properties.php" method="GET">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="location">Location</label>
                                <input type="text" class="form-input" id="location" name="location" placeholder="Enter location...">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="property_type">Property Type</label>
                                <select class="form-select" id="property_type" name="property_type">
                                    <option value="">Any Type</option>
                                    <option value="apartment">Apartment</option>
                                    <option value="house">House</option>
                                    <option value="bedsitter">Bedsitter</option>
                                    <option value="studio">Studio</option>
                                    <option value="hostel">Hostel</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="min_price">Min Price (KES)</label>
                                <input type="number" class="form-input" id="min_price" name="min_price" placeholder="Min price" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="max_price">Max Price (KES)</label>
                                <input type="number" class="form-input" id="max_price" name="max_price" placeholder="Max price" min="0">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary btn-block">
                            <i class="fas fa-search"></i> Search Properties
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <section class="features">
        <div class="container">
            <div class="section-title">
                <h2>Why Choose Coastal Student Housing?</h2>
                <p>We make finding your perfect student accommodation simple and stress-free</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Verified Properties</h3>
                    <p>All properties are thoroughly vetted for safety, quality, and authenticity</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Student-Focused</h3>
                    <p>Properties located near universities with student-friendly amenities</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Affordable Options</h3>
                    <p>Find housing that fits your budget with various price ranges</p>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Get help whenever you need it with our round-the-clock support</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="properties">
        <div class="container">
            <div class="section-title">
                <h2>Featured Properties</h2>
                <p>Discover our most popular student accommodations</p>
            </div>
            
            <div class="properties-grid">
                <?php if (!empty($featuredProperties)): ?>
                    <?php foreach ($featuredProperties as $property): 
                        $images = !empty($property['images']) ? json_decode($property['images'], true) : [];
                        $firstImage = !empty($images) ? 'assets/images/properties/' . $images[0] : 'assets/images/default-property.jpg';
                    ?>
                        <div class="property-card">
                            <img src="<?php echo $firstImage; ?>" alt="<?php echo $property['title']; ?>" 
                                 class="property-image" onerror="this.src='assets/images/default-property.jpg'">
                            
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
                                
                                <div class="property-actions">
                                    <a href="pages/view-property.php?id=<?php echo $property['property_id']; ?>" class="btn-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-properties">
                        <i class="fas fa-home fa-3x"></i>
                        <h3>No Properties Available</h3>
                        <p>Check back later for new listings</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-3">
                <a href="pages/properties.php" class="btn-secondary">View All Properties</a>
            </div>
        </div>
    </section>
    
    <section class="universities">
        <div class="container">
            <div class="section-title">
                <h2>Featured Universities</h2>
                <p>Find housing near these coastal universities</p>
            </div>
            
            <div class="universities-grid">
                <?php foreach ($universities as $university): ?>
                    <div class="university-card">
                        <div class="university-logo">
                            <?php if (!empty($university['logo'])): ?>
                                <img src="assets/images/universities/<?php echo $university['logo']; ?>" 
                                     alt="<?php echo $university['name']; ?>"
                                     onerror="this.src='assets/images/default-university.png'">
                            <?php else: ?>
                                <i class="fas fa-university fa-3x"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h3><?php echo $university['name']; ?></h3>
                        <p><?php echo $university['location']; ?>, <?php echo $university['county']; ?></p>
                        
                        <a href="pages/properties.php?university=<?php echo $university['university_id']; ?>" class="btn-outline">
                            Find Housing
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-home fa-3x"></i>
                    <h3><?php echo number_format($totalProperties); ?></h3>
                    <p>Properties Listed</p>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-users fa-3x"></i>
                    <h3><?php echo number_format($totalUsers); ?></h3>
                    <p>Happy Students</p>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-calendar-check fa-3x"></i>
                    <h3><?php echo number_format($totalBookings); ?></h3>
                    <p>Successful Bookings</p>
                </div>
                
                <div class="stat-item">
                    <i class="fas fa-star fa-3x"></i>
                    <h3>4.8/5</h3>
                    <p>Average Rating</p>
                </div>
            </div>
        </div>
    </section>
    
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Find Your New Home?</h2>
                <p>Join thousands of students who found their perfect accommodation through us</p>
                
                <div class="cta-buttons">
                    <?php if (isLoggedIn()): ?>
                        <a href="pages/properties.php" class="btn-primary">Browse Properties</a>
                        <?php if (isOwner()): ?>
                            <a href="pages/add-property.php" class="btn-secondary">List Your Property</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="pages/register.php" class="btn-primary">Get Started</a>
                        <a href="pages/properties.php" class="btn-outline">Browse Properties</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
</body>
</html>