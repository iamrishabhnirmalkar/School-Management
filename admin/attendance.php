<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
// Save attendance on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['date'])) {
    $date = $_GET['date'];
    $statuses = $_POST['status'] ?? [];
    $remarks = $_POST['remarks'] ?? [];
    foreach ($statuses as $student_id => $status) {
        $remark = isset($remarks[$student_id]) ? htmlspecialchars(trim($remarks[$student_id])) : '';
        // Check if record exists
        $check = $conn->query("SELECT id FROM attendance WHERE student_id = $student_id AND date = '$date'");
        if ($check && $check->num_rows > 0) {
            $conn->query("UPDATE attendance SET status = '" . $conn->real_escape_string($status) . "', remarks = '" . $conn->real_escape_string($remark) . "' WHERE student_id = $student_id AND date = '$date'");
        } else {
            $conn->query("INSERT INTO attendance (student_id, date, status, remarks) VALUES ($student_id, '$date', '" . $conn->real_escape_string($status) . "', '" . $conn->real_escape_string($remark) . "')");
        }
    }
    $_SESSION['success'] = 'Attendance saved successfully!';
    header('Location: attendance.php?date=' . urlencode($date));
    exit();
}
// Fetch students for attendance marking
$students = $conn->query('SELECT id, login_id, name, class, section FROM students WHERE is_active = 1 ORDER BY class, section, name');
// Fetch attendance records (optionally filter by date)
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$attendance = $conn->query("SELECT a.*, s.login_id, s.name, s.class, s.section FROM attendance a JOIN students s ON a.student_id = s.id WHERE a.date = '$date_filter' ORDER BY s.class, s.section, s.name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-yellow-100 to-yellow-300 min-h-screen">
    <div class="max-w-6xl mx-auto py-8">
        <h1 class="text-3xl font-bold text-yellow-700 mb-6">Attendance Management</h1>
        <form method="get" class="mb-6 flex gap-4 items-center">
            <label class="font-medium">Date:</label>
            <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" class="border rounded px-3 py-2">
            <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-700">Filter</button>
        </form>
        <div class="bg-white rounded-xl shadow-lg mb-8 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-yellow-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Login ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Class</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Section</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while($row = $attendance->fetch_assoc()): ?>
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['login_id']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['class']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['section']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['status']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($row['remarks']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <h2 class="text-xl font-semibold text-yellow-700 mb-4">Mark Attendance for <?php echo htmlspecialchars($date_filter); ?></h2>
        <form method="post" action="attendance.php?date=<?php echo htmlspecialchars($date_filter); ?>" class="bg-white rounded-xl shadow-lg p-6 mb-8 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-yellow-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Login ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Class</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Section</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Remarks</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while($stu = $students->fetch_assoc()): ?>
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($stu['login_id']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($stu['name']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($stu['class']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($stu['section']); ?></td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                            <select name="status[<?php echo $stu['id']; ?>]" class="border rounded px-2 py-1">
                                <option value="Present">Present</option>
                                <option value="Absent">Absent</option>
                                <option value="Leave">Leave</option>
                            </select>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm">
                            <input type="text" name="remarks[<?php echo $stu['id']; ?>]" class="border rounded px-2 py-1 w-full">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="flex justify-end mt-4">
                <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded shadow hover:bg-yellow-700 font-semibold">Save Attendance</button>
            </div>
        </form>
        <a href="dashboard.php" class="inline-block text-yellow-700 hover:underline">&larr; Back to Dashboard</a>
    </div>
</body>
</html> 