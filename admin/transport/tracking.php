<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get all vehicles with tracking enabled
$vehicles = $conn->query("
    SELECT id, bus_number, route_name, driver_name, driver_phone, 
           ST_X(last_location) as lat, ST_Y(last_location) as lng,
           last_update, vehicle_type
    FROM buses 
    WHERE tracking_enabled = TRUE AND last_location IS NOT NULL
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        #map {
            height: 600px;
        }

        .vehicle-marker {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #3b82f6;
            border: 2px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .auto-marker {
            background-color: #10b981;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <!-- Same header as index.php -->
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0">
            <!-- Same sidebar as index.php -->
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Live Vehicle Tracking</h2>

                <!-- Vehicle Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer"
                            onclick="focusVehicle(<?= $vehicle['id'] ?>)">
                            <div class="flex items-center">
                                <div class="p-2 rounded-full <?= $vehicle['vehicle_type'] == 'auto_rickshaw' ? 'bg-green-100 text-green-600' : 'bg-blue-100 text-blue-600' ?>">
                                    <i class="fas <?= $vehicle['vehicle_type'] == 'auto_rickshaw' ? 'fa-rickshaw' : 'fa-bus' ?>"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="font-medium"><?= htmlspecialchars($vehicle['bus_number']) ?></h3>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($vehicle['driver_name']) ?></p>
                                    <p class="text-xs text-gray-400">Last update: <?= $vehicle['last_update'] ? date('H:i', strtotime($vehicle['last_update'])) : 'N/A' ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Map Container -->
                <div id="map" class="rounded-lg border border-gray-200"></div>

                <!-- Tracking Controls -->
                <div class="mt-4 flex justify-between items-center">
                    <div class="flex space-x-2">
                        <button onclick="refreshMap()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                        <button onclick="centerMap()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <i class="fas fa-crosshairs mr-2"></i> Center
                        </button>
                    </div>
                    <div class="text-sm text-gray-500">
                        Last updated: <?= date('Y-m-d H:i:s') ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([20.5937, 78.9629], 12); // Default to India view

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Vehicle markers
        const vehicleMarkers = {};

        // Add vehicles to map
        <?php foreach ($vehicles as $vehicle): ?>
            <?php if ($vehicle['lat'] && $vehicle['lng']): ?>
                const marker<?= $vehicle['id'] ?> = L.marker([<?= $vehicle['lat'] ?>, <?= $vehicle['lng'] ?>], {
                    icon: L.divIcon({
                        className: 'vehicle-marker <?= $vehicle['vehicle_type'] == 'auto_rickshaw' ? 'auto-marker' : '' ?>',
                        html: '<i class="fas <?= $vehicle['vehicle_type'] == 'auto_rickshaw' ? 'fa-rickshaw' : 'fa-bus' ?> text-xs"></i>',
                        iconSize: [30, 30]
                    })
                }).addTo(map);

                marker<?= $vehicle['id'] ?>.bindPopup(`
                    <b><?= addslashes($vehicle['bus_number']) ?></b><br>
                    Driver: <?= addslashes($vehicle['driver_name']) ?><br>
                    Route: <?= addslashes($vehicle['route_name']) ?><br>
                    Last update: <?= $vehicle['last_update'] ? date('H:i', strtotime($vehicle['last_update'])) : 'N/A' ?>
                `);

                vehicleMarkers[<?= $vehicle['id'] ?>] = marker<?= $vehicle['id'] ?>;
            <?php endif; ?>
        <?php endforeach; ?>

        // Center on first vehicle if available
        <?php if (!empty($vehicles) && $vehicles[0]['lat'] && $vehicles[0]['lng']): ?>
            map.setView([<?= $vehicles[0]['lat'] ?>, <?= $vehicles[0]['lng'] ?>], 14);
        <?php endif; ?>

        // Functions
        function focusVehicle(vehicleId) {
            const marker = vehicleMarkers[vehicleId];
            if (marker) {
                map.setView(marker.getLatLng(), 16);
                marker.openPopup();
            }
        }

        function refreshMap() {
            window.location.reload();
        }

        function centerMap() {
            <?php if (!empty($vehicles) && $vehicles[0]['lat'] && $vehicles[0]['lng']): ?>
                map.setView([<?= $vehicles[0]['lat'] ?>, <?= $vehicles[0]['lng'] ?>], 12);
            <?php else: ?>
                map.setView([20.5937, 78.9629], 5);
            <?php endif; ?>
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshMap, 30000);
    </script>
</body>

</html>