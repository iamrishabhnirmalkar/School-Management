<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get filter parameters
$selected_class = $_GET['class_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_month = $_GET['month'] ?? date('Y-m');

// Get all classes
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section")->fetch_all(MYSQLI_ASSOC);

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date = $_POST['date'];
    $class_id = $_POST['class_id'];
    
    // Get all students in the class
    $students = $conn->query("
        SELECT u.id, u.full_name 
        FROM users u 
        JOIN students s ON u.id = s.user_id 
        WHERE s.class_id = $class_id
    ")->fetch_all(MYSQLI_ASSOC);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($students as $student) {
        $student_id = $student['id'];
        $status = $_POST['status'][$student_id] ?? 'absent';
        $remarks = $_POST['remarks'][$student_id] ?? '';
        
        // Check if attendance already exists for this student and date
        $existing = $conn->query("
            SELECT id FROM attendance 
            WHERE student_id = $student_id AND date = '$attendance_date'
        ")->fetch_assoc();
        
        if ($existing) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ? WHERE id = ?");
            $stmt->bind_param("ssi", $status, $remarks, $existing['id']);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, remarks) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $student_id, $attendance_date, $status, $remarks);
        }
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    if ($success_count > 0) {
        $_SESSION['success'] = "Attendance updated successfully for $success_count students!";
    }
    if ($error_count > 0) {
        $_SESSION['error'] = "Failed to update attendance for $error_count students.";
    }
    
    header("Location: students.php?class_id=$class_id&date=$attendance_date");
    exit;
}

// Get students and their attendance for selected class and date
$students = [];
$attendance_data = [];

if ($selected_class) {
    // Get students in the selected class
    $students = $conn->query("
        SELECT u.id, u.full_name, u.admission_number, s.roll_number
        FROM users u 
        JOIN students s ON u.id = s.user_id 
        WHERE s.class_id = $selected_class
        ORDER BY s.roll_number, u.full_name
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Get attendance data for the selected date
    if ($students) {
        $student_ids = array_column($students, 'id');
        $student_ids_str = implode(',', $student_ids);
        
        $result = $conn->query("
            SELECT student_id, status, remarks 
            FROM attendance 
            WHERE student_id IN ($student_ids_str) AND date = '$selected_date'
        ");
        
        while ($row = $result->fetch_assoc()) {
            $attendance_data[$row['student_id']] = $row;
        }
    }
}

// Get monthly attendance summary for selected class
$monthly_summary = [];
if ($selected_class) {
    $monthly_summary = $conn->query("
        SELECT 
            u.id as student_id,
            u.full_name,
            s.roll_number,
            COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days
        FROM users u
        JOIN students s ON u.id = s.user_id
        LEFT JOIN attendance a ON u.id = a.student_id AND DATE_FORMAT(a.date, '%Y-%m') = '$selected_month'
        WHERE s.class_id = $selected_class
        GROUP BY u.id, u.full_name, s.roll_number
        ORDER BY s.roll_number, u.full_name
    ")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance - School ERP</title>
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
                        <p class="text-blue-200">Student Attendance Management</p>
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
                        <a href="students.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
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

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Attendance Filters</h2>
                
                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select name="class_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $selected_class == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?> - <?= htmlspecialchars($class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($selected_class && !empty($students)): ?>
                <!-- Attendance Form -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Mark Attendance</h2>
                    
                    <form method="post">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">
                        <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class) ?>">
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($students as $student): ?>
                                        <?php
                                        $attendance = $attendance_data[$student['id']] ?? null;
                                        $status = $attendance['status'] ?? 'present';
                                        $remarks = $attendance['remarks'] ?? '';
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['full_name'] ?? '') ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($student['admission_number'] ?? '') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($student['roll_number'] ?? '') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <select name="status[<?= $student['id'] ?>]" class="px-3 py-2 border border-gray-300 rounded-md">
                                                    <option value="present" <?= $status === 'present' ? 'selected' : '' ?>>Present</option>
                                                    <option value="absent" <?= $status === 'absent' ? 'selected' : '' ?>>Absent</option>
                                                    <option value="late" <?= $status === 'late' ? 'selected' : '' ?>>Late</option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="text" name="remarks[<?= $student['id'] ?>]" value="<?= htmlspecialchars($remarks) ?>" 
                                                       class="px-3 py-2 border border-gray-300 rounded-md w-full" placeholder="Optional remarks">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md">
                                <i class="fas fa-save mr-2"></i> Save Attendance
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Monthly Summary -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Monthly Attendance Summary</h2>
                    
                    <form method="get" class="mb-4">
                        <input type="hidden" name="class_id" value="<?= htmlspecialchars($selected_class) ?>">
                        <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">
                        <div class="flex items-center space-x-4">
                            <label class="text-sm font-medium text-gray-700">Month:</label>
                            <input type="month" name="month" value="<?= htmlspecialchars($selected_month) ?>" class="px-3 py-2 border border-gray-300 rounded-md">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-search mr-2"></i> View
                            </button>
                        </div>
                    </form>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No.</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Days</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance %</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($monthly_summary as $student): ?>
                                    <?php
                                    $total_days = $student['present_days'] + $student['absent_days'] + $student['late_days'];
                                    $attendance_percentage = $total_days > 0 ? round((($student['present_days'] + $student['late_days']) / $total_days) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['full_name'] ?? '') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($student['roll_number'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                <?= $student['present_days'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                <?= $student['absent_days'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                                <?= $student['late_days'] ?>
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
                    </div>
                </div>
            <?php elseif ($selected_class): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <p class="text-gray-500 text-center py-8">No students found in the selected class.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <p class="text-gray-500 text-center py-8">Please select a class to view attendance.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 