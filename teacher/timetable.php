<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is teacher
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'teacher') {
    header("Location: ../logout.php");
    exit;
}

$teacher_id = $_SESSION['user']['id'];

// Define time slots
$time_slots = [
    '08:00-09:00',
    '09:00-10:00',
    '10:00-11:00',
    '11:00-12:00',
    '12:00-13:00',
    '13:00-14:00'
];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Fetch timetable entries for this teacher
$timetable_entries = $conn->query("SELECT t.*, c.class_name, c.section, s.subject_name FROM timetable t JOIN classes c ON t.class_id = c.id JOIN subjects s ON t.subject_id = s.id WHERE t.teacher_id = $teacher_id")->fetch_all(MYSQLI_ASSOC);

// Organize timetable by day and time slot
$timetable = [];
foreach ($timetable_entries as $entry) {
    $slot = substr($entry['start_time'], 0, 5) . '-' . substr($entry['end_time'], 0, 5);
    $timetable[$entry['day_of_week']][$slot] = [
        'subject' => $entry['subject_name'],
        'class_name' => $entry['class_name'] . ($entry['section'] ? ' ' . $entry['section'] : ''),
        'room_number' => $entry['room_number']
    ];
}
?>
<!-- Header same as dashboard -->
<header class="bg-blue-700 text-white shadow-md">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
            <div>
                <h1 class="text-2xl font-bold">School ERP System</h1>
                <p class="text-blue-200">Teacher Timetable</p>
            </div>
        </div>
        <div class="flex items-center space-x-4">
            <a href="dashboard.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
            </a>
            <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
        </div>
    </div>
</header>
<main class="container mx-auto px-6 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold mb-6 text-gray-800 border-b pb-2">Teacher Timetable</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 border">Day/Time</th>
                        <?php foreach($time_slots as $slot): ?>
                        <th class="py-2 px-4 border"><?php echo $slot; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($days as $day): ?>
                    <tr>
                        <td class="py-2 px-4 border font-medium"><?php echo $day; ?></td>
                        <?php foreach($time_slots as $slot): 
                            $class = $timetable[$day][$slot] ?? null;
                        ?>
                        <td class="py-2 px-4 border">
                            <?php if($class): ?>
                            <div class="text-center p-1 bg-blue-50 rounded">
                                <p class="font-medium"><?php echo htmlspecialchars($class['subject']); ?></p>
                                <p class="text-sm"><?php echo htmlspecialchars($class['class_name']); ?></p>
                                <p class="text-xs">Room <?php echo htmlspecialchars($class['room_number']); ?></p>
                            </div>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                <i class="fas fa-print mr-2"></i>Print Timetable
            </button>
        </div>
    </div>
</main>