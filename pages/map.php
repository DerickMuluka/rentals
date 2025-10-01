<?php
include '../includes/config.php';
include '../includes/auth.php';

// Get all properties with coordinates
$properties = $pdo->query("SELECT p.*, u.first_name, u.last_name 
                          FROM properties p 
                          JOIN users u ON p.owner_id = u.user_id 
                          WHERE p.status = 'available' 
                          AND p.latitude IS NOT NULL 
                          AND p.longitude IS NOT NULL")->fetchAll();

// Get all universities with coordinates
$universities = $pdo->query("SELECT * FROM universities 
                            WHERE latitude IS NOT NULL 
                            AND longitude IS NOT NULL")->fetchAll();

// Get distinct locations for filter
$locations = $pdo->query("SELECT DISTINCT location FROM properties WHERE status = 'available' ORDER BY location")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Map - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        
        .map-container {
            position: relative;
        }
        
        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .map-filters {
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .map-legend {
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .property-color {
            background: #e74c3c;
        }
        
        .university-color {
            background: #3498db;
        }
        
        .cluster-color {
            background: #f39c12;
        }
        
        .property-marker {
            background: #e74c3c;
            border: 2px solid white;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .university-marker {
            background: #3498db;
            border: 2px solid white;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .map-popup {
            min-width: 200px;
        }
        
        .map-popup img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .map-popup h3 {
            margin: 0 0 0.5rem 0;
            color: var(--dark-color);
        }
        
        .map-popup p {
            margin: 0 0 0.5rem 0;
            color: #666;
        }
        
        .map-popup .price {
            font-weight: bold;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .map-controls {
                position: relative;
                top: auto;
                right: auto;
                margin-bottom: 1rem;
            }
            
            .filter-row {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Find Properties on Map</h1>
                <p class="hero-subtitle">Explore available properties and universities on our interactive map</p>
            </div>
        </div>
    </section>
    
    <section class="map-section">
        <div class="container">
            <div class="map-legend">
                <h3>Map Legend</h3>
                <div class="legend-item">
                    <div class="legend-color property-color"></div>
                    <span>Properties</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color university-color"></div>
                    <span>Universities</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color cluster-color"></div>
                    <span>Multiple Properties</span>
                </div>
            </div>
            
            <div class="map-filters">
                <h3>Filter Properties</h3>
                <form id="map-filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="form-label" for="location-filter">Location</label>
                            <select class="form-select" id="location-filter">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo htmlspecialchars($location); ?>"><?php echo htmlspecialchars($location); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="form-label" for="type-filter">Property Type</label>
                            <select class="form-select" id="type-filter">
                                <option value="">All Types</option>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="bedsitter">Bedsitter</option>
                                <option value="studio">Studio</option>
                                <option value="hostel">Hostel</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="form-label" for="price-range">Price Range (KES)</label>
                            <select class="form-select" id="price-range">
                                <option value="">Any Price</option>
                                <option value="0-5000">0 - 5,000</option>
                                <option value="5000-10000">5,000 - 10,000</option>
                                <option value="10000-20000">10,000 - 20,000</option>
                                <option value="20000-50000">20,000 - 50,000</option>
                                <option value="50000-100000">50,000+</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="form-label" for="bedrooms">Bedrooms</label>
                            <select class="form-select" id="bedrooms">
                                <option value="">Any</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4+</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="form-label" for="show-universities">
                                <input type="checkbox" id="show-universities" checked> Show Universities
                            </label>
                        </div>
                        
                        <div class="filter-group">
                            <button type="button" id="reset-filters" class="btn-outline">Reset Filters</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="map-container">
                <div id="map"></div>
                <div class="map-controls">
                    <button id="locate-me" class="btn-secondary">
                        <i class="fas fa-location-arrow"></i> Locate Me
                    </button>
                    <button id="reset-view" class="btn-outline">
                        <i class="fas fa-globe-africa"></i> Reset View
                    </button>
                </div>
            </div>
            
            <div class="properties-list">
                <h2>Properties on Map</h2>
                <div class="properties-grid">
                    <?php foreach ($properties as $property): 
                        $images = !empty($property['images']) ? json_decode($property['images'], true) : [];
                        $firstImage = !empty($images) ? '../assets/images/properties/' . $images[0] : '../assets/images/default-property.jpg';
                    ?>
                        <div class="property-card" data-lat="<?php echo $property['latitude']; ?>" data-lng="<?php echo $property['longitude']; ?>" 
                             data-type="<?php echo $property['property_type']; ?>" data-price="<?php echo $property['price']; ?>" 
                             data-bedrooms="<?php echo $property['bedrooms']; ?>" data-location="<?php echo htmlspecialchars($property['location']); ?>">
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
                                    <span><i class="fas fa-home"></i> <?php echo ucfirst($property['property_type']); ?></span>
                                </div>
                                
                                <div class="property-actions">
                                    <a href="view-property.php?id=<?php echo $property['property_id']; ?>" class="btn-primary">
                                        View Details
                                    </a>
                                    <button class="btn-secondary locate-property" 
                                            data-lat="<?php echo $property['latitude']; ?>" 
                                            data-lng="<?php echo $property['longitude']; ?>">
                                        <i class="fas fa-map-marker-alt"></i> Show on Map
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([-4.0435, 39.6682], 12); // Default to Mombasa
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Create marker clusters
        const propertyMarkers = L.markerClusterGroup({
            chunkedLoading: true,
            maxClusterRadius: 40,
            iconCreateFunction: function(cluster) {
                const count = cluster.getChildCount();
                return L.divIcon({
                    html: '<div style="background-color: #f39c12; color: white; border: 2px solid white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold;">' + count + '</div>',
                    className: 'marker-cluster',
                    iconSize: L.point(40, 40)
                });
            }
        });
        
        const universityMarkers = L.layerGroup();
        
        // Add properties to map
        const properties = <?php echo json_encode($properties); ?>;
        const propertyLayer = {};
        
        properties.forEach(property => {
            if (property.latitude && property.longitude) {
                const marker = L.marker([parseFloat(property.latitude), parseFloat(property.longitude)], {
                    icon: L.divIcon({
                        html: '<div class="property-marker" style="width: 30px; height: 30px;">' + 
                              '<i class="fas fa-home"></i></div>',
                        className: 'property-icon',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    })
                });
                
                // Store property data in marker
                marker.propertyData = property;
                
                                // Create popup content
                const images = property.images ? JSON.parse(property.images) : [];
                const firstImage = images.length > 0 ? '../assets/images/properties/' + images[0] : '../assets/images/default-property.jpg';
                
                const popupContent = `
                    <div class="map-popup">
                        <img src="${firstImage}" alt="${property.title}" 
                             onerror="this.src='../assets/images/default-property.jpg'">
                        <h3>${property.title}</h3>
                        <p><i class="fas fa-map-marker-alt"></i> ${property.location}, ${property.county}</p>
                        <p class="price">KES ${Number(property.price).toLocaleString()}/month</p>
                        <p>${property.bedrooms} Bedrooms • ${property.bathrooms} Bathrooms</p>
                        <a href="view-property.php?id=${property.property_id}" class="btn-primary" style="display: block; text-align: center; margin-top: 0.5rem;">
                            View Details
                        </a>
                    </div>
                `;
                
                marker.bindPopup(popupContent, { maxWidth: 300 });
                propertyMarkers.addLayer(marker);
                propertyLayer[property.property_id] = marker;
            }
        });
        
        // Add universities to map
        const universities = <?php echo json_encode($universities); ?>;
        
        universities.forEach(university => {
            if (university.latitude && university.longitude) {
                const marker = L.marker([parseFloat(university.latitude), parseFloat(university.longitude)], {
                    icon: L.divIcon({
                        html: '<div class="university-marker" style="width: 30px; height: 30px;">' + 
                              '<i class="fas fa-graduation-cap"></i></div>',
                        className: 'university-icon',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    })
                });
                
                const popupContent = `
                    <div class="map-popup">
                        <h3>${university.name}</h3>
                        <p><i class="fas fa-map-marker-alt"></i> ${university.location}</p>
                        <p>${university.type} University</p>
                        <p>${university.website ? `<a href="${university.website}" target="_blank">Visit Website</a>` : ''}</p>
                    </div>
                `;
                
                marker.bindPopup(popupContent, { maxWidth: 300 });
                universityMarkers.addLayer(marker);
            }
        });
        
        // Add layers to map
        map.addLayer(propertyMarkers);
        map.addLayer(universityMarkers);
        
        // Filter functionality
        const filters = {
            location: '',
            type: '',
            priceRange: '',
            bedrooms: '',
            showUniversities: true
        };
        
        function applyFilters() {
            // Filter property markers
            propertyMarkers.clearLayers();
            
            properties.forEach(property => {
                if (property.latitude && property.longitude) {
                    // Check if property matches filters
                    const matchesLocation = !filters.location || property.location === filters.location;
                    const matchesType = !filters.type || property.property_type === filters.type;
                    const matchesBedrooms = !filters.bedrooms || 
                                          (filters.bedrooms === '4' ? property.bedrooms >= 4 : property.bedrooms == filters.bedrooms);
                    
                    let matchesPrice = true;
                    if (filters.priceRange) {
                        const [min, max] = filters.priceRange.split('-').map(Number);
                        matchesPrice = property.price >= min && (!max || property.price <= max);
                    }
                    
                    if (matchesLocation && matchesType && matchesPrice && matchesBedrooms) {
                        const marker = L.marker([parseFloat(property.latitude), parseFloat(property.longitude)], {
                            icon: L.divIcon({
                                html: '<div class="property-marker" style="width: 30px; height: 30px;">' + 
                                      '<i class="fas fa-home"></i></div>',
                                className: 'property-icon',
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            })
                        });
                        
                        const images = property.images ? JSON.parse(property.images) : [];
                        const firstImage = images.length > 0 ? '../assets/images/properties/' + images[0] : '../assets/images/default-property.jpg';
                        
                        const popupContent = `
                            <div class="map-popup">
                                <img src="${firstImage}" alt="${property.title}" 
                                     onerror="this.src='../assets/images/default-property.jpg'">
                                <h3>${property.title}</h3>
                                <p><i class="fas fa-map-marker-alt"></i> ${property.location}, ${property.county}</p>
                                <p class="price">KES ${Number(property.price).toLocaleString()}/month</p>
                                <p>${property.bedrooms} Bedrooms • ${property.bathrooms} Bathrooms</p>
                                <a href="view-property.php?id=${property.property_id}" class="btn-primary" style="display: block; text-align: center; margin-top: 0.5rem;">
                                    View Details
                                </a>
                            </div>
                        `;
                        
                        marker.bindPopup(popupContent, { maxWidth: 300 });
                        propertyMarkers.addLayer(marker);
                    }
                }
            });
            
            // Toggle university visibility
            if (filters.showUniversities) {
                map.addLayer(universityMarkers);
            } else {
                map.removeLayer(universityMarkers);
            }
        }
        
        // Event listeners for filters
        document.getElementById('location-filter').addEventListener('change', function(e) {
            filters.location = e.target.value;
            applyFilters();
        });
        
        document.getElementById('type-filter').addEventListener('change', function(e) {
            filters.type = e.target.value;
            applyFilters();
        });
        
        document.getElementById('price-range').addEventListener('change', function(e) {
            filters.priceRange = e.target.value;
            applyFilters();
        });
        
        document.getElementById('bedrooms').addEventListener('change', function(e) {
            filters.bedrooms = e.target.value;
            applyFilters();
        });
        
        document.getElementById('show-universities').addEventListener('change', function(e) {
            filters.showUniversities = e.target.checked;
            applyFilters();
        });
        
        document.getElementById('reset-filters').addEventListener('click', function() {
            document.getElementById('location-filter').value = '';
            document.getElementById('type-filter').value = '';
            document.getElementById('price-range').value = '';
            document.getElementById('bedrooms').value = '';
            document.getElementById('show-universities').checked = true;
            
            filters.location = '';
            filters.type = '';
            filters.priceRange = '';
            filters.bedrooms = '';
            filters.showUniversities = true;
            
            applyFilters();
        });
        
        // Locate me functionality
        document.getElementById('locate-me').addEventListener('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const latlng = [position.coords.latitude, position.coords.longitude];
                        map.setView(latlng, 15);
                        
                        // Add user location marker
                        const userMarker = L.marker(latlng, {
                            icon: L.divIcon({
                                html: '<div style="background-color: #2ecc71; color: white; border: 2px solid white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold;"><i class="fas fa-user"></i></div>',
                                className: 'user-icon',
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            })
                        }).addTo(map);
                        
                        userMarker.bindPopup('Your current location').openPopup();
                        
                        // Remove user marker after 10 seconds
                        setTimeout(() => {
                            map.removeLayer(userMarker);
                        }, 10000);
                    },
                    function(error) {
                        alert('Unable to get your location: ' + error.message);
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser');
            }
        });
        
        // Reset view functionality
        document.getElementById('reset-view').addEventListener('click', function() {
            map.setView([-4.0435, 39.6682], 12);
        });
        
        // Property card click handlers
        document.querySelectorAll('.locate-property').forEach(button => {
            button.addEventListener('click', function() {
                const lat = parseFloat(this.dataset.lat);
                const lng = parseFloat(this.dataset.lng);
                map.setView([lat, lng], 15);
                
                // Find and open the marker popup
                Object.values(propertyLayer).forEach(marker => {
                    const markerLatLng = marker.getLatLng();
                    if (markerLatLng.lat === lat && markerLatLng.lng === lng) {
                        marker.openPopup();
                    }
                });
            });
        });
        
        // Property card filtering
        const propertyCards = document.querySelectorAll('.property-card');
        
        function filterPropertyCards() {
            propertyCards.forEach(card => {
                const location = card.dataset.location;
                const type = card.dataset.type;
                const price = parseFloat(card.dataset.price);
                const bedrooms = card.dataset.bedrooms;
                
                const matchesLocation = !filters.location || location === filters.location;
                const matchesType = !filters.type || type === filters.type;
                const matchesBedrooms = !filters.bedrooms || 
                                      (filters.bedrooms === '4' ? bedrooms >= 4 : bedrooms == filters.bedrooms);
                
                let matchesPrice = true;
                if (filters.priceRange) {
                    const [min, max] = filters.priceRange.split('-').map(Number);
                    matchesPrice = price >= min && (!max || price <= max);
                }
                
                if (matchesLocation && matchesType && matchesPrice && matchesBedrooms) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Apply filters to property cards as well
        const originalApplyFilters = applyFilters;
        applyFilters = function() {
            originalApplyFilters();
            filterPropertyCards();
        };
        
        // Initial filter application
        applyFilters();
    </script>
</body>
</html>
