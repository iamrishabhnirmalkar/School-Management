<?php
require_once './config.php';

header('Content-Type: application/json');

// Simple API key authentication
$api_key = $_GET['api_key'] ?? '';
$driver_id = $_GET['driver_id'] ?? 0;
$lat = $_GET['lat'] ?? 0;
$lng = $_GET['lng'] ?? 0;

// Validate input
// if (empty($api_key) {
//     echo json_encode(['status' => 'error', 'message' => 'API key required']);
//     exit;
// }

// In a real implementation, verify API key against database
$valid = $conn->query("SELECT id FROM buses WHERE id = $driver_id AND tracking_device_id = '$api_key'")->num_rows > 0;

if (!$valid) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid API key or driver ID']);
    exit;
}

// Update location in database
$point = "POINT($lat $lng)";
$query = "UPDATE buses SET last_location = ST_GeomFromText('$point'), last_update = NOW() WHERE id = $driver_id";
$result = $conn->query($query);

if ($result) {
    echo json_encode(['status' => 'success', 'message' => 'Location updated']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update location']);
}
