<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $student_id = $_POST['student_id'];
                $bus_id = $_POST['bus_id'];
                $stop_name = $_POST['stop_name'];
                $pickup_time = $_POST['pickup_time'];
                $drop_time = $_POST['drop_time'];
                $monthly_fee = $_POST['monthly_fee'];
                $academic_year = $_POST['academic_year'];

                $stmt = $conn->prepare("INSERT INTO bus_allocations (bus_id, student_id, stop_name, pickup_time, drop_time, monthly_fee, payment_status, academic_year) VALUES (?, ?, ?, ?, ?, ?, 'unpaid', ?)");
                $stmt->bind_param("iisssds", $bus_id, $student_id, $stop_name, $pickup_time, $drop_time, $monthly_fee, $academic_year);
                
                if ($stmt->execute()) {
                    $allocation_id = $stmt->insert_id;
                    // Update student's bus_allocation_id
                    $stmt = $conn->prepare("UPDATE students SET bus_allocation_id = ? WHERE user_id = ?");
                    $stmt->bind_param("ii", $allocation_id, $student_id);
                    $stmt->execute();
                    $_SESSION['success'] = "Transport allocation added successfully!";
                } else {
                    $_SESSION['error'] = "Error adding allocation: " . $conn->error;
                }
                break;

            case 'edit':
                $allocation_id = $_POST['allocation_id'];
                $stop_name = $_POST['stop_name'];
                $pickup_time = $_POST['pickup_time'];
                $drop_time = $_POST['drop_time'];
                $monthly_fee = $_POST['monthly_fee'];
                $payment_status = $_POST['payment_status'];

                $stmt = $conn->prepare("UPDATE bus_allocations SET stop_name = ?, pickup_time = ?, drop_time = ?, monthly_fee = ?, payment_status = ? WHERE id = ?");
                $stmt->bind_param("sssdsi", $stop_name, $pickup_time, $drop_time, $monthly_fee, $payment_status, $allocation_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Transport allocation updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating allocation: " . $conn->error;
                }
                break;

            case 'delete':
                $allocation_id = $_POST['allocation_id'];
                
                // Get student_id before deleting
                $stmt = $conn->prepare("SELECT student_id FROM bus_allocations WHERE id = ?");
                $stmt->bind_param("i", $allocation_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $allocation = $result->fetch_assoc();
                
                if ($allocation) {
                    // Remove bus_allocation_id from student
                    $stmt = $conn->prepare("UPDATE students SET bus_allocation_id = NULL WHERE user_id = ?");
                    $stmt->bind_param("i", $allocation['student_id']);
                    $stmt->execute();
                    
                    // Delete allocation
                    $stmt = $conn->prepare("DELETE FROM bus_allocations WHERE id = ?");
                    $stmt->bind_param("i", $allocation_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Transport allocation removed successfully!";
                    } else {
                        $_SESSION['error'] = "Error removing allocation: " . $conn->error;
                    }
                }
                break;
        }
        header("Location: allocations.php");
        exit;
    }
}

