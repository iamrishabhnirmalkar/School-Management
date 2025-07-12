<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get filter parameters
$report_type = $_GET['type'] ?? 'daily';
$selected_class = $_GET['class_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_teacher = $_GET['teacher_id'] ?? '';
$selected_student = $_GET['student_id'] ?? '';

// Get all classes
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section")->fetch_all(MYSQLI_ASSOC);

// Get all teachers
$teachers = $conn->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

// Get all students
$students = $conn->query("
    SELECT u.id, u.full_name, c.class_name, c.section 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    JOIN classes c ON s.class_id = c.id 
    ORDER BY c.class_name, c.section, u.full_name
")->fetch_all(MYSQLI_ASSOC);

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $export_type = $_GET['export_type'] ?? 'daily';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($export_type === 'daily') {
        // Daily attendance export
        fputcsv($output, ['Date', 'Class', 'Student', 'Status', 'Remarks']);
        
        $query = "
            SELECT a.date, c.class_name, c.section, u.full_name, a.status, a.remarks
            FROM attendance a
            JOIN users u ON a.student_id = u.id
            JOIN students s ON a.student_id = s.user_id
            JOIN classes c ON s.class_id = c.id
            WHERE a.date = '$selected_date'
        ";
        
        if ($selected_class) {
            $query .= " AND s.class_id = $selected_class";
        }
        
        $query .= " ORDER BY c.class_name, c.section, u.full_name";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['date'],
                $row['class_name'] . ' - ' . $row['section'],
                $row['full_name'],
                $row['status'],
                $row['remarks']
            ]);
        }
    } elseif ($export_type === 'monthly') {
        // Monthly attendance export
        fputcsv($output, ['Month', 'Class', 'Student', 'Present Days', 'Absent Days', 'Late Days', 'Total Days', 'Attendance %']);
        
        $query = "
            SELECT 
                '$selected_month' as month,
                c.class_name,
                c.section,
                u.full_name,
                COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
                COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
                COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days,
                COUNT(a.id) as total_days
            FROM users u
            JOIN students s ON u.id = s.user_id
            JOIN classes c ON s.class_id = c.id
            LEFT JOIN attendance a ON u.id = a.student_id AND DATE_FORMAT(a.date, '%Y-%m') = '$selected_month'
        ";
        
        if ($selected_class) {
            $query .= " WHERE s.class_id = $selected_class";
        }
        
        $query .= " GROUP BY u.id, u.full_name, c.class_name, c.section ORDER BY c.class_name, c.section, u.full_name";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $total_days = $row['total_days'];
            $attendance_percentage = $total_days > 0 ? round((($row['present_days'] + $row['late_days']) / $total_days) * 100, 1) : 0;
            
            fputcsv($output, [
                $row['month'],
                $row['class_name'] . ' - ' . $row['section'],
                $row['full_name'],
                $row['present_days'],
                $row['absent_days'],
                $row['late_days'],
                $total_days,
                $attendance_percentage . '%'
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Get report data based on type
$report_data = [];

if ($report_type === 'daily') {
    // Daily attendance report
    $query = "
        SELECT a.date, c.class_name, c.section, u.full_name, a.status, a.remarks
        FROM attendance a
        JOIN users u ON a.student_id = u.id
        JOIN students s ON a.student_id = s.user_id
        JOIN classes c ON s.class_id = c.id
        WHERE a.date = '$selected_date'
    ";
    
    if ($selected_class) {
        $query .= " AND s.class_id = $selected_class";
    }
    
    $query .= " ORDER BY c.class_name, c.section, u.full_name";
    
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }
} elseif ($report_type === 'monthly') {
    // Monthly attendance report
    $query = "
        SELECT 
            c.class_name,
            c.section,
            u.full_name,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days,
            COUNT(a.id) as total_days
        FROM users u
        JOIN students s ON u.id = s.user_id
        JOIN classes c ON s.class_id = c.id
        LEFT JOIN attendance a ON u.id = a.student_id AND DATE_FORMAT(a.date, '%Y-%m') = '$selected_month'
    ";
    
    if ($selected_class) {
        $query .= " WHERE s.class_id = $selected_class";
    }
    
    $query .= " GROUP BY u.id, u.full_name, c.class_name, c.section ORDER BY c.class_name, c.section, u.full_name";
    
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }
} elseif ($report_type === 'teacher') {
    // Teacher attendance report
    $query = "
        SELECT 
            u.full_name,
            u.login_id,
            COUNT(CASE WHEN ta.status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN ta.status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN ta.status = 'leave' THEN 1 END) as leave_days,
            COUNT(ta.id) as total_days
        FROM users u
        LEFT JOIN teacher_attendance ta ON u.id = ta.teacher_id AND DATE_FORMAT(ta.date, '%Y-%m') = '$selected_month'
        WHERE u.role = 'teacher'
    ";
    
    if ($selected_teacher) {
        $query .= " AND u.id = $selected_teacher";
    }
    
    $query .= " GROUP BY u.id, u.full_name, u.login_id ORDER BY u.full_name";
    
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }
} elseif ($report_type === 'individual') {
    // Individual student attendance report
    if ($selected_student) {
        $query = "
            SELECT 
                a.date,
                a.status,
                a.remarks,
                c.class_name,
                c.section
            FROM attendance a
            JOIN users u ON a.student_id = u.id
            JOIN students s ON a.student_id = s.user_id
            JOIN classes c ON s.class_id = c.id
            WHERE a.student_id = $selected_student
            ORDER BY a.date DESC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - School ERP</title>
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
                        <p class="text-blue-200">Attendance Reports</p>
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
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
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
                        <a href="reports.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
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
            <!-- Report Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Report Filters</h2>
                
                <form method="get" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="daily" <?= $report_type === 'daily' ? 'selected' : '' ?>>Daily Attendance</option>
                                <option value="monthly" <?= $report_type === 'monthly' ? 'selected' : '' ?>>Monthly Summary</option>
                                <option value="teacher" <?= $report_type === 'teacher' ? 'selected' : '' ?>>Teacher Attendance</option>
                                <option value="individual" <?= $report_type === 'individual' ? 'selected' : '' ?>>Individual Student</option>
                            </select>
                        </div>
                        
                        <?php if ($report_type === 'daily' || $report_type === 'monthly'): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">All Classes</option>
                                    <?php foreach ($classes as $class): ?>
                                                                        <option value="<?= $class['id'] ?>" <?= $selected_class == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name'] ?? '') ?> - <?= htmlspecialchars($class['section'] ?? '') ?>
                                </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($report_type === 'daily'): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($report_type === 'monthly' || $report_type === 'teacher'): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                                <input type="month" name="month" value="<?= htmlspecialchars($selected_month) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($report_type === 'teacher'): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                                <select name="teacher_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">All Teachers</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= $teacher['id'] ?>" <?= $selected_teacher == $teacher['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($teacher['full_name'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($report_type === 'individual'): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                                <select name="student_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                    <option value="">Select Student</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>" <?= $selected_student == $student['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($student['full_name'] ?? '') ?> - <?= htmlspecialchars($student['class_name'] ?? '') ?> <?= htmlspecialchars($student['section'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-search mr-2"></i> Generate Report
                        </button>
                        
                        <?php if (!empty($report_data)): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv', 'export_type' => $report_type])) ?>" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-download mr-2"></i> Export CSV
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Report Results -->
            <?php if (!empty($report_data)): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">
                        <?php
                        switch ($report_type) {
                            case 'daily':
                                echo 'Daily Attendance Report - ' . date('M d, Y', strtotime($selected_date));
                                break;
                            case 'monthly':
                                echo 'Monthly Attendance Summary - ' . date('F Y', strtotime($selected_month . '-01'));
                                break;
                            case 'teacher':
                                echo 'Teacher Attendance Report - ' . date('F Y', strtotime($selected_month . '-01'));
                                break;
                            case 'individual':
                                echo 'Individual Student Attendance Report';
                                break;
                        }
                        ?>
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <?php if ($report_type === 'daily'): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M d, Y', strtotime($record['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($record['class_name'] ?? '') ?> - <?= htmlspecialchars($record['section'] ?? '') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($record['full_name'] ?? '') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    <?= $record['status'] === 'present' ? 'bg-green-100 text-green-800' : 
                                                       ($record['status'] === 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                    <?= ucfirst($record['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($record['remarks'] ?? '') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php elseif ($report_type === 'monthly'): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Days</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($report_data as $record): ?>
                                        <?php
                                        $total_days = $record['total_days'];
                                        $attendance_percentage = $total_days > 0 ? round((($record['present_days'] + $record['late_days']) / $total_days) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($record['class_name'] ?? '') ?> - <?= htmlspecialchars($record['section'] ?? '') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($record['full_name'] ?? '') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                    <?= $record['present_days'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                    <?= $record['absent_days'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                    <?= $record['late_days'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="text-sm text-gray-900"><?= $total_days ?></span>
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
                        <?php elseif ($report_type === 'teacher'): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login ID</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Leave</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Days</th>
                                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($report_data as $record): ?>
                                        <?php
                                        $total_days = $record['total_days'];
                                        $attendance_percentage = $total_days > 0 ? round((($record['present_days'] + $record['leave_days']) / $total_days) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($record['full_name']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($record['login_id']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                    <?= $record['present_days'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                    <?= $record['absent_days'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                                    <?= $record['leave_days'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span class="text-sm text-gray-900"><?= $total_days ?></span>
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
                        <?php elseif ($report_type === 'individual'): ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M d, Y', strtotime($record['date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($record['class_name']) ?> - <?= htmlspecialchars($record['section']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full 
                                                    <?= $record['status'] === 'present' ? 'bg-green-100 text-green-800' : 
                                                       ($record['status'] === 'absent' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') ?>">
                                                    <?= ucfirst($record['status']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($record['remarks'] ?? '') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (isset($_GET['type'])): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <p class="text-gray-500 text-center py-8">No data found for the selected criteria.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 