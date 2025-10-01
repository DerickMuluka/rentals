<?php
// Property management functions

/**
 * Get all properties with filters
 */
function get_properties($filters = [], $page = 1, $per_page = 12) {
    global $pdo;
    
    // Build WHERE clause
    $where_clauses = ["p.status = 'available'"];
    $params = [];
    
    if (!empty($filters['university_id'])) {
        $where_clauses[] = "p.university_id = ?";
        $params[] = $filters['university_id'];
    }
    
    if (!empty($filters['location'])) {
        $where_clauses[] = "p.location LIKE ?";
        $params[] = '%' . $filters['location'] . '%';
    }
    
    if (!empty($filters['type'])) {
        $where_clauses[] = "p.type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['min_price'])) {
        $where_clauses[] = "p.price >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $where_clauses[] = "p.price <= ?";
        $params[] = $filters['max_price'];
    }
    
    if (!empty($filters['bedrooms'])) {
        $where_clauses[] = "p.bedrooms = ?";
        $params[] = $filters['bedrooms'];
    }
    
    if (!empty($filters['amenities'])) {
        $amenities = is_array($filters['amenities']) ? $filters['amenities'] : [$filters['amenities']];
        foreach ($amenities as $index => $amenity) {
            $where_clauses[] = "FIND_IN_SET(?, p.amenities)";
            $params[] = $amenity;
        }
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM properties p WHERE $where_sql";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_properties = $stmt->fetchColumn();
    
    // Calculate pagination
    $pagination = get_pagination_data($page, $per_page, $total_properties);
    $offset = $pagination['offset'];
    
    // Get properties with owner and university info
    $sql = "SELECT p.*, 
                   u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
                   un.name as university_name, un.location as university_location
            FROM properties p
            LEFT JOIN users u ON p.owner_id = u.id
            LEFT JOIN universities un ON p.university_id = un.id
            WHERE $where_sql
            ORDER BY p.created_at DESC
            LIMIT $offset, $per_page";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();
    
    // Process amenities
    foreach ($properties as &$property) {
        $property['amenities_list'] = !empty($property['amenities']) ? 
            explode(',', $property['amenities']) : [];
    }
    
    return [
        'properties' => $properties,
        'pagination' => $pagination
    ];
}

/**
 * Get property by ID
 */
function get_property($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, 
                                  u.name as owner_name, u.email as owner_email, u.phone as owner_phone, u.avatar as owner_avatar,
                                  un.name as university_name, un.location as university_location
                           FROM properties p
                           LEFT JOIN users u ON p.owner_id = u.id
                           LEFT JOIN universities un ON p.university_id = un.id
                           WHERE p.id = ?");
    $stmt->execute([$id]);
    $property = $stmt->fetch();
    
    if ($property) {
        $property['amenities_list'] = !empty($property['amenities']) ? 
            explode(',', $property['amenities']) : [];
        
        // Get property images
        $property['images'] = get_property_images($id);
        
        // Get property reviews
        $property['reviews'] = get_property_reviews($id);
        
        // Calculate average rating
        $property['average_rating'] = calculate_average_rating($property['reviews']);
    }
    
    return $property;
}

/**
 * Get property images
 */
function get_property_images($property_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, created_at ASC");
    $stmt->execute([$property_id]);
    return $stmt->fetchAll();
}

/**
 * Get property reviews
 */
function get_property_reviews($property_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT r.*, u.name as user_name, u.avatar as user_avatar
                           FROM reviews r
                           LEFT JOIN users u ON r.user_id = u.id
                           WHERE r.property_id = ? AND r.status = 'approved'
                           ORDER BY r.created_at DESC");
    $stmt->execute([$property_id]);
    return $stmt->fetchAll();
}

/**
 * Calculate average rating from reviews
 */
function calculate_average_rating($reviews) {
    if (empty($reviews)) {
        return 0;
    }
    
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    
    return round($total_rating / count($reviews), 1);
}

/**
 * Create new property
 */
function create_property($owner_id, $data, $images = []) {
    global $pdo;
    
    // Validate required fields
    $required_fields = ['title', 'description', 'type', 'price', 'location', 'university_id'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'error' => "$field is required"];
        }
    }
    
    // Prepare property data
    $property_data = [
        'owner_id' => $owner_id,
        'title' => $data['title'],
        'description' => $data['description'],
        'type' => $data['type'],
        'price' => $data['price'],
        'location' => $data['location'],
        'university_id' => $data['university_id'],
        'bedrooms' => $data['bedrooms'] ?? null,
        'bathrooms' => $data['bathrooms'] ?? null,
        'size' => $data['size'] ?? null,
        'amenities' => !empty($data['amenities']) ? implode(',', $data['amenities']) : null,
        'status' => 'available',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Insert property
    $fields = implode(', ', array_keys($property_data));
    $placeholders = implode(', ', array_fill(0, count($property_data), '?'));
    
    $stmt = $pdo->prepare("INSERT INTO properties ($fields) VALUES ($placeholders)");
    
    try {
        $stmt->execute(array_values($property_data));
        $property_id = $pdo->lastInsertId();
        
        // Upload images if provided
        if (!empty($images)) {
            $upload_results = upload_property_images($property_id, $images);
            
            if (!$upload_results['success']) {
                // If image upload fails, delete the property and return error
                $pdo->prepare("DELETE FROM properties WHERE id = ?")->execute([$property_id]);
                return $upload_results;
            }
        }
        
        // Log activity
        log_activity($owner_id, 'property_create', "Created property: {$data['title']}");
        
        return ['success' => true, 'property_id' => $property_id];
    } catch (PDOException $e) {
        error_log("Property creation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Property creation failed. Please try again.'];
    }
}

/**
 * Update property
 */
function update_property($property_id, $owner_id, $data, $images = []) {
    global $pdo;
    
    // Check if property exists and belongs to user
    $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $owner_id]);
    
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'Property not found or access denied'];
    }
    
    // Prepare update data
    $update_data = [
        'title' => $data['title'],
        'description' => $data['description'],
        'type' => $data['type'],
        'price' => $data['price'],
        'location' => $data['location'],
        'university_id' => $data['university_id'],
        'bedrooms' => $data['bedrooms'] ?? null,
        'bathrooms' => $data['bathrooms'] ?? null,
        'size' => $data['size'] ?? null,
        'amenities' => !empty($data['amenities']) ? implode(',', $data['amenities']) : null,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Build update query
    $set_clause = implode(', ', array_map(function($field) {
        return "$field = ?";
    }, array_keys($update_data)));
    
    $values = array_values($update_data);
    $values[] = $property_id;
    
    // Update property
    $stmt = $pdo->prepare("UPDATE properties SET $set_clause WHERE id = ?");
    
    try {
        $stmt->execute($values);
        
        // Upload new images if provided
        if (!empty($images)) {
            $upload_results = upload_property_images($property_id, $images);
            
            if (!$upload_results['success']) {
                return $upload_results;
            }
        }
        
        // Log activity
        log_activity($owner_id, 'property_update', "Updated property: {$data['title']}");
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Property update error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Property update failed. Please try again.'];
    }
}

