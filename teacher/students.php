<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];
$students = [];

// Get student list from teacher's assigned classes
$result = $conn->query("
    SELECT s.user_id, u.full_name, s.roll_number, c.class_name, c.section
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN classes c ON s.class_id = c.id
    WHERE c.class_teacher_id = $teacher_id
");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Students - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-blue-200">My Students</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-blue-100">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Content -->
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded shadow p-6">
            <h2 class="text-2xl font-bold mb-4">My Students</h2>

            <?php if (empty($students)): ?>
                <p class="text-gray-600">No students found in your assigned class.</p>
            <?php else: ?>
                <table class="w-full table-auto border">
                    <thead>
                        <tr class="bg-gray-200 text-left">
                            <th class="p-2 border">Roll No.</th>
                            <th class="p-2 border">Name</th>
                            <th class="p-2 border">Class</th>
                            <th class="p-2 border">Section</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr class="border-b">
                                <td class="p-2 border"><?= htmlspecialchars($student['roll_number']) ?></td>
                                <td class="p-2 border"><?= htmlspecialchars($student['full_name']) ?></td>
                                <td class="p-2 border"><?= htmlspecialchars($student['class_name']) ?></td>
                                <td class="p-2 border"><?= htmlspecialchars($student['section']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
