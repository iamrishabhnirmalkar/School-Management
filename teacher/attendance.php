<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$today = date('Y-m-d');
$message = "";

// Get teacher's classes
$class_result = $conn->query("SELECT DISTINCT c.id, c.class_name, c.section 
                              FROM classes c 
                              WHERE c.class_teacher_id = $teacher_id");

$classes = [];
while ($row = $class_result->fetch_assoc()) {
    $classes[] = $row;
}

// If class selected
if (isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);

    // Check if attendance already taken
    $check = $conn->query("SELECT * FROM attendance a 
                           JOIN students s ON a.student_id = s.user_id 
                           WHERE s.class_id = $class_id AND a.date = '$today'");

    $already_taken = $check->num_rows > 0;

    // Get students of this class
    $students = [];
    $student_result = $conn->query("SELECT u.id, u.full_name FROM users u 
                                    JOIN students s ON u.id = s.user_id 
                                    WHERE s.class_id = $class_id");

    while ($row = $student_result->fetch_assoc()) {
        $students[] = $row;
    }

    // Handle POST attendance submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_taken) {
        foreach ($students as $student) {
            $status = $_POST['status'][$student['id']] ?? 'absent';
            $remarks = $_POST['remarks'][$student['id']] ?? '';
            $conn->query("INSERT INTO attendance (student_id, date, status, remarks)
                          VALUES ({$student['id']}, '$today', '$status', '$remarks')");
        }
        $message = "✅ Attendance submitted successfully.";
        $already_taken = true;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Take Attendance - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
     <!-- Header -->
     <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-blue-200">Attendance Panel</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-100">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">

    <div class="max-w-4xl mx-auto bg-white rounded shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Take Attendance - <?= date('d M Y') ?></h1>

        <?php if (!isset($_GET['class_id'])): ?>
            <form method="get">
                <label class="block mb-2 font-semibold">Select Class:</label>
                <select name="class_id" class="w-full border p-2 rounded mb-4" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['class_name'] ?> <?= $c['section'] ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Next</button>
            </form>
        <?php else: ?>
            <?php if ($already_taken): ?>
                <p class="bg-yellow-100 text-yellow-700 p-3 rounded mb-4">Attendance already taken for today.</p>
            <?php elseif (!empty($students)): ?>
                <form method="post">
                    <table class="w-full text-left border mb-4">
                        <thead>
                            <tr class="bg-gray-200">
                                <th class="p-2">Student Name</th>
                                <th class="p-2">Status</th>
                                <th class="p-2">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="p-2"><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td class="p-2">
                                        <select name="status[<?= $s['id'] ?>]" class="border p-1">
                                            <option value="present">Present</option>
                                            <option value="absent">Absent</option>
                                            <option value="late">Late</option>
                                        </select>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" name="remarks[<?= $s['id'] ?>]" class="border p-1 w-full" />
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Submit Attendance</button>
                </form>
            <?php else: ?>
                <p class="text-red-500">No students found in this class.</p>
            <?php endif; ?>

            <?php if ($message): ?>
                <p class="mt-4 text-green-600"><?= $message ?></p>
            <?php endif; ?>

            <a href="attendance.php" class="inline-block mt-4 text-blue-600 underline">← Back</a>
        <?php endif; ?>
    </div>
    </main>
</body>
</html>
