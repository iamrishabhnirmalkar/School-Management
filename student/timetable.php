<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../logout.php");
    exit;
}

$student_id = $_SESSION['user']['id'];
$class_id = $_SESSION['user']['class_id'] ?? null;

// Fetch time slots from database
$time_slots_result = $conn->query("SELECT id, start_time, end_time, label FROM time_slots ORDER BY start_time ASC");
$time_slots = [];
$time_slots_data = [];
while ($row = $time_slots_result->fetch_assoc()) {
    $time_range = date('H:i', strtotime($row['start_time'])) . '-' . date('H:i', strtotime($row['end_time']));
    $display_label = $row['label'] ? $row['label'] . ' (' . $time_range . ')' : $time_range;
    $time_slots[] = $display_label;
    $time_slots_data[] = [
        'id' => $row['id'],
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'label' => $row['label'],
        'display' => $display_label
    ];
}

// Get timetable
$timetable = [];
if ($class_id) {
    $result = $conn->query("SELECT tt.day_of_week, tt.start_time, tt.end_time, 
                            s.subject_name, u.full_name as teacher_name
                            FROM timetable tt
                            JOIN subjects s ON tt.subject_id = s.id
                            JOIN users u ON tt.teacher_id = u.id
                            WHERE tt.class_id = $class_id
                            ORDER BY tt.day_of_week, tt.start_time");
    while ($row = $result->fetch_assoc()) {
        $timetable[$row['day_of_week']][] = $row;
    }
}

// Days of week for display
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Timetable - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<header class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-green-200">Student Timetable</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-green-700 px-4 py-2 rounded-lg font-semibold hover:bg-green-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">My Class Timetable</h1>
            
            <?php if (empty($timetable)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">Timetable not available for your class</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <?php foreach ($days as $day): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= $day ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($time_slots_data as $slot_data): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($slot_data['display']) ?>
                                    </td>
                                    <?php foreach ($days as $day): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php 
                                            $found = false;
                                            if (isset($timetable[$day])) {
                                                foreach ($timetable[$day] as $slot) {
                                                    if ($slot['start_time'] == $slot_data['start_time'] && $slot['end_time'] == $slot_data['end_time']) {
                                                        echo htmlspecialchars($slot['subject_name']) . '<br>';
                                                        echo '<small class="text-gray-400">'
                                                            . htmlspecialchars($slot['teacher_name']) . '</small>';
                                                        $found = true;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$found) echo '-';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="mt-6">
                <a href="download.php?type=timetable" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                    <i class="fas fa-download mr-1"></i> Download Timetable
                </a>
            </div>
        </div>
    </main>
</body>
</html>