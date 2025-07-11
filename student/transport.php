<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../logout.php");
    exit;
}

$student_id = $_SESSION['user']['id'];

// Get bus allocation information
$bus_info = [];
$result = $conn->query("SELECT b.bus_number, b.route_name, b.driver_name, b.driver_phone, 
                        b.current_location, b.last_updated,
                        ba.stop_name, ba.pickup_time, ba.drop_time
                        FROM bus_allocations ba
                        JOIN buses b ON ba.bus_id = b.id
                        WHERE ba.student_id = $student_id");
if ($result->num_rows > 0) {
    $bus_info = $result->fetch_assoc();
}

// Get bus route stops
$route_stops = [];
if (!empty($bus_info)) {
    $result = $conn->query("SELECT stop_name, pickup_time 
                            FROM bus_allocations 
                            WHERE bus_id = (SELECT bus_id FROM bus_allocations WHERE student_id = $student_id)
                            ORDER BY pickup_time");
    while ($row = $result->fetch_assoc()) {
        $route_stops[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places"></script>
    <style>
        #map {
            height: 300px;
            width: 100%;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100">
 <!-- Header -->
 <header class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-green-200">Student Marks</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-green-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Panel
                </a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Transport Information</h1>
            
            <?php if (empty($bus_info)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-bus text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">No bus allocation information available</p>
                    <p class="text-sm text-gray-400 mt-2">Contact school administration for bus allocation</p>
                </div>
            <?php else: ?>
                <!-- Bus Information -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h2 class="text-xl font-semibold mb-4">My Bus Details</h2>
                        <div class="space-y-4">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Bus Number:</span>
                                <span class="font-medium"><?= htmlspecialchars($bus_info['bus_number']) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Route Name:</span>
                                <span class="font-medium"><?= htmlspecialchars($bus_info['route_name']) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Driver Name:</span>
                                <span class="font-medium"><?= htmlspecialchars($bus_info['driver_name']) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Driver Contact:</span>
                                <span class="font-medium"><?= htmlspecialchars($bus_info['driver_phone']) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">My Stop:</span>
                                <span class="font-medium"><?= htmlspecialchars($bus_info['stop_name']) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Pickup Time:</span>
                                <span class="font-medium"><?= date('h:i A', strtotime($bus_info['pickup_time'])) ?></span>
                            </div>
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600">Drop Time:</span>
                                <span class="font-medium"><?= date('h:i A', strtotime($bus_info['drop_time'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Bus Location Map -->
                    <div>
                        <h2 class="text-xl font-semibold mb-4">Bus Location</h2>
                        <div class="bg-gray-100 p-4 rounded-lg">
                            <div id="map"></div>
                            <div class="mt-2 text-sm text-gray-600">
                                <p><strong>Current Location:</strong> <?= htmlspecialchars($bus_info['current_location']) ?></p>
                                <p><strong>Last Updated:</strong> <?= date('M d, Y h:i A', strtotime($bus_info['last_updated'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Route Stops -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Route Stops</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stop Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pickup Time</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($route_stops as $stop): ?>
                                    <tr class="<?= $stop['stop_name'] === $bus_info['stop_name'] ? 'bg-green-50' : '' ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($stop['stop_name']) ?>
                                            <?php if ($stop['stop_name'] === $bus_info['stop_name']): ?>
                                                <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">My Stop</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('h:i A', strtotime($stop['pickup_time'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Bus Change Request -->
                <div class="border-t pt-4">
                    <h2 class="text-xl font-semibold mb-4">Bus Change Request</h2>
                    <form class="max-w-md">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="reason">
                                Reason for Change
                            </label>
                            <select id="reason" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Select a reason</option>
                                <option value="address_change">Address Change</option>
                                <option value="timing_issue">Timing Issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="details">
                                Additional Details
                            </label>
                            <textarea id="details" rows="3" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Submit Request
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Initialize Google Map
        function initMap() {
            <?php if (!empty($bus_info)): ?>
                // In a real app, you would get actual coordinates from your database
                // For demo, we'll use a random location near the school
                const schoolLocation = { lat: 28.6139, lng: 77.2090 }; // Delhi coordinates
                const busLocation = {
                    lat: schoolLocation.lat + (Math.random() * 0.02 - 0.01),
                    lng: schoolLocation.lng + (Math.random() * 0.02 - 0.01)
                };
                
                const map = new google.maps.Map(document.getElementById("map"), {
                    zoom: 13,
                    center: busLocation,
                });
                
                // School marker
                new google.maps.Marker({
                    position: schoolLocation,
                    map,
                    title: "School",
                    icon: {
                        url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
                    }
                });
                
                // Bus marker
                new google.maps.Marker({
                    position: busLocation,
                    map,
                    title: "Your Bus",
                    icon: {
                        url: "http://maps.google.com/mapfiles/ms/icons/green-dot.png"
                    }
                });
            <?php endif; ?>
        }
        
        // Call initMap when the page loads
        window.onload = initMap;
    </script>
</body>
</html>