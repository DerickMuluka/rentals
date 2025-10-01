<?php
include '../includes/config.php';
include '../includes/auth.php';

// Check if user is logged in
checkLoggedIn();

// Get search filters
$filters = [];
$params = [];

// Location filter
if (!empty($_GET['location'])) {
    $filters[] = "p.location LIKE ?";
    $params[] = '%' . $_GET['location'] . '%';
}

// Property type filter
if (!empty($_GET['property_type'])) {
    $filters[] = "p.property_type = ?";
    $params[] = $_GET['property_type'];
}

// Price range filters
if (!empty($_GET['min_price'])) {
    $filters[] = "p.price >= ?";
    $params[] = $_GET['min_price'];
}

if (!empty($_GET['max_price'])) {
    $filters[] = "p.price <= ?";
    $params[] = $_GET['max_price'];
}

// Bedrooms filter
if (!empty($_GET['bedrooms'])) {
    $filters[] = "p.bedrooms = ?";
    $params[] = $_GET['bedrooms'];
}

// University filter
if (!empty($_GET['university'])) {
    $filters[] = "up.university_id = ?";
    $params[] = $_GET['university'];
}

// Build WHERE clause
$whereClause = !empty($filters) ? "WHERE p.status = 'available' AND " . implode(" AND ", $filters) : "WHERE p.status = 'available'";

// Get properties with filters
$sql = "SELECT DISTINCT p.*, u.first_name, u.last_name 
        FROM properties p 
        JOIN users u ON p.owner_id = u.user_id 
        LEFT JOIN university_properties up ON p.property_id = up.property_id 
        $whereClause 
        ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Get universities for filter
$universities = $pdo->query("SELECT * FROM universities ORDER BY name")->fetchAll();

