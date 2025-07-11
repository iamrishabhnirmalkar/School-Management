<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

$errors = [];
$success = false;

// Form fields with default values
$vehicle = [
    'vehicle_type' => 'bus',
    'bus_number' => '',
    'route_name' => '',
    'driver_name' => '',
    'driver_phone' => '',
    'registration_number' => '',
    'model' => '',
    'year' => '',
    'capacity' => '',
    'stops' => '',
    'tracking_device_id' => '',
    'tracking_enabled' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $vehicle['vehicle_type'] = $_POST['vehicle_type'] ?? 'bus';
    $vehicle['bus_number'] = trim($_POST['bus_number'] ?? '');
    $vehicle['route_name'] = trim($_POST['route_name'] ?? '');
    $vehicle['driver_name'] = trim($_POST['driver_name'] ?? '');
    $vehicle['driver_phone'] = trim($_POST['driver_phone'] ?? '');
    $vehicle['registration_number'] = trim($_POST['registration_number'] ?? '');
    $vehicle['model'] = trim($_POST['model'] ?? '');
    $vehicle['year'] = intval($_POST['year'] ?? '');
    $vehicle['capacity'] = intval($_POST['capacity'] ?? '');
    $vehicle['stops'] = trim($_POST['stops'] ?? '');
    $vehicle['tracking_device_id'] = trim($_POST['tracking_device_id'] ?? '');
    $vehicle['tracking_enabled'] = isset($_POST['tracking_enabled']) ? 1 : 0;

    // Validate required fields
    if (empty($vehicle['bus_number'])) {
        $errors['bus_number'] = 'Bus/Auto number is required';
    }

    if (empty($vehicle['driver_name'])) {
        $errors['driver_name'] = 'Driver name is required';
    }

    if (empty($vehicle['driver_phone'])) {
        $errors['driver_phone'] = 'Driver phone is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $vehicle['driver_phone'])) {
        $errors['driver_phone'] = 'Invalid phone number format';
    }

    if ($vehicle['vehicle_type'] !== 'auto_rickshaw' && empty($vehicle['route_name'])) {
        $errors['route_name'] = 'Route name is required for buses';
    }

    if ($vehicle['vehicle_type'] !== 'auto_rickshaw' && empty($vehicle['capacity'])) {
        $errors['capacity'] = 'Capacity is required for buses';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        // Check if bus number already exists
        $check_query = "SELECT id FROM buses WHERE bus_number = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $vehicle['bus_number']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['bus_number'] = 'This vehicle number already exists';
        } else {
            // Insert new vehicle
            $insert_query = "INSERT INTO buses (
                vehicle_type, bus_number, route_name, driver_name, driver_phone,
                registration_number, model, year, capacity, stops,
                tracking_device_id, tracking_enabled
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param(
                "sssssssisssi",
                $vehicle['vehicle_type'],
                $vehicle['bus_number'],
                $vehicle['route_name'],
                $vehicle['driver_name'],
                $vehicle['driver_phone'],
                $vehicle['registration_number'],
                $vehicle['model'],
                $vehicle['year'],
                $vehicle['capacity'],
                $vehicle['stops'],
                $vehicle['tracking_device_id'],
                $vehicle['tracking_enabled']
            );

            if ($stmt->execute()) {
                $success = true;
                // Reset form for new entry
                $vehicle = [
                    'vehicle_type' => 'bus',
                    'bus_number' => '',
                    'route_name' => '',
                    'driver_name' => '',
                    'driver_phone' => '',
                    'registration_number' => '',
                    'model' => '',
                    'year' => '',
                    'capacity' => '',
                    'stops' => '',
                    'tracking_device_id' => '',
                    'tracking_enabled' => 0
                ];
            } else {
                $errors['database'] = 'Failed to add vehicle: ' . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Vehicle - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        function toggleAutoFields() {
            const vehicleType = document.getElementById('vehicle_type').value;
            const busFields = document.getElementById('bus_fields');
            const autoFields = document.getElementById('auto_fields');

            if (vehicleType === 'auto_rickshaw') {
                busFields.classList.add('hidden');
                autoFields.classList.remove('hidden');
                // Make route_name and capacity not required for autos
                document.getElementById('route_name').required = false;
                document.getElementById('capacity').required = false;
            } else {
                busFields.classList.remove('hidden');
                autoFields.classList.add('hidden');
                // Make route_name and capacity required for buses
                document.getElementById('route_name').required = true;
                document.getElementById('capacity').required = true;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', toggleAutoFields);
    </script>
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
                        <p class="text-blue-200">Add New Vehicle</p>
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
                        <a href="../dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-blue-700">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span>Back to Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
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
                            <span>Manage Autos</span>
                        </a>
                    </li>
                    <li>
                        <a href="add.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-plus w-5"></i>
                            <span>Add Vehicle</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Add New Vehicle</h2>

                <?php if ($success): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        <i class="fas fa-check-circle mr-2"></i> Vehicle added successfully!
                    </div>
                <?php elseif (!empty($errors['database'])): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($errors['database']) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-6">
                    <!-- Vehicle Type Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                        <select id="vehicle_type" name="vehicle_type" onchange="toggleAutoFields()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="bus" <?= $vehicle['vehicle_type'] === 'bus' ? 'selected' : '' ?>>School Bus</option>
                            <option value="minibus" <?= $vehicle['vehicle_type'] === 'minibus' ? 'selected' : '' ?>>Minibus</option>
                            <option value="auto_rickshaw" <?= $vehicle['vehicle_type'] === 'auto_rickshaw' ? 'selected' : '' ?>>Auto Rickshaw</option>
                        </select>
                    </div>

                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="bus_number" class="block text-sm font-medium text-gray-700 mb-1">
                                <?= $vehicle['vehicle_type'] === 'auto_rickshaw' ? 'Auto Number' : 'Bus Number' ?>
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="bus_number" name="bus_number" required
                                value="<?= htmlspecialchars($vehicle['bus_number']) ?>"
                                class="w-full px-3 py-2 border <?= isset($errors['bus_number']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md">
                            <?php if (isset($errors['bus_number'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['bus_number']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="driver_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Driver Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="driver_name" name="driver_name" required
                                value="<?= htmlspecialchars($vehicle['driver_name']) ?>"
                                class="w-full px-3 py-2 border <?= isset($errors['driver_name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md">
                            <?php if (isset($errors['driver_name'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['driver_name']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="driver_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                Driver Phone <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" id="driver_phone" name="driver_phone" required
                                value="<?= htmlspecialchars($vehicle['driver_phone']) ?>"
                                class="w-full px-3 py-2 border <?= isset($errors['driver_phone']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md">
                            <?php if (isset($errors['driver_phone'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['driver_phone']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-1">
                                Registration Number
                            </label>
                            <input type="text" id="registration_number" name="registration_number"
                                value="<?= htmlspecialchars($vehicle['registration_number']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Bus-specific Fields -->
                    <div id="bus_fields" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="route_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Route Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="route_name" name="route_name" required
                                    value="<?= htmlspecialchars($vehicle['route_name']) ?>"
                                    class="w-full px-3 py-2 border <?= isset($errors['route_name']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md">
                                <?php if (isset($errors['route_name'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['route_name']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">
                                    Passenger Capacity <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="capacity" name="capacity" min="1" required
                                    value="<?= htmlspecialchars($vehicle['capacity']) ?>"
                                    class="w-full px-3 py-2 border <?= isset($errors['capacity']) ? 'border-red-500' : 'border-gray-300' ?> rounded-md">
                                <?php if (isset($errors['capacity'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['capacity']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <label for="stops" class="block text-sm font-medium text-gray-700 mb-1">
                                Route Stops (comma separated)
                            </label>
                            <textarea id="stops" name="stops" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md"><?= htmlspecialchars($vehicle['stops']) ?></textarea>
                        </div>
                    </div>

                    <!-- Auto-specific Fields -->
                    <div id="auto_fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="model" class="block text-sm font-medium text-gray-700 mb-1">
                                Vehicle Model
                            </label>
                            <input type="text" id="model" name="model"
                                value="<?= htmlspecialchars($vehicle['model']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 mb-1">
                                Manufacturing Year
                            </label>
                            <input type="number" id="year" name="year" min="2000" max="<?= date('Y') ?>"
                                value="<?= htmlspecialchars($vehicle['year']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Tracking Information -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tracking Settings</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="tracking_device_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Tracking Device ID
                                </label>
                                <input type="text" id="tracking_device_id" name="tracking_device_id"
                                    value="<?= htmlspecialchars($vehicle['tracking_device_id']) ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <p class="mt-1 text-sm text-gray-500">Leave blank if no tracking device installed</p>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="tracking_enabled" name="tracking_enabled" value="1"
                                    <?= $vehicle['tracking_enabled'] ? 'checked' : '' ?>
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="tracking_enabled" class="ml-2 block text-sm text-gray-700">
                                    Enable Live Tracking
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-6">
                        <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            Add Vehicle
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>