<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM timetable WHERE id = $id");
    header("Location: index.php");
    exit;
}

// Handle creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = $_POST['class_id'];
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $room_number = $_POST['room_number'];

    $stmt = $conn->prepare("INSERT INTO timetable (class_id, subject_id, teacher_id, day_of_week, start_time, end_time, room_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissss", $class_id, $subject_id, $teacher_id, $day_of_week, $start_time, $end_time, $room_number);
    $stmt->execute();
    header("Location: index.php");
    exit;
}

// Fetch classes, subjects, teachers for dropdowns
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section")->fetch_all(MYSQLI_ASSOC);
$subjects = $conn->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")->fetch_all(MYSQLI_ASSOC);
$teachers = $conn->query("SELECT id, full_name FROM users WHERE role='teacher' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

// Fetch timetable entries
$timetable = $conn->query("SELECT t.*, c.class_name, c.section, s.subject_name, u.full_name as teacher_name FROM timetable t JOIN classes c ON t.class_id = c.id JOIN subjects s ON t.subject_id = s.id JOIN users u ON t.teacher_id = u.id ORDER BY c.class_name, t.day_of_week, t.start_time")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Timetable Management</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../../admin/dashboard.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    <div class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Add Timetable Entry</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class *</label>
                    <select name="class_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id'] ?>">
                                <?= htmlspecialchars($class['class_name']) ?> <?= htmlspecialchars($class['section']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                    <select name="subject_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>">
                                <?= htmlspecialchars($subject['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teacher *</label>
                    <select name="teacher_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>">
                                <?= htmlspecialchars($teacher['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Day of Week *</label>
                    <select name="day_of_week" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Day</option>
                        <?php foreach (["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"] as $day): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time *</label>
                    <input type="time" name="start_time" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time *</label>
                    <input type="time" name="end_time" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Room Number</label>
                    <input type="text" name="room_number" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="md:col-span-3 flex justify-end items-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Add Entry</button>
                </div>
            </form>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Timetable Entries</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">Class</th>
                            <th class="px-4 py-2">Subject</th>
                            <th class="px-4 py-2">Teacher</th>
                            <th class="px-4 py-2">Day</th>
                            <th class="px-4 py-2">Start</th>
                            <th class="px-4 py-2">End</th>
                            <th class="px-4 py-2">Room</th>
                            <th class="px-4 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($timetable)): ?>
                            <tr><td colspan="8" class="px-4 py-4 text-center text-gray-500">No entries found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($timetable as $entry): ?>
                                <tr>
                                    <td class="px-4 py-2"><?= htmlspecialchars($entry['class_name']) ?> <?= htmlspecialchars($entry['section']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($entry['subject_name']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($entry['teacher_name']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($entry['day_of_week']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($entry['start_time']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($entry['end_time']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($entry['room_number'] ?? '') ?></td>
                                    <td class="px-4 py-2">
                                        <a href="?delete=<?= $entry['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this entry?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
