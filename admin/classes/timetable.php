<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get class ID from URL
$classId = $_GET['id'] ?? null;
if (!$classId) {
    $_SESSION['error'] = "Class ID not specified";
    header("Location: index.php");
    exit;
}

// Fetch class details
$class = $conn->query("SELECT * FROM classes WHERE id = $classId")->fetch_assoc();
if (!$class) {
    $_SESSION['error'] = "Class not found";
    header("Location: index.php");
    exit;
}

// Fetch subjects for this class
$subjects = $conn->query("SELECT s.id, s.subject_name 
                         FROM subjects s 
                         WHERE s.class_id = $classId 
                         ORDER BY s.subject_name")->fetch_all(MYSQLI_ASSOC);

// Fetch teachers
$teachers = $conn->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);

// Fetch timetable for this class
$timetable = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$result = $conn->query("SELECT tt.*, s.subject_name, u.full_name as teacher_name 
                       FROM timetable tt
                       JOIN subjects s ON tt.subject_id = s.id
                       JOIN users u ON tt.teacher_id = u.id
                       WHERE tt.class_id = $classId
                       ORDER BY tt.day_of_week, tt.start_time");
while ($row = $result->fetch_assoc()) {
    $timetable[$row['day_of_week']][] = $row;
}

// Handle form submission for adding periods
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_period'])) {
    $day = $_POST['day'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $subjectId = $_POST['subject_id'];
    $teacherId = $_POST['teacher_id'];

    // Validate time slot
    $conflict = $conn->query("SELECT id FROM timetable 
                             WHERE class_id = $classId 
                             AND day_of_week = '$day'
                             AND (
                                (start_time <= '$startTime' AND end_time > '$startTime') OR 
                                (start_time < '$endTime' AND end_time >= '$endTime') OR
                                (start_time >= '$startTime' AND end_time <= '$endTime')
                             )");

    if ($conflict->num_rows > 0) {
        $_SESSION['error'] = "Time slot conflicts with existing period";
    } else {
        $stmt = $conn->prepare("INSERT INTO timetable (class_id, day_of_week, start_time, end_time, subject_id, teacher_id) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssii", $classId, $day, $startTime, $endTime, $subjectId, $teacherId);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Period added successfully";
            header("Location: timetable.php?id=$classId");
            exit;
        } else {
            $_SESSION['error'] = "Error adding period: " . $conn->error;
        }
    }
}

// Handle period deletion
if (isset($_GET['delete'])) {
    $periodId = $_GET['delete'];
    $conn->query("DELETE FROM timetable WHERE id = $periodId");
    $_SESSION['success'] = "Period deleted successfully";
    header("Location: timetable.php?id=$classId");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - <?= htmlspecialchars($class['class_name']) ?> - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .timetable-cell {
            min-height: 60px;
            position: relative;
        }

        .period-card {
            position: absolute;
            width: calc(100% - 8px);
            left: 4px;
            border-radius: 4px;
            padding: 4px;
            font-size: 12px;
            overflow: hidden;
        }

        .time-marker {
            position: absolute;
            left: 0;
            width: 100%;
            border-top: 1px dashed #e5e7eb;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
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
                        <p class="text-blue-200">Class Timetable</p>
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
                        <a href="view.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-blue-700">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span>Back to Class</span>
                        </a>
                    </li>
                    <li>
                        <a href="timetable.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-clock w-5"></i>
                            <span>Timetable</span>
                        </a>
                    </li>
                    <li>
                        <a href="fees.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-money-bill-wave w-5"></i>
                            <span>Fee Structure</span>
                        </a>
                    </li>
                    <li>
                        <a href="subjects.php?id=<?= $classId ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-book w-5"></i>
                            <span>Subjects</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">
                    Timetable for <?= htmlspecialchars($class['class_name']) ?>
                    <?= $class['section'] ? '(' . htmlspecialchars($class['section']) . ')' : '' ?>
                </h2>
                <button onclick="document.getElementById('addPeriodModal').classList.remove('hidden')"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-plus mr-2"></i> Add Period
                </button>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['success'];
                    unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Timetable Display -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Time</th>
                                <?php foreach ($days as $day): ?>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= $day ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php
                            // Generate time slots from 8:00 to 16:00 in 1-hour increments
                            $startHour = 8;
                            $endHour = 16;
                            $timeSlots = [];

                            for ($hour = $startHour; $hour < $endHour; $hour++) {
                                $timeSlots[] = sprintf("%02d:00", $hour);
                                $timeSlots[] = sprintf("%02d:30", $hour);
                            }
                            ?>

                            <?php foreach ($timeSlots as $index => $time): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-500 border-r border-gray-200"><?= $time ?></td>
                                    <?php foreach ($days as $day): ?>
                                        <td class="px-1 py-1 timetable-cell border border-gray-200">
                                            <!-- Time marker -->
                                            <div class="time-marker" style="top: 0"><?= $time ?></div>

                                            <!-- Check if there's a period at this time -->
                                            <?php if (isset($timetable[$day])): ?>
                                                <?php foreach ($timetable[$day] as $period): ?>
                                                    <?php
                                                    $periodStart = strtotime($period['start_time']);
                                                    $periodEnd = strtotime($period['end_time']);
                                                    $slotTime = strtotime($time);
                                                    $nextSlotTime = isset($timeSlots[$index + 1]) ? strtotime($timeSlots[$index + 1]) : $slotTime + 1800;

                                                    // Check if period overlaps with this time slot
                                                    if ($periodStart < $nextSlotTime && $periodEnd > $slotTime) {
                                                        // Calculate position and height
                                                        $startDiff = max(0, $periodStart - $slotTime) / 1800 * 60;
                                                        $duration = ($periodEnd - $periodStart) / 60;
                                                        $height = $duration / 30 * 60 - 2;
                                                    ?>
                                                        <div class="period-card bg-blue-100 border border-blue-200 text-blue-800"
                                                            style="top: <?= $startDiff ?>px; height: <?= $height ?>px;">
                                                            <div class="font-medium truncate"><?= htmlspecialchars($period['subject_name']) ?></div>
                                                            <div class="truncate text-xs"><?= htmlspecialchars($period['teacher_name']) ?></div>
                                                            <div class="text-xs">
                                                                <?= date('h:i A', $periodStart) ?> - <?= date('h:i A', $periodEnd) ?>
                                                            </div>
                                                            <a href="timetable.php?id=<?= $classId ?>&delete=<?= $period['id'] ?>"
                                                                class="absolute top-1 right-1 text-red-500 hover:text-red-700 text-xs"
                                                                onclick="return confirm('Delete this period?')">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        </div>
                                                    <?php } ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Period Modal -->
    <div id="addPeriodModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Add New Period</h3>
                <button onclick="document.getElementById('addPeriodModal').classList.add('hidden')"
                    class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST">
                <input type="hidden" name="add_period" value="1">

                <div class="mb-4">
                    <label for="day" class="block text-sm font-medium text-gray-700 mb-1">Day *</label>
                    <select id="day" name="day" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($days as $day): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time *</label>
                        <input type="time" id="end_time" name="end_time" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                    <select id="subject_id" name="subject_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">Teacher *</label>
                    <select id="teacher_id" name="teacher_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- Select Teacher --</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="document.getElementById('addPeriodModal').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-save mr-2"></i> Add Period
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>