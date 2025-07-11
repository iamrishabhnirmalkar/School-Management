<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

// Get teacher details
$teacher_id = $_SESSION['user']['id'];
$teacher = [];
$result = $conn->query("SELECT u.*, t.* FROM users u JOIN teachers t ON u.id = t.user_id WHERE u.id = $teacher_id");
if ($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
}

// Get assigned classes and subjects
$assigned_classes = [];
$result = $conn->query("SELECT DISTINCT c.id, c.class_name, c.section, s.subject_name, s.id as subject_id 
                        FROM classes c 
                        JOIN subjects s ON c.id = s.class_id 
                        WHERE s.teacher_id = $teacher_id OR c.class_teacher_id = $teacher_id");
while ($row = $result->fetch_assoc()) {
    $assigned_classes[] = $row;
}

// Get today's attendance summary
$today = date('Y-m-d');
$attendance_summary = [
    'total_students' => 0,
    'present' => 0,
    'absent' => 0,
    'late' => 0
];

// Get total students in teacher's classes
$class_ids = array_unique(array_column($assigned_classes, 'id'));
if (!empty($class_ids)) {
    $class_ids_str = implode(',', $class_ids);

    // Total students
    $result = $conn->query("SELECT COUNT(*) as total FROM students WHERE class_id IN ($class_ids_str)");
    if ($result->num_rows > 0) {
        $attendance_summary['total_students'] = $result->fetch_assoc()['total'];
    }

    // Today's attendance
    $result = $conn->query("SELECT a.status, COUNT(*) as count FROM attendance a 
                           JOIN students s ON a.student_id = s.user_id 
                           WHERE s.class_id IN ($class_ids_str) AND a.date = '$today' 
                           GROUP BY a.status");
    while ($row = $result->fetch_assoc()) {
        $attendance_summary[$row['status']] = $row['count'];
    }
}

// Get pending homework assignments
$pending_homework = [];
$result = $conn->query("SELECT h.*, c.class_name, c.section, s.subject_name 
                        FROM homework h 
                        JOIN classes c ON h.class_id = c.id 
                        JOIN subjects s ON h.subject_id = s.id 
                        WHERE h.teacher_id = $teacher_id AND h.due_date >= CURDATE() 
                        ORDER BY h.due_date ASC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $pending_homework[] = $row;
}

// Get recent notices
$recent_notices = [];
$result = $conn->query("SELECT * FROM notices WHERE created_by = $teacher_id ORDER BY created_at DESC LIMIT 3");
while ($row = $result->fetch_assoc()) {
    $recent_notices[] = $row;
}

// Get teacher attendance summary
$teacher_attendance = [
    'present' => 0,
    'absent' => 0,
    'leave' => 0
];
$result = $conn->query("SELECT status, COUNT(*) as count FROM teacher_attendance 
                        WHERE teacher_id = $teacher_id 
                        GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $teacher_attendance[$row['status']] = $row['count'];
}

// Get pending leave requests
$pending_leaves = [];
$result = $conn->query("SELECT * FROM teacher_leaves WHERE teacher_id = $teacher_id AND status = 'pending' ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $pending_leaves[] = $row;
}

// Get upcoming timetable for today
$day_of_week = date('l');
$today_timetable = [];
$result = $conn->query("SELECT t.*, c.class_name, c.section, s.subject_name 
                        FROM timetable t 
                        JOIN classes c ON t.class_id = c.id 
                        JOIN subjects s ON t.subject_id = s.id 
                        WHERE t.teacher_id = $teacher_id AND t.day_of_week = '$day_of_week' 
                        ORDER BY t.start_time ASC");
while ($row = $result->fetch_assoc()) {
    $today_timetable[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .dashboard-card {
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .time-slot {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Header -->
    <header class="bg-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-blue-200">Teacher Dashboard</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="container mx-auto px-6 py-8">
        <!-- Teacher Info Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Personal Info Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-user-tie text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Teacher</p>
                        <p class="text-lg font-bold"><?= htmlspecialchars($teacher['full_name'] ?? '') ?></p>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($teacher['specialization'] ?? 'General') ?></p>
                    </div>
                </div>
            </div>

            <!-- Classes Count Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-chalkboard-teacher text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Assigned Classes</p>
                        <p class="text-xl font-bold"><?= count(array_unique(array_column($assigned_classes, 'id'))) ?></p>
                        <p class="text-sm text-gray-500">Total Students: <?= $attendance_summary['total_students'] ?></p>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Today's Attendance</p>
                        <p class="text-xl font-bold"><?= $attendance_summary['present'] ?> Present</p>
                        <p class="text-sm text-gray-500"><?= $attendance_summary['absent'] ?> Absent</p>
                    </div>
                </div>
            </div>

            <!-- My Attendance Card -->
            <div class="dashboard-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">My Attendance</p>
                        <p class="text-xl font-bold"><?= $teacher_attendance['present'] ?> Days</p>
                        <p class="text-sm text-gray-500"><?= $teacher_attendance['leave'] ?> Leaves</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timetable Section: Full Week Modern Table (Dynamic Time Slots) -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Weekly Timetable</h2>
            <?php
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            // Fetch all timetable entries for this teacher
            $all_entries = $conn->query("SELECT t.*, c.class_name, c.section, s.subject_name FROM timetable t JOIN classes c ON t.class_id = c.id JOIN subjects s ON t.subject_id = s.id WHERE t.teacher_id = $teacher_id")->fetch_all(MYSQLI_ASSOC);
            // Get all unique time slots from the DB
            $slot_set = [];
            foreach ($all_entries as $entry) {
                $slot_key = $entry['start_time'] . '|' . $entry['end_time'];
                $slot_set[$slot_key] = [
                    'start' => $entry['start_time'],
                    'end' => $entry['end_time']
                ];
            }
            // Sort slots by start time
            usort($slot_set, function($a, $b) {
                return strcmp($a['start'], $b['start']);
            });
            // Format slots for display
            $time_slots = array_map(function($slot) {
                return date('H:i', strtotime($slot['start'])) . '-' . date('H:i', strtotime($slot['end']));
            }, $slot_set);
            // Organize timetable by day and slot
            $timetable = [];
            foreach ($all_entries as $entry) {
                $slot = date('H:i', strtotime($entry['start_time'])) . '-' . date('H:i', strtotime($entry['end_time']));
                $timetable[$entry['day_of_week']][$slot][] = [
                    'subject' => $entry['subject_name'],
                    'class_name' => $entry['class_name'] . ($entry['section'] ? ' ' . $entry['section'] : ''),
                    'room_number' => $entry['room_number']
                ];
            }
            // Color palette for subjects
            $subject_colors = ['bg-blue-100 text-blue-800','bg-green-100 text-green-800','bg-yellow-100 text-yellow-800','bg-pink-100 text-pink-800','bg-purple-100 text-purple-800','bg-orange-100 text-orange-800','bg-teal-100 text-teal-800'];
            $subject_color_map = [];
            $color_idx = 0;
            foreach ($all_entries as $entry) {
                $subj = $entry['subject_name'];
                if (!isset($subject_color_map[$subj])) {
                    $subject_color_map[$subj] = $subject_colors[$color_idx % count($subject_colors)];
                    $color_idx++;
                }
            }
            ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-center border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-2 px-4 border">Time</th>
                            <?php foreach ($days as $day): ?>
                                <th class="py-2 px-4 border"><?= $day ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($time_slots as $slot): ?>
                            <tr>
                                <td class="py-2 px-4 border font-semibold bg-gray-50"><?= $slot ?></td>
                                <?php foreach ($days as $day): ?>
                                    <td class="py-2 px-4 border">
                                        <?php if (!empty($timetable[$day][$slot])): ?>
                                            <?php foreach ($timetable[$day][$slot] as $class): ?>
                                                <div class="rounded p-2 mb-1 <?= $subject_color_map[$class['subject']] ?>">
                                                    <div class="font-bold text-xs"><?= htmlspecialchars($class['subject']) ?></div>
                                                    <div class="text-xs">Class: <?= htmlspecialchars($class['class_name']) ?></div>
                                                    <?php if ($class['room_number']): ?>
                                                        <div class="text-xs">Room: <?= htmlspecialchars($class['room_number']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-gray-300">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex justify-between items-center">
                <a href="timetable.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View Full Timetable <i class="fas fa-arrow-right ml-1"></i>
                </a>
                <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-print mr-1"></i>Print Timetable
                </button>
            </div>
        </div>

        <!-- Teacher Tools -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Teacher Tools</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <!-- Take Attendance -->
                <a href="attendance.php" class="flex flex-col items-center justify-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-user-check text-indigo-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Take Attendance</span>
                </a>

                <!-- Assign Homework -->
                <a href="homework.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-tasks text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Assign Homework</span>
                </a>

                <!-- Upload Study Material -->
                <a href="study-materials.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-upload text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Study Materials</span>
                </a>

                <!-- Upload Marks -->
                <a href="marks.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-chart-line text-green-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Upload Marks</span>
                </a>

                <!-- Notices -->
                <a href="notices.php" class="flex flex-col items-center justify-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                    <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-bullhorn text-orange-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Notices</span>
                </a>

                <!-- Timetable -->
                <a href="timetable.php" class="flex flex-col items-center justify-center p-4 bg-pink-50 rounded-lg hover:bg-pink-100 transition">
                    <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-clock text-pink-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Timetable</span>
                </a>

                <!-- Apply Leave -->
                <a href="leaves.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-calendar-minus text-yellow-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">Apply Leave</span>
                </a>

                <!-- My Students -->
                <a href="students.php" class="flex flex-col items-center justify-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-2">
                        <i class="fas fa-users text-red-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-center">My Students</span>
                </a>
            </div>
        </div>

        <!-- Quick Info Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Homework Assignments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Recent Homework Assignments</h2>
                <?php if (empty($pending_homework)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tasks text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500 text-sm">No pending homework assignments</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pending_homework as $homework): ?>
                            <div class="border-l-4 border-blue-500 pl-4 py-2 hover:bg-gray-50 transition">
                                <h3 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($homework['title']) ?></h3>
                                <p class="text-gray-600 text-xs">Class: <?= htmlspecialchars($homework['class_name']) ?> <?= htmlspecialchars($homework['section']) ?> - <?= htmlspecialchars($homework['subject_name']) ?></p>
                                <div class="flex justify-between items-center mt-1">
                                    <p class="text-gray-500 text-xs">Due: <?= date('M d, Y', strtotime($homework['due_date'])) ?></p>
                                    <a href="homework/view.php?id=<?= $homework['id'] ?>" class="text-blue-600 text-xs hover:underline">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-4 text-center">
                    <a href="homework.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        View All Homework <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <!-- My Classes & Subjects -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4 text-gray-800">My Classes & Subjects</h2>
                <?php if (empty($assigned_classes)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chalkboard text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500 text-sm">No classes assigned</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php
                        $grouped_classes = [];
                        foreach ($assigned_classes as $class) {
                            $class_key = $class['class_name'] . ' ' . $class['section'];
                            if (!isset($grouped_classes[$class_key])) {
                                $grouped_classes[$class_key] = [];
                            }
                            $grouped_classes[$class_key][] = $class['subject_name'];
                        }

                        foreach ($grouped_classes as $class_name => $subjects): ?>
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($class_name) ?></h3>
                                <p class="text-sm text-gray-600">
                                    Subjects: <?= implode(', ', array_unique($subjects)) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="mt-4 text-center">
                    <a href="classes.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        View All Classes <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Notices -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h2 class="text-xl font-bold mb-4 text-gray-800">My Recent Notices</h2>
            <div class="space-y-4">
                <?php if (empty($recent_notices)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-bullhorn text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500 text-sm">No notices created yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_notices as $notice): ?>
                        <div class="border-l-4 border-blue-500 pl-4 py-2 hover:bg-gray-50 transition">
                            <h3 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($notice['title']) ?></h3>
                            <p class="text-gray-600 text-xs"><?= htmlspecialchars(substr($notice['content'], 0, 80)) ?>...</p>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-gray-500 text-xs"><?= date('M d, Y', strtotime($notice['created_at'])) ?></p>
                                <a href="notices/view.php?id=<?= $notice['id'] ?>" class="text-blue-600 text-xs hover:underline">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="mt-4 text-center">
                <a href="notices.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Manage Notices <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>

        <!-- Leave Status -->
        <?php if (!empty($pending_leaves)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mt-8">
                <h2 class="text-xl font-bold mb-4 text-gray-800">Pending Leave Requests</h2>
                <div class="space-y-3">
                    <?php foreach ($pending_leaves as $leave): ?>
                        <div class="p-3 border rounded-lg bg-yellow-50 border-yellow-200">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-semibold text-gray-800">Leave Date: <?= date('M d, Y', strtotime($leave['leave_date'])) ?></p>
                                    <p class="text-sm text-gray-600">Reason: <?= htmlspecialchars($leave['reason']) ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                    <?= ucfirst($leave['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 text-center">
                    <a href="leaves.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Manage Leaves <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>