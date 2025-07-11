<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../logout.php");
    exit;
}

// Get student details
$student_id = $_SESSION['user']['id'];
$student = [];
$result = $conn->query("SELECT u.*, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = $student_id");
if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
}

// Get class name
$class_name = "";
if ($student['class_id']) {
    $result = $conn->query("SELECT class_name, section FROM classes WHERE id = {$student['class_id']}");
    if ($result->num_rows > 0) {
        $class = $result->fetch_assoc();
        $class_name = $class['class_name'] . $class['section'];
    }
}

// Get recent notices (both general and class-specific)
$recent_notices = [];
$result = $conn->query("SELECT * FROM notices 
                        WHERE created_by = 1 OR created_by IN (SELECT user_id FROM teachers WHERE user_id IN (SELECT teacher_id FROM subjects WHERE class_id = {$student['class_id']}))
                        ORDER BY created_at DESC LIMIT 3");
while ($row = $result->fetch_assoc()) {
    $recent_notices[] = $row;
}

// Get attendance summary
$attendance_summary = [
    'present' => 0,
    'absent' => 0,
    'late' => 0
];
$result = $conn->query("SELECT status, COUNT(*) as count FROM attendance 
                        WHERE student_id = $student_id 
                        GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $attendance_summary[$row['status']] = $row['count'];
}

// Get issued books
$issued_books = [];
$result = $conn->query("SELECT b.title, b.author, bi.issue_date, bi.due_date 
                        FROM book_issues bi
                        JOIN library_books b ON bi.book_id = b.id
                        WHERE bi.student_id = $student_id AND bi.status = 'issued'");
while ($row = $result->fetch_assoc()) {
    $issued_books[] = $row;
}

// Get fee status
$fee_status = [
    'paid' => 0,
    'unpaid' => 0,
    'total' => 0
];
$result = $conn->query("SELECT 
                        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid,
                        SUM(CASE WHEN status != 'paid' THEN amount ELSE 0 END) as unpaid,
                        SUM(amount) as total
                        FROM fees WHERE student_id = $student_id");
if ($result->num_rows > 0) {
    $fee_status = $result->fetch_assoc();
}

// Get bus information if allocated
$bus_info = [];
if ($student['bus_allocation_id']) {
    $result = $conn->query("SELECT b.bus_number, b.route_name, b.current_location, 
                            ba.stop_name, ba.pickup_time, ba.drop_time
                            FROM bus_allocations ba
                            JOIN buses b ON ba.bus_id = b.id
                            WHERE ba.id = {$student['bus_allocation_id']}");
    if ($result->num_rows > 0) {
        $bus_info = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: all 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
                    <p class="text-green-200">Student Dashboard</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="container mx-auto px-6 py-8">
        <!-- Student Info Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Personal Info Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-user text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Name</p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($student['full_name']) ?></p>
                        <p class="text-sm text-gray-500">Roll: <?= htmlspecialchars($student['roll_number']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Class Info Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-school text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Class</p>
                        <p class="text-xl font-bold"><?= htmlspecialchars($class_name) ?></p>
                        <p class="text-sm text-gray-500">Year: <?= htmlspecialchars($student['current_year']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Attendance Summary Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Attendance</p>
                        <p class="text-xl font-bold">
                            <?= $attendance_summary['present'] ?> Present
                        </p>
                        <p class="text-sm text-gray-500">
                            <?= $attendance_summary['absent'] ?> Absent
                        </p>
                    </div>
                </div>
            </div>

            <!-- Fee Status Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-money-bill-wave text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Fee Status</p>
                        <p class="text-xl font-bold">
                            ₹<?= number_format($fee_status['paid'] ?? 0, 2) ?> Paid
                        </p>
                        <p class="text-sm text-gray-500">
                            ₹<?= number_format($fee_status['unpaid'] ?? 0, 2) ?> Due
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Notices -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Recent Notices</h2>
            <div class="space-y-4">
                <?php if (empty($recent_notices)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bullhorn text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500 text-sm">No notices available</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_notices as $notice): ?>
                        <div class="border-l-4 border-green-500 pl-4 py-2 hover:bg-gray-50 transition">
                            <h3 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($notice['title']) ?></h3>
                            <p class="text-gray-600 text-xs"><?= htmlspecialchars(substr($notice['content'], 0, 80)) ?>...</p>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-gray-500 text-xs"><?= date('M d, Y', strtotime($notice['created_at'])) ?></p>
                                <a href="notices/view.php?id=<?= $notice['id'] ?>" class="text-green-600 text-xs hover:underline">Read More</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="mt-4 text-center">
                <a href="notices/" class="text-green-600 hover:text-green-800 text-sm font-medium">
                    View All Notices <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Student Tools -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Student Tools</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Attendance -->
                <a href="attendance.php" class="flex flex-col items-center justify-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-calendar-check text-indigo-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">My Attendance</span>
                </a>

                <!-- Marks/Results -->
                <a href="marks.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">View Marks</span>
                </a>

                <!-- Timetable -->
                <a href="timetable.php" class="flex flex-col items-center justify-center p-4 bg-pink-50 rounded-lg hover:bg-pink-100 transition">
                    <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-clock text-pink-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Timetable</span>
                </a>

                <!-- Notices -->
                <a href="notices.php" class="flex flex-col items-center justify-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-bullhorn text-orange-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Notices</span>
                </a>

                <!-- Library -->
                <a href="library.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-book text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Library</span>
                </a>

                <!-- Fees -->
                <a href="fees.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-money-bill-wave text-yellow-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Fee Payment</span>
                </a>

                <!-- Transport -->
                <a href="transport.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-bus text-green-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Transport</span>
                </a>

                <!-- Documents -->
                <a href="documents.php" class="flex flex-col items-center justify-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-file-alt text-red-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">My Documents</span>
                </a>
            </div>
        </div>

        <!-- Quick Info Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Issued Books -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">My Library Books</h2>
                <?php if (empty($issued_books)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500 text-sm">No books currently issued</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued On</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($issued_books as $book): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($book['title']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($book['author']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($book['issue_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($book['due_date'])) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <div class="mt-4 text-center">
                    <a href="library.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                        View Library <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <!-- Bus Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Transport Information</h2>
                <?php if (empty($bus_info)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bus text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500 text-sm">No bus allocation information</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Bus Number:</span>
                            <span class="font-medium"><?= htmlspecialchars($bus_info['bus_number']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Route:</span>
                            <span class="font-medium"><?= htmlspecialchars($bus_info['route_name']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Stop:</span>
                            <span class="font-medium"><?= htmlspecialchars($bus_info['stop_name']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pickup Time:</span>
                            <span class="font-medium"><?= date('h:i A', strtotime($bus_info['pickup_time'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Drop Time:</span>
                            <span class="font-medium"><?= date('h:i A', strtotime($bus_info['drop_time'])) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Current Location:</span>
                            <span class="font-medium"><?= htmlspecialchars($bus_info['current_location']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="mt-4 text-center">
                    <a href="transport.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                        View Transport Details <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>