/**
 * Upload property images
 */
function upload_property_images($property_id, $images) {
    global $pdo;
    
    $uploaded_images = [];
    
    foreach ($images as $index => $image) {
        $upload_result = upload_file($image, PROPERTY_IMAGES);
        
        if (!$upload_result['success']) {
            // Delete any already uploaded images
            foreach ($uploaded_images as $uploaded_image) {
                delete_file(PROPERTY_IMAGES . $uploaded_image);
            }
            
            return $upload_result;
        }
        
        $uploaded_images[] = $upload_result['filename'];
        
        // Insert image record
        $is_primary = ($index === 0) ? 1 : 0; // First image is primary
        
        $stmt = $pdo->prepare("INSERT INTO property_images (property_id, image_url, is_primary) VALUES (?, ?, ?)");
        $stmt->execute([$property_id, $upload_result['filename'], $is_primary]);
    }
    
    return ['success' => true];
}

/**
 * Delete property
 */
function delete_property($property_id, $owner_id) {
    global $pdo;
    
    // Check if property exists and belongs to user
    $stmt = $pdo->prepare("SELECT id, title FROM properties WHERE id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $owner_id]);
    $property = $stmt->fetch();
    
    if (!$property) {
        return ['success' => false, 'error' => 'Property not found or access denied'];
    }
    
    // Check if property has active bookings
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE property_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->execute([$property_id]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Cannot delete property with active bookings'];
    }
    
    // Get property images
    $stmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
    $stmt->execute([$property_id]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete property images records
        $stmt = $pdo->prepare("DELETE FROM property_images WHERE property_id = ?");
        $stmt->execute([$property_id]);
        
        // Delete property reviews
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE property_id = ?");
        $stmt->execute([$property_id]);
        
        // Delete property favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE property_id = ?");
        $stmt->execute([$property_id]);
        
        // Delete property
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
        $stmt->execute([$property_id]);
        
        $pdo->commit();
        
        // Delete image files
        foreach ($images as $image) {
            delete_file(PROPERTY_IMAGES . $image);
        }
        
        // Log activity
        log_activity($owner_id, 'property_delete', "Deleted property: {$property['title']}");
        
        return ['success' => true];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Property deletion error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Property deletion failed. Please try again.'];
    }
}

/**
 * Add property to favorites
 */
function add_to_favorites($user_id, $property_id) {
    global $pdo;
    
    // Check if already in favorites
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$user_id, $property_id]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Property already in favorites'];
    }
    
    // Add to favorites
    $stmt = $pdo->prepare("INSERT INTO favorites (user_id, property_id, created_at) VALUES (?, ?, NOW())");
    
    try {
        $stmt->execute([$user_id, $property_id]);
        
        // Log activity
        log_activity($user_id, 'favorite_add', "Added property $property_id to favorites");
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Add to favorites error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to add to favorites. Please try again.'];
    }
}

