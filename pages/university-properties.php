<?php
include '../includes/config.php';

// Check if university ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$university_id = (int)$_GET['id'];
$university = getUniversity($university_id);

if (!$university) {
    redirect('index.php');
}

// Get properties for this university
$properties = getUniversityProperties($university_id);

// Get academic calendar
$calendar = getUniversityCalendar($university_id);

$page_title = "Properties near " . $university['name'];
?>
<?php include '../includes/header.php'; ?>

<main>
    <section class="hero" style="background: linear-gradient(rgba(44, 62, 80, 0.8), rgba(44, 62, 80, 0.8)), url('../assets/images/university-bg.jpg') no-repeat center center/cover;">
        <div class="container">
            <div class="hero-content">
                <h1>Student Housing near <?php echo $university['name']; ?></h1>
                <p>Find the perfect accommodation close to campus with student-friendly rates</p>
            </div>
        </div>
    </section>

    <section class="property-detail">
        <div class="container">
            <div class="property-info">
                <h2>About <?php echo $university['name']; ?></h2>
                <p><?php echo $university['description']; ?></p>
                <p><strong>Location:</strong> <?php echo $university['location']; ?></p>
                
                <?php if (!empty($calendar)): ?>
                <div class="mt-3">
                    <h3>Academic Calendar</h3>
                    <div class="calendar-grid">
                        <?php foreach ($calendar as $term): ?>
                        <div class="calendar-term">
                            <h4><?php echo $term['semester_name']; ?> (<?php echo $term['academic_year']; ?>)</h4>
                            <p>Classes: <?php echo date('M j, Y', strtotime($term['start_date'])); ?> - <?php echo date('M j, Y', strtotime($term['end_date'])); ?></p>
                            <?php if ($term['holiday_start'] && $term['holiday_end']): ?>
                            <p>Holiday: <?php echo date('M j, Y', strtotime($term['holiday_start'])); ?> - <?php echo date('M j, Y', strtotime($term['holiday_end'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="featured-properties">
        <div class="container">
            <h2>Available Properties</h2>
            
            <?php if (!empty($properties)): ?>
            <div class="properties-grid">
                <?php foreach ($properties as $property): ?>
                <div class="property-card">
                    <img src="<?php echo !empty($property['images']) ? json_decode($property['images'])[0] : '../assets/images/default-property.jpg'; ?>" alt="<?php echo $property['title']; ?>">
                    <div class="property-details">
                        <h3><?php echo $property['title']; ?></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo $property['location']; ?>, <?php echo $property['county']; ?></p>
                        
                        <?php if ($property['distance']): ?>
                        <span class="university-badge"><i class="fas fa-walking"></i> <?php echo $property['distance']; ?> from campus</span>
                        <?php endif; ?>
                        
                        <p class="price">KES <?php echo number_format($property['price']); ?> / month</p>
                        
                        <?php if ($property['student_discount'] > 0): ?>
                        <p class="text-accent"><i class="fas fa-tag"></i> <?php echo $property['student_discount']; ?>% student discount</p>
                        <?php endif; ?>
                        
                        <div class="property-features">
                            <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Bedrooms</span>
                            <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Bathrooms</span>
                        </div>
                        
                        <a href="view-property.php?id=<?php echo $property['property_id']; ?>" class="btn-secondary">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center mt-3">
                <p>No properties available near this university at the moment.</p>
                <a href="../index.php" class="btn-primary">Browse All Properties</a>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>