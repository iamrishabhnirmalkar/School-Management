<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get transport stats
$stats = [
    'total_buses' => 0,
    'active_buses' => 0,
    'students_transported' => 0,
    'autos' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM buses");
$stats['total_buses'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(DISTINCT bus_id) as count FROM bus_allocations");
$stats['active_buses'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM bus_allocations");
$stats['students_transported'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM buses WHERE vehicle_type='auto_rickshaw'");
$stats['autos'] = $result->fetch_assoc()['count'];

// Get recent bus allocations
$recent_allocations = [];
$result = $conn->query("
    SELECT ba.id, u.full_name as student_name, b.bus_number, b.route_name, ba.stop_name, ba.pickup_time
    FROM bus_allocations ba
    JOIN users u ON ba.student_id = u.id
    JOIN buses b ON ba.bus_id = b.id
    ORDER BY ba.id DESC LIMIT 5
");
while ($row = $result->fetch_assoc()) {
    $recent_allocations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Transport Management</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <div class="flex items-center space-x-2 cursor-pointer">
                            <img src="../../assets/img/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
                            <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0">
            <nav class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <ul class="space-y-2">
                    <li>
                        <a href="../../admin/dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-blue-700">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span>Back to Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-tachometer-alt w-5"></i>
                            <span>Transport Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="buses.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-bus w-5"></i>
                            <span>Manage Buses</span>
                        </a>
                    </li>
                    <li>
                        <a href="autos.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-rickshaw w-5"></i>
                            <span>Manage Auto Rickshaws</span>
                        </a>
                    </li>
                    <li>
                        <a href="routes.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-route w-5"></i>
                            <span>Manage Routes</span>
                        </a>
                    </li>
                    <li>
                        <a href="allocations.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-users w-5"></i>
                            <span>Student Allocations</span>
                        </a>
                    </li>
                    <li>
                        <a href="tracking.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-map-marker-alt w-5"></i>
                            <span>Live Tracking</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Buses Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-bus text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Buses</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['total_buses'] ?></p>
                            <a href="buses.php" class="text-blue-600 text-sm hover:underline">View All</a>
                        </div>
                    </div>
                </div>

                <!-- Active Buses Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-bus-alt text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Active Buses</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['active_buses'] ?></p>
                            <a href="routes.php" class="text-green-600 text-sm hover:underline">View Routes</a>
                        </div>
                    </div>
                </div>

                <!-- Students Transported Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Students Transported</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['students_transported'] ?></p>
                            <a href="allocations.php" class="text-purple-600 text-sm hover:underline">View Allocations</a>
                        </div>
                    </div>
                </div>

                <!-- Auto Rickshaws Card -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <i class="fas fa-rickshaw text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Auto Rickshaws</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['autos'] ?></p>
                            <a href="autos.php" class="text-orange-600 text-sm hover:underline">Manage Autos</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <a href="buses/add.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-bus text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add New Bus</span>
                    </a>
                    <a href="autos/add.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-rickshaw text-green-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Auto Rickshaw</span>
                    </a>
                    <a href="routes/add.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-route text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Create Route</span>
                    </a>
                    <a href="allocations/add.php" class="flex flex-col items-center justify-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-user-plus text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Allocate Student</span>
                    </a>
                </div>
            </div>

            <!-- Recent Allocations and Map -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Allocations -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Recent Student Allocations</h2>
                    <div class="space-y-4">
                        <?php if (empty($recent_allocations)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users text-2xl text-gray-400 mb-2"></i>
                                <p class="text-gray-500 text-sm">No student allocations yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_allocations as $allocation): ?>
                                <div class="border-l-4 border-blue-500 pl-4 py-2 hover:bg-gray-50 transition">
                                    <h3 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($allocation['student_name']) ?></h3>
                                    <p class="text-gray-600 text-xs">Bus: <?= htmlspecialchars($allocation['bus_number']) ?> (<?= htmlspecialchars($allocation['route_name']) ?>)</p>
                                    <div class="flex justify-between items-center mt-1">
                                        <p class="text-gray-500 text-xs">Stop: <?= htmlspecialchars($allocation['stop_name']) ?> at <?= htmlspecialchars($allocation['pickup_time']) ?></p>
                                        <a href="allocations/edit.php?id=<?= $allocation['id'] ?>" class="text-blue-600 text-xs hover:underline">Details</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="allocations.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All Allocations <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

                <!-- Live Tracking Preview -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Live Tracking Preview</h2>
                    <div id="mapPreview" class="h-64 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marked-alt text-4xl text-gray-400"></i>
                        <p class="ml-3 text-gray-500">Live vehicle positions will appear here</p>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="tracking.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Open Full Tracking Map <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200 py-6 mt-8">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p class="text-gray-600">© 2025 School ERP System. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-600 hover:text-blue-600"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-600 hover:text-blue-400"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-600 hover:text-red-600"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Initialize map preview
        const mapPreview = L.map('mapPreview').setView([20.5937, 78.9629], 5); // Default to India view

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mapPreview);

        // In a real implementation, you would fetch bus locations from your API
        // and update the map with markers
    </script>
</body>

</html>