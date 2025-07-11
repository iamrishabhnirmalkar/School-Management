<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../logout.php");
    exit;
}

// Get stats
$stats = [
    'students' => 0,
    'teachers' => 0,
    'classes' => 0,
    'attendance' => 0,
    'fees_due' => 0,
    'books' => 0,
    'buses' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='student'");
$stats['students'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role='teacher'");
$stats['teachers'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM classes");
$stats['classes'] = $result->fetch_assoc()['count'];

$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date='$today' AND status='present'");
$stats['attendance'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM fees WHERE status='unpaid'");
$stats['fees_due'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM library_books");
$stats['books'] = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM buses");
$stats['buses'] = $result->fetch_assoc()['count'];

// Get recent activities
$recent_activities = [];
$result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = [
        'title' => 'New Notice: ' . $row['title'],
        'description' => substr($row['content'], 0, 50) . '...',
        'time' => $row['created_at'],
        'color' => 'blue',
        'icon' => 'fas fa-bullhorn'
    ];
}

// Get recent notices
$recent_notices = [];
$result = $conn->query("SELECT * FROM notices ORDER BY created_at DESC LIMIT 3");
while ($row = $result->fetch_assoc()) {
    $recent_notices[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School ERP</title>
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

        .sidebar-item {
            transition: all 0.2s ease;
        }

        .sidebar-item:hover {
            background-color: #f3f4f6;
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>


<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Admin Dashboard</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="userMenuButton" class="flex items-center space-x-2 cursor-pointer focus:outline-none">
                            <img src="../assets/img/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
                            <span class="text-sm"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50"><i class="fas fa-user mr-2"></i>Profile</a>
                            <a href="../logout.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Navigation -->
        <aside class="w-full lg:w-64 flex-shrink-0">
            <nav class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-tachometer-alt w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="students/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-users w-5"></i>
                            <span>Student Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="teachers/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-chalkboard-teacher w-5"></i>
                            <span>Teacher Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="classes/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-school w-5"></i>
                            <span>Class Management</span>
                        </a>
                    </li>

                    <li>
                        <a href="subject/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-book w-5"></i>
                            <span>Subject Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendance/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-calendar-check w-5"></i>
                            <span>Attendance</span>
                        </a>
                    </li>
                    <li>
                        <a href="timetable/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-clock w-5"></i>
                            <span>Timetable</span>
                        </a>
                    </li>
                    <li>
                        <a href="examinations/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-clipboard-list w-5"></i>
                            <span>Examinations</span>
                        </a>
                    </li>
                    <li>
                        <a href="fees/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-money-bill-wave w-5"></i>
                            <span>Fee Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="library/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-book w-5"></i>
                            <span>Library</span>
                        </a>
                    </li>

                    <li>
                        <a href="identity-cards/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-id-card w-5"></i>
                            <span>Identity Cards</span>
                        </a>
                    </li>
                    <li>
                        <a href="transport/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-bus w-5"></i>
                            <span>Transport (Bus)</span>
                        </a>
                    </li>
                    <li>
                        <a href="notices/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-bullhorn w-5"></i>
                            <span>Notices</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports/" class="sidebar-item flex items-center space-x-3 p-3 rounded-lg">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Dashboard Content -->
        <main class="flex-1">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Student Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Students</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['students'] ?></p>
                            <a href="students/" class="text-blue-600 text-sm hover:underline">View All</a>
                        </div>
                    </div>
                </div>

                <!-- Teacher Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-chalkboard-teacher text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Teachers</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['teachers'] ?></p>
                            <a href="teachers/" class="text-green-600 text-sm hover:underline">View All</a>
                        </div>
                    </div>
                </div>

                <!-- Class Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-school text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Total Classes</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['classes'] ?></p>
                            <a href="classes/" class="text-purple-600 text-sm hover:underline">View All</a>
                        </div>
                    </div>
                </div>

                <!-- Attendance Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Today's Attendance</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['attendance'] ?></p>
                            <a href="attendance/" class="text-orange-600 text-sm hover:underline">View Report</a>
                        </div>
                    </div>
                </div>

                <!-- Fees Due Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Pending Fees</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['fees_due'] ?></p>
                            <a href="fees/" class="text-red-600 text-sm hover:underline">Manage Fees</a>
                        </div>
                    </div>
                </div>

                <!-- Library Books Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <i class="fas fa-book text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Library Books</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['books'] ?></p>
                            <a href="library/" class="text-indigo-600 text-sm hover:underline">Library</a>
                        </div>
                    </div>
                </div>

                <!-- Buses Card -->
                <div class="dashboard-card bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-teal-100 text-teal-600">
                            <i class="fas fa-bus text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-500 text-sm">Transport Buses</p>
                            <p class="text-2xl font-bold text-gray-800"><?= $stats['buses'] ?></p>
                            <a href="transport/" class="text-teal-600 text-sm hover:underline">Transport</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-6 text-gray-800">Quick Actions</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <a href="students/create.php" class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-user-plus text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Student</span>
                    </a>
                    <a href="teachers/create.php" class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Teacher</span>
                    </a>
                    <a href="classes/create.php" class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-school text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Class</span>
                    </a>
                    <a href="notices/create.php" class="flex flex-col items-center justify-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-bullhorn text-orange-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Post Notice</span>
                    </a>
                    <a href="examinations/create.php" class="flex flex-col items-center justify-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-clipboard-list text-red-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Create Exam</span>
                    </a>
                    <a href="fees/collect.php" class="flex flex-col items-center justify-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-money-bill-wave text-yellow-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Collect Fee</span>
                    </a>
                    <a href="library/issue.php" class="flex flex-col items-center justify-center p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-book-open text-indigo-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Issue Book</span>
                    </a>
                    <a href="transport/add.php" class="flex flex-col items-center justify-center p-4 bg-teal-50 rounded-lg hover:bg-teal-100 transition">
                        <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center mb-2">
                            <i class="fas fa-bus-alt text-teal-600 text-xl"></i>
                        </div>
                        <span class="text-sm font-medium text-center">Add Bus</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activities and Notices -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Activities -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">Recent Activities</h2>
                    <div class="space-y-4">
                        <?php if (empty($recent_activities)): ?>
                            <div class="text-center py-8">
                                <i class="fas fa-activity text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500">No recent activities.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="flex items-start space-x-3 p-3 border-l-4 border-blue-500 bg-blue-50 rounded-lg">
                                    <div class="flex-shrink-0">
                                        <i class="<?= $activity['icon'] ?> text-blue-600 text-lg"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($activity['title']) ?></h3>
                                        <p class="text-gray-600 text-sm"><?= htmlspecialchars($activity['description']) ?></p>
                                        <p class="text-gray-500 text-xs mt-1"><?= date('M d, Y h:i A', strtotime($activity['time'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Notices -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Recent Notices</h2>
                    <div class="space-y-4">
                        <?php if (empty($recent_notices)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-bullhorn text-2xl text-gray-400 mb-2"></i>
                                <p class="text-gray-500 text-sm">No notices posted yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_notices as $notice): ?>
                                <div class="border-l-4 border-blue-500 pl-4 py-2 hover:bg-gray-50 transition">
                                    <h3 class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($notice['title']) ?></h3>
                                    <p class="text-gray-600 text-xs"><?= htmlspecialchars(substr($notice['content'], 0, 80)) ?>...</p>
                                    <div class="flex justify-between items-center mt-1">
                                        <p class="text-gray-500 text-xs"><?= date('M d, Y', strtotime($notice['created_at'])) ?></p>
                                        <a href="notices/view.php?id=<?= $notice['id'] ?>" class="text-blue-600 text-xs hover:underline">Read More</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="notices/" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All Notices <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-100 border-t border-gray-200 py-6 mt-8">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p class="text-gray-600">© 2025 School ERP System. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-600 hover:text-blue-600"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-600 hover:text-blue-400"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-600 hover:text-red-600"><i class="fab fa-youtube"></i></a>
                    <a href="#" class="text-gray-600 hover:text-gray-900"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>
    </footer>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('userMenuButton');
            const userMenu = document.getElementById('userMenu');

            // Toggle menu on click
            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userMenu.contains(e.target) && !userBtn.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });

            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    userMenu.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>