// Get unique locations for filter
$locations = $pdo->query("SELECT DISTINCT location FROM properties WHERE status = 'available' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        .search-bar-container {
            background: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-bar-form {
            display: flex;
            justify-content: center;
        }
        
        .search-input-group {
            display: flex;
            max-width: 800px;
            width: 100%;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--gray-color);
            border-right: none;
            border-radius: 4px 0 0 4px;
            font-size: 1rem;
        }
        
        .search-select {
            padding: 0.75rem;
            border: 2px solid var(--gray-color);
            border-right: none;
            background: white;
            width: 150px;
        }
        
        .search-btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .search-btn:hover {
            background: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .search-input-group {
                flex-direction: column;
            }
            
            .search-input, .search-select, .search-btn {
                width: 100%;
                border-radius: 4px;
                border: 2px solid var(--gray-color);
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Find Your Perfect Student Home</h1>
                <p class="hero-subtitle">Browse through our curated selection of student accommodations</p>
            </div>
        </div>
    </section>
    
    <!-- Search Bar -->
    <div class="search-bar-container">
        <div class="container">
            <form action="properties.php" method="GET" class="search-bar-form">
                <div class="search-input-group">
                    <input type="text" name="q" placeholder="Search properties, locations, universities..."
                    value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>" class="search-input">
                    <select name="search_type" class="search-select">
                        <option value="all" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'all') ? 'selected' : ''; ?>>All</option>
                        <option value="properties" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'properties') ? 'selected' : ''; ?>>Properties</option>
                        <option value="universities" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'universities') ? 'selected' : ''; ?>>Universities</option>
                        <option value="locations" <?php echo (isset($_GET['search_type']) && $_GET['search_type'] == 'locations') ? 'selected' : ''; ?>>Locations</option>
                    </select>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <section class="properties">
        <div class="container">
            <div class="properties-header">
                <h2>Available Properties</h2>
                <button class="btn-secondary" id="filter-toggle">
                    <i class="fas fa-filter"></i> Show Filters
                </button>
            </div>
            
            <div class="search-filters" id="search-filters">
                <form method="GET" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="location">Location</label>
                            <input type="text" class="form-input" id="location" name="location" 
                                   value="<?php echo isset($_GET['location']) ? htmlspecialchars($_GET['location']) : ''; ?>" 
                                   placeholder="Enter location...">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="property_type">Property Type</label>
                            <select class="form-select" id="property_type" name="property_type">
                                <option value="">Any Type</option>
                                <option value="apartment" <?php echo (isset($_GET['property_type']) && $_GET['property_type'] == 'apartment') ? 'selected' : ''; ?>>Apartment</option>
                                <option value="house" <?php echo (isset($_GET['property_type']) && $_GET['property_type'] == 'house') ? 'selected' : ''; ?>>House</option>
                                <option value="bedsitter" <?php echo (isset($_GET['property_type']) && $_GET['property_type'] == 'bedsitter') ? 'selected' : ''; ?>>Bedsitter</option>
                                <option value="studio" <?php echo (isset($_GET['property_type']) && $_GET['property_type'] == 'studio') ? 'selected' : ''; ?>>Studio</option>
                                <option value="hostel" <?php echo (isset($_GET['property_type']) && $_GET['property_type'] == 'hostel') ? 'selected' : ''; ?>>Hostel</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="university">Near University</label>
                            <select class="form-select" id="university" name="university">
                                <option value="">Any University</option>
                                <?php foreach ($universities as $university): ?>
                                    <option value="<?php echo $university['university_id']; ?>" 
                                        <?php echo (isset($_GET['university']) && $_GET['university'] == $university['university_id']) ? 'selected' : ''; ?>>
                                        <?php echo $university['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="min_price">Min Price (KES)</label>
                            <input type="number" class="form-input" id="min_price" name="min_price" 
                                   value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>" 
                                   placeholder="Min price" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="max_price">Max Price (KES)</label>
                            <input type="number" class="form-input" id="max_price" name="max_price" 
                                   value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>" 
                                   placeholder="Max price" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="bedrooms">Bedrooms</label>
                            <select class="form-select" id="bedrooms" name="bedrooms">
                                <option value="">Any</option>
                                <option value="1" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '1') ? 'selected' : ''; ?>>1</option>
                                <option value="2" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '2') ? 'selected' : ''; ?>>2</option>
                                <option value="3" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '3') ? 'selected' : ''; ?>>3</option>
                                <option value="4" <?php echo (isset($_GET['bedrooms']) && $_GET['bedrooms'] == '4') ? 'selected' : ''; ?>>4+</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="properties.php" class="btn-outline">Clear Filters</a>
                    </div>
                </form>
            </div>
            
            <div class="properties-grid">
                <?php if (!empty($properties)): ?>
                    <?php foreach ($properties as $property): 
                        $images = !empty($property['images']) ? json_decode($property['images'], true) : [];
                        $firstImage = !empty($images) ? '../assets/images/properties/' . $images[0] : '../assets/images/default-property.jpg';
                    ?>
                        <div class="property-card">
                            <img src="<?php echo $firstImage; ?>" alt="<?php echo $property['title']; ?>" 
                                 class="property-image" onerror="this.src='../assets/images/default-property.jpg'">
                            
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
                                    <span><i class="fas fa-ruler-combined"></i> <?php echo $property['size'] ?: 'N/A'; ?></span>
                                </div>
                                
                                <div class="property-description">
                                    <?php echo substr($property['description'], 0, 100); ?>...
                                </div>
                                
                                <div class="property-actions">
                                    <a href="view-property.php?id=<?php echo $property['property_id']; ?>" class="btn-primary">
                                        View Details
                                    </a>
                                    <?php if (checkLoggedIn() && $_SESSION['user_type'] == 'tenant'): ?>
                                        <a href="book-property.php?id=<?php echo $property['property_id']; ?>" class="btn-secondary">
                                            Book Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-properties">
                        <i class="fas fa-search fa-3x"></i>
                        <h3>No Properties Found</h3>
                        <p>Try adjusting your search filters or <a href="properties.php">browse all properties</a></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($properties)): ?>
                <div class="pagination">
                    <a href="#" class="page-link disabled"><i class="fas fa-chevron-left"></i> Previous</a>
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#" class="page-link">Next <i class="fas fa-chevron-right"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
    <script>
        // Toggle filters visibility
        document.getElementById('filter-toggle').addEventListener('click', function() {
            const filters = document.getElementById('search-filters');
            filters.classList.toggle('active');
            this.innerHTML = filters.classList.contains('active') 
                ? '<i class="fas fa-filter"></i> Hide Filters' 
                : '<i class="fas fa-filter"></i> Show Filters';
        });
    </script>
</body>
</html>