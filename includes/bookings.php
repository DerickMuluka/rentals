<?php
// Booking management functions

/**
 * Create a new booking
 */
function create_booking($user_id, $property_id, $check_in, $check_out, $guests, $special_requests = null) {
    global $pdo;
    
    // Validate input
    if (empty($check_in) || empty($check_out) || empty($guests)) {
        return ['success' => false, 'error' => 'Check-in, check-out dates and number of guests are required'];
    }
    
    // Validate dates
    $check_in_date = DateTime::createFromFormat('Y-m-d', $check_in);
    $check_out_date = DateTime::createFromFormat('Y-m-d', $check_out);
    $today = new DateTime();
    
    if (!$check_in_date || !$check_out_date) {
        return ['success' => false, 'error' => 'Invalid date format'];
    }
    
    if ($check_in_date < $today) {
        return ['success' => false, 'error' => 'Check-in date cannot be in the past'];
    }
    
    if ($check_out_date <= $check_in_date) {
        return ['success' => false, 'error' => 'Check-out date must be after check-in date'];
    }
    
    // Check property availability
    $property = get_property($property_id);
    if (!$property) {
        return ['success' => false, 'error' => 'Property not found'];
    }
    
    if ($property['status'] !== 'available') {
        return ['success' => false, 'error' => 'Property is not available for booking'];
    }
    
    // Check for overlapping bookings
    $stmt = $pdo->prepare("SELECT id FROM bookings 
                           WHERE property_id = ? 
                           AND status IN ('pending', 'confirmed')
                           AND (
                               (check_in <= ? AND check_out >= ?) OR
                               (check_in >= ? AND check_in < ?)
                           )");
    $stmt->execute([$property_id, $check_in, $check_in, $check_in, $check_out]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Property is not available for the selected dates'];
    }
    
    // Calculate total amount
    $nights = $check_out_date->diff($check_in_date)->days;
    $total_amount = $property['price'] * $nights;
    
    // Create booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, property_id, check_in, check_out, guests, total_amount, special_requests, status, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
    
    try {
        $stmt->execute([$user_id, $property_id, $check_in, $check_out, $guests, $total_amount, $special_requests]);
        $booking_id = $pdo->lastInsertId();
        
        // Send notification to property owner
        $owner_message = "New booking request for your property: {$property['title']}";
        create_notification($property['owner_id'], 'New Booking Request', $owner_message, '/owner/bookings.php');
        
        // Log activity
        log_activity($user_id, 'booking_create', "Created booking for property: {$property['title']}");
        
        return ['success' => true, 'booking_id' => $booking_id];
    } catch (PDOException $e) {
        error_log("Booking creation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Booking creation failed. Please try again.'];
    }
}

/**
 * Get booking by ID
 */
function get_booking($booking_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT b.*, 
                                  p.title as property_title, p.price as property_price, p.location as property_location,
                                  u.name as user_name, u.email as user_email, u.phone as user_phone,
                                  o.name as owner_name, o.email as owner_email, o.phone as owner_phone
                           FROM bookings b
                           LEFT JOIN properties p ON b.property_id = p.id
                           LEFT JOIN users u ON b.user_id = u.id
                           LEFT JOIN users o ON p.owner_id = o.id
                           WHERE b.id = ?");
    $stmt->execute([$booking_id]);
    return $stmt->fetch();
}

/**
 * Get user bookings
 */
function get_user_bookings($user_id, $status = null, $page = 1, $per_page = 10) {
    global $pdo;
    
    // Build WHERE clause
    $where_clause = "b.user_id = ?";
    $params = [$user_id];
    
    if ($status) {
        $where_clause .= " AND b.status = ?";
        $params[] = $status;
    }
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM bookings b WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_bookings = $stmt->fetchColumn();
    
    // Calculate pagination
    $pagination = get_pagination_data($page, $per_page, $total_bookings);
    $offset = $pagination['offset'];
    
    // Get bookings
    $sql = "SELECT b.*, 
                   p.title as property_title, p.location as property_location,
                   (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            WHERE $where_clause
            ORDER BY b.created_at DESC
            LIMIT $offset, $per_page";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    return [
        'bookings' => $bookings,
        'pagination' => $pagination
    ];
}

/**
 * Get owner bookings (bookings for properties owned by user)
 */
function get_owner_bookings($owner_id, $status = null, $page = 1, $per_page = 10) {
    global $pdo;
    
    // Build WHERE clause
    $where_clause = "p.owner_id = ?";
    $params = [$owner_id];
    
    if ($status) {
        $where_clause .= " AND b.status = ?";
        $params[] = $status;
    }
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM bookings b
                  LEFT JOIN properties p ON b.property_id = p.id
                  WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_bookings = $stmt->fetchColumn();
    
    // Calculate pagination
    $pagination = get_pagination_data($page, $per_page, $total_bookings);
    $offset = $pagination['offset'];
    
    // Get bookings
    $sql = "SELECT b.*, 
                   p.title as property_title, p.location as property_location,
                   u.name as user_name, u.email as user_email, u.phone as user_phone,
                   (SELECT image_url FROM property_images WHERE property_id = p.id AND is_primary = 1 LIMIT 1) as property_image
            FROM bookings b
            LEFT JOIN properties p ON b.property_id = p.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE $where_clause
            ORDER BY b.created_at DESC
            LIMIT $offset, $per_page";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    return [
        'bookings' => $bookings,
        'pagination' => $pagination
    ];
}

/**
 * Update booking status
 */
function update_booking_status($booking_id, $status, $user_id = null) {
    global $pdo;
    
    // Get booking details
    $booking = get_booking($booking_id);
    if (!$booking) {
        return ['success' => false, 'error' => 'Booking not found'];
    }
    
    // Check permissions
    $user_data = get_user_data();
    if ($user_data['role'] === 'owner' && $booking['owner_id'] != $user_data['id']) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    
    // Validate status transition
    $valid_transitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => []
    ];
    
    if (!in_array($status, $valid_transitions[$booking['status']])) {
        return ['success' => false, 'error' => "Cannot change status from {$booking['status']} to $status"];
    }
    
    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    
    try {
        $stmt->execute([$status, $booking_id]);
        
        // Send notification based on status change
        $notification_message = '';
        $notification_url = '';
        
        switch ($status) {
            case 'confirmed':
                $notification_message = "Your booking for {$booking['property_title']} has been confirmed";
                $notification_url = '/bookings.php';
                break;
                
            case 'cancelled':
                if ($user_data['role'] === 'owner') {
                    $notification_message = "Your booking for {$booking['property_title']} has been cancelled by the owner";
                } else {
                    $notification_message = "Booking for {$booking['property_title']} has been cancelled by the tenant";
                }
                break;
                
            case 'completed':
                $notification_message = "Your booking for {$booking['property_title']} has been marked as completed";
                break;
        }
        
        if ($notification_message) {
            $target_user_id = ($user_data['role'] === 'owner') ? $booking['user_id'] : $booking['owner_id'];
            create_notification($target_user_id, 'Booking Status Updated', $notification_message, $notification_url);
        }
        
        // Log activity
        log_activity($user_data['id'], 'booking_status_update', "Changed booking $booking_id status to $status");
        
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Booking status update error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update booking status. Please try again.'];
    }
}

