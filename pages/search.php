<?php
include '../includes/config.php';

// Get search parameters
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$property_type = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
$university = isset($_GET['university']) ? (int)$_GET['university'] : 0;

// Build SQL query
$sql = "SELECT p.*, u.first_name, u.last_name 
        FROM properties p 
        JOIN users u ON p.owner_id = u.user_id 
        WHERE p.status = 'available'";
$params = [];

// Add search conditions
// Updated search query handling
if (!empty($search_query)) {
    $search_type = $_GET['search_type'] ?? 'all';
    
    switch ($search_type) {
        case 'properties':
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            break;
        case 'universities':
            $sql .= " AND (u.name LIKE ? OR u.location LIKE ?)";
            break;
        case 'locations':
            $sql .= " AND p.location LIKE ?";
            break;
        default:
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ? OR u.name LIKE ?)";
            break;
    }
    
    $search_param = "%$search_query%";
    // Add parameters based on search type
}

if (!empty($location)) {
    $sql .= " AND p.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($property_type)) {
    $sql .= " AND p.property_type = ?";
    $params[] = $property_type;
}

if ($min_price > 0) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}

if ($bedrooms > 0) {
    $sql .= " AND p.bedrooms = ?";
    $params[] = $bedrooms;
}

if ($university > 0) {
    $sql .= " AND p.property_id IN (SELECT property_id FROM university_properties WHERE university_id = ?)";
    $params[] = $university;
}

$sql .= " ORDER BY p.created_at DESC";

// Execute query
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
    <title>Search Properties - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <!-- Add this CSS -->
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
                <h1 class="hero-title">Search Properties</h1>
                <p class="hero-subtitle">Find your perfect student accommodation</p>
            </div>
        </div>
    </section>
    
    <!-- Add this near the top of the page, after the hero section -->
    <div class="search-bar-container">
        <div class="container">
            <form action="search.php" method="GET" class="search-bar-form">
                <div class="search-input-group">
                    <input type="text" name="q" placeholder="Search properties, locations, universities..."
                    value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                    <select name="search_type" class="search-select">
                        <option value="all" <?php echo ($_GET['search_type'] ?? 'all') == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="properties" <?php echo ($_GET['search_type'] ?? '') == 'properties' ? 'selected' : ''; ?>>Properties</option>
                        <option value="universities" <?php echo ($_GET['search_type'] ?? '') == 'universities' ? 'selected' : ''; ?>>Universities</option>
                        <option value="locations" <?php echo ($_GET['search_type'] ?? '') == 'locations' ? 'selected' : ''; ?>>Locations</option>
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
                <h2>Search Results</h2>
                <button class="btn-secondary" id="filter-toggle">
                    <i class="fas fa-filter"></i> Show Filters
                </button>
            </div>
            
            <div class="search-filters" id="search-filters">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label class="form-label" for="q">Search Keywords</label>
                        <input type="text" class="form-input" id="q" name="q" 
                               value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Enter keywords...">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="location">Location</label>
                            <input type="text" class="form-input" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($location); ?>" 
                                   placeholder="Enter location...">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="property_type">Property Type</label>
                            <select class="form-select" id="property_type" name="property_type">
                                <option value="">Any Type</option>
                                <option value="apartment" <?php echo $property_type == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                <option value="house" <?php echo $property_type == 'house' ? 'selected' : ''; ?>>House</option>
                                <option value="bedsitter" <?php echo $property_type == 'bedsitter' ? 'selected' : ''; ?>>Bedsitter</option>
                                <option value="studio" <?php echo $property_type == 'studio' ? 'selected' : ''; ?>>Studio</option>
                                <option value="hostel" <?php echo $property_type == 'hostel' ? 'selected' : ''; ?>>Hostel</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="min_price">Min Price (KES)</label>
                            <input type="number" class="form-input" id="min_price" name="min_price" 
                                   value="<?php echo $min_price > 0 ? $min_price : ''; ?>" 
                                   placeholder="Min price" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="max_price">Max Price (KES)</label>
                            <input type="number" class="form-input" id="max_price" name="max_price" 
                                   value="<?php echo $max_price > 0 ? $max_price : ''; ?>" 
                                   placeholder="Max price" min="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="bedrooms">Bedrooms</label>
                            <select class="form-select" id="bedrooms" name="bedrooms">
                                <option value="0">Any</option>
                                <option value="1" <?php echo $bedrooms == 1 ? 'selected' : ''; ?>>1</option>
                                <option value="2" <?php echo $bedrooms == 2 ? 'selected' : ''; ?>>2</option>
                                <option value="3" <?php echo $bedrooms == 3 ? 'selected' : ''; ?>>3</option>
                                <option value="4" <?php echo $bedrooms == 4 ? 'selected' : ''; ?>>4+</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="university">Near University</label>
                            <select class="form-select" id="university" name="university">
                                <option value="0">Any University</option>
                                <?php foreach ($universities as $univ): ?>
                                    <option value="<?php echo $univ['university_id']; ?>" 
                                        <?php echo $university == $univ['university_id'] ? 'selected' : ''; ?>>
                                        <?php echo $univ['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="search.php" class="btn-outline">Clear Filters</a>
                    </div>
                </form>
            </div>
            
            <div class="search-results-info">
                <p>Found <?php echo count($properties); ?> properties matching your criteria</p>
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
                        <p>Try adjusting your search criteria or <a href="properties.php">browse all properties</a></p>
                    </div>
                <?php endif; ?>
            </div>
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
        
        // Auto-submit form when filter values change
        const filterForm = document.querySelector('.filter-form');
        const filterInputs = filterForm.querySelectorAll('select, input[type="number"]');
        
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                filterForm.submit();
            });
        });
    </script>
</body>
</html>