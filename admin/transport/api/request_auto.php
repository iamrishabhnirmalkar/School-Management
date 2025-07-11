<?php
require_once '../config.php';

header('Content-Type: application/json');

$student_id = $_POST['student_id'] ?? 0;
$stop_name = $_POST['stop_name'] ?? '';
$pickup_time = $_POST['pickup_time'] ?? '';

// Validate input
if (empty($student_id) || empty($stop_name) || empty($pickup_time)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Find nearest available auto
$query = "SELECT id, bus_number, driver_name, driver_phone 
          FROM buses 
          WHERE vehicle_type = 'auto_rickshaw' AND tracking_enabled = TRUE
          ORDER BY ST_Distance(last_location, (SELECT last_location FROM users WHERE id = $student_id))
          LIMIT 1";

$result = $conn->query($query);
if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'No available autos']);
    exit;
}

$auto = $result->fetch_assoc();

// Create allocation (in a real app, you'd first notify the driver and wait for confirmation)
$insert_query = "INSERT INTO bus_allocations 
                (bus_id, student_id, stop_name, pickup_time, drop_time, monthly_fee, academic_year)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($insert_query);
$drop_time = date('H:i:s', strtotime($pickup_time) + 3600); // 1 hour after pickup
$monthly_fee = 1500; // Example fee
$academic_year = '2023-2024';

$stmt->bind_param(
    "iisssds",
    $auto['id'],
    $student_id,
    $stop_name,
    $pickup_time,
    $drop_time,
    $monthly_fee,
    $academic_year
);

if ($stmt->execute()) {
    $response = [
        'status' => 'success',
        'message' => 'Auto allocated',
        'auto' => [
            'number' => $auto['bus_number'],
            'driver' => $auto['driver_name'],
            'phone' => $auto['driver_phone'],
            'pickup_time' => $pickup_time
        ]
    ];
    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to allocate auto']);
}