/**
 * Remove property from favorites
 */
function remove_from_favorites($user_id, $property_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND property_id = ?");
    
    try {
        $stmt->execute([$user_id, $property_id]);
        
        // Log activity
        log_activity($user_id, 'favorite_remove', "Removed property $property_id from favorites");
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Remove from favorites error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to remove from favorites. Please try again.'];
    }
}

/**
 * Get user favorites
 */
function get_user_favorites($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, 
                                  u.name as owner_name,
                                  un.name as university_name,
                                  (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as primary_image
                           FROM favorites f
                           LEFT JOIN properties p ON f.property_id = p.id
                           LEFT JOIN users u ON p.owner_id = u.id
                           LEFT JOIN universities un ON p.university_id = un.id
                           WHERE f.user_id = ?
                           ORDER BY f.created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * Add property review
 */
function add_property_review($user_id, $property_id, $rating, $comment) {
    global $pdo;
    
    // Validate input
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
    }
    
    if (empty($comment)) {
        return ['success' => false, 'error' => 'Comment is required'];
    }
    
    // Check if user has booked this property
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND property_id = ? AND status = 'completed'");
    $stmt->execute([$user_id, $property_id]);
    
    if (!$stmt->fetch()) {
        return ['success' => false, 'error' => 'You can only review properties you have booked and completed'];
    }
    
    // Check if user has already reviewed this property
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND property_id = ?");
    $stmt->execute([$user_id, $property_id]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'You have already reviewed this property'];
    }
    
    // Add review
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, property_id, rating, comment, status, created_at) 
                           VALUES (?, ?, ?, ?, 'pending', NOW())");
    
    try {
        $stmt->execute([$user_id, $property_id, $rating, $comment]);
        
        // Log activity
        log_activity($user_id, 'review_add', "Added review for property $property_id");
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Add review error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to add review. Please try again.'];
    }
}