/**
 * Cancel booking
 */
function cancel_booking($booking_id, $user_id) {
    global $pdo;
    
    // Get booking details
    $booking = get_booking($booking_id);
    if (!$booking) {
        return ['success' => false, 'error' => 'Booking not found'];
    }
    
    // Check permissions
    if ($booking['user_id'] != $user_id && $booking['owner_id'] != $user_id) {
        return ['success' => false, 'error' => 'Access denied'];
    }
    
    // Check if booking can be cancelled
    if ($booking['status'] !== 'pending' && $booking['status'] !== 'confirmed') {
        return ['success' => false, 'error' => 'Booking cannot be cancelled at this stage'];
    }
    
    // Calculate cancellation fee if applicable
    $check_in_date = new DateTime($booking['check_in']);
    $today = new DateTime();
    $days_until_checkin = $today->diff($check_in_date)->days;
    
    $cancellation_fee = 0;
    if ($days_until_checkin < 7) {
        // 50% cancellation fee if less than 7 days before check-in
        $cancellation_fee = $booking['total_amount'] * 0.5;
    } elseif ($days_until_checkin < 14) {
        // 25% cancellation fee if less than 14 days before check-in
        $cancellation_fee = $booking['total_amount'] * 0.25;
    }
    
    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancellation_fee = ?, updated_at = NOW() WHERE id = ?");
    
    try {
        $stmt->execute([$cancellation_fee, $booking_id]);
        
        // Send notification
        $cancelled_by = ($booking['user_id'] == $user_id) ? 'tenant' : 'owner';
        $notification_message = "Booking for {$booking['property_title']} has been cancelled by the $cancelled_by";
        
        $target_user_id = ($cancelled_by === 'owner') ? $booking['user_id'] : $booking['owner_id'];
        create_notification($target_user_id, 'Booking Cancelled', $notification_message, '/bookings.php');
        
        // Log activity
        log_activity($user_id, 'booking_cancel', "Cancelled booking $booking_id");
        
        return ['success' => true, 'cancellation_fee' => $cancellation_fee];
    } catch (PDOException $e) {
        error_log("Booking cancellation error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to cancel booking. Please try again.'];
    }
}

/**
 * Check if user can review property (has completed booking)
 */
function can_review_property($user_id, $property_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id FROM bookings 
                           WHERE user_id = ? AND property_id = ? AND status = 'completed'");
    $stmt->execute([$user_id, $property_id]);
    
    return (bool) $stmt->fetch();
}

/**
 * Create notification
 */
function create_notification($user_id, $title, $message, $url = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, url, is_read, created_at)
                           VALUES (?, ?, ?, ?, 0, NOW())");
    
    try {
        $stmt->execute([$user_id, $title, $message, $url]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification creation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user notifications
 */
function get_user_notifications($user_id, $unread_only = false, $limit = 10) {
    global $pdo;
    
    $where_clause = "user_id = ?";
    $params = [$user_id];
    
    if ($unread_only) {
        $where_clause .= " AND is_read = 0";
    }
    
    $stmt = $pdo->prepare("SELECT * FROM notifications 
                           WHERE $where_clause 
                           ORDER BY created_at DESC 
                           LIMIT $limit");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id, $user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    
    try {
        $stmt->execute([$notification_id, $user_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification read error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read
 */
function mark_all_notifications_read($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    
    try {
        $stmt->execute([$user_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Mark all notifications read error: " . $e->getMessage());
        return false;
    }
}