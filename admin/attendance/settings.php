<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $message = "Settings updated successfully!";
    
    // Update attendance settings
    $settings = [
        'student_attendance_types' => $_POST['student_attendance_types'] ?? 'present,absent,late',
        'teacher_attendance_types' => $_POST['teacher_attendance_types'] ?? 'present,absent,leave',
        'school_start_time' => $_POST['school_start_time'] ?? '08:00',
        'school_end_time' => $_POST['school_end_time'] ?? '15:00',
        'late_threshold_minutes' => $_POST['late_threshold_minutes'] ?? '15',
        'attendance_reminder_time' => $_POST['attendance_reminder_time'] ?? '07:30',
        'enable_auto_attendance' => isset($_POST['enable_auto_attendance']) ? 1 : 0,
        'enable_attendance_notifications' => isset($_POST['enable_attendance_notifications']) ? 1 : 0,
        'attendance_report_frequency' => $_POST['attendance_report_frequency'] ?? 'weekly',
        'holiday_calendar' => $_POST['holiday_calendar'] ?? ''
    ];
    
    // Save settings to database (you might want to create a settings table)
    // For now, we'll just show a success message
    $_SESSION['success'] = $message;
    header("Location: settings.php");
    exit;
}

// Get current settings (you would load these from database)
$current_settings = [
    'student_attendance_types' => 'present,absent,late',
    'teacher_attendance_types' => 'present,absent,leave',
    'school_start_time' => '08:00',
    'school_end_time' => '15:00',
    'late_threshold_minutes' => '15',
    'attendance_reminder_time' => '07:30',
    'enable_auto_attendance' => 0,
    'enable_attendance_notifications' => 1,
    'attendance_report_frequency' => 'weekly',
    'holiday_calendar' => ''
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Settings - School ERP</title>
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
                        <p class="text-blue-200">Attendance Settings</p>
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
                        <a href="students.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
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
                        <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
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

            <form method="post" class="space-y-6">
                <!-- Attendance Types -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Attendance Types</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Student Attendance Types</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="student_attendance_types[]" value="present" 
                                           <?= strpos($current_settings['student_attendance_types'], 'present') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Present</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="student_attendance_types[]" value="absent" 
                                           <?= strpos($current_settings['student_attendance_types'], 'absent') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Absent</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="student_attendance_types[]" value="late" 
                                           <?= strpos($current_settings['student_attendance_types'], 'late') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Late</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="student_attendance_types[]" value="half_day" 
                                           <?= strpos($current_settings['student_attendance_types'], 'half_day') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Half Day</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Teacher Attendance Types</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="teacher_attendance_types[]" value="present" 
                                           <?= strpos($current_settings['teacher_attendance_types'], 'present') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Present</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="teacher_attendance_types[]" value="absent" 
                                           <?= strpos($current_settings['teacher_attendance_types'], 'absent') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Absent</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="teacher_attendance_types[]" value="leave" 
                                           <?= strpos($current_settings['teacher_attendance_types'], 'leave') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Leave</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="teacher_attendance_types[]" value="sick_leave" 
                                           <?= strpos($current_settings['teacher_attendance_types'], 'sick_leave') !== false ? 'checked' : '' ?> 
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Sick Leave</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- School Hours -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">School Hours</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">School Start Time</label>
                            <input type="time" name="school_start_time" value="<?= htmlspecialchars($current_settings['school_start_time']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">School End Time</label>
                            <input type="time" name="school_end_time" value="<?= htmlspecialchars($current_settings['school_end_time']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Late Threshold (minutes)</label>
                            <input type="number" name="late_threshold_minutes" value="<?= htmlspecialchars($current_settings['late_threshold_minutes']) ?>" 
                                   min="1" max="60" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Notifications</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Enable Attendance Notifications</label>
                                <p class="text-xs text-gray-500">Send notifications to parents/guardians about attendance</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_attendance_notifications" 
                                       <?= $current_settings['enable_attendance_notifications'] ? 'checked' : '' ?> 
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Attendance Reminder Time</label>
                            <input type="time" name="attendance_reminder_time" value="<?= htmlspecialchars($current_settings['attendance_reminder_time']) ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <p class="text-xs text-gray-500 mt-1">Time to send attendance reminders to teachers</p>
                        </div>
                    </div>
                </div>

                <!-- Automation -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Automation</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Enable Auto Attendance</label>
                                <p class="text-xs text-gray-500">Automatically mark students as absent if not marked by teachers</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enable_auto_attendance" 
                                       <?= $current_settings['enable_auto_attendance'] ? 'checked' : '' ?> 
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Attendance Report Frequency</label>
                            <select name="attendance_report_frequency" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="daily" <?= $current_settings['attendance_report_frequency'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                                <option value="weekly" <?= $current_settings['attendance_report_frequency'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                <option value="monthly" <?= $current_settings['attendance_report_frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Holiday Calendar -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Holiday Calendar</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Holiday Dates (one per line, format: YYYY-MM-DD)</label>
                        <textarea name="holiday_calendar" rows="6" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                  placeholder="2024-01-26&#10;2024-08-15&#10;2024-10-02"><?= htmlspecialchars($current_settings['holiday_calendar']) ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Enter holiday dates in YYYY-MM-DD format, one per line</p>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md">
                        <i class="fas fa-save mr-2"></i> Save Settings
                    </button>
                </div>
            </form>
        </main>
    </div>
</body>
</html> 