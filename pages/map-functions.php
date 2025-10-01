<?php
/**
 * Map-related helper functions for the Coastal Student Housing System
 */

/**
 * Calculate distance between two coordinates using Haversine formula
 * @param float $lat1 Latitude of first point
 * @param float $lon1 Longitude of first point
 * @param float $lat2 Latitude of second point
 * @param float $lon2 Longitude of second point
 * @return float Distance in kilometers
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

/**
 * Get properties near a university within specified distance
 * @param PDO $pdo Database connection
 * @param int $universityId University ID
 * @param float $maxDistance Maximum distance in kilometers
 * @return array Properties within the specified distance
 */
function getPropertiesNearUniversity($pdo, $universityId, $maxDistance = 10) {
    // Get university coordinates
    $stmt = $pdo->prepare("SELECT latitude, longitude FROM universities WHERE university_id = ?");
    $stmt->execute([$universityId]);
    $university = $stmt->fetch();
    
    if (!$university || !$university['latitude'] || !$university['longitude']) {
        return [];
    }
    
    $uniLat = (float)$university['latitude'];
    $uniLng = (float)$university['longitude'];
    
    // Get all properties with coordinates
    $properties = $pdo->query("SELECT * FROM properties WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND status = 'available'")->fetchAll();
    
    $nearbyProperties = [];
    
    foreach ($properties as $property) {
        $distance = calculateDistance($uniLat, $uniLng, (float)$property['latitude'], (float)$property['longitude']);
        
        if ($distance <= $maxDistance) {
            $property['distance_to_university'] = round($distance, 2);
            $nearbyProperties[] = $property;
        }
    }
    
    // Sort by distance
    usort($nearbyProperties, function($a, $b) {
        return $a['distance_to_university'] <=> $b['distance_to_university'];
    });
    
    return $nearbyProperties;
}

/**
 * Get properties within a bounding box
 * @param PDO $pdo Database connection
 * @param float $minLat Minimum latitude
 * @param float $maxLat Maximum latitude
 * @param float $minLng Minimum longitude
 * @param float $maxLng Maximum longitude
 * @return array Properties within the bounding box
 */
function getPropertiesInBounds($pdo, $minLat, $maxLat, $minLng, $maxLng) {
    $stmt = $pdo->prepare("
        SELECT * FROM properties 
        WHERE latitude BETWEEN ? AND ? 
        AND longitude BETWEEN ? AND ?
        AND status = 'available'
    ");
    
    $stmt->execute([$minLat, $maxLat, $minLng, $maxLng]);
    return $stmt->fetchAll();
}

/**
 * Get coordinates for a location using OpenStreetMap Nominatim
 * @param string $location Location name
 * @return array|null Array with 'lat' and 'lng' or null if not found
 */
function geocodeLocation($location) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location) . ", Kenya";
    
    $options = [
        'http' => [
            'header' => "User-Agent: CoastalStudentHousing/1.0\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!empty($data)) {
        return [
            'lat' => (float)$data[0]['lat'],
            'lng' => (float)$data[0]['lon']
        ];
    }
    
    return null;
}

/**
 * Get nearby universities for a property
 * @param PDO $pdo Database connection
 * @param int $propertyId Property ID
 * @param float $maxDistance Maximum distance in kilometers
 * @return array Universities within the specified distance
 */
function getUniversitiesNearProperty($pdo, $propertyId, $maxDistance = 20) {
    // Get property coordinates
    $stmt = $pdo->prepare("SELECT latitude, longitude FROM properties WHERE property_id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();
    
    if (!$property || !$property['latitude'] || !$property['longitude']) {
        return [];
    }
    
    $propLat = (float)$property['latitude'];
    $propLng = (float)$property['longitude'];
    
    // Get all universities with coordinates
    $universities = $pdo->query("SELECT * FROM universities WHERE latitude IS NOT NULL AND longitude IS NOT NULL")->fetchAll();
    
    $nearbyUniversities = [];
    
    foreach ($universities as $university) {
        $distance = calculateDistance($propLat, $propLng, (float)$university['latitude'], (float)$university['longitude']);
        
        if ($distance <= $maxDistance) {
            $university['distance_to_property'] = round($distance, 2);
            $nearbyUniversities[] = $university;
        }
    }
    
    // Sort by distance
    usort($nearbyUniversities, function($a, $b) {
        return $a['distance_to_property'] <=> $b['distance_to_property'];
    });
    
    return $nearbyUniversities;
}