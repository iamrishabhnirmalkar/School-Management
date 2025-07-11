<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Date filter
$date = $_GET['date'] ?? date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');

// Get all teachers
$teachers = $conn->query("
    SELECT u.id, u.full_name, u.login_id 
    FROM users u 
    JOIN teachers t ON u.id = t.user_id 
    WHERE u.role = 'teacher'
    ORDER BY u.full_name
")->fetch_all(MYSQLI_ASSOC);

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attendance_date = $_POST['date'];
    $teacher_id = $_POST['teacher_id'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? null;

    // Check if attendance already exists
    $existing = $conn->query("SELECT id FROM attendance WHERE student_id = $teacher_id AND date = '$attendance_date'")->fetch_assoc();

    if ($existing) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $remarks, $existing['id']);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, date, status, remarks) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $teacher_id, $attendance_date, $status, $remarks);
    }
    $stmt->execute();
    $_SESSION['success'] = "Attendance updated successfully!";
    header("Location: attendance.php?date=$attendance_date");
    exit;
}

// Get attendance for selected date
$attendance_data = [];
if ($date) {
    $result = $conn->query("
        SELECT a.student_id, a.status, a.remarks 
        FROM attendance a 
        WHERE a.date = '$date'
    ");
    while ($row = $result->fetch_assoc()) {
        $attendance_data[$row['student_id']] = $row;
    }
}

// Get monthly attendance summary
$monthly_summary = $conn->query("
    SELECT 
        u.id as teacher_id,
        u.full_name,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent_days,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late_days
    FROM users u
    LEFT JOIN attendance a ON u.id = a.student_id AND DATE_FORMAT(a.date, '%Y-%m') = '$month'
    WHERE u.role = 'teacher'
    GROUP BY u.id, u.full_name
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Attendance - School ERP</title>
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
                        <p class="text-blue-200">Teacher Attendance</p>
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
                            <i class="fas fa-list w-5"></i>
                            <span>Teacher List</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendance.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-calendar-check w-5"></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="import.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-file-import w-5"></i>
                            <span>Bulk Import</span>
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

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Daily Attendance</h2>

                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </form>

                <form method="post">
                    <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($teachers as $teacher): ?>
                                    <?php
                                    $attendance = $attendance_data[$teacher['id']] ?? null;
                                    $status = $attendance['status'] ?? '';
                                    $remarks = $attendance['remarks'] ?? '';
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($teacher['full_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($teacher['login_id']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="hidden" name="teacher_id" value="<?= $teacher['id'] ?>">
                                            <select name="status" class="px-3 py-2 border border-gray-300 rounded-md">
                                                <option value="present" <?= $status === 'present' ? 'selected' : '' ?>>Present</option>
                                                <option value="absent" <?= $status === 'absent' ? 'selected' : '' ?>>Absent</option>
                                                <option value="late" <?= $status === 'late' ? 'selected' : '' ?>>Late</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="text" name="remarks" value="<?= htmlspecialchars($remarks) ?>" placeholder="Remarks" class="px-3 py-2 border border-gray-300 rounded-md w-full">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button type="submit" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-save"></i> Save
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Monthly Summary</h2>

                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                        <input type="month" name="month" value="<?= htmlspecialchars($month) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Present</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Absent</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Late</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Days</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($monthly_summary as $teacher): ?>
                                <?php
                                $total_days = $teacher['present_days'] + $teacher['absent_days'] + $teacher['late_days'];
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($teacher['full_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                            <?= $teacher['present_days'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                            <?= $teacher['absent_days'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                            <?= $teacher['late_days'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            <?= $total_days ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>