// Get filters
$search = $_GET['search'] ?? '';
$bus_filter = $_GET['bus_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';
$payment_filter = $_GET['payment_filter'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.admission_number LIKE ? OR b.bus_number LIKE ? OR b.route_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'ssss';
}

if (!empty($bus_filter)) {
    $where_conditions[] = "ba.bus_id = ?";
    $params[] = $bus_filter;
    $param_types .= 'i';
}

if (!empty($status_filter)) {
    $where_conditions[] = "ba.payment_status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get allocations
$query = "SELECT ba.id, ba.stop_name, ba.pickup_time, ba.drop_time, ba.monthly_fee, ba.payment_status, ba.academic_year,
                 u.id as student_id, u.full_name as student_name, u.admission_number,
                 b.id as bus_id, b.bus_number, b.route_name, b.vehicle_type, b.driver_name, b.driver_phone,
                 c.class_name, c.section
          FROM bus_allocations ba
          JOIN users u ON ba.student_id = u.id
          JOIN buses b ON ba.bus_id = b.id
          LEFT JOIN students s ON u.id = s.user_id
          LEFT JOIN classes c ON s.class_id = c.id
          $where_clause
          ORDER BY ba.id DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$allocations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available buses for filter
$buses = $conn->query("SELECT id, bus_number, route_name, vehicle_type FROM buses ORDER BY bus_number")->fetch_all(MYSQLI_ASSOC);

// Get unallocated students
$unallocated_students = $conn->query("
    SELECT u.id, u.full_name, u.admission_number, c.class_name, c.section
    FROM users u
    JOIN students s ON u.id = s.user_id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE u.role = 'student' AND s.bus_allocation_id IS NULL
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Allocations - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <p class="text-blue-200">Transport Allocations</p>
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
                            <span>Manage Auto Rickshaws</span>
                        </a>
                    </li>
                    <li>
                        <a href="allocations.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
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
                        <a href="bus-id-cards/index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-id-card w-5"></i>
                            <span>Bus ID Cards</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <!-- Page Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Transport Allocations</h1>
                    <p class="text-gray-600">Manage student bus and auto rickshaw allocations</p>
                </div>
                <button onclick="openAddModal()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Allocation</span>
                </button>
            </div>

            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Student name, admission number, bus number..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bus/Vehicle</label>
                        <select name="bus_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Vehicles</option>
                            <?php foreach ($buses as $bus): ?>
                                <option value="<?= $bus['id'] ?>" <?= $bus_filter == $bus['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bus['bus_number']) ?> - <?= htmlspecialchars($bus['route_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                        <select name="payment_filter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="paid" <?= $payment_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="unpaid" <?= $payment_filter === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 mr-2">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="allocations.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Allocations Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Current Allocations (<?= count($allocations) ?>)</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stop & Times</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fees</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($allocations)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        No allocations found. <a href="#" onclick="openAddModal()" class="text-blue-600 hover:underline">Add the first allocation</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allocations as $allocation): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($allocation['student_name']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= htmlspecialchars($allocation['admission_number']) ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?= htmlspecialchars($allocation['class_name'] ?? 'N/A') ?> <?= htmlspecialchars($allocation['section'] ?? '') ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($allocation['bus_number']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= htmlspecialchars($allocation['route_name']) ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?= ucfirst(str_replace('_', ' ', $allocation['vehicle_type'])) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($allocation['stop_name']) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    Pickup: <?= date('H:i', strtotime($allocation['pickup_time'])) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    Drop: <?= date('H:i', strtotime($allocation['drop_time'])) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                ₹<?= number_format($allocation['monthly_fee'], 2) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= htmlspecialchars($allocation['academic_year']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?= $allocation['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= ucfirst($allocation['payment_status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($allocation)) ?>)" 
                                                        class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteAllocation(<?= $allocation['id'] ?>)" 
                                                        class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Unallocated Students -->
            <?php if (!empty($unallocated_students)): ?>
                <div class="bg-white rounded-lg shadow-md mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">Unallocated Students (<?= count($unallocated_students) ?>)</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($unallocated_students as $student): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-medium text-gray-900"><?= htmlspecialchars($student['full_name']) ?></h3>
                                            <p class="text-sm text-gray-500"><?= htmlspecialchars($student['admission_number']) ?></p>
                                            <p class="text-xs text-gray-400">
                                                <?= htmlspecialchars($student['class_name'] ?? 'N/A') ?> <?= htmlspecialchars($student['section'] ?? '') ?>
                                            </p>
                                        </div>
                                        <button onclick="openAddModal(<?= htmlspecialchars(json_encode($student)) ?>)" 
                                                class="text-blue-600 hover:text-blue-900 text-sm">
                                            <i class="fas fa-plus"></i> Allocate
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Add/Edit Modal -->
    <div id="allocationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Add Transport Allocation</h3>
                </div>
                <form id="allocationForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="allocation_id" id="allocationId">
                    
                    <div class="px-6 py-4 space-y-4">
                        <!-- Student Selection -->
                        <div id="studentSelection">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Student</label>
                            <select name="student_id" id="studentId" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Student</option>
                                <?php foreach ($unallocated_students as $student): ?>
                                    <option value="<?= $student['id'] ?>">
                                        <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['admission_number']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Vehicle Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle</label>
                            <select name="bus_id" id="busId" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Vehicle</option>
                                <?php foreach ($buses as $bus): ?>
                                    <option value="<?= $bus['id'] ?>">
                                        <?= htmlspecialchars($bus['bus_number']) ?> - <?= htmlspecialchars($bus['route_name']) ?> (<?= ucfirst(str_replace('_', ' ', $bus['vehicle_type'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Stop Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Stop Name</label>
                            <input type="text" name="stop_name" id="stopName" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g., Central Park, Main Street">
                        </div>

                        <!-- Pickup Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pickup Time</label>
                            <input type="time" name="pickup_time" id="pickupTime" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Drop Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Drop Time</label>
                            <input type="time" name="drop_time" id="dropTime" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Monthly Fee -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Fee (₹)</label>
                            <input type="number" name="monthly_fee" id="monthlyFee" required step="0.01" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="1500.00">
                        </div>

                        <!-- Academic Year -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
                            <input type="text" name="academic_year" id="academicYear" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="2024-2025" value="2024-2025">
                        </div>

                        <!-- Payment Status (for edit mode) -->
                        <div id="paymentStatusDiv" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status</label>
                            <select name="payment_status" id="paymentStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Save Allocation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Deletion</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-gray-700">Are you sure you want to remove this transport allocation? This action cannot be undone.</p>
                </div>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="allocation_id" id="deleteAllocationId">
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddModal(studentData = null) {
            document.getElementById('modalTitle').textContent = 'Add Transport Allocation';
            document.getElementById('formAction').value = 'add';
            document.getElementById('allocationForm').reset();
            document.getElementById('paymentStatusDiv').classList.add('hidden');
            document.getElementById('studentSelection').style.display = 'block';
            
            if (studentData) {
                document.getElementById('studentId').value = studentData.id;
                document.getElementById('studentId').disabled = true;
            } else {
                document.getElementById('studentId').disabled = false;
            }
            
            document.getElementById('allocationModal').classList.remove('hidden');
        }

        function openEditModal(allocationData) {
            document.getElementById('modalTitle').textContent = 'Edit Transport Allocation';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('allocationId').value = allocationData.id;
            document.getElementById('studentSelection').style.display = 'none';
            document.getElementById('paymentStatusDiv').classList.remove('hidden');
            
            document.getElementById('stopName').value = allocationData.stop_name;
            document.getElementById('pickupTime').value = allocationData.pickup_time;
            document.getElementById('dropTime').value = allocationData.drop_time;
            document.getElementById('monthlyFee').value = allocationData.monthly_fee;
            document.getElementById('academicYear').value = allocationData.academic_year;
            document.getElementById('paymentStatus').value = allocationData.payment_status;
            
            document.getElementById('allocationModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('allocationModal').classList.add('hidden');
        }

        function deleteAllocation(allocationId) {
            document.getElementById('deleteAllocationId').value = allocationId;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('allocationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>

</html> 