<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get attendance statistics
$stats = [
    'total_students' => 0,
    'total_teachers' => 0,
    'today_present_students' => 0,
    'today_present_teachers' => 0,
    'today_absent_students' => 0,
    'today_absent_teachers' => 0,
    'today_late_students' => 0,
    'today_late_teachers' => 0
];

// Get total students
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['count'];

// Get total teachers
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$stats['total_teachers'] = $result->fetch_assoc()['count'];

// Get today's date
$today = date('Y-m-d');

// Get today's student attendance
$result = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM attendance 
    WHERE date = '$today' 
    GROUP BY status
");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'present') {
        $stats['today_present_students'] = $row['count'];
    } elseif ($row['status'] === 'absent') {
        $stats['today_absent_students'] = $row['count'];
    } elseif ($row['status'] === 'late') {
        $stats['today_late_students'] = $row['count'];
    }
}

// Get today's teacher attendance
$result = $conn->query("
    SELECT status, COUNT(*) as count 
    FROM teacher_attendance 
    WHERE date = '$today' 
    GROUP BY status
");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'present') {
        $stats['today_present_teachers'] = $row['count'];
    } elseif ($row['status'] === 'absent') {
        $stats['today_absent_teachers'] = $row['count'];
    } elseif ($row['status'] === 'leave') {
        $stats['today_late_teachers'] = $row['count']; // Using late field for leave count
    }
}

// Get recent attendance records
$recent_student_attendance = $conn->query("
    SELECT a.*, u.full_name, c.class_name, c.section
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    JOIN students s ON a.student_id = s.user_id
    JOIN classes c ON s.class_id = c.id
    ORDER BY a.date DESC, u.full_name
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$recent_teacher_attendance = $conn->query("
    SELECT ta.*, u.full_name
    FROM teacher_attendance ta
    JOIN users u ON ta.teacher_id = u.id
    ORDER BY ta.date DESC, u.full_name
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get class-wise attendance summary for today
$class_attendance = $conn->query("
    SELECT 
        c.class_name,
        c.section,
        COUNT(s.user_id) as total_students,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late
    FROM classes c
    LEFT JOIN students s ON c.id = s.class_id
    LEFT JOIN attendance a ON s.user_id = a.student_id AND a.date = '$today'
    GROUP BY c.id, c.class_name, c.section
    ORDER BY c.class_name, c.section
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/images/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Attendance Management</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <div class="flex items-center space-x-2 cursor-pointer">
                            <img src="../../assets/images/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
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
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-tachometer-alt w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="students.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-user-graduate w-5"></i>
                            <span>Student Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="teachers.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-chalkboard-teacher w-5"></i>
                            <span>Teacher Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-cog w-5"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
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

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Students -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-user-graduate text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Students</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['total_students'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Teachers -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-chalkboard-teacher text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Teachers</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['total_teachers'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Today's Student Attendance -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Students Present Today</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['today_present_students'] ?></p>
                            <p class="text-xs text-gray-500">
                                <?= $stats['total_students'] > 0 ? round(($stats['today_present_students'] / $stats['total_students']) * 100, 1) : 0 ?>% attendance
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Today's Teacher Attendance -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-user-tie text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Teachers Present Today</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['today_present_teachers'] ?></p>
                            <p class="text-xs text-gray-500">
                                <?= $stats['total_teachers'] > 0 ? round(($stats['today_present_teachers'] / $stats['total_teachers']) * 100, 1) : 0 ?>% attendance
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Class-wise Attendance Summary -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Today's Class-wise Attendance</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Students</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($class_attendance as $class): ?>
                                <?php
                                $total = $class['total_students'];
                                $present = $class['present'];
                                $absent = $class['absent'];
                                $late = $class['late'];
                                $attendance_percentage = $total > 0 ? round((($present + $late) / $total) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($class['class_name']) ?>
                                            <?= $class['section'] ? ' - ' . htmlspecialchars($class['section']) : '' ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm text-gray-900"><?= $total ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <?= $present ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                            <?= $absent ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <?= $late ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="text-sm font-medium <?= $attendance_percentage >= 90 ? 'text-green-600' : ($attendance_percentage >= 75 ? 'text-yellow-600' : 'text-red-600') ?>">
                                            <?= $attendance_percentage ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Student Attendance -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Recent Student Attendance</h3>
                    <?php if (empty($recent_student_attendance)): ?>
                        <p class="text-gray-500 text-center py-4">No recent attendance records</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_student_attendance as $record): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($record['full_name']) ?></p>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars($record['class_name']) ?> - <?= htmlspecialchars($record['section']) ?>
                                        </p>
                                        <p class="text-xs text-gray-400"><?= date('M d, Y', strtotime($record['date'])) ?></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?= $record['status'] === 'present' ? 'bg-green-100 text-green-800' : 
                                           ($record['status'] === 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                        <?= ucfirst($record['status']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Teacher Attendance -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800">Recent Teacher Attendance</h3>
                    <?php if (empty($recent_teacher_attendance)): ?>
                        <p class="text-gray-500 text-center py-4">No recent attendance records</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recent_teacher_attendance as $record): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($record['full_name']) ?></p>
                                        <p class="text-xs text-gray-400"><?= date('M d, Y', strtotime($record['date'])) ?></p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?= $record['status'] === 'present' ? 'bg-green-100 text-green-800' : 
                                           ($record['status'] === 'absent' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') ?>">
                                        <?= ucfirst($record['status']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 